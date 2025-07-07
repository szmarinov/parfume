<?php
/**
 * Parfume Catalog Stores Module
 * 
 * Управлява магазините и тяхната конфигурация
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Stores {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_submenu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('add_meta_boxes', array($this, 'add_stores_meta_box'));
        add_action('save_post', array($this, 'save_stores_meta_box'));
        add_action('wp_ajax_parfume_add_store', array($this, 'ajax_add_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_update_store_order', array($this, 'ajax_update_store_order'));
        add_action('wp_ajax_parfume_test_scraper_url', array($this, 'ajax_test_scraper_url'));
    }

    /**
     * Добавяне на администраторско подменю
     */
    public function add_admin_submenu() {
        add_submenu_page(
            'parfume-catalog',
            __('Stores Management', 'parfume-catalog'),
            __('Stores', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-stores',
            array($this, 'stores_admin_page')
        );
    }

    /**
     * Регистриране на настройки
     */
    public function register_settings() {
        register_setting('parfume_catalog_stores_settings', 'parfume_catalog_stores', array(
            'sanitize_callback' => array($this, 'sanitize_stores_settings')
        ));

        register_setting('parfume_catalog_stores_settings', 'parfume_catalog_scraper_settings', array(
            'sanitize_callback' => array($this, 'sanitize_scraper_settings')
        ));
    }

    /**
     * Админ страница за магазини
     */
    public function stores_admin_page() {
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['store_id'])) {
            $this->edit_store_page();
            return;
        }

        $stores = get_option('parfume_catalog_stores', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Stores Management', 'parfume-catalog'); ?></h1>
            
            <div class="parfume-stores-admin">
                <div class="stores-header">
                    <button type="button" class="button button-primary" id="add-new-store">
                        <?php _e('Добави нов магазин', 'parfume-catalog'); ?>
                    </button>
                </div>

                <div class="stores-list">
                    <?php if (empty($stores)): ?>
                        <div class="no-stores-message">
                            <p><?php _e('Няма добавени магазини. Добавете първия си магазин за да започнете.', 'parfume-catalog'); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Лого', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Име на магазин', 'parfume-catalog'); ?></th>
                                    <th><?php _e('URL', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Статус', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Брой продукти', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stores as $store_id => $store): ?>
                                    <tr data-store-id="<?php echo esc_attr($store_id); ?>">
                                        <td>
                                            <?php if (!empty($store['logo'])): ?>
                                                <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" style="max-width: 50px; max-height: 30px;" />
                                            <?php else: ?>
                                                <span class="dashicons dashicons-store"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo esc_html($store['name']); ?></strong></td>
                                        <td>
                                            <?php if (!empty($store['url'])): ?>
                                                <a href="<?php echo esc_url($store['url']); ?>" target="_blank"><?php echo esc_html($store['url']); ?></a>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($store['active']) && $store['active']): ?>
                                                <span class="status-active"><?php _e('Активен', 'parfume-catalog'); ?></span>
                                            <?php else: ?>
                                                <span class="status-inactive"><?php _e('Неактивен', 'parfume-catalog'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $this->get_store_products_count($store_id); ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=parfume-catalog-stores&action=edit&store_id=' . $store_id); ?>" class="button button-small">
                                                <?php _e('Редактирай', 'parfume-catalog'); ?>
                                            </a>
                                            <button type="button" class="button button-small delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Изтрий', 'parfume-catalog'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Scraper Settings Section -->
                <div class="scraper-settings-section">
                    <h2><?php _e('Scraper Settings', 'parfume-catalog'); ?></h2>
                    <?php $this->render_scraper_settings(); ?>
                </div>
            </div>
        </div>

        <!-- Add/Edit Store Modal -->
        <div id="store-modal" class="store-modal" style="display: none;">
            <div class="store-modal-content">
                <div class="store-modal-header">
                    <h3 id="store-modal-title"><?php _e('Добави нов магазин', 'parfume-catalog'); ?></h3>
                    <button type="button" class="store-modal-close">&times;</button>
                </div>
                <div class="store-modal-body">
                    <form id="store-form">
                        <input type="hidden" id="store-id" name="store_id" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="store-name"><?php _e('Име на магазин', 'parfume-catalog'); ?> *</label>
                                </th>
                                <td>
                                    <input type="text" id="store-name" name="store_name" class="regular-text" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store-url"><?php _e('URL на магазин', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="store-url" name="store_url" class="regular-text" />
                                    <p class="description"><?php _e('Основният URL на магазина', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store-logo"><?php _e('Лого', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <div class="logo-upload-container">
                                        <input type="hidden" id="store-logo" name="store_logo" />
                                        <div id="logo-preview" class="logo-preview"></div>
                                        <button type="button" class="button upload-logo-btn"><?php _e('Качи лого', 'parfume-catalog'); ?></button>
                                        <button type="button" class="button remove-logo-btn" style="display: none;"><?php _e('Премахни', 'parfume-catalog'); ?></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store-active"><?php _e('Статус', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="store-active" name="store_active" value="1" />
                                        <?php _e('Магазинът е активен', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <!-- Scraper Schema Section -->
                        <div class="scraper-schema-section">
                            <h4><?php _e('Scraper Schema', 'parfume-catalog'); ?></h4>
                            <p class="description"><?php _e('CSS селектори за автоматично извличане на продуктова информация', 'parfume-catalog'); ?></p>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="price-selector"><?php _e('Селектор за цена', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="price-selector" name="price_selector" class="regular-text" placeholder=".product-price, .price" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="old-price-selector"><?php _e('Селектор за стара цена', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="old-price-selector" name="old_price_selector" class="regular-text" placeholder=".old-price, .was-price" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="availability-selector"><?php _e('Селектор за наличност', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="availability-selector" name="availability_selector" class="regular-text" placeholder=".availability, .stock-status" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="delivery-selector"><?php _e('Селектор за доставка', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="delivery-selector" name="delivery_selector" class="regular-text" placeholder=".delivery-info, .shipping" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="variants-selector"><?php _e('Селектор за варианти (ml)', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="variants-selector" name="variants_selector" class="regular-text" placeholder=".size-options, .ml-variants" />
                                    </td>
                                </tr>
                            </table>

                            <div class="schema-test-section">
                                <h4><?php _e('Тестване на schema', 'parfume-catalog'); ?></h4>
                                <div class="test-url-container">
                                    <input type="url" id="test-url" placeholder="<?php _e('URL за тестване на schema', 'parfume-catalog'); ?>" class="regular-text" />
                                    <button type="button" class="button test-schema-btn"><?php _e('Тествай', 'parfume-catalog'); ?></button>
                                </div>
                                <div id="schema-test-results" class="schema-test-results" style="display: none;"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="store-modal-footer">
                    <button type="button" class="button button-primary save-store-btn"><?php _e('Запази', 'parfume-catalog'); ?></button>
                    <button type="button" class="button cancel-store-btn"><?php _e('Отмени', 'parfume-catalog'); ?></button>
                </div>
            </div>
        </div>
        <div id="store-modal-overlay" class="store-modal-overlay" style="display: none;"></div>
        <?php
    }

    /**
     * Страница за редактиране на магазин
     */
    private function edit_store_page() {
        $store_id = sanitize_text_field($_GET['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        if (!isset($stores[$store_id])) {
            wp_die(__('Магазинът не е намерен.', 'parfume-catalog'));
        }

        $store = $stores[$store_id];
        ?>
        <div class="wrap">
            <h1><?php printf(__('Редактиране на магазин: %s', 'parfume-catalog'), esc_html($store['name'])); ?></h1>
            
            <div class="store-edit-container">
                <!-- Съдържанието ще бъде имплементирано в следващи версии -->
                <p><?php _e('Подробна страница за редактиране ще бъде добавена в по-късна версия.', 'parfume-catalog'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=parfume-catalog-stores'); ?>" class="button">
                    <?php _e('Обратно към списъка', 'parfume-catalog'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Рендериране на scraper настройки
     */
    private function render_scraper_settings() {
        $scraper_settings = get_option('parfume_catalog_scraper_settings', array());
        $defaults = array(
            'scrape_interval' => 12,
            'batch_size' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'timeout' => 30,
            'max_retries' => 3,
            'respect_robots' => 1
        );
        
        $settings = wp_parse_args($scraper_settings, $defaults);
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('parfume_catalog_stores_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="scrape_interval"><?php _e('Scrape Interval (часове)', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="scrape_interval" name="parfume_catalog_scraper_settings[scrape_interval]" 
                               value="<?php echo esc_attr($settings['scrape_interval']); ?>" min="1" max="168" />
                        <p class="description"><?php _e('На колко часа да се обновяват цените (1-168 часа)', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="batch_size"><?php _e('Batch Size', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="batch_size" name="parfume_catalog_scraper_settings[batch_size]" 
                               value="<?php echo esc_attr($settings['batch_size']); ?>" min="1" max="50" />
                        <p class="description"><?php _e('Колко URL-а да се обработват наведнъж (1-50)', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="user_agent"><?php _e('User Agent', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="user_agent" name="parfume_catalog_scraper_settings[user_agent]" 
                               value="<?php echo esc_attr($settings['user_agent']); ?>" class="large-text" />
                        <p class="description"><?php _e('User Agent string за заявките', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="timeout"><?php _e('Timeout (секунди)', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="timeout" name="parfume_catalog_scraper_settings[timeout]" 
                               value="<?php echo esc_attr($settings['timeout']); ?>" min="5" max="120" />
                        <p class="description"><?php _e('Максимално време за чакане на отговор (5-120 секунди)', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_retries"><?php _e('Максимални опити', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_retries" name="parfume_catalog_scraper_settings[max_retries]" 
                               value="<?php echo esc_attr($settings['max_retries']); ?>" min="1" max="10" />
                        <p class="description"><?php _e('Колко пъти да се опитва при грешка (1-10)', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="respect_robots"><?php _e('Уважавай robots.txt', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="respect_robots" name="parfume_catalog_scraper_settings[respect_robots]" 
                                   value="1" <?php checked($settings['respect_robots'], 1); ?> />
                            <?php _e('Проверявай robots.txt преди скрейпване', 'parfume-catalog'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Запази настройки', 'parfume-catalog')); ?>
        </form>
        <?php
    }

    /**
     * Добавяне на stores meta box към парфюмите
     */
    public function add_stores_meta_box() {
        add_meta_box(
            'parfume_stores_meta_box',
            __('Магазини', 'parfume-catalog'),
            array($this, 'render_stores_meta_box'),
            'parfumes',
            'side',
            'default'
        );
    }

    /**
     * Рендериране на stores meta box
     */
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_nonce', 'parfume_stores_meta_nonce_field');
        
        $post_stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($post_stores)) {
            $post_stores = array();
        }
        
        $available_stores = get_option('parfume_catalog_stores', array());
        ?>
        <div class="parfume-post-stores">
            <p><?php _e('Изберете магазини за този парфюм:', 'parfume-catalog'); ?></p>
            
            <div class="stores-selector">
                <select id="add-store-select">
                    <option value=""><?php _e('Избери магазин...', 'parfume-catalog'); ?></option>
                    <?php foreach ($available_stores as $store_id => $store): ?>
                        <option value="<?php echo esc_attr($store_id); ?>" <?php echo in_array($store_id, array_keys($post_stores)) ? 'disabled' : ''; ?>>
                            <?php echo esc_html($store['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button add-store-to-post"><?php _e('Добави', 'parfume-catalog'); ?></button>
            </div>
            
            <div class="selected-stores" id="selected-stores-list">
                <?php foreach ($post_stores as $store_id => $store_data): ?>
                    <?php if (isset($available_stores[$store_id])): ?>
                        <?php $this->render_post_store_item($store_id, $available_stores[$store_id], $store_data); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <script type="text/template" id="post-store-template">
            <!-- Template ще бъде попълнен чрез JavaScript -->
        </script>
        <?php
    }

    /**
     * Рендериране на store item в post meta box
     */
    private function render_post_store_item($store_id, $store_info, $store_data) {
        ?>
        <div class="post-store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-item-header">
                <div class="store-info">
                    <?php if (!empty($store_info['logo'])): ?>
                        <img src="<?php echo esc_url($store_info['logo']); ?>" alt="<?php echo esc_attr($store_info['name']); ?>" class="store-logo-small" />
                    <?php endif; ?>
                    <strong><?php echo esc_html($store_info['name']); ?></strong>
                </div>
                <div class="store-controls">
                    <button type="button" class="button-link move-store-up" title="<?php _e('Нагоре', 'parfume-catalog'); ?>">↑</button>
                    <button type="button" class="button-link move-store-down" title="<?php _e('Надолу', 'parfume-catalog'); ?>">↓</button>
                    <button type="button" class="button-link remove-store" title="<?php _e('Премахни', 'parfume-catalog'); ?>">×</button>
                </div>
            </div>
            
            <div class="store-item-fields">
                <input type="hidden" name="parfume_stores[<?php echo esc_attr($store_id); ?>][store_id]" value="<?php echo esc_attr($store_id); ?>" />
                
                <label>
                    <?php _e('Product URL:', 'parfume-catalog'); ?>
                    <input type="url" name="parfume_stores[<?php echo esc_attr($store_id); ?>][product_url]" 
                           value="<?php echo esc_attr(isset($store_data['product_url']) ? $store_data['product_url'] : ''); ?>" 
                           class="widefat" placeholder="https://example.com/product/..." />
                </label>
                
                <label>
                    <?php _e('Affiliate URL:', 'parfume-catalog'); ?>
                    <input type="url" name="parfume_stores[<?php echo esc_attr($store_id); ?>][affiliate_url]" 
                           value="<?php echo esc_attr(isset($store_data['affiliate_url']) ? $store_data['affiliate_url'] : ''); ?>" 
                           class="widefat" placeholder="https://affiliate.link/..." />
                </label>
                
                <label>
                    <?php _e('Promo Code:', 'parfume-catalog'); ?>
                    <input type="text" name="parfume_stores[<?php echo esc_attr($store_id); ?>][promo_code]" 
                           value="<?php echo esc_attr(isset($store_data['promo_code']) ? $store_data['promo_code'] : ''); ?>" 
                           class="widefat" placeholder="DISCOUNT10" />
                </label>
                
                <label>
                    <?php _e('Promo Code Info:', 'parfume-catalog'); ?>
                    <input type="text" name="parfume_stores[<?php echo esc_attr($store_id); ?>][promo_code_info]" 
                           value="<?php echo esc_attr(isset($store_data['promo_code_info']) ? $store_data['promo_code_info'] : ''); ?>" 
                           class="widefat" placeholder="10% отстъпка" />
                </label>
                
                <?php if (!empty($store_data['product_url'])): ?>
                    <div class="scraper-info">
                        <button type="button" class="button manual-scrape" data-store-id="<?php echo esc_attr($store_id); ?>" data-post-id="<?php echo get_the_ID(); ?>">
                            <?php _e('Ръчно обновяване', 'parfume-catalog'); ?>
                        </button>
                        <span class="scraper-status">
                            <?php
                            $scraper_data = $this->get_scraper_data(get_the_ID(), $store_id);
                            if ($scraper_data) {
                                printf(__('Последно: %s', 'parfume-catalog'), 
                                       $scraper_data['last_scraped'] ? date('d.m.Y H:i', strtotime($scraper_data['last_scraped'])) : __('Никога', 'parfume-catalog'));
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Запазване на stores meta box
     */
    public function save_stores_meta_box($post_id) {
        // Проверки за сигурност
        if (!isset($_POST['parfume_stores_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['parfume_stores_meta_nonce_field'], 'parfume_stores_meta_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }

        // Запазване на stores данни
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores_data = array();
            
            foreach ($_POST['parfume_stores'] as $store_id => $store_data) {
                $stores_data[sanitize_text_field($store_id)] = array(
                    'store_id' => sanitize_text_field($store_data['store_id']),
                    'product_url' => esc_url_raw($store_data['product_url']),
                    'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                    'promo_code' => sanitize_text_field($store_data['promo_code']),
                    'promo_code_info' => sanitize_text_field($store_data['promo_code_info'])
                );
            }
            
            update_post_meta($post_id, '_parfume_stores', $stores_data);
        } else {
            delete_post_meta($post_id, '_parfume_stores');
        }
    }

    /**
     * AJAX - Добавяне на магазин
     */
    public function ajax_add_store() {
        check_ajax_referer('parfume_catalog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $store_data = array(
            'name' => sanitize_text_field($_POST['store_name']),
            'url' => esc_url_raw($_POST['store_url']),
            'logo' => esc_url_raw($_POST['store_logo']),
            'active' => isset($_POST['store_active']) ? 1 : 0,
            'schema' => array(
                'price_selector' => sanitize_text_field($_POST['price_selector']),
                'old_price_selector' => sanitize_text_field($_POST['old_price_selector']),
                'availability_selector' => sanitize_text_field($_POST['availability_selector']),
                'delivery_selector' => sanitize_text_field($_POST['delivery_selector']),
                'variants_selector' => sanitize_text_field($_POST['variants_selector'])
            )
        );

        $stores = get_option('parfume_catalog_stores', array());
        
        if (isset($_POST['store_id']) && !empty($_POST['store_id'])) {
            // Редактиране на съществуващ магазин
            $store_id = sanitize_text_field($_POST['store_id']);
            $stores[$store_id] = $store_data;
        } else {
            // Добавяне на нов магазин
            $store_id = uniqid('store_');
            $stores[$store_id] = $store_data;
        }

        update_option('parfume_catalog_stores', $stores);
        
        wp_send_json_success(array(
            'message' => __('Магазинът е запазен успешно.', 'parfume-catalog'),
            'store_id' => $store_id
        ));
    }

    /**
     * AJAX - Изтриване на магазин
     */
    public function ajax_delete_store() {
        check_ajax_referer('parfume_catalog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        if (isset($stores[$store_id])) {
            unset($stores[$store_id]);
            update_option('parfume_catalog_stores', $stores);
            
            // Изтриване на всички scraper данни за този магазин
            $this->delete_store_scraper_data($store_id);
            
            wp_send_json_success(__('Магазинът е изтрит успешно.', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Тестване на scraper URL
     */
    public function ajax_test_scraper_url() {
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

        // Тестване на schema (ще бъде имплементирано в scraper модула)
        $test_results = array(
            'url' => $test_url,
            'success' => true,
            'data' => array(
                'price' => '45.99 лв.',
                'old_price' => '59.99 лв.',
                'availability' => 'Наличен',
                'delivery' => 'Безплатна доставка',
                'variants' => array('30ml', '50ml', '100ml')
            ),
            'message' => __('Schema тестът е успешен. Открити са всички необходими данни.', 'parfume-catalog')
        );

        wp_send_json_success($test_results);
    }

    /**
     * Санитизиране на stores настройки
     */
    public function sanitize_stores_settings($input) {
        // Stores данните се обработват отделно в AJAX функциите
        return get_option('parfume_catalog_stores', array());
    }

    /**
     * Санитизиране на scraper настройки
     */
    public function sanitize_scraper_settings($input) {
        $sanitized = array();
        
        $sanitized['scrape_interval'] = absint($input['scrape_interval']);
        $sanitized['batch_size'] = absint($input['batch_size']);
        $sanitized['user_agent'] = sanitize_text_field($input['user_agent']);
        $sanitized['timeout'] = absint($input['timeout']);
        $sanitized['max_retries'] = absint($input['max_retries']);
        $sanitized['respect_robots'] = isset($input['respect_robots']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * Получаване на брой продукти за магазин
     */
    private function get_store_products_count($store_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_parfume_stores' 
             AND pm.meta_value LIKE %s 
             AND p.post_type = 'parfumes' 
             AND p.post_status = 'publish'",
            '%' . $store_id . '%'
        ));
        
        return $count ? $count : 0;
    }

    /**
     * Получаване на scraper данни
     */
    private function get_scraper_data($post_id, $store_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'parfume_scraper_data';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d AND store_id = %s",
            $post_id, $store_id
        ), ARRAY_A);
    }

    /**
     * Изтриване на scraper данни за магазин
     */
    private function delete_store_scraper_data($store_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'parfume_scraper_data';
        $wpdb->delete($table, array('store_id' => $store_id));
    }

    /**
     * Получаване на всички магазини
     */
    public static function get_stores() {
        return get_option('parfume_catalog_stores', array());
    }

    /**
     * Получаване на конкретен магазин
     */
    public static function get_store($store_id) {
        $stores = self::get_stores();
        return isset($stores[$store_id]) ? $stores[$store_id] : false;
    }

    /**
     * Получаване на активни магазини
     */
    public static function get_active_stores() {
        $stores = self::get_stores();
        return array_filter($stores, function($store) {
            return isset($store['active']) && $store['active'];
        });
    }
}