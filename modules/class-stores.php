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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Добавяне на администраторско подменю
     */
    public function add_admin_submenu() {
        add_submenu_page(
            'edit.php?post_type=parfumes',
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
        
        register_setting('parfume_catalog_stores_settings', 'parfume_catalog_mobile_settings', array(
            'sanitize_callback' => array($this, 'sanitize_mobile_settings')
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
                                            <?php if (!empty($store['logo_id'])): ?>
                                                <?php echo wp_get_attachment_image($store['logo_id'], array(50, 30), false, array('alt' => esc_attr($store['name']))); ?>
                                            <?php else: ?>
                                                <span class="dashicons dashicons-store"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo esc_html($store['name']); ?></strong></td>
                                        <td>
                                            <?php if (!empty($store['url'])): ?>
                                                <a href="<?php echo esc_url($store['url']); ?>" target="_blank"><?php echo esc_html($store['url']); ?></a>
                                            <?php else: ?>
                                                <span class="text-muted"><?php _e('Няма URL', 'parfume-catalog'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($store['active']) && $store['active']): ?>
                                                <span class="status-active"><?php _e('Активен', 'parfume-catalog'); ?></span>
                                            <?php else: ?>
                                                <span class="status-inactive"><?php _e('Неактивен', 'parfume-catalog'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $this->get_store_products_count($store_id); ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo add_query_arg(array('action' => 'edit', 'store_id' => $store_id)); ?>" class="button button-small">
                                                <?php _e('Редактирай', 'parfume-catalog'); ?>
                                            </a>
                                            <button type="button" class="button button-small button-secondary delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Изтрий', 'parfume-catalog'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add Store Modal -->
            <div id="add-store-modal" class="parfume-modal" style="display: none;">
                <div class="parfume-modal-content">
                    <div class="parfume-modal-header">
                        <h2><?php _e('Добави нов магазин', 'parfume-catalog'); ?></h2>
                        <span class="parfume-modal-close">&times;</span>
                    </div>
                    <div class="parfume-modal-body">
                        <form id="add-store-form">
                            <?php wp_nonce_field('parfume_catalog_admin_nonce', 'nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="store_name"><?php _e('Име на магазин', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="store_name" name="store_name" class="regular-text" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="store_url"><?php _e('URL на магазин', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="url" id="store_url" name="store_url" class="regular-text" placeholder="https://example.com" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="store_logo"><?php _e('Лого', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <div class="logo-upload-container">
                                            <input type="hidden" id="store_logo_id" name="store_logo_id" />
                                            <div class="logo-preview" id="logo-preview" style="display: none;">
                                                <img src="" alt="" style="max-width: 150px; max-height: 100px;" />
                                                <button type="button" class="button remove-logo"><?php _e('Премахни', 'parfume-catalog'); ?></button>
                                            </div>
                                            <button type="button" class="button upload-logo"><?php _e('Избери лого', 'parfume-catalog'); ?></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="store_active"><?php _e('Статус', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="store_active" name="store_active" value="1" checked />
                                            <?php _e('Активен магазин', 'parfume-catalog'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="parfume-modal-footer">
                        <button type="button" class="button button-primary" id="save-store"><?php _e('Запази', 'parfume-catalog'); ?></button>
                        <button type="button" class="button" id="cancel-store"><?php _e('Отказ', 'parfume-catalog'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Страница за редактиране на магазин
     */
    public function edit_store_page() {
        $store_id = sanitize_text_field($_GET['store_id']);
        $stores = get_option('parfume_catalog_stores', array());
        
        if (!isset($stores[$store_id])) {
            wp_die(__('Магазинът не е намерен.', 'parfume-catalog'));
        }
        
        $store = $stores[$store_id];
        ?>
        <div class="wrap">
            <h1><?php printf(__('Редактиране на магазин: %s', 'parfume-catalog'), esc_html($store['name'])); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('edit_store_' . $store_id, 'store_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="store_name"><?php _e('Име на магазин', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="store_name" name="store_name" value="<?php echo esc_attr($store['name']); ?>" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="store_url"><?php _e('URL на магазин', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="store_url" name="store_url" value="<?php echo esc_attr($store['url']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="store_logo"><?php _e('Лого', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <div class="logo-upload-container">
                                <input type="hidden" id="store_logo_id" name="store_logo_id" value="<?php echo esc_attr($store['logo_id'] ?? ''); ?>" />
                                <div class="logo-preview" id="logo-preview" <?php echo empty($store['logo_id']) ? 'style="display: none;"' : ''; ?>>
                                    <?php if (!empty($store['logo_id'])): ?>
                                        <?php echo wp_get_attachment_image($store['logo_id'], array(150, 100)); ?>
                                    <?php endif; ?>
                                    <button type="button" class="button remove-logo"><?php _e('Премахни', 'parfume-catalog'); ?></button>
                                </div>
                                <button type="button" class="button upload-logo"><?php _e('Избери лого', 'parfume-catalog'); ?></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="store_active"><?php _e('Статус', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="store_active" name="store_active" value="1" <?php checked(isset($store['active']) ? $store['active'] : false, true); ?> />
                                <?php _e('Активен магазин', 'parfume-catalog'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Обнови магазин', 'parfume-catalog')); ?>
            </form>
            
            <hr />
            
            <h2><?php _e('Scraper схема за този магазин', 'parfume-catalog'); ?></h2>
            <div class="store-schema-section">
                <?php $this->render_store_schema_settings($store_id); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Рендериране на schema настройки за магазин
     */
    private function render_store_schema_settings($store_id) {
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        $schema = isset($schemas[$store_id]) ? $schemas[$store_id] : array();
        ?>
        <div class="schema-settings">
            <p><?php _e('Конфигурирайте CSS селекторите за автоматично извличане на данни от този магазин.', 'parfume-catalog'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="price_selector"><?php _e('Селектор за цена', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="price_selector" name="schema[price_selector]" 
                               value="<?php echo esc_attr($schema['price_selector'] ?? ''); ?>" 
                               class="regular-text" placeholder=".price, .current-price" />
                        <p class="description"><?php _e('CSS селектор за текущата цена на продукта', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="old_price_selector"><?php _e('Селектор за стара цена', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="old_price_selector" name="schema[old_price_selector]" 
                               value="<?php echo esc_attr($schema['old_price_selector'] ?? ''); ?>" 
                               class="regular-text" placeholder=".old-price, .was-price" />
                        <p class="description"><?php _e('CSS селектор за старата/зачеркната цена', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ml_selector"><?php _e('Селектор за милилитри', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ml_selector" name="schema[ml_selector]" 
                               value="<?php echo esc_attr($schema['ml_selector'] ?? ''); ?>" 
                               class="regular-text" placeholder=".ml-options, .size-selector" />
                        <p class="description"><?php _e('CSS селектор за различните размери/милилитри', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="availability_selector"><?php _e('Селектор за наличност', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="availability_selector" name="schema[availability_selector]" 
                               value="<?php echo esc_attr($schema['availability_selector'] ?? ''); ?>" 
                               class="regular-text" placeholder=".stock-status, .availability" />
                        <p class="description"><?php _e('CSS селектор за статус на наличност', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="delivery_selector"><?php _e('Селектор за доставка', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="delivery_selector" name="schema[delivery_selector]" 
                               value="<?php echo esc_attr($schema['delivery_selector'] ?? ''); ?>" 
                               class="regular-text" placeholder=".shipping-info, .delivery" />
                        <p class="description"><?php _e('CSS селектор за информация за доставка', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="button" class="button button-primary" id="save-schema" data-store-id="<?php echo esc_attr($store_id); ?>">
                    <?php _e('Запази схема', 'parfume-catalog'); ?>
                </button>
                <button type="button" class="button" id="test-schema" data-store-id="<?php echo esc_attr($store_id); ?>">
                    <?php _e('Тествай схема', 'parfume-catalog'); ?>
                </button>
            </p>
        </div>
        <?php
    }

    /**
     * Добавяне на stores meta box към парфюми
     */
    public function add_stores_meta_box() {
        add_meta_box(
            'parfume_stores',
            __('Магазини и оферти', 'parfume-catalog'),
            array($this, 'render_stores_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
    }

    /**
     * Рендериране на stores meta box
     */
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_nonce', 'parfume_stores_meta_nonce_field');
        
        $all_stores = get_option('parfume_catalog_stores', array());
        $post_stores = get_post_meta($post->ID, '_parfume_stores', true);
        
        if (!is_array($post_stores)) {
            $post_stores = array();
        }
        ?>
        <div class="stores-meta-box">
            <div class="stores-header">
                <button type="button" class="button button-primary add-store-to-post">
                    <?php _e('Добави магазин', 'parfume-catalog'); ?>
                </button>
                <p class="description">
                    <?php _e('Добавете магазини за този парфюм. Можете да променяте реда им с drag & drop.', 'parfume-catalog'); ?>
                </p>
            </div>
            
            <div class="post-stores-list" data-post-id="<?php echo $post->ID; ?>">
                <?php if (empty($post_stores)): ?>
                    <div class="no-stores-added">
                        <p><?php _e('Не са добавени магазини за този парфюм.', 'parfume-catalog'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($post_stores as $store_id => $store_data): ?>
                        <?php if (isset($all_stores[$store_id])): ?>
                            <?php $this->render_post_store_item($store_id, $all_stores[$store_id], $store_data); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Add Store to Post Modal -->
            <div id="add-store-to-post-modal" class="parfume-modal" style="display: none;">
                <div class="parfume-modal-content">
                    <div class="parfume-modal-header">
                        <h2><?php _e('Добави магазин към парфюм', 'parfume-catalog'); ?></h2>
                        <span class="parfume-modal-close">&times;</span>
                    </div>
                    <div class="parfume-modal-body">
                        <select id="select-store-for-post" class="widefat">
                            <option value=""><?php _e('Избери магазин...', 'parfume-catalog'); ?></option>
                            <?php foreach ($all_stores as $store_id => $store): ?>
                                <?php if (!isset($post_stores[$store_id])): ?>
                                    <option value="<?php echo esc_attr($store_id); ?>"><?php echo esc_html($store['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="parfume-modal-footer">
                        <button type="button" class="button button-primary" id="confirm-add-store-to-post"><?php _e('Добави', 'parfume-catalog'); ?></button>
                        <button type="button" class="button" id="cancel-add-store-to-post"><?php _e('Отказ', 'parfume-catalog'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Рендериране на един store item в post meta box
     */
    private function render_post_store_item($store_id, $store_info, $store_data) {
        ?>
        <div class="post-store-item" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-item-header">
                <div class="store-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <div class="store-info">
                    <?php if (!empty($store_info['logo_id'])): ?>
                        <?php echo wp_get_attachment_image($store_info['logo_id'], array(50, 30)); ?>
                    <?php endif; ?>
                    <strong><?php echo esc_html($store_info['name']); ?></strong>
                </div>
                <div class="store-actions">
                    <button type="button" class="button remove-store-from-post" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <?php _e('Премахни', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div class="store-item-content">
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
                                       !empty($scraper_data['last_scraped']) ? date('d.m.Y H:i', strtotime($scraper_data['last_scraped'])) : __('Никога', 'parfume-catalog'));
                            } else {
                                _e('Не е скрейпвано', 'parfume-catalog');
                            }
                            ?>
                        </span>
                    </div>
                    
                    <?php if ($scraper_data && !empty($scraper_data['data'])): ?>
                        <div class="scraped-data-preview">
                            <h4><?php _e('Скрейпнати данни:', 'parfume-catalog'); ?></h4>
                            <div class="scraped-data-grid">
                                <?php if (!empty($scraper_data['data']['price'])): ?>
                                    <div class="data-item">
                                        <label><?php _e('Цена:', 'parfume-catalog'); ?></label>
                                        <span><?php echo esc_html($scraper_data['data']['price']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraper_data['data']['old_price'])): ?>
                                    <div class="data-item">
                                        <label><?php _e('Стара цена:', 'parfume-catalog'); ?></label>
                                        <span><?php echo esc_html($scraper_data['data']['old_price']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraper_data['data']['availability'])): ?>
                                    <div class="data-item">
                                        <label><?php _e('Наличност:', 'parfume-catalog'); ?></label>
                                        <span><?php echo esc_html($scraper_data['data']['availability']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraper_data['data']['delivery'])): ?>
                                    <div class="data-item">
                                        <label><?php _e('Доставка:', 'parfume-catalog'); ?></label>
                                        <span><?php echo esc_html($scraper_data['data']['delivery']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraper_data['data']['ml_options'])): ?>
                                    <div class="data-item">
                                        <label><?php _e('Милилитри:', 'parfume-catalog'); ?></label>
                                        <span><?php echo esc_html(implode(', ', $scraper_data['data']['ml_options'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
                    'store_id' => sanitize_text_field($store_id),
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
            'logo_id' => intval($_POST['store_logo_id']),
            'active' => isset($_POST['store_active']) ? (bool) $_POST['store_active'] : false
        );

        $stores = get_option('parfume_catalog_stores', array());
        $store_id = uniqid('store_');
        $stores[$store_id] = $store_data;
        
        if (update_option('parfume_catalog_stores', $stores)) {
            wp_send_json_success(array(
                'message' => __('Магазинът е добавен успешно.', 'parfume-catalog'),
                'store_id' => $store_id,
                'store_data' => $store_data
            ));
        } else {
            wp_send_json_error(__('Грешка при запазване на магазина.', 'parfume-catalog'));
        }
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
            
            if (update_option('parfume_catalog_stores', $stores)) {
                // Премахване на магазина от всички постове
                $this->remove_store_from_all_posts($store_id);
                
                wp_send_json_success(__('Магазинът е изтрит успешно.', 'parfume-catalog'));
            } else {
                wp_send_json_error(__('Грешка при изтриване на магазина.', 'parfume-catalog'));
            }
        } else {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Обновяване на реда на магазините
     */
    public function ajax_update_store_order() {
        check_ajax_referer('parfume_catalog_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $post_id = intval($_POST['post_id']);
        $store_order = array_map('sanitize_text_field', $_POST['store_order']);
        
        $current_stores = get_post_meta($post_id, '_parfume_stores', true);
        if (!is_array($current_stores)) {
            wp_send_json_error(__('Не са намерени магазини за този пост.', 'parfume-catalog'));
        }
        
        $ordered_stores = array();
        foreach ($store_order as $store_id) {
            if (isset($current_stores[$store_id])) {
                $ordered_stores[$store_id] = $current_stores[$store_id];
            }
        }
        
        if (update_post_meta($post_id, '_parfume_stores', $ordered_stores)) {
            wp_send_json_success(__('Редът на магазините е обновен.', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при обновяване на реда.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Тестване на scraper URL
     */
    public function ajax_test_scraper_url() {
        check_ajax_referer('parfume_catalog_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $url = esc_url_raw($_POST['url']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        // Извикване на scraper класа за тестване
        if (class_exists('Parfume_Catalog_Scraper')) {
            $scraper = new Parfume_Catalog_Scraper();
            $result = $scraper->test_url($url, $store_id);
            
            if ($result) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(__('Грешка при тестване на URL-а.', 'parfume-catalog'));
            }
        } else {
            wp_send_json_error(__('Scraper класът не е наличен.', 'parfume-catalog'));
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_parfume_page()) {
            return;
        }
        
        wp_enqueue_script(
            'parfume-stores-frontend',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/stores-frontend.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );
        
        wp_enqueue_style(
            'parfume-stores-frontend',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/stores-frontend.css',
            array(),
            PARFUME_CATALOG_VERSION
        );
        
        // Mobile styles
        wp_enqueue_style(
            'parfume-stores-mobile',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/stores-mobile.css',
            array('parfume-stores-frontend'),
            PARFUME_CATALOG_VERSION,
            '(max-width: 768px)'
        );
        
        $mobile_settings = get_option('parfume_catalog_mobile_settings', array());
        
        wp_localize_script('parfume-stores-frontend', 'parfumeStores', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_stores_nonce'),
            'mobile' => array(
                'fixed_panel' => isset($mobile_settings['fixed_panel']) ? $mobile_settings['fixed_panel'] : true,
                'show_close_button' => isset($mobile_settings['show_close_button']) ? $mobile_settings['show_close_button'] : true,
                'z_index' => isset($mobile_settings['z_index']) ? $mobile_settings['z_index'] : 9999,
                'bottom_offset' => isset($mobile_settings['bottom_offset']) ? $mobile_settings['bottom_offset'] : 0
            ),
            'strings' => array(
                'copySuccess' => __('Промо кодът е копиран!', 'parfume-catalog'),
                'copyError' => __('Грешка при копиране', 'parfume-catalog'),
                'priceUpdated' => __('Цената се актуализира на всеки', 'parfume-catalog'),
                'hours' => __('часа', 'parfume-catalog'),
                'availableWith' => __('По-изгодно с', 'parfume-catalog'),
                'toStore' => __('Към магазина', 'parfume-catalog'),
                'available' => __('наличен', 'parfume-catalog'),
                'freeDelivery' => __('безплатна доставка', 'parfume-catalog')
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        $valid_hooks = array(
            'post.php',
            'post-new.php',
            'parfumes_page_parfume-catalog-stores'
        );
        
        if (!in_array($hook, $valid_hooks)) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_script(
            'parfume-stores-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/stores-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            PARFUME_CATALOG_VERSION,
            true
        );
        
        wp_enqueue_style(
            'parfume-stores-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/stores-admin.css',
            array(),
            PARFUME_CATALOG_VERSION
        );
        
        wp_localize_script('parfume-stores-admin', 'parfumeStoresAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_catalog_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-catalog'),
                'confirmRemove' => __('Сигурни ли сте, че искате да премахнете този магазин от парфюма?', 'parfume-catalog'),
                'saving' => __('Запазване...', 'parfume-catalog'),
                'saved' => __('Запазено!', 'parfume-catalog'),
                'error' => __('Грешка!', 'parfume-catalog'),
                'selectImage' => __('Избери изображение', 'parfume-catalog'),
                'removeImage' => __('Премахни изображение', 'parfume-catalog'),
                'testing' => __('Тестване...', 'parfume-catalog'),
                'testSuccess' => __('Тестът е успешен!', 'parfume-catalog'),
                'testFailed' => __('Тестът е неуспешен!', 'parfume-catalog')
            )
        ));
    }

    /**
     * Помощни функции
     */
    
    /**
     * Проверка дали сме на parfume страница
     */
    private function is_parfume_page() {
        return is_singular('parfumes') || 
               is_post_type_archive('parfumes') || 
               is_tax('parfume_type') || 
               is_tax('parfume_vid') || 
               is_tax('parfume_marki') || 
               is_tax('parfume_season') || 
               is_tax('parfume_intensity') || 
               is_tax('parfume_notes');
    }

    /**
     * Получаване на броя продукти за магазин
     */
    private function get_store_products_count($store_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT pm.post_id) 
            FROM {$wpdb->postmeta} pm 
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE pm.meta_key = '_parfume_stores' 
            AND pm.meta_value LIKE %s 
            AND p.post_type = 'parfumes' 
            AND p.post_status = 'publish'
        ", '%' . $store_id . '%'));
        
        return intval($count);
    }

    /**
     * Получаване на scraper данни
     */
    private function get_scraper_data($post_id, $store_id) {
        if (class_exists('Parfume_Catalog_Scraper')) {
            $scraper = new Parfume_Catalog_Scraper();
            return $scraper->get_scraped_data($post_id, $store_id);
        }
        return false;
    }

    /**
     * Премахване на магазин от всички постове
     */
    private function remove_store_from_all_posts($store_id) {
        $posts = get_posts(array(
            'post_type' => 'parfumes',
            'post_status' => 'any',
            'numberposts' => -1,
            'meta_key' => '_parfume_stores',
            'fields' => 'ids'
        ));
        
        foreach ($posts as $post_id) {
            $post_stores = get_post_meta($post_id, '_parfume_stores', true);
            
            if (is_array($post_stores) && isset($post_stores[$store_id])) {
                unset($post_stores[$store_id]);
                
                if (empty($post_stores)) {
                    delete_post_meta($post_id, '_parfume_stores');
                } else {
                    update_post_meta($post_id, '_parfume_stores', $post_stores);
                }
            }
        }
    }

    /**
     * Sanitization functions
     */
    
    public function sanitize_stores_settings($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $store_id => $store_data) {
                $sanitized[sanitize_text_field($store_id)] = array(
                    'name' => sanitize_text_field($store_data['name']),
                    'url' => esc_url_raw($store_data['url']),
                    'logo_id' => intval($store_data['logo_id']),
                    'active' => (bool) $store_data['active']
                );
            }
        }
        
        return $sanitized;
    }
    
    public function sanitize_scraper_settings($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            $sanitized['interval'] = intval($input['interval']);
            $sanitized['batch_size'] = intval($input['batch_size']);
            $sanitized['user_agent'] = sanitize_text_field($input['user_agent']);
            $sanitized['timeout'] = intval($input['timeout']);
            $sanitized['retry_attempts'] = intval($input['retry_attempts']);
        }
        
        return $sanitized;
    }
    
    public function sanitize_mobile_settings($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            $sanitized['fixed_panel'] = (bool) $input['fixed_panel'];
            $sanitized['show_close_button'] = (bool) $input['show_close_button'];
            $sanitized['z_index'] = intval($input['z_index']);
            $sanitized['bottom_offset'] = intval($input['bottom_offset']);
        }
        
        return $sanitized;
    }
    
    /**
     * Static methods for external access
     */
    
    /**
     * Получаване на всички магазини
     */
    public static function get_all_stores() {
        return get_option('parfume_catalog_stores', array());
    }
    
    /**
     * Получаване на активни магазини
     */
    public static function get_active_stores() {
        $stores = self::get_all_stores();
        $active_stores = array();
        
        foreach ($stores as $store_id => $store_data) {
            if (isset($store_data['active']) && $store_data['active']) {
                $active_stores[$store_id] = $store_data;
            }
        }
        
        return $active_stores;
    }
    
    /**
     * Получаване на конкретен магазин
     */
    public static function get_store($store_id) {
        $stores = self::get_all_stores();
        return isset($stores[$store_id]) ? $stores[$store_id] : false;
    }
    
    /**
     * Получаване на магазини за пост
     */
    public static function get_post_stores($post_id) {
        $post_stores = get_post_meta($post_id, '_parfume_stores', true);
        return is_array($post_stores) ? $post_stores : array();
    }
}

// Initialize the stores module
new Parfume_Catalog_Stores();