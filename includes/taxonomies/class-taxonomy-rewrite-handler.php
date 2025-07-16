<?php
/**
 * Taxonomy Rewrite Handler - управлява URL rewrite rules за таксономии
 * НАПЪЛНО ПОПРАВЕНА ВЕРСИЯ - правилен приоритет и структура на правилата
 * 
 * Файл: includes/taxonomies/class-taxonomy-rewrite-handler.php
 */

namespace Parfume_Reviews\Taxonomies;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Taxonomy_Rewrite_Handler {
    
    public function __construct() {
        // Критично: Добавяме rewrite rules ПРЕДИ WordPress да генерира стандартните
        add_action('init', array($this, 'add_custom_rewrite_rules'), 1);
        add_filter('query_vars', array($this, 'add_query_vars'), 10);
        add_action('parse_request', array($this, 'parse_custom_requests'), 1);
        
        // Debug hooks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp', array($this, 'debug_current_request'), 999);
            add_action('wp', array($this, 'handle_404_redirects'), 5);
        }
    }
    
    /**
     * Добавя custom rewrite rules
     * КРИТИЧНО: Правилният ред на правилата е от значение!
     */
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("add_custom_rewrite_rules() called");
            error_log("Parfume slug: {$parfume_slug}");
        }
        
        // Define taxonomy slugs with correct defaults
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
        
        // КРИТИЧНО: Добавяме taxonomy archive правила ПРЕДИ individual post правила
        // За всяка таксономия добавяме архивни правила първо
        foreach ($taxonomies as $taxonomy => $slug) {
            
            // 1. ARCHIVE PAGES ПЪРВИ (най-висок приоритет)
            
            // Archive with pagination
            if ($taxonomy === 'perfumer') {
                // Perfumer archive with pagination - СПЕЦИАЛНО ПРАВИЛО
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$',
                    'index.php?perfumer_archive=1&paged=$matches[1]',
                    'top'
                );
                
                // Perfumer main archive - СПЕЦИАЛНО ПРАВИЛО
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?$',
                    'index.php?perfumer_archive=1',
                    'top'
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added perfumer archive rule: ^{$parfume_slug}/{$slug}/?$ -> index.php?perfumer_archive=1");
                }
                
            } else {
                // Останалите таксономии - обичайни archive правила
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$',
                    'index.php?parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]',
                    'top'
                );
                
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?$',
                    'index.php?parfume_taxonomy_archive=' . $taxonomy,
                    'top'
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added {$taxonomy} archive rule: ^{$parfume_slug}/{$slug}/?$ -> index.php?parfume_taxonomy_archive={$taxonomy}");
                }
            }
        }
        
        // 2. INDIVIDUAL TERM PAGES ВТОРИ (по-нисък приоритет)
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
        $vars[] = 'perfumer_archive';
        $vars[] = 'is_perfumer_archive';
        $vars[] = 'is_parfume_taxonomy_archive';
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
            
            // Премахваме всички други post query vars
            unset($wp->query_vars['name']);
            unset($wp->query_vars['post_type']);
            unset($wp->query_vars['pagename']);
            
            // Set the post type за query
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
            
            $this->debug_log("Set perfumer archive query vars");
            return;
        }
        
        // Handle other taxonomy archives
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            $this->debug_log("TAXONOMY ARCHIVE REQUEST for: {$taxonomy}");
            
            // Премахваме всички други post query vars
            unset($wp->query_vars['name']);
            unset($wp->query_vars['post_type']);
            unset($wp->query_vars['pagename']);
            
            // Set the post type за query
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
            
            $this->debug_log("Set {$taxonomy} archive query vars");
            return;
        }
    }
    
    /**
     * Handle 404 redirects за неправилно обработени URLs
     */
    public function handle_404_redirects() {
        if (!is_404() || !isset($_SERVER['REQUEST_URI'])) {
            return;
        }
        
        $request_uri = $_SERVER['REQUEST_URI'];
        $url_path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("404 URL Path: {$request_uri}");
            error_log("Checking path: {$url_path}");
        }
        
        // Check if this looks like a taxonomy archive
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $taxonomy_slugs = array(
            'marki', 'notki', 'parfumeri', 'gender', 'aroma-type', 'season', 'intensity'
        );
        
        foreach ($taxonomy_slugs as $tax_slug) {
            $pattern = $parfume_slug . '/' . $tax_slug;
            if (strpos($url_path, $pattern) !== false) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Possible taxonomy archive: {$tax_slug}");
                }
                
                // Force flush rewrite rules if we detect unmatched taxonomy URLs
                if (get_option('parfume_reviews_force_flush', false) !== 'done') {
                    flush_rewrite_rules();
                    update_option('parfume_reviews_force_flush', 'done');
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Force flushed rewrite rules due to 404 on taxonomy URL");
                    }
                }
                break;
            }
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
            $request_uri = $_SERVER['REQUEST_URI'];
            $url_path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
            
            // Remove site base path if exists
            $site_url = home_url();
            $parsed_site = parse_url($site_url);
            if (isset($parsed_site['path'])) {
                $site_path = trim($parsed_site['path'], '/');
                if (!empty($site_path) && strpos($url_path, $site_path) === 0) {
                    $url_path = substr($url_path, strlen($site_path) + 1);
                }
            }
            
            error_log("404 URL Path: {$url_path}");
        }
    }
    
    /**
     * Debug функция за проверка на rewrite rules
     */
    public function debug_rewrite_rules() {
        if (!current_user_can('manage_options') && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return;
        }
        
        $rules = get_option('rewrite_rules');
        error_log('Parfume Taxonomy Rewrite Rules:');
        
        if ($rules) {
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'parfiumi') !== false || 
                    strpos($rewrite, 'parfume_taxonomy_archive') !== false || 
                    strpos($rewrite, 'perfumer_archive') !== false ||
                    strpos($rewrite, 'marki') !== false || 
                    strpos($rewrite, 'perfumer') !== false ||
                    strpos($rewrite, 'notes') !== false) {
                    error_log("  {$rule} -> {$rewrite}");
                }
            }
        }
        
        error_log("Rewrite rules flushed");
    }
    
    /**
     * Helper функция за debug логове
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Rewrite Handler] {$message}");
        }
    }
    
    /**
     * Получава taxonomy slug за дадена таксономия
     */
    public function get_taxonomy_slug($taxonomy) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $taxonomy_slugs = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notki',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumeri',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        return isset($taxonomy_slugs[$taxonomy]) ? $taxonomy_slugs[$taxonomy] : $taxonomy;
    }
    
    /**
     * Получава всички поддържани таксономии
     */
    public function get_supported_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    /**
     * Получава URL за taxonomy archive
     */
    public function get_taxonomy_archive_url($taxonomy) {
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
}