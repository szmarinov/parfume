<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Query Handler - управлява query operations за parfume post type
 * COMPLETE VERSION: Пълна версия с всички функционалности
 * FIXED: Поправена синтактична грешка на ред 58
 */
class Query_Handler {
    
    /**
     * Filtering параметри
     */
    private $filter_params = array(
        'filter_brand' => 'marki',
        'filter_gender' => 'gender', 
        'filter_aroma_type' => 'aroma_type',
        'filter_season' => 'season',
        'filter_intensity' => 'intensity',
        'filter_notes' => 'notes',
        'filter_perfumer' => 'perfumer'
    );
    
    /**
     * Sorting опции
     */
    private $sort_options = array(
        'date' => 'date',
        'title' => 'title',
        'rating' => 'rating',
        'price' => 'price',
        'popularity' => 'popularity'
    );
    
    public function __construct() {
        add_action('pre_get_posts', array($this, 'modify_main_query'));
        add_filter('posts_clauses', array($this, 'custom_posts_clauses'), 10, 2);
        add_action('wp', array($this, 'handle_custom_queries'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_request'));
        
        // Search functionality
        add_filter('posts_join', array($this, 'search_join'), 10, 2);
        add_filter('posts_where', array($this, 'search_where'), 10, 2);
        add_filter('posts_distinct', array($this, 'search_distinct'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_parfume_filter_posts', array($this, 'ajax_filter_posts'));
        add_action('wp_ajax_nopriv_parfume_filter_posts', array($this, 'ajax_filter_posts'));
        add_action('wp_ajax_parfume_load_more', array($this, 'ajax_load_more'));
        add_action('wp_ajax_nopriv_parfume_load_more', array($this, 'ajax_load_more'));
        
        // Debug hooks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp', array($this, 'debug_query_vars'));
            add_action('wp_footer', array($this, 'debug_output'));
        }
    }
    
    /**
     * Модифицира main query
     */
    public function modify_main_query($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // За parfume archive страница
        if (is_post_type_archive('parfume')) {
            $settings = get_option('parfume_reviews_settings', array());
            $posts_per_page = isset($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 12;
            
            $query->set('posts_per_page', $posts_per_page);
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
            
            // Прилагаме филтри
            $this->apply_filters_to_query($query);
            
            // Прилагаме sorting
            $this->apply_sorting_to_query($query);
        }
        
        // За taxonomy страници
        if (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $settings = get_option('parfume_reviews_settings', array());
            $posts_per_page = isset($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 12;
            
            $query->set('posts_per_page', $posts_per_page);
            $query->set('post_type', 'parfume');
            
            // Прилагаме допълнителни филтри
            $this->apply_filters_to_query($query);
            
            // Прилагаме sorting
            $this->apply_sorting_to_query($query);
        }
        
        // Специално обработване за perfumer archive
        if (isset($query->query_vars['perfumer_archive']) && $query->query_vars['perfumer_archive'] === '1') {
            $query->set('post_type', 'parfume');
            $query->set('posts_per_page', 12);
            
            // Получаваме всички parfumes с perfumer taxonomy
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'perfumer',
                    'operator' => 'EXISTS'
                )
            ));
            
            // Прилагаме филтри
            $this->apply_filters_to_query($query);
        }
        
        // Search queries
        if ($query->is_search() && !is_admin()) {
            $this->modify_search_query($query);
        }
    }
    
    /**
     * Прилага филтри към query
     */
    private function apply_filters_to_query($query) {
        $tax_query = array('relation' => 'AND');
        $meta_query = array('relation' => 'AND');
        $has_tax_filters = false;
        $has_meta_filters = false;
        
        // Taxonomy filters
        foreach ($this->filter_params as $param => $taxonomy) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $values = is_array($_GET[$param]) ? $_GET[$param] : array($_GET[$param]);
                $values = array_map('sanitize_text_field', $values);
                
                if (!empty($values)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $values,
                        'operator' => 'IN'
                    );
                    $has_tax_filters = true;
                }
            }
        }
        
        // Price filtering
        if (isset($_GET['min_price']) || isset($_GET['max_price'])) {
            if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
                $meta_query[] = array(
                    'key' => '_parfume_price',
                    'value' => floatval($_GET['min_price']),
                    'compare' => '>=',
                    'type' => 'DECIMAL(10,2)'
                );
                $has_meta_filters = true;
            }
            
            if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
                $meta_query[] = array(
                    'key' => '_parfume_price',
                    'value' => floatval($_GET['max_price']),
                    'compare' => '<=',
                    'type' => 'DECIMAL(10,2)'
                );
                $has_meta_filters = true;
            }
        }
        
        // Rating filtering
        if (isset($_GET['min_rating']) && !empty($_GET['min_rating'])) {
            $meta_query[] = array(
                'key' => '_parfume_rating',
                'value' => floatval($_GET['min_rating']),
                'compare' => '>=',
                'type' => 'DECIMAL(3,1)'
            );
            $has_meta_filters = true;
        }
        
        // Year filtering
        if (isset($_GET['release_year']) && !empty($_GET['release_year'])) {
            $meta_query[] = array(
                'key' => '_parfume_release_year',
                'value' => intval($_GET['release_year']),
                'compare' => '=',
                'type' => 'NUMERIC'
            );
            $has_meta_filters = true;
        }
        
        // Availability filtering
        if (isset($_GET['availability']) && !empty($_GET['availability'])) {
            $meta_query[] = array(
                'key' => '_parfume_availability',
                'value' => sanitize_text_field($_GET['availability']),
                'compare' => '='
            );
            $has_meta_filters = true;
        }
        
        // Apply filters to query
        if ($has_tax_filters) {
            $existing_tax_query = $query->get('tax_query');
            if (!empty($existing_tax_query)) {
                $tax_query = array_merge($existing_tax_query, $tax_query);
            }
            $query->set('tax_query', $tax_query);
        }
        
        if ($has_meta_filters) {
            $existing_meta_query = $query->get('meta_query');
            if (!empty($existing_meta_query)) {
                $meta_query = array_merge($existing_meta_query, $meta_query);
            }
            $query->set('meta_query', $meta_query);
        }
    }
    
    /**
     * Прилага sorting към query
     */
    private function apply_sorting_to_query($query) {
        if (!isset($_GET['orderby']) || empty($_GET['orderby'])) {
            return;
        }
        
        $orderby = sanitize_text_field($_GET['orderby']);
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
        $order = in_array(strtoupper($order), array('ASC', 'DESC')) ? strtoupper($order) : 'DESC';
        
        switch ($orderby) {
            case 'date':
                $query->set('orderby', 'date');
                $query->set('order', $order);
                break;
                
            case 'title':
                $query->set('orderby', 'title');
                $query->set('order', $order);
                break;
                
            case 'rating':
                $query->set('orderby', 'meta_value_num');
                $query->set('meta_key', '_parfume_rating');
                $query->set('order', $order);
                break;
                
            case 'price':
                $query->set('orderby', 'meta_value_num');
                $query->set('meta_key', '_parfume_price');
                $query->set('order', $order);
                break;
                
            case 'popularity':
                $query->set('orderby', 'meta_value_num');
                $query->set('meta_key', '_parfume_popularity_score');
                $query->set('order', $order);
                break;
                
            case 'random':
                $query->set('orderby', 'rand');
                break;
        }
    }
    
    /**
     * Модифицира posts clauses за custom sorting
     */
    public function custom_posts_clauses($clauses, $query) {
        if (is_admin() || !$query->is_main_query()) {
            return $clauses;
        }
        
        // За parfume archive или taxonomy страници
        if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            global $wpdb;
            
            // Custom sorting logic
            if (isset($_GET['orderby'])) {
                $orderby = sanitize_text_field($_GET['orderby']);
                
                switch ($orderby) {
                    case 'rating':
                        if (strpos($clauses['join'], 'rating_meta') === false) {
                            $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS rating_meta ON {$wpdb->posts}.ID = rating_meta.post_id AND rating_meta.meta_key = '_parfume_rating'";
                        }
                        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
                        $clauses['orderby'] = "CAST(rating_meta.meta_value AS DECIMAL(3,1)) {$order}";
                        break;
                        
                    case 'price':
                        if (strpos($clauses['join'], 'price_meta') === false) {
                            $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS price_meta ON {$wpdb->posts}.ID = price_meta.post_id AND price_meta.meta_key = '_parfume_price'";
                        }
                        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
                        $clauses['orderby'] = "CAST(price_meta.meta_value AS DECIMAL(10,2)) {$order}";
                        break;
                        
                    case 'popularity':
                        if (strpos($clauses['join'], 'popularity_meta') === false) {
                            $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS popularity_meta ON {$wpdb->posts}.ID = popularity_meta.post_id AND popularity_meta.meta_key = '_parfume_popularity_score'";
                        }
                        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
                        $clauses['orderby'] = "CAST(popularity_meta.meta_value AS DECIMAL(10,2)) {$order}";
                        break;
                }
            }
            
            // Додаваме DISTINCT ако има taxonomy filtering
            if ($this->has_active_filters()) {
                $clauses['distinct'] = 'DISTINCT';
            }
        }
        
        return $clauses;
    }
    
    /**
     * Модифицира search queries
     */
    private function modify_search_query($query) {
        if (!$query->is_search()) {
            return;
        }
        
        // Ограничаваме търсенето само до parfume posts
        $query->set('post_type', 'parfume');
        
        // Добавяме meta fields в търсенето
        add_filter('posts_search', array($this, 'extend_search'), 10, 2);
    }
    
    /**
     * Разширява search functionality
     */
    public function extend_search($search, $query) {
        if (!$query->is_search() || empty($search)) {
            return $search;
        }
        
        global $wpdb;
        
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }
        
        // Търсим в title, content, excerpt, и meta fields
        $search_term = $wpdb->esc_like($search_term);
        
        $search = "AND (
            ({$wpdb->posts}.post_title LIKE '%{$search_term}%')
            OR ({$wpdb->posts}.post_content LIKE '%{$search_term}%')
            OR ({$wpdb->posts}.post_excerpt LIKE '%{$search_term}%')
            OR (meta_search.meta_value LIKE '%{$search_term}%')
            OR (tax_search.name LIKE '%{$search_term}%')
        )";
        
        return $search;
    }
    
    /**
     * JOIN за search в meta fields и taxonomies
     */
    public function search_join($join, $query) {
        if (!$query->is_search()) {
            return $join;
        }
        
        global $wpdb;
        
        // JOIN за meta fields
        $join .= " LEFT JOIN {$wpdb->postmeta} AS meta_search ON {$wpdb->posts}.ID = meta_search.post_id";
        
        // JOIN за taxonomies
        $join .= " LEFT JOIN {$wpdb->term_relationships} AS tr_search ON {$wpdb->posts}.ID = tr_search.object_id";
        $join .= " LEFT JOIN {$wpdb->term_taxonomy} AS tt_search ON tr_search.term_taxonomy_id = tt_search.term_taxonomy_id";
        $join .= " LEFT JOIN {$wpdb->terms} AS tax_search ON tt_search.term_id = tax_search.term_id";
        
        return $join;
    }
    
    /**
     * WHERE условия за search
     */
    public function search_where($where, $query) {
        if (!$query->is_search()) {
            return $where;
        }
        
        global $wpdb;
        
        // Ограничаваме meta search само до важни полета
        $meta_keys = array(
            '_parfume_brand',
            '_parfume_year',
            '_parfume_concentration',
            '_parfume_longevity',
            '_parfume_sillage'
        );
        
        $meta_keys_sql = "'" . implode("','", $meta_keys) . "'";
        $where .= " AND (meta_search.meta_key IS NULL OR meta_search.meta_key IN ({$meta_keys_sql}))";
        
        // Ограничаваме taxonomy search само до parfume taxonomies
        $taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        $taxonomies_sql = "'" . implode("','", $taxonomies) . "'";
        $where .= " AND (tt_search.taxonomy IS NULL OR tt_search.taxonomy IN ({$taxonomies_sql}))";
        
        return $where;
    }
    
    /**
     * DISTINCT за search results
     */
    public function search_distinct($distinct, $query) {
        if ($query->is_search()) {
            return 'DISTINCT';
        }
        
        return $distinct;
    }
    
    /**
     * Обработва custom queries
     */
    public function handle_custom_queries() {
        global $wp_query;
        
        // Обработка на perfumer archive
        if (isset($wp_query->query_vars['perfumer_archive']) && $wp_query->query_vars['perfumer_archive'] === '1') {
            
            // Задаваме правилни query флагове
            $wp_query->is_tax = true;
            $wp_query->is_taxonomy = true;
            $wp_query->is_archive = true;
            $wp_query->is_home = false;
            $wp_query->is_front_page = false;
            
            // Задаваме queried object за perfumer taxonomy
            $perfumer_taxonomy = get_taxonomy('perfumer');
            if ($perfumer_taxonomy) {
                $wp_query->queried_object = $perfumer_taxonomy;
                $wp_query->queried_object_id = 0;
            }
            
            // Debug лог
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Query Handler: Handling perfumer archive query');
            }
        }
        
        // Обработка на custom filtering и sorting
        $this->handle_filtering_display();
    }
    
    /**
     * Обработва display на активни филтри
     */
    private function handle_filtering_display() {
        if (!is_post_type_archive('parfume') && !is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            return;
        }
        
        // Записваме активните филтри в глобална променлива за лесен достъп
        $GLOBALS['parfume_active_filters'] = $this->get_active_filters();
        $GLOBALS['parfume_active_sorting'] = $this->get_active_sorting();
    }
    
    /**
     * Добавя custom query vars
     */
    public function add_query_vars($vars) {
        $custom_vars = array(
            'perfumer_archive',
            'parfume_taxonomy_archive',
            'parfume_ajax_load',
            'parfume_filter_ajax'
        );
        
        return array_merge($vars, $custom_vars);
    }
    
    /**
     * Parse request за custom URLs
     */
    public function parse_request($wp) {
        // Обработка на AJAX filtering requests
        if (isset($wp->query_vars['parfume_filter_ajax'])) {
            $this->handle_ajax_filtering();
            exit;
        }
        
        // Обработка на load more requests
        if (isset($wp->query_vars['parfume_ajax_load'])) {
            $this->handle_ajax_load_more();
            exit;
        }
    }
    
    /**
     * AJAX filtering handler
     */
    public function ajax_filter_posts() {
        check_ajax_referer('parfume_ajax_nonce', 'nonce');
        
        $filters = $_POST['filters'];
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 12;
        
        // Validate и sanitize filters
        $clean_filters = array();
        foreach ($filters as $key => $value) {
            if (array_key_exists($key, $this->filter_params) || in_array($key, array('min_price', 'max_price', 'min_rating', 'orderby', 'order'))) {
                if (is_array($value)) {
                    $clean_filters[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $clean_filters[$key] = sanitize_text_field($value);
                }
            }
        }
        
        // Build query args
        $query_args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'post_status' => 'publish'
        );
        
        // Apply filters to query args
        $this->apply_filters_to_query_args($query_args, $clean_filters);
        
        // Execute query
        $query = new \WP_Query($query_args);
        
        $response = array(
            'posts' => array(),
            'found_posts' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => $page
        );
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $response['posts'][] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'excerpt' => get_the_excerpt(),
                    'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'rating' => get_post_meta(get_the_ID(), '_parfume_rating', true),
                    'price' => get_post_meta(get_the_ID(), '_parfume_price', true)
                );
            }
        }
        
        wp_reset_postdata();
        wp_send_json_success($response);
    }
    
    /**
     * AJAX load more handler
     */
    public function ajax_load_more() {
        check_ajax_referer('parfume_ajax_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $query_vars = isset($_POST['query_vars']) ? $_POST['query_vars'] : array();
        
        // Recreate the query
        $query_args = array_merge($query_vars, array(
            'paged' => $page
        ));
        
        $query = new \WP_Query($query_args);
        
        $response = array(
            'posts' => array(),
            'has_more' => false
        );
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                ob_start();
                // Include template part for post card
                if (function_exists('parfume_reviews_display_parfume_card')) {
                    parfume_reviews_display_parfume_card(get_the_ID());
                } else {
                    echo '<div class="parfume-card">Post ID: ' . get_the_ID() . '</div>';
                }
                $post_html = ob_get_clean();
                
                $response['posts'][] = $post_html;
            }
            
            $response['has_more'] = ($page < $query->max_num_pages);
        }
        
        wp_reset_postdata();
        wp_send_json_success($response);
    }
    
    /**
     * Прилага филтри към query args
     */
    private function apply_filters_to_query_args(&$query_args, $filters) {
        $tax_query = array('relation' => 'AND');
        $meta_query = array('relation' => 'AND');
        
        // Taxonomy filters
        foreach ($this->filter_params as $param => $taxonomy) {
            if (isset($filters[$param]) && !empty($filters[$param])) {
                $values = is_array($filters[$param]) ? $filters[$param] : array($filters[$param]);
                
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $values,
                    'operator' => 'IN'
                );
            }
        }
        
        // Meta filters
        if (isset($filters['min_price']) && !empty($filters['min_price'])) {
            $meta_query[] = array(
                'key' => '_parfume_price',
                'value' => floatval($filters['min_price']),
                'compare' => '>=',
                'type' => 'DECIMAL(10,2)'
            );
        }
        
        if (isset($filters['max_price']) && !empty($filters['max_price'])) {
            $meta_query[] = array(
                'key' => '_parfume_price',
                'value' => floatval($filters['max_price']),
                'compare' => '<=',
                'type' => 'DECIMAL(10,2)'
            );
        }
        
        if (isset($filters['min_rating']) && !empty($filters['min_rating'])) {
            $meta_query[] = array(
                'key' => '_parfume_rating',
                'value' => floatval($filters['min_rating']),
                'compare' => '>=',
                'type' => 'DECIMAL(3,1)'
            );
        }
        
        // Apply to query args
        if (count($tax_query) > 1) {
            $query_args['tax_query'] = $tax_query;
        }
        
        if (count($meta_query) > 1) {
            $query_args['meta_query'] = $meta_query;
        }
        
        // Sorting
        if (isset($filters['orderby'])) {
            $orderby = $filters['orderby'];
            $order = isset($filters['order']) ? $filters['order'] : 'DESC';
            
            switch ($orderby) {
                case 'rating':
                    $query_args['orderby'] = 'meta_value_num';
                    $query_args['meta_key'] = '_parfume_rating';
                    $query_args['order'] = $order;
                    break;
                    
                case 'price':
                    $query_args['orderby'] = 'meta_value_num';
                    $query_args['meta_key'] = '_parfume_price';
                    $query_args['order'] = $order;
                    break;
                    
                case 'title':
                    $query_args['orderby'] = 'title';
                    $query_args['order'] = $order;
                    break;
                    
                case 'date':
                default:
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = $order;
                    break;
            }
        }
    }
    
    /**
     * Получава активни филтри
     */
    public function get_active_filters() {
        $filters = array();
        
        foreach ($this->filter_params as $param => $taxonomy) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $values = is_array($_GET[$param]) ? $_GET[$param] : array($_GET[$param]);
                $filters[$taxonomy] = array_map('sanitize_text_field', $values);
            }
        }
        
        // Price filters
        if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
            $filters['min_price'] = floatval($_GET['min_price']);
        }
        
        if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
            $filters['max_price'] = floatval($_GET['max_price']);
        }
        
        // Rating filter
        if (isset($_GET['min_rating']) && !empty($_GET['min_rating'])) {
            $filters['min_rating'] = floatval($_GET['min_rating']);
        }
        
        // Year filter
        if (isset($_GET['release_year']) && !empty($_GET['release_year'])) {
            $filters['release_year'] = intval($_GET['release_year']);
        }
        
        return $filters;
    }
    
    /**
     * Получава активен sorting
     */
    public function get_active_sorting() {
        $sorting = array(
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if (isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $this->sort_options)) {
            $sorting['orderby'] = sanitize_text_field($_GET['orderby']);
        }
        
        if (isset($_GET['order']) && in_array(strtoupper($_GET['order']), array('ASC', 'DESC'))) {
            $sorting['order'] = strtoupper(sanitize_text_field($_GET['order']));
        }
        
        return $sorting;
    }
    
    /**
     * Проверява дали има активни филтри
     */
    public function has_active_filters() {
        $filters = $this->get_active_filters();
        return !empty($filters);
    }
    
    /**
     * Получава URL за премахване на филтър
     */
    public function get_remove_filter_url($filter_type, $filter_value = null) {
        $current_url = home_url($_SERVER['REQUEST_URI']);
        $parsed_url = parse_url($current_url);
        
        if (!isset($parsed_url['query'])) {
            return $current_url;
        }
        
        parse_str($parsed_url['query'], $query_params);
        
        // Find the param name for this filter type
        $param_name = array_search($filter_type, $this->filter_params);
        if (!$param_name) {
            $param_name = $filter_type; // For non-taxonomy filters like min_price
        }
        
        if ($filter_value) {
            // Remove specific value from filter
            if (isset($query_params[$param_name])) {
                if (is_array($query_params[$param_name])) {
                    $query_params[$param_name] = array_diff($query_params[$param_name], array($filter_value));
                    if (empty($query_params[$param_name])) {
                        unset($query_params[$param_name]);
                    }
                } else {
                    if ($query_params[$param_name] === $filter_value) {
                        unset($query_params[$param_name]);
                    }
                }
            }
        } else {
            // Remove entire filter
            unset($query_params[$param_name]);
        }
        
        $new_query = http_build_query($query_params);
        $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        
        if (!empty($new_query)) {
            $new_url .= '?' . $new_query;
        }
        
        return $new_url;
    }
    
    /**
     * Получава URL за добавяне на филтър
     */
    public function get_add_filter_url($filter_type, $filter_value) {
        $current_url = home_url($_SERVER['REQUEST_URI']);
        $parsed_url = parse_url($current_url);
        
        $query_params = array();
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }
        
        // Find the param name for this filter type
        $param_name = array_search($filter_type, $this->filter_params);
        if (!$param_name) {
            $param_name = $filter_type; // For non-taxonomy filters like min_price
        }
        
        if (isset($query_params[$param_name])) {
            if (is_array($query_params[$param_name])) {
                if (!in_array($filter_value, $query_params[$param_name])) {
                    $query_params[$param_name][] = $filter_value;
                }
            } else {
                if ($query_params[$param_name] !== $filter_value) {
                    $query_params[$param_name] = array($query_params[$param_name], $filter_value);
                }
            }
        } else {
            $query_params[$param_name] = $filter_value;
        }
        
        $new_query = http_build_query($query_params);
        $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        
        if (!empty($new_query)) {
            $new_url .= '?' . $new_query;
        }
        
        return $new_url;
    }
    
    /**
     * Получава URL за sorting
     */
    public function get_sort_url($orderby, $order = 'DESC') {
        $current_url = home_url($_SERVER['REQUEST_URI']);
        $parsed_url = parse_url($current_url);
        
        $query_params = array();
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }
        
        $query_params['orderby'] = $orderby;
        $query_params['order'] = $order;
        
        $new_query = http_build_query($query_params);
        $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        
        if (!empty($new_query)) {
            $new_url .= '?' . $new_query;
        }
        
        return $new_url;
    }
    
    /**
     * Получава статистики за query
     */
    public function get_query_stats() {
        global $wp_query;
        
        return array(
            'found_posts' => $wp_query->found_posts,
            'posts_per_page' => $wp_query->get('posts_per_page'),
            'max_num_pages' => $wp_query->max_num_pages,
            'current_page' => max(1, get_query_var('paged')),
            'active_filters' => $this->get_active_filters(),
            'active_sorting' => $this->get_active_sorting()
        );
    }
    
    /**
     * Debug функция за query vars
     */
    public function debug_query_vars() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_query;
        
        if (is_post_type_archive('parfume') || 
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer')) ||
            isset($wp_query->query_vars['perfumer_archive'])) {
            
            error_log('=== PARFUME QUERY DEBUG ===');
            error_log('Current URL: ' . $_SERVER['REQUEST_URI']);
            error_log('Post Type: ' . get_post_type());
            error_log('Is Tax: ' . (is_tax() ? 'yes' : 'no'));
            error_log('Is Archive: ' . (is_archive() ? 'yes' : 'no'));
            error_log('Active Filters: ' . print_r($this->get_active_filters(), true));
            error_log('Active Sorting: ' . print_r($this->get_active_sorting(), true));
            error_log('Query Vars: ' . print_r($wp_query->query_vars, true));
            error_log('Found Posts: ' . $wp_query->found_posts);
            error_log('=== END PARFUME QUERY DEBUG ===');
        }
    }
    
    /**
     * Debug output за frontend
     */
    public function debug_output() {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('manage_options')) {
            return;
        }
        
        if (is_post_type_archive('parfume') || 
            is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            $stats = $this->get_query_stats();
            
            echo '<!-- Parfume Query Debug -->';
            echo '<!-- Found Posts: ' . $stats['found_posts'] . ' -->';
            echo '<!-- Posts Per Page: ' . $stats['posts_per_page'] . ' -->';
            echo '<!-- Max Pages: ' . $stats['max_num_pages'] . ' -->';
            echo '<!-- Current Page: ' . $stats['current_page'] . ' -->';
            echo '<!-- Active Filters: ' . json_encode($stats['active_filters']) . ' -->';
            echo '<!-- Active Sorting: ' . json_encode($stats['active_sorting']) . ' -->';
            echo '<!-- End Parfume Query Debug -->';
        }
    }
    
    /**
     * Handle AJAX filtering (internal method)
     */
    private function handle_ajax_filtering() {
        // Redirect to AJAX handler
        $this->ajax_filter_posts();
    }
    
    /**
     * Handle AJAX load more (internal method)
     */
    private function handle_ajax_load_more() {
        // Redirect to AJAX handler
        $this->ajax_load_more();
    }
}