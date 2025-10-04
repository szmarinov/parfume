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
                    'description' => 'Персонализирайте URL адресите. ВНИМАНИЕ: След промяна отидете на Settings → Permalinks → Save Changes',
                    'fields' => [
                        'parfume_slug' => [
                            'label' => 'Парфюми slug',
                            'type' => 'text',
                            'default' => 'parfiumi',
                            'placeholder' => 'parfiumi',
                            'description' => 'URL база за парфюми (напр. site.com/parfiumi/product)'
                        ],
                        'brands_slug' => [
                            'label' => 'Марки slug',
                            'type' => 'text',
                            'default' => 'marki',
                            'placeholder' => 'marki',
                            'description' => 'URL за марки таксономия'
                        ],
                        'gender_slug' => [
                            'label' => 'Пол slug',
                            'type' => 'text',
                            'default' => 'pol',
                            'placeholder' => 'pol',
                            'description' => 'URL за пол таксономия'
                        ],
                        'notes_slug' => [
                            'label' => 'Нотки slug',
                            'type' => 'text',
                            'default' => 'notki',
                            'placeholder' => 'notki',
                            'description' => 'URL за нотки таксономия'
                        ],
                        'perfumers_slug' => [
                            'label' => 'Парфюмери slug',
                            'type' => 'text',
                            'default' => 'parfiumeri',
                            'placeholder' => 'parfiumeri',
                            'description' => 'URL за парфюмери таксономия'
                        ],
                        'season_slug' => [
                            'label' => 'Сезон slug',
                            'type' => 'text',
                            'default' => 'sezon',
                            'placeholder' => 'sezon',
                            'description' => 'URL за сезон таксономия'
                        ],
                        'intensity_slug' => [
                            'label' => 'Интензивност slug',
                            'type' => 'text',
                            'default' => 'intenzivnost',
                            'placeholder' => 'intenzivnost',
                            'description' => 'URL за интензивност таксономия'
                        ],
                        'aroma_type_slug' => [
                            'label' => 'Тип аромат slug',
                            'type' => 'text',
                            'default' => 'tip-aromat',
                            'placeholder' => 'tip-aromat',
                            'description' => 'URL за тип аромат таксономия'
                        ]
                    ]
                ]
            ]
        ],
        
        'mobile' => [
            'title' => 'Мобилни',
            'description' => 'Настройки за мобилни устройства',
            'icon' => 'dashicons-smartphone',
            'sections' => [
                'mobile_panel' => [
                    'title' => 'Мобилен панел',
                    'description' => 'Настройки за фиксирания долен панел с магазини',
                    'fields' => [
                        'enable_mobile_panel' => [
                            'label' => 'Включи мобилен панел',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Показва фиксиран панел с магазини на мобилни устройства'
                        ],
                        'mobile_panel_zindex' => [
                            'label' => 'Z-Index',
                            'type' => 'number',
                            'default' => 9999,
                            'min' => 1,
                            'max' => 999999,
                            'description' => 'Z-index стойност на панела (по-високо = отгоре)'
                        ],
                        'mobile_panel_close_button' => [
                            'label' => 'Бутон за затваряне',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Показва бутон за затваряне на панела'
                        ]
                    ]
                ]
            ]
        ],
        
        'scraper' => [
            'title' => 'Scraper',
            'description' => 'Настройки за автоматично обновяване на цени',
            'icon' => 'dashicons-download',
            'sections' => [
                'scraper_settings' => [
                    'title' => 'Scraper конфигурация',
                    'description' => 'Автоматично обновяване на цени от магазините',
                    'fields' => [
                        'enable_scraper' => [
                            'label' => 'Включи scraper',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Активира функционалността за scraping на цени'
                        ],
                        'auto_update_prices' => [
                            'label' => 'Автоматично обновяване',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Автоматично обновява цените на зададен интервал'
                        ],
                        'update_interval' => [
                            'label' => 'Интервал (часове)',
                            'type' => 'number',
                            'default' => 24,
                            'min' => 1,
                            'max' => 168,
                            'description' => 'На колко часа да се обновяват цените (1-168 часа)'
                        ],
                        'scraper_timeout' => [
                            'label' => 'Timeout (секунди)',
                            'type' => 'number',
                            'default' => 30,
                            'min' => 5,
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
                'supported_stores' => [
                    'title' => 'Поддържани магазини',
                    'description' => 'Списък на магазините, които scraper-ът поддържа',
                    'fields' => [
                        'scraper_stores_info' => [
                            'label' => 'Налични магазини',
                            'type' => 'info',
                            'description' => 'Scraper-ът поддържа следните магазини:<br>• Notino.bg (пълна поддръжка)<br>• Parfimo.bg (базова поддръжка)<br>• Douglas.bg (базова поддръжка)<br>• Makeup.bg (базова поддръжка)'
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
                        ],
                        'comparison_bar_position' => [
                            'label' => 'Позиция на comparison bar',
                            'type' => 'select',
                            'default' => 'bottom',
                            'options' => [
                                'bottom' => 'Долу',
                                'top' => 'Горе'
                            ],
                            'description' => 'Къде да се показва comparison bar-а'
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
                'performance' => [
                    'title' => 'Производителност',
                    'description' => 'Настройки за оптимизация',
                    'fields' => [
                        'enable_caching' => [
                            'label' => 'Включи кеширане',
                            'type' => 'checkbox',
                            'default' => 1,
                            'description' => 'Кешира данните за по-добра производителност'
                        ],
                        'cache_duration' => [
                            'label' => 'Време на кеш (секунди)',
                            'type' => 'number',
                            'default' => 3600,
                            'min' => 60,
                            'max' => 86400,
                            'description' => 'Колко дълго да се пазят кешираните данни'
                        ]
                    ]
                ],
                'debug' => [
                    'title' => 'Дебъг',
                    'description' => 'Настройки за отстраняване на проблеми',
                    'fields' => [
                        'debug_mode' => [
                            'label' => 'Debug режим',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Включва допълнително логване в debug.log'
                        ],
                        'show_query_info' => [
                            'label' => 'Показвай query информация',
                            'type' => 'checkbox',
                            'default' => 0,
                            'description' => 'Показва информация за database queries (само за admins)'
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
                            'description' => 'ВНИМАНИЕ: Изтрива всички парфюми, таксономии и настройки при изтриване на плъгина'
                        ]
                    ]
                ]
            ]
        ]
    ]
];