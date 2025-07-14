<?php
/**
 * Template Functions - Utility Functions
 * Помощни функции за проверки, валидации и utilities
 * 
 * Файл: includes/template-functions-utils.php
 * РЕВИЗИРАНА ВЕРСИЯ - ПЪЛЕН НАБОР ОТ UTILITY ФУНКЦИИ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * РАЗДЕЛ 1: ФУНКЦИИ ЗА ПРОВЕРКА НА СТРАНИЦИ
 */

/**
 * Проверява дали сме на парфюмна страница
 * ВАЖНО: Използва се в много места в кода
 */
function parfume_reviews_is_parfume_page() {
    return is_singular('parfume') || 
           is_post_type_archive('parfume') || 
           is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
}

/**
 * Проверява дали сме на архив страница на парфюми
 */
function parfume_reviews_is_parfume_archive() {
    return is_post_type_archive('parfume') || 
           is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
}

/**
 * Проверява дали сме на single парфюм страница
 */
function parfume_reviews_is_single_parfume() {
    return is_singular('parfume');
}

/**
 * Проверява дали сме на taxonomy страница
 */
function parfume_reviews_is_parfume_taxonomy($taxonomy = null) {
    $parfume_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
    
    if ($taxonomy) {
        return is_tax($taxonomy);
    }
    
    return is_tax($parfume_taxonomies);
}

/**
 * РАЗДЕЛ 2: ФУНКЦИИ ЗА РАБОТА С ТАКСОНОМИИ
 */

/**
 * Получава всички поддържани таксономии
 * ВАЖНО: Централно място за дефиниране на таксономии
 */
function parfume_reviews_get_supported_taxonomies() {
    return array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
}

/**
 * Проверява дали таксономия е поддържана
 */
function parfume_reviews_is_supported_taxonomy($taxonomy) {
    return in_array($taxonomy, parfume_reviews_get_supported_taxonomies());
}

/**
 * Получава името на таксономия за показване
 */
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

/**
 * Получава архивния URL на таксономия
 */
function parfume_reviews_get_taxonomy_archive_url($taxonomy) {
    if (!parfume_reviews_is_supported_taxonomy($taxonomy)) {
        return false;
    }
    
    $settings = get_option('parfume_reviews_settings', array());
    $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
    
    // За специални таксономии като marki, notes, perfumer
    if (in_array($taxonomy, array('marki', 'notes', 'perfumer'))) {
        return home_url("/{$parfume_slug}/{$taxonomy}/");
    }
    
    return false;
}

/**
 * РАЗДЕЛ 3: ФУНКЦИИ ЗА РАБОТА С ЦЕНИ
 */

/**
 * Форматира цена за показване
 * ВАЖНО: Централна функция за форматиране на цени
 */
function parfume_reviews_get_formatted_price($price, $currency = 'лв.') {
    if (empty($price) || !is_numeric($price)) {
        return '';
    }
    
    $price = floatval($price);
    if ($price <= 0) {
        return '';
    }
    
    return number_format($price, 2, '.', '') . ' ' . $currency;
}

/**
 * Извлича числова стойност от цена
 */
function parfume_reviews_extract_price_number($price) {
    if (is_numeric($price)) {
        return floatval($price);
    }
    
    // Премахва всички символи освен числа, точки и запетаи
    $price = preg_replace('/[^\d.,]/', '', $price);
    
    return floatval($price);
}

/**
 * РАЗДЕЛ 4: ФУНКЦИИ ЗА РАБОТА С РЕЙТИНГИ
 */

/**
 * Получава рейтинг на парфюм
 * ВАЖНО: Централна функция за получаване на рейтинг
 */
function parfume_reviews_get_rating($post_id) {
    $rating = get_post_meta($post_id, '_parfume_rating', true);
    return !empty($rating) ? floatval($rating) : 0;
}

/**
 * Валидира рейтинг
 */
function parfume_reviews_sanitize_rating($rating) {
    $rating = floatval($rating);
    
    if ($rating < 0) {
        return 0;
    }
    
    if ($rating > 5) {
        return 5;
    }
    
    return round($rating, 1);
}

/**
 * РАЗДЕЛ 5: ФУНКЦИИ ЗА РАБОТА СЪС STORES
 */

/**
 * Получава магазините за даден парфюм
 * ВАЖНО: Връща форматирани данни за магазини
 */
function parfume_reviews_get_parfume_stores($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return array();
    }
    
    // Валидираме всеки магазин
    $validated_stores = array();
    foreach ($stores as $store) {
        if (is_array($store) && !empty($store['name']) && !empty($store['url'])) {
            $validated_stores[] = array(
                'name' => sanitize_text_field($store['name']),
                'url' => esc_url_raw($store['url']),
                'price' => !empty($store['price']) ? parfume_reviews_extract_price_number($store['price']) : 0,
                'currency' => !empty($store['currency']) ? sanitize_text_field($store['currency']) : 'лв.',
                'availability' => !empty($store['availability']) ? sanitize_text_field($store['availability']) : 'available',
                'shipping' => !empty($store['shipping']) ? parfume_reviews_extract_price_number($store['shipping']) : 0
            );
        }
    }
    
    return $validated_stores;
}

/**
 * Получава най-ниската цена за парфюм
 */
function parfume_reviews_get_lowest_price($post_id) {
    $stores = parfume_reviews_get_parfume_stores($post_id);
    
    if (empty($stores)) {
        return 0;
    }
    
    $lowest_price = PHP_FLOAT_MAX;
    foreach ($stores as $store) {
        if (!empty($store['price']) && $store['price'] > 0 && $store['price'] < $lowest_price) {
            $lowest_price = $store['price'];
        }
    }
    
    return $lowest_price === PHP_FLOAT_MAX ? 0 : $lowest_price;
}

/**
 * Проверява дали парфюмът е наличен
 */
function parfume_reviews_is_available($post_id) {
    $stores = parfume_reviews_get_parfume_stores($post_id);
    
    foreach ($stores as $store) {
        if ($store['availability'] === 'available') {
            return true;
        }
    }
    
    return false;
}

/**
 * Получава информация за доставка
 */
function parfume_reviews_get_shipping_info($post_id) {
    $stores = parfume_reviews_get_parfume_stores($post_id);
    $shipping_info = array(
        'min_shipping' => 0,
        'max_shipping' => 0,
        'free_shipping_available' => false,
        'stores_with_shipping' => 0
    );
    
    $shipping_costs = array();
    
    foreach ($stores as $store) {
        if (isset($store['shipping'])) {
            $shipping_costs[] = $store['shipping'];
            $shipping_info['stores_with_shipping']++;
            
            if ($store['shipping'] == 0) {
                $shipping_info['free_shipping_available'] = true;
            }
        }
    }
    
    if (!empty($shipping_costs)) {
        $shipping_info['min_shipping'] = min($shipping_costs);
        $shipping_info['max_shipping'] = max($shipping_costs);
    }
    
    return $shipping_info;
}

/**
 * Получава най-евтината доставка
 */
function parfume_reviews_get_cheapest_shipping($post_id) {
    $shipping_info = parfume_reviews_get_shipping_info($post_id);
    return $shipping_info['min_shipping'];
}

/**
 * Проверява дали има промоция
 */
function parfume_reviews_has_promotion($post_id) {
    $promotion = get_post_meta($post_id, '_parfume_promotion', true);
    return !empty($promotion);
}

/**
 * РАЗДЕЛ 6: ФУНКЦИИ ЗА ПОПУЛЯРНИ И ПОСЛЕДНИ ПАРФЮМИ
 */

/**
 * Получава популярни парфюми
 */
function parfume_reviews_get_popular_parfumes($limit = 10) {
    $args = array(
        'post_type' => 'parfume',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'meta_key' => '_parfume_rating',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_parfume_rating',
                'value' => 0,
                'compare' => '>'
            )
        )
    );
    
    return get_posts($args);
}

/**
 * Получава последни парфюми
 */
function parfume_reviews_get_latest_parfumes($limit = 10) {
    $args = array(
        'post_type' => 'parfume',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    return get_posts($args);
}

/**
 * Получава случайни парфюми
 */
function parfume_reviews_get_random_parfumes($limit = 10) {
    $args = array(
        'post_type' => 'parfume',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'rand'
    );
    
    return get_posts($args);
}

/**
 * Получава подобни парфюми
 */
function parfume_reviews_get_similar_parfumes($post_id, $limit = 5) {
    // Получаваме таксономиите на текущия парфюм
    $current_terms = array();
    $taxonomies = array('gender', 'aroma_type', 'marki', 'notes');
    
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
        if (!empty($terms)) {
            $current_terms[$taxonomy] = $terms;
        }
    }
    
    if (empty($current_terms)) {
        return array();
    }
    
    $args = array(
        'post_type' => 'parfume',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'post__not_in' => array($post_id),
        'orderby' => 'rand',
        'tax_query' => array(
            'relation' => 'OR'
        )
    );
    
    foreach ($current_terms as $taxonomy => $term_ids) {
        $args['tax_query'][] = array(
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => $term_ids
        );
    }
    
    return get_posts($args);
}

/**
 * РАЗДЕЛ 7: СТАТИСТИЧЕСКИ ФУНКЦИИ
 */

/**
 * Получава статистики за парфюми
 */
function parfume_reviews_get_parfume_stats() {
    $cache_key = 'parfume_reviews_stats';
    $stats = get_transient($cache_key);
    
    if (false === $stats) {
        global $wpdb;
        
        $stats = array(
            'total_parfumes' => 0,
            'total_brands' => 0,
            'average_rating' => 0,
            'total_reviews' => 0
        );
        
        // Общ брой парфюми
        $stats['total_parfumes'] = wp_count_posts('parfume')->publish;
        
        // Общ брой марки
        $brands = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => true,
            'fields' => 'count'
        ));
        $stats['total_brands'] = is_array($brands) ? count($brands) : 0;
        
        // Средна оценка
        $avg_rating = $wpdb->get_var(
            "SELECT AVG(CAST(meta_value AS DECIMAL(3,2))) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_parfume_rating' 
             AND meta_value != '' 
             AND meta_value != '0'"
        );
        $stats['average_rating'] = $avg_rating ? round($avg_rating, 1) : 0;
        
        // Кеширане за 1 час
        set_transient($cache_key, $stats, HOUR_IN_SECONDS);
    }
    
    return $stats;
}

/**
 * Изчиства кеша на статистиките
 */
function parfume_reviews_clear_stats_cache() {
    delete_transient('parfume_reviews_stats');
}

/**
 * РАЗДЕЛ 8: ВАЛИДАЦИОННИ ФУНКЦИИ
 */

/**
 * Валидира цена
 */
function parfume_reviews_sanitize_price($price) {
    $price = parfume_reviews_extract_price_number($price);
    
    if ($price < 0) {
        return 0;
    }
    
    return round($price, 2);
}

/**
 * РАЗДЕЛ 9: PERMISSION ФУНКЦИИ
 */

/**
 * Проверява дали потребителят може да редактира парфюм ревюта
 */
function parfume_reviews_user_can_edit_reviews() {
    return current_user_can('edit_posts') || current_user_can('manage_options');
}

/**
 * Проверява дали потребителят може да управлява плъгина
 */
function parfume_reviews_user_can_manage_plugin() {
    return current_user_can('manage_options');
}

/**
 * РАЗДЕЛ 10: ПОМОЩНИ ФУНКЦИИ ЗА СЪДЪРЖАНИЕ
 */

/**
 * Получава URL на първото изображение от content
 */
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

/**
 * Форматира срок за издръжливост
 */
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

// End of file