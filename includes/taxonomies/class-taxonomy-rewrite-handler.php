<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Rewrite Handler - управлява URL rewrite rules за таксономии
 * ПОПРАВЕНА ВЕРСИЯ - правилно handle на всички taxonomy archives
 * 
 * Файл: includes/taxonomies/class-taxonomy-rewrite-handler.php
 */
class Taxonomy_Rewrite_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_requests'));
        
        // Debug hook за проследяване
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp', array($this, 'debug_current_request'), 999);
        }
    }
    
    /**
     * Добавя custom rewrite rules
     * НАПЪЛНО ПОПРАВЕНА ВЕРСИЯ - ПРАВИЛЕН ПРИОРИТЕТ НА ПРАВИЛАТА
     */
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("add_custom_rewrite_rules() called");
            error_log("Parfume slug: {$parfume_slug}");
        }
        
        // Define taxonomy slugs
        $taxonomies = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notki',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumeri',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Perfumer slug: " . $taxonomies['perfumer']);
        }
        
        // КРИТИЧНО: Добавяме правилата в правилния ред - archive преди individual terms
        foreach ($taxonomies as $taxonomy => $slug) {
            
            // 1. ARCHIVE PAGES ПЪРВИ (най-висок приоритет)
            if ($taxonomy === 'perfumer') {
                // Perfumer archive with pagination
                $perfumer_pagination_rule = '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$';
                $perfumer_pagination_query = 'index.php?perfumer_archive=1&paged=$matches[1]';
                add_rewrite_rule($perfumer_pagination_rule, $perfumer_pagination_query, 'top');
                
                // ГЛАВНОТО ПРАВИЛО - Perfumer archive  
                $perfumer_archive_rule = '^' . $parfume_slug . '/' . $slug . '/?$';
                $perfumer_archive_query = 'index.php?perfumer_archive=1';
                add_rewrite_rule($perfumer_archive_rule, $perfumer_archive_query, 'top');
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added perfumer archive rule: {$perfumer_archive_rule} -> {$perfumer_archive_query}");
                }
            } else {
                // Other taxonomies - archive with pagination
                $other_pagination_rule = '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$';
                $other_pagination_query = 'index.php?parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]';
                add_rewrite_rule($other_pagination_rule, $other_pagination_query, 'top');
                
                // Other taxonomies - archive
                $other_archive_rule = '^' . $parfume_slug . '/' . $slug . '/?$';
                $other_archive_query = 'index.php?parfume_taxonomy_archive=' . $taxonomy;
                add_rewrite_rule($other_archive_rule, $other_archive_query, 'top');
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added {$taxonomy} archive rule: {$other_archive_rule} -> {$other_archive_query}");
                }
            }
            
            // 2. INDIVIDUAL TERM PAGES ВТОРИ (по-нисък приоритет)
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
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_rewrite_rules();
        }
    }
    
    /**
     * Добавя custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        $vars[] = 'perfumer_archive'; // ВАЖНО: Специален var за perfumer archive
        $vars[] = 'is_perfumer_archive'; // Допълнителен flag
        return $vars;
    }
    
    /**
     * Обработва custom requests
     * НАПЪЛНО ПОПРАВЕНА ВЕРСИЯ
     */
    public function parse_custom_requests($wp) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (!empty($wp->query_vars)) {
                error_log('Parse requests - Query vars: ' . print_r($wp->query_vars, true));
            }
        }
        
        // Handle perfumer archive
        if (isset($wp->query_vars['perfumer_archive'])) {
            $this->debug_log("PERFUMER ARCHIVE REQUEST detected!");
            
            // Set the post type
            $wp->query_vars['post_type'] = 'parfume';
            $wp->query_vars['posts_per_page'] = 12;
            
            // Get all perfumer terms
            $perfumer_terms = get_terms(array(
                'taxonomy' => 'perfumer',
                'hide_empty' => false,
                'fields' => 'ids'
            ));
            
            if (!empty($perfumer_terms) && !is_wp_error($perfumer_terms)) {
                $wp->query_vars['tax_query'] = array(
                    array(
                        'taxonomy' => 'perfumer',
                        'field' => 'term_id',
                        'terms' => $perfumer_terms,
                        'operator' => 'IN'
                    )
                );
            }
            
            // Set flag so template loader knows this is perfumer archive
            $wp->query_vars['is_perfumer_archive'] = true;
            
            return;
        }
        
        // Handle other taxonomy archives
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            $this->debug_log("TAXONOMY ARCHIVE REQUEST for: {$taxonomy}");
            
            // Set the post type
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
            
            // Set a flag so template loader knows this is a taxonomy archive
            $wp->query_vars['is_parfume_taxonomy_archive'] = $taxonomy;
            
            return;
        }
    }
    
    /**
     * Debug функция за проследяване на текущата заявка
     */
    public function debug_current_request() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_query;
        
        if (is_404() && !empty($_SERVER['REQUEST_URI'])) {
            error_log("404 URL Path: " . $_SERVER['REQUEST_URI']);
            
            // Check if it's a taxonomy archive URL
            $uri = trim($_SERVER['REQUEST_URI'], '/');
            $parts = explode('/', $uri);
            
            if (count($parts) >= 2 && $parts[0] === 'rehub') {
                array_shift($parts); // Remove "rehub"
                $path = implode('/', $parts);
                error_log("Checking path: {$path}");
                
                // Check if it's parfiumi/taxonomy pattern
                if (count($parts) >= 2 && $parts[0] === 'parfiumi') {
                    $taxonomy_slug = $parts[1];
                    error_log("Possible taxonomy archive: {$taxonomy_slug}");
                }
            }
        }
        
        // Log current query vars for debugging
        if (isset($wp_query->query_vars['perfumer_archive']) || 
            isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            
            error_log("Current template: " . get_page_template_slug());
            error_log("Is tax?: " . (is_tax() ? 'YES' : 'NO'));
            error_log("Is archive?: " . (is_archive() ? 'YES' : 'NO'));
            error_log("Queried object: " . print_r(get_queried_object(), true));
            
            // Проверяваме дали archive-perfumer.php съществува
            $archive_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-perfumer.php';
            error_log("archive-perfumer.php exists: " . (file_exists($archive_template) ? 'YES' : 'NO'));
        }
    }
    
    /**
     * Проверява дали текущата страница е архив на таксономия
     */
    public function is_taxonomy_archive($taxonomy = null) {
        global $wp_query;
        
        if ($taxonomy === 'perfumer') {
            return isset($wp_query->query_vars['perfumer_archive']) || 
                   isset($wp_query->query_vars['is_perfumer_archive']);
        }
        
        if ($taxonomy) {
            return isset($wp_query->query_vars['parfume_taxonomy_archive']) && 
                   $wp_query->query_vars['parfume_taxonomy_archive'] === $taxonomy;
        }
        
        return isset($wp_query->query_vars['parfume_taxonomy_archive']) || 
               isset($wp_query->query_vars['perfumer_archive']) ||
               isset($wp_query->query_vars['is_perfumer_archive']);
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
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notki',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumeri',
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
        if (!in_array($taxonomy, $this->get_supported_taxonomies())) {
            return false;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $taxonomy_slug = $this->get_taxonomy_slug($taxonomy);
        
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
    
    /**
     * Получава базовия URL за парфюм архива
     */
    public function get_parfume_base_url() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        return home_url('/' . $parfume_slug . '/');
    }
    
    /**
     * Проверява дали URL-а е за архивна страница на таксономия
     */
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
    
    /**
     * Debug функция за проверка на rewrite rules
     */
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
    
    /**
     * НОВА ФУНКЦИЯ - Flush rewrite rules ако е нужно
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('parfume_reviews_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_flush_rewrite_rules');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Rewrite rules flushed by taxonomy rewrite handler");
            }
        }
    }
    
    /**
     * Helper функция за debug logging
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Parfume Reviews: " . $message);
        } else if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Parfume Reviews: " . $message);
        }
    }
}