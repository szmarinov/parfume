<?php
namespace Parfume_Reviews;

/**
 * Settings class - управлява административните настройки
 * ПОПРАВЕН - URL БУТОНИТЕ ВОДЯТ ДО ПРАВИЛНИТЕ АРХИВНИ СТРАНИЦИ
 * ДОБАВЕНИ - Stores и Product Scraper функционалности
 * FIXED - Премахната syntax грешка около USD
 */
class Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // НОВИ AJAX хендлъри за дебъг функциите
        add_action('wp_ajax_parfume_debug_taxonomy_url', array($this, 'ajax_debug_taxonomy_url'));
        add_action('wp_ajax_parfume_debug_check_urls', array($this, 'ajax_debug_check_urls'));
        add_action('wp_ajax_parfume_debug_check_templates', array($this, 'ajax_debug_check_templates'));
        add_action('wp_ajax_parfume_flush_rewrite_rules', array($this, 'ajax_flush_rewrite_rules'));
        add_action('wp_ajax_parfume_get_rewrite_rules', array($this, 'ajax_get_rewrite_rules'));
        
        // НОВИ AJAX хендлъри за Stores и Product Scraper
        add_action('wp_ajax_parfume_save_store', array($this, 'ajax_save_store'));
        add_action('wp_ajax_parfume_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_parfume_scrape_product', array($this, 'ajax_scrape_product'));
        add_action('wp_ajax_parfume_test_scraper', array($this, 'ajax_test_scraper'));
        add_action('wp_ajax_parfume_save_scraper_schema', array($this, 'ajax_save_scraper_schema'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Parfume Reviews Settings', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'parfume_page_parfume-reviews-settings') {
            return;
        }
        
        wp_enqueue_style('parfume-admin-settings', PARFUME_REVIEWS_PLUGIN_URL . 
            'assets/css/admin-settings.css', array(), PARFUME_REVIEWS_VERSION);
        wp_enqueue_script('parfume-settings-tabs', PARFUME_REVIEWS_PLUGIN_URL . 
            'assets/js/admin-settings.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
        
        // Enqueue media uploader for store logos
        wp_enqueue_media();
        
        // Localize script for AJAX calls
        wp_localize_script('parfume-settings-tabs', 'parfumeSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_settings_nonce'),
            'strings' => array(
                'confirm_delete' => __('Сигурни ли сте, че искате да изтриете този магазин?', 'parfume-reviews'),
                'scraping' => __('Скрейпване...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
                'success' => __('Успешно', 'parfume-reviews'),
            )
        ));
    }
    
    public function render_settings_page() {
        if (isset($_GET['settings-updated'])) {
            // Flush rewrite rules after saving URL settings
            flush_rewrite_rules();
            add_settings_error('parfume_reviews_messages', 'parfume_reviews_message', 
                __('Настройките са запазени.', 'parfume-reviews'), 'updated');
        }
        
        settings_errors('parfume_reviews_messages');
        ?>
        <div class="wrap parfume-settings">
            <h1><?php _e('Parfume Reviews настройки', 'parfume-reviews'); ?></h1>
            
            <!-- Tab navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('Общи', 'parfume-reviews'); ?></a>
                <a href="#url" class="nav-tab"><?php _e('URL настройки', 'parfume-reviews'); ?></a>
                <a href="#homepage" class="nav-tab"><?php _e('Начална страница', 'parfume-reviews'); ?></a>
                <a href="#mobile" class="nav-tab"><?php _e('Mobile настройки', 'parfume-reviews'); ?></a>
                <a href="#stores" class="nav-tab"><?php _e('Stores', 'parfume-reviews'); ?></a>
                <a href="#product-scraper" class="nav-tab"><?php _e('Product Scraper', 'parfume-reviews'); ?></a>
                <a href="#prices" class="nav-tab"><?php _e('Цени', 'parfume-reviews'); ?></a>
                <a href="#import_export" class="nav-tab"><?php _e('Импорт/Експорт', 'parfume-reviews'); ?></a>
                <a href="#shortcodes" class="nav-tab"><?php _e('Shortcodes', 'parfume-reviews'); ?></a>
                <a href="#debug" class="nav-tab"><?php _e('Дебъг', 'parfume-reviews'); ?></a>
            </h2>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('parfume-reviews-settings');
                do_settings_sections('parfume-reviews-settings');
                ?>
                
                <!-- General Tab -->
                <div id="general" class="tab-content">
                    <h2><?php _e('Общи настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Основни настройки на плъгина.', 'parfume-reviews'); ?></p>
                    <?php do_settings_fields('parfume-reviews-settings', 'parfume_reviews_general_section'); ?>
                </div>
                
                <!-- URL Settings Tab -->
                <div id="url" class="tab-content">
                    <h2><?php _e('URL настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте URL структурата за различните типове страници.', 'parfume-reviews'); ?></p>
                    <?php do_settings_fields('parfume-reviews-settings', 'parfume_reviews_url_section'); ?>
                    
                    <?php $this->render_url_structure_info(); ?>
                </div>
                
                <!-- Homepage Tab -->
                <div id="homepage" class="tab-content">
                    <h2><?php _e('Настройки за начална страница', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте елементите на началната страница.', 'parfume-reviews'); ?></p>
                    <?php do_settings_fields('parfume-reviews-settings', 'parfume_reviews_homepage_section'); ?>
                </div>
                
                <!-- Mobile Settings Tab -->
                <div id="mobile" class="tab-content">
                    <h2><?php _e('Mobile настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за мобилно поведение на stores панела.', 'parfume-reviews'); ?></p>
                    <?php do_settings_fields('parfume-reviews-settings', 'parfume_reviews_mobile_section'); ?>
                </div>
                
                <!-- Stores Tab -->
                <div id="stores" class="tab-content">
                    <h2><?php _e('Stores', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Управление на affiliate магазини за показване в постовете.', 'parfume-reviews'); ?></p>
                    <?php $this->render_stores_section(); ?>
                </div>
                
                <!-- Product Scraper Tab -->
                <div id="product-scraper" class="tab-content">
                    <h2><?php _e('Product Scraper', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигуриране и мониторинг на автоматичното скрейпване на продуктови данни.', 'parfume-reviews'); ?></p>
                    <?php $this->render_product_scraper_section(); ?>
                </div>
                
                <!-- Prices Tab -->
                <div id="prices" class="tab-content">
                    <h2><?php _e('Настройки за цени', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте как се показват и обновяват цените.', 'parfume-reviews'); ?></p>
                    <?php do_settings_fields('parfume-reviews-settings', 'parfume_reviews_prices_section'); ?>
                </div>
                
                <!-- Import/Export Tab -->
                <div id="import_export" class="tab-content">
                    <h2><?php _e('Импорт и Експорт', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Импортирайте или експортирайте данни от плъгина.', 'parfume-reviews'); ?></p>
                    <?php $this->render_import_export_section(); ?>
                </div>
                
                <!-- Shortcodes Tab -->
                <div id="shortcodes" class="tab-content">
                    <h2><?php _e('Налични Shortcodes', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Списък с всички налични shortcodes за използване в постове и страници.', 'parfume-reviews'); ?></p>
                    <?php $this->render_shortcodes_section(); ?>
                </div>
                
                <!-- Debug Tab -->
                <div id="debug" class="tab-content">
                    <h2><?php _e('Дебъг информация', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Инструменти за отстраняване на проблеми.', 'parfume-reviews'); ?></p>
                    <?php $this->render_debug_section(); ?>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function register_settings() {
        register_setting('parfume-reviews-settings', 'parfume_reviews_settings');
        register_setting('parfume-reviews-settings', 'parfume_reviews_stores');
        register_setting('parfume-reviews-settings', 'parfume_reviews_scraper_settings');
        
        // General Section
        add_settings_section(
            'parfume_reviews_general_section',
            __('Общи настройки', 'parfume-reviews'),
            null,
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'posts_per_page',
            __('Постове на страница', 'parfume-reviews'),
            array($this, 'posts_per_page_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        // URL Section
        add_settings_section(
            'parfume_reviews_url_section',
            __('URL настройки', 'parfume-reviews'),
            null,
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'parfume_slug',
            __('Parfume slug', 'parfume-reviews'),
            array($this, 'parfume_slug_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_url_section'
        );
        
        add_settings_field(
            'blog_slug',
            __('Blog slug', 'parfume-reviews'),
            array($this, 'blog_slug_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_url_section'
        );
        
        // Taxonomy URL fields
        $taxonomies = array(
            'marki_slug' => __('Марки slug', 'parfume-reviews'),
            'gender_slug' => __('Пол slug', 'parfume-reviews'),
            'aroma_type_slug' => __('Тип аромат slug', 'parfume-reviews'),
            'season_slug' => __('Сезон slug', 'parfume-reviews'),
            'intensity_slug' => __('Интензивност slug', 'parfume-reviews'),
            'notes_slug' => __('Ноти slug', 'parfume-reviews'),
            'perfumer_slug' => __('Парфюмеристи slug', 'parfume-reviews'),
        );
        
        foreach ($taxonomies as $slug_field => $label) {
            add_settings_field(
                $slug_field,
                $label,
                array($this, 'taxonomy_slug_callback'),
                'parfume-reviews-settings',
                'parfume_reviews_url_section',
                array('field' => $slug_field)
            );
        }
        
        // Homepage Section
        add_settings_section(
            'parfume_reviews_homepage_section',
            __('Настройки за начална страница', 'parfume-reviews'),
            null,
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'homepage_hero_enabled',
            __('Покажи hero секция', 'parfume-reviews'),
            array($this, 'homepage_hero_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
        
        add_settings_field(
            'homepage_featured_enabled',
            __('Покажи препоръчани парфюми', 'parfume-reviews'),
            array($this, 'homepage_featured_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
        
        add_settings_field(
            'homepage_latest_count',
            __('Брой последни парфюми', 'parfume-reviews'),
            array($this, 'homepage_latest_count_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_homepage_section'
        );
        
        // Mobile Section
        add_settings_section(
            'parfume_reviews_mobile_section',
            __('Mobile настройки', 'parfume-reviews'),
            null,
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'mobile_fixed_panel',
            __('Фиксиран панел', 'parfume-reviews'),
            array($this, 'mobile_fixed_panel_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
        
        add_settings_field(
            'mobile_show_close_btn',
            __('Бутон за затваряне', 'parfume-reviews'),
            array($this, 'mobile_show_close_btn_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
        
        add_settings_field(
            'mobile_z_index',
            __('Z-index', 'parfume-reviews'),
            array($this, 'mobile_z_index_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
        
        add_settings_field(
            'mobile_bottom_offset',
            __('Отстояние отдолу (px)', 'parfume-reviews'),
            array($this, 'mobile_bottom_offset_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_mobile_section'
        );
        
        // Prices Section
        add_settings_section(
            'parfume_reviews_prices_section',
            __('Настройки за цени', 'parfume-reviews'),
            null,
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'currency_symbol',
            __('Валутен символ', 'parfume-reviews'),
            array($this, 'currency_symbol_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_prices_section'
        );
        
        add_settings_field(
            'price_format',
            __('Формат на цената', 'parfume-reviews'),
            array($this, 'price_format_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_prices_section'
        );
    }
    
    // Callback functions for settings fields
    public function posts_per_page_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['posts_per_page']) ? $settings['posts_per_page'] : 12;
        echo '<input type="number" name="parfume_reviews_settings[posts_per_page]" value="' . esc_attr($value) . '" min="1" max="50" />';
        echo '<p class="description">' . __('Брой постове за показване на страница в архивите.', 'parfume-reviews') . '</p>';
    }
    
    public function parfume_slug_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfume';
        echo '<input type="text" name="parfume_reviews_settings[parfume_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('URL slug за единични parfume постове.', 'parfume-reviews') . '</p>';
    }
    
    public function blog_slug_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['blog_slug']) ? $settings['blog_slug'] : 'parfume-blog';
        echo '<input type="text" name="parfume_reviews_settings[blog_slug]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('URL slug за blog постове.', 'parfume-reviews') . '</p>';
    }
    
    public function taxonomy_slug_callback($args) {
        $settings = get_option('parfume_reviews_settings', array());
        $field = $args['field'];
        $defaults = array(
            'marki_slug' => 'marki',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'notes_slug' => 'notes',
            'perfumer_slug' => 'perfumer',
        );
        $value = isset($settings[$field]) ? $settings[$field] : $defaults[$field];
        echo '<input type="text" name="parfume_reviews_settings[' . $field . ']" value="' . esc_attr($value) . '" />';
        
        // Add view archive button
        $taxonomy_name = str_replace('_slug', '', $field);
        $archive_url = home_url('/' . $value . '/');
        echo ' <a href="' . esc_url($archive_url) . '" class="button view-archive-btn" target="_blank">';
        echo '<span class="dashicons dashicons-external"></span>' . __('Виж архива', 'parfume-reviews');
        echo '</a>';
    }
    
    public function homepage_hero_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_hero_enabled']) ? $settings['homepage_hero_enabled'] : 1;
        echo '<label><input type="checkbox" name="parfume_reviews_settings[homepage_hero_enabled]" value="1" ' . checked(1, $value, false) . ' /> ';
        echo __('Покажи hero секция на началната страница', 'parfume-reviews') . '</label>';
    }
    
    public function homepage_featured_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_featured_enabled']) ? $settings['homepage_featured_enabled'] : 1;
        echo '<label><input type="checkbox" name="parfume_reviews_settings[homepage_featured_enabled]" value="1" ' . checked(1, $value, false) . ' /> ';
        echo __('Покажи секция с препоръчани парфюми', 'parfume-reviews') . '</label>';
    }
    
    public function homepage_latest_count_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['homepage_latest_count']) ? $settings['homepage_latest_count'] : 8;
        echo '<input type="number" name="parfume_reviews_settings[homepage_latest_count]" value="' . esc_attr($value) . '" min="1" max="20" />';
        echo '<p class="description">' . __('Брой последни парфюми за показване на началната страница.', 'parfume-reviews') . '</p>';
    }
    
    public function mobile_fixed_panel_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_fixed_panel']) ? $settings['mobile_fixed_panel'] : 1;
        echo '<label><input type="checkbox" name="parfume_reviews_settings[mobile_fixed_panel]" value="1" ' . checked(1, $value, false) . ' /> ';
        echo __('Показвай фиксиран stores панел в долната част на екрана на мобилни устройства', 'parfume-reviews') . '</label>';
    }
    
    public function mobile_show_close_btn_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_show_close_btn']) ? $settings['mobile_show_close_btn'] : 0;
        echo '<label><input type="checkbox" name="parfume_reviews_settings[mobile_show_close_btn]" value="1" ' . checked(1, $value, false) . ' /> ';
        echo __('Позволявай скриване на stores панела чрез бутон "X"', 'parfume-reviews') . '</label>';
    }
    
    public function mobile_z_index_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_z_index']) ? $settings['mobile_z_index'] : 9999;
        echo '<input type="number" name="parfume_reviews_settings[mobile_z_index]" value="' . esc_attr($value) . '" min="1" />';
        echo '<p class="description">' . __('Z-index стойност на stores панела (при конфликти с други фиксирани елементи).', 'parfume-reviews') . '</p>';
    }
    
    public function mobile_bottom_offset_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_bottom_offset']) ? $settings['mobile_bottom_offset'] : 0;
        echo '<input type="number" name="parfume_reviews_settings[mobile_bottom_offset]" value="' . esc_attr($value) . '" min="0" />';
        echo '<p class="description">' . __('Отстояние в пиксели от долния край на екрана (при наличие на други фиксирани елементи).', 'parfume-reviews') . '</p>';
    }
    
    public function currency_symbol_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'лв.';
        echo '<input type="text" name="parfume_reviews_settings[currency_symbol]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Валутен символ за показване до цените.', 'parfume-reviews') . '</p>';
    }
    
    public function price_format_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['price_format']) ? $settings['price_format'] : 'after';
        echo '<select name="parfume_reviews_settings[price_format]">';
        echo '<option value="before" ' . selected('before', $value, false) . '>' . __('Преди цената ($10)', 'parfume-reviews') . '</option>';
        echo '<option value="after" ' . selected('after', $value, false) . '>' . __('След цената (10$)', 'parfume-reviews') . '</option>';
        echo '</select>';
    }
    
    /**
     * Рендерира секцията за Stores
     */
    public function render_stores_section() {
        $stores = get_option('parfume_reviews_stores', array());
        ?>
        <div class="stores-management">
            <div class="stores-list">
                <h3><?php _e('Налични магазини', 'parfume-reviews'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Лого', 'parfume-reviews'); ?></th>
                            <th><?php _e('Име на магазина', 'parfume-reviews'); ?></th>
                            <th><?php _e('Статус', 'parfume-reviews'); ?></th>
                            <th><?php _e('Действия', 'parfume-reviews'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="stores-list-body">
                        <?php if (!empty($stores)): ?>
                            <?php foreach ($stores as $store_id => $store): ?>
                                <tr data-store-id="<?php echo esc_attr($store_id); ?>">
                                    <td>
                                        <?php if (!empty($store['logo'])): ?>
                                            <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" style="max-width: 50px; max-height: 30px;">
                                        <?php else: ?>
                                            <span class="dashicons dashicons-store"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($store['name']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $store['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                            <?php echo $store['status'] === 'active' ? __('Активен', 'parfume-reviews') : __('Неактивен', 'parfume-reviews'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="button edit-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Редактирай', 'parfume-reviews'); ?>
                                        </button>
                                        <button type="button" class="button delete-store" data-store-id="<?php echo esc_attr($store_id); ?>">
                                            <?php _e('Изтрий', 'parfume-reviews'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4"><?php _e('Няма добавени магазини.', 'parfume-reviews'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="add-store-form">
                <h3><?php _e('Добави нов магазин', 'parfume-reviews'); ?></h3>
                <form id="add-store-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="store_name"><?php _e('Име на магазина', 'parfume-reviews'); ?></label></th>
                            <td>
                                <input type="text" id="store_name" name="store_name" class="regular-text" required />
                                <p class="description"><?php _e('Въведете името на магазина.', 'parfume-reviews'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="store_logo"><?php _e('Лого', 'parfume-reviews'); ?></label></th>
                            <td>
                                <input type="hidden" id="store_logo" name="store_logo" />
                                <img id="store_logo_preview" src="" style="max-width: 100px; max-height: 60px; display: none;" />
                                <br />
                                <button type="button" id="upload_logo_btn" class="button"><?php _e('Избери лого', 'parfume-reviews'); ?></button>
                                <button type="button" id="remove_logo_btn" class="button" style="display: none;"><?php _e('Премахни лого', 'parfume-reviews'); ?></button>
                                <p class="description"><?php _e('Изберете лого за магазина.', 'parfume-reviews'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="store_status"><?php _e('Статус', 'parfume-reviews'); ?></label></th>
                            <td>
                                <select id="store_status" name="store_status">
                                    <option value="active"><?php _e('Активен', 'parfume-reviews'); ?></option>
                                    <option value="inactive"><?php _e('Неактивен', 'parfume-reviews'); ?></option>
                                </select>
                                <p class="description"><?php _e('Статус на магазина.', 'parfume-reviews'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Добави магазин', 'parfume-reviews'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <style>
        .stores-management {
            margin-top: 20px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .add-store-form {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ccd0d4;
            background: #f9f9f9;
        }
        </style>
        <?php
    }
    
    /**
     * Рендерира секцията за Product Scraper
     */
    public function render_product_scraper_section() {
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $scrape_interval = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 24;
        $batch_size = isset($scraper_settings['batch_size']) ? $scraper_settings['batch_size'] : 10;
        $user_agent = isset($scraper_settings['user_agent']) ? $scraper_settings['user_agent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        ?>
        <div class="product-scraper-management">
            <!-- Scraper Settings -->
            <div class="scraper-settings">
                <h3><?php _e('Настройки на скрейпъра', 'parfume-reviews'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="scrape_interval"><?php _e('Интервал за скрейпване (часове)', 'parfume-reviews'); ?></label></th>
                        <td>
                            <input type="number" id="scrape_interval" name="parfume_reviews_scraper_settings[scrape_interval]" value="<?php echo esc_attr($scrape_interval); ?>" min="1" max="168" />
                            <p class="description"><?php _e('На колко часа да се актуализират данните автоматично.', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="batch_size"><?php _e('Размер на batch (продукти)', 'parfume-reviews'); ?></label></th>
                        <td>
                            <input type="number" id="batch_size" name="parfume_reviews_scraper_settings[batch_size]" value="<?php echo esc_attr($batch_size); ?>" min="1" max="50" />
                            <p class="description"><?php _e('Колко продукта да се обработват наведнъж, за да не се натоварва сървъра.', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="user_agent"><?php _e('User Agent', 'parfume-reviews'); ?></label></th>
                        <td>
                            <input type="text" id="user_agent" name="parfume_reviews_scraper_settings[user_agent]" value="<?php echo esc_attr($user_agent); ?>" class="large-text" />
                            <p class="description"><?php _e('User Agent за HTTP заявките към магазините.', 'parfume-reviews'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Monitor Section -->
            <div class="scraper-monitor">
                <h3><?php _e('Monitor', 'parfume-reviews'); ?></h3>
                <p><?php _e('Преглед на всички Product URLs и техния статус.', 'parfume-reviews'); ?></p>
                
                <div class="monitor-actions">
                    <button type="button" id="refresh-monitor" class="button"><?php _e('Обнови', 'parfume-reviews'); ?></button>
                    <button type="button" id="scrape-all" class="button button-secondary"><?php _e('Скрейпни всички', 'parfume-reviews'); ?></button>
                </div>
                
                <table class="wp-list-table widefat fixed striped" id="scraper-monitor-table">
                    <thead>
                        <tr>
                            <th><?php _e('Пост', 'parfume-reviews'); ?></th>
                            <th><?php _e('Магазин', 'parfume-reviews'); ?></th>
                            <th><?php _e('Product URL', 'parfume-reviews'); ?></th>
                            <th><?php _e('Последна цена', 'parfume-reviews'); ?></th>
                            <th><?php _e('Последно скрейпване', 'parfume-reviews'); ?></th>
                            <th><?php _e('Следващо скрейпване', 'parfume-reviews'); ?></th>
                            <th><?php _e('Статус', 'parfume-reviews'); ?></th>
                            <th><?php _e('Действия', 'parfume-reviews'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="monitor-table-body">
                        <?php $this->render_monitor_table_rows(); ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Scraper Test Tool -->
            <div class="scraper-test-tool">
                <h3><?php _e('Scraper Test Tool', 'parfume-reviews'); ?></h3>
                <p><?php _e('Тестване и конфигуриране на схеми за скрейпване на нови магазини.', 'parfume-reviews'); ?></p>
                
                <form id="scraper-test-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="test_url"><?php _e('Тестов Product URL', 'parfume-reviews'); ?></label></th>
                            <td>
                                <input type="url" id="test_url" name="test_url" class="large-text" placeholder="https://example.com/product-page" />
                                <p class="description"><?php _e('Въведете URL на продуктова страница за анализ.', 'parfume-reviews'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="test_store"><?php _e('Магазин', 'parfume-reviews'); ?></label></th>
                            <td>
                                <select id="test_store" name="test_store">
                                    <option value=""><?php _e('Изберете магазин', 'parfume-reviews'); ?></option>
                                    <?php
                                    $stores = get_option('parfume_reviews_stores', array());
                                    foreach ($stores as $store_id => $store) {
                                        echo '<option value="' . esc_attr($store_id) . '">' . esc_html($store['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Изберете магазин за който ще се конфигурира схемата.', 'parfume-reviews'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Скрейпни и анализирай', 'parfume-reviews'); ?></button>
                    </p>
                </form>
                
                <div id="scraper-test-results" style="display: none;">
                    <h4><?php _e('Резултати от анализа', 'parfume-reviews'); ?></h4>
                    <div id="test-results-content"></div>
                </div>
            </div>
        </div>
        
        <style>
        .product-scraper-management {
            margin-top: 20px;
        }
        .scraper-settings,
        .scraper-monitor,
        .scraper-test-tool {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ccd0d4;
            background: #f9f9f9;
        }
        .monitor-actions {
            margin-bottom: 15px;
        }
        .status-success { color: #155724; }
        .status-error { color: #721c24; }
        .status-pending { color: #856404; }
        .status-blocked { color: #6c757d; }
        </style>
        <?php
    }
    
    /**
     * Рендерира редовете в monitor таблицата
     */
    private function render_monitor_table_rows() {
        global $wpdb;
        
        // Get all posts with stores that have product URLs
        $posts = get_posts(array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_parfume_stores'
        ));
        
        if (empty($posts)) {
            echo '<tr><td colspan="8">' . __('Няма постове с Product URLs.', 'parfume-reviews') . '</td></tr>';
            return;
        }
        
        foreach ($posts as $post) {
            $stores = get_post_meta($post->ID, '_parfume_stores', true);
            if (!empty($stores) && is_array($stores)) {
                foreach ($stores as $store_index => $store) {
                    if (!empty($store['product_url'])) {
                        $this->render_monitor_row($post, $store, $store_index);
                    }
                }
            }
        }
    }
    
    /**
     * Рендерира един ред в monitor таблицата
     */
    private function render_monitor_row($post, $store, $store_index) {
        $last_scraped = isset($store['last_scraped']) ? $store['last_scraped'] : '';
        $next_scrape = isset($store['next_scrape']) ? $store['next_scrape'] : '';
        $status = isset($store['scrape_status']) ? $store['scrape_status'] : 'pending';
        $price = isset($store['scraped_price']) ? $store['scraped_price'] : '';
        
        $status_class = 'status-' . $status;
        $status_text = '';
        switch ($status) {
            case 'success':
                $status_text = __('Успешно', 'parfume-reviews');
                break;
            case 'error':
                $status_text = __('Грешка', 'parfume-reviews');
                break;
            case 'blocked':
                $status_text = __('Блокиран', 'parfume-reviews');
                break;
            default:
                $status_text = __('Очакване', 'parfume-reviews');
        }
        
        echo '<tr>';
        echo '<td><a href="' . get_edit_post_link($post->ID) . '">' . esc_html($post->post_title) . '</a></td>';
        echo '<td>' . esc_html($store['name']) . '</td>';
        echo '<td><a href="' . esc_url($store['product_url']) . '" target="_blank">' . esc_html(substr($store['product_url'], 0, 50)) . '...</a></td>';
        echo '<td>' . esc_html($price) . '</td>';
        echo '<td>' . esc_html($last_scraped) . '</td>';
        echo '<td>' . esc_html($next_scrape) . '</td>';
        echo '<td><span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span></td>';
        echo '<td>';
        echo '<button type="button" class="button small scrape-single" data-post-id="' . esc_attr($post->ID) . '" data-store-index="' . esc_attr($store_index) . '">' . __('Скрейпни сега', 'parfume-reviews') . '</button>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Рендерира URL структура информация
     */
    private function render_url_structure_info() {
        $settings = get_option('parfume_reviews_settings', array());
        ?>
        <div class="url-structure-info">
            <h3><?php _e('URL структура', 'parfume-reviews'); ?></h3>
            <p><?php _e('Примерни URLs базирани на текущите настройки:', 'parfume-reviews'); ?></p>
            <ul>
                <li><?php echo home_url('/' . (isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfume') . '/'); ?> - <?php _e('Архив на парфюми', 'parfume-reviews'); ?></li>
                <li><?php echo home_url('/' . (isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfume') . '/sample-parfume/'); ?> - <?php _e('Единичен парфюм', 'parfume-reviews'); ?></li>
                <li><?php echo home_url('/' . (isset($settings['marki_slug']) ? $settings['marki_slug'] : 'marki') . '/chanel/'); ?> - <?php _e('Парфюми от марка', 'parfume-reviews'); ?></li>
                <li><?php echo home_url('/' . (isset($settings['perfumer_slug']) ? $settings['perfumer_slug'] : 'perfumer') . '/'); ?> - <?php _e('Всички парфюмеристи', 'parfume-reviews'); ?></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Рендерира импорт/експорт секция
     */
    private function render_import_export_section() {
        ?>
        <div class="import-export-section">
            <h3><?php _e('Експорт на данни', 'parfume-reviews'); ?></h3>
            <p><?php _e('Експортирайте данните от плъгина.', 'parfume-reviews'); ?></p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=parfume-reviews-settings&action=export'); ?>" class="button">
                    <?php _e('Експорт на всички данни', 'parfume-reviews'); ?>
                </a>
            </p>
            
            <h3><?php _e('Импорт на данни', 'parfume-reviews'); ?></h3>
            <p><?php _e('Импортирайте данни в плъгина.', 'parfume-reviews'); ?></p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="import_file" accept=".json" />
                <input type="submit" name="import_data" value="<?php _e('Импорт', 'parfume-reviews'); ?>" class="button" />
                <?php wp_nonce_field('parfume_import', 'parfume_import_nonce'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира shortcodes секция
     */
    private function render_shortcodes_section() {
        ?>
        <div class="shortcodes-section">
            <h3><?php _e('Налични Shortcodes', 'parfume-reviews'); ?></h3>
            
            <h4>[parfume_list]</h4>
            <p><?php _e('Показва списък с парфюми.', 'parfume-reviews'); ?></p>
            <p><strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong></p>
            <ul>
                <li><code>count</code> - <?php _e('Брой парфюми (по подразбиране: 12)', 'parfume-reviews'); ?></li>
                <li><code>brand</code> - <?php _e('Филтриране по марка', 'parfume-reviews'); ?></li>
                <li><code>gender</code> - <?php _e('Филтриране по пол', 'parfume-reviews'); ?></li>
                <li><code>featured</code> - <?php _e('Показване само на препоръчани (true/false)', 'parfume-reviews'); ?></li>
            </ul>
            <p><strong><?php _e('Примери:', 'parfume-reviews'); ?></strong></p>
            <code>[parfume_list count="6" featured="true"]</code><br>
            <code>[parfume_list brand="chanel" count="4"]</code>
            
            <h4>[parfume_comparison]</h4>
            <p><?php _e('Показва инструмент за сравняване на парфюми.', 'parfume-reviews'); ?></p>
            <p><strong><?php _e('Примери:', 'parfume-reviews'); ?></strong></p>
            <code>[parfume_comparison]</code>
            
            <h4>[parfume_search]</h4>
            <p><?php _e('Показва разширена форма за търсене.', 'parfume-reviews'); ?></p>
            <p><strong><?php _e('Примери:', 'parfume-reviews'); ?></strong></p>
            <code>[parfume_search]</code>
        </div>
        <?php
    }
    
    /**
     * Рендерира debug секция
     */
    private function render_debug_section() {
        ?>
        <div class="debug-section">
            <h3><?php _e('Debug инструменти', 'parfume-reviews'); ?></h3>
            
            <h4><?php _e('Rewrite Rules', 'parfume-reviews'); ?></h4>
            <p>
                <button type="button" id="flush-rewrite-rules" class="button"><?php _e('Flush Rewrite Rules', 'parfume-reviews'); ?></button>
                <button type="button" id="check-rewrite-rules" class="button"><?php _e('Провери правилата', 'parfume-reviews'); ?></button>
            </p>
            
            <h4><?php _e('URL Testing', 'parfume-reviews'); ?></h4>
            <p>
                <button type="button" id="check-urls" class="button"><?php _e('Провери URLs', 'parfume-reviews'); ?></button>
                <button type="button" id="check-templates" class="button"><?php _e('Провери templates', 'parfume-reviews'); ?></button>
            </p>
            
            <div id="debug-results" style="margin-top: 20px;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#flush-rewrite-rules').on('click', function() {
                $.post(ajaxurl, {
                    action: 'parfume_flush_rewrite_rules',
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    $('#debug-results').html('<div class="notice notice-success"><p>Rewrite rules flushed!</p></div>');
                });
            });
            
            $('#check-rewrite-rules').on('click', function() {
                $.post(ajaxurl, {
                    action: 'parfume_get_rewrite_rules',
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#debug-results').html('<div class="notice notice-info"><p><strong>Rewrite Rules:</strong></p><pre>' + response.data.rules + '</pre></div>');
                    }
                });
            });
            
            $('#check-urls').on('click', function() {
                $.post(ajaxurl, {
                    action: 'parfume_debug_check_urls',
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#debug-results').html('<div class="notice notice-info"><p><strong>URL Check Results:</strong></p><pre>' + response.data.results + '</pre></div>');
                    }
                });
            });
            
            $('#check-templates').on('click', function() {
                $.post(ajaxurl, {
                    action: 'parfume_debug_check_templates',
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#debug-results').html('<div class="notice notice-info"><p><strong>Template Check Results:</strong></p><pre>' + response.data.results + '</pre></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    // AJAX handlers for stores management
    public function ajax_save_store() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $store_name = sanitize_text_field($_POST['store_name']);
        $store_logo = esc_url_raw($_POST['store_logo']);
        $store_status = sanitize_text_field($_POST['store_status']);
        $store_id = isset($_POST['store_id']) ? sanitize_text_field($_POST['store_id']) : '';
        
        if (empty($store_name)) {
            wp_send_json_error('Store name is required');
        }
        
        $stores = get_option('parfume_reviews_stores', array());
        
        if (empty($store_id)) {
            // Add new store
            $store_id = uniqid('store_');
        }
        
        $stores[$store_id] = array(
            'name' => $store_name,
            'logo' => $store_logo,
            'status' => $store_status,
            'created' => current_time('mysql'),
            'updated' => current_time('mysql')
        );
        
        update_option('parfume_reviews_stores', $stores);
        
        wp_send_json_success(array(
            'store_id' => $store_id,
            'store' => $stores[$store_id],
            'message' => __('Магазинът е запазен успешно.', 'parfume-reviews')
        ));
    }
    
    public function ajax_delete_store() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $stores = get_option('parfume_reviews_stores', array());
        
        if (isset($stores[$store_id])) {
            unset($stores[$store_id]);
            update_option('parfume_reviews_stores', $stores);
            wp_send_json_success(__('Магазинът е изтрит успешно.', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-reviews'));
        }
    }
    
    // AJAX handlers for product scraper
    public function ajax_scrape_product() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        // Get the store data
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        if (!isset($stores[$store_index]) || empty($stores[$store_index]['product_url'])) {
            wp_send_json_error(__('Невалиден Product URL.', 'parfume-reviews'));
        }
        
        $store = $stores[$store_index];
        $product_url = $store['product_url'];
        
        // Perform scraping
        $scrape_result = $this->scrape_product_data($product_url, $store['name']);
        
        if ($scrape_result['success']) {
            // Update store data with scraped information
            $stores[$store_index] = array_merge($store, $scrape_result['data']);
            $stores[$store_index]['last_scraped'] = current_time('mysql');
            $stores[$store_index]['scrape_status'] = 'success';
            
            // Calculate next scrape time
            $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
            $interval = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 24;
            $stores[$store_index]['next_scrape'] = date('Y-m-d H:i:s', strtotime('+' . $interval . ' hours'));
            
            update_post_meta($post_id, '_parfume_stores', $stores);
            
            wp_send_json_success(array(
                'message' => __('Данните са актуализирани успешно.', 'parfume-reviews'),
                'data' => $scrape_result['data']
            ));
        } else {
            // Update error status
            $stores[$store_index]['scrape_status'] = 'error';
            $stores[$store_index]['scrape_error'] = $scrape_result['error'];
            update_post_meta($post_id, '_parfume_stores', $stores);
            
            wp_send_json_error($scrape_result['error']);
        }
    }
    
    public function ajax_test_scraper() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $test_url = esc_url_raw($_POST['test_url']);
        $store_id = sanitize_text_field($_POST['store_id']);
        
        if (empty($test_url)) {
            wp_send_json_error(__('URL е задължителен.', 'parfume-reviews'));
        }
        
        $stores = get_option('parfume_reviews_stores', array());
        $store_name = isset($stores[$store_id]) ? $stores[$store_id]['name'] : 'Unknown';
        
        // Perform test scraping
        $scrape_result = $this->scrape_product_data($test_url, $store_name, true);
        
        if ($scrape_result['success']) {
            wp_send_json_success(array(
                'message' => __('Скрейпването е успешно.', 'parfume-reviews'),
                'data' => $scrape_result['data'],
                'analysis' => $scrape_result['analysis']
            ));
        } else {
            wp_send_json_error($scrape_result['error']);
        }
    }
    
    public function ajax_save_scraper_schema() {
        check_ajax_referer('parfume_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $store_id = sanitize_text_field($_POST['store_id']);
        $schema = $_POST['schema']; // This should be sanitized based on structure
        
        $stores = get_option('parfume_reviews_stores', array());
        if (isset($stores[$store_id])) {
            $stores[$store_id]['scraper_schema'] = $schema;
            update_option('parfume_reviews_stores', $stores);
            wp_send_json_success(__('Схемата е запазена успешно.', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-reviews'));
        }
    }
    
    /**
     * Скрейпва данни от продуктова страница
     */
    private function scrape_product_data($url, $store_name, $is_test = false) {
        $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
        $user_agent = isset($scraper_settings['user_agent']) ? $scraper_settings['user_agent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        // Fetch page content
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => $user_agent,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => __('Грешка при зареждане на страницата: ', 'parfume-reviews') . $response->get_error_message()
            );
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            return array(
                'success' => false,
                'error' => sprintf(__('HTTP грешка %d при зареждане на страницата.', 'parfume-reviews'), $http_code)
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return array(
                'success' => false,
                'error' => __('Празно съдържание от страницата.', 'parfume-reviews')
            );
        }
        
        // Load HTML content
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Get store schema if exists
        $stores = get_option('parfume_reviews_stores', array());
        $schema = null;
        foreach ($stores as $store) {
            if ($store['name'] === $store_name && isset($store['scraper_schema'])) {
                $schema = $store['scraper_schema'];
                break;
            }
        }
        
        $scraped_data = array();
        
        if ($schema && !$is_test) {
            // Use existing schema
            $scraped_data = $this->scrape_with_schema($xpath, $schema);
        } else {
            // Auto-detect data for test or new schema
            $scraped_data = $this->auto_detect_product_data($xpath, $body);
        }
        
        if ($is_test) {
            return array(
                'success' => true,
                'data' => $scraped_data,
                'analysis' => $this->analyze_page_structure($xpath, $body)
            );
        }
        
        return array(
            'success' => true,
            'data' => $scraped_data
        );
    }
    
    /**
     * Скрейпва данни използвайки предефинирана схема
     */
    private function scrape_with_schema($xpath, $schema) {
        $data = array();
        
        // Price
        if (!empty($schema['price_selector'])) {
            $price_nodes = $xpath->query($schema['price_selector']);
            if ($price_nodes->length > 0) {
                $price_text = trim($price_nodes->item(0)->textContent);
                $data['scraped_price'] = $this->extract_price($price_text);
            }
        }
        
        // Old price
        if (!empty($schema['old_price_selector'])) {
            $old_price_nodes = $xpath->query($schema['old_price_selector']);
            if ($old_price_nodes->length > 0) {
                $old_price_text = trim($old_price_nodes->item(0)->textContent);
                $data['scraped_old_price'] = $this->extract_price($old_price_text);
            }
        }
        
        // Variants/ML options
        if (!empty($schema['ml_selector'])) {
            $ml_nodes = $xpath->query($schema['ml_selector']);
            $variants = array();
            foreach ($ml_nodes as $node) {
                $ml_text = trim($node->textContent);
                $ml_value = $this->extract_ml($ml_text);
                if ($ml_value) {
                    $variants[] = array(
                        'ml' => $ml_value,
                        'text' => $ml_text
                    );
                }
            }
            $data['scraped_variants'] = $variants;
        }
        
        // Availability
        if (!empty($schema['availability_selector'])) {
            $availability_nodes = $xpath->query($schema['availability_selector']);
            if ($availability_nodes->length > 0) {
                $availability_text = trim($availability_nodes->item(0)->textContent);
                $data['scraped_availability'] = $this->normalize_availability($availability_text);
            }
        }
        
        // Delivery info
        if (!empty($schema['delivery_selector'])) {
            $delivery_nodes = $xpath->query($schema['delivery_selector']);
            if ($delivery_nodes->length > 0) {
                $delivery_text = trim($delivery_nodes->item(0)->textContent);
                $data['scraped_delivery'] = $delivery_text;
            }
        }
        
        return $data;
    }
    
    /**
     * Автоматично откриване на продуктови данни
     */
    private function auto_detect_product_data($xpath, $html) {
        $data = array();
        
        // Auto-detect prices
        $price_patterns = array(
            '//span[contains(@class, "price")]',
            '//div[contains(@class, "price")]',
            '//*[contains(@class, "current-price")]',
            '//*[contains(@class, "product-price")]',
            '//*[contains(@itemprop, "price")]',
            '//*[contains(@class, "amount")]',
            '//*[text()[contains(., "лв")]]',
            '//*[text()[contains(., "BGN")]]',
            '//*[text()[contains(., "€")]]',
            '//*[text()[contains(., "$")]]'
        );
        
        $detected_prices = array();
        foreach ($price_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                $price = $this->extract_price($text);
                if ($price && !in_array($price, $detected_prices)) {
                    $detected_prices[] = $price;
                }
            }
        }
        
        if (!empty($detected_prices)) {
            sort($detected_prices, SORT_NUMERIC);
            $data['scraped_price'] = $detected_prices[0]; // Use lowest price
            if (count($detected_prices) > 1) {
                $data['scraped_old_price'] = $detected_prices[count($detected_prices) - 1]; // Use highest as old price
            }
        }
        
        // Auto-detect ML variants
        $ml_patterns = array(
            '//*[text()[contains(., "ml")]]',
            '//*[text()[contains(., "ML")]]',
            '//*[contains(@class, "variant")]',
            '//*[contains(@class, "size")]',
            '//*[contains(@class, "option")]'
        );
        
        $variants = array();
        foreach ($ml_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                $ml = $this->extract_ml($text);
                if ($ml && !in_array($ml, array_column($variants, 'ml'))) {
                    $variants[] = array(
                        'ml' => $ml,
                        'text' => $text
                    );
                }
            }
        }
        
        if (!empty($variants)) {
            usort($variants, function($a, $b) {
                return $a['ml'] - $b['ml'];
            });
            $data['scraped_variants'] = $variants;
        }
        
        // Auto-detect availability
        $availability_patterns = array(
            '//*[contains(@class, "stock")]',
            '//*[contains(@class, "availability")]',
            '//*[contains(@class, "in-stock")]',
            '//*[contains(@class, "out-of-stock")]',
            '//*[text()[contains(., "наличен")]]',
            '//*[text()[contains(., "налично")]]',
            '//*[text()[contains(., "в наличност")]]',
            '//*[text()[contains(., "няма в наличност")]]'
        );
        
        foreach ($availability_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            if ($nodes->length > 0) {
                $availability_text = trim($nodes->item(0)->textContent);
                $data['scraped_availability'] = $this->normalize_availability($availability_text);
                break;
            }
        }
        
        // Auto-detect delivery info
        $delivery_patterns = array(
            '//*[contains(@class, "shipping")]',
            '//*[contains(@class, "delivery")]',
            '//*[text()[contains(., "доставка")]]',
            '//*[text()[contains(., "безплатна доставка")]]',
            '//*[text()[contains(., "shipping")]]'
        );
        
        foreach ($delivery_patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            if ($nodes->length > 0) {
                $delivery_text = trim($nodes->item(0)->textContent);
                if (strlen($delivery_text) < 200) { // Reasonable length for delivery info
                    $data['scraped_delivery'] = $delivery_text;
                    break;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Анализира структурата на страницата за създаване на схема
     */
    private function analyze_page_structure($xpath, $html) {
        $analysis = array(
            'detected_prices' => array(),
            'detected_variants' => array(),
            'detected_availability' => array(),
            'detected_delivery' => array(),
            'suggested_selectors' => array()
        );
        
        // Analyze price elements
        $price_patterns = array(
            '//span[contains(@class, "price")]' => 'span.price',
            '//div[contains(@class, "price")]' => 'div.price',
            '//*[contains(@class, "current-price")]' => '.current-price',
            '//*[contains(@class, "product-price")]' => '.product-price'
        );
        
        foreach ($price_patterns as $xpath_pattern => $css_selector) {
            $nodes = $xpath->query($xpath_pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                $price = $this->extract_price($text);
                if ($price) {
                    $analysis['detected_prices'][] = array(
                        'text' => $text,
                        'price' => $price,
                        'selector' => $css_selector,
                        'xpath' => $xpath_pattern
                    );
                }
            }
        }
        
        // Analyze variant elements
        $variant_patterns = array(
            '//*[text()[contains(., "ml")]]' => 'text containing "ml"',
            '//*[contains(@class, "variant")]' => '.variant',
            '//*[contains(@class, "size")]' => '.size'
        );
        
        foreach ($variant_patterns as $xpath_pattern => $description) {
            $nodes = $xpath->query($xpath_pattern);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                $ml = $this->extract_ml($text);
                if ($ml) {
                    $analysis['detected_variants'][] = array(
                        'text' => $text,
                        'ml' => $ml,
                        'description' => $description,
                        'xpath' => $xpath_pattern
                    );
                }
            }
        }
        
        return $analysis;
    }
    
    /**
     * Извлича цена от текст
     */
    private function extract_price($text) {
        // Remove common currency symbols and words
        $text = str_replace(array('лв.', 'лв', 'BGN', 'EUR', '€', 'USD'), '', $text);
        $text = preg_replace('/[^0-9.,]/', '', $text);
        
        // Handle different decimal separators
        if (substr_count($text, '.') === 1 && substr_count($text, ',') === 0) {
            // Decimal with dot
            return floatval($text);
        } elseif (substr_count($text, ',') === 1 && substr_count($text, '.') === 0) {
            // Decimal with comma
            return floatval(str_replace(',', '.', $text));
        } elseif (substr_count($text, '.') > 1 || substr_count($text, ',') > 1) {
            // Multiple separators - assume thousands separator
            $text = preg_replace('/[,.](?=\d{3})/', '', $text);
            $text = str_replace(',', '.', $text);
            return floatval($text);
        }
        
        return floatval($text);
    }
    
    /**
     * Извлича ML стойност от текст
     */
    private function extract_ml($text) {
        preg_match('/(\d+)\s*ml/i', $text, $matches);
        return isset($matches[1]) ? intval($matches[1]) : null;
    }
    
    /**
     * Нормализира информация за наличност
     */
    private function normalize_availability($text) {
        $text = strtolower($text);
        
        $available_patterns = array('наличен', 'налично', 'в наличност', 'in stock', 'available');
        $unavailable_patterns = array('няма в наличност', 'не е наличен', 'out of stock', 'unavailable');
        
        foreach ($available_patterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                return 'available';
            }
        }
        
        foreach ($unavailable_patterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                return 'unavailable';
            }
        }
        
        return 'unknown';
    }
    
    // Debug AJAX handlers (existing methods)
    public function ajax_debug_taxonomy_url() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $term = sanitize_text_field($_POST['term']);
        
        $term_obj = get_term_by('slug', $term, $taxonomy);
        if ($term_obj) {
            $url = get_term_link($term_obj);
            wp_send_json_success(array('url' => $url));
        } else {
            wp_send_json_error('Term not found');
        }
    }
    
    public function ajax_debug_check_urls() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $results = "=== URL Check Results ===\n\n";
        
        // Check if post type is registered
        $post_types = get_post_types(array(), 'objects');
        if (isset($post_types['parfume'])) {
            $results .= "✅ Post type 'parfume' is registered\n";
            $results .= "   - Rewrite slug: " . $post_types['parfume']->rewrite['slug'] . "\n";
        } else {
            $results .= "❌ Post type 'parfume' is NOT registered\n";
        }
        
        // Check taxonomies
        $taxonomies = get_taxonomies(array(), 'objects');
        $parfume_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($parfume_taxonomies as $taxonomy) {
            if (isset($taxonomies[$taxonomy])) {
                $results .= "✅ Taxonomy '$taxonomy' is registered\n";
                if (isset($taxonomies[$taxonomy]->rewrite['slug'])) {
                    $results .= "   - Rewrite slug: " . $taxonomies[$taxonomy]->rewrite['slug'] . "\n";
                }
            } else {
                $results .= "❌ Taxonomy '$taxonomy' is NOT registered\n";
            }
        }
        
        wp_send_json_success(array('results' => $results));
    }
    
    public function ajax_debug_check_templates() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $results = "=== Template Check Results ===\n\n";
        
        $template_files = array(
            'templates/single-parfume.php',
            'templates/archive-parfume.php',
            'templates/taxonomy-marki.php',
            'templates/taxonomy-perfumer.php',
        );
        
        foreach ($template_files as $template) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $template;
            if (file_exists($file_path)) {
                $results .= "✅ Template exists: $template\n";
            } else {
                $results .= "❌ Template missing: $template\n";
            }
        }
        
        wp_send_json_success(array('results' => $results));
    }
    
    public function ajax_flush_rewrite_rules() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        flush_rewrite_rules();
        wp_send_json_success('Rewrite rules flushed successfully');
    }
    
    /**
     * НОВА ФУНКЦИЯ за показване на rewrite правилата
     */
    public function ajax_get_rewrite_rules() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wp_rewrite;
        $rules = get_option('rewrite_rules');
        
        $output = "=== WordPress Rewrite Rules ===\n\n";
        
        if (!empty($rules)) {
            foreach ($rules as $rule => $rewrite) {
                $output .= "'{$rule}' => '{$rewrite}'\n";
            }
        } else {
            $output .= "Няма rewrite правила или не са генерирани.\n";
        }
        
        wp_send_json_success(array('rules' => $output));
    }
}