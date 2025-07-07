<?php
/**
 * Admin class for Parfume Catalog plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Parfume Catalog', 'parfume-catalog'),
            __('Parfume Catalog', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog',
            array($this, 'admin_page'),
            'dashicons-store',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'parfume-catalog',
            __('Настройки', 'parfume-catalog'),
            __('Настройки', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-settings',
            array($this, 'settings_page')
        );
        
        // Stores submenu
        add_submenu_page(
            'parfume-catalog',
            __('Магазини', 'parfume-catalog'),
            __('Магазини', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-stores',
            array($this, 'stores_page')
        );
        
        // Product Scraper submenu
        add_submenu_page(
            'parfume-catalog',
            __('Product Scraper', 'parfume-catalog'),
            __('Product Scraper', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-scraper',
            array($this, 'scraper_page')
        );
        
        // Comments submenu
        add_submenu_page(
            'parfume-catalog',
            __('Коментари', 'parfume-catalog'),
            __('Коментари', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-comments',
            array($this, 'comments_page')
        );
        
        // Comparison submenu
        add_submenu_page(
            'parfume-catalog',
            __('Сравнение', 'parfume-catalog'),
            __('Сравнение', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog-comparison',
            array($this, 'comparison_page')
        );
        
        // Blog submenu
        add_submenu_page(
            'parfume-catalog',
            __('Блог', 'parfume-catalog'),
            __('Блог', 'parfume-catalog'),
            'edit_posts',
            'edit.php?post_type=parfume_blog'
        );
        
        // Add blog post submenu
        add_submenu_page(
            'parfume-catalog',
            __('Добави блог пост', 'parfume-catalog'),
            __('Добави блог пост', 'parfume-catalog'),
            'edit_posts',
            'post-new.php?post_type=parfume_blog'
        );
        
        // Documentation submenu
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
     * Register settings
     */
    public function register_settings() {
        register_setting('parfume_catalog_settings', 'parfume_catalog_options');
        
        // URL Settings Section
        add_settings_section(
            'parfume_catalog_urls',
            __('URL Структури', 'parfume-catalog'),
            array($this, 'urls_section_callback'),
            'parfume_catalog_settings'
        );
        
        // Archive slug
        add_settings_field(
            'archive_slug',
            __('Архивен slug за парфюми', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls',
            array('field' => 'archive_slug', 'description' => __('По подразбиране: parfiumi', 'parfume-catalog'))
        );
        
        // Tip slug
        add_settings_field(
            'tip_slug',
            __('Slug за таксономия Тип', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls',
            array('field' => 'tip_slug', 'description' => __('По подразбиране: parfiumi', 'parfume-catalog'))
        );
        
        // Vid aromat slug
        add_settings_field(
            'vid_aromat_slug',
            __('Slug за таксономия Вид аромат', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls',
            array('field' => 'vid_aromat_slug', 'description' => __('По подразбиране: parfiumi', 'parfume-catalog'))
        );
        
        // Marki slug
        add_settings_field(
            'marki_slug',
            __('Slug за таксономия Марки', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls',
            array('field' => 'marki_slug', 'description' => __('По подразбиране: parfiumi/marki', 'parfume-catalog'))
        );
        
        // Sezon slug
        add_settings_field(
            'sezon_slug',
            __('Slug за таксономия Сезон', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls',
            array('field' => 'sezon_slug', 'description' => __('По подразбиране: parfiumi/season', 'parfume-catalog'))
        );
        
        // Notki slug
        add_settings_field(
            'notki_slug',
            __('Slug за таксономия Нотки', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_urls',
            array('field' => 'notki_slug', 'description' => __('По подразбиране: notes', 'parfume-catalog'))
        );
        
        // Scraper Settings Section
        add_settings_section(
            'parfume_catalog_scraper',
            __('Product Scraper Настройки', 'parfume-catalog'),
            array($this, 'scraper_section_callback'),
            'parfume_catalog_settings'
        );
        
        // Scrape interval
        add_settings_field(
            'scrape_interval',
            __('Интервал за скрейпване (часове)', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_scraper',
            array('field' => 'scrape_interval', 'min' => 1, 'max' => 168, 'description' => __('На колко часа да се обновяват данните', 'parfume-catalog'))
        );
        
        // Batch size
        add_settings_field(
            'batch_size',
            __('Размер на партида', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_scraper',
            array('field' => 'batch_size', 'min' => 1, 'max' => 50, 'description' => __('Колко URL-а да се обработват наведнъж', 'parfume-catalog'))
        );
        
        // User agent
        add_settings_field(
            'user_agent',
            __('User Agent', 'parfume-catalog'),
            array($this, 'text_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_scraper',
            array('field' => 'user_agent', 'description' => __('User agent за scraper заявките', 'parfume-catalog'))
        );
        
        // Display Settings Section
        add_settings_section(
            'parfume_catalog_display',
            __('Настройки за визуализация', 'parfume-catalog'),
            array($this, 'display_section_callback'),
            'parfume_catalog_settings'
        );
        
        // Similar perfumes count
        add_settings_field(
            'similar_perfumes_count',
            __('Брой подобни парфюми', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_display',
            array('field' => 'similar_perfumes_count', 'min' => 1, 'max' => 12, 'description' => __('Колко подобни парфюма да се показват', 'parfume-catalog'))
        );
        
        // Recently viewed count
        add_settings_field(
            'recently_viewed_count',
            __('Брой наскоро разгледани', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_display',
            array('field' => 'recently_viewed_count', 'min' => 1, 'max' => 12, 'description' => __('Колко наскоро разгледани парфюма да се показват', 'parfume-catalog'))
        );
        
        // Same brand count
        add_settings_field(
            'same_brand_count',
            __('Брой от същата марка', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_display',
            array('field' => 'same_brand_count', 'min' => 1, 'max' => 12, 'description' => __('Колко парфюма от същата марка да се показват', 'parfume-catalog'))
        );
        
        // Comparison Settings Section
        add_settings_section(
            'parfume_catalog_comparison_settings',
            __('Настройки за сравнение', 'parfume-catalog'),
            array($this, 'comparison_section_callback'),
            'parfume_catalog_settings'
        );
        
        // Enable comparison
        add_settings_field(
            'enable_comparison',
            __('Включи сравнение на парфюми', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_comparison_settings',
            array('field' => 'enable_comparison', 'description' => __('Активира функционалността за сравнение', 'parfume-catalog'))
        );
        
        // Max comparison items
        add_settings_field(
            'max_comparison_items',
            __('Максимален брой за сравнение', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_comparison_settings',
            array('field' => 'max_comparison_items', 'min' => 2, 'max' => 10, 'description' => __('Максимален брой парфюми за сравнение', 'parfume-catalog'))
        );
        
        // Mobile Settings Section
        add_settings_section(
            'parfume_catalog_mobile',
            __('Мобилни настройки', 'parfume-catalog'),
            array($this, 'mobile_section_callback'),
            'parfume_catalog_settings'
        );
        
        // Enable mobile fixed panel
        add_settings_field(
            'enable_mobile_fixed_panel',
            __('Фиксиран панел на мобилни', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_mobile',
            array('field' => 'enable_mobile_fixed_panel', 'description' => __('Показва фиксиран панел с магазини на мобилни устройства', 'parfume-catalog'))
        );
        
        // Mobile panel z-index
        add_settings_field(
            'mobile_panel_zindex',
            __('Z-index на мобилния панел', 'parfume-catalog'),
            array($this, 'number_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_mobile',
            array('field' => 'mobile_panel_zindex', 'min' => 1, 'max' => 9999, 'description' => __('Z-index стойност за мобилния панел', 'parfume-catalog'))
        );
        
        // Comments Settings Section
        add_settings_section(
            'parfume_catalog_comments_settings',
            __('Настройки за коментари', 'parfume-catalog'),
            array($this, 'comments_settings_section_callback'),
            'parfume_catalog_settings'
        );
        
        // Enable comments
        add_settings_field(
            'enable_comments',
            __('Включи коментари/ревюта', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_comments_settings',
            array('field' => 'enable_comments', 'description' => __('Активира системата за коментари без регистрация', 'parfume-catalog'))
        );
        
        // Enable captcha
        add_settings_field(
            'enable_captcha',
            __('Включи CAPTCHA', 'parfume-catalog'),
            array($this, 'checkbox_field_callback'),
            'parfume_catalog_settings',
            'parfume_catalog_comments_settings',
            array('field' => 'enable_captcha', 'description' => __('Изисква CAPTCHA при публикуване на коментар', 'parfume-catalog'))
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Parfume Catalog', 'parfume-catalog'); ?></h1>
            
            <div class="parfume-catalog-dashboard">
                <div class="dashboard-cards">
                    <div class="card">
                        <h3><?php _e('Парфюми', 'parfume-catalog'); ?></h3>
                        <p class="count"><?php echo wp_count_posts('parfumes')->publish; ?></p>
                        <a href="<?php echo admin_url('edit.php?post_type=parfumes'); ?>" class="button"><?php _e('Управление', 'parfume-catalog'); ?></a>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Марки', 'parfume-catalog'); ?></h3>
                        <p class="count"><?php echo wp_count_terms('marki'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=marki&post_type=parfumes'); ?>" class="button"><?php _e('Управление', 'parfume-catalog'); ?></a>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Нотки', 'parfume-catalog'); ?></h3>
                        <p class="count"><?php echo wp_count_terms('notki'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=notki&post_type=parfumes'); ?>" class="button"><?php _e('Управление', 'parfume-catalog'); ?></a>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Магазини', 'parfume-catalog'); ?></h3>
                        <p class="count"><?php echo count(get_option('parfume_catalog_stores', array())); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=parfume-catalog-stores'); ?>" class="button"><?php _e('Управление', 'parfume-catalog'); ?></a>
                    </div>
                </div>
                
                <div class="recent-activity">
                    <h3><?php _e('Последна активност', 'parfume-catalog'); ?></h3>
                    <?php
                    $recent_posts = get_posts(array(
                        'post_type' => 'parfumes',
                        'numberposts' => 5,
                        'post_status' => 'publish'
                    ));
                    
                    if ($recent_posts) {
                        echo '<ul>';
                        foreach ($recent_posts as $post) {
                            echo '<li><a href="' . get_edit_post_link($post->ID) . '">' . esc_html($post->post_title) . '</a> - ' . get_the_date('d.m.Y H:i', $post->ID) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>' . __('Няма налични парфюми.', 'parfume-catalog') . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки на Parfume Catalog', 'parfume-catalog'); ?></h1>
            
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
     * Stores page
     */
    public function stores_page() {
        // This will be handled by the Stores module
        do_action('parfume_catalog_stores_admin_page');
    }
    
    /**
     * Scraper page
     */
    public function scraper_page() {
        // This will be handled by the Scraper module
        do_action('parfume_catalog_scraper_admin_page');
    }
    
    /**
     * Comments page
     */
    public function comments_page() {
        // This will be handled by the Comments module
        do_action('parfume_catalog_comments_admin_page');
    }
    
    /**
     * Comparison page
     */
    public function comparison_page() {
        // This will be handled by the Comparison module
        do_action('parfume_catalog_comparison_admin_page');
    }
    
    /**
     * Documentation page
     */
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Документация и Шорткодове', 'parfume-catalog'); ?></h1>
            
            <div class="documentation-content">
                <h2><?php _e('Налични шорткодове', 'parfume-catalog'); ?></h2>
                
                <div class="shortcode-section">
                    <h3>[parfume_filters]</h3>
                    <p><?php _e('Показва филтри за парфюми', 'parfume-catalog'); ?></p>
                    <code>[parfume_filters type="horizontal" show_search="true"]</code>
                </div>
                
                <div class="shortcode-section">
                    <h3>[parfume_comparison]</h3>
                    <p><?php _e('Показва бутон за отваряне на сравнението', 'parfume-catalog'); ?></p>
                    <code>[parfume_comparison]</code>
                </div>
                
                <div class="shortcode-section">
                    <h3>[parfume_list]</h3>
                    <p><?php _e('Показва списък с парфюми', 'parfume-catalog'); ?></p>
                    <code>[parfume_list count="12" category="damski" orderby="date"]</code>
                </div>
                
                <div class="shortcode-section">
                    <h3>[parfume_brands]</h3>
                    <p><?php _e('Показва списък с марки', 'parfume-catalog'); ?></p>
                    <code>[parfume_brands show_count="true" columns="4"]</code>
                </div>
                
                <div class="shortcode-section">
                    <h3>[parfume_notes]</h3>
                    <p><?php _e('Показва списък с нотки', 'parfume-catalog'); ?></p>
                    <code>[parfume_notes group="цветни" columns="3"]</code>
                </div>
                
                <h2><?php _e('Функции за разработчици', 'parfume-catalog'); ?></h2>
                
                <div class="function-section">
                    <h3>get_parfume_meta($post_id, $meta_key)</h3>
                    <p><?php _e('Връща мета стойност за парфюм', 'parfume-catalog'); ?></p>
                </div>
                
                <div class="function-section">
                    <h3>get_parfume_notes($post_id, $position)</h3>
                    <p><?php _e('Връща нотки за парфюм по позиция (top, middle, base)', 'parfume-catalog'); ?></p>
                </div>
                
                <div class="function-section">
                    <h3>get_similar_parfumes($post_id, $count)</h3>
                    <p><?php _e('Връща подобни парфюми', 'parfume-catalog'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Callback functions for settings fields
    public function urls_section_callback() {
        echo '<p>' . __('Настройки за URL структурите на плъгина.', 'parfume-catalog') . '</p>';
    }
    
    public function scraper_section_callback() {
        echo '<p>' . __('Настройки за Product Scraper модула.', 'parfume-catalog') . '</p>';
    }
    
    public function display_section_callback() {
        echo '<p>' . __('Настройки за визуализация на парфюми.', 'parfume-catalog') . '</p>';
    }
    
    public function comparison_section_callback() {
        echo '<p>' . __('Настройки за функционалността за сравнение.', 'parfume-catalog') . '</p>';
    }
    
    public function mobile_section_callback() {
        echo '<p>' . __('Настройки за мобилните устройства.', 'parfume-catalog') . '</p>';
    }
    
    public function comments_settings_section_callback() {
        echo '<p>' . __('Настройки за системата за коментари и ревюта.', 'parfume-catalog') . '</p>';
    }
    
    public function text_field_callback($args) {
        $options = get_option('parfume_catalog_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        
        echo '<input type="text" id="' . $args['field'] . '" name="parfume_catalog_options[' . $args['field'] . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    public function number_field_callback($args) {
        $options = get_option('parfume_catalog_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        
        echo '<input type="number" id="' . $args['field'] . '" name="parfume_catalog_options[' . $args['field'] . ']" value="' . esc_attr($value) . '" min="' . $min . '" max="' . $max . '" class="small-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    public function checkbox_field_callback($args) {
        $options = get_option('parfume_catalog_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : false;
        
        echo '<input type="checkbox" id="' . $args['field'] . '" name="parfume_catalog_options[' . $args['field'] . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="' . $args['field'] . '">' . $args['description'] . '</label>';
    }
    
    /**
     * Show admin notices
     */
    public function admin_notices() {
        if (isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Настройките са запазени.', 'parfume-catalog') . '</p></div>';
        }
    }
}