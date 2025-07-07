<?php
/**
 * Система за коментари/ревюта
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PC_Comments {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_submit_review', array($this, 'submit_review'));
        add_action('wp_ajax_nopriv_pc_submit_review', array($this, 'submit_review'));
        add_action('wp_ajax_pc_moderate_review', array($this, 'moderate_review'));
        add_action('wp_ajax_pc_delete_review', array($this, 'delete_review'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_shortcode('pc_reviews', array($this, 'reviews_shortcode'));
    }
    
    public function init() {
        // Създаване на таблица при активация
        $this->create_reviews_table();
        
        // Добавяне на ревюта в single parfume страници
        add_action('pc_after_parfume_content', array($this, 'display_reviews_section'));
        
        // Енqueue скрипт за CAPTCHA
        if ($this->is_captcha_enabled()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_captcha_scripts'));
        }
    }
    
    /**
     * Създаване на таблица за ревюта
     */
    private function create_reviews_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100) DEFAULT '',
            author_ip varchar(100) NOT NULL,
            rating tinyint(1) NOT NULL,
            review_text longtext NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Подаване на ревю
     */
    public function submit_review() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $author_name = sanitize_text_field($_POST['author_name']);
        $author_email = sanitize_email($_POST['author_email']);
        $rating = intval($_POST['rating']);
        $review_text = sanitize_textarea_field($_POST['review_text']);
        $captcha_response = sanitize_text_field($_POST['captcha_response']);
        
        // Валидация
        $validation = $this->validate_review_data($post_id, $author_name, $rating, $review_text, $captcha_response);
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
        }
        
        // Проверка за спам
        if ($this->is_spam($author_name, $author_email, $review_text)) {
            wp_send_json_error(__('Вашето мнение не може да бъде публикувано', 'parfume-catalog'));
        }
        
        // Проверка за дублиращи се ревюта от същия IP
        if ($this->has_recent_review_from_ip($post_id, $this->get_user_ip())) {
            wp_send_json_error(__('Вече сте оставили мнение за този парфюм', 'parfume-catalog'));
        }
        
        // Запазване на ревюто
        $review_id = $this->save_review($post_id, $author_name, $author_email, $rating, $review_text);
        
        if ($review_id) {
            // Изпращане на известие до админа
            $this->send_admin_notification($review_id);
            
            // Обновяване на средната оценка
            $this->update_average_rating($post_id);
            
            wp_send_json_success(array(
                'message' => __('Вашето мнение е изпратено за одобрение', 'parfume-catalog'),
                'review_id' => $review_id
            ));
        } else {
            wp_send_json_error(__('Възникна грешка при запазването', 'parfume-catalog'));
        }
    }
    
    /**
     * Валидация на данните за ревю
     */
    private function validate_review_data($post_id, $author_name, $rating, $review_text, $captcha_response) {
        // Проверка на парфюма
        if (!$post_id || get_post_type($post_id) !== 'parfumes') {
            return array('valid' => false, 'message' => __('Невалиден парфюм', 'parfume-catalog'));
        }
        
        // Проверка на име
        if (empty($author_name) || strlen($author_name) < 2) {
            return array('valid' => false, 'message' => __('Моля въведете валидно име', 'parfume-catalog'));
        }
        
        // Проверка на рейтинг
        if ($rating < 1 || $rating > 5) {
            return array('valid' => false, 'message' => __('Моля изберете оценка от 1 до 5 звезди', 'parfume-catalog'));
        }
        
        // Проверка на текст
        if (empty($review_text) || strlen($review_text) < 10) {
            return array('valid' => false, 'message' => __('Моля въведете мнение с минимум 10 символа', 'parfume-catalog'));
        }
        
        // Проверка на CAPTCHA
        if ($this->is_captcha_enabled() && !$this->verify_captcha($captcha_response)) {
            return array('valid' => false, 'message' => __('Моля потвърдете, че не сте робот', 'parfume-catalog'));
        }
        
        return array('valid' => true);
    }
    
    /**
     * Проверка за спам
     */
    private function is_spam($author_name, $author_email, $review_text) {
        $options = get_option('pc_comments_options', array());
        $blocked_words = isset($options['blocked_words']) ? $options['blocked_words'] : array();
        $blocked_domains = isset($options['blocked_domains']) ? $options['blocked_domains'] : array();
        
        // Проверка за блокирани думи
        foreach ($blocked_words as $word) {
            if (stripos($review_text, $word) !== false || stripos($author_name, $word) !== false) {
                return true;
            }
        }
        
        // Проверка за блокирани домейни в имейла
        if (!empty($author_email)) {
            $email_domain = substr(strrchr($author_email, "@"), 1);
            if (in_array($email_domain, $blocked_domains)) {
                return true;
            }
        }
        
        // Проверка за повтарящи се символи
        if (preg_match('/(.)\1{10,}/', $review_text)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Проверка за скорошно ревю от същия IP
     */
    private function has_recent_review_from_ip($post_id, $ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        $time_limit = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE post_id = %d AND author_ip = %s AND created_at > %s",
            $post_id, $ip, $time_limit
        ));
        
        return $count > 0;
    }
    
    /**
     * Запазване на ревю
     */
    private function save_review($post_id, $author_name, $author_email, $rating, $review_text) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'author_name' => empty($author_name) ? 'Анонимен' : $author_name,
                'author_email' => $author_email,
                'author_ip' => $this->get_user_ip(),
                'rating' => $rating,
                'review_text' => $review_text,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Изпращане на известие до админа
     */
    private function send_admin_notification($review_id) {
        $options = get_option('pc_comments_options', array());
        
        if (!isset($options['admin_notifications']) || !$options['admin_notifications']) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $review = $this->get_review($review_id);
        $post_title = get_the_title($review->post_id);
        
        $subject = sprintf(__('Ново мнение за %s', 'parfume-catalog'), $post_title);
        
        $message = sprintf(
            __("Ново мнение чака одобрение:\n\nПарфюм: %s\nАвтор: %s\nОценка: %d/5\nМнение: %s\n\nЗа одобрение отидете на: %s", 'parfume-catalog'),
            $post_title,
            $review->author_name,
            $review->rating,
            $review->review_text,
            admin_url('admin.php?page=pc-reviews')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Обновяване на средната оценка
     */
    private function update_average_rating($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as total 
             FROM $table_name 
             WHERE post_id = %d AND status = 'approved'",
            $post_id
        ));
        
        if ($stats) {
            update_post_meta($post_id, '_pc_average_rating', round($stats->average, 2));
            update_post_meta($post_id, '_pc_total_reviews', $stats->total);
        }
    }
    
    /**
     * Модерация на ревю
     */
    public function moderate_review() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('moderate_comments')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $review_id = intval($_POST['review_id']);
        $action = sanitize_text_field($_POST['action_type']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        switch ($action) {
            case 'approve':
                $result = $wpdb->update(
                    $table_name,
                    array('status' => 'approved'),
                    array('id' => $review_id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result) {
                    $review = $this->get_review($review_id);
                    $this->update_average_rating($review->post_id);
                    wp_send_json_success(__('Ревюто е одобрено', 'parfume-catalog'));
                }
                break;
                
            case 'reject':
                $result = $wpdb->update(
                    $table_name,
                    array('status' => 'rejected'),
                    array('id' => $review_id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result) {
                    wp_send_json_success(__('Ревюто е отхвърлено', 'parfume-catalog'));
                }
                break;
                
            case 'spam':
                $result = $wpdb->update(
                    $table_name,
                    array('status' => 'spam'),
                    array('id' => $review_id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result) {
                    wp_send_json_success(__('Ревюто е маркирано като спам', 'parfume-catalog'));
                }
                break;
        }
        
        wp_send_json_error(__('Възникна грешка', 'parfume-catalog'));
    }
    
    /**
     * Изтриване на ревю
     */
    public function delete_review() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        if (!current_user_can('moderate_comments')) {
            wp_die(__('Нямате права за това действие', 'parfume-catalog'));
        }
        
        $review_id = intval($_POST['review_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        $review = $this->get_review($review_id);
        if (!$review) {
            wp_send_json_error(__('Ревюто не съществува', 'parfume-catalog'));
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $review_id),
            array('%d')
        );
        
        if ($result) {
            $this->update_average_rating($review->post_id);
            wp_send_json_success(__('Ревюто е изтрито', 'parfume-catalog'));
        } else {
            wp_send_json_error(__('Възникна грешка при изтриването', 'parfume-catalog'));
        }
    }
    
    /**
     * Получаване на ревю по ID
     */
    private function get_review($review_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $review_id
        ));
    }
    
    /**
     * Показване на секция с ревюта
     */
    public function display_reviews_section($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        $options = get_option('pc_comments_options', array());
        if (isset($options['enabled']) && !$options['enabled']) {
            return;
        }
        
        echo '<div class="pc-reviews-section" id="pc-reviews">';
        echo '<h3>' . __('Потребителски мнения и оценка', 'parfume-catalog') . '</h3>';
        
        // Формуляр за ново ревю
        $this->display_review_form($post_id);
        
        // Списък с ревюта
        $this->display_reviews_list($post_id);
        
        echo '</div>';
    }
    
    /**
     * Формуляр за ново ревю
     */
    private function display_review_form($post_id) {
        ?>
        <div class="pc-review-form-wrapper">
            <h4><?php _e('Споделете вашето мнение', 'parfume-catalog'); ?></h4>
            
            <form id="pc-review-form" class="pc-review-form">
                <?php wp_nonce_field('pc_nonce', 'pc_nonce'); ?>
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                
                <div class="pc-form-row">
                    <div class="pc-form-group">
                        <label for="pc-author-name"><?php _e('Име', 'parfume-catalog'); ?>:</label>
                        <input type="text" 
                               id="pc-author-name" 
                               name="author_name" 
                               placeholder="<?php _e('Оставете празно за Анонимен', 'parfume-catalog'); ?>">
                    </div>
                    
                    <div class="pc-form-group">
                        <label for="pc-author-email"><?php _e('Имейл (по желание)', 'parfume-catalog'); ?>:</label>
                        <input type="email" 
                               id="pc-author-email" 
                               name="author_email" 
                               placeholder="<?php _e('example@email.com', 'parfume-catalog'); ?>">
                    </div>
                </div>
                
                <div class="pc-form-group">
                    <label><?php _e('Оценка', 'parfume-catalog'); ?>: <span class="required">*</span></label>
                    <div class="pc-rating-input">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" id="rating-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                            <label for="rating-<?php echo $i; ?>" class="star">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="pc-form-group">
                    <label for="pc-review-text"><?php _e('Вашето мнение', 'parfume-catalog'); ?>: <span class="required">*</span></label>
                    <textarea id="pc-review-text" 
                              name="review_text" 
                              rows="5" 
                              placeholder="<?php _e('Споделете вашето мнение за този парфюм...', 'parfume-catalog'); ?>" 
                              required></textarea>
                    <small class="pc-char-count"><?php _e('Минимум 10 символа', 'parfume-catalog'); ?></small>
                </div>
                
                <?php if ($this->is_captcha_enabled()): ?>
                    <div class="pc-form-group">
                        <?php $this->display_captcha(); ?>
                    </div>
                <?php endif; ?>
                
                <div class="pc-form-actions">
                    <button type="submit" class="pc-btn pc-btn-primary">
                        <?php _e('Изпрати мнение', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <div class="pc-form-message" id="pc-form-message"></div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Списък с ревюта
     */
    private function display_reviews_list($post_id) {
        $reviews = $this->get_approved_reviews($post_id);
        
        if (empty($reviews)) {
            echo '<div class="pc-no-reviews">';
            echo '<p>' . __('Все още няма оценки и мнения за този парфюм.', 'parfume-catalog') . '</p>';
            echo '</div>';
            return;
        }
        
        $average_rating = get_post_meta($post_id, '_pc_average_rating', true);
        $total_reviews = get_post_meta($post_id, '_pc_total_reviews', true);
        
        ?>
        <div class="pc-reviews-summary">
            <div class="pc-average-rating">
                <span class="pc-rating-number"><?php echo number_format($average_rating, 1); ?></span>
                <div class="pc-stars">
                    <?php echo $this->get_stars_html($average_rating); ?>
                </div>
                <span class="pc-total-reviews">
                    (<?php printf(_n('%d мнение', '%d мнения', $total_reviews, 'parfume-catalog'), $total_reviews); ?>)
                </span>
            </div>
        </div>
        
        <div class="pc-reviews-list">
            <?php foreach ($reviews as $review): ?>
                <div class="pc-review-item" data-review-id="<?php echo $review->id; ?>">
                    <div class="pc-review-header">
                        <div class="pc-review-author">
                            <strong><?php echo esc_html($review->author_name); ?></strong>
                        </div>
                        <div class="pc-review-meta">
                            <div class="pc-review-rating">
                                <?php echo $this->get_stars_html($review->rating); ?>
                            </div>
                            <div class="pc-review-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($review->created_at)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="pc-review-content">
                        <p><?php echo nl2br(esc_html($review->review_text)); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Получаване на одобрени ревюта
     */
    private function get_approved_reviews($post_id, $limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE post_id = %d AND status = 'approved' 
             ORDER BY created_at DESC 
             LIMIT %d",
            $post_id, $limit
        ));
    }
    
    /**
     * Генериране на HTML за звезди
     */
    private function get_stars_html($rating, $max_stars = 5) {
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = $max_stars - $full_stars - ($half_star ? 1 : 0);
        
        $html = '<div class="pc-stars">';
        
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
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Показване на CAPTCHA
     */
    private function display_captcha() {
        $options = get_option('pc_comments_options', array());
        $captcha_type = isset($options['captcha_type']) ? $options['captcha_type'] : 'simple';
        
        switch ($captcha_type) {
            case 'recaptcha':
                if (!empty($options['recaptcha_site_key'])) {
                    echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($options['recaptcha_site_key']) . '"></div>';
                }
                break;
                
            case 'simple':
            default:
                $num1 = rand(1, 10);
                $num2 = rand(1, 10);
                $answer = $num1 + $num2;
                
                echo '<label for="pc-captcha">' . sprintf(__('Колко е %d + %d?', 'parfume-catalog'), $num1, $num2) . '</label>';
                echo '<input type="number" id="pc-captcha" name="captcha_response" required>';
                echo '<input type="hidden" name="captcha_answer" value="' . $answer . '">';
                break;
        }
    }
    
    /**
     * Проверка на CAPTCHA
     */
    private function verify_captcha($response) {
        $options = get_option('pc_comments_options', array());
        $captcha_type = isset($options['captcha_type']) ? $options['captcha_type'] : 'simple';
        
        switch ($captcha_type) {
            case 'recaptcha':
                if (empty($options['recaptcha_secret_key'])) {
                    return false;
                }
                
                $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
                $verify_response = wp_remote_post($verify_url, array(
                    'body' => array(
                        'secret' => $options['recaptcha_secret_key'],
                        'response' => $response,
                        'remoteip' => $this->get_user_ip()
                    )
                ));
                
                if (is_wp_error($verify_response)) {
                    return false;
                }
                
                $verify_data = json_decode(wp_remote_retrieve_body($verify_response), true);
                return isset($verify_data['success']) && $verify_data['success'];
                
            case 'simple':
            default:
                $correct_answer = intval($_POST['captcha_answer']);
                return intval($response) === $correct_answer;
        }
    }
    
    /**
     * Проверка дали е активиран CAPTCHA
     */
    private function is_captcha_enabled() {
        $options = get_option('pc_comments_options', array());
        return isset($options['captcha_enabled']) ? $options['captcha_enabled'] : true;
    }
    
    /**
     * Зареждане на CAPTCHA скриптове
     */
    public function enqueue_captcha_scripts() {
        $options = get_option('pc_comments_options', array());
        $captcha_type = isset($options['captcha_type']) ? $options['captcha_type'] : 'simple';
        
        if ($captcha_type === 'recaptcha' && !empty($options['recaptcha_site_key'])) {
            wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true);
        }
    }
    
    /**
     * Получаване на IP адрес на потребителя
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Известия в администрацията
     */
    public function admin_notices() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        $pending_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name WHERE status = 'pending'"
        );
        
        if ($pending_count > 0) {
            echo '<div class="notice notice-info">';
            echo '<p>';
            printf(
                _n(
                    'Имате %d ново мнение за парфюм чакащо одобрение. <a href="%s">Прегледайте</a>',
                    'Имате %d нови мнения за парфюми чакащи одобрение. <a href="%s">Прегледайте</a>',
                    $pending_count,
                    'parfume-catalog'
                ),
                $pending_count,
                admin_url('admin.php?page=pc-reviews')
            );
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Шорткод за ревюта
     */
    public function reviews_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
            'show_form' => 'true',
            'limit' => 10
        ), $atts);
        
        ob_start();
        
        if ($atts['show_form'] === 'true') {
            $this->display_review_form($atts['post_id']);
        }
        
        $this->display_reviews_list($atts['post_id'], $atts['limit']);
        
        return ob_get_clean();
    }
    
    /**
     * Получаване на всички ревюта за админ панела
     */
    public function get_all_reviews($status = '', $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        $where = '';
        if (!empty($status)) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.post_title 
             FROM $table_name r 
             LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID 
             $where 
             ORDER BY r.created_at DESC 
             LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }
    
    /**
     * Получаване на броя ревюта по статус
     */
    public function get_reviews_count_by_status() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pc_reviews';
        
        return $wpdb->get_results(
            "SELECT status, COUNT(*) as count 
             FROM $table_name 
             GROUP BY status"
        );
    }
}