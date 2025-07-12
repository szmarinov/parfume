<?php
/**
 * Taxonomy template for Intensity pages
 * 
 * Displays parfumes by intensity level with projection analysis,
 * wearing distance recommendations, personal space considerations,
 * and environment-specific usage patterns
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

// Получаване на текущия intensity term
$current_term = get_queried_object();
$intensity_id = $current_term->term_id;
$intensity_slug = $current_term->slug;

// Получаване на intensity meta данни
$intensity_description_extended = get_term_meta($intensity_id, 'intensity-description-extended', true);
$intensity_projection_distance = get_term_meta($intensity_id, 'intensity-projection-distance', true);
$intensity_recommended_sprays = get_term_meta($intensity_id, 'intensity-recommended-sprays', true);
$intensity_suitable_environments = get_term_meta($intensity_id, 'intensity-suitable-environments', true);
$intensity_personal_space_consideration = get_term_meta($intensity_id, 'intensity-personal-space-consideration', true);
$intensity_longevity_correlation = get_term_meta($intensity_id, 'intensity-longevity-correlation', true);
$intensity_best_application_spots = get_term_meta($intensity_id, 'intensity-best-application-spots', true);
$intensity_time_recommendations = get_term_meta($intensity_id, 'intensity-time-recommendations', true);

// Анализ на парфюмите с тази интензивност
$intensity_query = new WP_Query(array(
    'post_type' => 'parfume',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'intensity',
            'field' => 'term_id',
            'terms' => $intensity_id
        )
    )
));

// Статистически анализ
$total_parfumes = $intensity_query->found_posts;
$avg_rating = 0;
$gender_distribution = array('male' => 0, 'female' => 0, 'unisex' => 0);
$seasonal_preferences = array('spring' => 0, 'summer' => 0, 'autumn' => 0, 'winter' => 0);
$aroma_families = array();
$longevity_distribution = array();
$sillage_correlation = array();
$price_analysis = array('budget' => 0, 'mid' => 0, 'luxury' => 0, 'niche' => 0);
$top_brands = array();
$top_perfumers = array();
$notes_frequency = array();
$release_decades = array();
$age_groups = array('young' => 0, 'adult' => 0, 'mature' => 0);
$occasions_analysis = array();
$highest_rated = null;
$most_versatile = null;
$most_popular = null;

if ($intensity_query->have_posts()) {
    $ratings_sum = 0;
    $ratings_count = 0;
    $max_rating = 0;
    $max_seasons = 0;
    $most_popular_views = 0;
    
    while ($intensity_query->have_posts()) {
        $intensity_query->the_post();
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
        
        // Seasonal анализ (за versatility)
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
        
        // Aroma families анализ
        $aroma_types = wp_get_post_terms($post_id, 'aroma_type');
        foreach ($aroma_types as $aroma_type) {
            if (!isset($aroma_families[$aroma_type->slug])) {
                $aroma_families[$aroma_type->slug] = array(
                    'name' => $aroma_type->name,
                    'count' => 0,
                    'total_rating' => 0
                );
            }
            $aroma_families[$aroma_type->slug]['count']++;
            if (!empty($rating)) {
                $aroma_families[$aroma_type->slug]['total_rating'] += $rating_float;
            }
        }
        
        // Longevity correlation анализ
        $longevity = get_post_meta($post_id, '_parfume_longevity', true);
        if (!empty($longevity)) {
            $longevity_key = strtolower(str_replace(array(' ', '-'), '_', $longevity));
            if (!isset($longevity_distribution[$longevity_key])) {
                $longevity_distribution[$longevity_key] = array(
                    'name' => $longevity,
                    'count' => 0
                );
            }
            $longevity_distribution[$longevity_key]['count']++;
        }
        
        // Sillage correlation
        $sillage = get_post_meta($post_id, '_parfume_sillage', true);
        if (!empty($sillage)) {
            $sillage_key = strtolower(str_replace(array(' ', '-'), '_', $sillage));
            if (!isset($sillage_correlation[$sillage_key])) {
                $sillage_correlation[$sillage_key] = array(
                    'name' => $sillage,
                    'count' => 0
                );
            }
            $sillage_correlation[$sillage_key]['count']++;
        }
        
        // Price анализ
        $price_level = get_post_meta($post_id, '_parfume_price_level', true);
        if (!empty($price_level)) {
            switch (intval($price_level)) {
                case 1: case 2: $price_analysis['budget']++; break;
                case 3: $price_analysis['mid']++; break;
                case 4: $price_analysis['luxury']++; break;
                case 5: $price_analysis['niche']++; break;
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
        
        // Release decades
        $release_year = get_post_meta($post_id, '_parfume_release_year', true);
        if (!empty($release_year) && is_numeric($release_year)) {
            $decade = floor(intval($release_year) / 10) * 10;
            if (!isset($release_decades[$decade])) {
                $release_decades[$decade] = 0;
            }
            $release_decades[$decade]++;
        }
        
        // Age groups analysis (simplified based on release year and brand)
        if (!empty($release_year)) {
            $year = intval($release_year);
            if ($year >= 2010) {
                $age_groups['young']++;
            } elseif ($year >= 1990) {
                $age_groups['adult']++;
            } else {
                $age_groups['mature']++;
            }
        }
        
        // Most popular analysis
        $post_views = get_post_meta($post_id, '_post_views', true) ?: rand(30, 300);
        if ($post_views > $most_popular_views) {
            $most_popular_views = $post_views;
            $most_popular = $post_id;
        }
    }
    
    if ($ratings_count > 0) {
        $avg_rating = round($ratings_sum / $ratings_count, 1);
    }
    
    // Calculate average ratings for aroma families
    foreach ($aroma_families as $key => &$family) {
        if ($family['count'] > 0) {
            $family['avg_rating'] = round($family['total_rating'] / $family['count'], 1);
        }
    }
}

wp_reset_postdata();

// Сортиране на данните
uasort($top_brands, function($a, $b) { return $b['count'] - $a['count']; });
uasort($top_perfumers, function($a, $b) { return $b['count'] - $a['count']; });
uasort($aroma_families, function($a, $b) { return $b['count'] - $a['count']; });
uasort($notes_frequency, function($a, $b) { return $b['count'] - $a['count']; });
uasort($longevity_distribution, function($a, $b) { return $b['count'] - $a['count']; });
uasort($sillage_correlation, function($a, $b) { return $b['count'] - $a['count']; });
krsort($release_decades);

// Intensity-specific configuration
$intensity_config = array(
    'light' => array(
        'icon' => '🌬️',
        'color' => '#a8e6cf',
        'projection' => '0-30cm',
        'sprays' => '3-5',
        'description' => 'Intimate, close to skin, perfect for office',
        'environments' => array('office', 'close quarters', 'daytime'),
        'personal_space' => 'Respectful, non-intrusive'
    ),
    'moderate' => array(
        'icon' => '🌸',
        'color' => '#ffd93d',
        'projection' => '30-60cm',
        'sprays' => '2-4',
        'description' => 'Well-balanced, noticeable but not overwhelming',
        'environments' => array('casual', 'social', 'everyday'),
        'personal_space' => 'Socially appropriate'
    ),
    'strong' => array(
        'icon' => '💫',
        'color' => '#ff8b94',
        'projection' => '60-120cm',
        'sprays' => '1-3',
        'description' => 'Bold, commanding presence, makes a statement',
        'environments' => array('evening', 'special occasions', 'cooler weather'),
        'personal_space' => 'Confident, attention-grabbing'
    ),
    'very_strong' => array(
        'icon' => '🔥',
        'color' => '#ff6b6b',
        'projection' => '120cm+',
        'sprays' => '1-2',
        'description' => 'Powerful, room-filling, maximum impact',
        'environments' => array('outdoor events', 'winter', 'special occasions'),
        'personal_space' => 'Bold statement, use carefully'
    )
);

$current_intensity_config = $intensity_config[$intensity_slug] ?? array(
    'icon' => '✨',
    'color' => '#a8e6cf',
    'projection' => 'Variable',
    'sprays' => '2-4',
    'description' => 'Versatile intensity level',
    'environments' => array('various'),
    'personal_space' => 'Adaptable'
);

$intensity_class = 'intensity-' . sanitize_html_class($intensity_slug);
?>

<div class="intensity-taxonomy-wrap <?php echo esc_attr($intensity_class); ?>" style="--intensity-primary-color: <?php echo esc_attr($current_intensity_config['color']); ?>">
    <div class="container">
        
        <?php
        /**
         * Hook: parfume_reviews_intensity_before_header
         * 
         * @hooked parfume_reviews_breadcrumbs - 10
         */
        do_action('parfume_reviews_intensity_before_header');
        ?>
        
        <!-- Intensity Header -->
        <header class="intensity-header">
            <div class="intensity-header-background">
                <div class="intensity-waves intensity-<?php echo esc_attr($intensity_slug); ?>"></div>
                <div class="intensity-overlay"></div>
            </div>
            
            <div class="intensity-header-content">
                <div class="intensity-main-info">
                    <div class="intensity-icon-section">
                        <div class="intensity-main-icon">
                            <span class="emoji-icon"><?php echo esc_html($current_intensity_config['icon']); ?></span>
                        </div>
                        
                        <div class="intensity-specs">
                            <div class="spec-item">
                                <span class="spec-icon">📏</span>
                                <span class="spec-label"><?php esc_html_e('Проекция:', 'parfume-reviews'); ?></span>
                                <span class="spec-value">
                                    <?php echo !empty($intensity_projection_distance) ? esc_html($intensity_projection_distance) : esc_html($current_intensity_config['projection']); ?>
                                </span>
                            </div>
                            
                            <div class="spec-item">
                                <span class="spec-icon">💧</span>
                                <span class="spec-label"><?php esc_html_e('Препоръчани спрейове:', 'parfume-reviews'); ?></span>
                                <span class="spec-value">
                                    <?php echo !empty($intensity_recommended_sprays) ? esc_html($intensity_recommended_sprays) : esc_html($current_intensity_config['sprays']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="intensity-title-section">
                        <h1 class="intensity-title">
                            <?php
                            printf(
                                /* translators: %s: intensity level name */
                                esc_html__('%s интензивност', 'parfume-reviews'),
                                esc_html($current_term->name)
                            );
                            ?>
                        </h1>
                        
                        <?php if (!empty($current_term->description)) : ?>
                            <p class="intensity-tagline"><?php echo esc_html($current_term->description); ?></p>
                        <?php else : ?>
                            <p class="intensity-tagline"><?php echo esc_html($current_intensity_config['description']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($intensity_description_extended)) : ?>
                            <div class="intensity-description-extended">
                                <?php echo wp_kses_post(wpautop($intensity_description_extended)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="intensity-personal-space">
                            <span class="personal-space-label"><?php esc_html_e('Лично пространство:', 'parfume-reviews'); ?></span>
                            <span class="personal-space-text">
                                <?php echo !empty($intensity_personal_space_consideration) ? esc_html($intensity_personal_space_consideration) : esc_html($current_intensity_config['personal_space']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Intensity Quick Stats -->
                <div class="intensity-quick-stats">
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
                                <div class="stat-number"><?php echo esc_html(count(array_filter($aroma_families))); ?></div>
                                <div class="stat-label"><?php esc_html_e('Аромати', 'parfume-reviews'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <?php
        /**
         * Hook: parfume_reviews_intensity_after_header
         */
        do_action('parfume_reviews_intensity_after_header');
        ?>
        
        <!-- Projection & Usage Analysis -->
        <section class="intensity-projection-section">
            <h2 class="section-title">
                <span class="title-icon">📡</span>
                <span class="title-text"><?php esc_html_e('Проекция и използване', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="projection-analysis-grid">
                
                <!-- Projection Visualization -->
                <div class="analysis-card projection-visualization-card">
                    <h3 class="card-title"><?php esc_html_e('Визуализация на проекцията', 'parfume-reviews'); ?></h3>
                    <div class="projection-diagram">
                        <div class="person-silhouette">
                            <div class="person-icon">👤</div>
                        </div>
                        <div class="projection-circles">
                            <?php
                            $projection_levels = array(
                                'intimate' => array('radius' => 30, 'label' => 'Интимна (30cm)', 'active' => false),
                                'moderate' => array('radius' => 60, 'label' => 'Умерена (60cm)', 'active' => false),
                                'strong' => array('radius' => 120, 'label' => 'Силна (120cm)', 'active' => false),
                                'very_strong' => array('radius' => 200, 'label' => 'Много силна (200cm+)', 'active' => false)
                            );
                            
                            // Mark current intensity as active
                            switch ($intensity_slug) {
                                case 'light':
                                    $projection_levels['intimate']['active'] = true;
                                    break;
                                case 'moderate':
                                    $projection_levels['intimate']['active'] = true;
                                    $projection_levels['moderate']['active'] = true;
                                    break;
                                case 'strong':
                                    $projection_levels['intimate']['active'] = true;
                                    $projection_levels['moderate']['active'] = true;
                                    $projection_levels['strong']['active'] = true;
                                    break;
                                case 'very_strong':
                                    foreach ($projection_levels as &$level) {
                                        $level['active'] = true;
                                    }
                                    break;
                            }
                            
                            foreach ($projection_levels as $level_key => $level_data) :
                                $active_class = $level_data['active'] ? 'active' : 'inactive';
                            ?>
                                <div class="projection-circle <?php echo esc_attr($level_key); ?> <?php echo esc_attr($active_class); ?>" 
                                     style="width: <?php echo esc_attr($level_data['radius']); ?>px; height: <?php echo esc_attr($level_data['radius']); ?>px;">
                                    <span class="projection-label"><?php echo esc_html($level_data['label']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="projection-legend">
                        <div class="legend-item active">
                            <span class="legend-dot active"></span>
                            <span class="legend-text"><?php esc_html_e('Активна зона за тази интензивност', 'parfume-reviews'); ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot inactive"></span>
                            <span class="legend-text"><?php esc_html_e('Неактивна зона', 'parfume-reviews'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Environmental Suitability -->
                <div class="analysis-card environmental-suitability-card">
                    <h3 class="card-title"><?php esc_html_e('Подходящи среди', 'parfume-reviews'); ?></h3>
                    <div class="environmental-grid">
                        <?php
                        $environments = array(
                            'office' => array('icon' => '🏢', 'name' => __('Офис', 'parfume-reviews'), 'compatibility' => 0),
                            'casual' => array('icon' => '👕', 'name' => __('Ежедневие', 'parfume-reviews'), 'compatibility' => 0),
                            'evening' => array('icon' => '🌙', 'name' => __('Вечер', 'parfume-reviews'), 'compatibility' => 0),
                            'special' => array('icon' => '🎉', 'name' => __('Специални поводи', 'parfume-reviews'), 'compatibility' => 0),
                            'outdoor' => array('icon' => '🌳', 'name' => __('На открито', 'parfume-reviews'), 'compatibility' => 0),
                            'intimate' => array('icon' => '💕', 'name' => __('Интимни моменти', 'parfume-reviews'), 'compatibility' => 0)
                        );
                        
                        // Set compatibility based on intensity
                        switch ($intensity_slug) {
                            case 'light':
                                $environments['office']['compatibility'] = 95;
                                $environments['casual']['compatibility'] = 85;
                                $environments['evening']['compatibility'] = 60;
                                $environments['special']['compatibility'] = 40;
                                $environments['outdoor']['compatibility'] = 70;
                                $environments['intimate']['compatibility'] = 90;
                                break;
                            case 'moderate':
                                $environments['office']['compatibility'] = 80;
                                $environments['casual']['compatibility'] = 95;
                                $environments['evening']['compatibility'] = 85;
                                $environments['special']['compatibility'] = 75;
                                $environments['outdoor']['compatibility'] = 85;
                                $environments['intimate']['compatibility'] = 75;
                                break;
                            case 'strong':
                                $environments['office']['compatibility'] = 50;
                                $environments['casual']['compatibility'] = 70;
                                $environments['evening']['compatibility'] = 95;
                                $environments['special']['compatibility'] = 90;
                                $environments['outdoor']['compatibility'] = 90;
                                $environments['intimate']['compatibility'] = 60;
                                break;
                            case 'very_strong':
                                $environments['office']['compatibility'] = 20;
                                $environments['casual']['compatibility'] = 45;
                                $environments['evening']['compatibility'] = 85;
                                $environments['special']['compatibility'] = 95;
                                $environments['outdoor']['compatibility'] = 95;
                                $environments['intimate']['compatibility'] = 40;
                                break;
                        }
                        
                        foreach ($environments as $env_key => $env_data) :
                            $compatibility_class = $env_data['compatibility'] >= 80 ? 'high' : ($env_data['compatibility'] >= 60 ? 'medium' : 'low');
                        ?>
                            <div class="environment-item compatibility-<?php echo esc_attr($compatibility_class); ?>">
                                <div class="environment-icon"><?php echo esc_html($env_data['icon']); ?></div>
                                <div class="environment-info">
                                    <span class="environment-name"><?php echo esc_html($env_data['name']); ?></span>
                                    <div class="compatibility-bar">
                                        <div class="compatibility-fill" style="width: <?php echo esc_attr($env_data['compatibility']); ?>%"></div>
                                    </div>
                                    <span class="compatibility-percentage"><?php echo esc_html($env_data['compatibility']); ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Application Recommendations -->
                <?php if (!empty($intensity_best_application_spots) || !empty($current_intensity_config['environments'])) : ?>
                    <div class="analysis-card application-recommendations-card">
                        <h3 class="card-title"><?php esc_html_e('Препоръки за приложение', 'parfume-reviews'); ?></h3>
                        
                        <?php if (!empty($intensity_best_application_spots) && is_array($intensity_best_application_spots)) : ?>
                            <div class="application-section">
                                <h4 class="subsection-title"><?php esc_html_e('Най-добри места за пръскане', 'parfume-reviews'); ?></h4>
                                <div class="application-spots">
                                    <?php foreach ($intensity_best_application_spots as $spot) : ?>
                                        <div class="application-spot">
                                            <span class="spot-icon">📍</span>
                                            <span class="spot-name"><?php echo esc_html($spot); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($intensity_time_recommendations) && is_array($intensity_time_recommendations)) : ?>
                            <div class="application-section">
                                <h4 class="subsection-title"><?php esc_html_e('Времеви препоръки', 'parfume-reviews'); ?></h4>
                                <div class="time-recommendations">
                                    <?php foreach ($intensity_time_recommendations as $time_rec) : ?>
                                        <div class="time-recommendation">
                                            <span class="time-icon">⏰</span>
                                            <span class="time-text"><?php echo esc_html($time_rec); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            </div>
        </section>
        
        <!-- Performance Correlations -->
        <section class="intensity-correlations-section">
            <h2 class="section-title">
                <span class="title-icon">📈</span>
                <span class="title-text"><?php esc_html_e('Корелации с производителността', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="correlations-grid">
                
                <!-- Longevity Correlation -->
                <div class="correlation-card longevity-correlation-card">
                    <h3 class="card-title"><?php esc_html_e('Връзка с дълготрайността', 'parfume-reviews'); ?></h3>
                    <div class="longevity-correlation-chart">
                        <?php
                        $total_longevity = array_sum(array_column($longevity_distribution, 'count'));
                        if ($total_longevity > 0) :
                            foreach ($longevity_distribution as $longevity_key => $longevity_data) :
                                $percentage = round(($longevity_data['count'] / $total_longevity) * 100);
                                if ($percentage > 0) :
                        ?>
                                    <div class="longevity-correlation-item">
                                        <div class="longevity-bar" style="height: <?php echo esc_attr($percentage * 2); ?>px;"></div>
                                        <div class="longevity-label"><?php echo esc_html($longevity_data['name']); ?></div>
                                        <div class="longevity-percentage"><?php echo esc_html($percentage); ?>%</div>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                    
                    <?php if (!empty($intensity_longevity_correlation)) : ?>
                        <div class="correlation-note">
                            <span class="note-icon">💡</span>
                            <span class="note-text"><?php echo esc_html($intensity_longevity_correlation); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sillage Distribution -->
                <div class="correlation-card sillage-distribution-card">
                    <h3 class="card-title"><?php esc_html_e('Разпределение на проекцията', 'parfume-reviews'); ?></h3>
                    <div class="sillage-distribution-chart">
                        <?php
                        $total_sillage = array_sum(array_column($sillage_correlation, 'count'));
                        if ($total_sillage > 0) :
                            foreach ($sillage_correlation as $sillage_key => $sillage_data) :
                                $percentage = round(($sillage_data['count'] / $total_sillage) * 100);
                                if ($percentage > 0) :
                        ?>
                                    <div class="sillage-distribution-item">
                                        <div class="sillage-segment" style="width: <?php echo esc_attr($percentage); ?>%;">
                                            <span class="sillage-percentage"><?php echo esc_html($percentage); ?>%</span>
                                        </div>
                                        <div class="sillage-name"><?php echo esc_html($sillage_data['name']); ?></div>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                
                <!-- Age Group Preferences -->
                <div class="correlation-card age-preferences-card">
                    <h3 class="card-title"><?php esc_html_e('Възрастови предпочитания', 'parfume-reviews'); ?></h3>
                    <div class="age-preferences-chart">
                        <?php
                        $total_age = array_sum($age_groups);
                        if ($total_age > 0) :
                            $age_labels = array(
                                'young' => __('Млади (до 30)', 'parfume-reviews'),
                                'adult' => __('Възрастни (30-50)', 'parfume-reviews'),
                                'mature' => __('Зрели (50+)', 'parfume-reviews')
                            );
                            
                            foreach ($age_groups as $age_group => $count) :
                                if ($count > 0) :
                                    $percentage = round(($count / $total_age) * 100);
                        ?>
                                    <div class="age-preference-item age-<?php echo esc_attr($age_group); ?>">
                                        <div class="age-circle" style="--size: <?php echo esc_attr(50 + $percentage); ?>px;">
                                            <span class="age-count"><?php echo esc_html($count); ?></span>
                                        </div>
                                        <div class="age-info">
                                            <span class="age-label"><?php echo esc_html($age_labels[$age_group]); ?></span>
                                            <span class="age-percentage"><?php echo esc_html($percentage); ?>%</span>
                                        </div>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                
            </div>
        </section>
        
        <!-- Aroma Families & Notes -->
        <section class="intensity-aroma-section">
            <h2 class="section-title">
                <span class="title-icon">🌺</span>
                <span class="title-text"><?php esc_html_e('Ароматни семейства и ноти', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="aroma-analysis-grid">
                
                <!-- Top Aroma Families -->
                <div class="aroma-card top-families-card">
                    <h3 class="card-title"><?php esc_html_e('Най-популярни ароматни семейства', 'parfume-reviews'); ?></h3>
                    <div class="families-ranking">
                        <?php
                        foreach (array_slice($aroma_families, 0, 8, true) as $family_slug => $family_data) :
                            $total_families = array_sum(array_column($aroma_families, 'count'));
                            $percentage = $total_families > 0 ? round(($family_data['count'] / $total_families) * 100) : 0;
                        ?>
                            <div class="family-ranking-item">
                                <div class="family-rank"><?php echo esc_html(array_search($family_slug, array_keys($aroma_families)) + 1); ?></div>
                                <div class="family-info">
                                    <span class="family-name"><?php echo esc_html($family_data['name']); ?></span>
                                    <div class="family-stats">
                                        <span class="family-count"><?php echo esc_html($family_data['count']); ?> <?php esc_html_e('парфюма', 'parfume-reviews'); ?></span>
                                        <?php if ($family_data['avg_rating'] > 0) : ?>
                                            <span class="family-rating">⭐ <?php echo esc_html($family_data['avg_rating']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="family-bar">
                                    <div class="family-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Characteristic Notes -->
                <div class="aroma-card characteristic-notes-card">
                    <h3 class="card-title"><?php esc_html_e('Характерни ноти', 'parfume-reviews'); ?></h3>
                    <div class="characteristic-notes-cloud">
                        <?php
                        foreach (array_slice($notes_frequency, 0, 25, true) as $note_slug => $note_data) :
                            $size_class = $note_data['count'] > 8 ? 'xl' : ($note_data['count'] > 5 ? 'large' : ($note_data['count'] > 3 ? 'medium' : 'small'));
                        ?>
                            <span class="characteristic-note <?php echo esc_attr($size_class); ?>" data-count="<?php echo esc_attr($note_data['count']); ?>">
                                <?php echo esc_html($note_data['name']); ?>
                                <span class="note-count"><?php echo esc_html($note_data['count']); ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Seasonal Distribution -->
                <div class="aroma-card seasonal-distribution-card">
                    <h3 class="card-title"><?php esc_html_e('Сезонно разпределение', 'parfume-reviews'); ?></h3>
                    <div class="seasonal-intensity-chart">
                        <?php
                        $total_seasonal = array_sum($seasonal_preferences);
                        $season_icons = array(
                            'spring' => '🌸',
                            'summer' => '☀️',
                            'autumn' => '🍂',
                            'winter' => '❄️'
                        );
                        $season_names = array(
                            'spring' => __('Пролет', 'parfume-reviews'),
                            'summer' => __('Лято', 'parfume-reviews'),
                            'autumn' => __('Есен', 'parfume-reviews'),
                            'winter' => __('Зима', 'parfume-reviews')
                        );
                        
                        if ($total_seasonal > 0) :
                            foreach ($seasonal_preferences as $season => $count) :
                                if ($count > 0) :
                                    $percentage = round(($count / $total_seasonal) * 100);
                        ?>
                                    <div class="seasonal-intensity-item">
                                        <div class="season-icon"><?php echo esc_html($season_icons[$season]); ?></div>
                                        <div class="season-info">
                                            <span class="season-name"><?php echo esc_html($season_names[$season]); ?></span>
                                            <div class="season-bar">
                                                <div class="season-fill season-<?php echo esc_attr($season); ?>" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                            </div>
                                            <span class="season-stats"><?php echo esc_html($count); ?> (<?php echo esc_html($percentage); ?>%)</span>
                                        </div>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                
            </div>
        </section>
        
        <!-- Intensity Highlights -->
        <section class="intensity-highlights-section">
            <h2 class="section-title">
                <span class="title-icon">🏆</span>
                <span class="title-text"><?php esc_html_e('Най-добри представители', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="intensity-highlights-showcase">
                
                <!-- Highest Rated -->
                <?php if ($highest_rated) : ?>
                    <div class="intensity-highlight-card highest-rated-card">
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
                    <div class="intensity-highlight-card most-versatile-card">
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
                
                <!-- Most Popular -->
                <?php if ($most_popular && !in_array($most_popular, array($highest_rated, $most_versatile))) : ?>
                    <div class="intensity-highlight-card most-popular-card">
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
        <section class="intensity-parfumes-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon"><?php echo esc_html($current_intensity_config['icon']); ?></span>
                    <span class="title-text">
                        <?php
                        printf(
                            /* translators: %s: intensity level name */
                            esc_html__('Всички парфюми с %s интензивност', 'parfume-reviews'),
                            esc_html($current_term->name)
                        );
                        ?>
                    </span>
                </h2>
                
                <!-- Intensity Filters -->
                <div class="intensity-filters">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="intensity-sort"><?php esc_html_e('Сортиране:', 'parfume-reviews'); ?></label>
                            <select id="intensity-sort" name="sort">
                                <option value="rating"><?php esc_html_e('По оценка', 'parfume-reviews'); ?></option>
                                <option value="longevity"><?php esc_html_e('По дълготрайност', 'parfume-reviews'); ?></option>
                                <option value="sillage"><?php esc_html_e('По проекция', 'parfume-reviews'); ?></option>
                                <option value="date"><?php esc_html_e('По дата', 'parfume-reviews'); ?></option>
                                <option value="title"><?php esc_html_e('По име', 'parfume-reviews'); ?></option>
                                <option value="popularity"><?php esc_html_e('По популярност', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="intensity-gender"><?php esc_html_e('Пол:', 'parfume-reviews'); ?></label>
                            <select id="intensity-gender" name="gender">
                                <option value=""><?php esc_html_e('Всички', 'parfume-reviews'); ?></option>
                                <option value="male"><?php esc_html_e('Мъжки', 'parfume-reviews'); ?></option>
                                <option value="female"><?php esc_html_e('Дамски', 'parfume-reviews'); ?></option>
                                <option value="unisex"><?php esc_html_e('Унисекс', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="intensity-aroma"><?php esc_html_e('Аромат:', 'parfume-reviews'); ?></label>
                            <select id="intensity-aroma" name="aroma_type">
                                <option value=""><?php esc_html_e('Всички аромати', 'parfume-reviews'); ?></option>
                                <?php foreach (array_slice($aroma_families, 0, 12, true) as $aroma_slug => $aroma_data) : ?>
                                    <option value="<?php echo esc_attr($aroma_slug); ?>">
                                        <?php echo esc_html($aroma_data['name']); ?> (<?php echo esc_html($aroma_data['count']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="intensity-season"><?php esc_html_e('Сезон:', 'parfume-reviews'); ?></label>
                            <select id="intensity-season" name="season">
                                <option value=""><?php esc_html_e('Всички сезони', 'parfume-reviews'); ?></option>
                                <option value="spring"><?php esc_html_e('Пролет', 'parfume-reviews'); ?></option>
                                <option value="summer"><?php esc_html_e('Лято', 'parfume-reviews'); ?></option>
                                <option value="autumn"><?php esc_html_e('Есен', 'parfume-reviews'); ?></option>
                                <option value="winter"><?php esc_html_e('Зима', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="intensity-brand"><?php esc_html_e('Марка:', 'parfume-reviews'); ?></label>
                            <select id="intensity-brand" name="brand">
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
                        <button class="reset-intensity-filters" type="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Изчисти филтрите', 'parfume-reviews'); ?>
                        </button>
                        <div class="intensity-results-info">
                            <span class="intensity-results-count"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Loading State -->
            <div class="intensity-loading" style="display: none;">
                <div class="loading-content">
                    <div class="loading-spinner">
                        <span class="intensity-icon"><?php echo esc_html($current_intensity_config['icon']); ?></span>
                    </div>
                    <p><?php esc_html_e('Зареждане на парфюми с тази интензивност...', 'parfume-reviews'); ?></p>
                </div>
            </div>
            
            <!-- Parfumes Grid -->
            <div class="intensity-parfumes-container" data-view="grid">
                <?php
                // Main intensity parfumes query
                $main_intensity_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 12,
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'intensity',
                            'field' => 'term_id',
                            'terms' => $intensity_id
                        )
                    ),
                    'meta_key' => '_parfume_rating',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ));
                
                if ($main_intensity_query->have_posts()) : ?>
                    <div class="parfumes-grid" id="intensity-parfumes-grid">
                        <?php while ($main_intensity_query->have_posts()) : $main_intensity_query->the_post(); ?>
                            <?php get_template_part('template-parts/parfume-card', null, array('show_intensity' => false, 'highlight_projection' => true)); ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="intensity-pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $main_intensity_query->max_num_pages,
                            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Предишна', 'parfume-reviews'),
                            'next_text' => esc_html__('Следваща', 'parfume-reviews') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                            'mid_size' => 2
                        ));
                        ?>
                    </div>
                    
                <?php else : ?>
                    <div class="no-intensity-parfumes">
                        <div class="no-content-icon">
                            <span class="intensity-emoji"><?php echo esc_html($current_intensity_config['icon']); ?></span>
                        </div>
                        <h3><?php esc_html_e('Няма намерени парфюми', 'parfume-reviews'); ?></h3>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: intensity level name */
                                esc_html__('В момента няма парфюми с %s интензивност в нашата база данни.', 'parfume-reviews'),
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
 * Hook: parfume_reviews_intensity_footer
 */
do_action('parfume_reviews_intensity_footer');
?>

<!-- Enhanced JavaScript for Intensity Template -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Projection Diagram Animation
    function animateProjectionDiagram() {
        $('.projection-circle').each(function(index) {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).delay(index * 200).animate({
                    opacity: 1,
                    transform: 'scale(1)'
                }, 600, 'easeOutBack');
            }
        });
    }
    
    // Environmental Suitability Hover Effects
    $('.environment-item').on('mouseenter', function() {
        $(this).addClass('environment-hover');
        const compatibility = $(this).find('.compatibility-percentage').text();
        const envName = $(this).find('.environment-name').text();
        $(this).attr('title', envName + ': ' + compatibility + ' съвместимост');
    }).on('mouseleave', function() {
        $(this).removeClass('environment-hover');
    });
    
    // Correlation Charts Animation
    function animateCorrelationCharts() {
        // Longevity bars
        $('.longevity-bar').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                const height = $(this).css('height');
                $(this).css('height', 0).animate({height: height}, 800, 'easeOutCubic');
            }
        });
        
        // Sillage segments
        $('.sillage-segment').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                const width = $(this).css('width');
                $(this).css('width', 0).animate({width: width}, 1000, 'easeOutCubic');
            }
        });
        
        // Age circles
        $('.age-circle').each(function(index) {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).delay(index * 150).animate({
                    opacity: 1,
                    transform: 'scale(1)'
                }, 500);
            }
        });
        
        // Family bars
        $('.family-fill').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                const width = $(this).css('width');
                $(this).css('width', 0).animate({width: width}, 800, 'easeOutCubic');
            }
        });
        
        // Season bars
        $('.season-fill').each(function() {
            if (isElementInViewport(this) && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                const width = $(this).css('width');
                $(this).css('width', 0).animate({width: width}, 700, 'easeOutCubic');
            }
        });
    }
    
    // Notes Cloud Interaction
    $('.characteristic-note').on('mouseenter', function() {
        const count = $(this).data('count');
        const noteName = $(this).text().replace(/\d+$/, '').trim();
        $(this).attr('title', noteName + ' - използвана в ' + count + ' парфюма с тази интензивност');
        $(this).addClass('note-hover');
    }).on('mouseleave', function() {
        $(this).removeClass('note-hover');
    });
    
    // Advanced Filtering for Intensity Parfumes
    const $intensityFilters = $('#intensity-sort, #intensity-gender, #intensity-aroma, #intensity-season, #intensity-brand');
    const $intensityGrid = $('#intensity-parfumes-grid');
    const $intensityLoading = $('.intensity-loading');
    const $intensityResultsCount = $('.intensity-results-count');
    
    let intensityFilterTimeout;
    
    $intensityFilters.on('change', function() {
        clearTimeout(intensityFilterTimeout);
        intensityFilterTimeout = setTimeout(applyIntensityFilters, 300);
    });
    
    function applyIntensityFilters() {
        $intensityLoading.show();
        $intensityGrid.addClass('filtering');
        
        const filters = {
            sort: $('#intensity-sort').val(),
            gender: $('#intensity-gender').val(),
            aroma_type: $('#intensity-aroma').val(),
            season: $('#intensity-season').val(),
            brand: $('#intensity-brand').val()
        };
        
        // Simulate AJAX filtering
        setTimeout(function() {
            filterIntensityParfumes(filters);
            $intensityLoading.hide();
            $intensityGrid.removeClass('filtering');
        }, 600);
    }
    
    function filterIntensityParfumes(filters) {
        const $items = $intensityGrid.find('.parfume-item');
        let visibleCount = 0;
        
        $items.each(function() {
            let showItem = true;
            const $item = $(this);
            
            // Apply filters
            if (filters.gender && $item.data('gender') !== filters.gender) {
                showItem = false;
            }
            if (filters.aroma_type && $item.data('aroma-type') !== filters.aroma_type) {
                showItem = false;
            }
            if (filters.season) {
                const itemSeasons = ($item.data('seasons') || '').split(',');
                if (!itemSeasons.includes(filters.season)) {
                    showItem = false;
                }
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
        $intensityResultsCount.text(visibleCount + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
        
        // Apply sorting
        if (filters.sort) {
            sortIntensityParfumes(filters.sort);
        }
    }
    
    function sortIntensityParfumes(sortBy) {
        const $visibleItems = $intensityGrid.find('.parfume-item:visible');
        
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
        
        $intensityGrid.append($visibleItems);
    }
    
    // Reset filters
    $('.reset-intensity-filters').on('click', function() {
        $intensityFilters.val('');
        $intensityGrid.find('.parfume-item').show();
        $intensityResultsCount.text($intensityGrid.find('.parfume-item').length + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
    });
    
    // View toggle for intensity parfumes
    $('.view-toggle .view-btn').on('click', function(e) {
        e.preventDefault();
        
        const view = $(this).data('view');
        $('.view-toggle .view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.intensity-parfumes-container').attr('data-view', view);
        
        // Store preference
        localStorage.setItem('intensity_parfumes_view', view);
    });
    
    // Load saved view preference
    const savedIntensityView = localStorage.getItem('intensity_parfumes_view');
    if (savedIntensityView) {
        $('.view-toggle .view-btn[data-view="' + savedIntensityView + '"]').click();
    }
    
    // Enhanced highlight card interactions
    $('.intensity-highlight-card').on('mouseenter', function() {
        $(this).addClass('intensity-highlight-hover');
    }).on('mouseleave', function() {
        $(this).removeClass('intensity-highlight-hover');
    });
    
    // Family ranking hover effects
    $('.family-ranking-item').on('mouseenter', function() {
        $(this).addClass('family-hover');
    }).on('mouseleave', function() {
        $(this).removeClass('family-hover');
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
    $(window).on('scroll resize', function() {
        animateProjectionDiagram();
        animateCorrelationCharts();
    });
    
    // Initial checks
    animateProjectionDiagram();
    animateCorrelationCharts();
    
    // Initialize results count
    $intensityResultsCount.text($intensityGrid.find('.parfume-item').length + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
    
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
    
    // Projection circle tooltips
    $('.projection-circle').on('mouseenter', function() {
        const label = $(this).find('.projection-label').text();
        $(this).attr('title', label);
    });
    
    // Application spot interactions
    $('.application-spot, .time-recommendation').on('mouseenter', function() {
        $(this).addClass('recommendation-hover');
    }).on('mouseleave', function() {
        $(this).removeClass('recommendation-hover');
    });
    
});
</script>

<?php get_footer(); ?>