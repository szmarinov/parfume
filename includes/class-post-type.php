<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * АКТУАЛИЗИРАН С НОВ STORES SIDEBAR И MOBILE НАСТРОЙКИ
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
        
        // НОВИ ДОБАВКИ ЗА MOBILE SETTINGS
        add_action('add_meta_boxes', array($this, 'add_mobile_meta_box'));
        add_action('save_post', array($this, 'save_mobile_meta_box'));
        
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
            'remove_featured_image' => __('Remove featured image', 'parfume-reviews'),
            'use_featured_image'   => __('Use as featured image', 'parfume-reviews'),
            'insert_into_item'     => __('Insert into parfume', 'parfume-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this parfume', 'parfume-reviews'),
            'items_list'           => __('Parfumes list', 'parfume-reviews'),
            'items_list_navigation' => __('Parfumes list navigation', 'parfume-reviews'),
            'filter_items_list'    => __('Filter parfumes list', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Parfume', 'parfume-reviews'),
            'description'          => __('Post Type Description', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields'),
            'taxonomies'           => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
            'hierarchical'         => false,
            'public'               => true,
            'show_ui'              => true,
            'show_in_menu'         => true,
            'menu_position'        => 5,
            'menu_icon'            => 'dashicons-awards',
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
        
        // Flush rewrite rules if needed
        if (get_option('parfume_reviews_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_flush_rewrite_rules');
        }
    }
    
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Add custom rewrite rules for parfume filtering
        add_rewrite_rule(
            '^' . $slug . '/filter/([^/]+)/?$',
            'index.php?post_type=parfume&parfume_filter=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $slug . '/brand/([^/]+)/?$',
            'index.php?post_type=parfume&marki=$matches[1]',
            'top'
        );
    }
    
    public function enqueue_scripts() {
        if (parfume_reviews_is_parfume_page()) {
            
            // Main frontend CSS
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
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
            
            // Single parfume specific CSS
            if (is_singular('parfume')) {
                wp_enqueue_style(
                    'parfume-reviews-single',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                    array('parfume-reviews-frontend'),
                    PARFUME_REVIEWS_VERSION
                );
            }
            
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
                    // Check if we're on a specific perfumer page vs all perfumers archive
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
                $generic_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy.php';
                if (file_exists($generic_template)) {
                    return $generic_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Debug информация за query (само в WP_DEBUG режим)
     */
    public function debug_query_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wp_query;
        
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
            echo "<!-- Parfume Reviews Debug:\n";
            echo "Query vars: " . print_r($wp_query->query_vars, true) . "\n";
            echo "Found posts: " . $wp_query->found_posts . "\n";
            echo "Post count: " . $wp_query->post_count . "\n";
            echo "-->";
        }
    }
    
    /**
     * НОВИ МЕТОДИ ЗА MOBILE SETTINGS META BOX
     */
    public function add_mobile_meta_box() {
        add_meta_box(
            'parfume-mobile-settings',
            __('Mobile Stores настройки', 'parfume-reviews'),
            array($this, 'render_mobile_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }
    
    public function render_mobile_meta_box($post) {
        wp_nonce_field('parfume_mobile_meta_box', 'parfume_mobile_meta_box_nonce');
        
        $mobile_fixed_stores = get_post_meta($post->ID, '_parfume_mobile_fixed_stores', true);
        $mobile_fixed_stores = ($mobile_fixed_stores !== '') ? $mobile_fixed_stores : '';
        
        ?>
        <table class="form-table">
            <tr>
                <td>
                    <label for="parfume_mobile_fixed_stores">
                        <input type="checkbox" id="parfume_mobile_fixed_stores" name="parfume_mobile_fixed_stores" value="1" <?php checked(1, $mobile_fixed_stores); ?> />
                        <?php _e('Използвай фиксиран stores панел на мобилни устройства', 'parfume-reviews'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Ако не е маркирано, следва глобалната настройка от Settings.', 'parfume-reviews'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_mobile_meta_box($post_id) {
        // Check nonce
        if (!isset($_POST['parfume_mobile_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_mobile_meta_box_nonce'], 'parfume_mobile_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if this is a parfume post
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        // Save mobile setting
        if (isset($_POST['parfume_mobile_fixed_stores'])) {
            update_post_meta($post_id, '_parfume_mobile_fixed_stores', 1);
        } else {
            update_post_meta($post_id, '_parfume_mobile_fixed_stores', 0);
        }
    }
    
    /**
     * Регистрира parfume_blog post type
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
        
        $labels = array(
            'name'                  => _x('Parfume Blog', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Blog Post', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Blog Posts', 'parfume-reviews'),
            'name_admin_bar'       => __('Blog Post', 'parfume-reviews'),
            'archives'             => __('Blog Archives', 'parfume-reviews'),
            'attributes'           => __('Blog Attributes', 'parfume-reviews'),
            'parent_item_colon'    => __('Parent Blog Post:', 'parfume-reviews'),
            'all_items'            => __('All Blog Posts', 'parfume-reviews'),
            'add_new_item'         => __('Add New Blog Post', 'parfume-reviews'),
            'add_new'              => __('Add New', 'parfume-reviews'),
            'new_item'             => __('New Blog Post', 'parfume-reviews'),
            'edit_item'            => __('Edit Blog Post', 'parfume-reviews'),
            'update_item'          => __('Update Blog Post', 'parfume-reviews'),
            'view_item'            => __('View Blog Post', 'parfume-reviews'),
            'view_items'           => __('View Blog Posts', 'parfume-reviews'),
            'search_items'         => __('Search Blog Posts', 'parfume-reviews'),
            'not_found'            => __('Not found', 'parfume-reviews'),
            'not_found_in_trash'   => __('Not found in Trash', 'parfume-reviews'),
            'featured_image'       => __('Featured Image', 'parfume-reviews'),
            'set_featured_image'   => __('Set featured image', 'parfume-reviews'),
            'remove_featured_image' => __('Remove featured image', 'parfume-reviews'),
            'use_featured_image'   => __('Use as featured image', 'parfume-reviews'),
            'insert_into_item'     => __('Insert into blog post', 'parfume-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this blog post', 'parfume-reviews'),
            'items_list'           => __('Blog posts list', 'parfume-reviews'),
            'items_list_navigation' => __('Blog posts list navigation', 'parfume-reviews'),
            'filter_items_list'    => __('Filter blog posts list', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Blog Post', 'parfume-reviews'),
            'description'          => __('Blog posts related to parfumes and reviews', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields'),
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
     * Добавя body classes за parfume страници
     */
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
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
            }
        }
        
        return $classes;
    }
    
    /**
     * Получава настройките за stores от post meta
     */
    public function get_post_stores($post_id) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (empty($stores) || !is_array($stores)) {
            return array();
        }
        
        // Ensure all stores have required fields
        $formatted_stores = array();
        foreach ($stores as $store) {
            if (!empty($store['name'])) {
                $formatted_stores[] = wp_parse_args($store, array(
                    'name' => '',
                    'logo' => '',
                    'price' => '',
                    'original_price' => '',
                    'discount' => '',
                    'availability' => '',
                    'shipping_info' => '',
                    'size' => '',
                    'affiliate_url' => '',
                    'promo_code' => '',
                    'promo_code_info' => '',
                    'promo_url' => '',
                    'variants' => array(),
                    'features' => array(),
                ));
            }
        }
        
        return $formatted_stores;
    }
    
    /**
     * Проверява дали дадена страница е свързана с parfume content
     */
    public function is_parfume_related_page() {
        return is_singular('parfume') || 
               is_singular('parfume_blog') || 
               is_post_type_archive('parfume') || 
               is_post_type_archive('parfume_blog') || 
               is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
    }
    
    /**
     * Получава mobile настройките за конкретен post
     */
    public function get_mobile_settings($post_id) {
        $global_settings = get_option('parfume_reviews_settings', array());
        $post_override = get_post_meta($post_id, '_parfume_mobile_fixed_stores', true);
        
        $settings = array(
            'mobile_fixed_panel' => isset($global_settings['mobile_fixed_panel']) ? $global_settings['mobile_fixed_panel'] : 1,
            'mobile_show_close_btn' => isset($global_settings['mobile_show_close_btn']) ? $global_settings['mobile_show_close_btn'] : 0,
            'mobile_z_index' => isset($global_settings['mobile_z_index']) ? $global_settings['mobile_z_index'] : 9999,
            'mobile_bottom_offset' => isset($global_settings['mobile_bottom_offset']) ? $global_settings['mobile_bottom_offset'] : 0,
            'scrape_interval' => isset($global_settings['scrape_interval']) ? $global_settings['scrape_interval'] : 24,
        );
        
        // Override with post-specific setting if set
        if ($post_override !== '') {
            $settings['mobile_fixed_panel'] = (bool) $post_override;
        }
        
        return $settings;
    }
}