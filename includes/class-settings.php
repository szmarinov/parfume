<?php
namespace Parfume_Reviews;

/**
 * Settings class - Модулен заместител файл
 * Зарежда всички settings компоненти от отделни файлове
 * 
 * Файл: includes/class-settings.php
 * FIXED VERSION: Променена структура от табове на отделни секции/страници
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
    
    /**
     * Текущата активна секция
     */
    private $current_section = 'general';
    
    public function __construct() {
        // Зареждаме всички settings компоненти
        $this->load_settings_components();
        
        // Основни хукове за admin менюто
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Определяме текущата секция
        $this->current_section = $this->get_current_section();
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
                }
            }
        }
    }
    
    /**
     * Добавя admin меню страница с подменюта
     */
    public function add_admin_menu() {
        // Главна settings страница
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Parfume Reviews настройки', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
        
        // Подменюта за всяка секция
        $sections = $this->get_sections();
        foreach ($sections as $section_id => $section_data) {
            if ($section_id === 'general') continue; // Главната страница
            
            add_submenu_page(
                'edit.php?post_type=parfume',
                $section_data['title'],
                $section_data['menu_title'],
                'manage_options',
                'parfume-reviews-' . $section_id,
                array($this, 'render_settings_page')
            );
        }
    }
    
    /**
     * Получава всички секции
     */
    private function get_sections() {
        return array(
            'general' => array(
                'title' => __('Общи настройки', 'parfume-reviews'),
                'menu_title' => __('Общи', 'parfume-reviews'),
                'icon' => 'dashicons-admin-settings',
                'description' => __('Основни настройки на плъгина.', 'parfume-reviews')
            ),
            'url' => array(
                'title' => __('URL настройки', 'parfume-reviews'),
                'menu_title' => __('URL структура', 'parfume-reviews'),
                'icon' => 'dashicons-admin-links',
                'description' => __('Конфигурирайте URL структурата за различните типове страници.', 'parfume-reviews')
            ),
            'homepage' => array(
                'title' => __('Настройки за начална страница', 'parfume-reviews'),
                'menu_title' => __('Начална страница', 'parfume-reviews'),
                'icon' => 'dashicons-admin-home',
                'description' => __('Конфигурирайте как се показват парфюмите на началната страница.', 'parfume-reviews')
            ),
            'mobile' => array(
                'title' => __('Mobile настройки', 'parfume-reviews'),
                'menu_title' => __('Mobile', 'parfume-reviews'),
                'icon' => 'dashicons-smartphone',
                'description' => __('Настройки за мобилни устройства.', 'parfume-reviews')
            ),
            'stores' => array(
                'title' => __('Управление на магазини', 'parfume-reviews'),
                'menu_title' => __('Stores', 'parfume-reviews'),
                'icon' => 'dashicons-store',
                'description' => __('Управлявайте налични магазини за парфюми.', 'parfume-reviews')
            ),
            'scraper' => array(
                'title' => __('Product Scraper', 'parfume-reviews'),
                'menu_title' => __('Product Scraper', 'parfume-reviews'),
                'icon' => 'dashicons-update',
                'description' => __('Настройки за автоматично извличане на информация за продукти.', 'parfume-reviews')
            ),
            'price' => array(
                'title' => __('Настройки за цени', 'parfume-reviews'),
                'menu_title' => __('Цени', 'parfume-reviews'),
                'icon' => 'dashicons-money',
                'description' => __('Конфигурирайте как се показват цените.', 'parfume-reviews')
            ),
            'import_export' => array(
                'title' => __('Импорт и експорт', 'parfume-reviews'),
                'menu_title' => __('Импорт/Експорт', 'parfume-reviews'),
                'icon' => 'dashicons-migrate',
                'description' => __('Импортирайте и експортирайте парфюми и настройки.', 'parfume-reviews')
            ),
            'shortcodes' => array(
                'title' => __('Shortcodes документация', 'parfume-reviews'),
                'menu_title' => __('Shortcodes', 'parfume-reviews'),
                'icon' => 'dashicons-editor-code',
                'description' => __('Информация за наличните shortcodes и тяхната употреба.', 'parfume-reviews')
            ),
            'debug' => array(
                'title' => __('Дебъг информация', 'parfume-reviews'),
                'menu_title' => __('Дебъг', 'parfume-reviews'),
                'icon' => 'dashicons-performance',
                'description' => __('Инструменти за отстраняване на проблеми.', 'parfume-reviews')
            )
        );
    }
    
    /**
     * Получава текущата секция от URL
     */
    private function get_current_section() {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        
        if (strpos($page, 'parfume-reviews-') === 0) {
            $section = str_replace('parfume-reviews-', '', $page);
            return $section;
        }
        
        return 'general';
    }
    
    /**
     * Enqueue admin scripts и styles
     * FIXED: Добавен Select2 за search функционалност
     */
    public function enqueue_admin_scripts($hook_suffix) {
        // Зареждаме на всички settings страници
        if (strpos($hook_suffix, 'parfume_page_parfume-reviews') === false) {
            return;
        }
        
        // Enqueue Select2 за search функционалност
        wp_enqueue_style(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
            array(),
            '4.0.13'
        );
        
        wp_enqueue_script(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
            array('jquery'),
            '4.0.13',
            true
        );
        
        // Enqueue CSS - FIXED: Правилният file path
        wp_enqueue_style(
            'parfume-settings-sections',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-settings.css',
            array('select2'),
            PARFUME_REVIEWS_VERSION
        );
        
        // Enqueue JavaScript - FIXED: Правилният file с Select2 dependency
        wp_enqueue_script(
            'parfume-settings-sections',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-settings.js',
            array('jquery', 'select2'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Enqueue media uploader за store логота
        wp_enqueue_media();
        
        // Localize script за AJAX calls
        wp_localize_script('parfume-settings-sections', 'parfumeSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_settings_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'current_section' => $this->current_section,
            'strings' => array(
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-reviews'),
                'scraping' => __('Скрейпване...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
                'success' => __('Успешно', 'parfume-reviews'),
                'searching' => __('Търсене...', 'parfume-reviews'),
                'no_results' => __('Няма намерени резултати', 'parfume-reviews'),
                'select_perfume' => __('Изберете парфюм...', 'parfume-reviews'),
                'select_brand' => __('Изберете марка...', 'parfume-reviews'),
                'settings_saved' => __('Настройките са запазени успешно!', 'parfume-reviews'),
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
     * FIXED: Нова структура с отделни секции вместо табове
     */
    public function render_settings_page() {
        if (isset($_GET['settings-updated'])) {
            // Flush rewrite rules след запазване на URL настройки
            flush_rewrite_rules();
            add_settings_error('parfume_reviews_messages', 'parfume_reviews_message', 
                __('Настройките са запазени.', 'parfume-reviews'), 'updated');
        }
        
        settings_errors('parfume_reviews_messages');
        
        $sections = $this->get_sections();
        $current_section_data = $sections[$this->current_section];
        ?>
        <div class="wrap parfume-settings">
            <!-- Header Section -->
            <div class="settings-header">
                <h1>
                    <span class="dashicons <?php echo esc_attr($current_section_data['icon']); ?>"></span>
                    <?php echo esc_html($current_section_data['title']); ?>
                </h1>
                <p class="description"><?php echo esc_html($current_section_data['description']); ?></p>
            </div>
            
            <!-- Navigation Menu -->
            <div class="settings-navigation">
                <h2 class="nav-tab-wrapper">
                    <?php foreach ($sections as $section_id => $section_data): ?>
                        <a href="<?php echo esc_url($this->get_section_url($section_id)); ?>" 
                           class="nav-tab <?php echo ($section_id === $this->current_section) ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons <?php echo esc_attr($section_data['icon']); ?>"></span>
                            <?php echo esc_html($section_data['menu_title']); ?>
                        </a>
                    <?php endforeach; ?>
                </h2>
            </div>
            
            <!-- Settings Form -->
            <form action="options.php" method="post" id="parfume-settings-form">
                <?php
                settings_fields('parfume-reviews-settings');
                do_settings_sections('parfume-reviews-settings');
                ?>
                
                <div class="settings-content">
                    <?php $this->render_current_section(); ?>
                </div>
                
                <!-- Submit Button -->
                <div class="settings-footer">
                    <?php submit_button(__('Запази настройките', 'parfume-reviews'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url($this->get_section_url('general')); ?>" class="button button-secondary">
                        <?php _e('Връщане към общи настройки', 'parfume-reviews'); ?>
                    </a>
                </div>
            </form>
        </div>
        
        <style>
        .parfume-settings {
            max-width: 1200px;
        }
        
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .settings-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
            color: white;
        }
        
        .settings-header .dashicons {
            margin-right: 10px;
            font-size: 28px;
            width: 28px;
            height: 28px;
        }
        
        .settings-header .description {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .settings-navigation {
            margin-bottom: 30px;
        }
        
        .settings-navigation .nav-tab-wrapper {
            border-bottom: 2px solid #ddd;
            padding-bottom: 0;
        }
        
        .settings-navigation .nav-tab {
            background: white;
            border: 2px solid #ddd;
            border-bottom: none;
            color: #666;
            font-weight: 500;
            padding: 12px 20px;
            margin-right: 5px;
            text-decoration: none;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
            position: relative;
            top: 2px;
        }
        
        .settings-navigation .nav-tab:hover {
            background: #f8f9fa;
            color: #0073aa;
            border-color: #0073aa;
        }
        
        .settings-navigation .nav-tab-active {
            background: #0073aa;
            color: white;
            border-color: #0073aa;
        }
        
        .settings-navigation .nav-tab .dashicons {
            margin-right: 5px;
            font-size: 16px;
            width: 16px;
            height: 16px;
            vertical-align: middle;
        }
        
        .settings-content {
            background: white;
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .settings-footer {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: right;
        }
        
        .settings-footer .button {
            margin-left: 10px;
        }
        
        .settings-footer .button-primary {
            background: #0073aa;
            border-color: #0073aa;
            font-size: 16px;
            padding: 8px 20px;
            height: auto;
        }
        
        .settings-footer .button-primary:hover {
            background: #005a87;
            border-color: #005a87;
        }
        </style>
        <?php
    }
    
    /**
     * Рендерира текущата секция
     */
    private function render_current_section() {
        $component_name = $this->current_section . '_settings';
        
        if (isset($this->$component_name) && method_exists($this->$component_name, 'render_section')) {
            $this->$component_name->render_section();
        } else {
            echo '<div class="notice notice-warning">';
            echo '<p>' . sprintf(__('Компонентът "%s" не е зареден или не може да бъде рендериран.', 'parfume-reviews'), $this->current_section) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Получава URL за секция
     */
    private function get_section_url($section_id) {
        if ($section_id === 'general') {
            return admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings');
        }
        
        return admin_url('edit.php?post_type=parfume&page=parfume-reviews-' . $section_id);
    }
}