<?php
/**
 * Product Scraper Class for Parfume Reviews Plugin
 * 
 * Handles automatic scraping of product data from store URLs
 * 
 * @package Parfume_Reviews
 * @since 1.0.0
 */

namespace Parfume_Reviews;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Product_Scraper {
    
    private $user_agents = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    );
    
    private $store_schemas = array();
    
    public function __construct() {
        // WP Cron за автоматично скрейпване
        add_action('wp', array($this, 'schedule_scraping'));
        add_action('parfume_scraper_cron', array($this, 'run_batch_scraping'));
        
        // AJAX handlers за ръчно скрейпване
        add_action('wp_ajax_manual_scrape_product', array($this, 'manual_scrape_product'));
        add_action('wp_ajax_test_scraper_url', array($this, 'test_scraper_url'));
        add_action('wp_ajax_save_store_schema', array($this, 'save_store_schema'));
        add_action('wp_ajax_scrape_store_data', array($this, 'ajax_scrape_store_data'));
        add_action('wp_ajax_manual_scrape_all_products', array($this, 'ajax_manual_scrape_all'));
        
        // Admin страница за мониториране
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Meta boxes за постове
        add_action('add_meta_boxes', array($this, 'add_scraper_meta_boxes'));
        add_action('save_post', array($this, 'save_scraper_meta_boxes'));
        
        // Settings integration
        add_action('admin_init', array($this, 'register_scraper_settings'));
        
        // Initialize store schemas
        $this->init_store_schemas();
    }
    
    /**
     * Initialize predefined store schemas
     */
    private function init_store_schemas() {
        $this->store_schemas = array(
            'douglas' => array(
                'name' => 'Douglas',
                'price_selector' => '.price-value',
                'old_price_selector' => '.price-old',
                'availability_selector' => '.availability-text',
                'variants_selector' => '.variant-item',
                'variant_ml_selector' => '.variant-size',
                'variant_price_selector' => '.variant-price'
            ),
            'notino' => array(
                'name' => 'Notino',
                'price_selector' => '.price-current',
                'old_price_selector' => '.price-original',
                'availability_selector' => '.stock-info',
                'variants_selector' => '.product-variants .variant',
                'variant_ml_selector' => '.variant-title',
                'variant_price_selector' => '.variant-price'
            ),
            'generic' => array(
                'name' => 'Generic Store',
                'price_selector' => '[class*="price"]:not([class*="old"])',
                'old_price_selector' => '[class*="old"], [class*="original"]',
                'availability_selector' => '[class*="stock"], [class*="availability"]',
                'variants_selector' => '.variant, .option, .size-option',
                'variant_ml_selector' => '.size, .ml, .volume',
                'variant_price_selector' => '.price'
            )
        );
    }
    
    /**
     * Schedule scraping cron job
     */
    public function schedule_scraping() {
        if (!wp_next_scheduled('parfume_scraper_cron')) {
            $settings = get_option('parfume_reviews_settings', array());
            $interval = isset($settings['scrape_interval']) ? intval($settings['scrape_interval']) : 24;
            
            wp_schedule_event(time(), 'hourly', 'parfume_scraper_cron');
        }
    }
    
    /**
     * Run batch scraping from cron
     */
    public function run_batch_scraping() {
        $settings = get_option('parfume_reviews_settings', array());
        $batch_size = isset($settings['batch_size']) ? intval($settings['batch_size']) : 10;
        
        $scrape_queue = get_option('parfume_scraper_queue', array());
        $processed = 0;
        
        foreach ($scrape_queue as $index => $item) {
            if ($processed >= $batch_size) break;
            
            if ($item['status'] === 'pending') {
                $result = $this->scrape_product_url($item['url'], $item['store_id']);
                
                if ($result) {
                    $this->save_scraped_data($item['post_id'], $item['store_index'], $result);
                    $scrape_queue[$index]['status'] = 'completed';
                    $scrape_queue[$index]['completed_at'] = current_time('mysql');
                } else {
                    $scrape_queue[$index]['status'] = 'error';
                    $scrape_queue[$index]['error_at'] = current_time('mysql');
                }
                
                $processed++;
            }
        }
        
        // Clean up old completed items
        $scrape_queue = array_filter($scrape_queue, function($item) {
            return $item['status'] !== 'completed' || 
                   strtotime($item['completed_at']) > strtotime('-7 days');
        });
        
        update_option('parfume_scraper_queue', array_values($scrape_queue));
    }
    
    /**
     * Scrape product data from URL
     */
    public function scrape_product_url($url, $store_id = 'generic') {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $schema = isset($this->store_schemas[$store_id]) ? 
                 $this->store_schemas[$store_id] : 
                 $this->store_schemas['generic'];
        
        try {
            $settings = get_option('parfume_reviews_settings', array());
            $user_agent = isset($settings['user_agent']) ? 
                         $settings['user_agent'] : 
                         $this->user_agents[array_rand($this->user_agents)];
            
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => $user_agent,
                'headers' => array(
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'bg-BG,bg;q=0.9,en;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Cache-Control' => 'no-cache',
                )
            ));
            
            if (is_wp_error($response)) {
                error_log("Scraping error for $url: " . $response->get_error_message());
                return false;
            }
            
            $html = wp_remote_retrieve_body($response);
            if (empty($html)) return false;
            
            // Parse HTML
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();
            
            $xpath = new \DOMXPath($dom);
            
            $scraped_data = array(
                'url' => $url,
                'scraped_at' => current_time('mysql'),
                'status' => 'success',
                'variants' => array()
            );
            
            // Extract price
            if (!empty($schema['price_selector'])) {
                $price = $this->extract_text_by_selector($xpath, $schema['price_selector']);
                if ($price) {
                    $scraped_data['price'] = $this->clean_price($price);
                }
            }
            
            // Extract old price
            if (!empty($schema['old_price_selector'])) {
                $old_price = $this->extract_text_by_selector($xpath, $schema['old_price_selector']);
                if ($old_price) {
                    $scraped_data['old_price'] = $this->clean_price($old_price);
                }
            }
            
            // Extract availability
            if (!empty($schema['availability_selector'])) {
                $availability = $this->extract_text_by_selector($xpath, $schema['availability_selector']);
                if ($availability) {
                    $scraped_data['availability'] = trim($availability);
                    $scraped_data['in_stock'] = $this->determine_stock_status($availability);
                }
            }
            
            // Extract delivery info
            if (!empty($schema['delivery_selector'])) {
                $delivery = $this->extract_text_by_selector($xpath, $schema['delivery_selector']);
                if ($delivery) {
                    $scraped_data['delivery'] = trim($delivery);
                }
            }
            
            // Extract variants
            $variants = $this->extract_variants($xpath, $schema);
            if (!empty($variants)) {
                $scraped_data['variants'] = $variants;
            }
            
            // Calculate discount if both prices exist
            if (!empty($scraped_data['price']) && !empty($scraped_data['old_price'])) {
                $current = floatval(preg_replace('/[^\d.]/', '', $scraped_data['price']));
                $original = floatval(preg_replace('/[^\d.]/', '', $scraped_data['old_price']));
                
                if ($current > 0 && $original > 0 && $original > $current) {
                    $discount_percent = round((($original - $current) / $original) * 100);
                    $scraped_data['discount_percent'] = $discount_percent;
                }
            }
            
            return $scraped_data;
            
        } catch (Exception $e) {
            error_log("Scraping error for $url: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract text by CSS selector or XPath
     */
    private function extract_text_by_selector($xpath, $selector) {
        try {
            // Try as XPath first
            if (strpos($selector, '//') === 0 || strpos($selector, '/') === 0) {
                $nodes = $xpath->query($selector);
            } else {
                // Convert CSS selector to XPath (basic conversion)
                $xpath_selector = $this->css_to_xpath($selector);
                $nodes = $xpath->query($xpath_selector);
            }
            
            if ($nodes && $nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }
        } catch (Exception $e) {
            error_log("Selector extraction error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Extract product variants
     */
    private function extract_variants($xpath, $schema) {
        $variants = array();
        
        if (empty($schema['variants_selector'])) {
            return $variants;
        }
        
        try {
            $xpath_selector = $this->css_to_xpath($schema['variants_selector']);
            $variant_nodes = $xpath->query($xpath_selector);
            
            foreach ($variant_nodes as $node) {
                $variant = array();
                
                // Extract ML/size
                if (!empty($schema['variant_ml_selector'])) {
                    $ml_xpath = $this->css_to_xpath($schema['variant_ml_selector']);
                    $ml_nodes = $xpath->query($ml_xpath, $node);
                    if ($ml_nodes && $ml_nodes->length > 0) {
                        $ml_text = trim($ml_nodes->item(0)->textContent);
                        $variant['ml'] = $this->extract_ml_from_text($ml_text);
                    }
                }
                
                // Extract price
                if (!empty($schema['variant_price_selector'])) {
                    $price_xpath = $this->css_to_xpath($schema['variant_price_selector']);
                    $price_nodes = $xpath->query($price_xpath, $node);
                    if ($price_nodes && $price_nodes->length > 0) {
                        $price_text = trim($price_nodes->item(0)->textContent);
                        $variant['price'] = $this->clean_price($price_text);
                    }
                }
                
                // Only add if we have both ML and price
                if (!empty($variant['ml']) && !empty($variant['price'])) {
                    $variants[] = $variant;
                }
            }
        } catch (Exception $e) {
            error_log("Variants extraction error: " . $e->getMessage());
        }
        
        return $variants;
    }
    
    /**
     * Convert CSS selector to XPath (basic)
     */
    private function css_to_xpath($css_selector) {
        $css_selector = trim($css_selector);
        
        // Basic conversions
        $xpath = '//' . str_replace(array(
            ' > ',
            ' ',
            '#',
            '.',
            '[class*="',
            '"]',
            ':not(',
            ')'
        ), array(
            '/',
            '//',
            "[@id='",
            "[@class='",
            "[contains(@class,'",
            "')]",
            "[not(contains(@class,'",
            "'))]"
        ), $css_selector);
        
        // Handle attribute selectors better
        if (strpos($css_selector, '[class*=') !== false) {
            $xpath = preg_replace('/\[@class=\'([^\']+)\'\]/', "[contains(@class,'$1')]", $xpath);
        }
        
        return $xpath;
    }
    
    /**
     * Clean and format price
     */
    private function clean_price($price_text) {
        $price = preg_replace('/[^\d.,]/', '', $price_text);
        $price = str_replace(',', '.', $price);
        
        // Remove extra dots
        $parts = explode('.', $price);
        if (count($parts) > 2) {
            $price = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
        }
        
        return $price;
    }
    
    /**
     * Extract ML from text
     */
    private function extract_ml_from_text($text) {
        if (preg_match('/(\d+)\s*ml/i', $text, $matches)) {
            return $matches[1] . 'ml';
        }
        
        return $text;
    }
    
    /**
     * Determine stock status from availability text
     */
    private function determine_stock_status($availability) {
        $availability = strtolower($availability);
        
        $in_stock_indicators = array('наличен', 'в наличност', 'available', 'in stock', 'на склад');
        $out_of_stock_indicators = array('няма наличност', 'изчерпан', 'out of stock', 'unavailable');
        
        foreach ($in_stock_indicators as $indicator) {
            if (strpos($availability, $indicator) !== false) {
                return true;
            }
        }
        
        foreach ($out_of_stock_indicators as $indicator) {
            if (strpos($availability, $indicator) !== false) {
                return false;
            }
        }
        
        return null; // Unknown status
    }
    
    /**
     * Save scraped data to post meta
     */
    private function save_scraped_data($post_id, $store_index, $data) {
        // Get current stores data
        $stores = get_post_meta($post_id, '_parfume_stores_v2', true);
        if (empty($stores) || !is_array($stores)) {
            return false;
        }
        
        if (!isset($stores[$store_index])) {
            return false;
        }
        
        // Add scraped data to store
        $stores[$store_index]['scraped_data'] = $data;
        $stores[$store_index]['last_scraped'] = current_time('mysql');
        
        // Calculate next scrape time
        $settings = get_option('parfume_reviews_settings', array());
        $interval = isset($settings['scrape_interval']) ? intval($settings['scrape_interval']) : 24;
        $stores[$store_index]['next_scrape'] = date('Y-m-d H:i:s', strtotime('+' . $interval . ' hours'));
        
        update_post_meta($post_id, '_parfume_stores_v2', $stores);
        
        return true;
    }
    
    /**
     * AJAX handler for manual product scraping
     */
    public function manual_scrape_product() {
        check_ajax_referer('parfume-scraper-nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        $stores = get_post_meta($post_id, '_parfume_stores_v2', true);
        
        if (empty($stores[$store_index])) {
            wp_send_json_error(__('Store not found', 'parfume-reviews'));
        }
        
        $store = $stores[$store_index];
        $result = $this->scrape_product_url($store['product_url'], $store['store_id']);
        
        if ($result) {
            $this->save_scraped_data($post_id, $store_index, $result);
            wp_send_json_success(array(
                'message' => __('Successfully scraped product data', 'parfume-reviews'),
                'data' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to scrape product data', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX handler for store data scraping
     */
    public function ajax_scrape_store_data() {
        check_ajax_referer('parfume-reviews-admin-nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $url = esc_url_raw($_POST['url']);
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        if (empty($url)) {
            wp_send_json_error(__('URL is required', 'parfume-reviews'));
        }
        
        // Determine store ID from URL
        $store_id = $this->detect_store_from_url($url);
        
        $result = $this->scrape_product_url($url, $store_id);
        
        if ($result) {
            $this->save_scraped_data($post_id, $store_index, $result);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Failed to scrape product data', 'parfume-reviews'));
        }
    }
    
    /**
     * AJAX handler for manual scrape all
     */
    public function ajax_manual_scrape_all() {
        check_ajax_referer('parfume-reviews-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        // Get all parfumes with store URLs
        $posts = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_parfume_stores_v2',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $count = 0;
        foreach ($posts as $post) {
            $stores = get_post_meta($post->ID, '_parfume_stores_v2', true);
            if (is_array($stores)) {
                foreach ($stores as $index => $store) {
                    if (!empty($store['product_url'])) {
                        $this->add_to_scrape_queue($post->ID, $index, $store);
                        $count++;
                    }
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Added %d products to scraping queue', 'parfume-reviews'), $count),
            'count' => $count
        ));
    }
    
    /**
     * Add item to scrape queue
     */
    private function add_to_scrape_queue($post_id, $store_index, $store) {
        $scrape_queue = get_option('parfume_scraper_queue', array());
        
        $queue_item = array(
            'post_id' => $post_id,
            'store_index' => $store_index,
            'url' => $store['product_url'],
            'store_id' => $store['store_id'],
            'added' => current_time('mysql'),
            'status' => 'pending'
        );
        
        $scrape_queue[] = $queue_item;
        update_option('parfume_scraper_queue', $scrape_queue);
    }
    
    /**
     * Detect store from URL
     */
    private function detect_store_from_url($url) {
        $domain = parse_url($url, PHP_URL_HOST);
        $domain = strtolower($domain);
        
        if (strpos($domain, 'douglas') !== false) {
            return 'douglas';
        } elseif (strpos($domain, 'notino') !== false) {
            return 'notino';
        }
        
        return 'generic';
    }
    
    /**
     * Test scraper URL - AJAX handler
     */
    public function test_scraper_url() {
        check_ajax_referer('parfume-scraper-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $url = esc_url_raw($_POST['url']);
        
        if (empty($url)) {
            wp_send_json_error(__('URL is required', 'parfume-reviews'));
        }
        
        $result = $this->analyze_page_structure($url);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Failed to analyze page', 'parfume-reviews'));
        }
    }
    
    /**
     * Analyze page structure for testing
     */
    private function analyze_page_structure($url) {
        try {
            $settings = get_option('parfume_reviews_settings', array());
            $user_agent = isset($settings['user_agent']) ? 
                         $settings['user_agent'] : 
                         $this->user_agents[0];
            
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => $user_agent,
            ));
            
            if (is_wp_error($response)) {
                return false;
            }
            
            $html = wp_remote_retrieve_body($response);
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();
            
            $xpath = new \DOMXPath($dom);
            
            $analysis = array(
                'url' => $url,
                'title' => '',
                'potential_prices' => array(),
                'potential_availability' => array(),
                'potential_variants' => array(),
            );
            
            // Get page title
            $title_nodes = $xpath->query('//title');
            if ($title_nodes->length > 0) {
                $analysis['title'] = trim($title_nodes->item(0)->textContent);
            }
            
            // Find potential prices
            $price_patterns = array(
                '//span[contains(@class, "price")]',
                '//div[contains(@class, "price")]',
                '//*[contains(text(), "лв")]',
                '//*[contains(text(), "BGN")]',
                '//*[contains(text(), "€")]',
                '//*[contains(@class, "cost")]',
                '//*[contains(@class, "amount")]'
            );
            
            foreach ($price_patterns as $pattern) {
                $nodes = $xpath->query($pattern);
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (preg_match('/[\d,.]+ ?(лв|BGN|€|USD|\$)/', $text)) {
                        $analysis['potential_prices'][] = array(
                            'text' => $text,
                            'selector' => $this->get_css_selector($node),
                            'xpath' => $node->getNodePath()
                        );
                    }
                }
            }
            
            // Find potential availability
            $availability_patterns = array(
                '//*[contains(text(), "наличен")]',
                '//*[contains(text(), "в наличност")]',
                '//*[contains(text(), "available")]',
                '//span[contains(@class, "stock")]',
                '//div[contains(@class, "availability")]',
                '//*[contains(@class, "status")]'
            );
            
            foreach ($availability_patterns as $pattern) {
                $nodes = $xpath->query($pattern);
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (!empty($text) && strlen($text) < 100) {
                        $analysis['potential_availability'][] = array(
                            'text' => $text,
                            'selector' => $this->get_css_selector($node),
                            'xpath' => $node->getNodePath()
                        );
                    }
                }
            }
            
            // Remove duplicates
            $analysis['potential_prices'] = array_slice(
                array_unique($analysis['potential_prices'], SORT_REGULAR), 
                0, 5
            );
            $analysis['potential_availability'] = array_slice(
                array_unique($analysis['potential_availability'], SORT_REGULAR), 
                0, 5
            );
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log("Page analysis error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get CSS selector from DOM node
     */
    private function get_css_selector($node) {
        $path = array();
        
        while ($node && $node->nodeType === XML_ELEMENT_NODE) {
            $selector = $node->nodeName;
            
            if ($node->hasAttribute('id')) {
                $selector .= '#' . $node->getAttribute('id');
                $path[] = $selector;
                break;
            }
            
            if ($node->hasAttribute('class')) {
                $classes = explode(' ', $node->getAttribute('class'));
                if (!empty($classes[0])) {
                    $selector .= '.' . $classes[0];
                }
            }
            
            $path[] = $selector;
            $node = $node->parentNode;
        }
        
        return implode(' > ', array_reverse($path));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Product Scraper', 'parfume-reviews'),
            __('Product Scraper', 'parfume-reviews'),
            'manage_options',
            'parfume-product-scraper',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Scraper Test Tool', 'parfume-reviews'),
            __('Scraper Test Tool', 'parfume-reviews'),
            'manage_options',
            'parfume-scraper-test',
            array($this, 'render_test_tool_page')
        );
    }
    
    /**
     * Render admin monitoring page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Product Scraper Monitor', 'parfume-reviews'); ?></h1>
            
            <?php $this->render_scraper_stats(); ?>
            <?php $this->render_scraper_controls(); ?>
            <?php $this->render_scraper_queue(); ?>
            <?php $this->render_scraped_products_table(); ?>
        </div>
        <?php
    }
    
    /**
     * Render test tool page
     */
    public function render_test_tool_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Scraper Test Tool', 'parfume-reviews'); ?></h1>
            
            <div class="scraper-test-tool">
                <h2><?php _e('Test URL Scraping', 'parfume-reviews'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="test-url"><?php _e('Test URL', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="test-url" class="large-text" placeholder="https://example.com/product">
                            <button type="button" id="test-scraper-btn" class="button button-primary">
                                <?php _e('Test Scraping', 'parfume-reviews'); ?>
                            </button>
                        </td>
                    </tr>
                </table>
                
                <div id="test-results" style="margin-top: 20px;"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render scraper statistics
     */
    private function render_scraper_stats() {
        $scrape_queue = get_option('parfume_scraper_queue', array());
        $pending_count = count(array_filter($scrape_queue, function($item) { 
            return $item['status'] === 'pending'; 
        }));
        $error_count = count(array_filter($scrape_queue, function($item) { 
            return $item['status'] === 'error'; 
        }));
        
        // Count total products with stores
        $total_products = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_parfume_stores_v2',
                    'compare' => 'EXISTS'
                )
            ),
            'fields' => 'ids'
        ));
        
        ?>
        <div class="scraper-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
            <div class="stat-box" style="background: #f0f0f1; padding: 20px; border-radius: 5px; text-align: center;">
                <h3><?php _e('Total Products', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #0073aa;"><?php echo count($total_products); ?></span>
            </div>
            
            <div class="stat-box" style="background: #fff3cd; padding: 20px; border-radius: 5px; text-align: center;">
                <h3><?php _e('Pending Scrape', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #856404;"><?php echo $pending_count; ?></span>
            </div>
            
            <div class="stat-box" style="background: #f8d7da; padding: 20px; border-radius: 5px; text-align: center;">
                <h3><?php _e('Errors', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #721c24;"><?php echo $error_count; ?></span>
            </div>
            
            <div class="stat-box" style="background: #d4edda; padding: 20px; border-radius: 5px; text-align: center;">
                <h3><?php _e('Queue Size', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #155724;"><?php echo count($scrape_queue); ?></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render scraper controls
     */
    private function render_scraper_controls() {
        ?>
        <div class="scraper-controls" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
            <h3><?php _e('Scraper Controls', 'parfume-reviews'); ?></h3>
            
            <p>
                <button type="button" class="button button-primary manual-scrape-all">
                    <?php _e('Scrape All Products Now', 'parfume-reviews'); ?>
                </button>
                
                <button type="button" class="button clear-queue" onclick="if(confirm('Clear scraping queue?')) location.href='<?php echo wp_nonce_url(admin_url('edit.php?post_type=parfume&page=parfume-product-scraper&action=clear_queue'), 'clear_queue'); ?>'">
                    <?php _e('Clear Queue', 'parfume-reviews'); ?>
                </button>
            </p>
            
            <p class="description">
                <?php _e('Manual scraping will add all products with store URLs to the scraping queue.', 'parfume-reviews'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render scraper queue
     */
    private function render_scraper_queue() {
        $scrape_queue = get_option('parfume_scraper_queue', array());
        $recent_queue = array_slice($scrape_queue, -20); // Show last 20 items
        
        ?>
        <div class="scraper-queue" style="margin: 20px 0;">
            <h3><?php _e('Recent Scraping Queue', 'parfume-reviews'); ?></h3>
            
            <?php if (empty($recent_queue)): ?>
                <p><?php _e('No items in scraping queue.', 'parfume-reviews'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'parfume-reviews'); ?></th>
                            <th><?php _e('Store', 'parfume-reviews'); ?></th>
                            <th><?php _e('Status', 'parfume-reviews'); ?></th>
                            <th><?php _e('Added', 'parfume-reviews'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($recent_queue) as $item): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($item['post_id']); ?>">
                                        <?php echo get_the_title($item['post_id']); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($item['store_id']); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($item['status']); ?>">
                                        <?php echo esc_html(ucfirst($item['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($item['added']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render scraped products table
     */
    private function render_scraped_products_table() {
        // Get products with scraped data
        $products = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => 20,
            'meta_query' => array(
                array(
                    'key' => '_parfume_stores_v2',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        ?>
        <div class="scraped-products" style="margin: 20px 0;">
            <h3><?php _e('Recently Scraped Products', 'parfume-reviews'); ?></h3>
            
            <?php if (empty($products)): ?>
                <p><?php _e('No products with store data found.', 'parfume-reviews'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'parfume-reviews'); ?></th>
                            <th><?php _e('Stores', 'parfume-reviews'); ?></th>
                            <th><?php _e('Last Scraped', 'parfume-reviews'); ?></th>
                            <th><?php _e('Status', 'parfume-reviews'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $stores = get_post_meta($product->ID, '_parfume_stores_v2', true);
                            $stores = is_array($stores) ? $stores : array();
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($product->ID); ?>">
                                        <strong><?php echo esc_html($product->post_title); ?></strong>
                                    </a>
                                </td>
                                <td><?php echo count($stores); ?> stores</td>
                                <td>
                                    <?php
                                    $last_scraped = '';
                                    foreach ($stores as $store) {
                                        if (!empty($store['last_scraped'])) {
                                            if (empty($last_scraped) || $store['last_scraped'] > $last_scraped) {
                                                $last_scraped = $store['last_scraped'];
                                            }
                                        }
                                    }
                                    echo $last_scraped ? esc_html($last_scraped) : __('Never', 'parfume-reviews');
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $has_data = false;
                                    foreach ($stores as $store) {
                                        if (!empty($store['scraped_data'])) {
                                            $has_data = true;
                                            break;
                                        }
                                    }
                                    echo $has_data ? '<span class="status-success">Has Data</span>' : '<span class="status-pending">No Data</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Register scraper settings
     */
    public function register_scraper_settings() {
        add_settings_section(
            'parfume_reviews_scraper_section',
            __('Product Scraper Settings', 'parfume-reviews'),
            array($this, 'render_scraper_section'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'scrape_interval',
            __('Scrape Interval (hours)', 'parfume-reviews'),
            array($this, 'render_scrape_interval_field'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'batch_size',
            __('Batch Size', 'parfume-reviews'),
            array($this, 'render_batch_size_field'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'user_agent',
            __('User Agent', 'parfume-reviews'),
            array($this, 'render_user_agent_field'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
    }
    
    public function render_scraper_section() {
        echo '<p>' . __('Configure the product scraper settings for automatic price and availability updates.', 'parfume-reviews') . '</p>';
    }
    
    public function render_scrape_interval_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['scrape_interval']) ? $settings['scrape_interval'] : 24;
        ?>
        <input type="number" name="parfume_reviews_settings[scrape_interval]" value="<?php echo esc_attr($value); ?>" min="1" step="1">
        <p class="description"><?php _e('How often to scrape product data (in hours).', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_batch_size_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['batch_size']) ? $settings['batch_size'] : 10;
        ?>
        <input type="number" name="parfume_reviews_settings[batch_size]" value="<?php echo esc_attr($value); ?>" min="1" max="50" step="1">
        <p class="description"><?php _e('Number of products to scrape in each batch (to avoid server overload).', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_user_agent_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['user_agent']) ? $settings['user_agent'] : $this->user_agents[0];
        ?>
        <input type="text" name="parfume_reviews_settings[user_agent]" value="<?php echo esc_attr($value); ?>" class="large-text">
        <p class="description"><?php _e('User agent string to use for scraping requests.', 'parfume-reviews'); ?></p>
        <?php
    }
    
    /**
     * Add scraper meta boxes
     */
    public function add_scraper_meta_boxes() {
        add_meta_box(
            'product_scraper_stores',
            __('Stores & Product Scraper', 'parfume-reviews'),
            array($this, 'render_stores_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    /**
     * Render stores meta box
     */
    public function render_stores_meta_box($post) {
        wp_nonce_field('product_scraper_nonce', 'product_scraper_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores_v2', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        $available_stores = array(
            'douglas' => array('name' => 'Douglas'),
            'notino' => array('name' => 'Notino'),
            'generic' => array('name' => 'Other Store')
        );
        
        ?>
        <div class="stores-meta-box">
            <div class="stores-container">
                <?php if (!empty($stores)): ?>
                    <?php foreach ($stores as $index => $store): ?>
                        <?php $this->render_single_store_admin($index, $store, $post->ID, $available_stores); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <p>
                <button type="button" class="button add-store-btn">
                    <?php _e('Add Store', 'parfume-reviews'); ?>
                </button>
            </p>
            
            <?php if (empty($stores)): ?>
                <div class="no-stores-message">
                    <p><em><?php _e('No stores configured. Add a store above to start price monitoring.', 'parfume-reviews'); ?></em></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Store template for JavaScript -->
        <script type="text/html" id="store-item-template">
            <?php $this->render_single_store_admin('{{INDEX}}', array(), $post->ID, $available_stores); ?>
        </script>
        <?php
    }
    
    /**
     * Render single store admin interface
     */
    private function render_single_store_admin($index, $store, $post_id, $available_stores) {
        $store = wp_parse_args($store, array(
            'store_id' => '',
            'product_url' => '',
            'affiliate_url' => '',
            'promo_code' => '',
            'promo_code_info' => '',
            'scraped_data' => array(),
            'last_scraped' => ''
        ));
        
        $store_info = isset($available_stores[$store['store_id']]) ? $available_stores[$store['store_id']] : array();
        
        ?>
        <div class="store-item-admin" data-index="<?php echo esc_attr($index); ?>">
            <div class="store-header">
                <span class="store-drag-handle">≡</span>
                <strong><?php _e('Store', 'parfume-reviews'); ?> <span class="store-number"><?php echo ($index + 1); ?></span></strong>
                <a href="#" class="store-remove" title="<?php esc_attr_e('Remove store', 'parfume-reviews'); ?>">×</a>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="stores_<?php echo esc_attr($index); ?>_store_id"><?php _e('Store', 'parfume-reviews'); ?></label></th>
                    <td>
                        <select name="stores[<?php echo esc_attr($index); ?>][store_id]" id="stores_<?php echo esc_attr($index); ?>_store_id" class="store-select">
                            <option value=""><?php _e('Select Store', 'parfume-reviews'); ?></option>
                            <?php foreach ($available_stores as $store_id => $store_data): ?>
                                <option value="<?php echo esc_attr($store_id); ?>" <?php selected($store['store_id'], $store_id); ?>>
                                    <?php echo esc_html($store_data['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="stores_<?php echo esc_attr($index); ?>_product_url"><?php _e('Product URL', 'parfume-reviews'); ?></label></th>
                    <td>
                        <input type="url" name="stores[<?php echo esc_attr($index); ?>][product_url]" 
                               id="stores_<?php echo esc_attr($index); ?>_product_url" 
                               value="<?php echo esc_url($store['product_url']); ?>" 
                               class="large-text store-product-url" 
                               placeholder="https://example.com/product">
                        <div class="scrape-indicator"></div>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="stores_<?php echo esc_attr($index); ?>_affiliate_url"><?php _e('Affiliate URL', 'parfume-reviews'); ?></label></th>
                    <td>
                        <input type="url" name="stores[<?php echo esc_attr($index); ?>][affiliate_url]" 
                               id="stores_<?php echo esc_attr($index); ?>_affiliate_url" 
                               value="<?php echo esc_url($store['affiliate_url']); ?>" 
                               class="large-text" 
                               placeholder="https://affiliate-link.com">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="stores_<?php echo esc_attr($index); ?>_promo_code"><?php _e('Promo Code', 'parfume-reviews'); ?></label></th>
                    <td>
                        <input type="text" name="stores[<?php echo esc_attr($index); ?>][promo_code]" 
                               id="stores_<?php echo esc_attr($index); ?>_promo_code" 
                               value="<?php echo esc_attr($store['promo_code']); ?>" 
                               class="regular-text" 
                               placeholder="DISCOUNT10">
                        <br>
                        <input type="text" name="stores[<?php echo esc_attr($index); ?>][promo_code_info]" 
                               id="stores_<?php echo esc_attr($index); ?>_promo_code_info" 
                               value="<?php echo esc_attr($store['promo_code_info']); ?>" 
                               class="large-text" 
                               placeholder="<?php esc_attr_e('Promo code description', 'parfume-reviews'); ?>">
                    </td>
                </tr>
            </table>
            
            <?php if (!empty($store['scraped_data'])): ?>
                <div class="scraped-results">
                    <h4><?php _e('Last Scraped Data', 'parfume-reviews'); ?></h4>
                    <div class="scraped-data">
                        <?php if (!empty($store['scraped_data']['price'])): ?>
                            <div class="scraped-item">
                                <strong><?php _e('Price:', 'parfume-reviews'); ?></strong> 
                                <?php echo esc_html($store['scraped_data']['price']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($store['scraped_data']['old_price'])): ?>
                            <div class="scraped-item">
                                <strong><?php _e('Old Price:', 'parfume-reviews'); ?></strong> 
                                <?php echo esc_html($store['scraped_data']['old_price']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($store['scraped_data']['availability'])): ?>
                            <div class="scraped-item">
                                <strong><?php _e('Availability:', 'parfume-reviews'); ?></strong> 
                                <?php echo esc_html($store['scraped_data']['availability']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($store['scraped_data']['variants'])): ?>
                            <div class="scraped-item">
                                <strong><?php _e('Variants:', 'parfume-reviews'); ?></strong>
                                <ul>
                                    <?php foreach ($store['scraped_data']['variants'] as $variant): ?>
                                        <li><?php echo esc_html($variant['ml']) . ' - ' . esc_html($variant['price']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="scraped-meta">
                            <?php _e('Scraped:', 'parfume-reviews'); ?> <?php echo esc_html($store['last_scraped']); ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="scraped-results"></div>
            <?php endif; ?>
            
            <p>
                <button type="button" class="button manual-scrape-btn">
                    <?php _e('Scrape Now', 'parfume-reviews'); ?>
                </button>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save scraper meta boxes
     */
    public function save_scraper_meta_boxes($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (get_post_type($post_id) !== 'parfume') return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        if (isset($_POST['product_scraper_nonce']) && wp_verify_nonce($_POST['product_scraper_nonce'], 'product_scraper_nonce')) {
            if (isset($_POST['stores']) && is_array($_POST['stores'])) {
                $stores = array();
                
                foreach ($_POST['stores'] as $store_data) {
                    if (empty($store_data['store_id'])) continue;
                    
                    $store = array(
                        'store_id' => sanitize_text_field($store_data['store_id']),
                        'product_url' => esc_url_raw($store_data['product_url']),
                        'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                        'promo_code' => sanitize_text_field($store_data['promo_code']),
                        'promo_code_info' => sanitize_text_field($store_data['promo_code_info']),
                    );
                    
                    $stores[] = $store;
                }
                
                update_post_meta($post_id, '_parfume_stores_v2', $stores);
                
                // Schedule scraping for new URLs
                $this->schedule_product_scraping($post_id, $stores);
                
            } else {
                delete_post_meta($post_id, '_parfume_stores_v2');
            }
        }
    }
    
    /**
     * Schedule product scraping
     */
    private function schedule_product_scraping($post_id, $stores) {
        foreach ($stores as $index => $store) {
            if (!empty($store['product_url'])) {
                $this->add_to_scrape_queue($post_id, $index, $store);
            }
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'parfume-product-scraper') !== false || 
            strpos($hook, 'parfume-scraper-test') !== false ||
            get_post_type() === 'parfume') {
            
            wp_enqueue_script('jquery-ui-sortable');
            
            wp_enqueue_script(
                'parfume-product-scraper',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/product-scraper.js',
                array('jquery', 'jquery-ui-sortable'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-product-scraper', 'parfumeProductScraper', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-scraper-nonce'),
                'strings' => array(
                    'confirm_remove' => __('Are you sure you want to remove this store?', 'parfume-reviews'),
                    'scraping' => __('Scraping...', 'parfume-reviews'),
                    'success' => __('Success', 'parfume-reviews'),
                    'error' => __('Error', 'parfume-reviews'),
                ),
            ));
            
            wp_enqueue_style(
                'parfume-product-scraper',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/product-scraper.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
        }
    }
}