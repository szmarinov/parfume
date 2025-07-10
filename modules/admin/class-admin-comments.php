<?php
/**
 * Parfume Catalog Admin Comments
 * 
 * Управление на коментари и ревюта в админ панела
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin_Comments {
    
    /**
     * Име на таблицата с коментари
     */
    private $table_name;
    
    /**
     * Настройки опция
     */
    private $settings_option = 'parfume_comments_settings';
    
    /**
     * Конструктор
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'parfume_comments';
        
        add_action('admin_menu', array($this, 'add_comments_page'));
        add_action('admin_init', array($this, 'register_comments_settings'));
        add_action('wp_ajax_parfume_moderate_comment', array($this, 'ajax_moderate_comment'));
        add_action('wp_ajax_parfume_delete_comment', array($this, 'ajax_delete_comment'));
        add_action('wp_ajax_parfume_bulk_moderate', array($this, 'ajax_bulk_moderate'));
        add_action('wp_ajax_parfume_get_comment_stats', array($this, 'ajax_get_comment_stats'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'show_pending_comments_notice'));
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }
    
    /**
     * Добавяне на страница в админ менюто
     */
    public function add_comments_page() {
        $pending_count = $this->get_pending_count();
        $menu_title = __('Коментари', 'parfume-catalog');
        
        if ($pending_count > 0) {
            $menu_title .= ' <span class="awaiting-mod count-' . $pending_count . '"><span class="pending-count">' . $pending_count . '</span></span>';
        }
        
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Управление на коментари', 'parfume-catalog'),
            $menu_title,
            'manage_options',
            'parfume-comments',
            array($this, 'render_comments_page')
        );
    }
    
    /**
     * Регистрация на settings
     */
    public function register_comments_settings() {
        register_setting('parfume_comments_settings', 'parfume_comments_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_require_moderation', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_allow_anonymous', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_require_email', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_enable_captcha', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_captcha_type', array(
            'type' => 'string',
            'default' => 'math',
            'sanitize_callback' => array($this, 'sanitize_captcha_type')
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_limit_per_ip', array(
            'type' => 'integer',
            'default' => 1,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_blocked_words', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_blocked_emails', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_blocked_ips', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_admin_notifications', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comments_settings', 'parfume_comments_auto_approve_after', array(
            'type' => 'integer',
            'default' => 0,
            'sanitize_callback' => 'absint'
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'parfumes_page_parfume-comments') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('parfume-admin-comments', 
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin-comments.js', 
            array('jquery', 'jquery-ui-tabs'), 
            PARFUME_CATALOG_VERSION, 
            true
        );
        
        wp_localize_script('parfume-admin-comments', 'parfumeCommentsAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_comments_admin'),
            'texts' => array(
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете този коментар?', 'parfume-catalog'),
                'confirm_bulk_delete' => __('Сигурни ли сте, че искате да изтриете избраните коментари?', 'parfume-catalog'),
                'no_comments_selected' => __('Моля, изберете коментари за обработка', 'parfume-catalog'),
                'action_success' => __('Действието е изпълнено успешно', 'parfume-catalog'),
                'action_error' => __('Грешка при изпълнение на действието', 'parfume-catalog')
            )
        ));
        
        wp_enqueue_style('parfume-admin-comments', 
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin-comments.css', 
            array(), 
            PARFUME_CATALOG_VERSION
        );
    }
    
    /**
     * Render comments management page
     */
    public function render_comments_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        
        $comments = $this->get_comments($current_tab, $current_page, $per_page);
        $total_comments = $this->get_comments_count($current_tab);
        $total_pages = ceil($total_comments / $per_page);
        
        $stats = $this->get_comments_stats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Управление на коментари', 'parfume-catalog'); ?></h1>
            
            <!-- Statistics Dashboard -->
            <div class="comments-stats-dashboard">
                <div class="stats-boxes">
                    <div class="stat-box pending">
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label"><?php _e('Чакащи', 'parfume-catalog'); ?></div>
                    </div>
                    <div class="stat-box approved">
                        <div class="stat-number"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label"><?php _e('Одобрени', 'parfume-catalog'); ?></div>
                    </div>
                    <div class="stat-box rejected">
                        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label"><?php _e('Отхвърлени', 'parfume-catalog'); ?></div>
                    </div>
                    <div class="stat-box spam">
                        <div class="stat-number"><?php echo $stats['spam']; ?></div>
                        <div class="stat-label"><?php _e('Спам', 'parfume-catalog'); ?></div>
                    </div>
                    <div class="stat-box total">
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label"><?php _e('Общо', 'parfume-catalog'); ?></div>
                    </div>
                </div>
            </div>
            
            <div id="comments-tabs">
                <ul>
                    <li><a href="#comments-list-tab"><?php _e('Коментари', 'parfume-catalog'); ?></a></li>
                    <li><a href="#settings-tab"><?php _e('Настройки', 'parfume-catalog'); ?></a></li>
                    <li><a href="#analytics-tab"><?php _e('Аналитика', 'parfume-catalog'); ?></a></li>
                </ul>
                
                <!-- Comments List Tab -->
                <div id="comments-list-tab">
                    <!-- Filter Navigation -->
                    <ul class="subsubsub">
                        <li><a href="<?php echo admin_url('admin.php?page=parfume-comments&tab=pending'); ?>" 
                               class="<?php echo ($current_tab === 'pending') ? 'current' : ''; ?>">
                            <?php _e('Чакащи', 'parfume-catalog'); ?> <span class="count">(<?php echo $stats['pending']; ?>)</span>
                        </a> |</li>
                        <li><a href="<?php echo admin_url('admin.php?page=parfume-comments&tab=approved'); ?>" 
                               class="<?php echo ($current_tab === 'approved') ? 'current' : ''; ?>">
                            <?php _e('Одобрени', 'parfume-catalog'); ?> <span class="count">(<?php echo $stats['approved']; ?>)</span>
                        </a> |</li>
                        <li><a href="<?php echo admin_url('admin.php?page=parfume-comments&tab=rejected'); ?>" 
                               class="<?php echo ($current_tab === 'rejected') ? 'current' : ''; ?>">
                            <?php _e('Отхвърлени', 'parfume-catalog'); ?> <span class="count">(<?php echo $stats['rejected']; ?>)</span>
                        </a> |</li>
                        <li><a href="<?php echo admin_url('admin.php?page=parfume-comments&tab=spam'); ?>" 
                               class="<?php echo ($current_tab === 'spam') ? 'current' : ''; ?>">
                            <?php _e('Спам', 'parfume-catalog'); ?> <span class="count">(<?php echo $stats['spam']; ?>)</span>
                        </a></li>
                    </ul>
                    
                    <div class="clear"></div>
                    
                    <!-- Bulk Actions -->
                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <select id="bulk-action-selector-top">
                                <option value="-1"><?php _e('Масови действия', 'parfume-catalog'); ?></option>
                                <?php if ($current_tab !== 'approved'): ?>
                                    <option value="approve"><?php _e('Одобри', 'parfume-catalog'); ?></option>
                                <?php endif; ?>
                                <?php if ($current_tab !== 'rejected'): ?>
                                    <option value="reject"><?php _e('Отхвърли', 'parfume-catalog'); ?></option>
                                <?php endif; ?>
                                <?php if ($current_tab !== 'spam'): ?>
                                    <option value="spam"><?php _e('Маркирай като спам', 'parfume-catalog'); ?></option>
                                <?php endif; ?>
                                <option value="delete"><?php _e('Изтрий', 'parfume-catalog'); ?></option>
                            </select>
                            <input type="button" id="doaction" class="button action" value="<?php _e('Приложи', 'parfume-catalog'); ?>">
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                            <div class="tablenav-pages">
                                <span class="displaying-num"><?php printf(__('%d елемента'), $total_comments); ?></span>
                                <?php
                                echo paginate_links(array(
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'current' => $current_page,
                                    'total' => $total_pages,
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;'
                                ));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Comments Table -->
                    <table class="wp-list-table widefat fixed striped comments">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="cb-select-all-1">
                                </td>
                                <th scope="col" class="manage-column column-author"><?php _e('Автор', 'parfume-catalog'); ?></th>
                                <th scope="col" class="manage-column column-comment"><?php _e('Коментар', 'parfume-catalog'); ?></th>
                                <th scope="col" class="manage-column column-rating"><?php _e('Рейтинг', 'parfume-catalog'); ?></th>
                                <th scope="col" class="manage-column column-perfume"><?php _e('Парфюм', 'parfume-catalog'); ?></th>
                                <th scope="col" class="manage-column column-date"><?php _e('Дата', 'parfume-catalog'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="the-comment-list">
                            <?php if (empty($comments)): ?>
                                <tr class="no-items">
                                    <td class="colspanchange" colspan="6">
                                        <?php _e('Няма коментари за показване.', 'parfume-catalog'); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <?php $this->render_comment_row($comment, $current_tab); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Bottom Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="tablenav bottom">
                            <div class="tablenav-pages">
                                <?php
                                echo paginate_links(array(
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'current' => $current_page,
                                    'total' => $total_pages,
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;'
                                ));
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings-tab">
                    <?php $this->render_settings_form(); ?>
                </div>
                
                <!-- Analytics Tab -->
                <div id="analytics-tab">
                    <?php $this->render_analytics(); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize tabs
            $('#comments-tabs').tabs();
            
            // Bulk actions
            $('#doaction').click(function() {
                var action = $('#bulk-action-selector-top').val();
                var selected = [];
                
                $('tbody input[type="checkbox"]:checked').each(function() {
                    selected.push($(this).val());
                });
                
                if (selected.length === 0) {
                    alert(parfumeCommentsAdmin.texts.no_comments_selected);
                    return;
                }
                
                if (action === 'delete' && !confirm(parfumeCommentsAdmin.texts.confirm_bulk_delete)) {
                    return;
                }
                
                if (action !== '-1') {
                    bulkModerate(action, selected);
                }
            });
            
            // Select all checkbox
            $('#cb-select-all-1').change(function() {
                $('tbody input[type="checkbox"]').prop('checked', this.checked);
            });
            
            // Individual comment actions
            $(document).on('click', '.moderate-comment', function() {
                var commentId = $(this).data('comment-id');
                var action = $(this).data('action');
                moderateComment(commentId, action);
            });
            
            $(document).on('click', '.delete-comment', function() {
                if (confirm(parfumeCommentsAdmin.texts.confirm_delete)) {
                    var commentId = $(this).data('comment-id');
                    deleteComment(commentId);
                }
            });
            
            // AJAX functions
            function moderateComment(commentId, action) {
                $.post(parfumeCommentsAdmin.ajax_url, {
                    action: 'parfume_moderate_comment',
                    comment_id: commentId,
                    moderate_action: action,
                    nonce: parfumeCommentsAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || parfumeCommentsAdmin.texts.action_error);
                    }
                });
            }
            
            function deleteComment(commentId) {
                $.post(parfumeCommentsAdmin.ajax_url, {
                    action: 'parfume_delete_comment',
                    comment_id: commentId,
                    nonce: parfumeCommentsAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        $('#comment-' + commentId).fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || parfumeCommentsAdmin.texts.action_error);
                    }
                });
            }
            
            function bulkModerate(action, commentIds) {
                $.post(parfumeCommentsAdmin.ajax_url, {
                    action: 'parfume_bulk_moderate',
                    moderate_action: action,
                    comment_ids: commentIds,
                    nonce: parfumeCommentsAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || parfumeCommentsAdmin.texts.action_error);
                    }
                });
            }
        });
        </script>
        
        <style>
        .comments-stats-dashboard {
            margin: 20px 0;
        }
        
        .stats-boxes {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            flex: 1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-box.pending { border-left: 4px solid #f39c12; }
        .stat-box.approved { border-left: 4px solid #27ae60; }
        .stat-box.rejected { border-left: 4px solid #e74c3c; }
        .stat-box.spam { border-left: 4px solid #9b59b6; }
        .stat-box.total { border-left: 4px solid #3498db; }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .comment-row {
            border-left: 4px solid #ddd;
        }
        
        .comment-row.status-pending { border-left-color: #f39c12; }
        .comment-row.status-approved { border-left-color: #27ae60; }
        .comment-row.status-rejected { border-left-color: #e74c3c; }
        .comment-row.status-spam { border-left-color: #9b59b6; }
        
        .comment-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .comment-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .comment-actions .button {
            padding: 2px 8px;
            font-size: 11px;
            height: auto;
            line-height: 1.4;
        }
        
        .rating-stars {
            color: #ffa500;
        }
        
        .comment-meta {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .tab-content {
            margin-top: 20px;
        }
        
        .form-table th {
            width: 200px;
        }
        
        .analytics-chart {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        </style>
        <?php
    }
    
    /**
     * Render single comment row
     */
    private function render_comment_row($comment, $current_tab) {
        $post = get_post($comment->post_id);
        $post_title = $post ? $post->post_title : __('Изтрит парфюм', 'parfume-catalog');
        ?>
        <tr id="comment-<?php echo $comment->id; ?>" class="comment-row status-<?php echo esc_attr($comment->status); ?>">
            <th scope="row" class="check-column">
                <input type="checkbox" value="<?php echo $comment->id; ?>">
            </th>
            <td class="column-author">
                <strong><?php echo esc_html($comment->author_name ?: __('Анонимен', 'parfume-catalog')); ?></strong>
                <?php if ($comment->author_email): ?>
                    <br><a href="mailto:<?php echo esc_attr($comment->author_email); ?>"><?php echo esc_html($comment->author_email); ?></a>
                <?php endif; ?>
                <div class="comment-meta">
                    IP: <?php echo esc_html($comment->author_ip); ?>
                </div>
            </td>
            <td class="column-comment">
                <div class="comment-content">
                    <?php echo esc_html(wp_trim_words($comment->content, 20)); ?>
                </div>
                <div class="comment-actions">
                    <?php if ($comment->status !== 'approved'): ?>
                        <button type="button" class="button button-small moderate-comment" 
                                data-comment-id="<?php echo $comment->id; ?>" 
                                data-action="approve">
                            <?php _e('Одобри', 'parfume-catalog'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($comment->status !== 'rejected'): ?>
                        <button type="button" class="button button-small moderate-comment" 
                                data-comment-id="<?php echo $comment->id; ?>" 
                                data-action="reject">
                            <?php _e('Отхвърли', 'parfume-catalog'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($comment->status !== 'spam'): ?>
                        <button type="button" class="button button-small moderate-comment" 
                                data-comment-id="<?php echo $comment->id; ?>" 
                                data-action="spam">
                            <?php _e('Спам', 'parfume-catalog'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="button button-small delete-comment" 
                            data-comment-id="<?php echo $comment->id; ?>">
                        <?php _e('Изтрий', 'parfume-catalog'); ?>
                    </button>
                </div>
            </td>
            <td class="column-rating">
                <div class="rating-stars">
                    <?php echo str_repeat('★', $comment->rating) . str_repeat('☆', 5 - $comment->rating); ?>
                </div>
                <small>(<?php echo $comment->rating; ?>/5)</small>
            </td>
            <td class="column-perfume">
                <?php if ($post): ?>
                    <a href="<?php echo get_edit_post_link($post->ID); ?>" target="_blank">
                        <?php echo esc_html($post_title); ?>
                    </a>
                <?php else: ?>
                    <?php echo esc_html($post_title); ?>
                <?php endif; ?>
            </td>
            <td class="column-date">
                <?php echo mysql2date(__('M j, Y @ H:i'), $comment->created_at); ?>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Render settings form
     */
    private function render_settings_form() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('parfume_comments_settings'); ?>
            
            <h3><?php _e('Основни настройки', 'parfume-catalog'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_enabled"><?php _e('Активирай коментари', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="parfume_comments_enabled" 
                                   name="parfume_comments_enabled" 
                                   value="1" 
                                   <?php checked(get_option('parfume_comments_enabled', true)); ?> />
                            <?php _e('Включи системата за коментари и ревюта', 'parfume-catalog'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_require_moderation"><?php _e('Изисквай модерация', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="parfume_comments_require_moderation" 
                                   name="parfume_comments_require_moderation" 
                                   value="1" 
                                   <?php checked(get_option('parfume_comments_require_moderation', true)); ?> />
                            <?php _e('Всички нови коментари трябва да бъдат одобрени', 'parfume-catalog'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_allow_anonymous"><?php _e('Анонимни коментари', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="parfume_comments_allow_anonymous" 
                                   name="parfume_comments_allow_anonymous" 
                                   value="1" 
                                   <?php checked(get_option('parfume_comments_allow_anonymous', true)); ?> />
                            <?php _e('Позволи коментари без въвеждане на име', 'parfume-catalog'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_require_email"><?php _e('Задължителен имейл', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="parfume_comments_require_email" 
                                   name="parfume_comments_require_email" 
                                   value="1" 
                                   <?php checked(get_option('parfume_comments_require_email', false)); ?> />
                            <?php _e('Изисквай имейл адрес за коментиране', 'parfume-catalog'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_admin_notifications"><?php _e('Известия до админ', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="parfume_comments_admin_notifications" 
                                   name="parfume_comments_admin_notifications" 
                                   value="1" 
                                   <?php checked(get_option('parfume_comments_admin_notifications', true)); ?> />
                            <?php _e('Изпращай имейл известия за нови коментари', 'parfume-catalog'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Спам защита', 'parfume-catalog'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_enable_captcha"><?php _e('CAPTCHA защита', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="parfume_comments_enable_captcha" 
                                   name="parfume_comments_enable_captcha" 
                                   value="1" 
                                   <?php checked(get_option('parfume_comments_enable_captcha', false)); ?> />
                            <?php _e('Включи CAPTCHA защита срещу спам', 'parfume-catalog'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_captcha_type"><?php _e('Тип CAPTCHA', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <select id="parfume_comments_captcha_type" name="parfume_comments_captcha_type">
                            <option value="math" <?php selected(get_option('parfume_comments_captcha_type', 'math'), 'math'); ?>><?php _e('Математически въпрос', 'parfume-catalog'); ?></option>
                            <option value="question" <?php selected(get_option('parfume_comments_captcha_type'), 'question'); ?>><?php _e('Прост въпрос', 'parfume-catalog'); ?></option>
                        </select>
                        <p class="description"><?php _e('Изберете типа CAPTCHA за защита от ботове', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_limit_per_ip"><?php _e('Лимит на IP', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="parfume_comments_limit_per_ip" 
                               name="parfume_comments_limit_per_ip" 
                               value="<?php echo esc_attr(get_option('parfume_comments_limit_per_ip', 1)); ?>" 
                               min="1" 
                               max="10" />
                        <p class="description"><?php _e('Максимален брой коментари на парфюм от един IP адрес', 'parfume-catalog'); ?></p>
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
                                  cols="50" 
                                  class="large-text"><?php echo esc_textarea(get_option('parfume_comments_blocked_words', '')); ?></textarea>
                        <p class="description"><?php _e('По една дума на ред. Коментари съдържащи тези думи ще бъдат автоматично отхвърлени.', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_blocked_emails"><?php _e('Блокирани имейли', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <textarea id="parfume_comments_blocked_emails" 
                                  name="parfume_comments_blocked_emails" 
                                  rows="5" 
                                  cols="50" 
                                  class="large-text"><?php echo esc_textarea(get_option('parfume_comments_blocked_emails', '')); ?></textarea>
                        <p class="description"><?php _e('По един имейл на ред. Коментари от тези имейли ще бъдат блокирани.', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_blocked_ips"><?php _e('Блокирани IP адреси', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <textarea id="parfume_comments_blocked_ips" 
                                  name="parfume_comments_blocked_ips" 
                                  rows="5" 
                                  cols="50" 
                                  class="large-text"><?php echo esc_textarea(get_option('parfume_comments_blocked_ips', '')); ?></textarea>
                        <p class="description"><?php _e('По един IP адрес на ред. Коментари от тези IP адреси ще бъдат блокирани.', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Автоматизация', 'parfume-catalog'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="parfume_comments_auto_approve_after"><?php _e('Автоматично одобрение', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="parfume_comments_auto_approve_after" 
                               name="parfume_comments_auto_approve_after" 
                               value="<?php echo esc_attr(get_option('parfume_comments_auto_approve_after', 0)); ?>" 
                               min="0" />
                        <p class="description"><?php _e('Автоматично одобри коментари след X одобрени коментара от същия автор (0 = изключено)', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render analytics section
     */
    private function render_analytics() {
        $analytics = $this->get_analytics_data();
        ?>
        <h3><?php _e('Статистики за коментари', 'parfume-catalog'); ?></h3>
        
        <div class="analytics-chart">
            <h4><?php _e('Коментари по дни (последните 30 дни)', 'parfume-catalog'); ?></h4>
            <canvas id="comments-chart" width="400" height="200"></canvas>
        </div>
        
        <div class="analytics-chart">
            <h4><?php _e('Разпределение на рейтингите', 'parfume-catalog'); ?></h4>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Рейтинг', 'parfume-catalog'); ?></th>
                        <th><?php _e('Брой коментари', 'parfume-catalog'); ?></th>
                        <th><?php _e('Процент', 'parfume-catalog'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <?php
                        $count = isset($analytics['rating_distribution'][$i]) ? $analytics['rating_distribution'][$i] : 0;
                        $percentage = $analytics['total_approved'] > 0 ? round(($count / $analytics['total_approved']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>
                                (<?php echo $i; ?>)
                            </td>
                            <td><?php echo $count; ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        
        <div class="analytics-chart">
            <h4><?php _e('Топ парфюми по брой коментари', 'parfume-catalog'); ?></h4>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Парфюм', 'parfume-catalog'); ?></th>
                        <th><?php _e('Брой коментари', 'parfume-catalog'); ?></th>
                        <th><?php _e('Среден рейтинг', 'parfume-catalog'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['top_perfumes'] as $perfume): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($perfume->post_id); ?>" target="_blank">
                                    <?php echo esc_html($perfume->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo $perfume->comment_count; ?></td>
                            <td><?php echo round($perfume->avg_rating, 1); ?>/5</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * AJAX: Moderate comment
     */
    public function ajax_moderate_comment() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_comments_admin')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $comment_id = intval($_POST['comment_id']);
        $action = sanitize_text_field($_POST['moderate_action']);
        
        $allowed_actions = array('approve', 'reject', 'spam');
        if (!in_array($action, $allowed_actions)) {
            wp_send_json_error(array('message' => __('Невалидно действие', 'parfume-catalog')));
        }
        
        global $wpdb;
        $status = ($action === 'approve') ? 'approved' : $action;
        
        $result = $wpdb->update(
            $this->table_name,
            array('status' => $status),
            array('id' => $comment_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Коментарът е обновен успешно', 'parfume-catalog')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Грешка при обновяване на коментара', 'parfume-catalog')
            ));
        }
    }
    
    /**
     * AJAX: Delete comment
     */
    public function ajax_delete_comment() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_comments_admin')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $comment_id = intval($_POST['comment_id']);
        
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $comment_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Коментарът е изтрит успешно', 'parfume-catalog')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Грешка при изтриване на коментара', 'parfume-catalog')
            ));
        }
    }
    
    /**
     * AJAX: Bulk moderate
     */
    public function ajax_bulk_moderate() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_comments_admin')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $action = sanitize_text_field($_POST['moderate_action']);
        $comment_ids = array_map('intval', $_POST['comment_ids']);
        
        if (empty($comment_ids)) {
            wp_send_json_error(array('message' => __('Няма избрани коментари', 'parfume-catalog')));
        }
        
        global $wpdb;
        
        if ($action === 'delete') {
            $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)",
                $comment_ids
            ));
        } else {
            $allowed_actions = array('approve', 'reject', 'spam');
            if (!in_array($action, $allowed_actions)) {
                wp_send_json_error(array('message' => __('Невалидно действие', 'parfume-catalog')));
            }
            
            $status = ($action === 'approve') ? 'approved' : $action;
            $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_name} SET status = %s WHERE id IN ($placeholders)",
                array_merge(array($status), $comment_ids)
            ));
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Действието е изпълнено успешно', 'parfume-catalog')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Грешка при изпълнение на действието', 'parfume-catalog')
            ));
        }
    }
    
    /**
     * AJAX: Get comment stats
     */
    public function ajax_get_comment_stats() {
        // Проверка на nonce
        if (!wp_verify_nonce($_GET['nonce'], 'parfume_comments_admin')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $stats = $this->get_comments_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * Show pending comments notice
     */
    public function show_pending_comments_notice() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'parfume') === false) {
            return;
        }
        
        $pending_count = $this->get_pending_count();
        if ($pending_count > 0) {
            echo '<div class="notice notice-warning"><p>';
            printf(
                _n(
                    'Имате %d коментар който чака одобрение.',
                    'Имате %d коментара които чакат одобрение.',
                    $pending_count,
                    'parfume-catalog'
                ),
                $pending_count
            );
            echo ' <a href="' . admin_url('admin.php?page=parfume-comments') . '">' . __('Прегледайте коментарите', 'parfume-catalog') . '</a>';
            echo '</p></div>';
        }
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'parfume_comments_dashboard',
            __('Коментари за парфюми', 'parfume-catalog'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $stats = $this->get_comments_stats();
        $recent_comments = $this->get_recent_comments(5);
        ?>
        <div class="activity-block">
            <h3><?php _e('Статистики', 'parfume-catalog'); ?></h3>
            <ul>
                <li><?php printf(__('Чакащи: %d', 'parfume-catalog'), $stats['pending']); ?></li>
                <li><?php printf(__('Одобрени: %d', 'parfume-catalog'), $stats['approved']); ?></li>
                <li><?php printf(__('Общо: %d', 'parfume-catalog'), $stats['total']); ?></li>
            </ul>
        </div>
        
        <?php if (!empty($recent_comments)): ?>
            <div class="activity-block">
                <h3><?php _e('Последни коментари', 'parfume-catalog'); ?></h3>
                <ul>
                    <?php foreach ($recent_comments as $comment): ?>
                        <li>
                            <strong><?php echo esc_html($comment->author_name ?: __('Анонимен', 'parfume-catalog')); ?></strong>
                            - <?php echo esc_html(wp_trim_words($comment->content, 8)); ?>
                            <small>(<?php echo human_time_diff(strtotime($comment->created_at)); ?> <?php _e('преди', 'parfume-catalog'); ?>)</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p><a href="<?php echo admin_url('admin.php?page=parfume-comments'); ?>"><?php _e('Вижте всички коментари', 'parfume-catalog'); ?></a></p>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Helper methods
     */
    
    private function get_pending_count() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
            'pending'
        ));
    }
    
    private function get_comments($status = 'pending', $page = 1, $per_page = 20) {
        global $wpdb;
        $offset = ($page - 1) * $per_page;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE status = %s 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $status,
            $per_page,
            $offset
        ));
    }
    
    private function get_comments_count($status = 'pending') {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
            $status
        ));
    }
    
    private function get_comments_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status",
            ARRAY_A
        );
        
        $result = array(
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'spam' => 0,
            'total' => 0
        );
        
        foreach ($stats as $stat) {
            $result[$stat['status']] = intval($stat['count']);
            $result['total'] += intval($stat['count']);
        }
        
        return $result;
    }
    
    private function get_recent_comments($limit = 5) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
    }
    
    private function get_analytics_data() {
        global $wpdb;
        
        // Rating distribution
        $rating_distribution = $wpdb->get_results(
            "SELECT rating, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE status = 'approved' 
             GROUP BY rating",
            ARRAY_A
        );
        
        $ratings = array();
        foreach ($rating_distribution as $rating) {
            $ratings[$rating['rating']] = intval($rating['count']);
        }
        
        // Top perfumes
        $top_perfumes = $wpdb->get_results(
            "SELECT c.post_id, p.post_title, COUNT(*) as comment_count, AVG(c.rating) as avg_rating
             FROM {$this->table_name} c
             LEFT JOIN {$wpdb->posts} p ON c.post_id = p.ID
             WHERE c.status = 'approved'
             GROUP BY c.post_id
             ORDER BY comment_count DESC
             LIMIT 10"
        );
        
        // Total approved
        $total_approved = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'approved'"
        );
        
        return array(
            'rating_distribution' => $ratings,
            'top_perfumes' => $top_perfumes,
            'total_approved' => intval($total_approved)
        );
    }
    
    /**
     * Sanitization functions
     */
    public function sanitize_captcha_type($value) {
        $allowed = array('math', 'question');
        return in_array($value, $allowed) ? $value : 'math';
    }
    
    /**
     * Static helper methods for external access
     */
    public static function get_comments_settings() {
        return array(
            'enabled' => get_option('parfume_comments_enabled', true),
            'require_moderation' => get_option('parfume_comments_require_moderation', true),
            'allow_anonymous' => get_option('parfume_comments_allow_anonymous', true),
            'require_email' => get_option('parfume_comments_require_email', false),
            'enable_captcha' => get_option('parfume_comments_enable_captcha', false),
            'captcha_type' => get_option('parfume_comments_captcha_type', 'math'),
            'limit_per_ip' => get_option('parfume_comments_limit_per_ip', 1),
            'blocked_words' => array_filter(array_map('trim', explode("\n", get_option('parfume_comments_blocked_words', '')))),
            'blocked_emails' => array_filter(array_map('trim', explode("\n", get_option('parfume_comments_blocked_emails', '')))),
            'blocked_ips' => array_filter(array_map('trim', explode("\n", get_option('parfume_comments_blocked_ips', '')))),
            'admin_notifications' => get_option('parfume_comments_admin_notifications', true),
            'auto_approve_after' => get_option('parfume_comments_auto_approve_after', 0)
        );
    }
    
    public static function is_comments_enabled() {
        return get_option('parfume_comments_enabled', true);
    }
}