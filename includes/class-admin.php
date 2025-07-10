<?php
/**
 * Parfume Catalog Admin Class
 * 
 * Управлява административния панел и главните настройки на плъгина
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('plugin_action_links_' . PARFUME_CATALOG_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }

    /**
     * Добавяне на админ меню
     */
    public function add_admin_menu() {
        // Главно меню
        add_menu_page(
            __('Parfume Catalog', 'parfume-catalog'),
            __('Parfume Catalog', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog',
            array($this, 'admin_page'),
            'dashicons-store',
            30
        );

        // Подменюта
        add_submenu_page(
            'parfume-catalog',
            __('Настройки', 'parfume-catalog'),
            __('Настройки', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'parfume-catalog',
            __('Парфюми', 'parfume-catalog'),
            __('Парфюми', 'parfume-catalog'),
            'edit_posts',
            'edit.php?post_type=parfumes'
        );

        add_submenu_page(
            'parfume-catalog',
            __('Добави парфюм', 'parfume-catalog'),
            __('Добави парфюм', 'parfume-catalog'),
            'edit_posts',
            'post-new.php?post_type=parfumes'
        );

        add_submenu_page(
            'parfume-catalog',
            __('Stores', 'parfume-catalog'),
            __('Stores', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-stores',
            array($this, 'stores_page')
        );

        add_submenu_page(
            'parfume-catalog',
            __('Product Scraper', 'parfume-catalog'),
            __('Product Scraper', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-scraper',
            array($this, 'scraper_page')
        );

        add_submenu_page(
            'parfume-catalog',
            __('Comparison', 'parfume-catalog'),
            __('Comparison', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-comparison',
            array($this, 'comparison_page')
        );

        add_submenu_page(
            'parfume-catalog',
            __('Comments', 'parfume-catalog'),
            __('Comments', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-comments',
            array($this, 'comments_page')
        );

        add_submenu_page(
            'parfume-catalog',
            __('Blog', 'parfume-catalog'),
            __('Blog', 'parfume-catalog'),
            'edit_posts',
            'edit.php?post_type=parfume_blog'
        );

        add_submenu_page(
            'parfume-catalog',
            __('Add Blog', 'parfume-catalog'),
            __('Add Blog', 'parfume-catalog'),
            'edit_posts',
            'post-new.php?post_type=parfume_blog'
        );

        add_submenu_page(
            'parfume-catalog',
            __('Документация', 'parfume-catalog'),
            __('Документация', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-docs',
            array($this, 'docs_page')
        );
    }

    /**
     * Регистриране на настройките
     */
    public function register_settings() {
        // Група за основни настройки
        register_setting('parfume_catalog_settings', 'parfume_catalog_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));

        // Секция за URL структури
        add_settings_section(
            'parfume_catalog_urls',
            __('URL Структури', 'parfume-catalog'),
            array($this, 'urls_section_callback'),
            'parfume_catalog_settings'
        );

        // Поле за архивен slug
        add_settings_field(
            'archive_slug',
            __('Архивен URL (/parfiumi/)', 'parfume-catalog'),
            array($this, 'archive_slug_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Поле за тип URL
        add_settings_field(
            'type_slug',
            __('Тип URL структура', 'parfume-catalog'),
            array($this, 'type_slug_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Поле за вид аромат URL
        add_settings_field(
            'vid_slug',
            __('Вид аромат URL структура', 'parfume-catalog'),
            array($this, 'vid_slug_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Поле за марка URL
        add_settings_field(
            'marki_slug',
            __('Марка URL структура', 'parfume-catalog'),
            array($this, 'marki_slug_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Поле за сезон URL
        add_settings_field(
            'season_slug',
            __('Сезон URL структура', 'parfume-catalog'),
            array($this, 'season_slug_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Поле за нотки URL
        add_settings_field(
            'notes_slug',
            __('Нотки URL структура', 'parfume-catalog'),
            array($this, 'notes_slug_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Секция за общи настройки
        add_settings_section(
            'parfume_catalog_general',
            __('Общи настройки', 'parfume-catalog'),
            array($this, 'general_section_callback'),
            'parfume_catalog_settings'
        );

        // Поле за брой подобни парфюми
        add_settings_field(
            'related_count',
            __('Брой подобни парфюми', 'parfume-catalog'),
            array($this, 'related_count_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_general'
        );

        // Поле за брой наскоро разгледани
        add_settings_field(
            'recent_count',
            __('Брой наскоро разгледани', 'parfume-catalog'),
            array($this, 'recent_count_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_general'
        );

        // Поле за брой парфюми от марка
        add_settings_field(
            'brand_count',
            __('Брой парфюми от марка', 'parfume-catalog'),
            array($this, 'brand_count_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_general'
        );

        // Секция за мобилни настройки
        add_settings_section(
            'parfume_catalog_mobile',
            __('Мобилни настройки', 'parfume-catalog'),
            array($this, 'mobile_section_callback'),
            'parfume_catalog_settings'
        );

        // Поле за фиксиран панел
        add_settings_field(
            'mobile_fixed_panel',
            __('Фиксиран панел на мобилни', 'parfume-catalog'),
            array($this, 'mobile_fixed_panel_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_mobile'
        );

        // Поле за бутон X
        add_settings_field(
            'mobile_show_x',
            __('Показвай бутон "X"', 'parfume-catalog'),
            array($this, 'mobile_show_x_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_mobile'
        );

        // Поле за Z-index
        add_settings_field(
            'mobile_z_index',
            __('Z-index за фиксиран панел', 'parfume-catalog'),
            array($this, 'mobile_z_index_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_mobile'
        );

        // Поле за вертикален offset
        add_settings_field(
            'mobile_offset',
            __('Вертикален offset (px)', 'parfume-catalog'),
            array($this, 'mobile_offset_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_mobile'
        );
    }

    /**
     * Главна админ страница
     */
    public function admin_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'parfume_catalog_messages',
                'parfume_catalog_message',
                __('Настройките са запазени успешно.', 'parfume-catalog'),
                'updated'
            );
        }

        settings_errors('parfume_catalog_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="parfume-catalog-admin-header">
                <h2><?php _e('Добре дошли в Parfume Catalog', 'parfume-catalog'); ?></h2>
                <p><?php _e('Управлявайте вашия каталог с парфюми, магазини, настройки и още много.', 'parfume-catalog'); ?></p>
            </div>

            <div class="parfume-catalog-admin-content">
                <div class="parfume-catalog-quick-stats">
                    <div class="quick-stat">
                        <h3><?php echo $this->get_parfumes_count(); ?></h3>
                        <p><?php _e('Парфюми', 'parfume-catalog'); ?></p>
                    </div>
                    <div class="quick-stat">
                        <h3><?php echo $this->get_stores_count(); ?></h3>
                        <p><?php _e('Магазини', 'parfume-catalog'); ?></p>
                    </div>
                    <div class="quick-stat">
                        <h3><?php echo $this->get_pending_comments_count(); ?></h3>
                        <p><?php _e('Чакащи ревюта', 'parfume-catalog'); ?></p>
                    </div>
                    <div class="quick-stat">
                        <h3><?php echo $this->get_scraper_data_count(); ?></h3>
                        <p><?php _e('Скрейпнати данни', 'parfume-catalog'); ?></p>
                    </div>
                </div>

                <form action="options.php" method="post">
                    <?php
                    settings_fields('parfume_catalog_settings');
                    do_settings_sections('parfume_catalog_settings');
                    submit_button(__('Запази настройки', 'parfume-catalog'));
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Stores страница
     */
    public function stores_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Stores', 'parfume-catalog'); ?></h1>
            <p><?php _e('Управление на магазини ще бъде добавено в модула class-stores.php', 'parfume-catalog'); ?></p>
        </div>
        <?php
    }

    /**
     * Scraper страница
     */
    public function scraper_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Product Scraper', 'parfume-catalog'); ?></h1>
            <p><?php _e('Настройки и мониторинг на скрейпъра ще бъдат добавени в модула class-scraper.php', 'parfume-catalog'); ?></p>
        </div>
        <?php
    }

    /**
     * Comparison страница
     */
    public function comparison_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Comparison', 'parfume-catalog'); ?></h1>
            <p><?php _e('Настройки за сравняване ще бъдат добавени в модула class-comparison.php', 'parfume-catalog'); ?></p>
        </div>
        <?php
    }

    /**
     * Comments страница
     */
    public function comments_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Comments', 'parfume-catalog'); ?></h1>
        <p><?php _e('Управление на коментари ще бъде добавено в модула class-comments.php', 'parfume-catalog'); ?></p>
        </div>
        <?php
    }

    /**
     * Документация страница
     */
    public function docs_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Документация', 'parfume-catalog'); ?></h1>
            
            <div class="parfume-catalog-docs">
                <h2><?php _e('Шорткодове', 'parfume-catalog'); ?></h2>
                <div class="shortcode-section">
                    <h3><?php _e('Основни шорткодове', 'parfume-catalog'); ?></h3>
                    <ul>
                        <li><code>[parfume_list]</code> - <?php _e('Показва списък с парфюми', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_filters]</code> - <?php _e('Показва филтри за парфюми', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_search]</code> - <?php _e('Показва търсачка за парфюми', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_comparison]</code> - <?php _e('Показва бутон за сравняване', 'parfume-catalog'); ?></li>
                    </ul>
                </div>

                <div class="shortcode-section">
                    <h3><?php _e('Филтри по таксономии', 'parfume-catalog'); ?></h3>
                    <ul>
                        <li><code>[parfume_filter_type]</code> - <?php _e('Филтър по тип', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_filter_marki]</code> - <?php _e('Филтър по марка', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_filter_vid]</code> - <?php _e('Филтър по вид аромат', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_filter_season]</code> - <?php _e('Филтър по сезон', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_filter_intensity]</code> - <?php _e('Филтър по интензивност', 'parfume-catalog'); ?></li>
                        <li><code>[parfume_filter_notes]</code> - <?php _e('Филтър по нотки', 'parfume-catalog'); ?></li>
                    </ul>
                </div>

                <h2><?php _e('URL Структури', 'parfume-catalog'); ?></h2>
                <div class="url-section">
                    <ul>
                        <li><?php _e('Архив парфюми:', 'parfume-catalog'); ?> <code>/parfiumi/</code></li>
                        <li><?php _e('Тип парфюми:', 'parfume-catalog'); ?> <code>/parfiumi/damski/</code></li>
                        <li><?php _e('Марки:', 'parfume-catalog'); ?> <code>/parfiumi/marki/chanel/</code></li>
                        <li><?php _e('Сезон:', 'parfume-catalog'); ?> <code>/parfiumi/season/lqto/</code></li>
                        <li><?php _e('Нотки:', 'parfume-catalog'); ?> <code>/notes/roza/</code></li>
                    </ul>
                </div>

                <h2><?php _e('Функционалности', 'parfume-catalog'); ?></h2>
                <div class="features-section">
                    <ul>
                        <li><?php _e('Custom Post Type "Parfumes" с пълна SEO поддръжка', 'parfume-catalog'); ?></li>
                        <li><?php _e('6 таксономии за категоризиране', 'parfume-catalog'); ?></li>
                        <li><?php _e('Product Scraper за автоматично извличане на цени', 'parfume-catalog'); ?></li>
                        <li><?php _e('Система за сравняване без регистрация', 'parfume-catalog'); ?></li>
                        <li><?php _e('Ревюта и рейтинги от гости', 'parfume-catalog'); ?></li>
                        <li><?php _e('Мобилен фиксиран панел за магазини', 'parfume-catalog'); ?></li>
                        <li><?php _e('Schema.org markup за SEO', 'parfume-catalog'); ?></li>
                        <li><?php _e('Интегрирана блог функционалност', 'parfume-catalog'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Callback функции за настройките
     */

    public function urls_section_callback() {
        echo '<p>' . __('Конфигурирайте URL структурите за различните части на каталога.', 'parfume-catalog') . '</p>';
    }

    public function general_section_callback() {
        echo '<p>' . __('Общи настройки за показване на съдържание.', 'parfume-catalog') . '</p>';
    }

    public function mobile_section_callback() {
        echo '<p>' . __('Настройки за мобилни устройства и фиксирания панел.', 'parfume-catalog') . '</p>';
    }

    public function archive_slug_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        echo '<input type="text" name="parfume_catalog_options[archive_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Слъгът за архивната страница (без / в началото и края)', 'parfume-catalog') . '</p>';
    }

    public function type_slug_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['type_slug']) ? $options['type_slug'] : 'parfiumi';
        echo '<input type="text" name="parfume_catalog_options[type_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('URL за типове парфюми (/parfiumi/{slug}/)', 'parfume-catalog') . '</p>';
    }

    public function vid_slug_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['vid_slug']) ? $options['vid_slug'] : 'parfiumi';
        echo '<input type="text" name="parfume_catalog_options[vid_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('URL за видове аромат (/parfiumi/{slug}/)', 'parfume-catalog') . '</p>';
    }

    public function marki_slug_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['marki_slug']) ? $options['marki_slug'] : 'marki';
        echo '<input type="text" name="parfume_catalog_options[marki_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('URL за марки (/parfiumi/marki/{slug}/)', 'parfume-catalog') . '</p>';
    }

    public function season_slug_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['season_slug']) ? $options['season_slug'] : 'season';
        echo '<input type="text" name="parfume_catalog_options[season_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('URL за сезони (/parfiumi/season/{slug}/)', 'parfume-catalog') . '</p>';
    }

    public function notes_slug_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['notes_slug']) ? $options['notes_slug'] : 'notes';
        echo '<input type="text" name="parfume_catalog_options[notes_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('URL за нотки (/notes/{slug}/)', 'parfume-catalog') . '</p>';
    }

    public function related_count_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['related_count']) ? $options['related_count'] : 4;
        echo '<input type="number" min="1" max="12" name="parfume_catalog_options[related_count]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Брой подобни парфюми за показване', 'parfume-catalog') . '</p>';
    }

    public function recent_count_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['recent_count']) ? $options['recent_count'] : 4;
        echo '<input type="number" min="1" max="12" name="parfume_catalog_options[recent_count]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Брой наскоро разгледани парфюми', 'parfume-catalog') . '</p>';
    }

    public function brand_count_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['brand_count']) ? $options['brand_count'] : 4;
        echo '<input type="number" min="1" max="12" name="parfume_catalog_options[brand_count]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Брой парфюми от същата марка', 'parfume-catalog') . '</p>';
    }

    public function mobile_fixed_panel_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['mobile_fixed_panel']) ? $options['mobile_fixed_panel'] : 1;
        echo '<input type="checkbox" name="parfume_catalog_options[mobile_fixed_panel]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>' . __('Показвай фиксиран магазин в долната част на мобилни устройства', 'parfume-catalog') . '</label>';
    }

    public function mobile_show_x_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['mobile_show_x']) ? $options['mobile_show_x'] : 0;
        echo '<input type="checkbox" name="parfume_catalog_options[mobile_show_x]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>' . __('Позволявай скриване на панела чрез бутон "X"', 'parfume-catalog') . '</label>';
    }

    public function mobile_z_index_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['mobile_z_index']) ? $options['mobile_z_index'] : 9999;
        echo '<input type="number" min="1" name="parfume_catalog_options[mobile_z_index]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Z-index за фиксирания панел (по-високо = отгоре)', 'parfume-catalog') . '</p>';
    }

    public function mobile_offset_callback() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['mobile_offset']) ? $options['mobile_offset'] : 0;
        echo '<input type="number" min="0" name="parfume_catalog_options[mobile_offset]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Вертикален отместване в пиксели (при други фиксирани елементи)', 'parfume-catalog') . '</p>';
    }

    /**
     * Санитизиране на опциите
     */
    public function sanitize_options($input) {
        $sanitized = array();

        if (isset($input['archive_slug'])) {
            $sanitized['archive_slug'] = sanitize_title($input['archive_slug']);
        }

        if (isset($input['type_slug'])) {
            $sanitized['type_slug'] = sanitize_title($input['type_slug']);
        }

        if (isset($input['vid_slug'])) {
            $sanitized['vid_slug'] = sanitize_title($input['vid_slug']);
        }

        if (isset($input['marki_slug'])) {
            $sanitized['marki_slug'] = sanitize_title($input['marki_slug']);
        }

        if (isset($input['season_slug'])) {
            $sanitized['season_slug'] = sanitize_title($input['season_slug']);
        }

        if (isset($input['notes_slug'])) {
            $sanitized['notes_slug'] = sanitize_title($input['notes_slug']);
        }

        if (isset($input['related_count'])) {
            $sanitized['related_count'] = absint($input['related_count']);
        }

        if (isset($input['recent_count'])) {
            $sanitized['recent_count'] = absint($input['recent_count']);
        }

        if (isset($input['brand_count'])) {
            $sanitized['brand_count'] = absint($input['brand_count']);
        }

        if (isset($input['mobile_fixed_panel'])) {
            $sanitized['mobile_fixed_panel'] = 1;
        } else {
            $sanitized['mobile_fixed_panel'] = 0;
        }

        if (isset($input['mobile_show_x'])) {
            $sanitized['mobile_show_x'] = 1;
        } else {
            $sanitized['mobile_show_x'] = 0;
        }

        if (isset($input['mobile_z_index'])) {
            $sanitized['mobile_z_index'] = absint($input['mobile_z_index']);
        }

        if (isset($input['mobile_offset'])) {
            $sanitized['mobile_offset'] = absint($input['mobile_offset']);
        }

        // Flush rewrite rules ако са променени URL структурите
        if (isset($input['archive_slug']) || isset($input['type_slug']) || 
            isset($input['vid_slug']) || isset($input['marki_slug']) || 
            isset($input['season_slug']) || isset($input['notes_slug'])) {
            flush_rewrite_rules();
        }

        return $sanitized;
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Проверка за flush rewrite rules
        if (get_transient('parfume_catalog_flush_rewrite_rules')) {
            delete_transient('parfume_catalog_flush_rewrite_rules');
            flush_rewrite_rules();
        }
    }

    /**
     * Добавяне на action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=parfume-catalog') . '">' . __('Настройки', 'parfume-catalog') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Helper функции за статистики
     */
    
    /**
     * Безопасно получаване на брой парфюми
     */
    private function get_parfumes_count() {
        $count_posts = wp_count_posts('parfumes');
        if (is_object($count_posts) && property_exists($count_posts, 'publish')) {
            return $count_posts->publish;
        }
        return 0;
    }
    
    private function get_stores_count() {
        $stores = get_option('parfume_stores', array());
        return is_array($stores) ? count($stores) : 0;
    }

    private function get_pending_comments_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'parfume_comments';
        
        // Проверка дали таблицата съществува
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return 0;
        }
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
        return $count ? $count : 0;
    }

    private function get_scraper_data_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'parfume_scraper_data';
        
        // Проверка дали таблицата съществува
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return 0;
        }
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        return $count ? $count : 0;
    }
}