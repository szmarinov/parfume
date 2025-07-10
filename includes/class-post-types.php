<?php
/**
 * Parfume Catalog Post Types Class
 * 
 * Регистрира Custom Post Type "Parfumes" и всички таксономии
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Post_Types {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_rewrite_rules'));
        add_filter('post_type_link', array($this, 'custom_permalink'), 10, 2);
        add_filter('term_link', array($this, 'custom_term_link'), 10, 3);
    }

    /**
     * Регистриране на Custom Post Types
     */
    public function register_post_types() {
        // Регистриране на CPT "Parfumes"
        $this->register_parfumes_cpt();
        
        // Регистриране на CPT за блог
        $this->register_blog_cpt();
    }

    /**
     * Регистриране на CPT "Parfumes"
     */
    private function register_parfumes_cpt() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';

        $labels = array(
            'name'                  => _x('Парфюми', 'Post type general name', 'parfume-catalog'),
            'singular_name'         => _x('Парфюм', 'Post type singular name', 'parfume-catalog'),
            'menu_name'             => _x('Парфюми', 'Admin Menu text', 'parfume-catalog'),
            'name_admin_bar'        => _x('Парфюм', 'Add New on Toolbar', 'parfume-catalog'),
            'add_new'               => __('Добави нов', 'parfume-catalog'),
            'add_new_item'          => __('Добави нов парфюм', 'parfume-catalog'),
            'new_item'              => __('Нов парфюм', 'parfume-catalog'),
            'edit_item'             => __('Редактирай парфюм', 'parfume-catalog'),
            'view_item'             => __('Виж парфюм', 'parfume-catalog'),
            'all_items'             => __('Всички парфюми', 'parfume-catalog'),
            'search_items'          => __('Търси парфюми', 'parfume-catalog'),
            'parent_item_colon'     => __('Родителски парфюми:', 'parfume-catalog'),
            'not_found'             => __('Няма намерени парфюми.', 'parfume-catalog'),
            'not_found_in_trash'    => __('Няма намерени парфюми в кошчето.', 'parfume-catalog'),
            'featured_image'        => _x('Изображение на парфюма', 'Overrides the "Featured Image" phrase', 'parfume-catalog'),
            'set_featured_image'    => _x('Задай изображение на парфюма', 'Overrides the "Set featured image" phrase', 'parfume-catalog'),
            'remove_featured_image' => _x('Премахни изображение на парфюма', 'Overrides the "Remove featured image" phrase', 'parfume-catalog'),
            'use_featured_image'    => _x('Използвай като изображение на парфюма', 'Overrides the "Use as featured image" phrase', 'parfume-catalog'),
            'archives'              => _x('Архив парфюми', 'The post type archive label used in nav menus', 'parfume-catalog'),
            'insert_into_item'      => _x('Вмъкни в парфюм', 'Overrides the "Insert into post"/"Insert into page" phrase', 'parfume-catalog'),
            'uploaded_to_this_item' => _x('Качени към този парфюм', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'parfume-catalog'),
            'filter_items_list'     => _x('Филтрирай списък парфюми', 'Screen reader text for the filter links', 'parfume-catalog'),
            'items_list_navigation' => _x('Навигация списък парфюми', 'Screen reader text for the pagination', 'parfume-catalog'),
            'items_list'            => _x('Списък парфюми', 'Screen reader text for the items list', 'parfume-catalog'),
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
            'rewrite'            => array(
                'slug'       => $archive_slug,
                'with_front' => false,
            ),
            'capability_type'    => 'post',
            'has_archive'        => $archive_slug,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-store',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
            'show_in_rest'       => true, // За Gutenberg поддръжка
        );

        register_post_type('parfumes', $args);
    }

    /**
     * Регистриране на CPT за блог
     */
    private function register_blog_cpt() {
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
            'not_found'             => __('Няма намерени блог постове.', 'parfume-catalog'),
            'not_found_in_trash'    => __('Няма намерени блог постове в кошчето.', 'parfume-catalog'),
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
            'rewrite'            => array(
                'slug'       => $archive_slug . '/blog',
                'with_front' => false,
            ),
            'capability_type'    => 'post',
            'has_archive'        => $archive_slug . '/blog',
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'trackbacks', 'revisions', 'author', 'page-attributes'),
            'show_in_rest'       => true,
        );

        register_post_type('parfume_blog', $args);
    }

    /**
     * Регистриране на таксономии
     */
    public function register_taxonomies() {
        // Регистриране на всички таксономии
        $this->register_parfume_type_taxonomy();
        $this->register_parfume_vid_taxonomy();
        $this->register_parfume_marki_taxonomy();
        $this->register_parfume_season_taxonomy();
        $this->register_parfume_intensity_taxonomy();
        $this->register_parfume_notes_taxonomy();
    }

    /**
     * Таксономия "Тип" (Дамски, Мъжки, Унисекс и т.н.)
     */
    private function register_parfume_type_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $type_slug = isset($options['type_slug']) ? $options['type_slug'] : 'parfiumi';

        $labels = array(
            'name'                       => _x('Типове', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Тип', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси типове', 'parfume-catalog'),
            'popular_items'              => __('Популярни типове', 'parfume-catalog'),
            'all_items'                  => __('Всички типове', 'parfume-catalog'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Редактирай тип', 'parfume-catalog'),
            'update_item'                => __('Обнови тип', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов тип', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия тип', 'parfume-catalog'),
            'separate_items_with_commas' => __('Разделяй типовете със запетая', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни типове', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните типове', 'parfume-catalog'),
            'not_found'                  => __('Няма намерени типове.', 'parfume-catalog'),
            'menu_name'                  => __('Типове', 'parfume-catalog'),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array(
                'slug'         => $type_slug,
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'show_in_rest'          => true,
        );

        register_taxonomy('parfume_type', array('parfumes'), $args);

        // Добавяне на default термини
        $this->add_default_type_terms();
    }

    /**
     * Таксономия "Вид аромат" (Тоалетна вода, Парфюмна вода и т.н.)
     */
    private function register_parfume_vid_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $vid_slug = isset($options['vid_slug']) ? $options['vid_slug'] : 'parfiumi';

        $labels = array(
            'name'                       => _x('Видове аромат', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Вид аромат', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси видове аромат', 'parfume-catalog'),
            'all_items'                  => __('Всички видове аромат', 'parfume-catalog'),
            'edit_item'                  => __('Редактирай вид аромат', 'parfume-catalog'),
            'update_item'                => __('Обнови вид аромат', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов вид аромат', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия вид аромат', 'parfume-catalog'),
            'menu_name'                  => __('Видове аромат', 'parfume-catalog'),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array(
                'slug'         => $vid_slug,
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'show_in_rest'          => true,
        );

        register_taxonomy('parfume_vid', array('parfumes'), $args);

        // Добавяне на default термини
        $this->add_default_vid_terms();
    }

    /**
     * Таксономия "Марка"
     */
    private function register_parfume_marki_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $marki_slug = isset($options['marki_slug']) ? $options['marki_slug'] : 'parfiumi/marki';

        $labels = array(
            'name'                       => _x('Марки', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Марка', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси марки', 'parfume-catalog'),
            'all_items'                  => __('Всички марки', 'parfume-catalog'),
            'edit_item'                  => __('Редактирай марка', 'parfume-catalog'),
            'update_item'                => __('Обнови марка', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова марка', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата марка', 'parfume-catalog'),
            'menu_name'                  => __('Марки', 'parfume-catalog'),
        );

        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array(
                'slug'         => $marki_slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'show_in_rest'          => true,
        );

        register_taxonomy('parfume_marki', array('parfumes'), $args);
    }

    /**
     * Таксономия "Сезон"
     */
    private function register_parfume_season_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $season_slug = isset($options['season_slug']) ? $options['season_slug'] : 'parfiumi/season';

        $labels = array(
            'name'                       => _x('Сезони', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Сезон', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси сезони', 'parfume-catalog'),
            'all_items'                  => __('Всички сезони', 'parfume-catalog'),
            'edit_item'                  => __('Редактирай сезон', 'parfume-catalog'),
            'update_item'                => __('Обнови сезон', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов сезон', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия сезон', 'parfume-catalog'),
            'menu_name'                  => __('Сезони', 'parfume-catalog'),
        );

        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array(
                'slug'         => $season_slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'show_in_rest'          => true,
        );

        register_taxonomy('parfume_season', array('parfumes'), $args);

        // Добавяне на default термини
        $this->add_default_season_terms();
    }

    /**
     * Таксономия "Интензивност"
     */
    private function register_parfume_intensity_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $intensity_slug = isset($options['intensity_slug']) ? $options['intensity_slug'] : 'parfiumi/intensity';

        $labels = array(
            'name'                       => _x('Интензивност', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Интензивност', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси интензивност', 'parfume-catalog'),
            'all_items'                  => __('Всички видове интензивност', 'parfume-catalog'),
            'edit_item'                  => __('Редактирай интензивност', 'parfume-catalog'),
            'update_item'                => __('Обнови интензивност', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова интензивност', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата интензивност', 'parfume-catalog'),
            'menu_name'                  => __('Интензивност', 'parfume-catalog'),
        );

        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array(
                'slug'         => $intensity_slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'show_in_rest'          => true,
        );

        register_taxonomy('parfume_intensity', array('parfumes'), $args);

        // Добавяне на default термини
        $this->add_default_intensity_terms();
    }

    /**
     * Таксономия "Нотки"
     */
    private function register_parfume_notes_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $notes_slug = isset($options['notes_slug']) ? $options['notes_slug'] : 'notes';

        $labels = array(
            'name'                       => _x('Нотки', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Нотка', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси нотки', 'parfume-catalog'),
            'all_items'                  => __('Всички нотки', 'parfume-catalog'),
            'edit_item'                  => __('Редактирай нотка', 'parfume-catalog'),
            'update_item'                => __('Обнови нотка', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова нотка', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата нотка', 'parfume-catalog'),
            'menu_name'                  => __('Нотки', 'parfume-catalog'),
        );

        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array(
                'slug'         => $notes_slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'show_in_rest'          => true,
        );

        register_taxonomy('parfume_notes', array('parfumes'), $args);
    }

    /**
     * Добавяне на default термини за типове
     */
    private function add_default_type_terms() {
        $default_types = array(
            'damski' => 'Дамски',
            'mazhki' => 'Мъжки',
            'uniseks' => 'Унисекс',
            'mladejki' => 'Младежки',
            'vazrastni' => 'Възрастни',
            'luksozni-parfiumi' => 'Луксозни парфюми',
            'nishovi-parfiumi' => 'Нишови парфюми',
            'arabski-parfiumi' => 'Арабски парфюми'
        );

        foreach ($default_types as $slug => $name) {
            if (!term_exists($name, 'parfume_type')) {
                wp_insert_term($name, 'parfume_type', array('slug' => $slug));
            }
        }
    }

    /**
     * Добавяне на default термини за видове аромат
     */
    private function add_default_vid_terms() {
        $default_vids = array(
            'toaletna-voda' => 'Тоалетна вода',
            'parfiumna-voda' => 'Парфюмна вода',
            'parfium' => 'Парфюм',
            'parfiumeu-eleksir' => 'Парфюмен елексир'
        );

        foreach ($default_vids as $slug => $name) {
            if (!term_exists($name, 'parfume_vid')) {
                wp_insert_term($name, 'parfume_vid', array('slug' => $slug));
            }
        }
    }

    /**
     * Добавяне на default термини за сезони
     */
    private function add_default_season_terms() {
        $default_seasons = array(
            'prolet' => 'Пролет',
            'liato' => 'Лято',
            'esen' => 'Есен',
            'zima' => 'Зима'
        );

        foreach ($default_seasons as $slug => $name) {
            if (!term_exists($name, 'parfume_season')) {
                wp_insert_term($name, 'parfume_season', array('slug' => $slug));
            }
        }
    }

    /**
     * Добавяне на default термини за интензивност
     */
    private function add_default_intensity_terms() {
        $default_intensities = array(
            'silni' => 'Силни',
            'sredni' => 'Средни',
            'leki' => 'Леки',
            'fini-delikatni' => 'Фини/деликатни',
            'intenzivni' => 'Интензивни',
            'pudreni-powdery' => 'Пудрени (Powdery)',
            'tejki-dalboky-heavy-deep' => 'Тежки/дълбоки (Heavy/Deep)'
        );

        foreach ($default_intensities as $slug => $name) {
            if (!term_exists($name, 'parfume_intensity')) {
                wp_insert_term($name, 'parfume_intensity', array('slug' => $slug));
            }
        }
    }

    /**
     * Регистриране на rewrite rules
     */
    public function register_rewrite_rules() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';

        // Main archive rule
        add_rewrite_rule(
            '^' . $archive_slug . '/?$',
            'index.php?post_type=parfumes',
            'top'
        );

        // Single parfume rule
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/?$',
            'index.php?post_type=parfumes&name=$matches[1]',
            'top'
        );

        // Archive pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/page/([0-9]{1,})/?$',
            'index.php?post_type=parfumes&paged=$matches[1]',
            'top'
        );

        // Taxonomy rules
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/([^/]+)/?$',
            'index.php?taxonomy=parfume_type&term=$matches[2]',
            'top'
        );

        // Taxonomy pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_type&term=$matches[2]&paged=$matches[3]',
            'top'
        );

        // Marki rules
        add_rewrite_rule(
            '^' . $archive_slug . '/marki/([^/]+)/?$',
            'index.php?taxonomy=parfume_marki&term=$matches[1]',
            'top'
        );

        // Marki pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/marki/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_marki&term=$matches[1]&paged=$matches[2]',
            'top'
        );

        // Season rules
        add_rewrite_rule(
            '^' . $archive_slug . '/season/([^/]+)/?$',
            'index.php?taxonomy=parfume_season&term=$matches[1]',
            'top'
        );

        // Season pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/season/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_season&term=$matches[1]&paged=$matches[2]',
            'top'
        );

        // Intensity rules
        add_rewrite_rule(
            '^' . $archive_slug . '/intensity/([^/]+)/?$',
            'index.php?taxonomy=parfume_intensity&term=$matches[1]',
            'top'
        );

        // Intensity pagination
        add_rewrite_rule(
            '^' . $archive_slug . '/intensity/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_intensity&term=$matches[1]&paged=$matches[2]',
            'top'
        );

        // Notes rules
        add_rewrite_rule(
            '^notes/([^/]+)/?$',
            'index.php?taxonomy=parfume_notes&term=$matches[1]',
            'top'
        );

        // Notes pagination
        add_rewrite_rule(
            '^notes/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?taxonomy=parfume_notes&term=$matches[1]&paged=$matches[2]',
            'top'
        );
    }

    /**
     * Custom permalink за парфюми
     */
    public function custom_permalink($permalink, $post) {
        if ($post->post_type == 'parfumes') {
            $options = get_option('parfume_catalog_options', array());
            $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
            
            $permalink = home_url($archive_slug . '/' . $post->post_name . '/');
        }
        
        return $permalink;
    }

    /**
     * Custom term link за таксономии
     */
    public function custom_term_link($termlink, $term, $taxonomy) {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';

        switch ($taxonomy) {
            case 'parfume_type':
                $type_slug = isset($options['type_slug']) ? $options['type_slug'] : $archive_slug;
                $termlink = home_url($type_slug . '/' . $term->slug . '/');
                break;

            case 'parfume_vid':
                $vid_slug = isset($options['vid_slug']) ? $options['vid_slug'] : $archive_slug;
                $termlink = home_url($vid_slug . '/' . $term->slug . '/');
                break;

            case 'parfume_marki':
                $marki_slug = isset($options['marki_slug']) ? $options['marki_slug'] : $archive_slug . '/marki';
                $termlink = home_url($marki_slug . '/' . $term->slug . '/');
                break;

            case 'parfume_season':
                $season_slug = isset($options['season_slug']) ? $options['season_slug'] : $archive_slug . '/season';
                $termlink = home_url($season_slug . '/' . $term->slug . '/');
                break;

            case 'parfume_intensity':
                $intensity_slug = isset($options['intensity_slug']) ? $options['intensity_slug'] : $archive_slug . '/intensity';
                $termlink = home_url($intensity_slug . '/' . $term->slug . '/');
                break;

            case 'parfume_notes':
                $notes_slug = isset($options['notes_slug']) ? $options['notes_slug'] : 'notes';
                $termlink = home_url($notes_slug . '/' . $term->slug . '/');
                break;
        }

        return $termlink;
    }
}