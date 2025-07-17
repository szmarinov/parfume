<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Homepage class - Управлява настройките за начална страница
 * 
 * Файл: includes/settings/class-settings-homepage.php
 * FIXED VERSION: Добавена search функция за select data-field в homepage настройките
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
        
        // НОВИ ПОЛЕТА
        add_settings_field(
            'homepage_men_perfumes',
            __('Мъжки парфюми', 'parfume-reviews'),
            array($this, 'men_perfumes_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
        
        add_settings_field(
            'homepage_women_perfumes',
            __('Дамски парфюми', 'parfume-reviews'),
            array($this, 'women_perfumes_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
        
        add_settings_field(
            'homepage_featured_brands',
            __('Препоръчани марки', 'parfume-reviews'),
            array($this, 'featured_brands_callback'),
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
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="homepage_hero_enabled"><?php _e('Покажи hero секция', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_hero_enabled_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="homepage_featured_enabled"><?php _e('Покажи препоръчани парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_featured_enabled_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="homepage_latest_count"><?php _e('Брой последни парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->homepage_latest_count_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Мъжки парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->men_perfumes_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Дамски парфюми', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->women_perfumes_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Препоръчани марки', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->featured_brands_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- FIXED: Добавен JavaScript за search функционалността -->
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize perfume selector functionality for all data-field selects
            function initPerfumeSelectors() {
                $('.perfume-selector').each(function() {
                    var $container = $(this);
                    var $select = $container.find('select');
                    var $selectedList = $container.find('.selected-perfumes');
                    var fieldName = $select.data('field');
                    
                    // FIXED: Добавена search функция за select
                    $select.select2({
                        placeholder: '<?php echo esc_js(__("Търсете парфюм...", "parfume-reviews")); ?>',
                        allowClear: true,
                        width: '100%',
                        language: {
                            searching: function() {
                                return '<?php echo esc_js(__("Търсене...", "parfume-reviews")); ?>';
                            },
                            noResults: function() {
                                return '<?php echo esc_js(__("Няма намерени резултати", "parfume-reviews")); ?>';
                            }
                        }
                    });
                    
                    $select.on('change', function() {
                        var selectedId = $(this).val();
                        var selectedText = $(this).find('option:selected').text();
                        
                        if (selectedId && !$selectedList.find('[data-id="' + selectedId + '"]').length) {
                            addSelectedPerfume(selectedId, selectedText, fieldName, $selectedList);
                            $(this).val(''); // Reset select
                            $(this).trigger('change'); // Trigger select2 update
                        }
                    });
                    
                    $(document).on('click', '.remove-perfume', function(e) {
                        e.preventDefault();
                        $(this).closest('.selected-perfume-item').remove();
                    });
                });
                
                // Initialize brand selector functionality
                $('.brand-selector').each(function() {
                    var $container = $(this);
                    var $select = $container.find('select');
                    var $selectedList = $container.find('.selected-brands');
                    var fieldName = $select.data('field');
                    
                    // FIXED: Добавена search функция за brands select
                    $select.select2({
                        placeholder: '<?php echo esc_js(__("Търсете марка...", "parfume-reviews")); ?>',
                        allowClear: true,
                        width: '100%',
                        language: {
                            searching: function() {
                                return '<?php echo esc_js(__("Търсене...", "parfume-reviews")); ?>';
                            },
                            noResults: function() {
                                return '<?php echo esc_js(__("Няма намерени резултати", "parfume-reviews")); ?>';
                            }
                        }
                    });
                    
                    $select.on('change', function() {
                        var selectedId = $(this).val();
                        var selectedText = $(this).find('option:selected').text();
                        
                        if (selectedId && !$selectedList.find('[data-id="' + selectedId + '"]').length) {
                            addSelectedBrand(selectedId, selectedText, fieldName, $selectedList);
                            $(this).val(''); // Reset select
                            $(this).trigger('change'); // Trigger select2 update
                        }
                    });
                    
                    $(document).on('click', '.remove-brand', function(e) {
                        e.preventDefault();
                        $(this).closest('.selected-brand-item').remove();
                    });
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
            
            function addSelectedBrand(id, name, fieldName, $container) {
                var html = '<div class="selected-brand-item" data-id="' + id + '">';
                html += '<span>' + name + '</span>';
                html += '<input type="hidden" name="parfume_reviews_settings[' + fieldName + '][]" value="' + id + '">';
                html += '<a href="#" class="remove-brand">✕</a>';
                html += '</div>';
                
                $container.append(html);
            }
            
            // Initialize when DOM is ready
            initPerfumeSelectors();
        });
        </script>
        
        <!-- CSS Styles -->
        <style type="text/css">
        .perfume-selector, .brand-selector {
            margin-bottom: 15px;
        }
        
        .selected-perfumes, .selected-brands {
            margin-top: 10px;
            min-height: 40px;
            border: 1px dashed #ccd0d4;
            border-radius: 4px;
            padding: 10px;
            background: #f9f9f9;
        }
        
        .selected-perfume-item, .selected-brand-item {
            display: inline-block;
            background: #0073aa;
            color: white;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 3px;
            position: relative;
        }
        
        .selected-perfume-item .remove-perfume,
        .selected-brand-item .remove-brand {
            color: white;
            text-decoration: none;
            margin-left: 8px;
            font-weight: bold;
        }
        
        .selected-perfume-item .remove-perfume:hover,
        .selected-brand-item .remove-brand:hover {
            color: #ff4444;
        }
        
        .select2-container {
            width: 100% !important;
        }
        </style>
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
     * Callback за homepage_latest_count настройката
     */
    public function homepage_latest_count_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_latest_count']) ? $settings['homepage_latest_count'] : 6;
        
        echo '<input type="number" 
                     id="homepage_latest_count"
                     name="parfume_reviews_settings[homepage_latest_count]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="20" 
                     class="small-text" />';
        echo '<p class="description">' . __('Брой последни парфюми за показване на началната страница.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * НОВА ФУНКЦИЯ: Callback за мъжки парфюми
     */
    public function men_perfumes_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $selected_perfumes = isset($settings['homepage_men_perfumes']) ? $settings['homepage_men_perfumes'] : array();
        
        echo '<div class="perfume-selector">';
        echo '<p class="description">' . __('Изберете до 5 мъжки парфюми за показване в секцията "Най-добрите мъжки парфюми".', 'parfume-reviews') . '</p>';
        
        // Dropdown за избор на парфюми с search функция
        echo '<select data-field="homepage_men_perfumes" style="width: 100%;">';
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
        
        // Dropdown за избор на парфюми с search функция
        echo '<select data-field="homepage_women_perfumes" style="width: 100%;">';
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
        
        echo '<div class="brand-selector">';
        echo '<p class="description">' . __('Изберете до 5 марки за показване в секцията "Известни марки парфюми".', 'parfume-reviews') . '</p>';
        
        // Dropdown за избор на марки с search функция
        echo '<select data-field="homepage_featured_brands" style="width: 100%;">';
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
        echo '<div class="selected-brands">';
        if (!empty($selected_brands)) {
            foreach ($selected_brands as $brand_id) {
                $brand = get_term($brand_id, 'marki');
                if ($brand && !is_wp_error($brand)) {
                    echo '<div class="selected-brand-item" data-id="' . esc_attr($brand_id) . '">';
                    echo '<span>' . esc_html($brand->name) . '</span>';
                    echo '<input type="hidden" name="parfume_reviews_settings[homepage_featured_brands][]" value="' . esc_attr($brand_id) . '">';
                    echo '<a href="#" class="remove-brand">✕</a>';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Получава парфюми по пол
     */
    private function get_perfumes_by_gender($gender) {
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_parfume_gender',
                    'value' => $gender,
                    'compare' => 'LIKE'
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Получава настройката
     */
    public function get_setting($setting_name, $default = null) {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings[$setting_name]) ? $settings[$setting_name] : $default;
    }
    
    /**
     * Запазва настройката
     */
    public function save_setting($setting_name, $value) {
        $settings = get_option('parfume_reviews_settings', array());
        $settings[$setting_name] = $value;
        return update_option('parfume_reviews_settings', $settings);
    }
}