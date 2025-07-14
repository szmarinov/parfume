<?php
/**
 * Debug Helper за 404 проблеми в Parfume Reviews
 * 📁 Файл: includes/debug-404-helper.php (временен файл за debug)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Reviews_404_Debug {
    
    public function __construct() {
        // Активираме debug mode само ако WP_DEBUG е включен
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('template_redirect', array($this, 'debug_404_issues'), 1);
            add_action('wp', array($this, 'log_query_vars'));
        }
    }
    
    /**
     * Debug 404 проблеми
     */
    public function debug_404_issues() {
        global $wp_query;
        
        if (is_404()) {
            $request_uri = $_SERVER['REQUEST_URI'];
            $parsed_url = parse_url($request_uri, PHP_URL_PATH);
            
            // Логваме 404 URL-та свързани с parfume
            if (strpos($parsed_url, 'parfiumi') !== false) {
                error_log('404 URL Path: ' . $parsed_url);
                
                // Debug информация
                $debug_info = array(
                    'original_url' => $request_uri,
                    'parsed_path' => $parsed_url,
                    'decoded_path' => urldecode($parsed_url),
                    'query_vars' => $wp_query->query_vars,
                    'request' => $wp_query->request,
                );
                
                error_log('404 Debug Info: ' . print_r($debug_info, true));
                
                // Проверяваме дали е taxonomy URL
                $this->check_taxonomy_url($parsed_url);
                
                // Проверяваме rewrite rules
                $this->check_rewrite_rules($parsed_url);
            }
        }
    }
    
    /**
     * Проверява дали URL-ът е за taxonomy
     */
    private function check_taxonomy_url($url_path) {
        $path_parts = explode('/', trim($url_path, '/'));
        
        if (count($path_parts) >= 3 && $path_parts[0] === 'rehub' && $path_parts[1] === 'parfiumi') {
            $potential_taxonomy_slug = $path_parts[2];
            $potential_term_slug = isset($path_parts[3]) ? $path_parts[3] : '';
            
            error_log("Checking taxonomy URL - Taxonomy: $potential_taxonomy_slug, Term: $potential_term_slug");
            
            // Проверяваме дали са валидни taxonomy и term
            $valid_taxonomies = array(
                'parfumeri' => 'marki',
                'notes' => 'notes', 
                'parfumers' => 'perfumer',
                'gender' => 'gender',
                'aroma-type' => 'aroma_type',
                'season' => 'season',
                'intensity' => 'intensity'
            );
            
            if (isset($valid_taxonomies[$potential_taxonomy_slug])) {
                $actual_taxonomy = $valid_taxonomies[$potential_taxonomy_slug];
                error_log("Valid taxonomy found: $actual_taxonomy");
                
                if (!empty($potential_term_slug)) {
                    // Проверяваме дали term съществува
                    $term = get_term_by('slug', urldecode($potential_term_slug), $actual_taxonomy);
                    if ($term) {
                        error_log("Term found: " . $term->name . " (ID: " . $term->term_id . ")");
                        error_log("Expected URL should work with rewrite rules");
                    } else {
                        error_log("Term NOT found for slug: " . urldecode($potential_term_slug) . " in taxonomy: " . $actual_taxonomy);
                        
                        // Търсим подобни terms
                        $similar_terms = get_terms(array(
                            'taxonomy' => $actual_taxonomy,
                            'search' => urldecode($potential_term_slug),
                            'number' => 5
                        ));
                        
                        if (!empty($similar_terms) && !is_wp_error($similar_terms)) {
                            error_log("Similar terms found: " . implode(', ', wp_list_pluck($similar_terms, 'name')));
                        }
                    }
                }
            } else {
                error_log("Invalid taxonomy slug: $potential_taxonomy_slug");
            }
        }
    }
    
    /**
     * Проверява rewrite rules
     */
    private function check_rewrite_rules($url_path) {
        global $wp_rewrite;
        
        $matching_rules = array();
        $clean_path = trim($url_path, '/');
        
        foreach ($wp_rewrite->rules as $pattern => $rewrite) {
            if (preg_match('#^' . $pattern . '#', $clean_path)) {
                $matching_rules[$pattern] = $rewrite;
            }
        }
        
        if (!empty($matching_rules)) {
            error_log("Matching rewrite rules found: " . print_r($matching_rules, true));
        } else {
            error_log("NO matching rewrite rules found for: " . $clean_path);
            
            // Показваме всички parfume-related rules
            $parfume_rules = array();
            foreach ($wp_rewrite->rules as $pattern => $rewrite) {
                if (strpos($pattern, 'parfiumi') !== false || strpos($rewrite, 'parfume') !== false) {
                    $parfume_rules[$pattern] = $rewrite;
                }
            }
            
            if (!empty($parfume_rules)) {
                error_log("Available parfume rewrite rules: " . print_r($parfume_rules, true));
            }
        }
    }
    
    /**
     * Логва query vars
     */
    public function log_query_vars() {
        global $wp_query;
        
        // Логваме само ако има query vars свързани с parfume
        $parfume_related = false;
        foreach ($wp_query->query_vars as $key => $value) {
            if (strpos($key, 'parfume') !== false || in_array($key, array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
                $parfume_related = true;
                break;
            }
        }
        
        if ($parfume_related) {
            error_log("Parfume-related query vars: " . print_r($wp_query->query_vars, true));
        }
    }
    
    /**
     * Получава URL debug информация
     */
    public function get_url_debug_info($url) {
        global $wp_rewrite;
        
        $info = array(
            'original_url' => $url,
            'parsed_path' => parse_url($url, PHP_URL_PATH),
            'decoded_path' => urldecode(parse_url($url, PHP_URL_PATH)),
            'path_parts' => explode('/', trim(parse_url($url, PHP_URL_PATH), '/')),
            'matching_rules' => array(),
            'parfume_rules' => array()
        );
        
        $clean_path = trim(parse_url($url, PHP_URL_PATH), '/');
        
        // Намираме matching rules
        foreach ($wp_rewrite->rules as $pattern => $rewrite) {
            if (preg_match('#^' . $pattern . '#', $clean_path)) {
                $info['matching_rules'][$pattern] = $rewrite;
            }
            
            if (strpos($pattern, 'parfiumi') !== false || strpos($rewrite, 'parfume') !== false) {
                $info['parfume_rules'][$pattern] = $rewrite;
            }
        }
        
        return $info;
    }
    
    /**
     * Тества дали URL би трябвало да работи
     */
    public function test_url($url) {
        $debug_info = $this->get_url_debug_info($url);
        $path_parts = $debug_info['path_parts'];
        
        $result = array(
            'url' => $url,
            'should_work' => false,
            'issues' => array(),
            'suggestions' => array()
        );
        
        // Проверяваме основната структура
        if (count($path_parts) >= 2 && $path_parts[1] === 'parfiumi') {
            if (count($path_parts) == 2) {
                // /rehub/parfiumi/ - archive page
                $result['should_work'] = true;
                $result['type'] = 'parfume_archive';
            } elseif (count($path_parts) >= 3) {
                $taxonomy_slug = $path_parts[2];
                $valid_taxonomies = array(
                    'parfumeri' => 'marki',
                    'notes' => 'notes',
                    'parfumers' => 'perfumer',
                    'gender' => 'gender',
                    'aroma-type' => 'aroma_type',
                    'season' => 'season',
                    'intensity' => 'intensity'
                );
                
                if (isset($valid_taxonomies[$taxonomy_slug])) {
                    $actual_taxonomy = $valid_taxonomies[$taxonomy_slug];
                    
                    if (count($path_parts) == 3) {
                        // /rehub/parfiumi/taxonomy/ - taxonomy archive
                        $result['should_work'] = true;
                        $result['type'] = 'taxonomy_archive';
                        $result['taxonomy'] = $actual_taxonomy;
                    } elseif (count($path_parts) >= 4) {
                        // /rehub/parfiumi/taxonomy/term/ - taxonomy term
                        $term_slug = urldecode($path_parts[3]);
                        $term = get_term_by('slug', $term_slug, $actual_taxonomy);
                        
                        if ($term) {
                            $result['should_work'] = true;
                            $result['type'] = 'taxonomy_term';
                            $result['taxonomy'] = $actual_taxonomy;
                            $result['term'] = $term;
                        } else {
                            $result['issues'][] = "Term '$term_slug' not found in taxonomy '$actual_taxonomy'";
                            
                            // Търсим подобни
                            $similar = get_terms(array(
                                'taxonomy' => $actual_taxonomy,
                                'search' => $term_slug,
                                'number' => 3
                            ));
                            
                            if (!empty($similar) && !is_wp_error($similar)) {
                                $result['suggestions'][] = "Similar terms: " . implode(', ', wp_list_pluck($similar, 'name'));
                            }
                        }
                    }
                } else {
                    $result['issues'][] = "Invalid taxonomy slug: '$taxonomy_slug'";
                    $result['suggestions'][] = "Valid taxonomy slugs: " . implode(', ', array_keys($valid_taxonomies));
                }
            }
        } else {
            $result['issues'][] = "URL doesn't match parfume structure";
        }
        
        // Проверяваме rewrite rules
        if (empty($debug_info['matching_rules'])) {
            $result['issues'][] = "No matching rewrite rules found";
            $result['suggestions'][] = "Consider flushing rewrite rules";
        }
        
        return $result;
    }
    
    /**
     * Принтира debug информация (за admin употреба)
     */
    public function print_debug_info($url = null) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!$url) {
            $url = $_SERVER['REQUEST_URI'];
        }
        
        $test_result = $this->test_url($url);
        $debug_info = $this->get_url_debug_info($url);
        
        echo "<div style='background: #f1f1f1; padding: 20px; margin: 20px; border: 1px solid #ccc;'>";
        echo "<h3>Parfume Reviews 404 Debug Info</h3>";
        echo "<h4>URL Test Result:</h4>";
        echo "<pre>" . print_r($test_result, true) . "</pre>";
        echo "<h4>Detailed Debug Info:</h4>";
        echo "<pre>" . print_r($debug_info, true) . "</pre>";
        echo "</div>";
    }
}

// Инициализираме debug helper-а
if (defined('WP_DEBUG') && WP_DEBUG) {
    new Parfume_Reviews_404_Debug();
}

/**
 * Функция за бърз debug от админа
 */
function parfume_reviews_debug_url($url = null) {
    $debug = new Parfume_Reviews_404_Debug();
    $debug->print_debug_info($url);
}