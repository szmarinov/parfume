<?php
/**
 * Taxonomy template for Perfumer pages
 * 
 * Displays perfumer profile with biography, photo, career timeline,
 * achievements, statistics, and created parfumes
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

// Получаване на текущия perfumer term
$current_term = get_queried_object();
$perfumer_id = $current_term->term_id;

// Получаване на perfumer meta данни
$perfumer_image_id = get_term_meta($perfumer_id, 'perfumer-image-id', true);
$perfumer_bio = get_term_meta($perfumer_id, 'perfumer-bio', true);
$perfumer_birth_year = get_term_meta($perfumer_id, 'perfumer-birth-year', true);
$perfumer_nationality = get_term_meta($perfumer_id, 'perfumer-nationality', true);
$perfumer_education = get_term_meta($perfumer_id, 'perfumer-education', true);
$perfumer_career_start = get_term_meta($perfumer_id, 'perfumer-career-start', true);
$perfumer_signature_notes = get_term_meta($perfumer_id, 'perfumer-signature-notes', true);
$perfumer_awards = get_term_meta($perfumer_id, 'perfumer-awards', true);
$perfumer_brands_worked = get_term_meta($perfumer_id, 'perfumer-brands-worked', true);
$perfumer_social_links = get_term_meta($perfumer_id, 'perfumer-social-links', true);
$perfumer_quotes = get_term_meta($perfumer_id, 'perfumer-quotes', true);

// Статистики за парфюмите
$parfumes_query = new WP_Query(array(
    'post_type' => 'parfume',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'perfumer',
            'field' => 'term_id',
            'terms' => $perfumer_id
        )
    ),
    'meta_key' => '_parfume_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
));

$total_parfumes = $parfumes_query->found_posts;
$avg_rating = 0;
$total_ratings = 0;
$highest_rated = null;
$brands_count = 0;
$release_years = array();

if ($parfumes_query->have_posts()) {
    $ratings_sum = 0;
    $ratings_count = 0;
    $brands = array();
    
    while ($parfumes_query->have_posts()) {
        $parfumes_query->the_post();
        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
        $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
        
        if (!empty($rating) && is_numeric($rating)) {
            $ratings_sum += floatval($rating);
            $ratings_count++;
            
            if (!$highest_rated || $rating > get_post_meta($highest_rated, '_parfume_rating', true)) {
                $highest_rated = get_the_ID();
            }
        }
        
        if (!empty($release_year) && is_numeric($release_year)) {
            $release_years[] = intval($release_year);
        }
        
        // Събиране на марки
        $parfume_brands = wp_get_post_terms(get_the_ID(), 'marki');
        foreach ($parfume_brands as $brand) {
            $brands[$brand->term_id] = $brand->name;
        }
    }
    
    if ($ratings_count > 0) {
        $avg_rating = round($ratings_sum / $ratings_count, 1);
        $total_ratings = $ratings_count;
    }
    
    $brands_count = count($brands);
}

wp_reset_postdata();

// Изчисляване на career span
$career_span = '';
if (!empty($perfumer_career_start) && is_numeric($perfumer_career_start)) {
    $current_year = date('Y');
    $years_active = $current_year - intval($perfumer_career_start);
    $career_span = sprintf(
        /* translators: %1$d: start year, %2$d: years active */
        esc_html__('От %1$d г. (%2$d години активност)', 'parfume-reviews'),
        intval($perfumer_career_start),
        $years_active
    );
}
?>

<div class="perfumer-profile-wrap">
    <div class="container">
        
        <?php
        /**
         * Hook: parfume_reviews_perfumer_before_header
         * 
         * @hooked parfume_reviews_breadcrumbs - 10
         */
        do_action('parfume_reviews_perfumer_before_header');
        ?>
        
        <!-- Perfumer Header -->
        <header class="perfumer-header">
            <div class="perfumer-header-content">
                
                <!-- Perfumer Photo -->
                <div class="perfumer-photo-section">
                    <?php if (!empty($perfumer_image_id)) : ?>
                        <div class="perfumer-photo">
                            <?php echo wp_get_attachment_image($perfumer_image_id, 'large', false, array(
                                'class' => 'perfumer-avatar',
                                'alt' => sprintf(esc_attr__('Снимка на парфюмера %s', 'parfume-reviews'), $current_term->name)
                            )); ?>
                        </div>
                    <?php else : ?>
                        <div class="perfumer-photo-placeholder">
                            <span class="dashicons dashicons-businessman"></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Social Links -->
                    <?php if (!empty($perfumer_social_links) && is_array($perfumer_social_links)) : ?>
                        <div class="perfumer-social-links">
                            <?php foreach ($perfumer_social_links as $platform => $url) : ?>
                                <?php if (!empty($url)) : ?>
                                    <a href="<?php echo esc_url($url); ?>" class="social-link social-<?php echo esc_attr($platform); ?>" target="_blank" rel="noopener">
                                        <?php
                                        switch ($platform) {
                                            case 'website':
                                                echo '<span class="dashicons dashicons-admin-site"></span>';
                                                break;
                                            case 'instagram':
                                                echo '<span class="dashicons dashicons-instagram"></span>';
                                                break;
                                            case 'twitter':
                                                echo '<span class="dashicons dashicons-twitter"></span>';
                                                break;
                                            case 'linkedin':
                                                echo '<span class="dashicons dashicons-linkedin"></span>';
                                                break;
                                            default:
                                                echo '<span class="dashicons dashicons-admin-links"></span>';
                                        }
                                        ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Perfumer Info -->
                <div class="perfumer-info-section">
                    <h1 class="perfumer-name"><?php echo esc_html($current_term->name); ?></h1>
                    
                    <?php if (!empty($current_term->description)) : ?>
                        <p class="perfumer-tagline"><?php echo esc_html($current_term->description); ?></p>
                    <?php endif; ?>
                    
                    <!-- Basic Info Grid -->
                    <div class="perfumer-basic-info">
                        <?php if (!empty($perfumer_nationality)) : ?>
                            <div class="info-item">
                                <span class="info-label">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php esc_html_e('Националност:', 'parfume-reviews'); ?>
                                </span>
                                <span class="info-value"><?php echo esc_html($perfumer_nationality); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($perfumer_birth_year)) : ?>
                            <div class="info-item">
                                <span class="info-label">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php esc_html_e('Година на раждане:', 'parfume-reviews'); ?>
                                </span>
                                <span class="info-value"><?php echo esc_html($perfumer_birth_year); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($career_span)) : ?>
                            <div class="info-item">
                                <span class="info-label">
                                    <span class="dashicons dashicons-businessperson"></span>
                                    <?php esc_html_e('Кариера:', 'parfume-reviews'); ?>
                                </span>
                                <span class="info-value"><?php echo esc_html($career_span); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($perfumer_education)) : ?>
                            <div class="info-item">
                                <span class="info-label">
                                    <span class="dashicons dashicons-welcome-learn-more"></span>
                                    <?php esc_html_e('Образование:', 'parfume-reviews'); ?>
                                </span>
                                <span class="info-value"><?php echo esc_html($perfumer_education); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="perfumer-stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo esc_html($total_parfumes); ?></div>
                            <div class="stat-label"><?php esc_html_e('Парфюма', 'parfume-reviews'); ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo esc_html($brands_count); ?></div>
                            <div class="stat-label"><?php esc_html_e('Марки', 'parfume-reviews'); ?></div>
                        </div>
                        
                        <?php if ($avg_rating > 0) : ?>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo esc_html($avg_rating); ?></div>
                                <div class="stat-label"><?php esc_html_e('Средна оценка', 'parfume-reviews'); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($release_years)) : ?>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo esc_html(max($release_years) - min($release_years) + 1); ?></div>
                                <div class="stat-label"><?php esc_html_e('Години активност', 'parfume-reviews'); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        
        <?php
        /**
         * Hook: parfume_reviews_perfumer_after_header
         */
        do_action('parfume_reviews_perfumer_after_header');
        ?>
        
        <!-- Main Content -->
        <div class="perfumer-content-area">
            <div class="content-wrapper">
                
                <!-- Biography Section -->
                <?php if (!empty($perfumer_bio)) : ?>
                    <section class="perfumer-biography-section">
                        <h2 class="section-title">
                            <span class="title-icon"><span class="dashicons dashicons-book-alt"></span></span>
                            <span class="title-text"><?php esc_html_e('Биография', 'parfume-reviews'); ?></span>
                        </h2>
                        
                        <div class="biography-content">
                            <?php echo wp_kses_post(wpautop($perfumer_bio)); ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Signature Notes Section -->
                <?php if (!empty($perfumer_signature_notes) && is_array($perfumer_signature_notes)) : ?>
                    <section class="perfumer-signature-notes-section">
                        <h2 class="section-title">
                            <span class="title-icon"><span class="dashicons dashicons-tag"></span></span>
                            <span class="title-text"><?php esc_html_e('Характерни ноти', 'parfume-reviews'); ?></span>
                        </h2>
                        
                        <div class="signature-notes-grid">
                            <?php foreach ($perfumer_signature_notes as $note) : ?>
                                <?php if (!empty($note)) : ?>
                                    <div class="signature-note-item">
                                        <span class="note-icon">🌿</span>
                                        <span class="note-name"><?php echo esc_html($note); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Awards & Achievements Section -->
                <?php if (!empty($perfumer_awards) && is_array($perfumer_awards)) : ?>
                    <section class="perfumer-awards-section">
                        <h2 class="section-title">
                            <span class="title-icon"><span class="dashicons dashicons-awards"></span></span>
                            <span class="title-text"><?php esc_html_e('Награди и постижения', 'parfume-reviews'); ?></span>
                        </h2>
                        
                        <div class="awards-timeline">
                            <?php foreach ($perfumer_awards as $award) : ?>
                                <?php if (!empty($award['title'])) : ?>
                                    <div class="award-item">
                                        <div class="award-year">
                                            <?php echo !empty($award['year']) ? esc_html($award['year']) : '—'; ?>
                                        </div>
                                        <div class="award-content">
                                            <h3 class="award-title"><?php echo esc_html($award['title']); ?></h3>
                                            <?php if (!empty($award['description'])) : ?>
                                                <p class="award-description"><?php echo esc_html($award['description']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($award['organization'])) : ?>
                                                <span class="award-organization"><?php echo esc_html($award['organization']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Quotes Section -->
                <?php if (!empty($perfumer_quotes) && is_array($perfumer_quotes)) : ?>
                    <section class="perfumer-quotes-section">
                        <h2 class="section-title">
                            <span class="title-icon"><span class="dashicons dashicons-format-quote"></span></span>
                            <span class="title-text"><?php esc_html_e('Цитати', 'parfume-reviews'); ?></span>
                        </h2>
                        
                        <div class="quotes-carousel">
                            <?php foreach ($perfumer_quotes as $index => $quote) : ?>
                                <?php if (!empty($quote['text'])) : ?>
                                    <div class="quote-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <blockquote class="quote-text">
                                            "<?php echo esc_html($quote['text']); ?>"
                                        </blockquote>
                                        <?php if (!empty($quote['source'])) : ?>
                                            <cite class="quote-source"><?php echo esc_html($quote['source']); ?></cite>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <?php if (count($perfumer_quotes) > 1) : ?>
                                <div class="quotes-navigation">
                                    <button class="quote-nav-btn prev-quote" aria-label="<?php esc_attr_e('Предишен цитат', 'parfume-reviews'); ?>">
                                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                                    </button>
                                    <button class="quote-nav-btn next-quote" aria-label="<?php esc_attr_e('Следващ цитат', 'parfume-reviews'); ?>">
                                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Highest Rated Parfume Highlight -->
                <?php if ($highest_rated) : ?>
                    <section class="perfumer-highlight-section">
                        <h2 class="section-title">
                            <span class="title-icon"><span class="dashicons dashicons-star-filled"></span></span>
                            <span class="title-text"><?php esc_html_e('Най-високо оценен парфюм', 'parfume-reviews'); ?></span>
                        </h2>
                        
                        <div class="highlight-parfume">
                            <?php
                            $highlight_post = get_post($highest_rated);
                            $highlight_rating = get_post_meta($highest_rated, '_parfume_rating', true);
                            $highlight_brands = wp_get_post_terms($highest_rated, 'marki');
                            ?>
                            
                            <div class="highlight-image">
                                <?php if (has_post_thumbnail($highest_rated)) : ?>
                                    <a href="<?php echo get_permalink($highest_rated); ?>">
                                        <?php echo get_the_post_thumbnail($highest_rated, 'medium_large'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="highlight-content">
                                <h3 class="highlight-title">
                                    <a href="<?php echo get_permalink($highest_rated); ?>">
                                        <?php echo esc_html($highlight_post->post_title); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($highlight_brands)) : ?>
                                    <div class="highlight-brand">
                                        <?php echo esc_html($highlight_brands[0]->name); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="highlight-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $rating = floatval($highlight_rating);
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
                                    <span class="rating-number"><?php echo esc_html($highlight_rating); ?></span>
                                </div>
                                
                                <div class="highlight-excerpt">
                                    <?php echo wp_trim_words($highlight_post->post_excerpt ?: $highlight_post->post_content, 25, '...'); ?>
                                </div>
                                
                                <a href="<?php echo get_permalink($highest_rated); ?>" class="highlight-link">
                                    <?php esc_html_e('Виж детайли', 'parfume-reviews'); ?>
                                    <span class="link-arrow">→</span>
                                </a>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Parfumes Grid Section -->
                <section class="perfumer-parfumes-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="title-icon"><span class="dashicons dashicons-products"></span></span>
                            <span class="title-text">
                                <?php
                                printf(
                                    /* translators: %1$s: perfumer name, %2$d: number of parfumes */
                                    esc_html__('Парфюми от %1$s (%2$d)', 'parfume-reviews'),
                                    esc_html($current_term->name),
                                    $total_parfumes
                                );
                                ?>
                            </span>
                        </h2>
                        
                        <!-- Parfumes Filter Bar -->
                        <div class="parfumes-filter-bar">
                            <div class="filter-group">
                                <label for="parfumes-sort"><?php esc_html_e('Сортиране:', 'parfume-reviews'); ?></label>
                                <select id="parfumes-sort" name="parfumes_sort">
                                    <option value="rating"><?php esc_html_e('По оценка', 'parfume-reviews'); ?></option>
                                    <option value="date"><?php esc_html_e('По дата', 'parfume-reviews'); ?></option>
                                    <option value="title"><?php esc_html_e('По име', 'parfume-reviews'); ?></option>
                                    <option value="release_year"><?php esc_html_e('По година', 'parfume-reviews'); ?></option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="parfumes-brand"><?php esc_html_e('Марка:', 'parfume-reviews'); ?></label>
                                <select id="parfumes-brand" name="parfumes_brand">
                                    <option value=""><?php esc_html_e('Всички марки', 'parfume-reviews'); ?></option>
                                    <?php
                                    if (isset($brands) && !empty($brands)) {
                                        foreach ($brands as $brand_id => $brand_name) {
                                            echo '<option value="' . esc_attr($brand_id) . '">' . esc_html($brand_name) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
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
                    
                    <?php
                    // Reset query за да покажем парфюмите
                    $parfumes_query->rewind_posts();
                    
                    if ($parfumes_query->have_posts()) : ?>
                        <div class="parfumes-grid" data-view="grid">
                            <?php while ($parfumes_query->have_posts()) : $parfumes_query->the_post(); ?>
                                <?php
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                                $parfume_brands = wp_get_post_terms(get_the_ID(), 'marki');
                                $parfume_gender = wp_get_post_terms(get_the_ID(), 'gender');
                                ?>
                                
                                <article class="parfume-item" 
                                         data-rating="<?php echo esc_attr($rating ?: 0); ?>"
                                         data-release-year="<?php echo esc_attr($release_year ?: 0); ?>"
                                         data-brand="<?php echo !empty($parfume_brands) ? esc_attr($parfume_brands[0]->term_id) : ''; ?>">
                                    
                                    <!-- Parfume Image -->
                                    <div class="parfume-image">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium', array('loading' => 'lazy')); ?>
                                            </a>
                                        <?php else : ?>
                                            <div class="parfume-image-placeholder">
                                                <span class="dashicons dashicons-products"></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Quick Actions -->
                                        <div class="parfume-quick-actions">
                                            <button class="quick-action add-to-comparison" data-parfume-id="<?php the_ID(); ?>" title="<?php esc_attr_e('Добави за сравнение', 'parfume-reviews'); ?>">
                                                <span class="dashicons dashicons-plus-alt"></span>
                                            </button>
                                            <a href="<?php the_permalink(); ?>" class="quick-action view-details" title="<?php esc_attr_e('Виж детайли', 'parfume-reviews'); ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Parfume Content -->
                                    <div class="parfume-content">
                                        <!-- Brand -->
                                        <?php if (!empty($parfume_brands)) : ?>
                                            <div class="parfume-brand">
                                                <a href="<?php echo get_term_link($parfume_brands[0]); ?>">
                                                    <?php echo esc_html($parfume_brands[0]->name); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Title -->
                                        <h3 class="parfume-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <!-- Meta Info -->
                                        <div class="parfume-meta">
                                            <?php if (!empty($release_year)) : ?>
                                                <span class="meta-item meta-year">
                                                    <span class="dashicons dashicons-calendar-alt"></span>
                                                    <?php echo esc_html($release_year); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($parfume_gender)) : ?>
                                                <span class="meta-item meta-gender">
                                                    <span class="dashicons dashicons-admin-users"></span>
                                                    <?php echo esc_html($parfume_gender[0]->name); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Rating -->
                                        <?php if (!empty($rating)) : ?>
                                            <div class="parfume-rating">
                                                <div class="rating-stars">
                                                    <?php
                                                    $rating_float = floatval($rating);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating_float) {
                                                            echo '<span class="star filled">★</span>';
                                                        } elseif ($i - 0.5 <= $rating_float) {
                                                            echo '<span class="star half">☆</span>';
                                                        } else {
                                                            echo '<span class="star empty">☆</span>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <span class="rating-number"><?php echo esc_html($rating); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Excerpt -->
                                        <div class="parfume-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                                        </div>
                                    </div>
                                </article>
                                
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Load More Button -->
                        <?php if ($parfumes_query->max_num_pages > 1) : ?>
                            <div class="parfumes-load-more">
                                <button class="load-more-btn" data-page="1" data-max-pages="<?php echo esc_attr($parfumes_query->max_num_pages); ?>">
                                    <?php esc_html_e('Зареди още парфюми', 'parfume-reviews'); ?>
                                    <span class="loading-spinner" style="display: none;">
                                        <span class="dashicons dashicons-update"></span>
                                    </span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                    <?php else : ?>
                        <div class="no-parfumes-found">
                            <div class="no-content-icon">
                                <span class="dashicons dashicons-info"></span>
                            </div>
                            <h3><?php esc_html_e('Няма намерени парфюми', 'parfume-reviews'); ?></h3>
                            <p><?php esc_html_e('Този парфюмер все още няма създадени парфюми в нашата база данни.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php wp_reset_postdata(); ?>
                </section>
                
            </div>
            
            <!-- Sidebar -->
            <aside class="perfumer-sidebar">
                <?php
                /**
                 * Hook: parfume_reviews_perfumer_sidebar
                 */
                do_action('parfume_reviews_perfumer_sidebar');
                
                // Fallback sidebar content
                if (!has_action('parfume_reviews_perfumer_sidebar')) :
                ?>
                    <!-- Related Perfumers Widget -->
                    <div class="sidebar-widget widget-related-perfumers">
                        <h3 class="widget-title"><?php esc_html_e('Подобни парфюмери', 'parfume-reviews'); ?></h3>
                        <?php
                        // Намиране на подобни парфюмери въз основа на общи марки
                        $related_perfumers = get_terms(array(
                            'taxonomy' => 'perfumer',
                            'exclude' => array($perfumer_id),
                            'hide_empty' => true,
                            'number' => 5,
                            'orderby' => 'count',
                            'order' => 'DESC'
                        ));
                        
                        if (!empty($related_perfumers)) : ?>
                            <div class="related-perfumers-list">
                                <?php foreach ($related_perfumers as $related_perfumer) : ?>
                                    <?php
                                    $related_image_id = get_term_meta($related_perfumer->term_id, 'perfumer-image-id', true);
                                    $related_parfumes_count = $related_perfumer->count;
                                    ?>
                                    <div class="related-perfumer-item">
                                        <div class="related-perfumer-avatar">
                                            <?php if (!empty($related_image_id)) : ?>
                                                <a href="<?php echo get_term_link($related_perfumer); ?>">
                                                    <?php echo wp_get_attachment_image($related_image_id, 'thumbnail'); ?>
                                                </a>
                                            <?php else : ?>
                                                <div class="avatar-placeholder">
                                                    <span class="dashicons dashicons-businessman"></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-perfumer-info">
                                            <h4 class="related-perfumer-name">
                                                <a href="<?php echo get_term_link($related_perfumer); ?>">
                                                    <?php echo esc_html($related_perfumer->name); ?>
                                                </a>
                                            </h4>
                                            <span class="related-perfumer-count">
                                                <?php
                                                printf(
                                                    /* translators: %d: number of parfumes */
                                                    _n('%d парфюм', '%d парфюма', $related_parfumes_count, 'parfume-reviews'),
                                                    $related_parfumes_count
                                                );
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Brands Worked With Widget -->
                    <?php if (!empty($perfumer_brands_worked) && is_array($perfumer_brands_worked)) : ?>
                        <div class="sidebar-widget widget-brands-worked">
                            <h3 class="widget-title"><?php esc_html_e('Работил с марки', 'parfume-reviews'); ?></h3>
                            <div class="brands-worked-list">
                                <?php foreach ($perfumer_brands_worked as $brand_name) : ?>
                                    <?php if (!empty($brand_name)) : ?>
                                        <span class="brand-tag"><?php echo esc_html($brand_name); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Newsletter Widget -->
                    <div class="sidebar-widget widget-newsletter">
                        <h3 class="widget-title"><?php esc_html_e('Следете новите парфюми', 'parfume-reviews'); ?></h3>
                        <p><?php esc_html_e('Абонирайте се за да получавате известия за нови парфюми и ревюта.', 'parfume-reviews'); ?></p>
                        <form class="newsletter-form" action="#" method="post">
                            <input type="email" name="email" placeholder="<?php esc_attr_e('Вашият email...', 'parfume-reviews'); ?>" required>
                            <button type="submit"><?php esc_html_e('Абониране', 'parfume-reviews'); ?></button>
                        </form>
                    </div>
                    
                <?php endif; ?>
            </aside>
        </div>
        
    </div>
</div>

<?php
/**
 * Hook: parfume_reviews_perfumer_footer
 */
do_action('parfume_reviews_perfumer_footer');
?>

<!-- Enhanced JavaScript Functionality -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Quotes Carousel
    let currentQuote = 0;
    const $quotes = $('.quote-item');
    const totalQuotes = $quotes.length;
    
    function showQuote(index) {
        $quotes.removeClass('active').eq(index).addClass('active');
    }
    
    $('.next-quote').on('click', function() {
        currentQuote = (currentQuote + 1) % totalQuotes;
        showQuote(currentQuote);
    });
    
    $('.prev-quote').on('click', function() {
        currentQuote = (currentQuote - 1 + totalQuotes) % totalQuotes;
        showQuote(currentQuote);
    });
    
    // Auto-rotate quotes every 5 seconds
    if (totalQuotes > 1) {
        setInterval(function() {
            currentQuote = (currentQuote + 1) % totalQuotes;
            showQuote(currentQuote);
        }, 5000);
    }
    
    // Parfumes Filtering
    $('#parfumes-sort, #parfumes-brand').on('change', function() {
        filterParfumes();
    });
    
    function filterParfumes() {
        const sortBy = $('#parfumes-sort').val();
        const brandId = $('#parfumes-brand').val();
        const $parfumes = $('.parfume-item');
        
        // Filter by brand
        $parfumes.each(function() {
            const itemBrand = $(this).data('brand');
            if (!brandId || itemBrand == brandId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Sort visible items
        const $visibleParfumes = $('.parfume-item:visible');
        const $container = $('.parfumes-grid');
        
        $visibleParfumes.sort(function(a, b) {
            let aVal, bVal;
            
            switch (sortBy) {
                case 'rating':
                    aVal = parseFloat($(a).data('rating')) || 0;
                    bVal = parseFloat($(b).data('rating')) || 0;
                    return bVal - aVal; // Descending
                    
                case 'release_year':
                    aVal = parseInt($(a).data('release-year')) || 0;
                    bVal = parseInt($(b).data('release-year')) || 0;
                    return bVal - aVal; // Descending
                    
                case 'title':
                    aVal = $(a).find('.parfume-title a').text().toLowerCase();
                    bVal = $(b).find('.parfume-title a').text().toLowerCase();
                    return aVal.localeCompare(bVal);
                    
                default:
                    return 0;
            }
        });
        
        $container.append($visibleParfumes);
    }
    
    // View Toggle
    $('.view-toggle .view-btn').on('click', function(e) {
        e.preventDefault();
        
        const view = $(this).data('view');
        const $container = $('.parfumes-grid');
        
        $('.view-toggle .view-btn').removeClass('active');
        $(this).addClass('active');
        
        $container.attr('data-view', view);
        
        // Store preference
        localStorage.setItem('perfumer_parfumes_view', view);
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('perfumer_parfumes_view');
    if (savedView) {
        $('.view-toggle .view-btn[data-view="' + savedView + '"]').click();
    }
    
    // Comparison functionality
    $('.add-to-comparison').on('click', function(e) {
        e.preventDefault();
        const parfumeId = $(this).data('parfume-id');
        
        // This would integrate with the comparison system
        if (typeof window.ParfumeComparison !== 'undefined') {
            window.ParfumeComparison.addItem(parfumeId);
        }
        
        // Visual feedback
        $(this).addClass('added');
        setTimeout(() => {
            $(this).removeClass('added');
        }, 1000);
    });
    
    // Load More functionality
    $('.load-more-btn').on('click', function() {
        const $btn = $(this);
        const page = parseInt($btn.data('page')) + 1;
        const maxPages = parseInt($btn.data('max-pages'));
        
        $btn.find('.loading-spinner').show();
        $btn.prop('disabled', true);
        
        // AJAX call would go here
        // For now, just simulate loading
        setTimeout(function() {
            $btn.find('.loading-spinner').hide();
            $btn.prop('disabled', false);
            
            if (page >= maxPages) {
                $btn.text('<?php esc_html_e("Всички парфюми са заредени", "parfume-reviews"); ?>').prop('disabled', true);
            } else {
                $btn.data('page', page);
            }
        }, 1500);
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Parallax effect for header (subtle)
    $(window).on('scroll', function() {
        const scrolled = $(this).scrollTop();
        const rate = scrolled * -0.3;
        
        $('.perfumer-header').css({
            'transform': 'translateY(' + rate + 'px)'
        });
    });
    
});
</script>

<?php get_footer(); ?>