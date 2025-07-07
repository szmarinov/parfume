<?php
/**
 * Blog модул за блог функционалност
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Blog {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_filter('template_include', array($this, 'blog_template_loader'));
    }
    
    public function init() {
        // Добавяне на rewrite rules за блога
        add_rewrite_rule(
            '^parfiumi/blog/?$',
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        add_rewrite_rule(
            '^parfiumi/blog/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Регистриране на блог post type
     */
    public function register_blog_post_type() {
        $options = get_option('parfume_catalog_settings', array());
        $blog_slug = !empty($options['blog_slug']) ? $options['blog_slug'] : 'parfiumi/blog';
        
        $labels = array(
            'name'                  => __('Блог постове', 'parfume-catalog'),
            'singular_name'         => __('Блог пост', 'parfume-catalog'),
            'menu_name'             => __('Блог', 'parfume-catalog'),
            'name_admin_bar'        => __('Блог пост', 'parfume-catalog'),
            'archives'              => __('Архив на блога', 'parfume-catalog'),
            'attributes'            => __('Атрибути на поста', 'parfume-catalog'),
            'parent_item_colon'     => __('Родителски пост:', 'parfume-catalog'),
            'all_items'             => __('Всички постове', 'parfume-catalog'),
            'add_new_item'          => __('Добави нов пост', 'parfume-catalog'),
            'add_new'               => __('Добави нов', 'parfume-catalog'),
            'new_item'              => __('Нов пост', 'parfume-catalog'),
            'edit_item'             => __('Редактиране на пост', 'parfume-catalog'),
            'update_item'           => __('Обновяване на пост', 'parfume-catalog'),
            'view_item'             => __('Виж пост', 'parfume-catalog'),
            'view_items'            => __('Виж постове', 'parfume-catalog'),
            'search_items'          => __('Търси постове', 'parfume-catalog'),
            'not_found'             => __('Не са намерени постове', 'parfume-catalog'),
            'not_found_in_trash'    => __('Не са намерени постове в кошчето', 'parfume-catalog'),
            'featured_image'        => __('Основно изображение', 'parfume-catalog'),
            'set_featured_image'    => __('Задай основно изображение', 'parfume-catalog'),
            'remove_featured_image' => __('Премахни основното изображение', 'parfume-catalog'),
            'use_featured_image'    => __('Използвай като основно изображение', 'parfume-catalog'),
            'insert_into_item'      => __('Вмъкни в поста', 'parfume-catalog'),
            'uploaded_to_this_item' => __('Качено към този пост', 'parfume-catalog'),
            'items_list'            => __('Списък с постове', 'parfume-catalog'),
            'items_list_navigation' => __('Навигация в списъка', 'parfume-catalog'),
            'filter_items_list'     => __('Филтриране на списъка', 'parfume-catalog'),
        );
        
        $args = array(
            'label'                 => __('Блог пост', 'parfume-catalog'),
            'description'           => __('Блог постове за парфюми', 'parfume-catalog'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false, // Ще се добави ръчно в менюто
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-edit-page',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array(
                'slug' => $blog_slug,
                'with_front' => false,
                'pages' => true,
                'feeds' => true,
            ),
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Template loader за блог страници
     */
    public function blog_template_loader($template) {
        if (is_embed()) {
            return $template;
        }
        
        $default_file = '';
        
        if (is_singular('parfume_blog')) {
            $default_file = 'single-parfume-blog.php';
        } elseif (is_post_type_archive('parfume_blog')) {
            $default_file = 'archive-parfume-blog.php';
        }
        
        if ($default_file) {
            $search_files = array(
                $default_file,
                'parfume-catalog/' . $default_file
            );
            
            $located_template = locate_template($search_files);
            
            if (!$located_template) {
                // Използваме default theme template ако няма custom
                if (is_singular('parfume_blog')) {
                    $located_template = get_single_template();
                } elseif (is_post_type_archive('parfume_blog')) {
                    $located_template = get_archive_template();
                }
            }
            
            if ($located_template) {
                return $located_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Получаване на най-нови блог постове
     */
    public function get_recent_blog_posts($limit = 5) {
        $query = new WP_Query(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $posts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $posts[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date(),
                    'author' => get_the_author(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'categories' => wp_get_post_categories(get_the_ID(), array('fields' => 'names'))
                );
            }
            wp_reset_postdata();
        }
        
        return $posts;
    }
    
    /**
     * Получаване на популярни блог постове
     */
    public function get_popular_blog_posts($limit = 5) {
        $query = new WP_Query(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_key' => '_pc_post_views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ));
        
        $posts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $views = get_post_meta(get_the_ID(), '_pc_post_views', true);
                
                $posts[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date(),
                    'author' => get_the_author(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'views' => intval($views)
                );
            }
            wp_reset_postdata();
        }
        
        return $posts;
    }
    
    /**
     * Получаване на свързани постове
     */
    public function get_related_blog_posts($post_id, $limit = 3) {
        $categories = wp_get_post_categories($post_id);
        
        if (empty($categories)) {
            return array();
        }
        
        $query = new WP_Query(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'post__not_in' => array($post_id),
            'category__in' => $categories,
            'orderby' => 'rand'
        ));
        
        $posts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $posts[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium')
                );
            }
            wp_reset_postdata();
        }
        
        return $posts;
    }
    
    /**
     * Увеличаване на броя прегледи
     */
    public function increment_post_views($post_id) {
        if (get_post_type($post_id) !== 'parfume_blog') {
            return;
        }
        
        $views = get_post_meta($post_id, '_pc_post_views', true);
        $views = $views ? intval($views) + 1 : 1;
        
        update_post_meta($post_id, '_pc_post_views', $views);
    }
    
    /**
     * Рендериране на blog widget
     */
    public function render_blog_widget($title = '', $limit = 5, $show_excerpt = true) {
        $posts = $this->get_recent_blog_posts($limit);
        
        if (empty($posts)) {
            return '';
        }
        
        if (empty($title)) {
            $title = __('Най-нови от блога', 'parfume-catalog');
        }
        
        ob_start();
        ?>
        <div class="pc-blog-widget">
            <h3 class="widget-title"><?php echo esc_html($title); ?></h3>
            
            <div class="blog-posts-list">
                <?php foreach ($posts as $post): ?>
                    <article class="blog-post-item">
                        <?php if (!empty($post['image'])): ?>
                            <div class="post-thumbnail">
                                <a href="<?php echo esc_url($post['url']); ?>">
                                    <img src="<?php echo esc_url($post['image']); ?>" alt="<?php echo esc_attr($post['title']); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h4 class="post-title">
                                <a href="<?php echo esc_url($post['url']); ?>">
                                    <?php echo esc_html($post['title']); ?>
                                </a>
                            </h4>
                            
                            <div class="post-meta">
                                <span class="post-date"><?php echo esc_html($post['date']); ?></span>
                                <span class="post-author"><?php echo sprintf(__('от %s', 'parfume-catalog'), esc_html($post['author'])); ?></span>
                            </div>
                            
                            <?php if ($show_excerpt && !empty($post['excerpt'])): ?>
                                <div class="post-excerpt">
                                    <?php echo wp_trim_words($post['excerpt'], 15); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <div class="widget-footer">
                <a href="<?php echo get_post_type_archive_link('parfume_blog'); ?>" class="view-all-link">
                    <?php _e('Виж всички постове', 'parfume-catalog'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode за блог widget
     */
    public function blog_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Най-нови от блога', 'parfume-catalog'),
            'limit' => 5,
            'show_excerpt' => true
        ), $atts);
        
        return $this->render_blog_widget(
            $atts['title'],
            intval($atts['limit']),
            filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN)
        );
    }
    
    /**
     * Breadcrumbs за блог страници
     */
    public function get_blog_breadcrumbs() {
        $breadcrumbs = array();
        
        // Начало
        $breadcrumbs[] = array(
            'name' => __('Начало', 'parfume-catalog'),
            'url' => home_url()
        );
        
        // Парфюми
        $breadcrumbs[] = array(
            'name' => __('Парфюми', 'parfume-catalog'),
            'url' => get_post_type_archive_link('parfumes')
        );
        
        // Блог
        $breadcrumbs[] = array(
            'name' => __('Блог', 'parfume-catalog'),
            'url' => get_post_type_archive_link('parfume_blog')
        );
        
        // Ако е single post
        if (is_singular('parfume_blog')) {
            $breadcrumbs[] = array(
                'name' => get_the_title(),
                'url' => get_permalink()
            );
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Flush rewrite rules при активиране
     */
    public function flush_blog_rewrite_rules() {
        $this->register_blog_post_type();
        flush_rewrite_rules();
    }
    
    /**
     * SEO оптимизация за блог
     */
    public function optimize_blog_seo() {
        if (is_singular('parfume_blog')) {
            add_action('wp_head', array($this, 'add_blog_post_schema'));
        } elseif (is_post_type_archive('parfume_blog')) {
            add_action('wp_head', array($this, 'add_blog_archive_schema'));
        }
    }
    
    /**
     * Schema за блог пост
     */
    public function add_blog_post_schema() {
        global $post;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author()
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            )
        );
        
        $image = get_the_post_thumbnail_url($post->ID, 'large');
        if ($image) {
            $schema['image'] = $image;
        }
        
        // Категории като keywords
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        if (!empty($categories)) {
            $schema['keywords'] = implode(', ', $categories);
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        echo '</script>' . "\n";
    }
    
    /**
     * Schema за блог архив
     */
    public function add_blog_archive_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => __('Блог за парфюми', 'parfume-catalog'),
            'description' => __('Статии, съвети и новини за парфюми и аромати', 'parfume-catalog'),
            'url' => get_post_type_archive_link('parfume_blog'),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            )
        );
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        echo '</script>' . "\n";
    }
    
    /**
     * Автоматично извикване на view counter
     */
    public function auto_increment_views() {
        if (is_singular('parfume_blog')) {
            $this->increment_post_views(get_the_ID());
        }
    }
    
    /**
     * Добавяне на blog функционалност към init
     */
    public function init_blog_features() {
        // Регистриране на shortcode
        add_shortcode('pc_blog_widget', array($this, 'blog_widget_shortcode'));
        
        // SEO оптимизация
        $this->optimize_blog_seo();
        
        // View counter
        add_action('wp', array($this, 'auto_increment_views'));
    }
}