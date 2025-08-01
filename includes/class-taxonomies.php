<?php
namespace Parfume_Reviews;

/**
 * Taxonomies class - управлява всички таксономии за parfume review система
 * РЕВИЗИРАНА ВЕРСИЯ: Модулна структура с подкомпоненти
 * 
 * Файл: includes/class-taxonomies.php
 */
class Taxonomies {
    
    /**
     * Компоненти за таксономиите
     */
    public $registrar;
    public $meta_fields;
    public $template_loader;
    public $rewrite_handler;
    public $seo_support;
    
    /**
     * Supported taxonomies
     */
    private $supported_taxonomies = array(
        'marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'
    );
    
    public function __construct() {
        // Зареждаме всички компоненти
        $this->load_components();
        
        // Инициализираме
        $this->init();
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Taxonomies class initialized");
        }
    }
    
    /**
     * Зарежда всички taxonomy компоненти
     */
    private function load_components() {
        $components = array(
            'class-taxonomy-registrar.php' => 'Taxonomy_Registrar',
            'class-taxonomy-meta-fields.php' => 'Taxonomy_Meta_Fields',
            'class-taxonomy-template-loader.php' => 'Taxonomy_Template_Loader',
            'class-taxonomy-rewrite-handler.php' => 'Taxonomy_Rewrite_Handler',
            'class-taxonomy-seo-support.php' => 'Taxonomy_SEO_Support',
        );
        
        foreach ($components as $file => $class_name) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/' . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Loaded taxonomy component: {$file}");
                }
                
                $full_class_name = __NAMESPACE__ . '\\Taxonomies\\' . $class_name;
                if (class_exists($full_class_name)) {
                    $property_name = $this->get_property_name($class_name);
                    $this->$property_name = new $full_class_name();
                } else {
                    if (function_exists('parfume_reviews_debug_log')) {
                        parfume_reviews_debug_log("Class not found: {$full_class_name}", 'error');
                    }
                }
            } else {
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Missing taxonomy component: {$file}", 'warning');
                }
            }
        }
    }
    
    /**
     * Преобразува class name в property name
     */
    private function get_property_name($class_name) {
        // Taxonomy_Registrar -> registrar
        // Taxonomy_Meta_Fields -> meta_fields
        // Taxonomy_Template_Loader -> template_loader
        // Taxonomy_Rewrite_Handler -> rewrite_handler
        // Taxonomy_SEO_Support -> seo_support
        $property_name = str_replace('Taxonomy_', '', $class_name);
        $property_name = strtolower(preg_replace('/([A-Z])/', '_$1', $property_name));
        return ltrim($property_name, '_');
    }
    
    /**
     * Инициализира таксономията
     */
    public function init() {
        // Registrar инициализация - най-високоприоритетно
        if ($this->registrar) {
            add_action('init', array($this->registrar, 'register_taxonomies'), 0);
        } else {
            // Fallback ако registrar компонентът липсва
            add_action('init', array($this, 'fallback_register_taxonomies'), 0);
        }
        
        // Meta fields инициализация  
        if ($this->meta_fields) {
            add_action('init', array($this->meta_fields, 'init'));
        } else {
            // Fallback за meta fields
            add_action('init', array($this, 'fallback_add_taxonomy_meta_fields'));
        }
        
        // Template loader инициализация
        if ($this->template_loader) {
            add_action('init', array($this->template_loader, 'init'));
            add_filter('template_include', array($this->template_loader, 'template_loader'));
        } else {
            // Fallback за template loading
            add_filter('template_include', array($this, 'fallback_template_loader'));
        }
        
        // КРИТИЧНО: Rewrite handler инициализация с приоритет
        if ($this->rewrite_handler) {
            // Добавяме rewrite rules преди WordPress да генерира стандартните
            add_action('init', array($this->rewrite_handler, 'add_custom_rewrite_rules'), 5);
            
            // Добавяме query vars
            add_filter('query_vars', array($this->rewrite_handler, 'add_query_vars'), 10);
            
            // Парсваме custom requests с висок приоритет
            add_action('parse_request', array($this->rewrite_handler, 'parse_custom_requests'), 5);
            
            // 404 handling
            add_action('wp', array($this->rewrite_handler, 'handle_404_redirects'), 5);
        } else {
            // Fallback за rewrite rules
            add_action('init', array($this, 'fallback_add_custom_rewrite_rules'), 5);
            add_filter('query_vars', array($this, 'fallback_add_query_vars'), 10);
            add_action('parse_request', array($this, 'fallback_parse_custom_requests'), 5);
        }
        
        // SEO support инициализация
        if ($this->seo_support) {
            add_action('init', array($this->seo_support, 'init'));
        } else {
            // Fallback за SEO support
            add_action('init', array($this, 'fallback_add_seo_support'));
        }
        
        // Admin scripts енqueuing
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Taxonomies initialized");
        }
    }
    
    // ===== PUBLIC API METHODS (BACKWARD COMPATIBILITY) =====
    
    /**
     * Регистрира таксономиите
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function register_taxonomies() {
        if ($this->registrar && method_exists($this->registrar, 'register_taxonomies')) {
            return $this->registrar->register_taxonomies();
        }
        
        return $this->fallback_register_taxonomies();
    }
    
    /**
     * Добавя мета полета за таксономии
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function add_taxonomy_meta_fields() {
        if ($this->meta_fields && method_exists($this->meta_fields, 'add_taxonomy_meta_fields')) {
            return $this->meta_fields->add_taxonomy_meta_fields();
        }
        
        return $this->fallback_add_taxonomy_meta_fields();
    }
    
    /**
     * Записва мета полетата за таксономии
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        if ($this->meta_fields && method_exists($this->meta_fields, 'save_taxonomy_meta_fields')) {
            return $this->meta_fields->save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy);
        }
        
        return $this->fallback_save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy);
    }
    
    /**
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function add_custom_rewrite_rules() {
        if ($this->rewrite_handler && method_exists($this->rewrite_handler, 'add_custom_rewrite_rules')) {
            return $this->rewrite_handler->add_custom_rewrite_rules();
        }
        
        return $this->fallback_add_custom_rewrite_rules();
    }
    
    /**
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function add_query_vars($vars) {
        if ($this->rewrite_handler && method_exists($this->rewrite_handler, 'add_query_vars')) {
            return $this->rewrite_handler->add_query_vars($vars);
        }
        
        return $this->fallback_add_query_vars($vars);
    }
    
    /**
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function parse_custom_requests($wp) {
        if ($this->rewrite_handler && method_exists($this->rewrite_handler, 'parse_custom_requests')) {
            return $this->rewrite_handler->parse_custom_requests($wp);
        }
        
        return $this->fallback_parse_custom_requests($wp);
    }
    
    /**
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function add_seo_support() {
        if ($this->seo_support && method_exists($this->seo_support, 'add_seo_support')) {
            return $this->seo_support->add_seo_support();
        }
        
        return $this->fallback_add_seo_support();
    }
    
    /**
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function template_loader($template) {
        if ($this->template_loader && method_exists($this->template_loader, 'template_loader')) {
            return $this->template_loader->template_loader($template);
        }
        
        return $this->fallback_template_loader($template);
    }
    
    /**
     * BACKWARD COMPATIBILITY: Запазен оригинален публичен API
     */
    public function enqueue_admin_scripts($hook) {
        // Делегираме към meta_fields компонента ако съществува
        if ($this->meta_fields && method_exists($this->meta_fields, 'enqueue_admin_scripts')) {
            $this->meta_fields->enqueue_admin_scripts($hook);
        }
        
        // Fallback enqueue
        $this->fallback_enqueue_admin_scripts($hook);
    }
    
    // ===== NEW API METHODS =====
    
    /**
     * Получава поддържаните таксономии
     */
    public function get_supported_taxonomies() {
        return $this->supported_taxonomies;
    }
    
    /**
     * Проверява дали таксономия е поддържана
     */
    public function is_supported_taxonomy($taxonomy) {
        return in_array($taxonomy, $this->supported_taxonomies);
    }
    
    /**
     * Получава URL за архив на таксономия
     */
    public function get_taxonomy_archive_url($taxonomy) {
        if ($this->rewrite_handler && method_exists($this->rewrite_handler, 'get_taxonomy_archive_url')) {
            return $this->rewrite_handler->get_taxonomy_archive_url($taxonomy);
        }
        
        // Fallback
        if (!$this->is_supported_taxonomy($taxonomy)) {
            return false;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $taxonomy_slugs = array(
            'marki' => 'marki',
            'gender' => 'gender',
            'aroma_type' => 'aroma-type',
            'season' => 'season',
            'intensity' => 'intensity',
            'notes' => 'notes',
            'perfumer' => 'parfumeri'
        );
        
        $taxonomy_slug = isset($taxonomy_slugs[$taxonomy]) ? $taxonomy_slugs[$taxonomy] : $taxonomy;
        
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
    
    /**
     * Проверява дали има template за таксономия
     */
    public function has_taxonomy_template($taxonomy) {
        if ($this->template_loader && method_exists($this->template_loader, 'has_taxonomy_template')) {
            return $this->template_loader->has_taxonomy_template($taxonomy);
        }
        
        // Fallback проверка
        $template_hierarchy = array(
            "taxonomy-{$taxonomy}.php",
            "archive-{$taxonomy}.php",
            'taxonomy.php',
            'archive.php'
        );
        
        foreach ($template_hierarchy as $template_name) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
            if (file_exists($plugin_template)) {
                return true;
            }
            
            $theme_template = locate_template($template_name);
            if ($theme_template) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получава URL на изображение за term
     */
    public function get_term_image_url($term_id, $taxonomy, $size = 'medium') {
        $image_id = get_term_meta($term_id, $taxonomy . '_image', true);
        
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, $size);
            return $image_url ? $image_url : false;
        }
        
        return false;
    }
    
    /**
     * Получава notes по група (само за notes таксономия)
     */
    public function get_notes_by_group($group = '') {
        if (empty($group)) {
            return get_terms(array(
                'taxonomy' => 'notes',
                'hide_empty' => false
            ));
        }
        
        return get_terms(array(
            'taxonomy' => 'notes',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'notes_group',
                    'value' => $group,
                    'compare' => '='
                )
            )
        ));
    }
    
    /**
     * Получава статистики за таксономиите
     */
    public function get_taxonomy_stats() {
        $stats = array();
        
        foreach ($this->supported_taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            $stats[$taxonomy] = array(
                'total_terms' => is_array($terms) ? count($terms) : 0,
                'terms_with_posts' => 0,
                'total_posts' => 0
            );
            
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    if ($term->count > 0) {
                        $stats[$taxonomy]['terms_with_posts']++;
                        $stats[$taxonomy]['total_posts'] += $term->count;
                    }
                }
            }
        }
        
        return $stats;
    }
    
    // ===== FALLBACK METHODS =====
    
    /**
     * Fallback регистрация на таксономии
     */
    public function fallback_register_taxonomies() {
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Using fallback taxonomy registration", 'warning');
        }
        
        // Регистрираме основните таксономии
        $taxonomies_config = array(
            'marki' => array(
                'labels' => array(
                    'name' => __('Марки', 'parfume-reviews'),
                    'singular_name' => __('Марка', 'parfume-reviews')
                ),
                'public' => true,
                'rewrite' => array('slug' => 'marki')
            ),
            'gender' => array(
                'labels' => array(
                    'name' => __('Пол', 'parfume-reviews'),
                    'singular_name' => __('Пол', 'parfume-reviews')
                ),
                'public' => true,
                'rewrite' => array('slug' => 'gender')
            ),
            'notes' => array(
                'labels' => array(
                    'name' => __('Нотки', 'parfume-reviews'),
                    'singular_name' => __('Нотка', 'parfume-reviews')
                ),
                'public' => true,
                'rewrite' => array('slug' => 'notes')
            )
        );
        
        foreach ($taxonomies_config as $taxonomy => $args) {
            register_taxonomy($taxonomy, 'parfume', $args);
        }
        
        return true;
    }
    
    /**
     * Fallback за meta fields
     */
    public function fallback_add_taxonomy_meta_fields() {
        // Добавяме основно image поле за таксономии
        foreach ($this->supported_taxonomies as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', array($this, 'render_add_taxonomy_image_field'));
            add_action($taxonomy . '_edit_form_fields', array($this, 'render_edit_taxonomy_image_field'));
            add_action('edited_' . $taxonomy, array($this, 'save_taxonomy_image_field'));
            add_action('create_' . $taxonomy, array($this, 'save_taxonomy_image_field'));
        }
    }
    
    /**
     * Fallback за save meta fields
     */
    public function fallback_save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        // Basic image field save
        if (isset($_POST[$taxonomy . '_image'])) {
            $image_id = intval($_POST[$taxonomy . '_image']);
            update_term_meta($term_id, $taxonomy . '_image', $image_id);
        }
    }
    
    /**
     * Fallback template loader
     */
    public function fallback_template_loader($template) {
        if (!is_tax($this->supported_taxonomies)) {
            return $template;
        }
        
        $queried_object = get_queried_object();
        if (!$queried_object || !isset($queried_object->taxonomy)) {
            return $template;
        }
        
        $taxonomy = $queried_object->taxonomy;
        
        // Търсим template файлове
        $template_hierarchy = array(
            "taxonomy-{$taxonomy}-{$queried_object->slug}.php",
            "taxonomy-{$taxonomy}.php",
            'taxonomy.php'
        );
        
        foreach ($template_hierarchy as $template_name) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Fallback за rewrite rules
     */
    public function fallback_add_custom_rewrite_rules() {
        // Добавяме основни rewrite rules
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        foreach ($this->supported_taxonomies as $taxonomy) {
            add_rewrite_rule(
                "^{$parfume_slug}/{$taxonomy}/([^/]+)/?$",
                "index.php?{$taxonomy}=\$matches[1]",
                'top'
            );
        }
    }
    
    /**
     * Fallback за query vars
     */
    public function fallback_add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_filter';
        return $vars;
    }
    
    /**
     * Fallback за parse requests
     */
    public function fallback_parse_custom_requests($wp) {
        // Basic request parsing
        return $wp;
    }
    
    /**
     * Fallback за SEO support
     */
    public function fallback_add_seo_support() {
        // Basic SEO мета tags
        add_action('wp_head', array($this, 'add_basic_seo_meta'));
    }
    
    /**
     * Fallback за admin scripts
     */
    public function fallback_enqueue_admin_scripts($hook) {
        if ($hook === 'edit-tags.php' || $hook === 'term.php') {
            wp_enqueue_media();
            
            wp_enqueue_script(
                'parfume-taxonomy-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/taxonomy-admin.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
        }
    }
    
    // ===== UTILITY METHODS =====
    
    /**
     * Render image field for taxonomy add form
     */
    public function render_add_taxonomy_image_field($taxonomy) {
        echo '<div class="form-field">';
        echo '<label for="' . $taxonomy . '_image">' . __('Изображение', 'parfume-reviews') . '</label>';
        echo '<input type="hidden" id="' . $taxonomy . '_image" name="' . $taxonomy . '_image" value="" />';
        echo '<button type="button" class="button taxonomy-image-upload">' . __('Избери изображение', 'parfume-reviews') . '</button>';
        echo '<div class="taxonomy-image-preview"></div>';
        echo '</div>';
    }
    
    /**
     * Render image field for taxonomy edit form
     */
    public function render_edit_taxonomy_image_field($term, $taxonomy) {
        $image_id = get_term_meta($term->term_id, $taxonomy . '_image', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
        
        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="' . $taxonomy . '_image">' . __('Изображение', 'parfume-reviews') . '</label></th>';
        echo '<td>';
        echo '<input type="hidden" id="' . $taxonomy . '_image" name="' . $taxonomy . '_image" value="' . esc_attr($image_id) . '" />';
        echo '<button type="button" class="button taxonomy-image-upload">' . __('Избери изображение', 'parfume-reviews') . '</button>';
        if ($image_url) {
            echo '<div class="taxonomy-image-preview"><img src="' . esc_url($image_url) . '" style="max-width: 150px; height: auto;" /></div>';
        } else {
            echo '<div class="taxonomy-image-preview"></div>';
        }
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Save image field
     */
    public function save_taxonomy_image_field($term_id) {
        if (!current_user_can('manage_categories')) {
            return;
        }
        
        $taxonomy = $_POST['taxonomy'];
        if (isset($_POST[$taxonomy . '_image'])) {
            $image_id = intval($_POST[$taxonomy . '_image']);
            update_term_meta($term_id, $taxonomy . '_image', $image_id);
        }
    }
    
    /**
     * Добавя основни SEO мета tags
     */
    public function add_basic_seo_meta() {
        if (!is_tax($this->supported_taxonomies)) {
            return;
        }
        
        $queried_object = get_queried_object();
        if (!$queried_object) {
            return;
        }
        
        $title = $queried_object->name;
        $description = $queried_object->description ? $queried_object->description : '';
        
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        if ($description) {
            echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        }
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_term_link($queried_object)) . '" />' . "\n";
    }
    
    /**
     * Получава компонент instance
     */
    public function get_component($component_name) {
        if (property_exists($this, $component_name)) {
            return $this->$component_name;
        }
        
        return null;
    }
    
    /**
     * Проверява дали компонент е зареден
     */
    public function has_component($component_name) {
        return property_exists($this, $component_name) && $this->$component_name !== null;
    }
}