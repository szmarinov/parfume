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
            <?php if ($show_average): ?>
                <div class="rating-average"><?php echo $rating; ?>/5</div>
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
            'show_brand' => true,
            'show_type' => true,
            'show_season' => true,
            'show_notes' => true,
        ), $atts);
        
        // Convert string values to boolean
        foreach ($atts as $key => $value) {
            $atts[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        ob_start();
        ?>
        <div class="parfume-details">
            <?php if ($atts['show_brand']): 
                $brands = wp_get_post_terms($post->ID, 'marki');
                if (!empty($brands) && !is_wp_error($brands)): ?>
                    <div class="detail-item">
                        <strong><?php _e('Марка:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($brands[0]->name); ?>
                    </div>
                <?php endif;
            endif; ?>
            
            <?php if ($atts['show_type']): 
                $types = wp_get_post_terms($post->ID, 'aroma-type');
                if (!empty($types) && !is_wp_error($types)): ?>
                    <div class="detail-item">
                        <strong><?php _e('Тип аромат:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($types[0]->name); ?>
                    </div>
                <?php endif;
            endif; ?>
            
            <?php if ($atts['show_season']): 
                $seasons = wp_get_post_terms($post->ID, 'season');
                if (!empty($seasons) && !is_wp_error($seasons)): ?>
                    <div class="detail-item">
                        <strong><?php _e('Сезон:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($seasons[0]->name); ?>
                    </div>
                <?php endif;
            endif; ?>
            
            <?php if ($atts['show_notes']): 
                $notes = wp_get_post_terms($post->ID, 'notes');
                if (!empty($notes) && !is_wp_error($notes)): ?>
                    <div class="detail-item">
                        <strong><?php _e('Ноти:', 'parfume-reviews'); ?></strong>
                        <?php 
                        $note_names = array_map(function($note) { return $note->name; }, $notes);
                        echo esc_html(implode(', ', $note_names));
                        ?>
                    </div>
                <?php endif;
            endif; ?>
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
            'show_prices' => true,
            'show_logos' => true,
            'limit' => 0,
        ), $atts);
        
        // Convert string values to boolean
        $show_prices = filter_var($atts['show_prices'], FILTER_VALIDATE_BOOLEAN);
        $show_logos = filter_var($atts['show_logos'], FILTER_VALIDATE_BOOLEAN);
        $limit = intval($atts['limit']);
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (empty($stores) || !is_array($stores)) {
            return '<p>' . __('Няма налични магазини.', 'parfume-reviews') . '</p>';
        }
        
        if ($limit > 0) {
            $stores = array_slice($stores, 0, $limit);
        }
        
        ob_start();
        ?>
        <div class="parfume-stores">
            <?php foreach ($stores as $store): ?>
                <div class="store-item">
                    <?php if ($show_logos && !empty($store['logo'])): ?>
                        <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" class="store-logo">
                    <?php endif; ?>
                    <div class="store-info">
                        <h4><?php echo esc_html($store['name']); ?></h4>
                        <?php if ($show_prices && !empty($store['price'])): ?>
                            <div class="store-price"><?php echo esc_html($store['price']); ?></div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($store['url']); ?>" target="_blank" rel="noopener" class="store-link">
                            <?php _e('Купи сега', 'parfume-reviews'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
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
            <form method="get" id="parfume-filters-form">
                
                <?php if ($atts['show_gender']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Категории', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="gender[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <?php
                            $genders = get_terms(array(
                                'taxonomy' => 'gender',
                                'hide_empty' => false,
                            ));
                            
                            $selected_genders = isset($_GET['gender']) ? (array) $_GET['gender'] : array();
                            
                            if (!empty($genders) && !is_wp_error($genders)) {
                                foreach ($genders as $gender): 
                                    if (is_object($gender) && isset($gender->slug, $gender->name, $gender->count)): ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="gender[]" value="<?php echo esc_attr($gender->slug); ?>" <?php echo in_array($gender->slug, $selected_genders) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($gender->name); ?> (<?php echo $gender->count; ?>)
                                        </label>
                                    <?php endif;
                                endforeach;
                            } ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_brand']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title collapsed">
                            <span class="toggle-arrow">▶</span>
                            <?php _e('Марки', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options" style="display: none;">
                            <input type="text" class="filter-search" placeholder="<?php _e('Търсене в марките...', 'parfume-reviews'); ?>">
                            <label class="filter-option">
                                <input type="checkbox" name="marki[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $brands = get_terms(array(
                                    'taxonomy' => 'marki',
                                    'hide_empty' => false,
                                    'number' => 50,
                                ));
                                
                                $selected_brands = isset($_GET['marki']) ? (array) $_GET['marki'] : array();
                                
                                if (!empty($brands) && !is_wp_error($brands)) {
                                    foreach ($brands as $brand): 
                                        if (is_object($brand) && isset($brand->slug, $brand->name, $brand->count)): ?>
                                            <label class="filter-option">
                                                <input type="checkbox" name="marki[]" value="<?php echo esc_attr($brand->slug); ?>" <?php echo in_array($brand->slug, $selected_brands) ? 'checked' : ''; ?>>
                                                <?php echo esc_html($brand->name); ?> (<?php echo $brand->count; ?>)
                                            </label>
                                        <?php endif;
                                    endforeach;
                                } ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_aroma_type']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title collapsed">
                            <span class="toggle-arrow">▶</span>
                            <?php _e('Тип аромат', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options" style="display: none;">
                            <?php
                            $types = get_terms(array(
                                'taxonomy' => 'aroma_type', // ПОПРАВЕНО: използваме aroma_type вместо aroma-type
                                'hide_empty' => false,
                            ));
                            
                            $selected_types = isset($_GET['aroma_type']) ? (array) $_GET['aroma_type'] : array(); // ПОПРАВЕНО
                            
                            if (!empty($types) && !is_wp_error($types)) {
                                foreach ($types as $type): 
                                    if (is_object($type) && isset($type->slug, $type->name, $type->count)): ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="aroma_type[]" value="<?php echo esc_attr($type->slug); ?>" <?php echo in_array($type->slug, $selected_types) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                                        </label>
                                    <?php endif;
                                endforeach;
                            } ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_season']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title collapsed">
                            <span class="toggle-arrow">▶</span>
                            <?php _e('Сезон', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options" style="display: none;">
                            <?php
                            $seasons = get_terms(array(
                                'taxonomy' => 'season',
                                'hide_empty' => false,
                            ));
                            
                            $selected_seasons = isset($_GET['season']) ? (array) $_GET['season'] : array();
                            
                            if (!empty($seasons) && !is_wp_error($seasons)) {
                                foreach ($seasons as $season): 
                                    if (is_object($season) && isset($season->slug, $season->name, $season->count)): ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="season[]" value="<?php echo esc_attr($season->slug); ?>" <?php echo in_array($season->slug, $selected_seasons) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($season->name); ?> (<?php echo $season->count; ?>)
                                        </label>
                                    <?php endif;
                                endforeach;
                            } ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_notes']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title collapsed">
                            <span class="toggle-arrow">▶</span>
                            <?php _e('Ароматни нотки', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options" style="display: none;">
                            <input type="text" class="filter-search" placeholder="<?php _e('Търсене в нотките...', 'parfume-reviews'); ?>">
                            <label class="filter-option">
                                <input type="checkbox" name="notes[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $notes = get_terms(array(
                                    'taxonomy' => 'notes',
                                    'hide_empty' => false,
                                    'number' => 50,
                                ));
                                
                                $selected_notes = isset($_GET['notes']) ? (array) $_GET['notes'] : array();
                                
                                if (!empty($notes) && !is_wp_error($notes)) {
                                    foreach ($notes as $note): 
                                        if (is_object($note) && isset($note->slug, $note->name, $note->count)): ?>
                                            <label class="filter-option">
                                                <input type="checkbox" name="notes[]" value="<?php echo esc_attr($note->slug); ?>" <?php echo in_array($note->slug, $selected_notes) ? 'checked' : ''; ?>>
                                                <?php echo esc_html($note->name); ?> (<?php echo $note->count; ?>)
                                            </label>
                                        <?php endif;
                                    endforeach;
                                } ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_perfumer']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title collapsed">
                            <span class="toggle-arrow">▶</span>
                            <?php _e('Парфюмеристи', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options" style="display: none;">
                            <input type="text" class="filter-search" placeholder="<?php _e('Търсене в парфюмеристите...', 'parfume-reviews'); ?>">
                            <?php
                            $perfumers = get_terms(array(
                                'taxonomy' => 'perfumer', // ПОПРАВЕНО: използваме perfumer вместо parfumeri
                                'hide_empty' => false,
                                'number' => 30,
                            ));
                            
                            $selected_perfumers = isset($_GET['perfumer']) ? (array) $_GET['perfumer'] : array(); // ПОПРАВЕНО
                            
                            if (!empty($perfumers) && !is_wp_error($perfumers)) {
                                foreach ($perfumers as $perfumer): 
                                    if (is_object($perfumer) && isset($perfumer->slug, $perfumer->name, $perfumer->count)): ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="perfumer[]" value="<?php echo esc_attr($perfumer->slug); ?>" <?php echo in_array($perfumer->slug, $selected_perfumers) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($perfumer->name); ?> (<?php echo $perfumer->count; ?>)
                                        </label>
                                    <?php endif;
                                endforeach;
                            } ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="filter-submit">
                    <button type="submit" class="filter-button"><?php _e('Филтрирай', 'parfume-reviews'); ?></button>
                    <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="reset-button"><?php _e('Изчисти филтрите', 'parfume-reviews'); ?></a>
                </div>
            </form>
        </div>
        
        <style>
        .parfume-filters {
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filter-group {
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .filter-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 10px 0;
            margin: 0 0 10px;
            font-weight: 600;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #e1e5e9;
            transition: color 0.3s ease;
        }
        
        .filter-title:hover {
            color: #0073aa;
        }
        
        .filter-title.collapsed {
            margin-bottom: 0;
        }
        
        .toggle-arrow {
            font-size: 12px;
            transition: transform 0.3s ease;
            color: #666;
        }
        
        .filter-title.collapsed .toggle-arrow {
            transform: rotate(0deg);
        }
        
        .filter-options {
            padding-left: 20px;
        }
        
        .filter-search {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .filter-option {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .filter-option input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .scrollable-options {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            background: white;
        }
        
        .filter-submit {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .filter-button {
            background: linear-gradient(135deg, #0073aa, #005a87);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,115,170,0.3);
        }
        
        .filter-button:hover {
            background: linear-gradient(135deg, #005a87, #004466);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,115,170,0.4);
        }
        
        .reset-button {
            background: linear-gradient(135deg, #6c757d, #545b62);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(108,117,125,0.3);
        }
        
        .reset-button:hover {
            background: linear-gradient(135deg, #545b62, #3d4449);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(108,117,125,0.4);
        }
        </style>
        
        <?php
        // ПРЕМАХНАТ ДУБЛИРАН JAVASCRIPT КОД - функционалността се управлява от assets/js/filters.js
        return ob_get_clean();
    }
    
    public function similar_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 4,
            'title' => __('Подобни парфюми', 'parfume-reviews'),
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
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit + 1, // +1 to exclude current post
            'post__not_in' => array($post->ID),
            'meta_query' => array('relation' => 'OR'),
        );
        
        if (!empty($brands) || !empty($notes) || !empty($genders)) {
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
            
            $args['tax_query'] = $tax_query;
        } else {
            // If no taxonomies, show recent perfumes
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
        
        $similar_query = new \WP_Query($args);
        
        if (!$similar_query->have_posts()) {
            return '<p>' . __('Няма намерени подобни парфюми.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="similar-parfumes">
            <?php if (!empty($atts['title'])): ?>
                <h3><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            <div class="similar-grid">
                <?php while ($similar_query->have_posts()): $similar_query->the_post(); ?>
                    <div class="similar-item">
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium'); ?>
                            <?php endif; ?>
                            <h4><?php the_title(); ?></h4>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    public function brand_products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'count' => 12,
            'columns' => 4,
            'exclude' => '',
        ), $atts);
        
        if (empty($atts['brand'])) {
            return '<p>' . __('Моля посочете марка.', 'parfume-reviews') . '</p>';
        }
        
        $exclude_ids = !empty($atts['exclude']) ? explode(',', $atts['exclude']) : array();
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => intval($atts['count']),
            'tax_query' => array(
                array(
                    'taxonomy' => 'marki',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts['brand']),
                ),
            ),
        );
        
        if (!empty($exclude_ids)) {
            $args['post__not_in'] = array_map('intval', $exclude_ids);
        }
        
        $brand_query = new \WP_Query($args);
        
        if (!$brand_query->have_posts()) {
            return '<p>' . __('Няма намерени продукти от тази марка.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="brand-products columns-<?php echo intval($atts['columns']); ?>">
            <?php while ($brand_query->have_posts()): $brand_query->the_post(); ?>
                <div class="product-item">
                    <a href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php endif; ?>
                        <h4><?php the_title(); ?></h4>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    public function recently_viewed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'title' => __('Последно разгледани', 'parfume-reviews'),
            'show_empty' => false,
        ), $atts);
        
        // Get recently viewed from cookies or session
        $viewed_ids = array();
        if (isset($_COOKIE['parfume_recently_viewed'])) {
            $viewed_ids = explode(',', $_COOKIE['parfume_recently_viewed']);
            $viewed_ids = array_map('intval', $viewed_ids);
            $viewed_ids = array_filter($viewed_ids);
        }
        
        if (empty($viewed_ids)) {
            if (filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN)) {
                return '<p>' . __('Още не сте разглеждали парфюми.', 'parfume-reviews') . '</p>';
            }
            return '';
        }
        
        $args = array(
            'post_type' => 'parfume',
            'post__in' => array_slice($viewed_ids, 0, intval($atts['count'])),
            'orderby' => 'post__in',
            'posts_per_page' => intval($atts['count']),
        );
        
        $viewed_query = new \WP_Query($args);
        
        if (!$viewed_query->have_posts()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="recently-viewed">
            <?php if (!empty($atts['title'])): ?>
                <h3><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            <div class="viewed-grid">
                <?php while ($viewed_query->have_posts()): $viewed_query->the_post(); ?>
                    <div class="viewed-item">
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('thumbnail'); ?>
                            <?php endif; ?>
                            <h4><?php the_title(); ?></h4>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}