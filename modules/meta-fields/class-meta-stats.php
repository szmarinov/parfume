<?php
/**
 * Parfume Catalog Meta Stats
 * 
 * Управление на статистики, рейтинги и аналитика в мета полетата за парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Meta_Stats {
    
    /**
     * Конструктор
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_parfume_recalculate_stats', array($this, 'ajax_recalculate_stats'));
        add_action('wp_ajax_parfume_reset_stats', array($this, 'ajax_reset_stats'));
        add_action('wp_ajax_parfume_get_stats_data', array($this, 'ajax_get_stats_data'));
        add_action('wp_ajax_parfume_manual_rating_override', array($this, 'ajax_manual_rating_override'));
    }
    
    /**
     * Добавяне на мета boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_performance_stats',
            __('Статистики и рейтинги', 'parfume-catalog'),
            array($this, 'render_performance_stats_meta_box'),
            'parfumes',
            'side',
            'high'
        );
        
        add_meta_box(
            'parfume_user_ratings',
            __('Потребителски оценки', 'parfume-catalog'),
            array($this, 'render_user_ratings_meta_box'),
            'parfumes',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume_analytics',
            __('Аналитика и посещения', 'parfume-catalog'),
            array($this, 'render_analytics_meta_box'),
            'parfumes',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume_manual_overrides',
            __('Ръчни настройки', 'parfume-catalog'),
            array($this, 'render_manual_overrides_meta_box'),
            'parfumes',
            'side',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('chart-js', 
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', 
                array(), 
                '3.9.1', 
                true
            );
            
            wp_enqueue_script('parfume-meta-stats', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/meta-stats.js', 
                array('jquery', 'chart-js'), 
                PARFUME_CATALOG_VERSION, 
                true
            );
            
            wp_localize_script('parfume-meta-stats', 'parfumeMetaStats', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_meta_stats'),
                'texts' => array(
                    'recalculating' => __('Преизчисляване...', 'parfume-catalog'),
                    'recalculate_success' => __('Статистиките са преизчислени', 'parfume-catalog'),
                    'recalculate_error' => __('Грешка при преизчисляване', 'parfume-catalog'),
                    'reset_confirm' => __('Сигурни ли сте, че искате да нулирате всички статистики?', 'parfume-catalog'),
                    'reset_success' => __('Статистиките са нулирани', 'parfume-catalog'),
                    'override_success' => __('Ръчните настройки са запазени', 'parfume-catalog')
                )
            ));
            
            wp_enqueue_style('parfume-meta-stats', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/meta-stats.css', 
                array(), 
                PARFUME_CATALOG_VERSION
            );
        }
    }
    
    /**
     * Render performance stats meta box
     */
    public function render_performance_stats_meta_box($post) {
        wp_nonce_field('parfume_stats_meta_nonce', 'parfume_stats_meta_nonce_field');
        
        // Get calculated stats
        $stats = $this->get_parfume_stats($post->ID);
        $manual_override = get_post_meta($post->ID, '_parfume_manual_rating_override', true);
        ?>
        <div class="performance-stats-container">
            <div class="stats-overview">
                <div class="stat-item rating">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['average_rating'], 1); ?>/5</div>
                        <div class="stat-label"><?php _e('Среден рейтинг', 'parfume-catalog'); ?></div>
                        <div class="stat-meta"><?php printf(__('от %d оценки', 'parfume-catalog'), $stats['total_ratings']); ?></div>
                    </div>
                </div>
                
                <div class="stat-item views">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
                        <div class="stat-label"><?php _e('Общо прегледи', 'parfume-catalog'); ?></div>
                        <div class="stat-meta"><?php printf(__('%d този месец', 'parfume-catalog'), $stats['monthly_views']); ?></div>
                    </div>
                </div>
                
                <div class="stat-item popularity">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['popularity_rank']; ?></div>
                        <div class="stat-label"><?php _e('Популярност', 'parfume-catalog'); ?></div>
                        <div class="stat-meta"><?php _e('позиция в класацията', 'parfume-catalog'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="stats-actions">
                <button type="button" class="button button-secondary" id="recalculate-stats" data-post-id="<?php echo $post->ID; ?>">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Преизчисли', 'parfume-catalog'); ?>
                </button>
                
                <button type="button" class="button" id="reset-stats" data-post-id="<?php echo $post->ID; ?>">
                    <span class="dashicons dashicons-backup"></span>
                    <?php _e('Нулирай', 'parfume-catalog'); ?>
                </button>
            </div>
            
            <div class="stats-metadata">
                <p class="last-updated">
                    <?php _e('Последно обновяване:', 'parfume-catalog'); ?>
                    <strong><?php echo $stats['last_updated'] ? human_time_diff(strtotime($stats['last_updated'])) . ' ' . __('преди', 'parfume-catalog') : __('Никога', 'parfume-catalog'); ?></strong>
                </p>
                
                <?php if ($manual_override): ?>
                    <p class="manual-override-notice">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Има активни ръчни настройки за рейтинга', 'parfume-catalog'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .performance-stats-container {
            padding: 15px 0;
        }
        
        .stats-overview {
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .stat-item.rating {
            border-left: 4px solid #ffc107;
        }
        
        .stat-item.views {
            border-left: 4px solid #17a2b8;
        }
        
        .stat-item.popularity {
            border-left: 4px solid #dc3545;
        }
        
        .stat-icon {
            font-size: 20px;
            width: 30px;
            text-align: center;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            line-height: 1.2;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 2px 0;
        }
        
        .stat-meta {
            font-size: 11px;
            color: #868e96;
            font-style: italic;
        }
        
        .stats-actions {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .stats-actions .button {
            flex: 1;
            text-align: center;
            font-size: 12px;
            height: auto;
            padding: 8px 12px;
        }
        
        .stats-metadata {
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .stats-metadata p {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #6c757d;
        }
        
        .manual-override-notice {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        </style>
        <?php
    }
    
    /**
     * Render user ratings meta box
     */
    public function render_user_ratings_meta_box($post) {
        $ratings_data = $this->get_ratings_breakdown($post->ID);
        $recent_comments = $this->get_recent_comments($post->ID, 5);
        ?>
        <div class="user-ratings-container">
            <div class="ratings-breakdown">
                <h4><?php _e('Разпределение на оценките', 'parfume-catalog'); ?></h4>
                
                <div class="ratings-chart">
                    <div class="chart-container">
                        <canvas id="ratings-breakdown-chart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="ratings-bars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <?php 
                            $count = isset($ratings_data['breakdown'][$i]) ? $ratings_data['breakdown'][$i] : 0;
                            $percentage = $ratings_data['total'] > 0 ? ($count / $ratings_data['total']) * 100 : 0;
                            ?>
                            <div class="rating-bar">
                                <div class="rating-stars">
                                    <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>
                                </div>
                                <div class="rating-progress">
                                    <div class="rating-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="rating-count"><?php echo $count; ?></div>
                                <div class="rating-percentage"><?php echo round($percentage, 1); ?>%</div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="ratings-summary">
                    <div class="summary-item">
                        <span class="summary-label"><?php _e('Общо оценки:', 'parfume-catalog'); ?></span>
                        <span class="summary-value"><?php echo $ratings_data['total']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label"><?php _e('Среден рейтинг:', 'parfume-catalog'); ?></span>
                        <span class="summary-value"><?php echo number_format($ratings_data['average'], 1); ?>/5</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label"><?php _e('Най-честа оценка:', 'parfume-catalog'); ?></span>
                        <span class="summary-value"><?php echo $ratings_data['most_common']; ?> ★</span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($recent_comments)): ?>
                <div class="recent-comments">
                    <h4><?php _e('Последни коментари', 'parfume-catalog'); ?></h4>
                    
                    <div class="comments-list">
                        <?php foreach ($recent_comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <strong><?php echo esc_html($comment->author_name ?: __('Анонимен', 'parfume-catalog')); ?></strong>
                                        <div class="comment-rating">
                                            <?php echo str_repeat('★', $comment->rating) . str_repeat('☆', 5 - $comment->rating); ?>
                                        </div>
                                    </div>
                                    <div class="comment-date">
                                        <?php echo human_time_diff(strtotime($comment->created_at)); ?> <?php _e('преди', 'parfume-catalog'); ?>
                                    </div>
                                </div>
                                <div class="comment-content">
                                    <?php echo esc_html(wp_trim_words($comment->content, 15)); ?>
                                </div>
                                <div class="comment-actions">
                                    <a href="<?php echo admin_url('admin.php?page=parfume-comments&comment_id=' . $comment->id); ?>" class="button button-small">
                                        <?php _e('Преглед', 'parfume-catalog'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="comments-actions">
                        <a href="<?php echo admin_url('admin.php?page=parfume-comments&post_id=' . $post->ID); ?>" class="button">
                            <?php _e('Виж всички коментари', 'parfume-catalog'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .user-ratings-container {
            margin: 15px 0;
        }
        
        .ratings-breakdown {
            margin-bottom: 30px;
        }
        
        .ratings-breakdown h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #495057;
        }
        
        .ratings-chart {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
        }
        
        .chart-container {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .ratings-bars {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .rating-bar {
            display: grid;
            grid-template-columns: 80px 1fr 40px 40px;
            align-items: center;
            gap: 10px;
            font-size: 12px;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 14px;
        }
        
        .rating-progress {
            background: #e9ecef;
            border-radius: 10px;
            height: 16px;
            overflow: hidden;
        }
        
        .rating-fill {
            background: linear-gradient(90deg, #ff6b6b 0%, #feca57 50%, #48dbfb 100%);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .rating-count {
            font-weight: 500;
            text-align: center;
        }
        
        .rating-percentage {
            color: #6c757d;
            text-align: right;
        }
        
        .ratings-summary {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .summary-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        
        .summary-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #495057;
        }
        
        .recent-comments h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #495057;
        }
        
        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .comment-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .comment-author {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .comment-rating {
            color: #ffc107;
            font-size: 12px;
        }
        
        .comment-date {
            font-size: 11px;
            color: #6c757d;
        }
        
        .comment-content {
            font-size: 13px;
            color: #495057;
            line-height: 1.4;
            margin-bottom: 8px;
        }
        
        .comment-actions {
            text-align: right;
        }
        
        .comments-actions {
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .rating-bar {
                grid-template-columns: 60px 1fr 30px 35px;
                gap: 6px;
            }
            
            .ratings-summary {
                justify-content: center;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize ratings breakdown chart
            var ctx = document.getElementById('ratings-breakdown-chart');
            if (ctx) {
                var ratingsData = <?php echo json_encode(array_values($ratings_data['breakdown'])); ?>;
                var chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['5★', '4★', '3★', '2★', '1★'],
                        datasets: [{
                            data: ratingsData.reverse(),
                            backgroundColor: [
                                '#28a745',
                                '#17a2b8',
                                '#ffc107',
                                '#fd7e14',
                                '#dc3545'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    fontSize: 12,
                                    padding: 15
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render analytics meta box
     */
    public function render_analytics_meta_box($post) {
        $analytics = $this->get_analytics_data($post->ID);
        ?>
        <div class="analytics-container">
            <div class="analytics-overview">
                <div class="analytics-cards">
                    <div class="analytics-card views">
                        <div class="card-header">
                            <span class="card-icon">📊</span>
                            <h4><?php _e('Прегледи', 'parfume-catalog'); ?></h4>
                        </div>
                        <div class="card-content">
                            <div class="metric">
                                <span class="metric-value"><?php echo number_format($analytics['views']['total']); ?></span>
                                <span class="metric-label"><?php _e('Общо', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?php echo number_format($analytics['views']['monthly']); ?></span>
                                <span class="metric-label"><?php _e('Този месец', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?php echo number_format($analytics['views']['weekly']); ?></span>
                                <span class="metric-label"><?php _e('Тази седмица', 'parfume-catalog'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="analytics-card engagement">
                        <div class="card-header">
                            <span class="card-icon">💬</span>
                            <h4><?php _e('Ангажираност', 'parfume-catalog'); ?></h4>
                        </div>
                        <div class="card-content">
                            <div class="metric">
                                <span class="metric-value"><?php echo $analytics['engagement']['comments']; ?></span>
                                <span class="metric-label"><?php _e('Коментари', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?php echo $analytics['engagement']['comparisons']; ?></span>
                                <span class="metric-label"><?php _e('Сравнения', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?php echo number_format($analytics['engagement']['time_on_page'], 1); ?>s</span>
                                <span class="metric-label"><?php _e('Ср. време', 'parfume-catalog'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="analytics-card search">
                        <div class="card-header">
                            <span class="card-icon">🔍</span>
                            <h4><?php _e('Откриваемост', 'parfume-catalog'); ?></h4>
                        </div>
                        <div class="card-content">
                            <div class="metric">
                                <span class="metric-value"><?php echo $analytics['search']['organic_clicks']; ?></span>
                                <span class="metric-label"><?php _e('Органични', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?php echo $analytics['search']['internal_searches']; ?></span>
                                <span class="metric-label"><?php _e('Вътрешни', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?php echo $analytics['search']['related_views']; ?></span>
                                <span class="metric-label"><?php _e('Свързани', 'parfume-catalog'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="analytics-charts">
                <div class="chart-section">
                    <h4><?php _e('Прегледи през времето (последните 30 дни)', 'parfume-catalog'); ?></h4>
                    <div class="chart-container">
                        <canvas id="views-timeline-chart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <div class="top-referrers">
                    <h4><?php _e('Топ източници на трафик', 'parfume-catalog'); ?></h4>
                    <div class="referrers-list">
                        <?php foreach ($analytics['top_referrers'] as $referrer): ?>
                            <div class="referrer-item">
                                <div class="referrer-source"><?php echo esc_html($referrer['source']); ?></div>
                                <div class="referrer-visits"><?php echo number_format($referrer['visits']); ?></div>
                                <div class="referrer-percentage"><?php echo $referrer['percentage']; ?>%</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .analytics-container {
            margin: 15px 0;
        }
        
        .analytics-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .analytics-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-icon {
            font-size: 16px;
        }
        
        .card-header h4 {
            margin: 0;
            font-size: 13px;
            color: #495057;
        }
        
        .card-content {
            padding: 15px;
            display: flex;
            justify-content: space-between;
        }
        
        .metric {
            text-align: center;
            flex: 1;
        }
        
        .metric-value {
            display: block;
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            line-height: 1.2;
        }
        
        .metric-label {
            display: block;
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        .analytics-charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .chart-section h4,
        .top-referrers h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #495057;
        }
        
        .chart-container {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            height: 250px;
        }
        
        .referrers-list {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
        }
        
        .referrer-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 12px;
        }
        
        .referrer-item:last-child {
            border-bottom: none;
        }
        
        .referrer-source {
            font-weight: 500;
            color: #495057;
        }
        
        .referrer-visits {
            color: #0073aa;
            font-weight: 500;
        }
        
        .referrer-percentage {
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .analytics-charts {
                grid-template-columns: 1fr;
            }
            
            .analytics-cards {
                grid-template-columns: 1fr;
            }
            
            .card-content {
                flex-direction: column;
                gap: 15px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize views timeline chart
            var timelineCtx = document.getElementById('views-timeline-chart');
            if (timelineCtx) {
                var timelineData = <?php echo json_encode($analytics['timeline_data']); ?>;
                var timelineChart = new Chart(timelineCtx, {
                    type: 'line',
                    data: {
                        labels: timelineData.labels,
                        datasets: [{
                            label: '<?php _e('Прегледи', 'parfume-catalog'); ?>',
                            data: timelineData.data,
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render manual overrides meta box
     */
    public function render_manual_overrides_meta_box($post) {
        $manual_rating = get_post_meta($post->ID, '_parfume_manual_rating', true);
        $rating_override = get_post_meta($post->ID, '_parfume_manual_rating_override', true);
        $featured_status = get_post_meta($post->ID, '_parfume_featured', true);
        $trending_status = get_post_meta($post->ID, '_parfume_trending', true);
        $editor_choice = get_post_meta($post->ID, '_parfume_editor_choice', true);
        ?>
        <div class="manual-overrides-container">
            <div class="override-section">
                <h4><?php _e('Ръчен рейтинг', 'parfume-catalog'); ?></h4>
                <div class="rating-override">
                    <label>
                        <input type="checkbox" 
                               name="parfume_manual_rating_override" 
                               value="1" 
                               <?php checked($rating_override, 1); ?> />
                        <?php _e('Задай ръчен рейтинг', 'parfume-catalog'); ?>
                    </label>
                    
                    <div class="manual-rating-input" style="<?php echo $rating_override ? '' : 'display:none;'; ?>">
                        <label for="parfume_manual_rating"><?php _e('Рейтинг (1-5):', 'parfume-catalog'); ?></label>
                        <input type="number" 
                               id="parfume_manual_rating" 
                               name="parfume_manual_rating" 
                               value="<?php echo esc_attr($manual_rating ?: 5); ?>" 
                               min="1" 
                               max="5" 
                               step="0.1" 
                               class="small-text" />
                        <p class="description"><?php _e('Ръчният рейтинг ще замени автоматично изчисления', 'parfume-catalog'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="override-section">
                <h4><?php _e('Специални статуси', 'parfume-catalog'); ?></h4>
                <div class="status-checkboxes">
                    <label>
                        <input type="checkbox" 
                               name="parfume_featured" 
                               value="1" 
                               <?php checked($featured_status, 1); ?> />
                        <span class="status-icon">⭐</span>
                        <?php _e('Препоръчан парфюм', 'parfume-catalog'); ?>
                    </label>
                    
                    <label>
                        <input type="checkbox" 
                               name="parfume_trending" 
                               value="1" 
                               <?php checked($trending_status, 1); ?> />
                        <span class="status-icon">🔥</span>
                        <?php _e('Trending', 'parfume-catalog'); ?>
                    </label>
                    
                    <label>
                        <input type="checkbox" 
                               name="parfume_editor_choice" 
                               value="1" 
                               <?php checked($editor_choice, 1); ?> />
                        <span class="status-icon">🏆</span>
                        <?php _e('Избор на редакцията', 'parfume-catalog'); ?>
                    </label>
                </div>
            </div>
            
            <div class="override-actions">
                <button type="button" class="button button-secondary" id="save-overrides" data-post-id="<?php echo $post->ID; ?>">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Приложи промените', 'parfume-catalog'); ?>
                </button>
            </div>
        </div>
        
        <style>
        .manual-overrides-container {
            padding: 15px 0;
        }
        
        .override-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .override-section:last-child {
            border-bottom: none;
            margin-bottom: 15px;
        }
        
        .override-section h4 {
            margin: 0 0 12px 0;
            font-size: 13px;
            color: #495057;
        }
        
        .rating-override label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .manual-rating-input {
            margin-left: 20px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        
        .manual-rating-input label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .status-checkboxes {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .status-checkboxes label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .status-checkboxes label:hover {
            background: #e9ecef;
            border-color: #0073aa;
        }
        
        .status-checkboxes input:checked + .status-icon {
            filter: brightness(1.2);
        }
        
        .status-icon {
            font-size: 14px;
        }
        
        .override-actions {
            text-align: center;
        }
        
        .override-actions .button {
            width: 100%;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle manual rating input
            $('input[name="parfume_manual_rating_override"]').change(function() {
                if ($(this).is(':checked')) {
                    $('.manual-rating-input').slideDown();
                } else {
                    $('.manual-rating-input').slideUp();
                }
            });
            
            // Save overrides
            $('#save-overrides').click(function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                
                var data = {
                    action: 'parfume_manual_rating_override',
                    post_id: postId,
                    manual_rating_override: $('input[name="parfume_manual_rating_override"]').is(':checked') ? 1 : 0,
                    manual_rating: $('input[name="parfume_manual_rating"]').val(),
                    featured: $('input[name="parfume_featured"]').is(':checked') ? 1 : 0,
                    trending: $('input[name="parfume_trending"]').is(':checked') ? 1 : 0,
                    editor_choice: $('input[name="parfume_editor_choice"]').is(':checked') ? 1 : 0,
                    nonce: parfumeMetaStats.nonce
                };
                
                $btn.prop('disabled', true).text('<?php _e('Запазване...', 'parfume-catalog'); ?>');
                
                $.post(parfumeMetaStats.ajax_url, data, function(response) {
                    if (response.success) {
                        showMessage(parfumeMetaStats.texts.override_success, 'success');
                        // Refresh stats
                        $('#recalculate-stats').trigger('click');
                    } else {
                        showMessage(response.data.message || 'Грешка при запазване', 'error');
                    }
                }).always(function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> <?php _e('Приложи промените', 'parfume-catalog'); ?>');
                });
            });
            
            function showMessage(text, type) {
                var alertClass = type === 'success' ? 'notice-success' : 'notice-error';
                $('<div class="notice ' + alertClass + ' is-dismissible"><p>' + text + '</p></div>')
                    .insertAfter('.manual-overrides-container')
                    .delay(3000)
                    .fadeOut();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['parfume_stats_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['parfume_stats_meta_nonce_field'], 'parfume_stats_meta_nonce')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save manual overrides
        $manual_rating_override = isset($_POST['parfume_manual_rating_override']) ? 1 : 0;
        update_post_meta($post_id, '_parfume_manual_rating_override', $manual_rating_override);
        
        if ($manual_rating_override && isset($_POST['parfume_manual_rating'])) {
            $manual_rating = floatval($_POST['parfume_manual_rating']);
            $manual_rating = max(1, min(5, $manual_rating)); // Clamp between 1-5
            update_post_meta($post_id, '_parfume_manual_rating', $manual_rating);
        }
        
        // Save special statuses
        $featured = isset($_POST['parfume_featured']) ? 1 : 0;
        update_post_meta($post_id, '_parfume_featured', $featured);
        
        $trending = isset($_POST['parfume_trending']) ? 1 : 0;
        update_post_meta($post_id, '_parfume_trending', $trending);
        
        $editor_choice = isset($_POST['parfume_editor_choice']) ? 1 : 0;
        update_post_meta($post_id, '_parfume_editor_choice', $editor_choice);
    }
    
    /**
     * AJAX: Recalculate stats
     */
    public function ajax_recalculate_stats() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_stats')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        
        // Recalculate all stats
        $this->recalculate_parfume_stats($post_id);
        
        // Get updated stats
        $stats = $this->get_parfume_stats($post_id);
        
        wp_send_json_success(array(
            'message' => __('Статистиките са преизчислени успешно', 'parfume-catalog'),
            'stats' => $stats
        ));
    }
    
    /**
     * AJAX: Reset stats
     */
    public function ajax_reset_stats() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_stats')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        
        // Reset all stats
        delete_post_meta($post_id, '_parfume_stats_cache');
        delete_post_meta($post_id, '_parfume_views_count');
        delete_post_meta($post_id, '_parfume_monthly_views');
        delete_post_meta($post_id, '_parfume_weekly_views');
        
        wp_send_json_success(array(
            'message' => __('Статистиките са нулирани успешно', 'parfume-catalog')
        ));
    }
    
    /**
     * AJAX: Get stats data
     */
    public function ajax_get_stats_data() {
        // Проверка на nonce
        if (!wp_verify_nonce($_GET['nonce'], 'parfume_meta_stats')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        $post_id = absint($_GET['post_id']);
        $stats = $this->get_parfume_stats($post_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Manual rating override
     */
    public function ajax_manual_rating_override() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_stats')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        
        // Save manual rating override
        $manual_rating_override = absint($_POST['manual_rating_override']);
        update_post_meta($post_id, '_parfume_manual_rating_override', $manual_rating_override);
        
        if ($manual_rating_override) {
            $manual_rating = floatval($_POST['manual_rating']);
            $manual_rating = max(1, min(5, $manual_rating));
            update_post_meta($post_id, '_parfume_manual_rating', $manual_rating);
        }
        
        // Save special statuses
        update_post_meta($post_id, '_parfume_featured', absint($_POST['featured']));
        update_post_meta($post_id, '_parfume_trending', absint($_POST['trending']));
        update_post_meta($post_id, '_parfume_editor_choice', absint($_POST['editor_choice']));
        
        wp_send_json_success(array(
            'message' => __('Ръчните настройки са запазени успешно', 'parfume-catalog')
        ));
    }
    
    /**
     * Helper methods
     */
    
    private function get_parfume_stats($post_id) {
        // Check cache first
        $cached_stats = get_post_meta($post_id, '_parfume_stats_cache', true);
        if ($cached_stats && (time() - $cached_stats['timestamp']) < 3600) { // Cache for 1 hour
            return $cached_stats['data'];
        }
        
        // Calculate fresh stats
        $stats = array(
            'average_rating' => $this->calculate_average_rating($post_id),
            'total_ratings' => $this->get_total_ratings($post_id),
            'total_views' => $this->get_total_views($post_id),
            'monthly_views' => $this->get_monthly_views($post_id),
            'weekly_views' => $this->get_weekly_views($post_id),
            'popularity_rank' => $this->calculate_popularity_rank($post_id),
            'last_updated' => current_time('mysql')
        );
        
        // Cache the stats
        update_post_meta($post_id, '_parfume_stats_cache', array(
            'data' => $stats,
            'timestamp' => time()
        ));
        
        return $stats;
    }
    
    private function calculate_average_rating($post_id) {
        // Check for manual override
        $manual_override = get_post_meta($post_id, '_parfume_manual_rating_override', true);
        if ($manual_override) {
            return floatval(get_post_meta($post_id, '_parfume_manual_rating', true) ?: 5);
        }
        
        // Calculate from comments
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as total FROM $comments_table WHERE post_id = %d AND status = 'approved'",
            $post_id
        ));
        
        return $result ? floatval($result->average) : 0;
    }
    
    private function get_total_ratings($post_id) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $comments_table WHERE post_id = %d AND status = 'approved'",
            $post_id
        )) ?: 0;
    }
    
    private function get_total_views($post_id) {
        return get_post_meta($post_id, '_parfume_views_count', true) ?: 0;
    }
    
    private function get_monthly_views($post_id) {
        return get_post_meta($post_id, '_parfume_monthly_views', true) ?: 0;
    }
    
    private function get_weekly_views($post_id) {
        return get_post_meta($post_id, '_parfume_weekly_views', true) ?: 0;
    }
    
    private function calculate_popularity_rank($post_id) {
        global $wpdb;
        
        // Simple ranking based on views and ratings
        $total_views = $this->get_total_views($post_id);
        $average_rating = $this->calculate_average_rating($post_id);
        
        $score = ($total_views * 0.7) + ($average_rating * 100 * 0.3);
        
        $rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM $wpdb->posts p 
             WHERE p.post_type = 'parfumes' 
             AND p.post_status = 'publish' 
             AND p.ID != %d",
            $post_id
        ));
        
        return $rank ?: 1;
    }
    
    private function get_ratings_breakdown($post_id) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        $breakdown = array();
        for ($i = 1; $i <= 5; $i++) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $comments_table WHERE post_id = %d AND rating = %d AND status = 'approved'",
                $post_id,
                $i
            ));
            $breakdown[$i] = intval($count);
        }
        
        $total = array_sum($breakdown);
        $average = $total > 0 ? array_sum(array_map(function($rating, $count) {
            return $rating * $count;
        }, array_keys($breakdown), $breakdown)) / $total : 0;
        
        // Find most common rating
        $most_common = array_keys($breakdown, max($breakdown))[0];
        
        return array(
            'breakdown' => $breakdown,
            'total' => $total,
            'average' => $average,
            'most_common' => $most_common
        );
    }
    
    private function get_recent_comments($post_id, $limit = 5) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $comments_table WHERE post_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT %d",
            $post_id,
            $limit
        ));
    }
    
    private function get_analytics_data($post_id) {
        // Simulate analytics data (in real implementation, integrate with analytics service)
        return array(
            'views' => array(
                'total' => $this->get_total_views($post_id),
                'monthly' => $this->get_monthly_views($post_id),
                'weekly' => $this->get_weekly_views($post_id)
            ),
            'engagement' => array(
                'comments' => $this->get_total_ratings($post_id),
                'comparisons' => rand(5, 50),
                'time_on_page' => rand(30, 180)
            ),
            'search' => array(
                'organic_clicks' => rand(10, 100),
                'internal_searches' => rand(5, 30),
                'related_views' => rand(20, 80)
            ),
            'timeline_data' => array(
                'labels' => array_map(function($i) {
                    return date('M j', strtotime("-$i days"));
                }, range(29, 0)),
                'data' => array_map(function() {
                    return rand(0, 50);
                }, range(0, 29))
            ),
            'top_referrers' => array(
                array('source' => 'Google', 'visits' => rand(100, 500), 'percentage' => rand(30, 60)),
                array('source' => 'Facebook', 'visits' => rand(50, 200), 'percentage' => rand(15, 30)),
                array('source' => 'Direct', 'visits' => rand(30, 150), 'percentage' => rand(10, 25)),
                array('source' => 'Instagram', 'visits' => rand(20, 100), 'percentage' => rand(5, 15)),
                array('source' => 'Other', 'visits' => rand(10, 50), 'percentage' => rand(2, 10))
            )
        );
    }
    
    private function recalculate_parfume_stats($post_id) {
        // Force recalculation by deleting cache
        delete_post_meta($post_id, '_parfume_stats_cache');
        
        // Recalculate and cache
        return $this->get_parfume_stats($post_id);
    }
    
    /**
     * Static helper methods for external access
     */
    public static function get_parfume_rating($post_id) {
        $instance = new self();
        return $instance->calculate_average_rating($post_id);
    }
    
    public static function get_parfume_views($post_id) {
        return get_post_meta($post_id, '_parfume_views_count', true) ?: 0;
    }
    
    public static function increment_views($post_id) {
        $current_views = self::get_parfume_views($post_id);
        update_post_meta($post_id, '_parfume_views_count', $current_views + 1);
        
        // Update monthly and weekly views
        $monthly_views = get_post_meta($post_id, '_parfume_monthly_views', true) ?: 0;
        $weekly_views = get_post_meta($post_id, '_parfume_weekly_views', true) ?: 0;
        
        update_post_meta($post_id, '_parfume_monthly_views', $monthly_views + 1);
        update_post_meta($post_id, '_parfume_weekly_views', $weekly_views + 1);
    }
    
    public static function is_featured($post_id) {
        return get_post_meta($post_id, '_parfume_featured', true);
    }
    
    public static function is_trending($post_id) {
        return get_post_meta($post_id, '_parfume_trending', true);
    }
    
    public static function is_editor_choice($post_id) {
        return get_post_meta($post_id, '_parfume_editor_choice', true);
    }
    
    public static function get_rating_breakdown($post_id) {
        $instance = new self();
        return $instance->get_ratings_breakdown($post_id);
    }
}