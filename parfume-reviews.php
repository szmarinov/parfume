<?php
/**
 * Plugin Name: Parfume Reviews
 * Plugin URI: https://example.com/parfume-reviews
 * Description: A comprehensive perfume review system for WordPress with advanced features
 * Version: 2.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: parfume-reviews
 * Domain Path: /languages
 */

namespace ParfumeReviews;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Constants
 */
define('PARFUME_REVIEWS_VERSION', '2.0.0');
define('PARFUME_REVIEWS_FILE', __FILE__);
define('PARFUME_REVIEWS_PATH', plugin_dir_path(__FILE__));
define('PARFUME_REVIEWS_URL', plugin_dir_url(__FILE__));
define('PARFUME_REVIEWS_BASENAME', plugin_basename(__FILE__));

/**
 * Minimum Requirements
 */
define('PARFUME_REVIEWS_MIN_PHP', '7.4');
define('PARFUME_REVIEWS_MIN_WP', '5.8');

/**
 * Autoloader
 * PSR-4 compatible autoloader for plugin classes
 */
spl_autoload_register(function ($class) {
    // Project namespace prefix (БЕЗ underscore!)
    $prefix = 'ParfumeReviews\\';
    
    // Base directory for the namespace prefix
    $base_dir = PARFUME_REVIEWS_PATH . 'includes/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    // Convert to lowercase for file paths
    $file_path = strtolower(str_replace('\\', '/', $relative_class));
    $file = $base_dir . $file_path . '.php';
    
    // Debug logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Parfume Reviews Autoloader: Trying to load class '$class' from '$file'");
    }
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Parfume Reviews Autoloader: Successfully loaded '$file'");
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Parfume Reviews Autoloader ERROR: File not found - '$file'");
        }
    }
});

/**
 * Load helper functions
 * These are global functions used in templates
 */
$helpers_file = PARFUME_REVIEWS_PATH . 'includes/helpers.php';
if (file_exists($helpers_file)) {
    require_once $helpers_file;
} else {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Parfume Reviews: helpers.php not found at: $helpers_file");
    }
}

/**
 * Check minimum requirements before activation
 */
function check_requirements() {
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, PARFUME_REVIEWS_MIN_PHP, '<')) {
        $errors[] = sprintf(
            __('Parfume Reviews requires PHP %s or higher. You are running PHP %s.', 'parfume-reviews'),
            PARFUME_REVIEWS_MIN_PHP,
            PHP_VERSION
        );
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, PARFUME_REVIEWS_MIN_WP, '<')) {
        $errors[] = sprintf(
            __('Parfume Reviews requires WordPress %s or higher. You are running WordPress %s.', 'parfume-reviews'),
            PARFUME_REVIEWS_MIN_WP,
            $wp_version
        );
    }
    
    // Display errors if any
    if (!empty($errors)) {
        deactivate_plugins(PARFUME_REVIEWS_BASENAME);
        wp_die(
            '<h1>' . __('Plugin Activation Error', 'parfume-reviews') . '</h1>' .
            '<p>' . implode('</p><p>', $errors) . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('&larr; Back to Plugins', 'parfume-reviews') . '</a></p>'
        );
    }
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\check_requirements');

/**
 * Initialize plugin
 */
function init() {
    // Log initialization
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews: Initializing plugin...');
    }
    
    // Check if Plugin class exists
    if (!class_exists('ParfumeReviews\\Core\\Plugin')) {
        // Log detailed error
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews ERROR: Plugin class not found!');
            error_log('Expected class: ParfumeReviews\\Core\\Plugin');
            error_log('Expected file: ' . PARFUME_REVIEWS_PATH . 'includes/core/plugin.php');
            error_log('File exists: ' . (file_exists(PARFUME_REVIEWS_PATH . 'includes/core/plugin.php') ? 'YES' : 'NO'));
            
            // List all PHP files in includes/core/
            $core_dir = PARFUME_REVIEWS_PATH . 'includes/core/';
            if (is_dir($core_dir)) {
                $files = scandir($core_dir);
                error_log('Files in includes/core/: ' . implode(', ', array_filter($files, function($f) { 
                    return pathinfo($f, PATHINFO_EXTENSION) === 'php'; 
                })));
            } else {
                error_log('Directory not found: ' . $core_dir);
            }
        }
        
        // Display admin notice
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>Parfume Reviews Error:</strong> Plugin class not found. Please check the installation.</p>
                <p>Expected file: <code><?php echo PARFUME_REVIEWS_PATH; ?>includes/core/plugin.php</code></p>
                <p>File exists: <strong><?php echo file_exists(PARFUME_REVIEWS_PATH . 'includes/core/plugin.php') ? 'YES' : 'NO'; ?></strong></p>
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                <p><em>Check wp-content/debug.log for more details</em></p>
                <?php endif; ?>
            </div>
            <?php
        });
        
        return;
    }
    
    // Get plugin instance and run
    try {
        $plugin = \ParfumeReviews\Core\Plugin::get_instance();
        $plugin->run();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Plugin initialized successfully');
        }
    } catch (\Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews ERROR: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
        
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><strong>Parfume Reviews Error:</strong> <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

/**
 * Plugin activation
 */
function activate() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews: Activating plugin...');
    }
    
    // Check requirements
    check_requirements();
    
    // Initialize plugin
    if (class_exists('ParfumeReviews\\Core\\Plugin')) {
        try {
            $plugin = \ParfumeReviews\Core\Plugin::get_instance();
            $plugin->run();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Set activation flag
            update_option('parfume_reviews_activated', true);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Plugin activated successfully');
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews Activation ERROR: ' . $e->getMessage());
            }
            
            deactivate_plugins(PARFUME_REVIEWS_BASENAME);
            wp_die(
                '<h1>' . __('Plugin Activation Error', 'parfume-reviews') . '</h1>' .
                '<p>' . esc_html($e->getMessage()) . '</p>' .
                '<p><a href="' . admin_url('plugins.php') . '">' . __('&larr; Back to Plugins', 'parfume-reviews') . '</a></p>'
            );
        }
    }
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\activate');

/**
 * Plugin deactivation
 */
function deactivate() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews: Deactivating plugin...');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Remove activation flag
    delete_option('parfume_reviews_activated');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews: Plugin deactivated successfully');
    }
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\deactivate');