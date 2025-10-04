<?php
/**
 * Settings Manager
 * 
 * Manages all plugin settings and admin pages
 * 
 * @package Parfume_Reviews
 * @subpackage Admin\Settings
 * @since 2.0.0
 */

namespace Parfume_Reviews\Admin\Settings;

use Parfume_Reviews\Core\Container;

/**
 * SettingsManager Class
 * 
 * Handles settings pages and configuration
 */
class SettingsManager {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Settings configuration
     * 
     * @var array
     */
    private $config;
    
    /**
     * Option name
     * 
     * @var string
     */
    private $option_name = 'parfume_reviews_settings';
    
    /**
     * Current tab
     * 
     * @var string
     */
    private $current_tab = 'general';
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->config = $this->get_config();
        
        if (isset($this->config['option_name'])) {
            $this->option_name = $this->config['option_name'];
        }
        
        // Get current tab
        $this->current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    }
    
    /**
     * Get settings configuration
     * 
     * @return array
     */
    private function get_config() {
        return $this->container->get('config.settings');
    }
    
    /**
     * Add admin menu
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Настройки', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'parfume_reviews_settings_group',
            $this->option_name,
            [$this, 'sanitize_settings']
        );
        
        // Register sections and fields for each page
        if (isset($this->config['pages'])) {
            foreach ($this->config['pages'] as $page_id => $page_config) {
                $this->register_page_settings($page_id, $page_config);
            }
        }
    }
    
    /**
     * Register settings for a page
     * 
     * @param string $page_id Page ID
     * @param array $page_config Page configuration
     */
    private function register_page_settings($page_id, $page_config) {
        if (!isset($page_config['sections'])) {
            return;
        }
        
        foreach ($page_config['sections'] as $section_id => $section_config) {
            $section_full_id = $page_id . '_' . $section_id;
            
            add_settings_section(
                $section_full_id,
                $section_config['title'],
                function() use ($section_config) {
                    if (isset($section_config['description'])) {
                        echo '<p>' . esc_html($section_config['description']) . '</p>';
                    }
                },
                'parfume_reviews_settings_' . $page_id
            );
            
            // Register fields
            if (isset($section_config['fields'])) {
                foreach ($section_config['fields'] as $field_id => $field_config) {
                    $this->register_field($section_full_id, $page_id, $field_id, $field_config);
                }
            }
        }
    }
    
    /**
     * Register individual field
     * 
     * @param string $section_id Section ID
     * @param string $page_id Page ID
     * @param string $field_id Field ID
     * @param array $field_config Field configuration
     */
    private function register_field($section_id, $page_id, $field_id, $field_config) {
        add_settings_field(
            $field_id,
            $field_config['label'],
            [$this, 'render_field'],
            'parfume_reviews_settings_' . $page_id,
            $section_id,
            [
                'field_id' => $field_id,
                'field_config' => $field_config
            ]
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('parfume_reviews_settings'); ?>
            
            <nav class="nav-tab-wrapper">
                <?php $this->render_tabs(); ?>
            </nav>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_settings_group');
                do_settings_sections('parfume_reviews_settings_' . $this->current_tab);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render tabs
     */
    private function render_tabs() {
        if (!isset($this->config['pages'])) {
            return;
        }
        
        $base_url = admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings');
        
        foreach ($this->config['pages'] as $page_id => $page_config) {
            $active = $this->current_tab === $page_id ? 'nav-tab-active' : '';
            $url = add_query_arg('tab', $page_id, $base_url);
            
            printf(
                '<a href="%s" class="nav-tab %s">%s</a>',
                esc_url($url),
                esc_attr($active),
                esc_html($page_config['title'])
            );
        }
    }
    
    /**
     * Render field
     * 
     * @param array $args Field arguments
     */
    public function render_field($args) {
        $field_id = $args['field_id'];
        $field_config = $args['field_config'];
        $field_type = $field_config['type'];
        
        $settings = get_option($this->option_name, []);
        $value = isset($settings[$field_id]) ? $settings[$field_id] : 
                 (isset($field_config['default']) ? $field_config['default'] : '');
        
        $field_name = $this->option_name . '[' . $field_id . ']';
        
        switch ($field_type) {
            case 'text':
                $this->render_text_field($field_name, $value, $field_config);
                break;
            
            case 'number':
                $this->render_number_field($field_name, $value, $field_config);
                break;
            
            case 'checkbox':
                $this->render_checkbox_field($field_name, $value, $field_config);
                break;
            
            case 'select':
                $this->render_select_field($field_name, $value, $field_config);
                break;
            
            case 'textarea':
                $this->render_textarea_field($field_name, $value, $field_config);
                break;
            
            case 'page_select':
                $this->render_page_select_field($field_name, $value, $field_config);
                break;
            
            default:
                $this->render_text_field($field_name, $value, $field_config);
        }
        
        // Description
        if (isset($field_config['description'])) {
            echo '<p class="description">' . esc_html($field_config['description']) . '</p>';
        }
    }
    
    /**
     * Render text field
     */
    private function render_text_field($name, $value, $config) {
        $placeholder = isset($config['placeholder']) ? $config['placeholder'] : '';
        
        printf(
            '<input type="text" name="%s" value="%s" placeholder="%s" class="regular-text" />',
            esc_attr($name),
            esc_attr($value),
            esc_attr($placeholder)
        );
    }
    
    /**
     * Render number field
     */
    private function render_number_field($name, $value, $config) {
        $min = isset($config['min']) ? $config['min'] : '';
        $max = isset($config['max']) ? $config['max'] : '';
        $step = isset($config['step']) ? $config['step'] : '1';
        
        printf(
            '<input type="number" name="%s" value="%s" min="%s" max="%s" step="%s" class="small-text" />',
            esc_attr($name),
            esc_attr($value),
            esc_attr($min),
            esc_attr($max),
            esc_attr($step)
        );
    }
    
    /**
     * Render checkbox field
     */
    private function render_checkbox_field($name, $value, $config) {
        printf(
            '<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
            esc_attr($name),
            checked($value, 1, false),
            isset($config['checkbox_label']) ? esc_html($config['checkbox_label']) : ''
        );
    }
    
    /**
     * Render select field
     */
    private function render_select_field($name, $value, $config) {
        $options = isset($config['options']) ? $config['options'] : [];
        
        echo '<select name="' . esc_attr($name) . '">';
        
        foreach ($options as $option_value => $option_label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                selected($value, $option_value, false),
                esc_html($option_label)
            );
        }
        
        echo '</select>';
    }
    
    /**
     * Render textarea field
     */
    private function render_textarea_field($name, $value, $config) {
        $rows = isset($config['rows']) ? $config['rows'] : 5;
        
        printf(
            '<textarea name="%s" rows="%s" class="large-text">%s</textarea>',
            esc_attr($name),
            esc_attr($rows),
            esc_textarea($value)
        );
    }
    
    /**
     * Render page select field
     */
    private function render_page_select_field($name, $value, $config) {
        wp_dropdown_pages([
            'name' => $name,
            'selected' => $value,
            'show_option_none' => __('-- Избери страница --', 'parfume-reviews'),
            'option_none_value' => ''
        ]);
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input Raw input
     * @return array Sanitized input
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        if (!is_array($input)) {
            return $sanitized;
        }
        
        // Get all fields from config
        $all_fields = $this->get_all_fields();
        
        foreach ($input as $key => $value) {
            if (!isset($all_fields[$key])) {
                continue;
            }
            
            $field_config = $all_fields[$key];
            $field_type = $field_config['type'];
            
            switch ($field_type) {
                case 'text':
                case 'select':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                
                case 'number':
                    $sanitized[$key] = absint($value);
                    break;
                
                case 'checkbox':
                    $sanitized[$key] = $value === '1' ? 1 : 0;
                    break;
                
                case 'textarea':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                
                case 'page_select':
                    $sanitized[$key] = absint($value);
                    break;
                
                default:
                    $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        // Check if rewrite rules need flushing
        if ($this->settings_need_flush($sanitized)) {
            update_option('parfume_reviews_flush_rewrite_rules', 1);
        }
        
        return $sanitized;
    }
    
    /**
     * Get all fields from config
     * 
     * @return array
     */
    private function get_all_fields() {
        $fields = [];
        
        if (!isset($this->config['pages'])) {
            return $fields;
        }
        
        foreach ($this->config['pages'] as $page_config) {
            if (!isset($page_config['sections'])) {
                continue;
            }
            
            foreach ($page_config['sections'] as $section_config) {
                if (!isset($section_config['fields'])) {
                    continue;
                }
                
                foreach ($section_config['fields'] as $field_id => $field_config) {
                    $fields[$field_id] = $field_config;
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Check if settings need rewrite rules flush
     * 
     * @param array $new_settings New settings
     * @return bool
     */
    private function settings_need_flush($new_settings) {
        $old_settings = get_option($this->option_name, []);
        
        // Check URL-related settings
        $url_keys = [
            'parfume_slug',
            'brands_slug',
            'gender_slug',
            'notes_slug',
            'perfumers_slug',
            'season_slug',
            'intensity_slug',
            'aroma_type_slug'
        ];
        
        foreach ($url_keys as $key) {
            $old_value = isset($old_settings[$key]) ? $old_settings[$key] : '';
            $new_value = isset($new_settings[$key]) ? $new_settings[$key] : '';
            
            if ($old_value !== $new_value) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Only on settings page
        if ($hook !== 'parfume_page_parfume-reviews-settings') {
            return;
        }
        
        wp_enqueue_style(
            'parfume-reviews-admin-settings',
            PARFUME_REVIEWS_URL . 'assets/css/admin-settings.css',
            [],
            PARFUME_REVIEWS_VERSION
        );
        
        wp_enqueue_script(
            'parfume-reviews-admin-settings',
            PARFUME_REVIEWS_URL . 'assets/js/admin-settings.js',
            ['jquery'],
            PARFUME_REVIEWS_VERSION,
            true
        );
    }
    
    /**
     * Get setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get($key, $default = null) {
        $settings = get_option($this->option_name, []);
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Update setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    public function update($key, $value) {
        $settings = get_option($this->option_name, []);
        $settings[$key] = $value;
        return update_option($this->option_name, $settings);
    }
    
    /**
     * Delete setting
     * 
     * @param string $key Setting key
     * @return bool
     */
    public function delete($key) {
        $settings = get_option($this->option_name, []);
        
        if (isset($settings[$key])) {
            unset($settings[$key]);
            return update_option($this->option_name, $settings);
        }
        
        return false;
    }
    
    /**
     * Get all settings
     * 
     * @return array
     */
    public function get_all() {
        return get_option($this->option_name, []);
    }
}