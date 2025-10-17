<?php
/**
 * Taxonomy Configuration
 * 
 * Defines all custom taxonomies for the Parfume post type
 * 
 * @package ParfumeReviews
 * @subpackage Config
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    
    /**
     * Марки (Brands)
     */
    'marki' => [
        'post_type' => 'parfume',
        'labels' => [
            'name' => __('Марки', 'parfume-reviews'),
            'singular_name' => __('Марка', 'parfume-reviews'),
            'search_items' => __('Търсене на марки', 'parfume-reviews'),
            'all_items' => __('Всички марки', 'parfume-reviews'),
            'parent_item' => __('Родителска марка', 'parfume-reviews'),
            'parent_item_colon' => __('Родителска марка:', 'parfume-reviews'),
            'edit_item' => __('Редактиране на марка', 'parfume-reviews'),
            'update_item' => __('Актуализиране на марка', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нова марка', 'parfume-reviews'),
            'new_item_name' => __('Име на нова марка', 'parfume-reviews'),
            'menu_name' => __('Марки', 'parfume-reviews'),
        ],
        'hierarchical' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'parfiumi/marki',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'meta_box_cb' => 'post_categories_meta_box',
        'default_terms' => [
            'Chanel',
            'Dior',
            'Tom Ford',
            'Guerlain',
            'Yves Saint Laurent',
            'Giorgio Armani',
            'Givenchy',
            'Lancôme',
            'Prada',
            'Versace',
            'Dolce & Gabbana',
            'Bvlgari',
            'Hermès',
            'Burberry',
            'Calvin Klein',
            'Hugo Boss',
            'Carolina Herrera',
            'Viktor & Rolf',
            'Kilian',
            'Creed',
        ],
    ],
    
    /**
     * Пол (Gender)
     */
    'gender' => [
        'post_type' => 'parfume',
        'labels' => [
            'name' => __('Пол', 'parfume-reviews'),
            'singular_name' => __('Пол', 'parfume-reviews'),
            'search_items' => __('Търсене по пол', 'parfume-reviews'),
            'all_items' => __('Всички', 'parfume-reviews'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Редактиране', 'parfume-reviews'),
            'update_item' => __('Актуализиране', 'parfume-reviews'),
            'add_new_item' => __('Добавяне', 'parfume-reviews'),
            'new_item_name' => __('Ново име', 'parfume-reviews'),
            'menu_name' => __('Пол', 'parfume-reviews'),
        ],
        'hierarchical' => false,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => false,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'parfiumi/pol',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'meta_box_cb' => 'post_tags_meta_box',
        'default_terms' => [
            'Мъжки',
            'Дамски',
            'Унисекс',
        ],
    ],
    
    /**
     * Тип Аромат (Aroma Type)
     */
    'aroma_type' => [
        'post_type' => 'parfume',
        'labels' => [
            'name' => __('Тип Аромат', 'parfume-reviews'),
            'singular_name' => __('Тип', 'parfume-reviews'),
            'search_items' => __('Търсене по тип', 'parfume-reviews'),
            'all_items' => __('Всички типове', 'parfume-reviews'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Редактиране на тип', 'parfume-reviews'),
            'update_item' => __('Актуализиране на тип', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нов тип', 'parfume-reviews'),
            'new_item_name' => __('Име на нов тип', 'parfume-reviews'),
            'menu_name' => __('Тип Аромат', 'parfume-reviews'),
        ],
        'hierarchical' => false,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'parfiumi/tip',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'meta_box_cb' => 'post_tags_meta_box',
        'default_terms' => [
            'Тоалетна вода',
            'Парфюмна вода',
            'Парфюм',
            'Одеколон',
            'Eau Fraiche',
        ],
    ],
    
    /**
     * Сезон (Season)
     */
    'season' => [
        'post_type' => 'parfume',
        'labels' => [
            'name' => __('Сезон', 'parfume-reviews'),
            'singular_name' => __('Сезон', 'parfume-reviews'),
            'search_items' => __('Търсене по сезон', 'parfume-reviews'),
            'all_items' => __('Всички сезони', 'parfume-reviews'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Редактиране на сезон', 'parfume-reviews'),
            'update_item' => __('Актуализиране на сезон', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нов сезон', 'parfume-reviews'),
            'new_item_name' => __('Име на нов сезон', 'parfume-reviews'),
            'menu_name' => __('Сезон', 'parfume-reviews'),
        ],
        'hierarchical' => false,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'parfiumi/sezon',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'meta_box_cb' => 'post_tags_meta_box',
        'default_terms' => [
            'Пролет',
            'Лято',
            'Есен',
            'Зима',
            'Целогодишен',
        ],
    ],
    
    /**
     * Интензитет (Intensity)
     */
    'intensity' => [
        'post_type' => 'parfume',
        'labels' => [
            'name' => __('Интензитет', 'parfume-reviews'),
            'singular_name' => __('Интензитет', 'parfume-reviews'),
            'search_items' => __('Търсене по интензитет', 'parfume-reviews'),
            'all_items' => __('Всички нива', 'parfume-reviews'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Редактиране на ниво', 'parfume-reviews'),
            'update_item' => __('Актуализиране на ниво', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на ново ниво', 'parfume-reviews'),
            'new_item_name' => __('Име на ново ниво', 'parfume-reviews'),
            'menu_name' => __('Интензитет', 'parfume-reviews'),
        ],
        'hierarchical' => false,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => false,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'parfiumi/intenzitet',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'meta_box_cb' => 'post_tags_meta_box',
        'default_terms' => [
            'Силен',
            'Среден',
            'Лек',
        ],
    ],
    
    /**
     * Нотки (Notes)
     */
    'notes' => [
        'post_type' => 'parfume',
        'labels' => [
            'name' => __('Нотки', 'parfume-reviews'),
            'singular_name' => __('Нотка', 'parfume-reviews'),
            'search_items' => __('Търсене на нотки', 'parfume-reviews'),
            'all_items' => __('Всички нотки', 'parfume-reviews'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Редактиране на нотка', 'parfume-reviews'),
            'update_item' => __('Актуализиране на нотка', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нова нотка', 'parfume-reviews'),
            'new_item_name' => __('Име на нова нотка', 'parfume-reviews'),
            'menu_name' => __('Нотки', 'parfume-reviews'),
        ],
        'hierarchical' => false,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => false,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'parfiumi/notki',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'meta_box_cb' => 'post_tags_meta_box',
        'default_terms' => [
            // Дървесни
            'Кедър',
            'Сандалово дърво',
            'Пачули',
            'Ветивер',
            'Oud',
            // Цветни
            'Роза',
            'Жасмин',
            'Иланг-иланг',
            'Лилия',
            'Лавандула',
            // Цитрусови
            'Бергамот',
            'Лимон',
            'Портокал',
            'Грейпфрут',
            'Мандарина',
            // Плодови
            'Ябълка',
            'Праскова',
            'Малина',
            'Круша',
            'Черна касис',
            // Ориенталски
            'Ванилия',
            'Амбър',
            'Бензоин',
            'Ладан',
            'Мирта',
            // Зелени
            'Зелен чай',
            'Мента',
            'Босилек',
            'Мъх',
            'Папрат',
            // Подправки
            'Канела',
            'Карамфил',
            'Джинджифил',
            'Черен пипер',
            'Кардамон',
        ],
    ],
    
    /**
     * Парфюмери (Perfumers)
     */
    'perfumer' => [
        'post_type' => 'parfume',
        'labels' => [
            'name' => __('Парфюмери', 'parfume-reviews'),
            'singular_name' => __('Парфюмер', 'parfume-reviews'),
            'search_items' => __('Търсене на парфюмери', 'parfume-reviews'),
            'all_items' => __('Всички парфюмери', 'parfume-reviews'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Редактиране на парфюмер', 'parfume-reviews'),
            'update_item' => __('Актуализиране на парфюмер', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нов парфюмер', 'parfume-reviews'),
            'new_item_name' => __('Име на нов парфюмер', 'parfume-reviews'),
            'menu_name' => __('Парфюмери', 'parfume-reviews'),
        ],
        'hierarchical' => false,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => false,
        'show_in_nav_menus' => true,
        'show_tagcloud' => false,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'parfiumi/parfumeri',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'meta_box_cb' => 'post_tags_meta_box',
        'default_terms' => [
            'Jacques Polge',
            'Olivier Polge',
            'François Demachy',
            'Christine Nagel',
            'Alberto Morillas',
            'Francis Kurkdjian',
            'Jean-Claude Ellena',
            'Dominique Ropion',
            'Pierre Negrin',
            'Nathalie Feisthauer',
        ],
    ],
];