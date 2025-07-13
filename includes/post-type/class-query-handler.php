<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Query Handler - управлява филтрирането и сортирането на архивните страници
 * ПОПРАВЕН ЗА МНОЖЕСТВЕНО ФИЛТРИРАНЕ ПО НЯКОЛКО КРИТЕРИЯ
 */
class Query_Handler {
    
    public function __construct() {
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
        add_filter('posts_where', array($this, 'filter_posts_where'), 10, 2);
        add_filter('posts_join', array($this, 'filter_posts_join'), 10, 2);
        add_filter('posts_groupby', array($this, 'filter_posts_groupby'), 10, 2);
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
        $meta_query = array();
        $has_filters = false;
        
        // Обработваме таксономии филтри
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $has_filters = true;
                
                // Получаваме стойностите и ги декодираме правилно
                $raw_terms = $_GET[$taxonomy];
                $terms = array();
                
                if (is_array($raw_terms)) {
                    // Множествени стойности
                    foreach ($raw_terms as $term) {
                        $clean_term = sanitize_text_field(urldecode($term));
                        if (!empty($clean_term)) {
                            $terms[] = $clean_term;
                        }
                    }
                } else {
                    // Единична стойност  
                    $clean_term = sanitize_text_field(urldecode($raw_terms));
                    if (!empty($clean_term)) {
                        $terms[] = $clean_term;
                    }
                }
                
                if (!empty($terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $terms,
                        'operator' => 'IN' // Позволява множествен избор в същата таксономия
                    );
                }
            }
        }
        
        // Обработваме ценови филтри
        $min_price = !empty($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
        $max_price = !empty($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
        
        if ($min_price > 0 || $max_price > 0) {
            $has_filters = true;
            $price_query = array('relation' => 'AND');
            
            if ($min_price > 0) {
                $price_query[] = array(
                    'key'     => '_price',
                    'value'   => $min_price,
                    'compare' => '>=',
                    'type'    => 'NUMERIC'
                );
            }
            
            if ($max_price > 0) {
                $price_query[] = array(
                    'key'     => '_price',
                    'value'   => $max_price,
                    'compare' => '<=',
                    'type'    => 'NUMERIC'
                );
            }
            
            $meta_query[] = $price_query;
        }
        
        // Обработваме рейтинг филтър
        $min_rating = !empty($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0;
        if ($min_rating > 0) {
            $has_filters = true;
            $meta_query[] = array(
                'key'     => '_average_rating',
                'value'   => $min_rating,
                'compare' => '>=',
                'type'    => 'NUMERIC'
            );
        }
        
        // Прилагаме филтрите ако има някакви
        if ($has_filters) {
            if (!empty($tax_query)) {
                // Използваме AND операция между различните таксономии
                $tax_query['relation'] = 'AND';
                $query->set('tax_query', $tax_query);
            }
            
            if (!empty($meta_query)) {
                $meta_query['relation'] = 'AND';
                $query->set('meta_query', $meta_query);
            }
        }
    }
    
    /**
     * ПОПРАВЕН МЕТОД ЗА СОРТИРАНЕ
     */
    private function handle_query_sorting($query) {
        $orderby = !empty($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
        $order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';
        
        if (!in_array($order, array('ASC', 'DESC'))) {
            $order = 'ASC';
        }
        
        switch ($orderby) {
            case 'price':
                $query->set('meta_key', '_price');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', $order);
                break;
                
            case 'rating':
                $query->set('meta_key', '_average_rating');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', $order);
                break;
                
            case 'name':
                $query->set('orderby', 'title');
                $query->set('order', $order);
                break;
                
            case 'date':
                $query->set('orderby', 'date');
                $query->set('order', $order);
                break;
                
            case 'popularity':
                $query->set('meta_key', '_view_count');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', $order);
                break;
                
            default:
                // Оставяме по подразбиране
                break;
        }
    }
    
    /**
     * Модифициране на WHERE клаузата при нужда
     */
    public function filter_posts_where($where, $query) {
        global $wpdb;
        
        if (is_admin() || !$query->is_main_query()) {
            return $where;
        }
        
        // Допълнителни WHERE условия при необходимост
        if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            // Филтриране по налични парфюми
            if (!empty($_GET['in_stock']) && $_GET['in_stock'] === '1') {
                $where .= " AND EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} 
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                    AND {$wpdb->postmeta}.meta_key = '_stock_status' 
                    AND {$wpdb->postmeta}.meta_value = 'in_stock'
                )";
            }
        }
        
        return $where;
    }
    
    /**
     * Модифициране на JOIN клаузата при нужда
     */
    public function filter_posts_join($join, $query) {
        // За сложни заявки може да се наложи JOIN
        return $join;
    }
    
    /**
     * Модифициране на GROUP BY клаузата при нужда
     */
    public function filter_posts_groupby($groupby, $query) {
        global $wpdb;
        
        if (is_admin() || !$query->is_main_query()) {
            return $groupby;
        }
        
        // При множествени таксономии може да се наложи групиране
        if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            if (!empty($_GET) && $this->has_multiple_taxonomy_filters()) {
                $groupby = "{$wpdb->posts}.ID";
            }
        }
        
        return $groupby;
    }
    
    /**
     * Проверява дали има множествени таксономии филтри
     */
    private function has_multiple_taxonomy_filters() {
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        $active_filters = 0;
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $active_filters++;
            }
        }
        
        return $active_filters > 1;
    }
    
    /**
     * Получава активните филтри за display
     */
    public static function get_active_filters() {
        $active_filters = array();
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
            }
        }
        
        // Добавяме ценови филтри
        if (!empty($_GET['min_price'])) {
            $active_filters['min_price'] = floatval($_GET['min_price']);
        }
        
        if (!empty($_GET['max_price'])) {
            $active_filters['max_price'] = floatval($_GET['max_price']);
        }
        
        // Добавяме рейтинг филтър
        if (!empty($_GET['min_rating'])) {
            $active_filters['min_rating'] = floatval($_GET['min_rating']);
        }
        
        return $active_filters;
    }
    
    /**
     * Построява URL за филтри
     */
    public static function build_filter_url($filters = array(), $base_url = '') {
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
}