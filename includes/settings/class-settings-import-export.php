<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Import_Export class - Управлява import/export настройките и интерфейса
 * 
 * Файл: includes/settings/class-settings-import-export.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_Import_Export {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за import/export
     */
    public function register_settings() {
        // Import/Export Section
        add_settings_section(
            'parfume_reviews_import_export_section',
            __('Импорт и експорт', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'import_export_backup_enabled',
            __('Автоматично бекъпиране', 'parfume-reviews'),
            array($this, 'backup_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_import_export_section'
        );
        
        add_settings_field(
            'import_export_backup_frequency',
            __('Честота на бекъпиране', 'parfume-reviews'),
            array($this, 'backup_frequency_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_import_export_section'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Импортирайте и експортирайте парфюми, настройки и таксономии.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с import/export интерфейс
     */
    public function render_section() {
        ?>
        <!-- Backup Settings -->
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="backup_enabled"><?php _e('Автоматично бекъпиране', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->backup_enabled_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="backup_frequency"><?php _e('Честота на бекъпиране', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->backup_frequency_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Import/Export Interface -->
        <div class="import-export-sections" style="margin-top: 40px;">
            
            <!-- Parfume Import/Export -->
            <div class="import-export-section">
                <h3><?php _e('Импорт и експорт на парфюми', 'parfume-reviews'); ?></h3>
                
                <div class="export-section" style="margin-bottom: 30px;">
                    <h4><?php _e('Експорт на парфюми', 'parfume-reviews'); ?></h4>
                    <p><?php _e('Експортирайте всички парфюми в JSON формат.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('parfume_export', 'parfume_export_nonce'); ?>
                        <input type="submit" name="parfume_export" class="button button-secondary" 
                               value="<?php esc_attr_e('Експортиране на парфюми', 'parfume-reviews'); ?>">
                    </form>
                </div>
                
                <div class="import-section">
                    <h4><?php _e('Импорт на парфюми', 'parfume-reviews'); ?></h4>
                    <p><?php _e('Импортирайте парфюми от JSON файл.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('parfume_import', 'parfume_import_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="parfume_import_file"><?php _e('JSON файл', 'parfume-reviews'); ?></label></th>
                                <td>
                                    <input type="file" id="parfume_import_file" name="parfume_import_file" accept=".json">
                                    <p class="description"><?php _e('Максимален размер: 10MB. Позволени формати: JSON', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" name="parfume_import" class="button button-primary" 
                               value="<?php esc_attr_e('Импортиране на парфюми', 'parfume-reviews'); ?>">
                    </form>
                </div>
                
                <?php $this->render_parfume_json_instructions(); ?>
            </div>
            
            <!-- Perfumer Import/Export -->
            <div class="import-export-section">
                <h3><?php _e('Импорт и експорт на парфюмеристи', 'parfume-reviews'); ?></h3>
                
                <div class="export-section" style="margin-bottom: 30px;">
                    <h4><?php _e('Експорт на парфюмеристи', 'parfume-reviews'); ?></h4>
                    <p><?php _e('Експортирайте всички парфюмеристи в JSON формат.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('perfumer_export', 'perfumer_export_nonce'); ?>
                        <input type="submit" name="perfumer_export" class="button button-secondary" 
                               value="<?php esc_attr_e('Експортиране на парфюмеристи', 'parfume-reviews'); ?>">
                    </form>
                </div>
                
                <div class="import-section">
                    <h4><?php _e('Импорт на парфюмеристи', 'parfume-reviews'); ?></h4>
                    <p><?php _e('Импортирайте парфюмеристи от JSON файл.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('perfumer_import', 'perfumer_import_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="perfumer_import_file"><?php _e('JSON файл', 'parfume-reviews'); ?></label></th>
                                <td>
                                    <input type="file" id="perfumer_import_file" name="perfumer_import_file" accept=".json">
                                    <p class="description"><?php _e('Максимален размер: 10MB. Позволени формати: JSON', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" name="perfumer_import" class="button button-primary" 
                               value="<?php esc_attr_e('Импортиране на парфюмеристи', 'parfume-reviews'); ?>">
                    </form>
                </div>
                
                <?php $this->render_perfumer_json_instructions(); ?>
            </div>
            
            <!-- Settings Import/Export -->
            <div class="import-export-section">
                <h3><?php _e('Импорт и експорт на настройки', 'parfume-reviews'); ?></h3>
                
                <div class="export-section" style="margin-bottom: 30px;">
                    <h4><?php _e('Експорт на настройки', 'parfume-reviews'); ?></h4>
                    <p><?php _e('Експортирайте всички настройки на плъгина.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('settings_export', 'settings_export_nonce'); ?>
                        <div style="margin-bottom: 15px;">
                            <label>
                                <input type="checkbox" name="export_components[]" value="general" checked>
                                <?php _e('Общи настройки', 'parfume-reviews'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="export_components[]" value="url" checked>
                                <?php _e('URL настройки', 'parfume-reviews'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="export_components[]" value="homepage" checked>
                                <?php _e('Настройки за начална страница', 'parfume-reviews'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="export_components[]" value="mobile" checked>
                                <?php _e('Mobile настройки', 'parfume-reviews'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="export_components[]" value="stores" checked>
                                <?php _e('Настройки за магазини', 'parfume-reviews'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="export_components[]" value="scraper" checked>
                                <?php _e('Product Scraper настройки', 'parfume-reviews'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="export_components[]" value="price" checked>
                                <?php _e('Настройки за цени', 'parfume-reviews'); ?>
                            </label>
                        </div>
                        <input type="submit" name="settings_export" class="button button-secondary" 
                               value="<?php esc_attr_e('Експортиране на настройки', 'parfume-reviews'); ?>">
                    </form>
                </div>
                
                <div class="import-section">
                    <h4><?php _e('Импорт на настройки', 'parfume-reviews'); ?></h4>
                    <p><?php _e('Импортирайте настройки от JSON файл.', 'parfume-reviews'); ?></p>
                    
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('settings_import', 'settings_import_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="settings_import_file"><?php _e('JSON файл', 'parfume-reviews'); ?></label></th>
                                <td>
                                    <input type="file" id="settings_import_file" name="settings_import_file" accept=".json">
                                    <p class="description"><?php _e('Файл с настройки, експортиран от същия плъгин.', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" name="settings_import" class="button button-primary" 
                               value="<?php esc_attr_e('Импортиране на настройки', 'parfume-reviews'); ?>">
                    </form>
                </div>
            </div>
            
            <!-- Cleanup Tools -->
            <div class="import-export-section">
                <h3><?php _e('Инструменти за почистване', 'parfume-reviews'); ?></h3>
                
                <div class="cleanup-tools">
                    <h4><?php _e('Изчистване на данни', 'parfume-reviews'); ?></h4>
                    <p class="description" style="color: #dc3232;">
                        <?php _e('⚠️ ВНИМАНИЕ: Тези действия са необратими! Препоръчва се експорт преди изчистване.', 'parfume-reviews'); ?>
                    </p>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" class="button button-secondary cleanup-btn" data-action="cleanup_orphan_meta">
                            <?php _e('Изчисти orphan meta данни', 'parfume-reviews'); ?>
                        </button>
                        <p class="description"><?php _e('Премахва meta данни без свързани постове/термини.', 'parfume-reviews'); ?></p>
                    </div>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" class="button button-secondary cleanup-btn" data-action="cleanup_empty_terms">
                            <?php _e('Изчисти празни термини', 'parfume-reviews'); ?>
                        </button>
                        <p class="description"><?php _e('Премахва термини без свързани постове.', 'parfume-reviews'); ?></p>
                    </div>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" class="button button-secondary cleanup-btn" data-action="rebuild_thumbnails">
                            <?php _e('Възстанови thumbnails', 'parfume-reviews'); ?>
                        </button>
                        <p class="description"><?php _e('Преизгражда thumbnail изображенията за парфюми.', 'parfume-reviews'); ?></p>
                    </div>
                </div>
            </div>
            
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Cleanup buttons functionality
            $('.cleanup-btn').on('click', function(e) {
                e.preventDefault();
                
                var action = $(this).data('action');
                var actionText = $(this).text();
                
                if (!confirm('Сигурни ли сте, че искате да извършите: ' + actionText + '?\n\nТова действие е необратимо!')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('Обработва...');
                
                $.post(ajaxurl, {
                    action: 'parfume_reviews_cleanup',
                    cleanup_action: action,
                    nonce: '<?php echo wp_create_nonce('parfume_cleanup_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        alert('Успешно извършено: ' + response.data.message);
                    } else {
                        alert('Грешка: ' + response.data.message);
                    }
                })
                .fail(function() {
                    alert('Възникна грешка при изпълнението.');
                })
                .always(function() {
                    location.reload(); // Refresh page to show updated status
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Callback за backup_enabled настройката
     */
    public function backup_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['backup_enabled']) ? $settings['backup_enabled'] : false;
        
        echo '<input type="checkbox" 
                     id="backup_enabled"
                     name="parfume_reviews_settings[backup_enabled]" 
                     value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Автоматично създава бекъп копия на данните.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за backup_frequency настройката
     */
    public function backup_frequency_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['backup_frequency']) ? $settings['backup_frequency'] : 'weekly';
        
        $options = array(
            'daily' => __('Ежедневно', 'parfume-reviews'),
            'weekly' => __('Седмично', 'parfume-reviews'),
            'monthly' => __('Месечно', 'parfume-reviews')
        );
        
        echo '<select id="backup_frequency" name="parfume_reviews_settings[backup_frequency]">';
        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>';
            echo esc_html($option_label);
            echo '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Честота на автоматичните бекъп копия.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира инструкции за JSON формат на парфюми
     */
    private function render_parfume_json_instructions() {
        ?>
        <div class="json-format-instructions" style="margin-top: 20px;">
            <details>
                <summary style="cursor: pointer; font-weight: bold;"><?php _e('Кликнете за JSON формат инструкции', 'parfume-reviews'); ?></summary>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 10px;">
                    <h5><?php _e('Примерен JSON формат за парфюми:', 'parfume-reviews'); ?></h5>
                    <pre style="background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto;"><code>[
  {
    "title": "Dior Sauvage",
    "content": "Описание на парфюма...",
    "status": "publish",
    "featured_image": "https://example.com/image.jpg",
    "rating": "8.5",
    "release_year": "2015",
    "longevity": "Дълготрайност: 8-10 часа",
    "sillage": "Силаж: Умерен до силен",
    "gender": ["Мъжки"],
    "brand": ["Dior"],
    "aroma_type": ["Древесен", "Свеж"],
    "perfumer": ["Франсоа Демаши"],
    "notes": ["Бергамот", "Амброксан", "Сичуански пипер"],
    "stores": [
      {
        "name": "Douglas",
        "url": "https://douglas.bg",
        "price": "120 лв",
        "product_url": "https://douglas.bg/product/123"
      }
    ]
  }
]</code></pre>
                    <p><strong><?php _e('Задължителни полета:', 'parfume-reviews'); ?></strong> title</p>
                    <p><strong><?php _e('Допълнителни полета:', 'parfume-reviews'); ?></strong> content, status, featured_image, rating, release_year, longevity, sillage, gender, brand, aroma_type, perfumer, notes, stores</p>
                </div>
            </details>
        </div>
        <?php
    }
    
    /**
     * Рендерира инструкции за JSON формат на парфюмеристи
     */
    private function render_perfumer_json_instructions() {
        ?>
        <div class="json-format-instructions" style="margin-top: 20px;">
            <details>
                <summary style="cursor: pointer; font-weight: bold;"><?php _e('Кликнете за JSON формат инструкции за парфюмеристи', 'parfume-reviews'); ?></summary>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 10px;">
                    <h5><?php _e('Примерен JSON формат за парфюмеристи:', 'parfume-reviews'); ?></h5>
                    <pre style="background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto;"><code>[
  {
    "name": "Франсоа Демаши",
    "slug": "francois-demachy",
    "description": "Главен парфюмер на Dior от 2006 година.",
    "image": "https://example.com/francois-demachy.jpg",
    "birth_date": "1952-03-15",
    "nationality": "Френски",
    "education": "Версай - Институт за международни парфюмни изследвания",
    "career_start": "1978",
    "signature_style": "Елегантни, изискани композиции",
    "famous_fragrances": [
      "Dior Sauvage",
      "J'adore Dior",
      "Miss Dior Cherie"
    ],
    "awards": [
      "Prix François Coty 2010",
      "Lifetime Achievement Award 2018"
    ],
    "website": "https://dior.com",
    "social_media": "https://instagram.com/dior"
  }
]</code></pre>
                    <p><strong><?php _e('Задължителни полета:', 'parfume-reviews'); ?></strong> name</p>
                    <p><strong><?php _e('Допълнителни полета:', 'parfume-reviews'); ?></strong> slug, description, image, birth_date, nationality, education, career_start, signature_style, famous_fragrances, awards, website, social_media</p>
                </div>
            </details>
        </div>
        <?php
    }
    
    /**
     * Експортира настройки в JSON формат
     */
    public function export_settings($components = array()) {
        if (empty($components)) {
            $components = array('general', 'url', 'homepage', 'mobile', 'stores', 'scraper', 'price');
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $export_data = array(
            'version' => PARFUME_REVIEWS_VERSION,
            'export_date' => current_time('mysql'),
            'export_components' => $components,
            'settings' => array()
        );
        
        // Филтрираме настройките според избраните компоненти
        foreach ($components as $component) {
            $component_settings = $this->get_component_settings($settings, $component);
            if (!empty($component_settings)) {
                $export_data['settings'][$component] = $component_settings;
            }
        }
        
        return json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Импортира настройки от JSON данни
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return new \WP_Error('invalid_settings', __('Файлът не съдържа валидни настройки.', 'parfume-reviews'));
        }
        
        $current_settings = get_option('parfume_reviews_settings', array());
        $imported_components = array();
        
        foreach ($data['settings'] as $component => $component_settings) {
            if (is_array($component_settings)) {
                $current_settings = array_merge($current_settings, $component_settings);
                $imported_components[] = $component;
            }
        }
        
        $result = update_option('parfume_reviews_settings', $current_settings);
        
        if ($result) {
            // Flush rewrite rules ако има URL настройки
            if (in_array('url', $imported_components)) {
                update_option('parfume_reviews_flush_rewrite_rules', true);
            }
            
            return array(
                'success' => true,
                'message' => sprintf(__('Успешно импортирани настройки за: %s', 'parfume-reviews'), implode(', ', $imported_components)),
                'imported_components' => $imported_components
            );
        } else {
            return new \WP_Error('save_failed', __('Грешка при запазване на настройките.', 'parfume-reviews'));
        }
    }
    
    /**
     * Получава настройките за конкретен компонент
     */
    private function get_component_settings($settings, $component) {
        $component_keys = array();
        
        switch ($component) {
            case 'general':
                $component_keys = array('posts_per_page');
                break;
            case 'url':
                $component_keys = array('parfume_slug', 'blog_slug', 'marki_slug', 'gender_slug', 'aroma_type_slug', 'season_slug', 'intensity_slug', 'notes_slug', 'perfumer_slug');
                break;
            case 'homepage':
                $component_keys = array('homepage_hero_enabled', 'homepage_featured_enabled', 'homepage_latest_count');
                break;
            case 'mobile':
                $component_keys = array('mobile_fixed_panel', 'mobile_show_close_btn', 'mobile_z_index', 'mobile_bottom_offset');
                break;
            case 'stores':
                $component_keys = array('available_stores', 'default_store_settings');
                break;
            case 'scraper':
                $component_keys = array('scraper_enabled', 'scraper_frequency', 'scraper_timeout');
                break;
            case 'price':
                $component_keys = array('price_currency', 'price_format', 'show_old_prices');
                break;
        }
        
        $component_settings = array();
        foreach ($component_keys as $key) {
            if (isset($settings[$key])) {
                $component_settings[$key] = $settings[$key];
            }
        }
        
        return $component_settings;
    }
    
    /**
     * Получава статистики за import/export операции
     */
    public function get_import_export_stats() {
        $stats = array(
            'parfumes_count' => wp_count_posts('parfume')->publish,
            'perfumers_count' => wp_count_terms(array('taxonomy' => 'perfumer', 'hide_empty' => false)),
            'last_backup' => get_option('parfume_reviews_last_backup', false),
            'backup_size' => $this->get_backup_file_size()
        );
        
        return $stats;
    }
    
    /**
     * Получава размера на backup файла
     */
    private function get_backup_file_size() {
        $backup_file = wp_upload_dir()['basedir'] . '/parfume-reviews-backup.json';
        if (file_exists($backup_file)) {
            return size_format(filesize($backup_file));
        }
        return __('Няма backup файл', 'parfume-reviews');
    }
    
    /**
     * Проверява дали backup е включен
     */
    public function is_backup_enabled() {
        $settings = get_option('parfume_reviews_settings', array());
        return !empty($settings['backup_enabled']);
    }
    
    /**
     * Получава честотата на backup
     */
    public function get_backup_frequency() {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings['backup_frequency']) ? $settings['backup_frequency'] : 'weekly';
    }
}