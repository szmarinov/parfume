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
 * Get comparison button HTML
 */
function parfume_reviews_get_comparison_button($post_id) {
    $post_id = intval($post_id);
    
    if (!$post_id || get_post_type($post_id) !== 'parfume') {
        return '';
    }
    
    if (class_exists('Parfume_Reviews\\Comparison')) {
        return Parfume_Reviews\Comparison::get_comparison_button($post_id);
    }
    return '';
}

/**
 * Get collections dropdown HTML
 */
function parfume_reviews_get_collections_dropdown($post_id) {
    $post_id = intval($post_id);
    
    if (!$post_id || get_post_type($post_id) !== 'parfume') {
        return '';
    }
    
    if (class_exists('Parfume_Reviews\\Collections')) {
        return Parfume_Reviews\Collections::get_collections_dropdown($post_id);
    }
    return '';
}