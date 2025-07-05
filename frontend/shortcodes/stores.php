<?php
/**
 * Stores Shortcode Class
 * 
 * @package Parfume_Reviews
 */

namespace Parfume_Reviews\Frontend\Shortcodes;

use Parfume_Reviews\Utils\Shortcode_Base;
use Parfume_Reviews\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Stores
 */
class Stores extends Shortcode_Base {
    
    /**
     * Shortcode tag
     */
    protected $tag = 'parfume_stores';
    
    /**
     * Default attributes
     */
    protected $defaults = array(
        'post_id' => 0,
        'layout' => 'grid',
        'show_logos' => true,
        'show_prices' => true,
        'show_promos' => true,
        'show_availability' => true,
        'show_shipping' => true,
        'sort_by' => 'price',
        'limit' => 0,
    );
    
    /**
     * Render shortcode content
     * 
     * @param array $atts Attributes
     * @param string $content Content (not used)
     * @return string
     */
    public function render($atts, $content = null) {
        $atts = $this->parse_attributes($atts);
        
        // Get post ID
        $post_id = $atts['post_id'] ? intval($atts['post_id']) : get_the_ID();
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '<p class="parfume-stores-error">' . __('Stores shortcode can only be used on parfume posts.', 'parfume-reviews') . '</p>';
        }
        
        // Get stores data
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        if (empty($stores)) {
            return '<p class="no-stores">' . __('No stores found for this parfume.', 'parfume-reviews') . '</p>';
        }
        
        // Sort stores
        $stores = $this->sort_stores($stores, $atts['sort_by']);
        
        // Limit stores if specified
        if ($atts['limit'] > 0) {
            $stores = array_slice($stores, 0, intval($atts['limit']));
        }
        
        // Enqueue assets
        $this->enqueue_assets();
        
        // Generate output
        ob_start();
        $this->render_stores($stores, $atts, $post_id);
        return ob_get_clean();
    }
    
    /**
     * Sort stores array
     * 
     * @param array $stores
     * @param string $sort_by
     * @return array
     */
    private function sort_stores($stores, $sort_by) {
        switch ($sort_by) {
            case 'price':
                usort($stores, function($a, $b) {
                    $price_a = $this->extract_price($a['price']);
                    $price_b = $this->extract_price($b['price']);
                    return $price_a <=> $price_b;
                });
                break;
                
            case 'name':
                usort($stores, function($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });
                break;
                
            case 'availability':
                usort($stores, function($a, $b) {
                    $avail_a = $this->is_available($a);
                    $avail_b = $this->is_available($b);
                    return $avail_b <=> $avail_a; // Available first
                });
                break;
        }
        
        return $stores;
    }
    
    /**
     * Extract numeric price from price string
     * 
     * @param string $price
     * @return float
     */
    private function extract_price($price) {
        if (empty($price)) return PHP_FLOAT_MAX;
        
        preg_match('/(\d+(?:[.,]\d+)?)/', $price, $matches);
        return !empty($matches[1]) ? floatval(str_replace(',', '.', $matches[1])) : PHP_FLOAT_MAX;
    }
    
    /**
     * Check if store has product available
     * 
     * @param array $store
     * @return bool
     */
    private function is_available($store) {
        $availability = strtolower($store['availability'] ?? '');
        return !empty($availability) && $availability !== 'няма наличност' && $availability !== 'unavailable';
    }
    
    /**
     * Render stores HTML
     * 
     * @param array $stores
     * @param array $atts
     * @param int $post_id
     */
    private function render_stores($stores, $atts, $post_id) {
        $layout_class = $atts['layout'] === 'list' ? 'stores-list' : 'stores-grid';
        ?>
        <div class="parfume-stores-container <?php echo esc_attr($layout_class); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
            <?php if ($atts['layout'] === 'grid'): ?>
                <div class="stores-header">
                    <h3 class="stores-title"><?php _e('Where to Buy', 'parfume-reviews'); ?></h3>
                    <div class="stores-controls">
                        <button class="price-refresh-btn" data-post-id="<?php echo esc_attr($post_id); ?>">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Update Prices', 'parfume-reviews'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="stores-wrapper">
                <?php foreach ($stores as $index => $store): ?>
                    <div class="store-item" data-store-index="<?php echo esc_attr($index); ?>">
                        <?php $this->render_single_store($store, $atts, $index); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($stores) > 1): ?>
                <div class="stores-footer">
                    <div class="price-comparison">
                        <?php $this->render_price_comparison($stores); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render single store
     * 
     * @param array $store
     * @param array $atts
     * @param int $index
     */
    private function render_single_store($store, $atts, $index) {
        $store = wp_parse_args($store, array(
            'name' => '',
            'logo' => '',
            'url' => '',
            'affiliate_url' => '',
            'price' => '',
            'size' => '',
            'availability' => '',
            'shipping_cost' => '',
            'promo_code' => '',
            'promo_text' => '',
            'last_updated' => ''
        ));
        
        $final_url = !empty($store['affiliate_url']) ? $store['affiliate_url'] : $store['url'];
        $is_available = $this->is_available($store);
        ?>
        <div class="store-card <?php echo $is_available ? 'available' : 'unavailable'; ?>">
            <?php if ($atts['show_logos'] && !empty($store['logo'])): ?>
                <div class="store-logo">
                    <img src="<?php echo esc_url($store['logo']); ?>" 
                         alt="<?php echo esc_attr($store['name']); ?>"
                         loading="lazy">
                </div>
            <?php endif; ?>
            
            <div class="store-info">
                <h4 class="store-name"><?php echo esc_html($store['name']); ?></h4>
                
                <?php if ($atts['show_prices'] && !empty($store['price'])): ?>
                    <div class="store-price">
                        <span class="price-amount"><?php echo esc_html($store['price']); ?></span>
                        <?php if (!empty($store['size'])): ?>
                            <span class="price-size"><?php echo esc_html($store['size']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_availability']): ?>
                    <div class="store-availability">
                        <span class="availability-status <?php echo $is_available ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php if ($is_available): ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php echo !empty($store['availability']) ? esc_html($store['availability']) : __('In Stock', 'parfume-reviews'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php echo !empty($store['availability']) ? esc_html($store['availability']) : __('Out of Stock', 'parfume-reviews'); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_shipping'] && !empty($store['shipping_cost'])): ?>
                    <div class="store-shipping">
                        <span class="dashicons dashicons-cart"></span>
                        <span class="shipping-cost"><?php echo esc_html($store['shipping_cost']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_promos'] && !empty($store['promo_code'])): ?>
                    <div class="store-promo">
                        <div class="promo-code" title="<?php _e('Click to copy', 'parfume-reviews'); ?>">
                            <?php if (!empty($store['promo_text'])): ?>
                                <span class="promo-text"><?php echo esc_html($store['promo_text']); ?></span>
                            <?php endif; ?>
                            <code class="promo-code-value" data-code="<?php echo esc_attr($store['promo_code']); ?>">
                                <?php echo esc_html($store['promo_code']); ?>
                            </code>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="store-actions">
                <?php if (!empty($final_url)): ?>
                    <a href="<?php echo esc_url($final_url); ?>" 
                       class="store-link <?php echo $is_available ? 'btn-primary' : 'btn-secondary'; ?>"
                       target="_blank" 
                       rel="nofollow noopener"
                       data-store="<?php echo esc_attr($store['name']); ?>">
                        <?php if ($is_available): ?>
                            <span class="dashicons dashicons-cart"></span>
                            <?php _e('Buy Now', 'parfume-reviews'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('View Product', 'parfume-reviews'); ?>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($store['last_updated'])): ?>
                    <div class="last-updated">
                        <small><?php printf(__('Updated: %s', 'parfume-reviews'), 
                            human_time_diff(strtotime($store['last_updated']))); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render price comparison
     * 
     * @param array $stores
     */
    private function render_price_comparison($stores) {
        $prices = array();
        foreach ($stores as $store) {
            if (!empty($store['price'])) {
                $numeric_price = $this->extract_price($store['price']);
                if ($numeric_price < PHP_FLOAT_MAX) {
                    $prices[] = $numeric_price;
                }
            }
        }
        
        if (count($prices) < 2) return;
        
        $min_price = min($prices);
        $max_price = max($prices);
        $savings = $max_price - $min_price;
        ?>
        <div class="price-comparison-info">
            <div class="comparison-stat">
                <span class="stat-label"><?php _e('Best Price:', 'parfume-reviews'); ?></span>
                <span class="stat-value"><?php echo number_format($min_price, 2); ?> лв.</span>
            </div>
            <?php if ($savings > 0): ?>
                <div class="comparison-stat">
                    <span class="stat-label"><?php _e('You Save:', 'parfume-reviews'); ?></span>
                    <span class="stat-value savings"><?php echo number_format($savings, 2); ?> лв.</span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Enqueue assets
     */
    private function enqueue_assets() {
        wp_enqueue_script(
            'parfume-stores',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/stores.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-stores', 'parfumeStores', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_stores_nonce'),
            'strings' => array(
                'updating' => __('Updating prices...', 'parfume-reviews'),
                'updated' => __('Prices updated!', 'parfume-reviews'),
                'error' => __('Error updating prices', 'parfume-reviews'),
                'copied' => __('Promo code copied!', 'parfume-reviews'),
                'copy_failed' => __('Failed to copy promo code', 'parfume-reviews'),
            )
        ));
        
        wp_enqueue_style(
            'parfume-stores',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/stores.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
    }
    
    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_parfume_update_prices', array($this, 'ajax_update_prices'));
        add_action('wp_ajax_nopriv_parfume_update_prices', array($this, 'ajax_update_prices'));
    }
    
    /**
     * AJAX handler for updating prices
     */
    public function ajax_update_prices() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            wp_send_json_error(__('Invalid post ID', 'parfume-reviews'));
        }
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        if (empty($stores) || !is_array($stores)) {
            wp_send_json_error(__('No stores found', 'parfume-reviews'));
        }
        
        $updated_count = 0;
        foreach ($stores as &$store) {
            if (!empty($store['url'])) {
                $new_price = $this->fetch_price_from_url($store['url']);
                if ($new_price) {
                    $store['price'] = $new_price;
                    $store['last_updated'] = current_time('mysql');
                    $updated_count++;
                }
            }
        }
        
        if ($updated_count > 0) {
            update_post_meta($post_id, '_parfume_stores', $stores);
            wp_send_json_success(array(
                'updated' => $updated_count,
                'message' => sprintf(__('%d prices updated successfully', 'parfume-reviews'), $updated_count)
            ));
        } else {
            wp_send_json_error(__('No prices could be updated', 'parfume-reviews'));
        }
    }
    
    /**
     * Fetch price from URL (basic implementation)
     * 
     * @param string $url
     * @return string|false
     */
    private function fetch_price_from_url($url) {
        // Basic price fetching - would need enhancement for specific sites
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'user-agent' => 'Mozilla/5.0 (compatible; PriceBot/1.0)'
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Try different price patterns
        $patterns = array(
            '/(\d+[.,]\d+)\s*лв/i',
            '/price["\']?\s*:\s*["\']?(\d+[.,]\d+)/i',
            '/(\d+[.,]\d+)\s*BGN/i',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                return $matches[1] . ' лв.';
            }
        }
        
        return false;
    }
}