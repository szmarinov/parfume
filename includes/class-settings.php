<?php
namespace Parfume_Reviews;

/**
 * Settings Handler
 * 📁 Файл: includes/class-settings.php
 * ПОПРАВЕНО: brands_slug default от 'marki' на 'parfumeri'
 */
class Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_parfume_reviews_flush_rewrite_rules', array($this, 'ajax_flush_rewrite_rules'));
    }
    
    /**
     * Добавя админ меню
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Настройки', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Инициализира настройките
     */
    public function init_settings() {
        register_setting('parfume_reviews_settings', 'parfume_reviews_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
            'default' => $this->get_default_settings()
        ));
        
        // General settings section
        add_settings_section(
            'parfume_reviews_general_section',
            __('Общи настройки', 'parfume-reviews'),
            null,
            'parfume-reviews-settings'
        );
        
        // URL settings fields
        add_settings_field(
            'parfume_slug',
            __('Parfume Archive Slug', 'parfume-reviews'),
            array($this, 'render_parfume_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'brands_slug',
            __('Brands Taxonomy Slug', 'parfume-reviews'),
            array($this, 'render_brands_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'notes_slug',
            __('Notes Taxonomy Slug', 'parfume-reviews'),
            array($this, 'render_notes_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'perfumers_slug',
            __('Perfumers Taxonomy Slug', 'parfume-reviews'),
            array($this, 'render_perfumers_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'gender_slug',
            __('Gender Taxonomy Slug', 'parfume-reviews'),
            array($this, 'render_gender_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'aroma_type_slug',
            __('Aroma Type Taxonomy Slug', 'parfume-reviews'),
            array($this, 'render_aroma_type_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'season_slug',
            __('Season Taxonomy Slug', 'parfume-reviews'),
            array($this, 'render_season_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'intensity_slug',
            __('Intensity Taxonomy Slug', 'parfume-reviews'),
            array($this, 'render_intensity_slug_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        // Feature toggles
        add_settings_field(
            'enable_comparison',
            __('Функция за сравнение', 'parfume-reviews'),
            array($this, 'render_enable_comparison_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'enable_wishlist',
            __('Wish list функция', 'parfume-reviews'),
            array($this, 'render_enable_wishlist_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'enable_collections',
            __('Колекции', 'parfume-reviews'),
            array($this, 'render_enable_collections_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'enable_reviews',
            __('Потребителски отзиви', 'parfume-reviews'),
            array($this, 'render_enable_reviews_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'enable_ratings',
            __('Система за оценяване', 'parfume-reviews'),
            array($this, 'render_enable_ratings_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'enable_stores',
            __('Интеграция с магазини', 'parfume-reviews'),
            array($this, 'render_enable_stores_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        add_settings_field(
            'archive_posts_per_page',
            __('Парфюми на страница', 'parfume-reviews'),
            array($this, 'render_archive_posts_per_page_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
    }
    
    /**
     * Получава default настройки
     */
    private function get_default_settings() {
        return array(
            'parfume_slug' => 'parfiumi',
            'brands_slug' => 'parfumeri', // ПОПРАВЕНО: от 'marki' на 'parfumeri'
            'notes_slug' => 'notes',
            'perfumers_slug' => 'parfumers',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'blog_slug' => 'parfume-blog',
            'archive_posts_per_page' => 12,
            'enable_comparison' => true,
            'enable_wishlist' => true,
            'enable_collections' => true,
            'enable_reviews' => true,
            'enable_ratings' => true,
            'enable_stores' => true,
            'homepage_men_perfumes' => array(),
            'homepage_women_perfumes' => array(),
            'homepage_featured_brands' => array(),
            'homepage_arabic_perfumes' => array(),
        );
    }
    
    /**
     * Sanitize настройките
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $defaults = $this->get_default_settings();
        
        foreach ($defaults as $key => $default_value) {
            if (isset($input[$key])) {
                if (strpos($key, '_slug') !== false) {
                    // Sanitize slugs
                    $sanitized[$key] = sanitize_title($input[$key]);
                } elseif (is_bool($default_value)) {
                    // Boolean values
                    $sanitized[$key] = !empty($input[$key]) ? 1 : 0;
                } elseif (is_numeric($default_value)) {
                    // Numeric values
                    $sanitized[$key] = intval($input[$key]);
                } else {
                    // Text values
                    $sanitized[$key] = sanitize_text_field($input[$key]);
                }
            } else {
                $sanitized[$key] = $default_value;
            }
        }
        
        // Sanitize array fields
        $array_fields = array(
            'homepage_men_perfumes', 'homepage_women_perfumes',
            'homepage_featured_brands', 'homepage_arabic_perfumes'
        );
        
        foreach ($array_fields as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $sanitized[$field] = array_map('intval', $input[$field]);
            } else {
                $sanitized[$field] = array();
            }
        }
        
        // Set transient to flush rewrite rules
        set_transient('parfume_reviews_flush_rewrite_rules', true, 60);
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle settings save
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'parfume_reviews_messages',
                'parfume_reviews_message',
                __('Настройките са запазени успешно.', 'parfume-reviews'),
                'updated'
            );
            
            // Show flush rewrite rules notice
            add_settings_error(
                'parfume_reviews_messages',
                'parfume_reviews_flush_notice',
                __('Моля flush-вайте rewrite rules за да приложите промените в URL структурата.', 'parfume-reviews') . ' <button type="button" id="flush-rewrite-rules" class="button button-secondary">' . __('Flush Rewrite Rules', 'parfume-reviews') . '</button>',
                'notice-warning'
            );
        }
        
        settings_errors('parfume_reviews_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('Общи', 'parfume-reviews'); ?></a>
                <a href="#url" class="nav-tab"><?php _e('URL структура', 'parfume-reviews'); ?></a>
                <a href="#features" class="nav-tab"><?php _e('Функционалности', 'parfume-reviews'); ?></a>
            </div>
            
            <form action="options.php" method="post">
                <?php settings_fields('parfume_reviews_settings'); ?>
                
                <!-- General Settings Tab -->
                <div id="general" class="tab-content">
                    <h2><?php _e('Общи настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Основни настройки за плъгина.', 'parfume-reviews'); ?></p>
                    <?php do_settings_fields('parfume-reviews-settings', 'parfume_reviews_general_section'); ?>
                </div>
                
                <!-- URL Settings Tab -->
                <div id="url" class="tab-content">
                    <h2><?php _e('URL настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте URL структурата за различните типове страници.', 'parfume-reviews'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="parfume_slug"><?php _e('Parfume Archive Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_parfume_slug_field(); ?>
                                    <?php $this->render_view_archive_button('parfume'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="brands_slug"><?php _e('Brands Taxonomy Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_brands_slug_field(); ?>
                                    <?php $this->render_view_taxonomy_button('marki', 'brands_slug'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="notes_slug"><?php _e('Notes Taxonomy Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_notes_slug_field(); ?>
                                    <?php $this->render_view_taxonomy_button('notes', 'notes_slug'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="perfumers_slug"><?php _e('Perfumers Taxonomy Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_perfumers_slug_field(); ?>
                                    <?php $this->render_view_taxonomy_button('perfumer', 'perfumers_slug'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="gender_slug"><?php _e('Gender Taxonomy Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_gender_slug_field(); ?>
                                    <?php $this->render_view_taxonomy_button('gender', 'gender_slug'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="aroma_type_slug"><?php _e('Aroma Type Taxonomy Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_aroma_type_slug_field(); ?>
                                    <?php $this->render_view_taxonomy_button('aroma_type', 'aroma_type_slug'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="season_slug"><?php _e('Season Taxonomy Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_season_slug_field(); ?>
                                    <?php $this->render_view_taxonomy_button('season', 'season_slug'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="intensity_slug"><?php _e('Intensity Taxonomy Slug', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_intensity_slug_field(); ?>
                                    <?php $this->render_view_taxonomy_button('intensity', 'intensity_slug'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="url-structure-info">
                        <?php 
                        $settings = get_option('parfume_reviews_settings');
                        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
                        $brands_slug = isset($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri'; // ПОПРАВЕНО
                        ?>
                        <h3><?php _e('Текуща URL структура', 'parfume-reviews'); ?></h3>
                        <p><strong><?php _e('URL адресите ще бъдат структурирани както следва:', 'parfume-reviews'); ?></strong></p>
                        <ul>
                            <li><?php _e('Главен архив:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/</code></li>
                            <li><?php _e('Отделен парфюм:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/perfume-name/</code></li>
                            <li><?php _e('Архив на марки:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html($brands_slug); ?>/</code></li>
                            <li><?php _e('Архив на нотки:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes'); ?>/</code></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Features Settings Tab -->
                <div id="features" class="tab-content">
                    <h2><?php _e('Функционалности', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Включете или изключете различни функционалности на плъгина.', 'parfume-reviews'); ?></p>
                    <!-- Feature fields will be rendered here -->
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    // Render methods for individual fields...
    private function render_parfume_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        echo '<input type="text" id="parfume_slug" name="parfume_reviews_settings[parfume_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    // ПОПРАВЕНО: brands_slug default от 'marki' на 'parfumeri'
    private function render_brands_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri'; // ПОПРАВЕНО
        echo '<input type="text" id="brands_slug" name="parfume_reviews_settings[brands_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_notes_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
        echo '<input type="text" id="notes_slug" name="parfume_reviews_settings[notes_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_perfumers_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        echo '<input type="text" id="perfumers_slug" name="parfume_reviews_settings[perfumers_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_gender_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['gender_slug']) ? $settings['gender_slug'] : 'gender';
        echo '<input type="text" id="gender_slug" name="parfume_reviews_settings[gender_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_aroma_type_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type';
        echo '<input type="text" id="aroma_type_slug" name="parfume_reviews_settings[aroma_type_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_season_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['season_slug']) ? $settings['season_slug'] : 'season';
        echo '<input type="text" id="season_slug" name="parfume_reviews_settings[season_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_intensity_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity';
        echo '<input type="text" id="intensity_slug" name="parfume_reviews_settings[intensity_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_enable_comparison_field() {
        $settings = get_option('parfume_reviews_settings');
        $checked = isset($settings['enable_comparison']) ? $settings['enable_comparison'] : true;
        echo '<input type="checkbox" id="enable_comparison" name="parfume_reviews_settings[enable_comparison]" value="1" ' . checked($checked, true, false) . ' />';
        echo '<label for="enable_comparison">' . __('Включи функцията за сравнение на парфюми', 'parfume-reviews') . '</label>';
    }
    
    private function render_enable_wishlist_field() {
        $settings = get_option('parfume_reviews_settings');
        $checked = isset($settings['enable_wishlist']) ? $settings['enable_wishlist'] : true;
        echo '<input type="checkbox" id="enable_wishlist" name="parfume_reviews_settings[enable_wishlist]" value="1" ' . checked($checked, true, false) . ' />';
        echo '<label for="enable_wishlist">' . __('Включи wish list функцията', 'parfume-reviews') . '</label>';
    }
    
    private function render_enable_collections_field() {
        $settings = get_option('parfume_reviews_settings');
        $checked = isset($settings['enable_collections']) ? $settings['enable_collections'] : true;
        echo '<input type="checkbox" id="enable_collections" name="parfume_reviews_settings[enable_collections]" value="1" ' . checked($checked, true, false) . ' />';
        echo '<label for="enable_collections">' . __('Включи колекциите', 'parfume-reviews') . '</label>';
    }
    
    private function render_enable_reviews_field() {
        $settings = get_option('parfume_reviews_settings');
        $checked = isset($settings['enable_reviews']) ? $settings['enable_reviews'] : true;
        echo '<input type="checkbox" id="enable_reviews" name="parfume_reviews_settings[enable_reviews]" value="1" ' . checked($checked, true, false) . ' />';
        echo '<label for="enable_reviews">' . __('Включи потребителските отзиви', 'parfume-reviews') . '</label>';
    }
    
    private function render_enable_ratings_field() {
        $settings = get_option('parfume_reviews_settings');
        $checked = isset($settings['enable_ratings']) ? $settings['enable_ratings'] : true;
        echo '<input type="checkbox" id="enable_ratings" name="parfume_reviews_settings[enable_ratings]" value="1" ' . checked($checked, true, false) . ' />';
        echo '<label for="enable_ratings">' . __('Включи системата за оценяване', 'parfume-reviews') . '</label>';
    }
    
    private function render_enable_stores_field() {
        $settings = get_option('parfume_reviews_settings');
        $checked = isset($settings['enable_stores']) ? $settings['enable_stores'] : true;
        echo '<input type="checkbox" id="enable_stores" name="parfume_reviews_settings[enable_stores]" value="1" ' . checked($checked, true, false) . ' />';
        echo '<label for="enable_stores">' . __('Включи интеграцията с магазини', 'parfume-reviews') . '</label>';
    }
    
    private function render_archive_posts_per_page_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['archive_posts_per_page']) ? $settings['archive_posts_per_page'] : 12;
        echo '<input type="number" id="archive_posts_per_page" name="parfume_reviews_settings[archive_posts_per_page]" value="' . esc_attr($value) . '" min="1" max="100" class="small-text" />';
        echo '<p class="description">' . __('Броя парфюми които да се показват на една страница в архивите.', 'parfume-reviews') . '</p>';
    }
    
    private function render_view_archive_button($post_type) {
        if ($post_type === 'parfume') {
            $settings = get_option('parfume_reviews_settings');
            $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
            $archive_url = home_url('/' . $parfume_slug . '/');
            ?>
            <a href="<?php echo esc_url($archive_url); ?>" target="_blank" class="button button-secondary view-archive-btn">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Преглед на архива', 'parfume-reviews'); ?>
            </a>
            <?php
        }
    }
    
    private function render_view_taxonomy_button($taxonomy, $slug_field) {
        $settings = get_option('parfume_reviews_settings');
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // ПОПРАВЕНО: Картира таксономиите към техните slug-ове
        $taxonomy_slugs = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri', // ПОПРАВЕНО
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        if (isset($taxonomy_slugs[$taxonomy])) {
            $taxonomy_archive_url = home_url('/' . $parfume_slug . '/' . $taxonomy_slugs[$taxonomy] . '/');
            ?>
            <a href="<?php echo esc_url($taxonomy_archive_url); ?>" target="_blank" class="button button-secondary view-archive-btn">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Преглед на архива', 'parfume-reviews'); ?>
            </a>
            <p class="description"><?php _e('Архивът може да е празен - добавете термини за да видите съдържание.', 'parfume-reviews'); ?></p>
            <?php
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ: Форсира правилните настройки
     */
    public function force_correct_settings() {
        $correct_settings = array(
            'parfume_slug' => 'parfiumi',
            'brands_slug' => 'parfumeri', // КРИТИЧНО: Трябва да е parfumeri
            'notes_slug' => 'notes',
            'perfumers_slug' => 'parfumers',
            'gender_slug' => 'gender',
            'aroma_type_slug' => 'aroma-type',
            'season_slug' => 'season',
            'intensity_slug' => 'intensity',
            'blog_slug' => 'parfume-blog',
            'archive_posts_per_page' => 12,
            'enable_comparison' => true,
            'enable_wishlist' => true,
            'enable_collections' => true,
            'enable_reviews' => true,
            'enable_ratings' => true,
            'enable_stores' => true,
            'homepage_men_perfumes' => array(),
            'homepage_women_perfumes' => array(),
            'homepage_featured_brands' => array(),
            'homepage_arabic_perfumes' => array(),
        );
        
        // Force update всички настройки
        update_option('parfume_reviews_settings', $correct_settings);
        
        // Force flush rewrite rules
        flush_rewrite_rules(false);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Forced correct settings and flushed rewrite rules');
        }
        
        return $correct_settings;
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'parfume_page_parfume-reviews-settings') {
            return;
        }
        
        wp_enqueue_script('parfume-reviews-admin-settings', PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-settings.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
        wp_enqueue_style('parfume-reviews-admin-settings', PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-settings.css', array(), PARFUME_REVIEWS_VERSION);
        
        wp_localize_script('parfume-reviews-admin-settings', 'parfumeReviewsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume-reviews-admin-nonce'),
            'strings' => array(
                'flushing' => __('Flush-ване на rewrite rules...', 'parfume-reviews'),
                'flushed' => __('Rewrite rules са flush-нати успешно!', 'parfume-reviews'),
                'error' => __('Възникна грешка при flush-ването.', 'parfume-reviews'),
            ),
        ));
    }
    
    /**
     * AJAX handler за flush rewrite rules
     */
    public function ajax_flush_rewrite_rules() {
        check_ajax_referer('parfume-reviews-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция.', 'parfume-reviews'));
        }
        
        flush_rewrite_rules(false);
        
        wp_send_json_success(__('Rewrite rules са flush-нати успешно!', 'parfume-reviews'));
    }
}