<?php
/**
 * Post Types class for Parfume Catalog plugin
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Post_Types {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('post_type_link', array($this, 'custom_post_type_link'), 10, 2);
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $options = get_option('parfume_catalog_settings', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        $labels = array(
            'name'                  => __('Парфюми', 'parfume-catalog'),
            'singular_name'         => __('Парфюм', 'parfume-catalog'),
            'menu_name'             => __('Парфюми', 'parfume-catalog'),
            'name_admin_bar'        => __('Парфюм', 'parfume-catalog'),
            'archives'              => __('Архив на парфюми', 'parfume-catalog'),
            'attributes'            => __('Атрибути на парфюма', 'parfume-catalog'),
            'parent_item_colon'     => __('Родителски парфюм:', 'parfume-catalog'),
            'all_items'             => __('Всички парфюми', 'parfume-catalog'),
            'add_new_item'          => __('Добави нов парфюм', 'parfume-catalog'),
            'add_new'               => __('Добави нов', 'parfume-catalog'),
            'new_item'              => __('Нов парфюм', 'parfume-catalog'),
            'edit_item'             => __('Редактиране на парфюм', 'parfume-catalog'),
            'update_item'           => __('Обновяване на парфюм', 'parfume-catalog'),
            'view_item'             => __('Виж парфюм', 'parfume-catalog'),
            'view_items'            => __('Виж парфюми', 'parfume-catalog'),
            'search_items'          => __('Търси парфюми', 'parfume-catalog'),
            'not_found'             => __('Не са намерени парфюми', 'parfume-catalog'),
            'not_found_in_trash'    => __('Не са намерени парфюми в кошчето', 'parfume-catalog'),
            'featured_image'        => __('Снимка на парфюма', 'parfume-catalog'),
            'set_featured_image'    => __('Задай снимка на парфюма', 'parfume-catalog'),
            'remove_featured_image' => __('Премахни снимката на парфюма', 'parfume-catalog'),
            'use_featured_image'    => __('Използвай като снимка на парфюма', 'parfume-catalog'),
            'insert_into_item'      => __('Вмъкни в парфюма', 'parfume-catalog'),
            'uploaded_to_this_item' => __('Качено към този парфюм', 'parfume-catalog'),
            'items_list'            => __('Списък с парфюми', 'parfume-catalog'),
            'items_list_navigation' => __('Навигация в списъка', 'parfume-catalog'),
            'filter_items_list'     => __('Филтриране на списъка', 'parfume-catalog'),
        );
        
        $args = array(
            'label'                 => __('Парфюм', 'parfume-catalog'),
            'description'           => __('Каталог с парфюми', 'parfume-catalog'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false, // Ще се добави в custom админ менюто
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-products',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array(
                'slug' => $archive_slug,
                'with_front' => false,
                'pages' => true,
                'feeds' => true,
            ),
        );
        
        register_post_type('parfumes', $args);
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        $options = get_option('parfume_catalog_settings', array());
        
        // Тип таксономия
        $tip_labels = array(
            'name'                       => __('Типове', 'parfume-catalog'),
            'singular_name'              => __('Тип', 'parfume-catalog'),
            'menu_name'                  => __('Типове', 'parfume-catalog'),
            'all_items'                  => __('Всички типове', 'parfume-catalog'),
            'parent_item'                => __('Родителски тип', 'parfume-catalog'),
            'parent_item_colon'          => __('Родителски тип:', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия тип', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов тип', 'parfume-catalog'),
            'edit_item'                  => __('Редактиране на тип', 'parfume-catalog'),
            'update_item'                => __('Обновяване на тип', 'parfume-catalog'),
            'view_item'                  => __('Виж тип', 'parfume-catalog'),
            'separate_items_with_commas' => __('Раздели типовете със запетаи', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни типове', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните типове', 'parfume-catalog'),
            'popular_items'              => __('Популярни типове', 'parfume-catalog'),
            'search_items'               => __('Търси типове', 'parfume-catalog'),
            'not_found'                  => __('Не са намерени типове', 'parfume-catalog'),
            'no_terms'                   => __('Няма типове', 'parfume-catalog'),
            'items_list'                 => __('Списък с типове', 'parfume-catalog'),
            'items_list_navigation'      => __('Навигация в списъка', 'parfume-catalog'),
        );
        
        $tip_args = array(
            'labels'                     => $tip_labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug' => isset($options['tip_slug']) ? $options['tip_slug'] : 'parfiumi',
                'with_front' => false,
                'hierarchical' => true,
            ),
        );
        
        register_taxonomy('tip', array('parfumes'), $tip_args);
        
        // Вид аромат таксономия
        $vid_aromat_labels = array(
            'name'                       => __('Видове аромат', 'parfume-catalog'),
            'singular_name'              => __('Вид аромат', 'parfume-catalog'),
            'menu_name'                  => __('Видове аромат', 'parfume-catalog'),
            'all_items'                  => __('Всички видове', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия вид', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов вид', 'parfume-catalog'),
            'edit_item'                  => __('Редактиране на вид', 'parfume-catalog'),
            'update_item'                => __('Обновяване на вид', 'parfume-catalog'),
            'view_item'                  => __('Виж вид', 'parfume-catalog'),
            'separate_items_with_commas' => __('Раздели видовете със запетаи', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни видове', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните', 'parfume-catalog'),
            'popular_items'              => __('Популярни видове', 'parfume-catalog'),
            'search_items'               => __('Търси видове', 'parfume-catalog'),
            'not_found'                  => __('Не са намерени видове', 'parfume-catalog'),
            'no_terms'                   => __('Няма видове', 'parfume-catalog'),
            'items_list'                 => __('Списък с видове', 'parfume-catalog'),
            'items_list_navigation'      => __('Навигация в списъка', 'parfume-catalog'),
        );
        
        $vid_aromat_args = array(
            'labels'                     => $vid_aromat_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug' => isset($options['vid_aromat_slug']) ? $options['vid_aromat_slug'] : 'parfiumi',
                'with_front' => false,
            ),
        );
        
        register_taxonomy('vid_aromat', array('parfumes'), $vid_aromat_args);
        
        // Марки таксономия
        $marki_labels = array(
            'name'                       => __('Марки', 'parfume-catalog'),
            'singular_name'              => __('Марка', 'parfume-catalog'),
            'menu_name'                  => __('Марки', 'parfume-catalog'),
            'all_items'                  => __('Всички марки', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата марка', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова марка', 'parfume-catalog'),
            'edit_item'                  => __('Редактиране на марка', 'parfume-catalog'),
            'update_item'                => __('Обновяване на марка', 'parfume-catalog'),
            'view_item'                  => __('Виж марка', 'parfume-catalog'),
            'separate_items_with_commas' => __('Раздели марките със запетаи', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни марки', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните', 'parfume-catalog'),
            'popular_items'              => __('Популярни марки', 'parfume-catalog'),
            'search_items'               => __('Търси марки', 'parfume-catalog'),
            'not_found'                  => __('Не са намерени марки', 'parfume-catalog'),
            'no_terms'                   => __('Няма марки', 'parfume-catalog'),
            'items_list'                 => __('Списък с марки', 'parfume-catalog'),
            'items_list_navigation'      => __('Навигация в списъка', 'parfume-catalog'),
        );
        
        $marki_args = array(
            'labels'                     => $marki_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug' => isset($options['marki_slug']) ? $options['marki_slug'] : 'parfiumi/marki',
                'with_front' => false,
            ),
        );
        
        register_taxonomy('marki', array('parfumes'), $marki_args);
        
        // Сезон таксономия
        $sezon_labels = array(
            'name'                       => __('Сезони', 'parfume-catalog'),
            'singular_name'              => __('Сезон', 'parfume-catalog'),
            'menu_name'                  => __('Сезони', 'parfume-catalog'),
            'all_items'                  => __('Всички сезони', 'parfume-catalog'),
            'new_item_name'              => __('Име на новия сезон', 'parfume-catalog'),
            'add_new_item'               => __('Добави нов сезон', 'parfume-catalog'),
            'edit_item'                  => __('Редактиране на сезон', 'parfume-catalog'),
            'update_item'                => __('Обновяване на сезон', 'parfume-catalog'),
            'view_item'                  => __('Виж сезон', 'parfume-catalog'),
            'separate_items_with_commas' => __('Раздели сезоните със запетаи', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни сезони', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните', 'parfume-catalog'),
            'popular_items'              => __('Популярни сезони', 'parfume-catalog'),
            'search_items'               => __('Търси сезони', 'parfume-catalog'),
            'not_found'                  => __('Не са намерени сезони', 'parfume-catalog'),
            'no_terms'                   => __('Няма сезони', 'parfume-catalog'),
            'items_list'                 => __('Списък със сезони', 'parfume-catalog'),
            'items_list_navigation'      => __('Навигация в списъка', 'parfume-catalog'),
        );
        
        $sezon_args = array(
            'labels'                     => $sezon_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug' => isset($options['sezon_slug']) ? $options['sezon_slug'] : 'parfiumi/season',
                'with_front' => false,
            ),
        );
        
        register_taxonomy('sezon', array('parfumes'), $sezon_args);
        
        // Интензивност таксономия
        $intenzivnost_labels = array(
            'name'                       => __('Интензивност', 'parfume-catalog'),
            'singular_name'              => __('Интензивност', 'parfume-catalog'),
            'menu_name'                  => __('Интензивност', 'parfume-catalog'),
            'all_items'                  => __('Всички нива', 'parfume-catalog'),
            'new_item_name'              => __('Име на новото ниво', 'parfume-catalog'),
            'add_new_item'               => __('Добави ново ниво', 'parfume-catalog'),
            'edit_item'                  => __('Редактиране на ниво', 'parfume-catalog'),
            'update_item'                => __('Обновяване на ниво', 'parfume-catalog'),
            'view_item'                  => __('Виж ниво', 'parfume-catalog'),
            'separate_items_with_commas' => __('Раздели нивата със запетаи', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни нива', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните', 'parfume-catalog'),
            'popular_items'              => __('Популярни нива', 'parfume-catalog'),
            'search_items'               => __('Търси нива', 'parfume-catalog'),
            'not_found'                  => __('Не са намерени нива', 'parfume-catalog'),
            'no_terms'                   => __('Няма нива', 'parfume-catalog'),
            'items_list'                 => __('Списък с нива', 'parfume-catalog'),
            'items_list_navigation'      => __('Навигация в списъка', 'parfume-catalog'),
        );
        
        $intenzivnost_args = array(
            'labels'                     => $intenzivnost_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug' => 'parfiumi/intensities',
                'with_front' => false,
            ),
        );
        
        register_taxonomy('intenzivnost', array('parfumes'), $intenzivnost_args);
        
        // Нотки таксономия
        $notki_labels = array(
            'name'                       => __('Нотки', 'parfume-catalog'),
            'singular_name'              => __('Нотка', 'parfume-catalog'),
            'menu_name'                  => __('Нотки', 'parfume-catalog'),
            'all_items'                  => __('Всички нотки', 'parfume-catalog'),
            'new_item_name'              => __('Име на новата нотка', 'parfume-catalog'),
            'add_new_item'               => __('Добави нова нотка', 'parfume-catalog'),
            'edit_item'                  => __('Редактиране на нотка', 'parfume-catalog'),
            'update_item'                => __('Обновяване на нотка', 'parfume-catalog'),
            'view_item'                  => __('Виж нотка', 'parfume-catalog'),
            'separate_items_with_commas' => __('Раздели нотките със запетаи', 'parfume-catalog'),
            'add_or_remove_items'        => __('Добави или премахни нотки', 'parfume-catalog'),
            'choose_from_most_used'      => __('Избери от най-използваните', 'parfume-catalog'),
            'popular_items'              => __('Популярни нотки', 'parfume-catalog'),
            'search_items'               => __('Търси нотки', 'parfume-catalog'),
            'not_found'                  => __('Не са намерени нотки', 'parfume-catalog'),
            'no_terms'                   => __('Няма нотки', 'parfume-catalog'),
            'items_list'                 => __('Списък с нотки', 'parfume-catalog'),
            'items_list_navigation'      => __('Навигация в списъка', 'parfume-catalog'),
        );
        
        $notki_args = array(
            'labels'                     => $notki_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => false, // Има много нотки
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug' => isset($options['notki_slug']) ? $options['notki_slug'] : 'notes',
                'with_front' => false,
            ),
        );
        
        register_taxonomy('notki', array('parfumes'), $notki_args);
    }
    
    /**
     * Add custom rewrite rules
     */
    public function add_rewrite_rules() {
        $options = get_option('parfume_catalog_settings', array());
        
        // Марки с A-Z навигация
        add_rewrite_rule(
            '^parfiumi/marki/?$',
            'index.php?post_type=parfumes&taxonomy=marki&is_brand_archive=1',
            'top'
        );
        
        // A-Z filter за марки
        add_rewrite_rule(
            '^parfiumi/marki/([a-zA-Z])/?$',
            'index.php?post_type=parfumes&taxonomy=marki&brand_letter=$matches[1]',
            'top'
        );
    }
    
    /**
     * Custom post type permalink structure
     */
    public function custom_post_type_link($post_link, $post) {
        if ($post->post_type !== 'parfumes' || $post->post_status !== 'publish') {
            return $post_link;
        }
        
        // Get the brand for the permalink
        $brands = wp_get_post_terms($post->ID, 'marki');
        if (!empty($brands)) {
            $brand = $brands[0];
            $post_link = str_replace('%marki%', $brand->slug, $post_link);
        } else {
            $post_link = str_replace('%marki%', 'uncategorized', $post_link);
        }
        
        return $post_link;
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'is_brand_archive';
        $vars[] = 'brand_letter';
        return $vars;
    }
    
    /**
     * Flush rewrite rules
     */
    public static function flush_rewrite_rules() {
        $instance = new self();
        $instance->register_post_types();
        $instance->register_taxonomies();
        $instance->add_rewrite_rules();
        flush_rewrite_rules();
    }
}