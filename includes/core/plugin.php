<?php
/**
 * Main Plugin Class
 * 
 * Orchestrates the entire plugin lifecycle and manages all components
 * 
 * @package Parfume_Reviews
 * @subpackage Core
 * @since 2.0.0
 */

namespace Parfume_Reviews\Core;

use Parfume_Reviews\PostTypes\Parfume\PostType;
use Parfume_Reviews\Taxonomies\TaxonomyManager;
use Parfume_Reviews\Templates\Loader as TemplateLoader;
use Parfume_Reviews\Admin\Settings\SettingsManager;
use Parfume_Reviews\Features\Comparison\Comparison;
use Parfume_Reviews\Features\ImportExport\ImportExport;
use Parfume_Reviews\Features\Scraper\Scraper;
use Parfume_Reviews\Features\Scraper\ScraperMonitor;
use Parfume_Reviews\Features\Scraper\ScraperCron;
use Parfume_Reviews\Features\Scraper\ScraperTestTool;
use Parfume_Reviews\Features\Stores\StoreManager;
use Parfume_Reviews\Features\Stores\StoreSchema;
use Parfume_Reviews\Features\Stores\StoreRepository;
use Parfume_Reviews\Features\Filters\FiltersHandler;

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
        // Check if PostType class exists
        if (!class_exists('Parfume_Reviews\\PostTypes\\Parfume\\PostType')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: PostType class not found');
            }
            return;
        }
        
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
        // Check if TaxonomyManager class exists
        if (!class_exists('Parfume_Reviews\\Taxonomies\\TaxonomyManager')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: TaxonomyManager class not found');
            }
            return;
        }
        
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
        // Check if TemplateLoader class exists
        if (!class_exists('Parfume_Reviews\\Templates\\Loader')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: TemplateLoader class not found');
            }
            return;
        }
        
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
        
        // Check if SettingsManager class exists
        if (!class_exists('Parfume_Reviews\\Admin\\Settings\\SettingsManager')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: SettingsManager class not found');
            }
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
     * Get container
     * 
     * @return Container
     */
    public function get_container() {
        return $this->container;
    }
    
    /**
     * Get loader
     * 
     * @return Loader
     */
    public function get_loader() {
        return $this->loader;
    }
    
    /**
     * Get version
     * 
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('parfume_reviews_activated')) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_activated');
        }
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'parfume-reviews',
            false,
            dirname(PARFUME_REVIEWS_BASENAME) . '/languages'
        );
    }
}