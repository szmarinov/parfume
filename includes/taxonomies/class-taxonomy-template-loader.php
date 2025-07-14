<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Template Loader - управлява зареждането на template файлове за таксономии
 * ПОПРАВЕНА ВЕРСИЯ ЗА PERFUMER ARCHIVE - /parfiumi/parfumeri/
 * 
 * Файл: includes/taxonomies/class-taxonomy-template-loader.php
 */
class Taxonomy_Template_Loader {
    
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'), 99);
        
        // Добавяме дебъг hook-ове само ако WP_DEBUG е включен
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_template_info'));
            add_filter('template_include', array($this, 'log_template_loading'), 999);
        }
    }
    
    /**
     * Зарежда подходящия template файл за таксономии
     * ПОПРАВЕНА ВЕРСИЯ ЗА PERFUMER ARCHIVE
     */
    public function template_loader($template) {
        global $wp_query;
        
        // Дебъг лог
        $this->debug_log('Template loader called for: ' . $this->get_current_page_type());
        
        // ПЪРВО - Проверяваме за PERFUMER ARCHIVE
        if (isset($wp_query->query_vars['perfumer_archive']) || 
            isset($wp_query->query_vars['is_perfumer_archive'])) {
            
            $this->debug_log("PERFUMER ARCHIVE detected!");
            
            // Опитваме се да заредим archive-perfumer.php
            $perfumer_archive_template = $this->locate_template('archive-perfumer.php');
            if ($perfumer_archive_template) {
                $this->debug_log("Loading archive-perfumer.php from: {$perfumer_archive_template}");
                return $perfumer_archive_template;
            }
            
            // Fallback към taxonomy-perfumer.php
            $perfumer_fallback_template = $this->locate_template('taxonomy-perfumer.php');
            if ($perfumer_fallback_template) {
                $this->debug_log("Fallback to taxonomy-perfumer.php from: {$perfumer_fallback_template}");
                return $perfumer_fallback_template;
            }
            
            $this->debug_log("ERROR: No perfumer archive template found!");
        }
        
        // ВТОРО - Проверяваме за други taxonomy archives
        if (isset($wp_query->query_vars['is_parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['is_parfume_taxonomy_archive'];
            $this->debug_log("Custom taxonomy archive detected: {$taxonomy}");
            
            $template = $this->load_taxonomy_archive_template($taxonomy);
            if ($template) {
                return $template;
            }
        }
        
        // ТРЕТО - Handle individual taxonomy terms (single perfumer, brand, etc.)
        if (is_tax()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $taxonomy = $queried_object->taxonomy;
                $this->debug_log("Taxonomy page detected: {$taxonomy}");
                
                // Проверяваме дали е наша поддържана таксономия
                if ($this->is_parfume_taxonomy($taxonomy)) {
                    
                    // СПЕЦИАЛНО ЗА PERFUMER SINGLE PAGES
                    if ($taxonomy === 'perfumer') {
                        $this->debug_log("Perfumer single page: {$queried_object->name} (slug: {$queried_object->slug})");
                        
                        // За single perfumer опитваме специфичен template
                        $specific_template = $this->locate_template("taxonomy-perfumer-{$queried_object->slug}.php");
                        if ($specific_template) {
                            $this->debug_log("Template found: taxonomy-perfumer-{$queried_object->slug}.php");
                            return $specific_template;
                        }
                        
                        // Fallback към общ perfumer template
                        $general_template = $this->locate_template('taxonomy-perfumer.php');
                        if ($general_template) {
                            $this->debug_log("Template found: taxonomy-perfumer.php");
                            return $general_template;
                        }
                        
                        $this->debug_log("Template not found: taxonomy-perfumer.php");
                    } else {
                        // За други таксономии
                        $template = $this->load_taxonomy_template($taxonomy, $queried_object);
                        if ($template) {
                            return $template;
                        }
                    }
                    
                    $this->debug_log("WARNING: No template found for taxonomy {$taxonomy}");
                }
            }
        }
        
        return $template;
    }
    
    /**
     * ПОПРАВЕНА ФУНКЦИЯ - Зарежда archive template за таксономия
     */
    private function load_taxonomy_archive_template($taxonomy) {
        $templates_to_try = array();
        
        // За perfumer - специален случай
        if ($taxonomy === 'perfumer') {
            $templates_to_try[] = "archive-perfumer.php";
            $templates_to_try[] = "taxonomy-perfumer.php";
        } else {
            // За други таксономии
            $templates_to_try[] = "archive-{$taxonomy}.php";
        }
        
        // Общи fallback-ове
        $templates_to_try[] = "archive-taxonomy.php";
        $templates_to_try[] = "taxonomy.php";
        
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
     * ПОПРАВЕНА ФУНКЦИЯ - Зарежда template за таксономия
     */
    private function load_taxonomy_template($taxonomy, $term_object) {
        $templates_to_try = array();
        
        // Специален случай за perfumer таксономия
        if ($taxonomy === 'perfumer') {
            // За конкретен парфюмерист term
            if ($term_object && !empty($term_object->slug) && !empty($term_object->name)) {
                // Опитваме се за специфичен template за термина
                $templates_to_try[] = "taxonomy-perfumer-{$term_object->slug}.php";
                $this->debug_log("Perfumer single page: {$term_object->name} (slug: {$term_object->slug})");
            }
            
            // За общ perfumer template (работи и за archive и за single)
            $templates_to_try[] = 'taxonomy-perfumer.php';
        } else {
            // За други таксономии
            if ($term_object && !empty($term_object->slug)) {
                $templates_to_try[] = "taxonomy-{$taxonomy}-{$term_object->slug}.php";
            }
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
     * Намира template файл в темата или в plugin-а
     */
    private function locate_template($template_name) {
        // Първо търси в активната тема
        $theme_template = locate_template(array(
            'parfume-reviews/' . $template_name,
            $template_name
        ));
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // После търси в plugin папката
        $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Проверява дали дадена таксономия е за парфюми
     */
    private function is_parfume_taxonomy($taxonomy) {
        $parfume_taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        return in_array($taxonomy, $parfume_taxonomies);
    }
    
    /**
     * НОВА ФУНКЦИЯ - Получава типа на текущата страница за дебъг
     */
    private function get_current_page_type() {
        global $wp_query;
        
        if (isset($wp_query->query_vars['perfumer_archive']) || 
            isset($wp_query->query_vars['is_perfumer_archive'])) {
            return 'perfumer_archive';
        }
        
        if (isset($wp_query->query_vars['is_parfume_taxonomy_archive'])) {
            return 'taxonomy_archive: ' . $wp_query->query_vars['is_parfume_taxonomy_archive'];
        }
        
        if (is_tax()) {
            $obj = get_queried_object();
            return 'taxonomy: ' . ($obj ? $obj->taxonomy : 'unknown');
        }
        
        if (is_singular('parfume')) {
            return 'single_parfume';
        }
        
        if (is_post_type_archive('parfume')) {
            return 'parfume_archive';
        }
        
        return 'other';
    }
    
    /**
     * Debug функция за проследяване
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log('Parfume Reviews Template Loader: ' . $message);
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Показва дебъг информация в footer
     */
    public function debug_template_info() {
        if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_query;
        
        echo "\n<!-- PARFUME REVIEWS TEMPLATE DEBUG\n";
        echo "Page Type: " . $this->get_current_page_type() . "\n";
        
        if (isset($wp_query->query_vars['perfumer_archive'])) {
            echo "Perfumer Archive: YES\n";
        }
        
        if (isset($wp_query->query_vars['is_perfumer_archive'])) {
            echo "Is Perfumer Archive: YES\n";
        }
        
        if (is_tax()) {
            $obj = get_queried_object();
            if ($obj) {
                echo "Taxonomy: {$obj->taxonomy}\n";
                echo "Term: {$obj->name} (slug: {$obj->slug})\n";
            }
        }
        
        // Проверяваме всички template файлове
        $templates = array(
            'archive-perfumer.php',
            'taxonomy-perfumer.php',
            'archive-taxonomy.php'
        );
        
        echo "Templates Status:\n";
        foreach ($templates as $template) {
            $exists = file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template);
            echo "  - {$template} " . ($exists ? '✅' : '❌') . "\n";
        }
        echo "-->";
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
    
    /**
     * НОВА ФУНКЦИЯ - Получава статистики за template файлове
     */
    public function get_template_stats() {
        $stats = array(
            'total_templates' => 0,
            'existing_templates' => 0,
            'missing_templates' => 0,
            'template_list' => array()
        );
        
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            $template_name = "taxonomy-{$taxonomy}.php";
            $template_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
            $exists = file_exists($template_path);
            
            $stats['total_templates']++;
            if ($exists) {
                $stats['existing_templates']++;
            } else {
                $stats['missing_templates']++;
            }
            
            $stats['template_list'][] = array(
                'name' => $template_name,
                'taxonomy' => $taxonomy,
                'exists' => $exists,
                'path' => $template_path
            );
        }
        
        return $stats;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Проверява template система
     */
    public function check_template_system() {
        $issues = array();
        
        // Проверяваме основната templates директория
        $templates_dir = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/';
        if (!is_dir($templates_dir)) {
            $issues[] = 'Templates директорията не съществува: ' . $templates_dir;
        }
        
        // Проверяваме write permissions
        if (!is_writable($templates_dir)) {
            $issues[] = 'Templates директорията не е writable: ' . $templates_dir;
        }
        
        // Проверяваме ключови template файлове
        $critical_templates = array(
            'single-parfume.php',
            'archive-parfume.php',
            'taxonomy-perfumer.php',
            'archive-perfumer.php'
        );
        
        foreach ($critical_templates as $template) {
            $template_path = $templates_dir . $template;
            if (!file_exists($template_path)) {
                $issues[] = "Липсва критичен template: {$template}";
            }
        }
        
        return $issues;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Прави debug dump на template system-а
     */
    public function debug_template_system() {
        if (!current_user_can('manage_options')) {
            return array();
        }
        
        $debug_info = array(
            'templates_dir' => PARFUME_REVIEWS_PLUGIN_DIR . 'templates/',
            'templates_exist' => is_dir(PARFUME_REVIEWS_PLUGIN_DIR . 'templates/'),
            'current_page_type' => $this->get_current_page_type(),
            'missing_templates' => $this->get_missing_templates(),
            'template_stats' => $this->get_template_stats(),
            'system_issues' => $this->check_template_system()
        );
        
        return $debug_info;
    }
}