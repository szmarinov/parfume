<?php
/**
 * Stores Meta Fields Class
 * 
 * Handles store-related meta fields for parfumes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Meta_Stores {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_parfume_manual_scrape_store', array($this, 'manual_scrape_store'));
        add_action('wp_ajax_parfume_add_store_to_post', array($this, 'add_store_to_post'));
        add_action('wp_ajax_parfume_remove_store_from_post', array($this, 'remove_store_from_post'));
        add_action('wp_ajax_parfume_reorder_post_stores', array($this, 'reorder_post_stores'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_stores',
            __('Магазини и цени', 'parfume-catalog'),
            array($this, 'render_stores_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('jquery-ui-sortable');
        }
    }
    
    /**
     * Render stores meta box
     */
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_nonce', 'parfume_stores_meta_nonce_field');
        
        $all_stores = Parfume_Admin_Stores::get_all_stores();
        $post_stores = get_post_meta($post->ID, '_parfume_stores', true) ?: array();
        
        if (!is_array($post_stores)) {
            $post_stores = array();
        }
        ?>
        <div class="stores-meta-container">
            <div class="stores-header">
                <h4><?php _e('Добавени магазини', 'parfume-catalog'); ?></h4>
                <div class="stores-actions">
                    <select id="available-stores">
                        <option value=""><?php _e('Изберете магазин за добавяне', 'parfume-catalog'); ?></option>
                        <?php foreach ($all_stores as $store_id => $store): ?>
                            <?php if (!isset($post_stores[$store_id])): ?>
                                <option value="<?php echo esc_attr($store_id); ?>">
                                    <?php echo esc_html($store['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-store-btn" class="button button-secondary">
                        <?php _e('Добави магазин', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div id="post-stores-list" class="post-stores-list">
                <?php if (empty($post_stores)): ?>
                    <div class="no-stores-message">
                        <p><?php _e('Няма добавени магазини. Изберете магазин от списъка по-горе.', 'parfume-catalog'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($post_stores as $store_id => $store_data): ?>
                        <?php if (isset($all_stores[$store_id])): ?>
                            <?php $this->render_store_item($store_id, $all_stores[$store_id], $store_data, $post->ID); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .stores-meta-container {
            margin-top: 15px;
        }
        
        .stores-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .stores-header h4 {
            margin: 0;
        }
        
        .stores-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .post-stores-list {
            min-height: 100px;
        }
        
        .store-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            background: #fff;
            position: relative;
        }
        
        .store-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
            cursor: move;
        }
        
        .store-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .store-logo {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 3px;
        }
        
        .store-logo-placeholder {
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
        }
        
        .store-name {
            font-weight: 600;
        }
        
        .store-actions {
            display: flex;
            gap: 5px;
        }
        
        .store-content {
            padding: 20px;
        }
        
        .store-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .field-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .field-group label {
            font-weight: 500;
            color: #555;
        }
        
        .scraped-data {
            background: #f0f8ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .scraped-data h5 {
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        
        .scraped-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .scraped-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .scraped-item-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }
        
        .scraped-item-value {
            font-size: 14px;
            color: #333;
        }
        
        .scraped-item-meta {
            font-size: 11px;
            color: #999;
        }
        
        .scrape-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .scrape-button:hover {
            background: #005a87;
        }
        
        .scrape-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .no-stores-message {
            text-align: center;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 4px;
            color: #666;
        }
        
        .sortable-placeholder {
            border: 2px dashed #0073aa;
            background: rgba(0, 115, 170, 0.1);
            height: 100px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Make stores sortable
            $('#post-stores-list').sortable({
                placeholder: 'sortable-placeholder',
                handle: '.store-item-header',
                update: function(event, ui) {
                    var order = [];
                    $('.store-item').each(function() {
                        order.push($(this).data('store-id'));
                    });
                    
                    $.post(ajaxurl, {
                        action: 'parfume_reorder_post_stores',
                        nonce: '<?php echo wp_create_nonce('parfume_store_action'); ?>',
                        post_id: <?php echo $post->ID; ?>,
                        store_order: order
                    });
                }
            });
            
            // Add store
            $('#add-store-btn').click(function() {
                var storeId = $('#available-stores').val();
                if (!storeId) {
                    alert('<?php _e('Моля изберете магазин', 'parfume-catalog'); ?>');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'parfume_add_store_to_post',
                    nonce: '<?php echo wp_create_nonce('parfume_store_action'); ?>',
                    post_id: <?php echo $post->ID; ?>,
                    store_id: storeId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при добавяне', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Remove store
            $(document).on('click', '.remove-store', function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да премахнете този магазин?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                var storeId = $(this).data('store-id');
                
                $.post(ajaxurl, {
                    action: 'parfume_remove_store_from_post',
                    nonce: '<?php echo wp_create_nonce('parfume_store_action'); ?>',
                    post_id: <?php echo $post->ID; ?>,
                    store_id: storeId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при премахване', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Manual scrape
            $(document).on('click', '.manual-scrape', function() {
                var button = $(this);
                var storeId = button.data('store-id');
                
                button.prop('disabled', true).text('<?php _e('Скрейпва...', 'parfume-catalog'); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_manual_scrape_store',
                    nonce: '<?php echo wp_create_nonce('parfume_store_action'); ?>',
                    post_id: <?php echo $post->ID; ?>,
                    store_id: storeId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        button.prop('disabled', false).text('<?php _e('Обнови', 'parfume-catalog'); ?>');
                        alert(response.data.message || '<?php _e('Грешка при скрейпване', 'parfume-catalog'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render individual store item
     */
    private function render_store_item($store_id, $store_info, $store_data, $post_id) {
        $logo_url = '';
        if (!empty($store_info['logo_id'])) {
            $logo_url = wp_get_attachment_image_url($store_info['logo_id'], 'thumbnail');
        }
        
        $scraped_data = $this->get_scraped_data($post_id, $store_id);
        ?>
        <div class="store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-item-header">
                <div class="store-info">
                    <span class="dashicons dashicons-move" style="color: #666; margin-right: 8px;"></span>
                    
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="" class="store-logo">
                    <?php else: ?>
                        <div class="store-logo-placeholder">
                            <?php _e('Лого', 'parfume-catalog'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <span class="store-name"><?php echo esc_html($store_info['name']); ?></span>
                </div>
                
                <div class="store-actions">
                    <button type="button" class="button button-small manual-scrape" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <?php _e('Обнови данни', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-small remove-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <?php _e('Премахни', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div class="store-content">
                <div class="store-fields">
                    <div class="field-group">
                        <label for="product_url_<?php echo esc_attr($store_id); ?>">
                            <?php _e('Product URL', 'parfume-catalog'); ?> *
                        </label>
                        <input type="url" 
                               id="product_url_<?php echo esc_attr($store_id); ?>" 
                               name="parfume_stores[<?php echo esc_attr($store_id); ?>][product_url]" 
                               value="<?php echo esc_attr($store_data['product_url'] ?? ''); ?>" 
                               placeholder="https://example.com/product" 
                               class="regular-text" />
                    </div>
                    
                    <div class="field-group">
                        <label for="affiliate_url_<?php echo esc_attr($store_id); ?>">
                            <?php _e('Affiliate URL', 'parfume-catalog'); ?>
                        </label>
                        <input type="url" 
                               id="affiliate_url_<?php echo esc_attr($store_id); ?>" 
                               name="parfume_stores[<?php echo esc_attr($store_id); ?>][affiliate_url]" 
                               value="<?php echo esc_attr($store_data['affiliate_url'] ?? ''); ?>" 
                               placeholder="https://affiliate.com/product" 
                               class="regular-text" />
                    </div>
                    
                    <div class="field-group">
                        <label for="promo_code_<?php echo esc_attr($store_id); ?>">
                            <?php _e('Promo Code', 'parfume-catalog'); ?>
                        </label>
                        <input type="text" 
                               id="promo_code_<?php echo esc_attr($store_id); ?>" 
                               name="parfume_stores[<?php echo esc_attr($store_id); ?>][promo_code]" 
                               value="<?php echo esc_attr($store_data['promo_code'] ?? ''); ?>" 
                               placeholder="SAVE20" 
                               class="regular-text" />
                    </div>
                    
                    <div class="field-group">
                        <label for="promo_code_info_<?php echo esc_attr($store_id); ?>">
                            <?php _e('Promo Code Info', 'parfume-catalog'); ?>
                        </label>
                        <input type="text" 
                               id="promo_code_info_<?php echo esc_attr($store_id); ?>" 
                               name="parfume_stores[<?php echo esc_attr($store_id); ?>][promo_code_info]" 
                               value="<?php echo esc_attr($store_data['promo_code_info'] ?? ''); ?>" 
                               placeholder="<?php _e('20% отстъпка', 'parfume-catalog'); ?>" 
                               class="regular-text" />
                    </div>
                </div>
                
                <?php if ($scraped_data): ?>
                    <div class="scraped-data">
                        <h5><?php _e('Скрейпнати данни', 'parfume-catalog'); ?></h5>
                        <div class="scraped-info">
                            <?php if (!empty($scraped_data['price'])): ?>
                                <div class="scraped-item">
                                    <div class="scraped-item-label"><?php _e('Цена', 'parfume-catalog'); ?></div>
                                    <div class="scraped-item-value"><?php echo esc_html($scraped_data['price']); ?></div>
                                    <div class="scraped-item-meta">
                                        <?php printf(__('Обновено: %s', 'parfume-catalog'), esc_html($scraped_data['price_updated'] ?? '')); ?>
                                        <button type="button" class="scrape-button manual-scrape" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Обнови', 'parfume-catalog'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($scraped_data['old_price'])): ?>
                                <div class="scraped-item">
                                    <div class="scraped-item-label"><?php _e('Стара цена', 'parfume-catalog'); ?></div>
                                    <div class="scraped-item-value"><?php echo esc_html($scraped_data['old_price']); ?></div>
                                    <div class="scraped-item-meta">
                                        <?php printf(__('Обновено: %s', 'parfume-catalog'), esc_html($scraped_data['price_updated'] ?? '')); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($scraped_data['ml_variants'])): ?>
                                <div class="scraped-item">
                                    <div class="scraped-item-label"><?php _e('Разфасовки', 'parfume-catalog'); ?></div>
                                    <div class="scraped-item-value">
                                        <?php echo esc_html(implode(', ', $scraped_data['ml_variants'])); ?>
                                    </div>
                                    <div class="scraped-item-meta">
                                        <?php printf(__('Обновено: %s', 'parfume-catalog'), esc_html($scraped_data['variants_updated'] ?? '')); ?>
                                        <button type="button" class="scrape-button manual-scrape" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Обнови', 'parfume-catalog'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($scraped_data['availability'])): ?>
                                <div class="scraped-item">
                                    <div class="scraped-item-label"><?php _e('Наличност', 'parfume-catalog'); ?></div>
                                    <div class="scraped-item-value"><?php echo esc_html($scraped_data['availability']); ?></div>
                                    <div class="scraped-item-meta">
                                        <?php printf(__('Обновено: %s', 'parfume-catalog'), esc_html($scraped_data['availability_updated'] ?? '')); ?>
                                        <button type="button" class="scrape-button manual-scrape" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Обнови', 'parfume-catalog'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($scraped_data['delivery'])): ?>
                                <div class="scraped-item">
                                    <div class="scraped-item-label"><?php _e('Доставка', 'parfume-catalog'); ?></div>
                                    <div class="scraped-item-value"><?php echo esc_html($scraped_data['delivery']); ?></div>
                                    <div class="scraped-item-meta">
                                        <?php printf(__('Обновено: %s', 'parfume-catalog'), esc_html($scraped_data['delivery_updated'] ?? '')); ?>
                                        <button type="button" class="scrape-button manual-scrape" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Обнови', 'parfume-catalog'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($scraped_data['next_scrape'])): ?>
                            <p style="margin-top: 10px; font-size: 12px; color: #666;">
                                <?php printf(__('Следващо автоматично обновяване: %s', 'parfume-catalog'), esc_html($scraped_data['next_scrape'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="scraped-data">
                        <p style="margin: 0; color: #666;">
                            <?php _e('Няма скрейпнати данни. Попълнете Product URL и натиснете "Обнови данни".', 'parfume-catalog'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['parfume_stores_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['parfume_stores_meta_nonce_field'], 'parfume_stores_meta_nonce')) {
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save stores data
        if (isset($_POST['parfume_stores'])) {
            $stores_data = array();
            
            foreach ($_POST['parfume_stores'] as $store_id => $store_data) {
                $stores_data[sanitize_text_field($store_id)] = array(
                    'product_url' => esc_url_raw($store_data['product_url']),
                    'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                    'promo_code' => sanitize_text_field($store_data['promo_code']),
                    'promo_code_info' => sanitize_text_field($store_data['promo_code_info'])
                );
            }
            
            update_post_meta($post_id, '_parfume_stores', $stores_data);
        } else {
            update_post_meta($post_id, '_parfume_stores', array());
        }
    }
    
    /**
     * Get scraped data for store
     */
    private function get_scraped_data($post_id, $store_id) {
        global $wpdb;
        
        // Mock scraped data - would normally come from scraper database
        return array(
            'price' => '59.99 лв.',
            'old_price' => '79.99 лв.',
            'ml_variants' => array('30ml', '50ml', '100ml'),
            'availability' => 'В наличност',
            'delivery' => 'Безплатна доставка над 50 лв.',
            'price_updated' => date('d.m.Y H:i'),
            'variants_updated' => date('d.m.Y H:i'),
            'availability_updated' => date('d.m.Y H:i'),
            'delivery_updated' => date('d.m.Y H:i'),
            'next_scrape' => date('d.m.Y H:i', strtotime('+12 hours'))
        );
    }
    
    /**
     * Add store to post via AJAX
     */
    public function add_store_to_post() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true) ?: array();
        
        if (!isset($post_stores[$store_id])) {
            $post_stores[$store_id] = array(
                'product_url' => '',
                'affiliate_url' => '',
                'promo_code' => '',
                'promo_code_info' => ''
            );
            
            update_post_meta($post_id, '_parfume_stores', $post_stores);
            wp_send_json_success(array('message' => __('Магазинът е добавен', 'parfume-catalog')));
        } else {
            wp_send_json_error(array('message' => __('Магазинът вече е добавен', 'parfume-catalog')));
        }
    }
    
    /**
     * Remove store from post via AJAX
     */
    public function remove_store_from_post() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true) ?: array();
        
        if (isset($post_stores[$store_id])) {
            unset($post_stores[$store_id]);
            update_post_meta($post_id, '_parfume_stores', $post_stores);
            wp_send_json_success(array('message' => __('Магазинът е премахнат', 'parfume-catalog')));
        } else {
            wp_send_json_error(array('message' => __('Магазинът не е намерен', 'parfume-catalog')));
        }
    }
    
    /**
     * Reorder stores via AJAX
     */
    public function reorder_post_stores() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $store_order = array_map('sanitize_text_field', $_POST['store_order']);
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true) ?: array();
        $reordered_stores = array();
        
        // Reorder according to new order
        foreach ($store_order as $store_id) {
            if (isset($post_stores[$store_id])) {
                $reordered_stores[$store_id] = $post_stores[$store_id];
            }
        }
        
        update_post_meta($post_id, '_parfume_stores', $reordered_stores);
        wp_send_json_success(array('message' => __('Редът е обновен', 'parfume-catalog')));
    }
    
    /**
     * Manual scrape store via AJAX
     */
    public function manual_scrape_store() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Mock scraping - would normally call actual scraper
        wp_send_json_success(array('message' => __('Данните са обновени', 'parfume-catalog')));
    }
    
    /**
     * Get post stores
     */
    public static function get_post_stores($post_id) {
        return get_post_meta($post_id, '_parfume_stores', true) ?: array();
    }
    
    /**
     * Get store data for post
     */
    public static function get_store_data($post_id, $store_id) {
        $post_stores = self::get_post_stores($post_id);
        return isset($post_stores[$store_id]) ? $post_stores[$store_id] : array();
    }
    
    /**
     * Check if post has stores
     */
    public static function has_stores($post_id) {
        $post_stores = self::get_post_stores($post_id);
        return !empty($post_stores);
    }
    
    /**
     * Get formatted store data for frontend display
     */
    public static function get_formatted_stores($post_id) {
        $post_stores = self::get_post_stores($post_id);
        $all_stores = Parfume_Admin_Stores::get_all_stores();
        $formatted_stores = array();
        
        foreach ($post_stores as $store_id => $store_data) {
            if (isset($all_stores[$store_id])) {
                $store_info = $all_stores[$store_id];
                
                $formatted_stores[$store_id] = array(
                    'name' => $store_info['name'],
                    'logo_url' => !empty($store_info['logo_id']) ? wp_get_attachment_image_url($store_info['logo_id'], 'thumbnail') : '',
                    'product_url' => $store_data['product_url'],
                    'affiliate_url' => $store_data['affiliate_url'],
                    'promo_code' => $store_data['promo_code'],
                    'promo_code_info' => $store_data['promo_code_info'],
                    'scraped_data' => array() // Would contain actual scraped data
                );
            }
        }
        
        return $formatted_stores;
    }
}

// Initialize the stores meta fields
new Parfume_Meta_Stores();