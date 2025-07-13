<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Template Loader - управлява зареждането на template файлове за таксономии
 * 📁 Файл: includes/taxonomies/class-taxonomy-template-loader.php
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
            $archive_templates = array(
                'marki' => 'templates/archive-marki.php',
                'notes' => 'templates/archive-notes.php',
                'gender' => 'templates/archive-gender.php',
                'aroma_type' => 'templates/archive-aroma_type.php',
                'season' => 'templates/archive-season.php',
                'intensity' => 'templates/archive-intensity.php',
                'perfumer' => 'templates/archive-perfumer.php',
            );
            
            if (isset($archive_templates[$taxonomy])) {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . $archive_templates[$taxonomy];
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
            // ВАЖНО: Perfumer използва single-perfumer.php template за детайлни страници
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-perfumer.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
            
            // Fallback to taxonomy-perfumer.php
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
    
    /**
     * Получава всички налични taxonomy templates
     */
    public function get_available_taxonomy_templates() {
        $templates = array();
        $template_dir = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/';
        
        if (is_dir($template_dir)) {
            $files = scandir($template_dir);
            foreach ($files as $file) {
                if (preg_match('/^taxonomy-(.+)\.php$/', $file, $matches)) {
                    $templates['taxonomy'][] = $matches[1];
                } elseif (preg_match('/^archive-(.+)\.php$/', $file, $matches)) {
                    $templates['archive'][] = $matches[1];
                }
            }
        }
        
        return $templates;
    }
    
    /**
     * Получава всички поддържани taxonomies с техните template файлове
     */
    public function get_taxonomies_with_templates() {
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        $result = array();
        
        foreach ($taxonomies as $taxonomy) {
            $result[$taxonomy] = array(
                'has_taxonomy_template' => $this->has_taxonomy_template($taxonomy),
                'has_archive_template' => $this->has_taxonomy_archive_template($taxonomy),
                'taxonomy_template_path' => $this->get_taxonomy_template_path($taxonomy),
                'archive_template_path' => $this->get_taxonomy_archive_template_path($taxonomy),
            );
        }
        
        return $result;
    }
}