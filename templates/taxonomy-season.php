<?php
/**
 * Taxonomy template for Season pages
 * 
 * Displays parfumes by season with climate analysis,
 * temperature recommendations, weather patterns, and seasonal trends
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

// Получаване на текущия season term
$current_term = get_queried_object();
$season_id = $current_term->term_id;
$season_slug = $current_term->slug;

// Получаване на season meta данни
$season_description_extended = get_term_meta($season_id, 'season-description-extended', true);
$season_temperature_range = get_term_meta($season_id, 'season-temperature-range', true);
$season_humidity_level = get_term_meta($season_id, 'season-humidity-level', true);
$season_occasions = get_term_meta($season_id, 'season-occasions', true);
$season_mood_keywords = get_term_meta($season_id, 'season-mood-keywords', true);
$season_color_palette = get_term_meta($season_id, 'season-color-palette', true);
$season_fragrance_families = get_term_meta($season_id, 'season-fragrance-families', true);
$season_wearing_tips = get_term_meta($season_id, 'season-wearing-tips', true);

// Анализ на парфюмите за този сезон
$season_query = new WP_Query(array(
    'post_type' => 'parfume',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'season',
            'field' => 'term_id',
            'terms' => $season_id
        )
    )
));

// Статистически анализ
$total_parfumes = $season_query->found_posts;
$avg_rating = 0;
$gender_distribution = array('male' => 0, 'female' => 0, 'unisex' => 0);
$intensity_preferences = array();
$aroma_type_distribution = array();
$price_ranges = array('budget' => 0, 'mid' => 0, 'luxury' => 0, 'niche' => 0);
$longevity_analysis = array('poor' => 0, 'weak' => 0, 'moderate' => 0, 'long' => 0, 'eternal' => 0);
$sillage_analysis = array('intimate' => 0, 'moderate' => 0, 'strong' => 0, 'enormous' => 0);
$top_brands = array();
$top_perfumers = array();
$notes_frequency = array();
$release_years = array();
$highest_rated = null;
$most_popular = null;
$best_longevity = null;

if ($season_query->have_posts()) {
    $ratings_sum = 0;
    $ratings_count = 0;
    $max_rating = 0;
    $max_longevity_score = 0;
    $most_popular_views = 0;
    
    while ($season_query->have_posts()) {
        $season_query->the_post();
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
            if (!isset($intensity_preferences[$intensity->slug])) {
                $intensity_preferences[$intensity->slug] = array(
                    'name' => $intensity->name,
                    'count' => 0,
                    'total_rating' => 0
                );
            }
            $intensity_preferences[$intensity->slug]['count']++;
            if (!empty($rating)) {
                $intensity_preferences[$intensity->slug]['total_rating'] += $rating_float;
            }
        }
        
        // Aroma type анализ
        $aroma_types = wp_get_post_terms($post_id, 'aroma_type');
        foreach ($aroma_types as $aroma_type) {
            if (!isset($aroma_type_distribution[$aroma_type->slug])) {
                $aroma_type_distribution[$aroma_type->slug] = array(
                    'name' => $aroma_type->name,
                    'count' => 0
                );
            }
            $aroma_type_distribution[$aroma_type->slug]['count']++;
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
        
        // Longevity анализ
        $longevity = get_post_meta($post_id, '_parfume_longevity', true);
        if (!empty($longevity)) {
            $longevity_normalized = strtolower(str_replace(array(' ', '-'), '_', $longevity));
            $longevity_score = 0;
            
            // Scoring система за longevity
            if (strpos($longevity_normalized, 'poor') !== false || strpos($longevity_normalized, 'very_weak') !== false) {
                $longevity_analysis['poor']++;
                $longevity_score = 1;
            } elseif (strpos($longevity_normalized, 'weak') !== false) {
                $longevity_analysis['weak']++;
                $longevity_score = 2;
            } elseif (strpos($longevity_normalized, 'moderate') !== false) {
                $longevity_analysis['moderate']++;
                $longevity_score = 3;
            } elseif (strpos($longevity_normalized, 'long') !== false) {
                $longevity_analysis['long']++;
                $longevity_score = 4;
            } elseif (strpos($longevity_normalized, 'eternal') !== false || strpos($longevity_normalized, 'excellent') !== false) {
                $longevity_analysis['eternal']++;
                $longevity_score = 5;
            }
            
            if ($longevity_score > $max_longevity_score) {
                $max_longevity_score = $longevity_score;
                $best_longevity = $post_id;
            }
        }
        
        // Sillage анализ
        $sillage = get_post_meta($post_id, '_parfume_sillage', true);
        if (!empty($sillage)) {
            $sillage_normalized = strtolower(str_replace(array(' ', '-'), '_', $sillage));
            if (strpos($sillage_normalized, 'intimate') !== false) {
                $sillage_analysis['intimate']++;
            } elseif (strpos($sillage_normalized, 'moderate') !== false) {
                $sillage_analysis['moderate']++;
            } elseif (strpos($sillage_normalized, 'strong') !== false) {
                $sillage_analysis['strong']++;
            } elseif (strpos($sillage_normalized, 'enormous') !== false) {
                $sillage_analysis['enormous']++;
            }
        }
        
        // Brands анализ
        $brands = wp_get_post_terms($post_id, 'marki');
        foreach ($brands as $brand) {
            if (!isset($top_brands[$brand->term_id])) {
                $top_brands[$brand->term_id] = array(
                    'name' => $brand->name,
                    'count' => 0,
                    'avg_rating' => 0,
                    'total_rating' => 0
                );
            }
            $top_brands[$brand->term_id]['count']++;
            if (!empty($rating)) {
                $top_brands[$brand->term_id]['total_rating'] += $rating_float;
                $top_brands[$brand->term_id]['avg_rating'] = $top_brands[$brand->term_id]['total_rating'] / $top_brands[$brand->term_id]['count'];
            }
        }
        
        // Perfumers анализ
        $perfumers = wp_get_post_terms($post_id, 'perfumer');
        foreach ($perfumers as $perfumer) {
            if (!isset($top_perfumers[$perfumer->term_id])) {
                $top_perfumers[$perfumer->term_id] = array(
                    'name' => $perfumer->name,
                    'count' => 0
                );
            }
            $top_perfumers[$perfumer->term_id]['count']++;
        }
        
        // Notes frequency
        $notes = wp_get_post_terms($post_id, 'notes');
        foreach ($notes as $note) {
            if (!isset($notes_frequency[$note->slug])) {
                $notes_frequency[$note->slug] = array(
                    'name' => $note->name,
                    'count' => 0
                );
            }
            $notes_frequency[$note->slug]['count']++;
        }
        
        // Release years
        $release_year = get_post_meta($post_id, '_parfume_release_year', true);
        if (!empty($release_year) && is_numeric($release_year)) {
            $year = intval($release_year);
            if (!isset($release_years[$year])) {
                $release_years[$year] = 0;
            }
            $release_years[$year]++;
        }
        
        // Most popular (simplified)
        $post_views = get_post_meta($post_id, '_post_views', true) ?: rand(50, 500);
        if ($post_views > $most_popular_views) {
            $most_popular_views = $post_views;
            $most_popular = $post_id;
        }
    }
    
    if ($ratings_count > 0) {
        $avg_rating = round($ratings_sum / $ratings_count, 1);
    }
    
    // Calculate average ratings for intensity preferences
    foreach ($intensity_preferences as $key => &$intensity) {
        if ($intensity['count'] > 0) {
            $intensity['avg_rating'] = round($intensity['total_rating'] / $intensity['count'], 1);
        }
    }
}

wp_reset_postdata();

// Сортиране на данните
uasort($top_brands, function($a, $b) { return $b['count'] - $a['count']; });
uasort($top_perfumers, function($a, $b) { return $b['count'] - $a['count']; });
uasort($aroma_type_distribution, function($a, $b) { return $b['count'] - $a['count']; });
uasort($notes_frequency, function($a, $b) { return $b['count'] - $a['count']; });
uasort($intensity_preferences, function($a, $b) { return $b['count'] - $a['count']; });
krsort($release_years);

// Season-specific configuration
$season_config = array(
    'spring' => array(
        'emoji' => '🌸',
        'color' => '#ff6b9d',
        'temp_range' => '10-20°C',
        'keywords' => array('fresh', 'floral', 'green', 'delicate'),
        'mood' => 'renewal, awakening, optimism'
    ),
    'summer' => array(
        'emoji' => '☀️',
        'color' => '#ffa726',
        'temp_range' => '20-35°C',
        'keywords' => array('fresh', 'citrus', 'aquatic', 'light'),
        'mood' => 'energetic, vibrant, carefree'
    ),
    'autumn' => array(
        'emoji' => '🍂',
        'color' => '#ff7043',
        'temp_range' => '5-20°C',
        'keywords' => array('warm', 'spicy', 'woody', 'cozy'),
        'mood' => 'nostalgic, sophisticated, comforting'
    ),
    'winter' => array(
        'emoji' => '❄️',
        'color' => '#42a5f5',
        'temp_range' => '-10-10°C',
        'keywords' => array('rich', 'intense', 'warm', 'gourmand'),
        'mood' => 'intimate, luxurious, enveloping'
    )
);

$current_season_config = $season_config[$season_slug] ?? array(
    'emoji' => '🌿',
    'color' => '#66bb6a',
    'temp_range' => 'Variable',
    'keywords' => array('versatile'),
    'mood' => 'adaptable'
);

$season_class = 'season-' . sanitize_html_class($season_slug);
?>

<div class="season-taxonomy-wrap <?php echo esc_attr($season_class); ?>" style="--season-primary-color: <?php echo esc_attr($current_season_config['color']); ?>">
    <div class="container">
        
        <?php
        /**
         * Hook: parfume_reviews_season_before_header
         * 
         * @hooked parfume_reviews_breadcrumbs - 10
         */
        do_action('parfume_reviews_season_before_header');
        ?>
        
        <!-- Season Header -->
        <header class="season-header">
            <div class="season-header-background">
                <div class="season-pattern season-<?php echo esc_attr($season_slug); ?>"></div>
                <div class="season-overlay"></div>
            </div>
            
            <div class="season-header-content">
                <div class="season-main-info">
                    <div class="season-icon-section">
                        <div class="season-main-icon">
                            <span class="emoji-icon"><?php echo esc_html($current_season_config['emoji']); ?></span>
                        </div>
                        
                        <div class="season-climate-info">
                            <div class="climate-item">
                                <span class="climate-icon">🌡️</span>
                                <span class="climate-value">
                                    <?php echo !empty($season_temperature_range) ? esc_html($season_temperature_range) : esc_html($current_season_config['temp_range']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($season_humidity_level)) : ?>
                                <div class="climate-item">
                                    <span class="climate-icon">💧</span>
                                    <span class="climate-value"><?php echo esc_html($season_humidity_level); ?>%</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="season-title-section">
                        <h1 class="season-title">
                            <?php
                            printf(
                                /* translators: %s: season name */
                                esc_html__('Парфюми за %s', 'parfume-reviews'),
                                esc_html($current_term->name)
                            );
                            ?>
                        </h1>
                        
                        <?php if (!empty($current_term->description)) : ?>
                            <p class="season-tagline"><?php echo esc_html($current_term->description); ?></p>
                        <?php endif; ?>
                        
                        <div class="season-mood-description">
                            <span class="mood-label"><?php esc_html_e('Настроение:', 'parfume-reviews'); ?></span>
                            <span class="mood-text"><?php echo esc_html($current_season_config['mood']); ?></span>
                        </div>
                        
                        <?php if (!empty($season_description_extended)) : ?>
                            <div class="season-description-extended">
                                <?php echo wp_kses_post(wpautop($season_description_extended)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Season Quick Stats -->
                <div class="season-quick-stats">
                    <div class="quick-stats-grid">
                        <div class="quick-stat-item">
                            <div class="stat-icon">🌺</div>
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
                                <div class="stat-number"><?php echo esc_html(count(array_filter($top_brands))); ?></div>
                                <div class="stat-label"><?php esc_html_e('Марки', 'parfume-reviews'); ?></div>
                            </div>
                        </div>
                        
                        <div class="quick-stat-item">
                            <div class="stat-icon">🎨</div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo esc_html(count(array_filter($aroma_type_distribution))); ?></div>
                                <div class="stat-label"><?php esc_html_e('Аромати', 'parfume-reviews'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <?php
        /**
         * Hook: parfume_reviews_season_after_header
         */
        do_action('parfume_reviews_season_after_header');
        ?>
        
        <!-- Climate & Performance Analysis -->
        <section class="season-climate-section">
            <h2 class="section-title">
                <span class="title-icon">🌤️</span>
                <span class="title-text"><?php esc_html_e('Климатичен анализ и производителност', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="climate-analysis-grid">
                
                <!-- Performance Metrics -->
                <div class="analysis-card performance-metrics-card">
                    <h3 class="card-title"><?php esc_html_e('Производителност в климата', 'parfume-reviews'); ?></h3>
                    
                    <!-- Longevity Analysis -->
                    <div class="performance-section">
                        <h4 class="performance-title"><?php esc_html_e('Издръжливост', 'parfume-reviews'); ?></h4>
                        <div class="longevity-chart">
                            <?php
                            $total_longevity = array_sum($longevity_analysis);
                            if ($total_longevity > 0) :
                                $longevity_labels = array(
                                    'poor' => __('Слаба (< 2ч)', 'parfume-reviews'),
                                    'weak' => __('Слабичка (2-4ч)', 'parfume-reviews'),
                                    'moderate' => __('Умерена (4-6ч)', 'parfume-reviews'),
                                    'long' => __('Дълга (6-12ч)', 'parfume-reviews'),
                                    'eternal' => __('Много дълга (12ч+)', 'parfume-reviews')
                                );
                                
                                foreach ($longevity_analysis as $level => $count) :
                                    if ($count > 0) :
                                        $percentage = round(($count / $total_longevity) * 100);
                            ?>
                                        <div class="longevity-item">
                                            <div class="longevity-bar longevity-<?php echo esc_attr($level); ?>" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                            <div class="longevity-info">
                                                <span class="longevity-label"><?php echo esc_html($longevity_labels[$level]); ?></span>
                                                <span class="longevity-stats"><?php echo esc_html($count); ?> (<?php echo esc_html($percentage); ?>%)</span>
                                            </div>
                                        </div>
                            <?php
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                    
                    <!-- Sillage Analysis -->
                    <div class="performance-section">
                        <h4 class="performance-title"><?php esc_html_e('Проекция (Sillage)', 'parfume-reviews'); ?></h4>
                        <div class="sillage-radar">
                            <?php
                            $total_sillage = array_sum($sillage_analysis);
                            if ($total_sillage > 0) :
                                $sillage_labels = array(
                                    'intimate' => __('Интимна', 'parfume-reviews'),
                                    'moderate' => __('Умерена', 'parfume-reviews'),
                                    'strong' => __('Силна', 'parfume-reviews'),
                                    'enormous' => __('Много силна', 'parfume-reviews')
                                );
                                
                                foreach ($sillage_analysis as $level => $count) :
                                    if ($count > 0) :
                                        $percentage = round(($count / $total_sillage) * 100);
                            ?>
                                        <div class="sillage-item sillage-<?php echo esc_attr($level); ?>">
                                            <div class="sillage-circle" style="width: <?php echo esc_attr(20 + $percentage); ?>px; height: <?php echo esc_attr(20 + $percentage); ?>px;"></div>
                                            <div class="sillage-label"><?php echo esc_html($sillage_labels[$level]); ?></div>
                                            <div class="sillage-percentage"><?php echo esc_html($percentage); ?>%</div>
                                        </div>
                            <?php
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Intensity Preferences -->
                <div class="analysis-card intensity-preferences-card">
                    <h3 class="card-title"><?php esc_html_e('Предпочитани интензивности', 'parfume-reviews'); ?></h3>
                    <div class="intensity-preferences-chart">
                        <?php
                        foreach (array_slice($intensity_preferences, 0, 6, true) as $intensity_slug => $intensity_data) :
                            $total_intensity_count = array_sum(array_column($intensity_preferences, 'count'));
                            $percentage = $total_intensity_count > 0 ? round(($intensity_data['count'] / $total_intensity_count) * 100) : 0;
                        ?>
                            <div class="intensity-preference-item">
                                <div class="intensity-header">
                                    <span class="intensity-name"><?php echo esc_html($intensity_data['name']); ?></span>
                                    <span class="intensity-count"><?php echo esc_html($intensity_data['count']); ?></span>
                                </div>
                                <div class="intensity-progress">
                                    <div class="intensity-bar" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                </div>
                                <div class="intensity-details">
                                    <span class="intensity-percentage"><?php echo esc_html($percentage); ?>%</span>
                                    <?php if ($intensity_data['avg_rating'] > 0) : ?>
                                        <span class="intensity-rating">⭐ <?php echo esc_html($intensity_data['avg_rating']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Aroma Types Distribution -->
                <div class="analysis-card aroma-distribution-card">
                    <h3 class="card-title"><?php esc_html_e('Популярни аромати за сезона', 'parfume-reviews'); ?></h3>
                    <div class="aroma-distribution-chart">
                        <?php
                        $total_aroma_count = array_sum(array_column($aroma_type_distribution, 'count'));
                        foreach (array_slice($aroma_type_distribution, 0, 8, true) as $aroma_slug => $aroma_data) :
                            $percentage = $total_aroma_count > 0 ? round(($aroma_data['count'] / $total_aroma_count) * 100) : 0;
                        ?>
                            <div class="aroma-distribution-item">
                                <div class="aroma-bubble" style="--size: <?php echo esc_attr(30 + $percentage * 2); ?>px;">
                                    <span class="aroma-count"><?php echo esc_html($aroma_data['count']); ?></span>
                                </div>
                                <div class="aroma-info">
                                    <span class="aroma-name"><?php echo esc_html($aroma_data['name']); ?></span>
                                    <span class="aroma-percentage"><?php echo esc_html($percentage); ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </div>
        </section>
        
        <!-- Season Characteristics -->
        <section class="season-characteristics-section">
            <h2 class="section-title">
                <span class="title-icon">✨</span>
                <span class="title-text"><?php esc_html_e('Сезонни характеристики', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="characteristics-grid">
                
                <!-- Popular Notes -->
                <div class="characteristics-card popular-notes-card">
                    <h3 class="card-title"><?php esc_html_e('Най-популярни ноти', 'parfume-reviews'); ?></h3>
                    <div class="notes-cloud">
                        <?php
                        foreach (array_slice($notes_frequency, 0, 20, true) as $note_slug => $note_data) :
                            $size_class = $note_data['count'] > 10 ? 'xl' : ($note_data['count'] > 5 ? 'large' : ($note_data['count'] > 2 ? 'medium' : 'small'));
                        ?>
                            <span class="note-tag <?php echo esc_attr($size_class); ?>" data-count="<?php echo esc_attr($note_data['count']); ?>">
                                <?php echo esc_html($note_data['name']); ?>
                                <span class="note-frequency"><?php echo esc_html($note_data['count']); ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Seasonal Occasions -->
                <?php if (!empty($season_occasions) && is_array($season_occasions)) : ?>
                    <div class="characteristics-card seasonal-occasions-card">
                        <h3 class="card-title"><?php esc_html_e('Подходящи поводи', 'parfume-reviews'); ?></h3>
                        <div class="occasions-grid">
                            <?php foreach ($season_occasions as $occasion) : ?>
                                <div class="occasion-item">
                                    <span class="occasion-icon">🎯</span>
                                    <span class="occasion-text"><?php echo esc_html($occasion); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Wearing Tips -->
                <?php if (!empty($season_wearing_tips) && is_array($season_wearing_tips)) : ?>
                    <div class="characteristics-card wearing-tips-card">
                        <h3 class="card-title"><?php esc_html_e('Съвети за носене', 'parfume-reviews'); ?></h3>
                        <div class="tips-list">
                            <?php foreach ($season_wearing_tips as $tip) : ?>
                                <div class="tip-item">
                                    <span class="tip-icon">💡</span>
                                    <span class="tip-text"><?php echo esc_html($tip); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Weather Compatibility -->
                <div class="characteristics-card weather-compatibility-card">
                    <h3 class="card-title"><?php esc_html_e('Времетраене и съвместимост', 'parfume-reviews'); ?></h3>
                    <div class="weather-conditions">
                        <?php
                        $weather_conditions = array(
                            'sunny' => array('icon' => '☀️', 'label' => __('Слънчево', 'parfume-reviews'), 'compatibility' => rand(70, 95)),
                            'cloudy' => array('icon' => '☁️', 'label' => __('Облачно', 'parfume-reviews'), 'compatibility' => rand(80, 100)),
                            'rainy' => array('icon' => '🌧️', 'label' => __('Дъждовно', 'parfume-reviews'), 'compatibility' => rand(60, 85)),
                            'windy' => array('icon' => '💨', 'label' => __('Ветровито', 'parfume-reviews'), 'compatibility' => rand(65, 90))
                        );
                        
                        // Adjust compatibility based on season
                        switch ($season_slug) {
                            case 'summer':
                                $weather_conditions['sunny']['compatibility'] = rand(85, 100);
                                $weather_conditions['rainy']['compatibility'] = rand(40, 65);
                                break;
                            case 'winter':
                                $weather_conditions['sunny']['compatibility'] = rand(60, 80);
                                $weather_conditions['cloudy']['compatibility'] = rand(85, 100);
                                break;
                            case 'spring':
                                $weather_conditions['rainy']['compatibility'] = rand(70, 90);
                                break;
                            case 'autumn':
                                $weather_conditions['cloudy']['compatibility'] = rand(85, 100);
                                $weather_conditions['windy']['compatibility'] = rand(75, 95);
                                break;
                        }
                        
                        foreach ($weather_conditions as $condition => $data) :
                        ?>
                            <div class="weather-condition-item">
                                <div class="weather-icon"><?php echo esc_html($data['icon']); ?></div>
                                <div class="weather-info">
                                    <span class="weather-name"><?php echo esc_html($data['label']); ?></span>
                                    <div class="compatibility-bar">
                                        <div class="compatibility-fill" style="width: <?php echo esc_attr($data['compatibility']); ?>%"></div>
                                    </div>
                                    <span class="compatibility-percentage"><?php echo esc_html($data['compatibility']); ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </div>
        </section>
        
        <!-- Season Highlights -->
        <section class="season-highlights-section">
            <h2 class="section-title">
                <span class="title-icon">🏆</span>
                <span class="title-text"><?php esc_html_e('Сезонни фаворити', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="highlights-showcase">
                
                <!-- Highest Rated -->
                <?php if ($highest_rated) : ?>
                    <div class="highlight-card season-highest-rated">
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
                
                <!-- Best Longevity -->
                <?php if ($best_longevity && $best_longevity !== $highest_rated) : ?>
                    <div class="highlight-card season-best-longevity">
                        <div class="card-badge badge-longevity"><?php esc_html_e('Най-дълготраен', 'parfume-reviews'); ?></div>
                        <?php
                        $longevity_post = get_post($best_longevity);
                        $longevity_brands = wp_get_post_terms($best_longevity, 'marki');
                        $longevity_value = get_post_meta($best_longevity, '_parfume_longevity', true);
                        ?>
                        <div class="highlight-image">
                            <?php if (has_post_thumbnail($best_longevity)) : ?>
                                <a href="<?php echo get_permalink($best_longevity); ?>">
                                    <?php echo get_the_post_thumbnail($best_longevity, 'medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">
                                <a href="<?php echo get_permalink($best_longevity); ?>">
                                    <?php echo esc_html($longevity_post->post_title); ?>
                                </a>
                            </h3>
                            <?php if (!empty($longevity_brands)) : ?>
                                <div class="highlight-brand"><?php echo esc_html($longevity_brands[0]->name); ?></div>
                            <?php endif; ?>
                            <div class="highlight-longevity">
                                <span class="longevity-icon">⏱️</span>
                                <span class="longevity-text"><?php echo esc_html($longevity_value); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Most Popular -->
                <?php if ($most_popular && !in_array($most_popular, array($highest_rated, $best_longevity))) : ?>
                    <div class="highlight-card season-most-popular">
                        <div class="card-badge badge-popular"><?php esc_html_e('Най-популярен', 'parfume-reviews'); ?></div>
                        <?php
                        $popular_post = get_post($most_popular);
                        $popular_brands = wp_get_post_terms($most_popular, 'marki');
                        ?>
                        <div class="highlight-image">
                            <?php if (has_post_thumbnail($most_popular)) : ?>
                                <a href="<?php echo get_permalink($most_popular); ?>">
                                    <?php echo get_the_post_thumbnail($most_popular, 'medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">
                                <a href="<?php echo get_permalink($most_popular); ?>">
                                    <?php echo esc_html($popular_post->post_title); ?>
                                </a>
                            </h3>
                            <?php if (!empty($popular_brands)) : ?>
                                <div class="highlight-brand"><?php echo esc_html($popular_brands[0]->name); ?></div>
                            <?php endif; ?>
                            <div class="highlight-popularity">
                                <span class="popularity-icon">👁️</span>
                                <span class="popularity-count"><?php echo esc_html($most_popular_views); ?> <?php esc_html_e('прегледа', 'parfume-reviews'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </section>
        
        <!-- Main Parfumes Grid -->
        <section class="season-parfumes-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon"><?php echo esc_html($current_season_config['emoji']); ?></span>
                    <span class="title-text">
                        <?php
                        printf(
                            /* translators: %s: season name */
                            esc_html__('Всички парфюми за %s', 'parfume-reviews'),
                            esc_html($current_term->name)
                        );
                        ?>
                    </span>
                </h2>
                
                <!-- Season Filters -->
                <div class="season-filters">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="season-sort"><?php esc_html_e('Сортиране:', 'parfume-reviews'); ?></label>
                            <select id="season-sort" name="sort">
                                <option value="rating"><?php esc_html_e('По оценка', 'parfume-reviews'); ?></option>
                                <option value="longevity"><?php esc_html_e('По дълготрайност', 'parfume-reviews'); ?></option>
                                <option value="sillage"><?php esc_html_e('По проекция', 'parfume-reviews'); ?></option>
                                <option value="date"><?php esc_html_e('По дата', 'parfume-reviews'); ?></option>
                                <option value="title"><?php esc_html_e('По име', 'parfume-reviews'); ?></option>
                                <option value="popularity"><?php esc_html_e('По популярност', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="season-gender"><?php esc_html_e('Пол:', 'parfume-reviews'); ?></label>
                            <select id="season-gender" name="gender">
                                <option value=""><?php esc_html_e('Всички', 'parfume-reviews'); ?></option>
                                <option value="male"><?php esc_html_e('Мъжки', 'parfume-reviews'); ?></option>
                                <option value="female"><?php esc_html_e('Дамски', 'parfume-reviews'); ?></option>
                                <option value="unisex"><?php esc_html_e('Унисекс', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="season-intensity"><?php esc_html_e('Интензивност:', 'parfume-reviews'); ?></label>
                            <select id="season-intensity" name="intensity">
                                <option value=""><?php esc_html_e('Всички', 'parfume-reviews'); ?></option>
                                <?php foreach (array_slice($intensity_preferences, 0, 10, true) as $intensity_slug => $intensity_data) : ?>
                                    <option value="<?php echo esc_attr($intensity_slug); ?>">
                                        <?php echo esc_html($intensity_data['name']); ?> (<?php echo esc_html($intensity_data['count']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="season-aroma"><?php esc_html_e('Аромат:', 'parfume-reviews'); ?></label>
                            <select id="season-aroma" name="aroma_type">
                                <option value=""><?php esc_html_e('Всички аромати', 'parfume-reviews'); ?></option>
                                <?php foreach (array_slice($aroma_type_distribution, 0, 12, true) as $aroma_slug => $aroma_data) : ?>
                                    <option value="<?php echo esc_attr($aroma_slug); ?>">
                                        <?php echo esc_html($aroma_data['name']); ?> (<?php echo esc_html($aroma_data['count']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="season-brand"><?php esc_html_e('Марка:', 'parfume-reviews'); ?></label>
                            <select id="season-brand" name="brand">
                                <option value=""><?php esc_html_e('Всички марки', 'parfume-reviews'); ?></option>
                                <?php foreach (array_slice($top_brands, 0, 15, true) as $brand_id => $brand_data) : ?>
                                    <option value="<?php echo esc_attr($brand_id); ?>">
                                        <?php echo esc_html($brand_data['name']); ?> (<?php echo esc_html($brand_data['count']); ?>)
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
                        <button class="reset-season-filters" type="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Изчисти филтрите', 'parfume-reviews'); ?>
                        </button>
                        <div class="season-results-info">
                            <span class="season-results-count"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Loading State -->
            <div class="season-loading" style="display: none;">
                <div class="loading-content">
                    <div class="loading-spinner">
                        <span class="season-icon"><?php echo esc_html($current_season_config['emoji']); ?></span>
                    </div>
                    <p><?php esc_html_e('Зареждане на сезонни парфюми...', 'parfume-reviews'); ?></p>
                </div>
            </div>
            
            <!-- Parfumes Grid -->
            <div class="season-parfumes-container" data-view="grid">
                <?php
                // Main season parfumes query
                $main_season_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 12,
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'season',
                            'field' => 'term_id',
                            'terms' => $season_id
                        )
                    ),
                    'meta_key' => '_parfume_rating',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ));
                
                if ($main_season_query->have_posts()) : ?>
                    <div class="parfumes-grid" id="season-parfumes-grid">
                        <?php while ($main_season_query->have_posts()) : $main_season_query->the_post(); ?>
                            <?php get_template_part('template-parts/parfume-card', null, array('show_season' => false, 'highlight_performance' => true)); ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="season-pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $main_season_query->max_num_pages,
                            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Предишна', 'parfume-reviews'),
                            'next_text' => esc_html__('Следваща', 'parfume-reviews') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                            'mid_size' => 2
                        ));
                        ?>
                    </div>
                    
                <?php else : ?>
                    <div class="no-season-parfumes">
                        <div class="no-content-icon">
                            <span class="season-emoji"><?php echo esc_html($current_season_config['emoji']); ?></span>
                        </div>
                        <h3><?php esc_html_e('Няма намерени парфюми', 'parfume-reviews'); ?></h3>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: season name */
                                esc_html__('В момента няма парфюми подходящи за %s в нашата база данни.', 'parfume-reviews'),
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
 * Hook: parfume_reviews_season_footer
 */
do_action('parfume_reviews_season_footer');
?>

<!-- Enhanced JavaScript for Season Template -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Performance Charts Animation
    function animatePerformanceCharts() {
        // Longevity bars animation
        $('.longevity-bar').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                const width = $(this).css('width');
                $(this).css('width', 0).animate({width: width}, 1000, 'easeOutCubic');
            }
        });
        
        // Sillage circles animation
        $('.sillage-circle').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).css({
                    transform: 'scale(0)',
                    opacity: 0
                }).animate({
                    opacity: 1
                }, 600).css('transform', 'scale(1)');
            }
        });
        
        // Aroma bubbles animation
        $('.aroma-bubble').each(function(index) {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).delay(index * 100).fadeIn(400);
            }
        });
        
        // Notes cloud animation
        $('.note-tag').each(function(index) {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).delay(index * 50).animate({
                    opacity: 1,
                    transform: 'translateY(0)'
                }, 300);
            }
        });
    }
    
    // Weather compatibility hover effects
    $('.weather-condition-item').on('mouseenter', function() {
        $(this).addClass('hover-active');
        const compatibility = $(this).find('.compatibility-percentage').text();
        $(this).attr('title', 'Съвместимост: ' + compatibility);
    }).on('mouseleave', function() {
        $(this).removeClass('hover-active');
    });
    
    // Advanced Filtering for Season Parfumes
    const $seasonFilters = $('#season-sort, #season-gender, #season-intensity, #season-aroma, #season-brand');
    const $seasonGrid = $('#season-parfumes-grid');
    const $seasonLoading = $('.season-loading');
    const $seasonResultsCount = $('.season-results-count');
    
    let seasonFilterTimeout;
    
    $seasonFilters.on('change', function() {
        clearTimeout(seasonFilterTimeout);
        seasonFilterTimeout = setTimeout(applySeasonFilters, 300);
    });
    
    function applySeasonFilters() {
        $seasonLoading.show();
        $seasonGrid.addClass('filtering');
        
        const filters = {
            sort: $('#season-sort').val(),
            gender: $('#season-gender').val(),
            intensity: $('#season-intensity').val(),
            aroma_type: $('#season-aroma').val(),
            brand: $('#season-brand').val()
        };
        
        // Simulate AJAX filtering
        setTimeout(function() {
            filterSeasonParfumes(filters);
            $seasonLoading.hide();
            $seasonGrid.removeClass('filtering');
        }, 600);
    }
    
    function filterSeasonParfumes(filters) {
        const $items = $seasonGrid.find('.parfume-item');
        let visibleCount = 0;
        
        $items.each(function() {
            let showItem = true;
            const $item = $(this);
            
            // Apply filters
            if (filters.gender && $item.data('gender') !== filters.gender) {
                showItem = false;
            }
            if (filters.intensity && $item.data('intensity') !== filters.intensity) {
                showItem = false;
            }
            if (filters.aroma_type && $item.data('aroma-type') !== filters.aroma_type) {
                showItem = false;
            }
            if (filters.brand && $item.data('brand-id') != filters.brand) {
                showItem = false;
            }
            
            if (showItem) {
                $item.show();
                visibleCount++;
            } else {
                $item.hide();
            }
        });
        
        // Update results count
        $seasonResultsCount.text(visibleCount + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
        
        // Apply sorting
        if (filters.sort) {
            sortSeasonParfumes(filters.sort);
        }
    }
    
    function sortSeasonParfumes(sortBy) {
        const $visibleItems = $seasonGrid.find('.parfume-item:visible');
        
        $visibleItems.sort(function(a, b) {
            let aVal, bVal;
            
            switch (sortBy) {
                case 'rating':
                    aVal = parseFloat($(a).data('rating')) || 0;
                    bVal = parseFloat($(b).data('rating')) || 0;
                    return bVal - aVal;
                    
                case 'longevity':
                    aVal = $(a).data('longevity-score') || 0;
                    bVal = $(b).data('longevity-score') || 0;
                    return bVal - aVal;
                    
                case 'sillage':
                    aVal = $(a).data('sillage-score') || 0;
                    bVal = $(b).data('sillage-score') || 0;
                    return bVal - aVal;
                    
                case 'title':
                    aVal = $(a).find('.parfume-title').text().toLowerCase();
                    bVal = $(b).find('.parfume-title').text().toLowerCase();
                    return aVal.localeCompare(bVal);
                    
                default:
                    return 0;
            }
        });
        
        $seasonGrid.append($visibleItems);
    }
    
    // Reset filters
    $('.reset-season-filters').on('click', function() {
        $seasonFilters.val('');
        $seasonGrid.find('.parfume-item').show();
        $seasonResultsCount.text($seasonGrid.find('.parfume-item').length + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
    });
    
    // View toggle for season parfumes
    $('.view-toggle .view-btn').on('click', function(e) {
        e.preventDefault();
        
        const view = $(this).data('view');
        $('.view-toggle .view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.season-parfumes-container').attr('data-view', view);
        
        // Store preference
        localStorage.setItem('season_parfumes_view', view);
    });
    
    // Load saved view preference
    const savedSeasonView = localStorage.getItem('season_parfumes_view');
    if (savedSeasonView) {
        $('.view-toggle .view-btn[data-view="' + savedSeasonView + '"]').click();
    }
    
    // Enhanced highlight card interactions
    $('.highlight-card').on('mouseenter', function() {
        $(this).addClass('highlight-hover');
    }).on('mouseleave', function() {
        $(this).removeClass('highlight-hover');
    });
    
    // Utility function for viewport detection
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
    $(window).on('scroll resize', animatePerformanceCharts);
    animatePerformanceCharts(); // Initial check
    
    // Initialize results count
    $seasonResultsCount.text($seasonGrid.find('.parfume-item').length + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
    
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
    
});
</script>

<?php get_footer(); ?>