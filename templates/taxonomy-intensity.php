<?php
/**
 * Universal template for all parfume taxonomies
 * Copy this file to templates/ folder with appropriate names:
 * - taxonomy-season.php
 * - taxonomy-intensity.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Проверяваме дали това е taxonomy archive или individual term
global $wp_query;

$is_taxonomy_archive = false;
$current_term = null;
$taxonomy_name = '';

// Проверяваме за parfume_taxonomy_archive (от rewrite rules)
if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
    $is_taxonomy_archive = true;
    $taxonomy_name = $wp_query->query_vars['parfume_taxonomy_archive'];
} else {
    // Стандартен individual term
    $current_term = get_queried_object();
    if ($current_term && is_object($current_term) && isset($current_term->taxonomy)) {
        $taxonomy_name = $current_term->taxonomy;
    }
}

// Получаваме taxonomy object
$taxonomy_obj = null;
if (!empty($taxonomy_name)) {
    $taxonomy_obj = get_taxonomy($taxonomy_name);
}

// Ако няма валиден taxonomy object, показваме съобщение за грешка
if (!$taxonomy_obj || !is_object($taxonomy_obj)) {
    ?>
    <div class="parfume-archive-error">
        <h1><?php _e('Невалидна таксономия', 'parfume-reviews'); ?></h1>
        <p><?php _e('Тази таксономия не съществува или не е правилно конфигурирана.', 'parfume-reviews'); ?></p>
    </div>
    <?php
    get_footer();
    return;
}
?>

<?php if ($is_taxonomy_archive): ?>
    <!-- TAXONOMY ARCHIVE PAGE - показва всички terms от таксономията -->
    <div class="container">
        <div class="parfume-taxonomy-archive <?php echo esc_attr($taxonomy_name); ?>-archive">
            <div class="archive-header">
                <h1 class="archive-title">
                    <?php 
                    echo esc_html(
                        isset($taxonomy_obj->labels->name) 
                        ? $taxonomy_obj->labels->name 
                        : __('Всички категории', 'parfume-reviews')
                    ); 
                    ?>
                </h1>
                
                <?php if (!empty($taxonomy_obj->description)): ?>
                    <div class="archive-description">
                        <?php echo wpautop(esc_html($taxonomy_obj->description)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="archive-meta">
                    <span class="taxonomy-info">
                        <?php 
                        printf(
                            __('Преглед на всички %s в нашия каталог', 'parfume-reviews'), 
                            strtolower($taxonomy_obj->labels->name ?? '')
                        ); 
                        ?>
                    </span>
                </div>
            </div>

            <div class="taxonomy-terms-grid">
                <?php
                // Получаваме всички terms от тази таксономия
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy_name,
                    'hide_empty' => true,
                    'orderby' => 'count',
                    'order' => 'DESC'
                ));

                if (!empty($terms) && !is_wp_error($terms)):
                    foreach ($terms as $term):
                        $term_link = get_term_link($term);
                        if (is_wp_error($term_link)) continue;
                        
                        // Получаваме настройките
                        $settings = get_option('parfume_reviews_settings', array());
                        $featured_perfumes_per_intensity = isset($settings['featured_perfumes_per_intensity']) ? intval($settings['featured_perfumes_per_intensity']) : 3;
                        
                        // Получаваме парфюми за този term
                        $perfumes_query = new WP_Query(array(
                            'post_type' => 'parfume',
                            'posts_per_page' => $featured_perfumes_per_intensity,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => $taxonomy_name,
                                    'field' => 'term_id',
                                    'terms' => $term->term_id,
                                ),
                            ),
                            'orderby' => 'rand',
                            'no_found_rows' => true,
                        ));
                        ?>
                        <div class="term-item">
                            <div class="term-header">
                                <h3 class="term-name">
                                    <a href="<?php echo esc_url($term_link); ?>"><?php echo esc_html($term->name); ?></a>
                                </h3>
                                <span class="term-count">
                                    <?php printf(_n('%d парфюм', '%d парфюма', $term->count, 'parfume-reviews'), $term->count); ?>
                                </span>
                                
                                <?php 
                                // Валидираме и почистваме описанието
                                $description = trim($term->description);
                                // Проверяваме дали описанието не е само повтарящи се символи
                                if (!empty($description) && !preg_match('/^(.)\1{10,}/', $description)): 
                                ?>
                                    <p class="term-description"><?php echo wp_trim_words(esc_html($description), 15); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($perfumes_query->have_posts()): ?>
                                <div class="term-perfumes">
                                    <?php while ($perfumes_query->have_posts()): $perfumes_query->the_post(); ?>
                                        <div class="mini-perfume-card">
                                            <a href="<?php the_permalink(); ?>" class="perfume-link">
                                                <?php if (has_post_thumbnail()): ?>
                                                    <div class="mini-perfume-thumb">
                                                        <?php the_post_thumbnail('thumbnail'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mini-perfume-title"><?php echo wp_trim_words(get_the_title(), 3); ?></div>
                                            </a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php wp_reset_postdata(); ?>
                            <?php endif; ?>
                            
                            <div class="term-actions">
                                <a href="<?php echo esc_url($term_link); ?>" class="view-all-btn">
                                    <?php printf(__('Виж всички %d парфюма', 'parfume-reviews'), $term->count); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-terms">
                        <?php 
                        printf(
                            __('Няма намерени %s.', 'parfume-reviews'), 
                            strtolower($taxonomy_obj->labels->name ?? '')
                        ); 
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- INDIVIDUAL TERM PAGE - показва парфюми от конкретен term -->
    <div class="container">
        <div class="parfume-archive <?php echo esc_attr($taxonomy_name); ?>-archive">
            <div class="archive-header">
                <h1 class="archive-title"><?php echo esc_html($current_term->name ?? ''); ?></h1>
                <?php if ($current_term && !empty($current_term->description)): ?>
                    <div class="archive-description">
                        <?php echo wpautop(esc_html($current_term->description)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="archive-meta">
                    <span class="taxonomy-label">
                        <?php 
                        echo esc_html(
                            isset($taxonomy_obj->labels->singular_name) 
                            ? $taxonomy_obj->labels->singular_name 
                            : __('Категория', 'parfume-reviews')
                        ); 
                        ?>:
                    </span>
                    <span class="perfume-count">
                        <?php 
                        $count = ($current_term && isset($current_term->count)) ? intval($current_term->count) : 0;
                        printf(_n('%d парфюм', '%d парфюма', $count, 'parfume-reviews'), $count); 
                        ?>
                    </span>
                </div>
            </div>

            <div class="archive-content">
                <div class="archive-sidebar">
                    <?php 
                    // Hide current taxonomy filter
                    $hide_filter = 'show_' . str_replace('_', '_', $taxonomy_name) . '="false"';
                    if ($taxonomy_name === 'marki') {
                        $hide_filter = 'show_brand="false"';
                    }
                    echo do_shortcode('[parfume_filters ' . $hide_filter . ']'); 
                    ?>
                </div>

            <div class="archive-main">
                <?php if (have_posts()): ?>
                    <div class="parfume-grid">
                        <?php while (have_posts()): the_post(); ?>
                            <div class="parfume-card">
                                <div class="parfume-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('medium'); ?>
                                        <?php else: ?>
                                            <div class="no-image">
                                                <span><?php _e('No Image', 'parfume-reviews'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>

                                <div class="parfume-content">
                                   <h3 class="parfume-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>

                                    <?php 
                                    $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                    if (!empty($brands) && !is_wp_error($brands)): 
                                    ?>
                                        <div class="parfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                    <?php endif; ?>

                                    <?php 
                                    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                    if (!empty($rating) && is_numeric($rating)): 
                                    ?>
                                        <div class="parfume-rating">
                                            <div class="rating-stars">
                                                <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                            </div>
                                            <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php
                    // Pagination
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => __('&laquo; Previous', 'parfume-reviews'),
                        'next_text' => __('Next &raquo;', 'parfume-reviews'),
                    ));
                    ?>

                <?php else: ?>
                    <p class="no-perfumes">
                        <?php 
                        $taxonomy_name_display = isset($taxonomy_obj->labels->singular_name) 
                            ? strtolower($taxonomy_obj->labels->singular_name) 
                            : __('категория', 'parfume-reviews');
                        printf(
                            __('No perfumes found for this %s.', 'parfume-reviews'), 
                            $taxonomy_name_display
                        ); 
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.parfume-taxonomy-archive {
    margin: 40px 0;
}

.archive-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
}

.archive-title {
    font-size: 2.5em;
    color: #2c3e50;
    margin: 0 0 15px;
    font-weight: 700;
}

.archive-description {
    font-size: 1.1em;
    color: #6c757d;
    margin: 15px 0;
    line-height: 1.6;
}

.archive-meta {
    margin-top: 20px;
}

.taxonomy-info {
    font-size: 1em;
    color: #495057;
    font-style: italic;
}

.taxonomy-terms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.term-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
}

.term-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
    border-color: #007cba;
}

.term-header {
    padding: 20px;
    border-bottom: 1px solid #f8f9fa;
}

.term-name {
    margin: 0 0 10px;
    font-size: 1.3em;
    font-weight: 600;
}

.term-name a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
}

.term-name a:hover {
    color: #007cba;
}

.term-count {
    display: inline-block;
    background: #007cba;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
    margin-bottom: 10px;
}

.term-description {
    color: #6c757d;
    font-size: 0.9em;
    line-height: 1.5;
    margin: 10px 0 0;
    word-break: break-word;
    overflow-wrap: break-word;
}

.term-perfumes {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 15px 20px;
    background: #f8f9fa;
}

.mini-perfume-card {
    flex: 1;
    min-width: 80px;
    max-width: 100px;
    text-align: center;
}

.perfume-link {
    display: block;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease;
}

.perfume-link:hover {
    transform: scale(1.05);
}

.mini-perfume-thumb {
    margin-bottom: 5px;
    border-radius: 6px;
    overflow: hidden;
}

.mini-perfume-thumb img {
    width: 100%;
    height: 60px;
    object-fit: cover;
    display: block;
}

.mini-perfume-title {
    font-size: 0.75em;
    color: #495057;
    line-height: 1.3;
    height: 2.6em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.term-actions {
    padding: 15px 20px;
    text-align: center;
    border-top: 1px solid #f8f9fa;
}

.view-all-btn {
    display: inline-block;
    padding: 8px 20px;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9em;
    font-weight: 500;
    transition: all 0.3s ease;
}

.view-all-btn:hover {
    background: #005a87;
    transform: translateY(-1px);
}

.archive-content {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 30px;
    margin-top: 30px;
}

.archive-sidebar {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    height: fit-content;
}

.archive-main {
    min-height: 400px;
}

.parfume-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.parfume-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.parfume-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.no-terms, .parfume-archive-error, .no-perfumes {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
    font-size: 1.1em;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 40px 0;
}

.parfume-archive-error h1 {
    color: #dc3545;
    margin-bottom: 15px;
}

/* Responsive Design */
@media (max-width: 992px) {
    .archive-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .archive-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .taxonomy-terms-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .archive-title {
        font-size: 2em;
    }
    
    .term-perfumes {
        justify-content: center;
    }
    
    .mini-perfume-card {
        min-width: 70px;
        max-width: 85px;
    }
    
    .parfume-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
}

@media (max-width: 480px) {
    .archive-header {
        padding: 20px;
    }
    
    .archive-title {
        font-size: 1.8em;
    }
    
    .term-item {
        margin: 0 -5px;
    }
}
</style>

<?php get_footer(); ?>