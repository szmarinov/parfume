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
        Parfume_Catalog_Stores::get_instance();
        Parfume_Catalog_Scraper::get_instance();
        Parfume_Catalog_Comparison::get_instance();
        Parfume_Catalog_Comments::get_instance();
        Parfume_Catalog_Filters::get_instance();
        Parfume_Catalog_Schema::get_instance();
        Parfume_Catalog_Blog::get_instance();
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
                'added_to_comparison' => __('Добавен за сравнение', 'parfume-catalog'),
                'removed_from_comparison' => __('Премахнат от сравнение', 'parfume-catalog'),
                'max_comparison_reached' => __('Достигнат е максималният брой парфюми за сравнение', 'parfume-catalog'),
                'copied_to_clipboard' => __('Копирано в клипборда', 'parfume-catalog')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PARFUME_CATALOG_VERSION
        );
        
        wp_enqueue_script(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
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
        wp_localize_script('parfume-catalog-admin', 'pc_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pc_admin_nonce'),
            'strings' => array(
                'saving' => __('Запазване...', 'parfume-catalog'),
                'saved' => __('Запазено', 'parfume-catalog'),
                'error' => __('Възникна грешка', 'parfume-catalog'),
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете това?', 'parfume-catalog'),
                'scraping' => __('Скрейпване...', 'parfume-catalog'),
                'scraped' => __('Успешно скрейпнато', 'parfume-catalog'),
                'scrape_error' => __('Грешка при скрейпване', 'parfume-catalog')
            )
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $default_options = array(
            'archive_slug' => 'parfiumi',
            'scrape_interval' => 12,
            'scrape_batch_size' => 10,
            'max_comparison_items' => 4,
            'comparison_enabled' => true,
            'comments_enabled' => true,
            'comments_moderation' => true,
            'mobile_fixed_panel' => true,
            'show_close_button' => true
        );
        
        $existing_options = get_option('parfume_catalog_settings', array());
        $merged_options = array_merge($default_options, $existing_options);
        update_option('parfume_catalog_settings', $merged_options);
        
        // Create default terms
        $this->create_default_terms();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('pc_scraper_cron');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create default terms for taxonomies
     */
    private function create_default_terms() {
        // Тип термини
        $tip_terms = array(
            'Дамски' => 'damski',
            'Мъжки' => 'majki',
            'Унисекс' => 'uniseks',
            'Младежки' => 'mladejki',
            'Възрастни' => 'vazrastni',
            'Луксозни парфюми' => 'luksozni-parfiumi',
            'Нишови парфюми' => 'nishovi-parfiumi',
            'Арабски Парфюми' => 'arabski-parfiumi'
        );
        
        foreach ($tip_terms as $name => $slug) {
            if (!term_exists($name, 'tip')) {
                wp_insert_term($name, 'tip', array('slug' => $slug));
            }
        }
        
        // Вид аромат термини
        $vid_aromat_terms = array(
            'Тоалетна вода' => 'toaletna-voda',
            'Парфюмна вода' => 'parfiumna-voda',
            'Парфюм' => 'parfium',
            'Парфюмен елексир' => 'parfumen-eleksir'
        );
        
        foreach ($vid_aromat_terms as $name => $slug) {
            if (!term_exists($name, 'vid_aromat')) {
                wp_insert_term($name, 'vid_aromat', array('slug' => $slug));
            }
        }
        
        // Сезон термини
        $sezon_terms = array(
            'Пролет' => 'prolet',
            'Лято' => 'liato',
            'Есен' => 'esen',
            'Зима' => 'zima'
        );
        
        foreach ($sezon_terms as $name => $slug) {
            if (!term_exists($name, 'sezon')) {
                wp_insert_term($name, 'sezon', array('slug' => $slug));
            }
        }
        
        // Интензивност термини
        $intenzivnost_terms = array(
            'Силни' => 'silni',
            'Средни' => 'sredni',
            'Леки' => 'leki',
            'Фини/деликатни' => 'fini-delikatni',
            'Интензивни' => 'intenzivni',
            'Пудрени (Powdery)' => 'pudreni-powdery',
            'Тежки/дълбоки (Heavy/Deep)' => 'tejki-dalбoki-heavy-deep'
        );
        
        foreach ($intenzivnost_terms as $name => $slug) {
            if (!term_exists($name, 'intenzivnost')) {
                wp_insert_term($name, 'intenzivnost', array('slug' => $slug));
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function parfume_catalog_init() {
    return Parfume_Catalog::get_instance();
}

// Initialize the plugin
parfume_catalog_init();