<?php
/**
 * Template Functions - Utility Functions
 * Помощни функции за проверки, валидации и utilities
 * 
 * Файл: includes/template-functions-utils.php
 * ПОПРАВЕНА ВЕРСИЯ - БЕЗ ДУБЛИРАНИ ФУНКЦИИ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Проверява дали сме на парфюмна страница
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
 * Получава всички поддържани таксономии
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
    
    $taxonomy_slugs = array(
        'gender' => 'gender',
        'aroma_type' => 'aroma-type',
        'marki' => 'marki',
        'season' => 'season',
        'intensity' => 'intensity',
        'notes' => 'notes',
        'perfumer' => 'parfumeri'
    );
    
    $taxonomy_slug = $taxonomy_slugs[$taxonomy] ?? $taxonomy;
    
    return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
}

/**
 * Получава цената на парфюм като форматиран string
 */
function parfume_reviews_get_formatted_price($post_id, $include_currency = true) {
    $price = get_post_meta($post_id, '_price', true);
    
    if (empty($price)) {
        return '';
    }
    
    $formatted_price = number_format(floatval($price), 2, ',', ' ');
    
    if ($include_currency) {
        $formatted_price .= ' лв.';
    }
    
    return $formatted_price;
}

/**
 * Получава рейтинга на парфюм като число
 */
function parfume_reviews_get_rating($post_id) {
    $rating = get_post_meta($post_id, '_rating', true);
    return !empty($rating) ? floatval($rating) : 0;
}

/**
 * Получава всички stores за парфюм
 */
function parfume_reviews_get_parfume_stores($post_id) {
    $stores = get_post_meta($post_id, '_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return array();
    }
    
    return $stores;
}

/**
 * Получава най-ниската цена от всички stores
 */
function parfume_reviews_get_lowest_price($post_id) {
    $stores = parfume_reviews_get_parfume_stores($post_id);
    
    if (empty($stores)) {
        return null;
    }
    
    $lowest_store = null;
    $lowest_price_value = null;
    
    foreach ($stores as $store) {
        if (!empty($store['variants']) && is_array($store['variants'])) {
            foreach ($store['variants'] as $variant) {
                if (!empty($variant['price'])) {
                    $price = parfume_reviews_extract_price_number($variant['price']);
                    if ($price > 0 && ($lowest_price_value === null || $price < $lowest_price_value)) {
                        $lowest_price_value = $price;
                        $lowest_store = array(
                            'name' => isset($store['name']) ? $store['name'] : '',
                            'price' => $variant['price'],
                            'size' => isset($variant['size']) ? $variant['size'] : '',
                            'url' => isset($variant['affiliate_url']) ? $variant['affiliate_url'] : (isset($store['url']) ? $store['url'] : ''),
                        );
                    }
                }
            }
        } elseif (!empty($store['price'])) {
            $price = parfume_reviews_extract_price_number($store['price']);
            if ($price > 0 && ($lowest_price_value === null || $price < $lowest_price_value)) {
                $lowest_price_value = $price;
                $lowest_store = array(
                    'name' => isset($store['name']) ? $store['name'] : '',
                    'price' => $store['price'],
                    'size' => isset($store['size']) ? $store['size'] : '',
                    'url' => isset($store['affiliate_url']) ? $store['affiliate_url'] : (isset($store['url']) ? $store['url'] : ''),
                );
            }
        }
    }
    
    return $lowest_store;
}

/**
 * Извлича числовата стойност от цена стринг
 */
function parfume_reviews_extract_price_number($price_string) {
    // Remove currency symbols and extract number
    $price = preg_replace('/[^\d.,]/', '', $price_string);
    $price = str_replace(',', '.', $price);
    return floatval($price);
}

/**
 * Проверява дали парфюм е наличен
 */
function parfume_reviews_is_available($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    foreach ($stores as $store) {
        if (!empty($store['availability'])) {
            $availability = strtolower($store['availability']);
            if (in_array($availability, array('в наличност', 'available', 'наличен', 'в склад'))) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Получава информация за доставка
 */
function parfume_reviews_get_shipping_info($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return '';
    }
    
    foreach ($stores as $store) {
        if (!empty($store['shipping_info'])) {
            return $store['shipping_info'];
        }
    }
    
    return '';
}

/**
 * Получава най-евтината доставка за парфюм
 */
function parfume_reviews_get_cheapest_shipping($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return '';
    }
    
    $shipping_options = array();
    
    foreach ($stores as $store) {
        if (!empty($store['shipping_info'])) {
            // Извличаме цена от shipping информацията
            $shipping_text = $store['shipping_info'];
            
            // Търсим цени в shipping текста
            if (preg_match('/(\d+[\.,]?\d*)\s*(лв|bgn|eur|€)/i', $shipping_text, $matches)) {
                $price = floatval(str_replace(',', '.', $matches[1]));
                $shipping_options[] = array(
                    'text' => $shipping_text,
                    'price' => $price,
                    'store' => isset($store['name']) ? $store['name'] : ''
                );
            } else {
                // Просто добавяме текста без цена
                $shipping_options[] = array(
                    'text' => $shipping_text,
                    'price' => 999999, // Висока цена за несортирани опции
                    'store' => isset($store['name']) ? $store['name'] : ''
                );
            }
        }
    }
    
    // Сортираме по цена
    if (!empty($shipping_options)) {
        usort($shipping_options, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        return $shipping_options[0]['text'];
    }
    
    return '';
}

/**
 * Проверява дали има промоция за парфюм
 */
function parfume_reviews_has_promotion($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    foreach ($stores as $store) {
        // Check for discount flag
        if (!empty($store['has_discount']) || !empty($store['has_promotion'])) {
            return true;
        }
        
        // Check for original vs current price
        if (!empty($store['original_price']) && !empty($store['price'])) {
            $original = parfume_reviews_extract_price_number($store['original_price']);
            $current = parfume_reviews_extract_price_number($store['price']);
            if ($original > $current) {
                return true;
            }
        }
        
        // Check for variant promotions
        if (!empty($store['variants']) && is_array($store['variants'])) {
            foreach ($store['variants'] as $variant) {
                if (!empty($variant['has_discount']) || !empty($variant['has_promotion'])) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * Получава най-популярните парфюми
 */
function parfume_reviews_get_popular_parfumes($limit = 10, $exclude = array()) {
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => $limit,
        'orderby' => 'comment_count',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_rating',
                'value' => 3,
                'compare' => '>='
            )
        )
    );
    
    if (!empty($exclude)) {
        $args['post__not_in'] = $exclude;
    }
    
    return get_posts($args);
}

/**
 * Получава последните парфюми
 */
function parfume_reviews_get_latest_parfumes($limit = 10, $exclude = array()) {
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    if (!empty($exclude)) {
        $args['post__not_in'] = $exclude;
    }
    
    return get_posts($args);
}

/**
 * Получава случайни парфюми
 */
function parfume_reviews_get_random_parfumes($limit = 10, $exclude = array()) {
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => $limit,
        'orderby' => 'rand'
    );
    
    if (!empty($exclude)) {
        $args['post__not_in'] = $exclude;
    }
    
    return get_posts($args);
}

/**
 * Получава сходни парфюми
 */
function parfume_reviews_get_similar_parfumes($post_id, $limit = 6) {
    // Получаваме таксономиите на текущия парфюм
    $current_terms = array();
    $taxonomies = parfume_reviews_get_supported_taxonomies();
    
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
        if (!empty($terms) && !is_wp_error($terms)) {
            $current_terms[$taxonomy] = $terms;
        }
    }
    
    if (empty($current_terms)) {
        return array();
    }
    
    // Създаваме tax_query за намиране на сходни парфюми
    $tax_query = array('relation' => 'OR');
    
    foreach ($current_terms as $taxonomy => $term_ids) {
        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => $term_ids
        );
    }
    
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => $limit + 1, // +1 защото ще изключим текущия
        'post__not_in' => array($post_id),
        'tax_query' => $tax_query,
        'orderby' => 'rand'
    );
    
    return get_posts($args);
}

/**
 * Получава статистики за парфюми
 */
function parfume_reviews_get_parfume_stats() {
    $stats = wp_cache_get('parfume_reviews_stats', 'parfume_reviews');
    
    if (false === $stats) {
        $stats = array();
        
        // Общо парфюми
        $stats['total_parfumes'] = wp_count_posts('parfume')->publish;
        
        // Средна оценка
        global $wpdb;
        $avg_rating = $wpdb->get_var("
            SELECT AVG(CAST(meta_value AS DECIMAL(3,2))) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_rating' 
            AND meta_value != ''
        ");
        $stats['average_rating'] = $avg_rating ? round($avg_rating, 2) : 0;
        
        // Най-популярни марки
        $popular_brands = get_terms(array(
            'taxonomy' => 'marki',
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5,
            'hide_empty' => true
        ));
        $stats['popular_brands'] = $popular_brands;
        
        // Кеширане за 1 час
        wp_cache_set('parfume_reviews_stats', $stats, 'parfume_reviews', HOUR_IN_SECONDS);
    }
    
    return $stats;
}

/**
 * Почиства кеша за статистики
 */
function parfume_reviews_clear_stats_cache() {
    wp_cache_delete('parfume_reviews_stats', 'parfume_reviews');
}

/**
 * Sanitize и валидира рейтинг
 */
function parfume_reviews_sanitize_rating($rating) {
    $rating = floatval($rating);
    
    if ($rating < 0) {
        return 0;
    } elseif ($rating > 5) {
        return 5;
    }
    
    return round($rating, 1);
}

/**
 * Sanitize и валидира цена
 */
function parfume_reviews_sanitize_price($price) {
    // Премахваме всички символи освен цифри, точка и запетая
    $price = preg_replace('/[^0-9.,]/', '', $price);
    
    // Заменяме запетая с точка
    $price = str_replace(',', '.', $price);
    
    return floatval($price);
}

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