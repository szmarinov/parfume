<?php
/**
 * Front Page Template
 * 
 * Home page template for Parfume Reviews
 * 
 * @package ParfumeReviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get settings
$settings = get_option('parfume_reviews_settings', []);
$items_per_page = isset($settings['items_per_page']) ? intval($settings['items_per_page']) : 12;
?>

<div class="parfume-home-wrapper">
    
    <!-- Hero Section -->
    <section class="home-hero">
        <div class="home-hero-container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <?php _e('Открийте Вашия Перфектен Парфюм', 'parfume-reviews'); ?>
                </h1>
                <p class="hero-subtitle">
                    <?php _e('Изследвайте хиляди ревюта и намерете аромата, който ще ви определи', 'parfume-reviews'); ?>
                </p>
                <div class="hero-search">
                    <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="hidden" name="post_type" value="parfume">
                        <input type="search" 
                               class="search-input" 
                               placeholder="<?php _e('Търсене по име, марка, нотка...', 'parfume-reviews'); ?>" 
                               value="<?php echo get_search_query(); ?>" 
                               name="s">
                        <button type="submit" class="search-button">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Търси', 'parfume-reviews'); ?>
                        </button>
                    </form>
                </div>
            </div>
            <div class="hero-image">
                <img src="<?php echo PARFUME_REVIEWS_URL; ?>assets/images/hero-parfume.svg" 
                     alt="<?php _e('Парфюми', 'parfume-reviews'); ?>">
            </div>
        </div>
    </section>
    
    <!-- Featured Categories -->
    <section class="home-categories">
        <div class="home-container">
            <h2 class="section-title">
                <?php _e('Разгледайте по Категории', 'parfume-reviews'); ?>
            </h2>
            
            <div class="categories-grid">
                <?php
                // Get popular taxonomies
                $taxonomies = [
                    'gender' => [
                        'icon' => 'groups',
                        'color' => '#8b7355'
                    ],
                    'marki' => [
                        'icon' => 'star-filled',
                        'color' => '#d4a574'
                    ],
                    'season' => [
                        'icon' => 'palmtree',
                        'color' => '#c9a882'
                    ],
                    'intensity' => [
                        'icon' => 'performance',
                        'color' => '#8b7355'
                    ]
                ];
                
                foreach ($taxonomies as $taxonomy => $data) {
                    $terms = get_terms([
                        'taxonomy' => $taxonomy,
                        'hide_empty' => true,
                        'number' => 1
                    ]);
                    
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $taxonomy_obj = get_taxonomy($taxonomy);
                        $term_count = wp_count_terms($taxonomy, ['hide_empty' => true]);
                        ?>
                        <a href="<?php echo get_post_type_archive_link('parfume'); ?>" 
                           class="category-card" 
                           style="--card-color: <?php echo $data['color']; ?>">
                            <span class="category-icon dashicons dashicons-<?php echo $data['icon']; ?>"></span>
                            <h3 class="category-name"><?php echo esc_html($taxonomy_obj->labels->name); ?></h3>
                            <p class="category-count"><?php printf(__('%d опции', 'parfume-reviews'), $term_count); ?></p>
                        </a>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </section>
    
    <!-- Top Rated Parfumes -->
    <section class="home-top-rated">
        <div class="home-container">
            <div class="section-header">
                <h2 class="section-title">
                    <?php _e('Най-Високо Оценени', 'parfume-reviews'); ?>
                </h2>
                <a href="<?php echo get_post_type_archive_link('parfume'); ?>?orderby=rating" 
                   class="section-link">
                    <?php _e('Виж Всички', 'parfume-reviews'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
            
            <div class="parfumes-showcase">
                <?php
                $top_rated = new WP_Query([
                    'post_type' => 'parfume',
                    'posts_per_page' => 6,
                    'meta_key' => '_parfume_rating',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                    'meta_query' => [
                        [
                            'key' => '_parfume_rating',
                            'value' => 0,
                            'compare' => '>',
                            'type' => 'NUMERIC'
                        ]
                    ]
                ]);
                
                if ($top_rated->have_posts()) :
                    while ($top_rated->have_posts()) : $top_rated->the_post();
                        get_template_part('parfume-reviews/parts/parfume', 'card');
                    endwhile;
                    wp_reset_postdata();
                else :
                    echo '<p class="no-results">' . __('Все още няма парфюми с рейтинг.', 'parfume-reviews') . '</p>';
                endif;
                ?>
            </div>
        </div>
    </section>
    
    <!-- New Releases -->
    <section class="home-new-releases">
        <div class="home-container">
            <div class="section-header">
                <h2 class="section-title">
                    <?php _e('Нови Парфюми', 'parfume-reviews'); ?>
                </h2>
                <a href="<?php echo get_post_type_archive_link('parfume'); ?>?orderby=date" 
                   class="section-link">
                    <?php _e('Виж Всички', 'parfume-reviews'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
            
            <div class="parfumes-showcase">
                <?php
                $new_releases = new WP_Query([
                    'post_type' => 'parfume',
                    'posts_per_page' => 6,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                if ($new_releases->have_posts()) :
                    while ($new_releases->have_posts()) : $new_releases->the_post();
                        get_template_part('parfume-reviews/parts/parfume', 'card');
                    endwhile;
                    wp_reset_postdata();
                else :
                    echo '<p class="no-results">' . __('Все още няма добавени парфюми.', 'parfume-reviews') . '</p>';
                endif;
                ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Brands -->
    <section class="home-brands">
        <div class="home-container">
            <h2 class="section-title">
                <?php _e('Популярни Марки', 'parfume-reviews'); ?>
            </h2>
            
            <div class="brands-carousel">
                <?php
                $brands = get_terms([
                    'taxonomy' => 'marki',
                    'hide_empty' => true,
                    'number' => 12,
                    'orderby' => 'count',
                    'order' => 'DESC'
                ]);
                
                if (!empty($brands) && !is_wp_error($brands)) :
                    foreach ($brands as $brand) :
                        ?>
                        <a href="<?php echo get_term_link($brand); ?>" class="brand-item">
                            <div class="brand-logo">
                                <?php
                                $brand_image = get_term_meta($brand->term_id, 'brand_logo', true);
                                if ($brand_image) :
                                    echo wp_get_attachment_image($brand_image, 'thumbnail');
                                else :
                                    echo '<span class="brand-letter">' . mb_substr($brand->name, 0, 1) . '</span>';
                                endif;
                                ?>
                            </div>
                            <span class="brand-name"><?php echo esc_html($brand->name); ?></span>
                            <span class="brand-count"><?php printf(__('%d парфюма', 'parfume-reviews'), $brand->count); ?></span>
                        </a>
                        <?php
                    endforeach;
                endif;
                ?>
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <section class="home-cta">
        <div class="home-container">
            <div class="cta-card">
                <div class="cta-content">
                    <h2 class="cta-title">
                        <?php _e('Започнете Вашето Парфюмно Пътешествие', 'parfume-reviews'); ?>
                    </h2>
                    <p class="cta-text">
                        <?php _e('Разгледайте нашата пълна колекция от парфюми и намерете аромата, който ви представя най-добре.', 'parfume-reviews'); ?>
                    </p>
                    <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="cta-button">
                        <?php _e('Разгледай Всички Парфюми', 'parfume-reviews'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
                <div class="cta-image">
                    <img src="<?php echo PARFUME_REVIEWS_URL; ?>assets/images/cta-bottles.svg" 
                         alt="<?php _e('Парфюми', 'parfume-reviews'); ?>">
                </div>
            </div>
        </div>
    </section>
    
</div>

<?php
get_footer();