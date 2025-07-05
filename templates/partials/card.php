<?php
/**
 * Product Card Module for Parfume Reviews
 * 
 * Reusable card component for displaying perfumes in various contexts
 * 
 * @param int    $post_id      The perfume post ID
 * @param string $layout       Card layout: 'grid', 'list', 'featured', 'minimal'
 * @param array  $options      Display options
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default parameters
$post_id = isset($post_id) ? intval($post_id) : get_the_ID();
$layout = isset($layout) ? sanitize_text_field($layout) : 'grid';
$options = isset($options) ? $options : array();

// Default options
$default_options = array(
    'show_image' => true,
    'show_brand' => true,
    'show_title' => true,
    'show_rating' => true,
    'show_price' => true,
    'show_availability' => true,
    'show_shipping' => true,
    'show_notes' => false,
    'show_year' => false,
    'show_excerpt' => false,
    'show_actions' => true,
    'image_size' => 'medium',
    'notes_limit' => 3,
    'excerpt_words' => 20,
);

$options = wp_parse_args($options, $default_options);

// Get post data
$post = get_post($post_id);
if (!$post || $post->post_type !== 'parfume') {
    return;
}

// Get perfume data
$brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
$rating = get_post_meta($post_id, '_parfume_rating', true);
$release_year = get_post_meta($post_id, '_parfume_release_year', true);
$stores = get_post_meta($post_id, '_parfume_stores', true);
$notes = wp_get_post_terms($post_id, 'notes', array('number' => $options['notes_limit']));

// Handle WP_Error
if (is_wp_error($brands)) $brands = array();
if (is_wp_error($notes)) $notes = array();

// Get price info
$lowest_store = parfume_reviews_get_lowest_price($post_id);
$is_available = parfume_reviews_is_available($post_id);
$cheapest_shipping = parfume_reviews_get_cheapest_shipping($post_id);

// Generate CSS classes
$card_classes = array('parfume-card', 'parfume-card--' . $layout);
if (!empty($options['additional_classes'])) {
    $card_classes = array_merge($card_classes, (array) $options['additional_classes']);
}

// Get permalink
$permalink = get_permalink($post_id);
?>

<article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
    
    <?php if ($options['show_image'] && (has_post_thumbnail($post_id) || $layout !== 'minimal')): ?>
        <div class="parfume-card__image">
            <a href="<?php echo esc_url($permalink); ?>" class="parfume-card__image-link">
                <?php if (has_post_thumbnail($post_id)): ?>
                    <?php echo get_the_post_thumbnail($post_id, $options['image_size'], array(
                        'class' => 'parfume-card__image-img',
                        'alt' => get_the_title($post_id),
                        'loading' => 'lazy'
                    )); ?>
                <?php else: ?>
                    <div class="parfume-card__image-placeholder">
                        <svg class="parfume-card__image-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="parfume-card__image-text"><?php _e('No Image', 'parfume-reviews'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($layout === 'featured' && !empty($rating)): ?>
                    <div class="parfume-card__badge">
                        <span class="parfume-card__badge-rating"><?php echo number_format(floatval($rating), 1); ?></span>
                        <span class="parfume-card__badge-star">★</span>
                    </div>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="parfume-card__content">
        
        <?php if ($options['show_brand'] && !empty($brands)): ?>
            <div class="parfume-card__brand">
                <?php echo esc_html($brands[0]); ?>
                <?php if ($options['show_year'] && !empty($release_year)): ?>
                    <span class="parfume-card__year">(<?php echo esc_html($release_year); ?>)</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($options['show_title']): ?>
            <h3 class="parfume-card__title">
                <a href="<?php echo esc_url($permalink); ?>" class="parfume-card__title-link">
                    <?php echo esc_html(get_the_title($post_id)); ?>
                </a>
            </h3>
        <?php endif; ?>
        
        <?php if ($options['show_excerpt'] && $layout !== 'minimal'): ?>
            <div class="parfume-card__excerpt">
                <?php 
                $excerpt = get_the_excerpt($post_id);
                if (!empty($excerpt)) {
                    echo wp_trim_words($excerpt, $options['excerpt_words']);
                } else {
                    $content = get_post_field('post_content', $post_id);
                    echo wp_trim_words(wp_strip_all_tags($content), $options['excerpt_words']);
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($options['show_rating'] && !empty($rating)): ?>
            <div class="parfume-card__rating">
                <div class="parfume-card__rating-stars">
                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                </div>
                <span class="parfume-card__rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
            </div>
        <?php endif; ?>
        
        <?php if ($options['show_notes'] && !empty($notes) && $layout !== 'minimal'): ?>
            <div class="parfume-card__notes">
                <span class="parfume-card__notes-label"><?php _e('Notes:', 'parfume-reviews'); ?></span>
                <?php foreach ($notes as $note): ?>
                    <span class="parfume-card__note-tag"><?php echo esc_html($note->name); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (($options['show_price'] || $options['show_availability'] || $options['show_shipping']) && $layout !== 'minimal'): ?>
            <div class="parfume-card__meta">
                
                <?php if ($options['show_price'] && $lowest_store): ?>
                    <div class="parfume-card__price">
                        <span class="parfume-card__price-label"><?php _e('from', 'parfume-reviews'); ?></span>
                        <span class="parfume-card__price-value"><?php echo esc_html($lowest_store['price']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($options['show_availability']): ?>
                    <div class="parfume-card__availability parfume-card__availability--<?php echo $is_available ? 'available' : 'unavailable'; ?>">
                        <svg class="parfume-card__availability-icon" viewBox="0 0 16 16" fill="currentColor">
                            <?php if ($is_available): ?>
                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                            <?php else: ?>
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            <?php endif; ?>
                        </svg>
                        <span class="parfume-card__availability-text">
                            <?php echo $is_available ? __('Available', 'parfume-reviews') : __('Unavailable', 'parfume-reviews'); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ($options['show_shipping'] && $cheapest_shipping): ?>
                    <div class="parfume-card__shipping">
                        <svg class="parfume-card__shipping-icon" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7zm1.294 7.456A1.999 1.999 0 0 1 4.732 11h5.536a2.01 2.01 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456zM12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12v4zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                        </svg>
                        <span class="parfume-card__shipping-text"><?php echo esc_html($cheapest_shipping); ?></span>
                    </div>
                <?php endif; ?>
                
            </div>
        <?php endif; ?>
        
        <?php if ($options['show_actions']): ?>
            <div class="parfume-card__actions">
                
                <?php if (function_exists('parfume_reviews_get_comparison_button')): ?>
                    <?php echo parfume_reviews_get_comparison_button($post_id); ?>
                <?php else: ?>
                    <button class="parfume-card__action parfume-card__action--compare" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="add-to-comparison">
                        <svg class="parfume-card__action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2z"/>
                            <path d="M20 11h-4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2z"/>
                            <path d="M7 21V9a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v12"/>
                        </svg>
                        <span class="parfume-card__action-text"><?php _e('Compare', 'parfume-reviews'); ?></span>
                    </button>
                <?php endif; ?>
                
                <?php if (function_exists('parfume_reviews_get_collections_dropdown')): ?>
                    <?php echo parfume_reviews_get_collections_dropdown($post_id); ?>
                <?php else: ?>
                    <button class="parfume-card__action parfume-card__action--wishlist" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="add-to-wishlist">
                        <svg class="parfume-card__action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span class="parfume-card__action-text"><?php _e('Wishlist', 'parfume-reviews'); ?></span>
                    </button>
                <?php endif; ?>
                
                <?php if ($layout === 'list'): ?>
                    <a href="<?php echo esc_url($permalink); ?>" class="parfume-card__action parfume-card__action--view">
                        <svg class="parfume-card__action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span class="parfume-card__action-text"><?php _e('View Details', 'parfume-reviews'); ?></span>
                    </a>
                <?php endif; ?>
                
            </div>
        <?php endif; ?>
        
    </div>
    
</article>

<style>
/* Base Card Styles */
.parfume-card {
    background: #fff;
    border: 1px solid #e0e4e7;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.parfume-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: #4a90e2;
}

/* Image Styles */
.parfume-card__image {
    position: relative;
    overflow: hidden;
    background: #f8fafc;
}

.parfume-card--grid .parfume-card__image {
    aspect-ratio: 4/3;
}

.parfume-card--list .parfume-card__image {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
}

.parfume-card--featured .parfume-card__image {
    aspect-ratio: 3/4;
}

.parfume-card--minimal .parfume-card__image {
    aspect-ratio: 1/1;
    width: 60px;
    height: 60px;
}

.parfume-card__image-link {
    display: block;
    width: 100%;
    height: 100%;
}

.parfume-card__image-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.parfume-card:hover .parfume-card__image-img {
    transform: scale(1.05);
}

.parfume-card__image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    background: #f1f5f9;
}

.parfume-card__image-icon {
    width: 32px;
    height: 32px;
    margin-bottom: 8px;
}

.parfume-card__image-text {
    font-size: 12px;
    font-weight: 500;
}

/* Badge */
.parfume-card__badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(74, 144, 226, 0.95);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    backdrop-filter: blur(10px);
}

.parfume-card__badge-star {
    color: #fbbf24;
}

/* Content */
.parfume-card__content {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.parfume-card--list {
    flex-direction: row;
}

.parfume-card--list .parfume-card__content {
    padding: 16px 20px;
}

.parfume-card--minimal .parfume-card__content {
    padding: 12px;
}

/* Brand */
.parfume-card__brand {
    color: #6b7280;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
    letter-spacing: 0.025em;
}

.parfume-card__year {
    opacity: 0.7;
}

/* Title */
.parfume-card__title {
    margin: 0 0 12px;
    font-size: 16px;
    line-height: 1.4;
    font-weight: 600;
}

.parfume-card--featured .parfume-card__title {
    font-size: 18px;
}

.parfume-card--minimal .parfume-card__title {
    font-size: 14px;
    margin-bottom: 8px;
}

.parfume-card__title-link {
    color: #1f2937;
    text-decoration: none;
    transition: color 0.2s ease;
}

.parfume-card__title-link:hover {
    color: #4a90e2;
}

/* Excerpt */
.parfume-card__excerpt {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 16px;
    flex: 1;
}

/* Rating */
.parfume-card__rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.parfume-card__rating-stars {
    color: #fbbf24;
    font-size: 16px;
}

.parfume-card__rating-number {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

/* Notes */
.parfume-card__notes {
    margin-bottom: 16px;
}

.parfume-card__notes-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: block;
    margin-bottom: 8px;
}

.parfume-card__note-tag {
    display: inline-block;
    background: #f0f9ff;
    color: #0369a1;
    font-size: 11px;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 12px;
    margin-right: 6px;
    margin-bottom: 4px;
    border: 1px solid #bae6fd;
}

/* Meta Information */
.parfume-card__meta {
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.parfume-card__price {
    margin-bottom: 8px;
}

.parfume-card__price-label {
    font-size: 12px;
    color: #6b7280;
    margin-right: 4px;
}

.parfume-card__price-value {
    font-size: 16px;
    font-weight: 700;
    color: #059669;
}

.parfume-card__availability,
.parfume-card__shipping {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    margin-bottom: 4px;
}

.parfume-card__availability-icon,
.parfume-card__shipping-icon {
    width: 14px;
    height: 14px;
}

.parfume-card__availability--available {
    color: #059669;
}

.parfume-card__availability--unavailable {
    color: #dc2626;
}

.parfume-card__shipping {
    color: #6b7280;
}

/* Actions */
.parfume-card__actions {
    display: flex;
    gap: 8px;
    margin-top: auto;
}

.parfume-card__action {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #475569;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.parfume-card__action:hover {
    background: #4a90e2;
    border-color: #4a90e2;
    color: white;
    transform: translateY(-1px);
}

.parfume-card__action-icon {
    width: 14px;
    height: 14px;
}

.parfume-card__action-text {
    white-space: nowrap;
}

/* Layout Variations */
.parfume-card--list {
    flex-direction: row;
    align-items: stretch;
}

.parfume-card--minimal {
    padding: 0;
    flex-direction: row;
    align-items: center;
    gap: 12px;
}

.parfume-card--minimal .parfume-card__image {
    margin: 12px;
}

.parfume-card--featured {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.parfume-card--featured .parfume-card__brand,
.parfume-card--featured .parfume-card__title-link,
.parfume-card--featured .parfume-card__excerpt {
    color: white;
}

.parfume-card--featured .parfume-card__action {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.parfume-card--featured .parfume-card__action:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .parfume-card__content {
        padding: 16px;
    }
    
    .parfume-card--list {
        flex-direction: column;
    }
    
    .parfume-card--list .parfume-card__image {
        width: 100%;
        height: 200px;
    }
    
    .parfume-card__actions {
        flex-direction: column;
    }
    
    .parfume-card__action {
        justify-content: flex-start;
    }
}

@media (max-width: 480px) {
    .parfume-card--minimal .parfume-card__actions {
        display: none;
    }
    
    .parfume-card--minimal .parfume-card__rating {
        margin-bottom: 0;
    }
}

/* Animation States */
.parfume-card--loading {
    opacity: 0.6;
    pointer-events: none;
}

.parfume-card--in-comparison {
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
}

.parfume-card--in-wishlist .parfume-card__action--wishlist {
    background: #dc2626;
    border-color: #dc2626;
    color: white;
}

/* Grid Layouts */
.parfume-grid--2-cols .parfume-card {
    min-height: 400px;
}

.parfume-grid--3-cols .parfume-card {
    min-height: 380px;
}

.parfume-grid--4-cols .parfume-card {
    min-height: 360px;
}
</style>