<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * UPDATED VERSION: Добавени Stores Meta Box и Product Scraper функционалности
 * ПЪЛНА ВЕРСИЯ: Всички оригинални методи + нови функции
 */
class Post_Type {
    
    /**
     * Instance на Query_Handler и Stores Meta Box
     */
    private $query_handler;
    private $stores_meta_box;
    
    public function __construct() {
        // Основни хукове за post type
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Permalink hooks
        add_filter('post_type_link', array($this, 'custom_post_type_link'), 1, 2);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_request'));
        
        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Зареждаме Stores Meta Box компонента
        $this->load_stores_meta_box();
        
        // Оригинални meta boxes
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // Всички AJAX handlers (оригинални + нови)
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_parfume_test_scraper', array($this, 'ajax_test_scraper'));
        add_action('wp_ajax_parfume_bulk_scrape', array($this, 'ajax_bulk_scrape'));
        add_action('wp_ajax_parfume_schedule_scrape', array($this, 'ajax_schedule_scrape'));
        
        // Инициализираме Query Handler само ако класът съществува
        if (class_exists('Parfume_Reviews\\Post_Type\\Query_Handler')) {
            $this->query_handler = new \Parfume_Reviews\Post_Type\Query_Handler();
        }
    
    /**
     * FALLBACK методи за Stores Meta Box ако файлът не съществува
     */
    public function add_fallback_stores_meta_box() {
        add_meta_box(
            'parfume_stores_fallback',
            __('Магазини (опростен)', 'parfume-reviews'),
            array($this, 'render_fallback_stores_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    public function render_fallback_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_fallback_meta_box', 'parfume_stores_fallback_nonce');
        
        $stores_data = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            $stores_data = array();
        }
        
        echo '<p>' . __('Опростен интерфейс за магазини. За пълната функционалност активирайте Stores Meta Box компонента.', 'parfume-reviews') . '</p>';
        echo '<textarea name="parfume_stores_simple" rows="10" style="width: 100%;" placeholder="' . __('JSON данни за магазини...', 'parfume-reviews') . '">';
        echo esc_textarea(wp_json_encode($stores_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo '</textarea>';
    }
    
    public function save_fallback_stores_meta_box($post_id) {
        if (!isset($_POST['parfume_stores_fallback_nonce']) || 
            !wp_verify_nonce($_POST['parfume_stores_fallback_nonce'], 'parfume_stores_fallback_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['parfume_stores_simple'])) {
            $stores_json = sanitize_textarea_field($_POST['parfume_stores_simple']);
            $stores_data = json_decode($stores_json, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($stores_data)) {
                update_post_meta($post_id, '_parfume_stores', $stores_data);
            } else {
                delete_post_meta($post_id, '_parfume_stores');
            }
        }
    } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Query Handler class not found, using basic functionality");
            }
        }
        
        // Debug хук
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_query_info'));
        }
    }
    
    /**
     * Зарежда Stores Meta Box компонента
     */
    private function load_stores_meta_box() {
        $stores_meta_box_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-stores-meta-box.php';
        
        if (file_exists($stores_meta_box_file)) {
            require_once $stores_meta_box_file;
            $this->stores_meta_box = new \Parfume_Reviews\Post_Type\Stores_Meta_Box();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box loaded successfully");
            }
        } else {
            // ПОПРАВЕНО: Не зарежда липсващ файл, само логира
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box file not found, using fallback meta boxes");
            }
            
            // Fallback към оригиналните meta boxes
            add_action('add_meta_boxes', array($this, 'add_fallback_stores_meta_box'));
            add_action('save_post', array($this, 'save_fallback_stores_meta_box'));
        }
    }
    
    /**
     * Регистрира parfume post type
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name'                  => _x('Parfumes', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Parfume', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Parfumes', 'parfume-reviews'),
            'name_admin_bar'       => __('Parfume', 'parfume-reviews'),
            'archives'             => __('Parfume Archives', 'parfume-reviews'),
            'all_items'            => __('All Parfumes', 'parfume-reviews'),
            'add_new_item'         => __('Add New Parfume', 'parfume-reviews'),
            'add_new'              => __('Add New', 'parfume-reviews'),
            'new_item'             => __('New Parfume', 'parfume-reviews'),
            'edit_item'            => __('Edit Parfume', 'parfume-reviews'),
            'update_item'          => __('Update Parfume', 'parfume-reviews'),
            'view_item'            => __('View Parfume', 'parfume-reviews'),
            'search_items'         => __('Search Parfumes', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Parfume', 'parfume-reviews'),
            'description'          => __('Parfume reviews and information', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'author', 'revisions', 
                                           'excerpt', 'comments', 'custom-fields'),
            'taxonomies'           => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
            'hierarchical'         => false,
            'public'               => true,
            'show_ui'              => true,
            'show_in_menu'         => true,
            'menu_position'        => 5,
            'show_in_admin_bar'    => true,
            'show_in_nav_menus'    => true,
            'can_export'           => true,
            'has_archive'          => true,
            'exclude_from_search'  => false,
            'publicly_queryable'   => true,
            'capability_type'      => 'post',
            'show_in_rest'         => true,
            'menu_icon'            => 'dashicons-admin-appearance',
            'rewrite'              => array(
                'slug'         => $slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume_blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Blog slug трябва да е под parfume_slug
        $blog_slug = $parfume_slug . '/blog';
        
        $labels = array(
            'name'                  => _x('Parfume Blog', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Blog Post', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Blog Posts', 'parfume-reviews'),
            'name_admin_bar'       => __('Blog Post', 'parfume-reviews'),
            'archives'             => __('Blog Archives', 'parfume-reviews'),
            'all_items'            => __('All Blog Posts', 'parfume-reviews'),
            'add_new_item'         => __('Add New Blog Post', 'parfume-reviews'),
            'add_new'              => __('Add New', 'parfume-reviews'),
            'new_item'             => __('New Blog Post', 'parfume-reviews'),
            'edit_item'            => __('Edit Blog Post', 'parfume-reviews'),
            'update_item'          => __('Update Blog Post', 'parfume-reviews'),
            'view_item'            => __('View Blog Post', 'parfume-reviews'),
            'search_items'         => __('Search Blog Posts', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Blog Post', 'parfume-reviews'),
            'description'          => __('Blog posts about parfumes', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'hierarchical'         => false,
            'public'               => true,
            'show_ui'              => true,
            'show_in_menu'         => 'edit.php?post_type=parfume',
            'menu_position'        => 5,
            'show_in_admin_bar'    => true,
            'show_in_nav_menus'    => true,
            'can_export'           => true,
            'has_archive'          => true,
            'exclude_from_search'  => false,
            'publicly_queryable'   => true,
            'capability_type'      => 'post',
            'show_in_rest'         => true,
            'rewrite'              => array(
                'slug'         => $blog_slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя custom rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Blog archive rewrite rules - най-важни първи
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/?
    
    public function __construct() {
        // Основни хукове за post type
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Зареждаме Stores Meta Box компонента
        $this->load_stores_meta_box();
        
        // Оригинални meta boxes
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // Оригинални AJAX handlers
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
    }
    
    /**
     * Зарежда Stores Meta Box компонента
     */
    private function load_stores_meta_box() {
        $stores_meta_box_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-stores-meta-box.php';
        
        if (file_exists($stores_meta_box_file)) {
            require_once $stores_meta_box_file;
            $this->stores_meta_box = new \Parfume_Reviews\Post_Type\Stores_Meta_Box();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box loaded successfully");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box file not found: {$stores_meta_box_file}");
            }
        }
    }
    
    /**
     * Регистрира parfume post type
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => _x('Парфюми', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Парфюм', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюми', 'Admin Menu text', 'parfume-reviews'),
            'name_admin_bar' => _x('Парфюм', 'Add New on Toolbar', 'parfume-reviews'),
            'add_new' => __('Добави нов', 'parfume-reviews'),
            'add_new_item' => __('Добави нов парфюм', 'parfume-reviews'),
            'new_item' => __('Нов парфюм', 'parfume-reviews'),
            'edit_item' => __('Редактирай парфюм', 'parfume-reviews'),
            'view_item' => __('Виж парфюм', 'parfume-reviews'),
            'all_items' => __('Всички парфюми', 'parfume-reviews'),
            'search_items' => __('Търси парфюми', 'parfume-reviews'),
            'parent_item_colon' => __('Родителски парфюми:', 'parfume-reviews'),
            'not_found' => __('Няма намерени парфюми.', 'parfume-reviews'),
            'not_found_in_trash' => __('Няма намерени парфюми в кошчето.', 'parfume-reviews'),
            'featured_image' => _x('Изображение на парфюма', 'Overrides the "Featured Image" phrase', 'parfume-reviews'),
            'set_featured_image' => _x('Задай изображение на парфюма', 'Overrides the "Set featured image" phrase', 'parfume-reviews'),
            'remove_featured_image' => _x('Премахни изображението на парфюма', 'Overrides the "Remove featured image" phrase', 'parfume-reviews'),
            'use_featured_image' => _x('Използвай като изображение на парфюма', 'Overrides the "Use as featured image" phrase', 'parfume-reviews'),
            'archives' => _x('Архиви на парфюми', 'The post type archive label used in nav menus', 'parfume-reviews'),
            'insert_into_item' => _x('Вмъкни в парфюм', 'Overrides the "Insert into post"/"Insert into page" phrase', 'parfume-reviews'),
            'uploaded_to_this_item' => _x('Качено към този парфюм', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'parfume-reviews'),
            'filter_items_list' => _x('Филтрирай списъка с парфюми', 'Screen reader text for the filter links', 'parfume-reviews'),
            'items_list_navigation' => _x('Навигация в списъка с парфюми', 'Screen reader text for the pagination', 'parfume-reviews'),
            'items_list' => _x('Списък с парфюми', 'Screen reader text for the items list', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $parfume_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-awards',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author'),
            'show_in_rest' => true,
            'taxonomies' => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        $labels = array(
            'name' => _x('Парфюм блог', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Блог пост', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюм блог', 'Admin Menu text', 'parfume-reviews'),
            'add_new' => __('Добави нов пост', 'parfume-reviews'),
            'add_new_item' => __('Добави нов блог пост', 'parfume-reviews'),
            'edit_item' => __('Редактирай блог пост', 'parfume-reviews'),
            'view_item' => __('Виж блог пост', 'parfume-reviews'),
            'all_items' => __('Всички блог постове', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'query_var' => true,
            'rewrite' => array('slug' => $blog_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'show_in_rest' => true,
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        // Rewrite rules за post types
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Enqueue scripts и styles
     */
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || 
            is_singular('parfume_blog') || is_post_type_archive('parfume_blog') ||
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Enqueue "Колона 2" assets
            wp_enqueue_style(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/column2.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/column2.js',
                array('jquery', 'parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Локализация за frontend scripts
            wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_frontend_nonce')
            ));
            
            // Локализация за "Колона 2"
            $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
            wp_localize_script('parfume-reviews-column2', 'parfumeColumn2', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_column2_nonce'),
                'mobile_settings' => $mobile_settings,
                'strings' => array(
                    'copied' => __('Копирано!', 'parfume-reviews'),
                    'copy_failed' => __('Неуспешно копиране', 'parfume-reviews'),
                    'loading' => __('Зареждане...', 'parfume-reviews')
                )
            ));
        }
    }
    
    /**
     * Зарежда template файлове
     */
    public function load_templates($template) {
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавя body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            $classes[] = 'parfume-reviews-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            }
        }
        
        return $classes;
    }
    
    /**
     * Добавя общи meta boxes (оригинални)
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            array($this, 'render_parfume_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-reviews'),
            array($this, 'render_parfume_additional_info_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира детайли meta box
     */
    public function render_parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_meta_box', 'parfume_details_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_price', true);
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $concentration = get_post_meta($post->ID, '_concentration', true);
        $bottle_size = get_post_meta($post->ID, '_bottle_size', true);
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Цена', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" />
                    <p class="description"><?php _e('Цената на парфюма (например: 89.99 лв)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="release_year"><?php _e('Година на издаване', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" 
                           min="1900" max="<?php echo date('Y'); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="concentration"><?php _e('Концентрация', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="concentration" name="concentration">
                        <option value=""><?php _e('Избери концентрация', 'parfume-reviews'); ?></option>
                        <option value="EDT" <?php selected($concentration, 'EDT'); ?>><?php _e('EDT (Eau de Toilette)', 'parfume-reviews'); ?></option>
                        <option value="EDP" <?php selected($concentration, 'EDP'); ?>><?php _e('EDP (Eau de Parfum)', 'parfume-reviews'); ?></option>
                        <option value="EDC" <?php selected($concentration, 'EDC'); ?>><?php _e('EDC (Eau de Cologne)', 'parfume-reviews'); ?></option>
                        <option value="Parfum" <?php selected($concentration, 'Parfum'); ?>><?php _e('Parfum', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bottle_size"><?php _e('Размер на бутилката', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="bottle_size" name="bottle_size" value="<?php echo esc_attr($bottle_size); ?>" class="regular-text" />
                    <p class="description"><?php _e('Например: 50ml, 100ml, 150ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="longevity"><?php _e('Издръжливост', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="longevity" name="longevity">
                        <option value=""><?php _e('Избери издръжливост', 'parfume-reviews'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаба (1-2 часа)', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерена (3-5 часа)', 'parfume-reviews'); ?></option>
                        <option value="long" <?php selected($longevity, 'long'); ?>><?php _e('Дълга (6-8 часа)', 'parfume-reviews'); ?></option>
                        <option value="very_long" <?php selected($longevity, 'very_long'); ?>><?php _e('Много дълга (8+ часа)', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sillage"><?php _e('Силаж', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="sillage" name="sillage">
                        <option value=""><?php _e('Избери силаж', 'parfume-reviews'); ?></option>
                        <option value="intimate" <?php selected($sillage, 'intimate'); ?>><?php _e('Интимен', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерен', 'parfume-reviews'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силен', 'parfume-reviews'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромен', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Рендерира рейтинг meta box
     */
    public function render_parfume_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_meta_box', 'parfume_rating_meta_box_nonce');
        
        $rating = get_post_meta($post->ID, '_rating', true);
        ?>
        <p>
            <label for="rating"><?php _e('Рейтинг (1-10)', 'parfume-reviews'); ?></label>
            <input type="number" id="rating" name="rating" value="<?php echo esc_attr($rating); ?>" 
                   min="1" max="10" step="0.1" class="small-text" />
        </p>
        <p class="description">
            <?php _e('Въведете рейтинг от 1 до 10 (може да използвате десетични числа като 8.5)', 'parfume-reviews'); ?>
        </p>
        <?php
    }
    
    /**
     * Рендерира допълнителна информация meta box
     */
    public function render_parfume_additional_info_meta_box($post) {
        wp_nonce_field('parfume_additional_info_meta_box', 'parfume_additional_info_meta_box_nonce');
        
        $pros = get_post_meta($post->ID, '_pros', true);
        $cons = get_post_meta($post->ID, '_cons', true);
        $occasions = get_post_meta($post->ID, '_occasions', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pros"><?php _e('Предимства', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="pros" name="pros" rows="4" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('Положителните страни на парфюма (всеки ред е отделно предимство)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cons"><?php _e('Недостатъци', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="cons" name="cons" rows="4" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('Отрицателните страни на парфюма (всеки ред е отделен недостатък)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="occasions"><?php _e('Подходящи случаи', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="occasions" name="occasions" rows="3" class="large-text"><?php echo esc_textarea($occasions); ?></textarea>
                    <p class="description"><?php _e('Кога е подходящо да се носи този парфюм', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Записва общи meta box данни
     */
    public function save_general_meta_boxes($post_id) {
        // Проверки за сигурност
        if (!isset($_POST['parfume_details_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_details_meta_box_nonce'], 'parfume_details_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Записваме полетата
        $fields = array(
            'price' => 'sanitize_text_field',
            'release_year' => 'intval',
            'concentration' => 'sanitize_text_field',
            'bottle_size' => 'sanitize_text_field',
            'longevity' => 'sanitize_text_field',
            'sillage' => 'sanitize_text_field',
            'rating' => 'floatval',
            'pros' => 'sanitize_textarea_field',
            'cons' => 'sanitize_textarea_field',
            'occasions' => 'sanitize_textarea_field'
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * AJAX handlers (оригинални)
     */
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        $new_price = sanitize_text_field($_POST['new_price']);
        
        // Тук може да добавим логика за обновяване на цени
        wp_send_json_success(array('message' => __('Цената е обновена.', 'parfume-reviews')));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        
        // Логика за получаване на размери
        wp_send_json_success(array('sizes' => array('50ml', '100ml', '150ml')));
    }
    
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Получаваме scraped данни за този store
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (is_array($stores_data) && isset($stores_data[$store_id]['scraped_data']['variants'])) {
            wp_send_json_success(array('variants' => $stores_data[$store_id]['scraped_data']['variants']));
        } else {
            wp_send_json_error(array('message' => __('Няма данни за варианти.', 'parfume-reviews')));
        }
    }
    
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Тук може да извикаме scraper за обновяване на данните
        wp_send_json_success(array('message' => __('Данните са обновени.', 'parfume-reviews')));
    }
    
    public function ajax_compare_prices() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        $comparison_data = array();
        foreach ($post_ids as $post_id) {
            $stores_data = get_post_meta($post_id, '_parfume_stores', true);
            if (is_array($stores_data)) {
                $comparison_data[$post_id] = $stores_data;
            }
        }
        
        wp_send_json_success(array('comparison' => $comparison_data));
    }
    
    /**
     * Получава stores meta box instance
     */
    public function get_stores_meta_box() {
        return $this->stores_meta_box;
    }
    
    /**
     * Проверява дали stores meta box е зареден
     */
    public function has_stores_meta_box() {
        return !is_null($this->stores_meta_box);
    }
}
,
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        // Blog pagination
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/page/([0-9]+)/?
    
    public function __construct() {
        // Основни хукове за post type
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Зареждаме Stores Meta Box компонента
        $this->load_stores_meta_box();
        
        // Оригинални meta boxes
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // Оригинални AJAX handlers
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
    }
    
    /**
     * Зарежда Stores Meta Box компонента
     */
    private function load_stores_meta_box() {
        $stores_meta_box_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-stores-meta-box.php';
        
        if (file_exists($stores_meta_box_file)) {
            require_once $stores_meta_box_file;
            $this->stores_meta_box = new \Parfume_Reviews\Post_Type\Stores_Meta_Box();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box loaded successfully");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box file not found: {$stores_meta_box_file}");
            }
        }
    }
    
    /**
     * Регистрира parfume post type
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => _x('Парфюми', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Парфюм', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюми', 'Admin Menu text', 'parfume-reviews'),
            'name_admin_bar' => _x('Парфюм', 'Add New on Toolbar', 'parfume-reviews'),
            'add_new' => __('Добави нов', 'parfume-reviews'),
            'add_new_item' => __('Добави нов парфюм', 'parfume-reviews'),
            'new_item' => __('Нов парфюм', 'parfume-reviews'),
            'edit_item' => __('Редактирай парфюм', 'parfume-reviews'),
            'view_item' => __('Виж парфюм', 'parfume-reviews'),
            'all_items' => __('Всички парфюми', 'parfume-reviews'),
            'search_items' => __('Търси парфюми', 'parfume-reviews'),
            'parent_item_colon' => __('Родителски парфюми:', 'parfume-reviews'),
            'not_found' => __('Няма намерени парфюми.', 'parfume-reviews'),
            'not_found_in_trash' => __('Няма намерени парфюми в кошчето.', 'parfume-reviews'),
            'featured_image' => _x('Изображение на парфюма', 'Overrides the "Featured Image" phrase', 'parfume-reviews'),
            'set_featured_image' => _x('Задай изображение на парфюма', 'Overrides the "Set featured image" phrase', 'parfume-reviews'),
            'remove_featured_image' => _x('Премахни изображението на парфюма', 'Overrides the "Remove featured image" phrase', 'parfume-reviews'),
            'use_featured_image' => _x('Използвай като изображение на парфюма', 'Overrides the "Use as featured image" phrase', 'parfume-reviews'),
            'archives' => _x('Архиви на парфюми', 'The post type archive label used in nav menus', 'parfume-reviews'),
            'insert_into_item' => _x('Вмъкни в парфюм', 'Overrides the "Insert into post"/"Insert into page" phrase', 'parfume-reviews'),
            'uploaded_to_this_item' => _x('Качено към този парфюм', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'parfume-reviews'),
            'filter_items_list' => _x('Филтрирай списъка с парфюми', 'Screen reader text for the filter links', 'parfume-reviews'),
            'items_list_navigation' => _x('Навигация в списъка с парфюми', 'Screen reader text for the pagination', 'parfume-reviews'),
            'items_list' => _x('Списък с парфюми', 'Screen reader text for the items list', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $parfume_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-awards',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author'),
            'show_in_rest' => true,
            'taxonomies' => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        $labels = array(
            'name' => _x('Парфюм блог', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Блог пост', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюм блог', 'Admin Menu text', 'parfume-reviews'),
            'add_new' => __('Добави нов пост', 'parfume-reviews'),
            'add_new_item' => __('Добави нов блог пост', 'parfume-reviews'),
            'edit_item' => __('Редактирай блог пост', 'parfume-reviews'),
            'view_item' => __('Виж блог пост', 'parfume-reviews'),
            'all_items' => __('Всички блог постове', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'query_var' => true,
            'rewrite' => array('slug' => $blog_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'show_in_rest' => true,
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        // Rewrite rules за post types
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Enqueue scripts и styles
     */
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || 
            is_singular('parfume_blog') || is_post_type_archive('parfume_blog') ||
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Enqueue "Колона 2" assets
            wp_enqueue_style(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/column2.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/column2.js',
                array('jquery', 'parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Локализация за frontend scripts
            wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_frontend_nonce')
            ));
            
            // Локализация за "Колона 2"
            $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
            wp_localize_script('parfume-reviews-column2', 'parfumeColumn2', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_column2_nonce'),
                'mobile_settings' => $mobile_settings,
                'strings' => array(
                    'copied' => __('Копирано!', 'parfume-reviews'),
                    'copy_failed' => __('Неуспешно копиране', 'parfume-reviews'),
                    'loading' => __('Зареждане...', 'parfume-reviews')
                )
            ));
        }
    }
    
    /**
     * Зарежда template файлове
     */
    public function load_templates($template) {
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавя body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            $classes[] = 'parfume-reviews-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            }
        }
        
        return $classes;
    }
    
    /**
     * Добавя общи meta boxes (оригинални)
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            array($this, 'render_parfume_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-reviews'),
            array($this, 'render_parfume_additional_info_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира детайли meta box
     */
    public function render_parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_meta_box', 'parfume_details_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_price', true);
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $concentration = get_post_meta($post->ID, '_concentration', true);
        $bottle_size = get_post_meta($post->ID, '_bottle_size', true);
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Цена', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" />
                    <p class="description"><?php _e('Цената на парфюма (например: 89.99 лв)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="release_year"><?php _e('Година на издаване', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" 
                           min="1900" max="<?php echo date('Y'); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="concentration"><?php _e('Концентрация', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="concentration" name="concentration">
                        <option value=""><?php _e('Избери концентрация', 'parfume-reviews'); ?></option>
                        <option value="EDT" <?php selected($concentration, 'EDT'); ?>><?php _e('EDT (Eau de Toilette)', 'parfume-reviews'); ?></option>
                        <option value="EDP" <?php selected($concentration, 'EDP'); ?>><?php _e('EDP (Eau de Parfum)', 'parfume-reviews'); ?></option>
                        <option value="EDC" <?php selected($concentration, 'EDC'); ?>><?php _e('EDC (Eau de Cologne)', 'parfume-reviews'); ?></option>
                        <option value="Parfum" <?php selected($concentration, 'Parfum'); ?>><?php _e('Parfum', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bottle_size"><?php _e('Размер на бутилката', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="bottle_size" name="bottle_size" value="<?php echo esc_attr($bottle_size); ?>" class="regular-text" />
                    <p class="description"><?php _e('Например: 50ml, 100ml, 150ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="longevity"><?php _e('Издръжливост', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="longevity" name="longevity">
                        <option value=""><?php _e('Избери издръжливост', 'parfume-reviews'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаба (1-2 часа)', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерена (3-5 часа)', 'parfume-reviews'); ?></option>
                        <option value="long" <?php selected($longevity, 'long'); ?>><?php _e('Дълга (6-8 часа)', 'parfume-reviews'); ?></option>
                        <option value="very_long" <?php selected($longevity, 'very_long'); ?>><?php _e('Много дълга (8+ часа)', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sillage"><?php _e('Силаж', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="sillage" name="sillage">
                        <option value=""><?php _e('Избери силаж', 'parfume-reviews'); ?></option>
                        <option value="intimate" <?php selected($sillage, 'intimate'); ?>><?php _e('Интимен', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерен', 'parfume-reviews'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силен', 'parfume-reviews'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромен', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Рендерира рейтинг meta box
     */
    public function render_parfume_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_meta_box', 'parfume_rating_meta_box_nonce');
        
        $rating = get_post_meta($post->ID, '_rating', true);
        ?>
        <p>
            <label for="rating"><?php _e('Рейтинг (1-10)', 'parfume-reviews'); ?></label>
            <input type="number" id="rating" name="rating" value="<?php echo esc_attr($rating); ?>" 
                   min="1" max="10" step="0.1" class="small-text" />
        </p>
        <p class="description">
            <?php _e('Въведете рейтинг от 1 до 10 (може да използвате десетични числа като 8.5)', 'parfume-reviews'); ?>
        </p>
        <?php
    }
    
    /**
     * Рендерира допълнителна информация meta box
     */
    public function render_parfume_additional_info_meta_box($post) {
        wp_nonce_field('parfume_additional_info_meta_box', 'parfume_additional_info_meta_box_nonce');
        
        $pros = get_post_meta($post->ID, '_pros', true);
        $cons = get_post_meta($post->ID, '_cons', true);
        $occasions = get_post_meta($post->ID, '_occasions', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pros"><?php _e('Предимства', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="pros" name="pros" rows="4" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('Положителните страни на парфюма (всеки ред е отделно предимство)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cons"><?php _e('Недостатъци', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="cons" name="cons" rows="4" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('Отрицателните страни на парфюма (всеки ред е отделен недостатък)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="occasions"><?php _e('Подходящи случаи', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="occasions" name="occasions" rows="3" class="large-text"><?php echo esc_textarea($occasions); ?></textarea>
                    <p class="description"><?php _e('Кога е подходящо да се носи този парфюм', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Записва общи meta box данни
     */
    public function save_general_meta_boxes($post_id) {
        // Проверки за сигурност
        if (!isset($_POST['parfume_details_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_details_meta_box_nonce'], 'parfume_details_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Записваме полетата
        $fields = array(
            'price' => 'sanitize_text_field',
            'release_year' => 'intval',
            'concentration' => 'sanitize_text_field',
            'bottle_size' => 'sanitize_text_field',
            'longevity' => 'sanitize_text_field',
            'sillage' => 'sanitize_text_field',
            'rating' => 'floatval',
            'pros' => 'sanitize_textarea_field',
            'cons' => 'sanitize_textarea_field',
            'occasions' => 'sanitize_textarea_field'
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * AJAX handlers (оригинални)
     */
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        $new_price = sanitize_text_field($_POST['new_price']);
        
        // Тук може да добавим логика за обновяване на цени
        wp_send_json_success(array('message' => __('Цената е обновена.', 'parfume-reviews')));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        
        // Логика за получаване на размери
        wp_send_json_success(array('sizes' => array('50ml', '100ml', '150ml')));
    }
    
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Получаваме scraped данни за този store
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (is_array($stores_data) && isset($stores_data[$store_id]['scraped_data']['variants'])) {
            wp_send_json_success(array('variants' => $stores_data[$store_id]['scraped_data']['variants']));
        } else {
            wp_send_json_error(array('message' => __('Няма данни за варианти.', 'parfume-reviews')));
        }
    }
    
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Тук може да извикаме scraper за обновяване на данните
        wp_send_json_success(array('message' => __('Данните са обновени.', 'parfume-reviews')));
    }
    
    public function ajax_compare_prices() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        $comparison_data = array();
        foreach ($post_ids as $post_id) {
            $stores_data = get_post_meta($post_id, '_parfume_stores', true);
            if (is_array($stores_data)) {
                $comparison_data[$post_id] = $stores_data;
            }
        }
        
        wp_send_json_success(array('comparison' => $comparison_data));
    }
    
    /**
     * Получава stores meta box instance
     */
    public function get_stores_meta_box() {
        return $this->stores_meta_box;
    }
    
    /**
     * Проверява дали stores meta box е зареден
     */
    public function has_stores_meta_box() {
        return !is_null($this->stores_meta_box);
    }
}
,
            'index.php?post_type=parfume_blog&paged=$matches[1]',
            'top'
        );
        
        // Single blog posts
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/([^/]+)/?
    
    public function __construct() {
        // Основни хукове за post type
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Зареждаме Stores Meta Box компонента
        $this->load_stores_meta_box();
        
        // Оригинални meta boxes
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // Оригинални AJAX handlers
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
    }
    
    /**
     * Зарежда Stores Meta Box компонента
     */
    private function load_stores_meta_box() {
        $stores_meta_box_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-stores-meta-box.php';
        
        if (file_exists($stores_meta_box_file)) {
            require_once $stores_meta_box_file;
            $this->stores_meta_box = new \Parfume_Reviews\Post_Type\Stores_Meta_Box();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box loaded successfully");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box file not found: {$stores_meta_box_file}");
            }
        }
    }
    
    /**
     * Регистрира parfume post type
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => _x('Парфюми', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Парфюм', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюми', 'Admin Menu text', 'parfume-reviews'),
            'name_admin_bar' => _x('Парфюм', 'Add New on Toolbar', 'parfume-reviews'),
            'add_new' => __('Добави нов', 'parfume-reviews'),
            'add_new_item' => __('Добави нов парфюм', 'parfume-reviews'),
            'new_item' => __('Нов парфюм', 'parfume-reviews'),
            'edit_item' => __('Редактирай парфюм', 'parfume-reviews'),
            'view_item' => __('Виж парфюм', 'parfume-reviews'),
            'all_items' => __('Всички парфюми', 'parfume-reviews'),
            'search_items' => __('Търси парфюми', 'parfume-reviews'),
            'parent_item_colon' => __('Родителски парфюми:', 'parfume-reviews'),
            'not_found' => __('Няма намерени парфюми.', 'parfume-reviews'),
            'not_found_in_trash' => __('Няма намерени парфюми в кошчето.', 'parfume-reviews'),
            'featured_image' => _x('Изображение на парфюма', 'Overrides the "Featured Image" phrase', 'parfume-reviews'),
            'set_featured_image' => _x('Задай изображение на парфюма', 'Overrides the "Set featured image" phrase', 'parfume-reviews'),
            'remove_featured_image' => _x('Премахни изображението на парфюма', 'Overrides the "Remove featured image" phrase', 'parfume-reviews'),
            'use_featured_image' => _x('Използвай като изображение на парфюма', 'Overrides the "Use as featured image" phrase', 'parfume-reviews'),
            'archives' => _x('Архиви на парфюми', 'The post type archive label used in nav menus', 'parfume-reviews'),
            'insert_into_item' => _x('Вмъкни в парфюм', 'Overrides the "Insert into post"/"Insert into page" phrase', 'parfume-reviews'),
            'uploaded_to_this_item' => _x('Качено към този парфюм', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'parfume-reviews'),
            'filter_items_list' => _x('Филтрирай списъка с парфюми', 'Screen reader text for the filter links', 'parfume-reviews'),
            'items_list_navigation' => _x('Навигация в списъка с парфюми', 'Screen reader text for the pagination', 'parfume-reviews'),
            'items_list' => _x('Списък с парфюми', 'Screen reader text for the items list', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $parfume_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-awards',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author'),
            'show_in_rest' => true,
            'taxonomies' => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        $labels = array(
            'name' => _x('Парфюм блог', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Блог пост', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюм блог', 'Admin Menu text', 'parfume-reviews'),
            'add_new' => __('Добави нов пост', 'parfume-reviews'),
            'add_new_item' => __('Добави нов блог пост', 'parfume-reviews'),
            'edit_item' => __('Редактирай блог пост', 'parfume-reviews'),
            'view_item' => __('Виж блог пост', 'parfume-reviews'),
            'all_items' => __('Всички блог постове', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'query_var' => true,
            'rewrite' => array('slug' => $blog_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'show_in_rest' => true,
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        // Rewrite rules за post types
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Enqueue scripts и styles
     */
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || 
            is_singular('parfume_blog') || is_post_type_archive('parfume_blog') ||
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Enqueue "Колона 2" assets
            wp_enqueue_style(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/column2.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/column2.js',
                array('jquery', 'parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Локализация за frontend scripts
            wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_frontend_nonce')
            ));
            
            // Локализация за "Колона 2"
            $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
            wp_localize_script('parfume-reviews-column2', 'parfumeColumn2', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_column2_nonce'),
                'mobile_settings' => $mobile_settings,
                'strings' => array(
                    'copied' => __('Копирано!', 'parfume-reviews'),
                    'copy_failed' => __('Неуспешно копиране', 'parfume-reviews'),
                    'loading' => __('Зареждане...', 'parfume-reviews')
                )
            ));
        }
    }
    
    /**
     * Зарежда template файлове
     */
    public function load_templates($template) {
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавя body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            $classes[] = 'parfume-reviews-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            }
        }
        
        return $classes;
    }
    
    /**
     * Добавя общи meta boxes (оригинални)
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            array($this, 'render_parfume_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-reviews'),
            array($this, 'render_parfume_additional_info_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира детайли meta box
     */
    public function render_parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_meta_box', 'parfume_details_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_price', true);
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $concentration = get_post_meta($post->ID, '_concentration', true);
        $bottle_size = get_post_meta($post->ID, '_bottle_size', true);
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Цена', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" />
                    <p class="description"><?php _e('Цената на парфюма (например: 89.99 лв)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="release_year"><?php _e('Година на издаване', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" 
                           min="1900" max="<?php echo date('Y'); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="concentration"><?php _e('Концентрация', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="concentration" name="concentration">
                        <option value=""><?php _e('Избери концентрация', 'parfume-reviews'); ?></option>
                        <option value="EDT" <?php selected($concentration, 'EDT'); ?>><?php _e('EDT (Eau de Toilette)', 'parfume-reviews'); ?></option>
                        <option value="EDP" <?php selected($concentration, 'EDP'); ?>><?php _e('EDP (Eau de Parfum)', 'parfume-reviews'); ?></option>
                        <option value="EDC" <?php selected($concentration, 'EDC'); ?>><?php _e('EDC (Eau de Cologne)', 'parfume-reviews'); ?></option>
                        <option value="Parfum" <?php selected($concentration, 'Parfum'); ?>><?php _e('Parfum', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bottle_size"><?php _e('Размер на бутилката', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="bottle_size" name="bottle_size" value="<?php echo esc_attr($bottle_size); ?>" class="regular-text" />
                    <p class="description"><?php _e('Например: 50ml, 100ml, 150ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="longevity"><?php _e('Издръжливост', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="longevity" name="longevity">
                        <option value=""><?php _e('Избери издръжливост', 'parfume-reviews'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаба (1-2 часа)', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерена (3-5 часа)', 'parfume-reviews'); ?></option>
                        <option value="long" <?php selected($longevity, 'long'); ?>><?php _e('Дълга (6-8 часа)', 'parfume-reviews'); ?></option>
                        <option value="very_long" <?php selected($longevity, 'very_long'); ?>><?php _e('Много дълга (8+ часа)', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sillage"><?php _e('Силаж', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="sillage" name="sillage">
                        <option value=""><?php _e('Избери силаж', 'parfume-reviews'); ?></option>
                        <option value="intimate" <?php selected($sillage, 'intimate'); ?>><?php _e('Интимен', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерен', 'parfume-reviews'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силен', 'parfume-reviews'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромен', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Рендерира рейтинг meta box
     */
    public function render_parfume_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_meta_box', 'parfume_rating_meta_box_nonce');
        
        $rating = get_post_meta($post->ID, '_rating', true);
        ?>
        <p>
            <label for="rating"><?php _e('Рейтинг (1-10)', 'parfume-reviews'); ?></label>
            <input type="number" id="rating" name="rating" value="<?php echo esc_attr($rating); ?>" 
                   min="1" max="10" step="0.1" class="small-text" />
        </p>
        <p class="description">
            <?php _e('Въведете рейтинг от 1 до 10 (може да използвате десетични числа като 8.5)', 'parfume-reviews'); ?>
        </p>
        <?php
    }
    
    /**
     * Рендерира допълнителна информация meta box
     */
    public function render_parfume_additional_info_meta_box($post) {
        wp_nonce_field('parfume_additional_info_meta_box', 'parfume_additional_info_meta_box_nonce');
        
        $pros = get_post_meta($post->ID, '_pros', true);
        $cons = get_post_meta($post->ID, '_cons', true);
        $occasions = get_post_meta($post->ID, '_occasions', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pros"><?php _e('Предимства', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="pros" name="pros" rows="4" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('Положителните страни на парфюма (всеки ред е отделно предимство)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cons"><?php _e('Недостатъци', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="cons" name="cons" rows="4" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('Отрицателните страни на парфюма (всеки ред е отделен недостатък)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="occasions"><?php _e('Подходящи случаи', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="occasions" name="occasions" rows="3" class="large-text"><?php echo esc_textarea($occasions); ?></textarea>
                    <p class="description"><?php _e('Кога е подходящо да се носи този парфюм', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Записва общи meta box данни
     */
    public function save_general_meta_boxes($post_id) {
        // Проверки за сигурност
        if (!isset($_POST['parfume_details_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_details_meta_box_nonce'], 'parfume_details_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Записваме полетата
        $fields = array(
            'price' => 'sanitize_text_field',
            'release_year' => 'intval',
            'concentration' => 'sanitize_text_field',
            'bottle_size' => 'sanitize_text_field',
            'longevity' => 'sanitize_text_field',
            'sillage' => 'sanitize_text_field',
            'rating' => 'floatval',
            'pros' => 'sanitize_textarea_field',
            'cons' => 'sanitize_textarea_field',
            'occasions' => 'sanitize_textarea_field'
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * AJAX handlers (оригинални)
     */
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        $new_price = sanitize_text_field($_POST['new_price']);
        
        // Тук може да добавим логика за обновяване на цени
        wp_send_json_success(array('message' => __('Цената е обновена.', 'parfume-reviews')));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        
        // Логика за получаване на размери
        wp_send_json_success(array('sizes' => array('50ml', '100ml', '150ml')));
    }
    
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Получаваме scraped данни за този store
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (is_array($stores_data) && isset($stores_data[$store_id]['scraped_data']['variants'])) {
            wp_send_json_success(array('variants' => $stores_data[$store_id]['scraped_data']['variants']));
        } else {
            wp_send_json_error(array('message' => __('Няма данни за варианти.', 'parfume-reviews')));
        }
    }
    
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Тук може да извикаме scraper за обновяване на данните
        wp_send_json_success(array('message' => __('Данните са обновени.', 'parfume-reviews')));
    }
    
    public function ajax_compare_prices() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        $comparison_data = array();
        foreach ($post_ids as $post_id) {
            $stores_data = get_post_meta($post_id, '_parfume_stores', true);
            if (is_array($stores_data)) {
                $comparison_data[$post_id] = $stores_data;
            }
        }
        
        wp_send_json_success(array('comparison' => $comparison_data));
    }
    
    /**
     * Получава stores meta box instance
     */
    public function get_stores_meta_box() {
        return $this->stores_meta_box;
    }
    
    /**
     * Проверява дали stores meta box е зареден
     */
    public function has_stores_meta_box() {
        return !is_null($this->stores_meta_box);
    }
}
,
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
        
        // Parfume rules
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?
    
    public function __construct() {
        // Основни хукове за post type
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Зареждаме Stores Meta Box компонента
        $this->load_stores_meta_box();
        
        // Оригинални meta boxes
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // Оригинални AJAX handlers
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
    }
    
    /**
     * Зарежда Stores Meta Box компонента
     */
    private function load_stores_meta_box() {
        $stores_meta_box_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-stores-meta-box.php';
        
        if (file_exists($stores_meta_box_file)) {
            require_once $stores_meta_box_file;
            $this->stores_meta_box = new \Parfume_Reviews\Post_Type\Stores_Meta_Box();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box loaded successfully");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box file not found: {$stores_meta_box_file}");
            }
        }
    }
    
    /**
     * Регистрира parfume post type
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => _x('Парфюми', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Парфюм', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюми', 'Admin Menu text', 'parfume-reviews'),
            'name_admin_bar' => _x('Парфюм', 'Add New on Toolbar', 'parfume-reviews'),
            'add_new' => __('Добави нов', 'parfume-reviews'),
            'add_new_item' => __('Добави нов парфюм', 'parfume-reviews'),
            'new_item' => __('Нов парфюм', 'parfume-reviews'),
            'edit_item' => __('Редактирай парфюм', 'parfume-reviews'),
            'view_item' => __('Виж парфюм', 'parfume-reviews'),
            'all_items' => __('Всички парфюми', 'parfume-reviews'),
            'search_items' => __('Търси парфюми', 'parfume-reviews'),
            'parent_item_colon' => __('Родителски парфюми:', 'parfume-reviews'),
            'not_found' => __('Няма намерени парфюми.', 'parfume-reviews'),
            'not_found_in_trash' => __('Няма намерени парфюми в кошчето.', 'parfume-reviews'),
            'featured_image' => _x('Изображение на парфюма', 'Overrides the "Featured Image" phrase', 'parfume-reviews'),
            'set_featured_image' => _x('Задай изображение на парфюма', 'Overrides the "Set featured image" phrase', 'parfume-reviews'),
            'remove_featured_image' => _x('Премахни изображението на парфюма', 'Overrides the "Remove featured image" phrase', 'parfume-reviews'),
            'use_featured_image' => _x('Използвай като изображение на парфюма', 'Overrides the "Use as featured image" phrase', 'parfume-reviews'),
            'archives' => _x('Архиви на парфюми', 'The post type archive label used in nav menus', 'parfume-reviews'),
            'insert_into_item' => _x('Вмъкни в парфюм', 'Overrides the "Insert into post"/"Insert into page" phrase', 'parfume-reviews'),
            'uploaded_to_this_item' => _x('Качено към този парфюм', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'parfume-reviews'),
            'filter_items_list' => _x('Филтрирай списъка с парфюми', 'Screen reader text for the filter links', 'parfume-reviews'),
            'items_list_navigation' => _x('Навигация в списъка с парфюми', 'Screen reader text for the pagination', 'parfume-reviews'),
            'items_list' => _x('Списък с парфюми', 'Screen reader text for the items list', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $parfume_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-awards',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author'),
            'show_in_rest' => true,
            'taxonomies' => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        $labels = array(
            'name' => _x('Парфюм блог', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Блог пост', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюм блог', 'Admin Menu text', 'parfume-reviews'),
            'add_new' => __('Добави нов пост', 'parfume-reviews'),
            'add_new_item' => __('Добави нов блог пост', 'parfume-reviews'),
            'edit_item' => __('Редактирай блог пост', 'parfume-reviews'),
            'view_item' => __('Виж блог пост', 'parfume-reviews'),
            'all_items' => __('Всички блог постове', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'query_var' => true,
            'rewrite' => array('slug' => $blog_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'show_in_rest' => true,
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        // Rewrite rules за post types
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Enqueue scripts и styles
     */
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || 
            is_singular('parfume_blog') || is_post_type_archive('parfume_blog') ||
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Enqueue "Колона 2" assets
            wp_enqueue_style(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/column2.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/column2.js',
                array('jquery', 'parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Локализация за frontend scripts
            wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_frontend_nonce')
            ));
            
            // Локализация за "Колона 2"
            $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
            wp_localize_script('parfume-reviews-column2', 'parfumeColumn2', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_column2_nonce'),
                'mobile_settings' => $mobile_settings,
                'strings' => array(
                    'copied' => __('Копирано!', 'parfume-reviews'),
                    'copy_failed' => __('Неуспешно копиране', 'parfume-reviews'),
                    'loading' => __('Зареждане...', 'parfume-reviews')
                )
            ));
        }
    }
    
    /**
     * Зарежда template файлове
     */
    public function load_templates($template) {
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавя body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            $classes[] = 'parfume-reviews-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            }
        }
        
        return $classes;
    }
    
    /**
     * Добавя общи meta boxes (оригинални)
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            array($this, 'render_parfume_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-reviews'),
            array($this, 'render_parfume_additional_info_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира детайли meta box
     */
    public function render_parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_meta_box', 'parfume_details_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_price', true);
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $concentration = get_post_meta($post->ID, '_concentration', true);
        $bottle_size = get_post_meta($post->ID, '_bottle_size', true);
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Цена', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" />
                    <p class="description"><?php _e('Цената на парфюма (например: 89.99 лв)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="release_year"><?php _e('Година на издаване', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" 
                           min="1900" max="<?php echo date('Y'); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="concentration"><?php _e('Концентрация', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="concentration" name="concentration">
                        <option value=""><?php _e('Избери концентрация', 'parfume-reviews'); ?></option>
                        <option value="EDT" <?php selected($concentration, 'EDT'); ?>><?php _e('EDT (Eau de Toilette)', 'parfume-reviews'); ?></option>
                        <option value="EDP" <?php selected($concentration, 'EDP'); ?>><?php _e('EDP (Eau de Parfum)', 'parfume-reviews'); ?></option>
                        <option value="EDC" <?php selected($concentration, 'EDC'); ?>><?php _e('EDC (Eau de Cologne)', 'parfume-reviews'); ?></option>
                        <option value="Parfum" <?php selected($concentration, 'Parfum'); ?>><?php _e('Parfum', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bottle_size"><?php _e('Размер на бутилката', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="bottle_size" name="bottle_size" value="<?php echo esc_attr($bottle_size); ?>" class="regular-text" />
                    <p class="description"><?php _e('Например: 50ml, 100ml, 150ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="longevity"><?php _e('Издръжливост', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="longevity" name="longevity">
                        <option value=""><?php _e('Избери издръжливост', 'parfume-reviews'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаба (1-2 часа)', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерена (3-5 часа)', 'parfume-reviews'); ?></option>
                        <option value="long" <?php selected($longevity, 'long'); ?>><?php _e('Дълга (6-8 часа)', 'parfume-reviews'); ?></option>
                        <option value="very_long" <?php selected($longevity, 'very_long'); ?>><?php _e('Много дълга (8+ часа)', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sillage"><?php _e('Силаж', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="sillage" name="sillage">
                        <option value=""><?php _e('Избери силаж', 'parfume-reviews'); ?></option>
                        <option value="intimate" <?php selected($sillage, 'intimate'); ?>><?php _e('Интимен', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерен', 'parfume-reviews'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силен', 'parfume-reviews'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромен', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Рендерира рейтинг meta box
     */
    public function render_parfume_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_meta_box', 'parfume_rating_meta_box_nonce');
        
        $rating = get_post_meta($post->ID, '_rating', true);
        ?>
        <p>
            <label for="rating"><?php _e('Рейтинг (1-10)', 'parfume-reviews'); ?></label>
            <input type="number" id="rating" name="rating" value="<?php echo esc_attr($rating); ?>" 
                   min="1" max="10" step="0.1" class="small-text" />
        </p>
        <p class="description">
            <?php _e('Въведете рейтинг от 1 до 10 (може да използвате десетични числа като 8.5)', 'parfume-reviews'); ?>
        </p>
        <?php
    }
    
    /**
     * Рендерира допълнителна информация meta box
     */
    public function render_parfume_additional_info_meta_box($post) {
        wp_nonce_field('parfume_additional_info_meta_box', 'parfume_additional_info_meta_box_nonce');
        
        $pros = get_post_meta($post->ID, '_pros', true);
        $cons = get_post_meta($post->ID, '_cons', true);
        $occasions = get_post_meta($post->ID, '_occasions', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pros"><?php _e('Предимства', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="pros" name="pros" rows="4" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('Положителните страни на парфюма (всеки ред е отделно предимство)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cons"><?php _e('Недостатъци', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="cons" name="cons" rows="4" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('Отрицателните страни на парфюма (всеки ред е отделен недостатък)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="occasions"><?php _e('Подходящи случаи', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="occasions" name="occasions" rows="3" class="large-text"><?php echo esc_textarea($occasions); ?></textarea>
                    <p class="description"><?php _e('Кога е подходящо да се носи този парфюм', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Записва общи meta box данни
     */
    public function save_general_meta_boxes($post_id) {
        // Проверки за сигурност
        if (!isset($_POST['parfume_details_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_details_meta_box_nonce'], 'parfume_details_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Записваме полетата
        $fields = array(
            'price' => 'sanitize_text_field',
            'release_year' => 'intval',
            'concentration' => 'sanitize_text_field',
            'bottle_size' => 'sanitize_text_field',
            'longevity' => 'sanitize_text_field',
            'sillage' => 'sanitize_text_field',
            'rating' => 'floatval',
            'pros' => 'sanitize_textarea_field',
            'cons' => 'sanitize_textarea_field',
            'occasions' => 'sanitize_textarea_field'
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * AJAX handlers (оригинални)
     */
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        $new_price = sanitize_text_field($_POST['new_price']);
        
        // Тук може да добавим логика за обновяване на цени
        wp_send_json_success(array('message' => __('Цената е обновена.', 'parfume-reviews')));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        
        // Логика за получаване на размери
        wp_send_json_success(array('sizes' => array('50ml', '100ml', '150ml')));
    }
    
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Получаваме scraped данни за този store
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (is_array($stores_data) && isset($stores_data[$store_id]['scraped_data']['variants'])) {
            wp_send_json_success(array('variants' => $stores_data[$store_id]['scraped_data']['variants']));
        } else {
            wp_send_json_error(array('message' => __('Няма данни за варианти.', 'parfume-reviews')));
        }
    }
    
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Тук може да извикаме scraper за обновяване на данните
        wp_send_json_success(array('message' => __('Данните са обновени.', 'parfume-reviews')));
    }
    
    public function ajax_compare_prices() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        $comparison_data = array();
        foreach ($post_ids as $post_id) {
            $stores_data = get_post_meta($post_id, '_parfume_stores', true);
            if (is_array($stores_data)) {
                $comparison_data[$post_id] = $stores_data;
            }
        }
        
        wp_send_json_success(array('comparison' => $comparison_data));
    }
    
    /**
     * Получава stores meta box instance
     */
    public function get_stores_meta_box() {
        return $this->stores_meta_box;
    }
    
    /**
     * Проверява дали stores meta box е зареден
     */
    public function has_stores_meta_box() {
        return !is_null($this->stores_meta_box);
    }
}
,
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Custom post type permalink
     */
    public function custom_post_type_link($post_link, $post) {
        if (!is_object($post) || !isset($post->post_type)) {
            return $post_link;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        if ($post->post_type === 'parfume') {
            return home_url('/' . $parfume_slug . '/' . $post->post_name . '/');
        } elseif ($post->post_type === 'parfume_blog') {
            return home_url('/' . $parfume_slug . '/blog/' . $post->post_name . '/');
        }
        
        return $post_link;
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_blog_archive';
        return $vars;
    }
    
    /**
     * Parse request
     */
    public function parse_request($wp) {
        // Handle blog archive requests
        if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'parfume_blog') {
            // No additional processing needed - WordPress will handle automatically
        }
    }
    
    /**
     * Enqueue frontend scripts и styles
     */
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || 
            is_singular('parfume_blog') || is_post_type_archive('parfume_blog') ||
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // ПОПРАВЕНО: Зареждаме Column 2 assets само ако файловете съществуват
            $column2_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/column2.css';
            if (file_exists($column2_css)) {
                wp_enqueue_style(
                    'parfume-reviews-column2',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/column2.css',
                    array('parfume-reviews-frontend'),
                    PARFUME_REVIEWS_VERSION
                );
            }
            
            $column2_js = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/column2.js';
            if (file_exists($column2_js)) {
                wp_enqueue_script(
                    'parfume-reviews-column2',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/column2.js',
                    array('jquery', 'parfume-reviews-frontend'),
                    PARFUME_REVIEWS_VERSION,
                    true
                );
                
                // Локализация за "Колона 2" само ако JS съществува
                $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
                wp_localize_script('parfume-reviews-column2', 'parfumeColumn2', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('parfume_column2_nonce'),
                    'mobile_settings' => $mobile_settings,
                    'strings' => array(
                        'copied' => __('Копирано!', 'parfume-reviews'),
                        'copy_failed' => __('Неуспешно копиране', 'parfume-reviews'),
                        'loading' => __('Зареждане...', 'parfume-reviews')
                    )
                ));
            }
            
            // Локализация за frontend scripts
            wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_frontend_nonce')
            ));
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            if ($post_type == 'parfume') {
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_media();
                
                wp_enqueue_script(
                    'parfume-admin',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js',
                    array('jquery', 'jquery-ui-sortable'),
                    PARFUME_REVIEWS_VERSION,
                    true
                );
                
                wp_localize_script('parfume-admin', 'parfumeAdmin', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('parfume_reviews_nonce'),
                    'strings' => array(
                        'confirm_delete' => __('Are you sure you want to delete this item?', 'parfume-reviews'),
                        'scraping' => __('Scraping...', 'parfume-reviews'),
                        'error' => __('An error occurred', 'parfume-reviews')
                    )
                ));
                
                wp_enqueue_style(
                    'parfume-admin',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css',
                    array(),
                    PARFUME_REVIEWS_VERSION
                );
            }
        }
    }
    
    /**
     * Зарежда template файлове
     */
    public function load_templates($template) {
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавя body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            $classes[] = 'parfume-reviews-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            }
        }
        
        return $classes;
    }
    
    /**
     * Добавя общи meta boxes (оригинални)
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            array($this, 'render_parfume_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-reviews'),
            array($this, 'render_parfume_additional_info_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира детайли meta box
     */
    public function render_parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_meta_box', 'parfume_details_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_price', true);
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $concentration = get_post_meta($post->ID, '_concentration', true);
        $bottle_size = get_post_meta($post->ID, '_bottle_size', true);
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Цена', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" />
                    <p class="description"><?php _e('Цената на парфюма (например: 89.99 лв)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="release_year"><?php _e('Година на издаване', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" 
                           min="1900" max="<?php echo date('Y'); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="concentration"><?php _e('Концентрация', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="concentration" name="concentration">
                        <option value=""><?php _e('Избери концентрация', 'parfume-reviews'); ?></option>
                        <option value="EDT" <?php selected($concentration, 'EDT'); ?>><?php _e('EDT (Eau de Toilette)', 'parfume-reviews'); ?></option>
                        <option value="EDP" <?php selected($concentration, 'EDP'); ?>><?php _e('EDP (Eau de Parfum)', 'parfume-reviews'); ?></option>
                        <option value="EDC" <?php selected($concentration, 'EDC'); ?>><?php _e('EDC (Eau de Cologne)', 'parfume-reviews'); ?></option>
                        <option value="Parfum" <?php selected($concentration, 'Parfum'); ?>><?php _e('Parfum', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bottle_size"><?php _e('Размер на бутилката', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="bottle_size" name="bottle_size" value="<?php echo esc_attr($bottle_size); ?>" class="regular-text" />
                    <p class="description"><?php _e('Например: 50ml, 100ml, 150ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="longevity"><?php _e('Издръжливост', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="longevity" name="longevity">
                        <option value=""><?php _e('Избери издръжливост', 'parfume-reviews'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаба (1-2 часа)', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерена (3-5 часа)', 'parfume-reviews'); ?></option>
                        <option value="long" <?php selected($longevity, 'long'); ?>><?php _e('Дълга (6-8 часа)', 'parfume-reviews'); ?></option>
                        <option value="very_long" <?php selected($longevity, 'very_long'); ?>><?php _e('Много дълга (8+ часа)', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sillage"><?php _e('Силаж', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="sillage" name="sillage">
                        <option value=""><?php _e('Избери силаж', 'parfume-reviews'); ?></option>
                        <option value="intimate" <?php selected($sillage, 'intimate'); ?>><?php _e('Интимен', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерен', 'parfume-reviews'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силен', 'parfume-reviews'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромен', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Рендерира рейтинг meta box
     */
    public function render_parfume_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_meta_box', 'parfume_rating_meta_box_nonce');
        
        $rating = get_post_meta($post->ID, '_rating', true);
        ?>
        <p>
            <label for="rating"><?php _e('Рейтинг (1-10)', 'parfume-reviews'); ?></label>
            <input type="number" id="rating" name="rating" value="<?php echo esc_attr($rating); ?>" 
                   min="1" max="10" step="0.1" class="small-text" />
        </p>
        <p class="description">
            <?php _e('Въведете рейтинг от 1 до 10 (може да използвате десетични числа като 8.5)', 'parfume-reviews'); ?>
        </p>
        <?php
    }
    
    /**
     * Рендерира допълнителна информация meta box
     */
    public function render_parfume_additional_info_meta_box($post) {
        wp_nonce_field('parfume_additional_info_meta_box', 'parfume_additional_info_meta_box_nonce');
        
        $pros = get_post_meta($post->ID, '_pros', true);
        $cons = get_post_meta($post->ID, '_cons', true);
        $occasions = get_post_meta($post->ID, '_occasions', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pros"><?php _e('Предимства', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="pros" name="pros" rows="4" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('Положителните страни на парфюма (всеки ред е отделно предимство)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cons"><?php _e('Недостатъци', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="cons" name="cons" rows="4" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('Отрицателните страни на парфюма (всеки ред е отделен недостатък)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="occasions"><?php _e('Подходящи случаи', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="occasions" name="occasions" rows="3" class="large-text"><?php echo esc_textarea($occasions); ?></textarea>
                    <p class="description"><?php _e('Кога е подходящо да се носи този парфюм', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Записва общи meta box данни
     */
    public function save_general_meta_boxes($post_id) {
        // Проверки за сигурност
        if (!isset($_POST['parfume_details_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_details_meta_box_nonce'], 'parfume_details_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Записваме полетата
        $fields = array(
            'price' => 'sanitize_text_field',
            'release_year' => 'intval',
            'concentration' => 'sanitize_text_field',
            'bottle_size' => 'sanitize_text_field',
            'longevity' => 'sanitize_text_field',
            'sillage' => 'sanitize_text_field',
            'rating' => 'floatval',
            'pros' => 'sanitize_textarea_field',
            'cons' => 'sanitize_textarea_field',
            'occasions' => 'sanitize_textarea_field'
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * AJAX handlers (всички оригинални + нови)
     */
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        $new_price = sanitize_text_field($_POST['new_price']);
        
        // Тук може да добавим логика за обновяване на цени
        wp_send_json_success(array('message' => __('Цената е обновена.', 'parfume-reviews')));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        
        // Mock data for now
        $sizes = array(
            array('size' => '30ml', 'price' => '45.00 лв.'),
            array('size' => '50ml', 'price' => '75.00 лв.'),
            array('size' => '100ml', 'price' => '120.00 лв.'),
        );
        
        wp_send_json_success($sizes);
    }
    
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_key = sanitize_key($_POST['store_key']);
        
        $product_url = home_url('/parfumes/' . get_post_field('post_name', $post_id) . '/');
        
        $variants = array(
            array(
                'name' => '30ml EDT',
                'price' => '45.00', 
                'availability' => 'in_stock',
                'url' => $product_url . '?size=30ml'
            ),
            array(
                'name' => '50ml EDT',
                'price' => '75.00', 
                'availability' => 'in_stock',
                'url' => $product_url . '?size=50ml'
            ),
            array(
                'name' => '100ml EDT',
                'price' => '120.00',
                'availability' => 'limited',
                'url' => $product_url . '?size=100ml'
            )
        );
        
        wp_send_json_success($variants);
    }
    
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!isset($stores[$store_index])) {
            wp_send_json_error('Store not found');
        }
        
        $store = $stores[$store_index];
        
        // Mock refresh - in real implementation would scrape again
        $updated_data = array(
            'price' => number_format(rand(50, 200), 2) . ' лв.',
            'availability' => rand(0, 1) ? 'in_stock' : 'limited',
            'last_updated' => current_time('mysql')
        );
        
        // Update store data
        $stores[$store_index] = array_merge($store, $updated_data);
        update_post_meta($post_id, '_parfume_stores', $stores);
        
        wp_send_json_success($updated_data);
    }
    
    public function ajax_compare_prices() {
        if (isset($_POST['nonce'])) {
            check_ajax_referer('parfume_reviews_nonce', 'nonce');
        }
        
        $parfume_ids = array_map('intval', $_POST['parfume_ids']);
        
        if (empty($parfume_ids)) {
            wp_send_json_error('No parfumes provided');
        }
        
        $comparison_data = array();
        
        foreach ($parfume_ids as $parfume_id) {
            $stores = get_post_meta($parfume_id, '_parfume_stores', true);
            $comparison_data[$parfume_id] = array(
                'title' => get_the_title($parfume_id),
                'stores' => $stores ?: array()
            );
        }
        
        wp_send_json_success($comparison_data);
    }
    
    public function ajax_test_scraper() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $url = esc_url_raw($_POST['test_url']);
        $store_key = sanitize_key($_POST['store_key']);
        
        // Mock scraper test
        $test_result = array(
            'url' => $url,
            'store' => $store_key,
            'found_price' => '89.99 лв.',
            'found_availability' => 'В наличност',
            'found_variants' => array('30ml', '50ml', '100ml'),
            'last_updated' => current_time('mysql')
        );
        
        wp_send_json_success($test_result);
    }
    
    public function ajax_bulk_scrape() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        $updated_count = 0;
        
        foreach ($post_ids as $post_id) {
            // Mock bulk scraping
            $stores = get_post_meta($post_id, '_parfume_stores', true);
            if (is_array($stores)) {
                foreach ($stores as $index => $store) {
                    $stores[$index]['price'] = number_format(rand(50, 200), 2) . ' лв.';
                    $stores[$index]['last_updated'] = current_time('mysql');
                }
                update_post_meta($post_id, '_parfume_stores', $stores);
                $updated_count++;
            }
        }
        
        wp_send_json_success(array(
            'updated_count' => $updated_count,
            'message' => sprintf(__('%d парфюма са обновени.', 'parfume-reviews'), $updated_count)
        ));
    }
    
    public function ajax_schedule_scrape() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $interval = sanitize_text_field($_POST['interval']);
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        // Mock scheduling
        $scheduled_time = current_time('mysql');
        
        wp_send_json_success(array(
            'scheduled_time' => $scheduled_time,
            'interval' => $interval,
            'post_count' => count($post_ids),
            'message' => __('Скрейпването е насрочено.', 'parfume-reviews')
        ));
    }
    
    /**
     * Debug query info
     */
    public function debug_query_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wp_query;
        
        echo '<!-- DEBUG: Post Type Query Info -->';
        echo '<!-- Query vars: ' . print_r($wp_query->query_vars, true) . ' -->';
        echo '<!-- Current post type: ' . get_post_type() . ' -->';
        echo '<!-- Is singular parfume: ' . (is_singular('parfume') ? 'yes' : 'no') . ' -->';
        echo '<!-- Is parfume archive: ' . (is_post_type_archive('parfume') ? 'yes' : 'no') . ' -->';
        echo '<!-- Is blog archive: ' . (is_post_type_archive('parfume_blog') ? 'yes' : 'no') . ' -->';
    }
    
    /**
     * Получава stores meta box instance
     */
    public function get_stores_meta_box() {
        return $this->stores_meta_box;
    }
    
    /**
     * Проверява дали stores meta box е зареден
     */
    public function has_stores_meta_box() {
        return !is_null($this->stores_meta_box);
    }
    
    /**
     * Получава query handler instance
     */
    public function get_query_handler() {
        return $this->query_handler;
    }
}

    
    public function __construct() {
        // Основни хукове за post type
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Зареждаме Stores Meta Box компонента
        $this->load_stores_meta_box();
        
        // Оригинални meta boxes
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // Оригинални AJAX handlers
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
    }
    
    /**
     * Зарежда Stores Meta Box компонента
     */
    private function load_stores_meta_box() {
        $stores_meta_box_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-stores-meta-box.php';
        
        if (file_exists($stores_meta_box_file)) {
            require_once $stores_meta_box_file;
            $this->stores_meta_box = new \Parfume_Reviews\Post_Type\Stores_Meta_Box();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box loaded successfully");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Stores Meta Box file not found: {$stores_meta_box_file}");
            }
        }
    }
    
    /**
     * Регистрира parfume post type
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => _x('Парфюми', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Парфюм', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюми', 'Admin Menu text', 'parfume-reviews'),
            'name_admin_bar' => _x('Парфюм', 'Add New on Toolbar', 'parfume-reviews'),
            'add_new' => __('Добави нов', 'parfume-reviews'),
            'add_new_item' => __('Добави нов парфюм', 'parfume-reviews'),
            'new_item' => __('Нов парфюм', 'parfume-reviews'),
            'edit_item' => __('Редактирай парфюм', 'parfume-reviews'),
            'view_item' => __('Виж парфюм', 'parfume-reviews'),
            'all_items' => __('Всички парфюми', 'parfume-reviews'),
            'search_items' => __('Търси парфюми', 'parfume-reviews'),
            'parent_item_colon' => __('Родителски парфюми:', 'parfume-reviews'),
            'not_found' => __('Няма намерени парфюми.', 'parfume-reviews'),
            'not_found_in_trash' => __('Няма намерени парфюми в кошчето.', 'parfume-reviews'),
            'featured_image' => _x('Изображение на парфюма', 'Overrides the "Featured Image" phrase', 'parfume-reviews'),
            'set_featured_image' => _x('Задай изображение на парфюма', 'Overrides the "Set featured image" phrase', 'parfume-reviews'),
            'remove_featured_image' => _x('Премахни изображението на парфюма', 'Overrides the "Remove featured image" phrase', 'parfume-reviews'),
            'use_featured_image' => _x('Използвай като изображение на парфюма', 'Overrides the "Use as featured image" phrase', 'parfume-reviews'),
            'archives' => _x('Архиви на парфюми', 'The post type archive label used in nav menus', 'parfume-reviews'),
            'insert_into_item' => _x('Вмъкни в парфюм', 'Overrides the "Insert into post"/"Insert into page" phrase', 'parfume-reviews'),
            'uploaded_to_this_item' => _x('Качено към този парфюм', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'parfume-reviews'),
            'filter_items_list' => _x('Филтрирай списъка с парфюми', 'Screen reader text for the filter links', 'parfume-reviews'),
            'items_list_navigation' => _x('Навигация в списъка с парфюми', 'Screen reader text for the pagination', 'parfume-reviews'),
            'items_list' => _x('Списък с парфюми', 'Screen reader text for the items list', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $parfume_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-awards',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author'),
            'show_in_rest' => true,
            'taxonomies' => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        $labels = array(
            'name' => _x('Парфюм блог', 'Post type general name', 'parfume-reviews'),
            'singular_name' => _x('Блог пост', 'Post type singular name', 'parfume-reviews'),
            'menu_name' => _x('Парфюм блог', 'Admin Menu text', 'parfume-reviews'),
            'add_new' => __('Добави нов пост', 'parfume-reviews'),
            'add_new_item' => __('Добави нов блог пост', 'parfume-reviews'),
            'edit_item' => __('Редактирай блог пост', 'parfume-reviews'),
            'view_item' => __('Виж блог пост', 'parfume-reviews'),
            'all_items' => __('Всички блог постове', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'query_var' => true,
            'rewrite' => array('slug' => $blog_slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'show_in_rest' => true,
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi/blog';
        
        // Rewrite rules за post types
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Enqueue scripts и styles
     */
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || 
            is_singular('parfume_blog') || is_post_type_archive('parfume_blog') ||
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Enqueue "Колона 2" assets
            wp_enqueue_style(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/column2.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-column2',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/column2.js',
                array('jquery', 'parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Локализация за frontend scripts
            wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_frontend_nonce')
            ));
            
            // Локализация за "Колона 2"
            $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
            wp_localize_script('parfume-reviews-column2', 'parfumeColumn2', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_column2_nonce'),
                'mobile_settings' => $mobile_settings,
                'strings' => array(
                    'copied' => __('Копирано!', 'parfume-reviews'),
                    'copy_failed' => __('Неуспешно копиране', 'parfume-reviews'),
                    'loading' => __('Зареждане...', 'parfume-reviews')
                )
            ));
        }
    }
    
    /**
     * Зарежда template файлове
     */
    public function load_templates($template) {
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавя body classes
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
            $classes[] = 'parfume-reviews-page';
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            $classes[] = 'parfume-reviews-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            }
        }
        
        return $classes;
    }
    
    /**
     * Добавя общи meta boxes (оригинални)
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            array($this, 'render_parfume_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-reviews'),
            array($this, 'render_parfume_additional_info_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира детайли meta box
     */
    public function render_parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_meta_box', 'parfume_details_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_price', true);
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $concentration = get_post_meta($post->ID, '_concentration', true);
        $bottle_size = get_post_meta($post->ID, '_bottle_size', true);
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Цена', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" />
                    <p class="description"><?php _e('Цената на парфюма (например: 89.99 лв)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="release_year"><?php _e('Година на издаване', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" 
                           min="1900" max="<?php echo date('Y'); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="concentration"><?php _e('Концентрация', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="concentration" name="concentration">
                        <option value=""><?php _e('Избери концентрация', 'parfume-reviews'); ?></option>
                        <option value="EDT" <?php selected($concentration, 'EDT'); ?>><?php _e('EDT (Eau de Toilette)', 'parfume-reviews'); ?></option>
                        <option value="EDP" <?php selected($concentration, 'EDP'); ?>><?php _e('EDP (Eau de Parfum)', 'parfume-reviews'); ?></option>
                        <option value="EDC" <?php selected($concentration, 'EDC'); ?>><?php _e('EDC (Eau de Cologne)', 'parfume-reviews'); ?></option>
                        <option value="Parfum" <?php selected($concentration, 'Parfum'); ?>><?php _e('Parfum', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bottle_size"><?php _e('Размер на бутилката', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <input type="text" id="bottle_size" name="bottle_size" value="<?php echo esc_attr($bottle_size); ?>" class="regular-text" />
                    <p class="description"><?php _e('Например: 50ml, 100ml, 150ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="longevity"><?php _e('Издръжливост', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="longevity" name="longevity">
                        <option value=""><?php _e('Избери издръжливост', 'parfume-reviews'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаба (1-2 часа)', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерена (3-5 часа)', 'parfume-reviews'); ?></option>
                        <option value="long" <?php selected($longevity, 'long'); ?>><?php _e('Дълга (6-8 часа)', 'parfume-reviews'); ?></option>
                        <option value="very_long" <?php selected($longevity, 'very_long'); ?>><?php _e('Много дълга (8+ часа)', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sillage"><?php _e('Силаж', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <select id="sillage" name="sillage">
                        <option value=""><?php _e('Избери силаж', 'parfume-reviews'); ?></option>
                        <option value="intimate" <?php selected($sillage, 'intimate'); ?>><?php _e('Интимен', 'parfume-reviews'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерен', 'parfume-reviews'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силен', 'parfume-reviews'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромен', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Рендерира рейтинг meta box
     */
    public function render_parfume_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_meta_box', 'parfume_rating_meta_box_nonce');
        
        $rating = get_post_meta($post->ID, '_rating', true);
        ?>
        <p>
            <label for="rating"><?php _e('Рейтинг (1-10)', 'parfume-reviews'); ?></label>
            <input type="number" id="rating" name="rating" value="<?php echo esc_attr($rating); ?>" 
                   min="1" max="10" step="0.1" class="small-text" />
        </p>
        <p class="description">
            <?php _e('Въведете рейтинг от 1 до 10 (може да използвате десетични числа като 8.5)', 'parfume-reviews'); ?>
        </p>
        <?php
    }
    
    /**
     * Рендерира допълнителна информация meta box
     */
    public function render_parfume_additional_info_meta_box($post) {
        wp_nonce_field('parfume_additional_info_meta_box', 'parfume_additional_info_meta_box_nonce');
        
        $pros = get_post_meta($post->ID, '_pros', true);
        $cons = get_post_meta($post->ID, '_cons', true);
        $occasions = get_post_meta($post->ID, '_occasions', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pros"><?php _e('Предимства', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="pros" name="pros" rows="4" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('Положителните страни на парфюма (всеки ред е отделно предимство)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cons"><?php _e('Недостатъци', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="cons" name="cons" rows="4" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('Отрицателните страни на парфюма (всеки ред е отделен недостатък)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="occasions"><?php _e('Подходящи случаи', 'parfume-reviews'); ?></label>
                </th>
                <td>
                    <textarea id="occasions" name="occasions" rows="3" class="large-text"><?php echo esc_textarea($occasions); ?></textarea>
                    <p class="description"><?php _e('Кога е подходящо да се носи този парфюм', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Записва общи meta box данни
     */
    public function save_general_meta_boxes($post_id) {
        // Проверки за сигурност
        if (!isset($_POST['parfume_details_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_details_meta_box_nonce'], 'parfume_details_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Записваме полетата
        $fields = array(
            'price' => 'sanitize_text_field',
            'release_year' => 'intval',
            'concentration' => 'sanitize_text_field',
            'bottle_size' => 'sanitize_text_field',
            'longevity' => 'sanitize_text_field',
            'sillage' => 'sanitize_text_field',
            'rating' => 'floatval',
            'pros' => 'sanitize_textarea_field',
            'cons' => 'sanitize_textarea_field',
            'occasions' => 'sanitize_textarea_field'
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * AJAX handlers (оригинални)
     */
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        $new_price = sanitize_text_field($_POST['new_price']);
        
        // Тук може да добавим логика за обновяване на цени
        wp_send_json_success(array('message' => __('Цената е обновена.', 'parfume-reviews')));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        
        // Логика за получаване на размери
        wp_send_json_success(array('sizes' => array('50ml', '100ml', '150ml')));
    }
    
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Получаваме scraped данни за този store
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (is_array($stores_data) && isset($stores_data[$store_id]['scraped_data']['variants'])) {
            wp_send_json_success(array('variants' => $stores_data[$store_id]['scraped_data']['variants']));
        } else {
            wp_send_json_error(array('message' => __('Няма данни за варианти.', 'parfume-reviews')));
        }
    }
    
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Тук може да извикаме scraper за обновяване на данните
        wp_send_json_success(array('message' => __('Данните са обновени.', 'parfume-reviews')));
    }
    
    public function ajax_compare_prices() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        $comparison_data = array();
        foreach ($post_ids as $post_id) {
            $stores_data = get_post_meta($post_id, '_parfume_stores', true);
            if (is_array($stores_data)) {
                $comparison_data[$post_id] = $stores_data;
            }
        }
        
        wp_send_json_success(array('comparison' => $comparison_data));
    }
    
    /**
     * Получава stores meta box instance
     */
    public function get_stores_meta_box() {
        return $this->stores_meta_box;
    }
    
    /**
     * Проверява дали stores meta box е зареден
     */
    public function has_stores_meta_box() {
        return !is_null($this->stores_meta_box);
    }
}