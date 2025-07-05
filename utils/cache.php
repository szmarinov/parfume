<?php
/**
 * Cache Management System for Parfume Reviews
 * 
 * Handles caching of expensive operations like:
 * - Taxonomy term queries
 * - Post queries with complex meta/tax queries
 * - Rating calculations
 * - Archive counts
 * - Related parfumes calculations
 * 
 * @package Parfume_Reviews
 */

namespace Parfume_Reviews\Utils;

class Cache {
    
    /**
     * Cache group for parfume reviews
     */
    const CACHE_GROUP = 'parfume_reviews';
    
    /**
     * Default cache expiration (1 hour)
     */
    const DEFAULT_EXPIRATION = 3600;
    
    /**
     * Cache key prefixes
     */
    const PREFIX_TERMS = 'terms_';
    const PREFIX_POSTS = 'posts_';
    const PREFIX_RATINGS = 'ratings_';
    const PREFIX_COUNTS = 'counts_';
    const PREFIX_RELATED = 'related_';
    const PREFIX_ARCHIVES = 'archives_';
    
    /**
     * Initialize cache system
     */
    public static function init() {
        // Clear cache when posts/terms are updated
        add_action('save_post', [__CLASS__, 'clear_post_cache']);
        add_action('delete_post', [__CLASS__, 'clear_post_cache']);
        add_action('created_term', [__CLASS__, 'clear_term_cache'], 10, 3);
        add_action('edited_term', [__CLASS__, 'clear_term_cache'], 10, 3);
        add_action('delete_term', [__CLASS__, 'clear_term_cache'], 10, 3);
        
        // Clear cache when plugin settings are updated
        add_action('update_option_parfume_reviews_settings', [__CLASS__, 'clear_all_cache']);
        
        // Add cache management to admin
        add_action('admin_post_parfume_clear_cache', [__CLASS__, 'admin_clear_cache']);
        add_action('wp_ajax_parfume_clear_cache', [__CLASS__, 'ajax_clear_cache']);
    }
    
    /**
     * Get cached data or execute callback and cache result
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $expiration Cache expiration in seconds
     * @param string $prefix Cache key prefix
     * @return mixed
     */
    public static function get_or_set($key, $callback, $expiration = self::DEFAULT_EXPIRATION, $prefix = '') {
        $cache_key = self::build_cache_key($key, $prefix);
        $cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        // Execute callback and cache result
        $data = call_user_func($callback);
        
        if (!is_wp_error($data)) {
            wp_cache_set($cache_key, $data, self::CACHE_GROUP, $expiration);
        }
        
        return $data;
    }
    
    /**
     * Get taxonomy terms with caching
     *
     * @param string $taxonomy
     * @param array $args
     * @param int $expiration
     * @return array|WP_Error
     */
    public static function get_taxonomy_terms($taxonomy, $args = [], $expiration = self::DEFAULT_EXPIRATION) {
        $cache_key = $taxonomy . '_' . md5(serialize($args));
        
        return self::get_or_set(
            $cache_key,
            function() use ($taxonomy, $args) {
                return get_terms(array_merge(['taxonomy' => $taxonomy], $args));
            },
            $expiration,
            self::PREFIX_TERMS
        );
    }
    
    /**
     * Get parfume posts with caching
     *
     * @param array $args WP_Query arguments
     * @param int $expiration
     * @return array
     */
    public static function get_parfume_posts($args = [], $expiration = self::DEFAULT_EXPIRATION) {
        $default_args = [
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => 10
        ];
        
        $args = array_merge($default_args, $args);
        $cache_key = 'parfume_posts_' . md5(serialize($args));
        
        return self::get_or_set(
            $cache_key,
            function() use ($args) {
                $query = new \WP_Query($args);
                return $query->posts;
            },
            $expiration,
            self::PREFIX_POSTS
        );
    }
    
    /**
     * Get average rating for taxonomy term with caching
     *
     * @param int $term_id
     * @param string $taxonomy
     * @param int $expiration
     * @return float
     */
    public static function get_term_average_rating($term_id, $taxonomy, $expiration = self::DEFAULT_EXPIRATION) {
        $cache_key = $taxonomy . '_' . $term_id . '_avg_rating';
        
        return self::get_or_set(
            $cache_key,
            function() use ($term_id, $taxonomy) {
                return self::calculate_term_average_rating($term_id, $taxonomy);
            },
            $expiration,
            self::PREFIX_RATINGS
        );
    }
    
    /**
     * Get post counts by taxonomy with caching
     *
     * @param string $taxonomy
     * @param int $expiration
     * @return array
     */
    public static function get_taxonomy_post_counts($taxonomy, $expiration = self::DEFAULT_EXPIRATION) {
        $cache_key = $taxonomy . '_post_counts';
        
        return self::get_or_set(
            $cache_key,
            function() use ($taxonomy) {
                global $wpdb;
                
                $query = $wpdb->prepare("
                    SELECT tt.term_id, COUNT(p.ID) as post_count
                    FROM {$wpdb->term_taxonomy} tt
                    LEFT JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                    LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                    WHERE tt.taxonomy = %s 
                    AND (p.post_status = 'publish' OR p.ID IS NULL)
                    AND (p.post_type = 'parfume' OR p.ID IS NULL)
                    GROUP BY tt.term_id
                ", $taxonomy);
                
                $results = $wpdb->get_results($query);
                $counts = [];
                
                foreach ($results as $result) {
                    $counts[$result->term_id] = (int) $result->post_count;
                }
                
                return $counts;
            },
            $expiration,
            self::PREFIX_COUNTS
        );
    }
    
    /**
     * Get related parfumes with caching
     *
     * @param int $post_id
     * @param int $limit
     * @param int $expiration
     * @return array
     */
    public static function get_related_parfumes($post_id, $limit = 4, $expiration = self::DEFAULT_EXPIRATION) {
        $cache_key = 'related_' . $post_id . '_' . $limit;
        
        return self::get_or_set(
            $cache_key,
            function() use ($post_id, $limit) {
                return self::calculate_related_parfumes($post_id, $limit);
            },
            $expiration,
            self::PREFIX_RELATED
        );
    }
    
    /**
     * Get archive data with caching
     *
     * @param string $archive_type
     * @param array $args
     * @param int $expiration
     * @return array
     */
    public static function get_archive_data($archive_type, $args = [], $expiration = self::DEFAULT_EXPIRATION) {
        $cache_key = $archive_type . '_' . md5(serialize($args));
        
        return self::get_or_set(
            $cache_key,
            function() use ($archive_type, $args) {
                switch ($archive_type) {
                    case 'brands_alphabetical':
                        return self::get_brands_alphabetical($args);
                    case 'notes_categorized':
                        return self::get_notes_categorized($args);
                    case 'perfumers_alphabetical':
                        return self::get_perfumers_alphabetical($args);
                    default:
                        return [];
                }
            },
            $expiration,
            self::PREFIX_ARCHIVES
        );
    }
    
    /**
     * Calculate average rating for taxonomy term
     *
     * @param int $term_id
     * @param string $taxonomy
     * @return float
     */
    private static function calculate_term_average_rating($term_id, $taxonomy) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT AVG(CAST(pm.meta_value AS DECIMAL(3,2))) as avg_rating
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE tt.term_id = %d 
            AND tt.taxonomy = %s
            AND p.post_type = 'parfume'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_parfume_rating'
            AND pm.meta_value != ''
            AND pm.meta_value != '0'
        ", $term_id, $taxonomy);
        
        $result = $wpdb->get_var($query);
        return $result ? floatval($result) : 0;
    }
    
    /**
     * Calculate related parfumes based on taxonomies
     *
     * @param int $post_id
     * @param int $limit
     * @return array
     */
    private static function calculate_related_parfumes($post_id, $limit) {
        // Get current parfume taxonomies
        $brands = wp_get_post_terms($post_id, 'marki', ['fields' => 'ids']);
        $notes = wp_get_post_terms($post_id, 'notes', ['fields' => 'ids']);
        $genders = wp_get_post_terms($post_id, 'gender', ['fields' => 'ids']);
        
        if (is_wp_error($brands)) $brands = [];
        if (is_wp_error($notes)) $notes = [];
        if (is_wp_error($genders)) $genders = [];
        
        $tax_query = ['relation' => 'OR'];
        
        if (!empty($brands)) {
            $tax_query[] = [
                'taxonomy' => 'marki',
                'field' => 'term_id',
                'terms' => $brands,
            ];
        }
        
        if (!empty($notes)) {
            $tax_query[] = [
                'taxonomy' => 'notes',
                'field' => 'term_id',
                'terms' => $notes,
            ];
        }
        
        if (!empty($genders)) {
            $tax_query[] = [
                'taxonomy' => 'gender',
                'field' => 'term_id',
                'terms' => $genders,
            ];
        }
        
        if (count($tax_query) === 1) {
            return [];
        }
        
        $args = [
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
            'post__not_in' => [$post_id],
            'tax_query' => $tax_query,
            'meta_key' => '_parfume_rating',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ];
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Get brands organized alphabetically
     *
     * @param array $args
     * @return array
     */
    private static function get_brands_alphabetical($args = []) {
        $brands = get_terms(array_merge([
            'taxonomy' => 'marki',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ], $args));
        
        if (is_wp_error($brands)) {
            return [];
        }
        
        $alphabetical = [];
        foreach ($brands as $brand) {
            $first_letter = mb_strtoupper(mb_substr($brand->name, 0, 1, 'UTF-8'), 'UTF-8');
            
            if (preg_match('/[А-Я]/u', $first_letter) || preg_match('/[A-Z]/', $first_letter)) {
                $letter_key = $first_letter;
            } else {
                $letter_key = '#';
            }
            
            if (!isset($alphabetical[$letter_key])) {
                $alphabetical[$letter_key] = [];
            }
            $alphabetical[$letter_key][] = $brand;
        }
        
        return $alphabetical;
    }
    
    /**
     * Get notes organized by categories
     *
     * @param array $args
     * @return array
     */
    private static function get_notes_categorized($args = []) {
        $notes = get_terms(array_merge([
            'taxonomy' => 'notes',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ], $args));
        
        if (is_wp_error($notes)) {
            return [];
        }
        
        $categories = [
            'Цитрусови' => ['keywords' => ['бергамот', 'лимон', 'портокал', 'грейпфрут'], 'notes' => []],
            'Флорални' => ['keywords' => ['роза', 'жасмин', 'лавандула', 'иланг'], 'notes' => []],
            'Дървесни' => ['keywords' => ['кедър', 'сандал', 'oud', 'ветивер', 'пачули'], 'notes' => []],
            'Ориенталски' => ['keywords' => ['ванилия', 'кехлибар', 'мускус', 'тонка'], 'notes' => []],
            'Свежи' => ['keywords' => ['iso e super', 'морски', 'мента', 'евкалипт'], 'notes' => []],
            'Подправки' => ['keywords' => ['канела', 'карамфил', 'пипер', 'джинджифил'], 'notes' => []],
            'Други' => ['keywords' => [], 'notes' => []]
        ];
        
        foreach ($notes as $note) {
            $found = false;
            $note_name_lower = mb_strtolower($note->name, 'UTF-8');
            
            foreach ($categories as $category => $data) {
                if ($category === 'Други') continue;
                
                foreach ($data['keywords'] as $keyword) {
                    if (strpos($note_name_lower, $keyword) !== false) {
                        $categories[$category]['notes'][] = $note;
                        $found = true;
                        break 2;
                    }
                }
            }
            
            if (!$found) {
                $categories['Други']['notes'][] = $note;
            }
        }
        
        return $categories;
    }
    
    /**
     * Get perfumers organized alphabetically
     *
     * @param array $args
     * @return array
     */
    private static function get_perfumers_alphabetical($args = []) {
        $perfumers = get_terms(array_merge([
            'taxonomy' => 'perfumer',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ], $args));
        
        if (is_wp_error($perfumers)) {
            return [];
        }
        
        $alphabetical = [];
        foreach ($perfumers as $perfumer) {
            $first_letter = mb_strtoupper(mb_substr($perfumer->name, 0, 1, 'UTF-8'), 'UTF-8');
            
            if (preg_match('/[А-Я]/u', $first_letter) || preg_match('/[A-Z]/', $first_letter)) {
                $letter_key = $first_letter;
            } else {
                $letter_key = '#';
            }
            
            if (!isset($alphabetical[$letter_key])) {
                $alphabetical[$letter_key] = [];
            }
            $alphabetical[$letter_key][] = $perfumer;
        }
        
        return $alphabetical;
    }
    
    /**
     * Build cache key with prefix
     *
     * @param string $key
     * @param string $prefix
     * @return string
     */
    private static function build_cache_key($key, $prefix = '') {
        return $prefix . $key;
    }
    
    /**
     * Clear post-related cache
     *
     * @param int $post_id
     */
    public static function clear_post_cache($post_id) {
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        // Clear related parfumes cache
        wp_cache_delete(self::PREFIX_RELATED . $post_id, self::CACHE_GROUP);
        
        // Clear post queries cache
        self::clear_cache_by_prefix(self::PREFIX_POSTS);
        
        // Clear ratings cache
        self::clear_cache_by_prefix(self::PREFIX_RATINGS);
        
        // Clear counts cache
        self::clear_cache_by_prefix(self::PREFIX_COUNTS);
    }
    
    /**
     * Clear term-related cache
     *
     * @param int $term_id
     * @param int $tt_id
     * @param string $taxonomy
     */
    public static function clear_term_cache($term_id, $tt_id, $taxonomy) {
        $parfume_taxonomies = ['marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'];
        
        if (!in_array($taxonomy, $parfume_taxonomies)) {
            return;
        }
        
        // Clear taxonomy terms cache
        self::clear_cache_by_prefix(self::PREFIX_TERMS);
        
        // Clear archives cache
        self::clear_cache_by_prefix(self::PREFIX_ARCHIVES);
        
        // Clear counts cache
        self::clear_cache_by_prefix(self::PREFIX_COUNTS);
        
        // Clear specific term rating cache
        wp_cache_delete(self::PREFIX_RATINGS . $taxonomy . '_' . $term_id . '_avg_rating', self::CACHE_GROUP);
    }
    
    /**
     * Clear cache by prefix
     *
     * @param string $prefix
     */
    public static function clear_cache_by_prefix($prefix) {
        global $wp_object_cache;
        
        if (!is_object($wp_object_cache)) {
            return;
        }
        
        $cache_group = self::CACHE_GROUP;
        
        if (method_exists($wp_object_cache, 'flush_group')) {
            // For advanced object cache plugins
            $wp_object_cache->flush_group($cache_group);
        } else {
            // Fallback - clear entire cache
            wp_cache_flush();
        }
    }
    
    /**
     * Clear all parfume reviews cache
     */
    public static function clear_all_cache() {
        self::clear_cache_by_prefix('');
    }
    
    /**
     * Admin handler for clearing cache
     */
    public static function admin_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        check_admin_referer('parfume_clear_cache');
        
        self::clear_all_cache();
        
        $redirect_url = add_query_arg([
            'page' => 'parfume-reviews-settings',
            'cache_cleared' => '1'
        ], admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public static function ajax_clear_cache() {
        check_ajax_referer('parfume_clear_cache');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.'));
        }
        
        self::clear_all_cache();
        
        wp_send_json_success(__('Cache cleared successfully.'));
    }
    
    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function get_cache_stats() {
        global $wp_object_cache;
        
        $stats = [
            'cache_enabled' => wp_using_ext_object_cache(),
            'cache_type' => wp_using_ext_object_cache() ? 'External' : 'Internal',
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_size' => 0
        ];
        
        if (is_object($wp_object_cache) && isset($wp_object_cache->cache_hits)) {
            $stats['cache_hits'] = $wp_object_cache->cache_hits;
        }
        
        if (is_object($wp_object_cache) && isset($wp_object_cache->cache_misses)) {
            $stats['cache_misses'] = $wp_object_cache->cache_misses;
        }
        
        return $stats;
    }
    
    /**
     * Warm up cache for common queries
     */
    public static function warm_up_cache() {
        // Warm up taxonomy terms
        $taxonomies = ['marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'];
        
        foreach ($taxonomies as $taxonomy) {
            self::get_taxonomy_terms($taxonomy, ['number' => 100]);
            self::get_taxonomy_post_counts($taxonomy);
        }
        
        // Warm up archive data
        self::get_archive_data('brands_alphabetical');
        self::get_archive_data('notes_categorized');
        self::get_archive_data('perfumers_alphabetical');
        
        // Warm up popular parfumes
        self::get_parfume_posts([
            'posts_per_page' => 20,
            'meta_key' => '_parfume_rating',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);
    }
}

// Initialize cache system
Cache::init();