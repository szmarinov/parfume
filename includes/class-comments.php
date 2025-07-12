<?php
namespace Parfume_Reviews;

class Comments {
    
    public function __construct() {
        // Регистрира custom comment type
        add_action('init', array($this, 'register_comment_type'));
        
        // AJAX handlers
        add_action('wp_ajax_submit_parfume_review', array($this, 'submit_review'));
        add_action('wp_ajax_nopriv_submit_parfume_review', array($this, 'submit_review'));
        
        // Admin menu и management
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Email notifications
        add_action('parfume_review_submitted', array($this, 'send_admin_notification'));
        
        // Schema.org integration
        add_filter('parfume_reviews_schema_rating', array($this, 'add_reviews_to_schema'), 10, 2);
        
        // Settings integration
        add_action('admin_init', array($this, 'register_comments_settings'));
        
        // Rate limiting
        add_action('wp_ajax_check_review_limit', array($this, 'check_review_limit'));
        add_action('wp_ajax_nopriv_check_review_limit', array($this, 'check_review_limit'));
    }
    
    public function register_comment_type() {
        // Използваме WordPress comment system, но с custom meta
        // Не е нужна отделна таблица
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Parfume Reviews', 'parfume-reviews'),
            __('User Reviews', 'parfume-reviews'),
            'moderate_comments',
            'parfume-user-reviews',
            array($this, 'render_admin_page')
        );
    }
    
    public function register_comments_settings() {
        add_settings_section(
            'parfume_reviews_comments_section',
            __('User Reviews Settings', 'parfume-reviews'),
            array($this, 'render_comments_section'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'enable_reviews',
            __('Enable User Reviews', 'parfume-reviews'),
            array($this, 'render_enable_reviews_field'),
            'parfume-reviews-settings',
            'parfume_reviews_comments_section'
        );
        
        add_settings_field(
            'require_approval',
            __('Require Approval', 'parfume-reviews'),
            array($this, 'render_require_approval_field'),
            'parfume-reviews-settings',
            'parfume_reviews_comments_section'
        );
        
        add_settings_field(
            'enable_captcha',
            __('Enable CAPTCHA', 'parfume-reviews'),
            array($this, 'render_enable_captcha_field'),
            'parfume-reviews-settings',
            'parfume_reviews_comments_section'
        );
        
        add_settings_field(
            'captcha_question',
            __('CAPTCHA Question', 'parfume-reviews'),
            array($this, 'render_captcha_question_field'),
            'parfume-reviews-settings',
            'parfume_reviews_comments_section'
        );
        
        add_settings_field(
            'captcha_answer',
            __('CAPTCHA Answer', 'parfume-reviews'),
            array($this, 'render_captcha_answer_field'),
            'parfume-reviews-settings',
            'parfume_reviews_comments_section'
        );
        
        add_settings_field(
            'blocked_words',
            __('Blocked Words/Domains', 'parfume-reviews'),
            array($this, 'render_blocked_words_field'),
            'parfume-reviews-settings',
            'parfume_reviews_comments_section'
        );
        
        add_settings_field(
            'review_limit_per_ip',
            __('Reviews Per IP Limit', 'parfume-reviews'),
            array($this, 'render_review_limit_field'),
            'parfume-reviews-settings',
            'parfume_reviews_comments_section'
        );
    }
    
    public function render_comments_section() {
        echo '<p>' . __('Configure user reviews and rating system for perfumes.', 'parfume-reviews') . '</p>';
    }
    
    public function render_enable_reviews_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['enable_reviews']) ? $settings['enable_reviews'] : '1';
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[enable_reviews]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Allow users to submit reviews and ratings', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_require_approval_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['require_approval']) ? $settings['require_approval'] : '1';
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[require_approval]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Require admin approval before reviews are published', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_enable_captcha_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['enable_captcha']) ? $settings['enable_captcha'] : '1';
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[enable_captcha]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable CAPTCHA protection', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_captcha_question_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['captcha_question']) ? $settings['captcha_question'] : 'Колко е 2 + 3?';
        ?>
        <input type="text" name="parfume_reviews_settings[captcha_question]" value="<?php echo esc_attr($value); ?>" class="large-text">
        <p class="description"><?php _e('Simple math question to prevent spam.', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_captcha_answer_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['captcha_answer']) ? $settings['captcha_answer'] : '5';
        ?>
        <input type="text" name="parfume_reviews_settings[captcha_answer]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Correct answer to the CAPTCHA question.', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_blocked_words_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['blocked_words']) ? $settings['blocked_words'] : '';
        ?>
        <textarea name="parfume_reviews_settings[blocked_words]" rows="5" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php _e('One word/domain per line. Reviews containing these will be automatically blocked.', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_review_limit_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['review_limit_per_ip']) ? $settings['review_limit_per_ip'] : '1';
        ?>
        <input type="number" name="parfume_reviews_settings[review_limit_per_ip]" value="<?php echo esc_attr($value); ?>" min="1" max="10" step="1" class="small-text">
        <p class="description"><?php _e('Maximum reviews per product per IP address.', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume')) {
            wp_enqueue_script(
                'parfume-reviews-comments',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comments.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-comments', 'parfumeComments', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-comments-nonce'),
                'strings' => array(
                    'submitting' => __('Submitting...', 'parfume-reviews'),
                    'success' => __('Review submitted successfully!', 'parfume-reviews'),
                    'error' => __('Error submitting review. Please try again.', 'parfume-reviews'),
                    'rating_required' => __('Please select a rating.', 'parfume-reviews'),
                    'text_required' => __('Please write a review.', 'parfume-reviews'),
                    'captcha_required' => __('Please answer the security question.', 'parfume-reviews'),
                ),
            ));
            
            wp_enqueue_style(
                'parfume-reviews-comments',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comments.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'parfume-user-reviews') !== false) {
            wp_enqueue_script(
                'parfume-comments-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comments-admin.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-comments-admin', 'parfumeCommentsAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-comments-admin-nonce'),
            ));
        }
    }
    
    public function submit_review() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume-comments-nonce')) {
            wp_send_json_error(__('Security check failed.', 'parfume-reviews'));
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        
        // Check if reviews are enabled
        if (empty($settings['enable_reviews'])) {
            wp_send_json_error(__('Reviews are currently disabled.', 'parfume-reviews'));
        }
        
        // Validate input
        $post_id = intval($_POST['post_id']);
        $rating = intval($_POST['rating']);
        $name = sanitize_text_field($_POST['name']);
        $review_text = sanitize_textarea_field($_POST['review_text']);
        $captcha_answer = sanitize_text_field($_POST['captcha_answer']);
        
        // Validation
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            wp_send_json_error(__('Invalid product.', 'parfume-reviews'));
        }
        
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(__('Please select a rating between 1 and 5 stars.', 'parfume-reviews'));
        }
        
        if (empty($review_text) || strlen($review_text) < 10) {
            wp_send_json_error(__('Please write a review (at least 10 characters).', 'parfume-reviews'));
        }
        
        // CAPTCHA check
        if (!empty($settings['enable_captcha'])) {
            $correct_answer = isset($settings['captcha_answer']) ? $settings['captcha_answer'] : '5';
            if (trim($captcha_answer) !== trim($correct_answer)) {
                wp_send_json_error(__('Incorrect CAPTCHA answer.', 'parfume-reviews'));
            }
        }
        
        // Check for blocked words
        if (!empty($settings['blocked_words'])) {
            $blocked_words = explode("\n", $settings['blocked_words']);
            $blocked_words = array_map('trim', $blocked_words);
            
            foreach ($blocked_words as $blocked_word) {
                if (empty($blocked_word)) continue;
                
                if (stripos($review_text, $blocked_word) !== false || 
                    stripos($name, $blocked_word) !== false) {
                    wp_send_json_error(__('Your review contains blocked content.', 'parfume-reviews'));
                }
            }
        }
        
        // Check rate limiting
        $user_ip = $this->get_user_ip();
        $limit = isset($settings['review_limit_per_ip']) ? intval($settings['review_limit_per_ip']) : 1;
        
        if (!$this->check_rate_limit($post_id, $user_ip, $limit)) {
            wp_send_json_error(__('You have already submitted the maximum number of reviews for this product.', 'parfume-reviews'));
        }
        
        // Default name if empty
        if (empty($name)) {
            $name = __('Anonymous', 'parfume-reviews');
        }
        
        // Create comment
        $comment_data = array(
            'comment_post_ID' => $post_id,
            'comment_author' => $name,
            'comment_content' => $review_text,
            'comment_author_IP' => $user_ip,
            'comment_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'comment_type' => 'parfume_review',
            'comment_approved' => !empty($settings['require_approval']) ? 0 : 1,
        );
        
        $comment_id = wp_insert_comment($comment_data);
        
        if ($comment_id) {
            // Add rating meta
            add_comment_meta($comment_id, 'parfume_rating', $rating);
            add_comment_meta($comment_id, 'review_verified', 0);
            
            // Send notification
            if (!empty($settings['require_approval'])) {
                do_action('parfume_review_submitted', $comment_id, $post_id);
            }
            
            // Update average rating
            $this->update_average_rating($post_id);
            
            $message = !empty($settings['require_approval']) ? 
                __('Thank you! Your review is pending approval.', 'parfume-reviews') :
                __('Thank you for your review!', 'parfume-reviews');
                
            wp_send_json_success(array(
                'message' => $message,
                'comment_id' => $comment_id,
                'requires_approval' => !empty($settings['require_approval'])
            ));
            
        } else {
            wp_send_json_error(__('Error saving review. Please try again.', 'parfume-reviews'));
        }
    }
    
    private function get_user_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    private function check_rate_limit($post_id, $ip, $limit) {
        $existing_reviews = get_comments(array(
            'post_id' => $post_id,
            'author_IP' => $ip,
            'type' => 'parfume_review',
            'count' => true,
        ));
        
        return $existing_reviews < $limit;
    }
    
    public function check_review_limit() {
        if (!wp_verify_nonce($_POST['nonce'], 'parfume-comments-nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $post_id = intval($_POST['post_id']);
        $settings = get_option('parfume_reviews_settings', array());
        $limit = isset($settings['review_limit_per_ip']) ? intval($settings['review_limit_per_ip']) : 1;
        $user_ip = $this->get_user_ip();
        
        $can_review = $this->check_rate_limit($post_id, $user_ip, $limit);
        
        wp_send_json_success(array(
            'can_review' => $can_review,
            'limit' => $limit
        ));
    }
    
    private function update_average_rating($post_id) {
        $reviews = get_comments(array(
            'post_id' => $post_id,
            'type' => 'parfume_review',
            'status' => 'approve',
            'meta_query' => array(
                array(
                    'key' => 'parfume_rating',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (empty($reviews)) {
            delete_post_meta($post_id, '_parfume_average_rating');
            delete_post_meta($post_id, '_parfume_review_count');
            return;
        }
        
        $total_rating = 0;
        $count = 0;
        
        foreach ($reviews as $review) {
            $rating = get_comment_meta($review->comment_ID, 'parfume_rating', true);
            if ($rating && is_numeric($rating)) {
                $total_rating += floatval($rating);
                $count++;
            }
        }
        
        if ($count > 0) {
            $average = $total_rating / $count;
            update_post_meta($post_id, '_parfume_average_rating', $average);
            update_post_meta($post_id, '_parfume_review_count', $count);
        }
    }
    
    public function send_admin_notification($comment_id, $post_id) {
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!empty($settings['email_notifications'])) {
            $admin_email = get_option('admin_email');
            $post_title = get_the_title($post_id);
            $post_url = get_permalink($post_id);
            $admin_url = admin_url('edit.php?post_type=parfume&page=parfume-user-reviews');
            
            $subject = sprintf(__('New review pending approval - %s', 'parfume-reviews'), $post_title);
            
            $message = sprintf(
                __("A new review has been submitted for: %s\n\nView the review: %s\n\nView product: %s", 'parfume-reviews'),
                $post_title,
                $admin_url,
                $post_url
            );
            
            wp_mail($admin_email, $subject, $message);
        }
    }
    
    public function add_reviews_to_schema($schema, $post_id) {
        $average_rating = get_post_meta($post_id, '_parfume_average_rating', true);
        $review_count = get_post_meta($post_id, '_parfume_review_count', true);
        
        if ($average_rating && $review_count) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($average_rating, 1),
                'reviewCount' => intval($review_count),
                'bestRating' => '5',
                'worstRating' => '1'
            );
        }
        
        return $schema;
    }
    
    public function render_admin_page() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'approve':
                $this->handle_approve_review();
                break;
            case 'unapprove':
                $this->handle_unapprove_review();
                break;
            case 'delete':
                $this->handle_delete_review();
                break;
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('User Reviews Management', 'parfume-reviews'); ?></h1>
            
            <?php $this->render_review_stats(); ?>
            <?php $this->render_reviews_table(); ?>
        </div>
        <?php
    }
    
    private function render_review_stats() {
        $pending_count = get_comments(array(
            'type' => 'parfume_review',
            'status' => 'hold',
            'count' => true
        ));
        
        $approved_count = get_comments(array(
            'type' => 'parfume_review',
            'status' => 'approve',
            'count' => true
        ));
        
        $total_count = $pending_count + $approved_count;
        
        ?>
        <div class="review-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
            <div class="stat-box" style="background: #fff3cd; padding: 20px; border-radius: 5px; border-left: 4px solid #ffc107;">
                <h3><?php _e('Pending Approval', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #856404;"><?php echo $pending_count; ?></span>
            </div>
            
            <div class="stat-box" style="background: #d4edda; padding: 20px; border-radius: 5px; border-left: 4px solid #28a745;">
                <h3><?php _e('Approved', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #155724;"><?php echo $approved_count; ?></span>
            </div>
            
            <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #6c757d;">
                <h3><?php _e('Total Reviews', 'parfume-reviews'); ?></h3>
                <span style="font-size: 2em; font-weight: bold; color: #495057;"><?php echo $total_count; ?></span>
            </div>
        </div>
        <?php
    }
    
    private function render_reviews_table() {
        $status_filter = $_GET['status'] ?? 'all';
        $paged = $_GET['paged'] ?? 1;
        
        $args = array(
            'type' => 'parfume_review',
            'number' => 20,
            'offset' => ($paged - 1) * 20,
            'orderby' => 'comment_date',
            'order' => 'DESC'
        );
        
        if ($status_filter !== 'all') {
            $args['status'] = $status_filter;
        }
        
        $reviews = get_comments($args);
        $total_reviews = get_comments(array_merge($args, array('count' => true, 'number' => '', 'offset' => '')));
        
        ?>
        <div class="reviews-filters">
            <a href="<?php echo add_query_arg('status', 'all'); ?>" class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>">
                <?php _e('All', 'parfume-reviews'); ?>
            </a> |
            <a href="<?php echo add_query_arg('status', 'hold'); ?>" class="<?php echo $status_filter === 'hold' ? 'current' : ''; ?>">
                <?php _e('Pending', 'parfume-reviews'); ?>
            </a> |
            <a href="<?php echo add_query_arg('status', 'approve'); ?>" class="<?php echo $status_filter === 'approve' ? 'current' : ''; ?>">
                <?php _e('Approved', 'parfume-reviews'); ?>
            </a>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Reviewer', 'parfume-reviews'); ?></th>
                    <th><?php _e('Product', 'parfume-reviews'); ?></th>
                    <th><?php _e('Rating', 'parfume-reviews'); ?></th>
                    <th><?php _e('Review', 'parfume-reviews'); ?></th>
                    <th><?php _e('Date', 'parfume-reviews'); ?></th>
                    <th><?php _e('Status', 'parfume-reviews'); ?></th>
                    <th><?php _e('Actions', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="7"><?php _e('No reviews found.', 'parfume-reviews'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php
                        $rating = get_comment_meta($review->comment_ID, 'parfume_rating', true);
                        $post_title = get_the_title($review->comment_post_ID);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($review->comment_author); ?></strong><br>
                                <small><?php echo esc_html($review->comment_author_IP); ?></small>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($review->comment_post_ID); ?>">
                                    <?php echo esc_html($post_title); ?>
                                </a>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <small>(<?php echo $rating; ?>/5)</small>
                            </td>
                            <td>
                                <div class="review-content">
                                    <?php echo wp_trim_words(esc_html($review->comment_content), 15); ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($review->comment_date); ?></td>
                            <td>
                                <?php if ($review->comment_approved == '1'): ?>
                                    <span class="status-approved"><?php _e('Approved', 'parfume-reviews'); ?></span>
                                <?php else: ?>
                                    <span class="status-pending"><?php _e('Pending', 'parfume-reviews'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($review->comment_approved != '1'): ?>
                                    <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'approve', 'comment_id' => $review->comment_ID)), 'approve_review_' . $review->comment_ID); ?>" class="button button-small">
                                        <?php _e('Approve', 'parfume-reviews'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'unapprove', 'comment_id' => $review->comment_ID)), 'unapprove_review_' . $review->comment_ID); ?>" class="button button-small">
                                        <?php _e('Unapprove', 'parfume-reviews'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete', 'comment_id' => $review->comment_ID)), 'delete_review_' . $review->comment_ID); ?>" 
                                   class="button button-small button-link-delete" 
                                   onclick="return confirm('<?php _e('Are you sure you want to delete this review?', 'parfume-reviews'); ?>')">
                                    <?php _e('Delete', 'parfume-reviews'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php
        // Pagination
        $total_pages = ceil($total_reviews / 20);
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $paged
            ));
            echo '</div></div>';
        }
    }
    
    public function handle_admin_actions() {
        if (isset($_GET['action']) && isset($_GET['comment_id'])) {
            $comment_id = intval($_GET['comment_id']);
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'approve':
                    if (wp_verify_nonce($_GET['_wpnonce'], 'approve_review_' . $comment_id)) {
                        wp_set_comment_status($comment_id, 'approve');
                        $comment = get_comment($comment_id);
                        if ($comment) {
                            $this->update_average_rating($comment->comment_post_ID);
                        }
                        wp_redirect(add_query_arg('message', 'approved', remove_query_arg(array('action', 'comment_id', '_wpnonce'))));
                        exit;
                    }
                    break;
                    
                case 'unapprove':
                    if (wp_verify_nonce($_GET['_wpnonce'], 'unapprove_review_' . $comment_id)) {
                        wp_set_comment_status($comment_id, 'hold');
                        $comment = get_comment($comment_id);
                        if ($comment) {
                            $this->update_average_rating($comment->comment_post_ID);
                        }
                        wp_redirect(add_query_arg('message', 'unapproved', remove_query_arg(array('action', 'comment_id', '_wpnonce'))));
                        exit;
                    }
                    break;
                    
                case 'delete':
                    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_review_' . $comment_id)) {
                        $comment = get_comment($comment_id);
                        $post_id = $comment ? $comment->comment_post_ID : null;
                        wp_delete_comment($comment_id, true);
                        if ($post_id) {
                            $this->update_average_rating($post_id);
                        }
                        wp_redirect(add_query_arg('message', 'deleted', remove_query_arg(array('action', 'comment_id', '_wpnonce'))));
                        exit;
                    }
                    break;
            }
        }
    }
    
    /**
     * Render reviews section for single parfume page
     */
    public static function render_reviews_section($post_id) {
        $settings = get_option('parfume_reviews_settings', array());
        
        if (empty($settings['enable_reviews'])) {
            return '';
        }
        
        $reviews = get_comments(array(
            'post_id' => $post_id,
            'type' => 'parfume_review',
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'DESC'
        ));
        
        $average_rating = get_post_meta($post_id, '_parfume_average_rating', true);
        $review_count = get_post_meta($post_id, '_parfume_review_count', true);
        
        ob_start();
        ?>
        <div class="parfume-reviews-section" id="parfume-reviews">
            <h3><?php _e('User Reviews and Rating', 'parfume-reviews'); ?></h3>
            
            <?php if ($average_rating && $review_count): ?>
                <div class="reviews-summary">
                    <div class="average-rating">
                        <span class="rating-number"><?php echo number_format($average_rating, 1); ?></span>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= round($average_rating) ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="review-count"><?php printf(_n('%d review', '%d reviews', $review_count, 'parfume-reviews'), $review_count); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Review Form -->
            <div class="review-form-container">
                <h4><?php _e('Leave a Review', 'parfume-reviews'); ?></h4>
                
                <form id="parfume-review-form" class="parfume-review-form">
                    <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
                    
                    <div class="form-group">
                        <label for="reviewer-name"><?php _e('Your Name (optional)', 'parfume-reviews'); ?></label>
                        <input type="text" id="reviewer-name" name="name" placeholder="<?php esc_attr_e('Anonymous', 'parfume-reviews'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="review-rating"><?php _e('Rating *', 'parfume-reviews'); ?></label>
                        <div class="rating-input" id="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star-input" data-rating="<?php echo $i; ?>">☆</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" id="selected-rating" name="rating" value="">
                    </div>
                    
                    <div class="form-group">
                        <label for="review-text"><?php _e('Your Review *', 'parfume-reviews'); ?></label>
                        <textarea id="review-text" name="review_text" rows="5" placeholder="<?php esc_attr_e('Share your experience with this perfume...', 'parfume-reviews'); ?>" required></textarea>
                    </div>
                    
                    <?php if (!empty($settings['enable_captcha'])): ?>
                        <div class="form-group">
                            <label for="captcha-answer">
                                <?php echo esc_html($settings['captcha_question'] ?? 'Колко е 2 + 3?'); ?> *
                            </label>
                            <input type="text" id="captcha-answer" name="captcha_answer" required>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <button type="submit" class="submit-review-btn">
                            <?php _e('Submit Review', 'parfume-reviews'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Existing Reviews -->
            <div class="existing-reviews">
                <?php if (empty($reviews)): ?>
                    <p class="no-reviews"><?php _e('No reviews yet. Be the first to leave a review!', 'parfume-reviews'); ?></p>
                <?php else: ?>
                    <h4><?php _e('Reviews', 'parfume-reviews'); ?></h4>
                    
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $rating = get_comment_meta($review->comment_ID, 'parfume_rating', true);
                            ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <span class="reviewer-name"><?php echo esc_html($review->comment_author); ?></span>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="review-date"><?php echo esc_html(mysql2date(get_option('date_format'), $review->comment_date)); ?></span>
                                </div>
                                <div class="review-content">
                                    <?php echo wpautop(esc_html($review->comment_content)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}