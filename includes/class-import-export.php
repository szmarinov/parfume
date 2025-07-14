<?php
namespace Parfume_Reviews;

/**
 * Import_Export class - управлява импорт и експорт на данни
 * ПОПРАВЕНА ВЕРСИЯ - добавен импорт за парфюмеристи
 */
class Import_Export {
    public function __construct() {
        add_action('admin_init', array($this, 'handle_import'));
        add_action('admin_init', array($this, 'handle_export'));
        add_action('admin_init', array($this, 'handle_perfumer_import'));
        add_action('admin_init', array($this, 'handle_perfumer_export'));
    }
    
    public function handle_import() {
        if (!isset($_POST['parfume_import_nonce']) || !wp_verify_nonce($_POST['parfume_import_nonce'], 'parfume_import')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        if (empty($_FILES['parfume_import_file']['tmp_name']) || $_FILES['parfume_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error('parfume_import', 'parfume_import_error', __('Моля изберете валиден файл за импорт.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Check file type
        $file_info = pathinfo($_FILES['parfume_import_file']['name']);
        if (empty($file_info['extension']) || strtolower($file_info['extension']) !== 'json') {
            add_settings_error('parfume_import', 'parfume_import_error', __('Позволени са само JSON файлове.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Check file size (max 10MB)
        if ($_FILES['parfume_import_file']['size'] > 10 * 1024 * 1024) {
            add_settings_error('parfume_import', 'parfume_import_error', __('Файлът е твърде голям. Максималният размер е 10MB.', 'parfume-reviews'), 'error');
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
                
            } catch (Exception $e) {
                $skipped++;
                $errors[] = sprintf(__('Елемент %d: %s', 'parfume-reviews'), $index + 1, $e->getMessage());
            }
        }
        
        $message = sprintf(__('Импортът завърши: %d импортирани, %d обновени, %d пропуснати.', 'parfume-reviews'), $imported, $updated, $skipped);
        
        if (!empty($errors)) {
            $message .= ' ' . __('Грешки:', 'parfume-reviews') . ' ' . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= sprintf(__(' и още %d грешки.', 'parfume-reviews'), count($errors) - 5);
            }
        }
        
        add_settings_error('parfume_import', 'parfume_import_success', $message, $imported > 0 || $updated > 0 ? 'updated' : 'error');
    }
    
    /**
     * НОВА ФУНКЦИЯ - Обработва импорт на парфюмеристи
     */
    public function handle_perfumer_import() {
        if (!isset($_POST['perfumer_import_nonce']) || !wp_verify_nonce($_POST['perfumer_import_nonce'], 'perfumer_import')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        if (empty($_FILES['perfumer_import_file']['tmp_name']) || $_FILES['perfumer_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error('perfumer_import', 'perfumer_import_error', __('Моля изберете валиден файл за импорт на парфюмеристи.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Check file type
        $file_info = pathinfo($_FILES['perfumer_import_file']['name']);
        if (empty($file_info['extension']) || strtolower($file_info['extension']) !== 'json') {
            add_settings_error('perfumer_import', 'perfumer_import_error', __('Позволени са само JSON файлове.', 'parfume-reviews'), 'error');
            return;
        }
        
        $file_content = file_get_contents($_FILES['perfumer_import_file']['tmp_name']);
        $data = json_decode($file_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            add_settings_error('perfumer_import', 'perfumer_import_error', __('Невалиден JSON файл за парфюмеристи.', 'parfume-reviews'), 'error');
            return;
        }
        
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($data as $index => $perfumer_data) {
            try {
                if (empty($perfumer_data['name']) || !is_string($perfumer_data['name'])) {
                    $skipped++;
                    $errors[] = sprintf(__('Парфюмерист %d: Липсва или невалидно име', 'parfume-reviews'), $index + 1);
                    continue;
                }
                
                $perfumer_name = sanitize_text_field($perfumer_data['name']);
                
                // Проверяваме дали парфюмеристът съществува
                $existing_term = get_term_by('name', $perfumer_name, 'perfumer');
                
                $term_args = array(
                    'description' => isset($perfumer_data['description']) ? wp_kses_post($perfumer_data['description']) : '',
                    'slug' => isset($perfumer_data['slug']) ? sanitize_title($perfumer_data['slug']) : sanitize_title($perfumer_name)
                );
                
                if ($existing_term) {
                    // Обновяваме съществуващ парфюмерист
                    $result = wp_update_term($existing_term->term_id, 'perfumer', $term_args);
                    if (!is_wp_error($result)) {
                        $term_id = $existing_term->term_id;
                        $updated++;
                    } else {
                        $skipped++;
                        $errors[] = sprintf(__('Парфюмерист %d: %s', 'parfume-reviews'), $index + 1, $result->get_error_message());
                        continue;
                    }
                } else {
                    // Създаваме нов парфюмерист
                    $result = wp_insert_term($perfumer_name, 'perfumer', $term_args);
                    if (!is_wp_error($result)) {
                        $term_id = $result['term_id'];
                        $imported++;
                    } else {
                        $skipped++;
                        $errors[] = sprintf(__('Парфюмерист %d: %s', 'parfume-reviews'), $index + 1, $result->get_error_message());
                        continue;
                    }
                }
                
                // Импортираме meta полета за парфюмериста
                $this->import_perfumer_meta($term_id, $perfumer_data);
                
            } catch (Exception $e) {
                $skipped++;
                $errors[] = sprintf(__('Парфюмерист %d: %s', 'parfume-reviews'), $index + 1, $e->getMessage());
            }
        }
        
        $message = sprintf(__('Импортът на парфюмеристи завърши: %d импортирани, %d обновени, %d пропуснати.', 'parfume-reviews'), $imported, $updated, $skipped);
        
        if (!empty($errors)) {
            $message .= ' ' . __('Грешки:', 'parfume-reviews') . ' ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= sprintf(__(' и още %d грешки.', 'parfume-reviews'), count($errors) - 3);
            }
        }
        
        add_settings_error('perfumer_import', 'perfumer_import_success', $message, $imported > 0 || $updated > 0 ? 'updated' : 'error');
    }
    
    /**
     * НОВА ФУНКЦИЯ - Импортира meta полета за парфюмеристи
     */
    private function import_perfumer_meta($term_id, $perfumer_data) {
        // Импорт на изображение
        if (!empty($perfumer_data['image']) && filter_var($perfumer_data['image'], FILTER_VALIDATE_URL)) {
            $image_id = $this->import_image_from_url($perfumer_data['image']);
            if ($image_id) {
                update_term_meta($term_id, 'perfumer-image-id', $image_id);
            }
        }
        
        // Допълнителни мета полета за парфюмеристи
        $meta_fields = array(
            'birth_date' => 'perfumer_birth_date',
            'nationality' => 'perfumer_nationality', 
            'education' => 'perfumer_education',
            'career_start' => 'perfumer_career_start',
            'signature_style' => 'perfumer_signature_style',
            'famous_fragrances' => 'perfumer_famous_fragrances',
            'awards' => 'perfumer_awards',
            'website' => 'perfumer_website',
            'social_media' => 'perfumer_social_media'
        );
        
        foreach ($meta_fields as $json_key => $meta_key) {
            if (isset($perfumer_data[$json_key])) {
                $value = is_array($perfumer_data[$json_key]) ? 
                    implode("\n", array_map('sanitize_text_field', $perfumer_data[$json_key])) :
                    sanitize_text_field($perfumer_data[$json_key]);
                    
                update_term_meta($term_id, $meta_key, $value);
            }
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Експорт на парфюмеристи
     */
    public function handle_perfumer_export() {
        if (!isset($_POST['perfumer_export_nonce']) || !wp_verify_nonce($_POST['perfumer_export_nonce'], 'perfumer_export')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $perfumers = get_terms(array(
            'taxonomy' => 'perfumer',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (is_wp_error($perfumers)) {
            wp_die(__('Грешка при експорта на парфюмеристи.', 'parfume-reviews'));
        }
        
        $data = array();
        
        foreach ($perfumers as $perfumer) {
            $perfumer_data = array(
                'name' => $perfumer->name,
                'slug' => $perfumer->slug,
                'description' => $perfumer->description
            );
            
            // Експорт на meta полета
            $image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
                if ($image_url) {
                    $perfumer_data['image'] = $image_url;
                }
            }
            
            // Допълнителни мета полета
            $meta_fields = array(
                'perfumer_birth_date' => 'birth_date',
                'perfumer_nationality' => 'nationality',
                'perfumer_education' => 'education',
                'perfumer_career_start' => 'career_start',
                'perfumer_signature_style' => 'signature_style',
                'perfumer_famous_fragrances' => 'famous_fragrances',
                'perfumer_awards' => 'awards',
                'perfumer_website' => 'website',
                'perfumer_social_media' => 'social_media'
            );
            
            foreach ($meta_fields as $meta_key => $json_key) {
                $value = get_term_meta($perfumer->term_id, $meta_key, true);
                if (!empty($value)) {
                    // Ако съдържа нови редове, прави масив
                    if (strpos($value, "\n") !== false) {
                        $perfumer_data[$json_key] = explode("\n", $value);
                    } else {
                        $perfumer_data[$json_key] = $value;
                    }
                }
            }
            
            $data[] = $perfumer_data;
        }
        
        // Set headers for download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="perfumers-export-' . date('Y-m-d-H-i-s') . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
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
            if (!empty($terms)) {
                wp_set_object_terms($post_id, $terms, $taxonomy);
            }
        }
    }
    
    private function import_meta_fields($post_id, $item) {
        $meta_mapping = array(
            'rating' => '_parfume_rating',
            'gender_text' => '_parfume_gender_text',
            'release_year' => '_parfume_release_year',
            'longevity' => '_parfume_longevity',
            'sillage' => '_parfume_sillage',
            'bottle_size' => '_parfume_bottle_size',
            'pros' => '_parfume_pros',
            'cons' => '_parfume_cons',
        );
        
        foreach ($meta_mapping as $item_key => $meta_key) {
            if (isset($item[$item_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($item[$item_key]));
            }
        }
        
        // Import aroma chart
        if (!empty($item['aroma_chart']) && is_array($item['aroma_chart'])) {
            update_post_meta($post_id, '_parfume_aroma_chart', $item['aroma_chart']);
        }
    }
    
    private function import_stores($post_id, $stores) {
        $clean_stores = array();
        
        foreach ($stores as $store) {
            if (!empty($store['name']) && !empty($store['url'])) {
                $clean_stores[] = array_map('sanitize_text_field', $store);
            }
        }
        
        if (!empty($clean_stores)) {
            update_post_meta($post_id, '_parfume_stores', $clean_stores);
        }
    }
    
    private function import_featured_image($post_id, $image_url) {
        $image_id = $this->import_image_from_url($image_url);
        if ($image_id) {
            set_post_thumbnail($post_id, $image_id);
        }
    }
    
    private function import_image_from_url($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $temp_file = download_url($url);
        
        if (is_wp_error($temp_file)) {
            return false;
        }
        
        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $temp_file
        );
        
        $attachment_id = media_handle_sideload($file_array, 0);
        
        @unlink($temp_file);
        
        return is_wp_error($attachment_id) ? false : $attachment_id;
    }
    
    public function handle_export() {
        if (!isset($_POST['parfume_export_nonce']) || !wp_verify_nonce($_POST['parfume_export_nonce'], 'parfume_export')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $posts = get_posts(array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $data = array();
        
        foreach ($posts as $post) {
            $post_id = $post->ID;
            
            $item = array(
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
            );
            
            // Get featured image
            $featured_image_id = get_post_thumbnail_id($post_id);
            if ($featured_image_id) {
                $item['featured_image'] = wp_get_attachment_image_url($featured_image_id, 'full');
            }
            
            // Get meta fields
            $meta_fields = array(
                '_parfume_rating' => 'rating',
                '_parfume_gender_text' => 'gender_text',
                '_parfume_release_year' => 'release_year',
                '_parfume_longevity' => 'longevity',
                '_parfume_sillage' => 'sillage',
                '_parfume_bottle_size' => 'bottle_size',
                '_parfume_pros' => 'pros',
                '_parfume_cons' => 'cons',
            );
            
            foreach ($meta_fields as $meta_key => $item_key) {
                $value = get_post_meta($post_id, $meta_key, true);
                if (!empty($value)) {
                    $item[$item_key] = $value;
                }
            }
            
            // Get aroma chart
            $aroma_chart = get_post_meta($post_id, '_parfume_aroma_chart', true);
            if (!empty($aroma_chart)) {
                $item['aroma_chart'] = $aroma_chart;
            }
            
            // Get taxonomies
            $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
            
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
                if (!empty($terms) && !is_wp_error($terms)) {
                    $key = $taxonomy === 'marki' ? 'brand' : $taxonomy;
                    $item[$key] = $terms;
                }
            }
            
            // Get stores
            $stores = get_post_meta($post_id, '_parfume_stores', true);
            if (!empty($stores) && is_array($stores)) {
                $item['stores'] = $stores;
            }
            
            $data[] = $item;
        }
        
        wp_reset_postdata();
        
        // Set headers for download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="parfume-export-' . date('Y-m-d-H-i-s') . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * НОВА ФУНКЦИЯ - Инструкции за JSON формат на парфюмеристи
     */
    public static function get_perfumer_json_format_instructions() {
        ob_start();
        ?>
        <div class="json-format-instructions perfumer-instructions">
            <h3><?php _e('Инструкции за JSON формат на парфюмеристи', 'parfume-reviews'); ?></h3>
            <p><?php _e('JSON файлът трябва да бъде масив от обекти, където всеки обект представлява парфюмерист със следната структура:', 'parfume-reviews'); ?></p>
            
            <details>
                <summary><?php _e('Кликнете за да видите примерен JSON формат за парфюмеристи', 'parfume-reviews'); ?></summary>
                <pre><code>[
  {
    "name": "Франсоа Демаши",
    "slug": "francois-demachy",
    "description": "Главен парфюмер на Dior от 2006 година. Създател на множество култови аромати.",
    "image": "https://example.com/francois-demachy.jpg",
    "birth_date": "1952-03-15",
    "nationality": "Френски",
    "education": "Версай - Институт за международни парфюмни изследвания",
    "career_start": "1978",
    "signature_style": "Елегантни, изискани композиции с акцент върху натуралните суровини",
    "famous_fragrances": [
      "Dior Sauvage",
      "J'adore Dior",
      "Miss Dior Cherie"
    ],
    "awards": [
      "Prix François Coty 2010",
      "Lifetime Achievement Award - Fragrance Foundation 2018"
    ],
    "website": "https://dior.com",
    "social_media": "https://instagram.com/dior"
  },
  {
    "name": "Доминик Ропион",
    "slug": "dominique-ropion",
    "description": "Легендарен парфюмер, познат с новаторския си подход към съвременните мъжки аромати.",
    "image": "https://example.com/dominique-ropion.jpg",
    "birth_date": "1960-08-22",
    "nationality": "Френски",
    "education": "ISIPCA - International School of Perfumery, Cosmetics and Food Flavoring",
    "career_start": "1985",
    "signature_style": "Смели, новаторски композиции с неочаквани съчетания",
    "famous_fragrances": [
      "Frédéric Malle Portrait of a Lady",
      "Tom Ford Oud Wood",
      "Paco Rabanne Pure XS"
    ],
    "awards": [
      "Fragrance Foundation Award 2007",
      "International Fragrance Award 2015"
    ]
  }
]</code></pre>
            </details>
            
            <h4><?php _e('Описание на полетата за парфюмеристи', 'parfume-reviews'); ?></h4>
            <ul>
                <li><strong>name</strong>: <?php _e('Задължително. Име на парфюмериста.', 'parfume-reviews'); ?></li>
                <li><strong>slug</strong>: <?php _e('Опционално. URL-friendly версия на името.', 'parfume-reviews'); ?></li>
                <li><strong>description</strong>: <?php _e('Опционално. Кратко описание на парфюмериста.', 'parfume-reviews'); ?></li>
                <li><strong>image</strong>: <?php _e('Опционално. URL към снимка на парфюмериста.', 'parfume-reviews'); ?></li>
                <li><strong>birth_date</strong>: <?php _e('Опционално. Дата на раждане (YYYY-MM-DD).', 'parfume-reviews'); ?></li>
                <li><strong>nationality</strong>: <?php _e('Опционално. Националност.', 'parfume-reviews'); ?></li>
                <li><strong>education</strong>: <?php _e('Опционално. Образование.', 'parfume-reviews'); ?></li>
                <li><strong>career_start</strong>: <?php _e('Опционално. Година на започване на кариерата.', 'parfume-reviews'); ?></li>
                <li><strong>signature_style</strong>: <?php _e('Опционално. Характерен стил.', 'parfume-reviews'); ?></li>
                <li><strong>famous_fragrances</strong>: <?php _e('Опционално. Масив от известни парфюми.', 'parfume-reviews'); ?></li>
                <li><strong>awards</strong>: <?php _e('Опционално. Масив от награди.', 'parfume-reviews'); ?></li>
                <li><strong>website</strong>: <?php _e('Опционално. Уебсайт.', 'parfume-reviews'); ?></li>
                <li><strong>social_media</strong>: <?php _e('Опционално. Социални мрежи.', 'parfume-reviews'); ?></li>
            </ul>
            
            <h4><?php _e('Примерна AI заявка за генериране на парфюмеристи', 'parfume-reviews'); ?></h4>
            <div class="ai-prompt-example">
                <p><?php _e('Когато питате AI да генерира информация за парфюмерист, използвайте заявка като:', 'parfume-reviews'); ?></p>
                
                <blockquote>
                    <p><em>"Създай JSON информация за парфюмерист [Име] според примерната структура. Включи реална биографична информация, образование, най-известни парфюми, награди и стил. Описанието трябва да бъде информативно, но не повече от 2-3 изречения. Включи реални парфюми, които е създал. Използвай български език за описанието и националността."</em></p>
                </blockquote>
            </div>
            
            <div class="import-tips">
                <h4><?php _e('Съвети за импорт на парфюмеристи', 'parfume-reviews'); ?></h4>
                <ul>
                    <li><?php _e('Максимален размер на файла: 10MB', 'parfume-reviews'); ?></li>
                    <li><?php _e('Приемат се само JSON файлове', 'parfume-reviews'); ?></li>
                    <li><?php _e('Съществуващи парфюмеристи със същото име ще бъдат обновени', 'parfume-reviews'); ?></li>
                    <li><?php _e('Изображенията ще бъдат изтеглени и добавени в медийната библиотека', 'parfume-reviews'); ?></li>
                    <li><?php _e('Всички мета полета са опционални освен името', 'parfume-reviews'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .perfumer-instructions .json-format-instructions pre {
            background: #f0f8ff;
            border-left: 4px solid #667eea;
        }
        
        .perfumer-instructions .ai-prompt-example blockquote {
            background: #e6f3ff;
            border-left: 4px solid #0073aa;
        }
        
        .perfumer-instructions .import-tips {
            background: #e8f5e8;
            border: 1px solid #4caf50;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    public static function get_json_format_instructions() {
        ob_start();
        ?>
        <div class="json-format-instructions">
            <h3><?php _e('Инструкции за JSON формат', 'parfume-reviews'); ?></h3>
            <p><?php _e('JSON файлът трябва да бъде масив от обекти, където всеки обект представлява ревю за парфюм със следната структура:', 'parfume-reviews'); ?></p>
            
            <details>
                <summary><?php _e('Кликнете за да видите примерен JSON формат', 'parfume-reviews'); ?></summary>
                <pre><code>[
  {
    "title": "Име на парфюма",
    "content": "Детайлно ревю съдържание...",
    "excerpt": "Кратко описание...",
    "featured_image": "https://example.com/image.jpg",
    "rating": 4.5,
    "gender": ["Мъжки парфюми"],
    "gender_text": "За мъже",
    "aroma_type": ["Парфюмна вода"],
    "brand": ["Dior"],
    "season": ["Зима", "Есен"],
    "intensity": ["Силни"],
    "notes": ["Ванилия", "Пачули", "Кехлибар"],
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
    "pros": "Отлична издръжливост\nУникален аромат\nЕлегантна опаковка",
    "cons": "Висока цена\nНе е подходящ за лято",
    "stores": [
      {
        "name": "Примерен магазин",
        "logo": "https://example.com/store-logo.jpg",
        "url": "https://example.com/product",
        "affiliate_url": "https://example.com/affiliate-link",
        "affiliate_class": "affiliate-link",
        "affiliate_rel": "nofollow",
        "affiliate_target": "_blank",
        "affiliate_anchor": "Купи сега",
        "promo_code": "DISCOUNT10",
        "promo_text": "Промо код -10%:",
        "price": "120.00 лв.",
        "size": "100ml",
        "availability": "Наличен",
        "shipping_cost": "4,99 лв."
      }
    ]
  }
]</code></pre>
            </details>
            
            <h4><?php _e('Описание на полетата', 'parfume-reviews'); ?></h4>
            <ul>
                <li><strong>title</strong>: <?php _e('Задължително. Името на парфюма.', 'parfume-reviews'); ?></li>
                <li><strong>content</strong>: <?php _e('Опционално. Пълно ревю съдържание (HTML позволен).', 'parfume-reviews'); ?></li>
                <li><strong>excerpt</strong>: <?php _e('Опционално. Кратко описание.', 'parfume-reviews'); ?></li>
                <li><strong>featured_image</strong>: <?php _e('Опционално. URL към главното изображение.', 'parfume-reviews'); ?></li>
                <li><strong>rating</strong>: <?php _e('Опционално. Числова оценка (0-5).', 'parfume-reviews'); ?></li>
                <li><strong>gender</strong>: <?php _e('Опционално. Масив от категории пол.', 'parfume-reviews'); ?></li>
                <li><strong>brand</strong>: <?php _e('Опционално. Масив от имена на марки.', 'parfume-reviews'); ?></li>
                <li><strong>notes</strong>: <?php _e('Опционално. Масив от ароматни нотки.', 'parfume-reviews'); ?></li>
                <li><strong>aroma_chart</strong>: <?php _e('Опционално. Обект с стойности за графиката на аромата (0-10).', 'parfume-reviews'); ?></li>
                <li><strong>pros</strong>: <?php _e('Опционално. Предимства (всяко на нов ред).', 'parfume-reviews'); ?></li>
                <li><strong>cons</strong>: <?php _e('Опционално. Недостатъци (всеки на нов ред).', 'parfume-reviews'); ?></li>
                <li><strong>stores</strong>: <?php _e('Опционално. Масив от обекти магазини, където може да се купи парфюмът.', 'parfume-reviews'); ?></li>
            </ul>
            
            <h4><?php _e('Примерна AI заявка', 'parfume-reviews'); ?></h4>
            <div class="ai-prompt-example">
                <p><?php _e('Когато питате AI да генерира съдържание за ревю на парфюм, използвайте заявка като:', 'parfume-reviews'); ?></p>
                
                <blockquote>
                    <p><em>"Създай детайлно ревю за парфюм в JSON формат за [Име на парфюма] от [Марка]. Включи всички полета показани в примера по-горе. Ревюто трябва да бъде изчерпателно, описвайки профила на аромата, издръжливостта, силажа и общото впечатление. Включи поне 5 нотки, които точно представляват ароматната пирамида на парфюма. Предостави реалистични стойности за рейтинг (между 3.5 и 5), издръжливост (напр. '6-8 часа'), и силаж (напр. 'Умерен до силен'). Добави 2-3 магазина където може да се купи парфюмът с реалистични цени. Използвай български термини където е подходящо (напр. 'Мъжки парфюми' за мъжки парфюми). Включи графика на аромата със стойности от 0 до 10 за свежест, сладост, интензивност и топлота. Добави предимства и недостатъци."</em></p>
                </blockquote>
            </div>
            
            <div class="import-tips">
                <h4><?php _e('Съвети за импорт', 'parfume-reviews'); ?></h4>
                <ul>
                    <li><?php _e('Максимален размер на файла: 10MB', 'parfume-reviews'); ?></li>
                    <li><?php _e('Приемат се само JSON файлове', 'parfume-reviews'); ?></li>
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
        .ai-prompt-example blockquote {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .import-tips {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}