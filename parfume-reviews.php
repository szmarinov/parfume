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
 */
function parfume_reviews_debug_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews [' . strtoupper($level) . ']: ' . $message);
    }
}

/**
 * Load required files manually first
 * ВАЖНО: Зареждаме критичните файлове първо
 */
function parfume_reviews_load_files() {
    $required_files = array(
        'includes/template-functions.php',  // Template functions (модулен файл)
        'includes/template-hooks.php',     // Template hooks
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
 * ПОПРАВЕНО: Зареждаме всички класове с правилна последователност
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
            
            // Проверяваме дали файлът зарежда допълнителни файлове за таксономии
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
 * ПОПРАВЕНО: Подобрена инициализация с error handling
 */
function parfume_reviews_init() {
    try {
        // Load text domain
        load_plugin_textdomain('parfume-reviews', false, dirname(PARFUME_REVIEWS_BASENAME) . '/languages');
        
        // Load required files first
        parfume_reviews_load_files();
        
        // Load class files
        parfume_reviews_load_classes();
        
        // Initialize components in proper order
        parfume_reviews_init_components();
        
        parfume_reviews_debug_log("Plugin initialized successfully");
        
    } catch (Exception $e) {
        parfume_reviews_debug_log("Plugin initialization error: " . $e->getMessage(), 'error');
        
        // Show admin notice if there's an error
        add_action('admin_notices', function() use ($e) {
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-error"><p><strong>Parfume Reviews:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            }
        });
    }
}

/**
 * Initialize plugin components
 * НОВА ФУНКЦИЯ: Правилна инициализация на компонентите
 */
function parfume_reviews_init_components() {
    // Initialize components with namespace
    if (class_exists('Parfume_Reviews\\Post_Type')) {
        new Parfume_Reviews\Post_Type();
        parfume_reviews_debug_log("Post_Type component initialized");
    }
    
    if (class_exists('Parfume_Reviews\\Taxonomies')) {
        new Parfume_Reviews\Taxonomies();
        parfume_reviews_debug_log("Taxonomies component initialized");
    }
    
    if (class_exists('Parfume_Reviews\\Comparison')) {
        new Parfume_Reviews\Comparison();
        parfume_reviews_debug_log("Comparison component initialized");
    }
    
    if (class_exists('Parfume_Reviews\\Shortcodes')) {
        new Parfume_Reviews\Shortcodes();
        parfume_reviews_debug_log("Shortcodes component initialized");
    }
    
    if (class_exists('Parfume_Reviews\\Import_Export')) {
        new Parfume_Reviews\Import_Export();
        parfume_reviews_debug_log("Import_Export component initialized");
    }
    
    if (class_exists('Parfume_Reviews\\Settings')) {
        new Parfume_Reviews\Settings();
        parfume_reviews_debug_log("Settings component initialized");
    }
}

/**
 * Plugin activation hook
 * ПОПРАВЕНО: Добавена flush_rewrite_rules
 */
function parfume_reviews_activate() {
    parfume_reviews_debug_log("Plugin activation started");
    
    // Първо зареждаме файловете
    parfume_reviews_load_files();
    parfume_reviews_load_classes();
    
    // Initialize components for activation
    parfume_reviews_init_components();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    parfume_reviews_debug_log("Plugin activated successfully");
}

/**
 * Plugin deactivation hook
 */
function parfume_reviews_deactivate() {
    parfume_reviews_debug_log("Plugin deactivation started");
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    parfume_reviews_debug_log("Plugin deactivated successfully");
}

/**
 * Plugin uninstall (handled in separate uninstall.php file)
 */

// Hook into WordPress
add_action('plugins_loaded', 'parfume_reviews_init');

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'parfume_reviews_activate');
register_deactivation_hook(__FILE__, 'parfume_reviews_deactivate');

/**
 * Check plugin requirements
 * НОВА ФУНКЦИЯ: Проверяваме системните изисквания
 */
function parfume_reviews_check_requirements() {
    $requirements = array(
        'php_version' => '7.4',
        'wp_version' => '5.0',
        'required_plugins' => array(), // Няма задължителни плъгини
    );
    
    $errors = array();
    
    // Check PHP version
    if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
        $errors[] = sprintf(
            __('Parfume Reviews изисква PHP версия %s или по-нова. Вашата версия е %s.', 'parfume-reviews'),
            $requirements['php_version'],
            PHP_VERSION
        );
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, $requirements['wp_version'], '<')) {
        $errors[] = sprintf(
            __('Parfume Reviews изисква WordPress версия %s или по-нова. Вашата версия е %s.', 'parfume-reviews'),
            $requirements['wp_version'],
            $wp_version
        );
    }
    
    if (!empty($errors)) {
        deactivate_plugins(PARFUME_REVIEWS_BASENAME);
        
        wp_die(
            '<h1>' . __('Parfume Reviews не може да бъде активиран', 'parfume-reviews') . '</h1>' .
            '<p>' . implode('</p><p>', $errors) . '</p>',
            __('Системни изисквания', 'parfume-reviews'),
            array('back_link' => true)
        );
    }
    
    return true;
}

// Check requirements on activation
register_activation_hook(__FILE__, 'parfume_reviews_check_requirements');

/**
 * Add plugin action links
 * НОВА ФУНКЦИЯ: Добавяме линкове в plugin page
 */
function parfume_reviews_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=parfume-reviews-settings') . '">' . __('Настройки', 'parfume-reviews') . '</a>';
    array_unshift($links, $settings_link);
    
    return $links;
}

add_filter('plugin_action_links_' . PARFUME_REVIEWS_BASENAME, 'parfume_reviews_plugin_action_links');

/**
 * Add plugin meta links
 * НОВА ФУНКЦИЯ: Добавяме мета линкове
 */
function parfume_reviews_plugin_row_meta($links, $file) {
    if ($file === PARFUME_REVIEWS_BASENAME) {
        $new_links = array(
            'docs' => '<a href="#" target="_blank">' . __('Документация', 'parfume-reviews') . '</a>',
            'support' => '<a href="#" target="_blank">' . __('Поддръжка', 'parfume-reviews') . '</a>',
        );
        
        $links = array_merge($links, $new_links);
    }
    
    return $links;
}

add_filter('plugin_row_meta', 'parfume_reviews_plugin_row_meta', 10, 2);

/**
 * Check if plugin is properly loaded
 * UTILITY ФУНКЦИЯ: За debugging
 */
function parfume_reviews_is_loaded() {
    return defined('PARFUME_REVIEWS_VERSION') && 
           class_exists('Parfume_Reviews\\Post_Type') && 
           class_exists('Parfume_Reviews\\Taxonomies');
}

/**
 * Get plugin info
 * UTILITY ФУНКЦИЯ: Информация за плъгина
 */
function parfume_reviews_get_plugin_info() {
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    return get_plugin_data(__FILE__);
}

/**
 * Emergency deactivation check
 * SAFETY ФУНКЦИЯ: За аварийна деактивация при грешки
 */
function parfume_reviews_emergency_check() {
    if (get_option('parfume_reviews_emergency_disable', false)) {
        deactivate_plugins(PARFUME_REVIEWS_BASENAME);
        delete_option('parfume_reviews_emergency_disable');
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p><strong>Parfume Reviews:</strong> ' . 
                 __('Плъгинът беше деактивиран поради аварийна настройка.', 'parfume-reviews') . '</p></div>';
        });
    }
}

add_action('admin_init', 'parfume_reviews_emergency_check');

parfume_reviews_debug_log("Main plugin file loaded successfully");