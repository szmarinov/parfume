<?php
namespace Parfume_Reviews;

/**
 * Settings class - управлява административните настройки
 * ПОПРАВЕН - URL БУТОНИТЕ ВОДЯТ ДО ПРАВИЛНИТЕ АРХИВНИ СТРАНИЦИ
 */
class Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
                    <?php $this->render_homepage_settings(); ?>
                </div>
                
                <!-- Cards Settings Tab -->
                <div id="cards" class="tab-content">
                    <h2><?php _e('Настройки на карточки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте как да изглеждат карточките на парфюмите.', 'parfume-reviews'); ?></p>
                    <?php $this->render_card_settings(); ?>
                </div>
                
                <!-- Price Tracking Tab -->
                <div id="price" class="tab-content">
                    <h2><?php _e('Проследяване на цени', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за автоматично проследяване на цени от различни магазини.', 'parfume-reviews'); ?></p>
                    <?php $this->render_price_tracking_settings(); ?>
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
     * ПОПРАВЕНА ФУНКЦИЯ - Render view archive button for main parfume archive
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
     * ПОПРАВЕНА ФУНКЦИЯ - Render view taxonomy archive button
     * ВОДИ ДО ПРАВИЛНАТА АРХИВНА СТРАНИЦА НА ТАКСОНОМИЯТА
     */
    private function render_view_taxonomy_button($taxonomy, $slug_field) {
        // Първо проверяваме дали таксономията съществува
        if (!taxonomy_exists($taxonomy)) {
            ?>
            <span class="button button-secondary button-disabled view-archive-btn">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Таксономията не съществува', 'parfume-reviews'); ?>
            </span>
            <?php
            return;
        }
        
        // Проверяваме дали има термини в таксономията
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 1
        ));
        
        if (!empty($terms) && !is_wp_error($terms)) {
            // Ако има термини, използваме първия термин за link
            $term_link = get_term_link($terms[0]);
            if (!is_wp_error($term_link)) {
                ?>
                <a href="<?php echo esc_url($term_link); ?>" target="_blank" class="button button-secondary view-archive-btn">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('Преглед на архива', 'parfume-reviews'); ?>
                </a>
                <?php
            } else {
                ?>
                <span class="button button-secondary button-disabled view-archive-btn">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('Грешка в линка', 'parfume-reviews'); ?>
                </span>
                <?php
            }
        } else {
            // Ако няма термини, създаваме generic archive URL
            $settings = get_option('parfume_reviews_settings', array());
            $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
            
            // Картира таксономиите към техните slug-ове
            $taxonomy_slugs = array(
                'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
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
                <p class="description"><?php _e('Архивът е празен - добавете термини за да видите съдържание.', 'parfume-reviews'); ?></p>
                <?php
            } else {
                ?>
                <span class="button button-secondary button-disabled view-archive-btn">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('Няма настроен URL', 'parfume-reviews'); ?>
                </span>
                <?php
            }
        }
    }
    
    public function register_settings() {
        register_setting('parfume_reviews_settings_group', 'parfume_reviews_settings', array($this, 'sanitize_settings'));
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize text fields
        $text_fields = array(
            'parfume_slug', 'brands_slug', 'notes_slug', 'perfumers_slug',
            'gender_slug', 'aroma_type_slug', 'season_slug', 'intensity_slug',
            'homepage_description', 'price_selector_parfium',
            'price_selector_douglas', 'price_selector_notino'
        );
        
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        // Sanitize numeric fields
        $numeric_fields = array(
            'price_update_interval', 'archive_posts_per_page', 'archive_grid_columns',
            'homepage_blog_count', 'homepage_blog_columns', 'homepage_featured_count',
            'homepage_featured_columns', 'homepage_latest_count'
        );
        
        foreach ($numeric_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = intval($input[$field]);
            }
        }
        
        // Sanitize boolean fields
        $boolean_fields = array(
            'show_archive_sidebar', 'card_show_image', 'card_show_brand',
            'card_show_name', 'card_show_price', 'card_show_availability', 'card_show_shipping'
        );
        
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? 1 : 0;
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
        
        return $sanitized;
    }
    
    // Render methods for individual fields...
    private function render_parfume_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        echo '<input type="text" id="parfume_slug" name="parfume_reviews_settings[parfume_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    private function render_brands_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
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
    
    private function render_show_archive_sidebar_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['show_archive_sidebar']) ? $settings['show_archive_sidebar'] : 1;
        echo '<input type="checkbox" id="show_archive_sidebar" name="parfume_reviews_settings[show_archive_sidebar]" value="1"' . checked(1, $value, false) . ' />';
        echo '<label for="show_archive_sidebar">' . __('Показвай страничен панел в архивните страници', 'parfume-reviews') . '</label>';
    }
    
    private function render_archive_posts_per_page_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['archive_posts_per_page']) ? $settings['archive_posts_per_page'] : 12;
        echo '<input type="number" id="archive_posts_per_page" name="parfume_reviews_settings[archive_posts_per_page]" value="' . esc_attr($value) . '" min="1" max="100" class="small-text" />';
        echo '<p class="description">' . __('Брой парфюми, които да се показват на една страница в архива.', 'parfume-reviews') . '</p>';
    }
    
    private function render_archive_grid_columns_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['archive_grid_columns']) ? $settings['archive_grid_columns'] : 3;
        echo '<select id="archive_grid_columns" name="parfume_reviews_settings[archive_grid_columns]">';
        for ($i = 1; $i <= 6; $i++) {
            echo '<option value="' . $i . '"' . selected($i, $value, false) . '>' . $i . ' колони</option>';
        }
        echo '</select>';
    }
    
    // Placeholder methods for other sections - should be implemented based on requirements
    private function render_homepage_settings() {
        echo '<p>Настройки за началната страница - за имплементиране</p>';
    }
    
    private function render_card_settings() {
        echo '<p>Настройки за карточки - за имплементиране</p>';
    }
    
    private function render_price_tracking_settings() {
        echo '<p>Настройки за проследяване на цени - за имплементиране</p>';
    }
    
    private function render_import_export_section() {
        echo '<p>Импорт/Експорт функционалност - за имплементиране</p>';
    }
    
    private function render_shortcodes_section() {
        echo '<p>Shortcodes документация - за имплементиране</p>';
    }
}