<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * АКТУАЛИЗИРАН ЗА РАБОТА С НОВИЯ QUERY HANDLER
 */
class Post_Type {
    
    /**
     * Instance на Query_Handler
     */
    private $query_handler;
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        
        // Инициализираме Query Handler
        if (class_exists('Parfume_Reviews\\Post_Type\\Query_Handler')) {
            $this->query_handler = new \Parfume_Reviews\Post_Type\Query_Handler();
        }
        
        // Debug хук
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_query_info'));
        }
    }
    
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name'                  => _x('Parfumes', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Parfume', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Parfumes', 'parfume-reviews'),
            'name_admin_bar'       => __('Parfume', 'parfume-reviews'),
            'archives'             => __('Parfume Archives', 'parfume-reviews'),
            'attributes'           => __('Parfume Attributes', 'parfume-reviews'),
            'parent_item_colon'    => __('Parent Parfume:', 'parfume-reviews'),
            'all_items'            => __('All Parfumes', 'parfume-reviews'),
            'add_new_item'         => __('Add New Parfume', 'parfume-reviews'),
            'add_new'              => __('Add New', 'parfume-reviews'),
            'new_item'             => __('New Parfume', 'parfume-reviews'),
            'edit_item'            => __('Edit Parfume', 'parfume-reviews'),
            'update_item'          => __('Update Parfume', 'parfume-reviews'),
            'view_item'            => __('View Parfume', 'parfume-reviews'),
            'view_items'           => __('View Parfumes', 'parfume-reviews'),
            'search_items'         => __('Search Parfumes', 'parfume-reviews'),
            'not_found'            => __('Not found', 'parfume-reviews'),
            'not_found_in_trash'   => __('Not found in Trash', 'parfume-reviews'),
            'featured_image'       => __('Featured Image', 'parfume-reviews'),
            'set_featured_image'   => __('Set featured image', 'parfume-reviews'),
            'remove_featured_image'=> __('Remove featured image', 'parfume-reviews'),
            'use_featured_image'   => __('Use as featured image', 'parfume-reviews'),
            'insert_into_item'     => __('Insert into parfume', 'parfume-reviews'),
            'uploaded_to_this_item'=> __('Uploaded to this parfume', 'parfume-reviews'),
            'items_list'           => __('Parfumes list', 'parfume-reviews'),
            'items_list_navigation'=> __('Parfumes list navigation', 'parfume-reviews'),
            'filter_items_list'    => __('Filter parfumes list', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Parfume', 'parfume-reviews'),
            'description'          => __('Parfume Reviews', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
            'taxonomies'           => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
            'hierarchical'         => false,
            'public'               => true,
            'show_ui'              => true,
            'show_in_menu'         => true,
            'menu_position'        => 5,
            'menu_icon'            => 'dashicons-products',
            'show_in_admin_bar'    => true,
            'show_in_nav_menus'    => true,
            'can_export'           => true,
            'has_archive'          => $slug,
            'exclude_from_search'  => false,
            'publicly_queryable'   => true,
            'capability_type'      => 'post',
            'show_in_rest'         => true,
            'rewrite'              => array(
                'slug' => $slug,
                'with_front' => false,
            ),
        );
        
        register_post_type('parfume', $args);
    }
    
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Archive pagination
        add_rewrite_rule(
            $parfume_slug . '/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&paged=$matches[1]',
            'top'
        );
        
        // Filter pagination
        add_rewrite_rule(
            $parfume_slug . '/filter/page/?([0-9]{1,})/?$',
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
        
        // Taxonomy templates
        if (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
            $queried_object = get_queried_object();
            
            if ($queried_object && isset($queried_object->taxonomy)) {
                // Special handling for perfumer taxonomy
                if ($queried_object->taxonomy === 'perfumer') {
                    // Check if we're on the main perfumer archive vs specific perfumer page
                    // For specific perfumer term (has slug and name), use single-perfumer template
                    if (!empty($queried_object->slug) && !empty($queried_object->name)) {
                        $single_perfumer_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-perfumer.php';
                        if (file_exists($single_perfumer_template)) {
                            return $single_perfumer_template;
                        }
                    }
                    
                    // For perfumer archive (all perfumers listing), use taxonomy-perfumer.php
                    $perfumer_archive_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-perfumer.php';
                    if (file_exists($perfumer_archive_template)) {
                        return $perfumer_archive_template;
                    }
                }
                
                // Specific taxonomy template for other taxonomies
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
    
    public function debug_query_info() {
        global $wp_query;
        
        if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            echo '<div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.9); color: white; padding: 10px; z-index: 9999; font-size: 12px; max-width: 300px;">';
            echo '<strong>Query Debug:</strong><br>';
            echo '<strong>Is Archive:</strong> ' . (is_post_type_archive('parfume') ? 'YES' : 'NO') . '<br>';
            echo '<strong>Is Tax:</strong> ' . (is_tax() ? 'YES' : 'NO') . '<br>';
            echo '<strong>Found Posts:</strong> ' . $wp_query->found_posts . '<br>';
            
            // Показваме активните филтри ако има такива
            if ($this->query_handler) {
                $active_filters = $this->query_handler->get_active_filters();
                if (!empty($active_filters)) {
                    echo '<strong>Active Filters:</strong><pre>' . print_r($active_filters, true) . '</pre>';
                }
            }
            
            echo '</div>';
        }
    }
    
    /**
     * АКТУАЛИЗИРАНИ МЕТОДИ ЗА ФИЛТРИ - ИЗПОЛЗВАТ НОВИЯ QUERY HANDLER
     */
    
    /**
     * Получава активните филтри от Query Handler
     */
    public function get_active_filters() {
        if ($this->query_handler) {
            return $this->query_handler->get_active_filters();
        }
        
        // Fallback ако Query Handler не е наличен
        return self::get_active_filters_static();
    }
    
    /**
     * Построява URL за филтри използвайки Query Handler
     */
    public function build_filter_url($filters = array(), $base_url = '') {
        if ($this->query_handler) {
            return $this->query_handler->build_filter_url($filters, $base_url);
        }
        
        // Fallback ако Query Handler не е наличен
        return self::build_filter_url_static($filters, $base_url);
    }
    
    /**
     * Проверява дали има активни филтри
     */
    public function has_active_filters() {
        $filters = $this->get_active_filters();
        return !empty($filters);
    }
    
    /**
     * СТАТИЧНИ МЕТОДИ ЗА BACKWARD COMPATIBILITY
     */
    
    /**
     * Статичен метод за получаване на активни филтри
     */
    public static function get_active_filters_static() {
        $active_filters = array();
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
            }
        }
        
        // Добавяме ценови филтри
        if (!empty($_GET['min_price'])) {
            $active_filters['min_price'] = floatval($_GET['min_price']);
        }
        
        if (!empty($_GET['max_price'])) {
            $active_filters['max_price'] = floatval($_GET['max_price']);
        }
        
        // Добавяме рейтинг филтър
        if (!empty($_GET['min_rating'])) {
            $active_filters['min_rating'] = floatval($_GET['min_rating']);
        }
        
        return $active_filters;
    }
    
    /**
     * Статичен метод за построяване на URL за филтри
     */
    public static function build_filter_url_static($filters = array(), $base_url = '') {
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
}