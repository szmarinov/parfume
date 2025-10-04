<?php
/**
 * Comments Moderation
 * Admin page for moderating parfume comments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Comments_Moderation {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_post_parfume_moderate_comment', [$this, 'moderate_comment']);
        add_action('admin_post_parfume_bulk_moderate', [$this, 'bulk_moderate']);
    }
    
    /**
     * Add admin menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            'Модериране на коментари',
            'Коментари',
            'moderate_comments',
            'parfume-comments-moderation',
            [$this, 'render_page']
        );
    }
    
    /**
     * Render moderation page
     */
    public function render_page() {
        // Get current status filter
        $status = isset($_GET['comment_status']) ? sanitize_text_field($_GET['comment_status']) : 'pending';
        
        // Get search query
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Prepare query args
        $args = [
            'type' => 'parfume_review',
            'status' => $status === 'pending' ? 'hold' : $status,
            'number' => 20,
            'paged' => isset($_GET['paged']) ? intval($_GET['paged']) : 1
        ];
        
        if ($search) {
            $args['search'] = $search;
        }
        
        // Get comments
        $comments = get_comments($args);
        
        // Get counts
        $counts = $this->get_comment_counts();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Модериране на коментари</h1>
            
            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>Коментарът е актуализиран успешно.</p>
                </div>
            <?php endif; ?>
            
            <hr class="wp-header-end">
            
            <!-- Status filters -->
            <ul class="subsubsub">
                <li>
                    <a href="?post_type=parfume&page=parfume-comments-moderation&comment_status=pending" <?php echo $status === 'pending' ? 'class="current"' : ''; ?>>
                        Чакащи <span class="count">(<?php echo $counts['pending']; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=parfume&page=parfume-comments-moderation&comment_status=approve" <?php echo $status === 'approve' ? 'class="current"' : ''; ?>>
                        Одобрени <span class="count">(<?php echo $counts['approved']; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=parfume&page=parfume-comments-moderation&comment_status=spam" <?php echo $status === 'spam' ? 'class="current"' : ''; ?>>
                        Спам <span class="count">(<?php echo $counts['spam']; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=parfume&page=parfume-comments-moderation&comment_status=trash" <?php echo $status === 'trash' ? 'class="current"' : ''; ?>>
                        Кошче <span class="count">(<?php echo $counts['trash']; ?>)</span>
                    </a>
                </li>
            </ul>
            
            <!-- Search form -->
            <form method="get" class="search-form" style="float: right; margin-top: 10px;">
                <input type="hidden" name="post_type" value="parfume">
                <input type="hidden" name="page" value="parfume-comments-moderation">
                <input type="hidden" name="comment_status" value="<?php echo esc_attr($status); ?>">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Търси коментари...">
                <button type="submit" class="button">Търси</button>
            </form>
            
            <div style="clear: both;"></div>
            
            <!-- Bulk actions form -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="parfume_bulk_moderate">
                <?php wp_nonce_field('parfume_bulk_moderate'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value="">Масови действия</option>
                            <option value="approve">Одобри</option>
                            <option value="spam">Маркирай като спам</option>
                            <option value="trash">Премести в кошчето</option>
                            <?php if ($status === 'trash') : ?>
                                <option value="delete">Изтрий завинаги</option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" class="button action">Приложи</button>
                    </div>
                </div>
                
                <!-- Comments table -->
                <table class="wp-list-table widefat fixed striped comments">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all">
                            </td>
                            <th class="manage-column column-author">Автор</th>
                            <th class="manage-column column-comment">Коментар</th>
                            <th class="manage-column column-rating">Оценка</th>
                            <th class="manage-column column-parfume">Парфюм</th>
                            <th class="manage-column column-date">Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)) : ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <p>Няма намерени коментари.</p>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($comments as $comment) : ?>
                                <?php $this->render_comment_row($comment, $status); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
            
            <!-- Pagination -->
            <?php
            $total_comments = get_comments(array_merge($args, ['count' => true]));
            $total_pages = ceil($total_comments / 20);
            
            if ($total_pages > 1) :
            ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $args['paged'],
                            'total' => $total_pages
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .comment-rating-stars {
            color: #ffc107;
        }
        .comment-excerpt {
            max-width: 400px;
        }
        .row-actions {
            color: #999;
        }
        .row-actions span {
            display: inline-block;
        }
        </style>
        <?php
    }
    
    /**
     * Render single comment row
     */
    private function render_comment_row($comment, $current_status) {
        $rating = get_comment_meta($comment->comment_ID, 'parfume_rating', true);
        $post_title = get_the_title($comment->comment_post_ID);
        ?>
        <tr id="comment-<?php echo $comment->comment_ID; ?>">
            <th class="check-column">
                <input type="checkbox" name="comment_ids[]" value="<?php echo $comment->comment_ID; ?>">
            </th>
            <td class="column-author">
                <strong><?php echo esc_html($comment->comment_author); ?></strong>
                <br>
                <a href="mailto:<?php echo esc_attr($comment->comment_author_email); ?>">
                    <?php echo esc_html($comment->comment_author_email); ?>
                </a>
            </td>
            <td class="column-comment">
                <div class="comment-excerpt">
                    <?php echo wp_trim_words($comment->comment_content, 20); ?>
                </div>
                <div class="row-actions">
                    <?php if ($current_status === 'pending') : ?>
                        <span class="approve">
                            <a href="<?php echo $this->get_action_url($comment->comment_ID, 'approve'); ?>">Одобри</a> |
                        </span>
                    <?php endif; ?>
                    <span class="edit">
                        <a href="<?php echo admin_url('comment.php?action=editcomment&c=' . $comment->comment_ID); ?>">Редактирай</a> |
                    </span>
                    <span class="spam">
                        <a href="<?php echo $this->get_action_url($comment->comment_ID, 'spam'); ?>">Спам</a> |
                    </span>
                    <span class="trash">
                        <a href="<?php echo $this->get_action_url($comment->comment_ID, 'trash'); ?>">Кошче</a>
                    </span>
                </div>
            </td>
            <td class="column-rating">
                <?php if ($rating) : ?>
                    <span class="comment-rating-stars">
                        <?php echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating); ?>
                    </span>
                <?php else : ?>
                    —
                <?php endif; ?>
            </td>
            <td class="column-parfume">
                <a href="<?php echo get_edit_post_link($comment->comment_post_ID); ?>">
                    <?php echo esc_html($post_title); ?>
                </a>
            </td>
            <td class="column-date">
                <?php echo date_i18n('d.m.Y H:i', strtotime($comment->comment_date)); ?>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Get action URL
     */
    private function get_action_url($comment_id, $action) {
        return wp_nonce_url(
            admin_url('admin-post.php?action=parfume_moderate_comment&comment_id=' . $comment_id . '&moderate_action=' . $action),
            'parfume_moderate_comment'
        );
    }
    
    /**
     * Handle single comment moderation
     */
    public function moderate_comment() {
        check_admin_referer('parfume_moderate_comment');
        
        $comment_id = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : 0;
        $action = isset($_GET['moderate_action']) ? sanitize_text_field($_GET['moderate_action']) : '';
        
        if ($comment_id && $action) {
            switch ($action) {
                case 'approve':
                    wp_set_comment_status($comment_id, 'approve');
                    break;
                case 'spam':
                    wp_spam_comment($comment_id);
                    break;
                case 'trash':
                    wp_trash_comment($comment_id);
                    break;
            }
        }
        
        wp_redirect(add_query_arg('updated', 1, wp_get_referer()));
        exit;
    }
    
    /**
     * Handle bulk moderation
     */
    public function bulk_moderate() {
        check_admin_referer('parfume_bulk_moderate');
        
        $comment_ids = isset($_POST['comment_ids']) ? array_map('intval', $_POST['comment_ids']) : [];
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        
        if (!empty($comment_ids) && $action) {
            foreach ($comment_ids as $comment_id) {
                switch ($action) {
                    case 'approve':
                        wp_set_comment_status($comment_id, 'approve');
                        break;
                    case 'spam':
                        wp_spam_comment($comment_id);
                        break;
                    case 'trash':
                        wp_trash_comment($comment_id);
                        break;
                    case 'delete':
                        wp_delete_comment($comment_id, true);
                        break;
                }
            }
        }
        
        wp_redirect(add_query_arg('updated', 1, wp_get_referer()));
        exit;
    }
    
    /**
     * Get comment counts by status
     */
    private function get_comment_counts() {
        return [
            'pending' => get_comments(['type' => 'parfume_review', 'status' => 'hold', 'count' => true]),
            'approved' => get_comments(['type' => 'parfume_review', 'status' => 'approve', 'count' => true]),
            'spam' => get_comments(['type' => 'parfume_review', 'status' => 'spam', 'count' => true]),
            'trash' => get_comments(['type' => 'parfume_review', 'status' => 'trash', 'count' => true])
        ];
    }
}

// Initialize
new Parfume_Comments_Moderation();