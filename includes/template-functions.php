<?php
/**
 * Template functions for Parfume Reviews
 * ВСИЧКИ ФУНКЦИИ СА ЦЕНТРАЛИЗИРАНИ ТУК - БЕЗ ДУБЛИРАНЕ!
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get HTML for rating stars
 */
function parfume_reviews_get_rating_stars($rating, $max = 5) {
    $rating = floatval($rating);
    $max = intval($max);
    
    if ($max <= 0) $max = 5;
    
    $output = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
    $empty_stars = $max - $full_stars - $half_star;
    
    // Ensure we don't exceed max stars
    if ($full_stars > $max) {
        $full_stars = $max;
        $half_star = 0;
        $empty_stars = 0;
    }
    
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<span class="star filled">★</span>';
    }
    
    for ($i = 0; $i < $half_star; $i++) {
        $output .= '<span class="star half">★</span>';
    }
    
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<span class="star">★</span>';
    }
    
    return $output;
}

/**
 * Get perfumer photo
 */
function parfume_reviews_get_perfumer_photo($term_id) {
    $photo_id = get_term_meta($term_id, 'perfumer-image-id', true);
    if ($photo_id) {
        return wp_get_attachment_image($photo_id, 'thumbnail');
    }
    return '';
}

/**
 * Get brand logo
 */
function parfume_reviews_get_brand_logo($term_id, $size = 'thumbnail') {
    $logo_id = get_term_meta($term_id, 'marki-image-id', true);
    if ($logo_id) {
        return wp_get_attachment_image($logo_id, $size);
    }
    return '';
}

/**
 * Get note image
 */
function parfume_reviews_get_note_image($term_id, $size = 'thumbnail') {
    $image_id = get_term_meta($term_id, 'notes-image-id', true);
    if ($image_id) {
        return wp_get_attachment_image($image_id, $size);
    }
    return '';
}

/**
 * Get lowest price from stores
 */
function parfume_reviews_get_lowest_price($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    $lowest_price = null;
    $lowest_store = null;
    
    foreach ($stores as $store) {
        if (empty($store['price'])) continue;
        
        // Extract numeric value from price
        preg_match('/(\d+(?:[.,]\d+)?)/', $store['price'], $matches);
        if (!empty($matches[1])) {
            $price = floatval(str_replace(',', '.', $matches[1]));
            
            if ($lowest_price === null || $price < $lowest_price) {
                $lowest_price = $price;
                $lowest_store = $store;
            }
        }
    }
    
    return $lowest_store;
}

/**
 * Check if perfume is available in any store
 */
function parfume_reviews_is_available($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    foreach ($stores as $store) {
        if (!empty($store['availability']) && strtolower($store['availability']) !== 'няма наличност') {
            return true;
        }
    }
    
    return false;
}

/**
 * Get cheapest shipping cost
 */
function parfume_reviews_get_cheapest_shipping($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    $cheapest_shipping = null;
    
    foreach ($stores as $store) {
        if (empty($store['shipping_cost'])) continue;
        
        // Check for free shipping
        if (stripos($store['shipping_cost'], 'безплатна') !== false || 
            stripos($store['shipping_cost'], '0') === 0) {
            return 'Безплатна доставка';
        }
        
        // Extract numeric value from shipping cost
        preg_match('/(\d+(?:[.,]\d+)?)/', $store['shipping_cost'], $matches);
        if (!empty($matches[1])) {
            $cost = floatval(str_replace(',', '.', $matches[1]));
            
            if ($cheapest_shipping === null || $cost < $cheapest_shipping) {
                $cheapest_shipping = $cost;
            }
        }
    }
    
    return $cheapest_shipping ? number_format($cheapest_shipping, 2) . ' лв.' : false;
}

/**
 * Get aroma chart data
 */
function parfume_reviews_get_aroma_chart($post_id) {
    return array(
        'freshness' => intval(get_post_meta($post_id, '_parfume_freshness', true)),
        'sweetness' => intval(get_post_meta($post_id, '_parfume_sweetness', true)),
        'intensity' => intval(get_post_meta($post_id, '_parfume_intensity', true)),
        'warmth' => intval(get_post_meta($post_id, '_parfume_warmth', true)),
    );
}

/**
 * Get pros and cons
 */
function parfume_reviews_get_pros_cons($post_id) {
    $pros = get_post_meta($post_id, '_parfume_pros', true);
    $cons = get_post_meta($post_id, '_parfume_cons', true);
    
    return array(
        'pros' => !empty($pros) ? explode("\n", $pros) : array(),
        'cons' => !empty($cons) ? explode("\n", $cons) : array(),
    );
}

/**
 * Get note description
 */
function parfume_reviews_get_note_description($term_id) {
    $description = get_term_meta($term_id, 'note_description', true);
    return !empty($description) ? $description : '';
}

/**
 * Get brand description  
 */
function parfume_reviews_get_brand_description($term_id) {
    $description = get_term_meta($term_id, 'brand_description', true);
    return !empty($description) ? $description : '';
}

/**
 * Get perfumer bio
 */
function parfume_reviews_get_perfumer_bio($term_id) {
    $bio = get_term_meta($term_id, 'perfumer_bio', true);
    return !empty($bio) ? $bio : '';
}

/**
 * Get product image
 */
function parfume_reviews_get_product_image($post_id, $size = 'medium') {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        return wp_get_attachment_image($thumbnail_id, $size, false, array('class' => 'parfume-image'));
    }
    
    // Fallback placeholder
    return '<div class="parfume-image-placeholder">No Image</div>';
}

/**
 * Format price
 */
function parfume_reviews_format_price($price) {
    if (empty($price)) {
        return '';
    }
    
    // Extract numeric value
    preg_match('/(\d+(?:[.,]\d+)?)/', $price, $matches);
    if (!empty($matches[1])) {
        $numeric_price = floatval(str_replace(',', '.', $matches[1]));
        return number_format($numeric_price, 2) . ' лв.';
    }
    
    return $price;
}

/**
 * Get store link with affiliate tracking
 */
function parfume_reviews_get_store_link($store_data, $post_id) {
    if (empty($store_data['url'])) {
        return '#';
    }
    
    $url = $store_data['url'];
    
    // Add affiliate parameters if configured
    $settings = get_option('parfume_reviews_settings', array());
    
    if (!empty($settings['affiliate_' . strtolower($store_data['name'])]) && 
        !empty($settings['affiliate_' . strtolower($store_data['name']) . '_id'])) {
        
        $affiliate_id = $settings['affiliate_' . strtolower($store_data['name']) . '_id'];
        
        // Add affiliate parameter based on store
        if (strpos($url, '?') !== false) {
            $url .= '&ref=' . urlencode($affiliate_id);
        } else {
            $url .= '?ref=' . urlencode($affiliate_id);
        }
    }
    
    return $url;
}

/**
 * Check if current page is parfume related
 * ДЕФИНИРА СЕ ТУК ЗА ДА НЕ СЕ ДУБЛИРА
 */
if (!function_exists('parfume_reviews_is_parfume_page')) {
    function parfume_reviews_is_parfume_page() {
        return (
            is_singular('parfume') || 
            is_post_type_archive('parfume') || 
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))
        );
    }
}

/**
 * Get breadcrumbs for parfume pages
 * ДЕФИНИРА СЕ ТУК ЗА ДА НЕ СЕ ДУБЛИРА
 */
if (!function_exists('parfume_reviews_get_breadcrumbs')) {
    function parfume_reviews_get_breadcrumbs() {
        if (!parfume_reviews_is_parfume_page()) {
            return '';
        }
        
        $breadcrumbs = array();
        $breadcrumbs[] = '<a href="' . home_url() . '">' . __('Home', 'parfume-reviews') . '</a>';
        
        if (is_post_type_archive('parfume')) {
            $breadcrumbs[] = __('Parfumes', 'parfume-reviews');
        } elseif (is_singular('parfume')) {
            $breadcrumbs[] = '<a href="' . get_post_type_archive_link('parfume') . '">' . __('Parfumes', 'parfume-reviews') . '</a>';
            $breadcrumbs[] = get_the_title();
        } elseif (is_tax()) {
            $term = get_queried_object();
            $breadcrumbs[] = '<a href="' . get_post_type_archive_link('parfume') . '">' . __('Parfumes', 'parfume-reviews') . '</a>';
            $breadcrumbs[] = $term->name;
        }
        
        return '<nav class="parfume-breadcrumbs">' . implode(' / ', $breadcrumbs) . '</nav>';
    }
}

/**
 * Get comparison link
 * ДЕФИНИРА СЕ ТУК ЗА ДА НЕ СЕ ДУБЛИРА
 */
if (!function_exists('parfume_reviews_get_comparison_link')) {
    function parfume_reviews_get_comparison_link() {
        if (class_exists('Parfume_Reviews\\Comparison')) {
            return Parfume_Reviews\Comparison::get_comparison_link();
        }
        
        // Fallback HTML
        ob_start();
        ?>
        <a href="#" id="show-comparison" class="comparison-link" style="display: none;">
            <?php _e('Comparison', 'parfume-reviews'); ?>
            <span class="comparison-count">0</span>
        </a>
        <?php
        return ob_get_clean();
    }
}

/**
 * Get comparison button HTML
 * WRAPPER ФУНКЦИЯ - ВСИЧКИ TEMPLATE ФАЙЛОВЕ ИЗПОЛЗВАТ ТАЗИ
 */
if (!function_exists('parfume_reviews_get_comparison_button')) {
    function parfume_reviews_get_comparison_button($post_id) {
        if (class_exists('Parfume_Reviews\\Comparison')) {
            return Parfume_Reviews\Comparison::get_comparison_button($post_id);
        }
        
        // Fallback HTML ако класът не е зареден
        ob_start();
        ?>
        <button class="add-to-comparison" data-post-id="<?php echo esc_attr($post_id); ?>">
            <?php _e('Add to comparison', 'parfume-reviews'); ?>
        </button>
        <?php
        return ob_get_clean();
    }
}

/**
 * Get collections dropdown 
 * WRAPPER ФУНКЦИЯ - ВСИЧКИ TEMPLATE ФАЙЛОВЕ ИЗПОЛЗВАТ ТАЗИ
 */
if (!function_exists('parfume_reviews_get_collections_dropdown')) {
    function parfume_reviews_get_collections_dropdown($post_id) {
        if (class_exists('Parfume_Reviews\\Collections')) {
            return Parfume_Reviews\Collections::get_collections_dropdown($post_id);
        }
        
        // Fallback HTML ако класът не е зареден
        return '';
    }
}


/**
 * Check if current page is parfume related
 * ТОВА Е КРИТИЧНАТА ФУНКЦИЯ КОЯТО ЛИПСВАШЕ И ПРИЧИНЯВА FATAL ERROR!
 */
function parfume_reviews_is_parfume_page() {
    return (
        is_singular('parfume') || 
        is_post_type_archive('parfume') || 
        is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))
    );
}