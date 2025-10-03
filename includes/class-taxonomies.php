<?php
/**
 * Taxonomies Class
 * 
 * Handles all taxonomy-related functionality for the Parfume Reviews plugin.
 * Manages registration, meta fields, template loading, rewrite handling, and SEO support.
 *
 * @package ParfumeReviews
 * @since 1.0.0
 * 
 * ФАЙЛ: includes/class-taxonomies.php
 * ПОПРАВЕНА ВЕРСИЯ - Фиксиран namespace проблем
 */

namespace Parfume_Reviews;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Taxonomies {
    
    private $taxonomy_registrar;
    private $meta_fields;
    private $template_loader;
    private $rewrite_handler;
    private $seo_support;
    
    public function __construct() {
        $this->load_components();
        $this->init();
    }
    
    /**
     * Зарежда всички компоненти на таксономията
     * ПОПРАВЕНО: Правилен namespace - Parfume_Reviews\Taxonomies\ClassName
     */
    private function load_components() {
        $components = array(
            'class-taxonomy-registrar.php'      => 'Taxonomy_Registrar',
            'class-taxonomy-meta-fields.php'    => 'Taxonomy_Meta_Fields', 
            'class-taxonomy-template-loader.php' => 'Taxonomy_Template_Loader',
            'class-taxonomy-rewrite-handler.php' => 'Taxonomy_Rewrite_Handler',
            'class-taxonomy-seo-support.php'    => 'Taxonomy_SEO_Support'
        );
        
        foreach ($components as $file => $class_name) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/' . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                parfume_reviews_debug_log("Loaded taxonomy component: {$file}");
                
                // ПОПРАВЕНО: Правилен namespace path
                $full_class_name = 'Parfume_Reviews\\Taxonomies\\' . $class_name;
                
                if (class_exists($full_class_name)) {
                    $property_name = $this->get_property_name($class_name);
                    $this->$property_name = new $full_class_name();
                    parfume_reviews_debug_log("Initialized component: {$class_name} as {$property_name}");
                } else {
                    parfume_reviews_debug_log("Class not found: {$full_class_name}");
                }
            } else {
                parfume_reviews_debug_log("Missing taxonomy component: {$file}");
            }
        }
    }
    
    /**
     * Преобразува class name в property name
     * ПОПРАВЕНО: По-ясна логика
     */
    private function get_property_name($class_name) {
        // Taxonomy_Registrar -> registrar
        // Taxonomy_Meta_Fields -> meta_fields
        // Taxonomy_Template_Loader -> template_loader
        // Taxonomy_Rewrite_Handler -> rewrite_handler
        // Taxonomy_SEO_Support -> seo_support
        
        // Премахваме Taxonomy_ префикс
        $property_name = str_replace('Taxonomy_', '', $class_name);
        
        // Преобразуваме CamelCase в snake_case
        $property_name = strtolower(preg_replace('/([A-Z])/', '_$1', $property_name));
        
        // Премахваме началното _
        return ltrim($property_name, '_');
    }
    
    /**
     * Инициализира таксономията
     */
    public function init() {
        // Registrar инициализация
        if ($this->registrar) {
            add_action('init', array($this->registrar, 'register_taxonomies'), 0);
            parfume_reviews_debug_log("Registrar hooks added");
        }
        
        // Meta fields инициализация  
        if ($this->meta_fields) {
            add_action('init', array($this->meta_fields, 'init'));
            parfume_reviews_debug_log("Meta fields hooks added");
        }
        
        // Template loader инициализация
        if ($this->template_loader) {
            add_action('init', array($this->template_loader, 'init'));
            parfume_reviews_debug_log("Template loader hooks added");
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
            
            parfume_reviews_debug_log("Rewrite handler hooks added");
        }
        
        // SEO support инициализация
        if ($this->seo_support) {
            add_action('init', array($this->seo_support, 'init'));
            parfume_reviews_debug_log("SEO support hooks added");
        }
        
        parfume_reviews_debug_log("Taxonomies initialized successfully");
    }
    
    /**
     * Регистрира таксономиите
     * DEPRECATED: Използвайте $this->registrar->register_taxonomies()
     */
    public function register_taxonomies() {
        if (!$this->registrar) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot register taxonomies - registrar not loaded");
            }
            return false;
        }
        
        return $this->registrar->register_taxonomies();
    }
    
    /**
     * Добавя мета полета за таксономии
     * DEPRECATED: Използвайте $this->meta_fields->add_taxonomy_meta_fields()
     */
    public function add_taxonomy_meta_fields() {
        if (!$this->meta_fields) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot add meta fields - meta_fields not loaded");
            }
            return false;
        }
        
        return $this->meta_fields->add_taxonomy_meta_fields();
    }
    
    /**
     * Записва мета полетата за таксономии
     * DEPRECATED: Използвайте $this->meta_fields->save_taxonomy_meta_fields()
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        if (!$this->meta_fields) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot save meta fields - meta_fields not loaded");
            }
            return false;
        }
        
        return $this->meta_fields->save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy);
    }
    
    /**
     * Добавя custom rewrite rules
     * DEPRECATED: Използвайте $this->rewrite_handler->add_custom_rewrite_rules()
     */
    public function add_custom_rewrite_rules() {
        if (!$this->rewrite_handler) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot add rewrite rules - rewrite_handler not loaded");
            }
            return false;
        }
        
        return $this->rewrite_handler->add_custom_rewrite_rules();
    }
    
    /**
     * Добавя query vars
     * DEPRECATED: Използвайте $this->rewrite_handler->add_query_vars()
     */
    public function add_query_vars($vars) {
        if (!$this->rewrite_handler) {
            return $vars;
        }
        
        return $this->rewrite_handler->add_query_vars($vars);
    }
    
    /**
     * Парсва custom requests
     * DEPRECATED: Използвайте $this->rewrite_handler->parse_custom_requests()
     */
    public function parse_custom_requests($wp) {
        if (!$this->rewrite_handler) {
            return;
        }
        
        return $this->rewrite_handler->parse_custom_requests($wp);
    }
    
    /**
     * Зарежда template
     * DEPRECATED: Използвайте $this->template_loader->load_template()
     */
    public function template_loader($template) {
        if (!$this->template_loader) {
            return $template;
        }
        
        return $this->template_loader->load_template($template);
    }
    
    /**
     * Добавя SEO поддръжка
     * DEPRECATED: Използвайте $this->seo_support->add_seo_support()
     */
    public function add_seo_support() {
        if (!$this->seo_support) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot add SEO support - seo_support not loaded");
            }
            return false;
        }
        
        return $this->seo_support->add_seo_support();
    }
    
    /**
     * Зарежда admin scripts
     * DEPRECATED: Използвайте $this->meta_fields->enqueue_admin_scripts()
     */
    public function enqueue_admin_scripts($hook) {
        if (!$this->meta_fields) {
            return;
        }
        
        return $this->meta_fields->enqueue_admin_scripts($hook);
    }
    
    // ============================================
    // НОВИ API МЕТОДИ ЗА УЛЕСНЕНА РАБОТА
    // ============================================
    
    /**
     * Получава поддържани таксономии
     */
    public function get_supported_taxonomies() {
        return array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    }
    
    /**
     * Получава URL за архив на таксономия
     */
    public function get_taxonomy_archive_url($taxonomy) {
        if ($this->rewrite_handler && method_exists($this->rewrite_handler, 'get_taxonomy_archive_url')) {
            return $this->rewrite_handler->get_taxonomy_archive_url($taxonomy);
        }
        return false;
    }
    
    /**
     * Проверява дали има template за таксономия
     */
    public function has_taxonomy_template($taxonomy) {
        if ($this->template_loader && method_exists($this->template_loader, 'has_taxonomy_template')) {
            return $this->template_loader->has_taxonomy_template($taxonomy);
        }
        return false;
    }
    
    /**
     * Получава изображение URL на term
     */
    public function get_term_image_url($term_id, $taxonomy, $size = 'thumbnail') {
        if ($this->meta_fields && method_exists($this->meta_fields, 'get_term_image_url')) {
            return $this->meta_fields->get_term_image_url($term_id, $taxonomy, $size);
        }
        return false;
    }
    
    /**
     * Получава ноти по групи
     */
    public function get_notes_by_group($group) {
        if ($this->meta_fields && method_exists($this->meta_fields, 'get_notes_by_group')) {
            return $this->meta_fields->get_notes_by_group($group);
        }
        return array();
    }
    
    /**
     * Получава статистики за таксономии
     */
    public function get_taxonomy_stats() {
        $stats = array();
        $taxonomies = $this->get_supported_taxonomies();
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            $stats[$taxonomy] = array(
                'total_terms' => is_array($terms) ? count($terms) : 0,
                'has_image_support' => in_array($taxonomy, array('marki', 'gender', 'notes', 'perfumer'))
            );
        }
        
        return $stats;
    }
}