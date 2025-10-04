<?php
/**
 * Settings Configuration
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
    'option_name' => 'parfume_reviews_settings',
    
    'pages' => [
        'general' => [
            'title' => 'Основни',
            'description' => 'Основни настройки на плъгина',
            'icon' => 'dashicons-admin-generic',
            'sections' => [
                'basic' => [
                    'title' => 'Основна конфигурация',
                    'description' => 'Базови настройки за плъгина',
                    'fields' => [
                        'enable_plugin' => [
                            'label' => 'Активирай плъгина',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Включва/изключва функционалността на плъгина'
                        ],
                        'items_per_page' => [
                            'label' => 'Продукти на страница',
                            'type' => 'number',
                            'default' => 12,
                            'min' => 1,
                            'max' => 100,
                            'description' => 'Брой продукти за показване на archive страниците'
                        ],
                        'enable_ratings' => [
                            'label' => 'Включи оценки',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Показва звездни оценки'
                        ],
                        'enable_breadcrumbs' => [
                            'label' => 'Включи breadcrumbs',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Показва навигационни breadcrumbs'
                        ]
                    ]
                ]
            ]
        ],
        
        'urls' => [
            'title' => 'URL структура',
            'description' => 'Конфигурация на URL адреси',
            'icon' => 'dashicons-admin-links',
            'sections' => [
                'slugs' => [
                    'title' => 'URL Slugs',
                    'description' => 'Персонализирайте URL адресите',
                    'fields' => [
                        'parfume_slug' => [
                            'label' => 'Slug на парфюми',
                            'type' => 'text',
                            'default' => 'parfiumi',
                            'description' => 'Основен URL slug за парфюми',
                            'placeholder' => 'parfiumi'
                        ],
                        'brands_slug' => [
                            'label' => 'Slug на марки',
                            'type' => 'text',
                            'default' => 'marki',
                            'description' => 'URL slug за марки',
                            'placeholder' => 'marki'
                        ],
                        'gender_slug' => [
                            'label' => 'Slug на пол',
                            'type' => 'text',
                            'default' => 'gender',
                            'description' => 'URL slug за пол',
                            'placeholder' => 'gender'
                        ],
                        'notes_slug' => [
                            'label' => 'Slug на нотки',
                            'type' => 'text',
                            'default' => 'notki',
                            'description' => 'URL slug за ароматни нотки',
                            'placeholder' => 'notki'
                        ],
                        'perfumers_slug' => [
                            'label' => 'Slug на парфюмери',
                            'type' => 'text',
                            'default' => 'parfumeri',
                            'description' => 'URL slug за парфюмери',
                            'placeholder' => 'parfumeri'
                        ],
                        'season_slug' => [
                            'label' => 'Slug на сезони',
                            'type' => 'text',
                            'default' => 'season',
                            'description' => 'URL slug за сезони',
                            'placeholder' => 'season'
                        ],
                        'intensity_slug' => [
                            'label' => 'Slug на интензивност',
                            'type' => 'text',
                            'default' => 'intensity',
                            'description' => 'URL slug за интензивност',
                            'placeholder' => 'intensity'
                        ],
                        'aroma_type_slug' => [
                            'label' => 'Slug на видове аромат',
                            'type' => 'text',
                            'default' => 'aroma-type',
                            'description' => 'URL slug за видове аромат',
                            'placeholder' => 'aroma-type'
                        ]
                    ]
                ]
            ]
        ],
        
        'comparison' => [
            'title' => 'Сравнение',
            'description' => 'Настройки за сравнение на парфюми',
            'icon' => 'dashicons-forms',
            'sections' => [
                'comparison_config' => [
                    'title' => 'Конфигурация',
                    'description' => 'Настройки за функцията за сравнение',
                    'fields' => [
                        'enable_comparison' => [
                            'label' => 'Включи сравнение',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Активира функцията за сравнение'
                        ],
                        'max_compare_items' => [
                            'label' => 'Максимален брой за сравнение',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 10,
                            'description' => 'Максимален брой парфюми за сравнение'
                        ],
                        'comparison_page' => [
                            'label' => 'Страница за сравнение',
                            'type' => 'page_select',
                            'description' => 'Изберете страница за показване на сравнението'
                        ]
                    ]
                ]
            ]
        ],
        
        'advanced' => [
            'title' => 'Разширени',
            'description' => 'Разширени настройки',
            'icon' => 'dashicons-admin-tools',
            'sections' => [
                'debug' => [
                    'title' => 'Дебъг',
                    'description' => 'Настройки за отстраняване на проблеми',
                    'fields' => [
                        'debug_mode' => [
                            'label' => 'Debug режим',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Включва допълнително логване'
                        ]
                    ]
                ],
                'uninstall' => [
                    'title' => 'Деинсталация',
                    'description' => 'Настройки при изтриване',
                    'fields' => [
                        'delete_data_on_uninstall' => [
                            'label' => 'Изтрий данни при деинсталация',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'ВНИМАНИЕ: Изтрива всички данни при изтриване на плъгина'
                        ]
                    ]
                ]
            ]
        ]
    ]
];