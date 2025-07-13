<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Template Loader - управлява зареждането на template файлове за таксономии
 * ПОДОБРЕНА ВЕРСИЯ С ДЕБЪГ И 404 ЗАЩИТА
 */
class Taxonomy_Template_Loader {
    
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'));
        
        // Добавяме дебъг hook-ове само ако WP_DEBUG е включен
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_template_info'));
            add_filter('template_include', array($this, 'log_template_loading'), 999);
        }
    }
    
    /**
     * Зарежда подходящия template файл за таксономии
     * ПОДОБРЕНА ВЕРСИЯ С FALLBACK И ДЕБЪГ
     */
    public function template_loader($template) {
        global $wp_query;
        
        // Дебъг лог
        $this->debug_log('Template loader called for: ' . $this->get_current_page_type());
        
        // Check if this is our custom taxonomy archive
        if (isset($wp_query->query_vars['is_parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['is_parfume_taxonomy_archive'];
            $this->debug_log("Custom taxonomy archive detected: {$taxonomy}");
            
            $template = $this->load_taxonomy_archive_template($taxonomy);
            if ($template) {
                return $template;
            }
        }
        
        // Handle individual taxonomy terms
        if (is_tax()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $taxonomy = $queried_object->taxonomy;
                $this->debug_log("Taxonomy page detected: {$taxonomy}");
                
                // Проверяваме дали е наша поддържана таксономия
                if ($this->is_parfume_taxonomy($taxonomy)) {
                    $template = $this->load_taxonomy_template($taxonomy, $queried_object);
                    if ($template) {
                        return $template;
                    } else {
                        $this->debug_log("WARNING: No template found for taxonomy {$taxonomy}");
                        // Добавяме 404 защита
                        $this->maybe_set_404_for_missing_template($taxonomy);
                    }
                }
            }
        }
        
        return $template;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Зарежда archive template за таксономия
     */
    private function load_taxonomy_archive_template($taxonomy) {
        $templates_to_try = array();
        
        // Специфичен archive template
        $templates_to_try[] = "archive-{$taxonomy}.php";
        
        // Fallback към общ taxonomy archive
        $templates_to_try[] = "archive-taxonomy.php";
        
        foreach ($templates_to_try as $template_name) {
            $template_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
            if (file_exists($template_path)) {
                $this->debug_log("Archive template found: {$template_name}");
                return $template_path;
            } else {
                $this->debug_log("Archive template not found: {$template_name}");
            }
        }
        
        return false;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Зарежда single template за таксономия
     */
    private function load_taxonomy_template($taxonomy, $term_object) {
        $templates_to_try = array();
        
        // Специален случай за perfumer таксономия
        if ($taxonomy === 'perfumer') {
            // За конкретен парфюмьор
            if ($term_object && !empty($term_object->slug) && !empty($term_object->name)) {
                $templates_to_try[] = 'single-perfumer.php';
                $this->debug_log("Perfumer single page: {$term_object->name}");
            }
            
            // За общ archive на парфюмьори
            $templates_to_try[] = 'taxonomy-perfumer.php';
        } else {
            // За други таксономии
            $templates_to_try[] = "taxonomy-{$taxonomy}-{$term_object->slug}.php";
            $templates_to_try[] = "taxonomy-{$taxonomy}.php";
        }
        
        // Общи fallback-ове
        $templates_to_try[] = 'taxonomy.php';
        $templates_to_try[] = 'archive-taxonomy.php';
        
        foreach ($templates_to_try as $template_name) {
            $template_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
            if (file_exists($template_path)) {
                $this->debug_log("Template found: {$template_name}");
                return $template_path;
            } else {
                $this->debug_log("Template not found: {$template_name}");
            }
        }
        
        return false;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Проверява дали таксономията е от нашия плъгин
     */
    private function is_parfume_taxonomy($taxonomy) {
        $parfume_taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        return in_array($taxonomy, $parfume_taxonomies);
    }
    
    /**
     * НОВА ФУНКЦИЯ - Задава 404 ако липсва template
     */
    private function maybe_set_404_for_missing_template($taxonomy) {
        // Само ако сме в debug режим и има настройка да показваме 404
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!empty($settings['debug_404_on_missing_template'])) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            $this->debug_log("Set 404 for missing template: taxonomy-{$taxonomy}.php");
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Получава типа на текущата страница
     */
    private function get_current_page_type() {
        if (is_tax()) {
            $queried_object = get_queried_object();
            return 'taxonomy: ' . ($queried_object->taxonomy ?? 'unknown');
        } elseif (is_singular()) {
            return 'single: ' . get_post_type();
        } elseif (is_post_type_archive()) {
            return 'archive: ' . get_post_type();
        } else {
            return 'other';
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Дебъг лог функция
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews Template Loader: ' . $message);
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Дебъг информация във footer (само в debug режим)
     */
    public function debug_template_info() {
        if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        if (is_tax() && $this->is_parfume_taxonomy(get_queried_object()->taxonomy)) {
            $queried_object = get_queried_object();
            echo "<!-- Parfume Reviews Debug:\n";
            echo "Taxonomy: {$queried_object->taxonomy}\n";
            echo "Term: {$queried_object->name} ({$queried_object->slug})\n";
            echo "Template hierarchy checked:\n";
            
            $templates = $this->get_template_hierarchy_for_debug($queried_object->taxonomy, $queried_object);
            foreach ($templates as $template) {
                $exists = file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template);
                echo "  - {$template} " . ($exists ? '✅' : '❌') . "\n";
            }
            echo "-->";
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Получава template hierarchy за дебъг
     */
    private function get_template_hierarchy_for_debug($taxonomy, $term_object) {
        $templates = array();
        
        if ($taxonomy === 'perfumer') {
            $templates[] = 'single-perfumer.php';
            $templates[] = 'taxonomy-perfumer.php';
        } else {
            $templates[] = "taxonomy-{$taxonomy}-{$term_object->slug}.php";
            $templates[] = "taxonomy-{$taxonomy}.php";
        }
        
        $templates[] = 'taxonomy.php';
        $templates[] = 'archive-taxonomy.php';
        
        return $templates;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Логира коя template се зарежда накрая
     */
    public function log_template_loading($template) {
        if (is_tax() && $this->is_parfume_taxonomy(get_queried_object()->taxonomy)) {
            $template_name = basename($template);
            $this->debug_log("Final template loaded: {$template_name} from {$template}");
        }
        
        return $template;
    }
    
    // ЗАПАЗЕНИ ОРИГИНАЛНИ ФУНКЦИИ ЗА BACKWARD COMPATIBILITY
    
    /**
     * Проверява дали съществува template файл за дадена таксономия
     */
    public function has_taxonomy_template($taxonomy) {
        $template_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-' . $taxonomy . '.php';
        return file_exists($template_path);
    }
    
    /**
     * Проверява дали съществува archive template файл за дадена таксономия
     */
    public function has_taxonomy_archive_template($taxonomy) {
        $template_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-' . $taxonomy . '.php';
        return file_exists($template_path);
    }
    
    /**
     * Получава пътя до template файла за дадена таксономия
     */
    public function get_taxonomy_template_path($taxonomy) {
        $template_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-' . $taxonomy . '.php';
        return file_exists($template_path) ? $template_path : false;
    }
    
    /**
     * Получава пътя до archive template файла за дадена таксономия
     */
    public function get_taxonomy_archive_template_path($taxonomy) {
        $template_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-' . $taxonomy . '.php';
        return file_exists($template_path) ? $template_path : false;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Получава всички липсващи template файлове
     */
    public function get_missing_templates() {
        $missing = array();
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            if (!$this->has_taxonomy_template($taxonomy)) {
                $missing[] = "taxonomy-{$taxonomy}.php";
            }
            
            // За специални таксономии проверяваме и archive templates
            if (in_array($taxonomy, array('marki', 'notes', 'perfumer'))) {
                if (!$this->has_taxonomy_archive_template($taxonomy)) {
                    $missing[] = "archive-{$taxonomy}.php";
                }
            }
        }
        
        return $missing;
    }
}