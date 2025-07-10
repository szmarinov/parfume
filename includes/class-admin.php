<?php
/**
 * Parfume Catalog Admin Class
 * 
 * Handles all admin functionality including menus, settings, and admin pages
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
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_filter('plugin_action_links_' . PARFUME_CATALOG_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
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
            array($this, 'dashboard_page'),
            'dashicons-store',
            26
        );

        // Подменю "Табло"
        add_submenu_page(
            'parfume-catalog',
            __('Табло', 'parfume-catalog'),
            __('Табло', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog',
            array($this, 'dashboard_page')
        );

        // Подменю "Всички парфюми"
        add_submenu_page(
            'parfume-catalog',
            __('Всички парфюми', 'parfume-catalog'),
            __('Всички парфюми', 'parfume-catalog'),
            'edit_posts',
            'edit.php?post_type=parfumes'
        );

        // Подменю "Добави парфюм"
        add_submenu_page(
            'parfume-catalog',
            __('Добави парфюм', 'parfume-catalog'),
            __('Добави парфюм', 'parfume-catalog'),
            'edit_posts',
            'post-new.php?post_type=parfumes'
        );

        // Подменю "Категории"
        add_submenu_page(
            'parfume-catalog',
            __('Категории', 'parfume-catalog'),
            __('Категории', 'parfume-catalog'),
            'manage_categories',
            'parfume-catalog-categories',
            array($this, 'categories_page')
        );

        // Подменю "Блог"
        add_submenu_page(
            'parfume-catalog',
            __('Блог', 'parfume-catalog'),
            __('Блог', 'parfume-catalog'),
            'edit_posts',
            'edit.php?post_type=parfume_blog'
        );

        // Подменю "Добави блог пост"
        add_submenu_page(
            'parfume-catalog',
            __('Добави блог пост', 'parfume-catalog'),
            __('Добави блог', 'parfume-catalog'),
            'edit_posts',
            'post-new.php?post_type=parfume_blog'
        );

        // Подменю "Магазини"
        add_submenu_page(
            'parfume-catalog',
            __('Магазини', 'parfume-catalog'),
            __('Магазини', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-stores',
            array($this, 'stores_page')
        );

        // Подменю "Scraper Settings"
        add_submenu_page(
            'parfume-catalog',
            __('Scraper Settings', 'parfume-catalog'),
            __('Scraper Settings', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-scraper',
            array($this, 'scraper_settings_page')
        );

        // Подменю "Сравнения"
        add_submenu_page(
            'parfume-catalog',
            __('Сравнения', 'parfume-catalog'),
            __('Сравнения', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-comparison',
            array($this, 'comparison_page')
        );

        // Подменю "Коментари"
        add_submenu_page(
            'parfume-catalog',
            __('Коментари', 'parfume-catalog'),
            __('Коментари', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-comments',
            array($this, 'comments_page')
        );

        // Подменю "Настройки"
        add_submenu_page(
            'parfume-catalog',
            __('Настройки', 'parfume-catalog'),
            __('Настройки', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-settings',
            array($this, 'settings_page')
        );

        // Подменю "Документация"
        add_submenu_page(
            'parfume-catalog',
            __('Документация', 'parfume-catalog'),
            __('Документация', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-docs',
            array($this, 'documentation_page')
        );
    }

    /**
     * Инициализация на админ панела
     */
    public function admin_init() {
        // Регистриране на настройки
        register_setting('parfume_catalog_settings', 'parfume_catalog_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));

        // Добавяне на секции и полета
        $this->add_settings_sections();
    }

    /**
     * Добавяне на settings секции
     */
    private function add_settings_sections() {
        // Основни настройки
        add_settings_section(
            'parfume_catalog_general',
            __('Основни настройки', 'parfume-catalog'),
            array($this, 'general_section_callback'),
            'parfume-catalog-settings'
        );

        // URL структури
        add_settings_section(
            'parfume_catalog_urls',
            __('URL структури', 'parfume-catalog'),
            array($this, 'urls_section_callback'),
            'parfume-catalog-settings'
        );

        // Полета за основни настройки
        add_settings_field(
            'archive_slug',
            __('Архив slug', 'parfume-catalog'),
            array($this, 'archive_slug_field'),
            'parfume-catalog-settings',
            'parfume_catalog_general'
        );

        add_settings_field(
            'type_slug',
            __('Тип slug', 'parfume-catalog'),
            array($this, 'type_slug_field'),
            'parfume-catalog-settings',
            'parfume_catalog_urls'
        );

        add_settings_field(
            'brand_slug',
            __('Марки slug', 'parfume-catalog'),
            array($this, 'brand_slug_field'),
            'parfume-catalog-settings',
            'parfume_catalog_urls'
        );

        add_settings_field(
            'season_slug',
            __('Сезон slug', 'parfume-catalog'),
            array($this, 'season_slug_field'),
            'parfume-catalog-settings',
            'parfume_catalog_urls'
        );

        add_settings_field(
            'notes_slug',
            __('Нотки slug', 'parfume-catalog'),
            array($this, 'notes_slug_field'),
            'parfume-catalog-settings',
            'parfume_catalog_urls'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Зареждане само на админ страници на плъгина
        if (strpos($hook, 'parfume-catalog') === false && 
            !in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Основни admin стилове
        wp_enqueue_style(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PARFUME_CATALOG_VERSION
        );

        // Основни admin скриптове
        wp_enqueue_script(
            'parfume-catalog-admin',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            PARFUME_CATALOG_VERSION,
            true
        );

        // Локализация
        wp_localize_script('parfume-catalog-admin', 'parfume_catalog_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_catalog_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете това?', 'parfume-catalog'),
                'processing' => __('Обработка...', 'parfume-catalog'),
                'error' => __('Възникна грешка', 'parfume-catalog'),
                'success' => __('Успешно!', 'parfume-catalog')
            )
        ));
    }

    /**
     * Показване на админ известия
     */
    public function display_admin_notices() {
        // Проверка за пропуснати зависимости
        if (!function_exists('wp_enqueue_scripts')) {
            echo '<div class="notice notice-error"><p>';
            echo __('Parfume Catalog изисква WordPress 5.0 или по-нова версия.', 'parfume-catalog');
            echo '</p></div>';
        }

        // Проверка за flush rewrite rules
        if (get_option('parfume_catalog_flush_rewrite_rules', false)) {
            echo '<div class="notice notice-warning"><p>';
            echo __('Моля, посетете <a href="' . admin_url('options-permalink.php') . '">Настройки > Постоянни връзки</a> за да обновите URL структурите.', 'parfume-catalog');
            echo '</p></div>';
        }
    }

    /**
     * Добавяне на plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=parfume-catalog-settings') . '">' . __('Настройки', 'parfume-catalog') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Табло страница
     */
    public function dashboard_page() {
        ?>
        <div class="wrap parfume-catalog-dashboard">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="parfume-dashboard-widgets">
                <div class="dashboard-widget">
                    <h2><?php _e('Статистики', 'parfume-catalog'); ?></h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo wp_count_posts('parfumes')->publish; ?></div>
                            <div class="stat-label"><?php _e('Парфюми', 'parfume-catalog'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo wp_count_terms('parfume_marki'); ?></div>
                            <div class="stat-label"><?php _e('Марки', 'parfume-catalog'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo wp_count_terms('parfume_notes'); ?></div>
                            <div class="stat-label"><?php _e('Нотки', 'parfume-catalog'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-widget">
                    <h2><?php _e('Бързи действия', 'parfume-catalog'); ?></h2>
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Добави парфюм', 'parfume-catalog'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_marki&post_type=parfumes'); ?>" class="button">
                            <?php _e('Управление на марки', 'parfume-catalog'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=parfume-catalog-settings'); ?>" class="button">
                            <?php _e('Настройки', 'parfume-catalog'); ?>
                        </a>
                    </div>
                </div>

                <div class="dashboard-widget">
                    <h2><?php _e('Последни парфюми', 'parfume-catalog'); ?></h2>
                    <?php
                    $recent_parfumes = get_posts(array(
                        'post_type' => 'parfumes',
                        'posts_per_page' => 5,
                        'post_status' => 'publish'
                    ));

                    if ($recent_parfumes) {
                        echo '<ul class="recent-items">';
                        foreach ($recent_parfumes as $parfume) {
                            echo '<li>';
                            echo '<a href="' . get_edit_post_link($parfume->ID) . '">' . esc_html($parfume->post_title) . '</a>';
                            echo ' <span class="date">(' . get_the_date('d.m.Y', $parfume->ID) . ')</span>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>' . __('Няма парфюми за показване.', 'parfume-catalog') . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Категории страница
     */
    public function categories_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="parfume-categories-grid">
                <div class="category-sections">
                    <div class="category-section">
                        <h2><?php _e('Типове', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на типовете парфюми (Дамски, Мъжки, Унисекс и т.н.)', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_type&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на типове', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Вид аромат', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на видовете аромати (Тоалетна вода, Парфюмна вода и т.н.)', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_vid&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на видове аромати', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Марки', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на марките парфюми', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_marki&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на марки', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Сезони', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на сезоните за парфюми', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_season&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на сезони', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Интензивност', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на интензивността на парфюмите', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_intensity&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на интензивност', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Нотки', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на ароматните нотки', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_notes&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на нотки', 'parfume-catalog'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Магазини страница
     */
    public function stores_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Управление на магазините се извършва чрез специализирания модул.', 'parfume-catalog'); ?></p>
            <p><em><?php _e('Тази функционалност ще бъде достъпна след завършването на stores модула.', 'parfume-catalog'); ?></em></p>
        </div>
        <?php
    }

    /**
     * Scraper settings страница
     */
    public function scraper_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Настройки за Product Scraper модула.', 'parfume-catalog'); ?></p>
            <p><em><?php _e('Тази функционалност ще бъде достъпна след завършването на scraper модула.', 'parfume-catalog'); ?></em></p>
        </div>
        <?php
    }

    /**
     * Сравнения страница
     */
    public function comparison_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Настройки за функционалността за сравняване на парфюми.', 'parfume-catalog'); ?></p>
            <p><em><?php _e('Тази функционалност ще бъде достъпна след завършването на comparison модула.', 'parfume-catalog'); ?></em></p>
        </div>
        <?php
    }

    /**
     * Коментари страница
     */
    public function comments_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Управление на коментарите и ревютата на парфюмите.', 'parfume-catalog'); ?></p>
            <p><em><?php _e('Тази функционалност ще бъде достъпна след завършването на comments модула.', 'parfume-catalog'); ?></em></p>
        </div>
        <?php
    }

    /**
     * Страница за настройки
     */
    public function settings_page() {
        if (isset($_GET['settings-updated'])) {
            flush_rewrite_rules();
            add_settings_error('parfume_catalog_messages', 'parfume_catalog_message', __('Настройките са запазени', 'parfume-catalog'), 'updated');
        }

        settings_errors('parfume_catalog_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_catalog_settings');
                do_settings_sections('parfume-catalog-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Документация страница
     */
    public function documentation_page() {
        ?>
        <div class="wrap parfume-catalog-docs">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="docs-content">
                <div class="docs-section">
                    <h2><?php _e('Въведение', 'parfume-catalog'); ?></h2>
                    <p><?php _e('Parfume Catalog е пълнофункционален плъгин за управление на каталог с парфюми. Той предоставя възможности за добавяне, категоризиране и управление на парфюми с богата функционалност.', 'parfume-catalog'); ?></p>
                </div>

                <div class="docs-section">
                    <h2><?php _e('Основни функции', 'parfume-catalog'); ?></h2>
                    <ul>
                        <li><?php _e('Управление на парфюми с детайлна информация', 'parfume-catalog'); ?></li>
                        <li><?php _e('Категоризиране по типове, марки, сезони и интензивност', 'parfume-catalog'); ?></li>
                        <li><?php _e('Система за ароматни нотки с групиране', 'parfume-catalog'); ?></li>
                        <li><?php _e('Интеграция с магазини и автоматично обновяване на цени', 'parfume-catalog'); ?></li>
                        <li><?php _e('Система за сравняване на парфюми', 'parfume-catalog'); ?></li>
                        <li><?php _e('Коментари и рейтинги без регистрация', 'parfume-catalog'); ?></li>
                        <li><?php _e('SEO оптимизация и schema.org markup', 'parfume-catalog'); ?></li>
                    </ul>
                </div>

                <div class="docs-section">
                    <h2><?php _e('Шорткодове', 'parfume-catalog'); ?></h2>
                    <div class="shortcodes-grid">
                        <div class="shortcode-item">
                            <code>[parfume_list limit="12" brand="chanel"]</code>
                            <p><?php _e('Показва списък с парфюми с опции за лимит и филтър по марка', 'parfume-catalog'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[parfume_brands]</code>
                            <p><?php _e('Показва списък с марки парфюми', 'parfume-catalog'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[parfume_notes group="цветни"]</code>
                            <p><?php _e('Показва нотки от определена група', 'parfume-catalog'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[parfume_comparison]</code>
                            <p><?php _e('Показва widget за сравняване на парфюми', 'parfume-catalog'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="docs-section">
                    <h2><?php _e('Начални стъпки', 'parfume-catalog'); ?></h2>
                    <ol>
                        <li><?php _e('Конфигурирайте основните настройки от секция "Настройки"', 'parfume-catalog'); ?></li>
                        <li><?php _e('Добавете магазини от секция "Магазини"', 'parfume-catalog'); ?></li>
                        <li><?php _e('Създайте необходимите категории (типове, марки, нотки)', 'parfume-catalog'); ?></li>
                        <li><?php _e('Добавете първите парфюми', 'parfume-catalog'); ?></li>
                        <li><?php _e('Конфигурирайте скрейпъра за автоматично обновяване на цени', 'parfume-catalog'); ?></li>
                    </ol>
                </div>

                <div class="docs-section">
                    <h2><?php _e('Поддръжка', 'parfume-catalog'); ?></h2>
                    <p><?php _e('За въпроси и поддръжка се свържете с разработчика на плъгина.', 'parfume-catalog'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Callback функции за настройки секции
     */
    public function general_section_callback() {
        echo '<p>' . __('Основни настройки за функционирането на плъгина.', 'parfume-catalog') . '</p>';
    }

    public function urls_section_callback() {
        echo '<p>' . __('Настройки за URL структурите. След промяна ще е необходимо да посетите Настройки > Постоянни връзки.', 'parfume-catalog') . '</p>';
    }

    /**
     * Полета за настройки
     */
    public function archive_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['archive_slug']) ? $options['archive_slug'] : 'parfiumi';
        $archive_url = home_url($value);
        
        echo '<input type="text" name="parfume_catalog_options[archive_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo ' <a href="' . esc_url($archive_url) . '" target="_blank" class="button button-small">' . __('Виж архива', 'parfume-catalog') . '</a>';
        echo '<p class="description">' . __('URL за архивната страница с парфюми (по подразбиране: parfiumi)', 'parfume-catalog') . '</p>';
    }

    public function type_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['type_slug']) ? $options['type_slug'] : 'parfiumi';
        
        echo '<input type="text" name="parfume_catalog_options[type_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL база за типовете парфюми (по подразбиране: parfiumi)', 'parfume-catalog') . '</p>';
    }

    public function brand_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['brand_slug']) ? $options['brand_slug'] : 'parfiumi/marki';
        
        echo '<input type="text" name="parfume_catalog_options[brand_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL база за марките парфюми (по подразбиране: parfiumi/marki)', 'parfume-catalog') . '</p>';
    }

    public function season_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['season_slug']) ? $options['season_slug'] : 'parfiumi/season';
        
        echo '<input type="text" name="parfume_catalog_options[season_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL база за сезоните (по подразбиране: parfiumi/season)', 'parfume-catalog') . '</p>';
    }

    public function notes_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['notes_slug']) ? $options['notes_slug'] : 'notes';
        
        echo '<input type="text" name="parfume_catalog_options[notes_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL база за нотките (по подразбиране: notes)', 'parfume-catalog') . '</p>';
    }

    /**
     * Sanitize настройки
     */
    public function sanitize_options($options) {
        $sanitized = array();
        
        if (isset($options['archive_slug'])) {
            $sanitized['archive_slug'] = sanitize_title($options['archive_slug']);
        }
        
        if (isset($options['type_slug'])) {
            $sanitized['type_slug'] = sanitize_title($options['type_slug']);
        }
        
        if (isset($options['brand_slug'])) {
            $sanitized['brand_slug'] = sanitize_title($options['brand_slug']);
        }
        
        if (isset($options['season_slug'])) {
            $sanitized['season_slug'] = sanitize_title($options['season_slug']);
        }
        
        if (isset($options['notes_slug'])) {
            $sanitized['notes_slug'] = sanitize_title($options['notes_slug']);
        }
        
        // Задаване на флаг за flush rewrite rules
        update_option('parfume_catalog_flush_rewrite_rules', true);
        
        return $sanitized;
    }
}