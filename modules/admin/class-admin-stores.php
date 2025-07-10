<?php
/**
 * Admin Stores Management Class
 * 
 * Handles store management in admin panel
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin_Stores {
    
    private $stores_option = 'parfume_catalog_stores';
    
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
        
        wp_enqueue_script(
            'parfume-admin-stores',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin-stores.js',
            array('jquery', 'jquery-ui-sortable'),
            PARFUME_CATALOG_VERSION,
            true
        );
        
        wp_enqueue_style(
            'parfume-admin-stores',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin-stores.css',
            array(),
            PARFUME_CATALOG_VERSION
        );
        
        wp_localize_script('parfume-admin-stores', 'parfumeStores', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_store_action'),
            'strings' => array(
                'confirmDelete' => __('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-catalog'),
                'savingStore' => __('Запазване...', 'parfume-catalog'),
                'storeNameRequired' => __('Името на магазина е задължително', 'parfume-catalog'),
                'errorSaving' => __('Грешка при запазване', 'parfume-catalog'),
                'errorDeleting' => __('Грешка при изтриване', 'parfume-catalog'),
                'selectLogo' => __('Избери лого', 'parfume-catalog'),
                'changeLogo' => __('Смени лого', 'parfume-catalog'),
                'removeLogo' => __('Премахни лого', 'parfume-catalog')
            )
        ));
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
                        <?php wp_nonce_field('parfume_store_action', 'parfume_store_nonce'); ?>
                        
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
                                        <input type="hidden" id="store-logo" name="store_logo" value="" />
                                        <div class="logo-preview">
                                            <img id="logo-preview-img" src="" style="display: none; max-width: 150px; height: auto;" />
                                        </div>
                                        <div class="logo-buttons">
                                            <button type="button" id="upload-logo-btn" class="button">
                                                <?php _e('Избери лого', 'parfume-catalog'); ?>
                                            </button>
                                            <button type="button" id="remove-logo-btn" class="button" style="display: none;">
                                                <?php _e('Премахни лого', 'parfume-catalog'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php _e('Препоръчителен размер: 200x60px или по-малко.', 'parfume-catalog'); ?></p>
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
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="store-description"><?php _e('Описание', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <textarea id="store-description" 
                                              name="store_description" 
                                              class="large-text" 
                                              rows="3"></textarea>
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
                                           min="1" 
                                           max="100" 
                                           value="1" />
                                    <p class="description"><?php _e('По-високият приоритет показва магазина по-отгоре (1-100)', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <div class="store-modal-footer">
                    <button type="button" id="save-store-btn" class="button button-primary">
                        <?php _e('Запази магазин', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button store-modal-close">
                        <?php _e('Откажи', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Loading overlay -->
        <div id="loading-overlay" style="display: none;">
            <div class="loading-spinner"></div>
        </div>
        <?php
    }
    
    /**
     * Render individual store card
     */
    private function render_store_card($store_id, $store) {
        $logo_url = '';
        if (!empty($store['logo_id'])) {
            $logo_url = wp_get_attachment_image_url($store['logo_id'], 'thumbnail');
        }
        
        $status_class = isset($store['status']) && $store['status'] === 'active' ? 'active' : 'inactive';
        $status_text = isset($store['status']) && $store['status'] === 'active' ? __('Активен', 'parfume-catalog') : __('Неактивен', 'parfume-catalog');
        ?>
        <div class="store-card <?php echo esc_attr($status_class); ?>" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-card-header">
                <div class="store-logo">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($store['name']); ?>" />
                    <?php else: ?>
                        <div class="no-logo"><?php _e('Няма лого', 'parfume-catalog'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="store-info">
                    <h3><?php echo esc_html($store['name']); ?></h3>
                    <span class="store-status status-<?php echo esc_attr($store['status'] ?? 'active'); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>
                </div>
            </div>
            
            <div class="store-card-body">
                <?php if (!empty($store['description'])): ?>
                    <p class="store-description"><?php echo esc_html(wp_trim_words($store['description'], 15)); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($store['url'])): ?>
                    <p class="store-url">
                        <a href="<?php echo esc_url($store['url']); ?>" target="_blank">
                            <?php echo esc_html(parse_url($store['url'], PHP_URL_HOST)); ?>
                        </a>
                    </p>
                <?php endif; ?>
                
                <div class="store-meta">
                    <span class="priority">
                        <?php _e('Приоритет:', 'parfume-catalog'); ?> 
                        <?php echo esc_html($store['priority'] ?? '1'); ?>
                    </span>
                    <span class="created">
                        <?php _e('Създаден:', 'parfume-catalog'); ?> 
                        <?php echo esc_html(mysql2date('d.m.Y', $store['created'] ?? current_time('mysql'))); ?>
                    </span>
                </div>
            </div>
            
            <div class="store-card-actions">
                <button type="button" class="button edit-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                    <?php _e('Редактирай', 'parfume-catalog'); ?>
                </button>
                <button type="button" class="button delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                    <?php _e('Изтрий', 'parfume-catalog'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get stores for use in other parts of the plugin
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
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_store_action', 'nonce');
        
        // Validate store name
        $store_name = sanitize_text_field($_POST['store_name'] ?? '');
        if (empty($store_name)) {
            wp_send_json_error(array('message' => __('Името на магазина е задължително', 'parfume-catalog')));
        }
        
        $stores = get_option($this->stores_option, array());
        $store_id = sanitize_text_field($_POST['store_id'] ?? '');
        
        // Generate new ID if creating new store
        if (empty($store_id)) {
            $store_id = 'store_' . uniqid();
        }
        
        $store_data = array(
            'id' => $store_id,
            'name' => $store_name,
            'logo_id' => absint($_POST['store_logo'] ?? 0),
            'url' => esc_url_raw($_POST['store_url'] ?? ''),
            'description' => sanitize_textarea_field($_POST['store_description'] ?? ''),
            'status' => in_array($_POST['store_status'] ?? 'active', array('active', 'inactive')) ? $_POST['store_status'] : 'active',
            'priority' => max(1, min(100, absint($_POST['store_priority'] ?? 1))),
            'created' => isset($stores[$store_id]['created']) ? $stores[$store_id]['created'] : current_time('mysql'),
            'updated' => current_time('mysql')
        );
        
        $stores[$store_id] = $store_data;
        
        if (update_option($this->stores_option, $stores)) {
            // Add logo URL for response
            if (!empty($store_data['logo_id'])) {
                $store_data['logo_url'] = wp_get_attachment_image_url($store_data['logo_id'], 'thumbnail');
            }
            
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
        
        $store_id = sanitize_text_field($_POST['store_id'] ?? '');
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
        
        $store_id = sanitize_text_field($_POST['store_id'] ?? '');
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
        $stores = get_option('parfume_catalog_stores', array());
        
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
        $stores = get_option('parfume_catalog_stores', array());
        
        if (isset($stores[$store_id])) {
            $store = $stores[$store_id];
            
            // Convert to object format for compatibility
            $store_obj = new stdClass();
            $store_obj->id = $store_id;
            $store_obj->name = isset($store['name']) ? $store['name'] : '';
            $store_obj->logo_id = isset($store['logo_id']) ? $store['logo_id'] : 0;
            $store_obj->url = isset($store['url']) ? $store['url'] : '';
            $store_obj->description = isset($store['description']) ? $store['description'] : '';
            $store_obj->status = isset($store['status']) ? $store['status'] : 'active';
            $store_obj->priority = isset($store['priority']) ? $store['priority'] : 1;
            $store_obj->created = isset($store['created']) ? $store['created'] : '';
            $store_obj->updated = isset($store['updated']) ? $store['updated'] : '';
            
            if (!empty($store_obj->logo_id)) {
                $store_obj->logo_url = wp_get_attachment_image_url($store_obj->logo_id, 'thumbnail');
            }
            
            return $store_obj;
        }
        
        return null;
    }
}