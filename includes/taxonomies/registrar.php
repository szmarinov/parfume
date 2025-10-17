<?php
/**
 * Taxonomy Registrar
 * 
 * Handles registration of all taxonomies
 * 
 * @package ParfumeReviews
 * @subpackage Taxonomies
 * @since 2.0.0
 */

namespace ParfumeReviews\Taxonomies;

/**
 * Registrar Class
 * 
 * Registers all taxonomies with WordPress
 */
class Registrar {
    
    /**
     * Taxonomy configuration
     * 
     * @var array
     */
    private $config;
    
    /**
     * Constructor
     * 
     * @param array $config Taxonomy configuration
     */
    public function __construct($config) {
        // ВАЖНО: Валидация на конфигурацията
        if (!is_array($config)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ParfumeReviews\Taxonomies\Registrar: Config is not an array! Type: ' . gettype($config));
            }
            $config = [];
        }
        
        $this->config = $config;
    }
    
    /**
     * Register all taxonomies
     */
    public function register_all() {
        // Проверка дали има конфигурация
        if (empty($this->config) || !is_array($this->config)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ParfumeReviews\Taxonomies\Registrar: No valid config for registration');
            }
            return;
        }
        
        // Get settings for dynamic slugs
        $settings = get_option('parfume_reviews_settings', []);
        
        foreach ($this->config as $taxonomy => $tax_config) {
            // Валидация на taxonomy конфигурацията
            if (!is_array($tax_config)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("ParfumeReviews: Taxonomy '$taxonomy' config is not array! Skipping.");
                }
                continue;
            }
            
            $this->register_taxonomy($taxonomy, $tax_config, $settings);
        }
        
        // Add default terms after registration
        add_action('init', [$this, 'add_default_terms'], 20);
    }
    
    /**
     * Register individual taxonomy
     * 
     * @param string $taxonomy Taxonomy name
     * @param array $config Taxonomy configuration
     * @param array $settings Plugin settings
     */
    private function register_taxonomy($taxonomy, $config, $settings) {
        // Get post type
        $post_type = isset($config['post_type']) ? $config['post_type'] : 'parfume';
        
        // Update rewrite slug from settings if available
        $config = $this->update_rewrite_slug($taxonomy, $config, $settings);
        
        // Prepare arguments
        $args = [
            'labels' => $config['labels'],
            'hierarchical' => $config['hierarchical'],
            'public' => $config['public'],
            'publicly_queryable' => $config['publicly_queryable'],
            'show_ui' => $config['show_ui'],
            'show_admin_column' => $config['show_admin_column'],
            'show_in_nav_menus' => $config['show_in_nav_menus'],
            'show_tagcloud' => $config['show_tagcloud'],
            'show_in_rest' => $config['show_in_rest'],
            'rewrite' => $config['rewrite'],
            'query_var' => true,
            'meta_box_cb' => $config['meta_box_cb']
        ];
        
        // Register the taxonomy
        register_taxonomy($taxonomy, $post_type, $args);
        
        // Log if debug enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $slug = isset($config['rewrite']['slug']) ? $config['rewrite']['slug'] : $taxonomy;
            error_log(sprintf(
                'Parfume Reviews: Taxonomy "%s" registered with slug: %s',
                $taxonomy,
                $slug
            ));
        }
    }
    
    /**
     * Update rewrite slug from settings
     * 
     * @param string $taxonomy Taxonomy name
     * @param array $config Taxonomy configuration
     * @param array $settings Plugin settings
     * @return array Updated configuration
     */
    private function update_rewrite_slug($taxonomy, $config, $settings) {
        // Get parfume base slug
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Mapping of taxonomy to settings key
        $slug_mapping = [
            'gender' => 'gender_slug',
            'aroma_type' => 'aroma_type_slug',
            'marki' => 'brands_slug',
            'season' => 'season_slug',
            'intensity' => 'intensity_slug',
            'notes' => 'notes_slug',
            'perfumer' => 'perfumers_slug'
        ];
        
        // Get custom slug if set
        if (isset($slug_mapping[$taxonomy]) && isset($settings[$slug_mapping[$taxonomy]])) {
            $tax_slug = $settings[$slug_mapping[$taxonomy]];
        } else {
            // Default to taxonomy name
            $tax_slug = $taxonomy;
        }
        
        // Update rewrite slug
        if (isset($config['rewrite']['slug'])) {
            $config['rewrite']['slug'] = $parfume_slug . '/' . $tax_slug;
        }
        
        return $config;
    }
    
    /**
     * Add default terms for all taxonomies
     */
    public function add_default_terms() {
        // Проверка дали има конфигурация
        if (empty($this->config) || !is_array($this->config)) {
            return;
        }
        
        foreach ($this->config as $taxonomy => $config) {
            // Валидация на конфигурацията
            if (!is_array($config)) {
                continue;
            }
            
            if (isset($config['default_terms']) && is_array($config['default_terms'])) {
                $this->add_taxonomy_default_terms($taxonomy, $config['default_terms']);
            }
        }
    }
    
    /**
     * Add default terms for a specific taxonomy
     * 
     * @param string $taxonomy Taxonomy name
     * @param array $terms Array of term names
     */
    private function add_taxonomy_default_terms($taxonomy, $terms) {
        // Check if taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            return;
        }
        
        foreach ($terms as $term_name) {
            // Check if term already exists
            if (!term_exists($term_name, $taxonomy)) {
                $result = wp_insert_term($term_name, $taxonomy);
                
                // Log if debug enabled
                if (defined('WP_DEBUG') && WP_DEBUG && !is_wp_error($result)) {
                    error_log(sprintf(
                        'Parfume Reviews: Default term "%s" added to taxonomy "%s"',
                        $term_name,
                        $taxonomy
                    ));
                }
            }
        }
    }
    
    /**
     * Get registered taxonomies
     * 
     * @return array
     */
    public function get_registered_taxonomies() {
        if (!is_array($this->config)) {
            return [];
        }
        return array_keys($this->config);
    }
    
    /**
     * Check if taxonomy is registered
     * 
     * @param string $taxonomy Taxonomy name
     * @return bool
     */
    public function is_registered($taxonomy) {
        return isset($this->config[$taxonomy]) && taxonomy_exists($taxonomy);
    }
    
    /**
     * Get taxonomy configuration
     * 
     * @param string $taxonomy Taxonomy name
     * @return array|null
     */
    public function get_config($taxonomy) {
        return isset($this->config[$taxonomy]) ? $this->config[$taxonomy] : null;
    }
    
    /**
     * Get all taxonomy configurations
     * 
     * @return array
     */
    public function get_all_configs() {
        return is_array($this->config) ? $this->config : [];
    }
    
    /**
     * Get taxonomy slug
     * 
     * @param string $taxonomy Taxonomy name
     * @return string|null
     */
    public function get_slug($taxonomy) {
        $config = $this->get_config($taxonomy);
        
        if (!$config || !isset($config['rewrite']['slug'])) {
            return null;
        }
        
        // Return only the last part of the slug (after the last /)
        return basename($config['rewrite']['slug']);
    }
    
    /**
     * Get taxonomy full rewrite slug
     * 
     * @param string $taxonomy Taxonomy name
     * @return string|null
     */
    public function get_full_slug($taxonomy) {
        $config = $this->get_config($taxonomy);
        return $config && isset($config['rewrite']['slug']) ? $config['rewrite']['slug'] : null;
    }
    
    /**
     * Get taxonomy labels
     * 
     * @param string $taxonomy Taxonomy name
     * @return array|null
     */
    public function get_labels($taxonomy) {
        $config = $this->get_config($taxonomy);
        return $config && isset($config['labels']) ? $config['labels'] : null;
    }
    
    /**
     * Check if taxonomy is hierarchical
     * 
     * @param string $taxonomy Taxonomy name
     * @return bool
     */
    public function is_hierarchical($taxonomy) {
        $config = $this->get_config($taxonomy);
        return $config && isset($config['hierarchical']) ? $config['hierarchical'] : false;
    }
    
    /**
     * Get taxonomy post type
     * 
     * @param string $taxonomy Taxonomy name
     * @return string|null
     */
    public function get_post_type($taxonomy) {
        $config = $this->get_config($taxonomy);
        return $config && isset($config['post_type']) ? $config['post_type'] : null;
    }
}