<?php
/**
 * Helper Functions
 * 
 * Global helper functions for templates
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get rating stars HTML
 * 
 * Converts numeric rating (0-10) to star display (0-5 stars)
 * 
 * @param float $rating Rating value (0-10)
 * @param bool $show_empty Whether to show empty stars
 * @return string HTML for stars
 */
function parfume_reviews_get_rating_stars($rating, $show_empty = true) {
    $rating = floatval($rating);
    $stars_count = $rating / 2; // Convert 0-10 to 0-5
    $full_stars = floor($stars_count);
    $half_star = ($stars_count - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    $html = '<span class="rating-stars">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $html .= '<span class="star star-full">★</span>';
    }
    
    // Half star
    if ($half_star) {
        $html .= '<span class="star star-half">★</span>';
    }
    
    // Empty stars
    if ($show_empty) {
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="star star-empty">☆</span>';
        }
    }
    
    $html .= '</span>';
    
    return $html;
}

/**
 * Format price
 * 
 * @param float $price Price value
 * @param string $currency Currency code (default: BGN)
 * @return string Formatted price
 */
function parfume_reviews_format_price($price, $currency = 'BGN') {
    $price = floatval($price);
    
    $formatted = number_format($price, 2, '.', ' ');
    
    return apply_filters('parfume_reviews_format_price', $formatted . ' ' . $currency, $price, $currency);
}

/**
 * Display parfume card
 * 
 * @param int $post_id Post ID
 */
function parfume_reviews_display_parfume_card($post_id) {
    $template_loader = new \ParfumeReviews\Templates\Loader(
        \ParfumeReviews\Core\Plugin::get_instance()->get_container()
    );
    
    $template_loader->get_template_part('parts/parfume-card', null, ['post_id' => $post_id]);
}

/**
 * Get breadcrumbs
 * 
 * @return array Breadcrumb items
 */
function parfume_reviews_get_breadcrumbs() {
    $breadcrumbs = [];
    
    // Home
    $breadcrumbs[] = [
        'title' => __('Начало', 'parfume-reviews'),
        'url' => home_url('/')
    ];
    
    // Parfume archive
    $settings = get_option('parfume_reviews_settings', []);
    $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
    
    $breadcrumbs[] = [
        'title' => __('Парфюми', 'parfume-reviews'),
        'url' => home_url('/' . $parfume_slug . '/')
    ];
    
    // Current page
    if (is_singular('parfume')) {
        $breadcrumbs[] = [
            'title' => get_the_title(),
            'url' => ''
        ];
    } elseif (is_tax()) {
        $term = get_queried_object();
        $breadcrumbs[] = [
            'title' => $term->name,
            'url' => ''
        ];
    }
    
    return apply_filters('parfume_reviews_breadcrumbs', $breadcrumbs);
}

/**
 * Display breadcrumbs
 */
function parfume_reviews_display_breadcrumbs() {
    $breadcrumbs = parfume_reviews_get_breadcrumbs();
    
    if (empty($breadcrumbs)) {
        return;
    }
    
    echo '<nav class="parfume-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'parfume-reviews') . '">';
    echo '<ol class="breadcrumb-list">';
    
    $total = count($breadcrumbs);
    $current = 0;
    
    foreach ($breadcrumbs as $crumb) {
        $current++;
        $is_last = ($current === $total);
        
        echo '<li class="breadcrumb-item' . ($is_last ? ' active' : '') . '">';
        
        if (!$is_last && !empty($crumb['url'])) {
            echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['title']) . '</a>';
        } else {
            echo '<span>' . esc_html($crumb['title']) . '</span>';
        }
        
        if (!$is_last) {
            echo '<span class="separator">/</span>';
        }
        
        echo '</li>';
    }
    
    echo '</ol>';
    echo '</nav>';
}

/**
 * Get longevity label
 * 
 * @param string $longevity Longevity value
 * @return string Translated label
 */
function parfume_reviews_get_longevity_label($longevity) {
    $labels = [
        'weak' => __('Слаба', 'parfume-reviews'),
        'moderate' => __('Умерена', 'parfume-reviews'),
        'long' => __('Дълга', 'parfume-reviews'),
        'very_long' => __('Много дълга', 'parfume-reviews')
    ];
    
    return isset($labels[$longevity]) ? $labels[$longevity] : $longevity;
}

/**
 * Get sillage label
 * 
 * @param string $sillage Sillage value
 * @return string Translated label
 */
function parfume_reviews_get_sillage_label($sillage) {
    $labels = [
        'intimate' => __('Интимен', 'parfume-reviews'),
        'moderate' => __('Умерен', 'parfume-reviews'),
        'strong' => __('Силен', 'parfume-reviews'),
        'enormous' => __('Много силен', 'parfume-reviews')
    ];
    
    return isset($labels[$sillage]) ? $labels[$sillage] : $sillage;
}

/**
 * Check if current page is parfume related
 * 
 * @return bool
 */
function parfume_reviews_is_parfume_page() {
    if (!did_action('wp')) {
        return false;
    }
    
    $parfume_taxonomies = ['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'];
    
    if (is_singular('parfume') || is_post_type_archive('parfume')) {
        return true;
    }
    
    foreach ($parfume_taxonomies as $taxonomy) {
        if (is_tax($taxonomy)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get taxonomy term link
 * 
 * @param string $taxonomy Taxonomy name
 * @param int|string $term Term ID or slug
 * @return string Term URL
 */
function parfume_reviews_get_term_link($taxonomy, $term) {
    $term_obj = is_numeric($term) ? get_term($term, $taxonomy) : get_term_by('slug', $term, $taxonomy);
    
    if (!$term_obj || is_wp_error($term_obj)) {
        return '';
    }
    
    return get_term_link($term_obj, $taxonomy);
}

/**
 * Display filters
 * 
 * @param array $args Optional arguments
 */
function parfume_reviews_display_filters($args = []) {
    $template_loader = new \ParfumeReviews\Templates\Loader(
        \ParfumeReviews\Core\Plugin::get_instance()->get_container()
    );
    
    $template_loader->get_template_part('parts/filters', null, $args);
}

/**
 * Display pagination
 * 
 * @param WP_Query $query Optional custom query
 */
function parfume_reviews_display_pagination($query = null) {
    $template_loader = new \ParfumeReviews\Templates\Loader(
        \ParfumeReviews\Core\Plugin::get_instance()->get_container()
    );
    
    $template_loader->get_template_part('parts/pagination', null, ['query' => $query]);
}

/**
 * Get comparison button HTML
 * 
 * @param int $post_id Post ID
 * @return string Button HTML
 */
function parfume_reviews_get_comparison_button($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $container = \ParfumeReviews\Core\Plugin::get_instance()->get_container();
    
    if (!$container->has('comparison')) {
        return '';
    }
    
    $comparison = $container->get('comparison');
    
    ob_start();
    $comparison->render_comparison_button($post_id);
    return ob_get_clean();
}

/**
 * Get stores data
 * 
 * @param int $post_id Post ID
 * @return array Stores data
 */
function parfume_reviews_get_stores($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (!is_array($stores)) {
        return [];
    }
    
    return $stores;
}

/**
 * Get lowest price from stores
 * 
 * @param int $post_id Post ID
 * @return float|null Lowest price or null
 */
function parfume_reviews_get_lowest_price($post_id) {
    $stores = parfume_reviews_get_stores($post_id);
    
    if (empty($stores)) {
        return null;
    }
    
    $prices = array_filter(array_column($stores, 'price'), function($price) {
        return !empty($price) && $price > 0;
    });
    
    return !empty($prices) ? min($prices) : null;
}

/**
 * Sanitize store data
 * 
 * @param array $store Store data
 * @return array Sanitized data
 */
function parfume_reviews_sanitize_store($store) {
    return [
        'name' => isset($store['name']) ? sanitize_text_field($store['name']) : '',
        'url' => isset($store['url']) ? esc_url_raw($store['url']) : '',
        'price' => isset($store['price']) ? floatval($store['price']) : 0,
        'affiliate_link' => isset($store['affiliate_link']) ? esc_url_raw($store['affiliate_link']) : ''
    ];
}