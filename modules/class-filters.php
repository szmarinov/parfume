<?php
/**
 * Parfume Catalog Filters Module
 * 
 * Система за динамични филтри, търсене и AJAX филтриране
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Filters {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_filters_assets'));
        add_action('wp_ajax_parfume_filter_results', array($this, 'ajax_filter_results'));
        add_action('wp_ajax_nopriv_parfume_filter_results', array($this, 'ajax_filter_results'));
        add_action('wp_ajax_parfume_search_suggestions', array($this, 'ajax_search_suggestions'));
        add_action('wp_ajax_nopriv_parfume_search_suggestions', array($this, 'ajax_search_suggestions'));
        add_action('pre_get_posts', array($this, 'modify_main_query'));
        add_filter('posts_clauses', array($this, 'custom_search_clauses'), 10, 2);
        add_shortcode('parfume_filters', array($this, 'filters_shortcode'));
        add_shortcode('parfume_search', array($this, 'search_shortcode'));
        add_shortcode('parfume_list', array($this, 'parfume_list_shortcode'));
        add_shortcode('parfume_filter_type', array($this, 'filter_type_shortcode'));
        add_shortcode('parfume_filter_marki', array($this, 'filter_marki_shortcode'));
        add_shortcode('parfume_filter_vid', array($this, 'filter_vid_shortcode'));
        add_shortcode('parfume_filter_season', array($this, 'filter_season_shortcode'));
        add_shortcode('parfume_filter_intensity', array($this, 'filter_intensity_shortcode'));
        add_shortcode('parfume_filter_notes', array($this, 'filter_notes_shortcode'));
    }

    /**
     * Enqueue на filters assets
     */
    public function enqueue_filters_assets() {
        if ($this->should_load_filters()) {
            wp_enqueue_script(
                'parfume-filters',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/filters.js',
                array('jquery'),
                '1.0.0',
                true
            );

            wp_enqueue_style(
                'parfume-filters',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/filters.css',
                array(),
                '1.0.0'
            );

            // Localize script с данни
            wp_localize_script('parfume-filters', 'parfumeFilters', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_filters_nonce'),
                'messages' => array(
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'no_results' => __('Няма намерени резултати', 'parfume-catalog'),
                    'error' => __('Възникна грешка', 'parfume-catalog')
                ),
                'sort_options' => array(
                    'date_desc' => __('Най-нови', 'parfume-catalog'),
                    'date_asc' => __('Най-стари', 'parfume-catalog'),
                    'title_asc' => __('Име А-Я', 'parfume-catalog'),
                    'title_desc' => __('Име Я-А', 'parfume-catalog'),
                    'rating_desc' => __('Най-високо оценени', 'parfume-catalog'),
                    'price_asc' => __('Цена възходящо', 'parfume-catalog'),
                    'price_desc' => __('Цена низходящо', 'parfume-catalog'),
                    'popularity' => __('Популярност', 'parfume-catalog')
                ),
                'per_page_options' => array(12, 24, 48, 96)
            ));

            // Добавяне на inline CSS
            wp_add_inline_style('parfume-filters', $this->get_filters_css());
        }
    }

    /**
     * Проверка дали да се зареди filters
     */
    private function should_load_filters() {
        return is_post_type_archive('parfumes') || 
               is_tax(array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes')) ||
               is_search() ||
               (is_page() && get_post() && has_shortcode(get_post()->post_content, 'parfume_filters'));
    }

    /**
     * AJAX - Филтриране на резултати
     */
    public function ajax_filter_results() {
        check_ajax_referer('parfume_filters_nonce', 'nonce');

        $filters = $this->sanitize_filter_data($_POST);
        $query_args = $this->build_filter_query($filters);
        
        $query = new WP_Query($query_args);
        
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_parfume_item(get_the_ID(), $filters['view_mode']);
            }
        } else {
            echo '<div class="no-results">' . __('Няма намерени резултати за вашите критерии.', 'parfume-catalog') . '</div>';
        }
        $content = ob_get_clean();

        wp_reset_postdata();

        wp_send_json_success(array(
            'content' => $content,
            'found_posts' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => max(1, $filters['paged'])
        ));
    }

    /**
     * AJAX - Търсене suggestions
     */
    public function ajax_search_suggestions() {
        check_ajax_referer('parfume_filters_nonce', 'nonce');

        $search_term = sanitize_text_field($_POST['search_term']);
        $suggestions = array();

        if (strlen($search_term) >= 2) {
            // Търсене на парфюми
            $parfumes_query = new WP_Query(array(
                'post_type' => 'parfumes',
                'posts_per_page' => 5,
                's' => $search_term,
                'post_status' => 'publish'
            ));

            if ($parfumes_query->have_posts()) {
                while ($parfumes_query->have_posts()) {
                    $parfumes_query->the_post();
                    $suggestions[] = array(
                        'title' => get_the_title(),
                        'url' => get_permalink(),
                        'type' => 'parfume',
                        'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                    );
                }
            }

            // Търсене на марки
            $marki = get_terms(array(
                'taxonomy' => 'parfume_marki',
                'name__like' => $search_term,
                'number' => 3,
                'hide_empty' => true
            ));

            foreach ($marki as $marka) {
                $suggestions[] = array(
                    'title' => $marka->name,
                    'url' => get_term_link($marka),
                    'type' => 'brand',
                    'count' => $marka->count
                );
            }

            // Търсене на нотки
            $notes = get_terms(array(
                'taxonomy' => 'parfume_notes',
                'name__like' => $search_term,
                'number' => 3,
                'hide_empty' => true
            ));

            foreach ($notes as $note) {
                $suggestions[] = array(
                    'title' => $note->name,
                    'url' => get_term_link($note),
                    'type' => 'note',
                    'count' => $note->count
                );
            }

            wp_reset_postdata();
        }

        wp_send_json_success($suggestions);
    }

    /**
     * Модификация на главната заявка
     */
    public function modify_main_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfumes') || is_tax(array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'))) {
                $filters = $this->get_current_filters();
                $this->apply_filters_to_query($query, $filters);
            }
        }
    }

    /**
     * Custom search clauses за разширено търсене
     */
    public function custom_search_clauses($clauses, $query) {
        global $wpdb;

        if (!is_admin() && $query->is_main_query() && $query->is_search() && $query->get('post_type') === 'parfumes') {
            $search_term = $query->get('s');
            
            // Търсене в мета полета и таксономии
            $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} pm ON {$wpdb->posts}.ID = pm.post_id";
            $clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id";
            $clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
            $clauses['join'] .= " LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id";

            $clauses['where'] = preg_replace('/\(\(\(.*?\)\)\)/', '', $clauses['where']);
            
            $clauses['where'] .= " AND (
                {$wpdb->posts}.post_title LIKE '%{$search_term}%'
                OR {$wpdb->posts}.post_content LIKE '%{$search_term}%'
                OR pm.meta_value LIKE '%{$search_term}%'
                OR t.name LIKE '%{$search_term}%'
            )";

            $clauses['groupby'] = "{$wpdb->posts}.ID";
        }

        return $clauses;
    }

    /**
     * Sanitize filter данни
     */
    private function sanitize_filter_data($data) {
        $filters = array(
            'search' => isset($data['search']) ? sanitize_text_field($data['search']) : '',
            'type' => isset($data['type']) ? array_map('sanitize_text_field', (array)$data['type']) : array(),
            'vid' => isset($data['vid']) ? array_map('sanitize_text_field', (array)$data['vid']) : array(),
            'marki' => isset($data['marki']) ? array_map('sanitize_text_field', (array)$data['marki']) : array(),
            'season' => isset($data['season']) ? array_map('sanitize_text_field', (array)$data['season']) : array(),
            'intensity' => isset($data['intensity']) ? array_map('sanitize_text_field', (array)$data['intensity']) : array(),
            'notes' => isset($data['notes']) ? array_map('sanitize_text_field', (array)$data['notes']) : array(),
            'price_min' => isset($data['price_min']) ? floatval($data['price_min']) : 0,
            'price_max' => isset($data['price_max']) ? floatval($data['price_max']) : 0,
            'rating_min' => isset($data['rating_min']) ? intval($data['rating_min']) : 0,
            'longevity' => isset($data['longevity']) ? array_map('sanitize_text_field', (array)$data['longevity']) : array(),
            'sillage' => isset($data['sillage']) ? array_map('sanitize_text_field', (array)$data['sillage']) : array(),
            'launch_year_min' => isset($data['launch_year_min']) ? intval($data['launch_year_min']) : 0,
            'launch_year_max' => isset($data['launch_year_max']) ? intval($data['launch_year_max']) : 0,
            'suitable_day' => isset($data['suitable_day']) ? (bool)$data['suitable_day'] : false,
            'suitable_night' => isset($data['suitable_night']) ? (bool)$data['suitable_night'] : false,
            'orderby' => isset($data['orderby']) ? sanitize_text_field($data['orderby']) : 'date',
            'order' => isset($data['order']) ? sanitize_text_field($data['order']) : 'DESC',
            'posts_per_page' => isset($data['posts_per_page']) ? intval($data['posts_per_page']) : 12,
            'paged' => isset($data['paged']) ? intval($data['paged']) : 1,
            'view_mode' => isset($data['view_mode']) ? sanitize_text_field($data['view_mode']) : 'grid'
        );

        return $filters;
    }

    /**
     * Построяване на filter query
     */
    private function build_filter_query($filters) {
        $args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => $filters['posts_per_page'],
            'paged' => $filters['paged'],
            'orderby' => $this->get_orderby_value($filters['orderby']),
            'order' => $filters['order']
        );

        // Търсене
        if (!empty($filters['search'])) {
            $args['s'] = $filters['search'];
        }

        // Tax queries
        $tax_query = array('relation' => 'AND');

        if (!empty($filters['type'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_type',
                'field' => 'slug',
                'terms' => $filters['type'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['vid'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_vid',
                'field' => 'slug',
                'terms' => $filters['vid'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['marki'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_marki',
                'field' => 'slug',
                'terms' => $filters['marki'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['season'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_season',
                'field' => 'slug',
                'terms' => $filters['season'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['intensity'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_intensity',
                'field' => 'slug',
                'terms' => $filters['intensity'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['notes'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_notes',
                'field' => 'slug',
                'terms' => $filters['notes'],
                'operator' => 'IN'
            );
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        // Meta queries
        $meta_query = array('relation' => 'AND');

        if ($filters['price_min'] > 0 || $filters['price_max'] > 0) {
            $price_query = array(
                'key' => '_parfume_price_range',
                'compare' => 'EXISTS'
            );
            
            if ($filters['price_min'] > 0 && $filters['price_max'] > 0) {
                $price_query['value'] = array($filters['price_min'], $filters['price_max']);
                $price_query['compare'] = 'BETWEEN';
                $price_query['type'] = 'NUMERIC';
            } elseif ($filters['price_min'] > 0) {
                $price_query['value'] = $filters['price_min'];
                $price_query['compare'] = '>=';
                $price_query['type'] = 'NUMERIC';
            } elseif ($filters['price_max'] > 0) {
                $price_query['value'] = $filters['price_max'];
                $price_query['compare'] = '<=';
                $price_query['type'] = 'NUMERIC';
            }
            
            $meta_query[] = $price_query;
        }

        if ($filters['rating_min'] > 0) {
            $meta_query[] = array(
                'key' => '_parfume_average_rating',
                'value' => $filters['rating_min'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            );
        }

        if ($filters['launch_year_min'] > 0 || $filters['launch_year_max'] > 0) {
            $year_query = array(
                'key' => '_parfume_launch_year',
                'type' => 'NUMERIC'
            );
            
            if ($filters['launch_year_min'] > 0 && $filters['launch_year_max'] > 0) {
                $year_query['value'] = array($filters['launch_year_min'], $filters['launch_year_max']);
                $year_query['compare'] = 'BETWEEN';
            } elseif ($filters['launch_year_min'] > 0) {
                $year_query['value'] = $filters['launch_year_min'];
                $year_query['compare'] = '>=';
            } elseif ($filters['launch_year_max'] > 0) {
                $year_query['value'] = $filters['launch_year_max'];
                $year_query['compare'] = '<=';
            }
            
            $meta_query[] = $year_query;
        }

        if ($filters['suitable_day']) {
            $meta_query[] = array(
                'key' => '_parfume_suitable_day',
                'value' => '1',
                'compare' => '='
            );
        }

        if ($filters['suitable_night']) {
            $meta_query[] = array(
                'key' => '_parfume_suitable_night',
                'value' => '1',
                'compare' => '='
            );
        }

        if (!empty($filters['longevity'])) {
            $meta_query[] = array(
                'key' => '_parfume_longevity',
                'value' => $filters['longevity'],
                'compare' => 'IN'
            );
        }

        if (!empty($filters['sillage'])) {
            $meta_query[] = array(
                'key' => '_parfume_sillage',
                'value' => $filters['sillage'],
                'compare' => 'IN'
            );
        }

        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    /**
     * Извличане на текущите филтри
     */
    private function get_current_filters() {
        return array(
            'search' => get_query_var('s', ''),
            'type' => get_query_var('parfume_type_filter', array()),
            'vid' => get_query_var('parfume_vid_filter', array()),
            'marki' => get_query_var('parfume_marki_filter', array()),
            'season' => get_query_var('parfume_season_filter', array()),
            'intensity' => get_query_var('parfume_intensity_filter', array()),
            'notes' => get_query_var('parfume_notes_filter', array()),
            'price_min' => floatval(get_query_var('price_min', 0)),
            'price_max' => floatval(get_query_var('price_max', 0)),
            'rating_min' => intval(get_query_var('rating_min', 0)),
            'longevity' => get_query_var('longevity_filter', array()),
            'sillage' => get_query_var('sillage_filter', array()),
            'launch_year_min' => intval(get_query_var('launch_year_min', 0)),
            'launch_year_max' => intval(get_query_var('launch_year_max', 0)),
            'suitable_day' => (bool)get_query_var('suitable_day', false),
            'suitable_night' => (bool)get_query_var('suitable_night', false),
            'orderby' => get_query_var('orderby', 'date'),
            'order' => get_query_var('order', 'DESC'),
            'posts_per_page' => intval(get_query_var('posts_per_page', 12)),
            'paged' => get_query_var('paged', 1),
            'view_mode' => get_query_var('view_mode', 'grid')
        );
    }

    /**
     * Прилагане на филтри към заявката
     */
    private function apply_filters_to_query($query, $filters) {
        if (!empty($filters['type'])) {
            $query->set('parfume_type', implode(',', $filters['type']));
        }

        if (!empty($filters['vid'])) {
            $query->set('parfume_vid', implode(',', $filters['vid']));
        }

        if (!empty($filters['marki'])) {
            $query->set('parfume_marki', implode(',', $filters['marki']));
        }

        // Добавяне на мета заявки
        $meta_query = array('relation' => 'AND');

        if ($filters['price_min'] > 0 || $filters['price_max'] > 0) {
            $price_query = array('key' => '_parfume_price_range');
            
            if ($filters['price_min'] > 0 && $filters['price_max'] > 0) {
                $price_query['value'] = array($filters['price_min'], $filters['price_max']);
                $price_query['compare'] = 'BETWEEN';
                $price_query['type'] = 'NUMERIC';
            } elseif ($filters['price_min'] > 0) {
                $price_query['value'] = $filters['price_min'];
                $price_query['compare'] = '>=';
                $price_query['type'] = 'NUMERIC';
            } elseif ($filters['price_max'] > 0) {
                $price_query['value'] = $filters['price_max'];
                $price_query['compare'] = '<=';
                $price_query['type'] = 'NUMERIC';
            }
            
            $meta_query[] = $price_query;
        }

        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }

        // Sorting
        $orderby = $this->get_orderby_value($filters['orderby']);
        $query->set('orderby', $orderby);
        $query->set('order', $filters['order']);

        // Posts per page
        if ($filters['posts_per_page'] > 0) {
            $query->set('posts_per_page', $filters['posts_per_page']);
        }
    }

    /**
     * Конвертиране на orderby стойности
     */
    private function get_orderby_value($orderby) {
        switch ($orderby) {
            case 'title_asc':
                return 'title';
            case 'title_desc':
                return 'title';
            case 'date_asc':
                return 'date';
            case 'date_desc':
                return 'date';
            case 'rating_desc':
                return 'meta_value_num';
            case 'price_asc':
            case 'price_desc':
                return 'meta_value_num';
            case 'popularity':
                return 'comment_count';
            default:
                return 'date';
        }
    }

    /**
     * Рендериране на parfume item
     */
    private function render_parfume_item($post_id, $view_mode = 'grid') {
        $post = get_post($post_id);
        $title = get_the_title($post_id);
        $permalink = get_permalink($post_id);
        $thumbnail = get_the_post_thumbnail($post_id, $view_mode === 'grid' ? 'medium' : 'thumbnail');
        $excerpt = get_the_excerpt($post_id);
        $price_range = get_post_meta($post_id, '_parfume_price_range', true);
        $average_rating = get_post_meta($post_id, '_parfume_average_rating', true);

        // Марка
        $marki = get_the_terms($post_id, 'parfume_marki');
        $brand = $marki && !is_wp_error($marki) ? $marki[0]->name : '';

        // Нотки
        $notes = get_the_terms($post_id, 'parfume_notes');
        $notes_list = array();
        if ($notes && !is_wp_error($notes)) {
            foreach (array_slice($notes, 0, 3) as $note) {
                $notes_list[] = $note->name;
            }
        }

        $class = $view_mode === 'list' ? 'parfume-list-item' : 'parfume-grid-item';

        ?>
        <article class="<?php echo esc_attr($class); ?>">
            <?php if ($thumbnail): ?>
                <div class="parfume-image">
                    <a href="<?php echo esc_url($permalink); ?>">
                        <?php echo $thumbnail; ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="parfume-content">
                <h3 class="parfume-title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h3>

                <?php if ($brand): ?>
                    <div class="parfume-brand"><?php echo esc_html($brand); ?></div>
                <?php endif; ?>

                <?php if ($average_rating): ?>
                    <div class="parfume-rating">
                        <?php echo $this->render_stars($average_rating); ?>
                        <span class="rating-text"><?php echo number_format($average_rating, 1); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($notes_list): ?>
                    <div class="parfume-notes">
                        <?php echo implode(', ', $notes_list); ?>
                        <?php if (count($notes) > 3): ?>
                            <span class="notes-more">...</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($price_range): ?>
                    <div class="parfume-price"><?php echo $this->format_price_range($price_range); ?></div>
                <?php endif; ?>

                <?php if ($view_mode === 'list' && $excerpt): ?>
                    <div class="parfume-excerpt"><?php echo esc_html($excerpt); ?></div>
                <?php endif; ?>

                <div class="parfume-actions">
                    <a href="<?php echo esc_url($permalink); ?>" class="button view-parfume">
                        <?php _e('Виж детайли', 'parfume-catalog'); ?>
                    </a>
                    <button type="button" class="button-outline add-to-comparison" data-id="<?php echo $post_id; ?>">
                        <?php _e('Сравни', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </article>
        <?php
    }

    /**
     * Форматиране на ценови диапазон
     */
    private function format_price_range($price_range) {
        if (is_array($price_range)) {
            $min_price = floatval($price_range[0]);
            $max_price = floatval($price_range[1]);
        } else {
            $min_price = $max_price = floatval($price_range);
        }

        if ($min_price === $max_price) {
            return sprintf('%.2f лв.', $min_price);
        }
        
        return sprintf('%.2f - %.2f лв.', $min_price, $max_price);
    }

    /**
     * Рендериране на звезди
     */
    private function render_stars($rating, $max_rating = 5) {
        $output = '<div class="rating-stars">';
        
        for ($i = 1; $i <= $max_rating; $i++) {
            $class = $i <= $rating ? 'star-filled' : 'star-empty';
            $output .= '<span class="star ' . $class . '">★</span>';
        }
        
        $output .= '</div>';
        return $output;
    }

    /**
     * Shortcode функции
     */
    public function filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_search' => 'yes',
            'show_sorting' => 'yes',
            'show_view_mode' => 'yes',
            'ajax' => 'yes',
            'class' => ''
        ), $atts);

        ob_start();
        $this->render_filters_form($atts);
        return ob_get_clean();
    }

    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Търси парфюми...', 'parfume-catalog'),
            'suggestions' => 'yes',
            'class' => ''
        ), $atts);

        ob_start();
        $this->render_search_form($atts);
        return ob_get_clean();
    }

    public function parfume_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'type' => '',
            'marki' => '',
            'vid' => '',
            'season' => '',
            'intensity' => '',
            'notes' => '',
            'view_mode' => 'grid',
            'show_filters' => 'no',
            'class' => ''
        ), $atts);

        // Построяване на query
        $query_args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        );

        // Добавяне на tax queries
        $tax_query = array();

        if (!empty($atts['type'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_type',
                'field' => 'slug',
                'terms' => explode(',', $atts['type'])
            );
        }

        if (!empty($atts['marki'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_marki',
                'field' => 'slug',
                'terms' => explode(',', $atts['marki'])
            );
        }

        if (!empty($atts['vid'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_vid',
                'field' => 'slug',
                'terms' => explode(',', $atts['vid'])
            );
        }

        if (!empty($atts['season'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_season',
                'field' => 'slug',
                'terms' => explode(',', $atts['season'])
            );
        }

        if (!empty($atts['intensity'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_intensity',
                'field' => 'slug',
                'terms' => explode(',', $atts['intensity'])
            );
        }

        if (!empty($atts['notes'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_notes',
                'field' => 'slug',
                'terms' => explode(',', $atts['notes'])
            );
        }

        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($query_args);

        ob_start();
        ?>
        <div class="parfume-shortcode-list <?php echo esc_attr($atts['class']); ?>">
            <?php if ($atts['show_filters'] === 'yes'): ?>
                <?php $this->render_filters_form(array('ajax' => 'yes')); ?>
            <?php endif; ?>

            <div class="parfume-results" data-view-mode="<?php echo esc_attr($atts['view_mode']); ?>">
                <?php if ($query->have_posts()): ?>
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <?php $this->render_parfume_item(get_the_ID(), $atts['view_mode']); ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <?php _e('Няма намерени парфюми.', 'parfume-catalog'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($query->max_num_pages > 1): ?>
                <div class="parfume-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $query->max_num_pages,
                        'current' => get_query_var('paged') ? get_query_var('paged') : 1,
                        'format' => '?paged=%#%',
                        'prev_text' => __('← Предишна', 'parfume-catalog'),
                        'next_text' => __('Следваща →', 'parfume-catalog')
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Filter shortcodes за отделни таксономии
     */
    public function filter_type_shortcode($atts) {
        return $this->render_taxonomy_filter('parfume_type', $atts);
    }

    public function filter_marki_shortcode($atts) {
        return $this->render_taxonomy_filter('parfume_marki', $atts);
    }

    public function filter_vid_shortcode($atts) {
        return $this->render_taxonomy_filter('parfume_vid', $atts);
    }

    public function filter_season_shortcode($atts) {
        return $this->render_taxonomy_filter('parfume_season', $atts);
    }

    public function filter_intensity_shortcode($atts) {
        return $this->render_taxonomy_filter('parfume_intensity', $atts);
    }

    public function filter_notes_shortcode($atts) {
        return $this->render_taxonomy_filter('parfume_notes', $atts);
    }

    /**
     * Рендериране на filter за таксономия
     */
    private function render_taxonomy_filter($taxonomy, $atts) {
        $atts = shortcode_atts(array(
            'show_count' => 'yes',
            'hide_empty' => 'yes',
            'orderby' => 'name',
            'order' => 'ASC',
            'style' => 'checkboxes', // checkboxes, dropdown, links
            'limit' => 0,
            'class' => ''
        ), $atts);

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => $atts['hide_empty'] === 'yes',
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'number' => $atts['limit'] > 0 ? $atts['limit'] : 0
        ));

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        $filter_name = str_replace('parfume_', '', $taxonomy);

        ob_start();
        ?>
        <div class="parfume-taxonomy-filter <?php echo esc_attr($atts['class']); ?>" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
            <?php if ($atts['style'] === 'dropdown'): ?>
                <select name="<?php echo esc_attr($filter_name); ?>[]" multiple>
                    <option value=""><?php echo esc_html(get_taxonomy($taxonomy)->labels->all_items); ?></option>
                    <?php foreach ($terms as $term): ?>
                        <option value="<?php echo esc_attr($term->slug); ?>">
                            <?php echo esc_html($term->name); ?>
                            <?php if ($atts['show_count'] === 'yes'): ?>
                                (<?php echo $term->count; ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            
            <?php elseif ($atts['style'] === 'links'): ?>
                <ul class="filter-links">
                    <?php foreach ($terms as $term): ?>
                        <li>
                            <a href="<?php echo esc_url(get_term_link($term)); ?>" class="filter-link">
                                <?php echo esc_html($term->name); ?>
                                <?php if ($atts['show_count'] === 'yes'): ?>
                                    <span class="count">(<?php echo $term->count; ?>)</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            
            <?php else: // checkboxes ?>
                <div class="filter-checkboxes">
                    <?php foreach ($terms as $term): ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="<?php echo esc_attr($filter_name); ?>[]" value="<?php echo esc_attr($term->slug); ?>" />
                            <span class="checkmark"></span>
                            <?php echo esc_html($term->name); ?>
                            <?php if ($atts['show_count'] === 'yes'): ?>
                                <span class="count">(<?php echo $term->count; ?>)</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендериране на filters форма
     */
    private function render_filters_form($atts) {
        ?>
        <div class="parfume-filters-form" data-ajax="<?php echo esc_attr($atts['ajax']); ?>">
            <form class="filters-form" method="GET">
                <?php if ($atts['show_search'] === 'yes'): ?>
                    <div class="filter-group search-group">
                        <label for="parfume_search"><?php _e('Търсене', 'parfume-catalog'); ?></label>
                        <input type="text" id="parfume_search" name="search" 
                               placeholder="<?php esc_attr_e('Търси парфюми, марки, нотки...', 'parfume-catalog'); ?>" 
                               value="<?php echo esc_attr(get_query_var('s')); ?>" />
                        <div class="search-suggestions" style="display: none;"></div>
                    </div>
                <?php endif; ?>

                <div class="filter-groups-container">
                    <!-- Тип филтър -->
                    <div class="filter-group">
                        <label><?php _e('Тип', 'parfume-catalog'); ?></label>
                        <?php echo $this->render_taxonomy_filter('parfume_type', array('style' => 'checkboxes')); ?>
                    </div>

                    <!-- Вид аромат филтър -->
                    <div class="filter-group">
                        <label><?php _e('Вид аромат', 'parfume-catalog'); ?></label>
                        <?php echo $this->render_taxonomy_filter('parfume_vid', array('style' => 'checkboxes')); ?>
                    </div>

                    <!-- Марка филтър -->
                    <div class="filter-group">
                        <label><?php _e('Марка', 'parfume-catalog'); ?></label>
                        <?php echo $this->render_taxonomy_filter('parfume_marki', array('style' => 'dropdown', 'limit' => 20)); ?>
                    </div>

                    <!-- Сезон филтър -->
                    <div class="filter-group">
                        <label><?php _e('Сезон', 'parfume-catalog'); ?></label>
                        <?php echo $this->render_taxonomy_filter('parfume_season', array('style' => 'checkboxes')); ?>
                    </div>

                    <!-- Интензивност филтър -->
                    <div class="filter-group">
                        <label><?php _e('Интензивност', 'parfume-catalog'); ?></label>
                        <?php echo $this->render_taxonomy_filter('parfume_intensity', array('style' => 'checkboxes')); ?>
                    </div>

                    <!-- Нотки филтър -->
                    <div class="filter-group">
                        <label><?php _e('Нотки', 'parfume-catalog'); ?></label>
                        <?php echo $this->render_taxonomy_filter('parfume_notes', array('style' => 'dropdown', 'limit' => 50)); ?>
                    </div>

                    <!-- Ценови диапазон -->
                    <div class="filter-group price-range">
                        <label><?php _e('Ценови диапазон', 'parfume-catalog'); ?></label>
                        <div class="price-inputs">
                            <input type="number" name="price_min" placeholder="<?php esc_attr_e('От', 'parfume-catalog'); ?>" 
                                   value="<?php echo esc_attr(get_query_var('price_min')); ?>" min="0" step="0.01" />
                            <span class="separator">-</span>
                            <input type="number" name="price_max" placeholder="<?php esc_attr_e('До', 'parfume-catalog'); ?>" 
                                   value="<?php echo esc_attr(get_query_var('price_max')); ?>" min="0" step="0.01" />
                        </div>
                    </div>

                    <!-- Рейтинг -->
                    <div class="filter-group rating-filter">
                        <label><?php _e('Минимален рейтинг', 'parfume-catalog'); ?></label>
                        <select name="rating_min">
                            <option value="0"><?php _e('Всички', 'parfume-catalog'); ?></option>
                            <option value="1">1+ ⭐</option>
                            <option value="2">2+ ⭐</option>
                            <option value="3">3+ ⭐</option>
                            <option value="4">4+ ⭐</option>
                            <option value="5">5 ⭐</option>
                        </select>
                    </div>

                    <!-- Подходящост -->
                    <div class="filter-group suitability-filter">
                        <label><?php _e('Подходящ за', 'parfume-catalog'); ?></label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="suitable_day" value="1" 
                                   <?php checked(get_query_var('suitable_day'), '1'); ?> />
                            <span class="checkmark"></span>
                            <?php _e('Ден', 'parfume-catalog'); ?>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="suitable_night" value="1" 
                                   <?php checked(get_query_var('suitable_night'), '1'); ?> />
                            <span class="checkmark"></span>
                            <?php _e('Нощ', 'parfume-catalog'); ?>
                        </label>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="button button-primary apply-filters">
                        <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button clear-filters">
                        <?php _e('Изчисти', 'parfume-catalog'); ?>
                    </button>
                </div>

                <?php if ($atts['show_sorting'] === 'yes'): ?>
                    <div class="results-controls">
                        <div class="sorting-controls">
                            <label for="orderby"><?php _e('Подреди по:', 'parfume-catalog'); ?></label>
                            <select name="orderby" id="orderby">
                                <option value="date_desc"><?php _e('Най-нови', 'parfume-catalog'); ?></option>
                                <option value="date_asc"><?php _e('Най-стари', 'parfume-catalog'); ?></option>
                                <option value="title_asc"><?php _e('Име А-Я', 'parfume-catalog'); ?></option>
                                <option value="title_desc"><?php _e('Име Я-А', 'parfume-catalog'); ?></option>
                                <option value="rating_desc"><?php _e('Най-високо оценени', 'parfume-catalog'); ?></option>
                                <option value="price_asc"><?php _e('Цена възходящо', 'parfume-catalog'); ?></option>
                                <option value="price_desc"><?php _e('Цена низходящо', 'parfume-catalog'); ?></option>
                                <option value="popularity"><?php _e('Популярност', 'parfume-catalog'); ?></option>
                            </select>
                        </div>

                        <div class="per-page-controls">
                            <label for="posts_per_page"><?php _e('Показвай по:', 'parfume-catalog'); ?></label>
                            <select name="posts_per_page" id="posts_per_page">
                                <option value="12">12</option>
                                <option value="24">24</option>
                                <option value="48">48</option>
                                <option value="96">96</option>
                            </select>
                        </div>

                        <?php if ($atts['show_view_mode'] === 'yes'): ?>
                            <div class="view-mode-controls">
                                <button type="button" class="view-mode-btn active" data-mode="grid" title="<?php esc_attr_e('Мрежа', 'parfume-catalog'); ?>">
                                    <span class="dashicons dashicons-grid-view"></span>
                                </button>
                                <button type="button" class="view-mode-btn" data-mode="list" title="<?php esc_attr_e('Списък', 'parfume-catalog'); ?>">
                                    <span class="dashicons dashicons-list-view"></span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php wp_nonce_field('parfume_filters_nonce', 'nonce'); ?>
            </form>

            <div class="filters-loading" style="display: none;">
                <span class="spinner"></span>
                <?php _e('Зареждане...', 'parfume-catalog'); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Рендериране на search форма
     */
    private function render_search_form($atts) {
        ?>
        <div class="parfume-search-form <?php echo esc_attr($atts['class']); ?>">
            <form class="search-form" method="GET" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="parfumes" />
                <div class="search-input-wrapper">
                    <input type="text" name="s" class="search-input" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                           value="<?php echo esc_attr(get_search_query()); ?>" />
                    <button type="submit" class="search-submit">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
                
                <?php if ($atts['suggestions'] === 'yes'): ?>
                    <div class="search-suggestions" style="display: none;"></div>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * CSS за filters
     */
    private function get_filters_css() {
        return '
        .parfume-filters-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .filter-groups-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .filter-checkboxes {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-weight: normal;
            cursor: pointer;
        }
        
        .filter-checkbox input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .filter-checkbox .count {
            margin-left: auto;
            color: #666;
            font-size: 0.9em;
        }
        
        .price-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price-inputs input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .separator {
            color: #666;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .results-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .sorting-controls,
        .per-page-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-mode-controls {
            display: flex;
            gap: 5px;
        }
        
        .view-mode-btn {
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .view-mode-btn.active {
            background: #0073aa;
            color: white;
            border-color: #0073aa;
        }
        
        .parfume-results {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        
        .parfume-results[data-view-mode="grid"] {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
        
        .parfume-results[data-view-mode="list"] {
            grid-template-columns: 1fr;
        }
        
        .parfume-grid-item,
        .parfume-list-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .parfume-grid-item:hover,
        .parfume-list-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .parfume-list-item {
            display: flex;
            align-items: center;
            padding: 15px;
        }
        
        .parfume-list-item .parfume-image {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            margin-right: 15px;
        }
        
        .parfume-list-item .parfume-content {
            flex: 1;
        }
        
        .parfume-image {
            position: relative;
            overflow: hidden;
        }
        
        .parfume-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .parfume-content {
            padding: 15px;
        }
        
        .parfume-title {
            margin: 0 0 8px 0;
            font-size: 1.1em;
            line-height: 1.3;
        }
        
        .parfume-title a {
            color: #333;
            text-decoration: none;
        }
        
        .parfume-title a:hover {
            color: #0073aa;
        }
        
        .parfume-brand {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 8px;
        }
        
        .parfume-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 8px;
        }
        
        .rating-stars {
            display: flex;
            gap: 2px;
        }
        
        .star {
            font-size: 14px;
            line-height: 1;
        }
        
        .star-filled {
            color: #ffa500;
        }
        
        .star-empty {
            color: #ddd;
        }
        
        .rating-text {
            font-size: 0.9em;
            color: #666;
        }
        
        .parfume-notes {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .parfume-price {
            font-weight: 600;
            color: #e91e63;
            margin-bottom: 12px;
        }
        
        .parfume-excerpt {
            font-size: 0.9em;
            color: #666;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        
        .parfume-actions {
            display: flex;
            gap: 8px;
        }
        
        .parfume-actions .button {
            flex: 1;
            text-align: center;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .view-parfume {
            background: #0073aa;
            color: white;
            border: 1px solid #0073aa;
        }
        
        .view-parfume:hover {
            background: #005a87;
            border-color: #005a87;
        }
        
        .add-to-comparison {
            background: transparent;
            color: #0073aa;
            border: 1px solid #0073aa;
        }
        
        .add-to-comparison:hover {
            background: #0073aa;
            color: white;
        }
        
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 100;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .search-suggestion {
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-suggestion:hover {
            background: #f9f9f9;
        }
        
        .search-suggestion:last-child {
            border-bottom: none;
        }
        
        .suggestion-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .suggestion-content {
            flex: 1;
        }
        
        .suggestion-title {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .suggestion-type {
            font-size: 0.8em;
            color: #666;
        }
        
        .filters-loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .filters-loading .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0073aa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .filter-groups-container {
                grid-template-columns: 1fr;
            }
            
            .results-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .parfume-results[data-view-mode="grid"] {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .parfume-list-item {
                flex-direction: column;
                text-align: center;
            }
            
            .parfume-list-item .parfume-image {
                margin-right: 0;
                margin-bottom: 15px;
                width: 120px;
                height: 120px;
            }
            
            .search-input-wrapper {
                position: relative;
            }
            
            .filter-actions {
                flex-direction: column;
            }
        }
        ';
    }

    /**
     * Извличане на scraper данни за цени
     */
    public function get_parfume_price_data($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_scraper_data';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT store_id, price, old_price, variants, last_scraped 
             FROM {$table_name} 
             WHERE post_id = %d AND status = 'success'
             ORDER BY price ASC",
            $post_id
        ));
        
        $price_data = array(
            'min_price' => 0,
            'max_price' => 0,
            'stores' => array()
        );
        
        if (!empty($results)) {
            $prices = array();
            
            foreach ($results as $result) {
                $variants = maybe_unserialize($result->variants);
                $store_prices = array();
                
                if (is_array($variants)) {
                    foreach ($variants as $variant) {
                        if (isset($variant['price']) && $variant['price'] > 0) {
                            $store_prices[] = floatval($variant['price']);
                        }
                    }
                } elseif ($result->price > 0) {
                    $store_prices[] = floatval($result->price);
                }
                
                if (!empty($store_prices)) {
                    $prices = array_merge($prices, $store_prices);
                    $price_data['stores'][$result->store_id] = array(
                        'min_price' => min($store_prices),
                        'max_price' => max($store_prices),
                        'has_discount' => !empty($result->old_price) && $result->old_price > $result->price,
                        'last_updated' => $result->last_scraped
                    );
                }
            }
            
            if (!empty($prices)) {
                $price_data['min_price'] = min($prices);
                $price_data['max_price'] = max($prices);
            }
        }
        
        return $price_data;
    }

    /**
     * Извличане на средна оценка от comments
     */
    public function get_parfume_average_rating($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count 
             FROM {$table_name} 
             WHERE post_id = %d AND status = 'approved' AND rating > 0",
            $post_id
        ));
        
        return array(
            'average' => $result ? round($result->average, 1) : 0,
            'count' => $result ? intval($result->count) : 0
        );
    }

    /**
     * Helper функции за интеграция с други модули
     */
    public static function get_filter_terms($taxonomy, $args = array()) {
        $defaults = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        return get_terms($args);
    }

    public static function get_parfume_meta_for_filters($post_id) {
        return array(
            'suitable_day' => get_post_meta($post_id, '_parfume_suitable_day', true),
            'suitable_night' => get_post_meta($post_id, '_parfume_suitable_night', true),
            'longevity' => get_post_meta($post_id, '_parfume_longevity', true),
            'sillage' => get_post_meta($post_id, '_parfume_sillage', true),
            'launch_year' => get_post_meta($post_id, '_parfume_launch_year', true),
            'price_category' => get_post_meta($post_id, '_parfume_price_category', true)
        );
    }

    /**
     * Utility функции
     */
    public static function sanitize_search_term($term) {
        return sanitize_text_field(trim($term));
    }

    public static function build_archive_url($taxonomy, $term_slug, $additional_params = array()) {
        $base_url = get_term_link($term_slug, $taxonomy);
        
        if (!empty($additional_params)) {
            $base_url = add_query_arg($additional_params, $base_url);
        }
        
        return $base_url;
    }

    public static function get_active_filters_count() {
        $count = 0;
        
        if (get_query_var('s')) $count++;
        if (get_query_var('parfume_type')) $count++;
        if (get_query_var('parfume_vid')) $count++;
        if (get_query_var('parfume_marki')) $count++;
        if (get_query_var('parfume_season')) $count++;
        if (get_query_var('parfume_intensity')) $count++;
        if (get_query_var('parfume_notes')) $count++;
        if (get_query_var('price_min') || get_query_var('price_max')) $count++;
        if (get_query_var('rating_min')) $count++;
        if (get_query_var('suitable_day') || get_query_var('suitable_night')) $count++;
        
        return $count;
    }

    /**
     * Debug функции
     */
    public function debug_query_vars() {
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
            global $wp_query;
            
            echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
            echo '<strong>Debug Query Vars:</strong><br>';
            echo '<pre>' . print_r($wp_query->query_vars, true) . '</pre>';
            echo '</div>';
        }
    }

    /**
     * Performance tracking
     */
    private function start_timer($name) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->timers[$name] = microtime(true);
        }
    }

    private function end_timer($name) {
        if (defined('WP_DEBUG') && WP_DEBUG && isset($this->timers[$name])) {
            $elapsed = microtime(true) - $this->timers[$name];
            error_log("Parfume Filters - {$name}: " . round($elapsed * 1000, 2) . 'ms');
        }
    }

    /**
     * Cleanup функции при деактивиране
     */
    public static function cleanup_filters_data() {
        // Изчистване на transients
        delete_transient('parfume_filters_terms_cache');
        delete_transient('parfume_price_ranges_cache');
        
        // Изчистване на опции
        delete_option('parfume_filters_settings');
    }
}