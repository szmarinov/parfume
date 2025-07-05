<?php
/**
 * Display Settings
 * 
 * @package ParfumeReviews
 * @subpackage Admin\Settings
 */

namespace ParfumeReviews\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Display {
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register display settings
     */
    public function register_settings() {
        // Archive Settings
        add_settings_section(
            'parfume_reviews_archive_section',
            __('Archive Display Settings', 'parfume-reviews'),
            array($this, 'render_archive_section'),
            'parfume-reviews-settings'
        );
        
        // Card Settings
        add_settings_section(
            'parfume_reviews_card_section',
            __('Card Display Settings', 'parfume-reviews'),
            array($this, 'render_card_section'),
            'parfume-reviews-settings'
        );
        
        // Add display fields
        $this->add_archive_fields();
        $this->add_card_fields();
    }
    
    /**
     * Add archive display fields
     */
    private function add_archive_fields() {
        $archive_fields = array(
            'show_archive_sidebar' => __('Show Archive Sidebar', 'parfume-reviews'),
            'archive_posts_per_page' => __('Perfumes Per Page', 'parfume-reviews'),
            'archive_grid_columns' => __('Grid Columns', 'parfume-reviews'),
        );
        
        foreach ($archive_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'render_archive_field'),
                'parfume-reviews-settings',
                'parfume_reviews_archive_section',
                array('field' => $field, 'label' => $label)
            );
        }
    }
    
    /**
     * Add card display fields
     */
    private function add_card_fields() {
        $card_fields = array(
            'card_show_image' => __('Show Image', 'parfume-reviews'),
            'card_show_brand' => __('Show Brand', 'parfume-reviews'),
            'card_show_name' => __('Show Name', 'parfume-reviews'),
            'card_show_price' => __('Show Price', 'parfume-reviews'),
            'card_show_availability' => __('Show Availability', 'parfume-reviews'),
            'card_show_shipping' => __('Show Shipping', 'parfume-reviews'),
        );
        
        foreach ($card_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'render_card_field'),
                'parfume-reviews-settings',
                'parfume_reviews_card_section',
                array('field' => $field, 'label' => $label)
            );
        }
    }
    
    /**
     * Render archive section
     */
    public function render_archive_section() {
        echo '<p>' . __('Configure how archive pages display perfumes.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Render card section
     */
    public function render_card_section() {
        echo '<p>' . __('Configure what information to show on perfume cards.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Render archive field
     */
    public function render_archive_field($args) {
        $settings = get_option('parfume_reviews_settings', array());
        $field = $args['field'];
        
        $defaults = array(
            'show_archive_sidebar' => 1,
            'archive_posts_per_page' => 12,
            'archive_grid_columns' => 3,
        );
        
        $value = isset($settings[$field]) ? $settings[$field] : $defaults[$field];
        
        switch ($field) {
            case 'show_archive_sidebar':
                echo '<label>';
                echo '<input type="checkbox" name="parfume_reviews_settings[' . esc_attr($field) . ']" ';
                echo 'value="1" ' . checked($value, 1, false) . '>';
                echo ' ' . __('Show sidebar on archive pages', 'parfume-reviews');
                echo '</label>';
                break;
                
            case 'archive_posts_per_page':
                echo '<input type="number" name="parfume_reviews_settings[' . esc_attr($field) . ']" ';
                echo 'value="' . esc_attr($value) . '" min="1" max="100" class="small-text" />';
                echo '<p class="description">' . __('Number of perfumes to show per page', 'parfume-reviews') . '</p>';
                break;
                
            case 'archive_grid_columns':
                echo '<select name="parfume_reviews_settings[' . esc_attr($field) . ']">';
                for ($i = 2; $i <= 5; $i++) {
                    echo '<option value="' . $i . '" ' . selected($value, $i, false) . '>';
                    echo sprintf(_n('%d column', '%d columns', $i, 'parfume-reviews'), $i);
                    echo '</option>';
                }
                echo '</select>';
                break;
        }
    }
    
    /**
     * Render card field
     */
    public function render_card_field($args) {
        $settings = get_option('parfume_reviews_settings', array());
        $field = $args['field'];
        $label = $args['label'];
        
        $value = isset($settings[$field]) ? $settings[$field] : 1;
        
        echo '<label>';
        echo '<input type="checkbox" name="parfume_reviews_settings[' . esc_attr($field) . ']" ';
        echo 'value="1" ' . checked($value, 1, false) . '>';
        echo ' ' . sprintf(__('Show %s on perfume cards', 'parfume-reviews'), strtolower($label));
        echo '</label>';
    }
    
    /**
     * Validate display settings
     */
    public function validate_display($input) {
        $output = array();
        
        // Boolean fields
        $boolean_fields = array(
            'show_archive_sidebar', 'card_show_image', 'card_show_brand',
            'card_show_name', 'card_show_price', 'card_show_availability', 'card_show_shipping'
        );
        
        foreach ($boolean_fields as $field) {
            $output[$field] = isset($input[$field]) ? 1 : 0;
        }
        
        // Numeric fields
        if (isset($input['archive_posts_per_page'])) {
            $output['archive_posts_per_page'] = max(1, min(100, intval($input['archive_posts_per_page'])));
        }
        
        if (isset($input['archive_grid_columns'])) {
            $output['archive_grid_columns'] = max(2, min(5, intval($input['archive_grid_columns'])));
        }
        
        return $output;
    }
}