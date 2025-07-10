<?php
/**
 * Template for single blog post
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-blog-single">
    <div class="container">
        
        <?php while (have_posts()) : the_post(); ?>
            
            <article class="blog-post" id="post-<?php the_ID(); ?>">
                
                <!-- Breadcrumbs -->
                <nav class="breadcrumbs">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Начало</a>
                    <span class="separator">›</span>
                    <a href="<?php echo esc_url(home_url('/parfiumi/')); ?>">Парфюми</a>
                    <span class="separator">›</span>
                    <a href="<?php echo esc_url(home_url('/parfiumi/blog/')); ?>">Блог</a>
                    <span class="separator">›</span>
                    <span class="current"><?php the_title(); ?></span>
                </nav>

                <header class="post-header">
                    <h1 class="post-title"><?php the_title(); ?></h1>
                    
                    <div class="post-meta">
                        <div class="meta-left">
                            <span class="post-date">
                                <time datetime="<?php echo get_the_date('c'); ?>">
                                    <?php echo get_the_date('j F Y'); ?>
                                </time>
                            </span>
                            
                            <?php if (get_the_author()) : ?>
                                <span class="post-author">
                                    от <strong><?php the_author(); ?></strong>
                                </span>
                            <?php endif; ?>
                            
                            <span class="reading-time">
                                <?php
                                $word_count = str_word_count(strip_tags(get_the_content()));
                                $reading_time = ceil($word_count / 200); // Average reading speed
                                printf('%d мин. четене', $reading_time);
                                ?>
                            </span>
                        </div>
                        
                        <div class="meta-right">
                            <?php if (comments_open() || get_comments_number()) : ?>
                                <span class="post-comments">
                                    <a href="#comments">
                                        <?php
                                        printf(
                                            _n(
                                                '%s коментар',
                                                '%s коментара',
                                                get_comments_number(),
                                                'parfume-catalog'
                                            ),
                                            number_format_i18n(get_comments_number())
                                        );
                                        ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <div class="social-share">
                                <span class="share-label">Сподели:</span>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="share-facebook" title="Сподели във Facebook">
                                    FB
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" class="share-twitter" title="Сподели в Twitter">
                                    TW
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode(get_the_title()); ?>&body=<?php echo urlencode(get_permalink()); ?>" class="share-email" title="Изпрати по имейл">
                                    ✉
                                </a>
                            </div>
                        </div>
                    </div>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-featured-image">
                        <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                        
                        <?php
                        $caption = get_the_post_thumbnail_caption();
                        if ($caption) :
                        ?>
                            <div class="image-caption">
                                <?php echo esc_html($caption); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="post-content">
                    <?php
                    the_content();
                    
                    wp_link_pages(array(
                        'before' => '<div class="page-links">Страници: ',
                        'after' => '</div>',
                        'link_before' => '<span class="page-number">',
                        'link_after' => '</span>',
                    ));
                    ?>
                </div>

                <!-- Tags -->
                <?php
                $tags = get_the_tags();
                if ($tags && !is_wp_error($tags)) :
                ?>
                    <footer class="post-footer">
                        <div class="post-tags">
                            <span class="tags-label">Тагове:</span>
                            <?php
                            $tag_links = array();
                            foreach ($tags as $tag) {
                                $tag_links[] = sprintf(
                                    '<a href="%s" class="tag-link">#%s</a>',
                                    esc_url(get_tag_link($tag)),
                                    esc_html($tag->name)
                                );
                            }
                            echo implode(' ', $tag_links);
                            ?>
                        </div>
                    </footer>
                <?php endif; ?>

                <!-- Post Navigation -->
                <nav class="post-navigation">
                    <div class="nav-links">
                        <?php
                        $prev_post = get_previous_post();
                        $next_post = get_next_post();
                        ?>
                        
                        <?php if ($prev_post) : ?>
                            <div class="nav-previous">
                                <a href="<?php echo esc_url(get_permalink($prev_post)); ?>">
                                    <span class="nav-direction">« Предишна статия</span>
                                    <span class="nav-title"><?php echo esc_html(get_the_title($prev_post)); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($next_post) : ?>
                            <div class="nav-next">
                                <a href="<?php echo esc_url(get_permalink($next_post)); ?>">
                                    <span class="nav-direction">Следваща статия »</span>
                                    <span class="nav-title"><?php echo esc_html(get_the_title($next_post)); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </nav>

                <!-- Author Bio -->
                <?php if (get_the_author_meta('description')) : ?>
                    <div class="author-bio">
                        <div class="author-avatar">
                            <?php echo get_avatar(get_the_author_meta('ID'), 80); ?>
                        </div>
                        <div class="author-info">
                            <h3 class="author-name"><?php the_author(); ?></h3>
                            <div class="author-description">
                                <?php echo wp_kses_post(get_the_author_meta('description')); ?>
                            </div>
                            
                            <div class="author-links">
                                <?php if (get_the_author_meta('url')) : ?>
                                    <a href="<?php echo esc_url(get_the_author_meta('url')); ?>" target="_blank" class="author-website">
                                        Уебсайт
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="author-posts">
                                    Всички статии от автора
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Related Posts -->
                <div class="related-posts">
                    <h3>Свързани статии</h3>
                    <?php
                    $related_posts = new WP_Query(array(
                        'post_type' => 'blog',
                        'posts_per_page' => 3,
                        'post__not_in' => array(get_the_ID()),
                        'orderby' => 'rand',
                        'post_status' => 'publish',
                        'meta_query' => array(
                            array(
                                'key' => '_blog_post',
                                'compare' => 'EXISTS'
                            )
                        )
                    ));
                    
                    if ($related_posts->have_posts()) :
                    ?>
                        <div class="related-posts-grid">
                            <?php while ($related_posts->have_posts()) : $related_posts->the_post(); ?>
                                <article class="related-post">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <div class="related-post-thumb">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="related-post-content">
                                        <h4 class="related-post-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h4>
                                        
                                        <div class="related-post-meta">
                                            <span class="related-post-date"><?php echo get_the_date('j M Y'); ?></span>
                                        </div>
                                        
                                        <div class="related-post-excerpt">
                                            <?php
                                            if (has_excerpt()) {
                                                echo wp_trim_words(get_the_excerpt(), 15);
                                            } else {
                                                echo wp_trim_words(get_the_content(), 15);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    <?php
                        wp_reset_postdata();
                    else :
                    ?>
                        <p>Няма свързани статии.</p>
                    <?php endif; ?>
                </div>

            </article>

            <!-- Comments Section -->
            <?php
            if (comments_open() || get_comments_number()) {
                comments_template();
            }
            ?>

        <?php endwhile; ?>

        <!-- Sidebar -->
        <aside class="blog-sidebar">
            <div class="back-to-blog">
                <a href="<?php echo esc_url(home_url('/parfiumi/blog/')); ?>" class="back-btn">
                    ← Назад към блога
                </a>
            </div>

            <div class="sidebar-section">
                <h3>Последни статии</h3>
                <div class="recent-posts">
                    <?php
                    $recent_posts = new WP_Query(array(
                        'post_type' => 'blog',
                        'posts_per_page' => 5,
                        'post__not_in' => array(get_the_ID()),
                        'post_status' => 'publish',
                        'meta_query' => array(
                            array(
                                'key' => '_blog_post',
                                'compare' => 'EXISTS'
                            )
                        )
                    ));
                    
                    if ($recent_posts->have_posts()) :
                        while ($recent_posts->have_posts()) : $recent_posts->the_post();
                    ?>
                        <article class="recent-post">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="recent-post-thumb">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="recent-post-content">
                                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                <span class="recent-post-date"><?php echo get_the_date('j M Y'); ?></span>
                            </div>
                        </article>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                    ?>
                        <p>Няма други статии.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-section">
                <h3>Търсене в блога</h3>
                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="blog-search-form">
                    <input type="hidden" name="post_type" value="blog">
                    <input type="search" name="s" placeholder="Търсене..." value="<?php echo get_search_query(); ?>">
                    <button type="submit">Търси</button>
                </form>
            </div>

            <div class="sidebar-section">
                <h3>Популярни парфюми</h3>
                <div class="popular-parfumes">
                    <?php
                    $popular_parfumes = new WP_Query(array(
                        'post_type' => 'parfumes',
                        'posts_per_page' => 3,
                        'orderby' => 'comment_count',
                        'order' => 'DESC',
                        'post_status' => 'publish'
                    ));
                    
                    if ($popular_parfumes->have_posts()) :
                        while ($popular_parfumes->have_posts()) : $popular_parfumes->the_post();
                    ?>
                        <article class="popular-parfume">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="popular-parfume-thumb">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="popular-parfume-content">
                                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                
                                <?php
                                $marki = wp_get_post_terms(get_the_ID(), 'parfume_marki');
                                if (!empty($marki) && !is_wp_error($marki)) {
                                    echo '<span class="parfume-brand">' . esc_html($marki[0]->name) . '</span>';
                                }
                                ?>
                            </div>
                        </article>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                    ?>
                        <p>Няма популярни парфюми.</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Smooth scroll for internal links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Social share tracking (optional)
    $('.social-share a').on('click', function() {
        var platform = $(this).attr('class').replace('share-', '');
        // You can add analytics tracking here
        console.log('Shared on: ' + platform);
    });
    
    // Reading progress indicator
    var $window = $(window);
    var $document = $(document);
    var $progressBar = $('<div class="reading-progress"><div class="progress-bar"></div></div>');
    
    $('body').append($progressBar);
    
    $window.on('scroll', function() {
        var scrollTop = $window.scrollTop();
        var documentHeight = $document.height();
        var windowHeight = $window.height();
        var progress = (scrollTop / (documentHeight - windowHeight)) * 100;
        
        $('.progress-bar').css('width', progress + '%');
    });
});
</script>

<?php get_footer(); ?>