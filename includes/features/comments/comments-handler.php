<?php
/**
 * Comments Handler
 * Manages comment submission without registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Comments_Handler {
    
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_parfume_submit_comment', [$this, 'submit_comment']);
        add_action('wp_ajax_nopriv_parfume_submit_comment', [$this, 'submit_comment']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Modify comment status
        add_filter('pre_comment_approved', [$this, 'set_comment_status'], 10, 2);
        
        // Email notifications
        add_action('comment_post', [$this, 'notify_admin'], 10, 3);
        
        // Comment display modifications
        add_filter('comment_text', [$this, 'add_rating_display'], 10, 2);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_singular('parfume')) {
            return;
        }
        
        wp_enqueue_script(
            'parfume-comments',
            plugin_dir_url(__FILE__) . '../../../assets/js/comments.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('parfume-comments', 'parfumeComments', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_comment_nonce'),
            'strings' => [
                'required' => 'Това поле е задължително',
                'invalid_email' => 'Невалиден имейл адрес',
                'rating_required' => 'Моля, изберете оценка',
                'success' => 'Коментарът е изпратен успешно и чака одобрение',
                'error' => 'Възникна грешка. Моля, опитайте отново'
            ]
        ]);
    }
    
    /**
     * Handle comment submission
     */
    public function submit_comment() {
        // Verify nonce
        check_ajax_referer('parfume_comment_nonce', 'nonce');
        
        // Get and sanitize data
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $captcha_answer = isset($_POST['captcha_answer']) ? sanitize_text_field($_POST['captcha_answer']) : '';
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $name = 'Анонимен';
        }
        
        if (empty($email)) {
            $errors[] = 'Имейлът е задължителен';
        } elseif (!is_email($email)) {
            $errors[] = 'Невалиден имейл адрес';
        }
        
        if (empty($comment)) {
            $errors[] = 'Коментарът е задължителен';
        }
        
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Моля, изберете оценка от 1 до 5';
        }
        
        // Simple captcha validation
        if (get_option('parfume_comments_captcha_enabled', true)) {
            $expected_answer = isset($_POST['captcha_expected']) ? intval($_POST['captcha_expected']) : 0;
            if (intval($captcha_answer) !== $expected_answer) {
                $errors[] = 'Грешен отговор на математическия въпрос';
            }
        }
        
        // Check for spam keywords
        if ($this->is_spam($comment, $name, $email)) {
            $errors[] = 'Коментарът съдържа забранено съдържание';
        }
        
        // Check IP rate limiting (max 1 comment per 5 minutes)
        if ($this->is_rate_limited()) {
            $errors[] = 'Моля, изчакайте преди да публикувате нов коментар';
        }
        
        if (!empty($errors)) {
            wp_send_json_error(['message' => implode('. ', $errors)]);
        }
        
        // Prepare comment data
        $comment_data = [
            'comment_post_ID' => $post_id,
            'comment_author' => $name,
            'comment_author_email' => $email,
            'comment_content' => $comment,
            'comment_type' => 'parfume_review',
            'comment_parent' => 0,
            'user_id' => 0,
            'comment_author_IP' => $this->get_user_ip(),
            'comment_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'comment_date' => current_time('mysql'),
            'comment_approved' => 0 // Pending approval
        ];
        
        // Insert comment
        $comment_id = wp_insert_comment($comment_data);
        
        if ($comment_id) {
            // Save rating as comment meta
            add_comment_meta($comment_id, 'parfume_rating', $rating);
            
            // Save IP for rate limiting
            $this->save_rate_limit_data();
            
            wp_send_json_success([
                'message' => 'Коментарът е изпратен успешно и чака одобрение'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Възникна грешка при изпращане на коментара'
            ]);
        }
    }
    
    /**
     * Set comment status to pending
     */
    public function set_comment_status($approved, $commentdata) {
        if (isset($commentdata['comment_type']) && $commentdata['comment_type'] === 'parfume_review') {
            return 0; // Always pending
        }
        return $approved;
    }
    
    /**
     * Send email notification to admin
     */
    public function notify_admin($comment_id, $approved, $commentdata) {
        if ($commentdata['comment_type'] !== 'parfume_review') {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $post_title = get_the_title($commentdata['comment_post_ID']);
        $comment_link = admin_url('comment.php?action=approve&c=' . $comment_id);
        
        $subject = sprintf('[%s] Нов коментар чака одобрение', get_bloginfo('name'));
        
        $message = sprintf(
            "Нов коментар чака одобрение за парфюм: %s\n\n" .
            "Автор: %s\n" .
            "Имейл: %s\n" .
            "Коментар: %s\n\n" .
            "Одобри коментара: %s",
            $post_title,
            $commentdata['comment_author'],
            $commentdata['comment_author_email'],
            $commentdata['comment_content'],
            $comment_link
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Add rating display to comment text
     */
    public function add_rating_display($comment_text, $comment) {
        if ($comment->comment_type !== 'parfume_review') {
            return $comment_text;
        }
        
        $rating = get_comment_meta($comment->comment_ID, 'parfume_rating', true);
        
        if ($rating) {
            $stars = $this->get_stars_html($rating);
            $comment_text = '<div class="comment-rating">' . $stars . '</div>' . $comment_text;
        }
        
        return $comment_text;
    }
    
    /**
     * Get stars HTML
     */
    private function get_stars_html($rating) {
        $html = '<div class="rating-stars">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $html .= '<span class="star filled">★</span>';
            } else {
                $html .= '<span class="star">☆</span>';
            }
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Check for spam
     */
    private function is_spam($comment, $name, $email) {
        $spam_keywords = get_option('parfume_comments_spam_keywords', '');
        
        if (empty($spam_keywords)) {
            return false;
        }
        
        $keywords = array_map('trim', explode("\n", $spam_keywords));
        $check_string = strtolower($comment . ' ' . $name . ' ' . $email);
        
        foreach ($keywords as $keyword) {
            if (empty($keyword)) {
                continue;
            }
            if (strpos($check_string, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check rate limiting
     */
    private function is_rate_limited() {
        $ip = $this->get_user_ip();
        $transient_key = 'parfume_comment_rate_' . md5($ip);
        $last_comment = get_transient($transient_key);
        
        if ($last_comment) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Save rate limit data
     */
    private function save_rate_limit_data() {
        $ip = $this->get_user_ip();
        $transient_key = 'parfume_comment_rate_' . md5($ip);
        set_transient($transient_key, time(), 5 * MINUTE_IN_SECONDS);
    }
    
    /**
     * Get user IP
     */
    private function get_user_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Get average rating for a post
     */
    public static function get_average_rating($post_id) {
        global $wpdb;
        
        $query = "
            SELECT AVG(CAST(cm.meta_value AS DECIMAL(3,2))) as avg_rating, COUNT(*) as count
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
            WHERE c.comment_post_ID = %d
            AND c.comment_approved = '1'
            AND c.comment_type = 'parfume_review'
            AND cm.meta_key = 'parfume_rating'
        ";
        
        $result = $wpdb->get_row($wpdb->prepare($query, $post_id));
        
        return [
            'average' => $result->avg_rating ? round($result->avg_rating, 1) : 0,
            'count' => $result->count ? intval($result->count) : 0
        ];
    }
}

// Initialize
new Parfume_Comments_Handler();