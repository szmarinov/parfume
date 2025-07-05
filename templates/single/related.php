<?php
/**
 * Single Parfume Related Products
 * 
 * @package ParfumeReviews
 * @subpackage Templates\Single
 */

namespace ParfumeReviews\Templates\Single;

if (!defined('ABSPATH')) {
    exit;
}

class Related {
    
    /**
     * Render related perfumes section
     */
    public static function render() {
        global $post;
        
        if ($post->post_type !== 'parfume') {
            return;
        }
        
        ob_start();
        ?>
        <div class="parfume-related-section">
            <?php
            self::render_brand_perfumes($post->ID);
            self::render_similar_notes_perfumes($post->ID);
            self::render_recently_viewed();
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render perfumes from the same brand
     */
    private static function render_brand_perfumes($post_id) {
        $brands = wp_get_post_terms($post_id, 'marki');
        
        if (empty($brands) || is_wp_error($brands)) {
            return;
        }
        
        $brand = $brands[0];
        $brand_perfumes = self::get_brand_perfumes($post_id, $brand->term_id);
        
        if (empty($brand_perfumes)) {
            return;
        }
        ?>
        <div class="related-section brand-perfumes">
            <h3><?php printf(__('More from %s', 'parfume-reviews'), esc_html($brand->name)); ?></h3>
            
            <div class="related-grid">
                <?php foreach ($brand_perfumes as $perfume): ?>
                    <div class="related-item">
                        <a href="<?php echo get_permalink($perfume->ID); ?>" class="related-link">
                            <?php if (has_post_thumbnail($perfume->ID)): ?>
                                <div class="related-image">
                                    <?php echo get_the_post_thumbnail($perfume->ID, 'medium'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="related-content">
                                <h4 class="related-title"><?php echo esc_html($perfume->post_title); ?></h4>
                                
                                <?php
                                $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
                                if (!empty($rating)):
                                ?>
                                    <div class="related-rating">
                                        <?php echo \ParfumeReviews\Utils\Helpers::get_rating_stars($rating); ?>
                                        <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php
                                $release_year = get_post_meta($perfume->ID, '_parfume_release_year', true);
                                if (!empty($release_year)):
                                ?>
                                    <div class="related-year"><?php echo esc_html($release_year); ?></div>
                                <?php endif; ?>
                                
                                <?php
                                $lowest_price = \ParfumeReviews\Utils\Helpers::get_lowest_price($perfume->ID);
                                if ($lowest_price):
                                ?>
                                    <div class="related-price">
                                        <?php _e('from', 'parfume-reviews'); ?> <?php echo esc_html($lowest_price['price']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="view-all-brand">
                <a href="<?php echo get_term_link($brand); ?>" class="view-all-link">
                    <?php printf(__('View all %s perfumes', 'parfume-reviews'), esc_html($brand->name)); ?>
                    <span class="arrow">→</span>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render perfumes with similar notes
     */
    private static function render_similar_notes_perfumes($post_id) {
        $similar_perfumes = self::get_similar_notes_perfumes($post_id);
        
        if (empty($similar_perfumes)) {
            return;
        }
        ?>
        <div class="related-section similar-notes">
            <h3><?php _e('Similar Fragrances', 'parfume-reviews'); ?></h3>
            
            <div class="related-grid">
                <?php foreach ($similar_perfumes as $perfume): ?>
                    <div class="related-item">
                        <a href="<?php echo get_permalink($perfume->ID); ?>" class="related-link">
                            <?php if (has_post_thumbnail($perfume->ID)): ?>
                                <div class="related-image">
                                    <?php echo get_the_post_thumbnail($perfume->ID, 'medium'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="related-content">
                                <h4 class="related-title"><?php echo esc_html($perfume->post_title); ?></h4>
                                
                                <?php
                                $brands = wp_get_post_terms($perfume->ID, 'marki', array('fields' => 'names'));
                                if (!empty($brands) && !is_wp_error($brands)):
                                ?>
                                    <div class="related-brand"><?php echo esc_html($brands[0]); ?></div>
                                <?php endif; ?>
                                
                                <?php
                                $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
                                if (!empty($rating)):
                                ?>
                                    <div class="related-rating">
                                        <?php echo \ParfumeReviews\Utils\Helpers::get_rating_stars($rating); ?>
                                        <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php
                                // Show shared notes
                                $shared_notes = self::get_shared_notes($post_id, $perfume->ID);
                                if (!empty($shared_notes)):
                                ?>
                                    <div class="shared-notes">
                                        <span class="shared-label"><?php _e('Shared notes:', 'parfume-reviews'); ?></span>
                                        <?php foreach (array_slice($shared_notes, 0, 3) as $note): ?>
                                            <span class="note-tag"><?php echo esc_html($note->name); ?></span>
                                        <?php endforeach; ?>
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
     * Render recently viewed perfumes
     */
    private static function render_recently_viewed() {
        ?>
        <div class="related-section recently-viewed" id="recently-viewed" style="display: none;">
            <h3><?php _e('Recently Viewed', 'parfume-reviews'); ?></h3>
            <div class="related-grid" id="recently-viewed-grid">
                <!-- Content populated by JavaScript -->
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get recently viewed from localStorage
            var viewed = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
            var currentId = <?php echo get_the_ID(); ?>;
            
            // Remove current perfume and limit to 4
            viewed = viewed.filter(id => id != currentId).slice(0, 4);
            
            if (viewed.length > 0) {
                document.getElementById('recently-viewed').style.display = 'block';
                
                // Make AJAX call to get perfume data
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_recently_viewed_perfumes',
                        perfume_ids: viewed.join(','),
                        nonce: '<?php echo wp_create_nonce('get_recently_viewed'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('recently-viewed-grid').innerHTML = data.data.html;
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get perfumes from the same brand
     */
    private static function get_brand_perfumes($post_id, $brand_id, $limit = 4) {
        $cache_key = 'brand_perfumes_' . $post_id . '_' . $brand_id;
        $perfumes = \ParfumeReviews\Utils\Cache::get($cache_key);
        
        if ($perfumes === false) {
            $args = array(
                'post_type' => 'parfume',
                'posts_per_page' => $limit,
                'post__not_in' => array($post_id),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'marki',
                        'field' => 'term_id',
                        'terms' => $brand_id,
                    ),
                ),
                'orderby' => 'date',
                'order' => 'DESC',
            );
            
            $query = new \WP_Query($args);
            $perfumes = $query->posts;
            wp_reset_postdata();
            
            // Cache for 1 hour
            \ParfumeReviews\Utils\Cache::set($cache_key, $perfumes, 3600);
        }
        
        return $perfumes;
    }
    
    /**
     * Get perfumes with similar notes
     */
    private static function get_similar_notes_perfumes($post_id, $limit = 4) {
        $cache_key = 'similar_notes_perfumes_' . $post_id;
        $perfumes = \ParfumeReviews\Utils\Cache::get($cache_key);
        
        if ($perfumes === false) {
            $notes = wp_get_post_terms($post_id, 'notes', array('fields' => 'ids'));
            
            if (empty($notes) || is_wp_error($notes)) {
                return array();
            }
            
            $args = array(
                'post_type' => 'parfume',
                'posts_per_page' => $limit,
                'post__not_in' => array($post_id),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'notes',
                        'field' => 'term_id',
                        'terms' => array_slice($notes, 0, 8), // Use top 8 notes
                        'operator' => 'IN',
                    ),
                ),
                'orderby' => 'rand',
            );
            
            $query = new \WP_Query($args);
            $perfumes = $query->posts;
            wp_reset_postdata();
            
            // Cache for 30 minutes
            \ParfumeReviews\Utils\Cache::set($cache_key, $perfumes, 1800);
        }
        
        return $perfumes;
    }
    
    /**
     * Get shared notes between two perfumes
     */
    private static function get_shared_notes($post_id1, $post_id2) {
        $notes1 = wp_get_post_terms($post_id1, 'notes', array('fields' => 'ids'));
        $notes2 = wp_get_post_terms($post_id2, 'notes', array('fields' => 'ids'));
        
        if (is_wp_error($notes1) || is_wp_error($notes2)) {
            return array();
        }
        
        $shared_note_ids = array_intersect($notes1, $notes2);
        
        if (empty($shared_note_ids)) {
            return array();
        }
        
        return get_terms(array(
            'taxonomy' => 'notes',
            'include' => $shared_note_ids,
        ));
    }
}