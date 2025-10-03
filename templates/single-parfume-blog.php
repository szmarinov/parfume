<?php
/**
 * Template for single blog posts (parfume_blog post type)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()): the_post();
?>

<article class="parfume-blog-single">
    <div class="blog-container">
        <header class="blog-header">
            <?php if (has_post_thumbnail()): ?>
                <div class="blog-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="blog-header-content">
                <h1 class="blog-title"><?php the_title(); ?></h1>
                
                <div class="blog-meta">
                    <div class="blog-meta-item">
                        <span class="meta-label"><?php _e('Published:', 'parfume-reviews'); ?></span>
                        <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                    </div>
                    
                    <div class="blog-meta-item">
                        <span class="meta-label"><?php _e('Author:', 'parfume-reviews'); ?></span>
                        <span class="meta-value"><?php the_author(); ?></span>
                    </div>
                    
                    <?php if (has_category()): ?>
                        <div class="blog-meta-item">
                            <span class="meta-label"><?php _e('Categories:', 'parfume-reviews'); ?></span>
                            <span class="meta-value"><?php the_category(', '); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (has_tag()): ?>
                        <div class="blog-meta-item">
                            <span class="meta-label"><?php _e('Tags:', 'parfume-reviews'); ?></span>
                            <span class="meta-value"><?php the_tags('', ', '); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (has_excerpt()): ?>
                    <div class="blog-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="blog-content">
            <?php the_content(); ?>
            
            <?php
            wp_link_pages(array(
                'before' => '<div class="page-links">' . __('Pages:', 'parfume-reviews'),
                'after' => '</div>',
            ));
            ?>
        </div>
        
        <footer class="blog-footer">
            <?php if (has_tag()): ?>
                <div class="blog-tags">
                    <h3><?php _e('Tags', 'parfume-reviews'); ?></h3>
                    <div class="tags-list">
                        <?php the_tags('<span class="tag">', '</span><span class="tag">', '</span>'); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="blog-navigation">
                <div class="nav-previous">
                    <?php 
                    $prev_post = get_previous_post();
                    if ($prev_post): 
                    ?>
                        <a href="<?php echo get_permalink($prev_post->ID); ?>" class="nav-link prev">
                            <span class="nav-label"><?php _e('Previous Article', 'parfume-reviews'); ?></span>
                            <span class="nav-title"><?php echo get_the_title($prev_post->ID); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="nav-next">
                    <?php 
                    $next_post = get_next_post();
                    if ($next_post): 
                    ?>
                        <a href="<?php echo get_permalink($next_post->ID); ?>" class="nav-link next">
                            <span class="nav-label"><?php _e('Next Article', 'parfume-reviews'); ?></span>
                            <span class="nav-title"><?php echo get_the_title($next_post->ID); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </footer>
        
        <!-- Related blog posts -->
        <div class="related-posts">
            <?php
            $related_posts = new WP_Query(array(
                'post_type' => 'parfume_blog',
                'posts_per_page' => 3,
                'post__not_in' => array(get_the_ID()),
                'orderby' => 'rand',
            ));
            
            if ($related_posts->have_posts()):
            ?>
                <h3><?php _e('You Might Also Like', 'parfume-reviews'); ?></h3>
                
                <div class="related-posts-grid">
                    <?php while ($related_posts->have_posts()): $related_posts->the_post(); ?>
                        <article class="related-post">
                            <a href="<?php the_permalink(); ?>" class="related-post-link">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="related-post-thumbnail">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="related-post-content">
                                    <h4 class="related-post-title"><?php the_title(); ?></h4>
                                    <div class="related-post-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                    </div>
                                    <time class="related-post-date"><?php echo get_the_date(); ?></time>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>
        
        <!-- Comments -->
        <?php if (comments_open() || get_comments_number()): ?>
            <div class="blog-comments">
                <?php comments_template(); ?>
            </div>
        <?php endif; ?>
    </div>
</article>

<style>
.blog-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
}

.blog-header {
    margin-bottom: 40px;
}

.blog-featured-image {
    margin-bottom: 30px;
}

.blog-featured-image img {
    width: 100%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.blog-title {
    font-size: 2.5em;
    line-height: 1.2;
    margin-bottom: 20px;
    color: #333;
}

.blog-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.blog-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.meta-label {
    font-weight: bold;
    color: #666;
}

.meta-value {
    color: #333;
}

.meta-value a {
    color: #0073aa;
    text-decoration: none;
}

.meta-value a:hover {
    text-decoration: underline;
}

.blog-excerpt {
    font-size: 1.2em;
    line-height: 1.6;
    color: #666;
    font-style: italic;
    padding: 20px;
    background: #e8f4fd;
    border-radius: 8px;
    margin-top: 20px;
}

.blog-content {
    line-height: 1.8;
    font-size: 1.1em;
    margin-bottom: 40px;
}

.blog-content h2,
.blog-content h3,
.blog-content h4 {
    margin-top: 2em;
    margin-bottom: 1em;
    color: #333;
}

.blog-content h2 {
    font-size: 1.8em;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.blog-content h3 {
    font-size: 1.4em;
}

.blog-content h4 {
    font-size: 1.2em;
}

.blog-content p {
    margin-bottom: 1.5em;
}

.blog-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.page-links {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

.blog-footer {
    border-top: 2px solid #dee2e6;
    padding-top: 30px;
    margin-top: 40px;
}

.blog-tags {
    margin-bottom: 30px;
}

.blog-tags h3 {
    margin-bottom: 15px;
    color: #333;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tag {
    background: #0073aa;
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.9em;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.tag:hover {
    background: #005a87;
    color: white;
}

.blog-navigation {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 40px;
}

.nav-link {
    display: block;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.nav-link:hover {
    background: #e8f4fd;
    border-color: #0073aa;
    transform: translateY(-2px);
}

.nav-link.prev {
    text-align: left;
}

.nav-link.next {
    text-align: right;
}

.nav-label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.nav-title {
    display: block;
    font-weight: bold;
    color: #333;
}

.related-posts {
    margin-top: 50px;
    padding-top: 30px;
    border-top: 2px solid #dee2e6;
}

.related-posts h3 {
    margin-bottom: 25px;
    color: #333;
    font-size: 1.5em;
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.related-post {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.related-post:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.related-post-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.related-post-thumbnail {
    height: 150px;
    overflow: hidden;
}

.related-post-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-post-content {
    padding: 15px;
}

.related-post-title {
    margin: 0 0 10px;
    font-size: 1.1em;
    line-height: 1.3;
    color: #333;
}

.related-post-excerpt {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin-bottom: 10px;
}

.related-post-date {
    color: #999;
    font-size: 0.8em;
}

.blog-comments {
    margin-top: 50px;
    padding-top: 30px;
    border-top: 2px solid #dee2e6;
}

/* Responsive */
@media (max-width: 768px) {
    .blog-container {
        padding: 20px 15px;
    }
    
    .blog-title {
        font-size: 2em;
    }
    
    .blog-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .blog-navigation {
        grid-template-columns: 1fr;
    }
    
    .related-posts-grid {
        grid-template-columns: 1fr;
    }
    
    .blog-content {
        font-size: 1em;
    }
}

@media (max-width: 480px) {
    .blog-title {
        font-size: 1.8em;
    }
    
    .blog-content h2 {
        font-size: 1.5em;
    }
    
    .blog-content h3 {
        font-size: 1.3em;
    }
}
</style>

<?php
endwhile;

get_footer();
?>