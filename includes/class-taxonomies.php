<?php
namespace Parfume_Reviews;

/**
 * Taxonomies Main Class - координира всички taxonomy компоненти
 * ПОПРАВЕНА ВЕРСИЯ - осигурява правилно зареждане и работа
 * 
 * Файл: includes/class-taxonomies.php
 */
class Taxonomies {
    
    /**
     * Компонентите на taxonomy системата
     */
    public $registrar;
    public $meta_fields;
    public $template_loader;
    public $rewrite_handler;
    public $seo_support;
    
    /**
     * Flag за проверка дали компонентите са заредени
     */
    private $components_loaded = false;
    
    public function __construct() {
        $this->load_components();
        $this->init_hooks();
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Taxonomies initialized");
        }
    }
    
    /**
     * Зарежда всички taxonomy компоненти
     * ПОПРАВЕНА ВЕРСИЯ - със задължителни проверки
     */
    private function load_components() {
        $component_files = array(
            'registrar' => 'includes/taxonomies/class-taxonomy-registrar.php',
            'meta_fields' => 'includes/taxonomies/class-taxonomy-meta-fields.php',
            'template_loader' => 'includes/taxonomies/class-taxonomy-template-loader.php',
            'rewrite_handler' => 'includes/taxonomies/class-taxonomy-rewrite-handler.php',
            'seo_support' => 'includes/taxonomies/class-taxonomy-seo-support.php'
        );
        
        foreach ($component_files as $component => $file) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Loaded taxonomy component: " . basename($file));
                }
            } else {
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Missing taxonomy component: {$file}", 'error');
                }
                return false;
            }
        }
        
        // Инициализираме компонентите
        $this->registrar = new Taxonomies\Taxonomy_Registrar();
        $this->meta_fields = new Taxonomies\Taxonomy_Meta_Fields();
        $this->template_loader = new Taxonomies\Taxonomy_Template_Loader();
        $this->rewrite_handler = new Taxonomies\Taxonomy_Rewrite_Handler();
        $this->seo_support = new Taxonomies\Taxonomy_SEO_Support();
        
        $this->components_loaded = true;
        
        return true;
    }
    
    /**
     * Инициализира hook-овете
     */
    private function init_hooks() {
        // Hook за flush на rewrite rules при нужда
        add_action('admin_init', array($this, 'maybe_flush_rewrite_rules'));
        
        // Hook за debug информация
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_taxonomy_status'));
        }
    }
    
    /**
     * Проверява дали трябва да flush-не rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('parfume_reviews_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_flush_rewrite_rules');
            
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Rewrite rules flushed");
            }
        }
    }
    
    /**
     * Debug статус на taxonomy системата
     */
    public function debug_taxonomy_status() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wp_query;
        
        if (isset($wp_query->query_vars['parfume_taxonomy_archive']) || 
            isset($wp_query->query_vars['perfumer_archive'])) {
            
            echo '<!-- Parfume Reviews Taxonomy Debug -->';
            echo '<!-- Components loaded: ' . ($this->components_loaded ? 'YES' : 'NO') . ' -->';
            echo '<!-- Current page type: ' . $this->get_current_page_type() . ' -->';
            
            if ($this->template_loader) {
                echo '<!-- Template loader status: ACTIVE -->';
            } else {
                echo '<!-- Template loader status: MISSING -->';
            }
        }
    }
    
    /**
     * Получава типа на текущата страница
     */
    public function get_current_page_type() {
        global $wp_query;
        
        if (isset($wp_query->query_vars['perfumer_archive'])) {
            return 'perfumer_archive';
        }
        
        if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
            return 'taxonomy_archive: ' . $wp_query->query_vars['parfume_taxonomy_archive'];
        }
        
        if (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
            $queried_object = get_queried_object();
            return 'single_taxonomy: ' . $queried_object->taxonomy;
        }
        
        return 'other';
    }
    
    /**
     * ===== BACKWARD COMPATIBILITY МЕТОДИ =====
     * Тези методи запазват съвместимостта със стария код
     */
    
    /**
     * @deprecated Използвайте $this->registrar->register_taxonomies()
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
     * @deprecated Използвайте $this->template_loader->template_loader()
     */
    public function template_loader($template) {
        if (!$this->template_loader) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot load template - template_loader not loaded");
            }
            return $template;
        }
        
        return $this->template_loader->template_loader($template);
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->add_taxonomy_meta_fields()
     */
    public function add_taxonomy_meta_fields($taxonomy) {
        if (!$this->meta_fields) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot add meta fields - meta_fields not loaded");
            }
            return false;
        }
        
        return $this->meta_fields->add_taxonomy_meta_fields($taxonomy);
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->save_taxonomy_meta_fields()
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
     * @deprecated Използвайте $this->rewrite_handler->add_custom_rewrite_rules()
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
     * @deprecated Използвайте $this->rewrite_handler->add_query_vars()
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
     * @deprecated Използвайте $this->rewrite_handler->parse_custom_requests()
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
     * @deprecated Използвайте $this->seo_support->add_seo_support()
     */
    public function add_seo_support() {
        if (!$this->seo_support) {
            if (function_calls('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot add SEO support - seo_support not loaded");
            }
            return false;
        }
        
        return $this->seo_support->add_seo_support();
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->enqueue_admin_scripts()
     */
    public function enqueue_admin_scripts($hook) {
        if (!$this->meta_fields) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Cannot enqueue scripts - meta_fields not loaded");
            }
            return false;
        }
        
        return $this->meta_fields->enqueue_admin_scripts($hook);
    }
    
    /**
     * ===== НОВИ API МЕТОДИ =====
     * Тези методи предоставят по-удобен достъп до функционалностите
     */
    
    /**
     * Получава всички поддържани таксономии
     */
    public function get_supported_taxonomies() {
        return array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
    }
    
    /**
     * Проверява дали дадена таксономия е поддържана
     */
    public function is_supported_taxonomy($taxonomy) {
        return in_array($taxonomy, $this->get_supported_taxonomies());
    }
    
    /**
     * ПОПРАВЕНА - Получава URL за архив на таксономия
     */
    public function get_taxonomy_archive_url($taxonomy) {
        if (!$this->is_supported_taxonomy($taxonomy)) {
            return false;
        }
        
        // Проверяваме дали rewrite_handler е зареден
        if (!$this->rewrite_handler) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Rewrite handler not loaded for taxonomy: {$taxonomy}");
            }
            return false;
        }
        
        return $this->rewrite_handler->get_taxonomy_archive_url($taxonomy);
    }
    
    /**
     * ПОПРАВЕНА - Получава slug за дадена таксономия
     */
    public function get_taxonomy_slug($taxonomy) {
        if (!$this->is_supported_taxonomy($taxonomy)) {
            return false;
        }
        
        // Проверяваме дали rewrite_handler е зареден
        if (!$this->rewrite_handler) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Rewrite handler not loaded for slug: {$taxonomy}");
            }
            return $taxonomy; // Fallback към името на таксономията
        }
        
        return $this->rewrite_handler->get_taxonomy_slug($taxonomy);
    }
    
    /**
     * ПОПРАВЕНА - Проверява дали съществува template за дадена таксономия
     */
    public function has_taxonomy_template($taxonomy) {
        if (!$this->template_loader) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Template loader not loaded for taxonomy: {$taxonomy}");
            }
            return false;
        }
        
        return $this->template_loader->has_taxonomy_template($taxonomy);
    }
    
    /**
     * ПОПРАВЕНА - Проверява дали съществува archive template за дадена таксономия
     */
    public function has_taxonomy_archive_template($taxonomy) {
        if (!$this->template_loader) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Template loader not loaded for archive: {$taxonomy}");
            }
            return false;
        }
        
        return $this->template_loader->has_taxonomy_archive_template($taxonomy);
    }
    
    /**
     * ПОПРАВЕНА - Проверява дали текущата страница е архив на таксономия
     */
    public function is_taxonomy_archive($taxonomy = null) {
        if (!$this->rewrite_handler) {
            return false;
        }
        
        return $this->rewrite_handler->is_taxonomy_archive($taxonomy);
    }
    
    /**
     * Получава изображение за таксономия term
     */
    public function get_term_image($term_id, $taxonomy, $size = 'thumbnail') {
        if (!$term_id || !$taxonomy) {
            return false;
        }
        
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
        if (!$term_id || !$taxonomy) {
            return false;
        }
        
        return get_term_meta($term_id, $taxonomy . '-image-id', true);
    }
    
    /**
     * Получава URL на изображение за таксономия term
     */
    public function get_term_image_url($term_id, $taxonomy, $size = 'thumbnail') {
        $image_id = $this->get_term_image_id($term_id, $taxonomy);
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, $size);
            return $image_url ? $image_url : false;
        }
        return false;
    }
    
    /**
     * Получава ноти по група (за notes таксономията)
     */
    public function get_notes_by_group($group = null) {
        $notes_terms = get_terms(array(
            'taxonomy' => 'notes',
            'hide_empty' => false
        ));
        
        if (is_wp_error($notes_terms)) {
            return array();
        }
        
        if (!$group) {
            return $notes_terms;
        }
        
        $filtered_notes = array();
        foreach ($notes_terms as $term) {
            $term_group = get_term_meta($term->term_id, 'notes_group', true);
            if ($term_group === $group) {
                $filtered_notes[] = $term;
            }
        }
        
        return $filtered_notes;
    }
    
    /**
     * Получава статистики за таксономиите
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
                'total_terms' => is_wp_error($terms) ? 0 : count($terms),
                'has_template' => $this->has_taxonomy_template($taxonomy),
                'has_archive_template' => $this->has_taxonomy_archive_template($taxonomy),
                'archive_url' => $this->get_taxonomy_archive_url($taxonomy)
            );
        }
        
        return $stats;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Проверява дали всички компоненти са заредени правилно
     */
    public function verify_components() {
        $status = array(
            'registrar' => $this->registrar !== null,
            'meta_fields' => $this->meta_fields !== null,
            'template_loader' => $this->template_loader !== null,
            'rewrite_handler' => $this->rewrite_handler !== null,
            'seo_support' => $this->seo_support !== null,
            'all_loaded' => $this->components_loaded
        );
        
        return $status;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Задейства flush на rewrite rules
     */
    public function trigger_rewrite_flush() {
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Rewrite rules flush triggered");
        }
    }
}