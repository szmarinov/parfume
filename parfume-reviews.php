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

// Debug function to check what's happening
function parfume_reviews_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews: ' . $message);
    }
}

// Load required files manually first
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
            parfume_reviews_debug_log("Missing file: $file");
        }
    }
}

// Load class files manually
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
            parfume_reviews_debug_log("Missing class file: $file");
        }
    }
}

// Initialize the plugin safely
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
        parfume_reviews_debug_log("Error during initialization: " . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Parfume Reviews Error:</strong> ' . esc_html($e->getMessage());
            echo '</p></div>';
        });
    }
}

// Force flush rewrite rules after post types and taxonomies are registered
function parfume_reviews_flush_rewrite_rules() {
    // Check if we need to flush rewrite rules
    if (get_option('parfume_reviews_flush_rewrite_rules', false)) {
        flush_rewrite_rules();
        delete_option('parfume_reviews_flush_rewrite_rules');
        parfume_reviews_debug_log("Rewrite rules flushed");
    }
}

// Hook initialization
add_action('plugins_loaded', 'parfume_reviews_init');
add_action('init', 'parfume_reviews_flush_rewrite_rules', 999); // Late priority

// ВАЖНА ДОБАВКА: Форсирано flush на rewrite rules след промени
add_action('admin_init', function() {
    // Проверка дали има промени във версията
    $current_version = get_option('parfume_reviews_version', '0.0.0');
    if (version_compare($current_version, PARFUME_REVIEWS_VERSION, '<')) {
        // Нова версия - flush rewrite rules
        flush_rewrite_rules();
        update_option('parfume_reviews_version', PARFUME_REVIEWS_VERSION);
        update_option('parfume_reviews_flush_rewrite_rules', true);
        parfume_reviews_debug_log("Version updated to " . PARFUME_REVIEWS_VERSION . " - flushed rewrite rules");
    }
});

// НОВА ДОБАВКА: Force flush при първо зареждане след промените
add_action('init', function() {
    // Проверяваме дали е била направена поправката на taxonomy files
    $taxonomy_fix_applied = get_option('parfume_reviews_taxonomy_404_fix', false);
    if (!$taxonomy_fix_applied) {
        // Задействаме flush на rewrite rules
        update_option('parfume_reviews_flush_rewrite_rules', true);
        update_option('parfume_reviews_taxonomy_404_fix', true);
        
        // Flush-ваме веднага
        add_action('wp_loaded', function() {
            flush_rewrite_rules();
            parfume_reviews_debug_log("Applied taxonomy 404 fix - flushed rewrite rules");
        }, 999);
    }
}, 20);

// Activation hook with error handling
function parfume_reviews_activate() {
    try {
        // Load required files for activation
        parfume_reviews_load_files();
        parfume_reviews_load_classes();
        
        // Initialize to register post types and taxonomies
        if (class_exists('Parfume_Reviews\\Post_Type')) {
            $post_type = new Parfume_Reviews\Post_Type();
        }
        if (class_exists('Parfume_Reviews\\Taxonomies')) {
            $taxonomies = new Parfume_Reviews\Taxonomies();
        }
        
        // Set default options including taxonomy fixes
        $defaults = array(
            'parfume_slug' => 'parfiumi',
            'brands_slug' => 'marki',
            'notes_slug' => 'notki',  // ПОПРАВЕНО: правилният slug
            'perfumers_slug' => 'parfumeri', // ПОПРАВЕНО: правилният slug
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'posts_per_page' => 12, // GENERAL SETTINGS
            'price_update_interval' => 24,
            'show_archive_sidebar' => 1,
            'archive_posts_per_page' => 12,
            'featured_perfumes_per_intensity' => 3, // НОВА НАСТРОЙКА: парфюми за интензивност
            'archive_grid_columns' => 3,
            'homepage_description' => '',
            'homepage_blog_count' => 6,
            'homepage_blog_columns' => 3,
            'homepage_featured_count' => 4,
            'homepage_featured_columns' => 2,
            'homepage_men_perfumes' => array(),
            'homepage_women_perfumes' => array(),
            'homepage_featured_brands' => array(),
            'homepage_arabic_perfumes' => array(),
            'homepage_latest_count' => 8,
            'card_show_image' => 1,
            'card_show_brand' => 1,
            'card_show_name' => 1,
            'card_show_price' => 1,
            'card_show_availability' => 1,
            'card_show_shipping' => 1,
            'price_selector_parfium' => '.price',
            'price_selector_douglas' => '.price',
            'price_selector_notino' => '.price',
        );
        
        // Only add if it doesn't exist
        if (!get_option('parfume_reviews_settings')) {
            add_option('parfume_reviews_settings', $defaults);
        } else {
            // Update existing settings with correct slugs
            $current_settings = get_option('parfume_reviews_settings', array());
            $current_settings['notes_slug'] = 'notki';
            $current_settings['perfumers_slug'] = 'parfumeri';
            update_option('parfume_reviews_settings', $current_settings);
        }
        
        // Set version
        update_option('parfume_reviews_version', PARFUME_REVIEWS_VERSION);
        
        // Force the taxonomy fix flag
        update_option('parfume_reviews_taxonomy_404_fix', false); // Reset to trigger fix on next load
        
        // Flush rewrite rules immediately during activation
        flush_rewrite_rules();
        
        // Also set flag for next page load as backup
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        parfume_reviews_debug_log("Plugin activated successfully with taxonomy fixes");
        
    } catch (Exception $e) {
        parfume_reviews_debug_log("Error during activation: " . $e->getMessage());
        wp_die('Error activating Parfume Reviews: ' . $e->getMessage());
    }
}

// Deactivation hook
function parfume_reviews_deactivate() {
    try {
        flush_rewrite_rules();
        delete_option('parfume_reviews_flush_rewrite_rules');
        delete_option('parfume_reviews_version');
        delete_option('parfume_reviews_taxonomy_404_fix'); // НОВА ДОБАВКА: изчистваме fix флага
        parfume_reviews_debug_log("Plugin deactivated successfully");
    } catch (Exception $e) {
        parfume_reviews_debug_log("Error during deactivation: " . $e->getMessage());
    }
}

// Register hooks
register_activation_hook(__FILE__, 'parfume_reviews_activate');
register_deactivation_hook(__FILE__, 'parfume_reviews_deactivate');

// Admin notice for missing files
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

// Check requirements on admin_init
add_action('admin_init', 'parfume_reviews_check_requirements');

// Debug function for checking URLs and templates
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
                echo '<li>❌ Таксономия "' . $taxonomy . '" НЕ е регистрирана</li>';
            }
        }
        
        // Check if post type is registered
        $post_types = get_post_types(array(), 'objects');
        if (isset($post_types['parfume'])) {
            echo '<li>✅ Post type "parfume" е регистриран</li>';
            $post_obj = $post_types['parfume'];
            if (isset($post_obj->rewrite['slug'])) {
                echo '<li>└── Rewrite slug: ' . $post_obj->rewrite['slug'] . '</li>';
            }
        } else {
            echo '<li>❌ Post type "parfume" НЕ е регистриран</li>';
        }
        
        // Check template files
        $template_files = array(
            'templates/taxonomy-marki.php',
            'templates/taxonomy-notes.php', 
            'templates/taxonomy-perfumer.php',
            'templates/taxonomy-gender.php',
            'templates/taxonomy-aroma_type.php',
            'templates/taxonomy-season.php',
            'templates/taxonomy-intensity.php',
            'templates/archive-marki.php',
            'templates/archive-notes.php',
            'templates/archive-taxonomy.php',
            'templates/archive-parfume.php',
            'templates/single-parfume.php'
        );
        
        foreach ($template_files as $template) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $template;
            if (file_exists($file_path)) {
                echo '<li>✅ Template "' . $template . '" съществува</li>';
            } else {
                echo '<li>❌ Template "' . $template . '" липсва</li>';
            }
        }
        
        // Check current rewrite rules
        $rules = get_option('rewrite_rules');
        echo '<li><strong>Активни Rewrite Rules (парфюм свързани):</strong></li>';
        if ($rules) {
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'parfiumi') !== false || strpos($rewrite, 'parfume') !== false || 
                    strpos($rule, 'marki') !== false || strpos($rule, 'notes') !== false ||
                    strpos($rewrite, 'marki') !== false || strpos($rewrite, 'notes') !== false) {
                    echo '<li>└── ' . esc_html($rule) . ' → ' . esc_html($rewrite) . '</li>';
                }
            }
        }
        
        // Check sample URLs
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        echo '<li><strong>Примерни URL-и:</strong></li>';
        echo '<li>└── Архив: <a href="' . home_url('/' . $parfume_slug . '/') . '" target="_blank">' . home_url('/' . $parfume_slug . '/') . '</a></li>';
        echo '<li>└── Марки: <a href="' . home_url('/' . $parfume_slug . '/marki/') . '" target="_blank">' . home_url('/' . $parfume_slug . '/marki/') . '</a></li>';
        echo '<li>└── Ноти: <a href="' . home_url('/' . $parfume_slug . '/notki/') . '" target="_blank">' . home_url('/' . $parfume_slug . '/notki/') . '</a></li>';
        echo '<li>└── Парфюмеристи: <a href="' . home_url('/' . $parfume_slug . '/parfumeri/') . '" target="_blank">' . home_url('/' . $parfume_slug . '/parfumeri/') . '</a></li>';
        echo '<li>└── Сезони: <a href="' . home_url('/' . $parfume_slug . '/season/') . '" target="_blank">' . home_url('/' . $parfume_slug . '/season/') . '</a></li>';
        
        echo '</ul></div>';
    }
}

add_action('admin_notices', 'parfume_reviews_debug_urls');

// ПРИНУДИТЕЛНО FLUSH НА REWRITE RULES - ВРЕМЕННО РЕШЕНИЕ
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && isset($_GET['flush_parfume_rules'])) {
        flush_rewrite_rules();
        update_option('parfume_reviews_flush_rewrite_rules', true);
        echo '<div class="notice notice-success"><p><strong>Parfume Reviews:</strong> Rewrite rules са изчистени и обновени!</p></div>';
    }
    
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>';
        echo '<strong>Parfume Reviews Debug:</strong> ';
        echo '<a href="' . add_query_arg('flush_parfume_rules', '1') . '">Изчисти rewrite rules</a> | ';
        echo '<a href="' . add_query_arg('parfume_debug', 'urls') . '">Покажи debug информация</a>';
        echo '</p></div>';
    }
});