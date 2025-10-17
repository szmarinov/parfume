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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ParfumeReviews\Core\Plugin: Constructor completed successfully');
        }
    }
    
    /**
     * Get plugin instance
     * 
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ParfumeReviews\Core\Plugin: Instance created');
            }
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
        
        // For now, we'll skip other registrations until we confirm Plugin class loads
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Service providers registered');
        }
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Plugin running');
        }
    }
    
    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('parfume_reviews_activated')) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_activated');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Rewrite rules flushed');
            }
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