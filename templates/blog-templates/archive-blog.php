<?php
/**
 * Template for blog archive
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-blog-archive">
    <div class="container">
        
        <?php if (have_posts()) : ?>
            
            <header class="archive-header">
                <h1 class="archive-title">
                    <?php
                    if (is_home() && !is_front_page()) {
                        echo 'Блог за парфюми';
                    } elseif (is_archive()) {
                        the_archive_title();
                    } else {
                        echo 'Блог за парфюми';
                    }
                    ?>
                </h1>
                
                <div class="archive-description">
                    <p>Открийте последните новини, съвети и ревюта от света на парфюмите.</p>
                </div>
                
                <div class="archive-meta">
                    <span class="post-count">
                        <?php
                        global $wp_query;
                        printf(
                            _n(
                                'Намерена %s статия',
                                'Намерени %s статии',
                                $wp_query->found_posts,
                                'parfume-catalog'
                            ),
                            number_format_i18n($wp_query->found_posts)
                        );
                        ?>
                    </span>
                </div>
            </header>

            <!-- Blog Navigation -->
            <div class="blog-navigation">
                <div class="blog-nav-links">
                    <a href="<?php echo esc_url(home_url('/parfiumi/blog/')); ?>" class="nav-link <?php echo (is_home() && get_query_var('post_type') === 'blog') ? 'current' : ''; ?>">
                        Всички статии
                    </a>
                    
                    <?php
                    // Get recent months with posts
                    global $wpdb;
                    $months = $wpdb->get_results("
                        SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month, COUNT(ID) as post_count
                        FROM {$wpdb->posts} 
                        WHERE post_type = 'blog' AND post_status = 'publish'
                        GROUP BY YEAR(post_date), MONTH(post_date)
                        ORDER BY post_date DESC
                        LIMIT 12
                    ");
                    
                    if (!empty($months)) {
                        foreach ($months as $archive_month) {
                            $month_link = get_month_link($archive_month->year, $archive_month->month);
                            $month_name = date_i18n('F Y', mktime(0, 0, 0, $archive_month->month, 1, $archive_month->year));
                            
                            printf(
                                '<a href="%s" class="nav-link">%s (%d)</a>',
                                esc_url($month_link),
                                esc_html($month_name),
                                $archive_month->post_count
                            );
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Search and Sorting -->
            <div class="blog-controls">
                <div class="blog-search">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="hidden" name="post_type" value="blog">
                        <input type="search" name="s" placeholder="Търсене в блога..." value="<?php echo get_search_query(); ?>">
                        <button type="submit">Търси</button>
                    </form>
                </div>
                
                <div class="blog-sorting">
                    <label for="sort-by">Подреди по:</label>
                    <select id="sort-by" name="orderby">
                        <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>>Най-нови</option>
                        <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>>Заглавие (А-Я)</option>
                        <option value="comment_count" <?php selected(get_query_var('orderby'), 'comment_count'); ?>>Най-коментирани</option>
                        <option value="rand" <?php selected(get_query_var('orderby'), 'rand'); ?>>Случайно</option>
                    </select>
                </div>
            </div>

            <!-- Blog Posts Grid -->
            <div class="blog-posts-grid" id="blog-results">
                <?php while (have_posts()) : the_post(); ?>
                    
                    <article class="blog-post-item" id="post-<?php the_ID(); ?>">
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="blog-post-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium_large', array('alt' => get_the_title())); ?>
                                </a>
                                
                                <!-- Post date overlay -->
                                <div class="post-date-overlay">
                                    <span class="post-day"><?php echo get_the_date('d'); ?></span>
                                    <span class="post-month"><?php echo get_the_date('M'); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="blog-post-content">
                            <header class="post-header">
                                <h2 class="post-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <div class="post-meta">
                                    <span class="post-date">
                                        <time datetime="<?php echo get_the_date('c'); ?>">
                                            <?php echo get_the_date('j F Y'); ?>
                                        </time>
                                    </span>
                                    
                                    <?php if (get_the_author()) : ?>
                                        <span class="post-author">
                                            от <?php the_author(); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (comments_open() || get_comments_number()) : ?>
                                        <span class="post-comments">
                                            <a href="<?php comments_link(); ?>">
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
                                </div>
                            </header>

                            <div class="post-excerpt">
                                <?php
                                if (has_excerpt()) {
                                    the_excerpt();
                                } else {
                                    $content = wp_strip_all_tags(get_the_content());
                                    echo wp_trim_words($content, 30, '...');
                                }
                                ?>
                            </div>

                            <!-- Tags if any -->
                            <?php
                            $tags = get_the_tags();
                            if ($tags && !is_wp_error($tags)) :
                            ?>
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
                            <?php endif; ?>

                            <footer class="post-footer">
                                <a href="<?php the_permalink(); ?>" class="read-more-btn">
                                    Прочети повече
                                </a>
                            </footer>
                        </div>
                    </article>

                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '« Предишна страница',
                'next_text' => 'Следваща страница »',
                'before_page_number' => '<span class="meta-nav screen-reader-text">Страница </span>',
            ));
            ?>

        <?php else : ?>
            
            <div class="no-results">
                <header class="page-header">
                    <h1 class="page-title">Няма намерени статии</h1>
                </header>

                <div class="page-content">
                    <?php if (is_search()) : ?>
                        <p>Съжаляваме, но не можахме да намерим резултати за вашето търсене. Опитайте отново с различни ключови думи.</p>
                    <?php else : ?>
                        <p>Все още няма публикувани статии в блога. Проверете отново скоро за нови публикации.</p>
                    <?php endif; ?>
                    
                    <div class="search-form-container">
                        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                            <input type="hidden" name="post_type" value="blog">
                            <input type="search" name="s" placeholder="Търсене в блога..." value="<?php echo get_search_query(); ?>">
                            <button type="submit">Търси</button>
                        </form>
                    </div>
                    
                    <p><a href="<?php echo esc_url(home_url('/parfiumi/')); ?>">Разгледайте нашите парфюми</a> или се <a href="<?php echo esc_url(home_url('/')); ?>">върнете към началната страница</a>.</p>
                </div>
            </div>

        <?php endif; ?>

        <!-- Sidebar with popular posts and recent comments -->
        <aside class="blog-sidebar">
            <div class="sidebar-section">
                <h3>Популярни статии</h3>
                <div class="popular-posts">
                    <?php
                    $popular_posts = new WP_Query(array(
                        'post_type' => 'blog',
                        'posts_per_page' => 5,
                        'orderby' => 'comment_count',
                        'order' => 'DESC',
                        'post_status' => 'publish',
                        'meta_query' => array(
                            array(
                                'key' => '_blog_post',
                                'compare' => 'EXISTS'
                            )
                        )
                    ));
                    
                    if ($popular_posts->have_posts()) :
                        while ($popular_posts->have_posts()) : $popular_posts->the_post();
                    ?>
                        <article class="popular-post">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="popular-post-thumb">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="popular-post-content">
                                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                <span class="popular-post-date"><?php echo get_the_date('j M Y'); ?></span>
                            </div>
                        </article>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                    ?>
                        <p>Няма популярни статии.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-section">
                <h3>Архив</h3>
                <div class="blog-archive-list">
                    <?php
                    $archives = $wpdb->get_results("
                        SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month, COUNT(ID) as post_count
                        FROM {$wpdb->posts} 
                        WHERE post_type = 'blog' AND post_status = 'publish'
                        GROUP BY YEAR(post_date), MONTH(post_date)
                        ORDER BY post_date DESC
                        LIMIT 24
                    ");
                    
                    if (!empty($archives)) {
                        foreach ($archives as $archive) {
                            $month_link = get_month_link($archive->year, $archive->month);
                            $month_name = date_i18n('F Y', mktime(0, 0, 0, $archive->month, 1, $archive->year));
                            
                            printf(
                                '<a href="%s" class="archive-link">%s (%d)</a>',
                                esc_url($month_link),
                                esc_html($month_name),
                                $archive->post_count
                            );
                        }
                    } else {
                        echo '<p>Няма архивни записи.</p>';
                    }
                    ?>
                </div>
            </div>
        </aside>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Sorting functionality
    $('#sort-by').on('change', function() {
        var url = new URL(window.location);
        var orderby = $(this).val();
        
        if (orderby && orderby !== 'date') {
            url.searchParams.set('orderby', orderby);
        } else {
            url.searchParams.delete('orderby');
        }
        
        window.location.href = url.toString();
    });
    
    // Smooth scroll for archive navigation
    $('.nav-link').on('click', function(e) {
        if ($(this).hasClass('current')) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.blog-posts-grid').offset().top - 100
            }, 500);
        }
    });
});
</script>

<?php get_footer(); ?>