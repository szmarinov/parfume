<?php
/**
 * URL Settings
 * 
 * @package ParfumeReviews
 * @subpackage Admin\Settings
 */

namespace ParfumeReviews\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Urls {
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register URL settings
     */
    public function register_settings() {
        // URL Settings Section
        add_settings_section(
            'parfume_reviews_url_section',
            __('URL Settings', 'parfume-reviews'),
            array($this, 'render_url_section'),
            'parfume-reviews-settings'
        );
        
        // Add URL fields
        $this->add_url_fields();
    }
    
    /**
     * Add URL settings fields
     */
    private function add_url_fields() {
        $url_fields = array(
            'parfume_slug' => __('Parfume Archive Slug', 'parfume-reviews'),
            'brands_slug' => __('Brands Taxonomy Slug', 'parfume-reviews'),
            'notes_slug' => __('Notes Taxonomy Slug', 'parfume-reviews'),
            'perfumers_slug' => __('Perfumers Taxonomy Slug', 'parfume-reviews'),
            'gender_slug' => __('Gender Taxonomy Slug', 'parfume-reviews'),
            'aroma_type_slug' => __('Aroma Type Taxonomy Slug', 'parfume-reviews'),
            'season_slug' => __('Season Taxonomy Slug', 'parfume-reviews'),
            'intensity_slug' => __('Intensity Taxonomy Slug', 'parfume-reviews'),
        );
        
        foreach ($url_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'render_slug_field'),
                'parfume-reviews-settings',
                'parfume_reviews_url_section',
                array('field' => $field, 'label' => $label)
            );
        }
    }
    
    /**
     * Render URL section
     */
    public function render_url_section() {
        echo '<p>' . __('Configure URL structure for different page types.', 'parfume-reviews') . '</p>';
        echo '<div class="notice notice-info inline"><p>';
        echo '<strong>' . __('Important:', 'parfume-reviews') . '</strong> ';
        echo __('Changing URL slugs will affect all taxonomy URLs. Make sure to set up redirects if necessary.', 'parfume-reviews');
        echo '</p></div>';
    }
    
    /**
     * Render slug field
     */
    public function render_slug_field($args) {
        $settings = get_option('parfume_reviews_settings', array());
        $field = $args['field'];
        
        $defaults = array(
            'parfume_slug' => 'parfiumi',
            'brands_slug' => 'marki',
            'notes_slug' => 'notes',
            'perfumers_slug' => 'parfumers',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
        );
        
        $value = isset($settings[$field]) ? $settings[$field] : $defaults[$field];
        
        echo '<input type="text" name="parfume_reviews_settings[' . esc_attr($field) . ']" ';
        echo 'value="' . esc_attr($value) . '" class="regular-text" />';
        
        // Show URL preview
        $this->show_url_preview($field, $value);
    }
    
    /**
     * Show URL preview
     */
    private function show_url_preview($field, $value) {
        $base_url = home_url('/');
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        echo '<br><small class="description">';
        
        switch ($field) {
            case 'parfume_slug':
                echo sprintf(__('Archive URL: %s', 'parfume-reviews'), 
                    '<code>' . $base_url . $value . '/</code>');
                break;
                
            default:
                echo sprintf(__('Archive URL: %s', 'parfume-reviews'), 
                    '<code>' . $base_url . $parfume_slug . '/' . $value . '/</code>');
                break;
        }
        
        echo '</small>';
    }
    
    /**
     * Validate URL settings
     */
    public function validate_urls($input) {
        $output = array();
        
        $slug_fields = array(
            'parfume_slug', 'brands_slug', 'notes_slug', 'perfumers_slug',
            'gender_slug', 'aroma_type_slug', 'season_slug', 'intensity_slug'
        );
        
        foreach ($slug_fields as $field) {
            if (isset($input[$field])) {
                $slug = sanitize_title($input[$field]);
                
                // Ensure slug is not empty
                if (empty($slug)) {
                    add_settings_error(
                        'parfume_reviews_settings',
                        $field,
                        sprintf(__('%s cannot be empty.', 'parfume-reviews'), $field),
                        'error'
                    );
                    continue;
                }
                
                // Check for reserved words
                $reserved = array('admin', 'api', 'www', 'ftp', 'mail', 'wp-admin', 'wp-content');
                if (in_array($slug, $reserved)) {
                    add_settings_error(
                        'parfume_reviews_settings',
                        $field,
                        sprintf(__('%s is a reserved word and cannot be used.', 'parfume-reviews'), $slug),
                        'error'
                    );
                    continue;
                }
                
                $output[$field] = $slug;
            }
        }
        
        // Schedule rewrite rules flush
        if (!empty($output)) {
            update_option('parfume_reviews_flush_rewrite_rules', true);
        }
        
        return $output;
    }
}