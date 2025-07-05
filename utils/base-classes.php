<?php
/**
 * Base Classes for Parfume Reviews Plugin
 *
 * @package Parfume_Reviews\Utils
 * @since 1.0.0
 */

namespace Parfume_Reviews\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Singleton Trait
 * Provides singleton functionality for classes that need it
 */
trait Singleton {
    /**
     * Store the singleton instance
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     *
     * @return static
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
}

/**
 * Base Post Type Class
 * Provides common functionality for all custom post types
 */
abstract class Post_Type_Base {
    
    /**
     * Post type slug
     * @var string
     */
    protected $post_type = '';
    
    /**
     * Post type arguments
     * @var array
     */
    protected $args = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor - initialization happens in init()
    }
    
    /**
     * Initialize the post type
     * Must be implemented by child classes
     */
    abstract public function init();
    
    /**
     * Register the post type
     */
    protected function register() {
        if (!empty($this->post_type) && !empty($this->args)) {
            register_post_type($this->post_type, $this->args);
        }
    }
    
    /**
     * Get post type slug
     *
     * @return string
     */
    public function get_post_type() {
        return $this->post_type;
    }
    
    /**
     * Add admin hooks
     */
    protected function add_admin_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post'), 10, 3);
    }
    
    /**
     * Add frontend hooks
     */
    protected function add_frontend_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'template_include'));
        add_action('pre_get_posts', array($this, 'modify_query'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Override in child classes
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Override in child classes
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Override in child classes
    }
    
    /**
     * Save post data
     */
    public function save_post($post_id, $post, $update) {
        // Override in child classes
    }
    
    /**
     * Include template files
     */
    public function template_include($template) {
        // Override in child classes
        return $template;
    }
    
    /**
     * Modify main query
     */
    public function modify_query($query) {
        // Override in child classes
    }
}

/**
 * Base Taxonomy Class
 * Provides common functionality for all custom taxonomies
 */
abstract class Taxonomy_Base {
    
    /**
     * Taxonomy slug
     * @var string
     */
    protected $taxonomy = '';
    
    /**
     * Post types to attach to
     * @var array
     */
    protected $post_types = array();
    
    /**
     * Taxonomy arguments
     * @var array
     */
    protected $args = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor - initialization happens in init()
    }
    
    /**
     * Initialize the taxonomy
     * Must be implemented by child classes
     */
    abstract public function init();
    
    /**
     * Register the taxonomy
     */
    protected function register() {
        if (!empty($this->taxonomy) && !empty($this->post_types) && !empty($this->args)) {
            register_taxonomy($this->taxonomy, $this->post_types, $this->args);
        }
    }
    
    /**
     * Get taxonomy slug
     *
     * @return string
     */
    public function get_taxonomy() {
        return $this->taxonomy;
    }
    
    /**
     * Add admin hooks
     */
    protected function add_admin_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action($this->taxonomy . '_add_form_fields', array($this, 'add_meta_fields'));
        add_action($this->taxonomy . '_edit_form_fields', array($this, 'edit_meta_fields'), 10, 2);
        add_action('created_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 2);
        add_action('edited_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 2);
    }
    
    /**
     * Add frontend hooks
     */
    protected function add_frontend_hooks() {
        add_filter('template_include', array($this, 'template_include'));
        add_action('pre_get_posts', array($this, 'modify_query'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        // Override in child classes
    }
    
    /**
     * Add meta fields to taxonomy
     */
    public function add_meta_fields() {
        // Override in child classes
    }
    
    /**
     * Edit meta fields in taxonomy
     */
    public function edit_meta_fields($term, $taxonomy) {
        // Override in child classes
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($term_id, $taxonomy) {
        // Override in child classes
    }
    
    /**
     * Include template files
     */
    public function template_include($template) {
        // Override in child classes
        return $template;
    }
    
    /**
     * Modify main query
     */
    public function modify_query($query) {
        // Override in child classes
    }
}

/**
 * Base Shortcode Class
 * Provides common functionality for all shortcodes
 */
abstract class Shortcode_Base {
    
    /**
     * Shortcode tag
     * @var string
     */
    protected $tag = '';
    
    /**
     * Default attributes
     * @var array
     */
    protected $defaults = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        if (!empty($this->tag)) {
            add_shortcode($this->tag, array($this, 'shortcode_handler'));
        }
    }
    
    /**
     * Shortcode handler
     */
    public function shortcode_handler($atts = array(), $content = null) {
        $atts = shortcode_atts($this->defaults, $atts, $this->tag);
        return $this->render($atts, $content);
    }
    
    /**
     * Render shortcode output
     * Must be implemented by child classes
     */
    abstract protected function render($atts, $content);
    
    /**
     * Get shortcode tag
     *
     * @return string
     */
    public function get_tag() {
        return $this->tag;
    }
}

/**
 * Base Admin Page Class
 * Provides common functionality for all admin pages
 */
abstract class Admin_Page_Base {
    
    /**
     * Page slug
     * @var string
     */
    protected $page_slug = '';
    
    /**
     * Page title
     * @var string
     */
    protected $page_title = '';
    
    /**
     * Menu title
     * @var string
     */
    protected $menu_title = '';
    
    /**
     * Parent slug
     * @var string
     */
    protected $parent_slug = '';
    
    /**
     * Capability required
     * @var string
     */
    protected $capability = 'manage_options';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_init', array($this, 'init_admin'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Add admin page to menu
     */
    public function add_admin_page() {
        if (!empty($this->parent_slug)) {
            add_submenu_page(
                $this->parent_slug,
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->page_slug,
                array($this, 'render_page')
            );
        } else {
            add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->page_slug,
                array($this, 'render_page')
            );
        }
    }
    
    /**
     * Initialize admin page
     * Override in child classes
     */
    public function init_admin() {
        // Override in child classes
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, $this->page_slug) !== false) {
            $this->enqueue_assets();
        }
    }
    
    /**
     * Enqueue specific assets
     * Override in child classes
     */
    protected function enqueue_assets() {
        // Override in child classes
    }
    
    /**
     * Render the admin page
     * Must be implemented by child classes
     */
    abstract public function render_page();
    
    /**
     * Get page slug
     *
     * @return string
     */
    public function get_page_slug() {
        return $this->page_slug;
    }
}

/**
 * Base Widget Class
 * Provides common functionality for all widgets
 */
abstract class Widget_Base extends \WP_Widget {
    
    /**
     * Widget options
     * @var array
     */
    public $widget_options;
    
    /**
     * Control options
     * @var array
     */
    public $control_options;
    
    /**
     * Constructor
     */
    public function __construct($id_base = '', $name = '', $widget_options = array(), $control_options = array()) {
        $this->widget_options = $widget_options;
        $this->control_options = $control_options;
        
        parent::__construct($id_base, $name, $widget_options, $control_options);
    }
    
    /**
     * Widget output
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        $this->render_widget($args, $instance);
        echo $args['after_widget'];
    }
    
    /**
     * Render widget content
     * Must be implemented by child classes
     */
    abstract protected function render_widget($args, $instance);
    
    /**
     * Widget form
     */
    public function form($instance) {
        $this->render_form($instance);
    }
    
    /**
     * Render widget form
     * Must be implemented by child classes
     */
    abstract protected function render_form($instance);
    
    /**
     * Update widget
     */
    public function update($new_instance, $old_instance) {
        return $this->process_update($new_instance, $old_instance);
    }
    
    /**
     * Process widget update
     * Override in child classes
     */
    protected function process_update($new_instance, $old_instance) {
        return $new_instance;
    }
}

/**
 * Base AJAX Handler Class
 * Provides common functionality for AJAX handlers
 */
abstract class Ajax_Base {
    
    /**
     * AJAX actions
     * @var array
     */
    protected $actions = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_ajax_actions();
    }
    
    /**
     * Register AJAX actions
     */
    protected function register_ajax_actions() {
        foreach ($this->actions as $action) {
            add_action('wp_ajax_' . $action, array($this, 'handle_' . $action));
            add_action('wp_ajax_nopriv_' . $action, array($this, 'handle_' . $action));
        }
    }
    
    /**
     * Verify nonce
     */
    protected function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error(__('Security check failed', 'parfume-reviews'));
        }
    }
    
    /**
     * Check permissions
     */
    protected function check_permissions($capability = 'edit_posts') {
        if (!current_user_can($capability)) {
            wp_send_json_error(__('Insufficient permissions', 'parfume-reviews'));
        }
    }
    
    /**
     * Send JSON success response
     */
    protected function send_success($data = null, $message = '') {
        wp_send_json_success(array(
            'data' => $data,
            'message' => $message
        ));
    }
    
    /**
     * Send JSON error response
     */
    protected function send_error($message = '', $data = null) {
        wp_send_json_error(array(
            'message' => $message,
            'data' => $data
        ));
    }
}

/**
 * Base Meta Box Class
 * Provides common functionality for meta boxes
 */
abstract class Meta_Box_Base {
    
    /**
     * Meta box ID
     * @var string
     */
    protected $id = '';
    
    /**
     * Meta box title
     * @var string
     */
    protected $title = '';
    
    /**
     * Post types to add meta box to
     * @var array
     */
    protected $post_types = array();
    
    /**
     * Meta box context
     * @var string
     */
    protected $context = 'normal';
    
    /**
     * Meta box priority
     * @var string
     */
    protected $priority = 'default';
    
    /**
     * Field configuration
     * @var array
     */
    protected $fields = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Add meta box
     */
    public function add_meta_box() {
        foreach ($this->post_types as $post_type) {
            add_meta_box(
                $this->id,
                $this->title,
                array($this, 'render_meta_box'),
                $post_type,
                $this->context,
                $this->priority
            );
        }
    }
    
    /**
     * Render meta box
     * Must be implemented by child classes
     */
    abstract public function render_meta_box($post);
    
    /**
     * Save meta box data
     */
    public function save_meta_box($post_id, $post, $update) {
        // Verify nonce
        if (!isset($_POST[$this->id . '_nonce']) || 
            !wp_verify_nonce($_POST[$this->id . '_nonce'], $this->id)) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save fields
        foreach ($this->fields as $field_id => $field_config) {
            if (isset($_POST[$field_id])) {
                $value = $this->sanitize_field_value($_POST[$field_id], $field_config['type']);
                update_post_meta($post_id, $field_id, $value);
            }
        }
    }
    
    /**
     * Sanitize field value based on type
     */
    protected function sanitize_field_value($value, $type) {
        switch ($type) {
            case 'text':
            case 'url':
            case 'email':
                return sanitize_text_field($value);
            
            case 'number':
                return is_numeric($value) ? floatval($value) : 0;
            
            case 'textarea':
                return sanitize_textarea_field($value);
            
            case 'checkbox':
                return $value ? 1 : 0;
            
            case 'select':
                return sanitize_text_field($value);
            
            case 'array':
                return is_array($value) ? array_map('sanitize_text_field', $value) : array();
            
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Render field
     */
    protected function render_field($field_id, $field_config, $value = '') {
        $type = isset($field_config['type']) ? $field_config['type'] : 'text';
        $label = isset($field_config['label']) ? $field_config['label'] : '';
        $description = isset($field_config['description']) ? $field_config['description'] : '';
        
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($field_id) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        
        switch ($type) {
            case 'text':
            case 'url':
            case 'email':
                echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
            
            case 'number':
                $min = isset($field_config['min']) ? $field_config['min'] : '';
                $max = isset($field_config['max']) ? $field_config['max'] : '';
                $step = isset($field_config['step']) ? $field_config['step'] : '1';
                echo '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" class="small-text" />';
                break;
            
            case 'textarea':
                $rows = isset($field_config['rows']) ? $field_config['rows'] : 5;
                echo '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" rows="' . esc_attr($rows) . '" class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
            
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="1" ' . checked($value, 1, false) . ' />';
                break;
            
            case 'select':
                echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '">';
                if (isset($field_config['options']) && is_array($field_config['options'])) {
                    foreach ($field_config['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                echo '</select>';
                break;
        }
        
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_media();
        }
    }
}