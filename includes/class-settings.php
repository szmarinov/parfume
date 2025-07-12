<?php
namespace Parfume_Reviews;

class Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Настройки на Parfume Reviews', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('parfume_page_parfume-reviews-settings' !== $hook) {
            return;
        }
        
        // Enqueue tabs CSS and JS
        wp_enqueue_style('parfume-settings-tabs', PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-settings.css', array(), PARFUME_REVIEWS_VERSION);
        wp_enqueue_script('parfume-settings-tabs', PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-settings.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
    }
    
    public function render_settings_page() {
        if (isset($_GET['settings-updated'])) {
            // Flush rewrite rules after saving URL settings
            flush_rewrite_rules();
            add_settings_error('parfume_reviews_messages', 'parfume_reviews_message', __('Настройките са запазени.', 'parfume-reviews'), 'updated');
        }
        
        settings_errors('parfume_reviews_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper parfume-settings-tabs">
                <a href="#general" class="nav-tab"><?php _e('Общи настройки', 'parfume-reviews'); ?></a>
                <a href="#url" class="nav-tab"><?php _e('URL настройки', 'parfume-reviews'); ?></a>
                <a href="#archive" class="nav-tab"><?php _e('Архивни страници', 'parfume-reviews'); ?></a>
                <a href="#homepage" class="nav-tab"><?php _e('Начална страница', 'parfume-reviews'); ?></a>
                <a href="#cards" class="nav-tab"><?php _e('Карточки', 'parfume-reviews'); ?></a>
                <a href="#price" class="nav-tab"><?php _e('Проследяване на цени', 'parfume-reviews'); ?></a>
                <a href="#import-export" class="nav-tab"><?php _e('Импорт/Експорт', 'parfume-reviews'); ?></a>
                <a href="#shortcodes" class="nav-tab"><?php _e('Shortcodes', 'parfume-reviews'); ?></a>
            </nav>
            
            <form method="post" action="options.php">
                <?php settings_fields('parfume_reviews_settings_group'); ?>
                
                <!-- General Settings Tab -->
                <div id="general" class="tab-content">
                    <h2><?php _e('Общи настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте основните настройки за плъгина Parfume Reviews.', 'parfume-reviews'); ?></p>
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
                        ?>
                        <h3><?php _e('Текуща URL структура', 'parfume-reviews'); ?></h3>
                        <p><strong><?php _e('URL адресите ще бъдат структурирани както следва:', 'parfume-reviews'); ?></strong></p>
                        <ul>
                            <li><?php _e('Главен архив:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/</code></li>
                            <li><?php _e('Отделен парфюм:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/perfume-name/</code></li>
                            <li><?php _e('Архив на марки:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['brands_slug']) ? $settings['brands_slug'] : 'marki'); ?>/</code></li>
                            <li><?php _e('Архив на нотки:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes'); ?>/</code></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Archive Settings Tab -->
                <div id="archive" class="tab-content">
                    <h2><?php _e('Настройки на архивни страници', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за архивните страници с парфюми.', 'parfume-reviews'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Показване на страничен панел', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_show_archive_sidebar_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Брой парфюми на страница', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_archive_posts_per_page_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Брой колони в мрежата', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_archive_grid_columns_field(); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Homepage Settings Tab -->
                <div id="homepage" class="tab-content">
                    <h2><?php _e('Настройки на началната страница', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте как да изглежда началната страница /parfiumi/.', 'parfume-reviews'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Описание за началната страница', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_homepage_description_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Брой статии от блога', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_homepage_blog_count_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Брой колони за блог статии', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_homepage_blog_columns_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Брой препоръчани статии', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_homepage_featured_count_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Брой колони за препоръчани', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_homepage_featured_columns_field(); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Cards Settings Tab -->
                <div id="cards" class="tab-content">
                    <h2><?php _e('Настройки на карточките', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за това как да изглеждат карточките на парфюмите в архивните страници.', 'parfume-reviews'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Показване на цени в карточките', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_show_card_prices_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Показване на рейтинг в карточките', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_show_card_rating_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Показване на бутон за сравнение', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_show_card_comparison_field(); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Price Settings Tab -->
                <div id="price" class="tab-content">
                    <h2><?php _e('Настройки за проследяване на цени', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за автоматичното проследяване и обновяване на цените.', 'parfume-reviews'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Включване на проследяване на цени', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_enable_price_monitoring_field(); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Интервал за проверка', 'parfume-reviews'); ?></th>
                                <td><?php $this->render_price_check_interval_field(); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Import/Export Tab -->
                <div id="import-export" class="tab-content">
                    <h2><?php _e('Импорт/Експорт', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Импортирайте и експортирайте данни за парфюми.', 'parfume-reviews'); ?></p>
                    <?php $this->render_import_export_section(); ?>
                </div>
                
                <!-- Shortcodes Tab -->
                <div id="shortcodes" class="tab-content">
                    <h2><?php _e('Налични Shortcodes', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Списък с всички налични shortcodes за използване в постове и страници.', 'parfume-reviews'); ?></p>
                    <?php $this->render_shortcodes_section(); ?>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render view archive button for main parfume archive
     */
    private function render_view_archive_button($post_type) {
        $archive_url = get_post_type_archive_link($post_type);
        if ($archive_url) {
            ?>
            <a href="<?php echo esc_url($archive_url); ?>" target="_blank" class="button button-secondary view-archive-btn">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Преглед на архива', 'parfume-reviews'); ?>
            </a>
            <?php
        }
    }
    
    /**
     * Render view taxonomy archive button
     */
    private function render_view_taxonomy_button($taxonomy, $slug_field) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 1
        ));
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $term_link = get_term_link($terms[0]);
            if (!is_wp_error($term_link)) {
                ?>
                <a href="<?php echo esc_url($term_link); ?>" target="_blank" class="button button-secondary view-archive-btn">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('Преглед на архива', 'parfume-reviews'); ?>
                </a>
                <?php
            }
        } else {
            ?>
            <span class="button button-secondary button-disabled view-archive-btn">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Няма термини', 'parfume-reviews'); ?>
            </span>
            <?php
        }
    }
    
    public function register_settings() {
        register_setting('parfume_reviews_settings_group', 'parfume_reviews_settings', array($this, 'sanitize_settings'));
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize URL slugs
        $url_fields = array('parfume_slug', 'brands_slug', 'notes_slug', 'perfumers_slug', 'gender_slug', 'aroma_type_slug', 'season_slug', 'intensity_slug');
        foreach ($url_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_title($input[$field]);
            }
        }
        
        // Sanitize numeric fields
        $numeric_fields = array('archive_posts_per_page', 'archive_grid_columns', 'homepage_blog_count', 'homepage_blog_columns', 'homepage_featured_count', 'homepage_featured_columns');
        foreach ($numeric_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = absint($input[$field]);
            }
        }
        
        // Sanitize checkbox fields
        $checkbox_fields = array('show_archive_sidebar', 'show_card_prices', 'show_card_rating', 'show_card_comparison', 'enable_price_monitoring');
        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? 1 : 0;
        }
        
        // Sanitize text fields
        if (isset($input['homepage_description'])) {
            $sanitized['homepage_description'] = wp_kses_post($input['homepage_description']);
        }
        
        if (isset($input['price_check_interval'])) {
            $sanitized['price_check_interval'] = sanitize_text_field($input['price_check_interval']);
        }
        
        return $sanitized;
    }
    
    // Field Renderers - URL Settings
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
        <?php
    }
    
    public function render_notes_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
        ?>
        <input type="text" name="parfume_reviews_settings[notes_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    public function render_perfumers_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        ?>
        <input type="text" name="parfume_reviews_settings[perfumers_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    public function render_gender_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['gender_slug']) ? $settings['gender_slug'] : 'gender';
        ?>
        <input type="text" name="parfume_reviews_settings[gender_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    public function render_aroma_type_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type';
        ?>
        <input type="text" name="parfume_reviews_settings[aroma_type_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    public function render_season_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['season_slug']) ? $settings['season_slug'] : 'season';
        ?>
        <input type="text" name="parfume_reviews_settings[season_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    public function render_intensity_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity';
        ?>
        <input type="text" name="parfume_reviews_settings[intensity_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    // Archive Settings Field Renderers
    public function render_show_archive_sidebar_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['show_archive_sidebar']) ? $settings['show_archive_sidebar'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[show_archive_sidebar]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на страничния панел в архивните страници', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_archive_posts_per_page_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['archive_posts_per_page']) ? $settings['archive_posts_per_page'] : 12;
        ?>
        <input type="number" name="parfume_reviews_settings[archive_posts_per_page]" value="<?php echo esc_attr($value); ?>" class="small-text" min="1" max="100">
        <p class="description"><?php _e('Колко парфюма да се показват на една страница в архива', 'parfume-reviews'); ?></p>
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
        <?php
    }
    
    // Homepage Settings Field Renderers
    public function render_homepage_description_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_description']) ? $settings['homepage_description'] : '';
        ?>
        <textarea name="parfume_reviews_settings[homepage_description]" rows="4" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php _e('Описание което ще се показва в топа на архивната страница', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_homepage_blog_count_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_blog_count']) ? $settings['homepage_blog_count'] : 6;
        ?>
        <input type="number" name="parfume_reviews_settings[homepage_blog_count]" value="<?php echo esc_attr($value); ?>" class="small-text" min="0" max="20">
        <?php
    }
    
    public function render_homepage_blog_columns_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_blog_columns']) ? $settings['homepage_blog_columns'] : 2;
        ?>
        <select name="parfume_reviews_settings[homepage_blog_columns]">
            <option value="1" <?php selected($value, 1); ?>>1 колона</option>
            <option value="2" <?php selected($value, 2); ?>>2 колони</option>
            <option value="3" <?php selected($value, 3); ?>>3 колони</option>
        </select>
        <?php
    }
    
    public function render_homepage_featured_count_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_featured_count']) ? $settings['homepage_featured_count'] : 4;
        ?>
        <input type="number" name="parfume_reviews_settings[homepage_featured_count]" value="<?php echo esc_attr($value); ?>" class="small-text" min="0" max="20">
        <?php
    }
    
    public function render_homepage_featured_columns_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_featured_columns']) ? $settings['homepage_featured_columns'] : 2;
        ?>
        <select name="parfume_reviews_settings[homepage_featured_columns]">
            <option value="1" <?php selected($value, 1); ?>>1 колона</option>
            <option value="2" <?php selected($value, 2); ?>>2 колони</option>
            <option value="3" <?php selected($value, 3); ?>>3 колони</option>
            <option value="4" <?php selected($value, 4); ?>>4 колони</option>
        </select>
        <?php
    }
    
    // Cards Settings Field Renderers
    public function render_show_card_prices_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['show_card_prices']) ? $settings['show_card_prices'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[show_card_prices]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на цени в карточките на парфюмите', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_show_card_rating_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['show_card_rating']) ? $settings['show_card_rating'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[show_card_rating]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на рейтинг в карточките на парфюмите', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_show_card_comparison_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['show_card_comparison']) ? $settings['show_card_comparison'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[show_card_comparison]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на бутон за сравнение в карточките', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    // Price Settings Field Renderers
    public function render_enable_price_monitoring_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['enable_price_monitoring']) ? $settings['enable_price_monitoring'] : 0;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[enable_price_monitoring]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Включване на автоматично проследяване на цени', 'parfume-reviews'); ?>
        </label>
        <p class="description"><?php _e('Ако е включено, плъгинът ще проверява за промени в цените периодично', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_price_check_interval_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['price_check_interval']) ? $settings['price_check_interval'] : 'daily';
        ?>
        <select name="parfume_reviews_settings[price_check_interval]">
            <option value="hourly" <?php selected($value, 'hourly'); ?>><?php _e('Всеки час', 'parfume-reviews'); ?></option>
            <option value="daily" <?php selected($value, 'daily'); ?>><?php _e('Ежедневно', 'parfume-reviews'); ?></option>
            <option value="weekly" <?php selected($value, 'weekly'); ?>><?php _e('Седмично', 'parfume-reviews'); ?></option>
        </select>
        <?php
    }
    
    // Import/Export Section
    public function render_import_export_section() {
        ?>
        <div class="import-export-section">
            <h3><?php _e('Експорт на данни', 'parfume-reviews'); ?></h3>
            <p><?php _e('Експортирайте всички парфюми в JSON формат.', 'parfume-reviews'); ?></p>
            <a href="<?php echo admin_url('admin-post.php?action=parfume_export'); ?>" class="button button-secondary">
                <?php _e('Експорт на парфюми', 'parfume-reviews'); ?>
            </a>
            
            <h3><?php _e('Импорт на данни', 'parfume-reviews'); ?></h3>
            <p><?php _e('Импортирайте парфюми от JSON файл.', 'parfume-reviews'); ?></p>
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('parfume_import', 'parfume_import_nonce'); ?>
                <input type="hidden" name="action" value="parfume_import">
                <input type="file" name="parfume_import_file" accept=".json" required>
                <input type="submit" class="button button-primary" value="<?php _e('Импорт', 'parfume-reviews'); ?>">
            </form>
        </div>
        <?php
    }
    
    // Shortcodes Section
    public function render_shortcodes_section() {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Shortcode', 'parfume-reviews'); ?></th>
                    <th><?php _e('Описание', 'parfume-reviews'); ?></th>
                    <th><?php _e('Параметри', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[parfume_comparison]</code></td>
                    <td><?php _e('Показва линк за сравнение на парфюми.', 'parfume-reviews'); ?></td>
                    <td><em><?php _e('Няма параметри', 'parfume-reviews'); ?></em></td>
                </tr>
                <tr>
                    <td><code>[parfume_rating]</code></td>
                    <td><?php _e('Показва звездичките за рейтинг и средния рейтинг за парфюм.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>show_empty</strong>: <?php _e('Показване ако няма рейтинг (true/false, по подразбиране: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_average</strong>: <?php _e('Показване на средния рейтинг (true/false, по подразбиране: true)', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_filters]</code></td>
                    <td><?php _e('Показва формата за филтриране в архива на парфюмите.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>show_gender</strong>: <?php _e('Показване на филтър за пол (true/false, по подразбиране: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_aroma_type</strong>: <?php _e('Показване на филтър за тип арома (true/false, по подразбиране: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_brand</strong>: <?php _e('Показване на филтър за марка (true/false, по подразбиране: true)', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}