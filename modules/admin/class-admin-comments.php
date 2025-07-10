<?php
/**
 * Admin Comments Management Class
 * 
 * Handles parfume comments/reviews system in admin panel
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Admin_Comments {
    
    private $table_name;
    private $comments_option = 'parfume_comments_settings';
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'parfume_comments';
        
        add_action('admin_menu', array($this, 'add_comments_page'));
        add_action('admin_init', array($this, 'register_comments_settings'));
        add_action('wp_ajax_parfume_moderate_comment', array($this, 'moderate_comment'));
        add_action('wp_ajax_parfume_delete_comment', array($this, 'delete_comment'));
        add_action('wp_ajax_parfume_bulk_moderate', array($this, 'bulk_moderate'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'show_pending_comments_notice'));
        
        // Create table on activation
        register_activation_hook(__FILE__, array($this, 'create_comments_table'));
    }
    
    /**
     * Add comments management page to admin menu
     */
    public function add_comments_page() {
        $pending_count = $this->get_pending_count();
        $menu_title = __('Коментари', 'parfume-catalog');
        
        if ($pending_count > 0) {
            $menu_title .= ' <span class="awaiting-mod count-' . $pending_count . '"><span class="pending-count">' . $pending_count . '</span></span>';
        }
        
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Коментари', 'parfume-catalog'),
            $menu_title,
            'moderate_comments',
            'parfume-comments',
            array($this, 'render_comments_page')
        );
    }
    
    /**
     * Register comments settings
     */
    public function register_comments_settings() {
        register_setting('parfume_catalog_settings', 'parfume_comments_enabled', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_require_moderation', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_allow_anonymous', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_require_email', array(
            'type' => 'boolean',
            'default' => false
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_enable_captcha', array(
            'type' => 'boolean',
            'default' => false
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_captcha_question', array(
            'type' => 'string',
            'default' => __('Колко е 2 + 3?', 'parfume-catalog'),
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_captcha_answer', array(
            'type' => 'string',
            'default' => '5',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_limit_per_ip', array(
            'type' => 'integer',
            'default' => 1,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_notification_email', array(
            'type' => 'string',
            'default' => get_option('admin_email'),
            'sanitize_callback' => 'sanitize_email'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_blocked_words', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comments_blocked_domains', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'parfumes_page_parfume-comments') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-tabs');
    }
    
    /**
     * Show admin notice for pending comments
     */
    public function show_pending_comments_notice() {
        $screen = get_current_screen();
        if ($screen->post_type !== 'parfumes') {
            return;
        }
        
        $pending_count = $this->get_pending_count();
        if ($pending_count > 0) {
            echo '<div class="notice notice-warning">';
            echo '<p>';
            printf(
                _n(
                    'Имате %d коментар, който чака одобрение.',
                    'Имате %d коментара, които чакат одобрение.',
                    $pending_count,
                    'parfume-catalog'
                ),
                $pending_count
            );
            echo ' <a href="' . admin_url('edit.php?post_type=parfumes&page=parfume-comments') . '">' . __('Прегледайте ги тук', 'parfume-catalog') . '</a>';
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Render the comments management page
     */
    public function render_comments_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'moderation';
        $comments = $this->get_comments($current_tab);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Управление на коментари', 'parfume-catalog'); ?></h1>
            
            <div id="comments-tabs">
                <ul>
                    <li><a href="#moderation-tab"><?php _e('Модериране', 'parfume-catalog'); ?></a></li>
                    <li><a href="#settings-tab"><?php _e('Настройки', 'parfume-catalog'); ?></a></li>
                    <li><a href="#spam-protection-tab"><?php _e('Спам защита', 'parfume-catalog'); ?></a></li>
                    <li><a href="#statistics-tab"><?php _e('Статистики', 'parfume-catalog'); ?></a></li>
                </ul>
                
                <!-- Moderation Tab -->
                <div id="moderation-tab">
                    <div class="comments-filter-bar">
                        <div class="filter-links">
                            <a href="?post_type=parfumes&page=parfume-comments&status=all" 
                               class="<?php echo ($current_tab === 'all' || empty($current_tab)) ? 'current' : ''; ?>">
                                <?php _e('Всички', 'parfume-catalog'); ?> (<?php echo $this->get_comments_count('all'); ?>)
                            </a>
                            <a href="?post_type=parfumes&page=parfume-comments&status=pending" 
                               class="<?php echo ($current_tab === 'pending') ? 'current' : ''; ?>">
                                <?php _e('Чакащи', 'parfume-catalog'); ?> (<?php echo $this->get_comments_count('pending'); ?>)
                            </a>
                            <a href="?post_type=parfumes&page=parfume-comments&status=approved" 
                               class="<?php echo ($current_tab === 'approved') ? 'current' : ''; ?>">
                                <?php _e('Одобрени', 'parfume-catalog'); ?> (<?php echo $this->get_comments_count('approved'); ?>)
                            </a>
                            <a href="?post_type=parfumes&page=parfume-comments&status=rejected" 
                               class="<?php echo ($current_tab === 'rejected') ? 'current' : ''; ?>">
                                <?php _e('Отхвърлени', 'parfume-catalog'); ?> (<?php echo $this->get_comments_count('rejected'); ?>)
                            </a>
                        </div>
                        
                        <div class="bulk-actions">
                            <select id="bulk-action-selector">
                                <option value=""><?php _e('Масови действия', 'parfume-catalog'); ?></option>
                                <option value="approve"><?php _e('Одобри', 'parfume-catalog'); ?></option>
                                <option value="reject"><?php _e('Отхвърли', 'parfume-catalog'); ?></option>
                                <option value="delete"><?php _e('Изтрий', 'parfume-catalog'); ?></option>
                            </select>
                            <button type="button" id="bulk-action-apply" class="button"><?php _e('Приложи', 'parfume-catalog'); ?></button>
                        </div>
                    </div>
                    
                    <div class="comments-list">
                        <?php if (empty($comments)): ?>
                            <div class="no-comments">
                                <p><?php _e('Няма коментари за показване.', 'parfume-catalog'); ?></p>
                            </div>
                        <?php else: ?>
                            <form id="comments-form">
                                <?php foreach ($comments as $comment): ?>
                                    <?php $this->render_comment_row($comment); ?>
                                <?php endforeach; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings-tab">
                    <h3><?php _e('Основни настройки', 'parfume-catalog'); ?></h3>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_catalog_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Разреши коментари', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comments_enabled" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comments_enabled', true)); ?> />
                                        <?php _e('Разреши коментари и оценки на парфюми', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Модериране', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comments_require_moderation" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comments_require_moderation', true)); ?> />
                                        <?php _e('Всички коментари трябва да бъдат одобрени преди публикуване', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Анонимни коментари', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comments_allow_anonymous" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comments_allow_anonymous', true)); ?> />
                                        <?php _e('Разреши коментари без попълване на име', 'parfume-catalog'); ?>
                                    </label>
                                    <p class="description"><?php _e('При празно име се показва "Анонимен"', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Задължителен имейл', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comments_require_email" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comments_require_email', false)); ?> />
                                        <?php _e('Изисквай имейл адрес при коментиране', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comments_limit_per_ip"><?php _e('Ограничение за IP', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_comments_limit_per_ip" 
                                           name="parfume_comments_limit_per_ip" 
                                           value="<?php echo esc_attr(get_option('parfume_comments_limit_per_ip', 1)); ?>" 
                                           min="1" max="10" />
                                    <p class="description"><?php _e('Максимален брой коментари на парфюм от един IP адрес', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comments_notification_email"><?php _e('Имейл за известяване', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="email" 
                                           id="parfume_comments_notification_email" 
                                           name="parfume_comments_notification_email" 
                                           value="<?php echo esc_attr(get_option('parfume_comments_notification_email', get_option('admin_email'))); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Имейл адрес за получаване на известия за нови коментари', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <!-- Spam Protection Tab -->
                <div id="spam-protection-tab">
                    <h3><?php _e('Спам защита', 'parfume-catalog'); ?></h3>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_catalog_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('CAPTCHA защита', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comments_enable_captcha" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comments_enable_captcha', false)); ?> />
                                        <?php _e('Разреши CAPTCHA за коментари', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comments_captcha_question"><?php _e('CAPTCHA въпрос', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comments_captcha_question" 
                                           name="parfume_comments_captcha_question" 
                                           value="<?php echo esc_attr(get_option('parfume_comments_captcha_question', __('Колко е 2 + 3?', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comments_captcha_answer"><?php _e('CAPTCHA отговор', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comments_captcha_answer" 
                                           name="parfume_comments_captcha_answer" 
                                           value="<?php echo esc_attr(get_option('parfume_comments_captcha_answer', '5')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comments_blocked_words"><?php _e('Блокирани думи', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <textarea id="parfume_comments_blocked_words" 
                                              name="parfume_comments_blocked_words" 
                                              rows="5" 
                                              class="large-text"><?php echo esc_textarea(get_option('parfume_comments_blocked_words', '')); ?></textarea>
                                    <p class="description"><?php _e('Една дума на ред. Коментари съдържащи тези думи ще бъдат автоматично блокирани.', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comments_blocked_domains"><?php _e('Блокирани домейни', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <textarea id="parfume_comments_blocked_domains" 
                                              name="parfume_comments_blocked_domains" 
                                              rows="5" 
                                              class="large-text"><?php echo esc_textarea(get_option('parfume_comments_blocked_domains', '')); ?></textarea>
                                    <p class="description"><?php _e('Един домейн на ред. Коментари съдържащи линкове към тези домейни ще бъдат автоматично блокирани.', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <!-- Statistics Tab -->
                <div id="statistics-tab">
                    <h3><?php _e('Статистики на коментарите', 'parfume-catalog'); ?></h3>
                    
                    <div class="statistics-grid">
                        <?php $stats = $this->get_comments_statistics(); ?>
                        
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="stat-label"><?php _e('Общо коментари', 'parfume-catalog'); ?></div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['approved']; ?></div>
                            <div class="stat-label"><?php _e('Одобрени', 'parfume-catalog'); ?></div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['pending']; ?></div>
                            <div class="stat-label"><?php _e('Чакащи', 'parfume-catalog'); ?></div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                            <div class="stat-label"><?php _e('Отхвърлени', 'parfume-catalog'); ?></div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number"><?php echo number_format($stats['average_rating'], 1); ?></div>
                            <div class="stat-label"><?php _e('Среден рейтинг', 'parfume-catalog'); ?></div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['today']; ?></div>
                            <div class="stat-label"><?php _e('Днес', 'parfume-catalog'); ?></div>
                        </div>
                    </div>
                    
                    <h4><?php _e('Топ парфюми по коментари', 'parfume-catalog'); ?></h4>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Парфюм', 'parfume-catalog'); ?></th>
                                <th><?php _e('Коментари', 'parfume-catalog'); ?></th>
                                <th><?php _e('Среден рейтинг', 'parfume-catalog'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_parfumes'] as $parfume): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($parfume['post_id']); ?>">
                                            <?php echo esc_html($parfume['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $parfume['comment_count']; ?></td>
                                    <td><?php echo number_format($parfume['average_rating'], 1); ?> ⭐</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <style>
        .comments-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .filter-links a {
            margin-right: 15px;
            text-decoration: none;
            color: #0073aa;
        }
        
        .filter-links a.current {
            font-weight: bold;
            color: #000;
        }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .comment-row {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            padding: 15px;
            background: #fff;
        }
        
        .comment-row.pending {
            border-left: 4px solid #ffb900;
        }
        
        .comment-row.approved {
            border-left: 4px solid #46b450;
        }
        
        .comment-row.rejected {
            border-left: 4px solid #dc3232;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .comment-meta {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .comment-author {
            font-weight: bold;
        }
        
        .comment-rating {
            color: #ffb900;
        }
        
        .comment-date {
            color: #666;
            font-size: 13px;
        }
        
        .comment-parfume {
            color: #0073aa;
            font-size: 13px;
        }
        
        .comment-actions {
            display: flex;
            gap: 5px;
        }
        
        .comment-content {
            margin: 10px 0;
            line-height: 1.6;
        }
        
        .comment-ip {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
            color: #0073aa;
        }
        
        .stat-label {
            font-size: 13px;
            color: #666;
        }
        
        .no-comments {
            text-align: center;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize tabs
            $('#comments-tabs').tabs();
            
            // Moderate comment
            $('.moderate-comment').click(function() {
                var commentId = $(this).data('comment-id');
                var action = $(this).data('action');
                var row = $(this).closest('.comment-row');
                
                $.post(ajaxurl, {
                    action: 'parfume_moderate_comment',
                    nonce: '<?php echo wp_create_nonce('parfume_comment_action'); ?>',
                    comment_id: commentId,
                    comment_action: action
                }, function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        location.reload(); // Refresh to update counts
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при модериране', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Delete comment
            $('.delete-comment').click(function() {
                if (!confirm('<?php _e('Сигурни ли сте, че искате да изтриете този коментар?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                var commentId = $(this).data('comment-id');
                var row = $(this).closest('.comment-row');
                
                $.post(ajaxurl, {
                    action: 'parfume_delete_comment',
                    nonce: '<?php echo wp_create_nonce('parfume_comment_action'); ?>',
                    comment_id: commentId
                }, function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при изтриване', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Bulk actions
            $('#bulk-action-apply').click(function() {
                var action = $('#bulk-action-selector').val();
                if (!action) {
                    alert('<?php _e('Моля изберете действие', 'parfume-catalog'); ?>');
                    return;
                }
                
                var selectedComments = [];
                $('.comment-checkbox:checked').each(function() {
                    selectedComments.push($(this).val());
                });
                
                if (selectedComments.length === 0) {
                    alert('<?php _e('Моля изберете коментари', 'parfume-catalog'); ?>');
                    return;
                }
                
                if (action === 'delete' && !confirm('<?php _e('Сигурни ли сте, че искате да изтриете избраните коментари?', 'parfume-catalog'); ?>')) {
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'parfume_bulk_moderate',
                    nonce: '<?php echo wp_create_nonce('parfume_comment_action'); ?>',
                    comment_ids: selectedComments,
                    bulk_action: action
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при изпълнение', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Select all checkbox
            $('#select-all-comments').change(function() {
                $('.comment-checkbox').prop('checked', $(this).is(':checked'));
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render individual comment row
     */
    private function render_comment_row($comment) {
        $post_title = get_the_title($comment->post_id);
        $status_class = strtolower($comment->status);
        ?>
        <div class="comment-row <?php echo esc_attr($status_class); ?>">
            <div class="comment-header">
                <div class="comment-meta">
                    <input type="checkbox" class="comment-checkbox" value="<?php echo esc_attr($comment->id); ?>">
                    <span class="comment-author"><?php echo esc_html($comment->author_name ?: __('Анонимен', 'parfume-catalog')); ?></span>
                    <span class="comment-rating">
                        <?php echo str_repeat('⭐', $comment->rating); ?>
                        (<?php echo $comment->rating; ?>/5)
                    </span>
                    <span class="comment-date"><?php echo esc_html($comment->created_at); ?></span>
                </div>
                
                <div class="comment-actions">
                    <?php if ($comment->status === 'pending'): ?>
                        <button type="button" class="button button-small moderate-comment" 
                                data-comment-id="<?php echo esc_attr($comment->id); ?>" 
                                data-action="approve">
                            <?php _e('Одобри', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" class="button button-small moderate-comment" 
                                data-comment-id="<?php echo esc_attr($comment->id); ?>" 
                                data-action="reject">
                            <?php _e('Отхвърли', 'parfume-catalog'); ?>
                        </button>
                    <?php elseif ($comment->status === 'approved'): ?>
                        <button type="button" class="button button-small moderate-comment" 
                                data-comment-id="<?php echo esc_attr($comment->id); ?>" 
                                data-action="reject">
                            <?php _e('Отхвърли', 'parfume-catalog'); ?>
                        </button>
                    <?php elseif ($comment->status === 'rejected'): ?>
                        <button type="button" class="button button-small moderate-comment" 
                                data-comment-id="<?php echo esc_attr($comment->id); ?>" 
                                data-action="approve">
                            <?php _e('Одобри', 'parfume-catalog'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="button button-small delete-comment" 
                            data-comment-id="<?php echo esc_attr($comment->id); ?>">
                        <?php _e('Изтрий', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div class="comment-parfume">
                <a href="<?php echo get_edit_post_link($comment->post_id); ?>">
                    <?php printf(__('Парфюм: %s', 'parfume-catalog'), esc_html($post_title)); ?>
                </a>
            </div>
            
            <div class="comment-content">
                <?php echo wp_kses_post(nl2br($comment->content)); ?>
            </div>
            
            <div class="comment-ip">
                <?php printf(__('IP: %s', 'parfume-catalog'), esc_html($comment->author_ip)); ?>
                <?php if ($comment->author_email): ?>
                    | <?php printf(__('Email: %s', 'parfume-catalog'), esc_html($comment->author_email)); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get comments for display
     */
    private function get_comments($status = 'all') {
        global $wpdb;
        
        $where = '';
        if ($status && $status !== 'all') {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }
        
        $sql = "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT 50";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get comments count by status
     */
    private function get_comments_count($status = 'all') {
        global $wpdb;
        
        if ($status === 'all') {
            return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
            $status
        ));
    }
    
    /**
     * Get pending comments count
     */
    private function get_pending_count() {
        return $this->get_comments_count('pending');
    }
    
    /**
     * Get comments statistics
     */
    private function get_comments_statistics() {
        global $wpdb;
        
        $stats = array(
            'total' => $this->get_comments_count('all'),
            'approved' => $this->get_comments_count('approved'),
            'pending' => $this->get_comments_count('pending'),
            'rejected' => $this->get_comments_count('rejected'),
            'average_rating' => 0,
            'today' => 0,
            'top_parfumes' => array()
        );
        
        // Average rating
        $avg_rating = $wpdb->get_var("SELECT AVG(rating) FROM {$this->table_name} WHERE status = 'approved'");
        $stats['average_rating'] = $avg_rating ? floatval($avg_rating) : 0;
        
        // Today's comments
        $today = date('Y-m-d');
        $stats['today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE DATE(created_at) = %s",
            $today
        ));
        
        // Top parfumes by comments
        $top_parfumes = $wpdb->get_results("
            SELECT 
                post_id,
                COUNT(*) as comment_count,
                AVG(rating) as average_rating
            FROM {$this->table_name} 
            WHERE status = 'approved'
            GROUP BY post_id 
            ORDER BY comment_count DESC 
            LIMIT 10
        ");
        
        foreach ($top_parfumes as $parfume) {
            $parfume->title = get_the_title($parfume->post_id);
            $stats['top_parfumes'][] = (array) $parfume;
        }
        
        return $stats;
    }
    
    /**
     * Moderate comment via AJAX
     */
    public function moderate_comment() {
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_comment_action', 'nonce');
        
        $comment_id = absint($_POST['comment_id']);
        $action = sanitize_text_field($_POST['comment_action']);
        
        if (!in_array($action, array('approve', 'reject'))) {
            wp_send_json_error(array('message' => __('Невалидно действие', 'parfume-catalog')));
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $this->table_name,
            array('status' => $action === 'approve' ? 'approved' : 'rejected'),
            array('id' => $comment_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Коментарът е модериран', 'parfume-catalog')));
        } else {
            wp_send_json_error(array('message' => __('Грешка при модериране', 'parfume-catalog')));
        }
    }
    
    /**
     * Delete comment via AJAX
     */
    public function delete_comment() {
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_comment_action', 'nonce');
        
        $comment_id = absint($_POST['comment_id']);
        
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $comment_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Коментарът е изтрит', 'parfume-catalog')));
        } else {
            wp_send_json_error(array('message' => __('Грешка при изтриване', 'parfume-catalog')));
        }
    }
    
    /**
     * Bulk moderate comments via AJAX
     */
    public function bulk_moderate() {
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_comment_action', 'nonce');
        
        $comment_ids = array_map('absint', $_POST['comment_ids']);
        $action = sanitize_text_field($_POST['bulk_action']);
        
        if (empty($comment_ids)) {
            wp_send_json_error(array('message' => __('Няма избрани коментари', 'parfume-catalog')));
        }
        
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
        
        if ($action === 'delete') {
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)",
                $comment_ids
            ));
        } else {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_name} SET status = %s WHERE id IN ($placeholders)",
                array_merge(array($status), $comment_ids)
            ));
        }
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Действието е изпълнено успешно', 'parfume-catalog')));
        } else {
            wp_send_json_error(array('message' => __('Грешка при изпълнение', 'parfume-catalog')));
        }
    }
    
    /**
     * Create comments table
     */
    public function create_comments_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            author_name varchar(100) DEFAULT NULL,
            author_email varchar(100) DEFAULT NULL,
            author_ip varchar(45) NOT NULL,
            rating tinyint(1) unsigned NOT NULL,
            content text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get comments settings for frontend use
     */
    public static function get_comments_settings() {
        return array(
            'enabled' => get_option('parfume_comments_enabled', true),
            'require_moderation' => get_option('parfume_comments_require_moderation', true),
            'allow_anonymous' => get_option('parfume_comments_allow_anonymous', true),
            'require_email' => get_option('parfume_comments_require_email', false),
            'enable_captcha' => get_option('parfume_comments_enable_captcha', false),
            'captcha_question' => get_option('parfume_comments_captcha_question', __('Колко е 2 + 3?', 'parfume-catalog')),
            'captcha_answer' => get_option('parfume_comments_captcha_answer', '5'),
            'limit_per_ip' => get_option('parfume_comments_limit_per_ip', 1),
            'blocked_words' => array_filter(array_map('trim', explode("\n", get_option('parfume_comments_blocked_words', '')))),
            'blocked_domains' => array_filter(array_map('trim', explode("\n", get_option('parfume_comments_blocked_domains', ''))))
        );
    }
}

// Initialize the admin comments
new Parfume_Admin_Comments();