<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Stores class - С ПЪЛЕН DEBUG
 * 
 * ФАЙЛ: includes/settings/class-settings-stores.php
 * DEBUG VERSION - Проследява защо магазините не се записват
 */
class Settings_Stores {
    
    public function __construct() {
        // AJAX handlers - ВАЖНО: Регистрирани правилно
        add_action('wp_ajax_parfume_add_new_store', array($this, 'ajax_add_new_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_upload_store_logo', array($this, 'ajax_upload_store_logo'));
        
        // Debug hook
        add_action('admin_init', array($this, 'debug_ajax_handlers'));
    }
    
    /**
     * DEBUG: Проверява дали AJAX handlers са регистрирани
     */
    public function debug_ajax_handlers() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $ajax_actions = array(
            'wp_ajax_parfume_add_new_store',
            'wp_ajax_parfume_delete_store',
            'wp_ajax_parfume_upload_store_logo'
        );
        
        foreach ($ajax_actions as $action) {
            $has_action = has_action($action);
            error_log("Stores Settings DEBUG: {$action} - " . ($has_action ? 'REGISTERED' : 'NOT REGISTERED'));
        }
    }
    
    /**
     * Регистрира settings
     */
    public function register_settings() {
        // Stores настройките се записват в главната опция
    }
    
    /**
     * Sanitize stores settings
     * КРИТИЧНО: ВИНАГИ запазва available_stores от базата
     */
    public function sanitize($input) {
        $sanitized = array();
        
        error_log('=== STORES SANITIZE CALLED ===');
        error_log('Input keys: ' . print_r(array_keys($input), true));
        error_log('Has available_stores in input: ' . (isset($input['available_stores']) ? 'YES' : 'NO'));
        
        // КРИТИЧНО: available_stores НИКОГА не идва от POST при натискане на Save Changes
        // Защото stores tab-ът не изпраща available_stores полета в главната форма
        // Затова ВИНАГИ запазваме текущата стойност от базата
        
        $old_settings = get_option('parfume_reviews_settings', array());
        
        if (isset($old_settings['available_stores']) && is_array($old_settings['available_stores'])) {
            // ВИНАГИ запазваме stores от базата
            $sanitized['available_stores'] = $old_settings['available_stores'];
            error_log('✅ STORES PRESERVED from database: ' . count($sanitized['available_stores']) . ' stores');
            error_log('Store IDs: ' . implode(', ', array_keys($sanitized['available_stores'])));
        } else {
            error_log('⚠️ No stores found in old settings');
            $sanitized['available_stores'] = array();
        }
        
        // Ако по някаква причина available_stores е в POST (не би трябвало при normal Save)
        // игнорираме го, защото AJAX handler-ите се грижат за добавяне/изтриване
        if (isset($input['available_stores'])) {
            error_log('⚠️ WARNING: available_stores detected in POST input - this should not happen on normal Save!');
        }
        
        error_log('=== STORES SANITIZE COMPLETED ===');
        error_log('Returning ' . count($sanitized['available_stores']) . ' stores');
        
        return $sanitized;
    }
    
    /**
     * Рендерира stores settings секция
     */
    public function render_section() {
        $settings = get_option('parfume_reviews_settings', array());
        $stores = isset($settings['available_stores']) && is_array($settings['available_stores']) 
            ? $settings['available_stores'] 
            : array();
        
        // DEBUG информация
        if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['debug'])) {
            echo '<div class="notice notice-info" style="margin-bottom: 20px;">';
            echo '<h4>🔍 Stores Debug Info:</h4>';
            echo '<ul style="list-style: disc; margin-left: 20px;">';
            echo '<li><strong>Available stores count:</strong> ' . count($stores) . '</li>';
            echo '<li><strong>AJAX action registered:</strong> ' . (has_action('wp_ajax_parfume_add_new_store') ? '✅ Yes' : '❌ No') . '</li>';
            echo '<li><strong>ajaxurl:</strong> <code>' . admin_url('admin-ajax.php') . '</code></li>';
            echo '<li><strong>Current nonce:</strong> <code>' . wp_create_nonce('parfume_stores_nonce') . '</code></li>';
            echo '</ul>';
            
            if (!empty($stores)) {
                echo '<details><summary>Show all stores data</summary>';
                echo '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">';
                echo esc_html(print_r($stores, true));
                echo '</pre></details>';
            }
            echo '</div>';
        }
        
        ?>
        <div class="stores-settings-wrapper">
            <h2><?php _e('Управление на магазини', 'parfume-reviews'); ?></h2>
            <p class="description"><?php _e('Добавете и управлявайте онлайн магазини за парфюми.', 'parfume-reviews'); ?></p>
            
            <!-- Добавяне на нов магазин -->
            <div class="add-new-store-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3><?php _e('Добави нов магазин', 'parfume-reviews'); ?></h3>
                <form id="add-store-form" class="add-store-form">
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-field">
                            <label for="store_name"><?php _e('Име на магазина *', 'parfume-reviews'); ?></label>
                            <input type="text" id="store_name" name="store_name" required class="regular-text">
                        </div>
                        <div class="form-field">
                            <label for="store_url"><?php _e('URL на магазина', 'parfume-reviews'); ?></label>
                            <input type="url" id="store_url" name="store_url" placeholder="https://example.com" class="regular-text">
                        </div>
                    </div>
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-field">
                            <label for="store_affiliate_id"><?php _e('Affiliate ID', 'parfume-reviews'); ?></label>
                            <input type="text" id="store_affiliate_id" name="store_affiliate_id" class="regular-text">
                        </div>
                        <div class="form-field">
                            <label for="store_promo_code"><?php _e('Промо код', 'parfume-reviews'); ?></label>
                            <input type="text" id="store_promo_code" name="store_promo_code" class="regular-text">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="add-store-btn" class="button button-primary">
                            <?php _e('Добави магазин', 'parfume-reviews'); ?>
                        </button>
                        <span id="add-store-status" style="margin-left: 10px;"></span>
                    </div>
                </form>
            </div>
            
            <!-- Списък със съществуващи магазини -->
            <div class="existing-stores-section" style="margin-top: 30px;">
                <h3><?php _e('Съществуващи магазини', 'parfume-reviews'); ?> (<?php echo count($stores); ?>)</h3>
                <div id="stores-list" class="stores-list" style="margin-top: 15px;">
                    <?php if (!empty($stores) && is_array($stores)): ?>
                        <?php foreach ($stores as $store_id => $store): ?>
                            <?php $this->render_store_item($store_id, $store); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-stores" style="text-align: center; padding: 40px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">
                            <?php _e('Няма добавени магазини. Добавете първия магазин по-горе.', 'parfume-reviews'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .stores-list {
            display: grid;
            gap: 15px;
        }
        .store-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        .store-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .store-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .store-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .store-details h4 {
            margin: 0 0 5px 0;
        }
        .store-meta {
            font-size: 12px;
            color: #666;
        }
        .store-actions {
            display: flex;
            gap: 10px;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('🔍 Stores Settings JS loaded');
            console.log('ajaxurl:', ajaxurl);
            
            // Добавяне на нов магазин
            $('#add-store-form').on('submit', function(e) {
                e.preventDefault();
                
                console.log('=== ADD STORE FORM SUBMITTED ===');
                
                var $form = $(this);
                var $btn = $('#add-store-btn');
                var $status = $('#add-store-status');
                
                var formData = {
                    action: 'parfume_add_new_store',
                    store_name: $('#store_name').val(),
                    store_url: $('#store_url').val(),
                    store_affiliate_id: $('#store_affiliate_id').val(),
                    store_promo_code: $('#store_promo_code').val(),
                    nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                };
                
                console.log('Form data:', formData);
                
                // Validation
                if (!formData.store_name || formData.store_name.trim() === '') {
                    alert('<?php echo esc_js(__('Моля въведете име на магазина', 'parfume-reviews')); ?>');
                    return;
                }
                
                // Show loading state
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Добавяне...', 'parfume-reviews')); ?>');
                $status.html('<span style="color: #0073aa;">⏳ Изпращане...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log('=== AJAX RESPONSE ===');
                        console.log('Success:', response.success);
                        console.log('Data:', response.data);
                        
                        if (response.success) {
                            // Премахваме "no stores" съобщението
                            $('#stores-list .no-stores').remove();
                            
                            // Добавяме новия магазин
                            $('#stores-list').prepend(response.data.html);
                            
                            // Reset формата
                            $form[0].reset();
                            
                            // Success message
                            $status.html('<span style="color: #46b450;">✅ Магазинът е добавен!</span>');
                            
                            // Показваме notification
                            if (typeof showNotification === 'function') {
                                showNotification('<?php echo esc_js(__('Магазинът е добавен успешно!', 'parfume-reviews')); ?>', 'success');
                            }
                            
                            // Highlight новия магазин
                            $('#stores-list .store-item:first').css('background', '#e8f5e9');
                            setTimeout(function() {
                                $('#stores-list .store-item:first').css('background', '');
                            }, 2000);
                            
                            setTimeout(function() {
                                $status.html('');
                            }, 3000);
                            
                        } else {
                            console.error('Error:', response.data);
                            
                            var errorMsg = response.data || '<?php echo esc_js(__('Грешка при добавяне на магазин', 'parfume-reviews')); ?>';
                            $status.html('<span style="color: #dc3232;">❌ ' + errorMsg + '</span>');
                            
                            alert('<?php echo esc_js(__('Грешка:', 'parfume-reviews')); ?> ' + errorMsg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('=== AJAX ERROR ===');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        
                        $status.html('<span style="color: #dc3232;">❌ AJAX грешка</span>');
                        
                        alert('<?php echo esc_js(__('AJAX грешка при добавяне на магазин. Проверете конзолата.', 'parfume-reviews')); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Добави магазин', 'parfume-reviews')); ?>');
                    }
                });
            });
            
            // Изтриване на магазин
            $(document).on('click', '.delete-store-btn', function(e) {
                e.preventDefault();
                
                if (!confirm('<?php echo esc_js(__('Сигурни ли сте че искате да изтриете този магазин?', 'parfume-reviews')); ?>')) {
                    return;
                }
                
                var $btn = $(this);
                var $storeItem = $btn.closest('.store-item');
                var storeId = $btn.data('store-id');
                
                console.log('Deleting store:', storeId);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_delete_store',
                        store_id: storeId,
                        nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $storeItem.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Ако няма повече магазини, показваме "no stores"
                                if ($('#stores-list .store-item').length === 0) {
                                    $('#stores-list').html('<p class="no-stores" style="text-align: center; padding: 40px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;"><?php echo esc_js(__('Няма добавени магазини. Добавете първия магазин по-горе.', 'parfume-reviews')); ?></p>');
                                }
                            });
                            
                            if (typeof showNotification === 'function') {
                                showNotification('<?php echo esc_js(__('Магазинът е изтрит', 'parfume-reviews')); ?>', 'success');
                            }
                        } else {
                            alert('<?php echo esc_js(__('Грешка при изтриване', 'parfume-reviews')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('AJAX грешка', 'parfume-reviews')); ?>');
                    }
                });
            });
        });
        
        // Notification function
        function showNotification(message, type) {
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 99999; min-width: 300px;"><p>' + message + '</p></div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        </script>
        <?php
    }
    
    /**
     * Рендерира един store item
     */
    private function render_store_item($store_id, $store) {
        if (!is_array($store)) {
            return;
        }
        
        $name = $store['name'] ?? 'Unknown Store';
        $url = $store['url'] ?? '';
        $logo = $store['logo'] ?? '';
        $affiliate_id = $store['affiliate_id'] ?? '';
        $promo_code = $store['promo_code'] ?? '';
        $status = $store['status'] ?? 'active';
        
        ?>
        <div class="store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-info">
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($name); ?>" class="store-logo">
                <?php else: ?>
                    <div class="store-logo" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                        <span class="dashicons dashicons-store" style="font-size: 32px;"></span>
                    </div>
                <?php endif; ?>
                
                <div class="store-details">
                    <h4><?php echo esc_html($name); ?></h4>
                    <div class="store-meta">
                        <?php if ($url): ?>
                            <span class="store-url">🔗 <a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_html(parse_url($url, PHP_URL_HOST)); ?></a></span><br>
                        <?php endif; ?>
                        <?php if ($affiliate_id): ?>
                            <span class="store-affiliate">🔑 Affiliate: <?php echo esc_html($affiliate_id); ?></span><br>
                        <?php endif; ?>
                        <?php if ($promo_code): ?>
                            <span class="store-promo">🎟️ Promo: <code><?php echo esc_html($promo_code); ?></code></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="store-actions">
                <span class="store-status <?php echo $status === 'active' ? 'active' : 'inactive'; ?>" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; <?php echo $status === 'active' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
                    <?php echo $status === 'active' ? '✓ Активен' : '✗ Неактивен'; ?>
                </span>
                <button type="button" class="button delete-store-btn" data-store-id="<?php echo esc_attr($store_id); ?>" style="color: #dc3232;">
                    <span class="dashicons dashicons-trash"></span> <?php _e('Изтрий', 'parfume-reviews'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Добавя нов магазин
     * ПОДОБРЕНА ВЕРСИЯ с ПЪЛЕН DEBUG
     */
    public function ajax_add_new_store() {
        error_log('=== AJAX ADD NEW STORE CALLED ===');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Nonce проверка
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'parfume_stores_nonce')) {
            error_log('❌ Nonce check FAILED');
            wp_send_json_error(__('Security check failed', 'parfume-reviews'));
            return;
        }
        error_log('✅ Nonce check passed');
        
        // Permissions проверка
        if (!current_user_can('manage_options')) {
            error_log('❌ User lacks manage_options capability');
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
            return;
        }
        error_log('✅ User has manage_options capability');
        
        // Валидация на данни
        $store_name = isset($_POST['store_name']) ? sanitize_text_field($_POST['store_name']) : '';
        $store_url = isset($_POST['store_url']) ? esc_url_raw($_POST['store_url']) : '';
        $affiliate_id = isset($_POST['store_affiliate_id']) ? sanitize_text_field($_POST['store_affiliate_id']) : '';
        $promo_code = isset($_POST['store_promo_code']) ? sanitize_text_field($_POST['store_promo_code']) : '';
        
        error_log('Store name: ' . $store_name);
        error_log('Store URL: ' . $store_url);
        
        if (empty($store_name)) {
            error_log('❌ Store name is empty');
            wp_send_json_error(__('Store name is required', 'parfume-reviews'));
            return;
        }
        error_log('✅ Store name is valid');
        
        // Получаваме текущите настройки
        $settings = get_option('parfume_reviews_settings', array());
        error_log('Current settings keys: ' . print_r(array_keys($settings), true));
        
        if (!isset($settings['available_stores'])) {
            $settings['available_stores'] = array();
            error_log('Created available_stores array');
        }
        
        $current_stores_count = count($settings['available_stores']);
        error_log('Current stores count: ' . $current_stores_count);
        
        // Генерираме уникален ключ
        $store_key = sanitize_key($store_name);
        $counter = 1;
        $original_key = $store_key;
        
        while (isset($settings['available_stores'][$store_key])) {
            $store_key = $original_key . '_' . $counter;
            $counter++;
        }
        
        error_log('Generated store key: ' . $store_key);
        
        // Създаваме новия магазин
        $new_store = array(
            'name' => $store_name,
            'url' => $store_url,
            'logo' => '',
            'affiliate_id' => $affiliate_id,
            'promo_code' => $promo_code,
            'status' => 'active',
            'schema' => array()
        );
        
        error_log('New store data: ' . print_r($new_store, true));
        
        // Добавяме към масива
        $settings['available_stores'][$store_key] = $new_store;
        
        error_log('Attempting to save settings...');
        error_log('New stores count: ' . count($settings['available_stores']));
        
        // Опит за запазване
        $update_result = update_option('parfume_reviews_settings', $settings);
        
        if ($update_result) {
            error_log('✅ Settings saved successfully!');
            
            // Verify save
            $verify_settings = get_option('parfume_reviews_settings', array());
            $verify_count = isset($verify_settings['available_stores']) ? count($verify_settings['available_stores']) : 0;
            error_log('Verification: stores in DB = ' . $verify_count);
            
            if ($verify_count > $current_stores_count) {
                error_log('✅ Store confirmed in database');
            } else {
                error_log('⚠️ Store count did not increase after save!');
            }
            
            // Генерираме HTML за новия магазин
            ob_start();
            $this->render_store_item($store_key, $new_store);
            $html = ob_get_clean();
            
            error_log('Generated HTML length: ' . strlen($html));
            
            wp_send_json_success(array(
                'html' => $html,
                'store_id' => $store_key,
                'message' => __('Store added successfully', 'parfume-reviews')
            ));
            
        } else {
            error_log('❌ Failed to save settings to database');
            error_log('Update result: ' . var_export($update_result, true));
            
            // Проверка за database грешки
            global $wpdb;
            if ($wpdb->last_error) {
                error_log('MySQL Error: ' . $wpdb->last_error);
            }
            
            wp_send_json_error(__('Failed to save store. Check debug log for details.', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Изтрива магазин
     */
    public function ajax_delete_store() {
        error_log('=== AJAX DELETE STORE CALLED ===');
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'parfume_stores_nonce')) {
            wp_send_json_error(__('Security check failed', 'parfume-reviews'));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
            return;
        }
        
        $store_id = isset($_POST['store_id']) ? sanitize_key($_POST['store_id']) : '';
        
        if (empty($store_id)) {
            wp_send_json_error(__('Store ID is required', 'parfume-reviews'));
            return;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!isset($settings['available_stores'][$store_id])) {
            error_log('Store not found: ' . $store_id);
            wp_send_json_error(__('Store not found', 'parfume-reviews'));
            return;
        }
        
        // Премахваме магазина
        unset($settings['available_stores'][$store_id]);
        
        if (update_option('parfume_reviews_settings', $settings)) {
            error_log('✅ Store deleted: ' . $store_id);
            wp_send_json_success(array('message' => __('Store deleted successfully', 'parfume-reviews')));
        } else {
            error_log('❌ Failed to delete store: ' . $store_id);
            wp_send_json_error(__('Failed to delete store', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Качва лого
     */
    public function ajax_upload_store_logo() {
        // Implementation for logo upload
        wp_send_json_error(__('Logo upload not yet implemented', 'parfume-reviews'));
    }
}