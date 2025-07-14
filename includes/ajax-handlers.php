<?php
/**
 * AJAX Handlers - обработва AJAX заявки
 * 📁 Файл: includes/ajax-handlers.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler за филтриране на парфюми
 */
add_action('wp_ajax_parfume_filter', 'parfume_reviews_ajax_filter');
add_action('wp_ajax_nopriv_parfume_filter', 'parfume_reviews_ajax_filter');

function parfume_reviews_ajax_filter() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume-reviews-nonce')) {
        wp_die(__('Security check failed', 'parfume-reviews'));
    }
    
    $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 12;
    
    // Build query args
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'post_status' => 'publish',
    );
    
    // Add taxonomy filters
    if (!empty($filters) && is_array($filters)) {
        $tax_query = array('relation' => 'AND');
        
        $supported_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($filters[$taxonomy])) {
                $terms = is_array($filters[$taxonomy]) ? $filters[$taxonomy] : array($filters[$taxonomy]);
                $terms = array_map('sanitize_text_field', $terms);
                
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN',
                );
            }
        }
        
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        
        // Price range filter
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $meta_query = array();
            
            if (!empty($filters['min_price'])) {
                $meta_query[] = array(
                    'key' => '_parfume_price',
                    'value' => floatval($filters['min_price']),
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                );
            }
            
            if (!empty($filters['max_price'])) {
                $meta_query[] = array(
                    'key' => '_parfume_price',
                    'value' => floatval($filters['max_price']),
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                );
            }
            
            $args['meta_query'] = $meta_query;
        }
        
        // Rating filter
        if (!empty($filters['min_rating'])) {
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = array();
            }
            
            $args['meta_query'][] = array(
                'key' => '_parfume_rating',
                'value' => floatval($filters['min_rating']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
        }
    }
    
    // Execute query
    $query = new WP_Query($args);
    
    $response = array(
        'success' => true,
        'data' => array(
            'html' => '',
            'found_posts' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => $paged,
        ),
    );
    
    // Generate HTML
    if ($query->have_posts()) {
        ob_start();
        echo '<div class="parfume-grid">';
        
        while ($query->have_posts()) {
            $query->the_post();
            parfume_reviews_display_parfume_card(get_the_ID());
        }
        
        echo '</div>';
        $response['data']['html'] = ob_get_clean();
        wp_reset_postdata();
    } else {
        $response['data']['html'] = '<div class="no-results">' . __('No parfumes found with the selected filters.', 'parfume-reviews') . '</div>';
    }
    
    wp_send_json($response);
}

/**
 * AJAX handler за търсене на парфюми
 */
add_action('wp_ajax_parfume_search', 'parfume_reviews_ajax_search');
add_action('wp_ajax_nopriv_parfume_search', 'parfume_reviews_ajax_search');

function parfume_reviews_ajax_search() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume-reviews-nonce')) {
        wp_die(__('Security check failed', 'parfume-reviews'));
    }
    
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
    
    if (empty($search_term) || strlen($search_term) < 2) {
        wp_send_json_error(__('Search term too short', 'parfume-reviews'));
    }
    
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => $limit,
        's' => $search_term,
        'post_status' => 'publish',
    );
    
    $query = new WP_Query($args);
    $results = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            $brand = parfume_reviews_get_brand(get_the_ID());
            
            $results[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'brand' => $brand ? $brand->name : '',
                'url' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($results);
}

/**
 * AJAX handler за добавяне в сравнение
 */
add_action('wp_ajax_add_to_comparison', 'parfume_reviews_ajax_add_to_comparison');
add_action('wp_ajax_nopriv_add_to_comparison', 'parfume_reviews_ajax_add_to_comparison');

function parfume_reviews_ajax_add_to_comparison() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
        wp_die(__('Security check failed', 'parfume-reviews'));
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id || get_post_type($post_id) !== 'parfume') {
        wp_send_json_error(__('Invalid parfume ID', 'parfume-reviews'));
    }
    
    // Get current comparison items from session/cookie
    if (!session_id()) {
        session_start();
    }
    
    $comparison_items = isset($_SESSION['parfume_comparison']) ? $_SESSION['parfume_comparison'] : array();
    
    // Check if already in comparison
    if (in_array($post_id, $comparison_items)) {
        wp_send_json_error(__('Already in comparison', 'parfume-reviews'));
    }
    
    // Check limit
    if (count($comparison_items) >= 4) {
        wp_send_json_error(__('Maximum 4 items allowed in comparison', 'parfume-reviews'));
    }
    
    // Add to comparison
    $comparison_items[] = $post_id;
    $_SESSION['parfume_comparison'] = $comparison_items;
    
    wp_send_json_success(array(
        'message' => __('Added to comparison', 'parfume-reviews'),
        'count' => count($comparison_items),
        'items' => $comparison_items,
    ));
}

/**
 * AJAX handler за премахване от сравнение
 */
add_action('wp_ajax_remove_from_comparison', 'parfume_reviews_ajax_remove_from_comparison');
add_action('wp_ajax_nopriv_remove_from_comparison', 'parfume_reviews_ajax_remove_from_comparison');

function parfume_reviews_ajax_remove_from_comparison() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
        wp_die(__('Security check failed', 'parfume-reviews'));
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(__('Invalid parfume ID', 'parfume-reviews'));
    }
    
    // Get current comparison items from session
    if (!session_id()) {
        session_start();
    }
    
    $comparison_items = isset($_SESSION['parfume_comparison']) ? $_SESSION['parfume_comparison'] : array();
    
    // Remove from comparison
    $comparison_items = array_diff($comparison_items, array($post_id));
    $_SESSION['parfume_comparison'] = array_values($comparison_items);
    
    wp_send_json_success(array(
        'message' => __('Removed from comparison', 'parfume-reviews'),
        'count' => count($comparison_items),
        'items' => $comparison_items,
    ));
}

/**
 * AJAX handler за получаване на информация за сравнение
 */
add_action('wp_ajax_get_comparison_data', 'parfume_reviews_ajax_get_comparison_data');
add_action('wp_ajax_nopriv_get_comparison_data', 'parfume_reviews_ajax_get_comparison_data');

function parfume_reviews_ajax_get_comparison_data() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
        wp_die(__('Security check failed', 'parfume-reviews'));
    }
    
    // Get current comparison items from session
    if (!session_id()) {
        session_start();
    }
    
    $comparison_items = isset($_SESSION['parfume_comparison']) ? $_SESSION['parfume_comparison'] : array();
    
    if (empty($comparison_items)) {
        wp_send_json_success(array(
            'count' => 0,
            'items' => array(),
        ));
    }
    
    $items_data = array();
    
    foreach ($comparison_items as $post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'parfume') {
            $brand = parfume_reviews_get_brand($post_id);
            $price = parfume_reviews_get_price($post_id);
            $rating = get_post_meta($post_id, '_parfume_rating', true);
            
            $items_data[] = array(
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'brand' => $brand ? $brand->name : '',
                'price' => $price,
                'rating' => $rating,
                'url' => get_permalink($post_id),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
            );
        }
    }
    
    wp_send_json_success(array(
        'count' => count($items_data),
        'items' => $items_data,
    ));
}

/**
 * AJAX handler за изчистване на сравнението
 */
add_action('wp_ajax_clear_comparison', 'parfume_reviews_ajax_clear_comparison');
add_action('wp_ajax_nopriv_clear_comparison', 'parfume_reviews_ajax_clear_comparison');

function parfume_reviews_ajax_clear_comparison() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
        wp_die(__('Security check failed', 'parfume-reviews'));
    }
    
    // Clear comparison items from session
    if (!session_id()) {
        session_start();
    }
    
    $_SESSION['parfume_comparison'] = array();
    
    wp_send_json_success(array(
        'message' => __('Comparison cleared', 'parfume-reviews'),
        'count' => 0,
        'items' => array(),
    ));
}

/**
 * AJAX handler за добавяне на ревю
 */
add_action('wp_ajax_add_parfume_review', 'parfume_reviews_ajax_add_review');

function parfume_reviews_ajax_add_review() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume-reviews-nonce')) {
        wp_die(__('Security check failed', 'parfume-reviews'));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(__('You must be logged in to add a review', 'parfume-reviews'));
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review_text = isset($_POST['review_text']) ? wp_kses_post($_POST['review_text']) : '';
    
    // Validate inputs
    if (!$post_id || get_post_type($post_id) !== 'parfume') {
        wp_send_json_error(__('Invalid parfume ID', 'parfume-reviews'));
    }
    
    if ($rating < 1 || $rating > 5) {
        wp_send_json_error(__('Rating must be between 1 and 5', 'parfume-reviews'));
    }
    
    if (empty($review_text)) {
        wp_send_json_error(__('Review text is required', 'parfume-reviews'));
    }
    
    $user_id = get_current_user_id();
    
    // Check if user already reviewed this parfume
    global $wpdb;
    $table_name = $wpdb->prefix . 'parfume_reviews';
    
    $existing_review = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_name WHERE parfume_id = %d AND user_id = %d",
        $post_id,
        $user_id
    ));
    
    if ($existing_review) {
        wp_send_json_error(__('You have already reviewed this parfume', 'parfume-reviews'));
    }
    
    // Add review to database
    $result = $wpdb->insert(
        $table_name,
        array(
            'parfume_id' => $post_id,
            'user_id' => $user_id,
            'rating' => $rating,
            'review_text' => $review_text,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error(__('Failed to save review', 'parfume-reviews'));
    }
    
    wp_send_json_success(array(
        'message' => __('Review added successfully', 'parfume-reviews'),
        'review_id' => $wpdb->insert_id,
    ));
}