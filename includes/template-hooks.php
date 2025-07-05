<?php
if (!defined('ABSPATH')) {
    exit;
}

// Add hooks only if we're not in admin and functions don't already exist
if (!is_admin()) {
    add_action('wp_head', 'parfume_reviews_add_schema_markup');
    add_action('wp_footer', 'parfume_reviews_track_recently_viewed');
}

/**
 * Add schema.org markup for perfume products
 */
if (!function_exists('parfume_reviews_add_schema_markup')) {
    function parfume_reviews_add_schema_markup() {
        if (!is_singular('parfume')) {
            return;
        }
        
        global $post;
        
        // Check if $post exists and is valid
        if (!$post || !is_object($post) || !isset($post->ID)) {
            return;
        }
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $brands = wp_get_post_terms($post->ID, 'marki', array('fields' => 'names'));
        $notes = wp_get_post_terms($post->ID, 'notes', array('fields' => 'names'));
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        
        // Handle WP_Error for taxonomy terms
        if (is_wp_error($brands)) {
            $brands = array();
        }
        if (is_wp_error($notes)) {
            $notes = array();
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
        );
        
        // Only add brand if we have valid brands
        if (!empty($brands) && is_array($brands)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brands[0],
            );
        }
        
        // Only add rating if it's valid
        if (!empty($rating) && is_numeric($rating)) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => floatval($rating),
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => '1',
            );
        }
        
        // Add image if exists
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($thumbnail_url) {
                $schema['image'] = $thumbnail_url;
            }
        }
        
        // Add additional properties if they exist
        if (!empty($gender)) {
            $schema['additionalProperty'] = array(
                '@type' => 'PropertyValue',
                'name' => 'gender',
                'value' => sanitize_text_field($gender),
            );
        }
        
        if (!empty($release_year)) {
            $schema['releaseDate'] = sanitize_text_field($release_year);
        }
        
        if (!empty($notes) && is_array($notes)) {
            $schema['keywords'] = implode(', ', array_map('sanitize_text_field', $notes));
        }
        
        // Use wp_json_encode for better compatibility
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}

/**
 * Track recently viewed perfumes
 */
if (!function_exists('parfume_reviews_track_recently_viewed')) {
    function parfume_reviews_track_recently_viewed() {
        if (!is_singular('parfume')) {
            return;
        }
        
        global $post;
        
        // Check if $post exists and is valid
        if (!$post || !is_object($post) || !isset($post->ID)) {
            return;
        }
        
        // Don't track in admin or if headers already sent
        if (is_admin() || headers_sent()) {
            return;
        }
        
        $post_id = intval($post->ID);
        if ($post_id <= 0) {
            return;
        }
        
        $viewed = array();
        
        // Safely get cookie data
        if (isset($_COOKIE['parfume_recently_viewed']) && !empty($_COOKIE['parfume_recently_viewed'])) {
            $cookie_data = sanitize_text_field($_COOKIE['parfume_recently_viewed']);
            $viewed = explode(',', $cookie_data);
            $viewed = array_filter(array_map('intval', $viewed));
        }
        
        // Remove current post if it's already in the list
        $viewed = array_diff($viewed, array($post_id));
        
        // Add current post to the beginning
        array_unshift($viewed, $post_id);
        
        // Keep only last 10 items
        $viewed = array_slice($viewed, 0, 10);
        
        // Set cookie for 30 days with proper error handling
        $cookie_value = implode(',', $viewed);
        $expire_time = time() + (30 * DAY_IN_SECONDS);
        
        // Use @ to suppress potential cookie warnings
        @setcookie(
            'parfume_recently_viewed', 
            $cookie_value, 
            $expire_time, 
            COOKIEPATH, 
            COOKIE_DOMAIN,
            is_ssl(), // secure
            true      // httponly
        );
    }
}