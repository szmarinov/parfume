<?php
/**
 * Stores модул за управление на магазини
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Stores {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_save_store', array($this, 'save_store'));
        add_action('wp_ajax_pc_delete_store', array($this, 'delete_store'));
        add_action('wp_ajax_pc_reorder_stores', array($this, 'reorder_stores'));
    }
    
    public function init() {
        // Hook for admin interface
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }
    
    public function admin_init() {
        // Admin hooks here
    }
    
    /**
     * Get all stores
     */
    public function get_stores() {
        $stores = get_option('parfume_catalog_stores', array());
        return is_array($stores) ? $stores : array();
    }
    
    /**
     * Get single store
     */
    public function get_store($store_id) {
        $stores = $this->get_stores();
        return isset($stores[$store_id]) ? $stores[$store_id] : false;
    }
    
    /**
     * Save store
     */
    public function save_store() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $store_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'logo' => esc_url_raw($_POST['logo']),
            'url' => esc_url_raw($_POST['url']),
            'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $stores = $this->get_stores();
        $store_id = sanitize_text_field($_POST['store_id']);
        
        if (empty($store_id)) {
            // New store
            $store_id = uniqid('store_');
        }
        
        $stores[$store_id] = $store_data;
        update_option('parfume_catalog_stores', $stores);
        
        wp_send_json_success(array(
            'message' => __('Магазинът е запазен успешно', 'parfume-catalog'),
            'store_id' => $store_id,
            'store_data' => $store_data
        ));
    }
    
    /**
     * Delete store
     */
    public function delete_store() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = $this->get_stores();
        
        if (isset($stores[$store_id])) {
            unset($stores[$store_id]);
            update_option('parfume_catalog_stores', $stores);
            wp_send_json_success(__('Магазинът е изтрит успешно', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Магазинът не е намерен', 'parfume-catalog'));
        }
    }
    
    /**
     * Reorder stores
     */
    public function reorder_stores() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $order = array_map('sanitize_text_field', $_POST['order']);
        $stores = $this->get_stores();
        $reordered_stores = array();
        
        foreach ($order as $store_id) {
            if (isset($stores[$store_id])) {
                $reordered_stores[$store_id] = $stores[$store_id];
            }
        }
        
        update_option('parfume_catalog_stores', $reordered_stores);
        wp_send_json_success(__('Редът на магазините е обновен', 'parfume-catalog'));
    }
    
    /**
     * Render stores admin page
     */
    public function render_stores_page() {
        $stores = $this->get_stores();
        ?>
        <div class="wrap">
            <h1><?php _e('Магазини', 'parfume-catalog'); ?></h1>
            
            <div class="pc-stores-container">
                <div class="pc-stores-list">
                    <h2><?php _e('Съществуващи магазини', 'parfume-catalog'); ?></h2>
                    
                    <?php if (empty($stores)): ?>
                        <p><?php _e('Няма добавени магазини.', 'parfume-catalog'); ?></p>
                    <?php else: ?>
                        <ul id="pc-stores-sortable" class="pc-stores-sortable">
                            <?php foreach ($stores as $store_id => $store): ?>
                                <li data-store-id="<?php echo esc_attr($store_id); ?>">
                                    <div class="store-item">
                                        <div class="store-logo">
                                            <?php if (!empty($store['logo'])): ?>
                                                <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" width="50">
                                            <?php endif; ?>
                                        </div>
                                        <div class="store-info">
                                            <h4><?php echo esc_html($store['name']); ?></h4>
                                            <p><?php echo esc_url($store['url']); ?></p>
                                            <span class="status status-<?php echo esc_attr($store['status']); ?>">
                                                <?php echo $store['status'] === 'active' ? __('Активен', 'parfume-catalog') : __('Неактивен', 'parfume-catalog'); ?>
                                            </span>
                                        </div>
                                        <div class="store-actions">
                                            <button type="button" class="button edit-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Редактиране', 'parfume-catalog'); ?>
                                            </button>
                                            <button type="button" class="button button-danger delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Изтриване', 'parfume-catalog'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <button type="button" class="button button-primary" id="add-new-store">
                        <?php _e('Добави нов магазин', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <div class="pc-store-form" id="pc-store-form" style="display: none;">
                    <h2 id="form-title"><?php _e('Добави нов магазин', 'parfume-catalog'); ?></h2>
                    
                    <form id="store-form">
                        <input type="hidden" id="store_id" name="store_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="store_name"><?php _e('Име на магазина', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="store_name" name="name" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store_logo"><?php _e('Лого (URL)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="store_logo" name="logo" class="regular-text">
                                    <button type="button" class="button" id="upload-logo"><?php _e('Качи лого', 'parfume-catalog'); ?></button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store_url"><?php _e('URL на магазина', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="store_url" name="url" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store_affiliate_url"><?php _e('Affiliate URL', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="store_affiliate_url" name="affiliate_url" class="regular-text">
                                    <p class="description"><?php _e('Ако е попълнен, ще се използва вместо основния URL', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store_status"><?php _e('Статус', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <select id="store_status" name="status">
                                        <option value="active"><?php _e('Активен', 'parfume-catalog'); ?></option>
                                        <option value="inactive"><?php _e('Неактивен', 'parfume-catalog'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="<?php _e('Запази магазин', 'parfume-catalog'); ?>">
                            <button type="button" class="button" id="cancel-form"><?php _e('Отказ', 'parfume-catalog'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add new store
            $('#add-new-store').on('click', function() {
                $('#pc-store-form').show();
                $('#form-title').text('<?php _e('Добави нов магазин', 'parfume-catalog'); ?>');
                $('#store-form')[0].reset();
                $('#store_id').val('');
            });
            
            // Cancel form
            $('#cancel-form').on('click', function() {
                $('#pc-store-form').hide();
            });
            
            // Edit store
            $('.edit-store').on('click', function() {
                var storeId = $(this).data('store-id');
                // Load store data and populate form
                $('#pc-store-form').show();
                $('#form-title').text('<?php _e('Редактиране на магазин', 'parfume-catalog'); ?>');
                $('#store_id').val(storeId);
                // Populate other fields with AJAX call
            });
            
            // Delete store
            $('.delete-store').on('click', function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                var storeId = $(this).data('store-id');
                $.post(ajaxurl, {
                    action: 'pc_delete_store',
                    store_id: storeId,
                    nonce: '<?php echo wp_create_nonce('pc_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                });
            });
            
            // Save store
            $('#store-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=pc_save_store&nonce=<?php echo wp_create_nonce('pc_admin_nonce'); ?>';
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                });
            });
            
            // Sortable stores
            $('#pc-stores-sortable').sortable({
                update: function(event, ui) {
                    var order = $(this).sortable('toArray', {attribute: 'data-store-id'});
                    $.post(ajaxurl, {
                        action: 'pc_reorder_stores',
                        order: order,
                        nonce: '<?php echo wp_create_nonce('pc_admin_nonce'); ?>'
                    });
                }
            });
        });
        </script>
        <?php
    }
}