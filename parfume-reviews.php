<?php
/**
 * Plugin Name: Parfume Reviews
 * Plugin URI: https://example.com/parfume-reviews
 * Description: A comprehensive perfume review system for WordPress with advanced features
 * Version: 2.0.999999999999999999
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
    // Project namespace prefix
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
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Load helper functions
 * These are global functions used in templates
 */
if (file_exists(PARFUME_REVIEWS_PATH . 'includes/helpers.php')) {
    require_once PARFUME_REVIEWS_PATH . 'includes/helpers.php';
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

/**
 * Initialize plugin
 */
function init() {
    // Check if Plugin class exists
    if (!class_exists('ParfumeReviews\\Core\\Plugin')) {
        // Display admin notice
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>Parfume Reviews Error:</strong> Plugin class not found. Please check the installation.</p>
            </div>
            <?php
        });
        
        return;
    }
    
    // Get plugin instance and run
    try {
        $plugin = \ParfumeReviews\Core\Plugin::get_instance();
        $plugin->run();
    } catch (\Exception $e) {
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><strong>Parfume Reviews Error:</strong> <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}

/**
 * Activation hook
 */
function activate() {
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
        } catch (\Exception $e) {
            deactivate_plugins(PARFUME_REVIEWS_BASENAME);
            wp_die(
                $e->getMessage(),
                __('Plugin Activation Error', 'parfume-reviews'),
                ['back_link' => true]
            );
        }
    }
}

/**
 * Deactivation hook
 */
function deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log deactivation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews: Plugin deactivated');
    }
}

// Register hooks
add_action('plugins_loaded', __NAMESPACE__ . '\\init');
register_activation_hook(__FILE__, __NAMESPACE__ . '\\activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\deactivate');