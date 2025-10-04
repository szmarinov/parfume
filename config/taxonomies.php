<?php
/**
 * Taxonomy Configuration
 * 
 * NOTE: Do NOT use __() translation functions in config files!
 * Config files load before 'init' hook where translations are loaded.
 * 
 * @package Parfume_Reviews
 * @subpackage Config
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    'gender' => [
        'slug' => 'gender',
        'post_type' => 'parfume',
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'rewrite' => [
            'slug' => 'parfiumi/gender',
            'with_front' => false,
            'hierarchical' => false
        ],
        'labels' => [
            'name' => 'Пол',
            'singular_name' => 'Пол',
            'search_items' => 'Търсене в пол',
            'all_items' => 'Всички',
            'edit_item' => 'Редактиране',
            'update_item' => 'Обновяване',
            'add_new_item' => 'Добавяне',
            'new_item_name' => 'Ново име',
            'menu_name' => 'Пол',
        ],
        'default_terms' => [
            'Мъжки парфюми',
            'Дамски парфюми',
            'Унисекс парфюми',
            'Арабски парфюми',
            'Луксозни парфюми',
            'Нишови парфюми'
        ]
    ],
    
    'aroma_type' => [
        'slug' => 'aroma_type',
        'post_type' => 'parfume',
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'rewrite' => [
            'slug' => 'parfiumi/aroma-type',
            'with_front' => false,
            'hierarchical' => false
        ],
        'labels' => [
            'name' => 'Видове аромати',
            'singular_name' => 'Вид арома',
            'search_items' => 'Търсене във видовете',
            'all_items' => 'Всички видове',
            'edit_item' => 'Редактиране на вид',
            'update_item' => 'Обновяване на вид',
            'add_new_item' => 'Добавяне на вид',
            'new_item_name' => 'Име на нов вид',
            'menu_name' => 'Видове аромати',
        ],
        'default_terms' => [
            'Тоалетна вода',
            'Парфюмна вода',
            'Парфюм',
            'Парфюмен елексир'
        ]
    ],
    
    'marki' => [
        'slug' => 'marki',
        'post_type' => 'parfume',
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'rewrite' => [
            'slug' => 'parfiumi/marki',
            'with_front' => false,
            'hierarchical' => false
        ],
        'labels' => [
            'name' => 'Марки',
            'singular_name' => 'Марка',
            'search_items' => 'Търсене в марките',
            'all_items' => 'Всички марки',
            'edit_item' => 'Редактиране на марка',
            'update_item' => 'Обновяване на марка',
            'add_new_item' => 'Добавяне на марка',
            'new_item_name' => 'Име на нова марка',
            'menu_name' => 'Марки',
        ],
        'default_terms' => [
            'Chanel',
            'Dior',
            'Tom Ford',
            'Creed',
            'Maison Francis Kurkdjian',
            'By Kilian',
            'Amouage',
            'Xerjoff',
            'Parfums de Marly',
            'Montale'
        ]
    ],
    
    'season' => [
        'slug' => 'season',
        'post_type' => 'parfume',
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'rewrite' => [
            'slug' => 'parfiumi/season',
            'with_front' => false,
            'hierarchical' => false
        ],
        'labels' => [
            'name' => 'Сезони',
            'singular_name' => 'Сезон',
            'search_items' => 'Търсене в сезони',
            'all_items' => 'Всички сезони',
            'edit_item' => 'Редактиране на сезон',
            'update_item' => 'Обновяване на сезон',
            'add_new_item' => 'Добавяне на сезон',
            'new_item_name' => 'Име на нов сезон',
            'menu_name' => 'Сезони',
        ],
        'default_terms' => [
            'Пролет',
            'Лято',
            'Есен',
            'Зима'
        ]
    ],
    
    'intensity' => [
        'slug' => 'intensity',
        'post_type' => 'parfume',
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'rewrite' => [
            'slug' => 'parfiumi/intensity',
            'with_front' => false,
            'hierarchical' => false
        ],
        'labels' => [
            'name' => 'Интензивност',
            'singular_name' => 'Интензивност',
            'search_items' => 'Търсене в интензивност',
            'all_items' => 'Всички',
            'edit_item' => 'Редактиране',
            'update_item' => 'Обновяване',
            'add_new_item' => 'Добавяне',
            'new_item_name' => 'Ново име',
            'menu_name' => 'Интензивност',
        ],
        'default_terms' => [
            'Силни',
            'Средни',
            'Леки'
        ]
    ],
    
    'notes' => [
        'slug' => 'notes',
        'post_type' => 'parfume',
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'rewrite' => [
            'slug' => 'parfiumi/notki',
            'with_front' => false,
            'hierarchical' => false
        ],
        'labels' => [
            'name' => 'Ароматни нотки',
            'singular_name' => 'Ароматна нотка',
            'search_items' => 'Търсене в нотки',
            'all_items' => 'Всички нотки',
            'edit_item' => 'Редактиране на нотка',
            'update_item' => 'Обновяване на нотка',
            'add_new_item' => 'Добавяне на нотка',
            'new_item_name' => 'Име на нова нотка',
            'menu_name' => 'Ароматни нотки',
        ],
        'default_terms' => [
            'Дървесни',
            'Цитрусови',
            'Ориенталски',
            'Цветни',
            'Свежи',
            'Пикантни'
        ]
    ],
    
    'perfumer' => [
        'slug' => 'perfumer',
        'post_type' => 'parfume',
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => 'post_categories_meta_box',
        'rewrite' => [
            'slug' => 'parfiumi/parfumeri',
            'with_front' => false,
            'hierarchical' => false
        ],
        'labels' => [
            'name' => 'Парфюмери',
            'singular_name' => 'Парфюмер',
            'search_items' => 'Търсене в парфюмери',
            'all_items' => 'Всички парфюмери',
            'edit_item' => 'Редактиране на парфюмер',
            'update_item' => 'Обновяване на парфюмер',
            'add_new_item' => 'Добавяне на парфюмер',
            'new_item_name' => 'Име на нов парфюмер',
            'menu_name' => 'Парфюмери',
        ],
        'default_terms' => [
            'Francis Kurkdjian',
            'Olivier Polge',
            'Jacques Polge',
            'Alberto Morillas',
            'Dominique Ropion'
        ]
    ]
];