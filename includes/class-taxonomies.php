<?php
namespace Parfume_Reviews;

/**
 * Taxonomies Handler - Координира всички taxonomy компоненти
 * 📁 Файл: includes/class-taxonomies.php
 * ПОПРАВЕНО: Правилни default slug-ове
 */
class Taxonomies {
    
    public $registrar;
    public $meta_fields;
    public $template_loader;
    public $rewrite_handler;
    public $seo_support;
    
    public function __construct() {
        // Зареждаме компонентите
        $this->load_components();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Taxonomies initialized');
        }
    }
    
    /**
     * Зарежда всички taxonomy компоненти
     */
    private function load_components() {
        // Зареждаме taxonomy файловете
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-registrar.php';
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-meta-fields.php';
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-template-loader.php';
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-rewrite-handler.php';
        require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-seo-support.php';
        
        // Инициализираме компонентите
        $this->registrar = new Taxonomies\Taxonomy_Registrar();
        $this->meta_fields = new Taxonomies\Taxonomy_Meta_Fields();
        $this->template_loader = new Taxonomies\Taxonomy_Template_Loader();
        $this->rewrite_handler = new Taxonomies\Taxonomy_Rewrite_Handler();
        $this->seo_support = new Taxonomies\Taxonomy_SEO_Support();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: All taxonomy components loaded');
        }
    }
    
    /**
     * Backward compatibility методи
     */
    
    /**
     * Регистрира таксономии (backward compatibility)
     */
    public function register_taxonomies() {
        return $this->registrar->register_taxonomies();
    }
    
    /**
     * Template loader (backward compatibility)
     */
    public function template_loader($template) {
        return $this->template_loader->load_template($template);
    }
    
    /**
     * Добавя taxonomy meta fields (backward compatibility)
     */
    public function add_taxonomy_meta_fields($taxonomy) {
        return $this->meta_fields->add_taxonomy_meta_fields($taxonomy);
    }
    
    /**
     * Запазва taxonomy meta fields (backward compatibility)
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        return $this->meta_fields->save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy);
    }
    
    /**
     * Добавя custom rewrite rules (backward compatibility)
     */
    public function add_custom_rewrite_rules() {
        return $this->rewrite_handler->add_custom_rewrite_rules();
    }
    
    /**
     * Добавя query vars (backward compatibility)
     */
    public function add_query_vars($vars) {
        return $this->rewrite_handler->add_query_vars($vars);
    }
    
    /**
     * Parse custom requests (backward compatibility)
     */
    public function parse_custom_requests($wp) {
        return $this->rewrite_handler->parse_custom_requests($wp);
    }
    
    /**
     * Добавя SEO поддръжка (backward compatibility)
     */
    public function add_seo_support() {
        return $this->seo_support->add_yoast_support();
    }
    
    /**
     * Enqueue admin scripts (backward compatibility)
     */
    public function enqueue_admin_scripts($hook) {
        return $this->meta_fields->enqueue_admin_scripts($hook);
    }
    
    /**
     * Нови API методи за улеснена работа
     */
    
    /**
     * Получава поддържани таксономии
     */
    public function get_supported_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    /**
     * Получава URL за архив на таксономия
     */
    public function get_taxonomy_archive_url($taxonomy) {
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
     * Проверява дали дадена таксономия е поддържана
     */
    public function is_supported_taxonomy($taxonomy) {
        return in_array($taxonomy, $this->get_supported_taxonomies());
    }
    
    /**
     * Получава правилните slug-ове за всички таксономии
     */
    public function get_all_taxonomy_slugs() {
        $settings = get_option('parfume_reviews_settings', array());
        
        // ПОПРАВЕНО: Правилни default slug-ове
        return array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
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
                })) : 0,
                'slug' => $this->get_taxonomy_slug($taxonomy),
                'archive_url' => $this->get_taxonomy_archive_url($taxonomy)
            );
        }
        
        return $stats;
    }
    
    /**
     * Debug: Получава информация за rewrite rules
     */
    public function get_debug_info() {
        return array(
            'supported_taxonomies' => $this->get_supported_taxonomies(),
            'taxonomy_slugs' => $this->get_all_taxonomy_slugs(),
            'rewrite_rules' => $this->rewrite_handler->get_debug_rewrite_rules(),
            'settings' => get_option('parfume_reviews_settings', array())
        );
    }
    
    /**
     * Force flush rewrite rules
     */
    public function flush_rewrite_rules() {
        $this->rewrite_handler->flush_rewrite_rules_on_activation();
    }
}