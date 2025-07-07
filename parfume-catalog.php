<?php
/**
 * Plugin Name: Parfume Catalog
 * Plugin URI: https://github.com/yourusername/parfume-catalog
 * Description: Цялостен WordPress плъгин за управление, каталогизиране и ревю на парфюми с динамични филтри, сравняване, product scraper и SEO оптимизация.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: parfume-catalog
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

// Дефиниране на константи за плъгина
define('PARFUME_CATALOG_VERSION', '1.0.0');
define('PARFUME_CATALOG_PLUGIN_FILE', __FILE__);
define('PARFUME_CATALOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARFUME_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PARFUME_CATALOG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Главен клас на плъгина Parfume Catalog
 */
class Parfume_Catalog {

    /**
     * Единствена инстанция на класа (Singleton pattern)
     */
    private static $instance = null;

    /**
     * Масив с инстанции на модулите
     */
    private $modules = array();

    /**
     * Получаване на единствената инстанция
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор - инициализация на плъгина
     */
    private function __construct() {
        // Регистриране на activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Инициализация на плъгина
        add_action('plugins_loaded', array($this, 'init'));
        
        // Зареждане на текстовия домейн за преводи
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Инициализация на плъгина
     */
    public function init() {
        // Проверка за минимални изисквания
        if (!$this->check_requirements()) {
            return;
        }

        // Зареждане на основните класове
        $this->load_core_classes();

        // Зареждане на модулите
        $this->load_modules();

        // Инициализация на hook-овете
        $this->init_hooks();
    }

    /**
     * Проверка за минимални изисквания
     */
    private function check_requirements() {
        // Проверка за PHP версия
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }

        // Проверка за WordPress версия
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }

        return true;
    }

    /**
     * Зареждане на основните класове
     */
    private function load_core_classes() {
        // Основни класове в includes/
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-admin.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-meta-fields.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-template-loader.php';

        // Инициализация на основните класове
        new Parfume_Catalog_Admin();
        new Parfume_Catalog_Post_Types();
        new Parfume_Catalog_Meta_Fields();
        new Parfume_Catalog_Template_Loader();
    }

    /**
     * Зареждане на модулите
     */
    private function load_modules() {
        $modules = array(
            'stores' => 'modules/class-stores.php',
            'scraper' => 'modules/class-scraper.php',
            'scraper_test_tool' => 'modules/class-scraper-test-tool.php',
            'scraper_monitor' => 'modules/class-scraper-monitor.php',
            'comparison' => 'modules/class-comparison.php',
            'comments' => 'modules/class-comments.php',
            'filters' => 'modules/class-filters.php',
            'schema' => 'modules/class-schema.php',
            'blog' => 'modules/class-blog.php'
        );

        // Зареждане на всеки модул
        foreach ($modules as $module_key => $module_file) {
            $file_path = PARFUME_CATALOG_PLUGIN_DIR . $module_file;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                // Създаване на инстанция на модула
                $class_name = 'Parfume_Catalog_' . ucfirst(str_replace('_', '_', $module_key));
                if (class_exists($class_name)) {
                    $this->modules[$module_key] = new $class_name();
                }
            }
        }

        // Зареждане на админ модулите
        if (is_admin()) {
            $admin_modules = array(
                'admin_settings' => 'modules/admin/class-admin-settings.php',
                'admin_stores' => 'modules/admin/class-admin-stores.php',
                'admin_scraper' => 'modules/admin/class-admin-scraper.php',
                'admin_comparison' => 'modules/admin/class-admin-comparison.php',
                'admin_comments' => 'modules/admin/class-admin-comments.php'
            );

            foreach ($admin_modules as $admin_module_key => $admin_module_file) {
                $admin_file_path = PARFUME_CATALOG_PLUGIN_DIR . $admin_module_file;
                if (file_exists($admin_file_path)) {
                    require_once $admin_file_path;
                }
            }
        }

        // Зареждане на мета полета модулите
        $meta_modules = array(
            'meta_basic' => 'modules/meta-fields/class-meta-basic.php',
            'meta_stores' => 'modules/meta-fields/class-meta-stores.php', 
            'meta_notes' => 'modules/meta-fields/class-meta-notes.php',
            'meta_stats' => 'modules/meta-fields/class-meta-stats.php'
        );

        foreach ($meta_modules as $meta_module_key => $meta_module_file) {
            $meta_file_path = PARFUME_CATALOG_PLUGIN_DIR . $meta_module_file;
            if (file_exists($meta_file_path)) {
                require_once $meta_file_path;
            }
        }
    }

    /**
     * Инициализация на hook-овете
     */
    private function init_hooks() {
        // Енqueue на скриптове и стилове
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX hook-ове
        add_action('wp_ajax_parfume_catalog_action', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_parfume_catalog_action', array($this, 'handle_ajax_request'));

        // REST API hook-ове
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Зареждане на frontend CSS и JS
     */
    public function enqueue_frontend_assets() {
        // CSS файлове
        wp_enqueue_style(
            'parfume-catalog-frontend',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PARFUME_CATALOG_VERSION
        );

        wp_enqueue_style(
            'parfume-catalog-mobile',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/mobile.css',
            array('parfume-catalog-frontend'),
            PARFUME_CATALOG_VERSION
        );

        // JS файлове
        wp_enqueue_script(
            'parfume-catalog-frontend',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );

        wp_enqueue_script(
            'parfume-catalog-comparison',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/comparison.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );

        wp_enqueue_script(
            'parfume-catalog-mobile-stores',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/mobile-stores.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );

        // Локализация на JS променливи
        wp_localize_script('parfume-catalog-frontend', 'parfume_catalog_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_catalog_nonce'),
            'rest_url' => rest_url('parfume-catalog/v1/'),
            'plugin_url' => PARFUME_CATALOG_PLUGIN_URL
        ));
    }

    /**
     * Зареждане на admin CSS и JS
     */
    public function enqueue_admin_assets($hook) {
        // Зареждане само на admin страниците на плъгина
        if (strpos($hook, 'parfume-catalog') === false && 
            !in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            return;
        }

        // CSS файлове
        wp_enqueue_style(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PARFUME_CATALOG_VERSION
        );

        // JS файлове
        wp_enqueue_script(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            PARFUME_CATALOG_VERSION,
            true
        );

        wp_enqueue_script(
            'parfume-catalog-scraper',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/scraper.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );

        // Локализация на admin JS
        wp_localize_script('parfume-catalog-admin', 'parfume_catalog_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_catalog_admin_nonce'),
            'plugin_url' => PARFUME_CATALOG_PLUGIN_URL
        ));
    }

    /**
     * Обработка на AJAX заявки
     */
    public function handle_ajax_request() {
        // Проверка на nonce за сигурност
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_catalog_nonce')) {
            wp_die('Security check failed');
        }

        // Получаване на action параметъра
        $action = sanitize_text_field($_POST['action_type']);

        // Пренасочване към съответния модул
        switch ($action) {
            case 'scraper_action':
                if (isset($this->modules['scraper'])) {
                    $this->modules['scraper']->handle_ajax();
                }
                break;
            case 'comparison_action':
                if (isset($this->modules['comparison'])) {
                    $this->modules['comparison']->handle_ajax();
                }
                break;
            default:
                wp_send_json_error('Unknown action');
        }

        wp_die();
    }

    /**
     * Регистриране на REST API endpoints
     */
    public function register_rest_routes() {
        // Регистриране на REST routes в модулите
        foreach ($this->modules as $module) {
            if (method_exists($module, 'register_rest_routes')) {
                $module->register_rest_routes();
            }
        }
    }

    /**
     * Зареждане на текстовия домейн за преводи
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'parfume-catalog',
            false,
            dirname(PARFUME_CATALOG_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Активиране на плъгина
     */
    public function activate() {
        // Създаване на database таблици
        $this->create_database_tables();

        // Задаване на default настройки
        $this->set_default_options();

        // Flush rewrite rules за новите URL структури
        flush_rewrite_rules();
    }

    /**
     * Деактивиране на плъгина
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Създаване на database таблици
     */
    private function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Таблица за scraper данни
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $scraper_sql = "CREATE TABLE $scraper_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            store_id int(11) NOT NULL,
            product_url varchar(500) NOT NULL,
            price decimal(10,2) DEFAULT NULL,
            old_price decimal(10,2) DEFAULT NULL,
            variants text DEFAULT NULL,
            availability varchar(100) DEFAULT NULL,
            delivery_info text DEFAULT NULL,
            last_scraped datetime DEFAULT NULL,
            next_scrape datetime DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            error_count int(3) DEFAULT 0,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY store_id (store_id),
            KEY status (status)
        ) $charset_collate;";

        // Таблица за коментари/ревюта
        $comments_table = $wpdb->prefix . 'parfume_comments';
        $comments_sql = "CREATE TABLE $comments_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            author_name varchar(100) DEFAULT 'Анонимен',
            author_ip varchar(45) NOT NULL,
            rating int(1) NOT NULL,
            comment_text text NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($scraper_sql);
        dbDelta($comments_sql);
    }

    /**
     * Задаване на default настройки
     */
    private function set_default_options() {
        $default_options = array(
            'parfume_catalog_archive_slug' => 'parfiumi',
            'parfume_catalog_scraper_interval' => 12,
            'parfume_catalog_scraper_batch_size' => 10,
            'parfume_catalog_comparison_max_items' => 4,
            'parfume_catalog_mobile_fixed_panel' => 1,
            'parfume_catalog_related_count' => 4,
            'parfume_catalog_recent_count' => 4,
            'parfume_catalog_brand_count' => 4
        );

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }

    /**
     * Съобщение за PHP версия
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Parfume Catalog изисква PHP версия 7.4 или по-нова. Вашата версия е %s.', 'parfume-catalog'),
            PHP_VERSION
        );
        echo '</p></div>';
    }

    /**
     * Съобщение за WordPress версия
     */
    public function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Parfume Catalog изисква WordPress версия 5.0 или по-нова. Вашата версия е %s.', 'parfume-catalog'),
            $GLOBALS['wp_version']
        );
        echo '</p></div>';
    }

    /**
     * Получаване на инстанция на модул
     */
    public function get_module($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name] : false;
    }

    /**
     * Проверка дали модул е активен
     */
    public function is_module_active($module_name) {
        return isset($this->modules[$module_name]);
    }
}

// Инициализация на плъгина
function parfume_catalog() {
    return Parfume_Catalog::get_instance();
}

// Стартиране на плъгина
parfume_catalog();

// Helper функции
if (!function_exists('parfume_catalog_get_option')) {
    function parfume_catalog_get_option($option_name, $default = false) {
        return get_option('parfume_catalog_' . $option_name, $default);
    }
}

if (!function_exists('parfume_catalog_update_option')) {
    function parfume_catalog_update_option($option_name, $value) {
        return update_option('parfume_catalog_' . $option_name, $value);
    }
}