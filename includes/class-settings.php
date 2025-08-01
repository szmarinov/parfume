<?php
namespace Parfume_Reviews;

/**
 * Settings class - Модулен заместител файл  
 * Зарежда всички settings компоненти от отделни файлове
 * РЕВИЗИРАНА ВЕРСИЯ: Пълна функционалност с всички компоненти
 * 
 * Файл: includes/class-settings.php
 */
class Settings {
    
    /**
     * Instances от различните settings компоненти
     */
    private $general_settings;
    private $url_settings;
    private $homepage_settings;
    private $mobile_settings;
    private $stores_settings;
    private $scraper_settings;
    private $price_settings;
    private $import_export_settings;
    private $shortcodes_settings;
    private $debug_settings;
    
    /**
     * Settings groups и option names
     */
    private $settings_groups = array(
        'parfume_reviews_settings' => 'parfume_reviews_settings',
        'parfume_reviews_url_settings' => 'parfume_reviews_url_settings',
        'parfume_reviews_homepage_settings' => 'parfume_reviews_homepage_settings',
        'parfume_reviews_mobile_settings' => 'parfume_reviews_mobile_settings',
        'parfume_reviews_stores_settings' => 'parfume_reviews_stores_settings',
        'parfume_reviews_scraper_settings' => 'parfume_reviews_scraper_settings',
        'parfume_reviews_price_settings' => 'parfume_reviews_price_settings',
        'parfume_reviews_import_export_settings' => 'parfume_reviews_import_export_settings',
        'parfume_reviews_shortcodes_settings' => 'parfume_reviews_shortcodes_settings',
        'parfume_reviews_debug_settings' => 'parfume_reviews_debug_settings'
    );
    
    public function __construct() {
        // Зареждаме всички settings компоненти
        $this->load_settings_components();
        
        // Основни хукове за admin менюто
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Хукове за обработка на опции
        add_action('update_option_parfume_reviews_url_settings', array($this, 'flush_rewrite_rules_on_url_change'), 10, 2);
    }
    
    /**
     * Зарежда всички settings компоненти от отделни файлове
     */
    private function load_settings_components() {
        $components = array(
            'includes/settings/class-settings-general.php' => 'Settings_General',
            'includes/settings/class-settings-url.php' => 'Settings_URL', 
            'includes/settings/class-settings-homepage.php' => 'Settings_Homepage',
            'includes/settings/class-settings-mobile.php' => 'Settings_Mobile',
            'includes/settings/class-settings-stores.php' => 'Settings_Stores',
            'includes/settings/class-settings-scraper.php' => 'Settings_Scraper',
            'includes/settings/class-settings-price.php' => 'Settings_Price',
            'includes/settings/class-settings-import-export.php' => 'Settings_Import_Export',
            'includes/settings/class-settings-shortcodes.php' => 'Settings_Shortcodes',
            'includes/settings/class-settings-debug.php' => 'Settings_Debug'
        );
        
        foreach ($components as $file => $class_name) {
            $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                // Инициализираме компонента ако класът съществува
                $full_class_name = 'Parfume_Reviews\\Settings\\' . $class_name;
                if (class_exists($full_class_name)) {
                    $property_name = strtolower(str_replace('Settings_', '', $class_name)) . '_settings';
                    $this->$property_name = new $full_class_name();
                    
                    // Debug лог ако е включен
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Parfume Reviews: Loaded settings component: {$class_name}");
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Parfume Reviews: Settings class not found: {$full_class_name}");
                    }
                }
            } else {
                // Логираме грешка ако файлът липсва - с fallback функционалност
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Parfume Reviews: Missing settings file: {$file}");
                }
                
                // Създаваме fallback компонент
                $this->create_fallback_component($class_name);
            }
        }
    }
    
    /**
     * Създава fallback компонент ако оригиналният файл липсва
     */
    private function create_fallback_component($class_name) {
        $property_name = strtolower(str_replace('Settings_', '', $class_name)) . '_settings';
        
        // Създаваме basic fallback обект
        $this->$property_name = new class() {
            public function register_settings() {
                // Empty fallback method
            }
            
            public function render_section() {
                echo '<p><em>Settings component not available.</em></p>';
            }
        };
    }
    
    /**
     * Добавя админ менюто за настройките
     */
    public function add_admin_menu() {
        // Главна страница за настройки
        add_menu_page(
            __('Parfume Reviews', 'parfume-reviews'),
            __('Parfume Reviews', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews',
            array($this, 'render_main_settings_page'),
            'dashicons-star-filled',
            30
        );
        
        // Подстраници за различните настройки
        $submenu_pages = array(
            'parfume-reviews-general' => array(
                'title' => __('Общи настройки', 'parfume-reviews'),
                'callback' => array($this, 'render_general_settings_page')
            ),
            'parfume-reviews-url' => array(
                'title' => __('URL настройки', 'parfume-reviews'),
                'callback' => array($this, 'render_url_settings_page')
            ),
            'parfume-reviews-homepage' => array(
                'title' => __('Начална страница', 'parfume-reviews'),
                'callback' => array($this, 'render_homepage_settings_page')
            ),
            'parfume-reviews-mobile' => array(
                'title' => __('Mobile настройки', 'parfume-reviews'),
                'callback' => array($this, 'render_mobile_settings_page')
            ),
            'parfume-reviews-stores' => array(
                'title' => __('Магазини', 'parfume-reviews'),
                'callback' => array($this, 'render_stores_settings_page')
            ),
            'parfume-reviews-scraper' => array(
                'title' => __('Scraper', 'parfume-reviews'),
                'callback' => array($this, 'render_scraper_settings_page')
            ),
            'parfume-reviews-price' => array(
                'title' => __('Цени', 'parfume-reviews'),
                'callback' => array($this, 'render_price_settings_page')
            ),
            'parfume-reviews-import-export' => array(
                'title' => __('Импорт/Експорт', 'parfume-reviews'),
                'callback' => array($this, 'render_import_export_settings_page')
            ),
            'parfume-reviews-shortcodes' => array(
                'title' => __('Shortcodes', 'parfume-reviews'),
                'callback' => array($this, 'render_shortcodes_settings_page')
            ),
            'parfume-reviews-debug' => array(
                'title' => __('Debug', 'parfume-reviews'),
                'callback' => array($this, 'render_debug_settings_page')
            )
        );
        
        foreach ($submenu_pages as $slug => $page_info) {
            add_submenu_page(
                'parfume-reviews',
                $page_info['title'],
                $page_info['title'],
                'manage_options',
                $slug,
                $page_info['callback']
            );
        }
    }
    
    /**
     * Регистрира всички настройки
     */
    public function register_settings() {
        // Регистрираме всички settings groups
        foreach ($this->settings_groups as $group => $option_name) {
            register_setting($group, $option_name, array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array()
            ));
        }
        
        // Делегираме регистрацията на отделните компоненти
        if ($this->general_settings) {
            $this->general_settings->register_settings();
        }
        
        if ($this->url_settings) {
            $this->url_settings->register_settings();
        }
        
        if ($this->homepage_settings) {
            $this->homepage_settings->register_settings();
        }
        
        if ($this->mobile_settings) {
            $this->mobile_settings->register_settings();
        }
        
        if ($this->stores_settings) {
            $this->stores_settings->register_settings();
        }
        
        if ($this->scraper_settings) {
            $this->scraper_settings->register_settings();
        }
        
        if ($this->price_settings) {
            $this->price_settings->register_settings();
        }
        
        if ($this->import_export_settings) {
            $this->import_export_settings->register_settings();
        }
        
        if ($this->shortcodes_settings) {
            $this->shortcodes_settings->register_settings();
        }
        
        if ($this->debug_settings) {
            $this->debug_settings->register_settings();
        }
    }
    
    /**
     * Зарежда admin скриптове и стилове
     */
    public function enqueue_admin_scripts($hook) {
        // Зареждаме само на страниците на плъгина
        if (strpos($hook, 'parfume-reviews') === false) {
            return;
        }
        
        // CSS за admin настройки
        $admin_css_path = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/admin-settings.css';
        if (file_exists($admin_css_path)) {
            wp_enqueue_style(
                'parfume-reviews-admin-settings',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-settings.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
        }
        
        // JavaScript за admin настройки
        $admin_js_path = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/admin-settings.js';
        if (file_exists($admin_js_path)) {
            wp_enqueue_script(
                'parfume-reviews-admin-settings',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-settings.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Локализация за JS
            wp_localize_script('parfume-reviews-admin-settings', 'parfumeReviewsAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_reviews_admin_nonce'),
                'strings' => array(
                    'saving' => __('Записване...', 'parfume-reviews'),
                    'saved' => __('Записано!', 'parfume-reviews'),
                    'error' => __('Грешка при записване', 'parfume-reviews')
                )
            ));
        }
        
        // WordPress media upload скриптове за image fields
        wp_enqueue_media();
    }
    
    /**
     * Render функции за различните страници с настройки
     */
    
    /**
     * Рендерира главната страница с настройки (Overview)
     */
    public function render_main_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Parfume Reviews Настройки', 'parfume-reviews'); ?></h1>
            
            <div class="parfume-reviews-dashboard">
                <div class="settings-overview">
                    <h2><?php _e('Преглед на настройките', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Използвайте менюто отляво за да достъпите различните секции с настройки.', 'parfume-reviews'); ?></p>
                    
                    <!-- Settings Statistics -->
                    <div class="settings-stats">
                        <h3><?php _e('Статистики', 'parfume-reviews'); ?></h3>
                        <?php $this->render_settings_statistics(); ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <h3><?php _e('Бързи действия', 'parfume-reviews'); ?></h3>
                        <a href="<?php echo admin_url('admin.php?page=parfume-reviews-import-export'); ?>" class="button button-primary">
                            <?php _e('Импорт/Експорт', 'parfume-reviews'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=parfume-reviews-debug'); ?>" class="button button-secondary">
                            <?php _e('Debug информация', 'parfume-reviews'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира статистики за настройките
     */
    private function render_settings_statistics() {
        $parfume_count = wp_count_posts('parfume');
        $perfumer_count = wp_count_terms('perfumer');
        $brand_count = wp_count_terms('marki');
        
        echo '<ul>';
        echo '<li>' . sprintf(__('Общо парфюми: %d', 'parfume-reviews'), $parfume_count->publish) . '</li>';
        echo '<li>' . sprintf(__('Парфюмеристи: %d', 'parfume-reviews'), $perfumer_count) . '</li>';
        echo '<li>' . sprintf(__('Марки: %d', 'parfume-reviews'), $brand_count) . '</li>';
        echo '</ul>';
    }
    
    /**
     * Рендерира страницата с общи настройки
     */
    public function render_general_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Общи настройки', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_settings');
                do_settings_sections('parfume-reviews-settings');
                
                if ($this->general_settings && method_exists($this->general_settings, 'render_section')) {
                    $this->general_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с URL настройки
     */
    public function render_url_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('URL настройки', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_url_settings');
                do_settings_sections('parfume-reviews-url-settings');
                
                if ($this->url_settings && method_exists($this->url_settings, 'render_section')) {
                    $this->url_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
            
            <div class="url-preview">
                <h3><?php _e('Преглед на URL структурата', 'parfume-reviews'); ?></h3>
                <?php $this->render_url_preview(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с homepage настройки
     */
    public function render_homepage_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки за начална страница', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_homepage_settings');
                do_settings_sections('parfume-reviews-homepage-settings');
                
                if ($this->homepage_settings && method_exists($this->homepage_settings, 'render_section')) {
                    $this->homepage_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с mobile настройки
     */
    public function render_mobile_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Mobile настройки', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_mobile_settings');
                do_settings_sections('parfume-reviews-mobile-settings');
                
                if ($this->mobile_settings && method_exists($this->mobile_settings, 'render_section')) {
                    $this->mobile_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с магазини настройки
     */
    public function render_stores_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки за магазини', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_stores_settings');
                do_settings_sections('parfume-reviews-stores-settings');
                
                if ($this->stores_settings && method_exists($this->stores_settings, 'render_section')) {
                    $this->stores_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с scraper настройки
     */
    public function render_scraper_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Scraper настройки', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_scraper_settings');
                do_settings_sections('parfume-reviews-scraper-settings');
                
                if ($this->scraper_settings && method_exists($this->scraper_settings, 'render_section')) {
                    $this->scraper_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с ценови настройки
     */
    public function render_price_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Ценови настройки', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_price_settings');
                do_settings_sections('parfume-reviews-price-settings');
                
                if ($this->price_settings && method_exists($this->price_settings, 'render_section')) {
                    $this->price_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с импорт/експорт настройки
     */
    public function render_import_export_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Импорт и експорт', 'parfume-reviews'); ?></h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_import_export_settings');
                do_settings_sections('parfume-reviews-import-export-settings');
                
                if ($this->import_export_settings && method_exists($this->import_export_settings, 'render_section')) {
                    $this->import_export_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с shortcodes настройки
     */
    public function render_shortcodes_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Shortcodes настройки', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_shortcodes_settings');
                do_settings_sections('parfume-reviews-shortcodes-settings');
                
                if ($this->shortcodes_settings && method_exists($this->shortcodes_settings, 'render_section')) {
                    $this->shortcodes_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Рендерира страницата с debug настройки
     */
    public function render_debug_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Debug настройки', 'parfume-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_debug_settings');
                do_settings_sections('parfume-reviews-debug-settings');
                
                if ($this->debug_settings && method_exists($this->debug_settings, 'render_section')) {
                    $this->debug_settings->render_section();
                }
                
                submit_button();
                ?>
            </form>
            
            <!-- Debug Information -->
            <div class="debug-info">
                <h3><?php _e('Информация за системата', 'parfume-reviews'); ?></h3>
                <?php $this->render_debug_info(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира преглед на URL структурата
     */
    private function render_url_preview() {
        $url_settings = get_option('parfume_reviews_url_settings', array());
        
        $defaults = array(
            'parfume_slug' => 'parfiumi',
            'blog_slug' => 'blog',
            'marki_slug' => 'marki',
            'gender_slug' => 'pol',
            'aroma_type_slug' => 'tip-aroma',
            'season_slug' => 'sezon',
            'intensity_slug' => 'intensivnost',
            'notes_slug' => 'noti',
            'perfumer_slug' => 'parfiumerist'
        );
        
        $url_settings = wp_parse_args($url_settings, $defaults);
        $site_url = home_url('/');
        
        echo '<ul class="url-preview-list">';
        echo '<li><strong>' . __('Архив парфюми:', 'parfume-reviews') . '</strong> ' . $site_url . $url_settings['parfume_slug'] . '/</li>';
        echo '<li><strong>' . __('Отделен парфюм:', 'parfume-reviews') . '</strong> ' . $site_url . $url_settings['parfume_slug'] . '/example-parfume/</li>';
        echo '<li><strong>' . __('Блог:', 'parfume-reviews') . '</strong> ' . $site_url . $url_settings['blog_slug'] . '/</li>';
        echo '<li><strong>' . __('Марки:', 'parfume-reviews') . '</strong> ' . $site_url . $url_settings['marki_slug'] . '/</li>';
        echo '<li><strong>' . __('Парфюмеристи:', 'parfume-reviews') . '</strong> ' . $site_url . $url_settings['perfumer_slug'] . '/</li>';
        echo '</ul>';
    }
    
    /**
     * Рендерира debug информация
     */
    private function render_debug_info() {
        global $wp_version;
        
        echo '<table class="widefat">';
        echo '<tbody>';
        echo '<tr><td><strong>WordPress Version:</strong></td><td>' . $wp_version . '</td></tr>';
        echo '<tr><td><strong>Plugin Version:</strong></td><td>' . PARFUME_REVIEWS_VERSION . '</td></tr>';
        echo '<tr><td><strong>PHP Version:</strong></td><td>' . phpversion() . '</td></tr>';
        echo '<tr><td><strong>MySQL Version:</strong></td><td>' . $GLOBALS['wpdb']->db_version() . '</td></tr>';
        echo '<tr><td><strong>Active Theme:</strong></td><td>' . wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version') . '</td></tr>';
        echo '<tr><td><strong>Memory Limit:</strong></td><td>' . ini_get('memory_limit') . '</td></tr>';
        echo '<tr><td><strong>Max Execution Time:</strong></td><td>' . ini_get('max_execution_time') . 's</td></tr>';
        echo '</tbody>';
        echo '</table>';
        
        // Plugin specific debug info
        echo '<h4>' . __('Plugin Information', 'parfume-reviews') . '</h4>';
        echo '<table class="widefat">';
        echo '<tbody>';
        
        $post_types = get_post_types(array('public' => true), 'objects');
        $our_post_types = array_filter($post_types, function($pt) {
            return strpos($pt->name, 'parfume') !== false;
        });
        
        echo '<tr><td><strong>Registered Post Types:</strong></td><td>' . implode(', ', array_keys($our_post_types)) . '</td></tr>';
        
        $taxonomies = get_taxonomies(array('public' => true), 'objects');
        $our_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        $existing_taxonomies = array_filter($our_taxonomies, 'taxonomy_exists');
        
        echo '<tr><td><strong>Registered Taxonomies:</strong></td><td>' . implode(', ', $existing_taxonomies) . '</td></tr>';
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Sanitization callback за всички настройки
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (!is_array($input)) {
            return $sanitized;
        }
        
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'posts_per_page':
                    $sanitized[$key] = absint($value);
                    if ($sanitized[$key] < 1) $sanitized[$key] = 12;
                    if ($sanitized[$key] > 50) $sanitized[$key] = 50;
                    break;
                    
                case 'parfume_slug':
                case 'blog_slug':
                case 'marki_slug':
                case 'gender_slug':
                case 'aroma_type_slug':
                case 'season_slug':
                case 'intensity_slug':
                case 'notes_slug':
                case 'perfumer_slug':
                    $sanitized[$key] = sanitize_title($value);
                    if (empty($sanitized[$key])) {
                        // Fallback к default стойности
                        $defaults = array(
                            'parfume_slug' => 'parfiumi',
                            'blog_slug' => 'blog',
                            'marki_slug' => 'marki',
                            'gender_slug' => 'pol',
                            'aroma_type_slug' => 'tip-aroma',
                            'season_slug' => 'sezon',
                            'intensity_slug' => 'intensivnost',
                            'notes_slug' => 'noti',
                            'perfumer_slug' => 'parfiumerist'
                        );
                        $sanitized[$key] = $defaults[$key];
                    }
                    break;
                    
                case 'backup_enabled':
                case 'debug_mode':
                case 'debug_log_enabled':
                case 'mobile_enabled':
                    $sanitized[$key] = (bool) $value;
                    break;
                    
                case 'backup_frequency':
                    $allowed_frequencies = array('daily', 'weekly', 'monthly');
                    $sanitized[$key] = in_array($value, $allowed_frequencies) ? $value : 'weekly';
                    break;
                    
                default:
                    if (is_string($value)) {
                        $sanitized[$key] = sanitize_text_field($value);
                    } elseif (is_array($value)) {
                        $sanitized[$key] = array_map('sanitize_text_field', $value);
                    } else {
                        $sanitized[$key] = $value;
                    }
                    break;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Флъшва rewrite rules когато се променят URL настройките
     */
    public function flush_rewrite_rules_on_url_change($old_value, $new_value) {
        if ($old_value !== $new_value) {
            flush_rewrite_rules();
        }
    }
    
    /**
     * Getter методи за компонентите (за external access)
     */
    public function get_general_settings() {
        return $this->general_settings;
    }
    
    public function get_url_settings() {
        return $this->url_settings;
    }
    
    public function get_homepage_settings() {
        return $this->homepage_settings;
    }
    
    public function get_mobile_settings() {
        return $this->mobile_settings;
    }
    
    public function get_stores_settings() {
        return $this->stores_settings;
    }
    
    public function get_scraper_settings() {
        return $this->scraper_settings;
    }
    
    public function get_price_settings() {
        return $this->price_settings;
    }
    
    public function get_import_export_settings() {
        return $this->import_export_settings;
    }
    
    public function get_shortcodes_settings() {
        return $this->shortcodes_settings;
    }
    
    public function get_debug_settings() {
        return $this->debug_settings;
    }
    
    /**
     * Utility методи
     */
    
    /**
     * Получава всички настройки като масив
     */
    public function get_all_settings() {
        $all_settings = array();
        
        foreach ($this->settings_groups as $group => $option_name) {
            $all_settings[$group] = get_option($option_name, array());
        }
        
        return $all_settings;
    }
    
    /**
     * Получава конкретна настройка
     */
    public function get_setting($group, $key, $default = null) {
        $option_name = isset($this->settings_groups[$group]) ? $this->settings_groups[$group] : $group;
        $settings = get_option($option_name, array());
        
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Задава конкретна настройка
     */
    public function update_setting($group, $key, $value) {
        $option_name = isset($this->settings_groups[$group]) ? $this->settings_groups[$group] : $group;
        $settings = get_option($option_name, array());
        $settings[$key] = $value;
        
        return update_option($option_name, $settings);
    }
    
    /**
     * Изтрива всички настройки на плъгина (за uninstall)
     */
    public function delete_all_settings() {
        foreach ($this->settings_groups as $group => $option_name) {
            delete_option($option_name);
        }
    }
    
    /**
     * Експортира всички настройки в масив
     */
    public function export_settings() {
        return $this->get_all_settings();
    }
    
    /**
     * Импортира настройки от масив
     */
    public function import_settings($settings_data) {
        if (!is_array($settings_data)) {
            return false;
        }
        
        $imported = 0;
        
        foreach ($settings_data as $group => $settings) {
            if (isset($this->settings_groups[$group]) && is_array($settings)) {
                $sanitized_settings = $this->sanitize_settings($settings);
                update_option($this->settings_groups[$group], $sanitized_settings);
                $imported++;
            }
        }
        
        return $imported;
    }
}