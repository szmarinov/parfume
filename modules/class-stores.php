<?php
/**
 * Управление на магазини
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Stores {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_add_store', array($this, 'add_store'));
        add_action('wp_ajax_pc_edit_store', array($this, 'edit_store'));
        add_action('wp_ajax_pc_delete_store', array($this, 'delete_store'));
        add_action('wp_ajax_pc_get_store', array($this, 'get_store'));
    }
    
    public function init() {
        // Инициализация на магазини
        $this->init_default_stores();
    }
    
    /**
     * Инициализация на default магазини
     */
    private function init_default_stores() {
        $stores = get_option('parfume_catalog_stores', array());
        
        if (empty($stores)) {
            $default_stores = array(
                array(
                    'id' => 1,
                    'name' => 'Parfium.bg',
                    'logo' => '',
                    'url' => 'https://parfium.bg',
                    'description' => 'Онлайн магазин за парфюми'
                ),
                array(
                    'id' => 2,
                    'name' => 'Douglas',
                    'logo' => '',
                    'url' => 'https://douglas.bg',
                    'description' => 'Козметичен магазин с парфюми'
                )
            );
            
            update_option('parfume_catalog_stores', $default_stores);
        }
    }
    
    /**
     * Добавяне на нов магазин (AJAX)
     */
    public function add_store() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $name = sanitize_text_field($_POST['store_name']);
        $url = sanitize_url($_POST['store_url']);
        $description = sanitize_textarea_field($_POST['store_description']);
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Името на магазина е задължително', 'parfume-catalog')));
        }
        
        $stores = get_option('parfume_catalog_stores', array());
        
        // Генериране на ново ID
        $new_id = 1;
        if (!empty($stores)) {
            $ids = array_column($stores, 'id');
            $new_id = max($ids) + 1;
        }
        
        // Обработка на лого
        $logo_url = '';
        if (!empty($_FILES['store_logo']['name'])) {
            $logo_url = $this->upload_store_logo($_FILES['store_logo'], $new_id);
        }
        
        $new_store = array(
            'id' => $new_id,
            'name' => $name,
            'logo' => $logo_url,
            'url' => $url,
            'description' => $description,
            'created' => current_time('mysql')
        );
        
        $stores[] = $new_store;
        update_option('parfume_catalog_stores', $stores);
        
        wp_send_json_success(array(
            'message' => __('Магазинът е добавен успешно', 'parfume-catalog'),
            'store' => $new_store
        ));
    }
    
    /**
     * Редактиране на магазин (AJAX)
     */
    public function edit_store() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $store_id = intval($_POST['store_id']);
        $name = sanitize_text_field($_POST['store_name']);
        $url = sanitize_url($_POST['store_url']);
        $description = sanitize_textarea_field($_POST['store_description']);
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Името на магазина е задължително', 'parfume-catalog')));
        }
        
        $stores = get_option('parfume_catalog_stores', array());
        $store_key = null;
        
        // Намиране на магазина
        foreach ($stores as $key => $store) {
            if ($store['id'] == $store_id) {
                $store_key = $key;
                break;
            }
        }
        
        if ($store_key === null) {
            wp_send_json_error(array('message' => __('Магазинът не е намерен', 'parfume-catalog')));
        }
        
        // Обработка на ново лого
        $logo_url = $stores[$store_key]['logo'];
        if (!empty($_FILES['store_logo']['name'])) {
            $logo_url = $this->upload_store_logo($_FILES['store_logo'], $store_id);
        }
        
        // Обновяване на данните
        $stores[$store_key]['name'] = $name;
        $stores[$store_key]['logo'] = $logo_url;
        $stores[$store_key]['url'] = $url;
        $stores[$store_key]['description'] = $description;
        $stores[$store_key]['updated'] = current_time('mysql');
        
        update_option('parfume_catalog_stores', $stores);
        
        wp_send_json_success(array(
            'message' => __('Магазинът е обновен успешно', 'parfume-catalog'),
            'store' => $stores[$store_key]
        ));
    }
    
    /**
     * Изтриване на магазин (AJAX)
     */
    public function delete_store() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $store_id = intval($_POST['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        // Намиране и изтриване на магазина
        foreach ($stores as $key => $store) {
            if ($store['id'] == $store_id) {
                // Изтриване на лого файла
                if (!empty($store['logo'])) {
                    $upload_dir = wp_upload_dir();
                    $logo_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $store['logo']);
                    if (file_exists($logo_path)) {
                        unlink($logo_path);
                    }
                }
                
                unset($stores[$key]);
                break;
            }
        }
        
        update_option('parfume_catalog_stores', $stores);
        
        wp_send_json_success(array(
            'message' => __('Магазинът е изтрит успешно', 'parfume-catalog')
        ));
    }
    
    /**
     * Получаване на данни за магазин (AJAX)
     */
    public function get_store() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $store_id = intval($_POST['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        foreach ($stores as $store) {
            if ($store['id'] == $store_id) {
                wp_send_json_success($store);
            }
        }
        
        wp_send_json_error(array('message' => __('Магазинът не е намерен', 'parfume-catalog')));
    }
    
    /**
     * Качване на лого за магазин
     */
    private function upload_store_logo($file, $store_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (!empty($uploaded_file['error'])) {
            return '';
        }
        
        // Създаване на папка за лога
        $upload_dir = wp_upload_dir();
        $store_dir = $upload_dir['basedir'] . '/parfume-catalog/stores/';
        
        if (!file_exists($store_dir)) {
            wp_mkdir_p($store_dir);
        }
        
        // Преместване на файла
        $file_extension = pathinfo($uploaded_file['file'], PATHINFO_EXTENSION);
        $new_filename = 'store-' . $store_id . '.' . $file_extension;
        $new_file_path = $store_dir . $new_filename;
        
        if (copy($uploaded_file['file'], $new_file_path)) {
            unlink($uploaded_file['file']); // Изтриване на временния файл
            return $upload_dir['baseurl'] . '/parfume-catalog/stores/' . $new_filename;
        }
        
        return '';
    }
    
    /**
     * Получаване на всички магазини
     */
    public static function get_all_stores() {
        return get_option('parfume_catalog_stores', array());
    }
    
    /**
     * Получаване на магазин по ID
     */
    public static function get_store_by_id($store_id) {
        $stores = self::get_all_stores();
        
        foreach ($stores as $store) {
            if ($store['id'] == $store_id) {
                return $store;
            }
        }
        
        return null;
    }
    
    /**
     * Рендериране на stores секция в admin
     */
    public function render_stores_section() {
        $stores = self::get_all_stores();
        ?>
        <div class="pc-stores-wrapper">
            <div class="pc-stores-header">
                <h3><?php _e('Магазини', 'parfume-catalog'); ?></h3>
                <button type="button" class="button button-primary" id="add-new-store">
                    <?php _e('Добави нов магазин', 'parfume-catalog'); ?>
                </button>
            </div>
            
            <div class="pc-stores-list">
                <?php if (!empty($stores)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'parfume-catalog'); ?></th>
                                <th><?php _e('Лого', 'parfume-catalog'); ?></th>
                                <th><?php _e('Име', 'parfume-catalog'); ?></th>
                                <th><?php _e('URL', 'parfume-catalog'); ?></th>
                                <th><?php _e('Описание', 'parfume-catalog'); ?></th>
                                <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stores as $store): ?>
                                <tr>
                                    <td><?php echo $store['id']; ?></td>
                                    <td>
                                        <?php if (!empty($store['logo'])): ?>
                                            <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" style="max-width: 50px; max-height: 50px;">
                                        <?php else: ?>
                                            <span class="dashicons dashicons-store"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($store['name']); ?></td>
                                    <td>
                                        <?php if (!empty($store['url'])): ?>
                                            <a href="<?php echo esc_url($store['url']); ?>" target="_blank"><?php echo esc_html($store['url']); ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($store['description']); ?></td>
                                    <td>
                                        <button type="button" class="button edit-store" data-store-id="<?php echo $store['id']; ?>">
                                            <?php _e('Редактирай', 'parfume-catalog'); ?>
                                        </button>
                                        <button type="button" class="button button-link-delete delete-store" data-store-id="<?php echo $store['id']; ?>">
                                            <?php _e('Изтрий', 'parfume-catalog'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('Няма добавени магазини.', 'parfume-catalog'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal за добавяне/редактиране на магазин -->
        <div id="store-modal" class="pc-modal" style="display: none;">
            <div class="pc-modal-content">
                <div class="pc-modal-header">
                    <h2 id="store-modal-title"><?php _e('Добави нов магазин', 'parfume-catalog'); ?></h2>
                    <span class="pc-modal-close">&times;</span>
                </div>
                
                <form id="store-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('pc_nonce', 'pc_nonce'); ?>
                    <input type="hidden" id="store_id" name="store_id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="store_name"><?php _e('Име на магазина', 'parfume-catalog'); ?> *</label></th>
                            <td><input type="text" id="store_name" name="store_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="store_url"><?php _e('URL', 'parfume-catalog'); ?></label></th>
                            <td><input type="url" id="store_url" name="store_url" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="store_logo"><?php _e('Лого', 'parfume-catalog'); ?></label></th>
                            <td>
                                <input type="file" id="store_logo" name="store_logo" accept="image/*">
                                <div id="logo_preview"></div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="store_description"><?php _e('Описание', 'parfume-catalog'); ?></label></th>
                            <td><textarea id="store_description" name="store_description" rows="3" class="large-text"></textarea></td>
                        </tr>
                    </table>
                    
                    <div class="pc-modal-footer">
                        <button type="submit" class="button button-primary"><?php _e('Запази', 'parfume-catalog'); ?></button>
                        <button type="button" class="button pc-modal-cancel"><?php _e('Отказ', 'parfume-catalog'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        
        <style>
        .pc-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .pc-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
        }
        
        .pc-modal-header {
            padding: 15px 20px;
            background: #f1f1f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pc-modal-header h2 {
            margin: 0;
        }
        
        .pc-modal-close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .pc-modal-close:hover {
            color: #999;
        }
        
        .pc-modal form {
            padding: 20px;
        }
        
        .pc-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .pc-modal-footer .button {
            margin-left: 10px;
        }
        
        #logo_preview img {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Отваряне на modal за добавяне
            $('#add-new-store').on('click', function() {
                $('#store-modal-title').text('<?php _e('Добави нов магазин', 'parfume-catalog'); ?>');
                $('#store-form')[0].reset();
                $('#store_id').val('');
                $('#logo_preview').empty();
                $('#store-modal').show();
            });
            
            // Отваряне на modal за редактиране
            $('.edit-store').on('click', function() {
                var storeId = $(this).data('store-id');
                
                $.post(ajaxurl, {
                    action: 'pc_get_store',
                    store_id: storeId,
                    nonce: $('#pc_nonce').val()
                }, function(response) {
                    if (response.success) {
                        var store = response.data;
                        $('#store-modal-title').text('<?php _e('Редактирай магазин', 'parfume-catalog'); ?>');
                        $('#store_id').val(store.id);
                        $('#store_name').val(store.name);
                        $('#store_url').val(store.url);
                        $('#store_description').val(store.description);
                        
                        if (store.logo) {
                            $('#logo_preview').html('<img src="' + store.logo + '" alt="' + store.name + '">');
                        }
                        
                        $('#store-modal').show();
                    }
                });
            });
            
            // Затваряне на modal
            $('.pc-modal-close, .pc-modal-cancel').on('click', function() {
                $('#store-modal').hide();
            });
            
            // Затваряне при клик извън modal
            $(window).on('click', function(event) {
                if (event.target.id === 'store-modal') {
                    $('#store-modal').hide();
                }
            });
            
            // Submitting form
            $('#store-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                var action = $('#store_id').val() ? 'pc_edit_store' : 'pc_add_store';
                formData.append('action', action);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Изтриване на магазин
            $('.delete-store').on('click', function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                var storeId = $(this).data('store-id');
                
                $.post(ajaxurl, {
                    action: 'pc_delete_store',
                    store_id: storeId,
                    nonce: $('#pc_nonce').val()
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            });
            
            // Preview на лого
            $('#store_logo').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#logo_preview').html('<img src="' + e.target.result + '" alt="Preview">');
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
        </script>
        <?php
    }
}