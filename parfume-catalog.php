<?php
/**
 * Plugin Name: Parfume Catalog
 * Plugin URI: https://example.com/parfume-catalog
 * Description: Пълноценен WordPress плъгин за управление, каталогизиране и ревю на парфюми с интерактивни функции.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: parfume-catalog
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PARFUME_CATALOG_VERSION', '1.0.0');
define('PARFUME_CATALOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARFUME_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PARFUME_CATALOG_PLUGIN_FILE', __FILE__);
define('PARFUME_CATALOG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Parfume_Catalog {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Plugin version
     */
    private $version = PARFUME_CATALOG_VERSION;
    
    /**
     * Database version
     */
    private $db_version = '1.0.0';
    
    /**
     * Plugin activated flag
     */
    private $plugin_activated = false;
    
    /**
     * Loaded modules tracking
     */
    private $loaded_modules = array();
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_modules();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Core hooks
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_loaded', array($this, 'wp_loaded'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX actions
        add_action('wp_ajax_parfume_catalog_action', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_parfume_catalog_action', array($this, 'handle_ajax_request'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Основни класове
        $this->require_file('includes/class-admin.php');
        $this->require_file('includes/class-post-types.php');
        $this->require_file('includes/class-meta-fields.php');
        $this->require_file('includes/class-template-loader.php');
        
        // Модули
        $this->require_file('modules/class-stores.php');
        $this->require_file('modules/class-scraper.php');
        $this->require_file('modules/class-scraper-test-tool.php');
        $this->require_file('modules/class-scraper-monitor.php');
        $this->require_file('modules/class-comparison.php');
        $this->require_file('modules/class-comments.php');
        $this->require_file('modules/class-filters.php');
        $this->require_file('modules/class-schema.php');
        $this->require_file('modules/class-blog.php');
        
        // Админ модули
        $this->require_file('modules/admin/class-admin-settings.php');
        $this->require_file('modules/admin/class-admin-stores.php');
        $this->require_file('modules/admin/class-admin-scraper.php');
        $this->require_file('modules/admin/class-admin-comparison.php');
        $this->require_file('modules/admin/class-admin-comments.php');
        
        // Мета полета модули
        $this->require_file('modules/meta-fields/class-meta-basic.php');
        $this->require_file('modules/meta-fields/class-meta-stores.php');
        $this->require_file('modules/meta-fields/class-meta-notes.php');
        $this->require_file('modules/meta-fields/class-meta-stats.php');
    }
    
    /**
     * Safely require file
     */
    private function require_file($relative_path) {
        $file_path = PARFUME_CATALOG_PLUGIN_DIR . $relative_path;
        
        if (file_exists($file_path)) {
            require_once $file_path;
            
            // Track loaded files for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->loaded_modules[] = $relative_path;
            }
        } else {
            // Log missing file
            error_log("Parfume Catalog: Missing file - " . $relative_path);
            
            // Show admin notice for critical files
            if (is_admin()) {
                add_action('admin_notices', function() use ($relative_path) {
                    echo '<div class="notice notice-error"><p>';
                    echo sprintf(__('Parfume Catalog: Missing critical file - %s', 'parfume-catalog'), $relative_path);
                    echo '</p></div>';
                });
            }
        }
    }
    
    /**
     * Initialize modules
     */
    private function init_modules() {
        // Initialize core modules only after all files are loaded
        add_action('wp_loaded', array($this, 'initialize_core_modules'));
    }
    
    /**
     * Initialize core modules
     */
    public function initialize_core_modules() {
        // Initialize modules only if their classes exist
        if (class_exists('Parfume_Catalog_Admin')) {
            new Parfume_Catalog_Admin();
        }
        
        if (class_exists('Parfume_Catalog_Post_Types')) {
            new Parfume_Catalog_Post_Types();
        }
        
        if (class_exists('Parfume_Catalog_Meta_Fields')) {
            new Parfume_Catalog_Meta_Fields();
        }
        
        if (class_exists('Parfume_Catalog_Template_Loader')) {
            new Parfume_Catalog_Template_Loader();
        }
        
        // Initialize other modules
        $this->initialize_optional_modules();
    }
    
    /**
     * Initialize optional modules
     */
    private function initialize_optional_modules() {
        $modules = array(
            'Parfume_Catalog_Stores',
            'Parfume_Catalog_Scraper',
            'Parfume_Catalog_Comparison',
            'Parfume_Catalog_Comments',
            'Parfume_Catalog_Filters',
            'Parfume_Catalog_Schema',
            'Parfume_Catalog_Blog'
        );
        
        foreach ($modules as $module_class) {
            if (class_exists($module_class)) {
                new $module_class();
            }
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check WordPress version
        if (!$this->check_wordpress_version()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Parfume Catalog изисква WordPress версия 5.0 или по-нова.', 'parfume-catalog'));
        }
        
        // Check PHP version
        if (!$this->check_php_version()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Parfume Catalog изисква PHP версия 7.4 или по-нова.', 'parfume-catalog'));
        }
        
        // Set activation flag
        $this->plugin_activated = true;
        
        // Save plugin version
        update_option('parfume_catalog_version', $this->version);
        update_option('parfume_catalog_db_version', $this->db_version);
        
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
        
        // Log activation
        error_log('Parfume Catalog plugin activated successfully');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        $this->clear_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clean up transients
        $this->cleanup_transients();
        
        // Log deactivation
        error_log('Parfume Catalog plugin deactivated');
    }
    
    /**
     * Check WordPress version
     */
    private function check_wordpress_version() {
        global $wp_version;
        return version_compare($wp_version, '5.0', '>=');
    }
    
    /**
     * Check PHP version
     */
    private function check_php_version() {
        return version_compare(PHP_VERSION, '7.4', '>=');
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        $this->load_textdomain();
        
        // Register post types and taxonomies
        $this->register_post_types_and_taxonomies();
        
        // Add custom image sizes
        $this->add_image_sizes();
        
        // Register shortcodes
        $this->register_shortcodes();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'parfume-catalog',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * WordPress loaded
     */
    public function wp_loaded() {
        // Plugin fully loaded
        do_action('parfume_catalog_loaded');
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Check for plugin updates
        $this->check_for_updates();
        
        // Add admin capabilities
        $this->add_admin_capabilities();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue on relevant pages
        if (!$this->should_enqueue_frontend_scripts()) {
            return;
        }
        
        // Check if CSS file exists before enqueuing
        $css_file = PARFUME_CATALOG_PLUGIN_DIR . 'assets/css/frontend.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'parfume-catalog-frontend',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                $this->version
            );
        }
        
        // Check if JS file exists before enqueuing
        $js_file = PARFUME_CATALOG_PLUGIN_DIR . 'assets/js/frontend.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'parfume-catalog-frontend',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                $this->version,
                true
            );
            
            // Localize script
            wp_localize_script('parfume-catalog-frontend', 'parfume_catalog_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_catalog_nonce'),
                'strings' => array(
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'error' => __('Грешка при зареждане', 'parfume-catalog'),
                    'success' => __('Успешно!', 'parfume-catalog')
                )
            ));
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on plugin admin pages
        if (!$this->is_plugin_admin_page($hook)) {
            return;
        }
        
        // Check if CSS file exists before enqueuing
        $css_file = PARFUME_CATALOG_PLUGIN_DIR . 'assets/css/admin.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'parfume-catalog-admin',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                $this->version
            );
        }
        
        // Check if JS file exists before enqueuing
        $js_file = PARFUME_CATALOG_PLUGIN_DIR . 'assets/js/admin.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'parfume-catalog-admin',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                $this->version,
                true
            );
            
            // Localize script
            wp_localize_script('parfume-catalog-admin', 'parfume_catalog_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_catalog_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Сигурни ли сте, че искате да изтриете това?', 'parfume-catalog'),
                    'processing' => __('Обработка...', 'parfume-catalog'),
                    'error' => __('Възникна грешка', 'parfume-catalog')
                )
            ));
        }
    }
    
    /**
     * Check if should enqueue frontend scripts
     */
    private function should_enqueue_frontend_scripts() {
        return (
            is_singular('parfumes') ||
            is_post_type_archive('parfumes') ||
            is_tax('parfume_type') ||
            is_tax('parfume_vid') ||
            is_tax('parfume_marki') ||
            is_tax('parfume_season') ||
            is_tax('parfume_intensity') ||
            is_tax('parfume_notes') ||
            is_page() // For pages that might have shortcodes
        );
    }
    
    /**
     * Check if current page is plugin admin page
     */
    private function is_plugin_admin_page($hook) {
        $plugin_pages = array(
            'toplevel_page_parfume-catalog',
            'parfume-catalog_page_parfume-catalog-settings',
            'parfume-catalog_page_parfume-catalog-stores',
            'parfume-catalog_page_parfume-catalog-scraper',
            'parfume-catalog_page_parfume-catalog-comparison',
            'parfume-catalog_page_parfume-catalog-comments',
            'parfume-catalog_page_parfume-catalog-blog',
            'parfume-catalog_page_parfume-catalog-docs'
        );
        
        return in_array($hook, $plugin_pages) || 
               (isset($_GET['post_type']) && $_GET['post_type'] === 'parfumes');
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_catalog_nonce')) {
            wp_die(__('Нарушение на сигурността', 'parfume-catalog'));
        }
        
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        switch ($action) {
            case 'get_parfumes':
                $this->ajax_get_parfumes();
                break;
            case 'add_to_comparison':
                $this->ajax_add_to_comparison();
                break;
            case 'remove_from_comparison':
                $this->ajax_remove_from_comparison();
                break;
            default:
                wp_send_json_error(__('Невалидно действие', 'parfume-catalog'));
        }
    }
    
    /**
     * AJAX get parfumes
     */
    private function ajax_get_parfumes() {
        // Implementation for getting parfumes via AJAX
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => 12,
            'post_status' => 'publish'
        );
        
        $query = new WP_Query($args);
        $parfumes = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $parfumes[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'brand' => $this->get_parfume_brand(get_the_ID()),
                    'type' => $this->get_parfume_type(get_the_ID()),
                    'notes' => $this->get_parfume_notes(get_the_ID(), 3)
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($parfumes);
    }
    
    /**
     * AJAX add to comparison
     */
    private function ajax_add_to_comparison() {
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id || get_post_type($post_id) !== 'parfumes') {
            wp_send_json_error(__('Невалиден парфюм', 'parfume-catalog'));
        }
        
        // This would be handled by the comparison module
        wp_send_json_success(array(
            'message' => __('Парфюмът е добавен за сравнение', 'parfume-catalog'),
            'post_id' => $post_id
        ));
    }
    
    /**
     * AJAX remove from comparison
     */
    private function ajax_remove_from_comparison() {
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(__('Невалиден парфюм', 'parfume-catalog'));
        }
        
        // This would be handled by the comparison module
        wp_send_json_success(array(
            'message' => __('Парфюмът е премахнат от сравнение', 'parfume-catalog'),
            'post_id' => $post_id
        ));
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create tables only if they don't exist
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Log table creation
        error_log('Parfume Catalog: Creating database tables');
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'archive_slug' => 'parfiumi',
            'type_slug' => 'tip',
            'brand_slug' => 'marki',
            'season_slug' => 'season',
            'notes_slug' => 'notes',
            'comparison_enabled' => true,
            'comments_enabled' => true,
            'scraper_enabled' => true,
            'scraper_interval' => 12
        );
        
        add_option('parfume_catalog_options', $default_options);
    }
    
    /**
     * Register post types and taxonomies
     */
    private function register_post_types_and_taxonomies() {
        // This will be handled by the Post Types class
        do_action('parfume_catalog_register_post_types');
    }
    
    /**
     * Add custom image sizes
     */
    private function add_image_sizes() {
        add_image_size('parfume-thumbnail', 300, 400, true);
        add_image_size('parfume-medium', 600, 800, true);
        add_image_size('parfume-large', 900, 1200, true);
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('parfume_list', array($this, 'parfume_list_shortcode'));
        add_shortcode('parfume_brands', array($this, 'parfume_brands_shortcode'));
        add_shortcode('parfume_notes', array($this, 'parfume_notes_shortcode'));
        add_shortcode('parfume_comparison', array($this, 'parfume_comparison_shortcode'));
    }
    
    /**
     * Shortcode for parfume list
     */
    public function parfume_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'brand' => '',
            'type' => '',
            'season' => ''
        ), $atts);
        
        ob_start();
        
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );
        
        // Add taxonomy queries based on shortcode attributes
        if (!empty($atts['brand'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'parfume_marki',
                'field' => 'slug',
                'terms' => $atts['brand']
            );
        }
        
        if (!empty($atts['type'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'parfume_type',
                'field' => 'slug',
                'terms' => $atts['type']
            );
        }
        
        if (!empty($atts['season'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'parfume_season',
                'field' => 'slug',
                'terms' => $atts['season']
            );
        }
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            echo '<div class="parfume-catalog-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_parfume_item(get_the_ID());
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('Няма намерени парфюми.', 'parfume-catalog') . '</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode for brands
     */
    public function parfume_brands_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'orderby' => 'name',
            'order' => 'ASC'
        ), $atts);
        
        $brands = get_terms(array(
            'taxonomy' => 'parfume_marki',
            'hide_empty' => true,
            'number' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        ));
        
        if (!$brands || is_wp_error($brands)) {
            return '<p>' . __('Няма намерени марки.', 'parfume-catalog') . '</p>';
        }
        
        ob_start();
        echo '<div class="parfume-brands-list">';
        foreach ($brands as $brand) {
            echo '<a href="' . get_term_link($brand) . '" class="parfume-brand-item">';
            echo esc_html($brand->name);
            echo ' <span class="count">(' . $brand->count . ')</span>';
            echo '</a>';
        }
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode for notes
     */
    public function parfume_notes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'group' => '',
            'limit' => -1
        ), $atts);
        
        $args = array(
            'taxonomy' => 'parfume_notes',
            'hide_empty' => true,
            'number' => intval($atts['limit'])
        );
        
        if (!empty($atts['group'])) {
            $args['meta_query'] = array(
                array(
                    'key' => 'note_group',
                    'value' => $atts['group'],
                    'compare' => '='
                )
            );
        }
        
        $notes = get_terms($args);
        
        if (!$notes || is_wp_error($notes)) {
            return '<p>' . __('Няма намерени нотки.', 'parfume-catalog') . '</p>';
        }
        
        ob_start();
        echo '<div class="parfume-notes-list">';
        foreach ($notes as $note) {
            echo '<span class="parfume-note-item">';
            echo esc_html($note->name);
            echo '</span>';
        }
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode for comparison
     */
    public function parfume_comparison_shortcode($atts) {
        if (!class_exists('Parfume_Catalog_Comparison')) {
            return '<p>' . __('Модулът за сравнение не е активен.', 'parfume-catalog') . '</p>';
        }
        
        // This would be handled by the comparison module
        return '<div id="parfume-comparison-widget"></div>';
    }
    
    /**
     * Render parfume item
     */
    private function render_parfume_item($post_id) {
        $brand = $this->get_parfume_brand($post_id);
        $type = $this->get_parfume_type($post_id);
        $notes = $this->get_parfume_notes($post_id, 3);
        
        echo '<div class="parfume-item" data-post-id="' . $post_id . '">';
        echo '<div class="parfume-image">';
        if (has_post_thumbnail($post_id)) {
            echo '<a href="' . get_permalink($post_id) . '">';
            echo get_the_post_thumbnail($post_id, 'parfume-thumbnail');
            echo '</a>';
        }
        echo '</div>';
        
        echo '<div class="parfume-content">';
        echo '<h3 class="parfume-title"><a href="' . get_permalink($post_id) . '">' . get_the_title($post_id) . '</a></h3>';
        
        if ($brand) {
            echo '<div class="parfume-brand">' . esc_html($brand) . '</div>';
        }
        
        if ($type) {
            echo '<div class="parfume-type">' . esc_html($type) . '</div>';
        }
        
        if (!empty($notes)) {
            echo '<div class="parfume-notes">';
            echo '<span class="notes-label">' . __('Нотки:', 'parfume-catalog') . '</span>';
            echo implode(', ', $notes);
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        // Schedule scraper cron job
        if (!wp_next_scheduled('parfume_catalog_scraper_cron')) {
            wp_schedule_event(time(), 'twicedaily', 'parfume_catalog_scraper_cron');
        }
        
        // Schedule cleanup cron job
        if (!wp_next_scheduled('parfume_catalog_cleanup_cron')) {
            wp_schedule_event(time(), 'daily', 'parfume_catalog_cleanup_cron');
        }
    }
    
    /**
     * Clear cron jobs
     */
    private function clear_cron_jobs() {
        wp_clear_scheduled_hook('parfume_catalog_scraper_cron');
        wp_clear_scheduled_hook('parfume_catalog_cleanup_cron');
    }
    
    /**
     * Check for updates
     */
    private function check_for_updates() {
        $current_version = get_option('parfume_catalog_version', '0.0.0');
        
        if (version_compare($current_version, $this->version, '<')) {
            $this->perform_update($current_version);
        }
    }
    
    /**
     * Perform update
     */
    private function perform_update($from_version) {
        // Update database if needed
        $this->update_database($from_version);
        
        // Update options
        update_option('parfume_catalog_version', $this->version);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log update
        error_log("Parfume Catalog updated from {$from_version} to {$this->version}");
    }
    
    /**
     * Update database
     */
    private function update_database($from_version) {
        // Database update logic based on version
        if (version_compare($from_version, '1.0.0', '<')) {
            // Updates for version 1.0.0
        }
    }
    
    /**
     * Add admin capabilities
     */
    private function add_admin_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_parfume_catalog');
            $role->add_cap('edit_parfumes');
            $role->add_cap('edit_others_parfumes');
            $role->add_cap('publish_parfumes');
            $role->add_cap('read_private_parfumes');
            $role->add_cap('delete_parfumes');
            $role->add_cap('delete_private_parfumes');
            $role->add_cap('delete_published_parfumes');
            $role->add_cap('delete_others_parfumes');
            $role->add_cap('edit_private_parfumes');
            $role->add_cap('edit_published_parfumes');
        }
    }
    
    /**
     * Helper functions
     */
    private function get_parfume_brand($post_id) {
        $brands = get_the_terms($post_id, 'parfume_marki');
        return $brands && !is_wp_error($brands) ? $brands[0]->name : '';
    }
    
    private function get_parfume_type($post_id) {
        $types = get_the_terms($post_id, 'parfume_type');
        return $types && !is_wp_error($types) ? $types[0]->name : '';
    }
    
    private function get_parfume_notes($post_id, $limit = 5) {
        $notes = get_the_terms($post_id, 'parfume_notes');
        if (!$notes || is_wp_error($notes)) {
            return array();
        }
        
        $note_names = array();
        foreach (array_slice($notes, 0, $limit) as $note) {
            $note_names[] = $note->name;
        }
        
        return $note_names;
    }
    
    /**
     * Cleanup transients
     */
    private function cleanup_transients() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_parfume_catalog_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_parfume_catalog_%'");
    }
    
    /**
     * Get plugin info
     */
    public function get_plugin_info() {
        return array(
            'version' => PARFUME_CATALOG_VERSION,
            'activated' => $this->plugin_activated,
            'db_version' => $this->db_version,
            'loaded_modules' => $this->loaded_modules,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        );
    }
}

// Initialize plugin
function parfume_catalog_init() {
    return Parfume_Catalog::get_instance();
}

// Start plugin
parfume_catalog_init();

// Helper functions for templates
function parfume_catalog_get_brand($post_id) {
    $brands = get_the_terms($post_id, 'parfume_marki');
    return $brands && !is_wp_error($brands) ? $brands[0] : false;
}

function parfume_catalog_get_type($post_id) {
    $types = get_the_terms($post_id, 'parfume_type');
    return $types && !is_wp_error($types) ? $types[0] : false;
}

function parfume_catalog_get_notes($post_id, $limit = 0) {
    $notes = get_the_terms($post_id, 'parfume_notes');
    if (!$notes || is_wp_error($notes)) {
        return array();
    }
    
    return $limit > 0 ? array_slice($notes, 0, $limit) : $notes;
}

function parfume_catalog_is_comparison_enabled() {
    $settings = get_option('parfume_catalog_comparison_settings', array());
    return isset($settings['enabled']) && $settings['enabled'];
}

function parfume_catalog_get_option($option_name, $default = false) {
    $options = get_option('parfume_catalog_options', array());
    return isset($options[$option_name]) ? $options[$option_name] : $default;
}