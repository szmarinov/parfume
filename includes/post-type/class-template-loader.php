<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Template Loader - управлява зареждането на template файлове
 * ПОПРАВЕНА ВЕРСИЯ - правилно зарежда perfumer templates
 */
class Template_Loader {
    
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'));
        add_filter('body_class', array($this, 'add_body_class'));
    }
    
    /**
     * Template loader - зарежда правилните template файлове
     * ПОПРАВЕНА ВЕРСИЯ
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
        
        // ПОПРАВЕНА ЧАСТ - Taxonomy templates за parfume таксономии
        if (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
            $queried_object = get_queried_object();
            
            if ($queried_object && isset($queried_object->taxonomy)) {
                $taxonomy = $queried_object->taxonomy;
                
                // СПЕЦИАЛНО ОБРАБОТВАНЕ ЗА PERFUMER ТАКСОНОМИЯ
                if ($taxonomy === 'perfumer') {
                    // Проверяваме дали е archive (няма конкретен term) или single perfumer
                    global $wp_query;
                    
                    // Проверяваме дали е perfumer archive (flag от rewrite handler)
                    $is_perfumer_archive = isset($wp_query->query_vars['is_perfumer_archive']);
                    
                    // Ако няма queried object или е archive flag
                    if ($is_perfumer_archive || empty($queried_object->name) || empty($queried_object->slug)) {
                        // Archive всички парфюмеристи
                        $taxonomy_perfumer_template = $this->locate_template('taxonomy-perfumer.php');
                        if ($taxonomy_perfumer_template) {
                            return $taxonomy_perfumer_template;
                        }
                    } else {
                        // Single perfumer - също използва taxonomy-perfumer.php
                        $taxonomy_perfumer_template = $this->locate_template('taxonomy-perfumer.php');
                        if ($taxonomy_perfumer_template) {
                            return $taxonomy_perfumer_template;
                        }
                    }
                }
                
                // За останалите таксономии
                $template_files = array(
                    'taxonomy-' . $taxonomy . '-' . $queried_object->slug . '.php',
                    'taxonomy-' . $taxonomy . '.php',
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
     * Намира template файл
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
     * Получава template hierarchy
     */
    private function get_template_hierarchy($post_type = null) {
        $templates = array();
        
        if (is_singular()) {
            $post = get_queried_object();
            $post_type = $post->post_type;
            
            $templates[] = "single-{$post_type}-{$post->post_name}.php";
            $templates[] = "single-{$post_type}.php";
            $templates[] = 'single.php';
        } elseif (is_post_type_archive()) {
            $post_type = get_query_var('post_type');
            if (is_array($post_type)) {
                $post_type = reset($post_type);
            }
            
            $templates[] = "archive-{$post_type}.php";
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
                
                // Добавяме специален клас за perfumer страници
                if ($queried_object->taxonomy === 'perfumer') {
                    if (!empty($queried_object->slug) && !empty($queried_object->name)) {
                        $classes[] = 'single-perfumer-page';
                    } else {
                        $classes[] = 'perfumers-archive-page';
                    }
                }
            }
        }
        
        return $classes;
    }
}