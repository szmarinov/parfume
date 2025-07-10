<?php
/**
 * Parfume Catalog Admin Class
 * 
 * Управлява администраторския панел на плъгина
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
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('parent_file', array($this, 'set_current_menu'));
    }

    /**
     * Добавяне на администраторско меню
     */
    public function add_admin_menu() {
        // Главно меню за плъгина
        add_menu_page(
            __('Parfume Catalog', 'parfume-catalog'),
            __('Parfume Catalog', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog',
            array($this, 'admin_page'),
            'dashicons-store',
            30
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
            __('Блог постове', 'parfume-catalog'),
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
     * Добавяне на секции за настройки
     */
    private function add_settings_sections() {
        // Основни настройки
        add_settings_section(
            'parfume_catalog_general',
            __('Основни настройки', 'parfume-catalog'),
            array($this, 'general_section_callback'),
            'parfume_catalog_settings'
        );

        // URL настройки
        add_settings_section(
            'parfume_catalog_urls',
            __('URL структури', 'parfume-catalog'),
            array($this, 'urls_section_callback'),
            'parfume_catalog_settings'
        );

        // Добавяне на полета
        $this->add_settings_fields();
    }

    /**
     * Добавяне на полета за настройки
     */
    private function add_settings_fields() {
        // Архивен slug
        add_settings_field(
            'archive_slug',
            __('Архивен URL', 'parfume-catalog'),
            array($this, 'archive_slug_field'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Типове slug
        add_settings_field(
            'type_slug',
            __('URL за типове', 'parfume-catalog'),
            array($this, 'type_slug_field'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Марки slug
        add_settings_field(
            'marki_slug',
            __('URL за марки', 'parfume-catalog'),
            array($this, 'marki_slug_field'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Сезони slug
        add_settings_field(
            'season_slug',
            __('URL за сезони', 'parfume-catalog'),
            array($this, 'season_slug_field'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Нотки slug
        add_settings_field(
            'notes_slug',
            __('URL за нотки', 'parfume-catalog'),
            array($this, 'notes_slug_field'),
            'parfume_catalog_settings',
            'parfume_catalog_urls'
        );

        // Брой подобни парфюми
        add_settings_field(
            'similar_parfumes_count',
            __('Брой подобни парфюми', 'parfume-catalog'),
            array($this, 'similar_parfumes_count_field'),
            'parfume_catalog_settings',
            'parfume_catalog_general'
        );

        // Брой наскоро разгледани
        add_settings_field(
            'recently_viewed_count',
            __('Брой наскоро разгледани', 'parfume-catalog'),
            array($this, 'recently_viewed_count_field'),
            'parfume_catalog_settings',
            'parfume_catalog_general'
        );

        // Брой парфюми от марка
        add_settings_field(
            'brand_parfumes_count',
            __('Брой парфюми от марка', 'parfume-catalog'),
            array($this, 'brand_parfumes_count_field'),
            'parfume_catalog_settings',
            'parfume_catalog_general'
        );
    }

    /**
     * Главна админ страница
     */
    public function admin_page() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="parfume-dashboard">
                <div class="dashboard-widgets">
                    <!-- Статистики -->
                    <div class="dashboard-widget">
                        <h2><?php _e('Статистики', 'parfume-catalog'); ?></h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($stats['parfumes_count']); ?></span>
                                <span class="stat-label"><?php _e('Парфюми', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($stats['stores_count']); ?></span>
                                <span class="stat-label"><?php _e('Магазини', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($stats['brands_count']); ?></span>
                                <span class="stat-label"><?php _e('Марки', 'parfume-catalog'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($stats['comments_count']); ?></span>
                                <span class="stat-label"><?php _e('Коментари', 'parfume-catalog'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Последни парфюми -->
                    <div class="dashboard-widget">
                        <h2><?php _e('Последни парфюми', 'parfume-catalog'); ?></h2>
                        <?php $this->render_recent_parfumes(); ?>
                    </div>

                    <!-- Бързи действия -->
                    <div class="dashboard-widget">
                        <h2><?php _e('Бързи действия', 'parfume-catalog'); ?></h2>
                        <div class="quick-actions">
                            <a href="<?php echo admin_url('post-new.php?post_type=parfumes'); ?>" class="button button-primary">
                                <?php _e('Добави парфюм', 'parfume-catalog'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=parfume-stores'); ?>" class="button">
                                <?php _e('Управление на магазини', 'parfume-catalog'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=parfume-catalog-settings'); ?>" class="button">
                                <?php _e('Настройки', 'parfume-catalog'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Системна информация -->
                    <div class="dashboard-widget">
                        <h2><?php _e('Системна информация', 'parfume-catalog'); ?></h2>
                        <div class="system-info">
                            <p><strong><?php _e('Версия на плъгина:', 'parfume-catalog'); ?></strong> <?php echo PARFUME_CATALOG_VERSION; ?></p>
                            <p><strong><?php _e('WordPress версия:', 'parfume-catalog'); ?></strong> <?php echo get_bloginfo('version'); ?></p>
                            <p><strong><?php _e('PHP версия:', 'parfume-catalog'); ?></strong> <?php echo PHP_VERSION; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Страница за категории
     */
    public function categories_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="parfume-categories">
                <div class="categories-grid">
                    <div class="category-section">
                        <h2><?php _e('Типове парфюми', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на типовете парфюми (Дамски, Мъжки, Унисекс и др.)', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_type&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на типове', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Видове аромат', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на видовете аромат (Тоалетна вода, Парфюмна вода и др.)', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_vid&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на видове', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Марки', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на марките на парфюми', 'parfume-catalog'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=parfume_marki&post_type=parfumes'); ?>" class="button button-primary">
                            <?php _e('Управление на марки', 'parfume-catalog'); ?>
                        </a>
                    </div>

                    <div class="category-section">
                        <h2><?php _e('Сезони', 'parfume-catalog'); ?></h2>
                        <p><?php _e('Управление на сезоните (Пролет, Лято, Есен, Зима)', 'parfume-catalog'); ?></p>
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
                do_settings_sections('parfume_catalog_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Страница за документация
     */
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="parfume-docs">
                <div class="docs-section">
                    <h2><?php _e('Шорткодове', 'parfume-catalog'); ?></h2>
                    <div class="shortcodes-list">
                        <div class="shortcode-item">
                            <code>[parfume_filters]</code>
                            <p><?php _e('Показва филтри за парфюми', 'parfume-catalog'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[parfume_comparison]</code>
                            <p><?php _e('Показва бутон за сравнение на парфюми', 'parfume-catalog'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[parfume_list category="damski" limit="8"]</code>
                            <p><?php _e('Показва списък с парфюми от определена категория', 'parfume-catalog'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[parfume_brands]</code>
                            <p><?php _e('Показва списък с марки парфюми', 'parfume-catalog'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[parfume_notes group="цветни"]</code>
                            <p><?php _e('Показва нотки от определена група', 'parfume-catalog'); ?></p>
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
        echo '<input type="text" name="parfume_catalog_options[archive_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL за архивната страница с парфюми (по подразбиране: parfiumi)', 'parfume-catalog') . '</p>';
    }

    public function type_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['type_slug']) ? $options['type_slug'] : 'parfiumi';
        echo '<input type="text" name="parfume_catalog_options[type_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL за типовете парфюми', 'parfume-catalog') . '</p>';
    }

    public function marki_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['marki_slug']) ? $options['marki_slug'] : 'parfiumi/marki';
        echo '<input type="text" name="parfume_catalog_options[marki_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL за марките парфюми', 'parfume-catalog') . '</p>';
    }

    public function season_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['season_slug']) ? $options['season_slug'] : 'parfiumi/season';
        echo '<input type="text" name="parfume_catalog_options[season_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL за сезоните', 'parfume-catalog') . '</p>';
    }

    public function notes_slug_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['notes_slug']) ? $options['notes_slug'] : 'notes';
        echo '<input type="text" name="parfume_catalog_options[notes_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL за нотките', 'parfume-catalog') . '</p>';
    }

    public function similar_parfumes_count_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['similar_parfumes_count']) ? $options['similar_parfumes_count'] : 4;
        echo '<input type="number" name="parfume_catalog_options[similar_parfumes_count]" value="' . esc_attr($value) . '" min="1" max="20" />';
        echo '<p class="description">' . __('Брой подобни парфюми, които да се показват', 'parfume-catalog') . '</p>';
    }

    public function recently_viewed_count_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['recently_viewed_count']) ? $options['recently_viewed_count'] : 4;
        echo '<input type="number" name="parfume_catalog_options[recently_viewed_count]" value="' . esc_attr($value) . '" min="1" max="20" />';
        echo '<p class="description">' . __('Брой наскоро разгледани парфюми', 'parfume-catalog') . '</p>';
    }

    public function brand_parfumes_count_field() {
        $options = get_option('parfume_catalog_options');
        $value = isset($options['brand_parfumes_count']) ? $options['brand_parfumes_count'] : 4;
        echo '<input type="number" name="parfume_catalog_options[brand_parfumes_count]" value="' . esc_attr($value) . '" min="1" max="20" />';
        echo '<p class="description">' . __('Брой парфюми от същата марка за показване', 'parfume-catalog') . '</p>';
    }

    /**
     * Проверка на настройки
     */
    public function sanitize_options($input) {
        $sanitized = array();

        if (isset($input['archive_slug'])) {
            $sanitized['archive_slug'] = sanitize_title($input['archive_slug']);
        }

        if (isset($input['type_slug'])) {
            $sanitized['type_slug'] = sanitize_title($input['type_slug']);
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

        if (isset($input['similar_parfumes_count'])) {
            $sanitized['similar_parfumes_count'] = max(1, min(20, absint($input['similar_parfumes_count'])));
        }

        if (isset($input['recently_viewed_count'])) {
            $sanitized['recently_viewed_count'] = max(1, min(20, absint($input['recently_viewed_count'])));
        }

        if (isset($input['brand_parfumes_count'])) {
            $sanitized['brand_parfumes_count'] = max(1, min(20, absint($input['brand_parfumes_count'])));
        }

        return $sanitized;
    }

    /**
     * Админ известия
     */
    public function admin_notices() {
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'parfume-catalog') !== false) {
            $this->check_system_requirements();
        }
    }

    /**
     * Проверка на системните изисквания
     */
    private function check_system_requirements() {
        $notices = array();

        // Проверка на PHP версия
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $notices[] = sprintf(
                __('Parfume Catalog изисква PHP версия 7.4 или по-нова. Текущата версия е %s.', 'parfume-catalog'),
                PHP_VERSION
            );
        }

        // Проверка на WordPress версия
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $notices[] = sprintf(
                __('Parfume Catalog изисква WordPress версия 5.0 или по-нова. Текущата версия е %s.', 'parfume-catalog'),
                get_bloginfo('version')
            );
        }

        // Показване на известия
        foreach ($notices as $notice) {
            echo '<div class="notice notice-error"><p>' . esc_html($notice) . '</p></div>';
        }
    }

    /**
     * Задаване на текущо меню
     */
    public function set_current_menu($parent_file) {
        global $current_screen;

        if ($current_screen->post_type == 'parfumes') {
            $parent_file = 'parfume-catalog';
        }

        if ($current_screen->post_type == 'parfume_blog') {
            $parent_file = 'parfume-catalog';
        }

        return $parent_file;
    }

    /**
     * Получаване на статистики за dashboard
     */
    private function get_dashboard_stats() {
        $stats = array();

        // Брой парфюми
        $parfumes_count = wp_count_posts('parfumes');
        $stats['parfumes_count'] = $parfumes_count->publish;

        // Брой магазини
        $stores = get_option('parfume_catalog_stores', array());
        $stats['stores_count'] = count($stores);

        // Брой марки
        $brands_count = wp_count_terms(array(
            'taxonomy' => 'parfume_marki',
            'hide_empty' => false
        ));
        $stats['brands_count'] = is_wp_error($brands_count) ? 0 : $brands_count;

        // Брой коментари
        global $wpdb;
        $comments_table = $wpdb->prefix . 'parfume_comments';
        $comments_count = $wpdb->get_var("SELECT COUNT(*) FROM $comments_table WHERE status = 'approved'");
        $stats['comments_count'] = intval($comments_count);

        return $stats;
    }

    /**
     * Показване на последни парфюми
     */
    private function render_recent_parfumes() {
        $recent_parfumes = get_posts(array(
            'post_type' => 'parfumes',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if (empty($recent_parfumes)) {
            echo '<p>' . __('Все още няма добавени парфюми.', 'parfume-catalog') . '</p>';
            return;
        }

        echo '<ul class="recent-parfumes-list">';
        foreach ($recent_parfumes as $parfume) {
            echo '<li>';
            echo '<a href="' . get_edit_post_link($parfume->ID) . '">' . esc_html($parfume->post_title) . '</a>';
            echo '<span class="post-date">' . get_the_date('d.m.Y', $parfume) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
}