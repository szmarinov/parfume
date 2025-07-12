<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Query Handler - управлява филтрирането и сортирането на архивните страници
 */
class Query_Handler {
    
    public function __construct() {
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
        add_filter('posts_where', array($this, 'filter_posts_where'), 10, 2);
    }
    
    /**
     * ПОПРАВЕН МЕТОД - ПОЗВОЛЯВА МНОЖЕСТВЕНИ ФИЛТРИ
     */
    public function modify_archive_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                $settings = get_option('parfume_reviews_settings', array());
                $per_page = !empty($settings['archive_posts_per_page']) ? intval($settings['archive_posts_per_page']) : 12;
                $query->set('posts_per_page', $per_page);
                
                // Handle filtering and sorting
                $this->handle_query_filters($query);
                
                // Handle custom sorting
                $this->handle_query_sorting($query);
            }
        }
    }
    
    /**
     * ПОПРАВЕН МЕТОД ЗА ФИЛТРИ - ПОДДЪРЖА МНОЖЕСТВЕНИ КРИТЕРИИ
     */
    private function handle_query_filters($query) {
        // Проверяваме дали има филтърни параметри в URL
        if (empty($_GET)) {
            return;
        }
        
        // Дефинираме поддържаните таксономии
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        $tax_query = array();
        $has_filters = false;
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $has_filters = true;
                
                // Получаваме стойностите и ги декодираме правилно
                $raw_terms = $_GET[$taxonomy];
                $terms = is_array($raw_terms) ? $raw_terms : array($raw_terms);
                
                // Почистваме и декодираме термините
                $clean_terms = array();
                foreach ($terms as $term) {
                    // Декодираме URL encoding
                    $decoded_term = rawurldecode($term);
                    $decoded_term = sanitize_text_field($decoded_term);
                    
                    if (!empty($decoded_term)) {
                        $clean_terms[] = $decoded_term;
                    }
                }
                
                if (!empty($clean_terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $clean_terms,
                        'operator' => 'IN',
                    );
                }
            }
        }
        
        // Проверяваме за ценови филтри
        $meta_query = array();
        $has_meta_filters = false;
        
        if (!empty($_GET['min_price']) || !empty($_GET['max_price'])) {
            $min_price = !empty($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
            $max_price = !empty($_GET['max_price']) ? floatval($_GET['max_price']) : 99999;
            
            if ($min_price > 0 || $max_price < 99999) {
                $has_meta_filters = true;
                $meta_query[] = array(
                    'key' => '_parfume_price',
                    'value' => array($min_price, $max_price),
                    'type' => 'DECIMAL',
                    'compare' => 'BETWEEN',
                );
            }
        }
        
        // Проверяваме за рейтинг филтър
        if (!empty($_GET['min_rating'])) {
            $min_rating = floatval($_GET['min_rating']);
            if ($min_rating > 0) {
                $has_meta_filters = true;
                $meta_query[] = array(
                    'key' => '_parfume_rating',
                    'value' => $min_rating,
                    'type' => 'DECIMAL',
                    'compare' => '>=',
                );
            }
        }
        
        // Прилагаме филтрите към query-то
        if ($has_filters) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $query->set('tax_query', $tax_query);
        }
        
        if ($has_meta_filters) {
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            $query->set('meta_query', $meta_query);
        }
    }
    
    /**
     * Управлява сортирането на резултатите
     */
    private function handle_query_sorting($query) {
        if (!empty($_GET['orderby'])) {
            $orderby = sanitize_key($_GET['orderby']);
            $order = !empty($_GET['order']) ? sanitize_key($_GET['order']) : 'ASC';
            
            switch ($orderby) {
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', $order);
                    break;
                case 'date':
                    $query->set('orderby', 'date');
                    $query->set('order', $order);
                    break;
                case 'rating':
                    $query->set('meta_key', '_parfume_rating');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', $order);
                    break;
                case 'price':
                    $query->set('meta_key', '_parfume_price');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', $order);
                    break;
                case 'popularity':
                    // Сортиране по брой коментари като показател за популярност
                    $query->set('orderby', 'comment_count');
                    $query->set('order', $order);
                    break;
                case 'random':
                    $query->set('orderby', 'rand');
                    break;
            }
        }
    }
    
    /**
     * Custom WHERE clause filter - може да се използва за допълнителни филтри
     */
    public function filter_posts_where($where, $query) {
        // This can be used for custom WHERE clauses if needed
        // Например за търсене в custom fields
        
        return $where;
    }
    
    /**
     * Получава активните филтри от URL параметрите
     */
    public function get_active_filters() {
        $active_filters = array();
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
            }
        }
        
        // Добавяме ценови и рейтинг филтри
        if (!empty($_GET['min_price'])) {
            $active_filters['min_price'] = floatval($_GET['min_price']);
        }
        if (!empty($_GET['max_price'])) {
            $active_filters['max_price'] = floatval($_GET['max_price']);
        }
        if (!empty($_GET['min_rating'])) {
            $active_filters['min_rating'] = floatval($_GET['min_rating']);
        }
        
        return $active_filters;
    }
    
    /**
     * Построява URL за филтри
     */
    public function build_filter_url($filters = array(), $base_url = '') {
        if (empty($base_url)) {
            if (is_post_type_archive('parfume')) {
                $base_url = get_post_type_archive_link('parfume');
            } elseif (is_tax()) {
                $base_url = get_term_link(get_queried_object());
            } else {
                $base_url = home_url('/parfiumi/');
            }
        }
        
        if (!empty($filters)) {
            $base_url = add_query_arg($filters, $base_url);
        }
        
        return $base_url;
    }
    
    /**
     * Почиства филтрите - премахва празни стойности
     */
    public function clean_filters($filters) {
        $clean_filters = array();
        
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $value = array_filter($value, function($v) {
                    return !empty($v) && $v !== '';
                });
                if (!empty($value)) {
                    $clean_filters[$key] = $value;
                }
            } elseif (!empty($value) && $value !== '') {
                $clean_filters[$key] = $value;
            }
        }
        
        return $clean_filters;
    }
    
    /**
     * Проверява дали има активни филтри
     */
    public function has_active_filters() {
        $filters = $this->get_active_filters();
        return !empty($filters);
    }
    
    /**
     * Получава статистики за филтрите (брой резултати на филтър)
     */
    public function get_filter_counts($taxonomy = '') {
        if (empty($taxonomy)) {
            return array();
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ));
        
        $counts = array();
        foreach ($terms as $term) {
            $counts[$term->slug] = $term->count;
        }
        
        return $counts;
    }
}