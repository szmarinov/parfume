<?php
/**
 * Parfume Card Component - IMPROVED
 * 
 * Modern card design for displaying parfumes in grid
 * 
 * @package ParfumeReviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get post ID
$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();

// Get parfume data
$rating = get_post_meta($post_id, '_parfume_rating', true);
$release_year = get_post_meta($post_id, '_parfume_release_year', true);
$stores = get_post_meta($post_id, '_parfume_stores', true);

// Get lowest price from stores
$lowest_price = null;
if (!empty($stores) && is_array($stores)) {
    $prices = array_filter(array_column($stores, 'price'), function($price) {
        return !empty($price) && is_numeric($price);
    });
    if (!empty($prices)) {
        $lowest_price = min($prices);
    }
}

// Get taxonomies
$brands = wp_get_post_terms($post_id, 'marki');
$genders = wp_get_post_terms($post_id, 'gender');
$types = wp_get_post_terms($post_id, 'aroma_type');
?>

<article id="parfume-<?php echo $post_id; ?>" class="parfume-card">
    
    <!-- Featured Badge -->
    <?php if ($rating && floatval($rating) >= 9.0) : ?>
        <div class="parfume-card-badge">
            <span class="dashicons dashicons-star-filled"></span>
            <?php _e('Топ Оценка', 'parfume-reviews'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Comparison Checkbox -->
    <div class="parfume-card-compare">
        <input type="checkbox" 
               class="compare-checkbox" 
               data-parfume-id="<?php echo $post_id; ?>"
               id="compare-<?php echo $post_id; ?>">
        <label for="compare-<?php echo $post_id; ?>" title="<?php _e('Добави за сравнение', 'parfume-reviews'); ?>">
            <span class="dashicons dashicons-plus"></span>
        </label>
    </div>
    
    <!-- Image -->
    <a href="<?php echo get_permalink($post_id); ?>" class="parfume-card-image">
        <?php
        if (has_post_thumbnail($post_id)) :
            echo get_the_post_thumbnail($post_id, 'medium', [
                'class' => 'card-thumbnail',
                'loading' => 'lazy'
            ]);
        else :
            ?>
            <div class="card-placeholder">
                <span class="dashicons dashicons-admin-customizer"></span>
            </div>
            <?php
        endif;
        ?>
        
        <!-- Quick View Overlay -->
        <div class="card-overlay">
            <span class="quick-view-btn">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Бърз преглед', 'parfume-reviews'); ?>
            </span>
        </div>
    </a>
    
    <!-- Content -->
    <div class="parfume-card-content">
        
        <!-- Brand -->
        <?php if (!empty($brands)) : ?>
            <div class="parfume-card-brand">
                <a href="<?php echo get_term_link($brands[0]); ?>">
                    <?php echo esc_html($brands[0]->name); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Title -->
        <h3 class="parfume-card-title">
            <a href="<?php echo get_permalink($post_id); ?>">
                <?php echo get_the_title($post_id); ?>
            </a>
        </h3>
        
        <!-- Meta Info -->
        <div class="parfume-card-meta">
            
            <!-- Rating -->
            <?php if ($rating && floatval($rating) > 0) : ?>
                <div class="parfume-card-rating">
                    <div class="rating-stars">
                        <?php
                        $stars = floatval($rating) / 2; // Convert 0-10 to 0-5
                        $full_stars = floor($stars);
                        $half_star = ($stars - $full_stars) >= 0.5;
                        
                        for ($i = 0; $i < $full_stars; $i++) {
                            echo '<span class="star star-full">★</span>';
                        }
                        if ($half_star) {
                            echo '<span class="star star-half">★</span>';
                        }
                        ?>
                    </div>
                    <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Gender -->
            <?php if (!empty($genders)) : ?>
                <span class="parfume-card-gender">
                    <?php echo esc_html($genders[0]->name); ?>
                </span>
            <?php endif; ?>
            
            <!-- Year -->
            <?php if ($release_year) : ?>
                <span class="parfume-card-year">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html($release_year); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Excerpt -->
        <?php if (has_excerpt($post_id)) : ?>
            <div class="parfume-card-excerpt">
                <?php echo wp_trim_words(get_the_excerpt($post_id), 15, '...'); ?>
            </div>
        <?php endif; ?>
        
        <!-- Type Tags -->
        <?php if (!empty($types)) : ?>
            <div class="parfume-card-tags">
                <?php foreach (array_slice($types, 0, 2) as $type) : ?>
                    <span class="type-tag"><?php echo esc_html($type->name); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="parfume-card-footer">
            
            <!-- Price -->
            <div class="parfume-card-price">
                <?php if ($lowest_price && $lowest_price > 0) : ?>
                    <span class="price-label"><?php _e('от', 'parfume-reviews'); ?></span>
                    <span class="price-value"><?php echo number_format($lowest_price, 2); ?></span>
                    <span class="price-currency"><?php _e('лв', 'parfume-reviews'); ?></span>
                <?php else : ?>
                    <span class="price-na"><?php _e('Виж магазини', 'parfume-reviews'); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Action Button -->
            <a href="<?php echo get_permalink($post_id); ?>" 
               class="parfume-card-link"
               title="<?php echo esc_attr(get_the_title($post_id)); ?>">
                <?php _e('Детайли', 'parfume-reviews'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
        
    </div>
    
</article>