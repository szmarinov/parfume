<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Homepage class - Управлява настройките за начална страница
 * 
 * Файл: includes/settings/class-settings-homepage.php
 * РАЗШИРЕНА ВЕРСИЯ: Добавени настройки за всички homepage секции
 */
class Settings_Homepage {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за начална страница
     */
    public function register_settings() {
        // Homepage Section
        add_settings_section(
            'parfume_reviews_homepage_section',
            __('Настройки за начална страница', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'homepage_hero_enabled',
            __('Покажи hero секция', 'parfume-reviews'),
            array($this, 'homepage_hero_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
        
        add_settings_field(
            'homepage_featured_enabled',
            __('Покажи препоръчани парфюми', 'parfume-reviews'),
            array($this, 'homepage_featured_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
        
        add_settings_field(
            'homepage_latest_count',
            __('Брой последни парфюми', 'parfume-reviews'),
            array($this, 'homepage_latest_count_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Конфигурирайте елементите на началната страница.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с homepage настройки
     * РАЗШИРЕНА ВЕРСИЯ: Добавени всички нови секции
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Hero Section -->
                <tr>
                    <th scope="row">
                        <label for="homepage_hero_enabled"><?php _e('Покажи hero секция', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_hero_enabled_callback(); ?>
                    </td>
                </tr>
                
                <!-- Featured Perfumes -->
                <tr>
                    <th scope="row">
                        <label for="homepage_featured_enabled"><?php _e('Покажи препоръчани парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_featured_enabled_callback(); ?>
                    </td>
                </tr>
                
                <!-- Men's Perfumes Section -->
                <tr>
                    <th scope="row">
                        <label><?php _e('Най-добрите мъжки парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->men_perfumes_callback(); ?>
                    </td>
                </tr>
                
                <!-- Women's Perfumes Section -->
                <tr>
                    <th scope="row">
                        <label><?php _e('Най-търсените дамски парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->women_perfumes_callback(); ?>
                    </td>
                </tr>
                
                <!-- Featured Brands Section -->
                <tr>
                    <th scope="row">
                        <label><?php _e('Известни марки парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->featured_brands_callback(); ?>
                    </td>
                </tr>
                
                <!-- Arabic Perfumes Section -->
                <tr>
                    <th scope="row">
                        <label><?php _e('Арабски парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->arabic_perfumes_callback(); ?>
                    </td>
                </tr>
                
                <!-- Latest Count -->
                <tr>
                    <th scope="row">
                        <label for="homepage_latest_count"><?php _e('Брой последни парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_latest_count_callback(); ?>
                    </td>
                </tr>
                
                <!-- Blog Section -->
                <tr>
                    <th scope="row">
                        <label for="homepage_blog_count"><?php _e('Брой статии от блога', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_blog_count_callback(); ?>
                    </td>
                </tr>
                
                <!-- Description Section -->
                <tr>
                    <th scope="row">
                        <label for="homepage_description"><?php _e('Описание за началната страница', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_description_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <style>
        .perfume-selector {
            max-width: 500px;
        }
        .perfume-selector select {
            width: 100%;
            margin-bottom: 10px;
        }
        .selected-perfumes {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        .selected-perfume-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .selected-perfume-item:last-child {
            border-bottom: none;
        }
        .remove-perfume {
            color: #a00;
            cursor: pointer;
            text-decoration: none;
        }
        .remove-perfume:hover {
            color: #f00;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize perfume selectors
            $('.perfume-selector').each(function() {
                initPerfumeSelector($(this));
            });
            
            function initPerfumeSelector($container) {
                var $select = $container.find('select');
                var $selectedList = $container.find('.selected-perfumes');
                var fieldName = $select.data('field');
                
                $select.on('change', function() {
                    var selectedId = $(this).val();
                    var selectedText = $(this).find('option:selected').text();
                    
                    if (selectedId && !$selectedList.find('[data-id="' + selectedId + '"]').length) {
                        addSelectedPerfume(selectedId, selectedText, fieldName, $selectedList);
                        $(this).val(''); // Reset select
                    }
                });
                
                $(document).on('click', '.remove-perfume', function(e) {
                    e.preventDefault();
                    $(this).closest('.selected-perfume-item').remove();
                });
            }
            
            function addSelectedPerfume(id, name, fieldName, $container) {
                var html = '<div class="selected-perfume-item" data-id="' + id + '">';
                html += '<span>' + name + '</span>';
                html += '<input type="hidden" name="parfume_reviews_settings[' + fieldName + '][]" value="' + id + '">';
                html += '<a href="#" class="remove-perfume">✕</a>';
                html += '</div>';
                
                $container.append(html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Callback за homepage_hero_enabled настройката
     */
    public function homepage_hero_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_hero_enabled']) ? $settings['homepage_hero_enabled'] : 1;
        
        echo '<label>
                <input type="checkbox" 
                       id="homepage_hero_enabled"
                       name="parfume_reviews_settings[homepage_hero_enabled]" 
                       value="1" ' . checked(1, $value, false) . ' /> 
                ' . __('Покажи hero секция на началната страница', 'parfume-reviews') . '
              </label>';
        echo '<p class="description">' . __('Включва/изключва показването на hero секцията в горната част на началната страница.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за homepage_featured_enabled настройката
     */
    public function homepage_featured_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_featured_enabled']) ? $settings['homepage_featured_enabled'] : 1;
        
        echo '<label>
                <input type="checkbox" 
                       id="homepage_featured_enabled"
                       name="parfume_reviews_settings[homepage_featured_enabled]" 
                       value="1" ' . checked(1, $value, false) . ' /> 
                ' . __('Покажи секция с препоръчани парфюми', 'parfume-reviews') . '
              </label>';
        echo '<p class="description">' . __('Включва/изключва показването на секцията с препоръчани парфюми на началната страница.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * НОВА ФУНКЦИЯ: Callback за мъжки парфюми
     */
    public function men_perfumes_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $selected_perfumes = isset($settings['homepage_men_perfumes']) ? $settings['homepage_men_perfumes'] : array();
        
        echo '<div class="perfume-selector">';
        echo '<p class="description">' . __('Изберете до 5 мъжки парфюми за показване в секцията "Най-добрите мъжки парфюми".', 'parfume-reviews') . '</p>';
        
        // Dropdown за избор на парфюми
        echo '<select data-field="homepage_men_perfumes">';
        echo '<option value="">' . __('Изберете парфюм...', 'parfume-reviews') . '</option>';
        
        // Получаваме мъжките парфюми
        $men_perfumes = $this->get_perfumes_by_gender('мъжки');
        foreach ($men_perfumes as $perfume) {
            echo '<option value="' . esc_attr($perfume->ID) . '">' . esc_html($perfume->post_title) . '</option>';
        }
        
        echo '</select>';
        
        // Показваме избраните парфюми
        echo '<div class="selected-perfumes">';
        if (!empty($selected_perfumes)) {
            foreach ($selected_perfumes as $perfume_id) {
                $perfume = get_post($perfume_id);
                if ($perfume) {
                    echo '<div class="selected-perfume-item" data-id="' . esc_attr($perfume_id) . '">';
                    echo '<span>' . esc_html($perfume->post_title) . '</span>';
                    echo '<input type="hidden" name="parfume_reviews_settings[homepage_men_perfumes][]" value="' . esc_attr($perfume_id) . '">';
                    echo '<a href="#" class="remove-perfume">✕</a>';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * НОВА ФУНКЦИЯ: Callback за дамски парфюми
     */
    public function women_perfumes_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $selected_perfumes = isset($settings['homepage_women_perfumes']) ? $settings['homepage_women_perfumes'] : array();
        
        echo '<div class="perfume-selector">';
        echo '<p class="description">' . __('Изберете до 5 дамски парфюми за показване в секцията "Най-търсените дамски парфюми".', 'parfume-reviews') . '</p>';
        
        // Dropdown за избор на парфюми
        echo '<select data-field="homepage_women_perfumes">';
        echo '<option value="">' . __('Изберете парфюм...', 'parfume-reviews') . '</option>';
        
        // Получаваме дамските парфюми
        $women_perfumes = $this->get_perfumes_by_gender('дамски');
        foreach ($women_perfumes as $perfume) {
            echo '<option value="' . esc_attr($perfume->ID) . '">' . esc_html($perfume->post_title) . '</option>';
        }
        
        echo '</select>';
        
        // Показваме избраните парфюми
        echo '<div class="selected-perfumes">';
        if (!empty($selected_perfumes)) {
            foreach ($selected_perfumes as $perfume_id) {
                $perfume = get_post($perfume_id);
                if ($perfume) {
                    echo '<div class="selected-perfume-item" data-id="' . esc_attr($perfume_id) . '">';
                    echo '<span>' . esc_html($perfume->post_title) . '</span>';
                    echo '<input type="hidden" name="parfume_reviews_settings[homepage_women_perfumes][]" value="' . esc_attr($perfume_id) . '">';
                    echo '<a href="#" class="remove-perfume">✕</a>';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * НОВА ФУНКЦИЯ: Callback за марки
     */
    public function featured_brands_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $selected_brands = isset($settings['homepage_featured_brands']) ? $settings['homepage_featured_brands'] : array();
        
        echo '<div class="perfume-selector">';
        echo '<p class="description">' . __('Изберете до 5 марки за показване в секцията "Известни марки парфюми".', 'parfume-reviews') . '</p>';
        
        // Dropdown за избор на марки
        echo '<select data-field="homepage_featured_brands">';
        echo '<option value="">' . __('Изберете марка...', 'parfume-reviews') . '</option>';
        
        // Получаваме всички марки
        $brands = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => false,
            'orderby' => 'name'
        ));
        
        if (!is_wp_error($brands)) {
            foreach ($brands as $brand) {
                echo '<option value="' . esc_attr($brand->term_id) . '">' . esc_html($brand->name) . '</option>';
            }
        }
        
        echo '</select>';
        
        // Показваме избраните марки
        echo '<div class="selected-perfumes">';
        if (!empty($selected_brands)) {
            foreach ($selected_brands as $brand_id) {
                $brand = get_term($brand_id, 'marki');
                if ($brand && !is_wp_error($brand)) {
                    echo '<div class="selected-perfume-item" data-id="' . esc_attr($brand_id) . '">';
                    echo '<span>' . esc_html($brand->name) . '</span>';
                    echo '<input type="hidden" name="parfume_reviews_settings[homepage_featured_brands][]" value="' . esc_attr($brand_id) . '">';
                    echo '<a href="#" class="remove-perfume">✕</a>';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * НОВА ФУНКЦИЯ: Callback за арабски парфюми
     */
    public function arabic_perfumes_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $selected_perfumes = isset($settings['homepage_arabic_perfumes']) ? $settings['homepage_arabic_perfumes'] : array();
        
        echo '<div class="perfume-selector">';
        echo '<p class="description">' . __('Изберете до 5 арабски парфюми за показване в секцията "Арабски парфюми".', 'parfume-reviews') . '</p>';
        
        // Dropdown за избор на парфюми
        echo '<select data-field="homepage_arabic_perfumes">';
        echo '<option value="">' . __('Изберете парфюм...', 'parfume-reviews') . '</option>';
        
        // Получаваме арабските парфюми
        $arabic_perfumes = $this->get_arabic_perfumes();
        foreach ($arabic_perfumes as $perfume) {
            echo '<option value="' . esc_attr($perfume->ID) . '">' . esc_html($perfume->post_title) . '</option>';
        }
        
        echo '</select>';
        
        // Показваме избраните парфюми
        echo '<div class="selected-perfumes">';
        if (!empty($selected_perfumes)) {
            foreach ($selected_perfumes as $perfume_id) {
                $perfume = get_post($perfume_id);
                if ($perfume) {
                    echo '<div class="selected-perfume-item" data-id="' . esc_attr($perfume_id) . '">';
                    echo '<span>' . esc_html($perfume->post_title) . '</span>';
                    echo '<input type="hidden" name="parfume_reviews_settings[homepage_arabic_perfumes][]" value="' . esc_attr($perfume_id) . '">';
                    echo '<a href="#" class="remove-perfume">✕</a>';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Callback за homepage_latest_count настройката
     */
    public function homepage_latest_count_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_latest_count']) ? $settings['homepage_latest_count'] : 8;
        
        echo '<input type="number" 
                     id="homepage_latest_count"
                     name="parfume_reviews_settings[homepage_latest_count]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="20" 
                     class="small-text" />';
        echo '<p class="description">' . __('Брой последни парфюми за показване на началната страница в секцията "Последно добавени".', 'parfume-reviews') . '</p>';
    }
    
    /**
     * НОВА ФУНКЦИЯ: Callback за брой статии от блога
     */
    public function homepage_blog_count_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_blog_count']) ? $settings['homepage_blog_count'] : 3;
        
        echo '<input type="number" 
                     id="homepage_blog_count"
                     name="parfume_reviews_settings[homepage_blog_count]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="10" 
                     class="small-text" />';
        echo '<p class="description">' . __('Брой статии от блога за показване в секцията "Последни от блога".', 'parfume-reviews') . '</p>';
    }
    
    /**
     * НОВА ФУНКЦИЯ: Callback за описание
     */
    public function homepage_description_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_description']) ? $settings['homepage_description'] : '';
        
        echo '<textarea id="homepage_description" 
                        name="parfume_reviews_settings[homepage_description]" 
                        rows="5" 
                        cols="50" 
                        class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('Описание и текст за показване на началната страница. Може да съдържа HTML.', 'parfume-reviews') . '</p>';
    }
    
    // ================ HELPER FUNCTIONS ================
    
    /**
     * НОВА ФУНКЦИЯ: Получава парфюми по пол
     */
    private function get_perfumes_by_gender($gender) {
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => 100,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'gender',
                    'field' => 'name',
                    'terms' => $gender
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * НОВА ФУНКЦИЯ: Получава арабски парфюми
     */
    private function get_arabic_perfumes() {
        // Търсим парфюми с арабски марки или които съдържат "араб" в заглавието
        $arabic_brands = array('Ajmal', 'Al Haramain', 'Amouage', 'Creed', 'Tom Ford');
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => 100,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_parfume_origin',
                    'value' => 'arab',
                    'compare' => 'LIKE'
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        // Ако не намерим специфично арабски, връщаме всички за сега
        $query = new \WP_Query($args);
        
        if (empty($query->posts)) {
            $args = array(
                'post_type' => 'parfume',
                'posts_per_page' => 50,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            );
            $query = new \WP_Query($args);
        }
        
        return $query->posts;
    }
    
    /**
     * Валидира homepage настройките преди запазване
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Валидация за homepage_hero_enabled
        $validated['homepage_hero_enabled'] = isset($input['homepage_hero_enabled']) ? 1 : 0;
        
        // Валидация за homepage_featured_enabled
        $validated['homepage_featured_enabled'] = isset($input['homepage_featured_enabled']) ? 1 : 0;
        
        // Валидация за homepage_latest_count
        if (isset($input['homepage_latest_count'])) {
            $latest_count = intval($input['homepage_latest_count']);
            if ($latest_count >= 1 && $latest_count <= 20) {
                $validated['homepage_latest_count'] = $latest_count;
            } else {
                add_settings_error(
                    'parfume_reviews_settings',
                    'homepage_latest_count_error',
                    __('Броят последни парфюми трябва да бъде между 1 и 20.', 'parfume-reviews'),
                    'error'
                );
                $validated['homepage_latest_count'] = 8; // default value
            }
        }
        
        // НОВА ВАЛИДАЦИЯ: Мъжки парфюми
        if (isset($input['homepage_men_perfumes']) && is_array($input['homepage_men_perfumes'])) {
            $men_perfumes = array_map('intval', $input['homepage_men_perfumes']);
            $men_perfumes = array_slice($men_perfumes, 0, 5); // Max 5
            $validated['homepage_men_perfumes'] = $men_perfumes;
        } else {
            $validated['homepage_men_perfumes'] = array();
        }
        
        // НОВА ВАЛИДАЦИЯ: Дамски парфюми
        if (isset($input['homepage_women_perfumes']) && is_array($input['homepage_women_perfumes'])) {
            $women_perfumes = array_map('intval', $input['homepage_women_perfumes']);
            $women_perfumes = array_slice($women_perfumes, 0, 5); // Max 5
            $validated['homepage_women_perfumes'] = $women_perfumes;
        } else {
            $validated['homepage_women_perfumes'] = array();
        }
        
        // НОВА ВАЛИДАЦИЯ: Марки
        if (isset($input['homepage_featured_brands']) && is_array($input['homepage_featured_brands'])) {
            $featured_brands = array_map('intval', $input['homepage_featured_brands']);
            $featured_brands = array_slice($featured_brands, 0, 5); // Max 5
            $validated['homepage_featured_brands'] = $featured_brands;
        } else {
            $validated['homepage_featured_brands'] = array();
        }
        
        // НОВА ВАЛИДАЦИЯ: Арабски парфюми
        if (isset($input['homepage_arabic_perfumes']) && is_array($input['homepage_arabic_perfumes'])) {
            $arabic_perfumes = array_map('intval', $input['homepage_arabic_perfumes']);
            $arabic_perfumes = array_slice($arabic_perfumes, 0, 5); // Max 5
            $validated['homepage_arabic_perfumes'] = $arabic_perfumes;
        } else {
            $validated['homepage_arabic_perfumes'] = array();
        }
        
        // НОВА ВАЛИДАЦИЯ: Брой статии от блога
        if (isset($input['homepage_blog_count'])) {
            $blog_count = intval($input['homepage_blog_count']);
            if ($blog_count >= 1 && $blog_count <= 10) {
                $validated['homepage_blog_count'] = $blog_count;
            } else {
                $validated['homepage_blog_count'] = 3; // default value
            }
        }
        
        // НОВА ВАЛИДАЦИЯ: Описание
        if (isset($input['homepage_description'])) {
            $validated['homepage_description'] = wp_kses_post($input['homepage_description']);
        }
        
        return $validated;
    }
    
    /**
     * Получава стойността на конкретна homepage настройка
     */
    public function get_setting($setting_name, $default = null) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $defaults = array(
            'homepage_hero_enabled' => 1,
            'homepage_featured_enabled' => 1,
            'homepage_latest_count' => 8,
            'homepage_men_perfumes' => array(),
            'homepage_women_perfumes' => array(),
            'homepage_featured_brands' => array(),
            'homepage_arabic_perfumes' => array(),
            'homepage_blog_count' => 3,
            'homepage_description' => ''
        );
        
        if (isset($defaults[$setting_name])) {
            return isset($settings[$setting_name]) ? $settings[$setting_name] : $defaults[$setting_name];
        }
        
        return isset($settings[$setting_name]) ? $settings[$setting_name] : $default;
    }
    
    /**
     * Запазва конкретна homepage настройка
     */
    public function save_setting($setting_name, $value) {
        $settings = get_option('parfume_reviews_settings', array());
        
        // Валидация според типа на настройката
        switch ($setting_name) {
            case 'homepage_hero_enabled':
            case 'homepage_featured_enabled':
                $settings[$setting_name] = $value ? 1 : 0;
                break;
            case 'homepage_latest_count':
                $count = intval($value);
                $settings[$setting_name] = ($count >= 1 && $count <= 20) ? $count : 8;
                break;
            case 'homepage_blog_count':
                $count = intval($value);
                $settings[$setting_name] = ($count >= 1 && $count <= 10) ? $count : 3;
                break;
            case 'homepage_men_perfumes':
            case 'homepage_women_perfumes':
            case 'homepage_featured_brands':
            case 'homepage_arabic_perfumes':
                if (is_array($value)) {
                    $settings[$setting_name] = array_map('intval', array_slice($value, 0, 5));
                } else {
                    $settings[$setting_name] = array();
                }
                break;
            case 'homepage_description':
                $settings[$setting_name] = wp_kses_post($value);
                break;
            default:
                $settings[$setting_name] = $value;
        }
        
        return update_option('parfume_reviews_settings', $settings);
    }
    
    /**
     * Получава всички homepage настройки
     */
    public function get_all_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'homepage_hero_enabled' => isset($settings['homepage_hero_enabled']) ? $settings['homepage_hero_enabled'] : 1,
            'homepage_featured_enabled' => isset($settings['homepage_featured_enabled']) ? $settings['homepage_featured_enabled'] : 1,
            'homepage_latest_count' => isset($settings['homepage_latest_count']) ? $settings['homepage_latest_count'] : 8,
            'homepage_men_perfumes' => isset($settings['homepage_men_perfumes']) ? $settings['homepage_men_perfumes'] : array(),
            'homepage_women_perfumes' => isset($settings['homepage_women_perfumes']) ? $settings['homepage_women_perfumes'] : array(),
            'homepage_featured_brands' => isset($settings['homepage_featured_brands']) ? $settings['homepage_featured_brands'] : array(),
            'homepage_arabic_perfumes' => isset($settings['homepage_arabic_perfumes']) ? $settings['homepage_arabic_perfumes'] : array(),
            'homepage_blog_count' => isset($settings['homepage_blog_count']) ? $settings['homepage_blog_count'] : 3,
            'homepage_description' => isset($settings['homepage_description']) ? $settings['homepage_description'] : ''
        );
    }
    
    /**
     * Проверява дали настройките са валидни
     */
    public function validate_all_settings() {
        $settings = $this->get_all_settings();
        $errors = array();
        
        // Проверка за homepage_latest_count
        if ($settings['homepage_latest_count'] < 1 || $settings['homepage_latest_count'] > 20) {
            $errors[] = __('Броят последни парфюми трябва да бъде между 1 и 20.', 'parfume-reviews');
        }
        
        // Проверка за homepage_blog_count
        if ($settings['homepage_blog_count'] < 1 || $settings['homepage_blog_count'] > 10) {
            $errors[] = __('Броят статии от блога трябва да бъде между 1 и 10.', 'parfume-reviews');
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Зарежда настройките по подразбиране
     */
    public function load_defaults() {
        $defaults = array(
            'homepage_hero_enabled' => 1,
            'homepage_featured_enabled' => 1,
            'homepage_latest_count' => 8,
            'homepage_men_perfumes' => array(),
            'homepage_women_perfumes' => array(),
            'homepage_featured_brands' => array(),
            'homepage_arabic_perfumes' => array(),
            'homepage_blog_count' => 3,
            'homepage_description' => ''
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        $merged_settings = wp_parse_args($current_settings, $defaults);
        
        return update_option('parfume_reviews_settings', $merged_settings);
    }
    
    /**
     * Получава конфигурацията за homepage секциите
     */
    public function get_homepage_config() {
        $settings = $this->get_all_settings();
        
        return array(
            'hero_enabled' => (bool) $settings['homepage_hero_enabled'],
            'featured_enabled' => (bool) $settings['homepage_featured_enabled'],
            'latest_count' => $settings['homepage_latest_count'],
            'men_perfumes' => $settings['homepage_men_perfumes'],
            'women_perfumes' => $settings['homepage_women_perfumes'],
            'featured_brands' => $settings['homepage_featured_brands'],
            'arabic_perfumes' => $settings['homepage_arabic_perfumes'],
            'blog_count' => $settings['homepage_blog_count'],
            'description' => $settings['homepage_description']
        );
    }
}