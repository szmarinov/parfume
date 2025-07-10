<?php
/**
 * Uninstall script for Parfume Catalog Plugin
 * 
 * This file is executed when the plugin is deleted from the WordPress admin.
 * It removes all plugin data including options, posts, taxonomies, and database tables.
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to delete plugins
if (!current_user_can('delete_plugins')) {
    exit;
}

/**
 * Check if we should skip uninstall (for debugging or testing)
 */
function parfume_catalog_should_skip_uninstall() {
    // Skip uninstall if constant is defined (useful for development)
    if (defined('PARFUME_CATALOG_SKIP_UNINSTALL') && PARFUME_CATALOG_SKIP_UNINSTALL) {
        return true;
    }
    
    // Skip if specific option is set
    if (get_option('parfume_catalog_skip_uninstall', false)) {
        return true;
    }
    
    return false;
}

/**
 * Main uninstall function
 */
function parfume_catalog_uninstall() {
    // Remove plugin options
    parfume_catalog_remove_options();
    
    // Remove custom post types and taxonomies data
    parfume_catalog_remove_posts_and_taxonomies();
    
    // Remove custom database tables
    parfume_catalog_remove_custom_tables();
    
    // Remove uploaded files
    parfume_catalog_remove_uploaded_files();
    
    // Remove scheduled cron jobs
    parfume_catalog_remove_cron_jobs();
    
    // Remove user meta data
    parfume_catalog_remove_user_meta();
    
    // Remove transients and cache
    parfume_catalog_remove_transients();
    
    // Remove rewrite rules
    parfume_catalog_remove_rewrite_rules();
    
    // Final cleanup
    parfume_catalog_final_cleanup();
}

/**
 * Remove all plugin options
 */
function parfume_catalog_remove_options() {
    $options_to_remove = array(
        // Main plugin settings
        'parfume_catalog_settings',
        'parfume_catalog_options',
        'parfume_catalog_version',
        'parfume_catalog_db_version',
        
        // Stores settings
        'parfume_catalog_stores',
        'parfume_catalog_stores_settings',
        
        // Scraper settings
        'parfume_catalog_scraper_settings',
        'parfume_catalog_scraper_interval',
        'parfume_catalog_scraper_batch_size',
        'parfume_catalog_scraper_user_agent',
        'parfume_catalog_scraper_last_run',
        'parfume_catalog_scraper_status',
        'parfume_catalog_scraper_log',
        
        // Comparison settings
        'parfume_catalog_comparison_settings',
        'parfume_catalog_comparison_criteria',
        'parfume_catalog_comparison_max_items',
        'parfume_comparison_enabled',
        'parfume_comparison_max_items',
        'parfume_comparison_popup_width',
        'parfume_comparison_popup_position',
        'parfume_comparison_show_undo',
        
        // Comments settings
        'parfume_catalog_comments_settings',
        'parfume_catalog_comments_moderation',
        'parfume_catalog_comments_spam_words',
        
        // URL settings
        'parfume_catalog_url_settings',
        'parfume_archive_slug',
        'parfume_type_base',
        'parfume_vid_base',
        'parfume_marki_base',
        'parfume_season_base',
        'parfume_intensity_base',
        'parfume_notes_base',
        
        // Mobile settings
        'parfume_catalog_mobile_settings',
        'parfume_mobile_fixed_panel',
        'parfume_mobile_show_close_button',
        'parfume_mobile_z_index',
        'parfume_mobile_offset',
        
        // Import/Export settings
        'parfume_catalog_import_settings',
        'parfume_catalog_export_settings',
        
        // Cache and performance
        'parfume_catalog_cache_settings',
        'parfume_catalog_performance_settings',
        
        // Blog settings
        'parfume_catalog_blog_settings',
        'parfume_blog_settings',
        
        // SEO settings
        'parfume_catalog_seo_settings',
        'parfume_catalog_schema_settings',
        
        // Other settings
        'parfume_catalog_activation_time',
        'parfume_catalog_first_install',
        'parfume_catalog_flush_rewrite_rules',
        'parfume_catalog_upgrade_notices',
        'parfume_catalog_admin_notices',
        'parfume_catalog_skip_uninstall'
    );
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
        delete_site_option($option); // For multisite
    }
    
    // Remove any options that start with parfume_catalog_ or parfume_
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        'parfume_catalog_%',
        'parfume_%'
    ));
    
    // For multisite
    if (is_multisite()) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
            'parfume_catalog_%',
            'parfume_%'
        ));
    }
}

/**
 * Remove custom post types and taxonomies data
 */
function parfume_catalog_remove_posts_and_taxonomies() {
    global $wpdb;
    
    // Get all parfume posts (corrected post type name)
    $parfume_posts = get_posts(array(
        'post_type' => array('parfumes', 'parfume_blog'),
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids'
    ));
    
    // Delete posts and their meta data
    foreach ($parfume_posts as $post_id) {
        // Delete post meta
        $wpdb->delete($wpdb->postmeta, array('post_id' => $post_id));
        
        // Delete comments for this post
        $comments = get_comments(array('post_id' => $post_id));
        foreach ($comments as $comment) {
            wp_delete_comment($comment->comment_ID, true);
        }
        
        // Delete the post
        wp_delete_post($post_id, true);
    }
    
    // Remove taxonomy terms and their meta
    $taxonomies = array(
        'parfume_type',
        'parfume_marki',
        'parfume_vid',
        'parfume_season',
        'parfume_intensity',
        'parfume_notes'
    );
    
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'ids'
        ));
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term_id) {
                // Delete term meta
                $wpdb->delete($wpdb->termmeta, array('term_id' => $term_id));
                
                // Delete term
                wp_delete_term($term_id, $taxonomy);
            }
        }
    }
    
    // Remove taxonomy data from database
    foreach ($taxonomies as $taxonomy) {
        $wpdb->delete($wpdb->term_taxonomy, array('taxonomy' => $taxonomy));
    }
    
    // Clean up orphaned relationships
    $wpdb->query("
        DELETE tr FROM {$wpdb->term_relationships} tr
        LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        WHERE p.ID IS NULL
    ");
    
    // Clean up orphaned term taxonomy
    $wpdb->query("
        DELETE tt FROM {$wpdb->term_taxonomy} tt
        LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        WHERE t.term_id IS NULL
    ");
    
    // Clean up orphaned terms
    $wpdb->query("
        DELETE t FROM {$wpdb->terms} t
        LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        WHERE tt.term_id IS NULL
    ");
}

/**
 * Remove custom database tables
 */
function parfume_catalog_remove_custom_tables() {
    global $wpdb;
    
    // List of custom tables created by the plugin
    $custom_tables = array(
        $wpdb->prefix . 'parfume_scraper_data',
        $wpdb->prefix . 'parfume_scraper_log',
        $wpdb->prefix . 'parfume_scraper_queue',
        $wpdb->prefix . 'parfume_scraper_schemas',
        $wpdb->prefix . 'parfume_comments',
        $wpdb->prefix . 'parfume_ratings',
        $wpdb->prefix . 'parfume_store_schemas',
        $wpdb->prefix . 'parfume_stores',
        $wpdb->prefix . 'parfume_comparison_data',
        $wpdb->prefix . 'parfume_import_log',
        $wpdb->prefix . 'parfume_analytics',
        $wpdb->prefix . 'parfume_views',
        $wpdb->prefix . 'parfume_favorites'
    );
    
    foreach ($custom_tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}

/**
 * Remove uploaded files
 */
function parfume_catalog_remove_uploaded_files() {
    $upload_dir = wp_upload_dir();
    
    // Plugin main upload directory
    $plugin_upload_dir = $upload_dir['basedir'] . '/parfume-catalog/';
    if (is_dir($plugin_upload_dir)) {
        parfume_catalog_remove_directory($plugin_upload_dir);
    }
    
    // Store logos directory
    $store_logos_dir = $upload_dir['basedir'] . '/parfume-store-logos/';
    if (is_dir($store_logos_dir)) {
        parfume_catalog_remove_directory($store_logos_dir);
    }
    
    // Import/export files directory
    $import_dir = $upload_dir['basedir'] . '/parfume-imports/';
    if (is_dir($import_dir)) {
        parfume_catalog_remove_directory($import_dir);
    }
    
    // Cached images directory
    $cache_dir = $upload_dir['basedir'] . '/parfume-cache/';
    if (is_dir($cache_dir)) {
        parfume_catalog_remove_directory($cache_dir);
    }
    
    // Backup files directory
    $backup_dir = $upload_dir['basedir'] . '/parfume-backups/';
    if (is_dir($backup_dir)) {
        parfume_catalog_remove_directory($backup_dir);
    }
    
    // Remove any parfume-related files from main uploads
    $upload_files = glob($upload_dir['basedir'] . '/parfume*');
    foreach ($upload_files as $file) {
        if (is_file($file)) {
            unlink($file);
        } elseif (is_dir($file)) {
            parfume_catalog_remove_directory($file);
        }
    }
}

/**
 * Recursively remove directory and its contents
 */
function parfume_catalog_remove_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            parfume_catalog_remove_directory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Remove scheduled cron jobs
 */
function parfume_catalog_remove_cron_jobs() {
    // Remove all cron jobs created by the plugin
    $cron_jobs = array(
        'parfume_catalog_scraper_cron',
        'parfume_catalog_batch_scraper_cron',
        'parfume_catalog_cleanup_cron',
        'parfume_catalog_analytics_cron',
        'parfume_catalog_backup_cron',
        'parfume_catalog_maintenance_cron',
        'parfume_scraper_daily',
        'parfume_scraper_hourly',
        'parfume_cleanup_daily'
    );
    
    foreach ($cron_jobs as $job) {
        wp_clear_scheduled_hook($job);
    }
    
    // Remove any cron jobs with plugin-specific hooks
    $cron_array = get_option('cron');
    if (is_array($cron_array)) {
        foreach ($cron_array as $timestamp => $cron) {
            if (is_array($cron)) {
                foreach ($cron as $hook => $data) {
                    if (strpos($hook, 'parfume_catalog_') === 0 || strpos($hook, 'parfume_') === 0) {
                        wp_unschedule_event($timestamp, $hook);
                    }
                }
            }
        }
    }
}

/**
 * Remove user meta data
 */
function parfume_catalog_remove_user_meta() {
    global $wpdb;
    
    // Remove user meta data created by the plugin
    $user_meta_keys = array(
        'parfume_catalog_recently_viewed',
        'parfume_catalog_favorites',
        'parfume_catalog_comparison_list',
        'parfume_catalog_preferences',
        'parfume_catalog_admin_notices_dismissed',
        'parfume_catalog_tour_completed',
        'parfume_catalog_dashboard_layout',
        'parfume_recently_viewed',
        'parfume_favorites',
        'parfume_comparison_list'
    );
    
    foreach ($user_meta_keys as $meta_key) {
        delete_metadata('user', 0, $meta_key, '', true);
    }
    
    // Remove any user meta that starts with parfume_catalog_ or parfume_
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
        'parfume_catalog_%',
        'parfume_%'
    ));
}

/**
 * Remove transients and cache
 */
function parfume_catalog_remove_transients() {
    global $wpdb;
    
    // Remove transients
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
        '_transient_parfume_catalog_%',
        '_transient_timeout_parfume_catalog_%',
        '_transient_parfume_%',
        '_transient_timeout_parfume_%'
    ));
    
    // Remove site transients for multisite
    if (is_multisite()) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s OR meta_key LIKE %s OR meta_key LIKE %s",
            '_site_transient_parfume_catalog_%',
            '_site_transient_timeout_parfume_catalog_%',
            '_site_transient_parfume_%',
            '_site_transient_timeout_parfume_%'
        ));
    }
    
    // Clear object cache
    wp_cache_flush();
    
    // Remove plugin-specific cache files
    if (function_exists('wp_cache_delete_group')) {
        wp_cache_delete_group('parfume_catalog');
        wp_cache_delete_group('parfume');
    }
}

/**
 * Remove rewrite rules
 */
function parfume_catalog_remove_rewrite_rules() {
    // Delete custom rewrite rules
    delete_option('rewrite_rules');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Final cleanup operations
 */
function parfume_catalog_final_cleanup() {
    global $wpdb;
    
    // Clean up any remaining plugin references in the database
    
    // Remove from active plugins (shouldn't be necessary but just in case)
    $active_plugins = get_option('active_plugins', array());
    $plugin_basename = 'parfume-catalog/parfume-catalog.php';
    
    if (($key = array_search($plugin_basename, $active_plugins)) !== false) {
        unset($active_plugins[$key]);
        update_option('active_plugins', $active_plugins);
    }
    
    // Remove from network active plugins for multisite
    if (is_multisite()) {
        $network_active_plugins = get_site_option('active_sitewide_plugins', array());
        if (isset($network_active_plugins[$plugin_basename])) {
            unset($network_active_plugins[$plugin_basename]);
            update_site_option('active_sitewide_plugins', $network_active_plugins);
        }
    }
    
    // Remove any plugin-specific database indexes or constraints
    // This is typically handled automatically when tables are dropped
    
    // Clear any remaining WordPress caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Clear opcache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Remove plugin from recently active plugins list
    $recent_plugins = get_option('recently_activated', array());
    if (isset($recent_plugins[$plugin_basename])) {
        unset($recent_plugins[$plugin_basename]);
        update_option('recently_activated', $recent_plugins);
    }
    
    // Remove any remaining plugin capabilities
    $role = get_role('administrator');
    if ($role) {
        $capabilities = array(
            'manage_parfume_catalog',
            'edit_parfumes',
            'edit_others_parfumes',
            'publish_parfumes',
            'read_private_parfumes',
            'delete_parfumes',
            'delete_private_parfumes',
            'delete_published_parfumes',
            'delete_others_parfumes',
            'edit_private_parfumes',
            'edit_published_parfumes'
        );
        
        foreach ($capabilities as $cap) {
            $role->remove_cap($cap);
        }
    }
    
    // Log uninstallation (if logging is still available)
    if (function_exists('error_log')) {
        error_log('Parfume Catalog Plugin: Uninstallation completed successfully');
    }
}

/**
 * Backup important data before deletion (optional)
 * This function can be called if you want to create a backup before uninstalling
 */
function parfume_catalog_create_backup() {
    global $wpdb;
    
    $backup_data = array(
        'settings' => array(
            'main_options' => get_option('parfume_catalog_options'),
            'stores' => get_option('parfume_catalog_stores'),
            'scraper_settings' => get_option('parfume_catalog_scraper_settings'),
            'comparison_settings' => get_option('parfume_catalog_comparison_settings'),
            'comments_settings' => get_option('parfume_catalog_comments_settings'),
            'blog_settings' => get_option('parfume_blog_settings')
        ),
        'statistics' => array(
            'parfumes_count' => wp_count_posts('parfumes'),
            'blog_posts_count' => wp_count_posts('parfume_blog'),
            'brands_count' => wp_count_terms('parfume_marki'),
            'notes_count' => wp_count_terms('parfume_notes'),
            'users_count' => count_users()
        ),
        'meta' => array(
            'version' => get_option('parfume_catalog_version'),
            'uninstall_date' => current_time('mysql'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        )
    );
    
    // Add custom table data if tables exist
    $custom_tables = array(
        $wpdb->prefix . 'parfume_scraper_data',
        $wpdb->prefix . 'parfume_comments',
        $wpdb->prefix . 'parfume_ratings'
    );
    
    foreach ($custom_tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $backup_data['custom_tables'][$table] = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
        }
    }
    
    $upload_dir = wp_upload_dir();
    $backup_dir = $upload_dir['basedir'] . '/parfume-backups/';
    
    // Create backup directory if it doesn't exist
    if (!file_exists($backup_dir)) {
        wp_mkdir_p($backup_dir);
    }
    
    $backup_file = $backup_dir . 'parfume-catalog-backup-' . date('Y-m-d-H-i-s') . '.json';
    
    file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
    
    return $backup_file;
}

/**
 * Execute the uninstall process
 */
if (!parfume_catalog_should_skip_uninstall()) {
    // Optional: Create backup before uninstalling
    if (defined('PARFUME_CATALOG_BACKUP_ON_UNINSTALL') && PARFUME_CATALOG_BACKUP_ON_UNINSTALL) {
        parfume_catalog_create_backup();
    }
    
    // Run the main uninstall function
    parfume_catalog_uninstall();
    
    // Final message (for debugging)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Catalog Plugin: All data has been removed from the database.');
    }
} else {
    // Log that uninstall was skipped
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Catalog Plugin: Uninstall was skipped due to debug settings.');
    }
}

// Clear any remaining output
if (ob_get_length()) {
    ob_end_clean();
}