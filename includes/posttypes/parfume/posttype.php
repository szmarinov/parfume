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

namespace Parfume_Reviews\PostTypes\Parfume;

use Parfume_Reviews\Core\Container;

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
        $this->meta_boxes = new MetaBoxes($this->config);
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
     * @return bool|int
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
    
    /**
     * Get all parfume meta
     * 
     * @param int $post_id Post ID
     * @return array
     */
    public function get_all_meta($post_id) {
        $meta = [];
        $all_meta = get_post_meta($post_id);
        
        foreach ($all_meta as $key => $value) {
            // Only get parfume meta (prefixed with _parfume_)
            if (strpos($key, '_parfume_') === 0) {
                $clean_key = str_replace('_parfume_', '', $key);
                $meta[$clean_key] = isset($value[0]) ? $value[0] : $value;
            }
        }
        
        return $meta;
    }
    
    /**
     * Get parfumes with filters
     * 
     * @param array $args WP_Query arguments
     * @return \WP_Query
     */
    public function get_parfumes($args = []) {
        $default_args = [
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        return new \WP_Query($args);
    }
    
    /**
     * Get parfume by ID
     * 
     * @param int $post_id Post ID
     * @return \WP_Post|null
     */
    public function get_parfume($post_id) {
        $post = get_post($post_id);
        
        if ($post && $post->post_type === $this->post_type) {
            return $post;
        }
        
        return null;
    }
    
    /**
     * Get featured parfumes
     * 
     * @param int $count Number of parfumes to get
     * @return \WP_Query
     */
    public function get_featured($count = 6) {
        return $this->get_parfumes([
            'posts_per_page' => $count,
            'meta_query' => [
                [
                    'key' => '_parfume_featured',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
    }
    
    /**
     * Get top rated parfumes
     * 
     * @param int $count Number of parfumes to get
     * @return \WP_Query
     */
    public function get_top_rated($count = 6) {
        return $this->get_parfumes([
            'posts_per_page' => $count,
            'meta_key' => '_parfume_rating',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);
    }
    
    /**
     * Get newest parfumes
     * 
     * @param int $count Number of parfumes to get
     * @return \WP_Query
     */
    public function get_newest($count = 6) {
        return $this->get_parfumes([
            'posts_per_page' => $count,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
    }
    
    /**
     * Search parfumes
     * 
     * @param string $search_term Search term
     * @param array $args Additional WP_Query arguments
     * @return \WP_Query
     */
    public function search($search_term, $args = []) {
        $args['s'] = $search_term;
        return $this->get_parfumes($args);
    }
    
    /**
     * Get parfumes by taxonomy term
     * 
     * @param string $taxonomy Taxonomy name
     * @param string|int $term Term slug or ID
     * @param array $args Additional WP_Query arguments
     * @return \WP_Query
     */
    public function get_by_term($taxonomy, $term, $args = []) {
        $tax_query = [
            [
                'taxonomy' => $taxonomy,
                'field' => is_numeric($term) ? 'term_id' : 'slug',
                'terms' => $term
            ]
        ];
        
        if (isset($args['tax_query'])) {
            $args['tax_query'] = array_merge($args['tax_query'], $tax_query);
        } else {
            $args['tax_query'] = $tax_query;
        }
        
        return $this->get_parfumes($args);
    }
    
    /**
     * Count parfumes
     * 
     * @param array $args WP_Query arguments
     * @return int
     */
    public function count($args = []) {
        $args['posts_per_page'] = -1;
        $args['fields'] = 'ids';
        
        $query = $this->get_parfumes($args);
        return $query->found_posts;
    }
}