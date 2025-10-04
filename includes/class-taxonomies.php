<?php
namespace Parfume_Reviews;

/**
 * Main Taxonomies class - координира всички taxonomy компоненти
 * 
 * Файл: includes/class-taxonomies.php
 * ПОПРАВЕН: URL handling, template loading, namespace проблеми
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
        // Зареждаме компонентите първо
        $this->load_components();
        
        // После ги инициализираме
        $this->init_components();
        
        // Добавяме debug за проследяване
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('init', array($this, 'debug_taxonomy_status'), 999);
        }
    }
    
    /**
     * Зарежда всички taxonomy компонент файлове
     */
    private function load_components() {
        $component_files = array(
            'includes/taxonomies/class-taxonomy-registrar.php',
            'includes/taxonomies/class-taxonomy-meta-fields.php', 
            'includes/taxonomies/class-taxonomy-template-loader.php',
            'includes/taxonomies/class-taxonomy-rewrite-handler.php',
            'includes/taxonomies/class-taxonomy-seo-support.php'
        );
        
        foreach ($component_files as $file) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Loaded taxonomy component: " . basename($file));
                }
            } else {
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Missing taxonomy component: " . $file);
                }
                
                // Показваме admin notice за липсващи файлове
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="notice notice-error"><p>';
                    echo '<strong>Parfume Reviews:</strong> Липсва taxonomy компонент: ' . esc_html(basename($file));
                    echo '</p></div>';
                });
            }
        }
    }
    
    /**
     * Инициализира всички компоненти БЕЗОПАСНО
     */
    private function init_components() {
        try {
            // Initialize all taxonomy components with error handling
            if (class_exists('Parfume_Reviews\\Taxonomies\\Taxonomy_Registrar')) {
                $this->registrar = new Taxonomies\Taxonomy_Registrar();
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Taxonomy_Registrar initialized");
                }
            } else {
                throw new \Exception("Taxonomy_Registrar class not found");
            }
            
            if (class_exists('Parfume_Reviews\\Taxonomies\\Taxonomy_Meta_Fields')) {
                $this->meta_fields = new Taxonomies\Taxonomy_Meta_Fields();
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Taxonomy_Meta_Fields initialized");
                }
            } else {
                throw new \Exception("Taxonomy_Meta_Fields class not found");
            }
            
            if (class_exists('Parfume_Reviews\\Taxonomies\\Taxonomy_Template_Loader')) {
                $this->template_loader = new Taxonomies\Taxonomy_Template_Loader();
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Taxonomy_Template_Loader initialized");
                }
            } else {
                throw new \Exception("Taxonomy_Template_Loader class not found");
            }
            
            if (class_exists('Parfume_Reviews\\Taxonomies\\Taxonomy_Rewrite_Handler')) {
                $this->rewrite_handler = new Taxonomies\Taxonomy_Rewrite_Handler();
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Taxonomy_Rewrite_Handler initialized");
                }
            } else {
                throw new \Exception("Taxonomy_Rewrite_Handler class not found");
            }
            
            if (class_exists('Parfume_Reviews\\Taxonomies\\Taxonomy_SEO_Support')) {
                $this->seo_support = new Taxonomies\Taxonomy_SEO_Support();
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Taxonomy_SEO_Support initialized");
                }
            } else {
                throw new \Exception("Taxonomy_SEO_Support class not found");
            }
            
        } catch (\Exception $e) {
            if (function_exists('parfume_reviews_debug_log')) {
                parfume_reviews_debug_log("Error initializing taxonomy components: " . $e->getMessage());
            }
            
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Parfume Reviews Taxonomies Error:</strong> ' . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Debug функция за проследяване на състоянието
     */
    public function debug_taxonomy_status() {
        if (!function_exists('parfume_reviews_debug_log')) {
            return;
        }
        
        // Проверяваме дали всички компоненти са заредени
        $components = array(
            'registrar' => $this->registrar,
            'meta_fields' => $this->meta_fields,
            'template_loader' => $this->template_loader,
            'rewrite_handler' => $this->rewrite_handler,
            'seo_support' => $this->seo_support
        );
        
        foreach ($components as $name => $component) {
            if ($component) {
                parfume_reviews_debug_log("Component '{$name}' is loaded and ready");
            } else {
                parfume_reviews_debug_log("Component '{$name}' failed to load");
            }
        }
        
        // Проверяваме дали таксономиите са регистрирани
        $taxonomies = $this->get_supported_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                parfume_reviews_debug_log("Taxonomy '{$taxonomy}' is registered");
            } else {
                parfume_reviews_debug_log("Taxonomy '{$taxonomy}' is NOT registered");
            }
        }
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
        if (!$term_id || !$taxonomy) {
            return false;
        }
        
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
        if (!$term_id) {
            return false;
        }
        
        return get_term_meta($term_id, 'note_group', true);
    }
    
    /**
     * Получава всички нотки от дадена група
     */
    public function get_notes_by_group($group) {
        if (!$group) {
            return array();
        }
        
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
    
    // =============================================
    // BACKWARD COMPATIBILITY METHODS
    // Запазваме съществуващите методи за съвместимост
    // =============================================
    
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
            return $template; // Връщаме оригиналния template
        }
        
        return $this->template_loader->template_loader($template);
    }
    
    /**
     * @deprecated Използвайте $this->meta_fields->add_taxonomy_meta_fields()
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
            if (function_exists('parfume_reviews_debug_log')) {
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
}