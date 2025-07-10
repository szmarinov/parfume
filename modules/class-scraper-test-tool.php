<?php
/**
 * Parfume Catalog Scraper Test Tool Module
 * 
 * Инструмент за тестване и конфигурация на scraper схеми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Scraper_Test_Tool {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_submenu'));
        add_action('wp_ajax_parfume_test_url_analysis', array($this, 'ajax_test_url_analysis'));
        add_action('wp_ajax_parfume_save_schema', array($this, 'ajax_save_schema'));
        add_action('wp_ajax_parfume_test_schema', array($this, 'ajax_test_schema'));
        add_action('wp_ajax_parfume_get_page_preview', array($this, 'ajax_get_page_preview'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Добавяне на администраторско подменю
     */
    public function add_admin_submenu() {
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Scraper Test Tool', 'parfume-catalog'),
            __('Test Tool', 'parfume-catalog'),
            'manage_options',
            'parfume-scraper-test-tool',
            array($this, 'render_test_tool_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'parfumes_page_parfume-scraper-test-tool') {
            return;
        }

        wp_enqueue_script(
            'parfume-scraper-test-tool',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/scraper-test-tool.js',
            array('jquery', 'jquery-ui-sortable'),
            PARFUME_CATALOG_VERSION,
            true
        );

        wp_enqueue_style(
            'parfume-scraper-test-tool',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/scraper-test-tool.css',
            array(),
            PARFUME_CATALOG_VERSION
        );

        wp_localize_script('parfume-scraper-test-tool', 'parfumeTestTool', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_test_tool_nonce'),
            'strings' => array(
                'analyzing' => __('Анализиране...', 'parfume-catalog'),
                'testing' => __('Тестване...', 'parfume-catalog'),
                'saving' => __('Запазване...', 'parfume-catalog'),
                'success' => __('Успешно!', 'parfume-catalog'),
                'error' => __('Грешка!', 'parfume-catalog'),
                'selectElement' => __('Маркирайте елемент', 'parfume-catalog'),
                'elementSelected' => __('Елемент избран', 'parfume-catalog'),
                'noElementsFound' => __('Не са открити подходящи елементи', 'parfume-catalog'),
                'testSuccessful' => __('Тестът е успешен', 'parfume-catalog'),
                'testFailed' => __('Тестът е неуспешен', 'parfume-catalog'),
                'schemaSaved' => __('Схемата е запазена', 'parfume-catalog'),
                'confirmDelete' => __('Сигурни ли сте, че искате да изтриете тази схема?', 'parfume-catalog')
            )
        ));
    }

    /**
     * Рендериране на test tool страница
     */
    public function render_test_tool_page() {
        $stores = get_option('parfume_catalog_stores', array());
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Scraper Test Tool', 'parfume-catalog'); ?></h1>
            <p class="description">
                <?php _e('Тествайте и конфигурирайте схеми за автоматично извличане на данни от различни магазини.', 'parfume-catalog'); ?>
            </p>

            <div class="test-tool-container">
                <!-- Step 1: URL Analysis -->
                <div class="test-section" id="url-analysis-section">
                    <h2><?php _e('Стъпка 1: Анализ на URL', 'parfume-catalog'); ?></h2>
                    <p class="description">
                        <?php _e('Въведете URL на продукт за автоматичен анализ и откриване на елементи.', 'parfume-catalog'); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test-url"><?php _e('URL на продукт', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="test-url" 
                                       class="large-text" 
                                       placeholder="https://example.com/product/..." 
                                       required />
                                <p class="description">
                                    <?php _e('Въведете пълен URL на продуктова страница от магазин', 'parfume-catalog'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="target-store"><?php _e('Целеви магазин', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <select id="target-store" class="regular-text">
                                    <option value=""><?php _e('Изберете магазин или създайте нов...', 'parfume-catalog'); ?></option>
                                    <?php foreach ($stores as $store_id => $store_data): ?>
                                        <option value="<?php echo esc_attr($store_id); ?>">
                                            <?php echo esc_html($store_data['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="new_store"><?php _e('+ Създай нов магазин', 'parfume-catalog'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Изберете съществуващ магазин или създайте нов за тази схема', 'parfume-catalog'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" id="analyze-url" class="button button-primary">
                            <?php _e('Анализирай URL', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" id="get-page-preview" class="button">
                            <?php _e('Преглед на страницата', 'parfume-catalog'); ?>
                        </button>
                    </p>

                    <div id="analysis-results" style="display: none;">
                        <h3><?php _e('Открити елементи', 'parfume-catalog'); ?></h3>
                        <p class="description">
                            <?php _e('Автоматично открити елементи на страницата. Кликнете върху елемент за да го използвате.', 'parfume-catalog'); ?>
                        </p>
                        <div id="detected-elements"></div>
                    </div>
                </div>

                <!-- Step 2: Schema Configuration -->
                <div class="test-section" id="schema-configuration-section">
                    <h2><?php _e('Стъпка 2: Конфигурация на схема', 'parfume-catalog'); ?></h2>
                    <p class="description">
                        <?php _e('Конфигурирайте CSS селекторите за извличане на данни от страницата.', 'parfume-catalog'); ?>
                    </p>

                    <form id="schema-form">
                        <input type="hidden" id="schema-store-id" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="price-selector"><?php _e('Селектор за цена', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="price-selector" 
                                           name="price_selector" 
                                           class="large-text" 
                                           placeholder=".price, .current-price, [data-price]" />
                                    <button type="button" class="button test-selector" data-field="price">
                                        <?php _e('Тествай', 'parfume-catalog'); ?>
                                    </button>
                                    <div class="selector-result" id="price-result"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="old-price-selector"><?php _e('Селектор за стара цена', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="old-price-selector" 
                                           name="old_price_selector" 
                                           class="large-text" 
                                           placeholder=".old-price, .was-price, .original-price" />
                                    <button type="button" class="button test-selector" data-field="old_price">
                                        <?php _e('Тествай', 'parfume-catalog'); ?>
                                    </button>
                                    <div class="selector-result" id="old-price-result"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ml-selector"><?php _e('Селектор за милилитри', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="ml-selector" 
                                           name="ml_selector" 
                                           class="large-text" 
                                           placeholder=".ml-options, .size-selector, .variants" />
                                    <button type="button" class="button test-selector" data-field="ml_options">
                                        <?php _e('Тествай', 'parfume-catalog'); ?>
                                    </button>
                                    <div class="selector-result" id="ml-result"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="availability-selector"><?php _e('Селектор за наличност', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="availability-selector" 
                                           name="availability_selector" 
                                           class="large-text" 
                                           placeholder=".stock-status, .availability, .in-stock" />
                                    <button type="button" class="button test-selector" data-field="availability">
                                        <?php _e('Тествай', 'parfume-catalog'); ?>
                                    </button>
                                    <div class="selector-result" id="availability-result"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="delivery-selector"><?php _e('Селектор за доставка', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="delivery-selector" 
                                           name="delivery_selector" 
                                           class="large-text" 
                                           placeholder=".shipping-info, .delivery, .free-shipping" />
                                    <button type="button" class="button test-selector" data-field="delivery">
                                        <?php _e('Тествай', 'parfume-catalog'); ?>
                                    </button>
                                    <div class="selector-result" id="delivery-result"></div>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="button" id="test-complete-schema" class="button button-primary">
                                <?php _e('Тествай цялата схема', 'parfume-catalog'); ?>
                            </button>
                            <button type="button" id="save-schema" class="button button-secondary">
                                <?php _e('Запази схема', 'parfume-catalog'); ?>
                            </button>
                            <button type="button" id="reset-schema" class="button">
                                <?php _e('Нулирай', 'parfume-catalog'); ?>
                            </button>
                        </p>
                    </form>

                    <div id="schema-test-results" style="display: none;">
                        <h3><?php _e('Резултат от тестването', 'parfume-catalog'); ?></h3>
                        <div id="schema-test-content"></div>
                    </div>
                </div>

                <!-- Step 3: Page Preview -->
                <div class="test-section" id="page-preview-section" style="display: none;">
                    <h2><?php _e('Стъпка 3: Преглед на страницата', 'parfume-catalog'); ?></h2>
                    <p class="description">
                        <?php _e('Интерактивен преглед на страницата за лесно избиране на елементи.', 'parfume-catalog'); ?>
                    </p>

                    <div class="preview-controls">
                        <button type="button" id="highlight-prices" class="button">
                            <?php _e('Маркирай цени', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" id="highlight-availability" class="button">
                            <?php _e('Маркирай наличност', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" id="highlight-variants" class="button">
                            <?php _e('Маркирай варианти', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" id="clear-highlights" class="button">
                            <?php _e('Изчисти маркировки', 'parfume-catalog'); ?>
                        </button>
                    </div>

                    <div id="page-preview-container">
                        <iframe id="page-preview-frame" style="display: none;"></iframe>
                        <div id="page-preview-content"></div>
                    </div>
                </div>

                <!-- Existing Schemas -->
                <div class="test-section" id="existing-schemas-section">
                    <h2><?php _e('Съществуващи схеми', 'parfume-catalog'); ?></h2>
                    <p class="description">
                        <?php _e('Управление на вече създадени схеми за скрейпване.', 'parfume-catalog'); ?>
                    </p>

                    <?php if (empty($schemas)): ?>
                        <div class="no-schemas-message">
                            <p><?php _e('Няма създадени схеми. Създайте първата си схема с инструмента по-горе.', 'parfume-catalog'); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Магазин', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Селектори', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Последно тестване', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Статус', 'parfume-catalog'); ?></th>
                                    <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schemas as $store_id => $schema): ?>
                                    <?php 
                                    $store = isset($stores[$store_id]) ? $stores[$store_id] : null;
                                    $store_name = $store ? $store['name'] : __('Неизвестен магазин', 'parfume-catalog');
                                    ?>
                                    <tr data-store-id="<?php echo esc_attr($store_id); ?>">
                                        <td>
                                            <strong><?php echo esc_html($store_name); ?></strong>
                                            <?php if ($store && !empty($store['url'])): ?>
                                                <br>
                                                <small><a href="<?php echo esc_url($store['url']); ?>" target="_blank"><?php echo esc_html($store['url']); ?></a></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="schema-selectors">
                                                <?php if (!empty($schema['price_selector'])): ?>
                                                    <div><strong><?php _e('Цена:', 'parfume-catalog'); ?></strong> <code><?php echo esc_html($schema['price_selector']); ?></code></div>
                                                <?php endif; ?>
                                                <?php if (!empty($schema['availability_selector'])): ?>
                                                    <div><strong><?php _e('Наличност:', 'parfume-catalog'); ?></strong> <code><?php echo esc_html($schema['availability_selector']); ?></code></div>
                                                <?php endif; ?>
                                                <?php if (!empty($schema['ml_selector'])): ?>
                                                    <div><strong><?php _e('Милилитри:', 'parfume-catalog'); ?></strong> <code><?php echo esc_html($schema['ml_selector']); ?></code></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $last_test = isset($schema['last_test']) ? $schema['last_test'] : null;
                                            if ($last_test) {
                                                echo date('d.m.Y H:i', strtotime($last_test));
                                            } else {
                                                _e('Никога', 'parfume-catalog');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = isset($schema['status']) ? $schema['status'] : 'unknown';
                                            switch ($status) {
                                                case 'working':
                                                    echo '<span class="status-working">' . __('Работи', 'parfume-catalog') . '</span>';
                                                    break;
                                                case 'error':
                                                    echo '<span class="status-error">' . __('Грешка', 'parfume-catalog') . '</span>';
                                                    break;
                                                default:
                                                    echo '<span class="status-unknown">' . __('Неизвестен', 'parfume-catalog') . '</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small edit-schema" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Редактирай', 'parfume-catalog'); ?>
                                            </button>
                                            <button type="button" class="button button-small test-schema" data-store-id="<?php echo esc_attr($store_id); ?>">
                                                <?php _e('Тествай', 'parfume-catalog'); ?>
                                            </button>
                                            <button type="button" class="button button-small button-link-delete delete-schema" data-store-id="<?php echo esc_attr($store_id); ?>">
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

            <!-- New Store Modal -->
            <div id="new-store-modal" class="parfume-modal" style="display: none;">
                <div class="parfume-modal-content">
                    <div class="parfume-modal-header">
                        <h2><?php _e('Създай нов магазин', 'parfume-catalog'); ?></h2>
                        <span class="parfume-modal-close">&times;</span>
                    </div>
                    <div class="parfume-modal-body">
                        <form id="new-store-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="new-store-name"><?php _e('Име на магазин', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="new-store-name" class="regular-text" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="new-store-url"><?php _e('URL на магазин', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="url" id="new-store-url" class="regular-text" placeholder="https://example.com" />
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="parfume-modal-footer">
                        <button type="button" class="button button-primary" id="save-new-store"><?php _e('Създай', 'parfume-catalog'); ?></button>
                        <button type="button" class="button" id="cancel-new-store"><?php _e('Отказ', 'parfume-catalog'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX - Анализ на URL и откриване на елементи
     */
    public function ajax_test_url_analysis() {
        check_ajax_referer('parfume_test_tool_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $test_url = esc_url_raw($_POST['test_url']);

        if (empty($test_url)) {
            wp_send_json_error(__('Моля, въведете валиден URL.', 'parfume-catalog'));
        }

        try {
            // Използваме scraper класа за fetch на съдържанието
            if (class_exists('Parfume_Catalog_Scraper')) {
                $scraper = new Parfume_Catalog_Scraper();
                
                // Използваме reflection за достъп до private метода
                $reflection = new ReflectionClass($scraper);
                $fetch_method = $reflection->getMethod('fetch_page_content');
                $fetch_method->setAccessible(true);
                
                $html_content = $fetch_method->invoke($scraper, $test_url);
                
                if (empty($html_content)) {
                    throw new Exception(__('Неуспешно зареждане на страницата.', 'parfume-catalog'));
                }

                // Анализиране на HTML и откриване на потенциални елементи
                $detected_elements = $this->analyze_page_content($html_content);
                $page_info = $this->extract_page_info($html_content);

                wp_send_json_success(array(
                    'message' => __('Анализът е успешен.', 'parfume-catalog'),
                    'detected_elements' => $detected_elements,
                    'page_info' => $page_info,
                    'url' => $test_url
                ));

            } else {
                throw new Exception(__('Scraper класът не е наличен.', 'parfume-catalog'));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Грешка при анализ на URL-а.', 'parfume-catalog'),
                'error' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX - Запазване на схема
     */
    public function ajax_save_schema() {
        check_ajax_referer('parfume_test_tool_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $store_id = sanitize_text_field($_POST['store_id']);
        $schema_data = array(
            'price_selector' => sanitize_text_field($_POST['price_selector']),
            'old_price_selector' => sanitize_text_field($_POST['old_price_selector']),
            'ml_selector' => sanitize_text_field($_POST['ml_selector']),
            'availability_selector' => sanitize_text_field($_POST['availability_selector']),
            'delivery_selector' => sanitize_text_field($_POST['delivery_selector']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Валидация - поне един селектор трябва да е попълнен
        $has_selectors = false;
        foreach ($schema_data as $key => $value) {
            if (strpos($key, '_selector') !== false && !empty($value)) {
                $has_selectors = true;
                break;
            }
        }

        if (!$has_selectors) {
            wp_send_json_error(__('Моля, попълнете поне един селектор.', 'parfume-catalog'));
        }

        // Запазване на схемата
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        $schemas[$store_id] = $schema_data;

        if (update_option('parfume_catalog_scraper_schemas', $schemas)) {
            wp_send_json_success(array(
                'message' => __('Схемата е запазена успешно.', 'parfume-catalog'),
                'store_id' => $store_id,
                'schema' => $schema_data
            ));
        } else {
            wp_send_json_error(__('Грешка при запазване на схемата.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Тестване на схема
     */
    public function ajax_test_schema() {
        check_ajax_referer('parfume_test_tool_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $test_url = esc_url_raw($_POST['test_url']);
        $schema = array(
            'price_selector' => sanitize_text_field($_POST['price_selector']),
            'old_price_selector' => sanitize_text_field($_POST['old_price_selector']),
            'ml_selector' => sanitize_text_field($_POST['ml_selector']),
            'availability_selector' => sanitize_text_field($_POST['availability_selector']),
            'delivery_selector' => sanitize_text_field($_POST['delivery_selector'])
        );

        try {
            if (class_exists('Parfume_Catalog_Scraper')) {
                $scraper = new Parfume_Catalog_Scraper();
                
                // Използваме reflection за достъп до private методите
                $reflection = new ReflectionClass($scraper);
                
                $fetch_method = $reflection->getMethod('fetch_page_content');
                $fetch_method->setAccessible(true);
                
                $parse_method = $reflection->getMethod('parse_page_data');
                $parse_method->setAccessible(true);
                
                $html_content = $fetch_method->invoke($scraper, $test_url);
                $scraped_data = $parse_method->invoke($scraper, $html_content, $schema);

                // Обновяване на статуса на схемата
                if (isset($_POST['store_id'])) {
                    $store_id = sanitize_text_field($_POST['store_id']);
                    $this->update_schema_status($store_id, 'working');
                }

                wp_send_json_success(array(
                    'message' => __('Тестът е успешен.', 'parfume-catalog'),
                    'scraped_data' => $scraped_data,
                    'schema' => $schema
                ));

            } else {
                throw new Exception(__('Scraper класът не е наличен.', 'parfume-catalog'));
            }

        } catch (Exception $e) {
            // Обновяване на статуса на схемата при грешка
            if (isset($_POST['store_id'])) {
                $store_id = sanitize_text_field($_POST['store_id']);
                $this->update_schema_status($store_id, 'error', $e->getMessage());
            }

            wp_send_json_error(array(
                'message' => __('Тестът е неуспешен.', 'parfume-catalog'),
                'error' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX - Получаване на преглед на страницата
     */
    public function ajax_get_page_preview() {
        check_ajax_referer('parfume_test_tool_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $test_url = esc_url_raw($_POST['test_url']);

        try {
            if (class_exists('Parfume_Catalog_Scraper')) {
                $scraper = new Parfume_Catalog_Scraper();
                
                $reflection = new ReflectionClass($scraper);
                $fetch_method = $reflection->getMethod('fetch_page_content');
                $fetch_method->setAccessible(true);
                
                $html_content = $fetch_method->invoke($scraper, $test_url);
                
                // Почистване на HTML за preview
                $clean_html = $this->prepare_html_for_preview($html_content, $test_url);

                wp_send_json_success(array(
                    'message' => __('Прегледът е зареден.', 'parfume-catalog'),
                    'html_content' => $clean_html,
                    'url' => $test_url
                ));

            } else {
                throw new Exception(__('Scraper класът не е наличен.', 'parfume-catalog'));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Грешка при зареждане на прегледа.', 'parfume-catalog'),
                'error' => $e->getMessage()
            ));
        }
    }

    /**
     * Анализиране на HTML съдържание и откриване на потенциални елементи
     */
    private function analyze_page_content($html_content) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html_content);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $detected_elements = array();

        // Търсене на цени
        $price_patterns = array(
            '//*[contains(@class, "price") and not(contains(@class, "old")) and not(contains(@class, "was"))]',
            '//*[contains(@class, "current")]',
            '//*[contains(@class, "now")]',
            '//*[@data-price]',
            '//*[contains(text(), "лв") or contains(text(), "EUR") or contains(text(), "€") or contains(text(), "$")]'
        );

        foreach ($price_patterns as $pattern) {
            $elements = $xpath->query($pattern);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                if (preg_match('/\d+[.,]\d+/', $text) || preg_match('/\d+\s*(лв|EUR|€|\$)/', $text)) {
                    $selector = $this->generate_css_selector($element);
                    if ($selector && !$this->element_exists_in_array($detected_elements, $selector)) {
                        $detected_elements[] = array(
                            'type' => 'price',
                            'value' => $text,
                            'selector' => $selector,
                            'confidence' => $this->calculate_confidence($element, 'price')
                        );
                    }
                }
            }
        }

        // Търсене на стари цени
        $old_price_patterns = array(
            '//*[contains(@class, "old") and contains(@class, "price")]',
            '//*[contains(@class, "was")]',
            '//*[contains(@class, "original")]',
            '//*[contains(@class, "before")]'
        );

        foreach ($old_price_patterns as $pattern) {
            $elements = $xpath->query($pattern);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                if (preg_match('/\d+[.,]\d+/', $text)) {
                    $selector = $this->generate_css_selector($element);
                    if ($selector && !$this->element_exists_in_array($detected_elements, $selector)) {
                        $detected_elements[] = array(
                            'type' => 'old_price',
                            'value' => $text,
                            'selector' => $selector,
                            'confidence' => $this->calculate_confidence($element, 'old_price')
                        );
                    }
                }
            }
        }

        // Търсене на наличност
        $availability_patterns = array(
            '//*[contains(@class, "stock") or contains(@class, "availability")]',
            '//*[contains(text(), "наличен") or contains(text(), "в наличност") or contains(text(), "available") or contains(text(), "in stock")]',
            '//*[contains(text(), "няма в наличност") or contains(text(), "изчерпан") or contains(text(), "out of stock")]'
        );

        foreach ($availability_patterns as $pattern) {
            $elements = $xpath->query($pattern);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                $selector = $this->generate_css_selector($element);
                if ($selector && !$this->element_exists_in_array($detected_elements, $selector)) {
                    $detected_elements[] = array(
                        'type' => 'availability',
                        'value' => $text,
                        'selector' => $selector,
                        'confidence' => $this->calculate_confidence($element, 'availability')
                    );
                }
            }
        }

        // Търсене на милилитри/варианти
        $ml_patterns = array(
            '//*[contains(text(), "ml") or contains(text(), "мл")]',
            '//*[contains(@class, "size") or contains(@class, "variant") or contains(@class, "option")]'
        );

        foreach ($ml_patterns as $pattern) {
            $elements = $xpath->query($pattern);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                if (preg_match('/\d+\s*(ml|мл)/i', $text)) {
                    $selector = $this->generate_css_selector($element);
                    if ($selector && !$this->element_exists_in_array($detected_elements, $selector)) {
                        $detected_elements[] = array(
                            'type' => 'ml_options',
                            'value' => $text,
                            'selector' => $selector,
                            'confidence' => $this->calculate_confidence($element, 'ml_options')
                        );
                    }
                }
            }
        }

        // Търсене на доставка
        $delivery_patterns = array(
            '//*[contains(@class, "shipping") or contains(@class, "delivery")]',
            '//*[contains(text(), "доставка") or contains(text(), "shipping") or contains(text(), "delivery")]',
            '//*[contains(text(), "безплатна") or contains(text(), "free")]'
        );

        foreach ($delivery_patterns as $pattern) {
            $elements = $xpath->query($pattern);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                $selector = $this->generate_css_selector($element);
                if ($selector && !$this->element_exists_in_array($detected_elements, $selector)) {
                    $detected_elements[] = array(
                        'type' => 'delivery',
                        'value' => $text,
                        'selector' => $selector,
                        'confidence' => $this->calculate_confidence($element, 'delivery')
                    );
                }
            }
        }

        // Сортиране по confidence score
        usort($detected_elements, function($a, $b) {
            return $b['confidence'] - $a['confidence'];
        });

        // Ограничаване на резултатите
        return array_slice($detected_elements, 0, 20);
    }

    /**
     * Генериране на CSS селектор за елемент
     */
    private function generate_css_selector($element) {
        $selectors = array();

        // ID селектор
        if ($element->hasAttribute('id')) {
            return '#' . $element->getAttribute('id');
        }

        // Клас селектор
        if ($element->hasAttribute('class')) {
            $classes = explode(' ', trim($element->getAttribute('class')));
            $classes = array_filter($classes);
            if (!empty($classes)) {
                return '.' . implode('.', $classes);
            }
        }

        // Data атрибути
        foreach ($element->attributes as $attr) {
            if (strpos($attr->name, 'data-') === 0) {
                return '[' . $attr->name . '="' . $attr->value . '"]';
            }
        }

        // Tag селектор с позиция
        $tag = $element->tagName;
        $parent = $element->parentNode;
        
        if ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
            $siblings = array();
            foreach ($parent->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName === $tag) {
                    $siblings[] = $child;
                }
            }
            
            if (count($siblings) > 1) {
                $index = array_search($element, $siblings, true) + 1;
                return $tag . ':nth-of-type(' . $index . ')';
            }
        }

        return $tag;
    }

    /**
     * Проверка дали елемент вече съществува в масива
     */
    private function element_exists_in_array($elements, $selector) {
        foreach ($elements as $element) {
            if ($element['selector'] === $selector) {
                return true;
            }
        }
        return false;
    }

    /**
     * Изчисляване на confidence score за елемент
     */
    private function calculate_confidence($element, $type) {
        $confidence = 0;

        // Базов score според типа
        $base_scores = array(
            'price' => 50,
            'old_price' => 40,
            'availability' => 30,
            'ml_options' => 30,
            'delivery' => 20
        );

        $confidence += isset($base_scores[$type]) ? $base_scores[$type] : 10;

        // Бонус за ID
        if ($element->hasAttribute('id')) {
            $confidence += 20;
        }

        // Бонус за подходящи класове
        if ($element->hasAttribute('class')) {
            $classes = strtolower($element->getAttribute('class'));
            $relevant_keywords = array(
                'price' => array('price', 'cost', 'amount'),
                'old_price' => array('old', 'was', 'original', 'before'),
                'availability' => array('stock', 'availability', 'available', 'in-stock'),
                'ml_options' => array('size', 'variant', 'option', 'ml'),
                'delivery' => array('shipping', 'delivery', 'free')
            );

            if (isset($relevant_keywords[$type])) {
                foreach ($relevant_keywords[$type] as $keyword) {
                    if (strpos($classes, $keyword) !== false) {
                        $confidence += 15;
                    }
                }
            }
        }

        // Бонус за data атрибути
        foreach ($element->attributes as $attr) {
            if (strpos($attr->name, 'data-') === 0) {
                $confidence += 10;
                break;
            }
        }

        // Намаляване за твърде дълъг текст
        $text_length = strlen(trim($element->textContent));
        if ($text_length > 100) {
            $confidence -= 10;
        }

        return max(0, min(100, $confidence));
    }

    /**
     * Извличане на информация за страницата
     */
    private function extract_page_info($html_content) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html_content);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        
        $info = array();

        // Заглавие
        $title_elements = $xpath->query('//title');
        if ($title_elements->length > 0) {
            $info['title'] = trim($title_elements->item(0)->textContent);
        }

        // Meta description
        $meta_desc = $xpath->query('//meta[@name="description"]');
        if ($meta_desc->length > 0) {
            $info['description'] = $meta_desc->item(0)->getAttribute('content');
        }

        // Размер на съдържанието
        $info['content_length'] = strlen($html_content);

        // Брой елементи
        $info['element_count'] = $xpath->query('//*')->length;

        return $info;
    }

    /**
     * Подготовка на HTML за preview
     */
    private function prepare_html_for_preview($html_content, $base_url) {
        // Почистване на HTML
        $html_content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html_content);
        $html_content = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $html_content);
        
        // Конвертиране на относителни URL-и в абсолютни
        $parsed_url = parse_url($base_url);
        $base_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        
        $html_content = preg_replace('/src="\/([^"]*)"/', 'src="' . $base_domain . '/$1"', $html_content);
        $html_content = preg_replace('/href="\/([^"]*)"/', 'href="' . $base_domain . '/$1"', $html_content);
        
        // Добавяне на стилове за highlight
        $highlight_styles = '
        <style>
            .parfume-highlight-price { background: yellow !important; border: 2px solid orange !important; }
            .parfume-highlight-availability { background: lightgreen !important; border: 2px solid green !important; }
            .parfume-highlight-variants { background: lightblue !important; border: 2px solid blue !important; }
            .parfume-highlight-delivery { background: lightcoral !important; border: 2px solid red !important; }
        </style>';
        
        $html_content = str_replace('</head>', $highlight_styles . '</head>', $html_content);
        
        return $html_content;
    }

    /**
     * Обновяване на статуса на схема
     */
    private function update_schema_status($store_id, $status, $error_message = null) {
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        
        if (isset($schemas[$store_id])) {
            $schemas[$store_id]['status'] = $status;
            $schemas[$store_id]['last_test'] = current_time('mysql');
            
            if ($error_message) {
                $schemas[$store_id]['last_error'] = $error_message;
            } elseif (isset($schemas[$store_id]['last_error'])) {
                unset($schemas[$store_id]['last_error']);
            }
            
            update_option('parfume_catalog_scraper_schemas', $schemas);
        }
    }

    /**
     * Получаване на схема за магазин
     */
    public function get_store_schema($store_id) {
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        return isset($schemas[$store_id]) ? $schemas[$store_id] : array();
    }

    /**
     * Изтриване на схема
     */
    public function delete_store_schema($store_id) {
        $schemas = get_option('parfume_catalog_scraper_schemas', array());
        
        if (isset($schemas[$store_id])) {
            unset($schemas[$store_id]);
            return update_option('parfume_catalog_scraper_schemas', $schemas);
        }
        
        return false;
    }
}

// Initialize the scraper test tool module
new Parfume_Catalog_Scraper_Test_Tool();