<?php
/**
 * Comparison Feature
 * 
 * Handles parfume comparison functionality
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Comparison
 * @since 2.0.0
 */

namespace ParfumeReviews\Features\Comparison;

use ParfumeReviews\Core\Container;

/**
 * Comparison Class
 * 
 * Manages parfume comparison feature
 */
class Comparison {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Session key for comparison items
     * 
     * @var string
     */
    private $session_key = 'parfume_comparison';
    
    /**
     * Maximum items for comparison
     * 
     * @var int
     */
    private $max_items = 4;
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        
        // Get max items from settings
        $settings = get_option('parfume_reviews_settings', []);
        if (isset($settings['max_compare_items'])) {
            $this->max_items = absint($settings['max_compare_items']);
        }
    }
    
    /**
     * Initialize comparison feature
     */
    public function init() {
        // Start session if not started
        if (!session_id()) {
            add_action('init', 'session_start', 1);
        }
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Add comparison button to parfume cards
        add_action('parfume_card_actions', [$this, 'render_comparison_button']);
        
        // Render comparison bar
        add_action('wp_footer', [$this, 'render_comparison_bar']);
    }
    
    /**
     * Enqueue comparison assets
     */
    public function enqueue_assets() {
        // Only on parfume pages
        if (!$this->is_parfume_page()) {
            return;
        }
        
        $settings = get_option('parfume_reviews_settings', []);
        $enabled = isset($settings['enable_comparison']) ? $settings['enable_comparison'] : true;
        
        if (!$enabled) {
            return;
        }
        
        wp_enqueue_style(
            'parfume-reviews-comparison',
            PARFUME_REVIEWS_URL . 'assets/css/comparison.css',
            [],
            PARFUME_REVIEWS_VERSION
        );
        
        wp_enqueue_script(
            'parfume-reviews-comparison',
            PARFUME_REVIEWS_URL . 'assets/js/comparison.js',
            ['jquery'],
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('parfume-reviews-comparison', 'parfumeComparison', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_comparison_nonce'),
            'maxItems' => $this->max_items,
            'items' => $this->get_items(),
            'strings' => [
                'added' => __('Добавено към сравнение', 'parfume-reviews'),
                'removed' => __('Премахнато от сравнение', 'parfume-reviews'),
                'max_reached' => sprintf(
                    __('Можете да сравните максимум %d парфюма', 'parfume-reviews'),
                    $this->max_items
                ),
                'compare' => __('Сравни', 'parfume-reviews'),
                'clear' => __('Изчисти', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
            ]
        ]);
    }
    
    /**
     * Add parfume to comparison
     * 
     * @param int $post_id Post ID
     * @return bool
     */
    public function add($post_id) {
        $items = $this->get_items();
        
        // Check if already in comparison
        if (in_array($post_id, $items)) {
            return true;
        }
        
        // Check max limit
        if (count($items) >= $this->max_items) {
            return false;
        }
        
        // Add to comparison
        $items[] = $post_id;
        $this->save_items($items);
        
        return true;
    }
    
    /**
     * Remove parfume from comparison
     * 
     * @param int $post_id Post ID
     * @return bool
     */
    public function remove($post_id) {
        $items = $this->get_items();
        
        // Remove from array
        $key = array_search($post_id, $items);
        if ($key !== false) {
            unset($items[$key]);
            $items = array_values($items); // Re-index array
            $this->save_items($items);
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear all comparison items
     * 
     * @return bool
     */
    public function clear() {
        $this->save_items([]);
        return true;
    }
    
    /**
     * Get comparison items
     * 
     * @return array
     */
    public function get_items() {
        if (isset($_SESSION[$this->session_key])) {
            return (array) $_SESSION[$this->session_key];
        }
        
        return [];
    }
    
    /**
     * Save comparison items
     * 
     * @param array $items Items to save
     */
    private function save_items($items) {
        $_SESSION[$this->session_key] = array_values($items);
    }
    
    /**
     * Get comparison count
     * 
     * @return int
     */
    public function get_count() {
        return count($this->get_items());
    }
    
    /**
     * Check if parfume is in comparison
     * 
     * @param int $post_id Post ID
     * @return bool
     */
    public function has_item($post_id) {
        return in_array($post_id, $this->get_items());
    }
    
    /**
     * Get comparison data
     * 
     * @return array
     */
    public function get_comparison_data() {
        $items = $this->get_items();
        $data = [];
        
        foreach ($items as $post_id) {
            $post = get_post($post_id);
            
            if (!$post || $post->post_type !== 'parfume') {
                continue;
            }
            
            $data[] = [
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'url' => get_permalink($post_id),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'rating' => get_post_meta($post_id, '_parfume_rating', true),
                'price' => $this->get_cheapest_price($post_id),
                'brand' => $this->get_brand($post_id),
                'gender' => $this->get_gender($post_id),
                'longevity' => get_post_meta($post_id, '_parfume_longevity', true),
                'sillage' => get_post_meta($post_id, '_parfume_sillage', true),
                'release_year' => get_post_meta($post_id, '_parfume_release_year', true),
                'notes' => $this->get_notes($post_id),
            ];
        }
        
        return $data;
    }
    
    /**
     * Get cheapest price for parfume
     * 
     * @param int $post_id Post ID
     * @return float|null
     */
    private function get_cheapest_price($post_id) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($stores) || empty($stores)) {
            return null;
        }
        
        $prices = array_filter(array_column($stores, 'price'));
        
        return !empty($prices) ? min($prices) : null;
    }
    
    /**
     * Get brand name
     * 
     * @param int $post_id Post ID
     * @return string
     */
    private function get_brand($post_id) {
        $brands = wp_get_post_terms($post_id, 'marki');
        
        if (!empty($brands) && !is_wp_error($brands)) {
            return $brands[0]->name;
        }
        
        return '';
    }
    
    /**
     * Get gender
     * 
     * @param int $post_id Post ID
     * @return string
     */
    private function get_gender($post_id) {
        $genders = wp_get_post_terms($post_id, 'gender');
        
        if (!empty($genders) && !is_wp_error($genders)) {
            return $genders[0]->name;
        }
        
        return '';
    }
    
    /**
     * Get notes
     * 
     * @param int $post_id Post ID
     * @return array
     */
    private function get_notes($post_id) {
        $notes = wp_get_post_terms($post_id, 'notes');
        
        if (!empty($notes) && !is_wp_error($notes)) {
            return array_map(function($note) {
                return $note->name;
            }, $notes);
        }
        
        return [];
    }
    
    /**
     * Render comparison button
     * 
     * @param int $post_id Post ID
     */
    public function render_comparison_button($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $is_in_comparison = $this->has_item($post_id);
        $class = $is_in_comparison ? 'in-comparison' : '';
        $text = $is_in_comparison ? __('Премахни от сравнение', 'parfume-reviews') : __('Добави за сравнение', 'parfume-reviews');
        
        ?>
        <button type="button" 
                class="comparison-toggle-btn <?php echo esc_attr($class); ?>" 
                data-post-id="<?php echo esc_attr($post_id); ?>"
                title="<?php echo esc_attr($text); ?>">
            <span class="dashicons dashicons-plus-alt"></span>
            <span class="btn-text"><?php echo esc_html($text); ?></span>
        </button>
        <?php
    }
    
    /**
     * Render comparison bar
     */
    public function render_comparison_bar() {
        $settings = get_option('parfume_reviews_settings', []);
        $enabled = isset($settings['enable_comparison']) ? $settings['enable_comparison'] : true;
        
        if (!$enabled || !$this->is_parfume_page()) {
            return;
        }
        
        $items = $this->get_items();
        $count = count($items);
        $comparison_page = isset($settings['comparison_page']) ? get_permalink($settings['comparison_page']) : '#';
        
        ?>
        <div id="comparison-bar" class="comparison-bar <?php echo $count > 0 ? 'has-items' : ''; ?>">
            <div class="comparison-bar-inner">
                <div class="comparison-info">
                    <span class="comparison-icon">
                        <span class="dashicons dashicons-forms"></span>
                    </span>
                    <span class="comparison-count">
                        <?php printf(__('Сравнение (%d)', 'parfume-reviews'), $count); ?>
                    </span>
                </div>
                
                <div class="comparison-items">
                    <?php if ($count > 0): ?>
                        <?php foreach ($items as $item_id): ?>
                            <div class="comparison-item" data-post-id="<?php echo esc_attr($item_id); ?>">
                                <?php if (has_post_thumbnail($item_id)): ?>
                                    <?php echo get_the_post_thumbnail($item_id, 'thumbnail'); ?>
                                <?php endif; ?>
                                <button type="button" class="remove-from-comparison" data-post-id="<?php echo esc_attr($item_id); ?>">
                                    <span class="dashicons dashicons-no"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="comparison-actions">
                    <?php if ($count > 1): ?>
                        <a href="<?php echo esc_url($comparison_page); ?>" class="button button-primary compare-btn">
                            <?php _e('Сравни', 'parfume-reviews'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($count > 0): ?>
                        <button type="button" class="button clear-comparison-btn">
                            <?php _e('Изчисти', 'parfume-reviews'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if current page is a parfume page
     * 
     * @return bool
     */
    private function is_parfume_page() {
        return is_singular('parfume') || 
               is_post_type_archive('parfume') || 
               is_tax(['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer']);
    }
    
    /**
     * Get comparison URL
     * 
     * @return string
     */
    public function get_comparison_url() {
        $settings = get_option('parfume_reviews_settings', []);
        
        if (isset($settings['comparison_page'])) {
            return get_permalink($settings['comparison_page']);
        }
        
        return home_url('/comparison/');
    }
}