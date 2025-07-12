<?php
get_header();

$settings = get_option('parfume_reviews_settings');
$archive_title = post_type_archive_title('', false);
?>

<div class="parfume-archive">
    <header class="archive-header">
        <h1 class="archive-title"><?php echo $archive_title; ?></h1>
        
        <?php if (!empty($settings['archive_description'])): ?>
            <div class="archive-description">
                <?php echo wpautop($settings['archive_description']); ?>
            </div>
        <?php endif; ?>
    </header>
    
    <div class="archive-content">
        <aside class="archive-sidebar">
            <?php echo do_shortcode('[parfume_filters]'); ?>
            
            <div class="popular-brands">
                <h3><?php _e('Popular Brands', 'parfume-reviews'); ?></h3>
                <?php
                $brands = get_terms(array(
                    'taxonomy' => 'marki',
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'number' => 10,
                    'hide_empty' => true,
                ));
                
                if (!empty($brands)): ?>
                    <ul>
                        <?php foreach ($brands as $brand): ?>
                            <li>
                                <a href="<?php echo get_term_link($brand); ?>">
                                    <?php echo $brand->name; ?>
                                    <span class="count">(<?php echo $brand->count; ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>
        
        <main class="archive-main">
            <?php if (have_posts()): ?>
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
                <p><?php _e('No perfumes found.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
get_footer();
?>