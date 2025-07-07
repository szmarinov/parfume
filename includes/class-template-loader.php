<?php
/**
 * Template Loader class for Parfume Catalog plugin
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Template_Loader {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_filter('template_include', array($this, 'template_loader'));
    }
    
    /**
     * Load custom templates
     */
    public function template_loader($template) {
        if (is_embed()) {
            return $template;
        }
        
        $default_file = $this->get_template_loader_default_file();
        
        if ($default_file) {
            $search_files = $this->get_template_loader_files($default_file);
            $template = locate_template($search_files);
            
            if (!$template) {
                $template = PARFUME_CATALOG_PLUGIN_DIR . 'templates/' . $default_file;
            }
        }
        
        return $template;
    }
    
    /**
     * Get the default filename for a template
     */
    private function get_template_loader_default_file() {
        if (is_singular('parfumes')) {
            $default_file = 'single-parfumes.php';
        } elseif (is_post_type_archive('parfumes')) {
            $default_file = 'archive-parfumes.php';
        } elseif (is_tax('tip')) {
            $default_file = 'taxonomy-tip.php';
        } elseif (is_tax('vid_aromat')) {
            $default_file = 'taxonomy-vid-aromat.php';
        } elseif (is_tax('marki')) {
            $default_file = 'taxonomy-marki.php';
        } elseif (is_tax('sezon')) {
            $default_file = 'taxonomy-sezon.php';
        } elseif (is_tax('intenzivnost')) {
            $default_file = 'taxonomy-intenzivnost.php';
        } elseif (is_tax('notki')) {
            $default_file = 'taxonomy-notki.php';
        } elseif (is_singular('parfume_blog')) {
            $default_file = 'single-parfume-blog.php';
        } elseif (is_post_type_archive('parfume_blog')) {
            $default_file = 'archive-parfume-blog.php';
        } else {
            $default_file = '';
        }
        
        return $default_file;
    }
    
    /**
     * Get an array of filenames to search for a given template
     */
    private function get_template_loader_files($default_file) {
        $search_files = array($default_file);
        $template = str_replace('.php', '', $default_file);
        
        // Look for more specific templates based on query
        if (is_singular('parfumes')) {
            $object = get_queried_object();
            $search_files[] = 'single-parfumes-' . $object->post_name . '.php';
            $search_files[] = 'parfume-catalog/single-parfumes-' . $object->post_name . '.php';
        } elseif (is_tax()) {
            $term = get_queried_object();
            $search_files[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
            $search_files[] = 'parfume-catalog/taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
        }
        
        // Add parfume-catalog folder prefix
        $search_files[] = 'parfume-catalog/' . $default_file;
        
        return array_unique($search_files);
    }
    
    /**
     * Get template part (for template files to include other template files)
     */
    public static function get_template_part($slug, $name = '') {
        $templates = array();
        
        if ($name) {
            $templates[] = "parfume-catalog/{$slug}-{$name}.php";
            $templates[] = "{$slug}-{$name}.php";
        }
        
        $templates[] = "parfume-catalog/{$slug}.php";
        $templates[] = "{$slug}.php";
        
        $template = locate_template($templates);
        
        if (!$template) {
            if ($name && file_exists(PARFUME_CATALOG_PLUGIN_DIR . "templates/{$slug}-{$name}.php")) {
                $template = PARFUME_CATALOG_PLUGIN_DIR . "templates/{$slug}-{$name}.php";
            } elseif (file_exists(PARFUME_CATALOG_PLUGIN_DIR . "templates/{$slug}.php")) {
                $template = PARFUME_CATALOG_PLUGIN_DIR . "templates/{$slug}.php";
            }
        }
        
        if ($template) {
            load_template($template, false);
        }
    }
    
    /**
     * Get template with data
     */
    public static function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        $located = self::locate_template($template_name, $template_path, $default_path);
        
        if (!file_exists($located)) {
            return;
        }
        
        include $located;
    }
    
    /**
     * Locate a template and return the path for inclusion
     */
    public static function locate_template($template_name, $template_path = '', $default_path = '') {
        if (!$template_path) {
            $template_path = 'parfume-catalog/';
        }
        
        if (!$default_path) {
            $default_path = PARFUME_CATALOG_PLUGIN_DIR . 'templates/';
        }
        
        // Look within passed path within the theme - this is priority
        $template = locate_template(array(
            trailingslashit($template_path) . $template_name,
            $template_name
        ));
        
        // Get default template
        if (!$template) {
            $template = $default_path . $template_name;
        }
        
        return $template;
    }
}