<?php
/**
 * Taxonomy Manager
 * 
 * Main class for managing all taxonomies
 * 
 * @package ParfumeReviews
 * @subpackage Taxonomies
 * @since 2.0.0
 */

namespace ParfumeReviews\Taxonomies;

use ParfumeReviews\Core\Container;

/**
 * TaxonomyManager Class
 * 
 * Orchestrates all taxonomy-related functionality
 */
class TaxonomyManager {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Taxonomy configuration
     * 
     * @var array
     */
    private $config;
    
    /**
     * Registrar instance
     * 
     * @var Registrar
     */
    private $registrar;
    
    /**
     * Rewrite handler instance
     * 
     * @var RewriteHandler
     */
    private $rewrite_handler;
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->config = $this->get_config();
        
        // ВАЖНО: Валидация на конфигурацията
        if (!is_array($this->config)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ParfumeReviews\Taxonomies\TaxonomyManager: Config is not an array! Type: ' . gettype($this->config));
            }
            $this->config = [];
        }
        
        // Initialize components
        $this->registrar = new Registrar($this->config);
        $this->rewrite_handler = new RewriteHandler($this->config);
    }
    
    /**
     * Get taxonomy configuration
     * 
     * @return array
     */
    private function get_config() {
        try {
            $config = $this->container->get('config.taxonomies');
            
            // Валидация на резултата
            if (!is_array($config)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('ParfumeReviews\Taxonomies\TaxonomyManager: Container returned non-array config. Type: ' . gettype($config));
                }
                return [];
            }
            
            return $config;
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ParfumeReviews\Taxonomies\TaxonomyManager: Error getting config: ' . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Register all taxonomies
     */
    public function register() {
        $this->registrar->register_all();
    }
    
    /**
     * Handle rewrite rules
     * 
     * @param \WP $wp WordPress environment object
     */
    public function handle_rewrite($wp) {
        $this->rewrite_handler->parse_request($wp);
    }
    
    /**
     * Load taxonomy templates
     * 
     * @param string $template Current template path
     * @return string Modified template path
     */
    public function load_templates($template) {
        return $this->rewrite_handler->load_template($template);
    }
    
    /**
     * Get all registered taxonomies
     * 
     * @return array
     */
    public function get_taxonomies() {
        if (!is_array($this->config)) {
            return [];
        }
        return array_keys($this->config);
    }
    
    /**
     * Get taxonomy configuration by name
     * 
     * @param string $taxonomy Taxonomy name
     * @return array|null
     */
    public function get_taxonomy_config($taxonomy) {
        return isset($this->config[$taxonomy]) ? $this->config[$taxonomy] : null;
    }
    
    /**
     * Check if taxonomy is registered
     * 
     * @param string $taxonomy Taxonomy name
     * @return bool
     */
    public function is_registered($taxonomy) {
        return isset($this->config[$taxonomy]) && taxonomy_exists($taxonomy);
    }
    
    /**
     * Get taxonomy terms
     * 
     * @param string $taxonomy Taxonomy name
     * @param array $args get_terms arguments
     * @return array|\WP_Error
     */
    public function get_terms($taxonomy, $args = []) {
        if (!$this->is_registered($taxonomy)) {
            return new \WP_Error('invalid_taxonomy', __('Taxonomy does not exist', 'parfume-reviews'));
        }
        
        $default_args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        return get_terms($args);
    }
    
    /**
     * Get term by slug
     * 
     * @param string $taxonomy Taxonomy name
     * @param string $slug Term slug
     * @return \WP_Term|false
     */
    public function get_term_by_slug($taxonomy, $slug) {
        return get_term_by('slug', $slug, $taxonomy);
    }
    
    /**
     * Get term by ID
     * 
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return \WP_Term|false
     */
    public function get_term($term_id, $taxonomy) {
        return get_term($term_id, $taxonomy);
    }
    
    /**
     * Get post terms
     * 
     * @param int $post_id Post ID
     * @param string $taxonomy Taxonomy name
     * @return array|\WP_Error
     */
    public function get_post_terms($post_id, $taxonomy) {
        if (!$this->is_registered($taxonomy)) {
            return new \WP_Error('invalid_taxonomy', __('Taxonomy does not exist', 'parfume-reviews'));
        }
        
        return wp_get_post_terms($post_id, $taxonomy);
    }
    
    /**
     * Set post terms
     * 
     * @param int $post_id Post ID
     * @param string $taxonomy Taxonomy name
     * @param array|int|string $terms Terms to set
     * @param bool $append Whether to append or replace
     * @return array|\WP_Error
     */
    public function set_post_terms($post_id, $taxonomy, $terms, $append = false) {
        if (!$this->is_registered($taxonomy)) {
            return new \WP_Error('invalid_taxonomy', __('Taxonomy does not exist', 'parfume-reviews'));
        }
        
        return wp_set_post_terms($post_id, $terms, $taxonomy, $append);
    }
    
    /**
     * Get taxonomy archive URL
     * 
     * @param string $taxonomy Taxonomy name
     * @return string|false
     */
    public function get_archive_url($taxonomy) {
        if (!$this->is_registered($taxonomy)) {
            return false;
        }
        
        $settings = get_option('parfume_reviews_settings', []);
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $config = $this->get_taxonomy_config($taxonomy);
        $tax_slug = isset($config['rewrite']['slug']) ? basename($config['rewrite']['slug']) : $taxonomy;
        
        return home_url('/' . $parfume_slug . '/' . $tax_slug . '/');
    }
    
    /**
     * Get term link
     * 
     * @param int|\WP_Term $term Term ID or object
     * @param string $taxonomy Taxonomy name
     * @return string|\WP_Error
     */
    public function get_term_link($term, $taxonomy = '') {
        return get_term_link($term, $taxonomy);
    }
    
    /**
     * Get term count
     * 
     * @param string $taxonomy Taxonomy name
     * @return int
     */
    public function get_term_count($taxonomy) {
        if (!$this->is_registered($taxonomy)) {
            return 0;
        }
        
        $terms = $this->get_terms($taxonomy, ['fields' => 'count']);
        return is_wp_error($terms) ? 0 : $terms;
    }
    
    /**
     * Get posts by term
     * 
     * @param string $taxonomy Taxonomy name
     * @param int|string $term Term ID or slug
     * @param array $args WP_Query arguments
     * @return \WP_Query
     */
    public function get_posts_by_term($taxonomy, $term, $args = []) {
        $default_args = [
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'field' => is_numeric($term) ? 'term_id' : 'slug',
                    'terms' => $term
                ]
            ]
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        return new \WP_Query($args);
    }
    
    /**
     * Check if current page is taxonomy archive
     * 
     * @param string $taxonomy Optional taxonomy name
     * @return bool
     */
    public function is_taxonomy_archive($taxonomy = null) {
        if ($taxonomy) {
            return is_tax($taxonomy);
        }
        
        return is_tax($this->get_taxonomies());
    }
    
    /**
     * Get current taxonomy
     * 
     * @return string|false
     */
    public function get_current_taxonomy() {
        if (!$this->is_taxonomy_archive()) {
            return false;
        }
        
        $queried_object = get_queried_object();
        
        return isset($queried_object->taxonomy) ? $queried_object->taxonomy : false;
    }
    
    /**
     * Get current term
     * 
     * @return \WP_Term|false
     */
    public function get_current_term() {
        if (!$this->is_taxonomy_archive()) {
            return false;
        }
        
        return get_queried_object();
    }
    
    /**
     * Add default terms for a taxonomy
     * 
     * @param string $taxonomy Taxonomy name
     */
    public function add_default_terms($taxonomy) {
        $config = $this->get_taxonomy_config($taxonomy);
        
        if (!$config || !isset($config['default_terms'])) {
            return;
        }
        
        foreach ($config['default_terms'] as $term_name) {
            if (!term_exists($term_name, $taxonomy)) {
                $this->create_term($taxonomy, $term_name);
            }
        }
    }
    
    /**
     * Create term
     * 
     * @param string $taxonomy Taxonomy name
     * @param string $term_name Term name
     * @param array $args Optional arguments
     * @return array|\WP_Error
     */
    public function create_term($taxonomy, $term_name, $args = []) {
        if (!$this->is_registered($taxonomy)) {
            return new \WP_Error('invalid_taxonomy', __('Taxonomy does not exist', 'parfume-reviews'));
        }
        
        return wp_insert_term($term_name, $taxonomy, $args);
    }
    
    /**
     * Get taxonomy labels
     * 
     * @param string $taxonomy Taxonomy name
     * @return array|null
     */
    public function get_labels($taxonomy) {
        $config = $this->get_taxonomy_config($taxonomy);
        return $config && isset($config['labels']) ? $config['labels'] : null;
    }
    
    /**
     * Get taxonomy label
     * 
     * @param string $taxonomy Taxonomy name
     * @param string $label_key Label key (e.g., 'name', 'singular_name')
     * @return string|null
     */
    public function get_label($taxonomy, $label_key = 'name') {
        $labels = $this->get_labels($taxonomy);
        return $labels && isset($labels[$label_key]) ? $labels[$label_key] : null;
    }
}