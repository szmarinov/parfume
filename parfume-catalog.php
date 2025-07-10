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
     * Състояние на плъгина
     */
    private $plugin_activated = false;
    
    /**
     * Заредени модули
     */
    private $loaded_modules = array();
    
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
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_action('wp_footer', array($this, 'add_footer_scripts'));
        
        // AJAX actions
        add_action('wp_ajax_parfume_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_nopriv_parfume_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_parfume_track_view', array($this, 'ajax_track_view'));
        add_action('wp_ajax_nopriv_parfume_track_view', array($this, 'ajax_track_view'));
    }
    
    /**
     * Активиране на плъгина
     */
    public function activate() {
        // Проверка на системните изисквания
        if (!$this->check_system_requirements()) {
            wp_die(__('Parfume Catalog изисква PHP 7.4+ и WordPress 5.0+', 'parfume-catalog'));
        }
        
        $this->create_database_tables();
        $this->create_default_options();
        $this->create_default_terms();
        $this->setup_user_capabilities();
        
        // Зареди post types и taxonomies за flush_rewrite_rules
        $this->require_file('includes/class-post-types.php');
        $post_types = new Parfume_Catalog_Post_Types();
        
        // Принудително обновяване на permalink структурата
        flush_rewrite_rules(true);
        
        // Запазване на версията в базата данни
        update_option('parfume_catalog_db_version', $this->db_version);
        update_option('parfume_catalog_activated', true);
        update_option('parfume_catalog_activation_date', current_time('mysql'));
        
        // Задаване на флаг за първоначално зареждане
        update_option('parfume_catalog_flush_rewrite_rules', true);
        
        $this->plugin_activated = true;
        
        // Logging
        error_log('Parfume Catalog Plugin: Successfully activated');
    }
    
    /**
     * Деактивиране на плъгина
     */
    public function deactivate() {
        // Изчистване на scheduled events
        wp_clear_scheduled_hook('parfume_catalog_scraper_cron');
        wp_clear_scheduled_hook('parfume_catalog_cleanup_cron');
        
        // Изчистване на transients
        $this->cleanup_transients();
        
        flush_rewrite_rules(true);
        update_option('parfume_catalog_activated', false);
        
        // Logging
        error_log('Parfume Catalog Plugin: Successfully deactivated');
    }
    
    /**
     * Проверка на системните изисквания
     */
    private function check_system_requirements() {
        $requirements_met = true;
        
        // PHP версия
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $requirements_met = false;
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                printf(__('Parfume Catalog изисква PHP версия 7.4 или по-нова. Текущата версия е %s.', 'parfume-catalog'), PHP_VERSION);
                echo '</p></div>';
            });
        }
        
        // WordPress версия
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $requirements_met = false;
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                printf(__('Parfume Catalog изисква WordPress версия 5.0 или по-нова. Текущата версия е %s.', 'parfume-catalog'), get_bloginfo('version'));
                echo '</p></div>';
            });
        }
        
        return $requirements_met;
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
            parent_id bigint(20) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY parent_id (parent_id),
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
            scraped_data text DEFAULT NULL,
            last_scraped datetime DEFAULT NULL,
            next_scrape datetime DEFAULT NULL,
            scrape_status varchar(20) DEFAULT 'pending',
            scrape_errors int DEFAULT 0,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY post_store (post_id, store_id),
            KEY store_id (store_id),
            KEY last_scraped (last_scraped),
            KEY scrape_status (scrape_status),
            KEY next_scrape (next_scrape)
        ) $charset_collate;";
        
        // Таблица за comparison данни
        $comparison_table = $wpdb->prefix . 'parfume_comparison_data';
        
        $sql_comparison = "CREATE TABLE $comparison_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            session_id varchar(64) NOT NULL,
            post_id bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY post_id (post_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Таблица за recently viewed
        $recently_viewed_table = $wpdb->prefix . 'parfume_recently_viewed';
        
        $sql_recently_viewed = "CREATE TABLE $recently_viewed_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            session_id varchar(64) NOT NULL,
            post_id bigint(20) NOT NULL,
            view_count int DEFAULT 1,
            last_viewed datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_post (user_id, post_id),
            KEY session_id (session_id),
            KEY post_id (post_id),
            KEY last_viewed (last_viewed)
        ) $charset_collate;";
        
        // Таблица за scraper logs
        $scraper_logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        
        $sql_scraper_logs = "CREATE TABLE $scraper_logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            store_id varchar(50) NOT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text DEFAULT NULL,
            execution_time decimal(5,3) DEFAULT NULL,
            memory_usage int DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY store_id (store_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_comments);
        dbDelta($sql_scraper);
        dbDelta($sql_comparison);
        dbDelta($sql_recently_viewed);
        dbDelta($sql_scraper_logs);
        
        // Създаване на индекси за производителност
        $this->create_database_indexes();
    }
    
    /**
     * Създаване на database indexes
     */
    private function create_database_indexes() {
        global $wpdb;
        
        // Добавяне на composite indexes за по-добра производителност
        $wpdb->query("ALTER TABLE {$wpdb->prefix}parfume_scraper_data ADD INDEX post_store_status (post_id, store_id, scrape_status)");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}parfume_comments ADD INDEX post_status_created (post_id, status, created_at)");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}parfume_recently_viewed ADD INDEX session_viewed (session_id, last_viewed)");
    }
    
    /**
     * Създаване на default настройки
     */
    private function create_default_options() {
        $default_options = array(
            // URL настройки
            'archive_slug' => 'parfiumi',
            'type_slug' => 'parfiumi',
            'vid_slug' => 'parfiumi',
            'marki_slug' => 'parfiumi/marki',
            'season_slug' => 'parfiumi/season',
            'intensity_slug' => 'parfiumi/intensity',
            'notes_slug' => 'notes',
            
            // Показване на съдържание
            'similar_parfumes_count' => 4,
            'recently_viewed_count' => 4,
            'brand_parfumes_count' => 4,
            'comparison_max_items' => 4,
            'archive_posts_per_page' => 12,
            
            // SEO настройки
            'enable_schema_markup' => true,
            'enable_breadcrumbs' => true,
            'enable_social_sharing' => true,
            
            // Scraper настройки
            'scraper_interval' => 12, // hours
            'scraper_batch_size' => 10,
            'scraper_timeout' => 30,
            'scraper_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'scraper_max_errors' => 5,
            'scraper_retry_delay' => 5, // minutes
            
            // Comparison настройки
            'comparison_enabled' => true,
            'comparison_show_in_archive' => true,
            'comparison_show_in_single' => true,
            'comparison_popup_position' => 'bottom-right',
            
            // Comments настройки
            'comments_enabled' => true,
            'comments_require_moderation' => true,
            'comments_allow_anonymous' => true,
            'comments_max_per_ip' => 5,
            'comments_time_limit' => 60, // seconds between comments
            'comments_captcha_enabled' => false,
            
            // Performance настройки
            'enable_caching' => true,
            'cache_duration' => 3600, // seconds
            'enable_lazy_loading' => true,
            'optimize_images' => true,
            
            // Advanced настройки
            'debug_mode' => false,
            'logging_enabled' => true,
            'cleanup_data_on_uninstall' => false
        );
        
        add_option('parfume_catalog_options', $default_options);
        
        // Stores настройки
        add_option('parfume_catalog_stores', array());
        
        // Scraper настройки
        $scraper_settings = array(
            'enabled' => true,
            'interval' => 12,
            'batch_size' => 10,
            'max_errors' => 5,
            'respect_robots' => true,
            'user_agent' => $default_options['scraper_user_agent'],
            'timeout' => $default_options['scraper_timeout'],
            'retry_delay' => $default_options['scraper_retry_delay']
        );
        add_option('parfume_catalog_scraper_settings', $scraper_settings);
        
        // Comparison настройки
        $comparison_settings = array(
            'enabled' => true,
            'max_items' => 4,
            'show_in_archive' => true,
            'show_in_single' => true,
            'popup_position' => 'bottom-right',
            'texts' => array(
                'add_to_comparison' => __('Добави за сравнение', 'parfume-catalog'),
                'remove_from_comparison' => __('Премахни от сравнение', 'parfume-catalog'),
                'compare_now' => __('Сравни сега', 'parfume-catalog'),
                'clear_all' => __('Изчисти всички', 'parfume-catalog'),
                'popup_title' => __('Сравнение на парфюми', 'parfume-catalog'),
                'max_items_reached' => __('Максималният брой парфюми за сравнение е достигнат', 'parfume-catalog')
            )
        );
        add_option('parfume_catalog_comparison_settings', $comparison_settings);
        
        // Comments настройки
        $comments_settings = array(
            'enabled' => true,
            'require_moderation' => true,
            'allow_anonymous' => true,
            'max_per_ip' => 5,
            'time_limit' => 60,
            'captcha_enabled' => false,
            'captcha_question' => __('Колко е 2 + 2?', 'parfume-catalog'),
            'captcha_answer' => '4',
            'blocked_words' => array('spam', 'casino', 'pharmacy'),
            'blocked_domains' => array(),
            'email_notifications' => true,
            'notification_email' => get_option('admin_email')
        );
        add_option('parfume_catalog_comments_settings', $comments_settings);
    }
    
    /**
     * Създаване на default terms
     */
    private function create_default_terms() {
        // Ще се извика след като post types и taxonomies са регистрирани
        add_action('init', array($this, 'create_default_taxonomy_terms'), 20);
    }
    
    /**
     * Създаване на default taxonomy terms
     */
    public function create_default_taxonomy_terms() {
        // Default notes data
        $default_notes = $this->get_default_notes_data();
        
        foreach ($default_notes as $note_data) {
            $term = wp_insert_term($note_data['note'], 'parfume_notes', array(
                'slug' => sanitize_title($note_data['note'])
            ));
            
            if (!is_wp_error($term)) {
                // Добавяне на group meta
                add_term_meta($term['term_id'], 'note_group', $note_data['group'], true);
            }
        }
    }
    
    /**
     * Default notes data
     */
    private function get_default_notes_data() {
        return array(
            array('note' => 'Iso E Super', 'group' => 'дървесни'),
            array('note' => 'Абаносово дърво', 'group' => 'дървесни'),
            array('note' => 'Абсент', 'group' => 'ароматни'),
            array('note' => 'Авокадо', 'group' => 'зелени'),
            array('note' => 'Австралийски син кипарис', 'group' => 'дървесни'),
            array('note' => 'Агаве', 'group' => 'зелени'),
            array('note' => 'Агарово дърво (Оуд)', 'group' => 'ориенталски'),
            array('note' => 'Аглая', 'group' => 'цветни'),
            array('note' => 'Адвокат', 'group' => 'гурме'),
            array('note' => 'Роза', 'group' => 'цветни'),
            array('note' => 'Жасмин', 'group' => 'цветни'),
            array('note' => 'Ванилия', 'group' => 'гурме'),
            array('note' => 'Санталово дърво', 'group' => 'дървесни'),
            array('note' => 'Кедър', 'group' => 'дървесни'),
            array('note' => 'Бергамот', 'group' => 'цитрусови'),
            array('note' => 'Лимон', 'group' => 'цитрусови'),
            array('note' => 'Портокал', 'group' => 'цитрусови'),
            array('note' => 'Пачули', 'group' => 'дървесни'),
            array('note' => 'Амбър', 'group' => 'ориенталски'),
            array('note' => 'Мускус', 'group' => 'животински')
        );
    }
    
    /**
     * Setup user capabilities
     */
    private function setup_user_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_parfume_catalog');
            $admin_role->add_cap('moderate_parfume_comments');
            $admin_role->add_cap('manage_parfume_scraper');
        }
        
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('manage_parfume_catalog');
            $editor_role->add_cap('moderate_parfume_comments');
        }
    }
    
    /**
     * Главна инициализация
     */
    public function init() {
        $this->load_dependencies();
        $this->init_post_types();
        $this->init_modules();
        $this->schedule_cron_jobs();
        
        // Проверка за flush rewrite rules
        if (get_option('parfume_catalog_flush_rewrite_rules')) {
            flush_rewrite_rules(true);
            delete_option('parfume_catalog_flush_rewrite_rules');
        }
        
        // Проверка за database updates
        $this->check_database_updates();
    }
    
    /**
     * Проверка за database updates
     */
    private function check_database_updates() {
        $current_db_version = get_option('parfume_catalog_db_version', '0.0.0');
        
        if (version_compare($current_db_version, $this->db_version, '<')) {
            $this->update_database($current_db_version);
            update_option('parfume_catalog_db_version', $this->db_version);
        }
    }
    
    /**
     * Database update
     */
    private function update_database($from_version) {
        // Handle database updates based on version
        if (version_compare($from_version, '1.0.0', '<')) {
            // First installation or major update
            $this->create_database_tables();
        }
        
        // Log update
        error_log("Parfume Catalog: Database updated from {$from_version} to {$this->db_version}");
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('parfume_catalog_scraper_cron')) {
            wp_schedule_event(time(), 'hourly', 'parfume_catalog_scraper_cron');
        }
        
        if (!wp_next_scheduled('parfume_catalog_cleanup_cron')) {
            wp_schedule_event(time(), 'daily', 'parfume_catalog_cleanup_cron');
        }
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
            
            // Track loaded files for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->loaded_modules[] = $relative_path;
            }
        } else {
            error_log("Parfume Catalog: Missing file - " . $relative_path);
            
            // Show admin notice for missing critical files
            if (is_admin()) {
                add_action('admin_notices', function() use ($relative_path) {
                    echo '<div class="notice notice-error"><p>';
                    printf(__('Parfume Catalog: Липсва файл %s', 'parfume-catalog'), $relative_path);
                    echo '</p></div>';
                });
            }
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
            
            // Show admin notice for missing critical classes
            if (is_admin()) {
                add_action('admin_notices', function() use ($class_name) {
                    echo '<div class="notice notice-warning"><p>';
                    printf(__('Parfume Catalog: Класът %s не е намерен', 'parfume-catalog'), $class_name);
                    echo '</p></div>';
                });
            }
        }
    }
    
    /**
     * Зареждане на преводи
     */
    public function load_textdomain() {
        $loaded = load_plugin_textdomain('parfume-catalog', false, dirname(PARFUME_CATALOG_PLUGIN_BASENAME) . '/languages');
        
        if (!$loaded && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Catalog: Failed to load textdomain');
        }
    }
    
    /**
     * Зареждане на frontend скриптове
     */
    public function enqueue_frontend_scripts() {
        // Зареди само на parfume страници
        if (!$this->is_parfume_page()) {
            return;
        }
        
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
                'noMoreResults' => __('Няма повече резултати', 'parfume-catalog'),
                'copySuccess' => __('Копирано в клипборда!', 'parfume-catalog'),
                'copyError' => __('Грешка при копиране', 'parfume-catalog'),
                'confirmClearComparison' => __('Сигурни ли сте, че искате да изчистите сравнението?', 'parfume-catalog')
            ),
            'settings' => array(
                'maxComparisonItems' => get_option('parfume_catalog_options')['comparison_max_items'] ?? 4,
                'enableLazyLoading' => get_option('parfume_catalog_options')['enable_lazy_loading'] ?? true,
                'cacheEnabled' => get_option('parfume_catalog_options')['enable_caching'] ?? true
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
                'error' => __('Грешка!', 'parfume-catalog'),
                'selectImage' => __('Избери изображение', 'parfume-catalog'),
                'removeImage' => __('Премахни изображение', 'parfume-catalog')
            ),
            'settings' => array(
                'debugMode' => get_option('parfume_catalog_options')['debug_mode'] ?? false
            )
        ));
    }
    
    /**
     * Проверка дали сме на parfume страница
     */
    private function is_parfume_page() {
        return is_singular('parfumes') || 
               is_post_type_archive('parfumes') || 
               is_tax('parfume_type') || 
               is_tax('parfume_vid') || 
               is_tax('parfume_marki') || 
               is_tax('parfume_season') || 
               is_tax('parfume_intensity') || 
               is_tax('parfume_notes') ||
               is_singular('parfume_blog') ||
               is_post_type_archive('parfume_blog');
    }
    
    /**
     * Добавяне на meta tags
     */
    public function add_meta_tags() {
        if (!$this->is_parfume_page()) {
            return;
        }
        
        $options = get_option('parfume_catalog_options', array());
        
        if (isset($options['enable_schema_markup']) && $options['enable_schema_markup']) {
            // Schema markup ще бъде добавен от Schema класа
        }
        
        // Добавяне на custom meta tags
        echo '<meta name="parfume-catalog-version" content="' . PARFUME_CATALOG_VERSION . '">' . "\n";
        
        if (is_singular('parfumes')) {
            $post_id = get_the_ID();
            
            // Add specific meta for parfume
            $brands = get_the_terms($post_id, 'parfume_marki');
            if ($brands && !is_wp_error($brands)) {
                echo '<meta name="parfume-brand" content="' . esc_attr($brands[0]->name) . '">' . "\n";
            }
            
            $types = get_the_terms($post_id, 'parfume_type');
            if ($types && !is_wp_error($types)) {
                echo '<meta name="parfume-type" content="' . esc_attr($types[0]->name) . '">' . "\n";
            }
        }
    }
    
    /**
     * Добавяне на footer scripts
     */
    public function add_footer_scripts() {
        if (!$this->is_parfume_page()) {
            return;
        }
        
        // Tracking script за recently viewed
        if (is_singular('parfumes')) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Track parfume view
                $.ajax({
                    url: parfumeCatalog.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'parfume_track_view',
                        post_id: <?php echo get_the_ID(); ?>,
                        nonce: parfumeCatalog.nonce
                    }
                });
            });
            </script>
            <?php
        }
    }
    
    /**
     * AJAX handler за comparison data
     */
    public function ajax_get_comparison_data() {
        check_ajax_referer('parfume_catalog_nonce', 'nonce');
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        if (empty($post_ids)) {
            wp_send_json_error('No post IDs provided');
        }
        
        $data = array();
        
        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) !== 'parfumes') {
                continue;
            }
            
            $data[] = array(
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'url' => get_permalink($post_id),
                'image' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                'brand' => $this->get_parfume_brand($post_id),
                'type' => $this->get_parfume_type($post_id),
                'notes' => $this->get_parfume_notes($post_id, 3)
            );
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler за track view
     */
    public function ajax_track_view() {
        check_ajax_referer('parfume_catalog_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id || get_post_type($post_id) !== 'parfumes') {
            wp_send_json_error('Invalid post ID');
        }
        
        // Track the view
        $this->track_parfume_view($post_id);
        
        wp_send_json_success();
    }
    
    /**
     * Track parfume view
     */
    private function track_parfume_view($post_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'parfume_recently_viewed';
        $user_id = get_current_user_id();
        $session_id = $this->get_session_id();
        
        $wpdb->replace(
            $table,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'post_id' => $post_id,
                'last_viewed' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s')
        );
    }
    
    /**
     * Get session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Helper functions
     */
    private function get_parfume_brand($post_id) {
        $brands = get_the_terms($post_id, 'parfume_marki');
        return $brands && !is_wp_error($brands) ? $brands[0]->name : '';
    }
    
    private function get_parfume_type($post_id) {
        $types = get_the_terms($post_id, 'parfume_type');
        return $types && !is_wp_error($types) ? $types[0]->name : '';
    }
    
    private function get_parfume_notes($post_id, $limit = 5) {
        $notes = get_the_terms($post_id, 'parfume_notes');
        if (!$notes || is_wp_error($notes)) {
            return array();
        }
        
        $note_names = array();
        foreach (array_slice($notes, 0, $limit) as $note) {
            $note_names[] = $note->name;
        }
        
        return $note_names;
    }
    
    /**
     * Cleanup transients
     */
    private function cleanup_transients() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_parfume_catalog_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_parfume_catalog_%'");
    }
    
    /**
     * Get plugin info
     */
    public function get_plugin_info() {
        return array(
            'version' => PARFUME_CATALOG_VERSION,
            'activated' => $this->plugin_activated,
            'db_version' => $this->db_version,
            'loaded_modules' => $this->loaded_modules,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        );
    }
}

// Инициализиране на плъгина
function parfume_catalog_init() {
    return Parfume_Catalog::get_instance();
}

// Стартиране на плъгина
parfume_catalog_init();

// Helper функции за използване в templates
function parfume_catalog_get_brand($post_id) {
    $brands = get_the_terms($post_id, 'parfume_marki');
    return $brands && !is_wp_error($brands) ? $brands[0] : false;
}

function parfume_catalog_get_type($post_id) {
    $types = get_the_terms($post_id, 'parfume_type');
    return $types && !is_wp_error($types) ? $types[0] : false;
}

function parfume_catalog_get_notes($post_id, $limit = 0) {
    $notes = get_the_terms($post_id, 'parfume_notes');
    if (!$notes || is_wp_error($notes)) {
        return array();
    }
    
    return $limit > 0 ? array_slice($notes, 0, $limit) : $notes;
}

function parfume_catalog_is_comparison_enabled() {
    $settings = get_option('parfume_catalog_comparison_settings', array());
    return isset($settings['enabled']) && $settings['enabled'];
}

function parfume_catalog_get_option($option_name, $default = false) {
    $options = get_option('parfume_catalog_options', array());
    return isset($options[$option_name]) ? $options[$option_name] : $default;
}