<?php
/**
 * Comparison Class for Parfume Reviews Plugin
 * 
 * Handles perfume comparison functionality
 * 
 * @package Parfume_Reviews
 * @since 1.0.0
 */

namespace Parfume_Reviews;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Comparison {
    
    /**
     * Maximum number of perfumes that can be compared
     */
    const MAX_COMPARISON_ITEMS = 4;
    
    /**
     * Session key for comparison list
     */
    const SESSION_KEY = 'parfume_comparison_list';
    
    public function __construct() {
        add_action('init', array($this, 'init_session'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_nopriv_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_nopriv_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_get_comparison_count', array($this, 'ajax_get_comparison_count'));
        add_action('wp_ajax_nopriv_get_comparison_count', array($this, 'ajax_get_comparison_count'));
        add_action('wp_ajax_clear_comparison', array($this, 'ajax_clear_comparison'));
        add_action('wp_ajax_nopriv_clear_comparison', array($this, 'ajax_clear_comparison'));
        
        // Add comparison buttons to templates
        add_action('parfume_single_after_content', array($this, 'add_comparison_button'), 5);
        add_action('parfume_archive_loop_item', array($this, 'add_loop_comparison_button'), 60);
        
        // Add comparison widget to footer
        add_action('wp_footer', array($this, 'comparison_widget'));
        
        // Register comparison page
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_comparison_page'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Initialize session for storing comparison list
     */
    public function init_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Enqueue comparison scripts and styles
     */
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            wp_enqueue_script(
                'parfume-comparison',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-comparison', 'parfumeComparison', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-comparison-nonce'),
                'maxItems' => self::MAX_COMPARISON_ITEMS,
                'compareUrl' => $this->get_comparison_url(),
                'strings' => array(
                    'added' => __('Added to comparison', 'parfume-reviews'),
                    'removed' => __('Removed from comparison', 'parfume-reviews'),
                    'maxReached' => sprintf(__('You can compare maximum %d perfumes', 'parfume-reviews'), self::MAX_COMPARISON_ITEMS),
                    'compare' => __('Compare', 'parfume-reviews'),
                    'compareNow' => __('Compare Now', 'parfume-reviews'),
                    'clear' => __('Clear All', 'parfume-reviews'),
                    'addToCompare' => __('Add to Compare', 'parfume-reviews'),
                    'removeFromCompare' => __('Remove from Compare', 'parfume-reviews'),
                    'error' => __('An error occurred', 'parfume-reviews'),
                )
            ));
            
            wp_enqueue_style(
                'parfume-comparison',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comparison.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
        }
    }
    
    /**
     * Get comparison list from session
     */
    public function get_comparison_list() {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = array();
        }
        
        // Validate that all items in the list still exist
        $valid_items = array();
        foreach ($_SESSION[self::SESSION_KEY] as $post_id) {
            if (get_post_status($post_id) === 'publish' && get_post_type($post_id) === 'parfume') {
                $valid_items[] = $post_id;
            }
        }
        
        $_SESSION[self::SESSION_KEY] = $valid_items;
        
        return $valid_items;
    }
    
    /**
     * Add item to comparison list
     */
    public function add_to_comparison($post_id) {
        $post_id = intval($post_id);
        
        // Validate post
        if (get_post_status($post_id) !== 'publish' || get_post_type($post_id) !== 'parfume') {
            return new \WP_Error('invalid_post', __('Invalid perfume', 'parfume-reviews'));
        }
        
        $comparison_list = $this->get_comparison_list();
        
        // Check if already in list
        if (in_array($post_id, $comparison_list)) {
            return new \WP_Error('already_added', __('Perfume already in comparison list', 'parfume-reviews'));
        }
        
        // Check maximum limit
        if (count($comparison_list) >= self::MAX_COMPARISON_ITEMS) {
            return new \WP_Error('max_reached', sprintf(__('You can compare maximum %d perfumes', 'parfume-reviews'), self::MAX_COMPARISON_ITEMS));
        }
        
        // Add to list
        $comparison_list[] = $post_id;
        $_SESSION[self::SESSION_KEY] = $comparison_list;
        
        return true;
    }
    
    /**
     * Remove item from comparison list
     */
    public function remove_from_comparison($post_id) {
        $post_id = intval($post_id);
        $comparison_list = $this->get_comparison_list();
        
        $key = array_search($post_id, $comparison_list);
        if ($key !== false) {
            unset($comparison_list[$key]);
            $_SESSION[self::SESSION_KEY] = array_values($comparison_list);
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear comparison list
     */
    public function clear_comparison() {
        $_SESSION[self::SESSION_KEY] = array();
        return true;
    }
    
    /**
     * Check if item is in comparison list
     */
    public function is_in_comparison($post_id) {
        $comparison_list = $this->get_comparison_list();
        return in_array(intval($post_id), $comparison_list);
    }
    
    /**
     * Get comparison count
     */
    public function get_comparison_count() {
        return count($this->get_comparison_list());
    }
    
    /**
     * AJAX handler for adding to comparison
     */
    public function ajax_add_to_comparison() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'parfume-reviews'));
        }
        
        $result = $this->add_to_comparison($post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Added to comparison', 'parfume-reviews'),
            'count' => $this->get_comparison_count(),
            'list' => $this->get_comparison_data(),
        ));
    }
    
    /**
     * AJAX handler for removing from comparison
     */
    public function ajax_remove_from_comparison() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'parfume-reviews'));
        }
        
        $result = $this->remove_from_comparison($post_id);
        
        if (!$result) {
            wp_send_json_error(__('Item not found in comparison list', 'parfume-reviews'));
        }
        
        wp_send_json_success(array(
            'message' => __('Removed from comparison', 'parfume-reviews'),
            'count' => $this->get_comparison_count(),
            'list' => $this->get_comparison_data(),
        ));
    }
    
    /**
     * AJAX handler for getting comparison count
     */
    public function ajax_get_comparison_count() {
        wp_send_json_success(array(
            'count' => $this->get_comparison_count(),
            'list' => $this->get_comparison_data(),
        ));
    }
    
    /**
     * AJAX handler for clearing comparison
     */
    public function ajax_clear_comparison() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        $this->clear_comparison();
        
        wp_send_json_success(array(
            'message' => __('Comparison list cleared', 'parfume-reviews'),
            'count' => 0,
            'list' => array(),
        ));
    }
    
    /**
     * Get comparison data for JavaScript
     */
    public function get_comparison_data() {
        $comparison_list = $this->get_comparison_list();
        $data = array();
        
        foreach ($comparison_list as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $data[] = array(
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'url' => get_permalink($post_id),
                    'image' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                    'brand' => $this->get_post_brand($post_id),
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Get post brand name
     */
    private function get_post_brand($post_id) {
        $brands = wp_get_post_terms($post_id, 'marki');
        if (!empty($brands) && !is_wp_error($brands)) {
            return $brands[0]->name;
        }
        return '';
    }
    
    /**
     * Add comparison button to single perfume page
     */
    public function add_comparison_button() {
        if (!is_singular('parfume')) {
            return;
        }
        
        $post_id = get_the_ID();
        $is_in_comparison = $this->is_in_comparison($post_id);
        $button_class = $is_in_comparison ? 'in-comparison' : '';
        $button_text = $is_in_comparison ? __('Remove from Compare', 'parfume-reviews') : __('Add to Compare', 'parfume-reviews');
        
        ?>
        <div class="parfume-comparison-actions">
            <button type="button" 
                    class="comparison-toggle-btn <?php echo esc_attr($button_class); ?>" 
                    data-post-id="<?php echo esc_attr($post_id); ?>"
                    data-add-text="<?php esc_attr_e('Add to Compare', 'parfume-reviews'); ?>"
                    data-remove-text="<?php esc_attr_e('Remove from Compare', 'parfume-reviews'); ?>">
                <span class="btn-icon">⚖️</span>
                <span class="btn-text"><?php echo esc_html($button_text); ?></span>
            </button>
        </div>
        <?php
    }
    
    /**
     * Add comparison button to archive loop items
     */
    public function add_loop_comparison_button() {
        if (!is_post_type_archive('parfume') && !is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            return;
        }
        
        $post_id = get_the_ID();
        $is_in_comparison = $this->is_in_comparison($post_id);
        $button_class = $is_in_comparison ? 'in-comparison' : '';
        
        ?>
        <div class="parfume-loop-comparison">
            <button type="button" 
                    class="comparison-toggle-btn small <?php echo esc_attr($button_class); ?>" 
                    data-post-id="<?php echo esc_attr($post_id); ?>"
                    data-add-text="<?php esc_attr_e('Add to Compare', 'parfume-reviews'); ?>"
                    data-remove-text="<?php esc_attr_e('Remove from Compare', 'parfume-reviews'); ?>"
                    title="<?php esc_attr_e('Add to Compare', 'parfume-reviews'); ?>">
                <span class="btn-icon">⚖️</span>
            </button>
        </div>
        <?php
    }
    
    /**
     * Comparison widget in footer
     */
    public function comparison_widget() {
        if (is_admin()) {
            return;
        }
        
        $comparison_count = $this->get_comparison_count();
        $widget_class = $comparison_count > 0 ? 'has-items' : 'empty';
        
        ?>
        <div id="comparison-widget" class="comparison-widget <?php echo esc_attr($widget_class); ?>">
            <div class="widget-header">
                <h4><?php _e('Compare Perfumes', 'parfume-reviews'); ?></h4>
                <span class="count">(<span class="count-number"><?php echo $comparison_count; ?></span>)</span>
                <button type="button" class="widget-toggle" aria-label="<?php esc_attr_e('Toggle comparison widget', 'parfume-reviews'); ?>">
                    <span class="toggle-icon">▼</span>
                </button>
            </div>
            
            <div class="widget-content">
                <div class="comparison-list">
                    <?php if ($comparison_count > 0): ?>
                        <?php $comparison_data = $this->get_comparison_data(); ?>
                        <?php foreach ($comparison_data as $item): ?>
                            <div class="comparison-item" data-post-id="<?php echo esc_attr($item['id']); ?>">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>" class="item-image">
                                <?php endif; ?>
                                <div class="item-info">
                                    <h5 class="item-title"><?php echo esc_html($item['title']); ?></h5>
                                    <?php if ($item['brand']): ?>
                                        <span class="item-brand"><?php echo esc_html($item['brand']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="remove-item" data-post-id="<?php echo esc_attr($item['id']); ?>" title="<?php esc_attr_e('Remove', 'parfume-reviews'); ?>">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-items"><?php _e('No perfumes added for comparison yet.', 'parfume-reviews'); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="widget-actions">
                    <a href="<?php echo esc_url($this->get_comparison_url()); ?>" class="compare-btn" <?php echo $comparison_count < 2 ? 'style="display:none;"' : ''; ?>>
                        <?php _e('Compare Now', 'parfume-reviews'); ?>
                    </a>
                    <button type="button" class="clear-all-btn" <?php echo $comparison_count === 0 ? 'style="display:none;"' : ''; ?>>
                        <?php _e('Clear All', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add rewrite rules for comparison page
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/compare/?$',
            'index.php?parfume_comparison=1',
            'top'
        );
        
        add_rewrite_tag('%parfume_comparison%', '([^&]+)');
    }
    
    /**
     * Handle comparison page template
     */
    public function handle_comparison_page() {
        if (get_query_var('parfume_comparison')) {
            $this->display_comparison_page();
            exit;
        }
    }
    
    /**
     * Display comparison page
     */
    public function display_comparison_page() {
        $comparison_list = $this->get_comparison_list();
        
        // Allow override from URL parameters
        if (isset($_GET['ids']) && !empty($_GET['ids'])) {
            $ids = explode(',', sanitize_text_field($_GET['ids']));
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids);
            
            // Validate IDs
            $valid_ids = array();
            foreach ($ids as $id) {
                if (get_post_status($id) === 'publish' && get_post_type($id) === 'parfume') {
                    $valid_ids[] = $id;
                }
            }
            
            if (!empty($valid_ids)) {
                $comparison_list = array_slice($valid_ids, 0, self::MAX_COMPARISON_ITEMS);
            }
        }
        
        get_header();
        
        ?>
        <div class="parfume-comparison-page">
            <div class="container">
                <header class="page-header">
                    <h1 class="page-title"><?php _e('Compare Perfumes', 'parfume-reviews'); ?></h1>
                    <?php if (!empty($comparison_list)): ?>
                        <p class="page-description">
                            <?php printf(_n('Comparing %d perfume', 'Comparing %d perfumes', count($comparison_list), 'parfume-reviews'), count($comparison_list)); ?>
                        </p>
                    <?php endif; ?>
                </header>
                
                <div class="comparison-content">
                    <?php if (empty($comparison_list)): ?>
                        <div class="no-comparison-items">
                            <h2><?php _e('No perfumes to compare', 'parfume-reviews'); ?></h2>
                            <p><?php _e('Add some perfumes to your comparison list and come back to compare them.', 'parfume-reviews'); ?></p>
                            <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="button">
                                <?php _e('Browse Perfumes', 'parfume-reviews'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <?php $this->display_comparison_table($comparison_list); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        
        get_footer();
    }
    
    /**
     * Display comparison table
     */
    public function display_comparison_table($parfume_ids) {
        $parfumes = array();
        foreach ($parfume_ids as $id) {
            $post = get_post($id);
            if ($post) {
                $parfumes[] = $post;
            }
        }
        
        if (empty($parfumes)) {
            return;
        }
        
        ?>
        <div class="comparison-table-container">
            <table class="parfume-comparison-table">
                <thead>
                    <tr>
                        <th class="attribute-column"><?php _e('Attribute', 'parfume-reviews'); ?></th>
                        <?php foreach ($parfumes as $parfume): ?>
                            <th class="parfume-column">
                                <div class="parfume-header">
                                    <div class="parfume-actions">
                                        <button type="button" class="remove-from-comparison" data-post-id="<?php echo esc_attr($parfume->ID); ?>" title="<?php esc_attr_e('Remove from comparison', 'parfume-reviews'); ?>">×</button>
                                    </div>
                                    
                                    <?php if (has_post_thumbnail($parfume->ID)): ?>
                                        <div class="parfume-image">
                                            <a href="<?php echo get_permalink($parfume->ID); ?>">
                                                <?php echo get_the_post_thumbnail($parfume->ID, 'medium'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h3 class="parfume-title">
                                        <a href="<?php echo get_permalink($parfume->ID); ?>">
                                            <?php echo esc_html($parfume->post_title); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php
                                    $brands = wp_get_post_terms($parfume->ID, 'marki');
                                    if (!empty($brands) && !is_wp_error($brands)):
                                    ?>
                                        <div class="parfume-brand">
                                            <?php foreach ($brands as $brand): ?>
                                                <a href="<?php echo get_term_link($brand); ?>"><?php echo esc_html($brand->name); ?></a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Define comparison attributes
                    $attributes = array(
                        'rating' => __('Rating', 'parfume-reviews'),
                        'gender' => __('Gender', 'parfume-reviews'),
                        'aroma_type' => __('Aroma Type', 'parfume-reviews'),
                        'season' => __('Season', 'parfume-reviews'),
                        'intensity' => __('Intensity', 'parfume-reviews'),
                        'longevity' => __('Longevity', 'parfume-reviews'),
                        'sillage' => __('Sillage', 'parfume-reviews'),
                        'release_year' => __('Release Year', 'parfume-reviews'),
                        'notes' => __('Notes', 'parfume-reviews'),
                        'perfumer' => __('Perfumer', 'parfume-reviews'),
                        'price_range' => __('Price Range', 'parfume-reviews'),
                        'bottle_size' => __('Bottle Sizes', 'parfume-reviews'),
                    );
                    
                    foreach ($attributes as $attr_key => $attr_label):
                    ?>
                        <tr class="comparison-row row-<?php echo esc_attr($attr_key); ?>">
                            <td class="attribute-name"><?php echo esc_html($attr_label); ?></td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td class="parfume-value">
                                    <?php echo $this->get_comparison_value($parfume->ID, $attr_key); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="comparison-actions">
            <button type="button" class="button clear-comparison">
                <?php _e('Clear Comparison', 'parfume-reviews'); ?>
            </button>
            <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="button">
                <?php _e('Add More Perfumes', 'parfume-reviews'); ?>
            </a>
        </div>
        <?php
    }
    
    /**
     * Get comparison value for specific attribute
     */
    private function get_comparison_value($post_id, $attribute) {
        switch ($attribute) {
            case 'rating':
                $rating = get_post_meta($post_id, '_parfume_rating', true);
                if (!empty($rating)) {
                    return '<div class="rating-display">' . parfume_reviews_display_rating($rating) . ' <span class="rating-number">(' . number_format($rating, 1) . ')</span></div>';
                }
                return '<span class="no-value">-</span>';
                
            case 'gender':
                $terms = wp_get_post_terms($post_id, 'gender');
                return $this->format_taxonomy_terms($terms);
                
            case 'aroma_type':
                $terms = wp_get_post_terms($post_id, 'aroma_type');
                return $this->format_taxonomy_terms($terms);
                
            case 'season':
                $terms = wp_get_post_terms($post_id, 'season');
                return $this->format_taxonomy_terms($terms);
                
            case 'intensity':
                $terms = wp_get_post_terms($post_id, 'intensity');
                return $this->format_taxonomy_terms($terms);
                
            case 'longevity':
                $value = get_post_meta($post_id, '_parfume_longevity', true);
                return !empty($value) ? esc_html($value) : '<span class="no-value">-</span>';
                
            case 'sillage':
                $value = get_post_meta($post_id, '_parfume_sillage', true);
                return !empty($value) ? esc_html($value) : '<span class="no-value">-</span>';
                
            case 'release_year':
                $value = get_post_meta($post_id, '_parfume_release_year', true);
                return !empty($value) ? esc_html($value) : '<span class="no-value">-</span>';
                
            case 'notes':
                $terms = wp_get_post_terms($post_id, 'notes', array('number' => 8));
                if (!empty($terms) && !is_wp_error($terms)) {
                    $notes_html = '<div class="notes-list">';
                    foreach ($terms as $term) {
                        $notes_html .= '<span class="note-item"><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></span>';
                    }
                    if (count($terms) == 8) {
                        $all_notes = wp_get_post_terms($post_id, 'notes');
                        if (count($all_notes) > 8) {
                            $notes_html .= '<span class="more-notes">+' . (count($all_notes) - 8) . ' more</span>';
                        }
                    }
                    $notes_html .= '</div>';
                    return $notes_html;
                }
                return '<span class="no-value">-</span>';
                
            case 'perfumer':
                $terms = wp_get_post_terms($post_id, 'perfumer');
                return $this->format_taxonomy_terms($terms);
                
            case 'price_range':
                $stores = get_post_meta($post_id, '_parfume_stores_v2', true);
                if (!empty($stores) && is_array($stores)) {
                    $prices = array();
                    foreach ($stores as $store) {
                        if (!empty($store['scraped_data']['variants'])) {
                            foreach ($store['scraped_data']['variants'] as $variant) {
                                $price = floatval(preg_replace('/[^\d.]/', '', $variant['price']));
                                if ($price > 0) {
                                    $prices[] = $price;
                                }
                            }
                        }
                    }
                    
                    if (!empty($prices)) {
                        $min_price = min($prices);
                        $max_price = max($prices);
                        if ($min_price == $max_price) {
                            return '<span class="price-single">' . number_format($min_price, 2) . ' лв.</span>';
                        } else {
                            return '<span class="price-range">' . number_format($min_price, 2) . ' - ' . number_format($max_price, 2) . ' лв.</span>';
                        }
                    }
                }
                return '<span class="no-value">-</span>';
                
            case 'bottle_size':
                $sizes = get_post_meta($post_id, '_parfume_bottle_size', true);
                if (!empty($sizes)) {
                    if (is_array($sizes)) {
                        return '<span class="bottle-sizes">' . implode(', ', array_map('esc_html', $sizes)) . '</span>';
                    } else {
                        return '<span class="bottle-size">' . esc_html($sizes) . '</span>';
                    }
                }
                return '<span class="no-value">-</span>';
                
            default:
                return '<span class="no-value">-</span>';
        }
    }
    
    /**
     * Format taxonomy terms for display
     */
    private function format_taxonomy_terms($terms) {
        if (!empty($terms) && !is_wp_error($terms)) {
            $links = array();
            foreach ($terms as $term) {
                $links[] = '<a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a>';
            }
            return implode(', ', $links);
        }
        return '<span class="no-value">-</span>';
    }
    
    /**
     * Get comparison URL
     */
    public function get_comparison_url() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        return home_url('/' . $parfume_slug . '/compare/');
    }
    
    /**
     * Add admin menu for comparison management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Comparison Settings', 'parfume-reviews'),
            __('Comparison', 'parfume-reviews'),
            'manage_options',
            'parfume-comparison-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page for comparison settings
     */
    public function admin_page() {
        if (isset($_POST['save_settings'])) {
            check_admin_referer('parfume_comparison_settings');
            
            $max_items = isset($_POST['max_comparison_items']) ? intval($_POST['max_comparison_items']) : 4;
            $max_items = max(2, min(10, $max_items)); // Between 2 and 10
            
            update_option('parfume_comparison_max_items', $max_items);
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'parfume-reviews') . '</p></div>';
        }
        
        $max_items = get_option('parfume_comparison_max_items', 4);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Comparison Settings', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('parfume_comparison_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="max_comparison_items"><?php _e('Maximum Comparison Items', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_comparison_items" name="max_comparison_items" value="<?php echo esc_attr($max_items); ?>" min="2" max="10" class="small-text">
                            <p class="description"><?php _e('Maximum number of perfumes that can be compared simultaneously (2-10).', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Settings', 'parfume-reviews'), 'primary', 'save_settings'); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Comparison Statistics', 'parfume-reviews'); ?></h2>
            <p><?php _e('Here you can view statistics about perfume comparisons.', 'parfume-reviews'); ?></p>
            
            <div class="comparison-stats">
                <div class="stat-box">
                    <h3><?php _e('Most Compared Perfumes', 'parfume-reviews'); ?></h3>
                    <p><?php _e('Feature coming soon...', 'parfume-reviews'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}