<?php
/**
 * Template for Parfume archive page
 * 📁 Файл: templates/archive-parfume.php
 * ПОПРАВЕНИ: WP_Error проверки и валидация на обекти
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive archive-parfume">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('All Perfumes', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Discover our complete collection of fragrances from top brands and niche perfumers.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="archive-meta">
            <?php
            global $wp_query;
            $total_perfumes = $wp_query->found_posts;
            ?>
            <span class="perfume-count">
                <?php printf(_n('%d парфюм', '%d парфюма', $total_perfumes, 'parfume-reviews'), $total_perfumes); ?>
            </span>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-sidebar">
            <div class="popular-brands-widget">
                <h3><?php _e('Popular Brands', 'parfume-reviews'); ?></h3>
                <div class="brands-list">
                    <?php
                    // Get popular brands with proper error handling
                    $popular_brands = get_terms(array(
                        'taxonomy' => 'marki',
                        'orderby' => 'count',
                        'order' => 'DESC',
                        'number' => 10,
                        'hide_empty' => true,
                    ));

                    if (!is_wp_error($popular_brands) && !empty($popular_brands)):
                        foreach ($popular_brands as $brand):
                            // ПОПРАВЕНО: Проверяваме че $brand е валиден object
                            if (!is_object($brand) || !isset($brand->name) || !isset($brand->slug) || !isset($brand->count)) {
                                continue;
                            }
                            
                            // ПОПРАВЕНО: Проверяваме get_term_link за грешки
                            $brand_link = get_term_link($brand);
                            if (is_wp_error($brand_link)) {
                                continue;
                            }
                            ?>
                            <a href="<?php echo esc_url($brand_link); ?>" class="brand-link">
                                <?php echo esc_html($brand->name); ?>
                                <span class="count">(<?php echo intval($brand->count); ?>)</span>
                            </a>
                        <?php endforeach;
                    else: ?>
                        <p><?php _e('No brands found.', 'parfume-reviews'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php echo do_shortcode('[parfume_filters]'); ?>
        </div>

        <div class="archive-main">
            <?php if (have_posts()): ?>
                <div class="parfume-grid">
                    <?php while (have_posts()): the_post(); ?>
                        <article class="parfume-card">
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

                            <div class="parfume-card-content">
                                <h3 class="parfume-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <?php 
                                // ПОПРАВЕНО: Безопасно извличане на brand с проверки за грешки
                                $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                if (!is_wp_error($brands) && !empty($brands) && is_array($brands)): 
                                ?>
                                    <div class="parfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                <?php endif; ?>

                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <span class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </span>
                                        <span class="rating-value"><?php echo esc_html($rating); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (has_excerpt()): ?>
                                    <div class="parfume-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="parfume-meta">
                                    <?php
                                    // ПОПРАВЕНО: Get gender with error handling
                                    $genders = wp_get_post_terms(get_the_ID(), 'gender', array('fields' => 'names'));
                                    if (!is_wp_error($genders) && !empty($genders) && is_array($genders)): 
                                    ?>
                                        <span class="meta-item gender">
                                            <strong><?php _e('Gender:', 'parfume-reviews'); ?></strong>
                                            <?php echo esc_html($genders[0]); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php
                                    // Get release year
                                    $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                                    if ($release_year && is_numeric($release_year)): 
                                    ?>
                                        <span class="meta-item year">
                                            <strong><?php _e('Year:', 'parfume-reviews'); ?></strong>
                                            <?php echo esc_html($release_year); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination(array(
                    'prev_text' => __('Previous', 'parfume-reviews'),
                    'next_text' => __('Next', 'parfume-reviews'),
                ));
                ?>

            <?php else: ?>
                <div class="no-perfumes-found">
                    <h3><?php _e('No perfumes found.', 'parfume-reviews'); ?></h3>
                    <p><?php _e('Try adjusting your filters or search terms.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>