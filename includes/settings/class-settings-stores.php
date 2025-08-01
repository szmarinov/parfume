<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Stores class - Управлява настройките за магазини
 * 
 * Файл: includes/settings/class-settings-stores.php
 * Създаден за "Колона 2" функционалността
 */
class Settings_Stores {
    
    public function __construct() {
        // AJAX actions за управление на магазини
        add_action('wp_ajax_parfume_add_store', array($this, 'ajax_add_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_update_store', array($this, 'ajax_update_store'));
    }
    
    /**
     * Регистрира настройките за магазини
     */
    public function register_settings() {
        // Stores Section
        add_settings_section(
            'parfume_reviews_stores_section',
            __('Магазини', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        register_setting('parfume-reviews-settings', 'parfume_reviews_stores', array(
            'sanitize_callback' => array($this, 'sanitize_stores')
        ));
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Добавете и управлявайте affiliate магазини за показване в постовете.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с магазини
     */
    public function render_section() {
        $stores = get_option('parfume_reviews_stores', array());
        ?>
        <div class="stores-management">
            <h3><?php _e('Управление на магазини', 'parfume-reviews'); ?></h3>
            
            <!-- Форма за добавяне на нов магазин -->
            <div class="add-store-form">
                <h4><?php _e('Добави нов магазин', 'parfume-reviews'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="store_name"><?php _e('Име на магазин', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="store_name" name="store_name" class="regular-text" required />
                            <p class="description"><?php _e('Името на магазина (напр. "Douglas", "Notino")', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="store_logo"><?php _e('Лого', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <div class="store-logo-upload">
                                <input type="hidden" id="store_logo_id" name="store_logo_id" />
                                <div class="logo-preview" style="display:none;">
                                    <img src="" alt="" style="max-width: 150px; height: auto;" />
                                    <button type="button" class="button remove-logo"><?php _e('Премахни', 'parfume-reviews'); ?></button>
                                </div>
                                <button type="button" class="button upload-logo"><?php _e('Качи лого', 'parfume-reviews'); ?></button>
                            </div>
                            <p class="description"><?php _e('Качете лого на магазина (JPG, PNG, максимум 2MB)', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="store_url"><?php _e('URL на магазин', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="store_url" name="store_url" class="regular-text" />
                            <p class="description"><?php _e('Основният URL на магазина (опционално)', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button-primary" id="add-store-btn">
                        <?php _e('Добави магазин', 'parfume-reviews'); ?>
                    </button>
                </p>
            </div>
            
            <!-- Списък със съществуващи магазини -->
            <div class="existing-stores">
                <h4><?php _e('Съществуващи магазини', 'parfume-reviews'); ?></h4>
                <div class="stores-list">
                    <?php if (empty($stores)): ?>
                        <p class="no-stores"><?php _e('Няма добавени магазини.', 'parfume-reviews'); ?></p>
                    <?php else: ?>
                        <?php foreach ($stores as $store_id => $store): ?>
                            <div class="store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
                                <div class="store-header">
                                    <?php if (!empty($store['logo_id'])): ?>
                                        <div class="store-logo">
                                            <?php echo wp_get_attachment_image($store['logo_id'], 'thumbnail', false, array('style' => 'width: 50px; height: auto;')); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="store-info">
                                        <h5><?php echo esc_html($store['name']); ?></h5>
                                        <?php if (!empty($store['url'])): ?>
                                            <p><a href="<?php echo esc_url($store['url']); ?>" target="_blank"><?php echo esc_html($store['url']); ?></a></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="store-actions">
                                        <button type="button" class="button edit-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Редактирай', 'parfume-reviews'); ?>
                                        </button>
                                        <button type="button" class="button delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Изтрий', 'parfume-reviews'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Инициализация на media uploader
            var mediaUploader;
            
            $('.upload-logo').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Избери лого на магазин', 'parfume-reviews'); ?>',
                    button: {
                        text: '<?php _e('Избери', 'parfume-reviews'); ?>'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#store_logo_id').val(attachment.id);
                    $('.logo-preview img').attr('src', attachment.url);
                    $('.logo-preview').show();
                    $('.upload-logo').text('<?php _e('Смени лого', 'parfume-reviews'); ?>');
                });
                
                mediaUploader.open();
            });
            
            $('.remove-logo').click(function(e) {
                e.preventDefault();
                $('#store_logo_id').val('');
                $('.logo-preview').hide();
                $('.upload-logo').text('<?php _e('Качи лого', 'parfume-reviews'); ?>');
            });
            
            // Добавяне на нов магазин
            $('#add-store-btn').click(function(e) {
                e.preventDefault();
                
                var storeName = $('#store_name').val().trim();
                var storeLogoId = $('#store_logo_id').val();
                var storeUrl = $('#store_url').val().trim();
                
                if (!storeName) {
                    alert('<?php _e('Моля въведете име на магазин.', 'parfume-reviews'); ?>');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_add_store',
                        nonce: parfumeSettings.nonce,
                        store_name: storeName,
                        store_logo_id: storeLogoId,
                        store_url: storeUrl
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Презареждаме страницата за да се покажат новите данни
                        } else {
                            alert(response.data.message || parfumeSettings.strings.error);
                        }
                    },
                    error: function() {
                        alert(parfumeSettings.strings.error);
                    }
                });
            });
            
            // Изтриване на магазин
            $('.delete-store').click(function(e) {
                e.preventDefault();
                
                if (!confirm(parfumeSettings.strings.confirm_delete)) {
                    return;
                }
                
                var storeId = $(this).data('store-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_delete_store',
                        nonce: parfumeSettings.nonce,
                        store_id: storeId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || parfumeSettings.strings.error);
                        }
                    },
                    error: function() {
                        alert(parfumeSettings.strings.error);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler за добавяне на магазин
     */
    public function ajax_add_store() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $store_name = sanitize_text_field($_POST['store_name']);
        $store_logo_id = intval($_POST['store_logo_id']);
        $store_url = esc_url_raw($_POST['store_url']);
        
        if (empty($store_name)) {
            wp_send_json_error(array('message' => __('Името на магазина е задължително.', 'parfume-reviews')));
        }
        
        $stores = get_option('parfume_reviews_stores', array());
        $store_id = sanitize_title($store_name) . '_' . time();
        
        $stores[$store_id] = array(
            'name' => $store_name,
            'logo_id' => $store_logo_id,
            'url' => $store_url,
            'created' => current_time('mysql')
        );
        
        update_option('parfume_reviews_stores', $stores);
        
        wp_send_json_success(array('message' => __('Магазинът е добавен успешно.', 'parfume-reviews')));
    }
    
    /**
     * AJAX handler за изтриване на магазин
     */
    public function ajax_delete_store() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option('parfume_reviews_stores', array());
        
        if (isset($stores[$store_id])) {
            unset($stores[$store_id]);
            update_option('parfume_reviews_stores', $stores);
            wp_send_json_success(array('message' => __('Магазинът е изтрит успешно.', 'parfume-reviews')));
        } else {
            wp_send_json_error(array('message' => __('Магазинът не е намерен.', 'parfume-reviews')));
        }
    }
    
    /**
     * AJAX handler за обновяване на магазин
     */
    public function ajax_update_store() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $store_name = sanitize_text_field($_POST['store_name']);
        $store_logo_id = intval($_POST['store_logo_id']);
        $store_url = esc_url_raw($_POST['store_url']);
        
        $stores = get_option('parfume_reviews_stores', array());
        
        if (isset($stores[$store_id])) {
            $stores[$store_id]['name'] = $store_name;
            $stores[$store_id]['logo_id'] = $store_logo_id;
            $stores[$store_id]['url'] = $store_url;
            $stores[$store_id]['updated'] = current_time('mysql');
            
            update_option('parfume_reviews_stores', $stores);
            wp_send_json_success(array('message' => __('Магазинът е обновен успешно.', 'parfume-reviews')));
        } else {
            wp_send_json_error(array('message' => __('Магазинът не е намерен.', 'parfume-reviews')));
        }
    }
    
    /**
     * Санитизация на stores данни
     */
    public function sanitize_stores($input) {
        if (!is_array($input)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($input as $store_id => $store) {
            if (!is_array($store)) {
                continue;
            }
            
            $sanitized[sanitize_key($store_id)] = array(
                'name' => isset($store['name']) ? sanitize_text_field($store['name']) : '',
                'logo_id' => isset($store['logo_id']) ? intval($store['logo_id']) : 0,
                'url' => isset($store['url']) ? esc_url_raw($store['url']) : '',
                'created' => isset($store['created']) ? sanitize_text_field($store['created']) : current_time('mysql'),
                'updated' => isset($store['updated']) ? sanitize_text_field($store['updated']) : ''
            );
        }
        
        return $sanitized;
    }
}