<?php
/**
 * Helper Functions for Parfume Reviews Plugin
 *
 * @package Parfume_Reviews
 * @since 1.0.0
 */

namespace Parfume_Reviews\Utils;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helpers Class
 * Contains all utility functions for the plugin
 */
class Helpers {
    
    /**
     * Get plugin setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public static function get_setting($key, $default = '') {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Update plugin setting
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Update result
     */
    public static function update_setting($key, $value) {
        $settings = get_option('parfume_reviews_settings', array());
        $settings[$key] = $value;
        return update_option('parfume_reviews_settings', $settings);
    }
    
    /**
     * Get rating stars HTML
     *
     * @param float $rating Rating value (0-5)
     * @param int $max Maximum rating
     * @return string HTML for rating stars
     */
    public static function get_rating_stars($rating, $max = 5) {
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
     * Get lowest price from stores
     *
     * @param int $post_id Post ID
     * @return array|false Store data or false
     */
    public static function get_lowest_price($post_id) {
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
     *
     * @param int $post_id Post ID
     * @return bool Availability status
     */
    public static function is_available($post_id) {
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
     *
     * @param int $post_id Post ID
     * @return string|false Shipping cost or false
     */
    public static function get_cheapest_shipping($post_id) {
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
     *
     * @param int $post_id Post ID
     * @return array Chart data
     */
    public static function get_aroma_chart($post_id) {
        return array(
            'freshness' => intval(get_post_meta($post_id, '_parfume_freshness', true)),
            'sweetness' => intval(get_post_meta($post_id, '_parfume_sweetness', true)),
            'intensity' => intval(get_post_meta($post_id, '_parfume_intensity', true)),
            'warmth' => intval(get_post_meta($post_id, '_parfume_warmth', true)),
        );
    }
    
    /**
     * Get pros and cons
     *
     * @param int $post_id Post ID
     * @return array Pros and cons
     */
    public static function get_pros_cons($post_id) {
        $pros = get_post_meta($post_id, '_parfume_pros', true);
        $cons = get_post_meta($post_id, '_parfume_cons', true);
        
        return array(
            'pros' => !empty($pros) ? explode("\n", $pros) : array(),
            'cons' => !empty($cons) ? explode("\n", $cons) : array(),
        );
    }
    
    /**
     * Get main fragrance notes grouped by category
     *
     * @param int $post_id Post ID
     * @return array Grouped notes
     */
    public static function get_main_notes_by_group($post_id) {
        $notes = wp_get_post_terms($post_id, 'notes');
        if (empty($notes) || is_wp_error($notes)) {
            return array();
        }
        
        $grouped_notes = array();
        
        foreach ($notes as $note) {
            $group = get_term_meta($note->term_id, 'note_group', true);
            if (empty($group)) {
                $group = 'Други';
            }
            
            if (!isset($grouped_notes[$group])) {
                $grouped_notes[$group] = array();
            }
            
            $grouped_notes[$group][] = $note;
        }
        
        return $grouped_notes;
    }
    
    /**
     * Get perfumer photo
     *
     * @param int $term_id Term ID
     * @return string HTML image tag
     */
    public static function get_perfumer_photo($term_id) {
        $photo_id = get_term_meta($term_id, 'perfumer-image-id', true);
        if ($photo_id) {
            return wp_get_attachment_image($photo_id, 'thumbnail');
        }
        return '';
    }
    
    /**
     * Get brand logo
     *
     * @param int $term_id Term ID
     * @param string $size Image size
     * @return string HTML image tag
     */
    public static function get_brand_logo($term_id, $size = 'thumbnail') {
        $logo_id = get_term_meta($term_id, 'marki-image-id', true);
        if ($logo_id) {
            return wp_get_attachment_image($logo_id, $size);
        }
        return '';
    }
    
    /**
     * Get note image
     *
     * @param int $term_id Term ID
     * @param string $size Image size
     * @return string HTML image tag
     */
    public static function get_note_image($term_id, $size = 'thumbnail') {
        $image_id = get_term_meta($term_id, 'notes-image-id', true);
        if ($image_id) {
            return wp_get_attachment_image($image_id, $size);
        }
        return '';
    }
    
    /**
     * Format price for display
     *
     * @param string $price Raw price
     * @return string Formatted price
     */
    public static function format_price($price) {
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
     * Sanitize taxonomy slug
     *
     * @param string $slug Raw slug
     * @return string Sanitized slug
     */
    public static function sanitize_taxonomy_slug($slug) {
        return sanitize_title($slug);
    }
    
    /**
     * Check if current page is parfume related
     *
     * @return bool True if parfume related
     */
    public static function is_parfume_page() {
        if (!did_action('wp')) {
            return false;
        }
        
        return is_singular('parfume') || 
               is_post_type_archive('parfume') || 
               is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
    }
    
    /**
     * Get taxonomy terms formatted
     *
     * @param int $post_id Post ID
     * @param string $taxonomy Taxonomy name
     * @param string $field Field to return
     * @return array Terms array
     */
    public static function get_formatted_terms($post_id, $taxonomy, $field = 'names') {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => $field));
        
        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }
        
        return $terms;
    }
    
    /**
     * Generate nonce for AJAX actions
     *
     * @param string $action Action name
     * @return string Nonce value
     */
    public static function create_nonce($action) {
        return wp_create_nonce('parfume_reviews_' . $action);
    }
    
    /**
     * Verify nonce for AJAX actions
     *
     * @param string $nonce Nonce value
     * @param string $action Action name
     * @return bool Verification result
     */
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, 'parfume_reviews_' . $action);
    }
    
    /**
     * Get plugin version
     *
     * @return string Plugin version
     */
    public static function get_version() {
        return PARFUME_REVIEWS_VERSION;
    }
    
    /**
     * Log debug message
     *
     * @param string $message Message to log
     * @param string $level Log level
     */
    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Parfume Reviews ' . strtoupper($level) . '] ' . $message);
        }
    }
    
    /**
     * Get plugin cache key
     *
     * @param string $key Cache key suffix
     * @return string Full cache key
     */
    public static function get_cache_key($key) {
        return 'parfume_reviews_' . $key;
    }
    
    /**
     * Check if cache is enabled
     *
     * @return bool Cache status
     */
    public static function is_cache_enabled() {
        return self::get_setting('enable_cache', true);
    }
    
    /**
     * Clear plugin cache
     *
     * @param string|null $key Specific cache key or null for all
     * @return bool Clear result
     */
    public static function clear_cache($key = null) {
        if ($key) {
            return wp_cache_delete(self::get_cache_key($key), 'parfume_reviews');
        }
        
        // Clear all plugin cache
        wp_cache_flush_group('parfume_reviews');
        return true;
    }
    
    /**
     * Enqueue plugin assets
     *
     * @param array $assets Assets to enqueue
     */
    public static function enqueue_assets($assets = array()) {
        foreach ($assets as $asset) {
            if ($asset['type'] === 'css') {
                wp_enqueue_style(
                    $asset['handle'],
                    $asset['src'],
                    $asset['deps'] ?? array(),
                    $asset['version'] ?? PARFUME_REVIEWS_VERSION
                );
            } elseif ($asset['type'] === 'js') {
                wp_enqueue_script(
                    $asset['handle'],
                    $asset['src'],
                    $asset['deps'] ?? array('jquery'),
                    $asset['version'] ?? PARFUME_REVIEWS_VERSION,
                    $asset['in_footer'] ?? true
                );
            }
        }
    }
}

// Backward compatibility wrapper functions
if (!function_exists('parfume_reviews_get_setting')) {
    function parfume_reviews_get_setting($key, $default = '') {
        return Helpers::get_setting($key, $default);
    }
}

if (!function_exists('parfume_reviews_get_rating_stars')) {
    function parfume_reviews_get_rating_stars($rating, $max = 5) {
        return Helpers::get_rating_stars($rating, $max);
    }
}

if (!function_exists('parfume_reviews_get_lowest_price')) {
    function parfume_reviews_get_lowest_price($post_id) {
        return Helpers::get_lowest_price($post_id);
    }
}

if (!function_exists('parfume_reviews_is_available')) {
    function parfume_reviews_is_available($post_id) {
        return Helpers::is_available($post_id);
    }
}

if (!function_exists('parfume_reviews_get_cheapest_shipping')) {
    function parfume_reviews_get_cheapest_shipping($post_id) {
        return Helpers::get_cheapest_shipping($post_id);
    }
}

if (!function_exists('parfume_reviews_get_aroma_chart')) {
    function parfume_reviews_get_aroma_chart($post_id) {
        return Helpers::get_aroma_chart($post_id);
    }
}

if (!function_exists('parfume_reviews_get_pros_cons')) {
    function parfume_reviews_get_pros_cons($post_id) {
        return Helpers::get_pros_cons($post_id);
    }
}

if (!function_exists('parfume_reviews_get_main_notes_by_group')) {
    function parfume_reviews_get_main_notes_by_group($post_id) {
        return Helpers::get_main_notes_by_group($post_id);
    }
}

if (!function_exists('parfume_reviews_get_perfumer_photo')) {
    function parfume_reviews_get_perfumer_photo($term_id) {
        return Helpers::get_perfumer_photo($term_id);
    }
}

if (!function_exists('parfume_reviews_get_brand_logo')) {
    function parfume_reviews_get_brand_logo($term_id, $size = 'thumbnail') {
        return Helpers::get_brand_logo($term_id, $size);
    }
}

if (!function_exists('parfume_reviews_get_note_image')) {
    function parfume_reviews_get_note_image($term_id, $size = 'thumbnail') {
        return Helpers::get_note_image($term_id, $size);
    }
}

if (!function_exists('is_parfume_reviews_active')) {
    function is_parfume_reviews_active() {
        return class_exists('Parfume_Reviews_Plugin');
    }
}