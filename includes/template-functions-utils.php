<?php
/**
 * Template Functions - Utility Functions
 * Помощни функции за проверки, валидации и utilities
 * РЕВИЗИРАНА ВЕРСИЯ: Пълна функционалност с всички utility функции
 * 
 * Файл: includes/template-functions-utils.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PAGE TYPE CHECKING FUNCTIONS
 * Функции за проверка на типа страница
 */

/**
 * Проверява дали сме на парфюмна страница
 */
if (!function_exists('parfume_reviews_is_parfume_page')) {
    function parfume_reviews_is_parfume_page() {
        return is_singular('parfume') || 
               is_singular('parfume_blog') ||
               is_post_type_archive('parfume') || 
               is_post_type_archive('parfume_blog') ||
               is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
    }
}

/**
 * Проверява дали сме на архив страница на парфюми
 */
if (!function_exists('parfume_reviews_is_parfume_archive')) {
    function parfume_reviews_is_parfume_archive() {
        return is_post_type_archive('parfume') || 
               is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
    }
}

/**
 * Проверява дали сме на single парфюм страница
 */
if (!function_exists('parfume_reviews_is_single_parfume')) {
    function parfume_reviews_is_single_parfume() {
        return is_singular('parfume');
    }
}

/**
 * Проверява дали сме на taxonomy страница
 */
if (!function_exists('parfume_reviews_is_parfume_taxonomy')) {
    function parfume_reviews_is_parfume_taxonomy($taxonomy = null) {
        $parfume_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        
        if ($taxonomy) {
            return is_tax($taxonomy);
        }
        
        return is_tax($parfume_taxonomies);
    }
}

/**
 * TAXONOMY UTILITY FUNCTIONS
 * Функции за работа с таксономии
 */

/**
 * Получава всички поддържани таксономии
 */
if (!function_exists('parfume_reviews_get_supported_taxonomies')) {
    function parfume_reviews_get_supported_taxonomies() {
        return array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    }
}

/**
 * Проверява дали таксономия е поддържана
 */
if (!function_exists('parfume_reviews_is_supported_taxonomy')) {
    function parfume_reviews_is_supported_taxonomy($taxonomy) {
        return in_array($taxonomy, parfume_reviews_get_supported_taxonomies());
    }
}

/**
 * Получава името на таксономия за показване
 */
if (!function_exists('parfume_reviews_get_taxonomy_label')) {
    function parfume_reviews_get_taxonomy_label($taxonomy) {
        $labels = array(
            'gender' => __('Пол', 'parfume-reviews'),
            'aroma_type' => __('Тип аромат', 'parfume-reviews'),
            'marki' => __('Марка', 'parfume-reviews'),
            'season' => __('Сезон', 'parfume-reviews'),
            'intensity' => __('Интензивност', 'parfume-reviews'),
            'notes' => __('Нотки', 'parfume-reviews'),
            'perfumer' => __('Парфюмерист', 'parfume-reviews')
        );
        
        return $labels[$taxonomy] ?? $taxonomy;
    }
}

/**
 * Получава архивния URL на таксономия
 */
if (!function_exists('parfume_reviews_get_taxonomy_archive_url')) {
    function parfume_reviews_get_taxonomy_archive_url($taxonomy) {
        if (!parfume_reviews_is_supported_taxonomy($taxonomy)) {
            return false;
        }
        
        $url_settings = get_option('parfume_reviews_url_settings', array());
        $base_url = home_url('/');
        
        $taxonomy_slugs = array(
            'marki' => isset($url_settings['marki_slug']) ? $url_settings['marki_slug'] : 'marki',
            'gender' => isset($url_settings['gender_slug']) ? $url_settings['gender_slug'] : 'pol',
            'aroma_type' => isset($url_settings['aroma_type_slug']) ? $url_settings['aroma_type_slug'] : 'tip-aroma',
            'season' => isset($url_settings['season_slug']) ? $url_settings['season_slug'] : 'sezon',
            'intensity' => isset($url_settings['intensity_slug']) ? $url_settings['intensity_slug'] : 'intensivnost',
            'notes' => isset($url_settings['notes_slug']) ? $url_settings['notes_slug'] : 'noti',
            'perfumer' => isset($url_settings['perfumer_slug']) ? $url_settings['perfumer_slug'] : 'parfiumerist'
        );
        
        if (isset($taxonomy_slugs[$taxonomy])) {
            return $base_url . $taxonomy_slugs[$taxonomy] . '/';
        }
        
        return false;
    }
}

/**
 * DATA UTILITY FUNCTIONS
 * Функции за обработка на данни
 */

/**
 * Форматира цена за показване
 */
if (!function_exists('parfume_reviews_get_formatted_price')) {
    function parfume_reviews_get_formatted_price($price) {
        if (empty($price)) {
            return '';
        }
        
        // Извличаме числото от цената
        $price_number = parfume_reviews_extract_price_number($price);
        
        if ($price_number > 0) {
            return number_format($price_number, 2, '.', '') . ' лв.';
        }
        
        return $price;
    }
}

/**
 * Извлича числото от цена (премахва валута и други символи)
 */
if (!function_exists('parfume_reviews_extract_price_number')) {
    function parfume_reviews_extract_price_number($price) {
        if (empty($price)) {
            return 0;
        }
        
        // Премахваме всички символи освен цифри, точка и запетая
        $price = preg_replace('/[^\d.,]/', '', $price);
        
        // Заместваме запетая с точка за decimal separator
        $price = str_replace(',', '.', $price);
        
        return floatval($price);
    }
}

/**
 * Получава рейтинг на парфюм
 */
if (!function_exists('parfume_reviews_get_rating')) {
    function parfume_reviews_get_rating($post_id) {
        $rating = get_post_meta($post_id, 'parfume_rating', true);
        if (empty($rating)) {
            $rating = get_post_meta($post_id, '_parfume_rating', true);
        }
        if (empty($rating)) {
            $rating = get_post_meta($post_id, '_rating', true);
        }
        
        return parfume_reviews_sanitize_rating($rating);
    }
}

/**
 * Валидира и sanitize рейтинг
 */
if (!function_exists('parfume_reviews_sanitize_rating')) {
    function parfume_reviews_sanitize_rating($rating) {
        $rating = floatval($rating);
        
        if ($rating < 0) {
            return 0;
        } elseif ($rating > 5) {
            return 5;
        }
        
        return round($rating, 1);
    }
}

/**
 * Валидира и sanitize цена
 */
if (!function_exists('parfume_reviews_sanitize_price')) {
    function parfume_reviews_sanitize_price($price) {
        $price = parfume_reviews_extract_price_number($price);
        
        return floatval($price);
    }
}

/**
 * Получава магазините на парфюм
 */
if (!function_exists('parfume_reviews_get_parfume_stores')) {
    function parfume_reviews_get_parfume_stores($post_id) {
        $stores = get_post_meta($post_id, 'parfume_stores', true);
        
        if (empty($stores) || !is_array($stores)) {
            return array();
        }
        
        // Филтрираме и валидираме магазините
        $valid_stores = array();
        foreach ($stores as $store) {
            if (!empty($store['name']) && (!empty($store['url']) || !empty($store['affiliate_url']))) {
                $valid_stores[] = array(
                    'name' => sanitize_text_field($store['name']),
                    'url' => isset($store['url']) ? esc_url_raw($store['url']) : '',
                    'affiliate_url' => isset($store['affiliate_url']) ? esc_url_raw($store['affiliate_url']) : '',
                    'price' => isset($store['price']) ? parfume_reviews_sanitize_price($store['price']) : 0,
                    'promo_code' => isset($store['promo_code']) ? sanitize_text_field($store['promo_code']) : '',
                    'shipping_cost' => isset($store['shipping_cost']) ? parfume_reviews_sanitize_price($store['shipping_cost']) : 0,
                    'availability' => isset($store['availability']) ? sanitize_text_field($store['availability']) : 'in_stock'
                );
            }
        }
        
        return $valid_stores;
    }
}

/**
 * Получава най-ниската цена на парфюм
 */
if (!function_exists('parfume_reviews_get_lowest_price')) {
    function parfume_reviews_get_lowest_price($post_id) {
        $stores = parfume_reviews_get_parfume_stores($post_id);
        $lowest_price = 0;
        
        foreach ($stores as $store) {
            if (!empty($store['price']) && $store['price'] > 0) {
                if ($lowest_price == 0 || $store['price'] < $lowest_price) {
                    $lowest_price = $store['price'];
                }
            }
        }
        
        return $lowest_price;
    }
}

/**
 * QUERY UTILITY FUNCTIONS
 * Функции за заявки към базата данни
 */

/**
 * Получава популярни парфюми
 */
if (!function_exists('parfume_reviews_get_popular_parfumes')) {
    function parfume_reviews_get_popular_parfumes($limit = 10) {
        $cache_key = 'parfume_reviews_popular_parfumes_' . $limit;
        $popular = wp_cache_get($cache_key, 'parfume_reviews');
        
        if ($popular === false) {
            $args = array(
                'post_type' => 'parfume',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'meta_key' => 'parfume_views',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'no_found_rows' => true,
                'update_post_meta_cache' => false
            );
            
            $query = new WP_Query($args);
            $popular = $query->posts;
            
            wp_cache_set($cache_key, $popular, 'parfume_reviews', HOUR_IN_SECONDS);
        }
        
        return $popular;
    }
}

/**
 * Получава най-нови парфюми
 */
if (!function_exists('parfume_reviews_get_latest_parfumes')) {
    function parfume_reviews_get_latest_parfumes($limit = 10) {
        $cache_key = 'parfume_reviews_latest_parfumes_' . $limit;
        $latest = wp_cache_get($cache_key, 'parfume_reviews');
        
        if ($latest === false) {
            $args = array(
                'post_type' => 'parfume',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
                'update_post_meta_cache' => false
            );
            
            $query = new WP_Query($args);
            $latest = $query->posts;
            
            wp_cache_set($cache_key, $latest, 'parfume_reviews', HOUR_IN_SECONDS);
        }
        
        return $latest;
    }
}

/**
 * Получава случайни парфюми
 */
if (!function_exists('parfume_reviews_get_random_parfumes')) {
    function parfume_reviews_get_random_parfumes($limit = 10) {
        $args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'rand',
            'no_found_rows' => true,
            'update_post_meta_cache' => false
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
}

/**
 * Получава подобни парфюми на база таксономии
 */
if (!function_exists('parfume_reviews_get_similar_parfumes')) {
    function parfume_reviews_get_similar_parfumes($post_id, $limit = 5) {
        $cache_key = 'parfume_reviews_similar_' . $post_id . '_' . $limit;
        $similar = wp_cache_get($cache_key, 'parfume_reviews');
        
        if ($similar === false) {
            // Получаваме таксономии на текущия парфюм
            $taxonomies = array('marki', 'gender', 'aroma_type', 'notes');
            $tax_query = array('relation' => 'OR');
            
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
                if (!empty($terms) && !is_wp_error($terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $terms
                    );
                }
            }
            
            $args = array(
                'post_type' => 'parfume',
                'post_status' => 'publish',
                'posts_per_page' => $limit + 1, // +1 защото ще изключим текущия пост
                'post__not_in' => array($post_id),
                'tax_query' => $tax_query,
                'orderby' => 'rand',
                'no_found_rows' => true,
                'update_post_meta_cache' => false
            );
            
            $query = new WP_Query($args);
            $similar = array_slice($query->posts, 0, $limit);
            
            wp_cache_set($cache_key, $similar, 'parfume_reviews', HOUR_IN_SECONDS);
        }
        
        return $similar;
    }
}

/**
 * Получава статистики за парфюми
 */
if (!function_exists('parfume_reviews_get_parfume_stats')) {
    function parfume_reviews_get_parfume_stats() {
        $cache_key = 'parfume_reviews_stats';
        $stats = wp_cache_get($cache_key, 'parfume_reviews');
        
        if ($stats === false) {
            $parfume_count = wp_count_posts('parfume');
            $brand_count = wp_count_terms('marki');
            $perfumer_count = wp_count_terms('perfumer');
            $notes_count = wp_count_terms('notes');
            
            $stats = array(
                'total_parfumes' => $parfume_count->publish,
                'total_brands' => $brand_count,
                'total_perfumers' => $perfumer_count,
                'total_notes' => $notes_count,
                'last_updated' => current_time('mysql')
            );
            
            wp_cache_set($cache_key, $stats, 'parfume_reviews', DAY_IN_SECONDS);
        }
        
        return $stats;
    }
}

/**
 * Изчиства статистическите кешове
 */
if (!function_exists('parfume_reviews_clear_stats_cache')) {
    function parfume_reviews_clear_stats_cache() {
        wp_cache_delete('parfume_reviews_stats', 'parfume_reviews');
        
        // Изчистваме и други кешове
        $cache_keys = array(
            'parfume_reviews_popular_parfumes_10',
            'parfume_reviews_popular_parfumes_5',
            'parfume_reviews_latest_parfumes_10',
            'parfume_reviews_latest_parfumes_5'
        );
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'parfume_reviews');
        }
    }
}

/**
 * PERMISSION UTILITY FUNCTIONS
 * Функции за проверка на права
 */

/**
 * Проверява дали потребителят може да редактира парфюм ревюта
 */
if (!function_exists('parfume_reviews_user_can_edit_reviews')) {
    function parfume_reviews_user_can_edit_reviews() {
        return current_user_can('edit_posts') || current_user_can('manage_options');
    }
}

/**
 * Проверява дали потребителят може да управлява плъгина
 */
if (!function_exists('parfume_reviews_user_can_manage_plugin')) {
    function parfume_reviews_user_can_manage_plugin() {
        return current_user_can('manage_options');
    }
}

/**
 * CONTENT UTILITY FUNCTIONS
 * Функции за обработка на съдържание
 */

/**
 * Получава URL на първото изображение от content
 */
if (!function_exists('parfume_reviews_get_first_image_from_content')) {
    function parfume_reviews_get_first_image_from_content($content) {
        preg_match_all('/<img[^>]+>/i', $content, $matches);
        
        if (!empty($matches[0])) {
            preg_match('/src="([^"]+)"/i', $matches[0][0], $src_matches);
            if (!empty($src_matches[1])) {
                return $src_matches[1];
            }
        }
        
        return false;
    }
}

/**
 * Форматира срок за издръжливост
 */
if (!function_exists('parfume_reviews_format_longevity')) {
    function parfume_reviews_format_longevity($hours) {
        $hours = intval($hours);
        
        if ($hours < 1) {
            return __('Под 1 час', 'parfume-reviews');
        } elseif ($hours == 1) {
            return __('1 час', 'parfume-reviews');
        } elseif ($hours < 24) {
            return sprintf(__('%d часа', 'parfume-reviews'), $hours);
        } else {
            $days = floor($hours / 24);
            $remaining_hours = $hours % 24;
            
            if ($remaining_hours == 0) {
                return sprintf(_n('%d ден', '%d дни', $days, 'parfume-reviews'), $days);
            } else {
                return sprintf(__('%d дни и %d часа', 'parfume-reviews'), $days, $remaining_hours);
            }
        }
    }
}

/**
 * STORE UTILITY FUNCTIONS
 * Функции за работа с магазини
 */

/**
 * Проверява дали парфюм е наличен
 */
if (!function_exists('parfume_reviews_is_available')) {
    function parfume_reviews_is_available($post_id) {
        $stores = parfume_reviews_get_parfume_stores($post_id);
        
        foreach ($stores as $store) {
            if (isset($store['availability']) && $store['availability'] === 'in_stock') {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Получава информация за доставка
 */
if (!function_exists('parfume_reviews_get_shipping_info')) {
    function parfume_reviews_get_shipping_info($post_id) {
        $stores = parfume_reviews_get_parfume_stores($post_id);
        $shipping_info = array();
        
        foreach ($stores as $store) {
            if (isset($store['shipping_cost'])) {
                $shipping_info[] = array(
                    'store' => $store['name'],
                    'cost' => $store['shipping_cost'],
                    'free_shipping_threshold' => isset($store['free_shipping_threshold']) ? $store['free_shipping_threshold'] : 0
                );
            }
        }
        
        return $shipping_info;
    }
}

/**
 * Получава най-евтината доставка
 */
if (!function_exists('parfume_reviews_get_cheapest_shipping')) {
    function parfume_reviews_get_cheapest_shipping($post_id) {
        $shipping_info = parfume_reviews_get_shipping_info($post_id);
        $cheapest = null;
        
        foreach ($shipping_info as $shipping) {
            if ($cheapest === null || $shipping['cost'] < $cheapest['cost']) {
                $cheapest = $shipping;
            }
        }
        
        return $cheapest;
    }
}

/**
 * Проверява дали има промоция
 */
if (!function_exists('parfume_reviews_has_promotion')) {
    function parfume_reviews_has_promotion($post_id) {
        $stores = parfume_reviews_get_parfume_stores($post_id);
        
        foreach ($stores as $store) {
            if (!empty($store['promo_code']) || !empty($store['discount_percentage'])) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * URL UTILITY FUNCTIONS
 * Функции за работа с URL-и
 */

/**
 * Получава красив URL за парфюм
 */
if (!function_exists('parfume_reviews_get_parfume_url')) {
    function parfume_reviews_get_parfume_url($post_id) {
        return get_permalink($post_id);
    }
}

/**
 * Получава URL за сравняване
 */
if (!function_exists('parfume_reviews_get_comparison_url')) {
    function parfume_reviews_get_comparison_url($parfume_ids = array()) {
        $base_url = home_url('/parfiumi/sravnyavane/');
        
        if (!empty($parfume_ids)) {
            $base_url .= implode(',', array_map('intval', $parfume_ids)) . '/';
        }
        
        return $base_url;
    }
}

/**
 * VALIDATION UTILITY FUNCTIONS
 * Функции за валидация
 */

/**
 * Валидира post ID за парфюм
 */
if (!function_exists('parfume_reviews_validate_parfume_id')) {
    function parfume_reviews_validate_parfume_id($post_id) {
        $post_id = absint($post_id);
        
        if ($post_id <= 0) {
            return false;
        }
        
        $post = get_post($post_id);
        
        return $post && $post->post_type === 'parfume' && $post->post_status === 'publish';
    }
}

/**
 * Валидира таксономия term
 */
if (!function_exists('parfume_reviews_validate_taxonomy_term')) {
    function parfume_reviews_validate_taxonomy_term($term_id, $taxonomy) {
        if (!parfume_reviews_is_supported_taxonomy($taxonomy)) {
            return false;
        }
        
        $term = get_term($term_id, $taxonomy);
        
        return $term && !is_wp_error($term);
    }
}

/**
 * MISC UTILITY FUNCTIONS
 * Разни помощни функции
 */

/**
 * Получава текущия URL на страницата
 */
if (!function_exists('parfume_reviews_get_current_url')) {
    function parfume_reviews_get_current_url() {
        global $wp;
        return home_url($wp->request);
    }
}

/**
 * Генерира уникален ID за елемент
 */
if (!function_exists('parfume_reviews_generate_unique_id')) {
    function parfume_reviews_generate_unique_id($prefix = 'parfume') {
        return $prefix . '_' . uniqid() . '_' . wp_rand(1000, 9999);
    }
}

/**
 * Конвертира slug към title
 */
if (!function_exists('parfume_reviews_slug_to_title')) {
    function parfume_reviews_slug_to_title($slug) {
        return ucwords(str_replace(array('-', '_'), ' ', $slug));
    }
}

/**
 * Създава excerpt със специфична дължина
 */
if (!function_exists('parfume_reviews_create_excerpt')) {
    function parfume_reviews_create_excerpt($content, $length = 55) {
        $excerpt = wp_strip_all_tags($content);
        $excerpt = wp_trim_words($excerpt, $length, '...');
        return $excerpt;
    }
}

/**
 * DEBUG UTILITY FUNCTIONS
 * Функции за debugging
 */

/**
 * Прави debug dump на променлива
 */
if (!function_exists('parfume_reviews_debug_dump')) {
    function parfume_reviews_debug_dump($var, $label = '') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $output = $label ? "DEBUG ({$label}): " : "DEBUG: ";
        $output .= print_r($var, true);
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log($output);
        } else {
            error_log($output);
        }
    }
}

/**
 * Проверява дали сме в debug режим
 */
if (!function_exists('parfume_reviews_is_debug_mode')) {
    function parfume_reviews_is_debug_mode() {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
}

/**
 * CACHE UTILITY FUNCTIONS
 * Функции за работа с кеш
 */

/**
 * Получава данни от кеш с fallback
 */
if (!function_exists('parfume_reviews_get_cached_data')) {
    function parfume_reviews_get_cached_data($key, $callback, $expiration = HOUR_IN_SECONDS) {
        $data = wp_cache_get($key, 'parfume_reviews');
        
        if ($data === false && is_callable($callback)) {
            $data = call_user_func($callback);
            wp_cache_set($key, $data, 'parfume_reviews', $expiration);
        }
        
        return $data;
    }
}

/**
 * Изчиства всички парфюмни кешове
 */
if (!function_exists('parfume_reviews_clear_all_caches')) {
    function parfume_reviews_clear_all_caches() {
        parfume_reviews_clear_stats_cache();
        wp_cache_flush_group('parfume_reviews');
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log('All parfume caches cleared');
        }
    }
}

/**
 * LEGACY COMPATIBILITY FUNCTIONS
 * Функции за backward compatibility
 */

// Алтернативни имена за backward compatibility
if (!function_exists('is_parfume_page')) {
    function is_parfume_page() {
        return parfume_reviews_is_parfume_page();
    }
}

if (!function_exists('get_parfume_rating')) {
    function get_parfume_rating($post_id) {
        return parfume_reviews_get_rating($post_id);
    }
}

if (!function_exists('get_parfume_price')) {
    function get_parfume_price($post_id) {
        return parfume_reviews_get_lowest_price($post_id);
    }
}