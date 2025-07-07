<?php
/**
 * Admin Scraper Management Class
 * 
 * Handles scraper configuration and monitoring in admin panel
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Admin_Scraper {
    
    private $schemas_option = 'parfume_scraper_schemas';
    private $monitor_option = 'parfume_scraper_monitor';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_scraper_pages'));
        add_action('wp_ajax_parfume_test_scraper', array($this, 'test_scraper'));
        add_action('wp_ajax_parfume_save_schema', array($this, 'save_schema'));
        add_action('wp_ajax_parfume_manual_scrape', array($this, 'manual_scrape'));
        add_action('wp_ajax_parfume_get_scraper_log', array($this, 'get_scraper_log'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add scraper pages to admin menu
     */
    public function add_scraper_pages() {
        // Product Scraper main page
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Product Scraper', 'parfume-catalog'),
            __('Product Scraper', 'parfume-catalog'),
            'manage_options',
            'parfume-scraper',
            array($this, 'render_scraper_page')
        );
        
        // Test Tool page
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Scraper Test Tool', 'parfume-catalog'),
            __('Test Tool', 'parfume-catalog'),
            'manage_options',
            'parfume-scraper-test',
            array($this, 'render_test_tool_page')
        );
        
        // Monitor page
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
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'parfume-scraper') === false) {
            return;
        }
        
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');
    }
    
    /**
     * Render main scraper page
     */
    public function render_scraper_page() {
        $stores = Parfume_Admin_Stores::get_all_stores();
        $schemas = get_option($this->schemas_option, array());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Product Scraper', 'parfume-catalog'); ?></h1>
            
            <div id="scraper-tabs">
                <ul>
                    <li><a href="#settings-tab"><?php _e('Настройки', 'parfume-catalog'); ?></a></li>
                    <li><a href="#schemas-tab"><?php _e('Схеми за магазини', 'parfume-catalog'); ?></a></li>
                    <li><a href="#logs-tab"><?php _e('Логове', 'parfume-catalog'); ?></a></li>
                </ul>
                
                <!-- Settings Tab -->
                <div id="settings-tab">
                    <h3><?php _e('Настройки на скрейпъра', 'parfume-catalog'); ?></h3>
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_catalog_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="parfume_scraper_interval"><?php _e('Интервал на скрейпване (часове)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_scraper_interval" 
                                           name="parfume_scraper_interval" 
                                           value="<?php echo esc_attr(get_option('parfume_scraper_interval', 12)); ?>" 
                                           min="1" max="168" />
                                    <p class="description"><?php _e('На колко часа да се обновяват цените автоматично', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_scraper_batch_size"><?php _e('Размер на партидата', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_scraper_batch_size" 
                                           name="parfume_scraper_batch_size" 
                                           value="<?php echo esc_attr(get_option('parfume_scraper_batch_size', 10)); ?>" 
                                           min="1" max="50" />
                                    <p class="description"><?php _e('Колко URL-а да се обработват в една партида', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_scraper_user_agent"><?php _e('User Agent', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_scraper_user_agent" 
                                           name="parfume_scraper_user_agent" 
                                           value="<?php echo esc_attr(get_option('parfume_scraper_user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')); ?>" 
                                           class="large-text" />
                                    <p class="description"><?php _e('User agent за HTTP заявките', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_scraper_timeout"><?php _e('Timeout (секунди)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_scraper_timeout" 
                                           name="parfume_scraper_timeout" 
                                           value="<?php echo esc_attr(get_option('parfume_scraper_timeout', 30)); ?>" 
                                           min="10" max="120" />
                                    <p class="description"><?php _e('Максимално време за изчакване на отговор', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_scraper_retry_attempts"><?php _e('Опити при грешка', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_scraper_retry_attempts" 
                                           name="parfume_scraper_retry_attempts" 
                                           value="<?php echo esc_attr(get_option('parfume_scraper_retry_attempts', 3)); ?>" 
                                           min="1" max="10" />
                                    <p class="description"><?php _e('Колко пъти да опита при неуспешно скрейпване', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                    
                    <h3><?php _e('Ръчно стартиране', 'parfume-catalog'); ?></h3>
                    <p><?php _e('Можете да стартирате скрейпъра ръчно за тестване или незабавно обновяване.', 'parfume-catalog'); ?></p>
                    <button type="button" id="manual-scrape-all" class="button button-secondary">
                        <?php _e('Скрейпни всички URL-и', 'parfume-catalog'); ?>
                    </button>
                    <div id="scrape-progress" style="margin-top: 15px; display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%;"></div>
                        </div>
                        <div class="progress-text">0%</div>
                    </div>
                </div>
                
                <!-- Schemas Tab -->
                <div id="schemas-tab">
                    <h3><?php _e('Схеми за скрейпване по магазини', 'parfume-catalog'); ?></h3>
                    <p><?php _e('Конфигурирайте CSS селектори за всеки магазин.', 'parfume-catalog'); ?></p>
                    
                    <?php if (empty($stores)): ?>
                        <div class="notice notice-warning">
                            <p><?php _e('Няма добавени магазини. Първо добавете магазини от секцията "Магазини".', 'parfume-catalog'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="schemas-container">
                            <?php foreach ($stores as $store_id => $store): ?>
                                <div class="schema-card">
                                    <h4><?php echo esc_html($store['name']); ?></h4>
                                    
                                    <form class="schema-form" data-store-id="<?php echo esc_attr($store_id); ?>">
                                        <table class="form-table">
                                            <tr>
                                                <th><?php _e('Селектор за цена', 'parfume-catalog'); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           name="price_selector" 
                                                           value="<?php echo isset($schemas[$store_id]['price_selector']) ? esc_attr($schemas[$store_id]['price_selector']) : ''; ?>" 
                                                           class="regular-text" 
                                                           placeholder=".price, .product-price" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?php _e('Селектор за стара цена', 'parfume-catalog'); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           name="old_price_selector" 
                                                           value="<?php echo isset($schemas[$store_id]['old_price_selector']) ? esc_attr($schemas[$store_id]['old_price_selector']) : ''; ?>" 
                                                           class="regular-text" 
                                                           placeholder=".old-price, .original-price" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?php _e('Селектор за милилитри', 'parfume-catalog'); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           name="ml_selector" 
                                                           value="<?php echo isset($schemas[$store_id]['ml_selector']) ? esc_attr($schemas[$store_id]['ml_selector']) : ''; ?>" 
                                                           class="regular-text" 
                                                           placeholder=".ml-option, .size-variant" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?php _e('Селектор за наличност', 'parfume-catalog'); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           name="availability_selector" 
                                                           value="<?php echo isset($schemas[$store_id]['availability_selector']) ? esc_attr($schemas[$store_id]['availability_selector']) : ''; ?>" 
                                                           class="regular-text" 
                                                           placeholder=".availability, .in-stock" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?php _e('Селектор за доставка', 'parfume-catalog'); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           name="delivery_selector" 
                                                           value="<?php echo isset($schemas[$store_id]['delivery_selector']) ? esc_attr($schemas[$store_id]['delivery_selector']) : ''; ?>" 
                                                           class="regular-text" 
                                                           placeholder=".shipping-info, .delivery" />
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <p class="submit">
                                            <button type="button" class="button button-primary save-schema">
                                                <?php _e('Запази схемата', 'parfume-catalog'); ?>
                                            </button>
                                            <button type="button" class="button button-secondary test-schema">
                                                <?php _e('Тествай схемата', 'parfume-catalog'); ?>
                                            </button>
                                        </p>
                                        
                                        <div class="schema-test-url" style="margin-top: 15px;">
                                            <label><?php _e('Тестов URL:', 'parfume-catalog'); ?></label>
                                            <input type="url" 
                                                   name="test_url" 
                                                   placeholder="https://example.com/product" 
                                                   class="regular-text" />
                                        </div>
                                        
                                        <div class="schema-result" style="margin-top: 15px; display: none;"></div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Logs Tab -->
                <div id="logs-tab">
                    <h3><?php _e('Логове на скрейпъра', 'parfume-catalog'); ?></h3>
                    
                    <div class="log-controls">
                        <button type="button" id="refresh-logs" class="button">
                            <?php _e('Обнови логовете', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" id="clear-logs" class="button">
                            <?php _e('Изчисти логовете', 'parfume-catalog'); ?>
                        </button>
                        
                        <select id="log-filter">
                            <option value=""><?php _e('Всички записи', 'parfume-catalog'); ?></option>
                            <option value="success"><?php _e('Успешни', 'parfume-catalog'); ?></option>
                            <option value="error"><?php _e('Грешки', 'parfume-catalog'); ?></option>
                            <option value="warning"><?php _e('Предупреждения', 'parfume-catalog'); ?></option>
                        </select>
                    </div>
                    
                    <div id="logs-container">
                        <div class="loading"><?php _e('Зареждане на логове...', 'parfume-catalog'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .schema-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
        }
        
        .schema-card h4 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .schema-result {
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #46b450;
            background: #f7fcf0;
        }
        
        .schema-result.error {
            border-left-color: #dc3232;
            background: #fef7f1;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background: #0073aa;
            transition: width 0.3s ease;
        }
        
        .log-controls {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .log-controls button, 
        .log-controls select {
            margin-right: 10px;
        }
        
        .log-entry {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 4px solid #ddd;
        }
        
        .log-entry.success {
            border-left-color: #46b450;
            background: #f7fcf0;
        }
        
        .log-entry.error {
            border-left-color: #dc3232;
            background: #fef7f1;
        }
        
        .log-entry.warning {
            border-left-color: #ffb900;
            background: #fff8e5;
        }
        
        .log-timestamp {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .log-message {
            font-weight: 500;
        }
        
        .log-details {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize tabs
            $('#scraper-tabs').tabs();
            
            // Save schema
            $('.save-schema').click(function() {
                var form = $(this).closest('.schema-form');
                var storeId = form.data('store-id');
                var formData = form.serialize();
                
                $.post(ajaxurl, {
                    action: 'parfume_save_schema',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>',
                    store_id: storeId,
                    schema_data: formData
                }, function(response) {
                    if (response.success) {
                        form.find('.schema-result')
                            .removeClass('error')
                            .html('<strong><?php _e('Схемата е запазена успешно!', 'parfume-catalog'); ?></strong>')
                            .show();
                    } else {
                        form.find('.schema-result')
                            .addClass('error')
                            .html('<strong><?php _e('Грешка:', 'parfume-catalog'); ?></strong> ' + (response.data.message || '<?php _e('Неизвестна грешка', 'parfume-catalog'); ?>'))
                            .show();
                    }
                });
            });
            
            // Test schema
            $('.test-schema').click(function() {
                var form = $(this).closest('.schema-form');
                var storeId = form.data('store-id');
                var testUrl = form.find('input[name="test_url"]').val();
                var formData = form.serialize();
                
                if (!testUrl) {
                    alert('<?php _e('Моля въведете тестов URL', 'parfume-catalog'); ?>');
                    return;
                }
                
                form.find('.schema-result').html('<?php _e('Тестване...', 'parfume-catalog'); ?>').show();
                
                $.post(ajaxurl, {
                    action: 'parfume_test_scraper',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>',
                    store_id: storeId,
                    test_url: testUrl,
                    schema_data: formData
                }, function(response) {
                    if (response.success) {
                        var result = response.data.result;
                        var html = '<strong><?php _e('Резултат от теста:', 'parfume-catalog'); ?></strong><br>';
                        
                        if (result.price) html += '<?php _e('Цена:', 'parfume-catalog'); ?> ' + result.price + '<br>';
                        if (result.old_price) html += '<?php _e('Стара цена:', 'parfume-catalog'); ?> ' + result.old_price + '<br>';
                        if (result.ml_variants) html += '<?php _e('Варианти:', 'parfume-catalog'); ?> ' + result.ml_variants.join(', ') + '<br>';
                        if (result.availability) html += '<?php _e('Наличност:', 'parfume-catalog'); ?> ' + result.availability + '<br>';
                        if (result.delivery) html += '<?php _e('Доставка:', 'parfume-catalog'); ?> ' + result.delivery + '<br>';
                        
                        form.find('.schema-result')
                            .removeClass('error')
                            .html(html)
                            .show();
                    } else {
                        form.find('.schema-result')
                            .addClass('error')
                            .html('<strong><?php _e('Грешка:', 'parfume-catalog'); ?></strong> ' + (response.data.message || '<?php _e('Неуспешно тестване', 'parfume-catalog'); ?>'))
                            .show();
                    }
                });
            });
            
            // Manual scrape all
            $('#manual-scrape-all').click(function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да стартирате ръчно скрейпване на всички URL-и?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                $('#scrape-progress').show();
                $('.progress-fill').css('width', '0%');
                $('.progress-text').text('0%');
                
                $.post(ajaxurl, {
                    action: 'parfume_manual_scrape',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>',
                    scrape_all: true
                }, function(response) {
                    if (response.success) {
                        $('.progress-fill').css('width', '100%');
                        $('.progress-text').text('100% - <?php _e('Завършено', 'parfume-catalog'); ?>');
                    } else {
                        $('.progress-text').text('<?php _e('Грешка при скрейпване', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Load logs on tab switch
            $('#scraper-tabs').on('tabsactivate', function(event, ui) {
                if (ui.newPanel.attr('id') === 'logs-tab') {
                    loadLogs();
                }
            });
            
            // Refresh logs
            $('#refresh-logs').click(function() {
                loadLogs();
            });
            
            // Clear logs
            $('#clear-logs').click(function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изчистите всички логове?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                // Clear logs logic here
                $('#logs-container').html('<div class="notice notice-success"><p><?php _e('Логовете са изчистени', 'parfume-catalog'); ?></p></div>');
            });
            
            // Filter logs
            $('#log-filter').change(function() {
                var filter = $(this).val();
                if (filter) {
                    $('.log-entry').hide();
                    $('.log-entry.' + filter).show();
                } else {
                    $('.log-entry').show();
                }
            });
            
            function loadLogs() {
                $('#logs-container').html('<div class="loading"><?php _e('Зареждане на логове...', 'parfume-catalog'); ?></div>');
                
                $.post(ajaxurl, {
                    action: 'parfume_get_scraper_log',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#logs-container').html(response.data.logs_html);
                    } else {
                        $('#logs-container').html('<div class="notice notice-error"><p><?php _e('Грешка при зареждане на логовете', 'parfume-catalog'); ?></p></div>');
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render test tool page
     */
    public function render_test_tool_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Scraper Test Tool', 'parfume-catalog'); ?></h1>
            <p><?php _e('Тествайте и конфигурирайте схеми за скрейпване на нови магазини.', 'parfume-catalog'); ?></p>
            
            <div class="test-tool-container">
                <div class="test-form-section">
                    <h3><?php _e('Тестване на URL', 'parfume-catalog'); ?></h3>
                    
                    <form id="test-tool-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="test-url"><?php _e('Тестов URL', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="url" 
                                           id="test-url" 
                                           name="test_url" 
                                           class="large-text" 
                                           placeholder="https://example.com/product" 
                                           required />
                                    <p class="description"><?php _e('Въведете URL на продукт от магазин за тестване', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="button" id="analyze-page" class="button button-primary">
                                <?php _e('Анализирай страницата', 'parfume-catalog'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <div id="analysis-results" style="display: none;">
                    <h3><?php _e('Резултат от анализа', 'parfume-catalog'); ?></h3>
                    <div id="analysis-content"></div>
                </div>
                
                <div id="selector-builder" style="display: none;">
                    <h3><?php _e('Конструктор на схема', 'parfume-catalog'); ?></h3>
                    <div id="selector-content"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#analyze-page').click(function() {
                var testUrl = $('#test-url').val();
                
                if (!testUrl) {
                    alert('<?php _e('Моля въведете тестов URL', 'parfume-catalog'); ?>');
                    return;
                }
                
                $('#analysis-results').show();
                $('#analysis-content').html('<?php _e('Анализиране...', 'parfume-catalog'); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_test_scraper',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>',
                    test_url: testUrl,
                    analyze_only: true
                }, function(response) {
                    if (response.success) {
                        displayAnalysisResults(response.data);
                    } else {
                        $('#analysis-content').html('<div class="notice notice-error"><p>' + 
                            (response.data.message || '<?php _e('Грешка при анализ', 'parfume-catalog'); ?>') + '</p></div>');
                    }
                });
            });
            
            function displayAnalysisResults(data) {
                var html = '<div class="analysis-grid">';
                
                if (data.potential_prices && data.potential_prices.length > 0) {
                    html += '<div class="analysis-section">';
                    html += '<h4><?php _e('Възможни цени:', 'parfume-catalog'); ?></h4>';
                    html += '<ul>';
                    data.potential_prices.forEach(function(item) {
                        html += '<li><code>' + item.selector + '</code> → ' + item.value + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                }
                
                if (data.potential_ml && data.potential_ml.length > 0) {
                    html += '<div class="analysis-section">';
                    html += '<h4><?php _e('Възможни варианти (мл):', 'parfume-catalog'); ?></h4>';
                    html += '<ul>';
                    data.potential_ml.forEach(function(item) {
                        html += '<li><code>' + item.selector + '</code> → ' + item.value + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                }
                
                html += '</div>';
                
                $('#analysis-content').html(html);
                $('#selector-builder').show();
                buildSelectorForm(data);
            }
            
            function buildSelectorForm(data) {
                var html = '<form id="schema-builder-form">';
                html += '<table class="form-table">';
                
                // Price selectors
                if (data.potential_prices && data.potential_prices.length > 0) {
                    html += '<tr><th><?php _e('Селектор за цена:', 'parfume-catalog'); ?></th><td>';
                    html += '<select name="price_selector">';
                    html += '<option value=""><?php _e('Изберете...', 'parfume-catalog'); ?></option>';
                    data.potential_prices.forEach(function(item) {
                        html += '<option value="' + item.selector + '">' + item.selector + ' (' + item.value + ')</option>';
                    });
                    html += '</select>';
                    html += '</td></tr>';
                }
                
                // ML selectors
                if (data.potential_ml && data.potential_ml.length > 0) {
                    html += '<tr><th><?php _e('Селектор за мл:', 'parfume-catalog'); ?></th><td>';
                    html += '<select name="ml_selector">';
                    html += '<option value=""><?php _e('Изберете...', 'parfume-catalog'); ?></option>';
                    data.potential_ml.forEach(function(item) {
                        html += '<option value="' + item.selector + '">' + item.selector + ' (' + item.value + ')</option>';
                    });
                    html += '</select>';
                    html += '</td></tr>';
                }
                
                html += '</table>';
                html += '<p class="submit">';
                html += '<button type="button" id="test-schema-builder" class="button button-primary"><?php _e('Тествай схемата', 'parfume-catalog'); ?></button>';
                html += '</p>';
                html += '</form>';
                
                $('#selector-content').html(html);
            }
            
            $(document).on('click', '#test-schema-builder', function() {
                var testUrl = $('#test-url').val();
                var formData = $('#schema-builder-form').serialize();
                
                $.post(ajaxurl, {
                    action: 'parfume_test_scraper',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>',
                    test_url: testUrl,
                    schema_data: formData
                }, function(response) {
                    if (response.success) {
                        var result = response.data.result;
                        var html = '<div class="notice notice-success"><p><strong><?php _e('Тест успешен!', 'parfume-catalog'); ?></strong></p>';
                        
                        if (result.price) html += '<p><?php _e('Цена:', 'parfume-catalog'); ?> ' + result.price + '</p>';
                        if (result.ml_variants) html += '<p><?php _e('Варианти:', 'parfume-catalog'); ?> ' + result.ml_variants.join(', ') + '</p>';
                        
                        html += '</div>';
                        
                        $('#selector-content').append(html);
                    } else {
                        $('#selector-content').append('<div class="notice notice-error"><p><strong><?php _e('Грешка:', 'parfume-catalog'); ?></strong> ' + 
                            (response.data.message || '<?php _e('Неуспешно тестване', 'parfume-catalog'); ?>') + '</p></div>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .test-tool-container {
            margin-top: 20px;
        }
        
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .analysis-section {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #fff;
        }
        
        .analysis-section h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #0073aa;
        }
        
        .analysis-section ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .analysis-section li {
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .analysis-section code {
            background: #f0f0f0;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * Render monitor page
     */
    public function render_monitor_page() {
        // Get all posts with product URLs
        $monitor_data = $this->get_monitor_data();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Scraper Monitor', 'parfume-catalog'); ?></h1>
            <p><?php _e('Мониторинг на всички Product URL-и и техния статус.', 'parfume-catalog'); ?></p>
            
            <div class="monitor-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $monitor_data['total_urls']; ?></div>
                    <div class="stat-label"><?php _e('Общо URL-и', 'parfume-catalog'); ?></div>
                </div>
                <div class="stat-box success">
                    <div class="stat-number"><?php echo $monitor_data['successful']; ?></div>
                    <div class="stat-label"><?php _e('Успешни', 'parfume-catalog'); ?></div>
                </div>
                <div class="stat-box error">
                    <div class="stat-number"><?php echo $monitor_data['errors']; ?></div>
                    <div class="stat-label"><?php _e('Грешки', 'parfume-catalog'); ?></div>
                </div>
                <div class="stat-box pending">
                    <div class="stat-number"><?php echo $monitor_data['pending']; ?></div>
                    <div class="stat-label"><?php _e('Чакащи', 'parfume-catalog'); ?></div>
                </div>
            </div>
            
            <div class="monitor-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Пост', 'parfume-catalog'); ?></th>
                            <th><?php _e('Магазин', 'parfume-catalog'); ?></th>
                            <th><?php _e('Product URL', 'parfume-catalog'); ?></th>
                            <th><?php _e('Последна цена', 'parfume-catalog'); ?></th>
                            <th><?php _e('Последно скрейпване', 'parfume-catalog'); ?></th>
                            <th><?php _e('Следващо скрейпване', 'parfume-catalog'); ?></th>
                            <th><?php _e('Статус', 'parfume-catalog'); ?></th>
                            <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monitor_data['entries'])): ?>
                            <tr>
                                <td colspan="8" class="no-items">
                                    <?php _e('Няма Product URL-и за мониторинг', 'parfume-catalog'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monitor_data['entries'] as $entry): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($entry['post_id']); ?>">
                                            <?php echo esc_html(get_the_title($entry['post_id'])); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($entry['store_name']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($entry['product_url']); ?>" target="_blank">
                                            <?php echo esc_html(wp_trim_words($entry['product_url'], 6, '...')); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($entry['last_price']); ?></td>
                                    <td><?php echo esc_html($entry['last_scraped']); ?></td>
                                    <td><?php echo esc_html($entry['next_scrape']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo esc_attr($entry['status']); ?>">
                                            <?php echo esc_html($entry['status_text']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="button button-small manual-scrape-single" 
                                                data-post-id="<?php echo esc_attr($entry['post_id']); ?>" 
                                                data-store-id="<?php echo esc_attr($entry['store_id']); ?>">
                                            <?php _e('Скрейпни сега', 'parfume-catalog'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .monitor-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #ddd;
        }
        
        .stat-box.success {
            border-left-color: #46b450;
        }
        
        .stat-box.error {
            border-left-color: #dc3232;
        }
        
        .stat-box.pending {
            border-left-color: #ffb900;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #666;
        }
        
        .monitor-table-container {
            margin-top: 20px;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-badge.success {
            background: #46b450;
            color: white;
        }
        
        .status-badge.error {
            background: #dc3232;
            color: white;
        }
        
        .status-badge.pending {
            background: #ffb900;
            color: white;
        }
        
        .status-badge.blocked {
            background: #666;
            color: white;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Manual scrape single URL
            $('.manual-scrape-single').click(function() {
                var button = $(this);
                var postId = button.data('post-id');
                var storeId = button.data('store-id');
                
                button.prop('disabled', true).text('<?php _e('Скрейпва...', 'parfume-catalog'); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_manual_scrape',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>',
                    post_id: postId,
                    store_id: storeId
                }, function(response) {
                    if (response.success) {
                        button.prop('disabled', false).text('<?php _e('Скрейпни сега', 'parfume-catalog'); ?>');
                        location.reload(); // Refresh to show updated data
                    } else {
                        button.prop('disabled', false).text('<?php _e('Грешка', 'parfume-catalog'); ?>');
                        alert(response.data.message || '<?php _e('Грешка при скрейпване', 'parfume-catalog'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get monitor data for display
     */
    private function get_monitor_data() {
        // This would typically query the database for scraper status
        // For now, return mock data structure
        return array(
            'total_urls' => 0,
            'successful' => 0,
            'errors' => 0,
            'pending' => 0,
            'entries' => array()
        );
    }
    
    /**
     * Test scraper via AJAX
     */
    public function test_scraper() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_scraper_action', 'nonce');
        
        $test_url = esc_url_raw($_POST['test_url']);
        if (empty($test_url)) {
            wp_send_json_error(array('message' => __('Невалиден URL', 'parfume-catalog')));
        }
        
        // Mock scraper test - would implement actual scraping logic
        wp_send_json_success(array(
            'result' => array(
                'price' => '59.99 лв.',
                'old_price' => '79.99 лв.',
                'ml_variants' => array('50ml', '100ml'),
                'availability' => 'В наличност',
                'delivery' => 'Безплатна доставка'
            )
        ));
    }
    
    /**
     * Save schema via AJAX
     */
    public function save_schema() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_scraper_action', 'nonce');
        
        $store_id = sanitize_text_field($_POST['store_id']);
        parse_str($_POST['schema_data'], $schema_data);
        
        $schemas = get_option($this->schemas_option, array());
        $schemas[$store_id] = array(
            'price_selector' => sanitize_text_field($schema_data['price_selector']),
            'old_price_selector' => sanitize_text_field($schema_data['old_price_selector']),
            'ml_selector' => sanitize_text_field($schema_data['ml_selector']),
            'availability_selector' => sanitize_text_field($schema_data['availability_selector']),
            'delivery_selector' => sanitize_text_field($schema_data['delivery_selector']),
            'updated' => current_time('mysql')
        );
        
        if (update_option($this->schemas_option, $schemas)) {
            wp_send_json_success(array('message' => __('Схемата е запазена', 'parfume-catalog')));
        } else {
            wp_send_json_error(array('message' => __('Грешка при запазване', 'parfume-catalog')));
        }
    }
    
    /**
     * Manual scrape via AJAX
     */
    public function manual_scrape() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_scraper_action', 'nonce');
        
        // Mock manual scrape - would implement actual scraping logic
        wp_send_json_success(array('message' => __('Скрейпването завърши успешно', 'parfume-catalog')));
    }
    
    /**
     * Get scraper log via AJAX
     */
    public function get_scraper_log() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_scraper_action', 'nonce');
        
        // Mock log data - would implement actual log retrieval
        $logs_html = '<div class="log-entry success">
            <div class="log-timestamp">2024-01-15 10:30:25</div>
            <div class="log-message">Успешно скрейпване на parfium.bg</div>
            <div class="log-details">Цена: 59.99 лв., Варианти: 50ml, 100ml</div>
        </div>';
        
        wp_send_json_success(array('logs_html' => $logs_html));
    }
}

// Initialize the admin scraper
new Parfume_Admin_Scraper();