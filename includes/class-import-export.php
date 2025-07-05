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
            wp_die(__('You do not have sufficient permissions to access this page.', 'parfume-reviews'));
        }
        
        if (empty($_FILES['parfume_import_file']['tmp_name']) || $_FILES['parfume_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error('parfume_import', 'parfume_import_error', __('Please select a valid file to import.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Check file type
        $file_info = pathinfo($_FILES['parfume_import_file']['name']);
        if (empty($file_info['extension']) || strtolower($file_info['extension']) !== 'json') {
            add_settings_error('parfume_import', 'parfume_import_error', __('Only JSON files are allowed.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Check file size (max 10MB)
        if ($_FILES['parfume_import_file']['size'] > 10 * 1024 * 1024) {
            add_settings_error('parfume_import', 'parfume_import_error', __('File is too large. Maximum size is 10MB.', 'parfume-reviews'), 'error');
            return;
        }
        
        $file_content = file_get_contents($_FILES['parfume_import_file']['tmp_name']);
        
        if ($file_content === false) {
            add_settings_error('parfume_import', 'parfume_import_error', __('Could not read the uploaded file.', 'parfume-reviews'), 'error');
            return;
        }
        
        $data = json_decode($file_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            add_settings_error('parfume_import', 'parfume_import_error', __('Invalid JSON file. Please check the file format.', 'parfume-reviews'), 'error');
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
                    $errors[] = sprintf(__('Item %d: Missing or invalid title', 'parfume-reviews'), $index + 1);
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
                    $errors[] = sprintf(__('Item %d: %s', 'parfume-reviews'), $index + 1, $post_id->get_error_message());
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
                $errors[] = sprintf(__('Item %d: %s', 'parfume-reviews'), $index + 1, $e->getMessage());
            }
        }
        
        $message = sprintf(__('Import completed: %d imported, %d updated, %d skipped.', 'parfume-reviews'), $imported, $updated, $skipped);
        
        if (!empty($errors)) {
            $message .= ' ' . __('Errors:', 'parfume-reviews') . ' ' . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= sprintf(__(' and %d more errors.', 'parfume-reviews'), count($errors) - 5);
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
                'price' => isset($store['price']) ? sanitize_text_field($store['price']) : '',
                'size' => isset($store['size']) ? sanitize_text_field($store['size']) : '',
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
            wp_die(__('You do not have sufficient permissions to access this page.', 'parfume-reviews'));
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
            <h3><?php _e('JSON Format Instructions', 'parfume-reviews'); ?></h3>
            <p><?php _e('The JSON file should be an array of objects, where each object represents a perfume review with the following structure:', 'parfume-reviews'); ?></p>
            
            <details>
                <summary><?php _e('Click to view example JSON format', 'parfume-reviews'); ?></summary>
                <pre><code>[
  {
    "title": "Parfume Name",
    "content": "Detailed review content...",
    "excerpt": "Short description...",
    "featured_image": "https://example.com/image.jpg",
    "rating": 4.5,
    "gender": ["Мъжки парфюми"],
    "gender_text": "For Men",
    "aroma_type": ["Парфюмна вода"],
    "brand": ["Dior"],
    "season": ["Зима", "Есен"],
    "intensity": ["Силни"],
    "notes": ["Ванилия", "Пачули", "Кехлибар"],
    "perfumer": ["Франсоа Демаши"],
    "release_year": "2018",
    "longevity": "8-10 hours",
    "sillage": "Moderate",
    "bottle_size": "100ml",
    "stores": [
      {
        "name": "Example Store",
        "logo": "https://example.com/store-logo.jpg",
        "url": "https://example.com/product",
        "affiliate_url": "https://example.com/affiliate-link",
        "affiliate_class": "affiliate-link",
        "affiliate_rel": "nofollow",
        "affiliate_target": "_blank",
        "affiliate_anchor": "Buy Now",
        "promo_code": "DISCOUNT10",
        "price": "$120.00",
        "size": "100ml"
      }
    ]
  }
]</code></pre>
            </details>
            
            <h4><?php _e('Field Descriptions', 'parfume-reviews'); ?></h4>
            <ul>
                <li><strong>title</strong>: <?php _e('Required. The name of the perfume.', 'parfume-reviews'); ?></li>
                <li><strong>content</strong>: <?php _e('Optional. Full review content (HTML allowed).', 'parfume-reviews'); ?></li>
                <li><strong>excerpt</strong>: <?php _e('Optional. Short description.', 'parfume-reviews'); ?></li>
                <li><strong>featured_image</strong>: <?php _e('Optional. URL to the main image.', 'parfume-reviews'); ?></li>
                <li><strong>rating</strong>: <?php _e('Optional. Numeric rating (0-5).', 'parfume-reviews'); ?></li>
                <li><strong>gender</strong>: <?php _e('Optional. Array of gender categories.', 'parfume-reviews'); ?></li>
                <li><strong>brand</strong>: <?php _e('Optional. Array of brand names.', 'parfume-reviews'); ?></li>
                <li><strong>notes</strong>: <?php _e('Optional. Array of fragrance notes.', 'parfume-reviews'); ?></li>
                <li><strong>stores</strong>: <?php _e('Optional. Array of store objects where the perfume can be purchased.', 'parfume-reviews'); ?></li>
            </ul>
            
            <h4><?php _e('AI Prompt Example', 'parfume-reviews'); ?></h4>
            <div class="ai-prompt-example">
                <p><?php _e('When asking an AI to generate content for a perfume review, use a prompt like:', 'parfume-reviews'); ?></p>
                
                <blockquote>
                    <p><em>"Create a detailed perfume review in JSON format for [Perfume Name] by [Brand]. Include all the fields shown in the example above. The review should be comprehensive, describing the scent profile, longevity, sillage, and overall impression. Include at least 5 notes that accurately represent the perfume's fragrance pyramid. Provide realistic values for rating (between 3.5 and 5), longevity (e.g., '6-8 hours'), and sillage (e.g., 'Moderate to Strong'). Add 2-3 stores where the perfume can be purchased with realistic prices. Use Bulgarian terms where appropriate (e.g., 'Мъжки парфюми' for men's perfumes)."</em></p>
                </blockquote>
            </div>
            
            <div class="import-tips">
                <h4><?php _e('Import Tips', 'parfume-reviews'); ?></h4>
                <ul>
                    <li><?php _e('Maximum file size: 10MB', 'parfume-reviews'); ?></li>
                    <li><?php _e('Only JSON files are accepted', 'parfume-reviews'); ?></li>
                    <li><?php _e('Validate your JSON using a tool like JSONLint before importing', 'parfume-reviews'); ?></li>
                    <li><?php _e('Existing perfumes with the same title will be updated', 'parfume-reviews'); ?></li>
                    <li><?php _e('New taxonomy terms will be created automatically', 'parfume-reviews'); ?></li>
                    <li><?php _e('Images will be downloaded and added to your media library', 'parfume-reviews'); ?></li>
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