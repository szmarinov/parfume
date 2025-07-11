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
        update_option('parfume_catalog_activated', true);
        update_option('parfume_catalog_version', $this->version);
        update_option('parfume_catalog_db_version', $this->db_version);
        
        // Create default options
        $this->create_default_options();
        
        // Create custom database tables if needed
        $this->create_custom_tables();
        
        // Set rewrite flush flag
        set_transient('parfume_catalog_flush_rewrite_rules', true, 30);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('parfume_catalog_scraper_cron');
        wp_clear_scheduled_hook('parfume_catalog_cleanup_cron');
        
        // Clean up transients
        $this->cleanup_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option('parfume_catalog_activated', false);
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        $this->load_textdomain();
        
        // Check if rewrite rules need to be flushed
        if (get_transient('parfume_catalog_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_transient('parfume_catalog_flush_rewrite_rules');
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'parfume-catalog',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * WordPress loaded hook
     */
    public function wp_loaded() {
        // Plugin fully loaded - final initialization
        do_action('parfume_catalog_loaded');
    }
    
    /**
     * Admin init hook
     */
    public function admin_init() {
        // Check for plugin updates
        $this->check_for_updates();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only load on parfume-related pages
        if (is_singular('parfumes') || is_post_type_archive('parfumes') || 
            is_tax('parfume_type') || is_tax('parfume_vid') || is_tax('parfume_marki') || 
            is_tax('parfume_season') || is_tax('parfume_intensity') || is_tax('parfume_notes')) {
            
            // Main frontend CSS
            wp_enqueue_style(
                'parfume-catalog-frontend',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_CATALOG_VERSION
            );
            
            // Mobile CSS
            wp_enqueue_style(
                'parfume-catalog-mobile',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/mobile.css',
                array('parfume-catalog-frontend'),
                PARFUME_CATALOG_VERSION,
                'screen and (max-width: 768px)'
            );
            
            // Main frontend JS
            wp_enqueue_script(
                'parfume-catalog-frontend',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_CATALOG_VERSION,
                true
            );
            
            // Comparison functionality
            if (parfume_catalog_is_comparison_enabled()) {
                wp_enqueue_script(
                    'parfume-catalog-comparison',
                    PARFUME_CATALOG_PLUGIN_URL . 'assets/js/comparison.js',
                    array('jquery', 'parfume-catalog-frontend'),
                    PARFUME_CATALOG_VERSION,
                    true
                );
            }
            
            // Mobile stores functionality
            wp_enqueue_script(
                'parfume-catalog-mobile-stores',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/mobile-stores.js',
                array('jquery'),
                PARFUME_CATALOG_VERSION,
                true
            );
            
            // Localize scripts
            wp_localize_script('parfume-catalog-frontend', 'parfume_catalog_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_catalog_nonce'),
                'strings' => array(
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'error' => __('Грешка при зареждане', 'parfume-catalog'),
                    'no_results' => __('Няма резултати', 'parfume-catalog'),
                    'add_to_comparison' => __('Добави за сравнение', 'parfume-catalog'),
                    'remove_from_comparison' => __('Премахни от сравнение', 'parfume-catalog'),
                    'view_comparison' => __('Виж сравнение', 'parfume-catalog'),
                    'close' => __('Затвори', 'parfume-catalog'),
                    'copied' => __('Копирано!', 'parfume-catalog')
                )
            ));
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        // Load on all parfume-related admin pages
        if ($post_type === 'parfumes' || strpos($hook, 'parfume-catalog') !== false) {
            wp_enqueue_style(
                'parfume-catalog-admin',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                PARFUME_CATALOG_VERSION
            );
            
            wp_enqueue_script(
                'parfume-catalog-admin',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                PARFUME_CATALOG_VERSION,
                true
            );
            
            wp_localize_script('parfume-catalog-admin', 'parfume_catalog_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_catalog_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Сигурни ли сте?', 'parfume-catalog'),
                    'saving' => __('Запазване...', 'parfume-catalog'),
                    'saved' => __('Запазено!', 'parfume-catalog'),
                    'error' => __('Грешка!', 'parfume-catalog')
                )
            ));
        }
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_catalog_nonce')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'add_to_comparison':
                $this->handle_add_to_comparison();
                break;
                
            case 'remove_from_comparison':
                $this->handle_remove_from_comparison();
                break;
                
            case 'load_comparison_popup':
                $this->handle_load_comparison_popup();
                break;
                
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * Handle add to comparison
     */
    private function handle_add_to_comparison() {
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfumes') {
            wp_send_json_error('Invalid post ID');
        }
        
        // Add to comparison logic here
        wp_send_json_success(array(
            'message' => __('Добавено за сравнение', 'parfume-catalog'),
            'post_id' => $post_id
        ));
    }
    
    /**
     * Handle remove from comparison
     */
    private function handle_remove_from_comparison() {
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        // Remove from comparison logic here
        wp_send_json_success(array(
            'message' => __('Премахнато от сравнение', 'parfume-catalog'),
            'post_id' => $post_id
        ));
    }
    
    /**
     * Handle load comparison popup
     */
    private function handle_load_comparison_popup() {
        // Load comparison popup content logic here
        wp_send_json_success(array(
            'html' => '<div>Comparison popup content</div>'
        ));
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
     * Create default options
     */
    private function create_default_options() {
        $default_options = array(
            'archive_slug' => 'parfiumi',
            'type_slug' => 'parfiumi',
            'vid_slug' => 'parfiumi',
            'marki_slug' => 'parfiumi/marki',
            'season_slug' => 'parfiumi/season',
            'notes_slug' => 'notes',
            'comparison_enabled' => true,
            'comments_enabled' => true,
            'scraper_enabled' => true,
            'related_count' => 4,
            'recent_count' => 4,
            'brand_count' => 4
        );
        
        add_option('parfume_catalog_options', $default_options);
    }
    
    /**
     * Create custom database tables
     */
    private function create_custom_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for store data
        $table_name = $wpdb->prefix . 'parfume_stores';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            logo_url varchar(500),
            affiliate_url varchar(500),
            scraper_schema longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Table for scraped data
        $table_name = $wpdb->prefix . 'parfume_scraped_data';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            store_id bigint(20) unsigned NOT NULL,
            product_url varchar(500) NOT NULL,
            price decimal(10,2),
            old_price decimal(10,2),
            currency varchar(10),
            availability varchar(100),
            shipping_info text,
            variants longtext,
            last_scraped datetime,
            status varchar(50) DEFAULT 'pending',
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY store_id (store_id),
            KEY last_scraped (last_scraped)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Check for plugin updates
     */
    private function check_for_updates() {
        $current_version = get_option('parfume_catalog_version', '0.0.0');
        
        if (version_compare($current_version, PARFUME_CATALOG_VERSION, '<')) {
            $this->perform_update($current_version);
            update_option('parfume_catalog_version', PARFUME_CATALOG_VERSION);
        }
    }
    
    /**
     * Perform plugin update
     */
    private function perform_update($old_version) {
        // Update database structure if needed
        $this->create_custom_tables();
        
        // Flush rewrite rules after update
        set_transient('parfume_catalog_flush_rewrite_rules', true, 30);
    }
    
    /**
     * Get parfume brand name
     */
    private function get_parfume_brand($post_id) {
        $brands = get_the_terms($post_id, 'parfume_marki');
        return $brands && !is_wp_error($brands) ? $brands[0]->name : '';
    }
    
    /**
     * Get parfume type
     */
    private function get_parfume_type($post_id) {
        $types = get_the_terms($post_id, 'parfume_type');
        return $types && !is_wp_error($types) ? $types[0]->name : '';
    }
    
    /**
     * Get parfume notes
     */
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