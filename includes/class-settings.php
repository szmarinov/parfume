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
        
        // НОВИ AJAX хендлъри за дебъг функциите
        add_action('wp_ajax_parfume_debug_taxonomy_url', array($this, 'ajax_debug_taxonomy_url'));
        add_action('wp_ajax_parfume_debug_check_urls', array($this, 'ajax_debug_check_urls'));
        add_action('wp_ajax_parfume_debug_check_templates', array($this, 'ajax_debug_check_templates'));
        add_action('wp_ajax_parfume_flush_rewrite_rules', array($this, 'ajax_flush_rewrite_rules'));
        add_action('wp_ajax_parfume_get_rewrite_rules', array($this, 'ajax_get_rewrite_rules'));
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
        <div class="wrap parfume-settings">
            <h1><?php _e('Parfume Reviews настройки', 'parfume-reviews'); ?></h1>
            
            <!-- Tab navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('Общи', 'parfume-reviews'); ?></a>
                <a href="#url" class="nav-tab"><?php _e('URL настройки', 'parfume-reviews'); ?></a>
                <a href="#homepage" class="nav-tab"><?php _e('Начална страница', 'parfume-reviews'); ?></a>
                <a href="#mobile" class="nav-tab"><?php _e('Mobile настройки', 'parfume-reviews'); ?></a>
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
                        </tbody>
                    </table>
                    
                    <?php $this->render_url_structure_info(); ?>
                </div>
                
                <!-- Homepage Tab -->
                <div id="homepage" class="tab-content">
                    <h2><?php _e('Настройки на началната страница', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Конфигурирайте съдържанието на началната страница.', 'parfume-reviews'); ?></p>
                    <?php do_settings_fields('parfume-reviews-settings', 'parfume_reviews_homepage_section'); ?>
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
                
                <!-- Mobile Settings Tab -->
                <div id="mobile" class="tab-content">
                    <h2><?php _e('Mobile настройки', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Настройки за мобилно поведение на stores панела.', 'parfume-reviews'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="mobile_fixed_panel"><?php _e('Фиксиран панел', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_mobile_fixed_panel_field(); ?>
                                    <p class="description"><?php _e('Показвай фиксиран магазин в долната част на екрана на мобилни устройства', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="mobile_show_close_btn"><?php _e('Показвай бутон "X"', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_mobile_show_close_btn_field(); ?>
                                    <p class="description"><?php _e('Позволявай скриване на "Колона 2" чрез бутон "X"', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="mobile_z_index"><?php _e('Z-Index', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_mobile_z_index_field(); ?>
                                    <p class="description"><?php _e('Z-index на "Колона 2" за избягване на припокриване с други елементи', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="mobile_bottom_offset"><?php _e('Отстояние отдолу (px)', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_mobile_bottom_offset_field(); ?>
                                    <p class="description"><?php _e('Вертикален offset при наличие на други фиксирани елементи (cookie bar, navigation и т.н.)', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scrape_interval"><?php _e('Интервал за обновяване на цени (часове)', 'parfume-reviews'); ?></label>
                                </th>
                                <td>
                                    <?php $this->render_scrape_interval_field(); ?>
                                    <p class="description"><?php _e('На колко часа се обновяват цените от магазините', 'parfume-reviews'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="debug" class="tab-content">
                    <h2><?php _e('Дебъг и диагностика', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Инструменти за диагностика и отстраняване на проблеми с плъгина.', 'parfume-reviews'); ?></p>
                    
                    <?php $this->render_taxonomy_debug_section(); ?>
                    
                    <!-- Допълнителна дебъг информация -->
                    <div class="debug-info-section" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-top: 20px;">
                        <h3><?php _e('Системна информация', 'parfume-reviews'); ?></h3>
                        
                        <?php
                        $plugin_info = array(
                            'Версия на плъгина' => PARFUME_REVIEWS_VERSION,
                            'WordPress версия' => get_bloginfo('version'),
                            'PHP версия' => PHP_VERSION,
                            'Активна тема' => get_template(),
                            'Постоянни линкове' => get_option('permalink_structure') ?: 'По подразбиране',
                            'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG ? 'Включен' : 'Изключен',
                            'Multisite' => is_multisite() ? 'Да' : 'Не'
                        );
                        ?>
                        
                        <table class="widefat" style="margin-top: 10px;">
                            <?php foreach ($plugin_info as $label => $value): ?>
                            <tr>
                                <td style="font-weight: bold; width: 200px;"><?php echo esc_html($label); ?>:</td>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <!-- Регистрирани таксономии -->
                        <h4 style="margin-top: 20px;"><?php _e('Регистрирани таксономии', 'parfume-reviews'); ?></h4>
                        <ul style="margin: 10px 0;">
                            <?php
                            $parfume_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
                            foreach ($parfume_taxonomies as $taxonomy) {
                                $exists = taxonomy_exists($taxonomy);
                                $icon = $exists ? '✅' : '❌';
                                echo "<li>{$icon} {$taxonomy}" . ($exists ? '' : ' (не съществува)') . "</li>";
                            }
                            ?>
                        </ul>
                        
                        <!-- Rewrite правила -->
                        <h4 style="margin-top: 20px;"><?php _e('Rewrite правила', 'parfume-reviews'); ?></h4>
                        <p>
                            <button type="button" id="show-rewrite-rules" class="button button-secondary">
                                <?php _e('Покажи rewrite правилата', 'parfume-reviews'); ?>
                            </button>
                        </p>
                        <div id="rewrite-rules-content" style="display: none; background: white; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                            <!-- Ще се зареди с JavaScript -->
                        </div>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching functionality
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
            });
            
            // Show first tab by default
            $('.tab-content').hide();
            $('.tab-content').first().show();
            
            // Show rewrite rules
            $('#show-rewrite-rules').on('click', function() {
                var $content = $('#rewrite-rules-content');
                if ($content.is(':visible')) {
                    $content.hide();
                    $(this).text('<?php _e('Покажи rewrite правилата', 'parfume-reviews'); ?>');
                } else {
                    $content.show().text('Зареждам...');
                    $(this).text('<?php _e('Скрий rewrite правилата', 'parfume-reviews'); ?>');
                    
                    // Заредяваме rewrite правилата
                    $.post(ajaxurl, {
                        action: 'parfume_get_rewrite_rules',
                        nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $content.text(response.data.rules);
                        } else {
                            $content.text('Грешка при зареждането на правилата.');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    // Register settings fields (placeholder methods)
    public function register_settings() {
        // Implement registration logic here
    }
    
    private function render_parfume_slug_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        echo '<input type="text" name="parfume_reviews_settings[parfume_slug]" value="' . esc_attr($value) . '" />';
    }
    
    private function render_brands_slug_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
        echo '<input type="text" name="parfume_reviews_settings[brands_slug]" value="' . esc_attr($value) . '" />';
    }
    
    private function render_notes_slug_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
        echo '<input type="text" name="parfume_reviews_settings[notes_slug]" value="' . esc_attr($value) . '" />';
    }
    
    private function render_perfumers_slug_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumeurs';
        echo '<input type="text" name="parfume_reviews_settings[perfumers_slug]" value="' . esc_attr($value) . '" />';
    }
    
    // НОВИ МЕТОДИ ЗА MOBILE НАСТРОЙКИ
    private function render_mobile_fixed_panel_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_fixed_panel']) ? $settings['mobile_fixed_panel'] : 1;
        echo '<input type="checkbox" name="parfume_reviews_settings[mobile_fixed_panel]" value="1" ' . checked(1, $value, false) . ' />';
    }
    
    private function render_mobile_show_close_btn_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_show_close_btn']) ? $settings['mobile_show_close_btn'] : 0;
        echo '<input type="checkbox" name="parfume_reviews_settings[mobile_show_close_btn]" value="1" ' . checked(1, $value, false) . ' />';
    }
    
    private function render_mobile_z_index_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_z_index']) ? $settings['mobile_z_index'] : 9999;
        echo '<input type="number" name="parfume_reviews_settings[mobile_z_index]" value="' . esc_attr($value) . '" min="1" max="99999" />';
    }
    
    private function render_mobile_bottom_offset_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['mobile_bottom_offset']) ? $settings['mobile_bottom_offset'] : 0;
        echo '<input type="number" name="parfume_reviews_settings[mobile_bottom_offset]" value="' . esc_attr($value) . '" min="0" max="200" /> px';
    }
    
    private function render_scrape_interval_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scrape_interval']) ? $settings['scrape_interval'] : 24;
        echo '<input type="number" name="parfume_reviews_settings[scrape_interval]" value="' . esc_attr($value) . '" min="1" max="168" /> часа';
    }
    
    private function render_url_structure_info() {
        // Placeholder method
        echo '<div class="url-structure-info"><p>URL structure information will be displayed here.</p></div>';
    }
    
    private function render_import_export_section() {
        // Placeholder method
        echo '<div class="import-export-section"><p>Import/Export functionality will be displayed here.</p></div>';
    }
    
    private function render_shortcodes_section() {
        // Placeholder method
        echo '<div class="shortcodes-section"><p>Available shortcodes will be displayed here.</p></div>';
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
                <span class="dashicons dashicons-warning"></span>
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
            // Ако има термини, правим URL към архива на таксономията
            $tax_obj = get_taxonomy($taxonomy);
            $archive_url = get_term_link($terms[0]);
            
            // За специални таксономии като marki и notes, които имат archive страници
            if (in_array($taxonomy, array('marki', 'notes', 'perfumer'))) {
                // Правим URL към архивната страница на таксономията
                $settings = get_option('parfume_reviews_settings', array());
                $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
                
                if ($taxonomy === 'marki') {
                    $brands_slug = !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
                    $archive_url = home_url('/' . $brands_slug . '/');
                } elseif ($taxonomy === 'notes') {
                    $notes_slug = !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
                    $archive_url = home_url('/' . $notes_slug . '/');
                } elseif ($taxonomy === 'perfumer') {
                    $perfumers_slug = !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumeurs';
                    $archive_url = home_url('/' . $perfumers_slug . '/');
                }
            }
            
            if (!is_wp_error($archive_url)) {
                ?>
                <a href="<?php echo esc_url($archive_url); ?>" target="_blank" class="button button-secondary view-archive-btn" onclick="parfumeDebugUrl('<?php echo esc_js($archive_url); ?>', '<?php echo esc_js($taxonomy); ?>')">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('Преглед на архива', 'parfume-reviews'); ?>
                </a>
                <?php
            } else {
                ?>
                <span class="button button-secondary button-disabled view-archive-btn">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Грешка в URL-а', 'parfume-reviews'); ?>
                </span>
                <?php
            }
        } else {
            // Ако няма термини, показваме warning
            ?>
            <span class="button button-secondary button-disabled view-archive-btn">
                <span class="dashicons dashicons-info"></span>
                <?php _e('Няма термини в таксономията', 'parfume-reviews'); ?>
            </span>
            <?php
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ - Debug функция за проверка на 404 грешки в таксономии
     */
    public function render_taxonomy_debug_section() {
        ?>
        <div class="taxonomy-debug-section" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-top: 20px;">
            <h3><?php _e('Дебъг информация за таксономии', 'parfume-reviews'); ?></h3>
            
            <div class="debug-actions" style="margin-bottom: 20px;">
                <button type="button" id="check-taxonomy-urls" class="button button-secondary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Провери URL-ите на таксономиите', 'parfume-reviews'); ?>
                </button>
                
                <button type="button" id="check-taxonomy-templates" class="button button-secondary">
                    <span class="dashicons dashicons-editor-code"></span>
                    <?php _e('Провери template файловете', 'parfume-reviews'); ?>
                </button>
                
                <button type="button" id="flush-taxonomy-rules" class="button button-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Изчисти rewrite правилата', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <div id="debug-results" style="background: white; padding: 15px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px; white-space: pre-wrap; display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Функция за дебъгване на URL при клик на бутон
            window.parfumeDebugUrl = function(url, taxonomy) {
                console.log('Checking URL for taxonomy:', taxonomy, url);
                
                // Проверяваме URL-а чрез AJAX
                $.post(ajaxurl, {
                    action: 'parfume_debug_taxonomy_url',
                    url: url,
                    taxonomy: taxonomy,
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        console.log('URL check result:', response.data);
                    } else {
                        console.error('URL check failed:', response.data);
                    }
                });
            };
            
            // Проверка на URL-ите
            $('#check-taxonomy-urls').on('click', function() {
                var $results = $('#debug-results');
                $results.show().text('Проверявам URL-ите на таксономиите...\n');
                
                $.post(ajaxurl, {
                    action: 'parfume_debug_check_urls',
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $results.text(response.data.message);
                    } else {
                        $results.text('Грешка: ' + response.data);
                    }
                });
            });
            
            // Проверка на template файловете
            $('#check-taxonomy-templates').on('click', function() {
                var $results = $('#debug-results');
                $results.show().text('Проверявам template файловете...\n');
                
                $.post(ajaxurl, {
                    action: 'parfume_debug_check_templates',
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $results.text(response.data.message);
                    } else {
                        $results.text('Грешка: ' + response.data);
                    }
                });
            });
            
            // Изчистване на rewrite правилата
            $('#flush-taxonomy-rules').on('click', function() {
                var $results = $('#debug-results');
                $results.show().text('Изчиствам rewrite правилата...\n');
                
                $.post(ajaxurl, {
                    action: 'parfume_flush_rewrite_rules',
                    nonce: '<?php echo wp_create_nonce('parfume_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $results.text('✅ Rewrite правилата са изчистени успешно!\n\nМоля опреснете страницата и проверете отново URL-ите.');
                    } else {
                        $results.text('Грешка: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * НОВА ФУНКЦИЯ - AJAX handler за дебъгване на URL
     */
    public function ajax_debug_taxonomy_url() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $url = sanitize_url($_POST['url']);
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        
        // Правим HTTP request към URL-а
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Грешка при заявката: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $result = array(
            'url' => $url,
            'taxonomy' => $taxonomy,
            'status_code' => $status_code,
            'is_404' => strpos($body, '404') !== false || $status_code == 404,
            'response_size' => strlen($body)
        );
        
        wp_send_json_success($result);
    }
    
    /**
     * НОВА ФУНКЦИЯ - AJAX handler за проверка на всички URL-и
     */
    public function ajax_debug_check_urls() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $results = array();
        $settings = get_option('parfume_reviews_settings', array());
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                $results[] = "❌ Таксономия '{$taxonomy}' не съществува";
                continue;
            }
            
            // Проверяваме дали има термини
            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 1));
            
            if (empty($terms) || is_wp_error($terms)) {
                $results[] = "⚠️ Таксономия '{$taxonomy}' няма термини";
                continue;
            }
            
            // Проверяваме URL на първия термин
            $term_url = get_term_link($terms[0]);
            if (is_wp_error($term_url)) {
                $results[] = "❌ Грешка в URL за таксономия '{$taxonomy}': " . $term_url->get_error_message();
            } else {
                $results[] = "✅ Таксономия '{$taxonomy}' - URL: {$term_url}";
            }
        }
        
        wp_send_json_success(array('message' => implode("\n", $results)));
    }
    
    /**
     * НОВА ФУНКЦИЯ - AJAX handler за проверка на template файлове
     */
    public function ajax_debug_check_templates() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $results = array();
        $templates_dir = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/';
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            // Проверяваме single template
            $single_template = $templates_dir . 'taxonomy-' . $taxonomy . '.php';
            if (file_exists($single_template)) {
                $results[] = "✅ Single template за '{$taxonomy}': taxonomy-{$taxonomy}.php";
            } else {
                $results[] = "❌ Липсва single template за '{$taxonomy}': taxonomy-{$taxonomy}.php";
            }
            
            // Проверяваме archive template (само за специални таксономии)
            if (in_array($taxonomy, array('marki', 'notes', 'perfumer'))) {
                $archive_template = $templates_dir . 'archive-' . $taxonomy . '.php';
                if (file_exists($archive_template)) {
                    $results[] = "✅ Archive template за '{$taxonomy}': archive-{$taxonomy}.php";
                } else {
                    $results[] = "❌ Липсва archive template за '{$taxonomy}': archive-{$taxonomy}.php";
                }
            }
        }
        
        // Проверяваме fallback templates
        $fallback_templates = array(
            'archive-taxonomy.php' => 'Общ archive template за таксономии',
            'single-perfumer.php' => 'Single perfumer template',
            'single-parfume.php' => 'Single parfume template',
            'archive-parfume.php' => 'Archive parfume template'
        );
        
        $results[] = "\n--- Fallback Templates ---";
        foreach ($fallback_templates as $template => $description) {
            $template_path = $templates_dir . $template;
            if (file_exists($template_path)) {
                $results[] = "✅ {$description}: {$template}";
            } else {
                $results[] = "❌ Липсва {$description}: {$template}";
            }
        }
        
        wp_send_json_success(array('message' => implode("\n", $results)));
    }
    
    /**
     * НОВА ФУНКЦИЯ - AJAX handler за изчистване на rewrite правила
     */
    public function ajax_flush_rewrite_rules() {
        check_ajax_referer('parfume_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Изчистваме rewrite правилата
        flush_rewrite_rules();
        
        // Задаваме флаг за следващото зареждане
        update_option('parfume_reviews_flush_rewrite_rules', true);
        
        wp_send_json_success('Rewrite правилата са изчистени успешно!');
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