<?php
/**
 * Comments модул за ревюта и рейтинги
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Comments {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_submit_comment', array($this, 'submit_comment'));
        add_action('wp_ajax_nopriv_pc_submit_comment', array($this, 'submit_comment'));
        add_action('wp_ajax_pc_approve_comment', array($this, 'approve_comment'));
        add_action('wp_ajax_pc_reject_comment', array($this, 'reject_comment'));
        add_action('wp_ajax_pc_delete_comment', array($this, 'delete_comment'));
        add_shortcode('pc_comments_section', array($this, 'comments_section_shortcode'));
    }
    
    public function init() {
        // Проверка дали функционалността е активна
        $options = get_option('parfume_catalog_settings', array());
        if (empty($options['comments_enabled'])) {
            return;
        }
        
        // Добавяне на comments секция в single парфюм
        add_action('pc_after_parfume_content', array($this, 'add_comments_section'));
        
        // Admin notices за нови коментари
        if (is_admin()) {
            add_action('admin_notices', array($this, 'admin_notices'));
        }
    }
    
    /**
     * Изпращане на коментар
     */
    public function submit_comment() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $author_name = sanitize_text_field($_POST['author_name']);
        $rating = intval($_POST['rating']);
        $comment_text = sanitize_textarea_field($_POST['comment_text']);
        $captcha_response = sanitize_text_field($_POST['captcha_response']);
        
        // Валидация
        if (!$post_id || get_post_type($post_id) !== 'parfumes') {
            wp_send_json_error(__('Невалиден парфюм', 'parfume-catalog'));
        }
        
        if (empty($comment_text)) {
            wp_send_json_error(__('Моля въведете коментар', 'parfume-catalog'));
        }
        
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(__('Невалиден рейтинг', 'parfume-catalog'));
        }
        
        // Проверка на CAPTCHA
        if (!$this->verify_captcha($captcha_response)) {
            wp_send_json_error(__('Невалиден CAPTCHA', 'parfume-catalog'));
        }
        
        // Проверка за спам
        if ($this->is_spam($comment_text, $author_name)) {
            wp_send_json_error(__('Коментарът е отхвърлен като спам', 'parfume-catalog'));
        }
        
        // Проверка за дублиращи коментари от същия IP
        $user_ip = $_SERVER['REMOTE_ADDR'];
        if ($this->has_recent_comment($post_id, $user_ip)) {
            wp_send_json_error(__('Вече сте оставили коментар за този парфюм', 'parfume-catalog'));
        }
        
        // Подготовка на коментара
        $comment_data = array(
            'id' => uniqid(),
            'post_id' => $post_id,
            'author_name' => !empty($author_name) ? $author_name : __('Анонимен', 'parfume-catalog'),
            'rating' => $rating,
            'comment_text' => $comment_text,
            'ip_address' => $user_ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'date_created' => current_time('mysql'),
            'status' => 'pending',
            'approved' => false
        );
        
        // Запазване на коментара
        $this->save_comment($comment_data);
        
        // Изпращане на email известие до админа
        $this->send_admin_notification($comment_data);
        
        wp_send_json_success(__('Вашият коментар е изпратен за одобрение', 'parfume-catalog'));
    }
    
    /**
     * Одобряване на коментар
     */
    public function approve_comment() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $comment_id = sanitize_text_field($_POST['comment_id']);
        $post_id = intval($_POST['post_id']);
        
        $result = $this->update_comment_status($post_id, $comment_id, 'approved');
        
        if ($result) {
            wp_send_json_success(__('Коментарът е одобрен', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при одобряване', 'parfume-catalog'));
        }
    }
    
    /**
     * Отхвърляне на коментар
     */
    public function reject_comment() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $comment_id = sanitize_text_field($_POST['comment_id']);
        $post_id = intval($_POST['post_id']);
        
        $result = $this->update_comment_status($post_id, $comment_id, 'rejected');
        
        if ($result) {
            wp_send_json_success(__('Коментарът е отхвърлен', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при отхвърляне', 'parfume-catalog'));
        }
    }
    
    /**
     * Изтриване на коментар
     */
    public function delete_comment() {
        check_ajax_referer('pc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate права за това действие', 'parfume-catalog'));
        }
        
        $comment_id = sanitize_text_field($_POST['comment_id']);
        $post_id = intval($_POST['post_id']);
        
        $result = $this->delete_comment_by_id($post_id, $comment_id);
        
        if ($result) {
            wp_send_json_success(__('Коментарът е изтрит', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Грешка при изтриване', 'parfume-catalog'));
        }
    }
    
    /**
     * Shortcode за comments секция
     */
    public function comments_section_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID()
        ), $atts);
        
        return $this->get_comments_section($atts['post_id']);
    }
    
    /**
     * Добавяне на comments секция
     */
    public function add_comments_section() {
        echo $this->get_comments_section();
    }
    
    /**
     * Генериране на comments секция
     */
    public function get_comments_section($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (get_post_type($post_id) !== 'parfumes') {
            return '';
        }
        
        $comments = $this->get_approved_comments($post_id);
        $average_rating = $this->get_average_rating($post_id);
        $total_comments = count($comments);
        
        ob_start();
        ?>
        <div class="pc-comments-section" id="pc-comments-section">
            <h3 class="pc-comments-title">
                <?php _e('Потребителски мнения и оценка', 'parfume-catalog'); ?>
            </h3>
            
            <?php if ($total_comments > 0): ?>
                <div class="pc-rating-summary">
                    <div class="pc-average-rating">
                        <span class="rating-number"><?php echo number_format($average_rating, 1); ?></span>
                        <div class="rating-stars">
                            <?php echo $this->render_stars($average_rating); ?>
                        </div>
                        <span class="rating-count">
                            (<?php echo sprintf(_n('%d мнение', '%d мнения', $total_comments, 'parfume-catalog'), $total_comments); ?>)
                        </span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Comment Form -->
            <div class="pc-comment-form-wrapper">
                <h4><?php _e('Споделете вашето мнение', 'parfume-catalog'); ?></h4>
                
                <form id="pc-comment-form" class="pc-comment-form">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    
                    <div class="form-row">
                        <label for="author_name"><?php _e('Име (по избор)', 'parfume-catalog'); ?></label>
                        <input type="text" id="author_name" name="author_name" placeholder="<?php _e('Анонимен', 'parfume-catalog'); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label for="rating"><?php _e('Оценка', 'parfume-catalog'); ?> *</label>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star" data-rating="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                            <input type="hidden" id="rating" name="rating" value="" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="comment_text"><?php _e('Коментар', 'parfume-catalog'); ?> *</label>
                        <textarea id="comment_text" name="comment_text" rows="4" required placeholder="<?php _e('Споделете вашето мнение за този парфюм...', 'parfume-catalog'); ?>"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <label for="captcha_response"><?php _e('Антиспам проверка', 'parfume-catalog'); ?> *</label>
                        <div class="captcha-wrapper">
                            <?php echo $this->render_captcha(); ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="button button-primary">
                            <?php _e('Изпрати мнение', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Comments List -->
            <div class="pc-comments-list">
                <?php if (empty($comments)): ?>
                    <p class="no-comments"><?php _e('Все още няма оценки', 'parfume-catalog'); ?></p>
                <?php else: ?>
                    <h4><?php _e('Мнения от потребители', 'parfume-catalog'); ?></h4>
                    
                    <?php foreach ($comments as $comment): ?>
                        <div class="pc-comment" data-comment-id="<?php echo esc_attr($comment['id']); ?>">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <strong><?php echo esc_html($comment['author_name']); ?></strong>
                                </div>
                                <div class="comment-rating">
                                    <?php echo $this->render_stars($comment['rating']); ?>
                                </div>
                                <div class="comment-date">
                                    <?php echo date('d.m.Y', strtotime($comment['date_created'])); ?>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(esc_html($comment['comment_text'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Rating stars interaction
            $('.rating-input .star').on('click', function() {
                var rating = $(this).data('rating');
                $('#rating').val(rating);
                
                $('.rating-input .star').removeClass('selected');
                for (var i = 1; i <= rating; i++) {
                    $('.rating-input .star[data-rating="' + i + '"]').addClass('selected');
                }
            });
            
            // Form submission
            $('#pc-comment-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=pc_submit_comment&nonce=<?php echo wp_create_nonce('pc_nonce'); ?>';
                
                var submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).text('<?php _e('Изпраща се...', 'parfume-catalog'); ?>');
                
                $.post(pc_ajax.ajax_url, formData, function(response) {
                    submitBtn.prop('disabled', false).text('<?php _e('Изпрати мнение', 'parfume-catalog'); ?>');
                    
                    if (response.success) {
                        alert(response.data);
                        $('#pc-comment-form')[0].reset();
                        $('#rating').val('');
                        $('.rating-input .star').removeClass('selected');
                    } else {
                        alert(response.data);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Запазване на коментар
     */
    private function save_comment($comment_data) {
        $post_id = $comment_data['post_id'];
        $comments = get_post_meta($post_id, '_pc_comments', true);
        
        if (!is_array($comments)) {
            $comments = array();
        }
        
        $comments[] = $comment_data;
        update_post_meta($post_id, '_pc_comments', $comments);
        
        // Обновяване на статистики
        $this->update_rating_stats($post_id);
        
        return true;
    }
    
    /**
     * Получаване на одобрени коментари
     */
    private function get_approved_comments($post_id) {
        $comments = get_post_meta($post_id, '_pc_comments', true);
        
        if (!is_array($comments)) {
            return array();
        }
        
        $approved_comments = array();
        foreach ($comments as $comment) {
            if (!empty($comment['status']) && $comment['status'] === 'approved') {
                $approved_comments[] = $comment;
            }
        }
        
        // Сортиране по дата (най-нови първи)
        usort($approved_comments, function($a, $b) {
            return strtotime($b['date_created']) - strtotime($a['date_created']);
        });
        
        return $approved_comments;
    }
    
    /**
     * Получаване на средна оценка
     */
    public function get_average_rating($post_id) {
        $comments = $this->get_approved_comments($post_id);
        
        if (empty($comments)) {
            return 0;
        }
        
        $total_rating = 0;
        foreach ($comments as $comment) {
            $total_rating += intval($comment['rating']);
        }
        
        return $total_rating / count($comments);
    }
    
    /**
     * Обновяване на статус на коментар
     */
    private function update_comment_status($post_id, $comment_id, $status) {
        $comments = get_post_meta($post_id, '_pc_comments', true);
        
        if (!is_array($comments)) {
            return false;
        }
        
        foreach ($comments as &$comment) {
            if ($comment['id'] === $comment_id) {
                $comment['status'] = $status;
                $comment['approved'] = ($status === 'approved');
                break;
            }
        }
        
        update_post_meta($post_id, '_pc_comments', $comments);
        
        if ($status === 'approved') {
            $this->update_rating_stats($post_id);
        }
        
        return true;
    }
    
    /**
     * Изтриване на коментар по ID
     */
    private function delete_comment_by_id($post_id, $comment_id) {
        $comments = get_post_meta($post_id, '_pc_comments', true);
        
        if (!is_array($comments)) {
            return false;
        }
        
        foreach ($comments as $index => $comment) {
            if ($comment['id'] === $comment_id) {
                unset($comments[$index]);
                break;
            }
        }
        
        $comments = array_values($comments); // Re-index array
        update_post_meta($post_id, '_pc_comments', $comments);
        
        $this->update_rating_stats($post_id);
        
        return true;
    }
    
    /**
     * Обновяване на статистики за рейтинг
     */
    private function update_rating_stats($post_id) {
        $approved_comments = $this->get_approved_comments($post_id);
        $average_rating = $this->get_average_rating($post_id);
        $total_ratings = count($approved_comments);
        
        update_post_meta($post_id, '_pc_average_rating', $average_rating);
        update_post_meta($post_id, '_pc_total_ratings', $total_ratings);
    }
    
    /**
     * Проверка за спам
     */
    private function is_spam($comment_text, $author_name) {
        $options = get_option('parfume_catalog_settings', array());
        $spam_keywords = !empty($options['spam_keywords']) ? explode(',', $options['spam_keywords']) : array();
        
        $text_to_check = strtolower($comment_text . ' ' . $author_name);
        
        foreach ($spam_keywords as $keyword) {
            $keyword = trim(strtolower($keyword));
            if (!empty($keyword) && strpos($text_to_check, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Проверка за скорошен коментар от същия IP
     */
    private function has_recent_comment($post_id, $ip_address) {
        $comments = get_post_meta($post_id, '_pc_comments', true);
        
        if (!is_array($comments)) {
            return false;
        }
        
        $time_limit = strtotime('-24 hours');
        
        foreach ($comments as $comment) {
            if (!empty($comment['ip_address']) && $comment['ip_address'] === $ip_address) {
                $comment_time = strtotime($comment['date_created']);
                if ($comment_time > $time_limit) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Проверка на CAPTCHA
     */
    private function verify_captcha($response) {
        // Прост математически CAPTCHA
        $stored_answer = get_transient('pc_captcha_' . session_id());
        
        if ($stored_answer && intval($response) === intval($stored_answer)) {
            delete_transient('pc_captcha_' . session_id());
            return true;
        }
        
        return false;
    }
    
    /**
     * Рендериране на CAPTCHA
     */
    private function render_captcha() {
        if (!session_id()) {
            session_start();
        }
        
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $answer = $num1 + $num2;
        
        set_transient('pc_captcha_' . session_id(), $answer, 600); // 10 minutes
        
        return sprintf(
            '<label>%s + %s = ?</label><input type="number" name="captcha_response" required min="0" max="20">',
            $num1,
            $num2
        );
    }
    
    /**
     * Рендериране на звезди
     */
    private function render_stars($rating) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        $html = '';
        
        // Пълни звезди
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<span class="star full">★</span>';
        }
        
        // Половин звезда
        if ($half_star) {
            $html .= '<span class="star half">★</span>';
        }
        
        // Празни звезди
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="star empty">☆</span>';
        }
        
        return $html;
    }
    
    /**
     * Изпращане на известие до админа
     */
    private function send_admin_notification($comment_data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $post_title = get_the_title($comment_data['post_id']);
        $post_url = get_permalink($comment_data['post_id']);
        
        $subject = sprintf(__('[%s] Нов коментар за одобрение', 'parfume-catalog'), $site_name);
        
        $message = sprintf(
            __("Нов коментар за одобрение:\n\nПарфюм: %s\nАвтор: %s\nОценка: %d/5\nКоментар: %s\n\nВижте поста: %s\n\nОдобрете коментара в админ панела.", 'parfume-catalog'),
            $post_title,
            $comment_data['author_name'],
            $comment_data['rating'],
            $comment_data['comment_text'],
            $post_url
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Admin notices за чакащи коментари
     */
    public function admin_notices() {
        $pending_count = $this->get_pending_comments_count();
        
        if ($pending_count > 0) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p>';
            echo sprintf(
                _n(
                    'Имате %d коментар чакащ одобрение.',
                    'Имате %d коментара чакащи одобрение.',
                    $pending_count,
                    'parfume-catalog'
                ),
                $pending_count
            );
            echo ' <a href="' . admin_url('admin.php?page=parfume-catalog-comments') . '">' . __('Прегледайте ги тук', 'parfume-catalog') . '</a>';
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Получаване на броя чакащи коментари
     */
    private function get_pending_comments_count() {
        global $wpdb;
        
        $query = "
            SELECT pm.meta_value
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'parfumes'
            AND pm.meta_key = '_pc_comments'
            AND pm.meta_value != ''
        ";
        
        $results = $wpdb->get_results($query);
        $pending_count = 0;
        
        foreach ($results as $row) {
            $comments = maybe_unserialize($row->meta_value);
            if (is_array($comments)) {
                foreach ($comments as $comment) {
                    if (!empty($comment['status']) && $comment['status'] === 'pending') {
                        $pending_count++;
                    }
                }
            }
        }
        
        return $pending_count;
    }
}