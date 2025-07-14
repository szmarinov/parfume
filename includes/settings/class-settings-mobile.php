<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Mobile class - Управлява mobile настройките за stores панела
 * 
 * Файл: includes/settings/class-settings-mobile.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_Mobile {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за mobile поведение
     */
    public function register_settings() {
        // Mobile Section
        add_settings_section(
            'parfume_reviews_mobile_section',
            __('Mobile настройки', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'mobile_fixed_panel',
            __('Фиксиран панел', 'parfume-reviews'),
            array($this, 'mobile_fixed_panel_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
        
        add_settings_field(
            'mobile_show_close_btn',
            __('Бутон за затваряне', 'parfume-reviews'),
            array($this, 'mobile_show_close_btn_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
        
        add_settings_field(
            'mobile_z_index',
            __('Z-index', 'parfume-reviews'),
            array($this, 'mobile_z_index_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
        
        add_settings_field(
            'mobile_bottom_offset',
            __('Отстояние отдолу (px)', 'parfume-reviews'),
            array($this, 'mobile_bottom_offset_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Настройки за мобилно поведение на stores панела.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с mobile настройки
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="mobile_fixed_panel"><?php _e('Фиксиран панел', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->mobile_fixed_panel_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mobile_show_close_btn"><?php _e('Бутон за затваряне', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->mobile_show_close_btn_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mobile_z_index"><?php _e('Z-index', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->mobile_z_index_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mobile_bottom_offset"><?php _e('Отстояние отдолу (px)', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->mobile_bottom_offset_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Callback за mobile_fixed_panel настройката
     */
    public function mobile_fixed_panel_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_fixed_panel']) ? $settings['mobile_fixed_panel'] : 1;
        
        echo '<label>
                <input type="checkbox" 
                       id="mobile_fixed_panel"
                       name="parfume_reviews_settings[mobile_fixed_panel]" 
                       value="1" ' . checked(1, $value, false) . ' /> 
                ' . __('Показвай фиксиран stores панел в долната част на екрана на мобилни устройства', 'parfume-reviews') . '
              </label>';
        echo '<p class="description">' . __('Когато е включено, stores панелът ще се показва фиксирано в долната част на екрана на мобилни устройства.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за mobile_show_close_btn настройката
     */
    public function mobile_show_close_btn_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_show_close_btn']) ? $settings['mobile_show_close_btn'] : 0;
        
        echo '<label>
                <input type="checkbox" 
                       id="mobile_show_close_btn"
                       name="parfume_reviews_settings[mobile_show_close_btn]" 
                       value="1" ' . checked(1, $value, false) . ' /> 
                ' . __('Позволявай скриване на stores панела чрез бутон "X"', 'parfume-reviews') . '
              </label>';
        echo '<p class="description">' . __('Добавя бутон "X" за затваряне на мобилния stores панел. Потребителите ще могат да го покажат отново чрез специален бутон.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за mobile_z_index настройката
     */
    public function mobile_z_index_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_z_index']) ? $settings['mobile_z_index'] : 9999;
        
        echo '<input type="number" 
                     id="mobile_z_index"
                     name="parfume_reviews_settings[mobile_z_index]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="99999"
                     class="small-text" />';
        echo '<p class="description">' . __('Z-index стойност на stores панела (при конфликти с други фиксирани елементи).', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за mobile_bottom_offset настройката
     */
    public function mobile_bottom_offset_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_bottom_offset']) ? $settings['mobile_bottom_offset'] : 0;
        
        echo '<input type="number" 
                     id="mobile_bottom_offset"
                     name="parfume_reviews_settings[mobile_bottom_offset]" 
                     value="' . esc_attr($value) . '" 
                     min="0" 
                     max="200"
                     class="small-text" />';
        echo '<p class="description">' . __('Отстояние в пиксели от долния край на екрана (при наличие на други фиксирани елементи).', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Валидира mobile настройките преди запазване
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Валидация за mobile_fixed_panel
        $validated['mobile_fixed_panel'] = isset($input['mobile_fixed_panel']) ? 1 : 0;
        
        // Валидация за mobile_show_close_btn
        $validated['mobile_show_close_btn'] = isset($input['mobile_show_close_btn']) ? 1 : 0;
        
        // Валидация за mobile_z_index
        if (isset($input['mobile_z_index'])) {
            $z_index = intval($input['mobile_z_index']);
            if ($z_index >= 1 && $z_index <= 99999) {
                $validated['mobile_z_index'] = $z_index;
            } else {
                add_settings_error(
                    'parfume_reviews_settings',
                    'mobile_z_index_error',
                    __('Z-index трябва да бъде между 1 и 99999.', 'parfume-reviews'),
                    'error'
                );
                $validated['mobile_z_index'] = 9999; // default value
            }
        }
        
        // Валидация за mobile_bottom_offset
        if (isset($input['mobile_bottom_offset'])) {
            $bottom_offset = intval($input['mobile_bottom_offset']);
            if ($bottom_offset >= 0 && $bottom_offset <= 200) {
                $validated['mobile_bottom_offset'] = $bottom_offset;
            } else {
                add_settings_error(
                    'parfume_reviews_settings',
                    'mobile_bottom_offset_error',
                    __('Отстоянието отдолу трябва да бъде между 0 и 200 пиксела.', 'parfume-reviews'),
                    'error'
                );
                $validated['mobile_bottom_offset'] = 0; // default value
            }
        }
        
        return $validated;
    }
    
    /**
     * Получава стойността на конкретна mobile настройка
     */
    public function get_setting($setting_name, $default = null) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $defaults = array(
            'mobile_fixed_panel' => 1,
            'mobile_show_close_btn' => 0,
            'mobile_z_index' => 9999,
            'mobile_bottom_offset' => 0
        );
        
        if (isset($defaults[$setting_name])) {
            return isset($settings[$setting_name]) ? $settings[$setting_name] : $defaults[$setting_name];
        }
        
        return isset($settings[$setting_name]) ? $settings[$setting_name] : $default;
    }
    
    /**
     * Запазва конкретна mobile настройка
     */
    public function save_setting($setting_name, $value) {
        $settings = get_option('parfume_reviews_settings', array());
        
        // Валидация според типа на настройката
        switch ($setting_name) {
            case 'mobile_fixed_panel':
            case 'mobile_show_close_btn':
                $settings[$setting_name] = $value ? 1 : 0;
                break;
            case 'mobile_z_index':
                $z_index = intval($value);
                $settings[$setting_name] = ($z_index >= 1 && $z_index <= 99999) ? $z_index : 9999;
                break;
            case 'mobile_bottom_offset':
                $offset = intval($value);
                $settings[$setting_name] = ($offset >= 0 && $offset <= 200) ? $offset : 0;
                break;
            default:
                $settings[$setting_name] = $value;
        }
        
        return update_option('parfume_reviews_settings', $settings);
    }
    
    /**
     * Получава всички mobile настройки
     */
    public function get_all_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'mobile_fixed_panel' => isset($settings['mobile_fixed_panel']) ? $settings['mobile_fixed_panel'] : 1,
            'mobile_show_close_btn' => isset($settings['mobile_show_close_btn']) ? $settings['mobile_show_close_btn'] : 0,
            'mobile_z_index' => isset($settings['mobile_z_index']) ? $settings['mobile_z_index'] : 9999,
            'mobile_bottom_offset' => isset($settings['mobile_bottom_offset']) ? $settings['mobile_bottom_offset'] : 0
        );
    }
    
    /**
     * Проверява дали настройките са валидни
     */
    public function validate_all_settings() {
        $settings = $this->get_all_settings();
        $errors = array();
        
        // Проверка за mobile_z_index
        if ($settings['mobile_z_index'] < 1 || $settings['mobile_z_index'] > 99999) {
            $errors[] = __('Z-index трябва да бъде между 1 и 99999.', 'parfume-reviews');
        }
        
        // Проверка за mobile_bottom_offset
        if ($settings['mobile_bottom_offset'] < 0 || $settings['mobile_bottom_offset'] > 200) {
            $errors[] = __('Отстоянието отдолу трябва да бъде между 0 и 200 пиксела.', 'parfume-reviews');
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Зарежда настройките по подразбиране
     */
    public function load_defaults() {
        $defaults = array(
            'mobile_fixed_panel' => 1,
            'mobile_show_close_btn' => 0,
            'mobile_z_index' => 9999,
            'mobile_bottom_offset' => 0
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        $merged_settings = wp_parse_args($current_settings, $defaults);
        
        return update_option('parfume_reviews_settings', $merged_settings);
    }
    
    /**
     * Ресетира mobile настройките към стойностите по подразбиране
     */
    public function reset_to_defaults() {
        $defaults = array(
            'mobile_fixed_panel' => 1,
            'mobile_show_close_btn' => 0,
            'mobile_z_index' => 9999,
            'mobile_bottom_offset' => 0
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        
        // Запазваме настройките от други компоненти
        foreach ($defaults as $key => $value) {
            $current_settings[$key] = $value;
        }
        
        return update_option('parfume_reviews_settings', $current_settings);
    }
    
    /**
     * Проверява дали фиксираният панел е включен
     */
    public function is_fixed_panel_enabled() {
        return (bool) $this->get_setting('mobile_fixed_panel', 1);
    }
    
    /**
     * Проверява дали бутонът за затваряне е включен
     */
    public function is_close_button_enabled() {
        return (bool) $this->get_setting('mobile_show_close_btn', 0);
    }
    
    /**
     * Получава Z-index стойността
     */
    public function get_z_index() {
        return intval($this->get_setting('mobile_z_index', 9999));
    }
    
    /**
     * Получава отстоянието отдолу
     */
    public function get_bottom_offset() {
        return intval($this->get_setting('mobile_bottom_offset', 0));
    }
    
    /**
     * Получава конфигурацията за mobile панела
     */
    public function get_mobile_config() {
        return array(
            'fixed_panel' => $this->is_fixed_panel_enabled(),
            'show_close_btn' => $this->is_close_button_enabled(),
            'z_index' => $this->get_z_index(),
            'bottom_offset' => $this->get_bottom_offset()
        );
    }
    
    /**
     * Получава CSS custom properties за mobile панела
     */
    public function get_css_variables() {
        $config = $this->get_mobile_config();
        
        return array(
            '--mobile-z-index' => $config['z_index'],
            '--mobile-bottom-offset' => $config['bottom_offset'] . 'px'
        );
    }
    
    /**
     * Генерира inline CSS стил за mobile панела
     */
    public function get_inline_style() {
        $variables = $this->get_css_variables();
        $style_parts = array();
        
        foreach ($variables as $property => $value) {
            $style_parts[] = $property . ': ' . $value;
        }
        
        return implode('; ', $style_parts);
    }
    
    /**
     * Експортира mobile настройките в JSON формат
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        
        return json_encode(array(
            'component' => 'mobile',
            'version' => PARFUME_REVIEWS_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Импортира mobile настройки от JSON данни
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['component']) || $data['component'] !== 'mobile') {
            return new \WP_Error('invalid_component', __('Файлът не съдържа mobile настройки.', 'parfume-reviews'));
        }
        
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return new \WP_Error('invalid_settings', __('Невалидни настройки в файла.', 'parfume-reviews'));
        }
        
        // Валидираме настройките
        $validated_settings = $this->validate_settings($data['settings']);
        
        // Запазваме настройките
        $current_settings = get_option('parfume_reviews_settings', array());
        $current_settings = array_merge($current_settings, $validated_settings);
        
        $result = update_option('parfume_reviews_settings', $current_settings);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => __('Mobile настройките са импортирани успешно.', 'parfume-reviews'),
                'imported_count' => count($validated_settings)
            );
        } else {
            return new \WP_Error('save_failed', __('Грешка при запазване на настройките.', 'parfume-reviews'));
        }
    }
    
    /**
     * Тества mobile настройките
     */
    public function test_settings() {
        $settings = $this->get_all_settings();
        $test_results = array();
        
        // Тест за fixed panel
        $test_results['fixed_panel_status'] = $settings['mobile_fixed_panel'] ? 'enabled' : 'disabled';
        
        // Тест за close button
        $test_results['close_button_status'] = $settings['mobile_show_close_btn'] ? 'enabled' : 'disabled';
        
        // Тест за z-index
        $test_results['z_index'] = $settings['mobile_z_index'];
        $test_results['z_index_valid'] = ($settings['mobile_z_index'] >= 1 && $settings['mobile_z_index'] <= 99999);
        
        // Тест за bottom offset
        $test_results['bottom_offset'] = $settings['mobile_bottom_offset'];
        $test_results['bottom_offset_valid'] = ($settings['mobile_bottom_offset'] >= 0 && $settings['mobile_bottom_offset'] <= 200);
        
        // Общ статус
        $test_results['overall_status'] = ($test_results['z_index_valid'] && $test_results['bottom_offset_valid']) ? 'valid' : 'invalid';
        
        return $test_results;
    }
}