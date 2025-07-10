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

            wp_localize_script('parfume-comments', 'parfume_comments_config', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_comments_nonce'),
                'post_id' => get_the_ID(),
                'settings' => $this->get_comments_settings(),
                'texts' => array(
                    'submit_comment' => __('Изпрати мнение', 'parfume-catalog'),
                    'submitting' => __('Изпращане...', 'parfume-catalog'),
                    'comment_submitted' => __('Вашето мнение е изпратено за одобрение', 'parfume-catalog'),
                    'comment_error' => __('Възникна грешка при изпращането', 'parfume-catalog'),
                    'rating_required' => __('Моля, поставете оценка', 'parfume-catalog'),
                    'comment_required' => __('Моля, напишете мнение', 'parfume-catalog'),
                    'name_too_long' => __('Името е твърде дълго', 'parfume-catalog'),
                    'comment_too_long' => __('Мнението е твърде дълго', 'parfume-catalog'),
                    'already_commented' => __('Вече сте оставили мнение за този парфюм', 'parfume-catalog'),
                    'load_more' => __('Зареди още мнения', 'parfume-catalog'),
                    'no_more_comments' => __('Няма повече мнения', 'parfume-catalog'),
                    'anonymous' => __('Анонимен', 'parfume-catalog'),
                    'verified_purchase' => __('Потвърдена покупка', 'parfume-catalog')
                )
            ));

            // Добавяне на inline CSS
            wp_add_inline_style('parfume-catalog-frontend', $this->get_comments_css());
        }
    }

    /**
     * AJAX - Изпращане на коментар
     */
    public function ajax_submit_comment() {
        check_ajax_referer('parfume_comments_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $rating = intval($_POST['rating']);
        $comment_text = sanitize_textarea_field($_POST['comment_text']);
        $author_name = sanitize_text_field($_POST['author_name']);
        $author_ip = $this->get_user_ip();

        // Валидации
        $validation_result = $this->validate_comment_data($post_id, $rating, $comment_text, $author_name, $author_ip);
        if (is_wp_error($validation_result)) {
            wp_send_json_error($validation_result->get_error_message());
        }

        // Проверка за спам
        $spam_check = $this->check_for_spam($comment_text, $author_name, $author_ip);
        if ($spam_check['is_spam']) {
            wp_send_json_error($spam_check['message']);
        }

        // CAPTCHA проверка ако е включена
        if ($this->is_captcha_enabled()) {
            $captcha_valid = $this->verify_captcha($_POST);
            if (!$captcha_valid) {
                wp_send_json_error(__('CAPTCHA проверката не е успешна', 'parfume-catalog'));
            }
        }

        // Проверка за дублиращи се коментари
        if ($this->has_user_commented($post_id, $author_ip, $author_name)) {
            wp_send_json_error(__('Вече сте оставили мнение за този парфюм', 'parfume-catalog'));
        }

        // Запазване на коментара
        $comment_id = $this->save_comment($post_id, $rating, $comment_text, $author_name, $author_ip);
        
        if ($comment_id) {
            // Изпращане на имейл до администратор
            $this->send_admin_notification($comment_id);
            
            wp_send_json_success(array(
                'message' => __('Вашето мнение е изпратено за одобрение', 'parfume-catalog'),
                'comment_id' => $comment_id
            ));
        } else {
            wp_send_json_error(__('Възникна грешка при запазването', 'parfume-catalog'));
        }
    }

    /**
     * AJAX - Зареждане на коментари
     */
    public function ajax_load_comments() {
        check_ajax_referer('parfume_comments_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $page = intval($_POST['page']);
        $per_page = 10;

        $comments = $this->get_approved_comments($post_id, $page, $per_page);
        $total_comments = $this->get_comments_count($post_id);
        $average_rating = $this->get_average_rating($post_id);

        $comments_html = '';
        foreach ($comments as $comment) {
            $comments_html .= $this->render_comment_html($comment);
        }

        wp_send_json_success(array(
            'comments_html' => $comments_html,
            'has_more' => (($page * $per_page) < $total_comments),
            'total_comments' => $total_comments,
            'average_rating' => $average_rating,
            'current_page' => $page
        ));
    }

    /**
     * AJAX - Модериране на коментар (admin only)
     */
    public function ajax_moderate_comment() {
        check_ajax_referer('parfume_comments_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-catalog'));
        }

        $comment_id = intval($_POST['comment_id']);
        $action = sanitize_text_field($_POST['moderation_action']); // approve, reject, spam, delete

        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';

        switch ($action) {
            case 'approve':
                $result = $wpdb->update(
                    $comments_table,
                    array('status' => self::STATUS_APPROVED),
                    array('id' => $comment_id),
                    array('%s'),
                    array('%d')
                );
                break;

            case 'reject':
                $result = $wpdb->update(
                    $comments_table,
                    array('status' => self::STATUS_REJECTED),
                    array('id' => $comment_id),
                    array('%s'),
                    array('%d')
                );
                break;

            case 'spam':
                $result = $wpdb->update(
                    $comments_table,
                    array('status' => self::STATUS_SPAM),
                    array('id' => $comment_id),
                    array('%s'),
                    array('%d')
                );
                break;

            case 'delete':
                $result = $wpdb->delete(
                    $comments_table,
                    array('id' => $comment_id),
                    array('%d')
                );
                break;

            default:
                wp_send_json_error(__('Неизвестно действие', 'parfume-catalog'));
        }

        if ($result !== false) {
            wp_send_json_success(__('Действието е изпълнено успешно', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Възникна грешка', 'parfume-catalog'));
        }
    }

    /**
     * Валидация на коментар данни
     */
    private function validate_comment_data($post_id, $rating, $comment_text, $author_name, $author_ip) {
        // Проверка на post
        if (!get_post($post_id) || get_post_type($post_id) !== 'parfumes') {
            return new WP_Error('invalid_post', __('Невалиден парфюм', 'parfume-catalog'));
        }

        // Проверка на рейтинг
        if ($rating < 1 || $rating > 5) {
            return new WP_Error('invalid_rating', __('Рейтингът трябва да е между 1 и 5', 'parfume-catalog'));
        }

        // Проверка на коментар
        if (empty(trim($comment_text))) {
            return new WP_Error('empty_comment', __('Моля, напишете мнение', 'parfume-catalog'));
        }

        if (strlen($comment_text) > 1000) {
            return new WP_Error('comment_too_long', __('Мнението е твърде дълго (максимум 1000 символа)', 'parfume-catalog'));
        }

        // Проверка на име
        if (!empty($author_name) && strlen($author_name) > 50) {
            return new WP_Error('name_too_long', __('Името е твърде дълго (максимум 50 символа)', 'parfume-catalog'));
        }

        // Проверка на IP
        if (empty($author_ip)) {
            return new WP_Error('invalid_ip', __('Невалиден IP адрес', 'parfume-catalog'));
        }

        return true;
    }

    /**
     * Проверка за спам
     */
    private function check_for_spam($comment_text, $author_name, $author_ip) {
        $settings = $this->get_comments_settings();
        $spam_words = isset($settings['spam_words']) ? explode(',', $settings['spam_words']) : array();
        $blocked_ips = isset($settings['blocked_ips']) ? explode(',', $settings['blocked_ips']) : array();

        // Проверка за блокирани IP адреси
        if (in_array($author_ip, array_map('trim', $blocked_ips))) {
            return array(
                'is_spam' => true,
                'message' => __('Вашият IP адрес е блокиран', 'parfume-catalog')
            );
        }

        // Проверка за спам думи
        $comment_lower = strtolower($comment_text . ' ' . $author_name);
        foreach ($spam_words as $spam_word) {
            $spam_word = trim(strtolower($spam_word));
            if (!empty($spam_word) && strpos($comment_lower, $spam_word) !== false) {
                return array(
                    'is_spam' => true,
                    'message' => __('Вашето мнение съдържа неподходящо съдържание', 'parfume-catalog')
                );
            }
        }

        // Проверка за твърде много линкове
        $link_count = substr_count($comment_text, 'http');
        if ($link_count > 2) {
            return array(
                'is_spam' => true,
                'message' => __('Твърде много линкове в мнението', 'parfume-catalog')
            );
        }

        // Проверка за повтарящи се символи
        if (preg_match('/(.)\1{10,}/', $comment_text)) {
            return array(
                'is_spam' => true,
                'message' => __('Мнението съдържа неподходящ формат', 'parfume-catalog')
            );
        }

        return array('is_spam' => false);
    }

    /**
     * Проверка дали потребителят е коментирал
     */
    private function has_user_commented($post_id, $author_ip, $author_name) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';

        $existing_comment = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $comments_table 
             WHERE post_id = %d 
             AND (author_ip = %s OR (author_name = %s AND author_name != '' AND author_name != %s))
             AND status != %s",
            $post_id,
            $author_ip,
            $author_name,
            __('Анонимен', 'parfume-catalog'),
            self::STATUS_SPAM
        ));

        return !empty($existing_comment);
    }

    /**
     * Запазване на коментар
     */
    private function save_comment($post_id, $rating, $comment_text, $author_name, $author_ip) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';

        // Ако името е празно, задаваме "Анонимен"
        if (empty(trim($author_name))) {
            $author_name = __('Анонимен', 'parfume-catalog');
        }

        $comment_data = array(
            'post_id' => $post_id,
            'author_name' => $author_name,
            'author_ip' => $author_ip,
            'rating' => $rating,
            'comment_text' => $comment_text,
            'status' => self::STATUS_PENDING,
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert(
            $comments_table,
            $comment_data,
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Получаване на одобрени коментари
     */
    private function get_approved_comments($post_id, $page = 1, $per_page = 10) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        $offset = ($page - 1) * $per_page;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $comments_table 
             WHERE post_id = %d AND status = %s 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $post_id,
            self::STATUS_APPROVED,
            $per_page,
            $offset
        ), ARRAY_A);
    }

    /**
     * Получаване на брой коментари
     */
    private function get_comments_count($post_id) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $comments_table WHERE post_id = %d AND status = %s",
            $post_id,
            self::STATUS_APPROVED
        ));
    }

    /**
     * Получаване на среден рейтинг
     */
    private function get_average_rating($post_id) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';

        $average = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $comments_table WHERE post_id = %d AND status = %s",
            $post_id,
            self::STATUS_APPROVED
        ));

        return $average ? round(floatval($average), 1) : 0;
    }

    /**
     * Рендериране на HTML за коментар
     */
    private function render_comment_html($comment) {
        $time_ago = human_time_diff(strtotime($comment['created_at']), current_time('timestamp'));
        
        ob_start();
        ?>
        <div class="parfume-comment" data-comment-id="<?php echo esc_attr($comment['id']); ?>">
            <div class="comment-header">
                <div class="comment-author">
                    <strong><?php echo esc_html($comment['author_name']); ?></strong>
                </div>
                <div class="comment-rating">
                    <?php echo $this->render_stars($comment['rating']); ?>
                </div>
                <div class="comment-date">
                    <?php printf(__('преди %s', 'parfume-catalog'), $time_ago); ?>
                </div>
            </div>
            <div class="comment-text">
                <?php echo wp_kses_post(nl2br($comment['comment_text'])); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендериране на звезди за рейтинг
     */
    private function render_stars($rating, $max_rating = 5) {
        $output = '<div class="rating-stars">';
        
        for ($i = 1; $i <= $max_rating; $i++) {
            $class = $i <= $rating ? 'star-filled' : 'star-empty';
            $output .= '<span class="star ' . $class . '">★</span>';
        }
        
        $output .= '</div>';
        return $output;
    }

    /**
     * Получаване на IP адрес на потребителя
     */
    private function get_user_ip() {
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
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Проверка на CAPTCHA
     */
    private function verify_captcha($post_data) {
        $settings = $this->get_comments_settings();
        
        if (!isset($settings['captcha_type']) || $settings['captcha_type'] === 'none') {
            return true;
        }

        switch ($settings['captcha_type']) {
            case 'recaptcha':
                return $this->verify_recaptcha($post_data);
            case 'simple_math':
                return $this->verify_simple_math($post_data);
            case 'simple_question':
                return $this->verify_simple_question($post_data);
        }

        return true;
    }

    /**
     * Проверка на reCAPTCHA
     */
    private function verify_recaptcha($post_data) {
        $settings = $this->get_comments_settings();
        $secret_key = isset($settings['recaptcha_secret']) ? $settings['recaptcha_secret'] : '';
        $response = isset($post_data['g-recaptcha-response']) ? $post_data['g-recaptcha-response'] : '';

        if (empty($secret_key) || empty($response)) {
            return false;
        }

        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $verify_data = array(
            'secret' => $secret_key,
            'response' => $response,
            'remoteip' => $this->get_user_ip()
        );

        $verify_response = wp_remote_post($verify_url, array(
            'body' => $verify_data,
            'timeout' => 10
        ));

        if (is_wp_error($verify_response)) {
            return false;
        }

        $verify_result = json_decode(wp_remote_retrieve_body($verify_response), true);
        return isset($verify_result['success']) && $verify_result['success'] === true;
    }

    /**
     * Проверка на проста математика
     */
    private function verify_simple_math($post_data) {
        $user_answer = isset($post_data['math_answer']) ? intval($post_data['math_answer']) : 0;
        $correct_answer = isset($post_data['math_correct']) ? intval($post_data['math_correct']) : 0;
        
        return $user_answer === $correct_answer;
    }

    /**
     * Проверка на прост въпрос
     */
    private function verify_simple_question($post_data) {
        $user_answer = isset($post_data['question_answer']) ? strtolower(trim($post_data['question_answer'])) : '';
        $correct_answer = isset($post_data['question_correct']) ? strtolower(trim($post_data['question_correct'])) : '';
        
        return $user_answer === $correct_answer;
    }

    /**
     * Дали CAPTCHA е включена
     */
    private function is_captcha_enabled() {
        $settings = $this->get_comments_settings();
        return isset($settings['captcha_type']) && $settings['captcha_type'] !== 'none';
    }

    /**
     * Изпращане на известие до администратор
     */
    private function send_admin_notification($comment_id) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        $comment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $comments_table WHERE id = %d",
            $comment_id
        ), ARRAY_A);

        if (!$comment) {
            return;
        }

        $post = get_post($comment['post_id']);
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(__('[%s] Ново мнение чака одобрение', 'parfume-catalog'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Здравейте!\n\nИмате ново мнение което чака одобрение:\n\nПарфюм: %s\nАвтор: %s\nРейтинг: %d/5\nМнение: %s\n\nЗа да одобрите или отхвърлите мнението, влезте в администраторския панел:\n%s\n\nБлагодарим!", 'parfume-catalog'),
            $post->post_title,
            $comment['author_name'],
            $comment['rating'],
            $comment['comment_text'],
            admin_url('admin.php?page=parfume-catalog-comments')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Добавяне на admin страница за коментари
     */
    public function add_comments_admin_page() {
        add_submenu_page(
            'parfume-catalog',
            __('Comments Management', 'parfume-catalog'),
            __('Comments', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-comments',
            array($this, 'render_comments_admin_page')
        );
    }

    /**
     * Рендериране на admin страница за коментари
     */
    public function render_comments_admin_page() {
        // Обработка на bulk actions
        if (isset($_POST['bulk_action']) && isset($_POST['comment_ids'])) {
            $this->handle_bulk_comment_actions();
        }

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;

        $comments = $this->get_admin_comments($status_filter, $current_page, $per_page);
        $total_comments = $this->get_admin_comments_count($status_filter);
        $total_pages = ceil($total_comments / $per_page);

        ?>
        <div class="wrap">
            <h1><?php _e('Comments Management', 'parfume-catalog'); ?></h1>
            
            <div class="comments-admin-header">
                <div class="comments-status-filters">
                    <?php
                    $statuses = array(
                        'pending' => __('Чакащи', 'parfume-catalog'),
                        'approved' => __('Одобрени', 'parfume-catalog'),
                        'rejected' => __('Отхвърлени', 'parfume-catalog'),
                        'spam' => __('Спам', 'parfume-catalog')
                    );
                    
                    foreach ($statuses as $status => $label) {
                        $count = $this->get_admin_comments_count($status);
                        $current_class = ($status_filter === $status) ? 'current' : '';
                        printf(
                            '<a href="?page=parfume-catalog-comments&status=%s" class="%s">%s (%d)</a> | ',
                            $status,
                            $current_class,
                            $label,
                            $count
                        );
                    }
                    ?>
                </div>
            </div>

            <form method="post">
                <?php wp_nonce_field('parfume_comments_bulk_action', 'parfume_comments_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value=""><?php _e('Bulk Actions', 'parfume-catalog'); ?></option>
                            <option value="approve"><?php _e('Одобри', 'parfume-catalog'); ?></option>
                            <option value="reject"><?php _e('Отхвърли', 'parfume-catalog'); ?></option>
                            <option value="spam"><?php _e('Маркирай като спам', 'parfume-catalog'); ?></option>
                            <option value="delete"><?php _e('Изтрий', 'parfume-catalog'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php _e('Apply', 'parfume-catalog'); ?>" />
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped comments">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" />
                            </td>
                            <th class="manage-column"><?php _e('Автор', 'parfume-catalog'); ?></th>
                            <th class="manage-column"><?php _e('Мнение', 'parfume-catalog'); ?></th>
                            <th class="manage-column"><?php _e('Рейтинг', 'parfume-catalog'); ?></th>
                            <th class="manage-column"><?php _e('Парфюм', 'parfume-catalog'); ?></th>
                            <th class="manage-column"><?php _e('Дата', 'parfume-catalog'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="6"><?php _e('Няма коментари с този статус', 'parfume-catalog'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="comment_ids[]" value="<?php echo esc_attr($comment['id']); ?>" />
                                    </th>
                                    <td>
                                        <strong><?php echo esc_html($comment['author_name']); ?></strong><br />
                                        <small><?php echo esc_html($comment['author_ip']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo wp_trim_words(esc_html($comment['comment_text']), 20); ?>
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
                                    </td>
                                    <td><?php echo $this->render_stars($comment['rating']); ?></td>
                                    <td>
                                        <?php 
                                        $post = get_post($comment['post_id']);
                                        if ($post) {
                                            printf('<a href="%s" target="_blank">%s</a>', get_permalink($post->ID), esc_html($post->post_title));
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

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
            </form>

            <div class="comments-settings-section">
                <h2><?php _e('Настройки за коментари', 'parfume-catalog'); ?></h2>
                <?php $this->render_comments_settings_form(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Получаване на коментари за admin
     */
    private function get_admin_comments($status, $page = 1, $per_page = 20) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        $offset = ($page - 1) * $per_page;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $comments_table 
             WHERE status = %s 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $status,
            $per_page,
            $offset
        ), ARRAY_A);
    }

    /**
     * Получаване на брой коментари за admin
     */
    private function get_admin_comments_count($status) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $comments_table WHERE status = %s",
            $status
        ));
    }

    /**
     * Обработка на bulk actions
     */
    private function handle_bulk_comment_actions() {
        if (!wp_verify_nonce($_POST['parfume_comments_nonce'], 'parfume_comments_bulk_action')) {
            return;
        }

        $action = sanitize_text_field($_POST['bulk_action']);
        $comment_ids = array_map('intval', $_POST['comment_ids']);

        if (empty($action) || empty($comment_ids)) {
            return;
        }

        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';

        foreach ($comment_ids as $comment_id) {
            switch ($action) {
                case 'approve':
                    $wpdb->update(
                        $comments_table,
                        array('status' => self::STATUS_APPROVED),
                        array('id' => $comment_id),
                        array('%s'),
                        array('%d')
                    );
                    break;

                case 'reject':
                    $wpdb->update(
                        $comments_table,
                        array('status' => self::STATUS_REJECTED),
                        array('id' => $comment_id),
                        array('%s'),
                        array('%d')
                    );
                    break;

                case 'spam':
                    $wpdb->update(
                        $comments_table,
                        array('status' => self::STATUS_SPAM),
                        array('id' => $comment_id),
                        array('%s'),
                        array('%d')
                    );
                    break;

                case 'delete':
                    $wpdb->delete(
                        $comments_table,
                        array('id' => $comment_id),
                        array('%d')
                    );
                    break;
            }
        }

        $redirect_url = add_query_arg(array(
            'page' => 'parfume-catalog-comments',
            'status' => isset($_GET['status']) ? $_GET['status'] : 'pending',
            'updated' => count($comment_ids)
        ), admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Рендериране на настройки форма
     */
    private function render_comments_settings_form() {
        $settings = $this->get_comments_settings();
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('parfume_catalog_comments_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('CAPTCHA тип', 'parfume-catalog'); ?></th>
                    <td>
                        <select name="parfume_catalog_comments_settings[captcha_type]">
                            <option value="none" <?php selected($settings['captcha_type'], 'none'); ?>><?php _e('Без CAPTCHA', 'parfume-catalog'); ?></option>
                            <option value="simple_math" <?php selected($settings['captcha_type'], 'simple_math'); ?>><?php _e('Проста математика', 'parfume-catalog'); ?></option>
                            <option value="simple_question" <?php selected($settings['captcha_type'], 'simple_question'); ?>><?php _e('Прост въпрос', 'parfume-catalog'); ?></option>
                            <option value="recaptcha" <?php selected($settings['captcha_type'], 'recaptcha'); ?>><?php _e('Google reCAPTCHA', 'parfume-catalog'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Спам думи', 'parfume-catalog'); ?></th>
                    <td>
                        <textarea name="parfume_catalog_comments_settings[spam_words]" rows="5" class="large-text"><?php echo esc_textarea($settings['spam_words']); ?></textarea>
                        <p class="description"><?php _e('Разделени със запетая', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Блокирани IP адреси', 'parfume-catalog'); ?></th>
                    <td>
                        <textarea name="parfume_catalog_comments_settings[blocked_ips]" rows="3" class="large-text"><?php echo esc_textarea($settings['blocked_ips']); ?></textarea>
                        <p class="description"><?php _e('Разделени със запетая', 'parfume-catalog'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Регистриране на настройки
     */
    public function register_comments_settings() {
        register_setting('parfume_catalog_comments_settings', 'parfume_catalog_comments_settings', array(
            'sanitize_callback' => array($this, 'sanitize_comments_settings')
        ));
    }

    /**
     * Санитизиране на настройки
     */
    public function sanitize_comments_settings($input) {
        $sanitized = array();
        
        $sanitized['captcha_type'] = sanitize_text_field($input['captcha_type']);
        $sanitized['spam_words'] = sanitize_textarea_field($input['spam_words']);
        $sanitized['blocked_ips'] = sanitize_textarea_field($input['blocked_ips']);
        
        return $sanitized;
    }

    /**
     * Получаване на настройки за коментари
     */
    private function get_comments_settings() {
        $defaults = array(
            'captcha_type' => 'simple_math',
            'spam_words' => '',
            'blocked_ips' => '',
            'auto_approve' => false,
            'email_notifications' => true
        );
        
        $settings = get_option('parfume_catalog_comments_settings', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Admin notice за pending коментари
     */
    public function pending_comments_notice() {
        $screen = get_current_screen();
        if ($screen->id !== 'parfume-catalog_page_parfume-catalog-comments') {
            $pending_count = $this->get_admin_comments_count('pending');
            if ($pending_count > 0) {
                printf(
                    '<div class="notice notice-info"><p><strong>Parfume Catalog:</strong> <a href="%s">%s</a></p></div>',
                    admin_url('admin.php?page=parfume-catalog-comments'),
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
                    "aggregateRating": {
                        "@type": "AggregateRating",
                        "ratingValue": "<?php echo esc_js($average_rating); ?>",
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
     * CSS за коментари
     */
    private function get_comments_css() {
        return '
        .parfume-comments-section {
            margin-top: 40px;
            padding: 30px 0;
            border-top: 1px solid #eee;
        }
        
        .comments-form {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
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
        }
        
        .rating-input label:hover,
        .rating-input input:checked ~ label,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }
        
        .comments-list {
            margin-top: 30px;
        }
        
        .parfume-comment {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .star-empty {
            color: #ddd;
        }
        
        .comment-date {
            color: #666;
            font-size: 14px;
        }
        
        .load-more-comments {
            text-align: center;
            margin-top: 20px;
        }
        
        .no-comments-message {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }
        ';
    }

    /**
     * Получаване на настройки (public method)
     */
    public function get_settings() {
        return $this->get_comments_settings();
    }
}