<?php
namespace Parfume_Reviews;

/**
 * Post Type Handler
 * 📁 Файл: includes/class-post-type.php
 * ПОПРАВЕНО: Template loading и rewrite rules
 */
class Post_Type {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'), 10);
        add_action('init', array($this, 'add_rewrite_rules'), 15);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'), 99);
        
        // Debug hooks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp', array($this, 'debug_query_info'));
        }
    }
    
    /**
     * Регистрира custom post types
     */
    public function register_post_types() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Register Parfume post type
        $parfume_args = array(
            'labels' => array(
                'name' => __('Parfumes', 'parfume-reviews'),
                'singular_name' => __('Parfume', 'parfume-reviews'),
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
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug,
                'with_front' => false,
                'feeds' => true,
                'pages' => true,
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-products',
            'supports' => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'custom-fields',
                'comments',
                'revisions',
                'author',
                'page-attributes'
            ),
            'show_in_rest' => true,
            'rest_base' => 'parfumes',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
        
        register_post_type('parfume', $parfume_args);
        
        // Регистрираме и parfume_blog ако е нужен
        $this->register_parfume_blog_post_type($settings);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Post Type initialized');
        }
    }
    
    /**
     * Регистрира parfume blog post type
     */
    private function register_parfume_blog_post_type($settings) {
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
        
        $blog_args = array(
            'labels' => array(
                'name' => __('Parfume Articles', 'parfume-reviews'),
                'singular_name' => __('Parfume Article', 'parfume-reviews'),
                'add_new' => __('Add New Article', 'parfume-reviews'),
                'add_new_item' => __('Add New Parfume Article', 'parfume-reviews'),
                'edit_item' => __('Edit Article', 'parfume-reviews'),
                'view_item' => __('View Article', 'parfume-reviews'),
                'all_items' => __('All Articles', 'parfume-reviews'),
                'search_items' => __('Search Articles', 'parfume-reviews'),
                'not_found' => __('No articles found.', 'parfume-reviews'),
                'not_found_in_trash' => __('No articles found in Trash.', 'parfume-reviews'),
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'show_in_nav_menus' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $blog_slug,
                'with_front' => false,
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array(
                'title',
                'editor', 
                'excerpt',
                'thumbnail',
                'comments',
                'revisions',
                'author'
            ),
            'show_in_rest' => true,
        );
        
        register_post_type('parfume_blog', $blog_args);
    }
    
    /**
     * ПОПРАВЕНО: Добавя custom rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // ПОПРАВЕНО: Archive pagination
        add_rewrite_rule(
            '^' . $parfume_slug . '/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&paged=$matches[1]',
            'top'
        );
        
        // ПОПРАВЕНО: Filter pagination
        add_rewrite_rule(
            '^' . $parfume_slug . '/filter/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&paged=$matches[1]',
            'top'
        );
        
        // ПОПРАВЕНО: Search within parfumes
        add_rewrite_rule(
            '^' . $parfume_slug . '/search/([^/]+)/?$',
            'index.php?post_type=parfume&s=$matches[1]',
            'top'
        );
        
        // ПОПРАВЕНО: Search pagination
        add_rewrite_rule(
            '^' . $parfume_slug . '/search/([^/]+)/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&s=$matches[1]&paged=$matches[2]',
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
            
            // Single perfumer specific CSS
            if (is_tax('perfumer')) {
                wp_enqueue_style(
                    'parfume-reviews-single-perfumer',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-perfumer.css',
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
            
            // Localization for JavaScript
            wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-reviews-nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'strings' => array(
                    'loading' => __('Зареждане...', 'parfume-reviews'),
                    'error' => __('Възникна грешка', 'parfume-reviews'),
                    'success' => __('Успех', 'parfume-reviews'),
                    'filterApplied' => __('Филтърът е приложен', 'parfume-reviews'),
                    'noResults' => __('Няма намерени резултати', 'parfume-reviews'),
                ),
            ));
        }
    }
    
    /**
     * ПОПРАВЕНО: Template loading с по-добри проверки
     */
    public function load_templates($template) {
        global $post;
        
        // Single parfume template
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Archive template
        if (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // ПОПРАВЕНО: Taxonomy templates с по-добри проверки
        if (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
            $queried_object = get_queried_object();
            
            if ($queried_object && isset($queried_object->taxonomy)) {
                // Special handling for single perfumer pages
                if ($queried_object->taxonomy === 'perfumer') {
                    $single_perfumer_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-perfumer.php';
                    if (file_exists($single_perfumer_template)) {
                        return $single_perfumer_template;
                    }
                }
                
                // Specific taxonomy template
                $specific_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-' . $queried_object->taxonomy . '.php';
                if (file_exists($specific_template)) {
                    return $specific_template;
                }
                
                // Generic taxonomy template
                $generic_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-parfume.php';
                if (file_exists($generic_template)) {
                    return $generic_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Debug query информация
     */
    public function debug_query_info() {
        global $wp_query;
        
        if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY && current_user_can('manage_options')) {
                echo '<div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.9); color: white; padding: 10px; z-index: 9999; font-size: 12px; max-width: 300px;">';
                echo '<strong>Query Debug:</strong><br>';
                echo '<strong>Is Archive:</strong> ' . (is_post_type_archive('parfume') ? 'YES' : 'NO') . '<br>';
                echo '<strong>Is Tax:</strong> ' . (is_tax() ? 'YES' : 'NO') . '<br>';
                echo '<strong>Found Posts:</strong> ' . $wp_query->found_posts . '<br>';
                echo '<strong>Request:</strong> ' . $wp_query->request . '<br>';
                
                if (is_tax()) {
                    $queried_object = get_queried_object();
                    if ($queried_object) {
                        echo '<strong>Taxonomy:</strong> ' . $queried_object->taxonomy . '<br>';
                        echo '<strong>Term:</strong> ' . $queried_object->name . '<br>';
                    }
                }
                
                echo '</div>';
            }
        }
    }
    
    /**
     * Получава настройки за post type
     */
    public function get_post_type_settings() {
        return get_option('parfume_reviews_settings', array());
    }
    
    /**
     * Flush rewrite rules (за използване при нужда)
     */
    public function flush_rewrite_rules() {
        $this->register_post_types();
        $this->add_rewrite_rules();
        flush_rewrite_rules(false);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Rewrite rules flushed');
        }
    }
}