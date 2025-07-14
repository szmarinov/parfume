<?php
namespace Parfume_Reviews;

/**
 * Main Taxonomies class - координира всички taxonomy компоненти
 * 
 * Файл: includes/class-taxonomies.php
 * РЕВИЗИРАНА ВЕРСИЯ - МОДУЛНА АРХИТЕКТУРА С BACKWARD COMPATIBILITY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ВАЖНО: Зареждаме всички taxonomy компоненти
 * Тази система използва модулна архитектура за по-добра организация
 */

// Load all taxonomy components
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-registrar.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-meta-fields.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-template-loader.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-rewrite-handler.php';
require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/taxonomies/class-taxonomy-seo-support.php';

/**
 * Main Taxonomies class
 * ВАЖНО: Координира всички taxonomy компоненти и запазва backward compatibility
 */
class Taxonomies {
    
    /**
     * @var Taxonomies\Taxonomy_Registrar
     * ВАЖНО: Отговаря за регистрация на всички таксономии
     */
    public $registrar;
    
    /**
     * @var Taxonomies\Taxonomy_Meta_Fields
     * ВАЖНО: Управлява meta полетата и изображенията
     */
    public $meta_fields;
    
    /**
     * @var Taxonomies\Taxonomy_Template_Loader
     * ВАЖНО: Зарежда правилните template файлове
     */
    public $template_loader;
    
    /**
     * @var Taxonomies\Taxonomy_Rewrite_Handler
     * ВАЖНО: Управлява URL rewrite правилата
     */
    public $rewrite_handler;
    
    /**
     * @var Taxonomies\Taxonomy_SEO_Support
     * ВАЖНО: Добавя SEO поддръжка (Yoast, RankMath)
     */
    public $seo_support;
    
    /**
     * Constructor
     * ВАЖНО: Инициализира всички компоненти
     */
    public function __construct() {
        $this->init_components();
        $this->register_hooks();
    }
    
    /**
     * Инициализира всички компоненти
     * ВАЖНО: Създава инстанции на всички taxonomy класове
     */
    private function init_components() {
        try {
            // Initialize all taxonomy components
            $this->registrar = new Taxonomies\Taxonomy_Registrar();
            $this->meta_fields = new Taxonomies\Taxonomy_Meta_Fields();
            $this->template_loader = new Taxonomies\Taxonomy_Template_Loader();
            $this->rewrite_handler = new Taxonomies\Taxonomy_Rewrite_Handler();
            $this->seo_support = new Taxonomies\Taxonomy_SEO_Support();
            
            // Debug log
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("All taxonomy components initialized successfully");
            }
            
        } catch (Exception $e) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Error initializing taxonomy components: " . $e->getMessage(), 'error');
            }
        }
    }
    
    /**
     * Регистрира основните hook-ове
     * ВАЖНО: Настройва основната интеграция с WordPress
     */
    private function register_hooks() {
        // Hook за flush на rewrite rules когато се активира плъгина
        add_action('wp_loaded', array($this, 'maybe_flush_rewrite_rules'));
        
        // Hook за debug информация
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_notices', array($this, 'debug_taxonomy_info'));
        }
    }
    
    /**
     * РАЗДЕЛ 1: ПУБЛИЧЕН API ЗА РАБОТА С ТАКСОНОМИИ
     */
    
    /**
     * Получава всички поддържани таксономии
     * ВАЖНО: Централно място за дефиниране на поддържаните таксономии
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
     * РАЗДЕЛ 2: TEMPLATE МЕТОДИ
     */
    
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
     * РАЗДЕЛ 3: РАБОТА С ИЗОБРАЖЕНИЯ НА TERMS
     */
    
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
     * РАЗДЕЛ 4: СПЕЦИАЛНИ МЕТОДИ ЗА NOTES ТАКСОНОМИЯ
     */
    
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
     * Получава всички групи на нотки
     */
    public function get_note_groups() {
        return array(
            'citrus' => __('Цитрусови', 'parfume-reviews'),
            'floral' => __('Цветни', 'parfume-reviews'),
            'woody' => __('Дървесни', 'parfume-reviews'),
            'oriental' => __('Ориенталски', 'parfume-reviews'),
            'fresh' => __('Свежи', 'parfume-reviews'),
            'gourmand' => __('Гурме', 'parfume-reviews'),
            'green' => __('Зелени', 'parfume-reviews'),
            'aquatic' => __('Водни', 'parfume-reviews')
        );
    }
    
    /**
     * РАЗДЕЛ 5: СТАТИСТИЧЕСКИ МЕТОДИ
     */
    
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
     * Получава най-популярните terms за дадена таксономия
     */
    public function get_popular_terms($taxonomy, $limit = 10) {
        if (!$this->is_supported_taxonomy($taxonomy)) {
            return array();
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => $limit,
            'hide_empty' => true
        ));
        
        return !is_wp_error($terms) ? $terms : array();
    }
    
    /**
     * РАЗДЕЛ 6: UTILITY МЕТОДИ
     */
    
    /**
     * Проверява и flush-ва rewrite rules ако е необходимо
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('parfume_reviews_flush_taxonomy_rules', false)) {
            flush_rewrite_rules();
            delete_option('parfume_reviews_flush_taxonomy_rules');
            
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Taxonomy rewrite rules flushed");
            }
        }
    }
    
    /**
     * Debug информация за таксономиите
     */
    public function debug_taxonomy_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['parfume_debug']) && $_GET['parfume_debug'] === 'taxonomies') {
            echo '<div class="notice notice-info"><p><strong>Parfume Reviews Taxonomies Debug:</strong></p>';
            
            $stats = $this->get_taxonomy_stats();
            echo '<ul>';
            foreach ($stats as $taxonomy => $stat) {
                echo '<li>' . esc_html($taxonomy) . ': ' . $stat['used_terms'] . '/' . $stat['total_terms'] . ' terms</li>';
            }
            echo '</ul></div>';
        }
    }
    
    /**
     * РАЗДЕЛ 7: BACKWARD COMPATIBILITY МЕТОДИ
     * ВАЖНО: Тези методи запазват съвместимостта със стария код
     */
    
    /**
     * @deprecated Използвайте $this->registrar->register_taxonomies()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function register_taxonomies() {
        return $this->registrar->register_taxonomies();
    }
    
    /**
     * @deprecated Използвайте $this->template_loader->template_loader()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function template_loader($template) {
        return $this->template_loader->template_loader($template);
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->add_taxonomy_meta_fields()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function add_taxonomy_meta_fields() {
        return $this->meta_fields->add_taxonomy_meta_fields();
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->save_taxonomy_meta_fields()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        return $this->meta_fields->save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy);
    }
    
    /**
     * @deprecated Използвайте $this->rewrite_handler->add_custom_rewrite_rules()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function add_custom_rewrite_rules() {
        return $this->rewrite_handler->add_custom_rewrite_rules();
    }
    
    /**
     * @deprecated Използвайте $this->rewrite_handler->add_query_vars()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function add_query_vars($vars) {
        return $this->rewrite_handler->add_query_vars($vars);
    }
    
    /**
     * @deprecated Използвайте $this->rewrite_handler->parse_custom_requests()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function parse_custom_requests($wp) {
        return $this->rewrite_handler->parse_custom_requests($wp);
    }
    
    /**
     * @deprecated Използвайте $this->seo_support->add_seo_support()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function add_seo_support() {
        return $this->seo_support->add_seo_support();
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->enqueue_admin_scripts()
     * ВАЖНО: Запазено за backward compatibility
     */
    public function enqueue_admin_scripts($hook) {
        return $this->meta_fields->enqueue_admin_scripts($hook);
    }
    
    /**
     * РАЗДЕЛ 8: ПОМОЩНИ МЕТОДИ ЗА ВАЛИДАЦИЯ
     */
    
    /**
     * Валидира term данни преди запазване
     */
    public function validate_term_data($term_data, $taxonomy) {
        $validated = array();
        
        // Валидация на името
        if (!empty($term_data['name'])) {
            $validated['name'] = sanitize_text_field($term_data['name']);
        }
        
        // Валидация на slug-а
        if (!empty($term_data['slug'])) {
            $validated['slug'] = sanitize_title($term_data['slug']);
        }
        
        // Валидация на описанието
        if (!empty($term_data['description'])) {
            $validated['description'] = wp_kses_post($term_data['description']);
        }
        
        // Специална валидация за notes таксономия
        if ($taxonomy === 'notes' && !empty($term_data['note_group'])) {
            $valid_groups = array_keys($this->get_note_groups());
            if (in_array($term_data['note_group'], $valid_groups)) {
                $validated['note_group'] = $term_data['note_group'];
            }
        }
        
        return $validated;
    }
    
    /**
     * Проверява дали компонентите са заредени правилно
     */
    public function validate_components() {
        $components = array(
            'registrar' => $this->registrar,
            'meta_fields' => $this->meta_fields,
            'template_loader' => $this->template_loader,
            'rewrite_handler' => $this->rewrite_handler,
            'seo_support' => $this->seo_support
        );
        
        $missing = array();
        foreach ($components as $name => $component) {
            if (!$component) {
                $missing[] = $name;
            }
        }
        
        if (!empty($missing) && function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Missing taxonomy components: " . implode(', ', $missing), 'error');
            return false;
        }
        
        return true;
    }
}

// End of file