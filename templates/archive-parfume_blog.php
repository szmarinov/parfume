<?php
/**
 * Archive template for Parfume Blog posts
 * 
 * Template for displaying blog archive pages with filtering,
 * featured posts, and responsive layout
 * 
 * @package ParfumeReviews
 * @subpackage Templates
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-blog-archive-wrap">
    <div class="container">
        
        <?php
        /**
         * Hook: parfume_reviews_blog_archive_before_header
         * 
         * @hooked parfume_reviews_blog_breadcrumbs - 10
         */
        do_action('parfume_reviews_blog_archive_before_header');
        ?>
        
        <!-- Archive Header -->
        <header class="blog-archive-header">
            <div class="archive-header-content">
                <h1 class="archive-title">
                    <?php
                    if (is_category()) {
                        printf(
                            /* translators: %s: category name */
                            esc_html__('Категория: %s', 'parfume-reviews'),
                            '<span class="category-name">' . single_cat_title('', false) . '</span>'
                        );
                    } elseif (is_tag()) {
                        printf(
                            /* translators: %s: tag name */
                            esc_html__('Етикет: %s', 'parfume-reviews'),
                            '<span class="tag-name">' . single_tag_title('', false) . '</span>'
                        );
                    } elseif (is_author()) {
                        printf(
                            /* translators: %s: author name */
                            esc_html__('Автор: %s', 'parfume-reviews'),
                            '<span class="author-name">' . get_the_author() . '</span>'
                        );
                    } elseif (is_date()) {
                        if (is_year()) {
                            printf(
                                /* translators: %s: year */
                                esc_html__('Година: %s', 'parfume-reviews'),
                                '<span class="year">' . get_the_date('Y') . '</span>'
                            );
                        } elseif (is_month()) {
                            printf(
                                /* translators: %s: month and year */
                                esc_html__('Месец: %s', 'parfume-reviews'),
                                '<span class="month">' . get_the_date('F Y') . '</span>'
                            );
                        }
                    } else {
                        esc_html_e('Блог за парфюми', 'parfume-reviews');
                    }
                    ?>
                </h1>
                
                <?php if (is_category() || is_tag()) : ?>
                    <div class="archive-description">
                        <?php echo term_description(); ?>
                    </div>
                <?php elseif (is_author()) : ?>
                    <div class="author-bio">
                        <?php echo get_the_author_meta('description'); ?>
                    </div>
                <?php else : ?>
                    <p class="archive-description">
                        <?php esc_html_e('Открийте най-новите статии, ревюта и новини от света на парфюмите.', 'parfume-reviews'); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Archive Stats -->
            <div class="archive-stats">
                <div class="stats-item">
                    <span class="stats-number"><?php echo wp_count_posts('parfume_blog')->publish; ?></span>
                    <span class="stats-label"><?php esc_html_e('Публикации', 'parfume-reviews'); ?></span>
                </div>
                <div class="stats-item">
                    <span class="stats-number"><?php echo get_terms(array('taxonomy' => 'category', 'count' => true, 'hide_empty' => true, 'number' => 1, 'fields' => 'count')); ?></span>
                    <span class="stats-label"><?php esc_html_e('Категории', 'parfume-reviews'); ?></span>
                </div>
            </div>
        </header>
        
        <?php
        /**
         * Hook: parfume_reviews_blog_archive_after_header
         * 
         * @hooked parfume_reviews_blog_featured_posts - 10
         */
        do_action('parfume_reviews_blog_archive_after_header');
        ?>
        
        <!-- Featured Posts Section (показва се само на главната blog страница) -->
        <?php if (is_home() || (is_post_type_archive('parfume_blog') && !is_paged())) : ?>
            <section class="featured-posts-section">
                <h2 class="section-title">
                    <span class="title-text"><?php esc_html_e('Препоръчани статии', 'parfume-reviews'); ?></span>
                    <span class="title-decoration"></span>
                </h2>
                
                <?php
                // Query за featured posts
                $featured_posts = new WP_Query(array(
                    'post_type' => 'parfume_blog',
                    'posts_per_page' => 3,
                    'meta_query' => array(
                        array(
                            'key' => '_is_featured',
                            'value' => 'yes',
                            'compare' => '='
                        )
                    ),
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));
                
                if ($featured_posts->have_posts()) : ?>
                    <div class="featured-posts-grid">
                        <?php 
                        $post_count = 0;
                        while ($featured_posts->have_posts()) : 
                            $featured_posts->the_post(); 
                            $post_count++;
                            $featured_class = ($post_count === 1) ? 'featured-main' : 'featured-secondary';
                        ?>
                            <article class="featured-post <?php echo esc_attr($featured_class); ?>">
                                <div class="featured-post-image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail($post_count === 1 ? 'large' : 'medium'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <span class="featured-badge"><?php esc_html_e('Препоръчано', 'parfume-reviews'); ?></span>
                                </div>
                                
                                <div class="featured-post-content">
                                    <div class="post-meta">
                                        <span class="post-date"><?php echo get_the_date(); ?></span>
                                        <span class="post-author"><?php esc_html_e('от', 'parfume-reviews'); ?> <?php the_author(); ?></span>
                                    </div>
                                    
                                    <h3 class="featured-post-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="featured-post-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), $post_count === 1 ? 30 : 20, '...'); ?>
                                    </div>
                                    
                                    <div class="featured-post-footer">
                                        <div class="post-categories">
                                            <?php
                                            $categories = get_the_category();
                                            if ($categories) {
                                                foreach (array_slice($categories, 0, 2) as $category) {
                                                    echo '<span class="category-tag">' . esc_html($category->name) . '</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <a href="<?php the_permalink(); ?>" class="read-more-btn">
                                            <?php esc_html_e('Прочети повече', 'parfume-reviews'); ?>
                                            <span class="btn-arrow">→</span>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php endif; wp_reset_postdata(); ?>
            </section>
        <?php endif; ?>
        
        <!-- Main Content Area -->
        <div class="blog-content-area">
            <div class="content-wrapper">
                
                <!-- Filters Bar -->
                <div class="blog-filters-bar">
                    <div class="filters-left">
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid">
                                <span class="dashicons dashicons-grid-view"></span>
                                <?php esc_html_e('Мрежа', 'parfume-reviews'); ?>
                            </button>
                            <button class="view-btn" data-view="list">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php esc_html_e('Списък', 'parfume-reviews'); ?>
                            </button>
                        </div>
                        
                        <div class="sort-dropdown">
                            <select id="blog-sort" name="blog_sort">
                                <option value="date"><?php esc_html_e('Най-нови', 'parfume-reviews'); ?></option>
                                <option value="title"><?php esc_html_e('По заглавие', 'parfume-reviews'); ?></option>
                                <option value="comment_count"><?php esc_html_e('Най-коментирани', 'parfume-reviews'); ?></option>
                                <option value="rand"><?php esc_html_e('Случайни', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filters-right">
                        <div class="search-box">
                            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                                <input type="hidden" name="post_type" value="parfume_blog">
                                <input type="search" name="s" placeholder="<?php esc_attr_e('Търси в блога...', 'parfume-reviews'); ?>" value="<?php echo get_search_query(); ?>">
                                <button type="submit">
                                    <span class="dashicons dashicons-search"></span>
                                </button>
                            </form>
                        </div>
                        
                        <div class="category-filter">
                            <select id="category-filter" name="category_filter">
                                <option value=""><?php esc_html_e('Всички категории', 'parfume-reviews'); ?></option>
                                <?php
                                $categories = get_categories(array('hide_empty' => true));
                                foreach ($categories as $category) {
                                    $selected = (is_category($category->term_id)) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <?php
                /**
                 * Hook: parfume_reviews_blog_before_loop
                 */
                do_action('parfume_reviews_blog_before_loop');
                ?>
                
                <!-- Posts Grid/List -->
                <div class="blog-posts-container" data-view="grid">
                    <?php if (have_posts()) : ?>
                        <div class="blog-posts-grid">
                            <?php while (have_posts()) : the_post(); ?>
                                <article id="post-<?php the_ID(); ?>" <?php post_class('blog-post-item'); ?>>
                                    
                                    <!-- Post Image -->
                                    <div class="post-image">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <a href="<?php the_permalink(); ?>" class="post-image-link">
                                                <?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
                                            </a>
                                        <?php else : ?>
                                            <div class="post-image-placeholder">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Post Format Icon -->
                                        <?php
                                        $post_format = get_post_format();
                                        if ($post_format) :
                                        ?>
                                            <span class="post-format-icon format-<?php echo esc_attr($post_format); ?>">
                                                <?php
                                                switch ($post_format) {
                                                    case 'video':
                                                        echo '<span class="dashicons dashicons-video-alt3"></span>';
                                                        break;
                                                    case 'gallery':
                                                        echo '<span class="dashicons dashicons-format-gallery"></span>';
                                                        break;
                                                    case 'audio':
                                                        echo '<span class="dashicons dashicons-format-audio"></span>';
                                                        break;
                                                    default:
                                                        echo '<span class="dashicons dashicons-format-standard"></span>';
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Post Content -->
                                    <div class="post-content">
                                        
                                        <!-- Post Meta -->
                                        <div class="post-meta">
                                            <span class="post-date">
                                                <span class="dashicons dashicons-calendar-alt"></span>
                                                <?php echo get_the_date(); ?>
                                            </span>
                                            <span class="post-author">
                                                <span class="dashicons dashicons-admin-users"></span>
                                                <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                                    <?php the_author(); ?>
                                                </a>
                                            </span>
                                            <span class="post-comments">
                                                <span class="dashicons dashicons-admin-comments"></span>
                                                <?php comments_number('0', '1', '%'); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Post Title -->
                                        <h2 class="post-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        
                                        <!-- Post Excerpt -->
                                        <div class="post-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?>
                                        </div>
                                        
                                        <!-- Post Footer -->
                                        <div class="post-footer">
                                            <div class="post-categories">
                                                <?php
                                                $categories = get_the_category();
                                                if ($categories) {
                                                    foreach (array_slice($categories, 0, 3) as $category) {
                                                        echo '<a href="' . esc_url(get_category_link($category->term_id)) . '" class="category-link">' . esc_html($category->name) . '</a>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            
                                            <div class="post-actions">
                                                <a href="<?php the_permalink(); ?>" class="read-more">
                                                    <?php esc_html_e('Прочети', 'parfume-reviews'); ?>
                                                    <span class="arrow">→</span>
                                                </a>
                                                
                                                <!-- Related Parfumes (ако има) -->
                                                <?php
                                                $related_parfumes = get_post_meta(get_the_ID(), '_related_parfumes', true);
                                                if (!empty($related_parfumes) && is_array($related_parfumes)) :
                                                ?>
                                                    <div class="related-parfumes-quick">
                                                        <span class="related-label"><?php esc_html_e('Парфюми:', 'parfume-reviews'); ?></span>
                                                        <?php
                                                        $parfume_links = array();
                                                        foreach (array_slice($related_parfumes, 0, 2) as $parfume_id) {
                                                            $parfume = get_post($parfume_id);
                                                            if ($parfume) {
                                                                $parfume_links[] = '<a href="' . get_permalink($parfume_id) . '">' . esc_html($parfume->post_title) . '</a>';
                                                            }
                                                        }
                                                        echo implode(', ', $parfume_links);
                                                        if (count($related_parfumes) > 2) {
                                                            echo ' <span class="more-count">+' . (count($related_parfumes) - 2) . '</span>';
                                                        }
                                                        ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php
                        /**
                         * Hook: parfume_reviews_blog_after_loop
                         */
                        do_action('parfume_reviews_blog_after_loop');
                        ?>
                        
                        <!-- Pagination -->
                        <div class="blog-pagination">
                            <?php
                            $pagination_args = array(
                                'mid_size' => 2,
                                'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Предишна', 'parfume-reviews'),
                                'next_text' => esc_html__('Следваща', 'parfume-reviews') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                                'class' => 'pagination-list'
                            );
                            
                            echo paginate_links($pagination_args);
                            ?>
                        </div>
                        
                    <?php else : ?>
                        
                        <!-- No Posts Found -->
                        <div class="no-posts-found">
                            <div class="no-posts-icon">
                                <span class="dashicons dashicons-search"></span>
                            </div>
                            <h3><?php esc_html_e('Няма намерени публикации', 'parfume-reviews'); ?></h3>
                            <p><?php esc_html_e('Опитайте да промените филтрите или да използвате различни ключови думи.', 'parfume-reviews'); ?></p>
                            
                            <div class="no-posts-suggestions">
                                <h4><?php esc_html_e('Може да ви интересуват:', 'parfume-reviews'); ?></h4>
                                <?php
                                // Показваме случайни последни постове като предложения
                                $suggestion_posts = new WP_Query(array(
                                    'post_type' => 'parfume_blog',
                                    'posts_per_page' => 3,
                                    'orderby' => 'rand',
                                    'post_status' => 'publish'
                                ));
                                
                                if ($suggestion_posts->have_posts()) : ?>
                                    <div class="suggestion-posts">
                                        <?php while ($suggestion_posts->have_posts()) : $suggestion_posts->the_post(); ?>
                                            <div class="suggestion-item">
                                                <a href="<?php the_permalink(); ?>">
                                                    <?php if (has_post_thumbnail()) : ?>
                                                        <?php the_post_thumbnail('thumbnail'); ?>
                                                    <?php endif; ?>
                                                    <span class="suggestion-title"><?php the_title(); ?></span>
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php endif; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        
                    <?php endif; ?>
                </div>
                
            </div>
            
            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <?php
                /**
                 * Hook: parfume_reviews_blog_sidebar
                 * 
                 * @hooked parfume_reviews_blog_categories_widget - 10
                 * @hooked parfume_reviews_blog_recent_posts_widget - 20
                 * @hooked parfume_reviews_blog_tags_widget - 30
                 * @hooked parfume_reviews_blog_archive_widget - 40
                 */
                do_action('parfume_reviews_blog_sidebar');
                
                // Fallback widgets ако няма hook съдържание
                if (!has_action('parfume_reviews_blog_sidebar')) :
                ?>
                    <!-- Categories Widget -->
                    <div class="sidebar-widget widget-categories">
                        <h3 class="widget-title"><?php esc_html_e('Категории', 'parfume-reviews'); ?></h3>
                        <ul class="category-list">
                            <?php
                            wp_list_categories(array(
                                'title_li' => '',
                                'show_count' => true,
                                'taxonomy' => 'category'
                            ));
                            ?>
                        </ul>
                    </div>
                    
                    <!-- Recent Posts Widget -->
                    <div class="sidebar-widget widget-recent-posts">
                        <h3 class="widget-title"><?php esc_html_e('Последни публикации', 'parfume-reviews'); ?></h3>
                        <?php
                        $recent_posts = new WP_Query(array(
                            'post_type' => 'parfume_blog',
                            'posts_per_page' => 5,
                            'post_status' => 'publish'
                        ));
                        
                        if ($recent_posts->have_posts()) : ?>
                            <div class="recent-posts-list">
                                <?php while ($recent_posts->have_posts()) : $recent_posts->the_post(); ?>
                                    <div class="recent-post-item">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="recent-post-thumb">
                                                <a href="<?php the_permalink(); ?>">
                                                    <?php the_post_thumbnail('thumbnail'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="recent-post-content">
                                            <h4 class="recent-post-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h4>
                                            <div class="recent-post-meta">
                                                <span class="recent-post-date"><?php echo get_the_date(); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; wp_reset_postdata(); ?>
                    </div>
                    
                    <!-- Tags Widget -->
                    <div class="sidebar-widget widget-tags">
                        <h3 class="widget-title"><?php esc_html_e('Етикети', 'parfume-reviews'); ?></h3>
                        <div class="tag-cloud">
                            <?php
                            wp_tag_cloud(array(
                                'smallest' => 12,
                                'largest' => 18,
                                'unit' => 'px',
                                'number' => 20,
                                'taxonomy' => 'post_tag'
                            ));
                            ?>
                        </div>
                    </div>
                    
                    <!-- Archive Widget -->
                    <div class="sidebar-widget widget-archive">
                        <h3 class="widget-title"><?php esc_html_e('Архив', 'parfume-reviews'); ?></h3>
                        <ul class="archive-list">
                            <?php
                            wp_get_archives(array(
                                'type' => 'monthly',
                                'show_post_count' => true,
                                'limit' => 12
                            ));
                            ?>
                        </ul>
                    </div>
                    
                <?php endif; ?>
            </aside>
            
        </div>
        
    </div>
</div>

<?php
/**
 * Hook: parfume_reviews_blog_archive_footer
 */
do_action('parfume_reviews_blog_archive_footer');
?>

<!-- Inline JavaScript за enhanced functionality -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // View Toggle Functionality
    $('.view-toggle .view-btn').on('click', function(e) {
        e.preventDefault();
        
        const view = $(this).data('view');
        const $container = $('.blog-posts-container');
        
        // Update active button
        $('.view-toggle .view-btn').removeClass('active');
        $(this).addClass('active');
        
        // Update container view
        $container.attr('data-view', view);
        
        // Store preference
        localStorage.setItem('blog_view_preference', view);
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('blog_view_preference');
    if (savedView) {
        $('.view-toggle .view-btn[data-view="' + savedView + '"]').click();
    }
    
    // Sort Functionality
    $('#blog-sort').on('change', function() {
        const sortBy = $(this).val();
        const currentUrl = new URL(window.location.href);
        
        currentUrl.searchParams.set('orderby', sortBy);
        window.location.href = currentUrl.toString();
    });
    
    // Category Filter
    $('#category-filter').on('change', function() {
        const category = $(this).val();
        const currentUrl = new URL(window.location.href);
        
        if (category) {
            currentUrl.searchParams.set('category_name', category);
        } else {
            currentUrl.searchParams.delete('category_name');
        }
        
        window.location.href = currentUrl.toString();
    });
    
    // Enhanced Search with Auto-complete (basic implementation)
    const $searchInput = $('.search-box input[type="search"]');
    let searchTimeout;
    
    $searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        if (query.length >= 3) {
            searchTimeout = setTimeout(function() {
                // Тук можете да добавите AJAX auto-complete функционалност
                console.log('Searching for: ' + query);
            }, 300);
        }
    });
    
    // Smooth scroll за pagination links
    $('.blog-pagination a').on('click', function(e) {
        $('html, body').animate({
            scrollTop: $('.blog-filters-bar').offset().top - 100
        }, 500);
    });
    
    // Lazy loading за images (ако браузърът не поддържа native lazy loading)
    if (!('loading' in HTMLImageElement.prototype)) {
        $('img[loading="lazy"]').each(function() {
            $(this).attr('data-src', $(this).attr('src')).removeAttr('src');
        });
        
        // Implement intersection observer fallback
        // Тук можете да добавите polyfill за lazy loading
    }
    
});
</script>

<?php get_footer(); ?>