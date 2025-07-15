<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Template Loader - управлява зареждането на template файлове за таксономии
 * ПОПРАВЕНА ВЕРСИЯ - правилно разпознава taxonomy archives и individual terms
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
     * НАПЪЛНО ПОПРАВЕНА ВЕРСИЯ
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
        if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['parfume_taxonomy_archive'];
            $this->debug_log("Custom taxonomy archive detected: {$taxonomy}");
            
            $loaded_template = $this->load_taxonomy_archive_template($taxonomy);
            if ($loaded_template) {
                return $loaded_template;
            }
        }
        
        // ТРЕТО - Handle individual taxonomy terms (single perfumer, brand, etc.)
        $supported_taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (is_tax($taxonomy)) {
                $this->debug_log("Individual taxonomy term detected: {$taxonomy}");
                
                $loaded_template = $this->load_single_taxonomy_template($taxonomy);
                if ($loaded_template) {
                    return $loaded_template;
                }
            }
        }
        
        // Връщаме оригиналния template ако нищо не е намерено
        return $template;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Зарежда template за taxonomy archive
     */
    public function load_taxonomy_archive_template($taxonomy) {
        $this->debug_log("Loading archive template for taxonomy: {$taxonomy}");
        
        // Опитваме се да заредим archive-{taxonomy}.php
        $archive_template = $this->locate_template("archive-{$taxonomy}.php");
        if ($archive_template) {
            $this->debug_log("Found archive template: archive-{$taxonomy}.php");
            return $archive_template;
        }
        
        // Fallback към taxonomy-{taxonomy}.php  
        $taxonomy_template = $this->locate_template("taxonomy-{$taxonomy}.php");
        if ($taxonomy_template) {
            $this->debug_log("Fallback to taxonomy template: taxonomy-{$taxonomy}.php");
            return $taxonomy_template;
        }
        
        // Общ fallback към archive-taxonomy.php
        $general_archive = $this->locate_template('archive-taxonomy.php');
        if ($general_archive) {
            $this->debug_log("Using general archive template: archive-taxonomy.php");
            return $general_archive;
        }
        
        $this->debug_log("No archive template found for taxonomy: {$taxonomy}");
        return false;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Зарежда template за individual taxonomy term
     */
    public function load_single_taxonomy_template($taxonomy) {
        $this->debug_log("Loading single template for taxonomy: {$taxonomy}");
        
        $queried_object = get_queried_object();
        $term_slug = '';
        
        if ($queried_object && isset($queried_object->slug)) {
            $term_slug = $queried_object->slug;
            $this->debug_log("Term slug: {$term_slug}");
        }
        
        // Опитваме се да заредим taxonomy-{taxonomy}-{term}.php
        if ($term_slug) {
            $specific_template = $this->locate_template("taxonomy-{$taxonomy}-{$term_slug}.php");
            if ($specific_template) {
                $this->debug_log("Found specific template: taxonomy-{$taxonomy}-{$term_slug}.php");
                return $specific_template;
            }
        }
        
        // Fallback към taxonomy-{taxonomy}.php
        $general_template = $this->locate_template("taxonomy-{$taxonomy}.php");
        if ($general_template) {
            $this->debug_log("Found general template: taxonomy-{$taxonomy}.php");
            return $general_template;
        }
        
        // Специално за perfumer - опитваме single-perfumer.php
        if ($taxonomy === 'perfumer') {
            $single_template = $this->locate_template('single-perfumer.php');
            if ($single_template) {
                $this->debug_log("Found single perfumer template: single-perfumer.php");
                return $single_template;
            }
        }
        
        // Специално за marki - опитваме single-marki.php
        if ($taxonomy === 'marki') {
            $single_template = $this->locate_template('single-marki.php');
            if ($single_template) {
                $this->debug_log("Found single brand template: single-marki.php");
                return $single_template;
            }
        }
        
        $this->debug_log("No single template found for taxonomy: {$taxonomy}");
        return false;
    }
    
    /**
     * Локализира template файл
     */
    public function locate_template($template_name) {
        // Първо проверяваме в темата
        $theme_template = locate_template(array($template_name));
        if ($theme_template) {
            return $theme_template;
        }
        
        // После проверяваме в плъгина
        $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Проверява дали съществува template за дадена таксономия
     */
    public function has_taxonomy_template($taxonomy) {
        $template_name = "taxonomy-{$taxonomy}.php";
        return $this->locate_template($template_name) !== false;
    }
    
    /**
     * Проверява дали съществува archive template за дадена таксономия
     */
    public function has_taxonomy_archive_template($taxonomy) {
        $archive_template = "archive-{$taxonomy}.php";
        return $this->locate_template($archive_template) !== false;
    }
    
    /**
     * Получава типа на текущата страница за debug
     */
    public function get_current_page_type() {
        global $wp_query;
        
        if (isset($wp_query->query_vars['perfumer_archive'])) {
            return 'perfumer_archive';
        }
        
        if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            return 'parfume_taxonomy_archive: ' . $wp_query->query_vars['parfume_taxonomy_archive'];
        }
        
        if (is_tax()) {
            $queried_object = get_queried_object();
            return 'taxonomy: ' . $queried_object->taxonomy;
        }
        
        if (is_singular('parfume')) {
            return 'parfume_single';
        }
        
        if (is_post_type_archive('parfume')) {
            return 'parfume_archive';
        }
        
        return 'other';
    }
    
    /**
     * Debug hook за показване на template информация
     */
    public function debug_template_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wp_query;
        
        echo '<!-- Parfume Reviews Template Debug -->';
        echo '<!-- Page Type: ' . $this->get_current_page_type() . ' -->';
        echo '<!-- Query Vars: ' . print_r($wp_query->query_vars, true) . ' -->';
        
        if (is_tax()) {
            $queried_object = get_queried_object();
            echo '<!-- Queried Object: ' . print_r($queried_object, true) . ' -->';
        }
    }
    
    /**
     * Логира зареждането на template за debug
     */
    public function log_template_loading($template) {
        $this->debug_log('Final template loaded: ' . $template);
        return $template;
    }
    
    /**
     * Получава липсващи template файлове
     */
    public function get_missing_templates() {
        $required_templates = array(
            'single-parfume.php',
            'archive-parfume.php',
            'taxonomy-marki.php',
            'taxonomy-notes.php',
            'taxonomy-perfumer.php',
            'archive-perfumer.php',
            'taxonomy-gender.php',
            'taxonomy-aroma_type.php',
            'taxonomy-season.php',
            'taxonomy-intensity.php'
        );
        
        $missing = array();
        
        foreach ($required_templates as $template) {
            if (!$this->locate_template($template)) {
                $missing[] = $template;
            }
        }
        
        return $missing;
    }
    
    /**
     * Получава статистики за template файлове
     */
    public function get_template_stats() {
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        $stats = array(
            'total_templates' => 0,
            'existing_templates' => 0,
            'missing_templates' => 0,
            'template_list' => array()
        );
        
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
    
    /**
     * Helper функция за debug logging
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Parfume Reviews Template Loader: " . $message);
        } else if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Parfume Reviews Template Loader: " . $message);
        }
    }
}