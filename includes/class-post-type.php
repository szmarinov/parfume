<?php
namespace Parfume_Reviews;

/**
 * Main Post Type Class - координира всички post type функционалности
 */
class Post_Type {
    private $meta_boxes;
    private $template_loader;
    private $query_handler;
    private $shortcodes;
    
    public function __construct() {
        // Зареждаме специализираните класове
        $this->load_dependencies();
        
        // Основни post type функции
        add_action('init', array($this, 'register_post_type'), 0);
        add_action('init', array($this, 'register_blog_post_type'), 0); 
        add_action('init', array($this, 'add_custom_rewrite_rules'), 10);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Debug hooks
        add_action('wp', array($this, 'debug_current_request'));
    }
    
    /**
     * Зарежда специализираните класове
     */
    private function load_dependencies() {
        // Meta boxes handler
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-meta-boxes.php';
        $this->meta_boxes = new Post_Type\Meta_Boxes();
        
        // Template loader
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-template-loader.php';
        $this->template_loader = new Post_Type\Template_Loader();
        
        // Query handler (филтри и сортиране)
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-query-handler.php';
        $this->query_handler = new Post_Type\Query_Handler();
        
        // Shortcodes
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-shortcodes.php';
        $this->shortcodes = new Post_Type\Shortcodes();
    }
    
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Parfumes', 'parfume-reviews'),
            'singular_name' => __('Parfume', 'parfume-reviews'),
            'menu_name' => __('Parfumes', 'parfume-reviews'),
            'name_admin_bar' => __('Parfume', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Parfume', 'parfume-reviews'),
            'new_item' => __('New Parfume', 'parfume-reviews'),
            'edit_item' => __('Edit Parfume', 'parfume-reviews'),
            'view_item' => __('View Parfume', 'parfume-reviews'),
            'all_items' => __('All Parfumes', 'parfume-reviews'),
            'search_items' => __('Search Parfumes', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Parfumes:', 'parfume-reviews'),
            'not_found' => __('No parfumes found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No parfumes found in Trash.', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $slug,
                'with_front' => false,
                'feeds' => true,
                'pages' => true
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-store',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
            'show_in_rest' => true,
            'rest_base' => 'parfumes',
        );
        
        register_post_type('parfume', $args);
    }
    
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Blog Posts', 'parfume-reviews'),
            'singular_name' => __('Blog Post', 'parfume-reviews'),
            'menu_name' => __('Blog', 'parfume-reviews'),
            'add_new' => __('Add New Post', 'parfume-reviews'),
            'add_new_item' => __('Add New Blog Post', 'parfume-reviews'),
            'edit_item' => __('Edit Blog Post', 'parfume-reviews'),
            'new_item' => __('New Blog Post', 'parfume-reviews'),
            'view_item' => __('View Blog Post', 'parfume-reviews'),
            'view_items' => __('View Blog Posts', 'parfume-reviews'),
            'search_items' => __('Search Blog Posts', 'parfume-reviews'),
            'not_found' => __('No blog posts found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No blog posts found in Trash.', 'parfume-reviews'),
            'all_items' => __('All Blog Posts', 'parfume-reviews'),
            'archives' => __('Blog Archives', 'parfume-reviews'),
            'attributes' => __('Blog Attributes', 'parfume-reviews'),
            'insert_into_item' => __('Insert into blog post', 'parfume-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this blog post', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/blog',
                'with_front' => false,
                'feeds' => true,
                'pages' => true,
                'hierarchical' => false
            ),
            'capability_type' => 'post',
            'has_archive' => $parfume_slug . '/blog',
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-admin-post',
            'supports' => array(
                'title', 
                'editor', 
                'thumbnail', 
                'excerpt', 
                'comments', 
                'author', 
                'custom-fields',
                'revisions',
                'trackbacks',
                'page-attributes'
            ),
            'show_in_rest' => true,
            'rest_base' => 'parfume-blog',
            'taxonomies' => array('category', 'post_tag'),
            'delete_with_user' => false,
        );
        
        register_post_type('parfume_blog', $args);
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Parfume Blog post type registered with archive: " . $parfume_slug . '/blog');
        }
    }

    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Blog archive rules
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/?$',
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume_blog&paged=$matches[1]',
            'top'
        );
        
        // Single blog post rules
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
        
        // Archive filter rules
        add_rewrite_rule(
            '^' . $parfume_slug . '/filter/?$',
            'index.php?post_type=parfume',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/filter/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&paged=$matches[1]',
            'top'
        );
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            // Main frontend CSS
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            // Single parfume specific CSS
            if (is_singular('parfume')) {
                wp_enqueue_style(
                    'parfume-reviews-single',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                    array('parfume-reviews-frontend'),
                    PARFUME_REVIEWS_VERSION
                );
            }
            
            // Filters CSS
            wp_enqueue_style(
                'parfume-reviews-filters',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/filters.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            // Comparison CSS
            wp_enqueue_style(
                'parfume-comparison',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comparison.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            // JavaScript files
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'parfume-reviews-filters',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/filters.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'parfume-comparison',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('parfume-reviews-frontend', 'parfume_reviews_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_reviews_nonce'),
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post;
        
        if (($hook == 'post-new.php' || $hook == 'post.php') && isset($post->post_type) && $post->post_type == 'parfume') {
            wp_enqueue_script(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_enqueue_style(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
        }
    }
    
    public function debug_current_request() {
        if (!current_user_can('manage_options') || !isset($_GET['parfume_debug'])) {
            return;
        }
        
        global $wp_query;
        
        echo '<div style="background: white; padding: 20px; border: 2px solid red; margin: 20px; z-index: 9999; position: relative;">';
        echo '<h3>Parfume Reviews Debug Info</h3>';
        echo '<strong>Current URL:</strong> ' . esc_url($_SERVER['REQUEST_URI']) . '<br>';
        echo '<strong>Query Vars:</strong><pre>' . print_r($wp_query->query_vars, true) . '</pre>';
        echo '<strong>GET Parameters:</strong><pre>' . print_r($_GET, true) . '</pre>';
        echo '<strong>Is 404:</strong> ' . (is_404() ? 'YES' : 'NO') . '<br>';
        echo '<strong>Post Type Archive:</strong> ' . (is_post_type_archive('parfume') ? 'YES' : 'NO') . '<br>';
        echo '<strong>Is Tax:</strong> ' . (is_tax() ? 'YES' : 'NO') . '<br>';
        echo '<strong>Found Posts:</strong> ' . $wp_query->found_posts . '<br>';
        echo '</div>';
    }
    
    // Helper методи за филтри - статични за backward compatibility
    public static function build_filter_url($filters = array(), $base_url = '') {
        if (empty($base_url)) {
            if (is_post_type_archive('parfume')) {
                $base_url = get_post_type_archive_link('parfume');
            } elseif (is_tax()) {
                $base_url = get_term_link(get_queried_object());
            } else {
                $base_url = home_url('/parfiumi/');
            }
        }
        
        if (!empty($filters)) {
            $base_url = add_query_arg($filters, $base_url);
        }
        
        return $base_url;
    }
    
    public static function get_active_filters() {
        $active_filters = array();
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
            }
        }
        
        return $active_filters;
    }
}