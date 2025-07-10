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
                PARFUME_CATALOG_VERSION,
                true
            );

            wp_localize_script('parfume-filters', 'parfume_filters_config', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_filters_nonce'),
                'current_url' => $this->get_current_filter_url(),
                'texts' => array(
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'no_results' => __('Няма намерени резултати', 'parfume-catalog'),
                    'clear_filters' => __('Изчисти филтри', 'parfume-catalog'),
                    'apply_filters' => __('Приложи филтри', 'parfume-catalog'),
                    'search_placeholder' => __('Търси парфюми...', 'parfume-catalog'),
                    'showing_results' => __('Показват се %d от %d резултата', 'parfume-catalog'),
                    'load_more' => __('Зареди още', 'parfume-catalog'),
                    'sort_by' => __('Сортирай по', 'parfume-catalog'),
                    'view_mode' => __('Изглед', 'parfume-catalog'),
                    'grid_view' => __('Решетка', 'parfume-catalog'),
                    'list_view' => __('Списък', 'parfume-catalog')
                ),
                'sort_options' => array(
                    'date_desc' => __('Най-нови първо', 'parfume-catalog'),
                    'date_asc' => __('Най-стари първо', 'parfume-catalog'),
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
            wp_add_inline_style('parfume-catalog-frontend', $this->get_filters_css());
        }
    }

    /**
     * Проверка дали да се зареди filters
     */
    private function should_load_filters() {
        return is_post_type_archive('parfumes') || 
               is_tax(array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes')) ||
               is_search() ||
               (is_page() && has_shortcode(get_post()->post_content, 'parfume_filters'));
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
        }
        $content = ob_get_clean();
        wp_reset_postdata();

        // Генериране на pagination
        $pagination_html = '';
        if ($query->max_num_pages > 1) {
            $pagination_html = $this->generate_ajax_pagination($query, $filters);
        }

        // Статистики
        $stats = array(
            'found_posts' => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
            'current_page' => $filters['paged']
        );

        wp_send_json_success(array(
            'content' => $content,
            'pagination' => $pagination_html,
            'stats' => $stats,
            'filters_applied' => $this->get_applied_filters_count($filters)
        ));
    }

    /**
     * AJAX - Автоматично допълване при търсене
     */
    public function ajax_search_suggestions() {
        check_ajax_referer('parfume_filters_nonce', 'nonce');

        $search_term = sanitize_text_field($_POST['term']);
        
        if (strlen($search_term) < 2) {
            wp_send_json_success(array());
        }

        $suggestions = array();

        // Търсене в парфюми
        $parfume_query = new WP_Query(array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => 5,
            'fields' => 'ids'
        ));

        foreach ($parfume_query->posts as $post_id) {
            $suggestions[] = array(
                'type' => 'parfume',
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'brand' => $this->get_parfume_brand_name($post_id),
                'image' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                'url' => get_permalink($post_id)
            );
        }

        // Търсене в марки
        $brands = get_terms(array(
            'taxonomy' => 'parfume_marki',
            'name__like' => $search_term,
            'number' => 5,
            'hide_empty' => true
        ));

        foreach ($brands as $brand) {
            $suggestions[] = array(
                'type' => 'brand',
                'id' => $brand->term_id,
                'title' => $brand->name,
                'count' => $brand->count,
                'url' => get_term_link($brand)
            );
        }

        // Търсене в нотки
        $notes = get_terms(array(
            'taxonomy' => 'parfume_notes',
            'name__like' => $search_term,
            'number' => 3,
            'hide_empty' => true
        ));

        foreach ($notes as $note) {
            $note_group = get_term_meta($note->term_id, 'note_group', true);
            $suggestions[] = array(
                'type' => 'note',
                'id' => $note->term_id,
                'title' => $note->name,
                'group' => $note_group,
                'count' => $note->count,
                'url' => get_term_link($note)
            );
        }

        wp_send_json_success($suggestions);
    }

    /**
     * Санитизиране на filter данни
     */
    private function sanitize_filter_data($data) {
        $sanitized = array(
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
            'longevity' => isset($data['longevity']) ? array_map('intval', (array)$data['longevity']) : array(),
            'sillage' => isset($data['sillage']) ? array_map('intval', (array)$data['sillage']) : array(),
            'launch_year_min' => isset($data['launch_year_min']) ? intval($data['launch_year_min']) : 0,
            'launch_year_max' => isset($data['launch_year_max']) ? intval($data['launch_year_max']) : 0,
            'suitable_day' => isset($data['suitable_day']) ? 1 : 0,
            'suitable_night' => isset($data['suitable_night']) ? 1 : 0,
            'has_image' => isset($data['has_image']) ? 1 : 0,
            'orderby' => isset($data['orderby']) ? sanitize_text_field($data['orderby']) : 'date',
            'order' => isset($data['order']) ? sanitize_text_field($data['order']) : 'DESC',
            'per_page' => isset($data['per_page']) ? intval($data['per_page']) : 12,
            'paged' => isset($data['paged']) ? intval($data['paged']) : 1,
            'view_mode' => isset($data['view_mode']) ? sanitize_text_field($data['view_mode']) : 'grid'
        );

        return $sanitized;
    }

    /**
     * Изграждане на WP_Query от филтри
     */
    private function build_filter_query($filters) {
        $query_args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => $filters['per_page'],
            'paged' => $filters['paged']
        );

        // Search
        if (!empty($filters['search'])) {
            $query_args['s'] = $filters['search'];
        }

        // Tax queries
        $tax_queries = array();

        if (!empty($filters['type'])) {
            $tax_queries[] = array(
                'taxonomy' => 'parfume_type',
                'field' => 'slug',
                'terms' => $filters['type'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['vid'])) {
            $tax_queries[] = array(
                'taxonomy' => 'parfume_vid',
                'field' => 'slug',
                'terms' => $filters['vid'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['marki'])) {
            $tax_queries[] = array(
                'taxonomy' => 'parfume_marki',
                'field' => 'slug',
                'terms' => $filters['marki'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['season'])) {
            $tax_queries[] = array(
                'taxonomy' => 'parfume_season',
                'field' => 'slug',
                'terms' => $filters['season'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['intensity'])) {
            $tax_queries[] = array(
                'taxonomy' => 'parfume_intensity',
                'field' => 'slug',
                'terms' => $filters['intensity'],
                'operator' => 'IN'
            );
        }

        if (!empty($filters['notes'])) {
            $tax_queries[] = array(
                'taxonomy' => 'parfume_notes',
                'field' => 'slug',
                'terms' => $filters['notes'],
                'operator' => 'IN'
            );
        }

        if (!empty($tax_queries)) {
            $query_args['tax_query'] = array('relation' => 'AND') + $tax_queries;
        }

        // Meta queries
        $meta_queries = array();

        if ($filters['rating_min'] > 0) {
            $meta_queries[] = array(
                'key' => '_parfume_average_rating',
                'value' => $filters['rating_min'],
                'type' => 'DECIMAL',
                'compare' => '>='
            );
        }

        if (!empty($filters['longevity'])) {
            $meta_queries[] = array(
                'key' => '_parfume_longevity',
                'value' => $filters['longevity'],
                'type' => 'NUMERIC',
                'compare' => 'IN'
            );
        }

        if (!empty($filters['sillage'])) {
            $meta_queries[] = array(
                'key' => '_parfume_sillage',
                'value' => $filters['sillage'],
                'type' => 'NUMERIC',
                'compare' => 'IN'
            );
        }

        if ($filters['launch_year_min'] > 0) {
            $meta_queries[] = array(
                'key' => '_parfume_launch_year',
                'value' => $filters['launch_year_min'],
                'type' => 'NUMERIC',
                'compare' => '>='
            );
        }

        if ($filters['launch_year_max'] > 0) {
            $meta_queries[] = array(
                'key' => '_parfume_launch_year',
                'value' => $filters['launch_year_max'],
                'type' => 'NUMERIC',
                'compare' => '<='
            );
        }

        if ($filters['suitable_day']) {
            $meta_queries[] = array(
                'key' => '_parfume_suitable_day',
                'value' => '1',
                'compare' => '='
            );
        }

        if ($filters['suitable_night']) {
            $meta_queries[] = array(
                'key' => '_parfume_suitable_night',
                'value' => '1',
                'compare' => '='
            );
        }

        if ($filters['has_image']) {
            $meta_queries[] = array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            );
        }

        if (!empty($meta_queries)) {
            $query_args['meta_query'] = array('relation' => 'AND') + $meta_queries;
        }

        // Price filtering (от scraper данни)
        if ($filters['price_min'] > 0 || $filters['price_max'] > 0) {
            $query_args = $this->add_price_filter($query_args, $filters['price_min'], $filters['price_max']);
        }

        // Ordering
        $query_args = $this->add_orderby($query_args, $filters['orderby'], $filters['order']);

        return $query_args;
    }

    /**
     * Добавяне на ценови филтър
     */
    private function add_price_filter($query_args, $min_price, $max_price) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $price_conditions = array();
        
        if ($min_price > 0) {
            $price_conditions[] = "price >= " . floatval($min_price);
        }
        
        if ($max_price > 0) {
            $price_conditions[] = "price <= " . floatval($max_price);
        }
        
        if (!empty($price_conditions)) {
            $price_where = implode(' AND ', $price_conditions);
            $post_ids = $wpdb->get_col("
                SELECT DISTINCT post_id 
                FROM $scraper_table 
                WHERE price IS NOT NULL 
                AND $price_where
            ");
            
            if (!empty($post_ids)) {
                $query_args['post__in'] = $post_ids;
            } else {
                $query_args['post__in'] = array(0); // Няма резултати
            }
        }
        
        return $query_args;
    }

    /**
     * Добавяне на сортиране
     */
    private function add_orderby($query_args, $orderby, $order) {
        switch ($orderby) {
            case 'title_asc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
            
            case 'title_desc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'DESC';
                break;
                
            case 'date_asc':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'ASC';
                break;
                
            case 'date_desc':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
                
            case 'rating_desc':
                $query_args['meta_key'] = '_parfume_average_rating';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
                
            case 'price_asc':
            case 'price_desc':
                $query_args = $this->add_price_ordering($query_args, $orderby);
                break;
                
            case 'popularity':
                $query_args['meta_key'] = '_parfume_view_count';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
                
            default:
                $query_args['orderby'] = $orderby;
                $query_args['order'] = $order;
        }
        
        return $query_args;
    }

    /**
     * Добавяне на ценово сортиране
     */
    private function add_price_ordering($query_args, $orderby) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $order = $orderby === 'price_asc' ? 'ASC' : 'DESC';
        
        // Използване на custom ordering чрез posts_clauses filter
        add_filter('posts_clauses', function($clauses, $query) use ($wpdb, $scraper_table, $order) {
            if (isset($query->query_vars['_price_ordering'])) {
                $clauses['join'] .= " LEFT JOIN (
                    SELECT post_id, MIN(price) as min_price 
                    FROM $scraper_table 
                    WHERE price IS NOT NULL AND price > 0 
                    GROUP BY post_id
                ) ps ON {$wpdb->posts}.ID = ps.post_id";
                
                $clauses['orderby'] = "ps.min_price $order";
            }
            return $clauses;
        }, 10, 2);
        
        $query_args['_price_ordering'] = true;
        
        return $query_args;
    }

    /**
     * Рендериране на парфюм item
     */
    private function render_parfume_item($post_id, $view_mode = 'grid') {
        $template = $view_mode === 'list' ? 'list-item' : 'grid-item';
        
        if ($view_mode === 'list') {
            $this->render_list_item($post_id);
        } else {
            $this->render_grid_item($post_id);
        }
    }

    /**
     * Рендериране на grid item
     */
    private function render_grid_item($post_id) {
        $brand = $this->get_parfume_brand_name($post_id);
        $average_rating = get_post_meta($post_id, '_parfume_average_rating', true);
        $price_range = $this->get_parfume_price_range($post_id);
        
        ?>
        <div class="parfume-grid-item" data-parfume-id="<?php echo esc_attr($post_id); ?>">
            <div class="parfume-image">
                <a href="<?php echo get_permalink($post_id); ?>">
                    <?php echo get_the_post_thumbnail($post_id, 'medium', array('alt' => get_the_title($post_id))); ?>
                </a>
                <div class="parfume-overlay">
                    <button type="button" class="parfume-comparison-btn" data-parfume-id="<?php echo esc_attr($post_id); ?>">
                        <?php _e('Добави за сравнение', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            <div class="parfume-content">
                <?php if ($brand): ?>
                    <div class="parfume-brand"><?php echo esc_html($brand); ?></div>
                <?php endif; ?>
                <h3 class="parfume-title">
                    <a href="<?php echo get_permalink($post_id); ?>"><?php echo get_the_title($post_id); ?></a>
                </h3>
                <?php if ($average_rating): ?>
                    <div class="parfume-rating">
                        <?php echo $this->render_stars($average_rating); ?>
                        <span class="rating-text">(<?php echo esc_html($average_rating); ?>)</span>
                    </div>
                <?php endif; ?>
                <?php if ($price_range): ?>
                    <div class="parfume-price"><?php echo esc_html($price_range); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Рендериране на list item
     */
    private function render_list_item($post_id) {
        $brand = $this->get_parfume_brand_name($post_id);
        $average_rating = get_post_meta($post_id, '_parfume_average_rating', true);
        $price_range = $this->get_parfume_price_range($post_id);
        $excerpt = wp_trim_words(get_the_excerpt($post_id), 20);
        
        ?>
        <div class="parfume-list-item" data-parfume-id="<?php echo esc_attr($post_id); ?>">
            <div class="parfume-image">
                <a href="<?php echo get_permalink($post_id); ?>">
                    <?php echo get_the_post_thumbnail($post_id, 'thumbnail', array('alt' => get_the_title($post_id))); ?>
                </a>
            </div>
            <div class="parfume-content">
                <div class="parfume-header">
                    <?php if ($brand): ?>
                        <div class="parfume-brand"><?php echo esc_html($brand); ?></div>
                    <?php endif; ?>
                    <h3 class="parfume-title">
                        <a href="<?php echo get_permalink($post_id); ?>"><?php echo get_the_title($post_id); ?></a>
                    </h3>
                </div>
                <?php if ($excerpt): ?>
                    <div class="parfume-excerpt"><?php echo esc_html($excerpt); ?></div>
                <?php endif; ?>
                <div class="parfume-meta">
                    <?php if ($average_rating): ?>
                        <div class="parfume-rating">
                            <?php echo $this->render_stars($average_rating); ?>
                            <span class="rating-text">(<?php echo esc_html($average_rating); ?>)</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($price_range): ?>
                        <div class="parfume-price"><?php echo esc_html($price_range); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="parfume-actions">
                <button type="button" class="parfume-comparison-btn" data-parfume-id="<?php echo esc_attr($post_id); ?>">
                    <?php _e('Добави за сравнение', 'parfume-catalog'); ?>
                </button>
                <a href="<?php echo get_permalink($post_id); ?>" class="button view-details">
                    <?php _e('Виж детайли', 'parfume-catalog'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Генериране на AJAX pagination
     */
    private function generate_ajax_pagination($query, $filters) {
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $current_page = $filters['paged'];
        $total_pages = $query->max_num_pages;
        
        $pagination = '<div class="parfume-pagination">';
        
        // Previous button
        if ($current_page > 1) {
            $pagination .= '<button type="button" class="pagination-btn prev-page" data-page="' . ($current_page - 1) . '">';
            $pagination .= '<span>&laquo;</span> ' . __('Предишна', 'parfume-catalog');
            $pagination .= '</button>';
        }
        
        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            $pagination .= '<button type="button" class="pagination-btn page-number" data-page="1">1</button>';
            if ($start_page > 2) {
                $pagination .= '<span class="pagination-dots">...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $current_class = ($i === $current_page) ? ' current' : '';
            $pagination .= '<button type="button" class="pagination-btn page-number' . $current_class . '" data-page="' . $i . '">' . $i . '</button>';
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $pagination .= '<span class="pagination-dots">...</span>';
            }
            $pagination .= '<button type="button" class="pagination-btn page-number" data-page="' . $total_pages . '">' . $total_pages . '</button>';
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $pagination .= '<button type="button" class="pagination-btn next-page" data-page="' . ($current_page + 1) . '">';
            $pagination .= __('Следваща', 'parfume-catalog') . ' <span>&raquo;</span>';
            $pagination .= '</button>';
        }
        
        $pagination .= '</div>';
        
        return $pagination;
    }

    /**
     * Модификация на main query
     */
    public function modify_main_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfumes') || is_tax(array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'))) {
                // Default ordering
                if (!isset($_GET['orderby'])) {
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                }

                // Posts per page
                $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 12;
                $query->set('posts_per_page', $per_page);

                // Meta query за thumbnail
                $meta_query = $query->get('meta_query') ?: array();
                $meta_query[] = array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                );
                $query->set('meta_query', $meta_query);
            }
        }
    }

    /**
     * Custom search clauses
     */
    public function custom_search_clauses($clauses, $query) {
        if (!is_admin() && $query->is_search() && $query->is_main_query() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'parfumes') {
            global $wpdb;
            
            $search_term = $query->get('s');
            if (empty($search_term)) {
                return $clauses;
            }
            
            // Търсене в заглавие, съдържание и мета полета
            $search_term_like = '%' . $wpdb->esc_like($search_term) . '%';
            
            // Custom WHERE клауза
            $clauses['where'] = str_replace(
                "({$wpdb->posts}.post_title LIKE '{$search_term_like}')",
                "({$wpdb->posts}.post_title LIKE '{$search_term_like}' 
                 OR {$wpdb->posts}.post_content LIKE '{$search_term_like}'
                 OR EXISTS (
                     SELECT 1 FROM {$wpdb->postmeta} pm 
                     WHERE pm.post_id = {$wpdb->posts}.ID 
                     AND pm.meta_value LIKE '{$search_term_like}'
                 )
                 OR EXISTS (
                     SELECT 1 FROM {$wpdb->term_relationships} tr
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                     INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                     WHERE tr.object_id = {$wpdb->posts}.ID
                     AND t.name LIKE '{$search_term_like}'
                 ))",
                $clauses['where']
            );
        }
        
        return $clauses;
    }

    /**
     * Helper функции
     */
    private function get_parfume_brand_name($post_id) {
        $brands = wp_get_object_terms($post_id, 'parfume_marki');
        return !empty($brands) ? $brands[0]->name : '';
    }

    private function get_parfume_price_range($post_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $prices = $wpdb->get_results($wpdb->prepare(
            "SELECT price FROM $scraper_table WHERE post_id = %d AND price IS NOT NULL AND price > 0",
            $post_id
        ));
        
        if (empty($prices)) {
            return '';
        }
        
        $price_values = wp_list_pluck($prices, 'price');
        $min_price = min($price_values);
        $max_price = max($price_values);
        
        if ($min_price === $max_price) {
            return sprintf('%.2f лв.', $min_price);
        }
        
        return sprintf('%.2f - %.2f лв.', $min_price, $max_price);
    }

    private function render_stars($rating, $max_rating = 5) {
        $output = '<div class="rating-stars">';
        
        for ($i = 1; $i <= $max_rating; $i++) {
            $class = $i <= $rating ? 'star-filled' : 'star-empty';
            $output .= '<span class="star ' . $class . '">★</span>';
        }
        
        $output .= '</div>';
        return $output;
    }

    private function get_current_filter_url() {
        return home_url(add_query_arg(array(), $GLOBALS['wp']->request));
    }

    private function get_applied_filters_count($filters) {
        $count = 0;
        
        if (!empty($filters['search'])) $count++;
        if (!empty($filters['type'])) $count++;
        if (!empty($filters['vid'])) $count++;
        if (!empty($filters['marki'])) $count++;
        if (!empty($filters['season'])) $count++;
        if (!empty($filters['intensity'])) $count++;
        if (!empty($filters['notes'])) $count++;
        if ($filters['price_min'] > 0 || $filters['price_max'] > 0) $count++;
        if ($filters['rating_min'] > 0) $count++;
        if (!empty($filters['longevity'])) $count++;
        if (!empty($filters['sillage'])) $count++;
        if ($filters['launch_year_min'] > 0 || $filters['launch_year_max'] > 0) $count++;
        if ($filters['suitable_day'] || $filters['suitable_night']) $count++;
        
        return $count;
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
        include PARFUME_CATALOG_PLUGIN_DIR . 'templates/filters-form.php';
        return ob_get_clean();
    }

    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Търси парфюми...', 'parfume-catalog'),
            'suggestions' => 'yes',
            'class' => ''
        ), $atts);

        ob_start();
        include PARFUME_CATALOG_PLUGIN_DIR . 'templates/search-form.php';
        return ob_get_clean();
    }

    public function parfume_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'type' => '',
            'marki' => '',
            'season' => '',
            'view_mode' => 'grid',
            'show_filters' => 'no',
            'ajax' => 'yes'
        ), $atts);

        // Изграждане на query
        $query_args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['per_page']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            )
        );

        // Филтри от shortcode атрибути
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
        
        if (!empty($atts['season'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_season',
                'field' => 'slug',
                'terms' => explode(',', $atts['season'])
            );
        }
        
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($query_args);
        
        ob_start();
        ?>
        <div class="parfume-list-shortcode" data-ajax="<?php echo esc_attr($atts['ajax']); ?>">
            <?php if ($atts['show_filters'] === 'yes'): ?>
                <div class="parfume-filters-wrapper">
                    <?php echo do_shortcode('[parfume_filters]'); ?>
                </div>
            <?php endif; ?>
            
            <div class="parfume-results-container">
                <div class="parfume-results" data-view-mode="<?php echo esc_attr($atts['view_mode']); ?>">
                    <?php
                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            $this->render_parfume_item(get_the_ID(), $atts['view_mode']);
                        }
                    } else {
                        echo '<div class="no-parfumes-found">' . __('Няма намерени парфюми.', 'parfume-catalog') . '</div>';
                    }
                    ?>
                </div>
                
                <?php if ($query->max_num_pages > 1): ?>
                    <div class="parfume-pagination-wrapper">
                        <?php echo $this->generate_ajax_pagination($query, array('paged' => 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Individual filter shortcodes
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
     * Рендериране на taxonomy филтър
     */
    private function render_taxonomy_filter($taxonomy, $atts) {
        $atts = shortcode_atts(array(
            'show_count' => 'yes',
            'hide_empty' => 'yes',
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => 0,
            'style' => 'checkboxes', // checkboxes, dropdown, list
            'class' => ''
        ), $atts);

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => $atts['hide_empty'] === 'yes',
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'number' => intval($atts['number'])
        ));

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        $taxonomy_obj = get_taxonomy($taxonomy);
        $filter_name = str_replace('parfume_', '', $taxonomy);

        ob_start();
        ?>
        <div class="parfume-filter parfume-filter-<?php echo esc_attr($filter_name); ?> <?php echo esc_attr($atts['class']); ?>">
            <h4 class="filter-title"><?php echo esc_html($taxonomy_obj->labels->name); ?></h4>
            
            <?php if ($atts['style'] === 'dropdown'): ?>
                <select name="<?php echo esc_attr($filter_name); ?>[]" multiple class="parfume-filter-select">
                    <?php foreach ($terms as $term): ?>
                        <option value="<?php echo esc_attr($term->slug); ?>">
                            <?php echo esc_html($term->name); ?>
                            <?php if ($atts['show_count'] === 'yes'): ?>
                                (<?php echo $term->count; ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            
            <?php elseif ($atts['style'] === 'list'): ?>
                <ul class="filter-list">
                    <?php foreach ($terms as $term): ?>
                        <li>
                            <a href="<?php echo get_term_link($term); ?>" class="filter-link">
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
     * CSS за filters
     */
    private function get_filters_css() {
        return '
        .parfume-filters {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .parfume-results {
            display: grid;
            gap: 20px;
        }
        
        .parfume-results[data-view-mode="grid"] {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
        
        .parfume-results[data-view-mode="list"] {
            grid-template-columns: 1fr;
        }
        
        .parfume-grid-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .parfume-grid-item:hover {
            transform: translateY(-5px);
        }
        
        .parfume-list-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .parfume-image {
            position: relative;
            overflow: hidden;
        }
        
        .parfume-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .parfume-grid-item:hover .parfume-overlay {
            opacity: 1;
        }
        
        .parfume-pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover,
        .pagination-btn.current {
            background: #007cba;
            color: white;
            border-color: #007cba;
        }
        
        @media (max-width: 768px) {
            .parfume-results[data-view-mode="grid"] {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
            
            .parfume-list-item {
                flex-direction: column;
                text-align: center;
            }
        }
        ';
    }
}