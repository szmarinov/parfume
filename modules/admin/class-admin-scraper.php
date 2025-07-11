<?php
/**
 * Parfume Catalog Admin Scraper
 * 
 * Админ интерфейс за управление на scraper функционалността
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
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_pc_test_url', array($this, 'ajax_test_url'));
        add_action('wp_ajax_pc_manual_run', array($this, 'ajax_manual_run'));
        add_action('wp_ajax_pc_get_scraper_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_pc_get_scraper_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_pc_clear_scraper_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_pc_reset_failed_scraper', array($this, 'ajax_reset_failed'));
        add_action('wp_ajax_pc_batch_scraper_action', array($this, 'ajax_batch_action'));
        add_action('wp_ajax_pc_save_scraper_settings', array($this, 'ajax_save_settings'));
    }
    
    /**
     * Добавяне на админ страница
     */
    public function add_admin_menu() {
        add_submenu_page(
            'parfume-catalog',
            __('Scraper Monitor', 'parfume-catalog'),
            __('Scraper', 'parfume-catalog'),
            'manage_options',
            'parfume-scraper',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts и styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'parfume-scraper') === false) {
            return;
        }
        
        // Chart.js за статистики
        wp_enqueue_script(
            'chartjs',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Admin scraper JS
        wp_enqueue_script(
            'parfume-admin-scraper',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin-scraper.js',
            array('jquery', 'chartjs'),
            PARFUME_CATALOG_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('parfume-admin-scraper', 'parfumeScraperAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_nonce'),
            'strings' => array(
                'testing_url' => __('Тестване на URL...', 'parfume-catalog'),
                'url_accessible' => __('URL-ът е достъпен!', 'parfume-catalog'),
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
                'settings_saved' => __('Настройките са запазени успешно!', 'parfume-catalog')
            )
        ));
        
        // Admin CSS
        wp_enqueue_style(
            'parfume-admin-scraper',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin-scraper.css',
            array(),
            PARFUME_CATALOG_VERSION
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Проверка за permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате достъп до тази страница.', 'parfume-catalog'));
        }
        
        // Запазване на настройки
        if (isset($_POST['save_scraper_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'parfume_scraper_settings')) {
            $this->save_scraper_settings();
        }
        
        // Получаване на текущи настройки
        $settings = get_option('parfume_catalog_scraper_settings', array());
        
        ?>
        <div class="wrap parfume-scraper-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#dashboard" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'parfume-catalog'); ?></a>
                <a href="#monitor" class="nav-tab"><?php _e('Monitor', 'parfume-catalog'); ?></a>
                <a href="#logs" class="nav-tab"><?php _e('Логове', 'parfume-catalog'); ?></a>
                <a href="#test-tool" class="nav-tab"><?php _e('Test Tool', 'parfume-catalog'); ?></a>
                <a href="#settings" class="nav-tab"><?php _e('Настройки', 'parfume-catalog'); ?></a>
            </div>
            
            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <?php $this->render_dashboard_tab(); ?>
            </div>
            
            <!-- Monitor Tab -->
            <div id="monitor" class="tab-content">
                <?php $this->render_monitor_tab(); ?>
            </div>
            
            <!-- Logs Tab -->
            <div id="logs" class="tab-content">
                <?php $this->render_logs_tab(); ?>
            </div>
            
            <!-- Test Tool Tab -->
            <div id="test-tool" class="tab-content">
                <?php $this->render_test_tool_tab(); ?>
            </div>
            
            <!-- Settings Tab -->
            <div id="settings" class="tab-content">
                <?php $this->render_settings_tab($settings); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Dashboard tab
     */
    private function render_dashboard_tab() {
        $stats = $this->get_scraper_stats();
        $recent_activity = $this->get_recent_activity();
        ?>
        <div class="scraper-dashboard">
            <div class="stats-cards">
                <div class="stat-card success">
                    <div class="stat-number"><?php echo esc_html($stats['successful']); ?></div>
                    <div class="stat-label"><?php _e('Успешни', 'parfume-catalog'); ?></div>
                </div>
                <div class="stat-card error">
                    <div class="stat-number"><?php echo esc_html($stats['failed']); ?></div>
                    <div class="stat-label"><?php _e('Неуспешни', 'parfume-catalog'); ?></div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo esc_html($stats['pending']); ?></div>
                    <div class="stat-label"><?php _e('Чакащи', 'parfume-catalog'); ?></div>
                </div>
                <div class="stat-card total">
                    <div class="stat-number"><?php echo esc_html($stats['total']); ?></div>
                    <div class="stat-label"><?php _e('Общо', 'parfume-catalog'); ?></div>
                </div>
            </div>
            
            <div class="charts-row">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
            
            <div class="quick-actions">
                <h3><?php _e('Бързи действия', 'parfume-catalog'); ?></h3>
                <button type="button" class="button button-primary" id="run-full-scrape">
                    <?php _e('Пълно скрейпване', 'parfume-catalog'); ?>
                </button>
                <button type="button" class="button" id="run-failed-scrape">
                    <?php _e('Скрейпни само неуспешни', 'parfume-catalog'); ?>
                </button>
                <button type="button" class="button" id="test-all-urls">
                    <?php _e('Тествай всички URLs', 'parfume-catalog'); ?>
                </button>
            </div>
            
            <div class="recent-activity">
                <h3><?php _e('Последна активност', 'parfume-catalog'); ?></h3>
                <div id="recent-activity-list">
                    <?php if (!empty($recent_activity)): ?>
                        <ul>
                            <?php foreach ($recent_activity as $activity): ?>
                                <li class="activity-item <?php echo esc_attr($activity['status']); ?>">
                                    <span class="activity-time"><?php echo esc_html($activity['time']); ?></span>
                                    <span class="activity-message"><?php echo esc_html($activity['message']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('Няма последна активност.', 'parfume-catalog'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Pie chart за статуси
            var statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['<?php _e('Успешни', 'parfume-catalog'); ?>', '<?php _e('Неуспешни', 'parfume-catalog'); ?>', '<?php _e('Чакащи', 'parfume-catalog'); ?>'],
                    datasets: [{
                        data: [<?php echo $stats['successful']; ?>, <?php echo $stats['failed']; ?>, <?php echo $stats['pending']; ?>],
                        backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: '<?php _e('Разпределение на статуси', 'parfume-catalog'); ?>'
                        }
                    }
                }
            });
            
            // Line chart за активност по дни
            var activityCtx = document.getElementById('activityChart').getContext('2d');
            new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($this->get_activity_labels()); ?>,
                    datasets: [{
                        label: '<?php _e('Scraper активност', 'parfume-catalog'); ?>',
                        data: <?php echo json_encode($this->get_activity_data()); ?>,
                        borderColor: '#007cba',
                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: '<?php _e('Активност за последните 7 дни', 'parfume-catalog'); ?>'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render Monitor tab
     */
    private function render_monitor_tab() {
        ?>
        <div class="scraper-monitor">
            <div class="monitor-controls">
                <div class="filters">
                    <select id="status-filter">
                        <option value=""><?php _e('Всички статуси', 'parfume-catalog'); ?></option>
                        <option value="success"><?php _e('Успешни', 'parfume-catalog'); ?></option>
                        <option value="error"><?php _e('Грешки', 'parfume-catalog'); ?></option>
                        <option value="pending"><?php _e('Чакащи', 'parfume-catalog'); ?></option>
                    </select>
                    
                    <select id="store-filter">
                        <option value=""><?php _e('Всички магазини', 'parfume-catalog'); ?></option>
                        <?php
                        $stores = get_option('parfume_catalog_stores', array());
                        foreach ($stores as $store_id => $store) {
                            echo '<option value="' . esc_attr($store_id) . '">' . esc_html($store['name']) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <input type="text" id="url-search" placeholder="<?php _e('Търси URL...', 'parfume-catalog'); ?>">
                    
                    <button type="button" class="button" id="apply-filters">
                        <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <div class="bulk-actions">
                    <select id="bulk-action">
                        <option value=""><?php _e('Масови действия', 'parfume-catalog'); ?></option>
                        <option value="scrape"><?php _e('Скрейпни избраните', 'parfume-catalog'); ?></option>
                        <option value="reset"><?php _e('Ресетни статус', 'parfume-catalog'); ?></option>
                        <option value="delete"><?php _e('Изтрий избраните', 'parfume-catalog'); ?></option>
                    </select>
                    <button type="button" class="button" id="apply-bulk-action">
                        <?php _e('Приложи', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <div class="refresh-controls">
                    <label>
                        <input type="checkbox" id="auto-refresh" checked>
                        <?php _e('Автоматично обновяване', 'parfume-catalog'); ?>
                    </label>
                    <button type="button" class="button" id="manual-refresh">
                        <?php _e('Обнови сега', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div id="monitor-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column check-column">
                                <input type="checkbox" id="select-all">
                            </td>
                            <th><?php _e('Парфюм', 'parfume-catalog'); ?></th>
                            <th><?php _e('Магазин', 'parfume-catalog'); ?></th>
                            <th><?php _e('URL', 'parfume-catalog'); ?></th>
                            <th><?php _e('Статус', 'parfume-catalog'); ?></th>
                            <th><?php _e('Последно скрейпване', 'parfume-catalog'); ?></th>
                            <th><?php _e('Следващо скрейпване', 'parfume-catalog'); ?></th>
                            <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="monitor-table-body">
                        <!-- Данни се зареждат с AJAX -->
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <div id="pagination-info"></div>
                    </div>
                    <div class="alignright">
                        <div class="pagination-links" id="pagination-links"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Logs tab
     */
    private function render_logs_tab() {
        ?>
        <div class="scraper-logs">
            <div class="logs-controls">
                <div class="filters">
                    <select id="log-level-filter">
                        <option value=""><?php _e('Всички нива', 'parfume-catalog'); ?></option>
                        <option value="error"><?php _e('Грешки', 'parfume-catalog'); ?></option>
                        <option value="warning"><?php _e('Предупреждения', 'parfume-catalog'); ?></option>
                        <option value="info"><?php _e('Информация', 'parfume-catalog'); ?></option>
                        <option value="debug"><?php _e('Debug', 'parfume-catalog'); ?></option>
                    </select>
                    
                    <input type="date" id="log-date-filter" value="<?php echo date('Y-m-d'); ?>">
                    
                    <input type="text" id="log-search" placeholder="<?php _e('Търси в логовете...', 'parfume-catalog'); ?>">
                    
                    <button type="button" class="button" id="apply-log-filters">
                        <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <div class="log-actions">
                    <button type="button" class="button" id="clear-logs" data-confirm="<?php _e('Сигурни ли сте, че искате да изчистите всички логове?', 'parfume-catalog'); ?>">
                        <?php _e('Изчисти логове', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button" id="export-logs">
                        <?php _e('Експорт логове', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div id="logs-container">
                <div class="logs-list" id="logs-list">
                    <!-- Логове се зареждат с AJAX -->
                </div>
                
                <div class="logs-pagination">
                    <button type="button" class="button" id="load-more-logs">
                        <?php _e('Зареди още логове', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Test Tool tab
     */
    private function render_test_tool_tab() {
        $stores = get_option('parfume_catalog_stores', array());
        ?>
        <div class="scraper-test-tool">
            <div class="test-section">
                <h3><?php _e('Тест на URL', 'parfume-catalog'); ?></h3>
                <p class="description">
                    <?php _e('Тествайте достъпността на URL и анализирайте съдържанието за скрейпване.', 'parfume-catalog'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="test-url"><?php _e('URL за тестване', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="test-url" class="regular-text" placeholder="https://example.com/product">
                            <button type="button" class="button" id="test-url-btn">
                                <?php _e('Тествай URL', 'parfume-catalog'); ?>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="test-store"><?php _e('Магазин (опционално)', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <select id="test-store">
                                <option value=""><?php _e('Избери магазин', 'parfume-catalog'); ?></option>
                                <?php foreach ($stores as $store_id => $store): ?>
                                    <option value="<?php echo esc_attr($store_id); ?>">
                                        <?php echo esc_html($store['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Ако изберете магазин, ще се използва неговата схема за анализ.', 'parfume-catalog'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div id="test-results" style="display: none;">
                    <h4><?php _e('Резултати от теста', 'parfume-catalog'); ?></h4>
                    <div id="test-results-content"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Settings tab
     */
    private function render_settings_tab($settings) {
        ?>
        <div class="scraper-settings">
            <form method="post" action="">
                <?php wp_nonce_field('parfume_scraper_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable-scraper"><?php _e('Активен scraper', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <label for="enable-scraper">
                                <input type="checkbox" id="enable-scraper" name="parfume_scraper_settings[enabled]" value="1" 
                                       <?php checked(!empty($settings['enabled']), true); ?>>
                                <?php _e('Включи автоматично скрейпване', 'parfume-catalog'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="scrape-interval"><?php _e('Интервал на скрейпване (часове)', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="scrape-interval" name="parfume_scraper_settings[interval]" 
                                   value="<?php echo esc_attr($settings['interval'] ?? 12); ?>" min="1" max="168" class="small-text">
                            <p class="description"><?php _e('Колко често да се извършва автоматично скрейпване (в часове)', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="batch-size"><?php _e('Размер на партида', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="batch-size" name="parfume_scraper_settings[batch_size]" 
                                   value="<?php echo esc_attr($settings['batch_size'] ?? 10); ?>" min="1" max="100" class="small-text">
                            <p class="description"><?php _e('Брой URLs за обработване наведнъж', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="user-agent"><?php _e('User Agent', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="user-agent" name="parfume_scraper_settings[user_agent]" 
                                   value="<?php echo esc_attr($settings['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('User Agent string за HTTP заявките', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="timeout"><?php _e('Timeout (секунди)', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="timeout" name="parfume_scraper_settings[timeout]" 
                                   value="<?php echo esc_attr($settings['timeout'] ?? 30); ?>" min="5" max="120" class="small-text">
                            <p class="description"><?php _e('Максимално време за чакане на отговор', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max-retries"><?php _e('Максимален брой опити', 'parfume-catalog'); ?></label>
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
                            <p class="description"><?php _e('След колко дни да се изтриват старите логове', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Запази настройки', 'parfume-catalog'), 'primary', 'save_scraper_settings'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Запазване на scraper настройки
     */
    private function save_scraper_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = array();
        
        if (isset($_POST['parfume_scraper_settings'])) {
            $input = $_POST['parfume_scraper_settings'];
            
            $settings['enabled'] = !empty($input['enabled']);
            $settings['interval'] = max(1, min(168, intval($input['interval'] ?? 12)));
            $settings['batch_size'] = max(1, min(100, intval($input['batch_size'] ?? 10)));
            $settings['user_agent'] = sanitize_text_field($input['user_agent'] ?? '');
            $settings['timeout'] = max(5, min(120, intval($input['timeout'] ?? 30)));
            $settings['max_retries'] = max(1, min(10, intval($input['max_retries'] ?? 3)));
            $settings['respect_robots'] = !empty($input['respect_robots']);
            $settings['log_level'] = sanitize_text_field($input['log_level'] ?? 'info');
            $settings['log_retention'] = max(1, min(365, intval($input['log_retention'] ?? 30)));
        }
        
        update_option('parfume_catalog_scraper_settings', $settings);
        
        // Обновяване на cron
        if ($settings['enabled']) {
            $this->schedule_scraper_cron($settings['interval']);
        } else {
            wp_clear_scheduled_hook('parfume_scraper_cron');
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Настройките са запазени успешно!', 'parfume-catalog') . '</p></div>';
        });
    }
    
    /**
     * Планиране на cron за scraper
     */
    private function schedule_scraper_cron($interval_hours) {
        wp_clear_scheduled_hook('parfume_scraper_cron');
        
        if (!wp_next_scheduled('parfume_scraper_cron')) {
            wp_schedule_event(time(), 'hourly', 'parfume_scraper_cron');
        }
    }
    
    /**
     * AJAX: Тестване на URL
     */
    public function ajax_test_url() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $url = sanitize_url($_POST['url'] ?? '');
        $store_id = sanitize_text_field($_POST['store_id'] ?? '');
        
        if (empty($url)) {
            wp_send_json_error(__('Моля въведете валиден URL.', 'parfume-catalog'));
        }
        
        try {
            // Проверка на достъпността на URL
            $response = wp_remote_head($url, array('timeout' => 10));
            
            if (is_wp_error($response)) {
                wp_send_json_error(__('URL-ът е недостъпен: ', 'parfume-catalog') . $response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code !== 200) {
                wp_send_json_error(__('URL-ът върна грешка: ', 'parfume-catalog') . $status_code);
            }
            
            $result = array(
                'status' => 'success',
                'message' => __('URL-ът е достъпен!', 'parfume-catalog'),
                'status_code' => $status_code
            );
            
            // Ако е избран магазин, тествай schema
            if (!empty($store_id) && class_exists('Parfume_Catalog_Scraper')) {
                $scraper = new Parfume_Catalog_Scraper();
                $schema_result = $scraper->test_store_schema($store_id, $url);
                $result['schema_test'] = $schema_result;
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Грешка при тестването: ', 'parfume-catalog') . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Ръчно стартиране на scraper
     */
    public function ajax_manual_run() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $type = sanitize_text_field($_POST['type'] ?? 'full');
        
        try {
            if (class_exists('Parfume_Catalog_Scraper')) {
                $scraper = new Parfume_Catalog_Scraper();
                
                if ($type === 'failed') {
                    $result = $scraper->run_failed_scraper();
                } else {
                    $result = $scraper->run_manual_scraper();
                }
                
                wp_send_json_success($result);
            } else {
                wp_send_json_error(__('Scraper класът не е достъпен.', 'parfume-catalog'));
            }
        } catch (Exception $e) {
            wp_send_json_error(__('Грешка при стартиране: ', 'parfume-catalog') . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Получаване на статистики
     */
    public function ajax_get_stats() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $stats = $this->get_scraper_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Получаване на логове
     */
    public function ajax_get_logs() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $level = sanitize_text_field($_POST['level'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = max(1, intval($_POST['page'] ?? 1));
        
        $logs = $this->get_scraper_logs($level, $date, $search, $page);
        wp_send_json_success($logs);
    }
    
    /**
     * AJAX: Изчистване на логове
     */
    public function ajax_clear_logs() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        global $wpdb;
        $logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        
        $result = $wpdb->query("TRUNCATE TABLE $logs_table");
        
        if ($result !== false) {
            wp_send_json_success(__('Логовете са изчистени успешно!', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при изчистване на логовете.', 'parfume-catalog'));
        }
    }
    
    /**
     * AJAX: Рестартиране на неуспешни задачи
     */
    public function ajax_reset_failed() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $result = $wpdb->update(
            $scraper_table,
            array(
                'status' => 'pending',
                'retry_count' => 0,
                'error_message' => null,
                'next_scrape' => current_time('mysql')
            ),
            array('status' => 'error'),
            array('%s', '%d', '%s', '%s'),
            array('%s')
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Неуспешните задачи са рестартирани!', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при рестартиране на задачите.', 'parfume-catalog'));
        }
    }
    
    /**
     * AJAX: Масови действия
     */
    public function ajax_batch_action() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $ids = array_map('intval', $_POST['ids'] ?? array());
        
        if (empty($action) || empty($ids)) {
            wp_send_json_error(__('Невалидни параметри.', 'parfume-catalog'));
        }
        
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        
        switch ($action) {
            case 'scrape':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $scraper_table SET status = 'pending', next_scrape = %s WHERE id IN ($ids_placeholder)",
                    current_time('mysql'),
                    ...$ids
                ));
                break;
                
            case 'reset':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $scraper_table SET status = 'pending', retry_count = 0, error_message = NULL WHERE id IN ($ids_placeholder)",
                    ...$ids
                ));
                break;
                
            case 'delete':
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $scraper_table WHERE id IN ($ids_placeholder)",
                    ...$ids
                ));
                break;
                
            default:
                wp_send_json_error(__('Невалидно действие.', 'parfume-catalog'));
        }
        
        if ($result !== false) {
            wp_send_json_success(sprintf(__('Обработени %d записа.', 'parfume-catalog'), $result));
        } else {
            wp_send_json_error(__('Грешка при изпълнение на действието.', 'parfume-catalog'));
        }
    }
    
    /**
     * AJAX: Запазване на настройки
     */
    public function ajax_save_settings() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате достъп до тази функционалност.', 'parfume-catalog'));
        }
        
        $settings = $_POST['settings'] ?? array();
        
        // Sanitize settings
        $clean_settings = array();
        $clean_settings['enabled'] = !empty($settings['enabled']);
        $clean_settings['interval'] = max(1, min(168, intval($settings['interval'] ?? 12)));
        $clean_settings['batch_size'] = max(1, min(100, intval($settings['batch_size'] ?? 10)));
        $clean_settings['user_agent'] = sanitize_text_field($settings['user_agent'] ?? '');
        $clean_settings['timeout'] = max(5, min(120, intval($settings['timeout'] ?? 30)));
        $clean_settings['max_retries'] = max(1, min(10, intval($settings['max_retries'] ?? 3)));
        $clean_settings['respect_robots'] = !empty($settings['respect_robots']);
        $clean_settings['log_level'] = sanitize_text_field($settings['log_level'] ?? 'info');
        $clean_settings['log_retention'] = max(1, min(365, intval($settings['log_retention'] ?? 30)));
        
        update_option('parfume_catalog_scraper_settings', $clean_settings);
        
        // Обновяване на cron
        if ($clean_settings['enabled']) {
            $this->schedule_scraper_cron($clean_settings['interval']);
        } else {
            wp_clear_scheduled_hook('parfume_scraper_cron');
        }
        
        wp_send_json_success(__('Настройките са запазени успешно!', 'parfume-catalog'));
    }
    
    /**
     * Получаване на scraper статистики
     */
    private function get_scraper_stats() {
        global $wpdb;
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM $scraper_table
        ", ARRAY_A);
        
        return $stats ?: array('total' => 0, 'successful' => 0, 'failed' => 0, 'pending' => 0);
    }
    
    /**
     * Получаване на последна активност
     */
    private function get_recent_activity() {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        
        $activities = $wpdb->get_results($wpdb->prepare("
            SELECT message, level as status, created_at as time
            FROM $logs_table 
            ORDER BY created_at DESC 
            LIMIT %d
        ", 10), ARRAY_A);
        
        return $activities ?: array();
    }
    
    /**
     * Получаване на labels за активност chart
     */
    private function get_activity_labels() {
        $labels = array();
        for ($i = 6; $i >= 0; $i--) {
            $labels[] = date('d.m', strtotime("-$i days"));
        }
        return $labels;
    }
    
    /**
     * Получаване на данни за активност chart
     */
    private function get_activity_data() {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        
        $data = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM $logs_table 
                WHERE DATE(created_at) = %s
            ", $date));
            $data[] = intval($count);
        }
        
        return $data;
    }
    
    /**
     * Получаване на scraper логове
     */
    private function get_scraper_logs($level = '', $date = '', $search = '', $page = 1) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        if (!empty($level)) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $level;
        }
        
        if (!empty($date)) {
            $where_conditions[] = 'DATE(created_at) = %s';
            $where_values[] = $date;
        }
        
        if (!empty($search)) {
            $where_conditions[] = 'message LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM $logs_table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        $logs = $wpdb->get_results($wpdb->prepare($query, ...$where_values), ARRAY_A);
        
        return $logs ?: array();
    }
    
    /**
     * Static method за external access
     */
    public static function get_scraper_status() {
        $settings = get_option('parfume_catalog_scraper_settings', array());
        return !empty($settings['enabled']);
    }
}

// Инициализиране
new Parfume_Catalog_Admin_Scraper();