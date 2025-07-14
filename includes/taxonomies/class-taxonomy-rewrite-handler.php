<?php
namespace Parfume_Reviews\Taxonomies;

class Taxonomy_Rewrite_Handler {
    
    public function __construct() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Taxonomy_Rewrite_Handler initialized');
        }
        
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_requests'));
    }
    
    public function add_custom_rewrite_rules() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('add_custom_rewrite_rules() called');
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $taxonomies = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume slug: ' . $parfume_slug);
            error_log('Perfumer slug: ' . $taxonomies['perfumer']);
        }
        
        foreach ($taxonomies as $taxonomy => $slug) {
            // Individual term page with pagination
            $term_pagination_rule = '^' . $parfume_slug . '/' . $slug . '/([^/]+)/page/([0-9]+)/?$';
            $term_pagination_query = 'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]';
            add_rewrite_rule($term_pagination_rule, $term_pagination_query, 'top');
            
            // Individual term page
            $term_rule = '^' . $parfume_slug . '/' . $slug . '/([^/]+)/?$';
            $term_query = 'index.php?' . $taxonomy . '=$matches[1]';
            add_rewrite_rule($term_rule, $term_query, 'top');
            
            // Archive pages
            if ($taxonomy === 'perfumer') {
                // Perfumer archive with pagination
                $perfumer_pagination_rule = '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$';
                $perfumer_pagination_query = 'index.php?perfumer_archive=1&paged=$matches[1]';
                add_rewrite_rule($perfumer_pagination_rule, $perfumer_pagination_query, 'top');
                
                // Perfumer archive
                $perfumer_archive_rule = '^' . $parfume_slug . '/' . $slug . '/?$';
                $perfumer_archive_query = 'index.php?perfumer_archive=1';
                add_rewrite_rule($perfumer_archive_rule, $perfumer_archive_query, 'top');
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added perfumer archive rule: {$perfumer_archive_rule} -> {$perfumer_archive_query}");
                }
            } else {
                // Other taxonomies - pagination
                $other_pagination_rule = '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$';
                $other_pagination_query = 'index.php?parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]';
                add_rewrite_rule($other_pagination_rule, $other_pagination_query, 'top');
                
                // Other taxonomies - archive
                $other_archive_rule = '^' . $parfume_slug . '/' . $slug . '/?$';
                $other_archive_query = 'index.php?parfume_taxonomy_archive=' . $taxonomy;
                add_rewrite_rule($other_archive_rule, $other_archive_query, 'top');
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_rewrite_rules();
        }
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        $vars[] = 'perfumer_archive';
        return $vars;
    }
    
    public function parse_custom_requests($wp) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (!empty($wp->query_vars)) {
                error_log('Parse requests - Query vars: ' . print_r($wp->query_vars, true));
            }
        }
        
        if (isset($wp->query_vars['perfumer_archive'])) {
            error_log('Perfumer archive detected!');
            
            $wp->query_vars['is_perfumer_archive'] = true;
            $wp->query_vars['taxonomy'] = 'perfumer';
            
            unset($wp->query_vars['perfumer_archive']);
            
            return;
        }
        
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            $wp->query_vars['post_type'] = 'parfume';
            $wp->query_vars['posts_per_page'] = 12;
            
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'fields' => 'ids'
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $wp->query_vars['tax_query'] = array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $terms,
                        'operator' => 'IN'
                    )
                );
            }
            
            $wp->query_vars['is_parfume_taxonomy_archive'] = $taxonomy;
        }
    }
    
    public function get_supported_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    public function get_taxonomy_slug($taxonomy) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $slug_mapping = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        return isset($slug_mapping[$taxonomy]) ? $slug_mapping[$taxonomy] : $taxonomy;
    }
    
    public function get_taxonomy_archive_url($taxonomy) {
        if (!in_array($taxonomy, $this->get_supported_taxonomies())) {
            return false;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $taxonomy_slug = $this->get_taxonomy_slug($taxonomy);
        
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
    
    public function get_parfume_base_url() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        return home_url('/' . $parfume_slug . '/');
    }
    
    public function is_taxonomy_archive_url($url) {
        $taxonomies = $this->get_supported_taxonomies();
        
        foreach ($taxonomies as $taxonomy) {
            $archive_url = $this->get_taxonomy_archive_url($taxonomy);
            if ($archive_url && strpos($url, $archive_url) === 0) {
                return $taxonomy;
            }
        }
        
        return false;
    }
    
    public function debug_rewrite_rules() {
        if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $rules = get_option('rewrite_rules');
        error_log('Parfume Taxonomy Rewrite Rules:');
        
        if ($rules) {
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'parfiumi') !== false || 
                    strpos($rewrite, 'marki') !== false || 
                    strpos($rewrite, 'perfumer') !== false ||
                    strpos($rewrite, 'notes') !== false) {
                    error_log("  {$rule} -> {$rewrite}");
                }
            }
        }
    }
}