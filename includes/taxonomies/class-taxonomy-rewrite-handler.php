<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Rewrite Handler - управлява URL rewrite rules за таксономии
 * 📁 Файл: includes/taxonomies/class-taxonomy-rewrite-handler.php
 * ПОПРАВЕНО: Добавени proper rewrite rules за кирилични символи
 */
class Taxonomy_Rewrite_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_requests'));
        
        // Flush rewrite rules при активация на плъгина
        add_action('parfume_reviews_activated', array($this, 'flush_rewrite_rules_on_activation'));
    }
    
    /**
     * Добавя custom rewrite rules за taxonomy archives
     */
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // ПОПРАВЕНО: Taxonomy archive rules - за всички taxonomies
        $taxonomy_slugs = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        foreach ($taxonomy_slugs as $taxonomy => $slug) {
            // ПОПРАВЕНО: Основни taxonomy term rules (поддръжка за кирилични символи)
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]',
                'top'
            );
            
            // ПОПРАВЕНО: Taxonomy term с pagination
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/page/?([0-9]{1,})/?$',
                'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]',
                'top'
            );
            
            // Archive page rule (e.g., /parfiumi/parfumeri/ за всички марки)
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/?$',
                'index.php?is_parfume_taxonomy_archive=' . $taxonomy,
                'top'
            );
            
            // Archive with pagination (e.g., /parfiumi/parfumeri/page/2/)
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/page/([0-9]{1,})/?$',
                'index.php?is_parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]',
                'top'
            );
            
            // Archive with letter filter (e.g., /parfiumi/parfumeri/letter/a/)
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/letter/([a-zA-Z0-9])/?$',
                'index.php?is_parfume_taxonomy_archive=' . $taxonomy . '&taxonomy_letter=$matches[1]',
                'top'
            );
            
            // Archive with letter filter and pagination
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/letter/([a-zA-Z0-9])/page/([0-9]{1,})/?$',
                'index.php?is_parfume_taxonomy_archive=' . $taxonomy . '&taxonomy_letter=$matches[1]&paged=$matches[2]',
                'top'
            );
        }
        
        // ПОПРАВЕНО: General parfume archive rules
        add_rewrite_rule(
            '^' . $parfume_slug . '/?$',
            'index.php?post_type=parfume',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&paged=$matches[1]',
            'top'
        );
        
        // ПОПРАВЕНО: Filter pagination
        add_rewrite_rule(
            '^' . $parfume_slug . '/filter/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&paged=$matches[1]',
            'top'
        );
    }
    
    /**
     * Добавя custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'is_parfume_taxonomy_archive';
        $vars[] = 'taxonomy_letter';
        return $vars;
    }
    
    /**
     * Обработва custom requests
     */
    public function parse_custom_requests($wp) {
        // Handle taxonomy archive requests
        if (isset($wp->query_vars['is_parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['is_parfume_taxonomy_archive'];
            
            // Validate taxonomy
            if (!in_array($taxonomy, $this->get_supported_taxonomies())) {
                return;
            }
            
            // Set query vars for taxonomy archive
            $wp->query_vars['post_type'] = 'parfume';
            
            // Handle letter filtering
            if (isset($wp->query_vars['taxonomy_letter'])) {
                $letter = strtoupper($wp->query_vars['taxonomy_letter']);
                $wp->query_vars['taxonomy_filter_letter'] = $letter;
            }
            
            // Mark this as a taxonomy archive page
            $wp->query_vars['is_home'] = false;
            $wp->query_vars['is_archive'] = true;
        }
    }
    
    /**
     * Получава slug за таксономия
     */
    public function get_taxonomy_slug($taxonomy) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $slug_mapping = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri',
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
     * Получава поддържани таксономии
     */
    public function get_supported_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    /**
     * Получава URL за архив на таксономия
     */
    public function get_taxonomy_archive_url($taxonomy) {
        if (!in_array($taxonomy, $this->get_supported_taxonomies())) {
            return false;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $taxonomy_slug = $this->get_taxonomy_slug($taxonomy);
        
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
    
    /**
     * Проверява дали slug е валиден за таксономия
     */
    public function is_valid_taxonomy_slug($slug) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $valid_slugs = array(
            $settings['brands_slug'] ?? 'parfumeri',
            $settings['notes_slug'] ?? 'notes',
            $settings['perfumers_slug'] ?? 'parfumers',
            $settings['gender_slug'] ?? 'gender',
            $settings['aroma_type_slug'] ?? 'aroma-type',
            $settings['season_slug'] ?? 'season',
            $settings['intensity_slug'] ?? 'intensity',
        );
        
        return in_array($slug, $valid_slugs);
    }
    
    /**
     * Flush rewrite rules при активация
     */
    public function flush_rewrite_rules_on_activation() {
        $this->add_custom_rewrite_rules();
        flush_rewrite_rules(false);
        
        // Логваме за debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Rewrite rules flushed');
        }
    }
    
    /**
     * Debug: Получава всички rewrite rules за плъгина
     */
    public function get_debug_rewrite_rules() {
        global $wp_rewrite;
        
        $parfume_rules = array();
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        foreach ($wp_rewrite->rules as $pattern => $rewrite) {
            if (strpos($pattern, $parfume_slug) !== false || strpos($rewrite, 'parfume') !== false) {
                $parfume_rules[$pattern] = $rewrite;
            }
        }
        
        return $parfume_rules;
    }
    
    /**
     * Debug: Проверява дали URL има правилни rewrite rules
     */
    public function debug_url($url_path) {
        $debug_info = array();
        $debug_info['url_path'] = $url_path;
        $debug_info['decoded_url'] = urldecode($url_path);
        
        // Проверяваме дали URL-ът съвпада с някой pattern
        global $wp_rewrite;
        $debug_info['matching_rules'] = array();
        
        foreach ($wp_rewrite->rules as $pattern => $rewrite) {
            if (preg_match('#^' . $pattern . '#', trim($url_path, '/'))) {
                $debug_info['matching_rules'][$pattern] = $rewrite;
            }
        }
        
        $debug_info['parfume_rules'] = $this->get_debug_rewrite_rules();
        
        return $debug_info;
    }
}