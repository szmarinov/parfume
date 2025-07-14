<?php
namespace Parfume_Reviews;

// Load all taxonomy components
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-registrar.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-meta-fields.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-template-loader.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-rewrite-handler.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-seo-support.php';

/**
 * Main Taxonomies class - координира всички taxonomy компоненти
 */
class Taxonomies {
    
    /**
     * @var Taxonomies\Taxonomy_Registrar
     */
    public $registrar;
    
    /**
     * @var Taxonomies\Taxonomy_Meta_Fields
     */
    public $meta_fields;
    
    /**
     * @var Taxonomies\Taxonomy_Template_Loader
     */
    public $template_loader;
    
    /**
     * @var Taxonomies\Taxonomy_Rewrite_Handler
     */
    public $rewrite_handler;
    
    /**
     * @var Taxonomies\Taxonomy_SEO_Support
     */
    public $seo_support;
    
    public function __construct() {
        $this->init_components();
    }
    
    /**
     * Инициализира всички компоненти
     */
    private function init_components() {
        // Initialize all taxonomy components
        $this->registrar = new Taxonomies\Taxonomy_Registrar();
        $this->meta_fields = new Taxonomies\Taxonomy_Meta_Fields();
        $this->template_loader = new Taxonomies\Taxonomy_Template_Loader();
        $this->rewrite_handler = new Taxonomies\Taxonomy_Rewrite_Handler();
        $this->seo_support = new Taxonomies\Taxonomy_SEO_Support();
    }
    
    /**
     * Получава всички поддържани таксономии
     */
    public function get_supported_taxonomies() {
        return array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    }
    
    /**
     * Проверява дали дадена таксономия е поддържана
     */
    public function is_supported_taxonomy($taxonomy) {
        return in_array($taxonomy, $this->get_supported_taxonomies());
    }
    
    /**
     * Получава URL за архив на таксономия
     */
    public function get_taxonomy_archive_url($taxonomy) {
        if (!$this->is_supported_taxonomy($taxonomy)) {
            return false;
        }
        
        return $this->rewrite_handler->get_taxonomy_archive_url($taxonomy);
    }
    
    /**
     * Получава slug за дадена таксономия
     */
    public function get_taxonomy_slug($taxonomy) {
        if (!$this->is_supported_taxonomy($taxonomy)) {
            return false;
        }
        
        return $this->rewrite_handler->get_taxonomy_slug($taxonomy);
    }
    
    /**
     * Проверява дали съществува template за дадена таксономия
     */
    public function has_taxonomy_template($taxonomy) {
        return $this->template_loader->has_taxonomy_template($taxonomy);
    }
    
    /**
     * Проверява дали съществува archive template за дадена таксономия
     */
    public function has_taxonomy_archive_template($taxonomy) {
        return $this->template_loader->has_taxonomy_archive_template($taxonomy);
    }
    
    /**
     * Проверява дали текущата страница е архив на таксономия
     */
    public function is_taxonomy_archive($taxonomy = null) {
        return $this->rewrite_handler->is_taxonomy_archive($taxonomy);
    }
    
    /**
     * Получава изображение за таксономия term
     */
    public function get_term_image($term_id, $taxonomy, $size = 'thumbnail') {
        $image_id = get_term_meta($term_id, $taxonomy . '-image-id', true);
        if ($image_id) {
            return wp_get_attachment_image($image_id, $size);
        }
        return false;
    }
    
    /**
     * Получава ID на изображение за таксономия term
     */
    public function get_term_image_id($term_id, $taxonomy) {
        return get_term_meta($term_id, $taxonomy . '-image-id', true);
    }
    
    /**
     * Получава URL на изображение за таксономия term
     */
    public function get_term_image_url($term_id, $taxonomy, $size = 'thumbnail') {
        $image_id = $this->get_term_image_id($term_id, $taxonomy);
        if ($image_id) {
            return wp_get_attachment_image_url($image_id, $size);
        }
        return false;
    }
    
    /**
     * Получава група на нотка (само за notes таксономия)
     */
    public function get_note_group($term_id) {
        return get_term_meta($term_id, 'note_group', true);
    }
    
    /**
     * Получава всички нотки от дадена група
     */
    public function get_notes_by_group($group) {
        $terms = get_terms(array(
            'taxonomy' => 'notes',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'note_group',
                    'value' => $group,
                    'compare' => '='
                )
            )
        ));
        
        return !is_wp_error($terms) ? $terms : array();
    }
    
    /**
     * Получава статистики за таксономии
     */
    public function get_taxonomy_stats() {
        $stats = array();
        
        foreach ($this->get_supported_taxonomies() as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            $stats[$taxonomy] = array(
                'total_terms' => !is_wp_error($terms) ? count($terms) : 0,
                'used_terms' => !is_wp_error($terms) ? count(array_filter($terms, function($term) {
                    return $term->count > 0;
                })) : 0
            );
        }
        
        return $stats;
    }
    
    // Backward compatibility methods - запазваме съществуващите методи
    
    /**
     * @deprecated Използвайте $this->registrar->register_taxonomies()
     */
    public function register_taxonomies() {
        return $this->registrar->register_taxonomies();
    }
    
    /**
     * @deprecated Използвайте $this->template_loader->template_loader()
     */
    public function template_loader($template) {
        return $this->template_loader->template_loader($template);
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->add_taxonomy_meta_fields()
     */
    public function add_taxonomy_meta_fields() {
        return $this->meta_fields->add_taxonomy_meta_fields();
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->save_taxonomy_meta_fields()
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        return $this->meta_fields->save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy);
    }
    
    /**
     * @deprecated Използвайте $this->rewrite_handler->add_custom_rewrite_rules()
     */
    public function add_custom_rewrite_rules() {
        return $this->rewrite_handler->add_custom_rewrite_rules();
    }
    
    /**
     * @deprecated Използвайте $this->rewrite_handler->add_query_vars()
     */
    public function add_query_vars($vars) {
        return $this->rewrite_handler->add_query_vars($vars);
    }
    
    /**
     * @deprecated Използвайте $this->rewrite_handler->parse_custom_requests()
     */
    public function parse_custom_requests($wp) {
        return $this->rewrite_handler->parse_custom_requests($wp);
    }
    
    /**
     * @deprecated Използвайте $this->seo_support->add_seo_support()
     */
    public function add_seo_support() {
        return $this->seo_support->add_seo_support();
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->enqueue_admin_scripts()
     */
    public function enqueue_admin_scripts($hook) {
        return $this->meta_fields->enqueue_admin_scripts($hook);
    }
}