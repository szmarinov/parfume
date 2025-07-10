<?php
/**
 * Parfume Catalog Admin Stores
 * 
 * Управление на магазини в админ панела
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin_Stores {

    /**
     * Stores option key
     */
    private $stores_option = 'parfume_catalog_stores';

    /**
     * Конструктор
     */
    public function __construct() {
        // Този клас се hook-ва в главния admin menu система
        // Функционалността е достъпна през parfume-catalog-stores страницата
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_parfume_save_store', array($this, 'ajax_save_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_get_store', array($this, 'ajax_get_store'));
        add_action('wp_ajax_parfume_test_store_url', array($this, 'ajax_test_store_url'));
        add_action('wp_ajax_parfume_duplicate_store', array($this, 'ajax_duplicate_store'));
        add_action('wp_ajax_parfume_export_stores', array($this, 'ajax_export_stores'));
        add_action('wp_ajax_parfume_import_stores', array($this, 'ajax_import_stores'));
    }

    /**
     * Enqueue admin scripts и styles
     */
    public function enqueue_admin_scripts($hook) {
        // Зарежда само на stores страницата
        if ($hook !== 'parfume-catalog_page_parfume-catalog-stores') {
            return;
        }

        // WordPress media uploader
        wp_enqueue_media();
        
        // jQuery UI за sortable
        wp_enqueue_script('jquery-ui-sortable');

        // Custom admin scripts
        wp_enqueue_script(
            'parfume-admin-stores',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin-stores.js',
            array('jquery', 'jquery-ui-sortable', 'wp-util'),
            PARFUME_CATALOG_VERSION,
            true
        );

        // Admin styles
        wp_enqueue_style(
            'parfume-admin-stores',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin-stores.css',
            array('wp-admin', 'buttons'),
            PARFUME_CATALOG_VERSION
        );

        // Localize script
        wp_localize_script('parfume-admin-stores', 'parfumeAdminStores', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_stores_nonce'),
            'strings' => array(
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-catalog'),
                'saving' => __('Запазване...', 'parfume-catalog'),
                'saved' => __('Запазено успешно!', 'parfume-catalog'),
                'error_saving' => __('Грешка при запазване!', 'parfume-catalog'),
                'error_deleting' => __('Грешка при изтриване!', 'parfume-catalog'),
                'store_name_required' => __('Името на магазина е задължително!', 'parfume-catalog'),
                'select_logo' => __('Избери лого', 'parfume-catalog'),
                'change_logo' => __('Смени лого', 'parfume-catalog'),
                'remove_logo' => __('Премахни лого', 'parfume-catalog'),
                'add_store' => __('Добави магазин', 'parfume-catalog'),
                'edit_store' => __('Редактирай магазин', 'parfume-catalog'),
                'duplicate_store' => __('Дублирай магазин', 'parfume-catalog'),
                'test_url' => __('Тествай URL', 'parfume-catalog'),
                'testing_url' => __('Тестване...', 'parfume-catalog'),
                'url_valid' => __('URL-ът е валиден!', 'parfume-catalog'),
                'url_invalid' => __('URL-ът не е достъпен!', 'parfume-catalog'),
                'export_success' => __('Експортирането завърши успешно!', 'parfume-catalog'),
                'import_success' => __('Импортирането завърши успешно!', 'parfume-catalog'),
                'invalid_file' => __('Невалиден файл! Моля изберете JSON файл.', 'parfume-catalog')
            )
        ));

        // Inline styles за по-добър UX
        wp_add_inline_style('parfume-admin-stores', $this->get_inline_styles());
    }

    /**
     * Рендериране на stores admin страница
     */
    public function render_stores_page() {
        $stores = $this->get_stores();
        $total_stores = count($stores);
        $active_stores = count(array_filter($stores, function($store) {
            return !empty($store['active']);
        }));

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Управление на магазини', 'parfume-catalog'); ?></h1>
            <button type="button" id="add-store-btn" class="page-title-action">
                <?php _e('Добави магазин', 'parfume-catalog'); ?>
            </button>
            <hr class="wp-header-end">

            <!-- Statistics -->
            <div class="stores-stats">
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_stores; ?></div>
                        <div class="stat-label"><?php _e('Общо магазини', 'parfume-catalog'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $active_stores; ?></div>
                        <div class="stat-label"><?php _e('Активни магазини', 'parfume-catalog'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $this->get_stores_products_count(); ?></div>
                        <div class="stat-label"><?php _e('Продукти с магазини', 'parfume-catalog'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="stores-bulk-actions">
                <div class="alignleft actions">
                    <button type="button" class="button" id="export-stores">
                        <?php _e('Export магазини', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button" id="import-stores">
                        <?php _e('Import магазини', 'parfume-catalog'); ?>
                    </button>
                    <input type="file" id="import-stores-file" accept=".json" style="display: none;">
                </div>
                <div class="alignright">
                    <input type="search" id="search-stores" placeholder="<?php esc_attr_e('Търси магазини...', 'parfume-catalog'); ?>">
                </div>
            </div>

            <!-- Stores List -->
            <div class="stores-container">
                <div id="stores-list">
                    <?php if (empty($stores)): ?>
                        <div class="no-stores-message">
                            <div class="no-stores-icon">🏪</div>
                            <h3><?php _e('Няма добавени магазини', 'parfume-catalog'); ?></h3>
                            <p><?php _e('Добавете първия си магазин, за да започнете да управлявате оферти за парфюмите.', 'parfume-catalog'); ?></p>
                            <button type="button" class="button button-primary" id="add-first-store">
                                <?php _e('Добави първи магазин', 'parfume-catalog'); ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="stores-grid" id="sortable-stores">
                            <?php foreach ($stores as $store_id => $store): ?>
                                <?php $this->render_store_card($store_id, $store); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Store Modal -->
        <?php $this->render_store_modal(); ?>
        <?php
    }

    /**
     * Рендериране на store card
     */
    private function render_store_card($store_id, $store) {
        $logo_url = !empty($store['logo']) ? $store['logo'] : '';
        $is_active = !empty($store['active']);
        $products_count = $this->get_store_products_count($store_id);
        
        ?>
        <div class="store-card <?php echo $is_active ? 'active' : 'inactive'; ?>" data-store-id="<?php echo esc_attr($store_id); ?>">
            <div class="store-card-header">
                <div class="store-logo">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($store['name']); ?>">
                    <?php else: ?>
                        <div class="logo-placeholder">🏪</div>
                    <?php endif; ?>
                </div>
                <div class="store-status">
                    <span class="status-indicator <?php echo $is_active ? 'active' : 'inactive'; ?>"></span>
                    <span class="status-text"><?php echo $is_active ? __('Активен', 'parfume-catalog') : __('Неактивен', 'parfume-catalog'); ?></span>
                </div>
            </div>

            <div class="store-card-body">
                <h3 class="store-name"><?php echo esc_html($store['name']); ?></h3>
                
                <div class="store-meta">
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Продукти:', 'parfume-catalog'); ?></span>
                        <span class="meta-value"><?php echo $products_count; ?></span>
                    </div>
                    <?php if (!empty($store['website'])): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Сайт:', 'parfume-catalog'); ?></span>
                            <span class="meta-value">
                                <a href="<?php echo esc_url($store['website']); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html(parse_url($store['website'], PHP_URL_HOST)); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($store['description'])): ?>
                    <div class="store-description">
                        <?php echo esc_html(wp_trim_words($store['description'], 15)); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="store-card-footer">
                <div class="store-actions">
                    <button type="button" class="button button-small edit-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <?php _e('Редактирай', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-small duplicate-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <?php _e('Дублирай', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-small button-link-delete delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                        <?php _e('Изтрий', 'parfume-catalog'); ?>
                    </button>
                </div>
                <div class="drag-handle" title="<?php esc_attr_e('Плъзни за пренареждане', 'parfume-catalog'); ?>">
                    ⋮⋮
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Рендериране на store modal
     */
    private function render_store_modal() {
        ?>
        <div id="store-modal" class="store-modal" style="display: none;">
            <div class="store-modal-overlay"></div>
            <div class="store-modal-content">
                <div class="store-modal-header">
                    <h2 id="modal-title"><?php _e('Добави магазин', 'parfume-catalog'); ?></h2>
                    <button type="button" class="store-modal-close" aria-label="<?php esc_attr_e('Затвори', 'parfume-catalog'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="store-modal-body">
                    <form id="store-form">
                        <input type="hidden" id="store-id" name="store_id" value="">
                        <?php wp_nonce_field('parfume_admin_stores_nonce', 'parfume_store_nonce'); ?>

                        <div class="form-sections">
                            <!-- Basic Information -->
                            <div class="form-section">
                                <h3><?php _e('Основна информация', 'parfume-catalog'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="store-name"><?php _e('Име на магазина', 'parfume-catalog'); ?> <span class="required">*</span></label>
                                        </th>
                                        <td>
                                            <input type="text" id="store-name" name="store_name" class="regular-text" required>
                                            <p class="description"><?php _e('Име на магазина, което ще се показва във фронтенда', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="store-description"><?php _e('Описание', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <textarea id="store-description" name="store_description" rows="3" class="large-text"></textarea>
                                            <p class="description"><?php _e('Кратко описание на магазина', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="store-website"><?php _e('Уебсайт', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <input type="url" id="store-website" name="store_website" class="regular-text" placeholder="https://example.com">
                                            <button type="button" id="test-website" class="button button-secondary">
                                                <?php _e('Тествай', 'parfume-catalog'); ?>
                                            </button>
                                            <p class="description"><?php _e('Основен уебсайт на магазина', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="store-active"><?php _e('Статус', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <label for="store-active">
                                                <input type="checkbox" id="store-active" name="store_active" value="1">
                                                <?php _e('Активен магазин', 'parfume-catalog'); ?>
                                            </label>
                                            <p class="description"><?php _e('Неактивните магазини няма да се показват във фронтенда', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Logo Section -->
                            <div class="form-section">
                                <h3><?php _e('Лого и брандинг', 'parfume-catalog'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="store-logo"><?php _e('Лого', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <div class="logo-upload-container">
                                                <div id="logo-preview" class="logo-preview" style="display: none;">
                                                    <img id="logo-image" src="" alt="Store logo">
                                                    <button type="button" id="remove-logo" class="button button-secondary">
                                                        <?php _e('Премахни', 'parfume-catalog'); ?>
                                                    </button>
                                                </div>
                                                <div id="logo-upload" class="logo-upload">
                                                    <button type="button" id="upload-logo" class="button button-secondary">
                                                        <?php _e('Избери лого', 'parfume-catalog'); ?>
                                                    </button>
                                                    <p class="description">
                                                        <?php _e('Препоръчителни размери: 200x60px, формат PNG или JPG', 'parfume-catalog'); ?>
                                                    </p>
                                                </div>
                                                <input type="hidden" id="store-logo" name="store_logo" value="">
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Scraper Schema Section -->
                            <div class="form-section">
                                <h3><?php _e('Scraper конфигурация', 'parfume-catalog'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="enable-scraper"><?php _e('Разреши scraping', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <label for="enable-scraper">
                                                <input type="checkbox" id="enable-scraper" name="enable_scraper" value="1">
                                                <?php _e('Разреши автоматично скрейпване на данни', 'parfume-catalog'); ?>
                                            </label>
                                        </td>
                                    </tr>

                                    <tr class="scraper-fields" style="display: none;">
                                        <th scope="row">
                                            <label for="price-selector"><?php _e('Price CSS селектор', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="price-selector" name="price_selector" class="regular-text" placeholder=".price, .product-price">
                                            <p class="description"><?php _e('CSS селектор за цената на продукта', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>

                                    <tr class="scraper-fields" style="display: none;">
                                        <th scope="row">
                                            <label for="old-price-selector"><?php _e('Old price селектор', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="old-price-selector" name="old_price_selector" class="regular-text" placeholder=".old-price, .was-price">
                                            <p class="description"><?php _e('CSS селектор за старата цена (при промоции)', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>

                                    <tr class="scraper-fields" style="display: none;">
                                        <th scope="row">
                                            <label for="availability-selector"><?php _e('Availability селектор', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="availability-selector" name="availability_selector" class="regular-text" placeholder=".availability, .stock-status">
                                            <p class="description"><?php _e('CSS селектор за наличността на продукта', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>

                                    <tr class="scraper-fields" style="display: none;">
                                        <th scope="row">
                                            <label for="variants-selector"><?php _e('Variants селектор', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="variants-selector" name="variants_selector" class="regular-text" placeholder=".variants select, .size-options">
                                            <p class="description"><?php _e('CSS селектор за различните разфасовки (ml)', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>

                                    <tr class="scraper-fields" style="display: none;">
                                        <th scope="row">
                                            <label for="delivery-selector"><?php _e('Delivery селектор', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="delivery-selector" name="delivery_selector" class="regular-text" placeholder=".delivery-info, .shipping">
                                            <p class="description"><?php _e('CSS селектор за информация за доставката', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Additional Settings -->
                            <div class="form-section">
                                <h3><?php _e('Допълнителни настройки', 'parfume-catalog'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="affiliate-network"><?php _e('Affiliate мрежа', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <select id="affiliate-network" name="affiliate_network">
                                                <option value=""><?php _e('Не е зададена', 'parfume-catalog'); ?></option>
                                                <option value="awin">Awin</option>
                                                <option value="tradedoubler">TradeDoubler</option>
                                                <option value="cj">Commission Junction</option>
                                                <option value="rakuten">Rakuten</option>
                                                <option value="direct"><?php _e('Директен партньор', 'parfume-catalog'); ?></option>
                                                <option value="other"><?php _e('Друга', 'parfume-catalog'); ?></option>
                                            </select>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="default-currency"><?php _e('Валута', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <select id="default-currency" name="default_currency">
                                                <option value="BGN">BGN - Български лев</option>
                                                <option value="EUR">EUR - Евро</option>
                                                <option value="USD">USD - Долар</option>
                                                <option value="GBP">GBP - Британска лира</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="store-priority"><?php _e('Приоритет', 'parfume-catalog'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" id="store-priority" name="store_priority" value="0" min="0" max="100" class="small-text">
                                            <p class="description"><?php _e('По-високия приоритет означава показване отгоре (0-100)', 'parfume-catalog'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="store-modal-footer">
                    <button type="button" class="button button-large" id="cancel-store">
                        <?php _e('Отказ', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-primary button-large" id="save-store">
                        <?php _e('Запази магазин', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX - Запазване на магазин
     */
    public function ajax_save_store() {
        check_ajax_referer('parfume_admin_stores_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $store_data = $this->sanitize_store_data($_POST);
        
        if (empty($store_data['name'])) {
            wp_send_json_error(__('Името на магазина е задължително.', 'parfume-catalog'));
        }

        $stores = $this->get_stores();
        $store_id = !empty($_POST['store_id']) ? sanitize_text_field($_POST['store_id']) : $this->generate_store_id();

        // Ако е нов магазин, проверяваме дали името не е заето
        if (empty($_POST['store_id'])) {
            foreach ($stores as $existing_id => $existing_store) {
                if (strtolower($existing_store['name']) === strtolower($store_data['name'])) {
                    wp_send_json_error(__('Магазин с това име вече съществува.', 'parfume-catalog'));
                }
            }
        }

        // Запазваме магазина
        $stores[$store_id] = $store_data;
        $this->save_stores($stores);

        wp_send_json_success(array(
            'message' => __('Магазинът е запазен успешно!', 'parfume-catalog'),
            'store_id' => $store_id,
            'store_data' => $store_data
        ));
    }

    /**
     * AJAX - Изтриване на магазин
     */
    public function ajax_delete_store() {
        check_ajax_referer('parfume_admin_stores_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $store_id = sanitize_text_field($_POST['store_id']);
        
        if (empty($store_id)) {
            wp_send_json_error(__('Невалиден ID на магазин.', 'parfume-catalog'));
        }

        $stores = $this->get_stores();
        
        if (!isset($stores[$store_id])) {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-catalog'));
        }

        // Премахваме магазина от всички постове
        $this->remove_store_from_posts($store_id);

        // Изтриваме магазина
        unset($stores[$store_id]);
        $this->save_stores($stores);

        wp_send_json_success(__('Магазинът е изтрит успешно!', 'parfume-catalog'));
    }

    /**
     * AJAX - Извличане на данни за магазин
     */
    public function ajax_get_store() {
        check_ajax_referer('parfume_admin_stores_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = $this->get_stores();

        if (!isset($stores[$store_id])) {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-catalog'));
        }

        wp_send_json_success($stores[$store_id]);
    }

    /**
     * AJAX - Тестване на URL
     */
    public function ajax_test_store_url() {
        check_ajax_referer('parfume_admin_stores_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $url = esc_url_raw($_POST['url']);
        
        if (empty($url)) {
            wp_send_json_error(__('Невалиден URL.', 'parfume-catalog'));
        }

        $response = wp_remote_head($url, array(
            'timeout' => 10,
            'user-agent' => 'Mozilla/5.0 (compatible; ParfumeCatalogBot/1.0)'
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(__('URL-ът не е достъпен: ', 'parfume-catalog') . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 400) {
            wp_send_json_success(__('URL-ът е валиден и достъпен!', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('URL-ът връща грешка: ', 'parfume-catalog') . $response_code);
        }
    }

    /**
     * AJAX - Дублиране на магазин
     */
    public function ajax_duplicate_store() {
        check_ajax_referer('parfume_admin_stores_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = $this->get_stores();

        if (!isset($stores[$store_id])) {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-catalog'));
        }

        $original_store = $stores[$store_id];
        $new_store = $original_store;
        $new_store['name'] = $original_store['name'] . ' (копие)';
        $new_store['active'] = false; // Копието е неактивно по подразбиране

        $new_store_id = $this->generate_store_id();
        $stores[$new_store_id] = $new_store;
        $this->save_stores($stores);

        wp_send_json_success(array(
            'message' => __('Магазинът е дублиран успешно!', 'parfume-catalog'),
            'store_id' => $new_store_id,
            'store_data' => $new_store
        ));
    }

    /**
     * AJAX - Export на магазини
     */
    public function ajax_export_stores() {
        check_ajax_referer('parfume_admin_stores_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $stores = $this->get_stores();
        
        $export_data = array(
            'stores' => $stores,
            'export_date' => current_time('mysql'),
            'plugin_version' => PARFUME_CATALOG_VERSION,
            'site_url' => home_url()
        );

        wp_send_json_success($export_data);
    }

    /**
     * AJAX - Import на магазини
     */
    public function ajax_import_stores() {
        check_ajax_referer('parfume_admin_stores_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $import_data = json_decode(stripslashes($_POST['import_data']), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Невалиден JSON формат.', 'parfume-catalog'));
        }

        if (!isset($import_data['stores']) || !is_array($import_data['stores'])) {
            wp_send_json_error(__('Невалидни данни за магазини.', 'parfume-catalog'));
        }

        $current_stores = $this->get_stores();
        $imported_count = 0;
        $skipped_count = 0;

        foreach ($import_data['stores'] as $store_id => $store_data) {
            // Проверяваме дали магазинът вече съществува
            if (isset($current_stores[$store_id])) {
                $skipped_count++;
                continue;
            }

            // Sanitizираме данните
            $sanitized_store = $this->sanitize_store_data($store_data);
            $current_stores[$store_id] = $sanitized_store;
            $imported_count++;
        }

        $this->save_stores($current_stores);

        wp_send_json_success(array(
            'message' => sprintf(
                __('Импортирани %d магазина. Пропуснати %d (вече съществуват).', 'parfume-catalog'),
                $imported_count,
                $skipped_count
            ),
            'imported' => $imported_count,
            'skipped' => $skipped_count
        ));
    }

    /**
     * Helper функции
     */
    private function get_stores() {
        return get_option($this->stores_option, array());
    }

    private function save_stores($stores) {
        return update_option($this->stores_option, $stores);
    }

    private function generate_store_id() {
        return 'store_' . uniqid();
    }

    private function sanitize_store_data($data) {
        return array(
            'name' => sanitize_text_field($data['name'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'website' => esc_url_raw($data['website'] ?? ''),
            'logo' => esc_url_raw($data['logo'] ?? ''),
            'active' => !empty($data['active']),
            'enable_scraper' => !empty($data['enable_scraper']),
            'price_selector' => sanitize_text_field($data['price_selector'] ?? ''),
            'old_price_selector' => sanitize_text_field($data['old_price_selector'] ?? ''),
            'availability_selector' => sanitize_text_field($data['availability_selector'] ?? ''),
            'variants_selector' => sanitize_text_field($data['variants_selector'] ?? ''),
            'delivery_selector' => sanitize_text_field($data['delivery_selector'] ?? ''),
            'affiliate_network' => sanitize_text_field($data['affiliate_network'] ?? ''),
            'default_currency' => sanitize_text_field($data['default_currency'] ?? 'BGN'),
            'priority' => intval($data['priority'] ?? 0),
            'created_at' => $data['created_at'] ?? current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
    }

    private function get_stores_products_count() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_parfume_stores' 
             AND meta_value != ''"
        );
        
        return intval($count);
    }

    private function get_store_products_count($store_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_parfume_stores' 
             AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($store_id) . '%'
        ));
        
        return intval($count);
    }

    private function remove_store_from_posts($store_id) {
        $posts = get_posts(array(
            'post_type' => 'parfumes',
            'meta_key' => '_parfume_stores',
            'numberposts' => -1,
            'post_status' => 'any'
        ));

        foreach ($posts as $post) {
            $stores_data = get_post_meta($post->ID, '_parfume_stores', true);
            
            if (is_array($stores_data) && isset($stores_data[$store_id])) {
                unset($stores_data[$store_id]);
                update_post_meta($post->ID, '_parfume_stores', $stores_data);
            }
        }
    }

    private function get_inline_styles() {
        return '
        .stores-stats { margin: 20px 0; }
        .stats-cards { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            text-align: center;
            min-width: 120px;
        }
        .stat-number { font-size: 2.5em; font-weight: bold; color: #0073aa; line-height: 1; }
        .stat-label { color: #666; font-size: 0.9em; margin-top: 5px; }
        
        .stores-bulk-actions { 
            background: white; 
            padding: 15px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .no-stores-message { 
            text-align: center; 
            padding: 60px 20px; 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
        }
        .no-stores-icon { font-size: 4em; margin-bottom: 20px; }
        
        .stores-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        
        .store-card { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .store-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .store-card.inactive { opacity: 0.7; }
        
        .store-card-header { 
            padding: 15px; 
            border-bottom: 1px solid #eee; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .store-logo img { max-width: 80px; max-height: 40px; }
        .logo-placeholder { 
            width: 80px; 
            height: 40px; 
            background: #f0f0f0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.5em; 
            border-radius: 4px; 
        }
        
        .status-indicator { 
            width: 10px; 
            height: 10px; 
            border-radius: 50%; 
            display: inline-block; 
            margin-right: 5px; 
        }
        .status-indicator.active { background: #4CAF50; }
        .status-indicator.inactive { background: #f44336; }
        
        .store-card-body { padding: 15px; }
        .store-name { margin: 0 0 10px 0; font-size: 1.1em; }
        .store-meta { margin: 10px 0; font-size: 0.9em; color: #666; }
        .meta-item { margin-bottom: 5px; }
        .meta-label { font-weight: 500; }
        
        .store-card-footer { 
            padding: 15px; 
            border-top: 1px solid #eee; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .store-actions { display: flex; gap: 5px; }
        .drag-handle { 
            cursor: move; 
            color: #ccc; 
            font-size: 1.2em; 
            user-select: none; 
        }
        .drag-handle:hover { color: #999; }
        
        .store-modal { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            z-index: 100000; 
        }
        .store-modal-overlay { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
        }
        .store-modal-content { 
            position: relative; 
            background: white; 
            width: 90%; 
            max-width: 800px; 
            margin: 50px auto; 
            border-radius: 8px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.3); 
            max-height: 90vh; 
            overflow-y: auto; 
        }
        
        .store-modal-header { 
            padding: 20px; 
            border-bottom: 1px solid #eee; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .store-modal-close { 
            background: none; 
            border: none; 
            font-size: 1.5em; 
            cursor: pointer; 
            color: #999; 
        }
        
        .store-modal-body { padding: 20px; }
        .form-sections { }
        .form-section { 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 1px solid #eee; 
        }
        .form-section:last-child { border-bottom: none; margin-bottom: 0; }
        .form-section h3 { margin-top: 0; color: #333; }
        
        .store-modal-footer { 
            padding: 20px; 
            border-top: 1px solid #eee; 
            text-align: right; 
        }
        .store-modal-footer .button { margin-left: 10px; }
        
        .logo-upload-container { }
        .logo-preview { 
            margin-bottom: 15px; 
            text-align: center; 
        }
        .logo-preview img { 
            max-width: 200px; 
            max-height: 100px; 
            border: 1px solid #ddd; 
            padding: 10px; 
            background: white; 
        }
        
        .required { color: #d63638; }
        .scraper-fields { opacity: 0.5; transition: opacity 0.3s ease; }
        .scraper-fields.enabled { opacity: 1; }
        
        @media (max-width: 768px) {
            .stats-cards { flex-direction: column; }
            .stores-bulk-actions { flex-direction: column; gap: 15px; align-items: stretch; }
            .stores-grid { grid-template-columns: 1fr; }
            .store-modal-content { margin: 20px; width: calc(100% - 40px); }
        }
        ';
    }

    /**
     * Static helper функции
     */
    public static function get_store($store_id) {
        $stores = get_option('parfume_catalog_stores', array());
        return isset($stores[$store_id]) ? $stores[$store_id] : null;
    }

    public static function get_active_stores() {
        $stores = get_option('parfume_catalog_stores', array());
        return array_filter($stores, function($store) {
            return !empty($store['active']);
        });
    }

    public static function store_exists($store_id) {
        $stores = get_option('parfume_catalog_stores', array());
        return isset($stores[$store_id]);
    }
}