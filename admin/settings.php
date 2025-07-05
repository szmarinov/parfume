<?php
/**
 * Settings Admin Page for Parfume Reviews Plugin
 * 
 * @package ParfumeReviews
 * @subpackage Admin
 * @since 1.0.0
 */

namespace Parfume_Reviews\Admin;

use Parfume_Reviews\Utils\Admin_Page_Base;
use Parfume_Reviews\Utils\Helpers;
use Parfume_Reviews\Utils\Cache_Manager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Admin Page Class
 * 
 * Handles all plugin settings and configuration.
 * Extends Admin_Page_Base for consistent functionality.
 */
class Settings extends Admin_Page_Base {
    
    /**
     * Page configuration
     * 
     * @var array
     */
    protected $page_config = array(
        'page_title' => 'Parfume Reviews Settings',
        'menu_title' => 'Settings',
        'capability' => 'manage_options',
        'menu_slug' => 'parfume-reviews-settings',
        'position' => 10,
        'icon_url' => 'dashicons-admin-settings',
        'parent_slug' => 'edit.php?post_type=parfume',
    );

    /**
     * Settings sections and fields
     * 
     * @var array
     */
    private $settings_sections = array();

    /**
     * Initialize the settings page
     * 
     * @return void
     */
    public function init() {
        $this->setup_settings_sections();
        parent::init();
        
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_parfume_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_parfume_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_parfume_import_settings', array($this, 'ajax_import_settings'));
    }

    /**
     * Setup settings sections and fields
     * 
     * @return void
     */
    private function setup_settings_sections() {
        $this->settings_sections = array(
            'general' => array(
                'title' => esc_html__('General Settings', 'parfume-reviews'),
                'description' => esc_html__('Configure basic plugin settings and behavior.', 'parfume-reviews'),
                'fields' => array(
                    'enable_plugin' => array(
                        'title' => esc_html__('Enable Plugin', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Enable or disable the plugin functionality.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'parfume_slug' => array(
                        'title' => esc_html__('Parfume Archive Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for the parfume archive (default: parfiumi).', 'parfume-reviews'),
                        'default' => 'parfiumi',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'posts_per_page' => array(
                        'title' => esc_html__('Posts Per Page', 'parfume-reviews'),
                        'type' => 'number',
                        'description' => esc_html__('Number of parfumes to show per archive page.', 'parfume-reviews'),
                        'default' => 12,
                        'min' => 1,
                        'max' => 100,
                    ),
                    'default_image' => array(
                        'title' => esc_html__('Default Parfume Image', 'parfume-reviews'),
                        'type' => 'media',
                        'description' => esc_html__('Default image to show when parfume has no featured image.', 'parfume-reviews'),
                    ),
                ),
            ),
            'taxonomy_slugs' => array(
                'title' => esc_html__('Taxonomy URL Slugs', 'parfume-reviews'),
                'description' => esc_html__('Customize URL slugs for different taxonomy archives. Changes require permalink refresh.', 'parfume-reviews'),
                'fields' => array(
                    'brands_slug' => array(
                        'title' => esc_html__('Brands Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for brands taxonomy (default: marki).', 'parfume-reviews'),
                        'default' => 'marki',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'notes_slug' => array(
                        'title' => esc_html__('Notes Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for notes taxonomy (default: notes).', 'parfume-reviews'),
                        'default' => 'notes',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'perfumers_slug' => array(
                        'title' => esc_html__('Perfumers Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for perfumers taxonomy (default: perfumers).', 'parfume-reviews'),
                        'default' => 'perfumers',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'gender_slug' => array(
                        'title' => esc_html__('Gender Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for gender taxonomy (default: gender).', 'parfume-reviews'),
                        'default' => 'gender',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'aroma_type_slug' => array(
                        'title' => esc_html__('Aroma Type Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for aroma type taxonomy (default: aroma-type).', 'parfume-reviews'),
                        'default' => 'aroma-type',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'season_slug' => array(
                        'title' => esc_html__('Season Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for season taxonomy (default: season).', 'parfume-reviews'),
                        'default' => 'season',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'intensity_slug' => array(
                        'title' => esc_html__('Intensity Slug', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('URL slug for intensity taxonomy (default: intensity).', 'parfume-reviews'),
                        'default' => 'intensity',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                ),
            ),
            'display' => array(
                'title' => esc_html__('Display Settings', 'parfume-reviews'),
                'description' => esc_html__('Configure how parfumes and archives are displayed.', 'parfume-reviews'),
                'fields' => array(
                    'grid_columns' => array(
                        'title' => esc_html__('Grid Columns', 'parfume-reviews'),
                        'type' => 'select',
                        'description' => esc_html__('Number of columns in parfume grid layout.', 'parfume-reviews'),
                        'options' => array(
                            '2' => esc_html__('2 Columns', 'parfume-reviews'),
                            '3' => esc_html__('3 Columns', 'parfume-reviews'),
                            '4' => esc_html__('4 Columns', 'parfume-reviews'),
                            '5' => esc_html__('5 Columns', 'parfume-reviews'),
                        ),
                        'default' => '3',
                    ),
                    'show_ratings' => array(
                        'title' => esc_html__('Show Ratings', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Display star ratings on parfume cards.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'show_brands' => array(
                        'title' => esc_html__('Show Brands', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Display brand names on parfume cards.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'show_notes' => array(
                        'title' => esc_html__('Show Notes', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Display fragrance notes on parfume cards.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'excerpt_length' => array(
                        'title' => esc_html__('Excerpt Length', 'parfume-reviews'),
                        'type' => 'number',
                        'description' => esc_html__('Number of words in parfume excerpt (0 to disable).', 'parfume-reviews'),
                        'default' => 25,
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
            ),
            'functionality' => array(
                'title' => esc_html__('Functionality Settings', 'parfume-reviews'),
                'description' => esc_html__('Enable or disable specific plugin features.', 'parfume-reviews'),
                'fields' => array(
                    'enable_comparison' => array(
                        'title' => esc_html__('Enable Comparison', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Allow users to compare parfumes side by side.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'enable_collections' => array(
                        'title' => esc_html__('Enable Collections', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Allow users to create parfume collections.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'enable_stores' => array(
                        'title' => esc_html__('Enable Store Links', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Show store links and pricing information.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'enable_blog' => array(
                        'title' => esc_html__('Enable Blog', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Enable blog functionality for parfume-related content.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'enable_reviews' => array(
                        'title' => esc_html__('Enable User Reviews', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Allow users to submit their own parfume reviews.', 'parfume-reviews'),
                        'default' => false,
                    ),
                ),
            ),
            'performance' => array(
                'title' => esc_html__('Performance Settings', 'parfume-reviews'),
                'description' => esc_html__('Configure caching and performance optimization.', 'parfume-reviews'),
                'fields' => array(
                    'enable_cache' => array(
                        'title' => esc_html__('Enable Caching', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Enable internal caching for better performance.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'cache_duration' => array(
                        'title' => esc_html__('Cache Duration', 'parfume-reviews'),
                        'type' => 'select',
                        'description' => esc_html__('How long to cache data before refreshing.', 'parfume-reviews'),
                        'options' => array(
                            '900' => esc_html__('15 Minutes', 'parfume-reviews'),
                            '1800' => esc_html__('30 Minutes', 'parfume-reviews'),
                            '3600' => esc_html__('1 Hour', 'parfume-reviews'),
                            '7200' => esc_html__('2 Hours', 'parfume-reviews'),
                            '14400' => esc_html__('4 Hours', 'parfume-reviews'),
                            '86400' => esc_html__('24 Hours', 'parfume-reviews'),
                        ),
                        'default' => '3600',
                    ),
                    'lazy_load_images' => array(
                        'title' => esc_html__('Lazy Load Images', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Load images only when they become visible.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'minify_css' => array(
                        'title' => esc_html__('Minify CSS', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Compress CSS files for faster loading.', 'parfume-reviews'),
                        'default' => false,
                    ),
                ),
            ),
            'seo' => array(
                'title' => esc_html__('SEO Settings', 'parfume-reviews'),
                'description' => esc_html__('Configure search engine optimization features.', 'parfume-reviews'),
                'fields' => array(
                    'enable_schema' => array(
                        'title' => esc_html__('Enable Schema Markup', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Add structured data for better search engine understanding.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'enable_breadcrumbs' => array(
                        'title' => esc_html__('Enable Breadcrumbs', 'parfume-reviews'),
                        'type' => 'checkbox',
                        'description' => esc_html__('Show navigation breadcrumbs on parfume pages.', 'parfume-reviews'),
                        'default' => true,
                    ),
                    'meta_title_format' => array(
                        'title' => esc_html__('Meta Title Format', 'parfume-reviews'),
                        'type' => 'text',
                        'description' => esc_html__('Template for meta titles. Use {title}, {brand}, {site_name}.', 'parfume-reviews'),
                        'default' => '{title} by {brand} - {site_name}',
                    ),
                    'meta_description_format' => array(
                        'title' => esc_html__('Meta Description Format', 'parfume-reviews'),
                        'type' => 'textarea',
                        'description' => esc_html__('Template for meta descriptions. Use {excerpt}, {notes}, {brand}.', 'parfume-reviews'),
                        'default' => '{excerpt} Discover {title} by {brand} and explore its unique fragrance notes.',
                    ),
                ),
            ),
        );
    }

    /**
     * Render the admin page
     * 
     * @return void
     */
    public function render_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap parfume-settings-page">
            <h1><?php echo esc_html($this->page_config['page_title']); ?></h1>
            
            <div class="settings-header">
                <div class="settings-info">
                    <p><?php esc_html_e('Configure your Parfume Reviews plugin settings below. Don\'t forget to save changes!', 'parfume-reviews'); ?></p>
                </div>
                
                <div class="settings-actions">
                    <button type="button" class="button" id="export-settings">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export Settings', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="button" id="import-settings">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Import Settings', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="reset-settings">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Reset to Defaults', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>

            <div class="nav-tab-wrapper">
                <?php foreach ($this->settings_sections as $section_id => $section): ?>
                    <a href="?post_type=parfume&page=<?php echo esc_attr($this->page_config['menu_slug']); ?>&tab=<?php echo esc_attr($section_id); ?>" 
                       class="nav-tab <?php echo $current_tab === $section_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($section['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="post" action="" id="parfume-settings-form">
                <?php wp_nonce_field('parfume_settings_save', 'parfume_settings_nonce'); ?>
                
                <div class="tab-content">
                    <?php $this->render_settings_section($current_tab); ?>
                </div>

                <div class="settings-footer">
                    <?php submit_button(esc_html__('Save Settings', 'parfume-reviews'), 'primary', 'submit', false); ?>
                    <span class="settings-status" id="save-status"></span>
                </div>
            </form>

            <!-- Import Modal -->
            <div id="import-modal" class="parfume-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?php esc_html_e('Import Settings', 'parfume-reviews'); ?></h2>
                        <button type="button" class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p><?php esc_html_e('Upload a settings file to import configuration:', 'parfume-reviews'); ?></p>
                        <input type="file" id="settings-file" accept=".json">
                        <div class="import-preview" style="display: none;">
                            <h4><?php esc_html_e('Settings Preview:', 'parfume-reviews'); ?></h4>
                            <pre id="settings-preview"></pre>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button button-primary" id="confirm-import" disabled>
                            <?php esc_html_e('Import Settings', 'parfume-reviews'); ?>
                        </button>
                        <button type="button" class="button" id="cancel-import">
                            <?php esc_html_e('Cancel', 'parfume-reviews'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings section
     * 
     * @param string $section_id Section identifier
     * @return void
     */
    private function render_settings_section($section_id) {
        if (!isset($this->settings_sections[$section_id])) {
            return;
        }

        $section = $this->settings_sections[$section_id];
        $settings = Helpers::get_plugin_settings();

        ?>
        <div class="settings-section">
            <div class="section-header">
                <h2><?php echo esc_html($section['title']); ?></h2>
                <?php if (!empty($section['description'])): ?>
                    <p class="description"><?php echo esc_html($section['description']); ?></p>
                <?php endif; ?>
            </div>

            <table class="form-table">
                <?php foreach ($section['fields'] as $field_id => $field): ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($field_id); ?>">
                                <?php echo esc_html($field['title']); ?>
                            </label>
                        </th>
                        <td>
                            <?php $this->render_field($field_id, $field, $settings); ?>
                            <?php if (!empty($field['description'])): ?>
                                <p class="description"><?php echo esc_html($field['description']); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Render individual form field
     * 
     * @param string $field_id Field identifier
     * @param array $field Field configuration
     * @param array $settings Current settings
     * @return void
     */
    private function render_field($field_id, $field, $settings) {
        $value = isset($settings[$field_id]) ? $settings[$field_id] : $field['default'];
        $name = "parfume_settings[{$field_id}]";

        switch ($field['type']) {
            case 'text':
                ?>
                <input type="text" 
                       id="<?php echo esc_attr($field_id); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       class="regular-text" />
                <?php
                break;

            case 'textarea':
                ?>
                <textarea id="<?php echo esc_attr($field_id); ?>" 
                          name="<?php echo esc_attr($name); ?>" 
                          rows="4" 
                          class="large-text"><?php echo esc_textarea($value); ?></textarea>
                <?php
                break;

            case 'number':
                $min = isset($field['min']) ? intval($field['min']) : '';
                $max = isset($field['max']) ? intval($field['max']) : '';
                ?>
                <input type="number" 
                       id="<?php echo esc_attr($field_id); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       <?php echo $min !== '' ? 'min="' . esc_attr($min) . '"' : ''; ?>
                       <?php echo $max !== '' ? 'max="' . esc_attr($max) . '"' : ''; ?>
                       class="small-text" />
                <?php
                break;

            case 'checkbox':
                ?>
                <label for="<?php echo esc_attr($field_id); ?>">
                    <input type="checkbox" 
                           id="<?php echo esc_attr($field_id); ?>" 
                           name="<?php echo esc_attr($name); ?>" 
                           value="1" 
                           <?php checked($value, 1); ?> />
                    <?php echo isset($field['label']) ? esc_html($field['label']) : esc_html__('Enable', 'parfume-reviews'); ?>
                </label>
                <?php
                break;

            case 'select':
                ?>
                <select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($name); ?>">
                    <?php foreach ($field['options'] as $option_value => $option_label): ?>
                        <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                            <?php echo esc_html($option_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
                break;

            case 'media':
                $image = $value ? wp_get_attachment_image_src($value, 'thumbnail') : false;
                ?>
                <div class="media-field">
                    <input type="hidden" 
                           id="<?php echo esc_attr($field_id); ?>" 
                           name="<?php echo esc_attr($name); ?>" 
                           value="<?php echo esc_attr($value); ?>" />
                    
                    <div class="media-preview">
                        <?php if ($image): ?>
                            <img src="<?php echo esc_url($image[0]); ?>" alt="" style="max-width: 150px; height: auto;" />
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="button media-upload-button" data-field="<?php echo esc_attr($field_id); ?>">
                        <?php esc_html_e('Choose Image', 'parfume-reviews'); ?>
                    </button>
                    
                    <?php if ($value): ?>
                        <button type="button" class="button media-remove-button" data-field="<?php echo esc_attr($field_id); ?>">
                            <?php esc_html_e('Remove', 'parfume-reviews'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php
                break;
        }
    }

    /**
     * Register settings with WordPress
     * 
     * @return void
     */
    public function register_settings() {
        register_setting(
            'parfume_reviews_settings',
            'parfume_reviews_settings',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_default_settings(),
            )
        );

        // Add settings sections and fields
        foreach ($this->settings_sections as $section_id => $section) {
            add_settings_section(
                "parfume_settings_{$section_id}",
                $section['title'],
                null,
                'parfume_reviews_settings'
            );

            foreach ($section['fields'] as $field_id => $field) {
                add_settings_field(
                    $field_id,
                    $field['title'],
                    array($this, 'render_field_callback'),
                    'parfume_reviews_settings',
                    "parfume_settings_{$section_id}",
                    array('field_id' => $field_id, 'field' => $field)
                );
            }
        }
    }

    /**
     * Field render callback for WordPress settings API
     * 
     * @param array $args Field arguments
     * @return void
     */
    public function render_field_callback($args) {
        $settings = Helpers::get_plugin_settings();
        $this->render_field($args['field_id'], $args['field'], $settings);
    }

    /**
     * Get default settings
     * 
     * @return array
     */
    private function get_default_settings() {
        $defaults = array();

        foreach ($this->settings_sections as $section) {
            foreach ($section['fields'] as $field_id => $field) {
                $defaults[$field_id] = $field['default'];
            }
        }

        return $defaults;
    }

    /**
     * Save settings
     * 
     * @return void
     */
    private function save_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['parfume_settings_nonce'], 'parfume_settings_save')) {
            add_settings_error(
                'parfume_settings',
                'nonce_error',
                esc_html__('Security check failed. Please try again.', 'parfume-reviews'),
                'error'
            );
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'parfume_settings',
                'permission_error',
                esc_html__('You do not have permission to modify settings.', 'parfume-reviews'),
                'error'
            );
            return;
        }

        $settings = isset($_POST['parfume_settings']) ? $_POST['parfume_settings'] : array();
        $sanitized_settings = $this->sanitize_settings($settings);

        // Save to database
        $saved = update_option('parfume_reviews_settings', $sanitized_settings);

        if ($saved !== false) {
            // Clear cache after settings change
            Cache_Manager::clear_all_cache();

            // Check if permalink flush is needed
            $permalink_fields = array('parfume_slug', 'brands_slug', 'notes_slug', 'perfumers_slug', 'gender_slug', 'aroma_type_slug', 'season_slug', 'intensity_slug');
            $current_settings = Helpers::get_plugin_settings();
            
            $flush_needed = false;
            foreach ($permalink_fields as $field) {
                if (isset($sanitized_settings[$field]) && $sanitized_settings[$field] !== $current_settings[$field]) {
                    $flush_needed = true;
                    break;
                }
            }

            if ($flush_needed) {
                flush_rewrite_rules();
                add_settings_error(
                    'parfume_settings',
                    'permalink_flushed',
                    esc_html__('Settings saved successfully. Permalinks have been refreshed.', 'parfume-reviews'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'parfume_settings',
                    'settings_saved',
                    esc_html__('Settings saved successfully.', 'parfume-reviews'),
                    'updated'
                );
            }
        } else {
            add_settings_error(
                'parfume_settings',
                'save_error',
                esc_html__('Failed to save settings. Please try again.', 'parfume-reviews'),
                'error'
            );
        }
    }

    /**
     * Sanitize settings before saving
     * 
     * @param array $settings Raw settings data
     * @return array Sanitized settings
     */
    public function sanitize_settings($settings) {
        $sanitized = array();

        foreach ($this->settings_sections as $section) {
            foreach ($section['fields'] as $field_id => $field) {
                $value = isset($settings[$field_id]) ? $settings[$field_id] : $field['default'];

                switch ($field['type']) {
                    case 'text':
                        if (isset($field['sanitize_callback']) && function_exists($field['sanitize_callback'])) {
                            $sanitized[$field_id] = call_user_func($field['sanitize_callback'], $value);
                        } else {
                            $sanitized[$field_id] = sanitize_text_field($value);
                        }
                        break;

                    case 'textarea':
                        $sanitized[$field_id] = sanitize_textarea_field($value);
                        break;

                    case 'number':
                        $sanitized[$field_id] = intval($value);
                        if (isset($field['min']) && $sanitized[$field_id] < $field['min']) {
                            $sanitized[$field_id] = $field['min'];
                        }
                        if (isset($field['max']) && $sanitized[$field_id] > $field['max']) {
                            $sanitized[$field_id] = $field['max'];
                        }
                        break;

                    case 'checkbox':
                        $sanitized[$field_id] = !empty($value) ? 1 : 0;
                        break;

                    case 'select':
                        $sanitized[$field_id] = array_key_exists($value, $field['options']) ? $value : $field['default'];
                        break;

                    case 'media':
                        $sanitized[$field_id] = absint($value);
                        break;

                    default:
                        $sanitized[$field_id] = sanitize_text_field($value);
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, $this->page_config['menu_slug']) === false) {
            return;
        }

        // Enqueue media uploader
        wp_enqueue_media();

        // Enqueue styles
        wp_enqueue_style(
            'parfume-admin-settings',
            PARFUME_REVIEWS_PLUGIN_URL . 'admin/assets/css/settings.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'parfume-admin-settings',
            PARFUME_REVIEWS_PLUGIN_URL . 'admin/assets/js/settings.js',
            array('jquery', 'media-upload', 'thickbox'),
            PARFUME_REVIEWS_VERSION,
            true
        );

        // Localize script
        wp_localize_script('parfume-admin-settings', 'parfumeSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_settings_nonce'),
            'strings' => array(
                'confirm_reset' => esc_html__('Are you sure you want to reset all settings to default values?', 'parfume-reviews'),
                'confirm_import' => esc_html__('Are you sure you want to import these settings? This will overwrite your current configuration.', 'parfume-reviews'),
                'export_success' => esc_html__('Settings exported successfully.', 'parfume-reviews'),
                'import_success' => esc_html__('Settings imported successfully.', 'parfume-reviews'),
                'reset_success' => esc_html__('Settings reset to defaults successfully.', 'parfume-reviews'),
                'error_occurred' => esc_html__('An error occurred. Please try again.', 'parfume-reviews'),
                'invalid_file' => esc_html__('Invalid file format. Please select a valid JSON file.', 'parfume-reviews'),
                'choose_media' => esc_html__('Choose Image', 'parfume-reviews'),
                'use_media' => esc_html__('Use This Image', 'parfume-reviews'),
            ),
        ));
    }

    /**
     * AJAX handler for resetting settings
     * 
     * @return void
     */
    public function ajax_reset_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'parfume-reviews')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'parfume-reviews')
            ));
        }

        // Reset to defaults
        $default_settings = $this->get_default_settings();
        $updated = update_option('parfume_reviews_settings', $default_settings);

        if ($updated !== false) {
            // Clear cache
            Cache_Manager::clear_all_cache();
            
            wp_send_json_success(array(
                'message' => esc_html__('Settings reset to defaults successfully.', 'parfume-reviews'),
                'settings' => $default_settings
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to reset settings.', 'parfume-reviews')
            ));
        }
    }

    /**
     * AJAX handler for exporting settings
     * 
     * @return void
     */
    public function ajax_export_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'parfume-reviews')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'parfume-reviews')
            ));
        }

        $settings = Helpers::get_plugin_settings();
        
        // Add export metadata
        $export_data = array(
            'plugin_version' => PARFUME_REVIEWS_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'settings' => $settings
        );

        wp_send_json_success(array(
            'data' => $export_data,
            'filename' => 'parfume-reviews-settings-' . date('Y-m-d-H-i-s') . '.json'
        ));
    }

    /**
     * AJAX handler for importing settings
     * 
     * @return void
     */
    public function ajax_import_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_settings_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'parfume-reviews')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'parfume-reviews')
            ));
        }

        $import_data = json_decode(stripslashes($_POST['import_data']), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid JSON data.', 'parfume-reviews')
            ));
        }

        // Validate import data structure
        if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid settings file structure.', 'parfume-reviews')
            ));
        }

        // Sanitize imported settings
        $sanitized_settings = $this->sanitize_settings($import_data['settings']);
        
        // Update settings
        $updated = update_option('parfume_reviews_settings', $sanitized_settings);

        if ($updated !== false) {
            // Clear cache
            Cache_Manager::clear_all_cache();
            
            // Flush rewrite rules if needed
            flush_rewrite_rules();
            
            wp_send_json_success(array(
                'message' => esc_html__('Settings imported successfully.', 'parfume-reviews'),
                'settings' => $sanitized_settings
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to import settings.', 'parfume-reviews')
            ));
        }
    }
}