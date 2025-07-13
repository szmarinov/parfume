<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Template Loader - управлява зареждането на template файлове за таксономии
 */
class Taxonomy_Template_Loader {
    
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'));
    }
    
    /**
     * Зарежда подходящия template файл за таксономии
     */
    public function template_loader($template) {
        global $wp_query;
        
        // Check if this is our custom taxonomy archive
        if (isset($wp_query->query_vars['is_parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['is_parfume_taxonomy_archive'];
            
            // Load the appropriate archive template
            if ($taxonomy === 'marki') {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-marki.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            } elseif ($taxonomy === 'notes') {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-notes.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
            
            // Fallback to generic taxonomy archive
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-taxonomy.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Handle individual taxonomy terms
        if (is_tax('marki')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-marki.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('notes')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-notes.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('perfumer')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-perfumer.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('gender')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-gender.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('aroma_type')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-aroma_type.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('season')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-season.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('intensity')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-intensity.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
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
}