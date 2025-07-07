<?php
/**
 * Блог функционалност
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Blog {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        add_filter('post_type_link', array($this, 'custom_blog_permalink'), 10, 2);
        add_action('pre_get_posts', array($this, 'modify_blog_query'));
    }
    
    public function init() {
        $this->register_blog_post_type();
        $this->add_rewrite_rules();
    }
    
    /**
     * Регистриране на blog post type
     */
    private function register_blog_post_type() {
        $settings = get_option('parfume_catalog_settings', array());
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
        
        $labels = array(
            'name' => __('Блог', 'parfume-catalog'),
            'singular_name' => __('Блог пост', 'parfume-catalog'),
            'menu_name' => __('Блог', 'parfume-catalog'),
            'add_new' => __('Добави нов', 'parfume-catalog'),
            'add_new_item' => __('Добави нов блог пост', 'parfume-catalog'),
            'edit_item' => __('Редактирай блог пост', 'parfume-catalog'),
            'new_item' => __('Нов блог пост', 'parfume-catalog'),
            'view_item' => __('Преглед на блог пост', 'parfume-catalog'),
            'search_items' => __('Търси блог постове', 'parfume-catalog'),
            'not_found' => __('Няма намерени блог постове', 'parfume-catalog'),
            'not_found_in_trash' => __('Няма блог постове в кошчето', 'parfume-catalog'),
            'all_items' => __('Всички блог постове', 'parfume-catalog'),
            'archives' => __('Архив блог', 'parfume-catalog'),
            'insert_into_item' => __('Вмъкни в блог пост', 'parfume-catalog'),
            'uploaded_to_this_item' => __('Качено към този блог пост', 'parfume-catalog'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Ще добавим собствено меню
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'parfiumi/' . $blog_slug,
                'with_front' => false
            ),
            'capability_type' => 'post',
            'has_archive' => 'parfiumi/' . $blog_slug,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-welcome-write-blog',
            'supports' => array(
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'comments',
                'trackbacks',
                'revisions',
                'custom-fields',
                'page-attributes',
                'post-formats'
            ),
            'show_in_rest' => true, // За Gutenberg
            'rest_base' => 'parfume-blog',
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавяне на rewrite правила
     */
    private function add_rewrite_rules() {
        $settings = get_option('parfume_catalog_settings', array());
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
        
        // Правило за архив
        add_rewrite_rule(
            '^parfiumi/' . $blog_slug . '/?$',
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        // Правило за пагинация
        add_rewrite_rule(
            '^parfiumi/' . $blog_slug . '/page/([0-9]{1,})/?$',
            'index.php?post_type=parfume_blog&paged=$matches[1]',
            'top'
        );
        
        // Правило за единичен пост
        add_rewrite_rule(
            '^parfiumi/' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Добавяне на админ меню
     */
    public function add_admin_menu() {
        // Главно меню за блог
        add_submenu_page(
            'parfume-catalog',
            __('Блог', 'parfume-catalog'),
            __('Блог', 'parfume-catalog'),
            'manage_options',
            'edit.php?post_type=parfume_blog'
        );
        
        // Подменю за добавяне на нов пост
        add_submenu_page(
            'parfume-catalog',
            __('Добави блог пост', 'parfume-catalog'),
            __('Добави блог пост', 'parfume-catalog'),
            'manage_options',
            'post-new.php?post_type=parfume_blog'
        );
    }
    
    /**
     * Персонализиране на permalink за блог постове
     */
    public function custom_blog_permalink($post_link, $post) {
        if ($post->post_type === 'parfume_blog') {
            $settings = get_option('parfume_catalog_settings', array());
            $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
            
            return home_url('/parfiumi/' . $blog_slug . '/' . $post->post_name . '/');
        }
        
        return $post_link;
    }
    
    /**
     * Модификация на blog заявката
     */
    public function modify_blog_query($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if (is_post_type_archive('parfume_blog')) {
            // Брой постове на страница
            $query->set('posts_per_page', 10);
            
            // Сортиране по дата
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }
    
    /**
     * Получаване на URL за архива на блога
     */
    public static function get_blog_archive_url() {
        $settings = get_option('parfume_catalog_settings', array());
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
        
        return home_url('/parfiumi/' . $blog_slug . '/');
    }
    
    /**
     * Рендериране на blog секция в настройките
     */
    public function render_blog_section() {
        $settings = get_option('parfume_catalog_settings', array());
        $blog_slug = !empty($settings['blog_slug']) ? $settings['blog_slug'] : 'blog';
        ?>
        <div class="pc-blog-settings">
            <h3><?php _e('Настройки на блога', 'parfume-catalog'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="blog_slug"><?php _e('URL slug за блога', 'parfume-catalog'); ?></label>
                    </th>
                    <td>
                        <code>yoursite.com/parfiumi/</code>
                        <input type="text" 
                               id="blog_slug" 
                               name="parfume_catalog_settings[blog_slug]" 
                               value="<?php echo esc_attr($blog_slug); ?>" 
                               class="regular-text" />
                        <code>/</code>
                        <p class="description">
                            <?php _e('URL структурата за блог постовете. По подразбиране: blog', 'parfume-catalog'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Активност', 'parfume-catalog'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="parfume_catalog_settings[blog_enabled]" 
                                   value="1" 
                                   <?php checked(!empty($settings['blog_enabled'])); ?> />
                            <?php _e('Включи блог функционалността', 'parfume-catalog'); ?>
                        </label>
                        <p class="description">
                            <?php _e('При изключване блог менюто и страници ще се скрият', 'parfume-catalog'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Архивна страница', 'parfume-catalog'); ?></th>
                    <td>
                        <a href="<?php echo self::get_blog_archive_url(); ?>" target="_blank" class="button">
                            <?php _e('Прегледай архива', 'parfume-catalog'); ?>
                        </a>
                        <p class="description">
                            <?php _e('Линк към архивната страница на блога', 'parfume-catalog'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <h4><?php _e('Последни блог постове', 'parfume-catalog'); ?></h4>
            
            <?php
            $recent_posts = get_posts(array(
                'post_type' => 'parfume_blog',
                'posts_per_page' => 5,
                'post_status' => 'publish'
            ));
            
            if (!empty($recent_posts)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Заглавие', 'parfume-catalog'); ?></th>
                            <th><?php _e('Автор', 'parfume-catalog'); ?></th>
                            <th><?php _e('Дата', 'parfume-catalog'); ?></th>
                            <th><?php _e('Статус', 'parfume-catalog'); ?></th>
                            <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_posts as $blog_post): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($blog_post->ID); ?>">
                                            <?php echo esc_html($blog_post->post_title); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo get_the_author_meta('display_name', $blog_post->post_author); ?>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($blog_post->post_date)); ?>
                                </td>
                                <td>
                                    <span class="post-status-<?php echo $blog_post->post_status; ?>">
                                        <?php echo ucfirst($blog_post->post_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($blog_post->ID); ?>" class="button button-small">
                                        <?php _e('Редактирай', 'parfume-catalog'); ?>
                                    </a>
                                    <a href="<?php echo get_permalink($blog_post->ID); ?>" target="_blank" class="button button-small">
                                        <?php _e('Преглед', 'parfume-catalog'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=parfume_blog'); ?>" class="button">
                        <?php _e('Виж всички блог постове', 'parfume-catalog'); ?>
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=parfume_blog'); ?>" class="button button-primary">
                        <?php _e('Добави нов блог пост', 'parfume-catalog'); ?>
                    </a>
                </p>
                
            <?php else: ?>
                <p><?php _e('Няма създадени блог постове.', 'parfume-catalog'); ?></p>
                <p>
                    <a href="<?php echo admin_url('post-new.php?post_type=parfume_blog'); ?>" class="button button-primary">
                        <?php _e('Създай първия си блог пост', 'parfume-catalog'); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <style>
            .post-status-publish {
                color: #46b450;
                font-weight: 600;
            }
            
            .post-status-draft {
                color: #999;
                font-style: italic;
            }
            
            .post-status-private {
                color: #d63638;
                font-weight: 600;
            }
            
            .post-status-pending {
                color: #dba617;
                font-weight: 600;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Флъш на rewrite правилата при активиране
     */
    public static function flush_rewrite_rules() {
        // Регистриране на post type временно
        $blog = new self();
        $blog->register_blog_post_type();
        
        // Флъш на правилата
        flush_rewrite_rules();
    }
    
    /**
     * Получаване на статистики за блога
     */
    public static function get_blog_stats() {
        $posts_count = wp_count_posts('parfume_blog');
        
        return array(
            'total' => $posts_count->publish + $posts_count->draft + $posts_count->private,
            'published' => $posts_count->publish,
            'draft' => $posts_count->draft,
            'private' => $posts_count->private,
            'pending' => $posts_count->pending ?? 0
        );
    }
    
    /**
     * Shortcode за показване на последни блог постове
     */
    public static function recent_blog_posts_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 3,
            'show_excerpt' => 'true',
            'show_date' => 'true',
            'show_author' => 'false'
        ), $atts);
        
        $posts = get_posts(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => intval($atts['count']),
            'post_status' => 'publish'
        ));
        
        if (empty($posts)) {
            return '<p>' . __('Няма блог постове за показване.', 'parfume-catalog') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="pc-recent-blog-posts">
            <?php foreach ($posts as $post): ?>
                <div class="pc-blog-post-item">
                    <?php if (has_post_thumbnail($post->ID)): ?>
                        <div class="pc-blog-post-thumbnail">
                            <a href="<?php echo get_permalink($post->ID); ?>">
                                <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="pc-blog-post-content">
                        <h3 class="pc-blog-post-title">
                            <a href="<?php echo get_permalink($post->ID); ?>">
                                <?php echo esc_html($post->post_title); ?>
                            </a>
                        </h3>
                        
                        <div class="pc-blog-post-meta">
                            <?php if ($atts['show_date'] === 'true'): ?>
                                <span class="pc-blog-post-date">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($post->post_date)); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_author'] === 'true'): ?>
                                <span class="pc-blog-post-author">
                                    <?php _e('от', 'parfume-catalog'); ?> 
                                    <?php echo get_the_author_meta('display_name', $post->post_author); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($atts['show_excerpt'] === 'true'): ?>
                            <div class="pc-blog-post-excerpt">
                                <?php echo wp_trim_words(get_the_excerpt($post), 20); ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php echo get_permalink($post->ID); ?>" class="pc-blog-read-more">
                            <?php _e('Прочети повече', 'parfume-catalog'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .pc-recent-blog-posts {
            display: grid;
            gap: 20px;
        }
        
        .pc-blog-post-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
        }
        
        .pc-blog-post-thumbnail {
            flex-shrink: 0;
        }
        
        .pc-blog-post-thumbnail img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .pc-blog-post-content {
            flex: 1;
        }
        
        .pc-blog-post-title {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .pc-blog-post-title a {
            text-decoration: none;
            color: #333;
        }
        
        .pc-blog-post-title a:hover {
            color: #007cba;
        }
        
        .pc-blog-post-meta {
            margin-bottom: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .pc-blog-post-meta span {
            margin-right: 15px;
        }
        
        .pc-blog-post-excerpt {
            margin-bottom: 10px;
            color: #555;
            line-height: 1.5;
        }
        
        .pc-blog-read-more {
            color: #007cba;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        
        .pc-blog-read-more:hover {
            color: #005a87;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .pc-blog-post-item {
                flex-direction: column;
            }
            
            .pc-blog-post-thumbnail img {
                width: 100%;
                height: 200px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Регистриране на shortcode
add_shortcode('pc_recent_blog_posts', array('Parfume_Catalog_Blog', 'recent_blog_posts_shortcode'));