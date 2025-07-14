<?php
namespace Parfume_Reviews;

/**
 * Settings class - Модулен заместител файл
 * Зарежда всички settings компоненти от отделни файлове
 * 
 * Файл: includes/class-settings.php
 */
class Settings {
    
    /**
     * Instances от различните settings компоненти
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
                    
                    // Debug лог
                    if (function_exists('parfume_reviews_debug_log') && defined('WP_DEBUG') && WP_DEBUG) {
                        parfume_reviews_debug_log("Settings component loaded: {$class_name}");
                    }
                } else {
                    if (function_exists('parfume_reviews_debug_log') && defined('WP_DEBUG') && WP_DEBUG) {
                        parfume_reviews_debug_log("Settings component class not found: {$full_class_name}", 'error');
                    }
                }
            } else {
                if (function_exists('parfume_reviews_debug_log') && defined('WP_DEBUG') && WP_DEBUG) {
                    parfume_reviews_debug_log("Missing settings component file: {$file}", 'error');
                }
            }
        }
    }
    
    /**
     * Добавя админ менюто
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Parfume Reviews Settings', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Зарежда admin скриптове и стилове
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'parfume_page_parfume-reviews-settings') {
            return;
        }
        
        wp_enqueue_style('parfume-admin-settings', PARFUME_REVIEWS_PLUGIN_URL . 
            'assets/css/admin-settings.css', array(), PARFUME_REVIEWS_VERSION);
        wp_enqueue_script('parfume-settings-tabs', PARFUME_REVIEWS_PLUGIN_URL . 
            'assets/js/admin-settings.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
        
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
        <div class="wrap parfume-settings">
            <h1><?php _e('Parfume Reviews настройки', 'parfume-reviews'); ?></h1>
            
            <!-- Tab navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('Общи', 'parfume-reviews'); ?></a>
                <a href="#url" class="nav-tab"><?php _e('URL настройки', 'parfume-reviews'); ?></a>
                <a href="#homepage" class="nav-tab"><?php _e('Начална страница', 'parfume-reviews'); ?></a>
                <a href="#mobile" class="nav-tab"><?php _e('Mobile настройки', 'parfume-reviews'); ?></a>
                <a href="#stores" class="nav-tab"><?php _e('Stores', 'parfume-reviews'); ?></a>
                <a href="#product-scraper" class="nav-tab"><?php _e('Product Scraper', 'parfume-reviews'); ?></a>
                <a href="#prices" class="nav-tab"><?php _e('Цени', 'parfume-reviews'); ?></a>
                <a href="#import_export" class="nav-tab"><?php _e('Импорт/Експорт', 'parfume-reviews'); ?></a>
                <a href="#shortcodes" class="nav-tab"><?php _e('Shortcodes', 'parfume-reviews'); ?></a>
                <a href="#debug" class="nav-tab"><?php _e('Дебъг', 'parfume-reviews'); ?></a>
            </h2>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('parfume-reviews-settings');
                do_settings_sections('parfume-reviews-settings');
                ?>
                
                <!-- General Tab -->
                <div id="general" class="tab-content">
                    <h2><?php _e('Общи настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Основни настройки на плъгина.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->general_settings) && method_exists($this->general_settings, 'render_section')) {
                        $this->general_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- URL Settings Tab -->
                <div id="url" class="tab-content">
                    <h2><?php _e('URL настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте URL структурата за различните типове страници.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->url_settings) && method_exists($this->url_settings, 'render_section')) {
                        $this->url_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Homepage Tab -->
                <div id="homepage" class="tab-content">
                    <h2><?php _e('Настройки за начална страница', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте как се показват парфюмите на началната страница.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->homepage_settings) && method_exists($this->homepage_settings, 'render_section')) {
                        $this->homepage_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Mobile Tab -->
                <div id="mobile" class="tab-content">
                    <h2><?php _e('Mobile настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за мобилни устройства.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->mobile_settings) && method_exists($this->mobile_settings, 'render_section')) {
                        $this->mobile_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Stores Tab -->
                <div id="stores" class="tab-content">
                    <h2><?php _e('Управление на магазини', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Управлявайте налични магазини за парфюми.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->stores_settings) && method_exists($this->stores_settings, 'render_section')) {
                        $this->stores_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Product Scraper Tab -->
                <div id="product-scraper" class="tab-content">
                    <h2><?php _e('Product Scraper', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за автоматично извличане на информация за продукти.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->scraper_settings) && method_exists($this->scraper_settings, 'render_section')) {
                        $this->scraper_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Prices Tab -->
                <div id="prices" class="tab-content">
                    <h2><?php _e('Настройки за цени', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте как се показват цените.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->price_settings) && method_exists($this->price_settings, 'render_section')) {
                        $this->price_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Import/Export Tab -->
                <div id="import_export" class="tab-content">
                    <h2><?php _e('Импорт и експорт', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Импортирайте и експортирайте парфюми и настройки.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->import_export_settings) && method_exists($this->import_export_settings, 'render_section')) {
                        $this->import_export_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Shortcodes Tab -->
                <div id="shortcodes" class="tab-content">
                    <h2><?php _e('Shortcodes документация', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Информация за наличните shortcodes и тяхната употреба.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->shortcodes_settings) && method_exists($this->shortcodes_settings, 'render_section')) {
                        $this->shortcodes_settings->render_section();
                    }
                    ?>
                </div>
                
                <!-- Debug Tab -->
                <div id="debug" class="tab-content">
                    <h2><?php _e('Дебъг информация', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Инструменти за отстраняване на проблеми.', 'parfume-reviews'); ?></p>
                    <?php
                    if (isset($this->debug_settings) && method_exists($this->debug_settings, 'render_section')) {
                        $this->debug_settings->render_section();
                    }
                    ?>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Получава instance от конкретен settings компонент
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
}