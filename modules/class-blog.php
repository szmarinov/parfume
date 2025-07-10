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
        
        // AJAX handlers
        add_action('wp_ajax_get_related_parfumes', array($this, 'ajax_get_related_parfumes'));
        add_action('wp_ajax_nopriv_get_related_parfumes', array($this, 'ajax_get_related_parfumes'));
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
        $blog_slug = isset($options['blog_slug']) ? $options['blog_slug'] : 'blog';

        // Blog archive rule
        add_rewrite_rule(
            '^' . $archive_slug . '/' . $blog_slug . '/?$',
            'index.php?parfume_blog_archive=1',
            'top'
        );

        // Blog archive pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/' . $blog_slug . '/page/([0-9]+)/?$',
            'index.php?parfume_blog_archive=1&paged=$matches[1]',
            'top'
        );

        // Single blog post rule
        add_rewrite_rule(
            '^' . $archive_slug . '/' . $blog_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );

        // Comments pagination for blog posts
        add_rewrite_rule(
            '^' . $archive_slug . '/' . $blog_slug . '/([^/]+)/comment-page-([0-9]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]&cpage=$matches[2]',
            'top'
        );
    }

    /**
     * Добавяне на blog query vars
     */
    public function add_blog_query_vars($vars) {
        $vars[] = 'parfume_blog_archive';
        return $vars;
    }

    /**
     * Flush rewrite rules при нужда
     */
    public function flush_rewrite_rules_if_needed() {
        if (get_option('parfume_blog_rewrite_rules_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('parfume_blog_rewrite_rules_flushed', '1');
        }
    }

    /**
     * Добавяне на Blog админ страници
     */
    public function add_blog_admin_pages() {
        // Главна Blog страница
        add_submenu_page(
            'parfume-catalog',
            __('Блог', 'parfume-catalog'),
            __('Блог', 'parfume-catalog'),
            'manage_options',
            'parfume-blog',
            array($this, 'blog_admin_page')
        );

        // Добавяне на нов Blog пост
        add_submenu_page(
            'parfume-catalog',
            __('Добави блог пост', 'parfume-catalog'),
            __('Добави блог пост', 'parfume-catalog'),
            'edit_posts',
            'post-new.php?post_type=parfume_blog'
        );

        // Настройки за блога
        add_submenu_page(
            'parfume-catalog',
            __('Настройки блог', 'parfume-catalog'),
            __('Настройки блог', 'parfume-catalog'),
            'manage_options',
            'parfume-blog-settings',
            array($this, 'blog_settings_page')
        );
    }

    /**
     * Blog админ страница
     */
    public function blog_admin_page() {
        $posts = get_posts(array(
            'post_type' => 'parfume_blog',
            'numberposts' => 20,
            'post_status' => array('publish', 'draft', 'pending')
        ));

        ?>
        <div class="wrap">
            <h1><?php _e('Блог управление', 'parfume-catalog'); ?></h1>

            <div class="blog-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo wp_count_posts('parfume_blog')->publish; ?></h3>
                        <p><?php _e('Публикувани постове', 'parfume-catalog'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo wp_count_posts('parfume_blog')->draft; ?></h3>
                        <p><?php _e('Чернови', 'parfume-catalog'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo wp_count_posts('parfume_blog')->pending; ?></h3>
                        <p><?php _e('Чакащи одобрение', 'parfume-catalog'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $this->get_blog_views_count(); ?></h3>
                        <p><?php _e('Общо прегледи', 'parfume-catalog'); ?></p>
                    </div>
                </div>
            </div>

            <div class="blog-quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=parfume_blog'); ?>" class="button button-primary">
                    <?php _e('Добави нов блог пост', 'parfume-catalog'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=parfume_blog'); ?>" class="button">
                    <?php _e('Всички блог постове', 'parfume-catalog'); ?>
                </a>
                <a href="<?php echo $this->get_blog_archive_url(); ?>" class="button" target="_blank">
                    <?php _e('Виж блог във фронтенда', 'parfume-catalog'); ?>
                </a>
            </div>

            <h2><?php _e('Последни блог постове', 'parfume-catalog'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Заглавие', 'parfume-catalog'); ?></th>
                        <th><?php _e('Автор', 'parfume-catalog'); ?></th>
                        <th><?php _e('Статус', 'parfume-catalog'); ?></th>
                        <th><?php _e('Дата', 'parfume-catalog'); ?></th>
                        <th><?php _e('Прегледи', 'parfume-catalog'); ?></th>
                        <th><?php _e('Действия', 'parfume-catalog'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                            <?php echo esc_html($post->post_title); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo get_the_author_meta('display_name', $post->post_author); ?></td>
                                <td>
                                    <span class="post-status status-<?php echo $post->post_status; ?>">
                                        <?php echo ucfirst($post->post_status); ?>
                                    </span>
                                </td>
                                <td><?php echo get_the_date('d.m.Y H:i', $post->ID); ?></td>
                                <td><?php echo $this->get_post_views_count($post->ID); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small">
                                        <?php _e('Редактирай', 'parfume-catalog'); ?>
                                    </a>
                                    <?php if ($post->post_status === 'publish'): ?>
                                        <a href="<?php echo get_permalink($post->ID); ?>" class="button button-small" target="_blank">
                                            <?php _e('Виж', 'parfume-catalog'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <?php _e('Няма блог постове за показване.', 'parfume-catalog'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <style>
            .blog-stats { margin: 20px 0; }
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
            .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
            .stat-card h3 { font-size: 2em; margin: 0 0 10px 0; color: #0073aa; }
            .stat-card p { margin: 0; color: #666; }
            .blog-quick-actions { margin: 20px 0; }
            .blog-quick-actions .button { margin-right: 10px; }
            .post-status { padding: 2px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase; }
            .status-publish { background: #d4edda; color: #155724; }
            .status-draft { background: #f8d7da; color: #721c24; }
            .status-pending { background: #fff3cd; color: #856404; }
            </style>
        </div>
        <?php
    }

    /**
     * Blog настройки страница
     */
    public function blog_settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['blog_settings_nonce'], 'save_blog_settings')) {
            $this->save_blog_settings();
        }

        $settings = $this->get_blog_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки на блога', 'parfume-catalog'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('save_blog_settings', 'blog_settings_nonce'); ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="posts_per_page"><?php _e('Постове на страница', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="posts_per_page" name="posts_per_page" 
                                       value="<?php echo esc_attr($settings['posts_per_page']); ?>" 
                                       min="1" max="50" class="small-text" />
                                <p class="description"><?php _e('Брой блог постове за показване на архивната страница.', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="excerpt_length"><?php _e('Дължина на извадката', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="excerpt_length" name="excerpt_length" 
                                       value="<?php echo esc_attr($settings['excerpt_length']); ?>" 
                                       min="10" max="100" class="small-text" />
                                <p class="description"><?php _e('Брой думи в извадката на блог постовете.', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Показване на мета информация', 'parfume-catalog'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="show_author">
                                        <input type="checkbox" id="show_author" name="show_author" value="1" 
                                               <?php checked($settings['show_author'], 1); ?> />
                                        <?php _e('Показвай автор', 'parfume-catalog'); ?>
                                    </label><br>

                                    <label for="show_date">
                                        <input type="checkbox" id="show_date" name="show_date" value="1" 
                                               <?php checked($settings['show_date'], 1); ?> />
                                        <?php _e('Показвай дата на публикуване', 'parfume-catalog'); ?>
                                    </label><br>

                                    <label for="show_views">
                                        <input type="checkbox" id="show_views" name="show_views" value="1" 
                                               <?php checked($settings['show_views'], 1); ?> />
                                        <?php _e('Показвай брой прегледи', 'parfume-catalog'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="enable_comments"><?php _e('Коментари', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <label for="enable_comments">
                                    <input type="checkbox" id="enable_comments" name="enable_comments" value="1" 
                                           <?php checked($settings['enable_comments'], 1); ?> />
                                    <?php _e('Разреши коментари за блог постовете', 'parfume-catalog'); ?>
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
                                       min="0" max="12" class="small-text" />
                                <p class="description"><?php _e('Брой свързани парфюми за показване в single blog пост. 0 = без показване.', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="enable_social_sharing"><?php _e('Социално споделяне', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <label for="enable_social_sharing">
                                    <input type="checkbox" id="enable_social_sharing" name="enable_social_sharing" value="1" 
                                           <?php checked($settings['enable_social_sharing'], 1); ?> />
                                    <?php _e('Покажи бутони за споделяне в социални мрежи', 'parfume-catalog'); ?>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="enable_reading_time"><?php _e('Време за четене', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <label for="enable_reading_time">
                                    <input type="checkbox" id="enable_reading_time" name="enable_reading_time" value="1" 
                                           <?php checked($settings['enable_reading_time'], 1); ?> />
                                    <?php _e('Покажи приблизително време за четене', 'parfume-catalog'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
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
        $settings = array(
            'posts_per_page' => absint($_POST['posts_per_page']),
            'excerpt_length' => absint($_POST['excerpt_length']),
            'show_author' => isset($_POST['show_author']) ? 1 : 0,
            'show_date' => isset($_POST['show_date']) ? 1 : 0,
            'show_views' => isset($_POST['show_views']) ? 1 : 0,
            'enable_comments' => isset($_POST['enable_comments']) ? 1 : 0,
            'related_parfumes_count' => absint($_POST['related_parfumes_count']),
            'enable_social_sharing' => isset($_POST['enable_social_sharing']) ? 1 : 0,
            'enable_reading_time' => isset($_POST['enable_reading_time']) ? 1 : 0
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
            'show_views' => 0,
            'enable_comments' => 1,
            'related_parfumes_count' => 4,
            'enable_social_sharing' => 1,
            'enable_reading_time' => 1
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

        // Fallback към стандартни WordPress templates
        if ($template_name === 'archive-blog.php') {
            return locate_template(array('archive.php', 'index.php'));
        } elseif ($template_name === 'single-blog.php') {
            return locate_template(array('single.php', 'index.php'));
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
                $query->set('post_status', 'publish');
                
                // Задаване на is_home false за да не конфликтира
                $query->is_home = false;
                $query->is_archive = true;
                $query->is_page = false;
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
            $blog_slug = isset($options['blog_slug']) ? $options['blog_slug'] : 'blog';
            
            $permalink = home_url($archive_slug . '/' . $blog_slug . '/' . $post->post_name . '/');
        }
        
        return $permalink;
    }

    /**
     * Enqueue на blog assets
     */
    public function enqueue_blog_assets() {
        if (is_singular('parfume_blog') || get_query_var('parfume_blog_archive')) {
            // CSS стилове за блога
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
                    'related_parfumes' => __('Свързани парфюми', 'parfume-catalog'),
                    'reading_time' => __('мин. четене', 'parfume-catalog'),
                    'comments' => __('Коментари', 'parfume-catalog'),
                    'no_comments' => __('Няма коментари', 'parfume-catalog')
                )
            ));

            // Добавяне на inline CSS за customization
            wp_add_inline_style('parfume-catalog-blog', $this->get_blog_inline_css());
        }
    }

    /**
     * Добавяне на body classes
     */
    public function add_blog_body_classes($classes) {
        if (get_query_var('parfume_blog_archive')) {
            $classes[] = 'parfume-blog-archive';
            $classes[] = 'archive';
            $classes[] = 'post-type-archive';
            $classes[] = 'post-type-archive-parfume_blog';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'parfume-blog-single';
            $classes[] = 'single';
            $classes[] = 'single-parfume_blog';
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

        if (!$post || $post->post_type !== 'parfume_blog') {
            return;
        }

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
        $thumbnail_id = get_post_thumbnail_id($post->ID);
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

        // Категория
        $schema_data['articleSection'] = 'Парфюми и ароматни тенденции';

        // Време за четене
        $reading_time = $this->calculate_reading_time($post->post_content);
        if ($reading_time > 0) {
            $schema_data['timeRequired'] = 'PT' . $reading_time . 'M';
        }

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
            'url' => $this->get_blog_archive_url(),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url()
                )
            )
        );

        // Брой постове в колекцията
        global $wp_query;
        if (isset($wp_query->found_posts)) {
            $schema_data['numberOfItems'] = $wp_query->found_posts;
        }

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
     * AJAX - Извличане на свързани парфюми
     */
    public function ajax_get_related_parfumes() {
        check_ajax_referer('parfume_blog_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $count = intval($_POST['count']) ?: 4;

        $related_parfumes = $this->get_related_parfumes($post_id, $count);

        ob_start();
        if (!empty($related_parfumes)) {
            echo '<div class="related-parfumes-grid">';
            foreach ($related_parfumes as $parfume) {
                $this->render_related_parfume_item($parfume);
            }
            echo '</div>';
        } else {
            echo '<p class="no-related">' . __('Няма свързани парфюми.', 'parfume-catalog') . '</p>';
        }
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content,
            'count' => count($related_parfumes)
        ));
    }

    /**
     * Извличане на свързани парфюми за blog пост
     */
    private function get_related_parfumes($post_id, $count = 4) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'parfume_blog') {
            return array();
        }

        // Извличаме ключови думи от заглавието и съдържанието
        $keywords = $this->extract_keywords_from_post($post);

        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => $count,
            'post_status' => 'publish',
            'meta_query' => array('relation' => 'OR')
        );

        // Търсене по ключови думи в различни полета
        foreach ($keywords as $keyword) {
            // В заглавието
            $args['meta_query'][] = array(
                'key' => 'post_title',
                'value' => $keyword,
                'compare' => 'LIKE'
            );
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Извличане на ключови думи от blog пост
     */
    private function extract_keywords_from_post($post) {
        $text = $post->post_title . ' ' . $post->post_content;
        $text = strip_tags($text);
        $text = strtolower($text);

        // Премахваме често срещани думи
        $stop_words = array('и', 'в', 'на', 'от', 'за', 'с', 'по', 'до', 'или', 'че', 'да', 'се', 'не', 'но', 'а', 'е', 'са', 'има', 'как', 'кои', 'къде', 'кога', 'защо');
        
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 3 && !in_array($word, $stop_words);
        });

        // Вземаме най-често срещаните думи
        $word_count = array_count_values($words);
        arsort($word_count);
        
        return array_slice(array_keys($word_count), 0, 10);
    }

    /**
     * Рендериране на свързан парфюм item
     */
    private function render_related_parfume_item($parfume) {
        $thumbnail = get_the_post_thumbnail($parfume->ID, 'medium');
        $permalink = get_permalink($parfume->ID);
        $title = get_the_title($parfume->ID);

        // Марка
        $brand_terms = wp_get_object_terms($parfume->ID, 'parfume_marki');
        $brand = $brand_terms && !is_wp_error($brand_terms) ? $brand_terms[0]->name : '';

        ?>
        <div class="related-parfume-item">
            <?php if ($thumbnail): ?>
                <div class="parfume-thumbnail">
                    <a href="<?php echo esc_url($permalink); ?>">
                        <?php echo $thumbnail; ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="parfume-info">
                <h4 class="parfume-title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h4>
                <?php if ($brand): ?>
                    <p class="parfume-brand"><?php echo esc_html($brand); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Helper функции
     */
    private function get_blog_post_description($post_id) {
        $post = get_post($post_id);
        
        if ($post->post_excerpt) {
            return $post->post_excerpt;
        }
        
        $settings = $this->get_blog_settings();
        return wp_trim_words($post->post_content, $settings['excerpt_length']);
    }

    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            return $logo_data ? $logo_data[0] : '';
        }
        
        // Fallback logo
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/parfume-catalog-logo.png';
    }

    private function get_blog_archive_url() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        $blog_slug = isset($options['blog_slug']) ? $options['blog_slug'] : 'blog';
        
        return home_url($archive_slug . '/' . $blog_slug . '/');
    }

    private function calculate_reading_time($content) {
        $word_count = str_word_count(strip_tags($content));
        $reading_speed = 200; // words per minute
        return ceil($word_count / $reading_speed);
    }

    private function get_blog_views_count() {
        global $wpdb;
        
        $total_views = get_option('parfume_blog_total_views', 0);
        
        // Ако няма кеширани данни, изчисляваме
        if ($total_views == 0) {
            $posts = get_posts(array(
                'post_type' => 'parfume_blog',
                'numberposts' => -1,
                'post_status' => 'publish'
            ));
            
            foreach ($posts as $post) {
                $total_views += $this->get_post_views_count($post->ID);
            }
            
            update_option('parfume_blog_total_views', $total_views);
        }
        
        return $total_views;
    }

    private function get_post_views_count($post_id) {
        $views = get_post_meta($post_id, '_parfume_blog_views', true);
        return $views ? intval($views) : 0;
    }

    /**
     * Увеличаване на views при преглед на пост
     */
    public function increment_post_views($post_id) {
        $current_views = $this->get_post_views_count($post_id);
        update_post_meta($post_id, '_parfume_blog_views', $current_views + 1);
        
        // Обновяваме и общия брой прегледи
        $total_views = get_option('parfume_blog_total_views', 0);
        update_option('parfume_blog_total_views', $total_views + 1);
    }

    /**
     * Inline CSS за blog стилизиране
     */
    private function get_blog_inline_css() {
        return '
        .parfume-blog-archive .content-area,
        .parfume-blog-single .content-area {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .related-parfumes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .related-parfume-item {
            text-align: center;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .related-parfume-item:hover {
            transform: translateY(-2px);
        }
        
        .related-parfume-item .parfume-thumbnail img {
            width: 100%;
            height: auto;
            border-radius: 4px;
        }
        
        .related-parfume-item .parfume-title {
            margin: 10px 0 5px 0;
            font-size: 1em;
        }
        
        .related-parfume-item .parfume-title a {
            text-decoration: none;
            color: #333;
        }
        
        .related-parfume-item .parfume-title a:hover {
            color: #0073aa;
        }
        
        .related-parfume-item .parfume-brand {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .blog-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .blog-meta span {
            margin-right: 15px;
        }
        
        .reading-time:before {
            content: "📖 ";
        }
        
        .views-count:before {
            content: "👁 ";
        }
        
        .social-sharing {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .social-sharing h4 {
            margin: 0 0 10px 0;
        }
        
        .social-buttons {
            display: flex;
            gap: 10px;
        }
        
        .social-button {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 0.9em;
        }
        
        .social-button.facebook { background: #3b5998; }
        .social-button.twitter { background: #1da1f2; }
        .social-button.linkedin { background: #0077b5; }
        
        @media (max-width: 768px) {
            .related-parfumes-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .social-buttons {
                flex-direction: column;
            }
        }
        ';
    }

    /**
     * Utility функции
     */
    public static function get_blog_post_count() {
        return wp_count_posts('parfume_blog')->publish;
    }

    public static function get_recent_blog_posts($count = 5) {
        return get_posts(array(
            'post_type' => 'parfume_blog',
            'numberposts' => $count,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    }

    /**
     * Cleanup при деактивиране
     */
    public static function cleanup_blog_data() {
        // Изчистване на opciones
        delete_option('parfume_blog_settings');
        delete_option('parfume_blog_total_views');
        delete_option('parfume_blog_rewrite_rules_flushed');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}