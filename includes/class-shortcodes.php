<?php
namespace Parfume_Reviews;

class Shortcodes {
    public function __construct() {
        add_shortcode('parfume_rating', array($this, 'rating_shortcode'));
        add_shortcode('parfume_details', array($this, 'details_shortcode'));
        add_shortcode('parfume_stores', array($this, 'stores_shortcode'));
        add_shortcode('parfume_filters', array($this, 'filters_shortcode'));
        add_shortcode('parfume_similar', array($this, 'similar_shortcode'));
        add_shortcode('parfume_brand_products', array($this, 'brand_products_shortcode'));
        add_shortcode('parfume_comparison', array($this, 'comparison_shortcode'));
        add_shortcode('parfume_recently_viewed', array($this, 'recently_viewed_shortcode'));
    }
    
    public function rating_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
            'show_average' => true,
        ), $atts);
        
        // Convert string values to boolean
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        $show_average = filter_var($atts['show_average'], FILTER_VALIDATE_BOOLEAN);
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $rating = !empty($rating) ? floatval($rating) : 0;
        
        if (empty($rating) && !$show_empty) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-rating">
            <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?php echo $i <= round($rating) ? 'filled' : ''; ?>">★</span>
                <?php endfor; ?>
            </div>
            <?php if ($show_average && $rating > 0): ?>
                <div class="rating-average"><?php echo number_format($rating, 1); ?>/5</div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function details_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
        ), $atts);
        
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $bottle_size = get_post_meta($post->ID, '_parfume_bottle_size', true);
        
        $has_details = !empty($gender) || !empty($release_year) || !empty($longevity) || !empty($sillage) || !empty($bottle_size);
        
        if (!$has_details && !$show_empty) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-details">
            <?php if ($has_details): ?>
                <ul>
                    <?php if (!empty($gender)): ?>
                        <li><strong><?php _e('Gender:', 'parfume-reviews'); ?></strong> <?php echo esc_html($gender); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($release_year)): ?>
                        <li><strong><?php _e('Release Year:', 'parfume-reviews'); ?></strong> <?php echo esc_html($release_year); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($longevity)): ?>
                        <li><strong><?php _e('Longevity:', 'parfume-reviews'); ?></strong> <?php echo esc_html($longevity); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($sillage)): ?>
                        <li><strong><?php _e('Sillage:', 'parfume-reviews'); ?></strong> <?php echo esc_html($sillage); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($bottle_size)): ?>
                        <li><strong><?php _e('Bottle Size:', 'parfume-reviews'); ?></strong> <?php echo esc_html($bottle_size); ?></li>
                    <?php endif; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('No details available.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function stores_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
        ), $atts);
        
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        if (empty($stores) && !$show_empty) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-stores">
            <h3><?php _e('Where to Buy', 'parfume-reviews'); ?></h3>
            
            <?php if (!empty($stores)): ?>
                <div class="store-list">
                    <?php foreach ($stores as $store): ?>
                        <?php if (empty($store['name'])) continue; ?>
                        <div class="store-item">
                            <?php if (!empty($store['logo'])): ?>
                                <div class="store-logo">
                                    <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" loading="lazy">
                                </div>
                            <?php endif; ?>
                            
                            <div class="store-info">
                                <h4><?php echo esc_html($store['name']); ?></h4>
                                
                                <?php if (!empty($store['price'])): ?>
                                    <div class="store-price">
                                        <?php echo esc_html($store['price']); ?>
                                        <?php if (!empty($store['size'])): ?>
                                            <span class="size">(<?php echo esc_html($store['size']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($store['promo_code'])): ?>
                                    <div class="store-promo">
                                        <?php _e('Promo Code:', 'parfume-reviews'); ?> 
                                        <strong><?php echo esc_html($store['promo_code']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="store-links">
                                    <?php if (!empty($store['url'])): ?>
                                        <a href="<?php echo esc_url($store['url']); ?>" target="_blank" rel="nofollow noopener">
                                            <?php _e('View Product', 'parfume-reviews'); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($store['affiliate_url'])): ?>
                                        <a href="<?php echo esc_url($store['affiliate_url']); ?>" 
                                           class="<?php echo esc_attr(!empty($store['affiliate_class']) ? $store['affiliate_class'] : 'affiliate-link'); ?>" 
                                           target="<?php echo esc_attr(!empty($store['affiliate_target']) ? $store['affiliate_target'] : '_blank'); ?>" 
                                           rel="<?php echo esc_attr(!empty($store['affiliate_rel']) ? $store['affiliate_rel'] : 'nofollow noopener'); ?>">
                                            <?php echo !empty($store['affiliate_anchor']) ? esc_html($store['affiliate_anchor']) : __('Buy Now', 'parfume-reviews'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($store['last_updated'])): ?>
                                    <div class="store-updated">
                                        <small><?php _e('Last updated:', 'parfume-reviews'); ?> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($store['last_updated']))); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('No stores found for this perfume.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_gender' => true,
            'show_aroma_type' => true,
            'show_brand' => true,
            'show_season' => true,
            'show_intensity' => true,
            'show_notes' => true,
            'show_perfumer' => true,
        ), $atts);
        
        // Convert string values to boolean
        foreach ($atts as $key => $value) {
            $atts[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        ob_start();
        ?>
        <div class="parfume-filters">
            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('parfume')); ?>">
                <?php if ($atts['show_gender']): ?>
                    <div class="filter-group">
                        <label for="gender"><?php _e('Gender', 'parfume-reviews'); ?></label>
                        <?php
                        wp_dropdown_categories(array(
                            'show_option_all' => __('All Genders', 'parfume-reviews'),
                            'taxonomy' => 'gender',
                            'name' => 'gender',
                            'value_field' => 'slug',
                            'selected' => isset($_GET['gender']) ? sanitize_text_field($_GET['gender']) : '',
                            'hierarchical' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_aroma_type']): ?>
                    <div class="filter-group">
                        <label for="aroma_type"><?php _e('Aroma Type', 'parfume-reviews'); ?></label>
                        <?php
                        wp_dropdown_categories(array(
                            'show_option_all' => __('All Types', 'parfume-reviews'),
                            'taxonomy' => 'aroma_type',
                            'name' => 'aroma_type',
                            'value_field' => 'slug',
                            'selected' => isset($_GET['aroma_type']) ? sanitize_text_field($_GET['aroma_type']) : '',
                            'hierarchical' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_brand']): ?>
                    <div class="filter-group">
                        <label for="marki"><?php _e('Brand', 'parfume-reviews'); ?></label>
                        <?php
                        wp_dropdown_categories(array(
                            'show_option_all' => __('All Brands', 'parfume-reviews'),
                            'taxonomy' => 'marki',
                            'name' => 'marki',
                            'value_field' => 'slug',
                            'selected' => isset($_GET['marki']) ? sanitize_text_field($_GET['marki']) : '',
                            'hierarchical' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_season']): ?>
                    <div class="filter-group">
                        <label for="season"><?php _e('Season', 'parfume-reviews'); ?></label>
                        <?php
                        wp_dropdown_categories(array(
                            'show_option_all' => __('All Seasons', 'parfume-reviews'),
                            'taxonomy' => 'season',
                            'name' => 'season',
                            'value_field' => 'slug',
                            'selected' => isset($_GET['season']) ? sanitize_text_field($_GET['season']) : '',
                            'hierarchical' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_intensity']): ?>
                    <div class="filter-group">
                        <label for="intensity"><?php _e('Intensity', 'parfume-reviews'); ?></label>
                        <?php
                        wp_dropdown_categories(array(
                            'show_option_all' => __('All Intensities', 'parfume-reviews'),
                            'taxonomy' => 'intensity',
                            'name' => 'intensity',
                            'value_field' => 'slug',
                            'selected' => isset($_GET['intensity']) ? sanitize_text_field($_GET['intensity']) : '',
                            'hierarchical' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_notes']): ?>
                    <div class="filter-group">
                        <label for="notes"><?php _e('Notes', 'parfume-reviews'); ?></label>
                        <?php
                        wp_dropdown_categories(array(
                            'show_option_all' => __('All Notes', 'parfume-reviews'),
                            'taxonomy' => 'notes',
                            'name' => 'notes',
                            'value_field' => 'slug',
                            'selected' => isset($_GET['notes']) ? sanitize_text_field($_GET['notes']) : '',
                            'hierarchical' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_perfumer']): ?>
                    <div class="filter-group">
                        <label for="perfumer"><?php _e('Perfumer', 'parfume-reviews'); ?></label>
                        <?php
                        wp_dropdown_categories(array(
                            'show_option_all' => __('All Perfumers', 'parfume-reviews'),
                            'taxonomy' => 'perfumer',
                            'name' => 'perfumer',
                            'value_field' => 'slug',
                            'selected' => isset($_GET['perfumer']) ? sanitize_text_field($_GET['perfumer']) : '',
                            'hierarchical' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="filter-submit">
                    <button type="submit" class="button"><?php _e('Filter', 'parfume-reviews'); ?></button>
                    <a href="<?php echo esc_url(get_post_type_archive_link('parfume')); ?>" class="button"><?php _e('Reset', 'parfume-reviews'); ?></a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function similar_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 4,
            'title' => __('Similar Perfumes', 'parfume-reviews'),
        ), $atts);
        
        $limit = intval($atts['limit']);
        if ($limit <= 0) $limit = 4;
        
        // Get current perfume taxonomies
        $brands = wp_get_post_terms($post->ID, 'marki', array('fields' => 'ids'));
        $notes = wp_get_post_terms($post->ID, 'notes', array('fields' => 'ids'));
        $genders = wp_get_post_terms($post->ID, 'gender', array('fields' => 'ids'));
        
        if (is_wp_error($brands)) $brands = array();
        if (is_wp_error($notes)) $notes = array();
        if (is_wp_error($genders)) $genders = array();
        
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
                'terms' => $notes,
            );
        }
        
        if (!empty($genders)) {
            $tax_query[] = array(
                'taxonomy' => 'gender',
                'field' => 'term_id',
                'terms' => $genders,
            );
        }
        
        if (count($tax_query) === 1) {
            return ''; // No taxonomies found
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
            'post__not_in' => array($post->ID),
            'tax_query' => $tax_query,
        );
        
        $similar = new \WP_Query($args);
        
        ob_start();
        
        if ($similar->have_posts()):
            ?>
            <div class="similar-parfumes">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                
                <div class="parfume-grid">
                    <?php while ($similar->have_posts()): $similar->the_post(); ?>
                        <div class="parfume-item">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="parfume-thumbnail">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                <h4><?php the_title(); ?></h4>
                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round(floatval($rating)) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public function brand_products_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 4,
            'title' => __('Other Products by This Brand', 'parfume-reviews'),
        ), $atts);
        
        $limit = intval($atts['limit']);
        if ($limit <= 0) $limit = 4;
        
        // Get current perfume brand
        $brands = wp_get_post_terms($post->ID, 'marki', array('fields' => 'ids'));
        
        if (is_wp_error($brands) || empty($brands)) {
            return '';
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
            'post__not_in' => array($post->ID),
            'tax_query' => array(
                array(
                    'taxonomy' => 'marki',
                    'field' => 'term_id',
                    'terms' => $brands,
                ),
            ),
        );
        
        $brand_products = new \WP_Query($args);
        
        ob_start();
        
        if ($brand_products->have_posts()):
            ?>
            <div class="brand-products">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                
                <div class="parfume-grid">
                    <?php while ($brand_products->have_posts()): $brand_products->the_post(); ?>
                        <div class="parfume-item">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="parfume-thumbnail">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                <h4><?php the_title(); ?></h4>
                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round(floatval($rating)) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public function comparison_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ids' => '',
        ), $atts);
        
        if (empty($atts['ids'])) {
            return '<p>' . __('No perfume IDs provided for comparison.', 'parfume-reviews') . '</p>';
        }
        
        $ids = array_map('intval', explode(',', $atts['ids']));
        $ids = array_filter($ids); // Remove empty values
        
        if (empty($ids)) {
            return '<p>' . __('Invalid perfume IDs.', 'parfume-reviews') . '</p>';
        }
        
        $args = array(
            'post_type' => 'parfume',
            'post__in' => $ids,
            'posts_per_page' => -1,
            'orderby' => 'post__in',
        );
        
        $comparison = new \WP_Query($args);
        
        ob_start();
        
        if ($comparison->have_posts()):
            ?>
            <div class="parfume-comparison">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Feature', 'parfume-reviews'); ?></th>
                            <?php while ($comparison->have_posts()): $comparison->the_post(); ?>
                                <th>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </th>
                            <?php endwhile; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Image', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                            ?>
                                <td>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('thumbnail'); ?>
                                        <?php else: ?>
                                            <div class="no-image"><?php _e('No image', 'parfume-reviews'); ?></div>
                                        <?php endif; ?>
                                    </a>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Rating', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                            ?>
                                <td>
                                    <?php if (!empty($rating) && is_numeric($rating)): ?>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= round(floatval($rating)) ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                            <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-rating"><?php _e('No rating', 'parfume-reviews'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Gender', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $gender = get_post_meta(get_the_ID(), '_parfume_gender', true);
                            ?>
                                <td><?php echo !empty($gender) ? esc_html($gender) : '—'; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Release Year', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                            ?>
                                <td><?php echo !empty($release_year) ? esc_html($release_year) : '—'; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Longevity', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
                            ?>
                                <td><?php echo !empty($longevity) ? esc_html($longevity) : '—'; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Sillage', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
                            ?>
                                <td><?php echo !empty($sillage) ? esc_html($sillage) : '—'; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Brand', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                            ?>
                                <td><?php echo !empty($brands) && !is_wp_error($brands) ? esc_html(implode(', ', $brands)) : '—'; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Aroma Type', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $aroma_types = wp_get_post_terms(get_the_ID(), 'aroma_type', array('fields' => 'names'));
                            ?>
                                <td><?php echo !empty($aroma_types) && !is_wp_error($aroma_types) ? esc_html(implode(', ', $aroma_types)) : '—'; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Top Notes', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($comparison->have_posts()): $comparison->the_post(); 
                                $notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names'));
                            ?>
                                <td><?php echo !empty($notes) && !is_wp_error($notes) ? esc_html(implode(', ', array_slice($notes, 0, 3))) : '—'; ?></td>
                            <?php endwhile; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
        else:
            ?>
            <p><?php _e('No perfumes found for comparison.', 'parfume-reviews'); ?></p>
            <?php
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public function recently_viewed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 4,
            'title' => __('Recently Viewed', 'parfume-reviews'),
        ), $atts);
        
        $limit = intval($atts['limit']);
        if ($limit <= 0) $limit = 4;
        
        if (!isset($_COOKIE['parfume_recently_viewed'])) {
            return '<p>' . __('No recently viewed perfumes.', 'parfume-reviews') . '</p>';
        }
        
        $viewed = explode(',', $_COOKIE['parfume_recently_viewed']);
        $viewed = array_filter(array_map('intval', $viewed));
        
        if (empty($viewed)) {
            return '<p>' . __('No recently viewed perfumes.', 'parfume-reviews') . '</p>';
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
            'post__in' => $viewed,
            'orderby' => 'post__in',
        );
        
        $recently_viewed = new \WP_Query($args);
        
        ob_start();
        
        if ($recently_viewed->have_posts()):
            ?>
            <div class="recently-viewed">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                
                <div class="parfume-grid">
                    <?php while ($recently_viewed->have_posts()): $recently_viewed->the_post(); ?>
                        <div class="parfume-item">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="parfume-thumbnail">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                <h4><?php the_title(); ?></h4>
                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round(floatval($rating)) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php
        else:
            ?>
            <p><?php _e('No recently viewed perfumes found.', 'parfume-reviews'); ?></p>
            <?php
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
}
?>