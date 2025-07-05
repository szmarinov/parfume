<?php
/**
 * Blog Post Type
 * 
 * @package Parfume_Reviews
 * @subpackage PostTypes
 */

namespace Parfume_Reviews\PostTypes;

use Parfume_Reviews\Utils\Post_Type_Base;
use Parfume_Reviews\Utils\Helpers;

/**
 * Blog Post Type Class
 * 
 * Manages blog functionality for the Parfume Reviews plugin
 */
class Blog extends Post_Type_Base {
    
    /**
     * Post type name
     *
     * @var string
     */
    protected $post_type = 'parfume_blog';
    
    /**
     * Initialize the blog post type
     * FIXED: Changed from protected to public to match base class
     */
    public function init() {
        $this->register_post_type();
        $this->register_hooks();
        $this->register_meta_boxes();
    }
    
    /**
     * Get post type configuration
     */
    protected function get_post_type_args() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Blog Posts', 'parfume-reviews'),
            'singular_name' => __('Blog Post', 'parfume-reviews'),
            'menu_name' => __('Blog Posts', 'parfume-reviews'),
            'name_admin_bar' => __('Blog Post', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Blog Post', 'parfume-reviews'),
            'new_item' => __('New Blog Post', 'parfume-reviews'),
            'edit_item' => __('Edit Blog Post', 'parfume-reviews'),
            'view_item' => __('View Blog Post', 'parfume-reviews'),
            'all_items' => __('All Blog Posts', 'parfume-reviews'),
            'search_items' => __('Search Blog Posts', 'parfume-reviews'),
            'not_found' => __('No blog posts found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No blog posts found in Trash.', 'parfume-reviews'),
            'featured_image' => __('Featured Image', 'parfume-reviews'),
            'set_featured_image' => __('Set featured image', 'parfume-reviews'),
            'remove_featured_image' => __('Remove featured image', 'parfume-reviews'),
            'use_featured_image' => __('Use as featured image', 'parfume-reviews'),
            'archives' => __('Blog Archives', 'parfume-reviews'),
            'insert_into_item' => __('Insert into blog post', 'parfume-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this blog post', 'parfume-reviews'),
            'filter_items_list' => __('Filter blog posts list', 'parfume-reviews'),
            'items_list_navigation' => __('Blog posts list navigation', 'parfume-reviews'),
            'items_list' => __('Blog posts list', 'parfume-reviews'),
        );
        
        return array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add it to parfume menu
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/blog',
                'with_front' => false,
            ),
            'capability_type' => 'post',
            'has_archive' => $parfume_slug . '/blog',
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'comments',
                'author',
                'custom-fields',
                'revisions',
                'page-attributes',
            ),
            'show_in_rest' => true,
            'rest_base' => 'parfume-blog',
            'taxonomies' => array('category', 'post_tag'),
            'menu_icon' => 'dashicons-admin-post',
            'description' => __('Blog posts for the Parfume Reviews website', 'parfume-reviews'),
        );
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Admin hooks
        add_action('manage_' . $this->post_type . '_posts_columns', array($this, 'admin_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'admin_column_content'), 10, 2);
        add_filter('manage_edit-' . $this->post_type . '_sortable_columns', array($this, 'sortable_columns'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('single_template', array($this, 'load_single_template'));
        add_filter('archive_template', array($this, 'load_archive_template'));
        
        // Meta hooks
        add_action('save_post_' . $this->post_type, array($this, 'save_meta_data'), 10, 2);
        
        // View counting
        add_action('wp_head', array($this, 'track_post_views'));
        
        // RSS feed
        add_action('init', array($this, 'add_to_main_feed'));
        
        // Excerpt filter
        add_filter('excerpt_length', array($this, 'blog_excerpt_length'), 999);
        add_filter('excerpt_more', array($this, 'blog_excerpt_more'));
    }
    
    /**
     * Register meta boxes
     */
    protected function register_meta_boxes() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes_callback'));
    }
    
    /**
     * Add meta boxes callback
     */
    public function add_meta_boxes_callback() {
        // Blog Settings Meta Box
        add_meta_box(
            'blog_settings',
            __('Blog Settings', 'parfume-reviews'),
            array($this, 'render_blog_settings_meta_box'),
            $this->post_type,
            'side',
            'default'
        );
        
        // Featured Content Meta Box
        add_meta_box(
            'featured_content',
            __('Featured Content', 'parfume-reviews'),
            array($this, 'render_featured_content_meta_box'),
            $this->post_type,
            'normal',
            'default'
        );
        
        // SEO Meta Box
        add_meta_box(
            'blog_seo',
            __('SEO Settings', 'parfume-reviews'),
            array($this, 'render_seo_meta_box'),
            $this->post_type,
            'normal',
            'low'
        );
    }
    
    /**
     * Render blog settings meta box
     */
    public function render_blog_settings_meta_box($post) {
        wp_nonce_field('blog_settings_nonce', 'blog_settings_nonce');
        
        $is_featured = get_post_meta($post->ID, '_blog_is_featured', true);
        $reading_time = get_post_meta($post->ID, '_blog_reading_time', true);
        $subtitle = get_post_meta($post->ID, '_blog_subtitle', true);
        $layout = get_post_meta($post->ID, '_blog_layout', true);
        $show_related = get_post_meta($post->ID, '_blog_show_related', true);
        
        ?>
        <table class="form-table">
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="blog_is_featured" value="1" <?php checked($is_featured, 1); ?>>
                        <?php _e('Featured Post', 'parfume-reviews'); ?>
                    </label>
                    <p class="description"><?php _e('Mark this post as featured', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="blog_subtitle"><?php _e('Subtitle', 'parfume-reviews'); ?></label>
                    <input type="text" id="blog_subtitle" name="blog_subtitle" value="<?php echo esc_attr($subtitle); ?>" class="widefat">
                    <p class="description"><?php _e('Optional subtitle for the post', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="blog_reading_time"><?php _e('Reading Time (minutes)', 'parfume-reviews'); ?></label>
                    <input type="number" id="blog_reading_time" name="blog_reading_time" value="<?php echo esc_attr($reading_time); ?>" min="1" max="60" class="small-text">
                    <p class="description"><?php _e('Estimated reading time in minutes (auto-calculated if empty)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="blog_layout"><?php _e('Post Layout', 'parfume-reviews'); ?></label>
                    <select id="blog_layout" name="blog_layout" class="widefat">
                        <option value="standard" <?php selected($layout, 'standard'); ?>><?php _e('Standard', 'parfume-reviews'); ?></option>
                        <option value="featured" <?php selected($layout, 'featured'); ?>><?php _e('Featured', 'parfume-reviews'); ?></option>
                        <option value="minimal" <?php selected($layout, 'minimal'); ?>><?php _e('Minimal', 'parfume-reviews'); ?></option>
                        <option value="wide" <?php selected($layout, 'wide'); ?>><?php _e('Wide', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="blog_show_related" value="1" <?php checked($show_related, 1); ?>>
                        <?php _e('Show Related Posts', 'parfume-reviews'); ?>
                    </label>
                    <p class="description"><?php _e('Show related posts at the end of this post', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render featured content meta box
     */
    public function render_featured_content_meta_box($post) {
        wp_nonce_field('featured_content_nonce', 'featured_content_nonce');
        
        $featured_parfumes = get_post_meta($post->ID, '_blog_featured_parfumes', true);
        $call_to_action = get_post_meta($post->ID, '_blog_call_to_action', true);
        $cta_url = get_post_meta($post->ID, '_blog_cta_url', true);
        
        if (!is_array($featured_parfumes)) {
            $featured_parfumes = array();
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Featured Parfumes', 'parfume-reviews'); ?></th>
                <td>
                    <?php
                    $parfumes = get_posts(array(
                        'post_type' => 'parfume',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    ?>
                    <select name="blog_featured_parfumes[]" multiple size="8" class="widefat">
                        <?php foreach ($parfumes as $parfume): ?>
                            <option value="<?php echo $parfume->ID; ?>" <?php echo in_array($parfume->ID, $featured_parfumes) ? 'selected' : ''; ?>>
                                <?php echo esc_html($parfume->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select parfumes to feature in this post. Hold Ctrl/Cmd for multiple selection.', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Call to Action', 'parfume-reviews'); ?></th>
                <td>
                    <input type="text" name="blog_call_to_action" value="<?php echo esc_attr($call_to_action); ?>" class="widefat">
                    <p class="description"><?php _e('Text for the call-to-action button', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('CTA URL', 'parfume-reviews'); ?></th>
                <td>
                    <input type="url" name="blog_cta_url" value="<?php echo esc_url($cta_url); ?>" class="widefat">
                    <p class="description"><?php _e('URL for the call-to-action button', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render SEO meta box
     */
    public function render_seo_meta_box($post) {
        wp_nonce_field('blog_seo_nonce', 'blog_seo_nonce');
        
        $meta_description = get_post_meta($post->ID, '_blog_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_blog_focus_keyword', true);
        $canonical_url = get_post_meta($post->ID, '_blog_canonical_url', true);
        $noindex = get_post_meta($post->ID, '_blog_noindex', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Meta Description', 'parfume-reviews'); ?></th>
                <td>
                    <textarea name="blog_meta_description" rows="3" class="widefat"><?php echo esc_textarea($meta_description); ?></textarea>
                    <p class="description"><?php _e('Meta description for search engines (max 160 characters)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Focus Keyword', 'parfume-reviews'); ?></th>
                <td>
                    <input type="text" name="blog_focus_keyword" value="<?php echo esc_attr($focus_keyword); ?>" class="widefat">
                    <p class="description"><?php _e('Main keyword for SEO optimization', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Canonical URL', 'parfume-reviews'); ?></th>
                <td>
                    <input type="url" name="blog_canonical_url" value="<?php echo esc_url($canonical_url); ?>" class="widefat">
                    <p class="description"><?php _e('Canonical URL (leave empty for auto-generation)', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Search Engine Visibility', 'parfume-reviews'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="blog_noindex" value="1" <?php checked($noindex, 1); ?>>
                        <?php _e('Discourage search engines from indexing this post', 'parfume-reviews'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta data
     */
    public function save_meta_data($post_id, $post) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save blog settings
        if (isset($_POST['blog_settings_nonce']) && wp_verify_nonce($_POST['blog_settings_nonce'], 'blog_settings_nonce')) {
            $is_featured = isset($_POST['blog_is_featured']) ? 1 : 0;
            update_post_meta($post_id, '_blog_is_featured', $is_featured);
            
            if (isset($_POST['blog_subtitle'])) {
                update_post_meta($post_id, '_blog_subtitle', sanitize_text_field($_POST['blog_subtitle']));
            }
            
            if (isset($_POST['blog_reading_time']) && is_numeric($_POST['blog_reading_time'])) {
                update_post_meta($post_id, '_blog_reading_time', intval($_POST['blog_reading_time']));
            } else {
                // Auto-calculate reading time
                $content = get_post_field('post_content', $post_id);
                $word_count = str_word_count(wp_strip_all_tags($content));
                $reading_time = max(1, ceil($word_count / 200)); // 200 words per minute
                update_post_meta($post_id, '_blog_reading_time', $reading_time);
            }
            
            if (isset($_POST['blog_layout'])) {
                update_post_meta($post_id, '_blog_layout', sanitize_text_field($_POST['blog_layout']));
            }
            
            $show_related = isset($_POST['blog_show_related']) ? 1 : 0;
            update_post_meta($post_id, '_blog_show_related', $show_related);
        }
        
        // Save featured content
        if (isset($_POST['featured_content_nonce']) && wp_verify_nonce($_POST['featured_content_nonce'], 'featured_content_nonce')) {
            if (isset($_POST['blog_featured_parfumes']) && is_array($_POST['blog_featured_parfumes'])) {
                $featured_parfumes = array_map('intval', $_POST['blog_featured_parfumes']);
                update_post_meta($post_id, '_blog_featured_parfumes', $featured_parfumes);
            } else {
                delete_post_meta($post_id, '_blog_featured_parfumes');
            }
            
            if (isset($_POST['blog_call_to_action'])) {
                update_post_meta($post_id, '_blog_call_to_action', sanitize_text_field($_POST['blog_call_to_action']));
            }
            
            if (isset($_POST['blog_cta_url'])) {
                update_post_meta($post_id, '_blog_cta_url', esc_url_raw($_POST['blog_cta_url']));
            }
        }
        
        // Save SEO data
        if (isset($_POST['blog_seo_nonce']) && wp_verify_nonce($_POST['blog_seo_nonce'], 'blog_seo_nonce')) {
            if (isset($_POST['blog_meta_description'])) {
                update_post_meta($post_id, '_blog_meta_description', sanitize_textarea_field($_POST['blog_meta_description']));
            }
            
            if (isset($_POST['blog_focus_keyword'])) {
                update_post_meta($post_id, '_blog_focus_keyword', sanitize_text_field($_POST['blog_focus_keyword']));
            }
            
            if (isset($_POST['blog_canonical_url'])) {
                update_post_meta($post_id, '_blog_canonical_url', esc_url_raw($_POST['blog_canonical_url']));
            }
            
            $noindex = isset($_POST['blog_noindex']) ? 1 : 0;
            update_post_meta($post_id, '_blog_noindex', $noindex);
        }
        
        // Update view count (initialize to 0 for new posts)
        if (get_post_meta($post_id, '_blog_view_count', true) === '') {
            update_post_meta($post_id, '_blog_view_count', 0);
        }
    }
    
    /**
     * Admin columns
     */
    public function admin_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            
            if ($key === 'title') {
                $new_columns['featured'] = __('Featured', 'parfume-reviews');
                $new_columns['reading_time'] = __('Reading Time', 'parfume-reviews');
                $new_columns['views'] = __('Views', 'parfume-reviews');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Admin column content
     */
    public function admin_column_content($column, $post_id) {
        switch ($column) {
            case 'featured':
                $is_featured = get_post_meta($post_id, '_blog_is_featured', true);
                if ($is_featured) {
                    echo '<span class="dashicons dashicons-star-filled" title="' . __('Featured Post', 'parfume-reviews') . '" style="color: #ffb900;"></span>';
                } else {
                    echo '<span class="dashicons dashicons-star-empty" style="color: #ccc;"></span>';
                }
                break;
                
            case 'reading_time':
                $reading_time = get_post_meta($post_id, '_blog_reading_time', true);
                if ($reading_time) {
                    printf(__('%d min', 'parfume-reviews'), $reading_time);
                } else {
                    echo '—';
                }
                break;
                
            case 'views':
                $view_count = get_post_meta($post_id, '_blog_view_count', true);
                echo intval($view_count);
                break;
        }
    }
    
    /**
     * Sortable columns
     */
    public function sortable_columns($columns) {
        $columns['views'] = 'views';
        $columns['reading_time'] = 'reading_time';
        return $columns;
    }
    
    /**
     * Track post views
     */
    public function track_post_views() {
        if (is_singular($this->post_type) && !is_user_logged_in()) {
            global $post;
            if ($post) {
                $current_views = get_post_meta($post->ID, '_blog_view_count', true);
                $current_views = intval($current_views) + 1;
                update_post_meta($post->ID, '_blog_view_count', $current_views);
            }
        }
    }
    
    /**
     * Add to main RSS feed
     */
    public function add_to_main_feed() {
        add_action('pre_get_posts', function($query) {
            if ($query->is_main_query() && $query->is_feed()) {
                $post_types = $query->get('post_type');
                if (empty($post_types)) {
                    $post_types = array('post');
                }
                if (!is_array($post_types)) {
                    $post_types = array($post_types);
                }
                $post_types[] = $this->post_type;
                $query->set('post_type', $post_types);
            }
        });
    }
    
    /**
     * Custom excerpt length for blog posts
     */
    public function blog_excerpt_length($length) {
        if (is_admin() || !is_singular($this->post_type)) {
            return $length;
        }
        return 30;
    }
    
    /**
     * Custom excerpt more text
     */
    public function blog_excerpt_more($more) {
        if (is_admin() || !is_singular($this->post_type)) {
            return $more;
        }
        return '...';
    }
    
    /**
     * Load single template
     */
    public function load_single_template($template) {
        global $post;
        
        if ($post->post_type === $this->post_type) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Load archive template
     */
    public function load_archive_template($template) {
        if (is_post_type_archive($this->post_type)) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_singular($this->post_type) || is_post_type_archive($this->post_type)) {
            wp_enqueue_style(
                'parfume-blog-style',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/blog.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-blog-script',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/blog.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-blog-script', 'parfumeBlog', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_blog_nonce'),
            ));
        }
    }
    
    /**
     * Get blog statistics
     */
    public static function get_statistics() {
        $stats = array(
            'total_posts' => 0,
            'published_posts' => 0,
            'featured_posts' => 0,
            'total_views' => 0,
            'average_reading_time' => 0,
            'recent_posts' => array(),
        );
        
        try {
            // Total posts
            $total_posts = wp_count_posts('parfume_blog');
            $stats['total_posts'] = $total_posts->publish + $total_posts->draft + $total_posts->private;
            $stats['published_posts'] = $total_posts->publish;
            
            // Featured posts
            $featured_query = new \WP_Query(array(
                'post_type' => 'parfume_blog',
                'meta_key' => '_blog_is_featured',
                'meta_value' => '1',
                'posts_per_page' => -1,
                'fields' => 'ids',
            ));
            $stats['featured_posts'] = $featured_query->found_posts;
            
            // Total views and reading time
            global $wpdb;
            $view_results = $wpdb->get_results("
                SELECT 
                    SUM(CAST(meta_value AS UNSIGNED)) as total_views,
                    COUNT(*) as post_count
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_blog_view_count' 
                AND p.post_type = 'parfume_blog'
                AND p.post_status = 'publish'
            ");
            
            if (!empty($view_results)) {
                $stats['total_views'] = intval($view_results[0]->total_views);
            }
            
            // Average reading time
            $reading_time_results = $wpdb->get_var("
                SELECT AVG(CAST(meta_value AS UNSIGNED))
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_blog_reading_time' 
                AND p.post_type = 'parfume_blog'
                AND p.post_status = 'publish'
                AND CAST(meta_value AS UNSIGNED) > 0
            ");
            
            $stats['average_reading_time'] = round(floatval($reading_time_results), 1);
            
            // Recent posts
            $recent_posts = get_posts(array(
                'post_type' => 'parfume_blog',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC',
            ));
            
            foreach ($recent_posts as $post) {
                $stats['recent_posts'][] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'date' => get_the_date('', $post->ID),
                    'url' => get_permalink($post->ID),
                    'views' => intval(get_post_meta($post->ID, '_blog_view_count', true)),
                );
            }
            
        } catch (Exception $e) {
            // Log error but return default stats
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Blog statistics error: ' . $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * Get featured posts
     */
    public static function get_featured_posts($limit = 5) {
        return get_posts(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'meta_key' => '_blog_is_featured',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
        ));
    }
    
    /**
     * Get popular posts by views
     */
    public static function get_popular_posts($limit = 5) {
        return get_posts(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'meta_key' => '_blog_view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        ));
    }
    
    /**
     * Get related posts
     */
    public static function get_related_posts($post_id, $limit = 4) {
        $categories = wp_get_post_categories($post_id);
        $tags = wp_get_post_tags($post_id, array('fields' => 'ids'));
        
        $args = array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
        );
        
        if (!empty($categories) || !empty($tags)) {
            $args['tax_query'] = array(
                'relation' => 'OR',
            );
            
            if (!empty($categories)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => $categories,
                );
            }
            
            if (!empty($tags)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'post_tag',
                    'field' => 'term_id',
                    'terms' => $tags,
                );
            }
        }
        
        return get_posts($args);
    }
}