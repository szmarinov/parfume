<?php
/**
 * Template for Parfume archive page
 * 📁 Файл: templates/archive-parfume.php
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
                            // Проверяваме че $brand е валиден object
                            if (!is_object($brand) || !isset($brand->name) || !isset($brand->slug)) {
                                continue;
                            }
                            ?>
                            <a href="<?php echo get_term_link($brand); ?>" class="brand-link">
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
                                // Get brand with proper error handling
                                $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                if (!is_wp_error($brands) && !empty($brands)): 
                                ?>
                                    <div class="parfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                <?php endif; ?>

                                <?php 
                                // Get rating
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if ($rating): 
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
                                    // Get gender with error handling
                                    $genders = wp_get_post_terms(get_the_ID(), 'gender', array('fields' => 'names'));
                                    if (!is_wp_error($genders) && !empty($genders)): 
                                    ?>
                                        <span class="meta-item gender">
                                            <strong><?php _e('Gender:', 'parfume-reviews'); ?></strong>
                                            <?php echo esc_html($genders[0]); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php
                                    // Get release year
                                    $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                                    if ($release_year): 
                                    ?>
                                        <span class="meta-item year">
                                            <strong><?php _e('Year:', 'parfume-reviews'); ?></strong>
                                            <?php echo esc_html($release_year); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="parfume-actions">
                                    <a href="<?php the_permalink(); ?>" class="read-more-btn">
                                        <?php _e('View Details', 'parfume-reviews'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => __('← Previous', 'parfume-reviews'),
                    'next_text' => __('Next →', 'parfume-reviews'),
                ));
                ?>
            <?php else: ?>
                <div class="no-parfumes">
                    <h2><?php _e('No perfumes found.', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Try adjusting your filters or search criteria.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.archive-parfume {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.archive-header {
    text-align: center;
    margin-bottom: 3rem;
}

.archive-title {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.archive-description {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.archive-meta .perfume-count {
    background: #f8f9fa;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    color: #495057;
    font-weight: 500;
}

.archive-content {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
}

.archive-sidebar {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    height: fit-content;
}

.popular-brands-widget h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.brands-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.brand-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: white;
    border-radius: 4px;
    text-decoration: none;
    color: #495057;
    transition: background 0.2s ease;
}

.brand-link:hover {
    background: #007cba;
    color: white;
}

.brand-link .count {
    font-size: 0.875rem;
    opacity: 0.7;
}

.parfume-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.parfume-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.parfume-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.parfume-thumbnail {
    height: 200px;
    overflow: hidden;
}

.parfume-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #6c757d;
}

.parfume-content {
    padding: 1.5rem;
}

.parfume-title a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.25rem;
    font-weight: 600;
}

.parfume-title a:hover {
    color: #007cba;
}

.parfume-brand {
    color: #6c757d;
    margin: 0.5rem 0;
    font-weight: 500;
}

.parfume-rating {
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stars .star {
    color: #ddd;
    font-size: 1.2rem;
}

.stars .star.filled {
    color: #ffc107;
}

.rating-value {
    font-weight: 600;
    color: #495057;
}

.parfume-excerpt {
    color: #6c757d;
    margin: 1rem 0;
    line-height: 1.5;
}

.parfume-meta {
    margin: 1rem 0;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.meta-item {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.parfume-actions {
    text-align: center;
    margin-top: 1rem;
}

.read-more-btn {
    display: inline-block;
    background: #007cba;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.2s ease;
}

.read-more-btn:hover {
    background: #005a87;
    color: white;
}

.no-parfumes {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .archive-content {
        grid-template-columns: 1fr;
    }
    
    .parfume-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>