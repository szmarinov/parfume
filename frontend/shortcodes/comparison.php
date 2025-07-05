<?php
/**
 * Comparison Shortcodes
 * 
 * @package Parfume_Reviews
 * @subpackage Frontend\Shortcodes
 * @since 1.0.0
 */

namespace Parfume_Reviews\Frontend\Shortcodes;

use Parfume_Reviews\Utils\Base_Classes\Shortcode_Base;
use Parfume_Reviews\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comparison shortcodes handler
 */
class Comparison extends Shortcode_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->register_shortcodes();
        $this->init_ajax_hooks();
    }
    
    /**
     * Register all comparison shortcodes
     */
    protected function register_shortcodes() {
        add_shortcode('parfume_comparison', array($this, 'comparison_table'));
        add_shortcode('comparison_button', array($this, 'comparison_button'));
        add_shortcode('comparison_widget', array($this, 'comparison_widget'));
        add_shortcode('comparison_popup', array($this, 'comparison_popup'));
        add_shortcode('similar_comparison', array($this, 'similar_comparison'));
    }
    
    /**
     * Initialize AJAX hooks
     */
    private function init_ajax_hooks() {
        add_action('wp_ajax_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_nopriv_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_nopriv_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_nopriv_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_clear_comparison', array($this, 'ajax_clear_comparison'));
        add_action('wp_ajax_nopriv_clear_comparison', array($this, 'ajax_clear_comparison'));
    }
    
    /**
     * Comparison table shortcode
     */
    public function comparison_table($atts) {
        $atts = $this->parse_attributes($atts, array(
            'perfumes' => '',
            'show_image' => true,
            'show_rating' => true,
            'show_brand' => true,
            'show_notes' => true,
            'show_price' => true,
            'show_availability' => true,
            'show_details' => true,
            'show_description' => false,
            'max_items' => 4,
        ));
        
        ob_start();
        
        try {
            $perfume_ids = array();
            
            if (!empty($atts['perfumes'])) {
                $perfume_ids = array_filter(array_map('intval', explode(',', $atts['perfumes'])));
            } else {
                // Get from cookie/session
                $perfume_ids = $this->get_comparison_items();
            }
            
            if (empty($perfume_ids)) {
                return $this->render_empty_message(__('No perfumes selected for comparison.', 'parfume-reviews'));
            }
            
            $perfume_ids = array_slice($perfume_ids, 0, intval($atts['max_items']));
            
            $perfumes = get_posts(array(
                'post_type' => 'parfume',
                'post__in' => $perfume_ids,
                'posts_per_page' => -1,
                'orderby' => 'post__in',
            ));
            
            if (empty($perfumes)) {
                return $this->render_empty_message(__('Selected perfumes not found.', 'parfume-reviews'));
            }
            
            $this->render_comparison_table($perfumes, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading comparison: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load comparison at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Comparison button shortcode
     */
    public function comparison_button($atts) {
        global $post;
        
        $atts = $this->parse_attributes($atts, array(
            'post_id' => $post ? $post->ID : 0,
            'text_add' => __('Add to Compare', 'parfume-reviews'),
            'text_remove' => __('Remove from Compare', 'parfume-reviews'),
            'text_compare' => __('Compare Now', 'parfume-reviews'),
            'show_count' => true,
            'style' => 'button', // button, link, icon
            'size' => 'medium', // small, medium, large
        ));
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        ob_start();
        
        try {
            $comparison_items = $this->get_comparison_items();
            $is_in_comparison = in_array($post_id, $comparison_items);
            $comparison_count = count($comparison_items);
            
            $this->render_comparison_button($post_id, $is_in_comparison, $comparison_count, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading comparison button: ' . $e->getMessage());
            }
            return '';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Comparison widget shortcode
     */
    public function comparison_widget($atts) {
        $atts = $this->parse_attributes($atts, array(
            'position' => 'fixed', // fixed, static
            'show_count' => true,
            'show_items' => true,
            'max_display' => 3,
            'auto_hide' => true,
        ));
        
        ob_start();
        
        try {
            $comparison_items = $this->get_comparison_items();
            
            if (empty($comparison_items) && $this->parse_bool($atts['auto_hide'])) {
                return '';
            }
            
            $this->render_comparison_widget($comparison_items, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading comparison widget: ' . $e->getMessage());
            }
            return '';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Comparison popup shortcode
     */
    public function comparison_popup($atts) {
        $atts = $this->parse_attributes($atts, array(
            'trigger' => 'click', // click, auto
            'show_on_count' => 2,
            'modal_title' => __('Compare Perfumes', 'parfume-reviews'),
        ));
        
        ob_start();
        
        try {
            $this->render_comparison_popup($atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading comparison popup: ' . $e->getMessage());
            }
            return '';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Similar comparison shortcode
     */
    public function similar_comparison($atts) {
        global $post;
        
        $atts = $this->parse_attributes($atts, array(
            'post_id' => $post ? $post->ID : 0,
            'limit' => 3,
            'orderby' => 'similarity',
            'show_button' => true,
            'title' => __('Similar Perfumes to Compare', 'parfume-reviews'),
        ));
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        ob_start();
        
        try {
            $similar_perfumes = $this->get_similar_perfumes($post_id, intval($atts['limit']));
            
            if (empty($similar_perfumes)) {
                return '';
            }
            
            $this->render_similar_comparison($similar_perfumes, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading similar comparison: ' . $e->getMessage());
            }
            return '';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render comparison table
     */
    private function render_comparison_table($perfumes, $atts) {
        ?>
        <div class="parfume-comparison-table" data-max-items="<?php echo esc_attr($atts['max_items']); ?>">
            <div class="comparison-header">
                <h3 class="comparison-title"><?php _e('Perfume Comparison', 'parfume-reviews'); ?></h3>
                <div class="comparison-actions">
                    <button type="button" class="clear-comparison-btn" title="<?php esc_attr_e('Clear All', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Clear All', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="export-comparison-btn" title="<?php esc_attr_e('Export Comparison', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
            
            <div class="comparison-table-wrapper">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th class="comparison-feature"><?php _e('Feature', 'parfume-reviews'); ?></th>
                            <?php foreach ($perfumes as $perfume): ?>
                                <th class="comparison-perfume">
                                    <div class="perfume-header">
                                        <?php if ($this->parse_bool($atts['show_image']) && has_post_thumbnail($perfume->ID)): ?>
                                            <div class="perfume-image">
                                                <?php echo get_the_post_thumbnail($perfume->ID, 'thumbnail'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="perfume-basic-info">
                                            <h4 class="perfume-title">
                                                <a href="<?php echo esc_url(get_permalink($perfume->ID)); ?>">
                                                    <?php echo esc_html($perfume->post_title); ?>
                                                </a>
                                            </h4>
                                            
                                            <?php if ($this->parse_bool($atts['show_brand'])): ?>
                                                <?php 
                                                $brands = wp_get_post_terms($perfume->ID, 'marki', array('fields' => 'names'));
                                                if (!empty($brands) && !is_wp_error($brands)): 
                                                ?>
                                                    <span class="perfume-brand"><?php echo esc_html($brands[0]); ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button type="button" class="remove-from-comparison" data-perfume-id="<?php echo esc_attr($perfume->ID); ?>" title="<?php esc_attr_e('Remove from comparison', 'parfume-reviews'); ?>">
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if ($this->parse_bool($atts['show_rating'])): ?>
                            <tr class="comparison-row rating-row">
                                <td class="feature-label"><?php _e('Rating', 'parfume-reviews'); ?></td>
                                <?php foreach ($perfumes as $perfume): ?>
                                    <td class="feature-value">
                                        <?php 
                                        $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
                                        if (!empty($rating)): 
                                        ?>
                                            <div class="rating-display">
                                                <?php echo Helpers::get_rating_stars($rating); ?>
                                                <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-data"><?php _e('Not rated', 'parfume-reviews'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if ($this->parse_bool($atts['show_price'])): ?>
                            <tr class="comparison-row price-row">
                                <td class="feature-label"><?php _e('Price', 'parfume-reviews'); ?></td>
                                <?php foreach ($perfumes as $perfume): ?>
                                    <td class="feature-value">
                                        <?php 
                                        $price_info = Helpers::get_lowest_price($perfume->ID);
                                        if ($price_info): 
                                        ?>
                                            <div class="price-display">
                                                <span class="price-value"><?php echo esc_html($price_info['price']); ?></span>
                                                <span class="price-store"><?php echo esc_html($price_info['name']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-data"><?php _e('Price not available', 'parfume-reviews'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if ($this->parse_bool($atts['show_availability'])): ?>
                            <tr class="comparison-row availability-row">
                                <td class="feature-label"><?php _e('Availability', 'parfume-reviews'); ?></td>
                                <?php foreach ($perfumes as $perfume): ?>
                                    <td class="feature-value">
                                        <?php 
                                        $is_available = Helpers::is_available($perfume->ID);
                                        ?>
                                        <span class="availability-status <?php echo $is_available ? 'available' : 'unavailable'; ?>">
                                            <?php echo $is_available ? __('Available', 'parfume-reviews') : __('Out of Stock', 'parfume-reviews'); ?>
                                        </span>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if ($this->parse_bool($atts['show_details'])): ?>
                            <?php $this->render_detail_rows($perfumes); ?>
                        <?php endif; ?>
                        
                        <?php if ($this->parse_bool($atts['show_notes'])): ?>
                            <tr class="comparison-row notes-row">
                                <td class="feature-label"><?php _e('Top Notes', 'parfume-reviews'); ?></td>
                                <?php foreach ($perfumes as $perfume): ?>
                                    <td class="feature-value">
                                        <?php 
                                        $notes = wp_get_post_terms($perfume->ID, 'notes', array('number' => 5));
                                        if (!empty($notes) && !is_wp_error($notes)): 
                                        ?>
                                            <div class="notes-list">
                                                <?php foreach ($notes as $note): ?>
                                                    <span class="note-tag"><?php echo esc_html($note->name); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-data"><?php _e('No notes listed', 'parfume-reviews'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if ($this->parse_bool($atts['show_description'])): ?>
                            <tr class="comparison-row description-row">
                                <td class="feature-label"><?php _e('Description', 'parfume-reviews'); ?></td>
                                <?php foreach ($perfumes as $perfume): ?>
                                    <td class="feature-value">
                                        <div class="description-text">
                                            <?php echo wp_trim_words(get_post_field('post_content', $perfume->ID), 25); ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="comparison-footer">
                <div class="comparison-actions-footer">
                    <?php foreach ($perfumes as $perfume): ?>
                        <div class="perfume-actions">
                            <a href="<?php echo esc_url(get_permalink($perfume->ID)); ?>" class="view-perfume-btn">
                                <?php _e('View Details', 'parfume-reviews'); ?>
                            </a>
                            
                            <?php 
                            $price_info = Helpers::get_lowest_price($perfume->ID);
                            if ($price_info && !empty($price_info['url'])): 
                            ?>
                                <a href="<?php echo esc_url($price_info['url']); ?>" class="buy-perfume-btn" target="_blank" rel="nofollow">
                                    <?php _e('Buy Now', 'parfume-reviews'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render detail rows
     */
    private function render_detail_rows($perfumes) {
        $detail_fields = array(
            '_parfume_gender' => __('Gender', 'parfume-reviews'),
            '_parfume_release_year' => __('Release Year', 'parfume-reviews'),
            '_parfume_longevity' => __('Longevity', 'parfume-reviews'),
            '_parfume_sillage' => __('Sillage', 'parfume-reviews'),
            '_parfume_bottle_size' => __('Bottle Size', 'parfume-reviews'),
        );
        
        foreach ($detail_fields as $field_key => $field_label):
        ?>
            <tr class="comparison-row detail-row">
                <td class="feature-label"><?php echo esc_html($field_label); ?></td>
                <?php foreach ($perfumes as $perfume): ?>
                    <td class="feature-value">
                        <?php 
                        $value = get_post_meta($perfume->ID, $field_key, true);
                        if (!empty($value)): 
                        ?>
                            <span class="detail-value"><?php echo esc_html($value); ?></span>
                        <?php else: ?>
                            <span class="no-data"><?php _e('Not specified', 'parfume-reviews'); ?></span>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php 
        endforeach;
    }
    
    /**
     * Render comparison button
     */
    private function render_comparison_button($post_id, $is_in_comparison, $comparison_count, $atts) {
        $button_class = 'parfume-comparison-btn';
        $button_class .= ' style-' . esc_attr($atts['style']);
        $button_class .= ' size-' . esc_attr($atts['size']);
        $button_class .= $is_in_comparison ? ' in-comparison' : ' not-in-comparison';
        ?>
        <div class="parfume-comparison-button-wrapper">
            <button type="button" 
                    class="<?php echo esc_attr($button_class); ?>" 
                    data-perfume-id="<?php echo esc_attr($post_id); ?>"
                    data-action="<?php echo $is_in_comparison ? 'remove' : 'add'; ?>"
                    data-text-add="<?php echo esc_attr($atts['text_add']); ?>"
                    data-text-remove="<?php echo esc_attr($atts['text_remove']); ?>">
                
                <?php if ($atts['style'] === 'icon'): ?>
                    <span class="comparison-icon">
                        <?php if ($is_in_comparison): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-plus-alt"></span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                
                <span class="comparison-text">
                    <?php echo $is_in_comparison ? esc_html($atts['text_remove']) : esc_html($atts['text_add']); ?>
                </span>
            </button>
            
            <?php if ($this->parse_bool($atts['show_count']) && $comparison_count > 0): ?>
                <div class="comparison-count-badge">
                    <span class="count-number"><?php echo esc_html($comparison_count); ?></span>
                    <?php if ($comparison_count >= 2): ?>
                        <button type="button" class="compare-now-btn" data-text="<?php echo esc_attr($atts['text_compare']); ?>">
                            <?php echo esc_html($atts['text_compare']); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render comparison widget
     */
    private function render_comparison_widget($comparison_items, $atts) {
        $widget_class = 'parfume-comparison-widget';
        $widget_class .= ' position-' . esc_attr($atts['position']);
        ?>
        <div class="<?php echo esc_attr($widget_class); ?>" id="parfume-comparison-widget">
            <div class="widget-header">
                <h4 class="widget-title">
                    <?php _e('Compare Perfumes', 'parfume-reviews'); ?>
                    <?php if ($this->parse_bool($atts['show_count'])): ?>
                        <span class="comparison-count">(<?php echo count($comparison_items); ?>)</span>
                    <?php endif; ?>
                </h4>
                
                <div class="widget-actions">
                    <button type="button" class="toggle-widget-btn" title="<?php esc_attr_e('Toggle widget', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                    <button type="button" class="clear-all-btn" title="<?php esc_attr_e('Clear all', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            
            <div class="widget-content">
                <?php if (!empty($comparison_items)): ?>
                    <div class="comparison-items">
                        <?php 
                        $display_items = array_slice($comparison_items, 0, intval($atts['max_display']));
                        $remaining_count = max(0, count($comparison_items) - intval($atts['max_display']));
                        
                        foreach ($display_items as $item_id):
                            $perfume = get_post($item_id);
                            if (!$perfume) continue;
                        ?>
                            <div class="comparison-item" data-perfume-id="<?php echo esc_attr($item_id); ?>">
                                <?php if ($this->parse_bool($atts['show_items'])): ?>
                                    <div class="item-info">
                                        <?php if (has_post_thumbnail($item_id)): ?>
                                            <div class="item-image">
                                                <?php echo get_the_post_thumbnail($item_id, 'thumbnail'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="item-details">
                                            <h5 class="item-title"><?php echo esc_html($perfume->post_title); ?></h5>
                                            
                                            <?php 
                                            $brands = wp_get_post_terms($item_id, 'marki', array('fields' => 'names'));
                                            if (!empty($brands) && !is_wp_error($brands)): 
                                            ?>
                                                <span class="item-brand"><?php echo esc_html($brands[0]); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <button type="button" class="remove-item-btn" data-perfume-id="<?php echo esc_attr($item_id); ?>" title="<?php esc_attr_e('Remove from comparison', 'parfume-reviews'); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($remaining_count > 0): ?>
                            <div class="remaining-items">
                                <span class="remaining-count">
                                    <?php printf(__('and %d more...', 'parfume-reviews'), $remaining_count); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="widget-footer">
                        <?php if (count($comparison_items) >= 2): ?>
                            <button type="button" class="compare-all-btn">
                                <?php _e('Compare All', 'parfume-reviews'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="view-comparison-btn">
                            <?php _e('View Comparison', 'parfume-reviews'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="empty-comparison">
                        <p><?php _e('No perfumes added to comparison yet.', 'parfume-reviews'); ?></p>
                        <p class="help-text"><?php _e('Click the "Add to Compare" button on any perfume to start comparing.', 'parfume-reviews'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render comparison popup
     */
    private function render_comparison_popup($atts) {
        ?>
        <div id="parfume-comparison-popup" class="comparison-popup-overlay" style="display: none;">
            <div class="comparison-popup-container">
                <div class="popup-header">
                    <h3 class="popup-title"><?php echo esc_html($atts['modal_title']); ?></h3>
                    <button type="button" class="close-popup-btn" title="<?php esc_attr_e('Close', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                
                <div class="popup-content">
                    <div class="loading-message">
                        <p><?php _e('Loading comparison data...', 'parfume-reviews'); ?></p>
                        <div class="spinner"></div>
                    </div>
                    
                    <div class="comparison-content">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
                
                <div class="popup-footer">
                    <button type="button" class="close-popup-btn secondary">
                        <?php _e('Close', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="export-comparison-btn">
                        <?php _e('Export Comparison', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var popup = document.getElementById('parfume-comparison-popup');
            var triggers = document.querySelectorAll('[data-comparison-popup]');
            var closeBtns = popup.querySelectorAll('.close-popup-btn');
            
            // Show popup on trigger
            <?php if ($atts['trigger'] === 'auto'): ?>
                // Auto-show when comparison count reaches threshold
                var checkComparison = function() {
                    var comparisonData = localStorage.getItem('parfume_comparison') || '[]';
                    var items = JSON.parse(comparisonData);
                    
                    if (items.length >= <?php echo intval($atts['show_on_count']); ?>) {
                        showPopup();
                    }
                };
                
                setInterval(checkComparison, 2000);
            <?php endif; ?>
            
            // Manual trigger
            triggers.forEach(function(trigger) {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    showPopup();
                });
            });
            
            // Close popup
            closeBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    hidePopup();
                });
            });
            
            // Close on overlay click
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    hidePopup();
                }
            });
            
            function showPopup() {
                popup.style.display = 'flex';
                document.body.classList.add('comparison-popup-open');
                loadComparisonData();
            }
            
            function hidePopup() {
                popup.style.display = 'none';
                document.body.classList.remove('comparison-popup-open');
            }
            
            function loadComparisonData() {
                var contentArea = popup.querySelector('.comparison-content');
                var loadingArea = popup.querySelector('.loading-message');
                
                loadingArea.style.display = 'block';
                contentArea.innerHTML = '';
                
                // AJAX call to load comparison data
                var xhr = new XMLHttpRequest();
                xhr.open('POST', parfume_ajax.ajax_url);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    loadingArea.style.display = 'none';
                    
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            contentArea.innerHTML = response.data.html;
                        } else {
                            contentArea.innerHTML = '<p class="error">' + response.data + '</p>';
                        }
                    } else {
                        contentArea.innerHTML = '<p class="error"><?php esc_js(_e('Error loading comparison data.', 'parfume-reviews')); ?></p>';
                    }
                };
                
                xhr.send('action=get_comparison_data&nonce=' + parfume_ajax.nonce);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render similar comparison
     */
    private function render_similar_comparison($similar_perfumes, $atts) {
        ?>
        <div class="similar-comparison-section">
            <?php if (!empty($atts['title'])): ?>
                <h3 class="section-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <div class="similar-perfumes-grid">
                <?php foreach ($similar_perfumes as $perfume): ?>
                    <div class="similar-perfume-card">
                        <div class="perfume-info">
                            <?php if (has_post_thumbnail($perfume->ID)): ?>
                                <div class="perfume-image">
                                    <a href="<?php echo esc_url(get_permalink($perfume->ID)); ?>">
                                        <?php echo get_the_post_thumbnail($perfume->ID, 'thumbnail'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="perfume-details">
                                <h4 class="perfume-title">
                                    <a href="<?php echo esc_url(get_permalink($perfume->ID)); ?>">
                                        <?php echo esc_html($perfume->post_title); ?>
                                    </a>
                                </h4>
                                
                                <?php 
                                $brands = wp_get_post_terms($perfume->ID, 'marki', array('fields' => 'names'));
                                if (!empty($brands) && !is_wp_error($brands)): 
                                ?>
                                    <span class="perfume-brand"><?php echo esc_html($brands[0]); ?></span>
                                <?php endif; ?>
                                
                                <?php 
                                $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
                                if (!empty($rating)): 
                                ?>
                                    <div class="perfume-rating">
                                        <?php echo Helpers::get_rating_stars($rating); ?>
                                        <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($this->parse_bool($atts['show_button'])): ?>
                            <div class="perfume-actions">
                                <button type="button" class="add-to-comparison-btn" data-perfume-id="<?php echo esc_attr($perfume->ID); ?>">
                                    <?php _e('Add to Compare', 'parfume-reviews'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get comparison items from cookie/session
     */
    private function get_comparison_items() {
        if (isset($_COOKIE['parfume_comparison'])) {
            $items = json_decode(stripslashes($_COOKIE['parfume_comparison']), true);
            return is_array($items) ? array_map('intval', $items) : array();
        }
        
        return array();
    }
    
    /**
     * Get similar perfumes for comparison
     */
    private function get_similar_perfumes($post_id, $limit = 3) {
        // Get current perfume taxonomies
        $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'ids'));
        $notes = wp_get_post_terms($post_id, 'notes', array('fields' => 'ids'));
        $genders = wp_get_post_terms($post_id, 'gender', array('fields' => 'ids'));
        
        if (is_wp_error($brands)) $brands = array();
        if (is_wp_error($notes)) $notes = array();
        if (is_wp_error($genders)) $genders = array();
        
        $tax_query = array('relation' => 'OR');
        
        if (!empty($brands)) {
            $tax_query[] = array(
                'taxonomy' => 'marki',
                'field' => 'term_id',
                'terms' => $brands,
            );
        }
        
        if (!empty($notes)) {
            $tax_query[] = array(
                'taxonomy' => 'notes',
                'field' => 'term_id',
                'terms' => $notes,
            );
        }
        
        if (!empty($genders)) {
            $tax_query[] = array(
                'taxonomy' => 'gender',
                'field' => 'term_id',
                'terms' => $genders,
            );
        }
        
        if (count($tax_query) === 1) {
            return array(); // No taxonomies found
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
            'tax_query' => $tax_query,
            'orderby' => 'rand',
        );
        
        return get_posts($args);
    }
    
    /**
     * AJAX: Add to comparison
     */
    public function ajax_add_to_comparison() {
        check_ajax_referer('parfume_ajax_nonce', 'nonce');
        
        $perfume_id = intval($_POST['perfume_id']);
        
        if (!$perfume_id || get_post_type($perfume_id) !== 'parfume') {
            wp_send_json_error(__('Invalid perfume ID.', 'parfume-reviews'));
        }
        
        $comparison_items = $this->get_comparison_items();
        
        if (!in_array($perfume_id, $comparison_items)) {
            $comparison_items[] = $perfume_id;
            
            // Limit to 4 items max
            if (count($comparison_items) > 4) {
                array_shift($comparison_items);
            }
            
            setcookie('parfume_comparison', json_encode($comparison_items), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        }
        
        wp_send_json_success(array(
            'message' => __('Added to comparison.', 'parfume-reviews'),
            'count' => count($comparison_items),
            'items' => $comparison_items,
        ));
    }
    
    /**
     * AJAX: Remove from comparison
     */
    public function ajax_remove_from_comparison() {
        check_ajax_referer('parfume_ajax_nonce', 'nonce');
        
        $perfume_id = intval($_POST['perfume_id']);
        $comparison_items = $this->get_comparison_items();
        
        $key = array_search($perfume_id, $comparison_items);
        if ($key !== false) {
            unset($comparison_items[$key]);
            $comparison_items = array_values($comparison_items);
            
            setcookie('parfume_comparison', json_encode($comparison_items), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        }
        
        wp_send_json_success(array(
            'message' => __('Removed from comparison.', 'parfume-reviews'),
            'count' => count($comparison_items),
            'items' => $comparison_items,
        ));
    }
    
    /**
     * AJAX: Get comparison data
     */
    public function ajax_get_comparison_data() {
        check_ajax_referer('parfume_ajax_nonce', 'nonce');
        
        $comparison_items = $this->get_comparison_items();
        
        if (empty($comparison_items)) {
            wp_send_json_error(__('No perfumes in comparison.', 'parfume-reviews'));
        }
        
        ob_start();
        echo do_shortcode('[parfume_comparison perfumes="' . implode(',', $comparison_items) . '"]');
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'count' => count($comparison_items),
        ));
    }
    
    /**
     * AJAX: Clear comparison
     */
    public function ajax_clear_comparison() {
        check_ajax_referer('parfume_ajax_nonce', 'nonce');
        
        setcookie('parfume_comparison', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        
        wp_send_json_success(array(
            'message' => __('Comparison cleared.', 'parfume-reviews'),
            'count' => 0,
        ));
    }
}

// Initialize the class
new Comparison();