<?php
/**
 * Blog Shortcodes for Parfume Reviews Plugin
 * 
 * @package ParfumeReviews
 * @subpackage Frontend\Shortcodes
 * @since 1.0.0
 */

namespace Parfume_Reviews\Frontend\Shortcodes;

use Parfume_Reviews\Utils\Shortcode_Base;
use Parfume_Reviews\Utils\Helpers;
use Parfume_Reviews\Utils\Cache_Manager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Blog Shortcodes Class
 * 
 * Handles all blog-related shortcodes for the plugin.
 * Extends Shortcode_Base for consistent functionality.
 */
class Blog extends Shortcode_Base {
    
    /**
     * Shortcode configurations
     * 
     * @var array
     */
    protected $shortcodes = array(
        'blog_posts' => array(
            'callback' => 'render_blog_posts',
            'defaults' => array(
                'posts_per_page' => 6,
                'category' => '',
                'tag' => '',
                'author' => '',
                'orderby' => 'date',
                'order' => 'DESC',
                'columns' => 3,
                'show_excerpt' => 'true',
                'show_author' => 'true',
                'show_date' => 'true',
                'show_categories' => 'true',
                'excerpt_length' => 150,
                'image_size' => 'medium',
                'pagination' => 'false',
                'class' => '',
            )
        ),
        'blog_categories' => array(
            'callback' => 'render_blog_categories',
            'defaults' => array(
                'show_count' => 'true',
                'hide_empty' => 'true',
                'orderby' => 'name',
                'order' => 'ASC',
                'columns' => 4,
                'show_description' => 'false',
                'class' => '',
            )
        ),
        'popular_posts' => array(
            'callback' => 'render_popular_posts',
            'defaults' => array(
                'posts_per_page' => 5,
                'time_range' => '30', // days
                'orderby' => 'views',
                'show_views' => 'true',
                'show_date' => 'true',
                'show_excerpt' => 'false',
                'image_size' => 'thumbnail',
                'class' => '',
            )
        ),
        'recent_posts' => array(
            'callback' => 'render_recent_posts',
            'defaults' => array(
                'posts_per_page' => 5,
                'category' => '',
                'show_date' => 'true',
                'show_excerpt' => 'true',
                'excerpt_length' => 100,
                'image_size' => 'thumbnail',
                'class' => '',
            )
        ),
        'blog_search' => array(
            'callback' => 'render_blog_search',
            'defaults' => array(
                'placeholder' => 'Search blog posts...',
                'button_text' => 'Search',
                'show_filters' => 'false',
                'class' => '',
            )
        ),
        'blog_archive' => array(
            'callback' => 'render_blog_archive',
            'defaults' => array(
                'type' => 'monthly', // monthly, yearly
                'show_post_count' => 'true',
                'format' => 'html', // html, dropdown
                'limit' => 12,
                'class' => '',
            )
        ),
        'author_bio' => array(
            'callback' => 'render_author_bio',
            'defaults' => array(
                'author_id' => '',
                'show_avatar' => 'true',
                'avatar_size' => '80',
                'show_posts_count' => 'true',
                'show_social_links' => 'true',
                'show_bio' => 'true',
                'class' => '',
            )
        ),
        'tags_cloud' => array(
            'callback' => 'render_tags_cloud',
            'defaults' => array(
                'number' => 45,
                'smallest' => '12',
                'largest' => '24',
                'unit' => 'px',
                'format' => 'flat', // flat, list
                'orderby' => 'name',
                'order' => 'ASC',
                'class' => '',
            )
        ),
        'reading_time' => array(
            'callback' => 'render_reading_time',
            'defaults' => array(
                'post_id' => '',
                'words_per_minute' => 200,
                'format' => 'min read',
                'class' => '',
            )
        ),
        'post_navigation' => array(
            'callback' => 'render_post_navigation',
            'defaults' => array(
                'show_thumbnails' => 'true',
                'thumbnail_size' => 'thumbnail',
                'same_category' => 'false',
                'class' => '',
            )
        )
    );

    /**
     * Initialize the shortcodes
     * 
     * @return void
     */
    public function init() {
        $this->register_shortcodes();
        $this->enqueue_assets();
    }

    /**
     * Render blog posts shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_blog_posts($atts) {
        $atts = $this->parse_attributes('blog_posts', $atts);
        
        // Build query arguments
        $query_args = array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($atts['posts_per_page']),
            'post_status' => 'publish',
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );

        // Add taxonomy filters
        if (!empty($atts['category'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'parfume_blog_category',
                'field' => 'slug',
                'terms' => explode(',', $atts['category']),
            );
        }

        if (!empty($atts['tag'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'parfume_blog_tag',
                'field' => 'slug',
                'terms' => explode(',', $atts['tag']),
            );
        }

        if (!empty($atts['author'])) {
            $query_args['author'] = $atts['author'];
        }

        // Handle pagination
        if (filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN)) {
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $query_args['paged'] = $paged;
        }

        $posts = new \WP_Query($query_args);

        if (!$posts->have_posts()) {
            return '<p class="no-posts">' . esc_html__('No blog posts found.', 'parfume-reviews') . '</p>';
        }

        $output = '<div class="blog-posts-grid columns-' . esc_attr($atts['columns']) . ' ' . esc_attr($atts['class']) . '">';

        while ($posts->have_posts()) {
            $posts->the_post();
            $output .= $this->render_blog_post_card($atts);
        }

        $output .= '</div>';

        // Add pagination if enabled
        if (filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN) && $posts->max_num_pages > 1) {
            $output .= '<div class="blog-pagination">';
            $output .= paginate_links(array(
                'total' => $posts->max_num_pages,
                'current' => max(1, get_query_var('paged')),
                'format' => '?paged=%#%',
                'show_all' => false,
                'end_size' => 1,
                'mid_size' => 2,
                'prev_next' => true,
                'prev_text' => '« ' . esc_html__('Previous', 'parfume-reviews'),
                'next_text' => esc_html__('Next', 'parfume-reviews') . ' »',
                'type' => 'plain',
            ));
            $output .= '</div>';
        }

        wp_reset_postdata();

        return $output;
    }

    /**
     * Render individual blog post card
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function render_blog_post_card($atts) {
        $output = '<article class="blog-post-card">';

        // Featured image
        if (has_post_thumbnail()) {
            $output .= '<div class="post-thumbnail">';
            $output .= '<a href="' . get_permalink() . '">';
            $output .= get_the_post_thumbnail(get_the_ID(), $atts['image_size']);
            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '<div class="post-content">';

        // Categories
        if (filter_var($atts['show_categories'], FILTER_VALIDATE_BOOLEAN)) {
            $categories = get_the_terms(get_the_ID(), 'parfume_blog_category');
            if ($categories && !is_wp_error($categories)) {
                $output .= '<div class="post-categories">';
                foreach ($categories as $category) {
                    $output .= '<a href="' . get_term_link($category) . '" class="category-link">';
                    $output .= esc_html($category->name);
                    $output .= '</a>';
                }
                $output .= '</div>';
            }
        }

        // Title
        $output .= '<h3 class="post-title">';
        $output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
        $output .= '</h3>';

        // Meta information
        $output .= '<div class="post-meta">';
        
        if (filter_var($atts['show_author'], FILTER_VALIDATE_BOOLEAN)) {
            $output .= '<span class="post-author">';
            $output .= esc_html__('By', 'parfume-reviews') . ' ';
            $output .= '<a href="' . get_author_posts_url(get_the_author_meta('ID')) . '">';
            $output .= get_the_author();
            $output .= '</a>';
            $output .= '</span>';
        }

        if (filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN)) {
            $output .= '<span class="post-date">';
            $output .= get_the_date();
            $output .= '</span>';
        }

        // Reading time
        $reading_time = $this->calculate_reading_time(get_the_content());
        if ($reading_time > 0) {
            $output .= '<span class="reading-time">';
            $output .= sprintf(esc_html__('%d min read', 'parfume-reviews'), $reading_time);
            $output .= '</span>';
        }

        $output .= '</div>';

        // Excerpt
        if (filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN)) {
            $excerpt_length = intval($atts['excerpt_length']);
            $excerpt = get_the_excerpt();
            if ($excerpt_length > 0 && strlen($excerpt) > $excerpt_length) {
                $excerpt = substr($excerpt, 0, $excerpt_length) . '...';
            }
            $output .= '<div class="post-excerpt">' . esc_html($excerpt) . '</div>';
        }

        $output .= '</div>'; // .post-content
        $output .= '</article>';

        return $output;
    }

    /**
     * Render blog categories shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_blog_categories($atts) {
        $atts = $this->parse_attributes('blog_categories', $atts);

        $terms = get_terms(array(
            'taxonomy' => 'parfume_blog_category',
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        ));

        if (empty($terms) || is_wp_error($terms)) {
            return '<p class="no-categories">' . esc_html__('No categories found.', 'parfume-reviews') . '</p>';
        }

        $output = '<div class="blog-categories columns-' . esc_attr($atts['columns']) . ' ' . esc_attr($atts['class']) . '">';

        foreach ($terms as $term) {
            $output .= '<div class="category-item">';
            $output .= '<a href="' . get_term_link($term) . '" class="category-link">';
            $output .= '<h4 class="category-name">' . esc_html($term->name) . '</h4>';

            if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="category-count">';
                $output .= sprintf(_n('%d post', '%d posts', $term->count, 'parfume-reviews'), $term->count);
                $output .= '</span>';
            }

            if (filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN) && !empty($term->description)) {
                $output .= '<p class="category-description">' . esc_html($term->description) . '</p>';
            }

            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render popular posts shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_popular_posts($atts) {
        $atts = $this->parse_attributes('popular_posts', $atts);

        $query_args = array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($atts['posts_per_page']),
            'post_status' => 'publish',
            'meta_key' => '_post_views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        );

        // Time range filter
        if (intval($atts['time_range']) > 0) {
            $query_args['date_query'] = array(
                array(
                    'after' => intval($atts['time_range']) . ' days ago',
                ),
            );
        }

        $posts = new \WP_Query($query_args);

        if (!$posts->have_posts()) {
            return '<p class="no-posts">' . esc_html__('No popular posts found.', 'parfume-reviews') . '</p>';
        }

        $output = '<div class="popular-posts ' . esc_attr($atts['class']) . '">';

        while ($posts->have_posts()) {
            $posts->the_post();
            $output .= '<article class="popular-post-item">';

            if (has_post_thumbnail()) {
                $output .= '<div class="post-thumbnail">';
                $output .= '<a href="' . get_permalink() . '">';
                $output .= get_the_post_thumbnail(get_the_ID(), $atts['image_size']);
                $output .= '</a>';
                $output .= '</div>';
            }

            $output .= '<div class="post-content">';
            $output .= '<h4 class="post-title">';
            $output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
            $output .= '</h4>';

            $output .= '<div class="post-meta">';
            
            if (filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="post-date">' . get_the_date() . '</span>';
            }

            if (filter_var($atts['show_views'], FILTER_VALIDATE_BOOLEAN)) {
                $views = get_post_meta(get_the_ID(), '_post_views', true);
                if ($views) {
                    $output .= '<span class="post-views">';
                    $output .= sprintf(_n('%d view', '%d views', $views, 'parfume-reviews'), $views);
                    $output .= '</span>';
                }
            }

            $output .= '</div>';

            if (filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<div class="post-excerpt">' . wp_trim_words(get_the_excerpt(), 20) . '</div>';
            }

            $output .= '</div>';
            $output .= '</article>';
        }

        $output .= '</div>';

        wp_reset_postdata();

        return $output;
    }

    /**
     * Render recent posts shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_recent_posts($atts) {
        $atts = $this->parse_attributes('recent_posts', $atts);

        $query_args = array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($atts['posts_per_page']),
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if (!empty($atts['category'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'parfume_blog_category',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['category']),
                ),
            );
        }

        $posts = new \WP_Query($query_args);

        if (!$posts->have_posts()) {
            return '<p class="no-posts">' . esc_html__('No recent posts found.', 'parfume-reviews') . '</p>';
        }

        $output = '<div class="recent-posts ' . esc_attr($atts['class']) . '">';

        while ($posts->have_posts()) {
            $posts->the_post();
            $output .= '<article class="recent-post-item">';

            if (has_post_thumbnail()) {
                $output .= '<div class="post-thumbnail">';
                $output .= '<a href="' . get_permalink() . '">';
                $output .= get_the_post_thumbnail(get_the_ID(), $atts['image_size']);
                $output .= '</a>';
                $output .= '</div>';
            }

            $output .= '<div class="post-content">';
            $output .= '<h4 class="post-title">';
            $output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
            $output .= '</h4>';

            if (filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<div class="post-date">' . get_the_date() . '</div>';
            }

            if (filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN)) {
                $excerpt_length = intval($atts['excerpt_length']) / 5; // rough words estimate
                $output .= '<div class="post-excerpt">' . wp_trim_words(get_the_excerpt(), $excerpt_length) . '</div>';
            }

            $output .= '</div>';
            $output .= '</article>';
        }

        $output .= '</div>';

        wp_reset_postdata();

        return $output;
    }

    /**
     * Render blog search shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_blog_search($atts) {
        $atts = $this->parse_attributes('blog_search', $atts);

        $output = '<div class="blog-search-form ' . esc_attr($atts['class']) . '">';
        $output .= '<form method="get" action="' . esc_url(home_url('/')) . '">';
        $output .= '<input type="hidden" name="post_type" value="parfume_blog">';
        
        $output .= '<div class="search-input-group">';
        $output .= '<input type="text" name="s" placeholder="' . esc_attr($atts['placeholder']) . '" value="' . get_search_query() . '">';
        $output .= '<button type="submit">' . esc_html($atts['button_text']) . '</button>';
        $output .= '</div>';

        // Add filters if enabled
        if (filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN)) {
            $categories = get_terms(array(
                'taxonomy' => 'parfume_blog_category',
                'hide_empty' => true,
            ));

            if (!empty($categories) && !is_wp_error($categories)) {
                $output .= '<div class="search-filters">';
                $output .= '<select name="category">';
                $output .= '<option value="">' . esc_html__('All Categories', 'parfume-reviews') . '</option>';
                
                foreach ($categories as $category) {
                    $selected = (isset($_GET['category']) && $_GET['category'] == $category->slug) ? 'selected' : '';
                    $output .= '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>';
                    $output .= esc_html($category->name);
                    $output .= '</option>';
                }
                
                $output .= '</select>';
                $output .= '</div>';
            }
        }

        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Render blog archive shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_blog_archive($atts) {
        $atts = $this->parse_attributes('blog_archive', $atts);

        $archives = wp_get_archives(array(
            'type' => $atts['type'],
            'limit' => intval($atts['limit']),
            'format' => 'custom',
            'post_type' => 'parfume_blog',
            'echo' => false,
            'show_post_count' => filter_var($atts['show_post_count'], FILTER_VALIDATE_BOOLEAN),
        ));

        if (empty($archives)) {
            return '<p class="no-archives">' . esc_html__('No archives found.', 'parfume-reviews') . '</p>';
        }

        if ($atts['format'] === 'dropdown') {
            $output = '<div class="blog-archive-dropdown ' . esc_attr($atts['class']) . '">';
            $output .= '<select onchange="document.location.href=this.options[this.selectedIndex].value;">';
            $output .= '<option value="">' . esc_html__('Select Archive', 'parfume-reviews') . '</option>';
            $output .= $archives;
            $output .= '</select>';
            $output .= '</div>';
        } else {
            $output = '<div class="blog-archive-list ' . esc_attr($atts['class']) . '">';
            $output .= '<ul>' . $archives . '</ul>';
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Render author bio shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_author_bio($atts) {
        $atts = $this->parse_attributes('author_bio', $atts);

        global $post;
        $author_id = !empty($atts['author_id']) ? intval($atts['author_id']) : get_the_author_meta('ID');

        if (!$author_id) {
            return '';
        }

        $output = '<div class="author-bio ' . esc_attr($atts['class']) . '">';

        if (filter_var($atts['show_avatar'], FILTER_VALIDATE_BOOLEAN)) {
            $output .= '<div class="author-avatar">';
            $output .= get_avatar($author_id, intval($atts['avatar_size']));
            $output .= '</div>';
        }

        $output .= '<div class="author-info">';
        $output .= '<h4 class="author-name">' . get_the_author_meta('display_name', $author_id) . '</h4>';

        if (filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN)) {
            $bio = get_the_author_meta('description', $author_id);
            if (!empty($bio)) {
                $output .= '<div class="author-description">' . wpautop(esc_html($bio)) . '</div>';
            }
        }

        if (filter_var($atts['show_posts_count'], FILTER_VALIDATE_BOOLEAN)) {
            $posts_count = count_user_posts($author_id, 'parfume_blog');
            $output .= '<div class="author-posts-count">';
            $output .= sprintf(_n('%d post', '%d posts', $posts_count, 'parfume-reviews'), $posts_count);
            $output .= '</div>';
        }

        if (filter_var($atts['show_social_links'], FILTER_VALIDATE_BOOLEAN)) {
            $social_links = array(
                'twitter' => get_the_author_meta('twitter', $author_id),
                'facebook' => get_the_author_meta('facebook', $author_id),
                'instagram' => get_the_author_meta('instagram', $author_id),
                'linkedin' => get_the_author_meta('linkedin', $author_id),
            );

            $social_output = '';
            foreach ($social_links as $platform => $url) {
                if (!empty($url)) {
                    $social_output .= '<a href="' . esc_url($url) . '" class="social-link ' . esc_attr($platform) . '" target="_blank">';
                    $social_output .= ucfirst($platform);
                    $social_output .= '</a>';
                }
            }

            if (!empty($social_output)) {
                $output .= '<div class="author-social-links">' . $social_output . '</div>';
            }
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Render tags cloud shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_tags_cloud($atts) {
        $atts = $this->parse_attributes('tags_cloud', $atts);

        $tags = wp_tag_cloud(array(
            'taxonomy' => 'parfume_blog_tag',
            'number' => intval($atts['number']),
            'smallest' => intval($atts['smallest']),
            'largest' => intval($atts['largest']),
            'unit' => $atts['unit'],
            'format' => $atts['format'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'echo' => false,
        ));

        if (empty($tags)) {
            return '<p class="no-tags">' . esc_html__('No tags found.', 'parfume-reviews') . '</p>';
        }

        return '<div class="tags-cloud ' . esc_attr($atts['class']) . '">' . $tags . '</div>';
    }

    /**
     * Render reading time shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_reading_time($atts) {
        $atts = $this->parse_attributes('reading_time', $atts);

        global $post;
        $post_id = !empty($atts['post_id']) ? intval($atts['post_id']) : $post->ID;

        if (!$post_id) {
            return '';
        }

        $content = get_post_field('post_content', $post_id);
        $reading_time = $this->calculate_reading_time($content, intval($atts['words_per_minute']));

        if ($reading_time <= 0) {
            return '';
        }

        return '<span class="reading-time ' . esc_attr($atts['class']) . '">' . 
               sprintf(esc_html($atts['format']), $reading_time) . 
               '</span>';
    }

    /**
     * Render post navigation shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_post_navigation($atts) {
        $atts = $this->parse_attributes('post_navigation', $atts);

        if (!is_single() || get_post_type() !== 'parfume_blog') {
            return '';
        }

        $prev_post = get_previous_post(filter_var($atts['same_category'], FILTER_VALIDATE_BOOLEAN));
        $next_post = get_next_post(filter_var($atts['same_category'], FILTER_VALIDATE_BOOLEAN));

        if (!$prev_post && !$next_post) {
            return '';
        }

        $output = '<div class="post-navigation ' . esc_attr($atts['class']) . '">';

        if ($prev_post) {
            $output .= '<div class="nav-previous">';
            $output .= '<a href="' . get_permalink($prev_post) . '">';
            
            if (filter_var($atts['show_thumbnails'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<div class="nav-thumbnail">';
                $output .= get_the_post_thumbnail($prev_post, $atts['thumbnail_size']);
                $output .= '</div>';
            }
            
            $output .= '<div class="nav-content">';
            $output .= '<span class="nav-label">' . esc_html__('Previous Post', 'parfume-reviews') . '</span>';
            $output .= '<span class="nav-title">' . get_the_title($prev_post) . '</span>';
            $output .= '</div>';
            $output .= '</a>';
            $output .= '</div>';
        }

        if ($next_post) {
            $output .= '<div class="nav-next">';
            $output .= '<a href="' . get_permalink($next_post) . '">';
            
            if (filter_var($atts['show_thumbnails'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<div class="nav-thumbnail">';
                $output .= get_the_post_thumbnail($next_post, $atts['thumbnail_size']);
                $output .= '</div>';
            }
            
            $output .= '<div class="nav-content">';
            $output .= '<span class="nav-label">' . esc_html__('Next Post', 'parfume-reviews') . '</span>';
            $output .= '<span class="nav-title">' . get_the_title($next_post) . '</span>';
            $output .= '</div>';
            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Calculate reading time for content
     * 
     * @param string $content Post content
     * @param int $wpm Words per minute (default: 200)
     * @return int Reading time in minutes
     */
    private function calculate_reading_time($content, $wpm = 200) {
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / $wpm);
        
        return max(1, $reading_time); // At least 1 minute
    }

    /**
     * Enqueue required assets
     * 
     * @return void
     */
    protected function enqueue_assets() {
        if (is_admin()) {
            return;
        }

        wp_enqueue_style(
            'parfume-blog-shortcodes',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/blog.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );

        wp_enqueue_script(
            'parfume-blog-shortcodes',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/blog.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('parfume-blog-shortcodes', 'parfumeBlogShortcodes', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_blog_nonce'),
            'strings' => array(
                'loading' => esc_html__('Loading...', 'parfume-reviews'),
                'error' => esc_html__('Error loading content.', 'parfume-reviews'),
                'no_more_posts' => esc_html__('No more posts to load.', 'parfume-reviews'),
            ),
        ));
    }

    /**
     * AJAX handler for loading more posts
     * 
     * @return void
     */
    public function ajax_load_more_posts() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_blog_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'parfume-reviews')));
            return;
        }

        $page = intval($_POST['page']);
        $shortcode_atts = $_POST['atts'];

        // Sanitize attributes
        $shortcode_atts = array_map('sanitize_text_field', $shortcode_atts);
        $shortcode_atts['pagination'] = 'false'; // Disable pagination for AJAX

        // Build query args from shortcode attributes
        $query_args = array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($shortcode_atts['posts_per_page']),
            'post_status' => 'publish',
            'paged' => $page,
            'orderby' => $shortcode_atts['orderby'],
            'order' => $shortcode_atts['order'],
        );

        $posts = new \WP_Query($query_args);

        if (!$posts->have_posts()) {
            wp_send_json_error(array('message' => esc_html__('No more posts found.', 'parfume-reviews')));
            return;
        }

        $output = '';
        while ($posts->have_posts()) {
            $posts->the_post();
            $output .= $this->render_blog_post_card($shortcode_atts);
        }

        wp_reset_postdata();

        wp_send_json_success(array(
            'html' => $output,
            'max_pages' => $posts->max_num_pages,
            'current_page' => $page,
        ));
    }
}