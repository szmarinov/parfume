<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Shortcodes Handler - управлява всички shortcodes свързани с post types
 */
class Shortcodes {
    
    public function __construct() {
        // Регистрираме shortcodes
        add_shortcode('all_brands_archive', array($this, 'all_brands_archive_shortcode'));
        add_shortcode('all_notes_archive', array($this, 'all_notes_archive_shortcode'));
        add_shortcode('all_perfumers_archive', array($this, 'all_perfumers_archive_shortcode'));
        add_shortcode('parfume_grid', array($this, 'parfume_grid_shortcode'));
        add_shortcode('latest_parfumes', array($this, 'latest_parfumes_shortcode'));
        add_shortcode('featured_parfumes', array($this, 'featured_parfumes_shortcode'));
        add_shortcode('top_rated_parfumes', array($this, 'top_rated_parfumes_shortcode'));
        add_shortcode('parfume_filters', array($this, 'parfume_filters_shortcode'));
    }
    
    /**
     * Shortcode за показване на всички марки
     */
    public function all_brands_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 4,
            'show_count' => true,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
        ), $atts);
        
        $args = array(
            'taxonomy' => 'marki',
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        );
        
        if ($atts['limit'] > 0) {
            $args['number'] = intval($atts['limit']);
        }
        
        $terms = get_terms($args);
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('No brands found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="brands-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="brand-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            
            // Brand logo
            if (function_exists('parfume_reviews_get_brand_logo')) {
                $logo = parfume_reviews_get_brand_logo($term->term_id, 'medium');
                if ($logo) {
                    $output .= '<div class="brand-logo">' . $logo . '</div>';
                }
            }
            
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="count">(' . $term->count . ' ' . __('parfumes', 'parfume-reviews') . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode за показване на всички ноти
     */
    public function all_notes_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 6,
            'show_count' => true,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
        ), $atts);
        
        $args = array(
            'taxonomy' => 'notes',
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        );
        
        if ($atts['limit'] > 0) {
            $args['number'] = intval($atts['limit']);
        }
        
        $terms = get_terms($args);
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('No notes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="notes-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="note-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            
            // Note image
            if (function_exists('parfume_reviews_get_note_image')) {
                $image = parfume_reviews_get_note_image($term->term_id, 'thumbnail');
                if ($image) {
                    $output .= '<div class="note-image">' . $image . '</div>';
                }
            }
            
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="count">(' . $term->count . ' ' . __('parfumes', 'parfume-reviews') . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode за показване на всички парфюмьори
     */
    public function all_perfumers_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 3,
            'show_count' => true,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
        ), $atts);
        
        $args = array(
            'taxonomy' => 'perfumer',
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        );
        
        if ($atts['limit'] > 0) {
            $args['number'] = intval($atts['limit']);
        }
        
        $terms = get_terms($args);
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('No perfumers found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="perfumers-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="perfumer-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            
            // Perfumer photo
            if (function_exists('parfume_reviews_get_perfumer_photo')) {
                $photo = parfume_reviews_get_perfumer_photo($term->term_id);
                if ($photo) {
                    $output .= '<div class="perfumer-photo">' . $photo . '</div>';
                }
            }
            
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="count">(' . $term->count . ' ' . __('parfumes', 'parfume-reviews') . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode за показване на мрежа от парфюми
     */
    public function parfume_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 12,
            'columns' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '',
            'meta_value' => '',
            'brand' => '',
            'gender' => '',
            'aroma_type' => '',
            'season' => '',
            'intensity' => '',
            'show_rating' => true,
            'show_price' => true,
            'show_excerpt' => false,
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'post_status' => 'publish',
        );
        
        // Meta query
        if (!empty($atts['meta_key']) && !empty($atts['meta_value'])) {
            $args['meta_query'] = array(
                array(
                    'key' => sanitize_text_field($atts['meta_key']),
                    'value' => sanitize_text_field($atts['meta_value']),
                    'compare' => '='
                )
            );
        }
        
        // Tax query
        $tax_query = array();
        $taxonomies = array('brand' => 'marki', 'gender' => 'gender', 'aroma_type' => 'aroma_type', 'season' => 'season', 'intensity' => 'intensity');
        
        foreach ($taxonomies as $att_key => $taxonomy) {
            if (!empty($atts[$att_key])) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts[$att_key])
                );
            }
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('No parfumes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="parfume-grid columns-' . intval($atts['columns']) . '">';
        
        while ($query->have_posts()) {
            $query->the_post();
            $output .= $this->render_parfume_card($atts);
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    /**
     * Shortcode за последни парфюми
     */
    public function latest_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'columns' => 3,
            'show_rating' => true,
            'show_price' => true,
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => intval($atts['count']),
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
        );
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('No recent parfumes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="latest-parfumes-grid columns-' . intval($atts['columns']) . '">';
        $output .= '<h3>' . __('Latest Parfumes', 'parfume-reviews') . '</h3>';
        
        while ($query->have_posts()) {
            $query->the_post();
            $output .= $this->render_parfume_card($atts);
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    /**
     * Shortcode за препоръчани парфюми
     */
    public function featured_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'columns' => 3,
            'show_rating' => true,
            'show_price' => true,
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => intval($atts['count']),
            'meta_query' => array(
                array(
                    'key' => '_parfume_featured',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
        );
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('No featured parfumes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="featured-parfumes-grid columns-' . intval($atts['columns']) . '">';
        $output .= '<h3>' . __('Featured Parfumes', 'parfume-reviews') . '</h3>';
        
        while ($query->have_posts()) {
            $query->the_post();
            $output .= $this->render_parfume_card($atts);
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    /**
     * Shortcode за най-високо оценените парфюми
     */
    public function top_rated_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'columns' => 3,
            'min_rating' => 7.0,
            'show_rating' => true,
            'show_price' => true,
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => intval($atts['count']),
            'meta_query' => array(
                array(
                    'key' => '_parfume_rating',
                    'value' => floatval($atts['min_rating']),
                    'type' => 'DECIMAL',
                    'compare' => '>='
                )
            ),
            'meta_key' => '_parfume_rating',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'post_status' => 'publish',
        );
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('No top rated parfumes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="top-rated-parfumes-grid columns-' . intval($atts['columns']) . '">';
        $output .= '<h3>' . __('Top Rated Parfumes', 'parfume-reviews') . '</h3>';
        
        while ($query->have_posts()) {
            $query->the_post();
            $output .= $this->render_parfume_card($atts);
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    /**
     * Помощен метод за рендериране на parfume карта
     */
    private function render_parfume_card($atts) {
        $post_id = get_the_ID();
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        $price_info = false;
        
        if (function_exists('parfume_reviews_get_lowest_price')) {
            $price_info = parfume_reviews_get_lowest_price($post_id);
        }
        
        $output = '<div class="parfume-card">';
        $output .= '<a href="' . get_permalink() . '" class="parfume-card-link">';
        
        // Thumbnail
        if (has_post_thumbnail()) {
            $output .= '<div class="parfume-thumbnail">';
            $output .= get_the_post_thumbnail(null, 'medium');
            $output .= '</div>';
        }
        
        $output .= '<div class="parfume-info">';
        $output .= '<h4 class="parfume-title">' . get_the_title() . '</h4>';
        
        // Brand
        $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
        if (!empty($brands) && !is_wp_error($brands)) {
            $output .= '<div class="parfume-brand">' . esc_html(implode(', ', $brands)) . '</div>';
        }
        
        // Rating
        if (filter_var($atts['show_rating'], FILTER_VALIDATE_BOOLEAN) && !empty($rating)) {
            $output .= '<div class="parfume-rating">';
            if (function_exists('parfume_reviews_get_rating_stars')) {
                $output .= parfume_reviews_get_rating_stars($rating);
            }
            $output .= '<span class="rating-number">' . $rating . '/10</span>';
            $output .= '</div>';
        }
        
        // Price
        if (filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN) && $price_info) {
            $output .= '<div class="parfume-price">';
            $output .= '<span class="price">' . esc_html($price_info['price']) . '</span>';
            if (!empty($price_info['store'])) {
                $output .= '<span class="store">' . __('at', 'parfume-reviews') . ' ' . esc_html($price_info['store']) . '</span>';
            }
            $output .= '</div>';
        }
        
        // Excerpt
        if (filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN)) {
            $output .= '<div class="parfume-excerpt">' . wp_trim_words(get_the_excerpt(), 20) . '</div>';
        }
        
        $output .= '</div>'; // .parfume-info
        $output .= '</a>'; // .parfume-card-link
        $output .= '</div>'; // .parfume-card
        
        return $output;
    }
    
    /**
     * Shortcode за филтри - може да се използва на всяка страница
     */
    public function parfume_filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_brands' => true,
            'show_gender' => true,
            'show_aroma_type' => true,
            'show_season' => true,
            'show_intensity' => true,
            'show_price' => true,
            'show_rating' => true,
            'form_action' => '',
        ), $atts);
        
        // Ако няма зададено action, използваме текущата страница
        if (empty($atts['form_action'])) {
            if (is_post_type_archive('parfume')) {
                $atts['form_action'] = get_post_type_archive_link('parfume');
            } elseif (is_tax()) {
                $atts['form_action'] = get_term_link(get_queried_object());
            } else {
                $atts['form_action'] = get_permalink();
            }
        }
        
        ob_start();
        ?>
        <form class="parfume-filters-form" method="GET" action="<?php echo esc_url($atts['form_action']); ?>">
            
            <?php if (filter_var($atts['show_brands'], FILTER_VALIDATE_BOOLEAN)): ?>
            <div class="filter-group">
                <label><?php _e('Brand', 'parfume-reviews'); ?></label>
                <select name="marki">
                    <option value=""><?php _e('All Brands', 'parfume-reviews'); ?></option>
                    <?php
                    $brands = get_terms(array('taxonomy' => 'marki', 'hide_empty' => false));
                    if (!is_wp_error($brands)) {
                        foreach ($brands as $brand):
                        ?>
                            <option value="<?php echo esc_attr($brand->slug); ?>" <?php selected($_GET['marki'] ?? '', $brand->slug); ?>>
                                <?php echo esc_html($brand->name); ?>
                            </option>
                        <?php 
                        endforeach;
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (filter_var($atts['show_gender'], FILTER_VALIDATE_BOOLEAN)): ?>
            <div class="filter-group">
                <label><?php _e('Gender', 'parfume-reviews'); ?></label>
                <select name="gender">
                    <option value=""><?php _e('All Genders', 'parfume-reviews'); ?></option>
                    <?php
                    $genders = get_terms(array('taxonomy' => 'gender', 'hide_empty' => false));
                    if (!is_wp_error($genders)) {
                        foreach ($genders as $gender):
                        ?>
                            <option value="<?php echo esc_attr($gender->slug); ?>" <?php selected($_GET['gender'] ?? '', $gender->slug); ?>>
                                <?php echo esc_html($gender->name); ?>
                            </option>
                        <?php 
                        endforeach;
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN)): ?>
            <div class="filter-group">
                <label><?php _e('Price Range', 'parfume-reviews'); ?></label>
                <div class="price-range">
                    <input type="number" name="min_price" placeholder="<?php _e('Min', 'parfume-reviews'); ?>" value="<?php echo esc_attr($_GET['min_price'] ?? ''); ?>">
                    <span>-</span>
                    <input type="number" name="max_price" placeholder="<?php _e('Max', 'parfume-reviews'); ?>" value="<?php echo esc_attr($_GET['max_price'] ?? ''); ?>">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="filter-submit">
                <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'parfume-reviews'); ?></button>
                <a href="<?php echo esc_url($atts['form_action']); ?>" class="button"><?php _e('Clear', 'parfume-reviews'); ?></a>
            </div>
            
        </form>
        <?php
        return ob_get_clean();
    }
}