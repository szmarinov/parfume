<?php
namespace Parfume_Reviews;

class Import_Export {
    public function __construct() {
        add_action('admin_init', array($this, 'handle_import'));
        add_action('admin_init', array($this, 'handle_export'));
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
    
    private function import_meta_fields($post_id, $item) {
        $meta_fields = array(
            '_parfume_rating' => isset($item['rating']) && is_numeric($item['rating']) ? floatval($item['rating']) : 0,
            '_parfume_gender' => isset($item['gender_text']) ? sanitize_text_field($item['gender_text']) : '',
            '_parfume_release_year' => isset($item['release_year']) ? sanitize_text_field($item['release_year']) : '',
            '_parfume_longevity' => isset($item['longevity']) ? sanitize_text_field($item['longevity']) : '',
            '_parfume_sillage' => isset($item['sillage']) ? sanitize_text_field($item['sillage']) : '',
            '_parfume_bottle_size' => isset($item['bottle_size']) ? sanitize_text_field($item['bottle_size']) : '',
            '_parfume_freshness' => isset($item['aroma_chart']['freshness']) ? intval($item['aroma_chart']['freshness']) : 0,
            '_parfume_sweetness' => isset($item['aroma_chart']['sweetness']) ? intval($item['aroma_chart']['sweetness']) : 0,
            '_parfume_intensity' => isset($item['aroma_chart']['intensity']) ? intval($item['aroma_chart']['intensity']) : 0,
            '_parfume_warmth' => isset($item['aroma_chart']['warmth']) ? intval($item['aroma_chart']['warmth']) : 0,
            '_parfume_pros' => isset($item['pros']) ? sanitize_textarea_field($item['pros']) : '',
            '_parfume_cons' => isset($item['cons']) ? sanitize_textarea_field($item['cons']) : '',
        );
        
        foreach ($meta_fields as $key => $value) {
            if (!empty($value) || is_numeric($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }
    
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
                'availability' => isset($store['availability']) ? sanitize_text_field($store['availability']) : '',
                'shipping_cost' => isset($store['shipping_cost']) ? sanitize_text_field($store['shipping_cost']) : '',
                'last_updated' => current_time('mysql'),
            );
        }
        
        if (!empty($stores)) {
            update_post_meta($post_id, '_parfume_stores', $stores);
        }
    }
    
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
        if (!$wp_filetype['type']) {
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
    
    public function handle_export() {
        if (!isset($_GET['parfume_export']) || !wp_verify_nonce($_GET['_wpnonce'], 'parfume_export')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за достъп до тази страница.', 'parfume-reviews'));
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
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
                    'rating' => floatval(get_post_meta($post_id, '_parfume_rating', true)),
                    'gender_text' => get_post_meta($post_id, '_parfume_gender', true),
                    'release_year' => get_post_meta($post_id, '_parfume_release_year', true),
                    'longevity' => get_post_meta($post_id, '_parfume_longevity', true),
                    'sillage' => get_post_meta($post_id, '_parfume_sillage', true),
                    'bottle_size' => get_post_meta($post_id, '_parfume_bottle_size', true),
                    'aroma_chart' => array(
                        'freshness' => intval(get_post_meta($post_id, '_parfume_freshness', true)),
                        'sweetness' => intval(get_post_meta($post_id, '_parfume_sweetness', true)),
                        'intensity' => intval(get_post_meta($post_id, '_parfume_intensity', true)),
                        'warmth' => intval(get_post_meta($post_id, '_parfume_warmth', true)),
                    ),
                    'pros' => get_post_meta($post_id, '_parfume_pros', true),
                    'cons' => get_post_meta($post_id, '_parfume_cons', true),
                );
                
                // Get featured image URL
                if (has_post_thumbnail($post_id)) {
                    $item['featured_image'] = get_the_post_thumbnail_url($post_id, 'full');
                }
                
                // Get taxonomies
                $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
                
                foreach ($taxonomies as $taxonomy) {
                    if (taxonomy_exists($taxonomy)) {
                        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
                        if (!empty($terms) && !is_wp_error($terms)) {
                            $key = ($taxonomy === 'marki') ? 'brand' : $taxonomy;
                            $item[$key] = $terms;
                        }
                    }
                }
                
                // Get stores
                $stores = get_post_meta($post_id, '_parfume_stores', true);
                if (!empty($stores) && is_array($stores)) {
                    $item['stores'] = $stores;
                }
                
                $data[] = $item;
            }
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