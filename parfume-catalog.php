<?php
/**
 * Plugin Name: Parfume Catalog
 * Plugin URI: https://example.com/parfume-catalog
 * Description: Comprehensive WordPress plugin for perfume management, cataloging and reviews. Features custom post types, taxonomies, comparison system, scraper functionality, and user reviews without registration.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: parfume-catalog
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PARFUME_CATALOG_VERSION', '1.0.0');
define('PARFUME_CATALOG_PLUGIN_FILE', __FILE__);
define('PARFUME_CATALOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARFUME_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PARFUME_CATALOG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Parfume Catalog Plugin Class
 */
class Parfume_Catalog {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core includes
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-admin.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-meta-fields.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'includes/class-template-loader.php';
        
        // Modules
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'modules/class-stores.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'modules/class-scraper.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'modules/class-comparison.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'modules/class-comments.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'modules/class-filters.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'modules/class-schema.php';
        require_once PARFUME_CATALOG_PLUGIN_DIR . 'modules/class-blog.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core classes
        new Parfume_Catalog_Admin();
        new Parfume_Catalog_Post_Types();
        new Parfume_Catalog_Meta_Fields();
        new Parfume_Catalog_Template_Loader();
        
        // Initialize modules
        new Parfume_Catalog_Stores();
        new Parfume_Catalog_Scraper();
        new Parfume_Catalog_Comparison();
        new Parfume_Catalog_Comments();
        new Parfume_Catalog_Filters();
        new Parfume_Catalog_Schema();
        new Parfume_Catalog_Blog();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'parfume-catalog',
            false,
            dirname(PARFUME_CATALOG_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'parfume-catalog-frontend',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
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
        
        // Localize script for AJAX
        wp_localize_script('parfume-catalog-frontend', 'parfume_catalog_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_catalog_nonce'),
            'strings' => array(
                'added_to_comparison' => __('Добавен за сравнение', 'parfume-catalog'),
                'removed_from_comparison' => __('Премахнат от сравнение', 'parfume-catalog'),
                'max_comparison_reached' => __('Достигнат е максималният брой за сравнение', 'parfume-catalog'),
                'comparison_min_items' => __('Необходими са поне 2 парфюма за сравнение', 'parfume-catalog'),
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
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
        
        wp_enqueue_script(
            'parfume-catalog-scraper',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/scraper.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );
        
        // Localize script for admin AJAX
        wp_localize_script('parfume-catalog-admin', 'parfume_catalog_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_catalog_admin_nonce'),
            'strings' => array(
                'scraping' => __('Скрейпване...', 'parfume-catalog'),
                'scrape_success' => __('Скрейпването завърши успешно', 'parfume-catalog'),
                'scrape_error' => __('Грешка при скрейпване', 'parfume-catalog'),
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете?', 'parfume-catalog'),
            )
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        $default_options = array(
            'archive_slug' => 'parfiumi',
            'tip_slug' => 'parfiumi',
            'vid_aromat_slug' => 'parfiumi',
            'marki_slug' => 'parfiumi/marki',
            'sezon_slug' => 'parfiumi/season',
            'intenzivnost_slug' => 'parfiumi/intenzivnost',
            'notki_slug' => 'notes',
            'scrape_interval' => 12,
            'batch_size' => 10,
            'max_comparison_items' => 4,
            'enable_comparison' => true,
            'enable_comments' => true,
            'enable_mobile_fixed_panel' => true,
            'similar_perfumes_count' => 4,
            'recently_viewed_count' => 4,
            'same_brand_count' => 4,
        );
        
        add_option('parfume_catalog_options', $default_options);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create database tables if needed
        $this->create_tables();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for scraped data
        $table_scraped_data = $wpdb->prefix . 'parfume_scraped_data';
        $sql_scraped = "CREATE TABLE $table_scraped_data (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            store_id mediumint(9) NOT NULL,
            product_url text NOT NULL,
            price decimal(10,2) DEFAULT NULL,
            old_price decimal(10,2) DEFAULT NULL,
            currency varchar(10) DEFAULT 'лв.',
            ml_variants text DEFAULT NULL,
            availability varchar(255) DEFAULT NULL,
            delivery_info text DEFAULT NULL,
            last_scraped datetime DEFAULT NULL,
            next_scrape datetime DEFAULT NULL,
            scrape_status varchar(50) DEFAULT 'pending',
            error_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY store_id (store_id),
            KEY scrape_status (scrape_status)
        ) $charset_collate;";
        
        // Table for comments/reviews
        $table_comments = $wpdb->prefix . 'parfume_comments';
        $sql_comments = "CREATE TABLE $table_comments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            author_name varchar(255) NOT NULL,
            author_ip varchar(100) NOT NULL,
            rating tinyint(1) NOT NULL,
            comment_text text NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY author_ip (author_ip)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_scraped);
        dbDelta($sql_comments);
    }
}

// Initialize the plugin
function parfume_catalog_init() {
    return Parfume_Catalog::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'parfume_catalog_init');