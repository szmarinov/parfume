<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * 
 * Файл: includes/class-post-type.php
 * РЕВИЗИРАНА ВЕРСИЯ - ПОДОБРЕНА СТРУКТУРА И НОВИ ФУНКЦИОНАЛНОСТИ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Type класа
 * ВАЖНО: Управлява parfume и parfume_blog post types с всички свързани функционалности
 */
class Post_Type {
    
    /**
     * @var Post_Type\Meta_Boxes|null
     * ВАЖНО: Управлява meta boxes за parfume posts
     */
    private $meta_boxes;
    
    /**
     * @var Post_Type\Query_Handler|null
     * ВАЖНО: Управлява query модификации и филтриране
     */
    private $query_handler;
    
    /**
     * @var Post_Type\Template_Loader|null
     * ВАЖНО: Управлява зареждането на template файлове
     */
    private $template_loader;
    
    /**
     * Constructor
     * ВАЖНО: Инициализира всички компоненти и hook-ове
     */
    public function __construct() {
        $this->init_components();
        $this->register_hooks();
    }
    
    /**
     * Инициализира всички компоненти
     * ВАЖНО: Зарежда модулните класове ако съществуват
     */
    private function init_components() {
        // Зареждаме Meta Boxes компонента ако съществува
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-meta-boxes.php')) {
            require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-meta-boxes.php';
            if (class_exists('Parfume_Reviews\\Post_Type\\Meta_Boxes')) {
                $this->meta_boxes = new \Parfume_Reviews\Post_Type\Meta_Boxes();
            }
        }
        
        // Зареждаме Query Handler компонента ако съществува
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-query-handler.php')) {
            require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-query-handler.php';
            if (class_exists('Parfume_Reviews\\Post_Type\\Query_Handler')) {
                $this->query_handler = new \Parfume_Reviews\Post_Type\Query_Handler();
            }
        }
        
        // Зареждаме Template Loader компонента ако съществува
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-template-loader.php')) {
            require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-template-loader.php';
            if (class_exists('Parfume_Reviews\\Post_Type\\Template_Loader')) {
                $this->template_loader = new \Parfume_Reviews\Post_Type\Template_Loader();
            }
        }
        
        // Debug log
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Post Type components initialized");
        }
    }
    
    /**
     * Регистрира всички hook-ове
     * ВАЖНО: Настройва интеграцията с WordPress
     */
    private function register_hooks() {
        // Основни hook-ове
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        
        // Body classes
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Admin customizations
        add_filter('manage_parfume_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_parfume_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-parfume_sortable_columns', array($this, 'make_columns_sortable'));
        
        // Mobile meta box
        add_action('add_meta_boxes', array($this, 'add_mobile_meta_box'));
        add_action('save_post', array($this, 'save_mobile_meta_box'));
        
        // Debug hooks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_query_info'));
            add_action('admin_notices', array($this, 'debug_post_type_info'));
        }
    }
    
    /**
     * РАЗДЕЛ 1: РЕГИСТРАЦИЯ НА POST TYPES
     */
    
    /**
     * Регистрира parfume и parfume_blog post types
     * ВАЖНО: Основната функция за регистрация на post types
     */
    public function register_post_types() {
        $this->register_parfume_post_type();
        $this->register_parfume_blog_post_type();
    }
    
    /**
     * Регистрира parfume post type
     * ВАЖНО: Основният post type за парфюми
     */
    private function register_parfume_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name'                  => _x('Парфюми', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Парфюм', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Парфюми', 'parfume-reviews'),
            'name_admin_bar'       => __('Парфюм', 'parfume-reviews'),
            'archives'             => __('Архив с парфюми', 'parfume-reviews'),
            'attributes'           => __('Атрибути на парфюм', 'parfume-reviews'),
            'parent_item_colon'    => __('Родителски парфюм:', 'parfume-reviews'),
            'all_items'            => __('Всички парфюми', 'parfume-reviews'),
            'add_new_item'         => __('Добави нов парфюм', 'parfume-reviews'),
            'add_new'              => __('Добави нов', 'parfume-reviews'),
            'new_item'             => __('Нов парфюм', 'parfume-reviews'),
            'edit_item'            => __('Редактирай парфюм', 'parfume-reviews'),
            'update_item'          => __('Обнови парфюм', 'parfume-reviews'),
            'view_item'            => __('Прегледай парфюм', 'parfume-reviews'),
            'view_items'           => __('Прегледай парфюми', 'parfume-reviews'),
            'search_items'         => __('Търси парфюми', 'parfume-reviews'),
            'not_found'            => __('Няма намерени парфюми.', 'parfume-reviews'),
            'not_found_in_trash'   => __('Няма намерени парфюми в кошчето.', 'parfume-reviews'),
            'featured_image'       => __('Изображение на парфюм', 'parfume-reviews'),
            'set_featured_image'   => __('Задай изображение на парфюм', 'parfume-reviews'),
            'remove_featured_image' => __('Премахни изображение на парфюм', 'parfume-reviews'),
            'use_featured_image'   => __('Използвай като изображение на парфюм', 'parfume-reviews'),
            'insert_into_item'     => __('Вмъкни в парфюм', 'parfume-reviews'),
            'uploaded_to_this_item' => __('Качено за този парфюм', 'parfume-reviews'),
            'items_list'           => __('Списък с парфюми', 'parfume-reviews'),
            'items_list_navigation' => __('Навигация в списъка с парфюми', 'parfume-reviews'),
            'filter_items_list'    => __('Филтрирай списъка с парфюми', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Парфюм', 'parfume-reviews'),
            'description'          => __('Парфюми за ревю и сравнение', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
            'taxonomies'           => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
            'hierarchical'         => false,
            'public'               => true,
            'show_ui'              => true,
            'show_in_menu'         => true,
            'menu_position'        => 5,
            'menu_icon'            => 'dashicons-art',
            'show_in_admin_bar'    => true,
            'show_in_nav_menus'    => true,
            'can_export'           => true,
            'has_archive'          => true,
            'exclude_from_search'  => false,
            'publicly_queryable'   => true,
            'capability_type'      => 'post',
            'show_in_rest'         => true,
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
     * ВАЖНО: Post type за блог статии за парфюми
     */
    private function register_parfume_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'parfiumi-blog';
        
        $labels = array(
            'name'                  => _x('Парфюмен блог', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Блог статия', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Парфюмен блог', 'parfume-reviews'),
            'name_admin_bar'       => __('Блог статия', 'parfume-reviews'),
            'archives'             => __('Архив на блога', 'parfume-reviews'),
            'attributes'           => __('Атрибути на статия', 'parfume-reviews'),
            'parent_item_colon'    => __('Родителска статия:', 'parfume-reviews'),
            'all_items'            => __('Всички статии', 'parfume-reviews'),
            'add_new_item'         => __('Добави нова статия', 'parfume-reviews'),
            'add_new'              => __('Добави нова', 'parfume-reviews'),
            'new_item'             => __('Нова статия', 'parfume-reviews'),
            'edit_item'            => __('Редактирай статия', 'parfume-reviews'),
            'update_item'          => __('Обнови статия', 'parfume-reviews'),
            'view_item'            => __('Прегледай статия', 'parfume-reviews'),
            'view_items'           => __('Прегледай статии', 'parfume-reviews'),
            'search_items'         => __('Търси статии', 'parfume-reviews'),
            'not_found'            => __('Няма намерени статии.', 'parfume-reviews'),
            'not_found_in_trash'   => __('Няма намерени статии в кошчето.', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Блог статия', 'parfume-reviews'),
            'description'          => __('Блог статии за парфюми', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'author', 'revisions'),
            'taxonomies'           => array('category', 'post_tag'),
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
     * РАЗДЕЛ 2: REWRITE RULES И URL СТРУКТУРА
     */
    
    /**
     * Добавя rewrite rules
     * ВАЖНО: Настройва URL структурата за парфюми
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Основни rewrite rules
        add_rewrite_rule(
            '^' . $slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
        
        // Pagination за архив
        add_rewrite_rule(
            '^' . $slug . '/page/([0-9]+)/?$',
            'index.php?post_type=parfume&paged=$matches[1]',
            'top'
        );
        
        // Debug log
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Post type rewrite rules added for slug: {$slug}");
        }
    }
    
    /**
     * РАЗДЕЛ 3: SCRIPTS И STYLES
     */
    
    /**
     * Enqueue scripts и styles
     * ВАЖНО: Зарежда необходимите ресурси за frontend
     */
    public function enqueue_scripts() {
        // Само на парфюмни страници
        if (!$this->is_parfume_page()) {
            return;
        }
        
        $plugin_version = defined('PARFUME_REVIEWS_VERSION') ? PARFUME_REVIEWS_VERSION : '1.0.0';
        
        // CSS за post type
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/post-type.css')) {
            wp_enqueue_style(
                'parfume-reviews-post-type',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/post-type.css',
                array(),
                $plugin_version
            );
        }
        
        // JS за post type
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/post-type.js')) {
            wp_enqueue_script(
                'parfume-reviews-post-type',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/post-type.js',
                array('jquery'),
                $plugin_version,
                true
            );
            
            // Localize script
            wp_localize_script('parfume-reviews-post-type', 'parfume_post_type', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_post_type_nonce'),
                'loading_text' => __('Зареждане...', 'parfume-reviews'),
                'error_text' => __('Възникна грешка.', 'parfume-reviews')
            ));
        }
    }
    
    /**
     * РАЗДЕЛ 4: TEMPLATE LOADING
     */
    
    /**
     * Зарежда templates за post type
     * ВАЖНО: Fallback ако няма Template_Loader компонент
     */
    public function load_templates($template) {
        // Ако имаме Template_Loader компонент, използваме него
        if ($this->template_loader) {
            return $this->template_loader->template_loader($template);
        }
        
        // Fallback template loading
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * РАЗДЕЛ 5: BODY CLASSES И STYLING
     */
    
    /**
     * Добавя body classes за parfume страници
     * ВАЖНО: Улеснява CSS стилизирането
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
            
            // Добавяме клас за типа парфюм
            $gender_terms = wp_get_post_terms(get_the_ID(), 'gender', array('fields' => 'slugs'));
            if (!empty($gender_terms)) {
                $classes[] = 'parfume-gender-' . $gender_terms[0];
            }
            
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
            
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
            
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
            
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
                $classes[] = 'parfume-term-' . $queried_object->slug;
            }
        }
        
        return $classes;
    }
    
    /**
     * РАЗДЕЛ 6: ADMIN CUSTOMIZATIONS
     */
    
    /**
     * Добавя custom колони в admin списъка
     * ВАЖНО: Подобрява admin изгледа
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['thumbnail'] = __('Изображение', 'parfume-reviews');
                $new_columns['brand'] = __('Марка', 'parfume-reviews');
                $new_columns['rating'] = __('Рейтинг', 'parfume-reviews');
                $new_columns['price'] = __('Цена', 'parfume-reviews');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Показва съдържанието на custom колоните
     * ВАЖНО: Попълва данните в admin колоните
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50));
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="font-size: 30px; color: #ccc;"></span>';
                }
                break;
                
            case 'brand':
                $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
                echo !empty($brands) ? esc_html($brands[0]) : '—';
                break;
                
            case 'rating':
                $rating = get_post_meta($post_id, '_parfume_rating', true);
                if (!empty($rating)) {
                    echo '<span class="rating-stars">' . str_repeat('★', intval($rating)) . str_repeat('☆', 5 - intval($rating)) . '</span>';
                    echo '<br><small>' . esc_html($rating) . '/5</small>';
                } else {
                    echo '—';
                }
                break;
                
            case 'price':
                $price = get_post_meta($post_id, '_parfume_price', true);
                echo !empty($price) ? esc_html($price) : '—';
                break;
        }
    }
    
    /**
     * Прави колоните sortable
     * ВАЖНО: Позволява сортиране в admin
     */
    public function make_columns_sortable($columns) {
        $columns['brand'] = 'brand';
        $columns['rating'] = 'rating';
        $columns['price'] = 'price';
        
        return $columns;
    }
    
    /**
     * РАЗДЕЛ 7: MOBILE META BOX
     */
    
    /**
     * Добавя mobile meta box
     * ВАЖНО: Настройки за мобилно поведение
     */
    public function add_mobile_meta_box() {
        add_meta_box(
            'parfume-mobile-settings',
            __('Mobile настройки', 'parfume-reviews'),
            array($this, 'mobile_meta_box_callback'),
            'parfume',
            'side',
            'default'
        );
    }
    
    /**
     * Callback за mobile meta box
     */
    public function mobile_meta_box_callback($post) {
        wp_nonce_field('parfume_mobile_meta_box', 'parfume_mobile_meta_box_nonce');
        
        $mobile_optimized = get_post_meta($post->ID, '_parfume_mobile_optimized', true);
        $mobile_stores_position = get_post_meta($post->ID, '_parfume_mobile_stores_position', true);
        
        ?>
        <table class="form-table">
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="parfume_mobile_optimized" value="1" <?php checked($mobile_optimized, '1'); ?>>
                        <?php _e('Мобилно оптимизиран', 'parfume-reviews'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="parfume_mobile_stores_position"><?php _e('Позиция на stores:', 'parfume-reviews'); ?></label>
                    <select id="parfume_mobile_stores_position" name="parfume_mobile_stores_position">
                        <option value="bottom" <?php selected($mobile_stores_position, 'bottom'); ?>><?php _e('Отдолу', 'parfume-reviews'); ?></option>
                        <option value="top" <?php selected($mobile_stores_position, 'top'); ?>><?php _e('Отгоре', 'parfume-reviews'); ?></option>
                        <option value="sidebar" <?php selected($mobile_stores_position, 'sidebar'); ?>><?php _e('Странична лента', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Запазва mobile meta box данни
     */
    public function save_mobile_meta_box($post_id) {
        if (!isset($_POST['parfume_mobile_meta_box_nonce']) || !wp_verify_nonce($_POST['parfume_mobile_meta_box_nonce'], 'parfume_mobile_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Запазваме mobile настройки
        $mobile_optimized = isset($_POST['parfume_mobile_optimized']) ? '1' : '0';
        update_post_meta($post_id, '_parfume_mobile_optimized', $mobile_optimized);
        
        if (isset($_POST['parfume_mobile_stores_position'])) {
            update_post_meta($post_id, '_parfume_mobile_stores_position', sanitize_text_field($_POST['parfume_mobile_stores_position']));
        }
    }
    
    /**
     * РАЗДЕЛ 8: UTILITY МЕТОДИ
     */
    
    /**
     * Проверява дали сме на парфюмна страница
     * ВАЖНО: Централна функция за проверка
     */
    private function is_parfume_page() {
        return is_singular('parfume') || 
               is_post_type_archive('parfume') || 
               is_singular('parfume_blog') || 
               is_post_type_archive('parfume_blog') ||
               is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
    }
    
    /**
     * Получава статистики за post type
     * ВАЖНО: Статистически данни за dashboard
     */
    public function get_post_type_stats() {
        $stats = array(
            'parfume' => array(
                'published' => wp_count_posts('parfume')->publish,
                'draft' => wp_count_posts('parfume')->draft,
                'pending' => wp_count_posts('parfume')->pending
            ),
            'parfume_blog' => array(
                'published' => wp_count_posts('parfume_blog')->publish,
                'draft' => wp_count_posts('parfume_blog')->draft,
                'pending' => wp_count_posts('parfume_blog')->pending
            )
        );
        
        return $stats;
    }
    
    /**
     * РАЗДЕЛ 9: DEBUG ФУНКЦИИ
     */
    
    /**
     * Debug информация за queries
     */
    public function debug_query_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['parfume_debug']) && $_GET['parfume_debug'] === 'query') {
            global $wp_query;
            echo '<div style="position: fixed; bottom: 10px; right: 10px; background: white; padding: 10px; border: 1px solid #ccc; z-index: 9999;">';
            echo '<strong>Query Debug:</strong><br>';
            echo 'Post Type: ' . get_post_type() . '<br>';
            echo 'Is Singular: ' . (is_singular() ? 'Yes' : 'No') . '<br>';
            echo 'Is Archive: ' . (is_archive() ? 'Yes' : 'No') . '<br>';
            echo 'Found Posts: ' . $wp_query->found_posts . '<br>';
            echo '</div>';
        }
    }
    
    /**
     * Debug информация за post type в admin
     */
    public function debug_post_type_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['parfume_debug']) && $_GET['parfume_debug'] === 'post_type') {
            echo '<div class="notice notice-info"><p><strong>Post Type Debug:</strong></p>';
            
            $stats = $this->get_post_type_stats();
            echo '<ul>';
            foreach ($stats as $post_type => $stat) {
                echo '<li>' . esc_html($post_type) . ': ' . $stat['published'] . ' published, ' . $stat['draft'] . ' draft</li>';
            }
            echo '</ul>';
            
            echo '<p>Components loaded:</p><ul>';
            echo '<li>Meta Boxes: ' . ($this->meta_boxes ? '✅' : '❌') . '</li>';
            echo '<li>Query Handler: ' . ($this->query_handler ? '✅' : '❌') . '</li>';
            echo '<li>Template Loader: ' . ($this->template_loader ? '✅' : '❌') . '</li>';
            echo '</ul></div>';
        }
    }
    
    /**
     * РАЗДЕЛ 10: ПУБЛИЧЕН API
     */
    
    /**
     * Получава инстанция на Meta_Boxes компонента
     */
    public function get_meta_boxes() {
        return $this->meta_boxes;
    }
    
    /**
     * Получава инстанция на Query_Handler компонента
     */
    public function get_query_handler() {
        return $this->query_handler;
    }
    
    /**
     * Получава инстанция на Template_Loader компонента
     */
    public function get_template_loader() {
        return $this->template_loader;
    }
    
    /**
     * Проверява дали компонентите са заредени
     */
    public function validate_components() {
        $components = array(
            'meta_boxes' => $this->meta_boxes,
            'query_handler' => $this->query_handler,
            'template_loader' => $this->template_loader
        );
        
        $missing = array();
        foreach ($components as $name => $component) {
            if (!$component) {
                $missing[] = $name;
            }
        }
        
        if (!empty($missing) && function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Missing post type components: " . implode(', ', $missing), 'error');
            return false;
        }
        
        return true;
    }
}

// End of file