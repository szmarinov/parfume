<?php
/**
 * Single Parfume Blog Post Template
 * 
 * Template for displaying single blog posts from parfume_blog post type
 * 
 * @package Parfume_Reviews
 * @since 1.0.0
 */

get_header();

$post_id = get_the_ID();
$featured = get_post_meta($post_id, '_blog_featured', true);
$related_parfumes = get_post_meta($post_id, '_blog_related_parfumes', true);
$related_parfumes = is_array($related_parfumes) ? $related_parfumes : array();

// Blog settings
$settings = get_option('parfume_reviews_settings', array());
?>

<div class="parfume-blog-single">
    <div class="container">
        <?php while (have_posts()): the_post(); ?>
            
            <!-- Breadcrumbs -->
            <nav class="blog-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'parfume-reviews'); ?>">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="<?php echo home_url(); ?>"><?php _e('Home', 'parfume-reviews'); ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?php echo get_post_type_archive_link('parfume_blog'); ?>"><?php _e('Blog', 'parfume-reviews'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php the_title(); ?>
                    </li>
                </ol>
            </nav>
            
            <div class="blog-content-wrapper">
                <!-- Main Content Area -->
                <main class="blog-main-content">
                    <article id="post-<?php the_ID(); ?>" <?php post_class('blog-article'); ?>>
                        
                        <!-- Article Header -->
                        <header class="article-header">
                            <?php if ($featured): ?>
                                <div class="featured-badge">
                                    <span class="badge-icon">⭐</span>
                                    <span class="badge-text"><?php _e('Featured', 'parfume-reviews'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <h1 class="article-title"><?php the_title(); ?></h1>
                            
                            <div class="article-meta">
                                <div class="meta-item author-meta">
                                    <span class="meta-icon">👤</span>
                                    <span class="meta-label"><?php _e('By', 'parfume-reviews'); ?></span>
                                    <span class="author-name"><?php the_author(); ?></span>
                                </div>
                                
                                <div class="meta-item date-meta">
                                    <span class="meta-icon">📅</span>
                                    <time datetime="<?php echo get_the_date('c'); ?>" class="published-date">
                                        <?php echo get_the_date(); ?>
                                    </time>
                                </div>
                                
                                <?php if (get_the_modified_date() !== get_the_date()): ?>
                                    <div class="meta-item updated-meta">
                                        <span class="meta-icon">🔄</span>
                                        <span class="meta-label"><?php _e('Updated', 'parfume-reviews'); ?></span>
                                        <time datetime="<?php echo get_the_modified_date('c'); ?>" class="updated-date">
                                            <?php echo get_the_modified_date(); ?>
                                        </time>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="meta-item reading-time">
                                    <span class="meta-icon">⏱️</span>
                                    <span class="reading-time-text">
                                        <?php 
                                        $word_count = str_word_count(strip_tags(get_the_content()));
                                        $reading_time = ceil($word_count / 200); // 200 words per minute
                                        printf(_n('%d min read', '%d min read', $reading_time, 'parfume-reviews'), $reading_time);
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if (has_category()): ?>
                                    <div class="meta-item categories-meta">
                                        <span class="meta-icon">📂</span>
                                        <span class="categories-list">
                                            <?php the_category(', '); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Featured Image -->
                            <?php if (has_post_thumbnail()): ?>
                                <div class="article-featured-image">
                                    <?php 
                                    the_post_thumbnail('large', array(
                                        'class' => 'featured-image',
                                        'alt' => get_the_title()
                                    )); 
                                    ?>
                                    
                                    <?php 
                                    $caption = get_the_post_thumbnail_caption();
                                    if ($caption): ?>
                                        <div class="image-caption">
                                            <?php echo esc_html($caption); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </header>
                        
                        <!-- Article Excerpt -->
                        <?php if (has_excerpt()): ?>
                            <div class="article-excerpt">
                                <div class="excerpt-content">
                                    <?php the_excerpt(); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Article Content -->
                        <div class="article-content">
                            <?php
                            $content = get_the_content();
                            
                            // Add table of contents if content is long
                            if (str_word_count(strip_tags($content)) > 500) {
                                echo '<div class="table-of-contents">';
                                echo '<h3>' . __('Table of Contents', 'parfume-reviews') . '</h3>';
                                echo parfume_reviews_generate_toc($content);
                                echo '</div>';
                            }
                            
                            echo apply_filters('the_content', $content);
                            ?>
                        </div>
                        
                        <!-- Tags -->
                        <?php if (has_tag()): ?>
                            <div class="article-tags">
                                <h4 class="tags-title"><?php _e('Tags', 'parfume-reviews'); ?></h4>
                                <div class="tags-list">
                                    <?php
                                    $tags = get_the_tags();
                                    foreach ($tags as $tag): ?>
                                        <a href="<?php echo get_tag_link($tag); ?>" class="tag-link" rel="tag">
                                            #<?php echo esc_html($tag->name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Social Sharing -->
                        <div class="article-sharing">
                            <h4 class="sharing-title"><?php _e('Share this article', 'parfume-reviews'); ?></h4>
                            <div class="sharing-buttons">
                                <a href="#" class="share-btn facebook" data-platform="facebook" title="<?php esc_attr_e('Share on Facebook', 'parfume-reviews'); ?>">
                                    <span class="btn-icon">📘</span>
                                    <span class="btn-text"><?php _e('Facebook', 'parfume-reviews'); ?></span>
                                </a>
                                
                                <a href="#" class="share-btn twitter" data-platform="twitter" title="<?php esc_attr_e('Share on Twitter', 'parfume-reviews'); ?>">
                                    <span class="btn-icon">🐦</span>
                                    <span class="btn-text"><?php _e('Twitter', 'parfume-reviews'); ?></span>
                                </a>
                                
                                <a href="#" class="share-btn pinterest" data-platform="pinterest" title="<?php esc_attr_e('Share on Pinterest', 'parfume-reviews'); ?>">
                                    <span class="btn-icon">📌</span>
                                    <span class="btn-text"><?php _e('Pinterest', 'parfume-reviews'); ?></span>
                                </a>
                                
                                <a href="#" class="share-btn whatsapp" data-platform="whatsapp" title="<?php esc_attr_e('Share on WhatsApp', 'parfume-reviews'); ?>">
                                    <span class="btn-icon">💬</span>
                                    <span class="btn-text"><?php _e('WhatsApp', 'parfume-reviews'); ?></span>
                                </a>
                                
                                <button type="button" class="share-btn copy-link" data-copy="<?php echo get_permalink(); ?>" title="<?php esc_attr_e('Copy link', 'parfume-reviews'); ?>">
                                    <span class="btn-icon">🔗</span>
                                    <span class="btn-text"><?php _e('Copy Link', 'parfume-reviews'); ?></span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Author Bio -->
                        <div class="author-bio-section">
                            <div class="author-bio-card">
                                <div class="author-avatar">
                                    <?php echo get_avatar(get_the_author_meta('ID'), 80); ?>
                                </div>
                                
                                <div class="author-info">
                                    <h4 class="author-name">
                                        <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>">
                                            <?php the_author(); ?>
                                        </a>
                                    </h4>
                                    
                                    <?php if (get_the_author_meta('description')): ?>
                                        <div class="author-description">
                                            <?php echo wpautop(get_the_author_meta('description')); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="author-links">
                                        <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="author-posts-link">
                                            <?php _e('View all posts', 'parfume-reviews'); ?>
                                        </a>
                                        
                                        <?php if (get_the_author_meta('website')): ?>
                                            <a href="<?php the_author_meta('website'); ?>" class="author-website" target="_blank" rel="nofollow">
                                                <?php _e('Website', 'parfume-reviews'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </article>
                    
                    <!-- Related Parfumes Section -->
                    <?php if (!empty($related_parfumes)): ?>
                        <section class="related-parfumes-section">
                            <h3 class="section-title"><?php _e('Related Perfumes', 'parfume-reviews'); ?></h3>
                            <div class="related-parfumes-grid">
                                <?php
                                $parfumes = get_posts(array(
                                    'post_type' => 'parfume',
                                    'post__in' => $related_parfumes,
                                    'posts_per_page' => 6,
                                    'post_status' => 'publish'
                                ));
                                
                                foreach ($parfumes as $parfume):
                                    $brands = wp_get_post_terms($parfume->ID, 'marki');
                                    $rating = get_post_meta($parfume->ID, '_parfume_rating', true);
                                ?>
                                    <div class="related-parfume-item">
                                        <a href="<?php echo get_permalink($parfume->ID); ?>" class="parfume-card-link">
                                            <?php if (has_post_thumbnail($parfume->ID)): ?>
                                                <div class="parfume-image">
                                                    <?php echo get_the_post_thumbnail($parfume->ID, 'medium'); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="parfume-info">
                                                <h4 class="parfume-title"><?php echo esc_html($parfume->post_title); ?></h4>
                                                
                                                <?php if (!empty($brands) && !is_wp_error($brands)): ?>
                                                    <div class="parfume-brand">
                                                        <?php echo esc_html($brands[0]->name); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($rating)): ?>
                                                    <div class="parfume-rating">
                                                        <?php echo parfume_reviews_display_rating($rating); ?>
                                                        <span class="rating-text"><?php echo number_format($rating, 1); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Related Blog Posts -->
                    <section class="related-posts-section">
                        <h3 class="section-title"><?php _e('Related Articles', 'parfume-reviews'); ?></h3>
                        
                        <?php
                        $related_posts = get_posts(array(
                            'post_type' => 'parfume_blog',
                            'posts_per_page' => 3,
                            'post__not_in' => array(get_the_ID()),
                            'orderby' => 'rand',
                            'meta_query' => array(
                                array(
                                    'key' => '_blog_featured',
                                    'value' => '1',
                                    'compare' => '='
                                )
                            )
                        ));
                        
                        // If no featured posts, get recent posts
                        if (empty($related_posts)) {
                            $related_posts = get_posts(array(
                                'post_type' => 'parfume_blog',
                                'posts_per_page' => 3,
                                'post__not_in' => array(get_the_ID()),
                                'orderby' => 'date',
                                'order' => 'DESC'
                            ));
                        }
                        ?>
                        
                        <?php if (!empty($related_posts)): ?>
                            <div class="related-posts-grid">
                                <?php foreach ($related_posts as $related_post): ?>
                                    <article class="related-post-item">
                                        <a href="<?php echo get_permalink($related_post->ID); ?>" class="post-link">
                                            <?php if (has_post_thumbnail($related_post->ID)): ?>
                                                <div class="post-image">
                                                    <?php echo get_the_post_thumbnail($related_post->ID, 'medium'); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="post-content">
                                                <h4 class="post-title"><?php echo esc_html($related_post->post_title); ?></h4>
                                                
                                                <div class="post-meta">
                                                    <span class="post-date"><?php echo get_the_date('', $related_post->ID); ?></span>
                                                </div>
                                                
                                                <div class="post-excerpt">
                                                    <?php 
                                                    echo wp_trim_words(
                                                        get_post_field('post_excerpt', $related_post->ID) ?: get_post_field('post_content', $related_post->ID), 
                                                        20
                                                    ); 
                                                    ?>
                                                </div>
                                            </div>
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-related-posts"><?php _e('No related articles found.', 'parfume-reviews'); ?></p>
                        <?php endif; ?>
                    </section>
                    
                    <!-- Comments Section -->
                    <?php if (comments_open() || get_comments_number()): ?>
                        <section class="comments-section">
                            <?php comments_template(); ?>
                        </section>
                    <?php endif; ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="blog-sidebar">
                    
                    <!-- Newsletter Signup -->
                    <div class="sidebar-widget newsletter-widget">
                        <h4 class="widget-title"><?php _e('Stay Updated', 'parfume-reviews'); ?></h4>
                        <div class="widget-content">
                            <p><?php _e('Subscribe to our newsletter for the latest perfume reviews and trends.', 'parfume-reviews'); ?></p>
                            <form class="newsletter-form" action="#" method="post">
                                <div class="form-group">
                                    <input type="email" name="newsletter_email" placeholder="<?php esc_attr_e('Your email address', 'parfume-reviews'); ?>" required>
                                    <button type="submit" class="submit-btn"><?php _e('Subscribe', 'parfume-reviews'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Recent Posts -->
                    <div class="sidebar-widget recent-posts-widget">
                        <h4 class="widget-title"><?php _e('Recent Articles', 'parfume-reviews'); ?></h4>
                        <div class="widget-content">
                            <?php
                            $recent_posts = get_posts(array(
                                'post_type' => 'parfume_blog',
                                'posts_per_page' => 5,
                                'post__not_in' => array(get_the_ID())
                            ));
                            
                            if (!empty($recent_posts)): ?>
                                <ul class="recent-posts-list">
                                    <?php foreach ($recent_posts as $recent_post): ?>
                                        <li class="recent-post-item">
                                            <a href="<?php echo get_permalink($recent_post->ID); ?>" class="recent-post-link">
                                                <?php if (has_post_thumbnail($recent_post->ID)): ?>
                                                    <div class="recent-post-image">
                                                        <?php echo get_the_post_thumbnail($recent_post->ID, 'thumbnail'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="recent-post-content">
                                                    <h5 class="recent-post-title"><?php echo esc_html($recent_post->post_title); ?></h5>
                                                    <span class="recent-post-date"><?php echo get_the_date('', $recent_post->ID); ?></span>
                                                </div>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Categories -->
                    <?php
                    $categories = get_categories(array(
                        'taxonomy' => 'category',
                        'hide_empty' => true,
                        'number' => 10
                    ));
                    
                    if (!empty($categories)): ?>
                        <div class="sidebar-widget categories-widget">
                            <h4 class="widget-title"><?php _e('Categories', 'parfume-reviews'); ?></h4>
                            <div class="widget-content">
                                <ul class="categories-list">
                                    <?php foreach ($categories as $category): ?>
                                        <li class="category-item">
                                            <a href="<?php echo get_category_link($category); ?>" class="category-link">
                                                <?php echo esc_html($category->name); ?>
                                                <span class="post-count">(<?php echo $category->count; ?>)</span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Popular Perfumes -->
                    <div class="sidebar-widget popular-perfumes-widget">
                        <h4 class="widget-title"><?php _e('Popular Perfumes', 'parfume-reviews'); ?></h4>
                        <div class="widget-content">
                            <?php echo do_shortcode('[parfume_grid limit="3" orderby="meta_value_num" meta_key="_parfume_rating" order="DESC"]'); ?>
                        </div>
                    </div>
                    
                    <!-- Archive -->
                    <div class="sidebar-widget archive-widget">
                        <h4 class="widget-title"><?php _e('Archives', 'parfume-reviews'); ?></h4>
                        <div class="widget-content">
                            <ul class="archive-list">
                                <?php
                                $archives = wp_get_archives(array(
                                    'type' => 'monthly',
                                    'limit' => 12,
                                    'format' => 'custom',
                                    'before' => '<li><a href="',
                                    'after' => '</a></li>',
                                    'echo' => false,
                                    'post_type' => 'parfume_blog'
                                ));
                                echo $archives;
                                ?>
                            </ul>
                        </div>
                    </div>
                    
                </aside>
                
            </div>
            
        <?php endwhile; ?>
    </div>
</div>

<!-- Blog Single Styles -->
<style>
.parfume-blog-single {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Breadcrumbs */
.blog-breadcrumbs {
    margin-bottom: 30px;
}

.breadcrumb-list {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    padding: 0;
    font-size: 14px;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
}

.breadcrumb-item:not(:last-child)::after {
    content: '/';
    margin-left: 10px;
    color: #666;
}

.breadcrumb-item a {
    color: #0073aa;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #666;
}

/* Content Layout */
.blog-content-wrapper {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 40px;
    align-items: start;
}

/* Main Content */
.blog-main-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.blog-article {
    padding: 40px;
}

/* Article Header */
.article-header {
    margin-bottom: 40px;
    position: relative;
}

.featured-badge {
    position: absolute;
    top: -20px;
    right: -20px;
    background: linear-gradient(135deg, #ffd700, #ffb347);
    color: #333;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 2px 10px rgba(255, 215, 0, 0.3);
}

.article-title {
    font-size: 2.5em;
    line-height: 1.2;
    margin: 0 0 20px 0;
    color: #333;
    font-weight: 700;
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #666;
}

.meta-icon {
    font-size: 16px;
}

.meta-label {
    font-weight: 500;
}

.author-name, .published-date, .updated-date {
    font-weight: 600;
    color: #333;
}

.categories-list a {
    color: #0073aa;
    text-decoration: none;
}

.categories-list a:hover {
    text-decoration: underline;
}

/* Featured Image */
.article-featured-image {
    margin-bottom: 30px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.featured-image {
    width: 100%;
    height: auto;
    display: block;
}

.image-caption {
    padding: 15px;
    background: #f8f9fa;
    font-size: 14px;
    color: #666;
    font-style: italic;
    text-align: center;
}

/* Article Excerpt */
.article-excerpt {
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(135deg, #e3f2fd, #f0f4ff);
    border-radius: 10px;
    border-left: 4px solid #2196f3;
}

.excerpt-content {
    font-size: 1.1em;
    line-height: 1.6;
    color: #333;
    font-style: italic;
}

/* Table of Contents */
.table-of-contents {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.table-of-contents h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.2em;
}

.table-of-contents ul {
    margin: 0;
    padding-left: 20px;
}

.table-of-contents li {
    margin-bottom: 8px;
}

.table-of-contents a {
    color: #0073aa;
    text-decoration: none;
}

.table-of-contents a:hover {
    text-decoration: underline;
}

/* Article Content */
.article-content {
    line-height: 1.8;
    font-size: 16px;
    color: #333;
    margin-bottom: 40px;
}

.article-content h2,
.article-content h3,
.article-content h4 {
    margin: 30px 0 15px 0;
    color: #333;
}

.article-content h2 {
    font-size: 1.8em;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.article-content h3 {
    font-size: 1.5em;
}

.article-content h4 {
    font-size: 1.3em;
}

.article-content p {
    margin-bottom: 20px;
}

.article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.article-content blockquote {
    border-left: 4px solid #0073aa;
    margin: 30px 0;
    padding: 20px 30px;
    background: #f8f9fa;
    border-radius: 0 8px 8px 0;
    font-style: italic;
}

/* Tags */
.article-tags {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.tags-title {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.1em;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tag-link {
    display: inline-block;
    padding: 6px 12px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 20px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.tag-link:hover {
    background: #005a87;
    transform: translateY(-2px);
}

/* Social Sharing */
.article-sharing {
    margin-bottom: 40px;
    padding: 25px;
    background: linear-gradient(135deg, #fff, #f8f9fa);
    border-radius: 12px;
    border: 1px solid #dee2e6;
}

.sharing-title {
    margin: 0 0 20px 0;
    color: #333;
    text-align: center;
}

.sharing-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
}

.share-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 14px;
}

.share-btn.facebook {
    background: #1877f2;
    color: white;
}

.share-btn.twitter {
    background: #1da1f2;
    color: white;
}

.share-btn.pinterest {
    background: #bd081c;
    color: white;
}

.share-btn.whatsapp {
    background: #25d366;
    color: white;
}

.share-btn.copy-link {
    background: #6c757d;
    color: white;
}

.share-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Author Bio */
.author-bio-section {
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    border: 1px solid #dee2e6;
}

.author-bio-card {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.author-avatar img {
    border-radius: 50%;
    border: 3px solid #0073aa;
}

.author-info h4 {
    margin: 0 0 10px 0;
    font-size: 1.3em;
}

.author-info h4 a {
    color: #333;
    text-decoration: none;
}

.author-info h4 a:hover {
    color: #0073aa;
}

.author-description {
    margin-bottom: 15px;
    color: #666;
    line-height: 1.6;
}

.author-links {
    display: flex;
    gap: 15px;
}

.author-links a {
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
}

.author-links a:hover {
    text-decoration: underline;
}

/* Related Content */
.related-parfumes-section,
.related-posts-section {
    margin-bottom: 40px;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.section-title {
    margin: 0 0 25px 0;
    font-size: 1.5em;
    color: #333;
    text-align: center;
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background: #0073aa;
    border-radius: 2px;
}

.related-parfumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.related-parfume-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.related-parfume-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.parfume-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.parfume-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.parfume-info {
    padding: 15px;
}

.parfume-title {
    margin: 0 0 8px 0;
    font-size: 1.1em;
    color: #333;
}

.parfume-brand {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-text {
    font-weight: 500;
    color: #333;
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.related-post-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.related-post-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.post-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.post-image img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.post-content {
    padding: 20px;
}

.post-title {
    margin: 0 0 10px 0;
    font-size: 1.1em;
    color: #333;
    line-height: 1.3;
}

.post-meta {
    margin-bottom: 10px;
    font-size: 14px;
    color: #666;
}

.post-excerpt {
    color: #666;
    line-height: 1.5;
}

/* Sidebar */
.blog-sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.sidebar-widget {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.widget-title {
    margin: 0;
    padding: 20px;
    background: linear-gradient(135deg, #0073aa, #005a87);
    color: white;
    font-size: 1.1em;
    text-align: center;
}

.widget-content {
    padding: 20px;
}

/* Newsletter Widget */
.newsletter-form {
    margin-top: 15px;
}

.form-group {
    display: flex;
    gap: 10px;
}

.form-group input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.submit-btn {
    padding: 10px 20px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
}

.submit-btn:hover {
    background: #005a87;
}

/* Recent Posts Widget */
.recent-posts-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.recent-post-item {
    border-bottom: 1px solid #eee;
    padding: 15px 0;
}

.recent-post-item:last-child {
    border-bottom: none;
}

.recent-post-link {
    display: flex;
    gap: 15px;
    text-decoration: none;
    color: inherit;
}

.recent-post-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
}

.recent-post-title {
    margin: 0 0 5px 0;
    font-size: 14px;
    line-height: 1.3;
    color: #333;
}

.recent-post-date {
    font-size: 12px;
    color: #666;
}

/* Categories Widget */
.categories-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.category-item {
    border-bottom: 1px solid #eee;
}

.category-item:last-child {
    border-bottom: none;
}

.category-link {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    text-decoration: none;
    color: #333;
}

.category-link:hover {
    color: #0073aa;
}

.post-count {
    color: #666;
    font-size: 14px;
}

/* Archive Widget */
.archive-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.archive-list li {
    border-bottom: 1px solid #eee;
}

.archive-list li:last-child {
    border-bottom: none;
}

.archive-list a {
    display: block;
    padding: 8px 0;
    text-decoration: none;
    color: #333;
}

.archive-list a:hover {
    color: #0073aa;
}

/* Comments Section */
.comments-section {
    margin-top: 40px;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* Responsive Design */
@media (max-width: 968px) {
    .blog-content-wrapper {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .blog-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .blog-article {
        padding: 20px;
    }
    
    .article-title {
        font-size: 2em;
    }
    
    .article-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .author-bio-card {
        flex-direction: column;
        text-align: center;
    }
    
    .sharing-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .share-btn {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
    
    .related-parfumes-grid,
    .related-posts-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group {
        flex-direction: column;
    }
}
</style>

<script>
// Social sharing functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle social sharing
    document.querySelectorAll('.share-btn[data-platform]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            var platform = this.dataset.platform;
            var url = encodeURIComponent(window.location.href);
            var title = encodeURIComponent(document.title);
            var shareUrl = '';
            
            switch(platform) {
                case 'facebook':
                    shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
                    break;
                case 'twitter':
                    shareUrl = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title;
                    break;
                case 'pinterest':
                    var image = document.querySelector('.featured-image');
                    var imageUrl = image ? encodeURIComponent(image.src) : '';
                    shareUrl = 'https://pinterest.com/pin/create/button/?url=' + url + '&media=' + imageUrl + '&description=' + title;
                    break;
                case 'whatsapp':
                    shareUrl = 'https://wa.me/?text=' + title + ' ' + url;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, 'share', 'width=600,height=400');
            }
        });
    });
    
    // Handle copy link
    document.querySelector('.copy-link')?.addEventListener('click', function() {
        var url = this.dataset.copy;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                showNotification('Link copied to clipboard!');
            });
        } else {
            // Fallback
            var textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('Link copied to clipboard!');
        }
    });
    
    function showNotification(message) {
        var notification = document.createElement('div');
        notification.className = 'copy-notification';
        notification.textContent = message;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; z-index: 10000; opacity: 0; transition: opacity 0.3s;';
        
        document.body.appendChild(notification);
        
        setTimeout(function() {
            notification.style.opacity = '1';
        }, 100);
        
        setTimeout(function() {
            notification.style.opacity = '0';
            setTimeout(function() {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
});
</script>

<?php
/**
 * Helper function to generate table of contents
 */
function parfume_reviews_generate_toc($content) {
    $headings = array();
    
    // Find all headings
    preg_match_all('/<h([2-6])([^>]*)>(.*?)<\/h[2-6]>/i', $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        return '';
    }
    
    $toc = '<ul>';
    
    foreach ($matches as $heading) {
        $level = $heading[1];
        $text = strip_tags($heading[3]);
        $id = sanitize_title($text);
        
        // Add ID to heading if it doesn't have one
        if (strpos($heading[2], 'id=') === false) {
            $content = str_replace($heading[0], 
                '<h' . $level . ' id="' . $id . '"' . $heading[2] . '>' . $heading[3] . '</h' . $level . '>', 
                $content);
        }
        
        $toc .= '<li><a href="#' . $id . '">' . esc_html($text) . '</a></li>';
    }
    
    $toc .= '</ul>';
    
    return $toc;
}

get_footer();
?>