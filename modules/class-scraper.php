<?php
/**
 * Parfume Catalog Scraper Module
 * 
 * Основен клас за скрейпване на продуктова информация от магазини
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Scraper {

    // Константи за статус
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_MISSING_DATA = 'missing_data';

    // Константи за логове
    const LOG_INFO = 'info';
    const LOG_SUCCESS = 'success';
    const LOG_ERROR = 'error';
    const LOG_WARNING = 'warning';

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('wp', array($this, 'init_cron'));
        add_action('parfume_scraper_cron', array($this, 'run_batch_scraper'));
        add_action('save_post', array($this, 'schedule_post_scraping'));
        add_action('wp_ajax_parfume_manual_scrape', array($this, 'ajax_manual_scrape'));
        add_action('wp_ajax_parfume_test_scraper_url', array($this, 'ajax_test_single_url'));
        add_action('wp_ajax_parfume_get_scraper_stats', array($this, 'ajax_get_scraper_stats'));
        add_action('admin_init', array($this, 'create_scraper_tables'));
    }

    /**
     * Създаване на необходимите таблици
     */
    public function create_scraper_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Таблица за scraper данни
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $sql_scraper = "CREATE TABLE $scraper_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            store_id varchar(50) NOT NULL,
            product_url text NOT NULL,
            scraped_data longtext,
            status varchar(20) DEFAULT 'pending',
            error_message text,
            retry_count int(11) DEFAULT 0,
            last_scraped datetime,
            next_scrape datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_store (post_id, store_id),
            KEY status (status),
            KEY next_scrape (next_scrape)
        ) $charset_collate;";

        // Таблица за scraper логове
        $log_table = $wpdb->prefix . 'parfume_scraper_log';
        $sql_log = "CREATE TABLE $log_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            scraper_id mediumint(9),
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at),
            KEY scraper_id (scraper_id)
        ) $charset_collate;";

        // Таблица за scraper queue
        $queue_table = $wpdb->prefix . 'parfume_scraper_queue';
        $sql_queue = "CREATE TABLE $queue_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            scraper_data_id mediumint(9) NOT NULL,
            priority int(11) DEFAULT 0,
            attempts int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            scheduled_for datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY scraper_data_id (scraper_data_id),
            KEY status (status),
            KEY scheduled_for (scheduled_for),
            KEY priority (priority)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_scraper);
        dbDelta($sql_log);
        dbDelta($sql_queue);
    }

    /**
     * Инициализация на cron job
     */
    public function init_cron() {
        if (!wp_next_scheduled('parfume_scraper_cron')) {
            $settings = get_option('parfume_catalog_scraper_settings', array());
            $interval = isset($settings['interval']) ? $settings['interval'] : 12; // часове
            
            wp_schedule_event(time(), 'parfume_scraper_interval', 'parfume_scraper_cron');
        }
    }

    /**
     * Batch scraper - основна функция за автоматично скрейпване
     */
    public function run_batch_scraper() {
        $this->log_scraper_activity(self::LOG_INFO, 'Стартиране на batch scraper');

        $settings = get_option('parfume_catalog_scraper_settings', array());
        $batch_size = isset($settings['batch_size']) ? intval($settings['batch_size']) : 10;

        // Получаване на URL-и за скрейпване
        $scrape_urls = $this->get_pending_scrape_urls($batch_size);

        if (empty($scrape_urls)) {
            $this->log_scraper_activity(self::LOG_INFO, 'Няма URL-и за скрейпване');
            return;
        }

        $processed = 0;
        $successful = 0;
        $failed = 0;

        foreach ($scrape_urls as $scrape_item) {
            $this->scrape_single_url($scrape_item);
            $processed++;

            // Проверка на статуса след скрейпване
            $status = $this->get_scraper_status($scrape_item['id']);
            if ($status === self::STATUS_SUCCESS) {
                $successful++;
            } elseif ($status === self::STATUS_ERROR) {
                $failed++;
            }

            // Малка пауза между заявките
            sleep(1);
        }

        $this->log_scraper_activity(self::LOG_INFO, sprintf(
            'Batch scraper завършен. Обработени: %d, Успешни: %d, Неуспешни: %d',
            $processed, $successful, $failed
        ));
    }

    /**
     * Получаване на pending URL-и за скрейпване
     */
    private function get_pending_scrape_urls($limit = 10) {
        global $wpdb;

        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $current_time = current_time('mysql');

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT sd.*, pm.meta_value as stores_data
            FROM $scraper_table sd
            INNER JOIN {$wpdb->postmeta} pm ON sd.post_id = pm.post_id
            WHERE sd.status IN (%s, %s, %s)
            AND (sd.next_scrape IS NULL OR sd.next_scrape <= %s)
            AND sd.retry_count < 3
            AND pm.meta_key = '_parfume_stores'
            ORDER BY sd.priority DESC, sd.next_scrape ASC
            LIMIT %d
        ", self::STATUS_PENDING, self::STATUS_ERROR, self::STATUS_MISSING_DATA, $current_time, $limit), ARRAY_A);

        $scrape_urls = array();

        foreach ($results as $result) {
            $stores_data = maybe_unserialize($result['stores_data']);
            
            if (isset($stores_data[$result['store_id']]['product_url'])) {
                $scrape_urls[] = array(
                    'id' => $result['id'],
                    'post_id' => $result['post_id'],
                    'store_id' => $result['store_id'],
                    'product_url' => $stores_data[$result['store_id']]['product_url'],
                    'current_data' => $result
                );
            }
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
        
        $this->log_scraper_activity(self::LOG_INFO, sprintf(
            'Скрейпване на URL: %s (Post: %d, Store: %s)', 
            $product_url, $post_id, $store_id
        ), $scrape_item['id']);

        try {
            // Получаване на store schema
            $store_info = Parfume_Catalog_Stores::get_store($store_id);
            if (!$store_info) {
                throw new Exception('Магазинът не е намерен');
            }

            $schemas = get_option('parfume_catalog_scraper_schemas', array());
            $schema = isset($schemas[$store_id]) ? $schemas[$store_id] : array();
            
            if (empty($schema)) {
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
            $scraped_data = $this->parse_page_data($html_content, $schema);
            
            // Валидиране на данните
            if (empty($scraped_data['price']) && empty($scraped_data['availability'])) {
                throw new Exception('Не са открити необходими данни');
            }

            // Запазване на резултата
            $this->save_scraped_data($scrape_item['id'], $scraped_data, self::STATUS_SUCCESS);
            
            $this->log_scraper_activity(self::LOG_SUCCESS, sprintf(
                'Успешно скрейпване на %s - Цена: %s', 
                $product_url, 
                $scraped_data['price'] ?? 'N/A'
            ), $scrape_item['id']);

        } catch (Exception $e) {
            $this->handle_scraper_error($scrape_item['id'], $e->getMessage());
            $this->log_scraper_activity(self::LOG_ERROR, sprintf(
                'Грешка при скрейпване на %s: %s', 
                $product_url, 
                $e->getMessage()
            ), $scrape_item['id']);
        }
    }

    /**
     * Извличане на съдържанието на страница
     */
    private function fetch_page_content($url) {
        $scraper_settings = get_option('parfume_catalog_scraper_settings', array());
        $user_agent = isset($scraper_settings['user_agent']) ? 
            $scraper_settings['user_agent'] : 
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $timeout = isset($scraper_settings['timeout']) ? intval($scraper_settings['timeout']) : 30;

        $args = array(
            'user-agent' => $user_agent,
            'timeout' => $timeout,
            'sslverify' => false,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'bg-BG,bg;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            )
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('HTTP заявката не е успешна: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new Exception('HTTP грешка: ' . $response_code);
        }

        $content = wp_remote_retrieve_body($response);
        
        if (empty($content)) {
            throw new Exception('Празно съдържание на страницата');
        }

        return $content;
    }

    /**
     * Парсиране на данните от страницата според schema
     */
    private function parse_page_data($html_content, $schema) {
        // Създаване на DOMDocument
        $dom = new DOMDocument();
        
        // Заглушаване на HTML грешки
        libxml_use_internal_errors(true);
        
        // Зареждане на HTML със UTF-8 encoding
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html_content);
        
        // Възстановяване на error reporting
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $scraped_data = array();

        // Извличане на основната цена
        if (!empty($schema['price_selector'])) {
            $price_elements = $this->query_selector($xpath, $schema['price_selector']);
            if ($price_elements->length > 0) {
                $price_text = trim($price_elements->item(0)->textContent);
                $scraped_data['price'] = $this->clean_price($price_text);
            }
        }

        // Извличане на старата цена
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
        if (!empty($schema['ml_selector'])) {
            $variants_elements = $this->query_selector($xpath, $schema['ml_selector']);
            $variants = array();
            
            foreach ($variants_elements as $variant_element) {
                $variant_text = trim($variant_element->textContent);
                $ml_value = $this->extract_ml_value($variant_text);
                if ($ml_value) {
                    $variant_data = array(
                        'ml' => $ml_value,
                        'text' => $variant_text
                    );
                    
                    // Опит за извличане на цена за конкретния вариант
                    $variant_price = $this->extract_variant_price($variant_element);
                    if ($variant_price) {
                        $variant_data['price'] = $variant_price;
                    }
                    
                    $variants[] = $variant_data;
                }
            }
            
            if (!empty($variants)) {
                $scraped_data['ml_options'] = $variants;
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
        
        // Атрибут селектор ([attribute=value])
        if (preg_match('/\[([^=]+)=([^\]]+)\]/', $selector, $matches)) {
            $attr = trim($matches[1]);
            $value = trim($matches[2], '"\'');
            return $xpath->query("//*[@$attr='$value']");
        }
        
        // Комбинирани селектори (.class span)
        if (strpos($selector, ' ') !== false) {
            $parts = explode(' ', trim($selector));
            $xpath_parts = array();
            
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) continue;
                
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
        
        // Direct child selector (.parent > .child)
        if (strpos($selector, '>') !== false) {
            $parts = array_map('trim', explode('>', $selector));
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
            
            return $xpath->query('//' . implode('/', $xpath_parts));
        }
        
        // Fallback - опитване като XPath заявка
        try {
            return $xpath->query($selector);
        } catch (Exception $e) {
            return $xpath->query("//*[contains(text(), '$selector')]");
        }
    }

    /**
     * Почистване на цена
     */
    private function clean_price($price_text) {
        if (empty($price_text)) {
            return 0;
        }
        
        // Премахване на всички символи освен цифри, точки и запетайки
        $price = preg_replace('/[^\d.,]/', '', $price_text);
        
        if (empty($price)) {
            return 0;
        }
        
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
        $available_keywords = array('наличен', 'в наличност', 'налично', 'available', 'in stock', 'на склад');
        $unavailable_keywords = array('няма в наличност', 'изчерпан', 'out of stock', 'unavailable', 'не е наличен');
        
        foreach ($available_keywords as $keyword) {
            if (strpos($availability_text, $keyword) !== false) {
                return 'наличен';
            }
        }
        
        foreach ($unavailable_keywords as $keyword) {
            if (strpos($availability_text, $keyword) !== false) {
                return 'няма в наличност';
            }
        }
        
        // Ако не може да се определи, връщаме оригиналния текст
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
        
        // Определяне на безплатна доставка
        $free_delivery_keywords = array('безплатна доставка', 'free shipping', 'free delivery', 'без такса доставка');
        
        $lower_text = strtolower($delivery_text);
        foreach ($free_delivery_keywords as $keyword) {
            if (strpos($lower_text, $keyword) !== false) {
                return 'безплатна доставка';
            }
        }
        
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
        // Търсене на цена в същия елемент
        $price_text = $element->textContent;
        $price = $this->clean_price($price_text);
        
        if ($price > 0) {
            return $price;
        }
        
        // Търсене в родителски елементи
        $parent = $element->parentNode;
        $levels = 0;
        
        while ($parent && $parent->nodeType === XML_ELEMENT_NODE && $levels < 3) {
            $price_text = $parent->textContent;
            $price = $this->clean_price($price_text);
            
            if ($price > 0) {
                return $price;
            }
            
            $parent = $parent->parentNode;
            $levels++;
        }
        
        // Търсене в съседни елементи
        $sibling = $element->nextSibling;
        while ($sibling) {
            if ($sibling->nodeType === XML_ELEMENT_NODE) {
                $price_text = $sibling->textContent;
                $price = $this->clean_price($price_text);
                
                if ($price > 0) {
                    return $price;
                }
            }
            $sibling = $sibling->nextSibling;
        }
        
        return null;
    }

    /**
     * Запазване на скрейпнати данни
     */
    private function save_scraped_data($scraper_id, $scraped_data, $status) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $settings = get_option('parfume_catalog_scraper_settings', array());
        $interval_hours = isset($settings['interval']) ? intval($settings['interval']) : 12;
        
        $next_scrape = date('Y-m-d H:i:s', strtotime("+$interval_hours hours"));
        
        $wpdb->update(
            $scraper_table,
            array(
                'scraped_data' => wp_json_encode($scraped_data),
                'status' => $status,
                'error_message' => null,
                'retry_count' => 0,
                'last_scraped' => current_time('mysql'),
                'next_scrape' => $next_scrape
            ),
            array('id' => $scraper_id),
            array('%s', '%s', '%s', '%d', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Обработка на грешки при скрейпване
     */
    private function handle_scraper_error($scraper_id, $error_message) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        // Увеличаване на retry count
        $current_data = $wpdb->get_row($wpdb->prepare(
            "SELECT retry_count FROM $scraper_table WHERE id = %d",
            $scraper_id
        ));
        
        $retry_count = $current_data ? intval($current_data->retry_count) + 1 : 1;
        $max_retries = 3;
        
        // Определяне на статус и следващ опит
        if ($retry_count >= $max_retries) {
            $status = self::STATUS_ERROR;
            $next_scrape = date('Y-m-d H:i:s', strtotime('+24 hours')); // Опитвай отново след 24 часа
        } else {
            $status = self::STATUS_PENDING;
            $retry_delay = pow(2, $retry_count) * 60; // Exponential backoff в минути
            $next_scrape = date('Y-m-d H:i:s', strtotime("+$retry_delay minutes"));
        }
        
        $wpdb->update(
            $scraper_table,
            array(
                'status' => $status,
                'error_message' => $error_message,
                'retry_count' => $retry_count,
                'next_scrape' => $next_scrape
            ),
            array('id' => $scraper_id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
    }

    /**
     * Обновяване на статус на scraper
     */
    private function update_scraper_status($scraper_id, $status, $error_message = null) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $update_data = array(
            'status' => $status
        );
        
        if ($error_message) {
            $update_data['error_message'] = $error_message;
        }
        
        $wpdb->update(
            $scraper_table,
            $update_data,
            array('id' => $scraper_id),
            array('%s', '%s'),
            array('%d')
        );
    }

    /**
     * Получаване на статус на scraper
     */
    private function get_scraper_status($scraper_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $scraper_table WHERE id = %d",
            $scraper_id
        ));
    }

    /**
     * Проверка дали да се спазва robots.txt
     */
    private function should_respect_robots() {
        $settings = get_option('parfume_catalog_scraper_settings', array());
        return isset($settings['respect_robots']) ? (bool) $settings['respect_robots'] : true;
    }

    /**
     * Проверка на robots.txt
     */
    private function check_robots_txt($url) {
        $parsed_url = parse_url($url);
        $robots_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/robots.txt';
        
        $response = wp_remote_get($robots_url, array('timeout' => 10));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return true; // Ако няма robots.txt, разрешаваме скрейпването
        }
        
        $robots_content = wp_remote_retrieve_body($response);
        
        // Опростена проверка - търсим "Disallow: /"
        if (strpos($robots_content, 'Disallow: /') !== false) {
            return false;
        }
        
        return true;
    }

    /**
     * Планиране на скрейпване за пост
     */
    public function schedule_post_scraping($post_id) {
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
            } else {
                // Обновяване на съществуващ запис
                $wpdb->update(
                    $scraper_table,
                    array(
                        'product_url' => $store_data['product_url'],
                        'status' => self::STATUS_PENDING,
                        'next_scrape' => current_time('mysql')
                    ),
                    array('id' => $existing),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            }
        }
    }

    /**
     * AJAX - Ръчно скрейпване
     */
    public function ajax_manual_scrape() {
        check_ajax_referer('parfume_catalog_admin_nonce', 'nonce');
        
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
            $scraped_data = json_decode($updated_record['scraped_data'], true);
            wp_send_json_success(array(
                'message' => __('Скрейпването е успешно.', 'parfume-catalog'),
                'data' => $scraped_data,
                'last_scraped' => $updated_record['last_scraped']
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Скрейпването не е успешно.', 'parfume-catalog'),
                'error' => $updated_record['error_message']
            ));
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
            'ml_selector' => sanitize_text_field($_POST['ml_selector'])
        );
        
        try {
            $html_content = $this->fetch_page_content($test_url);
            $scraped_data = $this->parse_page_data($html_content, $schema);
            
            wp_send_json_success(array(
                'message' => __('Тестът е успешен.', 'parfume-catalog'),
                'scraped_data' => $scraped_data,
                'url' => $test_url
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Тестът е неуспешен.', 'parfume-catalog'),
                'error' => $e->getMessage(),
                'url' => $test_url
            ));
        }
    }

    /**
     * AJAX - Получаване на scraper статистики
     */
    public function ajax_get_scraper_stats() {
        check_ajax_referer('parfume_catalog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }
        
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $stats = array();
        
        // Общ брой записи
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $scraper_table");
        
        // Брой по статуси
        $status_counts = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $scraper_table 
            GROUP BY status
        ");
        
        foreach ($status_counts as $status_count) {
            $stats['by_status'][$status_count->status] = intval($status_count->count);
        }
        
        // Последно успешно скрейпване
        $last_success = $wpdb->get_var("
            SELECT last_scraped 
            FROM $scraper_table 
            WHERE status = 'success' 
            ORDER BY last_scraped DESC 
            LIMIT 1
        ");
        
        $stats['last_success'] = $last_success;
        
        // Брой неуспешни опити от последните 24 часа
        $recent_errors = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $scraper_table 
            WHERE status = 'error' 
            AND updated_at >= %s
        ", date('Y-m-d H:i:s', strtotime('-24 hours'))));
        
        $stats['recent_errors'] = intval($recent_errors);
        
        wp_send_json_success($stats);
    }

    /**
     * Логване на scraper активност
     */
    private function log_scraper_activity($level, $message, $scraper_id = null, $context = array()) {
        global $wpdb;
        
        $log_table = $wpdb->prefix . 'parfume_scraper_log';
        
        $wpdb->insert(
            $log_table,
            array(
                'scraper_id' => $scraper_id,
                'level' => $level,
                'message' => $message,
                'context' => wp_json_encode($context)
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        // Почистване на стари логове (пазим само последните 1000)
        $log_count = $wpdb->get_var("SELECT COUNT(*) FROM $log_table");
        if ($log_count > 1000) {
            $wpdb->query("
                DELETE FROM $log_table 
                WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM $log_table 
                        ORDER BY created_at DESC 
                        LIMIT 1000
                    ) AS t
                )
            ");
        }
    }

    /**
     * Публични помощни методи
     */

    /**
     * Получаване на скрейпнати данни за пост и магазин
     */
    public function get_scraped_data($post_id, $store_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $scraper_table WHERE post_id = %d AND store_id = %s",
            $post_id, $store_id
        ), ARRAY_A);
        
        if (!$record) {
            return false;
        }
        
        $result = array(
            'status' => $record['status'],
            'last_scraped' => $record['last_scraped'],
            'next_scrape' => $record['next_scrape'],
            'error_message' => $record['error_message'],
            'data' => array()
        );
        
        if (!empty($record['scraped_data'])) {
            $result['data'] = json_decode($record['scraped_data'], true);
        }
        
        return $result;
    }

    /**
     * Тестване на URL с конкретна schema
     */
    public function test_url($url, $store_id) {
        try {
            $schemas = get_option('parfume_catalog_scraper_schemas', array());
            $schema = isset($schemas[$store_id]) ? $schemas[$store_id] : array();
            
            if (empty($schema)) {
                throw new Exception('Липсва schema за този магазин');
            }
            
            $html_content = $this->fetch_page_content($url);
            $scraped_data = $this->parse_page_data($html_content, $schema);
            
            return array(
                'success' => true,
                'data' => $scraped_data
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Получаване на всички scraper логове
     */
    public function get_scraper_logs($limit = 100, $level = null) {
        global $wpdb;
        
        $log_table = $wpdb->prefix . 'parfume_scraper_log';
        
        $where = '';
        if ($level) {
            $where = $wpdb->prepare(" WHERE level = %s", $level);
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $log_table $where ORDER BY created_at DESC LIMIT %d",
            $limit
        ), ARRAY_A);
    }

    /**
     * Деактивиране на scraper
     */
    public static function deactivate_scraper() {
        wp_clear_scheduled_hook('parfume_scraper_cron');
    }
}

// Initialize the scraper module
new Parfume_Catalog_Scraper();