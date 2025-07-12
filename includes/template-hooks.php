<?php
/**
 * Template Hooks for Parfume Reviews Plugin
 * 
 * Defines action and filter hooks used in templates
 * 
 * @package Parfume_Reviews
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Archive page hooks
 */

// Before archive content
add_action('parfume_archive_before_content', 'parfume_reviews_archive_header', 10);
add_action('parfume_archive_before_content', 'parfume_reviews_archive_filters', 20);

// Archive sidebar hooks
add_action('parfume_archive_sidebar', 'parfume_reviews_popular_brands_widget', 10);
add_action('parfume_archive_sidebar', 'parfume_reviews_filter_widget', 20);
add_action('parfume_archive_sidebar', 'parfume_reviews_recent_reviews_widget', 30);

// Archive content hooks
add_action('parfume_archive_loop_item', 'parfume_reviews_loop_item_image', 10);
add_action('parfume_archive_loop_item', 'parfume_reviews_loop_item_title', 20);
add_action('parfume_archive_loop_item', 'parfume_reviews_loop_item_brand', 30);
add_action('parfume_archive_loop_item', 'parfume_reviews_loop_item_rating', 40);
add_action('parfume_archive_loop_item', 'parfume_reviews_loop_item_price', 50);

/**
 * Single perfume page hooks
 */

// Single perfume header
add_action('parfume_single_header', 'parfume_reviews_single_title', 10);
add_action('parfume_single_header', 'parfume_reviews_single_rating', 20);
add_action('parfume_single_header', 'parfume_reviews_single_basic_info', 30);

// Single perfume main content
add_action('parfume_single_main_content', 'parfume_reviews_single_image', 10);
add_action('parfume_single_main_content', 'parfume_reviews_single_description', 20);
add_action('parfume_single_main_content', 'parfume_reviews_single_notes', 30);
add_action('parfume_single_main_content', 'parfume_reviews_single_perfumer', 40);

// Single perfume sidebar
add_action('parfume_single_sidebar', 'parfume_reviews_single_stores', 10);
add_action('parfume_single_sidebar', 'parfume_reviews_single_similar', 20);
add_action('parfume_single_sidebar', 'parfume_reviews_single_collections', 30);

// Single perfume footer
add_action('parfume_single_after_content', 'parfume_reviews_single_reviews', 10);
add_action('parfume_single_after_content', 'parfume_reviews_single_related', 20);

/**
 * Blog hooks
 */

// Blog archive hooks
add_action('parfume_blog_archive_before', 'parfume_reviews_blog_archive_header', 10);
add_action('parfume_blog_archive_sidebar', 'parfume_reviews_blog_categories_widget', 10);
add_action('parfume_blog_archive_sidebar', 'parfume_reviews_blog_recent_posts_widget', 20);

// Blog single hooks
add_action('parfume_blog_single_before', 'parfume_reviews_blog_breadcrumbs', 10);
add_action('parfume_blog_single_after', 'parfume_reviews_blog_related_parfumes', 10);
add_action('parfume_blog_single_after', 'parfume_reviews_blog_related_posts', 20);

/**
 * Taxonomy hooks
 */

// Brand page hooks
add_action('parfume_brand_page_header', 'parfume_reviews_brand_logo', 10);
add_action('parfume_brand_page_header', 'parfume_reviews_brand_description', 20);
add_action('parfume_brand_page_header', 'parfume_reviews_brand_stats', 30);

// Notes page hooks
add_action('parfume_notes_page_header', 'parfume_reviews_note_description', 10);
add_action('parfume_notes_page_header', 'parfume_reviews_note_category', 20);

// Perfumer page hooks
add_action('parfume_perfumer_page_header', 'parfume_reviews_perfumer_photo', 10);
add_action('parfume_perfumer_page_header', 'parfume_reviews_perfumer_bio', 20);
add_action('parfume_perfumer_page_header', 'parfume_reviews_perfumer_stats', 30);

/**
 * Hook implementation functions
 */

/**
 * Archive header with title and description
 */
function parfume_reviews_archive_header() {
    if (is_post_type_archive('parfume')) {
        $settings = get_option('parfume_reviews_settings', array());
        ?>
        <header class="parfume-archive-header">
            <h1 class="archive-title"><?php post_type_archive_title(); ?></h1>
            <?php if (!empty($settings['archive_description'])): ?>
                <div class="archive-description">
                    <?php echo wpautop(esc_html($settings['archive_description'])); ?>
                </div>
            <?php endif; ?>
        </header>
        <?php
    }
}

/**
 * Archive filters widget
 */
function parfume_reviews_archive_filters() {
    if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
        echo do_shortcode('[parfume_filters]');
    }
}

/**
 * Popular brands widget
 */
function parfume_reviews_popular_brands_widget() {
    $brands = get_terms(array(
        'taxonomy' => 'marki',
        'orderby' => 'count',
        'order' => 'DESC',
        'number' => 8,
        'hide_empty' => true,
    ));
    
    if (!empty($brands) && !is_wp_error($brands)): ?>
        <div class="popular-brands-widget">
            <h3><?php _e('Popular Brands', 'parfume-reviews'); ?></h3>
            <ul class="brands-list">
                <?php foreach ($brands as $brand): ?>
                    <li>
                        <a href="<?php echo get_term_link($brand); ?>">
                            <?php echo esc_html($brand->name); ?>
                            <span class="count">(<?php echo $brand->count; ?>)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif;
}

/**
 * Filter widget for archive pages
 */
function parfume_reviews_filter_widget() {
    if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
        ?>
        <div class="parfume-filter-widget">
            <h3><?php _e('Filter Perfumes', 'parfume-reviews'); ?></h3>
            <?php echo do_shortcode('[parfume_filters compact="true"]'); ?>
        </div>
        <?php
    }
}

/**
 * Recent reviews widget
 */
function parfume_reviews_recent_reviews_widget() {
    $recent_reviews = get_comments(array(
        'post_type' => 'parfume',
        'status' => 'approve',
        'number' => 5,
        'meta_query' => array(
            array(
                'key' => 'parfume_rating',
                'compare' => 'EXISTS'
            )
        )
    ));
    
    if (!empty($recent_reviews)): ?>
        <div class="recent-reviews-widget">
            <h3><?php _e('Recent Reviews', 'parfume-reviews'); ?></h3>
            <ul class="recent-reviews-list">
                <?php foreach ($recent_reviews as $review): 
                    $rating = get_comment_meta($review->comment_ID, 'parfume_rating', true);
                    $post_title = get_the_title($review->comment_post_ID);
                ?>
                    <li>
                        <div class="review-item">
                            <a href="<?php echo get_permalink($review->comment_post_ID); ?>#comment-<?php echo $review->comment_ID; ?>">
                                <strong><?php echo esc_html($post_title); ?></strong>
                            </a>
                            <div class="review-rating">
                                <?php echo parfume_reviews_display_rating($rating); ?>
                            </div>
                            <div class="review-excerpt">
                                <?php echo wp_trim_words($review->comment_content, 15); ?>
                            </div>
                            <div class="review-author">
                                <?php _e('by', 'parfume-reviews'); ?> <?php echo esc_html($review->comment_author); ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif;
}

/**
 * Loop item hooks implementations
 */

function parfume_reviews_loop_item_image() {
    if (has_post_thumbnail()): ?>
        <div class="parfume-loop-image">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium'); ?>
            </a>
        </div>
    <?php endif;
}

function parfume_reviews_loop_item_title() {
    ?>
    <h3 class="parfume-loop-title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h3>
    <?php
}

function parfume_reviews_loop_item_brand() {
    $brands = wp_get_post_terms(get_the_ID(), 'marki');
    if (!empty($brands) && !is_wp_error($brands)): ?>
        <div class="parfume-loop-brand">
            <?php foreach ($brands as $brand): ?>
                <a href="<?php echo get_term_link($brand); ?>"><?php echo esc_html($brand->name); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif;
}

function parfume_reviews_loop_item_rating() {
    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
    if (!empty($rating)): ?>
        <div class="parfume-loop-rating">
            <?php echo parfume_reviews_display_rating($rating); ?>
        </div>
    <?php endif;
}

function parfume_reviews_loop_item_price() {
    $stores = get_post_meta(get_the_ID(), '_parfume_stores_v2', true);
    if (!empty($stores) && is_array($stores)):
        // Find lowest price
        $lowest_price = null;
        foreach ($stores as $store) {
            if (!empty($store['scraped_data']['variants'])) {
                foreach ($store['scraped_data']['variants'] as $variant) {
                    $price = floatval(preg_replace('/[^\d.]/', '', $variant['price']));
                    if ($price > 0 && ($lowest_price === null || $price < $lowest_price)) {
                        $lowest_price = $price;
                    }
                }
            }
        }
        
        if ($lowest_price): ?>
            <div class="parfume-loop-price">
                <?php _e('From', 'parfume-reviews'); ?> <?php echo number_format($lowest_price, 2); ?> лв.
            </div>
        <?php endif;
    endif;
}

/**
 * Single perfume hooks implementations
 */

function parfume_reviews_single_title() {
    ?>
    <h1 class="parfume-single-title"><?php the_title(); ?></h1>
    <?php
}

function parfume_reviews_single_rating() {
    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
    $rating_count = get_comments_number();
    
    if (!empty($rating)): ?>
        <div class="parfume-single-rating">
            <div class="rating-display">
                <?php echo parfume_reviews_display_rating($rating); ?>
                <span class="rating-text"><?php echo number_format($rating, 1); ?>/5</span>
            </div>
            <?php if ($rating_count > 0): ?>
                <div class="rating-count">
                    <?php printf(_n('%d review', '%d reviews', $rating_count, 'parfume-reviews'), $rating_count); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif;
}

function parfume_reviews_single_basic_info() {
    $brands = wp_get_post_terms(get_the_ID(), 'marki');
    $genders = wp_get_post_terms(get_the_ID(), 'gender');
    $aroma_types = wp_get_post_terms(get_the_ID(), 'aroma_type');
    $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
    
    ?>
    <div class="parfume-basic-info">
        <?php if (!empty($brands) && !is_wp_error($brands)): ?>
            <div class="info-item">
                <span class="label"><?php _e('Brand:', 'parfume-reviews'); ?></span>
                <?php foreach ($brands as $brand): ?>
                    <a href="<?php echo get_term_link($brand); ?>"><?php echo esc_html($brand->name); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($genders) && !is_wp_error($genders)): ?>
            <div class="info-item">
                <span class="label"><?php _e('Gender:', 'parfume-reviews'); ?></span>
                <?php foreach ($genders as $gender): ?>
                    <a href="<?php echo get_term_link($gender); ?>"><?php echo esc_html($gender->name); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($aroma_types) && !is_wp_error($aroma_types)): ?>
            <div class="info-item">
                <span class="label"><?php _e('Aroma Type:', 'parfume-reviews'); ?></span>
                <?php foreach ($aroma_types as $type): ?>
                    <a href="<?php echo get_term_link($type); ?>"><?php echo esc_html($type->name); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($release_year)): ?>
            <div class="info-item">
                <span class="label"><?php _e('Release Year:', 'parfume-reviews'); ?></span>
                <span><?php echo esc_html($release_year); ?></span>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Taxonomy page hooks implementations
 */

function parfume_reviews_brand_logo() {
    if (is_tax('marki')) {
        $term = get_queried_object();
        $logo_id = get_term_meta($term->term_id, 'brand-image-id', true);
        
        if ($logo_id): ?>
            <div class="brand-logo">
                <?php echo wp_get_attachment_image($logo_id, 'medium'); ?>
            </div>
        <?php endif;
    }
}

function parfume_reviews_brand_description() {
    if (is_tax('marki')) {
        $term = get_queried_object();
        if (!empty($term->description)): ?>
            <div class="brand-description">
                <?php echo wpautop($term->description); ?>
            </div>
        <?php endif;
    }
}

function parfume_reviews_brand_stats() {
    if (is_tax('marki')) {
        $term = get_queried_object();
        $perfume_count = $term->count;
        
        // Get average rating for this brand
        $posts = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'marki',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_parfume_rating',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $total_rating = 0;
        $rating_count = 0;
        
        foreach ($posts as $post) {
            $rating = get_post_meta($post->ID, '_parfume_rating', true);
            if (!empty($rating)) {
                $total_rating += floatval($rating);
                $rating_count++;
            }
        }
        
        $average_rating = $rating_count > 0 ? $total_rating / $rating_count : 0;
        
        ?>
        <div class="brand-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $perfume_count; ?></span>
                <span class="stat-label"><?php _e('Perfumes', 'parfume-reviews'); ?></span>
            </div>
            <?php if ($average_rating > 0): ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($average_rating, 1); ?></span>
                    <span class="stat-label"><?php _e('Average Rating', 'parfume-reviews'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

function parfume_reviews_perfumer_photo() {
    if (is_tax('perfumer')) {
        $term = get_queried_object();
        $photo_id = get_term_meta($term->term_id, 'perfumer-image-id', true);
        
        if ($photo_id): ?>
            <div class="perfumer-photo">
                <?php echo wp_get_attachment_image($photo_id, 'medium'); ?>
            </div>
        <?php endif;
    }
}

function parfume_reviews_perfumer_bio() {
    if (is_tax('perfumer')) {
        $term = get_queried_object();
        if (!empty($term->description)): ?>
            <div class="perfumer-bio">
                <?php echo wpautop($term->description); ?>
            </div>
        <?php endif;
    }
}

function parfume_reviews_perfumer_stats() {
    if (is_tax('perfumer')) {
        $term = get_queried_object();
        ?>
        <div class="perfumer-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $term->count; ?></span>
                <span class="stat-label"><?php _e('Created Perfumes', 'parfume-reviews'); ?></span>
            </div>
        </div>
        <?php
    }
}

/**
 * Blog hooks implementations
 */

function parfume_reviews_blog_archive_header() {
    if (is_post_type_archive('parfume_blog')): ?>
        <header class="blog-archive-header">
            <h1><?php _e('Perfume Blog', 'parfume-reviews'); ?></h1>
            <p><?php _e('Latest news, reviews and articles about perfumes', 'parfume-reviews'); ?></p>
        </header>
    <?php endif;
}

function parfume_reviews_blog_categories_widget() {
    // Blog categories widget implementation
    $categories = get_categories(array(
        'taxonomy' => 'category',
        'hide_empty' => true,
        'number' => 10
    ));
    
    if (!empty($categories)): ?>
        <div class="blog-categories-widget">
            <h3><?php _e('Categories', 'parfume-reviews'); ?></h3>
            <ul>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="<?php echo get_category_link($category); ?>">
                            <?php echo esc_html($category->name); ?>
                            <span class="count">(<?php echo $category->count; ?>)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif;
}

function parfume_reviews_blog_recent_posts_widget() {
    $recent_posts = get_posts(array(
        'post_type' => 'parfume_blog',
        'posts_per_page' => 5,
        'post_status' => 'publish'
    ));
    
    if (!empty($recent_posts)): ?>
        <div class="blog-recent-posts-widget">
            <h3><?php _e('Recent Posts', 'parfume-reviews'); ?></h3>
            <ul>
                <?php foreach ($recent_posts as $post): ?>
                    <li>
                        <a href="<?php echo get_permalink($post); ?>">
                            <?php echo esc_html($post->post_title); ?>
                        </a>
                        <span class="post-date"><?php echo get_the_date('', $post); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif;
}

function parfume_reviews_blog_breadcrumbs() {
    if (is_singular('parfume_blog')): ?>
        <div class="blog-breadcrumbs">
            <a href="<?php echo home_url(); ?>"><?php _e('Home', 'parfume-reviews'); ?></a>
            <span class="separator"> / </span>
            <a href="<?php echo get_post_type_archive_link('parfume_blog'); ?>"><?php _e('Blog', 'parfume-reviews'); ?></a>
            <span class="separator"> / </span>
            <span class="current"><?php the_title(); ?></span>
        </div>
    <?php endif;
}

function parfume_reviews_blog_related_parfumes() {
    if (is_singular('parfume_blog')) {
        $related_parfumes = get_post_meta(get_the_ID(), '_blog_related_parfumes', true);
        
        if (!empty($related_parfumes) && is_array($related_parfumes)): ?>
            <div class="blog-related-parfumes">
                <h3><?php _e('Related Perfumes', 'parfume-reviews'); ?></h3>
                <?php echo do_shortcode('[parfume_grid ids="' . implode(',', $related_parfumes) . '" columns="3"]'); ?>
            </div>
        <?php endif;
    }
}

function parfume_reviews_blog_related_posts() {
    if (is_singular('parfume_blog')) {
        $related_posts = get_posts(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => 3,
            'post__not_in' => array(get_the_ID()),
            'orderby' => 'rand'
        ));
        
        if (!empty($related_posts)): ?>
            <div class="blog-related-posts">
                <h3><?php _e('Related Articles', 'parfume-reviews'); ?></h3>
                <div class="related-posts-grid">
                    <?php foreach ($related_posts as $post): ?>
                        <article class="related-post-item">
                            <?php if (has_post_thumbnail($post->ID)): ?>
                                <div class="post-thumbnail">
                                    <a href="<?php echo get_permalink($post); ?>">
                                        <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <h4><a href="<?php echo get_permalink($post); ?>"><?php echo esc_html($post->post_title); ?></a></h4>
                            <div class="post-excerpt"><?php echo wp_trim_words($post->post_content, 20); ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif;
    }
}