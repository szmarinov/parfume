<?php
/**
 * Dashboard Admin Page for Parfume Reviews Plugin
 * 
 * @package Parfume_Reviews
 * @subpackage Admin
 * @since 1.0.0
 */

namespace Parfume_Reviews\Admin;

use Parfume_Reviews\Utils\Admin_Page_Base;
use Parfume_Reviews\Utils\Helpers;
use Parfume_Reviews\Utils\Cache;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Admin Page Class
 */
class Dashboard extends Admin_Page_Base {
    
    /**
     * Page slug
     */
    protected $page_slug = 'parfume-dashboard';
    
    /**
     * Page capability requirement
     */
    protected $capability = 'edit_posts';
    
    /**
     * Cache key prefix
     */
    private $cache_prefix = 'parfume_dashboard_';
    
    /**
     * Initialize the dashboard functionality
     */
    public function init() {
        parent::init();
        
        // Add dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        
        // Add AJAX handlers
        add_action('wp_ajax_parfume_dashboard_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_parfume_recent_activity', array($this, 'ajax_get_recent_activity'));
        add_action('wp_ajax_parfume_quick_action', array($this, 'ajax_quick_action'));
        
        // Add admin bar menu
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
        
        // Add admin notices for important updates
        add_action('admin_notices', array($this, 'display_dashboard_notices'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        $hook = add_menu_page(
            __('Parfume Dashboard', 'parfume-reviews'),
            __('Parfume Dashboard', 'parfume-reviews'),
            $this->capability,
            $this->page_slug,
            array($this, 'render_page'),
            'dashicons-analytics-pro',
            3
        );
        
        // Add overview submenu
        add_submenu_page(
            $this->page_slug,
            __('Overview', 'parfume-reviews'),
            __('Overview', 'parfume-reviews'),
            $this->capability,
            $this->page_slug,
            array($this, 'render_page')
        );
        
        // Add analytics submenu
        add_submenu_page(
            $this->page_slug,
            __('Analytics', 'parfume-reviews'),
            __('Analytics', 'parfume-reviews'),
            $this->capability,
            'parfume-analytics',
            array($this, 'render_analytics_page')
        );
        
        // Add reports submenu
        add_submenu_page(
            $this->page_slug,
            __('Reports', 'parfume-reviews'),
            __('Reports', 'parfume-reviews'),
            $this->capability,
            'parfume-reports',
            array($this, 'render_reports_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!$this->is_plugin_page($hook)) {
            return;
        }
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_enqueue_style(
            'parfume-dashboard',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/dashboard.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        wp_enqueue_script(
            'parfume-dashboard',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/dashboard.js',
            array('jquery', 'chart-js', 'wp-util'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-dashboard', 'parfumeDashboard', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_dashboard_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'parfume-reviews'),
                'error' => __('Error occurred', 'parfume-reviews'),
                'success' => __('Success!', 'parfume-reviews'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'parfume-reviews'),
                'no_data' => __('No data available', 'parfume-reviews'),
            ),
            'urls' => array(
                'add_parfume' => admin_url('post-new.php?post_type=parfume'),
                'manage_parfumes' => admin_url('edit.php?post_type=parfume'),
                'settings' => admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings'),
                'import_export' => admin_url('edit.php?post_type=parfume&page=parfume-import-export'),
            )
        ));
    }
    
    /**
     * Render the main dashboard page
     */
    public function render_page() {
        $stats = $this->get_dashboard_stats();
        $recent_activity = $this->get_recent_activity();
        ?>
        <div class="wrap parfume-dashboard-page">
            <h1 class="wp-heading-inline">
                <?php _e('Parfume Reviews Dashboard', 'parfume-reviews'); ?>
                <span class="dashboard-version">v<?php echo PARFUME_REVIEWS_VERSION; ?></span>
            </h1>
            
            <div class="dashboard-widgets-wrap">
                <!-- Overview Stats Cards -->
                <div class="dashboard-stats-row">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <span class="dashicons dashicons-products"></span>
                        </div>
                        <div class="stats-content">
                            <h3><?php echo number_format($stats['total_parfumes']); ?></h3>
                            <p><?php _e('Total Parfumes', 'parfume-reviews'); ?></p>
                            <?php if ($stats['parfumes_this_month'] > 0): ?>
                                <span class="stats-change positive">
                                    +<?php echo $stats['parfumes_this_month']; ?> <?php _e('this month', 'parfume-reviews'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">
                            <span class="dashicons dashicons-star-filled"></span>
                        </div>
                        <div class="stats-content">
                            <h3><?php echo number_format($stats['average_rating'], 1); ?></h3>
                            <p><?php _e('Average Rating', 'parfume-reviews'); ?></p>
                            <span class="stats-detail">
                                <?php printf(__('%d rated parfumes', 'parfume-reviews'), $stats['rated_parfumes']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">
                            <span class="dashicons dashicons-tag"></span>
                        </div>
                        <div class="stats-content">
                            <h3><?php echo number_format($stats['total_brands']); ?></h3>
                            <p><?php _e('Brands', 'parfume-reviews'); ?></p>
                            <span class="stats-detail">
                                <?php printf(__('%d notes', 'parfume-reviews'), $stats['total_notes']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">
                            <span class="dashicons dashicons-visibility"></span>
                        </div>
                        <div class="stats-content">
                            <h3><?php echo number_format($stats['total_views']); ?></h3>
                            <p><?php _e('Total Views', 'parfume-reviews'); ?></p>
                            <?php if ($stats['views_growth'] !== 0): ?>
                                <span class="stats-change <?php echo $stats['views_growth'] > 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo ($stats['views_growth'] > 0 ? '+' : '') . number_format($stats['views_growth'], 1); ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="dashboard-charts-row">
                    <div class="chart-container half-width">
                        <div class="chart-header">
                            <h3><?php _e('Parfumes by Month', 'parfume-reviews'); ?></h3>
                            <div class="chart-controls">
                                <select id="chart-period" data-chart="monthly">
                                    <option value="6"><?php _e('Last 6 months', 'parfume-reviews'); ?></option>
                                    <option value="12" selected><?php _e('Last 12 months', 'parfume-reviews'); ?></option>
                                    <option value="24"><?php _e('Last 24 months', 'parfume-reviews'); ?></option>
                                </select>
                            </div>
                        </div>
                        <canvas id="monthlyChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container half-width">
                        <div class="chart-header">
                            <h3><?php _e('Top Brands', 'parfume-reviews'); ?></h3>
                        </div>
                        <canvas id="brandsChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Content Row -->
                <div class="dashboard-content-row">
                    <!-- Recent Activity -->
                    <div class="dashboard-widget">
                        <div class="widget-header">
                            <h3><?php _e('Recent Activity', 'parfume-reviews'); ?></h3>
                            <button class="button button-small" id="refresh-activity">
                                <?php _e('Refresh', 'parfume-reviews'); ?>
                            </button>
                        </div>
                        <div class="widget-content" id="recent-activity-content">
                            <?php $this->render_recent_activity($recent_activity); ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="dashboard-widget">
                        <div class="widget-header">
                            <h3><?php _e('Quick Actions', 'parfume-reviews'); ?></h3>
                        </div>
                        <div class="widget-content">
                            <div class="quick-actions-grid">
                                <a href="<?php echo admin_url('post-new.php?post_type=parfume'); ?>" class="quick-action-btn">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php _e('Add Parfume', 'parfume-reviews'); ?>
                                </a>
                                
                                <a href="<?php echo admin_url('edit-tags.php?taxonomy=marki&post_type=parfume'); ?>" class="quick-action-btn">
                                    <span class="dashicons dashicons-tag"></span>
                                    <?php _e('Manage Brands', 'parfume-reviews'); ?>
                                </a>
                                
                                <a href="<?php echo admin_url('edit.php?post_type=parfume&page=parfume-import-export'); ?>" class="quick-action-btn">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php _e('Import/Export', 'parfume-reviews'); ?>
                                </a>
                                
                                <a href="<?php echo admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings'); ?>" class="quick-action-btn">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <?php _e('Settings', 'parfume-reviews'); ?>
                                </a>
                                
                                <button type="button" class="quick-action-btn" id="clear-cache-btn">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php _e('Clear Cache', 'parfume-reviews'); ?>
                                </button>
                                
                                <button type="button" class="quick-action-btn" id="export-backup-btn">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Quick Backup', 'parfume-reviews'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="dashboard-system-row">
                    <div class="dashboard-widget full-width">
                        <div class="widget-header">
                            <h3><?php _e('System Status', 'parfume-reviews'); ?></h3>
                        </div>
                        <div class="widget-content">
                            <?php $this->render_system_status(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hidden data for charts -->
        <script type="application/json" id="dashboard-chart-data">
            <?php echo json_encode($this->get_chart_data()); ?>
        </script>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        ?>
        <div class="wrap parfume-analytics-page">
            <h1><?php _e('Parfume Analytics', 'parfume-reviews'); ?></h1>
            
            <div class="analytics-filters">
                <div class="filter-group">
                    <label for="analytics-period"><?php _e('Time Period:', 'parfume-reviews'); ?></label>
                    <select id="analytics-period">
                        <option value="7"><?php _e('Last 7 days', 'parfume-reviews'); ?></option>
                        <option value="30" selected><?php _e('Last 30 days', 'parfume-reviews'); ?></option>
                        <option value="90"><?php _e('Last 90 days', 'parfume-reviews'); ?></option>
                        <option value="365"><?php _e('Last year', 'parfume-reviews'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="analytics-brand"><?php _e('Brand:', 'parfume-reviews'); ?></label>
                    <select id="analytics-brand">
                        <option value=""><?php _e('All Brands', 'parfume-reviews'); ?></option>
                        <?php
                        $brands = get_terms(array(
                            'taxonomy' => 'marki',
                            'hide_empty' => true,
                            'orderby' => 'count',
                            'order' => 'DESC',
                            'number' => 20
                        ));
                        
                        foreach ($brands as $brand) {
                            printf(
                                '<option value="%s">%s (%d)</option>',
                                esc_attr($brand->slug),
                                esc_html($brand->name),
                                $brand->count
                            );
                        }
                        ?>
                    </select>
                </div>
                
                <button type="button" class="button button-primary" id="update-analytics">
                    <?php _e('Update Analytics', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <div class="analytics-charts">
                <div class="chart-container">
                    <h3><?php _e('Views Over Time', 'parfume-reviews'); ?></h3>
                    <canvas id="viewsChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Popular Parfumes', 'parfume-reviews'); ?></h3>
                    <canvas id="popularChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Rating Distribution', 'parfume-reviews'); ?></h3>
                    <canvas id="ratingsChart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        $reports = $this->get_available_reports();
        ?>
        <div class="wrap parfume-reports-page">
            <h1><?php _e('Parfume Reports', 'parfume-reviews'); ?></h1>
            
            <div class="reports-grid">
                <?php foreach ($reports as $report): ?>
                    <div class="report-card">
                        <div class="report-icon">
                            <span class="dashicons dashicons-<?php echo esc_attr($report['icon']); ?>"></span>
                        </div>
                        <div class="report-content">
                            <h3><?php echo esc_html($report['title']); ?></h3>
                            <p><?php echo esc_html($report['description']); ?></p>
                            <div class="report-actions">
                                <button type="button" class="button button-primary generate-report" 
                                        data-report="<?php echo esc_attr($report['id']); ?>">
                                    <?php _e('Generate Report', 'parfume-reviews'); ?>
                                </button>
                                <?php if (!empty($report['export'])): ?>
                                    <button type="button" class="button export-report" 
                                            data-report="<?php echo esc_attr($report['id']); ?>">
                                        <?php _e('Export', 'parfume-reviews'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="report-output" class="report-output" style="display: none;">
                <h2><?php _e('Report Results', 'parfume-reviews'); ?></h2>
                <div id="report-content"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        $cache_key = $this->cache_prefix . 'stats';
        $stats = Cache::get($cache_key);
        
        if ($stats === false) {
            $stats = array();
            
            // Total parfumes
            $stats['total_parfumes'] = wp_count_posts('parfume')->publish;
            
            // Parfumes this month
            $stats['parfumes_this_month'] = get_posts(array(
                'post_type' => 'parfume',
                'post_status' => 'publish',
                'date_query' => array(
                    array(
                        'after' => date('Y-m-01'),
                        'inclusive' => true,
                    ),
                ),
                'fields' => 'ids',
                'posts_per_page' => -1
            ));
            $stats['parfumes_this_month'] = count($stats['parfumes_this_month']);
            
            // Average rating
            global $wpdb;
            $rating_data = $wpdb->get_row("
                SELECT 
                    AVG(CAST(meta_value AS DECIMAL(3,2))) as avg_rating,
                    COUNT(*) as rated_count
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_parfume_rating' 
                AND pm.meta_value != ''
                AND pm.meta_value != '0'
                AND p.post_status = 'publish'
                AND p.post_type = 'parfume'
            ");
            
            $stats['average_rating'] = $rating_data ? floatval($rating_data->avg_rating) : 0;
            $stats['rated_parfumes'] = $rating_data ? intval($rating_data->rated_count) : 0;
            
            // Total brands and notes
            $stats['total_brands'] = wp_count_terms(array('taxonomy' => 'marki', 'hide_empty' => false));
            $stats['total_notes'] = wp_count_terms(array('taxonomy' => 'notes', 'hide_empty' => false));
            
            // Views (mock data for now - would be replaced with actual analytics)
            $stats['total_views'] = get_option('parfume_total_views', 0);
            $stats['views_growth'] = get_option('parfume_views_growth', 0);
            
            Cache::set($cache_key, $stats, HOUR_IN_SECONDS);
        }
        
        return $stats;
    }
    
    /**
     * Get recent activity
     */
    private function get_recent_activity($limit = 10) {
        $cache_key = $this->cache_prefix . 'activity_' . $limit;
        $activity = Cache::get($cache_key);
        
        if ($activity === false) {
            $activity = array();
            
            // Recent parfumes
            $recent_parfumes = get_posts(array(
                'post_type' => 'parfume',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            foreach ($recent_parfumes as $parfume) {
                $activity[] = array(
                    'type' => 'parfume_added',
                    'title' => $parfume->post_title,
                    'date' => $parfume->post_date,
                    'url' => get_edit_post_link($parfume->ID),
                    'icon' => 'plus-alt2'
                );
            }
            
            // Recent comments (if enabled)
            $recent_comments = get_comments(array(
                'post_type' => 'parfume',
                'status' => 'approve',
                'number' => $limit,
                'orderby' => 'comment_date',
                'order' => 'DESC'
            ));
            
            foreach ($recent_comments as $comment) {
                $activity[] = array(
                    'type' => 'comment_added',
                    'title' => sprintf(__('Comment on %s', 'parfume-reviews'), get_the_title($comment->comment_post_ID)),
                    'date' => $comment->comment_date,
                    'url' => get_edit_comment_link($comment->comment_ID),
                    'icon' => 'admin-comments'
                );
            }
            
            // Sort by date
            usort($activity, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            $activity = array_slice($activity, 0, $limit);
            
            Cache::set($cache_key, $activity, 30 * MINUTE_IN_SECONDS);
        }
        
        return $activity;
    }
    
    /**
     * Get chart data
     */
    private function get_chart_data() {
        $cache_key = $this->cache_prefix . 'charts';
        $data = Cache::get($cache_key);
        
        if ($data === false) {
            global $wpdb;
            
            // Monthly parfumes data (last 12 months)
            $monthly_data = array();
            for ($i = 11; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*)
                    FROM {$wpdb->posts}
                    WHERE post_type = 'parfume'
                    AND post_status = 'publish'
                    AND DATE_FORMAT(post_date, '%%Y-%%m') = %s
                ", $month));
                
                $monthly_data[] = array(
                    'month' => date('M Y', strtotime($month . '-01')),
                    'count' => intval($count)
                );
            }
            
            // Top brands data
            $brands_data = get_terms(array(
                'taxonomy' => 'marki',
                'hide_empty' => true,
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 10
            ));
            
            $brands_chart = array();
            foreach ($brands_data as $brand) {
                $brands_chart[] = array(
                    'name' => $brand->name,
                    'count' => $brand->count
                );
            }
            
            $data = array(
                'monthly' => $monthly_data,
                'brands' => $brands_chart
            );
            
            Cache::set($cache_key, $data, 2 * HOUR_IN_SECONDS);
        }
        
        return $data;
    }
    
    /**
     * Render recent activity widget
     */
    private function render_recent_activity($activity) {
        if (empty($activity)) {
            echo '<p class="no-activity">' . __('No recent activity found.', 'parfume-reviews') . '</p>';
            return;
        }
        
        echo '<ul class="activity-list">';
        foreach ($activity as $item) {
            printf(
                '<li class="activity-item activity-%s">
                    <span class="activity-icon dashicons dashicons-%s"></span>
                    <div class="activity-content">
                        <a href="%s" class="activity-title">%s</a>
                        <span class="activity-date">%s</span>
                    </div>
                </li>',
                esc_attr($item['type']),
                esc_attr($item['icon']),
                esc_url($item['url']),
                esc_html($item['title']),
                esc_html(human_time_diff(strtotime($item['date'])) . ' ' . __('ago', 'parfume-reviews'))
            );
        }
        echo '</ul>';
    }
    
    /**
     * Render system status
     */
    private function render_system_status() {
        $system_info = array(
            'plugin_version' => PARFUME_REVIEWS_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        );
        
        $checks = array(
            array(
                'label' => __('Plugin Version', 'parfume-reviews'),
                'value' => $system_info['plugin_version'],
                'status' => 'good'
            ),
            array(
                'label' => __('WordPress Version', 'parfume-reviews'),
                'value' => $system_info['wordpress_version'],
                'status' => version_compare($system_info['wordpress_version'], '5.0', '>=') ? 'good' : 'warning'
            ),
            array(
                'label' => __('PHP Version', 'parfume-reviews'),
                'value' => $system_info['php_version'],
                'status' => version_compare($system_info['php_version'], '7.4', '>=') ? 'good' : 'warning'
            ),
            array(
                'label' => __('Memory Limit', 'parfume-reviews'),
                'value' => $system_info['memory_limit'],
                'status' => wp_convert_hr_to_bytes($system_info['memory_limit']) >= 128 * 1024 * 1024 ? 'good' : 'warning'
            ),
            array(
                'label' => __('Cache Status', 'parfume-reviews'),
                'value' => Cache::is_enabled() ? __('Enabled', 'parfume-reviews') : __('Disabled', 'parfume-reviews'),
                'status' => Cache::is_enabled() ? 'good' : 'info'
            ),
        );
        
        echo '<div class="system-status-grid">';
        foreach ($checks as $check) {
            printf(
                '<div class="status-item status-%s">
                    <span class="status-label">%s:</span>
                    <span class="status-value">%s</span>
                    <span class="status-indicator"></span>
                </div>',
                esc_attr($check['status']),
                esc_html($check['label']),
                esc_html($check['value'])
            );
        }
        echo '</div>';
    }
    
    /**
     * Get available reports
     */
    private function get_available_reports() {
        return array(
            array(
                'id' => 'overview',
                'title' => __('Overview Report', 'parfume-reviews'),
                'description' => __('Complete overview of all parfumes, brands, and statistics.', 'parfume-reviews'),
                'icon' => 'chart-area',
                'export' => true
            ),
            array(
                'id' => 'brands',
                'title' => __('Brands Report', 'parfume-reviews'),
                'description' => __('Detailed analysis of brands and their performance.', 'parfume-reviews'),
                'icon' => 'tag',
                'export' => true
            ),
            array(
                'id' => 'ratings',
                'title' => __('Ratings Analysis', 'parfume-reviews'),
                'description' => __('Rating trends and distribution analysis.', 'parfume-reviews'),
                'icon' => 'star-filled',
                'export' => true
            ),
            array(
                'id' => 'missing_data',
                'title' => __('Missing Data Report', 'parfume-reviews'),
                'description' => __('Identify parfumes with incomplete information.', 'parfume-reviews'),
                'icon' => 'warning',
                'export' => false
            ),
            array(
                'id' => 'duplicates',
                'title' => __('Duplicate Detection', 'parfume-reviews'),
                'description' => __('Find potential duplicate parfumes.', 'parfume-reviews'),
                'icon' => 'admin-page',
                'export' => false
            ),
        );
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        if (!current_user_can($this->capability)) {
            return;
        }
        
        wp_add_dashboard_widget(
            'parfume_overview_widget',
            __('Parfume Reviews Overview', 'parfume-reviews'),
            array($this, 'render_overview_widget')
        );
        
        wp_add_dashboard_widget(
            'parfume_recent_widget',
            __('Recent Parfumes', 'parfume-reviews'),
            array($this, 'render_recent_widget')
        );
    }
    
    /**
     * Render overview dashboard widget
     */
    public function render_overview_widget() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="parfume-widget-content">
            <div class="widget-stats">
                <div class="stat-item">
                    <strong><?php echo number_format($stats['total_parfumes']); ?></strong>
                    <span><?php _e('Parfumes', 'parfume-reviews'); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php echo number_format($stats['total_brands']); ?></strong>
                    <span><?php _e('Brands', 'parfume-reviews'); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php echo number_format($stats['average_rating'], 1); ?></strong>
                    <span><?php _e('Avg Rating', 'parfume-reviews'); ?></span>
                </div>
            </div>
            <div class="widget-actions">
                <a href="<?php echo admin_url('edit.php?post_type=parfume&page=parfume-dashboard'); ?>" class="button button-small">
                    <?php _e('View Dashboard', 'parfume-reviews'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent parfumes dashboard widget
     */
    public function render_recent_widget() {
        $recent_parfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($recent_parfumes)) {
            echo '<p>' . __('No parfumes found.', 'parfume-reviews') . '</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($recent_parfumes as $parfume) {
            $rating = get_post_meta($parfume->ID, '_parfume_rating', true);
            printf(
                '<li>
                    <a href="%s">%s</a>
                    %s
                    <span class="post-date">%s</span>
                </li>',
                get_edit_post_link($parfume->ID),
                esc_html($parfume->post_title),
                $rating ? '<span class="rating">★ ' . number_format($rating, 1) . '</span>' : '',
                esc_html(human_time_diff(strtotime($parfume->post_date)) . ' ' . __('ago', 'parfume-reviews'))
            );
        }
        echo '</ul>';
        
        printf(
            '<p><a href="%s" class="button button-small">%s</a></p>',
            admin_url('edit.php?post_type=parfume'),
            __('View All Parfumes', 'parfume-reviews')
        );
    }
    
    /**
     * Add admin bar menu
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can($this->capability)) {
            return;
        }
        
        $stats = $this->get_dashboard_stats();
        
        $wp_admin_bar->add_node(array(
            'id' => 'parfume-reviews',
            'title' => sprintf(
                '<span class="ab-icon dashicons dashicons-analytics-pro"></span> %s (%d)',
                __('Parfumes', 'parfume-reviews'),
                $stats['total_parfumes']
            ),
            'href' => admin_url('edit.php?post_type=parfume&page=parfume-dashboard'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'parfume-reviews',
            'id' => 'parfume-dashboard',
            'title' => __('Dashboard', 'parfume-reviews'),
            'href' => admin_url('edit.php?post_type=parfume&page=parfume-dashboard'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'parfume-reviews',
            'id' => 'parfume-add-new',
            'title' => __('Add New Parfume', 'parfume-reviews'),
            'href' => admin_url('post-new.php?post_type=parfume'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'parfume-reviews',
            'id' => 'parfume-manage',
            'title' => __('Manage Parfumes', 'parfume-reviews'),
            'href' => admin_url('edit.php?post_type=parfume'),
        ));
    }
    
    /**
     * AJAX handler for dashboard stats
     */
    public function ajax_get_stats() {
        check_ajax_referer('parfume_dashboard_nonce', 'nonce');
        
        if (!current_user_can($this->capability)) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        // Clear cache and get fresh stats
        Cache::delete($this->cache_prefix . 'stats');
        $stats = $this->get_dashboard_stats();
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX handler for recent activity
     */
    public function ajax_get_recent_activity() {
        check_ajax_referer('parfume_dashboard_nonce', 'nonce');
        
        if (!current_user_can($this->capability)) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        // Clear cache and get fresh activity
        Cache::delete($this->cache_prefix . 'activity_10');
        $activity = $this->get_recent_activity();
        
        ob_start();
        $this->render_recent_activity($activity);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX handler for quick actions
     */
    public function ajax_quick_action() {
        check_ajax_referer('parfume_dashboard_nonce', 'nonce');
        
        if (!current_user_can($this->capability)) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $action = sanitize_text_field($_POST['quick_action']);
        
        switch ($action) {
            case 'clear_cache':
                Cache::flush();
                wp_send_json_success(array('message' => __('Cache cleared successfully!', 'parfume-reviews')));
                break;
                
            case 'export_backup':
                // Trigger quick backup
                wp_send_json_success(array('redirect' => admin_url('edit.php?post_type=parfume&page=parfume-import-export&tab=backup')));
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'parfume-reviews')));
        }
    }
    
    /**
     * Display dashboard notices
     */
    public function display_dashboard_notices() {
        if (!$this->is_plugin_page()) {
            return;
        }
        
        // Check for important system issues
        $notices = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $notices[] = array(
                'type' => 'warning',
                'message' => sprintf(
                    __('Your PHP version (%s) is outdated. Please update to PHP 7.4 or higher for better performance.', 'parfume-reviews'),
                    PHP_VERSION
                )
            );
        }
        
        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 128 * 1024 * 1024) {
            $notices[] = array(
                'type' => 'warning',
                'message' => sprintf(
                    __('Your memory limit (%s) might be too low. Consider increasing it to 128M or higher.', 'parfume-reviews'),
                    ini_get('memory_limit')
                )
            );
        }
        
        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%s"><p>%s</p></div>',
                esc_attr($notice['type']),
                wp_kses_post($notice['message'])
            );
        }
    }
    
    /**
     * Check if current page is this plugin page
     */
    private function is_plugin_page($hook = null) {
        if ($hook) {
            return in_array($hook, array(
                'toplevel_page_parfume-dashboard',
                'parfume-dashboard_page_parfume-analytics',
                'parfume-dashboard_page_parfume-reports'
            ));
        }
        
        return isset($_GET['page']) && in_array($_GET['page'], array(
            'parfume-dashboard',
            'parfume-analytics',
            'parfume-reports'
        ));
    }
}