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
            __('Parfume Reviews Settings', 'parfume-reviews'),
            __('Settings', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('parfume_reviews_settings_group', 'parfume_reviews_settings', array($this, 'sanitize_settings'));
        
        add_settings_section(
            'parfume_reviews_general_section',
            __('General Settings', 'parfume-reviews'),
            array($this, 'render_general_section'),
            'parfume-reviews-settings'
        );
        
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
        
        add_settings_field(
            'price_update_interval',
            __('Price Update Interval (hours)', 'parfume-reviews'),
            array($this, 'render_price_update_interval_field'),
            'parfume-reviews-settings',
            'parfume_reviews_general_section'
        );
        
        // Import/Export section
        add_settings_section(
            'parfume_reviews_import_export_section',
            __('Import/Export', 'parfume-reviews'),
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
    }
    
    public function sanitize_settings($input) {
        $output = array();
        
        if (isset($input['parfume_slug'])) {
            $output['parfume_slug'] = sanitize_title($input['parfume_slug']);
        }
        
        if (isset($input['brands_slug'])) {
            $output['brands_slug'] = sanitize_title($input['brands_slug']);
        }
        
        if (isset($input['notes_slug'])) {
            $output['notes_slug'] = sanitize_title($input['notes_slug']);
        }
        
        if (isset($input['perfumers_slug'])) {
            $output['perfumers_slug'] = sanitize_title($input['perfumers_slug']);
        }
        
        if (isset($input['gender_slug'])) {
            $output['gender_slug'] = sanitize_title($input['gender_slug']);
        }
        
        if (isset($input['aroma_type_slug'])) {
            $output['aroma_type_slug'] = sanitize_title($input['aroma_type_slug']);
        }
        
        if (isset($input['season_slug'])) {
            $output['season_slug'] = sanitize_title($input['season_slug']);
        }
        
        if (isset($input['intensity_slug'])) {
            $output['intensity_slug'] = sanitize_title($input['intensity_slug']);
        }
        
        if (isset($input['price_update_interval'])) {
            $output['price_update_interval'] = absint($input['price_update_interval']);
        }
        
        // After saving, flush rewrite rules to update permalinks
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        return $output;
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Parfume Reviews Settings', 'parfume-reviews'); ?></h1>
            
            <div class="notice notice-info">
                <p><strong><?php _e('Important:', 'parfume-reviews'); ?></strong> <?php _e('After changing URL slugs, all taxonomy URLs will follow the pattern: /parfiumi/{taxonomy-slug}/', 'parfume-reviews'); ?></p>
                <p><?php _e('For example: /parfiumi/marki/, /parfiumi/notes/, /parfiumi/parfumers/', 'parfume-reviews'); ?></p>
            </div>
            
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php
                settings_fields('parfume_reviews_settings_group');
                do_settings_sections('parfume-reviews-settings');
                submit_button();
                ?>
            </form>
            
            <div class="parfume-import-export">
                <h2><?php _e('Import/Export', 'parfume-reviews'); ?></h2>
                
                <div class="import-section">
                    <h3><?php _e('Import Perfumes', 'parfume-reviews'); ?></h3>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('parfume_import', 'parfume_import_nonce'); ?>
                        <p>
                            <input type="file" name="parfume_import_file" accept=".json" required>
                        </p>
                        <p>
                            <button type="submit" name="parfume_import" class="button button-primary">
                                <?php _e('Import', 'parfume-reviews'); ?>
                            </button>
                        </p>
                    </form>
                    
                    <div class="json-format-instructions">
                        <?php echo Import_Export::get_json_format_instructions(); ?>
                    </div>
                </div>
                
                <div class="export-section">
                    <h3><?php _e('Export Perfumes', 'parfume-reviews'); ?></h3>
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings&parfume_export=1'), 'parfume_export'); ?>" class="button button-primary">
                            <?php _e('Export All Perfumes', 'parfume-reviews'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_general_section() {
        echo '<p>' . __('Configure the URL structure and basic settings for the Parfume Reviews plugin. All taxonomy URLs will be under the main parfume archive URL.', 'parfume-reviews') . '</p>';
    }
    
    public function render_parfume_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[parfume_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('The slug for the main parfume archive page. Default: parfiumi', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_brands_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[brands_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php printf(__('The slug for the brands taxonomy. Will create URLs like: /%s/%s/brand-name/', 'parfume-reviews'), $parfume_slug, $value); ?></p>
        <?php
    }
    
    public function render_notes_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[notes_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php printf(__('The slug for the notes taxonomy. Will create URLs like: /%s/%s/note-name/', 'parfume-reviews'), $parfume_slug, $value); ?></p>
        <?php
    }
    
    public function render_perfumers_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[perfumers_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php printf(__('The slug for the perfumers taxonomy. Will create URLs like: /%s/%s/perfumer-name/', 'parfume-reviews'), $parfume_slug, $value); ?></p>
        <?php
    }
    
    public function render_gender_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['gender_slug']) ? $settings['gender_slug'] : 'gender';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[gender_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php printf(__('The slug for the gender taxonomy. Will create URLs like: /%s/%s/gender-name/', 'parfume-reviews'), $parfume_slug, $value); ?></p>
        <?php
    }
    
    public function render_aroma_type_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[aroma_type_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php printf(__('The slug for the aroma type taxonomy. Will create URLs like: /%s/%s/type-name/', 'parfume-reviews'), $parfume_slug, $value); ?></p>
        <?php
    }
    
    public function render_season_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['season_slug']) ? $settings['season_slug'] : 'season';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[season_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php printf(__('The slug for the season taxonomy. Will create URLs like: /%s/%s/season-name/', 'parfume-reviews'), $parfume_slug, $value); ?></p>
        <?php
    }
    
    public function render_intensity_slug_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity';
        $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        ?>
        <input type="text" name="parfume_reviews_settings[intensity_slug]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php printf(__('The slug for the intensity taxonomy. Will create URLs like: /%s/%s/intensity-name/', 'parfume-reviews'), $parfume_slug, $value); ?></p>
        <?php
    }
    
    public function render_price_update_interval_field() {
        $settings = get_option('parfume_reviews_settings');
        $value = isset($settings['price_update_interval']) ? $settings['price_update_interval'] : 24;
        ?>
        <input type="number" name="parfume_reviews_settings[price_update_interval]" value="<?php echo esc_attr($value); ?>" min="1" step="1">
        <p class="description"><?php _e('How often (in hours) to check for price updates from store URLs.', 'parfume-reviews'); ?></p>
        <?php
    }
    
    public function render_import_export_section() {
        echo '<p>' . __('Import or export perfume reviews in JSON format.', 'parfume-reviews') . '</p>';
    }
    
    public function render_shortcodes_section() {
        ?>
        <p><?php _e('Use these shortcodes to display various elements in your posts and pages.', 'parfume-reviews'); ?></p>
        
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th><?php _e('Shortcode', 'parfume-reviews'); ?></th>
                    <th><?php _e('Description', 'parfume-reviews'); ?></th>
                    <th><?php _e('Parameters', 'parfume-reviews'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[parfume_rating]</code></td>
                    <td><?php _e('Displays the rating stars and average rating for a perfume.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>show_empty</strong>: <?php _e('Show if no rating exists (true/false, default: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_average</strong>: <?php _e('Show the average rating number (true/false, default: true)', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_details]</code></td>
                    <td><?php _e('Displays the perfume details like gender, release year, etc.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>show_empty</strong>: <?php _e('Show if no details exist (true/false, default: true)', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_stores]</code></td>
                    <td><?php _e('Displays the stores where the perfume can be purchased.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>show_empty</strong>: <?php _e('Show if no stores exist (true/false, default: true)', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_filters]</code></td>
                    <td><?php _e('Displays the filter form for the perfume archive.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>show_gender</strong>: <?php _e('Show gender filter (true/false, default: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_aroma_type</strong>: <?php _e('Show aroma type filter (true/false, default: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_brand</strong>: <?php _e('Show brand filter (true/false, default: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_season</strong>: <?php _e('Show season filter (true/false, default: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_intensity</strong>: <?php _e('Show intensity filter (true/false, default: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_notes</strong>: <?php _e('Show notes filter (true/false, default: true)', 'parfume-reviews'); ?></li>
                            <li><strong>show_perfumer</strong>: <?php _e('Show perfumer filter (true/false, default: true)', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_similar]</code></td>
                    <td><?php _e('Displays similar perfumes based on brand, notes and gender.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>limit</strong>: <?php _e('Number of perfumes to show (default: 4)', 'parfume-reviews'); ?></li>
                            <li><strong>title</strong>: <?php _e('Section title (default: "Similar Perfumes")', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_brand_products]</code></td>
                    <td><?php _e('Displays other products from the same brand.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>limit</strong>: <?php _e('Number of perfumes to show (default: 4)', 'parfume-reviews'); ?></li>
                            <li><strong>title</strong>: <?php _e('Section title (default: "Other Products by This Brand")', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_comparison]</code></td>
                    <td><?php _e('Displays a comparison table of multiple perfumes.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>ids</strong>: <?php _e('Comma-separated list of perfume IDs to compare', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_recently_viewed]</code></td>
                    <td><?php _e('Displays recently viewed perfumes.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>limit</strong>: <?php _e('Number of perfumes to show (default: 4)', 'parfume-reviews'); ?></li>
                            <li><strong>title</strong>: <?php _e('Section title (default: "Recently Viewed")', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[parfume_collections]</code></td>
                    <td><?php _e('Displays user collections of perfumes.', 'parfume-reviews'); ?></td>
                    <td>
                        <ul>
                            <li><strong>user_id</strong>: <?php _e('User ID (default: current user)', 'parfume-reviews'); ?></li>
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php _e('URL Structure Information', 'parfume-reviews'); ?></h3>
        <div class="url-structure-info">
            <?php 
            $settings = get_option('parfume_reviews_settings');
            $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
            ?>
            <p><strong><?php _e('Current URL Structure:', 'parfume-reviews'); ?></strong></p>
            <ul>
                <li><?php _e('Main Archive:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/</code></li>
                <li><?php _e('Single Perfume:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/perfume-name/</code></li>
                <li><?php _e('Brands:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['brands_slug']) ? $settings['brands_slug'] : 'marki'); ?>/brand-name/</code></li>
                <li><?php _e('Notes:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes'); ?>/note-name/</code></li>
                <li><?php _e('Perfumers:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers'); ?>/perfumer-name/</code></li>
                <li><?php _e('Gender:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['gender_slug']) ? $settings['gender_slug'] : 'gender'); ?>/gender-name/</code></li>
                <li><?php _e('Aroma Type:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type'); ?>/type-name/</code></li>
                <li><?php _e('Season:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['season_slug']) ? $settings['season_slug'] : 'season'); ?>/season-name/</code></li>
                <li><?php _e('Intensity:', 'parfume-reviews'); ?> <code>/<?php echo esc_html($parfume_slug); ?>/<?php echo esc_html(isset($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity'); ?>/intensity-name/</code></li>
            </ul>
        </div>
        <?php
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
        }
    }
}