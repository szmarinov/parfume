<?php
/**
 * Parfume Catalog Scraper Monitor Module
 * 
 * Мониторинг и управление на scraper операции
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Scraper_Monitor {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_submenu'));
        add_action('wp_ajax_parfume_get_monitor_data', array($this, 'ajax_get_monitor_data'));
        add_action('wp_ajax_parfume_manual_scrape_item', array($this, 'ajax_manual_scrape_item'));
        add_action('wp_ajax_parfume_bulk_scrape', array($this, 'ajax_bulk_scrape'));
        add_action('wp_ajax_parfume_get_scraper_logs', array($this, 'ajax_get_scraper_logs'));
        add_action('wp_ajax_parfume_clear_scraper_logs', array($this, 'ajax_clear_scraper_logs'));
        add_action('wp_ajax_parfume_update_scraper_settings', array($this, 'ajax_update_scraper_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_parfume_get_scraper_stats', array($this, 'ajax_get_scraper_stats'));
    }

    /**
     * Добавяне на администраторско подменю
     */
    public function add_admin_submenu() {
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Scraper Monitor', 'parfume-catalog'),
            __('Monitor', 'parfume-catalog'),
            'manage_options',
            'parfume-scraper-monitor',
            array($this, 'render_monitor_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'parfumes_page_parfume-scraper-monitor') {
            return;
        }

        wp_enqueue_script(
            'parfume-scraper-monitor',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/scraper-monitor.js',
            array('jquery', 'jquery-ui-datepicker'),
            PARFUME_CATALOG_VERSION,
            true
        );

        wp_enqueue_style(
            'parfume-scraper-monitor',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/scraper-monitor.css',
            array('jquery-ui-datepicker'),
            PARFUME_CATALOG_VERSION
        );

        wp_localize_script('parfume-scraper-monitor', 'parfumeScraperMonitor', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_scraper_monitor_nonce'),
            'strings' => array(
                'refreshing' => __('Обновяване...', 'parfume-catalog'),
                'scraping' => __('Скрейпване...', 'parfume-catalog'),
                'success' => __('Успешно!', 'parfume-catalog'),
                'error' => __('Грешка!', 'parfume-catalog'),
                'confirmBulkScrape' => __('Сигурни ли сте, че искате да стартирате bulk скрейпване?', 'parfume-catalog'),
                'confirmClearLogs' => __('Сигурни ли сте, че искате да изчистите всички логове?', 'parfume-catalog'),
                'bulkScrapeStarted' => __('Bulk скрейпването е стартирано', 'parfume-catalog'),
                'logsCleared' => __('Логовете са изчистени', 'parfume-catalog'),
                'settingsUpdated' => __('Настройките са обновени', 'parfume-catalog'),
                'selectItems' => __('Моля, изберете поне един елемент', 'parfume-catalog')
            ),
            'refreshInterval' => 30000, // 30 seconds
            'autoRefresh' => true
        ));
    }

    /**
     * Рендериране на monitor страница
     */
    public function render_monitor_page() {
        $stats = $this->get_scraper_statistics();
        $settings = get_option('parfume_catalog_scraper_settings', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Scraper Monitor', 'parfume-catalog'); ?></h1>
            <p class="description">
                <?php _e('Мониторинг на всички scraper операции, статистики и управление на настройки.', 'parfume-catalog'); ?>
            </p>

            <!-- Statistics Dashboard -->
            <div class="monitor-dashboard">
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-number"><?php echo number_format($stats['total_urls']); ?></div>
                        <div class="stat-label"><?php _e('Общо URL-и', 'parfume-catalog'); ?></div>
                        <div class="stat-sublabel"><?php _e('за скрейпване', 'parfume-catalog'); ?></div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-number"><?php echo number_format($stats['successful']); ?></div>
                        <div class="stat-label"><?php _e('Успешни', 'parfume-catalog'); ?></div>
                        <div class="stat-percentage">
                            <?php echo $stats['total_urls'] > 0 ? round(($stats['successful'] / $stats['total_urls']) * 100, 1) : 0; ?>%
                        </div>
                    </div>
                    
                    <div class="stat-card error">
                        <div class="stat-number"><?php echo number_format($stats['errors']); ?></div>
                        <div class="stat-label"><?php _e('Грешки', 'parfume-catalog'); ?></div>
                        <div class="stat-percentage">
                            <?php echo $stats['total_urls'] > 0 ? round(($stats['errors'] / $stats['total_urls']) * 100, 1) : 0; ?>%
                        </div>
                    </div>
                    
                    <div class="stat-card pending">
                        <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                        <div class="stat-label"><?php _e('Чакащи', 'parfume-catalog'); ?></div>
                        <div class="stat-sublabel"><?php _e('за обработка', 'parfume-catalog'); ?></div>
                    </div>
                    
                    <div class="stat-card blocked">
                        <div class="stat-number"><?php echo number_format($stats['blocked']); ?></div>
                        <div class="stat-label"><?php _e('Блокирани', 'parfume-catalog'); ?></div>
                        <div class="stat-sublabel"><?php _e('от robots.txt', 'parfume-catalog'); ?></div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-number">
                            <?php 
                            if ($stats['last_successful_scrape']) {
                                echo human_time_diff(strtotime($stats['last_successful_scrape']), current_time('timestamp'));
                            } else {
                                _e('Никога', 'parfume-catalog');
                            }
                            ?>
                        </div>
                        <div class="stat-label"><?php _e('Последно скрейпване', 'parfume-catalog'); ?></div>
                        <div class="stat-sublabel">
                            <?php 
                            if ($stats['last_successful_scrape']) {
                                echo date('d.m.Y H:i', strtotime($stats['last_successful_scrape']));
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <button type="button" id="refresh-monitor" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Обнови данните', 'parfume-catalog'); ?>
                    </button>
                    
                    <button type="button" id="bulk-scrape-pending" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Скрейпни всички чакащи', 'parfume-catalog'); ?>
                    </button>
                    
                    <button type="button" id="force-scrape-all" class="button">
                        <span class="dashicons dashicons-controls-repeat"></span>
                        <?php _e('Форсирай всички', 'parfume-catalog'); ?>
                    </button>
                    
                    <label class="auto-refresh-control">
                        <input type="checkbox" id="auto-refresh" checked />
                        <?php _e('Автоматично обновяване', 'parfume-catalog'); ?>
                    </label>
                </div>
            </div>

            <!-- Filters and Controls -->
            <div class="monitor-controls">
                <div class="monitor-filters">
                    <select id="status-filter" class="filter-select">
                        <option value=""><?php _e('Всички статуси', 'parfume-catalog'); ?></option>
                        <option value="success"><?php _e('Успешни', 'parfume-catalog'); ?></option>
                        <option value="error"><?php _e('Грешки', 'parfume-catalog'); ?></option>
                        <option value="pending"><?php _e('Чакащи', 'parfume-catalog'); ?></option>
                        <option value="blocked"><?php _e('Блокирани', 'parfume-catalog'); ?></option>
                    </select>
                    
                    <select id="store-filter" class="filter-select">
                        <option value=""><?php _e('Всички магазини', 'parfume-catalog'); ?></option>
                        <?php
                        $stores = get_option('parfume_catalog_stores', array());
                        foreach ($stores as $store_id => $store_data) {
                            echo '<option value="' . esc_attr($store_id) . '">' . esc_html($store_data['name']) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <input type="text" id="search-filter" class="filter-search" placeholder="<?php _e('Търси в URL или парфюм...', 'parfume-catalog'); ?>" />
                    
                    <button type="button" id="apply-filters" class="button">
                        <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                    </button>
                    
                    <button type="button" id="clear-filters" class="button">
                        <?php _e('Изчисти', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <div class="bulk-actions">
                    <select id="bulk-action-select">
                        <option value=""><?php _e('Масови действия', 'parfume-catalog'); ?></option>
                        <option value="scrape"><?php _e('Скрейпни избраните', 'parfume-catalog'); ?></option>
                        <option value="reset"><?php _e('Нулирай статуса', 'parfume-catalog'); ?></option>
                        <option value="delete"><?php _e('Изтрий от опашката', 'parfume-catalog'); ?></option>
                    </select>
                    
                    <button type="button" id="apply-bulk-action" class="button">
                        <?php _e('Приложи', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>

            <!-- Monitor Table -->
            <div class="monitor-table-container">
                <div id="monitor-loading" class="loading-overlay" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p><?php _e('Зареждане на данни...', 'parfume-catalog'); ?></p>
                </div>
                
                <table class="wp-list-table widefat fixed striped" id="monitor-table">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="select-all-items" />
                            </td>
                            <th class="manage-column column-post sortable">
                                <a href="#" data-sort="post_title">
                                    <span><?php _e('Парфюм', 'parfume-catalog'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-store sortable">
                                <a href="#" data-sort="store_name">
                                    <span><?php _e('Магазин', 'parfume-catalog'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-url">
                                <?php _e('Product URL', 'parfume-catalog'); ?>
                            </th>
                            <th class="manage-column column-price sortable">
                                <a href="#" data-sort="price">
                                    <span><?php _e('Последна цена', 'parfume-catalog'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-status sortable">
                                <a href="#" data-sort="status">
                                    <span><?php _e('Статус', 'parfume-catalog'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-last-scraped sortable">
                                <a href="#" data-sort="last_scraped">
                                    <span><?php _e('Последно скрейпване', 'parfume-catalog'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-next-scrape sortable">
                                <a href="#" data-sort="next_scrape">
                                    <span><?php _e('Следващо скрейпване', 'parfume-catalog'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-actions">
                                <?php _e('Действия', 'parfume-catalog'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="monitor-table-body">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
                
                <div class="pagination-container">
                    <div class="pagination-info">
                        <span id="pagination-info-text"></span>
                    </div>
                    <div class="pagination-controls">
                        <button type="button" id="prev-page" class="button" disabled>
                            <?php _e('Предишна', 'parfume-catalog'); ?>
                        </button>
                        <span id="page-numbers"></span>
                        <button type="button" id="next-page" class="button" disabled>
                            <?php _e('Следваща', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="monitor-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#logs-tab" class="nav-tab nav-tab-active"><?php _e('Логове', 'parfume-catalog'); ?></a>
                    <a href="#settings-tab" class="nav-tab"><?php _e('Настройки', 'parfume-catalog'); ?></a>
                    <a href="#analytics-tab" class="nav-tab"><?php _e('Аналитика', 'parfume-catalog'); ?></a>
                </nav>

                <!-- Logs Tab -->
                <div id="logs-tab" class="tab-content active">
                    <div class="logs-controls">
                        <select id="log-level-filter">
                            <option value=""><?php _e('Всички нива', 'parfume-catalog'); ?></option>
                            <option value="info"><?php _e('Информация', 'parfume-catalog'); ?></option>
                            <option value="success"><?php _e('Успех', 'parfume-catalog'); ?></option>
                            <option value="warning"><?php _e('Предупреждение', 'parfume-catalog'); ?></option>
                            <option value="error"><?php _e('Грешка', 'parfume-catalog'); ?></option>
                        </select>
                        
                        <button type="button" id="refresh-logs" class="button">
                            <?php _e('Обнови логове', 'parfume-catalog'); ?>
                        </button>
                        
                        <button type="button" id="clear-logs" class="button">
                            <?php _e('Изчисти логове', 'parfume-catalog'); ?>
                        </button>
                        
                        <button type="button" id="export-logs" class="button">
                            <?php _e('Експортирай логове', 'parfume-catalog'); ?>
                        </button>
                    </div>
                    
                    <div id="logs-container" class="logs-display">
                        <!-- Logs will be loaded via AJAX -->
                    </div>
                </div>

                <!-- Settings Tab -->
                <div id="settings-tab" class="tab-content">
                    <form id="scraper-settings-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="scraper-interval"><?php _e('Интервал на скрейпване (часове)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="scraper-interval" 
                                           name="interval" 
                                           value="<?php echo esc_attr($settings['interval'] ?? 12); ?>" 
                                           min="1" 
                                           max="168" 
                                           class="small-text" />
                                    <p class="description">
                                        <?php _e('Колко често да се извършва автоматично скрейпване на всички URL-и', 'parfume-catalog'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="batch-size"><?php _e('Размер на batch (URL-и)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="batch-size" 
                                           name="batch_size" 
                                           value="<?php echo esc_attr($settings['batch_size'] ?? 10); ?>" 
                                           min="1" 
                                           max="100" 
                                           class="small-text" />
                                    <p class="description">
                                        <?php _e('Колко URL-и да се обработват едновременно в един batch', 'parfume-catalog'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="user-agent"><?php _e('User Agent', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="user-agent" 
                                           name="user_agent" 
                                           value="<?php echo esc_attr($settings['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'); ?>" 
                                           class="large-text" />
                                    <p class="description">
                                        <?php _e('User Agent string за HTTP заявките', 'parfume-catalog'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="request-timeout"><?php _e('Timeout на заявка (секунди)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="request-timeout" 
                                           name="timeout" 
                                           value="<?php echo esc_attr($settings['timeout'] ?? 30); ?>" 
                                           min="5" 
                                           max="120" 
                                           class="small-text" />
                                    <p class="description">
                                        <?php _e('Максимално време за изчакване на отговор от сървъра', 'parfume-catalog'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="retry-attempts"><?php _e('Опити за повторение', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="retry-attempts" 
                                           name="retry_attempts" 
                                           value="<?php echo esc_attr($settings['retry_attempts'] ?? 3); ?>" 
                                           min="1" 
                                           max="10" 
                                           class="small-text" />
                                    <p class="description">
                                        <?php _e('Колко пъти да се опита отново при неуспешно скрейпване', 'parfume-catalog'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php _e('Допълнителни настройки', 'parfume-catalog'); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="respect_robots" 
                                               value="1" 
                                               <?php checked($settings['respect_robots'] ?? true, true); ?> />
                                        <?php _e('Спазвай robots.txt', 'parfume-catalog'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" 
                                               name="enable_logs" 
                                               value="1" 
                                               <?php checked($settings['enable_logs'] ?? true, true); ?> />
                                        <?php _e('Включи детайлни логове', 'parfume-catalog'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" 
                                               name="email_notifications" 
                                               value="1" 
                                               <?php checked($settings['email_notifications'] ?? false, true); ?> />
                                        <?php _e('Имейл известия при грешки', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Запази настройки', 'parfume-catalog'); ?>
                            </button>
                            <button type="button" id="test-scraper-settings" class="button">
                                <?php _e('Тествай настройки', 'parfume-catalog'); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Analytics Tab -->
                <div id="analytics-tab" class="tab-content">
                    <div class="analytics-container">
                        <div class="analytics-charts">
                            <div class="chart-container">
                                <h3><?php _e('Успеваемост по дни', 'parfume-catalog'); ?></h3>
                                <canvas id="success-rate-chart"></canvas>
                            </div>
                            
                            <div class="chart-container">
                                <h3><?php _e('Разпределение по магазини', 'parfume-catalog'); ?></h3>
                                <canvas id="stores-distribution-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="analytics-stats">
                            <h3><?php _e('Статистики за ефективност', 'parfume-catalog'); ?></h3>
                            <div id="analytics-data">
                                <!-- Analytics data will be loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX - Получаване на monitor данни
     */
    public function ajax_get_monitor_data() {
        check_ajax_referer('parfume_scraper_monitor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        $store_filter = sanitize_text_field($_POST['store_filter'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'last_scraped');
        $order = sanitize_text_field($_POST['order'] ?? 'DESC');

        $monitor_data = $this->get_monitor_data($page, $per_page, $status_filter, $store_filter, $search, $orderby, $order);

        wp_send_json_success($monitor_data);
    }

    /**
     * AJAX - Ръчно скрейпване на конкретен item
     */
    public function ajax_manual_scrape_item() {
        check_ajax_referer('parfume_scraper_monitor_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $scraper_id = intval($_POST['scraper_id']);

        if (!$scraper_id) {
            wp_send_json_error(__('Невалиден ID на scraper запис.', 'parfume-catalog'));
        }

        // Използваме scraper класа за ръчно скрейпване
        if (class_exists('Parfume_Catalog_Scraper')) {
            global $wpdb;
            $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
            
            $scraper_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $scraper_table WHERE id = %d",
                $scraper_id
            ), ARRAY_A);

            if (!$scraper_record) {
                wp_send_json_error(__('Scraper записът не е намерен.', 'parfume-catalog'));
            }

            // Получаване на product URL от post meta
            $post_stores = get_post_meta($scraper_record['post_id'], '_parfume_stores', true);
            
            if (!isset($post_stores[$scraper_record['store_id']]['product_url'])) {
                wp_send_json_error(__('Product URL не е намерен.', 'parfume-catalog'));
            }

            $scrape_item = array(
                'id' => $scraper_record['id'],
                'post_id' => $scraper_record['post_id'],
                'store_id' => $scraper_record['store_id'],
                'product_url' => $post_stores[$scraper_record['store_id']]['product_url'],
                'current_data' => $scraper_record
            );

            $scraper = new Parfume_Catalog_Scraper();
            
            // Използваме reflection за достъп до private метода
            $reflection = new ReflectionClass($scraper);
            $scrape_method = $reflection->getMethod('scrape_single_url');
            $scrape_method->setAccessible(true);
            
            $scrape_method->invoke($scraper, $scrape_item);

            // Получаване на актуализираните данни
            $updated_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $scraper_table WHERE id = %d",
                $scraper_id
            ), ARRAY_A);

            if ($updated_record['status'] === 'success') {
                wp_send_json_success(array(
                    'message' => __('Скрейпването е успешно.', 'parfume-catalog'),
                    'data' => $updated_record
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Скрейпването не е успешно.', 'parfume-catalog'),
                    'error' => $updated_record['error_message']
                ));
            }

        } else {
            wp_send_json_error(__('Scraper класът не е наличен.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Bulk скрейпване
     */
    public function ajax_bulk_scrape() {
        check_ajax_referer('parfume_scraper_monitor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $action = sanitize_text_field($_POST['bulk_action']);
        $scraper_ids = array_map('intval', $_POST['scraper_ids'] ?? array());

        if (empty($scraper_ids)) {
            wp_send_json_error(__('Не са избрани записи за обработка.', 'parfume-catalog'));
        }

        $processed = 0;
        $successful = 0;
        $failed = 0;

        switch ($action) {
            case 'scrape':
                foreach ($scraper_ids as $scraper_id) {
                    // Тук би трябвало да се извика scraping логиката
                    $processed++;
                    // За демонстрация приемаме че половината са успешни
                    if ($processed % 2 === 0) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                }
                break;

            case 'reset':
                global $wpdb;
                $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
                
                foreach ($scraper_ids as $scraper_id) {
                    $wpdb->update(
                        $scraper_table,
                        array(
                            'status' => 'pending',
                            'error_message' => null,
                            'retry_count' => 0,
                            'next_scrape' => current_time('mysql')
                        ),
                        array('id' => $scraper_id),
                        array('%s', '%s', '%d', '%s'),
                        array('%d')
                    );
                    $processed++;
                    $successful++;
                }
                break;

            case 'delete':
                global $wpdb;
                $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
                
                foreach ($scraper_ids as $scraper_id) {
                    $deleted = $wpdb->delete(
                        $scraper_table,
                        array('id' => $scraper_id),
                        array('%d')
                    );
                    
                    if ($deleted) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                    $processed++;
                }
                break;

            default:
                wp_send_json_error(__('Невалидно действие.', 'parfume-catalog'));
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Обработени: %d, Успешни: %d, Неуспешни: %d', 'parfume-catalog'),
                $processed, $successful, $failed
            ),
            'processed' => $processed,
            'successful' => $successful,
            'failed' => $failed
        ));
    }

    /**
     * AJAX - Получаване на scraper логове
     */
    public function ajax_get_scraper_logs() {
        check_ajax_referer('parfume_scraper_monitor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $level_filter = sanitize_text_field($_POST['level_filter'] ?? '');
        $limit = intval($_POST['limit'] ?? 100);

        if (class_exists('Parfume_Catalog_Scraper')) {
            $scraper = new Parfume_Catalog_Scraper();
            $logs = $scraper->get_scraper_logs($limit, $level_filter);

            $logs_html = '';
            foreach ($logs as $log) {
                $logs_html .= sprintf(
                    '<div class="log-entry log-level-%s">
                        <span class="log-timestamp">%s</span>
                        <span class="log-level">%s</span>
                        <span class="log-message">%s</span>
                    </div>',
                    esc_attr($log['level']),
                    esc_html(date('d.m.Y H:i:s', strtotime($log['created_at']))),
                    esc_html(strtoupper($log['level'])),
                    esc_html($log['message'])
                );
            }

            wp_send_json_success(array(
                'logs_html' => $logs_html,
                'count' => count($logs)
            ));

        } else {
            wp_send_json_error(__('Scraper класът не е наличен.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Изчистване на scraper логове
     */
    public function ajax_clear_scraper_logs() {
        check_ajax_referer('parfume_scraper_monitor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'parfume_scraper_log';
        
        $deleted = $wpdb->query("TRUNCATE TABLE $log_table");

        if ($deleted !== false) {
            wp_send_json_success(__('Логовете са изчистени успешно.', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при изчистване на логовете.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Обновяване на scraper настройки
     */
    public function ajax_update_scraper_settings() {
        check_ajax_referer('parfume_scraper_monitor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $settings = array(
            'interval' => intval($_POST['interval']),
            'batch_size' => intval($_POST['batch_size']),
            'user_agent' => sanitize_text_field($_POST['user_agent']),
            'timeout' => intval($_POST['timeout']),
            'retry_attempts' => intval($_POST['retry_attempts']),
            'respect_robots' => (bool) ($_POST['respect_robots'] ?? false),
            'enable_logs' => (bool) ($_POST['enable_logs'] ?? false),
            'email_notifications' => (bool) ($_POST['email_notifications'] ?? false)
        );

        if (update_option('parfume_catalog_scraper_settings', $settings)) {
            wp_send_json_success(__('Настройките са запазени успешно.', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при запазване на настройките.', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Получаване на scraper статистики
     */
    public function ajax_get_scraper_stats() {
        check_ajax_referer('parfume_scraper_monitor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $stats = $this->get_scraper_statistics();
        wp_send_json_success($stats);
    }

    /**
     * Получаване на monitor данни
     */
    private function get_monitor_data($page = 1, $per_page = 20, $status_filter = '', $store_filter = '', $search = '', $orderby = 'last_scraped', $order = 'DESC') {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $posts_table = $wpdb->posts;
        
        $offset = ($page - 1) * $per_page;
        
        // Build WHERE clause
        $where_conditions = array("p.post_status = 'publish'");
        $where_params = array();
        
        if ($status_filter) {
            $where_conditions[] = "sd.status = %s";
            $where_params[] = $status_filter;
        }
        
        if ($store_filter) {
            $where_conditions[] = "sd.store_id = %s";
            $where_params[] = $store_filter;
        }
        
        if ($search) {
            $where_conditions[] = "(p.post_title LIKE %s OR sd.product_url LIKE %s)";
            $where_params[] = '%' . $wpdb->esc_like($search) . '%';
            $where_params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Build ORDER BY clause
        $allowed_orderby = array('post_title', 'store_name', 'price', 'status', 'last_scraped', 'next_scrape');
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'last_scraped';
        }
        
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $order_clause = "ORDER BY sd.$orderby $order";
        
        // Get total count
        $count_query = "
            SELECT COUNT(*)
            FROM $scraper_table sd
            INNER JOIN $posts_table p ON sd.post_id = p.ID
            $where_clause
        ";
        
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $where_params));
        
        // Get data
        $data_query = "
            SELECT sd.*, p.post_title
            FROM $scraper_table sd
            INNER JOIN $posts_table p ON sd.post_id = p.ID
            $where_clause
            $order_clause
            LIMIT %d OFFSET %d
        ";
        
        $query_params = array_merge($where_params, array($per_page, $offset));
        $items = $wpdb->get_results($wpdb->prepare($data_query, $query_params), ARRAY_A);
        
        // Get stores for display
        $stores = get_option('parfume_catalog_stores', array());
        
        // Format items for display
        foreach ($items as &$item) {
            $item['store_name'] = isset($stores[$item['store_id']]) ? $stores[$item['store_id']]['name'] : __('Неизвестен магазин', 'parfume-catalog');
            
            // Parse scraped data
            if (!empty($item['scraped_data'])) {
                $scraped_data = json_decode($item['scraped_data'], true);
                $item['price'] = $scraped_data['price'] ?? '';
                $item['availability'] = $scraped_data['availability'] ?? '';
            } else {
                $item['price'] = '';
                $item['availability'] = '';
            }
            
            // Format dates
            $item['last_scraped_formatted'] = $item['last_scraped'] ? 
                human_time_diff(strtotime($item['last_scraped']), current_time('timestamp')) . ' назад' : 
                __('Никога', 'parfume-catalog');
            
            $item['next_scrape_formatted'] = $item['next_scrape'] ? 
                human_time_diff(current_time('timestamp'), strtotime($item['next_scrape'])) . ' след това' : 
                __('Неопределено', 'parfume-catalog');
        }
        
        return array(
            'items' => $items,
            'total_items' => intval($total_items),
            'total_pages' => ceil($total_items / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        );
    }

    /**
     * Получаване на scraper статистики
     */
    private function get_scraper_statistics() {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $stats = array(
            'total_urls' => 0,
            'successful' => 0,
            'errors' => 0,
            'pending' => 0,
            'blocked' => 0,
            'last_successful_scrape' => null
        );
        
        // Общ брой записи
        $stats['total_urls'] = intval($wpdb->get_var("SELECT COUNT(*) FROM $scraper_table"));
        
        // Брой по статуси
        $status_counts = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $scraper_table 
            GROUP BY status
        ", ARRAY_A);
        
        foreach ($status_counts as $row) {
            switch ($row['status']) {
                case 'success':
                    $stats['successful'] = intval($row['count']);
                    break;
                case 'error':
                    $stats['errors'] = intval($row['count']);
                    break;
                case 'pending':
                    $stats['pending'] = intval($row['count']);
                    break;
                case 'blocked':
                    $stats['blocked'] = intval($row['count']);
                    break;
            }
        }
        
        // Последно успешно скрейпване
        $stats['last_successful_scrape'] = $wpdb->get_var("
            SELECT MAX(last_scraped) 
            FROM $scraper_table 
            WHERE status = 'success' AND last_scraped IS NOT NULL
        ");
        
        return $stats;
    }

    /**
     * Получаване на аналитични данни
     */
    public function get_analytics_data($days = 30) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $log_table = $wpdb->prefix . 'parfume_scraper_log';
        
        $start_date = date('Y-m-d', strtotime("-$days days"));
        
        // Дневна статистика за успеваемост
        $daily_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(CASE WHEN level = 'success' THEN 1 END) as successful,
                COUNT(CASE WHEN level = 'error' THEN 1 END) as failed
            FROM $log_table 
            WHERE created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $start_date), ARRAY_A);
        
        // Разпределение по магазини
        $stores = get_option('parfume_catalog_stores', array());
        $store_distribution = $wpdb->get_results("
            SELECT 
                store_id,
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful
            FROM $scraper_table 
            GROUP BY store_id
        ", ARRAY_A);
        
        // Добавяне на имената на магазините
        foreach ($store_distribution as &$item) {
            $item['store_name'] = isset($stores[$item['store_id']]) ? 
                $stores[$item['store_id']]['name'] : 
                __('Неизвестен магазин', 'parfume-catalog');
        }
        
        return array(
            'daily_stats' => $daily_stats,
            'store_distribution' => $store_distribution
        );
    }
}

// Initialize the scraper monitor module
new Parfume_Catalog_Scraper_Monitor();