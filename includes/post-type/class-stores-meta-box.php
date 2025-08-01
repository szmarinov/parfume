<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Stores_Meta_Box class - Управлява meta box за магазини в parfume постове
 * 
 * Файл: includes/post-type/class-stores-meta-box.php
 * Създаден за "Колона 2" функционалността
 */
class Stores_Meta_Box {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX actions за управление на магазини в post
        add_action('wp_ajax_parfume_add_post_store', array($this, 'ajax_add_post_store'));
        add_action('wp_ajax_parfume_remove_post_store', array($this, 'ajax_remove_post_store'));
        add_action('wp_ajax_parfume_update_store_order', array($this, 'ajax_update_store_order'));
        add_action('wp_ajax_parfume_scrape_store_data', array($this, 'ajax_scrape_store_data'));
    }
    
    /**
     * Добавя meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_stores_meta_box',
            __('Магазини за "Колона 2"', 'parfume-reviews'),
            array($this, 'render_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        // Mobile настройки meta box
        add_meta_box(
            'parfume_mobile_settings_meta_box',
            __('Mobile настройки за този пост', 'parfume-reviews'),
            array($this, 'render_mobile_settings_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts и styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if ($post_type !== 'parfume') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();
        
        wp_enqueue_script(
            'parfume-stores-meta-box',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-stores-meta-box.js',
            array('jquery', 'jquery-ui-sortable'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-stores-meta-box', 'parfumeStoresMetaBox', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_stores_meta_box_nonce'),
            'strings' => array(
                'confirm_remove' => __('Сигурни ли сте, че искате да премахнете този магазин?', 'parfume-reviews'),
                'scraping' => __('Скрейпване...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
                'success' => __('Успешно', 'parfume-reviews'),
                'no_product_url' => __('Моля въведете Product URL преди скрейпване.', 'parfume-reviews'),
            )
        ));
        
        wp_enqueue_style(
            'parfume-stores-meta-box',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-stores-meta-box.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
    }
    
    /**
     * Рендерира main meta box за магазини
     */
    public function render_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_box', 'parfume_stores_meta_box_nonce');
        
        $stores_data = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            $stores_data = array();
        }
        
        $available_stores = get_option('parfume_reviews_stores', array());
        ?>
        <div class="parfume-stores-meta-box">
            <!-- Добавяне на нов магазин -->
            <div class="add-store-section">
                <h4><?php _e('Добави магазин', 'parfume-reviews'); ?></h4>
                <div class="add-store-form">
                    <select id="available-stores-select">
                        <option value=""><?php _e('Избери магазин...', 'parfume-reviews'); ?></option>
                        <?php foreach ($available_stores as $store_id => $store): ?>
                            <?php if (!isset($stores_data[$store_id])): ?>
                                <option value="<?php echo esc_attr($store_id); ?>">
                                    <?php echo esc_html($store['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button" id="add-store-btn">
                        <?php _e('Добави', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Списък с добавени магазини -->
            <div class="stores-list-section">
                <h4><?php _e('Добавени магазини', 'parfume-reviews'); ?></h4>
                <div id="stores-list" class="stores-sortable">
                    <?php if (empty($stores_data)): ?>
                        <p class="no-stores"><?php _e('Няма добавени магазини. Добавете магазин от падащото меню по-горе.', 'parfume-reviews'); ?></p>
                    <?php else: ?>
                        <?php foreach ($stores_data as $store_id => $store_data): ?>
                            <?php $this->render_store_item($store_id, $store_data, $available_stores); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира individual store item
     */
    private function render_store_item($store_id, $store_data, $available_stores) {
        $store_info = isset($available_stores[$store_id]) ? $available_stores[$store_id] : array();
        $store_name = isset($store_info['name']) ? $store_info['name'] : __('Неизвестен магазин', 'parfume-reviews');
        $logo_id = isset($store_info['logo_id']) ? $store_info['logo_id'] : 0;
        
        // Scraped data
        $scraped_data = isset($store_data['scraped_data']) ? $store_data['scraped_data'] : array();
        $last_scraped = isset($store_data['last_scraped']) ? $store_data['last_scraped'] : '';
        $scrape_status = isset($store_data['scrape_status']) ? $store_data['scrape_status'] : 'pending';
        ?>
        <div class="store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-header">
                <div class="store-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                
                <?php if ($logo_id): ?>
                    <div class="store-logo">
                        <?php echo wp_get_attachment_image($logo_id, 'thumbnail', false, array('style' => 'width: 40px; height: auto;')); ?>
                    </div>
                <?php endif; ?>
                
                <div class="store-info">
                    <h5><?php echo esc_html($store_name); ?></h5>
                </div>
                
                <div class="store-actions">
                    <button type="button" class="button-small toggle-store-details">
                        <?php _e('Детайли', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="button-small remove-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <?php _e('Премахни', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
            
            <div class="store-details" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_url_<?php echo esc_attr($store_id); ?>">
                                <?php _e('Product URL', 'parfume-reviews'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="url" id="product_url_<?php echo esc_attr($store_id); ?>" 
                                   name="parfume_stores[<?php echo esc_attr($store_id); ?>][product_url]" 
                                   value="<?php echo esc_attr(isset($store_data['product_url']) ? $store_data['product_url'] : ''); ?>" 
                                   class="large-text" />
                            <p class="description"><?php _e('URL към продукта в този магазин', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="affiliate_url_<?php echo esc_attr($store_id); ?>">
                                <?php _e('Affiliate URL', 'parfume-reviews'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="url" id="affiliate_url_<?php echo esc_attr($store_id); ?>" 
                                   name="parfume_stores[<?php echo esc_attr($store_id); ?>][affiliate_url]" 
                                   value="<?php echo esc_attr(isset($store_data['affiliate_url']) ? $store_data['affiliate_url'] : ''); ?>" 
                                   class="large-text" />
                            <p class="description"><?php _e('Affiliate линк към продукта (target="_blank" и rel="nofollow" се добавят автоматично)', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="promo_code_<?php echo esc_attr($store_id); ?>">
                                <?php _e('Promo Code', 'parfume-reviews'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="promo_code_<?php echo esc_attr($store_id); ?>" 
                                   name="parfume_stores[<?php echo esc_attr($store_id); ?>][promo_code]" 
                                   value="<?php echo esc_attr(isset($store_data['promo_code']) ? $store_data['promo_code'] : ''); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Промо код за отстъпка (опционално)', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="promo_code_info_<?php echo esc_attr($store_id); ?>">
                                <?php _e('Promo Code Info', 'parfume-reviews'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="promo_code_info_<?php echo esc_attr($store_id); ?>" 
                                   name="parfume_stores[<?php echo esc_attr($store_id); ?>][promo_code_info]" 
                                   value="<?php echo esc_attr(isset($store_data['promo_code_info']) ? $store_data['promo_code_info'] : ''); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Информация за промо кода (напр. "-15% отстъпка")', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <!-- Scraped Data Section -->
                <div class="scraped-data-section">
                    <h4><?php _e('Скрейпнати данни', 'parfume-reviews'); ?></h4>
                    
                    <div class="scrape-controls">
                        <button type="button" class="button scrape-now-btn" data-store-id="<?php echo esc_attr($store_id); ?>">
                            <?php _e('Ръчно обновяване', 'parfume-reviews'); ?>
                        </button>
                        
                        <?php if (!empty($last_scraped)): ?>
                            <span class="last-scraped">
                                <?php _e('Последно обновяване:', 'parfume-reviews'); ?>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_scraped))); ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="scrape-status status-<?php echo esc_attr($scrape_status); ?>">
                            <?php
                            switch ($scrape_status) {
                                case 'success':
                                    _e('Успешно', 'parfume-reviews');
                                    break;
                                case 'error':
                                    _e('Грешка', 'parfume-reviews');
                                    break;
                                case 'pending':
                                    _e('Изчакване', 'parfume-reviews');
                                    break;
                                default:
                                    echo esc_html($scrape_status);
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="scraped-data-display">
                        <?php if (!empty($scraped_data)): ?>
                            <div class="scraped-info">
                                <?php if (!empty($scraped_data['prices'])): ?>
                                    <div class="scraped-item">
                                        <strong><?php _e('Цени:', 'parfume-reviews'); ?></strong>
                                        <?php echo esc_html(implode(', ', $scraped_data['prices'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraped_data['variants'])): ?>
                                    <div class="scraped-item">
                                        <strong><?php _e('Разфасовки:', 'parfume-reviews'); ?></strong>
                                        <?php echo esc_html(implode(', ', $scraped_data['variants'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraped_data['availability'])): ?>
                                    <div class="scraped-item">
                                        <strong><?php _e('Наличност:', 'parfume-reviews'); ?></strong>
                                        <?php echo esc_html($scraped_data['availability']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraped_data['shipping'])): ?>
                                    <div class="scraped-item">
                                        <strong><?php _e('Доставка:', 'parfume-reviews'); ?></strong>
                                        <?php echo esc_html($scraped_data['shipping']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-scraped-data"><?php _e('Няма скрейпнати данни. Въведете Product URL и натиснете "Ръчно обновяване".', 'parfume-reviews'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Hidden field за order -->
            <input type="hidden" name="parfume_stores[<?php echo esc_attr($store_id); ?>][order]" 
                   value="<?php echo esc_attr(isset($store_data['order']) ? $store_data['order'] : 0); ?>" class="store-order" />
        </div>
        <?php
    }
    
    /**
     * Рендерира mobile settings meta box
     */
    public function render_mobile_settings_meta_box($post) {
        wp_nonce_field('parfume_mobile_settings_meta_box', 'parfume_mobile_settings_meta_box_nonce');
        
        $mobile_settings = get_post_meta($post->ID, '_parfume_mobile_settings', true);
        if (!is_array($mobile_settings)) {
            $mobile_settings = array();
        }
        
        // Global settings за reference
        $global_mobile_settings = get_option('parfume_reviews_mobile_settings', array());
        ?>
        <div class="parfume-mobile-settings-meta-box">
            <p class="description"><?php _e('Тези настройки имат приоритет пред глобалните mobile настройки.', 'parfume-reviews'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Използвай фиксиран панел', 'parfume-reviews'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="parfume_mobile_settings[fixed_panel_override]" value="" 
                                       <?php checked(empty($mobile_settings['fixed_panel_override'])); ?> />
                                <?php _e('Използвай глобалната настройка', 'parfume-reviews'); ?>
                                <?php if (isset($global_mobile_settings['fixed_panel_enabled'])): ?>
                                    <span class="description">(<?php echo $global_mobile_settings['fixed_panel_enabled'] ? __('включено', 'parfume-reviews') : __('изключено', 'parfume-reviews'); ?>)</span>
                                <?php endif; ?>
                            </label><br>
                            <label>
                                <input type="radio" name="parfume_mobile_settings[fixed_panel_override]" value="1" 
                                       <?php checked($mobile_settings['fixed_panel_override'], '1'); ?> />
                                <?php _e('Включи за този пост', 'parfume-reviews'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="parfume_mobile_settings[fixed_panel_override]" value="0" 
                                       <?php checked($mobile_settings['fixed_panel_override'], '0'); ?> />
                                <?php _e('Изключи за този пост', 'parfume-reviews'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Записва meta box данните
     */
    public function save_meta_box($post_id, $post) {
        // Проверки за сигурност
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
        
        if ($post->post_type !== 'parfume') {
            return;
        }
        
        // Записваме stores данни
        if (isset($_POST['parfume_stores'])) {
            $stores_data = array();
            
            foreach ($_POST['parfume_stores'] as $store_id => $store_data) {
                $stores_data[sanitize_key($store_id)] = array(
                    'product_url' => esc_url_raw($store_data['product_url']),
                    'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                    'promo_code' => sanitize_text_field($store_data['promo_code']),
                    'promo_code_info' => sanitize_text_field($store_data['promo_code_info']),
                    'order' => intval($store_data['order']),
                    // Запазваме съществуващите scraped данни
                    'scraped_data' => isset($existing_data[$store_id]['scraped_data']) ? $existing_data[$store_id]['scraped_data'] : array(),
                    'last_scraped' => isset($existing_data[$store_id]['last_scraped']) ? $existing_data[$store_id]['last_scraped'] : '',
                    'scrape_status' => isset($existing_data[$store_id]['scrape_status']) ? $existing_data[$store_id]['scrape_status'] : 'pending'
                );
            }
            
            update_post_meta($post_id, '_parfume_stores', $stores_data);
        } else {
            delete_post_meta($post_id, '_parfume_stores');
        }
        
        // Записваме mobile settings
        if (isset($_POST['parfume_mobile_settings'])) {
            $mobile_settings = array();
            
            if (!empty($_POST['parfume_mobile_settings']['fixed_panel_override'])) {
                $mobile_settings['fixed_panel_override'] = sanitize_text_field($_POST['parfume_mobile_settings']['fixed_panel_override']);
            }
            
            if (!empty($mobile_settings)) {
                update_post_meta($post_id, '_parfume_mobile_settings', $mobile_settings);
            } else {
                delete_post_meta($post_id, '_parfume_mobile_settings');
            }
        }
    }
    
    /**
     * AJAX handler за добавяне на магазин към post
     */
    public function ajax_add_post_store() {
        check_ajax_referer('parfume_stores_meta_box_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_key($_POST['store_id']);
        
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            $stores_data = array();
        }
        
        // Проверяваме дали магазинът вече не е добавен
        if (isset($stores_data[$store_id])) {
            wp_send_json_error(array('message' => __('Този магазин вече е добавен.', 'parfume-reviews')));
        }
        
        // Добавяме новия магазин
        $stores_data[$store_id] = array(
            'product_url' => '',
            'affiliate_url' => '',
            'promo_code' => '',
            'promo_code_info' => '',
            'order' => count($stores_data),
            'scraped_data' => array(),
            'last_scraped' => '',
            'scrape_status' => 'pending'
        );
        
        update_post_meta($post_id, '_parfume_stores', $stores_data);
        
        // Генерираме HTML за новия елемент
        $available_stores = get_option('parfume_reviews_stores', array());
        
        ob_start();
        $this->render_store_item($store_id, $stores_data[$store_id], $available_stores);
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'message' => __('Магазинът е добавен успешно.', 'parfume-reviews')
        ));
    }
    
    /**
     * AJAX handler за премахване на магазин от post
     */
    public function ajax_remove_post_store() {
        check_ajax_referer('parfume_stores_meta_box_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_key($_POST['store_id']);
        
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            wp_send_json_error(array('message' => __('Няма данни за магазини.', 'parfume-reviews')));
        }
        
        if (isset($stores_data[$store_id])) {
            unset($stores_data[$store_id]);
            update_post_meta($post_id, '_parfume_stores', $stores_data);
            wp_send_json_success(array('message' => __('Магазинът е премахнат успешно.', 'parfume-reviews')));
        } else {
            wp_send_json_error(array('message' => __('Магазинът не е намерен.', 'parfume-reviews')));
        }
    }
    
    /**
     * AJAX handler за обновяване на подредбата на магазини
     */
    public function ajax_update_store_order() {
        check_ajax_referer('parfume_stores_meta_box_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_order = $_POST['store_order']; // array of store_ids in new order
        
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            wp_send_json_error(array('message' => __('Няма данни за магазини.', 'parfume-reviews')));
        }
        
        // Обновяваме order полетата
        foreach ($store_order as $index => $store_id) {
            if (isset($stores_data[$store_id])) {
                $stores_data[$store_id]['order'] = $index;
            }
        }
        
        update_post_meta($post_id, '_parfume_stores', $stores_data);
        
        wp_send_json_success(array('message' => __('Подредбата е обновена успешно.', 'parfume-reviews')));
    }
    
    /**
     * AJAX handler за scrape на store данни
     */
    public function ajax_scrape_store_data() {
        check_ajax_referer('parfume_stores_meta_box_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_key($_POST['store_id']);
        
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($stores_data) || !isset($stores_data[$store_id])) {
            wp_send_json_error(array('message' => __('Магазинът не е намерен.', 'parfume-reviews')));
        }
        
        $product_url = $stores_data[$store_id]['product_url'];
        if (empty($product_url)) {
            wp_send_json_error(array('message' => __('Няма зададен Product URL.', 'parfume-reviews')));
        }
        
        // Използваме scraper класа
        if (class_exists('Parfume_Reviews\\Settings\\Settings_Scraper')) {
            $scraper = new \Parfume_Reviews\Settings\Settings_Scraper();
            $scrape_result = $scraper->scrape_product_page($product_url);
            
            if (is_wp_error($scrape_result)) {
                $stores_data[$store_id]['scrape_status'] = 'error';
                $stores_data[$store_id]['last_error'] = $scrape_result->get_error_message();
                $stores_data[$store_id]['last_scraped'] = current_time('mysql');
                
                update_post_meta($post_id, '_parfume_stores', $stores_data);
                
                wp_send_json_error(array('message' => $scrape_result->get_error_message()));
            }
            
            // Записваме успешните данни
            $stores_data[$store_id]['scraped_data'] = $scrape_result;
            $stores_data[$store_id]['scrape_status'] = 'success';
            $stores_data[$store_id]['last_scraped'] = current_time('mysql');
            unset($stores_data[$store_id]['last_error']);
            
            update_post_meta($post_id, '_parfume_stores', $stores_data);
            
            wp_send_json_success(array(
                'message' => __('Скрейпването завърши успешно.', 'parfume-reviews'),
                'scraped_data' => $scrape_result
            ));
        } else {
            wp_send_json_error(array('message' => __('Scraper класът не е наличен.', 'parfume-reviews')));
        }
    }
}