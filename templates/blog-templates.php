<?php
/**
 * Blog Template Functions
 * 
 * Template functions for blog functionality
 * 
 * @package Parfume_Reviews
 * @subpackage Templates
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display blog post card
 */
function parfume_blog_card($post_id, $args = array()) {
    $defaults = array(
        'show_excerpt' => true,
        'show_date' => true,
        'show_author' => true,
        'show_categories' => true,
        'show_tags' => false,
        'excerpt_length' => 20,
        'image_size' => 'medium',
        'card_class' => 'blog-card',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $post = get_post($post_id);
    if (!$post) return '';
    
    $post_categories = get_the_category($post_id);
    $post_tags = get_the_tags($post_id);
    $featured_image = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, $args['image_size']) : '';
    
    ob_start();
    ?>
    <article class="<?php echo esc_attr($args['card_class']); ?>" data-post-id="<?php echo $post_id; ?>">
        <?php if ($featured_image): ?>
            <div class="blog-card-image">
                <a href="<?php echo get_permalink($post_id); ?>" aria-label="<?php echo esc_attr($post->post_title); ?>">
                    <img src="<?php echo esc_url($featured_image); ?>" 
                         alt="<?php echo esc_attr($post->post_title); ?>"
                         loading="lazy">
                </a>
                <?php if ($args['show_categories'] && !empty($post_categories)): ?>
                    <div class="blog-card-categories">
                        <?php foreach (array_slice($post_categories, 0, 2) as $category): ?>
                            <a href="<?php echo get_category_link($category->term_id); ?>" 
                               class="category-tag"><?php echo esc_html($category->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="blog-card-content">
            <h3 class="blog-card-title">
                <a href="<?php echo get_permalink($post_id); ?>"><?php echo esc_html($post->post_title); ?></a>
            </h3>
            
            <div class="blog-card-meta">
                <?php if ($args['show_date']): ?>
                    <span class="blog-date">
                        <i class="icon-calendar"></i>
                        <time datetime="<?php echo get_the_date('c', $post_id); ?>">
                            <?php echo get_the_date('', $post_id); ?>
                        </time>
                    </span>
                <?php endif; ?>
                
                <?php if ($args['show_author']): ?>
                    <span class="blog-author">
                        <i class="icon-user"></i>
                        <a href="<?php echo get_author_posts_url($post->post_author); ?>">
                            <?php echo get_the_author_meta('display_name', $post->post_author); ?>
                        </a>
                    </span>
                <?php endif; ?>
                
                <span class="read-time">
                    <i class="icon-clock"></i>
                    <?php echo parfume_calculate_read_time($post->post_content); ?> <?php _e('min read', 'parfume-reviews'); ?>
                </span>
            </div>
            
            <?php if ($args['show_excerpt']): ?>
                <div class="blog-card-excerpt">
                    <?php echo wp_trim_words($post->post_excerpt ?: $post->post_content, $args['excerpt_length']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($args['show_tags'] && !empty($post_tags)): ?>
                <div class="blog-card-tags">
                    <?php foreach (array_slice($post_tags, 0, 3) as $tag): ?>
                        <a href="<?php echo get_tag_link($tag->term_id); ?>" 
                           class="tag"><?php echo esc_html($tag->name); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="blog-card-footer">
                <a href="<?php echo get_permalink($post_id); ?>" class="read-more-btn">
                    <?php _e('Read More', 'parfume-reviews'); ?>
                    <i class="icon-arrow-right"></i>
                </a>
                
                <div class="blog-card-actions">
                    <button class="share-btn" data-post-id="<?php echo $post_id; ?>" 
                            title="<?php _e('Share this post', 'parfume-reviews'); ?>">
                        <i class="icon-share"></i>
                    </button>
                    <button class="bookmark-btn" data-post-id="<?php echo $post_id; ?>"
                            title="<?php _e('Bookmark this post', 'parfume-reviews'); ?>">
                        <i class="icon-bookmark"></i>
                    </button>
                </div>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Display blog post grid
 */
function parfume_blog_grid($posts, $args = array()) {
    if (empty($posts)) return '';
    
    $defaults = array(
        'columns' => 3,
        'gap' => '20px',
        'card_args' => array(),
        'grid_class' => 'blog-grid',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    ob_start();
    ?>
    <div class="<?php echo esc_attr($args['grid_class']); ?>" 
         style="--grid-columns: <?php echo intval($args['columns']); ?>; --grid-gap: <?php echo esc_attr($args['gap']); ?>;">
        <?php foreach ($posts as $post): ?>
            <?php echo parfume_blog_card($post->ID, $args['card_args']); ?>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Display related blog posts
 */
function parfume_related_posts($post_id, $args = array()) {
    $defaults = array(
        'posts_per_page' => 4,
        'title' => __('Related Posts', 'parfume-reviews'),
        'show_title' => true,
        'columns' => 2,
        'card_args' => array(
            'show_categories' => false,
            'excerpt_length' => 15,
        ),
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Get current post categories
    $categories = wp_get_post_categories($post_id);
    if (empty($categories)) return '';
    
    // Query related posts
    $related_query = new WP_Query(array(
        'post_type' => 'post',
        'posts_per_page' => $args['posts_per_page'],
        'post__not_in' => array($post_id),
        'category__in' => $categories,
        'orderby' => 'rand',
    ));
    
    if (!$related_query->have_posts()) {
        wp_reset_postdata();
        return '';
    }
    
    ob_start();
    ?>
    <section class="related-posts">
        <?php if ($args['show_title']): ?>
            <h3 class="related-posts-title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>
        
        <?php echo parfume_blog_grid($related_query->posts, array(
            'columns' => $args['columns'],
            'card_args' => $args['card_args'],
            'grid_class' => 'related-posts-grid',
        )); ?>
    </section>
    <?php
    
    wp_reset_postdata();
    return ob_get_clean();
}

/**
 * Display blog categories with post counts
 */
function parfume_blog_categories($args = array()) {
    $defaults = array(
        'show_count' => true,
        'show_empty' => false,
        'orderby' => 'count',
        'order' => 'DESC',
        'number' => 10,
        'title' => __('Categories', 'parfume-reviews'),
        'show_title' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $categories = get_categories(array(
        'orderby' => $args['orderby'],
        'order' => $args['order'],
        'number' => $args['number'],
        'hide_empty' => !$args['show_empty'],
    ));
    
    if (empty($categories)) return '';
    
    ob_start();
    ?>
    <div class="blog-categories">
        <?php if ($args['show_title']): ?>
            <h3 class="categories-title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>
        
        <ul class="categories-list">
            <?php foreach ($categories as $category): ?>
                <li class="category-item">
                    <a href="<?php echo get_category_link($category->term_id); ?>" 
                       class="category-link">
                        <span class="category-name"><?php echo esc_html($category->name); ?></span>
                        <?php if ($args['show_count']): ?>
                            <span class="category-count"><?php echo $category->count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Display blog archive navigation
 */
function parfume_blog_archive_nav($args = array()) {
    $defaults = array(
        'type' => 'monthly', // monthly, yearly
        'show_post_count' => true,
        'limit' => 12,
        'title' => __('Archives', 'parfume-reviews'),
        'show_title' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    if ($args['type'] === 'monthly') {
        $archives = wp_get_archives(array(
            'type' => 'monthly',
            'limit' => $args['limit'],
            'echo' => false,
            'show_post_count' => $args['show_post_count'],
        ));
    } else {
        $archives = wp_get_archives(array(
            'type' => 'yearly',
            'limit' => $args['limit'],
            'echo' => false,
            'show_post_count' => $args['show_post_count'],
        ));
    }
    
    if (empty($archives)) return '';
    
    ob_start();
    ?>
    <div class="blog-archives">
        <?php if ($args['show_title']): ?>
            <h3 class="archives-title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>
        
        <ul class="archives-list">
            <?php echo $archives; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Display popular blog posts
 */
function parfume_popular_posts($args = array()) {
    $defaults = array(
        'posts_per_page' => 5,
        'title' => __('Popular Posts', 'parfume-reviews'),
        'show_title' => true,
        'meta_key' => 'post_views_count', // Assumes you track post views
        'show_views' => true,
        'show_date' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $popular_query = new WP_Query(array(
        'post_type' => 'post',
        'posts_per_page' => $args['posts_per_page'],
        'meta_key' => $args['meta_key'],
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    ));
    
    if (!$popular_query->have_posts()) {
        wp_reset_postdata();
        return '';
    }
    
    ob_start();
    ?>
    <div class="popular-posts">
        <?php if ($args['show_title']): ?>
            <h3 class="popular-posts-title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>
        
        <ul class="popular-posts-list">
            <?php while ($popular_query->have_posts()): $popular_query->the_post(); ?>
                <li class="popular-post-item">
                    <div class="popular-post-content">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="popular-post-thumb">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('thumbnail'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="popular-post-details">
                            <h4 class="popular-post-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <div class="popular-post-meta">
                                <?php if ($args['show_date']): ?>
                                    <span class="post-date"><?php echo get_the_date('M j, Y'); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($args['show_views']): ?>
                                    <?php $views = get_post_meta(get_the_ID(), $args['meta_key'], true); ?>
                                    <?php if ($views): ?>
                                        <span class="post-views">
                                            <?php printf(__('%s views', 'parfume-reviews'), number_format($views)); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
    <?php
    
    wp_reset_postdata();
    return ob_get_clean();
}

/**
 * Display blog search form
 */
function parfume_blog_search_form($args = array()) {
    $defaults = array(
        'placeholder' => __('Search blog posts...', 'parfume-reviews'),
        'button_text' => __('Search', 'parfume-reviews'),
        'show_button' => true,
        'form_class' => 'blog-search-form',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    ob_start();
    ?>
    <form role="search" method="get" class="<?php echo esc_attr($args['form_class']); ?>" action="<?php echo esc_url(home_url('/')); ?>">
        <div class="search-input-group">
            <input type="search" 
                   name="s" 
                   placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                   value="<?php echo get_search_query(); ?>"
                   class="search-field"
                   autocomplete="off">
            
            <?php if ($args['show_button']): ?>
                <button type="submit" class="search-submit">
                    <i class="icon-search"></i>
                    <span class="sr-only"><?php echo esc_html($args['button_text']); ?></span>
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Hidden field to search only in posts -->
        <input type="hidden" name="post_type" value="post">
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Display blog post navigation (prev/next)
 */
function parfume_blog_post_navigation($args = array()) {
    $defaults = array(
        'prev_text' => __('Previous Post', 'parfume-reviews'),
        'next_text' => __('Next Post', 'parfume-reviews'),
        'show_thumbnails' => true,
        'in_same_term' => false,
        'taxonomy' => 'category',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $prev_post = get_previous_post($args['in_same_term'], '', $args['taxonomy']);
    $next_post = get_next_post($args['in_same_term'], '', $args['taxonomy']);
    
    if (!$prev_post && !$next_post) return '';
    
    ob_start();
    ?>
    <nav class="post-navigation">
        <div class="nav-links">
            <?php if ($prev_post): ?>
                <div class="nav-previous">
                    <a href="<?php echo get_permalink($prev_post->ID); ?>" class="nav-link">
                        <span class="nav-direction"><?php echo esc_html($args['prev_text']); ?></span>
                        <div class="nav-content">
                            <?php if ($args['show_thumbnails'] && has_post_thumbnail($prev_post->ID)): ?>
                                <div class="nav-thumb">
                                    <?php echo get_the_post_thumbnail($prev_post->ID, 'thumbnail'); ?>
                                </div>
                            <?php endif; ?>
                            <span class="nav-title"><?php echo esc_html($prev_post->post_title); ?></span>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($next_post): ?>
                <div class="nav-next">
                    <a href="<?php echo get_permalink($next_post->ID); ?>" class="nav-link">
                        <span class="nav-direction"><?php echo esc_html($args['next_text']); ?></span>
                        <div class="nav-content">
                            <?php if ($args['show_thumbnails'] && has_post_thumbnail($next_post->ID)): ?>
                                <div class="nav-thumb">
                                    <?php echo get_the_post_thumbnail($next_post->ID, 'thumbnail'); ?>
                                </div>
                            <?php endif; ?>
                            <span class="nav-title"><?php echo esc_html($next_post->post_title); ?></span>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>
    <?php
    return ob_get_clean();
}

/**
 * Calculate reading time for post content
 */
function parfume_calculate_read_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Assuming 200 words per minute
    return max(1, $reading_time);
}

/**
 * Display author bio box
 */
function parfume_author_bio($author_id = null, $args = array()) {
    if (!$author_id) {
        global $post;
        $author_id = $post->post_author;
    }
    
    $defaults = array(
        'show_avatar' => true,
        'avatar_size' => 80,
        'show_name' => true,
        'show_bio' => true,
        'show_social' => true,
        'show_posts_link' => true,
        'title' => __('About the Author', 'parfume-reviews'),
        'show_title' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_bio = get_the_author_meta('description', $author_id);
    $author_url = get_author_posts_url($author_id);
    
    if (empty($author_bio) && !$args['show_name']) return '';
    
    ob_start();
    ?>
    <div class="author-bio">
        <?php if ($args['show_title']): ?>
            <h3 class="author-bio-title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>
        
        <div class="author-bio-content">
            <?php if ($args['show_avatar']): ?>
                <div class="author-avatar">
                    <?php echo get_avatar($author_id, $args['avatar_size']); ?>
                </div>
            <?php endif; ?>
            
            <div class="author-details">
                <?php if ($args['show_name']): ?>
                    <h4 class="author-name">
                        <a href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?></a>
                    </h4>
                <?php endif; ?>
                
                <?php if ($args['show_bio'] && $author_bio): ?>
                    <p class="author-description"><?php echo esc_html($author_bio); ?></p>
                <?php endif; ?>
                
                <div class="author-links">
                    <?php if ($args['show_posts_link']): ?>
                        <a href="<?php echo esc_url($author_url); ?>" class="author-posts-link">
                            <?php printf(__('View all posts by %s', 'parfume-reviews'), $author_name); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($args['show_social']): ?>
                        <?php
                        $social_links = array(
                            'twitter' => get_the_author_meta('twitter', $author_id),
                            'facebook' => get_the_author_meta('facebook', $author_id),
                            'linkedin' => get_the_author_meta('linkedin', $author_id),
                            'instagram' => get_the_author_meta('instagram', $author_id),
                        );
                        
                        $has_social = array_filter($social_links);
                        ?>
                        
                        <?php if (!empty($has_social)): ?>
                            <div class="author-social">
                                <?php foreach ($social_links as $platform => $url): ?>
                                    <?php if ($url): ?>
                                        <a href="<?php echo esc_url($url); ?>" 
                                           class="social-link social-<?php echo $platform; ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer">
                                            <i class="icon-<?php echo $platform; ?>"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Display blog tags cloud
 */
function parfume_blog_tags_cloud($args = array()) {
    $defaults = array(
        'number' => 30,
        'orderby' => 'count',
        'order' => 'DESC',
        'title' => __('Popular Tags', 'parfume-reviews'),
        'show_title' => true,
        'smallest' => 12,
        'largest' => 18,
        'unit' => 'px',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $tags = wp_tag_cloud(array(
        'echo' => false,
        'number' => $args['number'],
        'orderby' => $args['orderby'],
        'order' => $args['order'],
        'smallest' => $args['smallest'],
        'largest' => $args['largest'],
        'unit' => $args['unit'],
    ));
    
    if (empty($tags)) return '';
    
    ob_start();
    ?>
    <div class="blog-tags-cloud">
        <?php if ($args['show_title']): ?>
            <h3 class="tags-cloud-title"><?php echo esc_html($args['title']); ?></h3>
        <?php endif; ?>
        
        <div class="tags-cloud-content">
            <?php echo $tags; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}