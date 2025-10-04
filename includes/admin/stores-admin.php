<?php
/**
 * Stores Admin Page
 * 
 * Admin UI for managing stores (CRUD operations)
 * 
 * @package ParfumeReviews
 */

namespace ParfumeReviews\Admin;

use ParfumeReviews\Features\Stores\StoreManager;

class StoresAdmin {
    
    /**
     * Store Manager instance
     */
    private $store_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->store_manager = StoreManager::get_instance();
    }
    
    /**
     * Add admin menu
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Manage Stores', 'parfume-reviews'),
            __('Stores', 'parfume-reviews'),
            'manage_options',
            'parfume-stores',
            [$this, 'render_page']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ('parfume_page_parfume-stores' !== $hook) {
            return;
        }
        
        // Media uploader for logo
        wp_enqueue_media();
        
        // Styles
        wp_enqueue_style(
            'parfume-stores-admin',
            PARFUME_REVIEWS_URL . 'assets/css/admin-stores-page.css',
            [],
            PARFUME_REVIEWS_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'parfume-stores-admin-page',
            PARFUME_REVIEWS_URL . 'assets/js/admin-stores-page.js',
            ['jquery'],
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-stores-admin-page', 'parfumeStoresAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_stores_admin'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this store? This action cannot be undone.', 'parfume-reviews'),
                'upload_logo' => __('Upload Logo', 'parfume-reviews'),
                'select_logo' => __('Select Logo', 'parfume-reviews'),
                'saving' => __('Saving...', 'parfume-reviews'),
                'saved' => __('Saved!', 'parfume-reviews'),
                'error' => __('Error occurred. Please try again.', 'parfume-reviews'),
            ]
        ]);
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Handle form submissions
        $this->handle_form_submission();
        
        // Get current action
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $store_id = isset($_GET['store_id']) ? sanitize_text_field($_GET['store_id']) : '';
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php _e('Manage Stores', 'parfume-reviews'); ?>
            </h1>
            
            <?php if ('list' === $action) : ?>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=parfume&page=parfume-stores&action=add')); ?>" class="page-title-action">
                    <?php _e('Add New Store', 'parfume-reviews'); ?>
                </a>
            <?php endif; ?>
            
            <hr class="wp-header-end">
            
            <?php $this->render_notices(); ?>
            
            <?php
            switch ($action) {
                case 'add':
                    $this->render_add_form();
                    break;
                    
                case 'edit':
                    $this->render_edit_form($store_id);
                    break;
                    
                case 'list':
                default:
                    $this->render_stores_table();
                    break;
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render stores table
     */
    private function render_stores_table() {
        $stores = $this->store_manager->get_all_stores();
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-logo"><?php _e('Logo', 'parfume-reviews'); ?></th>
                    <th scope="col" class="column-name"><?php _e('Store Name', 'parfume-reviews'); ?></th>
                    <th scope="col" class="column-domain"><?php _e('Domain', 'parfume-reviews'); ?></th>
                    <th scope="col" class="column-status"><?php _e('Status', 'parfume-reviews'); ?></th>
                    <th scope="col" class="column-schema"><?php _e('Schema', 'parfume-reviews'); ?></th>
                    <th scope="col" class="column-actions"><?php _e('Actions', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stores)) : ?>
                    <tr>
                        <td colspan="6" class="no-items">
                            <?php _e('No stores found. Add your first store to get started.', 'parfume-reviews'); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($stores as $store) : ?>
                        <tr>
                            <td class="column-logo">
                                <?php if (!empty($store['logo'])) : ?>
                                    <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" style="max-width: 60px; height: auto;">
                                <?php else : ?>
                                    <span class="dashicons dashicons-store" style="font-size: 40px; color: #ccc;"></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-name">
                                <strong><?php echo esc_html($store['name']); ?></strong>
                            </td>
                            <td class="column-domain">
                                <?php echo esc_html($store['domain']); ?>
                            </td>
                            <td class="column-status">
                                <?php if (isset($store['enabled']) && $store['enabled']) : ?>
                                    <span class="status-badge status-enabled"><?php _e('Enabled', 'parfume-reviews'); ?></span>
                                <?php else : ?>
                                    <span class="status-badge status-disabled"><?php _e('Disabled', 'parfume-reviews'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-schema">
                                <?php if (!empty($store['schema'])) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php _e('Configured', 'parfume-reviews'); ?>
                                <?php else : ?>
                                    <span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
                                    <?php _e('Not configured', 'parfume-reviews'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo esc_url(admin_url('edit.php?post_type=parfume&page=parfume-stores&action=edit&store_id=' . $store['id'])); ?>" class="button button-small">
                                    <?php _e('Edit', 'parfume-reviews'); ?>
                                </a>
                                <button type="button" class="button button-small button-link-delete delete-store" data-store-id="<?php echo esc_attr($store['id']); ?>">
                                    <?php _e('Delete', 'parfume-reviews'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <form method="post" id="delete-store-form" style="display: none;">
            <?php wp_nonce_field('parfume_delete_store', 'parfume_store_nonce'); ?>
            <input type="hidden" name="action" value="delete_store">
            <input type="hidden" name="store_id" id="delete-store-id" value="">
        </form>
        <?php
    }
    
    /**
     * Render add store form
     */
    private function render_add_form() {
        $this->render_store_form();
    }
    
    /**
     * Render edit store form
     */
    private function render_edit_form($store_id) {
        $store = $this->store_manager->get_store($store_id);
        
        if (!$store) {
            echo '<div class="notice notice-error"><p>' . __('Store not found.', 'parfume-reviews') . '</p></div>';
            return;
        }
        
        $this->render_store_form($store);
    }
    
    /**
     * Render store form (add/edit)
     */
    private function render_store_form($store = null) {
        $is_edit = !empty($store);
        $store_id = $is_edit ? $store['id'] : '';
        $name = $is_edit ? $store['name'] : '';
        $domain = $is_edit ? $store['domain'] : '';
        $logo = $is_edit ? ($store['logo'] ?? '') : '';
        $enabled = $is_edit ? ($store['enabled'] ?? true) : true;
        
        ?>
        <form method="post" id="store-form" class="store-form">
            <?php wp_nonce_field('parfume_save_store', 'parfume_store_nonce'); ?>
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update_store' : 'add_store'; ?>">
            <?php if ($is_edit) : ?>
                <input type="hidden" name="store_id" value="<?php echo esc_attr($store_id); ?>">
            <?php endif; ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="store_name"><?php _e('Store Name', 'parfume-reviews'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="store_name" id="store_name" class="regular-text" value="<?php echo esc_attr($name); ?>" required>
                            <p class="description"><?php _e('Enter the store name (e.g., "Notino", "Douglas")', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="store_domain"><?php _e('Domain', 'parfume-reviews'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="store_domain" id="store_domain" class="regular-text" value="<?php echo esc_attr($domain); ?>" required>
                            <p class="description"><?php _e('Enter the store domain (e.g., "notino.bg", "douglas.bg")', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="store_logo"><?php _e('Logo', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <div class="logo-upload-wrapper">
                                <input type="hidden" name="store_logo" id="store_logo" value="<?php echo esc_attr($logo); ?>">
                                <div class="logo-preview">
                                    <?php if ($logo) : ?>
                                        <img src="<?php echo esc_url($logo); ?>" alt="Logo" style="max-width: 150px; height: auto;">
                                    <?php else : ?>
                                        <span class="dashicons dashicons-format-image" style="font-size: 80px; color: #ccc;"></span>
                                    <?php endif; ?>
                                </div>
                                <p>
                                    <button type="button" class="button upload-logo-button">
                                        <?php _e('Upload Logo', 'parfume-reviews'); ?>
                                    </button>
                                    <button type="button" class="button remove-logo-button" <?php echo empty($logo) ? 'style="display:none;"' : ''; ?>>
                                        <?php _e('Remove Logo', 'parfume-reviews'); ?>
                                    </button>
                                </p>
                                <p class="description"><?php _e('Upload a logo image for this store. Recommended size: 200x80px', 'parfume-reviews'); ?></p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="store_enabled"><?php _e('Status', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="store_enabled" id="store_enabled" value="1" <?php checked($enabled, true); ?>>
                                <?php _e('Enable this store', 'parfume-reviews'); ?>
                            </label>
                            <p class="description"><?php _e('Disabled stores will not appear on the frontend.', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php echo $is_edit ? __('Update Store', 'parfume-reviews') : __('Add Store', 'parfume-reviews'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=parfume&page=parfume-stores')); ?>" class="button">
                    <?php _e('Cancel', 'parfume-reviews'); ?>
                </a>
            </p>
        </form>
        <?php
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission() {
        if (!isset($_POST['action'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['parfume_store_nonce']) || !wp_verify_nonce($_POST['parfume_store_nonce'], 'parfume_save_store')) {
            if (isset($_POST['parfume_store_nonce']) && !wp_verify_nonce($_POST['parfume_store_nonce'], 'parfume_delete_store')) {
                return;
            }
        }
        
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'add_store':
                $this->handle_add_store();
                break;
                
            case 'update_store':
                $this->handle_update_store();
                break;
                
            case 'delete_store':
                $this->handle_delete_store();
                break;
        }
    }
    
    /**
     * Handle add store
     */
    private function handle_add_store() {
        $name = isset($_POST['store_name']) ? sanitize_text_field($_POST['store_name']) : '';
        $domain = isset($_POST['store_domain']) ? sanitize_text_field($_POST['store_domain']) : '';
        $logo = isset($_POST['store_logo']) ? esc_url_raw($_POST['store_logo']) : '';
        $enabled = isset($_POST['store_enabled']);
        
        if (empty($name) || empty($domain)) {
            add_settings_error('parfume_stores', 'invalid_data', __('Store name and domain are required.', 'parfume-reviews'), 'error');
            return;
        }
        
        $store_id = $this->store_manager->add_store([
            'name' => $name,
            'domain' => $domain,
            'logo' => $logo,
            'enabled' => $enabled,
        ]);
        
        if ($store_id) {
            add_settings_error('parfume_stores', 'store_added', __('Store added successfully!', 'parfume-reviews'), 'success');
            
            // Redirect to edit page
            wp_redirect(admin_url('edit.php?post_type=parfume&page=parfume-stores&action=edit&store_id=' . $store_id));
            exit;
        } else {
            add_settings_error('parfume_stores', 'add_failed', __('Failed to add store. Please try again.', 'parfume-reviews'), 'error');
        }
    }
    
    /**
     * Handle update store
     */
    private function handle_update_store() {
        $store_id = isset($_POST['store_id']) ? sanitize_text_field($_POST['store_id']) : '';
        $name = isset($_POST['store_name']) ? sanitize_text_field($_POST['store_name']) : '';
        $domain = isset($_POST['store_domain']) ? sanitize_text_field($_POST['store_domain']) : '';
        $logo = isset($_POST['store_logo']) ? esc_url_raw($_POST['store_logo']) : '';
        $enabled = isset($_POST['store_enabled']);
        
        if (empty($store_id) || empty($name) || empty($domain)) {
            add_settings_error('parfume_stores', 'invalid_data', __('Invalid data provided.', 'parfume-reviews'), 'error');
            return;
        }
        
        $result = $this->store_manager->update_store($store_id, [
            'name' => $name,
            'domain' => $domain,
            'logo' => $logo,
            'enabled' => $enabled,
        ]);
        
        if ($result) {
            add_settings_error('parfume_stores', 'store_updated', __('Store updated successfully!', 'parfume-reviews'), 'success');
        } else {
            add_settings_error('parfume_stores', 'update_failed', __('Failed to update store.', 'parfume-reviews'), 'error');
        }
    }
    
    /**
     * Handle delete store
     */
    private function handle_delete_store() {
        $store_id = isset($_POST['store_id']) ? sanitize_text_field($_POST['store_id']) : '';
        
        if (empty($store_id)) {
            add_settings_error('parfume_stores', 'invalid_id', __('Invalid store ID.', 'parfume-reviews'), 'error');
            return;
        }
        
        $result = $this->store_manager->delete_store($store_id);
        
        if ($result) {
            add_settings_error('parfume_stores', 'store_deleted', __('Store deleted successfully!', 'parfume-reviews'), 'success');
            
            // Redirect to list
            wp_redirect(admin_url('edit.php?post_type=parfume&page=parfume-stores'));
            exit;
        } else {
            add_settings_error('parfume_stores', 'delete_failed', __('Failed to delete store.', 'parfume-reviews'), 'error');
        }
    }
    
    /**
     * Render admin notices
     */
    private function render_notices() {
        settings_errors('parfume_stores');
    }
    
}