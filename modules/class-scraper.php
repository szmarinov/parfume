<?php
/**
 * Product Scraper система
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PC_Scraper {
    
    private static $instance = null;
    private $batch_size = 10;
    private $scrape_interval = 12; // часове
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_manual_scrape', array($this, 'manual_scrape'));
        add_action('wp_ajax_pc_test_scraper', array($this, 'test_scraper'));
        add_action('wp_ajax_pc_save_schema', array($this, 'save_schema'));
        add_action('pc_scraper_cron', array($this, 'run_batch_scrape'));
    }
    
    public function init() {
        // Регистриране на cron job
        if (!wp_next_scheduled('pc_scraper_cron')) {
            wp_schedule_event(time(), 'hourly', 'pc_scraper_cron');
        }
    }
    
    /**
     * Ръчно скрейпване на конкретен продукт
     */
    public function manual_scrape() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        $result = $this->scrape_product($post_id, $store_index);
        
        wp_send_json($result);
    }
    
    /**
     * Тест на scraper за конкретен URL
     */
    public function test_scraper() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $url = sanitize_url($_POST['url']);
        $store_id = intval($_POST['store_id']);
        
        $result = $this->analyze_page($url, $store_id);
        
        wp_send_json($result);
    }
    
    /**
     * Запазване на schema за магазин
     */
    public function save_schema() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $store_id = intval($_POST['store_id']);
        $schema = array(
            'price_selector' => sanitize_text_field($_POST['price_selector']),
            'old_price_selector' => sanitize_text_field($_POST['old_price_selector']),
            'ml_selector' => sanitize_text_field($_POST['ml_selector']),
            'availability_selector' => sanitize_text_field($_POST['availability_selector']),
            'delivery_selector' => sanitize_text_field($_POST['delivery_selector']),
            'currency_selector' => sanitize_text_field($_POST['currency_selector']),
            'updated' => current_time('mysql')
        );
        
        $schemas = get_option('pc_store_schemas', array());
        $schemas[$store_id] = $schema;
        
        update_option('pc_store_schemas', $schemas);
        
        wp_send_json_success(__('Schema запазена успешно', 'parfume-catalog'));
    }
    
    /**
     * Анализ на страница за автоматично откриване на елементи
     */
    public function analyze_page($url, $store_id = null) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => $this->get_user_agent()
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            return array(
                'success' => false,
                'message' => __('Не може да се зареди съдържанието на страницата', 'parfume-catalog')
            );
        }
        
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);
        
        $analysis = array(
            'prices' => $this->find_prices($xpath),
            'availability' => $this->find_availability($xpath),
            'ml_options' => $this->find_ml_options($xpath),
            'delivery' => $this->find_delivery_info($xpath),
            'currency' => $this->find_currency($xpath)
        );
        
        return array(
            'success' => true,
            'data' => $analysis,
            'preview_html' => $this->generate_preview($html)
        );
    }
    
    /**
     * Търсене на цени в страницата
     */
    private function find_prices($xpath) {
        $price_patterns = array(
            '//span[contains(@class, "price")]',
            '//div[contains(@class, "price")]',
            '//*[contains(@class, "cost")]',
            '//*[contains(@class, "amount")]',
            '//*[text()[contains(., "лв")]]',
            '//*[text()[contains(., "€")]]',
            '//*[text()[contains(., "$")]]'
        );
        
        $found_prices = array();
        
        foreach ($price_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (preg_match('/[\d.,]+/', $text, $matches)) {
                    $price_value = str_replace(',', '.', $matches[0]);
                    if (is_numeric($price_value) && $price_value > 0) {
                        $found_prices[] = array(
                            'value' => $price_value,
                            'text' => $text,
                            'selector' => $this->get_css_selector($node),
                            'xpath' => $this->get_xpath_selector($node)
                        );
                    }
                }
            }
        }
        
        return array_unique($found_prices, SORT_REGULAR);
    }
    
    /**
     * Търсене на информация за наличност
     */
    private function find_availability($xpath) {
        $availability_patterns = array(
            '//*[contains(@class, "stock")]',
            '//*[contains(@class, "availability")]',
            '//*[contains(@class, "available")]',
            '//*[text()[contains(., "наличен")]]',
            '//*[text()[contains(., "в наличност")]]',
            '//*[text()[contains(., "няма в наличност")]]'
        );
        
        $found_availability = array();
        
        foreach ($availability_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (!empty($text)) {
                    $found_availability[] = array(
                        'text' => $text,
                        'selector' => $this->get_css_selector($node),
                        'xpath' => $this->get_xpath_selector($node)
                    );
                }
            }
        }
        
        return $found_availability;
    }
    
    /**
     * Търсене на ML опции
     */
    private function find_ml_options($xpath) {
        $ml_patterns = array(
            '//*[text()[contains(., "ml")]]',
            '//*[text()[contains(., "мл")]]',
            '//*[contains(@class, "volume")]',
            '//*[contains(@class, "size")]'
        );
        
        $found_ml = array();
        
        foreach ($ml_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (preg_match('/(\d+)\s*(ml|мл)/i', $text, $matches)) {
                    $found_ml[] = array(
                        'value' => $matches[1],
                        'text' => $text,
                        'selector' => $this->get_css_selector($node),
                        'xpath' => $this->get_xpath_selector($node)
                    );
                }
            }
        }
        
        return $found_ml;
    }
    
    /**
     * Търсене на информация за доставка
     */
    private function find_delivery_info($xpath) {
        $delivery_patterns = array(
            '//*[contains(@class, "delivery")]',
            '//*[contains(@class, "shipping")]',
            '//*[text()[contains(., "доставка")]]',
            '//*[text()[contains(., "безплатна")]]'
        );
        
        $found_delivery = array();
        
        foreach ($delivery_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (!empty($text) && strlen($text) < 200) {
                    $found_delivery[] = array(
                        'text' => $text,
                        'selector' => $this->get_css_selector($node),
                        'xpath' => $this->get_xpath_selector($node)
                    );
                }
            }
        }
        
        return $found_delivery;
    }
    
    /**
     * Търсене на валута
     */
    private function find_currency($xpath) {
        $currency_patterns = array(
            '//*[text()[contains(., "лв")]]',
            '//*[text()[contains(., "€")]]',
            '//*[text()[contains(., "$")]]',
            '//*[contains(@class, "currency")]'
        );
        
        $found_currency = array();
        
        foreach ($currency_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (preg_match('/(лв|€|\$|USD|EUR|BGN)/i', $text, $matches)) {
                    $found_currency[] = array(
                        'currency' => $matches[1],
                        'text' => $text,
                        'selector' => $this->get_css_selector($node),
                        'xpath' => $this->get_xpath_selector($node)
                    );
                }
            }
        }
        
        return $found_currency;
    }
    
    /**
     * Скрейпване на конкретен продукт
     */
    public function scrape_product($post_id, $store_index) {
        $stores = get_post_meta($post_id, '_pc_stores', true);
        
        if (empty($stores[$store_index]) || empty($stores[$store_index]['product_url'])) {
            return array(
                'success' => false,
                'message' => __('Няма валиден Product URL', 'parfume-catalog')
            );
        }
        
        $store_data = $stores[$store_index];
        $store_id = $store_data['store_id'];
        $product_url = $store_data['product_url'];
        
        // Проверка дали има schema за този магазин
        $schemas = get_option('pc_store_schemas', array());
        if (empty($schemas[$store_id])) {
            return array(
                'success' => false,
                'message' => __('Няма конфигурирана schema за този магазин', 'parfume-catalog')
            );
        }
        
        $schema = $schemas[$store_id];
        
        // Зареждане на страницата
        $response = wp_remote_get($product_url, array(
            'timeout' => 30,
            'user-agent' => $this->get_user_agent()
        ));
        
        if (is_wp_error($response)) {
            $this->log_scrape_error($post_id, $store_index, $response->get_error_message());
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            $this->log_scrape_error($post_id, $store_index, 'Empty response');
            return array(
                'success' => false,
                'message' => __('Празен отговор от сървъра', 'parfume-catalog')
            );
        }
        
        // Парсване на HTML
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);
        
        // Извличане на данни според schema
        $scraped_data = array(
            'price' => $this->extract_by_selector($xpath, $schema['price_selector']),
            'old_price' => $this->extract_by_selector($xpath, $schema['old_price_selector']),
            'ml_options' => $this->extract_multiple_by_selector($xpath, $schema['ml_selector']),
            'availability' => $this->extract_by_selector($xpath, $schema['availability_selector']),
            'delivery' => $this->extract_by_selector($xpath, $schema['delivery_selector']),
            'currency' => $this->extract_by_selector($xpath, $schema['currency_selector']),
            'last_scraped' => current_time('mysql'),
            'next_scrape' => date('Y-m-d H:i:s', strtotime('+' . $this->scrape_interval . ' hours'))
        );
        
        // Обновяване на данните в поста
        $stores[$store_index]['scraped_data'] = $scraped_data;
        update_post_meta($post_id, '_pc_stores', $stores);
        
        $this->log_scrape_success($post_id, $store_index, $scraped_data);
        
        return array(
            'success' => true,
            'data' => $scraped_data,
            'message' => __('Данните са обновени успешно', 'parfume-catalog')
        );
    }
    
    /**
     * Batch скрейпване
     */
    public function run_batch_scrape() {
        $options = get_option('pc_scraper_options', array());
        $batch_size = isset($options['batch_size']) ? intval($options['batch_size']) : $this->batch_size;
        $scrape_interval = isset($options['scrape_interval']) ? intval($options['scrape_interval']) : $this->scrape_interval;
        
        // Намиране на продукти които са готови за скрейпване
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_pc_stores',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $posts = get_posts($args);
        $processed = 0;
        $pointer = get_option('pc_scrape_pointer', 0);
        
        foreach ($posts as $post) {
            if ($processed >= $batch_size) {
                break;
            }
            
            $stores = get_post_meta($post->ID, '_pc_stores', true);
            if (empty($stores)) {
                continue;
            }
            
            foreach ($stores as $index => $store) {
                if ($processed >= $batch_size) {
                    break 2;
                }
                
                if ($index < $pointer) {
                    continue;
                }
                
                if (empty($store['product_url'])) {
                    continue;
                }
                
                // Проверка дали е време за скрейпване
                $last_scraped = isset($store['scraped_data']['last_scraped']) ? $store['scraped_data']['last_scraped'] : '1970-01-01';
                $next_scrape_time = strtotime($last_scraped) + ($scrape_interval * 3600);
                
                if (time() >= $next_scrape_time) {
                    $this->scrape_product($post->ID, $index);
                    $processed++;
                    update_option('pc_scrape_pointer', $index + 1);
                }
            }
        }
        
        // Reset pointer when finished
        if ($processed == 0) {
            update_option('pc_scrape_pointer', 0);
        }
    }
    
    /**
     * Извличане на данни по селектор
     */
    private function extract_by_selector($xpath, $selector) {
        if (empty($selector)) {
            return '';
        }
        
        // Опит с CSS селектор (конвертиран в XPath)
        $xpath_selector = $this->css_to_xpath($selector);
        $nodes = $xpath->query($xpath_selector);
        
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        
        // Опит директно с XPath ако е подаден такъв
        if (strpos($selector, '/') === 0 || strpos($selector, '//') === 0) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }
        }
        
        return '';
    }
    
    /**
     * Извличане на множество данни по селектор
     */
    private function extract_multiple_by_selector($xpath, $selector) {
        if (empty($selector)) {
            return array();
        }
        
        $xpath_selector = $this->css_to_xpath($selector);
        $nodes = $xpath->query($xpath_selector);
        
        $results = array();
        foreach ($nodes as $node) {
            $text = trim($node->textContent);
            if (!empty($text)) {
                $results[] = $text;
            }
        }
        
        return $results;
    }
    
    /**
     * Конвертиране на CSS селектор в XPath
     */
    private function css_to_xpath($css_selector) {
        // Основни CSS селектори -> XPath
        $css_selector = trim($css_selector);
        
        // Class селектор
        if (strpos($css_selector, '.') === 0) {
            $class = substr($css_selector, 1);
            return "//*[contains(@class, '$class')]";
        }
        
        // ID селектор
        if (strpos($css_selector, '#') === 0) {
            $id = substr($css_selector, 1);
            return "//*[@id='$id']";
        }
        
        // Tag селектор
        if (preg_match('/^[a-zA-Z]+$/', $css_selector)) {
            return "//$css_selector";
        }
        
        // Сложни селектори - основна поддръжка
        return "//*[contains(@class, '$css_selector')]";
    }
    
    /**
     * Генериране на CSS селектор от DOM node
     */
    private function get_css_selector($node) {
        $path = array();
        
        while ($node && $node->nodeType === XML_ELEMENT_NODE) {
            $selector = $node->nodeName;
            
            if ($node->hasAttribute('id')) {
                $selector = '#' . $node->getAttribute('id');
                array_unshift($path, $selector);
                break;
            } elseif ($node->hasAttribute('class')) {
                $classes = explode(' ', $node->getAttribute('class'));
                $selector .= '.' . implode('.', array_filter($classes));
            }
            
            array_unshift($path, $selector);
            $node = $node->parentNode;
        }
        
        return implode(' > ', $path);
    }
    
    /**
     * Генериране на XPath селектор от DOM node
     */
    private function get_xpath_selector($node) {
        $path = array();
        
        while ($node && $node->nodeType === XML_ELEMENT_NODE) {
            $index = 1;
            $sibling = $node->previousSibling;
            
            while ($sibling) {
                if ($sibling->nodeType === XML_ELEMENT_NODE && $sibling->nodeName === $node->nodeName) {
                    $index++;
                }
                $sibling = $sibling->previousSibling;
            }
            
            $path[] = $node->nodeName . '[' . $index . ']';
            $node = $node->parentNode;
        }
        
        return '/' . implode('/', array_reverse($path));
    }
    
    /**
     * Генериране на preview HTML
     */
    private function generate_preview($html) {
        // Намаляване на HTML за preview (първите 2000 символа)
        $preview = substr($html, 0, 2000);
        
        // Премахване на script и style тагове
        $preview = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $preview);
        $preview = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $preview);
        
        return $preview;
    }
    
    /**
     * Получаване на User Agent
     */
    private function get_user_agent() {
        $options = get_option('pc_scraper_options', array());
        
        if (!empty($options['user_agent'])) {
            return $options['user_agent'];
        }
        
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    }
    
    /**
     * Логване на успешно скрейпване
     */
    private function log_scrape_success($post_id, $store_index, $data) {
        $log = get_option('pc_scrape_log', array());
        
        $log[] = array(
            'post_id' => $post_id,
            'store_index' => $store_index,
            'status' => 'success',
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
        
        // Запазване само на последните 1000 записа
        if (count($log) > 1000) {
            $log = array_slice($log, -1000);
        }
        
        update_option('pc_scrape_log', $log);
    }
    
    /**
     * Логване на грешка при скрейпване
     */
    private function log_scrape_error($post_id, $store_index, $error) {
        $log = get_option('pc_scrape_log', array());
        
        $log[] = array(
            'post_id' => $post_id,
            'store_index' => $store_index,
            'status' => 'error',
            'error' => $error,
            'timestamp' => current_time('mysql')
        );
        
        // Запазване само на последните 1000 записа
        if (count($log) > 1000) {
            $log = array_slice($log, -1000);
        }
        
        update_option('pc_scrape_log', $log);
    }
    
    /**
     * Получаване на всички URL-и за скрейпване
     */
    public function get_all_scrape_urls() {
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_pc_stores',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $posts = get_posts($args);
        $urls = array();
        
        foreach ($posts as $post) {
            $stores = get_post_meta($post->ID, '_pc_stores', true);
            if (empty($stores)) {
                continue;
            }
            
            foreach ($stores as $index => $store) {
                if (!empty($store['product_url'])) {
                    $urls[] = array(
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'store_index' => $index,
                        'store_name' => $store['store_name'],
                        'product_url' => $store['product_url'],
                        'last_scraped' => isset($store['scraped_data']['last_scraped']) ? $store['scraped_data']['last_scraped'] : '',
                        'next_scrape' => isset($store['scraped_data']['next_scrape']) ? $store['scraped_data']['next_scrape'] : '',
                        'status' => $this->get_scrape_status($post->ID, $index)
                    );
                }
            }
        }
        
        return $urls;
    }
    
    /**
     * Получаване на статуса на скрейпване
     */
    private function get_scrape_status($post_id, $store_index) {
        $log = get_option('pc_scrape_log', array());
        
        // Намиране на последния запис за този продукт/магазин
        $last_log = null;
        for ($i = count($log) - 1; $i >= 0; $i--) {
            if ($log[$i]['post_id'] == $post_id && $log[$i]['store_index'] == $store_index) {
                $last_log = $log[$i];
                break;
            }
        }
        
        if (!$last_log) {
            return 'never';
        }
        
        return $last_log['status'];
    }
}