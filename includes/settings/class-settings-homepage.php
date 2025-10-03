<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Homepage class - Управлява настройките за начална страница
 * 
 * Файл: includes/settings/class-settings-homepage.php
 * Извлечен от оригинален class-settings.php
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
            </tbody>
        </table>
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
        $value = isset($settings['homepage_latest_count']) ? $settings['homepage_latest_count'] : 8;
        
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
            'homepage_latest_count' => 8
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
            'homepage_latest_count' => isset($settings['homepage_latest_count']) ? $settings['homepage_latest_count'] : 8
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
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Зарежда настройките по подразбиране
     */
    public function load_defaults() {
        $defaults = array(
            'homepage_hero_enabled' => 1,
            'homepage_featured_enabled' => 1,
            'homepage_latest_count' => 8
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        $merged_settings = wp_parse_args($current_settings, $defaults);
        
        return update_option('parfume_reviews_settings', $merged_settings);
    }
    
    /**
     * Ресетира homepage настройките към стойностите по подразбиране
     */
    public function reset_to_defaults() {
        $defaults = array(
            'homepage_hero_enabled' => 1,
            'homepage_featured_enabled' => 1,
            'homepage_latest_count' => 8
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        
        // Запазваме настройките от други компоненти
        foreach ($defaults as $key => $value) {
            $current_settings[$key] = $value;
        }
        
        return update_option('parfume_reviews_settings', $current_settings);
    }
    
    /**
     * Проверява дали hero секцията е включена
     */
    public function is_hero_enabled() {
        return (bool) $this->get_setting('homepage_hero_enabled', 1);
    }
    
    /**
     * Проверява дали секцията с препоръчани парфюми е включена
     */
    public function is_featured_enabled() {
        return (bool) $this->get_setting('homepage_featured_enabled', 1);
    }
    
    /**
     * Получава броя последни парфюми за показване
     */
    public function get_latest_count() {
        return intval($this->get_setting('homepage_latest_count', 8));
    }
    
    /**
     * Получава конфигурацията за homepage секциите
     */
    public function get_homepage_config() {
        return array(
            'hero_enabled' => $this->is_hero_enabled(),
            'featured_enabled' => $this->is_featured_enabled(),
            'latest_count' => $this->get_latest_count()
        );
    }
    
    /**
     * Експортира homepage настройките в JSON формат
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        
        return json_encode(array(
            'component' => 'homepage',
            'version' => PARFUME_REVIEWS_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Импортира homepage настройки от JSON данни
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['component']) || $data['component'] !== 'homepage') {
            return new \WP_Error('invalid_component', __('Файлът не съдържа homepage настройки.', 'parfume-reviews'));
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
                'message' => __('Homepage настройките са импортирани успешно.', 'parfume-reviews'),
                'imported_count' => count($validated_settings)
            );
        } else {
            return new \WP_Error('save_failed', __('Грешка при запазване на настройките.', 'parfume-reviews'));
        }
    }
    
    /**
     * Получава настройките за homepage shortcode
     */
    public function get_shortcode_settings() {
        $settings = $this->get_all_settings();
        
        return array(
            'show_hero' => $settings['homepage_hero_enabled'],
            'show_featured' => $settings['homepage_featured_enabled'],
            'latest_count' => $settings['homepage_latest_count']
        );
    }
    
    /**
     * Тества homepage настройките
     */
    public function test_settings() {
        $settings = $this->get_all_settings();
        $test_results = array();
        
        // Тест за hero секция
        $test_results['hero_status'] = $settings['homepage_hero_enabled'] ? 'enabled' : 'disabled';
        
        // Тест за featured секция
        $test_results['featured_status'] = $settings['homepage_featured_enabled'] ? 'enabled' : 'disabled';
        
        // Тест за latest count
        $test_results['latest_count'] = $settings['homepage_latest_count'];
        $test_results['latest_count_valid'] = ($settings['homepage_latest_count'] >= 1 && $settings['homepage_latest_count'] <= 20);
        
        // Общ статус
        $test_results['overall_status'] = $test_results['latest_count_valid'] ? 'valid' : 'invalid';
        
        return $test_results;
    }
}