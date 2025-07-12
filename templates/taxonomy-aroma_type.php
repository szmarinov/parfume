<?php
/**
 * Taxonomy template for Aroma Type pages
 * 
 * Displays parfumes by aroma type with fragrance pyramid,
 * notes analysis, compatibility suggestions, and seasonal recommendations
 * 
 * @package ParfumeReviews
 * @subpackage Templates
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Получаване на текущия aroma type term
$current_term = get_queried_object();
$aroma_type_id = $current_term->term_id;
$aroma_type_slug = $current_term->slug;

// Получаване на aroma type meta данни
$aroma_description_extended = get_term_meta($aroma_type_id, 'aroma-description-extended', true);
$aroma_characteristics = get_term_meta($aroma_type_id, 'aroma-characteristics', true);
$aroma_personality = get_term_meta($aroma_type_id, 'aroma-personality', true);
$aroma_occasions = get_term_meta($aroma_type_id, 'aroma-occasions', true);
$aroma_season_compatibility = get_term_meta($aroma_type_id, 'aroma-season-compatibility', true);
$aroma_color_scheme = get_term_meta($aroma_type_id, 'aroma-color-scheme', true);
$aroma_icon = get_term_meta($aroma_type_id, 'aroma-icon', true);

// Анализ на парфюмите в тази категория
$aroma_query = new WP_Query(array(
    'post_type' => 'parfume',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'aroma_type',
            'field' => 'term_id',
            'terms' => $aroma_type_id
        )
    )
));

// Статистически анализ
$total_parfumes = $aroma_query->found_posts;
$avg_rating = 0;
$gender_distribution = array('male' => 0, 'female' => 0, 'unisex' => 0);
$intensity_distribution = array();
$seasonal_preferences = array('spring' => 0, 'summer' => 0, 'autumn' => 0, 'winter' => 0);
$popular_brands = array();
$notes_analysis = array('top' => array(), 'middle' => array(), 'base' => array());
$price_ranges = array('budget' => 0, 'mid' => 0, 'luxury' => 0, 'niche' => 0);
$release_decades = array();
$highest_rated = null;
$most_versatile = null;

if ($aroma_query->have_posts()) {
    $ratings_sum = 0;
    $ratings_count = 0;
    $max_rating = 0;
    $max_seasons = 0;
    
    while ($aroma_query->have_posts()) {
        $aroma_query->the_post();
        $post_id = get_the_ID();
        
        // Rating анализ
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        if (!empty($rating) && is_numeric($rating)) {
            $rating_float = floatval($rating);
            $ratings_sum += $rating_float;
            $ratings_count++;
            
            if ($rating_float > $max_rating) {
                $max_rating = $rating_float;
                $highest_rated = $post_id;
            }
        }
        
        // Gender анализ
        $genders = wp_get_post_terms($post_id, 'gender');
        foreach ($genders as $gender) {
            $gender_key = strtolower($gender->slug);
            if (isset($gender_distribution[$gender_key])) {
                $gender_distribution[$gender_key]++;
            }
        }
        
        // Intensity анализ
        $intensities = wp_get_post_terms($post_id, 'intensity');
        foreach ($intensities as $intensity) {
            if (!isset($intensity_distribution[$intensity->slug])) {
                $intensity_distribution[$intensity->slug] = array(
                    'name' => $intensity->name,
                    'count' => 0
                );
            }
            $intensity_distribution[$intensity->slug]['count']++;
        }
        
        // Season анализ (за versatility)
        $seasons = wp_get_post_terms($post_id, 'season');
        $season_count = count($seasons);
        if ($season_count > $max_seasons) {
            $max_seasons = $season_count;
            $most_versatile = $post_id;
        }
        
        foreach ($seasons as $season) {
            if (isset($seasonal_preferences[$season->slug])) {
                $seasonal_preferences[$season->slug]++;
            }
        }
        
        // Brand анализ
        $brands = wp_get_post_terms($post_id, 'marki');
        foreach ($brands as $brand) {
            if (!isset($popular_brands[$brand->term_id])) {
                $popular_brands[$brand->term_id] = array(
                    'name' => $brand->name,
                    'count' => 0,
                    'avg_rating' => 0,
                    'ratings_sum' => 0
                );
            }
            $popular_brands[$brand->term_id]['count']++;
            if (!empty($rating)) {
                $popular_brands[$brand->term_id]['ratings_sum'] += $rating_float;
                $popular_brands[$brand->term_id]['avg_rating'] = $popular_brands[$brand->term_id]['ratings_sum'] / $popular_brands[$brand->term_id]['count'];
            }
        }
        
        // Notes анализ
        $notes = wp_get_post_terms($post_id, 'notes');
        foreach ($notes as $note) {
            $note_category = get_term_meta($note->term_id, 'note-category', true) ?: 'middle';
            if (!isset($notes_analysis[$note_category])) {
                $notes_analysis[$note_category] = array();
            }
            if (!isset($notes_analysis[$note_category][$note->slug])) {
                $notes_analysis[$note_category][$note->slug] = array(
                    'name' => $note->name,
                    'count' => 0
                );
            }
            $notes_analysis[$note_category][$note->slug]['count']++;
        }
        
        // Price анализ
        $price_level = get_post_meta($post_id, '_parfume_price_level', true);
        if (!empty($price_level)) {
            switch (intval($price_level)) {
                case 1: case 2: $price_ranges['budget']++; break;
                case 3: $price_ranges['mid']++; break;
                case 4: $price_ranges['luxury']++; break;
                case 5: $price_ranges['niche']++; break;
            }
        }
        
        // Release decade анализ
        $release_year = get_post_meta($post_id, '_parfume_release_year', true);
        if (!empty($release_year) && is_numeric($release_year)) {
            $decade = floor(intval($release_year) / 10) * 10;
            if (!isset($release_decades[$decade])) {
                $release_decades[$decade] = 0;
            }
            $release_decades[$decade]++;
        }
    }
    
    if ($ratings_count > 0) {
        $avg_rating = round($ratings_sum / $ratings_count, 1);
    }
}

wp_reset_postdata();

// Сортиране на анализите
uasort($popular_brands, function($a, $b) { return $b['count'] - $a['count']; });
foreach ($notes_analysis as $category => &$notes) {
    uasort($notes, function($a, $b) { return $b['count'] - $a['count']; });
}
krsort($release_decades);

// Aroma-specific theming
$primary_color = !empty($aroma_color_scheme) ? $aroma_color_scheme : '#e17055';
$aroma_class = 'aroma-' . sanitize_html_class($aroma_type_slug);

// Определяне на икона по подразбиране според aroma type
$default_icons = array(
    'floral' => '🌸',
    'oriental' => '🌟', 
    'woody' => '🌳',
    'fresh' => '🍃',
    'citrus' => '🍋',
    'fruity' => '🍑',
    'gourmand' => '🍰',
    'aromatic' => '🌿',
    'chypre' => '🍂',
    'fougere' => '🌱',
    'aquatic' => '🌊',
    'spicy' => '🌶️'
);

$display_icon = !empty($aroma_icon) ? $aroma_icon : ($default_icons[$aroma_type_slug] ?? '🌺');
?>

<div class="aroma-type-wrap <?php echo esc_attr($aroma_class); ?>" style="--aroma-primary-color: <?php echo esc_attr($primary_color); ?>">
    <div class="container">
        
        <?php
        /**
         * Hook: parfume_reviews_aroma_before_header
         * 
         * @hooked parfume_reviews_breadcrumbs - 10
         */
        do_action('parfume_reviews_aroma_before_header');
        ?>
        
        <!-- Aroma Type Header -->
        <header class="aroma-header">
            <div class="aroma-header-background">
                <div class="aroma-pattern-overlay"></div>
            </div>
            
            <div class="aroma-header-content">
                <div class="aroma-main-info">
                    <div class="aroma-icon-section">
                        <div class="aroma-main-icon">
                            <span class="icon-display"><?php echo wp_kses_post($display_icon); ?></span>
                        </div>
                        
                        <!-- Aroma Characteristics Quick Tags -->
                        <?php if (!empty($aroma_characteristics) && is_array($aroma_characteristics)) : ?>
                            <div class="aroma-quick-tags">
                                <?php foreach (array_slice($aroma_characteristics, 0, 3) as $characteristic) : ?>
                                    <span class="characteristic-tag"><?php echo esc_html($characteristic); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="aroma-title-section">
                        <h1 class="aroma-title">
                            <?php echo esc_html($current_term->name); ?>
                            <span class="aroma-subtitle"><?php esc_html_e('аромат', 'parfume-reviews'); ?></span>
                        </h1>
                        
                        <?php if (!empty($current_term->description)) : ?>
                            <p class="aroma-tagline"><?php echo esc_html($current_term->description); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($aroma_description_extended)) : ?>
                            <div class="aroma-description-extended">
                                <?php echo wp_kses_post(wpautop($aroma_description_extended)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="aroma-quick-stats">
                    <div class="quick-stats-grid">
                        <div class="quick-stat-item">
                            <div class="stat-icon">📊</div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo esc_html($total_parfumes); ?></div>
                                <div class="stat-label"><?php esc_html_e('Парфюма', 'parfume-reviews'); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($avg_rating > 0) : ?>
                            <div class="quick-stat-item">
                                <div class="stat-icon">⭐</div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo esc_html($avg_rating); ?></div>
                                    <div class="stat-label"><?php esc_html_e('Средна оценка', 'parfume-reviews'); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="quick-stat-item">
                            <div class="stat-icon">🏷️</div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo esc_html(count(array_filter($popular_brands))); ?></div>
                                <div class="stat-label"><?php esc_html_e('Марки', 'parfume-reviews'); ?></div>
                            </div>
                        </div>
                        
                        <div class="quick-stat-item">
                            <div class="stat-icon">🌿</div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo esc_html(array_sum(array_map(function($notes) { return count($notes); }, $notes_analysis))); ?></div>
                                <div class="stat-label"><?php esc_html_e('Уникални ноти', 'parfume-reviews'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <?php
        /**
         * Hook: parfume_reviews_aroma_after_header
         */
        do_action('parfume_reviews_aroma_after_header');
        ?>
        
        <!-- Fragrance Analysis Section -->
        <section class="fragrance-analysis-section">
            <h2 class="section-title">
                <span class="title-icon">🔬</span>
                <span class="title-text"><?php esc_html_e('Анализ на ароматния профил', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="analysis-grid">
                
                <!-- Fragrance Pyramid -->
                <div class="analysis-card fragrance-pyramid-card">
                    <h3 class="card-title"><?php esc_html_e('Ароматна пирамида', 'parfume-reviews'); ?></h3>
                    <div class="fragrance-pyramid">
                        
                        <!-- Top Notes -->
                        <div class="pyramid-level top-notes">
                            <div class="level-header">
                                <h4 class="level-title"><?php esc_html_e('Горни ноти', 'parfume-reviews'); ?></h4>
                                <span class="level-timing">(0-15 мин)</span>
                            </div>
                            <div class="notes-cloud">
                                <?php 
                                $top_notes = array_slice($notes_analysis['top'] ?? array(), 0, 8, true);
                                foreach ($top_notes as $note_slug => $note_data) :
                                    $size_class = $note_data['count'] > 5 ? 'large' : ($note_data['count'] > 2 ? 'medium' : 'small');
                                ?>
                                    <span class="note-bubble <?php echo esc_attr($size_class); ?>" data-count="<?php echo esc_attr($note_data['count']); ?>">
                                        <?php echo esc_html($note_data['name']); ?>
                                        <span class="note-count"><?php echo esc_html($note_data['count']); ?></span>
                                    </span>
                                <?php endforeach; ?>
                                
                                <?php if (empty($top_notes)) : ?>
                                    <span class="no-notes"><?php esc_html_e('Няма данни за горни ноти', 'parfume-reviews'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Middle Notes -->
                        <div class="pyramid-level middle-notes">
                            <div class="level-header">
                                <h4 class="level-title"><?php esc_html_e('Средни ноти (сърце)', 'parfume-reviews'); ?></h4>
                                <span class="level-timing">(15 мин - 4 часа)</span>
                            </div>
                            <div class="notes-cloud">
                                <?php 
                                $middle_notes = array_slice($notes_analysis['middle'] ?? array(), 0, 12, true);
                                foreach ($middle_notes as $note_slug => $note_data) :
                                    $size_class = $note_data['count'] > 5 ? 'large' : ($note_data['count'] > 2 ? 'medium' : 'small');
                                ?>
                                    <span class="note-bubble <?php echo esc_attr($size_class); ?>" data-count="<?php echo esc_attr($note_data['count']); ?>">
                                        <?php echo esc_html($note_data['name']); ?>
                                        <span class="note-count"><?php echo esc_html($note_data['count']); ?></span>
                                    </span>
                                <?php endforeach; ?>
                                
                                <?php if (empty($middle_notes)) : ?>
                                    <span class="no-notes"><?php esc_html_e('Няма данни за средни ноти', 'parfume-reviews'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Base Notes -->
                        <div class="pyramid-level base-notes">
                            <div class="level-header">
                                <h4 class="level-title"><?php esc_html_e('Базови ноти', 'parfume-reviews'); ?></h4>
                                <span class="level-timing">(4+ часа)</span>
                            </div>
                            <div class="notes-cloud">
                                <?php 
                                $base_notes = array_slice($notes_analysis['base'] ?? array(), 0, 10, true);
                                foreach ($base_notes as $note_slug => $note_data) :
                                    $size_class = $note_data['count'] > 5 ? 'large' : ($note_data['count'] > 2 ? 'medium' : 'small');
                                ?>
                                    <span class="note-bubble <?php echo esc_attr($size_class); ?>" data-count="<?php echo esc_attr($note_data['count']); ?>">
                                        <?php echo esc_html($note_data['name']); ?>
                                        <span class="note-count"><?php echo esc_html($note_data['count']); ?></span>
                                    </span>
                                <?php endforeach; ?>
                                
                                <?php if (empty($base_notes)) : ?>
                                    <span class="no-notes"><?php esc_html_e('Няма данни за базови ноти', 'parfume-reviews'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Gender & Intensity Distribution -->
                <div class="analysis-card distribution-card">
                    <h3 class="card-title"><?php esc_html_e('Разпределение по пол и интензивност', 'parfume-reviews'); ?></h3>
                    
                    <!-- Gender Distribution -->
                    <div class="distribution-section">
                        <h4 class="distribution-title"><?php esc_html_e('По пол', 'parfume-reviews'); ?></h4>
                        <div class="gender-distribution">
                            <?php
                            $total_gender = array_sum($gender_distribution);
                            if ($total_gender > 0) :
                                foreach ($gender_distribution as $gender => $count) :
                                    if ($count > 0) :
                                        $percentage = round(($count / $total_gender) * 100);
                            ?>
                                        <div class="gender-item">
                                            <div class="gender-icon gender-<?php echo esc_attr($gender); ?>">
                                                <?php
                                                switch ($gender) {
                                                    case 'male': echo '♂'; break;
                                                    case 'female': echo '♀'; break;
                                                    case 'unisex': echo '⚲'; break;
                                                }
                                                ?>
                                            </div>
                                            <div class="gender-info">
                                                <div class="gender-name">
                                                    <?php
                                                    switch ($gender) {
                                                        case 'male': esc_html_e('Мъжки', 'parfume-reviews'); break;
                                                        case 'female': esc_html_e('Дамски', 'parfume-reviews'); break;
                                                        case 'unisex': esc_html_e('Унисекс', 'parfume-reviews'); break;
                                                    }
                                                    ?>
                                                </div>
                                                <div class="gender-stats">
                                                    <span class="count"><?php echo esc_html($count); ?></span>
                                                    <span class="percentage">(<?php echo esc_html($percentage); ?>%)</span>
                                                </div>
                                            </div>
                                            <div class="gender-bar">
                                                <div class="gender-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                            </div>
                                        </div>
                            <?php
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                    
                    <!-- Intensity Distribution -->
                    <div class="distribution-section">
                        <h4 class="distribution-title"><?php esc_html_e('По интензивност', 'parfume-reviews'); ?></h4>
                        <div class="intensity-distribution">
                            <?php
                            $total_intensity = array_sum(array_column($intensity_distribution, 'count'));
                            if ($total_intensity > 0) :
                                foreach ($intensity_distribution as $intensity_slug => $intensity_data) :
                                    $percentage = round(($intensity_data['count'] / $total_intensity) * 100);
                            ?>
                                    <div class="intensity-item">
                                        <div class="intensity-bar-container">
                                            <div class="intensity-bar intensity-<?php echo esc_attr($intensity_slug); ?>" style="height: <?php echo esc_attr($percentage); ?>%"></div>
                                        </div>
                                        <div class="intensity-label"><?php echo esc_html($intensity_data['name']); ?></div>
                                        <div class="intensity-count"><?php echo esc_html($intensity_data['count']); ?></div>
                                    </div>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Seasonal Compatibility -->
                <div class="analysis-card seasonal-compatibility-card">
                    <h3 class="card-title"><?php esc_html_e('Сезонна съвместимост', 'parfume-reviews'); ?></h3>
                    <div class="seasonal-radar">
                        <?php
                        $total_seasonal = array_sum($seasonal_preferences);
                        if ($total_seasonal > 0) :
                            $seasons_data = array(
                                'spring' => array('name' => __('Пролет', 'parfume-reviews'), 'emoji' => '🌸', 'angle' => 0),
                                'summer' => array('name' => __('Лято', 'parfume-reviews'), 'emoji' => '☀️', 'angle' => 90),
                                'autumn' => array('name' => __('Есен', 'parfume-reviews'), 'emoji' => '🍂', 'angle' => 180),
                                'winter' => array('name' => __('Зима', 'parfume-reviews'), 'emoji' => '❄️', 'angle' => 270)
                            );
                        ?>
                            <div class="radar-chart">
                                <div class="radar-center"></div>
                                <?php foreach ($seasons_data as $season_slug => $season_info) : ?>
                                    <?php
                                    $count = $seasonal_preferences[$season_slug] ?? 0;
                                    $percentage = $count > 0 ? round(($count / $total_seasonal) * 100) : 0;
                                    $radius = 40 + ($percentage * 1.5); // Базов радиус + процентно увеличение
                                    ?>
                                    <div class="radar-point season-<?php echo esc_attr($season_slug); ?>" 
                                         style="--angle: <?php echo esc_attr($season_info['angle']); ?>deg; --radius: <?php echo esc_attr($radius); ?>px;" 
                                         data-season="<?php echo esc_attr($season_slug); ?>"
                                         data-count="<?php echo esc_attr($count); ?>"
                                         data-percentage="<?php echo esc_attr($percentage); ?>">
                                        <div class="season-marker">
                                            <span class="season-emoji"><?php echo esc_html($season_info['emoji']); ?></span>
                                            <div class="season-tooltip">
                                                <div class="tooltip-content">
                                                    <strong><?php echo esc_html($season_info['name']); ?></strong><br>
                                                    <?php echo esc_html($count); ?> <?php esc_html_e('парфюма', 'parfume-reviews'); ?> (<?php echo esc_html($percentage); ?>%)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="seasonal-legend">
                                <?php foreach ($seasons_data as $season_slug => $season_info) : ?>
                                    <?php
                                    $count = $seasonal_preferences[$season_slug] ?? 0;
                                    $percentage = $count > 0 ? round(($count / $total_seasonal) * 100) : 0;
                                    ?>
                                    <div class="legend-item season-<?php echo esc_attr($season_slug); ?>">
                                        <span class="legend-emoji"><?php echo esc_html($season_info['emoji']); ?></span>
                                        <span class="legend-name"><?php echo esc_html($season_info['name']); ?></span>
                                        <span class="legend-stats"><?php echo esc_html($count); ?> (<?php echo esc_html($percentage); ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Personality & Occasions -->
                <?php if (!empty($aroma_personality) || !empty($aroma_occasions)) : ?>
                    <div class="analysis-card personality-occasions-card">
                        <h3 class="card-title"><?php esc_html_e('Личност и повод', 'parfume-reviews'); ?></h3>
                        
                        <?php if (!empty($aroma_personality) && is_array($aroma_personality)) : ?>
                            <div class="personality-section">
                                <h4 class="subsection-title"><?php esc_html_e('Тип личност', 'parfume-reviews'); ?></h4>
                                <div class="personality-traits">
                                    <?php foreach ($aroma_personality as $trait) : ?>
                                        <span class="personality-trait"><?php echo esc_html($trait); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($aroma_occasions) && is_array($aroma_occasions)) : ?>
                            <div class="occasions-section">
                                <h4 class="subsection-title"><?php esc_html_e('Подходящи поводи', 'parfume-reviews'); ?></h4>
                                <div class="occasions-grid">
                                    <?php foreach ($aroma_occasions as $occasion) : ?>
                                        <div class="occasion-item">
                                            <span class="occasion-icon">📅</span>
                                            <span class="occasion-name"><?php echo esc_html($occasion); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            </div>
        </section>
        
        <!-- Featured Highlights -->
        <section class="aroma-highlights-section">
            <h2 class="section-title">
                <span class="title-icon">🌟</span>
                <span class="title-text"><?php esc_html_e('Избрани представители', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="highlights-grid">
                
                <!-- Highest Rated -->
                <?php if ($highest_rated) : ?>
                    <div class="highlight-card highest-rated-card">
                        <div class="card-badge badge-rating"><?php esc_html_e('Най-високо оценен', 'parfume-reviews'); ?></div>
                        <?php
                        $rated_post = get_post($highest_rated);
                        $rated_brands = wp_get_post_terms($highest_rated, 'marki');
                        $rated_rating = get_post_meta($highest_rated, '_parfume_rating', true);
                        ?>
                        <div class="highlight-image">
                            <?php if (has_post_thumbnail($highest_rated)) : ?>
                                <a href="<?php echo get_permalink($highest_rated); ?>">
                                    <?php echo get_the_post_thumbnail($highest_rated, 'medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">
                                <a href="<?php echo get_permalink($highest_rated); ?>">
                                    <?php echo esc_html($rated_post->post_title); ?>
                                </a>
                            </h3>
                            <?php if (!empty($rated_brands)) : ?>
                                <div class="highlight-brand"><?php echo esc_html($rated_brands[0]->name); ?></div>
                            <?php endif; ?>
                            <div class="highlight-rating">
                                <div class="rating-stars">
                                    <?php
                                    $rating = floatval($rated_rating);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<span class="star filled">★</span>';
                                        } elseif ($i - 0.5 <= $rating) {
                                            echo '<span class="star half">☆</span>';
                                        } else {
                                            echo '<span class="star empty">☆</span>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="rating-number"><?php echo esc_html($rated_rating); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Most Versatile -->
                <?php if ($most_versatile && $most_versatile !== $highest_rated) : ?>
                    <div class="highlight-card most-versatile-card">
                        <div class="card-badge badge-versatile"><?php esc_html_e('Най-универсален', 'parfume-reviews'); ?></div>
                        <?php
                        $versatile_post = get_post($most_versatile);
                        $versatile_brands = wp_get_post_terms($most_versatile, 'marki');
                        $versatile_seasons = wp_get_post_terms($most_versatile, 'season');
                        ?>
                        <div class="highlight-image">
                            <?php if (has_post_thumbnail($most_versatile)) : ?>
                                <a href="<?php echo get_permalink($most_versatile); ?>">
                                    <?php echo get_the_post_thumbnail($most_versatile, 'medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">
                                <a href="<?php echo get_permalink($most_versatile); ?>">
                                    <?php echo esc_html($versatile_post->post_title); ?>
                                </a>
                            </h3>
                            <?php if (!empty($versatile_brands)) : ?>
                                <div class="highlight-brand"><?php echo esc_html($versatile_brands[0]->name); ?></div>
                            <?php endif; ?>
                            <div class="highlight-seasons">
                                <span class="seasons-label"><?php esc_html_e('Сезони:', 'parfume-reviews'); ?></span>
                                <?php foreach ($versatile_seasons as $season) : ?>
                                    <span class="season-tag"><?php echo esc_html($season->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Random Representative -->
                <?php
                $representative_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                    'orderby' => 'rand',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'aroma_type',
                            'field' => 'term_id',
                            'terms' => $aroma_type_id
                        )
                    ),
                    'post__not_in' => array_filter(array($highest_rated, $most_versatile))
                ));
                
                if ($representative_query->have_posts()) :
                    $representative_query->the_post();
                    $rep_brands = wp_get_post_terms(get_the_ID(), 'marki');
                    $rep_rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                ?>
                    <div class="highlight-card representative-card">
                        <div class="card-badge badge-representative"><?php esc_html_e('Типичен представител', 'parfume-reviews'); ?></div>
                        <div class="highlight-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <?php if (!empty($rep_brands)) : ?>
                                <div class="highlight-brand"><?php echo esc_html($rep_brands[0]->name); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($rep_rating)) : ?>
                                <div class="highlight-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $rating = floatval($rep_rating);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<span class="star filled">★</span>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<span class="star half">☆</span>';
                                            } else {
                                                echo '<span class="star empty">☆</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-number"><?php echo esc_html($rep_rating); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                endif;
                wp_reset_postdata();
                ?>
                
            </div>
        </section>
        
        <!-- Main Parfumes Grid -->
        <section class="aroma-parfumes-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon">🌺</span>
                    <span class="title-text">
                        <?php
                        printf(
                            /* translators: %s: aroma type name */
                            esc_html__('Всички %s парфюми', 'parfume-reviews'),
                            esc_html($current_term->name)
                        );
                        ?>
                    </span>
                </h2>
                
                <!-- Advanced Filters -->
                <div class="aroma-filters">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="sort-parfumes"><?php esc_html_e('Сортиране:', 'parfume-reviews'); ?></label>
                            <select id="sort-parfumes" name="sort">
                                <option value="rating"><?php esc_html_e('По оценка', 'parfume-reviews'); ?></option>
                                <option value="date"><?php esc_html_e('По дата добавяне', 'parfume-reviews'); ?></option>
                                <option value="title"><?php esc_html_e('По име', 'parfume-reviews'); ?></option>
                                <option value="release_year"><?php esc_html_e('По година издаване', 'parfume-reviews'); ?></option>
                                <option value="popularity"><?php esc_html_e('По популярност', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter-gender"><?php esc_html_e('Пол:', 'parfume-reviews'); ?></label>
                            <select id="filter-gender" name="gender">
                                <option value=""><?php esc_html_e('Всички', 'parfume-reviews'); ?></option>
                                <option value="male"><?php esc_html_e('Мъжки', 'parfume-reviews'); ?></option>
                                <option value="female"><?php esc_html_e('Дамски', 'parfume-reviews'); ?></option>
                                <option value="unisex"><?php esc_html_e('Унисекс', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter-brand"><?php esc_html_e('Марка:', 'parfume-reviews'); ?></label>
                            <select id="filter-brand" name="brand">
                                <option value=""><?php esc_html_e('Всички марки', 'parfume-reviews'); ?></option>
                                <?php foreach (array_slice($popular_brands, 0, 15, true) as $brand_id => $brand_data) : ?>
                                    <option value="<?php echo esc_attr($brand_id); ?>">
                                        <?php echo esc_html($brand_data['name']); ?> (<?php echo esc_html($brand_data['count']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter-season"><?php esc_html_e('Сезон:', 'parfume-reviews'); ?></label>
                            <select id="filter-season" name="season">
                                <option value=""><?php esc_html_e('Всички сезони', 'parfume-reviews'); ?></option>
                                <option value="spring"><?php esc_html_e('Пролет', 'parfume-reviews'); ?></option>
                                <option value="summer"><?php esc_html_e('Лято', 'parfume-reviews'); ?></option>
                                <option value="autumn"><?php esc_html_e('Есен', 'parfume-reviews'); ?></option>
                                <option value="winter"><?php esc_html_e('Зима', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter-intensity"><?php esc_html_e('Интензивност:', 'parfume-reviews'); ?></label>
                            <select id="filter-intensity" name="intensity">
                                <option value=""><?php esc_html_e('Всички нива', 'parfume-reviews'); ?></option>
                                <?php foreach ($intensity_distribution as $intensity_slug => $intensity_data) : ?>
                                    <option value="<?php echo esc_attr($intensity_slug); ?>">
                                        <?php echo esc_html($intensity_data['name']); ?> (<?php echo esc_html($intensity_data['count']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <div class="view-controls">
                                <div class="view-toggle">
                                    <button class="view-btn active" data-view="grid" aria-label="<?php esc_attr_e('Мрежа', 'parfume-reviews'); ?>">
                                        <span class="dashicons dashicons-grid-view"></span>
                                    </button>
                                    <button class="view-btn" data-view="list" aria-label="<?php esc_attr_e('Списък', 'parfume-reviews'); ?>">
                                        <span class="dashicons dashicons-list-view"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filters-actions">
                        <button class="reset-filters-btn" type="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Изчисти филтрите', 'parfume-reviews'); ?>
                        </button>
                        
                        <div class="results-info">
                            <span class="results-count"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Loading State -->
            <div class="aroma-loading" style="display: none;">
                <div class="loading-content">
                    <div class="loading-spinner">
                        <span class="dashicons dashicons-update spin"></span>
                    </div>
                    <p><?php esc_html_e('Зареждане на парфюми...', 'parfume-reviews'); ?></p>
                </div>
            </div>
            
            <!-- Parfumes Grid -->
            <div class="aroma-parfumes-container" data-view="grid">
                <?php
                // Main parfumes query
                $main_parfumes_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 12,
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'aroma_type',
                            'field' => 'term_id',
                            'terms' => $aroma_type_id
                        )
                    ),
                    'meta_key' => '_parfume_rating',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ));
                
                if ($main_parfumes_query->have_posts()) : ?>
                    <div class="parfumes-grid" id="aroma-parfumes-grid">
                        <?php while ($main_parfumes_query->have_posts()) : $main_parfumes_query->the_post(); ?>
                            <?php get_template_part('template-parts/parfume-card', null, array('show_aroma' => false)); ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="aroma-pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $main_parfumes_query->max_num_pages,
                            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Предишна', 'parfume-reviews'),
                            'next_text' => esc_html__('Следваща', 'parfume-reviews') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                            'mid_size' => 2
                        ));
                        ?>
                    </div>
                    
                <?php else : ?>
                    <div class="no-parfumes-found">
                        <div class="no-content-icon">
                            <span class="icon"><?php echo wp_kses_post($display_icon); ?></span>
                        </div>
                        <h3><?php esc_html_e('Няма намерени парфюми', 'parfume-reviews'); ?></h3>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: aroma type name */
                                esc_html__('В момента няма парфюми от тип "%s" в нашата база данни.', 'parfume-reviews'),
                                esc_html($current_term->name)
                            );
                            ?>
                        </p>
                        <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="browse-all-btn">
                            <?php esc_html_e('Разгледай всички парфюми', 'parfume-reviews'); ?>
                        </a>
                    </div>
                <?php endif; wp_reset_postdata(); ?>
            </div>
        </section>
        
    </div>
</div>

<?php
/**
 * Hook: parfume_reviews_aroma_footer
 */
do_action('parfume_reviews_aroma_footer');
?>

<!-- Enhanced JavaScript for Aroma Type -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Fragrance Pyramid Note Interactions
    $('.note-bubble').on('mouseenter', function() {
        const count = $(this).data('count');
        const noteName = $(this).text().replace(/\d+$/, '').trim();
        
        // Show tooltip or enhance display
        $(this).attr('title', noteName + ' - използвана в ' + count + ' парфюма');
    });
    
    // Seasonal Radar Chart Interactions
    $('.season-marker').on('mouseenter', function() {
        const $tooltip = $(this).find('.season-tooltip');
        $tooltip.addClass('visible');
    }).on('mouseleave', function() {
        const $tooltip = $(this).find('.season-tooltip');
        $tooltip.removeClass('visible');
    });
    
    // Advanced Filtering System
    const $filters = $('#sort-parfumes, #filter-gender, #filter-brand, #filter-season, #filter-intensity');
    const $grid = $('#aroma-parfumes-grid');
    const $loading = $('.aroma-loading');
    const $resultsCount = $('.results-count');
    
    let filterTimeout;
    
    $filters.on('change', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 300);
    });
    
    function applyFilters() {
        // Show loading
        $loading.show();
        $grid.addClass('filtering');
        
        // Collect filter values
        const filters = {
            sort: $('#sort-parfumes').val(),
            gender: $('#filter-gender').val(),
            brand: $('#filter-brand').val(),
            season: $('#filter-season').val(),
            intensity: $('#filter-intensity').val()
        };
        
        // Simulate AJAX filtering (in production this would be a real AJAX call)
        setTimeout(function() {
            filterParfumesLocally(filters);
            $loading.hide();
            $grid.removeClass('filtering');
        }, 800);
    }
    
    function filterParfumesLocally(filters) {
        const $items = $grid.find('.parfume-item');
        let visibleCount = 0;
        
        $items.each(function() {
            let showItem = true;
            const $item = $(this);
            
            // Apply gender filter
            if (filters.gender && $item.data('gender') !== filters.gender) {
                showItem = false;
            }
            
            // Apply brand filter
            if (filters.brand && $item.data('brand-id') != filters.brand) {
                showItem = false;
            }
            
            // Apply season filter
            if (filters.season) {
                const itemSeasons = ($item.data('seasons') || '').split(',');
                if (!itemSeasons.includes(filters.season)) {
                    showItem = false;
                }
            }
            
            // Apply intensity filter
            if (filters.intensity && $item.data('intensity') !== filters.intensity) {
                showItem = false;
            }
            
            // Show/hide item
            if (showItem) {
                $item.show();
                visibleCount++;
            } else {
                $item.hide();
            }
        });
        
        // Update results count
        $resultsCount.text(visibleCount + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
        
        // Apply sorting
        if (filters.sort) {
            sortParfumes(filters.sort);
        }
    }
    
    function sortParfumes(sortBy) {
        const $visibleItems = $grid.find('.parfume-item:visible');
        
        $visibleItems.sort(function(a, b) {
            let aVal, bVal;
            
            switch (sortBy) {
                case 'rating':
                    aVal = parseFloat($(a).data('rating')) || 0;
                    bVal = parseFloat($(b).data('rating')) || 0;
                    return bVal - aVal;
                    
                case 'title':
                    aVal = $(a).find('.parfume-title').text().toLowerCase();
                    bVal = $(b).find('.parfume-title').text().toLowerCase();
                    return aVal.localeCompare(bVal);
                    
                case 'release_year':
                    aVal = parseInt($(a).data('release-year')) || 0;
                    bVal = parseInt($(b).data('release-year')) || 0;
                    return bVal - aVal;
                    
                default:
                    return 0;
            }
        });
        
        $grid.append($visibleItems);
    }
    
    // Reset filters
    $('.reset-filters-btn').on('click', function() {
        $filters.val('');
        $grid.find('.parfume-item').show();
        $resultsCount.text($grid.find('.parfume-item').length + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
    });
    
    // View toggle
    $('.view-toggle .view-btn').on('click', function(e) {
        e.preventDefault();
        
        const view = $(this).data('view');
        $('.view-toggle .view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.aroma-parfumes-container').attr('data-view', view);
        
        // Store preference
        localStorage.setItem('aroma_parfumes_view', view);
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('aroma_parfumes_view');
    if (savedView) {
        $('.view-toggle .view-btn[data-view="' + savedView + '"]').click();
    }
    
    // Animate charts on scroll
    function animateElements() {
        // Fragrance Pyramid Animation
        $('.pyramid-level').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).find('.note-bubble').each(function(index) {
                    $(this).delay(index * 50).fadeIn(300);
                });
            }
        });
        
        // Distribution Charts Animation
        $('.gender-fill, .intensity-bar').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                const width = $(this).css('width');
                const height = $(this).css('height');
                $(this).css({width: 0, height: 0}).animate({
                    width: width,
                    height: height
                }, 800, 'easeOutCubic');
            }
        });
        
        // Radar Chart Animation
        $('.radar-point').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).css('opacity', 0).delay(200).animate({opacity: 1}, 600);
            }
        });
    }
    
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    // Initialize animations
    $(window).on('scroll resize', animateElements);
    animateElements(); // Initial check
    
    // Enhanced card interactions
    $('.highlight-card').on('mouseenter', function() {
        $(this).addClass('hover-enhanced');
    }).on('mouseleave', function() {
        $(this).removeClass('hover-enhanced');
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });
    
    // Initialize results count
    $resultsCount.text($grid.find('.parfume-item').length + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
    
});
</script>

<?php get_footer(); ?>