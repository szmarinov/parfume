<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Template Loader - управлява зареждането на template файлове за таксономии
 * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена поддръжка за archive-season.php
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
     * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена поддръжка за season archive
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
        
        // ВТОРО - Проверяваме за SEASON ARCHIVE 
        if (isset($wp_query->query_vars['season_archive']) || 
            (isset($wp_query->query_vars['parfume_taxonomy_archive']) && 
             $wp_query->query_vars['parfume_taxonomy_archive'] === 'season')) {
            
            $this->debug_log("SEASON ARCHIVE detected!");
            
            // Опитваме се да заредим archive-season.php
            $season_archive_template = $this->locate_template('archive-season.php');
            if ($season_archive_template) {
                $this->debug_log("Loading archive-season.php from: {$season_archive_template}");
                return $season_archive_template;
            }
            
            // Fallback към taxonomy-season.php
            $season_fallback_template = $this->locate_template('taxonomy-season.php');
            if ($season_fallback_template) {
                $this->debug_log("Fallback to taxonomy-season.php from: {$season_fallback_template}");
                return $season_fallback_template;
            }
            
            $this->debug_log("ERROR: No season archive template found!");
        }
        
        // ТРЕТО - Проверяваме за други taxonomy archives
        if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['parfume_taxonomy_archive'];
            $this->debug_log("Custom taxonomy archive detected: {$taxonomy}");
            
            // Проверяваме дали не е season (защото го обработихме вече по-горе)
            if ($taxonomy !== 'season') {
                $loaded_template = $this->load_taxonomy_archive_template($taxonomy);
                if ($loaded_template) {
                    return $loaded_template;
                }
            }
        }
        
        // ЧЕТВЪРТО - Handle individual taxonomy terms (single perfumer, brand, season term, etc.)
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
     * Зарежда template за taxonomy archive
     * АКТУАЛИЗИРАНА ВЕРСИЯ - подобрена поддръжка за всички archives
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
     * Зарежда template за individual taxonomy term
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
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
        
        $this->debug_log("No single template found for taxonomy: {$taxonomy}");
        return false;
    }
    
    /**
     * Намира template файл
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
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
     * Получава информация за текущия тип страница
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    private function get_current_page_type() {
        global $wp_query;
        
        if (is_singular('parfume')) {
            return 'single-parfume';
        } elseif (is_post_type_archive('parfume')) {
            return 'archive-parfume';
        } elseif (isset($wp_query->query_vars['perfumer_archive'])) {
            return 'archive-perfumer';
        } elseif (isset($wp_query->query_vars['season_archive'])) {
            return 'archive-season';
        } elseif (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            return 'archive-' . $wp_query->query_vars['parfume_taxonomy_archive'];
        } elseif (is_tax()) {
            $queried_object = get_queried_object();
            return 'taxonomy-' . $queried_object->taxonomy;
        }
        
        return 'unknown';
    }
    
    /**
     * Проверява дали даден template файл съществува
     * НОВА ФУНКЦИОНАЛНОСТ
     */
    public function has_taxonomy_template($taxonomy) {
        $template_files = array(
            "archive-{$taxonomy}.php",
            "taxonomy-{$taxonomy}.php"
        );
        
        foreach ($template_files as $template_file) {
            if ($this->locate_template($template_file)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Debug лог функция
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Taxonomy Template Loader] {$message}");
        }
    }
    
    /**
     * Debug информация за template зареждането
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function debug_template_info() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_query;
        
        echo '<!-- Taxonomy Template Loader Debug Info -->';
        echo '<div style="position: fixed; bottom: 0; right: 0; background: rgba(0,0,0,0.8); color: white; padding: 10px; font-size: 12px; z-index: 9999; max-width: 300px;">';
        echo '<strong>Template Debug:</strong><br>';
        echo 'Page Type: ' . $this->get_current_page_type() . '<br>';
        
        if (is_tax()) {
            $queried_object = get_queried_object();
            echo 'Taxonomy: ' . $queried_object->taxonomy . '<br>';
            echo 'Term: ' . $queried_object->name . '<br>';
        }
        
        if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            echo 'Archive for: ' . $wp_query->query_vars['parfume_taxonomy_archive'] . '<br>';
        }
        
        echo '</div>';
    }
    
    /**
     * Логира template зареждането
     * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
     */
    public function log_template_loading($template) {
        $template_name = basename($template);
        $this->debug_log("Final template loaded: {$template_name} from {$template}");
        return $template;
    }
}