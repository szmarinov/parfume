<?php
/**
 * Parfume Catalog Scraper Module
 * 
 * Основен продуктов скрейпър за автоматично извличане на цени и данни
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Scraper {

    /**
     * Scraper статуси
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_MISSING_DATA = 'missing_data';

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('init', array($this, 'schedule_scraper_cron'));
        add_action('parfume_scraper_run', array($this, 'run_scraper_batch'));
        add_action('wp_ajax_parfume_manual_scrape', array($this, 'ajax_manual_scrape'));
        add_action('wp_ajax_parfume_test_single_url', array($this, 'ajax_test_single_url'));
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
        add_action('save_post', array($this, 'schedule_new_post_scrape'), 20);
    }

    /**
     * Добавяне на custom cron интервали
     */
    public function add_custom_cron_intervals($schedules) {
        $scraper_settings = get_option('parfume_catalog_scraper_settings', array());
        $interval_hours = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 12;
        
        $schedules['parfume_scraper_interval'] = array(
            'interval' => $interval_hours * 3600, // Конвертиране в секунди
            'display' => sprintf(__('На всеки %d часа', 'parfume-catalog'), $interval_hours)
        );
        
        return $schedules;
    }

    /**
     * Планиране на scraper cron job
     */
    public function schedule_scraper_cron() {
        if (!wp_next_scheduled('parfume_scraper_run')) {
            wp_schedule_event(time(), 'parfume_scraper_interval', 'parfume_scraper_run');
        }
    }

    /**
     * Стартиране на scraper batch
     */
    public function run_scraper_batch() {
        $scraper_settings = get_option('parfume_catalog_scraper_settings', array());
        $batch_size = isset($scraper_settings['batch_size']) ? $scraper_settings['batch_size'] : 10;
        
        // Получаване на URLs за скрейпване
        $urls_to_scrape = $this->get_pending_scrape_urls($batch_size);
        
        if (empty($urls_to_scrape)) {
            $this->log_scraper_activity('info', 'Няма URL-и за скрейпване в този batch.');
            return;
        }

        $this->log_scraper_activity('info', sprintf('Стартиране на scraper batch с %d URL-и.', count($urls_to_scrape)));

        foreach ($urls_to_scrape as $scrape_item) {
            $this->scrape_single_url($scrape_item);
            
            // Малка пауза между заявките за да не натоварваме сървъра
            sleep(1);
        }

        // Актуализиране на pointer за следващия batch
        $this->update_scraper_pointer($urls_to_scrape);
        
        $this->log_scraper_activity('info', 'Scraper batch завършен.');
    }

    /**
     * Получаване на pending URLs за скрейпване
     */
    private function get_pending_scrape_urls($limit = 10) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $current_time = current_time('mysql');
        
        // Търсене на URL-и които трябва да се обновят
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT sd.*, pm.meta_value as post_stores_data 
            FROM $scraper_table sd
            INNER JOIN {$wpdb->postmeta} pm ON sd.post_id = pm.post_id 
            WHERE pm.meta_key = '_parfume_stores'
            AND (
                sd.next_scrape IS NULL 
                OR sd.next_scrape <= %s
                OR sd.status = %s
            )
            AND sd.error_count < 5
            ORDER BY sd.next_scrape ASC, sd.last_scraped ASC
            LIMIT %d
        ", $current_time, self::STATUS_PENDING, $limit), ARRAY_A);

        $scrape_urls = array();
        
        foreach ($results as $result) {
            // Получаване на post stores данни
            $post_stores = maybe_unserialize($result['post_stores_data']);
            if (!is_array($post_stores) || !isset($post_stores[$result['store_id']])) {
                continue;
            }
            
            $store_data = $post_stores[$result['store_id']];
            if (empty($store_data['product_url'])) {
                continue;
            }
            
            $scrape_urls[] = array(
                'id' => $result['id'],
                'post_id' => $result['post_id'],
                'store_id' => $result['store_id'],
                'product_url' => $store_data['product_url'],
                'current_data' => $result
            );
        }

        return $scrape_urls;
    }

    /**
     * Скрейпване на единичен URL
     */
    private function scrape_single_url($scrape_item) {
        $post_id = $scrape_item['post_id'];
        $store_id = $scrape_item['store_id'];
        $product_url = $scrape_item['product_url'];
        
        $this->log_scraper_activity('info', sprintf('Скрейпване на URL: %s (Post: %d, Store: %s)', $product_url, $post_id, $store_id));

        try {
            // Получаване на store schema
            $store_info = Parfume_Catalog_Stores::get_store($store_id);
            if (!$store_info || empty($store_info['schema'])) {
                throw new Exception('Липсва schema за магазина');
            }

            // Проверка на robots.txt ако е включена
            if ($this->should_respect_robots() && !$this->check_robots_txt($product_url)) {
                $this->update_scraper_status($scrape_item['id'], self::STATUS_BLOCKED, 'Блокиран от robots.txt');
                return;
            }

            // Извличане на съдържанието на страницата
            $html_content = $this->fetch_page_content($product_url);
            if (!$html_content) {
                throw new Exception('Неуспешно зареждане на страницата');
            }

            // Парсиране на данните според schema
            $scraped_data = $this->parse_page_data($html_content, $store_info['schema']);
            
            // Валидиране на данните
            if (empty($scraped_data['price']) && empty($scraped_data['availability'])) {
                throw new Exception('Не са открити необходими данни');
            }

            // Запазване на резултата
            $this->save_scraped_data($scrape_item['id'], $scraped_data, self::STATUS_SUCCESS);
            
            $this->log_scraper_activity('success', sprintf('Успешно скрейпване на %s - Цена: %s', $product_url, $scraped_data['price'] ?? 'N/A'));

        } catch (Exception $e) {
            $this->handle_scraper_error($scrape_item['id'], $e->getMessage());
            $this->log_scraper_activity('error', sprintf('Грешка при скрейпване на %s: %s', $product_url, $e->getMessage()));
        }
    }

    /**
     * Извличане на съдържанието на страница
     */
    private function fetch_page_content($url) {
        $scraper_settings = get_option('parfume_catalog_scraper_settings', array());
        $user_agent = isset($scraper_settings['user_agent']) ? $scraper_settings['user_agent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $timeout = isset($scraper_settings['timeout']) ? $scraper_settings['timeout'] : 30;

        $args = array(
            'timeout' => $timeout,
            'user-agent' => $user_agent,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'bg-BG,bg;q=0.8,en;q=0.6',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            ),
            'sslverify' => false
        );

        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP грешка: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new Exception('HTTP статус код: ' . $response_code);
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new Exception('Празно съдържание на страницата');
        }

        return $body;
    }

    /**
     * Парсиране на данни от HTML според schema
     */
    private function parse_page_data($html_content, $schema) {
        // Създаване на DOMDocument за парсиране
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html_content, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $scraped_data = array();

        // Извличане на цена
        if (!empty($schema['price_selector'])) {
            $price_elements = $this->query_selector($xpath, $schema['price_selector']);
            if ($price_elements->length > 0) {
                $price_text = trim($price_elements->item(0)->textContent);
                $scraped_data['price'] = $this->clean_price($price_text);
            }
        }

        // Извличане на стара цена
        if (!empty($schema['old_price_selector'])) {
            $old_price_elements = $this->query_selector($xpath, $schema['old_price_selector']);
            if ($old_price_elements->length > 0) {
                $old_price_text = trim($old_price_elements->item(0)->textContent);
                $scraped_data['old_price'] = $this->clean_price($old_price_text);
            }
        }

        // Извличане на наличност
        if (!empty($schema['availability_selector'])) {
            $availability_elements = $this->query_selector($xpath, $schema['availability_selector']);
            if ($availability_elements->length > 0) {
                $availability_text = trim($availability_elements->item(0)->textContent);
                $scraped_data['availability'] = $this->clean_availability($availability_text);
            }
        }

        // Извличане на информация за доставка
        if (!empty($schema['delivery_selector'])) {
            $delivery_elements = $this->query_selector($xpath, $schema['delivery_selector']);
            if ($delivery_elements->length > 0) {
                $delivery_text = trim($delivery_elements->item(0)->textContent);
                $scraped_data['delivery_info'] = $this->clean_delivery_info($delivery_text);
            }
        }

        // Извличане на варианти (ml)
        if (!empty($schema['variants_selector'])) {
            $variants_elements = $this->query_selector($xpath, $schema['variants_selector']);
            $variants = array();
            
            foreach ($variants_elements as $variant_element) {
                $variant_text = trim($variant_element->textContent);
                $ml_value = $this->extract_ml_value($variant_text);
                if ($ml_value) {
                    $variants[] = array(
                        'ml' => $ml_value,
                        'text' => $variant_text,
                        'price' => $this->extract_variant_price($variant_element)
                    );
                }
            }
            
            if (!empty($variants)) {
                $scraped_data['variants'] = $variants;
            }
        }

        return $scraped_data;
    }

    /**
     * CSS селектор към XPath заявка
     */
    private function query_selector($xpath, $selector) {
        // Опростена конвертация от CSS към XPath
        $selector = trim($selector);
        
        // Клас селектор (.class-name)
        if (strpos($selector, '.') === 0) {
            $class_name = substr($selector, 1);
            return $xpath->query("//*[contains(@class, '$class_name')]");
        }
        
        // ID селектор (#id-name)
        if (strpos($selector, '#') === 0) {
            $id_name = substr($selector, 1);
            return $xpath->query("//*[@id='$id_name']");
        }
        
        // Tag селектор (div, span, etc.)
        if (ctype_alpha($selector)) {
            return $xpath->query("//$selector");
        }
        
        // Комбинирани селектори (.class span)
        if (strpos($selector, ' ') !== false) {
            $parts = explode(' ', $selector);
            $xpath_parts = array();
            
            foreach ($parts as $part) {
                if (strpos($part, '.') === 0) {
                    $class_name = substr($part, 1);
                    $xpath_parts[] = "*[contains(@class, '$class_name')]";
                } elseif (strpos($part, '#') === 0) {
                    $id_name = substr($part, 1);
                    $xpath_parts[] = "*[@id='$id_name']";
                } else {
                    $xpath_parts[] = $part;
                }
            }
            
            return $xpath->query('//' . implode('//', $xpath_parts));
        }
        
        // Fallback - опитване като XPath заявка
        return $xpath->query($selector);
    }

    /**
     * Почистване на цена
     */
    private function clean_price($price_text) {
        // Премахване на всички символи освен цифри, точки и запетайки
        $price = preg_replace('/[^\d.,]/', '', $price_text);
        
        // Конвертиране на запетайки в точки за decimal места
        if (substr_count($price, ',') === 1 && substr_count($price, '.') === 0) {
            $price = str_replace(',', '.', $price);
        }
        
        // Премахване на хиляди разделители
        if (substr_count($price, '.') > 1) {
            $parts = explode('.', $price);
            $decimal_part = array_pop($parts);
            $price = implode('', $parts) . '.' . $decimal_part;
        }
        
        return floatval($price);
    }

    /**
     * Почистване на наличност
     */
    private function clean_availability($availability_text) {
        $availability_text = strtolower(trim($availability_text));
        
        // Българските думи за наличност
        $available_keywords = array('наличен', 'в наличност', 'налично', 'available', 'in stock');
        $unavailable_keywords = array('няма в наличност', 'изчерпан', 'out of stock', 'unavailable');
        
        foreach ($available_keywords as $keyword) {
            if (strpos($availability_text, $keyword) !== false) {
                return 'Наличен';
            }
        }
        
        foreach ($unavailable_keywords as $keyword) {
            if (strpos($availability_text, $keyword) !== false) {
                return 'Няма в наличност';
            }
        }
        
        return $availability_text;
    }

    /**
     * Почистване на информация за доставка
     */
    private function clean_delivery_info($delivery_text) {
        $delivery_text = trim($delivery_text);
        
        // Почистване на излишни интервали и символи
        $delivery_text = preg_replace('/\s+/', ' ', $delivery_text);
        $delivery_text = str_replace(array("\n", "\r", "\t"), ' ', $delivery_text);
        
        return $delivery_text;
    }

    /**
     * Извличане на ml стойност от текст
     */
    private function extract_ml_value($text) {
        if (preg_match('/(\d+)\s*ml/i', $text, $matches)) {
            return intval($matches[1]);
        }
        return null;
    }

    /**
     * Извличане на цена за вариант
     */
    private function extract_variant_price($element) {
        // Търсене на цена в същия елемент или родителски елементи
        $price_text = $element->textContent;
        $price = $this->clean_price($price_text);
        
        if ($price > 0) {
            return $price;
        }
        
        // Търсене в родителски елементи
        $parent = $element->parentNode;
        while ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
            $price_text = $parent->textContent;
            $price = $this->clean_price($price_text);
            
            if ($price > 0) {
                return $price;
            }
            
            $parent = $parent->parentNode;
        }
        
        return null;
    }

    /**
     * Запазване на скрейпнати данни
     */
    private function save_scraped_data($scraper_id, $scraped_data, $status) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $scraper_settings = get_option('parfume_catalog_scraper_settings', array());
        $interval_hours = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 12;
        
        $current_time = current_time('mysql');
        $next_scrape = date('Y-m-d H:i:s', strtotime($current_time . ' +' . $interval_hours . ' hours'));
        
        $update_data = array(
            'price' => isset($scraped_data['price']) ? $scraped_data['price'] : null,
            'old_price' => isset($scraped_data['old_price']) ? $scraped_data['old_price'] : null,
            'variants' => isset($scraped_data['variants']) ? json_encode($scraped_data['variants']) : null,
            'availability' => isset($scraped_data['availability']) ? $scraped_data['availability'] : null,
            'delivery_info' => isset($scraped_data['delivery_info']) ? $scraped_data['delivery_info'] : null,
            'last_scraped' => $current_time,
            'next_scrape' => $next_scrape,
            'status' => $status,
            'error_count' => 0 // Нулиране на error count при успех
        );
        
        $wpdb->update(
            $scraper_table,
            $update_data,
            array('id' => $scraper_id),
            array('%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
            array('%d')
        );
    }

    /**
     * Обработка на scraper грешка
     */
    private function handle_scraper_error($scraper_id, $error_message) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        // Увеличаване на error count
        $current_data = $wpdb->get_row($wpdb->prepare(
            "SELECT error_count FROM $scraper_table WHERE id = %d",
            $scraper_id
        ), ARRAY_A);
        
        $error_count = ($current_data ? $current_data['error_count'] : 0) + 1;
        $status = $error_count >= 5 ? self::STATUS_BLOCKED : self::STATUS_ERROR;
        
        // Определяне на следващия опит
        $retry_intervals = array(1, 2, 4, 8, 24); // часове
        $retry_hours = isset($retry_intervals[$error_count - 1]) ? $retry_intervals[$error_count - 1] : 24;
        $next_scrape = date('Y-m-d H:i:s', strtotime(current_time('mysql') . ' +' . $retry_hours . ' hours'));
        
        $wpdb->update(
            $scraper_table,
            array(
                'status' => $status,
                'error_count' => $error_count,
                'next_scrape' => $next_scrape
            ),
            array('id' => $scraper_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
    }

    /**
     * Проверка на robots.txt
     */
    private function check_robots_txt($url) {
        $parsed_url = parse_url($url);
        if (!$parsed_url) {
            return true; // Ако не можем да парсираме URL-a, позволяваме скрейпването
        }
        
        $robots_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/robots.txt';
        
        $response = wp_remote_get($robots_url, array('timeout' => 10));
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return true; // Ако няма robots.txt, позволяваме скрейпването
        }
        
        $robots_content = wp_remote_retrieve_body($response);
        $user_agent = '*'; // Проверяваме за общ user agent
        
        // Опростена проверка на robots.txt
        $lines = explode("\n", $robots_content);
        $current_user_agent = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (strpos($line, 'User-agent:') === 0) {
                $ua = trim(substr($line, 11));
                $current_user_agent = ($ua === '*' || $ua === $user_agent);
            }
            
            if ($current_user_agent && strpos($line, 'Disallow:') === 0) {
                $disallowed_path = trim(substr($line, 9));
                if (!empty($disallowed_path) && strpos($parsed_url['path'], $disallowed_path) === 0) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Дали да се уважава robots.txt
     */
    private function should_respect_robots() {
        $scraper_settings = get_option('parfume_catalog_scraper_settings', array());
        return isset($scraper_settings['respect_robots']) && $scraper_settings['respect_robots'];
    }

    /**
     * Актуализиране на scraper pointer
     */
    private function update_scraper_pointer($processed_items) {
        // Тази функция може да се използва за проследяване на прогреса
        $last_processed_id = 0;
        foreach ($processed_items as $item) {
            if ($item['id'] > $last_processed_id) {
                $last_processed_id = $item['id'];
            }
        }
        
        update_option('parfume_scraper_last_processed_id', $last_processed_id);
    }

    /**
     * Планиране на скрейпване за нов пост
     */
    public function schedule_new_post_scrape($post_id) {
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($post_stores) || empty($post_stores)) {
            return;
        }
        
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        foreach ($post_stores as $store_id => $store_data) {
            if (empty($store_data['product_url'])) {
                continue;
            }
            
            // Проверка дали вече съществува запис
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $scraper_table WHERE post_id = %d AND store_id = %s",
                $post_id, $store_id
            ));
            
            if (!$existing) {
                // Добавяне на нов запис за скрейпване
                $wpdb->insert(
                    $scraper_table,
                    array(
                        'post_id' => $post_id,
                        'store_id' => $store_id,
                        'product_url' => $store_data['product_url'],
                        'status' => self::STATUS_PENDING,
                        'next_scrape' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s', '%s')
                );
            }
        }
    }

    /**
     * AJAX - Ръчно скрейпване
     */
    public function ajax_manual_scrape() {
        check_ajax_referer('parfume_catalog_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Получаване на scraper запис
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $scraper_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $scraper_table WHERE post_id = %d AND store_id = %s",
            $post_id, $store_id
        ), ARRAY_A);
        
        if (!$scraper_record) {
            wp_send_json_error(__('Scraper записът не е намерен.', 'parfume-catalog'));
        }
        
        // Получаване на product URL
        $post_stores = get_post_meta($post_id, '_parfume_stores', true);
        if (!isset($post_stores[$store_id]['product_url'])) {
            wp_send_json_error(__('Product URL не е намерен.', 'parfume-catalog'));
        }
        
        $scrape_item = array(
            'id' => $scraper_record['id'],
            'post_id' => $post_id,
            'store_id' => $store_id,
            'product_url' => $post_stores[$store_id]['product_url'],
            'current_data' => $scraper_record
        );
        
        // Изпълнение на скрейпването
        $this->scrape_single_url($scrape_item);
        
        // Получаване на актуализираните данни
        $updated_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $scraper_table WHERE id = %d",
            $scraper_record['id']
        ), ARRAY_A);
        
        if ($updated_record['status'] === self::STATUS_SUCCESS) {
            wp_send_json_success(array(
                'message' => __('Скрейпването е успешно.', 'parfume-catalog'),
                'data' => $updated_record
            ));
        } else {
            wp_send_json_error(__('Скрейпването не е успешно. Проверете логовете за повече информация.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Тестване на единичен URL
     */
    public function ajax_test_single_url() {
        check_ajax_referer('parfume_catalog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }
        
        $test_url = esc_url_raw($_POST['test_url']);
        $schema = array(
            'price_selector' => sanitize_text_field($_POST['price_selector']),
            'old_price_selector' => sanitize_text_field($_POST['old_price_selector']),
            'availability_selector' => sanitize_text_field($_POST['availability_selector']),
            'delivery_selector' => sanitize_text_field($_POST['delivery_selector']),
            'variants_selector' => sanitize_text_field($_POST['variants_selector'])
        );
        
        try {
            $html_content = $this->fetch_page_content($test_url);
            $scraped_data = $this->parse_page_data($html_content, $schema);
            
            wp_send_json_success(array(
                'message' => __('Тестът е успешен.', 'parfume-catalog'),
                'data' => $scraped_data,
                'url' => $test_url
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'url' => $test_url
            ));
        }
    }

    /**
     * Логиране на scraper активност
     */
    private function log_scraper_activity($level, $message) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message
        );
        
        // Получаване на текущия лог
        $scraper_log = get_option('parfume_scraper_log', array());
        
        // Добавяне на новия запис
        array_unshift($scraper_log, $log_entry);
        
        // Ограничаване до последните 100 записа
        $scraper_log = array_slice($scraper_log, 0, 100);
        
        // Запазване на лога
        update_option('parfume_scraper_log', $scraper_log);
    }

    /**
     * Получаване на scraper лог
     */
    public static function get_scraper_log($limit = 50) {
        $log = get_option('parfume_scraper_log', array());
        return array_slice($log, 0, $limit);
    }

    /**
     * Изчистване на scraper лог
     */
    public static function clear_scraper_log() {
        delete_option('parfume_scraper_log');
    }

    /**
     * Получаване на scraper статистики
     */
    public static function get_scraper_stats() {
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $stats = array();
        
        // Общ брой записи
        $stats['total_urls'] = $wpdb->get_var("SELECT COUNT(*) FROM $scraper_table");
        
        // Брой по статус
        $status_counts = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $scraper_table 
            GROUP BY status
        ", ARRAY_A);
        
        foreach ($status_counts as $row) {
            $stats['status_' . $row['status']] = $row['count'];
        }
        
        // Последно скрейпване
        $last_scraped = $wpdb->get_var("
            SELECT MAX(last_scraped) 
            FROM $scraper_table 
            WHERE status = 'success'
        ");
        $stats['last_successful_scrape'] = $last_scraped;
        
        // Следващо планирано скрейпване
        $next_scrape = $wpdb->get_var("
            SELECT MIN(next_scrape) 
            FROM $scraper_table 
            WHERE next_scrape IS NOT NULL
        ");
        $stats['next_scheduled_scrape'] = $next_scrape;
        
        return $stats;
    }
}