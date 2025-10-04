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
    }
    
    /**
     * Register admin functionality
     */
    private function register_admin() {
        if (!is_admin()) {
            return;
        }
        
        $settings = new SettingsManager($this->container);
        $this->container->set('settings', $settings);
        
        // Register hooks for settings
        $this->loader->add_action('admin_menu', $settings, 'add_menu');
        $this->loader->add_action('admin_init', $settings, 'register_settings');
        $this->loader->add_action('admin_enqueue_scripts', $settings, 'enqueue_assets');
    }
    
    /**
     * Register features
     */
    private function register_features() {
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
        
        // Scraper feature
        $scraper = new Scraper($this->container);
        $this->container->set('features.scraper', $scraper);
        
        if (is_admin()) {
            $this->loader->add_action('wp_ajax_parfume_scrape_product', $scraper, 'ajax_scrape');
            $this->loader->add_action('wp_ajax_parfume_update_price', $scraper, 'ajax_update_price');
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
        
        // AJAX handlers for frontend
        $this->register_ajax_handlers();
        
        // Enqueue frontend assets
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_frontend_assets');
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Main shortcodes will be registered here
        // Example: add_shortcode('parfume_grid', [$this, 'shortcode_parfume_grid']);
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Add to comparison (logged in and non-logged in)
        $this->loader->add_action('wp_ajax_add_to_comparison', $this, 'ajax_add_to_comparison');
        $this->loader->add_action('wp_ajax_nopriv_add_to_comparison', $this, 'ajax_add_to_comparison');
        
        // Remove from comparison
        $this->loader->add_action('wp_ajax_remove_from_comparison', $this, 'ajax_remove_from_comparison');
        $this->loader->add_action('wp_ajax_nopriv_remove_from_comparison', $this, 'ajax_remove_from_comparison');
        
        // Get comparison data
        $this->loader->add_action('wp_ajax_get_comparison_data', $this, 'ajax_get_comparison_data');
        $this->loader->add_action('wp_ajax_nopriv_get_comparison_data', $this, 'ajax_get_comparison_data');
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only on parfume pages
        if (!$this->is_parfume_page()) {
            return;
        }
        
        // Main CSS
        wp_enqueue_style(
            'parfume-reviews-main',
            PARFUME_REVIEWS_URL . 'assets/css/main.css',
            [],
            $this->version
        );
        
        // Comparison Lightbox CSS
        wp_enqueue_style(
            'parfume-reviews-comparison-lightbox',
            PARFUME_REVIEWS_URL . 'assets/css/comparison-lightbox.css',
            ['parfume-reviews-main'],
            $this->version
        );
        
        // Main JavaScript
        wp_enqueue_script(
            'parfume-reviews-main',
            PARFUME_REVIEWS_URL . 'assets/js/main.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Comparison Lightbox JavaScript
        wp_enqueue_script(
            'parfume-reviews-comparison-lightbox',
            PARFUME_REVIEWS_URL . 'assets/js/comparison-lightbox.js',
            ['jquery', 'parfume-reviews-main'],
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script('parfume-reviews-main', 'parfumeReviews', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_reviews_nonce')
        ]);
    }
    
    /**
     * Run the loader to register hooks with WordPress
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Get the plugin version
     * 
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Get the dependency injection container
     * 
     * @return Container
     */
    public function get_container() {
        return $this->container;
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'parfume-reviews',
            false,
            dirname(PARFUME_REVIEWS_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('parfume_reviews_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_flush_rewrite_rules');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Rewrite rules flushed');
            }
        }
    }
    
    /**
     * Check if current page is parfume related
     * 
     * @return bool
     */
    private function is_parfume_page() {
        if (!did_action('wp')) {
            return false;
        }
        
        return is_singular('parfume') || 
               is_post_type_archive('parfume') || 
               $this->is_parfume_taxonomy();
    }
    
    /**
     * Check if current page is parfume taxonomy
     * 
     * @return bool
     */
    private function is_parfume_taxonomy() {
        if (!did_action('parse_query')) {
            return false;
        }
        
        $parfume_taxonomies = ['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'];
        
        foreach ($parfume_taxonomies as $taxonomy) {
            if (is_tax($taxonomy)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * AJAX: Add to comparison
     */
    public function ajax_add_to_comparison() {
        // Implementation handled by Comparison feature
        if ($this->container->has('features.comparison')) {
            $comparison = $this->container->get('features.comparison');
            // Forward to comparison handler
        }
    }
    
    /**
     * AJAX: Remove from comparison
     */
    public function ajax_remove_from_comparison() {
        // Implementation handled by Comparison feature
        if ($this->container->has('features.comparison')) {
            $comparison = $this->container->get('features.comparison');
            // Forward to comparison handler
        }
    }
    
    /**
     * AJAX: Get comparison data
     */
    public function ajax_get_comparison_data() {
        // Implementation handled by Comparison feature
        if ($this->container->has('features.comparison')) {
            $comparison = $this->container->get('features.comparison');
            // Forward to comparison handler
        }
    }
}