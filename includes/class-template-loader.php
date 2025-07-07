<?php
/**
 * Template Loader class for Parfume Catalog plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Template_Loader {
    
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'));
        add_action('wp', array($this, 'track_recently_viewed'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_template_scripts'));
    }
    
    /**
     * Load custom templates
     */
    public function template_loader($template) {
        if (is_post_type_archive('parfumes') || is_singular('parfumes')) {
            return $this->get_template_hierarchy('parfumes');
        }
        
        if (is_post_type_archive('parfume_blog') || is_singular('parfume_blog')) {
            return $this->get_template_hierarchy('blog');
        }
        
        if (is_tax(array('tip', 'vid-aromat', 'marki', 'sezon', 'intenzivnost', 'notki'))) {
            return $this->get_taxonomy_template();
        }
        
        return $template;
    }
    
    /**
     * Get template hierarchy
     */
    private function get_template_hierarchy($type) {
        $templates = array();
        
        if ($type === 'parfumes') {
            if (is_singular()) {
                $templates[] = 'single-parfumes.php';
            } else {
                $templates[] = 'archive-parfumes.php';
            }
        } elseif ($type === 'blog') {
            if (is_singular()) {
                $templates[] = 'single-parfume_blog.php';
            } else {
                $templates[] = 'archive-parfume_blog.php';
            }
        }
        
        return $this->locate_template($templates);
    }
    
    /**
     * Get taxonomy template
     */
    private function get_taxonomy_template() {
        $taxonomy = get_queried_object()->taxonomy;
        $templates = array(
            "taxonomy-{$taxonomy}.php",
            'taxonomy-parfume.php',
            'archive-parfumes.php'
        );
        
        return $this->locate_template($templates);
    }
    
    /**
     * Locate template file
     */
    private function locate_template($templates) {
        $located = '';
        
        foreach ($templates as $template) {
            // Check theme first
            if (file_exists(get_stylesheet_directory() . '/parfume-catalog/' . $template)) {
                $located = get_stylesheet_directory() . '/parfume-catalog/' . $template;
                break;
            } elseif (file_exists(get_template_directory() . '/parfume-catalog/' . $template)) {
                $located = get_template_directory() . '/parfume-catalog/' . $template;
                break;
            }
            
            // Check plugin templates
            if (file_exists(PARFUME_CATALOG_PLUGIN_DIR . 'templates/' . $template)) {
                $located = PARFUME_CATALOG_PLUGIN_DIR . 'templates/' . $template;
                break;
            }
        }
        
        return $located;
    }
    
    /**
     * Track recently viewed perfumes
     */
    public function track_recently_viewed() {
        if (!is_singular('parfumes')) {
            return;
        }
        
        $post_id = get_the_ID();
        $viewed = isset($_COOKIE['parfume_recently_viewed']) ? $_COOKIE['parfume_recently_viewed'] : '';
        $viewed_ids = $viewed ? explode(',', $viewed) : array();
        
        // Remove current post if already in list
        $viewed_ids = array_diff($viewed_ids, array($post_id));
        
        // Add current post to beginning
        array_unshift($viewed_ids, $post_id);
        
        // Limit to 10 items
        $viewed_ids = array_slice($viewed_ids, 0, 10);
        
        // Set cookie for 30 days
        setcookie('parfume_recently_viewed', implode(',', $viewed_ids), time() + (30 * DAY_IN_SECONDS), '/');
    }
    
    /**
     * Enqueue scripts for templates
     */
    public function enqueue_template_scripts() {
        if (is_singular('parfumes') || is_post_type_archive('parfumes') || is_tax(array('tip', 'vid-aromat', 'marki', 'sezon', 'intenzivnost', 'notki'))) {
            wp_enqueue_script('parfume-catalog-single', PARFUME_CATALOG_PLUGIN_URL . 'assets/js/single-parfume.js', array('jquery'), PARFUME_CATALOG_VERSION, true);
            
            // Localize script for single parfume page
            wp_localize_script('parfume-catalog-single', 'parfume_single_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_single_nonce'),
                'post_id' => is_singular() ? get_the_ID() : 0,
                'strings' => array(
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'error' => __('Възникна грешка', 'parfume-catalog'),
                    'comment_submitted' => __('Коментарът е изпратен за одобрение', 'parfume-catalog'),
                    'promo_copied' => __('Промо кодът е копиран', 'parfume-catalog'),
                )
            ));
        }
    }
}