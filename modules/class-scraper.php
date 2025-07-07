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

class Parfume_Catalog_Scraper {
    
    private static $instance = null;
    private $batch_size = 10;
    private $scrape_interval = 12; // часове
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
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
        
        // Настройки от опциите
        $options = get_option('parfume_catalog_settings', array());
        $this->batch_size = !empty($options['scrape_batch_size']) ? intval($options['scrape_batch_size']) : 10;
        $this->scrape_interval = !empty($options['scrape_interval']) ? intval($options['scrape_interval']) : 12;
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
            'last_updated' => current_time('mysql')
        );
        
        $schemas = get_option('pc_store_schemas', array());
        $schemas[$store_id] = $schema;
        update_option('pc_store_schemas', $schemas);
        
        wp_send_json_success(array(
            'message' => __('Schema записана успешно', 'parfume-catalog')
        ));
    }
    
    /**
     * Batch скрейпване (cron job)
     */
    public function run_batch_scrape() {
        $all_urls = $this->get_all_scrape_urls();
        
        if (empty($all_urls)) {
            return;
        }
        
        // Получаване на pointer позицията
        $pointer = get_option('pc_scrape_pointer', 0);
        
        // Вземане на batch от URL-и
        $batch = array_slice($all_urls, $pointer, $this->batch_size);
        
        foreach ($batch as $item) {
            $this->scrape_product($item['post_id'], $item['store_index']);
            
            // Малка пауза между заявките
            sleep(1);
        }
        
        // Обновяване на pointer
        $new_pointer = $pointer + $this->batch_size;
        if ($new_pointer >= count($all_urls)) {
            $new_pointer = 0; // Рестарт от началото
        }
        
        update_option('pc_scrape_pointer', $new_pointer);
        update_option('pc_scrape_last_run', current_time('mysql'));
    }
    
    /**
     * Скрейпване на конкретен продукт
     */
    public function scrape_product($post_id, $store_index) {
        $stores = get_post_meta($post_id, '_pc_stores', true);
        
        if (empty($stores) || !isset($stores[$store_index])) {
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
        
        // Запазване на данните
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
     * Анализ на страница за създаване на schema
     */
    public function analyze_page($url, $store_id) {
        // Зареждане на страницата
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
                'message' => __('Празен отговор от сървъра', 'parfume-catalog')
            );
        }
        
        // Парсване на HTML
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);
        
        // Автоматично откриване на възможни селектори
        $suggestions = array(
            'prices' => $this->find_price_elements($xpath),
            'ml_options' => $this->find_ml_elements($xpath),
            'availability' => $this->find_availability_elements($xpath),
            'delivery' => $this->find_delivery_elements($xpath)
        );
        
        // Генериране на preview
        $preview = $this->generate_preview($html);
        
        return array(
            'success' => true,
            'suggestions' => $suggestions,
            'preview' => $preview,
            'full_html' => $html
        );
    }
    
    /**
     * Извличане на стойност по CSS/XPath селектор
     */
    private function extract_by_selector($xpath, $selector) {
        if (empty($selector)) {
            return '';
        }
        
        // Конвертиране на CSS селектор в XPath ако е нужно
        if (strpos($selector, '/') !== 0) {
            $selector = $this->css_to_xpath($selector);
        }
        
        try {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }
        } catch (Exception $e) {
            error_log('Scraper selector error: ' . $e->getMessage());
        }
        
        return '';
    }
    
    /**
     * Извличане на множество стойности по селектор
     */
    private function extract_multiple_by_selector($xpath, $selector) {
        if (empty($selector)) {
            return array();
        }
        
        // Конвертиране на CSS селектор в XPath ако е нужно
        if (strpos($selector, '/') !== 0) {
            $selector = $this->css_to_xpath($selector);
        }
        
        $results = array();
        
        try {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $results[] = trim($node->textContent);
                }
            }
        } catch (Exception $e) {
            error_log('Scraper selector error: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Конвертиране на CSS селектор в XPath
     */
    private function css_to_xpath($css) {
        // Базова конвертация на CSS към XPath
        $xpath = $css;
        
        // Class селектори
        $xpath = preg_replace('/\.([a-zA-Z0-9_-]+)/', '*[contains(concat(" ",normalize-space(@class)," ")," $1 ")]', $xpath);
        
        // ID селектори
        $xpath = preg_replace('/#([a-zA-Z0-9_-]+)/', '*[@id="$1"]', $xpath);
        
        // Атрибутни селектори
        $xpath = preg_replace('/\[([a-zA-Z0-9_-]+)\]/', '[@$1]', $xpath);
        $xpath = preg_replace('/\[([a-zA-Z0-9_-]+)="([^"]+)"\]/', '[@$1="$2"]', $xpath);
        
        // Child селектори
        $xpath = str_replace(' > ', '/', $xpath);
        
        // Descendant селектори
        $xpath = preg_replace('/\s+/', '//', $xpath);
        
        // Добавяне на "//" в началото ако няма
        if (strpos($xpath, '/') !== 0) {
            $xpath = '//' . $xpath;
        }
        
        return $xpath;
    }
    
    /**
     * Намиране на ценови елементи
     */
    private function find_price_elements($xpath) {
        $price_selectors = array(
            '//*[contains(@class,"price")]',
            '//*[contains(@class,"cost")]',
            '//*[contains(@class,"amount")]',
            '//*[contains(text(),"лв")]',
            '//*[contains(text(),"€")]',
            '//*[contains(text(),"$")]'
        );
        
        $found_prices = array();
        
        foreach ($price_selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (preg_match('/[\d,\.]+/', $text)) {
                        $found_prices[] = array(
                            'text' => $text,
                            'selector' => $this->get_element_selector($node),
                            'xpath' => $this->get_element_xpath($node)
                        );
                    }
                }
            }
        }
        
        return array_slice($found_prices, 0, 10); // Ограничаване на резултатите
    }
    
    /**
     * Намиране на ml елементи
     */
    private function find_ml_elements($xpath) {
        $ml_selectors = array(
            '//*[contains(text(),"ml")]',
            '//*[contains(text(),"мл")]',
            '//*[contains(@class,"volume")]',
            '//*[contains(@class,"size")]'
        );
        
        $found_ml = array();
        
        foreach ($ml_selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (preg_match('/\d+\s*(ml|мл)/i', $text)) {
                        $found_ml[] = array(
                            'text' => $text,
                            'selector' => $this->get_element_selector($node),
                            'xpath' => $this->get_element_xpath($node)
                        );
                    }
                }
            }
        }
        
        return array_slice($found_ml, 0, 10);
    }
    
    /**
     * Намиране на елементи за наличност
     */
    private function find_availability_elements($xpath) {
        $availability_selectors = array(
            '//*[contains(@class,"stock")]',
            '//*[contains(@class,"availability")]',
            '//*[contains(@class,"available")]',
            '//*[contains(text(),"наличен")]',
            '//*[contains(text(),"в наличност")]',
            '//*[contains(text(),"available")]',
            '//*[contains(text(),"in stock")]'
        );
        
        $found_availability = array();
        
        foreach ($availability_selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (!empty($text) && strlen($text) < 100) {
                        $found_availability[] = array(
                            'text' => $text,
                            'selector' => $this->get_element_selector($node),
                            'xpath' => $this->get_element_xpath($node)
                        );
                    }
                }
            }
        }
        
        return array_slice($found_availability, 0, 10);
    }
    
    /**
     * Намиране на елементи за доставка
     */
    private function find_delivery_elements($xpath) {
        $delivery_selectors = array(
            '//*[contains(@class,"shipping")]',
            '//*[contains(@class,"delivery")]',
            '//*[contains(text(),"доставка")]',
            '//*[contains(text(),"безплатна")]',
            '//*[contains(text(),"shipping")]',
            '//*[contains(text(),"delivery")]'
        );
        
        $found_delivery = array();
        
        foreach ($delivery_selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (!empty($text) && strlen($text) < 200) {
                        $found_delivery[] = array(
                            'text' => $text,
                            'selector' => $this->get_element_selector($node),
                            'xpath' => $this->get_element_xpath($node)
                        );
                    }
                }
            }
        }
        
        return array_slice($found_delivery, 0, 10);
    }
    
    /**
     * Получаване на CSS селектор за елемент
     */
    private function get_element_selector($node) {
        $selector = $node->nodeName;
        
        if ($node->hasAttribute('id')) {
            $selector .= '#' . $node->getAttribute('id');
        }
        
        if ($node->hasAttribute('class')) {
            $classes = explode(' ', $node->getAttribute('class'));
            foreach ($classes as $class) {
                if (!empty(trim($class))) {
                    $selector .= '.' . trim($class);
                }
            }
        }
        
        return $selector;
    }
    
    /**
     * Получаване на XPath за елемент
     */
    private function get_element_xpath($node) {
        $path = array();
        
        while ($node && $node->nodeType === XML_ELEMENT_NODE) {
            $index = 1;
            $prev = $node->previousSibling;
            
            while ($prev) {
                if ($prev->nodeType === XML_ELEMENT_NODE && $prev->nodeName === $node->nodeName) {
                    $index++;
                }
                $prev = $prev->previousSibling;
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
        $options = get_option('parfume_catalog_settings', array());
        
        if (!empty($options['scrape_user_agent'])) {
            return $options['scrape_user_agent'];
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
            'post_status' => 'publish',
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
            
            if (!empty($stores) && is_array($stores)) {
                foreach ($stores as $index => $store) {
                    if (!empty($store['product_url'])) {
                        $urls[] = array(
                            'post_id' => $post->ID,
                            'store_index' => $index,
                            'product_url' => $store['product_url'],
                            'store_id' => $store['store_id']
                        );
                    }
                }
            }
        }
        
        return $urls;
    }
    
    /**
     * Получаване на статистики за scraper
     */
    public function get_scraper_stats() {
        $all_urls = $this->get_all_scrape_urls();
        $total_urls = count($all_urls);
        
        $log = get_option('pc_scrape_log', array());
        $last_24h = array_filter($log, function($entry) {
            return strtotime($entry['timestamp']) > (time() - 86400);
        });
        
        $success_count = count(array_filter($last_24h, function($entry) {
            return $entry['status'] === 'success';
        }));
        
        $error_count = count(array_filter($last_24h, function($entry) {
            return $entry['status'] === 'error';
        }));
        
        return array(
            'total_urls' => $total_urls,
            'success_24h' => $success_count,
            'errors_24h' => $error_count,
            'last_run' => get_option('pc_scrape_last_run', ''),
            'next_run' => wp_next_scheduled('pc_scraper_cron') ? date('Y-m-d H:i:s', wp_next_scheduled('pc_scraper_cron')) : '',
            'pointer' => get_option('pc_scrape_pointer', 0)
        );
    }
}