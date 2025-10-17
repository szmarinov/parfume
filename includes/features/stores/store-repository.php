<?php
/**
 * Store Repository
 * 
 * Data access layer for store-related post meta
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Stores
 * @since 2.0.0
 */

namespace ParfumeReviews\Features\Stores;

/**
 * StoreRepository Class
 * 
 * Handles store data in post meta
 */
class StoreRepository {
    
    /**
     * Meta key for stores
     * 
     * @var string
     */
    private $meta_key = '_parfume_stores';
    
    /**
     * Get all stores for a post
     * 
     * @param int $post_id Post ID
     * @return array
     */
    public function get_post_stores($post_id) {
        $stores = get_post_meta($post_id, $this->meta_key, true);
        
        if (!is_array($stores)) {
            return [];
        }
        
        return $stores;
    }
    
    /**
     * Save stores for a post
     * 
     * @param int $post_id Post ID
     * @param array $stores Stores data
     * @return bool
     */
    public function save_post_stores($post_id, $stores) {
        if (!is_array($stores)) {
            return false;
        }
        
        // Sanitize each store
        $sanitized = [];
        foreach ($stores as $store) {
            $sanitized[] = $this->sanitize_post_store($store);
        }
        
        return update_post_meta($post_id, $this->meta_key, $sanitized);
    }
    
    /**
     * Add store to post
     * 
     * @param int $post_id Post ID
     * @param array $store_data Store data
     * @return bool
     */
    public function add_post_store($post_id, $store_data) {
        $stores = $this->get_post_stores($post_id);
        $stores[] = $this->sanitize_post_store($store_data);
        
        return $this->save_post_stores($post_id, $stores);
    }
    
    /**
     * Update store in post
     * 
     * @param int $post_id Post ID
     * @param int $store_index Store index
     * @param array $store_data Store data
     * @return bool
     */
    public function update_post_store($post_id, $store_index, $store_data) {
        $stores = $this->get_post_stores($post_id);
        
        if (!isset($stores[$store_index])) {
            return false;
        }
        
        $stores[$store_index] = array_merge(
            $stores[$store_index],
            $this->sanitize_post_store($store_data)
        );
        
        return $this->save_post_stores($post_id, $stores);
    }
    
    /**
     * Remove store from post
     * 
     * @param int $post_id Post ID
     * @param int $store_index Store index
     * @return bool
     */
    public function remove_post_store($post_id, $store_index) {
        $stores = $this->get_post_stores($post_id);
        
        if (!isset($stores[$store_index])) {
            return false;
        }
        
        unset($stores[$store_index]);
        $stores = array_values($stores); // Re-index array
        
        return $this->save_post_stores($post_id, $stores);
    }
    
    /**
     * Reorder stores for post
     * 
     * @param int $post_id Post ID
     * @param array $order New order (array of indices)
     * @return bool
     */
    public function reorder_post_stores($post_id, $order) {
        $stores = $this->get_post_stores($post_id);
        
        if (count($order) !== count($stores)) {
            return false;
        }
        
        $reordered = [];
        foreach ($order as $index) {
            if (isset($stores[$index])) {
                $reordered[] = $stores[$index];
            }
        }
        
        return $this->save_post_stores($post_id, $reordered);
    }
    
    /**
     * Sanitize post store data
     * 
     * @param array $store Store data
     * @return array
     */
    private function sanitize_post_store($store) {
        return [
            'store_id' => isset($store['store_id']) ? absint($store['store_id']) : 0,
            'product_url' => isset($store['product_url']) ? esc_url_raw($store['product_url']) : '',
            'affiliate_url' => isset($store['affiliate_url']) ? esc_url_raw($store['affiliate_url']) : '',
            'promo_code' => isset($store['promo_code']) ? sanitize_text_field($store['promo_code']) : '',
            'promo_code_info' => isset($store['promo_code_info']) ? sanitize_text_field($store['promo_code_info']) : '',
            'scraped_data' => isset($store['scraped_data']) ? $this->sanitize_scraped_data($store['scraped_data']) : [],
            'last_scraped' => isset($store['last_scraped']) ? sanitize_text_field($store['last_scraped']) : '',
            'next_scrape' => isset($store['next_scrape']) ? sanitize_text_field($store['next_scrape']) : '',
            'scrape_status' => isset($store['scrape_status']) ? sanitize_text_field($store['scrape_status']) : 'pending'
        ];
    }
    
    /**
     * Sanitize scraped data
     * 
     * @param array $data Scraped data
     * @return array
     */
    private function sanitize_scraped_data($data) {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        
        // Price
        if (isset($data['price'])) {
            $sanitized['price'] = floatval($data['price']);
        }
        
        // Old price
        if (isset($data['old_price'])) {
            $sanitized['old_price'] = floatval($data['old_price']);
        }
        
        // Currency
        if (isset($data['currency'])) {
            $sanitized['currency'] = sanitize_text_field($data['currency']);
        }
        
        // ML variants
        if (isset($data['ml_variants']) && is_array($data['ml_variants'])) {
            $sanitized['ml_variants'] = [];
            foreach ($data['ml_variants'] as $variant) {
                $sanitized['ml_variants'][] = [
                    'ml' => isset($variant['ml']) ? absint($variant['ml']) : 0,
                    'price' => isset($variant['price']) ? floatval($variant['price']) : 0,
                    'old_price' => isset($variant['old_price']) ? floatval($variant['old_price']) : 0,
                    'in_stock' => isset($variant['in_stock']) ? (bool) $variant['in_stock'] : true
                ];
            }
        }
        
        // Availability
        if (isset($data['in_stock'])) {
            $sanitized['in_stock'] = (bool) $data['in_stock'];
        }
        
        // Delivery info
        if (isset($data['delivery'])) {
            $sanitized['delivery'] = sanitize_text_field($data['delivery']);
        }
        
        // Name
        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }
        
        // Image
        if (isset($data['image'])) {
            $sanitized['image'] = esc_url_raw($data['image']);
        }
        
        return $sanitized;
    }
    
    /**
     * Get lowest price from post stores
     * 
     * @param int $post_id Post ID
     * @return float|null
     */
    public function get_lowest_price($post_id) {
        $stores = $this->get_post_stores($post_id);
        $prices = [];
        
        foreach ($stores as $store) {
            if (isset($store['scraped_data']['price']) && $store['scraped_data']['price'] > 0) {
                $prices[] = $store['scraped_data']['price'];
            }
            
            // Check ML variants
            if (isset($store['scraped_data']['ml_variants']) && is_array($store['scraped_data']['ml_variants'])) {
                foreach ($store['scraped_data']['ml_variants'] as $variant) {
                    if (isset($variant['price']) && $variant['price'] > 0) {
                        $prices[] = $variant['price'];
                    }
                }
            }
        }
        
        return !empty($prices) ? min($prices) : null;
    }
    
    /**
     * Get stores that need scraping
     * 
     * @param int $limit Limit
     * @return array Array of [post_id, store_index]
     */
    public function get_stores_for_scraping($limit = 10) {
        global $wpdb;
        
        $results = [];
        $current_time = current_time('mysql');
        
        // Get all posts with stores
        $post_ids = $wpdb->get_col("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '{$this->meta_key}'
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} WHERE post_type = 'parfume' AND post_status = 'publish'
            )
            LIMIT 1000
        ");
        
        foreach ($post_ids as $post_id) {
            $stores = $this->get_post_stores($post_id);
            
            foreach ($stores as $index => $store) {
                // Skip if no product URL
                if (empty($store['product_url'])) {
                    continue;
                }
                
                // Skip if recently scraped and not due for next scrape
                if (!empty($store['next_scrape']) && $store['next_scrape'] > $current_time) {
                    continue;
                }
                
                // Skip if blocked
                if (isset($store['scrape_status']) && $store['scrape_status'] === 'blocked') {
                    continue;
                }
                
                $results[] = [
                    'post_id' => $post_id,
                    'store_index' => $index,
                    'product_url' => $store['product_url'],
                    'store_id' => isset($store['store_id']) ? $store['store_id'] : 0
                ];
                
                if (count($results) >= $limit) {
                    break 2;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Update scraped data for store
     * 
     * @param int $post_id Post ID
     * @param int $store_index Store index
     * @param array $scraped_data Scraped data
     * @param string $status Scrape status
     * @return bool
     */
    public function update_scraped_data($post_id, $store_index, $scraped_data, $status = 'success') {
        $stores = $this->get_post_stores($post_id);
        
        if (!isset($stores[$store_index])) {
            return false;
        }
        
        // Calculate next scrape time
        $settings = get_option('parfume_reviews_settings', []);
        $interval = isset($settings['scrape_interval']) ? absint($settings['scrape_interval']) : 12;
        $next_scrape = date('Y-m-d H:i:s', strtotime("+{$interval} hours"));
        
        $stores[$store_index]['scraped_data'] = $this->sanitize_scraped_data($scraped_data);
        $stores[$store_index]['last_scraped'] = current_time('mysql');
        $stores[$store_index]['next_scrape'] = $next_scrape;
        $stores[$store_index]['scrape_status'] = $status;
        
        return $this->save_post_stores($post_id, $stores);
    }
    
    /**
     * Get scraping statistics
     * 
     * @return array
     */
    public function get_scraping_stats() {
        global $wpdb;
        
        $stats = [
            'total_stores' => 0,
            'pending' => 0,
            'success' => 0,
            'error' => 0,
            'blocked' => 0
        ];
        
        $post_ids = $wpdb->get_col("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '{$this->meta_key}'
        ");
        
        foreach ($post_ids as $post_id) {
            $stores = $this->get_post_stores($post_id);
            
            foreach ($stores as $store) {
                if (empty($store['product_url'])) {
                    continue;
                }
                
                $stats['total_stores']++;
                
                $status = isset($store['scrape_status']) ? $store['scrape_status'] : 'pending';
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            }
        }
        
        return $stats;
    }
}