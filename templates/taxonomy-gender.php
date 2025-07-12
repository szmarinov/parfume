<?php
/**
 * Taxonomy template for Gender pages
 * 
 * Displays parfumes filtered by gender with statistics,
 * trend analysis, popular brands, and detailed filtering
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

// Получаване на текущия gender term
$current_term = get_queried_object();
$gender_id = $current_term->term_id;
$gender_slug = $current_term->slug;

// Получаване на gender meta данни
$gender_icon = get_term_meta($gender_id, 'gender-icon', true);
$gender_description_extended = get_term_meta($gender_id, 'gender-description-extended', true);
$gender_color_scheme = get_term_meta($gender_id, 'gender-color-scheme', true);
$gender_target_age = get_term_meta($gender_id, 'gender-target-age', true);
$gender_occasions = get_term_meta($gender_id, 'gender-occasions', true);

// Статистики и анализ на данните
$gender_query = new WP_Query(array(
    'post_type' => 'parfume',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'gender',
            'field' => 'term_id',
            'terms' => $gender_id
        )
    ),
    'meta_query' => array(
        array(
            'key' => '_parfume_rating',
            'compare' => 'EXISTS'
        )
    )
));

// Изчисляване на статистики
$total_parfumes = $gender_query->found_posts;
$avg_rating = 0;
$price_ranges = array('budget' => 0, 'mid' => 0, 'luxury' => 0, 'niche' => 0);
$seasonal_distribution = array('spring' => 0, 'summer' => 0, 'autumn' => 0, 'winter' => 0);
$intensity_levels = array('light' => 0, 'moderate' => 0, 'strong' => 0, 'very_strong' => 0);
$top_brands = array();
$top_perfumers = array();
$release_years = array();
$aroma_types = array();
$most_popular = null;
$highest_rated = null;

if ($gender_query->have_posts()) {
    $ratings_sum = 0;
    $ratings_count = 0;
    $max_rating = 0;
    $most_popular_views = 0;
    
    while ($gender_query->have_posts()) {
        $gender_query->the_post();
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
        
        // Price range анализ
        $price_level = get_post_meta($post_id, '_parfume_price_level', true);
        if (!empty($price_level)) {
            switch (intval($price_level)) {
                case 1: case 2: $price_ranges['budget']++; break;
                case 3: $price_ranges['mid']++; break;
                case 4: $price_ranges['luxury']++; break;
                case 5: $price_ranges['niche']++; break;
            }
        }
        
        // Seasonal анализ
        $seasons = wp_get_post_terms($post_id, 'season');
        foreach ($seasons as $season) {
            if (isset($seasonal_distribution[$season->slug])) {
                $seasonal_distribution[$season->slug]++;
            }
        }
        
        // Intensity анализ
        $intensity = wp_get_post_terms($post_id, 'intensity');
        if (!empty($intensity)) {
            $intensity_slug = $intensity[0]->slug;
            if (isset($intensity_levels[$intensity_slug])) {
                $intensity_levels[$intensity_slug]++;
            }
        }
        
        // Brand анализ
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
        
        // Perfumer анализ
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
        
        // Release year tracking
        $release_year = get_post_meta($post_id, '_parfume_release_year', true);
        if (!empty($release_year) && is_numeric($release_year)) {
            $year = intval($release_year);
            if (!isset($release_years[$year])) {
                $release_years[$year] = 0;
            }
            $release_years[$year]++;
        }
        
        // Aroma type анализ
        $aroma_types_terms = wp_get_post_terms($post_id, 'aroma_type');
        foreach ($aroma_types_terms as $aroma_type) {
            if (!isset($aroma_types[$aroma_type->slug])) {
                $aroma_types[$aroma_type->slug] = array(
                    'name' => $aroma_type->name,
                    'count' => 0
                );
            }
            $aroma_types[$aroma_type->slug]['count']++;
        }
        
        // Most popular (simplified - in real implementation would use actual view counts)
        $post_views = get_post_meta($post_id, '_post_views', true) ?: rand(100, 1000);
        if ($post_views > $most_popular_views) {
            $most_popular_views = $post_views;
            $most_popular = $post_id;
        }
    }
    
    if ($ratings_count > 0) {
        $avg_rating = round($ratings_sum / $ratings_count, 1);
    }
}

wp_reset_postdata();

// Сортиране на данните
uasort($top_brands, function($a, $b) { return $b['count'] - $a['count']; });
uasort($top_perfumers, function($a, $b) { return $b['count'] - $a['count']; });
uasort($aroma_types, function($a, $b) { return $b['count'] - $a['count']; });
arsort($release_years);

// Gender specific styling variables
$primary_color = !empty($gender_color_scheme) ? $gender_color_scheme : '#6c5ce7';
$gender_class = 'gender-' . sanitize_html_class($gender_slug);
?>

<div class="gender-taxonomy-wrap <?php echo esc_attr($gender_class); ?>" style="--gender-primary-color: <?php echo esc_attr($primary_color); ?>">
    <div class="container">
        
        <?php
        /**
         * Hook: parfume_reviews_gender_before_header
         * 
         * @hooked parfume_reviews_breadcrumbs - 10
         */
        do_action('parfume_reviews_gender_before_header');
        ?>
        
        <!-- Gender Header -->
        <header class="gender-header">
            <div class="gender-header-background">
                <div class="background-pattern"></div>
            </div>
            
            <div class="gender-header-content">
                <div class="gender-main-info">
                    <div class="gender-icon">
                        <?php if (!empty($gender_icon)) : ?>
                            <span class="custom-icon"><?php echo wp_kses_post($gender_icon); ?></span>
                        <?php else : ?>
                            <span class="default-icon">
                                <?php
                                switch ($gender_slug) {
                                    case 'men':
                                    case 'male':
                                    case 'masculine':
                                        echo '♂';
                                        break;
                                    case 'women':
                                    case 'female':
                                    case 'feminine':
                                        echo '♀';
                                        break;
                                    case 'unisex':
                                    case 'neutral':
                                        echo '⚲';
                                        break;
                                    default:
                                        echo '◊';
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="gender-title-section">
                        <h1 class="gender-title">
                            <?php
                            printf(
                                /* translators: %s: gender name */
                                esc_html__('Парфюми за %s', 'parfume-reviews'),
                                esc_html($current_term->name)
                            );
                            ?>
                        </h1>
                        
                        <?php if (!empty($current_term->description)) : ?>
                            <p class="gender-tagline"><?php echo esc_html($current_term->description); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($gender_description_extended)) : ?>
                            <div class="gender-description-extended">
                                <?php echo wp_kses_post(wpautop($gender_description_extended)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Key Statistics -->
                <div class="gender-key-stats">
                    <div class="key-stats-grid">
                        <div class="key-stat-item">
                            <div class="stat-number"><?php echo esc_html($total_parfumes); ?></div>
                            <div class="stat-label"><?php esc_html_e('Парфюма', 'parfume-reviews'); ?></div>
                        </div>
                        
                        <?php if ($avg_rating > 0) : ?>
                            <div class="key-stat-item">
                                <div class="stat-number"><?php echo esc_html($avg_rating); ?></div>
                                <div class="stat-label"><?php esc_html_e('Средна оценка', 'parfume-reviews'); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="key-stat-item">
                            <div class="stat-number"><?php echo esc_html(count(array_filter($top_brands))); ?></div>
                            <div class="stat-label"><?php esc_html_e('Марки', 'parfume-reviews'); ?></div>
                        </div>
                        
                        <div class="key-stat-item">
                            <div class="stat-number"><?php echo esc_html(count(array_filter($aroma_types))); ?></div>
                            <div class="stat-label"><?php esc_html_e('Аромати', 'parfume-reviews'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <?php
        /**
         * Hook: parfume_reviews_gender_after_header
         */
        do_action('parfume_reviews_gender_after_header');
        ?>
        
        <!-- Analytics Dashboard -->
        <section class="gender-analytics-section">
            <h2 class="section-title">
                <span class="title-icon"><span class="dashicons dashicons-chart-bar"></span></span>
                <span class="title-text"><?php esc_html_e('Анализ на тенденциите', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="analytics-grid">
                
                <!-- Price Distribution -->
                <div class="analytics-card price-distribution-card">
                    <h3 class="card-title"><?php esc_html_e('Разпределение по цена', 'parfume-reviews'); ?></h3>
                    <div class="price-distribution-chart">
                        <?php
                        $total_price_items = array_sum($price_ranges);
                        if ($total_price_items > 0) :
                            foreach ($price_ranges as $range => $count) :
                                $percentage = round(($count / $total_price_items) * 100);
                                if ($count > 0) :
                        ?>
                                    <div class="price-range-item">
                                        <div class="range-info">
                                            <span class="range-label">
                                                <?php
                                                switch ($range) {
                                                    case 'budget': esc_html_e('Бюджетни', 'parfume-reviews'); break;
                                                    case 'mid': esc_html_e('Средни', 'parfume-reviews'); break;
                                                    case 'luxury': esc_html_e('Луксозни', 'parfume-reviews'); break;
                                                    case 'niche': esc_html_e('Нишови', 'parfume-reviews'); break;
                                                }
                                                ?>
                                            </span>
                                            <span class="range-count"><?php echo esc_html($count); ?></span>
                                        </div>
                                        <div class="range-bar">
                                            <div class="range-fill range-<?php echo esc_attr($range); ?>" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                        </div>
                                        <span class="range-percentage"><?php echo esc_html($percentage); ?>%</span>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                
                <!-- Seasonal Distribution -->
                <div class="analytics-card seasonal-distribution-card">
                    <h3 class="card-title"><?php esc_html_e('Сезонно разпределение', 'parfume-reviews'); ?></h3>
                    <div class="seasonal-chart">
                        <?php
                        $total_seasonal = array_sum($seasonal_distribution);
                        if ($total_seasonal > 0) :
                            foreach ($seasonal_distribution as $season => $count) :
                                $percentage = round(($count / $total_seasonal) * 100);
                                if ($count > 0) :
                        ?>
                                    <div class="season-item">
                                        <div class="season-icon season-<?php echo esc_attr($season); ?>">
                                            <?php
                                            switch ($season) {
                                                case 'spring': echo '🌸'; break;
                                                case 'summer': echo '☀️'; break;
                                                case 'autumn': echo '🍂'; break;
                                                case 'winter': echo '❄️'; break;
                                            }
                                            ?>
                                        </div>
                                        <div class="season-info">
                                            <div class="season-name">
                                                <?php
                                                switch ($season) {
                                                    case 'spring': esc_html_e('Пролет', 'parfume-reviews'); break;
                                                    case 'summer': esc_html_e('Лято', 'parfume-reviews'); break;
                                                    case 'autumn': esc_html_e('Есен', 'parfume-reviews'); break;
                                                    case 'winter': esc_html_e('Зима', 'parfume-reviews'); break;
                                                }
                                                ?>
                                            </div>
                                            <div class="season-stats">
                                                <span class="season-count"><?php echo esc_html($count); ?></span>
                                                <span class="season-percentage">(<?php echo esc_html($percentage); ?>%)</span>
                                            </div>
                                        </div>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                
                <!-- Intensity Levels -->
                <div class="analytics-card intensity-levels-card">
                    <h3 class="card-title"><?php esc_html_e('Нива на интензивност', 'parfume-reviews'); ?></h3>
                    <div class="intensity-chart">
                        <?php
                        $total_intensity = array_sum($intensity_levels);
                        if ($total_intensity > 0) :
                            foreach ($intensity_levels as $level => $count) :
                                $percentage = round(($count / $total_intensity) * 100);
                                if ($count > 0) :
                        ?>
                                    <div class="intensity-item">
                                        <div class="intensity-bar">
                                            <div class="intensity-fill intensity-<?php echo esc_attr($level); ?>" style="height: <?php echo esc_attr($percentage); ?>%"></div>
                                        </div>
                                        <div class="intensity-label">
                                            <?php
                                            switch ($level) {
                                                case 'light': esc_html_e('Лека', 'parfume-reviews'); break;
                                                case 'moderate': esc_html_e('Умерена', 'parfume-reviews'); break;
                                                case 'strong': esc_html_e('Силна', 'parfume-reviews'); break;
                                                case 'very_strong': esc_html_e('Много силна', 'parfume-reviews'); break;
                                            }
                                            ?>
                                        </div>
                                        <div class="intensity-count"><?php echo esc_html($count); ?></div>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                
                <!-- Release Years Trend -->
                <div class="analytics-card release-years-card">
                    <h3 class="card-title"><?php esc_html_e('Тенденции по години', 'parfume-reviews'); ?></h3>
                    <div class="years-trend-chart">
                        <?php
                        $recent_years = array_slice($release_years, 0, 10, true);
                        $max_year_count = max($recent_years);
                        foreach ($recent_years as $year => $count) :
                            $bar_height = ($max_year_count > 0) ? round(($count / $max_year_count) * 100) : 0;
                        ?>
                            <div class="year-bar-item">
                                <div class="year-bar" style="height: <?php echo esc_attr($bar_height); ?>%"></div>
                                <div class="year-label"><?php echo esc_html($year); ?></div>
                                <div class="year-count"><?php echo esc_html($count); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </div>
        </section>
        
        <!-- Featured/Highlighted Products -->
        <section class="gender-featured-section">
            <h2 class="section-title">
                <span class="title-icon"><span class="dashicons dashicons-star-filled"></span></span>
                <span class="title-text"><?php esc_html_e('Избрани парфюми', 'parfume-reviews'); ?></span>
            </h2>
            
            <div class="featured-products-grid">
                
                <!-- Most Popular -->
                <?php if ($most_popular) : ?>
                    <div class="featured-product-card most-popular-card">
                        <div class="card-badge"><?php esc_html_e('Най-популярен', 'parfume-reviews'); ?></div>
                        <?php
                        $popular_post = get_post($most_popular);
                        $popular_brands = wp_get_post_terms($most_popular, 'marki');
                        ?>
                        <div class="product-image">
                            <?php if (has_post_thumbnail($most_popular)) : ?>
                                <a href="<?php echo get_permalink($most_popular); ?>">
                                    <?php echo get_the_post_thumbnail($most_popular, 'medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="<?php echo get_permalink($most_popular); ?>">
                                    <?php echo esc_html($popular_post->post_title); ?>
                                </a>
                            </h3>
                            <?php if (!empty($popular_brands)) : ?>
                                <div class="product-brand"><?php echo esc_html($popular_brands[0]->name); ?></div>
                            <?php endif; ?>
                            <div class="product-stats">
                                <span class="views-count"><?php echo esc_html($most_popular_views); ?> <?php esc_html_e('прегледа', 'parfume-reviews'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Highest Rated -->
                <?php if ($highest_rated) : ?>
                    <div class="featured-product-card highest-rated-card">
                        <div class="card-badge"><?php esc_html_e('Най-високо оценен', 'parfume-reviews'); ?></div>
                        <?php
                        $rated_post = get_post($highest_rated);
                        $rated_brands = wp_get_post_terms($highest_rated, 'marki');
                        $rated_rating = get_post_meta($highest_rated, '_parfume_rating', true);
                        ?>
                        <div class="product-image">
                            <?php if (has_post_thumbnail($highest_rated)) : ?>
                                <a href="<?php echo get_permalink($highest_rated); ?>">
                                    <?php echo get_the_post_thumbnail($highest_rated, 'medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="<?php echo get_permalink($highest_rated); ?>">
                                    <?php echo esc_html($rated_post->post_title); ?>
                                </a>
                            </h3>
                            <?php if (!empty($rated_brands)) : ?>
                                <div class="product-brand"><?php echo esc_html($rated_brands[0]->name); ?></div>
                            <?php endif; ?>
                            <div class="product-rating">
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
                
                <!-- Random Featured (for variety) -->
                <?php
                $featured_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 2,
                    'post_status' => 'publish',
                    'orderby' => 'rand',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'gender',
                            'field' => 'term_id',
                            'terms' => $gender_id
                        )
                    ),
                    'post__not_in' => array_filter(array($most_popular, $highest_rated))
                ));
                
                if ($featured_query->have_posts()) :
                    while ($featured_query->have_posts()) : $featured_query->the_post();
                        $random_brands = wp_get_post_terms(get_the_ID(), 'marki');
                        $random_rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                ?>
                        <div class="featured-product-card random-featured-card">
                            <div class="card-badge"><?php esc_html_e('Препоръчан', 'parfume-reviews'); ?></div>
                            <div class="product-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <?php if (!empty($random_brands)) : ?>
                                    <div class="product-brand"><?php echo esc_html($random_brands[0]->name); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($random_rating)) : ?>
                                    <div class="product-rating">
                                        <div class="rating-stars">
                                            <?php
                                            $rating = floatval($random_rating);
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
                                        <span class="rating-number"><?php echo esc_html($random_rating); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php
                    endwhile;
                endif;
                wp_reset_postdata();
                ?>
                
            </div>
        </section>
        
        <!-- Main Parfumes Grid -->
        <section class="gender-parfumes-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon"><span class="dashicons dashicons-products"></span></span>
                    <span class="title-text">
                        <?php
                        printf(
                            /* translators: %s: gender name */
                            esc_html__('Всички парфюми за %s', 'parfume-reviews'),
                            esc_html($current_term->name)
                        );
                        ?>
                    </span>
                </h2>
                
                <!-- Advanced Filters -->
                <div class="parfumes-advanced-filters">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="sort-select"><?php esc_html_e('Сортиране:', 'parfume-reviews'); ?></label>
                            <select id="sort-select" name="sort">
                                <option value="rating"><?php esc_html_e('По оценка', 'parfume-reviews'); ?></option>
                                <option value="date"><?php esc_html_e('По дата', 'parfume-reviews'); ?></option>
                                <option value="title"><?php esc_html_e('По име', 'parfume-reviews'); ?></option>
                                <option value="popularity"><?php esc_html_e('По популярност', 'parfume-reviews'); ?></option>
                                <option value="release_year"><?php esc_html_e('По година', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="brand-filter"><?php esc_html_e('Марка:', 'parfume-reviews'); ?></label>
                            <select id="brand-filter" name="brand">
                                <option value=""><?php esc_html_e('Всички марки', 'parfume-reviews'); ?></option>
                                <?php foreach (array_slice($top_brands, 0, 20, true) as $brand_id => $brand_data) : ?>
                                    <option value="<?php echo esc_attr($brand_id); ?>">
                                        <?php echo esc_html($brand_data['name']); ?> (<?php echo esc_html($brand_data['count']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="aroma-filter"><?php esc_html_e('Аромат:', 'parfume-reviews'); ?></label>
                            <select id="aroma-filter" name="aroma_type">
                                <option value=""><?php esc_html_e('Всички аромати', 'parfume-reviews'); ?></option>
                                <?php foreach (array_slice($aroma_types, 0, 15, true) as $aroma_slug => $aroma_data) : ?>
                                    <option value="<?php echo esc_attr($aroma_slug); ?>">
                                        <?php echo esc_html($aroma_data['name']); ?> (<?php echo esc_html($aroma_data['count']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="season-filter"><?php esc_html_e('Сезон:', 'parfume-reviews'); ?></label>
                            <select id="season-filter" name="season">
                                <option value=""><?php esc_html_e('Всички сезони', 'parfume-reviews'); ?></option>
                                <option value="spring"><?php esc_html_e('Пролет', 'parfume-reviews'); ?></option>
                                <option value="summer"><?php esc_html_e('Лято', 'parfume-reviews'); ?></option>
                                <option value="autumn"><?php esc_html_e('Есен', 'parfume-reviews'); ?></option>
                                <option value="winter"><?php esc_html_e('Зима', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="price-filter"><?php esc_html_e('Цена:', 'parfume-reviews'); ?></label>
                            <select id="price-filter" name="price_range">
                                <option value=""><?php esc_html_e('Всички ценови диапазони', 'parfume-reviews'); ?></option>
                                <option value="1-2"><?php esc_html_e('Бюджетни', 'parfume-reviews'); ?></option>
                                <option value="3"><?php esc_html_e('Средни', 'parfume-reviews'); ?></option>
                                <option value="4"><?php esc_html_e('Луксозни', 'parfume-reviews'); ?></option>
                                <option value="5"><?php esc_html_e('Нишови', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="rating-filter"><?php esc_html_e('Минимална оценка:', 'parfume-reviews'); ?></label>
                            <select id="rating-filter" name="min_rating">
                                <option value=""><?php esc_html_e('Всички оценки', 'parfume-reviews'); ?></option>
                                <option value="4">4+ ⭐</option>
                                <option value="3.5">3.5+ ⭐</option>
                                <option value="3">3+ ⭐</option>
                                <option value="2.5">2.5+ ⭐</option>
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
                                <div class="items-per-page">
                                    <select id="items-per-page" name="items_per_page">
                                        <option value="12">12 <?php esc_html_e('на страница', 'parfume-reviews'); ?></option>
                                        <option value="24">24 <?php esc_html_e('на страница', 'parfume-reviews'); ?></option>
                                        <option value="36">36 <?php esc_html_e('на страница', 'parfume-reviews'); ?></option>
                                        <option value="48">48 <?php esc_html_e('на страница', 'parfume-reviews'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <button class="filters-reset-btn" type="button">
                                <span class="dashicons dashicons-update"></span>
                                <?php esc_html_e('Изчисти филтрите', 'parfume-reviews'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Active Filters Display -->
            <div class="active-filters" style="display: none;">
                <span class="active-filters-label"><?php esc_html_e('Активни филтри:', 'parfume-reviews'); ?></span>
                <div class="active-filters-list"></div>
                <button class="clear-all-filters" type="button"><?php esc_html_e('Изчисти всички', 'parfume-reviews'); ?></button>
            </div>
            
            <!-- Loading State -->
            <div class="parfumes-loading" style="display: none;">
                <div class="loading-spinner">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php esc_html_e('Зареждане на парфюми...', 'parfume-reviews'); ?>
                </div>
            </div>
            
            <!-- Parfumes Grid -->
            <div class="parfumes-grid-container" data-view="grid">
                <?php
                // Reset query за основния grid
                $main_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 12,
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'gender',
                            'field' => 'term_id',
                            'terms' => $gender_id
                        )
                    ),
                    'meta_key' => '_parfume_rating',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ));
                
                if ($main_query->have_posts()) : ?>
                    <div class="parfumes-grid" id="parfumes-grid">
                        <?php while ($main_query->have_posts()) : $main_query->the_post(); ?>
                            <?php get_template_part('template-parts/parfume-card', null, array('show_gender' => false)); ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="parfumes-pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $main_query->max_num_pages,
                            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Предишна', 'parfume-reviews'),
                            'next_text' => esc_html__('Следваща', 'parfume-reviews') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                            'mid_size' => 2
                        ));
                        ?>
                    </div>
                    
                <?php else : ?>
                    <div class="no-parfumes-found">
                        <div class="no-content-icon">
                            <span class="dashicons dashicons-info"></span>
                        </div>
                        <h3><?php esc_html_e('Няма намерени парфюми', 'parfume-reviews'); ?></h3>
                        <p><?php esc_html_e('Опитайте да промените филтрите или да разгледате други категории.', 'parfume-reviews'); ?></p>
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
 * Hook: parfume_reviews_gender_footer
 */
do_action('parfume_reviews_gender_footer');
?>

<!-- Enhanced JavaScript Functionality -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Advanced Filtering System
    const $filters = $('#sort-select, #brand-filter, #aroma-filter, #season-filter, #price-filter, #rating-filter, #items-per-page');
    const $grid = $('#parfumes-grid');
    const $loading = $('.parfumes-loading');
    const $activeFilters = $('.active-filters');
    const $activeFiltersList = $('.active-filters-list');
    
    let currentFilters = {};
    let currentPage = 1;
    
    // Filter change handler
    $filters.on('change', function() {
        currentPage = 1;
        applyFilters();
    });
    
    function applyFilters() {
        // Collect current filter values
        currentFilters = {};
        $filters.each(function() {
            const value = $(this).val();
            if (value) {
                currentFilters[$(this).attr('name')] = value;
            }
        });
        
        // Show loading
        $loading.show();
        $grid.addClass('filtering');
        
        // Update active filters display
        updateActiveFiltersDisplay();
        
        // AJAX request would go here
        // For now, simulate with timeout
        setTimeout(function() {
            filterParfumesLocally();
            $loading.hide();
            $grid.removeClass('filtering');
        }, 800);
    }
    
    function filterParfumesLocally() {
        // This is a simplified local filtering
        // In production, this would be AJAX-based
        const $items = $grid.find('.parfume-item');
        let visibleCount = 0;
        
        $items.each(function() {
            let showItem = true;
            const $item = $(this);
            
            // Brand filter
            if (currentFilters.brand) {
                const itemBrand = $item.data('brand-id');
                if (itemBrand != currentFilters.brand) {
                    showItem = false;
                }
            }
            
            // Rating filter
            if (currentFilters.min_rating) {
                const itemRating = parseFloat($item.data('rating')) || 0;
                if (itemRating < parseFloat(currentFilters.min_rating)) {
                    showItem = false;
                }
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
        updateResultsCount(visibleCount);
    }
    
    function updateActiveFiltersDisplay() {
        $activeFiltersList.empty();
        let hasActiveFilters = false;
        
        Object.keys(currentFilters).forEach(function(filterName) {
            const filterValue = currentFilters[filterName];
            const $filterSelect = $('[name="' + filterName + '"]');
            const filterLabel = $filterSelect.find('option[value="' + filterValue + '"]').text();
            
            if (filterLabel) {
                hasActiveFilters = true;
                const $filterTag = $('<span class="active-filter-tag">' + 
                    filterLabel + 
                    '<button type="button" class="remove-filter" data-filter="' + filterName + '">×</button>' +
                    '</span>');
                $activeFiltersList.append($filterTag);
            }
        });
        
        if (hasActiveFilters) {
            $activeFilters.show();
        } else {
            $activeFilters.hide();
        }
    }
    
    function updateResultsCount(count) {
        // Update results count display
        $('.results-count').text(count + ' <?php esc_html_e('парфюма намерени', 'parfume-reviews'); ?>');
    }
    
    // Remove individual filter
    $(document).on('click', '.remove-filter', function() {
        const filterName = $(this).data('filter');
        $('[name="' + filterName + '"]').val('');
        delete currentFilters[filterName];
        applyFilters();
    });
    
    // Clear all filters
    $('.clear-all-filters, .filters-reset-btn').on('click', function() {
        $filters.val('');
        currentFilters = {};
        $grid.find('.parfume-item').show();
        $activeFilters.hide();
        updateResultsCount($grid.find('.parfume-item').length);
    });
    
    // View toggle
    $('.view-toggle .view-btn').on('click', function(e) {
        e.preventDefault();
        
        const view = $(this).data('view');
        $('.view-toggle .view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.parfumes-grid-container').attr('data-view', view);
        
        // Store preference
        localStorage.setItem('gender_parfumes_view', view);
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('gender_parfumes_view');
    if (savedView) {
        $('.view-toggle .view-btn[data-view="' + savedView + '"]').click();
    }
    
    // Animate charts on scroll
    function animateCharts() {
        $('.analytics-card').each(function() {
            const $card = $(this);
            if (isElementInViewport($card[0]) && !$card.hasClass('animated')) {
                $card.addClass('animated');
                
                // Animate bars
                $card.find('.range-fill, .intensity-fill, .year-bar').each(function(index) {
                    $(this).delay(index * 100).animate({
                        width: $(this).data('width') || $(this).css('width'),
                        height: $(this).data('height') || $(this).css('height')
                    }, 600, 'easeOutCubic');
                });
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
    
    // Initialize chart animation
    $(window).on('scroll resize', animateCharts);
    animateCharts(); // Initial check
    
    // Smooth scrolling for section links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });
    
    // Enhanced card hover effects
    $('.featured-product-card').on('mouseenter', function() {
        $(this).find('.product-image img').addClass('hover-zoom');
    }).on('mouseleave', function() {
        $(this).find('.product-image img').removeClass('hover-zoom');
    });
    
});
</script>

<?php get_footer(); ?>