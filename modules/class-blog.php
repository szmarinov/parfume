<?php
/**
 * Parfume Catalog Blog Module
 * 
 * Блог функционалност с отделни постове под /parfiumi/blog/
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Blog {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('admin_menu', array($this, 'add_blog_admin_pages'));
        add_filter('template_include', array($this, 'blog_template_include'));
        add_action('pre_get_posts', array($this, 'modify_blog_query'));
        add_filter('post_type_link', array($this, 'custom_blog_permalink'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_blog_assets'));
        add_filter('body_class', array($this, 'add_blog_body_classes'));
        add_action('wp_head', array($this, 'add_blog_schema'));
        add_filter('document_title_parts', array($this, 'customize_blog_title'));
        add_action('init', array($this, 'add_blog_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_blog_query_vars'));
        add_action('wp_loaded', array($this, 'flush_rewrite_rules_if_needed'));
    }

    /**
     * Регистриране на Blog Post Type
     */
    public function register_blog_post_type() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';

        $labels = array(
            'name'                  => _x('Блог постове', 'Post type general name', 'parfume-catalog'),
            'singular_name'         => _x('Блог пост', 'Post type singular name', 'parfume-catalog'),
            'menu_name'             => _x('Блог', 'Admin Menu text', 'parfume-catalog'),
            'name_admin_bar'        => _x('Блог пост', 'Add New on Toolbar', 'parfume-catalog'),
            'add_new'               => __('Добави нов', 'parfume-catalog'),
            'add_new_item'          => __('Добави нов блог пост', 'parfume-catalog'),
            'new_item'              => __('Нов блог пост', 'parfume-catalog'),
            'edit_item'             => __('Редактирай блог пост', 'parfume-catalog'),
            'view_item'             => __('Виж блог пост', 'parfume-catalog'),
            'all_items'             => __('Всички блог постове', 'parfume-catalog'),
            'search_items'          => __('Търси блог постове', 'parfume-catalog'),
            'parent_item_colon'     => __('Родителски блог пост:', 'parfume-catalog'),
            'not_found'             => __('Няма намерени блог постове.', 'parfume-catalog'),
            'not_found_in_trash'    => __('Няма намерени блог постове в кошчето.', 'parfume-catalog'),
            'featured_image'        => _x('Основно изображение', 'Overrides the "Featured Image" phrase', 'parfume-catalog'),
            'set_featured_image'    => _x('Задай основно изображение', 'Overrides the "Set featured image" phrase', 'parfume-catalog'),
            'remove_featured_image' => _x('Премахни основното изображение', 'Overrides the "Remove featured image" phrase', 'parfume-catalog'),
            'use_featured_image'    => _x('Използвай като основно изображение', 'Overrides the "Use as featured image" phrase', 'parfume-catalog'),
            'archives'              => _x('Архив блог', 'The post type archive label used in nav menus', 'parfume-catalog'),
            'insert_into_item'      => _x('Вмъкни в блог пост', 'Overrides the "Insert into post"/"Insert into page" phrase', 'parfume-catalog'),
            'uploaded_to_this_item' => _x('Качени към този блог пост', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'parfume-catalog'),
            'filter_items_list'     => _x('Филтрирай списък блог постове', 'Screen reader text for the filter links', 'parfume-catalog'),
            'items_list_navigation' => _x('Навигация списък блог постове', 'Screen reader text for the pagination', 'parfume-catalog'),
            'items_list'            => _x('Списък блог постове', 'Screen reader text for the items list', 'parfume-catalog'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // Ще показваме в custom menu
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'query_var'          => true,
            'rewrite'            => false, // Ще използваме custom rewrite rules
            'capability_type'    => 'post',
            'has_archive'        => false, // Ще използваме custom archive
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-edit-large',
            'supports'           => array(
                'title', 
                'editor', 
                'thumbnail', 
                'excerpt', 
                'comments', 
                'trackbacks', 
                'revisions', 
                'author', 
                'page-attributes',
                'custom-fields'
            ),
            'show_in_rest'       => true, // За Gutenberg поддръжка
            'rest_base'          => 'parfume-blog',
        );

        register_post_type('parfume_blog', $args);
    }

    /**
     * Добавяне на custom rewrite rules
     */
    public function add_blog_rewrite_rules() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';

        // Archive страница за блога
        add_rewrite_rule(
            '^' . $archive_slug . '/blog/?$',
            'index.php?post_type=parfume_blog&parfume_blog_archive=1',
            'top'
        );

        // Pagination за архива
        add_rewrite_rule(
            '^' . $archive_slug . '/blog/page/([0-9]+)/?$',
            'index.php?post_type=parfume_blog&parfume_blog_archive=1&paged=$matches[1]',
            'top'
        );

        // Single блог постове
        add_rewrite_rule(
            '^' . $archive_slug . '/blog/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
    }

    /**
     * Добавяне на query vars
     */
    public function add_blog_query_vars($query_vars) {
        $query_vars[] = 'parfume_blog_archive';
        return $query_vars;
    }

    /**
     * Flush rewrite rules ако е нужно
     */
    public function flush_rewrite_rules_if_needed() {
        if (get_option('parfume_blog_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('parfume_blog_flush_rewrite_rules');
        }
    }

    /**
     * Добавяне на админ страници
     */
    public function add_blog_admin_pages() {
        // Тези страници вече са добавени в главния admin клас
        // Тук можем да добавим допълнителни специфични за блога
        
        add_submenu_page(
            'parfume-catalog',
            __('Blog Settings', 'parfume-catalog'),
            __('Blog Settings', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-blog-settings',
            array($this, 'blog_settings_page')
        );
    }

    /**
     * Blog settings страница
     */
    public function blog_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_blog_settings();
        }

        $settings = $this->get_blog_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('Blog Settings', 'parfume-catalog'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('parfume_blog_settings', 'parfume_blog_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="posts_per_page"><?php _e('Постове на страница', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="posts_per_page" name="posts_per_page" 
                                   value="<?php echo esc_attr($settings['posts_per_page']); ?>" 
                                   min="1" max="50" />
                            <p class="description"><?php _e('Брой блог постове на архивната страница', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="excerpt_length"><?php _e('Дължина на откъса', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="excerpt_length" name="excerpt_length" 
                                   value="<?php echo esc_attr($settings['excerpt_length']); ?>" 
                                   min="10" max="100" />
                            <p class="description"><?php _e('Брой думи в откъса на архивната страница', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="show_author"><?php _e('Показвай автор', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="show_author" name="show_author" value="1" 
                                       <?php checked($settings['show_author'], 1); ?> />
                                <?php _e('Показвай информация за автора на блог постовете', 'parfume-catalog'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="show_date"><?php _e('Показвай дата', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="show_date" name="show_date" value="1" 
                                       <?php checked($settings['show_date'], 1); ?> />
                                <?php _e('Показвай дата на публикуване', 'parfume-catalog'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="enable_comments"><?php _e('Активирай коментари', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_comments" name="enable_comments" value="1" 
                                       <?php checked($settings['enable_comments'], 1); ?> />
                                <?php _e('Позволи WordPress коментари за блог постовете', 'parfume-catalog'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="related_parfumes_count"><?php _e('Свързани парфюми', 'parfume-catalog'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="related_parfumes_count" name="related_parfumes_count" 
                                   value="<?php echo esc_attr($settings['related_parfumes_count']); ?>" 
                                   min="0" max="12" />
                            <p class="description"><?php _e('Брой свързани парфюми за показване в края на постовете (0 = изключено)', 'parfume-catalog'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Запази настройки', 'parfume-catalog')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Запазване на blog настройки
     */
    private function save_blog_settings() {
        if (!wp_verify_nonce($_POST['parfume_blog_settings_nonce'], 'parfume_blog_settings')) {
            return;
        }

        $settings = array(
            'posts_per_page' => absint($_POST['posts_per_page']),
            'excerpt_length' => absint($_POST['excerpt_length']),
            'show_author' => isset($_POST['show_author']) ? 1 : 0,
            'show_date' => isset($_POST['show_date']) ? 1 : 0,
            'enable_comments' => isset($_POST['enable_comments']) ? 1 : 0,
            'related_parfumes_count' => absint($_POST['related_parfumes_count'])
        );

        update_option('parfume_blog_settings', $settings);
        
        echo '<div class="notice notice-success"><p>' . __('Настройките са запазени успешно.', 'parfume-catalog') . '</p></div>';
    }

    /**
     * Получаване на blog настройки
     */
    private function get_blog_settings() {
        $defaults = array(
            'posts_per_page' => 10,
            'excerpt_length' => 30,
            'show_author' => 1,
            'show_date' => 1,
            'enable_comments' => 1,
            'related_parfumes_count' => 4
        );

        $settings = get_option('parfume_blog_settings', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Template include за блог страници
     */
    public function blog_template_include($template) {
        if (get_query_var('parfume_blog_archive')) {
            // Archive template за блога
            $blog_archive_template = $this->locate_blog_template('archive-blog.php');
            if ($blog_archive_template) {
                return $blog_archive_template;
            }
        } elseif (is_singular('parfume_blog')) {
            // Single template за блог пост
            $blog_single_template = $this->locate_blog_template('single-blog.php');
            if ($blog_single_template) {
                return $blog_single_template;
            }
        }

        return $template;
    }

    /**
     * Локализиране на blog template
     */
    private function locate_blog_template($template_name) {
        // Първо търси в темата
        $theme_template = locate_template(array(
            'parfume-catalog/blog-templates/' . $template_name,
            'blog-templates/' . $template_name,
            $template_name
        ));

        if ($theme_template) {
            return $theme_template;
        }

        // След това в плъгина
        $plugin_template = PARFUME_CATALOG_PLUGIN_DIR . 'templates/blog-templates/' . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Модификация на blog query
     */
    public function modify_blog_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (get_query_var('parfume_blog_archive')) {
                $settings = $this->get_blog_settings();
                
                $query->set('post_type', 'parfume_blog');
                $query->set('posts_per_page', $settings['posts_per_page']);
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
                
                // Задаване на is_home false за да не конфликтира
                $query->is_home = false;
                $query->is_archive = true;
            }
        }
    }

    /**
     * Custom permalink за блог постове
     */
    public function custom_blog_permalink($permalink, $post) {
        if ($post->post_type == 'parfume_blog') {
            $options = get_option('parfume_catalog_options', array());
            $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
            
            $permalink = home_url($archive_slug . '/blog/' . $post->post_name . '/');
        }
        
        return $permalink;
    }

    /**
     * Enqueue на blog assets
     */
    public function enqueue_blog_assets() {
        if (is_singular('parfume_blog') || get_query_var('parfume_blog_archive')) {
            // Използваме същите стилове като за основните постове
            wp_enqueue_style('parfume-catalog-blog', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/blog.css', 
                array('parfume-catalog-frontend'), 
                PARFUME_CATALOG_VERSION
            );

            // JavaScript за blog функционалност
            wp_enqueue_script('parfume-catalog-blog', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/blog.js', 
                array('jquery'), 
                PARFUME_CATALOG_VERSION, 
                true
            );

            wp_localize_script('parfume-catalog-blog', 'parfume_blog_config', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_blog_nonce'),
                'settings' => $this->get_blog_settings(),
                'texts' => array(
                    'read_more' => __('Прочети повече', 'parfume-catalog'),
                    'share_article' => __('Сподели статията', 'parfume-catalog'),
                    'related_articles' => __('Свързани статии', 'parfume-catalog'),
                    'related_parfumes' => __('Свързани парфюми', 'parfume-catalog')
                )
            ));
        }
    }

    /**
     * Добавяне на body classes
     */
    public function add_blog_body_classes($classes) {
        if (get_query_var('parfume_blog_archive')) {
            $classes[] = 'parfume-blog-archive';
            $classes[] = 'archive';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'parfume-blog-single';
            $classes[] = 'single';
        }

        return $classes;
    }

    /**
     * Blog Schema markup
     */
    public function add_blog_schema() {
        if (is_singular('parfume_blog')) {
            $this->add_blog_article_schema();
        } elseif (get_query_var('parfume_blog_archive')) {
            $this->add_blog_collection_schema();
        }
    }

    /**
     * Article Schema за single блог пост
     */
    private function add_blog_article_schema() {
        global $post;

        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => $this->get_blog_post_description($post->ID),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author)
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url()
                )
            )
        );

        // Основно изображение
        $thumbnail_id = get_post_thumbnail_id();
        if ($thumbnail_id) {
            $image_data = wp_get_attachment_image_src($thumbnail_id, 'large');
            if ($image_data) {
                $schema_data['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2]
                );
            }
        }

        // Категория/Tags ако има
        $schema_data['articleSection'] = 'Парфюми';

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Collection Schema за блог архив
     */
    private function add_blog_collection_schema() {
        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => __('Блог за парфюми', 'parfume-catalog'),
            'description' => __('Последни статии и новини за парфюми, ароматни тенденции и съвети.', 'parfume-catalog'),
            'url' => $this->get_blog_archive_url()
        );

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Customization на blog titles
     */
    public function customize_blog_title($title_parts) {
        if (get_query_var('parfume_blog_archive')) {
            $title_parts['title'] = __('Блог за парфюми', 'parfume-catalog');
            $title_parts['site'] = get_bloginfo('name');
        } elseif (is_singular('parfume_blog')) {
            // Оставяме default поведението за single постове
        }

        return $title_parts;
    }

    /**
     * Helper функции
     */
    private function get_blog_post_description($post_id) {
        $post = get_post($post_id);
        
        if ($post->post_excerpt) {
            return $post->post_excerpt;
        }
        
        return wp_trim_words($post->post_content, 25);
    }

    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            return $logo_data ? $logo_data[0] : '';
        }
        
        return get_template_directory_uri() . '/assets/images/logo.png';
    }

    private function get_blog_archive_url() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        return home_url($archive_slug . '/blog/');
    }

    /**
     * Получаване на свързани парфюми за блог пост
     */
    public static function get_related_parfumes($post_id, $limit = 4) {
        // Може да се базира на keywords в съдържанието или custom мета полета
        $content = get_post_field('post_content', $post_id);
        $title = get_post_field('post_title', $post_id);
        
        // Търсене за споменати марки в съдържанието
        $brands = get_terms(array(
            'taxonomy' => 'parfume_marki',
            'hide_empty' => true
        ));

        $mentioned_brands = array();
        foreach ($brands as $brand) {
            if (stripos($content . ' ' . $title, $brand->name) !== false) {
                $mentioned_brands[] = $brand->term_id;
            }
        }

        if (!empty($mentioned_brands)) {
            $query_args = array(
                'post_type' => 'parfumes',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'parfume_marki',
                        'field' => 'term_id',
                        'terms' => $mentioned_brands,
                        'operator' => 'IN'
                    )
                ),
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'compare' => 'EXISTS'
                    )
                ),
                'orderby' => 'rand'
            );

            $related_query = new WP_Query($query_args);
            $related_parfumes = $related_query->posts;
            wp_reset_postdata();

            return $related_parfumes;
        }

        // Fallback - най-нови парфюми
        $query_args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $fallback_query = new WP_Query($query_args);
        $fallback_parfumes = $fallback_query->posts;
        wp_reset_postdata();

        return $fallback_parfumes;
    }

    /**
     * Получаване на блог статистики
     */
    public static function get_blog_stats() {
        $stats = array();
        
        // Общ брой публикувани постове
        $published_posts = wp_count_posts('parfume_blog');
        $stats['published_count'] = $published_posts->publish;
        
        // Брой чернови
        $stats['draft_count'] = $published_posts->draft;
        
        // Най-нова публикация
        $latest_post = get_posts(array(
            'post_type' => 'parfume_blog',
            'post_status' => 'publish',
            'numberposts' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $stats['latest_post_date'] = !empty($latest_post) ? $latest_post[0]->post_date : null;
        
        return $stats;
    }

    /**
     * Получаване на популярни блог постове
     */
    public static function get_popular_blog_posts($limit = 5) {
        // Базирано на WordPress post views или коментари
        $query_args = array(
            'post_type' => 'parfume_blog',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => '_post_views', // Ако използваме post views tracking
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        );

        // Fallback към comments count ако няма post views
        if (!get_posts(array('post_type' => 'parfume_blog', 'meta_key' => '_post_views', 'numberposts' => 1))) {
            $query_args = array(
                'post_type' => 'parfume_blog',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'comment_count',
                'order' => 'DESC'
            );
        }

        $popular_query = new WP_Query($query_args);
        $popular_posts = $popular_query->posts;
        wp_reset_postdata();

        return $popular_posts;
    }

    /**
     * Проследяване на post views (опционално)
     */
    public static function track_post_view($post_id) {
        if (is_singular('parfume_blog')) {
            $views = get_post_meta($post_id, '_post_views', true);
            $views = $views ? intval($views) + 1 : 1;
            update_post_meta($post_id, '_post_views', $views);
        }
    }
}