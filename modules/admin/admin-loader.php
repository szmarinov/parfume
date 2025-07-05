<?php
namespace ParfumeReviews\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Loader {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        $this->load_admin_modules();
    }
    
    private function load_admin_modules() {
        $modules = [
            'settings.php',
            'media-handler.php',
        ];
        
        foreach ($modules as $module) {
            $file = PARFUME_REVIEWS_PLUGIN_DIR . 'modules/admin/' . $module;
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        // Initialize admin components
        new Settings();
        new MediaHandler();
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Настройки на Parfume Reviews', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting('parfume_reviews_settings_group', 'parfume_reviews_settings', [$this, 'sanitize_settings']);
        
        // General Settings Section
        add_settings_section(
            'parfume_reviews_general_section',
            __('Общи настройки', 'parfume-reviews'),
            [$this, 'render_general_section'],
            'parfume-reviews-settings'
        );
        
        // URL Settings Section  
        add_settings_section(
            'parfume_reviews_url_section',
            __('URL настройки', 'parfume-reviews'),
            [$this, 'render_url_section'],
            'parfume-reviews-settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    private function add_settings_fields() {
        // URL Settings
        add_settings_field('parfume_slug', __('Parfume Archive Slug', 'parfume-reviews'), [$this, 'render_parfume_slug_field'], 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('brands_slug', __('Brands Taxonomy Slug', 'parfume-reviews'), [$this, 'render_brands_slug_field'], 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('notes_slug', __('Notes Taxonomy Slug', 'parfume-reviews'), [$this, 'render_notes_slug_field'], 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('perfumers_slug', __('Perfumers Taxonomy Slug', 'parfume-reviews'), [$this, 'render_perfumers_slug_field'], 'parfume-reviews-settings', 'parfume_reviews_url_section');
        
        // Archive Settings
        add_settings_field('archive_posts_per_page', __('Брой парфюми на страница', 'parfume-reviews'), [$this, 'render_archive_posts_per_page_field'], 'parfume-reviews-settings', 'parfume_reviews_general_section');
        add_settings_field('archive_grid_columns', __('Брой колони в мрежата', 'parfume-reviews'), [$this, 'render_archive_grid_columns_field'], 'parfume-reviews-settings', 'parfume_reviews_general_section');
    }
    
    public function sanitize_settings($input) {
        $output = [];
        
        // URL slugs
        $slug_fields = ['parfume_slug', 'brands_slug', 'notes_slug', 'perfumers_slug'];
        foreach ($slug_fields as $field) {
            if (isset($input[$field])) {
                $output[$field] = sanitize_title($input[$field]);
            }
        }
        
        // Numeric fields
        $numeric_fields = ['archive_posts_per_page', 'archive_grid_columns'];
        foreach ($numeric_fields as $field) {
            if (isset($input[$field])) {
                $output[$field] = absint($input[$field]);
            }
        }
        
        // Force flush rewrite rules after URL changes
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        return $output;
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки на Parfume Reviews', 'parfume-reviews'); ?></h1>
            
            <div class="notice notice-info">
                <p><strong><?php _e('Важно:', 'parfume-reviews'); ?></strong> <?php _e('Промяната на URL slugs ще засегне всички URL адреси. Уверете се, че настроите пренасочвания, ако е необходимо.', 'parfume-reviews'); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_reviews_settings_group');
                do_settings_sections('parfume-reviews-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function render_general_section() {
        echo '<p>' . __('Конфигурирайте основните настройки за плъгина.', 'parfume-reviews') . '</p>';
    }
    
    public function render_url_section() {
        echo '<p>' . __('Конфигурирайте URL структурата за различните типове страници.', 'parfume-reviews') . '</p>';
    }
    
    public function render_parfume_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[parfume_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Slug за главната архивна страница. По подразбиране: parfiumi', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_brands_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
        ?>
        <input type="text" name="parfume_reviews_settings[brands_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Slug за марките. По подразбиране: marki', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_notes_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
        ?>
        <input type="text" name="parfume_reviews_settings[notes_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Slug за нотките. По подразбиране: notes', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_perfumers_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        ?>
        <input type="text" name="parfume_reviews_settings[perfumers_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Slug за парфюмеристите. По подразбиране: parfumers', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_archive_posts_per_page_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['archive_posts_per_page']) ? $settings['archive_posts_per_page'] : 12;
        ?>
        <input type="number" name="parfume_reviews_settings[archive_posts_per_page]" value="<?php echo esc_attr($value); ?>" min="1" max="100">
        <p class="description"><?php _e('Брой парфюми за показване на страница в архивите', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_archive_grid_columns_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['archive_grid_columns']) ? $settings['archive_grid_columns'] : 3;
        ?>
        <select name="parfume_reviews_settings[archive_grid_columns]">
            <option value="2" <?php selected($value, 2); ?>>2 колони</option>
            <option value="3" <?php selected($value, 3); ?>>3 колони</option>
            <option value="4" <?php selected($value, 4); ?>>4 колони</option>
            <option value="5" <?php selected($value, 5); ?>>5 колони</option>
        </select>
        <p class="description"><?php _e('Брой колони в grid layout-а на архивните страници', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'parfume') !== false) {
            wp_enqueue_media();
            
            wp_enqueue_script(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_enqueue_style(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css',
                [],
                PARFUME_REVIEWS_VERSION
            );
        }
    }
}