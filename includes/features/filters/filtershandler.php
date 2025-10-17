<?php
/**
 * Filters Handler
 * 
 * Handles parfume filtering functionality
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Filters
 * @since 2.0.0
 */

namespace ParfumeReviews\Features\Filters;

use ParfumeReviews\Core\Container;

/**
 * FiltersHandler Class
 * 
 * Manages parfume filtering and queries
 */
class FiltersHandler {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Active filters
     * 
     * @var array
     */
    private $active_filters = [];
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->active_filters = $this->get_active_filters();
    }
    
    /**
     * Initialize filters
     */
    public function init() {
        // Hook into pre_get_posts to modify the query
        add_action('pre_get_posts', [$this, 'apply_filters_to_query']);
    }
    
    /**
     * Get active filters from URL
     * 
     * @return array
     */
    public function get_active_filters() {
        return [
            'brand' => isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '',
            'gender' => isset($_GET['gender']) ? sanitize_text_field($_GET['gender']) : '',
            'aroma_type' => isset($_GET['aroma_type']) ? sanitize_text_field($_GET['aroma_type']) : '',
            'season' => isset($_GET['season']) ? sanitize_text_field($_GET['season']) : '',
            'intensity' => isset($_GET['intensity']) ? sanitize_text_field($_GET['intensity']) : '',
            'min_price' => isset($_GET['min_price']) ? absint($_GET['min_price']) : 0,
            'max_price' => isset($_GET['max_price']) ? absint($_GET['max_price']) : 0,
            'min_rating' => isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0,
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date'
        ];
    }
    
    /**
     * Apply filters to WP_Query
     * 
     * @param \WP_Query $query
     */
    public function apply_filters_to_query($query) {
        // Only on main query for parfume post type
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Only on parfume archives or taxonomies
        if (!is_post_type_archive('parfume') && !$this->is_parfume_taxonomy()) {
            return;
        }
        
        // Apply taxonomy filters
        $tax_query = $this->build_tax_query();
        if (!empty($tax_query)) {
            $query->set('tax_query', $tax_query);
        }
        
        // Apply meta query (price, rating)
        $meta_query = $this->build_meta_query();
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
        
        // Apply ordering
        $this->apply_ordering($query);
    }
    
    /**
     * Build taxonomy query
     * 
     * @return array
     */
    private function build_tax_query() {
        $tax_query = ['relation' => 'AND'];
        
        // Brand filter
        if (!empty($this->active_filters['brand'])) {
            $tax_query[] = [
                'taxonomy' => 'marki',
                'field' => 'slug',
                'terms' => $this->active_filters['brand']
            ];
        }
        
        // Gender filter
        if (!empty($this->active_filters['gender'])) {
            $tax_query[] = [
                'taxonomy' => 'gender',
                'field' => 'slug',
                'terms' => $this->active_filters['gender']
            ];
        }
        
        // Aroma type filter
        if (!empty($this->active_filters['aroma_type'])) {
            $tax_query[] = [
                'taxonomy' => 'aroma_type',
                'field' => 'slug',
                'terms' => $this->active_filters['aroma_type']
            ];
        }
        
        // Season filter
        if (!empty($this->active_filters['season'])) {
            $tax_query[] = [
                'taxonomy' => 'season',
                'field' => 'slug',
                'terms' => $this->active_filters['season']
            ];
        }
        
        // Intensity filter
        if (!empty($this->active_filters['intensity'])) {
            $tax_query[] = [
                'taxonomy' => 'intensity',
                'field' => 'slug',
                'terms' => $this->active_filters['intensity']
            ];
        }
        
        // Return only if we have actual filters
        return count($tax_query) > 1 ? $tax_query : [];
    }
    
    /**
     * Build meta query for price and rating
     * 
     * @return array
     */
    private function build_meta_query() {
        $meta_query = ['relation' => 'AND'];
        
        // Rating filter
        if (!empty($this->active_filters['min_rating'])) {
            $meta_query[] = [
                'key' => '_parfume_rating',
                'value' => $this->active_filters['min_rating'],
                'type' => 'NUMERIC',
                'compare' => '>='
            ];
        }
        
        // Price filters require custom handling since prices are in repeater
        // We'll use a post__in approach with custom query
        if (!empty($this->active_filters['min_price']) || !empty($this->active_filters['max_price'])) {
            $filtered_posts = $this->get_posts_by_price_range(
                $this->active_filters['min_price'],
                $this->active_filters['max_price']
            );
            
            if (!empty($filtered_posts)) {
                // We can't add post__in to meta_query, so we'll handle this separately
                // by filtering in a different hook
                add_filter('posts_where', function($where) use ($filtered_posts) {
                    global $wpdb;
                    $ids = implode(',', array_map('absint', $filtered_posts));
                    $where .= " AND {$wpdb->posts}.ID IN ({$ids})";
                    return $where;
                }, 10, 1);
            } else {
                // No posts match price range, return impossible condition
                $meta_query[] = [
                    'key' => '_impossible',
                    'value' => 'impossible',
                    'compare' => '='
                ];
            }
        }
        
        return count($meta_query) > 1 ? $meta_query : [];
    }
    
    /**
     * Get posts by price range
     * 
     * @param float $min_price Minimum price
     * @param float $max_price Maximum price
     * @return array Post IDs
     */
    private function get_posts_by_price_range($min_price, $max_price) {
        global $wpdb;
        
        $matching_posts = [];
        
        // Get all parfumes
        $posts = get_posts([
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        foreach ($posts as $post_id) {
            $stores = get_post_meta($post_id, '_parfume_stores', true);
            
            if (!is_array($stores)) {
                continue;
            }
            
            // Check if any store has a price in range
            foreach ($stores as $store) {
                if (!isset($store['price']) || empty($store['price'])) {
                    continue;
                }
                
                $price = floatval($store['price']);
                
                // Check price range
                $in_range = true;
                
                if ($min_price > 0 && $price < $min_price) {
                    $in_range = false;
                }
                
                if ($max_price > 0 && $price > $max_price) {
                    $in_range = false;
                }
                
                if ($in_range) {
                    $matching_posts[] = $post_id;
                    break; // Found matching price, no need to check other stores
                }
            }
        }
        
        return array_unique($matching_posts);
    }
    
    /**
     * Apply ordering to query
     * 
     * @param \WP_Query $query
     */
    private function apply_ordering($query) {
        $orderby = $this->active_filters['orderby'];
        
        switch ($orderby) {
            case 'price_asc':
                // Custom ordering by price requires meta query
                $query->set('orderby', 'meta_value_num');
                $query->set('meta_key', '_parfume_price_min');
                $query->set('order', 'ASC');
                break;
            
            case 'price_desc':
                $query->set('orderby', 'meta_value_num');
                $query->set('meta_key', '_parfume_price_min');
                $query->set('order', 'DESC');
                break;
            
            case 'rating':
                $query->set('orderby', 'meta_value_num');
                $query->set('meta_key', '_parfume_rating');
                $query->set('order', 'DESC');
                break;
            
            case 'title':
                $query->set('orderby', 'title');
                $query->set('order', 'ASC');
                break;
            
            case 'date':
            default:
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
                break;
        }
    }
    
    /**
     * Check if current page is parfume taxonomy
     * 
     * @return bool
     */
    private function is_parfume_taxonomy() {
        $parfume_taxonomies = ['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'];
        
        foreach ($parfume_taxonomies as $taxonomy) {
            if (is_tax($taxonomy)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get filter URL
     * 
     * @param array $filters Filters to apply
     * @return string
     */
    public function get_filter_url($filters = []) {
        $base_url = get_post_type_archive_link('parfume');
        
        if (is_tax()) {
            $queried_object = get_queried_object();
            $base_url = get_term_link($queried_object);
        }
        
        return add_query_arg($filters, $base_url);
    }
}