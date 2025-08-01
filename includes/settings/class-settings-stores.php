<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Stores class - Управлява настройките за магазини
 * 
 * Файл: includes/settings/class-settings-stores.php
 * STORES SIDEBAR ФУНКЦИОНАЛНОСТ: Управление на магазини, логота, schema
 * SCRAPER TEST TOOL: Конфигурация и тестване на схеми за скрейпване
 */
class Settings_Stores {
    
    public function __construct() {
        // AJAX handlers за stores управление
        add_action('wp_ajax_parfume_add_new_store', array($this, 'ajax_add_new_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_upload_store_logo', array($this, 'ajax_upload_store_logo'));
        add_action('wp_ajax_parfume_test_store_schema', array($this, 'ajax_test_store_schema'));
        add_action('wp_ajax_parfume_save_store_schema', array($this, 'ajax_save_store_schema'));
    }
    
    /**
     * Рендерира stores settings секцията
     */
    public function render_section() {
        $stores = $this->get_all_stores();
        $stats = $this->get_stores_statistics($stores);
        
        ?>
        <div class="parfume-stores-settings">
            
            <!-- Stores статистики -->
            <div class="stores-stats">
                <h3><?php _e('Статистики за магазини', 'parfume-reviews'); ?></h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_stores']; ?></span>
                        <span class="stat-label"><?php _e('Общо магазини', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['active_stores']; ?></span>
                        <span class="stat-label"><?php _e('Активни магазини', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['stores_with_logos']; ?></span>
                        <span class="stat-label"><?php _e('Магазини с логота', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['stores_in_use']; ?></span>
                        <span class="stat-label"><?php _e('Използвани в постове', 'parfume-reviews'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Добавяне на нов магазин -->
            <div class="add-new-store-section">
                <h3><?php _e('Добави нов магазин', 'parfume-reviews'); ?></h3>
                <form id="add-store-form" class="add-store-form">
                    <div class="form-row">
                        <div class="form-field">
                            <label for="store_name"><?php _e('Име на магазина', 'parfume-reviews'); ?></label>
                            <input type="text" id="store_name" name="store_name" required>
                        </div>
                        <div class="form-field">
                            <label for="store_url"><?php _e('URL на магазина', 'parfume-reviews'); ?></label>
                            <input type="url" id="store_url" name="store_url" placeholder="https://example.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="store_affiliate_id"><?php _e('Affiliate ID', 'parfume-reviews'); ?></label>
                            <input type="text" id="store_affiliate_id" name="store_affiliate_id">
                        </div>
                        <div class="form-field">
                            <label for="store_commission_rate"><?php _e('Комисионна (%)', 'parfume-reviews'); ?></label>
                            <input type="number" id="store_commission_rate" name="store_commission_rate" min="0" max="100" step="0.1">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Добави магазин', 'parfume-reviews'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- Списък със съществуващи магазини -->
            <div class="existing-stores-section">
                <h3><?php _e('Съществуващи магазини', 'parfume-reviews'); ?></h3>
                <div id="stores-list" class="stores-list">
                    <?php if (!empty($stores)): ?>
                        <?php foreach ($stores as $store_id => $store): ?>
                            <?php $this->render_store_item($store_id, $store); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-stores"><?php _e('Няма добавени магазини.', 'parfume-reviews'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Scraper Test Tool секция -->
            <div class="scraper-test-tool-section">
                <h3><?php _e('Scraper Test Tool', 'parfume-reviews'); ?></h3>
                <p class="description"><?php _e('Тествайте и конфигурирайте схеми за скрейпване на продуктова информация.', 'parfume-reviews'); ?></p>
                
                <div class="test-url-form">
                    <div class="form-row">
                        <div class="form-field">
                            <label for="test_url"><?php _e('URL за тестване', 'parfume-reviews'); ?></label>
                            <input type="url" id="test_url" name="test_url" placeholder="https://example.com/product-page" class="regular-text">
                        </div>
                        <div class="form-field">
                            <label for="test_store_id"><?php _e('Магазин', 'parfume-reviews'); ?></label>
                            <select id="test_store_id" name="test_store_id">
                                <option value=""><?php _e('Изберете магазин...', 'parfume-reviews'); ?></option>
                                <?php foreach ($stores as $store_id => $store): ?>
                                    <option value="<?php echo esc_attr($store_id); ?>"><?php echo esc_html($store['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="test-scraper-btn" class="button button-primary"><?php _e('Скрейпни и анализирай', 'parfume-reviews'); ?></button>
                    </div>
                </div>
                
                <!-- Резултати от тестването -->
                <div id="scraper-test-results" class="scraper-test-results" style="display: none;">
                    <h4><?php _e('Резултати от анализа', 'parfume-reviews'); ?></h4>
                    <div id="test-results-content"></div>
                    
                    <div class="schema-configuration">
                        <h5><?php _e('Конфигурация на схемата', 'parfume-reviews'); ?></h5>
                        <form id="schema-config-form">
                            <div class="schema-fields">
                                <div class="schema-field">
                                    <label><?php _e('Селектор за цена', 'parfume-reviews'); ?></label>
                                    <input type="text" name="price_selector" class="schema-input">
                                    <span class="found-value"></span>
                                </div>
                                <div class="schema-field">
                                    <label><?php _e('Селектор за стара цена', 'parfume-reviews'); ?></label>
                                    <input type="text" name="old_price_selector" class="schema-input">
                                    <span class="found-value"></span>
                                </div>
                                <div class="schema-field">
                                    <label><?php _e('Селектор за разфасовки (ml)', 'parfume-reviews'); ?></label>
                                    <input type="text" name="ml_selector" class="schema-input">
                                    <span class="found-value"></span>
                                </div>
                                <div class="schema-field">
                                    <label><?php _e('Селектор за наличност', 'parfume-reviews'); ?></label>
                                    <input type="text" name="availability_selector" class="schema-input">
                                    <span class="found-value"></span>
                                </div>
                                <div class="schema-field">
                                    <label><?php _e('Селектор за доставка', 'parfume-reviews'); ?></label>
                                    <input type="text" name="delivery_selector" class="schema-input">
                                    <span class="found-value"></span>
                                </div>
                            </div>
                            <div class="schema-actions">
                                <button type="button" id="test-schema-btn" class="button"><?php _e('Тествай схемата', 'parfume-reviews'); ?></button>
                                <button type="button" id="save-schema-btn" class="button button-primary"><?php _e('Запази схемата', 'parfume-reviews'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .parfume-stores-settings {
            max-width: 1200px;
        }
        .stores-stats {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .stat-item {
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            display: block;
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
        }
        .stat-label {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 14px;
        }
        .add-store-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-field input, .form-field select {
            width: 100%;
        }
        .stores-list {
            margin-top: 20px;
        }
        .store-item {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            border-radius: 6px;
            position: relative;
        }
        .store-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .store-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .store-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 4px;
        }
        .store-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .store-actions {
            display: flex;
            gap: 10px;
        }
        .scraper-test-tool-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .test-url-form .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .scraper-test-results {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
            border: 1px solid #ddd;
        }
        .schema-fields {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .schema-field {
            display: grid;
            grid-template-columns: 200px 1fr 1fr;
            gap: 10px;
            align-items: center;
        }
        .schema-field label {
            font-weight: bold;
        }
        .schema-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .found-value {
            font-family: monospace;
            background: #e8f4fd;
            padding: 5px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .schema-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .logo-upload-field {
            margin-top: 10px;
        }
        .logo-preview {
            margin-top: 10px;
        }
        .current-logo {
            max-width: 100px;
            max-height: 50px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            // Добавяне на нов магазин
            $('#add-store-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'parfume_add_new_store',
                    store_name: $('#store_name').val(),
                    store_url: $('#store_url').val(),
                    store_affiliate_id: $('#store_affiliate_id').val(),
                    store_commission_rate: $('#store_commission_rate').val(),
                    nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                };
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        $('#stores-list').append(response.data.html);
                        $('#add-store-form')[0].reset();
                        $('.no-stores').hide();
                        alert('<?php echo esc_js(__('Магазинът е добавен успешно', 'parfume-reviews')); ?>');
                    } else {
                        alert('<?php echo esc_js(__('Грешка при добавяне на магазин:', 'parfume-reviews')); ?> ' + response.data);
                    }
                });
            });
            
            // Изтриване на магазин
            $(document).on('click', '.delete-store', function() {
                if (confirm('<?php echo esc_js(__('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-reviews')); ?>')) {
                    var $storeItem = $(this).closest('.store-item');
                    var storeId = $storeItem.data('store-id');
                    
                    $.post(ajaxurl, {
                        action: 'parfume_delete_store',
                        store_id: storeId,
                        nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $storeItem.remove();
                            if ($('#stores-list .store-item').length === 0) {
                                $('#stores-list').append('<p class="no-stores"><?php echo esc_js(__('Няма добавени магазини.', 'parfume-reviews')); ?></p>');
                            }
                        } else {
                            alert('<?php echo esc_js(__('Грешка при изтриване на магазин:', 'parfume-reviews')); ?> ' + response.data);
                        }
                    });
                }
            });
            
            // Scraper Test Tool функционалност
            $('#test-scraper-btn').on('click', function() {
                var testUrl = $('#test_url').val();
                var storeId = $('#test_store_id').val();
                
                if (!testUrl || !storeId) {
                    alert('<?php echo esc_js(__('Моля попълнете URL и изберете магазин', 'parfume-reviews')); ?>');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Анализира...', 'parfume-reviews')); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_test_store_schema',
                    test_url: testUrl,
                    store_id: storeId,
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_test_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#test-results-content').html(response.data.html);
                        $('#scraper-test-results').show();
                        
                        // Попълваме предложените селектори
                        if (response.data.suggestions) {
                            $.each(response.data.suggestions, function(field, selector) {
                                $('input[name="' + field + '"]').val(selector);
                            });
                        }
                        
                        // Показваме намерените стойности
                        if (response.data.found_values) {
                            $.each(response.data.found_values, function(field, value) {
                                $('input[name="' + field + '"]').siblings('.found-value').text(value);
                            });
                        }
                    } else {
                        alert('<?php echo esc_js(__('Грешка при анализиране:', 'parfume-reviews')); ?> ' + response.data);
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Скрейпни и анализирай', 'parfume-reviews')); ?>');
                });
            });
            
            // Тестване на схемата
            $('#test-schema-btn').on('click', function() {
                var testUrl = $('#test_url').val();
                var storeId = $('#test_store_id').val();
                var schemaData = {};
                
                $('.schema-input').each(function() {
                    var fieldName = $(this).attr('name');
                    var fieldValue = $(this).val();
                    if (fieldValue) {
                        schemaData[fieldName] = fieldValue;
                    }
                });
                
                if (!testUrl || !storeId || Object.keys(schemaData).length === 0) {
                    alert('<?php echo esc_js(__('Моля попълнете URL, магазин и поне един селектор', 'parfume-reviews')); ?>');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Тества...', 'parfume-reviews')); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_test_store_schema',
                    test_url: testUrl,
                    store_id: storeId,
                    schema_data: schemaData,
                    test_mode: true,
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_test_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        // Показваме резултатите от тестването
                        if (response.data.test_results) {
                            $.each(response.data.test_results, function(field, value) {
                                $('input[name="' + field + '"]').siblings('.found-value').text(value);
                            });
                        }
                        alert('<?php echo esc_js(__('Схемата е тестана успешно', 'parfume-reviews')); ?>');
                    } else {
                        alert('<?php echo esc_js(__('Грешка при тестване на схемата:', 'parfume-reviews')); ?> ' + response.data);
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Тествай схемата', 'parfume-reviews')); ?>');
                });
            });
            
            // Запазване на схемата
            $('#save-schema-btn').on('click', function() {
                var storeId = $('#test_store_id').val();
                var schemaData = {};
                
                $('.schema-input').each(function() {
                    var fieldName = $(this).attr('name');
                    var fieldValue = $(this).val();
                    if (fieldValue) {
                        schemaData[fieldName] = fieldValue;
                    }
                });
                
                if (!storeId || Object.keys(schemaData).length === 0) {
                    alert('<?php echo esc_js(__('Моля изберете магазин и попълнете поне един селектор', 'parfume-reviews')); ?>');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Запазва...', 'parfume-reviews')); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_save_store_schema',
                    store_id: storeId,
                    schema_data: schemaData,
                    nonce: '<?php echo wp_create_nonce('parfume_store_schema_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php echo esc_js(__('Схемата е запазена успешно', 'parfume-reviews')); ?>');
                        // Обновяваме схемата в store item
                        var $storeItem = $('.store-item[data-store-id="' + storeId + '"]');
                        if ($storeItem.length) {
                            $storeItem.find('.store-schema-status').text('<?php echo esc_js(__('Конфигурирана', 'parfume-reviews')); ?>').addClass('configured');
                        }
                    } else {
                        alert('<?php echo esc_js(__('Грешка при запазване на схемата:', 'parfume-reviews')); ?> ' + response.data);
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Запази схемата', 'parfume-reviews')); ?>');
                });
            });
            
            // Logo upload handling
            $(document).on('change', '.logo-upload-input', function() {
                var $input = $(this);
                var storeId = $input.data('store-id');
                var file = this.files[0];
                
                if (!file) return;
                
                var formData = new FormData();
                formData.append('action', 'parfume_upload_store_logo');
                formData.append('store_id', storeId);
                formData.append('logo_file', file);
                formData.append('nonce', '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $input.siblings('.logo-preview').html('<img src="' + response.data.logo_url + '" class="current-logo" alt="Store logo">');
                            alert('<?php echo esc_js(__('Логото е качено успешно', 'parfume-reviews')); ?>');
                        } else {
                            alert('<?php echo esc_js(__('Грешка при качване на лого:', 'parfume-reviews')); ?> ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Рендерира един store item
     */
    private function render_store_item($store_id, $store) {
        ?>
        <div class="store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-item-header">
                <div class="store-info">
                    <?php if (!empty($store['logo'])): ?>
                        <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" class="store-logo">
                    <?php endif; ?>
                    <div>
                        <strong><?php echo esc_html($store['name']); ?></strong>
                        <div class="store-status">
                            <span class="status-badge status-<?php echo esc_attr($store['status']); ?>">
                                <?php echo $store['status'] === 'active' ? __('Активен', 'parfume-reviews') : __('Неактивен', 'parfume-reviews'); ?>
                            </span>
                            <span class="store-schema-status <?php echo !empty($store['schema']) ? 'configured' : 'not-configured'; ?>">
                                <?php echo !empty($store['schema']) ? __('Конфигурирана', 'parfume-reviews') : __('Без схема', 'parfume-reviews'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="store-actions">
                    <button type="button" class="button edit-store" data-store-id="<?php echo esc_attr($store_id); ?>"><?php _e('Редактирай', 'parfume-reviews'); ?></button>
                    <button type="button" class="button button-link-delete delete-store" data-store-id="<?php echo esc_attr($store_id); ?>"><?php _e('Изтрий', 'parfume-reviews'); ?></button>
                </div>
            </div>
            
            <div class="store-details">
                <div class="store-detail">
                    <label><?php _e('URL:', 'parfume-reviews'); ?></label>
                    <span><?php echo esc_html($store['url']); ?></span>
                </div>
                <div class="store-detail">
                    <label><?php _e('Affiliate ID:', 'parfume-reviews'); ?></label>
                    <span><?php echo esc_html($store['affiliate_id']); ?></span>
                </div>
                <div class="store-detail">
                    <label><?php _e('Комисионна:', 'parfume-reviews'); ?></label>
                    <span><?php echo esc_html($store['commission_rate']); ?>%</span>
                </div>
            </div>
            
            <div class="logo-upload-field">
                <label><?php _e('Лого на магазина:', 'parfume-reviews'); ?></label>
                <input type="file" class="logo-upload-input" data-store-id="<?php echo esc_attr($store_id); ?>" accept=".jpg,.jpeg,.png,.gif">
                <div class="logo-preview">
                    <?php if (!empty($store['logo'])): ?>
                        <img src="<?php echo esc_url($store['logo']); ?>" class="current-logo" alt="Store logo">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Получава всички магазини
     */
    public function get_all_stores() {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings['available_stores']) ? $settings['available_stores'] : array();
    }
    
    /**
     * Запазва store schema
     */
    public function save_store_schema($store_id, $schema_data) {
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!isset($settings['available_stores'])) {
            $settings['available_stores'] = array();
        }
        
        if (!isset($settings['available_stores'][$store_id])) {
            return false;
        }
        
        $settings['available_stores'][$store_id]['schema'] = $schema_data;
        
        return update_option('parfume_reviews_settings', $settings);
    }
    
    // ==================== AJAX HANDLERS ====================
    
    /**
     * AJAX: Добавя нов магазин
     */
    public function ajax_add_new_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $store_name = sanitize_text_field($_POST['store_name']);
        $store_url = esc_url_raw($_POST['store_url']);
        $affiliate_id = sanitize_text_field($_POST['store_affiliate_id']);
        $commission_rate = floatval($_POST['store_commission_rate']);
        
        if (empty($store_name)) {
            wp_send_json_error(__('Store name is required', 'parfume-reviews'));
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!isset($settings['available_stores'])) {
            $settings['available_stores'] = array();
        }
        
        // Генерираме уникален ключ за магазина
        $store_key = sanitize_key($store_name);
        $counter = 1;
        $original_key = $store_key;
        
        while (isset($settings['available_stores'][$store_key])) {
            $store_key = $original_key . '_' . $counter;
            $counter++;
        }
        
        $new_store = array(
            'name' => $store_name,
            'url' => $store_url,
            'logo' => '',
            'affiliate_id' => $affiliate_id,
            'commission_rate' => $commission_rate,
            'status' => 'active',
            'schema' => array()
        );
        
        $settings['available_stores'][$store_key] = $new_store;
        
        if (update_option('parfume_reviews_settings', $settings)) {
            ob_start();
            $this->render_store_item($store_key, $new_store);
            $html = ob_get_clean();
            
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(__('Failed to save store', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Изтрива магазин
     */
    public function ajax_delete_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $store_id = sanitize_key($_POST['store_id']);
        
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!isset($settings['available_stores'][$store_id])) {
            wp_send_json_error(__('Store not found', 'parfume-reviews'));
        }
        
        // Изтриваме логото ако съществува
        if (!empty($settings['available_stores'][$store_id]['logo'])) {
            $this->delete_store_logo($settings['available_stores'][$store_id]['logo']);
        }
        
        unset($settings['available_stores'][$store_id]);
        
        if (update_option('parfume_reviews_settings', $settings)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to delete store', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Качва лого за магазин
     */
    public function ajax_upload_store_logo() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $store_id = sanitize_key($_POST['store_id']);
        
        if (empty($_FILES['logo_file']['tmp_name'])) {
            wp_send_json_error(__('No file uploaded', 'parfume-reviews'));
        }
        
        $logo_url = $this->upload_store_logo($_FILES['logo_file']);
        
        if (is_wp_error($logo_url)) {
            wp_send_json_error($logo_url->get_error_message());
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!isset($settings['available_stores'][$store_id])) {
            wp_send_json_error(__('Store not found', 'parfume-reviews'));
        }
        
        // Изтриваме старото лого ако съществува
        if (!empty($settings['available_stores'][$store_id]['logo'])) {
            $this->delete_store_logo($settings['available_stores'][$store_id]['logo']);
        }
        
        $settings['available_stores'][$store_id]['logo'] = $logo_url;
        
        if (update_option('parfume_reviews_settings', $settings)) {
            wp_send_json_success(array('logo_url' => $logo_url));
        } else {
            wp_send_json_error(__('Failed to save logo', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Тества store schema
     */
    public function ajax_test_store_schema() {
        check_ajax_referer('parfume_scraper_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $test_url = esc_url_raw($_POST['test_url']);
        $store_id = sanitize_key($_POST['store_id']);
        $schema_data = isset($_POST['schema_data']) ? $_POST['schema_data'] : array();
        $test_mode = isset($_POST['test_mode']) && $_POST['test_mode'];
        
        if (!$test_url || !filter_var($test_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Invalid URL', 'parfume-reviews'));
        }
        
        // Тук ще извикаме scraper функционалността
        $scraper_result = $this->run_scraper_test($test_url, $store_id, $schema_data, $test_mode);
        
        if ($scraper_result) {
            wp_send_json_success($scraper_result);
        } else {
            wp_send_json_error(__('Failed to test scraping', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX: Запазва store schema
     */
    public function ajax_save_store_schema() {
        check_ajax_referer('parfume_store_schema_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $store_id = sanitize_key($_POST['store_id']);
        $schema_data = $_POST['schema_data'];
        
        if (!$store_id || !is_array($schema_data)) {
            wp_send_json_error(__('Invalid data', 'parfume-reviews'));
        }
        
        // Валидираме схемата
        $validated_schema = array();
        $allowed_fields = array('price_selector', 'old_price_selector', 'ml_selector', 'availability_selector', 'delivery_selector');
        
        foreach ($schema_data as $field => $selector) {
            if (in_array($field, $allowed_fields) && !empty($selector)) {
                $validated_schema[$field] = sanitize_text_field($selector);
            }
        }
        
        if (empty($validated_schema)) {
            wp_send_json_error(__('No valid selectors provided', 'parfume-reviews'));
        }
        
        if ($this->save_store_schema($store_id, $validated_schema)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to save schema', 'parfume-reviews'));
        }
    }
    
    // ==================== HELPER FUNCTIONS ====================
    
    /**
     * Качва и валидира store лого
     */
    private function upload_store_logo($file) {
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
        $stats = array(
            'total_stores' => count($stores),
            'active_stores' => 0,
            'stores_with_logos' => 0,
            'stores_in_use' => 0
        );
        
        foreach ($stores as $store) {
            if ($store['status'] === 'active') {
                $stats['active_stores']++;
            }
            
            if (!empty($store['logo'])) {
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
     * Изпълнява scraper тест
     */
    private function run_scraper_test($url, $store_id, $schema_data = array(), $test_mode = false) {
        // Placeholder за scraper логика
        // Тази функция ще бъде имплементирана в scraper класа
        
        if ($test_mode && !empty($schema_data)) {
            // Тестваме с предоставената схема
            return array(
                'test_results' => array(
                    'price_selector' => '59.99 лв.',
                    'old_price_selector' => '79.99 лв.',
                    'ml_selector' => '30ml, 50ml, 100ml',
                    'availability_selector' => 'Наличен',
                    'delivery_selector' => 'Безплатна доставка над 50 лв.'
                )
            );
        } else {
            // Анализираме страницата и предлагаме селектори
            return array(
                'html' => '<p>Анализът е завършен успешно. Открити са потенциални селектори за цени и продуктова информация.</p>',
                'suggestions' => array(
                    'price_selector' => '.price-current',
                    'old_price_selector' => '.price-old',
                    'ml_selector' => '.product-variants',
                    'availability_selector' => '.stock-status',
                    'delivery_selector' => '.shipping-info'
                ),
                'found_values' => array(
                    'price_selector' => '59.99 лв.',
                    'old_price_selector' => '79.99 лв.',
                    'ml_selector' => '30ml, 50ml, 100ml',
                    'availability_selector' => 'Наличен',
                    'delivery_selector' => 'Безплатна доставка над 50 лв.'
                )
            );
        }
    }
}