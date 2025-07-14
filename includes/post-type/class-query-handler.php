<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Rewrite Handler - управлява URL rewrite rules за таксономии
 * ВЪЗСТАНОВЕНА ВЕРСИЯ - с поправена archive логика
 */
class Taxonomy_Rewrite_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_requests'));
    }
    
    /**
     * Добавя custom rewrite rules
     * ВЪЗСТАНОВЕНА ОРИГИНАЛНА ВЕРСИЯ
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
            if ($taxonomy === 'perfumer') {
                // За perfumer archive със pagination
                $query_with_pagination = 'index.php?perfumer_archive=1&paged=$matches[1]';
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?
            
            // Archive page rule - САМО ЗА PERFUMER ТАКСОНОМИЯ
            if ($taxonomy === 'perfumer') {
                // За perfumer archive използваме специален query var
                $query_archive = 'index.php?perfumer_archive=1';
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?
        }
    }
    
    /**
     * Добавя custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        $vars[] = 'perfumer_archive'; // Специален var за perfumer archive
        return $vars;
    }
    
    /**
     * Обработва custom requests
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
        if (isset($wp->query_vars['perfumer_archive'])) {
            // Set-ваме че е perfumer archive page
            $wp->query_vars['is_perfumer_archive'] = true;
            $wp->query_vars['taxonomy'] = 'perfumer';
            
            // Не set-ваме post_type или tax_query
            // Оставяме празен query за да може template-ът да направи собствен query
            
            // Премахваме custom flag-а
            unset($wp->query_vars['perfumer_archive']);
            
            return;
        }
        
        // За ОСТАНАЛИТЕ таксономии - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_archive,
                    'top'
                );
            } else {
                // За останалите таксономии - оригиналната логика
                $query_archive = 'index.php?parfume_taxonomy_archive=' . $taxonomy;
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?
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
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
            if ($taxonomy === 'perfumer') {
                // За perfumer archive НЕ query-ваме парфюм постове
                // Вместо това set-ваме flag че е perfumer archive
                $wp->query_vars['is_perfumer_archive'] = true;
                
                // Махаме parfume_taxonomy_archive за да не се третира като post query
                unset($wp->query_vars['parfume_taxonomy_archive']);
                
                // Не set-ваме post_type, tax_query или друго
                // Оставяме WordPress да handle-ва като taxonomy archive
                return;
            }
            
            // За ОСТАНАЛИТЕ таксономии (marki, notes, etc.) - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_archive,
                    'top'
                );
            }
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
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
            if ($taxonomy === 'perfumer') {
                // За perfumer archive НЕ query-ваме парфюм постове
                // Вместо това set-ваме flag че е perfumer archive
                $wp->query_vars['is_perfumer_archive'] = true;
                
                // Махаме parfume_taxonomy_archive за да не се третира като post query
                unset($wp->query_vars['parfume_taxonomy_archive']);
                
                // Не set-ваме post_type, tax_query или друго
                // Оставяме WordPress да handle-ва като taxonomy archive
                return;
            }
            
            // За ОСТАНАЛИТЕ таксономии (marki, notes, etc.) - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_with_pagination,
                    'top'
                );
            } else {
                // За останалите таксономии - оригиналната логика  
                $query_with_pagination = 'index.php?parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]';
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?
            
            // Archive page rule - САМО ЗА PERFUMER ТАКСОНОМИЯ
            if ($taxonomy === 'perfumer') {
                // За perfumer archive използваме специален query var
                $query_archive = 'index.php?perfumer_archive=1';
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?
        }
    }
    
    /**
     * Добавя custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        $vars[] = 'perfumer_archive'; // Специален var за perfumer archive
        return $vars;
    }
    
    /**
     * Обработва custom requests
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
        if (isset($wp->query_vars['perfumer_archive'])) {
            // Set-ваме че е perfumer archive page
            $wp->query_vars['is_perfumer_archive'] = true;
            $wp->query_vars['taxonomy'] = 'perfumer';
            
            // Не set-ваме post_type или tax_query
            // Оставяме празен query за да може template-ът да направи собствен query
            
            // Премахваме custom flag-а
            unset($wp->query_vars['perfumer_archive']);
            
            return;
        }
        
        // За ОСТАНАЛИТЕ таксономии - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_archive,
                    'top'
                );
            } else {
                // За останалите таксономии - оригиналната логика
                $query_archive = 'index.php?parfume_taxonomy_archive=' . $taxonomy;
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?
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
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
            if ($taxonomy === 'perfumer') {
                // За perfumer archive НЕ query-ваме парфюм постове
                // Вместо това set-ваме flag че е perfumer archive
                $wp->query_vars['is_perfumer_archive'] = true;
                
                // Махаме parfume_taxonomy_archive за да не се третира като post query
                unset($wp->query_vars['parfume_taxonomy_archive']);
                
                // Не set-ваме post_type, tax_query или друго
                // Оставяме WordPress да handle-ва като taxonomy archive
                return;
            }
            
            // За ОСТАНАЛИТЕ таксономии (marki, notes, etc.) - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_archive,
                    'top'
                );
            }
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
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
            if ($taxonomy === 'perfumer') {
                // За perfumer archive НЕ query-ваме парфюм постове
                // Вместо това set-ваме flag че е perfumer archive
                $wp->query_vars['is_perfumer_archive'] = true;
                
                // Махаме parfume_taxonomy_archive за да не се третира като post query
                unset($wp->query_vars['parfume_taxonomy_archive']);
                
                // Не set-ваме post_type, tax_query или друго
                // Оставяме WordPress да handle-ва като taxonomy archive
                return;
            }
            
            // За ОСТАНАЛИТЕ таксономии (marki, notes, etc.) - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_with_pagination,
                    'top'
                );
            }
            
            // Archive page rule - САМО ЗА PERFUMER ТАКСОНОМИЯ
            if ($taxonomy === 'perfumer') {
                // За perfumer archive използваме специален query var
                $query_archive = 'index.php?perfumer_archive=1';
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?
        }
    }
    
    /**
     * Добавя custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        $vars[] = 'perfumer_archive'; // Специален var за perfumer archive
        return $vars;
    }
    
    /**
     * Обработва custom requests
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
        if (isset($wp->query_vars['perfumer_archive'])) {
            // Set-ваме че е perfumer archive page
            $wp->query_vars['is_perfumer_archive'] = true;
            $wp->query_vars['taxonomy'] = 'perfumer';
            
            // Не set-ваме post_type или tax_query
            // Оставяме празен query за да може template-ът да направи собствен query
            
            // Премахваме custom flag-а
            unset($wp->query_vars['perfumer_archive']);
            
            return;
        }
        
        // За ОСТАНАЛИТЕ таксономии - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_archive,
                    'top'
                );
            } else {
                // За останалите таксономии - оригиналната логика
                $query_archive = 'index.php?parfume_taxonomy_archive=' . $taxonomy;
                add_rewrite_rule(
                    '^' . $parfume_slug . '/' . $slug . '/?
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
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
            if ($taxonomy === 'perfumer') {
                // За perfumer archive НЕ query-ваме парфюм постове
                // Вместо това set-ваме flag че е perfumer archive
                $wp->query_vars['is_perfumer_archive'] = true;
                
                // Махаме parfume_taxonomy_archive за да не се третира като post query
                unset($wp->query_vars['parfume_taxonomy_archive']);
                
                // Не set-ваме post_type, tax_query или друго
                // Оставяме WordPress да handle-ва като taxonomy archive
                return;
            }
            
            // За ОСТАНАЛИТЕ таксономии (marki, notes, etc.) - ОРИГИНАЛНА ЛОГИКА
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
},
                    $query_archive,
                    'top'
                );
            }
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
     * ПОПРАВЕНА ВЕРСИЯ - специално за perfumer archive
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ARCHIVE
            if ($taxonomy === 'perfumer') {
                // За perfumer archive НЕ query-ваме парфюм постове
                // Вместо това set-ваме flag че е perfumer archive
                $wp->query_vars['is_perfumer_archive'] = true;
                
                // Махаме parfume_taxonomy_archive за да не се третира като post query
                unset($wp->query_vars['parfume_taxonomy_archive']);
                
                // Не set-ваме post_type, tax_query или друго
                // Оставяме WordPress да handle-ва като taxonomy archive
                return;
            }
            
            // За ОСТАНАЛИТЕ таксономии (marki, notes, etc.) - ОРИГИНАЛНА ЛОГИКА
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
}