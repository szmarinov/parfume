<?php
/**
 * Template for blog archive (parfume_blog post type)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get settings
$settings = get_option('parfume_reviews_settings', array());
$blog_columns = isset($settings['homepage_blog_columns']) ? $settings['homepage_blog_columns'] : 3;
?>

<div class="parfume-blog-archive">
    <div class="blog-container">
        <header class="archive-header">
            <h1 class="archive-title">
                <?php _e('Парфюмен блог', 'parfume-reviews'); ?>
            </h1>
            <p class="archive-description">
                <?php _e('Последни статии, новини и съвети за парфюми', 'parfume-reviews'); ?>
            </p>
        </header>

        <?php if (have_posts()): ?>
            <div class="blog-grid columns-<?php echo esc_attr($blog_columns); ?>">
                <?php while (have_posts()): the_post(); ?>
                    <article class="blog-card" id="post-<?php the_ID(); ?>">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="blog-card-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="blog-card-content">
                            <h2 class="blog-card-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <div class="blog-card-meta">
                                <span class="blog-date">
                                    <i class="icon-calendar"></i>
                                    <?php echo get_the_date(); ?>
                                </span>
                                
                                <span class="blog-author">
                                    <i class="icon-user"></i>
                                    <?php the_author(); ?>
                                </span>

                                <?php if (has_category()): ?>
                                    <span class="blog-categories">
                                        <i class="icon-folder"></i>
                                        <?php the_category(', '); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (has_excerpt()): ?>
                                <div class="blog-card-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php else: ?>
                                <div class="blog-card-excerpt">
                                    <?php echo wp_trim_words(get_the_content(), 30, '...'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="blog-card-footer">
                                <a href="<?php the_permalink(); ?>" class="read-more-btn">
                                    <?php _e('Прочети повече', 'parfume-reviews'); ?>
                                    <i class="icon-arrow-right"></i>
                                </a>

                                <?php if (has_tag()): ?>
                                    <div class="blog-tags">
                                        <?php the_tags('<span class="tag">', '</span><span class="tag">', '</span>'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="blog-pagination">
                <?php
                echo paginate_links(array(
                    'prev_text' => '<i class="icon-arrow-left"></i> ' . __('Предишна', 'parfume-reviews'),
                    'next_text' => __('Следваща', 'parfume-reviews') . ' <i class="icon-arrow-right"></i>',
                    'type' => 'list',
                    'end_size' => 2,
                    'mid_size' => 2,
                ));
                ?>
            </div>

        <?php else: ?>
            <div class="no-posts">
                <h2><?php _e('Няма публикувани статии', 'parfume-reviews'); ?></h2>
                <p><?php _e('Все още няма публикувани статии в блога.', 'parfume-reviews'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Blog Archive Styles */
.parfume-blog-archive {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.blog-container {
    width: 100%;
}

.archive-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
}

.archive-title {
    font-size: 2.5em;
    margin-bottom: 15px;
    color: #333;
}

.archive-description {
    font-size: 1.2em;
    color: #666;
    margin: 0;
}

/* Blog Grid */
.blog-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 50px;
}

.blog-grid.columns-2 { grid-template-columns: repeat(2, 1fr); }
.blog-grid.columns-3 { grid-template-columns: repeat(3, 1fr); }
.blog-grid.columns-4 { grid-template-columns: repeat(4, 1fr); }

/* Blog Cards */
.blog-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #0073aa;
}

.blog-card-image {
    height: 200px;
    overflow: hidden;
    background: #f8f9fa;
}

.blog-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.blog-card:hover .blog-card-image img {
    transform: scale(1.1);
}

.blog-card-content {
    padding: 20px;
}

.blog-card-title {
    margin: 0 0 15px;
    font-size: 1.3em;
    line-height: 1.4;
}

.blog-card-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.blog-card-title a:hover {
    color: #0073aa;
}

.blog-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #666;
}

.blog-card-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.blog-card-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}

.blog-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.read-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.read-more-btn:hover {
    background: #005a87;
    transform: translateY(-1px);
    color: white;
}

.blog-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.blog-tags .tag {
    background: #f8f9fa;
    color: #666;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    text-decoration: none;
    transition: all 0.3s ease;
}

.blog-tags .tag:hover {
    background: #0073aa;
    color: white;
}

/* Pagination */
.blog-pagination {
    display: flex;
    justify-content: center;
    margin-top: 50px;
}

.blog-pagination .page-numbers {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 5px;
}

.blog-pagination .page-numbers li {
    margin: 0;
}

.blog-pagination .page-numbers a,
.blog-pagination .page-numbers span {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 15px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    text-decoration: none;
    color: #0073aa;
    transition: all 0.3s ease;
}

.blog-pagination .page-numbers a:hover,
.blog-pagination .page-numbers .current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

/* No Posts */
.no-posts {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.no-posts h2 {
    color: #666;
    margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .blog-grid.columns-2,
    .blog-grid.columns-3,
    .blog-grid.columns-4 {
        grid-template-columns: 1fr;
    }
    
    .archive-title {
        font-size: 2em;
    }
    
    .blog-card-meta {
        flex-direction: column;
        gap: 8px;
    }
    
    .blog-card-footer {
        flex-direction: column;
        align-items: stretch;
    }
    
    .read-more-btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .parfume-blog-archive {
        padding: 15px;
    }
    
    .archive-header {
        padding: 30px 15px;
    }
    
    .blog-card-content {
        padding: 15px;
    }
    
    .archive-title {
        font-size: 1.8em;
    }
}
</style>

<?php get_footer(); ?>