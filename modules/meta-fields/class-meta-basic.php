<?php
/**
 * Parfume Catalog Meta Basic
 * 
 * Управление на основни мета полета за парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Meta_Basic {
    
    /**
     * Конструктор
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Добавяне на мета boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_basic_info',
            __('Основна информация', 'parfume-catalog'),
            array($this, 'render_basic_info_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_characteristics',
            __('Характеристики на аромата', 'parfume-catalog'),
            array($this, 'render_characteristics_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_pros_cons',
            __('Предимства и недостатъци', 'parfume-catalog'),
            array($this, 'render_pros_cons_meta_box'),
            'parfumes',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-catalog'),
            array($this, 'render_additional_info_meta_box'),
            'parfumes',
            'side',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('parfume-meta-basic', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/meta-basic.js', 
                array('jquery'), 
                PARFUME_CATALOG_VERSION, 
                true
            );
            
            wp_localize_script('parfume-meta-basic', 'parfumeMetaBasic', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_meta_basic'),
                'texts' => array(
                    'add_item' => __('Добави', 'parfume-catalog'),
                    'remove_item' => __('Премахни', 'parfume-catalog'),
                    'confirm_remove' => __('Сигурни ли сте, че искате да премахнете този елемент?', 'parfume-catalog')
                )
            ));
            
            wp_enqueue_style('parfume-meta-basic', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/meta-basic.css', 
                array(), 
                PARFUME_CATALOG_VERSION
            );
        }
    }
    
    /**
     * Render basic info meta box
     */
    public function render_basic_info_meta_box($post) {
        wp_nonce_field('parfume_basic_meta_nonce', 'parfume_basic_meta_nonce_field');
        
        // Get saved values
        $suitable_spring = get_post_meta($post->ID, '_parfume_suitable_spring', true);
        $suitable_summer = get_post_meta($post->ID, '_parfume_suitable_summer', true);
        $suitable_autumn = get_post_meta($post->ID, '_parfume_suitable_autumn', true);
        $suitable_winter = get_post_meta($post->ID, '_parfume_suitable_winter', true);
        $suitable_day = get_post_meta($post->ID, '_parfume_suitable_day', true);
        $suitable_night = get_post_meta($post->ID, '_parfume_suitable_night', true);
        $launch_year = get_post_meta($post->ID, '_parfume_launch_year', true);
        $perfumer = get_post_meta($post->ID, '_parfume_perfumer', true);
        $concentration = get_post_meta($post->ID, '_parfume_concentration', true);
        $gender_target = get_post_meta($post->ID, '_parfume_gender_target', true);
        $age_target = get_post_meta($post->ID, '_parfume_age_target', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('Подходящ сезон', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <div class="season-checkboxes">
                        <label>
                            <input type="checkbox" name="parfume_suitable_spring" value="1" <?php checked($suitable_spring, 1); ?> />
                            <span class="season-icon">🌸</span>
                            <?php _e('Пролет', 'parfume-catalog'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="parfume_suitable_summer" value="1" <?php checked($suitable_summer, 1); ?> />
                            <span class="season-icon">☀️</span>
                            <?php _e('Лято', 'parfume-catalog'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="parfume_suitable_autumn" value="1" <?php checked($suitable_autumn, 1); ?> />
                            <span class="season-icon">🍂</span>
                            <?php _e('Есен', 'parfume-catalog'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="parfume_suitable_winter" value="1" <?php checked($suitable_winter, 1); ?> />
                            <span class="season-icon">❄️</span>
                            <?php _e('Зима', 'parfume-catalog'); ?>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php _e('Подходящо време', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <div class="time-checkboxes">
                        <label>
                            <input type="checkbox" name="parfume_suitable_day" value="1" <?php checked($suitable_day, 1); ?> />
                            <span class="time-icon">🌅</span>
                            <?php _e('Ден', 'parfume-catalog'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="parfume_suitable_night" value="1" <?php checked($suitable_night, 1); ?> />
                            <span class="time-icon">🌙</span>
                            <?php _e('Нощ', 'parfume-catalog'); ?>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_launch_year"><?php _e('Година на излизане', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="parfume_launch_year" 
                           name="parfume_launch_year" 
                           value="<?php echo esc_attr($launch_year); ?>" 
                           min="1900" 
                           max="<?php echo date('Y') + 1; ?>" 
                           class="small-text" />
                    <p class="description"><?php _e('Година, в която е пуснат парфюмът на пазара', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_perfumer"><?php _e('Парфюмерист', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="parfume_perfumer" 
                           name="parfume_perfumer" 
                           value="<?php echo esc_attr($perfumer); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Име на парфюмериста/създателя', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_concentration"><?php _e('Концентрация', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_concentration" name="parfume_concentration">
                        <option value=""><?php _e('Изберете концентрация', 'parfume-catalog'); ?></option>
                        <option value="parfum" <?php selected($concentration, 'parfum'); ?>><?php _e('Parfum (20-40%)', 'parfume-catalog'); ?></option>
                        <option value="edp" <?php selected($concentration, 'edp'); ?>><?php _e('Eau de Parfum (10-20%)', 'parfume-catalog'); ?></option>
                        <option value="edt" <?php selected($concentration, 'edt'); ?>><?php _e('Eau de Toilette (5-15%)', 'parfume-catalog'); ?></option>
                        <option value="edc" <?php selected($concentration, 'edc'); ?>><?php _e('Eau de Cologne (2-5%)', 'parfume-catalog'); ?></option>
                        <option value="edm" <?php selected($concentration, 'edm'); ?>><?php _e('Eau de Mist (1-3%)', 'parfume-catalog'); ?></option>
                    </select>
                    <p class="description"><?php _e('Концентрация на ароматни масла', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_gender_target"><?php _e('Целева група (пол)', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_gender_target" name="parfume_gender_target">
                        <option value=""><?php _e('Изберете целева група', 'parfume-catalog'); ?></option>
                        <option value="unisex" <?php selected($gender_target, 'unisex'); ?>><?php _e('Унисекс', 'parfume-catalog'); ?></option>
                        <option value="women" <?php selected($gender_target, 'women'); ?>><?php _e('Дамски', 'parfume-catalog'); ?></option>
                        <option value="men" <?php selected($gender_target, 'men'); ?>><?php _e('Мъжки', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_age_target"><?php _e('Целева възрастова група', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_age_target" name="parfume_age_target">
                        <option value=""><?php _e('Изберете възрастова група', 'parfume-catalog'); ?></option>
                        <option value="teens" <?php selected($age_target, 'teens'); ?>><?php _e('Тийнейджъри (13-19)', 'parfume-catalog'); ?></option>
                        <option value="young_adults" <?php selected($age_target, 'young_adults'); ?>><?php _e('Млади възрастни (20-35)', 'parfume-catalog'); ?></option>
                        <option value="adults" <?php selected($age_target, 'adults'); ?>><?php _e('Възрастни (36-55)', 'parfume-catalog'); ?></option>
                        <option value="mature" <?php selected($age_target, 'mature'); ?>><?php _e('Зрели (55+)', 'parfume-catalog'); ?></option>
                        <option value="all_ages" <?php selected($age_target, 'all_ages'); ?>><?php _e('Всички възрасти', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        
        <style>
        .season-checkboxes, .time-checkboxes {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .season-checkboxes label, .time-checkboxes label {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .season-checkboxes label:hover, .time-checkboxes label:hover {
            background: #f0f0f1;
            border-color: #0073aa;
        }
        
        .season-checkboxes input:checked + .season-icon,
        .time-checkboxes input:checked + .time-icon {
            filter: brightness(1.2);
        }
        
        .season-icon, .time-icon {
            font-size: 16px;
        }
        </style>
        <?php
    }
    
    /**
     * Render characteristics meta box
     */
    public function render_characteristics_meta_box($post) {
        // Get saved values
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $price_category = get_post_meta($post->ID, '_parfume_price_category', true);
        $gender_perception = get_post_meta($post->ID, '_parfume_gender_perception', true);
        $uniqueness = get_post_meta($post->ID, '_parfume_uniqueness', true);
        $versatility = get_post_meta($post->ID, '_parfume_versatility', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_longevity"><?php _e('Дълготрайност', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_longevity" name="parfume_longevity">
                        <option value=""><?php _e('Изберете дълготрайност', 'parfume-catalog'); ?></option>
                        <option value="very_weak" <?php selected($longevity, 'very_weak'); ?>><?php _e('Много слаб (1-2 часа)', 'parfume-catalog'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаб (2-4 часа)', 'parfume-catalog'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерен (4-6 часа)', 'parfume-catalog'); ?></option>
                        <option value="long_lasting" <?php selected($longevity, 'long_lasting'); ?>><?php _e('Траен (6-8 часа)', 'parfume-catalog'); ?></option>
                        <option value="eternal" <?php selected($longevity, 'eternal'); ?>><?php _e('Изключително траен (8+ часа)', 'parfume-catalog'); ?></option>
                    </select>
                    <div class="longevity-bar">
                        <div class="longevity-indicator" data-level="<?php echo esc_attr($longevity); ?>"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_sillage"><?php _e('Ароматна следа', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_sillage" name="parfume_sillage">
                        <option value=""><?php _e('Изберете интензивност', 'parfume-catalog'); ?></option>
                        <option value="intimate" <?php selected($sillage, 'intimate'); ?>><?php _e('Слаба (интимна)', 'parfume-catalog'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерена', 'parfume-catalog'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силна', 'parfume-catalog'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромна', 'parfume-catalog'); ?></option>
                    </select>
                    <div class="sillage-bar">
                        <div class="sillage-indicator" data-level="<?php echo esc_attr($sillage); ?>"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_price_category"><?php _e('Ценова категория', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_price_category" name="parfume_price_category">
                        <option value=""><?php _e('Изберете ценова категория', 'parfume-catalog'); ?></option>
                        <option value="budget" <?php selected($price_category, 'budget'); ?>><?php _e('Евтин (до 50 лв)', 'parfume-catalog'); ?></option>
                        <option value="affordable" <?php selected($price_category, 'affordable'); ?>><?php _e('Добра цена (50-100 лв)', 'parfume-catalog'); ?></option>
                        <option value="moderate" <?php selected($price_category, 'moderate'); ?>><?php _e('Приемлива цена (100-200 лв)', 'parfume-catalog'); ?></option>
                        <option value="expensive" <?php selected($price_category, 'expensive'); ?>><?php _e('Скъп (200-400 лв)', 'parfume-catalog'); ?></option>
                        <option value="luxury" <?php selected($price_category, 'luxury'); ?>><?php _e('Прекалено скъп (400+ лв)', 'parfume-catalog'); ?></option>
                    </select>
                    <div class="price-bar">
                        <div class="price-indicator" data-level="<?php echo esc_attr($price_category); ?>"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_gender_perception"><?php _e('Полово възприятие', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_gender_perception" name="parfume_gender_perception">
                        <option value=""><?php _e('Изберете възприятие', 'parfume-catalog'); ?></option>
                        <option value="very_feminine" <?php selected($gender_perception, 'very_feminine'); ?>><?php _e('Много женствен', 'parfume-catalog'); ?></option>
                        <option value="feminine" <?php selected($gender_perception, 'feminine'); ?>><?php _e('Женствен', 'parfume-catalog'); ?></option>
                        <option value="unisex" <?php selected($gender_perception, 'unisex'); ?>><?php _e('Унисекс', 'parfume-catalog'); ?></option>
                        <option value="masculine" <?php selected($gender_perception, 'masculine'); ?>><?php _e('Мъжествен', 'parfume-catalog'); ?></option>
                        <option value="very_masculine" <?php selected($gender_perception, 'very_masculine'); ?>><?php _e('Много мъжествен', 'parfume-catalog'); ?></option>
                    </select>
                    <div class="gender-bar">
                        <div class="gender-indicator" data-level="<?php echo esc_attr($gender_perception); ?>"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_uniqueness"><?php _e('Уникалност', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="range" 
                           id="parfume_uniqueness" 
                           name="parfume_uniqueness" 
                           value="<?php echo esc_attr($uniqueness ?: 50); ?>" 
                           min="0" 
                           max="100" 
                           step="10" 
                           class="uniqueness-slider" />
                    <div class="slider-labels">
                        <span><?php _e('Обичаен', 'parfume-catalog'); ?></span>
                        <span id="uniqueness-value"><?php echo esc_html($uniqueness ?: 50); ?>%</span>
                        <span><?php _e('Уникален', 'parfume-catalog'); ?></span>
                    </div>
                    <p class="description"><?php _e('Колко уникален е ароматът спрямо други парфюми', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_versatility"><?php _e('Универсалност', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="range" 
                           id="parfume_versatility" 
                           name="parfume_versatility" 
                           value="<?php echo esc_attr($versatility ?: 50); ?>" 
                           min="0" 
                           max="100" 
                           step="10" 
                           class="versatility-slider" />
                    <div class="slider-labels">
                        <span><?php _e('Специфичен', 'parfume-catalog'); ?></span>
                        <span id="versatility-value"><?php echo esc_html($versatility ?: 50); ?>%</span>
                        <span><?php _e('Универсален', 'parfume-catalog'); ?></span>
                    </div>
                    <p class="description"><?php _e('Подходящ за различни случаи и сезони', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        
        <style>
        .longevity-bar, .sillage-bar, .price-bar, .gender-bar {
            margin-top: 8px;
            height: 20px;
            background: #f0f0f1;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .longevity-indicator, .sillage-indicator, .price-indicator, .gender-indicator {
            height: 100%;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: linear-gradient(90deg, #e74c3c 0%, #f39c12 25%, #f1c40f 50%, #27ae60 75%, #2ecc71 100%);
        }
        
        .longevity-indicator[data-level="very_weak"] { width: 20%; }
        .longevity-indicator[data-level="weak"] { width: 40%; }
        .longevity-indicator[data-level="moderate"] { width: 60%; }
        .longevity-indicator[data-level="long_lasting"] { width: 80%; }
        .longevity-indicator[data-level="eternal"] { width: 100%; }
        
        .sillage-indicator[data-level="intimate"] { width: 25%; }
        .sillage-indicator[data-level="moderate"] { width: 50%; }
        .sillage-indicator[data-level="strong"] { width: 75%; }
        .sillage-indicator[data-level="enormous"] { width: 100%; }
        
        .price-indicator[data-level="budget"] { width: 20%; background: #27ae60; }
        .price-indicator[data-level="affordable"] { width: 40%; background: #2ecc71; }
        .price-indicator[data-level="moderate"] { width: 60%; background: #f39c12; }
        .price-indicator[data-level="expensive"] { width: 80%; background: #e67e22; }
        .price-indicator[data-level="luxury"] { width: 100%; background: #e74c3c; }
        
        .gender-indicator[data-level="very_feminine"] { width: 100%; background: #e91e63; }
        .gender-indicator[data-level="feminine"] { width: 75%; background: #f06292; }
        .gender-indicator[data-level="unisex"] { width: 50%; background: #9c27b0; }
        .gender-indicator[data-level="masculine"] { width: 75%; background: #2196f3; }
        .gender-indicator[data-level="very_masculine"] { width: 100%; background: #1976d2; }
        
        .uniqueness-slider, .versatility-slider {
            width: 100%;
            margin: 10px 0;
        }
        
        .slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
        }
        
        #uniqueness-value, #versatility-value {
            font-weight: bold;
            color: #0073aa;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Update slider values
            $('#parfume_uniqueness').on('input', function() {
                $('#uniqueness-value').text($(this).val() + '%');
            });
            
            $('#parfume_versatility').on('input', function() {
                $('#versatility-value').text($(this).val() + '%');
            });
            
            // Update bars when selects change
            $('select[name="parfume_longevity"]').change(function() {
                $('.longevity-indicator').attr('data-level', $(this).val());
            });
            
            $('select[name="parfume_sillage"]').change(function() {
                $('.sillage-indicator').attr('data-level', $(this).val());
            });
            
            $('select[name="parfume_price_category"]').change(function() {
                $('.price-indicator').attr('data-level', $(this).val());
            });
            
            $('select[name="parfume_gender_perception"]').change(function() {
                $('.gender-indicator').attr('data-level', $(this).val());
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render pros and cons meta box
     */
    public function render_pros_cons_meta_box($post) {
        $advantages = get_post_meta($post->ID, '_parfume_advantages', true) ?: array();
        $disadvantages = get_post_meta($post->ID, '_parfume_disadvantages', true) ?: array();
        ?>
        <div class="pros-cons-container">
            <div class="pros-cons-row">
                <!-- Advantages -->
                <div class="advantages-section">
                    <h4><span class="dashicons dashicons-yes"></span> <?php _e('Предимства', 'parfume-catalog'); ?></h4>
                    <div class="advantages-list" id="advantages-list">
                        <?php foreach ($advantages as $index => $advantage): ?>
                            <div class="advantage-item">
                                <input type="text" 
                                       name="parfume_advantages[]" 
                                       value="<?php echo esc_attr($advantage); ?>" 
                                       placeholder="<?php _e('Въведете предимство', 'parfume-catalog'); ?>" />
                                <button type="button" class="remove-advantage button button-small">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-advantage button button-secondary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Добави предимство', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <!-- Disadvantages -->
                <div class="disadvantages-section">
                    <h4><span class="dashicons dashicons-no"></span> <?php _e('Недостатъци', 'parfume-catalog'); ?></h4>
                    <div class="disadvantages-list" id="disadvantages-list">
                        <?php foreach ($disadvantages as $index => $disadvantage): ?>
                            <div class="disadvantage-item">
                                <input type="text" 
                                       name="parfume_disadvantages[]" 
                                       value="<?php echo esc_attr($disadvantage); ?>" 
                                       placeholder="<?php _e('Въведете недостатък', 'parfume-catalog'); ?>" />
                                <button type="button" class="remove-disadvantage button button-small">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-disadvantage button button-secondary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Добави недостатък', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .pros-cons-container {
            margin: 15px 0;
        }
        
        .pros-cons-row {
            display: flex;
            gap: 20px;
        }
        
        .advantages-section, .disadvantages-section {
            flex: 1;
        }
        
        .advantages-section h4 {
            color: #27ae60;
            margin-bottom: 15px;
        }
        
        .disadvantages-section h4 {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        
        .advantage-item, .disadvantage-item {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            align-items: center;
        }
        
        .advantage-item input, .disadvantage-item input {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .advantage-item input:focus, .disadvantage-item input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
        }
        
        .remove-advantage, .remove-disadvantage {
            min-width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-advantage, .add-disadvantage {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .advantages-list, .disadvantages-list {
            min-height: 50px;
            border: 1px dashed #ddd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .advantages-list:empty::before {
            content: "<?php _e('Няма добавени предимства', 'parfume-catalog'); ?>";
            color: #999;
            font-style: italic;
            display: block;
            text-align: center;
            padding: 20px 0;
        }
        
        .disadvantages-list:empty::before {
            content: "<?php _e('Няма добавени недостатъци', 'parfume-catalog'); ?>";
            color: #999;
            font-style: italic;
            display: block;
            text-align: center;
            padding: 20px 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Add advantage
            $('.add-advantage').click(function() {
                var newItem = '<div class="advantage-item">' +
                    '<input type="text" name="parfume_advantages[]" value="" placeholder="<?php _e('Въведете предимство', 'parfume-catalog'); ?>" />' +
                    '<button type="button" class="remove-advantage button button-small">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                    '</div>';
                $('#advantages-list').append(newItem);
            });
            
            // Add disadvantage
            $('.add-disadvantage').click(function() {
                var newItem = '<div class="disadvantage-item">' +
                    '<input type="text" name="parfume_disadvantages[]" value="" placeholder="<?php _e('Въведете недостатък', 'parfume-catalog'); ?>" />' +
                    '<button type="button" class="remove-disadvantage button button-small">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                    '</div>';
                $('#disadvantages-list').append(newItem);
            });
            
            // Remove advantage
            $(document).on('click', '.remove-advantage', function() {
                $(this).closest('.advantage-item').remove();
            });
            
            // Remove disadvantage
            $(document).on('click', '.remove-disadvantage', function() {
                $(this).closest('.disadvantage-item').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render additional info meta box
     */
    public function render_additional_info_meta_box($post) {
        $bottle_size = get_post_meta($post->ID, '_parfume_bottle_size', true);
        $availability = get_post_meta($post->ID, '_parfume_availability', true);
        $limited_edition = get_post_meta($post->ID, '_parfume_limited_edition', true);
        $reformulated = get_post_meta($post->ID, '_parfume_reformulated', true);
        $discontinued = get_post_meta($post->ID, '_parfume_discontinued', true);
        $seasonal = get_post_meta($post->ID, '_parfume_seasonal', true);
        $notes = get_post_meta($post->ID, '_parfume_internal_notes', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_bottle_size"><?php _e('Размери на бутилката', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="parfume_bottle_size" 
                           name="parfume_bottle_size" 
                           value="<?php echo esc_attr($bottle_size); ?>" 
                           placeholder="<?php _e('30ml, 50ml, 100ml', 'parfume-catalog'); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Налични размери, разделени със запетая', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_availability"><?php _e('Наличност', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_availability" name="parfume_availability">
                        <option value="available" <?php selected($availability, 'available'); ?>><?php _e('Наличен', 'parfume-catalog'); ?></option>
                        <option value="limited" <?php selected($availability, 'limited'); ?>><?php _e('Ограничена наличност', 'parfume-catalog'); ?></option>
                        <option value="pre_order" <?php selected($availability, 'pre_order'); ?>><?php _e('Предварителна поръчка', 'parfume-catalog'); ?></option>
                        <option value="out_of_stock" <?php selected($availability, 'out_of_stock'); ?>><?php _e('Изчерпан', 'parfume-catalog'); ?></option>
                        <option value="discontinued" <?php selected($availability, 'discontinued'); ?>><?php _e('Прекратен', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Специални характеристики', 'parfume-catalog'); ?></th>
                <td>
                    <div class="special-characteristics">
                        <label>
                            <input type="checkbox" name="parfume_limited_edition" value="1" <?php checked($limited_edition, 1); ?> />
                            <?php _e('Лимитирано издание', 'parfume-catalog'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="parfume_reformulated" value="1" <?php checked($reformulated, 1); ?> />
                            <?php _e('Преформулиран', 'parfume-catalog'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="parfume_discontinued" value="1" <?php checked($discontinued, 1); ?> />
                            <?php _e('Прекратено производство', 'parfume-catalog'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="parfume_seasonal" value="1" <?php checked($seasonal, 1); ?> />
                            <?php _e('Сезонен аромат', 'parfume-catalog'); ?>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_internal_notes"><?php _e('Вътрешни бележки', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <textarea id="parfume_internal_notes" 
                              name="parfume_internal_notes" 
                              rows="4" 
                              class="large-text"
                              placeholder="<?php _e('Вътрешни бележки за администратори...', 'parfume-catalog'); ?>"><?php echo esc_textarea($notes); ?></textarea>
                    <p class="description"><?php _e('Тези бележки са видими само в администраторския панел', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        
        <style>
        .special-characteristics {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .special-characteristics label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        </style>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['parfume_basic_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['parfume_basic_meta_nonce_field'], 'parfume_basic_meta_nonce')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save basic info fields
        $this->save_checkbox_field($post_id, 'parfume_suitable_spring', '_parfume_suitable_spring');
        $this->save_checkbox_field($post_id, 'parfume_suitable_summer', '_parfume_suitable_summer');
        $this->save_checkbox_field($post_id, 'parfume_suitable_autumn', '_parfume_suitable_autumn');
        $this->save_checkbox_field($post_id, 'parfume_suitable_winter', '_parfume_suitable_winter');
        $this->save_checkbox_field($post_id, 'parfume_suitable_day', '_parfume_suitable_day');
        $this->save_checkbox_field($post_id, 'parfume_suitable_night', '_parfume_suitable_night');
        
        $this->save_text_field($post_id, 'parfume_launch_year', '_parfume_launch_year');
        $this->save_text_field($post_id, 'parfume_perfumer', '_parfume_perfumer');
        $this->save_text_field($post_id, 'parfume_concentration', '_parfume_concentration');
        $this->save_text_field($post_id, 'parfume_gender_target', '_parfume_gender_target');
        $this->save_text_field($post_id, 'parfume_age_target', '_parfume_age_target');
        
        // Save characteristics
        $this->save_text_field($post_id, 'parfume_longevity', '_parfume_longevity');
        $this->save_text_field($post_id, 'parfume_sillage', '_parfume_sillage');
        $this->save_text_field($post_id, 'parfume_price_category', '_parfume_price_category');
        $this->save_text_field($post_id, 'parfume_gender_perception', '_parfume_gender_perception');
        $this->save_number_field($post_id, 'parfume_uniqueness', '_parfume_uniqueness');
        $this->save_number_field($post_id, 'parfume_versatility', '_parfume_versatility');
        
        // Save pros and cons
        $this->save_array_field($post_id, 'parfume_advantages', '_parfume_advantages');
        $this->save_array_field($post_id, 'parfume_disadvantages', '_parfume_disadvantages');
        
        // Save additional info
        $this->save_text_field($post_id, 'parfume_bottle_size', '_parfume_bottle_size');
        $this->save_text_field($post_id, 'parfume_availability', '_parfume_availability');
        $this->save_checkbox_field($post_id, 'parfume_limited_edition', '_parfume_limited_edition');
        $this->save_checkbox_field($post_id, 'parfume_reformulated', '_parfume_reformulated');
        $this->save_checkbox_field($post_id, 'parfume_discontinued', '_parfume_discontinued');
        $this->save_checkbox_field($post_id, 'parfume_seasonal', '_parfume_seasonal');
        $this->save_textarea_field($post_id, 'parfume_internal_notes', '_parfume_internal_notes');
    }
    
    /**
     * Helper methods for saving different field types
     */
    private function save_text_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    private function save_textarea_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_textarea_field($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    private function save_number_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name])) {
            $value = intval($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    private function save_checkbox_field($post_id, $field_name, $meta_key) {
        $value = isset($_POST[$field_name]) ? 1 : 0;
        update_post_meta($post_id, $meta_key, $value);
    }
    
    private function save_array_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
            $values = array_map('sanitize_text_field', $_POST[$field_name]);
            $values = array_filter($values); // Remove empty values
            update_post_meta($post_id, $meta_key, $values);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }
    
    /**
     * Static helper methods for external access
     */
    public static function get_parfume_basic_info($post_id) {
        return array(
            'suitable_spring' => get_post_meta($post_id, '_parfume_suitable_spring', true),
            'suitable_summer' => get_post_meta($post_id, '_parfume_suitable_summer', true),
            'suitable_autumn' => get_post_meta($post_id, '_parfume_suitable_autumn', true),
            'suitable_winter' => get_post_meta($post_id, '_parfume_suitable_winter', true),
            'suitable_day' => get_post_meta($post_id, '_parfume_suitable_day', true),
            'suitable_night' => get_post_meta($post_id, '_parfume_suitable_night', true),
            'launch_year' => get_post_meta($post_id, '_parfume_launch_year', true),
            'perfumer' => get_post_meta($post_id, '_parfume_perfumer', true),
            'concentration' => get_post_meta($post_id, '_parfume_concentration', true),
            'gender_target' => get_post_meta($post_id, '_parfume_gender_target', true),
            'age_target' => get_post_meta($post_id, '_parfume_age_target', true)
        );
    }
    
    public static function get_parfume_characteristics($post_id) {
        return array(
            'longevity' => get_post_meta($post_id, '_parfume_longevity', true),
            'sillage' => get_post_meta($post_id, '_parfume_sillage', true),
            'price_category' => get_post_meta($post_id, '_parfume_price_category', true),
            'gender_perception' => get_post_meta($post_id, '_parfume_gender_perception', true),
            'uniqueness' => get_post_meta($post_id, '_parfume_uniqueness', true),
            'versatility' => get_post_meta($post_id, '_parfume_versatility', true)
        );
    }
    
    public static function get_parfume_pros_cons($post_id) {
        return array(
            'advantages' => get_post_meta($post_id, '_parfume_advantages', true) ?: array(),
            'disadvantages' => get_post_meta($post_id, '_parfume_disadvantages', true) ?: array()
        );
    }
    
    public static function get_parfume_additional_info($post_id) {
        return array(
            'bottle_size' => get_post_meta($post_id, '_parfume_bottle_size', true),
            'availability' => get_post_meta($post_id, '_parfume_availability', true),
            'limited_edition' => get_post_meta($post_id, '_parfume_limited_edition', true),
            'reformulated' => get_post_meta($post_id, '_parfume_reformulated', true),
            'discontinued' => get_post_meta($post_id, '_parfume_discontinued', true),
            'seasonal' => get_post_meta($post_id, '_parfume_seasonal', true),
            'internal_notes' => get_post_meta($post_id, '_parfume_internal_notes', true)
        );
    }
}