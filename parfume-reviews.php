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

namespace Parfume_Reviews;

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
    $prefix = 'Parfume_Reviews\\';
    
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
    // Replace underscores with dashes in file names (PSR-4 style)
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Load helper functions
 * These are global functions used in templates
 */
require_once PARFUME_REVIEWS_PATH . 'includes/helpers.php';

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
    
    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function display_requirement_errors($errors) {
    ?>
    <div class="notice notice-error">
        <p><strong><?php _e('Parfume Reviews Plugin Error:', 'parfume-reviews'); ?></strong></p>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo esc_html($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function init() {
    // Check requirements
    $errors = check_requirements();
    
    if (!empty($errors)) {
        add_action('admin_notices', function() use ($errors) {
            display_requirement_errors($errors);
        });
        return;
    }
    
    // Initialize the main plugin class
    try {
        $plugin = Core\Plugin::get_instance();
        $plugin->run();
    } catch (\Exception $e) {
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>Parfume Reviews Plugin Error:</strong>
                    <?php echo esc_html($e->getMessage()); ?>
                </p>
            </div>
            <?php
        });
        
        // Log error if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews Error: ' . $e->getMessage());
        }
    }
}

/**
 * Activation hook
 */
function activate() {
    // Check requirements before activation
    $errors = check_requirements();
    
    if (!empty($errors)) {
        wp_die(
            implode('<br>', $errors),
            __('Plugin Activation Error', 'parfume-reviews'),
            ['back_link' => true]
        );
    }
    
    try {
        // Store plugin version
        update_option('parfume_reviews_version', PARFUME_REVIEWS_VERSION);
        
        // Set flag to flush rewrite rules on next init
        update_option('parfume_reviews_flush_rewrite_rules', 1);
        
        // Log activation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Plugin activated successfully - v' . PARFUME_REVIEWS_VERSION);
        }
    } catch (\Exception $e) {
        wp_die(
            $e->getMessage(),
            __('Plugin Activation Error', 'parfume-reviews'),
            ['back_link' => true]
        );
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