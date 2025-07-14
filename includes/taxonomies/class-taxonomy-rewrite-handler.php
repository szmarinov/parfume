<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Rewrite Handler - управлява URL rewrite rules за таксономии
 */
class Taxonomy_Rewrite_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_requests'));
    }
    
    /**
     * Добавя custom rewrite rules
     */
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Define taxonomy slugs
        $taxonomies = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        foreach ($taxonomies as $taxonomy => $slug) {
            // Individual term page with pagination
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/page/([0-9]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]',
                'top'
            );
            
            // Individual term page
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]',
                'top'
            );
            
            // Archive page with pagination
            $query_with_pagination = 'index.php?parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]';
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$',
                $query_with_pagination,
                'top'
            );
            
            // Archive page rule
            $query_archive = 'index.php?parfume_taxonomy_archive=' . $taxonomy;
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/?$',
                $query_archive,
                'top'
            );
        }
    }
    
    /**
     * Добавя custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        return $vars;
    }
    
    /**
     * Обработва custom requests
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // Set the main query to show all posts from this taxonomy
            $wp->query_vars['post_type'] = 'parfume';
            $wp->query_vars['posts_per_page'] = 12;
            
            // Get all terms from this taxonomy
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
            
            // Set a flag so we know this is a taxonomy archive
            $wp->query_vars['is_parfume_taxonomy_archive'] = $taxonomy;
        }
    }
    
    /**
     * Получава всички поддържани таксономии
     */
    public function get_supported_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    /**
     * Получава slug за дадена таксономия
     */
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
    
    /**
     * Получава URL за архив на таксономия
     */
    public function get_taxonomy_archive_url($taxonomy) {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $taxonomy_slug = $this->get_taxonomy_slug($taxonomy);
        
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
    
    /**
     * Проверява дали текущата страница е архив на таксономия
     */
    public function is_taxonomy_archive($taxonomy = null) {
        global $wp_query;
        
        if (!isset($wp_query->query_vars['is_parfume_taxonomy_archive'])) {
            return false;
        }
        
        if ($taxonomy === null) {
            return true;
        }
        
        return $wp_query->query_vars['is_parfume_taxonomy_archive'] === $taxonomy;
    }
}