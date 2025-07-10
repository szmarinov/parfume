<?php
/**
 * Parfume Catalog Admin Scraper
 * 
 * Управление на scraper система в админ панела
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin_Scraper {

    /**
     * Конструктор
     */
    public function __construct() {
        // Този клас се hook-ва в главния admin menu система
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_parfume_scraper_test_url', array($this, 'ajax_test_url'));
        add_action('wp_ajax_parfume_scraper_manual_run', array($this, 'ajax_manual_run'));
        add_action('wp_ajax_parfume_scraper_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_parfume_scraper_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_parfume_scraper_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_parfume_scraper_reset_failed', array($this, 'ajax_reset_failed'));
        add_action('wp_ajax_parfume_scraper_batch_action', array($this, 'ajax_batch_action'));
    }

    /**
     * Enqueue admin scripts и styles
     */
    public function enqueue_admin_scripts($hook) {
        // Зарежда само на scraper страницата
        if ($hook !== 'parfume-catalog_page_parfume-catalog-scraper') {
            return;
        }

        // Custom admin scripts
        wp_enqueue_script(
            'parfume-admin-scraper',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin-scraper.js',
            array('jquery', 'wp-util', 'jquery-ui-tabs'),
            PARFUME_CATALOG_VERSION,
            true
        );

        // Admin styles
        wp_enqueue_style(
            'parfume-admin-scraper',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin-scraper.css',
            array('wp-admin', 'buttons'),
            PARFUME_CATALOG_VERSION
        );

        // Chart.js за статистики
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );

        // Localize script
        wp_localize_script('parfume-admin-scraper', 'parfumeAdminScraper', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_scraper_nonce'),
            'strings' => array(
                'testing_url' => __('Тестване на URL...', 'parfume-catalog'),
                'url_valid' => __('URL-ът е валиден!', 'parfume-catalog'),
                'url_invalid' => __('URL-ът е невалиден или недостъпен!', 'parfume-catalog'),
                'running_scraper' => __('Стартиране на scraper...', 'parfume-catalog'),
                'scraper_started' => __('Scraper-ът е стартиран успешно!', 'parfume-catalog'),
                'scraper_error' => __('Грешка при стартиране на scraper-а!', 'parfume-catalog'),
                'confirm_clear_logs' => __('Сигурни ли сте, че искате да изчистите всички логове?', 'parfume-catalog'),
                'confirm_reset_failed' => __('Сигурни ли сте, че искате да рестартирате всички неуспешни задачи?', 'parfume-catalog'),
                'logs_cleared' => __('Логовете са изчистени успешно!', 'parfume-catalog'),
                'failed_reset' => __('Неуспешните задачи са рестартирани!', 'parfume-catalog'),
                'loading' => __('Зареждане...', 'parfume-catalog'),
                'error' => __('Възникна грешка!', 'parfume-catalog'),
                'success' => __('Операцията завърши успешно!', 'parfume-catalog'),
                'no_data' => __('Няма данни за показване', 'parfume-catalog')
            ),
            'settings' => array(
                'refresh_interval' => 30000, // 30 секунди
                'log_refresh_interval' => 10000, // 10 секунди
                'chart_colors' => array(
                    'success' => '#4CAF50',
                    'error' => '#f44336',
                    'pending' => '#FF9800',
                    'blocked' => '#9E9E9E'
                )
            )
        ));

        // Inline styles
        wp_add_inline_style('parfume-admin-scraper', $this->get_inline_styles());
    }

    /**
     * Рендериране на scraper admin страница
     */
    public function render_scraper_page() {
        $stats = $this->get_scraper_statistics();
        $recent_activity = $this->get_recent_activity(10);
        $settings = $this->get_scraper_settings();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Product Scraper', 'parfume-catalog'); ?></h1>
            <button type="button" id="manual-scraper-run" class="page-title-action">
                <?php _e('Ръчно стартиране', 'parfume-catalog'); ?>
            </button>
            <hr class="wp-header-end">

            <!-- Tabs Navigation -->
            <div id="scraper-tabs" class="nav-tab-wrapper">
                <a href="#tab-dashboard" class="nav-tab nav-tab-active"><?php _e('Табло', 'parfume-catalog'); ?></a>
                <a href="#tab-monitor" class="nav-tab"><?php _e('Мониторинг', 'parfume-catalog'); ?></a>
                <a href="#tab-logs" class="nav-tab"><?php _e('Логове', 'parfume-catalog'); ?></a>
                <a href="#tab-test" class="nav-tab"><?php _e('Тест инструмент', 'parfume-catalog'); ?></a>
                <a href="#tab-settings" class="nav-tab"><?php _e('Настройки', 'parfume-catalog'); ?></a>
            </div>

            <!-- Dashboard Tab -->
            <div id="tab-dashboard" class="tab-content active">
                <?php $this->render_dashboard_tab($stats, $recent_activity); ?>
            </div>

            <!-- Monitor Tab -->
            <div id="tab-monitor" class="tab-content">
                <?php $this->render_monitor_tab(); ?>
            </div>

            <!-- Logs Tab -->
            <div id="tab-logs" class="tab-content">
                <?php $this->render_logs_tab(); ?>
            </div>

            <!-- Test Tool Tab -->
            <div id="tab-test" class="tab-content">
                <?php $this->render_test_tool_tab(); ?>
            </div>

            <!-- Settings Tab -->
            <div id="tab-settings" class="tab-content">
                <?php $this->render_settings_tab($settings); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Dashboard tab content
     */
    private function render_dashboard_tab($stats, $recent_activity) {
        ?>
        <div class="scraper-dashboard">
            <!-- Statistics Cards -->
            <div class="stats-section">
                <h2><?php _e('Обща статистика', 'parfume-catalog'); ?></h2>
                <div class="stats-cards">
                    <div class="stat-card success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-details">
                            <div class="stat-number"><?php echo $stats['successful']; ?></div>
                            <div class="stat-label"><?php _e('Успешни', 'parfume-catalog'); ?></div>
                        </div>
                    </div>
                    <div class="stat-card error">
                        <div class="stat-icon">❌</div>
                        <div class="stat-details">
                            <div class="stat-number"><?php echo $stats['failed']; ?></div>
                            <div class="stat-label"><?php _e('Неуспешни', 'parfume-catalog'); ?></div>
                        </div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-details">
                            <div class="stat-number"><?php echo $stats['pending']; ?></div>
                            <div class="stat-label"><?php _e('Чакащи', 'parfume-catalog'); ?></div>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon">📊</div>
                        <div class="stat-details">
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="stat-label"><?php _e('Общо URL-и', 'parfume-catalog'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-container">
                    <h3><?php _e('Статус разпределение', 'parfume-catalog'); ?></h3>
                    <canvas id="status-chart" width="300" height="300"></canvas>
                </div>
                <div class="chart-container">
                    <h3><?php _e('Активност през последните 7 дни', 'parfume-catalog'); ?></h3>
                    <canvas id="activity-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-section">
                <h3><?php _e('Бързи действия', 'parfume-catalog'); ?></h3>
                <div class="action-buttons">
                    <button type="button" class="button button-primary" id="start-full-scrape">
                        <?php _e('Пълно скрейпване', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button" id="scrape-failed-only">
                        <?php _e('Само неуспешни', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button" id="test-random-urls">
                        <?php _e('Тест случайни URL-и', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="clear-all-logs">
                        <?php _e('Изчисти логове', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity-section">
                <h3><?php _e('Последна активност', 'parfume-catalog'); ?></h3>
                <div class="activity-list">
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item <?php echo esc_attr($activity['type']); ?>">
                                <div class="activity-time"><?php echo esc_html($activity['time']); ?></div>
                                <div class="activity-message"><?php echo esc_html($activity['message']); ?></div>
                                <?php if (!empty($activity['details'])): ?>
                                    <div class="activity-details"><?php echo esc_html($activity['details']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-activity">
                            <?php _e('Няма последна активност', 'parfume-catalog'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        // Initialize charts when dashboard loads
        jQuery(document).ready(function($) {
            // Status pie chart
            if (document.getElementById('status-chart')) {
                const statusCtx = document.getElementById('status-chart').getContext('2d');
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Успешни', 'Неуспешни', 'Чакащи'],
                        datasets: [{
                            data: [<?php echo $stats['successful']; ?>, <?php echo $stats['failed']; ?>, <?php echo $stats['pending']; ?>],
                            backgroundColor: ['#4CAF50', '#f44336', '#FF9800']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: { position: 'bottom' }
                    }
                });
            }

            // Activity line chart
            if (document.getElementById('activity-chart')) {
                const activityCtx = document.getElementById('activity-chart').getContext('2d');
                new Chart(activityCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($this->get_activity_chart_labels()); ?>,
                        datasets: [{
                            label: 'Скрейпнати URL-и',
                            data: <?php echo json_encode($this->get_activity_chart_data()); ?>,
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Monitor tab content
     */
    private function render_monitor_tab() {
        ?>
        <div class="scraper-monitor">
            <div class="monitor-controls">
                <h2><?php _e('Мониторинг в реално време', 'parfume-catalog'); ?></h2>
                <div class="controls-row">
                    <button type="button" class="button" id="refresh-monitor">
                        <?php _e('Обнови данните', 'parfume-catalog'); ?>
                    </button>
                    <label for="auto-refresh">
                        <input type="checkbox" id="auto-refresh" checked>
                        <?php _e('Автоматично обновяване', 'parfume-catalog'); ?>
                    </label>
                    <select id="filter-status">
                        <option value=""><?php _e('Всички статуси', 'parfume-catalog'); ?></option>
                        <option value="success"><?php _e('Успешни', 'parfume-catalog'); ?></option>
                        <option value="error"><?php _e('Грешка', 'parfume-catalog'); ?></option>
                        <option value="pending"><?php _e('Чакащи', 'parfume-catalog'); ?></option>
                        <option value="blocked"><?php _e('Блокирани', 'parfume-catalog'); ?></option>
                    </select>
                </div>
            </div>

            <div class="monitor-table-container">
                <table class="wp-list-table widefat fixed striped" id="scraper-monitor-table">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column">
                                <input type="checkbox" id="select-all-monitor">
                            </th>
                            <th scope="col" class="manage-column"><?php _e('Парфюм', 'parfume-catalog'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Магазин', 'parfume-catalog'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('URL', 'parfume-catalog'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Статус', 'parfume-catalog'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Последно скрейпване', 'parfume-catalog'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Следващо', 'parfume-catalog'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Действия', 'parfume-catalog'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="monitor-table-body">
                        <!-- Dynamic content loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="monitor-pagination">
                <div class="tablenav">
                    <div class="alignleft actions bulkactions">
                        <select id="bulk-action">
                            <option value=""><?php _e('Масови действия', 'parfume-catalog'); ?></option>
                            <option value="scrape"><?php _e('Скрейпни', 'parfume-catalog'); ?></option>
                            <option value="reset"><?php _e('Рестартирай', 'parfume-catalog'); ?></option>
                            <option value="delete"><?php _e('Изтрий', 'parfume-catalog'); ?></option>
                        </select>
                        <button type="button" class="button" id="apply-bulk-action">
                            <?php _e('Приложи', 'parfume-catalog'); ?>
                        </button>
                    </div>
                    <div class="tablenav-pages" id="monitor-pagination">
                        <!-- Pagination will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Logs tab content
     */
    private function render_logs_tab() {
        ?>
        <div class="scraper-logs">
            <div class="logs-controls">
                <h2><?php _e('Scraper логове', 'parfume-catalog'); ?></h2>
                <div class="controls-row">
                    <select id="log-level-filter">
                        <option value=""><?php _e('Всички нива', 'parfume-catalog'); ?></option>
                        <option value="info"><?php _e('Информация', 'parfume-catalog'); ?></option>
                        <option value="success"><?php _e('Успех', 'parfume-catalog'); ?></option>
                        <option value="warning"><?php _e('Предупреждение', 'parfume-catalog'); ?></option>
                        <option value="error"><?php _e('Грешка', 'parfume-catalog'); ?></option>
                    </select>
                    <input type="date" id="log-date-filter" />
                    <input type="text" id="log-search" placeholder="<?php esc_attr_e('Търси в логовете...', 'parfume-catalog'); ?>">
                    <button type="button" class="button" id="refresh-logs">
                        <?php _e('Обнови', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="export-logs">
                        <?php _e('Експорт', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>

            <div class="logs-container">
                <div id="logs-list">
                    <!-- Logs will be loaded dynamically -->
                </div>
            </div>

            <div class="logs-pagination">
                <button type="button" class="button" id="load-more-logs">
                    <?php _e('Зареди още логове', 'parfume-catalog'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Test Tool tab content
     */
    private function render_test_tool_tab() {
        ?>
        <div class="scraper-test-tool">
            <h2><?php _e('Scraper Test Tool', 'parfume-catalog'); ?></h2>
            <p><?php _e('Тествайте URL-и и конфигурирайте scraper схеми за магазините.', 'parfume-catalog'); ?></p>

            <div class="test-sections">
                <!-- URL Testing Section -->
                <div class="test-section">
                    <h3><?php _e('Тестване на URL', 'parfume-catalog'); ?></h3>
                    <div class="test-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="test-url"><?php _e('URL за тестване', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="test-url" class="large-text" placeholder="https://example.com/product">
                                    <button type="button" class="button button-primary" id="test-url-btn">
                                        <?php _e('Тествай URL', 'parfume-catalog'); ?>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div id="url-test-results" class="test-results" style="display: none;"></div>
                </div>

                <!-- Schema Configuration Section -->
                <div class="test-section">
                    <h3><?php _e('Конфигурация на схема', 'parfume-catalog'); ?></h3>
                    <div class="schema-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="schema-store"><?php _e('Изберете магазин', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <select id="schema-store">
                                        <option value=""><?php _e('Избери магазин...', 'parfume-catalog'); ?></option>
                                        <?php foreach ($this->get_available_stores() as $store_id => $store_name): ?>
                                            <option value="<?php echo esc_attr($store_id); ?>">
                                                <?php echo esc_html($store_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <div id="schema-selectors" style="display: none;">
                            <h4><?php _e('CSS Селектори', 'parfume-catalog'); ?></h4>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="price-selector"><?php _e('Цена', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="price-selector" class="regular-text" placeholder=".price, .product-price">
                                        <button type="button" class="button button-small test-selector" data-selector="price">
                                            <?php _e('Тест', 'parfume-catalog'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="old-price-selector"><?php _e('Стара цена', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="old-price-selector" class="regular-text" placeholder=".old-price, .was-price">
                                        <button type="button" class="button button-small test-selector" data-selector="old-price">
                                            <?php _e('Тест', 'parfume-catalog'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="availability-selector"><?php _e('Наличност', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="availability-selector" class="regular-text" placeholder=".availability, .stock-status">
                                        <button type="button" class="button button-small test-selector" data-selector="availability">
                                            <?php _e('Тест', 'parfume-catalog'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="variants-selector"><?php _e('Варианти (ml)', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="variants-selector" class="regular-text" placeholder=".variants select, .size-options">
                                        <button type="button" class="button button-small test-selector" data-selector="variants">
                                            <?php _e('Тест', 'parfume-catalog'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="delivery-selector"><?php _e('Доставка', 'parfume-catalog'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="delivery-selector" class="regular-text" placeholder=".delivery-info, .shipping">
                                        <button type="button" class="button button-small test-selector" data-selector="delivery">
                                            <?php _e('Тест', 'parfume-catalog'); ?>
                                        </button>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <button type="button" class="button button-primary" id="save-schema">
                                    <?php _e('Запази схемата', 'parfume-catalog'); ?>
                                </button>
                                <button type="button" class="button" id="test-full-schema">
                                    <?php _e('Тествай пълната схема', 'parfume-catalog'); ?>
                                </button>
                            </p>
                        </div>
                    </div>
                    <div id="schema-test-results" class="test-results" style="display: none;"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings tab content
     */
    private function render_settings_tab($settings) {
        ?>
        <div class="scraper-settings">
            <h2><?php _e('Scraper настройки', 'parfume-catalog'); ?></h2>
            
            <form method="post" action="options.php" id="scraper-settings-form">
                <?php settings_fields('parfume_catalog_scraper_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="scraper-enabled"><?php _e('Разреши scraper', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <label for="scraper-enabled">
                                <input type="checkbox" id="scraper-enabled" name="parfume_scraper_settings[enabled]" value="1" 
                                       <?php checked(!empty($settings['enabled']), true); ?>>
                                <?php _e('Включи автоматично скрейпване', 'parfume-catalog'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="scraper-interval"><?php _e('Интервал (часове)', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="scraper-interval" name="parfume_scraper_settings[interval]" 
                                   value="<?php echo esc_attr($settings['interval'] ?? 12); ?>" min="1" max="168" class="small-text">
                            <p class="description"><?php _e('На колко часа да се извършва автоматично скрейпване', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="batch-size"><?php _e('Batch размер', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="batch-size" name="parfume_scraper_settings[batch_size]" 
                                   value="<?php echo esc_attr($settings['batch_size'] ?? 10); ?>" min="1" max="100" class="small-text">
                            <p class="description"><?php _e('Брой URL-и за обработване в един batch', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="user-agent"><?php _e('User Agent', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="user-agent" name="parfume_scraper_settings[user_agent]" 
                                   value="<?php echo esc_attr($settings['user_agent'] ?? 'Mozilla/5.0 (compatible; ParfumeCatalogBot/1.0)'); ?>" class="large-text">
                            <p class="description"><?php _e('User Agent string за scraper заявките', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="timeout"><?php _e('Timeout (секунди)', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="timeout" name="parfume_scraper_settings[timeout]" 
                                   value="<?php echo esc_attr($settings['timeout'] ?? 30); ?>" min="5" max="120" class="small-text">
                            <p class="description"><?php _e('Timeout за scraper заявките', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max-retries"><?php _e('Максимум опити', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max-retries" name="parfume_scraper_settings[max_retries]" 
                                   value="<?php echo esc_attr($settings['max_retries'] ?? 3); ?>" min="1" max="10" class="small-text">
                            <p class="description"><?php _e('Максимален брой опити при грешка', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="respect-robots"><?php _e('Зачитай robots.txt', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <label for="respect-robots">
                                <input type="checkbox" id="respect-robots" name="parfume_scraper_settings[respect_robots]" value="1" 
                                       <?php checked(!empty($settings['respect_robots']), true); ?>>
                                <?php _e('Проверявай robots.txt преди скрейпване', 'parfume-catalog'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="log-level"><?php _e('Ниво на логиране', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <select id="log-level" name="parfume_scraper_settings[log_level]">
                                <option value="error" <?php selected($settings['log_level'] ?? 'info', 'error'); ?>><?php _e('Само грешки', 'parfume-catalog'); ?></option>
                                <option value="warning" <?php selected($settings['log_level'] ?? 'info', 'warning'); ?>><?php _e('Предупреждения и грешки', 'parfume-catalog'); ?></option>
                                <option value="info" <?php selected($settings['log_level'] ?? 'info', 'info'); ?>><?php _e('Вся информация', 'parfume-catalog'); ?></option>
                                <option value="debug" <?php selected($settings['log_level'] ?? 'info', 'debug'); ?>><?php _e('Debug (подробно)', 'parfume-catalog'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="log-retention"><?php _e('Съхранение на логове (дни)', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="log-retention" name="parfume_scraper_settings[log_retention]" 
                                   value="<?php echo esc_attr($settings['log_retention'] ?? 30); ?>" min="1" max="365" class="small-text">
                            <p class="description"><?php _e('Брой дни за съхранение на логовете (стари логове се изтриват автоматично)', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Запази настройки', 'parfume-catalog')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX handlers
     */
    public function ajax_test_url() {
        check_ajax_referer('parfume_admin_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $url = esc_url_raw($_POST['url']);
        
        if (empty($url)) {
            wp_send_json_error(__('Невалиден URL.', 'parfume-catalog'));
        }

        // Test URL accessibility
        $response = wp_remote_head($url, array(
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (compatible; ParfumeCatalogBot/1.0)'
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(__('URL недостъпен: ', 'parfume-catalog') . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 400) {
            // Get page content for analysis
            $content_response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'Mozilla/5.0 (compatible; ParfumeCatalogBot/1.0)'
            ));

            if (!is_wp_error($content_response)) {
                $html = wp_remote_retrieve_body($content_response);
                $analysis = $this->analyze_page_content($html);
                
                wp_send_json_success(array(
                    'message' => __('URL е достъпен и анализиран успешно!', 'parfume-catalog'),
                    'response_code' => $response_code,
                    'analysis' => $analysis
                ));
            } else {
                wp_send_json_success(array(
                    'message' => __('URL е достъпен, но има проблем с извличането на съдържанието.', 'parfume-catalog'),
                    'response_code' => $response_code
                ));
            }
        } else {
            wp_send_json_error(__('URL връща грешка: ', 'parfume-catalog') . $response_code);
        }
    }

    public function ajax_manual_run() {
        check_ajax_referer('parfume_admin_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        // Trigger manual scraper run
        if (class_exists('Parfume_Catalog_Scraper')) {
            $scraper = new Parfume_Catalog_Scraper();
            $result = $scraper->run_manual_scrape();
            
            if ($result) {
                wp_send_json_success(__('Scraper е стартиран успешно!', 'parfume-catalog'));
            } else {
                wp_send_json_error(__('Грешка при стартиране на scraper-а!', 'parfume-catalog'));
            }
        } else {
            wp_send_json_error(__('Scraper класът не е наличен!', 'parfume-catalog'));
        }
    }

    public function ajax_get_stats() {
        check_ajax_referer('parfume_admin_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $stats = $this->get_scraper_statistics();
        wp_send_json_success($stats);
    }

    public function ajax_get_logs() {
        check_ajax_referer('parfume_admin_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $page = intval($_POST['page'] ?? 1);
        $level = sanitize_text_field($_POST['level'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? '');

        $logs = $this->get_scraper_logs($page, $level, $search, $date);
        wp_send_json_success($logs);
    }

    public function ajax_clear_logs() {
        check_ajax_referer('parfume_admin_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $this->clear_scraper_logs();
        wp_send_json_success(__('Логовете са изчистени успешно!', 'parfume-catalog'));
    }

    public function ajax_reset_failed() {
        check_ajax_referer('parfume_admin_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $count = $this->reset_failed_scrapes();
        wp_send_json_success(sprintf(__('Рестартирани %d неуспешни задачи!', 'parfume-catalog'), $count));
    }

    public function ajax_batch_action() {
        check_ajax_referer('parfume_admin_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate разрешение за това действие.', 'parfume-catalog'));
        }

        $action = sanitize_text_field($_POST['action_type']);
        $items = array_map('intval', $_POST['items'] ?? array());

        if (empty($items)) {
            wp_send_json_error(__('Няма избрани елементи.', 'parfume-catalog'));
        }

        $result = $this->execute_batch_action($action, $items);
        wp_send_json_success($result);
    }

    /**
     * Helper функции
     */
    private function get_scraper_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_data';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return array(
                'successful' => 0,
                'failed' => 0,
                'pending' => 0,
                'total' => 0
            );
        }

        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
             FROM {$table_name}",
            ARRAY_A
        );

        return array(
            'successful' => intval($stats['successful']),
            'failed' => intval($stats['failed']),
            'pending' => intval($stats['pending']),
            'total' => intval($stats['total'])
        );
    }

    private function get_recent_activity($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return array();
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT level, message, created_at 
             FROM {$table_name} 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);

        $activity = array();
        foreach ($results as $result) {
            $activity[] = array(
                'type' => $result['level'],
                'message' => $result['message'],
                'time' => date('H:i', strtotime($result['created_at'])),
                'details' => ''
            );
        }

        return $activity;
    }

    private function get_activity_chart_labels() {
        $labels = array();
        for ($i = 6; $i >= 0; $i--) {
            $labels[] = date('d.m', strtotime("-{$i} days"));
        }
        return $labels;
    }

    private function get_activity_chart_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_data';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return array_fill(0, 7, 0);
        }

        $data = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE DATE(last_scraped) = %s",
                $date
            ));
            $data[] = intval($count);
        }

        return $data;
    }

    private function get_available_stores() {
        $stores = get_option('parfume_catalog_stores', array());
        $store_options = array();
        
        foreach ($stores as $store_id => $store_data) {
            if (!empty($store_data['active'])) {
                $store_options[$store_id] = $store_data['name'];
            }
        }
        
        return $store_options;
    }

    private function get_scraper_settings() {
        return get_option('parfume_scraper_settings', array(
            'enabled' => false,
            'interval' => 12,
            'batch_size' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; ParfumeCatalogBot/1.0)',
            'timeout' => 30,
            'max_retries' => 3,
            'respect_robots' => true,
            'log_level' => 'info',
            'log_retention' => 30
        ));
    }

    private function analyze_page_content($html) {
        // Simple analysis of page content
        $analysis = array(
            'price_elements' => array(),
            'availability_elements' => array(),
            'variant_elements' => array()
        );

        // Look for common price patterns
        $price_patterns = array(
            '/class="[^"]*price[^"]*"/',
            '/class="[^"]*cost[^"]*"/',
            '/class="[^"]*amount[^"]*"/'
        );

        foreach ($price_patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                $analysis['price_elements'] = array_merge($analysis['price_elements'], $matches[0]);
            }
        }

        return $analysis;
    }

    private function get_scraper_logs($page = 1, $level = '', $search = '', $date = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_logs';
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return array('logs' => array(), 'total' => 0);
        }

        $where_conditions = array();
        $params = array();

        if (!empty($level)) {
            $where_conditions[] = 'level = %s';
            $params[] = $level;
        }

        if (!empty($search)) {
            $where_conditions[] = 'message LIKE %s';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($date)) {
            $where_conditions[] = 'DATE(created_at) = %s';
            $params[] = $date;
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} {$where_clause}",
            $params
        ));

        $params[] = $per_page;
        $params[] = $offset;

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $params
        ), ARRAY_A);

        return array(
            'logs' => $logs,
            'total' => intval($total),
            'pages' => ceil($total / $per_page)
        );
    }

    private function clear_scraper_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_logs';
        $wpdb->query("TRUNCATE TABLE {$table_name}");
    }

    private function reset_failed_scrapes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_data';
        
        $count = $wpdb->query(
            "UPDATE {$table_name} SET status = 'pending', retry_count = 0 WHERE status = 'error'"
        );

        return intval($count);
    }

    private function execute_batch_action($action, $items) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_data';
        $placeholders = implode(',', array_fill(0, count($items), '%d'));

        switch ($action) {
            case 'scrape':
                $count = $wpdb->query($wpdb->prepare(
                    "UPDATE {$table_name} SET status = 'pending', next_scrape = NOW() WHERE id IN ({$placeholders})",
                    $items
                ));
                return sprintf(__('Планирани %d елемента за скрейпване.', 'parfume-catalog'), $count);

            case 'reset':
                $count = $wpdb->query($wpdb->prepare(
                    "UPDATE {$table_name} SET status = 'pending', retry_count = 0 WHERE id IN ({$placeholders})",
                    $items
                ));
                return sprintf(__('Рестартирани %d елемента.', 'parfume-catalog'), $count);

            case 'delete':
                $count = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE id IN ({$placeholders})",
                    $items
                ));
                return sprintf(__('Изтрити %d елемента.', 'parfume-catalog'), $count);

            default:
                return __('Неизвестно действие.', 'parfume-catalog');
        }
    }

    private function get_inline_styles() {
        return '
        .scraper-dashboard { }
        
        .stats-section { margin-bottom: 30px; }
        .stats-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
        }
        .stat-card { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card.success { border-left: 4px solid #4CAF50; }
        .stat-card.error { border-left: 4px solid #f44336; }
        .stat-card.pending { border-left: 4px solid #FF9800; }
        .stat-card.info { border-left: 4px solid #2196F3; }
        
        .stat-icon { font-size: 2em; }
        .stat-number { font-size: 2.5em; font-weight: bold; line-height: 1; }
        .stat-label { color: #666; font-size: 0.9em; }
        
        .charts-section { 
            display: grid; 
            grid-template-columns: 1fr 2fr; 
            gap: 30px; 
            margin-bottom: 30px; 
        }
        .chart-container { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
        }
        .chart-container h3 { margin-top: 0; }
        
        .quick-actions-section { margin-bottom: 30px; }
        .action-buttons { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
            margin-top: 15px; 
        }
        
        .recent-activity-section { }
        .activity-list { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            max-height: 400px; 
            overflow-y: auto; 
        }
        .activity-item { 
            padding: 15px; 
            border-bottom: 1px solid #f0f0f0; 
            display: flex; 
            align-items: flex-start; 
            gap: 15px; 
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item.success:before { content: "✅"; }
        .activity-item.error:before { content: "❌"; }
        .activity-item.info:before { content: "ℹ️"; }
        .activity-item.warning:before { content: "⚠️"; }
        
        .activity-time { 
            font-size: 0.8em; 
            color: #999; 
            white-space: nowrap; 
            width: 50px; 
        }
        .activity-message { font-weight: 500; }
        .activity-details { font-size: 0.9em; color: #666; }
        
        .no-activity { 
            padding: 40px; 
            text-align: center; 
            color: #999; 
            font-style: italic; 
        }
        
        .nav-tab-wrapper { margin-bottom: 20px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .monitor-controls,
        .logs-controls { 
            background: white; 
            padding: 20px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            margin-bottom: 20px; 
        }
        .controls-row { 
            display: flex; 
            gap: 15px; 
            align-items: center; 
            margin-top: 15px; 
            flex-wrap: wrap; 
        }
        
        .monitor-table-container,
        .logs-container { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            overflow: hidden; 
        }
        
        .test-sections { }
        .test-section { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
        }
        .test-section h3 { margin-top: 0; }
        
        .test-results { 
            margin-top: 20px; 
            padding: 15px; 
            background: #f9f9f9; 
            border-radius: 4px; 
        }
        
        @media (max-width: 768px) {
            .stats-cards { grid-template-columns: 1fr; }
            .charts-section { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
            .controls-row { flex-direction: column; align-items: stretch; }
        }
        ';
    }

    /**
     * Static helper функции
     */
    public static function get_scraper_status() {
        $settings = get_option('parfume_scraper_settings', array());
        return !empty($settings['enabled']);
    }

    public static function get_next_scrape_time() {
        return wp_next_scheduled('parfume_catalog_scraper_cron');
    }
}