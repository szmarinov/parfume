<?php
/**
 * Template Loader
 * 
 * Handles template loading and overrides
 * 
 * @package Parfume_Reviews
 * @subpackage Templates
 * @since 2.0.0
 */

namespace ParfumeReviews\Templates;

use ParfumeReviews\Core\Container;

/**
 * Loader Class
 * 
 * Manages template loading system
 */
class Loader {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Template path in theme
     * 
     * @var string
     */
    private $theme_template_path = 'parfume-reviews';
    
    /**
     * Plugin template path
     * 
     * @var string
     */
    private $plugin_template_path;
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->plugin_template_path = PARFUME_REVIEWS_PATH . 'templates/';
    }
    
    /**
     * Load template
     * 
     * @param string $template Current template path
     * @return string Modified template path
     */
    public function load_template($template) {
        global $post;
        
        // Single parfume template
        if (is_singular('parfume')) {
            $new_template = $this->locate_template('single-parfume.php');
            if ($new_template) {
                return $new_template;
            }
        }
        
        // Parfume archive template
        if (is_post_type_archive('parfume')) {
            $new_template = $this->locate_template('archive-parfume.php');
            if ($new_template) {
                return $new_template;
            }
        }
        
        // Taxonomy templates - check only after WordPress is loaded
        if (did_action('parse_query')) {
            $taxonomy_template = $this->load_taxonomy_template();
            if ($taxonomy_template) {
                return $taxonomy_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Load taxonomy template
     * 
     * @return string|false Template path or false
     */
    private function load_taxonomy_template() {
        $queried_object = get_queried_object();
        
        if (!isset($queried_object->taxonomy)) {
            return false;
        }
        
        $taxonomy = $queried_object->taxonomy;
        
        // Check if it's a parfume taxonomy
        $parfume_taxonomies = ['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'];
        
        if (!in_array($taxonomy, $parfume_taxonomies)) {
            return false;
        }
        
        // Try specific taxonomy template first
        $specific_template = $this->locate_template("taxonomy-{$taxonomy}.php");
        if ($specific_template) {
            return $specific_template;
        }
        
        // Fall back to generic taxonomy template
        $generic_template = $this->locate_template('taxonomy.php');
        if ($generic_template) {
            return $generic_template;
        }
        
        return false;
    }
    
    /**
     * Locate template
     * 
     * Checks theme folder first, then plugin folder
     * 
     * @param string $template_name Template file name
     * @return string|false Template path or false if not found
     */
    public function locate_template($template_name) {
        // Check in theme folder
        $theme_template = locate_template([
            trailingslashit($this->theme_template_path) . $template_name
        ]);
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // Check in plugin folder
        $plugin_template = $this->plugin_template_path . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Get template part
     * 
     * Load a template part
     * 
     * @param string $slug Template slug
     * @param string $name Optional template name
     * @param array $args Optional arguments to pass to template
     */
    public function get_template_part($slug, $name = null, $args = []) {
        $templates = [];
        
        if ($name) {
            $templates[] = "{$slug}-{$name}.php";
        }
        
        $templates[] = "{$slug}.php";
        
        $template = $this->locate_template_from_array($templates);
        
        if ($template) {
            if (!empty($args)) {
                extract($args);
            }
            
            do_action('parfume_reviews_before_template_part', $slug, $name, $template, $args);
            
            include $template;
            
            do_action('parfume_reviews_after_template_part', $slug, $name, $template, $args);
        }
    }
    
    /**
     * Locate template from array
     * 
     * @param array $template_names Array of template names
     * @return string|false Template path or false
     */
    private function locate_template_from_array($template_names) {
        foreach ($template_names as $template_name) {
            $template = $this->locate_template($template_name);
            if ($template) {
                return $template;
            }
        }
        
        return false;
    }
    
    /**
     * Include template with arguments
     * 
     * @param string $template_name Template file name
     * @param array $args Arguments to pass to template
     * @param string $template_path Optional template path
     * @param string $default_path Optional default path
     */
    public function include_template($template_name, $args = [], $template_path = '', $default_path = '') {
        if (!empty($args)) {
            extract($args);
        }
        
        $template = $this->locate_template($template_name);
        
        if (!$template) {
            return;
        }
        
        do_action('parfume_reviews_before_template', $template_name, $template, $args);
        
        include $template;
        
        do_action('parfume_reviews_after_template', $template_name, $template, $args);
    }
    
    /**
     * Enqueue template assets
     */
    public function enqueue_assets() {
        // Only on parfume pages
        if (!$this->is_parfume_page()) {
            return;
        }
        
        // Enqueue CSS based on page type
        if (is_singular('parfume')) {
            wp_enqueue_style(
                'parfume-reviews-single',
                PARFUME_REVIEWS_URL . 'assets/css/single-parfume.css',
                ['parfume-reviews-main'],
                PARFUME_REVIEWS_VERSION
            );
        }
        
        if (is_post_type_archive('parfume') || $this->is_parfume_taxonomy()) {
            wp_enqueue_style(
                'parfume-reviews-archive',
                PARFUME_REVIEWS_URL . 'assets/css/archive.css',
                ['parfume-reviews-main'],
                PARFUME_REVIEWS_VERSION
            );
        }
    }
    
    /**
     * Check if current page is parfume related
     * 
     * @return bool
     */
    private function is_parfume_page() {
        // Check only after WordPress is fully loaded
        if (!did_action('wp')) {
            return false;
        }
        
        return is_singular('parfume') || 
               is_post_type_archive('parfume') || 
               $this->is_parfume_taxonomy();
    }
    
    /**
     * Check if current page is parfume taxonomy
     * 
     * @return bool
     */
    private function is_parfume_taxonomy() {
        // Check only after parse_query
        if (!did_action('parse_query')) {
            return false;
        }
        
        $parfume_taxonomies = ['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'];
        
        foreach ($parfume_taxonomies as $taxonomy) {
            if (is_tax($taxonomy)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get template content
     * 
     * Returns template content as string instead of including it
     * 
     * @param string $template_name Template file name
     * @param array $args Arguments to pass to template
     * @return string Template content
     */
    public function get_template_content($template_name, $args = []) {
        ob_start();
        $this->include_template($template_name, $args);
        return ob_get_clean();
    }
}