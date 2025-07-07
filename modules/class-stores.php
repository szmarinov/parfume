<?php
/**
 * Stores management class for Parfume Catalog plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Stores {
    
    public function __construct() {
        add_action('parfume_catalog_stores_admin_page', array($this, 'render_admin_page'));
        add_action('wp_ajax_parfume_add_store', array($this, 'ajax_add_store'));
        add_action('wp_ajax_parfume_edit_store', array($this, 'ajax_edit_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_get_store', array($this, 'ajax_get_store'));
    }
    
    /**
     * Render admin page for stores management
     */
    public function render_admin_page() {
        $stores = get_option('parfume_catalog_stores', array());
        
        // Handle form submissions
        if (isset($_POST['action']) && $_POST['action'] === 'add_store') {
            $this->handle_add_store();
            $stores = get_option('parfume_catalog_stores', array());
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Управление на магазини', 'parfume-catalog'); ?></h1>
            
            <div class="stores-management">
                <div class="stores-list">
                    <h2><?php _e('Налични магазини', 'parfume-catalog'); ?></h2>
                    
                    <?php if (empty($stores)) : ?>
                        <p><?php _e('Няма добавени магазини.', 'parfume-catalog'); ?></p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Лого', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Име', 'parfume-catalog'); ?></th>
                                    <th><?php _e('URL', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Schema', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stores as $store_id => $store) : ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($store['logo'])) : ?>
                                                <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" style="max-width: 50px; max-height: 30px;" />
                                            <?php else : ?>
                                                <span class="dashicons dashicons-store"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($store['name']); ?></td>
                                        <td><?php echo esc_html($store['url']); ?></td>
                                        <td>
                                            <?php if (!empty($store['schema'])) : ?>
                                                <span class="dashicons dashicons-yes-alt" style="color: green;" title="<?php _e('Schema настроена', 'parfume-catalog'); ?>"></span>
                                            <?php else : ?>
                                                <span class="dashicons dashicons-warning" style="color: orange;" title="<?php _e('Няма schema', 'parfume-catalog'); ?>"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button edit-store-btn" data-store-id="<?php echo esc_attr($store_id); ?>"><?php _e('Редактирай', 'parfume-catalog'); ?></button>
                                            <button type="button" class="button-link-delete delete-store-btn" data-store-id="<?php echo esc_attr($store_id); ?>"><?php _e('Изтрий', 'parfume-catalog'); ?></button>
                                            <a href="<?php echo admin_url('admin.php?page=parfume-catalog-scraper&action=test&store_id=' . $store_id); ?>" class="button"><?php _e('Тест Schema', 'parfume-catalog'); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="add-store-form">
                    <h2><?php _e('Добави нов магазин', 'parfume-catalog'); ?></h2>
                    
                    <form method="post" id="add-store-form">
                        <?php wp_nonce_field('add_store_nonce', 'add_store_nonce'); ?>
                        <input type="hidden" name="action" value="add_store" />
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="store_name"><?php _e('Име на магазина', 'parfume-catalog'); ?></label></th>
                                <td><input type="text" id="store_name" name="store_name" required class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label for="store_url"><?php _e('URL на магазина', 'parfume-catalog'); ?></label></th>
                                <td><input type="url" id="store_url" name="store_url" required class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label for="store_logo"><?php _e('Лого', 'parfume-catalog'); ?></label></th>
                                <td>
                                    <input type="url" id="store_logo" name="store_logo" class="regular-text" />
                                    <button type="button" id="upload_logo_button" class="button"><?php _e('Качи лого', 'parfume-catalog'); ?></button>
                                    <div id="logo_preview"></div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="store_description"><?php _e('Описание', 'parfume-catalog'); ?></label></th>
                                <td><textarea id="store_description" name="store_description" rows="3" class="large-text"></textarea></td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Добави магазин', 'parfume-catalog')); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Store Modal -->
        <div id="edit-store-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2><?php _e('Редактирай магазин', 'parfume-catalog'); ?></h2>
                
                <form id="edit-store-form">
                    <input type="hidden" id="edit_store_id" name="store_id" />
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="edit_store_name"><?php _e('Име на магазина', 'parfume-catalog'); ?></label></th>
                            <td><input type="text" id="edit_store_name" name="store_name" required class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="edit_store_url"><?php _e('URL на магазина', 'parfume-catalog'); ?></label></th>
                            <td><input type="url" id="edit_store_url" name="store_url" required class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="edit_store_logo"><?php _e('Лого', 'parfume-catalog'); ?></label></th>
                            <td>
                                <input type="url" id="edit_store_logo" name="store_logo" class="regular-text" />
                                <button type="button" id="edit_upload_logo_button" class="button"><?php _e('Качи лого', 'parfume-catalog'); ?></button>
                                <div id="edit_logo_preview"></div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="edit_store_description"><?php _e('Описание', 'parfume-catalog'); ?></label></th>
                            <td><textarea id="edit_store_description" name="store_description" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Schema настройки', 'parfume-catalog'); ?></label></th>
                            <td>
                                <div class="schema-settings">
                                    <label><?php _e('Селектор за цена:', 'parfume-catalog'); ?></label>
                                    <input type="text" id="edit_price_selector" name="schema[price_selector]" class="regular-text" />
                                    
                                    <label><?php _e('Селектор за стара цена:', 'parfume-catalog'); ?></label>
                                    <input type="text" id="edit_old_price_selector" name="schema[old_price_selector]" class="regular-text" />
                                    
                                    <label><?php _e('Селектор за ML варианти:', 'parfume-catalog'); ?></label>
                                    <input type="text" id="edit_ml_selector" name="schema[ml_selector]" class="regular-text" />
                                    
                                    <label><?php _e('Селектор за наличност:', 'parfume-catalog'); ?></label>
                                    <input type="text" id="edit_availability_selector" name="schema[availability_selector]" class="regular-text" />
                                    
                                    <label><?php _e('Селектор за доставка:', 'parfume-catalog'); ?></label>
                                    <input type="text" id="edit_delivery_selector" name="schema[delivery_selector]" class="regular-text" />
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button-primary"><?php _e('Запази промените', 'parfume-catalog'); ?></button>
                        <button type="button" class="button" onclick="closeEditModal()"><?php _e('Отказ', 'parfume-catalog'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <style>
        #edit-store-modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 5px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: black;
        }
        
        .schema-settings label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .schema-settings input {
            margin-bottom: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Media uploader for logo
            var mediaUploader;
            
            $('#upload_logo_button, #edit_upload_logo_button').click(function(e) {
                e.preventDefault();
                var targetInput = $(this).prev('input');
                var targetPreview = $(this).next('div');
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Избери лого за магазина', 'parfume-catalog'); ?>',
                    button: {
                        text: '<?php _e('Използвай това изображение', 'parfume-catalog'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    targetInput.val(attachment.url);
                    targetPreview.html('<img src="' + attachment.url + '" style="max-width: 100px; max-height: 60px;" />');
                });
                
                mediaUploader.open();
            });
            
            // Edit store
            $('.edit-store-btn').click(function() {
                var storeId = $(this).data('store-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_get_store',
                        store_id: storeId,
                        nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var store = response.data;
                            $('#edit_store_id').val(storeId);
                            $('#edit_store_name').val(store.name);
                            $('#edit_store_url').val(store.url);
                            $('#edit_store_logo').val(store.logo);
                            $('#edit_store_description').val(store.description);
                            
                            if (store.logo) {
                                $('#edit_logo_preview').html('<img src="' + store.logo + '" style="max-width: 100px; max-height: 60px;" />');
                            }
                            
                            // Fill schema fields
                            if (store.schema) {
                                $('#edit_price_selector').val(store.schema.price_selector || '');
                                $('#edit_old_price_selector').val(store.schema.old_price_selector || '');
                                $('#edit_ml_selector').val(store.schema.ml_selector || '');
                                $('#edit_availability_selector').val(store.schema.availability_selector || '');
                                $('#edit_delivery_selector').val(store.schema.delivery_selector || '');
                            }
                            
                            $('#edit-store-modal').show();
                        }
                    }
                });
            });
            
            // Close modal
            $('.close').click(function() {
                $('#edit-store-modal').hide();
            });
            
            window.closeEditModal = function() {
                $('#edit-store-modal').hide();
            };
            
            // Save edited store
            $('#edit-store-form').submit(function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=parfume_edit_store&nonce=<?php echo wp_create_nonce('parfume_stores_nonce'); ?>';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });
            
            // Delete store
            $('.delete-store-btn').click(function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                var storeId = $(this).data('store-id');
                
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
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle add store form submission
     */
    private function handle_add_store() {
        if (!isset($_POST['add_store_nonce']) || !wp_verify_nonce($_POST['add_store_nonce'], 'add_store_nonce')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $store_data = array(
            'name' => sanitize_text_field($_POST['store_name']),
            'url' => esc_url_raw($_POST['store_url']),
            'logo' => esc_url_raw($_POST['store_logo']),
            'description' => sanitize_textarea_field($_POST['store_description']),
            'schema' => array(),
            'created_at' => current_time('mysql')
        );
        
        $stores = get_option('parfume_catalog_stores', array());
        $store_id = uniqid();
        $stores[$store_id] = $store_data;
        
        update_option('parfume_catalog_stores', $stores);
    }
    
    /**
     * AJAX handler for adding store
     */
    public function ajax_add_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $store_data = array(
            'name' => sanitize_text_field($_POST['store_name']),
            'url' => esc_url_raw($_POST['store_url']),
            'logo' => esc_url_raw($_POST['store_logo']),
            'description' => sanitize_textarea_field($_POST['store_description']),
            'schema' => array(),
            'created_at' => current_time('mysql')
        );
        
        $stores = get_option('parfume_catalog_stores', array());
        $store_id = uniqid();
        $stores[$store_id] = $store_data;
        
        if (update_option('parfume_catalog_stores', $stores)) {
            wp_send_json_success('Store added successfully');
        } else {
            wp_send_json_error('Failed to add store');
        }
    }
    
    /**
     * AJAX handler for editing store
     */
    public function ajax_edit_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        if (!isset($stores[$store_id])) {
            wp_send_json_error('Store not found');
        }
        
        $stores[$store_id]['name'] = sanitize_text_field($_POST['store_name']);
        $stores[$store_id]['url'] = esc_url_raw($_POST['store_url']);
        $stores[$store_id]['logo'] = esc_url_raw($_POST['store_logo']);
        $stores[$store_id]['description'] = sanitize_textarea_field($_POST['store_description']);
        $stores[$store_id]['updated_at'] = current_time('mysql');
        
        // Update schema if provided
        if (isset($_POST['schema']) && is_array($_POST['schema'])) {
            $schema = array();
            foreach ($_POST['schema'] as $key => $value) {
                $schema[sanitize_key($key)] = sanitize_text_field($value);
            }
            $stores[$store_id]['schema'] = $schema;
        }
        
        if (update_option('parfume_catalog_stores', $stores)) {
            wp_send_json_success('Store updated successfully');
        } else {
            wp_send_json_error('Failed to update store');
        }
    }
    
    /**
     * AJAX handler for deleting store
     */
    public function ajax_delete_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        if (isset($stores[$store_id])) {
            unset($stores[$store_id]);
            
            if (update_option('parfume_catalog_stores', $stores)) {
                wp_send_json_success('Store deleted successfully');
            } else {
                wp_send_json_error('Failed to delete store');
            }
        } else {
            wp_send_json_error('Store not found');
        }
    }
    
    /**
     * AJAX handler for getting store data
     */
    public function ajax_get_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        if (isset($stores[$store_id])) {
            wp_send_json_success($stores[$store_id]);
        } else {
            wp_send_json_error('Store not found');
        }
    }
    
    /**
     * Get store by ID
     */
    public static function get_store($store_id) {
        $stores = get_option('parfume_catalog_stores', array());
        return isset($stores[$store_id]) ? $stores[$store_id] : false;
    }
    
    /**
     * Get all stores
     */
    public static function get_all_stores() {
        return get_option('parfume_catalog_stores', array());
    }
}