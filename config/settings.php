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
            'title' => 'URL Структура',
            'description' => 'Настройки за URL адреси и permalink-ове',
            'icon' => 'dashicons-admin-links',
            'sections' => [
                'slugs' => [
                    'title' => 'URL Slugs',
                    'description' => 'Персонализирайте URL адресите. След промяна отидете на Настройки → Permalinks и запазете.',
                    'fields' => [
                        'parfume_slug' => [
                            'label' => 'Парфюми slug',
                            'type' => 'text',
                            'default' => 'parfiumi',
                            'placeholder' => 'parfiumi',
                            'description' => 'URL за архива на парфюмите'
                        ],
                        'brands_slug' => [
                            'label' => 'Марки slug',
                            'type' => 'text',
                            'default' => 'marki',
                            'placeholder' => 'marki',
                            'description' => 'URL за таксономията марки'
                        ],
                        'gender_slug' => [
                            'label' => 'Пол slug',
                            'type' => 'text',
                            'default' => 'gender',
                            'placeholder' => 'gender',
                            'description' => 'URL за таксономията пол'
                        ],
                        'aroma_type_slug' => [
                            'label' => 'Вид аромат slug',
                            'type' => 'text',
                            'default' => 'aroma-type',
                            'placeholder' => 'aroma-type',
                            'description' => 'URL за таксономията вид аромат'
                        ],
                        'season_slug' => [
                            'label' => 'Сезон slug',
                            'type' => 'text',
                            'default' => 'season',
                            'placeholder' => 'season',
                            'description' => 'URL за таксономията сезон'
                        ],
                        'intensity_slug' => [
                            'label' => 'Интензивност slug',
                            'type' => 'text',
                            'default' => 'intensity',
                            'placeholder' => 'intensity',
                            'description' => 'URL за таксономията интензивност'
                        ],
                        'notes_slug' => [
                            'label' => 'Нотки slug',
                            'type' => 'text',
                            'default' => 'notes',
                            'placeholder' => 'notes',
                            'description' => 'URL за таксономията нотки'
                        ],
                        'perfumers_slug' => [
                            'label' => 'Парфюмеристи slug',
                            'type' => 'text',
                            'default' => 'perfumers',
                            'placeholder' => 'perfumers',
                            'description' => 'URL за таксономията парфюмеристи'
                        ]
                    ]
                ]
            ]
        ],
        
        'stores' => [
            'title' => 'Магазини',
            'description' => 'Управление на affiliate магазини',
            'icon' => 'dashicons-store',
            'sections' => [
                'store_management' => [
                    'title' => 'Управление на магазини',
                    'description' => 'Списък с всички добавени магазини и техните настройки',
                    'fields' => [
                        'stores_list_info' => [
                            'label' => 'Добавени магазини',
                            'type' => 'info',
                            'description' => 'Тук ще виждате списък с всички добавени магазини. Използвайте бутона долу за да добавите нов магазин.'
                        ]
                    ]
                ]
            ]
        ],
        
        'scraper' => [
            'title' => 'Product Scraper',
            'description' => 'Настройки за автоматично скрейпване на цени и информация',
            'icon' => 'dashicons-download',
            'sections' => [
                'scraper_settings' => [
                    'title' => 'Основни настройки',
                    'description' => 'Конфигурация на скрейпър системата',
                    'fields' => [
                        'enable_scraper' => [
                            'label' => 'Активирай скрейпъра',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Включва/изключва автоматичното скрейпване'
                        ],
                        'scrape_interval' => [
                            'label' => 'Интервал за скрейпване (часове)',
                            'type' => 'number',
                            'default' => 12,
                            'min' => 1,
                            'max' => 168,
                            'description' => 'На колко часа да се обновяват цените (1-168 часа)'
                        ],
                        'scrape_batch_size' => [
                            'label' => 'Batch размер',
                            'type' => 'number',
                            'default' => 10,
                            'min' => 1,
                            'max' => 100,
                            'description' => 'Брой URL-и за обработка при всяко изпълнение'
                        ],
                        'scrape_timeout' => [
                            'label' => 'Timeout (секунди)',
                            'type' => 'number',
                            'default' => 30,
                            'min' => 10,
                            'max' => 120,
                            'description' => 'Максимално време за scraping на един магазин'
                        ],
                        'scraper_user_agent' => [
                            'label' => 'User Agent',
                            'type' => 'text',
                            'default' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'placeholder' => 'User Agent string',
                            'description' => 'User Agent за HTTP заявките'
                        ]
                    ]
                ],
                'scraper_monitor' => [
                    'title' => 'Monitor',
                    'description' => 'Преглед и мониторинг на скрейпнати данни',
                    'fields' => [
                        'scraper_monitor_info' => [
                            'label' => 'Статус на скрейпването',
                            'type' => 'info',
                            'description' => 'Виж подробна информация за всички Product URLs в секцията Monitor по-долу'
                        ]
                    ]
                ],
                'scraper_test_tool' => [
                    'title' => 'Test Tool',
                    'description' => 'Инструмент за тестване и конфигурация на schema',
                    'fields' => [
                        'scraper_test_info' => [
                            'label' => 'Schema конфигурация',
                            'type' => 'info',
                            'description' => 'Използвайте Test Tool за да конфигурирате schema за всеки магазин'
                        ]
                    ]
                ]
            ]
        ],
        
        'mobile' => [
            'title' => 'Мобилни настройки',
            'description' => 'Настройки за мобилни устройства',
            'icon' => 'dashicons-smartphone',
            'sections' => [
                'mobile_panel' => [
                    'title' => 'Фиксиран панел за магазини',
                    'description' => 'Поведение на "Колона 2" на мобилни устройства',
                    'fields' => [
                        'enable_mobile_fixed' => [
                            'label' => 'Фиксиран панел',
                            'type' => 'checkbox',
                            'default' => 1,
                            'checkbox_label' => 'Показвай фиксиран магазин в долната част на екрана',
                            'description' => 'При мобилни устройства първият магазин е винаги фиксиран в долу'
                        ],
                        'enable_mobile_close_button' => [
                            'label' => 'Бутон за затваряне',
                            'type' => 'checkbox',
                            'default' => 1,
                            'checkbox_label' => 'Позволявай скриване на "Колона 2" чрез бутон "X"',
                            'description' => 'Показва бутон X за скриване на целия панел'
                        ],
                        'mobile_panel_zindex' => [
                            'label' => 'Z-index',
                            'type' => 'number',
                            'default' => 9999,
                            'min' => 1,
                            'max' => 999999,
                            'description' => 'Z-index на панела (за избягване на припокриване)'
                        ],
                        'mobile_panel_offset' => [
                            'label' => 'Вертикален offset (px)',
                            'type' => 'number',
                            'default' => 0,
                            'min' => 0,
                            'max' => 500,
                            'description' => 'Отместване отдолу (ако има cookie bar или друг елемент)'
                        ]
                    ]
                ]
            ]
        ],
        
        'comparison' => [
            'title' => 'Сравнение',
            'description' => 'Настройки за сравнение на парфюми',
            'icon' => 'dashicons-columns',
            'sections' => [
                'comparison_settings' => [
                    'title' => 'Настройки за сравнение',
                    'description' => 'Конфигурация на функционалността за сравнение',
                    'fields' => [
                        'enable_comparison' => [
                            'label' => 'Включи сравнение',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Активира функционалността за сравнение'
                        ],
                        'max_comparison_items' => [
                            'label' => 'Максимален брой парфюми',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 10,
                            'description' => 'Максимален брой парфюми за сравнение едновременно'
                        ],
                        'comparison_page' => [
                            'label' => 'Страница за сравнение',
                            'type' => 'page_select',
                            'default' => '',
                            'description' => 'Изберете страница за пълното сравнение (опционално)'
                        ]
                    ]
                ]
            ]
        ],
        
        'advanced' => [
            'title' => 'Разширени',
            'description' => 'Разширени настройки и инструменти',
            'icon' => 'dashicons-admin-tools',
            'sections' => [
                'display_settings' => [
                    'title' => 'Настройки за показване',
                    'description' => 'Конфигурация на визуализацията',
                    'fields' => [
                        'similar_parfumes_count' => [
                            'label' => 'Брой подобни парфюми',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 12,
                            'description' => 'Брой подобни парфюми за показване'
                        ],
                        'similar_parfumes_columns' => [
                            'label' => 'Колони за подобни парфюми',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 6,
                            'description' => 'Брой колони за показване на подобни парфюми'
                        ],
                        'recently_viewed_count' => [
                            'label' => 'Брой наскоро разгледани',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 12,
                            'description' => 'Брой наскоро разгледани парфюми'
                        ],
                        'recently_viewed_columns' => [
                            'label' => 'Колони за наскоро разгледани',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 6,
                            'description' => 'Брой колони за показване на наскоро разгледани'
                        ],
                        'brand_parfumes_count' => [
                            'label' => 'Брой парфюми от марката',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 12,
                            'description' => 'Брой парфюми от същата марка за показване'
                        ],
                        'brand_parfumes_columns' => [
                            'label' => 'Колони за парфюми от марката',
                            'type' => 'number',
                            'default' => 4,
                            'min' => 2,
                            'max' => 6,
                            'description' => 'Брой колони за показване на парфюми от марката'
                        ]
                    ]
                ],
                'performance' => [
                    'title' => 'Производителност',
                    'description' => 'Настройки за оптимизация',
                    'fields' => [
                        'enable_cache' => [
                            'label' => 'Включи кеширане',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Кешира скрейпнати данни и заявки'
                        ],
                        'cache_duration' => [
                            'label' => 'Време на кеша (часове)',
                            'type' => 'number',
                            'default' => 24,
                            'min' => 1,
                            'max' => 720,
                            'description' => 'Колко време да се пази кеша'
                        ]
                    ]
                ],
                'debug' => [
                    'title' => 'Debug режим',
                    'description' => 'Настройки за debugging',
                    'fields' => [
                        'enable_debug_mode' => [
                            'label' => 'Debug режим',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Записва подробни логове (само за development)'
                        ],
                        'debug_scraper' => [
                            'label' => 'Debug Scraper',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Записва подробни scraper логове'
                        ]
                    ]
                ]
            ]
        ]
    ]
];