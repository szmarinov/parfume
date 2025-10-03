<?php
/**
 * Taxonomy Rewrite Handler - управлява URL rewrite rules за таксономии
 * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена поддръжка за season archive и запазени всички съществуващи функции
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
     * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена поддръжка за season archive
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
            error_log("Season slug: " . $taxonomies['season']);
            error_log("Perfumer slug: " . $taxonomies['perfumer']);
        }
        
        // КРИТИЧНО: Добавяме taxonomy archive правила ПРЕДИ individual post правила
        // За всяка таксономия добавяме архивни правила първо
        foreach ($taxonomies as $taxonomy => $slug) {
            
            // 1. ARCHIVE PAGES ПЪРВИ (най-висок приоритет)
            
            if ($taxonomy === 'perfumer') {
                // PERFUMER archive with pagination - СПЕЦИАЛНО ПРАВИЛО
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$',
                    'index.php?perfumer_archive=1&paged=$matches[1]',
                    'top'
                );
                
                // PERFUMER main archive - СПЕЦИАЛНО ПРАВИЛО
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?$',
                    'index.php?perfumer_archive=1',
                    'top'
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added perfumer archive rule: ^{$parfume_slug}/{$slug}/?$ -> index.php?perfumer_archive=1");
                }
                
            } elseif ($taxonomy === 'season') {
                // SEASON archive with pagination - СПЕЦИАЛНО ПРАВИЛО
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$',
                    'index.php?season_archive=1&paged=$matches[1]',
                    'top'
                );
                
                // SEASON main archive - СПЕЦИАЛНО ПРАВИЛО
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?$',
                    'index.php?season_archive=1',
                    'top'
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added season archive rule: ^{$parfume_slug}/{$slug}/?$ -> index.php?season_archive=1");
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
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Added {$taxonomy} term rule: ^{$parfume_slug}/{$slug}/([^/]+)/?$ -> index.php?{$taxonomy}=\$matches[1]");
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("All taxonomy rewrite rules added successfully");
        }
    }
    
    /**
     * Добавя custom query vars
     * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена season_archive
     */
    public function add_query_vars($vars) {
        $vars[] = 'perfumer_archive';
        $vars[] = 'is_perfumer_archive';
        $vars[] = 'season_archive';  // НОВО - добавена поддръжка за season archive
        $vars[] = 'parfume_taxonomy_archive';
        
        return $vars;
    }
    
    /**
     * Парсира custom request-и
     * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена обработка на season_archive
     */
    public function parse_custom_requests($wp) {
        // Проверяваме за perfumer archive
        if (isset($wp->query_vars['perfumer_archive'])) {
            $this->debug_log('Perfumer archive request detected');
            
            // Премахваме конфликтни query vars
            unset($wp->query_vars['name']);
            unset($wp->query_vars['post_type']);
            unset($wp->query_vars['pagename']);
            
            // Задаваме правилните query vars за perfumer archive
            $wp->query_vars['is_perfumer_archive'] = true;
            
            $this->debug_log('Parse requests - Query vars after perfumer archive setup: ' . print_r($wp->query_vars, true));
        }
        
        // НОВО - Проверяваме за season archive
        if (isset($wp->query_vars['season_archive'])) {
            $this->debug_log('Season archive request detected');
            
            // Премахваме конфликтни query vars
            unset($wp->query_vars['name']);
            unset($wp->query_vars['post_type']);
            unset($wp->query_vars['pagename']);
            
            // Задаваме правилните query vars за season archive
            $wp->query_vars['is_season_archive'] = true;
            $wp->query_vars['parfume_taxonomy_archive'] = 'season';
            
            $this->debug_log('Parse requests - Query vars after season archive setup: ' . print_r($wp->query_vars, true));
        }
        
        // Проверяваме за други taxonomy archives
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            $this->debug_log("Taxonomy archive request detected for: {$taxonomy}");
            
            // Премахваме конфликтни query vars
            unset($wp->query_vars['name']);
            unset($wp->query_vars['post_type']);
            unset($wp->query_vars['pagename']);
            
            $this->debug_log("Parse requests - Query vars after {$taxonomy} archive setup: " . print_r($wp->query_vars, true));
        }
    }
    
    /**
     * Debug текущия request
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function debug_current_request() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_query, $wp;
        
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $this->debug_log("Current request URI: {$request_uri}");
        
        if (isset($wp_query->query_vars['perfumer_archive'])) {
            $this->debug_log("PERFUMER ARCHIVE PAGE - Query vars: " . print_r($wp_query->query_vars, true));
        }
        
        if (isset($wp_query->query_vars['season_archive'])) {
            $this->debug_log("SEASON ARCHIVE PAGE - Query vars: " . print_r($wp_query->query_vars, true));
        }
        
        if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['parfume_taxonomy_archive'];
            $this->debug_log("TAXONOMY ARCHIVE PAGE ({$taxonomy}) - Query vars: " . print_r($wp_query->query_vars, true));
        }
        
        if (is_tax()) {
            $queried_object = get_queried_object();
            $this->debug_log("INDIVIDUAL TAXONOMY TERM - Taxonomy: {$queried_object->taxonomy}, Term: {$queried_object->name}");
        }
    }
    
    /**
     * Обработва 404 redirects
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function handle_404_redirects() {
        if (!is_404()) {
            return;
        }
        
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $this->debug_log("404 detected for: {$request_uri}");
        
        // Ако 404-то е за taxonomy archive URLs, може да се наложи flush на rewrite rules
        if (strpos($request_uri, '/parfiumi/') !== false) {
            $this->debug_log("404 detected for parfume URL - might need rewrite rules flush");
            
            // Само за admin потребители, в dev среда
            if (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("Automatically flushing rewrite rules due to 404");
                flush_rewrite_rules();
            }
        }
    }
    
    /**
     * Debug rewrite rules
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function debug_rewrite_rules() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_rewrite;
        
        $this->debug_log("=== REWRITE RULES DEBUG ===");
        
        $rules = get_option('rewrite_rules');
        if ($rules) {
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'parfiumi') !== false || 
                    strpos($rewrite, 'parfume_taxonomy_archive') !== false || 
                    strpos($rewrite, 'perfumer_archive') !== false ||
                    strpos($rewrite, 'season_archive') !== false ||
                    strpos($rewrite, 'marki') !== false || 
                    strpos($rewrite, 'perfumer') !== false ||
                    strpos($rewrite, 'season') !== false ||
                    strpos($rewrite, 'notes') !== false) {
                    error_log("  {$rule} -> {$rewrite}");
                }
            }
        }
        
        error_log("Rewrite rules flushed");
    }
    
    /**
     * Helper функция за debug логове
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Rewrite Handler] {$message}");
        }
    }
    
    /**
     * Получава taxonomy slug за дадена таксономия
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
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
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function get_supported_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    /**
     * Получава URL за taxonomy archive
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function get_taxonomy_archive_url($taxonomy) {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $taxonomy_slug = $this->get_taxonomy_slug($taxonomy);
        
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
    
    /**
     * Получава базовия URL за парфюм архива
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function get_parfume_base_url() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        return home_url('/' . $parfume_slug . '/');
    }
}