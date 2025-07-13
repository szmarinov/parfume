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
 * 📁 Файл: includes/class-taxonomies.php
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
        // Initialize all taxonomy components with proper priority
        $this->registrar = new Taxonomies\Taxonomy_Registrar();
        $this->meta_fields = new Taxonomies\Taxonomy_Meta_Fields();
        $this->template_loader = new Taxonomies\Taxonomy_Template_Loader(); // Приоритет 10 (по-нисък от post loader)
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
     * Проверява дали дадена таксономия има template
     */
    public function has_taxonomy_template($taxonomy) {
        return $this->template_loader->has_taxonomy_template($taxonomy);
    }
    
    /**
     * Проверява дали дадена таксономия има archive template
     */
    public function has_taxonomy_archive_template($taxonomy) {
        return $this->template_loader->has_taxonomy_archive_template($taxonomy);
    }
    
    /**
     * Получава URL за изображение на term
     */
    public function get_term_image_url($term_id, $taxonomy, $size = 'medium') {
        $image_meta_key = $taxonomy . '-image-id';
        $image_id = get_term_meta($term_id, $image_meta_key, true);
        
        if ($image_id) {
            return wp_get_attachment_image_url($image_id, $size);
        }
        
        return false;
    }
    
    /**
     * Получава нотки по група (за notes таксономия)
     */
    public function get_notes_by_group($group = '') {
        $args = array(
            'taxonomy' => 'notes',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        if (!empty($group)) {
            $args['meta_query'] = array(
                array(
                    'key' => 'notes_group',
                    'value' => $group,
                    'compare' => '='
                )
            );
        }
        
        $terms = get_terms($args);
        
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
    
    /**
     * Получава всички наличини templates за таксономии
     */
    public function get_taxonomies_with_templates() {
        return $this->template_loader->get_taxonomies_with_templates();
    }
    
    // Backward compatibility methods - запазваме съществуващите методи
    
    /**
     * @deprecated Използвайте $this->registrar->register_taxonomies()
     */
    public function register_taxonomies() {
        return $this->registrar->register_taxonomies();
    }
    
    /**
     * ПРЕМАХНАТ: template_loader методът вече се управлява автоматично от компонентите
     * Taxonomy template loading се извършва автоматично от Taxonomy_Template_Loader компонента
     * Post type template loading се извършва от Post_Type\Template_Loader компонента
     * 
     * @deprecated Автоматично управление от template loader компонентите
     */
    
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