<?php
namespace Parfume_Reviews;

/**
 * Settings class - Модулен заместител файл
 * Зарежда всички settings компоненти от отделни файлове
 * 
 * Файл: includes/class-settings.php
 * UPDATED VERSION: Добавени нови компоненти за Stores, Scraper и Mobile
 */
class Settings {
    
    /**
     * Instances от különböző settings компоненти
     */
    private $general_settings;
    private $url_settings;
    private $homepage_settings;
    private $mobile_settings;
    private $stores_settings;
    private $scraper_settings;
    private $price_settings;
    private $import_export_settings;
    private $shortcodes_settings;
    private $debug_settings;
    
    public function __construct() {
        // Зареждаме всички settings компоненти
        $this->load_settings_components();
        
        // Основни хукове за admin менюто
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Зарежда всички settings компоненти от отделни файлове
     */
    private function load_settings_components() {
        $components = array(
            'includes/settings/class-settings-general.php' => 'Settings_General',
            'includes/settings/class-settings-url.php' => 'Settings_URL', 
            'includes/settings/class-settings-homepage.php' => 'Settings_Homepage',
            'includes/settings/class-settings-mobile.php' => 'Settings_Mobile',
            'includes/settings/class-settings-stores.php' => 'Settings_Stores',
            'includes/settings/class-settings-scraper.php' => 'Settings_Scraper',
            'includes/settings/class-settings-price.php' => 'Settings_Price',
            'includes/settings/class-settings-import-export.php' => 'Settings_Import_Export',
            'includes/settings/class-settings-shortcodes.php' => 'Settings_Shortcodes',
            'includes/settings/class-settings-debug.php' => 'Settings_Debug'
        );
        
        foreach ($components as $file => $class_name) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                // Инициализираме компонента ако класът съществува
                $full_class_name = 'Parfume_Reviews\\Settings\\' . $class_name;
                if (class_exists($full_class_name)) {
                    $property_name = strtolower(str_replace('Settings_', '', $class_name)) . '_settings';
                    $this->$property_name = new $full_class_name();
                    
                    // Debug logging ако е включен
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Parfume Reviews: Loaded settings component: {$class_name}");
                    }
                } else {
                    // Debug logging за липсващи класове
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Parfume Reviews: Settings component class not found: {$full_class_name}");
                    }
                }
            } else {
                // Debug logging за липсващи файлове
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Parfume Reviews: Settings component file not found: {$file_path}");
                }
            }
        }
    }
    
    /**
     * Добавя admin менюто
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Parfume Reviews настройки', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts и styles
     */
    public function enqueue_admin_scripts($hook) {
        // Зареждаме само на нашата settings страница
        if ($hook !== 'parfume_page_parfume-reviews-settings') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'parfume-admin-settings',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-settings.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'parfume-settings-tabs',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-settings.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Enqueue media uploader за store логота
        wp_enqueue_media();
        
        // Localize script за AJAX calls
        wp_localize_script('parfume-settings-tabs', 'parfumeSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_settings_nonce'),
            'strings' => array(
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-reviews'),
                'scraping' => __('Скрейпване...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
                'success' => __('Успешно', 'parfume-reviews'),
            )
        ));
    }
    
    /**
     * Регистрира всички настройки
     */
    public function register_settings() {
        // Основни групи настройки
        register_setting('parfume-reviews-settings', 'parfume_reviews_settings');
        register_setting('parfume-reviews-settings', 'parfume_reviews_stores');
        register_setting('parfume-reviews-settings', 'parfume_reviews_scraper_settings');
        register_setting('parfume-reviews-settings', 'parfume_reviews_mobile_settings');
        
        // Позволяваме на всеки компонент да регистрира собствените си настройки
        $this->register_component_settings();
    }
    
    /**
     * Регистрира настройките от всички компоненти
     */
    private function register_component_settings() {
        $components = array(
            'general_settings',
            'url_settings', 
            'homepage_settings',
            'mobile_settings',
            'stores_settings',
            'scraper_settings',
            'price_settings',
            'import_export_settings',
            'shortcodes_settings',
            'debug_settings'
        );
        
        foreach ($components as $component) {
            if (isset($this->$component) && method_exists($this->$component, 'register_settings')) {
                $this->$component->register_settings();
            }
        }
    }
    
    /**
     * Рендерира главната settings страница
     */
    public function render_settings_page() {
        if (isset($_GET['settings-updated'])) {
            // Flush rewrite rules след запазване на URL настройки
            flush_rewrite_rules();
            add_settings_error('parfume_reviews_messages', 'parfume_reviews_message', 
                __('Настройките са запазени.', 'parfume-reviews'), 'updated');
        }
        
        settings_errors('parfume_reviews_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active" data-tab="general">
                    <?php _e('Общи', 'parfume-reviews'); ?>
                </a>
                <a href="#url" class="nav-tab" data-tab="url">
                    <?php _e('URL', 'parfume-reviews'); ?>
                </a>
                <a href="#homepage" class="nav-tab" data-tab="homepage">
                    <?php _e('Начална страница', 'parfume-reviews'); ?>
                </a>
                <a href="#stores" class="nav-tab" data-tab="stores">
                    <?php _e('Магазини', 'parfume-reviews'); ?>
                </a>
                <a href="#scraper" class="nav-tab" data-tab="scraper">
                    <?php _e('Product Scraper', 'parfume-reviews'); ?>
                </a>
                <a href="#mobile" class="nav-tab" data-tab="mobile">
                    <?php _e('Mobile', 'parfume-reviews'); ?>
                </a>
                <a href="#price" class="nav-tab" data-tab="price">
                    <?php _e('Цени', 'parfume-reviews'); ?>
                </a>
                <a href="#import-export" class="nav-tab" data-tab="import-export">
                    <?php _e('Импорт/Експорт', 'parfume-reviews'); ?>
                </a>
                <a href="#shortcodes" class="nav-tab" data-tab="shortcodes">
                    <?php _e('Shortcodes', 'parfume-reviews'); ?>
                </a>
                <a href="#debug" class="nav-tab" data-tab="debug">
                    <?php _e('Дебъг', 'parfume-reviews'); ?>
                </a>
            </nav>
            
            <form method="post" action="options.php">
                <?php settings_fields('parfume-reviews-settings'); ?>
                
                <!-- General Tab -->
                <div id="general-tab" class="tab-content tab-content-active">
                    <h2><?php _e('Общи настройки', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->general_settings)): ?>
                        <?php $this->general_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- URL Tab -->
                <div id="url-tab" class="tab-content">
                    <h2><?php _e('URL настройки', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->url_settings)): ?>
                        <?php $this->url_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Homepage Tab -->
                <div id="homepage-tab" class="tab-content">
                    <h2><?php _e('Настройки за начална страница', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->homepage_settings)): ?>
                        <?php $this->homepage_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Stores Tab -->
                <div id="stores-tab" class="tab-content">
                    <h2><?php _e('Управление на магазини', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->stores_settings)): ?>
                        <?php $this->stores_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Scraper Tab -->
                <div id="scraper-tab" class="tab-content">
                    <h2><?php _e('Product Scraper настройки', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->scraper_settings)): ?>
                        <?php $this->scraper_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Tab -->
                <div id="mobile-tab" class="tab-content">
                    <h2><?php _e('Mobile настройки', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->mobile_settings)): ?>
                        <?php $this->mobile_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Price Tab -->
                <div id="price-tab" class="tab-content">
                    <h2><?php _e('Настройки за цени', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->price_settings)): ?>
                        <?php $this->price_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Import/Export Tab -->
                <div id="import-export-tab" class="tab-content">
                    <h2><?php _e('Импорт и експорт на данни', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->import_export_settings)): ?>
                        <?php $this->import_export_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Shortcodes Tab -->
                <div id="shortcodes-tab" class="tab-content">
                    <h2><?php _e('Shortcodes настройки', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->shortcodes_settings)): ?>
                        <?php $this->shortcodes_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Debug Tab -->
                <div id="debug-tab" class="tab-content">
                    <h2><?php _e('Дебъг информация', 'parfume-reviews'); ?></h2>
                    <?php if (isset($this->debug_settings)): ?>
                        <?php $this->debug_settings->render_section(); ?>
                    <?php endif; ?>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Tab switching functionality
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                
                var tabId = $(this).data('tab');
                
                // Remove active class from all tabs and content
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').removeClass('tab-content-active');
                
                // Add active class to clicked tab and corresponding content
                $(this).addClass('nav-tab-active');
                $('#' + tabId + '-tab').addClass('tab-content-active');
                
                // Update URL hash
                window.location.hash = tabId;
            });
            
            // Show tab based on URL hash
            if (window.location.hash) {
                var hash = window.location.hash.substring(1);
                var $targetTab = $('[data-tab="' + hash + '"]');
                if ($targetTab.length) {
                    $targetTab.click();
                }
            }
        });
        </script>
        
        <style>
        .tab-content {
            display: none;
            margin-top: 20px;
        }
        .tab-content-active {
            display: block;
        }
        .nav-tab-wrapper {
            margin-bottom: 0;
        }
        .tab-content h2 {
            margin-top: 0;
            padding-top: 20px;
        }
        .stores-management .store-item {
            border: 1px solid #ddd;
            margin-bottom: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .stores-management .store-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stores-management .store-logo img {
            margin-right: 15px;
        }
        .stores-management .add-store-form {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .scraper-settings .test-results-content {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 15px;
            border-radius: 4px;
        }
        .mobile-preview-container {
            border-radius: 8px;
            overflow: hidden;
        }
        .preview-mobile-panel {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
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
     * Получава компонент по име
     */
    public function get_component($component_name) {
        $property_name = $component_name . '_settings';
        return isset($this->$property_name) ? $this->$property_name : null;
    }
    
    /**
     * Проверява дали компонент е зареден
     */
    public function has_component($component_name) {
        $property_name = $component_name . '_settings';
        return isset($this->$property_name);
    }
    
    /**
     * Получава всички заредени компоненти
     */
    public function get_loaded_components() {
        $loaded = array();
        $components = array(
            'general', 'url', 'homepage', 'mobile', 'stores', 'scraper', 
            'price', 'import_export', 'shortcodes', 'debug'
        );
        
        foreach ($components as $component) {
            if ($this->has_component($component)) {
                $loaded[] = $component;
            }
        }
        
        return $loaded;
    }
    
    /**
     * Извиква метод на компонент
     */
    public function call_component_method($component_name, $method_name, $args = array()) {
        $component = $this->get_component($component_name);
        
        if ($component && method_exists($component, $method_name)) {
            return call_user_func_array(array($component, $method_name), $args);
        }
        
        return false;
    }
    
    /**
     * Backup функционалност за настройки
     */
    public function backup_settings() {
        $settings_backup = array(
            'parfume_reviews_settings' => get_option('parfume_reviews_settings', array()),
            'parfume_reviews_stores' => get_option('parfume_reviews_stores', array()),
            'parfume_reviews_scraper_settings' => get_option('parfume_reviews_scraper_settings', array()),
            'parfume_reviews_mobile_settings' => get_option('parfume_reviews_mobile_settings', array()),
            'backup_date' => current_time('mysql'),
            'backup_version' => PARFUME_REVIEWS_VERSION
        );
        
        return $settings_backup;
    }
    
    /**
     * Restore функционалност за настройки
     */
    public function restore_settings($backup_data) {
        if (!is_array($backup_data)) {
            return false;
        }
        
        $restored = array();
        
        $option_keys = array(
            'parfume_reviews_settings',
            'parfume_reviews_stores', 
            'parfume_reviews_scraper_settings',
            'parfume_reviews_mobile_settings'
        );
        
        foreach ($option_keys as $key) {
            if (isset($backup_data[$key])) {
                update_option($key, $backup_data[$key]);
                $restored[] = $key;
            }
        }
        
        return $restored;
    }
    
    /**
     * Валидира цялостността на настройките
     */
    public function validate_settings_integrity() {
        $issues = array();
        
        // Проверяваме дали основните опции съществуват
        $required_options = array(
            'parfume_reviews_settings' => 'Основни настройки',
            'parfume_reviews_stores' => 'Настройки за магазини'
        );
        
        foreach ($required_options as $option_key => $description) {
            $option_value = get_option($option_key);
            if ($option_value === false) {
                $issues[] = sprintf(__('Липсва опция: %s (%s)', 'parfume-reviews'), $option_key, $description);
            }
        }
        
        // Проверяваме дали stores са валидни
        $stores = get_option('parfume_reviews_stores', array());
        if (is_array($stores)) {
            foreach ($stores as $store_id => $store_data) {
                if (!isset($store_data['name']) || empty($store_data['name'])) {
                    $issues[] = sprintf(__('Магазин %s няма име', 'parfume-reviews'), $store_id);
                }
            }
        }
        
        // Проверяваме scraper настройки
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        if (is_array($scraper_settings)) {
            $required_scraper_fields = array('batch_size', 'timeout', 'user_agent');
            foreach ($required_scraper_fields as $field) {
                if (!isset($scraper_settings[$field])) {
                    $issues[] = sprintf(__('Scraper настройка липсва: %s', 'parfume-reviews'), $field);
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Получава статистики за настройките
     */
    public function get_settings_stats() {
        $stats = array();
        
        // Брой магазини
        $stores = get_option('parfume_reviews_stores', array());
        $stats['stores_count'] = is_array($stores) ? count($stores) : 0;
        
        // Брой постове с магазини
        global $wpdb;
        $posts_with_stores = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores' 
            AND meta_value != ''
        ");
        $stats['posts_with_stores'] = intval($posts_with_stores);
        
        // Брой product URLs
        $total_urls = 0;
        $posts_stores_data = $wpdb->get_results("
            SELECT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores' 
            AND meta_value != ''
        ");
        
        foreach ($posts_stores_data as $row) {
            $stores_data = maybe_unserialize($row->meta_value);
            if (is_array($stores_data)) {
                foreach ($stores_data as $store_data) {
                    if (!empty($store_data['product_url'])) {
                        $total_urls++;
                    }
                }
            }
        }
        $stats['total_product_urls'] = $total_urls;
        
        // Scraper статистики
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $stats['scraper_enabled'] = !empty($scraper_settings);
        $stats['scraper_interval'] = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 24;
        
        // Mobile настройки
        $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
        $stats['mobile_fixed_panel'] = isset($mobile_settings['fixed_panel_enabled']) ? $mobile_settings['fixed_panel_enabled'] : 1;
        
        return $stats;
    }
    
    /**
     * Миграция на стари настройки (ако е необходимо)
     */
    public function migrate_legacy_settings() {
        $current_version = get_option('parfume_reviews_settings_version', '1.0.0');
        
        if (version_compare($current_version, PARFUME_REVIEWS_VERSION, '<')) {
            // Извършваме миграция
            $this->perform_settings_migration($current_version);
            
            // Обновяваме версията
            update_option('parfume_reviews_settings_version', PARFUME_REVIEWS_VERSION);
            
            // Логираме миграцията
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Reviews: Settings migrated from {$current_version} to " . PARFUME_REVIEWS_VERSION);
            }
        }
    }
    
    /**
     * Извършва миграция на настройки
     */
    private function perform_settings_migration($from_version) {
        // Тук може да добавим специфични миграции според версията
        
        // Пример за миграция от версия 1.0.0 към 1.1.0
        if (version_compare($from_version, '1.1.0', '<')) {
            // Мигрираме стари настройки към нова структура
            $old_settings = get_option('parfume_reviews_old_settings', array());
            if (!empty($old_settings)) {
                $new_settings = get_option('parfume_reviews_settings', array());
                // Преобразуваме стари настройки към нови
                // ... migration logic ...
                update_option('parfume_reviews_settings', $new_settings);
                delete_option('parfume_reviews_old_settings');
            }
        }
    }
    
    /**
     * Debug информация за настройките
     */
    public function get_debug_info() {
        $debug_info = array(
            'loaded_components' => $this->get_loaded_components(),
            'settings_stats' => $this->get_settings_stats(),
            'integrity_issues' => $this->validate_settings_integrity(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => PARFUME_REVIEWS_VERSION,
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        );
        
        return $debug_info;
    }
}