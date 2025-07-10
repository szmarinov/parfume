<?php
/**
 * Template Loader Class
 * 
 * Handles template loading for parfume custom post types and taxonomies
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Template_Loader {
    
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'), 99);
        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));
        add_filter('taxonomy_template', array($this, 'taxonomy_template'));
        
        // Add rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Add query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Handle template variables
        add_action('wp_head', array($this, 'add_template_variables'));
    }
    
    /**
     * Add custom rewrite rules
     */
    public function add_rewrite_rules() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        // Main archive
        add_rewrite_rule(
            '^' . $archive_slug . '/?$',
            'index.php?post_type=parfumes',
            'top'
        );
        
        // Single parfume
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/?$',
            'index.php?post_type=parfumes&name=$matches[1]',
            'top'
        );
        
        // Archive pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/page/([0-9]{1,})/?$',
            'index.php?post_type=parfumes&paged=$matches[1]',
            'top'
        );
        
        // Taxonomy rules for parfume_type
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/?$',
            'index.php?taxonomy=parfume_type&term=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_type&term=$matches[1]&paged=$matches[2]',
            'top'
        );
        
        // Marki rules
        add_rewrite_rule(
            '^' . $archive_slug . '/marki/?$',
            'index.php?taxonomy=parfume_marki',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/marki/([^/]+)/?$',
            'index.php?taxonomy=parfume_marki&term=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/marki/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_marki&term=$matches[1]&paged=$matches[2]',
            'top'
        );
        
        // Season rules
        add_rewrite_rule(
            '^' . $archive_slug . '/season/?$',
            'index.php?taxonomy=parfume_season',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/season/([^/]+)/?$',
            'index.php?taxonomy=parfume_season&term=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/season/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_season&term=$matches[1]&paged=$matches[2]',
            'top'
        );
        
        // Intensity rules
        add_rewrite_rule(
            '^' . $archive_slug . '/intensity/?$',
            'index.php?taxonomy=parfume_intensity',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/intensity/([^/]+)/?$',
            'index.php?taxonomy=parfume_intensity&term=$matches[1]',
            'top'
        );
        
        // Vid rules
        add_rewrite_rule(
            '^' . $archive_slug . '/vid/([^/]+)/?$',
            'index.php?taxonomy=parfume_vid&term=$matches[1]',
            'top'
        );
        
        // Notes rules
        add_rewrite_rule(
            '^notes/?$',
            'index.php?taxonomy=parfume_notes',
            'top'
        );
        
        add_rewrite_rule(
            '^notes/([^/]+)/?$',
            'index.php?taxonomy=parfume_notes&term=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^notes/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_notes&term=$matches[1]&paged=$matches[2]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_type';
        $vars[] = 'parfume_vid';
        $vars[] = 'parfume_marki';
        $vars[] = 'parfume_season';
        $vars[] = 'parfume_intensity';
        $vars[] = 'parfume_notes';
        return $vars;
    }
    
    /**
     * Main template loader
     */
    public function template_loader($template) {
        if (is_singular('parfumes')) {
            $custom_template = $this->get_template_path('single-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('parfumes')) {
            $custom_template = $this->get_template_path('archive-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        if (is_singular('parfume_blog')) {
            $custom_template = $this->get_template_path('single-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('parfume_blog')) {
            $custom_template = $this->get_template_path('archive-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        // Handle taxonomies
        if (is_tax()) {
            $taxonomy = get_query_var('taxonomy');
            
            if (in_array($taxonomy, array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'))) {
                $custom_template = $this->get_template_path('taxonomy-' . $taxonomy . '.php');
                if ($custom_template) {
                    return $custom_template;
                }
                
                // Fallback to generic taxonomy template
                $fallback_template = $this->get_template_path('taxonomy-parfume.php');
                if ($fallback_template) {
                    return $fallback_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Single template
     */
    public function single_template($template) {
        if (is_singular('parfumes')) {
            $custom_template = $this->get_template_path('single-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        if (is_singular('parfume_blog')) {
            $custom_template = $this->get_template_path('single-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Archive template
     */
    public function archive_template($template) {
        if (is_post_type_archive('parfumes')) {
            $custom_template = $this->get_template_path('archive-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('parfume_blog')) {
            $custom_template = $this->get_template_path('archive-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Taxonomy template
     */
    public function taxonomy_template($template) {
        $taxonomy = get_query_var('taxonomy');
        
        if (in_array($taxonomy, array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'))) {
            $custom_template = $this->get_template_path('taxonomy-' . $taxonomy . '.php');
            if ($custom_template) {
                return $custom_template;
            }
            
            // Fallback to generic taxonomy template
            $fallback_template = $this->get_template_path('taxonomy-parfume.php');
            if ($fallback_template) {
                return $fallback_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Get template path
     */
    private function get_template_path($template_name) {
        // First check if theme has the template
        $theme_template = locate_template(array(
            'parfume-catalog/' . $template_name,
            $template_name
        ));
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // Check plugin templates
        $plugin_template = PARFUME_CATALOG_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Add template variables to head
     */
    public function add_template_variables() {
        if (is_singular('parfumes') || is_post_type_archive('parfumes') || 
            is_tax('parfume_type') || is_tax('parfume_vid') || is_tax('parfume_marki') || 
            is_tax('parfume_season') || is_tax('parfume_intensity') || is_tax('parfume_notes')) {
            
            // Settings from plugin
            $options = get_option('parfume_catalog_options', array());
            
            echo '<script type="text/javascript">';
            echo 'var parfume_catalog_config = ' . json_encode(array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_catalog_nonce'),
                'archive_slug' => isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi',
                'strings' => array(
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'error' => __('Грешка при зареждане', 'parfume-catalog'),
                    'no_results' => __('Няма резултати', 'parfume-catalog')
                )
            )) . ';';
            echo '</script>';
        }
    }
    
    /**
     * Get brand name for a parfume
     */
    public static function get_parfume_brand($post_id) {
        $brands = get_the_terms($post_id, 'parfume_marki');
        return !empty($brands) && !is_wp_error($brands) ? $brands[0] : null;
    }
    
    /**
     * Get parfume type
     */
    public static function get_parfume_type($post_id) {
        $types = get_the_terms($post_id, 'parfume_vid');
        return !empty($types) && !is_wp_error($types) ? $types[0] : null;
    }
    
    /**
     * Check if template exists in theme
     */
    public static function template_exists_in_theme($template_name) {
        $theme_template = locate_template(array(
            'parfume-catalog/' . $template_name,
            $template_name
        ));
        
        return !empty($theme_template);
    }
}