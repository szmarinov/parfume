<?php
/**
 * Parfume Catalog Scraper
 * 
 * Автоматично скрейпване на данни от магазини
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Scraper {
    
    // Статуси за scraper записи
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_BLOCKED = 'blocked';
    
    // Log levels
    const LOG_INFO = 'info';
    const LOG_SUCCESS = 'success';
    const LOG_WARNING = 'warning';
    const LOG_ERROR = 'error';
    const LOG_DEBUG = 'debug';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('parfume_scraper_cron', array($this, 'run_scheduled_scraper'));
        
        // AJAX hooks
        add_action('wp_ajax_pc_manual_scrape', array($this, 'ajax_manual_scrape'));
        add_action('wp_ajax_pc_test_single_url', array($this, 'ajax_test_single_url'));
        add_action('wp_ajax_pc_get_scraper_stats', array($this, 'ajax_get_scraper_stats'));
    }
    
    /**
     * Инициализация
     */
    public function init() {
        $this->create_scraper_tables();
        $this->schedule_scraper_if_needed();
    }
    
    /**
     * Създаване на необходимите таблици
     */
    public function create_scraper_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица за scraper данни
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $scraper_sql = "CREATE TABLE $scraper_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            store_id int(11) NOT NULL,
            product_url varchar(500) NOT NULL,
            scraped_data longtext,
            status varchar(20) DEFAULT 'pending',
            error_message text,
            retry_count int(11) DEFAULT 0,
            last_scraped datetime,
            next_scrape datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY store_id (store_id),
            KEY status (status),
            KEY next_scrape (next_scrape)
        ) $charset_collate;";
        
        // Таблица за scraper логове
        $logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            scraper_id int(11),
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY scraper_id (scraper_id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($scraper_sql);
        dbDelta($logs_sql);
    }
    
    /**
     * Планиране на scraper ако е нужно
     */
    private function schedule_scraper_if_needed() {
        $settings = get_option('parfume_catalog_scraper_settings', array());
        
        if (!empty($settings['enabled']) && !wp_next_scheduled('parfume_scraper_cron')) {
            wp_schedule_event(time(), 'hourly', 'parfume_scraper_cron');
        }
    }
    
    /**
     * Стартиране на scheduled scraper
     */
    public function run_scheduled_scraper() {
        $settings = get_option('parfume_catalog_scraper_settings', array());
        
        if (empty($settings['enabled'])) {
            return;
        }
        
        $this->log_scraper_activity(self::LOG_INFO, 'Стартиране на scheduled scraper');
        
        try {
            $batch_size = isset($settings['batch_size']) ? intval($settings['batch_size']) : 10;
            $processed = $this->process_scraper_batch($batch_size);
            
            $this->log_scraper_activity(self::LOG_SUCCESS, sprintf(
                'Scheduled scraper завърши - обработени %d записа', 
                $processed
            ));
            
        } catch (Exception $e) {
            $this->log_scraper_activity(self::LOG_ERROR, sprintf(
                'Грешка в scheduled scraper: %s', 
                $e->getMessage()
            ));
        }
    }
    
    /**
     * Ръчно стартиране на scraper
     */
    public function run_manual_scraper() {
        $this->log_scraper_activity(self::LOG_INFO, 'Стартиране на manual scraper');
        
        try {
            $settings = get_option('parfume_catalog_scraper_settings', array());
            $batch_size = isset($settings['batch_size']) ? intval($settings['batch_size']) : 50; // По-голям batch за manual
            
            $processed = $this->process_scraper_batch($batch_size);
            
            $this->log_scraper_activity(self::LOG_SUCCESS, sprintf(
                'Manual scraper завърши - обработени %d записа', 
                $processed
            ));
            
            return array(
                'success' => true,
                'processed' => $processed,
                'message' => sprintf(__('Обработени %d записа', 'parfume-catalog'), $processed)
            );
            
        } catch (Exception $e) {
            $this->log_scraper_activity(self::LOG_ERROR, sprintf(
                'Грешка в manual scraper: %s', 
                $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Стартиране на scraper само за неуспешни записи
     */
    public function run_failed_scraper() {
        $this->log_scraper_activity(self::LOG_INFO, 'Стартиране на failed scraper');
        
        try {
            global $wpdb;
            $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
            
            // Reset статуса на неуспешни записи
            $reset_count = $wpdb->update(
                $scraper_table,
                array(
                    'status' => self::STATUS_PENDING,
                    'retry_count' => 0,
                    'error_message' => null,
                    'next_scrape' => current_time('mysql')
                ),
                array('status' => self::STATUS_ERROR),
                array('%s', '%d', '%s', '%s'),
                array('%s')
            );
            
            $this->log_scraper_activity(self::LOG_INFO, sprintf(
                'Рестартирани %d неуспешни записа', 
                $reset_count
            ));
            
            // Обработване на рестартираните записи
            $settings = get_option('parfume_catalog_scraper_settings', array());
            $batch_size = isset($settings['batch_size']) ? intval($settings['batch_size']) : 20;
            
            $processed = $this->process_scraper_batch($batch_size);
            
            $this->log_scraper_activity(self::LOG_SUCCESS, sprintf(
                'Failed scraper завърши - обработени %d записа', 
                $processed
            ));
            
            return array(
                'success' => true,
                'reset' => $reset_count,
                'processed' => $processed,
                'message' => sprintf(__('Рестартирани %d записа, обработени %d', 'parfume-catalog'), $reset_count, $processed)
            );
            
        } catch (Exception $e) {
            $this->log_scraper_activity(self::LOG_ERROR, sprintf(
                'Грешка в failed scraper: %s', 
                $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Обработване на партида записи за скрейпване
     */
    private function process_scraper_batch($batch_size = 10) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        // Получаване на записи за обработване
        $scrape_items = $wpdb->get_results($wpdb->prepare("
            SELECT id, post_id, store_id, product_url 
            FROM $scraper_table 
            WHERE status = %s AND (next_scrape IS NULL OR next_scrape <= %s)
            ORDER BY created_at ASC 
            LIMIT %d
        ", self::STATUS_PENDING, current_time('mysql'), $batch_size), ARRAY_A);
        
        if (empty($scrape_items)) {
            return 0;
        }
        
        $processed = 0;
        $stores_schemas = get_option('parfume_catalog_stores_schemas', array());
        
        foreach ($scrape_items as $scrape_item) {
            $this->process_single_scrape($scrape_item, $stores_schemas);
            $processed++;
            
            // Малка пауза между заявките за да не натоварим сървъра
            usleep(500000); // 0.5 секунди
        }
        
        return $processed;
    }
    
    /**
     * Обработване на единичен scrape
     */
    private function process_single_scrape($scrape_item, $stores_schemas) {
        $product_url = $scrape_item['product_url'];
        $store_id = $scrape_item['store_id'];
        
        try {
            // Проверка за валиден URL
            if (!filter_var($product_url, FILTER_VALIDATE_URL)) {
                throw new Exception('Невалиден URL');
            }
            
            // Получаване на schema за магазина
            $schema = isset($stores_schemas[$store_id]) ? $stores_schemas[$store_id] : array();
            
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

        // Извличане на варианти (мл)
        if (!empty($schema['variants_selector'])) {
            $variants_elements = $this->query_selector($xpath, $schema['variants_selector']);
            $variants = array();
            
            for ($i = 0; $i < $variants_elements->length; $i++) {
                $variant_text = trim($variants_elements->item($i)->textContent);
                $variant_data = $this->extract_variant_data($variant_text);
                if ($variant_data) {
                    $variants[] = $variant_data;
                }
            }
            
            if (!empty($variants)) {
                $scraped_data['variants'] = $variants;
            }
        }

        // Извличане на информация за доставка
        if (!empty($schema['delivery_selector'])) {
            $delivery_elements = $this->query_selector($xpath, $schema['delivery_selector']);
            if ($delivery_elements->length > 0) {
                $delivery_text = trim($delivery_elements->item(0)->textContent);
                $scraped_data['delivery'] = $this->clean_delivery_info($delivery_text);
            }
        }

        return $scraped_data;
    }

    /**
     * CSS селектор към XPath конвертация
     */
    private function query_selector($xpath, $css_selector) {
        // Основни CSS към XPath конверсии
        $css_selector = trim($css_selector);
        
        // Конвертиране на CSS селектори към XPath
        $xpath_expression = $css_selector;
        
        // Клас селектори (.class)
        $xpath_expression = preg_replace('/\.([a-zA-Z0-9_-]+)/', "[contains(@class, '$1')]", $xpath_expression);
        
        // ID селектори (#id)  
        $xpath_expression = preg_replace('/#([a-zA-Z0-9_-]+)/', "[@id='$1']", $xpath_expression);
        
        // Атрибут селектори [attr=value]
        $xpath_expression = preg_replace('/\[([a-zA-Z0-9_-]+)=[\'"]?([^\'"]*)[\'"]?\]/', "[@$1='$2']", $xpath_expression);
        
        // Tag селектори
        $xpath_expression = preg_replace('/^([a-zA-Z][a-zA-Z0-9]*)([\[\.]|$)/', '//$1$2', $xpath_expression);
        
        // Descendant селектори (space)
        $xpath_expression = preg_replace('/\s+/', '//', $xpath_expression);
        
        // Ако няма // в началото, добавяме
        if (strpos($xpath_expression, '//') !== 0) {
            $xpath_expression = '//' . $xpath_expression;
        }
        
        try {
            return $xpath->query($xpath_expression);
        } catch (Exception $e) {
            // Fallback - търсене по text съдържание
            return $xpath->query("//*[contains(text(), '{$css_selector}')]");
        }
    }

    /**
     * Почистване на цена
     */
    private function clean_price($price_text) {
        // Премахване на всички символи освен цифри, точка и запетая
        $clean_price = preg_replace('/[^\d\.,]/', '', $price_text);
        
        // Конвертиране на запетая към точка за decimal separator
        $clean_price = str_replace(',', '.', $clean_price);
        
        // Ако има повече от една точка, запазваме последната като decimal separator
        $parts = explode('.', $clean_price);
        if (count($parts) > 2) {
            $decimal = array_pop($parts);
            $integer = implode('', $parts);
            $clean_price = $integer . '.' . $decimal;
        }
        
        return floatval($clean_price);
    }

    /**
     * Почистване на наличност
     */
    private function clean_availability($availability_text) {
        $availability_text = strtolower(trim($availability_text));
        
        // Български индикатори за наличност
        $available_keywords = array('наличен', 'налично', 'в наличност', 'available', 'in stock', 'на склад');
        $unavailable_keywords = array('изчерпан', 'няма наличност', 'out of stock', 'unavailable', 'не е наличен');
        
        foreach ($available_keywords as $keyword) {
            if (strpos($availability_text, $keyword) !== false) {
                return 'available';
            }
        }
        
        foreach ($unavailable_keywords as $keyword) {
            if (strpos($availability_text, $keyword) !== false) {
                return 'unavailable';
            }
        }
        
        return 'unknown';
    }

    /**
     * Извличане на данни за вариант
     */
    private function extract_variant_data($variant_text) {
        // Търсене на милилитри
        if (preg_match('/(\d+)\s*ml/i', $variant_text, $matches)) {
            $ml = intval($matches[1]);
            
            // Търсене на цена в същия текст
            $price = null;
            if (preg_match('/(\d+[\.,]?\d*)\s*лв/i', $variant_text, $price_matches)) {
                $price = $this->clean_price($price_matches[1]);
            }
            
            return array(
                'ml' => $ml,
                'price' => $price,
                'text' => $variant_text
            );
        }
        
        return null;
    }

    /**
     * Почистване на информация за доставка
     */
    private function clean_delivery_info($delivery_text) {
        $delivery_text = trim($delivery_text);
        
        // Търсене за безплатна доставка
        if (preg_match('/(безплатна|free)/i', $delivery_text)) {
            return array(
                'type' => 'free',
                'text' => $delivery_text
            );
        }
        
        // Търсене за цена на доставка
        if (preg_match('/(\d+[\.,]?\d*)\s*лв/i', $delivery_text, $matches)) {
            return array(
                'type' => 'paid',
                'price' => $this->clean_price($matches[1]),
                'text' => $delivery_text
            );
        }
        
        return array(
            'type' => 'unknown',
            'text' => $delivery_text
        );
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
        $settings = get_option('parfume_catalog_scraper_settings', array());
        $max_retries = isset($settings['max_retries']) ? intval($settings['max_retries']) : 3;
        
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
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        if ($error_message) {
            $update_data['error_message'] = $error_message;
        }
        
        $wpdb->update(
            $scraper_table,
            $update_data,
            array('id' => $scraper_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Проверка дали да се зачита robots.txt
     */
    private function should_respect_robots() {
        $settings = get_option('parfume_catalog_scraper_settings', array());
        return !empty($settings['respect_robots']);
    }

    /**
     * Проверка на robots.txt
     */
    private function check_robots_txt($url) {
        $parsed_url = parse_url($url);
        if (!$parsed_url) {
            return true; // Ако не можем да парсираме URL-а, позволяваме скрейпването
        }
        
        $robots_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/robots.txt';
        
        $response = wp_remote_get($robots_url, array(
            'timeout' => 5,
            'user-agent' => 'Parfume-Catalog-Bot/1.0'
        ));
        
        if (is_wp_error($response)) {
            return true; // Ако не можем да достъпим robots.txt, позволяваме скрейпването
        }
        
        $robots_content = wp_remote_retrieve_body($response);
        
        // Проста проверка за Disallow правила
        $lines = explode("\n", $robots_content);
        $applies_to_us = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (preg_match('/^User-agent:\s*\*/i', $line) || 
                preg_match('/^User-agent:\s*Parfume-Catalog-Bot/i', $line)) {
                $applies_to_us = true;
                continue;
            }
            
            if (preg_match('/^User-agent:/i', $line)) {
                $applies_to_us = false;
                continue;
            }
            
            if ($applies_to_us && preg_match('/^Disallow:\s*(.+)/i', $line, $matches)) {
                $disallowed_path = trim($matches[1]);
                $path = parse_url($url, PHP_URL_PATH);
                
                if ($disallowed_path === '/' || 
                    ($disallowed_path !== '/' && strpos($path, $disallowed_path) === 0)) {
                    return false; // Disallowed
                }
            }
        }
        
        return true; // Allowed
    }

    /**
     * Логиране на scraper активност
     */
    private function log_scraper_activity($level, $message, $scraper_id = null) {
        global $wpdb;
        
        $settings = get_option('parfume_catalog_scraper_settings', array());
        $log_level = isset($settings['log_level']) ? $settings['log_level'] : self::LOG_INFO;
        
        // Проверка дали това ниво трябва да се логира
        $level_priority = array(
            self::LOG_DEBUG => 0,
            self::LOG_INFO => 1,
            self::LOG_SUCCESS => 2,
            self::LOG_WARNING => 3,
            self::LOG_ERROR => 4
        );
        
        if ($level_priority[$level] < $level_priority[$log_level]) {
            return;
        }
        
        $logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        
        $wpdb->insert(
            $logs_table,
            array(
                'scraper_id' => $scraper_id,
                'level' => $level,
                'message' => $message,
                'context' => wp_json_encode(array(
                    'timestamp' => current_time('mysql'),
                    'memory_usage' => memory_get_usage(true),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'WordPress Cron'
                )),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        // Автоматично изчистване на стари логове
        $this->cleanup_old_logs();
    }

    /**
     * Изчистване на стари логове
     */
    private function cleanup_old_logs() {
        global $wpdb;
        
        $settings = get_option('parfume_catalog_scraper_settings', array());
        $retention_days = isset($settings['log_retention']) ? intval($settings['log_retention']) : 30;
        
        $logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM $logs_table 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $retention_days));
    }

    /**
     * Тестване на schema за магазин
     */
    public function test_store_schema($store_id, $test_url) {
        try {
            $stores_schemas = get_option('parfume_catalog_stores_schemas', array());
            $schema = isset($stores_schemas[$store_id]) ? $stores_schemas[$store_id] : array();
            
            if (empty($schema)) {
                throw new Exception('Липсва schema за този магазин');
            }
            
            $html_content = $this->fetch_page_content($test_url);
            $scraped_data = $this->parse_page_data($html_content, $schema);
            
            return array(
                'success' => true,
                'data' => $scraped_data,
                'schema' => $schema
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * AJAX: Ръчно скрейпване
     */
    public function ajax_manual_scrape() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $scraper_id = intval($_POST['scraper_id'] ?? 0);
        
        if (!$scraper_id) {
            wp_send_json_error(__('Невалиден scraper ID.', 'parfume-catalog'));
        }
        
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $scrape_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $scraper_table WHERE id = %d",
            $scraper_id
        ), ARRAY_A);
        
        if (!$scrape_item) {
            wp_send_json_error(__('Scraper записът не е намерен.', 'parfume-catalog'));
        }
        
        $stores_schemas = get_option('parfume_catalog_stores_schemas', array());
        $this->process_single_scrape($scrape_item, $stores_schemas);
        
        // Получаване на обновените данни
        $updated_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $scraper_table WHERE id = %d",
            $scraper_id
        ), ARRAY_A);
        
        wp_send_json_success(array(
            'message' => __('Скрейпването завърши.', 'parfume-catalog'),
            'status' => $updated_item['status'],
            'data' => json_decode($updated_item['scraped_data'], true),
            'error' => $updated_item['error_message'],
            'last_scraped' => $updated_item['last_scraped']
        ));
    }

    /**
     * AJAX: Тестване на единичен URL
     */
    public function ajax_test_single_url() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $url = sanitize_url($_POST['url'] ?? '');
        $store_id = intval($_POST['store_id'] ?? 0);
        
        if (empty($url)) {
            wp_send_json_error(__('Моля въведете валиден URL.', 'parfume-catalog'));
        }
        
        try {
            if ($store_id) {
                $result = $this->test_store_schema($store_id, $url);
            } else {
                // Основен тест на достъпност
                $html_content = $this->fetch_page_content($url);
                $result = array(
                    'success' => true,
                    'message' => __('URL-ът е достъпен.', 'parfume-catalog'),
                    'content_length' => strlen($html_content)
                );
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Грешка при тестването: ', 'parfume-catalog') . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX: Получаване на scraper статистики
     */
    public function ajax_get_scraper_stats() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM $scraper_table
        ", ARRAY_A);
        
        wp_send_json_success($stats);
    }

    /**
     * Добавяне на scraper запис за парфюм
     */
    public static function add_scraper_entry($post_id, $store_id, $product_url) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        // Проверка дали записът вече съществува
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $scraper_table WHERE post_id = %d AND store_id = %d",
            $post_id, $store_id
        ));
        
        if ($existing) {
            // Обновяване на съществуващ запис
            $wpdb->update(
                $scraper_table,
                array(
                    'product_url' => $product_url,
                    'status' => self::STATUS_PENDING,
                    'next_scrape' => current_time('mysql')
                ),
                array('id' => $existing),
                array('%s', '%s', '%s'),
                array('%d')
            );
            return $existing;
        } else {
            // Добавяне на нов запис
            $wpdb->insert(
                $scraper_table,
                array(
                    'post_id' => $post_id,
                    'store_id' => $store_id,
                    'product_url' => $product_url,
                    'status' => self::STATUS_PENDING,
                    'next_scrape' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Премахване на scraper запис
     */
    public static function remove_scraper_entry($post_id, $store_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $wpdb->delete(
            $scraper_table,
            array(
                'post_id' => $post_id,
                'store_id' => $store_id
            ),
            array('%d', '%d')
        );
    }

    /**
     * Получаване на scraper данни за парфюм
     */
    public static function get_scraped_data($post_id, $store_id = null) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        if ($store_id) {
            $data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $scraper_table WHERE post_id = %d AND store_id = %d",
                $post_id, $store_id
            ), ARRAY_A);
            
            if ($data && !empty($data['scraped_data'])) {
                $data['scraped_data'] = json_decode($data['scraped_data'], true);
            }
            
            return $data;
        } else {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $scraper_table WHERE post_id = %d",
                $post_id
            ), ARRAY_A);
            
            foreach ($results as &$result) {
                if (!empty($result['scraped_data'])) {
                    $result['scraped_data'] = json_decode($result['scraped_data'], true);
                }
            }
            
            return $results;
        }
    }

    /**
     * Static метод за external access
     */
    public static function is_scraper_enabled() {
        $settings = get_option('parfume_catalog_scraper_settings', array());
        return !empty($settings['enabled']);
    }
}

// Инициализиране
new Parfume_Catalog_Scraper();