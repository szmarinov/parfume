<?php
/**
 * Scraper Feature
 * 
 * Handles price scraping and data extraction from external sources
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Scraper
 * @since 2.0.0
 */

namespace Parfume_Reviews\Features\Scraper;

use Parfume_Reviews\Core\Container;

/**
 * Scraper Class
 * 
 * Manages price scraping and product data extraction
 */
class Scraper {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Scraper enabled status
     * 
     * @var bool
     */
    private $enabled = false;
    
    /**
     * Auto update prices status
     * 
     * @var bool
     */
    private $auto_update = false;
    
    /**
     * Update interval in hours
     * 
     * @var int
     */
    private $update_interval = 24;
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        
        // Get settings
        $settings = get_option('parfume_reviews_settings', []);
        $this->enabled = isset($settings['enable_scraper']) ? (bool) $settings['enable_scraper'] : false;
        $this->auto_update = isset($settings['auto_update_prices']) ? (bool) $settings['auto_update_prices'] : false;
        $this->update_interval = isset($settings['update_interval']) ? absint($settings['update_interval']) : 24;
        
        // Schedule auto update if enabled
        if ($this->auto_update) {
            add_action('parfume_reviews_auto_update_prices', [$this, 'auto_update_all_prices']);
            
            if (!wp_next_scheduled('parfume_reviews_auto_update_prices')) {
                wp_schedule_event(time(), 'hourly', 'parfume_reviews_auto_update_prices');
            }
        }
    }
    
    /**
     * AJAX handler: Scrape product data
     */
    public function ajax_scrape() {
        check_ajax_referer('parfume_scraper_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        if (!$this->enabled) {
            wp_send_json_error(__('Scraper е деактивиран', 'parfume-reviews'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(__('Невалиден URL', 'parfume-reviews'));
        }
        
        try {
            $data = $this->scrape_url($url);
            wp_send_json_success($data);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler: Update price for a store
     */
    public function ajax_update_price() {
        check_ajax_referer('parfume_scraper_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        if (!$this->enabled) {
            wp_send_json_error(__('Scraper е деактивиран', 'parfume-reviews'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $store_index = isset($_POST['store_index']) ? absint($_POST['store_index']) : -1;
        
        if (!$post_id || $store_index < 0) {
            wp_send_json_error(__('Невалидни данни', 'parfume-reviews'));
        }
        
        try {
            $result = $this->update_store_price($post_id, $store_index);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Scrape URL for product data
     * 
     * @param string $url URL to scrape
     * @return array Scraped data
     * @throws \Exception If scraping fails
     */
    public function scrape_url($url) {
        // Parse URL to determine the store
        $host = parse_url($url, PHP_URL_HOST);
        $scraper_method = $this->get_scraper_method($host);
        
        if (!$scraper_method) {
            throw new \Exception(__('Неподдържан магазин', 'parfume-reviews'));
        }
        
        // Fetch page content
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            throw new \Exception(__('Празно съдържание', 'parfume-reviews'));
        }
        
        // Parse data using appropriate method
        return call_user_func([$this, $scraper_method], $body, $url);
    }
    
    /**
     * Get scraper method based on hostname
     * 
     * @param string $host Hostname
     * @return string|false Method name or false
     */
    private function get_scraper_method($host) {
        $scrapers = [
            'notino.bg' => 'scrape_notino',
            'parfimo.bg' => 'scrape_parfimo',
            'douglas.bg' => 'scrape_douglas',
            'makeup.bg' => 'scrape_makeup',
        ];
        
        foreach ($scrapers as $domain => $method) {
            if (strpos($host, $domain) !== false) {
                return $method;
            }
        }
        
        return 'scrape_generic';
    }
    
    /**
     * Generic scraper (basic implementation)
     * 
     * @param string $html HTML content
     * @param string $url Source URL
     * @return array Scraped data
     */
    private function scrape_generic($html, $url) {
        $data = [
            'name' => '',
            'price' => null,
            'currency' => 'BGN',
            'in_stock' => true,
            'image' => '',
            'url' => $url
        ];
        
        // Try to extract price using common patterns
        if (preg_match('/(\d+[\.,]\d{2})\s*(лв|bgn|лева)/i', $html, $matches)) {
            $data['price'] = floatval(str_replace(',', '.', $matches[1]));
        }
        
        // Try to extract product name from title
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            $data['name'] = trim($matches[1]);
        }
        
        // Check stock status
        if (preg_match('/(out of stock|изчерпан|няма наличност)/i', $html)) {
            $data['in_stock'] = false;
        }
        
        return $data;
    }
    
    /**
     * Scraper for Notino.bg
     * 
     * @param string $html HTML content
     * @param string $url Source URL
     * @return array Scraped data
     */
    private function scrape_notino($html, $url) {
        $data = [
            'name' => '',
            'price' => null,
            'currency' => 'BGN',
            'in_stock' => true,
            'image' => '',
            'url' => $url
        ];
        
        // Extract price
        if (preg_match('/data-testid="pdPrice"[^>]*>([^<]+)/i', $html, $matches)) {
            $price = preg_replace('/[^\d\.,]/', '', $matches[1]);
            $data['price'] = floatval(str_replace(',', '.', $price));
        }
        
        // Extract product name
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            $data['name'] = trim($matches[1]);
        }
        
        // Check stock
        if (preg_match('/out-of-stock|изчерпан/i', $html)) {
            $data['in_stock'] = false;
        }
        
        return $data;
    }
    
    /**
     * Scraper for Parfimo.bg
     * 
     * @param string $html HTML content
     * @param string $url Source URL
     * @return array Scraped data
     */
    private function scrape_parfimo($html, $url) {
        return $this->scrape_generic($html, $url);
    }
    
    /**
     * Scraper for Douglas.bg
     * 
     * @param string $html HTML content
     * @param string $url Source URL
     * @return array Scraped data
     */
    private function scrape_douglas($html, $url) {
        return $this->scrape_generic($html, $url);
    }
    
    /**
     * Scraper for Makeup.bg
     * 
     * @param string $html HTML content
     * @param string $url Source URL
     * @return array Scraped data
     */
    private function scrape_makeup($html, $url) {
        return $this->scrape_generic($html, $url);
    }
    
    /**
     * Update price for a specific store
     * 
     * @param int $post_id Post ID
     * @param int $store_index Store index in array
     * @return array Updated data
     * @throws \Exception If update fails
     */
    public function update_store_price($post_id, $store_index) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($stores) || !isset($stores[$store_index])) {
            throw new \Exception(__('Магазинът не е намерен', 'parfume-reviews'));
        }
        
        $store = $stores[$store_index];
        
        if (empty($store['url'])) {
            throw new \Exception(__('Липсва URL на магазина', 'parfume-reviews'));
        }
        
        // Scrape current data
        $scraped_data = $this->scrape_url($store['url']);
        
        // Update store data
        if (isset($scraped_data['price']) && $scraped_data['price'] !== null) {
            $stores[$store_index]['price'] = $scraped_data['price'];
        }
        
        if (isset($scraped_data['in_stock'])) {
            $stores[$store_index]['in_stock'] = $scraped_data['in_stock'];
        }
        
        // Save updated stores
        update_post_meta($post_id, '_parfume_stores', $stores);
        
        // Update last scrape timestamp
        update_post_meta($post_id, '_parfume_last_price_update', current_time('mysql'));
        
        return [
            'price' => $stores[$store_index]['price'],
            'in_stock' => $stores[$store_index]['in_stock'],
            'updated_at' => current_time('mysql')
        ];
    }
    
    /**
     * Update all prices for a parfume
     * 
     * @param int $post_id Post ID
     * @return array Results
     */
    public function update_all_prices($post_id) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($stores)) {
            return ['success' => 0, 'errors' => 0];
        }
        
        $success = 0;
        $errors = 0;
        
        foreach ($stores as $index => $store) {
            if (empty($store['url'])) {
                continue;
            }
            
            try {
                $this->update_store_price($post_id, $index);
                $success++;
            } catch (\Exception $e) {
                $errors++;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Price update error: ' . $e->getMessage());
                }
            }
            
            // Small delay to avoid rate limiting
            sleep(1);
        }
        
        return ['success' => $success, 'errors' => $errors];
    }
    
    /**
     * Auto update all prices (scheduled task)
     */
    public function auto_update_all_prices() {
        if (!$this->enabled || !$this->auto_update) {
            return;
        }
        
        // Get all parfumes that need update
        $parfumes = $this->get_parfumes_to_update();
        
        $total_updated = 0;
        $total_errors = 0;
        
        foreach ($parfumes as $post_id) {
            $result = $this->update_all_prices($post_id);
            $total_updated += $result['success'];
            $total_errors += $result['errors'];
            
            // Small delay between parfumes
            sleep(2);
        }
        
        // Log results
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Parfume Reviews Auto Update: Updated %d prices, %d errors',
                $total_updated,
                $total_errors
            ));
        }
    }
    
    /**
     * Get parfumes that need price update
     * 
     * @return array Post IDs
     */
    private function get_parfumes_to_update() {
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-' . $this->update_interval . ' hours'));
        
        $query = new \WP_Query([
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => 50, // Limit to avoid overload
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_parfume_last_price_update',
                    'value' => $cutoff_time,
                    'compare' => '<',
                    'type' => 'DATETIME'
                ],
                [
                    'key' => '_parfume_last_price_update',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        return $query->posts;
    }
    
    /**
     * Check if scraper is enabled
     * 
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled;
    }
    
    /**
     * Get supported stores
     * 
     * @return array
     */
    public function get_supported_stores() {
        return [
            'notino.bg' => __('Notino', 'parfume-reviews'),
            'parfimo.bg' => __('Parfimo', 'parfume-reviews'),
            'douglas.bg' => __('Douglas', 'parfume-reviews'),
            'makeup.bg' => __('Makeup', 'parfume-reviews'),
        ];
    }
}