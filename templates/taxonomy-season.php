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

$current_term = get_queried_object();
$taxonomy_obj = get_taxonomy($current_term->taxonomy);
?>

<div class="parfume-archive <?php echo esc_attr($current_term->taxonomy); ?>-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php echo esc_html($current_term->name); ?></h1>
        <?php if ($current_term->description): ?>
            <div class="archive-description">
                <?php echo wpautop(esc_html($current_term->description)); ?>
            </div>
        <?php endif; ?>
        
        <div class="archive-meta">
            <span class="taxonomy-label"><?php echo esc_html($taxonomy_obj->labels->singular_name); ?>:</span>
            <span class="perfume-count">
                <?php printf(_n('%d парфюм', '%d парфюма', $current_term->count, 'parfume-reviews'), $current_term->count); ?>
            </span>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-sidebar">
            <?php 
            // Hide current taxonomy filter
            $hide_filter = 'show_' . str_replace('_', '_', $current_term->taxonomy) . '="false"';
            if ($current_term->taxonomy === 'marki') {
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

                                <div class="parfume-actions">
                                    <?php echo parfume_reviews_get_comparison_button(get_the_ID()); ?>
                                    <?php echo parfume_reviews_get_collections_dropdown(get_the_ID()); ?>
                                </div>
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
                    printf(
                        __('No perfumes found for this %s.', 'parfume-reviews'), 
                        strtolower($taxonomy_obj->labels->singular_name)
                    ); 
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>