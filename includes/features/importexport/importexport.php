<?php
/**
 * Import/Export Feature
 * 
 * Handles parfume data import and export functionality
 * 
 * @package Parfume_Reviews
 * @subpackage Features\ImportExport
 * @since 2.0.0
 */

namespace Parfume_Reviews\Features\ImportExport;

use Parfume_Reviews\Core\Container;

/**
 * ImportExport Class
 * 
 * Manages parfume data import and export
 */
class ImportExport {
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Supported file formats
     * 
     * @var array
     */
    private $supported_formats = ['json', 'csv'];
    
    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    /**
     * Add admin menu
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Import/Export', 'parfume-reviews'),
            __('Import/Export', 'parfume-reviews'),
            'manage_options',
            'parfume-import-export',
            [$this, 'render_page']
        );
    }
    
    /**
     * Render import/export page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import/Export парфюми', 'parfume-reviews'); ?></h1>
            
            <div class="import-export-wrapper">
                <!-- Export Section -->
                <div class="card">
                    <h2><?php _e('Експорт на парфюми', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Експортирайте всички парфюми в JSON или CSV формат.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('parfume_export', 'parfume_export_nonce'); ?>
                        <input type="hidden" name="action" value="parfume_export" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="export_format"><?php _e('Формат', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <select name="export_format" id="export_format">
                                        <option value="json">JSON</option>
                                        <option value="csv">CSV</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="include_meta"><?php _e('Включи мета данни', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="include_meta" id="include_meta" value="1" checked />
                                        <?php _e('Включи всички custom fields и мета данни', 'parfume-reviews'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="include_taxonomies"><?php _e('Включи таксономии', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="include_taxonomies" id="include_taxonomies" value="1" checked />
                                        <?php _e('Включи марки, пол, нотки и др.', 'parfume-reviews'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Експорт', 'parfume-reviews'), 'primary', 'submit', false); ?>
                    </form>
                </div>
                
                <!-- Import Section -->
                <div class="card">
                    <h2><?php _e('Импорт на парфюми', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Импортирайте парфюми от JSON или CSV файл.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('parfume_import', 'parfume_import_nonce'); ?>
                        <input type="hidden" name="action" value="parfume_import" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php _e('Файл', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="import_file" id="import_file" accept=".json,.csv" required />
                                    <p class="description">
                                        <?php _e('Поддържани формати: JSON, CSV', 'parfume-reviews'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="update_existing"><?php _e('Обнови съществуващи', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="update_existing" id="update_existing" value="1" />
                                        <?php _e('Обнови съществуващи парфюми със същото име', 'parfume-reviews'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="import_images"><?php _e('Импорт на снимки', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="import_images" id="import_images" value="1" checked />
                                        <?php _e('Изтегли и импортирай снимки от URL-и', 'parfume-reviews'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Импорт', 'parfume-reviews'), 'primary', 'submit', false); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
            .import-export-wrapper {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-top: 20px;
            }
            
            @media (max-width: 782px) {
                .import-export-wrapper {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Handle export
     */
    public function handle_export() {
        // Check nonce
        if (!isset($_POST['parfume_export_nonce']) || !wp_verify_nonce($_POST['parfume_export_nonce'], 'parfume_export')) {
            wp_die(__('Невалидна заявка', 'parfume-reviews'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'json';
        $include_meta = isset($_POST['include_meta']);
        $include_taxonomies = isset($_POST['include_taxonomies']);
        
        // Get all parfumes
        $parfumes = $this->get_all_parfumes($include_meta, $include_taxonomies);
        
        // Export based on format
        if ($format === 'csv') {
            $this->export_csv($parfumes);
        } else {
            $this->export_json($parfumes);
        }
        
        exit;
    }
    
    /**
     * Handle import
     */
    public function handle_import() {
        // Check nonce
        if (!isset($_POST['parfume_import_nonce']) || !wp_verify_nonce($_POST['parfume_import_nonce'], 'parfume_import')) {
            wp_die(__('Невалидна заявка', 'parfume-reviews'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        // Check file upload
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('Грешка при качване на файл', 'parfume-reviews'));
        }
        
        $update_existing = isset($_POST['update_existing']);
        $import_images = isset($_POST['import_images']);
        
        $file = $_FILES['import_file'];
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Import based on format
        if ($extension === 'csv') {
            $result = $this->import_csv($file['tmp_name'], $update_existing, $import_images);
        } elseif ($extension === 'json') {
            $result = $this->import_json($file['tmp_name'], $update_existing, $import_images);
        } else {
            wp_die(__('Неподдържан формат', 'parfume-reviews'));
        }
        
        // Redirect with result
        $redirect_url = add_query_arg([
            'page' => 'parfume-import-export',
            'imported' => $result['imported'],
            'updated' => $result['updated'],
            'errors' => $result['errors']
        ], admin_url('edit.php?post_type=parfume'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Get all parfumes data
     * 
     * @param bool $include_meta Include meta data
     * @param bool $include_taxonomies Include taxonomies
     * @return array
     */
    private function get_all_parfumes($include_meta = true, $include_taxonomies = true) {
        $query = new \WP_Query([
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        $parfumes = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $parfume = [
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'status' => get_post_status(),
                    'date' => get_the_date('Y-m-d H:i:s'),
                ];
                
                // Add meta data
                if ($include_meta) {
                    $parfume['meta'] = $this->get_parfume_meta($post_id);
                }
                
                // Add taxonomies
                if ($include_taxonomies) {
                    $parfume['taxonomies'] = $this->get_parfume_taxonomies($post_id);
                }
                
                // Add featured image
                if (has_post_thumbnail()) {
                    $parfume['featured_image'] = get_the_post_thumbnail_url($post_id, 'full');
                }
                
                $parfumes[] = $parfume;
            }
            wp_reset_postdata();
        }
        
        return $parfumes;
    }
    
    /**
     * Get parfume meta data
     * 
     * @param int $post_id Post ID
     * @return array
     */
    private function get_parfume_meta($post_id) {
        $meta = [];
        $all_meta = get_post_meta($post_id);
        
        foreach ($all_meta as $key => $value) {
            if (strpos($key, '_parfume_') === 0) {
                $clean_key = str_replace('_parfume_', '', $key);
                $meta[$clean_key] = maybe_unserialize($value[0]);
            }
        }
        
        return $meta;
    }
    
    /**
     * Get parfume taxonomies
     * 
     * @param int $post_id Post ID
     * @return array
     */
    private function get_parfume_taxonomies($post_id) {
        $taxonomies = ['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'];
        $data = [];
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy);
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $data[$taxonomy] = array_map(function($term) {
                    return $term->name;
                }, $terms);
            }
        }
        
        return $data;
    }
    
    /**
     * Export to JSON
     * 
     * @param array $data Data to export
     */
    private function export_json($data) {
        $filename = 'parfumes-export-' . date('Y-m-d-His') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Export to CSV
     * 
     * @param array $data Data to export
     */
    private function export_csv($data) {
        $filename = 'parfumes-export-' . date('Y-m-d-His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            fputcsv($output, $headers);
            
            // Data
            foreach ($data as $row) {
                $csv_row = [];
                foreach ($row as $value) {
                    if (is_array($value)) {
                        $csv_row[] = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } else {
                        $csv_row[] = $value;
                    }
                }
                fputcsv($output, $csv_row);
            }
        }
        
        fclose($output);
    }
    
    /**
     * Import from JSON
     * 
     * @param string $file_path File path
     * @param bool $update_existing Update existing posts
     * @param bool $import_images Import images
     * @return array Result statistics
     */
    private function import_json($file_path, $update_existing = false, $import_images = false) {
        $json = file_get_contents($file_path);
        $data = json_decode($json, true);
        
        if (!$data) {
            return ['imported' => 0, 'updated' => 0, 'errors' => 1];
        }
        
        return $this->import_data($data, $update_existing, $import_images);
    }
    
    /**
     * Import from CSV
     * 
     * @param string $file_path File path
     * @param bool $update_existing Update existing posts
     * @param bool $import_images Import images
     * @return array Result statistics
     */
    private function import_csv($file_path, $update_existing = false, $import_images = false) {
        $data = [];
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                $item = [];
                foreach ($headers as $index => $header) {
                    $value = isset($row[$index]) ? $row[$index] : '';
                    
                    // Try to decode JSON
                    $decoded = json_decode($value, true);
                    $item[$header] = $decoded !== null ? $decoded : $value;
                }
                $data[] = $item;
            }
            
            fclose($handle);
        }
        
        return $this->import_data($data, $update_existing, $import_images);
    }
    
    /**
     * Import data
     * 
     * @param array $data Data to import
     * @param bool $update_existing Update existing posts
     * @param bool $import_images Import images
     * @return array Result statistics
     */
    private function import_data($data, $update_existing = false, $import_images = false) {
        $imported = 0;
        $updated = 0;
        $errors = 0;
        
        foreach ($data as $item) {
            try {
                $result = $this->import_single_parfume($item, $update_existing, $import_images);
                
                if ($result['action'] === 'imported') {
                    $imported++;
                } elseif ($result['action'] === 'updated') {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors++;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Parfume import error: ' . $e->getMessage());
                }
            }
        }
        
        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        ];
    }
    
    /**
     * Import single parfume
     * 
     * @param array $data Parfume data
     * @param bool $update_existing Update existing
     * @param bool $import_images Import images
     * @return array Result
     */
    private function import_single_parfume($data, $update_existing, $import_images) {
        // Check if parfume exists
        $existing = get_page_by_title($data['title'], OBJECT, 'parfume');
        
        if ($existing && !$update_existing) {
            return ['action' => 'skipped', 'post_id' => $existing->ID];
        }
        
        // Prepare post data
        $post_data = [
            'post_title' => $data['title'],
            'post_content' => isset($data['content']) ? $data['content'] : '',
            'post_excerpt' => isset($data['excerpt']) ? $data['excerpt'] : '',
            'post_status' => isset($data['status']) ? $data['status'] : 'publish',
            'post_type' => 'parfume',
        ];
        
        if ($existing) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data);
            $action = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            $action = 'imported';
        }
        
        if (is_wp_error($post_id)) {
            throw new \Exception($post_id->get_error_message());
        }
        
        // Import meta data
        if (isset($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                update_post_meta($post_id, '_parfume_' . $key, $value);
            }
        }
        
        // Import taxonomies
        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            foreach ($data['taxonomies'] as $taxonomy => $terms) {
                wp_set_post_terms($post_id, $terms, $taxonomy);
            }
        }
        
        // Import featured image
        if ($import_images && isset($data['featured_image'])) {
            $this->import_image($post_id, $data['featured_image']);
        }
        
        return ['action' => $action, 'post_id' => $post_id];
    }
    
    /**
     * Import image from URL
     * 
     * @param int $post_id Post ID
     * @param string $image_url Image URL
     * @return int|false Attachment ID or false
     */
    private function import_image($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_sideload_image($image_url, $post_id, null, 'id');
        
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }
        
        return false;
    }
}