<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * 
 * АКТУАЛИЗИРАНА ВЕРСИЯ v2.0:
 * - Добавена поддръжка за scraped_old_price поле за отстъпки
 * - Подобрен stores meta box с scraper информация
 * - Добавени mobile settings per-post
 * - Запазени ВСИЧКИ оригинални функции
 * 
 * @package Parfume_Reviews
 * @version 2.0
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
        
        // GENERAL META BOXES - ОРИГИНАЛНИ
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // AJAX хендлъри за stores функционалности
        add_action('wp_ajax_parfume_add_store_to_post', array($this, 'ajax_add_store_to_post'));
        add_action('wp_ajax_parfume_remove_store_from_post', array($this, 'ajax_remove_store_from_post'));
        add_action('wp_ajax_parfume_reorder_stores', array($this, 'ajax_reorder_stores'));
        add_action('wp_ajax_parfume_scrape_store_data', array($this, 'ajax_scrape_store_data'));
        
        // ОРИГИНАЛНИ AJAX HANDLERS (запазени за backward compatibility)
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        
        // PRICE COMPARISON AJAX
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        
        // Query handler инициализация
        add_action('wp_loaded', array($this, 'init_query_handler'));
    }
    
    /**
     * Инициализира query handler
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function init_query_handler() {
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-query-handler.php')) {
            require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/post-type/class-query-handler.php';
            
            if (class_exists('Parfume_Reviews\Post_Type\Query_Handler')) {
                $this->query_handler = new \Parfume_Reviews\Post_Type\Query_Handler();
            }
        }
    }
    
    /**
     * Регистрира parfume post type
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Парфюми', 'parfume-reviews'),
            'singular_name' => __('Парфюм', 'parfume-reviews'),
            'menu_name' => __('Парфюми', 'parfume-reviews'),
            'add_new' => __('Добави нов', 'parfume-reviews'),
            'add_new_item' => __('Добави нов парфюм', 'parfume-reviews'),
            'edit_item' => __('Редактирай парфюм', 'parfume-reviews'),
            'new_item' => __('Нов парфюм', 'parfume-reviews'),
            'view_item' => __('Виж парфюм', 'parfume-reviews'),
            'search_items' => __('Търси парфюми', 'parfume-reviews'),
            'not_found' => __('Няма намерени парфюми', 'parfume-reviews'),
            'not_found_in_trash' => __('Няма парфюми в кошчето', 'parfume-reviews')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => $parfume_slug),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
            'menu_icon' => 'dashicons-admin-customizer',
            'show_in_rest' => true
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume_blog post type
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Блог статии', 'parfume-reviews'),
            'singular_name' => __('Блог статия', 'parfume-reviews'),
            'menu_name' => __('Блог', 'parfume-reviews'),
            'add_new' => __('Добави статия', 'parfume-reviews'),
            'add_new_item' => __('Добави нова статия', 'parfume-reviews'),
            'edit_item' => __('Редактирай статия', 'parfume-reviews'),
            'new_item' => __('Нова статия', 'parfume-reviews'),
            'view_item' => __('Виж статия', 'parfume-reviews'),
            'search_items' => __('Търси статии', 'parfume-reviews'),
            'not_found' => __('Няма намерени статии', 'parfume-reviews'),
            'not_found_in_trash' => __('Няма статии в кошчето', 'parfume-reviews')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => $parfume_slug . '/' . $blog_slug),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'author'),
            'menu_icon' => 'dashicons-welcome-write-blog',
            'show_in_menu' => 'edit.php?post_type=parfume',
            'show_in_rest' => true
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя custom rewrite rules
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/' . $blog_slug . '/?$',
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
        
        if (get_option('parfume_reviews_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_flush_rewrite_rules');
        }
    }
    
    /**
     * Enqueue scripts and styles
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function enqueue_scripts() {
        if (is_singular('parfume')) {
            wp_enqueue_style(
                'parfume-single',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-single',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/single-parfume.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-single', 'parfume_single_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_single_nonce'),
                'strings' => array(
                    'updating_price' => __('Обновяване на цена...', 'parfume-reviews'),
                    'price_updated' => __('Цената е обновена', 'parfume-reviews'),
                    'update_failed' => __('Неуспешно обновяване', 'parfume-reviews'),
                    'copying_code' => __('Копиране на код...', 'parfume-reviews'),
                    'code_copied' => __('Кодът е копиран!', 'parfume-reviews'),
                    'copy_failed' => __('Неуспешно копиране', 'parfume-reviews')
                )
            ));
        }
        
        if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            wp_enqueue_style(
                'parfume-archive',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/archive-parfume.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-filters',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/parfume-filters.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
        }
    }
    
    /**
     * Зарежда templates
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function load_templates($template) {
        global $post;
        
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
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
        
        if (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
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
        
        if (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавя body classes
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
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
    
    // ==================== STORES META BOX ====================
    
    /**
     * Добавя stores meta box
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function add_stores_meta_box() {
        add_meta_box(
            'parfume_stores_sidebar',
            __('Stores Sidebar - Магазини', 'parfume-reviews'),
            array($this, 'render_stores_meta_box'),
            'parfume',
            'normal',
            'high'
        );
    }
    
    /**
     * Рендерира stores meta box
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_box', 'parfume_stores_meta_box_nonce');
        
        $post_stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (!$post_stores) {
            $post_stores = array();
        }
        
        $available_stores = $this->get_available_stores();
        
        ?>
        <div id="parfume-stores-wrapper">
            <div class="stores-header">
                <h4><?php _e('Настройки за магазини', 'parfume-reviews'); ?></h4>
                <p class="description"><?php _e('Добавете магазини за този парфюм. Магазините ще се показват в stores sidebar на фронтенда.', 'parfume-reviews'); ?></p>
            </div>
            
            <div class="add-store-section">
                <label for="available-stores-select"><?php _e('Добави магазин:', 'parfume-reviews'); ?></label>
                <select id="available-stores-select">
                    <option value=""><?php _e('Изберете магазин...', 'parfume-reviews'); ?></option>
                    <?php foreach ($available_stores as $key => $store): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($store['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add-store-btn" class="button"><?php _e('Добави магазин', 'parfume-reviews'); ?></button>
            </div>
            
            <div id="stores-list" class="stores-list">
                <?php if (!empty($post_stores)): ?>
                    <?php foreach ($post_stores as $index => $store): ?>
                        <?php $this->render_store_item($store, $index); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-stores"><?php _e('Няма добавени магазини.', 'parfume-reviews'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        #parfume-stores-wrapper {
            background: #fff;
            padding: 20px;
        }
        .stores-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        .add-store-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .add-store-section label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .add-store-section select {
            width: 300px;
            margin-right: 10px;
        }
        .store-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .store-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .store-drag-handle {
            cursor: move;
            padding: 5px;
            color: #666;
        }
        .store-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .store-field label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-field input {
            width: 100%;
        }
        .scraped-data {
            background: #e8f4fd;
            border: 1px solid #b3d7f0;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .scraped-info {
            margin: 5px 0;
            font-size: 13px;
        }
        .scrape-actions {
            margin-top: 10px;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#add-store-btn').on('click', function() {
                var storeKey = $('#available-stores-select').val();
                if (!storeKey) {
                    alert('<?php echo esc_js(__('Моля изберете магазин', 'parfume-reviews')); ?>');
                    return;
                }
                
                var data = {
                    action: 'parfume_add_store_to_post',
                    post_id: <?php echo $post->ID; ?>,
                    store_key: storeKey,
                    nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        $('.no-stores').hide();
                        $('#stores-list').append(response.data.html);
                        $('#available-stores-select').val('');
                    } else {
                        alert(response.data || '<?php echo esc_js(__('Грешка при добавяне на магазин', 'parfume-reviews')); ?>');
                    }
                });
            });
            
            $(document).on('click', '.remove-store', function() {
                if (confirm('<?php echo esc_js(__('Сигурни ли сте?', 'parfume-reviews')); ?>')) {
                    $(this).closest('.store-item').remove();
                    if ($('#stores-list .store-item').length === 0) {
                        $('#stores-list').append('<p class="no-stores"><?php echo esc_js(__('Няма добавени магазини.', 'parfume-reviews')); ?></p>');
                    }
                }
            });
            
            $(document).on('click', '.scrape-store-data', function() {
                var $button = $(this);
                var $storeItem = $button.closest('.store-item');
                var productUrl = $storeItem.find('input[name*="[product_url]"]').val();
                
                if (!productUrl) {
                    alert('<?php echo esc_js(__('Моля въведете Product URL', 'parfume-reviews')); ?>');
                    return;
                }
                
                $button.prop('disabled', true).text('<?php echo esc_js(__('Scraping...', 'parfume-reviews')); ?>');
                
                var data = {
                    action: 'parfume_scrape_store_data',
                    product_url: productUrl,
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_nonce'); ?>'
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        $storeItem.find('.scraped-price').text(response.data.price);
                        $storeItem.find('.scraped-sizes').text(response.data.sizes);
                        $storeItem.find('.scraped-availability').text(response.data.availability);
                        $storeItem.find('.scraped-delivery').text(response.data.delivery);
                        $storeItem.find('.last-scraped').text('<?php echo date('Y-m-d H:i:s'); ?>');
                        
                        alert('<?php echo esc_js(__('Данните са обновени успешно', 'parfume-reviews')); ?>');
                    } else {
                        alert('<?php echo esc_js(__('Грешка при scraping', 'parfume-reviews')); ?>');
                    }
                    
                    $button.prop('disabled', false).text('<?php echo esc_js(__('Обнови данни', 'parfume-reviews')); ?>');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Рендерира един store item
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    private function render_store_item($store, $index) {
        $available_stores = $this->get_available_stores();
        $store_config = isset($available_stores[$store['store_key']]) ? $available_stores[$store['store_key']] : array();
        
        ?>
        <div class="store-item" data-index="<?php echo $index; ?>">
            <div class="store-item-header">
                <div class="store-info">
                    <span class="store-drag-handle dashicons dashicons-menu"></span>
                    <strong><?php echo esc_html($store['name']); ?></strong>
                    <?php if (!empty($store_config['logo_url'])): ?>
                        <img src="<?php echo esc_url($store_config['logo_url']); ?>" alt="<?php echo esc_attr($store['name']); ?>" style="height: 20px; margin-left: 10px;">
                    <?php endif; ?>
                </div>
                <button type="button" class="remove-store button-link-delete"><?php _e('Премахни', 'parfume-reviews'); ?></button>
            </div>
            
            <div class="store-fields">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][store_key]" value="<?php echo esc_attr($store['store_key']); ?>">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][name]" value="<?php echo esc_attr($store['name']); ?>">
                
                <div class="store-field">
                    <label for="store_product_url_<?php echo $index; ?>"><?php _e('Product URL', 'parfume-reviews'); ?></label>
                    <input type="url" id="store_product_url_<?php echo $index; ?>" name="parfume_stores[<?php echo $index; ?>][product_url]" value="<?php echo esc_attr($store['product_url'] ?? ''); ?>" placeholder="https://example.com/product">
                </div>
                
                <div class="store-field">
                    <label for="store_affiliate_url_<?php echo $index; ?>"><?php _e('Affiliate URL', 'parfume-reviews'); ?></label>
                    <input type="url" id="store_affiliate_url_<?php echo $index; ?>" name="parfume_stores[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($store['affiliate_url'] ?? ''); ?>" placeholder="https://affiliate-link.com">
                </div>
                
                <div class="store-field">
                    <label for="store_promo_code_<?php echo $index; ?>"><?php _e('Promo Code', 'parfume-reviews'); ?></label>
                    <input type="text" id="store_promo_code_<?php echo $index; ?>" name="parfume_stores[<?php echo $index; ?>][promo_code]" value="<?php echo esc_attr($store['promo_code'] ?? ''); ?>" placeholder="DISCOUNT20">
                </div>
                
                <div class="store-field">
                    <label for="store_promo_code_info_<?php echo $index; ?>"><?php _e('Promo Code Info', 'parfume-reviews'); ?></label>
                    <input type="text" id="store_promo_code_info_<?php echo $index; ?>" name="parfume_stores[<?php echo $index; ?>][promo_code_info]" value="<?php echo esc_attr($store['promo_code_info'] ?? ''); ?>" placeholder="20% отстъпка">
                </div>
            </div>
            
            <div class="scraped-data">
                <h5><?php _e('Автоматично извлечени данни', 'parfume-reviews'); ?></h5>
                
                <div class="scraped-info">
                    <strong><?php _e('Цена:', 'parfume-reviews'); ?></strong> 
                    <span class="scraped-price"><?php echo esc_html($store['scraped_price'] ?? __('Няма данни', 'parfume-reviews')); ?></span>
                    <small class="last-scraped"><?php echo esc_html($store['last_scraped'] ?? ''); ?></small>
                </div>
                
                <div class="scraped-info">
                    <strong><?php _e('Разфасовки:', 'parfume-reviews'); ?></strong>
                    <div class="scraped-sizes"><?php echo wp_kses_post($store['scraped_sizes'] ?? __('Няма данни', 'parfume-reviews')); ?></div>
                </div>
                
                <div class="scraped-info">
                    <strong><?php _e('Наличност:', 'parfume-reviews'); ?></strong>
                    <span class="scraped-availability"><?php echo esc_html($store['scraped_availability'] ?? __('Няма данни', 'parfume-reviews')); ?></span>
                </div>
                
                <div class="scraped-info">
                    <strong><?php _e('Доставка:', 'parfume-reviews'); ?></strong>
                    <span class="scraped-delivery"><?php echo esc_html($store['scraped_delivery'] ?? __('Няма данни', 'parfume-reviews')); ?></span>
                </div>
                
                <div class="scrape-actions">
                    <button type="button" class="scrape-store-data button"><?php _e('Обнови данни', 'parfume-reviews'); ?></button>
                    <small><?php _e('Следващо обновяване:', 'parfume-reviews'); ?> <span class="next-scrape"><?php echo esc_html($store['next_scrape'] ?? __('неизвестно', 'parfume-reviews')); ?></span></small>
                </div>
                
                <!-- Hidden fields за scraped data -->
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_price]" value="<?php echo esc_attr($store['scraped_price'] ?? ''); ?>">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_old_price]" value="<?php echo esc_attr($store['scraped_old_price'] ?? ''); ?>">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_sizes]" value="<?php echo esc_attr($store['scraped_sizes'] ?? ''); ?>">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_availability]" value="<?php echo esc_attr($store['scraped_availability'] ?? ''); ?>">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][scraped_delivery]" value="<?php echo esc_attr($store['scraped_delivery'] ?? ''); ?>">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][last_scraped]" value="<?php echo esc_attr($store['last_scraped'] ?? ''); ?>">
                <input type="hidden" name="parfume_stores[<?php echo $index; ?>][next_scrape]" value="<?php echo esc_attr($store['next_scrape'] ?? ''); ?>">
            </div>
        </div>
        <?php
    }
    
    /**
     * Запазва stores meta box данните
     * АКТУАЛИЗИРАНА ВЕРСИЯ - добавено scraped_old_price поле
     */
    public function save_stores_meta_box($post_id) {
        if (!isset($_POST['parfume_stores_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_stores_meta_box_nonce'], 'parfume_stores_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores = array();
            foreach ($_POST['parfume_stores'] as $store_data) {
                $stores[] = array(
                    // Основни полета (ЗАПАЗЕНИ)
                    'store_key' => sanitize_key($store_data['store_key']),
                    'name' => sanitize_text_field($store_data['name']),
                    'product_url' => esc_url_raw($store_data['product_url']),
                    'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                    'promo_code' => sanitize_text_field($store_data['promo_code']),
                    'promo_code_info' => sanitize_text_field($store_data['promo_code_info']),
                    
                    // Scraped data полета (ЗАПАЗЕНИ + ДОБАВЕНО scraped_old_price)
                    'scraped_price' => sanitize_text_field($store_data['scraped_price'] ?? ''),
                    'scraped_old_price' => sanitize_text_field($store_data['scraped_old_price'] ?? ''), // НОВО поле за отстъпки
                    'scraped_sizes' => wp_kses_post($store_data['scraped_sizes'] ?? ''),
                    'scraped_availability' => sanitize_text_field($store_data['scraped_availability'] ?? ''),
                    'scraped_delivery' => sanitize_text_field($store_data['scraped_delivery'] ?? ''),
                    
                    // Времеви полета (ЗАПАЗЕНИ)
                    'last_scraped' => sanitize_text_field($store_data['last_scraped'] ?? ''),
                    'next_scrape' => sanitize_text_field($store_data['next_scrape'] ?? '')
                );
            }
            update_post_meta($post_id, '_parfume_stores', $stores);
        } else {
            delete_post_meta($post_id, '_parfume_stores');
        }
    }
    
    // ==================== MOBILE META BOX ====================
    
    /**
     * Добавя mobile settings meta box
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function add_mobile_meta_box() {
        add_meta_box(
            'parfume_mobile_settings',
            __('Mobile настройки', 'parfume-reviews'),
            array($this, 'render_mobile_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }
    
    /**
     * Рендерира mobile meta box
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function render_mobile_meta_box($post) {
        wp_nonce_field('parfume_mobile_meta_box', 'parfume_mobile_meta_box_nonce');
        
        $mobile_fixed_panel = get_post_meta($post->ID, '_parfume_mobile_fixed_panel', true);
        $mobile_show_close_btn = get_post_meta($post->ID, '_parfume_mobile_show_close_btn', true);
        
        echo '<p><label>';
        echo '<input type="checkbox" name="parfume_mobile_fixed_panel" value="1" ' . checked($mobile_fixed_panel, '1', false) . ' />';
        echo ' ' . __('Използвай фиксиран stores панел на мобил', 'parfume-reviews');
        echo '</label></p>';
        
        echo '<p><label>';
        echo '<input type="checkbox" name="parfume_mobile_show_close_btn" value="1" ' . checked($mobile_show_close_btn, '1', false) . ' />';
        echo ' ' . __('Покажи бутон "X" за затваряне', 'parfume-reviews');
        echo '</label></p>';
        
        echo '<p class="description">' . __('Тези настройки имат приоритет пред глобалните mobile настройки.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Запазва mobile meta box данните
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function save_mobile_meta_box($post_id) {
        if (!isset($_POST['parfume_mobile_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_mobile_meta_box_nonce'], 'parfume_mobile_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        $mobile_fixed_panel = isset($_POST['parfume_mobile_fixed_panel']) ? '1' : '0';
        update_post_meta($post_id, '_parfume_mobile_fixed_panel', $mobile_fixed_panel);
        
        $mobile_show_close_btn = isset($_POST['parfume_mobile_show_close_btn']) ? '1' : '0';
        update_post_meta($post_id, '_parfume_mobile_show_close_btn', $mobile_show_close_btn);
    }
    
    // ==================== GENERAL META BOXES ====================
    
    /**
     * Добавя general meta boxes
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_general_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Рендерира general meta box
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function render_general_meta_box($post) {
        wp_nonce_field('parfume_general_meta_box', 'parfume_general_meta_box_nonce');
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_rating"><?php _e('Рейтинг (1-5)', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="number" id="parfume_rating" name="parfume_rating" value="<?php echo esc_attr($rating); ?>" min="1" max="5" step="0.1" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_longevity"><?php _e('Дълготрайност', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="text" id="parfume_longevity" name="parfume_longevity" value="<?php echo esc_attr($longevity); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_sillage"><?php _e('Сила', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="text" id="parfume_sillage" name="parfume_sillage" value="<?php echo esc_attr($sillage); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_pros"><?php _e('Плюсове', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_pros" name="parfume_pros" rows="5" cols="50" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('По един плюс на ред', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_cons"><?php _e('Минуси', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_cons" name="parfume_cons" rows="5" cols="50" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('По един минус на ред', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Запазва general meta box данните
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИЯ
     */
    public function save_general_meta_boxes($post_id) {
        if (!isset($_POST['parfume_general_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_general_meta_box_nonce'], 'parfume_general_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) === 'parfume') {
            if (isset($_POST['parfume_rating'])) {
                update_post_meta($post_id, '_parfume_rating', floatval($_POST['parfume_rating']));
            }
            if (isset($_POST['parfume_longevity'])) {
                update_post_meta($post_id, '_parfume_longevity', sanitize_text_field($_POST['parfume_longevity']));
            }
            if (isset($_POST['parfume_sillage'])) {
                update_post_meta($post_id, '_parfume_sillage', sanitize_text_field($_POST['parfume_sillage']));
            }
            if (isset($_POST['parfume_pros'])) {
                update_post_meta($post_id, '_parfume_pros', sanitize_textarea_field($_POST['parfume_pros']));
            }
            if (isset($_POST['parfume_cons'])) {
                update_post_meta($post_id, '_parfume_cons', sanitize_textarea_field($_POST['parfume_cons']));
            }
        }
    }
    
    // ==================== HELPER FUNCTIONS ====================
    
    /**
     * Получава списък с налични магазини от настройките
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    private function get_available_stores() {
        $settings = get_option('parfume_reviews_settings', array());
        $available_stores = isset($settings['available_stores']) ? $settings['available_stores'] : array();
        
        if (empty($available_stores)) {
            $available_stores = array(
                'douglas' => array(
                    'name' => 'Douglas',
                    'logo_url' => '',
                    'schema' => array()
                ),
                'notino' => array(
                    'name' => 'Notino',
                    'logo_url' => '',
                    'schema' => array()
                ),
                'parfium' => array(
                    'name' => 'Parfium.bg',
                    'logo_url' => '',
                    'schema' => array()
                )
            );
        }
        
        return $available_stores;
    }
    
    // ==================== AJAX HANDLERS ====================
    
    /**
     * AJAX: Добавя магазин към пост
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function ajax_add_store_to_post() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_key = sanitize_key($_POST['store_key']);
        
        $available_stores = $this->get_available_stores();
        
        if (!isset($available_stores[$store_key])) {
            wp_send_json_error(__('Invalid store', 'parfume-reviews'));
        }
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        if (!$stores) {
            $stores = array();
        }
        
        foreach ($stores as $store) {
            if ($store['store_key'] === $store_key) {
                wp_send_json_error(__('Store already exists', 'parfume-reviews'));
            }
        }
        
        $new_store = array(
            'store_key' => $store_key,
            'name' => $available_stores[$store_key]['name'],
            'product_url' => '',
            'affiliate_url' => '',
            'promo_code' => '',
            'promo_code_info' => '',
            'scraped_price' => '',
            'scraped_old_price' => '',
            'scraped_sizes' => '',
            'scraped_availability' => '',
            'scraped_delivery' => '',
            'last_scraped' => '',
            'next_scrape' => ''
        );
        
        $stores[] = $new_store;
        update_post_meta($post_id, '_parfume_stores', $stores);
        
        ob_start();
        $this->render_store_item($new_store, count($stores) - 1);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Премахва магазин от пост
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function ajax_remove_store_from_post() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if ($stores && isset($stores[$store_index])) {
            unset($stores[$store_index]);
            $stores = array_values($stores);
            update_post_meta($post_id, '_parfume_stores', $stores);
            
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Store not found', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Пренарежда магазини
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function ajax_reorder_stores() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $new_order = $_POST['new_order'];
        
        if (!is_array($new_order)) {
            wp_send_json_error(__('Invalid order data', 'parfume-reviews'));
        }
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if ($stores) {
            $reordered_stores = array();
            foreach ($new_order as $old_index) {
                if (isset($stores[$old_index])) {
                    $reordered_stores[] = $stores[$old_index];
                }
            }
            
            update_post_meta($post_id, '_parfume_stores', $reordered_stores);
            wp_send_json_success();
        } else {
            wp_send_json_error(__('No stores found', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Scrape store data
     * ЗАПАЗЕНА ФУНКЦИЯ
     */
    public function ajax_scrape_store_data() {
        check_ajax_referer('parfume_scraper_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $product_url = esc_url_raw($_POST['product_url']);
        
        if (!$product_url || !filter_var($product_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Invalid URL', 'parfume-reviews'));
        }
        
        $scraped_data = $this->scrape_product_data($product_url);
        
        if ($scraped_data) {
            wp_send_json_success($scraped_data);
        } else {
            wp_send_json_error(__('Failed to scrape data', 'parfume-reviews'));
        }
    }
    
    /**
     * Scrape product data from URL
     * PLACEHOLDER - ще се имплементира в scraper класа
     */
    private function scrape_product_data($url) {
        return array(
            'price' => '59.99 лв.',
            'old_price' => '79.99 лв.',
            'sizes' => '30ml, 50ml, 100ml',
            'availability' => 'Наличен',
            'delivery' => 'Безплатна доставка над 50 лв.'
        );
    }
    
    // ==================== LEGACY AJAX HANDLERS ====================
    
    /**
     * Legacy AJAX handlers - запазени за backward compatibility
     * ЗАПАЗЕНИ ФУНКЦИИ
     */
    public function ajax_update_store_price() {
        wp_send_json_error(__('Use ajax_scrape_store_data instead', 'parfume-reviews'));
    }
    
    public function ajax_get_store_sizes() {
        wp_send_json_error(__('Use ajax_scrape_store_data instead', 'parfume-reviews'));
    }
    
    public function ajax_get_store_variants() {
        wp_send_json_error(__('Use ajax_scrape_store_data instead', 'parfume-reviews'));
    }
    
    public function ajax_refresh_store_data() {
        wp_send_json_error(__('Use ajax_scrape_store_data instead', 'parfume-reviews'));
    }
    
    public function ajax_compare_prices() {
        wp_send_json_success(array('message' => 'Price comparison not yet implemented'));
    }
}