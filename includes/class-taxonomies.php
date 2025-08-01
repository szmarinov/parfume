<?php
/**
 * Taxonomies Class
 * 
 * Handles all taxonomy-related functionality for the Parfume Reviews plugin.
 * Manages registration, meta fields, template loading, rewrite handling, and SEO support.
 *
 * @package ParfumeReviews
 * @since 1.0.0
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
                
                $full_class_name = __NAMESPACE__ . '\\Taxonomies\\' . $class_name;
                if (class_exists($full_class_name)) {
                    $property_name = $this->get_property_name($class_name);
                    $this->$property_name = new $full_class_name();
                }
            } else {
                parfume_reviews_debug_log("Missing taxonomy component: {$file}");
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
        // Registrar инициализация
        if ($this->registrar) {
            add_action('init', array($this->registrar, 'register_taxonomies'), 0);
        }
        
        // Meta fields инициализация  
        if ($this->meta_fields) {
            add_action('init', array($this->meta_fields, 'init'));
        }
        
        // Template loader инициализация
        if ($this->template_loader) {
            add_action('init', array($this->template_loader, 'init'));
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
        }
        
        // SEO support инициализация
        if ($this->seo_support) {
            add_action('init', array($this->seo_support, 'init'));
        }
        
        parfume_reviews_debug_log("Taxonomies initialized");
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
     * DEPRECATED: Използвайте $this->rewrite_handler->add_query_vars()
     */
    public function add_query_vars($vars) {
        if (!$this->rewrite_handler) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot add query vars - rewrite_handler not loaded");
            }
            return $vars; // Връщаме оригиналните vars
        }
        
        return $this->rewrite_handler->add_query_vars($vars);
    }
    
    /**
     * DEPRECATED: Използвайте $this->rewrite_handler->parse_custom_requests()
     */
    public function parse_custom_requests($wp) {
        if (!$this->rewrite_handler) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot parse requests - rewrite_handler not loaded");
            }
            return false;
        }
        
        return $this->rewrite_handler->parse_custom_requests($wp);
    }
    
    /**
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
     * Получава всички регистрирани таксономии за парфюми
     */
    public function get_parfume_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    /**
     * Проверява дали дадена таксономия е валидна за парфюми
     */
    public function is_parfume_taxonomy($taxonomy) {
        return in_array($taxonomy, $this->get_parfume_taxonomies());
    }
    
    /**
     * Получава taxonomy object по име
     */
    public function get_taxonomy($taxonomy) {
        if ($this->is_parfume_taxonomy($taxonomy)) {
            return get_taxonomy($taxonomy);
        }
        return false;
    }
    
    /**
     * Получава всички terms за дадена таксономия
     */
    public function get_terms($taxonomy, $args = array()) {
        if (!$this->is_parfume_taxonomy($taxonomy)) {
            return array();
        }
        
        $default_args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return get_terms($args);
    }
    
    /**
     * Получава мета стойност за term
     */
    public function get_term_meta($term_id, $meta_key, $single = true) {
        return get_term_meta($term_id, $meta_key, $single);
    }
    
    /**
     * Записва мета стойност за term
     */
    public function update_term_meta($term_id, $meta_key, $meta_value) {
        return update_term_meta($term_id, $meta_key, $meta_value);
    }
    
    /**
     * Изтрива мета стойност за term
     */
    public function delete_term_meta($term_id, $meta_key, $meta_value = '') {
        return delete_term_meta($term_id, $meta_key, $meta_value);
    }
    
    /**
     * Проверява дали term има мета стойност
     */
    public function term_has_meta($term_id, $meta_key) {
        $meta_value = $this->get_term_meta($term_id, $meta_key);
        return !empty($meta_value);
    }
    
    /**
     * Получава URL за taxonomy archive
     */
    public function get_taxonomy_archive_url($taxonomy) {
        if (!$this->is_parfume_taxonomy($taxonomy)) {
            return false;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $taxonomy_slugs = array(
            'marki' => 'marki',
            'notes' => 'notki', 
            'perfumer' => 'parfumeri',
            'gender' => 'gender',
            'aroma_type' => 'aroma-type',
            'season' => 'season',
            'intensity' => 'intensity'
        );
        
        $taxonomy_slug = isset($taxonomy_slugs[$taxonomy]) ? $taxonomy_slugs[$taxonomy] : $taxonomy;
        
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
    
    /**
     * Получава URL за term
     */
    public function get_term_url($term, $taxonomy) {
        if (!$this->is_parfume_taxonomy($taxonomy)) {
            return false;
        }
        
        $term_obj = is_object($term) ? $term : get_term($term, $taxonomy);
        if (!$term_obj || is_wp_error($term_obj)) {
            return false;
        }
        
        $archive_url = $this->get_taxonomy_archive_url($taxonomy);
        if (!$archive_url) {
            return false;
        }
        
        return $archive_url . $term_obj->slug . '/';
    }
    
    /**
     * Проверява дали се намира на taxonomy archive страница
     */
    public function is_taxonomy_archive($taxonomy = null) {
        if ($taxonomy) {
            return is_tax($taxonomy);
        }
        
        foreach ($this->get_parfume_taxonomies() as $tax) {
            if (is_tax($tax)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получава текущата таксономия ако се намира на archive страница
     */
    public function get_current_taxonomy() {
        foreach ($this->get_parfume_taxonomies() as $taxonomy) {
            if (is_tax($taxonomy)) {
                return $taxonomy;
            }
        }
        
        return false;
    }
    
    /**
     * Получава текущия term ако се намира на term страница
     */
    public function get_current_term() {
        $current_taxonomy = $this->get_current_taxonomy();
        if ($current_taxonomy) {
            return get_queried_object();
        }
        
        return false;
    }
    
    /**
     * Флъшва rewrite rules
     */
    public function flush_rewrite_rules() {
        flush_rewrite_rules();
        parfume_reviews_debug_log("Flushed rewrite rules from Taxonomies class");
    }
    
    /**
     * Debug функция за проверка на състоянието
     */
    public function debug_status() {
        if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $status = array(
            'registrar' => isset($this->registrar) ? 'loaded' : 'not loaded',
            'meta_fields' => isset($this->meta_fields) ? 'loaded' : 'not loaded',
            'template_loader' => isset($this->template_loader) ? 'loaded' : 'not loaded',
            'rewrite_handler' => isset($this->rewrite_handler) ? 'loaded' : 'not loaded',
            'seo_support' => isset($this->seo_support) ? 'loaded' : 'not loaded'
        );
        
        parfume_reviews_debug_log("Taxonomies components status: " . json_encode($status));
        
        if (isset($this->rewrite_handler)) {
            $this->rewrite_handler->debug_rewrite_rules();
        }
    }
}