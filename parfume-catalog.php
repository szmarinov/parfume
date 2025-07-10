<?php
/**
 * Plugin Name: Parfume Catalog
 * Plugin URI: https://example.com/parfume-catalog
 * Description: Цялостен WordPress плъгин за управление, каталогизиране и ревю на парфюми с висока интерактивност.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: parfume-catalog
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

// Дефиниране на константи
define('PARFUME_CATALOG_VERSION', '1.0.0');
define('PARFUME_CATALOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARFUME_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PARFUME_CATALOG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Главен клас на плъгина
 */
class Parfume_Catalog {
    
    /**
     * Единичен инстанс на класа
     */
    private static $instance = null;
    
    /**
     * Версия на базата данни
     */
    private $db_version = '1.0.0';
    
    /**
     * Връща единичен инстанс на класа
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Конструктор
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Инициализация на hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'), 0); // Early init
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Активиране на плъгина
     */
    public function activate() {
        $this->create_database_tables();
        $this->create_default_options();
        
        // Зареди post types за flush_rewrite_rules
        $this->require_file('includes/class-post-types.php');
        new Parfume_Catalog_Post_Types();
        
        // Обновяване на permalink структурата
        flush_rewrite_rules();
        
        // Запазване на версията в базата данни
        update_option('parfume_catalog_db_version', $this->db_version);
        update_option('parfume_catalog_activated', true);
    }
    
    /**
     * Деактивиране на плъгина
     */
    public function deactivate() {
        flush_rewrite_rules();
        update_option('parfume_catalog_activated', false);
    }
    
    /**
     * Създаване на таблици в базата данни
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица за коментари
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        $sql_comments = "CREATE TABLE $comments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            author_name varchar(255) NOT NULL,
            author_email varchar(255) DEFAULT '',
            author_ip varchar(100) NOT NULL,
            content text NOT NULL,
            rating tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Таблица за scraper данни
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        
        $sql_scraper = "CREATE TABLE $scraper_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            store_id varchar(50) NOT NULL,
            product_url text NOT NULL,
            price decimal(10,2) DEFAULT NULL,
            old_price decimal(10,2) DEFAULT NULL,
            currency varchar(10) DEFAULT 'лв.',
            variants text DEFAULT NULL,
            availability varchar(100) DEFAULT NULL,
            delivery_info text DEFAULT NULL,
            last_scraped datetime DEFAULT NULL,
            next_scrape datetime DEFAULT NULL,
            scrape_status varchar(20) DEFAULT 'pending',
            scrape_errors int DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY post_store (post_id, store_id),
            KEY store_id (store_id),
            KEY last_scraped (last_scraped),
            KEY scrape_status (scrape_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_comments);
        dbDelta($sql_scraper);
    }
    
    /**
     * Създаване на default настройки
     */
    private function create_default_options() {
        $default_options = array(
            'archive_slug' => 'parfiumi',
            'type_slug' => 'parfiumi',
            'vid_slug' => 'parfiumi',
            'marki_slug' => 'parfiumi/marki',
            'season_slug' => 'parfiumi/season',
            'intensity_slug' => 'parfiumi/intensity',
            'notes_slug' => 'notes',
            'similar_parfumes_count' => 4,
            'recently_viewed_count' => 4,
            'brand_parfumes_count' => 4,
            'comparison_max_items' => 4,
            'scraper_interval' => 12, // hours
            'scraper_batch_size' => 10,
            'scraper_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        );
        
        add_option('parfume_catalog_options', $default_options);
        add_option('parfume_catalog_stores', array());
        add_option('parfume_catalog_scraper_settings', array(
            'enabled' => true,
            'interval' => 12,
            'batch_size' => 10,
            'max_errors' => 5
        ));
    }
    
    /**
     * Главна инициализация
     */
    public function init() {
        $this->load_dependencies();
        $this->init_post_types();
        $this->init_modules();
    }
    
    /**
     * Зареждане на зависимости
     */
    private function load_dependencies() {
        // Основни класове
        $this->require_file('includes/class-admin.php');
        $this->require_file('includes/class-post-types.php');
        $this->require_file('includes/class-meta-fields.php');
        $this->require_file('includes/class-template-loader.php');
        
        // Модули
        $this->require_file('modules/class-stores.php');
        $this->require_file('modules/class-scraper.php');
        $this->require_file('modules/class-scraper-test-tool.php');
        $this->require_file('modules/class-scraper-monitor.php');
        $this->require_file('modules/class-comparison.php');
        $this->require_file('modules/class-comments.php');
        $this->require_file('modules/class-filters.php');
        $this->require_file('modules/class-schema.php');
        $this->require_file('modules/class-blog.php');
        
        // Админ модули
        $this->require_file('modules/admin/class-admin-settings.php');
        $this->require_file('modules/admin/class-admin-stores.php');
        $this->require_file('modules/admin/class-admin-scraper.php');
        $this->require_file('modules/admin/class-admin-comparison.php');
        $this->require_file('modules/admin/class-admin-comments.php');
        
        // Мета полета модули
        $this->require_file('modules/meta-fields/class-meta-basic.php');
        $this->require_file('modules/meta-fields/class-meta-stores.php');
        $this->require_file('modules/meta-fields/class-meta-notes.php');
        $this->require_file('modules/meta-fields/class-meta-stats.php');
    }
    
    /**
     * Безопасно зареждане на файл
     */
    private function require_file($relative_path) {
        $file_path = PARFUME_CATALOG_PLUGIN_DIR . $relative_path;
        
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log("Parfume Catalog: Missing file - " . $relative_path);
        }
    }
    
    /**
     * Инициализация на post types
     */
    private function init_post_types() {
        $this->init_class('Parfume_Catalog_Post_Types');
    }
    
    /**
     * Инициализация на модули
     */
    private function init_modules() {
        // Инициализация на основни класове
        $this->init_class('Parfume_Catalog_Admin');
        $this->init_class('Parfume_Catalog_Meta_Fields');
        $this->init_class('Parfume_Catalog_Template_Loader');
        
        // Инициализация на модули
        $this->init_class('Parfume_Catalog_Stores');
        $this->init_class('Parfume_Catalog_Scraper');
        $this->init_class('Parfume_Catalog_Scraper_Test_Tool');
        $this->init_class('Parfume_Catalog_Scraper_Monitor');
        $this->init_class('Parfume_Catalog_Comparison');
        $this->init_class('Parfume_Catalog_Comments');
        $this->init_class('Parfume_Catalog_Filters');
        $this->init_class('Parfume_Catalog_Schema');
        $this->init_class('Parfume_Catalog_Blog');
        
        // Админ модули
        if (is_admin()) {
            $this->init_class('Parfume_Catalog_Admin_Settings');
            $this->init_class('Parfume_Catalog_Admin_Stores');
            $this->init_class('Parfume_Catalog_Admin_Scraper');
            $this->init_class('Parfume_Catalog_Admin_Comparison');
            $this->init_class('Parfume_Catalog_Admin_Comments');
        }
        
        // Мета полета модули
        $this->init_class('Parfume_Catalog_Meta_Basic');
        $this->init_class('Parfume_Catalog_Meta_Stores');
        $this->init_class('Parfume_Catalog_Meta_Notes');
        $this->init_class('Parfume_Catalog_Meta_Stats');
    }
    
    /**
     * Безопасна инициализация на клас
     */
    private function init_class($class_name) {
        if (class_exists($class_name)) {
            new $class_name();
        } else {
            error_log("Parfume Catalog: Class not found - " . $class_name);
        }
    }
    
    /**
     * Зареждане на преводи
     */
    public function load_textdomain() {
        load_plugin_textdomain('parfume-catalog', false, dirname(PARFUME_CATALOG_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Зареждане на frontend скриптове
     */
    public function enqueue_frontend_scripts() {
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
        
        // Локализация за JavaScript
        wp_localize_script('parfume-catalog-frontend', 'parfumeCatalog', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_catalog_nonce'),
            'strings' => array(
                'addToComparison' => __('Добави за сравнение', 'parfume-catalog'),
                'removeFromComparison' => __('Премахни от сравнение', 'parfume-catalog'),
                'maxComparisonReached' => __('Максималният брой парфюми за сравнение е достигнат', 'parfume-catalog'),
                'loadingMore' => __('Зареждане...', 'parfume-catalog'),
                'noMoreResults' => __('Няма повече резултати', 'parfume-catalog')
            )
        ));
    }
    
    /**
     * Зареждане на admin скриптове
     */
    public function enqueue_admin_scripts($hook) {
        // Зареди само на страниците на плъгина
        if (strpos($hook, 'parfume') === false && get_post_type() !== 'parfumes') {
            return;
        }
        
        wp_enqueue_style(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PARFUME_CATALOG_VERSION
        );
        
        wp_enqueue_script(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            PARFUME_CATALOG_VERSION,
            true
        );
        
        // Локализация за admin JavaScript
        wp_localize_script('parfume-catalog-admin', 'parfumeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Сигурни ли сте?', 'parfume-catalog'),
                'saving' => __('Запазване...', 'parfume-catalog'),
                'saved' => __('Запазено!', 'parfume-catalog'),
                'error' => __('Грешка!', 'parfume-catalog')
            )
        ));
    }
}

// Инициализиране на плъгина
function parfume_catalog_init() {
    return Parfume_Catalog::get_instance();
}

// Стартиране на плъгина
parfume_catalog_init();