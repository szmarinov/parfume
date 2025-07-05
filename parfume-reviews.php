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
        'includes/class-shortcodes.php' => 'Parfume_Reviews\\Shortcodes',
        'includes/class-import-export.php' => 'Parfume_Reviews\\Import_Export',
        'includes/class-settings.php' => 'Parfume_Reviews\\Settings',
    );
    
    foreach ($class_files as $file => $class_name) {
        $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
            parfume_reviews_debug_log("Loaded class file: $file");
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
        
        // Set default options
        $defaults = array(
            'parfume_slug' => 'parfiumi',
            'brands_slug' => 'marki',
            'notes_slug' => 'notes',
            'perfumers_slug' => 'parfumers',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'price_update_interval' => 24,
        );
        
        // Only add if it doesn't exist
        if (!get_option('parfume_reviews_settings')) {
            add_option('parfume_reviews_settings', $defaults);
        }
        
        // Flush rewrite rules immediately during activation
        flush_rewrite_rules();
        
        // Also set flag for next page load as backup
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        parfume_reviews_debug_log("Plugin activated successfully");
        
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
            echo '<strong>Parfume Reviews:</strong> Missing required files: ' . implode(', ', $missing_files);
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
                echo '<li>✅ Taxonomy "' . $taxonomy . '" is registered</li>';
                
                // Check rewrite rules
                $tax_obj = $taxonomies[$taxonomy];
                if (isset($tax_obj->rewrite['slug'])) {
                    echo '<li>└── Rewrite slug: ' . $tax_obj->rewrite['slug'] . '</li>';
                }
            } else {
                echo '<li>❌ Taxonomy "' . $taxonomy . '" is NOT registered</li>';
            }
        }
        
        // Check if post type is registered
        $post_types = get_post_types(array(), 'objects');
        if (isset($post_types['parfume'])) {
            echo '<li>✅ Post type "parfume" is registered</li>';
            $post_obj = $post_types['parfume'];
            if (isset($post_obj->rewrite['slug'])) {
                echo '<li>└── Rewrite slug: ' . $post_obj->rewrite['slug'] . '</li>';
            }
        } else {
            echo '<li>❌ Post type "parfume" is NOT registered</li>';
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
                echo '<li>✅ Template "' . $template . '" exists</li>';
            } else {
                echo '<li>❌ Template "' . $template . '" is missing</li>';
            }
        }
        
        // Check current rewrite rules
        $rules = get_option('rewrite_rules');
        echo '<li><strong>Active Rewrite Rules (parfume related):</strong></li>';
        if ($rules) {
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'parfiumi') !== false || strpos($rewrite, 'parfume') !== false) {
                    echo '<li>└── ' . esc_html($rule) . ' → ' . esc_html($rewrite) . '</li>';
                }
            }
        }
        
        // Check sample URLs
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        echo '<li><strong>Sample URLs:</strong></li>';
        echo '<li>└── Archive: <a href="' . home_url('/' . $parfume_slug . '/') . '" target="_blank">' . home_url('/' . $parfume_slug . '/') . '</a></li>';
        
        // Show some sample taxonomy URLs if terms exist
        $sample_terms = array(
            'marki' => get_terms(array('taxonomy' => 'marki', 'number' => 1, 'hide_empty' => false)),
            'notes' => get_terms(array('taxonomy' => 'notes', 'number' => 1, 'hide_empty' => false)),
        );
        
        foreach ($sample_terms as $taxonomy => $terms) {
            if (!empty($terms) && !is_wp_error($terms)) {
                $term_link = get_term_link($terms[0]);
                if (!is_wp_error($term_link)) {
                    echo '<li>└── ' . ucfirst($taxonomy) . ' term: <a href="' . $term_link . '" target="_blank">' . $term_link . '</a></li>';
                }
                
                // Archive link
                $archive_link = home_url('/' . $parfume_slug . '/' . $taxonomy . '/');
                echo '<li>└── ' . ucfirst($taxonomy) . ' archive: <a href="' . $archive_link . '" target="_blank">' . $archive_link . '</a></li>';
            }
        }
        
        echo '</ul>';
        echo '<p><a href="' . admin_url('options-permalink.php') . '" class="button button-primary">Flush Rewrite Rules</a> ';
        echo '<a href="' . admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings') . '" class="button">Plugin Settings</a></p>';
        echo '</div>';
    }
}

// Add debug functionality
add_action('admin_notices', 'parfume_reviews_debug_urls');

// Add admin menu item for quick debug
function parfume_reviews_add_debug_menu() {
    if (current_user_can('manage_options')) {
        add_management_page(
            'Parfume Reviews Debug',
            'Parfume Debug',
            'manage_options',
            'parfume-debug',
            'parfume_reviews_debug_page'
        );
    }
}

function parfume_reviews_debug_page() {
    echo '<div class="wrap">';
    echo '<h1>Parfume Reviews Debug</h1>';
    echo '<p>Use this page to debug URL and template issues.</p>';
    echo '<p><a href="' . add_query_arg('parfume_debug', 'urls') . '" class="button button-primary">Show Debug Info</a></p>';
    
    // Force flush rewrite rules
    if (isset($_POST['flush_rules']) && wp_verify_nonce($_POST['_wpnonce'], 'parfume_flush_rules')) {
        flush_rewrite_rules();
        echo '<div class="notice notice-success"><p>Rewrite rules flushed successfully!</p></div>';
    }
    
    echo '<form method="post">';
    wp_nonce_field('parfume_flush_rules');
    echo '<p><input type="submit" name="flush_rules" class="button button-secondary" value="Force Flush Rewrite Rules"></p>';
    echo '</form>';
    
    // Test URLs
    echo '<h2>Test URLs</h2>';
    echo '<p>Click these links to test if URLs are working:</p>';
    
    $settings = get_option('parfume_reviews_settings', array());
    $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
    $brands_slug = !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
    $notes_slug = !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
    
    echo '<ul>';
    echo '<li><strong>Archives:</strong></li>';
    echo '<li><a href="' . home_url('/' . $parfume_slug . '/') . '" target="_blank">Main Archive</a></li>';
    echo '<li><a href="' . home_url('/' . $parfume_slug . '/' . $brands_slug . '/') . '" target="_blank">Brands Archive</a></li>';
    echo '<li><a href="' . home_url('/' . $parfume_slug . '/' . $notes_slug . '/') . '" target="_blank">Notes Archive</a></li>';
    
    // Get some sample terms
    $sample_brand = get_terms(array('taxonomy' => 'marki', 'number' => 1, 'hide_empty' => false));
    $sample_note = get_terms(array('taxonomy' => 'notes', 'number' => 1, 'hide_empty' => false));
    
    if (!empty($sample_brand) && !is_wp_error($sample_brand)) {
        echo '<li><strong>Sample Terms:</strong></li>';
        echo '<li><a href="' . get_term_link($sample_brand[0]) . '" target="_blank">Sample Brand: ' . $sample_brand[0]->name . '</a></li>';
    }
    
    if (!empty($sample_note) && !is_wp_error($sample_note)) {
        echo '<li><a href="' . get_term_link($sample_note[0]) . '" target="_blank">Sample Note: ' . $sample_note[0]->name . '</a></li>';
    }
    
    echo '</ul>';
    echo '</div>';
}

add_action('admin_menu', 'parfume_reviews_add_debug_menu');