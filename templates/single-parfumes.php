<?php
/**
 * Single Parfume Template
 * 
 * Template for displaying individual parfume posts
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get parfume data
$post_id = get_the_ID();
$parfume_basic = Parfume_Meta_Basic::get_parfume_info($post_id);
$parfume_notes = Parfume_Meta_Notes::get_notes_composition($post_id);
$parfume_stores = Parfume_Meta_Stores::get_formatted_stores($post_id);
$parfume_stats = Parfume_Meta_Stats::get_public_stats($post_id);

// Get taxonomies
$parfume_type = get_the_terms($post_id, 'parfume_type');
$parfume_vid = get_the_terms($post_id, 'parfume_vid');
$parfume_marki = get_the_terms($post_id, 'parfume_marki');
$parfume_season = get_the_terms($post_id, 'parfume_season');
$parfume_intensity = get_the_terms($post_id, 'parfume_intensity');

// Settings
$comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
$comments_settings = Parfume_Admin_Comments::get_comments_settings();
?>

<div class="parfume-single-container">
    <div class="parfume-content-wrapper">
        <!-- Left Column (70%) -->
        <div class="parfume-left-column">
            <div class="parfume-header">
                <div class="parfume-image">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('large', array('class' => 'parfume-featured-image')); ?>
                    <?php else: ?>
                        <div class="parfume-placeholder-image">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="parfume-header-info">
                    <h1 class="parfume-title"><?php the_title(); ?></h1>
                    
                    <div class="parfume-meta">
                        <?php if ($parfume_vid): ?>
                            <div class="parfume-type">
                                <strong><?php _e('Вид:', 'parfume-catalog'); ?></strong>
                                <?php echo esc_html($parfume_vid[0]->name); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($parfume_marki): ?>
                            <div class="parfume-brand">
                                <strong><?php _e('Марка:', 'parfume-catalog'); ?></strong>
                                <a href="<?php echo get_term_link($parfume_marki[0]); ?>">
                                    <?php echo esc_html($parfume_marki[0]->name); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($comparison_settings['enabled']): ?>
                            <div class="parfume-comparison">
                                <button type="button" 
                                        class="comparison-btn" 
                                        data-parfume-id="<?php echo $post_id; ?>"
                                        data-parfume-title="<?php echo esc_attr(get_the_title()); ?>"
                                        data-parfume-image="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')); ?>">
                                    <span class="comparison-icon">⚖️</span>
                                    <span class="comparison-text"><?php echo esc_html($comparison_settings['texts']['add']); ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($parfume_notes['main_notes'])): ?>
                        <div class="parfume-main-notes">
                            <strong><?php _e('Основни нотки:', 'parfume-catalog'); ?></strong>
                            <div class="main-notes-list">
                                <?php
                                $main_notes = Parfume_Meta_Notes::get_formatted_notes($parfume_notes['main_notes']);
                                foreach ($main_notes as $note):
                                ?>
                                    <span class="main-note">
                                        <?php if ($note['icon_url']): ?>
                                            <img src="<?php echo esc_url($note['icon_url']); ?>" alt="" class="note-icon">
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($note['link']); ?>">
                                            <?php echo esc_html($note['name']); ?>
                                        </a>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Suitable For Icons -->
            <?php if (!empty($parfume_basic['suitable_for'])): ?>
                <div class="parfume-suitable-for">
                    <div class="suitable-icons">
                        <?php
                        $suitable_labels = Parfume_Meta_Basic::get_suitable_for_labels();
                        $suitable_icons = array(
                            'spring' => '🌸',
                            'summer' => '☀️', 
                            'autumn' => '🍂',
                            'winter' => '❄️',
                            'day' => '🌅',
                            'night' => '🌙'
                        );
                        
                        foreach ($parfume_basic['suitable_for'] as $suitable):
                            if (isset($suitable_labels[$suitable])):
                        ?>
                            <div class="suitable-item" title="<?php echo esc_attr($suitable_labels[$suitable]); ?>">
                                <span class="suitable-icon"><?php echo $suitable_icons[$suitable] ?? '⭐'; ?></span>
                                <span class="suitable-label"><?php echo esc_html($suitable_labels[$suitable]); ?></span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Content -->
            <div class="parfume-content">
                <?php the_content(); ?>
            </div>
            
            <!-- Composition Section -->
            <?php if (Parfume_Meta_Notes::has_notes($post_id)): ?>
                <div class="parfume-composition">
                    <h3><?php _e('Състав', 'parfume-catalog'); ?></h3>
                    <div class="composition-pyramid">
                        <?php if (!empty($parfume_notes['top_notes'])): ?>
                            <div class="notes-section top-notes">
                                <h4><?php _e('Връхни нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php
                                    $top_notes = Parfume_Meta_Notes::get_formatted_notes($parfume_notes['top_notes']);
                                    foreach ($top_notes as $note):
                                    ?>
                                        <span class="note-item">
                                            <?php if ($note['icon_url']): ?>
                                                <img src="<?php echo esc_url($note['icon_url']); ?>" alt="" class="note-icon">
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($note['link']); ?>">
                                                <?php echo esc_html($note['name']); ?>
                                            </a>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($parfume_notes['heart_notes'])): ?>
                            <div class="notes-section heart-notes">
                                <h4><?php _e('Средни нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php
                                    $heart_notes = Parfume_Meta_Notes::get_formatted_notes($parfume_notes['heart_notes']);
                                    foreach ($heart_notes as $note):
                                    ?>
                                        <span class="note-item">
                                            <?php if ($note['icon_url']): ?>
                                                <img src="<?php echo esc_url($note['icon_url']); ?>" alt="" class="note-icon">
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($note['link']); ?>">
                                                <?php echo esc_html($note['name']); ?>
                                            </a>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($parfume_notes['base_notes'])): ?>
                            <div class="notes-section base-notes">
                                <h4><?php _e('Базови нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php
                                    $base_notes = Parfume_Meta_Notes::get_formatted_notes($parfume_notes['base_notes']);
                                    foreach ($base_notes as $note):
                                    ?>
                                        <span class="note-item">
                                            <?php if ($note['icon_url']): ?>
                                                <img src="<?php echo esc_url($note['icon_url']); ?>" alt="" class="note-icon">
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($note['link']); ?>">
                                                <?php echo esc_html($note['name']); ?>
                                            </a>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Fragrance Graphics -->
            <div class="parfume-graphics">
                <h3><?php _e('Графика на аромата', 'parfume-catalog'); ?></h3>
                <div class="graphics-grid">
                    <div class="graphics-column">
                        <h4><?php _e('ДЪЛГОТРАЙНОСТ', 'parfume-catalog'); ?></h4>
                        <div class="progress-bars">
                            <?php
                            $durability_labels = array(
                                1 => __('много слаб', 'parfume-catalog'),
                                2 => __('слаб', 'parfume-catalog'),
                                3 => __('умерен', 'parfume-catalog'),
                                4 => __('траен', 'parfume-catalog'),
                                5 => __('изключително траен', 'parfume-catalog')
                            );
                            
                            $durability = $parfume_basic['durability'] ?: 3;
                            for ($i = 1; $i <= 5; $i++):
                            ?>
                                <div class="progress-bar <?php echo $i <= $durability ? 'active' : ''; ?>">
                                    <span class="progress-label"><?php echo esc_html($durability_labels[$i]); ?></span>
                                    <div class="progress-fill"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="graphics-column">
                        <h4><?php _e('АРОМАТНА СЛЕДА', 'parfume-catalog'); ?></h4>
                        <div class="progress-bars">
                            <?php
                            $sillage_labels = array(
                                1 => __('слаба', 'parfume-catalog'),
                                2 => __('умерена', 'parfume-catalog'),
                                3 => __('силна', 'parfume-catalog'),
                                4 => __('огромна', 'parfume-catalog')
                            );
                            
                            $sillage = $parfume_basic['sillage'] ?: 3;
                            for ($i = 1; $i <= 4; $i++):
                            ?>
                                <div class="progress-bar <?php echo $i <= $sillage ? 'active' : ''; ?>">
                                    <span class="progress-label"><?php echo esc_html($sillage_labels[$i]); ?></span>
                                    <div class="progress-fill"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="graphics-grid">
                    <div class="graphics-column">
                        <h4><?php _e('ПОЛ', 'parfume-catalog'); ?></h4>
                        <div class="progress-bars">
                            <?php
                            $gender_labels = array(
                                1 => __('дамски', 'parfume-catalog'),
                                2 => __('мъжки', 'parfume-catalog'),
                                3 => __('унисекс', 'parfume-catalog'),
                                4 => __('по-млади', 'parfume-catalog'),
                                5 => __('по-зрели', 'parfume-catalog')
                            );
                            
                            // Determine gender based on taxonomies
                            $gender_score = 3; // Default unisex
                            if ($parfume_type) {
                                $type_name = strtolower($parfume_type[0]->name);
                                if (strpos($type_name, 'дамски') !== false) $gender_score = 1;
                                elseif (strpos($type_name, 'мъжки') !== false) $gender_score = 2;
                                elseif (strpos($type_name, 'младежки') !== false) $gender_score = 4;
                                elseif (strpos($type_name, 'възрастни') !== false) $gender_score = 5;
                            }
                            
                            for ($i = 1; $i <= 5; $i++):
                            ?>
                                <div class="progress-bar <?php echo $i <= $gender_score ? 'active' : ''; ?>">
                                    <span class="progress-label"><?php echo esc_html($gender_labels[$i]); ?></span>
                                    <div class="progress-fill"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="graphics-column">
                        <h4><?php _e('ЦЕНА', 'parfume-catalog'); ?></h4>
                        <div class="progress-bars">
                            <?php
                            $price_labels = array(
                                1 => __('евтин', 'parfume-catalog'),
                                2 => __('добра цена', 'parfume-catalog'),
                                3 => __('приемлива цена', 'parfume-catalog'),
                                4 => __('скъп', 'parfume-catalog'),
                                5 => __('прекалено скъп', 'parfume-catalog')
                            );
                            
                            $price_range = $parfume_basic['price_range'] ?: 3;
                            for ($i = 1; $i <= 5; $i++):
                            ?>
                                <div class="progress-bar <?php echo $i <= $price_range ? 'active' : ''; ?>">
                                    <span class="progress-label"><?php echo esc_html($price_labels[$i]); ?></span>
                                    <div class="progress-fill"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pros and Cons -->
            <?php if (!empty($parfume_basic['pros']) || !empty($parfume_basic['cons'])): ?>
                <div class="parfume-pros-cons">
                    <h3><?php _e('Предимства и недостатъци', 'parfume-catalog'); ?></h3>
                    <div class="pros-cons-grid">
                        <?php if (!empty($parfume_basic['pros'])): ?>
                            <div class="pros-column">
                                <h4><?php _e('Предимства', 'parfume-catalog'); ?></h4>
                                <ul class="pros-list">
                                    <?php foreach ($parfume_basic['pros'] as $pro): ?>
                                        <li class="pro-item">
                                            <span class="pro-icon">✅</span>
                                            <?php echo esc_html($pro); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($parfume_basic['cons'])): ?>
                            <div class="cons-column">
                                <h4><?php _e('Недостатъци', 'parfume-catalog'); ?></h4>
                                <ul class="cons-list">
                                    <?php foreach ($parfume_basic['cons'] as $con): ?>
                                        <li class="con-item">
                                            <span class="con-icon">❌</span>
                                            <?php echo esc_html($con); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Similar Fragrances -->
            <?php
            $similar_count = get_option('parfume_similar_count', 4);
            $similar_columns = get_option('parfume_similar_columns', 4);
            $similar_parfumes = $this->get_similar_parfumes($post_id, $similar_count);
            
            if (!empty($similar_parfumes)):
            ?>
                <div class="parfume-similar">
                    <h3><?php _e('Подобни аромати', 'parfume-catalog'); ?></h3>
                    <div class="similar-grid" style="grid-template-columns: repeat(<?php echo $similar_columns; ?>, 1fr);">
                        <?php foreach ($similar_parfumes as $similar): ?>
                            <div class="similar-item">
                                <a href="<?php echo get_permalink($similar->ID); ?>">
                                    <?php if (has_post_thumbnail($similar->ID)): ?>
                                        <?php echo get_the_post_thumbnail($similar->ID, 'medium', array('class' => 'similar-image')); ?>
                                    <?php else: ?>
                                        <div class="similar-placeholder">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="similar-title"><?php echo esc_html($similar->post_title); ?></h4>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Recently Viewed -->
            <?php
            $recent_count = get_option('parfume_recent_count', 4);
            $recent_columns = get_option('parfume_recent_columns', 4);
            ?>
            <div class="parfume-recent" id="recent-parfumes">
                <h3><?php _e('Наскоро разгледани', 'parfume-catalog'); ?></h3>
                <div class="recent-grid" style="grid-template-columns: repeat(<?php echo $recent_columns; ?>, 1fr);">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Same Brand -->
            <?php
            if ($parfume_marki):
                $brand_count = get_option('parfume_brand_count', 4);
                $brand_columns = get_option('parfume_brand_columns', 4);
                $brand_parfumes = $this->get_brand_parfumes($post_id, $parfume_marki[0]->term_id, $brand_count);
                
                if (!empty($brand_parfumes)):
            ?>
                <div class="parfume-brand">
                    <h3><?php printf(__('Други парфюми от %s', 'parfume-catalog'), esc_html($parfume_marki[0]->name)); ?></h3>
                    <div class="brand-grid" style="grid-template-columns: repeat(<?php echo $brand_columns; ?>, 1fr);">
                        <?php foreach ($brand_parfumes as $brand_parfume): ?>
                            <div class="brand-item">
                                <a href="<?php echo get_permalink($brand_parfume->ID); ?>">
                                    <?php if (has_post_thumbnail($brand_parfume->ID)): ?>
                                        <?php echo get_the_post_thumbnail($brand_parfume->ID, 'medium', array('class' => 'brand-image')); ?>
                                    <?php else: ?>
                                        <div class="brand-placeholder">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="brand-title"><?php echo esc_html($brand_parfume->post_title); ?></h4>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php 
                endif;
            endif; 
            ?>
            
            <!-- Comments Section -->
            <?php if ($comments_settings['enabled']): ?>
                <div class="parfume-comments">
                    <h3><?php _e('Потребителски мнения и оценка', 'parfume-catalog'); ?></h3>
                    
                    <!-- Comments Form -->
                    <div class="comments-form-section">
                        <form id="parfume-comment-form" class="comment-form">
                            <?php wp_nonce_field('parfume_comment_nonce', 'parfume_comment_nonce_field'); ?>
                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="comment-author"><?php _e('Име', 'parfume-catalog'); ?></label>
                                    <input type="text" 
                                           id="comment-author" 
                                           name="author_name" 
                                           placeholder="<?php _e('Вашето име (или оставете празно за Анонимен)', 'parfume-catalog'); ?>" 
                                           class="form-control" />
                                </div>
                                
                                <?php if ($comments_settings['require_email']): ?>
                                    <div class="form-group">
                                        <label for="comment-email"><?php _e('Имейл', 'parfume-catalog'); ?> *</label>
                                        <input type="email" 
                                               id="comment-email" 
                                               name="author_email" 
                                               required 
                                               class="form-control" />
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment-rating"><?php _e('Оценка', 'parfume-catalog'); ?> *</label>
                                <div class="rating-input">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" 
                                               id="rating-<?php echo $i; ?>" 
                                               name="rating" 
                                               value="<?php echo $i; ?>" 
                                               required />
                                        <label for="rating-<?php echo $i; ?>" class="star-label">⭐</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment-content"><?php _e('Вашето мнение', 'parfume-catalog'); ?> *</label>
                                <textarea id="comment-content" 
                                          name="content" 
                                          rows="4" 
                                          required 
                                          placeholder="<?php _e('Споделете вашето мнение за този парфюм...', 'parfume-catalog'); ?>" 
                                          class="form-control"></textarea>
                            </div>
                            
                            <?php if ($comments_settings['enable_captcha']): ?>
                                <div class="form-group">
                                    <label for="captcha-answer"><?php echo esc_html($comments_settings['captcha_question']); ?> *</label>
                                    <input type="text" 
                                           id="captcha-answer" 
                                           name="captcha_answer" 
                                           required 
                                           class="form-control" />
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-actions">
                                <button type="submit" class="submit-comment-btn">
                                    <?php _e('Публикувай мнение', 'parfume-catalog'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Comments List -->
                    <div class="comments-list" id="comments-list">
                        <?php $this->render_comments_list($post_id); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column (30%) - Stores -->
        <div class="parfume-right-column" id="stores-column">
            <?php if (!empty($parfume_stores)): ?>
                <div class="stores-section">
                    <h3><?php _e('Сравни цените', 'parfume-catalog'); ?></h3>
                    <p class="stores-intro">
                        <?php printf(__('Купи %s на най‑изгодната цена:', 'parfume-catalog'), get_the_title()); ?>
                    </p>
                    
                    <div class="stores-list">
                        <?php foreach ($parfume_stores as $store_id => $store): ?>
                            <?php $this->render_store_item($store); ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="stores-note">
                        <?php printf(__('Цените ни се актуализират на всеки %d ч.', 'parfume-catalog'), get_option('parfume_scraper_interval', 12)); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Add recent parfume to localStorage
?>
<script>
jQuery(document).ready(function($) {
    // Add current parfume to recently viewed
    var recentParfumes = JSON.parse(localStorage.getItem('recentParfumes') || '[]');
    var currentParfume = {
        id: <?php echo $post_id; ?>,
        title: <?php echo json_encode(get_the_title()); ?>,
        url: <?php echo json_encode(get_permalink()); ?>,
        image: <?php echo json_encode(get_the_post_thumbnail_url($post_id, 'thumbnail')); ?>,
        timestamp: Date.now()
    };
    
    // Remove if already exists
    recentParfumes = recentParfumes.filter(function(parfume) {
        return parfume.id !== currentParfume.id;
    });
    
    // Add to beginning
    recentParfumes.unshift(currentParfume);
    
    // Keep only last 10
    recentParfumes = recentParfumes.slice(0, 10);
    
    // Save back to localStorage
    localStorage.setItem('recentParfumes', JSON.stringify(recentParfumes));
    
    // Display recent parfumes (excluding current)
    var recentToShow = recentParfumes.filter(function(parfume) {
        return parfume.id !== currentParfume.id;
    }).slice(0, <?php echo $recent_count; ?>);
    
    if (recentToShow.length > 0) {
        var recentHtml = '';
        recentToShow.forEach(function(parfume) {
            recentHtml += '<div class="recent-item">' +
                '<a href="' + parfume.url + '">' +
                (parfume.image ? '<img src="' + parfume.image + '" alt="" class="recent-image">' : '<div class="recent-placeholder"><span class="dashicons dashicons-format-image"></span></div>') +
                '<h4 class="recent-title">' + parfume.title + '</h4>' +
                '</a>' +
                '</div>';
        });
        $('#recent-parfumes .recent-grid').html(recentHtml);
    } else {
        $('#recent-parfumes').hide();
    }
    
    // Comment form submission
    $('#parfume-comment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=parfume_submit_comment';
        
        var submitBtn = $('.submit-comment-btn');
        submitBtn.prop('disabled', true).text('<?php _e('Изпраща...', 'parfume-catalog'); ?>');
        
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
            if (response.success) {
                $('#parfume-comment-form')[0].reset();
                $('.rating-input input').prop('checked', false);
                
                if (response.data.message) {
                    alert(response.data.message);
                }
                
                // Reload comments if needed
                if (response.data.reload_comments) {
                    location.reload();
                }
            } else {
                alert(response.data.message || '<?php _e('Грешка при изпращане на коментара', 'parfume-catalog'); ?>');
            }
            
            submitBtn.prop('disabled', false).text('<?php _e('Публикувай мнение', 'parfume-catalog'); ?>');
        });
    });
    
    // Comparison functionality
    <?php if ($comparison_settings['enabled']): ?>
    $('.comparison-btn').on('click', function() {
        var parfumeId = $(this).data('parfume-id');
        var parfumeTitle = $(this).data('parfume-title');
        var parfumeImage = $(this).data('parfume-image');
        
        // Add to comparison (would integrate with comparison module)
        console.log('Adding to comparison:', parfumeId, parfumeTitle);
    });
    <?php endif; ?>
});
</script>

<?php
get_footer();

// Helper methods
function get_similar_parfumes($current_id, $count) {
    // Get parfumes with similar notes
    $current_notes = Parfume_Meta_Notes::get_all_parfume_notes($current_id);
    
    if (empty($current_notes)) {
        return array();
    }
    
    $args = array(
        'post_type' => 'parfumes',
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'post__not_in' => array($current_id),
        'tax_query' => array(
            array(
                'taxonomy' => 'parfume_notes',
                'field' => 'term_id',
                'terms' => $current_notes,
                'operator' => 'IN'
            )
        ),
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            )
        )
    );
    
    return get_posts($args);
}

function get_brand_parfumes($current_id, $brand_id, $count) {
    $args = array(
        'post_type' => 'parfumes',
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'post__not_in' => array($current_id),
        'tax_query' => array(
            array(
                'taxonomy' => 'parfume_marki',
                'field' => 'term_id',
                'terms' => $brand_id
            )
        ),
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            )
        )
    );
    
    return get_posts($args);
}

function render_store_item($store) {
    // Implementation for rendering individual store item
    // Would display store logo, prices, variants, availability, etc.
    ?>
    <div class="store-item">
        <div class="store-header">
            <?php if ($store['logo_url']): ?>
                <img src="<?php echo esc_url($store['logo_url']); ?>" alt="" class="store-logo">
            <?php endif; ?>
            <div class="store-price">
                <!-- Price display logic -->
            </div>
        </div>
        <!-- Store content -->
    </div>
    <?php
}

function render_comments_list($post_id) {
    // Implementation for rendering comments list
    // Would display approved comments with ratings
    echo '<div class="no-comments">' . __('Все още няма оценки', 'parfume-catalog') . '</div>';
}
?>

<style>
.parfume-single-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.parfume-content-wrapper {
    display: grid;
    grid-template-columns: 70% 30%;
    gap: 30px;
}

.parfume-left-column {
    background: #fff;
    padding: 0;
}

.parfume-right-column {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    position: sticky;
    top: 20px;
    height: fit-content;
}

.parfume-header {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.parfume-image {
    flex: 0 0 200px;
}

.parfume-featured-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.parfume-placeholder-image {
    width: 200px;
    height: 200px;
    background: #f0f0f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #ccc;
}

.parfume-header-info {
    flex: 1;
}

.parfume-title {
    margin: 0 0 15px 0;
    font-size: 28px;
    line-height: 1.2;
}

.parfume-meta {
    margin-bottom: 15px;
}

.parfume-meta > div {
    margin-bottom: 8px;
}

.comparison-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.comparison-btn:hover {
    background: #005a87;
}

.parfume-main-notes {
    margin-top: 15px;
}

.main-notes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.main-note {
    display: flex;
    align-items: center;
    gap: 5px;
    background: #f0f8ff;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 13px;
    border: 1px solid #b3d9ff;
}

.note-icon {
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.parfume-suitable-for {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
}

.suitable-icons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.suitable-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
}

.suitable-icon {
    font-size: 18px;
}

.parfume-content {
    margin: 30px 0;
    line-height: 1.6;
}

.parfume-composition {
    margin: 40px 0;
}

.composition-pyramid {
    display: grid;
    gap: 20px;
}

.notes-section {
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid;
}

.notes-section.top-notes {
    background: #fffde7;
    border-left-color: #ffeb3b;
}

.notes-section.heart-notes {
    background: #fff3e0;
    border-left-color: #ff5722;
}

.notes-section.base-notes {
    background: #efebe9;
    border-left-color: #795548;
}

.notes-section h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.notes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.note-item {
    display: flex;
    align-items: center;
    gap: 5px;
    background: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 13px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
}

.note-item:hover {
    border-color: #0073aa;
    text-decoration: none;
}

.parfume-graphics {
    margin: 40px 0;
}

.graphics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 20px;
}

.graphics-column h4 {
    margin: 0 0 15px 0;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    color: #555;
}

.progress-bars {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.progress-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
}

.progress-label {
    flex: 0 0 120px;
    font-size: 12px;
    color: #666;
}

.progress-fill {
    flex: 1;
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    position: relative;
}

.progress-bar.active .progress-fill {
    background: #0073aa;
}

.parfume-pros-cons {
    margin: 40px 0;
}

.pros-cons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.pros-column h4 {
    color: #46b450;
    margin-bottom: 15px;
}

.cons-column h4 {
    color: #dc3232;
    margin-bottom: 15px;
}

.pros-list,
.cons-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pro-item,
.con-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    line-height: 1.4;
}

.pro-icon,
.con-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.similar-grid,
.recent-grid,
.brand-grid {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.similar-item,
.recent-item,
.brand-item {
    text-align: center;
}

.similar-image,
.recent-image,
.brand-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
    margin-bottom: 10px;
}

.similar-placeholder,
.recent-placeholder,
.brand-placeholder {
    width: 100%;
    height: 200px;
    background: #f0f0f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #ccc;
    margin-bottom: 10px;
}

.similar-title,
.recent-title,
.brand-title {
    margin: 0;
    font-size: 14px;
    line-height: 1.3;
}

.parfume-comments {
    margin: 40px 0;
}

.comment-form {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.rating-input {
    display: flex;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.star-label {
    font-size: 24px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input input[type="radio"]:checked ~ .star-label,
.rating-input input[type="radio"]:checked + .star-label {
    color: #ffb900;
}

.rating-input .star-label:hover,
.rating-input input[type="radio"]:hover + .star-label {
    color: #ffb900;
}

.submit-comment-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.submit-comment-btn:hover {
    background: #005a87;
}

.submit-comment-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.stores-section h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.stores-intro {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.stores-note {
    margin-top: 20px;
    font-size: 12px;
    color: #999;
    text-align: center;
}

@media (max-width: 768px) {
    .parfume-content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .parfume-right-column {
        position: static;
        order: -1;
    }
    
    .parfume-header {
        flex-direction: column;
        text-align: center;
    }
    
    .parfume-image {
        flex: none;
        align-self: center;
    }
    
    .graphics-grid,
    .pros-cons-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>