<?php
/**
 * Parfume Repository
 * 
 * Data access layer for Parfume post type
 * 
 * @package Parfume_Reviews
 * @subpackage PostTypes\Parfume
 * @since 2.0.0
 */

namespace ParfumeReviews\PostTypes\Parfume;

/**
 * Repository Class
 * 
 * Handles all database queries for parfumes
 */
class Repository {
    
    /**
     * Post type
     * 
     * @var string
     */
    private $post_type = 'parfume';
    
    /**
     * Default query args
     * 
     * @var array
     */
    private $default_args = [
        'post_type' => 'parfume',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    
    /**
     * Find parfume by ID
     * 
     * @param int $id Post ID
     * @return \WP_Post|null
     */
    public function find($id) {
        $post = get_post($id);
        
        if ($post && $post->post_type === $this->post_type) {
            return $post;
        }
        
        return null;
    }
    
    /**
     * Find parfume by slug
     * 
     * @param string $slug Post slug
     * @return \WP_Post|null
     */
    public function find_by_slug($slug) {
        $query = $this->query([
            'name' => $slug,
            'posts_per_page' => 1
        ]);
        
        return $query->have_posts() ? $query->posts[0] : null;
    }
    
    /**
     * Find parfume by title
     * 
     * @param string $title Post title
     * @return \WP_Post|null
     */
    public function find_by_title($title) {
        $post = get_page_by_title($title, OBJECT, $this->post_type);
        return $post ? $post : null;
    }
    
    /**
     * Get all parfumes
     * 
     * @param array $args Query arguments
     * @return \WP_Query
     */
    public function all($args = []) {
        return $this->query($args);
    }
    
    /**
     * Get parfumes with pagination
     * 
     * @param int $page Page number
     * @param int $per_page Posts per page
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function paginate($page = 1, $per_page = 12, $args = []) {
        $args['paged'] = $page;
        $args['posts_per_page'] = $per_page;
        
        return $this->query($args);
    }
    
    /**
     * Search parfumes
     * 
     * @param string $search Search term
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function search($search, $args = []) {
        $args['s'] = $search;
        return $this->query($args);
    }
    
    /**
     * Get parfumes by taxonomy term
     * 
     * @param string $taxonomy Taxonomy name
     * @param string|int|array $terms Term(s)
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function by_term($taxonomy, $terms, $args = []) {
        $tax_query = [
            [
                'taxonomy' => $taxonomy,
                'field' => is_numeric($terms) ? 'term_id' : 'slug',
                'terms' => $terms
            ]
        ];
        
        if (isset($args['tax_query'])) {
            $args['tax_query']['relation'] = 'AND';
            $args['tax_query'][] = $tax_query[0];
        } else {
            $args['tax_query'] = $tax_query;
        }
        
        return $this->query($args);
    }
    
    /**
     * Get parfumes by multiple taxonomy terms
     * 
     * @param array $taxonomies Array of [taxonomy => terms]
     * @param string $relation Relation between taxonomies (AND/OR)
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function by_terms($taxonomies, $relation = 'AND', $args = []) {
        $tax_query = ['relation' => $relation];
        
        foreach ($taxonomies as $taxonomy => $terms) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => is_numeric($terms) ? 'term_id' : 'slug',
                'terms' => $terms
            ];
        }
        
        $args['tax_query'] = $tax_query;
        
        return $this->query($args);
    }
    
    /**
     * Get parfumes by meta value
     * 
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @param string $compare Comparison operator
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function by_meta($meta_key, $meta_value, $compare = '=', $args = []) {
        $meta_query = [
            [
                'key' => '_parfume_' . $meta_key,
                'value' => $meta_value,
                'compare' => $compare
            ]
        ];
        
        if (isset($args['meta_query'])) {
            $args['meta_query'][] = $meta_query[0];
        } else {
            $args['meta_query'] = $meta_query;
        }
        
        return $this->query($args);
    }
    
    /**
     * Get featured parfumes
     * 
     * @param int $count Number of parfumes
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function featured($count = 6, $args = []) {
        $args['posts_per_page'] = $count;
        
        return $this->by_meta('featured', '1', '=', $args);
    }
    
    /**
     * Get top rated parfumes
     * 
     * @param int $count Number of parfumes
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function top_rated($count = 6, $args = []) {
        $args['posts_per_page'] = $count;
        $args['meta_key'] = '_parfume_rating';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        
        return $this->query($args);
    }
    
    /**
     * Get newest parfumes
     * 
     * @param int $count Number of parfumes
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function newest($count = 6, $args = []) {
        $args['posts_per_page'] = $count;
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        
        return $this->query($args);
    }
    
    /**
     * Get related parfumes
     * 
     * @param int $post_id Post ID
     * @param int $count Number of related parfumes
     * @return \WP_Query
     */
    public function related($post_id, $count = 6) {
        // Get taxonomies from current parfume
        $terms = [];
        $taxonomies = ['marki', 'gender', 'aroma_type', 'notes'];
        
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
            if (!empty($post_terms) && !is_wp_error($post_terms)) {
                $terms[$taxonomy] = $post_terms;
            }
        }
        
        if (empty($terms)) {
            return $this->newest($count);
        }
        
        // Build tax query
        $tax_query = ['relation' => 'OR'];
        foreach ($terms as $taxonomy => $term_ids) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $term_ids
            ];
        }
        
        $args = [
            'posts_per_page' => $count,
            'post__not_in' => [$post_id],
            'tax_query' => $tax_query,
            'orderby' => 'rand'
        ];
        
        return $this->query($args);
    }
    
    /**
     * Get parfumes in price range
     * 
     * @param float $min_price Minimum price
     * @param float $max_price Maximum price
     * @param array $args Additional query arguments
     * @return \WP_Query
     */
    public function by_price_range($min_price, $max_price, $args = []) {
        // This is complex as prices are in repeater field
        // We'll use meta_query with serialized data matching
        $all_parfumes = $this->all(['posts_per_page' => -1, 'fields' => 'ids'])->posts;
        $matching_ids = [];
        
        foreach ($all_parfumes as $post_id) {
            $stores = get_post_meta($post_id, '_parfume_stores', true);
            
            if (!is_array($stores)) {
                continue;
            }
            
            foreach ($stores as $store) {
                if (isset($store['price'])) {
                    $price = floatval($store['price']);
                    
                    if ($price >= $min_price && $price <= $max_price) {
                        $matching_ids[] = $post_id;
                        break;
                    }
                }
            }
        }
        
        if (empty($matching_ids)) {
            $matching_ids = [0]; // No results
        }
        
        $args['post__in'] = $matching_ids;
        
        return $this->query($args);
    }
    
    /**
     * Count parfumes
     * 
     * @param array $args Query arguments
     * @return int
     */
    public function count($args = []) {
        $args['posts_per_page'] = -1;
        $args['fields'] = 'ids';
        
        $query = $this->query($args);
        return $query->found_posts;
    }
    
    /**
     * Check if parfume exists
     * 
     * @param int $id Post ID
     * @return bool
     */
    public function exists($id) {
        return $this->find($id) !== null;
    }
    
    /**
     * Create new parfume
     * 
     * @param array $data Post data
     * @return int|WP_Error Post ID or WP_Error
     */
    public function create($data) {
        $post_data = [
            'post_type' => $this->post_type,
            'post_status' => isset($data['status']) ? $data['status'] : 'publish',
            'post_title' => isset($data['title']) ? $data['title'] : '',
            'post_content' => isset($data['content']) ? $data['content'] : '',
            'post_excerpt' => isset($data['excerpt']) ? $data['excerpt'] : '',
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id) && isset($data['meta'])) {
            $this->update_meta($post_id, $data['meta']);
        }
        
        if (!is_wp_error($post_id) && isset($data['taxonomies'])) {
            $this->update_taxonomies($post_id, $data['taxonomies']);
        }
        
        return $post_id;
    }
    
    /**
     * Update parfume
     * 
     * @param int $id Post ID
     * @param array $data Data to update
     * @return int|WP_Error Post ID or WP_Error
     */
    public function update($id, $data) {
        $post_data = ['ID' => $id];
        
        if (isset($data['title'])) {
            $post_data['post_title'] = $data['title'];
        }
        
        if (isset($data['content'])) {
            $post_data['post_content'] = $data['content'];
        }
        
        if (isset($data['excerpt'])) {
            $post_data['post_excerpt'] = $data['excerpt'];
        }
        
        if (isset($data['status'])) {
            $post_data['post_status'] = $data['status'];
        }
        
        $result = wp_update_post($post_data);
        
        if (!is_wp_error($result) && isset($data['meta'])) {
            $this->update_meta($id, $data['meta']);
        }
        
        if (!is_wp_error($result) && isset($data['taxonomies'])) {
            $this->update_taxonomies($id, $data['taxonomies']);
        }
        
        return $result;
    }
    
    /**
     * Delete parfume
     * 
     * @param int $id Post ID
     * @param bool $force Force delete (skip trash)
     * @return \WP_Post|false|null
     */
    public function delete($id, $force = false) {
        return wp_delete_post($id, $force);
    }
    
    /**
     * Update meta data
     * 
     * @param int $post_id Post ID
     * @param array $meta Meta data
     */
    private function update_meta($post_id, $meta) {
        foreach ($meta as $key => $value) {
            update_post_meta($post_id, '_parfume_' . $key, $value);
        }
    }
    
    /**
     * Update taxonomies
     * 
     * @param int $post_id Post ID
     * @param array $taxonomies Taxonomies data
     */
    private function update_taxonomies($post_id, $taxonomies) {
        foreach ($taxonomies as $taxonomy => $terms) {
            wp_set_post_terms($post_id, $terms, $taxonomy);
        }
    }
    
    /**
     * Execute query
     * 
     * @param array $args Query arguments
     * @return \WP_Query
     */
    private function query($args = []) {
        $args = wp_parse_args($args, $this->default_args);
        return new \WP_Query($args);
    }
    
    /**
     * Get cheapest price for parfume
     * 
     * @param int $post_id Post ID
     * @return float|null
     */
    public function get_cheapest_price($post_id) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($stores) || empty($stores)) {
            return null;
        }
        
        $prices = array_filter(array_column($stores, 'price'));
        
        return !empty($prices) ? min($prices) : null;
    }
    
    /**
     * Get highest price for parfume
     * 
     * @param int $post_id Post ID
     * @return float|null
     */
    public function get_highest_price($post_id) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($stores) || empty($stores)) {
            return null;
        }
        
        $prices = array_filter(array_column($stores, 'price'));
        
        return !empty($prices) ? max($prices) : null;
    }
    
    /**
     * Get average rating
     * 
     * @return float
     */
    public function get_average_rating() {
        global $wpdb;
        
        $query = "SELECT AVG(CAST(meta_value AS DECIMAL(10,2))) 
                  FROM {$wpdb->postmeta} 
                  WHERE meta_key = '_parfume_rating' 
                  AND meta_value != ''";
        
        return floatval($wpdb->get_var($query));
    }
    
    /**
     * Get statistics
     * 
     * @return array
     */
    public function get_statistics() {
        return [
            'total' => $this->count(),
            'published' => $this->count(['post_status' => 'publish']),
            'draft' => $this->count(['post_status' => 'draft']),
            'average_rating' => $this->get_average_rating(),
        ];
    }
}