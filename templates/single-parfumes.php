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
$parfume_basic = Parfume_Catalog_Meta_Basic::get_parfume_info($post_id);
$parfume_notes = Parfume_Catalog_Meta_Notes::get_notes_composition($post_id);
$parfume_stores = Parfume_Catalog_Meta_Stores::get_formatted_stores($post_id);
$parfume_stats = Parfume_Catalog_Meta_Stats::get_public_stats($post_id);

// Get taxonomies
$parfume_type = get_the_terms($post_id, 'parfume_type');
$parfume_vid = get_the_terms($post_id, 'parfume_vid');
$parfume_marki = get_the_terms($post_id, 'parfume_marki');
$parfume_season = get_the_terms($post_id, 'parfume_season');
$parfume_intensity = get_the_terms($post_id, 'parfume_intensity');

// Settings
$comparison_settings = Parfume_Catalog_Admin_Comparison::get_comparison_settings();
$comments_settings = Parfume_Catalog_Admin_Comments::get_comments_settings();
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

// Get advantages and disadvantages
$advantages = get_post_meta($post_id, '_parfume_advantages', true);
$disadvantages = get_post_meta($post_id, '_parfume_disadvantages', true);

// Get stats for graphics
$durability_stats = get_post_meta($post_id, '_parfume_durability_stats', true);
$sillage_stats = get_post_meta($post_id, '_parfume_sillage_stats', true);
$gender_stats = get_post_meta($post_id, '_parfume_gender_stats', true);
$price_stats = get_post_meta($post_id, '_parfume_price_stats', true);
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
                                    <?php echo esc_html($comparison_settings['texts']['add_to_comparison']); ?>
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
                                    $note_names[] = $note['name'];
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
                            <div class="suitable-item" title="<?php echo esc_attr($suitable_labels[$suitable] ?? ''); ?>">
                                <span class="suitable-icon"><?php echo $suitable_icons[$suitable]; ?></span>
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
            <?php if (!empty($parfume_notes['top_notes']) || !empty($parfume_notes['middle_notes']) || !empty($parfume_notes['base_notes'])): ?>
                <div class="parfume-composition">
                    <h3><?php _e('Състав', 'parfume-catalog'); ?></h3>
                    <div class="composition-pyramid">
                        <?php if (!empty($parfume_notes['top_notes'])): ?>
                            <div class="notes-section top-notes">
                                <h4><?php _e('Връхни нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php
                                    foreach ($parfume_notes['top_notes'] as $note):
                                    ?>
                                        <span class="note-item">
                                            <?php if (!empty($note['icon_url'])): ?>
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
                        
                        <?php if (!empty($parfume_notes['middle_notes'])): ?>
                            <div class="notes-section middle-notes">
                                <h4><?php _e('Средни нотки', 'parfume-catalog'); ?></h4>
                                <div class="notes-list">
                                    <?php
                                    foreach ($parfume_notes['middle_notes'] as $note):
                                    ?>
                                        <span class="note-item">
                                            <?php if (!empty($note['icon_url'])): ?>
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
                                    foreach ($parfume_notes['base_notes'] as $note):
                                    ?>
                                        <span class="note-item">
                                            <?php if (!empty($note['icon_url'])): ?>
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
            <?php if ($durability_stats || $sillage_stats || $gender_stats || $price_stats): ?>
                <div class="parfume-graphics">
                    <h3><?php _e('Графика на аромата', 'parfume-catalog'); ?></h3>
                    
                    <div class="graphics-row">
                        <?php if ($durability_stats): ?>
                            <div class="graphic-section">
                                <h4><?php _e('ДЪЛГОТРАЙНОСТ', 'parfume-catalog'); ?></h4>
                                <div class="progress-bars">
                                    <?php
                                    $durability_levels = array(
                                        'very_weak' => __('много слаб', 'parfume-catalog'),
                                        'weak' => __('слаб', 'parfume-catalog'),
                                        'moderate' => __('умерен', 'parfume-catalog'),
                                        'long_lasting' => __('траен', 'parfume-catalog'),
                                        'very_long_lasting' => __('изключително траен', 'parfume-catalog')
                                    );
                                    
                                    foreach ($durability_levels as $level => $label):
                                        $value = isset($durability_stats[$level]) ? intval($durability_stats[$level]) : 0;
                                    ?>
                                        <div class="progress-bar">
                                            <span class="progress-label"><?php echo esc_html($label); ?></span>
                                            <div class="progress-track">
                                                <div class="progress-fill" style="width: <?php echo $value; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($sillage_stats): ?>
                            <div class="graphic-section">
                                <h4><?php _e('АРОМАТНА СЛЕДА', 'parfume-catalog'); ?></h4>
                                <div class="progress-bars">
                                    <?php
                                    $sillage_levels = array(
                                        'weak' => __('слаба', 'parfume-catalog'),
                                        'moderate' => __('умерена', 'parfume-catalog'),
                                        'strong' => __('силна', 'parfume-catalog'),
                                        'enormous' => __('огромна', 'parfume-catalog')
                                    );
                                    
                                    foreach ($sillage_levels as $level => $label):
                                        $value = isset($sillage_stats[$level]) ? intval($sillage_stats[$level]) : 0;
                                    ?>
                                        <div class="progress-bar">
                                            <span class="progress-label"><?php echo esc_html($label); ?></span>
                                            <div class="progress-track">
                                                <div class="progress-fill" style="width: <?php echo $value; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="graphics-row">
                        <?php if ($gender_stats): ?>
                            <div class="graphic-section">
                                <h4><?php _e('ПОЛ', 'parfume-catalog'); ?></h4>
                                <div class="progress-bars">
                                    <?php
                                    $gender_levels = array(
                                        'female' => __('дамски', 'parfume-catalog'),
                                        'male' => __('мъжки', 'parfume-catalog'),
                                        'unisex' => __('унисекс', 'parfume-catalog'),
                                        'younger' => __('по-млади', 'parfume-catalog'),
                                        'older' => __('по-зрели', 'parfume-catalog')
                                    );
                                    
                                    foreach ($gender_levels as $level => $label):
                                        $value = isset($gender_stats[$level]) ? intval($gender_stats[$level]) : 0;
                                    ?>
                                        <div class="progress-bar">
                                            <span class="progress-label"><?php echo esc_html($label); ?></span>
                                            <div class="progress-track">
                                                <div class="progress-fill" style="width: <?php echo $value; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($price_stats): ?>
                            <div class="graphic-section">
                                <h4><?php _e('ЦЕНА', 'parfume-catalog'); ?></h4>
                                <div class="progress-bars">
                                    <?php
                                    $price_levels = array(
                                        'too_expensive' => __('прекалено скъп', 'parfume-catalog'),
                                        'expensive' => __('скъп', 'parfume-catalog'),
                                        'acceptable' => __('приемлива цена', 'parfume-catalog'),
                                        'good_price' => __('добра цена', 'parfume-catalog'),
                                        'cheap' => __('евтин', 'parfume-catalog')
                                    );
                                    
                                    foreach ($price_levels as $level => $label):
                                        $value = isset($price_stats[$level]) ? intval($price_stats[$level]) : 0;
                                    ?>
                                        <div class="progress-bar">
                                            <span class="progress-label"><?php echo esc_html($label); ?></span>
                                            <div class="progress-track">
                                                <div class="progress-fill" style="width: <?php echo $value; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Advantages and Disadvantages -->
            <?php if ($advantages || $disadvantages): ?>
                <div class="parfume-pros-cons">
                    <h3><?php _e('Предимства и недостатъци', 'parfume-catalog'); ?></h3>
                    
                    <div class="pros-cons-grid">
                        <?php if ($advantages): ?>
                            <div class="pros-section">
                                <h4><?php _e('Предимства', 'parfume-catalog'); ?></h4>
                                <ul class="pros-list">
                                    <?php foreach ($advantages as $advantage): ?>
                                        <li class="pro-item">
                                            <span class="pro-icon">✓</span>
                                            <?php echo esc_html($advantage); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($disadvantages): ?>
                            <div class="cons-section">
                                <h4><?php _e('Недостатъци', 'parfume-catalog'); ?></h4>
                                <ul class="cons-list">
                                    <?php foreach ($disadvantages as $disadvantage): ?>
                                        <li class="con-item">
                                            <span class="con-icon">✗</span>
                                            <?php echo esc_html($disadvantage); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Similar Parfumes -->
            <?php
            $similar_count = isset($plugin_options['similar_parfumes_count']) ? intval($plugin_options['similar_parfumes_count']) : 4;
            $similar_parfumes = Parfume_Catalog_Template_Loader::get_similar_parfumes($post_id, $similar_count);
            
            if ($similar_parfumes):
            ?>
                <div class="parfume-similar">
                    <h3><?php _e('Подобни аромати', 'parfume-catalog'); ?></h3>
                    <div class="similar-parfumes-grid">
                        <?php foreach ($similar_parfumes as $similar): ?>
                            <div class="similar-parfume-item">
                                <a href="<?php echo get_permalink($similar); ?>">
                                    <?php if (has_post_thumbnail($similar)): ?>
                                        <?php echo get_the_post_thumbnail($similar, 'medium', array('class' => 'similar-parfume-image')); ?>
                                    <?php else: ?>
                                        <div class="similar-parfume-placeholder">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="similar-parfume-title"><?php echo get_the_title($similar); ?></h4>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Recently Viewed -->
            <?php
            $recently_viewed_count = isset($plugin_options['recently_viewed_count']) ? intval($plugin_options['recently_viewed_count']) : 4;
            $recently_viewed = Parfume_Catalog_Template_Loader::get_recently_viewed($recently_viewed_count);
            
            if ($recently_viewed):
            ?>
                <div class="parfume-recently-viewed">
                    <h3><?php _e('Наскоро разгледани', 'parfume-catalog'); ?></h3>
                    <div class="recently-viewed-grid">
                        <?php foreach ($recently_viewed as $recent): ?>
                            <div class="recent-parfume-item">
                                <a href="<?php echo get_permalink($recent); ?>">
                                    <?php if (has_post_thumbnail($recent)): ?>
                                        <?php echo get_the_post_thumbnail($recent, 'medium', array('class' => 'recent-parfume-image')); ?>
                                    <?php else: ?>
                                        <div class="recent-parfume-placeholder">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="recent-parfume-title"><?php echo get_the_title($recent); ?></h4>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Brand Parfumes -->
            <?php
            if ($parfume_marki):
                $brand_count = isset($plugin_options['brand_parfumes_count']) ? intval($plugin_options['brand_parfumes_count']) : 4;
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
                
                if ($brand_parfumes):
            ?>
                <div class="parfume-from-brand">
                    <h3><?php printf(__('Други парфюми от %s', 'parfume-catalog'), esc_html($parfume_marki[0]->name)); ?></h3>
                    <div class="brand-parfumes-grid">
                        <?php foreach ($brand_parfumes as $brand_parfume): ?>
                            <div class="brand-parfume-item">
                                <a href="<?php echo get_permalink($brand_parfume); ?>">
                                    <?php if (has_post_thumbnail($brand_parfume)): ?>
                                        <?php echo get_the_post_thumbnail($brand_parfume, 'medium', array('class' => 'brand-parfume-image')); ?>
                                    <?php else: ?>
                                        <div class="brand-parfume-placeholder">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="brand-parfume-title"><?php echo get_the_title($brand_parfume); ?></h4>
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
                <div class="parfume-comments-section">
                    <h3><?php _e('Потребителски мнения и оценка', 'parfume-catalog'); ?></h3>
                    
                    <!-- Comments display -->
                    <div id="parfume-comments-list">
                        <?php Parfume_Catalog_Comments::display_comments($post_id); ?>
                    </div>
                    
                    <!-- Add comment form -->
                    <div class="parfume-comment-form">
                        <h4><?php _e('Споделете вашето мнение', 'parfume-catalog'); ?></h4>
                        <form id="parfume-comment-form" method="post">
                            <?php wp_nonce_field('parfume_add_comment', 'parfume_comment_nonce'); ?>
                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                            
                            <div class="comment-form-fields">
                                <div class="comment-field">
                                    <label for="comment-author-name"><?php _e('Име', 'parfume-catalog'); ?></label>
                                    <input type="text" 
                                           id="comment-author-name" 
                                           name="author_name" 
                                           placeholder="<?php _e('Оставете празно за „Анонимен"', 'parfume-catalog'); ?>">
                                </div>
                                
                                <div class="comment-field">
                                    <label for="comment-rating"><?php _e('Оценка', 'parfume-catalog'); ?></label>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star" data-rating="<?php echo $i; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" id="comment-rating" name="rating" value="">
                                </div>
                                
                                <div class="comment-field">
                                    <label for="comment-content"><?php _e('Мнение', 'parfume-catalog'); ?></label>
                                    <textarea id="comment-content" 
                                              name="content" 
                                              rows="4" 
                                              placeholder="<?php _e('Споделете вашето мнение за този парфюм...', 'parfume-catalog'); ?>" 
                                              required></textarea>
                                </div>
                                
                                <?php if ($comments_settings['captcha_enabled']): ?>
                                    <div class="comment-field">
                                        <label for="comment-captcha"><?php _e('Проверка', 'parfume-catalog'); ?></label>
                                        <div class="captcha-question">
                                            <span><?php echo $comments_settings['captcha_question']; ?></span>
                                            <input type="text" id="comment-captcha" name="captcha" required>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="submit-comment-btn">
                                <?php _e('Публикувай мнение', 'parfume-catalog'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column (30%) -->
        <div class="parfume-right-column">
            <div class="parfume-stores-sidebar">
                <h3><?php _e('Сравни цените', 'parfume-catalog'); ?></h3>
                <p class="stores-intro"><?php printf(__('Купи %s на най‑изгодната цена:', 'parfume-catalog'), get_the_title()); ?></p>
                
                <?php if ($parfume_stores && !empty($parfume_stores)): ?>
                    <div class="stores-list">
                        <?php foreach ($parfume_stores as $store): ?>
                            <div class="store-item">
                                <div class="store-header">
                                    <div class="store-logo">
                                        <?php if ($store['logo_url']): ?>
                                            <img src="<?php echo esc_url($store['logo_url']); ?>" alt="<?php echo esc_attr($store['name']); ?>">
                                        <?php else: ?>
                                            <span class="store-name"><?php echo esc_html($store['name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="store-price">
                                        <?php if ($store['old_price'] && $store['old_price'] > $store['price']): ?>
                                            <span class="old-price"><?php echo esc_html($store['old_price'] . ' ' . $store['currency']); ?></span>
                                        <?php endif; ?>
                                        <span class="current-price"><?php echo esc_html($store['price'] . ' ' . $store['currency']); ?></span>
                                        <?php if ($store['old_price'] && $store['old_price'] > $store['price']): ?>
                                            <?php
                                            $discount_percent = round((($store['old_price'] - $store['price']) / $store['old_price']) * 100);
                                            ?>
                                            <span class="discount-percent"><?php printf(__('По-изгодно с %d%%', 'parfume-catalog'), $discount_percent); ?></span>
                                        <?php endif; ?>
                                        <span class="price-update-info">
                                            <i class="update-icon">ℹ</i>
                                            <span class="tooltip"><?php _e('Цената се актуализира на всеки 12 часа', 'parfume-catalog'); ?></span>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="store-details">
                                    <?php if (count($store['variants']) == 1): ?>
                                        <div class="store-variant">
                                            <span class="variant-size"><?php echo esc_html($store['variants'][0]['size']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($store['availability']): ?>
                                        <div class="store-availability">
                                            <span class="availability-status available">
                                                <span class="status-icon">✓</span>
                                                <?php echo esc_html($store['availability']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($store['delivery_info']): ?>
                                        <div class="store-delivery">
                                            <span class="delivery-info"><?php echo esc_html($store['delivery_info']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (count($store['variants']) > 1): ?>
                                    <div class="store-variants">
                                        <?php foreach ($store['variants'] as $variant): ?>
                                            <a href="<?php echo esc_url($store['affiliate_url']); ?>" 
                                               class="variant-btn<?php echo $variant['on_sale'] ? ' on-sale' : ''; ?>" 
                                               target="_blank" 
                                               rel="nofollow">
                                                <?php if ($variant['on_sale']): ?>
                                                    <span class="sale-badge">%</span>
                                                <?php endif; ?>
                                                <span class="variant-size"><?php echo esc_html($variant['size']); ?></span>
                                                <span class="variant-price"><?php echo esc_html($variant['price'] . ' ' . $store['currency']); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="store-actions">
                                    <a href="<?php echo esc_url($store['affiliate_url']); ?>" 
                                       class="store-btn primary" 
                                       target="_blank" 
                                       rel="nofollow">
                                        <?php _e('Към магазина', 'parfume-catalog'); ?>
                                    </a>
                                    
                                    <?php if ($store['promo_code']): ?>
                                        <div class="promo-code-btn" data-promo-code="<?php echo esc_attr($store['promo_code']); ?>">
                                            <?php if ($store['promo_code_info']): ?>
                                                <span class="promo-info"><?php echo esc_html($store['promo_code_info']); ?></span>
                                            <?php endif; ?>
                                            <span class="promo-code"><?php echo esc_html($store['promo_code']); ?></span>
                                            <span class="copy-icon">📋</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="price-update-note">
                        <small><?php _e('Цените ни се актуализират на всеки 12 ч.', 'parfume-catalog'); ?></small>
                    </div>
                <?php else: ?>
                    <div class="no-stores">
                        <p><?php _e('В момента няма налични оферти за този парфюм.', 'parfume-catalog'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Rating stars functionality
    $('.rating-stars .star').on('click', function() {
        var rating = $(this).data('rating');
        $('#comment-rating').val(rating);
        
        $('.rating-stars .star').removeClass('active');
        for (var i = 1; i <= rating; i++) {
            $('.rating-stars .star[data-rating="' + i + '"]').addClass('active');
        }
    });
    
    // Promo code copy functionality
    $('.promo-code-btn').on('click', function() {
        var promoCode = $(this).data('promo-code');
        
        // Copy to clipboard
        navigator.clipboard.writeText(promoCode).then(function() {
            // Show success message
            $(this).addClass('copied');
            setTimeout(function() {
                $(this).removeClass('copied');
            }, 2000);
        });
        
        // Open affiliate URL
        var affiliateUrl = $(this).closest('.store-item').find('.store-btn.primary').attr('href');
        if (affiliateUrl) {
            window.open(affiliateUrl, '_blank');
        }
    });
    
    // Comparison functionality
    $('.comparison-btn').on('click', function() {
        var parfumeId = $(this).data('parfume-id');
        var parfumeTitle = $(this).data('parfume-title');
        var parfumeImage = $(this).data('parfume-image');
        
        // Add to comparison logic here
        console.log('Add to comparison:', parfumeId, parfumeTitle, parfumeImage);
    });
    
    // Comment form submission
    $('#parfume-comment-form').on('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        if (!$('#comment-content').val().trim()) {
            alert('Моля, въведете вашето мнение.');
            return;
        }
        
        if (!$('#comment-rating').val()) {
            alert('Моля, поставете оценка.');
            return;
        }
        
        // Submit comment via AJAX
        var formData = $(this).serialize();
        
        $.ajax({
            url: parfume_catalog_config.ajaxUrl,
            type: 'POST',
            data: formData + '&action=parfume_add_comment',
            success: function(response) {
                if (response.success) {
                    alert('Благодарим за вашето мнение! То ще бъде публикувано след одобрение.');
                    $('#parfume-comment-form')[0].reset();
                    $('.rating-stars .star').removeClass('active');
                    $('#comment-rating').val('');
                } else {
                    alert('Грешка при изпращане на мнението. Моля, опитайте отново.');
                }
            },
            error: function() {
                alert('Грешка при изпращане на мнението. Моля, опитайте отново.');
            }
        });
    });
});
</script>

<style>
.parfume-single-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.parfume-content-wrapper {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 40px;
    align-items: flex-start;
}

.parfume-left-column {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.parfume-header {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.parfume-image {
    flex: 0 0 250px;
    height: 300px;
    border-radius: 8px;
    overflow: hidden;
}

.parfume-featured-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.parfume-placeholder-image {
    width: 100%;
    height: 100%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 48px;
}

.parfume-header-info {
    flex: 1;
}

.parfume-title {
    font-size: 2.5em;
    margin: 0 0 20px 0;
    color: #333;
    line-height: 1.2;
}

.parfume-meta {
    margin-bottom: 20px;
}

.parfume-meta > div {
    margin-bottom: 12px;
}

.parfume-brand a {
    color: #007cba;
    text-decoration: none;
    font-weight: 500;
}

.parfume-brand a:hover {
    text-decoration: underline;
}

.comparison-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.comparison-btn:hover {
    background: #218838;
    transform: translateY(-2px);
}

.parfume-basic-notes {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}

.basic-notes-list {
    margin-top: 10px;
    color: #666;
}

.parfume-suitable-conditions {
    background: #e3f2fd;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.suitable-icons {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.suitable-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    padding: 8px 15px;
    border-radius: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.suitable-icon {
    font-size: 18px;
}

.suitable-label {
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

.parfume-content {
    font-size: 1.1em;
    line-height: 1.6;
    color: #555;
    margin-bottom: 40px;
}

.parfume-composition {
    margin-bottom: 40px;
}

.parfume-composition h3 {
    font-size: 1.8em;
    margin-bottom: 25px;
    color: #333;
}

.composition-pyramid {
    display: grid;
    gap: 20px;
}

.notes-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #007cba;
}

.notes-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.2em;
}

.notes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.note-item {
    background: white;
    padding: 8px 12px;
    border-radius: 15px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    transition: all 0.3s ease;
}

.note-item:hover {
    background: #007cba;
    color: white;
    transform: translateY(-1px);
}

.note-item a {
    color: inherit;
    text-decoration: none;
}

.note-icon {
    width: 16px;
    height: 16px;
    object-fit: cover;
    border-radius: 2px;
}

.parfume-graphics {
    margin-bottom: 40px;
}

.parfume-graphics h3 {
    font-size: 1.8em;
    margin-bottom: 25px;
    color: #333;
}

.graphics-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.graphic-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.1em;
    text-align: center;
}

.progress-bars {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.progress-bar {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-label {
    min-width: 120px;
    font-size: 12px;
    color: #666;
}

.progress-track {
    flex: 1;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #007cba, #28a745);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.parfume-pros-cons {
    margin-bottom: 40px;
}

.parfume-pros-cons h3 {
    font-size: 1.8em;
    margin-bottom: 25px;
    color: #333;
}

.pros-cons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.pros-section h4 {
    color: #28a745;
    margin-bottom: 15px;
}

.cons-section h4 {
    color: #dc3545;
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
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.pro-icon {
    color: #28a745;
    font-weight: bold;
}

.con-icon {
    color: #dc3545;
    font-weight: bold;
}

.parfume-similar,
.parfume-recently-viewed,
.parfume-from-brand {
    margin-bottom: 40px;
}

.parfume-similar h3,
.parfume-recently-viewed h3,
.parfume-from-brand h3 {
    font-size: 1.8em;
    margin-bottom: 25px;
    color: #333;
}

.similar-parfumes-grid,
.recently-viewed-grid,
.brand-parfumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
}

.similar-parfume-item,
.recent-parfume-item,
.brand-parfume-item {
    text-align: center;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.similar-parfume-item:hover,
.recent-parfume-item:hover,
.brand-parfume-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.similar-parfume-item a,
.recent-parfume-item a,
.brand-parfume-item a {
    color: inherit;
    text-decoration: none;
}

.similar-parfume-image,
.recent-parfume-image,
.brand-parfume-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
}

.similar-parfume-placeholder,
.recent-parfume-placeholder,
.brand-parfume-placeholder {
    width: 100%;
    height: 120px;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 24px;
    border-radius: 5px;
    margin-bottom: 10px;
}

.similar-parfume-title,
.recent-parfume-title,
.brand-parfume-title {
    font-size: 14px;
    line-height: 1.3;
    margin: 0;
}

.parfume-comments-section {
    margin-top: 40px;
    border-top: 2px solid #eee;
    padding-top: 40px;
}

.parfume-comments-section h3 {
    font-size: 1.8em;
    margin-bottom: 25px;
    color: #333;
}

.parfume-comment-form {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.parfume-comment-form h4 {
    margin-bottom: 20px;
    color: #333;
}

.comment-form-fields {
    display: grid;
    gap: 20px;
}

.comment-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.comment-field input,
.comment-field textarea {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.rating-stars {
    display: flex;
    gap: 5px;
    margin-bottom: 10px;
}

.rating-stars .star {
    font-size: 24px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.3s ease;
}

.rating-stars .star:hover,
.rating-stars .star.active {
    color: #ffc107;
}

.submit-comment-btn {
    background: #007cba;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    margin-top: 20px;
}

.submit-comment-btn:hover {
    background: #005a87;
}

.parfume-right-column {
    position: sticky;
    top: 20px;
}

.parfume-stores-sidebar {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.parfume-stores-sidebar h3 {
    font-size: 1.5em;
    margin-bottom: 15px;
    color: #333;
}

.stores-intro {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
}

.stores-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.store-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    background: #fafafa;
}

.store-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.store-logo {
    flex: 0 0 80px;
    height: 40px;
    display: flex;
    align-items: center;
}

.store-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.store-name {
    font-weight: 600;
    font-size: 14px;
}

.store-price {
    text-align: right;
    position: relative;
}

.old-price {
    text-decoration: line-through;
    color: #999;
    font-size: 12px;
    display: block;
}

.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #007cba;
    display: block;
}

.discount-percent {
    font-size: 12px;
    color: #28a745;
    font-weight: 500;
    display: block;
}

.price-update-info {
    position: relative;
    margin-top: 5px;
}

.update-icon {
    color: #999;
    cursor: help;
}

.store-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 14px;
}

.availability-status {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #28a745;
}

.status-icon {
    font-size: 12px;
}

.store-variants {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.variant-btn {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 8px 12px;
    text-decoration: none;
    color: #333;
    font-size: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    transition: all 0.3s ease;
}

.variant-btn:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

.variant-btn.on-sale {
    border-color: #28a745;
    background: #f8fff8;
}

.sale-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.store-actions {
    display: flex;
    gap: 10px;
}

.store-btn {
    flex: 1;
    padding: 12px 20px;
    border-radius: 5px;
    text-decoration: none;
    text-align: center;
    font-weight: 500;
    transition: all 0.3s ease;
}

.store-btn.primary {
    background: #ff6b35;
    color: white;
}

.store-btn.primary:hover {
    background: #e55a2e;
}

.promo-code-btn {
    flex: 1;
    border: 2px dashed #dc3545;
    border-radius: 5px;
    padding: 8px 12px;
    cursor: pointer;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.promo-code-btn:hover {
    background: #fff5f5;
}

.promo-info {
    font-size: 10px;
    color: #666;
    display: block;
    margin-bottom: 3px;
}

.promo-code {
    font-weight: 700;
    color: #dc3545;
    font-size: 14px;
    display: block;
}

.copy-icon {
    font-size: 12px;
    color: #999;
    margin-left: 5px;
}

.price-update-note {
    text-align: center;
    margin-top: 20px;
    color: #666;
}

.no-stores {
    text-align: center;
    color: #666;
    font-style: italic;
}

@media (max-width: 768px) {
    .parfume-content-wrapper {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .parfume-header {
        flex-direction: column;
        text-align: center;
    }
    
    .parfume-image {
        flex: none;
        width: 250px;
        margin: 0 auto;
    }
    
    .parfume-title {
        font-size: 2em;
    }
    
    .graphics-row {
        grid-template-columns: 1fr;
    }
    
    .pros-cons-grid {
        grid-template-columns: 1fr;
    }
    
    .similar-parfumes-grid,
    .recently-viewed-grid,
    .brand-parfumes-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .parfume-right-column {
        position: static;
    }
    
    .store-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .store-actions {
        flex-direction: column;
    }
}
</style>

<?php get_footer(); ?>