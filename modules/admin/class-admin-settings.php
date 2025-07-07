<?php
/**
 * Admin Settings Class
 * 
 * Handles all plugin settings and configuration options
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Admin_Settings {
    
    private $option_group = 'parfume_catalog_settings';
    private $settings_page = 'parfume-settings';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_parfume_import_notes', array($this, 'handle_notes_import'));
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Настройки', 'parfume-catalog'),
            __('Настройки', 'parfume-catalog'),
            'manage_options',
            $this->settings_page,
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register all plugin settings
     */
    public function register_settings() {
        // General Settings
        register_setting($this->option_group, 'parfume_archive_slug', array(
            'type' => 'string',
            'default' => 'parfiumi',
            'sanitize_callback' => 'sanitize_title'
        ));
        
        // URL Structure Settings
        register_setting($this->option_group, 'parfume_type_base', array(
            'type' => 'string',
            'default' => 'parfiumi',
            'sanitize_callback' => 'sanitize_title'
        ));
        
        register_setting($this->option_group, 'parfume_vid_base', array(
            'type' => 'string',
            'default' => 'parfiumi',
            'sanitize_callback' => 'sanitize_title'
        ));
        
        register_setting($this->option_group, 'parfume_marki_base', array(
            'type' => 'string',
            'default' => 'parfiumi/marki',
            'sanitize_callback' => 'sanitize_title'
        ));
        
        register_setting($this->option_group, 'parfume_season_base', array(
            'type' => 'string',
            'default' => 'parfiumi/season',
            'sanitize_callback' => 'sanitize_title'
        ));
        
        register_setting($this->option_group, 'parfume_notes_base', array(
            'type' => 'string',
            'default' => 'notes',
            'sanitize_callback' => 'sanitize_title'
        ));
        
        // Related Products Settings
        register_setting($this->option_group, 'parfume_similar_count', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_similar_columns', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_recent_count', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_recent_columns', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_brand_count', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_brand_columns', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => 'absint'
        ));
        
        // Scraper Settings
        register_setting($this->option_group, 'parfume_scraper_interval', array(
            'type' => 'integer',
            'default' => 12,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_scraper_batch_size', array(
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_scraper_user_agent', array(
            'type' => 'string',
            'default' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        // Mobile Settings
        register_setting($this->option_group, 'parfume_mobile_fixed_panel', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting($this->option_group, 'parfume_mobile_show_close_button', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting($this->option_group, 'parfume_mobile_z_index', array(
            'type' => 'integer',
            'default' => 9999,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting($this->option_group, 'parfume_mobile_offset', array(
            'type' => 'integer',
            'default' => 0,
            'sanitize_callback' => 'absint'
        ));
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (isset($_POST['submit'])) {
            // Force rewrite rules flush after saving
            add_action('admin_init', 'flush_rewrite_rules');
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Настройки на Parfume Catalog', 'parfume-catalog'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields($this->option_group); ?>
                
                <!-- General Settings Tab -->
                <h2 class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active"><?php _e('Общи', 'parfume-catalog'); ?></a>
                    <a href="#urls" class="nav-tab"><?php _e('URL Структури', 'parfume-catalog'); ?></a>
                    <a href="#display" class="nav-tab"><?php _e('Показване', 'parfume-catalog'); ?></a>
                    <a href="#scraper" class="nav-tab"><?php _e('Scraper', 'parfume-catalog'); ?></a>
                    <a href="#mobile" class="nav-tab"><?php _e('Мобилни', 'parfume-catalog'); ?></a>
                    <a href="#notes" class="nav-tab"><?php _e('Нотки', 'parfume-catalog'); ?></a>
                    <a href="#documentation" class="nav-tab"><?php _e('Документация', 'parfume-catalog'); ?></a>
                </h2>
                
                <!-- General Settings -->
                <div id="general" class="tab-content">
                    <h3><?php _e('Основни настройки', 'parfume-catalog'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="parfume_archive_slug"><?php _e('Основен URL на архива', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="parfume_archive_slug" 
                                       name="parfume_archive_slug" 
                                       value="<?php echo esc_attr(get_option('parfume_archive_slug', 'parfiumi')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('URL база за архива на парфюмите (напр. /parfiumi/)', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- URL Structure Settings -->
                <div id="urls" class="tab-content" style="display:none;">
                    <h3><?php _e('URL Структури', 'parfume-catalog'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="parfume_type_base"><?php _e('База за типове', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="parfume_type_base" 
                                       name="parfume_type_base" 
                                       value="<?php echo esc_attr(get_option('parfume_type_base', 'parfiumi')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('URL база за типове парфюми (напр. /parfiumi/damski/)', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_vid_base"><?php _e('База за видове аромат', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="parfume_vid_base" 
                                       name="parfume_vid_base" 
                                       value="<?php echo esc_attr(get_option('parfume_vid_base', 'parfiumi')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('URL база за видове аромат (напр. /parfiumi/parfumi/)', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_marki_base"><?php _e('База за марки', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="parfume_marki_base" 
                                       name="parfume_marki_base" 
                                       value="<?php echo esc_attr(get_option('parfume_marki_base', 'parfiumi/marki')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('URL база за марки (напр. /parfiumi/marki/)', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_season_base"><?php _e('База за сезони', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="parfume_season_base" 
                                       name="parfume_season_base" 
                                       value="<?php echo esc_attr(get_option('parfume_season_base', 'parfiumi/season')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('URL база за сезони (напр. /parfiumi/season/)', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_notes_base"><?php _e('База за нотки', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="parfume_notes_base" 
                                       name="parfume_notes_base" 
                                       value="<?php echo esc_attr(get_option('parfume_notes_base', 'notes')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('URL база за нотки (напр. /notes/)', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Display Settings -->
                <div id="display" class="tab-content" style="display:none;">
                    <h3><?php _e('Настройки за показване', 'parfume-catalog'); ?></h3>
                    
                    <h4><?php _e('Подобни аромати', 'parfume-catalog'); ?></h4>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="parfume_similar_count"><?php _e('Брой подобни парфюми', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_similar_count" 
                                       name="parfume_similar_count" 
                                       value="<?php echo esc_attr(get_option('parfume_similar_count', 4)); ?>" 
                                       min="1" max="20" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_similar_columns"><?php _e('Колони подобни парфюми', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_similar_columns" 
                                       name="parfume_similar_columns" 
                                       value="<?php echo esc_attr(get_option('parfume_similar_columns', 4)); ?>" 
                                       min="1" max="6" />
                            </td>
                        </tr>
                    </table>
                    
                    <h4><?php _e('Наскоро разгледани', 'parfume-catalog'); ?></h4>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="parfume_recent_count"><?php _e('Брой наскоро разгледани', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_recent_count" 
                                       name="parfume_recent_count" 
                                       value="<?php echo esc_attr(get_option('parfume_recent_count', 4)); ?>" 
                                       min="1" max="20" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_recent_columns"><?php _e('Колони наскоро разгледани', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_recent_columns" 
                                       name="parfume_recent_columns" 
                                       value="<?php echo esc_attr(get_option('parfume_recent_columns', 4)); ?>" 
                                       min="1" max="6" />
                            </td>
                        </tr>
                    </table>
                    
                    <h4><?php _e('Други от марката', 'parfume-catalog'); ?></h4>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="parfume_brand_count"><?php _e('Брой от марката', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_brand_count" 
                                       name="parfume_brand_count" 
                                       value="<?php echo esc_attr(get_option('parfume_brand_count', 4)); ?>" 
                                       min="1" max="20" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_brand_columns"><?php _e('Колони от марката', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_brand_columns" 
                                       name="parfume_brand_columns" 
                                       value="<?php echo esc_attr(get_option('parfume_brand_columns', 4)); ?>" 
                                       min="1" max="6" />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Scraper Settings -->
                <div id="scraper" class="tab-content" style="display:none;">
                    <h3><?php _e('Настройки на скрейпъра', 'parfume-catalog'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="parfume_scraper_interval"><?php _e('Интервал на скрейпване (часове)', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_scraper_interval" 
                                       name="parfume_scraper_interval" 
                                       value="<?php echo esc_attr(get_option('parfume_scraper_interval', 12)); ?>" 
                                       min="1" max="168" />
                                <p class="description"><?php _e('На колко часа да се обновяват цените', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_scraper_batch_size"><?php _e('Размер на партидата', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_scraper_batch_size" 
                                       name="parfume_scraper_batch_size" 
                                       value="<?php echo esc_attr(get_option('parfume_scraper_batch_size', 10)); ?>" 
                                       min="1" max="50" />
                                <p class="description"><?php _e('Колко URL-а да се обработват наведнъж', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_scraper_user_agent"><?php _e('User Agent', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="parfume_scraper_user_agent" 
                                       name="parfume_scraper_user_agent" 
                                       value="<?php echo esc_attr(get_option('parfume_scraper_user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')); ?>" 
                                       class="large-text" />
                                <p class="description"><?php _e('User agent за заявките към магазините', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Mobile Settings -->
                <div id="mobile" class="tab-content" style="display:none;">
                    <h3><?php _e('Мобилни настройки', 'parfume-catalog'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Фиксиран панел', 'parfume-catalog'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="parfume_mobile_fixed_panel" 
                                           value="1" 
                                           <?php checked(get_option('parfume_mobile_fixed_panel', true)); ?> />
                                    <?php _e('Показвай фиксиран магазин в долната част на екрана на мобилни устройства', 'parfume-catalog'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Бутон за затваряне', 'parfume-catalog'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="parfume_mobile_show_close_button" 
                                           value="1" 
                                           <?php checked(get_option('parfume_mobile_show_close_button', true)); ?> />
                                    <?php _e('Позволявай скриване на "Колона 2" чрез бутон "X"', 'parfume-catalog'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_mobile_z_index"><?php _e('Z-Index', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_mobile_z_index" 
                                       name="parfume_mobile_z_index" 
                                       value="<?php echo esc_attr(get_option('parfume_mobile_z_index', 9999)); ?>" 
                                       min="1" />
                                <p class="description"><?php _e('Z-index на мобилния панел', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="parfume_mobile_offset"><?php _e('Отместване (px)', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="parfume_mobile_offset" 
                                       name="parfume_mobile_offset" 
                                       value="<?php echo esc_attr(get_option('parfume_mobile_offset', 0)); ?>" 
                                       min="0" />
                                <p class="description"><?php _e('Вертикално отместване при други фиксирани елементи', 'parfume-catalog'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Notes Import -->
                <div id="notes" class="tab-content" style="display:none;">
                    <h3><?php _e('Импорт на нотки', 'parfume-catalog'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="notes_json_file"><?php _e('JSON файл с нотки', 'parfume-catalog'); ?></label>
                            </th>
                            <td>
                                <input type="file" 
                                       id="notes_json_file" 
                                       name="notes_json_file" 
                                       accept=".json" />
                                <p class="description">
                                    <?php _e('Качете JSON файл с нотки и техните групи. Формат: [{"note": "Име на нотка", "group": "група"}]', 'parfume-catalog'); ?>
                                </p>
                                <button type="button" 
                                        id="import_notes_btn" 
                                        class="button button-secondary">
                                    <?php _e('Импортирай нотки', 'parfume-catalog'); ?>
                                </button>
                                <div id="import_result" style="margin-top: 10px;"></div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Documentation -->
                <div id="documentation" class="tab-content" style="display:none;">
                    <h3><?php _e('Документация и шорткодове', 'parfume-catalog'); ?></h3>
                    
                    <h4><?php _e('Налични шорткодове:', 'parfume-catalog'); ?></h4>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Шорткод', 'parfume-catalog'); ?></th>
                                <th><?php _e('Описание', 'parfume-catalog'); ?></th>
                                <th><?php _e('Параметри', 'parfume-catalog'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[parfume_list]</code></td>
                                <td><?php _e('Показва списък с парфюми', 'parfume-catalog'); ?></td>
                                <td>count, category, orderby</td>
                            </tr>
                            <tr>
                                <td><code>[parfume_filters]</code></td>
                                <td><?php _e('Показва филтри за парфюми', 'parfume-catalog'); ?></td>
                                <td>type, show_search</td>
                            </tr>
                            <tr>
                                <td><code>[parfume_comparison]</code></td>
                                <td><?php _e('Показва бутон за сравнение', 'parfume-catalog'); ?></td>
                                <td>id</td>
                            </tr>
                            <tr>
                                <td><code>[parfume_stores]</code></td>
                                <td><?php _e('Показва магазини за парфюм', 'parfume-catalog'); ?></td>
                                <td>id</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4><?php _e('Системни изисквания:', 'parfume-catalog'); ?></h4>
                    <ul>
                        <li><?php _e('WordPress 5.0+', 'parfume-catalog'); ?></li>
                        <li><?php _e('PHP 7.4+', 'parfume-catalog'); ?></li>
                        <li><?php _e('MySQL 5.6+', 'parfume-catalog'); ?></li>
                    </ul>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab navigation
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
            });
            
            // Notes import
            $('#import_notes_btn').click(function() {
                var fileInput = $('#notes_json_file')[0];
                if (!fileInput.files[0]) {
                    alert('<?php _e('Моля изберете JSON файл', 'parfume-catalog'); ?>');
                    return;
                }
                
                var formData = new FormData();
                formData.append('action', 'parfume_import_notes');
                formData.append('notes_file', fileInput.files[0]);
                formData.append('nonce', '<?php echo wp_create_nonce('parfume_import_notes'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#import_result').html(response.data.message);
                    },
                    error: function() {
                        $('#import_result').html('<div class="notice notice-error"><p><?php _e('Грешка при импорта', 'parfume-catalog'); ?></p></div>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .tab-content {
            padding: 20px 0;
        }
        
        .tab-content h4 {
            margin-top: 30px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        #import_result .notice {
            padding: 10px;
            margin: 10px 0;
        }
        </style>
        <?php
    }
    
    /**
     * Handle notes import via AJAX
     */
    public function handle_notes_import() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Недостатъчни права', 'parfume-catalog'));
        }
        
        check_ajax_referer('parfume_import_notes', 'nonce');
        
        if (!isset($_FILES['notes_file'])) {
            wp_send_json_error(array('message' => __('Няма качен файл', 'parfume-catalog')));
        }
        
        $file = $_FILES['notes_file'];
        $json_content = file_get_contents($file['tmp_name']);
        $notes_data = json_decode($json_content, true);
        
        if (!$notes_data) {
            wp_send_json_error(array('message' => __('Невалиден JSON формат', 'parfume-catalog')));
        }
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($notes_data as $note_item) {
            if (!isset($note_item['note']) || !isset($note_item['group'])) {
                $skipped++;
                continue;
            }
            
            // Check if term exists
            $existing_term = term_exists($note_item['note'], 'parfume_notes');
            
            if (!$existing_term) {
                $term_result = wp_insert_term(
                    $note_item['note'],
                    'parfume_notes',
                    array(
                        'slug' => sanitize_title($note_item['note'])
                    )
                );
                
                if (!is_wp_error($term_result)) {
                    // Add group meta
                    update_term_meta($term_result['term_id'], 'note_group', sanitize_text_field($note_item['group']));
                    $imported++;
                } else {
                    $skipped++;
                }
            } else {
                // Update existing term's group
                update_term_meta($existing_term['term_id'], 'note_group', sanitize_text_field($note_item['group']));
                $imported++;
            }
        }
        
        $message = sprintf(
            __('Импортът завърши. Импортирани: %d, Прескочени: %d', 'parfume-catalog'),
            $imported,
            $skipped
        );
        
        wp_send_json_success(array(
            'message' => '<div class="notice notice-success"><p>' . $message . '</p></div>'
        ));
    }
}

// Initialize the admin settings
new Parfume_Admin_Settings();