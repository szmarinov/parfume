<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_URL class - Управлява URL настройките и структурата
 * 
 * Файл: includes/settings/class-settings-url.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_URL {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за URL структура
     */
    public function register_settings() {
        // URL Section
        add_settings_section(
            'parfume_reviews_url_section',
            __('URL настройки', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'parfume_slug',
            __('Parfume slug', 'parfume-reviews'),
            array($this, 'parfume_slug_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_url_section'
        );
        
        add_settings_field(
            'blog_slug',
            __('Blog slug', 'parfume-reviews'),
            array($this, 'blog_slug_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_url_section'
        );
        
        // Taxonomy URL fields
        $taxonomies = array(
            'marki_slug' => __('Марки slug', 'parfume-reviews'),
            'gender_slug' => __('Пол slug', 'parfume-reviews'),
            'aroma_type_slug' => __('Тип аромат slug', 'parfume-reviews'),
            'season_slug' => __('Сезон slug', 'parfume-reviews'),
            'intensity_slug' => __('Интензивност slug', 'parfume-reviews'),
            'notes_slug' => __('Ноти slug', 'parfume-reviews'),
            'perfumer_slug' => __('Парфюмеристи slug', 'parfume-reviews'),
        );
        
        foreach ($taxonomies as $slug_field => $label) {
            add_settings_field(
                $slug_field,
                $label,
                array($this, 'taxonomy_slug_callback'),
                'parfume-reviews-settings',
                'parfume_reviews_url_section',
                array('field' => $slug_field)
            );
        }
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Конфигурирайте URL структурата за различните типове страници.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с URL настройки
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="parfume_slug"><?php _e('Parfume slug', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->parfume_slug_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="blog_slug"><?php _e('Blog slug', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->blog_slug_callback(); ?>
                    </td>
                </tr>
                
                <?php
                // Taxonomy slugs
                $taxonomies = array(
                    'marki_slug' => __('Марки slug', 'parfume-reviews'),
                    'gender_slug' => __('Пол slug', 'parfume-reviews'),
                    'aroma_type_slug' => __('Тип аромат slug', 'parfume-reviews'),
                    'season_slug' => __('Сезон slug', 'parfume-reviews'),
                    'intensity_slug' => __('Интензивност slug', 'parfume-reviews'),
                    'notes_slug' => __('Ноти slug', 'parfume-reviews'),
                    'perfumer_slug' => __('Парфюмеристи slug', 'parfume-reviews'),
                );
                
                foreach ($taxonomies as $slug_field => $label):
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr($slug_field); ?>"><?php echo esc_html($label); ?></label>
                    </th>
                    <td>
                        <?php $this->taxonomy_slug_callback(array('field' => $slug_field)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php $this->render_url_structure_info(); ?>
        <?php
    }
    
    /**
     * Callback за parfume_slug настройката
     */
    public function parfume_slug_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfume';
        
        echo '<input type="text" 
                     id="parfume_slug"
                     name="parfume_reviews_settings[parfume_slug]" 
                     value="' . esc_attr($value) . '" 
                     class="regular-text" />';
        echo '<p class="description">' . __('URL slug за единични parfume постове.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за blog_slug настройката
     */
    public function blog_slug_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
        
        echo '<input type="text" 
                     id="blog_slug"
                     name="parfume_reviews_settings[blog_slug]" 
                     value="' . esc_attr($value) . '" 
                     class="regular-text" />';
        echo '<p class="description">' . __('URL slug за blog постове.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за taxonomy slug настройките
     */
    public function taxonomy_slug_callback($args) {
        $settings = get_option('parfume_reviews_settings', array());
        $field = $args['field'];
        $defaults = array(
            'marki_slug' => 'marki',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'notes_slug' => 'notes',
            'perfumer_slug' => 'perfumer',
        );
        $value = isset($settings[$field]) ? $settings[$field] : $defaults[$field];
        
        echo '<input type="text" 
                     id="' . esc_attr($field) . '"
                     name="parfume_reviews_settings[' . esc_attr($field) . ']" 
                     value="' . esc_attr($value) . '" 
                     class="regular-text" />';
        
        // Add view archive button
        $taxonomy_name = str_replace('_slug', '', $field);
        $archive_url = home_url('/' . $value . '/');
        echo ' <a href="' . esc_url($archive_url) . '" class="button view-archive-btn" target="_blank">';
        echo '<span class="dashicons dashicons-external"></span>' . __('Виж архива', 'parfume-reviews');
        echo '</a>';
    }
    
    /**
     * Рендерира информация за URL структурата
     */
    private function render_url_structure_info() {
        $settings = get_option('parfume_reviews_settings', array());
        ?>
        <div class="url-structure-info">
            <h3><?php _e('URL структура', 'parfume-reviews'); ?></h3>
            <p><?php _e('Примерни URLs базирани на текущите настройки:', 'parfume-reviews'); ?></p>
            <ul>
                <li><?php echo home_url('/' . (isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfume') . '/'); ?> - <?php _e('Архив на парфюми', 'parfume-reviews'); ?></li>
                <li><?php echo home_url('/' . (isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfume') . '/sample-parfume/'); ?> - <?php _e('Единичен парфюм', 'parfume-reviews'); ?></li>
                <li><?php echo home_url('/' . (isset($settings['marki_slug']) ? $settings['marki_slug'] : 'marki') . '/chanel/'); ?> - <?php _e('Парфюми от марка', 'parfume-reviews'); ?></li>
                <li><?php echo home_url('/' . (isset($settings['perfumer_slug']) ? $settings['perfumer_slug'] : 'perfumer') . '/jean-claude-ellena/'); ?> - <?php _e('Парфюми от парфюмерист', 'parfume-reviews'); ?></li>
                <li><?php echo home_url('/' . (isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog') . '/'); ?> - <?php _e('Blog архив', 'parfume-reviews'); ?></li>
            </ul>
            <p class="description">
                <strong><?php _e('Важно:', 'parfume-reviews'); ?></strong> 
                <?php _e('След промяна на URL настройките, permalink структурата се обновява автоматично.', 'parfume-reviews'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Валидира URL настройките преди запазване
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Списък на URL полетата
        $url_fields = array(
            'parfume_slug' => 'parfume',
            'blog_slug' => 'parfume-blog',
            'marki_slug' => 'marki',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'notes_slug' => 'notes',
            'perfumer_slug' => 'perfumer'
        );
        
        foreach ($url_fields as $field => $default) {
            if (isset($input[$field])) {
                $slug = sanitize_title($input[$field]);
                
                // Проверка за празен slug
                if (empty($slug)) {
                    add_settings_error(
                        'parfume_reviews_settings',
                        $field . '_error',
                        sprintf(__('Полето "%s" не може да бъде празно.', 'parfume-reviews'), $field),
                        'error'
                    );
                    $validated[$field] = $default;
                } else {
                    $validated[$field] = $slug;
                }
            }
        }
        
        // Проверка за дублирани slugs
        $used_slugs = array();
        foreach ($validated as $field => $slug) {
            if (in_array($slug, $used_slugs)) {
                add_settings_error(
                    'parfume_reviews_settings',
                    'duplicate_slug_error',
                    sprintf(__('Slug "%s" се използва повече от веднъж. Моля използвайте уникални slugs.', 'parfume-reviews'), $slug),
                    'error'
                );
            } else {
                $used_slugs[] = $slug;
            }
        }
        
        return $validated;
    }
    
    /**
     * Получава стойността на конкретна URL настройка
     */
    public function get_setting($setting_name, $default = null) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $defaults = array(
            'parfume_slug' => 'parfume',
            'blog_slug' => 'parfume-blog',
            'marki_slug' => 'marki',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'notes_slug' => 'notes',
            'perfumer_slug' => 'perfumer'
        );
        
        if (isset($defaults[$setting_name])) {
            return isset($settings[$setting_name]) ? $settings[$setting_name] : $defaults[$setting_name];
        }
        
        return isset($settings[$setting_name]) ? $settings[$setting_name] : $default;
    }
    
    /**
     * Запазва конкретна URL настройка
     */
    public function save_setting($setting_name, $value) {
        $settings = get_option('parfume_reviews_settings', array());
        $settings[$setting_name] = sanitize_title($value);
        
        $result = update_option('parfume_reviews_settings', $settings);
        
        // Flush rewrite rules след промяна в URL настройките
        if ($result) {
            update_option('parfume_reviews_flush_rewrite_rules', true);
        }
        
        return $result;
    }
    
    /**
     * Получава всички URL настройки
     */
    public function get_all_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        $defaults = array(
            'parfume_slug' => 'parfume',
            'blog_slug' => 'parfume-blog',
            'marki_slug' => 'marki',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'notes_slug' => 'notes',
            'perfumer_slug' => 'perfumer'
        );
        
        $url_settings = array();
        foreach ($defaults as $key => $default_value) {
            $url_settings[$key] = isset($settings[$key]) ? $settings[$key] : $default_value;
        }
        
        return $url_settings;
    }
    
    /**
     * Зарежда настройките по подразбиране
     */
    public function load_defaults() {
        $defaults = array(
            'parfume_slug' => 'parfume',
            'blog_slug' => 'parfume-blog',
            'marki_slug' => 'marki',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'notes_slug' => 'notes',
            'perfumer_slug' => 'perfumer'
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        $merged_settings = wp_parse_args($current_settings, $defaults);
        
        $result = update_option('parfume_reviews_settings', $merged_settings);
        
        if ($result) {
            update_option('parfume_reviews_flush_rewrite_rules', true);
        }
        
        return $result;
    }
    
    /**
     * Ресетира URL настройките към стойностите по подразбиране
     */
    public function reset_to_defaults() {
        $defaults = array(
            'parfume_slug' => 'parfume',
            'blog_slug' => 'parfume-blog',
            'marki_slug' => 'marki',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'notes_slug' => 'notes',
            'perfumer_slug' => 'perfumer'
        );
        
        $current_settings = get_option('parfume_reviews_settings', array());
        
        // Запазваме настройките от други компоненти
        foreach ($defaults as $key => $value) {
            $current_settings[$key] = $value;
        }
        
        $result = update_option('parfume_reviews_settings', $current_settings);
        
        if ($result) {
            update_option('parfume_reviews_flush_rewrite_rules', true);
        }
        
        return $result;
    }
    
    /**
     * Проверява URL структурата
     */
    public function test_url_structure() {
        $settings = $this->get_all_settings();
        $test_results = array();
        
        // Проверка за дублирани slugs
        $slugs = array_values($settings);
        $unique_slugs = array_unique($slugs);
        $test_results['has_duplicates'] = count($slugs) !== count($unique_slugs);
        
        // Проверка за невалидни символи
        $invalid_slugs = array();
        foreach ($settings as $field => $slug) {
            if ($slug !== sanitize_title($slug)) {
                $invalid_slugs[] = $field;
            }
        }
        $test_results['invalid_slugs'] = $invalid_slugs;
        
        // Проверка за заети WordPress slugs
        $reserved_slugs = array('admin', 'wp-admin', 'wp-content', 'wp-includes', 'index', 'search');
        $conflicts = array();
        foreach ($settings as $field => $slug) {
            if (in_array($slug, $reserved_slugs)) {
                $conflicts[] = array('field' => $field, 'slug' => $slug);
            }
        }
        $test_results['reserved_conflicts'] = $conflicts;
        
        return $test_results;
    }
    
    /**
     * Експортира URL настройките в JSON формат
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        
        return json_encode(array(
            'component' => 'url',
            'version' => PARFUME_REVIEWS_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Импортира URL настройки от JSON данни
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['component']) || $data['component'] !== 'url') {
            return new \WP_Error('invalid_component', __('Файлът не съдържа URL настройки.', 'parfume-reviews'));
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
            // Flush rewrite rules след импорт
            update_option('parfume_reviews_flush_rewrite_rules', true);
            
            return array(
                'success' => true,
                'message' => __('URL настройките са импортирани успешно.', 'parfume-reviews'),
                'imported_count' => count($validated_settings)
            );
        } else {
            return new \WP_Error('save_failed', __('Грешка при запазване на настройките.', 'parfume-reviews'));
        }
    }
}