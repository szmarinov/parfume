<?php
/**
 * Main Plugin Class
 * 
 * Orchestrates the entire plugin lifecycle and manages all components
 * 
 * @package ParfumeReviews
 * @subpackage Core
 * @since 2.0.0
 */

namespace ParfumeReviews\Core;

use ParfumeReviews\PostTypes\Parfume\PostType;
use ParfumeReviews\Taxonomies\TaxonomyManager;
use ParfumeReviews\Templates\Loader as TemplateLoader;
use ParfumeReviews\Admin\Settings\SettingsManager;
use ParfumeReviews\Features\Comparison\Comparison;
use ParfumeReviews\Features\ImportExport\ImportExport;
use ParfumeReviews\Features\Scraper\Scraper;
use ParfumeReviews\Features\Scraper\ScraperMonitor;
use ParfumeReviews\Features\Scraper\ScraperCron;
use ParfumeReviews\Features\Scraper\ScraperTestTool;
use ParfumeReviews\Features\Stores\StoreManager;
use ParfumeReviews\Features\Stores\StoreSchema;
use ParfumeReviews\Features\Stores\StoreRepository;
use ParfumeReviews\Features\Filters\FiltersHandler;

/**
 * Main Plugin Class
 * 
 * Singleton pattern implementation
 */
class Plugin {
    
    /**
     * Plugin instance
     * 
     * @var Plugin
     */
    private static $instance = null;
    
    /**
     * Dependency Injection Container
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Hook Loader
     * 
     * @var Loader
     */
    private $loader;
    
    /**
     * Registered service providers
     * 
     * @var array
     */
    private $providers = [];
    
    /**
     * Plugin version
     * 
     * @var string
     */
    private $version;
    
    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        $this->version = PARFUME_REVIEWS_VERSION;
        $this->container = new Container();
        $this->loader = new Loader();
        
        $this->load_dependencies();
        $this->register_providers();
    }
    
    /**
     * Get plugin instance
     * 
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load configuration files
        $this->load_config('taxonomies');
        $this->load_config('post-types');
        $this->load_config('settings');
    }
    
    /**
     * Load configuration file
     * 
     * @param string $config Config file name (without .php)
     */
    private function load_config($config) {
        $config_file = PARFUME_REVIEWS_PATH . 'config/' . $config . '.php';
        
        if (file_exists($config_file)) {
            $data = require $config_file;
            $this->container->set('config.' . $config, $data);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Config loaded - ' . $config);
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Config file not found - ' . $config_file);
            }
        }
    }
    
    /**
     * Register service providers
     */
    private function register_providers() {
        // Core components
        $this->register_core_components();
        
        // Post types
        $this->register_post_types();
        
        // Taxonomies
        $this->register_taxonomies();
        
        // Templates
        $this->register_templates();
        
        // Admin
        $this->register_admin();
        
        // Features
        $this->register_features();
        
        // Frontend
        $this->register_frontend();
    }
    
    /**
     * Register core components
     */
    private function register_core_components() {
        // Flush rewrite rules if needed
        $this->loader->add_action('init', $this, 'maybe_flush_rewrite_rules', 999);
        
        // Load text domain
        $this->loader->add_action('init', $this, 'load_textdomain');
    }
    
    /**
     * Register post types
     */
    private function register_post_types() {
        $post_type = new PostType($this->container);
        $this->container->set('post_type.parfume', $post_type);
        
        // Register hooks for post type
        $this->loader->add_action('init', $post_type, 'register', 10);
        $this->loader->add_action('add_meta_boxes', $post_type, 'add_meta_boxes');
        $this->loader->add_action('save_post_parfume', $post_type, 'save_meta_boxes', 10, 2);
    }
    
    /**
     * Register taxonomies
     */
    private function register_taxonomies() {
        $taxonomy_manager = new TaxonomyManager($this->container);
        $this->container->set('taxonomies', $taxonomy_manager);
        
        // Register hooks for taxonomies
        $this->loader->add_action('init', $taxonomy_manager, 'register', 5);
        $this->loader->add_action('parse_request', $taxonomy_manager, 'handle_rewrite', 1);
        $this->loader->add_filter('template_include', $taxonomy_manager, 'load_templates');
    }
    
    /**
     * Register templates
     */
    private function register_templates() {
        $template_loader = new TemplateLoader($this->container);
        $this->container->set('templates', $template_loader);
        
        // Register hooks for templates
        $this->loader->add_filter('template_include', $template_loader, 'load_template');
        $this->loader->add_action('wp_enqueue_scripts', $template_loader, 'enqueue_assets');
        
        // Single template override
        $this->loader->add_filter('single_template', $this, 'load_single_template');
    }
    
    /**
     * Register admin functionality
     */
    private function register_admin() {
        if (!is_admin()) {
            return;
        }
        
        // Settings manager
        $settings = new SettingsManager($this->container);
        $this->container->set('settings', $settings);
        
        // Register hooks for settings
        $this->loader->add_action('admin_menu', $settings, 'add_menu');
        $this->loader->add_action('admin_init', $settings, 'register_settings');
        $this->loader->add_action('admin_enqueue_scripts', $settings, 'enqueue_assets');
        
        // Admin stores assets
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_stores_assets');
    }
    
    /**
     * Register features
     */
    private function register_features() {
        // Store Management System
        $store_manager = StoreManager::get_instance();
        $this->container->set('features.store_manager', $store_manager);
        
        // Store Schema
        $store_schema = new StoreSchema();
        $this->container->set('features.store_schema', $store_schema);
        
        // Store Repository
        $store_repository = new StoreRepository();
        $this->container->set('features.store_repository', $store_repository);
        
        // Scraper with dependencies
        $scraper = new Scraper($this->container);
        $this->container->set('features.scraper', $scraper);
        
        if (is_admin()) {
            // Scraper Monitor (Admin only)
            $scraper_monitor = new ScraperMonitor($this->container);
            $this->container->set('features.scraper_monitor', $scraper_monitor);
            $this->loader->add_action('admin_menu', $this, 'add_scraper_monitor_page');
            
            // Scraper Test Tool (Admin only)
            $scraper_test_tool = new ScraperTestTool($this->container);
            $this->container->set('features.scraper_test_tool', $scraper_test_tool);
            $this->loader->add_action('admin_menu', $this, 'add_scraper_test_tool_page');
            
            // AJAX handlers for scraper
            $this->loader->add_action('wp_ajax_parfume_scrape_product', $scraper, 'ajax_scrape');
            $this->loader->add_action('wp_ajax_parfume_update_price', $scraper, 'ajax_update_price');
            
            // AJAX handlers for test tool
            $this->loader->add_action('wp_ajax_parfume_test_scrape', $scraper_test_tool, 'ajax_test_scrape');
            $this->loader->add_action('wp_ajax_parfume_test_selector', $scraper_test_tool, 'ajax_test_selector');
            $this->loader->add_action('wp_ajax_parfume_save_test_schema', $scraper_test_tool, 'ajax_save_schema');
        }
        
        // Scraper Cron
        $scraper_cron = new ScraperCron($this->container);
        $this->container->set('features.scraper_cron', $scraper_cron);
        
        // Comparison feature
        $comparison = new Comparison($this->container);
        $this->container->set('features.comparison', $comparison);
        $this->loader->add_action('init', $comparison, 'init');
        
        // Import/Export feature
        $import_export = new ImportExport($this->container);
        $this->container->set('features.import_export', $import_export);
        
        if (is_admin()) {
            $this->loader->add_action('admin_menu', $import_export, 'add_menu');
            $this->loader->add_action('admin_post_parfume_import', $import_export, 'handle_import');
            $this->loader->add_action('admin_post_parfume_export', $import_export, 'handle_export');
        }
        
        // Filters feature
        $filters = new FiltersHandler($this->container);
        $this->container->set('features.filters', $filters);
        $this->loader->add_action('init', $filters, 'init');
    }
    
    /**
     * Register frontend functionality
     */
    private function register_frontend() {
        // Shortcodes
        $this->loader->add_action('init', $this, 'register_shortcodes');
        
        // Frontend assets
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_frontend_assets');
        
        // AJAX handlers for frontend
        $this->register_ajax_handlers();
    }
    
    /**
     * Add Scraper Monitor admin page
     */
    public function add_scraper_monitor_page() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Scraper Monitor', 'parfume-reviews'),
            __('Scraper Monitor', 'parfume-reviews'),
            'manage_options',
            'parfume-scraper-monitor',
            [$this->container->get('features.scraper_monitor'), 'render_page']
        );
    }
    
    /**
     * Add Scraper Test Tool admin page
     */
    public function add_scraper_test_tool_page() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Scraper Test Tool', 'parfume-reviews'),
            __('Test Tool', 'parfume-reviews'),
            'manage_options',
            'parfume-scraper-test',
            [$this->container->get('features.scraper_test_tool'), 'render_page']
        );
    }
    
    /**
     * Enqueue admin stores assets
     */
    public function enqueue_admin_stores_assets($hook) {
        // Only on post editor pages
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        
        if (!$post || 'parfume' !== $post->post_type) {
            return;
        }
        
        // Admin stores CSS
        wp_enqueue_style(
            'parfume-admin-stores',
            PARFUME_REVIEWS_URL . 'assets/css/admin-stores.css',
            [],
            $this->version
        );
        
        // Admin stores JS
        wp_enqueue_script(
            'parfume-admin-stores',
            PARFUME_REVIEWS_URL . 'assets/js/admin-stores.js',
            ['jquery', 'jquery-ui-sortable'],
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script('parfume-admin-stores', 'parfumeAdminStores', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_stores'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to remove this store?', 'parfume-reviews'),
                'scraping' => __('Scraping...', 'parfume-reviews'),
                'scrape_success' => __('Product data scraped successfully!', 'parfume-reviews'),
                'scrape_error' => __('Failed to scrape product data.', 'parfume-reviews'),
            ]
        ]);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only on single parfume pages
        if (!is_singular('parfume')) {
            return;
        }

        // Stores Column CSS
        wp_enqueue_style(
            'parfume-stores-column',
            PARFUME_REVIEWS_URL . 'assets/css/stores-column.css',
            [],
            $this->version
        );

        // Mobile Fixed Panel CSS
        wp_enqueue_style(
            'parfume-mobile-panel',
            PARFUME_REVIEWS_URL . 'assets/css/mobile-fixed-panel.css',
            ['parfume-stores-column'],
            $this->version
        );

        // Stores Frontend JS
        wp_enqueue_script(
            'parfume-stores-frontend',
            PARFUME_REVIEWS_URL . 'assets/js/stores-frontend.js',
            ['jquery'],
            $this->version,
            true
        );

        // Mobile Panel JS
        wp_enqueue_script(
            'parfume-mobile-panel',
            PARFUME_REVIEWS_URL . 'assets/js/mobile-panel.js',
            ['jquery', 'parfume-stores-frontend'],
            $this->version,
            true
        );

        // Localize mobile settings from WordPress options
        $mobile_settings = get_option('parfume_reviews_mobile', []);
        
        wp_localize_script('parfume-mobile-panel', 'parfumeMobileSettings', [
            'enabled' => isset($mobile_settings['enabled']) ? $mobile_settings['enabled'] : true,
            'zIndex' => isset($mobile_settings['z_index']) ? intval($mobile_settings['z_index']) : 9999,
            'offset' => isset($mobile_settings['offset']) ? intval($mobile_settings['offset']) : 0,
            'showCloseButton' => isset($mobile_settings['show_close_button']) ? $mobile_settings['show_close_button'] : true,
            'breakpoint' => isset($mobile_settings['breakpoint']) ? intval($mobile_settings['breakpoint']) : 768,
        ]);
    }
    
    /**
     * Load single template
     */
    public function load_single_template($template) {
        global $post;

        if ('parfume' === $post->post_type) {
            $plugin_template = PARFUME_REVIEWS_PATH . 'templates/single-parfume.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Frontend AJAX handlers (both logged in and logged out users)
        $this->loader->add_action('wp_ajax_parfume_add_to_comparison', $this, 'ajax_add_to_comparison');
        $this->loader->add_action('wp_ajax_nopriv_parfume_add_to_comparison', $this, 'ajax_add_to_comparison');
        
        $this->loader->add_action('wp_ajax_parfume_remove_from_comparison', $this, 'ajax_remove_from_comparison');
        $this->loader->add_action('wp_ajax_nopriv_parfume_remove_from_comparison', $this, 'ajax_remove_from_comparison');
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('parfume_comparison', [$this, 'render_comparison_shortcode']);
        add_shortcode('parfume_filters', [$this, 'render_filters_shortcode']);
    }
    
    /**
     * Render comparison shortcode
     */
    public function render_comparison_shortcode($atts) {
        $comparison = $this->container->get('features.comparison');
        return $comparison->render_comparison_table($atts);
    }
    
    /**
     * Render filters shortcode
     */
    public function render_filters_shortcode($atts) {
        $filters = $this->container->get('features.filters');
        return $filters->render_filters_form($atts);
    }
    
    /**
     * AJAX: Add to comparison
     */
    public function ajax_add_to_comparison() {
        check_ajax_referer('parfume_comparison', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid product ID', 'parfume-reviews')]);
        }
        
        $comparison = $this->container->get('features.comparison');
        $result = $comparison->add_to_comparison($post_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Added to comparison', 'parfume-reviews')]);
        } else {
            wp_send_json_error(['message' => __('Failed to add to comparison', 'parfume-reviews')]);
        }
    }
    
    /**
     * AJAX: Remove from comparison
     */
    public function ajax_remove_from_comparison() {
        check_ajax_referer('parfume_comparison', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid product ID', 'parfume-reviews')]);
        }
        
        $comparison = $this->container->get('features.comparison');
        $result = $comparison->remove_from_comparison($post_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Removed from comparison', 'parfume-reviews')]);
        } else {
            wp_send_json_error(['message' => __('Failed to remove from comparison', 'parfume-reviews')]);
        }
    }
    
    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        $version_option = 'parfume_reviews_version';
        $current_version = get_option($version_option);
        
        if ($current_version !== $this->version) {
            flush_rewrite_rules();
            update_option($version_option, $this->version);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Rewrite rules flushed - Version ' . $this->version);
            }
        }
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'parfume-reviews',
            false,
            dirname(PARFUME_REVIEWS_BASENAME) . '/languages'
        );
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Get container instance
     * 
     * @return Container
     */
    public function get_container() {
        return $this->container;
    }
    
    /**
     * Get version
     * 
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
    
}