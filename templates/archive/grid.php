<?php
/**
 * Archive Grid Layout
 * 
 * @package ParfumeReviews
 * @subpackage Templates\Archive
 */

namespace ParfumeReviews\Templates\Archive;

if (!defined('ABSPATH')) {
    exit;
}

class Grid {
    
    /**
     * Render archive grid
     */
    public static function render($query = null, $columns = 3) {
        global $wp_query;
        
        if ($query === null) {
            $query = $wp_query;
        }
        
        if (!$query->have_posts()) {
            self::render_no_results();
            return;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $card_settings = self::get_card_settings($settings);
        
        ob_start();
        ?>
        <div class="parfume-archive-grid" data-columns="<?php echo esc_attr($columns); ?>">
            <?php while ($query->have_posts()): $query->the_post(); ?>
                <?php self::render_perfume_card(get_the_ID(), $card_settings); ?>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Render individual perfume card
     */
    private static function render_perfume_card($post_id, $card_settings) {
        $meta_data = self::get_card_meta_data($post_id);
        $taxonomy_data = self::get_card_taxonomy_data($post_id);
        ?>
        <article class="parfume-card" data-post-id="<?php echo esc_attr($post_id); ?>">
            <?php if ($card_settings['show_image']): ?>
                <div class="parfume-card-image">
                    <a href="<?php echo get_permalink($post_id); ?>">
                        <?php if (has_post_thumbnail($post_id)): ?>
                            <?php echo get_the_post_thumbnail($post_id, 'medium', array('loading' => 'lazy')); ?>
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <span class="placeholder-icon">🌸</span>
                                <span class="placeholder-text"><?php _e('No Image', 'parfume-reviews'); ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                    
                    <div class="card-overlay">
                        <div class="card-actions">
                            <button class="action-btn compare-btn" data-post-id="<?php echo esc_attr($post_id); ?>" title="<?php _e('Compare', 'parfume-reviews'); ?>">
                                <span class="icon">⚖️</span>
                            </button>
                            <button class="action-btn wishlist-btn" data-post-id="<?php echo esc_attr($post_id); ?>" title="<?php _e('Add to Wishlist', 'parfume-reviews'); ?>">
                                <span class="icon">❤️</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="parfume-card-content">
                <?php if ($card_settings['show_brand'] && !empty($taxonomy_data['brands'])): ?>
                    <div class="parfume-brand">
                        <a href="<?php echo get_term_link($taxonomy_data['brands'][0]); ?>">
                            <?php echo esc_html($taxonomy_data['brands'][0]->name); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($card_settings['show_name']): ?>
                    <h3 class="parfume-title">
                        <a href="<?php echo get_permalink($post_id); ?>">
                            <?php echo get_the_title($post_id); ?>
                        </a>
                    </h3>
                <?php endif; ?>
                
                <?php if (!empty($meta_data['rating'])): ?>
                    <div class="parfume-rating">
                        <div class="rating-stars">
                            <?php echo \ParfumeReviews\Utils\Helpers::get_rating_stars($meta_data['rating']); ?>
                        </div>
                        <span class="rating-number"><?php echo number_format(floatval($meta_data['rating']), 1); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta_data['release_year'])): ?>
                    <div class="parfume-year"><?php echo esc_html($meta_data['release_year']); ?></div>
                <?php endif; ?>
                
                <?php if ($card_settings['show_price']): ?>
                    <?php 
                    $lowest_price = \ParfumeReviews\Utils\Helpers::get_lowest_price($post_id);
                    if ($lowest_price):
                    ?>
                        <div class="parfume-price">
                            <span class="price-label"><?php _e('from', 'parfume-reviews'); ?></span>
                            <span class="price-value"><?php echo esc_html($lowest_price['price']); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="parfume-meta">
                    <?php if ($card_settings['show_availability']): ?>
                        <div class="availability">
                            <?php if (\ParfumeReviews\Utils\Helpers::is_available($post_id)): ?>
                                <span class="available">
                                    <span class="icon">✓</span>
                                    <?php _e('Available', 'parfume-reviews'); ?>
                                </span>
                            <?php else: ?>
                                <span class="unavailable">
                                    <span class="icon">✗</span>
                                    <?php _e('Out of Stock', 'parfume-reviews'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($card_settings['show_shipping']): ?>
                        <?php 
                        $shipping = \ParfumeReviews\Utils\Helpers::get_cheapest_shipping($post_id);
                        if ($shipping):
                        ?>
                            <div class="shipping">
                                <span class="icon">🚚</span>
                                <span><?php echo esc_html($shipping); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($taxonomy_data['notes'])): ?>
                    <div class="parfume-notes">
                        <?php foreach (array_slice($taxonomy_data['notes'], 0, 3) as $note): ?>
                            <span class="note-tag">
                                <a href="<?php echo get_term_link($note); ?>">
                                    <?php echo esc_html($note->name); ?>
                                </a>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($taxonomy_data['notes']) > 3): ?>
                            <span class="more-notes">+<?php echo count($taxonomy_data['notes']) - 3; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }
    
    /**
     * Render no results message
     */
    private static function render_no_results() {
        ?>
        <div class="no-perfumes-found">
            <div class="no-results-icon">🔍</div>
            <h3><?php _e('No perfumes found', 'parfume-reviews'); ?></h3>
            <p><?php _e('Try adjusting your filters or search terms.', 'parfume-reviews'); ?></p>
            
            <div class="suggested-actions">
                <a href="<?php echo remove_query_arg(array('s', 'gender', 'marki', 'notes', 'perfumer', 'aroma_type', 'season', 'intensity')); ?>" 
                   class="clear-filters-btn">
                    <?php _e('Clear all filters', 'parfume-reviews'); ?>
                </a>
                
                <a href="<?php echo get_post_type_archive_link('parfume'); ?>" 
                   class="view-all-btn">
                    <?php _e('View all perfumes', 'parfume-reviews'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get card settings from options
     */
    private static function get_card_settings($settings) {
        return array(
            'show_image' => isset($settings['card_show_image']) ? $settings['card_show_image'] : 1,
            'show_brand' => isset($settings['card_show_brand']) ? $settings['card_show_brand'] : 1,
            'show_name' => isset($settings['card_show_name']) ? $settings['card_show_name'] : 1,
            'show_price' => isset($settings['card_show_price']) ? $settings['card_show_price'] : 1,
            'show_availability' => isset($settings['card_show_availability']) ? $settings['card_show_availability'] : 1,
            'show_shipping' => isset($settings['card_show_shipping']) ? $settings['card_show_shipping'] : 1,
        );
    }
    
    /**
     * Get meta data for card
     */
    private static function get_card_meta_data($post_id) {
        $stats = \ParfumeReviews\Utils\Cache::get_perfume_stats($post_id);
        
        return array(
            'rating' => $stats['rating'],
            'release_year' => get_post_meta($post_id, '_parfume_release_year', true),
            'lowest_price' => $stats['lowest_price'],
            'availability' => $stats['availability'],
            'shipping' => $stats['shipping'],
        );
    }
    
    /**
     * Get taxonomy data for card
     */
    private static function get_card_taxonomy_data($post_id) {
        return array(
            'brands' => wp_get_post_terms($post_id, 'marki'),
            'notes' => wp_get_post_terms($post_id, 'notes'),
        );
    }
}