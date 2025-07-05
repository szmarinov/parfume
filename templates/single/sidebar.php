<?php
/**
 * Single Parfume Sidebar
 * 
 * @package ParfumeReviews
 * @subpackage Templates\Single
 */

namespace ParfumeReviews\Templates\Single;

if (!defined('ABSPATH')) {
    exit;
}

class Sidebar {
    
    /**
     * Render single parfume sidebar
     */
    public static function render() {
        global $post;
        
        if ($post->post_type !== 'parfume') {
            return;
        }
        
        ob_start();
        ?>
        <div class="parfume-single-sidebar">
            <?php
            self::render_stores_section($post->ID);
            self::render_quick_info_section($post->ID);
            self::render_similar_section($post->ID);
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render stores section
     */
    private static function render_stores_section($post_id) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (empty($stores) || !is_array($stores)) {
            return;
        }
        ?>
        <div class="sidebar-section stores-section">
            <h3><?php _e('Where to Buy', 'parfume-reviews'); ?></h3>
            
            <div class="stores-list">
                <?php foreach ($stores as $store): ?>
                    <?php if (empty($store['name'])) continue; ?>
                    
                    <div class="store-item">
                        <div class="store-header">
                            <?php if (!empty($store['logo'])): ?>
                                <div class="store-logo">
                                    <img src="<?php echo esc_url($store['logo']); ?>" 
                                         alt="<?php echo esc_attr($store['name']); ?>"
                                         loading="lazy">
                                </div>
                            <?php endif; ?>
                            
                            <div class="store-name">
                                <strong><?php echo esc_html($store['name']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="store-details">
                            <?php if (!empty($store['price'])): ?>
                                <div class="store-price">
                                    <span class="price-label"><?php _e('Price:', 'parfume-reviews'); ?></span>
                                    <span class="price-value"><?php echo esc_html($store['price']); ?></span>
                                    <?php if (!empty($store['size'])): ?>
                                        <span class="price-size">(<?php echo esc_html($store['size']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($store['availability'])): ?>
                                <div class="store-availability">
                                    <span class="availability-icon">
                                        <?php if (stripos($store['availability'], 'наличен') !== false): ?>
                                            <span class="available">✓</span>
                                        <?php else: ?>
                                            <span class="unavailable">✗</span>
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo esc_html($store['availability']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($store['shipping_cost'])): ?>
                                <div class="store-shipping">
                                    <span class="shipping-icon">🚚</span>
                                    <span><?php echo esc_html($store['shipping_cost']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($store['promo_code'])): ?>
                                <div class="store-promo">
                                    <span class="promo-label">
                                        <?php echo !empty($store['promo_text']) ? esc_html($store['promo_text']) : __('Promo code:', 'parfume-reviews'); ?>
                                    </span>
                                    <code><?php echo esc_html($store['promo_code']); ?></code>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="store-actions">
                            <?php if (!empty($store['affiliate_url']) || !empty($store['url'])): ?>
                                <a href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" 
                                   target="<?php echo esc_attr($store['affiliate_target'] ?: '_blank'); ?>"
                                   rel="<?php echo esc_attr($store['affiliate_rel'] ?: 'nofollow'); ?>"
                                   class="store-link">
                                    <?php echo !empty($store['affiliate_anchor']) ? esc_html($store['affiliate_anchor']) : __('Visit Store', 'parfume-reviews'); ?>
                                    <span class="external-icon">↗</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render quick info section
     */
    private static function render_quick_info_section($post_id) {
        $meta_data = self::get_quick_info_data($post_id);
        
        if (empty(array_filter($meta_data))) {
            return;
        }
        ?>
        <div class="sidebar-section quick-info-section">
            <h3><?php _e('Quick Info', 'parfume-reviews'); ?></h3>
            
            <div class="quick-info-list">
                <?php if (!empty($meta_data['rating'])): ?>
                    <div class="info-item">
                        <span class="info-label"><?php _e('Rating:', 'parfume-reviews'); ?></span>
                        <span class="info-value">
                            <?php echo \ParfumeReviews\Utils\Helpers::get_rating_stars($meta_data['rating']); ?>
                            <span class="rating-number"><?php echo number_format(floatval($meta_data['rating']), 1); ?>/5</span>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta_data['gender'])): ?>
                    <div class="info-item">
                        <span class="info-label"><?php _e('Gender:', 'parfume-reviews'); ?></span>
                        <span class="info-value"><?php echo esc_html($meta_data['gender']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta_data['release_year'])): ?>
                    <div class="info-item">
                        <span class="info-label"><?php _e('Release Year:', 'parfume-reviews'); ?></span>
                        <span class="info-value"><?php echo esc_html($meta_data['release_year']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta_data['longevity'])): ?>
                    <div class="info-item">
                        <span class="info-label"><?php _e('Longevity:', 'parfume-reviews'); ?></span>
                        <span class="info-value"><?php echo esc_html($meta_data['longevity']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta_data['sillage'])): ?>
                    <div class="info-item">
                        <span class="info-label"><?php _e('Sillage:', 'parfume-reviews'); ?></span>
                        <span class="info-value"><?php echo esc_html($meta_data['sillage']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta_data['bottle_size'])): ?>
                    <div class="info-item">
                        <span class="info-label"><?php _e('Bottle Size:', 'parfume-reviews'); ?></span>
                        <span class="info-value"><?php echo esc_html($meta_data['bottle_size']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render similar perfumes section
     */
    private static function render_similar_section($post_id) {
        $similar_perfumes = self::get_similar_perfumes($post_id);
        
        if (empty($similar_perfumes)) {
            return;
        }
        ?>
        <div class="sidebar-section similar-section">
            <h3><?php _e('You Might Also Like', 'parfume-reviews'); ?></h3>
            
            <div class="similar-perfumes-list">
                <?php foreach ($similar_perfumes as $perfume): ?>
                    <div class="similar-perfume-item">
                        <a href="<?php echo get_permalink($perfume->ID); ?>" class="similar-perfume-link">
                            <?php if (has_post_thumbnail($perfume->ID)): ?>
                                <div class="similar-perfume-image">
                                    <?php echo get_the_post_thumbnail($perfume->ID, 'thumbnail'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="similar-perfume-info">
                                <h4 class="similar-perfume-title"><?php echo esc_html($perfume->post_title); ?></h4>
                                
                                <?php
                                $brands = wp_get_post_terms($perfume->ID, 'marki', array('fields' => 'names'));
                                if (!empty($brands) && !is_wp_error($brands)):
                                ?>
                                    <div class="similar-perfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                <?php endif; ?>
                                
                                <?php
                                $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
                                if (!empty($rating)):
                                ?>
                                    <div class="similar-perfume-rating">
                                        <?php echo \ParfumeReviews\Utils\Helpers::get_rating_stars($rating); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get quick info data
     */
    private static function get_quick_info_data($post_id) {
        return array(
            'rating' => get_post_meta($post_id, '_parfume_rating', true),
            'gender' => get_post_meta($post_id, '_parfume_gender', true),
            'release_year' => get_post_meta($post_id, '_parfume_release_year', true),
            'longevity' => get_post_meta($post_id, '_parfume_longevity', true),
            'sillage' => get_post_meta($post_id, '_parfume_sillage', true),
            'bottle_size' => get_post_meta($post_id, '_parfume_bottle_size', true),
        );
    }
    
    /**
     * Get similar perfumes
     */
    private static function get_similar_perfumes($post_id, $limit = 3) {
        // Try to get from cache first
        $cache_key = 'similar_perfumes_' . $post_id;
        $similar = \ParfumeReviews\Utils\Cache::get($cache_key);
        
        if ($similar === false) {
            // Get current perfume taxonomies
            $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'ids'));
            $notes = wp_get_post_terms($post_id, 'notes', array('fields' => 'ids'));
            
            if (is_wp_error($brands)) $brands = array();
            if (is_wp_error($notes)) $notes = array();
            
            $tax_query = array('relation' => 'OR');
            
            if (!empty($brands)) {
                $tax_query[] = array(
                    'taxonomy' => 'marki',
                    'field' => 'term_id',
                    'terms' => $brands,
                );
            }
            
            if (!empty($notes)) {
                $tax_query[] = array(
                    'taxonomy' => 'notes',
                    'field' => 'term_id',
                    'terms' => array_slice($notes, 0, 5), // Limit to top 5 notes
                );
            }
            
            if (count($tax_query) > 1) {
                $args = array(
                    'post_type' => 'parfume',
                    'posts_per_page' => $limit,
                    'post__not_in' => array($post_id),
                    'tax_query' => $tax_query,
                    'orderby' => 'rand',
                );
                
                $query = new \WP_Query($args);
                $similar = $query->posts;
                wp_reset_postdata();
                
                // Cache for 30 minutes
                \ParfumeReviews\Utils\Cache::set($cache_key, $similar, 1800);
            } else {
                $similar = array();
            }
        }
        
        return $similar;
    }
}