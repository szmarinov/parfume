<?php
namespace Parfume_Reviews;

/**
 * Shortcodes class - управлява всички shortcodes на плъгина
 * 
 * Файл: includes/class-shortcodes.php
 * РЕВИЗИРАНА ВЕРСИЯ - ПЪЛЕН НАБОР ОТ SHORTCODES С ПОДОБРЕНА ФУНКЦИОНАЛНОСТ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcodes класа
 * ВАЖНО: Управлява всички shortcodes на плъгина за показване на парфюми и свързани данни
 */
class Shortcodes {
    
    /**
     * Constructor
     * ВАЖНО: Регистрира всички shortcodes на плъгина
     */
    public function __construct() {
        $this->register_shortcodes();
        $this->init_hooks();
    }
    
    /**
     * Регистрира всички shortcodes
     * ВАЖНО: Централно място за регистрация на всички shortcodes
     */
    private function register_shortcodes() {
        // Основни shortcodes за парфюми
        add_shortcode('parfume_grid', array($this, 'parfume_grid_shortcode'));
        add_shortcode('parfume_list', array($this, 'parfume_list_shortcode'));
        add_shortcode('latest_parfumes', array($this, 'latest_parfumes_shortcode'));
        add_shortcode('featured_parfumes', array($this, 'featured_parfumes_shortcode'));
        add_shortcode('top_rated_parfumes', array($this, 'top_rated_parfumes_shortcode'));
        add_shortcode('random_parfumes', array($this, 'random_parfumes_shortcode'));
        
        // Shortcodes за детайли на парфюм
        add_shortcode('parfume_rating', array($this, 'rating_shortcode'));
        add_shortcode('parfume_details', array($this, 'details_shortcode'));
        add_shortcode('parfume_stores', array($this, 'stores_shortcode'));
        add_shortcode('parfume_notes', array($this, 'notes_shortcode'));
        add_shortcode('parfume_similar', array($this, 'similar_shortcode'));
        
        // Shortcodes за таксономии
        add_shortcode('all_brands_archive', array($this, 'all_brands_archive_shortcode'));
        add_shortcode('all_notes_archive', array($this, 'all_notes_archive_shortcode'));
        add_shortcode('all_perfumers_archive', array($this, 'all_perfumers_archive_shortcode'));
        add_shortcode('brand_parfumes', array($this, 'brand_parfumes_shortcode'));
        
        // Shortcodes за филтри и функционалност
        add_shortcode('parfume_filters', array($this, 'filters_shortcode'));
        add_shortcode('parfume_search', array($this, 'search_shortcode'));
        add_shortcode('parfume_comparison', array($this, 'comparison_shortcode'));
        
        // Shortcodes за статистики
        add_shortcode('parfume_stats', array($this, 'stats_shortcode'));
        add_shortcode('popular_brands', array($this, 'popular_brands_shortcode'));
        add_shortcode('popular_notes', array($this, 'popular_notes_shortcode'));
        
        // Legacy shortcodes за backward compatibility
        add_shortcode('parfume_brand_products', array($this, 'brand_parfumes_shortcode')); // псевдоним
        add_shortcode('parfume_recently_viewed', array($this, 'recently_viewed_shortcode'));
    }
    
    /**
     * Инициализира hook-ове
     */
    private function init_hooks() {
        // Enqueue styles за shortcodes
        add_action('wp_enqueue_scripts', array($this, 'enqueue_shortcode_styles'));
        
        // Add shortcode support to widgets
        add_filter('widget_text', 'do_shortcode');
    }
    
    /**
     * РАЗДЕЛ 1: ОСНОВНИ PARFUME SHORTCODES
     */
    
    /**
     * [parfume_grid] - Показва парфюми в grid формат
     * ВАЖНО: Основен shortcode за показване на парфюми
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
            'notes' => '',
            'perfumer' => '',
            'show_rating' => true,
            'show_price' => true,
            'show_excerpt' => false,
            'show_filters' => false,
            'featured_only' => false,
            'min_rating' => '',
            'class' => '',
            'title' => ''
        ), $atts, 'parfume_grid');
        
        // Build WP_Query arguments
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'post_status' => 'publish',
        );
        
        // Meta query
        $meta_query = array();
        
        if (!empty($atts['meta_key']) && !empty($atts['meta_value'])) {
            $meta_query[] = array(
                'key' => sanitize_text_field($atts['meta_key']),
                'value' => sanitize_text_field($atts['meta_value']),
                'compare' => '='
            );
        }
        
        if ($atts['featured_only'] === 'true' || $atts['featured_only'] === true) {
            $meta_query[] = array(
                'key' => '_parfume_featured',
                'value' => '1',
                'compare' => '='
            );
        }
        
        if (!empty($atts['min_rating'])) {
            $meta_query[] = array(
                'key' => '_parfume_rating',
                'value' => floatval($atts['min_rating']),
                'type' => 'DECIMAL',
                'compare' => '>='
            );
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        // Tax query
        $tax_query = array();
        $taxonomies = array(
            'brand' => 'marki', 
            'gender' => 'gender', 
            'aroma_type' => 'aroma_type', 
            'season' => 'season', 
            'intensity' => 'intensity',
            'notes' => 'notes',
            'perfumer' => 'perfumer'
        );
        
        foreach ($taxonomies as $att_key => $taxonomy) {
            if (!empty($atts[$att_key])) {
                $terms = explode(',', $atts[$att_key]);
                $terms = array_map('trim', $terms);
                $terms = array_map('sanitize_text_field', $terms);
                
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN'
                );
            }
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Execute query
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<div class="no-parfumes-found"><p>' . __('Няма намерени парфюми.', 'parfume-reviews') . '</p></div>';
        }
        
        // Build output
        ob_start();
        
        $container_class = 'parfume-grid-container';
        if (!empty($atts['class'])) {
            $container_class .= ' ' . sanitize_html_class($atts['class']);
        }
        
        echo '<div class="' . esc_attr($container_class) . '">';
        
        // Title
        if (!empty($atts['title'])) {
            echo '<h2 class="parfume-grid-title">' . esc_html($atts['title']) . '</h2>';
        }
        
        // Filters
        if ($atts['show_filters'] === 'true' || $atts['show_filters'] === true) {
            echo $this->render_inline_filters();
        }
        
        // Grid
        $grid_class = 'parfume-grid columns-' . intval($atts['columns']);
        echo '<div class="' . esc_attr($grid_class) . '" data-columns="' . esc_attr($atts['columns']) . '">';
        
        while ($query->have_posts()) {
            $query->the_post();
            $this->render_parfume_card(get_the_ID(), $atts);
        }
        
        echo '</div>';
        
        // Pagination за големи списъци
        if ($query->max_num_pages > 1 && intval($atts['posts_per_page']) > 10) {
            echo '<div class="parfume-grid-pagination">';
            echo paginate_links(array(
                'total' => $query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
                'format' => '?paged=%#%',
                'prev_text' => '‹ ' . __('Предишна', 'parfume-reviews'),
                'next_text' => __('Следваща', 'parfume-reviews') . ' ›'
            ));
            echo '</div>';
        }
        
        echo '</div>';
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * [parfume_list] - Показва парфюми в list формат
     */
    public function parfume_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'show_excerpt' => true,
            'show_meta' => true,
            'excerpt_length' => 100
        ), $atts, 'parfume_list');
        
        // Използваме grid функцията с модифицирани атрибути
        $atts['columns'] = 1;
        $atts['show_excerpt'] = true;
        $atts['class'] = 'parfume-list-style';
        
        return $this->parfume_grid_shortcode($atts);
    }
    
    /**
     * [latest_parfumes] - Показва най-новите парфюми
     */
    public function latest_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'columns' => 3,
            'title' => __('Най-нови парфюми', 'parfume-reviews')
        ), $atts, 'latest_parfumes');
        
        $modified_atts = array(
            'posts_per_page' => intval($atts['limit']),
            'columns' => intval($atts['columns']),
            'orderby' => 'date',
            'order' => 'DESC',
            'title' => $atts['title'],
            'class' => 'latest-parfumes'
        );
        
        return $this->parfume_grid_shortcode($modified_atts);
    }
    
    /**
     * [featured_parfumes] - Показва препоръчани парфюми
     */
    public function featured_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'columns' => 3,
            'title' => __('Препоръчани парфюми', 'parfume-reviews')
        ), $atts, 'featured_parfumes');
        
        $modified_atts = array(
            'posts_per_page' => intval($atts['limit']),
            'columns' => intval($atts['columns']),
            'featured_only' => true,
            'title' => $atts['title'],
            'class' => 'featured-parfumes'
        );
        
        return $this->parfume_grid_shortcode($modified_atts);
    }
    
    /**
     * [top_rated_parfumes] - Показва най-високо оценените парфюми
     */
    public function top_rated_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'columns' => 3,
            'min_rating' => 4.0,
            'title' => __('Най-високо оценени', 'parfume-reviews')
        ), $atts, 'top_rated_parfumes');
        
        $modified_atts = array(
            'posts_per_page' => intval($atts['limit']),
            'columns' => intval($atts['columns']),
            'orderby' => 'meta_value_num',
            'meta_key' => '_parfume_rating',
            'order' => 'DESC',
            'min_rating' => floatval($atts['min_rating']),
            'title' => $atts['title'],
            'class' => 'top-rated-parfumes'
        );
        
        return $this->parfume_grid_shortcode($modified_atts);
    }
    
    /**
     * [random_parfumes] - Показва случайни парфюми
     */
    public function random_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'columns' => 3,
            'title' => __('Открийте нещо ново', 'parfume-reviews')
        ), $atts, 'random_parfumes');
        
        $modified_atts = array(
            'posts_per_page' => intval($atts['limit']),
            'columns' => intval($atts['columns']),
            'orderby' => 'rand',
            'title' => $atts['title'],
            'class' => 'random-parfumes'
        );
        
        return $this->parfume_grid_shortcode($modified_atts);
    }
    
    /**
     * РАЗДЕЛ 2: PARFUME DETAILS SHORTCODES
     */
    
    /**
     * [parfume_rating] - Показва рейтинг на парфюм
     * ВАЖНО: Използва се в single templates
     */
    public function rating_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
            'show_average' => true,
            'show_count' => false,
            'size' => 'normal' // normal, large, small
        ), $atts, 'parfume_rating');
        
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        $show_average = filter_var($atts['show_average'], FILTER_VALIDATE_BOOLEAN);
        $show_count = filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN);
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $rating = !empty($rating) ? floatval($rating) : 0;
        
        if (empty($rating) && !$show_empty) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-rating rating-size-<?php echo esc_attr($atts['size']); ?>">
            <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?php echo $i <= round($rating) ? 'filled' : 'empty'; ?>">★</span>
                <?php endfor; ?>
            </div>
            
            <?php if ($show_average && $rating > 0): ?>
                <span class="rating-average"><?php echo number_format($rating, 1); ?>/5</span>
            <?php endif; ?>
            
            <?php if ($show_count): ?>
                <?php 
                $review_count = get_post_meta($post->ID, '_parfume_review_count', true);
                if (!empty($review_count)):
                ?>
                    <span class="rating-count">(<?php echo intval($review_count); ?> <?php _e('отзива', 'parfume-reviews'); ?>)</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * [parfume_details] - Показва детайли на парфюм
     */
    public function details_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'fields' => 'brand,year,concentration,longevity,sillage',
            'layout' => 'table' // table, list, inline
        ), $atts, 'parfume_details');
        
        $fields = explode(',', $atts['fields']);
        $fields = array_map('trim', $fields);
        
        $field_labels = array(
            'brand' => __('Марка', 'parfume-reviews'),
            'year' => __('Година', 'parfume-reviews'),
            'concentration' => __('Концентрация', 'parfume-reviews'),
            'longevity' => __('Дълготрайност', 'parfume-reviews'),
            'sillage' => __('Прожекция', 'parfume-reviews'),
            'bottle_size' => __('Размер на бутилката', 'parfume-reviews'),
            'availability' => __('Наличност', 'parfume-reviews')
        );
        
        ob_start();
        
        echo '<div class="parfume-details layout-' . esc_attr($atts['layout']) . '">';
        
        if ($atts['layout'] === 'table') {
            echo '<table class="details-table">';
        } elseif ($atts['layout'] === 'list') {
            echo '<ul class="details-list">';
        } else {
            echo '<div class="details-inline">';
        }
        
        foreach ($fields as $field) {
            $value = $this->get_parfume_field_value($post->ID, $field);
            if (empty($value)) continue;
            
            $label = $field_labels[$field] ?? ucfirst($field);
            
            if ($atts['layout'] === 'table') {
                echo '<tr><td class="label">' . esc_html($label) . ':</td><td class="value">' . esc_html($value) . '</td></tr>';
            } elseif ($atts['layout'] === 'list') {
                echo '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
            } else {
                echo '<span class="detail-item"><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</span>';
            }
        }
        
        if ($atts['layout'] === 'table') {
            echo '</table>';
        } elseif ($atts['layout'] === 'list') {
            echo '</ul>';
        } else {
            echo '</div>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * [parfume_stores] - Показва магазини
     */
    public function stores_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
            'limit' => 0,
            'show_logos' => true,
            'show_prices' => true,
            'title' => __('Къде да купите', 'parfume-reviews')
        ), $atts, 'parfume_stores');
        
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        $show_logos = filter_var($atts['show_logos'], FILTER_VALIDATE_BOOLEAN);
        $show_prices = filter_var($atts['show_prices'], FILTER_VALIDATE_BOOLEAN);
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        if (empty($stores) && !$show_empty) {
            return '';
        }
        
        if ($atts['limit'] > 0) {
            $stores = array_slice($stores, 0, intval($atts['limit']));
        }
        
        ob_start();
        ?>
        <div class="parfume-stores-sidebar">
            <?php if (!empty($atts['title'])): ?>
                <h3 class="stores-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <?php if (!empty($stores)): ?>
                <div class="store-list">
                    <?php foreach ($stores as $store): ?>
                        <?php if (empty($store['name'])) continue; ?>
                        <div class="store-item">
                            <div class="store-info">
                                <?php if ($show_logos && !empty($store['logo'])): ?>
                                    <div class="store-logo">
                                        <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="store-details">
                                    <div class="store-name">
                                        <?php if (!empty($store['url'])): ?>
                                            <a href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" 
                                               target="_blank" 
                                               rel="<?php echo esc_attr($store['affiliate_rel'] ?: 'nofollow'); ?>">
                                                <?php echo esc_html($store['name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo esc_html($store['name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($show_prices && !empty($store['price'])): ?>
                                        <div class="store-price">
                                            <?php echo esc_html($store['price']); ?>
                                            <?php if (!empty($store['size'])): ?>
                                                <span class="price-size">(<?php echo esc_html($store['size']); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($store['availability'])): ?>
                                        <div class="store-availability status-<?php echo esc_attr($store['availability']); ?>">
                                            <?php echo esc_html($this->format_availability($store['availability'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-stores"><?php _e('Няма налични магазини в момента.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * [parfume_notes] - Показва нотки на парфюм
     */
    public function notes_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_groups' => true,
            'group_by' => 'position', // position, type
            'show_empty' => false
        ), $atts, 'parfume_notes');
        
        $notes = wp_get_post_terms($post->ID, 'notes');
        
        if (empty($notes) || is_wp_error($notes)) {
            if (!filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN)) {
                return '';
            }
            return '<div class="parfume-notes"><p>' . __('Няма дефинирани нотки.', 'parfume-reviews') . '</p></div>';
        }
        
        ob_start();
        
        echo '<div class="parfume-notes">';
        
        if ($atts['group_by'] === 'position') {
            $this->render_notes_by_position($post->ID);
        } else {
            $this->render_notes_by_type($notes);
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * РАЗДЕЛ 3: TAXONOMY SHORTCODES
     */
    
    /**
     * [all_brands_archive] - Показва всички марки
     */
    public function all_brands_archive_shortcode($atts) {
        return $this->render_taxonomy_archive('marki', $atts, array(
            'columns' => 4,
            'show_count' => true,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
            'show_images' => true,
            'title' => __('Всички марки', 'parfume-reviews')
        ));
    }
    
    /**
     * [all_notes_archive] - Показва всички нотки
     */
    public function all_notes_archive_shortcode($atts) {
        return $this->render_taxonomy_archive('notes', $atts, array(
            'columns' => 6,
            'show_count' => true,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
            'show_images' => true,
            'title' => __('Всички нотки', 'parfume-reviews')
        ));
    }
    
    /**
     * [all_perfumers_archive] - Показва всички парфюмьори
     */
    public function all_perfumers_archive_shortcode($atts) {
        return $this->render_taxonomy_archive('perfumer', $atts, array(
            'columns' => 3,
            'show_count' => true,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
            'show_images' => true,
            'title' => __('Всички парфюмьори', 'parfume-reviews')
        ));
    }
    
    /**
     * [brand_parfumes] - Показва парфюми от конкретна марка
     */
    public function brand_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'limit' => 12,
            'columns' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
            'title' => ''
        ), $atts, 'brand_parfumes');
        
        if (empty($atts['brand'])) {
            return '<p>' . __('Моля посочете марка.', 'parfume-reviews') . '</p>';
        }
        
        if (empty($atts['title'])) {
            $brand_term = get_term_by('slug', $atts['brand'], 'marki');
            if ($brand_term) {
                $atts['title'] = sprintf(__('Парфюми от %s', 'parfume-reviews'), $brand_term->name);
            }
        }
        
        $modified_atts = array(
            'posts_per_page' => intval($atts['limit']),
            'columns' => intval($atts['columns']),
            'brand' => $atts['brand'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'title' => $atts['title'],
            'class' => 'brand-parfumes'
        );
        
        return $this->parfume_grid_shortcode($modified_atts);
    }
    
    /**
     * РАЗДЕЛ 4: FILTER SHORTCODES
     */
    
    /**
     * [parfume_filters] - Показва филтри за парфюми
     */
    public function filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_gender' => true,
            'show_aroma_type' => true,
            'show_brand' => true,
            'show_season' => true,
            'show_intensity' => true,
            'show_notes' => false,
            'show_perfumer' => false,
            'show_price_range' => true,
            'show_rating' => true,
            'show_search' => true,
            'layout' => 'vertical', // vertical, horizontal, compact
            'ajax' => false
        ), $atts, 'parfume_filters');
        
        // Convert string values to boolean
        foreach ($atts as $key => $value) {
            if (in_array($key, array('show_gender', 'show_aroma_type', 'show_brand', 'show_season', 'show_intensity', 'show_notes', 'show_perfumer', 'show_price_range', 'show_rating', 'show_search', 'ajax'))) {
                $atts[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }
        
        ob_start();
        
        echo '<div class="parfume-filters layout-' . esc_attr($atts['layout']) . '">';
        echo '<form method="get" class="parfume-filters-form" data-ajax="' . ($atts['ajax'] ? 'true' : 'false') . '">';
        
        // Search field
        if ($atts['show_search']) {
            echo '<div class="filter-group search-group">';
            echo '<input type="text" name="search" placeholder="' . esc_attr__('Търси парфюм...', 'parfume-reviews') . '" value="' . esc_attr($_GET['search'] ?? '') . '">';
            echo '</div>';
        }
        
        // Price range
        if ($atts['show_price_range']) {
            echo '<div class="filter-group price-group">';
            echo '<label>' . __('Ценови диапазон:', 'parfume-reviews') . '</label>';
            echo '<div class="price-inputs">';
            echo '<input type="number" name="min_price" placeholder="' . esc_attr__('От', 'parfume-reviews') . '" value="' . esc_attr($_GET['min_price'] ?? '') . '" min="0" step="0.01">';
            echo '<span>-</span>';
            echo '<input type="number" name="max_price" placeholder="' . esc_attr__('До', 'parfume-reviews') . '" value="' . esc_attr($_GET['max_price'] ?? '') . '" min="0" step="0.01">';
            echo '</div>';
            echo '</div>';
        }
        
        // Rating filter
        if ($atts['show_rating']) {
            echo '<div class="filter-group rating-group">';
            echo '<label>' . __('Мин. рейтинг:', 'parfume-reviews') . '</label>';
            echo '<select name="min_rating">';
            echo '<option value="">' . __('Всички', 'parfume-reviews') . '</option>';
            for ($i = 5; $i >= 1; $i--) {
                $selected = (isset($_GET['min_rating']) && $_GET['min_rating'] == $i) ? ' selected' : '';
                echo '<option value="' . $i . '"' . $selected . '>' . sprintf(__('%d+ звезди', 'parfume-reviews'), $i) . '</option>';
            }
            echo '</select>';
            echo '</div>';
        }
        
        // Taxonomy filters
        $taxonomy_filters = array(
            'gender' => __('Пол', 'parfume-reviews'),
            'aroma_type' => __('Тип аромат', 'parfume-reviews'),
            'brand' => array('marki', __('Марка', 'parfume-reviews')),
            'season' => __('Сезон', 'parfume-reviews'),
            'intensity' => __('Интензивност', 'parfume-reviews'),
            'notes' => __('Нотки', 'parfume-reviews'),
            'perfumer' => __('Парфюмерист', 'parfume-reviews')
        );
        
        foreach ($taxonomy_filters as $filter_key => $filter_data) {
            if (!$atts['show_' . $filter_key]) continue;
            
            if (is_array($filter_data)) {
                $taxonomy = $filter_data[0];
                $label = $filter_data[1];
            } else {
                $taxonomy = $filter_key;
                $label = $filter_data;
            }
            
            $this->render_taxonomy_filter($taxonomy, $label, $atts['layout']);
        }
        
        // Submit button
        echo '<div class="filter-submit">';
        echo '<button type="submit" class="button button-primary">' . __('Филтрирай', 'parfume-reviews') . '</button>';
        
        // Reset button
        $reset_url = strtok($_SERVER['REQUEST_URI'], '?');
        echo '<a href="' . esc_url($reset_url) . '" class="button button-secondary">' . __('Изчисти', 'parfume-reviews') . '</a>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * [parfume_search] - Показва търсачка за парфюми
     */
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Търси парфюм...', 'parfume-reviews'),
            'button_text' => __('Търси', 'parfume-reviews'),
            'show_suggestions' => true,
            'ajax' => false
        ), $atts, 'parfume_search');
        
        ob_start();
        ?>
        <div class="parfume-search-form">
            <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-form">
                <input type="hidden" name="post_type" value="parfume">
                <div class="search-input-wrapper">
                    <input type="search" 
                           name="s" 
                           value="<?php echo esc_attr(get_search_query()); ?>"
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                           class="search-input"
                           data-suggestions="<?php echo $atts['show_suggestions'] ? 'true' : 'false'; ?>"
                           data-ajax="<?php echo $atts['ajax'] ? 'true' : 'false'; ?>">
                    <button type="submit" class="search-button">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                
                <?php if ($atts['show_suggestions']): ?>
                    <div class="search-suggestions" style="display: none;"></div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * РАЗДЕЛ 5: HELPER МЕТОДИ
     */
    
    /**
     * Рендва parfume карточка
     */
    private function render_parfume_card($post_id, $atts) {
        // Използваме template function ако е налична
        if (function_exists('parfume_reviews_display_parfume_card')) {
            parfume_reviews_display_parfume_card($post_id);
            return;
        }
        
        // Fallback rendering
        $post = get_post($post_id);
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        $price = get_post_meta($post_id, '_parfume_price', true);
        
        echo '<article class="parfume-card" data-post-id="' . esc_attr($post_id) . '">';
        
        // Image
        if (has_post_thumbnail($post_id)) {
            echo '<div class="parfume-image">';
            echo '<a href="' . get_permalink($post_id) . '">';
            echo get_the_post_thumbnail($post_id, 'medium');
            echo '</a>';
            echo '</div>';
        }
        
        // Content
        echo '<div class="parfume-content">';
        echo '<h3 class="parfume-title"><a href="' . get_permalink($post_id) . '">' . get_the_title($post_id) . '</a></h3>';
        
        // Rating
        if (!empty($rating) && ($atts['show_rating'] ?? true)) {
            echo '<div class="parfume-rating">';
            for ($i = 1; $i <= 5; $i++) {
                echo '<span class="star ' . ($i <= round($rating) ? 'filled' : 'empty') . '">★</span>';
            }
            echo '</div>';
        }
        
        // Price
        if (!empty($price) && ($atts['show_price'] ?? true)) {
            echo '<div class="parfume-price">' . esc_html($price) . '</div>';
        }
        
        // Excerpt
        if ($atts['show_excerpt'] ?? false) {
            echo '<div class="parfume-excerpt">' . wp_trim_words(get_the_excerpt($post_id), 15) . '</div>';
        }
        
        echo '</div>';
        echo '</article>';
    }
    
    /**
     * Получава стойност на поле за парфюм
     */
    private function get_parfume_field_value($post_id, $field) {
        switch ($field) {
            case 'brand':
                $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
                return !empty($brands) ? $brands[0] : '';
                
            case 'year':
                return get_post_meta($post_id, '_parfume_release_year', true);
                
            case 'concentration':
                return get_post_meta($post_id, '_parfume_concentration', true);
                
            case 'longevity':
                return get_post_meta($post_id, '_parfume_longevity', true);
                
            case 'sillage':
                return get_post_meta($post_id, '_parfume_sillage', true);
                
            case 'bottle_size':
                return get_post_meta($post_id, '_parfume_bottle_size', true);
                
            case 'availability':
                $availability = get_post_meta($post_id, '_parfume_availability', true);
                return $this->format_availability($availability);
                
            default:
                return get_post_meta($post_id, '_parfume_' . $field, true);
        }
    }
    
    /**
     * Форматира availability статус
     */
    private function format_availability($availability) {
        $statuses = array(
            'available' => __('Наличен', 'parfume-reviews'),
            'limited' => __('Ограничено издание', 'parfume-reviews'),
            'discontinued' => __('Спрян от производство', 'parfume-reviews')
        );
        
        return $statuses[$availability] ?? $availability;
    }
    
    /**
     * Рендва taxonomy archive
     */
    private function render_taxonomy_archive($taxonomy, $user_atts, $default_atts) {
        $atts = shortcode_atts($default_atts, $user_atts);
        
        $args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        );
        
        if ($atts['limit'] > 0) {
            $args['number'] = intval($atts['limit']);
        }
        
        $terms = get_terms($args);
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . sprintf(__('Няма намерени %s.', 'parfume-reviews'), $taxonomy) . '</p>';
        }
        
        ob_start();
        
        echo '<div class="' . esc_attr($taxonomy) . '-archive-grid columns-' . intval($atts['columns']) . '">';
        
        if (!empty($atts['title'])) {
            echo '<h2 class="archive-title">' . esc_html($atts['title']) . '</h2>';
        }
        
        echo '<div class="terms-grid">';
        
        foreach ($terms as $term) {
            echo '<div class="term-item">';
            echo '<a href="' . get_term_link($term) . '">';
            
            // Term image
            if ($atts['show_images'] && function_exists('parfume_reviews_get_term_image_url')) {
                $image_url = parfume_reviews_get_term_image_url($term->term_id, $taxonomy, 'medium');
                if ($image_url) {
                    echo '<div class="term-image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($term->name) . '"></div>';
                }
            }
            
            echo '<h3>' . esc_html($term->name) . '</h3>';
            
            if ($atts['show_count']) {
                echo '<span class="count">(' . $term->count . ')</span>';
            }
            
            echo '</a>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Рендва taxonomy filter
     */
    private function render_taxonomy_filter($taxonomy, $label, $layout) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => 50
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return;
        }
        
        $selected_values = isset($_GET[$taxonomy]) ? (array)$_GET[$taxonomy] : array();
        
        echo '<div class="filter-group ' . esc_attr($taxonomy) . '-group">';
        echo '<label class="filter-label">' . esc_html($label) . ':</label>';
        
        if ($layout === 'compact') {
            echo '<select name="' . esc_attr($taxonomy) . '[]" multiple>';
            echo '<option value="">' . __('Всички', 'parfume-reviews') . '</option>';
            foreach ($terms as $term) {
                $selected = in_array($term->slug, $selected_values) ? ' selected' : '';
                echo '<option value="' . esc_attr($term->slug) . '"' . $selected . '>' . esc_html($term->name) . ' (' . $term->count . ')</option>';
            }
            echo '</select>';
        } else {
            echo '<div class="filter-options">';
            foreach ($terms as $term) {
                $checked = in_array($term->slug, $selected_values) ? ' checked' : '';
                echo '<label class="filter-option">';
                echo '<input type="checkbox" name="' . esc_attr($taxonomy) . '[]" value="' . esc_attr($term->slug) . '"' . $checked . '>';
                echo esc_html($term->name) . ' (' . $term->count . ')';
                echo '</label>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Enqueue styles за shortcodes
     */
    public function enqueue_shortcode_styles() {
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/shortcodes.css')) {
            wp_enqueue_style(
                'parfume-reviews-shortcodes',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/shortcodes.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
        }
    }
    
    /**
     * РАЗДЕЛ 6: LEGACY SHORTCODES
     */
    
    /**
     * [parfume_recently_viewed] - Показва наскоро разгледани
     */
    public function recently_viewed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'columns' => 3,
            'title' => __('Наскоро разгледани', 'parfume-reviews')
        ), $atts, 'parfume_recently_viewed');
        
        // Implement recently viewed logic here
        // For now, return random parfumes
        return $this->random_parfumes_shortcode($atts);
    }
    
    /**
     * РАЗДЕЛ 7: STATS SHORTCODES
     */
    
    /**
     * [parfume_stats] - Показва статистики
     */
    public function stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_total' => true,
            'show_brands' => true,
            'show_notes' => true,
            'show_perfumers' => true,
            'layout' => 'grid' // grid, list, inline
        ), $atts, 'parfume_stats');
        
        ob_start();
        
        echo '<div class="parfume-stats layout-' . esc_attr($atts['layout']) . '">';
        
        if ($atts['show_total']) {
            $total_parfumes = wp_count_posts('parfume')->publish;
            echo '<div class="stat-item total-parfumes">';
            echo '<span class="stat-number">' . number_format($total_parfumes) . '</span>';
            echo '<span class="stat-label">' . __('Парфюма', 'parfume-reviews') . '</span>';
            echo '</div>';
        }
        
        if ($atts['show_brands']) {
            $total_brands = wp_count_terms('marki', array('hide_empty' => true));
            echo '<div class="stat-item total-brands">';
            echo '<span class="stat-number">' . number_format($total_brands) . '</span>';
            echo '<span class="stat-label">' . __('Марки', 'parfume-reviews') . '</span>';
            echo '</div>';
        }
        
        if ($atts['show_notes']) {
            $total_notes = wp_count_terms('notes', array('hide_empty' => true));
            echo '<div class="stat-item total-notes">';
            echo '<span class="stat-number">' . number_format($total_notes) . '</span>';
            echo '<span class="stat-label">' . __('Нотки', 'parfume-reviews') . '</span>';
            echo '</div>';
        }
        
        if ($atts['show_perfumers']) {
            $total_perfumers = wp_count_terms('perfumer', array('hide_empty' => true));
            echo '<div class="stat-item total-perfumers">';
            echo '<span class="stat-number">' . number_format($total_perfumers) . '</span>';
            echo '<span class="stat-label">' . __('Парфюмьори', 'parfume-reviews') . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * [popular_brands] - Показва популярни марки
     */
    public function popular_brands_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_count' => true,
            'title' => __('Популярни марки', 'parfume-reviews')
        ), $atts, 'popular_brands');
        
        $terms = get_terms(array(
            'taxonomy' => 'marki',
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => intval($atts['limit']),
            'hide_empty' => true
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('Няма намерени марки.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        
        echo '<div class="popular-brands">';
        
        if (!empty($atts['title'])) {
            echo '<h3 class="widget-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        echo '<ul class="brands-list">';
        foreach ($terms as $term) {
            echo '<li class="brand-item">';
            echo '<a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a>';
            if ($atts['show_count']) {
                echo ' <span class="count">(' . $term->count . ')</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * [popular_notes] - Показва популярни нотки
     */
    public function popular_notes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 15,
            'show_count' => false,
            'title' => __('Популярни нотки', 'parfume-reviews')
        ), $atts, 'popular_notes');
        
        $terms = get_terms(array(
            'taxonomy' => 'notes',
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => intval($atts['limit']),
            'hide_empty' => true
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('Няма намерени нотки.', 'parfume-reviews') . '</p>';
        }
        
        ob_start();
        
        echo '<div class="popular-notes">';
        
        if (!empty($atts['title'])) {
            echo '<h3 class="widget-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        echo '<div class="notes-cloud">';
        foreach ($terms as $term) {
            $font_size = min(100 + ($term->count * 2), 150); // Font size based on count
            echo '<a href="' . get_term_link($term) . '" class="note-tag" style="font-size: ' . $font_size . '%;">';
            echo esc_html($term->name);
            if ($atts['show_count']) {
                echo ' (' . $term->count . ')';
            }
            echo '</a>';
        }
        echo '</div>';
        
        echo '</div>';
        
        return ob_get_clean();
    }
}

// End of file