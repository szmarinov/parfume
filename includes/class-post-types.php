<?php
/**
 * Post Types class for Parfume Catalog plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('post_type_link', array($this, 'custom_post_type_link'), 10, 2);
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        // Register Parfumes CPT
        $args = array(
            'labels' => array(
                'name' => __('Парфюми', 'parfume-catalog'),
                'singular_name' => __('Парфюм', 'parfume-catalog'),
                'menu_name' => __('Парфюми', 'parfume-catalog'),
                'add_new' => __('Добави нов', 'parfume-catalog'),
                'add_new_item' => __('Добави нов парфюм', 'parfume-catalog'),
                'edit_item' => __('Редактирай парфюм', 'parfume-catalog'),
                'new_item' => __('Нов парфюм', 'parfume-catalog'),
                'view_item' => __('Виж парфюм', 'parfume-catalog'),
                'search_items' => __('Търси парфюми', 'parfume-catalog'),
                'not_found' => __('Няма намерени парфюми', 'parfume-catalog'),
                'not_found_in_trash' => __('Няма парфюми в кошчето', 'parfume-catalog'),
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $archive_slug, 'with_front' => false),
            'capability_type' => 'post',
            'has_archive' => $archive_slug,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-products',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
        );
        
        register_post_type('parfumes', $args);
        
        // Register Blog CPT
        $blog_args = array(
            'labels' => array(
                'name' => __('Блог постове', 'parfume-catalog'),
                'singular_name' => __('Блог пост', 'parfume-catalog'),
                'menu_name' => __('Блог', 'parfume-catalog'),
                'add_new' => __('Добави нов', 'parfume-catalog'),
                'add_new_item' => __('Добави нов блог пост', 'parfume-catalog'),
                'edit_item' => __('Редактирай блог пост', 'parfume-catalog'),
                'new_item' => __('Нов блог пост', 'parfume-catalog'),
                'view_item' => __('Виж блог пост', 'parfume-catalog'),
                'search_items' => __('Търси блог постове', 'parfume-catalog'),
                'not_found' => __('Няма намерени блог постове', 'parfume-catalog'),
                'not_found_in_trash' => __('Няма блог постове в кошчето', 'parfume-catalog'),
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Will be added to main menu via admin class
            'query_var' => true,
            'rewrite' => array('slug' => $archive_slug . '/blog', 'with_front' => false),
            'capability_type' => 'post',
            'has_archive' => $archive_slug . '/blog',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'show_in_rest' => true,
        );
        
        register_post_type('parfume_blog', $blog_args);
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        $options = get_option('parfume_catalog_options', array());
        
        // Get slugs from options
        $tip_slug = isset($options['tip_slug']) ? $options['tip_slug'] : 'parfiumi';
        $vid_aromat_slug = isset($options['vid_aromat_slug']) ? $options['vid_aromat_slug'] : 'parfiumi';
        $marki_slug = isset($options['marki_slug']) ? $options['marki_slug'] : 'parfiumi/marki';
        $sezon_slug = isset($options['sezon_slug']) ? $options['sezon_slug'] : 'parfiumi/season';
        $intenzivnost_slug = isset($options['intenzivnost_slug']) ? $options['intenzivnost_slug'] : 'parfiumi/intenzivnost';
        $notki_slug = isset($options['notki_slug']) ? $options['notki_slug'] : 'notes';
        
        // Register Tip taxonomy
        register_taxonomy('tip', 'parfumes', array(
            'labels' => array(
                'name' => __('Тип', 'parfume-catalog'),
                'singular_name' => __('Тип', 'parfume-catalog'),
                'menu_name' => __('Тип', 'parfume-catalog'),
                'add_new_item' => __('Добави нов тип', 'parfume-catalog'),
                'edit_item' => __('Редактирай тип', 'parfume-catalog'),
                'update_item' => __('Обнови тип', 'parfume-catalog'),
                'view_item' => __('Виж тип', 'parfume-catalog'),
                'separate_items_with_commas' => __('Разделете типовете със запетаи', 'parfume-catalog'),
                'add_or_remove_items' => __('Добави или премахни типове', 'parfume-catalog'),
                'choose_from_most_used' => __('Избери от най-използваните типове', 'parfume-catalog'),
                'popular_items' => __('Популярни типове', 'parfume-catalog'),
                'search_items' => __('Търси типове', 'parfume-catalog'),
                'not_found' => __('Няма намерени типове', 'parfume-catalog'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array('slug' => $tip_slug, 'with_front' => false, 'hierarchical' => true),
            'show_in_rest' => true,
        ));
        
        // Register Vid Aromat taxonomy
        register_taxonomy('vid-aromat', 'parfumes', array(
            'labels' => array(
                'name' => __('Вид аромат', 'parfume-catalog'),
                'singular_name' => __('Вид аромат', 'parfume-catalog'),
                'menu_name' => __('Вид аромат', 'parfume-catalog'),
                'add_new_item' => __('Добави нов вид аромат', 'parfume-catalog'),
                'edit_item' => __('Редактирай вид аромат', 'parfume-catalog'),
                'update_item' => __('Обнови вид аромат', 'parfume-catalog'),
                'view_item' => __('Виж вид аромат', 'parfume-catalog'),
                'separate_items_with_commas' => __('Разделете видовете със запетаи', 'parfume-catalog'),
                'add_or_remove_items' => __('Добави или премахни видове', 'parfume-catalog'),
                'choose_from_most_used' => __('Избери от най-използваните видове', 'parfume-catalog'),
                'popular_items' => __('Популярни видове', 'parfume-catalog'),
                'search_items' => __('Търси видове', 'parfume-catalog'),
                'not_found' => __('Няма намерени видове', 'parfume-catalog'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array('slug' => $vid_aromat_slug, 'with_front' => false, 'hierarchical' => true),
            'show_in_rest' => true,
        ));
        
        // Register Marki taxonomy
        register_taxonomy('marki', 'parfumes', array(
            'labels' => array(
                'name' => __('Марки', 'parfume-catalog'),
                'singular_name' => __('Марка', 'parfume-catalog'),
                'menu_name' => __('Марки', 'parfume-catalog'),
                'add_new_item' => __('Добави нова марка', 'parfume-catalog'),
                'edit_item' => __('Редактирай марка', 'parfume-catalog'),
                'update_item' => __('Обнови марка', 'parfume-catalog'),
                'view_item' => __('Виж марка', 'parfume-catalog'),
                'separate_items_with_commas' => __('Разделете марките със запетаи', 'parfume-catalog'),
                'add_or_remove_items' => __('Добави или премахни марки', 'parfume-catalog'),
                'choose_from_most_used' => __('Избери от най-използваните марки', 'parfume-catalog'),
                'popular_items' => __('Популярни марки', 'parfume-catalog'),
                'search_items' => __('Търси марки', 'parfume-catalog'),
                'not_found' => __('Няма намерени марки', 'parfume-catalog'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array('slug' => $marki_slug, 'with_front' => false),
            'show_in_rest' => true,
        ));
        
        // Register Sezon taxonomy
        register_taxonomy('sezon', 'parfumes', array(
            'labels' => array(
                'name' => __('Сезон', 'parfume-catalog'),
                'singular_name' => __('Сезон', 'parfume-catalog'),
                'menu_name' => __('Сезон', 'parfume-catalog'),
                'add_new_item' => __('Добави нов сезон', 'parfume-catalog'),
                'edit_item' => __('Редактирай сезон', 'parfume-catalog'),
                'update_item' => __('Обнови сезон', 'parfume-catalog'),
                'view_item' => __('Виж сезон', 'parfume-catalog'),
                'separate_items_with_commas' => __('Разделете сезоните със запетаи', 'parfume-catalog'),
                'add_or_remove_items' => __('Добави или премахни сезони', 'parfume-catalog'),
                'choose_from_most_used' => __('Избери от най-използваните сезони', 'parfume-catalog'),
                'popular_items' => __('Популярни сезони', 'parfume-catalog'),
                'search_items' => __('Търси сезони', 'parfume-catalog'),
                'not_found' => __('Няма намерени сезони', 'parfume-catalog'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array('slug' => $sezon_slug, 'with_front' => false),
            'show_in_rest' => true,
        ));
        
        // Register Intenzivnost taxonomy
        register_taxonomy('intenzivnost', 'parfumes', array(
            'labels' => array(
                'name' => __('Интензивност', 'parfume-catalog'),
                'singular_name' => __('Интензивност', 'parfume-catalog'),
                'menu_name' => __('Интензивност', 'parfume-catalog'),
                'add_new_item' => __('Добави нова интензивност', 'parfume-catalog'),
                'edit_item' => __('Редактирай интензивност', 'parfume-catalog'),
                'update_item' => __('Обнови интензивност', 'parfume-catalog'),
                'view_item' => __('Виж интензивност', 'parfume-catalog'),
                'separate_items_with_commas' => __('Разделете интензивностите със запетаи', 'parfume-catalog'),
                'add_or_remove_items' => __('Добави или премахни интензивности', 'parfume-catalog'),
                'choose_from_most_used' => __('Избери от най-използваните интензивности', 'parfume-catalog'),
                'popular_items' => __('Популярни интензивности', 'parfume-catalog'),
                'search_items' => __('Търси интензивности', 'parfume-catalog'),
                'not_found' => __('Няма намерени интензивности', 'parfume-catalog'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array('slug' => $intenzivnost_slug, 'with_front' => false),
            'show_in_rest' => true,
        ));
        
        // Register Notki taxonomy
        register_taxonomy('notki', 'parfumes', array(
            'labels' => array(
                'name' => __('Нотки', 'parfume-catalog'),
                'singular_name' => __('Нотка', 'parfume-catalog'),
                'menu_name' => __('Нотки', 'parfume-catalog'),
                'add_new_item' => __('Добави нова нотка', 'parfume-catalog'),
                'edit_item' => __('Редактирай нотка', 'parfume-catalog'),
                'update_item' => __('Обнови нотка', 'parfume-catalog'),
                'view_item' => __('Виж нотка', 'parfume-catalog'),
                'separate_items_with_commas' => __('Разделете нотките със запетаи', 'parfume-catalog'),
                'add_or_remove_items' => __('Добави или премахни нотки', 'parfume-catalog'),
                'choose_from_most_used' => __('Избери от най-използваните нотки', 'parfume-catalog'),
                'popular_items' => __('Популярни нотки', 'parfume-catalog'),
                'search_items' => __('Търси нотки', 'parfume-catalog'),
                'not_found' => __('Няма намерени нотки', 'parfume-catalog'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array('slug' => $notki_slug, 'with_front' => false),
            'show_in_rest' => true,
        ));
        
        // Add default terms on plugin activation
        add_action('init', array($this, 'add_default_terms'), 20);
    }
    
    /**
     * Add default terms to taxonomies
     */
    public function add_default_terms() {
        // Only run once
        if (get_option('parfume_catalog_default_terms_added')) {
            return;
        }
        
        // Default terms for Tip taxonomy
        $tip_terms = array(
            'Дамски' => 'damski',
            'Мъжки' => 'mazhki',
            'Унисекс' => 'uniseks',
            'Младежки' => 'mladezhki',
            'Възрастни' => 'vazrastni',
            'Луксозни парфюми' => 'luksozni-parfiumi',
            'Нишови парфюми' => 'nishovi-parfiumi',
            'Арабски Парфюми' => 'arabski-parfiumi'
        );
        
        foreach ($tip_terms as $name => $slug) {
            if (!term_exists($name, 'tip')) {
                wp_insert_term($name, 'tip', array('slug' => $slug));
            }
        }
        
        // Default terms for Vid Aromat taxonomy
        $vid_aromat_terms = array(
            'Тоалетна вода' => 'toaletna-voda',
            'Парфюмна вода' => 'parfiumna-voda',
            'Парфюм' => 'parfium',
            'Парфюмен елексир' => 'parfumen-eleksir'
        );
        
        foreach ($vid_aromat_terms as $name => $slug) {
            if (!term_exists($name, 'vid-aromat')) {
                wp_insert_term($name, 'vid-aromat', array('slug' => $slug));
            }
        }
        
        // Default terms for Sezon taxonomy
        $sezon_terms = array(
            'Пролет' => 'prolet',
            'Лято' => 'liato',
            'Есен' => 'esen',
            'Зима' => 'zima'
        );
        
        foreach ($sezon_terms as $name => $slug) {
            if (!term_exists($name, 'sezon')) {
                wp_insert_term($name, 'sezon', array('slug' => $slug));
            }
        }
        
        // Default terms for Intenzivnost taxonomy
        $intenzivnost_terms = array(
            'Силни' => 'silni',
            'Средни' => 'sredni',
            'Леки' => 'leki',
            'Фини/деликатни' => 'fini-delikatni',
            'Интензивни' => 'intenzivni',
            'Пудрени (Powdery)' => 'pudreni-powdery',
            'Тежки/дълбоки (Heavy/Deep)' => 'tezhki-dalbok-heavy-deep'
        );
        
        foreach ($intenzivnost_terms as $name => $slug) {
            if (!term_exists($name, 'intenzivnost')) {
                wp_insert_term($name, 'intenzivnost', array('slug' => $slug));
            }
        }
        
        // Mark as done
        update_option('parfume_catalog_default_terms_added', true);
    }
    
    /**
     * Add custom rewrite rules
     */
    public function add_rewrite_rules() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        
        // Add custom rewrite rules for taxonomies with custom slugs
        add_rewrite_rule(
            '^' . $archive_slug . '/([^/]+)/([^/]+)/?$',
            'index.php?taxonomy=$matches[1]&term=$matches[2]',
            'top'
        );
    }
    
    /**
     * Custom post type permalink structure
     */
    public function custom_post_type_link($post_link, $post) {
        if ($post->post_type === 'parfumes') {
            $options = get_option('parfume_catalog_options', array());
            $archive_slug = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
            
            return home_url($archive_slug . '/' . $post->post_name . '/');
        }
        
        return $post_link;
    }
}