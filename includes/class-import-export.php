<?php
namespace Parfume_Reviews;

/**
 * Import Export class - управлява импорт и експорт на парфюмни данни
 * 
 * Файл: includes/class-import-export.php
 * РЕВИЗИРАНА ВЕРСИЯ - ПОДОБРЕНА SECURITY И ФУНКЦИОНАЛНОСТ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import_Export класа
 * ВАЖНО: Управлява импорт/експорт на парфюми, таксономии и настройки
 */
class Import_Export {
    
    /**
     * Максимален размер на файл за импорт (в байтове)
     * @var int
     */
    private $max_file_size = 10485760; // 10MB
    
    /**
     * Поддържани формати за импорт
     * @var array
     */
    private $supported_formats = array('json', 'csv');
    
    /**
     * Constructor
     * ВАЖНО: Инициализира всички hook-ове
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Инициализира hook-ове
     * ВАЖНО: Регистрира всички необходими WordPress hook-ове
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_init', array($this, 'handle_import'));
        add_action('admin_init', array($this, 'handle_export'));
        add_action('admin_init', array($this, 'handle_bulk_export'));
        
        // AJAX hooks
        add_action('wp_ajax_parfume_validate_import', array($this, 'ajax_validate_import'));
        add_action('wp_ajax_parfume_preview_import', array($this, 'ajax_preview_import'));
        add_action('wp_ajax_parfume_export_progress', array($this, 'ajax_export_progress'));
        
        // Admin menu hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * РАЗДЕЛ 1: IMPORT FUNCTIONALITY
     */
    
    /**
     * Обработва импорт заявки
     * ВАЖНО: Основната функция за импорт на данни
     */
    public function handle_import() {
        if (!isset($_POST['parfume_import_nonce']) || !wp_verify_nonce($_POST['parfume_import_nonce'], 'parfume_import')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        // Validate file upload
        $validation = $this->validate_import_file();
        if (is_wp_error($validation)) {
            add_settings_error('parfume_import', 'parfume_import_error', $validation->get_error_message(), 'error');
            return;
        }
        
        // Process import
        $result = $this->process_import($validation['file_path'], $validation['format']);
        
        if (is_wp_error($result)) {
            add_settings_error('parfume_import', 'parfume_import_error', $result->get_error_message(), 'error');
        } else {
            $message = sprintf(
                __('Импортът завърши успешно: %d импортирани, %d обновени, %d пропуснати.', 'parfume-reviews'),
                $result['imported'],
                $result['updated'],
                $result['skipped']
            );
            
            if (!empty($result['errors'])) {
                $message .= ' ' . __('Грешки:', 'parfume-reviews') . ' ' . implode('; ', array_slice($result['errors'], 0, 3));
                if (count($result['errors']) > 3) {
                    $message .= sprintf(__(' и още %d грешки.', 'parfume-reviews'), count($result['errors']) - 3);
                }
            }
            
            add_settings_error('parfume_import', 'parfume_import_success', $message, 'updated');
        }
        
        // Clean up temp file
        if (file_exists($validation['file_path'])) {
            unlink($validation['file_path']);
        }
    }
    
    /**
     * Валидира импорт файла
     * ВАЖНО: Проверява файла преди импорт
     */
    private function validate_import_file() {
        if (empty($_FILES['parfume_import_file']['tmp_name']) || $_FILES['parfume_import_file']['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error('file_error', __('Моля изберете валиден файл за импорт.', 'parfume-reviews'));
        }
        
        // Check file size
        if ($_FILES['parfume_import_file']['size'] > $this->max_file_size) {
            return new \WP_Error('file_size', sprintf(__('Файлът е твърде голям. Максималният размер е %s.', 'parfume-reviews'), size_format($this->max_file_size)));
        }
        
        // Check file type
        $file_info = pathinfo($_FILES['parfume_import_file']['name']);
        $extension = strtolower($file_info['extension'] ?? '');
        
        if (!in_array($extension, $this->supported_formats)) {
            return new \WP_Error('file_format', sprintf(__('Неподдържан формат. Позволени са: %s.', 'parfume-reviews'), implode(', ', $this->supported_formats)));
        }
        
        // Validate file content
        $file_path = $_FILES['parfume_import_file']['tmp_name'];
        $content_validation = $this->validate_file_content($file_path, $extension);
        
        if (is_wp_error($content_validation)) {
            return $content_validation;
        }
        
        return array(
            'file_path' => $file_path,
            'format' => $extension,
            'original_name' => $_FILES['parfume_import_file']['name']
        );
    }
    
    /**
     * Валидира съдържанието на файла
     * ВАЖНО: Проверява дали съдържанието е валидно
     */
    private function validate_file_content($file_path, $format) {
        $content = file_get_contents($file_path);
        
        if ($content === false) {
            return new \WP_Error('read_error', __('Не може да се прочете качения файл.', 'parfume-reviews'));
        }
        
        switch ($format) {
            case 'json':
                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return new \WP_Error('json_error', __('Невалиден JSON файл: ', 'parfume-reviews') . json_last_error_msg());
                }
                
                if (!is_array($data)) {
                    return new \WP_Error('format_error', __('JSON файлът трябва да съдържа масив от обекти.', 'parfume-reviews'));
                }
                
                if (empty($data)) {
                    return new \WP_Error('empty_error', __('JSON файлът е празен.', 'parfume-reviews'));
                }
                break;
                
            case 'csv':
                $lines = str_getcsv($content, "\n");
                if (count($lines) < 2) {
                    return new \WP_Error('csv_error', __('CSV файлът трябва да има поне заглавен ред и един ред с данни.', 'parfume-reviews'));
                }
                break;
        }
        
        return true;
    }
    
    /**
     * Обработва импорта
     * ВАЖНО: Основната логика за импорт
     */
    private function process_import($file_path, $format) {
        $content = file_get_contents($file_path);
        
        if ($format === 'json') {
            return $this->process_json_import($content);
        } elseif ($format === 'csv') {
            return $this->process_csv_import($content);
        }
        
        return new \WP_Error('format_error', __('Неподдържан формат за импорт.', 'parfume-reviews'));
    }
    
    /**
     * Обработва JSON импорт
     * ВАЖНО: Импорт от JSON формат
     */
    private function process_json_import($content) {
        $data = json_decode($content, true);
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($data as $index => $item) {
            try {
                if (empty($item['title']) || !is_string($item['title'])) {
                    $skipped++;
                    $errors[] = sprintf(__('Елемент %d: Липсва или невалидно заглавие', 'parfume-reviews'), $index + 1);
                    continue;
                }
                
                // Check if post exists by title
                $existing = get_page_by_title(sanitize_text_field($item['title']), OBJECT, 'parfume');
                
                $post_data = array(
                    'post_title' => sanitize_text_field($item['title']),
                    'post_content' => isset($item['content']) ? wp_kses_post($item['content']) : '',
                    'post_excerpt' => isset($item['excerpt']) ? sanitize_text_field($item['excerpt']) : '',
                    'post_type' => 'parfume',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id()
                );
                
                if ($existing) {
                    $post_data['ID'] = $existing->ID;
                    $post_id = wp_update_post($post_data);
                    if (!is_wp_error($post_id)) {
                        $updated++;
                    }
                } else {
                    $post_id = wp_insert_post($post_data);
                    if (!is_wp_error($post_id)) {
                        $imported++;
                    }
                }
                
                if (is_wp_error($post_id)) {
                    $skipped++;
                    $errors[] = sprintf(__('Елемент %d: %s', 'parfume-reviews'), $index + 1, $post_id->get_error_message());
                    continue;
                }
                
                // Set featured image if provided
                if (!empty($item['featured_image']) && filter_var($item['featured_image'], FILTER_VALIDATE_URL)) {
                    $this->import_featured_image($post_id, $item['featured_image']);
                }
                
                // Set taxonomies
                $this->import_taxonomies($post_id, $item);
                
                // Set meta fields
                $this->import_meta_fields($post_id, $item);
                
                // Set stores
                if (!empty($item['stores']) && is_array($item['stores'])) {
                    $this->import_stores($post_id, $item['stores']);
                }
                
                // Custom hook for extensions
                do_action('parfume_reviews_after_import_item', $post_id, $item, $index);
                
            } catch (Exception $e) {
                $skipped++;
                $errors[] = sprintf(__('Елемент %d: %s', 'parfume-reviews'), $index + 1, $e->getMessage());
            }
        }
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }
    
    /**
     * Обработва CSV импорт
     * ВАЖНО: Импорт от CSV формат
     */
    private function process_csv_import($content) {
        $lines = str_getcsv($content, "\n");
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);
        
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($lines as $index => $line) {
            $data = str_getcsv($line);
            
            if (count($data) !== count($headers)) {
                $skipped++;
                $errors[] = sprintf(__('Ред %d: Несъответствие в броя колони', 'parfume-reviews'), $index + 2);
                continue;
            }
            
            $item = array_combine($headers, $data);
            
            // Convert CSV row to JSON-like structure
            $processed_item = $this->convert_csv_row_to_item($item);
            
            // Process like JSON item
            $json_data = array($processed_item);
            $result = $this->process_json_import(json_encode($json_data));
            
            $imported += $result['imported'];
            $updated += $result['updated'];
            $skipped += $result['skipped'];
            $errors = array_merge($errors, $result['errors']);
        }
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }
    
    /**
     * Преобразува CSV ред в структурирана данна
     */
    private function convert_csv_row_to_item($row) {
        $item = array();
        
        // Map CSV columns to item structure
        $column_mapping = array(
            'title' => array('title', 'name', 'parfume_name'),
            'content' => array('content', 'description', 'review'),
            'excerpt' => array('excerpt', 'summary', 'short_description'),
            'rating' => array('rating', 'score'),
            'brand' => array('brand', 'марка'),
            'gender' => array('gender', 'пол'),
            'notes' => array('notes', 'нотки'),
            'longevity' => array('longevity', 'издръжливост'),
            'sillage' => array('sillage', 'прожекция')
        );
        
        foreach ($column_mapping as $key => $possible_columns) {
            foreach ($possible_columns as $column) {
                if (isset($row[$column]) && !empty(trim($row[$column]))) {
                    if (in_array($key, array('brand', 'gender', 'notes'))) {
                        // Convert comma-separated values to array
                        $item[$key] = array_map('trim', explode(',', $row[$column]));
                    } else {
                        $item[$key] = trim($row[$column]);
                    }
                    break;
                }
            }
        }
        
        return $item;
    }
    
    /**
     * РАЗДЕЛ 2: IMPORT HELPER METHODS
     */
    
    /**
     * Импортира таксономии
     * ВАЖНО: Свързва парфюма с таксономии
     */
    private function import_taxonomies($post_id, $item) {
        $taxonomies = array(
            'gender' => isset($item['gender']) ? (array)$item['gender'] : array(),
            'aroma_type' => isset($item['aroma_type']) ? (array)$item['aroma_type'] : array(),
            'marki' => isset($item['brand']) ? (array)$item['brand'] : array(),
            'season' => isset($item['season']) ? (array)$item['season'] : array(),
            'intensity' => isset($item['intensity']) ? (array)$item['intensity'] : array(),
            'notes' => isset($item['notes']) ? (array)$item['notes'] : array(),
            'perfumer' => isset($item['perfumer']) ? (array)$item['perfumer'] : array(),
        );
        
        foreach ($taxonomies as $taxonomy => $terms) {
            if (!empty($terms) && taxonomy_exists($taxonomy)) {
                $term_ids = array();
                
                foreach ($terms as $term_name) {
                    $term_name = sanitize_text_field(trim($term_name));
                    if (empty($term_name)) continue;
                    
                    $term = term_exists($term_name, $taxonomy);
                    
                    if (!$term) {
                        $term = wp_insert_term($term_name, $taxonomy);
                    }
                    
                    if (!is_wp_error($term) && isset($term['term_id'])) {
                        $term_ids[] = intval($term['term_id']);
                    }
                }
                
                if (!empty($term_ids)) {
                    wp_set_object_terms($post_id, $term_ids, $taxonomy);
                }
            }
        }
    }
    
    /**
     * Импортира meta полета
     * ВАЖНО: Запазва custom fields на парфюма
     */
    private function import_meta_fields($post_id, $item) {
        $meta_fields = array(
            '_parfume_rating' => isset($item['rating']) && is_numeric($item['rating']) ? floatval($item['rating']) : 0,
            '_parfume_gender' => isset($item['gender_text']) ? sanitize_text_field($item['gender_text']) : '',
            '_parfume_release_year' => isset($item['release_year']) ? sanitize_text_field($item['release_year']) : '',
            '_parfume_longevity' => isset($item['longevity']) ? sanitize_text_field($item['longevity']) : '',
            '_parfume_sillage' => isset($item['sillage']) ? sanitize_text_field($item['sillage']) : '',
            '_parfume_bottle_size' => isset($item['bottle_size']) ? sanitize_text_field($item['bottle_size']) : '',
            '_parfume_pros' => isset($item['pros']) ? sanitize_textarea_field($item['pros']) : '',
            '_parfume_cons' => isset($item['cons']) ? sanitize_textarea_field($item['cons']) : '',
        );
        
        // Aroma chart fields
        if (isset($item['aroma_chart']) && is_array($item['aroma_chart'])) {
            $chart_fields = array('freshness', 'sweetness', 'intensity', 'warmth');
            foreach ($chart_fields as $field) {
                if (isset($item['aroma_chart'][$field])) {
                    $meta_fields['_parfume_' . $field] = intval($item['aroma_chart'][$field]);
                }
            }
        }
        
        foreach ($meta_fields as $key => $value) {
            if (!empty($value) || is_numeric($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }
    
    /**
     * Импортира stores данни
     * ВАЖНО: Запазва информация за магазини
     */
    private function import_stores($post_id, $stores_data) {
        $stores = array();
        
        foreach ($stores_data as $store) {
            if (empty($store['name'])) continue;
            
            $stores[] = array(
                'name' => sanitize_text_field($store['name']),
                'logo' => isset($store['logo']) && filter_var($store['logo'], FILTER_VALIDATE_URL) ? esc_url_raw($store['logo']) : '',
                'url' => isset($store['url']) && filter_var($store['url'], FILTER_VALIDATE_URL) ? esc_url_raw($store['url']) : '',
                'affiliate_url' => isset($store['affiliate_url']) && filter_var($store['affiliate_url'], FILTER_VALIDATE_URL) ? esc_url_raw($store['affiliate_url']) : '',
                'affiliate_class' => isset($store['affiliate_class']) ? sanitize_text_field($store['affiliate_class']) : '',
                'affiliate_rel' => isset($store['affiliate_rel']) ? sanitize_text_field($store['affiliate_rel']) : 'nofollow',
                'affiliate_target' => isset($store['affiliate_target']) ? sanitize_text_field($store['affiliate_target']) : '_blank',
                'affiliate_anchor' => isset($store['affiliate_anchor']) ? sanitize_text_field($store['affiliate_anchor']) : '',
                'promo_code' => isset($store['promo_code']) ? sanitize_text_field($store['promo_code']) : '',
                'promo_text' => isset($store['promo_text']) ? sanitize_text_field($store['promo_text']) : '',
                'price' => isset($store['price']) ? sanitize_text_field($store['price']) : '',
                'size' => isset($store['size']) ? sanitize_text_field($store['size']) : '',
                'availability' => isset($store['availability']) ? sanitize_text_field($store['availability']) : 'available',
                'shipping_cost' => isset($store['shipping_cost']) ? sanitize_text_field($store['shipping_cost']) : '',
                'last_updated' => current_time('mysql'),
            );
        }
        
        if (!empty($stores)) {
            update_post_meta($post_id, '_parfume_stores', $stores);
        }
    }
    
    /**
     * Импортира featured image
     * ВАЖНО: Изтегля и прикачва изображение
     */
    private function import_featured_image($post_id, $image_url) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        
        // Download the file
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        // Get file info
        $file_array = array(
            'name' => basename(parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $tmp,
        );
        
        // Validate file type
        $wp_filetype = wp_check_filetype($file_array['name']);
        if (!$wp_filetype['type'] || !in_array($wp_filetype['type'], array('image/jpeg', 'image/png', 'image/gif', 'image/webp'))) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        // Import the image
        $id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        return set_post_thumbnail($post_id, $id);
    }
    
    /**
     * РАЗДЕЛ 3: EXPORT FUNCTIONALITY
     */
    
    /**
     * Обработва експорт заявки
     * ВАЖНО: Основната функция за експорт на данни
     */
    public function handle_export() {
        if (!isset($_GET['parfume_export']) || !wp_verify_nonce($_GET['_wpnonce'], 'parfume_export')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $format = sanitize_text_field($_GET['format'] ?? 'json');
        $export_type = sanitize_text_field($_GET['export_type'] ?? 'all');
        
        if ($format === 'json') {
            $this->export_json($export_type);
        } elseif ($format === 'csv') {
            $this->export_csv($export_type);
        } else {
            wp_die(__('Неподдържан формат за експорт.', 'parfume-reviews'));
        }
    }
    
    /**
     * Експорт в JSON формат
     * ВАЖНО: Генерира JSON файл с парфюмни данни
     */
    private function export_json($export_type) {
        $args = $this->get_export_query_args($export_type);
        $query = new \WP_Query($args);
        
        $data = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $item = array(
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'featured_image' => get_the_post_thumbnail_url($post_id, 'full'),
                    'rating' => get_post_meta($post_id, '_parfume_rating', true),
                    'gender_text' => get_post_meta($post_id, '_parfume_gender', true),
                    'release_year' => get_post_meta($post_id, '_parfume_release_year', true),
                    'longevity' => get_post_meta($post_id, '_parfume_longevity', true),
                    'sillage' => get_post_meta($post_id, '_parfume_sillage', true),
                    'bottle_size' => get_post_meta($post_id, '_parfume_bottle_size', true),
                    'pros' => get_post_meta($post_id, '_parfume_pros', true),
                    'cons' => get_post_meta($post_id, '_parfume_cons', true),
                );
                
                // Aroma chart
                $item['aroma_chart'] = array(
                    'freshness' => get_post_meta($post_id, '_parfume_freshness', true),
                    'sweetness' => get_post_meta($post_id, '_parfume_sweetness', true),
                    'intensity' => get_post_meta($post_id, '_parfume_intensity', true),
                    'warmth' => get_post_meta($post_id, '_parfume_warmth', true),
                );
                
                // Taxonomies
                $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $key = ($taxonomy === 'marki') ? 'brand' : $taxonomy;
                        $item[$key] = $terms;
                    }
                }
                
                // Stores
                $stores = get_post_meta($post_id, '_parfume_stores', true);
                if (!empty($stores) && is_array($stores)) {
                    $item['stores'] = $stores;
                }
                
                $data[] = $item;
            }
        }
        
        wp_reset_postdata();
        
        // Output JSON file
        $filename = 'parfume-export-' . $export_type . '-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Експорт в CSV формат
     * ВАЖНО: Генерира CSV файл с парфюмни данни
     */
    private function export_csv($export_type) {
        $args = $this->get_export_query_args($export_type);
        $query = new \WP_Query($args);
        
        $filename = 'parfume-export-' . $export_type . '-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for proper UTF-8 encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        $headers = array(
            'Title', 'Brand', 'Rating', 'Release Year', 'Longevity', 'Sillage', 
            'Gender', 'Aroma Type', 'Season', 'Intensity', 'Notes', 'Perfumer',
            'Bottle Size', 'Pros', 'Cons', 'URL'
        );
        fputcsv($output, $headers);
        
        // Data
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $row = array(
                    get_the_title(),
                    $this->get_taxonomy_names($post_id, 'marki'),
                    get_post_meta($post_id, '_parfume_rating', true),
                    get_post_meta($post_id, '_parfume_release_year', true),
                    get_post_meta($post_id, '_parfume_longevity', true),
                    get_post_meta($post_id, '_parfume_sillage', true),
                    $this->get_taxonomy_names($post_id, 'gender'),
                    $this->get_taxonomy_names($post_id, 'aroma_type'),
                    $this->get_taxonomy_names($post_id, 'season'),
                    $this->get_taxonomy_names($post_id, 'intensity'),
                    $this->get_taxonomy_names($post_id, 'notes'),
                    $this->get_taxonomy_names($post_id, 'perfumer'),
                    get_post_meta($post_id, '_parfume_bottle_size', true),
                    str_replace(array("\r", "\n"), ' ', get_post_meta($post_id, '_parfume_pros', true)),
                    str_replace(array("\r", "\n"), ' ', get_post_meta($post_id, '_parfume_cons', true)),
                    get_permalink($post_id)
                );
                
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        wp_reset_postdata();
        exit;
    }
    
    /**
     * Bulk експорт заявки
     */
    public function handle_bulk_export() {
        if (!isset($_GET['parfume_bulk_export']) || !wp_verify_nonce($_GET['_wpnonce'], 'parfume_bulk_export')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $post_ids = array_map('intval', explode(',', $_GET['post_ids']));
        $format = sanitize_text_field($_GET['format'] ?? 'json');
        
        if ($format === 'json') {
            $this->bulk_export_json($post_ids);
        } elseif ($format === 'csv') {
            $this->bulk_export_csv($post_ids);
        }
    }
    
    /**
     * РАЗДЕЛ 4: HELPER METHODS
     */
    
    /**
     * Получава query args за експорт
     */
    private function get_export_query_args($export_type) {
        $args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        switch ($export_type) {
            case 'published':
                // Default args are fine
                break;
                
            case 'drafts':
                $args['post_status'] = 'draft';
                break;
                
            case 'featured':
                $args['meta_query'] = array(
                    array(
                        'key' => '_parfume_featured',
                        'value' => '1',
                        'compare' => '='
                    )
                );
                break;
                
            case 'recent':
                $args['date_query'] = array(
                    array(
                        'after' => '30 days ago'
                    )
                );
                break;
        }
        
        return $args;
    }
    
    /**
     * Получава имената на таксономия като string
     */
    private function get_taxonomy_names($post_id, $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }
        return implode(', ', $terms);
    }
    
    /**
     * РАЗДЕЛ 5: AJAX HANDLERS
     */
    
    /**
     * AJAX валидация на импорт файл
     */
    public function ajax_validate_import() {
        check_ajax_referer('parfume_import_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Недостатъчни права.', 'parfume-reviews'));
        }
        
        $validation = $this->validate_import_file();
        
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        } else {
            wp_send_json_success(array(
                'message' => __('Файлът е валиден и готов за импорт.', 'parfume-reviews'),
                'format' => $validation['format'],
                'filename' => $validation['original_name']
            ));
        }
    }
    
    /**
     * AJAX preview на импорт
     */
    public function ajax_preview_import() {
        check_ajax_referer('parfume_import_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Недостатъчни права.', 'parfume-reviews'));
        }
        
        $validation = $this->validate_import_file();
        
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        // Generate preview
        $content = file_get_contents($validation['file_path']);
        $preview_data = array();
        
        if ($validation['format'] === 'json') {
            $data = json_decode($content, true);
            $preview_data = array_slice($data, 0, 5); // First 5 items
        }
        
        wp_send_json_success(array(
            'preview' => $preview_data,
            'total_items' => count(json_decode($content, true))
        ));
    }
    
    /**
     * РАЗДЕЛ 6: ADMIN INTERFACE
     */
    
    /**
     * Добавя admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Импорт/Експорт', 'parfume-reviews'),
            __('Импорт/Експорт', 'parfume-reviews'),
            'edit_posts',
            'parfume-import-export',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin страница
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Импорт/Експорт на парфюми', 'parfume-reviews'); ?></h1>
            
            <div class="import-export-tabs">
                <h2 class="nav-tab-wrapper">
                    <a href="#import" class="nav-tab nav-tab-active"><?php _e('Импорт', 'parfume-reviews'); ?></a>
                    <a href="#export" class="nav-tab"><?php _e('Експорт', 'parfume-reviews'); ?></a>
                </h2>
                
                <div id="import" class="tab-content">
                    <?php $this->render_import_section(); ?>
                </div>
                
                <div id="export" class="tab-content" style="display: none;">
                    <?php $this->render_export_section(); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').hide();
                $(target).show();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Рендва import секция
     */
    private function render_import_section() {
        ?>
        <div class="import-section">
            <h3><?php _e('Импорт на парфюми', 'parfume-reviews'); ?></h3>
            
            <form method="post" enctype="multipart/form-data" class="import-form">
                <?php wp_nonce_field('parfume_import', 'parfume_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="parfume_import_file"><?php _e('Импорт файл', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="file" 
                                   id="parfume_import_file" 
                                   name="parfume_import_file" 
                                   accept=".json,.csv" 
                                   required>
                            <p class="description">
                                <?php printf(__('Максимален размер: %s. Поддържани формати: JSON, CSV.', 'parfume-reviews'), size_format($this->max_file_size)); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="import_options"><?php _e('Опции за импорт', 'parfume-reviews'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="update_existing" value="1" checked>
                                    <?php _e('Обновявай съществуващи парфюми', 'parfume-reviews'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="import_images" value="1" checked>
                                    <?php _e('Импортирай изображения', 'parfume-reviews'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="create_categories" value="1" checked>
                                    <?php _e('Създавай нови категории автоматично', 'parfume-reviews'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Импортирай', 'parfume-reviews'), 'primary', 'submit', false); ?>
                <button type="button" class="button" id="preview-import"><?php _e('Преглед', 'parfume-reviews'); ?></button>
            </form>
            
            <div id="import-preview" style="display: none;">
                <h4><?php _e('Преглед на импорт данни', 'parfume-reviews'); ?></h4>
                <div class="preview-content"></div>
            </div>
            
            <?php echo $this->get_json_format_instructions(); ?>
        </div>
        <?php
    }
    
    /**
     * Рендва export секция
     */
    private function render_export_section() {
        ?>
        <div class="export-section">
            <h3><?php _e('Експорт на парфюми', 'parfume-reviews'); ?></h3>
            
            <div class="export-options">
                <h4><?php _e('Какво да експортирам?', 'parfume-reviews'); ?></h4>
                
                <div class="export-type-options">
                    <label>
                        <input type="radio" name="export_type" value="all" checked>
                        <?php _e('Всички парфюми', 'parfume-reviews'); ?>
                    </label><br>
                    
                    <label>
                        <input type="radio" name="export_type" value="published">
                        <?php _e('Само публикувани', 'parfume-reviews'); ?>
                    </label><br>
                    
                    <label>
                        <input type="radio" name="export_type" value="featured">
                        <?php _e('Само препоръчани', 'parfume-reviews'); ?>
                    </label><br>
                    
                    <label>
                        <input type="radio" name="export_type" value="recent">
                        <?php _e('Последните 30 дни', 'parfume-reviews'); ?>
                    </label>
                </div>
                
                <h4><?php _e('Формат на експорт', 'parfume-reviews'); ?></h4>
                
                <div class="export-format-options">
                    <label>
                        <input type="radio" name="export_format" value="json" checked>
                        <?php _e('JSON формат', 'parfume-reviews'); ?>
                        <span class="description"><?php _e('(препоръчан за повторен импорт)', 'parfume-reviews'); ?></span>
                    </label><br>
                    
                    <label>
                        <input type="radio" name="export_format" value="csv">
                        <?php _e('CSV формат', 'parfume-reviews'); ?>
                        <span class="description"><?php _e('(за Excel и други приложения)', 'parfume-reviews'); ?></span>
                    </label>
                </div>
                
                <div class="export-actions">
                    <button type="button" class="button button-primary" id="start-export">
                        <?php _e('Стартирай експорт', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#start-export').click(function() {
                var exportType = $('input[name="export_type"]:checked').val();
                var exportFormat = $('input[name="export_format"]:checked').val();
                var nonce = '<?php echo wp_create_nonce('parfume_export'); ?>';
                
                var url = '<?php echo admin_url('edit.php?post_type=parfume&page=parfume-import-export'); ?>';
                url += '&parfume_export=1&format=' + exportFormat + '&export_type=' + exportType + '&_wpnonce=' + nonce;
                
                window.location.href = url;
            });
        });
        </script>
        <?php
    }
    
    /**
     * Получава инструкции за JSON формат
     */
    public function get_json_format_instructions() {
        ob_start();
        ?>
        <div class="json-format-instructions">
            <h3><?php _e('Инструкции за JSON формат', 'parfume-reviews'); ?></h3>
            <p><?php _e('JSON файлът трябва да бъде масив от обекти, където всеки обект представлява парфюм със следната структура:', 'parfume-reviews'); ?></p>
            
            <details>
                <summary><?php _e('Кликнете за да видите примерен JSON формат', 'parfume-reviews'); ?></summary>
                <pre><code>[
  {
    "title": "Име на парфюма",
    "content": "Детайлно ревю съдържание...",
    "excerpt": "Кратко описание...",
    "featured_image": "https://example.com/image.jpg",
    "rating": 4.5,
    "gender": ["Мъжки"],
    "gender_text": "За мъже",
    "aroma_type": ["EDT"],
    "brand": ["Dior"],
    "season": ["Зима", "Есен"],
    "intensity": ["Силен"],
    "notes": ["Ванилия", "Пачули"],
    "perfumer": ["Франсоа Демаши"],
    "release_year": "2018",
    "longevity": "8-10 часа",
    "sillage": "Умерен",
    "bottle_size": "100ml",
    "aroma_chart": {
      "freshness": 6,
      "sweetness": 7,
      "intensity": 8,
      "warmth": 9
    },
    "pros": "Отлична издръжливост",
    "cons": "Висока цена",
    "stores": [
      {
        "name": "Примерен магазин",
        "url": "https://example.com/product",
        "price": "120.00 лв.",
        "availability": "Наличен"
      }
    ]
  }
]</code></pre>
            </details>
            
            <div class="import-tips">
                <h4><?php _e('Съвети за импорт', 'parfume-reviews'); ?></h4>
                <ul>
                    <li><?php printf(__('Максимален размер на файла: %s', 'parfume-reviews'), size_format($this->max_file_size)); ?></li>
                    <li><?php _e('Приемат се JSON и CSV файлове', 'parfume-reviews'); ?></li>
                    <li><?php _e('Валидирайте JSON-а си с инструмент като JSONLint преди импорт', 'parfume-reviews'); ?></li>
                    <li><?php _e('Съществуващи парфюми със същото заглавие ще бъдат обновени', 'parfume-reviews'); ?></li>
                    <li><?php _e('Новите термини в таксономиите ще бъдат създадени автоматично', 'parfume-reviews'); ?></li>
                    <li><?php _e('Изображенията ще бъдат изтеглени и добавени в медийната библиотека', 'parfume-reviews'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .json-format-instructions pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 400px;
        }
        .json-format-instructions details {
            margin: 15px 0;
        }
        .json-format-instructions summary {
            cursor: pointer;
            font-weight: bold;
            padding: 10px;
            background: #e7e7e7;
            border-radius: 5px;
        }
        .import-tips {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        .import-export-tabs .nav-tab-wrapper {
            margin-bottom: 20px;
        }
        .tab-content {
            padding: 20px;
            background: white;
            border: 1px solid #ccd0d4;
            border-top: none;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'parfume-import-export') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'parfume-import-export',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-import-export.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-import-export', 'parfumeImportExport', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_import_ajax'),
            'strings' => array(
                'validating' => __('Валидиране на файла...', 'parfume-reviews'),
                'previewing' => __('Генериране на преглед...', 'parfume-reviews'),
                'exporting' => __('Експортиране...', 'parfume-reviews'),
                'error' => __('Възникна грешка.', 'parfume-reviews')
            )
        ));
    }
}

// End of file