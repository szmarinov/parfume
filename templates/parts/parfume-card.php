<?php
/**
 * Parfume Card Component
 * 
 * Reusable card component for displaying parfumes in grids
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 * 
 * @var int $post_id Post ID (passed as argument)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default to current post if not provided
if (!isset($post_id)) {
    $post_id = get_the_ID();
}

// Get post object
$post = get_post($post_id);
if (!$post) {
    return;
}

// Get meta data
$rating = get_post_meta($post_id, '_parfume_rating', true);
$stores = get_post_meta($post_id, '_parfume_stores', true);

// Calculate cheapest price
$cheapest_price = null;
if (!empty($stores) && is_array($stores)) {
    $prices = array_filter(array_column($stores, 'price'));
    if (!empty($prices)) {
        $cheapest_price = min($prices);
    }
}

// Get taxonomies
$brands = wp_get_post_terms($post_id, 'marki');
$genders = wp_get_post_terms($post_id, 'gender');
$notes = wp_get_post_terms($post_id, 'notes', ['number' => 3]);

?>

<article id="post-<?php echo esc_attr($post_id); ?>" class="parfume-card">
    
    <div class="card-inner">
        
        <!-- Card Image -->
        <div class="card-image">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php if (has_post_thumbnail($post_id)) : ?>
                    <?php echo get_the_post_thumbnail($post_id, 'medium', ['alt' => get_the_title($post_id)]); ?>
                <?php else : ?>
                    <img src="<?php echo esc_url(PARFUME_REVIEWS_URL . 'assets/images/placeholder.jpg'); ?>" 
                         alt="<?php echo esc_attr(get_the_title($post_id)); ?>">
                <?php endif; ?>
            </a>
            
            <!-- Quick Actions -->
            <div class="card-actions">
                
                <!-- Add to Comparison -->
                <?php if (function_exists('parfume_reviews_comparison_button')) : ?>
                    <button type="button" 
                            class="quick-action comparison-toggle" 
                            data-post-id="<?php echo esc_attr($post_id); ?>"
                            title="<?php _e('Добави за сравнение', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-forms"></span>
                    </button>
                <?php endif; ?>
                
                <!-- Wishlist (if implemented) -->
                <button type="button" 
                        class="quick-action wishlist-toggle" 
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        title="<?php _e('Добави в любими', 'parfume-reviews'); ?>">
                    <span class="dashicons dashicons-heart"></span>
                </button>
                
            </div>
            
            <!-- Price Badge -->
            <?php if ($cheapest_price) : ?>
                <div class="price-badge">
                    <span class="price-from"><?php _e('от', 'parfume-reviews'); ?></span>
                    <span class="price-value"><?php echo esc_html(number_format($cheapest_price, 2)); ?></span>
                    <span class="price-currency">лв</span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Card Content -->
        <div class="card-content">
            
            <!-- Brand -->
            <?php if (!empty($brands)) : ?>
                <div class="card-brand">
                    <a href="<?php echo esc_url(get_term_link($brands[0])); ?>">
                        <?php echo esc_html($brands[0]->name); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Title -->
            <h3 class="card-title">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <?php echo esc_html(get_the_title($post_id)); ?>
                </a>
            </h3>
            
            <!-- Gender -->
            <?php if (!empty($genders)) : ?>
                <div class="card-gender">
                    <?php foreach ($genders as $gender) : ?>
                        <span class="gender-badge"><?php echo esc_html($gender->name); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Notes -->
            <?php if (!empty($notes)) : ?>
                <div class="card-notes">
                    <?php foreach ($notes as $note) : ?>
                        <span class="note-badge"><?php echo esc_html($note->name); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Rating -->
            <?php if ($rating) : ?>
                <div class="card-rating">
                    <div class="rating-stars">
                        <?php
                        if (function_exists('parfume_reviews_get_rating_stars')) {
                            echo parfume_reviews_get_rating_stars($rating);
                        } else {
                            // Fallback star display
                            $stars_count = round($rating / 2); // Convert 0-10 to 0-5
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $stars_count) {
                                    echo '<span class="star filled">★</span>';
                                } else {
                                    echo '<span class="star">☆</span>';
                                }
                            }
                        }
                        ?>
                    </div>
                    <span class="rating-value"><?php echo esc_html($rating); ?>/10</span>
                </div>
            <?php endif; ?>
            
            <!-- Excerpt -->
            <div class="card-excerpt">
                <?php echo wp_trim_words(get_the_excerpt($post_id), 15, '...'); ?>
            </div>
            
            <!-- View Button -->
            <div class="card-footer">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="card-button">
                    <?php _e('Виж повече', 'parfume-reviews'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
            
        </div>
        
    </div>
    
</article>