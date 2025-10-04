<?php
/**
 * Meta Boxes
 * 
 * Handles meta boxes for the Parfume post type
 * 
 * @package Parfume_Reviews
 * @subpackage PostTypes\Parfume
 * @since 2.0.0
 */

namespace Parfume_Reviews\PostTypes\Parfume;

/**
 * MetaBoxes Class
 * 
 * Manages all meta boxes for parfume posts
 */
class MetaBoxes {
    
    /**
     * Post type configuration
     * 
     * @var array
     */
    private $config;
    
    /**
     * Meta boxes configuration
     * 
     * @var array
     */
    private $meta_boxes;
    
    /**
     * Constructor
     * 
     * @param array $config Post type configuration
     */
    public function __construct($config) {
        $this->config = $config;
        $this->meta_boxes = isset($config['meta_boxes']) ? $config['meta_boxes'] : [];
    }
    
    /**
     * Register all meta boxes
     */
    public function register() {
        foreach ($this->meta_boxes as $meta_box_config) {
            add_meta_box(
                $meta_box_config['id'],
                $meta_box_config['title'],
                [$this, 'render_meta_box'],
                'parfume',
                $meta_box_config['context'],
                $meta_box_config['priority'],
                $meta_box_config
            );
        }
    }
    
    /**
     * Render meta box
     * 
     * @param \WP_Post $post Current post object
     * @param array $args Meta box arguments
     */
    public function render_meta_box($post, $args) {
        $config = $args['args'];
        
        // Add nonce field for security
        wp_nonce_field('parfume_meta_box_' . $config['id'], 'parfume_meta_box_nonce_' . $config['id']);
        
        // Render fields
        if (isset($config['fields'])) {
            echo '<div class="parfume-meta-box-fields">';
            
            foreach ($config['fields'] as $field_key => $field_config) {
                $this->render_field($post->ID, $field_key, $field_config);
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Render individual field
     * 
     * @param int $post_id Post ID
     * @param string $field_key Field key
     * @param array $field_config Field configuration
     */
    private function render_field($post_id, $field_key, $field_config) {
        $value = get_post_meta($post_id, '_parfume_' . $field_key, true);
        $field_type = $field_config['type'];
        
        echo '<div class="parfume-field-wrapper parfume-field-' . esc_attr($field_type) . '">';
        
        // Field label
        if (isset($field_config['label'])) {
            echo '<label for="parfume_' . esc_attr($field_key) . '">';
            echo '<strong>' . esc_html($field_config['label']) . '</strong>';
            echo '</label>';
        }
        
        // Render field based on type
        switch ($field_type) {
            case 'text':
                $this->render_text_field($field_key, $value, $field_config);
                break;
            
            case 'url':
                $this->render_url_field($field_key, $value, $field_config);
                break;
            
            case 'number':
                $this->render_number_field($field_key, $value, $field_config);
                break;
            
            case 'textarea':
                $this->render_textarea_field($field_key, $value, $field_config);
                break;
            
            case 'select':
                $this->render_select_field($field_key, $value, $field_config);
                break;
            
            case 'checkbox':
                $this->render_checkbox_field($field_key, $value, $field_config);
                break;
            
            case 'repeater':
                $this->render_repeater_field($field_key, $value, $field_config);
                break;
            
            case 'gallery':
                $this->render_gallery_field($field_key, $value, $field_config);
                break;
        }
        
        // Field description
        if (isset($field_config['description'])) {
            echo '<p class="description">' . esc_html($field_config['description']) . '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render text field
     */
    private function render_text_field($key, $value, $config) {
        $placeholder = isset($config['placeholder']) ? $config['placeholder'] : '';
        
        echo '<input type="text" ';
        echo 'id="parfume_' . esc_attr($key) . '" ';
        echo 'name="parfume_' . esc_attr($key) . '" ';
        echo 'value="' . esc_attr($value) . '" ';
        echo 'placeholder="' . esc_attr($placeholder) . '" ';
        echo 'class="widefat" />';
    }
    
    /**
     * Render URL field
     */
    private function render_url_field($key, $value, $config) {
        $placeholder = isset($config['placeholder']) ? $config['placeholder'] : 'https://';
        
        echo '<input type="url" ';
        echo 'id="parfume_' . esc_attr($key) . '" ';
        echo 'name="parfume_' . esc_attr($key) . '" ';
        echo 'value="' . esc_url($value) . '" ';
        echo 'placeholder="' . esc_attr($placeholder) . '" ';
        echo 'class="widefat" />';
    }
    
    /**
     * Render number field
     */
    private function render_number_field($key, $value, $config) {
        $min = isset($config['min']) ? $config['min'] : '';
        $max = isset($config['max']) ? $config['max'] : '';
        $step = isset($config['step']) ? $config['step'] : '1';
        
        echo '<input type="number" ';
        echo 'id="parfume_' . esc_attr($key) . '" ';
        echo 'name="parfume_' . esc_attr($key) . '" ';
        echo 'value="' . esc_attr($value) . '" ';
        
        if ($min !== '') {
            echo 'min="' . esc_attr($min) . '" ';
        }
        if ($max !== '') {
            echo 'max="' . esc_attr($max) . '" ';
        }
        echo 'step="' . esc_attr($step) . '" ';
        echo 'class="widefat" />';
    }
    
    /**
     * Render textarea field
     */
    private function render_textarea_field($key, $value, $config) {
        $rows = isset($config['rows']) ? $config['rows'] : 5;
        
        echo '<textarea ';
        echo 'id="parfume_' . esc_attr($key) . '" ';
        echo 'name="parfume_' . esc_attr($key) . '" ';
        echo 'rows="' . esc_attr($rows) . '" ';
        echo 'class="widefat">';
        echo esc_textarea($value);
        echo '</textarea>';
    }
    
    /**
     * Render select field
     */
    private function render_select_field($key, $value, $config) {
        $options = isset($config['options']) ? $config['options'] : [];
        
        echo '<select ';
        echo 'id="parfume_' . esc_attr($key) . '" ';
        echo 'name="parfume_' . esc_attr($key) . '" ';
        echo 'class="widefat">';
        
        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ';
            selected($value, $option_value);
            echo '>' . esc_html($option_label) . '</option>';
        }
        
        echo '</select>';
    }
    
    /**
     * Render checkbox field
     */
    private function render_checkbox_field($key, $value, $config) {
        echo '<label>';
        echo '<input type="checkbox" ';
        echo 'id="parfume_' . esc_attr($key) . '" ';
        echo 'name="parfume_' . esc_attr($key) . '" ';
        echo 'value="1" ';
        checked($value, '1');
        echo '/> ';
        
        if (isset($config['checkbox_label'])) {
            echo esc_html($config['checkbox_label']);
        }
        
        echo '</label>';
    }
    
    /**
     * Render repeater field
     */
    private function render_repeater_field($key, $value, $config) {
        $values = is_array($value) ? $value : [];
        $fields = isset($config['fields']) ? $config['fields'] : [];
        
        echo '<div class="parfume-repeater-field" data-field-name="parfume_' . esc_attr($key) . '">';
        echo '<div class="repeater-items">';
        
        if (!empty($values)) {
            foreach ($values as $index => $item_values) {
                $this->render_repeater_item($key, $index, $item_values, $fields);
            }
        }
        
        echo '</div>';
        
        // Add button
        echo '<button type="button" class="button add-repeater-item" data-field-name="parfume_' . esc_attr($key) . '">';
        echo __('Добави', 'parfume-reviews');
        echo '</button>';
        
        // Template for new items
        echo '<script type="text/template" id="parfume-' . esc_attr($key) . '-template">';
        $this->render_repeater_item($key, '{{INDEX}}', [], $fields);
        echo '</script>';
        
        echo '</div>';
    }
    
    /**
     * Render repeater item
     */
    private function render_repeater_item($parent_key, $index, $values, $fields) {
        echo '<div class="repeater-item">';
        
        foreach ($fields as $field_key => $field_config) {
            $field_value = isset($values[$field_key]) ? $values[$field_key] : '';
            $field_name = 'parfume_' . $parent_key . '[' . $index . '][' . $field_key . ']';
            
            echo '<div class="repeater-field">';
            
            if (isset($field_config['label'])) {
                echo '<label>' . esc_html($field_config['label']) . '</label>';
            }
            
            // Render field based on type
            switch ($field_config['type']) {
                case 'text':
                    echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" class="widefat" />';
                    break;
                
                case 'url':
                    echo '<input type="url" name="' . esc_attr($field_name) . '" value="' . esc_url($field_value) . '" class="widefat" />';
                    break;
                
                case 'number':
                    $min = isset($field_config['min']) ? 'min="' . esc_attr($field_config['min']) . '"' : '';
                    $max = isset($field_config['max']) ? 'max="' . esc_attr($field_config['max']) . '"' : '';
                    $step = isset($field_config['step']) ? 'step="' . esc_attr($field_config['step']) . '"' : 'step="1"';
                    
                    echo '<input type="number" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" ' . $min . ' ' . $max . ' ' . $step . ' class="widefat" />';
                    break;
                
                case 'checkbox':
                    echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="1" ' . checked($field_value, '1', false) . ' />';
                    break;
            }
            
            echo '</div>';
        }
        
        // Remove button
        echo '<button type="button" class="button button-link-delete remove-repeater-item">';
        echo __('Премахни', 'parfume-reviews');
        echo '</button>';
        
        echo '</div>';
    }
    
    /**
     * Render gallery field
     */
    private function render_gallery_field($key, $value, $config) {
        $image_ids = is_array($value) ? $value : [];
        
        echo '<div class="parfume-gallery-field">';
        echo '<div class="gallery-images">';
        
        if (!empty($image_ids)) {
            foreach ($image_ids as $image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                if ($image_url) {
                    echo '<div class="gallery-image" data-id="' . esc_attr($image_id) . '">';
                    echo '<img src="' . esc_url($image_url) . '" />';
                    echo '<button type="button" class="remove-gallery-image">×</button>';
                    echo '<input type="hidden" name="parfume_' . esc_attr($key) . '[]" value="' . esc_attr($image_id) . '" />';
                    echo '</div>';
                }
            }
        }
        
        echo '</div>';
        
        // Add images button
        echo '<button type="button" class="button add-gallery-images" data-field-name="parfume_' . esc_attr($key) . '">';
        echo __('Добави снимки', 'parfume-reviews');
        echo '</button>';
        
        echo '</div>';
    }
    
    /**
     * Save meta boxes data
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function save($post_id, $post) {
        // Check if it's autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if ($post->post_type !== 'parfume') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save each meta box
        foreach ($this->meta_boxes as $meta_box_config) {
            // Verify nonce
            $nonce_key = 'parfume_meta_box_nonce_' . $meta_box_config['id'];
            $nonce_action = 'parfume_meta_box_' . $meta_box_config['id'];
            
            if (!isset($_POST[$nonce_key]) || !wp_verify_nonce($_POST[$nonce_key], $nonce_action)) {
                continue;
            }
            
            // Save fields
            if (isset($meta_box_config['fields'])) {
                foreach ($meta_box_config['fields'] as $field_key => $field_config) {
                    $this->save_field($post_id, $field_key, $field_config);
                }
            }
        }
    }
    
    /**
     * Save individual field
     * 
     * @param int $post_id Post ID
     * @param string $field_key Field key
     * @param array $field_config Field configuration
     */
    private function save_field($post_id, $field_key, $field_config) {
        $meta_key = '_parfume_' . $field_key;
        $post_key = 'parfume_' . $field_key;
        
        if (!isset($_POST[$post_key])) {
            delete_post_meta($post_id, $meta_key);
            return;
        }
        
        $value = $_POST[$post_key];
        
        // Sanitize based on field type
        switch ($field_config['type']) {
            case 'text':
                $value = sanitize_text_field($value);
                break;
            
            case 'url':
                $value = esc_url_raw($value);
                break;
            
            case 'number':
                $value = floatval($value);
                break;
            
            case 'textarea':
                $value = sanitize_textarea_field($value);
                break;
            
            case 'checkbox':
                $value = $value === '1' ? '1' : '0';
                break;
            
            case 'repeater':
                $value = $this->sanitize_repeater($value, $field_config);
                break;
            
            case 'gallery':
                $value = array_map('absint', (array) $value);
                break;
            
            default:
                $value = sanitize_text_field($value);
        }
        
        update_post_meta($post_id, $meta_key, $value);
    }
    
    /**
     * Sanitize repeater field data
     * 
     * @param array $data Repeater data
     * @param array $config Field configuration
     * @return array
     */
    private function sanitize_repeater($data, $config) {
        if (!is_array($data) || !isset($config['fields'])) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($data as $item) {
            $sanitized_item = [];
            
            foreach ($config['fields'] as $field_key => $field_config) {
                if (!isset($item[$field_key])) {
                    continue;
                }
                
                $value = $item[$field_key];
                
                // Sanitize based on type
                switch ($field_config['type']) {
                    case 'text':
                        $sanitized_item[$field_key] = sanitize_text_field($value);
                        break;
                    
                    case 'url':
                        $sanitized_item[$field_key] = esc_url_raw($value);
                        break;
                    
                    case 'number':
                        $sanitized_item[$field_key] = floatval($value);
                        break;
                    
                    case 'checkbox':
                        $sanitized_item[$field_key] = $value === '1' ? '1' : '0';
                        break;
                    
                    default:
                        $sanitized_item[$field_key] = sanitize_text_field($value);
                }
            }
            
            $sanitized[] = $sanitized_item;
        }
        
        return $sanitized;
    }
}