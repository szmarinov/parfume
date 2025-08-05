<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_General class - Управлява основните настройки на плъгина
 * 
 * Файл: includes/settings/class-settings-general.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_General {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за общи опции
     */
    public function register_settings() {
        // General Section
        add_settings_section(
            'parfume_reviews_general_section',
            __('Общи настройки', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'posts_per_page',
            __('Постове на страница', 'parfume-reviews'),
            array($this, 'posts_per_page_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'featured_perfumes_per_intensity',
            __('Парфюми за интензивност', 'parfume-reviews'),
            array($this, 'featured_perfumes_per_intensity_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Основни настройки за функционирането на плъгина.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с общи настройки
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="posts_per_page"><?php _e('Постове на страница', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->posts_per_page_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="featured_perfumes_per_intensity"><?php _e('Парфюми за интензивност', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->featured_perfumes_per_intensity_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Callback за posts_per_page настройката
     */
    public function posts_per_page_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['posts_per_page']) ? $settings['posts_per_page'] : 12;
        
        echo '<input type="number" 
                     id="posts_per_page"
                     name="parfume_reviews_settings[posts_per_page]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="50" 
                     class="small-text" />';
        echo '<p class="description">' . __('Брой постове за показване на страница в архивите.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за featured_perfumes_per_intensity настройката
     */
    public function featured_perfumes_per_intensity_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['featured_perfumes_per_intensity']) ? $settings['featured_perfumes_per_intensity'] : 3;
        
        echo '<input type="number" 
                     id="featured_perfumes_per_intensity"
                     name="parfume_reviews_settings[featured_perfumes_per_intensity]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="5" 
                     class="small-text" />';
        echo '<p class="description">' . __('Брой парфюми за показване в превю за всяка интензивност на архивната страница (1-5).', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Получава всички настройки
     */
    public function get_all_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'posts_per_page' => isset($settings['posts_per_page']) ? absint($settings['posts_per_page']) : 12,
            'featured_perfumes_per_intensity' => isset($settings['featured_perfumes_per_intensity']) ? absint($settings['featured_perfumes_per_intensity']) : 3
        );
    }
    
    /**
     * Валидира настройките
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Валидация за posts_per_page
        if (isset($input['posts_per_page'])) {
            $posts_per_page = absint($input['posts_per_page']);
            if ($posts_per_page >= 1 && $posts_per_page <= 50) {
                $validated['posts_per_page'] = $posts_per_page;
            } else {
                $validated['posts_per_page'] = 12;
                add_settings_error(
                    'parfume_reviews_messages',
                    'invalid_posts_per_page',
                    __('Постовете на страница трябва да бъдат между 1 и 50. Използвана е стойността по подразбиране.', 'parfume-reviews'),
                    'error'
                );
            }
        }
        
        // Валидация за featured_perfumes_per_intensity
        if (isset($input['featured_perfumes_per_intensity'])) {
            $featured_perfumes = absint($input['featured_perfumes_per_intensity']);
            if ($featured_perfumes >= 1 && $featured_perfumes <= 5) {
                $validated['featured_perfumes_per_intensity'] = $featured_perfumes;
            } else {
                $validated['featured_perfumes_per_intensity'] = 3;
                add_settings_error(
                    'parfume_reviews_messages',
                    'invalid_featured_perfumes',
                    __('Парфюмите за интензивност трябва да бъдат между 1 и 5. Използвана е стойността по подразбиране.', 'parfume-reviews'),
                    'error'
                );
            }
        }
        
        return $validated;
    }
    
    /**
     * Получава настройките с defaults
     */
    public function get_settings_with_defaults() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'posts_per_page' => isset($settings['posts_per_page']) ? absint($settings['posts_per_page']) : 12,
            'featured_perfumes_per_intensity' => isset($settings['featured_perfumes_per_intensity']) ? absint($settings['featured_perfumes_per_intensity']) : 3
        );
    }
    
    /**
     * Проверява дали настройките са валидни
     */
    public function validate_all_settings() {
        $settings = $this->get_all_settings();
        $errors = array();
        
        // Проверка за posts_per_page
        if ($settings['posts_per_page'] < 1 || $settings['posts_per_page'] > 50) {
            $errors[] = __('Постовете на страница трябва да бъдат между 1 и 50.', 'parfume-reviews');
        }
        
        // Проверка за featured_perfumes_per_intensity
        if ($settings['featured_perfumes_per_intensity'] < 1 || $settings['featured_perfumes_per_intensity'] > 5) {
            $errors[] = __('Парфюмите за интензивност трябва да бъдат между 1 и 5.', 'parfume-reviews');
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Зарежда настройките по подразбиране
     */
    public function load_defaults() {
        $defaults = array(
            'posts_per_page' => 12,
            'featured_perfumes_per_intensity' => 3
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        $merged_settings = wp_parse_args($current_settings, $defaults);
        
        return update_option('parfume_reviews_settings', $merged_settings);
    }
    
    /**
     * Ресетира настройките към стойностите по подразбиране
     */
    public function reset_to_defaults() {
        $defaults = array(
            'posts_per_page' => 12,
            'featured_perfumes_per_intensity' => 3
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        
        // Запазваме настройките от други компоненти
        foreach ($defaults as $key => $value) {
            $current_settings[$key] = $value;
        }
        
        return update_option('parfume_reviews_settings', $current_settings);
    }
    
    /**
     * Експортира настройките в JSON формат
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        
        return json_encode(array(
            'component' => 'general',
            'version' => PARFUME_REVIEWS_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Импортира настройки от JSON данни
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['component']) || $data['component'] !== 'general') {
            return new \WP_Error('invalid_component', __('Файлът не съдържа настройки за общи опции.', 'parfume-reviews'));
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
                'message' => __('Общите настройки са импортирани успешно.', 'parfume-reviews'),
                'imported_count' => count($validated_settings)
            );
        } else {
            return new \WP_Error('save_failed', __('Грешка при запазване на настройките.', 'parfume-reviews'));
        }
    }
}