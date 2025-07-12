<?php
namespace Parfume_Reviews;

class Blog {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_filter('template_include', array($this, 'template_loader'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('pre_get_posts', array($this, 'modify_blog_query'));
        
        // Add blog to main menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add meta boxes for blog posts
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        
        // Add shortcodes
        add_shortcode('parfume_blog_recent', array($this, 'recent_posts_shortcode'));
        add_shortcode('parfume_blog_featured', array($this, 'featured_posts_shortcode'));
    }
    
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $blog_slug = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
        
        $labels = array(
            'name' => __('Blog Posts', 'parfume-reviews'),
            'singular_name' => __('Blog Post', 'parfume-reviews'),
            'menu_name' => __('Blog', 'parfume-reviews'),
            'name_admin_bar' => __('Blog Post', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Blog Post', 'parfume-reviews'),
            'new_item' => __('New Blog Post', 'parfume-reviews'),
            'edit_item' => __('Edit Blog Post', 'parfume-reviews'),
            'view_item' => __('View Blog Post', 'parfume-reviews'),
            'all_items' => __('All Blog Posts', 'parfume-reviews'),
            'search_items' => __('Search Blog Posts', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Blog Posts:', 'parfume-reviews'),
            'not_found' => __('No blog posts found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No blog posts found in Trash.', 'parfume-reviews')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Ще добавим като submenu
            'query_var' => true,
            'rewrite' => array('slug' => $blog_slug, 'with_front' => false),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'author', 'custom-fields'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-post',
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    public function add_admin_menu() {
        // Добавяме Blog като submenu под Parfumes
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Blog Posts', 'parfume-reviews'),
            __('Blog', 'parfume-reviews'),
            'edit_posts',
            'edit.php?post_type=parfume_blog'
        );
        
        // Добавяме "Add New Blog Post"
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Add New Blog Post', 'parfume-reviews'),
            __('Add New Blog Post', 'parfume-reviews'),
            'edit_posts',
            'post-new.php?post_type=parfume_blog'
        );
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'blog_featured',
            __('Featured Post Settings', 'parfume-reviews'),
            array($this, 'render_featured_meta_box'),
            'parfume_blog',
            'side',
            'default'
        );
        
        add_meta_box(
            'blog_related_parfumes',
            __('Related Parfumes', 'parfume-reviews'),
            array($this, 'render_related_parfumes_meta_box'),
            'parfume_blog',
            'normal',
            'default'
        );
    }
    
    public function render_featured_meta_box($post) {
        wp_nonce_field('blog_featured_nonce', 'blog_featured_nonce');
        
        $is_featured = get_post_meta($post->ID, '_blog_featured', true);
        $featured_order = get_post_meta($post->ID, '_blog_featured_order', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="blog_featured" value="1" <?php checked($is_featured, '1'); ?>>
                <?php _e('Mark as Featured Post', 'parfume-reviews'); ?>
            </label>
        </p>
        
        <p>
            <label for="blog_featured_order"><?php _e('Featured Order (lower numbers appear first):', 'parfume-reviews'); ?></label>
            <input type="number" id="blog_featured_order" name="blog_featured_order" value="<?php echo esc_attr($featured_order); ?>" min="0" step="1" class="small-text">
        </p>
        <?php
    }
    
    public function render_related_parfumes_meta_box($post) {
        wp_nonce_field('blog_related_parfumes_nonce', 'blog_related_parfumes_nonce');
        
        $related_parfumes = get_post_meta($post->ID, '_blog_related_parfumes', true);
        $related_parfumes = is_array($related_parfumes) ? $related_parfumes : array();
        
        // Get all parfumes
        $all_parfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <p><?php _e('Select parfumes related to this blog post:', 'parfume-reviews'); ?></p>
        
        <div class="related-parfumes-checklist" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
            <?php foreach ($all_parfumes as $parfume): ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="blog_related_parfumes[]" value="<?php echo esc_attr($parfume->ID); ?>" <?php checked(in_array($parfume->ID, $related_parfumes)); ?>>
                    <?php echo esc_html($parfume->post_title); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the post type
        if (get_post_type($post_id) !== 'parfume_blog') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save featured settings
        if (isset($_POST['blog_featured_nonce']) && wp_verify_nonce($_POST['blog_featured_nonce'], 'blog_featured_nonce')) {
            $is_featured = isset($_POST['blog_featured']) ? '1' : '0';
            update_post_meta($post_id, '_blog_featured', $is_featured);
            
            if (isset($_POST['blog_featured_order'])) {
                $featured_order = intval($_POST['blog_featured_order']);
                update_post_meta($post_id, '_blog_featured_order', $featured_order);
            }
        }
        
        // Save related parfumes
        if (isset($_POST['blog_related_parfumes_nonce']) && wp_verify_nonce($_POST['blog_related_parfumes_nonce'], 'blog_related_parfumes_nonce')) {
            if (isset($_POST['blog_related_parfumes']) && is_array($_POST['blog_related_parfumes'])) {
                $related_parfumes = array_map('intval', $_POST['blog_related_parfumes']);
                update_post_meta($post_id, '_blog_related_parfumes', $related_parfumes);
            } else {
                delete_post_meta($post_id, '_blog_related_parfumes');
            }
        }
    }
    
    public function modify_blog_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume_blog')) {
                // Set posts per page
                $query->set('posts_per_page', 10);
                
                // Set default ordering (newest first)
                if (!$query->get('orderby')) {
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                }
            }
        }
    }
    
    public function template_loader($template) {
        // Използва дизайна от активната WordPress тема
        if (is_singular('parfume_blog')) {
            // Търси single-parfume_blog.php в темата, след това single.php
            $theme_template = locate_template(array('single-parfume_blog.php', 'single.php'));
            if ($theme_template) {
                return $theme_template;
            }
            
            // Fallback към plugin template ако няма в темата
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume_blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            // Търси archive-parfume_blog.php в темата, след това archive.php
            $theme_template = locate_template(array('archive-parfume_blog.php', 'archive.php'));
            if ($theme_template) {
                return $theme_template;
            }
            
            // Fallback към plugin template ако няма в темата
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume_blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume_blog') || is_post_type_archive('parfume_blog')) {
            // Използва стиловете на активната тема
            // Не зареждаме допълнителни CSS файлове, освен ако не е необходимо
            
            wp_enqueue_script(
                'parfume-blog',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/blog.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-blog', 'parfumeBlog', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-blog-nonce'),
            ));
        }
    }
    
    /**
     * Shortcode за последни blog постове
     */
    public function recent_posts_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 3,
            'show_excerpt' => true,
            'show_date' => true,
            'show_author' => false,
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $recent_posts = new \WP_Query($args);
        
        if (!$recent_posts->have_posts()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-blog-recent">
            <h3><?php _e('Latest Blog Posts', 'parfume-reviews'); ?></h3>
            
            <div class="blog-posts-grid">
                <?php while ($recent_posts->have_posts()): $recent_posts->the_post(); ?>
                    <article class="blog-post-item">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h4 class="post-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <?php if ($atts['show_date'] || $atts['show_author']): ?>
                                <div class="post-meta">
                                    <?php if ($atts['show_date']): ?>
                                        <span class="post-date"><?php echo get_the_date(); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($atts['show_author']): ?>
                                        <span class="post-author"><?php _e('by', 'parfume-reviews'); ?> <?php the_author(); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_excerpt']): ?>
                                <div class="post-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>
                            
                            <a href="<?php the_permalink(); ?>" class="read-more">
                                <?php _e('Read More', 'parfume-reviews'); ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Shortcode за featured blog постове
     */
    public function featured_posts_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 3,
            'show_excerpt' => true,
            'show_date' => true,
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_blog_featured',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'meta_key' => '_blog_featured_order',
            'orderby' => 'meta_value_num date',
            'order' => 'ASC'
        );
        
        $featured_posts = new \WP_Query($args);
        
        if (!$featured_posts->have_posts()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-blog-featured">
            <h3><?php _e('Featured Blog Posts', 'parfume-reviews'); ?></h3>
            
            <div class="blog-posts-grid featured">
                <?php while ($featured_posts->have_posts()): $featured_posts->the_post(); ?>
                    <article class="blog-post-item featured">
                        <div class="featured-badge">
                            <?php _e('Featured', 'parfume-reviews'); ?>
                        </div>
                        
                        <?php if (has_post_thumbnail()): ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('large'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h4 class="post-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <?php if ($atts['show_date']): ?>
                                <div class="post-meta">
                                    <span class="post-date"><?php echo get_the_date(); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_excerpt']): ?>
                                <div class="post-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>
                            
                            <a href="<?php the_permalink(); ?>" class="read-more featured-link">
                                <?php _e('Read Featured Post', 'parfume-reviews'); ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Get related parfumes for blog post
     */
    public static function get_related_parfumes($post_id) {
        $related_parfumes = get_post_meta($post_id, '_blog_related_parfumes', true);
        
        if (empty($related_parfumes) || !is_array($related_parfumes)) {
            return '';
        }
        
        $args = array(
            'post_type' => 'parfume',
            'post__in' => $related_parfumes,
            'posts_per_page' => -1,
            'orderby' => 'post__in'
        );
        
        $parfumes = new \WP_Query($args);
        
        if (!$parfumes->have_posts()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="blog-related-parfumes">
            <h3><?php _e('Related Parfumes', 'parfume-reviews'); ?></h3>
            
            <div class="related-parfumes-grid">
                <?php while ($parfumes->have_posts()): $parfumes->the_post(); ?>
                    <div class="related-parfume-item">
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <div class="parfume-thumbnail">
                                    <?php the_post_thumbnail('thumbnail'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <h4 class="parfume-title"><?php the_title(); ?></h4>
                            
                            <?php
                            $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                            if (!empty($rating) && is_numeric($rating)):
                            ?>
                                <div class="parfume-rating">
                                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        return ob_get_clean();
    }
}