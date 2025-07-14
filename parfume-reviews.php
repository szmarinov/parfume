<?php
/**
 * Plugin Name: Parfume Reviews
 * Description: A comprehensive perfume review system for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: parfume-reviews
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PARFUME_REVIEWS_VERSION', '1.0.0');
define('PARFUME_REVIEWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARFUME_REVIEWS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PARFUME_REVIEWS_BASENAME', plugin_basename(__FILE__));

/**
 * Debug function to check what's happening
 * ВАЖНО: Основна debug функция за целия плъгин
 */
function parfume_reviews_debug_log($message, $type = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $prefix = '[Parfume Reviews]';
        if ($type === 'error') {
            $prefix = '[Parfume Reviews ERROR]';
        }
        error_log($prefix . ' ' . $message);
    }
}

/**
 * Load required files manually first
 * ВАЖНО: Зарежда template functions и hooks преди класовете
 */
function parfume_reviews_load_files() {
    $required_files = array(
        'includes/template-functions.php',
        'includes/template-hooks.php',
    );
    
    foreach ($required_files as $file) {
        $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
            parfume_reviews_debug_log("Loaded: $file");
        } else {
            parfume_reviews_debug_log("Missing file: $file", 'error');
        }
    }
}

/**
 * Load class files manually
 * ВАЖНО: Зарежда всички класове с проверки
 */
function parfume_reviews_load_classes() {
    $class_files = array(
        'includes/class-post-type.php' => 'Parfume_Reviews\\Post_Type',
        'includes/class-taxonomies.php' => 'Parfume_Reviews\\Taxonomies',
        'includes/class-comparison.php' => 'Parfume_Reviews\\Comparison',
        'includes/class-shortcodes.php' => 'Parfume_Reviews\\Shortcodes',
        'includes/class-import-export.php' => 'Parfume_Reviews\\Import_Export',
        'includes/class-settings.php' => 'Parfume_Reviews\\Settings',
    );
    
    foreach ($class_files as $file => $class_name) {
        $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
            parfume_reviews_debug_log("Loaded class file: $file");
            
            // Специална проверка за taxonomies
            if ($file === 'includes/class-taxonomies.php') {
                parfume_reviews_debug_log("Taxonomies main file loaded, component files will be auto-loaded");
            }
        } else {
            parfume_reviews_debug_log("Missing class file: $file", 'error');
        }
    }
}

/**
 * Initialize the plugin safely
 * ВАЖНО: Основна инициализация на плъгина
 */
function parfume_reviews_init() {
    try {
        // Load text domain
        load_plugin_textdomain('parfume-reviews', false, dirname(PARFUME_REVIEWS_BASENAME) . '/languages');
        
        // Load files
        parfume_reviews_load_files();
        parfume_reviews_load_classes();
        
        // Initialize components safely with proper order
        if (class_exists('Parfume_Reviews\\Post_Type')) {
            new Parfume_Reviews\Post_Type();
            parfume_reviews_debug_log("Post_Type initialized");
        }
        
        if (class_exists('Parfume_Reviews\\Taxonomies')) {
            new Parfume_Reviews\Taxonomies();
            parfume_reviews_debug_log("Taxonomies initialized");
        }
        
        if (class_exists('Parfume_Reviews\\Comparison')) {
            new Parfume_Reviews\Comparison();
            parfume_reviews_debug_log("Comparison initialized");
        }
        
        if (class_exists('Parfume_Reviews\\Shortcodes')) {
            new Parfume_Reviews\Shortcodes();
            parfume_reviews_debug_log("Shortcodes initialized");
        }
        
        if (class_exists('Parfume_Reviews\\Import_Export')) {
            new Parfume_Reviews\Import_Export();
            parfume_reviews_debug_log("Import_Export initialized");
        }
        
        if (class_exists('Parfume_Reviews\\Settings')) {
            new Parfume_Reviews\Settings();
            parfume_reviews_debug_log("Settings initialized");
        }
        
        parfume_reviews_debug_log("Plugin initialized successfully");
        
    } catch (Exception $e) {
        parfume_reviews_debug_log("Error during initialization: " . $e->getMessage(), 'error');
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Parfume Reviews Error:</strong> ' . esc_html($e->getMessage());
            echo '</p></div>';
        });
    }
}

/**
 * Force flush rewrite rules after post types and taxonomies are registered
 * ВАЖНО: Flush правила за URL-и
 */
function parfume_reviews_flush_rewrite_rules() {
    // Check if we need to flush rewrite rules
    if (get_option('parfume_reviews_flush_rewrite_rules', false)) {
        flush_rewrite_rules();
        delete_option('parfume_reviews_flush_rewrite_rules');
        parfume_reviews_debug_log("Rewrite rules flushed");
    }
}

/**
 * Activation hook
 * ВАЖНО: Активация на плъгина
 */
function parfume_reviews_activate() {
    try {
        // Initialize plugin to register post types and taxonomies
        parfume_reviews_init();
        
        // Force flush rewrite rules on activation
        flush_rewrite_rules();
        
        // Set version and flush flag
        update_option('parfume_reviews_version', PARFUME_REVIEWS_VERSION);
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        parfume_reviews_debug_log("Plugin activated successfully");
        
    } catch (Exception $e) {
        parfume_reviews_debug_log("Error during activation: " . $e->getMessage(), 'error');
        wp_die('Error activating Parfume Reviews: ' . $e->getMessage());
    }
}

/**
 * Deactivation hook
 * ВАЖНО: Деактивация на плъгина
 */
function parfume_reviews_deactivate() {
    try {
        flush_rewrite_rules();
        delete_option('parfume_reviews_flush_rewrite_rules');
        delete_option('parfume_reviews_version');
        parfume_reviews_debug_log("Plugin deactivated successfully");
    } catch (Exception $e) {
        parfume_reviews_debug_log("Error during deactivation: " . $e->getMessage(), 'error');
    }
}

/**
 * Admin notice for missing files
 * ВАЖНО: Проверка за липсващи файлове
 */
function parfume_reviews_check_requirements() {
    $missing_files = array();
    
    $required_files = array(
        'includes/class-post-type.php',
        'includes/class-taxonomies.php',
        'includes/class-comparison.php',
        'includes/template-functions.php',
    );
    
    foreach ($required_files as $file) {
        if (!file_exists(PARFUME_REVIEWS_PLUGIN_DIR . $file)) {
            $missing_files[] = $file;
        }
    }
    
    if (!empty($missing_files)) {
        add_action('admin_notices', function() use ($missing_files) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Parfume Reviews:</strong> Липсват задължителни файлове: ' . implode(', ', $missing_files);
            echo '</p></div>';
        });
        return false;
    }
    
    return true;
}

/**
 * Debug function for checking URLs and templates
 * ВАЖНО: Debug информация за URL-и и таксономии
 */
function parfume_reviews_debug_urls() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['parfume_debug']) && $_GET['parfume_debug'] === 'urls') {
        echo '<div class="notice notice-info"><p><strong>Parfume Reviews Debug Info:</strong></p>';
        
        // Check if taxonomies are registered
        $taxonomies = get_taxonomies(array(), 'objects');
        $parfume_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        
        echo '<ul>';
        foreach ($parfume_taxonomies as $taxonomy) {
            if (isset($taxonomies[$taxonomy])) {
                echo '<li>✅ Таксономия "' . $taxonomy . '" е регистрирана</li>';
                
                // Check rewrite rules
                $tax_obj = $taxonomies[$taxonomy];
                if (isset($tax_obj->rewrite['slug'])) {
                    echo '<li>└── Rewrite slug: ' . $tax_obj->rewrite['slug'] . '</li>';
                }
            } else {
                echo '<li>❌ Таксономия "' . $taxonomy . '" не е регистрирана</li>';
            }
        }
        
        // Check post type
        if (post_type_exists('parfume')) {
            echo '<li>✅ Post type "parfume" е регистриран</li>';
        } else {
            echo '<li>❌ Post type "parfume" не е регистриран</li>';
        }
        
        echo '</ul></div>';
    }
}

/**
 * ВАЖНА ДОБАВКА: Форсирано flush на rewrite rules след промени
 */
function parfume_reviews_check_version_update() {
    $current_version = get_option('parfume_reviews_version', '0.0.0');
    if (version_compare($current_version, PARFUME_REVIEWS_VERSION, '<')) {
        // Нова версия - flush rewrite rules
        flush_rewrite_rules();
        update_option('parfume_reviews_version', PARFUME_REVIEWS_VERSION);
        update_option('parfume_reviews_flush_rewrite_rules', true);
        parfume_reviews_debug_log("Version updated to " . PARFUME_REVIEWS_VERSION . " - rewrite rules flushed");
    }
}

// Register hooks
register_activation_hook(__FILE__, 'parfume_reviews_activate');
register_deactivation_hook(__FILE__, 'parfume_reviews_deactivate');

// Hook initialization
add_action('plugins_loaded', 'parfume_reviews_init');
add_action('init', 'parfume_reviews_flush_rewrite_rules', 999); // Late priority
add_action('admin_init', 'parfume_reviews_check_requirements');
add_action('admin_init', 'parfume_reviews_check_version_update');
add_action('admin_notices', 'parfume_reviews_debug_urls');

// End of file