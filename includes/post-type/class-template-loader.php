<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Template Loader - управлява зареждането на template файлове
 */
class Template_Loader {
    
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'));
    }
    
    /**
     * Template loader - зарежда правилните template файлове
     */
    public function template_loader($template) {
        // Single parfume template
        if (is_singular('parfume')) {
            $plugin_template = $this->locate_template('single-parfume.php');
            if ($plugin_template) {
                return $plugin_template;
            }
        }
        
        // Single blog template
        if (is_singular('parfume_blog')) {
            $plugin_template = $this->locate_template('single-parfume-blog.php');
            if ($plugin_template) {
                return $plugin_template;
            }
        }
        
        // Blog archive template
        if (is_post_type_archive('parfume_blog')) {
            $plugin_template = $this->locate_template('archive-parfume-blog.php');
            if ($plugin_template) {
                return $plugin_template;
            }
        }
        
        // Parfume archive template
        if (is_post_type_archive('parfume')) {
            $plugin_template = $this->locate_template('archive-parfume.php');
            if ($plugin_template) {
                return $plugin_template;
            }
        }
        
        // Taxonomy templates - специфични таксономии
        if (is_tax(array('marki', 'notes', 'perfumer'))) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $template_files = array(
                    'taxonomy-' . $queried_object->taxonomy . '-' . $queried_object->slug . '.php',
                    'taxonomy-' . $queried_object->taxonomy . '.php',
                    'taxonomy.php'
                );
                
                foreach ($template_files as $template_file) {
                    $plugin_template = $this->locate_template($template_file);
                    if ($plugin_template) {
                        return $plugin_template;
                    }
                }
            }
        }
        
        // Generic taxonomy templates
        if (is_tax(array('gender', 'aroma_type', 'season', 'intensity'))) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $template_files = array(
                    'taxonomy-' . $queried_object->taxonomy . '.php',
                    'taxonomy.php'
                );
                
                foreach ($template_files as $template_file) {
                    $plugin_template = $this->locate_template($template_file);
                    if ($plugin_template) {
                        return $plugin_template;
                    }
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Намира template файла - първо в темата, после в плъгина
     */
    private function locate_template($template_name) {
        // Първо търсим в темата
        $theme_template = locate_template(array(
            'parfume-reviews/' . $template_name,
            $template_name
        ));
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // После в плъгина
        $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Получава правилния template за конкретна страница
     */
    public function get_template_hierarchy() {
        $templates = array();
        
        if (is_singular('parfume')) {
            global $post;
            $templates[] = 'single-parfume-' . $post->post_name . '.php';
            $templates[] = 'single-parfume.php';
            $templates[] = 'single.php';
        } elseif (is_singular('parfume_blog')) {
            global $post;
            $templates[] = 'single-parfume-blog-' . $post->post_name . '.php';
            $templates[] = 'single-parfume-blog.php';
            $templates[] = 'single.php';
        } elseif (is_post_type_archive('parfume')) {
            $templates[] = 'archive-parfume.php';
            $templates[] = 'archive.php';
        } elseif (is_post_type_archive('parfume_blog')) {
            $templates[] = 'archive-parfume-blog.php';
            $templates[] = 'archive.php';
        } elseif (is_tax()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $templates[] = 'taxonomy-' . $queried_object->taxonomy . '-' . $queried_object->slug . '.php';
                $templates[] = 'taxonomy-' . $queried_object->taxonomy . '.php';
                $templates[] = 'taxonomy.php';
                $templates[] = 'archive.php';
            }
        }
        
        return $templates;
    }
    
    /**
     * Зарежда template part
     */
    public function get_template_part($slug, $name = null) {
        $templates = array();
        $name = (string) $name;
        
        if ($name !== '') {
            $templates[] = "parfume-reviews/{$slug}-{$name}.php";
            $templates[] = "{$slug}-{$name}.php";
        }
        
        $templates[] = "parfume-reviews/{$slug}.php";
        $templates[] = "{$slug}.php";
        
        $template = locate_template($templates);
        
        if (!$template) {
            // Fallback to plugin templates
            foreach ($templates as $template_name) {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
                if (file_exists($plugin_template)) {
                    $template = $plugin_template;
                    break;
                }
            }
        }
        
        if ($template) {
            load_template($template, false);
        }
    }
    
    /**
     * Включва template файл с данни
     */
    public function include_template($template_name, $variables = array()) {
        $template_path = $this->locate_template($template_name);
        
        if ($template_path) {
            if (!empty($variables) && is_array($variables)) {
                extract($variables);
            }
            include $template_path;
            return true;
        }
        
        return false;
    }
    
    /**
     * Получава съдържанието на template като string
     */
    public function get_template_content($template_name, $variables = array()) {
        ob_start();
        $included = $this->include_template($template_name, $variables);
        $content = ob_get_clean();
        
        return $included ? $content : false;
    }
    
    /**
     * Проверява дали template файл съществува
     */
    public function template_exists($template_name) {
        return (bool) $this->locate_template($template_name);
    }
    
    /**
     * Получава всички налични template файлове
     */
    public function get_available_templates() {
        $templates = array();
        $template_dir = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/';
        
        if (is_dir($template_dir)) {
            $files = scandir($template_dir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $templates[] = $file;
                }
            }
        }
        
        return $templates;
    }
    
    /**
     * Добавя CSS клас към body за parfume страници
     */
    public function add_body_class($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume-page';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog-page';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'parfume-archive-page';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'parfume-blog-archive-page';
        } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            $classes[] = 'parfume-taxonomy-page';
            
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            }
        }
        
        return $classes;
    }
}