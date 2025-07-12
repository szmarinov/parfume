<?php
namespace Parfume_Reviews;

class Product_Scraper {
    
    private $user_agents = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    );
    
    public function __construct() {
        // WP Cron за автоматично скрейпване
        add_action('wp', array($this, 'schedule_scraping'));
        add_action('parfume_scraper_cron', array($this, 'run_batch_scraping'));
        
        // AJAX handlers за ръчно скрейпване
        add_action('wp_ajax_manual_scrape_product', array($this, 'manual_scrape_product'));
        add_action('wp_ajax_test_scraper_url', array($this, 'test_scraper_url'));
        add_action('wp_ajax_save_store_schema', array($this, 'save_store_schema'));
        
        // Admin страница за мониториране
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Meta boxes за постове
        add_action('add_meta_boxes', array($this, 'add_scraper_meta_boxes'));
        add_action('save_post', array($this, 'save_scraper_meta_boxes'));
        
        // Settings integration
        add_action('admin_init', array($this, 'register_scraper_settings'));
    }
    
    public function schedule_scraping() {
        if (!wp_next_scheduled('parfume_scraper_cron')) {
            $settings = get_option('parfume_reviews_settings', array());
            $interval = isset($settings['scrape_interval']) ? intval($settings['scrape_interval']) : 24;
            
            // Конвертираме часове в секунди
            $interval_seconds = $interval * 3600;
            
            wp_schedule_event(time(), 'hourly', 'parfume_scraper_cron');
        }
    }
    
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
    
    public function register_scraper_settings() {
        // Добавяме настройки към съществуващата група
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
    
    public function render_stores_meta_box($post) {
        wp_nonce_field('product_scraper_nonce', 'product_scraper_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores_v2', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        // Получаваме всички налични stores от настройките
        $available_stores = get_option('parfume_reviews_stores', array());
        
        ?>
        <div id="product-scraper-container">
            <div class="stores-list" id="stores-list">
                <?php if (!empty($stores)): ?>
                    <?php foreach ($stores as $index => $store): ?>
                        <?php $this->render_single_store_admin($index, $store, $post->ID, $available_stores); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="add-store-section">
                <select id="available-stores-select">
                    <option value=""><?php _e('Select a store to add', 'parfume-reviews'); ?></option>
                    <?php foreach ($available_stores as $store_id => $store_data): ?>
                        <option value="<?php echo esc_attr($store_id); ?>"><?php echo esc_html($store_data['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add-store-btn" class="button"><?php _e('Add Store', 'parfume-reviews'); ?></button>
            </div>
        </div>
        
        <script type="text/template" id="store-template">
            <?php $this->render_single_store_admin('{{INDEX}}', array(), $post->ID, $available_stores); ?>
        </script>
        
        <style>
        .store-item-admin {
            border: 1px solid #ddd;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            position: relative;
        }
        .store-drag-handle {
            cursor: move;
            padding: 5px;
            background: #0073aa;
            color: white;
            display: inline-block;
            margin-bottom: 10px;
        }
        .store-remove {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #a00;
            text-decoration: none;
        }
        .scraped-data {
            background: #e7f3ff;
            padding: 10px;
            margin-top: 10px;
            border-left: 4px solid #0073aa;
        }
        .scrape-status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        </style>
        <?php
    }
    
    private function render_single_store_admin($index, $store, $post_id, $available_stores) {
        $store = wp_parse_args($store, array(
            'store_id' => '',
            'product_url' => '',
            'affiliate_url' => '',
            'promo_code' => '',
            'promo_code_info' => '',
        ));
        
        // Получаваме scraped data
        $scraped_key = 'scraped_' . md5($store['product_url']);
        $scraped_data = get_post_meta($post_id, '_store_' . $scraped_key, true);
        $scraped_data = is_array($scraped_data) ? $scraped_data : array();
        
        $store_info = isset($available_stores[$store['store_id']]) ? $available_stores[$store['store_id']] : array();
        
        ?>
        <div class="store-item-admin" data-index="<?php echo esc_attr($index); ?>">
            <div class="store-drag-handle">≡ <?php _e('Drag to reorder', 'parfume-reviews'); ?></div>
            <a href="#" class="store-remove" onclick="return confirm('<?php _e('Remove this store?', 'parfume-reviews'); ?>')"><?php _e('Remove', 'parfume-reviews'); ?></a>
            
            <h4><?php echo isset($store_info['name']) ? esc_html($store_info['name']) : __('Unknown Store', 'parfume-reviews'); ?></h4>
            
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Store', 'parfume-reviews'); ?></label></th>
                    <td>
                        <select name="stores[<?php echo $index; ?>][store_id]" class="store-select">
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
                    <th><label><?php _e('Product URL', 'parfume-reviews'); ?></label></th>
                    <td>
                        <input type="url" name="stores[<?php echo $index; ?>][product_url]" value="<?php echo esc_attr($store['product_url']); ?>" class="large-text">
                        <button type="button" class="button test-scrape-btn" data-url="<?php echo esc_attr($store['product_url']); ?>"><?php _e('Test Scrape', 'parfume-reviews'); ?></button>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Affiliate URL', 'parfume-reviews'); ?></label></th>
                    <td>
                        <input type="url" name="stores[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($store['affiliate_url']); ?>" class="large-text">
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Promo Code', 'parfume-reviews'); ?></label></th>
                    <td>
                        <input type="text" name="stores[<?php echo $index; ?>][promo_code]" value="<?php echo esc_attr($store['promo_code']); ?>" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Promo Code Info', 'parfume-reviews'); ?></label></th>
                    <td>
                        <input type="text" name="stores[<?php echo $index; ?>][promo_code_info]" value="<?php echo esc_attr($store['promo_code_info']); ?>" class="large-text">
                    </td>
                </tr>
            </table>
            
            <?php if (!empty($scraped_data)): ?>
                <div class="scraped-data">
                    <h5><?php _e('Scraped Data', 'parfume-reviews'); ?></h5>
                    
                    <div class="scrape-info-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <!-- Цена -->
                        <div class="scrape-info-item">
                            <strong><?php _e('Price', 'parfume-reviews'); ?></strong><br>
                            <?php if (isset($scraped_data['price'])): ?>
                                <span class="scraped-value"><?php echo esc_html($scraped_data['price']); ?></span><br>
                            <?php endif; ?>
                            <small>
                                <?php _e('Last:', 'parfume-reviews'); ?> <?php echo isset($scraped_data['last_updated']) ? esc_html($scraped_data['last_updated']) : __('Never', 'parfume-reviews'); ?><br>
                                <?php _e('Next:', 'parfume-reviews'); ?> <?php echo isset($scraped_data['next_update']) ? esc_html($scraped_data['next_update']) : __('Pending', 'parfume-reviews'); ?>
                            </small><br>
                            <button type="button" class="button-small manual-scrape-btn" data-post-id="<?php echo $post_id; ?>" data-store-index="<?php echo $index; ?>"><?php _e('Update Now', 'parfume-reviews'); ?></button>
                        </div>
                        
                        <!-- Наличност -->
                        <div class="scrape-info-item">
                            <strong><?php _e('Availability', 'parfume-reviews'); ?></strong><br>
                            <?php if (isset($scraped_data['availability'])): ?>
                                <span class="scraped-value"><?php echo esc_html($scraped_data['availability']); ?></span><br>
                            <?php endif; ?>
                            <small>
                                <?php _e('Last:', 'parfume-reviews'); ?> <?php echo isset($scraped_data['availability_updated']) ? esc_html($scraped_data['availability_updated']) : __('Never', 'parfume-reviews'); ?>
                            </small>
                        </div>
                        
                        <!-- Доставка -->
                        <div class="scrape-info-item">
                            <strong><?php _e('Delivery', 'parfume-reviews'); ?></strong><br>
                            <?php if (isset($scraped_data['delivery'])): ?>
                                <span class="scraped-value"><?php echo esc_html($scraped_data['delivery']); ?></span><br>
                            <?php endif; ?>
                            <small>
                                <?php _e('Last:', 'parfume-reviews'); ?> <?php echo isset($scraped_data['delivery_updated']) ? esc_html($scraped_data['delivery_updated']) : __('Never', 'parfume-reviews'); ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Variants -->
                    <?php if (isset($scraped_data['variants']) && !empty($scraped_data['variants'])): ?>
                        <div class="variants-info">
                            <strong><?php _e('Variants', 'parfume-reviews'); ?></strong><br>
                            <?php foreach ($scraped_data['variants'] as $variant): ?>
                                <span class="variant-item">
                                    <?php echo esc_html($variant['ml']); ?> - <?php echo esc_html($variant['price']); ?>
                                    <?php if (isset($variant['old_price'])): ?>
                                        <small>(<?php _e('was', 'parfume-reviews'); ?> <?php echo esc_html($variant['old_price']); ?>)</small>
                                    <?php endif; ?>
                                </span><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Status -->
                    <div class="scrape-status-info">
                        <span class="scrape-status <?php echo isset($scraped_data['status']) ? 'status-' . esc_attr($scraped_data['status']) : 'status-pending'; ?>">
                            <?php 
                            if (isset($scraped_data['status'])) {
                                switch ($scraped_data['status']) {
                                    case 'success': echo __('Success', 'parfume-reviews'); break;
                                    case 'error': echo __('Error', 'parfume-reviews'); break;
                                    case 'blocked': echo __('Blocked', 'parfume-reviews'); break;
                                    default: echo __('Pending', 'parfume-reviews'); break;
                                }
                            } else {
                                echo __('Pending', 'parfume-reviews');
                            }
                            ?>
                        </span>
                        <?php if (isset($scraped_data['error_message'])): ?>
                            <small class="error-message"><?php echo esc_html($scraped_data['error_message']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="scraped-data">
                    <p><em><?php _e('No scraped data yet. Save the post and add a Product URL to start scraping.', 'parfume-reviews'); ?></em></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
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
                
                // Планираме scraping за новите URL-и
                $this->schedule_product_scraping($post_id, $stores);
                
            } else {
                delete_post_meta($post_id, '_parfume_stores_v2');
            }
        }
    }
    
    private function schedule_product_scraping($post_id, $stores) {
        foreach ($stores as $index => $store) {
            if (!empty($store['product_url'])) {
                // Добавяме в опашката за scraping
                $scrape_queue = get_option('parfume_scraper_queue', array());
                
                $queue_item = array(
                    'post_id' => $post_id,
                    'store_index' => $index,
                    'url' => $store['product_url'],
                    'store_id' => $store['store_id'],
                    'added' => current_time('mysql'),
                    'status' => 'pending'
                );
                
                $scrape_queue[] = $queue_item;
                update_option('parfume_scraper_queue', $scrape_queue);
            }
        }
    }
    
    public function run_batch_scraping() {
        $settings = get_option('parfume_reviews_settings', array());
        $batch_size = isset($settings['batch_size']) ? intval($settings['batch_size']) : 10;
        
        $scrape_queue = get_option('parfume_scraper_queue', array());
        $processed = 0;
        
        foreach ($scrape_queue as $key => $item) {
            if ($processed >= $batch_size) break;
            if ($item['status'] !== 'pending') continue;
            
            $result = $this->scrape_product_url($item['url'], $item['store_id']);
            
            if ($result) {
                // Запазваме резултата
                $this->save_scraped_data($item['post_id'], $item['store_index'], $result);
                $scrape_queue[$key]['status'] = 'completed';
            } else {
                $scrape_queue[$key]['status'] = 'error';
                $scrape_queue[$key]['error_count'] = isset($item['error_count']) ? $item['error_count'] + 1 : 1;
                
                // Ако има повече от 3 грешки, премахваме от опашката
                if ($scrape_queue[$key]['error_count'] >= 3) {
                    unset($scrape_queue[$key]);
                }
            }
            
            $processed++;
            
            // Добавяме delay между заявките
            sleep(2);
        }
        
        // Почистваме завършените елементи
        $scrape_queue = array_filter($scrape_queue, function($item) {
            return $item['status'] !== 'completed';
        });
        
        update_option('parfume_scraper_queue', array_values($scrape_queue));
    }
    
    private function scrape_product_url($url, $store_id) {
        if (empty($url)) return false;
        
        // Получаваме schema за магазина
        $store_schemas = get_option('parfume_store_schemas', array());
        $schema = isset($store_schemas[$store_id]) ? $store_schemas[$store_id] : null;
        
        if (!$schema) {
            error_log("No schema found for store: " . $store_id);
            return false;
        }
        
        try {
            $settings = get_option('parfume_reviews_settings', array());
            $user_agent = isset($settings['user_agent']) ? $settings['user_agent'] : $this->user_agents[0];
            
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => $user_agent,
                'headers' => array(
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                ),
            ));
            
            if (is_wp_error($response)) {
                error_log("Scraping error for $url: " . $response->get_error_message());
                return false;
            }
            
            $html = wp_remote_retrieve_body($response);
            if (empty($html)) return false;
            
            // Парсим HTML
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            
            $scraped_data = array(
                'url' => $url,
                'scraped_at' => current_time('mysql'),
                'status' => 'success'
            );
            
            // Extractваме данни според схемата
            if (!empty($schema['price_selector'])) {
                $price_nodes = $xpath->query($schema['price_selector']);
                if ($price_nodes->length > 0) {
                    $scraped_data['price'] = trim($price_nodes->item(0)->textContent);
                }
            }
            
            if (!empty($schema['old_price_selector'])) {
                $old_price_nodes = $xpath->query($schema['old_price_selector']);
                if ($old_price_nodes->length > 0) {
                    $scraped_data['old_price'] = trim($old_price_nodes->item(0)->textContent);
                }
            }
            
            if (!empty($schema['availability_selector'])) {
                $availability_nodes = $xpath->query($schema['availability_selector']);
                if ($availability_nodes->length > 0) {
                    $scraped_data['availability'] = trim($availability_nodes->item(0)->textContent);
                }
            }
            
            if (!empty($schema['delivery_selector'])) {
                $delivery_nodes = $xpath->query($schema['delivery_selector']);
                if ($delivery_nodes->length > 0) {
                    $scraped_data['delivery'] = trim($delivery_nodes->item(0)->textContent);
                }
            }
            
            // Варианти (ml и цени)
            if (!empty($schema['variants_selector'])) {
                $variants_nodes = $xpath->query($schema['variants_selector']);
                $variants = array();
                
                foreach ($variants_nodes as $node) {
                    $ml = '';
                    $price = '';
                    
                    // Търсим ml и цена в рамките на този node
                    if (!empty($schema['variant_ml_selector'])) {
                        $ml_nodes = $xpath->query($schema['variant_ml_selector'], $node);
                        if ($ml_nodes->length > 0) {
                            $ml = trim($ml_nodes->item(0)->textContent);
                        }
                    }
                    
                    if (!empty($schema['variant_price_selector'])) {
                        $price_nodes = $xpath->query($schema['variant_price_selector'], $node);
                        if ($price_nodes->length > 0) {
                            $price = trim($price_nodes->item(0)->textContent);
                        }
                    }
                    
                    if (!empty($ml) && !empty($price)) {
                        $variants[] = array(
                            'ml' => $ml,
                            'price' => $price
                        );
                    }
                }
                
                if (!empty($variants)) {
                    $scraped_data['variants'] = $variants;
                }
            }
            
            return $scraped_data;
            
        } catch (Exception $e) {
            error_log("Scraping exception for $url: " . $e->getMessage());
            return false;
        }
    }
    
    private function save_scraped_data($post_id, $store_index, $data) {
        $meta_key = '_store_scraped_' . $store_index;
        
        // Добавяме timestamps
        $data['last_updated'] = current_time('mysql');
        $settings = get_option('parfume_reviews_settings', array());
        $interval = isset($settings['scrape_interval']) ? intval($settings['scrape_interval']) : 24;
        $data['next_update'] = date('Y-m-d H:i:s', strtotime('+' . $interval . ' hours'));
        
        update_post_meta($post_id, $meta_key, $data);
    }
    
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
    
    public function test_scraper_url() {
        check_ajax_referer('parfume-scraper-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $url = esc_url_raw($_POST['url']);
        
        if (empty($url)) {
            wp_send_json_error(__('URL is required', 'parfume-reviews'));
        }
        
        // Правим опростен scrape за тестване
        $result = $this->analyze_page_structure($url);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Failed to analyze page', 'parfume-reviews'));
        }
    }
    
    private function analyze_page_structure($url) {
        // Тази функция анализира структурата на страницата и предлага селектори
        // Това е опростена версия - в реалността би трябвало по-сложна логика
        
        try {
            $settings = get_option('parfume_reviews_settings', array());
            $user_agent = isset($settings['user_agent']) ? $settings['user_agent'] : $this->user_agents[0];
            
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => $user_agent,
            ));
            
            if (is_wp_error($response)) {
                return false;
            }
            
            $html = wp_remote_retrieve_body($response);
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            
            $analysis = array(
                'url' => $url,
                'title' => '',
                'potential_prices' => array(),
                'potential_availability' => array(),
                'potential_variants' => array(),
            );
            
            // Търсим заглавие
            $title_nodes = $xpath->query('//title');
            if ($title_nodes->length > 0) {
                $analysis['title'] = trim($title_nodes->item(0)->textContent);
            }
            
            // Търсим потенциални цени (елементи с числа и валутни символи)
            $price_patterns = array(
                '//span[contains(@class, "price")]',
                '//div[contains(@class, "price")]',
                '//*[contains(text(), "лв")]',
                '//*[contains(text(), "BGN")]',
                '//*[contains(text(), "€")]',
            );
            
            foreach ($price_patterns as $pattern) {
                $nodes = $xpath->query($pattern);
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (preg_match('/[\d,.]+ ?(лв|BGN|€)/', $text)) {
                        $analysis['potential_prices'][] = array(
                            'text' => $text,
                            'selector' => $this->get_css_selector($node),
                            'xpath' => $node->getNodePath()
                        );
                    }
                }
            }
            
            // Търсим потенциална наличност
            $availability_patterns = array(
                '//*[contains(text(), "наличен")]',
                '//*[contains(text(), "в наличност")]',
                '//*[contains(text(), "available")]',
                '//span[contains(@class, "stock")]',
                '//div[contains(@class, "availability")]',
            );
            
            foreach ($availability_patterns as $pattern) {
                $nodes = $xpath->query($pattern);
                foreach ($nodes as $node) {
                    $analysis['potential_availability'][] = array(
                        'text' => trim($node->textContent),
                        'selector' => $this->get_css_selector($node),
                        'xpath' => $node->getNodePath()
                    );
                }
            }
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log("Page analysis error: " . $e->getMessage());
            return false;
        }
    }
    
    private function get_css_selector($node) {
        // Опростена функция за генериране на CSS селектор
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
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Product Scraper Monitor', 'parfume-reviews'); ?></h1>
            
            <?php $this->render_scraper_stats(); ?>
            <?php $this->render_scraper_queue(); ?>
            <?php $this->render_scraped_products_table(); ?>
        </div>
        <?php
    }
    
    private function render_scraper_stats() {
        $scrape_queue = get_option('parfume_scraper_queue', array());
        $pending_count = count(array_filter($scrape_queue, function($item) { return $item['status'] === 'pending'; }));
        $error_count = count(array_filter($scrape_queue, function($item) { return $item['status'] === 'error'; }));
        
        ?>
        <div class="scraper-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
            <div class="stat-box" style="background: #f0f0f1; padding: 20px; border-radius: 5px;">
                <h3><?php _e('Pending', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #0073aa;"><?php echo $pending_count; ?></span>
            </div>
            
            <div class="stat-box" style="background: #f0f0f1; padding: 20px; border-radius: 5px;">
                <h3><?php _e('Errors', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #dc3232;"><?php echo $error_count; ?></span>
            </div>
            
            <div class="stat-box" style="background: #f0f0f1; padding: 20px; border-radius: 5px;">
                <h3><?php _e('Total Queue', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #46b450;"><?php echo count($scrape_queue); ?></span>
            </div>
        </div>
        <?php
    }
    
    private function render_scraper_queue() {
        $scrape_queue = get_option('parfume_scraper_queue', array());
        
        ?>
        <h2><?php _e('Scraper Queue', 'parfume-reviews'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Post', 'parfume-reviews'); ?></th>
                    <th><?php _e('Store', 'parfume-reviews'); ?></th>
                    <th><?php _e('URL', 'parfume-reviews'); ?></th>
                    <th><?php _e('Status', 'parfume-reviews'); ?></th>
                    <th><?php _e('Added', 'parfume-reviews'); ?></th>
                    <th><?php _e('Actions', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($scrape_queue)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No items in queue', 'parfume-reviews'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($scrape_queue as $item): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($item['post_id']); ?>">
                                    <?php echo get_the_title($item['post_id']); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($item['store_id']); ?></td>
                            <td>
                                <a href="<?php echo esc_url($item['url']); ?>" target="_blank">
                                    <?php echo esc_html(wp_trim_words($item['url'], 8)); ?>
                                </a>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($item['status']); ?>">
                                    <?php echo esc_html(ucfirst($item['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($item['added']); ?></td>
                            <td>
                                <button class="button button-small manual-scrape-queue" 
                                        data-post-id="<?php echo esc_attr($item['post_id']); ?>" 
                                        data-store-index="<?php echo esc_attr($item['store_index']); ?>">
                                    <?php _e('Scrape Now', 'parfume-reviews'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    private function render_scraped_products_table() {
        // Получаваме всички постове с scraped data
        $posts_with_stores = get_posts(array(
            'post_type' => 'parfume',
            'meta_key' => '_parfume_stores_v2',
            'posts_per_page' => 50,
            'post_status' => 'publish'
        ));
        
        ?>
        <h2><?php _e('Scraped Products', 'parfume-reviews'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Post', 'parfume-reviews'); ?></th>
                    <th><?php _e('Stores', 'parfume-reviews'); ?></th>
                    <th><?php _e('Last Scraped', 'parfume-reviews'); ?></th>
                    <th><?php _e('Status', 'parfume-reviews'); ?></th>
                    <th><?php _e('Actions', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts_with_stores)): ?>
                    <tr>
                        <td colspan="5"><?php _e('No products with stores found', 'parfume-reviews'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts_with_stores as $post): ?>
                        <?php
                        $stores = get_post_meta($post->ID, '_parfume_stores_v2', true);
                        $store_count = is_array($stores) ? count($stores) : 0;
                        
                        // Получаваме последно scraping време
                        $last_scraped = '';
                        $status = 'pending';
                        
                        for ($i = 0; $i < $store_count; $i++) {
                            $scraped_data = get_post_meta($post->ID, '_store_scraped_' . $i, true);
                            if (!empty($scraped_data['last_updated'])) {
                                if (empty($last_scraped) || $scraped_data['last_updated'] > $last_scraped) {
                                    $last_scraped = $scraped_data['last_updated'];
                                }
                                if (isset($scraped_data['status'])) {
                                    $status = $scraped_data['status'];
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                    <?php echo esc_html($post->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo $store_count; ?> stores</td>
                            <td><?php echo $last_scraped ? esc_html($last_scraped) : __('Never', 'parfume-reviews'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html(ucfirst($status)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small">
                                    <?php _e('Edit', 'parfume-reviews'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    public function render_test_tool_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Scraper Test Tool', 'parfume-reviews'); ?></h1>
            
            <div class="scraper-test-form">
                <h2><?php _e('Test URL Analysis', 'parfume-reviews'); ?></h2>
                
                <form id="test-scraper-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test_url"><?php _e('Product URL', 'parfume-reviews'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="test_url" class="large-text" placeholder="https://example.com/product-page">
                                <button type="submit" class="button button-primary"><?php _e('Analyze Page', 'parfume-reviews'); ?></button>
                            </td>
                        </tr>
                    </table>
                </form>
                
                <div id="analysis-results" style="display: none;">
                    <h3><?php _e('Analysis Results', 'parfume-reviews'); ?></h3>
                    <div id="analysis-content"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-scraper-form').on('submit', function(e) {
                e.preventDefault();
                
                var url = $('#test_url').val();
                if (!url) return;
                
                $('#analysis-results').show();
                $('#analysis-content').html('<p>Analyzing...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_scraper_url',
                        url: url,
                        nonce: '<?php echo wp_create_nonce('parfume-scraper-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<h4>Page Title: ' + response.data.title + '</h4>';
                            
                            if (response.data.potential_prices.length > 0) {
                                html += '<h5>Potential Prices Found:</h5><ul>';
                                response.data.potential_prices.forEach(function(price) {
                                    html += '<li><strong>' + price.text + '</strong><br>';
                                    html += 'CSS: <code>' + price.selector + '</code><br>';
                                    html += 'XPath: <code>' + price.xpath + '</code></li>';
                                });
                                html += '</ul>';
                            }
                            
                            if (response.data.potential_availability.length > 0) {
                                html += '<h5>Potential Availability:</h5><ul>';
                                response.data.potential_availability.forEach(function(avail) {
                                    html += '<li><strong>' + avail.text + '</strong><br>';
                                    html += 'CSS: <code>' + avail.selector + '</code></li>';
                                });
                                html += '</ul>';
                            }
                            
                            $('#analysis-content').html(html);
                        } else {
                            $('#analysis-content').html('<p style="color: red;">Error: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#analysis-content').html('<p style="color: red;">AJAX Error</p>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
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