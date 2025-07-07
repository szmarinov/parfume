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
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_manual_scrape', array($this, 'manual_scrape'));
        add_action('wp_ajax_pc_test_scraper', array($this, 'test_scraper'));
        add_action('wp_ajax_pc_save_schema', array($this, 'save_schema'));
        add_action('pc_scraper_cron', array($this, 'run_batch_scrape'));
        
        // Регистриране на допълнителни AJAX действия
        $this->register_ajax_actions();
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
     * Ръчно скрейпване
     */
    public function manual_scrape() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        if (!$post_id) {
            wp_send_json_error(__('Невалиден пост ID', 'parfume-catalog'));
        }
        
        $result = $this->scrape_post_store($post_id, $store_index);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Тест скрейпър
     */
    public function test_scraper() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $url = esc_url_raw($_POST['url']);
        
        if (!$url) {
            wp_send_json_error(__('Невалиден URL', 'parfume-catalog'));
        }
        
        $result = $this->analyze_page($url);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Запазване на schema
     */
    public function save_schema() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $schema_data = array(
            'price_selector' => sanitize_text_field($_POST['price_selector']),
            'old_price_selector' => sanitize_text_field($_POST['old_price_selector']),
            'ml_selector' => sanitize_text_field($_POST['ml_selector']),
            'availability_selector' => sanitize_text_field($_POST['availability_selector']),
            'delivery_selector' => sanitize_text_field($_POST['delivery_selector'])
        );
        
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        $schemas[$store_id] = $schema_data;
        update_option('parfume_catalog_scraper_schemas', $schemas);
        
        wp_send_json_success(__('Schema запазена успешно', 'parfume-catalog'));
    }
    
    /**
     * Batch скрейпване
     */
    public function run_batch_scrape() {
        $pointer = get_option('pc_scraper_pointer', 0);
        $processed = 0;
        
        // Получаване на всички постове с product URLs
        $posts_with_urls = $this->get_posts_with_product_urls($pointer, $this->batch_size);
        
        foreach ($posts_with_urls as $post_data) {
            $this->scrape_post_stores($post_data['post_id']);
            $processed++;
            
            if ($processed >= $this->batch_size) {
                break;
            }
        }
        
        // Обновяване на pointer
        $new_pointer = $pointer + $processed;
        $total_posts = $this->get_total_posts_with_urls();
        
        if ($new_pointer >= $total_posts) {
            $new_pointer = 0; // Reset to beginning
        }
        
        update_option('pc_scraper_pointer', $new_pointer);
        
        // Log
        $this->log_scraper_activity("Batch scrape completed. Processed: {$processed} posts.");
    }
    
    /**
     * Скрейпване на всички магазини за един пост
     */
    private function scrape_post_stores($post_id) {
        $stores_data = get_post_meta($post_id, '_pc_stores', true);
        
        if (empty($stores_data) || !is_array($stores_data)) {
            return;
        }
        
        foreach ($stores_data as $index => $store_data) {
            if (!empty($store_data['product_url'])) {
                $this->scrape_post_store($post_id, $index);
                
                // Добавяне на delay между заявките
                sleep(1);
            }
        }
    }
    
    /**
     * Скрейпване на конкретен магазин за пост
     */
    private function scrape_post_store($post_id, $store_index) {
        $stores_data = get_post_meta($post_id, '_pc_stores', true);
        
        if (empty($stores_data[$store_index]['product_url'])) {
            return array(
                'success' => false,
                'message' => __('Няма зададен Product URL', 'parfume-catalog')
            );
        }
        
        $store_data = $stores_data[$store_index];
        $url = $store_data['product_url'];
        $store_id = $store_data['store_id'];
        
        // Получаване на schema за магазина
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        $schema = isset($schemas[$store_id]) ? $schemas[$store_id] : null;
        
        if (!$schema) {
            return array(
                'success' => false,
                'message' => __('Няма настроена schema за този магазин', 'parfume-catalog')
            );
        }
        
        // Скрейпване
        $scraped_data = $this->scrape_url($url, $schema);
        
        if ($scraped_data['success']) {
            // Запазване на данните
            $stores_data[$store_index]['scraped_data'] = $scraped_data['data'];
            $stores_data[$store_index]['last_scraped'] = current_time('mysql');
            $stores_data[$store_index]['next_scrape'] = date('Y-m-d H:i:s', strtotime("+{$this->scrape_interval} hours"));
            $stores_data[$store_index]['scrape_status'] = 'success';
            
            update_post_meta($post_id, '_pc_stores', $stores_data);
            
            return array(
                'success' => true,
                'data' => $scraped_data['data']
            );
        } else {
            // Записване на грешката
            $stores_data[$store_index]['scrape_status'] = 'error';
            $stores_data[$store_index]['scrape_error'] = $scraped_data['message'];
            $stores_data[$store_index]['last_scraped'] = current_time('mysql');
            
            update_post_meta($post_id, '_pc_stores', $stores_data);
            
            return array(
                'success' => false,
                'message' => $scraped_data['message']
            );
        }
    }
    
    /**
     * Скрейпване на URL със schema
     */
    private function scrape_url($url, $schema) {
        $options = get_option('parfume_catalog_settings', array());
        $user_agent = !empty($options['scraper_user_agent']) ? $options['scraper_user_agent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        // Опит за заявка
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => $user_agent,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return array(
                'success' => false,
                'message' => sprintf(__('HTTP грешка: %d', 'parfume-catalog'), $status_code)
            );
        }
        
        // Парсиране на HTML
        if (!class_exists('DOMDocument')) {
            return array(
                'success' => false,
                'message' => __('DOMDocument не е наличен', 'parfume-catalog')
            );
        }
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($body);
        $xpath = new DOMXPath($dom);
        
        $extracted_data = array();
        
        // Извличане на цена
        if (!empty($schema['price_selector'])) {
            $price_nodes = $xpath->query($this->css_to_xpath($schema['price_selector']));
            if ($price_nodes->length > 0) {
                $price_text = trim($price_nodes->item(0)->textContent);
                $extracted_data['price'] = $this->extract_price($price_text);
            }
        }
        
        // Извличане на стара цена
        if (!empty($schema['old_price_selector'])) {
            $old_price_nodes = $xpath->query($this->css_to_xpath($schema['old_price_selector']));
            if ($old_price_nodes->length > 0) {
                $old_price_text = trim($old_price_nodes->item(0)->textContent);
                $extracted_data['old_price'] = $this->extract_price($old_price_text);
            }
        }
        
        // Извличане на разфасовки
        if (!empty($schema['ml_selector'])) {
            $ml_nodes = $xpath->query($this->css_to_xpath($schema['ml_selector']));
            $ml_options = array();
            foreach ($ml_nodes as $node) {
                $ml_text = trim($node->textContent);
                $ml_value = $this->extract_ml($ml_text);
                if ($ml_value) {
                    $ml_options[] = $ml_value;
                }
            }
            $extracted_data['ml_options'] = array_unique($ml_options);
        }
        
        // Извличане на наличност
        if (!empty($schema['availability_selector'])) {
            $availability_nodes = $xpath->query($this->css_to_xpath($schema['availability_selector']));
            if ($availability_nodes->length > 0) {
                $availability_text = trim($availability_nodes->item(0)->textContent);
                $extracted_data['availability'] = $this->determine_availability($availability_text);
            }
        }
        
        // Извличане на информация за доставка
        if (!empty($schema['delivery_selector'])) {
            $delivery_nodes = $xpath->query($this->css_to_xpath($schema['delivery_selector']));
            if ($delivery_nodes->length > 0) {
                $delivery_text = trim($delivery_nodes->item(0)->textContent);
                $extracted_data['delivery'] = $delivery_text;
            }
        }
        
        return array(
            'success' => true,
            'data' => $extracted_data
        );
    }
    
    /**
     * Анализ на страница за schema tool
     */
    private function analyze_page($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($body);
        $xpath = new DOMXPath($dom);
        
        // Търсене на елементи, които изглеждат като цени
        $price_patterns = array(
            '/\d+[.,]\d+\s*(лв|bgn|€|$)/',
            '/\d+\s*(лв|bgn|€|$)/',
            '/(лв|bgn|€|$)\s*\d+[.,]\d+/',
            '/(лв|bgn|€|$)\s*\d+/'
        );
        
        $potential_prices = array();
        $all_text_nodes = $xpath->query('//text()');
        
        foreach ($all_text_nodes as $node) {
            $text = trim($node->textContent);
            foreach ($price_patterns as $pattern) {
                if (preg_match($pattern, $text)) {
                    $potential_prices[] = array(
                        'text' => $text,
                        'selector' => $this->get_css_selector($node->parentNode)
                    );
                }
            }
        }
        
        // Търсене на ML информация
        $ml_patterns = array(
            '/\d+\s*ml/',
            '/\d+\s*мл/'
        );
        
        $potential_ml = array();
        foreach ($all_text_nodes as $node) {
            $text = trim($node->textContent);
            foreach ($ml_patterns as $pattern) {
                if (preg_match($pattern, $text)) {
                    $potential_ml[] = array(
                        'text' => $text,
                        'selector' => $this->get_css_selector($node->parentNode)
                    );
                }
            }
        }
        
        return array(
            'success' => true,
            'data' => array(
                'prices' => array_slice($potential_prices, 0, 10), // Ограничаване до 10 резултата
                'ml_options' => array_slice($potential_ml, 0, 10),
                'page_title' => $dom->getElementsByTagName('title')->item(0)->textContent ?? '',
                'url' => $url
            )
        );
    }
    
    /**
     * Получаване на постове с product URLs
     */
    private function get_posts_with_product_urls($offset = 0, $limit = 10) {
        global $wpdb;
        
        $query = "
            SELECT p.ID as post_id, pm.meta_value as stores_data
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'parfumes' 
            AND p.post_status = 'publish'
            AND pm.meta_key = '_pc_stores'
            AND pm.meta_value != ''
            LIMIT %d OFFSET %d
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $limit, $offset));
        
        $posts = array();
        foreach ($results as $row) {
            $stores_data = maybe_unserialize($row->stores_data);
            if (is_array($stores_data)) {
                foreach ($stores_data as $store) {
                    if (!empty($store['product_url'])) {
                        $posts[] = array('post_id' => $row->post_id);
                        break;
                    }
                }
            }
        }
        
        return $posts;
    }
    
    /**
     * Получаване на общия брой постове с URLs
     */
    private function get_total_posts_with_urls() {
        global $wpdb;
        
        $query = "
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'parfumes' 
            AND p.post_status = 'publish'
            AND pm.meta_key = '_pc_stores'
            AND pm.meta_value != ''
        ";
        
        return $wpdb->get_var($query);
    }
    
    /**
     * CSS selector към XPath
     */
    private function css_to_xpath($css_selector) {
        // Основни CSS селектори към XPath
        $css_selector = trim($css_selector);
        
        // Class selector
        $css_selector = preg_replace('/\.([a-zA-Z0-9_-]+)/', "[contains(@class,'$1')]", $css_selector);
        
        // ID selector
        $css_selector = preg_replace('/#([a-zA-Z0-9_-]+)/', "[@id='$1']", $css_selector);
        
        // Attribute selector
        $css_selector = preg_replace('/\[([a-zA-Z0-9_-]+)="([^"]+)"\]/', "[@$1='$2']", $css_selector);
        
        // Descendant combinator
        $css_selector = str_replace(' ', '//', $css_selector);
        
        // Child combinator
        $css_selector = str_replace('>', '/', $css_selector);
        
        // Ако не започва с //, добавяме //
        if (strpos($css_selector, '//') !== 0 && strpos($css_selector, '/') !== 0) {
            $css_selector = '//' . $css_selector;
        }
        
        return $css_selector;
    }
    
    /**
     * Получаване на CSS selector от DOM node
     */
    private function get_css_selector($node) {
        $selector = '';
        
        while ($node && $node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($node->tagName);
            
            if ($node->hasAttribute('id')) {
                $selector = '#' . $node->getAttribute('id') . ($selector ? ' > ' . $selector : '');
                break;
            }
            
            if ($node->hasAttribute('class')) {
                $classes = explode(' ', $node->getAttribute('class'));
                $class = '.' . implode('.', array_filter($classes));
                $selector = $tag . $class . ($selector ? ' > ' . $selector : '');
            } else {
                $selector = $tag . ($selector ? ' > ' . $selector : '');
            }
            
            $node = $node->parentNode;
        }
        
        return $selector;
    }
    
    /**
     * Извличане на цена от текст
     */
    private function extract_price($text) {
        // Премахване на whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Търсене на числа с десетични знаци
        if (preg_match('/(\d+[.,]\d+)/', $text, $matches)) {
            return floatval(str_replace(',', '.', $matches[1]));
        }
        
        // Търсене на цели числа
        if (preg_match('/(\d+)/', $text, $matches)) {
            return floatval($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Извличане на ML от текст
     */
    private function extract_ml($text) {
        if (preg_match('/(\d+)\s*(ml|мл)/i', $text, $matches)) {
            return intval($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Определяване на наличност
     */
    private function determine_availability($text) {
        $text = mb_strtolower($text, 'UTF-8');
        
        $available_keywords = array('наличен', 'в наличност', 'available', 'in stock', 'на склад');
        $unavailable_keywords = array('няма наличност', 'изчерпан', 'out of stock', 'unavailable', 'не е наличен');
        
        foreach ($available_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'available';
            }
        }
        
        foreach ($unavailable_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'unavailable';
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Log на скрейпър активност
     */
    private function log_scraper_activity($message) {
        $log_option = 'pc_scraper_log';
        $log = get_option($log_option, array());
        
        $log[] = array(
            'timestamp' => current_time('mysql'),
            'message' => $message
        );
        
        // Запазване само на последните 100 записа
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        update_option($log_option, $log);
    }
    
    /**
     * Получаване на scraper log
     */
    public function get_scraper_log() {
        return get_option('pc_scraper_log', array());
    }
    
    /**
     * AJAX действия за admin
     */
    public function run_batch_scrape_ajax() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $this->run_batch_scrape();
        
        wp_send_json_success(__('Batch скрейпването завърши успешно', 'parfume-catalog'));
    }
    
    /**
     * AJAX за скрейпване на единичен пост
     */
    public function scrape_single_post_ajax() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error(__('Невалиден пост ID', 'parfume-catalog'));
        }
        
        $this->scrape_post_stores($post_id);
        
        wp_send_json_success(__('Скрейпването на поста завърши успешно', 'parfume-catalog'));
    }
    
    /**
     * AJAX за изчистване на лога
     */
    public function clear_scraper_log_ajax() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        delete_option('pc_scraper_log');
        
        wp_send_json_success(__('Логът е изчистен успешно', 'parfume-catalog'));
    }
    
    /**
     * Регистриране на AJAX действия
     */
    public function register_ajax_actions() {
        add_action('wp_ajax_pc_run_batch_scrape', array($this, 'run_batch_scrape_ajax'));
        add_action('wp_ajax_pc_scrape_post', array($this, 'scrape_single_post_ajax'));
        add_action('wp_ajax_pc_clear_scraper_log', array($this, 'clear_scraper_log_ajax'));
    }
    
    /**
     * Рендериране на scraper monitor страница
     */
    public function render_scraper_monitor() {
        global $wpdb;
        
        // Получаване на статистики
        $total_posts = $this->get_total_posts_with_urls();
        $log_entries = $this->get_scraper_log();
        
        // Получаване на всички постове със store данни
        $query = "
            SELECT p.ID, p.post_title, pm.meta_value as stores_data
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'parfumes' 
            AND p.post_status = 'publish'
            AND pm.meta_key = '_pc_stores'
            AND pm.meta_value != ''
            ORDER BY p.post_modified DESC
            LIMIT 50
        ";
        
        $posts_with_stores = $wpdb->get_results($query);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Product Scraper Monitor', 'parfume-catalog'); ?></h1>
            
            <div class="pc-scraper-stats">
                <div class="stats-box">
                    <h3><?php _e('Общо постове', 'parfume-catalog'); ?></h3>
                    <span class="stat-number"><?php echo $total_posts; ?></span>
                </div>
                
                <div class="stats-box">
                    <h3><?php _e('Последно изпълнение', 'parfume-catalog'); ?></h3>
                    <span class="stat-text">
                        <?php 
                        $last_cron = wp_next_scheduled('pc_scraper_cron');
                        echo $last_cron ? date('d.m.Y H:i', $last_cron) : __('Няма планирано', 'parfume-catalog');
                        ?>
                    </span>
                </div>
                
                <div class="stats-box">
                    <h3><?php _e('Batch размер', 'parfume-catalog'); ?></h3>
                    <span class="stat-number"><?php echo $this->batch_size; ?></span>
                </div>
            </div>
            
            <div class="pc-scraper-actions">
                <button type="button" class="button button-primary" id="run-batch-scrape">
                    <?php _e('Изпълни batch скрейпване', 'parfume-catalog'); ?>
                </button>
                
                <button type="button" class="button" id="clear-scraper-log">
                    <?php _e('Изчисти лог', 'parfume-catalog'); ?>
                </button>
            </div>
            
            <h2><?php _e('Постове със скрейпър данни', 'parfume-catalog'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Пост', 'parfume-catalog'); ?></th>
                        <th><?php _e('Магазини', 'parfume-catalog'); ?></th>
                        <th><?php _e('Последно скрейпване', 'parfume-catalog'); ?></th>
                        <th><?php _e('Статус', 'parfume-catalog'); ?></th>
                        <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts_with_stores as $post): ?>
                        <?php 
                        $stores_data = maybe_unserialize($post->stores_data);
                        if (!is_array($stores_data)) continue;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($post->post_title); ?></strong><br>
                                <small>ID: <?php echo $post->ID; ?></small>
                            </td>
                            <td>
                                <?php foreach ($stores_data as $index => $store): ?>
                                    <?php if (!empty($store['product_url'])): ?>
                                        <div class="store-info">
                                            <strong><?php echo esc_html($store['store_name'] ?? 'N/A'); ?></strong><br>
                                            <small><?php echo esc_url($store['product_url']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php 
                                $last_scraped = '';
                                foreach ($stores_data as $store) {
                                    if (!empty($store['last_scraped'])) {
                                        $last_scraped = $store['last_scraped'];
                                        break;
                                    }
                                }
                                echo $last_scraped ? date('d.m.Y H:i', strtotime($last_scraped)) : __('Никога', 'parfume-catalog');
                                ?>
                            </td>
                            <td>
                                <?php 
                                $has_error = false;
                                foreach ($stores_data as $store) {
                                    if (!empty($store['scrape_status']) && $store['scrape_status'] === 'error') {
                                        $has_error = true;
                                        break;
                                    }
                                }
                                
                                if ($has_error) {
                                    echo '<span class="status-error">' . __('Грешка', 'parfume-catalog') . '</span>';
                                } else {
                                    echo '<span class="status-success">' . __('ОК', 'parfume-catalog') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="button scrape-post" data-post-id="<?php echo $post->ID; ?>">
                                    <?php _e('Скрейпни сега', 'parfume-catalog'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h3><?php _e('Лог на активност', 'parfume-catalog'); ?></h3>
            
            <div class="pc-scraper-log">
                <?php if (empty($log_entries)): ?>
                    <p><?php _e('Няма записи в лога', 'parfume-catalog'); ?></p>
                <?php else: ?>
                    <ul>
                        <?php foreach (array_reverse(array_slice($log_entries, -20)) as $entry): ?>
                            <li>
                                <strong><?php echo date('d.m.Y H:i:s', strtotime($entry['timestamp'])); ?></strong>
                                <?php echo esc_html($entry['message']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Run batch scrape
            $('#run-batch-scrape').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Изпълнява се...', 'parfume-catalog'); ?>');
                
                $.post(ajaxurl, {
                    action: 'pc_run_batch_scrape',
                    nonce: '<?php echo wp_create_nonce('pc_admin_nonce'); ?>'
                }, function(response) {
                    button.prop('disabled', false).text('<?php _e('Изпълни batch скрейпване', 'parfume-catalog'); ?>');
                    
                    if (response.success) {
                        alert('<?php _e('Batch скрейпването завърши успешно', 'parfume-catalog'); ?>');
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                });
            });
            
            // Scrape single post
            $('.scrape-post').on('click', function() {
                var button = $(this);
                var postId = button.data('post-id');
                
                button.prop('disabled', true).text('<?php _e('Скрейпва...', 'parfume-catalog'); ?>');
                
                $.post(ajaxurl, {
                    action: 'pc_scrape_post',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('pc_admin_nonce'); ?>'
                }, function(response) {
                    button.prop('disabled', false).text('<?php _e('Скрейпни сега', 'parfume-catalog'); ?>');
                    
                    if (response.success) {
                        alert('<?php _e('Скрейпването завърши успешно', 'parfume-catalog'); ?>');
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                });
            });
            
            // Clear log
            $('#clear-scraper-log').on('click', function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изчистите лога?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'pc_clear_scraper_log',
                    nonce: '<?php echo wp_create_nonce('pc_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Рендериране на scraper test tool страница
     */
    public function render_scraper_test_tool() {
        $stores = get_option('parfume_catalog_stores', array());
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        
        ?>
        <div class="wrap">
            <h1><?php _e('Scraper Test Tool', 'parfume-catalog'); ?></h1>
            
            <div class="pc-test-tool-container">
                <div class="test-url-section">
                    <h2><?php _e('Тест на URL', 'parfume-catalog'); ?></h2>
                    
                    <form id="test-url-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="test_url"><?php _e('Product URL', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="test_url" name="test_url" class="large-text" required>
                                    <p class="description"><?php _e('Въведете URL на продукт за анализ', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="<?php _e('Анализирай страницата', 'parfume-catalog'); ?>">
                        </p>
                    </form>
                </div>
                
                <div id="analysis-results" class="analysis-results" style="display: none;">
                    <h2><?php _e('Резултати от анализа', 'parfume-catalog'); ?></h2>
                    
                    <div id="found-data" class="found-data">
                        <!-- Results will be loaded here -->
                    </div>
                    
                    <div class="schema-builder">
                        <h3><?php _e('Изграждане на Schema', 'parfume-catalog'); ?></h3>
                        
                        <form id="schema-form">
                            <input type="hidden" id="store_id_for_schema" name="store_id" value="">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="price_selector"><?php _e('Селектор за цена', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="price_selector" name="price_selector" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="old_price_selector"><?php _e('Селектор за стара цена', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="old_price_selector" name="old_price_selector" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="ml_selector"><?php _e('Селектор за ML', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="ml_selector" name="ml_selector" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="availability_selector"><?php _e('Селектор за наличност', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="availability_selector" name="availability_selector" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="delivery_selector"><?php _e('Селектор за доставка', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="delivery_selector" name="delivery_selector" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="target_store"><?php _e('Целеви магазин', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <select id="target_store" name="target_store" required>
                                            <option value=""><?php _e('Избери магазин', 'parfume-catalog'); ?></option>
                                            <?php foreach ($stores as $store_id => $store): ?>
                                                <option value="<?php echo esc_attr($store_id); ?>">
                                                    <?php echo esc_html($store['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <input type="button" id="test-schema" class="button" value="<?php _e('Тествай Schema', 'parfume-catalog'); ?>">
                                <input type="submit" class="button button-primary" value="<?php _e('Запази Schema', 'parfume-catalog'); ?>">
                            </p>
                        </form>
                    </div>
                </div>
                
                <div class="existing-schemas">
                    <h2><?php _e('Съществуващи Schemas', 'parfume-catalog'); ?></h2>
                    
                    <?php if (empty($schemas)): ?>
                        <p><?php _e('Няма записани schemas', 'parfume-catalog'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Магазин', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Цена', 'parfume-catalog'); ?></th>
                                    <th><?php _e('ML', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Наличност', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schemas as $store_id => $schema): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $store_name = isset($stores[$store_id]['name']) ? $stores[$store_id]['name'] : $store_id;
                                            echo esc_html($store_name);
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($schema['price_selector']); ?></td>
                                        <td><?php echo esc_html($schema['ml_selector']); ?></td>
                                        <td><?php echo esc_html($schema['availability_selector']); ?></td>
                                        <td>
                                            <button type="button" class="button edit-schema" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Редактиране', 'parfume-catalog'); ?>
                                            </button>
                                            <button type="button" class="button button-danger delete-schema" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Изтриване', 'parfume-catalog'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Test URL form
            $('#test-url-form').on('submit', function(e) {
                e.preventDefault();
                
                var url = $('#test_url').val();
                if (!url) return;
                
                $.post(ajaxurl, {
                    action: 'pc_test_scraper',
                    url: url,
                    nonce: '<?php echo wp_create_nonce('pc_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        displayAnalysisResults(response.data);
                        $('#analysis-results').show();
                    } else {
                        alert(response.data);
                    }
                });
            });
            
            // Schema form
            $('#schema-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=pc_save_schema&nonce=<?php echo wp_create_nonce('pc_admin_nonce'); ?>';
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                });
            });
            
            // Test schema
            $('#test-schema').on('click', function() {
                var testUrl = $('#test_url').val();
                var selectors = {
                    price_selector: $('#price_selector').val(),
                    old_price_selector: $('#old_price_selector').val(),
                    ml_selector: $('#ml_selector').val(),
                    availability_selector: $('#availability_selector').val(),
                    delivery_selector: $('#delivery_selector').val()
                };
                
                // Test the schema with the current URL
                console.log('Testing schema:', selectors);
                alert('Schema тест функционалността ще бъде добавена скоро');
            });
            
            // Store selection
            $('#target_store').on('change', function() {
                $('#store_id_for_schema').val($(this).val());
            });
            
            function displayAnalysisResults(data) {
                var html = '<h4>Намерени елементи:</h4>';
                
                if (data.prices && data.prices.length > 0) {
                    html += '<h5>Възможни цени:</h5><ul>';
                    $.each(data.prices, function(index, item) {
                        html += '<li>';
                        html += '<strong>' + item.text + '</strong> ';
                        html += '<code>' + item.selector + '</code> ';
                        html += '<button type="button" class="button-small use-selector" data-field="price_selector" data-value="' + item.selector + '">Използвай</button>';
                        html += '</li>';
                    });
                    html += '</ul>';
                }
                
                if (data.ml_options && data.ml_options.length > 0) {
                    html += '<h5>Възможни ML опции:</h5><ul>';
                    $.each(data.ml_options, function(index, item) {
                        html += '<li>';
                        html += '<strong>' + item.text + '</strong> ';
                        html += '<code>' + item.selector + '</code> ';
                        html += '<button type="button" class="button-small use-selector" data-field="ml_selector" data-value="' + item.selector + '">Използвай</button>';
                        html += '</li>';
                    });
                    html += '</ul>';
                }
                
                $('#found-data').html(html);
            }
            
            // Use selector buttons
            $(document).on('click', '.use-selector', function() {
                var field = $(this).data('field');
                var value = $(this).data('value');
                $('#' + field).val(value);
            });
        });
        </script>
        <?php
    }
}