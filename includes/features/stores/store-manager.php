<?php
/**
 * Store Manager
 * 
 * Manages affiliate stores and their configurations
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Stores
 * @since 2.0.0
 */

namespace Parfume_Reviews\Features\Stores;

use Parfume_Reviews\Core\Container;

/**
 * StoreManager Class
 * 
 * Handles store CRUD operations and management
 */
class StoreManager {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Option name for stores
     * 
     * @var string
     */
    private $option_name = 'parfume_reviews_stores';
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        
        // Register AJAX actions
        add_action('wp_ajax_parfume_add_store', [$this, 'ajax_add_store']);
        add_action('wp_ajax_parfume_update_store', [$this, 'ajax_update_store']);
        add_action('wp_ajax_parfume_delete_store', [$this, 'ajax_delete_store']);
        add_action('wp_ajax_parfume_get_stores', [$this, 'ajax_get_stores']);
    }
    
    /**
     * Get all stores
     * 
     * @return array
     */
    public function get_all_stores() {
        $stores = get_option($this->option_name, []);
        
        if (!is_array($stores)) {
            return [];
        }
        
        return $stores;
    }
    
    /**
     * Get store by ID
     * 
     * @param int $store_id Store ID
     * @return array|null
     */
    public function get_store($store_id) {
        $stores = $this->get_all_stores();
        
        return isset($stores[$store_id]) ? $stores[$store_id] : null;
    }
    
    /**
     * Add new store
     * 
     * @param array $store_data Store data
     * @return int|WP_Error Store ID or error
     */
    public function add_store($store_data) {
        // Validate required fields
        if (empty($store_data['name'])) {
            return new \WP_Error('missing_name', __('Името на магазина е задължително', 'parfume-reviews'));
        }
        
        $stores = $this->get_all_stores();
        
        // Generate new ID
        $new_id = $this->generate_store_id($stores);
        
        // Prepare store data
        $store = [
            'id' => $new_id,
            'name' => sanitize_text_field($store_data['name']),
            'logo_id' => isset($store_data['logo_id']) ? absint($store_data['logo_id']) : 0,
            'logo_url' => isset($store_data['logo_url']) ? esc_url_raw($store_data['logo_url']) : '',
            'domain' => isset($store_data['domain']) ? sanitize_text_field($store_data['domain']) : '',
            'schema' => isset($store_data['schema']) ? $this->sanitize_schema($store_data['schema']) : [],
            'enabled' => isset($store_data['enabled']) ? (bool) $store_data['enabled'] : true,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        // Add store to array
        $stores[$new_id] = $store;
        
        // Save
        update_option($this->option_name, $stores);
        
        // Log action
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Store added - ID: ' . $new_id . ', Name: ' . $store['name']);
        }
        
        return $new_id;
    }
    
    /**
     * Update existing store
     * 
     * @param int $store_id Store ID
     * @param array $store_data Store data
     * @return bool|WP_Error
     */
    public function update_store($store_id, $store_data) {
        $stores = $this->get_all_stores();
        
        if (!isset($stores[$store_id])) {
            return new \WP_Error('store_not_found', __('Магазинът не е намерен', 'parfume-reviews'));
        }
        
        // Update fields
        if (isset($store_data['name'])) {
            $stores[$store_id]['name'] = sanitize_text_field($store_data['name']);
        }
        
        if (isset($store_data['logo_id'])) {
            $stores[$store_id]['logo_id'] = absint($store_data['logo_id']);
        }
        
        if (isset($store_data['logo_url'])) {
            $stores[$store_id]['logo_url'] = esc_url_raw($store_data['logo_url']);
        }
        
        if (isset($store_data['domain'])) {
            $stores[$store_id]['domain'] = sanitize_text_field($store_data['domain']);
        }
        
        if (isset($store_data['schema'])) {
            $stores[$store_id]['schema'] = $this->sanitize_schema($store_data['schema']);
        }
        
        if (isset($store_data['enabled'])) {
            $stores[$store_id]['enabled'] = (bool) $store_data['enabled'];
        }
        
        $stores[$store_id]['updated_at'] = current_time('mysql');
        
        // Save
        update_option($this->option_name, $stores);
        
        // Log action
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Store updated - ID: ' . $store_id);
        }
        
        return true;
    }
    
    /**
     * Delete store
     * 
     * @param int $store_id Store ID
     * @return bool|WP_Error
     */
    public function delete_store($store_id) {
        $stores = $this->get_all_stores();
        
        if (!isset($stores[$store_id])) {
            return new \WP_Error('store_not_found', __('Магазинът не е намерен', 'parfume-reviews'));
        }
        
        // Remove store
        unset($stores[$store_id]);
        
        // Save
        update_option($this->option_name, $stores);
        
        // Log action
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Store deleted - ID: ' . $store_id);
        }
        
        return true;
    }
    
    /**
     * Generate new store ID
     * 
     * @param array $stores Existing stores
     * @return int
     */
    private function generate_store_id($stores) {
        if (empty($stores)) {
            return 1;
        }
        
        $max_id = max(array_keys($stores));
        return $max_id + 1;
    }
    
    /**
     * Sanitize schema data
     * 
     * @param array $schema Schema data
     * @return array
     */
    private function sanitize_schema($schema) {
        if (!is_array($schema)) {
            return [];
        }
        
        $sanitized = [];
        
        $allowed_fields = [
            'price_selector',
            'old_price_selector',
            'ml_selector',
            'availability_selector',
            'delivery_selector',
            'name_selector',
            'image_selector'
        ];
        
        foreach ($allowed_fields as $field) {
            if (isset($schema[$field])) {
                $sanitized[$field] = sanitize_text_field($schema[$field]);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX: Add store
     */
    public function ajax_add_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $store_data = isset($_POST['store_data']) ? $_POST['store_data'] : [];
        
        $result = $this->add_store($store_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success([
            'store_id' => $result,
            'message' => __('Магазинът е добавен успешно', 'parfume-reviews')
        ]);
    }
    
    /**
     * AJAX: Update store
     */
    public function ajax_update_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $store_id = isset($_POST['store_id']) ? absint($_POST['store_id']) : 0;
        $store_data = isset($_POST['store_data']) ? $_POST['store_data'] : [];
        
        if (!$store_id) {
            wp_send_json_error(__('Невалиден ID', 'parfume-reviews'));
        }
        
        $result = $this->update_store($store_id, $store_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success([
            'message' => __('Магазинът е обновен успешно', 'parfume-reviews')
        ]);
    }
    
    /**
     * AJAX: Delete store
     */
    public function ajax_delete_store() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $store_id = isset($_POST['store_id']) ? absint($_POST['store_id']) : 0;
        
        if (!$store_id) {
            wp_send_json_error(__('Невалиден ID', 'parfume-reviews'));
        }
        
        $result = $this->delete_store($store_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success([
            'message' => __('Магазинът е изтрит успешно', 'parfume-reviews')
        ]);
    }
    
    /**
     * AJAX: Get all stores
     */
    public function ajax_get_stores() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $stores = $this->get_all_stores();
        
        wp_send_json_success([
            'stores' => array_values($stores)
        ]);
    }
    
    /**
     * Get store options for select field
     * 
     * @return array
     */
    public function get_store_options() {
        $stores = $this->get_all_stores();
        $options = [];
        
        foreach ($stores as $store) {
            if (!empty($store['enabled'])) {
                $options[$store['id']] = $store['name'];
            }
        }
        
        return $options;
    }
    
    /**
     * Get store name by ID
     * 
     * @param int $store_id Store ID
     * @return string
     */
    public function get_store_name($store_id) {
        $store = $this->get_store($store_id);
        return $store ? $store['name'] : '';
    }
    
    /**
     * Get store logo URL by ID
     * 
     * @param int $store_id Store ID
     * @return string
     */
    public function get_store_logo($store_id) {
        $store = $this->get_store($store_id);
        
        if (!$store) {
            return '';
        }
        
        // Try logo URL first
        if (!empty($store['logo_url'])) {
            return $store['logo_url'];
        }
        
        // Try logo attachment
        if (!empty($store['logo_id'])) {
            $logo_url = wp_get_attachment_image_url($store['logo_id'], 'medium');
            if ($logo_url) {
                return $logo_url;
            }
        }
        
        return '';
    }
    
    /**
     * Get store schema by ID
     * 
     * @param int $store_id Store ID
     * @return array
     */
    public function get_store_schema($store_id) {
        $store = $this->get_store($store_id);
        return $store && isset($store['schema']) ? $store['schema'] : [];
    }
}