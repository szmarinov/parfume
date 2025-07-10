<?php
/**
 * Parfume Catalog Comments Module
 * 
 * Система за коментари и ревюта без регистрация с модерация и спам защита
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Comments {

    /**
     * Comment статуси
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SPAM = 'spam';

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_comments_assets'));
        add_action('wp_ajax_parfume_submit_comment', array($this, 'ajax_submit_comment'));
        add_action('wp_ajax_nopriv_parfume_submit_comment', array($this, 'ajax_submit_comment'));
        add_action('wp_ajax_parfume_load_comments', array($this, 'ajax_load_comments'));
        add_action('wp_ajax_nopriv_parfume_load_comments', array($this, 'ajax_load_comments'));
        add_action('wp_ajax_parfume_moderate_comment', array($this, 'ajax_moderate_comment'));
        add_action('admin_menu', array($this, 'add_comments_admin_page'));
        add_action('admin_init', array($this, 'register_comments_settings'));
        add_action('admin_notices', array($this, 'pending_comments_notice'));
        add_filter('wp_mail', array($this, 'comment_notification_email'));
        add_action('wp_head', array($this, 'add_comments_schema'));
        add_action('init', array($this, 'create_comments_table'));
        add_action('wp_footer', array($this, 'add_comments_styles'));
    }

    /**
     * Създаване на comments таблица
     */
    public function create_comments_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'parfume_comments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100),
            author_ip varchar(45) NOT NULL,
            content longtext NOT NULL,
            rating tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at),
            KEY author_ip (author_ip)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Enqueue на comments assets
     */
    public function enqueue_comments_assets() {
        if (is_singular('parfumes')) {
            wp_enqueue_script(
                'parfume-comments',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/comments.js',
                array('jquery'),
                PARFUME_CATALOG_VERSION,
                true
            );

            wp_enqueue_style(
                'parfume-comments',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/comments.css',
                array(),
                PARFUME_CATALOG_VERSION
            );

            $settings = $this->get_comments_settings();

            wp_localize_script('parfume-comments', 'parfumeComments', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_comments_nonce'),
                'postId' => get_the_ID(),
                'settings' => array(
                    'requireName' => (bool) ($settings['require_name'] ?? true),
                    'requireEmail' => (bool) ($settings['require_email'] ?? false),
                    'enableCaptcha' => (bool) ($settings['enable_captcha'] ?? true),
                    'maxCommentLength' => intval($settings['max_comment_length'] ?? 1000),
                    'allowOneCommentPerIP' => (bool) ($settings['one_comment_per_ip'] ?? true)
                ),
                'strings' => array(
                    'submitting' => __('Изпращане...', 'parfume-catalog'),
                    'submitComment' => __('Изпрати мнение', 'parfume-catalog'),
                    'commentSubmitted' => __('Вашето мнение е изпратено за одобрение', 'parfume-catalog'),
                    'errorOccurred' => __('Възникна грешка. Моля, опитайте отново.', 'parfume-catalog'),
                    'nameRequired' => __('Моля, въведете име', 'parfume-catalog'),
                    'emailRequired' => __('Моля, въведете валиден имейл', 'parfume-catalog'),
                    'ratingRequired' => __('Моля, поставете оценка', 'parfume-catalog'),
                    'commentRequired' => __('Моля, въведете мнение', 'parfume-catalog'),
                    'commentTooLong' => __('Мнението е твърде дълго', 'parfume-catalog'),
                    'alreadyCommented' => __('Вече сте оставили мнение за този парфюм', 'parfume-catalog'),
                    'loadMore' => __('Зареди още мнения', 'parfume-catalog'),
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'noMoreComments' => __('Няма повече мнения', 'parfume-catalog'),
                    'anonymous' => __('Анонимен', 'parfume-catalog'),
                    'ratingLabel' => __('Вашата оценка:', 'parfume-catalog'),
                    'captchaError' => __('Моля, решете задачата за защита от спам', 'parfume-catalog')
                )
            ));
        }
    }

    /**
     * Рендериране на comments секция
     */
    public function render_comments_section($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (get_post_type($post_id) !== 'parfumes') {
            return '';
        }

        $settings = $this->get_comments_settings();
        if (!($settings['enabled'] ?? true)) {
            return '';
        }

        $comments = $this->get_comments($post_id, 1, 5);
        $average_rating = $this->get_average_rating($post_id);
        $total_comments = $this->get_comments_count($post_id);

        ob_start();
        ?>
        <div class="parfume-comments-section" id="parfume-comments">
            <h3 class="comments-title">
                <?php _e('Потребителски мнения и оценка', 'parfume-catalog'); ?>
                <?php if ($total_comments > 0): ?>
                    <span class="comments-count">(<?php echo $total_comments; ?>)</span>
                <?php endif; ?>
            </h3>

            <?php if ($average_rating > 0 && $total_comments > 0): ?>
                <div class="comments-summary">
                    <div class="average-rating">
                        <div class="rating-number"><?php echo number_format($average_rating, 1); ?></div>
                        <div class="rating-stars">
                            <?php echo $this->render_stars($average_rating); ?>
                        </div>
                        <div class="rating-text">
                            <?php printf(__('от %d мнения', 'parfume-catalog'), $total_comments); ?>
                        </div>
                    </div>
                    
                    <div class="rating-breakdown">
                        <?php echo $this->render_rating_breakdown($post_id); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Comments Form -->
            <div class="comments-form-container">
                <h4><?php _e('Споделете мнението си', 'parfume-catalog'); ?></h4>
                <form class="parfume-comment-form" id="parfume-comment-form">
                    <?php wp_nonce_field('parfume_comments_nonce', 'parfume_comment_nonce'); ?>
                    <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="comment-author-name">
                                <?php _e('Име', 'parfume-catalog'); ?>
                                <?php if ($settings['require_name'] ?? true): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="text" 
                                   id="comment-author-name" 
                                   name="author_name" 
                                   placeholder="<?php _e('Вашето име или анонимен', 'parfume-catalog'); ?>"
                                   <?php if ($settings['require_name'] ?? true): ?>required<?php endif; ?> />
                        </div>

                        <?php if ($settings['require_email'] ?? false): ?>
                            <div class="form-group">
                                <label for="comment-author-email">
                                    <?php _e('Имейл', 'parfume-catalog'); ?>
                                    <span class="required">*</span>
                                </label>
                                <input type="email" 
                                       id="comment-author-email" 
                                       name="author_email" 
                                       placeholder="<?php _e('your@email.com', 'parfume-catalog'); ?>"
                                       required />
                                <small class="form-help"><?php _e('Няма да бъде показван публично', 'parfume-catalog'); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group rating-group">
                        <label><?php _e('Вашата оценка', 'parfume-catalog'); ?> <span class="required">*</span></label>
                        <div class="rating-input" id="comment-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="rating-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="rating-<?php echo $i; ?>" title="<?php echo $i; ?> звезди">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comment-content">
                            <?php _e('Вашето мнение', 'parfume-catalog'); ?> <span class="required">*</span>
                        </label>
                        <textarea id="comment-content" 
                                  name="content" 
                                  rows="4" 
                                  maxlength="<?php echo intval($settings['max_comment_length'] ?? 1000); ?>"
                                  placeholder="<?php _e('Споделете вашия опит с този парфюм...', 'parfume-catalog'); ?>"
                                  required></textarea>
                        <div class="character-count">
                            <span id="char-count">0</span> / <?php echo intval($settings['max_comment_length'] ?? 1000); ?>
                        </div>
                    </div>

                    <?php if ($settings['enable_captcha'] ?? true): ?>
                        <div class="form-group captcha-group">
                            <label for="comment-captcha">
                                <?php _e('Защита от спам', 'parfume-catalog'); ?> <span class="required">*</span>
                            </label>
                            <?php $this->render_captcha(); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="submit-comment-btn">
                            <?php _e('Изпрати мнение', 'parfume-catalog'); ?>
                        </button>
                        <div class="form-notice">
                            <?php _e('Вашето мнение ще бъде публикувано след одобрение от модератор.', 'parfume-catalog'); ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Comments List -->
            <div class="comments-list-container">
                <?php if (empty($comments)): ?>
                    <div class="no-comments-message">
                        <p><?php _e('Все още няма оценки за този парфюм. Бъдете първият!', 'parfume-catalog'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="comments-list" id="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <?php echo $this->render_single_comment($comment); ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_comments > count($comments)): ?>
                        <div class="load-more-comments">
                            <button type="button" class="load-more-btn" id="load-more-comments" data-page="2">
                                <?php _e('Зареди още мнения', 'parfume-catalog'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендериране на единичен коментар
     */
    private function render_single_comment($comment) {
        ob_start();
        ?>
        <div class="parfume-comment" data-comment-id="<?php echo esc_attr($comment['id']); ?>">
            <div class="comment-header">
                <div class="comment-author">
                    <strong><?php echo esc_html($comment['author_name'] ?: __('Анонимен', 'parfume-catalog')); ?></strong>
                </div>
                <div class="comment-rating">
                    <?php echo $this->render_stars($comment['rating']); ?>
                </div>
                <div class="comment-date">
                    <?php echo human_time_diff(strtotime($comment['created_at']), current_time('timestamp')) . ' ' . __('назад', 'parfume-catalog'); ?>
                </div>
            </div>
            <div class="comment-content">
                <?php echo wp_kses_post(nl2br($comment['content'])); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендериране на звезди
     */
    private function render_stars($rating, $max_stars = 5) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = $max_stars - $full_stars - ($half_star ? 1 : 0);

        $output = '<span class="rating-stars">';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $output .= '<span class="star-full">★</span>';
        }
        
        // Half star
        if ($half_star) {
            $output .= '<span class="star-half">★</span>';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $output .= '<span class="star-empty">☆</span>';
        }
        
        $output .= '</span>';
        
        return $output;
    }

    /**
     * Рендериране на rating breakdown
     */
    private function render_rating_breakdown($post_id) {
        $breakdown = $this->get_rating_breakdown($post_id);
        $total = array_sum(array_column($breakdown, 'count'));

        if ($total === 0) {
            return '';
        }

        ob_start();
        ?>
        <div class="rating-breakdown">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <?php 
                $count = $breakdown[$i]['count'] ?? 0;
                $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                ?>
                <div class="rating-bar">
                    <span class="rating-label"><?php echo $i; ?> ★</span>
                    <div class="rating-progress">
                        <div class="rating-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <span class="rating-count"><?php echo $count; ?></span>
                </div>
            <?php endfor; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендериране на CAPTCHA
     */
    private function render_captcha() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operation = rand(0, 1) ? '+' : '-';
        
        if ($operation === '-' && $num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        
        $answer = $operation === '+' ? $num1 + $num2 : $num1 - $num2;
        
        echo '<div class="captcha-container">';
        echo '<span class="captcha-question">' . $num1 . ' ' . $operation . ' ' . $num2 . ' = ?</span>';
        echo '<input type="number" name="captcha_answer" id="comment-captcha" required>';
        echo '<input type="hidden" name="captcha_expected" value="' . esc_attr($answer) . '">';
        echo '</div>';
    }

    /**
     * AJAX - Изпращане на коментар
     */
    public function ajax_submit_comment() {
        check_ajax_referer('parfume_comments_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $author_name = sanitize_text_field($_POST['author_name']);
        $author_email = sanitize_email($_POST['author_email'] ?? '');
        $content = sanitize_textarea_field($_POST['content']);
        $rating = intval($_POST['rating']);
        $captcha_answer = intval($_POST['captcha_answer'] ?? 0);
        $captcha_expected = intval($_POST['captcha_expected'] ?? 0);

        // Validation
        $settings = $this->get_comments_settings();
        
        if (!$post_id || get_post_type($post_id) !== 'parfumes') {
            wp_send_json_error(__('Невалиден парфюм.', 'parfume-catalog'));
        }

        if (($settings['require_name'] ?? true) && empty($author_name)) {
            wp_send_json_error(__('Името е задължително.', 'parfume-catalog'));
        }

        if (($settings['require_email'] ?? false) && empty($author_email)) {
            wp_send_json_error(__('Имейлът е задължителен.', 'parfume-catalog'));
        }

        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(__('Моля, поставете валидна оценка.', 'parfume-catalog'));
        }

        if (empty($content)) {
            wp_send_json_error(__('Мнението е задължително.', 'parfume-catalog'));
        }

        $max_length = intval($settings['max_comment_length'] ?? 1000);
        if (strlen($content) > $max_length) {
            wp_send_json_error(__('Мнението е твърде дълго.', 'parfume-catalog'));
        }

        // CAPTCHA validation
        if (($settings['enable_captcha'] ?? true) && $captcha_answer !== $captcha_expected) {
            wp_send_json_error(__('Неправилен отговор на задачата.', 'parfume-catalog'));
        }

        // IP restrictions
        $author_ip = $this->get_client_ip();
        if (($settings['one_comment_per_ip'] ?? true) && $this->has_user_commented($post_id, $author_ip)) {
            wp_send_json_error(__('Вече сте оставили мнение за този парфюм.', 'parfume-catalog'));
        }

        // Spam detection
        if ($this->is_spam_comment($content, $author_name, $author_email, $author_ip)) {
            $status = self::STATUS_SPAM;
        } else {
            $status = ($settings['auto_approve'] ?? false) ? self::STATUS_APPROVED : self::STATUS_PENDING;
        }

        // Set default name
        if (empty($author_name)) {
            $author_name = __('Анонимен', 'parfume-catalog');
        }

        // Insert comment
        $comment_id = $this->insert_comment(array(
            'post_id' => $post_id,
            'author_name' => $author_name,
            'author_email' => $author_email,
            'author_ip' => $author_ip,
            'content' => $content,
            'rating' => $rating,
            'status' => $status
        ));

        if (!$comment_id) {
            wp_send_json_error(__('Грешка при запазване на мнението.', 'parfume-catalog'));
        }

        // Send notification email
        if ($status === self::STATUS_PENDING) {
            $this->send_moderation_notification($comment_id);
        }

        if ($status === self::STATUS_APPROVED) {
            wp_send_json_success(array(
                'message' => __('Благодарим за мнението! То е публикувано.', 'parfume-catalog'),
                'comment_html' => $this->render_single_comment($this->get_comment($comment_id))
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('Благодарим за мнението! То ще бъде публикувано след одобрение.', 'parfume-catalog')
            ));
        }
    }

    /**
     * AJAX - Зареждане на коментари
     */
    public function ajax_load_comments() {
        check_ajax_referer('parfume_comments_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 5);

        if (!$post_id || get_post_type($post_id) !== 'parfumes') {
            wp_send_json_error(__('Невалиден парфюм.', 'parfume-catalog'));
        }

        $comments = $this->get_comments($post_id, $page, $per_page);
        $total_comments = $this->get_comments_count($post_id);
        $loaded_count = ($page - 1) * $per_page + count($comments);

        $comments_html = '';
        foreach ($comments as $comment) {
            $comments_html .= $this->render_single_comment($comment);
        }

        wp_send_json_success(array(
            'comments_html' => $comments_html,
            'has_more' => $loaded_count < $total_comments,
            'next_page' => $page + 1,
            'total_comments' => $total_comments,
            'loaded_count' => $loaded_count
        ));
    }

    /**
     * AJAX - Модериране на коментар
     */
    public function ajax_moderate_comment() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');

        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-catalog'));
        }

        $comment_id = intval($_POST['comment_id']);
        $action = sanitize_text_field($_POST['action']);

        $valid_actions = array('approve', 'reject', 'spam', 'delete');
        if (!in_array($action, $valid_actions)) {
            wp_send_json_error(__('Невалидно действие.', 'parfume-catalog'));
        }

        if ($action === 'delete') {
            $result = $this->delete_comment($comment_id);
        } else {
            $status_map = array(
                'approve' => self::STATUS_APPROVED,
                'reject' => self::STATUS_REJECTED,
                'spam' => self::STATUS_SPAM
            );
            $result = $this->update_comment_status($comment_id, $status_map[$action]);
        }

        if ($result) {
            wp_send_json_success(__('Коментарът е обновен успешно.', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при обновяване на коментара.', 'parfume-catalog'));
        }
    }

    /**
     * Добавяне на comments admin страница
     */
    public function add_comments_admin_page() {
        $pending_count = $this->get_comments_count_by_status(self::STATUS_PENDING);
        $menu_title = __('Коментари', 'parfume-catalog');
        
        if ($pending_count > 0) {
            $menu_title .= ' <span class="awaiting-mod count-' . $pending_count . '"><span class="pending-count">' . $pending_count . '</span></span>';
        }

        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Мнения и коментари', 'parfume-catalog'),
            $menu_title,
            'moderate_comments',
            'parfume-comments',
            array($this, 'render_comments_admin_page')
        );
    }

    /**
     * Рендериране на admin страница за коментари
     */
    public function render_comments_admin_page() {
        $current_status = sanitize_text_field($_GET['status'] ?? 'pending');
        $comments = $this->get_admin_comments($current_status);
        $status_counts = $this->get_status_counts();

        ?>
        <div class="wrap">
            <h1><?php _e('Мнения и коментари', 'parfume-catalog'); ?></h1>

            <ul class="subsubsub">
                <li><a href="?page=parfume-comments&status=pending" <?php echo $current_status === 'pending' ? 'class="current"' : ''; ?>>
                    <?php _e('Чакащи', 'parfume-catalog'); ?> 
                    <span class="count">(<?php echo $status_counts['pending']; ?>)</span>
                </a> |</li>
                <li><a href="?page=parfume-comments&status=approved" <?php echo $current_status === 'approved' ? 'class="current"' : ''; ?>>
                    <?php _e('Одобрени', 'parfume-catalog'); ?> 
                    <span class="count">(<?php echo $status_counts['approved']; ?>)</span>
                </a> |</li>
                <li><a href="?page=parfume-comments&status=rejected" <?php echo $current_status === 'rejected' ? 'class="current"' : ''; ?>>
                    <?php _e('Отхвърлени', 'parfume-catalog'); ?> 
                    <span class="count">(<?php echo $status_counts['rejected']; ?>)</span>
                </a> |</li>
                <li><a href="?page=parfume-comments&status=spam" <?php echo $current_status === 'spam' ? 'class="current"' : ''; ?>>
                    <?php _e('Спам', 'parfume-catalog'); ?> 
                    <span class="count">(<?php echo $status_counts['spam']; ?>)</span>
                </a></li>
            </ul>

            <?php if (empty($comments)): ?>
                <div class="no-comments">
                    <p><?php _e('Няма коментари с този статус.', 'parfume-catalog'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped comments">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all" />
                            </td>
                            <th class="manage-column column-author"><?php _e('Автор', 'parfume-catalog'); ?></th>
                            <th class="manage-column column-comment"><?php _e('Мнение', 'parfume-catalog'); ?></th>
                            <th class="manage-column column-rating"><?php _e('Оценка', 'parfume-catalog'); ?></th>
                            <th class="manage-column column-parfume"><?php _e('Парфюм', 'parfume-catalog'); ?></th>
                            <th class="manage-column column-date"><?php _e('Дата', 'parfume-catalog'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr id="comment-<?php echo $comment['id']; ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="comment[]" value="<?php echo $comment['id']; ?>" />
                                </th>
                                <td class="comment-author">
                                    <strong><?php echo esc_html($comment['author_name']); ?></strong>
                                    <?php if (!empty($comment['author_email'])): ?>
                                        <br><a href="mailto:<?php echo esc_attr($comment['author_email']); ?>"><?php echo esc_html($comment['author_email']); ?></a>
                                    <?php endif; ?>
                                    <br><span class="author-ip"><?php echo esc_html($comment['author_ip']); ?></span>
                                </td>
                                <td class="comment-content">
                                    <div class="comment-text">
                                        <?php echo wp_kses_post(wp_trim_words($comment['content'], 20)); ?>
                                    </div>
                                    <?php if ($current_status === 'pending'): ?>
                                        <div class="row-actions">
                                            <span class="approve">
                                                <a href="#" class="moderate-comment" data-comment-id="<?php echo $comment['id']; ?>" data-action="approve"><?php _e('Одобри', 'parfume-catalog'); ?></a> |
                                            </span>
                                            <span class="reject">
                                                <a href="#" class="moderate-comment" data-comment-id="<?php echo $comment['id']; ?>" data-action="reject"><?php _e('Отхвърли', 'parfume-catalog'); ?></a> |
                                            </span>
                                            <span class="spam">
                                                <a href="#" class="moderate-comment" data-comment-id="<?php echo $comment['id']; ?>" data-action="spam"><?php _e('Спам', 'parfume-catalog'); ?></a> |
                                            </span>
                                            <span class="delete">
                                                <a href="#" class="moderate-comment" data-comment-id="<?php echo $comment['id']; ?>" data-action="delete"><?php _e('Изтрий', 'parfume-catalog'); ?></a>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $this->render_stars($comment['rating']); ?></td>
                                <td>
                                    <?php 
                                    $post = get_post($comment['post_id']);
                                    if ($post) {
                                        printf('<a href="%s">%s</a>', 
                                            get_edit_post_link($comment['post_id']), 
                                            esc_html($post->post_title)
                                        );
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Регистриране на comments настройки
     */
    public function register_comments_settings() {
        register_setting('parfume_catalog_comments_settings', 'parfume_catalog_comments_settings', array(
            'sanitize_callback' => array($this, 'sanitize_comments_settings')
        ));
    }

    /**
     * Database операции
     */

    /**
     * Вмъкване на коментар
     */
    private function insert_comment($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $data['post_id'],
                'author_name' => $data['author_name'],
                'author_email' => $data['author_email'],
                'author_ip' => $data['author_ip'],
                'content' => $data['content'],
                'rating' => $data['rating'],
                'status' => $data['status']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Получаване на коментари
     */
    private function get_comments($post_id, $page = 1, $per_page = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        $offset = ($page - 1) * $per_page;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE post_id = %d AND status = %s 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $post_id, self::STATUS_APPROVED, $per_page, $offset
        ), ARRAY_A);
    }

    /**
     * Получаване на единичен коментар
     */
    private function get_comment($comment_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $comment_id
        ), ARRAY_A);
    }

    /**
     * Получаване на брой коментари
     */
    private function get_comments_count($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND status = %s",
            $post_id, self::STATUS_APPROVED
        )));
    }

    /**
     * Получаване на средна оценка
     */
    private function get_average_rating($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $average = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $table_name WHERE post_id = %d AND status = %s",
            $post_id, self::STATUS_APPROVED
        ));
        
        return $average ? floatval($average) : 0;
    }

    /**
     * Получаване на rating breakdown
     */
    private function get_rating_breakdown($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $breakdown = array();
        for ($i = 1; $i <= 5; $i++) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND status = %s AND rating = %d",
                $post_id, self::STATUS_APPROVED, $i
            ));
            $breakdown[$i] = array('count' => intval($count));
        }
        
        return $breakdown;
    }

    /**
     * Получаване на admin коментари
     */
    private function get_admin_comments($status = 'pending', $limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE status = %s 
             ORDER BY created_at DESC 
             LIMIT %d",
            $status, $limit
        ), ARRAY_A);
    }

    /**
     * Получаване на count по статуси
     */
    private function get_status_counts() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $counts = array(
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'spam' => 0
        );
        
        $results = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table_name 
            GROUP BY status
        ");
        
        foreach ($results as $result) {
            if (isset($counts[$result->status])) {
                $counts[$result->status] = intval($result->count);
            }
        }
        
        return $counts;
    }

    /**
     * Получаване на брой коментари по статус
     */
    private function get_comments_count_by_status($status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE status = %s",
            $status
        )));
    }

    /**
     * Обновяване на статус на коментар
     */
    private function update_comment_status($comment_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        return $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $comment_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Изтриване на коментар
     */
    private function delete_comment($comment_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $comment_id),
            array('%d')
        );
    }

    /**
     * Helper функции
     */

    /**
     * Получаване на IP адрес на клиента
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Проверка дали потребителят вече е коментирал
     */
    private function has_user_commented($post_id, $ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND author_ip = %s",
            $post_id, $ip
        ));
        
        return intval($count) > 0;
    }

    /**
     * Spam detection
     */
    private function is_spam_comment($content, $author_name, $author_email, $author_ip) {
        $settings = $this->get_comments_settings();
        $blocked_words = $settings['blocked_words'] ?? array();
        $blocked_emails = $settings['blocked_emails'] ?? array();
        $blocked_ips = $settings['blocked_ips'] ?? array();

        // Check blocked words
        foreach ($blocked_words as $word) {
            if (stripos($content . ' ' . $author_name, trim($word)) !== false) {
                return true;
            }
        }

        // Check blocked emails
        if (!empty($author_email)) {
            foreach ($blocked_emails as $email) {
                if (stripos($author_email, trim($email)) !== false) {
                    return true;
                }
            }
        }

        // Check blocked IPs
        foreach ($blocked_ips as $ip) {
            if (strpos($author_ip, trim($ip)) === 0) {
                return true;
            }
        }

        // Simple spam patterns
        $spam_patterns = array(
            '/\b(buy|sale|cheap|discount|viagra|casino|poker|loan)\b/i',
            '/http[s]?:\/\/[^\s]+\.[^\s]{2,}/i', // URLs in content
            '/(.)\1{10,}/', // Repeated characters
        );

        foreach ($spam_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Изпращане на notification email
     */
    private function send_moderation_notification($comment_id) {
        $settings = $this->get_comments_settings();
        
        if (!($settings['email_notifications'] ?? true)) {
            return;
        }

        $comment = $this->get_comment($comment_id);
        if (!$comment) {
            return;
        }

        $post = get_post($comment['post_id']);
        if (!$post) {
            return;
        }

        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] Ново мнение чака одобрение', 'parfume-catalog'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Ново мнение чака одобрение на сайта %s:

Парфюм: %s
Автор: %s
Оценка: %d/5
Мнение: %s

За да модерирате мнението, отидете на:
%s', 'parfume-catalog'),
            get_bloginfo('name'),
            $post->post_title,
            $comment['author_name'],
            $comment['rating'],
            $comment['content'],
            admin_url('admin.php?page=parfume-comments')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Comments filter за email notifications
     */
    public function comment_notification_email($args) {
        // Customize email if needed
        return $args;
    }

    /**
     * Admin notice за pending коментари
     */
    public function pending_comments_notice() {
        $screen = get_current_screen();
        if ($screen && $screen->id !== 'parfumes_page_parfume-comments') {
            $pending_count = $this->get_comments_count_by_status(self::STATUS_PENDING);
            if ($pending_count > 0) {
                printf(
                    '<div class="notice notice-info"><p><strong>Parfume Catalog:</strong> <a href="%s">%s</a></p></div>',
                    admin_url('admin.php?page=parfume-comments'),
                    sprintf(__('Имате %d нови мнения които чакат одобрение', 'parfume-catalog'), $pending_count)
                );
            }
        }
    }

    /**
     * Schema.org markup за коментари
     */
    public function add_comments_schema() {
        if (is_singular('parfumes')) {
            $post_id = get_the_ID();
            $average_rating = $this->get_average_rating($post_id);
            $comments_count = $this->get_comments_count($post_id);
            
            if ($average_rating > 0 && $comments_count > 0) {
                ?>
                <script type="application/ld+json">
                {
                    "@context": "https://schema.org",
                    "@type": "Product",
                    "name": "<?php echo esc_js(get_the_title()); ?>",
                    "aggregateRating": {
                        "@type": "AggregateRating",
                        "ratingValue": "<?php echo esc_js(number_format($average_rating, 1)); ?>",
                        "reviewCount": "<?php echo esc_js($comments_count); ?>",
                        "bestRating": "5",
                        "worstRating": "1"
                    }
                }
                </script>
                <?php
            }
        }
    }

    /**
     * Добавяне на inline CSS
     */
    public function add_comments_styles() {
        if (!is_singular('parfumes')) {
            return;
        }

        echo '<style type="text/css">' . $this->get_comments_css() . '</style>';
    }

    /**
     * CSS за коментари
     */
    private function get_comments_css() {
        return '
        .parfume-comments-section {
            margin-top: 40px;
            padding: 30px 0;
            border-top: 1px solid #eee;
        }
        
        .comments-summary {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .average-rating {
            text-align: center;
        }
        
        .rating-number {
            font-size: 48px;
            font-weight: bold;
            color: #333;
            line-height: 1;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 24px;
            margin: 5px 0;
        }
        
        .rating-text {
            color: #666;
            font-size: 14px;
        }
        
        .rating-breakdown {
            flex: 1;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .rating-label {
            width: 40px;
            font-size: 14px;
        }
        
        .rating-progress {
            flex: 1;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .rating-fill {
            height: 100%;
            background: #ffc107;
            transition: width 0.3s ease;
        }
        
        .rating-count {
            width: 30px;
            text-align: right;
            font-size: 12px;
            color: #666;
        }
        
        .comments-form-container {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .parfume-comment-form .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .parfume-comment-form .form-group {
            flex: 1;
        }
        
        .parfume-comment-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .parfume-comment-form .required {
            color: #e74c3c;
        }
        
        .parfume-comment-form input,
        .parfume-comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .parfume-comment-form input:focus,
        .parfume-comment-form textarea:focus {
            outline: none;
            border-color: #007cba;
        }
        
        .rating-input {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .rating-input input[type="radio"] {
            display: none;
        }
        
        .rating-input label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
            width: auto;
            margin: 0;
        }
        
        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }
        
        .character-count {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .captcha-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .captcha-question {
            font-weight: 600;
            font-size: 16px;
        }
        
        .captcha-container input {
            width: 80px;
        }
        
        .form-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .submit-comment-btn {
            background: #007cba;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .submit-comment-btn:hover {
            background: #005a87;
        }
        
        .submit-comment-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .form-notice {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        .comments-list {
            margin-top: 30px;
        }
        
        .parfume-comment {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        
        .parfume-comment:last-child {
            border-bottom: none;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .comment-author strong {
            color: #333;
        }
        
        .comment-rating .rating-stars {
            color: #ffc107;
            font-size: 16px;
        }
        
        .star-empty {
            color: #ddd;
        }
        
        .comment-date {
            color: #666;
            font-size: 14px;
        }
        
        .comment-content {
            line-height: 1.6;
            color: #333;
        }
        
        .load-more-comments {
            text-align: center;
            margin-top: 20px;
        }
        
        .load-more-btn {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .load-more-btn:hover {
            background: #e9ecef;
        }
        
        .no-comments-message {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .comments-summary {
                flex-direction: column;
                gap: 20px;
            }
            
            .parfume-comment-form .form-row {
                flex-direction: column;
            }
            
            .comment-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        ';
    }

    /**
     * Получаване на настройки
     */
    private function get_comments_settings() {
        $defaults = array(
            'enabled' => true,
            'require_name' => true,
            'require_email' => false,
            'enable_captcha' => true,
            'max_comment_length' => 1000,
            'one_comment_per_ip' => true,
            'auto_approve' => false,
            'email_notifications' => true,
            'blocked_words' => array(),
            'blocked_emails' => array(),
            'blocked_ips' => array()
        );

        $settings = get_option('parfume_catalog_comments_settings', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Sanitization на настройки
     */
    public function sanitize_comments_settings($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = (bool) ($input['enabled'] ?? true);
        $sanitized['require_name'] = (bool) ($input['require_name'] ?? true);
        $sanitized['require_email'] = (bool) ($input['require_email'] ?? false);
        $sanitized['enable_captcha'] = (bool) ($input['enable_captcha'] ?? true);
        $sanitized['max_comment_length'] = intval($input['max_comment_length'] ?? 1000);
        $sanitized['one_comment_per_ip'] = (bool) ($input['one_comment_per_ip'] ?? true);
        $sanitized['auto_approve'] = (bool) ($input['auto_approve'] ?? false);
        $sanitized['email_notifications'] = (bool) ($input['email_notifications'] ?? true);
        
        // Sanitize blocked lists
        $sanitized['blocked_words'] = array_filter(array_map('trim', explode("\n", $input['blocked_words'] ?? '')));
        $sanitized['blocked_emails'] = array_filter(array_map('trim', explode("\n", $input['blocked_emails'] ?? '')));
        $sanitized['blocked_ips'] = array_filter(array_map('trim', explode("\n", $input['blocked_ips'] ?? '')));
        
        return $sanitized;
    }

    /**
     * Получаване на настройки (public method)
     */
    public function get_settings() {
        return $this->get_comments_settings();
    }

    /**
     * Static helper методи
     */
    public static function render_comments($post_id = null) {
        $instance = new self();
        return $instance->render_comments_section($post_id);
    }

    public static function get_rating($post_id) {
        $instance = new self();
        return $instance->get_average_rating($post_id);
    }

    public static function get_count($post_id) {
        $instance = new self();
        return $instance->get_comments_count($post_id);
    }
}

// Initialize the comments module
new Parfume_Catalog_Comments();