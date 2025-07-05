<?php
/**
 * Bootstrap class for Parfume Reviews Plugin
 * Handles initialization of all components
 */

namespace Parfume_Reviews\Core;

use Parfume_Reviews\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

class Bootstrap {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Components registry
     */
    private $components = array();
    
    /**
     * Assets loaded flag
     */
    private $assets_loaded = false;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_components'), 5);
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('template_include', array($this, 'template_loader'));
        
        // AJAX handlers
        add_action('wp_ajax_parfume_filter', array($this, 'handle_ajax_filter'));
        add_action('wp_ajax_nopriv_parfume_filter', array($this, 'handle_ajax_filter'));
    }
    
    /**
     * Register all components
     */
    public function register_components() {
        try {
            $this->register_post_types();
            $this->register_taxonomies();
            $this->register_shortcodes();
            $this->register_meta_boxes();
            
            // Flush rewrite rules if needed
            if (get_option('parfume_reviews_flush_rewrite_rules', false)) {
                flush_rewrite_rules();
                delete_option('parfume_reviews_flush_rewrite_rules');
            }
            
        } catch (Exception $e) {
            error_log('Parfume Reviews Bootstrap Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Register post types
     */
    private function register_post_types() {
        // Parfume Post Type
        if (class_exists('Parfume_Reviews\\PostTypes\\Parfume')) {
            $parfume = new \Parfume_Reviews\PostTypes\Parfume();
            $this->components['parfume'] = $parfume;
        }
        
        // Blog Post Type
        if (class_exists('Parfume_Reviews\\PostTypes\\Blog')) {
            $blog = new \Parfume_Reviews\PostTypes\Blog();
            $this->components['blog'] = $blog;
        }
    }
    
    /**
     * Register taxonomies
     */
    private function register_taxonomies() {
        $taxonomies = array(
            'Brands' => 'Parfume_Reviews\\Taxonomies\\Brands',
            'Notes' => 'Parfume_Reviews\\Taxonomies\\Notes',
            'Perfumers' => 'Parfume_Reviews\\Taxonomies\\Perfumers',
            'Gender' => 'Parfume_Reviews\\Taxonomies\\Gender',
            'AromaType' => 'Parfume_Reviews\\Taxonomies\\AromaType',
            'Season' => 'Parfume_Reviews\\Taxonomies\\Season',
            'Intensity' => 'Parfume_Reviews\\Taxonomies\\Intensity',
        );
        
        foreach ($taxonomies as $name => $class) {
            if (class_exists($class)) {
                try {
                    $instance = new $class();
                    $this->components[strtolower($name)] = $instance;
                } catch (Exception $e) {
                    error_log("Failed to initialize taxonomy {$name}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        $shortcodes = array(
            'Rating' => 'Parfume_Reviews\\Frontend\\Shortcodes\\Rating',
            'Filters' => 'Parfume_Reviews\\Frontend\\Shortcodes\\Filters',
            'Stores' => 'Parfume_Reviews\\Frontend\\Shortcodes\\Stores',
            'Archives' => 'Parfume_Reviews\\Frontend\\Shortcodes\\Archives',
            'Comparison' => 'Parfume_Reviews\\Frontend\\Shortcodes\\Comparison',
            'Blog' => 'Parfume_Reviews\\Frontend\\Shortcodes\\Blog',
        );
        
        foreach ($shortcodes as $name => $class) {
            if (class_exists($class)) {
                try {
                    $instance = new $class();
                    $this->components['shortcode_' . strtolower($name)] = $instance;
                } catch (Exception $e) {
                    error_log("Failed to initialize shortcode {$name}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Register meta boxes
     */
    private function register_meta_boxes() {
        if (class_exists('Parfume_Reviews\\PostTypes\\MetaBoxes')) {
            try {
                $meta_boxes = new \Parfume_Reviews\PostTypes\MetaBoxes();
                $this->components['meta_boxes'] = $meta_boxes;
            } catch (Exception $e) {
                error_log('Failed to initialize meta boxes: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        $capability = 'manage_options';
        
        // Main menu page
        add_menu_page(
            __('Parfume Reviews', 'parfume-reviews'),
            __('Parfumes', 'parfume-reviews'),
            $capability,
            'parfume-reviews',
            array($this, 'admin_page'),
            'dashicons-awards',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'parfume-reviews',
            __('Dashboard', 'parfume-reviews'),
            __('Dashboard', 'parfume-reviews'),
            $capability,
            'parfume-reviews',
            array($this, 'admin_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'parfume-reviews',
            __('Settings', 'parfume-reviews'),
            __('Settings', 'parfume-reviews'),
            $capability,
            'parfume-reviews-settings',
            array($this, 'settings_page')
        );
        
        // Import/Export submenu
        add_submenu_page(
            'parfume-reviews',
            __('Import/Export', 'parfume-reviews'),
            __('Import/Export', 'parfume-reviews'),
            $capability,
            'parfume-reviews-import-export',
            array($this, 'import_export_page')
        );
        
        // Analytics submenu
        add_submenu_page(
            'parfume-reviews',
            __('Analytics', 'parfume-reviews'),
            __('Analytics', 'parfume-reviews'),
            $capability,
            'parfume-reviews-analytics',
            array($this, 'analytics_page')
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        if (class_exists('Parfume_Reviews\\Admin\\Dashboard')) {
            try {
                $dashboard = new \Parfume_Reviews\Admin\Dashboard();
                $dashboard->render();
            } catch (Exception $e) {
                $this->render_fallback_dashboard();
            }
        } else {
            $this->render_fallback_dashboard();
        }
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (class_exists('Parfume_Reviews\\Admin\\Settings')) {
            try {
                $settings = new \Parfume_Reviews\Admin\Settings();
                $settings->render();
            } catch (Exception $e) {
                echo '<div class="wrap"><h1>Settings</h1><p>Settings component is not loaded. Please check if admin/settings.php exists.</p></div>';
            }
        } else {
            echo '<div class="wrap"><h1>Settings</h1><p>Settings component is not loaded. Please check if admin/settings.php exists.</p></div>';
        }
    }
    
    /**
     * Import/Export page
     */
    public function import_export_page() {
        if (class_exists('Parfume_Reviews\\Admin\\ImportExport')) {
            try {
                $import_export = new \Parfume_Reviews\Admin\ImportExport();
                $import_export->render();
            } catch (Exception $e) {
                echo '<div class="wrap"><h1>Import/Export</h1><p>Import/Export component is not loaded. Please check if admin/import-export.php exists.</p></div>';
            }
        } else {
            echo '<div class="wrap"><h1>Import/Export</h1><p>Import/Export component is not loaded. Please check if admin/import-export.php exists.</p></div>';
        }
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        if (class_exists('Parfume_Reviews\\Admin\\Dashboard')) {
            try {
                $dashboard = new \Parfume_Reviews\Admin\Dashboard();
                $dashboard->render_analytics();
            } catch (Exception $e) {
                echo '<div class="wrap"><h1>Analytics</h1><p>Analytics component is not loaded. Please check if admin/dashboard.php exists.</p></div>';
            }
        } else {
            echo '<div class="wrap"><h1>Analytics</h1><p>Analytics component is not loaded. Please check if admin/dashboard.php exists.</p></div>';
        }
    }
    
    /**
     * Fallback dashboard
     */
    private function render_fallback_dashboard() {
        $stats = $this->get_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Parfume Reviews Dashboard', 'parfume-reviews'); ?></h1>
            
            <div class="dashboard-widgets-wrap">
                <div class="dashboard-widgets">
                    
                    <!-- Statistics -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Statistics Overview', 'parfume-reviews'); ?></h2>
                        <div class="inside">
                            <div class="dashboard-stats">
                                <div class="stat-item">
                                    <h3><?php echo esc_html($stats['parfumes']); ?></h3>
                                    <p><?php _e('Total Parfumes', 'parfume-reviews'); ?></p>
                                </div>
                                <div class="stat-item">
                                    <h3><?php echo esc_html($stats['brands']); ?></h3>
                                    <p><?php _e('Brands', 'parfume-reviews'); ?></p>
                                </div>
                                <div class="stat-item">
                                    <h3><?php echo esc_html($stats['notes']); ?></h3>
                                    <p><?php _e('Notes', 'parfume-reviews'); ?></p>
                                </div>
                                <div class="stat-item">
                                    <h3><?php echo esc_html($stats['blog_posts']); ?></h3>
                                    <p><?php _e('Blog Posts', 'parfume-reviews'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Quick Actions', 'parfume-reviews'); ?></h2>
                        <div class="inside">
                            <p>
                                <a href="<?php echo admin_url('post-new.php?post_type=parfume'); ?>" class="button button-primary">
                                    <?php _e('Add New Parfume', 'parfume-reviews'); ?>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=parfume'); ?>" class="button">
                                    <?php _e('Manage Parfumes', 'parfume-reviews'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-item h3 {
            font-size: 2em;
            margin: 0 0 10px;
            color: #0073aa;
        }
        .stat-item p {
            margin: 0;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Get statistics
     */
    private function get_stats() {
        $stats = array(
            'parfumes' => 0,
            'brands' => 0,
            'notes' => 0,
            'blog_posts' => 0,
        );
        
        try {
            // Get parfume count
            $parfume_count = wp_count_posts('parfume');
            if ($parfume_count && property_exists($parfume_count, 'publish')) {
                $stats['parfumes'] = $parfume_count->publish;
            }
            
            // Get blog count
            $blog_count = wp_count_posts('parfume_blog');
            if ($blog_count && property_exists($blog_count, 'publish')) {
                $stats['blog_posts'] = $blog_count->publish;
            }
            
            // Get taxonomy counts
            $brands_count = wp_count_terms(array('taxonomy' => 'marki', 'hide_empty' => false));
            if (!is_wp_error($brands_count)) {
                $stats['brands'] = $brands_count;
            }
            
            $notes_count = wp_count_terms(array('taxonomy' => 'notes', 'hide_empty' => false));
            if (!is_wp_error($notes_count)) {
                $stats['notes'] = $notes_count;
            }
            
        } catch (Exception $e) {
            error_log('Error getting stats: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->assets_loaded && $this->should_load_assets()) {
            $version = PARFUME_REVIEWS_VERSION;
            $url = PARFUME_REVIEWS_PLUGIN_URL;
            
            // CSS files
            wp_enqueue_style('parfume-grid', $url . 'frontend/assets/styles/grid.css', array(), $version);
            wp_enqueue_style('parfume-cards', $url . 'frontend/assets/styles/cards.css', array(), $version);
            wp_enqueue_style('parfume-filters', $url . 'frontend/assets/styles/filters.css', array(), $version);
            wp_enqueue_style('parfume-responsive', $url . 'frontend/assets/styles/responsive.css', array(), $version);
            
            // JavaScript files
            wp_enqueue_script('parfume-filters', $url . 'frontend/assets/scripts/filters.js', array('jquery'), $version, true);
            wp_enqueue_script('parfume-ajax', $url . 'frontend/assets/scripts/ajax.js', array('jquery'), $version, true);
            wp_enqueue_script('parfume-ui', $url . 'frontend/assets/scripts/ui.js', array('jquery'), $version, true);
            
            // Localize scripts
            wp_localize_script('parfume-ajax', 'parfumeAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_nonce'),
            ));
            
            $this->assets_loaded = true;
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'parfume') !== false) {
            $version = PARFUME_REVIEWS_VERSION;
            $url = PARFUME_REVIEWS_PLUGIN_URL;
            
            wp_enqueue_style('parfume-admin', $url . 'frontend/assets/styles/dashboard.css', array(), $version);
            wp_enqueue_script('parfume-admin', $url . 'frontend/assets/scripts/ui.js', array('jquery'), $version, true);
        }
    }
    
    /**
     * Template loader
     */
    public function template_loader($template) {
        if (is_singular('parfume') || is_post_type_archive('parfume') || 
            is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
            
            $plugin_templates = array(
                'single-parfume.php' => is_singular('parfume'),
                'archive-parfume.php' => is_post_type_archive('parfume'),
                'taxonomy-marki.php' => is_tax('marki'),
                'taxonomy-notes.php' => is_tax('notes'),
                'taxonomy-perfumer.php' => is_tax('perfumer'),
                'taxonomy-gender.php' => is_tax('gender'),
                'taxonomy-aroma_type.php' => is_tax('aroma_type'),
                'taxonomy-season.php' => is_tax('season'),
                'taxonomy-intensity.php' => is_tax('intensity'),
            );
            
            foreach ($plugin_templates as $template_name => $condition) {
                if ($condition) {
                    $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
                    if (file_exists($plugin_template)) {
                        return $plugin_template;
                    }
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Handle AJAX filter
     */
    public function handle_ajax_filter() {
        check_ajax_referer('parfume_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        
        // Process filters and return results
        wp_send_json_success(array('html' => 'Filtered results here'));
    }
    
    /**
     * Should load assets
     */
    private function should_load_assets() {
        return is_singular('parfume') || 
               is_post_type_archive('parfume') || 
               is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'));
    }
    
    /**
     * Activation hook
     */
    public function activate() {
        // Register components first
        $this->register_components();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('parfume_reviews_activated', true);
        update_option('parfume_reviews_flush_rewrite_rules', true);
    }
    
    /**
     * Deactivation hook
     */
    public function deactivate() {
        flush_rewrite_rules();
        delete_option('parfume_reviews_activated');
        delete_option('parfume_reviews_flush_rewrite_rules');
    }
    
    /**
     * Get component
     */
    public function get_component($name) {
        return isset($this->components[$name]) ? $this->components[$name] : null;
    }
}