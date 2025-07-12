<?php
/**
 * Shortcodes Class for Parfume Reviews Plugin
 * 
 * Handles all shortcode functionality
 * 
 * @package Parfume_Reviews
 * @since 1.0.0
 */

namespace Parfume_Reviews;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes {
    
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('parfume_grid', array($this, 'parfume_grid_shortcode'));
        add_shortcode('parfume_filters', array($this, 'parfume_filters_shortcode'));
        add_shortcode('parfume_search', array($this, 'parfume_search_shortcode'));
        add_shortcode('recent_parfumes', array($this, 'recent_parfumes_shortcode'));
        add_shortcode('featured_parfumes', array($this, 'featured_parfumes_shortcode'));
        add_shortcode('popular_brands', array($this, 'popular_brands_shortcode'));
        add_shortcode('parfume_comparison', array($this, 'comparison_shortcode'));
        add_shortcode('parfume_notes', array($this, 'notes_shortcode'));
        add_shortcode('brand_parfumes', array($this, 'brand_parfumes_shortcode'));
        add_shortcode('similar_parfumes', array($this, 'similar_parfumes_shortcode'));
        add_shortcode('perfumer_parfumes', array($this, 'perfumer_parfumes_shortcode'));
        add_shortcode('parfume_rating', array($this, 'rating_shortcode'));
        add_shortcode('parfume_reviews_stats', array($this, 'stats_shortcode'));
        add_shortcode('parfume_blog_recent', array($this, 'blog_recent_shortcode'));
    }
    
    /**
     * Parfume Grid Shortcode
     * [parfume_grid ids="1,2,3" columns="3" show_rating="true"]
     */
    public function parfume_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ids' => '',
            'columns' => '4',
            'limit' => '12',
            'orderby' => 'date',
            'order' => 'DESC',
            'brand' => '',
            'gender' => '',
            'aroma_type' => '',
            'season' => '',
            'intensity' => '',
            'notes' => '',
            'perfumer' => '',
            'show_rating' => 'true',
            'show_price' => 'true',
            'show_brand' => 'true',
            'class' => 'parfume-grid-shortcode',
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );
        
        // Handle specific IDs
        if (!empty($atts['ids'])) {
            $ids = array_map('intval', explode(',', $atts['ids']));
            $args['post__in'] = $ids;
            $args['orderby'] = 'post__in';
        }
        
        // Build tax query
        $tax_query = array();
        
        if (!empty($atts['brand'])) {
            $tax_query[] = array(
                'taxonomy' => 'marki',
                'field' => 'slug',
                'terms' => explode(',', $atts['brand']),
            );
        }
        
        if (!empty($atts['gender'])) {
            $tax_query[] = array(
                'taxonomy' => 'gender',
                'field' => 'slug',
                'terms' => explode(',', $atts['gender']),
            );
        }
        
        if (!empty($atts['aroma_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'aroma_type',
                'field' => 'slug',
                'terms' => explode(',', $atts['aroma_type']),
            );
        }
        
        if (!empty($atts['season'])) {
            $tax_query[] = array(
                'taxonomy' => 'season',
                'field' => 'slug',
                'terms' => explode(',', $atts['season']),
            );
        }
        
        if (!empty($atts['intensity'])) {
            $tax_query[] = array(
                'taxonomy' => 'intensity',
                'field' => 'slug',
                'terms' => explode(',', $atts['intensity']),
            );
        }
        
        if (!empty($atts['notes'])) {
            $tax_query[] = array(
                'taxonomy' => 'notes',
                'field' => 'slug',
                'terms' => explode(',', $atts['notes']),
            );
        }
        
        if (!empty($atts['perfumer'])) {
            $tax_query[] = array(
                'taxonomy' => 'perfumer',
                'field' => 'slug',
                'terms' => explode(',', $atts['perfumer']),
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p class="no-parfumes-found">' . __('No perfumes found.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['class']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <div class="parfume-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php while ($query->have_posts()): $query->the_post(); ?>
                    <div class="parfume-grid-item">
                        <article class="parfume-card">
                            <a href="<?php the_permalink(); ?>" class="parfume-card-link">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="parfume-thumbnail">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="parfume-info">
                                    <h3 class="parfume-title"><?php the_title(); ?></h3>
                                    
                                    <?php if ($atts['show_brand'] === 'true'): ?>
                                        <?php
                                        $brands = wp_get_post_terms(get_the_ID(), 'marki');
                                        if (!empty($brands) && !is_wp_error($brands)):
                                        ?>
                                            <div class="parfume-brand">
                                                <?php foreach ($brands as $brand): ?>
                                                    <span><?php echo esc_html($brand->name); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($atts['show_rating'] === 'true'): ?>
                                        <?php
                                        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                        if (!empty($rating)):
                                        ?>
                                            <div class="parfume-rating">
                                                <?php echo parfume_reviews_display_rating($rating); ?>
                                                <span class="rating-text"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($atts['show_price'] === 'true'): ?>
                                        <?php
                                        $stores = get_post_meta(get_the_ID(), '_parfume_stores_v2', true);
                                        if (!empty($stores) && is_array($stores)):
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
                                            
                                            if ($lowest_price):
                                        ?>
                                            <div class="parfume-price">
                                                <?php _e('From', 'parfume-reviews'); ?> <?php echo number_format($lowest_price, 2); ?> лв.
                                            </div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </article>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Parfume Filters Shortcode
     * [parfume_filters compact="false"]
     */
    public function parfume_filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'compact' => 'false',
            'action' => '', // URL to submit to, defaults to current page
            'method' => 'get',
            'show_search' => 'true',
            'show_reset' => 'true',
        ), $atts);
        
        $current_values = array(
            'brand' => isset($_GET['filter_brand']) ? sanitize_text_field($_GET['filter_brand']) : '',
            'gender' => isset($_GET['filter_gender']) ? sanitize_text_field($_GET['filter_gender']) : '',
            'aroma_type' => isset($_GET['filter_aroma_type']) ? sanitize_text_field($_GET['filter_aroma_type']) : '',
            'season' => isset($_GET['filter_season']) ? sanitize_text_field($_GET['filter_season']) : '',
            'intensity' => isset($_GET['filter_intensity']) ? sanitize_text_field($_GET['filter_intensity']) : '',
            'perfumer' => isset($_GET['filter_perfumer']) ? sanitize_text_field($_GET['filter_perfumer']) : '',
            'search' => isset($_GET['parfume_search']) ? sanitize_text_field($_GET['parfume_search']) : '',
        );
        
        $action_url = !empty($atts['action']) ? $atts['action'] : '';
        $compact_class = $atts['compact'] === 'true' ? ' compact' : '';
        
        ob_start();
        ?>
        <form class="parfume-filters-form<?php echo $compact_class; ?>" method="<?php echo esc_attr($atts['method']); ?>" action="<?php echo esc_url($action_url); ?>">
            
            <?php if ($atts['show_search'] === 'true'): ?>
                <div class="filter-group search-group">
                    <label for="parfume_search"><?php _e('Search', 'parfume-reviews'); ?></label>
                    <input type="text" 
                           id="parfume_search" 
                           name="parfume_search" 
                           value="<?php echo esc_attr($current_values['search']); ?>" 
                           placeholder="<?php esc_attr_e('Search perfumes...', 'parfume-reviews'); ?>">
                </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <label for="filter_brand"><?php _e('Brand', 'parfume-reviews'); ?></label>
                <select id="filter_brand" name="filter_brand">
                    <option value=""><?php _e('All Brands', 'parfume-reviews'); ?></option>
                    <?php
                    $brands = get_terms(array(
                        'taxonomy' => 'marki',
                        'hide_empty' => true,
                        'orderby' => 'name',
                    ));
                    foreach ($brands as $brand):
                    ?>
                        <option value="<?php echo esc_attr($brand->slug); ?>" <?php selected($current_values['brand'], $brand->slug); ?>>
                            <?php echo esc_html($brand->name); ?> (<?php echo $brand->count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_gender"><?php _e('Gender', 'parfume-reviews'); ?></label>
                <select id="filter_gender" name="filter_gender">
                    <option value=""><?php _e('All Genders', 'parfume-reviews'); ?></option>
                    <?php
                    $genders = get_terms(array(
                        'taxonomy' => 'gender',
                        'hide_empty' => true,
                    ));
                    foreach ($genders as $gender):
                    ?>
                        <option value="<?php echo esc_attr($gender->slug); ?>" <?php selected($current_values['gender'], $gender->slug); ?>>
                            <?php echo esc_html($gender->name); ?> (<?php echo $gender->count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_aroma_type"><?php _e('Aroma Type', 'parfume-reviews'); ?></label>
                <select id="filter_aroma_type" name="filter_aroma_type">
                    <option value=""><?php _e('All Types', 'parfume-reviews'); ?></option>
                    <?php
                    $aroma_types = get_terms(array(
                        'taxonomy' => 'aroma_type',
                        'hide_empty' => true,
                    ));
                    foreach ($aroma_types as $type):
                    ?>
                        <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($current_values['aroma_type'], $type->slug); ?>>
                            <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($atts['compact'] !== 'true'): ?>
                <div class="filter-group">
                    <label for="filter_season"><?php _e('Season', 'parfume-reviews'); ?></label>
                    <select id="filter_season" name="filter_season">
                        <option value=""><?php _e('All Seasons', 'parfume-reviews'); ?></option>
                        <?php
                        $seasons = get_terms(array(
                            'taxonomy' => 'season',
                            'hide_empty' => true,
                        ));
                        foreach ($seasons as $season):
                        ?>
                            <option value="<?php echo esc_attr($season->slug); ?>" <?php selected($current_values['season'], $season->slug); ?>>
                                <?php echo esc_html($season->name); ?> (<?php echo $season->count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter_intensity"><?php _e('Intensity', 'parfume-reviews'); ?></label>
                    <select id="filter_intensity" name="filter_intensity">
                        <option value=""><?php _e('All Intensities', 'parfume-reviews'); ?></option>
                        <?php
                        $intensities = get_terms(array(
                            'taxonomy' => 'intensity',
                            'hide_empty' => true,
                        ));
                        foreach ($intensities as $intensity):
                        ?>
                            <option value="<?php echo esc_attr($intensity->slug); ?>" <?php selected($current_values['intensity'], $intensity->slug); ?>>
                                <?php echo esc_html($intensity->name); ?> (<?php echo $intensity->count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter_perfumer"><?php _e('Perfumer', 'parfume-reviews'); ?></label>
                    <select id="filter_perfumer" name="filter_perfumer">
                        <option value=""><?php _e('All Perfumers', 'parfume-reviews'); ?></option>
                        <?php
                        $perfumers = get_terms(array(
                            'taxonomy' => 'perfumer',
                            'hide_empty' => true,
                            'orderby' => 'name',
                        ));
                        foreach ($perfumers as $perfumer):
                        ?>
                            <option value="<?php echo esc_attr($perfumer->slug); ?>" <?php selected($current_values['perfumer'], $perfumer->slug); ?>>
                                <?php echo esc_html($perfumer->name); ?> (<?php echo $perfumer->count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="filter-actions">
                <button type="submit" class="button filter-submit">
                    <?php _e('Filter', 'parfume-reviews'); ?>
                </button>
                
                <?php if ($atts['show_reset'] === 'true'): ?>
                    <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="button filter-reset">
                        <?php _e('Reset', 'parfume-reviews'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Recent Parfumes Shortcode
     * [recent_parfumes limit="6" columns="3"]
     */
    public function recent_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '6',
            'columns' => '3',
            'show_excerpt' => 'false',
            'show_rating' => 'true',
            'show_price' => 'true',
        ), $atts);
        
        return $this->parfume_grid_shortcode(array(
            'limit' => $atts['limit'],
            'columns' => $atts['columns'],
            'orderby' => 'date',
            'order' => 'DESC',
            'show_rating' => $atts['show_rating'],
            'show_price' => $atts['show_price'],
            'class' => 'recent-parfumes-shortcode',
        ));
    }
    
    /**
     * Featured Parfumes Shortcode
     * [featured_parfumes limit="4" columns="4"]
     */
    public function featured_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '4',
            'columns' => '4',
            'show_rating' => 'true',
            'show_price' => 'true',
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                array(
                    'key' => '_parfume_featured',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_parfume_featured_order',
            'order' => 'ASC'
        );
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p class="no-featured-parfumes">' . __('No featured perfumes found.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="featured-parfumes-shortcode">
            <div class="parfume-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php while ($query->have_posts()): $query->the_post(); ?>
                    <div class="parfume-grid-item featured">
                        <article class="parfume-card featured-card">
                            <div class="featured-badge">
                                <span><?php _e('Featured', 'parfume-reviews'); ?></span>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="parfume-card-link">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="parfume-thumbnail">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="parfume-info">
                                    <h3 class="parfume-title"><?php the_title(); ?></h3>
                                    
                                    <?php
                                    $brands = wp_get_post_terms(get_the_ID(), 'marki');
                                    if (!empty($brands) && !is_wp_error($brands)):
                                    ?>
                                        <div class="parfume-brand">
                                            <?php foreach ($brands as $brand): ?>
                                                <span><?php echo esc_html($brand->name); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($atts['show_rating'] === 'true'): ?>
                                        <?php
                                        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                        if (!empty($rating)):
                                        ?>
                                            <div class="parfume-rating">
                                                <?php echo parfume_reviews_display_rating($rating); ?>
                                                <span class="rating-text"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </article>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Popular Brands Shortcode
     * [popular_brands limit="10" columns="5" show_count="true"]
     */
    public function popular_brands_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'columns' => '5',
            'show_count' => 'true',
            'show_logo' => 'true',
            'orderby' => 'count',
            'order' => 'DESC',
        ), $atts);
        
        $brands = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => true,
            'number' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        ));
        
        if (empty($brands) || is_wp_error($brands)) {
            return '<p class="no-brands-found">' . __('No brands found.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="popular-brands-shortcode">
            <div class="brands-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($brands as $brand): ?>
                    <div class="brand-grid-item">
                        <a href="<?php echo get_term_link($brand); ?>" class="brand-card">
                            <?php if ($atts['show_logo'] === 'true'): ?>
                                <?php
                                $logo_id = get_term_meta($brand->term_id, 'brand-image-id', true);
                                if ($logo_id):
                                ?>
                                    <div class="brand-logo">
                                        <?php echo wp_get_attachment_image($logo_id, 'thumbnail'); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="brand-logo-placeholder">
                                        <span><?php echo esc_html(substr($brand->name, 0, 1)); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="brand-info">
                                <h3 class="brand-name"><?php echo esc_html($brand->name); ?></h3>
                                <?php if ($atts['show_count'] === 'true'): ?>
                                    <span class="brand-count">
                                        <?php printf(_n('%d perfume', '%d perfumes', $brand->count, 'parfume-reviews'), $brand->count); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Parfume Comparison Shortcode
     * [parfume_comparison ids="1,2,3"]
     */
    public function comparison_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ids' => '',
        ), $atts);
        
        if (empty($atts['ids'])) {
            return '<p class="comparison-error">' . __('No perfume IDs provided for comparison.', 'parfume-reviews') . '</p>';
        }
        
        $ids = array_map('intval', explode(',', $atts['ids']));
        $ids = array_filter($ids);
        
        if (empty($ids)) {
            return '<p class="comparison-error">' . __('Invalid perfume IDs.', 'parfume-reviews') . '</p>';
        }
        
        $parfumes = get_posts(array(
            'post_type' => 'parfume',
            'post__in' => $ids,
            'post_status' => 'publish',
            'orderby' => 'post__in'
        ));
        
        if (empty($parfumes)) {
            return '<p class="comparison-error">' . __('No valid perfumes found for comparison.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="parfume-comparison-shortcode">
            <div class="comparison-table-wrapper">
                <table class="parfume-comparison-table">
                    <thead>
                        <tr>
                            <th class="comparison-attribute"><?php _e('Attribute', 'parfume-reviews'); ?></th>
                            <?php foreach ($parfumes as $parfume): ?>
                                <th class="comparison-parfume">
                                    <div class="parfume-header">
                                        <?php if (has_post_thumbnail($parfume->ID)): ?>
                                            <div class="parfume-image">
                                                <?php echo get_the_post_thumbnail($parfume->ID, 'thumbnail'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <h4><a href="<?php echo get_permalink($parfume->ID); ?>"><?php echo esc_html($parfume->post_title); ?></a></h4>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Brand -->
                        <tr>
                            <td class="attribute-name"><?php _e('Brand', 'parfume-reviews'); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td>
                                    <?php
                                    $brands = wp_get_post_terms($parfume->ID, 'marki');
                                    if (!empty($brands) && !is_wp_error($brands)) {
                                        foreach ($brands as $brand) {
                                            echo '<a href="' . get_term_link($brand) . '">' . esc_html($brand->name) . '</a> ';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Rating -->
                        <tr>
                            <td class="attribute-name"><?php _e('Rating', 'parfume-reviews'); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td>
                                    <?php
                                    $rating = get_post_meta($parfume->ID, '_parfume_rating', true);
                                    if (!empty($rating)) {
                                        echo parfume_reviews_display_rating($rating) . ' (' . number_format($rating, 1) . ')';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Gender -->
                        <tr>
                            <td class="attribute-name"><?php _e('Gender', 'parfume-reviews'); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td>
                                    <?php
                                    $genders = wp_get_post_terms($parfume->ID, 'gender');
                                    if (!empty($genders) && !is_wp_error($genders)) {
                                        echo esc_html($genders[0]->name);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Aroma Type -->
                        <tr>
                            <td class="attribute-name"><?php _e('Aroma Type', 'parfume-reviews'); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td>
                                    <?php
                                    $types = wp_get_post_terms($parfume->ID, 'aroma_type');
                                    if (!empty($types) && !is_wp_error($types)) {
                                        echo esc_html($types[0]->name);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Longevity -->
                        <tr>
                            <td class="attribute-name"><?php _e('Longevity', 'parfume-reviews'); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td>
                                    <?php
                                    $longevity = get_post_meta($parfume->ID, '_parfume_longevity', true);
                                    echo !empty($longevity) ? esc_html($longevity) : '-';
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Sillage -->
                        <tr>
                            <td class="attribute-name"><?php _e('Sillage', 'parfume-reviews'); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td>
                                    <?php
                                    $sillage = get_post_meta($parfume->ID, '_parfume_sillage', true);
                                    echo !empty($sillage) ? esc_html($sillage) : '-';
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Price Range -->
                        <tr>
                            <td class="attribute-name"><?php _e('Price Range', 'parfume-reviews'); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td>
                                    <?php
                                    $stores = get_post_meta($parfume->ID, '_parfume_stores_v2', true);
                                    if (!empty($stores) && is_array($stores)) {
                                        $prices = array();
                                        foreach ($stores as $store) {
                                            if (!empty($store['scraped_data']['variants'])) {
                                                foreach ($store['scraped_data']['variants'] as $variant) {
                                                    $price = floatval(preg_replace('/[^\d.]/', '', $variant['price']));
                                                    if ($price > 0) {
                                                        $prices[] = $price;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if (!empty($prices)) {
                                            $min_price = min($prices);
                                            $max_price = max($prices);
                                            if ($min_price == $max_price) {
                                                echo number_format($min_price, 2) . ' лв.';
                                            } else {
                                                echo number_format($min_price, 2) . ' - ' . number_format($max_price, 2) . ' лв.';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Notes Display Shortcode
     * [parfume_notes id="123" style="pyramid"]
     */
    public function notes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'style' => 'pyramid', // pyramid, list, grid
            'show_icons' => 'true',
        ), $atts);
        
        $post_id = intval($atts['id']);
        $notes = wp_get_post_terms($post_id, 'notes');
        
        if (empty($notes) || is_wp_error($notes)) {
            return '<p class="no-notes">' . __('No notes found for this perfume.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        
        if ($atts['style'] === 'pyramid') {
            // Pyramid style - split notes into top, middle, base
            $total_notes = count($notes);
            $top_count = min(3, ceil($total_notes / 3));
            $middle_count = min(3, ceil(($total_notes - $top_count) / 2));
            
            $top_notes = array_slice($notes, 0, $top_count);
            $middle_notes = array_slice($notes, $top_count, $middle_count);
            $base_notes = array_slice($notes, $top_count + $middle_count);
            
            ?>
            <div class="parfume-notes-pyramid">
                <?php if (!empty($top_notes)): ?>
                    <div class="notes-level top-notes">
                        <h4><?php _e('Top Notes', 'parfume-reviews'); ?></h4>
                        <div class="notes-list">
                            <?php foreach ($top_notes as $note): ?>
                                <span class="note-item">
                                    <?php if ($atts['show_icons'] === 'true'): ?>
                                        <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                                    <?php endif; ?>
                                    <a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($middle_notes)): ?>
                    <div class="notes-level middle-notes">
                        <h4><?php _e('Middle Notes', 'parfume-reviews'); ?></h4>
                        <div class="notes-list">
                            <?php foreach ($middle_notes as $note): ?>
                                <span class="note-item">
                                    <?php if ($atts['show_icons'] === 'true'): ?>
                                        <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                                    <?php endif; ?>
                                    <a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($base_notes)): ?>
                    <div class="notes-level base-notes">
                        <h4><?php _e('Base Notes', 'parfume-reviews'); ?></h4>
                        <div class="notes-list">
                            <?php foreach ($base_notes as $note): ?>
                                <span class="note-item">
                                    <?php if ($atts['show_icons'] === 'true'): ?>
                                        <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                                    <?php endif; ?>
                                    <a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        } else {
            // List or grid style
            ?>
            <div class="parfume-notes-<?php echo esc_attr($atts['style']); ?>">
                <?php foreach ($notes as $note): ?>
                    <span class="note-item">
                        <?php if ($atts['show_icons'] === 'true'): ?>
                            <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                        <?php endif; ?>
                        <a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php
        }
        
        return ob_get_clean();
    }
    
    /**
     * Brand Parfumes Shortcode
     * [brand_parfumes brand="dior" limit="4" exclude_current="true"]
     */
    public function brand_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'limit' => '4',
            'columns' => '4',
            'exclude_current' => 'true',
            'orderby' => 'date',
            'order' => 'DESC',
        ), $atts);
        
        if (empty($atts['brand'])) {
            // Try to get brand from current post
            if (is_singular('parfume')) {
                $brands = wp_get_post_terms(get_the_ID(), 'marki');
                if (!empty($brands) && !is_wp_error($brands)) {
                    $atts['brand'] = $brands[0]->slug;
                }
            }
        }
        
        if (empty($atts['brand'])) {
            return '<p class="no-brand-specified">' . __('No brand specified.', 'parfume-reviews') . '</p>';
        }
        
        $shortcode_atts = array(
            'brand' => $atts['brand'],
            'limit' => $atts['limit'],
            'columns' => $atts['columns'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'class' => 'brand-parfumes-shortcode',
        );
        
        // Exclude current post if needed
        if ($atts['exclude_current'] === 'true' && is_singular('parfume')) {
            $args = array(
                'post_type' => 'parfume',
                'post__not_in' => array(get_the_ID()),
                'posts_per_page' => intval($atts['limit']),
                'orderby' => $atts['orderby'],
                'order' => $atts['order'],
                'tax_query' => array(
                    array(
                        'taxonomy' => 'marki',
                        'field' => 'slug',
                        'terms' => $atts['brand'],
                    )
                )
            );
            
            $query = new \WP_Query($args);
            
            if (!$query->have_posts()) {
                return '<p class="no-brand-parfumes">' . __('No other perfumes found from this brand.', 'parfume-reviews') . '</p>';
            }
            
            $ids = array();
            while ($query->have_posts()) {
                $query->the_post();
                $ids[] = get_the_ID();
            }
            wp_reset_postdata();
            
            $shortcode_atts['ids'] = implode(',', $ids);
            unset($shortcode_atts['brand']);
        }
        
        return $this->parfume_grid_shortcode($shortcode_atts);
    }
    
    /**
     * Similar Parfumes Shortcode
     * [similar_parfumes id="123" limit="4" based_on="notes,brand"]
     */
    public function similar_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'limit' => '4',
            'columns' => '4',
            'based_on' => 'notes,brand,aroma_type', // what to base similarity on
        ), $atts);
        
        $post_id = intval($atts['id']);
        $based_on = explode(',', $atts['based_on']);
        $based_on = array_map('trim', $based_on);
        
        // Get similar perfumes based on the criteria
        $similar_ids = $this->find_similar_parfumes($post_id, $based_on, intval($atts['limit']) + 1);
        
        // Remove current post from results
        $similar_ids = array_diff($similar_ids, array($post_id));
        $similar_ids = array_slice($similar_ids, 0, intval($atts['limit']));
        
        if (empty($similar_ids)) {
            return '<p class="no-similar-parfumes">' . __('No similar perfumes found.', 'parfume-reviews') . '</p>';
        }
        
        return $this->parfume_grid_shortcode(array(
            'ids' => implode(',', $similar_ids),
            'columns' => $atts['columns'],
            'class' => 'similar-parfumes-shortcode',
        ));
    }
    
    /**
     * Perfumer Parfumes Shortcode
     * [perfumer_parfumes perfumer="francois-demachy" limit="6"]
     */
    public function perfumer_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'perfumer' => '',
            'limit' => '6',
            'columns' => '3',
            'exclude_current' => 'true',
        ), $atts);
        
        if (empty($atts['perfumer'])) {
            // Try to get perfumer from current post
            if (is_singular('parfume')) {
                $perfumers = wp_get_post_terms(get_the_ID(), 'perfumer');
                if (!empty($perfumers) && !is_wp_error($perfumers)) {
                    $atts['perfumer'] = $perfumers[0]->slug;
                }
            }
        }
        
        if (empty($atts['perfumer'])) {
            return '<p class="no-perfumer-specified">' . __('No perfumer specified.', 'parfume-reviews') . '</p>';
        }
        
        $shortcode_atts = array(
            'perfumer' => $atts['perfumer'],
            'limit' => $atts['limit'],
            'columns' => $atts['columns'],
            'class' => 'perfumer-parfumes-shortcode',
        );
        
        return $this->parfume_grid_shortcode($shortcode_atts);
    }
    
    /**
     * Rating Display Shortcode
     * [parfume_rating id="123" show_text="true"]
     */
    public function rating_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'show_text' => 'true',
            'show_count' => 'true',
        ), $atts);
        
        $post_id = intval($atts['id']);
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        
        if (empty($rating)) {
            return '<span class="no-rating">' . __('No rating', 'parfume-reviews') . '</span>';
        }
        
        ob_start();
        ?>
        <span class="parfume-rating-shortcode">
            <?php echo parfume_reviews_display_rating($rating); ?>
            <?php if ($atts['show_text'] === 'true'): ?>
                <span class="rating-text"><?php echo number_format($rating, 1); ?>/5</span>
            <?php endif; ?>
            <?php if ($atts['show_count'] === 'true'): ?>
                <?php
                $review_count = get_comments_number($post_id);
                if ($review_count > 0):
                ?>
                    <span class="rating-count">
                        (<?php printf(_n('%d review', '%d reviews', $review_count, 'parfume-reviews'), $review_count); ?>)
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </span>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Plugin Stats Shortcode
     * [parfume_reviews_stats]
     */
    public function stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_perfumes' => 'true',
            'show_brands' => 'true',
            'show_reviews' => 'true',
            'show_perfumers' => 'true',
        ), $atts);
        
        ob_start();
        ?>
        <div class="parfume-reviews-stats">
            <?php if ($atts['show_perfumes'] === 'true'): ?>
                <?php
                $perfume_count = wp_count_posts('parfume');
                $published_perfumes = isset($perfume_count->publish) ? $perfume_count->publish : 0;
                ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($published_perfumes); ?></span>
                    <span class="stat-label"><?php _e('Perfumes', 'parfume-reviews'); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_brands'] === 'true'): ?>
                <?php
                $brand_count = wp_count_terms(array(
                    'taxonomy' => 'marki',
                    'hide_empty' => true,
                ));
                ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($brand_count); ?></span>
                    <span class="stat-label"><?php _e('Brands', 'parfume-reviews'); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_reviews'] === 'true'): ?>
                <?php
                $review_count = get_comments(array(
                    'post_type' => 'parfume',
                    'status' => 'approve',
                    'count' => true,
                ));
                ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($review_count); ?></span>
                    <span class="stat-label"><?php _e('Reviews', 'parfume-reviews'); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_perfumers'] === 'true'): ?>
                <?php
                $perfumer_count = wp_count_terms(array(
                    'taxonomy' => 'perfumer',
                    'hide_empty' => true,
                ));
                ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($perfumer_count); ?></span>
                    <span class="stat-label"><?php _e('Perfumers', 'parfume-reviews'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Blog Recent Posts Shortcode
     * [parfume_blog_recent limit="3" show_excerpt="true"]
     */
    public function blog_recent_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '3',
            'show_excerpt' => 'true',
            'show_date' => 'true',
            'show_author' => 'false',
            'excerpt_length' => '20',
        ), $atts);
        
        $recent_posts = get_posts(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($recent_posts)) {
            return '<p class="no-blog-posts">' . __('No blog posts found.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="parfume-blog-recent-shortcode">
            <div class="blog-posts-list">
                <?php foreach ($recent_posts as $post): ?>
                    <article class="blog-post-item">
                        <?php if (has_post_thumbnail($post->ID)): ?>
                            <div class="post-thumbnail">
                                <a href="<?php echo get_permalink($post); ?>">
                                    <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h3 class="post-title">
                                <a href="<?php echo get_permalink($post); ?>"><?php echo esc_html($post->post_title); ?></a>
                            </h3>
                            
                            <?php if ($atts['show_date'] === 'true' || $atts['show_author'] === 'true'): ?>
                                <div class="post-meta">
                                    <?php if ($atts['show_date'] === 'true'): ?>
                                        <span class="post-date"><?php echo get_the_date('', $post); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($atts['show_author'] === 'true'): ?>
                                        <span class="post-author">
                                            <?php _e('by', 'parfume-reviews'); ?> <?php echo get_the_author_meta('display_name', $post->post_author); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_excerpt'] === 'true'): ?>
                                <div class="post-excerpt">
                                    <?php echo wp_trim_words($post->post_content, intval($atts['excerpt_length'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Parfume Search Shortcode
     * [parfume_search placeholder="Search perfumes..." button_text="Search"]
     */
    public function parfume_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Search perfumes...', 'parfume-reviews'),
            'button_text' => __('Search', 'parfume-reviews'),
            'show_filters' => 'false',
            'action' => '', // URL to submit to
        ), $atts);
        
        $action_url = !empty($atts['action']) ? $atts['action'] : home_url('/');
        $current_search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        ob_start();
        ?>
        <div class="parfume-search-shortcode">
            <form class="parfume-search-form" method="get" action="<?php echo esc_url($action_url); ?>">
                <div class="search-input-wrapper">
                    <input type="text" 
                           name="s" 
                           value="<?php echo esc_attr($current_search); ?>" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                           class="search-field">
                    <input type="hidden" name="post_type" value="parfume">
                    <button type="submit" class="search-submit">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                
                <?php if ($atts['show_filters'] === 'true'): ?>
                    <div class="search-filters">
                        <?php echo do_shortcode('[parfume_filters compact="true" show_search="false"]'); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Helper method to find similar parfumes
     */
    private function find_similar_parfumes($post_id, $based_on, $limit = 5) {
        $similar_scores = array();
        
        // Get all parfumes except current one
        $all_parfumes = get_posts(array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__not_in' => array($post_id),
            'fields' => 'ids'
        ));
        
        if (empty($all_parfumes)) {
            return array();
        }
        
        // Get current post taxonomies
        $current_taxonomies = array();
        foreach ($based_on as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
            if (!is_wp_error($terms)) {
                $current_taxonomies[$taxonomy] = $terms;
            }
        }
        
        // Calculate similarity scores
        foreach ($all_parfumes as $parfume_id) {
            $score = 0;
            
            foreach ($based_on as $taxonomy) {
                if (!isset($current_taxonomies[$taxonomy])) {
                    continue;
                }
                
                $parfume_terms = wp_get_post_terms($parfume_id, $taxonomy, array('fields' => 'ids'));
                if (is_wp_error($parfume_terms)) {
                    continue;
                }
                
                // Calculate intersection
                $intersection = array_intersect($current_taxonomies[$taxonomy], $parfume_terms);
                $score += count($intersection);
            }
            
            if ($score > 0) {
                $similar_scores[$parfume_id] = $score;
            }
        }
        
        // Sort by similarity score
        arsort($similar_scores);
        
        return array_keys(array_slice($similar_scores, 0, $limit, true));
    }
}