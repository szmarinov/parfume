<?php
/**
 * Scraper Monitor
 * 
 * Monitoring dashboard for scraping operations
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Scraper
 * @since 2.0.0
 */

namespace ParfumeReviews\Features\Scraper;

use ParfumeReviews\Features\Stores\StoreRepository;
use ParfumeReviews\Features\Stores\StoreManager;

/**
 * ScraperMonitor Class
 * 
 * Provides monitoring and reporting for scraping activities
 */
class ScraperMonitor {
    
    /**
     * Store repository
     * 
     * @var StoreRepository
     */
    private $store_repo;
    
    /**
     * Store manager
     * 
     * @var StoreManager
     */
    private $store_manager;
    
    /**
     * Constructor
     * 
     * @param StoreRepository $store_repo Store repository
     * @param StoreManager $store_manager Store manager
     */
    public function __construct(StoreRepository $store_repo, StoreManager $store_manager) {
        $this->store_repo = $store_repo;
        $this->store_manager = $store_manager;
    }
    
    /**
     * Render monitor page
     */
    public function render_monitor_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за достъп до тази страница', 'parfume-reviews'));
        }
        
        $stats = $this->get_statistics();
        $recent_scrapes = $this->get_recent_scrapes(20);
        
        ?>
        <div class="wrap parfume-scraper-monitor">
            <h1><?php _e('Product Scraper Monitor', 'parfume-reviews'); ?></h1>
            
            <!-- Statistics Cards -->
            <div class="scraper-stats-cards">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo esc_html($stats['total_stores']); ?></div>
                    <div class="stat-label"><?php _e('Общо Product URLs', 'parfume-reviews'); ?></div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-number"><?php echo esc_html($stats['success']); ?></div>
                    <div class="stat-label"><?php _e('Успешни', 'parfume-reviews'); ?></div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo esc_html($stats['pending']); ?></div>
                    <div class="stat-label"><?php _e('Чакащи', 'parfume-reviews'); ?></div>
                </div>
                
                <div class="stat-card error">
                    <div class="stat-number"><?php echo esc_html($stats['error']); ?></div>
                    <div class="stat-label"><?php _e('Грешки', 'parfume-reviews'); ?></div>
                </div>
                
                <div class="stat-card blocked">
                    <div class="stat-number"><?php echo esc_html($stats['blocked']); ?></div>
                    <div class="stat-label"><?php _e('Блокирани', 'parfume-reviews'); ?></div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="scraper-actions">
                <button type="button" class="button button-primary" id="scrape-all-pending">
                    <?php _e('Скрейпни всички чакащи', 'parfume-reviews'); ?>
                </button>
                <button type="button" class="button" id="refresh-monitor">
                    <?php _e('Обнови', 'parfume-reviews'); ?>
                </button>
                <button type="button" class="button" id="export-monitor-data">
                    <?php _e('Експортирай данни', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <!-- Recent Scrapes Table -->
            <h2><?php _e('Последни скрейпвания', 'parfume-reviews'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Пост', 'parfume-reviews'); ?></th>
                        <th><?php _e('Магазин', 'parfume-reviews'); ?></th>
                        <th><?php _e('Product URL', 'parfume-reviews'); ?></th>
                        <th><?php _e('Последна цена', 'parfume-reviews'); ?></th>
                        <th><?php _e('Последно скрейпване', 'parfume-reviews'); ?></th>
                        <th><?php _e('Следващо скрейпване', 'parfume-reviews'); ?></th>
                        <th><?php _e('Статус', 'parfume-reviews'); ?></th>
                        <th><?php _e('Действия', 'parfume-reviews'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_scrapes)) : ?>
                        <tr>
                            <td colspan="8" class="no-items">
                                <?php _e('Няма данни за показване', 'parfume-reviews'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($recent_scrapes as $scrape) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($scrape['post_id'])); ?>">
                                        <?php echo esc_html(get_the_title($scrape['post_id'])); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($scrape['store_name']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($scrape['product_url']); ?>" target="_blank">
                                        <?php echo esc_html($this->truncate_url($scrape['product_url'])); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($scrape['price'])) : ?>
                                        <strong><?php echo esc_html($scrape['price']); ?></strong>
                                        <?php if (!empty($scrape['currency'])) : ?>
                                            <?php echo esc_html($scrape['currency']); ?>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-minus"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($scrape['last_scraped'])) : ?>
                                        <?php echo esc_html($this->format_datetime($scrape['last_scraped'])); ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-minus"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($scrape['next_scrape'])) : ?>
                                        <?php echo esc_html($this->format_datetime($scrape['next_scrape'])); ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-minus"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $this->render_status_badge($scrape['status']); ?>
                                </td>
                                <td>
                                    <button 
                                        type="button" 
                                        class="button button-small scrape-now-button"
                                        data-post-id="<?php echo esc_attr($scrape['post_id']); ?>"
                                        data-store-index="<?php echo esc_attr($scrape['store_index']); ?>"
                                    >
                                        <?php _e('Скрейпни сега', 'parfume-reviews'); ?>
                                    </button>
                                    <a 
                                        href="<?php echo esc_url(admin_url('admin.php?page=scraper-log&post_id=' . $scrape['post_id'] . '&store_index=' . $scrape['store_index'])); ?>" 
                                        class="button button-small"
                                    >
                                        <?php _e('Лог', 'parfume-reviews'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Error Log -->
            <?php $errors = $this->get_recent_errors(10); ?>
            <?php if (!empty($errors)) : ?>
                <h2><?php _e('Последни грешки', 'parfume-reviews'); ?></h2>
                <div class="scraper-error-log">
                    <?php foreach ($errors as $error) : ?>
                        <div class="error-entry">
                            <div class="error-time">
                                <?php echo esc_html($this->format_datetime($error['timestamp'])); ?>
                            </div>
                            <div class="error-post">
                                <strong><?php echo esc_html(get_the_title($error['post_id'])); ?></strong>
                                - <?php echo esc_html($error['store_name']); ?>
                            </div>
                            <div class="error-message">
                                <?php echo esc_html($error['message']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .scraper-stats-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #646970;
        }
        
        .stat-card.total .stat-number { color: #2271b1; }
        .stat-card.success .stat-number { color: #46b450; }
        .stat-card.pending .stat-number { color: #dba617; }
        .stat-card.error .stat-number { color: #dc3232; }
        .stat-card.blocked .stat-number { color: #646970; }
        
        .scraper-actions {
            margin: 20px 0;
        }
        
        .scraper-actions .button {
            margin-right: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.blocked {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .scraper-error-log {
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #dc3232;
        }
        
        .error-entry {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .error-entry:last-child {
            border-bottom: none;
        }
        
        .error-time {
            font-size: 12px;
            color: #646970;
            margin-bottom: 3px;
        }
        
        .error-post {
            margin-bottom: 3px;
        }
        
        .error-message {
            font-size: 13px;
            color: #dc3232;
        }
        </style>
        <?php
    }
    
    /**
     * Get statistics
     * 
     * @return array
     */
    private function get_statistics() {
        return $this->store_repo->get_scraping_stats();
    }
    
    /**
     * Get recent scrapes
     * 
     * @param int $limit Limit
     * @return array
     */
    private function get_recent_scrapes($limit = 20) {
        global $wpdb;
        
        $results = [];
        
        $post_ids = $wpdb->get_col("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores'
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} WHERE post_type = 'parfume' AND post_status = 'publish'
            )
            ORDER BY post_id DESC
            LIMIT 100
        ");
        
        foreach ($post_ids as $post_id) {
            $stores = $this->store_repo->get_post_stores($post_id);
            
            foreach ($stores as $index => $store) {
                if (empty($store['product_url'])) {
                    continue;
                }
                
                $store_name = $this->store_manager->get_store_name($store['store_id']);
                
                $results[] = [
                    'post_id' => $post_id,
                    'store_index' => $index,
                    'store_name' => $store_name,
                    'product_url' => $store['product_url'],
                    'price' => isset($store['scraped_data']['price']) ? $store['scraped_data']['price'] : null,
                    'currency' => isset($store['scraped_data']['currency']) ? $store['scraped_data']['currency'] : 'BGN',
                    'last_scraped' => isset($store['last_scraped']) ? $store['last_scraped'] : '',
                    'next_scrape' => isset($store['next_scrape']) ? $store['next_scrape'] : '',
                    'status' => isset($store['scrape_status']) ? $store['scrape_status'] : 'pending'
                ];
            }
            
            if (count($results) >= $limit) {
                break;
            }
        }
        
        // Sort by last scraped
        usort($results, function($a, $b) {
            return strcmp($b['last_scraped'], $a['last_scraped']);
        });
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Get recent errors
     * 
     * @param int $limit Limit
     * @return array
     */
    private function get_recent_errors($limit = 10) {
        $log_option = get_option('parfume_scraper_error_log', []);
        
        if (!is_array($log_option)) {
            return [];
        }
        
        // Get last N errors
        $errors = array_slice($log_option, -$limit);
        
        // Reverse to show newest first
        return array_reverse($errors);
    }
    
    /**
     * Render status badge
     * 
     * @param string $status Status
     * @return string HTML
     */
    private function render_status_badge($status) {
        $labels = [
            'success' => __('Успех', 'parfume-reviews'),
            'pending' => __('Чакащ', 'parfume-reviews'),
            'error' => __('Грешка', 'parfume-reviews'),
            'blocked' => __('Блокиран', 'parfume-reviews')
        ];
        
        $label = isset($labels[$status]) ? $labels[$status] : $status;
        
        return sprintf(
            '<span class="status-badge %s">%s</span>',
            esc_attr($status),
            esc_html($label)
        );
    }
    
    /**
     * Truncate URL for display
     * 
     * @param string $url URL
     * @param int $length Max length
     * @return string
     */
    private function truncate_url($url, $length = 50) {
        if (strlen($url) <= $length) {
            return $url;
        }
        
        return substr($url, 0, $length) . '...';
    }
    
    /**
     * Format datetime for display
     * 
     * @param string $datetime MySQL datetime
     * @return string
     */
    private function format_datetime($datetime) {
        if (empty($datetime)) {
            return '';
        }
        
        $timestamp = strtotime($datetime);
        $now = current_time('timestamp');
        $diff = $now - $timestamp;
        
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return sprintf(__('Преди %d мин.', 'parfume-reviews'), $minutes);
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf(__('Преди %d ч.', 'parfume-reviews'), $hours);
        } else {
            return date_i18n('d.m.Y H:i', $timestamp);
        }
    }
    
    /**
     * Log error
     * 
     * @param int $post_id Post ID
     * @param int $store_index Store index
     * @param string $message Error message
     */
    public static function log_error($post_id, $store_index, $message) {
        $log = get_option('parfume_scraper_error_log', []);
        
        if (!is_array($log)) {
            $log = [];
        }
        
        $store_repo = new StoreRepository();
        $store_manager = new StoreManager(\ParfumeReviews\Core\Plugin::get_instance()->get_container());
        
        $stores = $store_repo->get_post_stores($post_id);
        $store_id = isset($stores[$store_index]['store_id']) ? $stores[$store_index]['store_id'] : 0;
        $store_name = $store_manager->get_store_name($store_id);
        
        $log[] = [
            'timestamp' => current_time('mysql'),
            'post_id' => $post_id,
            'store_index' => $store_index,
            'store_name' => $store_name,
            'message' => $message
        ];
        
        // Keep only last 100 errors
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        update_option('parfume_scraper_error_log', $log);
    }
}