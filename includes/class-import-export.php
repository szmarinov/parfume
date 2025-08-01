<?php
namespace Parfume_Reviews;

/**
 * Import_Export class - управлява импорт и експорт на данни
 * РЕВИЗИРАНА ВЕРСИЯ: Пълна функционалност с error handling
 * 
 * Файл: includes/class-import-export.php
 */
class Import_Export {
    
    /**
     * Максимален размер на файл (10MB)
     */
    const MAX_FILE_SIZE = 10 * 1024 * 1024;
    
    /**
     * Позволени типове файлове
     */
    const ALLOWED_EXTENSIONS = array('json');
    
    /**
     * Максимален брой парфюми за импорт наведнъж
     */
    const MAX_IMPORT_ITEMS = 1000;
    
    public function __construct() {
        // Регистрираме всички AJAX handlers и admin hooks
        $this->register_hooks();
    }
    
    /**
     * Регистрира всички хукове и handlers
     */
    private function register_hooks() {
        // Admin handlers
        add_action('admin_init', array($this, 'handle_import'));
        add_action('admin_init', array($this, 'handle_export'));
        add_action('admin_init', array($this, 'handle_perfumer_import'));
        add_action('admin_init', array($this, 'handle_perfumer_export'));
        add_action('admin_init', array($this, 'handle_backup_export'));
        add_action('admin_init', array($this, 'handle_settings_import'));
        add_action('admin_init', array($this, 'handle_settings_export'));
        
        // AJAX handlers
        add_action('wp_ajax_parfume_batch_import', array($this, 'ajax_batch_import'));
        add_action('wp_ajax_perfumer_batch_import', array($this, 'ajax_perfumer_batch_import'));
        add_action('wp_ajax_validate_import_file', array($this, 'ajax_validate_import_file'));
        add_action('wp_ajax_get_import_progress', array($this, 'ajax_get_import_progress'));
        
        // Scheduled backup
        add_action('parfume_reviews_auto_backup', array($this, 'perform_auto_backup'));
    }
    
    /**
     * Handles parfume import from uploaded JSON file
     */
    public function handle_import() {
        if (!isset($_POST['parfume_import_nonce']) || !wp_verify_nonce($_POST['parfume_import_nonce'], 'parfume_import')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        // Валидираме качения файл
        $validation_result = $this->validate_uploaded_file('parfume_import_file');
        if (is_wp_error($validation_result)) {
            add_settings_error('parfume_import', 'parfume_import_error', $validation_result->get_error_message(), 'error');
            return;
        }
        
        $file_content = file_get_contents($_FILES['parfume_import_file']['tmp_name']);
        if ($file_content === false) {
            add_settings_error('parfume_import', 'parfume_import_error', __('Не може да се прочете качения файл.', 'parfume-reviews'), 'error');
            return;
        }
        
        $data = json_decode($file_content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            add_settings_error('parfume_import', 'parfume_import_error', __('Невалиден JSON файл. Моля проверете формата на файла.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Проверяваме структурата на данните
        $validation_result = $this->validate_parfume_data($data);
        if (is_wp_error($validation_result)) {
            add_settings_error('parfume_import', 'parfume_import_error', $validation_result->get_error_message(), 'error');
            return;
        }
        
        // Извършваме импорта
        $import_result = $this->import_parfumes($data);
        if (is_wp_error($import_result)) {
            add_settings_error('parfume_import', 'parfume_import_error', $import_result->get_error_message(), 'error');
        } else {
            add_settings_error('parfume_import', 'parfume_import_success', 
                sprintf(__('Успешно импортирани %d парфюма.', 'parfume-reviews'), $import_result), 'updated');
        }
    }
    
    /**
     * Handles parfume export to JSON
     */
    public function handle_export() {
        if (!isset($_POST['parfume_export_nonce']) || !wp_verify_nonce($_POST['parfume_export_nonce'], 'parfume_export')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $parfumes = $this->export_parfumes();
        if (is_wp_error($parfumes)) {
            add_settings_error('parfume_export', 'parfume_export_error', $parfumes->get_error_message(), 'error');
            return;
        }
        
        $this->send_json_download($parfumes, 'parfumes-export-' . date('Y-m-d-H-i-s') . '.json');
    }
    
    /**
     * Handles perfumer import from uploaded JSON file
     */
    public function handle_perfumer_import() {
        if (!isset($_POST['perfumer_import_nonce']) || !wp_verify_nonce($_POST['perfumer_import_nonce'], 'perfumer_import')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        // Валидираме качения файл
        $validation_result = $this->validate_uploaded_file('perfumer_import_file');
        if (is_wp_error($validation_result)) {
            add_settings_error('perfumer_import', 'perfumer_import_error', $validation_result->get_error_message(), 'error');
            return;
        }
        
        $file_content = file_get_contents($_FILES['perfumer_import_file']['tmp_name']);
        if ($file_content === false) {
            add_settings_error('perfumer_import', 'perfumer_import_error', __('Не може да се прочете качения файл.', 'parfume-reviews'), 'error');
            return;
        }
        
        $data = json_decode($file_content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            add_settings_error('perfumer_import', 'perfumer_import_error', __('Невалиден JSON файл. Моля проверете формата на файла.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Извършваме импорта на парфюмеристи
        $import_result = $this->import_perfumers($data);
        if (is_wp_error($import_result)) {
            add_settings_error('perfumer_import', 'perfumer_import_error', $import_result->get_error_message(), 'error');
        } else {
            add_settings_error('perfumer_import', 'perfumer_import_success', 
                sprintf(__('Успешно импортирани %d парфюмериста.', 'parfume-reviews'), $import_result), 'updated');
        }
    }
    
    /**
     * Handles perfumer export to JSON
     */
    public function handle_perfumer_export() {
        if (!isset($_POST['perfumer_export_nonce']) || !wp_verify_nonce($_POST['perfumer_export_nonce'], 'perfumer_export')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $perfumers = $this->export_perfumers();
        if (is_wp_error($perfumers)) {
            add_settings_error('perfumer_export', 'perfumer_export_error', $perfumers->get_error_message(), 'error');
            return;
        }
        
        $this->send_json_download($perfumers, 'perfumers-export-' . date('Y-m-d-H-i-s') . '.json');
    }
    
    /**
     * Handles backup export
     */
    public function handle_backup_export() {
        if (!isset($_POST['backup_export_nonce']) || !wp_verify_nonce($_POST['backup_export_nonce'], 'backup_export')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $backup_data = $this->create_full_backup();
        if (is_wp_error($backup_data)) {
            add_settings_error('backup_export', 'backup_export_error', $backup_data->get_error_message(), 'error');
            return;
        }
        
        $this->send_json_download($backup_data, 'parfume-reviews-backup-' . date('Y-m-d-H-i-s') . '.json');
    }
    
    /**
     * Handles settings import
     */
    public function handle_settings_import() {
        if (!isset($_POST['settings_import_nonce']) || !wp_verify_nonce($_POST['settings_import_nonce'], 'settings_import')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        // Валидираме качения файл
        $validation_result = $this->validate_uploaded_file('settings_import_file');
        if (is_wp_error($validation_result)) {
            add_settings_error('settings_import', 'settings_import_error', $validation_result->get_error_message(), 'error');
            return;
        }
        
        $file_content = file_get_contents($_FILES['settings_import_file']['tmp_name']);
        $data = json_decode($file_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            add_settings_error('settings_import', 'settings_import_error', __('Невалиден JSON файл.', 'parfume-reviews'), 'error');
            return;
        }
        
        $import_result = $this->import_settings($data);
        if (is_wp_error($import_result)) {
            add_settings_error('settings_import', 'settings_import_error', $import_result->get_error_message(), 'error');
        } else {
            add_settings_error('settings_import', 'settings_import_success', __('Настройките са успешно импортирани.', 'parfume-reviews'), 'updated');
        }
    }
    
    /**
     * Handles settings export
     */
    public function handle_settings_export() {
        if (!isset($_POST['settings_export_nonce']) || !wp_verify_nonce($_POST['settings_export_nonce'], 'settings_export')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $settings = $this->export_settings();
        $this->send_json_download($settings, 'parfume-reviews-settings-' . date('Y-m-d-H-i-s') . '.json');
    }
    
    /**
     * Валидира качен файл
     */
    private function validate_uploaded_file($field_name) {
        if (empty($_FILES[$field_name]['tmp_name']) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error('invalid_file', __('Моля изберете валиден файл за импорт.', 'parfume-reviews'));
        }
        
        // Проверяваме типа на файла
        $file_info = pathinfo($_FILES[$field_name]['name']);
        if (empty($file_info['extension']) || !in_array(strtolower($file_info['extension']), self::ALLOWED_EXTENSIONS)) {
            return new \WP_Error('invalid_extension', __('Позволени са само JSON файлове.', 'parfume-reviews'));
        }
        
        // Проверяваме размера
        if ($_FILES[$field_name]['size'] > self::MAX_FILE_SIZE) {
            return new \WP_Error('file_too_large', __('Файлът е твърде голям. Максималният размер е 10MB.', 'parfume-reviews'));
        }
        
        return true;
    }
    
    /**
     * Валидира структурата на данните за парфюми
     */
    private function validate_parfume_data($data) {
        if (!isset($data['parfumes']) || !is_array($data['parfumes'])) {
            return new \WP_Error('invalid_structure', __('JSON файлът не съдържа валидни данни за парфюми.', 'parfume-reviews'));
        }
        
        if (count($data['parfumes']) > self::MAX_IMPORT_ITEMS) {
            return new \WP_Error('too_many_items', 
                sprintf(__('Твърде много парфюми за импорт. Максимум: %d', 'parfume-reviews'), self::MAX_IMPORT_ITEMS));
        }
        
        // Проверяваме първите няколко записа за валидност
        $sample_size = min(5, count($data['parfumes']));
        for ($i = 0; $i < $sample_size; $i++) {
            $parfume = $data['parfumes'][$i];
            if (!isset($parfume['title']) || empty($parfume['title'])) {
                return new \WP_Error('invalid_parfume_data', 
                    sprintf(__('Парфюм #%d няма валидно заглавие.', 'parfume-reviews'), $i + 1));
            }
        }
        
        return true;
    }
    
    /**
     * Импортира парфюми от масив данни
     */
    private function import_parfumes($data) {
        if (!isset($data['parfumes']) || !is_array($data['parfumes'])) {
            return new \WP_Error('no_data', __('Няма данни за импорт.', 'parfume-reviews'));
        }
        
        $imported_count = 0;
        $errors = array();
        
        foreach ($data['parfumes'] as $index => $parfume_data) {
            $result = $this->import_single_parfume($parfume_data);
            if (is_wp_error($result)) {
                $errors[] = sprintf(__('Парфюм #%d: %s', 'parfume-reviews'), $index + 1, $result->get_error_message());
            } else {
                $imported_count++;
            }
            
            // Прекъсваме ако има твърде много грешки
            if (count($errors) > 10) {
                break;
            }
        }
        
        if (!empty($errors)) {
            $error_message = __('Възникнаха грешки при импорта:', 'parfume-reviews') . "\n" . implode("\n", array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $error_message .= "\n" . sprintf(__('... и още %d грешки.', 'parfume-reviews'), count($errors) - 5);
            }
            return new \WP_Error('import_errors', $error_message);
        }
        
        return $imported_count;
    }
    
    /**
     * Импортира един парфюм
     */
    private function import_single_parfume($parfume_data) {
        // Валидираме задължителните полета
        if (empty($parfume_data['title'])) {
            return new \WP_Error('missing_title', __('Липсва заглавие на парфюма.', 'parfume-reviews'));
        }
        
        // Създаваме поста
        $post_data = array(
            'post_title' => sanitize_text_field($parfume_data['title']),
            'post_content' => isset($parfume_data['content']) ? wp_kses_post($parfume_data['content']) : '',
            'post_excerpt' => isset($parfume_data['excerpt']) ? sanitize_textarea_field($parfume_data['excerpt']) : '',
            'post_status' => 'publish',
            'post_type' => 'parfume',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Добавяме meta данни
        $this->import_parfume_meta($post_id, $parfume_data);
        
        // Добавяме таксономии
        $this->import_parfume_taxonomies($post_id, $parfume_data);
        
        return $post_id;
    }
    
    /**
     * Импортира meta данни за парфюм
     */
    private function import_parfume_meta($post_id, $parfume_data) {
        $meta_fields = array(
            'parfume_rating' => 'sanitize_text_field',
            'parfume_price' => 'sanitize_text_field',
            'parfume_year' => 'intval',
            'parfume_concentration' => 'sanitize_text_field',
            'parfume_volume' => 'sanitize_text_field',
            'parfume_longevity' => 'sanitize_text_field',
            'parfume_sillage' => 'sanitize_text_field',
            'parfume_projection' => 'sanitize_text_field'
        );
        
        foreach ($meta_fields as $field => $sanitize_function) {
            if (isset($parfume_data[$field])) {
                $value = call_user_func($sanitize_function, $parfume_data[$field]);
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Специално обработване на stores данни
        if (isset($parfume_data['stores']) && is_array($parfume_data['stores'])) {
            $stores = array();
            foreach ($parfume_data['stores'] as $store_data) {
                if (isset($store_data['name']) && isset($store_data['url'])) {
                    $stores[] = array(
                        'name' => sanitize_text_field($store_data['name']),
                        'url' => esc_url_raw($store_data['url']),
                        'price' => isset($store_data['price']) ? sanitize_text_field($store_data['price']) : '',
                        'affiliate_url' => isset($store_data['affiliate_url']) ? esc_url_raw($store_data['affiliate_url']) : '',
                        'promo_code' => isset($store_data['promo_code']) ? sanitize_text_field($store_data['promo_code']) : ''
                    );
                }
            }
            update_post_meta($post_id, 'parfume_stores', $stores);
        }
    }
    
    /**
     * Импортира таксономии за парфюм
     */
    private function import_parfume_taxonomies($post_id, $parfume_data) {
        $taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($taxonomies as $taxonomy) {
            if (isset($parfume_data[$taxonomy]) && is_array($parfume_data[$taxonomy])) {
                $terms = array();
                foreach ($parfume_data[$taxonomy] as $term_name) {
                    $term = get_term_by('name', $term_name, $taxonomy);
                    if (!$term) {
                        // Създаваме термина ако не съществува
                        $new_term = wp_insert_term($term_name, $taxonomy);
                        if (!is_wp_error($new_term)) {
                            $terms[] = $new_term['term_id'];
                        }
                    } else {
                        $terms[] = $term->term_id;
                    }
                }
                if (!empty($terms)) {
                    wp_set_post_terms($post_id, $terms, $taxonomy);
                }
            }
        }
    }
    
    /**
     * Експортира всички парфюми
     */
    private function export_parfumes() {
        $args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(),
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        );
        
        $parfumes_query = new \WP_Query($args);
        
        if (!$parfumes_query->have_posts()) {
            return new \WP_Error('no_parfumes', __('Няма парфюми за експорт.', 'parfume-reviews'));
        }
        
        $export_data = array(
            'version' => PARFUME_REVIEWS_VERSION,
            'export_date' => current_time('mysql'),
            'parfumes' => array()
        );
        
        while ($parfumes_query->have_posts()) {
            $parfumes_query->the_post();
            $post_id = get_the_ID();
            
            $parfume_data = array(
                'title' => get_the_title(),
                'content' => get_the_content(),
                'excerpt' => get_the_excerpt(),
                'parfume_rating' => get_post_meta($post_id, 'parfume_rating', true),
                'parfume_price' => get_post_meta($post_id, 'parfume_price', true),
                'parfume_year' => get_post_meta($post_id, 'parfume_year', true),
                'parfume_concentration' => get_post_meta($post_id, 'parfume_concentration', true),
                'parfume_volume' => get_post_meta($post_id, 'parfume_volume', true),
                'parfume_longevity' => get_post_meta($post_id, 'parfume_longevity', true),
                'parfume_sillage' => get_post_meta($post_id, 'parfume_sillage', true),
                'parfume_projection' => get_post_meta($post_id, 'parfume_projection', true),
                'stores' => get_post_meta($post_id, 'parfume_stores', true)
            );
            
            // Добавяме таксономии
            $taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
                if (!is_wp_error($terms)) {
                    $parfume_data[$taxonomy] = $terms;
                }
            }
            
            $export_data['parfumes'][] = $parfume_data;
        }
        
        wp_reset_postdata();
        
        return $export_data;
    }
    
    /**
     * Импортира парфюмеристи
     */
    private function import_perfumers($data) {
        if (!isset($data['perfumers']) || !is_array($data['perfumers'])) {
            return new \WP_Error('no_data', __('Няма данни за импорт на парфюмеристи.', 'parfume-reviews'));
        }
        
        $imported_count = 0;
        
        foreach ($data['perfumers'] as $perfumer_data) {
            if (empty($perfumer_data['name'])) {
                continue;
            }
            
            // Проверяваме дали терминът вече съществува
            $existing_term = get_term_by('name', $perfumer_data['name'], 'perfumer');
            if ($existing_term) {
                continue; // Пропускаме ако вече съществува
            }
            
            $term_data = array(
                'description' => isset($perfumer_data['description']) ? sanitize_textarea_field($perfumer_data['description']) : ''
            );
            
            $result = wp_insert_term($perfumer_data['name'], 'perfumer', $term_data);
            if (!is_wp_error($result)) {
                $imported_count++;
                
                // Добавяме meta данни ако има
                if (isset($perfumer_data['meta']) && is_array($perfumer_data['meta'])) {
                    foreach ($perfumer_data['meta'] as $meta_key => $meta_value) {
                        update_term_meta($result['term_id'], sanitize_key($meta_key), sanitize_text_field($meta_value));
                    }
                }
            }
        }
        
        return $imported_count;
    }
    
    /**
     * Експортира всички парфюмеристи
     */
    private function export_perfumers() {
        $perfumers = get_terms(array(
            'taxonomy' => 'perfumer',
            'hide_empty' => false
        ));
        
        if (is_wp_error($perfumers)) {
            return new \WP_Error('export_error', __('Грешка при експорт на парфюмеристи.', 'parfume-reviews'));
        }
        
        $export_data = array(
            'version' => PARFUME_REVIEWS_VERSION,
            'export_date' => current_time('mysql'),
            'perfumers' => array()
        );
        
        foreach ($perfumers as $perfumer) {
            $perfumer_data = array(
                'name' => $perfumer->name,
                'description' => $perfumer->description,
                'meta' => get_term_meta($perfumer->term_id)
            );
            
            $export_data['perfumers'][] = $perfumer_data;
        }
        
        return $export_data;
    }
    
    /**
     * Създава пълен backup на всички данни
     */
    private function create_full_backup() {
        $backup_data = array(
            'version' => PARFUME_REVIEWS_VERSION,
            'backup_date' => current_time('mysql'),
            'parfumes' => array(),
            'perfumers' => array(),
            'settings' => array()
        );
        
        // Експортираме парфюми
        $parfumes_export = $this->export_parfumes();
        if (!is_wp_error($parfumes_export) && isset($parfumes_export['parfumes'])) {
            $backup_data['parfumes'] = $parfumes_export['parfumes'];
        }
        
        // Експортираме парфюмеристи
        $perfumers_export = $this->export_perfumers();
        if (!is_wp_error($perfumers_export) && isset($perfumers_export['perfumers'])) {
            $backup_data['perfumers'] = $perfumers_export['perfumers'];
        }
        
        // Експортираме настройки
        $backup_data['settings'] = $this->export_settings();
        
        // Експортираме всички таксономии
        $backup_data['taxonomies'] = $this->export_all_taxonomies();
        
        return $backup_data;
    }
    
    /**
     * Експортира всички настройки на плъгина
     */
    private function export_settings() {
        global $wpdb;
        
        $settings = array();
        
        // Получаваме всички опции на плъгина
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                'parfume_reviews_%'
            )
        );
        
        foreach ($results as $option) {
            $settings[$option->option_name] = maybe_unserialize($option->option_value);
        }
        
        return $settings;
    }
    
    /**
     * Импортира настройки
     */
    private function import_settings($data) {
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return new \WP_Error('no_settings', __('Няма настройки за импорт.', 'parfume-reviews'));
        }
        
        $imported_count = 0;
        
        foreach ($data['settings'] as $option_name => $option_value) {
            // Проверяваме дали опцията принадлежи на нашия плъгин
            if (strpos($option_name, 'parfume_reviews_') === 0) {
                update_option($option_name, $option_value);
                $imported_count++;
            }
        }
        
        return $imported_count;
    }
    
    /**
     * Експортира всички таксономии и термини
     */
    private function export_all_taxonomies() {
        $taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        $taxonomy_data = array();
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($terms)) {
                $taxonomy_data[$taxonomy] = array();
                foreach ($terms as $term) {
                    $taxonomy_data[$taxonomy][] = array(
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'description' => $term->description,
                        'meta' => get_term_meta($term->term_id)
                    );
                }
            }
        }
        
        return $taxonomy_data;
    }
    
    /**
     * Изпраща JSON файл за сваляне
     */
    private function send_json_download($data, $filename) {
        $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Изчистваме output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json_data));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $json_data;
        exit;
    }
    
    /**
     * AJAX handler за batch импорт
     */
    public function ajax_batch_import() {
        check_ajax_referer('parfume_batch_import', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $batch_data = json_decode(stripslashes($_POST['batch_data']), true);
        if (!$batch_data) {
            wp_send_json_error(__('Invalid batch data', 'parfume-reviews'));
        }
        
        $results = array();
        foreach ($batch_data as $parfume_data) {
            $result = $this->import_single_parfume($parfume_data);
            $results[] = array(
                'success' => !is_wp_error($result),
                'data' => is_wp_error($result) ? $result->get_error_message() : $result
            );
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler за валидиране на импорт файл
     */
    public function ajax_validate_import_file() {
        check_ajax_referer('validate_import_file', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $validation_result = $this->validate_uploaded_file('import_file');
        if (is_wp_error($validation_result)) {
            wp_send_json_error($validation_result->get_error_message());
        }
        
        wp_send_json_success(__('File is valid', 'parfume-reviews'));
    }
    
    /**
     * Автоматично бекъпиране (scheduled event)
     */
    public function perform_auto_backup() {
        $settings = get_option('parfume_reviews_import_export_settings', array());
        
        if (empty($settings['backup_enabled'])) {
            return;
        }
        
        $backup_data = $this->create_full_backup();
        if (is_wp_error($backup_data)) {
            return;
        }
        
        // Съхраняваме backup файла в uploads директорията
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/parfume-reviews-backups/';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $filename = 'auto-backup-' . date('Y-m-d-H-i-s') . '.json';
        $file_path = $backup_dir . $filename;
        
        $json_data = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($file_path, $json_data);
        
        // Изтриваме стари backup файлове (запазваме само последните 10)
        $this->cleanup_old_backups($backup_dir);
    }
    
    /**
     * Изтрива стари backup файлове
     */
    private function cleanup_old_backups($backup_dir) {
        $files = glob($backup_dir . 'auto-backup-*.json');
        if (count($files) > 10) {
            // Сортираме по дата на модификация
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Изтриваме най-старите файлове
            $files_to_delete = array_slice($files, 0, count($files) - 10);
            foreach ($files_to_delete as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Получава статистики за импорт/експорт
     */
    public function get_import_export_stats() {
        $stats = array(
            'total_parfumes' => wp_count_posts('parfume')->publish,
            'total_perfumers' => wp_count_terms('perfumer'),
            'last_export' => get_option('parfume_reviews_last_export_date', ''),
            'last_import' => get_option('parfume_reviews_last_import_date', ''),
            'auto_backup_enabled' => get_option('parfume_reviews_backup_enabled', false)
        );
        
        return $stats;
    }
    
    /**
     * Wrapper methods за backward compatibility
     */
    public function export_data() {
        return $this->export_parfumes();
    }
    
    public function import_data($data) {
        return $this->import_parfumes($data);
    }
}