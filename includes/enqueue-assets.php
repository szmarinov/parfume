<?php
/**
 * Enqueue Assets Helper
 * 
 * Helper file for loading CSS and JS files
 * 
 * @package ParfumeReviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue frontend styles
 */
function parfume_reviews_enqueue_styles() {
    // Main CSS
    wp_enqueue_style(
        'parfume-reviews-main',
        PARFUME_REVIEWS_URL . 'assets/css/main.css',
        [],
        PARFUME_REVIEWS_VERSION
    );
    
    // Home page CSS
    if (is_front_page() || is_page_template('front-page.php')) {
        wp_enqueue_style(
            'parfume-reviews-home',
            PARFUME_REVIEWS_URL . 'assets/css/home.css',
            ['parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION
        );
    }
    
    // Archive CSS (for archive and taxonomy pages)
    if (is_post_type_archive('parfume') || is_tax(['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'])) {
        wp_enqueue_style(
            'parfume-reviews-archive',
            PARFUME_REVIEWS_URL . 'assets/css/archive.css',
            ['parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION
        );
    }
    
    // Single parfume CSS
    if (is_singular('parfume')) {
        wp_enqueue_style(
            'parfume-reviews-single',
            PARFUME_REVIEWS_URL . 'assets/css/single-parfume.css',
            ['parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION
        );
    }
    
    // Comparison CSS (if comparison is enabled)
    $settings = get_option('parfume_reviews_settings', []);
    if (isset($settings['enable_comparison']) && $settings['enable_comparison']) {
        wp_enqueue_style(
            'parfume-reviews-comparison',
            PARFUME_REVIEWS_URL . 'assets/css/comparison.css',
            ['parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'parfume_reviews_enqueue_styles');

/**
 * Enqueue frontend scripts
 */
function parfume_reviews_enqueue_scripts() {
    // Main JS
    wp_enqueue_script(
        'parfume-reviews-main',
        PARFUME_REVIEWS_URL . 'assets/js/main.js',
        ['jquery'],
        PARFUME_REVIEWS_VERSION,
        true
    );
    
    // Single parfume JS
    if (is_singular('parfume')) {
        wp_enqueue_script(
            'parfume-reviews-single',
            PARFUME_REVIEWS_URL . 'assets/js/single-parfume.js',
            ['jquery', 'parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('parfume-reviews-single', 'parfumeSingle', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_single_nonce'),
            'strings' => [
                'updating' => __('Обновяване...', 'parfume-reviews'),
                'updated' => __('Обновено', 'parfume-reviews'),
                'error' => __('Грешка при обновяване', 'parfume-reviews')
            ]
        ]);
    }
    
    // Archive JS (for filtering)
    if (is_post_type_archive('parfume') || is_tax(['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'])) {
        wp_enqueue_script(
            'parfume-reviews-archive',
            PARFUME_REVIEWS_URL . 'assets/js/archive.js',
            ['jquery', 'parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Localize script for AJAX filtering
        wp_localize_script('parfume-reviews-archive', 'parfumeArchive', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_archive_nonce')
        ]);
    }
    
    // Comparison JS (if enabled)
    $settings = get_option('parfume_reviews_settings', []);
    if (isset($settings['enable_comparison']) && $settings['enable_comparison']) {
        wp_enqueue_script(
            'parfume-reviews-comparison',
            PARFUME_REVIEWS_URL . 'assets/js/comparison.js',
            ['jquery', 'parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('parfume-reviews-comparison', 'parfumeComparison', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_comparison_nonce'),
            'maxItems' => isset($settings['comparison_max_items']) ? intval($settings['comparison_max_items']) : 4,
            'strings' => [
                'added' => __('Добавено за сравнение', 'parfume-reviews'),
                'removed' => __('Премахнато от сравнение', 'parfume-reviews'),
                'maxReached' => __('Достигнат е максималният брой за сравнение', 'parfume-reviews'),
                'compare' => __('Сравни', 'parfume-reviews'),
                'clear' => __('Изчисти', 'parfume-reviews')
            ]
        ]);
    }
    
    // Home page JS (for interactive elements)
    if (is_front_page()) {
        wp_enqueue_script(
            'parfume-reviews-home',
            PARFUME_REVIEWS_URL . 'assets/js/home.js',
            ['jquery', 'parfume-reviews-main'],
            PARFUME_REVIEWS_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'parfume_reviews_enqueue_scripts');

/**
 * Add inline styles for custom colors (from settings)
 */
function parfume_reviews_inline_styles() {
    $settings = get_option('parfume_reviews_settings', []);
    
    // Get custom colors if set
    $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#8b7355';
    $secondary_color = isset($settings['secondary_color']) ? $settings['secondary_color'] : '#d4a574';
    $accent_color = isset($settings['accent_color']) ? $settings['accent_color'] : '#c9a882';
    
    $custom_css = "
        :root {
            --pr-primary: {$primary_color};
            --pr-secondary: {$secondary_color};
            --pr-accent: {$accent_color};
        }
    ";
    
    wp_add_inline_style('parfume-reviews-main', $custom_css);
}
add_action('wp_enqueue_scripts', 'parfume_reviews_inline_styles');

/**
 * Enqueue admin styles
 */
function parfume_reviews_admin_enqueue_styles($hook) {
    // Admin settings CSS
    if (strpos($hook, 'parfume') !== false || strpos($hook, 'parfume-reviews') !== false) {
        wp_enqueue_style(
            'parfume-reviews-admin-settings',
            PARFUME_REVIEWS_URL . 'assets/css/admin-settings.css',
            [],
            PARFUME_REVIEWS_VERSION
        );
    }
    
    // Stores admin CSS
    if (strpos($hook, 'stores') !== false) {
        wp_enqueue_style(
            'parfume-reviews-admin-stores',
            PARFUME_REVIEWS_URL . 'assets/css/admin-stores-page.css',
            [],
            PARFUME_REVIEWS_VERSION
        );
    }
}
add_action('admin_enqueue_scripts', 'parfume_reviews_admin_enqueue_styles');

/**
 * Enqueue admin scripts
 */
function parfume_reviews_admin_enqueue_scripts($hook) {
    // Admin settings JS
    if (strpos($hook, 'parfume') !== false) {
        wp_enqueue_script(
            'parfume-reviews-admin-settings',
            PARFUME_REVIEWS_URL . 'assets/js/admin-settings.js',
            ['jquery'],
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-reviews-admin-settings', 'parfumeAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_nonce')
        ]);
    }
}
add_action('admin_enqueue_scripts', 'parfume_reviews_admin_enqueue_scripts');

/**
 * Add Dashicons to frontend for icons
 */
function parfume_reviews_load_dashicons() {
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'parfume_reviews_load_dashicons');