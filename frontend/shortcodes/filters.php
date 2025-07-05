<?php
/**
 * Filters Shortcode - Адаптиран към новата архитектура
 *
 * @package Parfume_Reviews
 * @subpackage Frontend\Shortcodes
 * @since 1.0.0
 */

namespace Parfume_Reviews\Frontend\Shortcodes;

use Parfume_Reviews\Utils\Shortcode_Base;
use Parfume_Reviews\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filters Shortcode Class
 */
class Filters extends Shortcode_Base {
    
    /**
     * Shortcode tag
     *
     * @var string
     */
    protected $tag = 'parfume_filters';
    
    /**
     * Default attributes
     *
     * @var array
     */
    protected $default_atts = array(
        'show_gender' => 'true',
        'show_aroma_type' => 'true',
        'show_brands' => 'true',
        'show_notes' => 'true',
        'show_perfumers' => 'true',
        'show_season' => 'true',
        'show_intensity' => 'true',
        'show_search' => 'true',
        'show_price_range' => 'false',
        'show_rating_filter' => 'false',
        'layout' => 'vertical',
        'show_reset' => 'true',
        'ajax_filtering' => 'true',
        'collapsible' => 'true',
    );
    
    /**
     * Initialize the shortcode
     */
    public function init() {
        // Enqueue filter assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add AJAX handlers
        add_action('wp_ajax_parfume_filter', array($this, 'ajax_filter'));
        add_action('wp_ajax_nopriv_parfume_filter', array($this, 'ajax_filter'));
        
        // Add filter form processing
        add_action('init', array($this, 'process_filter_form'));
    }
    
    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered HTML
     */
    public function render($atts, $content = '') {
        // Get current filter values from URL
        $current_filters = $this->get_current_filters();
        
        // Render filter form
        return $this->render_filter_form($atts, $current_filters);
    }
    
    /**
     * Get current filter values from URL parameters
     *
     * @return array Current filter values
     */
    private function get_current_filters() {
        $filters = array();
        $taxonomies = array('gender', 'aroma_type', 'marki', 'notes', 'perfumer', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            if (isset($_GET[$taxonomy])) {
                $filters[$taxonomy] = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $filters[$taxonomy] = array_map('sanitize_text_field', $filters[$taxonomy]);
            }
        }
        
        // Get other filter values
        $filters['search'] = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $filters['min_price'] = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
        $filters['max_price'] = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
        $filters['min_rating'] = isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0;
        
        return $filters;
    }
    
    /**
     * Render filter form HTML
     *
     * @param array $atts Shortcode attributes
     * @param array $current_filters Current filter values
     * @return string HTML output
     */
    private function render_filter_form($atts, $current_filters) {
        $form_classes = array(
            'parfume-filters-form',
            'layout-' . esc_attr($atts['layout']),
        );
        
        if ($this->string_to_bool($atts['collapsible'])) {
            $form_classes[] = 'collapsible';
        }
        
        if ($this->string_to_bool($atts['ajax_filtering'])) {
            $form_classes[] = 'ajax-enabled';
        }
        
        ob_start();
        ?>
        <div class="parfume-filters-wrapper">
            <form method="get" class="<?php echo implode(' ', $form_classes); ?>" id="parfume-filters-form">
                
                <?php if ($this->string_to_bool($atts['show_search'])): ?>
                    <?php echo $this->render_search_field($current_filters); ?>
                <?php endif; ?>
                
                <div class="filter-groups">
                    <?php
                    // Render taxonomy filters
                    $taxonomies = array(
                        'gender' => __('Categories', 'parfume-reviews'),
                        'aroma_type' => __('Aroma Types', 'parfume-reviews'),
                        'marki' => __('Brands', 'parfume-reviews'),
                        'season' => __('Seasons', 'parfume-reviews'),
                        'intensity' => __('Intensity', 'parfume-reviews'),
                        'notes' => __('Fragrance Notes', 'parfume-reviews'),
                        'perfumer' => __('Perfumers', 'parfume-reviews'),
                    );
                    
                    foreach ($taxonomies as $taxonomy => $label) {
                        $show_key = 'show_' . ($taxonomy === 'marki' ? 'brands' : $taxonomy);
                        if ($this->string_to_bool($atts[$show_key])) {
                            echo $this->render_taxonomy_filter($taxonomy, $label, $current_filters, $atts);
                        }
                    }
                    ?>
                    
                    <?php if ($this->string_to_bool($atts['show_price_range'])): ?>
                        <?php echo $this->render_price_range_filter($current_filters); ?>
                    <?php endif; ?>
                    
                    <?php if ($this->string_to_bool($atts['show_rating_filter'])): ?>
                        <?php echo $this->render_rating_filter($current_filters); ?>
                    <?php endif; ?>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary filter-submit">
                        <?php _e('Apply Filters', 'parfume-reviews'); ?>
                    </button>
                    
                    <?php if ($this->string_to_bool($atts['show_reset'])): ?>
                        <a href="<?php echo esc_url($this->get_reset_url()); ?>" class="btn btn-secondary filter-reset">
                            <?php _e('Reset Filters', 'parfume-reviews'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php wp_nonce_field('parfume_filter_nonce', 'filter_nonce'); ?>
            </form>
            
            <?php if ($this->string_to_bool($atts['ajax_filtering'])): ?>
                <div id="filter-results" class="filter-results">
                    <!-- AJAX results will be loaded here -->
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render search field
     *
     * @param array $current_filters Current filter values
     * @return string HTML output
     */
    private function render_search_field($current_filters) {
        ob_start();
        ?>
        <div class="filter-group search-group">
            <label for="parfume-search" class="filter-label">
                <?php _e('Search Perfumes', 'parfume-reviews'); ?>
            </label>
            <div class="search-input-wrapper">
                <input 
                    type="text" 
                    id="parfume-search" 
                    name="s" 
                    value="<?php echo esc_attr($current_filters['search']); ?>"
                    placeholder="<?php esc_attr_e('Enter perfume name, brand, or notes...', 'parfume-reviews'); ?>"
                    class="search-input"
                >
                <button type="button" class="search-clear" title="<?php esc_attr_e('Clear search', 'parfume-reviews'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render taxonomy filter
     *
     * @param string $taxonomy Taxonomy name
     * @param string $label Filter label
     * @param array $current_filters Current filter values
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    private function render_taxonomy_filter($taxonomy, $label, $current_filters, $atts) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }
        
        $selected_terms = isset($current_filters[$taxonomy]) ? $current_filters[$taxonomy] : array();
        $is_collapsible = $this->string_to_bool($atts['collapsible']);
        $is_collapsed = $is_collapsible && !in_array($taxonomy, array('gender', 'aroma_type', 'marki'));
        
        ob_start();
        ?>
        <div class="filter-group taxonomy-filter" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
            <h4 class="filter-title <?php echo $is_collapsible ? 'collapsible' : ''; ?> <?php echo $is_collapsed ? 'collapsed' : ''; ?>"
                <?php if ($is_collapsible): ?>data-toggle="collapse"<?php endif; ?>>
                <?php if ($is_collapsible): ?>
                    <span class="toggle-icon"><?php echo $is_collapsed ? '▶' : '▼'; ?></span>
                <?php endif; ?>
                <?php echo esc_html($label); ?>
                <span class="filter-count">(<?php echo count($terms); ?>)</span>
            </h4>
            
            <div class="filter-options <?php echo $is_collapsed ? 'collapsed' : ''; ?>">
                <?php if (count($terms) > 10): ?>
                    <div class="filter-search-wrapper">
                        <input type="text" class="filter-search" placeholder="<?php esc_attr_e('Search...', 'parfume-reviews'); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="options-wrapper <?php echo count($terms) > 10 ? 'scrollable' : ''; ?>">
                    <?php foreach ($terms as $term): ?>
                        <label class="filter-option" data-term="<?php echo esc_attr($term->slug); ?>">
                            <input 
                                type="checkbox" 
                                name="<?php echo esc_attr($taxonomy); ?>[]" 
                                value="<?php echo esc_attr($term->slug); ?>"
                                <?php checked(in_array($term->slug, $selected_terms)); ?>
                                class="filter-checkbox"
                            >
                            <span class="option-label"><?php echo esc_html($term->name); ?></span>
                            <span class="option-count">(<?php echo $term->count; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render price range filter
     *
     * @param array $current_filters Current filter values
     * @return string HTML output
     */
    private function render_price_range_filter($current_filters) {
        // Get price range from database
        $price_range = $this->get_price_range();
        
        ob_start();
        ?>
        <div class="filter-group price-range-filter">
            <h4 class="filter-title"><?php _e('Price Range', 'parfume-reviews'); ?></h4>
            <div class="filter-options">
                <div class="price-range-inputs">
                    <label class="price-input-wrapper">
                        <span class="price-label"><?php _e('Min', 'parfume-reviews'); ?></span>
                        <input 
                            type="number" 
                            name="min_price" 
                            value="<?php echo esc_attr($current_filters['min_price']); ?>"
                            min="<?php echo esc_attr($price_range['min']); ?>"
                            max="<?php echo esc_attr($price_range['max']); ?>"
                            step="0.01"
                            class="price-input"
                        >
                    </label>
                    
                    <span class="price-separator">—</span>
                    
                    <label class="price-input-wrapper">
                        <span class="price-label"><?php _e('Max', 'parfume-reviews'); ?></span>
                        <input 
                            type="number" 
                            name="max_price" 
                            value="<?php echo esc_attr($current_filters['max_price']); ?>"
                            min="<?php echo esc_attr($price_range['min']); ?>"
                            max="<?php echo esc_attr($price_range['max']); ?>"
                            step="0.01"
                            class="price-input"
                        >
                    </label>
                </div>
                
                <div class="price-range-slider" 
                     data-min="<?php echo esc_attr($price_range['min']); ?>"
                     data-max="<?php echo esc_attr($price_range['max']); ?>"
                     data-current-min="<?php echo esc_attr($current_filters['min_price'] ?: $price_range['min']); ?>"
                     data-current-max="<?php echo esc_attr($current_filters['max_price'] ?: $price_range['max']); ?>">
                    <!-- Slider will be initialized by JavaScript -->
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render rating filter
     *
     * @param array $current_filters Current filter values
     * @return string HTML output
     */
    private function render_rating_filter($current_filters) {
        ob_start();
        ?>
        <div class="filter-group rating-filter">
            <h4 class="filter-title"><?php _e('Minimum Rating', 'parfume-reviews'); ?></h4>
            <div class="filter-options">
                <div class="rating-options">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <label class="rating-option">
                            <input 
                                type="radio" 
                                name="min_rating" 
                                value="<?php echo $i; ?>"
                                <?php checked($current_filters['min_rating'], $i); ?>
                                class="rating-radio"
                            >
                            <span class="rating-stars">
                                <?php for ($j = 1; $j <= 5; $j++): ?>
                                    <span class="star <?php echo $j <= $i ? 'filled' : 'empty'; ?>">★</span>
                                <?php endfor; ?>
                            </span>
                            <span class="rating-text"><?php printf(__('%d+ stars', 'parfume-reviews'), $i); ?></span>
                        </label>
                    <?php endfor; ?>
                    
                    <label class="rating-option">
                        <input 
                            type="radio" 
                            name="min_rating" 
                            value="0"
                            <?php checked($current_filters['min_rating'], 0); ?>
                            class="rating-radio"
                        >
                        <span class="rating-text"><?php _e('Any rating', 'parfume-reviews'); ?></span>
                    </label>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get price range from stored prices
     *
     * @return array Min and max prices
     */
    private function get_price_range() {
        global $wpdb;
        
        // Cache key
        $cache_key = 'parfume_price_range';
        $cached = wp_cache_get($cache_key, 'parfume_reviews');
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Get price range from database
        $query = "
            SELECT 
                MIN(CAST(pm.meta_value AS DECIMAL(10,2))) as min_price,
                MAX(CAST(pm.meta_value AS DECIMAL(10,2))) as max_price
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_parfume_min_price'
            AND p.post_type = 'parfume'
            AND p.post_status = 'publish'
            AND pm.meta_value > 0
        ";
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        $price_range = array(
            'min' => !empty($result['min_price']) ? floatval($result['min_price']) : 0,
            'max' => !empty($result['max_price']) ? floatval($result['max_price']) : 1000,
        );
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $price_range, 'parfume_reviews', HOUR_IN_SECONDS);
        
        return $price_range;
    }
    
    /**
     * Get reset URL (current page without filter parameters)
     *
     * @return string Reset URL
     */
    private function get_reset_url() {
        $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));
        return remove_query_arg(array(
            'gender', 'aroma_type', 'marki', 'notes', 'perfumer', 
            'season', 'intensity', 's', 'min_price', 'max_price', 'min_rating'
        ), $current_url);
    }
    
    /**
     * Process filter form submission
     */
    public function process_filter_form() {
        if (!isset($_GET['filter_nonce']) || !wp_verify_nonce($_GET['filter_nonce'], 'parfume_filter_nonce')) {
            return;
        }
        
        // Process filters and redirect if needed
        // This method handles non-AJAX form submissions
    }
    
    /**
     * AJAX filter handler
     */
    public function ajax_filter() {
        check_ajax_referer('parfume_filter_nonce', 'nonce');
        
        // Get filter parameters
        $filters = array();
        $taxonomies = array('gender', 'aroma_type', 'marki', 'notes', 'perfumer', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            if (!empty($_POST[$taxonomy])) {
                $filters[$taxonomy] = array_map('sanitize_text_field', (array) $_POST[$taxonomy]);
            }
        }
        
        // Get other filters
        $search = sanitize_text_field($_POST['s'] ?? '');
        $min_price = floatval($_POST['min_price'] ?? 0);
        $max_price = floatval($_POST['max_price'] ?? 0);
        $min_rating = floatval($_POST['min_rating'] ?? 0);
        
        // Build query
        $query_args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => intval($_POST['paged'] ?? 1),
        );
        
        // Add taxonomy filters
        if (!empty($filters)) {
            $tax_query = array('relation' => 'AND');
            
            foreach ($filters as $taxonomy => $terms) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN',
                );
            }
            
            $query_args['tax_query'] = $tax_query;
        }
        
        // Add search
        if (!empty($search)) {
            $query_args['s'] = $search;
        }
        
        // Add meta queries for price and rating
        $meta_query = array('relation' => 'AND');
        
        if ($min_price > 0) {
            $meta_query[] = array(
                'key' => '_parfume_min_price',
                'value' => $min_price,
                'compare' => '>=',
                'type' => 'DECIMAL',
            );
        }
        
        if ($max_price > 0) {
            $meta_query[] = array(
                'key' => '_parfume_min_price',
                'value' => $max_price,
                'compare' => '<=',
                'type' => 'DECIMAL',
            );
        }
        
        if ($min_rating > 0) {
            $meta_query[] = array(
                'key' => '_parfume_rating',
                'value' => $min_rating,
                'compare' => '>=',
                'type' => 'DECIMAL',
            );
        }
        
        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }
        
        // Execute query
        $query = new \WP_Query($query_args);
        
        // Render results
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="parfume-grid">';
            
            while ($query->have_posts()) {
                $query->the_post();
                // Use template function or include card template
                Helpers::render_parfume_card(get_the_ID());
            }
            
            echo '</div>';
            
            // Pagination
            if ($query->max_num_pages > 1) {
                echo '<div class="filter-pagination">';
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'current' => $query_args['paged'],
                    'format' => '?paged=%#%',
                    'show_all' => false,
                    'prev_text' => __('‹ Previous', 'parfume-reviews'),
                    'next_text' => __('Next ›', 'parfume-reviews'),
                ));
                echo '</div>';
            }
        } else {
            echo '<div class="no-results">';
            echo '<p>' . __('No perfumes found matching your criteria.', 'parfume-reviews') . '</p>';
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        $response = array(
            'success' => true,
            'html' => ob_get_clean(),
            'found_posts' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
        );
        
        wp_send_json($response);
    }
    
    /**
     * Enqueue filter assets
     */
    public function enqueue_assets() {
        if (!is_admin() && ($this->is_parfume_archive() || $this->has_shortcode_on_page())) {
            wp_enqueue_style(
                'parfume-filters',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/filters.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-filters',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/filters.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-filters', 'parfumeFilters', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_filter_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'parfume-reviews'),
                    'error' => __('Error loading results', 'parfume-reviews'),
                    'noResults' => __('No results found', 'parfume-reviews'),
                ),
            ));
        }
    }
    
    /**
     * Check if current page is parfume archive
     *
     * @return bool
     */
    private function is_parfume_archive() {
        return is_post_type_archive('parfume') || 
               is_tax(array('gender', 'aroma_type', 'marki', 'notes', 'perfumer', 'season', 'intensity'));
    }
    
    /**
     * Check if page has this shortcode
     *
     * @return bool
     */
    private function has_shortcode_on_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        return has_shortcode($post->post_content, $this->tag);
    }
}

// Initialize the shortcode
new Filters();