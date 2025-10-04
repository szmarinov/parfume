<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * АКТУАЛИЗИРАН С НОВ STORES SIDEBAR И MOBILE НАСТРОЙКИ
 * ДОБАВЕНИ - Stores Meta Box и Product Scraper функционалности
 */
class Post_Type {
    
    /**
     * Instance на Query_Handler
     */
    private $query_handler;
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // STORES META BOX ФУНКЦИОНАЛНОСТИ
        add_action('add_meta_boxes', array($this, 'add_stores_meta_box'));
        add_action('save_post', array($this, 'save_stores_meta_box'));
        
        // MOBILE SETTINGS META BOX
        add_action('add_meta_boxes', array($this, 'add_mobile_meta_box'));
        add_action('save_post', array($this, 'save_mobile_meta_box'));
        
        // AJAX хендлъри за stores функционалности
        add_action('wp_ajax_parfume_add_store_to_post', array($this, 'ajax_add_store_to_post'));
        add_action('wp_ajax_parfume_remove_store_from_post', array($this, 'ajax_remove_store_from_post'));
        add_action('wp_ajax_parfume_reorder_stores', array($this, 'ajax_reorder_stores'));
        add_action('wp_ajax_parfume_scrape_store_data', array($this, 'ajax_scrape_store_data'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
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
            'description'          => __('Parfume reviews and information', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
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
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
        
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
    
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Custom rewrite rules can be added here if needed
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
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
     * Enqueue admin scripts for stores meta box
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'parfume' && in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script(
                'parfume-stores-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-stores.js',
                array('jquery', 'jquery-ui-sortable'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_enqueue_style(
                'parfume-stores-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-stores.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_localize_script('parfume-stores-admin', 'parfumeStores', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_stores_nonce'),
                'strings' => array(
                    'confirm_remove' => __('Сигурни ли сте, че искате да премахнете този магазин?', 'parfume-reviews'),
                    'scraping' => __('Скрейпване...', 'parfume-reviews'),
                    'error' => __('Възникна грешка', 'parfume-reviews'),
                    'success' => __('Успешно', 'parfume-reviews'),
                ),
                'available_stores' => get_option('parfume_reviews_stores', array())
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
     * STORES META BOX ФУНКЦИИ
     */
    
    /**
     * Добавя stores meta box
     */
    public function add_stores_meta_box() {
        add_meta_box(
            'parfume-stores',
            __('Stores & Product Scraper', 'parfume-reviews'),
            array($this, 'render_stores_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира stores meta box
     */
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_box', 'parfume_stores_meta_box_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        $available_stores = get_option('parfume_reviews_stores', array());
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $scrape_interval = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 24;
        ?>
        
        <div class="parfume-stores-manager">
            <div class="stores-header">
                <h3><?php _e('Affiliate магазини за този парфюм', 'parfume-reviews'); ?></h3>
                <p class="description"><?php _e('Добавете магазини които предлагат този парфюм. Можете да ги пренареждате с drag & drop.', 'parfume-reviews'); ?></p>
            </div>
            
            <!-- Add Store Section -->
            <div class="add-store-section">
                <h4><?php _e('Добави магазин', 'parfume-reviews'); ?></h4>
                <div class="add-store-form">
                    <select id="available-stores-select">
                        <option value=""><?php _e('Изберете магазин', 'parfume-reviews'); ?></option>
                        <?php foreach ($available_stores as $store_id => $store): ?>
                            <?php if ($store['status'] === 'active'): ?>
                                <option value="<?php echo esc_attr($store_id); ?>" data-name="<?php echo esc_attr($store['name']); ?>" data-logo="<?php echo esc_attr($store['logo']); ?>">
                                    <?php echo esc_html($store['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-store-btn" class="button button-secondary"><?php _e('Добави', 'parfume-reviews'); ?></button>
                </div>
                <?php if (empty($available_stores)): ?>
                    <p class="notice notice-warning inline">
                        <?php _e('Няма налични магазини. Първо', 'parfume-reviews'); ?> 
                        <a href="<?php echo admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings&tab=stores'); ?>" target="_blank"><?php _e('добавете магазини', 'parfume-reviews'); ?></a> 
                        <?php _e('в настройките.', 'parfume-reviews'); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Stores List -->
            <div class="stores-list-section">
                <h4><?php _e('Добавени магазини', 'parfume-reviews'); ?></h4>
                <div id="stores-list" class="stores-sortable">
                    <?php if (!empty($stores)): ?>
                        <?php foreach ($stores as $index => $store): ?>
                            <?php $this->render_store_item($store, $index, $scrape_interval); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-stores-message">
                            <p><?php _e('Няма добавени магазини за този парфюм.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .parfume-stores-manager {
            margin: 15px 0;
        }
        .stores-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .add-store-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .add-store-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .add-store-form select {
            min-width: 200px;
        }
        .stores-sortable {
            min-height: 50px;
        }
        .store-item {
            background: white;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            padding: 15px;
            position: relative;
            cursor: move;
        }
        .store-item:hover {
            border-color: #999;
        }
        .store-item .dashicons-sort {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #999;
        }
        .store-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .store-logo {
            width: 40px;
            height: 40px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            border-radius: 3px;
        }
        .store-logo img {
            max-width: 35px;
            max-height: 35px;
        }
        .store-name {
            font-weight: bold;
            font-size: 16px;
        }
        .store-actions {
            margin-left: auto;
        }
        .store-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .field-group {
            display: flex;
            flex-direction: column;
        }
        .field-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .field-group input,
        .field-group textarea {
            padding: 5px;
        }
        .scraped-data {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            padding: 10px;
            margin-top: 10px;
            border-radius: 3px;
        }
        .scraped-data h5 {
            margin: 0 0 10px 0;
            color: #2e7d32;
        }
        .scrape-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        .no-stores-message {
            text-align: center;
            padding: 40px;
            color: #666;
            background: #f9f9f9;
            border: 1px dashed #ddd;
        }
        </style>
        <?php
    }
    
    /**
     * Рендерира един магазин item
     */
    private function render_store_item($store, $index, $scrape_interval) {
        $store_name = isset($store['name']) ? $store['name'] : '';
        $store_logo = isset($store['logo']) ? $store['logo'] : '';
        $product_url = isset($store['product_url']) ? $store['product_url'] : '';
        $affiliate_url = isset($store['affiliate_url']) ? $store['affiliate_url'] : '';
        $promo_code = isset($store['promo_code']) ? $store['promo_code'] : '';
        $promo_code_info = isset($store['promo_code_info']) ? $store['promo_code_info'] : '';
        
        // Scraped data
        $scraped_price = isset($store['scraped_price']) ? $store['scraped_price'] : '';
        $scraped_old_price = isset($store['scraped_old_price']) ? $store['scraped_old_price'] : '';
        $scraped_variants = isset($store['scraped_variants']) ? $store['scraped_variants'] : array();
        $scraped_availability = isset($store['scraped_availability']) ? $store['scraped_availability'] : '';
        $scraped_delivery = isset($store['scraped_delivery']) ? $store['scraped_delivery'] : '';
        $last_scraped = isset($store['last_scraped']) ? $store['last_scraped'] : '';
        $next_scrape = isset($store['next_scrape']) ? $store['next_scrape'] : '';
        $scrape_status = isset($store['scrape_status']) ? $store['scrape_status'] : 'pending';
        ?>
        
        <div class="store-item" data-index="<?php echo esc_attr($index); ?>">
            <span class="dashicons dashicons-sort"></span>
            
            <div class="store-header">
                <div class="store-logo">
                    <?php if (!empty($store_logo)): ?>
                        <img src="<?php echo esc_url($store_logo); ?>" alt="<?php echo esc_attr($store_name); ?>">
                    <?php else: ?>
                        <span class="dashicons dashicons-store"></span>
                    <?php endif; ?>
                </div>
                <div class="store-name"><?php echo esc_html($store_name); ?></div>
                <div class="store-actions">
                    <button type="button" class="button button-small remove-store" data-index="<?php echo esc_attr($index); ?>">
                        <?php _e('Премахни', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
            
            <div class="store-fields">
                <div class="field-group">
                    <label><?php _e('Product URL', 'parfume-reviews'); ?></label>
                    <input type="url" name="parfume_stores[<?php echo $index; ?>][product_url]" value="<?php echo esc_attr($product_url); ?>" placeholder="https://example.com/product-page" class="product-url-field">
                    <small><?php _e('URL към страницата на продукта за скрейпване на данни', 'parfume-reviews'); ?></small>
                </div>
                
                <div class="field-group">
                    <label><?php _e('Affiliate URL', 'parfume-reviews'); ?></label>
                    <input type="url" name="parfume_stores[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($affiliate_url); ?>" placeholder="https://affiliate-link.com">
                    <small><?php _e('Affiliate линк към магазина (target="_blank" и rel="nofollow")', 'parfume-reviews'); ?></small>
                </div>
                
                <div class="field-group">
                    <label><?php _e('Promo Code', 'parfume-reviews'); ?></label>
                    <input type="text" name="parfume_stores[<?php echo $index; ?>][promo_code]" value="<?php echo esc_attr($promo_code); ?>" placeholder="DISCOUNT20">
                </div>
                
                <div class="field-group">
                    <label><?php _e('Promo Code Info', 'parfume-reviews'); ?></label>
                    <input type="text" name="parfume_stores[<?php echo $index; ?>][promo_code_info]" value="<?php echo esc_attr($promo_code_info); ?>" placeholder="20% отстъпка">
                </div>
            </div>
            
            <!-- Hidden fields for store data -->
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][name]" value="<?php echo esc_attr($store_name); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][logo]" value="<?php echo esc_attr($store_logo); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_price]" value="<?php echo esc_attr($scraped_price); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_old_price]" value="<?php echo esc_attr($scraped_old_price); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_variants]" value="<?php echo esc_attr(json_encode($scraped_variants)); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_availability]" value="<?php echo esc_attr($scraped_availability); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_delivery]" value="<?php echo esc_attr($scraped_delivery); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][last_scraped]" value="<?php echo esc_attr($last_scraped); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][next_scrape]" value="<?php echo esc_attr($next_scrape); ?>">
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scrape_status]" value="<?php echo esc_attr($scrape_status); ?>">
            
            <!-- Scraped Data Display -->
            <?php if (!empty($product_url)): ?>
                <div class="scraped-data">
                    <h5><?php _e('Скрейпнати данни', 'parfume-reviews'); ?></h5>
                    
                    <div class="scraped-fields">
                        <?php if (!empty($scraped_price)): ?>
                            <div class="scraped-field">
                                <strong><?php _e('Цена:', 'parfume-reviews'); ?></strong> 
                                <?php echo esc_html($scraped_price); ?>
                                <?php if (!empty($scraped_old_price) && $scraped_old_price != $scraped_price): ?>
                                    <span style="text-decoration: line-through; color: #999;"><?php echo esc_html($scraped_old_price); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_variants)): ?>
                            <div class="scraped-field">
                                <strong><?php _e('Разфасовки:', 'parfume-reviews'); ?></strong>
                                <?php foreach ($scraped_variants as $variant): ?>
                                    <span class="variant-tag"><?php echo esc_html($variant['ml']); ?>ml</span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_availability)): ?>
                            <div class="scraped-field">
                                <strong><?php _e('Наличност:', 'parfume-reviews'); ?></strong>
                                <span class="availability-<?php echo esc_attr($scraped_availability); ?>">
                                    <?php 
                                    switch ($scraped_availability) {
                                        case 'available':
                                            _e('Наличен', 'parfume-reviews');
                                            break;
                                        case 'unavailable':
                                            _e('Няма в наличност', 'parfume-reviews');
                                            break;
                                        default:
                                            _e('Неизвестно', 'parfume-reviews');
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_delivery)): ?>
                            <div class="scraped-field">
                                <strong><?php _e('Доставка:', 'parfume-reviews'); ?></strong> 
                                <?php echo esc_html($scraped_delivery); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="scrape-info">
                        <div class="scrape-times">
                            <?php if (!empty($last_scraped)): ?>
                                <span><?php _e('Последно скрейпване:', 'parfume-reviews'); ?> <?php echo esc_html($last_scraped); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($next_scrape)): ?>
                                <span><?php _e('Следващо скрейпване:', 'parfume-reviews'); ?> <?php echo esc_html($next_scrape); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="scrape-actions">
                            <button type="button" class="button button-small scrape-now" data-index="<?php echo esc_attr($index); ?>">
                                <?php _e('Скрейпни сега', 'parfume-reviews'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <style>
                .variant-tag {
                    background: #e1f5fe;
                    color: #0277bd;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 11px;
                    margin-right: 5px;
                }
                .availability-available { color: #4caf50; }
                .availability-unavailable { color: #f44336; }
                .availability-unknown { color: #ff9800; }
                .scraped-field {
                    margin-bottom: 8px;
                }
                .scraped-fields {
                    margin-bottom: 10px;
                }
                </style>
            <?php else: ?>
                <p class="description"><?php _e('Добавете Product URL за да започнете скрейпване на данни.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Запазва stores meta box данни
     */
    public function save_stores_meta_box($post_id) {
        // Check nonce
        if (!isset($_POST['parfume_stores_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_stores_meta_box_nonce'], 'parfume_stores_meta_box')) {
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
        
        // Save stores data
        $stores = array();
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            foreach ($_POST['parfume_stores'] as $store_data) {
                if (!empty($store_data['name'])) {
                    $store = array(
                        'name' => sanitize_text_field($store_data['name']),
                        'logo' => esc_url_raw($store_data['logo']),
                        'product_url' => esc_url_raw($store_data['product_url']),
                        'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                        'promo_code' => sanitize_text_field($store_data['promo_code']),
                        'promo_code_info' => sanitize_text_field($store_data['promo_code_info']),
                        'scraped_price' => sanitize_text_field($store_data['scraped_price']),
                        'scraped_old_price' => sanitize_text_field($store_data['scraped_old_price']),
                        'scraped_variants' => !empty($store_data['scraped_variants']) ? json_decode(stripslashes($store_data['scraped_variants']), true) : array(),
                        'scraped_availability' => sanitize_text_field($store_data['scraped_availability']),
                        'scraped_delivery' => sanitize_text_field($store_data['scraped_delivery']),
                        'last_scraped' => sanitize_text_field($store_data['last_scraped']),
                        'next_scrape' => sanitize_text_field($store_data['next_scrape']),
                        'scrape_status' => sanitize_text_field($store_data['scrape_status'])
                    );
                    $stores[] = $store;
                }
            }
        }
        
        update_post_meta($post_id, '_parfume_stores', $stores);
    }
    
    /**
     * MOBILE SETTINGS META BOX ФУНКЦИИ
     */
    
    /**
     * Добавя mobile meta box
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
    
    /**
     * Рендерира mobile meta box
     */
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
    
    /**
     * Запазва mobile meta box данни
     */
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
     * AJAX HANDLERS
     */
    
    /**
     * AJAX handler за добавяне на магазин към пост
     */
    public function ajax_add_store_to_post() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        $available_stores = get_option('parfume_reviews_stores', array());
        if (!isset($available_stores[$store_id])) {
            wp_send_json_error(__('Магазинът не съществува.', 'parfume-reviews'));
        }
        
        $store = $available_stores[$store_id];
        $current_stores = get_post_meta($post_id, '_parfume_stores', true);
        $current_stores = !empty($current_stores) && is_array($current_stores) ? $current_stores : array();
        
        // Check if store is already added
        foreach ($current_stores as $existing_store) {
            if ($existing_store['name'] === $store['name']) {
                wp_send_json_error(__('Този магазин вече е добавен.', 'parfume-reviews'));
            }
        }
        
        // Add new store
        $new_store = array(
            'name' => $store['name'],
            'logo' => $store['logo'],
            'product_url' => '',
            'affiliate_url' => '',
            'promo_code' => '',
            'promo_code_info' => '',
            'scraped_price' => '',
            'scraped_old_price' => '',
            'scraped_variants' => array(),
            'scraped_availability' => '',
            'scraped_delivery' => '',
            'last_scraped' => '',
            'next_scrape' => '',
            'scrape_status' => 'pending'
        );
        
        $current_stores[] = $new_store;
        update_post_meta($post_id, '_parfume_stores', $current_stores);
        
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $scrape_interval = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 24;
        
        ob_start();
        $this->render_store_item($new_store, count($current_stores) - 1, $scrape_interval);
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'message' => __('Магазинът е добавен успешно.', 'parfume-reviews')
        ));
    }
    
    /**
     * AJAX handler за премахване на магазин от пост
     */
    public function ajax_remove_store_from_post() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        $current_stores = get_post_meta($post_id, '_parfume_stores', true);
        $current_stores = !empty($current_stores) && is_array($current_stores) ? $current_stores : array();
        
        if (isset($current_stores[$store_index])) {
            unset($current_stores[$store_index]);
            $current_stores = array_values($current_stores); // Re-index array
            update_post_meta($post_id, '_parfume_stores', $current_stores);
            wp_send_json_success(__('Магазинът е премахнат успешно.', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX handler за пренареждане на магазини
     */
    public function ajax_reorder_stores() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $order = $_POST['order']; // Array of indices
        
        $current_stores = get_post_meta($post_id, '_parfume_stores', true);
        $current_stores = !empty($current_stores) && is_array($current_stores) ? $current_stores : array();
        
        $reordered_stores = array();
        foreach ($order as $index) {
            if (isset($current_stores[$index])) {
                $reordered_stores[] = $current_stores[$index];
            }
        }
        
        update_post_meta($post_id, '_parfume_stores', $reordered_stores);
        wp_send_json_success(__('Редът на магазините е обновен.', 'parfume-reviews'));
    }
    
    /**
     * AJAX handler за скрейпване на данни за магазин
     */
    public function ajax_scrape_store_data() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        // Redirect to Settings class method for scraping
        if (class_exists('Parfume_Reviews\\Settings')) {
            $settings = new \Parfume_Reviews\Settings();
            $settings->ajax_scrape_product();
        } else {
            wp_send_json_error(__('Scraper функционалността не е налична.', 'parfume-reviews'));
        }
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
                    'product_url' => '',
                    'affiliate_url' => '',
                    'promo_code' => '',
                    'promo_code_info' => '',
                    'scraped_price' => '',
                    'scraped_old_price' => '',
                    'scraped_variants' => array(),
                    'scraped_availability' => '',
                    'scraped_delivery' => '',
                    'last_scraped' => '',
                    'next_scrape' => '',
                    'scrape_status' => 'pending'
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
}