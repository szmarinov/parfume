<?php
/**
 * Блог функционалност
 * 
 * @package Parfume_Catalog
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Blog {
    
    /**
     * Инициализация
     */
    public function __construct() {
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('template_redirect', array($this, 'template_redirect'));
        add_filter('post_type_link', array($this, 'custom_blog_permalink'), 10, 2);
        add_action('wp_loaded', array($this, 'flush_rewrite_rules_if_needed'));
    }
    
    /**
     * Регистриране на блог post type
     */
    public function register_blog_post_type() {
        $slug = get_option('parfume_catalog_blog_slug', 'parfiumi');
        
        $labels = array(
            'name' => 'Блог статии',
            'singular_name' => 'Блог статия',
            'menu_name' => 'Блог',
            'add_new' => 'Добави статия',
            'add_new_item' => 'Добави нова статия',
            'edit_item' => 'Редактирай статия',
            'new_item' => 'Нова статия',
            'view_item' => 'Виж статия',
            'search_items' => 'Търси статии',
            'not_found' => 'Няма намерени статии',
            'not_found_in_trash' => 'Няма статии в кошчето',
            'all_items' => 'Всички статии',
            'archives' => 'Архив статии',
            'insert_into_item' => 'Вмъкни в статия',
            'uploaded_to_this_item' => 'Качено към тази статия',
            'filter_items_list' => 'Филтрирай списъка със статии',
            'items_list_navigation' => 'Навигация в списъка със статии',
            'items_list' => 'Списък със статии',
        );
        
        $args = array(
            'label' => 'Блог статии',
            'labels' => $labels,
            'description' => 'Блог статии за парфюми',
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Ще добавим през admin_menu
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $slug,
                'with_front' => false,
            ),
            'capability_type' => 'post',
            'has_archive' => $slug,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-edit-page',
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
            'taxonomies' => array('post_tag'), // Само тагове, без категории
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавяне на админ меню
     */
    public function add_admin_menu() {
        // Главно меню за блога
        add_menu_page(
            'Блог',
            'Блог',
            'edit_posts',
            'edit.php?post_type=parfume_blog',
            '',
            'dashicons-edit-page',
            26
        );
        
        // Подменю за всички статии
        add_submenu_page(
            'edit.php?post_type=parfume_blog',
            'Всички статии',
            'Всички статии',
            'edit_posts',
            'edit.php?post_type=parfume_blog'
        );
        
        // Подменю за добавяне на нова статия
        add_submenu_page(
            'edit.php?post_type=parfume_blog',
            'Добави статия',
            'Добави статия',
            'edit_posts',
            'post-new.php?post_type=parfume_blog'
        );
        
        // Подменю за тагове
        add_submenu_page(
            'edit.php?post_type=parfume_blog',
            'Тагове',
            'Тагове',
            'manage_categories',
            'edit-tags.php?taxonomy=post_tag&post_type=parfume_blog'
        );
    }
    
    /**
     * Пренасочване на шаблони
     */
    public function template_redirect() {
        if (is_singular('parfume_blog')) {
            $this->load_blog_single_template();
        } elseif (is_post_type_archive('parfume_blog')) {
            $this->load_blog_archive_template();
        }
    }
    
    /**
     * Зареждане на single шаблон за блог
     */
    private function load_blog_single_template() {
        // Използваме стандартния single.php от темата
        $template = locate_template(array('single.php'));
        
        if ($template) {
            // Модифицираме query за да работи правилно
            add_filter('body_class', array($this, 'add_blog_body_class'));
            add_filter('post_class', array($this, 'add_blog_post_class'));
            
            include $template;
            exit;
        }
    }
    
    /**
     * Зареждане на архивен шаблон за блог
     */
    private function load_blog_archive_template() {
        // Използваме archive.php или index.php от темата
        $templates = array('archive.php', 'index.php');
        $template = locate_template($templates);
        
        if ($template) {
            // Модифицираме заглавието на архива
            add_filter('get_the_archive_title', array($this, 'modify_blog_archive_title'));
            add_filter('get_the_archive_description', array($this, 'modify_blog_archive_description'));
            add_filter('body_class', array($this, 'add_blog_archive_body_class'));
            
            include $template;
            exit;
        }
    }
    
    /**
     * Добавяне на CSS класове за single блог
     */
    public function add_blog_body_class($classes) {
        $classes[] = 'parfume-blog-single';
        return $classes;
    }
    
    /**
     * Добавяне на CSS класове за пост
     */
    public function add_blog_post_class($classes) {
        $classes[] = 'parfume-blog-post';
        return $classes;
    }
    
    /**
     * Добавяне на CSS класове за архив
     */
    public function add_blog_archive_body_class($classes) {
        $classes[] = 'parfume-blog-archive';
        return $classes;
    }
    
    /**
     * Модифициране на заглавието на архива
     */
    public function modify_blog_archive_title($title) {
        if (is_post_type_archive('parfume_blog')) {
            return 'Блог за парфюми';
        }
        return $title;
    }
    
    /**
     * Модифициране на описанието на архива
     */
    public function modify_blog_archive_description($description) {
        if (is_post_type_archive('parfume_blog')) {
            return 'Интересни статии, новини и съвети за парфюми';
        }
        return $description;
    }
    
    /**
     * Персонализиране на permalink за блог постове
     */
    public function custom_blog_permalink($post_link, $post) {
        if ($post->post_type === 'parfume_blog') {
            $slug = get_option('parfume_catalog_blog_slug', 'parfiumi');
            return home_url($slug . '/' . $post->post_name . '/');
        }
        return $post_link;
    }
    
    /**
     * Flush rewrite rules ако е необходимо
     */
    public function flush_rewrite_rules_if_needed() {
        if (get_option('parfume_catalog_blog_flush_rewrite', false)) {
            flush_rewrite_rules();
            delete_option('parfume_catalog_blog_flush_rewrite');
        }
    }
    
    /**
     * Получаване на последни блог постове
     */
    public static function get_recent_posts($limit = 5) {
        return get_posts(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    }
    
    /**
     * Получаване на свързани блог постове
     */
    public static function get_related_posts($post_id, $limit = 3) {
        $tags = wp_get_post_tags($post_id);
        
        if (empty($tags)) {
            return self::get_recent_posts($limit);
        }
        
        $tag_ids = array();
        foreach ($tags as $tag) {
            $tag_ids[] = $tag->term_id;
        }
        
        return get_posts(array(
            'post_type' => 'parfume_blog',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'post__not_in' => array($post_id),
            'tag__in' => $tag_ids,
            'orderby' => 'rand'
        ));
    }
    
    /**
     * Shortcode за показване на последни блог постове
     */
    public static function recent_posts_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'show_excerpt' => true,
            'show_date' => true,
            'show_author' => false
        ), $atts);
        
        $posts = self::get_recent_posts($atts['limit']);
        
        if (empty($posts)) {
            return '<p>Няма блог статии.</p>';
        }
        
        $output = '<div class="parfume-blog-recent-posts">';
        
        foreach ($posts as $post) {
            setup_postdata($post);
            
            $output .= '<div class="parfume-blog-recent-post">';
            $output .= '<h3><a href="' . get_permalink($post->ID) . '">' . get_the_title($post->ID) . '</a></h3>';
            
            if ($atts['show_date']) {
                $output .= '<div class="post-date">' . get_the_date('', $post->ID) . '</div>';
            }
            
            if ($atts['show_author']) {
                $output .= '<div class="post-author">От: ' . get_the_author_meta('display_name', $post->post_author) . '</div>';
            }
            
            if ($atts['show_excerpt']) {
                $output .= '<div class="post-excerpt">' . get_the_excerpt($post->ID) . '</div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    /**
     * Генериране на бърз RSS feed за блога
     */
    public static function generate_blog_feed() {
        add_action('do_feed_parfume_blog', array(__CLASS__, 'output_blog_feed'));
        add_feed('parfume_blog', array(__CLASS__, 'output_blog_feed'));
    }
    
    /**
     * Извеждане на RSS feed
     */
    public static function output_blog_feed() {
        header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset'), true);
        
        $posts = self::get_recent_posts(10);
        
        echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>';
        ?>
        <rss version="2.0">
            <channel>
                <title><?php echo get_bloginfo('name'); ?> - Блог за парфюми</title>
                <link><?php echo home_url(); ?></link>
                <description>Блог статии за парфюми</description>
                <language><?php echo get_option('rss_language'); ?></language>
                
                <?php foreach ($posts as $post): setup_postdata($post); ?>
                <item>
                    <title><?php echo get_the_title(); ?></title>
                    <link><?php echo get_permalink(); ?></link>
                    <description><![CDATA[<?php echo get_the_excerpt(); ?>]]></description>
                    <pubDate><?php echo get_the_date('r'); ?></pubDate>
                    <guid><?php echo get_permalink(); ?></guid>
                </item>
                <?php endforeach; wp_reset_postdata(); ?>
            </channel>
        </rss>
        <?php
        exit;
    }
}

// Инициализация на модула
new Parfume_Catalog_Blog();

// Регистриране на shortcode
add_shortcode('parfume_recent_blog_posts', array('Parfume_Catalog_Blog', 'recent_posts_shortcode'));