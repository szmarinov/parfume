<?php
/**
 * Template Loader Class
 * 
 * Handles template loading for parfume custom post types and taxonomies
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Template_Loader {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'), 99);
        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));
        add_filter('taxonomy_template', array($this, 'taxonomy_template'));
        
        // Add rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'), 5);
        
        // Add query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Handle template variables
        add_action('wp_head', array($this, 'add_template_variables'));
        
        // Flush rewrite rules on activation
        add_action('wp_loaded', array($this, 'maybe_flush_rewrite_rules'));
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
        
        // Archive pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/page/([0-9]{1,})/?$',
            'index.php?post_type=parfumes&paged=$matches[1]',
            'top'
        );
        
        // Single parfume
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/?$',
            'index.php?post_type=parfumes&name=$matches[1]',
            'top'
        );
        
        // Taxonomy rules for parfume_type
        add_rewrite_rule(
            '^' . $archive_slug . '/tip/([^/]+)/?$',
            'index.php?parfume_type=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/tip/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?parfume_type=$matches[1]&paged=$matches[2]',
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
            'index.php?parfume_marki=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/marki/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?parfume_marki=$matches[1]&paged=$matches[2]',
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
            'index.php?parfume_season=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/season/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?parfume_season=$matches[1]&paged=$matches[2]',
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
            'index.php?parfume_intensity=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/intensity/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?parfume_intensity=$matches[1]&paged=$matches[2]',
            'top'
        );
        
        // Vid rules
        add_rewrite_rule(
            '^' . $archive_slug . '/vid/([^/]+)/?$',
            'index.php?parfume_vid=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/vid/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?parfume_vid=$matches[1]&paged=$matches[2]',
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
            'index.php?parfume_notes=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^notes/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?parfume_notes=$matches[1]&paged=$matches[2]',
            'top'
        );
        
        // Blog rules
        add_rewrite_rule(
            '^' . $archive_slug . '/blog/?$',
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/blog/page/([0-9]{1,})/?$',
            'index.php?post_type=parfume_blog&paged=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/blog/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
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
        $vars[] = 'parfume_blog_archive';
        return $vars;
    }
    
    /**
     * Main template loader
     */
    public function template_loader($template) {
        // Handle parfume single pages
        if (is_singular('parfumes')) {
            $custom_template = $this->get_template_path('single-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        // Handle parfume archive
        if (is_post_type_archive('parfumes')) {
            $custom_template = $this->get_template_path('archive-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        // Handle parfume blog
        if (is_singular('parfume_blog')) {
            $custom_template = $this->get_template_path('blog-templates/single-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('parfume_blog')) {
            $custom_template = $this->get_template_path('blog-templates/archive-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        // Handle taxonomies
        if (is_tax()) {
            $taxonomy = get_query_var('taxonomy');
            
            if (in_array($taxonomy, array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'))) {
                // Try specific taxonomy template first
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
            $custom_template = $this->get_template_path('blog-templates/single-blog.php');
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
            $custom_template = $this->get_template_path('blog-templates/archive-blog.php');
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
            // Try specific taxonomy template first
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
                'comparison_enabled' => parfume_catalog_is_comparison_enabled(),
                'strings' => array(
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'error' => __('Грешка при зареждане', 'parfume-catalog'),
                    'no_results' => __('Няма резултати', 'parfume-catalog'),
                    'add_to_comparison' => __('Добави за сравнение', 'parfume-catalog'),
                    'remove_from_comparison' => __('Премахни от сравнение', 'parfume-catalog'),
                    'view_comparison' => __('Виж сравнение', 'parfume-catalog'),
                    'close' => __('Затвори', 'parfume-catalog')
                )
            )) . ';';
            echo '</script>';
        }
    }
    
    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('parfume_catalog_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('parfume_catalog_flush_rewrite_rules');
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
        $types = get_the_terms($post_id, 'parfume_type');
        return !empty($types) && !is_wp_error($types) ? $types[0] : null;
    }
    
    /**
     * Get parfume vid (fragrance type)
     */
    public static function get_parfume_vid($post_id) {
        $vids = get_the_terms($post_id, 'parfume_vid');
        return !empty($vids) && !is_wp_error($vids) ? $vids[0] : null;
    }
    
    /**
     * Get parfume season
     */
    public static function get_parfume_season($post_id) {
        $seasons = get_the_terms($post_id, 'parfume_season');
        return !empty($seasons) && !is_wp_error($seasons) ? $seasons : array();
    }
    
    /**
     * Get parfume intensity
     */
    public static function get_parfume_intensity($post_id) {
        $intensities = get_the_terms($post_id, 'parfume_intensity');
        return !empty($intensities) && !is_wp_error($intensities) ? $intensities[0] : null;
    }
    
    /**
     * Get parfume notes
     */
    public static function get_parfume_notes($post_id, $limit = 0) {
        $notes = get_the_terms($post_id, 'parfume_notes');
        if (!$notes || is_wp_error($notes)) {
            return array();
        }
        
        return $limit > 0 ? array_slice($notes, 0, $limit) : $notes;
    }
    
    /**
     * Get parfume notes by group
     */
    public static function get_parfume_notes_by_group($post_id, $group = '') {
        $notes = get_the_terms($post_id, 'parfume_notes');
        if (!$notes || is_wp_error($notes)) {
            return array();
        }
        
        if (empty($group)) {
            return $notes;
        }
        
        $filtered_notes = array();
        foreach ($notes as $note) {
            $note_group = get_term_meta($note->term_id, 'note_group', true);
            if ($note_group === $group) {
                $filtered_notes[] = $note;
            }
        }
        
        return $filtered_notes;
    }
    
    /**
     * Get parfume main notes (from meta)
     */
    public static function get_parfume_main_notes($post_id) {
        $main_notes = get_post_meta($post_id, '_parfume_main_notes', true);
        if (empty($main_notes)) {
            return array();
        }
        
        return array_map('trim', explode(',', $main_notes));
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
    
    /**
     * Get template part
     */
    public static function get_template_part($slug, $name = null) {
        $template = '';
        
        if ($name) {
            $template = $slug . '-' . $name . '.php';
        } else {
            $template = $slug . '.php';
        }
        
        // Check theme first
        $theme_template = locate_template(array(
            'parfume-catalog/' . $template,
            $template
        ));
        
        if ($theme_template) {
            include $theme_template;
            return;
        }
        
        // Check plugin
        $plugin_template = PARFUME_CATALOG_PLUGIN_DIR . 'templates/' . $template;
        if (file_exists($plugin_template)) {
            include $plugin_template;
            return;
        }
        
        // Fallback to generic template
        if ($name) {
            $fallback_template = PARFUME_CATALOG_PLUGIN_DIR . 'templates/' . $slug . '.php';
            if (file_exists($fallback_template)) {
                include $fallback_template;
            }
        }
    }
    
    /**
     * Render pagination
     */
    public static function render_pagination($args = array()) {
        $defaults = array(
            'prev_text' => __('&laquo; Предишна', 'parfume-catalog'),
            'next_text' => __('Следваща &raquo;', 'parfume-catalog'),
            'type' => 'array',
            'current' => max(1, get_query_var('paged')),
            'total' => $GLOBALS['wp_query']->max_num_pages,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        if ($args['total'] <= 1) {
            return;
        }
        
        $links = paginate_links($args);
        
        if ($links) {
            echo '<nav class="parfume-pagination">';
            echo '<div class="pagination-links">';
            if (is_array($links)) {
                foreach ($links as $link) {
                    echo $link;
                }
            } else {
                echo $links;
            }
            echo '</div>';
            echo '</nav>';
        }
    }
    
    /**
     * Get breadcrumbs
     */
    public static function get_breadcrumbs() {
        if (!is_singular('parfumes') && !is_post_type_archive('parfumes') && !is_tax()) {
            return;
        }
        
        $breadcrumbs = array();
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        // Home
        $breadcrumbs[] = array(
            'title' => __('Начало', 'parfume-catalog'),
            'url' => home_url('/')
        );
        
        // Archive
        $breadcrumbs[] = array(
            'title' => __('Парфюми', 'parfume-catalog'),
            'url' => home_url($archive_slug)
        );
        
        // Current page
        if (is_singular('parfumes')) {
            $breadcrumbs[] = array(
                'title' => get_the_title(),
                'url' => '',
                'current' => true
            );
        } elseif (is_tax()) {
            $term = get_queried_object();
            $breadcrumbs[] = array(
                'title' => $term->name,
                'url' => '',
                'current' => true
            );
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Render breadcrumbs
     */
    public static function render_breadcrumbs() {
        $breadcrumbs = self::get_breadcrumbs();
        
        if (empty($breadcrumbs)) {
            return;
        }
        
        echo '<nav class="parfume-breadcrumbs">';
        echo '<ol class="breadcrumb-list">';
        
        foreach ($breadcrumbs as $breadcrumb) {
            echo '<li class="breadcrumb-item' . (isset($breadcrumb['current']) ? ' current' : '') . '">';
            
            if (!empty($breadcrumb['url']) && !isset($breadcrumb['current'])) {
                echo '<a href="' . esc_url($breadcrumb['url']) . '">' . esc_html($breadcrumb['title']) . '</a>';
            } else {
                echo esc_html($breadcrumb['title']);
            }
            
            echo '</li>';
        }
        
        echo '</ol>';
        echo '</nav>';
    }
}