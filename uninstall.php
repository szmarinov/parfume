<?php
/**
 * Uninstall Handler
 * 
 * Fired when the plugin is uninstalled
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Only proceed if user has permission
 */
if (!current_user_can('delete_plugins')) {
    exit;
}

/**
 * Check if data should be deleted
 */
$settings = get_option('parfume_reviews_settings', []);
$delete_data = isset($settings['delete_data_on_uninstall']) ? $settings['delete_data_on_uninstall'] : false;

if (!$delete_data) {
    // Don't delete data - just clean up temporary options
    delete_option('parfume_reviews_flush_rewrite_rules');
    flush_rewrite_rules();
    exit;
}

/**
 * Delete all parfume posts
 */
function parfume_reviews_delete_posts() {
    global $wpdb;
    
    // Get all parfume post IDs
    $post_ids = $wpdb->get_col(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'parfume'"
    );
    
    // Delete posts and their meta
    foreach ($post_ids as $post_id) {
        // Delete post meta
        $wpdb->delete(
            $wpdb->postmeta,
            ['post_id' => $post_id],
            ['%d']
        );
        
        // Delete post
        $wpdb->delete(
            $wpdb->posts,
            ['ID' => $post_id],
            ['%d']
        );
    }
    
    return count($post_ids);
}

/**
 * Delete all taxonomies and terms
 */
function parfume_reviews_delete_taxonomies() {
    global $wpdb;
    
    $taxonomies = [
        'marki',
        'gender',
        'aroma_type',
        'season',
        'intensity',
        'notes',
        'perfumer'
    ];
    
    foreach ($taxonomies as $taxonomy) {
        // Get all term IDs for this taxonomy
        $term_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
                $taxonomy
            )
        );
        
        // Delete term relationships
        $wpdb->delete(
            $wpdb->term_taxonomy,
            ['taxonomy' => $taxonomy],
            ['%s']
        );
        
        // Delete terms if they don't belong to other taxonomies
        foreach ($term_ids as $term_id) {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE term_id = %d",
                    $term_id
                )
            );
            
            if ($count == 0) {
                $wpdb->delete(
                    $wpdb->terms,
                    ['term_id' => $term_id],
                    ['%d']
                );
                
                $wpdb->delete(
                    $wpdb->termmeta,
                    ['term_id' => $term_id],
                    ['%d']
                );
            }
        }
    }
}

/**
 * Delete all plugin options
 */
function parfume_reviews_delete_options() {
    global $wpdb;
    
    // Delete main options
    delete_option('parfume_reviews_version');
    delete_option('parfume_reviews_settings');
    delete_option('parfume_reviews_flush_rewrite_rules');
    
    // Delete any options that start with 'parfume_reviews_'
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'parfume\_reviews\_%'"
    );
}

/**
 * Delete scheduled events
 */
function parfume_reviews_delete_scheduled_events() {
    // Remove auto price update cron
    wp_clear_scheduled_hook('parfume_reviews_auto_update_prices');
    
    // Remove any other scheduled events
    $scheduled = _get_cron_array();
    
    if (!empty($scheduled)) {
        foreach ($scheduled as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'parfume_reviews_') === 0) {
                    wp_clear_scheduled_hook($hook);
                }
            }
        }
    }
}

/**
 * Delete user meta
 */
function parfume_reviews_delete_user_meta() {
    global $wpdb;
    
    // Delete any user meta that starts with 'parfume_reviews_'
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'parfume\_reviews\_%'"
    );
}

/**
 * Delete transients
 */
function parfume_reviews_delete_transients() {
    global $wpdb;
    
    // Delete transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '\_transient\_parfume\_reviews\_%' 
         OR option_name LIKE '\_transient\_timeout\_parfume\_reviews\_%'"
    );
}

/**
 * Execute uninstall
 */
try {
    // Delete posts
    $deleted_posts = parfume_reviews_delete_posts();
    
    // Delete taxonomies
    parfume_reviews_delete_taxonomies();
    
    // Delete options
    parfume_reviews_delete_options();
    
    // Delete scheduled events
    parfume_reviews_delete_scheduled_events();
    
    // Delete user meta
    parfume_reviews_delete_user_meta();
    
    // Delete transients
    parfume_reviews_delete_transients();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log uninstall if debug enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Parfume Reviews: Uninstalled successfully. Deleted %d posts.',
            $deleted_posts
        ));
    }
    
} catch (Exception $e) {
    // Log error if debug enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Parfume Reviews Uninstall Error: ' . $e->getMessage());
    }
}