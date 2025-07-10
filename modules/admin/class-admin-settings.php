<?php
/**
 * Parfume Catalog Admin Settings
 * 
 * Управление на всички настройки на плъгина
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin_Settings {

    /**
     * Settings group
     */
    private $option_group = 'parfume_catalog_settings';

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_parfume_import_notes', array($this, 'ajax_import_notes'));
        add_action('wp_ajax_parfume_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_parfume_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_parfume_import_settings', array($this, 'ajax_import_settings'));
    }

    /**
     * Регистриране на всички настройки
     */
    public function register_settings() {
        // Регистриране на основната опция
        register_setting(
            $this->option_group,
            'parfume_catalog_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => $this->get_default_options()
            )
        );

        // Добавяне на секции
        $this->add_settings_sections();
        
        // Добавяне на полета
        $this->add_settings_fields();
    }

    /**
     * Добавяне на секции за настройки
     */
    private function add_settings_sections() {
        // Основни настройки
        add_settings_section(
            'parfume_general',
            __('Основни настройки', 'parfume-catalog'),
            array($this, 'general_section_callback'),
            'parfume_catalog_settings'
        );

        // URL структури
        add_settings_section(
            'parfume_urls',
            __('URL структури', 'parfume-catalog'),
            array($this, 'urls_section_callback'),
            'parfume_catalog_settings'
        );

        // Single страница настройки
        add_settings_section(
            'parfume_single',
            __('Single страница настройки', 'parfume-catalog'),
            array($this, 'single_section_callback'),
            'parfume_catalog_settings'
        );

        // SEO настройки
        add_settings_section(
            'parfume_seo',
            __('SEO настройки', 'parfume-catalog'),
            array($this, 'seo_section_callback'),
            'parfume_catalog_settings'
        );

        // Scraper настройки
        add_settings_section(
            'parfume_scraper',
            __('Scraper настройки', 'parfume-catalog'),
            array($this, 'scraper_section_callback'),
            'parfume_catalog_settings'
        );

        // Stores настройки
        add_settings_section(
            'parfume_stores',
            __('Stores настройки', 'parfume-catalog'),
            array($this, 'stores_section_callback'),
            'parfume_catalog_settings'
        );

        // Comments настройки
        add_settings_section(
            'parfume_comments',
            __('Comments настройки', 'parfume-catalog'),
            array($this, 'comments_section_callback'),
            'parfume_catalog_settings'
        );

        // Comparison настройки
        add_settings_section(
            'parfume_comparison',
            __('Comparison настройки', 'parfume-catalog'),
            array($this, 'comparison_section_callback'),
            'parfume_catalog_settings'
        );

        // Blog настройки
        add_settings_section(
            'parfume_blog',
            __('Blog настройки', 'parfume-catalog'),
            array($this, 'blog_section_callback'),
            'parfume_catalog_settings'
        );

        // Import/Export настройки
        add_settings_section(
            'parfume_import_export',
            __('Import/Export', 'parfume-catalog'),
            array($this, 'import_export_section_callback'),
            'parfume_catalog_settings'
        );
    }

    /**
     * Добавяне на полета за настройки
     */
    private function add_settings_fields() {
        // ОСНОВНИ НАСТРОЙКИ
        add_settings_field(
            'archive_slug',
            __('Основен архивен URL', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_general',
            array(
                'field' => 'archive_slug',
                'description' => __('URL за архивна страница на парфюмите (по подразбиране: parfiumi)', 'parfume-catalog'),
                'placeholder' => 'parfiumi'
            )
        );

        add_settings_field(
            'posts_per_page',
            __('Постове на страница', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_general',
            array(
                'field' => 'posts_per_page',
                'description' => __('Брой парфюми за показване на архивни страници', 'parfume-catalog'),
                'min' => 1,
                'max' => 100,
                'default' => 12
            )
        );

        // URL СТРУКТУРИ
        add_settings_field(
            'type_slug',
            __('URL за типове', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_urls',
            array(
                'field' => 'type_slug',
                'description' => __('URL база за типове парфюми (дамски, мъжки и др.)', 'parfume-catalog'),
                'placeholder' => 'parfiumi'
            )
        );

        add_settings_field(
            'vid_slug',
            __('URL за вид аромат', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_urls',
            array(
                'field' => 'vid_slug',
                'description' => __('URL база за вид аромат (тоалетна вода, парфюм и др.)', 'parfume-catalog'),
                'placeholder' => 'parfiumi'
            )
        );

        add_settings_field(
            'marki_slug',
            __('URL за марки', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_urls',
            array(
                'field' => 'marki_slug',
                'description' => __('URL база за марки парфюми', 'parfume-catalog'),
                'placeholder' => 'parfiumi/marki'
            )
        );

        add_settings_field(
            'season_slug',
            __('URL за сезони', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_urls',
            array(
                'field' => 'season_slug',
                'description' => __('URL база за сезони', 'parfume-catalog'),
                'placeholder' => 'parfiumi/season'
            )
        );

        add_settings_field(
            'intensity_slug',
            __('URL за интензивност', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_urls',
            array(
                'field' => 'intensity_slug',
                'description' => __('URL база за интензивност', 'parfume-catalog'),
                'placeholder' => 'parfiumi/intensity'
            )
        );

        add_settings_field(
            'notes_slug',
            __('URL за нотки', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_urls',
            array(
                'field' => 'notes_slug',
                'description' => __('URL база за ароматни нотки', 'parfume-catalog'),
                'placeholder' => 'notes'
            )
        );

        add_settings_field(
            'blog_slug',
            __('URL за блог', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_urls',
            array(
                'field' => 'blog_slug',
                'description' => __('URL за блог секцията', 'parfume-catalog'),
                'placeholder' => 'blog'
            )
        );

        // SINGLE СТРАНИЦА НАСТРОЙКИ
        add_settings_field(
            'similar_parfumes_count',
            __('Брой подобни парфюми', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_single',
            array(
                'field' => 'similar_parfumes_count',
                'description' => __('Брой подобни парфюми за показване в single страница', 'parfume-catalog'),
                'min' => 0,
                'max' => 12,
                'default' => 4
            )
        );

        add_settings_field(
            'recently_viewed_count',
            __('Брой наскоро разгледани', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_single',
            array(
                'field' => 'recently_viewed_count',
                'description' => __('Брой наскоро разгледани парфюми за показване', 'parfume-catalog'),
                'min' => 0,
                'max' => 12,
                'default' => 4
            )
        );

        add_settings_field(
            'brand_parfumes_count',
            __('Брой парфюми от марка', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_single',
            array(
                'field' => 'brand_parfumes_count',
                'description' => __('Брой други парфюми от същата марка за показване', 'parfume-catalog'),
                'min' => 0,
                'max' => 12,
                'default' => 4
            )
        );

        add_settings_field(
            'show_similar_columns',
            __('Колони за подобни парфюми', 'parfume-catalog'),
            array($this, 'select_field_callback'),
            'parfume_catalog_settings',
            'parfume_single',
            array(
                'field' => 'show_similar_columns',
                'description' => __('Брой колони за визуализация на подобни парфюми', 'parfume-catalog'),
                'options' => array(
                    '2' => '2 колони',
                    '3' => '3 колони',
                    '4' => '4 колони',
                    '6' => '6 колони'
                ),
                'default' => '4'
            )
        );

        // SEO НАСТРОЙКИ
        add_settings_field(
            'enable_schema',
            __('Разреши Schema.org', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_seo',
            array(
                'field' => 'enable_schema',
                'description' => __('Добавя Schema.org structured data за по-добро SEO', 'parfume-catalog')
            )
        );

        add_settings_field(
            'enable_og_tags',
            __('Open Graph tags', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_seo',
            array(
                'field' => 'enable_og_tags',
                'description' => __('Добавя Open Graph tags за социално споделяне', 'parfume-catalog')
            )
        );

        add_settings_field(
            'enable_twitter_cards',
            __('Twitter Cards', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_seo',
            array(
                'field' => 'enable_twitter_cards',
                'description' => __('Добавя Twitter Cards мета тагове', 'parfume-catalog')
            )
        );

        // SCRAPER НАСТРОЙКИ
        add_settings_field(
            'scraper_interval',
            __('Scraper интервал (часове)', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_scraper',
            array(
                'field' => 'scraper_interval',
                'description' => __('На колко часа да се извършва автоматично скрейпване', 'parfume-catalog'),
                'min' => 1,
                'max' => 168,
                'default' => 12
            )
        );

        add_settings_field(
            'scraper_batch_size',
            __('Batch размер', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_scraper',
            array(
                'field' => 'scraper_batch_size',
                'description' => __('Брой URL-и за обработване в един batch', 'parfume-catalog'),
                'min' => 1,
                'max' => 50,
                'default' => 10
            )
        );

        add_settings_field(
            'scraper_user_agent',
            __('User Agent', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_scraper',
            array(
                'field' => 'scraper_user_agent',
                'description' => __('User Agent string за scraper заявките', 'parfume-catalog'),
                'placeholder' => 'Mozilla/5.0 (compatible; ParfumeCatalogBot/1.0)'
            )
        );

        add_settings_field(
            'scraper_timeout',
            __('Timeout (секунди)', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_scraper',
            array(
                'field' => 'scraper_timeout',
                'description' => __('Timeout за scraper заявките', 'parfume-catalog'),
                'min' => 5,
                'max' => 60,
                'default' => 30
            )
        );

        // STORES НАСТРОЙКИ
        add_settings_field(
            'mobile_fixed_panel',
            __('Мобилен фиксиран панел', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_stores',
            array(
                'field' => 'mobile_fixed_panel',
                'description' => __('Показвай фиксиран панел с магазини на мобилни устройства', 'parfume-catalog')
            )
        );

        add_settings_field(
            'mobile_panel_z_index',
            __('Z-index за мобилен панел', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_stores',
            array(
                'field' => 'mobile_panel_z_index',
                'description' => __('Z-index стойност за мобилния панел (за избягване на припокриване)', 'parfume-catalog'),
                'min' => 1,
                'max' => 9999,
                'default' => 1000
            )
        );

        add_settings_field(
            'show_close_button',
            __('Показвай X бутон', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_stores',
            array(
                'field' => 'show_close_button',
                'description' => __('Позволявай скриване на stores панела чрез X бутон', 'parfume-catalog')
            )
        );

        // COMMENTS НАСТРОЙКИ
        add_settings_field(
            'enable_comments',
            __('Разреши коментари', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_comments',
            array(
                'field' => 'enable_comments',
                'description' => __('Разреши коментари и рейтинги за парфюмите', 'parfume-catalog')
            )
        );

        add_settings_field(
            'comments_moderation',
            __('Модерация на коментари', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_comments',
            array(
                'field' => 'comments_moderation',
                'description' => __('Изисквай одобрение за всички нови коментари', 'parfume-catalog')
            )
        );

        add_settings_field(
            'enable_captcha',
            __('Разреши CAPTCHA', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_comments',
            array(
                'field' => 'enable_captcha',
                'description' => __('Показвай CAPTCHA за защита от спам', 'parfume-catalog')
            )
        );

        add_settings_field(
            'blocked_words',
            __('Блокирани думи', 'parfume-catalog'),
            array($this, 'textarea_field_callback'),
            'parfume_catalog_settings',
            'parfume_comments',
            array(
                'field' => 'blocked_words',
                'description' => __('Списък с блокирани думи (по една на ред). Коментари съдържащи тези думи ще бъдат блокирани.', 'parfume-catalog'),
                'rows' => 5
            )
        );

        // COMPARISON НАСТРОЙКИ
        add_settings_field(
            'enable_comparison',
            __('Разреши сравняване', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_comparison',
            array(
                'field' => 'enable_comparison',
                'description' => __('Разреши функционалността за сравняване на парфюми', 'parfume-catalog')
            )
        );

        add_settings_field(
            'max_comparison_items',
            __('Максимален брой за сравнение', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_comparison',
            array(
                'field' => 'max_comparison_items',
                'description' => __('Максимален брой парфюми които могат да се сравняват едновременно', 'parfume-catalog'),
                'min' => 2,
                'max' => 10,
                'default' => 4
            )
        );

        // BLOG НАСТРОЙКИ
        add_settings_field(
            'blog_posts_per_page',
            __('Blog постове на страница', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_blog',
            array(
                'field' => 'blog_posts_per_page',
                'description' => __('Брой blog постове за показване на архивната страница', 'parfume-catalog'),
                'min' => 1,
                'max' => 50,
                'default' => 10
            )
        );

        add_settings_field(
            'blog_excerpt_length',
            __('Дължина на извадка', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_blog',
            array(
                'field' => 'blog_excerpt_length',
                'description' => __('Брой думи в извадката на blog постовете', 'parfume-catalog'),
                'min' => 10,
                'max' => 100,
                'default' => 30
            )
        );

        add_settings_field(
            'blog_show_author',
            __('Показвай автор', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_blog',
            array(
                'field' => 'blog_show_author',
                'description' => __('Показвай информация за автора в blog постовете', 'parfume-catalog')
            )
        );

        add_settings_field(
            'blog_show_date',
            __('Показвай дата', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_blog',
            array(
                'field' => 'blog_show_date',
                'description' => __('Показвай дата на публикуване в blog постовете', 'parfume-catalog')
            )
        );
    }

    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Основни настройки на плъгина.', 'parfume-catalog') . '</p>';
    }

    public function urls_section_callback() {
        echo '<p>' . __('Конфигуриране на URL структурите за различните секции. <strong>След промяна трябва да обновите permalinks.</strong>', 'parfume-catalog') . '</p>';
        echo '<p><a href="' . admin_url('options-permalink.php') . '" class="button button-secondary">' . __('Обнови Permalinks', 'parfume-catalog') . '</a></p>';
    }

    public function single_section_callback() {
        echo '<p>' . __('Настройки за single страниците на парфюмите.', 'parfume-catalog') . '</p>';
    }

    public function seo_section_callback() {
        echo '<p>' . __('SEO настройки за по-добра видимост в търсачките.', 'parfume-catalog') . '</p>';
    }

    public function scraper_section_callback() {
        echo '<p>' . __('Настройки за автоматичното скрейпване на данни от магазините.', 'parfume-catalog') . '</p>';
    }

    public function stores_section_callback() {
        echo '<p>' . __('Настройки за визуализацията на магазините във фронтенда.', 'parfume-catalog') . '</p>';
    }

    public function comments_section_callback() {
        echo '<p>' . __('Настройки за системата за коментари и рейтинги.', 'parfume-catalog') . '</p>';
    }

    public function comparison_section_callback() {
        echo '<p>' . __('Настройки за функционалността за сравняване на парфюми.', 'parfume-catalog') . '</p>';
    }

    public function blog_section_callback() {
        echo '<p>' . __('Настройки за blog функционалността.', 'parfume-catalog') . '</p>';
    }

    public function import_export_section_callback() {
        echo '<p>' . __('Import и export на настройки и данни.', 'parfume-catalog') . '</p>';
        
        ?>
        <div class="import-export-tools">
            <div class="tool-section">
                <h4><?php _e('Import/Export настройки', 'parfume-catalog'); ?></h4>
                <p>
                    <button type="button" class="button" id="export-settings">
                        <?php _e('Export настройки', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button" id="import-settings">
                        <?php _e('Import настройки', 'parfume-catalog'); ?>
                    </button>
                    <input type="file" id="import-settings-file" accept=".json" style="display: none;">
                </p>
            </div>

            <div class="tool-section">
                <h4><?php _e('Import нотки', 'parfume-catalog'); ?></h4>
                <p><?php _e('Импортирайте ароматни нотки от JSON файл:', 'parfume-catalog'); ?></p>
                <textarea id="notes-json-input" rows="8" placeholder='[{"note": "Лавандула", "group": "ароматни"}, ...]' style="width: 100%; max-width: 600px;"></textarea>
                <br><br>
                <button type="button" class="button button-primary" id="import-notes">
                    <?php _e('Import нотки', 'parfume-catalog'); ?>
                </button>
                <div id="import-notes-result" style="margin-top: 10px;"></div>
            </div>

            <div class="tool-section">
                <h4><?php _e('Reset настройки', 'parfume-catalog'); ?></h4>
                <p><?php _e('Възстановява всички настройки до първоначалните стойности.', 'parfume-catalog'); ?></p>
                <button type="button" class="button button-secondary" id="reset-settings" 
                        onclick="return confirm('<?php _e('Сигурни ли сте, че искате да възстановите всички настройки? Това действие не може да бъде отменено.', 'parfume-catalog'); ?>')">
                    <?php _e('Reset настройки', 'parfume-catalog'); ?>
                </button>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Export settings
            $('#export-settings').on('click', function() {
                $.post(ajaxurl, {
                    action: 'parfume_export_settings',
                    nonce: '<?php echo wp_create_nonce('parfume_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data, null, 2));
                        var downloadAnchorNode = document.createElement('a');
                        downloadAnchorNode.setAttribute("href", dataStr);
                        downloadAnchorNode.setAttribute("download", "parfume-catalog-settings.json");
                        document.body.appendChild(downloadAnchorNode);
                        downloadAnchorNode.click();
                        downloadAnchorNode.remove();
                    }
                });
            });

            // Import settings
            $('#import-settings').on('click', function() {
                $('#import-settings-file').click();
            });

            $('#import-settings-file').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        try {
                            var settings = JSON.parse(e.target.result);
                            $.post(ajaxurl, {
                                action: 'parfume_import_settings',
                                nonce: '<?php echo wp_create_nonce('parfume_admin_nonce'); ?>',
                                settings: JSON.stringify(settings)
                            }, function(response) {
                                if (response.success) {
                                    alert('<?php _e('Настройките са импортирани успешно!', 'parfume-catalog'); ?>');
                                    location.reload();
                                } else {
                                    alert('<?php _e('Грешка при импортиране: ', 'parfume-catalog'); ?>' + response.data);
                                }
                            });
                        } catch (err) {
                            alert('<?php _e('Невалиден JSON файл!', 'parfume-catalog'); ?>');
                        }
                    };
                    reader.readAsText(file);
                }
            });

            // Import notes
            $('#import-notes').on('click', function() {
                var notesJson = $('#notes-json-input').val();
                if (!notesJson.trim()) {
                    alert('<?php _e('Моля въведете JSON данни за нотките!', 'parfume-catalog'); ?>');
                    return;
                }

                $.post(ajaxurl, {
                    action: 'parfume_import_notes',
                    nonce: '<?php echo wp_create_nonce('parfume_admin_nonce'); ?>',
                    notes_json: notesJson
                }, function(response) {
                    $('#import-notes-result').html(
                        '<div class="notice notice-' + (response.success ? 'success' : 'error') + '">' +
                        '<p>' + response.data + '</p>' +
                        '</div>'
                    );
                });
            });

            // Reset settings
            $('#reset-settings').on('click', function() {
                $.post(ajaxurl, {
                    action: 'parfume_reset_settings',
                    nonce: '<?php echo wp_create_nonce('parfume_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Настройките са възстановени успешно!', 'parfume-catalog'); ?>');
                        location.reload();
                    }
                });
            });
        });
        </script>

        <style>
        .import-export-tools { margin-top: 20px; }
        .tool-section { 
            background: white; 
            padding: 15px; 
            margin: 15px 0; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
        }
        .tool-section h4 { margin-top: 0; }
        </style>
        <?php
    }

    /**
     * Field callbacks
     */
    public function text_field_callback($args) {
        $options = get_option('parfume_catalog_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : (isset($args['default']) ? $args['default'] : '');
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        
        echo '<input type="text" id="' . esc_attr($field) . '" name="parfume_catalog_options[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="regular-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    public function number_field_callback($args) {
        $options = get_option('parfume_catalog_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : (isset($args['default']) ? $args['default'] : '');
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        
        echo '<input type="number" id="' . esc_attr($field) . '" name="parfume_catalog_options[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" class="small-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    public function checkbox_field_callback($args) {
        $options = get_option('parfume_catalog_options', array());
        $field = $args['field'];
        $checked = isset($options[$field]) ? $options[$field] : false;
        
        echo '<label for="' . esc_attr($field) . '">';
        echo '<input type="checkbox" id="' . esc_attr($field) . '" name="parfume_catalog_options[' . esc_attr($field) . ']" value="1" ' . checked(1, $checked, false) . ' />';
        
        if (isset($args['description'])) {
            echo ' ' . esc_html($args['description']);
        }
        echo '</label>';
    }

    public function select_field_callback($args) {
        $options = get_option('parfume_catalog_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : (isset($args['default']) ? $args['default'] : '');
        $select_options = isset($args['options']) ? $args['options'] : array();
        
        echo '<select id="' . esc_attr($field) . '" name="parfume_catalog_options[' . esc_attr($field) . ']">';
        foreach ($select_options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    public function textarea_field_callback($args) {
        $options = get_option('parfume_catalog_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $rows = isset($args['rows']) ? $args['rows'] : 3;
        
        echo '<textarea id="' . esc_attr($field) . '" name="parfume_catalog_options[' . esc_attr($field) . ']" rows="' . esc_attr($rows) . '" class="large-text">' . esc_textarea($value) . '</textarea>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Санитизация на опциите
     */
    public function sanitize_options($options) {
        $sanitized = array();
        
        if (!is_array($options)) {
            return $sanitized;
        }

        // Text fields
        $text_fields = array(
            'archive_slug', 'type_slug', 'vid_slug', 'marki_slug', 
            'season_slug', 'intensity_slug', 'notes_slug', 'blog_slug',
            'scraper_user_agent'
        );
        
        foreach ($text_fields as $field) {
            if (isset($options[$field])) {
                $sanitized[$field] = sanitize_text_field($options[$field]);
            }
        }

        // Number fields
        $number_fields = array(
            'posts_per_page', 'similar_parfumes_count', 'recently_viewed_count',
            'brand_parfumes_count', 'scraper_interval', 'scraper_batch_size',
            'scraper_timeout', 'mobile_panel_z_index', 'max_comparison_items',
            'blog_posts_per_page', 'blog_excerpt_length'
        );
        
        foreach ($number_fields as $field) {
            if (isset($options[$field])) {
                $sanitized[$field] = absint($options[$field]);
            }
        }

        // Checkbox fields
        $checkbox_fields = array(
            'enable_schema', 'enable_og_tags', 'enable_twitter_cards',
            'mobile_fixed_panel', 'show_close_button', 'enable_comments',
            'comments_moderation', 'enable_captcha', 'enable_comparison',
            'blog_show_author', 'blog_show_date'
        );
        
        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = isset($options[$field]) ? 1 : 0;
        }

        // Select fields
        if (isset($options['show_similar_columns'])) {
            $allowed_columns = array('2', '3', '4', '6');
            $sanitized['show_similar_columns'] = in_array($options['show_similar_columns'], $allowed_columns) ? $options['show_similar_columns'] : '4';
        }

        // Textarea fields
        if (isset($options['blocked_words'])) {
            $sanitized['blocked_words'] = sanitize_textarea_field($options['blocked_words']);
        }

        return $sanitized;
    }

    /**
     * Получаване на default опции
     */
    private function get_default_options() {
        return array(
            // General
            'archive_slug' => 'parfiumi',
            'posts_per_page' => 12,
            
            // URLs
            'type_slug' => 'parfiumi',
            'vid_slug' => 'parfiumi',
            'marki_slug' => 'parfiumi/marki',
            'season_slug' => 'parfiumi/season',
            'intensity_slug' => 'parfiumi/intensity',
            'notes_slug' => 'notes',
            'blog_slug' => 'blog',
            
            // Single page
            'similar_parfumes_count' => 4,
            'recently_viewed_count' => 4,
            'brand_parfumes_count' => 4,
            'show_similar_columns' => '4',
            
            // SEO
            'enable_schema' => 1,
            'enable_og_tags' => 1,
            'enable_twitter_cards' => 1,
            
            // Scraper
            'scraper_interval' => 12,
            'scraper_batch_size' => 10,
            'scraper_user_agent' => 'Mozilla/5.0 (compatible; ParfumeCatalogBot/1.0)',
            'scraper_timeout' => 30,
            
            // Stores
            'mobile_fixed_panel' => 1,
            'mobile_panel_z_index' => 1000,
            'show_close_button' => 1,
            
            // Comments
            'enable_comments' => 1,
            'comments_moderation' => 1,
            'enable_captcha' => 1,
            'blocked_words' => '',
            
            // Comparison
            'enable_comparison' => 1,
            'max_comparison_items' => 4,
            
            // Blog
            'blog_posts_per_page' => 10,
            'blog_excerpt_length' => 30,
            'blog_show_author' => 1,
            'blog_show_date' => 1
        );
    }

    /**
     * AJAX handlers
     */
    public function ajax_import_notes() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate достъп до тази функционалност.', 'parfume-catalog'));
        }

        $notes_json = sanitize_textarea_field($_POST['notes_json']);
        $notes_data = json_decode($notes_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Невалиден JSON формат.', 'parfume-catalog'));
        }

        if (!is_array($notes_data)) {
            wp_send_json_error(__('JSON данните трябва да са array.', 'parfume-catalog'));
        }

        $imported = 0;
        $errors = 0;

        foreach ($notes_data as $note_data) {
            if (!isset($note_data['note']) || !isset($note_data['group'])) {
                $errors++;
                continue;
            }

            $note_name = sanitize_text_field($note_data['note']);
            $note_group = sanitize_text_field($note_data['group']);

            // Проверяваме дали нотката вече съществува
            $existing_term = get_term_by('name', $note_name, 'parfume_notes');
            
            if (!$existing_term) {
                $result = wp_insert_term($note_name, 'parfume_notes');
                
                if (!is_wp_error($result)) {
                    $term_id = $result['term_id'];
                    update_term_meta($term_id, 'note_group', $note_group);
                    $imported++;
                } else {
                    $errors++;
                }
            } else {
                // Обновяваме групата ако е различна
                update_term_meta($existing_term->term_id, 'note_group', $note_group);
                $imported++;
            }
        }

        wp_send_json_success(sprintf(
            __('Импортирани %d нотки. Грешки: %d', 'parfume-catalog'),
            $imported,
            $errors
        ));
    }

    public function ajax_export_settings() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate достъп до тази функционалност.', 'parfume-catalog'));
        }

        $settings = array(
            'parfume_catalog_options' => get_option('parfume_catalog_options', array()),
            'parfume_catalog_stores' => get_option('parfume_catalog_stores', array()),
            'parfume_blog_settings' => get_option('parfume_blog_settings', array()),
            'export_date' => current_time('mysql'),
            'plugin_version' => PARFUME_CATALOG_VERSION
        );

        wp_send_json_success($settings);
    }

    public function ajax_import_settings() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate достъп до тази функционалност.', 'parfume-catalog'));
        }

        $settings_json = sanitize_textarea_field($_POST['settings']);
        $settings_data = json_decode($settings_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Невалиден JSON формат.', 'parfume-catalog'));
        }

        // Импортираме настройките
        if (isset($settings_data['parfume_catalog_options'])) {
            update_option('parfume_catalog_options', $settings_data['parfume_catalog_options']);
        }

        if (isset($settings_data['parfume_catalog_stores'])) {
            update_option('parfume_catalog_stores', $settings_data['parfume_catalog_stores']);
        }

        if (isset($settings_data['parfume_blog_settings'])) {
            update_option('parfume_blog_settings', $settings_data['parfume_blog_settings']);
        }

        wp_send_json_success(__('Настройките са импортирани успешно!', 'parfume-catalog'));
    }

    public function ajax_reset_settings() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Няmate достъп до тази функционалност.', 'parfume-catalog'));
        }

        // Възстановяваме default настройките
        update_option('parfume_catalog_options', $this->get_default_options());

        wp_send_json_success(__('Настройките са възстановени успешно!', 'parfume-catalog'));
    }

    /**
     * Получаване на опция с fallback
     */
    public static function get_option($key, $default = null) {
        $options = get_option('parfume_catalog_options', array());
        
        if (isset($options[$key])) {
            return $options[$key];
        }
        
        $defaults = array(
            'archive_slug' => 'parfiumi',
            'posts_per_page' => 12,
            'similar_parfumes_count' => 4,
            'recently_viewed_count' => 4,
            'brand_parfumes_count' => 4,
            'enable_schema' => 1,
            'enable_comparison' => 1,
            'enable_comments' => 1
        );
        
        return isset($defaults[$key]) ? $defaults[$key] : $default;
    }

    /**
     * Utility функции
     */
    public static function is_feature_enabled($feature) {
        return (bool) self::get_option($feature, false);
    }

    public static function get_url_slug($taxonomy) {
        $slug_map = array(
            'parfume_type' => 'type_slug',
            'parfume_vid' => 'vid_slug',
            'parfume_marki' => 'marki_slug',
            'parfume_season' => 'season_slug',
            'parfume_intensity' => 'intensity_slug',
            'parfume_notes' => 'notes_slug'
        );
        
        if (isset($slug_map[$taxonomy])) {
            return self::get_option($slug_map[$taxonomy], 'parfiumi');
        }
        
        return 'parfiumi';
    }
}