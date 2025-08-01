<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * РЕВИЗИРАНА ВЕРСИЯ: Поправени липсващи функции и зависимости
 * 
 * Файл: includes/class-post-type.php
 */
class Post_Type {
    
    /**
     * Instance на Query_Handler и Stores Meta Box
     */
    private $query_handler;
    private $stores_meta_box;
    private $template_loader;
    
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
        
        // Зареждаме компоненти
        $this->load_components();
        
        // Оригинални meta boxes
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // Всички AJAX handlers
        $this->register_ajax_handlers();
    }
    
    /**
     * Зарежда всички компоненти
     */
    private function load_components() {
        // Зареждаме Template Loader
        $this->load_template_loader();
        
        // Зареждаме Stores Meta Box
        $this->load_stores_meta_box();
        
        // Зареждаме Query Handler
        $this->load_query_handler();
    }
    
    /**
     * Зарежда Template Loader компонента
     */
    private function load_template_loader() {
        $template_loader_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-template-loader.php';
        
        if (file_exists($template_loader_file)) {
            require_once $template_loader_file;
            
            if (class_exists('Parfume_Reviews\\Post_Type\\Template_Loader')) {
                $this->template_loader = new Post_Type\Template_Loader();
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Template_Loader component loaded successfully");
                }
            }
        } else {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Template_Loader file missing: $template_loader_file", 'error');
            }
        }
    }
    
    /**
     * Зарежда Stores Meta Box компонента
     */
    private function load_stores_meta_box() {
        $stores_meta_box_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-stores-meta-box.php';
        
        if (file_exists($stores_meta_box_file)) {
            require_once $stores_meta_box_file;
            
            if (class_exists('Parfume_Reviews\\Post_Type\\Stores_Meta_Box')) {
                $this->stores_meta_box = new Post_Type\Stores_Meta_Box();
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Stores_Meta_Box component loaded successfully");
                }
            }
        } else {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Stores_Meta_Box file missing: $stores_meta_box_file", 'warning');
            }
            
            // Fallback - добавяме основния stores meta box
            add_action('add_meta_boxes', array($this, 'add_fallback_stores_meta_box'));
        }
    }
    
    /**
     * Зарежда Query Handler компонента
     */
    private function load_query_handler() {
        $query_handler_file = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-query-handler.php';
        
        if (file_exists($query_handler_file)) {
            require_once $query_handler_file;
            
            if (class_exists('Parfume_Reviews\\Post_Type\\Query_Handler')) {
                $this->query_handler = new Post_Type\Query_Handler();
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Query_Handler component loaded successfully");
                }
            }
        } else {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Query_Handler file missing: $query_handler_file", 'warning');
            }
        }
    }
    
    /**
     * Регистрира всички AJAX handlers
     */
    private function register_ajax_handlers() {
        // Оригинални AJAX handlers
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        
        // Нови AJAX handlers за scraper
        add_action('wp_ajax_parfume_test_scraper', array($this, 'ajax_test_scraper'));
        add_action('wp_ajax_parfume_bulk_scrape', array($this, 'ajax_bulk_scrape'));
        add_action('wp_ajax_parfume_schedule_scrape', array($this, 'ajax_schedule_scrape'));
    }
    
    /**
     * Регистрира parfume post type
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $rewrite_slug = $parfume_slug;
        
        $labels = array(
            'name'                  => _x('Парфюми', 'Post type general name', 'parfume-reviews'),
            'singular_name'         => _x('Парфюм', 'Post type singular name', 'parfume-reviews'),
            'menu_name'             => _x('Парфюми', 'Admin Menu text', 'parfume-reviews'),
            'name_admin_bar'        => _x('Парфюм', 'Add New on Toolbar', 'parfume-reviews'),
            'add_new'               => __('Добави нов', 'parfume-reviews'),
            'add_new_item'          => __('Добави нов парфюм', 'parfume-reviews'),
            'new_item'              => __('Нов парфюм', 'parfume-reviews'),
            'edit_item'             => __('Редактирай парфюм', 'parfume-reviews'),
            'view_item'             => __('Виж парфюм', 'parfume-reviews'),
            'all_items'             => __('Всички парфюми', 'parfume-reviews'),
            'search_items'          => __('Търси парфюми', 'parfume-reviews'),
            'parent_item_colon'     => __('Родителски парфюми:', 'parfume-reviews'),
            'not_found'             => __('Не са намерени парфюми.', 'parfume-reviews'),
            'not_found_in_trash'    => __('Не са намерени парфюми в кошчето.', 'parfume-reviews'),
            'featured_image'        => _x('Снимка на парфюма', 'Overrides the "Featured Image" phrase', 'parfume-reviews'),
            'set_featured_image'    => _x('Постави снимка на парфюма', 'Overrides the "Set featured image" phrase', 'parfume-reviews'),
            'remove_featured_image' => _x('Премахни снимката на парфюма', 'Overrides the "Remove featured image" phrase', 'parfume-reviews'),
            'use_featured_image'    => _x('Използвай като снимка на парфюма', 'Overrides the "Use as featured image" phrase', 'parfume-reviews'),
            'archives'              => _x('Архиви на парфюми', 'The post type archive label used in nav menus', 'parfume-reviews'),
            'insert_into_item'      => _x('Вмъкни в парфюм', 'Overrides the "Insert into post" phrase', 'parfume-reviews'),
            'uploaded_to_this_item' => _x('Качено към този парфюм', 'Overrides the "Uploaded to this post" phrase', 'parfume-reviews'),
            'filter_items_list'     => _x('Филтрирай списъка с парфюми', 'Screen reader text for the filter links', 'parfume-reviews'),
            'items_list_navigation' => _x('Навигация в списъка с парфюми', 'Screen reader text for the pagination', 'parfume-reviews'),
            'items_list'            => _x('Списък с парфюми', 'Screen reader text for the items list', 'parfume-reviews'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug' => $rewrite_slug,
                'with_front' => false
            ),
            'capability_type'    => 'post',
            'has_archive'        => $parfume_slug,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-format-image',
            'show_in_rest'       => true,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
            'taxonomies'         => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Parfume post type registered with slug: $rewrite_slug");
        }
    }
    
    /**
     * Регистрира parfume_blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
        
        $labels = array(
            'name'                  => _x('Парфюм Блог', 'Post type general name', 'parfume-reviews'),
            'singular_name'         => _x('Блог пост', 'Post type singular name', 'parfume-reviews'),
            'menu_name'             => _x('Парфюм Блог', 'Admin Menu text', 'parfume-reviews'),
            'name_admin_bar'        => _x('Блог пост', 'Add New on Toolbar', 'parfume-reviews'),
            'add_new'               => __('Добави нов пост', 'parfume-reviews'),
            'add_new_item'          => __('Добави нов блог пост', 'parfume-reviews'),
            'new_item'              => __('Нов блог пост', 'parfume-reviews'),
            'edit_item'             => __('Редактирай блог пост', 'parfume-reviews'),
            'view_item'             => __('Виж блог пост', 'parfume-reviews'),
            'all_items'             => __('Всички блог постове', 'parfume-reviews'),
            'search_items'          => __('Търси блог постове', 'parfume-reviews'),
            'not_found'             => __('Не са намерени блог постове.', 'parfume-reviews'),
            'not_found_in_trash'    => __('Не са намерени блог постове в кошчето.', 'parfume-reviews'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=parfume',
            'query_var'          => true,
            'rewrite'            => array(
                'slug' => $blog_slug,
                'with_front' => false
            ),
            'capability_type'    => 'post',
            'has_archive'        => $blog_slug,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
        );
        
        register_post_type('parfume_blog', $args);
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Parfume blog post type registered with slug: $blog_slug");
        }
    }
    
    /**
     * Добавя custom rewrite rules
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
        
        // Rewrite rules за parfume post type
        add_rewrite_rule(
            "^{$parfume_slug}/([^/]+)/?$",
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
        
        // Rewrite rules за parfume_blog post type
        add_rewrite_rule(
            "^{$blog_slug}/([^/]+)/?$",
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
        
        // Rewrite rules за taxonomy архиви
        $taxonomies = array(
            'marki' => 'marki',
            'gender' => 'gender',
            'aroma_type' => 'aroma-type',
            'season' => 'season',
            'intensity' => 'intensity',
            'notes' => 'notes',
            'perfumer' => 'parfumeri'
        );
        
        foreach ($taxonomies as $taxonomy => $slug) {
            add_rewrite_rule(
                "^{$parfume_slug}/{$slug}/([^/]+)/?$",
                "index.php?{$taxonomy}=\$matches[1]",
                'top'
            );
            
            add_rewrite_rule(
                "^{$parfume_slug}/{$slug}/?$",
                "index.php?post_type=parfume&{$taxonomy}=",
                'top'
            );
        }
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Custom rewrite rules added for parfume and blog post types");
        }
    }
    
    /**
     * Custom permalink structure
     */
    public function custom_post_type_link($post_link, $post) {
        if ($post->post_type !== 'parfume' && $post->post_type !== 'parfume_blog') {
            return $post_link;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        
        if ($post->post_type === 'parfume') {
            $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
            return home_url("/{$parfume_slug}/{$post->post_name}/");
        } elseif ($post->post_type === 'parfume_blog') {
            $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
            return home_url("/{$blog_slug}/{$post->post_name}/");
        }
        
        return $post_link;
    }
    
    /**
     * Добавя query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_filter';
        $vars[] = 'parfume_sort';
        $vars[] = 'parfume_search';
        
        return $vars;
    }
    
    /**
     * Parse request за custom queries
     */
    public function parse_request($wp) {
        // Delegate to Query Handler if available
        if ($this->query_handler && method_exists($this->query_handler, 'parse_request')) {
            return $this->query_handler->parse_request($wp);
        }
        
        // Fallback basic parsing
        if (isset($wp->query_vars['parfume_filter'])) {
            // Basic filter parsing
            $filters = sanitize_text_field($wp->query_vars['parfume_filter']);
            // Process filters...
        }
        
        return $wp;
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (!is_singular('parfume') && !is_post_type_archive('parfume') && !is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            return;
        }
        
        // Main frontend CSS
        wp_enqueue_style(
            'parfume-reviews-frontend',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        // Main frontend JS
        wp_enqueue_script(
            'parfume-reviews-frontend',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Single parfume specific assets
        if (is_singular('parfume')) {
            // Single parfume CSS - check if file exists
            $single_parfume_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/single-parfume.css';
            if (file_exists($single_parfume_css)) {
                wp_enqueue_style(
                    'parfume-reviews-single-parfume',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                    array('parfume-reviews-frontend'),
                    PARFUME_REVIEWS_VERSION
                );
            }
            
            // Column 2 assets (for stores)
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
        
        // Локализация за frontend scripts
        wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_frontend_nonce')
        ));
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Frontend scripts enqueued for parfume page");
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
                
                // Main admin script
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
                        'confirm_delete' => __('Сигурни ли сте, че искате да изтриете този елемент?', 'parfume-reviews'),
                        'loading' => __('Зареждане...', 'parfume-reviews'),
                        'error' => __('Възникна грешка', 'parfume-reviews'),
                        'success' => __('Успешно!', 'parfume-reviews')
                    )
                ));
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Admin scripts enqueued for parfume post type");
                }
            }
        }
    }
    
    /**
     * Зарежда template файлове
     */
    public function load_templates($template) {
        // Delegate to Template Loader if available
        if ($this->template_loader && method_exists($this->template_loader, 'load_template')) {
            $custom_template = $this->template_loader->load_template($template);
            if ($custom_template) {
                return $custom_template;
            }
        }
        
        // Fallback template loading
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
     * Добавя general meta boxes
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
            __('Рейтинг и оценка', 'parfume-reviews'),
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
     * Render parfume details meta box
     */
    public function render_parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_meta_box', 'parfume_details_nonce');
        
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        $bottle_size = get_post_meta($post->ID, '_bottle_size', true);
        $price = get_post_meta($post->ID, '_price', true);
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th><label for="release_year">' . __('Година на излизане', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="number" id="release_year" name="release_year" value="' . esc_attr($release_year) . '" min="1900" max="' . date('Y') . '" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="longevity">' . __('Издръжливост', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="longevity" name="longevity" value="' . esc_attr($longevity) . '" placeholder="напр. 6-8 часа" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="sillage">' . __('Силаж', 'parfume-reviews') . '</label></th>';
        echo '<td>';
        echo '<select id="sillage" name="sillage">';
        echo '<option value="">' . __('Изберете силаж', 'parfume-reviews') . '</option>';
        
        $sillage_options = array(
            'слаб' => __('Слаб', 'parfume-reviews'),
            'умерен' => __('Умерен', 'parfume-reviews'),
            'силен' => __('Силен', 'parfume-reviews'),
            'много силен' => __('Много силен', 'parfume-reviews')
        );
        
        foreach ($sillage_options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($sillage, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="bottle_size">' . __('Размер на бутилката', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="bottle_size" name="bottle_size" value="' . esc_attr($bottle_size) . '" placeholder="напр. 50ml, 100ml" /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="price">' . __('Цена (лв.)', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="number" id="price" name="price" value="' . esc_attr($price) . '" min="0" step="0.01" /></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * Render parfume rating meta box
     */
    public function render_parfume_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_meta_box', 'parfume_rating_nonce');
        
        $rating = get_post_meta($post->ID, '_rating', true);
        
        echo '<p>';
        echo '<label for="rating"><strong>' . __('Рейтинг (0-10)', 'parfume-reviews') . '</strong></label><br>';
        echo '<input type="number" id="rating" name="rating" value="' . esc_attr($rating) . '" min="0" max="10" step="0.1" style="width: 100%;" />';
        echo '</p>';
        
        echo '<div class="rating-preview" style="margin-top: 10px;">';
        echo '<strong>' . __('Визуализация:', 'parfume-reviews') . '</strong><br>';
        echo '<div class="stars-display" id="stars-preview">';
        for ($i = 1; $i <= 10; $i++) {
            echo '<span class="star" data-rating="' . $i . '">★</span>';
        }
        echo '</div>';
        echo '</div>';
        
        // Add inline JavaScript for rating preview
        echo '<script>
        jQuery(document).ready(function($) {
            function updateStarsPreview() {
                var rating = parseFloat($("#rating").val()) || 0;
                $("#stars-preview .star").each(function(index) {
                    var starValue = index + 1;
                    if (starValue <= rating) {
                        $(this).addClass("filled");
                    } else {
                        $(this).removeClass("filled");
                    }
                });
            }
            
            $("#rating").on("input change", updateStarsPreview);
            updateStarsPreview(); // Initial update
        });
        </script>';
        
        echo '<style>
        .stars-display .star {
            font-size: 16px;
            color: #ddd;
            cursor: pointer;
        }
        .stars-display .star.filled {
            color: #FFD700;
        }
        </style>';
    }
    
    /**
     * Render parfume additional info meta box
     */
    public function render_parfume_additional_info_meta_box($post) {
        wp_nonce_field('parfume_additional_info_meta_box', 'parfume_additional_info_nonce');
        
        $pros = get_post_meta($post->ID, '_pros', true);
        $cons = get_post_meta($post->ID, '_cons', true);
        $aroma_chart = get_post_meta($post->ID, '_aroma_chart', true);
        
        if (!is_array($aroma_chart)) {
            $aroma_chart = array(
                'freshness' => 5,
                'sweetness' => 5,
                'intensity' => 5,
                'warmth' => 5
            );
        }
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th><label for="pros">' . __('Предимства', 'parfume-reviews') . '</label></th>';
        echo '<td><textarea id="pros" name="pros" rows="4" style="width: 100%;">' . esc_textarea($pros) . '</textarea>';
        echo '<p class="description">' . __('Всяко предимство на нов ред', 'parfume-reviews') . '</p></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="cons">' . __('Недостатъци', 'parfume-reviews') . '</label></th>';
        echo '<td><textarea id="cons" name="cons" rows="4" style="width: 100%;">' . esc_textarea($cons) . '</textarea>';
        echo '<p class="description">' . __('Всеки недостатък на нов ред', 'parfume-reviews') . '</p></td>';
        echo '</tr>';
        
        echo '</table>';
        
        echo '<h4>' . __('Графика на аромата', 'parfume-reviews') . '</h4>';
        echo '<table class="form-table">';
        
        $chart_fields = array(
            'freshness' => __('Свежест', 'parfume-reviews'),
            'sweetness' => __('Сладост', 'parfume-reviews'),
            'intensity' => __('Интензивност', 'parfume-reviews'),
            'warmth' => __('Топлота', 'parfume-reviews')
        );
        
        foreach ($chart_fields as $field => $label) {
            $value = isset($aroma_chart[$field]) ? $aroma_chart[$field] : 5;
            
            echo '<tr>';
            echo '<th><label for="aroma_chart_' . $field . '">' . $label . '</label></th>';
            echo '<td>';
            echo '<input type="range" id="aroma_chart_' . $field . '" name="aroma_chart[' . $field . ']" value="' . esc_attr($value) . '" min="0" max="10" step="1" style="width: 100%;" />';
            echo '<output for="aroma_chart_' . $field . '" id="output_' . $field . '">' . $value . '</output>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Add JavaScript for range sliders
        echo '<script>
        jQuery(document).ready(function($) {
            $("input[type=range]").on("input", function() {
                var output = $("#output_" + this.name.split("[")[1].split("]")[0]);
                output.text(this.value);
            });
        });
        </script>';
    }
    
    /**
     * Fallback stores meta box ако компонентът липсва
     */
    public function add_fallback_stores_meta_box() {
        add_meta_box(
            'parfume_stores_fallback',
            __('Магазини (Основен режим)', 'parfume-reviews'),
            array($this, 'render_fallback_stores_meta_box'),
            'parfume',
            'normal',
            'high'
        );
    }
    
    /**
     * Render fallback stores meta box
     */
    public function render_fallback_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_fallback', 'parfume_stores_fallback_nonce');
        
        $stores_data = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            $stores_data = array();
        }
        
        echo '<div class="fallback-stores-meta-box">';
        echo '<p><em>' . __('Основен режим за управление на магазини. За пълната функционалност инсталирайте компонента Stores_Meta_Box.', 'parfume-reviews') . '</em></p>';
        
        echo '<label for="parfume_stores_json"><strong>' . __('Stores данни (JSON формат):', 'parfume-reviews') . '</strong></label>';
        echo '<textarea id="parfume_stores_json" name="parfume_stores_json" rows="10" style="width: 100%; font-family: monospace;">';
        echo esc_textarea(json_encode($stores_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo '</textarea>';
        
        echo '<p class="description">' . __('Въведете stores данните в JSON формат. Примерна структура:', 'parfume-reviews') . '</p>';
        
        $example = array(
            array(
                'store_id' => 'store1',
                'product_url' => 'https://example.com/product',
                'affiliate_url' => 'https://affiliate.example.com/link',
                'promo_code' => 'DISCOUNT10'
            )
        );
        
        echo '<pre style="background: #f1f1f1; padding: 10px; font-size: 12px;">';
        echo esc_html(json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo '</pre>';
        
        echo '</div>';
    }
    
    /**
     * Save general meta boxes
     */
    public function save_general_meta_boxes($post_id) {
        // Verify nonces
        if (!isset($_POST['parfume_details_nonce']) || 
            !wp_verify_nonce($_POST['parfume_details_nonce'], 'parfume_details_meta_box')) {
            return;
        }
        
        if (!isset($_POST['parfume_rating_nonce']) || 
            !wp_verify_nonce($_POST['parfume_rating_nonce'], 'parfume_rating_meta_box')) {
            return;
        }
        
        if (!isset($_POST['parfume_additional_info_nonce']) || 
            !wp_verify_nonce($_POST['parfume_additional_info_nonce'], 'parfume_additional_info_meta_box')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Skip for autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save details fields
        $details_fields = array('release_year', 'longevity', 'sillage', 'bottle_size', 'price');
        foreach ($details_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save rating
        if (isset($_POST['rating'])) {
            $rating = floatval($_POST['rating']);
            $rating = max(0, min(10, $rating)); // Ensure rating is between 0 and 10
            update_post_meta($post_id, '_rating', $rating);
        }
        
        // Save additional info fields
        if (isset($_POST['pros'])) {
            update_post_meta($post_id, '_pros', sanitize_textarea_field($_POST['pros']));
        }
        
        if (isset($_POST['cons'])) {
            update_post_meta($post_id, '_cons', sanitize_textarea_field($_POST['cons']));
        }
        
        // Save aroma chart
        if (isset($_POST['aroma_chart']) && is_array($_POST['aroma_chart'])) {
            $aroma_chart = array();
            $allowed_fields = array('freshness', 'sweetness', 'intensity', 'warmth');
            
            foreach ($allowed_fields as $field) {
                if (isset($_POST['aroma_chart'][$field])) {
                    $value = intval($_POST['aroma_chart'][$field]);
                    $aroma_chart[$field] = max(0, min(10, $value)); // Ensure value is between 0 and 10
                }
            }
            
            update_post_meta($post_id, '_aroma_chart', $aroma_chart);
        }
        
        // Save fallback stores data if present
        if (isset($_POST['parfume_stores_fallback_nonce']) && 
            wp_verify_nonce($_POST['parfume_stores_fallback_nonce'], 'parfume_stores_fallback') &&
            isset($_POST['parfume_stores_json'])) {
            
            $stores_json = stripslashes($_POST['parfume_stores_json']);
            $stores_data = json_decode($stores_json, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($stores_data)) {
                update_post_meta($post_id, '_parfume_stores', $stores_data);
            }
        }
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Meta boxes saved for post ID: $post_id");
        }
    }
    
    // ===== AJAX HANDLERS =====
    
    /**
     * AJAX handler за update store price
     */
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        $new_price = sanitize_text_field($_POST['new_price']);
        
        // Simulate price update
        $result = array(
            'success' => true,
            'new_price' => $new_price,
            'store_id' => $store_id,
            'timestamp' => current_time('mysql')
        );
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler за get store sizes
     */
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Mock data - в реалната имплементация тук би имало scraping логика
        $sizes = array(
            array('size' => '30ml', 'price' => '45.90 лв', 'available' => true),
            array('size' => '50ml', 'price' => '65.90 лв', 'available' => true),
            array('size' => '100ml', 'price' => '89.90 лв', 'available' => false)
        );
        
        wp_send_json_success($sizes);
    }
    
    /**
     * AJAX handler за get store variants
     */
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $post_id = intval($_POST['post_id']);
        
        // Mock варианти
        $variants = array(
            array(
                'size' => '50ml',
                'price' => '89.90 лв',
                'old_price' => '99.90 лв',
                'discount' => 10,
                'available' => true,
                'shipping' => '4.99 лв'
            ),
            array(
                'size' => '100ml',
                'price' => '129.90 лв',
                'old_price' => '',
                'discount' => 0,
                'available' => true,
                'shipping' => 'Безплатна'
            )
        );
        
        wp_send_json_success($variants);
    }
    
    /**
     * AJAX handler за refresh store data
     */
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_column2_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $post_id = intval($_POST['post_id']);
        
        // Mock обновени данни
        $updated_data = array(
            'availability' => 'В наличност',
            'availability_status' => 'in_stock',
            'prices' => array(
                array(
                    'size' => '50ml',
                    'current_price' => '85.90 лв',
                    'old_price' => '89.90 лв',
                    'discount' => 5
                ),
                array(
                    'size' => '100ml',
                    'current_price' => '125.90 лв',
                    'old_price' => '129.90 лв',
                    'discount' => 3
                )
            ),
            'last_updated' => current_time('Y-m-d H:i:s')
        );
        
        wp_send_json_success($updated_data);
    }
    
    /**
     * AJAX handler за compare prices
     */
    public function ajax_compare_prices() {
        check_ajax_referer('parfume_frontend_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        // Mock сравнение на цени
        $comparison = array(
            'lowest_price' => array(
                'store' => 'Магазин А',
                'price' => '79.90 лв',
                'size' => '50ml',
                'url' => 'https://example.com/product'
            ),
            'highest_price' => array(
                'store' => 'Магазин В',
                'price' => '95.90 лв',
                'size' => '50ml',
                'url' => 'https://example-b.com/product'
            ),
            'average_price' => '87.90 лв',
            'stores_count' => 3
        );
        
        wp_send_json_success($comparison);
    }
    
    /**
     * AJAX handler за test scraper
     */
    public function ajax_test_scraper() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_url = esc_url_raw($_POST['test_url']);
        
        if (empty($test_url)) {
            wp_send_json_error('URL е задължителен');
        }
        
        // Mock scraper тест
        $test_result = array(
            'success' => true,
            'url' => $test_url,
            'title' => 'Тестов продукт',
            'prices' => array(
                array('size' => '50ml', 'price' => '89.90 лв'),
                array('size' => '100ml', 'price' => '129.90 лв')
            ),
            'availability' => 'В наличност',
            'response_time' => '1.2s',
            'timestamp' => current_time('Y-m-d H:i:s')
        );
        
        wp_send_json_success($test_result);
    }
    
    /**
     * AJAX handler за bulk scrape
     */
    public function ajax_bulk_scrape() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Mock bulk scrape
        $batch_result = array(
            'processed' => 5,
            'total' => 25,
            'completed' => false,
            'current_batch' => 1,
            'errors' => array(),
            'updated_posts' => array(123, 124, 125, 126, 127)
        );
        
        wp_send_json_success($batch_result);
    }
    
    /**
     * AJAX handler за schedule scrape
     */
    public function ajax_schedule_scrape() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $frequency = sanitize_text_field($_POST['frequency']);
        
        // Mock schedule
        $schedule_result = array(
            'frequency' => $frequency,
            'next_run' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'status' => 'scheduled'
        );
        
        wp_send_json_success($schedule_result);
    }
    
    // ===== UTILITY METHODS =====
    
    /**
     * Debug query info
     */
    public function debug_query_info() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_query;
        
        $debug_info = array(
            'is_parfume_page' => function_exists('parfume_reviews_is_parfume_page') ? parfume_reviews_is_parfume_page() : 'unknown',
            'is_singular_parfume' => is_singular('parfume'),
            'is_archive_parfume' => is_post_type_archive('parfume'),
            'is_tax' => is_tax(),
            'queried_object' => get_queried_object(),
            'query_vars' => $wp_query->query_vars
        );
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Query debug info: " . print_r($debug_info, true));
        }
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
        return $this->stores_meta_box !== null;
    }
    
    /**
     * Получава query handler instance
     */
    public function get_query_handler() {
        return $this->query_handler;
    }
    
    /**
     * Получава template loader instance
     */
    public function get_template_loader() {
        return $this->template_loader;
    }
}