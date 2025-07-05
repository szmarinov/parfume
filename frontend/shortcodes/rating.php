<?php
/**
 * Rating Shortcode - Адаптиран към новата архитектура
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
 * Rating Shortcode Class
 */
class Rating extends Shortcode_Base {
    
    /**
     * Shortcode tag
     *
     * @var string
     */
    protected $tag = 'parfume_rating';
    
    /**
     * Default attributes
     *
     * @var array
     */
    protected $default_atts = array(
        'show_empty' => 'true',
        'show_average' => 'true',
        'show_count' => 'false',
        'size' => 'medium',
        'alignment' => 'left',
        'post_id' => 0,
    );
    
    /**
     * Required post types for this shortcode
     *
     * @var array
     */
    protected $required_post_types = array('parfume');
    
    /**
     * Initialize the shortcode
     */
    public function init() {
        // Enqueue rating styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add rating schema markup
        add_action('wp_head', array($this, 'add_schema_markup'));
    }
    
    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered HTML
     */
    public function render($atts, $content = '') {
        // Get post ID - from attribute or current post
        $post_id = !empty($atts['post_id']) ? intval($atts['post_id']) : get_the_ID();
        
        if (!$post_id) {
            return $this->render_error(__('No post found for rating display.', 'parfume-reviews'));
        }
        
        // Validate post type
        if (!$this->validate_post_type($post_id)) {
            if ($this->show_errors) {
                return $this->render_error(__('Rating shortcode can only be used with parfume posts.', 'parfume-reviews'));
            }
            return '';
        }
        
        // Get rating data
        $rating_data = $this->get_rating_data($post_id);
        
        // Check if we should show empty ratings
        if (empty($rating_data['rating']) && !$this->string_to_bool($atts['show_empty'])) {
            return '';
        }
        
        // Render rating HTML
        return $this->render_rating($rating_data, $atts);
    }
    
    /**
     * Get rating data for a post
     *
     * @param int $post_id Post ID
     * @return array Rating data
     */
    private function get_rating_data($post_id) {
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        $rating = !empty($rating) ? floatval($rating) : 0;
        
        // Get review count (from comments or custom meta)
        $review_count = get_comments_number($post_id);
        
        // Calculate additional metrics
        $rounded_rating = round($rating * 2) / 2; // Round to nearest 0.5
        $percentage = ($rating / 5) * 100;
        
        return array(
            'rating' => $rating,
            'rounded_rating' => $rounded_rating,
            'percentage' => $percentage,
            'review_count' => $review_count,
            'max_rating' => 5,
        );
    }
    
    /**
     * Render rating HTML
     *
     * @param array $rating_data Rating data
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    private function render_rating($rating_data, $atts) {
        $css_classes = array(
            'parfume-rating',
            'size-' . esc_attr($atts['size']),
            'align-' . esc_attr($atts['alignment']),
        );
        
        if (empty($rating_data['rating'])) {
            $css_classes[] = 'no-rating';
        }
        
        ob_start();
        ?>
        <div class="<?php echo implode(' ', $css_classes); ?>" data-rating="<?php echo esc_attr($rating_data['rating']); ?>">
            <div class="rating-stars" aria-label="<?php printf(__('Rating: %s out of %s stars', 'parfume-reviews'), $rating_data['rating'], $rating_data['max_rating']); ?>">
                <?php echo $this->render_stars($rating_data); ?>
            </div>
            
            <?php if ($this->string_to_bool($atts['show_average']) && !empty($rating_data['rating'])): ?>
                <div class="rating-average">
                    <span class="rating-number"><?php echo number_format($rating_data['rating'], 1); ?></span>
                    <span class="rating-separator">/</span>
                    <span class="rating-max"><?php echo $rating_data['max_rating']; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($this->string_to_bool($atts['show_count']) && $rating_data['review_count'] > 0): ?>
                <div class="rating-count">
                    <?php printf(
                        _n('(%d review)', '(%d reviews)', $rating_data['review_count'], 'parfume-reviews'),
                        $rating_data['review_count']
                    ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render star HTML
     *
     * @param array $rating_data Rating data
     * @return string Stars HTML
     */
    private function render_stars($rating_data) {
        $output = '';
        $rating = $rating_data['rounded_rating'];
        
        for ($i = 1; $i <= $rating_data['max_rating']; $i++) {
            $star_class = 'star';
            
            if ($i <= floor($rating)) {
                $star_class .= ' filled';
            } elseif ($i <= $rating) {
                $star_class .= ' half-filled';
            } else {
                $star_class .= ' empty';
            }
            
            $output .= sprintf(
                '<span class="%s" data-star="%d">★</span>',
                esc_attr($star_class),
                $i
            );
        }
        
        return $output;
    }
    
    /**
     * Enqueue rating assets
     */
    public function enqueue_assets() {
        if (!is_admin() && (is_singular('parfume') || $this->has_shortcode_on_page())) {
            wp_enqueue_style(
                'parfume-rating',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/rating.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-rating',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/rating.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
        }
    }
    
    /**
     * Add schema markup for ratings
     */
    public function add_schema_markup() {
        if (is_singular('parfume')) {
            $post_id = get_the_ID();
            $rating_data = $this->get_rating_data($post_id);
            
            if (!empty($rating_data['rating'])) {
                $schema = array(
                    '@type' => 'AggregateRating',
                    'ratingValue' => $rating_data['rating'],
                    'bestRating' => $rating_data['max_rating'],
                    'worstRating' => 1,
                    'ratingCount' => max(1, $rating_data['review_count']),
                );
                
                echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
            }
        }
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
new Rating();