<?php
get_header();

$term = get_queried_object();
$term_id = $term->term_id;
$term_name = $term->name;
$term_description = term_description($term_id, 'intensity');
?>

<div class="parfume-taxonomy intensity-taxonomy">
    <header class="taxonomy-header">
        <h1 class="taxonomy-title"><?php echo $term_name; ?></h1>
        
        <?php if (!empty($term_description)): ?>
            <div class="taxonomy-description">
                <?php echo $term_description; ?>
            </div>
        <?php endif; ?>
    </header>
    
    <div class="taxonomy-content">
        <?php if (have_posts()): ?>
            <div class="intensity-statistics">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $term->count; ?></span>
                    <span class="stat-label"><?php _e('Perfumes', 'parfume-reviews'); ?></span>
                </div>
            </div>
            
            <div class="intensity-filters">
                <?php echo do_shortcode('[parfume_filters show_intensity="false"]'); ?>
            </div>
            
            <div class="parfume-grid">
                <?php while (have_posts()): the_post(); ?>
                    <article class="parfume-card">
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <div class="parfume-thumbnail">
                                    <?php the_post_thumbnail('medium'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <h2 class="parfume-title"><?php the_title(); ?></h2>
                            
                            <?php
                            $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                            if (!empty($brands)): ?>
                                <div class="parfume-brand"><?php echo implode(', ', $brands); ?></div>
                            <?php endif; ?>
                            
                            <?php
                            $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                            if (!empty($rating)): ?>
                                <div class="parfume-rating">
                                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                    <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="parfume-actions">
                            <?php echo parfume_reviews_get_comparison_button(get_the_ID()); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <?php the_posts_pagination(); ?>
        <?php else: ?>
            <p><?php _e('No perfumes found with this intensity.', 'parfume-reviews'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
?>