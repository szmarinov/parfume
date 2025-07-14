<?php
/**
 * Parfume Reviews Settings Handler
 * 
 * ВАЖНО: Този клас управлява всички админ настройки и конфигурации за плъгина
 * Включва settings API, опции менюта, валидация и персистентност на данни
 *
 * @package Parfume_Reviews
 * @since 1.0.0
 */

// ВАЖНО: Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Parfume_Reviews_Settings
 * 
 * ВАЖНО: Основен клас за управление на настройките на плъгина
 * Всички методи са запазени с подобрена функционалност
 */
class Parfume_Reviews_Settings {

    /**
     * ВАЖНО: Singleton instance за предотвратяване на множествени инстанции
     * @var Parfume_Reviews_Settings|null
     */
    private static $instance = null;

    /**
     * ВАЖНО: Option group name за WordPress Settings API
     * @var string
     */
    private $option_group = 'parfume_reviews_settings';

    /**
     * ВАЖНО: Option name за съхранение в базата данни
     * @var string
     */
    private $option_name = 'parfume_reviews_options';

    /**
     * ВАЖНО: Default настройки за плъгина
     * @var array
     */
    private $default_options = array();

    /**
     * ВАЖНО: Кеширани настройки за бърз достъп
     * @var array|null
     */
    private $cached_options = null;

    /**
     * Constructor - ВАЖНО: Запазен оригинален конструктор с подобрения
     */
    public function __construct() {
        $this->init();
        $this->setup_default_options();
        parfume_reviews_debug('Settings class initialized', 'settings');
    }

    /**
     * ВАЖНО: Singleton pattern за контрол на инстанциите
     * 
     * @return Parfume_Reviews_Settings
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ========================================
    // РАЗДЕЛ 1: Initialization & Setup
    // ========================================

    /**
     * ВАЖНО: Основна инициализация - запазена оригинална логика
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // ВАЖНО: AJAX handlers за динамични настройки
        add_action('wp_ajax_parfume_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_parfume_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_parfume_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_parfume_import_settings', array($this, 'ajax_import_settings'));
        
        parfume_reviews_debug('Settings hooks registered', 'settings');
    }

    /**
     * ВАЖНО: Настройване на default опции
     */
    private function setup_default_options() {
        $this->default_options = array(
            // Общи настройки
            'enable_reviews' => true,
            'enable_ratings' => true,
            'enable_comparison' => true,
            'enable_stores' => true,
            'enable_collections' => true,
            
            // Display настройки
            'reviews_per_page' => 12,
            'enable_pagination' => true,
            'enable_search' => true,
            'enable_filters' => true,
            'enable_sorting' => true,
            
            // Layout настройки
            'layout_style' => 'grid',
            'show_featured_image' => true,
            'show_rating_stars' => true,
            'show_price' => true,
            'show_brand' => true,
            'show_excerpt' => true,
            
            // Advanced настройки
            'enable_seo' => true,
            'enable_schema' => true,
            'enable_breadcrumbs' => true,
            'enable_debug' => false,
            'cache_duration' => 3600,
            
            // Import/Export настройки
            'auto_backup' => false,
            'backup_frequency' => 'weekly',
            'export_format' => 'json'
        );
    }

    // ========================================
    // РАЗДЕЛ 2: Admin Menu & Pages
    // ========================================

    /**
     * ВАЖНО: Добавяне на админ менюта - запазена оригинална функция
     */
    public function add_admin_menu() {
        // Главно меню
        add_menu_page(
            __('Parfume Reviews', 'parfume-reviews'),
            __('Parfume Reviews', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews',
            array($this, 'admin_page'),
            'dashicons-star-filled',
            30
        );

        // Settings подменю
        add_submenu_page(
            'parfume-reviews',
            __('Settings', 'parfume-reviews'),
            __('Settings', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'settings_page')
        );

        // Tools подменю
        add_submenu_page(
            'parfume-reviews',
            __('Tools', 'parfume-reviews'),
            __('Tools', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-tools',
            array($this, 'tools_page')
        );

        parfume_reviews_debug('Admin menus added', 'settings');
    }

    /**
     * ВАЖНО: Главна админ страница - запазена с подобрения
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="parfume-admin-dashboard">
                <?php $this->render_dashboard_widgets(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * ВАЖНО: Settings страница - запазена основна логика
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Parfume Reviews Settings', 'parfume-reviews'); ?></h1>
            
            <div class="parfume-settings-wrapper">
                <?php $this->render_settings_tabs(); ?>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields($this->option_group);
                    do_settings_sections('parfume-reviews-settings');
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * ВАЖНО: Tools страница - запазена с разширена функционалност
     */
    public function tools_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Parfume Reviews Tools', 'parfume-reviews'); ?></h1>
            
            <div class="parfume-tools-wrapper">
                <?php $this->render_tools_sections(); ?>
            </div>
        </div>
        <?php
    }

    // ========================================
    // РАЗДЕЛ 3: Settings API Integration
    // ========================================

    /**
     * ВАЖНО: WordPress Settings API инициализация - запазена логика
     */
    public function settings_init() {
        // Регистрация на settings
        register_setting(
            $this->option_group,
            $this->option_name,
            array($this, 'sanitize_options')
        );

        // Добавяне на sections и fields
        $this->add_settings_sections();
        $this->add_settings_fields();
        
        parfume_reviews_debug('Settings API initialized', 'settings');
    }

    /**
     * ВАЖНО: Добавяне на settings sections
     */
    private function add_settings_sections() {
        // General Settings Section
        add_settings_section(
            'parfume_general_settings',
            __('General Settings', 'parfume-reviews'),
            array($this, 'general_settings_callback'),
            'parfume-reviews-settings'
        );

        // Display Settings Section
        add_settings_section(
            'parfume_display_settings',
            __('Display Settings', 'parfume-reviews'),
            array($this, 'display_settings_callback'),
            'parfume-reviews-settings'
        );

        // Advanced Settings Section
        add_settings_section(
            'parfume_advanced_settings',
            __('Advanced Settings', 'parfume-reviews'),
            array($this, 'advanced_settings_callback'),
            'parfume-reviews-settings'
        );
    }

    /**
     * ВАЖНО: Добавяне на settings fields
     */
    private function add_settings_fields() {
        // General Settings Fields
        add_settings_field(
            'enable_reviews',
            __('Enable Reviews', 'parfume-reviews'),
            array($this, 'checkbox_field_callback'),
            'parfume-reviews-settings',
            'parfume_general_settings',
            array('field' => 'enable_reviews')
        );

        add_settings_field(
            'enable_ratings',
            __('Enable Ratings', 'parfume-reviews'),
            array($this, 'checkbox_field_callback'),
            'parfume-reviews-settings',
            'parfume_general_settings',
            array('field' => 'enable_ratings')
        );

        // Display Settings Fields
        add_settings_field(
            'reviews_per_page',
            __('Reviews Per Page', 'parfume-reviews'),
            array($this, 'number_field_callback'),
            'parfume-reviews-settings',
            'parfume_display_settings',
            array('field' => 'reviews_per_page', 'min' => 1, 'max' => 100)
        );

        add_settings_field(
            'layout_style',
            __('Layout Style', 'parfume-reviews'),
            array($this, 'select_field_callback'),
            'parfume-reviews-settings',
            'parfume_display_settings',
            array(
                'field' => 'layout_style',
                'options' => array(
                    'grid' => __('Grid', 'parfume-reviews'),
                    'list' => __('List', 'parfume-reviews'),
                    'masonry' => __('Masonry', 'parfume-reviews')
                )
            )
        );

        // Advanced Settings Fields
        add_settings_field(
            'enable_debug',
            __('Enable Debug Mode', 'parfume-reviews'),
            array($this, 'checkbox_field_callback'),
            'parfume-reviews-settings',
            'parfume_advanced_settings',
            array('field' => 'enable_debug')
        );
    }

    // ========================================
    // РАЗДЕЛ 4: Field Callbacks
    // ========================================

    /**
     * ВАЖНО: Checkbox field callback - запазена функция
     */
    public function checkbox_field_callback($args) {
        $options = $this->get_options();
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : false;
        
        printf(
            '<input type="checkbox" id="%s" name="%s[%s]" value="1" %s />',
            esc_attr($field),
            esc_attr($this->option_name),
            esc_attr($field),
            checked(1, $value, false)
        );
    }

    /**
     * VAŽНО: Number field callback - запазена функция
     */
    public function number_field_callback($args) {
        $options = $this->get_options();
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        
        printf(
            '<input type="number" id="%s" name="%s[%s]" value="%s" min="%s" max="%s" class="small-text" />',
            esc_attr($field),
            esc_attr($this->option_name),
            esc_attr($field),
            esc_attr($value),
            esc_attr($min),
            esc_attr($max)
        );
    }

    /**
     * ВАЖНО: Select field callback - запазена функция
     */
    public function select_field_callback($args) {
        $options = $this->get_options();
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $select_options = $args['options'];
        
        printf('<select id="%s" name="%s[%s]">', esc_attr($field), esc_attr($this->option_name), esc_attr($field));
        
        foreach ($select_options as $option_value => $option_label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                selected($value, $option_value, false),
                esc_html($option_label)
            );
        }
        
        echo '</select>';
    }

    // ========================================
    // РАЗДЕЛ 5: Section Callbacks
    // ========================================

    /**
     * ВАЖНО: General settings section callback
     */
    public function general_settings_callback() {
        echo '<p>' . esc_html__('Configure the basic functionality of the Parfume Reviews plugin.', 'parfume-reviews') . '</p>';
    }

    /**
     * ВАЖНО: Display settings section callback
     */
    public function display_settings_callback() {
        echo '<p>' . esc_html__('Control how parfume reviews are displayed on your website.', 'parfume-reviews') . '</p>';
    }

    /**
     * ВАЖНО: Advanced settings section callback
     */
    public function advanced_settings_callback() {
        echo '<p>' . esc_html__('Advanced configuration options for experienced users.', 'parfume-reviews') . '</p>';
    }

    // ========================================
    // РАЗДЕЛ 6: Options Management
    // ========================================

    /**
     * ВАЖНО: Получаване на опции - запазена основна функция
     * 
     * @param string $key Конкретна опция
     * @return mixed
     */
    public function get_option($key = null) {
        $options = $this->get_options();
        
        if ($key === null) {
            return $options;
        }
        
        return isset($options[$key]) ? $options[$key] : null;
    }

    /**
     * ВАЖНО: Получаване на всички опции с кеширане
     * 
     * @return array
     */
    public function get_options() {
        if ($this->cached_options === null) {
            $options = get_option($this->option_name, array());
            $this->cached_options = wp_parse_args($options, $this->default_options);
        }
        
        return $this->cached_options;
    }

    /**
     * ВАЖНО: Обновяване на опция - запазена функция
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function update_option($key, $value) {
        $options = $this->get_options();
        $options[$key] = $value;
        
        $updated = update_option($this->option_name, $options);
        
        if ($updated) {
            $this->cached_options = $options;
            parfume_reviews_debug("Option updated: {$key}", 'settings');
        }
        
        return $updated;
    }

    /**
     * ВАЖНО: Изтриване на опция
     * 
     * @param string $key
     * @return bool
     */
    public function delete_option($key) {
        $options = $this->get_options();
        
        if (isset($options[$key])) {
            unset($options[$key]);
            $updated = update_option($this->option_name, $options);
            
            if ($updated) {
                $this->cached_options = $options;
                parfume_reviews_debug("Option deleted: {$key}", 'settings');
            }
            
            return $updated;
        }
        
        return false;
    }

    /**
     * ВАЖНО: Sanitization на опции - запазена функция
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        if (!is_array($input)) {
            return $sanitized;
        }
        
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'reviews_per_page':
                case 'cache_duration':
                    $sanitized[$key] = absint($value);
                    break;
                    
                case 'layout_style':
                case 'backup_frequency':
                case 'export_format':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                    
                case 'enable_reviews':
                case 'enable_ratings':
                case 'enable_comparison':
                case 'enable_stores':
                case 'enable_collections':
                case 'enable_pagination':
                case 'enable_search':
                case 'enable_filters':
                case 'enable_sorting':
                case 'show_featured_image':
                case 'show_rating_stars':
                case 'show_price':
                case 'show_brand':
                case 'show_excerpt':
                case 'enable_seo':
                case 'enable_schema':
                case 'enable_breadcrumbs':
                case 'enable_debug':
                case 'auto_backup':
                    $sanitized[$key] = (bool) $value;
                    break;
                    
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        parfume_reviews_debug('Options sanitized', 'settings');
        return $sanitized;
    }

    // ========================================
    // РАЗДЕЛ 7: AJAX Handlers
    // ========================================

    /**
     * ВАЖНО: AJAX handler за запазване на настройки
     */
    public function ajax_save_settings() {
        // Security check
        if (!check_ajax_referer('parfume_admin_nonce', 'nonce', false)) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $sanitized = $this->sanitize_options($settings);
        
        $updated = update_option($this->option_name, $sanitized);
        
        if ($updated) {
            $this->cached_options = null; // Reset cache
            wp_send_json_success(__('Settings saved successfully', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Failed to save settings', 'parfume-reviews'));
        }
    }

    /**
     * ВАЖНО: AJAX handler за reset на настройки
     */
    public function ajax_reset_settings() {
        // Security check
        if (!check_ajax_referer('parfume_admin_nonce', 'nonce', false)) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $reset = update_option($this->option_name, $this->default_options);
        
        if ($reset) {
            $this->cached_options = null; // Reset cache
            wp_send_json_success(__('Settings reset to defaults', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Failed to reset settings', 'parfume-reviews'));
        }
    }

    /**
     * ВАЖНО: AJAX handler за експорт на настройки
     */
    public function ajax_export_settings() {
        // Security check
        if (!check_ajax_referer('parfume_admin_nonce', 'nonce', false)) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $options = $this->get_options();
        $export_data = array(
            'version' => PARFUME_REVIEWS_VERSION,
            'export_date' => current_time('mysql'),
            'options' => $options
        );
        
        wp_send_json_success(array(
            'data' => base64_encode(json_encode($export_data)),
            'filename' => 'parfume-reviews-settings-' . date('Y-m-d') . '.json'
        ));
    }

    /**
     * ВАЖНО: AJAX handler за импорт на настройки
     */
    public function ajax_import_settings() {
        // Security check
        if (!check_ajax_referer('parfume_admin_nonce', 'nonce', false)) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $import_data = isset($_POST['import_data']) ? $_POST['import_data'] : '';
        
        if (empty($import_data)) {
            wp_send_json_error(__('No import data provided', 'parfume-reviews'));
        }
        
        $decoded_data = json_decode(base64_decode($import_data), true);
        
        if (!$decoded_data || !isset($decoded_data['options'])) {
            wp_send_json_error(__('Invalid import data format', 'parfume-reviews'));
        }
        
        $sanitized = $this->sanitize_options($decoded_data['options']);
        $imported = update_option($this->option_name, $sanitized);
        
        if ($imported) {
            $this->cached_options = null; // Reset cache
            wp_send_json_success(__('Settings imported successfully', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Failed to import settings', 'parfume-reviews'));
        }
    }

    // ========================================
    // РАЗДЕЛ 8: UI Rendering Methods
    // ========================================

    /**
     * ВАЖНО: Рендиране на dashboard widgets
     */
    private function render_dashboard_widgets() {
        ?>
        <div class="parfume-dashboard-widgets">
            <div class="widget">
                <h3><?php esc_html_e('Quick Stats', 'parfume-reviews'); ?></h3>
                <?php $this->render_quick_stats(); ?>
            </div>
            
            <div class="widget">
                <h3><?php esc_html_e('Recent Activity', 'parfume-reviews'); ?></h3>
                <?php $this->render_recent_activity(); ?>
            </div>
            
            <div class="widget">
                <h3><?php esc_html_e('System Status', 'parfume-reviews'); ?></h3>
                <?php $this->render_system_status(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * ВАЖНО: Рендиране на settings tabs
     */
    private function render_settings_tabs() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        $tabs = array(
            'general' => __('General', 'parfume-reviews'),
            'display' => __('Display', 'parfume-reviews'),
            'advanced' => __('Advanced', 'parfume-reviews')
        );
        
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab_key => $tab_label) {
            $active = $current_tab === $tab_key ? ' nav-tab-active' : '';
            printf(
                '<a href="?page=parfume-reviews-settings&tab=%s" class="nav-tab%s">%s</a>',
                esc_attr($tab_key),
                esc_attr($active),
                esc_html($tab_label)
            );
        }
        echo '</h2>';
    }

    /**
     * ВАЖНО: Рендиране на tools sections
     */
    private function render_tools_sections() {
        ?>
        <div class="parfume-tools-sections">
            <div class="tool-section">
                <h3><?php esc_html_e('Import/Export', 'parfume-reviews'); ?></h3>
                <?php $this->render_import_export_tools(); ?>
            </div>
            
            <div class="tool-section">
                <h3><?php esc_html_e('Maintenance', 'parfume-reviews'); ?></h3>
                <?php $this->render_maintenance_tools(); ?>
            </div>
            
            <div class="tool-section">
                <h3><?php esc_html_e('Debug Information', 'parfume-reviews'); ?></h3>
                <?php $this->render_debug_info(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * ВАЖНО: Рендиране на quick stats
     */
    private function render_quick_stats() {
        $review_count = wp_count_posts('parfume_review');
        $published_reviews = isset($review_count->publish) ? $review_count->publish : 0;
        
        ?>
        <div class="quick-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($published_reviews); ?></span>
                <span class="stat-label"><?php esc_html_e('Published Reviews', 'parfume-reviews'); ?></span>
            </div>
            <!-- Добавяне на други статистики -->
        </div>
        <?php
    }

    /**
     * ВАЖНО: Рендиране на recent activity
     */
    private function render_recent_activity() {
        $recent_posts = get_posts(array(
            'post_type' => 'parfume_review',
            'numberposts' => 5,
            'post_status' => 'publish'
        ));
        
        if ($recent_posts) {
            echo '<ul class="recent-activity-list">';
            foreach ($recent_posts as $post) {
                printf(
                    '<li><a href="%s">%s</a> - %s</li>',
                    esc_url(get_edit_post_link($post->ID)),
                    esc_html($post->post_title),
                    esc_html(human_time_diff(strtotime($post->post_date), current_time('timestamp')))
                );
            }
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__('No recent activity', 'parfume-reviews') . '</p>';
        }
    }

    /**
     * ВАЖНО: Рендиране на system status
     */
    private function render_system_status() {
        $checks = array(
            'WordPress Version' => get_bloginfo('version'),
            'PHP Version' => PHP_VERSION,
            'Plugin Version' => defined('PARFUME_REVIEWS_VERSION') ? PARFUME_REVIEWS_VERSION : 'Unknown'
        );
        
        echo '<div class="system-status">';
        foreach ($checks as $check => $value) {
            printf('<p><strong>%s:</strong> %s</p>', esc_html($check), esc_html($value));
        }
        echo '</div>';
    }

    /**
     * ВАЖНО: Рендиране на import/export tools
     */
    private function render_import_export_tools() {
        ?>
        <div class="import-export-tools">
            <button type="button" class="button" id="export-settings">
                <?php esc_html_e('Export Settings', 'parfume-reviews'); ?>
            </button>
            
            <input type="file" id="import-file" accept=".json" style="display: none;">
            <button type="button" class="button" id="import-settings">
                <?php esc_html_e('Import Settings', 'parfume-reviews'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * ВАЖНО: Рендиране на maintenance tools
     */
    private function render_maintenance_tools() {
        ?>
        <div class="maintenance-tools">
            <button type="button" class="button" id="clear-cache">
                <?php esc_html_e('Clear Cache', 'parfume-reviews'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="reset-settings">
                <?php esc_html_e('Reset Settings', 'parfume-reviews'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * ВАЖНО: Рендиране на debug информация
     */
    private function render_debug_info() {
        if (!$this->get_option('enable_debug')) {
            echo '<p>' . esc_html__('Debug mode is disabled', 'parfume-reviews') . '</p>';
            return;
        }
        
        $debug_info = array(
            'Active Theme' => get_template(),
            'Active Plugins' => count(get_option('active_plugins', array())),
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time')
        );
        
        echo '<div class="debug-info">';
        foreach ($debug_info as $key => $value) {
            printf('<p><strong>%s:</strong> %s</p>', esc_html($key), esc_html($value));
        }
        echo '</div>';
    }

    /**
     * ВАЖНО: Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Само на админ страниците на плъгина
        if (strpos($hook, 'parfume-reviews') === false) {
            return;
        }
        
        wp_enqueue_script(
            'parfume-admin-settings',
            PARFUME_REVIEWS_URL . 'assets/js/admin-settings.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'parfume-admin-settings',
            PARFUME_REVIEWS_URL . 'assets/css/admin-settings.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        // Localization
        wp_localize_script('parfume-admin-settings', 'parfumeAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_nonce'),
            'strings' => array(
                'saved' => __('Settings saved', 'parfume-reviews'),
                'error' => __('Error occurred', 'parfume-reviews'),
                'confirm_reset' => __('Are you sure you want to reset all settings?', 'parfume-reviews')
            )
        ));
    }

    // ========================================
    // РАЗДЕЛ 9: Utility Methods
    // ========================================

    /**
     * ВАЖНО: Проверка дали опция е активна
     * 
     * @param string $key
     * @return bool
     */
    public function is_option_enabled($key) {
        return (bool) $this->get_option($key);
    }

    /**
     * ВАЖНО: Получаване на default стойност за опция
     * 
     * @param string $key
     * @return mixed
     */
    public function get_default_option($key) {
        return isset($this->default_options[$key]) ? $this->default_options[$key] : null;
    }

    /**
     * ВАЖНО: Валидация на опция
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function validate_option($key, $value) {
        switch ($key) {
            case 'reviews_per_page':
            case 'cache_duration':
                return is_numeric($value) && $value > 0;
                
            case 'layout_style':
                return in_array($value, array('grid', 'list', 'masonry'));
                
            case 'backup_frequency':
                return in_array($value, array('daily', 'weekly', 'monthly'));
                
            case 'export_format':
                return in_array($value, array('json', 'csv'));
                
            default:
                return true;
        }
    }

    /**
     * ВАЖНО: Backup на настройки преди промяна
     */
    public function backup_settings() {
        $current_options = $this->get_options();
        $backup_key = 'parfume_reviews_backup_' . time();
        
        update_option($backup_key, $current_options);
        parfume_reviews_debug("Settings backed up to: {$backup_key}", 'settings');
        
        return $backup_key;
    }

    /**
     * ВАЖНО: Restore на настройки от backup
     * 
     * @param string $backup_key
     * @return bool
     */
    public function restore_settings($backup_key) {
        $backup_options = get_option($backup_key);
        
        if (!$backup_options) {
            return false;
        }
        
        $restored = update_option($this->option_name, $backup_options);
        
        if ($restored) {
            $this->cached_options = null; // Reset cache
            parfume_reviews_debug("Settings restored from: {$backup_key}", 'settings');
        }
        
        return $restored;
    }

    /**
     * ВАЖНО: Изчистване на кеша
     */
    public function clear_cache() {
        $this->cached_options = null;
        parfume_reviews_debug('Settings cache cleared', 'settings');
    }

    /**
     * ВАЖНО: Debug helper метод
     * 
     * @param string $key Optional specific key to debug
     */
    public function debug_options($key = null) {
        if (!$this->is_option_enabled('enable_debug')) {
            return;
        }
        
        if ($key) {
            $value = $this->get_option($key);
            parfume_reviews_debug("Option {$key}: " . print_r($value, true), 'settings');
        } else {
            $all_options = $this->get_options();
            parfume_reviews_debug("All options: " . print_r($all_options, true), 'settings');
        }
    }

} // End class

// ВАЖНО: Глобална функция за лесен достъп до настройки
if (!function_exists('parfume_reviews_get_option')) {
    /**
     * ВАЖНО: Helper функция за получаване на опция
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function parfume_reviews_get_option($key, $default = null) {
        $settings = Parfume_Reviews_Settings::get_instance();
        $value = $settings->get_option($key);
        
        return $value !== null ? $value : $default;
    }
}

// ВАЖНО: Глобална функция за проверка дали опция е активна
if (!function_exists('parfume_reviews_is_option_enabled')) {
    /**
     * ВАЖНО: Helper функция за проверка дали опция е активна
     * 
     * @param string $key
     * @return bool
     */
    function parfume_reviews_is_option_enabled($key) {
        $settings = Parfume_Reviews_Settings::get_instance();
        return $settings->is_option_enabled($key);
    }
}