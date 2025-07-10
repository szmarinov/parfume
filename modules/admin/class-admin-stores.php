<?php
/**
 * Admin Stores Management Class
 * 
 * Handles store management in admin panel
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Admin_Stores {
    
    private $stores_option = 'parfume_stores';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_stores_page'));
        add_action('wp_ajax_parfume_save_store', array($this, 'save_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'delete_store'));
        add_action('wp_ajax_parfume_get_store', array($this, 'get_store'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add stores management page to admin menu
     */
    public function add_stores_page() {
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Магазини', 'parfume-catalog'),
            __('Магазини', 'parfume-catalog'),
            'manage_options',
            'parfume-stores',
            array($this, 'render_stores_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'parfumes_page_parfume-stores') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
    }
    
    /**
     * Render the stores management page
     */
    public function render_stores_page() {
        $stores = $this->get_stores();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Управление на магазини', 'parfume-catalog'); ?>
                <button type="button" id="add-store-btn" class="page-title-action">
                    <?php _e('Добави магазин', 'parfume-catalog'); ?>
                </button>
            </h1>
            
            <div class="stores-container">
                <div id="stores-list">
                    <?php if (empty($stores)): ?>
                        <div class="no-stores-message">
                            <p><?php _e('Все още няма добавени магазини. Добавете първия си магазин с бутона по-горе.', 'parfume-catalog'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="stores-grid">
                            <?php foreach ($stores as $store_id => $store): ?>
                                <?php $this->render_store_card($store_id, $store); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Store Modal -->
        <div id="store-modal" class="store-modal" style="display: none;">
            <div class="store-modal-content">
                <div class="store-modal-header">
                    <h2 id="modal-title"><?php _e('Добави магазин', 'parfume-catalog'); ?></h2>
                    <span class="store-modal-close">&times;</span>
                </div>
                
                <div class="store-modal-body">
                    <form id="store-form">
                        <input type="hidden" id="store-id" name="store_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="store-name"><?php _e('Име на магазина', 'parfume-catalog'); ?> *</label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="store-name" 
                                           name="store_name" 
                                           class="regular-text" 
                                           required />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="store-logo"><?php _e('Лого на магазина', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <div class="logo-upload-container">
                                        <input type="hidden" id="store-logo" name="store_logo" value="">
                                        <div id="logo-preview" class="logo-preview"></div>
                                        <button type="button" id="upload-logo-btn" class="button">
                                            <?php _e('Избери лого', 'parfume-catalog'); ?>
                                        </button>
                                        <button type="button" id="remove-logo-btn" class="button" style="display: none;">
                                            <?php _e('Премахни', 'parfume-catalog'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="store-url"><?php _e('URL на магазина', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" 
                                           id="store-url" 
                                           name="store_url" 
                                           class="regular-text" 
                                           placeholder="https://example.com" />
                                    <p class="description"><?php _e('Основният URL на магазина', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="store-description"><?php _e('Описание', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <textarea id="store-description" 
                                              name="store_description" 
                                              rows="3" 
                                              class="large-text"></textarea>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="store-status"><?php _e('Статус', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <select id="store-status" name="store_status">
                                        <option value="active"><?php _e('Активен', 'parfume-catalog'); ?></option>
                                        <option value="inactive"><?php _e('Неактивен', 'parfume-catalog'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="store-priority"><?php _e('Приоритет', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="store-priority" 
                                           name="store_priority" 
                                           value="1" 
                                           min="1" 
                                           max="100" />
                                    <p class="description"><?php _e('По-високия номер означава по-висок приоритет', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <div class="store-modal-footer">
                    <button type="button" id="save-store-btn" class="button button-primary">
                        <?php _e('Запази', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button store-modal-close">
                        <?php _e('Отказ', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .store-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .store-card.inactive {
            opacity: 0.6;
        }
        
        .store-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .store-logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .store-logo-placeholder {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            background: #f0f0f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
        }
        
        .store-name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .store-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .store-status.active {
            background: #46b450;
            color: white;
        }
        
        .store-status.inactive {
            background: #dc3232;
            color: white;
        }
        
        .store-description {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .store-url {
            font-size: 12px;
            color: #0073aa;
            text-decoration: none;
            word-break: break-all;
        }
        
        .store-actions {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .store-actions button {
            margin-right: 5px;
        }
        
        .store-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .store-modal-content {
            background-color: #fff;
            margin: 5% auto;
            border-radius: 4px;
            width: 80%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .store-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .store-modal-header h2 {
            margin: 0;
        }
        
        .store-modal-close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .store-modal-close:hover {
            color: #000;
        }
        
        .store-modal-body {
            padding: 20px;
        }
        
        .store-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .logo-upload-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-preview {
            width: 60px;
            height: 60px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
        }
        
        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 3px;
        }
        
        .no-stores-message {
            text-align: center;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-top: 20px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            // Open modal for adding new store
            $('#add-store-btn').click(function() {
                resetForm();
                $('#modal-title').text('<?php _e('Добави магазин', 'parfume-catalog'); ?>');
                $('#store-modal').show();
            });
            
            // Close modal
            $('.store-modal-close').click(function() {
                $('#store-modal').hide();
            });
            
            // Click outside modal to close
            $(window).click(function(e) {
                if (e.target.id === 'store-modal') {
                    $('#store-modal').hide();
                }
            });
            
            // Logo upload
            $('#upload-logo-btn').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Избери лого на магазина', 'parfume-catalog'); ?>',
                    button: {
                        text: '<?php _e('Избери', 'parfume-catalog'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#store-logo').val(attachment.id);
                    $('#logo-preview').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="">');
                    $('#remove-logo-btn').show();
                });
                
                mediaUploader.open();
            });
            
            // Remove logo
            $('#remove-logo-btn').click(function() {
                $('#store-logo').val('');
                $('#logo-preview').html('');
                $(this).hide();
            });
            
            // Save store
            $('#save-store-btn').click(function() {
                var formData = {
                    action: 'parfume_save_store',
                    nonce: '<?php echo wp_create_nonce('parfume_store_action'); ?>',
                    store_id: $('#store-id').val(),
                    store_name: $('#store-name').val(),
                    store_logo: $('#store-logo').val(),
                    store_url: $('#store-url').val(),
                    store_description: $('#store-description').val(),
                    store_status: $('#store-status').val(),
                    store_priority: $('#store-priority').val()
                };
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при запазване', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Edit store
            $(document).on('click', '.edit-store', function() {
                var storeId = $(this).data('store-id');
                
                $.post(ajaxurl, {
                    action: 'parfume_get_store',
                    nonce: '<?php echo wp_create_nonce('parfume_store_action'); ?>',
                    store_id: storeId
                }, function(response) {
                    if (response.success) {
                        populateForm(response.data.store);
                        $('#modal-title').text('<?php _e('Редактирай магазин', 'parfume-catalog'); ?>');
                        $('#store-modal').show();
                    }
                });
            });
            
            // Delete store
            $(document).on('click', '.delete-store', function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                var storeId = $(this).data('store-id');
                
                $.post(ajaxurl, {
                    action: 'parfume_delete_store',
                    nonce: '<?php echo wp_create_nonce('parfume_store_action'); ?>',
                    store_id: storeId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при изтриване', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            function resetForm() {
                $('#store-form')[0].reset();
                $('#store-id').val('');
                $('#store-logo').val('');
                $('#logo-preview').html('');
                $('#remove-logo-btn').hide();
            }
            
            function populateForm(store) {
                $('#store-id').val(store.id);
                $('#store-name').val(store.name);
                $('#store-logo').val(store.logo_id || '');
                $('#store-url').val(store.url || '');
                $('#store-description').val(store.description || '');
                $('#store-status').val(store.status || 'active');
                $('#store-priority').val(store.priority || 1);
                
                if (store.logo_url) {
                    $('#logo-preview').html('<img src="' + store.logo_url + '" alt="">');
                    $('#remove-logo-btn').show();
                } else {
                    $('#logo-preview').html('');
                    $('#remove-logo-btn').hide();
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render individual store card
     */
    private function render_store_card($store_id, $store) {
        $status_class = $store['status'] === 'active' ? 'active' : 'inactive';
        $logo_url = '';
        
        if (!empty($store['logo_id'])) {
            $logo_url = wp_get_attachment_image_url($store['logo_id'], 'thumbnail');
        }
        ?>
        <div class="store-card <?php echo esc_attr($status_class); ?>">
            <div class="store-status <?php echo esc_attr($status_class); ?>">
                <?php echo $store['status'] === 'active' ? __('Активен', 'parfume-catalog') : __('Неактивен', 'parfume-catalog'); ?>
            </div>
            
            <div class="store-card-header">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="" class="store-logo">
                <?php else: ?>
                    <div class="store-logo-placeholder">
                        <?php _e('Лого', 'parfume-catalog'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="store-name">
                    <?php echo esc_html($store['name']); ?>
                </div>
            </div>
            
            <?php if (!empty($store['description'])): ?>
                <div class="store-description">
                    <?php echo esc_html($store['description']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($store['url'])): ?>
                <div>
                    <a href="<?php echo esc_url($store['url']); ?>" target="_blank" class="store-url">
                        <?php echo esc_html($store['url']); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="store-actions">
                <button type="button" class="button button-small edit-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                    <?php _e('Редактирай', 'parfume-catalog'); ?>
                </button>
                <button type="button" class="button button-small delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                    <?php _e('Изтрий', 'parfume-catalog'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get all stores
     */
    private function get_stores() {
        $stores = get_option($this->stores_option, array());
        
        // Sort by priority (higher first)
        uasort($stores, function($a, $b) {
            $priority_a = isset($a['priority']) ? (int)$a['priority'] : 1;
            $priority_b = isset($b['priority']) ? (int)$b['priority'] : 1;
            return $priority_b - $priority_a;
        });
        
        return $stores;
    }
    
    /**
     * Save store via AJAX
     */
    public function save_store() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        $store_name = sanitize_text_field($_POST['store_name']);
        if (empty($store_name)) {
            wp_send_json_error(array('message' => __('Името на магазина е задължително', 'parfume-catalog')));
        }
        
        $stores = get_option($this->stores_option, array());
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Generate new ID if creating new store
        if (empty($store_id)) {
            $store_id = uniqid('store_');
        }
        
        $store_data = array(
            'id' => $store_id,
            'name' => $store_name,
            'logo_id' => absint($_POST['store_logo']),
            'url' => esc_url_raw($_POST['store_url']),
            'description' => sanitize_textarea_field($_POST['store_description']),
            'status' => in_array($_POST['store_status'], array('active', 'inactive')) ? $_POST['store_status'] : 'active',
            'priority' => absint($_POST['store_priority']),
            'created' => isset($stores[$store_id]['created']) ? $stores[$store_id]['created'] : current_time('mysql'),
            'updated' => current_time('mysql')
        );
        
        $stores[$store_id] = $store_data;
        
        if (update_option($this->stores_option, $stores)) {
            wp_send_json_success(array(
                'message' => __('Магазинът е запазен успешно', 'parfume-catalog'),
                'store' => $store_data
            ));
        } else {
            wp_send_json_error(array('message' => __('Грешка при запазване', 'parfume-catalog')));
        }
    }
    
    /**
     * Delete store via AJAX
     */
    public function delete_store() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option($this->stores_option, array());
        
        if (isset($stores[$store_id])) {
            unset($stores[$store_id]);
            
            if (update_option($this->stores_option, $stores)) {
                wp_send_json_success(array('message' => __('Магазинът е изтрит успешно', 'parfume-catalog')));
            } else {
                wp_send_json_error(array('message' => __('Грешка при изтриване', 'parfume-catalog')));
            }
        } else {
            wp_send_json_error(array('message' => __('Магазинът не е намерен', 'parfume-catalog')));
        }
    }
    
    /**
     * Get store data via AJAX
     */
    public function get_store() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option($this->stores_option, array());
        
        if (isset($stores[$store_id])) {
            $store = $stores[$store_id];
            
            // Add logo URL if exists
            if (!empty($store['logo_id'])) {
                $store['logo_url'] = wp_get_attachment_image_url($store['logo_id'], 'thumbnail');
            }
            
            wp_send_json_success(array('store' => $store));
        } else {
            wp_send_json_error(array('message' => __('Магазинът не е намерен', 'parfume-catalog')));
        }
    }
    
    /**
     * Get stores for use in other parts of the plugin
     */
    public static function get_all_stores() {
        $stores = get_option('parfume_stores', array());
        
        // Filter only active stores and sort by priority
        $active_stores = array_filter($stores, function($store) {
            return isset($store['status']) && $store['status'] === 'active';
        });
        
        uasort($active_stores, function($a, $b) {
            $priority_a = isset($a['priority']) ? (int)$a['priority'] : 1;
            $priority_b = isset($b['priority']) ? (int)$b['priority'] : 1;
            return $priority_b - $priority_a;
        });
        
        return $active_stores;
    }
    
    /**
     * Get active stores only
     */
    public static function get_active_stores() {
        return self::get_all_stores();
    }
    
    /**
     * Get store by ID
     */
    public static function get_store_by_id($store_id) {
        $stores = get_option('parfume_stores', array());
        
        if (isset($stores[$store_id])) {
            $store = $stores[$store_id];
            
            // Convert to object format for compatibility
            $store_obj = new stdClass();
            $store_obj->id = $store_id;
            $store_obj->name = isset($store['name']) ? $store['name'] : '';
            $store_obj->logo_url = '';
            
            if (!empty($store['logo_id'])) {
                $logo_url = wp_get_attachment_image_url($store['logo_id'], 'thumbnail');
                if ($logo_url) {
                    $store_obj->logo_url = $logo_url;
                }
            }
            
            $store_obj->base_url = isset($store['url']) ? $store['url'] : '';
            $store_obj->is_active = (isset($store['status']) && $store['status'] === 'active') ? 1 : 0;
            $store_obj->created_at = isset($store['created']) ? $store['created'] : '';
            
            return $store_obj;
        }
        
        return null;
    }
    
    /**
     * Get formatted stores for display in posts
     */
    public static function get_formatted_stores($post_id) {
        // Get stores assigned to this post
        $post_stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (empty($post_stores) || !is_array($post_stores)) {
            return array();
        }
        
        $formatted_stores = array();
        $all_stores = get_option('parfume_stores', array());
        
        foreach ($post_stores as $store_data) {
            if (!isset($store_data['store_id']) || !isset($all_stores[$store_data['store_id']])) {
                continue;
            }
            
            $store_id = $store_data['store_id'];
            $store = $all_stores[$store_id];
            
            if (!isset($store['status']) || $store['status'] !== 'active') {
                continue;
            }
            
            $formatted_store = array(
                'id' => $store_id,
                'name' => isset($store['name']) ? $store['name'] : '',
                'logo_url' => '',
                'product_url' => isset($store_data['product_url']) ? $store_data['product_url'] : '',
                'affiliate_url' => isset($store_data['affiliate_url']) ? $store_data['affiliate_url'] : '',
                'promo_code' => isset($store_data['promo_code']) ? $store_data['promo_code'] : '',
                'promo_code_info' => isset($store_data['promo_code_info']) ? $store_data['promo_code_info'] : '',
                'priority' => isset($store_data['priority']) ? intval($store_data['priority']) : 1
            );
            
            // Get logo URL
            if (!empty($store['logo_id'])) {
                $logo_url = wp_get_attachment_image_url($store['logo_id'], 'thumbnail');
                if ($logo_url) {
                    $formatted_store['logo_url'] = $logo_url;
                }
            }
            
            // Get scraped data if available
            global $wpdb;
            $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
            
            $scraped_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $scraper_table WHERE post_id = %d AND store_id = %s ORDER BY last_scraped DESC LIMIT 1",
                $post_id,
                $store_id
            ));
            
            if ($scraped_data) {
                $formatted_store['scraped_data'] = array(
                    'price' => $scraped_data->price,
                    'old_price' => $scraped_data->old_price,
                    'currency' => $scraped_data->currency,
                    'ml_variants' => $scraped_data->ml_variants ? json_decode($scraped_data->ml_variants, true) : null,
                    'availability' => $scraped_data->availability,
                    'delivery_info' => $scraped_data->delivery_info,
                    'last_scraped' => $scraped_data->last_scraped,
                    'next_scrape' => $scraped_data->next_scrape,
                    'scrape_status' => $scraped_data->scrape_status
                );
            } else {
                $formatted_store['scraped_data'] = null;
            }
            
            $formatted_stores[] = $formatted_store;
        }
        
        // Sort by priority
        usort($formatted_stores, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        return $formatted_stores;
    }
}

// Initialize the admin stores
new Parfume_Admin_Stores();