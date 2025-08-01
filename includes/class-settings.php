<?php
namespace Parfume_Reviews;

/**
 * Settings class - Модулен заместител файл
 * Зарежда всички settings компоненти от отделни файлове
 * 
 * Файл: includes/class-settings.php
 * UPDATED VERSION: Добавена поддръжка за Stores, Scraper и Mobile настройки
 * STORES SIDEBAR & PRODUCT SCRAPER: Пълна интеграция
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
        
        // STORES & SCRAPER: Инициализираме WP Cron за scraper
        add_action('init', array($this, 'init_scraper_cron'));
        
        // STORES & SCRAPER: AJAX handlers за scraper operations
        add_action('wp_ajax_parfume_run_scraper_batch', array($this, 'ajax_run_scraper_batch'));
        add_action('wp_ajax_parfume_scraper_test_url', array($this, 'ajax_scraper_test_url'));
        add_action('wp_ajax_parfume_save_store_schema', array($this, 'ajax_save_store_schema'));
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
     * STORES & SCRAPER: Инициализира WP Cron за scraper operations
     */
    public function init_scraper_cron() {
        // Проверяваме дали scraper event е schedulнат
        if (!wp_next_scheduled('parfume_scraper_batch_event')) {
            // Получаваме scraper frequency от настройките
            $settings = get_option('parfume_reviews_settings', array());
            $frequency = isset($settings['scraper_frequency']) ? intval($settings['scraper_frequency']) : 24;
            
            // Schedule scraper event
            wp_schedule_event(time(), 'hourly', 'parfume_scraper_batch_event');
        }
        
        // Hook за scraper batch execution
        add_action('parfume_scraper_batch_event', array($this, 'run_scraper_batch'));
    }
    
    /**
     * STORES & SCRAPER: Изпълнява batch scraping процес
     */
    public function run_scraper_batch() {
        if (!$this->scraper_settings) {
            return false;
        }
        
        // Делегираме към scraper settings класа
        if (method_exists($this->scraper_settings, 'run_batch_scraping')) {
            return $this->scraper_settings->run_batch_scraping();
        }
        
        return false;
    }
    
    /**
     * STORES & SCRAPER: AJAX handler за manual scraper batch
     */
    public function ajax_run_scraper_batch() {
        // Nonce verification
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_scraper_batch')) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        // Permission check
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $result = $this->run_scraper_batch();
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Batch scraping completed successfully', 'parfume-reviews'),
                'processed' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Batch scraping failed', 'parfume-reviews')
            ));
        }
    }
    
    /**
     * STORES & SCRAPER: AJAX handler за тестване на URL за scraping
     */
    public function ajax_scraper_test_url() {
        // Nonce verification
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_scraper_test')) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        // Permission check
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $test_url = sanitize_url($_POST['test_url']);
        
        if (!$test_url || !filter_var($test_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(array(
                'message' => __('Invalid URL provided', 'parfume-reviews')
            ));
        }
        
        // Делегираме към scraper settings класа
        if ($this->scraper_settings && method_exists($this->scraper_settings, 'test_scrape_url')) {
            $result = $this->scraper_settings->test_scrape_url($test_url);
            
            if ($result) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to scrape URL', 'parfume-reviews')
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => __('Scraper not available', 'parfume-reviews')
            ));
        }
    }
    
    /**
     * STORES & SCRAPER: AJAX handler за запазване на store schema
     */
    public function ajax_save_store_schema() {
        // Nonce verification
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_store_schema')) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        // Permission check
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $store_id = intval($_POST['store_id']);
        $schema_data = $_POST['schema_data'];
        
        // Валидация на schema данните
        if (!$store_id || !is_array($schema_data)) {
            wp_send_json_error(array(
                'message' => __('Invalid schema data', 'parfume-reviews')
            ));
        }
        
        // Делегираме към stores settings класа
        if ($this->stores_settings && method_exists($this->stores_settings, 'save_store_schema')) {
            $result = $this->stores_settings->save_store_schema($store_id, $schema_data);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Store schema saved successfully', 'parfume-reviews')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to save store schema', 'parfume-reviews')
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => __('Stores settings not available', 'parfume-reviews')
            ));
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
     * Регистрира settings
     */
    public function register_settings() {
        register_setting(
            'parfume_reviews_settings',
            'parfume_reviews_settings',
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Sanitize settings input
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (!is_array($input)) {
            return $sanitized;
        }
        
        // GENERAL SETTINGS
        if (isset($input['posts_per_page'])) {
            $sanitized['posts_per_page'] = absint($input['posts_per_page']);
        }
        
        // URL SETTINGS
        $url_fields = array('parfume_slug', 'blog_slug', 'marki_slug', 'gender_slug', 'aroma_type_slug', 'season_slug', 'intensity_slug', 'notes_slug', 'perfumer_slug');
        foreach ($url_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_title($input[$field]);
            }
        }
        
        // HOMEPAGE SETTINGS
        if (isset($input['homepage_hero_enabled'])) {
            $sanitized['homepage_hero_enabled'] = (bool) $input['homepage_hero_enabled'];
        }
        if (isset($input['homepage_featured_enabled'])) {
            $sanitized['homepage_featured_enabled'] = (bool) $input['homepage_featured_enabled'];
        }
        if (isset($input['homepage_latest_count'])) {
            $sanitized['homepage_latest_count'] = absint($input['homepage_latest_count']);
        }
        
        // MOBILE SETTINGS
        if (isset($input['mobile_fixed_panel'])) {
            $sanitized['mobile_fixed_panel'] = (bool) $input['mobile_fixed_panel'];
        }
        if (isset($input['mobile_show_close_btn'])) {
            $sanitized['mobile_show_close_btn'] = (bool) $input['mobile_show_close_btn'];
        }
        if (isset($input['mobile_z_index'])) {
            $sanitized['mobile_z_index'] = absint($input['mobile_z_index']);
        }
        if (isset($input['mobile_bottom_offset'])) {
            $sanitized['mobile_bottom_offset'] = absint($input['mobile_bottom_offset']);
        }
        
        // STORES SETTINGS
        if (isset($input['available_stores']) && is_array($input['available_stores'])) {
            $sanitized['available_stores'] = array();
            foreach ($input['available_stores'] as $store_id => $store_data) {
                if (is_array($store_data)) {
                    $sanitized['available_stores'][$store_id] = array(
                        'name' => sanitize_text_field($store_data['name']),
                        'logo_url' => esc_url_raw($store_data['logo_url']),
                        'schema' => isset($store_data['schema']) ? $store_data['schema'] : array()
                    );
                }
            }
        }
        
        // SCRAPER SETTINGS
        if (isset($input['scraper_enabled'])) {
            $sanitized['scraper_enabled'] = (bool) $input['scraper_enabled'];
        }
        if (isset($input['scraper_frequency'])) {
            $sanitized['scraper_frequency'] = absint($input['scraper_frequency']);
        }
        if (isset($input['scraper_timeout'])) {
            $sanitized['scraper_timeout'] = absint($input['scraper_timeout']);
        }
        if (isset($input['scraper_batch_size'])) {
            $sanitized['scraper_batch_size'] = absint($input['scraper_batch_size']);
        }
        if (isset($input['scraper_user_agent'])) {
            $sanitized['scraper_user_agent'] = sanitize_text_field($input['scraper_user_agent']);
        }
        
        // PRICE SETTINGS
        if (isset($input['price_currency'])) {
            $sanitized['price_currency'] = sanitize_text_field($input['price_currency']);
        }
        if (isset($input['price_format'])) {
            $sanitized['price_format'] = sanitize_text_field($input['price_format']);
        }
        if (isset($input['show_old_prices'])) {
            $sanitized['show_old_prices'] = (bool) $input['show_old_prices'];
        }
        
        return $sanitized;
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
        
        // STORES & SCRAPER: Enqueue допълнителни scripts
        wp_enqueue_script(
            'parfume-scraper-test',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/scraper-test.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Локализация за AJAX
        wp_localize_script('parfume-settings-tabs', 'parfume_settings_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_settings_nonce'),
            'scraper_batch_nonce' => wp_create_nonce('parfume_scraper_batch'),
            'scraper_test_nonce' => wp_create_nonce('parfume_scraper_test'),
            'store_schema_nonce' => wp_create_nonce('parfume_store_schema'),
            'strings' => array(
                'scraping_in_progress' => __('Scraping in progress...', 'parfume-reviews'),
                'scraping_completed' => __('Scraping completed', 'parfume-reviews'),
                'scraping_failed' => __('Scraping failed', 'parfume-reviews'),
                'testing_url' => __('Testing URL...', 'parfume-reviews'),
                'test_completed' => __('Test completed', 'parfume-reviews'),
                'test_failed' => __('Test failed', 'parfume-reviews'),
                'schema_saved' => __('Schema saved successfully', 'parfume-reviews'),
                'schema_save_failed' => __('Failed to save schema', 'parfume-reviews')
            )
        ));
    }
    
    /**
     * Рендерира settings страницата
     */
    public function render_settings_page() {
        ?>
        <div class="wrap parfume-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php" id="parfume-settings-form">
                <?php settings_fields('parfume_reviews_settings'); ?>
                
                <!-- Tab Navigation -->
                <nav class="nav-tab-wrapper parfume-nav-tabs">
                    <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php _e('Основни', 'parfume-reviews'); ?></a>
                    <a href="#url" class="nav-tab" data-tab="url"><?php _e('URL структура', 'parfume-reviews'); ?></a>
                    <a href="#homepage" class="nav-tab" data-tab="homepage"><?php _e('Начална страница', 'parfume-reviews'); ?></a>
                    <a href="#mobile" class="nav-tab" data-tab="mobile"><?php _e('Mobile настройки', 'parfume-reviews'); ?></a>
                    <a href="#stores" class="nav-tab" data-tab="stores"><?php _e('Магазини', 'parfume-reviews'); ?></a>
                    <a href="#product-scraper" class="nav-tab" data-tab="product-scraper"><?php _e('Product Scraper', 'parfume-reviews'); ?></a>
                    <a href="#prices" class="nav-tab" data-tab="prices"><?php _e('Цени', 'parfume-reviews'); ?></a>
                    <a href="#import_export" class="nav-tab" data-tab="import_export"><?php _e('Импорт/Експорт', 'parfume-reviews'); ?></a>
                </nav>
                
                <!-- Tab Content -->
                <div class="parfume-tab-content">
                    
                    <!-- General Tab -->
                    <div id="general" class="tab-content tab-content-active">
                        <h2><?php _e('Основни настройки', 'parfume-reviews'); ?></h2>
                        <p><?php _e('Конфигурирайте основните настройки на плъгина.', 'parfume-reviews'); ?></p>
                        <?php
                        if (isset($this->general_settings) && method_exists($this->general_settings, 'render_section')) {
                            $this->general_settings->render_section();
                        } else {
                            echo '<p class="notice notice-warning">' . __('General settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- URL Tab -->
                    <div id="url" class="tab-content">
                        <h2><?php _e('URL структура', 'parfume-reviews'); ?></h2>
                        <p><?php _e('Настройте URL структурата на парфюмните страници и таксономии.', 'parfume-reviews'); ?></p>
                        <?php
                        if (isset($this->url_settings) && method_exists($this->url_settings, 'render_section')) {
                            $this->url_settings->render_section();
                        } else {
                            echo '<p class="notice notice-warning">' . __('URL settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
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
                        } else {
                            echo '<p class="notice notice-warning">' . __('Homepage settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- Mobile Tab -->
                    <div id="mobile" class="tab-content">
                        <h2><?php _e('Mobile настройки', 'parfume-reviews'); ?></h2>
                        <p><?php _e('Настройки за stores sidebar на мобилни устройства.', 'parfume-reviews'); ?></p>
                        <?php
                        if (isset($this->mobile_settings) && method_exists($this->mobile_settings, 'render_section')) {
                            $this->mobile_settings->render_section();
                        } else {
                            echo '<p class="notice notice-warning">' . __('Mobile settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- Stores Tab -->
                    <div id="stores" class="tab-content">
                        <h2><?php _e('Управление на магазини', 'parfume-reviews'); ?></h2>
                        <p><?php _e('Управлявайте налични магазини за парфюми и техните настройки.', 'parfume-reviews'); ?></p>
                        <?php
                        if (isset($this->stores_settings) && method_exists($this->stores_settings, 'render_section')) {
                            $this->stores_settings->render_section();
                        } else {
                            echo '<p class="notice notice-warning">' . __('Stores settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
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
                        } else {
                            echo '<p class="notice notice-warning">' . __('Scraper settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
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
                        } else {
                            echo '<p class="notice notice-warning">' . __('Price settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
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
                        } else {
                            echo '<p class="notice notice-warning">' . __('Import/Export settings компонентът не е зареден.', 'parfume-reviews') . '</p>';
                        }
                        ?>
                    </div>
                    
                </div>
                
                <?php submit_button(__('Запази настройките', 'parfume-reviews'), 'primary', 'submit', true, array('id' => 'parfume-settings-submit')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * STORES & SCRAPER: Получава scraper статистики за dashboard
     */
    public function get_scraper_stats() {
        if ($this->scraper_settings && method_exists($this->scraper_settings, 'get_scraper_statistics')) {
            return $this->scraper_settings->get_scraper_statistics();
        }
        
        return array(
            'total_products' => 0,
            'products_with_scraping' => 0,
            'recent_scrapes' => 0,
            'failed_scrapes' => 0
        );
    }
    
    /**
     * STORES & SCRAPER: Получава stores configuration
     */
    public function get_stores_config() {
        if ($this->stores_settings && method_exists($this->stores_settings, 'get_all_stores')) {
            return $this->stores_settings->get_all_stores();
        }
        
        return array();
    }
    
    /**
     * Получава всички настройки
     */
    public function get_all_settings() {
        return get_option('parfume_reviews_settings', array());
    }
}