<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Scraper class - Управлява настройките за Product Scraper
 * 
 * Файл: includes/settings/class-settings-scraper.php
 * Създаден за Product Scraper функционалността
 */
class Settings_Scraper {
    
    public function __construct() {
        // AJAX actions за scraper функционалност
        add_action('wp_ajax_parfume_manual_scrape', array($this, 'ajax_manual_scrape'));
        add_action('wp_ajax_parfume_test_scraper', array($this, 'ajax_test_scraper'));
        add_action('wp_ajax_parfume_save_scraper_schema', array($this, 'ajax_save_scraper_schema'));
        
        // Cron job за автоматично скрейпване
        add_action('parfume_reviews_scraper_cron', array($this, 'run_batch_scraper'));
        
        // Регистрираме cron event
        if (!wp_next_scheduled('parfume_reviews_scraper_cron')) {
            wp_schedule_event(time(), 'hourly', 'parfume_reviews_scraper_cron');
        }
    }
    
    /**
     * Регистрира настройките за scraper
     */
    public function register_settings() {
        // Product Scraper Section
        add_settings_section(
            'parfume_reviews_scraper_section',
            __('Product Scraper', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        register_setting('parfume-reviews-settings', 'parfume_reviews_scraper_settings', array(
            'sanitize_callback' => array($this, 'sanitize_scraper_settings')
        ));
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Конфигурирайте автоматичното скрейпване на продуктова информация от външни магазини.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията за scraper настройки
     */
    public function render_section() {
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $default_settings = $this->get_default_scraper_settings();
        $settings = wp_parse_args($scraper_settings, $default_settings);
        ?>
        <div class="scraper-settings">
            <!-- Основни настройки -->
            <h3><?php _e('Основни настройки', 'parfume-reviews'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="scrape_interval"><?php _e('Интервал за скрейпване', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <select id="scrape_interval" name="parfume_reviews_scraper_settings[scrape_interval]">
                            <option value="1" <?php selected($settings['scrape_interval'], 1); ?>><?php _e('Всеки час', 'parfume-reviews'); ?></option>
                            <option value="6" <?php selected($settings['scrape_interval'], 6); ?>><?php _e('На всеки 6 часа', 'parfume-reviews'); ?></option>
                            <option value="12" <?php selected($settings['scrape_interval'], 12); ?>><?php _e('На всеки 12 часа', 'parfume-reviews'); ?></option>
                            <option value="24" <?php selected($settings['scrape_interval'], 24); ?>><?php _e('Веднъж дневно', 'parfume-reviews'); ?></option>
                            <option value="168" <?php selected($settings['scrape_interval'], 168); ?>><?php _e('Веднъж седмично', 'parfume-reviews'); ?></option>
                        </select>
                        <p class="description"><?php _e('Колко често да се обновява информацията автоматично.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="batch_size"><?php _e('Размер на batch', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="batch_size" name="parfume_reviews_scraper_settings[batch_size]" 
                               value="<?php echo esc_attr($settings['batch_size']); ?>" min="1" max="50" class="small-text" />
                        <p class="description"><?php _e('Колко URL-а да се обработват наведнъж при всяко изпълнение.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="user_agent"><?php _e('User Agent', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="user_agent" name="parfume_reviews_scraper_settings[user_agent]" 
                               value="<?php echo esc_attr($settings['user_agent']); ?>" class="large-text" />
                        <p class="description"><?php _e('User Agent string за HTTP заявките.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="timeout"><?php _e('Timeout (секунди)', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="timeout" name="parfume_reviews_scraper_settings[timeout]" 
                               value="<?php echo esc_attr($settings['timeout']); ?>" min="5" max="60" class="small-text" />
                        <p class="description"><?php _e('Максимално време за изчакване на отговор.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_retries"><?php _e('Максимум повторни опити', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_retries" name="parfume_reviews_scraper_settings[max_retries]" 
                               value="<?php echo esc_attr($settings['max_retries']); ?>" min="0" max="10" class="small-text" />
                        <p class="description"><?php _e('Колко пъти да се опитва при грешка.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
            </table>
            
            <!-- Scraper Test Tool -->
            <h3><?php _e('Scraper Test Tool', 'parfume-reviews'); ?></h3>
            <div class="scraper-test-tool">
                <p><?php _e('Тествайте скрейпването на произволен URL и настройте schema за магазин.', 'parfume-reviews'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="test_url"><?php _e('Тестов URL', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="test_url" class="large-text" placeholder="https://example.com/product-page" />
                            <button type="button" class="button" id="test-scrape-btn">
                                <?php _e('Скрейпни и анализирай', 'parfume-reviews'); ?>
                            </button>
                        </td>
                    </tr>
                </table>
                
                <div id="scraper-test-results" style="display:none;">
                    <h4><?php _e('Резултати от анализа', 'parfume-reviews'); ?></h4>
                    <div class="test-results-content"></div>
                </div>
            </div>
            
            <!-- Monitor Section -->
            <h3><?php _e('Мониторинг', 'parfume-reviews'); ?></h3>
            <div class="scraper-monitor">
                <?php $this->render_monitor_section(); ?>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Тест на scraper
            $('#test-scrape-btn').click(function(e) {
                e.preventDefault();
                
                var testUrl = $('#test_url').val().trim();
                if (!testUrl) {
                    alert('<?php _e('Моля въведете валиден URL.', 'parfume-reviews'); ?>');
                    return;
                }
                
                $(this).prop('disabled', true).text(parfumeSettings.strings.scraping);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_test_scraper',
                        nonce: parfumeSettings.nonce,
                        test_url: testUrl
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#scraper-test-results .test-results-content').html(response.data.html);
                            $('#scraper-test-results').show();
                        } else {
                            alert(response.data.message || parfumeSettings.strings.error);
                        }
                    },
                    error: function() {
                        alert(parfumeSettings.strings.error);
                    },
                    complete: function() {
                        $('#test-scrape-btn').prop('disabled', false).text('<?php _e('Скрейпни и анализирай', 'parfume-reviews'); ?>');
                    }
                });
            });
            
            // Ръчно скрейпване
            $('.manual-scrape-btn').click(function(e) {
                e.preventDefault();
                
                var postId = $(this).data('post-id');
                var storeId = $(this).data('store-id');
                
                $(this).prop('disabled', true).text(parfumeSettings.strings.scraping);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_manual_scrape',
                        nonce: parfumeSettings.nonce,
                        post_id: postId,
                        store_id: storeId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Презареждаме за да видим новите данни
                        } else {
                            alert(response.data.message || parfumeSettings.strings.error);
                        }
                    },
                    error: function() {
                        alert(parfumeSettings.strings.error);
                    },
                    complete: function() {
                        $('.manual-scrape-btn').prop('disabled', false).text('<?php _e('Обнови', 'parfume-reviews'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Рендерира мониторинг секцията
     */
    private function render_monitor_section() {
        // Получаваме всички постове с product URLs
        $posts_with_urls = $this->get_posts_with_product_urls();
        
        if (empty($posts_with_urls)) {
            echo '<p>' . __('Няма постове с продуктови URL-и.', 'parfume-reviews') . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Пост', 'parfume-reviews'); ?></th>
                    <th><?php _e('Магазин', 'parfume-reviews'); ?></th>
                    <th><?php _e('Product URL', 'parfume-reviews'); ?></th>
                    <th><?php _e('Последна цена', 'parfume-reviews'); ?></th>
                    <th><?php _e('Последно скрейпване', 'parfume-reviews'); ?></th>
                    <th><?php _e('Статус', 'parfume-reviews'); ?></th>
                    <th><?php _e('Действия', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts_with_urls as $item): ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link($item['post_id']); ?>">
                                <?php echo esc_html(get_the_title($item['post_id'])); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo esc_html($item['store_name']); ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($item['product_url']); ?>" target="_blank" rel="nofollow">
                                <?php echo esc_html(wp_trim_words($item['product_url'], 8, '...')); ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($item['last_price'])): ?>
                                <?php echo esc_html($item['last_price']); ?>
                            <?php else: ?>
                                <span class="no-data"><?php _e('Няма данни', 'parfume-reviews'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['last_scraped'])): ?>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['last_scraped']))); ?>
                            <?php else: ?>
                                <span class="no-data"><?php _e('Никога', 'parfume-reviews'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status = $item['status'];
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($status) {
                                case 'success':
                                    $status_class = 'status-success';
                                    $status_text = __('Успешно', 'parfume-reviews');
                                    break;
                                case 'error':
                                    $status_class = 'status-error';
                                    $status_text = __('Грешка', 'parfume-reviews');
                                    break;
                                case 'blocked':
                                    $status_class = 'status-blocked';
                                    $status_text = __('Блокиран', 'parfume-reviews');
                                    break;
                                case 'missing_data':
                                    $status_class = 'status-warning';
                                    $status_text = __('Липсват данни', 'parfume-reviews');
                                    break;
                                default:
                                    $status_class = 'status-pending';
                                    $status_text = __('Изчакване', 'parfume-reviews');
                            }
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button-small manual-scrape-btn" 
                                    data-post-id="<?php echo esc_attr($item['post_id']); ?>" 
                                    data-store-id="<?php echo esc_attr($item['store_id']); ?>">
                                <?php _e('Обнови', 'parfume-reviews'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <style>
        .status-badge {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-blocked { background: #fff3cd; color: #856404; }
        .status-warning { background: #ffeaa7; color: #6c5500; }
        .status-pending { background: #d1ecf1; color: #0c5460; }
        .no-data { color: #999; font-style: italic; }
        </style>
        <?php
    }
    
    /**
     * Получава постове с продуктови URL-и
     */
    private function get_posts_with_product_urls() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT p.ID as post_id, pm.meta_value as stores_data
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'parfume'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_parfume_stores'
            AND pm.meta_value != ''
        ");
        
        $posts_with_urls = array();
        $stores = get_option('parfume_reviews_stores', array());
        
        foreach ($results as $result) {
            $stores_data = maybe_unserialize($result->stores_data);
            if (!is_array($stores_data)) continue;
            
            foreach ($stores_data as $store_id => $store_data) {
                if (empty($store_data['product_url'])) continue;
                
                $store_name = isset($stores[$store_id]['name']) ? $stores[$store_id]['name'] : __('Неизвестен магазин', 'parfume-reviews');
                
                $posts_with_urls[] = array(
                    'post_id' => $result->post_id,
                    'store_id' => $store_id,
                    'store_name' => $store_name,
                    'product_url' => $store_data['product_url'],
                    'last_price' => isset($store_data['scraped_data']['price']) ? $store_data['scraped_data']['price'] : '',
                    'last_scraped' => isset($store_data['last_scraped']) ? $store_data['last_scraped'] : '',
                    'status' => isset($store_data['scrape_status']) ? $store_data['scrape_status'] : 'pending'
                );
            }
        }
        
        return $posts_with_urls;
    }
    
    /**
     * AJAX handler за тестване на scraper
     */
    public function ajax_test_scraper() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $test_url = esc_url_raw($_POST['test_url']);
        
        if (empty($test_url)) {
            wp_send_json_error(array('message' => __('Невалиден URL.', 'parfume-reviews')));
        }
        
        // Изпълняваме тестовото скрейпване
        $scraper_result = $this->scrape_product_page($test_url);
        
        if (is_wp_error($scraper_result)) {
            wp_send_json_error(array('message' => $scraper_result->get_error_message()));
        }
        
        // Генерираме HTML за резултатите
        ob_start();
        ?>
        <div class="scraper-test-results">
            <h4><?php _e('Открити данни:', 'parfume-reviews'); ?></h4>
            
            <?php if (!empty($scraper_result['prices'])): ?>
                <div class="found-data">
                    <h5><?php _e('Цени:', 'parfume-reviews'); ?></h5>
                    <ul>
                        <?php foreach ($scraper_result['prices'] as $price): ?>
                            <li><?php echo esc_html($price); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($scraper_result['variants'])): ?>
                <div class="found-data">
                    <h5><?php _e('Разфасовки:', 'parfume-reviews'); ?></h5>
                    <ul>
                        <?php foreach ($scraper_result['variants'] as $variant): ?>
                            <li><?php echo esc_html($variant); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($scraper_result['availability'])): ?>
                <div class="found-data">
                    <h5><?php _e('Наличност:', 'parfume-reviews'); ?></h5>
                    <p><?php echo esc_html($scraper_result['availability']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($scraper_result['shipping'])): ?>
                <div class="found-data">
                    <h5><?php _e('Доставка:', 'parfume-reviews'); ?></h5>
                    <p><?php echo esc_html($scraper_result['shipping']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="schema-configuration">
                <h5><?php _e('Конфигурация на schema:', 'parfume-reviews'); ?></h5>
                <p><?php _e('Тук можете да настроите CSS селекторите за автоматично извличане на данни.', 'parfume-reviews'); ?></p>
                <!-- Тук ще добавим интерфейс за конфигуриране на schema -->
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX handler за ръчно скрейпване
     */
    public function ajax_manual_scrape() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Нямате права за това действие.', 'parfume-reviews')));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Получаваме данните за магазината от поста
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($stores_data) || !isset($stores_data[$store_id])) {
            wp_send_json_error(array('message' => __('Магазинът не е намерен в поста.', 'parfume-reviews')));
        }
        
        $product_url = $stores_data[$store_id]['product_url'];
        
        if (empty($product_url)) {
            wp_send_json_error(array('message' => __('Няма зададен Product URL.', 'parfume-reviews')));
        }
        
        // Извършваме скрейпването
        $scrape_result = $this->scrape_product_page($product_url);
        
        if (is_wp_error($scrape_result)) {
            // Записваме грешката
            $stores_data[$store_id]['scrape_status'] = 'error';
            $stores_data[$store_id]['last_error'] = $scrape_result->get_error_message();
            $stores_data[$store_id]['last_scraped'] = current_time('mysql');
            
            update_post_meta($post_id, '_parfume_stores', $stores_data);
            
            wp_send_json_error(array('message' => $scrape_result->get_error_message()));
        }
        
        // Записваме успешните данни
        $stores_data[$store_id]['scraped_data'] = $scrape_result;
        $stores_data[$store_id]['scrape_status'] = 'success';
        $stores_data[$store_id]['last_scraped'] = current_time('mysql');
        unset($stores_data[$store_id]['last_error']);
        
        update_post_meta($post_id, '_parfume_stores', $stores_data);
        
        wp_send_json_success(array('message' => __('Скрейпването завърши успешно.', 'parfume-reviews')));
    }
    
    /**
     * Основна функция за скрейпване на продуктова страница
     */
    private function scrape_product_page($url) {
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $default_settings = $this->get_default_scraper_settings();
        $settings = wp_parse_args($scraper_settings, $default_settings);
        
        // HTTP заявка с настроени параметри
        $response = wp_remote_get($url, array(
            'timeout' => $settings['timeout'],
            'user-agent' => $settings['user_agent'],
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'bg,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        if ($http_code !== 200) {
            return new \WP_Error('http_error', sprintf(__('HTTP грешка: %d', 'parfume-reviews'), $http_code));
        }
        
        if (empty($body)) {
            return new \WP_Error('empty_response', __('Празен отговор от сървъра.', 'parfume-reviews'));
        }
        
        // Парсваме HTML-а
        return $this->parse_product_html($body, $url);
    }
    
    /**
     * Парсва HTML и извлича продуктова информация
     */
    private function parse_product_html($html, $url) {
        // Използваме DOMDocument за парсване на HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        $result = array(
            'prices' => array(),
            'variants' => array(),
            'availability' => '',
            'shipping' => ''
        );
        
        // Общи селектори за цени
        $price_selectors = array(
            '.price',
            '.product-price',
            '[class*="price"]',
            '[data-price]',
            '.cost',
            '.amount'
        );
        
        foreach ($price_selectors as $selector) {
            $elements = $this->xpath_query_by_css($xpath, $selector);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                if ($this->is_price_text($text)) {
                    $result['prices'][] = $text;
                }
            }
        }
        
        // Общи селектори за варианти/разфасовки
        $variant_selectors = array(
            '[class*="variant"]',
            '[class*="size"]',
            '[class*="volume"]',
            '[class*="ml"]',
            'select option'
        );
        
        foreach ($variant_selectors as $selector) {
            $elements = $this->xpath_query_by_css($xpath, $selector);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                if ($this->is_variant_text($text)) {
                    $result['variants'][] = $text;
                }
            }
        }
        
        // Общи селектори за наличност
        $availability_selectors = array(
            '[class*="stock"]',
            '[class*="availability"]',
            '[class*="available"]',
            '.in-stock',
            '.out-of-stock'
        );
        
        foreach ($availability_selectors as $selector) {
            $elements = $this->xpath_query_by_css($xpath, $selector);
            if (!empty($elements)) {
                $result['availability'] = trim($elements[0]->textContent);
                break;
            }
        }
        
        // Общи селектори за доставка
        $shipping_selectors = array(
            '[class*="shipping"]',
            '[class*="delivery"]',
            '[class*="freight"]',
            '.shipping-info',
            '.delivery-info'
        );
        
        foreach ($shipping_selectors as $selector) {
            $elements = $this->xpath_query_by_css($xpath, $selector);
            if (!empty($elements)) {
                $result['shipping'] = trim($elements[0]->textContent);
                break;
            }
        }
        
        // Премахваме дублиращи се данни
        $result['prices'] = array_unique($result['prices']);
        $result['variants'] = array_unique($result['variants']);
        
        return $result;
    }
    
    /**
     * Помощна функция за XPath заявки с CSS селектори
     */
    private function xpath_query_by_css($xpath, $css_selector) {
        // Опростена конверсия от CSS към XPath
        $xpath_selector = $css_selector;
        
        // Заместваме основни CSS селектори с XPath
        $xpath_selector = str_replace('.', "[contains(@class, '", $xpath_selector);
        $xpath_selector = str_replace('[class*="', "[contains(@class, '", $xpath_selector);
        $xpath_selector = str_replace('"]', "')]", $xpath_selector);
        
        if (strpos($xpath_selector, '[') === false) {
            // Ако няма атрибути, добавяме основния XPath
            $xpath_selector = "//*[contains(@class, '" . ltrim($xpath_selector, '.') . "')]";
        } else {
            $xpath_selector = "//*" . $xpath_selector;
        }
        
        try {
            return $xpath->query($xpath_selector);
        } catch (Exception $e) {
            return new \DOMNodeList();
        }
    }
    
    /**
     * Проверява дали текстът е цена
     */
    private function is_price_text($text) {
        // Търсим числа със символи за валута
        return preg_match('/\d+[.,]\d+\s*(?:лв|bgn|€|eur|\$|usd)/i', $text) || 
               preg_match('/(?:лв|bgn|€|eur|\$|usd)\s*\d+[.,]\d+/i', $text);
    }
    
    /**
     * Проверява дали текстът е вариант/разфасовка
     */
    private function is_variant_text($text) {
        // Търсим обеми в ml, oz и други мерни единици
        return preg_match('/\d+\s*(?:ml|мл|oz|г|g)\b/i', $text);
    }
    
    /**
     * Batch scraper за cron job
     */
    public function run_batch_scraper() {
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $default_settings = $this->get_default_scraper_settings();
        $settings = wp_parse_args($scraper_settings, $default_settings);
        
        $batch_size = $settings['batch_size'];
        $posts_with_urls = $this->get_posts_with_product_urls();
        
        // Получаваме pointer за последния обработен елемент
        $pointer = get_option('parfume_scraper_pointer', 0);
        
        // Вземаме следващия batch
        $batch = array_slice($posts_with_urls, $pointer, $batch_size);
        
        foreach ($batch as $item) {
            $this->scrape_single_item($item);
            
            // Добавяме малка пауза между заявките
            sleep(1);
        }
        
        // Обновяваме pointer
        $new_pointer = $pointer + $batch_size;
        if ($new_pointer >= count($posts_with_urls)) {
            $new_pointer = 0; // Започваме отначало
        }
        
        update_option('parfume_scraper_pointer', $new_pointer);
    }
    
    /**
     * Скрейпва единичен елемент
     */
    private function scrape_single_item($item) {
        $post_id = $item['post_id'];
        $store_id = $item['store_id'];
        $product_url = $item['product_url'];
        
        $stores_data = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            return;
        }
        
        // Проверяваме дали не е скрейпван скоро
        $last_scraped = isset($stores_data[$store_id]['last_scraped']) ? $stores_data[$store_id]['last_scraped'] : '';
        if (!empty($last_scraped)) {
            $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
            $interval_hours = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 24;
            
            $next_scrape_time = strtotime($last_scraped) + ($interval_hours * 3600);
            if (time() < $next_scrape_time) {
                return; // Още е рано за скрейпване
            }
        }
        
        // Извършваме скрейпването
        $scrape_result = $this->scrape_product_page($product_url);
        
        if (is_wp_error($scrape_result)) {
            $stores_data[$store_id]['scrape_status'] = 'error';
            $stores_data[$store_id]['last_error'] = $scrape_result->get_error_message();
        } else {
            $stores_data[$store_id]['scraped_data'] = $scrape_result;
            $stores_data[$store_id]['scrape_status'] = 'success';
            unset($stores_data[$store_id]['last_error']);
        }
        
        $stores_data[$store_id]['last_scraped'] = current_time('mysql');
        update_post_meta($post_id, '_parfume_stores', $stores_data);
    }
    
    /**
     * Получава default настройки за scraper
     */
    private function get_default_scraper_settings() {
        return array(
            'scrape_interval' => 24, // часове
            'batch_size' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'timeout' => 30,
            'max_retries' => 3
        );
    }
    
    /**
     * Санитизация на scraper настройки
     */
    public function sanitize_scraper_settings($input) {
        if (!is_array($input)) {
            return $this->get_default_scraper_settings();
        }
        
        $sanitized = array();
        $defaults = $this->get_default_scraper_settings();
        
        $sanitized['scrape_interval'] = isset($input['scrape_interval']) ? intval($input['scrape_interval']) : $defaults['scrape_interval'];
        $sanitized['batch_size'] = isset($input['batch_size']) ? max(1, min(50, intval($input['batch_size']))) : $defaults['batch_size'];
        $sanitized['user_agent'] = isset($input['user_agent']) ? sanitize_text_field($input['user_agent']) : $defaults['user_agent'];
        $sanitized['timeout'] = isset($input['timeout']) ? max(5, min(60, intval($input['timeout']))) : $defaults['timeout'];
        $sanitized['max_retries'] = isset($input['max_retries']) ? max(0, min(10, intval($input['max_retries']))) : $defaults['max_retries'];
        
        return $sanitized;
    }
}