<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Stores class - Управлява настройките за магазини
 * 
 * Файл: includes/settings/class-settings-stores.php
 * ВЕРСИЯ С АВТОМАТИЧЕН FIX ЗА ПРАЗЕН СТРИНГ
 */
class Settings_Stores {
    
    public function __construct() {
        // AJAX хендлъри за магазини
        add_action('wp_ajax_parfume_add_store', array($this, 'ajax_add_store'));
        add_action('wp_ajax_parfume_edit_store', array($this, 'ajax_edit_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_toggle_store_status', array($this, 'ajax_toggle_store_status'));
        add_action('wp_ajax_parfume_upload_store_logo', array($this, 'ajax_upload_store_logo'));
        
        // AUTO-FIX при инициализация
        add_action('admin_init', array($this, 'fix_stores_option_if_needed'));
    }
    
    /**
     * Автоматично поправя stores опцията ако е празен стринг
     */
    public function fix_stores_option_if_needed() {
        $stores = get_option('parfume_reviews_stores', false);
        
        // Ако е празен стринг, false, или не е масив - поправяме
        if ($stores === '' || $stores === false || !is_array($stores)) {
            error_log('AUTO-FIX: parfume_reviews_stores is corrupted, fixing now...');
            error_log('Current type: ' . gettype($stores));
            error_log('Current value: ' . var_export($stores, true));
            
            // Изтриваме лошата опция
            delete_option('parfume_reviews_stores');
            
            // Създаваме нова с празен масив
            add_option('parfume_reviews_stores', array(), '', 'yes');
            
            error_log('AUTO-FIX: parfume_reviews_stores fixed and reset to empty array');
        }
    }
    
    /**
     * Helper метод за получаване на stores с автоматичен fix
     */
    private function get_stores_safe() {
        $stores = get_option('parfume_reviews_stores', array());
        
        // Допълнителна защита
        if (!is_array($stores)) {
            error_log('WARNING: get_stores_safe() - stores is not array, type: ' . gettype($stores));
            
            // Форсираме fix
            delete_option('parfume_reviews_stores');
            add_option('parfume_reviews_stores', array(), '', 'yes');
            
            return array();
        }
        
        return $stores;
    }
    
    /**
     * Регистрира настройките за stores
     */
    public function register_settings() {
        // Stores Section - не регистрираме полета тук защото stores се управляват чрез AJAX
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
        // Използваме безопасния метод
        $available_stores = $this->get_stores_safe();
        
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
            border-bottom: 1px solid #eee;
        }
        .store-item:last-child {
            border-bottom: none;
        }
        .store-logo {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .store-logo img {
            max-width: 50px;
            max-height: 50px;
        }
        .store-logo .dashicons {
            font-size: 32px;
            color: #999;
        }
        .store-info {
            flex-grow: 1;
        }
        .store-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .store-url {
            font-size: 13px;
            color: #666;
        }
        .store-url a {
            text-decoration: none;
        }
        .store-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .store-status {
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
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
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }
        .stores-statistics h3 {
            margin-top: 0;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .stat-value {
            display: block;
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }
        .stat-label {
            display: block;
            font-size: 13px;
            color: #666;
        }
        .logo-upload-area {
            border: 2px dashed #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .logo-upload-area:hover {
            border-color: #0073aa;
            background: #f8f9fa;
        }
        .logo-upload-area img.logo-preview {
            max-width: 100px;
            max-height: 100px;
            margin-bottom: 10px;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Upload logo functionality
            $('.logo-upload-area').on('click', function() {
                $(this).find('.logo-upload-input').trigger('click');
            });
            
            $('.logo-upload-input').on('change', function(e) {
                var file = e.target.files[0];
                var $uploadArea = $(this).closest('.logo-upload-area');
                
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $uploadArea.find('.logo-preview').attr('src', e.target.result).show();
                        $uploadArea.find('p').hide();
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Submit add store form
            $('#add-store-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'parfume_add_store');
                formData.append('nonce', '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>');
                
                var $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).text('<?php _e('Добавяне...', 'parfume-reviews'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Магазинът е добавен успешно!', 'parfume-reviews'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Грешка:', 'parfume-reviews'); ?> ' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Грешка при добавяне на магазина.', 'parfume-reviews'); ?>');
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text('<?php _e('Добави магазин', 'parfume-reviews'); ?>');
                    }
                });
            });
            
            // Delete store
            $('.delete-store').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-reviews'); ?>')) {
                    return;
                }
                
                var storeId = $(this).data('store-id');
                var $storeItem = $(this).closest('.store-item');
                
                $.post(ajaxurl, {
                    action: 'parfume_delete_store',
                    store_id: storeId,
                    nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $storeItem.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('<?php _e('Грешка при изтриване:', 'parfume-reviews'); ?> ' + response.data);
                    }
                });
            });
            
            // Toggle status
            $('.toggle-status').on('click', function(e) {
                e.preventDefault();
                
                var storeId = $(this).data('store-id');
                var $statusSpan = $(this).siblings('.store-status');
                var $button = $(this);
                
                $.post(ajaxurl, {
                    action: 'parfume_toggle_store_status',
                    store_id: storeId,
                    nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        if (response.data.status === 'active') {
                            $statusSpan.removeClass('inactive').addClass('active').text('<?php _e('Активен', 'parfume-reviews'); ?>');
                            $button.text('<?php _e('Деактивирай', 'parfume-reviews'); ?>');
                        } else {
                            $statusSpan.removeClass('active').addClass('inactive').text('<?php _e('Неактивен', 'parfume-reviews'); ?>');
                            $button.text('<?php _e('Активирай', 'parfume-reviews'); ?>');
                        }
                    } else {
                        alert('<?php _e('Грешка:', 'parfume-reviews'); ?> ' + response.data);
                    }
                });
            });
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
                        <label for="store-name"><?php _e('Име на магазина:', 'parfume-reviews'); ?> <span class="required">*</span></label>
                        <input type="text" id="store-name" name="store_name" required placeholder="<?php _e('Например: Douglas', 'parfume-reviews'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="store-url"><?php _e('URL на магазина:', 'parfume-reviews'); ?></label>
                        <input type="url" id="store-url" name="store_url" placeholder="https://www.example.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="affiliate-id"><?php _e('Affiliate ID:', 'parfume-reviews'); ?></label>
                        <input type="text" id="affiliate-id" name="affiliate_id" placeholder="<?php _e('Вашият affiliate ID', 'parfume-reviews'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="commission-rate"><?php _e('Комисионна (%):', 'parfume-reviews'); ?></label>
                        <input type="number" id="commission-rate" name="commission_rate" step="0.01" min="0" max="100" placeholder="5.00">
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
                        <label for="store-priority"><?php _e('Приоритет:', 'parfume-reviews'); ?></label>
                        <input type="number" id="store-priority" name="store_priority" min="1" max="10" value="5">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php _e('Лого на магазина:', 'parfume-reviews'); ?></label>
                    <div class="logo-upload-area">
                        <input type="file" name="store_logo" class="logo-upload-input" accept="image/*" style="display: none;">
                        <img class="logo-preview" style="display: none;">
                        <p><?php _e('Кликнете за да качите лого', 'parfume-reviews'); ?></p>
                        <small><?php _e('Препоръчван размер: 200x200px', 'parfume-reviews'); ?></small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="store-description"><?php _e('Описание:', 'parfume-reviews'); ?></label>
                    <textarea id="store-description" name="store_description" rows="3" placeholder="<?php _e('Кратко описание на магазина...', 'parfume-reviews'); ?>"></textarea>
                </div>
                
                <button type="submit" class="button button-primary"><?php _e('Добави магазин', 'parfume-reviews'); ?></button>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира списъка с магазини
     */
    private function render_stores_list($stores) {
        // Валидация
        if (!is_array($stores)) {
            $stores = array();
        }
        
        ?>
        <div class="stores-list">
            <h3><?php _e('Налични магазини', 'parfume-reviews'); ?></h3>
            
            <?php if (empty($stores)): ?>
                <div style="padding: 20px; text-align: center; color: #666;">
                    <p><?php _e('Няма добавени магазини.', 'parfume-reviews'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($stores as $store_id => $store): ?>
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
                <?php endforeach; ?>
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
     * AJAX HANDLERS
     */
    
    /**
     * AJAX handler за добавяне на магазин
     */
    public function ajax_add_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
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
        
        // Използваме безопасния метод
        $available_stores = $this->get_stores_safe();
        
        // Проверка за дублиране
        foreach ($available_stores as $existing_store) {
            if (strtolower($existing_store['name']) === strtolower($store_name)) {
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
        
        // Генериране на ID
        $store_id = sanitize_title($store_name) . '_' . time();
        
        // Добавяне на новия магазин
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
        
        // КРИТИЧНА ЧАСТ - delete + add за сигурност
        delete_option('parfume_reviews_stores');
        $result = add_option('parfume_reviews_stores', $available_stores, '', 'yes');
        
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
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $available_stores = $this->get_stores_safe();
        
        if (!isset($available_stores[$store_id])) {
            wp_send_json_error(__('Магазинът не съществува.', 'parfume-reviews'));
        }
        
        // Изтриване на логото
        if (!empty($available_stores[$store_id]['logo'])) {
            $this->delete_store_logo($available_stores[$store_id]['logo']);
        }
        
        unset($available_stores[$store_id]);
        update_option('parfume_reviews_stores', $available_stores);
        
        wp_send_json_success(__('Магазинът е изтрит успешно.', 'parfume-reviews'));
    }
    
    /**
     * AJAX handler за смяна на статус
     */
    public function ajax_toggle_store_status() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $available_stores = $this->get_stores_safe();
        
        if (!isset($available_stores[$store_id])) {
            wp_send_json_error(__('Магазинът не съществува.', 'parfume-reviews'));
        }
        
        $current_status = $available_stores[$store_id]['status'];
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        
        $available_stores[$store_id]['status'] = $new_status;
        $available_stores[$store_id]['updated_at'] = current_time('mysql');
        
        update_option('parfume_reviews_stores', $available_stores);
        
        wp_send_json_success(array(
            'status' => $new_status,
            'message' => sprintf(__('Статусът е променен на "%s".', 'parfume-reviews'), 
                $new_status === 'active' ? __('активен', 'parfume-reviews') : __('неактивен', 'parfume-reviews'))
        ));
    }
    
    /**
     * AJAX handler за редактиране на магазин
     */
    public function ajax_edit_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за тази операция.', 'parfume-reviews'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $available_stores = $this->get_stores_safe();
        
        if (!isset($available_stores[$store_id])) {
            wp_send_json_error(__('Магазинът не съществува.', 'parfume-reviews'));
        }
        
        // Обновяване на данните
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
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
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
     * HELPER METHODS
     */
    
    /**
     * Обработва upload на лого
     */
    private function handle_logo_upload($file) {
        if (empty($file['name'])) {
            return new \WP_Error('no_file', __('Няма избран файл.', 'parfume-reviews'));
        }
        
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return new \WP_Error('invalid_type', __('Неподдържан тип файл. Разрешени: JPG, PNG, GIF.', 'parfume-reviews'));
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
     * Изтрива лого
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
     * Получава статистики
     */
    private function get_stores_statistics($stores) {
        // Валидация на $stores
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
            if ($store['status'] === 'active') {
                $stats['active_stores']++;
            }
            
            if (!empty($store['logo'])) {
                $stats['stores_with_logos']++;
            }
        }
        
        // Брой магазини в употреба
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
     * Получава всички настройки
     */
    public function get_all_settings() {
        return $this->get_stores_safe();
    }
    
    /**
     * Валидира настройките
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
     * Експортира настройките
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        
        return json_encode(array(
            'component' => 'stores',
            'version' => PARFUME_REVIEWS_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Импортира настройки
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['component']) || $data['component'] !== 'stores') {
            return new \WP_Error('invalid_component', __('Файлът не съдържа stores настройки.', 'parfume-reviews'));
        }
        
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return new \WP_Error('invalid_settings', __('Невалидни настройки в файла.', 'parfume-reviews'));
        }
        
        $validated_settings = $this->validate_settings($data['settings']);
        
        // Използваме delete + add за сигурност
        delete_option('parfume_reviews_stores');
        $result = add_option('parfume_reviews_stores', $validated_settings, '', 'yes');
        
        return $result;
    }
}