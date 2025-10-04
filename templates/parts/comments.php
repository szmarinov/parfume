<?php
/**
 * Template Part: Comments
 * Displays comment form and list for parfumes
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

// Get average rating
$rating_data = Parfume_Comments_Handler::get_average_rating($post->ID);

// Get approved comments
$comments = get_comments([
    'post_id' => $post->ID,
    'type' => 'parfume_review',
    'status' => 'approve',
    'orderby' => 'comment_date',
    'order' => 'DESC'
]);

// Generate simple math captcha
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$captcha_answer = $num1 + $num2;
?>

<section class="parfume-comments-section" id="comments">
    <div class="comments-header">
        <h2 class="comments-title">Потребителски мнения и оценка</h2>
        
        <?php if ($rating_data['count'] > 0) : ?>
            <div class="average-rating">
                <div class="rating-number"><?php echo esc_html($rating_data['average']); ?></div>
                <div class="rating-details">
                    <div class="rating-stars-display">
                        <?php
                        $full_stars = floor($rating_data['average']);
                        $half_star = ($rating_data['average'] - $full_stars) >= 0.5;
                        
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $full_stars) {
                                echo '<span class="star filled">★</span>';
                            } elseif ($i == $full_stars + 1 && $half_star) {
                                echo '<span class="star half">★</span>';
                            } else {
                                echo '<span class="star">☆</span>';
                            }
                        }
                        ?>
                    </div>
                    <div class="rating-count">
                        Базирано на <?php echo esc_html($rating_data['count']); ?> 
                        <?php echo $rating_data['count'] === 1 ? 'мнение' : 'мнения'; ?>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <p class="no-ratings">Все още няма оценки</p>
        <?php endif; ?>
    </div>
    
    <!-- Comment Form -->
    <div class="comment-form-wrapper">
        <h3>Напишете мнение</h3>
        
        <form id="parfume-comment-form" class="parfume-comment-form">
            <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">
            <input type="hidden" name="captcha_expected" value="<?php echo esc_attr($captcha_answer); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="comment-name">
                        Име <span class="optional">(Оставете празно за "Анонимен")</span>
                    </label>
                    <input type="text" id="comment-name" name="name" placeholder="Вашето име">
                </div>
                
                <div class="form-group">
                    <label for="comment-email">
                        Имейл <span class="required">*</span>
                    </label>
                    <input type="email" id="comment-email" name="email" required placeholder="your@email.com">
                </div>
            </div>
            
            <div class="form-group rating-group">
                <label>
                    Оценка <span class="required">*</span>
                </label>
                <div class="rating-input">
                    <input type="radio" id="rating-5" name="rating" value="5" required>
                    <label for="rating-5" title="5 звезди">★</label>
                    
                    <input type="radio" id="rating-4" name="rating" value="4">
                    <label for="rating-4" title="4 звезди">★</label>
                    
                    <input type="radio" id="rating-3" name="rating" value="3">
                    <label for="rating-3" title="3 звезди">★</label>
                    
                    <input type="radio" id="rating-2" name="rating" value="2">
                    <label for="rating-2" title="2 звезди">★</label>
                    
                    <input type="radio" id="rating-1" name="rating" value="1">
                    <label for="rating-1" title="1 звезда">★</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="comment-text">
                    Коментар <span class="required">*</span>
                </label>
                <textarea id="comment-text" name="comment" rows="5" required placeholder="Напишете вашето мнение..."></textarea>
            </div>
            
            <?php if (get_option('parfume_comments_captcha_enabled', true)) : ?>
            <div class="form-group captcha-group">
                <label for="captcha-answer">
                    Колко е <?php echo $num1; ?> + <?php echo $num2; ?>? <span class="required">*</span>
                </label>
                <input type="number" id="captcha-answer" name="captcha_answer" required placeholder="Вашият отговор">
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <button type="submit" class="submit-button">
                    <span class="button-text">Публикувай мнение</span>
                    <span class="button-loader" style="display: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
                            <path d="M12 2 A10 10 0 0 1 22 12" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round">
                                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                            </path>
                        </svg>
                    </span>
                </button>
            </div>
            
            <div class="form-message" style="display: none;"></div>
        </form>
    </div>
    
    <!-- Comments List -->
    <div class="comments-list">
        <h3>
            Мнения (<?php echo count($comments); ?>)
        </h3>
        
        <?php if (empty($comments)) : ?>
            <p class="no-comments">Все още няма публикувани мнения. Бъдете първи!</p>
        <?php else : ?>
            <div class="comments-wrapper">
                <?php foreach ($comments as $comment) : 
                    $rating = get_comment_meta($comment->comment_ID, 'parfume_rating', true);
                ?>
                    <article class="comment-item" id="comment-<?php echo $comment->comment_ID; ?>">
                        <div class="comment-header">
                            <div class="comment-author">
                                <div class="author-avatar">
                                    <?php echo get_avatar($comment, 48); ?>
                                </div>
                                <div class="author-info">
                                    <span class="author-name"><?php echo esc_html($comment->comment_author); ?></span>
                                    <span class="comment-date"><?php echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')); ?> назад</span>
                                </div>
                            </div>
                            
                            <?php if ($rating) : ?>
                                <div class="comment-rating">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="comment-content">
                            <?php echo wpautop(esc_html($comment->comment_content)); ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.parfume-comments-section {
    margin: 40px 0;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.comments-header {
    margin-bottom: 40px;
    text-align: center;
}

.comments-title {
    font-size: 28px;
    margin-bottom: 20px;
}

.average-rating {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
}

.rating-number {
    font-size: 48px;
    font-weight: bold;
    color: #ffc107;
}

.rating-stars-display {
    font-size: 24px;
    color: #ffc107;
    margin-bottom: 5px;
}

.rating-stars-display .star.filled {
    color: #ffc107;
}

.rating-stars-display .star.half {
    background: linear-gradient(90deg, #ffc107 50%, #e0e0e0 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.rating-stars-display .star {
    color: #e0e0e0;
}

.rating-count {
    font-size: 14px;
    color: #666;
}

.no-ratings {
    color: #999;
    font-style: italic;
}

.comment-form-wrapper {
    margin-bottom: 50px;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 8px;
}

.comment-form-wrapper h3 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 22px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.required {
    color: #dc3545;
}

.optional {
    font-weight: normal;
    font-size: 13px;
    color: #666;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 15px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4a90e2;
}

.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.rating-input input {
    display: none;
}

.rating-input label {
    font-size: 32px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease;
}

.rating-input input:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label {
    color: #ffc107;
}

.submit-button {
    padding: 12px 30px;
    background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(74, 144, 226, 0.4);
}

.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-message {
    margin-top: 15px;
    padding: 12px 15px;
    border-radius: 4px;
    font-size: 14px;
}

.form-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.form-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.comments-list h3 {
    font-size: 22px;
    margin-bottom: 25px;
}

.no-comments {
    text-align: center;
    color: #999;
    padding: 40px;
    font-style: italic;
}

.comments-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.comment-item {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #4a90e2;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.comment-author {
    display: flex;
    gap: 12px;
}

.author-avatar img {
    border-radius: 50%;
}

.author-name {
    display: block;
    font-weight: 600;
    color: #333;
}

.comment-date {
    display: block;
    font-size: 13px;
    color: #666;
}

.comment-rating {
    font-size: 18px;
}

.comment-rating .star.filled {
    color: #ffc107;
}

.comment-rating .star {
    color: #e0e0e0;
}

.comment-content {
    color: #333;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .parfume-comments-section {
        padding: 20px 15px;
    }
    
    .comments-title {
        font-size: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .comment-form-wrapper {
        padding: 20px 15px;
    }
    
    .average-rating {
        flex-direction: column;
        gap: 10px;
    }
    
    .comment-header {
        flex-direction: column;
        gap: 10px;
    }
}
</style>