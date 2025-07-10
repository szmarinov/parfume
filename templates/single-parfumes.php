<?php
/**
 * Single Parfume Template
 * 
 * Template for displaying individual parfume posts with full functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get parfume data
$post_id = get_the_ID();

// Safe method calls with fallbacks
$parfume_basic = array();
if (class_exists('Parfume_Catalog_Meta_Basic')) {
    $parfume_basic = Parfume_Catalog_Meta_Basic::get_parfume_info($post_id);
}

$parfume_notes = array();
if (class_exists('Parfume_Catalog_Meta_Notes')) {
    $parfume_notes = Parfume_Catalog_Meta_Notes::get_notes_composition($post_id);
}

$parfume_stores = array();
if (class_exists('Parfume_Catalog_Meta_Stores')) {
    $parfume_stores = Parfume_Catalog_Meta_Stores::get_formatted_stores($post_id);
}

$parfume_stats = array();
if (class_exists('Parfume_Catalog_Meta_Stats')) {
    $parfume_stats = Parfume_Catalog_Meta_Stats::get_public_stats($post_id);
}

// Get taxonomies
$parfume_type = get_the_terms($post_id, 'parfume_type');
$parfume_vid = get_the_terms($post_id, 'parfume_vid');
$parfume_marki = get_the_terms($post_id, 'parfume_marki');
$parfume_season = get_the_terms($post_id, 'parfume_season');
$parfume_intensity = get_the_terms($post_id, 'parfume_intensity');

// Settings - Fixed class names
$comparison_settings = array('enabled' => false);
if (class_exists('Parfume_Admin_Comparison')) {
    $comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
}

$comments_settings = array('enabled' => false);
if (class_exists('Parfume_Admin_Comments')) {
    $comments_settings = Parfume_Admin_Comments::get_comments_settings();
}

$plugin_options = get_option('parfume_catalog_options', array());

// Get suitable conditions
$suitable_conditions = array();
if ($parfume_season && !is_wp_error($parfume_season)) {
    foreach ($parfume_season as $season) {
        $suitable_conditions[] = $season->slug;
    }
}

// Day/Night suitability
$day_night_suitable = get_post_meta($post_id, '_parfume_day_night_suitable', true);
if ($day_night_suitable) {
    $suitable_conditions = array_merge($suitable_conditions, $day_night_suitable);
}

// Icons for suitable conditions
$suitable_icons = array(
    'prolet' => '🌸',
    'liato' => '☀️',
    'esen' => '🍂',
    'zima' => '❄️',
    'den' => '🌞',
    'nosht' => '🌙'
);

$suitable_labels = array(
    'prolet' => __('Пролет', 'parfume-catalog'),
    'liato' => __('Лято', 'parfume-catalog'),
    'esen' => __('Есен', 'parfume-catalog'),
    'zima' => __('Зима', 'parfume-catalog'),
    'den' => __('Ден', 'parfume-catalog'),
    'nosht' => __('Нощ', 'parfume-catalog')
);

// Get ratings info
$longevity_rating = get_post_meta($post_id, '_parfume_longevity_rating', true);
$sillage_rating = get_post_meta($post_id, '_parfume_sillage_rating', true);
$gender_rating = get_post_meta($post_id, '_parfume_gender_rating', true);
$price_rating = get_post_meta($post_id, '_parfume_price_rating', true);

// Get advantages and disadvantages
$advantages = get_post_meta($post_id, '_parfume_advantages', true);
$disadvantages = get_post_meta($post_id, '_parfume_disadvantages', true);

// Get settings from plugin options
$similar_count = isset($plugin_options['similar_count']) ? $plugin_options['similar_count'] : 4;
$similar_columns = isset($plugin_options['similar_columns']) ? $plugin_options['similar_columns'] : 4;
$recent_count = isset($plugin_options['recent_count']) ? $plugin_options['recent_count'] : 4;
$recent_columns = isset($plugin_options['recent_columns']) ? $plugin_options['recent_columns'] : 4;
$brand_count = isset($plugin_options['brand_count']) ? $plugin_options['brand_count'] : 4;
$brand_columns = isset($plugin_options['brand_columns']) ? $plugin_options['brand_columns'] : 4;

?>

<div class="single-parfume-container">
    <div class="parfume-main-content">
        <!-- Left Column -->
        <div class="parfume-left-column">
            <div class="parfume-header">
                <div class="parfume-image-section">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="parfume-featured-image">
                            <?php the_post_thumbnail('large', array('class' => 'parfume-main-image')); ?>
                        </div>
                    <?php else: ?>
                        <div class="parfume-placeholder-image">
                            <span class="dashicons dashicons-format-image"></span>
                            <p><?php _e('Няма изображение', 'parfume-catalog'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="parfume-info-section">
                    <div class="parfume-basic-info">
                        <h1 class="parfume-title"><?php the_title(); ?></h1>
                        
                        <?php if ($parfume_vid && !is_wp_error($parfume_vid)): ?>
                            <div class="parfume-type">
                                <strong><?php _e('Вид аромат:', 'parfume-catalog'); ?></strong>
                                <span class="parfume-vid"><?php echo esc_html($parfume_vid[0]->name); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($parfume_marki && !is_wp_error($parfume_marki)): ?>
                            <div class="parfume-brand">
                                <strong><?php _e('Марка:', 'parfume-catalog'); ?></strong>
                                <a href="<?php echo esc_url(get_term_link($parfume_marki[0])); ?>">
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
                                    <span class="comparison-text"><?php echo esc_html($comparison_settings['texts']['add'] ?? __('Добави за сравнение', 'parfume-catalog')); ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Basic aromantic notes -->
                    <?php if (!empty($parfume_notes['top_notes']) || !empty($parfume_notes['middle_notes']) || !empty($parfume_notes['base_notes'])): ?>
                        <div class="parfume-basic-notes">
                            <strong><?php _e('Основни ароматни нотки:', 'parfume-catalog'); ?></strong>
                            <div class="basic-notes-list">
                                <?php
                                $all_notes = array();
                                if (!empty($parfume_notes['top_notes'])) {
                                    $all_notes = array_merge($all_notes, array_slice($parfume_notes['top_notes'], 0, 3));
                                }
                                if (!empty($parfume_notes['middle_notes'])) {
                                    $all_notes = array_merge($all_notes, array_slice($parfume_notes['middle_notes'], 0, 2));
                                }
                                if (!empty($parfume_notes['base_notes'])) {
                                    $all_notes = array_merge($all_notes, array_slice($parfume_notes['base_notes'], 0, 2));
                                }
                                
                                $note_names = array();
                                foreach (array_slice($all_notes, 0, 5) as $note) {
                                    $note_names[] = is_array($note) ? $note['name'] : $note;
                                }
                                echo esc_html(implode(', ', $note_names));
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Suitable conditions -->
            <?php if (!empty($suitable_conditions)): ?>
                <div class="parfume-suitable-conditions">
                    <strong><?php _e('Подходящ за:', 'parfume-catalog'); ?></strong>
                    <div class="suitable-icons">
                        <?php 
                        foreach ($suitable_conditions as $suitable): 
                            if (isset($suitable_icons[$suitable])):
                        ?>
                            <div class="suitable-item" title="<?php echo esc_attr($suitable_labels[$suitable] ?? $suitable); ?>">
                                <span class="suitable-icon"><?php echo $suitable_icons[$suitable]; ?></span>
                                <span class="suitable-label"><?php echo esc_html($suitable_labels[$suitable] ?? $suitable); ?></span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Main content -->
            <div class="parfume-content">
                <div class="parfume-description">
                    <?php the_content(); ?>
                </div>
            </div>
            
            <!-- Composition Section -->
            <?php if (!empty($parfume_notes['top_notes']) || !empty($parfume_notes['middle_notes']) || !empty($parfume_notes['base_notes'])): ?>
                <div class="parfume-composition">
                    <h3><?php _e('Състав', 'parfume-catalog'); ?></h3>
                    <div class="composition-pyramid">
                        <?php if (!empty($parfume_notes['top_notes'])): ?>
                            <div class="notes-section top-notes">
                                <h4><?php _e('Връхни нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php foreach ($parfume_notes['top_notes'] as $note): ?>
                                        <div class="note-item">
                                            <?php if (isset($note['icon'])): ?>
                                                <span class="note-icon"><?php echo $note['icon']; ?></span>
                                            <?php endif; ?>
                                            <span class="note-name"><?php echo esc_html(is_array($note) ? $note['name'] : $note); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($parfume_notes['middle_notes'])): ?>
                            <div class="notes-section middle-notes">
                                <h4><?php _e('Средни нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php foreach ($parfume_notes['middle_notes'] as $note): ?>
                                        <div class="note-item">
                                            <?php if (isset($note['icon'])): ?>
                                                <span class="note-icon"><?php echo $note['icon']; ?></span>
                                            <?php endif; ?>
                                            <span class="note-name"><?php echo esc_html(is_array($note) ? $note['name'] : $note); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($parfume_notes['base_notes'])): ?>
                            <div class="notes-section base-notes">
                                <h4><?php _e('Базови нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php foreach ($parfume_notes['base_notes'] as $note): ?>
                                        <div class="note-item">
                                            <?php if (isset($note['icon'])): ?>
                                                <span class="note-icon"><?php echo $note['icon']; ?></span>
                                            <?php endif; ?>
                                            <span class="note-name"><?php echo esc_html(is_array($note) ? $note['name'] : $note); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Fragrance Graphics -->
            <?php if ($longevity_rating || $sillage_rating || $gender_rating || $price_rating): ?>
                <div class="parfume-graphics">
                    <h3><?php _e('Графика на аромата', 'parfume-catalog'); ?></h3>
                    <div class="graphics-grid">
                        <?php if ($longevity_rating): ?>
                            <div class="graphic-section longevity">
                                <h4><?php _e('Дълготрайност', 'parfume-catalog'); ?></h4>
                                <div class="rating-bars">
                                    <?php
                                    $longevity_labels = array(
                                        1 => __('Много слаб', 'parfume-catalog'),
                                        2 => __('Слаб', 'parfume-catalog'),
                                        3 => __('Умерен', 'parfume-catalog'),
                                        4 => __('Траен', 'parfume-catalog'),
                                        5 => __('Изключително траен', 'parfume-catalog')
                                    );
                                    
                                    for ($i = 1; $i <= 5; $i++) {
                                        $active = $i <= $longevity_rating ? 'active' : '';
                                        echo '<div class="rating-bar ' . $active . '">';
                                        echo '<span class="bar-label">' . $longevity_labels[$i] . '</span>';
                                        echo '<div class="bar-fill"></div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($sillage_rating): ?>
                            <div class="graphic-section sillage">
                                <h4><?php _e('Ароматна следа', 'parfume-catalog'); ?></h4>
                                <div class="rating-bars">
                                    <?php
                                    $sillage_labels = array(
                                        1 => __('Слаба', 'parfume-catalog'),
                                        2 => __('Умерена', 'parfume-catalog'),
                                        3 => __('Силна', 'parfume-catalog'),
                                        4 => __('Огромна', 'parfume-catalog')
                                    );
                                    
                                    for ($i = 1; $i <= 4; $i++) {
                                        $active = $i <= $sillage_rating ? 'active' : '';
                                        echo '<div class="rating-bar ' . $active . '">';
                                        echo '<span class="bar-label">' . $sillage_labels[$i] . '</span>';
                                        echo '<div class="bar-fill"></div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($gender_rating): ?>
                            <div class="graphic-section gender">
                                <h4><?php _e('Пол', 'parfume-catalog'); ?></h4>
                                <div class="rating-bars">
                                    <?php
                                    $gender_labels = array(
                                        1 => __('Дамски', 'parfume-catalog'),
                                        2 => __('Мъжки', 'parfume-catalog'),
                                        3 => __('Унисекс', 'parfume-catalog'),
                                        4 => __('По-млади', 'parfume-catalog'),
                                        5 => __('По-зрели', 'parfume-catalog')
                                    );
                                    
                                    for ($i = 1; $i <= 5; $i++) {
                                        $active = $i <= $gender_rating ? 'active' : '';
                                        echo '<div class="rating-bar ' . $active . '">';
                                        echo '<span class="bar-label">' . $gender_labels[$i] . '</span>';
                                        echo '<div class="bar-fill"></div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($price_rating): ?>
                            <div class="graphic-section price">
                                <h4><?php _e('Цена', 'parfume-catalog'); ?></h4>
                                <div class="rating-bars">
                                    <?php
                                    $price_labels = array(
                                        1 => __('Прекалено скъп', 'parfume-catalog'),
                                        2 => __('Скъп', 'parfume-catalog'),
                                        3 => __('Приемлива цена', 'parfume-catalog'),
                                        4 => __('Добра цена', 'parfume-catalog'),
                                        5 => __('Евтин', 'parfume-catalog')
                                    );
                                    
                                    for ($i = 1; $i <= 5; $i++) {
                                        $active = $i <= $price_rating ? 'active' : '';
                                        echo '<div class="rating-bar ' . $active . '">';
                                        echo '<span class="bar-label">' . $price_labels[$i] . '</span>';
                                        echo '<div class="bar-fill"></div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Advantages and Disadvantages -->
            <?php if (!empty($advantages) || !empty($disadvantages)): ?>
                <div class="parfume-pros-cons">
                    <h3><?php _e('Предимства и недостатъци', 'parfume-catalog'); ?></h3>
                    <div class="pros-cons-grid">
                        <?php if (!empty($advantages)): ?>
                            <div class="pros-section">
                                <h4><?php _e('Предимства', 'parfume-catalog'); ?></h4>
                                <ul class="pros-list">
                                    <?php foreach ($advantages as $advantage): ?>
                                        <li class="pro-item">
                                            <span class="pro-icon">✅</span>
                                            <span class="pro-text"><?php echo esc_html($advantage); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($disadvantages)): ?>
                            <div class="cons-section">
                                <h4><?php _e('Недостатъци', 'parfume-catalog'); ?></h4>
                                <ul class="cons-list">
                                    <?php foreach ($disadvantages as $disadvantage): ?>
                                        <li class="con-item">
                                            <span class="con-icon">❌</span>
                                            <span class="con-text"><?php echo esc_html($disadvantage); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Similar Fragrances -->
            <div class="parfume-similar">
                <h3><?php _e('Подобни аромати', 'parfume-catalog'); ?></h3>
                <div class="similar-grid" style="grid-template-columns: repeat(<?php echo $similar_columns; ?>, 1fr);">
                    <?php
                    // Get similar perfumes based on shared notes
                    $similar_parfumes = parfume_get_similar_perfumes($post_id, $similar_count);
                    
                    if (!empty($similar_parfumes)) {
                        foreach ($similar_parfumes as $similar_id) {
                            parfume_render_mini_card($similar_id);
                        }
                    } else {
                        echo '<p>' . __('Няма намерени подобни аромати.', 'parfume-catalog') . '</p>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Recently Viewed -->
            <div class="parfume-recent">
                <h3><?php _e('Наскоро разгледани', 'parfume-catalog'); ?></h3>
                <div class="recent-grid" style="grid-template-columns: repeat(<?php echo $recent_columns; ?>, 1fr);">
                    <?php
                    // Get recently viewed perfumes from localStorage (will be handled by JavaScript)
                    ?>
                    <div id="recently-viewed-container">
                        <p><?php _e('Няма наскоро разгледани парфюми.', 'parfume-catalog'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Other perfumes from same brand -->
            <?php if ($parfume_marki && !is_wp_error($parfume_marki)): ?>
                <div class="parfume-brand-others">
                    <h3><?php printf(__('Други парфюми от %s', 'parfume-catalog'), $parfume_marki[0]->name); ?></h3>
                    <div class="brand-grid" style="grid-template-columns: repeat(<?php echo $brand_columns; ?>, 1fr);">
                        <?php
                        $brand_parfumes = get_posts(array(
                            'post_type' => 'parfumes',
                            'posts_per_page' => $brand_count,
                            'post__not_in' => array($post_id),
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'parfume_marki',
                                    'field' => 'term_id',
                                    'terms' => $parfume_marki[0]->term_id
                                )
                            )
                        ));
                        
                        if (!empty($brand_parfumes)) {
                            foreach ($brand_parfumes as $brand_parfume) {
                                parfume_render_mini_card($brand_parfume->ID);
                            }
                        } else {
                            echo '<p>' . __('Няма други парфюми от тази марка.', 'parfume-catalog') . '</p>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Comments Section -->
            <?php if ($comments_settings['enabled']): ?>
                <div class="parfume-comments">
                    <h3><?php _e('Потребителски мнения и оценка', 'parfume-catalog'); ?></h3>
                    
                    <!-- Comments Form -->
                    <div class="comments-form">
                        <h4><?php _e('Споделете вашето мнение', 'parfume-catalog'); ?></h4>
                        <form id="parfume-comment-form" method="post">
                            <?php wp_nonce_field('parfume_comment_nonce', 'parfume_comment_nonce'); ?>
                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                            
                            <div class="form-group">
                                <label for="comment-name"><?php _e('Име (незадължително)', 'parfume-catalog'); ?></label>
                                <input type="text" id="comment-name" name="comment_name" placeholder="<?php _e('Анонимен', 'parfume-catalog'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="comment-rating"><?php _e('Оценка', 'parfume-catalog'); ?></label>
                                <div class="rating-input">
                                    <input type="radio" id="rating-1" name="comment_rating" value="1">
                                    <label for="rating-1">★</label>
                                    <input type="radio" id="rating-2" name="comment_rating" value="2">
                                    <label for="rating-2">★</label>
                                    <input type="radio" id="rating-3" name="comment_rating" value="3">
                                    <label for="rating-3">★</label>
                                    <input type="radio" id="rating-4" name="comment_rating" value="4">
                                    <label for="rating-4">★</label>
                                    <input type="radio" id="rating-5" name="comment_rating" value="5">
                                    <label for="rating-5">★</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment-text"><?php _e('Вашето мнение', 'parfume-catalog'); ?></label>
                                <textarea id="comment-text" name="comment_text" rows="5" required></textarea>
                            </div>
                            
                            <?php if ($comments_settings['captcha_enabled']): ?>
                                <div class="form-group">
                                    <label for="captcha"><?php _e('Защита от спам', 'parfume-catalog'); ?></label>
                                    <div class="captcha-question">
                                        <?php
                                        $num1 = rand(1, 10);
                                        $num2 = rand(1, 10);
                                        $answer = $num1 + $num2;
                                        ?>
                                        <p><?php printf(__('Колко е %d + %d?', 'parfume-catalog'), $num1, $num2); ?></p>
                                        <input type="number" id="captcha" name="captcha_answer" required>
                                        <input type="hidden" name="captcha_correct" value="<?php echo $answer; ?>">
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="button button-primary"><?php _e('Изпрати мнение', 'parfume-catalog'); ?></button>
                        </form>
                    </div>
                    
                    <!-- Comments List -->
                    <div class="comments-list">
                        <?php
                        // Get approved comments for this post
                        $comments = parfume_get_comments($post_id);
                        
                        if (!empty($comments)) {
                            echo '<h4>' . __('Мнения на потребители', 'parfume-catalog') . '</h4>';
                            foreach ($comments as $comment) {
                                parfume_render_comment($comment);
                            }
                        } else {
                            echo '<p>' . __('Все още няма оценки.', 'parfume-catalog') . '</p>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Stores -->
        <div class="parfume-right-column">
            <div class="parfume-stores-sidebar">
                <h3><?php _e('Сравни цените', 'parfume-catalog'); ?></h3>
                <p><?php printf(__('Купи %s на най-изгодната цена:', 'parfume-catalog'), get_the_title()); ?></p>
                
                <?php if (!empty($parfume_stores)): ?>
                    <div class="stores-list">
                        <?php foreach ($parfume_stores as $store): ?>
                            <div class="store-item">
                                <?php parfume_render_store_card($store); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="stores-update-note">
                        <small><?php _e('Цените се актуализират на всеки 12 ч.', 'parfume-catalog'); ?></small>
                    </div>
                <?php else: ?>
                    <p><?php _e('Няма налични оферти в момента.', 'parfume-catalog'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions

function parfume_get_similar_perfumes($post_id, $count = 4) {
    $similar_ids = array();
    
    // Get notes of current perfume
    $current_notes = get_the_terms($post_id, 'parfume_notes');
    if (!$current_notes || is_wp_error($current_notes)) {
        return $similar_ids;
    }
    
    $note_ids = array();
    foreach ($current_notes as $note) {
        $note_ids[] = $note->term_id;
    }
    
    // Find perfumes with similar notes
    $similar_posts = get_posts(array(
        'post_type' => 'parfumes',
        'posts_per_page' => $count * 2, // Get more to filter duplicates
        'post__not_in' => array($post_id),
        'tax_query' => array(
            array(
                'taxonomy' => 'parfume_notes',
                'field' => 'term_id',
                'terms' => $note_ids,
                'operator' => 'IN'
            )
        )
    ));
    
    foreach ($similar_posts as $post) {
        $similar_ids[] = $post->ID;
        if (count($similar_ids) >= $count) {
            break;
        }
    }
    
    return $similar_ids;
}

function parfume_render_mini_card($post_id) {
    $post = get_post($post_id);
    $brands = get_the_terms($post_id, 'parfume_marki');
    ?>
    <div class="parfume-mini-card">
        <a href="<?php echo get_permalink($post_id); ?>">
            <?php if (has_post_thumbnail($post_id)): ?>
                <?php echo get_the_post_thumbnail($post_id, 'thumbnail', array('class' => 'mini-card-image')); ?>
            <?php else: ?>
                <div class="mini-card-placeholder">
                    <span class="dashicons dashicons-format-image"></span>
                </div>
            <?php endif; ?>
            <h5 class="mini-card-title"><?php echo get_the_title($post_id); ?></h5>
            <?php if ($brands && !is_wp_error($brands)): ?>
                <div class="mini-card-brand"><?php echo esc_html($brands[0]->name); ?></div>
            <?php endif; ?>
        </a>
    </div>
    <?php
}

function parfume_get_comments($post_id) {
    // This would integrate with the comments system
    // For now, return empty array
    return array();
}

function parfume_render_comment($comment) {
    // This would render individual comment
    // Implementation depends on comment structure
}

function parfume_render_store_card($store) {
    // This would render store card with prices
    // Implementation depends on store structure
}

?>

<script>
jQuery(document).ready(function($) {
    // Track viewed perfume for recently viewed
    var viewedPerfumes = JSON.parse(localStorage.getItem('viewedPerfumes')) || [];
    var currentPerfume = {
        id: <?php echo $post_id; ?>,
        title: "<?php echo esc_js(get_the_title()); ?>",
        image: "<?php echo esc_js(get_the_post_thumbnail_url($post_id, 'thumbnail')); ?>",
        brand: "<?php echo esc_js($parfume_marki && !is_wp_error($parfume_marki) ? $parfume_marki[0]->name : ''); ?>",
        timestamp: Date.now()
    };
    
    // Remove if already exists
    viewedPerfumes = viewedPerfumes.filter(function(p) {
        return p.id !== currentPerfume.id;
    });
    
    // Add to beginning
    viewedPerfumes.unshift(currentPerfume);
    
    // Keep only last 10
    viewedPerfumes = viewedPerfumes.slice(0, 10);
    
    // Save to localStorage
    localStorage.setItem('viewedPerfumes', JSON.stringify(viewedPerfumes));
    
    // Load recently viewed (exclude current)
    var recentPerfumes = viewedPerfumes.filter(function(p) {
        return p.id !== currentPerfume.id;
    }).slice(0, <?php echo $recent_count; ?>);
    
    if (recentPerfumes.length > 0) {
        var recentHtml = '';
        recentPerfumes.forEach(function(perfume) {
            recentHtml += '<div class="parfume-mini-card">';
            recentHtml += '<a href="' + perfume.link + '">';
            if (perfume.image) {
                recentHtml += '<img src="' + perfume.image + '" alt="' + perfume.title + '" class="mini-card-image">';
            } else {
                recentHtml += '<div class="mini-card-placeholder"><span class="dashicons dashicons-format-image"></span></div>';
            }
            recentHtml += '<h5 class="mini-card-title">' + perfume.title + '</h5>';
            if (perfume.brand) {
                recentHtml += '<div class="mini-card-brand">' + perfume.brand + '</div>';
            }
            recentHtml += '</a></div>';
        });
        
        $('#recently-viewed-container').html(recentHtml);
    }
    
    <?php if ($comparison_settings['enabled']): ?>
    // Comparison functionality
    var comparisonItems = JSON.parse(localStorage.getItem('parfumeComparison')) || [];
    
    function updateComparisonButton() {
        var parfumeId = <?php echo $post_id; ?>;
        var isInComparison = comparisonItems.some(function(item) {
            return item.id === parfumeId;
        });
        
        if (isInComparison) {
            $('.comparison-btn').addClass('active');
            $('.comparison-btn .comparison-text').text('<?php echo esc_js($comparison_settings['texts']['remove'] ?? __('Премахни от сравнение', 'parfume-catalog')); ?>');
        } else {
            $('.comparison-btn').removeClass('active');
            $('.comparison-btn .comparison-text').text('<?php echo esc_js($comparison_settings['texts']['add'] ?? __('Добави за сравнение', 'parfume-catalog')); ?>');
        }
    }
    
    $('.comparison-btn').on('click', function() {
        var parfumeId = $(this).data('parfume-id');
        var parfumeTitle = $(this).data('parfume-title');
        var parfumeImage = $(this).data('parfume-image');
        
        var existingIndex = comparisonItems.findIndex(function(item) {
            return item.id === parfumeId;
        });
        
        if (existingIndex !== -1) {
            // Remove from comparison
            comparisonItems.splice(existingIndex, 1);
        } else {
            // Add to comparison
            var maxItems = <?php echo intval($comparison_settings['max_items'] ?? 4); ?>;
            if (comparisonItems.length >= maxItems) {
                alert('<?php echo esc_js($comparison_settings['texts']['max_reached'] ?? __('Максимален брой достигнат', 'parfume-catalog')); ?>');
                return;
            }
            
            comparisonItems.push({
                id: parfumeId,
                title: parfumeTitle,
                image: parfumeImage
            });
        }
        
        localStorage.setItem('parfumeComparison', JSON.stringify(comparisonItems));
        updateComparisonButton();
    });
    
    updateComparisonButton();
    <?php endif; ?>
    
    // Rating input functionality
    $('.rating-input input').on('change', function() {
        var rating = $(this).val();
        $('.rating-input label').removeClass('active');
        $(this).prevAll('label').addBack().addClass('active');
        $(this).nextAll('label').removeClass('active');
    });
    
    // Comment form submission
    $('#parfume-comment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData + '&action=parfume_submit_comment',
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Мнението е изпратено за одобрение.', 'parfume-catalog')); ?>');
                    $('#parfume-comment-form')[0].reset();
                } else {
                    alert('<?php echo esc_js(__('Възникна грешка. Моля, опитайте отново.', 'parfume-catalog')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Възникна грешка. Моля, опитайте отново.', 'parfume-catalog')); ?>');
            }
        });
    });
});
</script>

<style>
/* Single parfume styles */
.single-parfume-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.parfume-main-content {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 40px;
    align-items: start;
}

.parfume-left-column {
    min-width: 0;
}

.parfume-right-column {
    position: sticky;
    top: 20px;
}

.parfume-header {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 30px;
    margin-bottom: 30px;
    align-items: start;
}

.parfume-featured-image,
.parfume-placeholder-image {
    width: 200px;
    height: 200px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #ddd;
}

.parfume-main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.parfume-placeholder-image {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    color: #999;
}

.parfume-title {
    font-size: 2.5rem;
    margin: 0 0 20px 0;
    color: #333;
    line-height: 1.2;
}

.parfume-type,
.parfume-brand {
    margin-bottom: 10px;
    font-size: 16px;
}

.parfume-type strong,
.parfume-brand strong {
    color: #333;
    margin-right: 8px;
}

.parfume-vid {
    color: #666;
}

.parfume-brand a {
    color: #0073aa;
    text-decoration: none;
}

.parfume-brand a:hover {
    text-decoration: underline;
}

.comparison-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
}

.comparison-btn:hover {
    background: #005a87;
}

.comparison-btn.active {
    background: #dc3232;
}

.parfume-basic-notes {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
}

.parfume-basic-notes strong {
    display: block;
    margin-bottom: 8px;
    color: #333;
}

.basic-notes-list {
    color: #666;
    line-height: 1.5;
}

.parfume-suitable-conditions {
    margin-bottom: 30px;
    padding: 20px;
    background: #f0f8ff;
    border-radius: 8px;
}

.parfume-suitable-conditions strong {
    display: block;
    margin-bottom: 15px;
    color: #333;
    font-size: 18px;
}

.suitable-icons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.suitable-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: white;
    border-radius: 20px;
    border: 1px solid #ddd;
}

.suitable-icon {
    font-size: 20px;
}

.suitable-label {
    font-size: 14px;
    color: #333;
}

.parfume-content {
    margin-bottom: 30px;
}

.parfume-description {
    font-size: 16px;
    line-height: 1.6;
    color: #333;
}

.parfume-composition {
    margin-bottom: 30px;
}

.parfume-composition h3 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

.composition-pyramid {
    display: grid;
    gap: 20px;
}

.notes-section {
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.notes-section.top-notes {
    background: #fff8dc;
}

.notes-section.middle-notes {
    background: #f0fff0;
}

.notes-section.base-notes {
    background: #faf0e6;
}

.notes-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 18px;
}

.notes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.note-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: white;
    border-radius: 15px;
    border: 1px solid #ddd;
    font-size: 14px;
}

.note-icon {
    font-size: 16px;
}

.parfume-graphics {
    margin-bottom: 30px;
}

.parfume-graphics h3 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

.graphics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.graphic-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.graphic-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
    text-align: center;
}

.rating-bars {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.rating-bar.active {
    background: #e8f5e8;
}

.rating-bar.active .bar-fill {
    background: #4caf50;
}

.bar-label {
    flex: 1;
    font-size: 12px;
    color: #666;
}

.bar-fill {
    width: 30px;
    height: 8px;
    background: #ddd;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.parfume-pros-cons {
    margin-bottom: 30px;
}

.parfume-pros-cons h3 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

.pros-cons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.pros-section,
.cons-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.pros-section h4 {
    color: #4caf50;
    margin: 0 0 15px 0;
}

.cons-section h4 {
    color: #f44336;
    margin: 0 0 15px 0;
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
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.pro-item:last-child,
.con-item:last-child {
    border-bottom: none;
}

.pro-icon,
.con-icon {
    font-size: 16px;
    flex-shrink: 0;
}

.pro-text,
.con-text {
    flex: 1;
    color: #333;
}

.parfume-similar,
.parfume-recent,
.parfume-brand-others {
    margin-bottom: 30px;
}

.parfume-similar h3,
.parfume-recent h3,
.parfume-brand-others h3 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

.similar-grid,
.recent-grid,
.brand-grid {
    display: grid;
    gap: 15px;
}

.parfume-mini-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.parfume-mini-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.parfume-mini-card a {
    display: block;
    text-decoration: none;
    color: inherit;
}

.mini-card-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.mini-card-placeholder {
    width: 100%;
    height: 120px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
}

.mini-card-title {
    padding: 10px;
    margin: 0;
    font-size: 14px;
    color: #333;
    line-height: 1.3;
}

.mini-card-brand {
    padding: 0 10px 10px 10px;
    font-size: 12px;
    color: #666;
}

.parfume-comments {
    margin-bottom: 30px;
}

.parfume-comments h3,
.parfume-comments h4 {
    color: #333;
    margin-bottom: 20px;
}

.comments-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: bold;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.rating-input {
    display: flex;
    gap: 5px;
}

.rating-input input {
    display: none;
}

.rating-input label {
    font-size: 24px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.3s ease;
}

.rating-input label:hover,
.rating-input label.active {
    color: #ffd700;
}

.captcha-question {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.captcha-question p {
    margin: 0 0 10px 0;
    font-weight: bold;
}

.captcha-question input {
    width: 80px;
}

.parfume-stores-sidebar {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    position: sticky;
    top: 20px;
}

.parfume-stores-sidebar h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 20px;
}

.stores-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.store-item {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #f9f9f9;
}

.stores-update-note {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    text-align: center;
}

.stores-update-note small {
    color: #666;
    font-style: italic;
}

@media (max-width: 768px) {
    .parfume-main-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .parfume-header {
        grid-template-columns: 1fr;
        gap: 20px;
        text-align: center;
    }
    
    .parfume-featured-image,
    .parfume-placeholder-image {
        width: 150px;
        height: 150px;
        margin: 0 auto;
    }
    
    .parfume-title {
        font-size: 2rem;
    }
    
    .graphics-grid {
        grid-template-columns: 1fr;
    }
    
    .pros-cons-grid {
        grid-template-columns: 1fr;
    }
    
    .similar-grid,
    .recent-grid,
    .brand-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .parfume-right-column {
        position: static;
        order: -1;
    }
}
</style>

<?php get_footer(); ?>