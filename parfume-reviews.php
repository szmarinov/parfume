<?php
/**
 * Plugin Name: Parfume Reviews
 * Plugin URI: https://example.com/parfume-reviews
 * Description: Advanced perfume review system for WordPress with modular architecture
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: parfume-reviews
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Main Plugin Class
 * Handles plugin initialization, requirements checking, and error handling
 */
class Parfume_Reviews_Plugin {
    
    /**
     * Plugin instance
     * @var Parfume_Reviews_Plugin|null
     */
    private static $instance = null;
    
    /**
     * Bootstrap instance
     * @var \Parfume_Reviews\Core\Bootstrap|null
     */
    private $bootstrap = null;
    
    /**
     * Plugin version
     * @var string
     */
    public $version = '1.0.0';
    
    /**
     * Minimum PHP version required
     * @var string
     */
    public $min_php = '7.4';
    
    /**
     * Minimum WordPress version required
     * @var string
     */
    public $min_wp = '5.0';
    
    /**
     * Get plugin instance (Singleton)
     * @return Parfume_Reviews_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Private for singleton
     */
    private function __construct() {
        $this->define_constants();
        $this->setup_hooks();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        // Main constants - check if already defined to prevent conflicts
        if (!defined('PARFUME_REVIEWS_VERSION')) {
            define('PARFUME_REVIEWS_VERSION', $this->version);
        }
        
        if (!defined('PARFUME_REVIEWS_PLUGIN_FILE')) {
            define('PARFUME_REVIEWS_PLUGIN_FILE', __FILE__);
        }
        
        if (!defined('PARFUME_REVIEWS_PLUGIN_DIR')) {
            define('PARFUME_REVIEWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
        }
        
        if (!defined('PARFUME_REVIEWS_PLUGIN_URL')) {
            define('PARFUME_REVIEWS_PLUGIN_URL', plugin_dir_url(__FILE__));
        }
        
        if (!defined('PARFUME_REVIEWS_PLUGIN_BASENAME')) {
            define('PARFUME_REVIEWS_PLUGIN_BASENAME', plugin_basename(__FILE__));
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Check requirements before doing anything
        register_activation_hook(__FILE__, array($this, 'check_requirements_on_activation'));
        
        // Initialize plugin after WordPress loads
        add_action('plugins_loaded', array($this, 'plugins_loaded'), 10);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Plugin loaded hook
        add_action('parfume_reviews_loaded', array($this, 'on_plugin_loaded'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Check requirements when activating
     */
    public function check_requirements_on_activation() {
        if (!$this->check_requirements()) {
            // Deactivate plugin immediately
            deactivate_plugins(plugin_basename(__FILE__));
            
            $message = sprintf(
                __('Parfume Reviews requires PHP %s+ and WordPress %s+. Your versions: PHP %s, WordPress %s', 'parfume-reviews'),
                $this->min_php,
                $this->min_wp,
                PHP_VERSION,
                get_bloginfo('version')
            );
            
            wp_die($message, __('Plugin Activation Error', 'parfume-reviews'), array('back_link' => true));
        }
    }
    
    /**
     * Check system requirements
     * @return bool
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, $this->min_php, '<')) {
            return false;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), $this->min_wp, '<')) {
            return false;
        }
        
        // Check if required functions exist
        if (!function_exists('register_post_type')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize plugin after WordPress loads
     */
    public function plugins_loaded() {
        // Check requirements
        if (!$this->check_requirements()) {
            add_action('admin_notices', array($this, 'requirements_notice'));
            return;
        }
        
        // Load text domain
        $this->load_textdomain();
        
        // Load dependencies
        $this->load_dependencies();
        
        // Fire plugin loaded action
        do_action('parfume_reviews_loaded');
    }
    
    /**
     * Load plugin text domain for translations
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'parfume-reviews',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Load required files and dependencies
     */
    private function load_dependencies() {
        try {
            // Load core files first
            $this->load_core_files();
            
            // Initialize bootstrap using singleton pattern
            $this->bootstrap = \Parfume_Reviews\Core\Bootstrap::get_instance();
            
        } catch (Exception $e) {
            // Log the error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews Plugin Error: ' . $e->getMessage());
            }
            
            // Show admin notice
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Parfume Reviews Error:</strong> ' . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Load core files in correct order
     */
    private function load_core_files() {
        $core_files = array(
            'core/constants.php',
            'core/autoloader.php',
            'utils/base-classes.php',
            'utils/helpers.php',
            'core/bootstrap.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
            
            if (!file_exists($file_path)) {
                throw new Exception("Required file missing: {$file}");
            }
            
            require_once $file_path;
        }
        
        // Initialize autoloader
        if (class_exists('\Parfume_Reviews\Core\Autoloader')) {
            \Parfume_Reviews\Core\Autoloader::register();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate($network_wide = false) {
        try {
            // Ensure bootstrap is loaded
            if (null === $this->bootstrap && class_exists('\Parfume_Reviews\Core\Bootstrap')) {
                $this->bootstrap = \Parfume_Reviews\Core\Bootstrap::get_instance();
            }
            
            // Call bootstrap activation
            if ($this->bootstrap && method_exists($this->bootstrap, 'activate')) {
                $this->bootstrap->activate();
            }
            
            // Set activation flag
            update_option('parfume_reviews_activated', true);
            update_option('parfume_reviews_version', $this->version);
            
        } catch (Exception $e) {
            // Log activation error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews Activation Error: ' . $e->getMessage());
            }
            
            // Don't let activation fail completely, but flag the issue
            update_option('parfume_reviews_activation_error', $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        try {
            // Call bootstrap deactivation
            if ($this->bootstrap && method_exists($this->bootstrap, 'deactivate')) {
                $this->bootstrap->deactivate();
            }
            
            // Clear activation flag
            delete_option('parfume_reviews_activated');
            
        } catch (Exception $e) {
            // Log deactivation error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews Deactivation Error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Called when plugin is fully loaded
     */
    public function on_plugin_loaded() {
        // Plugin is ready - can be used by other plugins/themes
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews Plugin fully loaded and ready.');
        }
    }
    
    /**
     * Show admin notices
     */
    public function admin_notices() {
        // Show activation error if any
        $activation_error = get_option('parfume_reviews_activation_error');
        if ($activation_error) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo '<strong>Parfume Reviews Warning:</strong> ' . esc_html($activation_error);
            echo '</p></div>';
            delete_option('parfume_reviews_activation_error');
        }
    }
    
    /**
     * Show requirements notice
     */
    public function requirements_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Parfume Reviews:</strong> ';
        printf(
            __('This plugin requires PHP %s+ and WordPress %s+. Your versions: PHP %s, WordPress %s', 'parfume-reviews'),
            $this->min_php,
            $this->min_wp,
            PHP_VERSION,
            get_bloginfo('version')
        );
        echo '</p></div>';
    }
    
    /**
     * Get bootstrap instance
     * @return \Parfume_Reviews\Core\Bootstrap|null
     */
    public function get_bootstrap() {
        return $this->bootstrap;
    }
    
    /**
     * Get plugin version
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Check if plugin is active and working
     * @return bool
     */
    public function is_active() {
        return (null !== $this->bootstrap && get_option('parfume_reviews_activated', false));
    }
}

/**
 * Returns the main instance of Parfume_Reviews_Plugin
 * @return Parfume_Reviews_Plugin
 */
function parfume_reviews() {
    return Parfume_Reviews_Plugin::get_instance();
}

/**
 * Global backward compatibility functions
 */
if (!function_exists('is_parfume_reviews_active')) {
    function is_parfume_reviews_active() {
        return parfume_reviews()->is_active();
    }
}

if (!function_exists('parfume_reviews_version')) {
    function parfume_reviews_version() {
        return parfume_reviews()->get_version();
    }
}

// Initialize the plugin
parfume_reviews();