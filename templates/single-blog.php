<?php
/**
 * Single Blog Template
 * Uses theme's template as fallback with custom elements
 */

if (!defined('ABSPATH')) {
    exit;
}

// Try to use theme's single.php template
get_header();

// Add custom class to body
add_filter('body_class', function($classes) {
    $classes[] = 'single-parfume-blog';
    return $classes;
});
?>

<div class="blog-post-wrapper">
    <?php
    while (have_posts()) :
        the_post();
    ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('blog-post'); ?>>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="blog-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <header class="blog-header">
                <h1 class="blog-title"><?php the_title(); ?></h1>
                
                <div class="blog-meta">
                    <span class="blog-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M8 7V3M16 7V3M7 11H17M5 21H19C19.5304 21 20.0391 20.7893 20.4142 20.4142C20.7893 20.0391 21 19.5304 21 19V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V19C3 19.5304 3.21071 20.0391 3.58579 20.4142C3.96086 20.7893 4.46957 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo get_the_date(); ?>
                    </span>
                    
                    <span class="blog-author">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo get_the_author(); ?>
                    </span>
                    
                    <?php
                    // Calculate read time
                    $content = get_post_field('post_content', get_the_ID());
                    $word_count = str_word_count(strip_tags($content));
                    $read_time = ceil($word_count / 200); // 200 words per minute
                    ?>
                    <span class="blog-read-time">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo $read_time; ?> мин. четене
                    </span>
                </div>
            </header>
            
            <div class="blog-content">
                <?php the_content(); ?>
            </div>
            
            <?php if (has_tag()) : ?>
                <div class="blog-tags">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M7 7H7.01M7 3H17C18.1046 3 19 3.89543 19 5V7.58579C19 8.11622 18.7893 8.62493 18.4142 9L12 15.4142C11.2426 16.1716 10.0071 16.1716 9.24985 15.4142L4.58579 10.75C3.82843 9.99264 3.82843 8.75736 4.58579 8L11 1.58579C11.3751 1.21071 11.8838 1 12.4142 1H17C18.1046 1 19 1.89543 19 3V5M7.5 7C7.5 7.27614 7.27614 7.5 7 7.5C6.72386 7.5 6.5 7.27614 6.5 7C6.5 6.72386 6.72386 6.5 7 6.5C7.27614 6.5 7.5 6.72386 7.5 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php the_tags('', ', ', ''); ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Social share buttons
            $share_url = get_permalink();
            $share_title = get_the_title();
            ?>
            <div class="blog-share">
                <span class="share-label">Сподели:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($share_url); ?>" target="_blank" rel="noopener" class="share-btn share-facebook">
                    Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($share_url); ?>&text=<?php echo urlencode($share_title); ?>" target="_blank" rel="noopener" class="share-btn share-twitter">
                    Twitter
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($share_url); ?>&title=<?php echo urlencode($share_title); ?>" target="_blank" rel="noopener" class="share-btn share-linkedin">
                    LinkedIn
                </a>
            </div>
            
        </article>
        
        <?php
        // Related blog posts
        $related_args = [
            'post_type' => 'parfume_blog',
            'posts_per_page' => 3,
            'post__not_in' => [get_the_ID()],
            'orderby' => 'rand'
        ];
        
        $related_query = new WP_Query($related_args);
        
        if ($related_query->have_posts()) :
        ?>
            <section class="related-blog-posts">
                <h2>Свързани статии</h2>
                <div class="related-posts-grid">
                    <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
                        <article class="related-post-item">
                            <a href="<?php the_permalink(); ?>" class="related-post-link">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="related-post-image">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="related-post-content">
                                    <h3><?php the_title(); ?></h3>
                                    <span class="related-post-date"><?php echo get_the_date(); ?></span>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php
            wp_reset_postdata();
        endif;
        ?>
        
        <?php
        // Comments
        if (comments_open() || get_comments_number()) :
            comments_template();
        endif;
        ?>
        
    <?php endwhile; ?>
</div>

<style>
.blog-post-wrapper {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

.blog-post {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.blog-featured-image {
    width: 100%;
    margin-bottom: 30px;
}

.blog-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

.blog-header {
    padding: 30px 30px 20px;
}

.blog-title {
    font-size: 36px;
    margin: 0 0 20px 0;
    line-height: 1.3;
}

.blog-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    font-size: 14px;
    color: #666;
}

.blog-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.blog-meta svg {
    flex-shrink: 0;
}

.blog-content {
    padding: 0 30px 30px;
    font-size: 18px;
    line-height: 1.8;
    color: #333;
}

.blog-content p {
    margin-bottom: 1.5em;
}

.blog-content h2,
.blog-content h3 {
    margin-top: 2em;
    margin-bottom: 1em;
}

.blog-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.blog-tags {
    padding: 20px 30px;
    border-top: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.blog-tags a {
    background: #f8f9fa;
    padding: 5px 12px;
    border-radius: 4px;
    text-decoration: none;
    color: #666;
    font-size: 14px;
    transition: all 0.3s ease;
}

.blog-tags a:hover {
    background: #e9ecef;
    color: #333;
}

.blog-share {
    padding: 20px 30px 30px;
    border-top: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.share-label {
    font-weight: 600;
    color: #333;
}

.share-btn {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    color: #fff;
    font-size: 14px;
    transition: all 0.3s ease;
}

.share-facebook {
    background: #1877f2;
}

.share-facebook:hover {
    background: #145dbf;
}

.share-twitter {
    background: #1da1f2;
}

.share-twitter:hover {
    background: #0d8bd9;
}

.share-linkedin {
    background: #0077b5;
}

.share-linkedin:hover {
    background: #005582;
}

.related-blog-posts {
    margin-top: 60px;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 8px;
}

.related-blog-posts h2 {
    font-size: 28px;
    margin-bottom: 30px;
    text-align: center;
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.related-post-item {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.related-post-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.related-post-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.related-post-image {
    aspect-ratio: 16/9;
    overflow: hidden;
}

.related-post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.related-post-item:hover .related-post-image img {
    transform: scale(1.1);
}

.related-post-content {
    padding: 20px;
}

.related-post-content h3 {
    font-size: 16px;
    margin: 0 0 10px 0;
    line-height: 1.4;
}

.related-post-date {
    font-size: 13px;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .blog-post-wrapper {
        padding: 0 15px;
    }
    
    .blog-header {
        padding: 20px 20px 15px;
    }
    
    .blog-title {
        font-size: 28px;
    }
    
    .blog-content {
        padding: 0 20px 20px;
        font-size: 16px;
    }
    
    .blog-tags,
    .blog-share {
        padding: 15px 20px;
    }
    
    .related-posts-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
</style>

<?php
get_footer();