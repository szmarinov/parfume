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
    
    public function register_settings() {
        register_setting('parfume_reviews_settings_group', 'parfume_reviews_settings', array($this, 'sanitize_settings'));
        
        // General Settings
        add_settings_section(
            'parfume_reviews_general_section',
            __('Общи настройки', 'parfume-reviews'),
            array($this, 'render_general_section'),
            'parfume-reviews-settings'
        );
        
        // URL Settings
        add_settings_section(
            'parfume_reviews_url_section',
            __('URL настройки', 'parfume-reviews'),
            array($this, 'render_url_section'),
            'parfume-reviews-settings'
        );
        
        // Archive Settings
        add_settings_section(
            'parfume_reviews_archive_section',
            __('Настройки на архивни страници', 'parfume-reviews'),
            array($this, 'render_archive_section'),
            'parfume-reviews-settings'
        );
        
        // Homepage Settings
        add_settings_section(
            'parfume_reviews_homepage_section',
            __('Настройки на началната страница', 'parfume-reviews'),
            array($this, 'render_homepage_section'),
            'parfume-reviews-settings'
        );
        
        // Card Settings
        add_settings_section(
            'parfume_reviews_card_section',
            __('Настройки на карточките', 'parfume-reviews'),
            array($this, 'render_card_section'),
            'parfume-reviews-settings'
        );
        
        // Price Monitor Settings
        add_settings_section(
            'parfume_reviews_price_section',
            __('Настройки за проследяване на цени', 'parfume-reviews'),
            array($this, 'render_price_section'),
            'parfume-reviews-settings'
        );
        
        // Import/Export section
        add_settings_section(
            'parfume_reviews_import_export_section',
            __('Импорт/Експорт', 'parfume-reviews'),
            array($this, 'render_import_export_section'),
            'parfume-reviews-settings'
        );
        
        // Shortcodes section
        add_settings_section(
            'parfume_reviews_shortcodes_section',
            __('Shortcodes', 'parfume-reviews'),
            array($this, 'render_shortcodes_section'),
            'parfume-reviews-settings'
        );
        
        // Add all settings fields
        $this->add_settings_fields();
    }
    
    private function add_settings_fields() {
        // URL Settings
        add_settings_field('parfume_slug', __('Parfume Archive Slug', 'parfume-reviews'), array($this, 'render_parfume_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('brands_slug', __('Brands Taxonomy Slug', 'parfume-reviews'), array($this, 'render_brands_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('notes_slug', __('Notes Taxonomy Slug', 'parfume-reviews'), array($this, 'render_notes_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('perfumers_slug', __('Perfumers Taxonomy Slug', 'parfume-reviews'), array($this, 'render_perfumers_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('gender_slug', __('Gender Taxonomy Slug', 'parfume-reviews'), array($this, 'render_gender_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('aroma_type_slug', __('Aroma Type Taxonomy Slug', 'parfume-reviews'), array($this, 'render_aroma_type_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('season_slug', __('Season Taxonomy Slug', 'parfume-reviews'), array($this, 'render_season_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        add_settings_field('intensity_slug', __('Intensity Taxonomy Slug', 'parfume-reviews'), array($this, 'render_intensity_slug_field'), 'parfume-reviews-settings', 'parfume_reviews_url_section');
        
        // Archive Settings
        add_settings_field('show_archive_sidebar', __('Показване на страничен панел', 'parfume-reviews'), array($this, 'render_show_archive_sidebar_field'), 'parfume-reviews-settings', 'parfume_reviews_archive_section');
        add_settings_field('archive_posts_per_page', __('Брой парфюми на страница', 'parfume-reviews'), array($this, 'render_archive_posts_per_page_field'), 'parfume-reviews-settings', 'parfume_reviews_archive_section');
        add_settings_field('archive_grid_columns', __('Брой колони в мрежата', 'parfume-reviews'), array($this, 'render_archive_grid_columns_field'), 'parfume-reviews-settings', 'parfume_reviews_archive_section');
        
        // Homepage Settings
        add_settings_field('homepage_description', __('Описание за началната страница', 'parfume-reviews'), array($this, 'render_homepage_description_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_blog_count', __('Брой статии от блога', 'parfume-reviews'), array($this, 'render_homepage_blog_count_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_blog_columns', __('Брой колони за блог статии', 'parfume-reviews'), array($this, 'render_homepage_blog_columns_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_featured_count', __('Брой препоръчани статии', 'parfume-reviews'), array($this, 'render_homepage_featured_count_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_featured_columns', __('Брой колони за препоръчани', 'parfume-reviews'), array($this, 'render_homepage_featured_columns_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_men_perfumes', __('Най-добри мъжки парфюми', 'parfume-reviews'), array($this, 'render_homepage_men_perfumes_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_women_perfumes', __('Най-търсени дамски парфюми', 'parfume-reviews'), array($this, 'render_homepage_women_perfumes_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_featured_brands', __('Известни марки', 'parfume-reviews'), array($this, 'render_homepage_featured_brands_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_arabic_perfumes', __('Арабски парфюми', 'parfume-reviews'), array($this, 'render_homepage_arabic_perfumes_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        add_settings_field('homepage_latest_count', __('Най-нови ревюта', 'parfume-reviews'), array($this, 'render_homepage_latest_count_field'), 'parfume-reviews-settings', 'parfume_reviews_homepage_section');
        
        // Card Settings
        add_settings_field('card_show_image', __('Показване на снимка', 'parfume-reviews'), array($this, 'render_card_show_image_field'), 'parfume-reviews-settings', 'parfume_reviews_card_section');
        add_settings_field('card_show_brand', __('Показване на марка', 'parfume-reviews'), array($this, 'render_card_show_brand_field'), 'parfume-reviews-settings', 'parfume_reviews_card_section');
        add_settings_field('card_show_name', __('Показване на име', 'parfume-reviews'), array($this, 'render_card_show_name_field'), 'parfume-reviews-settings', 'parfume_reviews_card_section');
        add_settings_field('card_show_price', __('Показване на цена', 'parfume-reviews'), array($this, 'render_card_show_price_field'), 'parfume-reviews-settings', 'parfume_reviews_card_section');
        add_settings_field('card_show_availability', __('Показване на наличност', 'parfume-reviews'), array($this, 'render_card_show_availability_field'), 'parfume-reviews-settings', 'parfume_reviews_card_section');
        add_settings_field('card_show_shipping', __('Показване на доставка', 'parfume-reviews'), array($this, 'render_card_show_shipping_field'), 'parfume-reviews-settings', 'parfume_reviews_card_section');
        
        // Price Monitor Settings
        add_settings_field('price_update_interval', __('Интервал за обновяване (часове)', 'parfume-reviews'), array($this, 'render_price_update_interval_field'), 'parfume-reviews-settings', 'parfume_reviews_price_section');
        add_settings_field('price_selector_parfium', __('CSS селектор за цена (parfium.bg)', 'parfume-reviews'), array($this, 'render_price_selector_parfium_field'), 'parfume-reviews-settings', 'parfume_reviews_price_section');
        add_settings_field('price_selector_douglas', __('CSS селектор за цена (douglas.bg)', 'parfume-reviews'), array($this, 'render_price_selector_douglas_field'), 'parfume-reviews-settings', 'parfume_reviews_price_section');
        add_settings_field('price_selector_notino', __('CSS селектор за цена (notino.bg)', 'parfume-reviews'), array($this, 'render_price_selector_notino_field'), 'parfume-reviews-settings', 'parfume_reviews_price_section');
    }
    
    public function sanitize_settings($input) {
        $output = array();
        
        // URL slugs
        $slug_fields = array('parfume_slug', 'brands_slug', 'notes_slug', 'perfumers_slug', 'gender_slug', 'aroma_type_slug', 'season_slug', 'intensity_slug');
        foreach ($slug_fields as $field) {
            if (isset($input[$field])) {
                $output[$field] = sanitize_title($input[$field]);
            }
        }
        
        // Numeric fields
        $numeric_fields = array('archive_posts_per_page', 'archive_grid_columns', 'homepage_blog_count', 'homepage_blog_columns', 'homepage_featured_count', 'homepage_featured_columns', 'homepage_latest_count', 'price_update_interval');
        foreach ($numeric_fields as $field) {
            if (isset($input[$field])) {
                $output[$field] = absint($input[$field]);
            }
        }
        
        // Boolean fields
        $boolean_fields = array('show_archive_sidebar', 'card_show_image', 'card_show_brand', 'card_show_name', 'card_show_price', 'card_show_availability', 'card_show_shipping');
        foreach ($boolean_fields as $field) {
            $output[$field] = isset($input[$field]) ? 1 : 0;
        }
        
        // Text fields
        $text_fields = array('price_selector_parfium', 'price_selector_douglas', 'price_selector_notino');
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $output[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        // Array fields
        $array_fields = array('homepage_men_perfumes', 'homepage_women_perfumes', 'homepage_featured_brands', 'homepage_arabic_perfumes');
        foreach ($array_fields as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $output[$field] = array_map('absint', $input[$field]);
            }
        }
        
        // Rich text fields
        if (isset($input['homepage_description'])) {
            $output['homepage_description'] = wp_kses_post($input['homepage_description']);
        }
        
        // After saving, flush rewrite rules to update permalinks
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        return $output;
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки на Parfume Reviews', 'parfume-reviews'); ?></h1>
            
            <div class="notice notice-info">
                <p><strong><?php _e('Важно:', 'parfume-reviews'); ?></strong> <?php _e('Промяната на URL slugs ще засегне всички taxonomy URLs. Уверете се, че настроите пренасочвания, ако е необходимо.', 'parfume-reviews'); ?></p>
            </div>
            
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php
                settings_fields('parfume_reviews_settings_group');
                do_settings_sections('parfume-reviews-settings');
                submit_button();
                ?>
            </form>
            
            <div class="parfume-import-export">
                <h2><?php _e('Импорт/Експорт', 'parfume-reviews'); ?></h2>
                
                <div class="import-section">
                    <h3><?php _e('Импорт на парфюми', 'parfume-reviews'); ?></h3>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('parfume_import', 'parfume_import_nonce'); ?>
                        <p>
                            <input type="file" name="parfume_import_file" accept=".json" required>
                        </p>
                        <p>
                            <button type="submit" name="parfume_import" class="button button-primary">
                                <?php _e('Импорт', 'parfume-reviews'); ?>
                            </button>
                        </p>
                    </form>
                    
                    <div class="json-format-instructions">
                        <?php echo Import_Export::get_json_format_instructions(); ?>
                    </div>
                </div>
                
                <div class="export-section">
                    <h3><?php _e('Експорт на парфюми', 'parfume-reviews'); ?></h3>
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings&parfume_export=1'), 'parfume_export'); ?>" class="button button-primary">
                            <?php _e('Експорт на всички парфюми', 'parfume-reviews'); ?>
                        </a>
                    </p>
                </div>
                
                <div class="price-status-section">
                    <h3><?php _e('Статус на цените', 'parfume-reviews'); ?></h3>
                    <?php $this->render_price_status(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_general_section() {
        echo '<p>' . __('Конфигурирайте основните настройки за плъгина Parfume Reviews.', 'parfume-reviews') . '</p>';
    }
    
    public function render_url_section() {
        echo '<p>' . __('Конфигурирайте URL структурата за различните типове страници.', 'parfume-reviews') . '</p>';
    }
    
    public function render_archive_section() {
        echo '<p>' . __('Настройки за архивните страници с парфюми.', 'parfume-reviews') . '</p>';
    }
    
    public function render_homepage_section() {
        echo '<p>' . __('Конфигурирайте как да изглежда началната страница /parfiumi/.', 'parfume-reviews') . '</p>';
    }
    
    public function render_card_section() {
        echo '<p>' . __('Настройки за това как да изглеждат карточките на парфюмите в архивните страници.', 'parfume-reviews') . '</p>';
    }
    
    public function render_price_section() {
        echo '<p>' . __('Настройки за автоматичното проследяване и обновяване на цените.', 'parfume-reviews') . '</p>';
    }
    
    // URL Field Renderers
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
        <?php
    }
    
    // Homepage Settings Field Renderers
    public function render_homepage_description_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_description']) ? $settings['homepage_description'] : '';
        ?>
        <?php wp_editor($value, 'homepage_description', array(
            'textarea_name' => 'parfume_reviews_settings[homepage_description]',
            'media_buttons' => true,
            'textarea_rows' => 10,
        )); ?>
        <p class="description"><?php _e('Текст, който ще се показва в долната част на началната страница', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_homepage_blog_count_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_blog_count']) ? $settings['homepage_blog_count'] : 6;
        ?>
        <input type="number" name="parfume_reviews_settings[homepage_blog_count]" value="<?php echo esc_attr($value); ?>" min="0" max="20">
        <?php
    }
    
    public function render_homepage_blog_columns_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_blog_columns']) ? $settings['homepage_blog_columns'] : 3;
        ?>
        <select name="parfume_reviews_settings[homepage_blog_columns]">
            <option value="2" <?php selected($value, 2); ?>>2 колони</option>
            <option value="3" <?php selected($value, 3); ?>>3 колони</option>
            <option value="4" <?php selected($value, 4); ?>>4 колони</option>
        </select>
        <?php
    }
    
    public function render_homepage_featured_count_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_featured_count']) ? $settings['homepage_featured_count'] : 4;
        ?>
        <input type="number" name="parfume_reviews_settings[homepage_featured_count]" value="<?php echo esc_attr($value); ?>" min="0" max="20">
        <?php
    }
    
    public function render_homepage_featured_columns_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_featured_columns']) ? $settings['homepage_featured_columns'] : 2;
        ?>
        <select name="parfume_reviews_settings[homepage_featured_columns]">
            <option value="2" <?php selected($value, 2); ?>>2 колони</option>
            <option value="3" <?php selected($value, 3); ?>>3 колони</option>
            <option value="4" <?php selected($value, 4); ?>>4 колони</option>
        </select>
        <?php
    }
    
    public function render_homepage_men_perfumes_field() {
        $settings = get_option('parfume_reviews_settings');
        $selected = isset($settings['homepage_men_perfumes']) ? $settings['homepage_men_perfumes'] : array();
        
        $perfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <select name="parfume_reviews_settings[homepage_men_perfumes][]" multiple size="10" style="width: 300px;">
            <?php foreach ($perfumes as $perfume): ?>
                <option value="<?php echo $perfume->ID; ?>" <?php echo in_array($perfume->ID, $selected) ? 'selected' : ''; ?>>
                    <?php echo esc_html($perfume->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Задръжте Ctrl/Cmd за множествен избор', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_homepage_women_perfumes_field() {
        $settings = get_option('parfume_reviews_settings');
        $selected = isset($settings['homepage_women_perfumes']) ? $settings['homepage_women_perfumes'] : array();
        
        $perfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <select name="parfume_reviews_settings[homepage_women_perfumes][]" multiple size="10" style="width: 300px;">
            <?php foreach ($perfumes as $perfume): ?>
                <option value="<?php echo $perfume->ID; ?>" <?php echo in_array($perfume->ID, $selected) ? 'selected' : ''; ?>>
                    <?php echo esc_html($perfume->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Задръжте Ctrl/Cmd за множествен избор', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_homepage_featured_brands_field() {
        $settings = get_option('parfume_reviews_settings');
        $selected = isset($settings['homepage_featured_brands']) ? $settings['homepage_featured_brands'] : array();
        
        $brands = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => false,
            'orderby' => 'name'
        ));
        ?>
        <select name="parfume_reviews_settings[homepage_featured_brands][]" multiple size="10" style="width: 300px;">
            <?php foreach ($brands as $brand): ?>
                <option value="<?php echo $brand->term_id; ?>" <?php echo in_array($brand->term_id, $selected) ? 'selected' : ''; ?>>
                    <?php echo esc_html($brand->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Задръжте Ctrl/Cmd за множествен избор', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_homepage_arabic_perfumes_field() {
        $settings = get_option('parfume_reviews_settings');
        $selected = isset($settings['homepage_arabic_perfumes']) ? $settings['homepage_arabic_perfumes'] : array();
        
        $perfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <select name="parfume_reviews_settings[homepage_arabic_perfumes][]" multiple size="10" style="width: 300px;">
            <?php foreach ($perfumes as $perfume): ?>
                <option value="<?php echo $perfume->ID; ?>" <?php echo in_array($perfume->ID, $selected) ? 'selected' : ''; ?>>
                    <?php echo esc_html($perfume->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Задръжте Ctrl/Cmd за множествен избор', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_homepage_latest_count_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['homepage_latest_count']) ? $settings['homepage_latest_count'] : 8;
        ?>
        <input type="number" name="parfume_reviews_settings[homepage_latest_count]" value="<?php echo esc_attr($value); ?>" min="0" max="20">
        <?php
    }
    
    // Card Settings Field Renderers
    public function render_card_show_image_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['card_show_image']) ? $settings['card_show_image'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[card_show_image]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на снимка в карточките', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_card_show_brand_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['card_show_brand']) ? $settings['card_show_brand'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[card_show_brand]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на марка в карточките', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_card_show_name_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['card_show_name']) ? $settings['card_show_name'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[card_show_name]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на име в карточките', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_card_show_price_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['card_show_price']) ? $settings['card_show_price'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[card_show_price]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на цена в карточките', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_card_show_availability_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['card_show_availability']) ? $settings['card_show_availability'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[card_show_availability]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на наличност в карточките', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    public function render_card_show_shipping_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['card_show_shipping']) ? $settings['card_show_shipping'] : 1;
        ?>
        <label>
            <input type="checkbox" name="parfume_reviews_settings[card_show_shipping]" value="1" <?php checked($value, 1); ?>>
            <?php _e('Показване на цена за доставка в карточките', 'parfume-reviews'); ?>
        </label>
        <?php
    }
    
    // Price Settings Field Renderers
    public function render_price_update_interval_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['price_update_interval']) ? $settings['price_update_interval'] : 24;
        ?>
        <input type="number" name="parfume_reviews_settings[price_update_interval]" value="<?php echo esc_attr($value); ?>" min="1" step="1">
        <p class="description"><?php _e('Колко често (в часове) да се проверяват цените от URL адресите на магазините.', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_price_selector_parfium_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['price_selector_parfium']) ? $settings['price_selector_parfium'] : '.price';
        ?>
        <input type="text" name="parfume_reviews_settings[price_selector_parfium]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('CSS селектор за намиране на цената в parfium.bg', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_price_selector_douglas_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['price_selector_douglas']) ? $settings['price_selector_douglas'] : '.price';
        ?>
        <input type="text" name="parfume_reviews_settings[price_selector_douglas]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('CSS селектор за намиране на цената в douglas.bg', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_price_selector_notino_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['price_selector_notino']) ? $settings['price_selector_notino'] : '.price';
        ?>
        <input type="text" name="parfume_reviews_settings[price_selector_notino]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('CSS селектор за намиране на цената в notino.bg', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_import_export_section() {
        echo '<p>' . __('Импорт или експорт на ревюта за парфюми в JSON формат.', 'parfume-reviews') . '</p>';
    }
    
    public function render_shortcodes_section() {
        ?>
        <p><?php _e('Използвайте тези shortcodes за показване на различни елементи в публикациите и страниците си.', 'parfume-reviews'); ?></p>
        
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th><?php _e('Shortcode', 'parfume-reviews'); ?></th>
                    <th><?php _e('Описание', 'parfume-reviews'); ?></th>
                    <th><?php _e('Параметри', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[all_brands_archive]</code></td>
                    <td><?php _e('Показва всички марки в организиран алфабетен изглед.', 'parfume-reviews'); ?></td>
                    <td><em><?php _e('Няма параметри', 'parfume-reviews'); ?></em></td>
                </tr>
                <tr>
                    <td><code>[all_notes_archive]</code></td>
                    <td><?php _e('Показва всички ароматни нотки, категоризирани по тип.', 'parfume-reviews'); ?></td>
                    <td><em><?php _e('Няма параметри', 'parfume-reviews'); ?></em></td>
                </tr>
                <tr>
                    <td><code>[all_perfumers_archive]</code></td>
                    <td><?php _e('Показва всички парфюмеристи в мрежов изглед.', 'parfume-reviews'); ?></td>
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
        
        <h3><?php _e('Текуща URL структура', 'parfume-reviews'); ?></h3>
        <div class="url-structure-info">
            <?php 
            $settings = get_option('parfume_reviews_settings');
            $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
            ?>
            <p><strong><?php _e('URL адресите ще бъдат структурирани както следва:', 'parfume-reviews'); ?></strong></p>
            <ul>
                <li><?php _e('Главен архив:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/</code></li>
                <li><?php _e('Отделен парфюм:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/perfume-name/</code></li>
                <li><?php _e('Архив на марки:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['brands_slug']) ? $settings['brands_slug'] : 'marki'); ?>/</code></li>
                <li><?php _e('Архив на нотки:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes'); ?>/</code></li>
                <li><?php _e('Архив на парфюмеристи:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers'); ?>/</code></li>
            </ul>
        </div>
        <?php
    }
    
    private function render_price_status() {
        global $wpdb;
        
        // Get perfumes without recent price updates
        $outdated_query = $wpdb->prepare("
            SELECT p.ID, p.post_title, pm.meta_value as stores
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_parfume_stores'
            WHERE p.post_type = 'parfume' 
            AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value LIKE %s)
        ", '%last_updated%');
        
        $outdated_perfumes = $wpdb->get_results($outdated_query);
        
        echo '<div class="price-status-table">';
        echo '<h4>' . __('Парфюми без актуални цени', 'parfume-reviews') . '</h4>';
        
        if (empty($outdated_perfumes)) {
            echo '<p>' . __('Всички парфюми имат актуални цени.', 'parfume-reviews') . '</p>';
        } else {
            echo '<table class="widefat">';
            echo '<thead><tr><th>' . __('Парфюм', 'parfume-reviews') . '</th><th>' . __('Статус', 'parfume-reviews') . '</th><th>' . __('Действия', 'parfume-reviews') . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($outdated_perfumes as $perfume) {
                $stores = maybe_unserialize($perfume->stores);
                $has_stores = !empty($stores) && is_array($stores);
                
                echo '<tr>';
                echo '<td><a href="' . get_edit_post_link($perfume->ID) . '">' . esc_html($perfume->post_title) . '</a></td>';
                echo '<td>';
                if (!$has_stores) {
                    echo '<span style="color: orange;">' . __('Няма добавени магазини', 'parfume-reviews') . '</span>';
                } else {
                    echo '<span style="color: red;">' . __('Цените не са актуални', 'parfume-reviews') . '</span>';
                }
                echo '</td>';
                echo '<td>';
                if ($has_stores) {
                    echo '<button class="button button-small update-all-prices" data-perfume-id="' . $perfume->ID . '">' . __('Обнови цени', 'parfume-reviews') . '</button>';
                }
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        echo '</div>';
        
        // Statistics
        $total_perfumes = wp_count_posts('parfume')->publish;
        $with_stores = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_parfume_stores' AND meta_value != ''");
        
        echo '<div class="price-statistics">';
        echo '<h4>' . __('Статистика', 'parfume-reviews') . '</h4>';
        echo '<p>' . sprintf(__('Общо парфюми: %d', 'parfume-reviews'), $total_perfumes) . '</p>';
        echo '<p>' . sprintf(__('С добавени магазини: %d', 'parfume-reviews'), $with_stores) . '</p>';
        echo '<p>' . sprintf(__('Без магазини: %d', 'parfume-reviews'), $total_perfumes - $with_stores) . '</p>';
        echo '</div>';
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook == 'parfume_page_parfume-reviews-settings') {
            wp_enqueue_media();
            
            wp_enqueue_style(
                'parfume-reviews-settings',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-settings.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-settings',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-settings.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-settings', 'parfumeSettings', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-settings-nonce'),
                'strings' => array(
                    'updating' => __('Обновяване...', 'parfume-reviews'),
                    'updated' => __('Обновено', 'parfume-reviews'),
                    'error' => __('Грешка', 'parfume-reviews'),
                ),
            ));
        }
    }
}