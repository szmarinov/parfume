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
            'featured_image' => 'Главна снимка',
            'set_featured_image' => 'Задай главна снимка',
            'remove_featured_image' => 'Премахни главна снимка',
            'use_featured_image' => 'Използвай като главна снимка',
            'archives' => 'Архиви на парфюми',
            'insert_into_item' => 'Вмъкни в парфюм',
            'uploaded_to_this_item' => 'Качено към този парфюм',
            'filter_items_list' => 'Филтрирай списъка с парфюми',
            'items_list_navigation' => 'Навигация в списъка с парфюми',
            'items_list' => 'Списък с парфюми',
            'item_published' => 'Парфюмът е публикуван.',
            'item_published_privately' => 'Парфюмът е публикуван частно.',
            'item_reverted_to_draft' => 'Парфюмът е върнат в чернова.',
            'item_scheduled' => 'Парфюмът е планиран.',
            'item_updated' => 'Парфюмът е обновен.',
        ],
        
        /**
         * Meta Boxes Configuration
         */
        'meta_boxes' => [
            'basic_info' => [
                'id' => 'parfume_basic_info',
                'title' => 'Основна информация',
                'context' => 'normal',
                'priority' => 'high',
                'fields' => [
                    'rating' => [
                        'label' => 'Оценка',
                        'type' => 'number',
                        'min' => 0,
                        'max' => 10,
                        'step' => 0.1,
                        'default' => 0,
                        'description' => 'Обща оценка (0-10)'
                    ],
                    'gender_text' => [
                        'label' => 'Пол (текст)',
                        'type' => 'text',
                        'description' => 'Допълнителна информация за пол'
                    ],
                    'release_year' => [
                        'label' => 'Година на издаване',
                        'type' => 'number',
                        'min' => 1900,
                        'max' => 2100,
                        'step' => 1,
                        'description' => 'Годината в която е издаден парфюмът'
                    ],
                    'longevity' => [
                        'label' => 'Дълготрайност',
                        'type' => 'select',
                        'options' => [
                            '' => '-- Избери --',
                            'weak' => 'Слаба (1-2 часа)',
                            'moderate' => 'Средна (3-5 часа)',
                            'long' => 'Дълга (6-8 часа)',
                            'very_long' => 'Много дълга (8+ часа)',
                        ],
                        'description' => 'Колко време издържа парфюмът'
                    ],
                    'sillage' => [
                        'label' => 'Силаж',
                        'type' => 'select',
                        'options' => [
                            '' => '-- Избери --',
                            'intimate' => 'Интимен',
                            'moderate' => 'Среден',
                            'strong' => 'Силен',
                            'enormous' => 'Огромен',
                        ],
                        'description' => 'Интензивността на аромата'
                    ],
                    'bottle_size' => [
                        'label' => 'Обем на бутилката (ml)',
                        'type' => 'text',
                        'description' => 'Налични размери (напр. 50ml, 100ml)'
                    ]
                ]
            ],
            
            'aroma_chart' => [
                'id' => 'parfume_aroma_chart',
                'title' => 'Ароматна диаграма',
                'context' => 'normal',
                'priority' => 'default',
                'fields' => [
                    'aroma_chart' => [
                        'label' => 'Ароматни характеристики',
                        'type' => 'repeater',
                        'description' => 'Добавете ароматни характеристики и техните стойности',
                        'fields' => [
                            'name' => [
                                'label' => 'Характеристика',
                                'type' => 'text'
                            ],
                            'value' => [
                                'label' => 'Стойност (%)',
                                'type' => 'number',
                                'min' => 0,
                                'max' => 100,
                                'step' => 1
                            ]
                        ]
                    ]
                ]
            ],
            
            'pros_cons' => [
                'id' => 'parfume_pros_cons',
                'title' => 'Предимства и Недостатъци',
                'context' => 'normal',
                'priority' => 'default',
                'fields' => [
                    'pros' => [
                        'label' => 'Предимства',
                        'type' => 'textarea',
                        'rows' => 5,
                        'description' => 'Едно предимство на ред'
                    ],
                    'cons' => [
                        'label' => 'Недостатъци',
                        'type' => 'textarea',
                        'rows' => 5,
                        'description' => 'Един недостатък на ред'
                    ]
                ]
            ],
            
            'stores' => [
                'id' => 'parfume_stores',
                'title' => 'Магазини и Цени',
                'context' => 'side',
                'priority' => 'default',
                'fields' => [
                    'stores' => [
                        'label' => 'Магазини',
                        'type' => 'repeater',
                        'description' => 'Добавете магазини и цени',
                        'fields' => [
                            'name' => [
                                'label' => 'Име на магазин',
                                'type' => 'text'
                            ],
                            'url' => [
                                'label' => 'URL',
                                'type' => 'url'
                            ],
                            'price' => [
                                'label' => 'Цена',
                                'type' => 'number',
                                'min' => 0,
                                'step' => 0.01
                            ],
                            'currency' => [
                                'label' => 'Валута',
                                'type' => 'text',
                                'default' => 'BGN'
                            ],
                            'in_stock' => [
                                'label' => 'В наличност',
                                'type' => 'checkbox'
                            ],
                            'shipping_info' => [
                                'label' => 'Информация за доставка',
                                'type' => 'text'
                            ],
                            'coupon_code' => [
                                'label' => 'Код за отстъпка',
                                'type' => 'text'
                            ],
                            'promotion' => [
                                'label' => 'Промоция',
                                'type' => 'text'
                            ]
                        ]
                    ]
                ]
            ],
            
            'gallery' => [
                'id' => 'parfume_gallery',
                'title' => 'Галерия',
                'context' => 'side',
                'priority' => 'low',
                'fields' => [
                    'gallery' => [
                        'label' => 'Снимки',
                        'type' => 'gallery',
                        'description' => 'Добавете допълнителни снимки на парфюма'
                    ]
                ]
            ]
        ]
    ]
];