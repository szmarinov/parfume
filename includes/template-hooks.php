<?php
/**
 * Parfume Reviews Template Hooks
 * 
 * ВАЖНО: Този файл дефинира всички action и filter hooks за template система
 * Предоставя гъвкавост за developers и themes да модифицират изгледа
 *
 * @package Parfume_Reviews
 * @since 1.0.0
 */

// ВАЖНО: Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// РАЗДЕЛ 1: Core Template Hooks
// ========================================

/**
 * ВАЖНО: Hook system за template loading
 * Позволява на developers да модифицират template логиката
 */

/**
 * ВАЖНО: Fires before any parfume review template is loaded
 * 
 * @param string $template Template name being loaded
 * @param array $args Template arguments
 */
do_action('parfume_reviews_before_template_load', '', array());

/**
 * ВАЖНО: Fires after any parfume review template is loaded
 * 
 * @param string $template Template name that was loaded
 * @param array $args Template arguments that were used
 */
do_action('parfume_reviews_after_template_load', '', array());

/**
 * ВАЖНО: Filter for modifying template path
 * 
 * @param string $template_path Path to template file
 * @param string $template_name Template name
 * @param array $args Template arguments
 */
apply_filters('parfume_reviews_template_path', '', '', array());

/**
 * ВАЖНО: Filter for template arguments
 * 
 * @param array $args Template arguments
 * @param string $template_name Template name
 */
apply_filters('parfume_reviews_template_args', array(), '');

// ========================================
// РАЗДЕЛ 2: Single Review Display Hooks
// ========================================

/**
 * ВАЖНО: Single review template hooks
 * Запазени оригинални hooks с подобрения
 */

/**
 * Hook: parfume_reviews_single_review_before
 * 
 * ВАЖНО: Fires before single review content
 * 
 * @hooked parfume_reviews_single_breadcrumbs - 10
 * @hooked parfume_reviews_single_navigation - 20
 */
do_action('parfume_reviews_single_review_before');

/**
 * Hook: parfume_reviews_single_review_header
 * 
 * ВАЖНО: Fires in single review header section
 * 
 * @hooked parfume_reviews_single_title - 10
 * @hooked parfume_reviews_single_meta - 20
 * @hooked parfume_reviews_single_rating - 30
 */
do_action('parfume_reviews_single_review_header');

/**
 * Hook: parfume_reviews_single_review_image
 * 
 * ВАЖНО: Fires for single review featured image
 * 
 * @hooked parfume_reviews_single_featured_image - 10
 * @hooked parfume_reviews_single_gallery - 20
 */
do_action('parfume_reviews_single_review_image');

/**
 * Hook: parfume_reviews_single_review_content
 * 
 * ВАЖНО: Fires for single review main content
 * 
 * @hooked parfume_reviews_single_content - 10
 * @hooked parfume_reviews_single_details - 20
 * @hooked parfume_reviews_single_notes - 30
 */
do_action('parfume_reviews_single_review_content');

/**
 * Hook: parfume_reviews_single_review_sidebar
 * 
 * ВАЖНО: Fires for single review sidebar content
 * 
 * @hooked parfume_reviews_single_price_info - 10
 * @hooked parfume_reviews_single_stores - 20
 * @hooked parfume_reviews_single_comparison - 30
 * @hooked parfume_reviews_single_related - 40
 */
do_action('parfume_reviews_single_review_sidebar');

/**
 * Hook: parfume_reviews_single_review_footer
 * 
 * ВАЖНО: Fires in single review footer section
 * 
 * @hooked parfume_reviews_single_tags - 10
 * @hooked parfume_reviews_single_categories - 20
 * @hooked parfume_reviews_single_share - 30
 */
do_action('parfume_reviews_single_review_footer');

/**
 * Hook: parfume_reviews_single_review_after
 * 
 * ВАЖНО: Fires after single review content
 * 
 * @hooked parfume_reviews_single_comments - 10
 * @hooked parfume_reviews_single_related_products - 20
 * @hooked parfume_reviews_single_navigation_bottom - 30
 */
do_action('parfume_reviews_single_review_after');

// ========================================
// РАЗДЕЛ 3: Archive/List Display Hooks
// ========================================

/**
 * ВАЖНО: Archive template hooks
 * Запазени оригинални hooks за списъци
 */

/**
 * Hook: parfume_reviews_archive_before
 * 
 * ВАЖНО: Fires before archive content
 * 
 * @hooked parfume_reviews_archive_header - 10
 * @hooked parfume_reviews_archive_breadcrumbs - 20
 */
do_action('parfume_reviews_archive_before');

/**
 * Hook: parfume_reviews_archive_header
 * 
 * VAŽНО: Fires in archive header section
 * 
 * @hooked parfume_reviews_archive_title - 10
 * @hooked parfume_reviews_archive_description - 20
 * @hooked parfume_reviews_archive_filters - 30
 */
do_action('parfume_reviews_archive_header');

/**
 * Hook: parfume_reviews_archive_loop_before
 * 
 * ВАЖНО: Fires before archive loop starts
 * 
 * @hooked parfume_reviews_archive_sorting - 10
 * @hooked parfume_reviews_archive_view_toggle - 20
 */
do_action('parfume_reviews_archive_loop_before');

/**
 * Hook: parfume_reviews_archive_loop_item_before
 * 
 * ВАЖНО: Fires before each loop item
 * 
 * @param WP_Post $post Current post object
 */
do_action('parfume_reviews_archive_loop_item_before', null);

/**
 * Hook: parfume_reviews_archive_loop_item
 * 
 * ВАЖНО: Fires for each loop item content
 * 
 * @hooked parfume_reviews_loop_item_image - 10
 * @hooked parfume_reviews_loop_item_title - 20
 * @hooked parfume_reviews_loop_item_excerpt - 30
 * @hooked parfume_reviews_loop_item_meta - 40
 * @hooked parfume_reviews_loop_item_rating - 50
 * @hooked parfume_reviews_loop_item_price - 60
 * 
 * @param WP_Post $post Current post object
 */
do_action('parfume_reviews_archive_loop_item', null);

/**
 * Hook: parfume_reviews_archive_loop_item_after
 * 
 * ВАЖНО: Fires after each loop item
 * 
 * @param WP_Post $post Current post object
 */
do_action('parfume_reviews_archive_loop_item_after', null);

/**
 * Hook: parfume_reviews_archive_loop_after
 * 
 * ВАЖНО: Fires after archive loop ends
 * 
 * @hooked parfume_reviews_archive_pagination - 10
 * @hooked parfume_reviews_archive_load_more - 20
 */
do_action('parfume_reviews_archive_loop_after');

/**
 * Hook: parfume_reviews_archive_after
 * 
 * ВАЖНО: Fires after archive content
 * 
 * @hooked parfume_reviews_archive_footer - 10
 */
do_action('parfume_reviews_archive_after');

// ========================================
// РАЗДЕЛ 4: Search & Filter Hooks
// ========================================

/**
 * ВАЖНО: Search and filtering hooks
 * Запазени с разширена функционалност
 */

/**
 * Hook: parfume_reviews_search_form_before
 * 
 * ВАЖНО: Fires before search form
 */
do_action('parfume_reviews_search_form_before');

/**
 * Hook: parfume_reviews_search_form
 * 
 * ВАЖНО: Fires for search form content
 * 
 * @hooked parfume_reviews_search_input - 10
 * @hooked parfume_reviews_search_filters - 20
 * @hooked parfume_reviews_search_submit - 30
 */
do_action('parfume_reviews_search_form');

/**
 * Hook: parfume_reviews_search_form_after
 * 
 * ВАЖНО: Fires after search form
 */
do_action('parfume_reviews_search_form_after');

/**
 * Hook: parfume_reviews_filters_before
 * 
 * ВАЖНО: Fires before filter section
 */
do_action('parfume_reviews_filters_before');

/**
 * Hook: parfume_reviews_filters
 * 
 * ВАЖНО: Fires for filters content
 * 
 * @hooked parfume_reviews_filter_by_brand - 10
 * @hooked parfume_reviews_filter_by_category - 20
 * @hooked parfume_reviews_filter_by_price - 30
 * @hooked parfume_reviews_filter_by_rating - 40
 * @hooked parfume_reviews_filter_by_year - 50
 */
do_action('parfume_reviews_filters');

/**
 * Hook: parfume_reviews_filters_after
 * 
 * ВАЖНО: Fires after filter section
 */
do_action('parfume_reviews_filters_after');

// ========================================
// РАЗДЕЛ 5: Comparison Hooks
// ========================================

/**
 * ВАЖНО: Comparison functionality hooks
 * Запазени оригинални comparison hooks
 */

/**
 * Hook: parfume_reviews_comparison_before
 * 
 * ВАЖНО: Fires before comparison table
 */
do_action('parfume_reviews_comparison_before');

/**
 * Hook: parfume_reviews_comparison_header
 * 
 * ВАЖНО: Fires for comparison table header
 * 
 * @hooked parfume_reviews_comparison_title - 10
 * @hooked parfume_reviews_comparison_controls - 20
 */
do_action('parfume_reviews_comparison_header');

/**
 * Hook: parfume_reviews_comparison_table
 * 
 * ВАЖНО: Fires for comparison table content
 * 
 * @hooked parfume_reviews_comparison_row_image - 10
 * @hooked parfume_reviews_comparison_row_title - 20
 * @hooked parfume_reviews_comparison_row_rating - 30
 * @hooked parfume_reviews_comparison_row_price - 40
 * @hooked parfume_reviews_comparison_row_details - 50
 */
do_action('parfume_reviews_comparison_table');

/**
 * Hook: parfume_reviews_comparison_footer
 * 
 * ВАЖНО: Fires for comparison table footer
 * 
 * @hooked parfume_reviews_comparison_actions - 10
 */
do_action('parfume_reviews_comparison_footer');

/**
 * Hook: parfume_reviews_comparison_after
 * 
 * ВАЖНО: Fires after comparison table
 */
do_action('parfume_reviews_comparison_after');

// ========================================
// РАЗДЕЛ 6: Widget & Shortcode Hooks
// ========================================

/**
 * ВАЖНО: Widget and shortcode hooks
 * Запазени с подобрения
 */

/**
 * Hook: parfume_reviews_widget_before
 * 
 * ВАЖНО: Fires before widget content
 * 
 * @param array $args Widget arguments
 * @param array $instance Widget instance
 */
do_action('parfume_reviews_widget_before', array(), array());

/**
 * Hook: parfume_reviews_widget_title
 * 
 * ВАЖНО: Fires for widget title
 * 
 * @param string $title Widget title
 * @param array $args Widget arguments
 * @param array $instance Widget instance
 */
do_action('parfume_reviews_widget_title', '', array(), array());

/**
 * Hook: parfume_reviews_widget_content
 * 
 * ВАЖНО: Fires for widget main content
 * 
 * @param array $args Widget arguments
 * @param array $instance Widget instance
 */
do_action('parfume_reviews_widget_content', array(), array());

/**
 * Hook: parfume_reviews_widget_after
 * 
 * ВАЖНО: Fires after widget content
 * 
 * @param array $args Widget arguments
 * @param array $instance Widget instance
 */
do_action('parfume_reviews_widget_after', array(), array());

/**
 * Hook: parfume_reviews_shortcode_before
 * 
 * ВАЖНО: Fires before shortcode output
 * 
 * @param array $atts Shortcode attributes
 * @param string $content Shortcode content
 * @param string $tag Shortcode tag
 */
do_action('parfume_reviews_shortcode_before', array(), '', '');

/**
 * Hook: parfume_reviews_shortcode_content
 * 
 * ВАЖНО: Fires for shortcode main content
 * 
 * @param array $atts Shortcode attributes
 * @param string $content Shortcode content
 * @param string $tag Shortcode tag
 */
do_action('parfume_reviews_shortcode_content', array(), '', '');

/**
 * Hook: parfume_reviews_shortcode_after
 * 
 * ВАЖНО: Fires after shortcode output
 * 
 * @param array $atts Shortcode attributes
 * @param string $content Shortcode content
 * @param string $tag Shortcode tag
 */
do_action('parfume_reviews_shortcode_after', array(), '', '');

// ========================================
// РАЗДЕЛ 7: Asset & Script Hooks
// ========================================

/**
 * ВАЖНО: Asset loading hooks
 * Запазени за съвместимост
 */

/**
 * Hook: parfume_reviews_enqueue_scripts
 * 
 * ВАЖНО: Fires when enqueueing scripts
 */
do_action('parfume_reviews_enqueue_scripts');

/**
 * Hook: parfume_reviews_enqueue_styles
 * 
 * ВАЖНО: Fires when enqueueing styles
 */
do_action('parfume_reviews_enqueue_styles');

/**
 * Hook: parfume_reviews_localize_scripts
 * 
 * ВАЖНО: Fires when localizing scripts
 * 
 * @param array $localize_data Data for localization
 */
do_action('parfume_reviews_localize_scripts', array());

// ========================================
// РАЗДЕЛ 8: SEO & Meta Hooks
// ========================================

/**
 * ВАЖНО: SEO and meta information hooks
 * Нови hooks за SEO подобрения
 */

/**
 * Hook: parfume_reviews_head
 * 
 * ВАЖНО: Fires in document head for SEO meta
 */
do_action('parfume_reviews_head');

/**
 * Hook: parfume_reviews_meta_before
 * 
 * ВАЖНО: Fires before meta information
 */
do_action('parfume_reviews_meta_before');

/**
 * Hook: parfume_reviews_meta
 * 
 * ВАЖНО: Fires for meta information output
 * 
 * @hooked parfume_reviews_meta_brand - 10
 * @hooked parfume_reviews_meta_rating - 20
 * @hooked parfume_reviews_meta_price - 30
 * @hooked parfume_reviews_meta_release_year - 40
 */
do_action('parfume_reviews_meta');

/**
 * Hook: parfume_reviews_meta_after
 * 
 * ВАЖНО: Fires after meta information
 */
do_action('parfume_reviews_meta_after');

/**
 * Hook: parfume_reviews_structured_data
 * 
 * ВАЖНО: Fires for structured data output
 * 
 * @param array $structured_data Schema.org data
 */
do_action('parfume_reviews_structured_data', array());

// ========================================
// РАЗДЕЛ 9: Admin & Backend Hooks
// ========================================

/**
 * ВАЖНО: Admin interface hooks
 * Запазени admin hooks
 */

/**
 * Hook: parfume_reviews_admin_header
 * 
 * ВАЖНО: Fires in admin header
 */
do_action('parfume_reviews_admin_header');

/**
 * Hook: parfume_reviews_admin_menu
 * 
 * ВАЖНО: Fires when adding admin menu items
 */
do_action('parfume_reviews_admin_menu');

/**
 * Hook: parfume_reviews_admin_enqueue_scripts
 * 
 * ВАЖНО: Fires when enqueueing admin scripts
 * 
 * @param string $hook_suffix Current admin page
 */
do_action('parfume_reviews_admin_enqueue_scripts', '');

/**
 * Hook: parfume_reviews_save_post
 * 
 * ВАЖНО: Fires when saving parfume review post
 * 
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @param bool $update Whether this is an update
 */
do_action('parfume_reviews_save_post', 0, null, false);

/**
 * Hook: parfume_reviews_delete_post
 * 
 * ВАЖНО: Fires when deleting parfume review post
 * 
 * @param int $post_id Post ID
 */
do_action('parfume_reviews_delete_post', 0);

// ========================================
// РАЗДЕЛ 10: Filter Hooks
// ========================================

/**
 * ВАЖНО: Content filtering hooks
 * Запазени filter hooks за модификация на съдържанието
 */

/**
 * Filter: parfume_reviews_content
 * 
 * ВАЖНО: Filters the main review content
 * 
 * @param string $content Review content
 * @param WP_Post $post Post object
 */
apply_filters('parfume_reviews_content', '', null);

/**
 * Filter: parfume_reviews_excerpt
 * 
 * ВАЖНО: Filters the review excerpt
 * 
 * @param string $excerpt Review excerpt
 * @param WP_Post $post Post object
 */
apply_filters('parfume_reviews_excerpt', '', null);

/**
 * Filter: parfume_reviews_title
 * 
 * ВАЖНО: Filters the review title
 * 
 * @param string $title Review title
 * @param WP_Post $post Post object
 */
apply_filters('parfume_reviews_title', '', null);

/**
 * Filter: parfume_reviews_rating
 * 
 * ВАЖНО: Filters the review rating
 * 
 * @param float $rating Review rating
 * @param int $post_id Post ID
 */
apply_filters('parfume_reviews_rating', 0.0, 0);

/**
 * Filter: parfume_reviews_price
 * 
 * ВАЖНО: Filters the review price display
 * 
 * @param string $price Formatted price
 * @param float $raw_price Raw price value
 * @param int $post_id Post ID
 */
apply_filters('parfume_reviews_price', '', 0.0, 0);

/**
 * Filter: parfume_reviews_brand
 * 
 * VAŽNO: Filters the brand display
 * 
 * @param string $brand Brand name
 * @param int $post_id Post ID
 */
apply_filters('parfume_reviews_brand', '', 0);

/**
 * Filter: parfume_reviews_categories
 * 
 * ВАЖНО: Filters the categories display
 * 
 * @param array $categories Category terms
 * @param int $post_id Post ID
 */
apply_filters('parfume_reviews_categories', array(), 0);

/**
 * Filter: parfume_reviews_tags
 * 
 * ВАЖНО: Filters the tags display
 * 
 * @param array $tags Tag terms
 * @param int $post_id Post ID
 */
apply_filters('parfume_reviews_tags', array(), 0);

/**
 * Filter: parfume_reviews_notes
 * 
 * ВАЖНО: Filters the fragrance notes
 * 
 * @param array $notes Notes array
 * @param int $post_id Post ID
 */
apply_filters('parfume_reviews_notes', array(), 0);

/**
 * Filter: parfume_reviews_stores
 * 
 * ВАЖНО: Filters the store information
 * 
 * @param array $stores Store data
 * @param int $post_id Post ID
 */
apply_filters('parfume_reviews_stores', array(), 0);

/**
 * Filter: parfume_reviews_query_args
 * 
 * ВАЖНО: Filters query arguments for reviews
 * 
 * @param array $args WP_Query arguments
 * @param string $context Query context
 */
apply_filters('parfume_reviews_query_args', array(), '');

/**
 * Filter: parfume_reviews_loop_class
 * 
 * ВАЖНО: Filters CSS classes for loop container
 * 
 * @param array $classes CSS classes
 * @param string $context Loop context
 */
apply_filters('parfume_reviews_loop_class', array(), '');

/**
 * Filter: parfume_reviews_item_class
 * 
 * ВАЖНО: Filters CSS classes for individual items
 * 
 * @param array $classes CSS classes
 * @param WP_Post $post Post object
 */
apply_filters('parfume_reviews_item_class', array(), null);

/**
 * Filter: parfume_reviews_pagination_args
 * 
 * ВАЖНО: Filters pagination arguments
 * 
 * @param array $args Pagination arguments
 * @param WP_Query $query Query object
 */
apply_filters('parfume_reviews_pagination_args', array(), null);

// ========================================
// РАЗДЕЛ 11: Integration Hooks
// ========================================

/**
 * ВАЖНО: Third-party integration hooks
 * Нови hooks за интеграция с други плъгини
 */

/**
 * Hook: parfume_reviews_woocommerce_integration
 * 
 * ВАЖНО: Fires for WooCommerce integration
 * 
 * @param int $product_id WooCommerce product ID
 * @param int $review_id Parfume review ID
 */
do_action('parfume_reviews_woocommerce_integration', 0, 0);

/**
 * Hook: parfume_reviews_yoast_seo_integration
 * 
 * ВАЖНО: Fires for Yoast SEO integration
 * 
 * @param int $post_id Post ID
 */
do_action('parfume_reviews_yoast_seo_integration', 0);

/**
 * Hook: parfume_reviews_elementor_integration
 * 
 * VAŽNO: Fires for Elementor integration
 */
do_action('parfume_reviews_elementor_integration');

/**
 * Hook: parfume_reviews_gutenberg_integration
 * 
 * ВАЖНО: Fires for Gutenberg blocks integration
 */
do_action('parfume_reviews_gutenberg_integration');

// ========================================
// РАЗДЕЛ 12: Performance & Caching Hooks
// ========================================

/**
 * ВАЖНО: Performance and caching hooks
 * Нови hooks за performance optimization
 */

/**
 * Hook: parfume_reviews_cache_clear
 * 
 * ВАЖНО: Fires when clearing cache
 * 
 * @param string $cache_key Cache key being cleared
 */
do_action('parfume_reviews_cache_clear', '');

/**
 * Hook: parfume_reviews_cache_set
 * 
 * ВАЖНО: Fires when setting cache
 * 
 * @param string $cache_key Cache key
 * @param mixed $data Cache data
 * @param int $expiration Cache expiration
 */
do_action('parfume_reviews_cache_set', '', null, 0);

/**
 * Filter: parfume_reviews_cache_key
 * 
 * ВАЖНО: Filters cache key generation
 * 
 * @param string $cache_key Generated cache key
 * @param array $args Cache key arguments
 */
apply_filters('parfume_reviews_cache_key', '', array());

/**
 * Filter: parfume_reviews_cache_expiration
 * 
 * ВАЖНО: Filters cache expiration time
 * 
 * @param int $expiration Expiration time in seconds
 * @param string $cache_type Type of cache
 */
apply_filters('parfume_reviews_cache_expiration', 0, '');

// ========================================
// РАЗДЕЛ 13: Debug & Development Hooks
// ========================================

/**
 * ВАЖНО: Debug and development hooks
 * Нови hooks за debugging
 */

/**
 * Hook: parfume_reviews_debug
 * 
 * ВАЖНО: Fires for debug information
 * 
 * @param string $message Debug message
 * @param string $type Debug type
 * @param array $data Additional debug data
 */
do_action('parfume_reviews_debug', '', '', array());

/**
 * Filter: parfume_reviews_debug_enabled
 * 
 * ВАЖНО: Filters whether debug is enabled
 * 
 * @param bool $enabled Debug enabled status
 * @param string $context Debug context
 */
apply_filters('parfume_reviews_debug_enabled', false, '');

/**
 * Filter: parfume_reviews_debug_output
 * 
 * ВАЖНО: Filters debug output format
 * 
 * @param string $output Debug output
 * @param string $message Original message
 * @param array $data Debug data
 */
apply_filters('parfume_reviews_debug_output', '', '', array());

// ========================================
// Hook Documentation Footer
// ========================================

/**
 * ВАЖНО: Hook Usage Examples
 * 
 * // Adding content before single review
 * add_action('parfume_reviews_single_review_before', 'my_custom_function', 15);
 * 
 * // Modifying review rating display
 * add_filter('parfume_reviews_rating', 'my_rating_modifier', 10, 2);
 * 
 * // Adding custom meta information
 * add_action('parfume_reviews_meta', 'my_custom_meta', 35);
 * 
 * // Modifying query arguments
 * add_filter('parfume_reviews_query_args', 'my_query_modifier', 10, 2);
 * 
 * // Adding custom CSS classes
 * add_filter('parfume_reviews_item_class', 'my_custom_classes', 10, 2);
 */

// ВАЖНО: Debug се извиква само когато функцията е достъпна
if (function_exists('parfume_reviews_debug')) {
    parfume_reviews_debug('Template hooks loaded', 'hooks');
}