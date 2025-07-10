<?php
/**
 * Statistics Meta Fields Class
 * 
 * Handles statistics, ratings and performance meta fields for parfumes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Meta_Stats {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_parfume_recalculate_stats', array($this, 'recalculate_stats'));
        add_action('wp_ajax_parfume_reset_stats', array($this, 'reset_stats'));
    }
    
    /**
     * Add meta boxes
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
            __('Аналитика', 'parfume-catalog'),
            array($this, 'render_analytics_meta_box'),
            'parfumes',
            'normal',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', array(), '3.9.1', true);
        }
    }
    
    /**
     * Render performance stats meta box
     */
    public function render_performance_stats_meta_box($post) {
        wp_nonce_field('parfume_stats_meta_nonce', 'parfume_stats_meta_nonce_field');
        
        // Get calculated stats
        $stats = $this->get_parfume_stats($post->ID);
        $manual_override = get_post_meta($post->ID, '_parfume_manual_stats_override', true);
        ?>
        <div class="stats-container">
            <div class="stats-header">
                <label>
                    <input type="checkbox" 
                           name="parfume_manual_stats_override" 
                           value="1" 
                           <?php checked($manual_override, '1'); ?> 
                           id="manual-stats-toggle" />
                    <?php _e('Ръчно задаване на статистики', 'parfume-catalog'); ?>
                </label>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-label"><?php _e('Общ рейтинг', 'parfume-catalog'); ?></div>
                    <div class="stat-value">
                        <?php if ($manual_override): ?>
                            <input type="number" 
                                   name="parfume_manual_overall_rating" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_parfume_manual_overall_rating', true) ?: $stats['overall_rating']); ?>" 
                                   min="0" max="5" step="0.1" 
                                   class="small-text" />
                        <?php else: ?>
                            <span class="rating-display"><?php echo number_format($stats['overall_rating'], 1); ?></span>
                        <?php endif; ?>
                        <div class="star-rating">
                            <?php echo $this->render_star_rating($stats['overall_rating']); ?>
                        </div>
                    </div>
                    <div class="stat-meta">
                        <?php printf(__('Базиран на %d оценки', 'parfume-catalog'), $stats['total_reviews']); ?>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-label"><?php _e('Общо прегледи', 'parfume-catalog'); ?></div>
                    <div class="stat-value">
                        <?php if ($manual_override): ?>
                            <input type="number" 
                                   name="parfume_manual_total_reviews" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_parfume_manual_total_reviews', true) ?: $stats['total_reviews']); ?>" 
                                   min="0" 
                                   class="small-text" />
                        <?php else: ?>
                            <span class="count-display"><?php echo number_format($stats['total_reviews']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-label"><?php _e('Популярност', 'parfume-catalog'); ?></div>
                    <div class="stat-value">
                        <?php if ($manual_override): ?>
                            <input type="number" 
                                   name="parfume_manual_popularity_score" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_parfume_manual_popularity_score', true) ?: $stats['popularity_score']); ?>" 
                                   min="0" max="100" 
                                   class="small-text" />
                        <?php else: ?>
                            <span class="score-display"><?php echo number_format($stats['popularity_score']); ?></span>
                        <?php endif; ?>
                        <div class="popularity-bar">
                            <div class="popularity-fill" style="width: <?php echo min(100, $stats['popularity_score']); ?>%;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-label"><?php _e('Месечни посещения', 'parfume-catalog'); ?></div>
                    <div class="stat-value">
                        <span class="count-display"><?php echo number_format($stats['monthly_views']); ?></span>
                    </div>
                    <div class="stat-trend">
                        <?php if ($stats['views_trend'] > 0): ?>
                            <span class="trend-up">↗ +<?php echo number_format($stats['views_trend'], 1); ?>%</span>
                        <?php elseif ($stats['views_trend'] < 0): ?>
                            <span class="trend-down">↘ <?php echo number_format($stats['views_trend'], 1); ?>%</span>
                        <?php else: ?>
                            <span class="trend-neutral">→ 0%</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="stats-actions">
                <button type="button" id="recalculate-stats" class="button button-secondary">
                    <?php _e('Преизчисли статистики', 'parfume-catalog'); ?>
                </button>
                <button type="button" id="reset-stats" class="button">
                    <?php _e('Нулирай статистики', 'parfume-catalog'); ?>
                </button>
            </div>
        </div>
        
        <style>
        .stats-container {
            padding: 10px 0;
        }
        
        .stats-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .stats-grid {
            display: grid;
            gap: 15px;
        }
        
        .stat-item {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-value input {
            font-size: 16px;
            font-weight: bold;
        }
        
        .stat-meta {
            font-size: 11px;
            color: #999;
        }
        
        .stat-trend {
            font-size: 11px;
            font-weight: 600;
        }
        
        .trend-up {
            color: #46b450;
        }
        
        .trend-down {
            color: #dc3232;
        }
        
        .trend-neutral {
            color: #666;
        }
        
        .star-rating {
            font-size: 14px;
            color: #ffb900;
            margin-top: 3px;
        }
        
        .popularity-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .popularity-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffb900 0%, #ff6900 50%, #dc3232 100%);
            transition: width 0.3s ease;
        }
        
        .stats-actions {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle manual stats override
            $('#manual-stats-toggle').change(function() {
                var isManual = $(this).is(':checked');
                if (isManual) {
                    $('.stat-value span').hide();
                    $('.stat-value input').show();
                } else {
                    $('.stat-value input').hide();
                    $('.stat-value span').show();
                }
            });
            
            // Initialize display
            $('#manual-stats-toggle').trigger('change');
            
            // Recalculate stats
            $('#recalculate-stats').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Изчислява...', 'parfume-catalog'); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_recalculate_stats',
                    nonce: '<?php echo wp_create_nonce('parfume_stats_action'); ?>',
                    post_id: <?php echo $post->ID; ?>
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при изчисляване', 'parfume-catalog'); ?>');
                    }
                    button.prop('disabled', false).text('<?php _e('Преизчисли статистики', 'parfume-catalog'); ?>');
                });
            });
            
            // Reset stats
            $('#reset-stats').click(function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да нулирате всички статистики?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Нулира...', 'parfume-catalog'); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_reset_stats',
                    nonce: '<?php echo wp_create_nonce('parfume_stats_action'); ?>',
                    post_id: <?php echo $post->ID; ?>
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при нулиране', 'parfume-catalog'); ?>');
                    }
                    button.prop('disabled', false).text('<?php _e('Нулирай статистики', 'parfume-catalog'); ?>');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render user ratings meta box
     */
    public function render_user_ratings_meta_box($post) {
        $ratings_breakdown = $this->get_ratings_breakdown($post->ID);
        $recent_reviews = $this->get_recent_reviews($post->ID, 5);
        ?>
        <div class="ratings-container">
            <div class="ratings-grid">
                <div class="ratings-breakdown">
                    <h4><?php _e('Разбивка на оценките', 'parfume-catalog'); ?></h4>
                    
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="rating-row">
                            <div class="rating-stars">
                                <?php echo str_repeat('⭐', $i); ?>
                            </div>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $ratings_breakdown[$i]['percentage']; ?>%;"></div>
                            </div>
                            <div class="rating-count">
                                <?php echo $ratings_breakdown[$i]['count']; ?> (<?php echo number_format($ratings_breakdown[$i]['percentage'], 1); ?>%)
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="rating-categories">
                    <h4><?php _e('Оценки по категории', 'parfume-catalog'); ?></h4>
                    
                    <?php
                    $category_ratings = $this->get_category_ratings($post->ID);
                    foreach ($category_ratings as $category => $rating):
                    ?>
                        <div class="category-rating">
                            <div class="category-name"><?php echo esc_html($category); ?></div>
                            <div class="category-stars">
                                <?php echo $this->render_star_rating($rating); ?>
                            </div>
                            <div class="category-score"><?php echo number_format($rating, 1); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (!empty($recent_reviews)): ?>
                <div class="recent-reviews">
                    <h4><?php _e('Последни ревюта', 'parfume-catalog'); ?></h4>
                    
                    <?php foreach ($recent_reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-author"><?php echo esc_html($review['author'] ?: __('Анонимен', 'parfume-catalog')); ?></div>
                                <div class="review-rating"><?php echo $this->render_star_rating($review['rating']); ?></div>
                                <div class="review-date"><?php echo esc_html($review['date']); ?></div>
                            </div>
                            <div class="review-content">
                                <?php echo esc_html(wp_trim_words($review['content'], 20)); ?>
                            </div>
                            <div class="review-actions">
                                <a href="<?php echo admin_url('edit.php?post_type=parfumes&page=parfume-comments&comment_id=' . $review['id']); ?>" class="button button-small">
                                    <?php _e('Прегледай', 'parfume-catalog'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .ratings-container {
            margin-top: 15px;
        }
        
        .ratings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .ratings-breakdown h4,
        .rating-categories h4,
        .recent-reviews h4 {
            margin: 0 0 15px 0;
            color: #0073aa;
        }
        
        .rating-row {
            display: grid;
            grid-template-columns: 80px 1fr 80px;
            gap: 10px;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .rating-stars {
            font-size: 12px;
        }
        
        .rating-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .rating-fill {
            height: 100%;
            background: #ffb900;
            transition: width 0.3s ease;
        }
        
        .rating-count {
            font-size: 11px;
            color: #666;
            text-align: right;
        }
        
        .category-rating {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            align-items: center;
            margin-bottom: 8px;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .category-name {
            font-size: 13px;
            font-weight: 500;
        }
        
        .category-stars {
            font-size: 12px;
            color: #ffb900;
        }
        
        .category-score {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            min-width: 25px;
            text-align: right;
        }
        
        .recent-reviews {
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .review-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .review-author {
            font-weight: 600;
        }
        
        .review-rating {
            color: #ffb900;
        }
        
        .review-date {
            color: #666;
        }
        
        .review-content {
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
            color: #555;
        }
        
        .review-actions {
            text-align: right;
        }
        </style>
        <?php
    }
    
    /**
     * Render analytics meta box
     */
    public function render_analytics_meta_box($post) {
        $analytics = $this->get_parfume_analytics($post->ID);
        ?>
        <div class="analytics-container">
            <div class="analytics-grid">
                <div class="analytics-chart">
                    <h4><?php _e('Посещения през последните 30 дни', 'parfume-catalog'); ?></h4>
                    <canvas id="views-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="analytics-metrics">
                    <h4><?php _e('Ключови метрики', 'parfume-catalog'); ?></h4>
                    
                    <div class="metric-item">
                        <div class="metric-label"><?php _e('Брой сравнения', 'parfume-catalog'); ?></div>
                        <div class="metric-value"><?php echo number_format($analytics['comparison_count']); ?></div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-label"><?php _e('Клик върху магазини', 'parfume-catalog'); ?></div>
                        <div class="metric-value"><?php echo number_format($analytics['store_clicks']); ?></div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-label"><?php _e('Споделяния', 'parfume-catalog'); ?></div>
                        <div class="metric-value"><?php echo number_format($analytics['shares']); ?></div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-label"><?php _e('Време на страницата', 'parfume-catalog'); ?></div>
                        <div class="metric-value"><?php echo $this->format_duration($analytics['avg_time_on_page']); ?></div>
                    </div>
                    
                    <div class="metric-item">
                        <div class="metric-label"><?php _e('Отказни посещения', 'parfume-catalog'); ?></div>
                        <div class="metric-value"><?php echo number_format($analytics['bounce_rate'], 1); ?>%</div>
                    </div>
                </div>
            </div>
            
            <div class="analytics-sources">
                <h4><?php _e('Топ източници на трафик', 'parfume-catalog'); ?></h4>
                <div class="sources-list">
                    <?php foreach ($analytics['traffic_sources'] as $source): ?>
                        <div class="source-item">
                            <div class="source-name"><?php echo esc_html($source['name']); ?></div>
                            <div class="source-bar">
                                <div class="source-fill" style="width: <?php echo $source['percentage']; ?>%;"></div>
                            </div>
                            <div class="source-percentage"><?php echo number_format($source['percentage'], 1); ?>%</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <style>
        .analytics-container {
            margin-top: 15px;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .analytics-chart h4,
        .analytics-metrics h4,
        .analytics-sources h4 {
            margin: 0 0 15px 0;
            color: #0073aa;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 5px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .metric-label {
            font-size: 13px;
            color: #555;
        }
        
        .metric-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        
        .analytics-sources {
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .source-item {
            display: grid;
            grid-template-columns: 150px 1fr 60px;
            gap: 10px;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .source-name {
            font-size: 13px;
            font-weight: 500;
        }
        
        .source-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .source-fill {
            height: 100%;
            background: #0073aa;
            transition: width 0.3s ease;
        }
        
        .source-percentage {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize views chart
            var ctx = document.getElementById('views-chart').getContext('2d');
            var viewsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($analytics['views_chart']['labels']); ?>,
                    datasets: [{
                        label: '<?php _e('Посещения', 'parfume-catalog'); ?>',
                        data: <?php echo json_encode($analytics['views_chart']['data']); ?>,
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
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
        
        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save manual stats override
        $manual_override = isset($_POST['parfume_manual_stats_override']) ? '1' : '0';
        update_post_meta($post_id, '_parfume_manual_stats_override', $manual_override);
        
        // Save manual stats if override is enabled
        if ($manual_override === '1') {
            $manual_fields = array(
                'parfume_manual_overall_rating' => 'floatval',
                'parfume_manual_total_reviews' => 'absint',
                'parfume_manual_popularity_score' => 'absint'
            );
            
            foreach ($manual_fields as $field => $sanitize_function) {
                if (isset($_POST[$field])) {
                    $value = $sanitize_function($_POST[$field]);
                    
                    // Validate ranges
                    if ($field === 'parfume_manual_overall_rating') {
                        $value = max(0, min(5, $value));
                    } elseif ($field === 'parfume_manual_popularity_score') {
                        $value = max(0, min(100, $value));
                    }
                    
                    update_post_meta($post_id, '_' . $field, $value);
                }
            }
        }
    }
    
    /**
     * Get parfume statistics
     */
    private function get_parfume_stats($post_id) {
        $manual_override = get_post_meta($post_id, '_parfume_manual_stats_override', true);
        
        if ($manual_override === '1') {
            // Return manual stats
            return array(
                'overall_rating' => floatval(get_post_meta($post_id, '_parfume_manual_overall_rating', true)) ?: 0,
                'total_reviews' => absint(get_post_meta($post_id, '_parfume_manual_total_reviews', true)) ?: 0,
                'popularity_score' => absint(get_post_meta($post_id, '_parfume_manual_popularity_score', true)) ?: 0,
                'monthly_views' => $this->get_monthly_views($post_id),
                'views_trend' => $this->get_views_trend($post_id)
            );
        }
        
        // Calculate automatic stats
        return array(
            'overall_rating' => $this->calculate_overall_rating($post_id),
            'total_reviews' => $this->get_total_reviews($post_id),
            'popularity_score' => $this->calculate_popularity_score($post_id),
            'monthly_views' => $this->get_monthly_views($post_id),
            'views_trend' => $this->get_views_trend($post_id)
        );
    }
    
    /**
     * Calculate overall rating
     */
    private function calculate_overall_rating($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        $avg_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $table_name WHERE post_id = %d AND status = 'approved'",
            $post_id
        ));
        
        return $avg_rating ? floatval($avg_rating) : 0;
    }
    
    /**
     * Get total reviews count
     */
    private function get_total_reviews($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND status = 'approved'",
            $post_id
        ));
        
        return $count ? absint($count) : 0;
    }
    
    /**
     * Calculate popularity score
     */
    private function calculate_popularity_score($post_id) {
        $views = $this->get_monthly_views($post_id);
        $reviews = $this->get_total_reviews($post_id);
        $rating = $this->calculate_overall_rating($post_id);
        
        // Weighted popularity score (0-100)
        $score = ($views * 0.4) + ($reviews * 2) + ($rating * 10);
        return min(100, max(0, $score));
    }
    
    /**
     * Get monthly views
     */
    private function get_monthly_views($post_id) {
        // Mock data - would integrate with analytics system
        return rand(100, 5000);
    }
    
    /**
     * Get views trend
     */
    private function get_views_trend($post_id) {
        // Mock data - would calculate trend from analytics
        return rand(-50, 50) / 10;
    }
    
    /**
     * Get ratings breakdown
     */
    private function get_ratings_breakdown($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        $total_reviews = $this->get_total_reviews($post_id);
        
        $breakdown = array();
        
        for ($i = 1; $i <= 5; $i++) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND status = 'approved' AND rating = %d",
                $post_id,
                $i
            ));
            
            $count = $count ? absint($count) : 0;
            $percentage = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
            
            $breakdown[$i] = array(
                'count' => $count,
                'percentage' => $percentage
            );
        }
        
        return $breakdown;
    }
    
    /**
     * Get category ratings
     */
    private function get_category_ratings($post_id) {
        // Mock data - would calculate from detailed ratings
        return array(
            __('Дълготрайност', 'parfume-catalog') => 4.2,
            __('Ароматна следа', 'parfume-catalog') => 3.8,
            __('Съотношение цена/качество', 'parfume-catalog') => 4.1,
            __('Оригиналност', 'parfume-catalog') => 3.9,
            __('Универсалност', 'parfume-catalog') => 4.0
        );
    }
    
    /**
     * Get recent reviews
     */
    private function get_recent_reviews($post_id, $limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT %d",
            $post_id,
            $limit
        ));
        
        $formatted_reviews = array();
        foreach ($reviews as $review) {
            $formatted_reviews[] = array(
                'id' => $review->id,
                'author' => $review->author_name,
                'rating' => $review->rating,
                'content' => $review->content,
                'date' => date('d.m.Y', strtotime($review->created_at))
            );
        }
        
        return $formatted_reviews;
    }
    
    /**
     * Get parfume analytics
     */
    private function get_parfume_analytics($post_id) {
        // Mock analytics data - would integrate with actual analytics
        return array(
            'comparison_count' => rand(50, 500),
            'store_clicks' => rand(100, 1000),
            'shares' => rand(10, 100),
            'avg_time_on_page' => rand(120, 600), // seconds
            'bounce_rate' => rand(20, 80),
            'views_chart' => array(
                'labels' => array_map(function($i) {
                    return date('d.m', strtotime("-$i days"));
                }, range(29, 0)),
                'data' => array_map(function() {
                    return rand(10, 200);
                }, range(0, 29))
            ),
            'traffic_sources' => array(
                array('name' => 'Google Search', 'percentage' => 45.2),
                array('name' => 'Direct', 'percentage' => 28.1),
                array('name' => 'Social Media', 'percentage' => 15.7),
                array('name' => 'Referrals', 'percentage' => 11.0)
            )
        );
    }
    
    /**
     * Render star rating
     */
    private function render_star_rating($rating) {
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        $html = str_repeat('⭐', $full_stars);
        if ($half_star) {
            $html .= '⭐'; // Could use half-star character
        }
        $html .= str_repeat('☆', $empty_stars);
        
        return $html;
    }
    
    /**
     * Format duration in seconds to readable format
     */
    private function format_duration($seconds) {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        } else {
            return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        }
    }
    
    /**
     * Recalculate stats via AJAX
     */
    public function recalculate_stats() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_stats_action', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        
        // Force recalculation by clearing cached stats
        delete_post_meta($post_id, '_parfume_cached_stats');
        
        wp_send_json_success(array('message' => __('Статистиките са преизчислени', 'parfume-catalog')));
    }
    
    /**
     * Reset stats via AJAX
     */
    public function reset_stats() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_stats_action', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        
        // Reset all stats-related meta
        $meta_keys = array(
            '_parfume_manual_stats_override',
            '_parfume_manual_overall_rating',
            '_parfume_manual_total_reviews',
            '_parfume_manual_popularity_score',
            '_parfume_cached_stats'
        );
        
        foreach ($meta_keys as $key) {
            delete_post_meta($post_id, $key);
        }
        
        wp_send_json_success(array('message' => __('Статистиките са нулирани', 'parfume-catalog')));
    }
    
    /**
     * Get parfume stats for public use
     */
    public static function get_public_stats($post_id) {
        $instance = new self();
        return $instance->get_parfume_stats($post_id);
    }
    
    /**
     * Get formatted rating for display
     */
    public static function get_formatted_rating($post_id) {
        $stats = self::get_public_stats($post_id);
        return array(
            'rating' => $stats['overall_rating'],
            'count' => $stats['total_reviews'],
            'stars_html' => (new self())->render_star_rating($stats['overall_rating'])
        );
    }
    
    /**
     * Check if parfume has ratings
     */
    public static function has_ratings($post_id) {
        $stats = self::get_public_stats($post_id);
        return $stats['total_reviews'] > 0;
    }
}

// Initialize the stats meta fields
new Parfume_Meta_Stats();