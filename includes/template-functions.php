<?php
/**
 * Template functions for Parfume Reviews
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
 * Get price history for a perfume
 */
function parfume_reviews_get_price_history($post_id) {
    // This would be implemented to track price changes over time
    // For now, return empty array
    return array();
}