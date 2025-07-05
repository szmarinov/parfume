<?php
/**
 * Meta Boxes for Parfume Reviews Plugin
 *
 * @package Parfume_Reviews
 * @since 1.0.0
 */

namespace Parfume_Reviews\PostTypes;

use Parfume_Reviews\Utils\Meta_Box_Base; // Fixed namespace
use Parfume_Reviews\Utils\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta Boxes Class
 * Handles all meta boxes for parfume post type
 */
class Meta_Boxes extends Meta_Box_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Don't call parent constructor as we handle multiple meta boxes
        add_action('add_meta_boxes', array($this, 'add_all_meta_boxes'));
        add_action('save_post', array($this, 'save_all_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        $this->init();
    }
    
    /**
     * Initialize meta boxes
     */
    protected function init() {
        // Meta box configurations
        $this->meta_boxes = array(
            'parfume_details' => array(
                'title' => __('Детайли за парфюма', 'parfume-reviews'),
                'context' => 'normal',
                'priority' => 'high',
                'fields' => array(
                    '_parfume_gender' => array(
                        'type' => 'text',
                        'label' => __('Пол', 'parfume-reviews'),
                        'description' => __('За кого е парфюмът (мъже, жени, унисекс)', 'parfume-reviews')
                    ),
                    '_parfume_release_year' => array(
                        'type' => 'number',
                        'label' => __('Година на издаване', 'parfume-reviews'),
                        'min' => 1900,
                        'max' => date('Y') + 5
                    ),
                    '_parfume_longevity' => array(
                        'type' => 'text',
                        'label' => __('Издръжливост', 'parfume-reviews'),
                        'description' => __('Например: 6-8 часа', 'parfume-reviews')
                    ),
                    '_parfume_sillage' => array(
                        'type' => 'text',
                        'label' => __('Силаж', 'parfume-reviews'),
                        'description' => __('Например: Умерен', 'parfume-reviews')
                    ),
                    '_parfume_bottle_size' => array(
                        'type' => 'text',
                        'label' => __('Размер на шишето', 'parfume-reviews'),
                        'description' => __('Например: 100ml', 'parfume-reviews')
                    )
                )
            ),
            'parfume_rating' => array(
                'title' => __('Рейтинг', 'parfume-reviews'),
                'context' => 'side',
                'priority' => 'default',
                'fields' => array(
                    '_parfume_rating' => array(
                        'type' => 'number',
                        'label' => __('Рейтинг (0-5)', 'parfume-reviews'),
                        'min' => 0,
                        'max' => 5,
                        'step' => 0.1
                    )
                )
            ),
            'parfume_aroma_chart' => array(
                'title' => __('Графика на аромата', 'parfume-reviews'),
                'context' => 'side',
                'priority' => 'default',
                'fields' => array(
                    '_parfume_freshness' => array(
                        'type' => 'range',
                        'label' => __('Свежест', 'parfume-reviews'),
                        'min' => 0,
                        'max' => 10,
                        'step' => 1
                    ),
                    '_parfume_sweetness' => array(
                        'type' => 'range',
                        'label' => __('Сладост', 'parfume-reviews'),
                        'min' => 0,
                        'max' => 10,
                        'step' => 1
                    ),
                    '_parfume_intensity' => array(
                        'type' => 'range',
                        'label' => __('Интензивност', 'parfume-reviews'),
                        'min' => 0,
                        'max' => 10,
                        'step' => 1
                    ),
                    '_parfume_warmth' => array(
                        'type' => 'range',
                        'label' => __('Топлота', 'parfume-reviews'),
                        'min' => 0,
                        'max' => 10,
                        'step' => 1
                    )
                )
            ),
            'parfume_pros_cons' => array(
                'title' => __('Предимства и недостатъци', 'parfume-reviews'),
                'context' => 'normal',
                'priority' => 'default',
                'fields' => array(
                    '_parfume_pros' => array(
                        'type' => 'textarea',
                        'label' => __('Предимства', 'parfume-reviews'),
                        'description' => __('Въведете всяко предимство на нов ред', 'parfume-reviews'),
                        'rows' => 5
                    ),
                    '_parfume_cons' => array(
                        'type' => 'textarea',
                        'label' => __('Недостатъци', 'parfume-reviews'),
                        'description' => __('Въведете всеки недостатък на нов ред', 'parfume-reviews'),
                        'rows' => 5
                    )
                )
            ),
            'parfume_stores' => array(
                'title' => __('Магазини', 'parfume-reviews'),
                'context' => 'normal',
                'priority' => 'default',
                'fields' => array(
                    '_parfume_stores' => array(
                        'type' => 'stores_repeater',
                        'label' => __('Магазини', 'parfume-reviews'),
                        'description' => __('Добавете магазини където може да се купи парфюмът', 'parfume-reviews')
                    )
                )
            )
        );
    }
    
    /**
     * Add all meta boxes
     */
    public function add_all_meta_boxes() {
        foreach ($this->meta_boxes as $meta_box_id => $meta_box_config) {
            add_meta_box(
                $meta_box_id,
                $meta_box_config['title'],
                array($this, 'render_meta_box_' . str_replace('parfume_', '', $meta_box_id)),
                'parfume',
                $meta_box_config['context'],
                $meta_box_config['priority']
            );
        }
    }
    
    /**
     * Render details meta box
     *
     * @param \WP_Post $post Current post
     */
    public function render_meta_box_details($post) {
        $this->render_meta_box_with_fields($post, 'parfume_details');
    }
    
    /**
     * Render rating meta box
     *
     * @param \WP_Post $post Current post
     */
    public function render_meta_box_rating($post) {
        wp_nonce_field('parfume_rating_nonce', 'parfume_rating_nonce');
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $rating = !empty($rating) ? floatval($rating) : 0;
        
        echo '<p>';
        echo '<label for="parfume_rating">' . __('Рейтинг (0-5):', 'parfume-reviews') . '</label><br>';
        echo '<input type="number" id="parfume_rating" name="_parfume_rating" value="' . esc_attr($rating) . '" min="0" max="5" step="0.1" class="small-text" />';
        echo '</p>';
        
        if ($rating > 0) {
            echo '<div class="rating-preview">';
            echo '<strong>' . __('Преглед:', 'parfume-reviews') . '</strong><br>';
            echo Helpers::get_rating_stars($rating);
            echo ' <span>(' . number_format($rating, 1) . '/5)</span>';
            echo '</div>';
        }
    }
    
    /**
     * Render aroma chart meta box
     *
     * @param \WP_Post $post Current post
     */
    public function render_meta_box_aroma_chart($post) {
        wp_nonce_field('parfume_aroma_chart_nonce', 'parfume_aroma_chart_nonce');
        
        $fields = array(
            'freshness' => __('Свежест', 'parfume-reviews'),
            'sweetness' => __('Сладост', 'parfume-reviews'),
            'intensity' => __('Интензивност', 'parfume-reviews'),
            'warmth' => __('Топлота', 'parfume-reviews'),
        );
        
        echo '<table class="form-table"><tbody>';
        foreach ($fields as $field => $label) {
            $value = get_post_meta($post->ID, '_parfume_' . $field, true);
            echo '<tr>';
            echo '<th scope="row"><label for="parfume_' . $field . '">' . $label . '</label></th>';
            echo '<td>';
            echo '<input type="range" id="parfume_' . $field . '" name="_parfume_' . $field . '" value="' . esc_attr($value) . '" min="0" max="10" step="1" class="range-input" />';
            echo '<span class="range-value">' . esc_attr($value) . '</span>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const ranges = document.querySelectorAll(".range-input");
            ranges.forEach(range => {
                const valueSpan = range.nextElementSibling;
                range.addEventListener("input", function() {
                    valueSpan.textContent = this.value;
                });
            });
        });
        </script>';
    }
    
    /**
     * Render pros cons meta box
     *
     * @param \WP_Post $post Current post
     */
    public function render_meta_box_pros_cons($post) {
        $this->render_meta_box_with_fields($post, 'parfume_pros_cons');
    }
    
    /**
     * Render stores meta box
     *
     * @param \WP_Post $post Current post
     */
    public function render_meta_box_stores($post) {
        wp_nonce_field('parfume_stores_nonce', 'parfume_stores_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        echo '<div id="parfume-stores-container">';
        
        if (!empty($stores)) {
            foreach ($stores as $index => $store) {
                $this->render_store_fields($index, $store);
            }
        }
        
        echo '</div>';
        
        echo '<p>';
        echo '<button type="button" id="add-store" class="button">' . __('Добави магазин', 'parfume-reviews') . '</button>';
        echo '</p>';
        
        // JavaScript template for new stores
        echo '<script type="text/template" id="store-template">';
        $this->render_store_fields('{{INDEX}}', array());
        echo '</script>';
        
        $this->render_stores_javascript();
    }
    
    /**
     * Render store fields
     *
     * @param int|string $index Store index
     * @param array $store Store data
     */
    private function render_store_fields($index, $store = array()) {
        $store = wp_parse_args($store, array(
            'name' => '',
            'logo' => '',
            'url' => '',
            'affiliate_url' => '',
            'affiliate_class' => '',
            'affiliate_rel' => 'nofollow',
            'affiliate_target' => '_blank',
            'affiliate_anchor' => '',
            'promo_code' => '',
            'promo_text' => '',
            'price' => '',
            'size' => '',
            'availability' => '',
            'shipping_cost' => '',
        ));
        
        echo '<div class="store-item">';
        echo '<div class="store-header">';
        echo __('Магазин', 'parfume-reviews') . ' #' . ($index + 1);
        if ($index !== '{{INDEX}}') {
            echo '<a href="#" class="remove-store">' . __('Премахни', 'parfume-reviews') . '</a>';
        }
        echo '</div>';
        
        echo '<table class="form-table"><tbody>';
        
        // Store fields
        $fields = array(
            'name' => __('Име на магазина', 'parfume-reviews'),
            'logo' => __('Лого URL', 'parfume-reviews'),
            'url' => __('URL на продукта', 'parfume-reviews'),
            'affiliate_url' => __('Affiliate URL', 'parfume-reviews'),
            'affiliate_anchor' => __('Текст на affiliate линка', 'parfume-reviews'),
            'promo_code' => __('Промо код', 'parfume-reviews'),
            'promo_text' => __('Текст за промо кода', 'parfume-reviews'),
            'price' => __('Цена', 'parfume-reviews'),
            'size' => __('Размер', 'parfume-reviews'),
            'availability' => __('Наличност', 'parfume-reviews'),
            'shipping_cost' => __('Цена на доставка', 'parfume-reviews'),
        );
        
        foreach ($fields as $field_key => $field_label) {
            echo '<tr>';
            echo '<th scope="row"><label for="store_' . $index . '_' . $field_key . '">' . $field_label . '</label></th>';
            echo '<td><input type="text" id="store_' . $index . '_' . $field_key . '" name="_parfume_stores[' . $index . '][' . $field_key . ']" value="' . esc_attr($store[$field_key]) . '" class="regular-text" /></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    /**
     * Render JavaScript for stores management
     */
    private function render_stores_javascript() {
        ?>
        <style>
        .store-item { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 10px; 
            background: #f9f9f9; 
            border-radius: 5px;
        }
        .store-item .store-header { 
            font-weight: bold; 
            margin-bottom: 10px; 
            position: relative;
        }
        .store-item .form-table th { 
            width: 150px; 
        }
        .remove-store { 
            color: #a00; 
            text-decoration: none; 
            position: absolute;
            right: 0;
            top: 0;
        }
        .rating-preview .star { 
            color: #ddd; 
            font-size: 18px; 
        }
        .rating-preview .star.filled { 
            color: #ffb900; 
        }
        </style>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            let storeIndex = <?php echo count(get_post_meta(get_the_ID(), '_parfume_stores', true) ?: array()); ?>;
            
            document.getElementById("add-store").addEventListener("click", function() {
                const template = document.getElementById("store-template").innerHTML;
                const html = template.replace(/\{\{INDEX\}\}/g, storeIndex);
                const container = document.getElementById("parfume-stores-container");
                container.insertAdjacentHTML("beforeend", html);
                storeIndex++;
            });
            
            document.addEventListener("click", function(e) {
                if (e.target.classList.contains("remove-store")) {
                    e.preventDefault();
                    if (confirm("<?php echo esc_js(__('Сигурни ли сте, че искате да премахнете този магазин?', 'parfume-reviews')); ?>")) {
                        e.target.closest(".store-item").remove();
                    }
                }
            });
            
            // Rating preview update
            const ratingInput = document.getElementById("parfume_rating");
            if (ratingInput) {
                ratingInput.addEventListener("input", function() {
                    const rating = parseFloat(this.value) || 0;
                    const preview = document.querySelector(".rating-preview");
                    if (preview) {
                        const stars = preview.querySelectorAll(".star");
                        stars.forEach((star, index) => {
                            star.classList.toggle("filled", index < Math.round(rating));
                        });
                        const ratingText = preview.querySelector("span:last-child");
                        if (ratingText) {
                            ratingText.textContent = "(" + rating.toFixed(1) + "/5)";
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render meta box with fields
     *
     * @param \WP_Post $post Current post
     * @param string $meta_box_key Meta box key
     */
    private function render_meta_box_with_fields($post, $meta_box_key) {
        $nonce_action = $meta_box_key . '_nonce';
        wp_nonce_field($nonce_action, $nonce_action);
        
        $fields = $this->meta_boxes[$meta_box_key]['fields'];
        
        echo '<table class="form-table"><tbody>';
        foreach ($fields as $field_key => $field_config) {
            $value = get_post_meta($post->ID, $field_key, true);
            echo $this->render_field($field_key, $field_config, $value);
        }
        echo '</tbody></table>';
    }
    
    /**
     * Render field (override parent method to add range support)
     *
     * @param string $field_key Field key
     * @param array $field_config Field configuration
     * @param mixed $value Current value
     * @return string Field HTML
     */
    protected function render_field($field_key, $field_config, $value = '') {
        if ($field_config['type'] === 'range') {
            $label = $field_config['label'] ?? ucfirst(str_replace('_', ' ', $field_key));
            $min = $field_config['min'] ?? 0;
            $max = $field_config['max'] ?? 10;
            $step = $field_config['step'] ?? 1;
            
            $html = '<tr>';
            $html .= '<th scope="row"><label for="' . esc_attr($field_key) . '">' . esc_html($label) . '</label></th>';
            $html .= '<td>';
            $html .= '<input type="range" id="' . esc_attr($field_key) . '" name="' . esc_attr($field_key) . '" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" class="range-input" />';
            $html .= '<span class="range-value">' . esc_attr($value) . '</span>';
            $html .= '</td>';
            $html .= '</tr>';
            
            return $html;
        }
        
        return parent::render_field($field_key, $field_config, $value);
    }
    
    /**
     * Save all meta boxes
     *
     * @param int $post_id Post ID
     */
    public function save_all_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the post type
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save details
        if (isset($_POST['parfume_details_nonce']) && wp_verify_nonce($_POST['parfume_details_nonce'], 'parfume_details_nonce')) {
            $this->save_meta_box_fields($post_id, 'parfume_details');
        }
        
        // Save rating
        if (isset($_POST['parfume_rating_nonce']) && wp_verify_nonce($_POST['parfume_rating_nonce'], 'parfume_rating_nonce')) {
            if (isset($_POST['_parfume_rating'])) {
                $rating = floatval($_POST['_parfume_rating']);
                $rating = max(0, min(5, $rating)); // Ensure rating is between 0 and 5
                update_post_meta($post_id, '_parfume_rating', $rating);
            }
        }
        
        // Save aroma chart
        if (isset($_POST['parfume_aroma_chart_nonce']) && wp_verify_nonce($_POST['parfume_aroma_chart_nonce'], 'parfume_aroma_chart_nonce')) {
            $chart_fields = array('_parfume_freshness', '_parfume_sweetness', '_parfume_intensity', '_parfume_warmth');
            
            foreach ($chart_fields as $field) {
                if (isset($_POST[$field])) {
                    $value = intval($_POST[$field]);
                    $value = max(0, min(10, $value)); // Ensure value is between 0 and 10
                    update_post_meta($post_id, $field, $value);
                }
            }
        }
        
        // Save pros and cons
        if (isset($_POST['parfume_pros_cons_nonce']) && wp_verify_nonce($_POST['parfume_pros_cons_nonce'], 'parfume_pros_cons_nonce')) {
            $this->save_meta_box_fields($post_id, 'parfume_pros_cons');
        }
        
        // Save stores
        if (isset($_POST['parfume_stores_nonce']) && wp_verify_nonce($_POST['parfume_stores_nonce'], 'parfume_stores_nonce')) {
            if (isset($_POST['_parfume_stores']) && is_array($_POST['_parfume_stores'])) {
                $stores = array();
                
                foreach ($_POST['_parfume_stores'] as $store_data) {
                    // Skip empty stores
                    if (empty($store_data['name'])) continue;
                    
                    $store = array();
                    $fields = array('name', 'logo', 'url', 'affiliate_url', 'affiliate_class', 'affiliate_anchor', 'promo_code', 'promo_text', 'price', 'size', 'availability', 'shipping_cost');
                    
                    foreach ($fields as $field) {
                        if (in_array($field, array('logo', 'url', 'affiliate_url'))) {
                            $store[$field] = !empty($store_data[$field]) ? esc_url_raw($store_data[$field]) : '';
                        } else {
                            $store[$field] = isset($store_data[$field]) ? sanitize_text_field($store_data[$field]) : '';
                        }
                    }
                    
                    $store['affiliate_rel'] = 'nofollow';
                    $store['affiliate_target'] = '_blank';
                    $store['last_updated'] = current_time('mysql');
                    $stores[] = $store;
                }
                
                update_post_meta($post_id, '_parfume_stores', $stores);
            } else {
                delete_post_meta($post_id, '_parfume_stores');
            }
        }
    }
    
    /**
     * Save meta box fields
     *
     * @param int $post_id Post ID
     * @param string $meta_box_key Meta box key
     */
    private function save_meta_box_fields($post_id, $meta_box_key) {
        $fields = $this->meta_boxes[$meta_box_key]['fields'];
        
        foreach ($fields as $field_key => $field_config) {
            if (isset($_POST[$field_key])) {
                $value = $this->sanitize_field_value($_POST[$field_key], $field_config);
                update_post_meta($post_id, $field_key, $value);
            }
        }
    }
    
    /**
     * Required abstract method from parent
     */
    public function render_meta_box($post) {
        // Not used - we override with specific methods
    }
    
    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page
     */
    public function enqueue_admin_scripts($hook) {
        if (in_array($hook, array('post-new.php', 'post.php'))) {
            global $post_type;
            if ($post_type === 'parfume') {
                wp_enqueue_media();
                
                wp_enqueue_style(
                    'parfume-reviews-admin',
                    PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/dashboard.css',
                    array(),
                    PARFUME_REVIEWS_VERSION
                );
            }
        }
    }
}