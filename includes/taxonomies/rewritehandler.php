<?php
/**
 * Rewrite Handler
 * 
 * Handles URL rewriting and template loading for taxonomies
 * 
 * @package ParfumeReviews
 * @subpackage Taxonomies
 * @since 2.0.0
 */

namespace ParfumeReviews\Taxonomies;

/**
 * RewriteHandler Class
 * 
 * Manages rewrite rules and template loading for taxonomies
 */
class RewriteHandler {
    
    /**
     * Taxonomy configuration
     * 
     * @var array
     */
    private $config;
    
    /**
     * Supported taxonomies
     * 
     * @var array
     */
    private $taxonomies;
    
    /**
     * Constructor
     * 
     * @param array $config Taxonomy configuration
     */
    public function __construct($config) {
        // ВАЖНО: Валидация на конфигурацията
        if (!is_array($config)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ParfumeReviews\Taxonomies\RewriteHandler: Config is not an array! Type: ' . gettype($config));
            }
            $config = [];
        }
        
        $this->config = $config;
        $this->taxonomies = !empty($config) ? array_keys($config) : [];
        
        // Add rewrite rules with high priority
        add_action('init', [$this, 'add_rewrite_rules'], 1);
    }
    
    /**
     * Add custom rewrite rules
     */
    public function add_rewrite_rules() {
        // Проверка дали има конфигурация
        if (empty($this->config) || !is_array($this->config)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ParfumeReviews\Taxonomies\RewriteHandler: No valid config for rewrite rules');
            }
            return;
        }
        
        $settings = get_option('parfume_reviews_settings', []);
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Add archive rules for each taxonomy
        foreach ($this->config as $taxonomy => $tax_config) {
            // Валидация на taxonomy конфигурацията
            if (!is_array($tax_config)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("ParfumeReviews: Taxonomy '$taxonomy' config is not array! Skipping.");
                }
                continue;
            }
            
            $tax_slug = $this->get_taxonomy_slug($taxonomy, $settings);
            
            // Archive page rule (all terms)
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $tax_slug . '/?$',
                'index.php?taxonomy_archive=' . $taxonomy,
                'top'
            );
            
            // Single term rule
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $tax_slug . '/([^/]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]',
                'top'
            );
            
            // Pagination for archive
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $tax_slug . '/page/?([0-9]{1,})/?$',
                'index.php?taxonomy_archive=' . $taxonomy . '&paged=$matches[1]',
                'top'
            );
            
            // Pagination for single term
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $tax_slug . '/([^/]+)/page/?([0-9]{1,})/?$',
                'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]',
                'top'
            );
        }
        
        // Add custom query vars
        add_filter('query_vars', [$this, 'add_query_vars']);
    }
    
    /**
     * Get taxonomy slug from settings or config
     * 
     * @param string $taxonomy Taxonomy name
     * @param array $settings Plugin settings
     * @return string
     */
    private function get_taxonomy_slug($taxonomy, $settings) {
        $slug_mapping = [
            'gender' => 'gender_slug',
            'aroma_type' => 'aroma_type_slug',
            'marki' => 'brands_slug',
            'season' => 'season_slug',
            'intensity' => 'intensity_slug',
            'notes' => 'notes_slug',
            'perfumer' => 'perfumers_slug'
        ];
        
        if (isset($slug_mapping[$taxonomy]) && isset($settings[$slug_mapping[$taxonomy]])) {
            return $settings[$slug_mapping[$taxonomy]];
        }
        
        return $taxonomy;
    }
    
    /**
     * Add custom query vars
     * 
     * @param array $vars Query vars
     * @return array
     */
    public function add_query_vars($vars) {
        $vars[] = 'taxonomy_archive';
        return $vars;
    }
    
    /**
     * Parse custom requests
     * 
     * @param \WP $wp WordPress environment object
     */
    public function parse_request($wp) {
        // Check for taxonomy archive
        if (isset($wp->query_vars['taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['taxonomy_archive'];
            
            // Validate taxonomy exists in config
            if (!isset($this->config[$taxonomy])) {
                return;
            }
            
            // Set query vars for archive
            $wp->query_vars['post_type'] = 'parfume';
            $wp->query_vars['taxonomy'] = $taxonomy;
            $wp->is_tax = true;
            $wp->is_archive = true;
        }
    }
    
    /**
     * Load taxonomy template
     * 
     * @param string $template Current template path
     * @return string Modified template path
     */
    public function load_template($template) {
        // Проверка дали има валидна конфигурация
        if (empty($this->taxonomies) || !is_array($this->taxonomies)) {
            return $template;
        }
        
        // Check if this is a taxonomy archive page
        if (isset($_GET['taxonomy_archive']) && in_array($_GET['taxonomy_archive'], $this->taxonomies)) {
            $taxonomy = sanitize_text_field($_GET['taxonomy_archive']);
            
            // Try taxonomy-specific template
            $template_file = $this->locate_template('taxonomy-' . $taxonomy . '.php');
            
            if ($template_file) {
                return $template_file;
            }
            
            // Fallback to generic taxonomy archive
            $template_file = $this->locate_template('archive-taxonomy.php');
            if ($template_file) {
                return $template_file;
            }
        }
        
        // Check if this is a single taxonomy term page
        if (is_tax($this->taxonomies)) {
            $queried_object = get_queried_object();
            
            if ($queried_object && isset($queried_object->taxonomy)) {
                $taxonomy = $queried_object->taxonomy;
                $term_slug = $queried_object->slug;
                
                // Try specific term template
                $template_file = $this->locate_template('taxonomy-' . $taxonomy . '-' . $term_slug . '.php');
                if ($template_file) {
                    return $template_file;
                }
                
                // Try taxonomy template
                $template_file = $this->locate_template('taxonomy-' . $taxonomy . '.php');
                if ($template_file) {
                    return $template_file;
                }
                
                // Fallback to generic taxonomy template
                $template_file = $this->locate_template('taxonomy.php');
                if ($template_file) {
                    return $template_file;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Locate template file
     * 
     * @param string $template_name Template file name
     * @return string|false Template path or false
     */
    private function locate_template($template_name) {
        // First, check in theme
        $theme_template = locate_template([
            'parfume-reviews/' . $template_name,
            $template_name
        ]);
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // Then check in plugin templates folder
        $plugin_template = PARFUME_REVIEWS_PATH . 'templates/' . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Get taxonomy archive URL
     * 
     * @param string $taxonomy Taxonomy name
     * @return string|false
     */
    public function get_archive_url($taxonomy) {
        if (!in_array($taxonomy, $this->taxonomies)) {
            return false;
        }
        
        $settings = get_option('parfume_reviews_settings', []);
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $tax_slug = $this->get_taxonomy_slug($taxonomy, $settings);
        
        return home_url('/' . $parfume_slug . '/' . $tax_slug . '/');
    }
}