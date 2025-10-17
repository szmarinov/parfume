<?php
/**
 * Parfume Post Type
 * 
 * Handles registration and functionality for the Parfume custom post type
 * 
 * @package Parfume_Reviews
 * @subpackage PostTypes\Parfume
 * @since 2.0.0
 */

namespace ParfumeReviews\PostTypes\Parfume;

use ParfumeReviews\Core\Container;

/**
 * PostType Class
 * 
 * Manages the Parfume custom post type
 */
class PostType {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Post type configuration
     * 
     * @var array
     */
    private $config;
    
    /**
     * Post type slug
     * 
     * @var string
     */
    private $post_type = 'parfume';
    
    /**
     * Meta boxes instance
     * 
     * @var MetaBoxes
     */
    private $meta_boxes;
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->config = $this->get_config();
        $this->post_type = isset($this->config['post_type']) ? $this->config['post_type'] : 'parfume';
        
        // Initialize meta boxes
        $this->meta_boxes = new MetaBoxes($this->config);
        
        // Register admin assets hook
        add_action('admin_enqueue_scripts', [$this, 'enqueue_meta_box_assets']);
    }
    
    /**
     * Get post type configuration
     * 
     * @return array
     */
    private function get_config() {
        try {
            $config = $this->container->get('config.post-types');
            return isset($config['parfume']) ? $config['parfume'] : $this->get_default_config();
        } catch (\Exception $e) {
            return $this->get_default_config();
        }
    }
    
    /**
     * Get default configuration if config file is missing
     * 
     * @return array
     */
    private function get_default_config() {
        return [
            'post_type' => 'parfume',
            'labels' => [],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-products',
            'capability_type' => 'post',
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'parfiumi'],
            'query_var' => true,
            'can_export' => true,
            'delete_with_user' => false,
            'meta_boxes' => []
        ];
    }
    
    /**
     * Register the custom post type
     */
    public function register() {
        // Get settings for dynamic slug
        $settings = get_option('parfume_reviews_settings', []);
        $slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Override rewrite slug if set in settings
        if (isset($this->config['rewrite']['slug'])) {
            $this->config['rewrite']['slug'] = $slug;
        }
        
        // Register the post type
        register_post_type($this->post_type, $this->get_args());
        
        // Log registration if debug enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Post type registered with slug: ' . $slug);
        }
    }
    
    /**
     * Get post type registration arguments
     * 
     * @return array
     */
    private function get_args() {
        $args = [
            'labels' => $this->config['labels'],
            'public' => $this->config['public'],
            'publicly_queryable' => $this->config['publicly_queryable'],
            'show_ui' => $this->config['show_ui'],
            'show_in_menu' => $this->config['show_in_menu'],
            'show_in_nav_menus' => $this->config['show_in_nav_menus'],
            'show_in_admin_bar' => $this->config['show_in_admin_bar'],
            'show_in_rest' => $this->config['show_in_rest'],
            'menu_position' => $this->config['menu_position'],
            'menu_icon' => $this->config['menu_icon'],
            'capability_type' => $this->config['capability_type'],
            'has_archive' => $this->config['has_archive'],
            'hierarchical' => false,
            'supports' => $this->config['supports'],
            'rewrite' => $this->config['rewrite'],
            'query_var' => $this->config['query_var'],
            'can_export' => $this->config['can_export'],
            'delete_with_user' => $this->config['delete_with_user'],
        ];
        
        // Add REST API configuration if available
        if (isset($this->config['rest_base'])) {
            $args['rest_base'] = $this->config['rest_base'];
        }
        
        if (isset($this->config['rest_controller_class'])) {
            $args['rest_controller_class'] = $this->config['rest_controller_class'];
        }
        
        return $args;
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $this->meta_boxes->register();
    }
    
    /**
     * Save meta boxes data
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function save_meta_boxes($post_id, $post) {
        $this->meta_boxes->save($post_id, $post);
    }
    
    /**
     * Enqueue meta box assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_meta_box_assets($hook) {
        // Only on post edit screens
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        // Only for parfume post type
        global $post;
        if (!$post || $post->post_type !== 'parfume') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'parfume-reviews-admin-metaboxes',
            PARFUME_REVIEWS_URL . 'assets/css/admin-metaboxes.css',
            [],
            PARFUME_REVIEWS_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_media(); // For gallery field
        wp_enqueue_script(
            'parfume-reviews-admin-metaboxes',
            PARFUME_REVIEWS_URL . 'assets/js/admin-metaboxes.js',
            ['jquery', 'jquery-ui-sortable'],
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('parfume-reviews-admin-metaboxes', 'parfumeMetaboxes', [
            'nonce' => wp_create_nonce('parfume_scraper_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'postId' => $post->ID
        ]);
    }
    
    /**
     * Get post type slug
     * 
     * @return string
     */
    public function get_post_type() {
        return $this->post_type;
    }
    
    /**
     * Check if current post is a parfume
     * 
     * @param int $post_id Optional post ID
     * @return bool
     */
    public function is_parfume($post_id = null) {
        if (null === $post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_type($post_id) === $this->post_type;
    }
    
    /**
     * Get parfume meta value
     * 
     * @param int $post_id Post ID
     * @param string $key Meta key
     * @param bool $single Return single value
     * @return mixed
     */
    public function get_meta($post_id, $key, $single = true) {
        return get_post_meta($post_id, '_parfume_' . $key, $single);
    }
    
    /**
     * Update parfume meta value
     * 
     * @param int $post_id Post ID
     * @param string $key Meta key
     * @param mixed $value Meta value
     * @return bool
     */
    public function update_meta($post_id, $key, $value) {
        return update_post_meta($post_id, '_parfume_' . $key, $value);
    }
    
    /**
     * Delete parfume meta value
     * 
     * @param int $post_id Post ID
     * @param string $key Meta key
     * @return bool
     */
    public function delete_meta($post_id, $key) {
        return delete_post_meta($post_id, '_parfume_' . $key);
    }
}