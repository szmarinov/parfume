<?php
/**
 * Scraper Feature
 * 
 * Handles price scraping and data extraction from external sources
 * Schema-based scraping with support for multiple stores
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Scraper
 * @since 2.0.0
 */

namespace Parfume_Reviews\Features\Scraper;

use Parfume_Reviews\Core\Container;
use Parfume_Reviews\Features\Stores\StoreManager;
use Parfume_Reviews\Features\Stores\StoreSchema;

/**
 * Scraper Class
 * 
 * Manages price scraping and product data extraction using configurable schemas
 */
class Scraper {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Store manager
     * 
     * @var StoreManager
     */
    private $store_manager;
    
    /**
     * Store schema
     * 
     * @var StoreSchema
     */
    private $store_schema;
    
    /**
     * Settings
     * 
     * @var array
     */
    private $settings = [];
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     * @param StoreManager $store_manager Store manager
     * @param StoreSchema $store_schema Store schema
     */
    public function __construct(Container $container, StoreManager $store_manager, StoreSchema $store_schema) {
        $this->container = $container;
        $this->store_manager = $store_manager;
        $this->store_schema = $store_schema;
        
        // Get settings
        $this->settings = get_option('parfume_reviews_settings', []);
        
        // Register AJAX handlers
        add_action('wp_ajax_parfume_scrape_product', [$this, 'ajax_scrape']);
        add_action('wp_ajax_parfume_update_price', [$this, 'ajax_update_price']);
    }
    
    /**
     * Scrape URL using schema
     * 
     * @param string $url URL to scrape
     * @param int $store_id Store ID (optional - for schema lookup)
     * @return array Scraped data
     * @throws \Exception If scraping fails
     */
    public function scrape_url($url, $store_id = 0) {
        // Get user agent from settings
        $user_agent = isset($this->settings['scraper_user_agent']) 
            ? $this->settings['scraper_user_agent'] 
            : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        // Get timeout from settings
        $timeout = isset($this->settings['scrape_timeout']) 
            ? absint($this->settings['scrape_timeout']) 
            : 30;
        
        // Fetch page content
        $start_time = microtime(true);
        
        $response = wp_remote_get($url, [
            'timeout' => $timeout,
            'user-agent' => $user_agent,
            'sslverify' => false // For some hosts with SSL issues
        ]);
        
        $time_taken = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Log HTTP request if debug enabled
        if (isset($this->settings['debug_scraper']) && $this->settings['debug_scraper']) {
            error_log(sprintf(
                'Parfume Scraper: HTTP %d - %s (%.2fs)',
                $response_code,
                $url,
                $time_taken
            ));
        }
        
        if ($response_code !== 200) {
            throw new \Exception(sprintf(__('HTTP грешка: %d', 'parfume-reviews'), $response_code));
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            throw new \Exception(__('Празно съдържание', 'parfume-reviews'));
        }
        
        // Get schema for store
        $schema = [];
        if ($store_id) {
            $schema = $this->store_manager->get_store_schema($store_id);
        }
        
        // If no schema or empty schema, try generic scraping
        if (empty($schema) || empty($schema['price_selector'])) {
            return $this->scrape_generic($html, $url);
        }
        
        // Use schema-based scraping
        return $this->scrape_with_schema($html, $url, $schema);
    }
    
    /**
     * Scrape using schema
     * 
     * @param string $html HTML content
     * @param string $url Source URL
     * @param array $schema Scraping schema
     * @return array Scraped data
     */
    private function scrape_with_schema($html, $url, $schema) {
        $data = [
            'url' => $url,
            'name' => '',
            'price' => null,
            'old_price' => null,
            'currency' => 'BGN',
            'ml_variants' => [],
            'in_stock' => true,
            'delivery' => '',
            'image' => '',
            'scraped_at' => current_time('mysql')
        ];
        
        // Parse HTML with DOMDocument
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        // Extract price
        if (!empty($schema['price_selector'])) {
            $price_nodes = $this->query_selector($xpath, $schema['price_selector']);
            if ($price_nodes && $price_nodes->length > 0) {
                $price_text = trim($price_nodes->item(0)->textContent);
                $data['price'] = $this->extract_number($price_text);
            }
        }
        
        // Extract old price
        if (!empty($schema['old_price_selector'])) {
            $old_price_nodes = $this->query_selector($xpath, $schema['old_price_selector']);
            if ($old_price_nodes && $old_price_nodes->length > 0) {
                $old_price_text = trim($old_price_nodes->item(0)->textContent);
                $data['old_price'] = $this->extract_number($old_price_text);
            }
        }
        
        // Extract ML variants
        if (!empty($schema['ml_selector'])) {
            $ml_nodes = $this->query_selector($xpath, $schema['ml_selector']);
            if ($ml_nodes && $ml_nodes->length > 0) {
                foreach ($ml_nodes as $ml_node) {
                    $ml_text = trim($ml_node->textContent);
                    
                    // Try to extract ML and price from text
                    if (preg_match('/(\d+)\s*ml/i', $ml_text, $ml_match)) {
                        $variant = [
                            'ml' => absint($ml_match[1]),
                            'price' => $this->extract_number($ml_text),
                            'old_price' => null,
                            'in_stock' => true
                        ];
                        
                        $data['ml_variants'][] = $variant;
                    }
                }
            }
        }
        
        // Extract availability
        if (!empty($schema['availability_selector'])) {
            $availability_nodes = $this->query_selector($xpath, $schema['availability_selector']);
            if ($availability_nodes && $availability_nodes->length > 0) {
                $availability_text = strtolower(trim($availability_nodes->item(0)->textContent));
                
                // Check for "out of stock" indicators
                if (preg_match('/(изчерпан|out of stock|няма налич)/i', $availability_text)) {
                    $data['in_stock'] = false;
                } else {
                    $data['in_stock'] = true;
                }
            }
        }
        
        // Extract delivery info
        if (!empty($schema['delivery_selector'])) {
            $delivery_nodes = $this->query_selector($xpath, $schema['delivery_selector']);
            if ($delivery_nodes && $delivery_nodes->length > 0) {
                $data['delivery'] = trim($delivery_nodes->item(0)->textContent);
            }
        }
        
        // Extract product name
        if (!empty($schema['name_selector'])) {
            $name_nodes = $this->query_selector($xpath, $schema['name_selector']);
            if ($name_nodes && $name_nodes->length > 0) {
                $data['name'] = trim($name_nodes->item(0)->textContent);
            }
        }
        
        // Extract image
        if (!empty($schema['image_selector'])) {
            $image_nodes = $this->query_selector($xpath, $schema['image_selector']);
            if ($image_nodes && $image_nodes->length > 0) {
                $img_node = $image_nodes->item(0);
                if ($img_node->hasAttribute('src')) {
                    $data['image'] = $img_node->getAttribute('src');
                } elseif ($img_node->hasAttribute('data-src')) {
                    $data['image'] = $img_node->getAttribute('data-src');
                }
            }
        }
        
        // Extract currency from price text
        if ($data['price'] !== null) {
            $currency = $this->extract_currency($html);
            if ($currency) {
                $data['currency'] = $currency;
            }
        }
        
        return $data;
    }
    
    /**
     * Generic scraper fallback
     * 
     * @param string $html HTML content
     * @param string $url Source URL
     * @return array Scraped data
     */
    private function scrape_generic($html, $url) {
        $data = [
            'url' => $url,
            'name' => '',
            'price' => null,
            'old_price' => null,
            'currency' => 'BGN',
            'ml_variants' => [],
            'in_stock' => true,
            'delivery' => '',
            'image' => '',
            'scraped_at' => current_time('mysql')
        ];
        
        // Try to extract price using common patterns
        if (preg_match('/(\d+[.,]\d{2})\s*(лв|bgn|лева)/i', $html, $matches)) {
            $data['price'] = floatval(str_replace(',', '.', $matches[1]));
        }
        
        // Try to extract product name from title
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            $data['name'] = trim($matches[1]);
        }
        
        // Try to extract ML variants
        if (preg_match_all('/(\d+)\s*ml[^\d]*?(\d+[.,]\d{2})/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['ml_variants'][] = [
                    'ml' => absint($match[1]),
                    'price' => floatval(str_replace(',', '.', $match[2])),
                    'old_price' => null,
                    'in_stock' => true
                ];
            }
        }
        
        // Check stock status
        if (preg_match('/(out of stock|изчерпан|няма наличност)/i', $html)) {
            $data['in_stock'] = false;
        }
        
        // Try to extract delivery info
        if (preg_match('/(безплатна доставка|free shipping)/i', $html, $matches)) {
            $data['delivery'] = $matches[1];
        }
        
        return $data;
    }
    
    /**
     * Query DOM using CSS selector (converted to XPath)
     * 
     * @param \DOMXPath $xpath XPath object
     * @param string $selector CSS selector
     * @return \DOMNodeList|false
     */
    private function query_selector($xpath, $selector) {
        // Convert CSS to XPath
        $xpath_query = $this->css_to_xpath($selector);
        
        try {
            return $xpath->query($xpath_query);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Scraper: XPath query error - ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Convert CSS selector to XPath (basic conversion)
     * 
     * @param string $css_selector CSS selector
     * @return string XPath query
     */
    private function css_to_xpath($css_selector) {
        $xpath = $css_selector;
        
        // .class -> *[contains(@class, 'class')]
        $xpath = preg_replace('/\.([a-zA-Z0-9_-]+)/', "*[contains(concat(' ', normalize-space(@class), ' '), ' $1 ')]", $xpath);
        
        // #id -> *[@id='id']
        $xpath = preg_replace('/#([a-zA-Z0-9_-]+)/', "*[@id='$1']", $xpath);
        
        // element > child -> element/child
        $xpath = str_replace(' > ', '/', $xpath);
        
        // element child -> element//child
        $xpath = preg_replace('/\s+/', '//', $xpath);
        
        // Add // prefix if not present
        if (strpos($xpath, '/') !== 0) {
            $xpath = '//' . $xpath;
        }
        
        return $xpath;
    }
    
    /**
     * Extract number from text
     * 
     * @param string $text Text containing number
     * @return float|null
     */
    private function extract_number($text) {
        // Remove all non-numeric characters except dot and comma
        $cleaned = preg_replace('/[^\d.,]/', '', $text);
        
        if (empty($cleaned)) {
            return null;
        }
        
        // Replace comma with dot
        $cleaned = str_replace(',', '.', $cleaned);
        
        // If multiple dots, keep only the last one as decimal separator
        if (substr_count($cleaned, '.') > 1) {
            $parts = explode('.', $cleaned);
            $last = array_pop($parts);
            $cleaned = implode('', $parts) . '.' . $last;
        }
        
        return floatval($cleaned);
    }
    
    /**
     * Extract currency from HTML
     * 
     * @param string $html HTML content
     * @return string|null
     */
    private function extract_currency($html) {
        $currencies = [
            'BGN' => ['лв', 'bgn', 'лева'],
            'EUR' => ['€', 'eur', 'euro'],
            'USD' => ['$', 'usd', 'dollar']
        ];
        
        foreach ($currencies as $code => $indicators) {
            foreach ($indicators as $indicator) {
                if (stripos($html, $indicator) !== false) {
                    return $code;
                }
            }
        }
        
        return null;
    }
    
    /**
     * AJAX: Scrape product data
     */
    public function ajax_scrape() {
        check_ajax_referer('parfume_scraper_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $store_id = isset($_POST['store_id']) ? absint($_POST['store_id']) : 0;
        
        if (empty($url)) {
            wp_send_json_error(__('Невалиден URL', 'parfume-reviews'));
        }
        
        try {
            $data = $this->scrape_url($url, $store_id);
            wp_send_json_success($data);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Update price for a store
     */
    public function ajax_update_price() {
        check_ajax_referer('parfume_scraper_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
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
     * Update price for specific store in post
     * 
     * @param int $post_id Post ID
     * @param int $store_index Store index
     * @return array Updated data
     * @throws \Exception If update fails
     */
    public function update_store_price($post_id, $store_index) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($stores) || !isset($stores[$store_index])) {
            throw new \Exception(__('Магазинът не е намерен', 'parfume-reviews'));
        }
        
        $store = $stores[$store_index];
        
        if (empty($store['product_url'])) {
            throw new \Exception(__('Липсва Product URL', 'parfume-reviews'));
        }
        
        $store_id = isset($store['store_id']) ? $store['store_id'] : 0;
        
        // Scrape data
        $scraped_data = $this->scrape_url($store['product_url'], $store_id);
        
        // Update store with scraped data
        $stores[$store_index]['scraped_data'] = $scraped_data;
        $stores[$store_index]['last_scraped'] = current_time('mysql');
        
        // Calculate next scrape time
        $interval = isset($this->settings['scrape_interval']) ? absint($this->settings['scrape_interval']) : 12;
        $stores[$store_index]['next_scrape'] = date('Y-m-d H:i:s', strtotime("+{$interval} hours"));
        $stores[$store_index]['scrape_status'] = 'success';
        
        // Save
        update_post_meta($post_id, '_parfume_stores', $stores);
        
        return [
            'scraped_data' => $scraped_data,
            'last_scraped' => $stores[$store_index]['last_scraped'],
            'next_scrape' => $stores[$store_index]['next_scrape']
        ];
    }
}