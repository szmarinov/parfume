<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Stores class - Управлява настройките за магазини
 * 
 * Файл: includes/settings/class-settings-stores.php
 * FIXED VERSION: Поправени count() грешки и променен affiliate ID на affiliate линк
 */
class Settings_Stores {
    
    public function __construct() {
        // AJAX хукове за магазините
        add_action('wp_ajax_parfume_add_store', array($this, 'ajax_add_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_toggle_store_status', array($this, 'ajax_toggle_store_status'));
        add_action('wp_ajax_parfume_edit_store', array($this, 'ajax_edit_store'));
        add_action('wp_ajax_parfume_upload_store_logo', array($this, 'ajax_upload_store_logo'));
        
        // DEBUG AJAX хукове за тестване
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_ajax_parfume_test_add_store', array($this, 'ajax_test_add_store'));
            add_action('wp_ajax_parfume_clear_stores', array($this, 'ajax_clear_stores'));
            add_action('wp_ajax_parfume_fix_option_type', array($this, 'ajax_fix_option_type'));
        }
    }
    
    /**
     * Регистрира настройките за stores
     */
    public function register_settings() {
        // Stores Section
        add_settings_section(
            'parfume_reviews_stores_section',
            __('Управление на магазини', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Управлявайте налични магазини за парфюми. Тези магазини ще могат да бъдат добавяни към individual парфюми.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с stores управление
     */
    public function render_section() {
        $available_stores = get_option('parfume_reviews_stores', array());
        
        // FIXED: Проверяваме дали е array преди да използваме count()
        if (!is_array($available_stores)) {
            $available_stores = array();
        }
        
    /**
     * Рендерира секцията с stores управление
     */
    public function render_section() {
        $available_stores = get_option('parfume_reviews_stores', array());
        
        // CRITICAL FIX: Проверяваме дали е array и поправяме ако не е
        if (!is_array($available_stores)) {
            $available_stores = array();
            // Форсираме правилния тип в базата данни
            update_option('parfume_reviews_stores', $available_stores);
        }
        
        // DEBUG: Добавяме debug информация ако WP_DEBUG е включен
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<div class="notice notice-info" style="padding: 10px; margin-bottom: 20px;">';
            echo '<strong>Debug Info:</strong><br>';
            echo 'Stores array type: ' . gettype($available_stores) . '<br>';
            echo 'Stores count: ' . count($available_stores) . '<br>';
            echo 'Stores data: <pre>' . print_r($available_stores, true) . '</pre>';
            
            // DIAGNOSTIC: Проверяваме direct database query
            global $wpdb;
            $db_value = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'parfume_reviews_stores'
            ));
            echo '<strong>Direct DB Value:</strong> ' . var_export($db_value, true) . '<br>';
            echo '<strong>DB Value Type:</strong> ' . gettype($db_value) . '<br>';
            
            // TEST BUTTON: Добавяме тест бутон за директно добавяне на магазин
            echo '<hr><strong>Test Functions:</strong><br>';
            echo '<button type="button" id="test-add-store" class="button button-secondary">Test Add Store Directly</button> ';
            echo '<button type="button" id="test-clear-stores" class="button button-secondary">Clear All Stores</button> ';
            echo '<button type="button" id="fix-option-type" class="button button-primary">Fix Option Type</button>';
            
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#test-add-store").on("click", function() {
                        $.post(ajaxurl, {
                            action: "parfume_test_add_store",
                            nonce: parfumeSettings.nonce
                        }, function(response) {
                            alert("Test result: " + JSON.stringify(response));
                            location.reload();
                        });
                    });
                    
                    $("#test-clear-stores").on("click", function() {
                        if (confirm("Clear all stores?")) {
                            $.post(ajaxurl, {
                                action: "parfume_clear_stores",
                                nonce: parfumeSettings.nonce
                            }, function(response) {
                                alert("Clear result: " + JSON.stringify(response));
                                location.reload();
                            });
                        }
                    });
                    
                    $("#fix-option-type").on("click", function() {
                        $.post(ajaxurl, {
                            action: "parfume_fix_option_type",
                            nonce: parfumeSettings.nonce
                        }, function(response) {
                            alert("Fix result: " + JSON.stringify(response));
                            location.reload();
                        });
                    });
                });
            </script>';
            
            echo '</div>';
        }
        
        ?>
        <div class="stores-management">
            <?php $this->render_add_store_form(); ?>
            <?php $this->render_stores_list($available_stores); ?>
            <?php $this->render_stores_statistics($available_stores); ?>
        </div>
        
        <style>
        .stores-management {
            max-width: 100%;
        }
        .add-store-form {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .add-store-form h3 {
            margin-top: 0;
            color: #0073aa;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 8px 12px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        .stores-list {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .stores-list h3 {
            margin: 0;
            padding: 15px 20px;
            background: #f1f1f1;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        .store-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f1;
            transition: background-color 0.3s ease;
        }
        .store-item:hover {
            background: #f8f9fa;
        }
        .store-item:last-child {
            border-bottom: none;
        }
        .store-logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .store-logo img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 4px;
        }
        .store-logo .dashicons {
            font-size: 24px;
            color: #666;
        }
        .store-info {
            flex: 1;
        }
        .store-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-url {
            color: #666;
            font-size: 14px;
        }
        .store-url a {
            color: #0073aa;
            text-decoration: none;
        }
        .store-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .store-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .store-status.active {
            background: #d4edda;
            color: #155724;
        }
        .store-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .stores-statistics {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }
        .stores-statistics h3 {
            margin-top: 0;
            color: #0073aa;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Debug: Log current stores
            console.log('Parfume Stores Settings loaded');
            
            // Add store form submission
            $('#add-store-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'parfume_add_store');
                formData.append('nonce', parfumeSettings.nonce);
                
                console.log('Submitting store data:', {
                    store_name: formData.get('store_name'),
                    store_url: formData.get('store_url'),
                    affiliate_id: formData.get('affiliate_id')
                });
                
                $.ajax({
                    url: parfumeSettings.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#add-store-form .button-primary').prop('disabled', true).text('Добавяне...');
                    },
                    success: function(response) {
                        console.log('AJAX Response:', response);
                        if (response.success) {
                            alert(response.data);
                            // FIXED: Презареждаме страницата след успешно добавяне
                            window.location.reload();
                        } else {
                            alert('Грешка: ' + response.data);
                            console.error('Server error:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                        alert('Възникна грешка при връзката със сървъра: ' + error);
                    },
                    complete: function() {
                        $('#add-store-form .button-primary').prop('disabled', false).text('Добави магазин');
                    }
                });
            });
            
            // Delete store
            $('.delete-store').on('click', function() {
                var storeId = $(this).data('store-id');
                
                if (confirm('Сигурни ли сте, че искате да изтриете този магазин?')) {
                    $.post(parfumeSettings.ajax_url, {
                        action: 'parfume_delete_store',
                        store_id: storeId,
                        nonce: parfumeSettings.nonce
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Грешка: ' + response.data);
                        }
                    }).fail(function() {
                        alert('Грешка при връзката със сървъра.');
                    });
                }
            });
            
            // Toggle store status
            $('.toggle-status').on('click', function() {
                var storeId = $(this).data('store-id');
                var button = $(this);
                
                $.post(parfumeSettings.ajax_url, {
                    action: 'parfume_toggle_store_status',
                    store_id: storeId,
                    nonce: parfumeSettings.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Грешка: ' + response.data);
                    }
                }).fail(function() {
                    alert('Грешка при връзката със сървъра.');
                });
            });
            
            // Debug: Check if we have stores data
            if (typeof parfumeSettings !== 'undefined') {
                console.log('Parfume Settings available:', parfumeSettings);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Рендерира формата за добавяне на магазин
     */
    private function render_add_store_form() {
        ?>
        <div class="add-store-form">
            <h3><?php _e('Добави нов магазин', 'parfume-reviews'); ?></h3>
            
            <form id="add-store-form" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="store-name"><?php _e('Име на магазина:', 'parfume-reviews'); ?></label>
                        <input type="text" id="store-name" name="store_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="store-url"><?php _e('URL на магазина:', 'parfume-reviews'); ?></label>
                        <input type="url" id="store-url" name="store_url" placeholder="https://example.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="affiliate-link"><?php _e('Affiliate линк:', 'parfume-reviews'); ?></label>
                        <input type="text" id="affiliate-link" name="affiliate_id" placeholder="https://example.com/?ref=youraffiliateID">
                        <small class="description"><?php _e('Пълният affiliate линк за този магазин', 'parfume-reviews'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="commission-rate"><?php _e('Комисионна (%):', 'parfume-reviews'); ?></label>
                        <input type="number" id="commission-rate" name="commission_rate" min="0" max="100" step="0.1">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="store-status"><?php _e('Статус:', 'parfume-reviews'); ?></label>
                        <select id="store-status" name="store_status">
                            <option value="active"><?php _e('Активен', 'parfume-reviews'); ?></option>
                            <option value="inactive"><?php _e('Неактивен', 'parfume-reviews'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="store-priority"><?php _e('Приоритет (1-10):', 'parfume-reviews'); ?></label>
                        <input type="number" id="store-priority" name="store_priority" min="1" max="10" value="5">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="store-logo"><?php _e('Лого на магазина:', 'parfume-reviews'); ?></label>
                        <input type="file" id="store-logo" name="store_logo" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="store-description"><?php _e('Описание:', 'parfume-reviews'); ?></label>
                        <textarea id="store-description" name="store_description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Добави магазин', 'parfume-reviews'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира списъка с магазини
     */
    private function render_stores_list($stores) {
        ?>
        <div class="stores-list">
            <h3><?php _e('Налични магазини', 'parfume-reviews'); ?></h3>
            
            <?php 
            // ENHANCED DEBUG: Подробна debug информация
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<div class="notice notice-info" style="margin: 10px 0; padding: 10px;">';
                echo '<strong>EXTENDED DEBUG INFO:</strong><br>';
                echo 'Raw option value: ';
                var_dump(get_option('parfume_reviews_stores'));
                echo '<br>Is array: ' . (is_array($stores) ? 'YES' : 'NO') . '<br>';
                echo 'Count: ' . count($stores) . '<br>';
                echo 'Empty check: ' . (empty($stores) ? 'YES (EMPTY)' : 'NO (NOT EMPTY)') . '<br>';
                if (!empty($stores)) {
                    echo 'First store key: ' . array_key_first($stores) . '<br>';
                    echo 'Store keys: ' . implode(', ', array_keys($stores)) . '<br>';
                }
                echo '</div>';
            }
            ?>
            
            <?php if (empty($stores)): ?>
                <div style="padding: 20px; text-align: center; color: #666; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <p><strong><?php _e('Няма добавени магазини.', 'parfume-reviews'); ?></strong></p>
                    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                        <p style="font-size: 12px; color: #666;">
                            DEBUG: $stores is <?php echo gettype($stores); ?> with count <?php echo count($stores); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php 
                // FIXED: Проверяваме дали е array преди foreach
                if (is_array($stores)) {
                    foreach ($stores as $store_id => $store): 
                        // FIXED: Проверяваме дали $store е array
                        if (!is_array($store)) {
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                echo '<div class="notice notice-warning"><p>Store ' . $store_id . ' is not an array: ' . gettype($store) . '</p></div>';
                            }
                            continue;
                        }
                ?>
                    <div class="store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <div class="store-logo">
                            <?php if (!empty($store['logo'])): ?>
                                <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>">
                            <?php else: ?>
                                <span class="dashicons dashicons-store"></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="store-info">
                            <div class="store-name"><?php echo esc_html($store['name']); ?></div>
                            <?php if (!empty($store['url'])): ?>
                                <div class="store-url">
                                    <a href="<?php echo esc_url($store['url']); ?>" target="_blank"><?php echo esc_html($store['url']); ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($store['affiliate_id'])): ?>
                                <div class="store-affiliate">
                                    <small><?php _e('Affiliate:', 'parfume-reviews'); ?> <?php echo esc_html($store['affiliate_id']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="store-actions">
                            <span class="store-status <?php echo esc_attr($store['status']); ?>">
                                <?php echo $store['status'] === 'active' ? __('Активен', 'parfume-reviews') : __('Неактивен', 'parfume-reviews'); ?>
                            </span>
                            
                            <button type="button" class="button button-secondary toggle-status" data-store-id="<?php echo esc_attr($store_id); ?>">
                                <?php echo $store['status'] === 'active' ? __('Деактивирай', 'parfume-reviews') : __('Активирай', 'parfume-reviews'); ?>
                            </button>
                            
                            <button type="button" class="button edit-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                <?php _e('Редактирай', 'parfume-reviews'); ?>
                            </button>
                            
                            <button type="button" class="button button-secondary delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                <?php _e('Изтрий', 'parfume-reviews'); ?>
                            </button>
                        </div>
                    </div>
                <?php 
                    endforeach;
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        echo '<div class="notice notice-error"><p>$stores is not an array: ' . gettype($stores) . '</p></div>';
                    }
                }
                ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Рендерира статистики за магазините
     */
    private function render_stores_statistics($stores) {
        $stats = $this->get_stores_statistics($stores);
        ?>
        <div class="stores-statistics">
            <h3><?php _e('Статистики за магазини', 'parfume-reviews'); ?></h3>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($stats['total_stores']); ?></span>
                    <span class="stat-label"><?php _e('Общо магазини', 'parfume-reviews'); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($stats['active_stores']); ?></span>
                    <span class="stat-label"><?php _e('Активни магазини', 'parfume-reviews'); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($stats['stores_with_logos']); ?></span>
                    <span class="stat-label"><?php _e('Магазини с лого', 'parfume-reviews'); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($stats['stores_in_use']); ?></span>
                    <span class="stat-label"><?php _e('Използвани в постове', 'parfume-reviews'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler за добавяне на магазин
     */
    public function ajax_add_store() {
        // FIXED: Използваме правилният nonce name
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(__('Невалиден nonce.', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        $store_name = sanitize_text_field($_POST['store_name']);
        $store_url = esc_url_raw($_POST['store_url']);
        $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
        $commission_rate = floatval($_POST['commission_rate']);
        $store_status = sanitize_text_field($_POST['store_status']);
        $store_priority = intval($_POST['store_priority']);
        $store_description = sanitize_textarea_field($_POST['store_description']);
        
        if (empty($store_name)) {
            wp_send_json_error(__('Името на магазина е задължително.', 'parfume-reviews'));
        }
        
        $available_stores = get_option('parfume_reviews_stores', array());
        
        // FIXED: Проверяваме дали е array
        if (!is_array($available_stores)) {
            $available_stores = array();
        }
        
        // Проверяваме за дублиране
        foreach ($available_stores as $existing_store) {
            if (is_array($existing_store) && isset($existing_store['name']) && strtolower($existing_store['name']) === strtolower($store_name)) {
                wp_send_json_error(__('Магазин с това име вече съществува.', 'parfume-reviews'));
            }
        }
        
        // Upload лого
        $logo_url = '';
        if (!empty($_FILES['store_logo']['name'])) {
            $upload_result = $this->handle_logo_upload($_FILES['store_logo']);
            if (is_wp_error($upload_result)) {
                wp_send_json_error($upload_result->get_error_message());
            }
            $logo_url = $upload_result;
        }
        
        // Генерираме ID за магазина
        $store_id = sanitize_title($store_name) . '_' . time();
        
        // Добавяме новия магазин
        $available_stores[$store_id] = array(
            'name' => $store_name,
            'url' => $store_url,
            'logo' => $logo_url,
            'affiliate_id' => $affiliate_id,
            'commission_rate' => $commission_rate,
            'status' => $store_status,
            'priority' => $store_priority,
            'description' => $store_description,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // DEBUG: Log преди запазването
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Adding store - ' . print_r($available_stores[$store_id], true));
        }
        
        $result = update_option('parfume_reviews_stores', $available_stores);
        
        // DEBUG: Log резултата
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Update option result - ' . ($result ? 'SUCCESS' : 'FAILED'));
            error_log('Parfume Reviews: Stores after save - ' . print_r(get_option('parfume_reviews_stores', array()), true));
        }
        
        if ($result) {
            wp_send_json_success(__('Магазинът е добавен успешно.', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Грешка при запазване на магазина.', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX handler за изтриване на магазин
     */
    public function ajax_delete_store() {
        // FIXED: Използваме правилният nonce name
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(__('Невалиден nonce.', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $available_stores = get_option('parfume_reviews_stores', array());
        
        // FIXED: Проверяваме дали е array
        if (!is_array($available_stores)) {
            $available_stores = array();
        }
        
        if (!isset($available_stores[$store_id])) {
            wp_send_json_error(__('Магазинът не съществува.', 'parfume-reviews'));
        }
        
        // Изтриваме логото ако има такова
        if (!empty($available_stores[$store_id]['logo'])) {
            $this->delete_store_logo($available_stores[$store_id]['logo']);
        }
        
        unset($available_stores[$store_id]);
        update_option('parfume_reviews_stores', $available_stores);
        
        wp_send_json_success(__('Магазинът е изтрит успешно.', 'parfume-reviews'));
    }
    
    /**
     * AJAX handler за toggle на статус на магазин
     */
    public function ajax_toggle_store_status() {
        // FIXED: Използваме правилният nonce name
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(__('Невалиден nonce.', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $available_stores = get_option('parfume_reviews_stores', array());
        
        // FIXED: Проверяваме дали е array
        if (!is_array($available_stores)) {
            $available_stores = array();
        }
        
        if (!isset($available_stores[$store_id])) {
            wp_send_json_error(__('Магазинът не съществува.', 'parfume-reviews'));
        }
        
        $current_status = $available_stores[$store_id]['status'];
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
        
        $available_stores[$store_id]['status'] = $new_status;
        $available_stores[$store_id]['updated_at'] = current_time('mysql');
        
        update_option('parfume_reviews_stores', $available_stores);
        
        wp_send_json_success(sprintf(
            __('Статусът на магазина е променен на %s.', 'parfume-reviews'), 
            $new_status === 'active' ? __('активен', 'parfume-reviews') : __('неактивен', 'parfume-reviews'))
        );
    }
    
    /**
     * AJAX handler за редактиране на магазин
     */
    public function ajax_edit_store() {
        // FIXED: Използваме правилният nonce name
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(__('Невалиден nonce.', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $available_stores = get_option('parfume_reviews_stores', array());
        
        // FIXED: Проверяваме дали е array
        if (!is_array($available_stores)) {
            $available_stores = array();
        }
        
        if (!isset($available_stores[$store_id])) {
            wp_send_json_error(__('Магазинът не съществува.', 'parfume-reviews'));
        }
        
        // Обновяваме данните
        $available_stores[$store_id]['name'] = sanitize_text_field($_POST['store_name']);
        $available_stores[$store_id]['url'] = esc_url_raw($_POST['store_url']);
        $available_stores[$store_id]['affiliate_id'] = sanitize_text_field($_POST['affiliate_id']);
        $available_stores[$store_id]['commission_rate'] = floatval($_POST['commission_rate']);
        $available_stores[$store_id]['priority'] = intval($_POST['store_priority']);
        $available_stores[$store_id]['description'] = sanitize_textarea_field($_POST['store_description']);
        $available_stores[$store_id]['updated_at'] = current_time('mysql');
        
        update_option('parfume_reviews_stores', $available_stores);
        
        wp_send_json_success(__('Магазинът е обновен успешно.', 'parfume-reviews'));
    }
    
    /**
     * AJAX handler за upload на лого
     */
    public function ajax_upload_store_logo() {
        // FIXED: Използваме правилният nonce name
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(__('Невалиден nonce.', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        if (empty($_FILES['logo'])) {
            wp_send_json_error(__('Няма качен файл.', 'parfume-reviews'));
        }
        
        $upload_result = $this->handle_logo_upload($_FILES['logo']);
        
        if (is_wp_error($upload_result)) {
            wp_send_json_error($upload_result->get_error_message());
        }
        
        wp_send_json_success(array(
            'logo_url' => $upload_result,
            'message' => __('Логото е качено успешно.', 'parfume-reviews')
        ));
    }
    
    /**
     * Обработва upload на store лого
     */
    private function handle_logo_upload($file) {
        if (empty($file['name'])) {
            return new \WP_Error('no_file', __('Няма избран файл.', 'parfume-reviews'));
        }
        
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($file['type'], $allowed_types)) {
            return new \WP_Error('invalid_file_type', __('Невалиден тип файл. Разрешени: JPG, PNG, GIF.', 'parfume-reviews'));
        }
        
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB
            return new \WP_Error('file_too_large', __('Файлът е твърде голям. Максимален размер: 2MB.', 'parfume-reviews'));
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) {
                return 'store-logo-' . time() . '-' . $name;
            }
        );
        
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return new \WP_Error('upload_error', $uploaded_file['error']);
        }
        
        return $uploaded_file['url'];
    }
    
    /**
     * Изтрива store лого
     */
    private function delete_store_logo($logo_url) {
        if (empty($logo_url)) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $logo_url);
        
        if (file_exists($file_path)) {
            wp_delete_file($file_path);
        }
    }
    
    /**
     * Получава статистики за магазините
     */
    private function get_stores_statistics($stores) {
        // FIXED: Проверяваме дали е array преди count()
        if (!is_array($stores)) {
            $stores = array();
        }
        
        $stats = array(
            'total_stores' => count($stores),
            'active_stores' => 0,
            'stores_with_logos' => 0,
            'stores_in_use' => 0
        );
        
        foreach ($stores as $store) {
            // FIXED: Проверяваме дали $store е array
            if (!is_array($store)) {
                continue;
            }
            
            if (isset($store['status']) && $store['status'] === 'active') {
                $stats['active_stores']++;
            }
            
            if (isset($store['logo']) && !empty($store['logo'])) {
                $stats['stores_with_logos']++;
            }
        }
        
        // Броим колко от магазините се използват в постове
        global $wpdb;
        $stores_in_use = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores' 
            AND meta_value != '' 
            AND meta_value != 'a:0:{}'
        ");
        $stats['stores_in_use'] = intval($stores_in_use);
        
        return $stats;
    }
    
    /**
     * Получава всички настройки за export
     */
    public function get_all_settings() {
        $stores = get_option('parfume_reviews_stores', array());
        return is_array($stores) ? $stores : array();
    }
    
    /**
     * Валидира настройките преди запазване
     */
    public function validate_settings($input) {
        if (!is_array($input)) {
            return array();
        }
        
        $validated = array();
        
        foreach ($input as $store_id => $store) {
            if (!is_array($store)) continue;
            
            $validated[$store_id] = array(
                'name' => sanitize_text_field($store['name']),
                'url' => esc_url_raw($store['url']),
                'logo' => esc_url_raw($store['logo']),
                'affiliate_id' => sanitize_text_field($store['affiliate_id']),
                'commission_rate' => floatval($store['commission_rate']),
                'status' => in_array($store['status'], array('active', 'inactive')) ? $store['status'] : 'active',
                'priority' => intval($store['priority']),
                'description' => sanitize_textarea_field($store['description']),
                'created_at' => sanitize_text_field($store['created_at']),
                'updated_at' => current_time('mysql')
            );
        }
        
        return $validated;
    }
    
    /**
     * Получава конкретна настройка
     */
    public function get_setting($setting_name, $default = null) {
        $stores = get_option('parfume_reviews_stores', array());
        if (!is_array($stores)) {
            return $default;
        }
        
        return isset($stores[$setting_name]) ? $stores[$setting_name] : $default;
    }
    
    /**
     * DEBUG: Test AJAX handler за директно добавяне на магазин
     */
    public function ajax_test_add_store() {
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error('Невалиден nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Няmate права');
        }
        
        $test_store_id = 'test_store_' . time();
        $available_stores = get_option('parfume_reviews_stores', array());
        
        if (!is_array($available_stores)) {
            $available_stores = array();
        }
        
        $available_stores[$test_store_id] = array(
            'name' => 'Test Store ' . date('H:i:s'),
            'url' => 'https://test.com',
            'logo' => '',
            'affiliate_id' => 'test123',
            'commission_rate' => 5.0,
            'status' => 'active',
            'priority' => 5,
            'description' => 'Test store description',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = update_option('parfume_reviews_stores', $available_stores);
        
        wp_send_json_success(array(
            'message' => 'Test store добавен',
            'result' => $result,
            'store_id' => $test_store_id,
            'total_stores' => count($available_stores)
        ));
    }
    
    /**
     * DEBUG: AJAX handler за изчистване на всички магазини
     */
    public function ajax_clear_stores() {
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error('Невалиден nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Няmate права');
        }
        
        $result = delete_option('parfume_reviews_stores');
        
        wp_send_json_success(array(
            'message' => 'Всички магазини изчистени',
            'result' => $result
        ));
    }
    
    /**
     * DEBUG: AJAX handler за поправяне на option type
     */
    public function ajax_fix_option_type() {
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error('Невалиден nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Няmate права');
        }
        
        global $wpdb;
        
        // Получаваме текущата стойност от базата
        $current_value = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            'parfume_reviews_stores'
        ));
        
        // Изтриваме стария option
        delete_option('parfume_reviews_stores');
        
        // Добавяме нов с правилния тип
        $empty_array = array();
        $result = add_option('parfume_reviews_stores', $empty_array, '', 'yes');
        
        // Проверяваме резултата
        $new_value = get_option('parfume_reviews_stores', 'NOT_FOUND');
        
        wp_send_json_success(array(
            'message' => 'Option type поправен',
            'old_value' => $current_value,
            'old_type' => gettype($current_value),
            'new_value' => $new_value,
            'new_type' => gettype($new_value),
            'add_result' => $result
        ));
    }
}