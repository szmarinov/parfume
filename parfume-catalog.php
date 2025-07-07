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
        Parfume_Catalog_Admin::get_instance();
        Parfume_Catalog_Post_Types::get_instance();
        Parfume_Catalog_Meta_Fields::get_instance();
        Parfume_Catalog_Template_Loader::get_instance();
        
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
        wp_localize_script('parfume-catalog-frontend', 'pc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pc_nonce'),
            'strings' => array(
                'loading' => __('Зареждане...', 'parfume-catalog'),
                'error' => __('Възникна грешка', 'parfume-catalog'),
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете това?', 'parfume-catalog'),
                'added_to_compare' => __('Добавено за сравнение', 'parfume-catalog'),
                'removed_from_compare' => __('Премахнато от сравнение', 'parfume-catalog'),
                'max_compare_reached' => __('Достигнат е максималният брой за сравнение', 'parfume-catalog')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Load only on our admin pages
        if (strpos($hook, 'parfume-catalog') === false && 
            strpos($hook, 'parfumes') === false && 
            strpos($hook, 'parfume_blog') === false) {
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
        
        wp_enqueue_script(
            'parfume-catalog-scraper',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/scraper.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );
        
        // WordPress media uploader
        wp_enqueue_media();
        
        // Localize admin script
        wp_localize_script('parfume-catalog-admin', 'pc_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pc_nonce'),
            'strings' => array(
                'loading' => __('Зареждане...', 'parfume-catalog'),
                'saved' => __('Запазено успешно', 'parfume-catalog'),
                'error' => __('Възникна грешка', 'parfume-catalog'),
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете това?', 'parfume-catalog')
            )
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        $this->flush_rewrite_rules();
        
        // Set activation flag
        update_option('parfume_catalog_activation_date', current_time('mysql'));
        update_option('parfume_catalog_version', PARFUME_CATALOG_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('pc_scraper_cron');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Comments table
        $comments_table = $wpdb->prefix . 'parfume_comments';
        $comments_sql = "CREATE TABLE $comments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100) DEFAULT '',
            author_ip varchar(100) NOT NULL,
            comment_content text NOT NULL,
            rating tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($comments_sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_settings = array(
            // URLs
            'archive_slug' => 'parfiumi',
            'tip_slug' => 'parfiumi',
            'vid_aromat_slug' => 'parfiumi',
            'marki_slug' => 'parfiumi/marki',
            'sezon_slug' => 'parfiumi/season',
            'notki_slug' => 'notes',
            'blog_slug' => 'blog',
            
            // Scraper
            'scrape_interval' => 12,
            'scrape_batch_size' => 10,
            'scrape_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'scrape_timeout' => 30,
            
            // Comments
            'comments_moderation' => true,
            'comments_notification_email' => get_option('admin_email'),
            'comments_max_per_ip' => 5,
            'comments_blocked_words' => '',
            
            // Comparison
            'comparison_enabled' => true,
            'comparison_max_items' => 4,
            
            // Mobile
            'mobile_fixed_panel' => true,
            'mobile_show_x_button' => true,
            'mobile_z_index' => 1000,
            'mobile_offset' => 0,
            
            // Similar products
            'similar_count' => 4,
            'similar_columns' => 4,
            'recently_viewed_count' => 4,
            'same_brand_count' => 4,
            
            // SEO
            'seo_schema' => true,
            'seo_og_tags' => true,
            'seo_twitter_cards' => true,
            
            // Blog
            'blog_enabled' => true
        );
        
        add_option('parfume_catalog_settings', $default_settings);
        
        // Initialize stores
        $default_stores = array(
            array(
                'id' => 1,
                'name' => 'Parfium.bg',
                'logo' => '',
                'url' => 'https://parfium.bg',
                'description' => 'Онлайн магазин за парфюми'
            )
        );
        
        add_option('parfume_catalog_stores', $default_stores);
    }
    
    /**
     * Flush rewrite rules
     */
    private function flush_rewrite_rules() {
        // Initialize post types and taxonomies temporarily
        if (class_exists('Parfume_Catalog_Post_Types')) {
            Parfume_Catalog_Post_Types::get_instance();
        }
        
        if (class_exists('Parfume_Catalog_Blog')) {
            Parfume_Catalog_Blog::flush_rewrite_rules();
        }
        
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function parfume_catalog_init() {
    return Parfume_Catalog::get_instance();
}

// Start the plugin
parfume_catalog_init();