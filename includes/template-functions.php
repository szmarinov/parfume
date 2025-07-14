<?php
/**
 * Template Functions - Main Loader File
 * Зарежда всички template function подфайлове
 * 
 * Файл: includes/template-functions.php
 * ЗАМЕСТИТЕЛ ЗА СТАРИЯ ФАЙЛ - БЕЗ ДУБЛИРАНИ ФУНКЦИИ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ВАЖНО: Този файл е заместен с модулна структура
 * Всички функции сега се зареждат от подфайлове
 */

/**
 * Зарежда всички template function файлове
 * ВАЖНО: Тази функция само зарежда файлове, не дефинира функции
 */
function parfume_reviews_load_template_functions() {
    $template_function_files = array(
        'includes/template-functions-utils.php',    // Utility функции (проверки, валидации)
        'includes/template-functions-display.php',  // Display функции (карточки, UI елементи)
        'includes/template-functions-filters.php'   // Filter функции (филтри, URL-и)
    );
    
    foreach ($template_function_files as $file) {
        $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
            
            // Debug лог ако е включен - използваме функцията от основния файл
            if (function_exists('parfume_reviews_debug_log') && defined('WP_DEBUG') && WP_DEBUG) {
                parfume_reviews_debug_log("Template function file loaded: {$file}");
            }
        } else {
            // Логираме грешка ако файлът липсва
            if (function_exists('parfume_reviews_debug_log') && defined('WP_DEBUG') && WP_DEBUG) {
                parfume_reviews_debug_log("Missing template function file: {$file}", 'error');
            }
        }
    }
}

// Зареждаме всички template function файлове
parfume_reviews_load_template_functions();

/**
 * BACKWARD COMPATIBILITY ФУНКЦИИ
 * Тези функции запазват съвместимостта със стария код
 * ВАЖНО: Проверяваме дали функциите съществуват преди да ги дефинираме
 */

if (!function_exists('show_parfume_card')) {
    /**
     * @deprecated Използвайте parfume_reviews_display_parfume_card()
     * Запазено за backward compatibility
     */
    function show_parfume_card($post_id) {
        return parfume_reviews_display_parfume_card($post_id);
    }
}

if (!function_exists('get_parfume_active_filters')) {
    /**
     * @deprecated Използвайте parfume_reviews_get_active_filters()
     * Запазено за backward compatibility
     */
    function get_parfume_active_filters() {
        return parfume_reviews_get_active_filters();
    }
}

if (!function_exists('build_parfume_filter_url')) {
    /**
     * @deprecated Използвайте parfume_reviews_build_filter_url()
     * Запазено за backward compatibility
     */
    function build_parfume_filter_url($filters = array(), $base_url = '') {
        return parfume_reviews_build_filter_url($filters, $base_url);
    }
}

if (!function_exists('is_parfume_page')) {
    /**
     * @deprecated Използвайте parfume_reviews_is_parfume_page()
     * Запазено за backward compatibility
     */
    function is_parfume_page() {
        return parfume_reviews_is_parfume_page();
    }
}

/**
 * НОВИ ПОМОЩНИ ФУНКЦИИ ЗА ЦЕНТРАЛИЗИРАНО УПРАВЛЕНИЕ
 */

/**
 * Получава списък с всички налични template функции
 */
function parfume_reviews_get_available_template_functions() {
    return array(
        'utils' => array(
            'parfume_reviews_is_parfume_page',
            'parfume_reviews_is_parfume_archive',
            'parfume_reviews_is_single_parfume',
            'parfume_reviews_is_parfume_taxonomy',
            'parfume_reviews_get_supported_taxonomies',
            'parfume_reviews_is_supported_taxonomy',
            'parfume_reviews_get_taxonomy_label',
            'parfume_reviews_get_taxonomy_archive_url',
            'parfume_reviews_get_formatted_price',
            'parfume_reviews_get_rating',
            'parfume_reviews_get_parfume_stores',
            'parfume_reviews_get_lowest_price',
            'parfume_reviews_get_popular_parfumes',
            'parfume_reviews_get_latest_parfumes',
            'parfume_reviews_get_random_parfumes',
            'parfume_reviews_get_similar_parfumes',
            'parfume_reviews_get_parfume_stats',
            'parfume_reviews_clear_stats_cache',
            'parfume_reviews_sanitize_rating',
            'parfume_reviews_sanitize_price',
            'parfume_reviews_user_can_edit_reviews',
            'parfume_reviews_user_can_manage_plugin',
            'parfume_reviews_get_first_image_from_content',
            'parfume_reviews_format_longevity',
            'parfume_reviews_extract_price_number',
            'parfume_reviews_is_available',
            'parfume_reviews_get_shipping_info',
            'parfume_reviews_get_cheapest_shipping',
            'parfume_reviews_has_promotion'
        ),
        'display' => array(
            'parfume_reviews_display_parfume_card',
            'parfume_reviews_display_star_rating',
            'parfume_reviews_get_rating_stars',
            'parfume_reviews_display_stars',
            'parfume_reviews_display_parfumes_grid',
            'parfume_reviews_display_pagination',
            'parfume_reviews_display_breadcrumb',
            'parfume_reviews_display_archive_header',
            'parfume_reviews_display_term_image',
            'parfume_reviews_display_loading_indicator',
            'parfume_reviews_get_comparison_button'
        ),
        'filters' => array(
            'parfume_reviews_get_active_filters',
            'parfume_reviews_build_filter_url',
            'parfume_reviews_get_remove_filter_url',
            'parfume_reviews_get_add_filter_url',
            'parfume_reviews_is_filter_active',
            'parfume_reviews_display_active_filters',
            'parfume_reviews_display_filter_form',
            'parfume_reviews_display_sort_options'
        )
    );
}

/**
 * Проверява дали всички template функции са заредени правилно
 */
function parfume_reviews_validate_template_functions() {
    $issues = array();
    $functions = parfume_reviews_get_available_template_functions();
    
    foreach ($functions as $category => $function_list) {
        foreach ($function_list as $function_name) {
            if (!function_exists($function_name)) {
                $issues[] = "Missing function: {$function_name} (category: {$category})";
            }
        }
    }
    
    if (!empty($issues) && function_exists('parfume_reviews_debug_log')) {
        parfume_reviews_debug_log("Template function validation issues: " . implode(', ', $issues), 'error');
        return false;
    }
    
    return true;
}

/**
 * Показва информация за template функциите (за debug)
 */
function parfume_reviews_debug_template_functions() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['parfume_debug']) && $_GET['parfume_debug'] === 'template_functions') {
        echo '<div class="notice notice-info"><p><strong>Parfume Reviews Template Functions Debug:</strong></p>';
        
        $functions = parfume_reviews_get_available_template_functions();
        $validation_result = parfume_reviews_validate_template_functions();
        
        echo '<p>Validation result: ' . ($validation_result ? '✅ All OK' : '❌ Issues found') . '</p>';
        
        foreach ($functions as $category => $function_list) {
            echo '<h4>' . ucfirst($category) . ' Functions (' . count($function_list) . '):</h4>';
            echo '<ul>';
            foreach ($function_list as $function_name) {
                $exists = function_exists($function_name);
                echo '<li>' . ($exists ? '✅' : '❌') . ' ' . $function_name . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
}

// Hook за debug информация
add_action('admin_notices', 'parfume_reviews_debug_template_functions');

/**
 * ENQUEUE ФУНКЦИИ ЗА FRONTEND СТИЛОВЕ И СКРИПТОВЕ
 */

/**
 * Зарежда CSS и JS файлове за template функциите
 */
function parfume_reviews_enqueue_template_assets() {
    // Само на парфюмни страници
    if (!function_exists('parfume_reviews_is_parfume_page') || !parfume_reviews_is_parfume_page()) {
        return;
    }
    
    $plugin_version = defined('PARFUME_REVIEWS_VERSION') ? PARFUME_REVIEWS_VERSION : '1.0.0';
    
    // CSS за template елементи
    if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/templates.css')) {
        wp_enqueue_style(
            'parfume-reviews-templates',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/templates.css',
            array(),
            $plugin_version
        );
    }
    
    // JS за интерактивни елементи
    if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/templates.js')) {
        wp_enqueue_script(
            'parfume-reviews-templates',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/templates.js',
            array('jquery'),
            $plugin_version,
            true
        );
        
        // Локализация за JS
        wp_localize_script('parfume-reviews-templates', 'parfumeReviewsTemplates', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_reviews_template_nonce'),
            'strings' => array(
                'loading' => __('Зареждане...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
                'addedToComparison' => __('Добавен за сравняване', 'parfume-reviews'),
                'removedFromComparison' => __('Премахнат от сравняването', 'parfume-reviews')
            )
        ));
    }
}

add_action('wp_enqueue_scripts', 'parfume_reviews_enqueue_template_assets');

/**
 * Добавя body classes за template страници
 */
function parfume_reviews_add_template_body_classes($classes) {
    if (function_exists('parfume_reviews_is_parfume_page') && parfume_reviews_is_parfume_page()) {
        $classes[] = 'parfume-reviews-page';
        
        if (function_exists('parfume_reviews_is_single_parfume') && parfume_reviews_is_single_parfume()) {
            $classes[] = 'single-parfume';
        } elseif (function_exists('parfume_reviews_is_parfume_archive') && parfume_reviews_is_parfume_archive()) {
            $classes[] = 'parfume-archive';
            
            if (is_tax()) {
                $queried_object = get_queried_object();
                if ($queried_object && isset($queried_object->taxonomy)) {
                    $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
                }
            }
        }
    }
    
    return $classes;
}

add_filter('body_class', 'parfume_reviews_add_template_body_classes');

/**
 * CLEANUP ФУНКЦИИ
 */

/**
 * Почиства кешове свързани с template функциите
 */
function parfume_reviews_clear_template_caches() {
    if (function_exists('parfume_reviews_clear_stats_cache')) {
        parfume_reviews_clear_stats_cache();
    }
    
    // Изчистваме object cache
    wp_cache_flush_group('parfume_reviews');
    
    if (function_exists('parfume_reviews_debug_log')) {
        parfume_reviews_debug_log('Template caches cleared');
    }
}

/**
 * Hook за изчистване на кешове при запазване на парфюм
 */
add_action('save_post_parfume', 'parfume_reviews_clear_template_caches');

/**
 * Hook за изчистване на кешове при промяна на term
 */
add_action('edit_term', function($term_id, $tt_id, $taxonomy) {
    if (function_exists('parfume_reviews_is_supported_taxonomy') && parfume_reviews_is_supported_taxonomy($taxonomy)) {
        parfume_reviews_clear_template_caches();
    }
}, 10, 3);

/**
 * RUNTIME ПРОВЕРКИ
 */

// Валидираме template функциите при зареждане
add_action('init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        parfume_reviews_validate_template_functions();
    }
}, 99);