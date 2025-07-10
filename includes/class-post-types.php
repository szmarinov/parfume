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
        add_action('init', array($this, 'register_post_types'), 0);
        add_action('init', array($this, 'register_taxonomies'), 0);
        add_action('init', array($this, 'register_rewrite_rules'), 10);
        add_filter('post_type_link', array($this, 'custom_permalink'), 10, 2);
        add_filter('term_link', array($this, 'custom_term_link'), 10, 3);
        add_action('wp_loaded', array($this, 'flush_rewrite_rules_maybe'));
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
            'archives'              => __('Архиви с парфюми', 'parfume-catalog'),
            'attributes'            => __('Атрибути на парфюма', 'parfume-catalog'),
            'insert_into_item'      => __('Вмъкни в парфюм', 'parfume-catalog'),
            'uploaded_to_this_item' => __('Качено към този парфюм', 'parfume-catalog'),
            'featured_image'        => __('Основна снимка', 'parfume-catalog'),
            'set_featured_image'    => __('Постави основна снимка', 'parfume-catalog'),
            'remove_featured_image' => __('Премахни основна снимка', 'parfume-catalog'),
            'use_featured_image'    => __('Използвай като основна снимка', 'parfume-catalog'),
            'filter_items_list'     => __('Филтрирай списъка с парфюми', 'parfume-catalog'),
            'items_list_navigation' => __('Навигация в списъка с парфюми', 'parfume-catalog'),
            'items_list'            => __('Списък с парфюми', 'parfume-catalog'),
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
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'trackbacks', 'revisions', 'author', 'page-attributes'),
            'show_in_rest'       => true,
            'rest_base'          => 'parfumes',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
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
            'rest_base'          => 'parfume-blog',
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
        
        // Добавяне на default термини при активиране
        add_action('wp_loaded', array($this, 'add_default_terms'), 20);
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
            'rest_base'             => 'parfume-types',
        );

        register_taxonomy('parfume_type', array('parfumes'), $args);
    }

    /**
     * Таксономия "Вид аромат" (Тоалетна вода, Парфюмна вода и т.н.)
     */
    private function register_parfume_vid_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $vid_slug = isset($options['vid_slug']) ? $options['vid_slug'] : 'parfiumi';

        $labels = array(
            'name'                       => _x('Видове аромати', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Вид аромат', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси видове аромати', 'parfume-catalog'),
            'popular_items'              => __('Популярни видове аромати', 'parfume-catalog'),
            'all_items'                  => __('Всички видове аромати', 'parfume-catalog'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Редактирай вид аромат', 'parfume-catalog'),
            'update_item'                => __('Обнови вид аромат', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов вид аромат', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия вид аромат', 'parfume-catalog'),
            'separate_items_with_commas' => __('Разделяй видовете аромати със запетая', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни видове аромати', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните видове аромати', 'parfume-catalog'),
            'not_found'                  => __('Няма намерени видове аромати.', 'parfume-catalog'),
            'menu_name'                  => __('Видове аромати', 'parfume-catalog'),
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
            'rest_base'             => 'parfume-vid',
        );

        register_taxonomy('parfume_vid', array('parfumes'), $args);
    }

    /**
     * Таксономия "Марки"
     */
    private function register_parfume_marki_taxonomy() {
        $options = get_option('parfume_catalog_options', array());
        $marki_slug = isset($options['marki_slug']) ? $options['marki_slug'] : 'parfiumi/marki';

        $labels = array(
            'name'                       => _x('Марки', 'taxonomy general name', 'parfume-catalog'),
            'singular_name'              => _x('Марка', 'taxonomy singular name', 'parfume-catalog'),
            'search_items'               => __('Търси марки', 'parfume-catalog'),
            'popular_items'              => __('Популярни марки', 'parfume-catalog'),
            'all_items'                  => __('Всички марки', 'parfume-catalog'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Редактирай марка', 'parfume-catalog'),
            'update_item'                => __('Обнови марка', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова марка', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата марка', 'parfume-catalog'),
            'separate_items_with_commas' => __('Разделяй марките със запетая', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни марки', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните марки', 'parfume-catalog'),
            'not_found'                  => __('Няма намерени марки.', 'parfume-catalog'),
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
            'rest_base'             => 'parfume-marki',
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
            'popular_items'              => __('Популярни сезони', 'parfume-catalog'),
            'all_items'                  => __('Всички сезони', 'parfume-catalog'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Редактирай сезон', 'parfume-catalog'),
            'update_item'                => __('Обнови сезон', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов сезон', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия сезон', 'parfume-catalog'),
            'separate_items_with_commas' => __('Разделяй сезоните със запетая', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни сезони', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните сезони', 'parfume-catalog'),
            'not_found'                  => __('Няма намерени сезони.', 'parfume-catalog'),
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
            'rest_base'             => 'parfume-season',
        );

        register_taxonomy('parfume_season', array('parfumes'), $args);
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
            'popular_items'              => __('Популярни интензивности', 'parfume-catalog'),
            'all_items'                  => __('Всички интензивности', 'parfume-catalog'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Редактирай интензивност', 'parfume-catalog'),
            'update_item'                => __('Обнови интензивност', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова интензивност', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата интензивност', 'parfume-catalog'),
            'separate_items_with_commas' => __('Разделяй интензивностите със запетая', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни интензивности', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните интензивности', 'parfume-catalog'),
            'not_found'                  => __('Няма намерени интензивности.', 'parfume-catalog'),
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
            'rest_base'             => 'parfume-intensity',
        );

        register_taxonomy('parfume_intensity', array('parfumes'), $args);
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
            'popular_items'              => __('Популярни нотки', 'parfume-catalog'),
            'all_items'                  => __('Всички нотки', 'parfume-catalog'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Редактирай нотка', 'parfume-catalog'),
            'update_item'                => __('Обнови нотка', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова нотка', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата нотка', 'parfume-catalog'),
            'separate_items_with_commas' => __('Разделяй нотките със запетая', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни нотки', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните нотки', 'parfume-catalog'),
            'not_found'                  => __('Няма намерени нотки.', 'parfume-catalog'),
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
            'rest_base'             => 'parfume-notes',
        );

        register_taxonomy('parfume_notes', array('parfumes'), $args);
    }

    /**
     * Добавяне на default термини
     */
    public function add_default_terms() {
        // Добавяне на default типове
        $this->add_default_type_terms();
        
        // Добавяне на default видове аромати
        $this->add_default_vid_terms();
        
        // Добавяне на default сезони
        $this->add_default_season_terms();
        
        // Добавяне на default интензивности
        $this->add_default_intensity_terms();
        
        // Добавяне на примерни нотки
        $this->add_default_notes_terms();
    }

    /**
     * Добавяне на default типове
     */
    private function add_default_type_terms() {
        $default_types = array(
            'Дамски' => 'damski',
            'Мъжки' => 'mazhki',
            'Унисекс' => 'uniseks',
            'Младежки' => 'mladezhki',
            'Възрастни' => 'vazrastni',
            'Луксозни парфюми' => 'luksozni-parfiumi',
            'Нишови парфюми' => 'nishovi-parfiumi',
            'Арабски Парфюми' => 'arabski-parfiumi'
        );

        foreach ($default_types as $name => $slug) {
            if (!term_exists($name, 'parfume_type')) {
                wp_insert_term($name, 'parfume_type', array('slug' => $slug));
            }
        }
    }

    /**
     * Добавяне на default видове аромати
     */
    private function add_default_vid_terms() {
        $default_vids = array(
            'Тоалетна вода' => 'toaletna-voda',
            'Парфюмна вода' => 'parfyumna-voda',
            'Парфюм' => 'parfyum',
            'Парфюмен елексир' => 'parfyumen-eleksir'
        );

        foreach ($default_vids as $name => $slug) {
            if (!term_exists($name, 'parfume_vid')) {
                wp_insert_term($name, 'parfume_vid', array('slug' => $slug));
            }
        }
    }

    /**
     * Добавяне на default сезони
     */
    private function add_default_season_terms() {
        $default_seasons = array(
            'Пролет' => 'prolet',
            'Лято' => 'lyato',
            'Есен' => 'esen',
            'Зима' => 'zima'
        );

        foreach ($default_seasons as $name => $slug) {
            if (!term_exists($name, 'parfume_season')) {
                wp_insert_term($name, 'parfume_season', array('slug' => $slug));
            }
        }
    }

    /**
     * Добавяне на default интензивности
     */
    private function add_default_intensity_terms() {
        $default_intensities = array(
            'Силни' => 'silni',
            'Средни' => 'sredni',
            'Леки' => 'leki',
            'Фини/деликатни' => 'fini-delikatni',
            'Интензивни' => 'intenzivni',
            'Пудрени (Powdery)' => 'pudreni-powdery',
            'Тежки/дълбоки (Heavy/Deep)' => 'tezhki-dylboki-heavy-deep'
        );

        foreach ($default_intensities as $name => $slug) {
            if (!term_exists($name, 'parfume_intensity')) {
                wp_insert_term($name, 'parfume_intensity', array('slug' => $slug));
            }
        }
    }

    /**
     * Добавяне на примерни нотки
     */
    private function add_default_notes_terms() {
        $default_notes = array(
            // Дървесни нотки
            'Iso E Super' => array('slug' => 'iso-e-super', 'group' => 'дървесни'),
            'Абаносово дърво' => array('slug' => 'abanosovo-darvo', 'group' => 'дървесни'),
            'Австралийски син кипарис' => array('slug' => 'avstralijski-sin-kiparis', 'group' => 'дървесни'),
            'Агарово дърво (Оуд)' => array('slug' => 'agarovo-darvo-oud', 'group' => 'ориенталски'),
            
            // Ароматни нотки
            'Абсент' => array('slug' => 'absent', 'group' => 'ароматни'),
            
            // Зелени нотки
            'Авокадо' => array('slug' => 'avokado', 'group' => 'зелени'),
            'Агаве' => array('slug' => 'agave', 'group' => 'зелени'),
            
            // Цветни нотки
            'Аглая' => array('slug' => 'aglaya', 'group' => 'цветни'),
            
            // Гурме нотки
            'Адвокат' => array('slug' => 'advokat', 'group' => 'гурме'),
            
            // Популярни нотки
            'Роза' => array('slug' => 'roza', 'group' => 'цветни'),
            'Жасмин' => array('slug' => 'zhasmin', 'group' => 'цветни'),
            'Сандалово дърво' => array('slug' => 'sandalovo-darvo', 'group' => 'дървесни'),
            'Ванилия' => array('slug' => 'vaniliya', 'group' => 'сладки'),
            'Мускус' => array('slug' => 'muskus', 'group' => 'животински'),
            'Амбра' => array('slug' => 'ambra', 'group' => 'животински'),
            'Бергамот' => array('slug' => 'bergamot', 'group' => 'цитрусови'),
            'Лимон' => array('slug' => 'limon', 'group' => 'цитрусови'),
            'Лавандула' => array('slug' => 'lavandula', 'group' => 'ароматни'),
            'Пачули' => array('slug' => 'pachuli', 'group' => 'ориенталски')
        );

        foreach ($default_notes as $name => $data) {
            if (!term_exists($name, 'parfume_notes')) {
                $term = wp_insert_term($name, 'parfume_notes', array('slug' => $data['slug']));
                if (!is_wp_error($term)) {
                    // Добавяне на група като term meta
                    add_term_meta($term['term_id'], 'note_group', $data['group'], true);
                }
            }
        }
    }

    /**
     * Регистриране на custom rewrite rules
     */
    public function register_rewrite_rules() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        // Custom rewrite rules за complex URL structures
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/([^/]+)/?$',
            'index.php?parfume_type=$matches[1]&parfumes=$matches[2]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $archive_slug . '/marki/([^/]+)/([^/]+)/?$',
            'index.php?parfume_marki=$matches[1]&parfumes=$matches[2]',
            'top'
        );
    }

    /**
     * Custom permalink за парфюми
     */
    public function custom_permalink($permalink, $post) {
        if ($post->post_type !== 'parfumes') {
            return $permalink;
        }
        
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        // Ако има brand в URL структурата
        $brand = get_the_terms($post->ID, 'parfume_marki');
        if ($brand && !is_wp_error($brand)) {
            $brand_slug = $brand[0]->slug;
            $permalink = home_url($archive_slug . '/' . $brand_slug . '/' . $post->post_name . '/');
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

    /**
     * Flush rewrite rules when needed
     */
    public function flush_rewrite_rules_maybe() {
        if (get_option('parfume_catalog_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('parfume_catalog_flush_rewrite_rules');
        }
    }
}