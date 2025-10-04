<?php
/**
 * Post Type Configuration
 * 
 * NOTE: Do NOT use __() translation functions in config files!
 * Config files load before 'init' hook where translations are loaded.
 * Use plain strings here - translations happen in the classes that use these configs.
 * 
 * @package Parfume_Reviews
 * @subpackage Config
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    /**
     * Parfume Post Type
     */
    'parfume' => [
        'slug' => 'parfume',
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'excerpt',
            'custom-fields',
            'revisions',
            'author'
        ],
        'taxonomies' => [
            'marki',
            'gender',
            'aroma_type',
            'season',
            'intensity',
            'notes',
            'perfumer'
        ],
        'rewrite' => [
            'slug' => 'parfiumi',
            'with_front' => false,
            'hierarchical' => false,
            'feeds' => true
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-products',
        'capability_type' => 'post',
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'delete_with_user' => false,
        'rest_base' => 'parfumes',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'labels' => [
            'name' => 'Парфюми',
            'singular_name' => 'Парфюм',
            'menu_name' => 'Парфюми',
            'name_admin_bar' => 'Парфюм',
            'add_new' => 'Добави нов',
            'add_new_item' => 'Добави нов парфюм',
            'new_item' => 'Нов парфюм',
            'edit_item' => 'Редактирай парфюм',
            'view_item' => 'Виж парфюм',
            'view_items' => 'Виж парфюми',
            'all_items' => 'Всички парфюми',
            'search_items' => 'Търси парфюми',
            'parent_item_colon' => 'Родителски парфюм:',
            'not_found' => 'Няма намерени парфюми.',
            'not_found_in_trash' => 'Няма парфюми в кошчето.',
            'archives' => 'Архиви на парфюми',
            'insert_into_item' => 'Вмъкни в парфюм',
            'uploaded_to_this_item' => 'Качено към този парфюм',
            'featured_image' => 'Изображение на парфюма',
            'set_featured_image' => 'Задай изображение',
            'remove_featured_image' => 'Премахни изображение',
            'use_featured_image' => 'Използвай като изображение',
            'filter_items_list' => 'Филтрирай списък с парфюми',
            'items_list_navigation' => 'Навигация в списъка с парфюми',
            'items_list' => 'Списък с парфюми'
        ],
        
        /**
         * Meta Boxes Configuration
         */
        'meta_boxes' => [
            // Basic Information Meta Box
            'basic_info' => [
                'id' => 'parfume_basic_info',
                'title' => 'Основна информация',
                'context' => 'normal',
                'priority' => 'high',
                'fields' => [
                    'rating' => [
                        'label' => 'Обща оценка',
                        'type' => 'number',
                        'min' => 0,
                        'max' => 10,
                        'step' => 0.1,
                        'default' => 0,
                        'description' => 'Обща оценка (0-10)'
                    ],
                    'release_year' => [
                        'label' => 'Година на издаване',
                        'type' => 'number',
                        'min' => 1900,
                        'max' => 2100,
                        'description' => 'Годината, в която е пуснат парфюмът'
                    ],
                    'concentration' => [
                        'label' => 'Концентрация',
                        'type' => 'select',
                        'options' => [
                            'edp' => 'Eau de Parfum (EDP)',
                            'edt' => 'Eau de Toilette (EDT)',
                            'edc' => 'Eau de Cologne (EDC)',
                            'perfume' => 'Perfume/Extrait',
                            'edp_intense' => 'EDP Intense',
                            'other' => 'Друго'
                        ],
                        'default' => 'edp'
                    ]
                ]
            ],
            
            // Characteristics Meta Box
            'characteristics' => [
                'id' => 'parfume_characteristics',
                'title' => 'Характеристики',
                'context' => 'normal',
                'priority' => 'high',
                'fields' => [
                    'longevity' => [
                        'label' => 'Дълготрайност',
                        'type' => 'select',
                        'options' => [
                            'very_weak' => 'Много слаб',
                            'weak' => 'Слаб',
                            'moderate' => 'Умерен',
                            'long_lasting' => 'Траен',
                            'eternal' => 'Изключително траен'
                        ],
                        'default' => 'moderate',
                        'description' => 'Колко време издържа ароматът'
                    ],
                    'sillage' => [
                        'label' => 'Ароматна следа (Sillage)',
                        'type' => 'select',
                        'options' => [
                            'intimate' => 'Слаба',
                            'moderate' => 'Умерена',
                            'strong' => 'Силна',
                            'enormous' => 'Огромна'
                        ],
                        'default' => 'moderate',
                        'description' => 'Интензивност на ароматната следа'
                    ],
                    'price_value' => [
                        'label' => 'Ценова категория',
                        'type' => 'select',
                        'options' => [
                            'too_expensive' => 'Прекалено скъп',
                            'expensive' => 'Скъп',
                            'acceptable' => 'Приемлива цена',
                            'good_price' => 'Добра цена',
                            'cheap' => 'Евтин'
                        ],
                        'default' => 'acceptable'
                    ]
                ]
            ],
            
            // Notes Meta Box (Composition)
            'notes' => [
                'id' => 'parfume_notes',
                'title' => 'Ароматни нотки',
                'context' => 'normal',
                'priority' => 'high',
                'fields' => [
                    'top_notes' => [
                        'label' => 'Връхни нотки',
                        'type' => 'taxonomy_select',
                        'taxonomy' => 'notes',
                        'multiple' => true,
                        'description' => 'Първоначални нотки (първите 15 мин)'
                    ],
                    'middle_notes' => [
                        'label' => 'Средни нотки (Сърце)',
                        'type' => 'taxonomy_select',
                        'taxonomy' => 'notes',
                        'multiple' => true,
                        'description' => 'Сърдечни нотки (2-4 часа)'
                    ],
                    'base_notes' => [
                        'label' => 'Базови нотки',
                        'type' => 'taxonomy_select',
                        'taxonomy' => 'notes',
                        'multiple' => true,
                        'description' => 'Финални нотки (продължават най-дълго)'
                    ]
                ]
            ],
            
            // Advantages & Disadvantages
            'pros_cons' => [
                'id' => 'parfume_pros_cons',
                'title' => 'Предимства и недостатъци',
                'context' => 'normal',
                'priority' => 'default',
                'fields' => [
                    'advantages' => [
                        'label' => 'Предимства',
                        'type' => 'repeater',
                        'button_label' => 'Добави предимство',
                        'fields' => [
                            'text' => [
                                'label' => 'Предимство',
                                'type' => 'text'
                            ]
                        ]
                    ],
                    'disadvantages' => [
                        'label' => 'Недостатъци',
                        'type' => 'repeater',
                        'button_label' => 'Добави недостатък',
                        'fields' => [
                            'text' => [
                                'label' => 'Недостатък',
                                'type' => 'text'
                            ]
                        ]
                    ]
                ]
            ],
            
            // Gallery Meta Box
            'gallery' => [
                'id' => 'parfume_gallery',
                'title' => 'Галерия',
                'context' => 'side',
                'priority' => 'default',
                'fields' => [
                    'gallery' => [
                        'label' => 'Изображения',
                        'type' => 'gallery',
                        'description' => 'Добавете допълнителни изображения'
                    ]
                ]
            ],
            
            // STORES META BOX
            'stores' => [
                'id' => 'parfume_stores',
                'title' => 'Магазини и цени',
                'context' => 'side',
                'priority' => 'default',
                'fields' => [
                    'stores' => [
                        'label' => 'Магазини',
                        'type' => 'stores_repeater',
                        'description' => 'Добавете магазини с Product URLs за автоматично скрейпване',
                        'button_label' => 'Добави магазин',
                        'fields' => [
                            'store_id' => [
                                'label' => 'Магазин',
                                'type' => 'store_select',
                                'description' => 'Изберете магазин'
                            ],
                            'product_url' => [
                                'label' => 'Product URL',
                                'type' => 'url',
                                'placeholder' => 'https://example.com/product/parfum',
                                'description' => 'URL на продукта в магазина'
                            ],
                            'affiliate_url' => [
                                'label' => 'Affiliate URL',
                                'type' => 'url',
                                'placeholder' => 'https://example.com/aff/product',
                                'description' => 'Affiliate линк (с target="_blank" rel="nofollow")'
                            ],
                            'promo_code' => [
                                'label' => 'Promo Code',
                                'type' => 'text',
                                'placeholder' => 'DISCOUNT10',
                                'description' => 'Промо код за отстъпка'
                            ],
                            'promo_code_info' => [
                                'label' => 'Promo Code Info',
                                'type' => 'text',
                                'placeholder' => '-10% отстъпка',
                                'description' => 'Информация за промо кода'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];