<?php
/**
 * Import/Export Admin Page for Parfume Reviews Plugin
 * 
 * @package Parfume_Reviews
 * @subpackage Admin
 * @since 1.0.0
 */

namespace Parfume_Reviews\Admin;

use Parfume_Reviews\Utils\Admin_Page_Base;
use Parfume_Reviews\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import Export Admin Page Class
 */
class Import_Export extends Admin_Page_Base {
    
    /**
     * Page slug
     */
    protected $page_slug = 'parfume-import-export';
    
    /**
     * Page capability requirement
     */
    protected $capability = 'manage_options';
    
    /**
     * Initialize the import/export functionality
     */
    public function init() {
        parent::init();
        
        // Add AJAX handlers
        add_action('wp_ajax_parfume_import_progress', array($this, 'handle_import_progress'));
        add_action('wp_ajax_parfume_export_data', array($this, 'handle_export_ajax'));
        
        // Handle file uploads
        add_action('admin_init', array($this, 'handle_file_import'));
        add_action('admin_init', array($this, 'handle_export_download'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'display_import_notices'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Import/Export', 'parfume-reviews'),
            __('Import/Export', 'parfume-reviews'),
            $this->capability,
            $this->page_slug,
            array($this, 'render_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!$this->is_plugin_page($hook)) {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_style(
            'parfume-import-export',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/import-export.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        wp_enqueue_script(
            'parfume-import-export',
            PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/import-export.js',
            array('jquery', 'wp-util'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-import-export', 'parfumeImportExport', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_import_export_nonce'),
            'strings' => array(
                'importing' => __('Importing...', 'parfume-reviews'),
                'exporting' => __('Exporting...', 'parfume-reviews'),
                'completed' => __('Completed!', 'parfume-reviews'),
                'error' => __('Error occurred', 'parfume-reviews'),
                'confirm_import' => __('Are you sure you want to import this data? This action cannot be undone.', 'parfume-reviews'),
                'file_too_large' => __('File is too large. Maximum size is 10MB.', 'parfume-reviews'),
                'invalid_file' => __('Please select a valid JSON file.', 'parfume-reviews'),
            )
        ));
    }
    
    /**
     * Render the admin page
     */
    public function render_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'import';
        ?>
        <div class="wrap parfume-import-export-page">
            <h1><?php _e('Parfume Reviews - Import/Export', 'parfume-reviews'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?post_type=parfume&page=<?php echo $this->page_slug; ?>&tab=import" 
                   class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Import', 'parfume-reviews'); ?>
                </a>
                <a href="?post_type=parfume&page=<?php echo $this->page_slug; ?>&tab=export" 
                   class="nav-tab <?php echo $active_tab === 'export' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Export', 'parfume-reviews'); ?>
                </a>
                <a href="?post_type=parfume&page=<?php echo $this->page_slug; ?>&tab=backup" 
                   class="nav-tab <?php echo $active_tab === 'backup' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Backup/Restore', 'parfume-reviews'); ?>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'import':
                        $this->render_import_tab();
                        break;
                    case 'export':
                        $this->render_export_tab();
                        break;
                    case 'backup':
                        $this->render_backup_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render import tab
     */
    private function render_import_tab() {
        ?>
        <div class="import-section">
            <div class="postbox">
                <h2 class="hndle"><?php _e('Import Parfumes', 'parfume-reviews'); ?></h2>
                <div class="inside">
                    <form method="post" enctype="multipart/form-data" id="parfume-import-form">
                        <?php wp_nonce_field('parfume_import_nonce', 'parfume_import_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php _e('Select JSON File', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="import_file" id="import_file" accept=".json" required>
                                    <p class="description">
                                        <?php _e('Upload a JSON file with parfume data. Maximum file size: 10MB', 'parfume-reviews'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Import Options', 'parfume-reviews'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="update_existing" value="1" checked>
                                            <?php _e('Update existing parfumes if they already exist', 'parfume-reviews'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="import_images" value="1" checked>
                                            <?php _e('Download and import images', 'parfume-reviews'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="create_taxonomies" value="1" checked>
                                            <?php _e('Create new taxonomy terms if they don\'t exist', 'parfume-reviews'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="import_parfumes" class="button-primary" 
                                   value="<?php _e('Import Parfumes', 'parfume-reviews'); ?>">
                        </p>
                    </form>
                    
                    <div id="import-progress" style="display: none;">
                        <h3><?php _e('Import Progress', 'parfume-reviews'); ?></h3>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-status"></div>
                    </div>
                </div>
            </div>
            
            <?php $this->render_json_format_help(); ?>
        </div>
        <?php
    }
    
    /**
     * Render export tab
     */
    private function render_export_tab() {
        ?>
        <div class="export-section">
            <div class="postbox">
                <h2 class="hndle"><?php _e('Export Parfumes', 'parfume-reviews'); ?></h2>
                <div class="inside">
                    <form method="post" id="parfume-export-form">
                        <?php wp_nonce_field('parfume_export_nonce', 'parfume_export_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Export Options', 'parfume-reviews'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="include_images" value="1" checked>
                                            <?php _e('Include image URLs', 'parfume-reviews'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="include_stores" value="1" checked>
                                            <?php _e('Include store information', 'parfume-reviews'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="include_meta" value="1" checked>
                                            <?php _e('Include all meta data', 'parfume-reviews'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="pretty_json" value="1" checked>
                                            <?php _e('Format JSON for readability', 'parfume-reviews'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="export_limit"><?php _e('Number of Parfumes', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="export_limit" id="export_limit" value="-1" min="-1" step="1">
                                    <p class="description">
                                        <?php _e('Number of parfumes to export. Use -1 for all parfumes.', 'parfume-reviews'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="export_parfumes" class="button-primary" 
                                   value="<?php _e('Export Parfumes', 'parfume-reviews'); ?>">
                        </p>
                    </form>
                    
                    <div id="export-progress" style="display: none;">
                        <h3><?php _e('Export Progress', 'parfume-reviews'); ?></h3>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-status"></div>
                    </div>
                </div>
            </div>
            
            <?php $this->render_export_statistics(); ?>
        </div>
        <?php
    }
    
    /**
     * Render backup tab
     */
    private function render_backup_tab() {
        ?>
        <div class="backup-section">
            <div class="postbox">
                <h2 class="hndle"><?php _e('Backup & Restore', 'parfume-reviews'); ?></h2>
                <div class="inside">
                    <div class="backup-actions">
                        <div class="backup-create">
                            <h3><?php _e('Create Backup', 'parfume-reviews'); ?></h3>
                            <p><?php _e('Create a complete backup of all parfume data, including taxonomies and settings.', 'parfume-reviews'); ?></p>
                            <button type="button" class="button-primary" id="create-backup">
                                <?php _e('Create Full Backup', 'parfume-reviews'); ?>
                            </button>
                        </div>
                        
                        <div class="backup-restore">
                            <h3><?php _e('Restore from Backup', 'parfume-reviews'); ?></h3>
                            <p><?php _e('Restore data from a previously created backup file.', 'parfume-reviews'); ?></p>
                            <form method="post" enctype="multipart/form-data">
                                <?php wp_nonce_field('parfume_restore_nonce', 'parfume_restore_nonce'); ?>
                                <input type="file" name="backup_file" accept=".json" required>
                                <input type="submit" name="restore_backup" class="button-secondary" 
                                       value="<?php _e('Restore Backup', 'parfume-reviews'); ?>">
                            </form>
                        </div>
                    </div>
                    
                    <?php $this->render_backup_history(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle file import
     */
    public function handle_file_import() {
        if (!isset($_POST['import_parfumes']) || !isset($_POST['parfume_import_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['parfume_import_nonce'], 'parfume_import_nonce')) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        if (!current_user_can($this->capability)) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $this->process_import();
    }
    
    /**
     * Process import
     */
    private function process_import() {
        if (empty($_FILES['import_file']['tmp_name'])) {
            $this->add_notice(__('No file selected for import.', 'parfume-reviews'), 'error');
            return;
        }
        
        $file = $_FILES['import_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->add_notice(__('File upload error.', 'parfume-reviews'), 'error');
            return;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            $this->add_notice(__('File is too large. Maximum size is 10MB.', 'parfume-reviews'), 'error');
            return;
        }
        
        $file_info = pathinfo($file['name']);
        if (strtolower($file_info['extension']) !== 'json') {
            $this->add_notice(__('Invalid file type. Only JSON files are allowed.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Read and parse JSON
        $json_content = file_get_contents($file['tmp_name']);
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->add_notice(__('Invalid JSON file. Please check the file format.', 'parfume-reviews'), 'error');
            return;
        }
        
        if (!is_array($data)) {
            $this->add_notice(__('Invalid data format. Expected an array of parfumes.', 'parfume-reviews'), 'error');
            return;
        }
        
        // Import options
        $update_existing = !empty($_POST['update_existing']);
        $import_images = !empty($_POST['import_images']);
        $create_taxonomies = !empty($_POST['create_taxonomies']);
        
        // Process import
        $results = $this->import_parfumes($data, array(
            'update_existing' => $update_existing,
            'import_images' => $import_images,
            'create_taxonomies' => $create_taxonomies,
        ));
        
        // Display results
        $message = sprintf(
            __('Import completed: %d imported, %d updated, %d skipped, %d errors.', 'parfume-reviews'),
            $results['imported'],
            $results['updated'],
            $results['skipped'],
            $results['errors']
        );
        
        $notice_type = $results['errors'] > 0 ? 'warning' : 'success';
        $this->add_notice($message, $notice_type);
        
        if (!empty($results['error_messages'])) {
            $error_list = '<ul><li>' . implode('</li><li>', array_slice($results['error_messages'], 0, 10)) . '</li></ul>';
            $this->add_notice(__('Errors encountered:', 'parfume-reviews') . $error_list, 'error');
        }
    }
    
    /**
     * Import parfumes from data array
     */
    private function import_parfumes($data, $options = array()) {
        $results = array(
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_messages' => array()
        );
        
        foreach ($data as $index => $parfume_data) {
            try {
                if (empty($parfume_data['title'])) {
                    $results['skipped']++;
                    $results['error_messages'][] = sprintf(__('Item %d: Missing title', 'parfume-reviews'), $index + 1);
                    continue;
                }
                
                // Check if parfume exists
                $existing = get_page_by_title($parfume_data['title'], OBJECT, 'parfume');
                
                $post_data = array(
                    'post_title' => sanitize_text_field($parfume_data['title']),
                    'post_content' => isset($parfume_data['content']) ? wp_kses_post($parfume_data['content']) : '',
                    'post_excerpt' => isset($parfume_data['excerpt']) ? sanitize_text_field($parfume_data['excerpt']) : '',
                    'post_type' => 'parfume',
                    'post_status' => 'publish',
                );
                
                if ($existing && $options['update_existing']) {
                    $post_data['ID'] = $existing->ID;
                    $post_id = wp_update_post($post_data);
                    if (!is_wp_error($post_id)) {
                        $results['updated']++;
                    }
                } elseif (!$existing) {
                    $post_id = wp_insert_post($post_data);
                    if (!is_wp_error($post_id)) {
                        $results['imported']++;
                    }
                } else {
                    $results['skipped']++;
                    continue;
                }
                
                if (is_wp_error($post_id)) {
                    $results['errors']++;
                    $results['error_messages'][] = sprintf(__('Item %d: %s', 'parfume-reviews'), $index + 1, $post_id->get_error_message());
                    continue;
                }
                
                // Import meta data
                $this->import_meta_data($post_id, $parfume_data);
                
                // Import taxonomies
                if ($options['create_taxonomies']) {
                    $this->import_taxonomies($post_id, $parfume_data);
                }
                
                // Import featured image
                if ($options['import_images'] && !empty($parfume_data['featured_image'])) {
                    $this->import_featured_image($post_id, $parfume_data['featured_image']);
                }
                
            } catch (Exception $e) {
                $results['errors']++;
                $results['error_messages'][] = sprintf(__('Item %d: %s', 'parfume-reviews'), $index + 1, $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Import meta data for a parfume
     */
    private function import_meta_data($post_id, $data) {
        $meta_fields = array(
            '_parfume_rating' => 'rating',
            '_parfume_gender' => 'gender_text',
            '_parfume_release_year' => 'release_year',
            '_parfume_longevity' => 'longevity',
            '_parfume_sillage' => 'sillage',
            '_parfume_bottle_size' => 'bottle_size',
            '_parfume_freshness' => array('aroma_chart', 'freshness'),
            '_parfume_sweetness' => array('aroma_chart', 'sweetness'),
            '_parfume_intensity' => array('aroma_chart', 'intensity'),
            '_parfume_warmth' => array('aroma_chart', 'warmth'),
            '_parfume_pros' => 'pros',
            '_parfume_cons' => 'cons',
        );
        
        foreach ($meta_fields as $meta_key => $data_key) {
            $value = null;
            
            if (is_array($data_key)) {
                $value = isset($data[$data_key[0]][$data_key[1]]) ? $data[$data_key[0]][$data_key[1]] : null;
            } else {
                $value = isset($data[$data_key]) ? $data[$data_key] : null;
            }
            
            if ($value !== null) {
                if (in_array($meta_key, array('_parfume_rating', '_parfume_freshness', '_parfume_sweetness', '_parfume_intensity', '_parfume_warmth'))) {
                    $value = floatval($value);
                } elseif (in_array($meta_key, array('_parfume_pros', '_parfume_cons'))) {
                    $value = sanitize_textarea_field($value);
                } else {
                    $value = sanitize_text_field($value);
                }
                
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Import stores data
        if (!empty($data['stores']) && is_array($data['stores'])) {
            update_post_meta($post_id, '_parfume_stores', $data['stores']);
        }
    }
    
    /**
     * Import taxonomies for a parfume
     */
    private function import_taxonomies($post_id, $data) {
        $taxonomy_mappings = array(
            'gender' => 'gender',
            'aroma_type' => 'aroma_type',
            'brand' => 'marki',
            'season' => 'season',
            'intensity' => 'intensity',
            'notes' => 'notes',
            'perfumer' => 'perfumer',
        );
        
        foreach ($taxonomy_mappings as $data_key => $taxonomy) {
            if (!empty($data[$data_key]) && taxonomy_exists($taxonomy)) {
                $terms = is_array($data[$data_key]) ? $data[$data_key] : array($data[$data_key]);
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
     * Import featured image
     */
    private function import_featured_image($post_id, $image_url) {
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            'name' => basename(parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $tmp,
        );
        
        $id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        return set_post_thumbnail($post_id, $id);
    }
    
    /**
     * Handle export download
     */
    public function handle_export_download() {
        if (!isset($_POST['export_parfumes']) || !isset($_POST['parfume_export_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['parfume_export_nonce'], 'parfume_export_nonce')) {
            wp_die(__('Security check failed', 'parfume-reviews'));
        }
        
        if (!current_user_can($this->capability)) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        $this->process_export();
    }
    
    /**
     * Process export
     */
    private function process_export() {
        $include_images = !empty($_POST['include_images']);
        $include_stores = !empty($_POST['include_stores']);
        $include_meta = !empty($_POST['include_meta']);
        $pretty_json = !empty($_POST['pretty_json']);
        $limit = intval($_POST['export_limit']);
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
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
                );
                
                if ($include_images && has_post_thumbnail()) {
                    $item['featured_image'] = get_the_post_thumbnail_url($post_id, 'full');
                }
                
                if ($include_meta) {
                    $item = array_merge($item, $this->get_export_meta_data($post_id));
                }
                
                if ($include_stores) {
                    $stores = get_post_meta($post_id, '_parfume_stores', true);
                    if (!empty($stores)) {
                        $item['stores'] = $stores;
                    }
                }
                
                // Add taxonomies
                $item = array_merge($item, $this->get_export_taxonomies($post_id));
                
                $data[] = $item;
            }
        }
        
        wp_reset_postdata();
        
        // Generate filename
        $filename = 'parfume-export-' . date('Y-m-d-H-i-s') . '.json';
        
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output JSON
        $json_flags = JSON_UNESCAPED_UNICODE;
        if ($pretty_json) {
            $json_flags |= JSON_PRETTY_PRINT;
        }
        
        echo json_encode($data, $json_flags);
        exit;
    }
    
    /**
     * Get export meta data
     */
    private function get_export_meta_data($post_id) {
        return array(
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
    }
    
    /**
     * Get export taxonomies
     */
    private function get_export_taxonomies($post_id) {
        $taxonomies = array(
            'gender' => 'gender',
            'aroma_type' => 'aroma_type',
            'brand' => 'marki',
            'season' => 'season',
            'intensity' => 'intensity',
            'notes' => 'notes',
            'perfumer' => 'perfumer',
        );
        
        $result = array();
        
        foreach ($taxonomies as $key => $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
                if (!empty($terms) && !is_wp_error($terms)) {
                    $result[$key] = $terms;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * AJAX handler for import progress
     */
    public function handle_import_progress() {
        check_ajax_referer('parfume_import_export_nonce', 'nonce');
        
        if (!current_user_can($this->capability)) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        // This would be used for chunked import processing
        wp_send_json_success(array(
            'progress' => 100,
            'status' => __('Import completed', 'parfume-reviews')
        ));
    }
    
    /**
     * AJAX handler for export
     */
    public function handle_export_ajax() {
        check_ajax_referer('parfume_import_export_nonce', 'nonce');
        
        if (!current_user_can($this->capability)) {
            wp_die(__('Insufficient permissions', 'parfume-reviews'));
        }
        
        // This would be used for AJAX export processing
        wp_send_json_success(array(
            'download_url' => admin_url('admin.php?action=download_export&nonce=' . wp_create_nonce('download_export'))
        ));
    }
    
    /**
     * Render JSON format help
     */
    private function render_json_format_help() {
        ?>
        <div class="postbox">
            <h2 class="hndle"><?php _e('JSON Format Instructions', 'parfume-reviews'); ?></h2>
            <div class="inside">
                <p><?php _e('The JSON file should contain an array of parfume objects with the following structure:', 'parfume-reviews'); ?></p>
                
                <details>
                    <summary><?php _e('Click to view example JSON format', 'parfume-reviews'); ?></summary>
                    <pre><code>[
  {
    "title": "Perfume Name",
    "content": "Detailed review content...",
    "excerpt": "Brief description...",
    "featured_image": "https://example.com/image.jpg",
    "rating": 4.5,
    "gender": ["Men's Fragrances"],
    "gender_text": "For men",
    "aroma_type": ["Eau de Parfum"],
    "brand": ["Dior"],
    "season": ["Winter", "Fall"],
    "intensity": ["Strong"],
    "notes": ["Vanilla", "Patchouli", "Amber"],
    "perfumer": ["François Demachy"],
    "release_year": "2018",
    "longevity": "8-10 hours",
    "sillage": "Moderate",
    "bottle_size": "100ml",
    "aroma_chart": {
      "freshness": 6,
      "sweetness": 7,
      "intensity": 8,
      "warmth": 9
    },
    "pros": "Excellent longevity\nUnique scent\nElegant packaging",
    "cons": "High price\nNot suitable for summer",
    "stores": [
      {
        "name": "Example Store",
        "logo": "https://example.com/store-logo.jpg",
        "url": "https://example.com/product",
        "affiliate_url": "https://example.com/affiliate-link",
        "price": "120.00 BGN",
        "size": "100ml",
        "availability": "Available"
      }
    ]
  }
]</code></pre>
                </details>
                
                <h4><?php _e('Field Descriptions', 'parfume-reviews'); ?></h4>
                <ul>
                    <li><strong>title</strong>: <?php _e('Required. The name of the perfume.', 'parfume-reviews'); ?></li>
                    <li><strong>content</strong>: <?php _e('Optional. Full review content (HTML allowed).', 'parfume-reviews'); ?></li>
                    <li><strong>featured_image</strong>: <?php _e('Optional. URL to the main image.', 'parfume-reviews'); ?></li>
                    <li><strong>rating</strong>: <?php _e('Optional. Numeric rating (0-5).', 'parfume-reviews'); ?></li>
                    <li><strong>gender</strong>: <?php _e('Optional. Array of gender categories.', 'parfume-reviews'); ?></li>
                    <li><strong>brand</strong>: <?php _e('Optional. Array of brand names.', 'parfume-reviews'); ?></li>
                    <li><strong>notes</strong>: <?php _e('Optional. Array of fragrance notes.', 'parfume-reviews'); ?></li>
                    <li><strong>aroma_chart</strong>: <?php _e('Optional. Object with aroma chart values (0-10).', 'parfume-reviews'); ?></li>
                    <li><strong>stores</strong>: <?php _e('Optional. Array of store objects where the perfume can be purchased.', 'parfume-reviews'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render export statistics
     */
    private function render_export_statistics() {
        $total_parfumes = wp_count_posts('parfume')->publish;
        $total_brands = wp_count_terms(array('taxonomy' => 'marki', 'hide_empty' => false));
        $total_notes = wp_count_terms(array('taxonomy' => 'notes', 'hide_empty' => false));
        
        ?>
        <div class="postbox">
            <h2 class="hndle"><?php _e('Export Statistics', 'parfume-reviews'); ?></h2>
            <div class="inside">
                <ul>
                    <li><?php printf(__('Total Parfumes: %d', 'parfume-reviews'), $total_parfumes); ?></li>
                    <li><?php printf(__('Total Brands: %d', 'parfume-reviews'), $total_brands); ?></li>
                    <li><?php printf(__('Total Notes: %d', 'parfume-reviews'), $total_notes); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render backup history
     */
    private function render_backup_history() {
        // This would show a list of previous backups
        ?>
        <div class="backup-history">
            <h3><?php _e('Backup History', 'parfume-reviews'); ?></h3>
            <p><?php _e('No backups found.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Display import notices
     */
    public function display_import_notices() {
        if (!$this->is_plugin_page()) {
            return;
        }
        
        $notices = get_transient('parfume_import_notices');
        if (!empty($notices)) {
            foreach ($notices as $notice) {
                printf(
                    '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                    esc_attr($notice['type']),
                    wp_kses_post($notice['message'])
                );
            }
            delete_transient('parfume_import_notices');
        }
    }
    
    /**
     * Add a notice to be displayed
     */
    private function add_notice($message, $type = 'info') {
        $notices = get_transient('parfume_import_notices') ?: array();
        $notices[] = array(
            'message' => $message,
            'type' => $type
        );
        set_transient('parfume_import_notices', $notices, 60);
    }
    
    /**
     * Check if current page is this plugin page
     */
    private function is_plugin_page($hook = null) {
        if ($hook) {
            return strpos($hook, $this->page_slug) !== false;
        }
        
        return isset($_GET['page']) && $_GET['page'] === $this->page_slug;
    }
}