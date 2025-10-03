<?php
namespace Parfume_Reviews;

/**
 * Settings class - С НАПЪЛНО ПОДОБРЕН DEBUG СИСТЕМА
 * Проследява form submissions, validation, sanitization и записване
 * 
 * ФАЙЛ: includes/class-settings.php
 * ADVANCED DEBUG VERSION v2.0
 */
class Settings {
    
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
    
    private $debug_info = array();
    private $form_submission_log = array();
    
    public function __construct() {
        $this->log_debug('=== Settings Initialization Started ===');
        
        $this->load_settings_components();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // НОВИ DEBUG HOOKS
        add_action('update_option_parfume_reviews_settings', array($this, 'debug_option_update'), 10, 3);
        add_action('added_option', array($this, 'debug_option_added'), 10, 2);
        add_filter('pre_update_option_parfume_reviews_settings', array($this, 'debug_before_save'), 10, 3);
        
        // Hooks за form submission detection
        add_action('admin_init', array($this, 'detect_form_submission'), 1);
        
        add_action('init', array($this, 'init_scraper_cron'));
        add_action('wp_ajax_parfume_run_scraper_batch', array($this, 'ajax_run_scraper_batch'));
        add_action('wp_ajax_parfume_scraper_test_url', array($this, 'ajax_scraper_test_url'));
        add_action('wp_ajax_parfume_save_store_schema', array($this, 'ajax_save_store_schema'));
        
        add_action('admin_notices', array($this, 'show_debug_notices'));
        
        $this->log_debug('=== Settings Initialization Completed ===');
    }
    
    /**
     * НОВА ФУНКЦИЯ: Детектира form submission
     */
    public function detect_form_submission() {
        if (!isset($_POST['option_page']) || $_POST['option_page'] !== 'parfume_reviews_settings') {
            return;
        }
        
        $this->log_form_submission('Form submission detected!', array(
            'POST_keys' => array_keys($_POST),
            'POST_data_sample' => $this->get_safe_post_sample(),
            'nonce' => isset($_POST['_wpnonce']) ? 'Present' : 'Missing',
            'referer' => wp_get_referer(),
            'user' => wp_get_current_user()->user_login,
            'timestamp' => current_time('mysql')
        ));
        
        // Проверяваме nonce
        if (!isset($_POST['_wpnonce'])) {
            $this->log_form_submission('❌ CRITICAL: Nonce is missing!');
        } else {
            $nonce_check = wp_verify_nonce($_POST['_wpnonce'], 'parfume_reviews_settings-options');
            $this->log_form_submission('Nonce verification: ' . ($nonce_check ? '✅ Valid' : '❌ Invalid'));
        }
        
        // Проверяваме permissions
        if (!current_user_can('manage_options')) {
            $this->log_form_submission('❌ CRITICAL: User lacks manage_options capability!');
        } else {
            $this->log_form_submission('✅ User has manage_options capability');
        }
    }
    
    /**
     * НОВА ФУНКЦИЯ: Debug hook преди записване
     */
    public function debug_before_save($value, $old_value, $option) {
        $this->log_form_submission('=== PRE-SAVE HOOK TRIGGERED ===', array(
            'option_name' => $option,
            'old_value_keys' => is_array($old_value) ? array_keys($old_value) : 'not_array',
            'new_value_keys' => is_array($value) ? array_keys($value) : 'not_array',
            'old_value_count' => is_array($old_value) ? count($old_value) : 0,
            'new_value_count' => is_array($value) ? count($value) : 0
        ));
        
        // Сравняваме промените
        if (is_array($old_value) && is_array($value)) {
            $added = array_diff_key($value, $old_value);
            $removed = array_diff_key($old_value, $value);
            $changed = array();
            
            foreach ($value as $key => $val) {
                if (isset($old_value[$key]) && $old_value[$key] !== $val) {
                    $changed[$key] = array(
                        'old' => $old_value[$key],
                        'new' => $val
                    );
                }
            }
            
            $this->log_form_submission('Changes detected:', array(
                'added_keys' => array_keys($added),
                'removed_keys' => array_keys($removed),
                'changed_keys' => array_keys($changed),
                'changed_details' => $changed
            ));
        }
        
        return $value;
    }
    
    /**
     * НОВА ФУНКЦИЯ: Debug hook при успешно записване
     */
    public function debug_option_update($old_value, $value, $option) {
        $this->log_form_submission('✅ Option successfully updated!', array(
            'option_name' => $option,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * НОВА ФУНКЦИЯ: Debug hook при добавяне на option
     */
    public function debug_option_added($option, $value) {
        if ($option === 'parfume_reviews_settings') {
            $this->log_form_submission('✅ Option added for the first time!', array(
                'option_name' => $option,
                'value_keys' => is_array($value) ? array_keys($value) : 'not_array'
            ));
        }
    }
    
    /**
     * Получава безопасен sample от POST данни
     */
    private function get_safe_post_sample() {
        $sample = array();
        $sensitive_keys = array('_wpnonce', '_wp_http_referer', 'password', 'api_key');
        
        foreach ($_POST as $key => $value) {
            if (in_array($key, $sensitive_keys)) {
                $sample[$key] = '[HIDDEN]';
            } elseif (is_array($value)) {
                $sample[$key] = '[Array with ' . count($value) . ' items]';
            } elseif (strlen($value) > 100) {
                $sample[$key] = '[Long string: ' . strlen($value) . ' chars]';
            } else {
                $sample[$key] = $value;
            }
        }
        
        return $sample;
    }
    
    /**
     * Логира form submission събития
     */
    private function log_form_submission($message, $data = null) {
        $this->form_submission_log[] = array(
            'time' => current_time('mysql'),
            'message' => $message,
            'data' => $data
        );
        
        // Също логираме в стандартния debug log
        $this->log_debug('[FORM] ' . $message, $data);
    }
    
    /**
     * Стандартно debug логване
     */
    private function log_debug($message, $data = null) {
        $timestamp = current_time('mysql');
        $log_entry = array(
            'time' => $timestamp,
            'message' => $message,
            'data' => $data
        );
        
        $this->debug_info[] = $log_entry;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = "Parfume Reviews Settings: {$message}";
            if ($data !== null) {
                $log_message .= ' | Data: ' . print_r($data, true);
            }
            error_log($log_message);
        }
    }
    
    /**
     * Показва enhanced debug notices
     */
    public function show_debug_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'parfume-reviews-settings') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!isset($_GET['debug']) || $_GET['debug'] !== '1') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>🔍 Debug Mode:</strong> Добавете <code>&debug=1</code> в URL-а за показване на debug информация.</p>';
                echo '</div>';
            }
            return;
        }
        
        // Показваме form submission log ако има
        if (!empty($this->form_submission_log)) {
            ?>
            <div class="notice notice-warning" style="border-left: 4px solid #ff9800;">
                <h3>📝 Form Submission Log (Last Request)</h3>
                <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0; max-height: 400px; overflow-y: auto;">
                    <?php foreach ($this->form_submission_log as $entry): ?>
                        <div style="padding: 8px 0; border-bottom: 1px solid #ffe8a1;">
                            <strong style="color: #ff6f00;">[<?php echo esc_html($entry['time']); ?>]</strong>
                            <span style="margin-left: 10px;"><?php echo esc_html($entry['message']); ?></span>
                            <?php if ($entry['data'] !== null): ?>
                                <details style="margin: 5px 0;">
                                    <summary style="cursor: pointer; color: #0073aa;">Детайли</summary>
                                    <pre style="margin: 5px 0; padding: 10px; background: #fff; border: 1px solid #ddd; font-size: 11px; overflow-x: auto;"><?php echo esc_html(print_r($entry['data'], true)); ?></pre>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
        
        ?>
        <div class="notice notice-info">
            <h3>🔍 Parfume Reviews Settings Debug Information</h3>
            
            <!-- Form Submission Status -->
            <div style="background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #2196F3;">
                <h4>📋 Form Submission Status</h4>
                <?php
                $is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
                $has_settings_post = isset($_POST['option_page']) && $_POST['option_page'] === 'parfume_reviews_settings';
                ?>
                <ul style="list-style: none; padding: 0;">
                    <li>
                        <?php echo $is_post ? '✅' : '❌'; ?>
                        <strong>POST Request:</strong> <?php echo $is_post ? 'Yes' : 'No (GET)'; ?>
                    </li>
                    <li>
                        <?php echo $has_settings_post ? '✅' : '❌'; ?>
                        <strong>Settings Form:</strong> <?php echo $has_settings_post ? 'Yes' : 'No'; ?>
                    </li>
                    <li>
                        <strong>Current Settings in DB:</strong>
                        <?php
                        $current_settings = get_option('parfume_reviews_settings', array());
                        echo is_array($current_settings) ? count($current_settings) . ' entries' : 'Not an array!';
                        ?>
                    </li>
                    <li>
                        <strong>Last Modified:</strong>
                        <?php
                        global $wpdb;
                        $last_modified = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'parfume_reviews_settings_modified'");
                        echo $last_modified ? esc_html($last_modified) : 'Never or not tracked';
                        ?>
                    </li>
                </ul>
                
                <!-- Real-time settings test -->
                <div style="margin-top: 15px; padding: 10px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                    <strong>🧪 Real-time Settings Test:</strong>
                    <form method="post" action="options.php" style="margin-top: 10px;">
                        <?php settings_fields('parfume_reviews_settings'); ?>
                        <input type="hidden" name="parfume_reviews_settings[test_field]" value="test_<?php echo time(); ?>">
                        <button type="submit" class="button button-small">Test Save (adds test_field)</button>
                        <p class="description">Този бутон ще запише тестово поле. След refresh проверете дали се появява в "Текущи настройки".</p>
                    </form>
                </div>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <!-- Компоненти статус -->
                <h4>📦 Компоненти статус:</h4>
                <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Компонент</th>
                            <th>Статус</th>
                            <th>Клас</th>
                            <th>Методи</th>
                            <th>Has render_section()</th>
                            <th>Has sanitize()</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $components = array(
                            'general_settings' => 'Settings_General',
                            'url_settings' => 'Settings_URL',
                            'homepage_settings' => 'Settings_Homepage',
                            'mobile_settings' => 'Settings_Mobile',
                            'stores_settings' => 'Settings_Stores',
                            'scraper_settings' => 'Settings_Scraper',
                            'price_settings' => 'Settings_Price',
                            'import_export_settings' => 'Settings_Import_Export',
                            'shortcodes_settings' => 'Settings_Shortcodes',
                            'debug_settings' => 'Settings_Debug'
                        );
                        
                        foreach ($components as $property => $class_name) {
                            $loaded = isset($this->$property) && is_object($this->$property);
                            $full_class = 'Parfume_Reviews\\Settings\\' . $class_name;
                            $exists = class_exists($full_class);
                            
                            $methods = array();
                            $has_render = false;
                            $has_sanitize = false;
                            
                            if ($loaded) {
                                $reflection = new \ReflectionClass($this->$property);
                                $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                                $has_render = method_exists($this->$property, 'render_section');
                                $has_sanitize = method_exists($this->$property, 'sanitize');
                            }
                            
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($class_name) . '</strong></td>';
                            echo '<td>';
                            if ($loaded) {
                                echo '<span style="color: #46b450;">✅ Зареден</span>';
                            } elseif ($exists) {
                                echo '<span style="color: #ffb900;">⚠️ Класът съществува</span>';
                            } else {
                                echo '<span style="color: #dc3232;">❌ Не съществува</span>';
                            }
                            echo '</td>';
                            echo '<td><code style="font-size: 10px;">' . esc_html($full_class) . '</code></td>';
                            echo '<td>' . (count($methods)) . ' метода</td>';
                            echo '<td>' . ($has_render ? '✅' : '❌') . '</td>';
                            echo '<td>' . ($has_sanitize ? '✅' : '❌') . '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <!-- Текущи настройки -->
                <h4 style="margin-top: 20px;">⚙️ Текущи настройки в базата данни:</h4>
                <?php
                $current_settings = get_option('parfume_reviews_settings', array());
                ?>
                <div style="background: white; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <p><strong>Брой записи:</strong> <?php echo is_array($current_settings) ? count($current_settings) : 'Not an array'; ?></p>
                    <details style="margin: 10px 0;">
                        <summary style="cursor: pointer; font-weight: bold; color: #0073aa;">
                            Покажи всички настройки
                            <?php if (is_array($current_settings)): ?>
                                (<?php echo count($current_settings); ?> keys)
                            <?php endif; ?>
                        </summary>
                        <pre style="background: #f8f9fa; padding: 15px; overflow-x: auto; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto;"><?php echo esc_html(print_r($current_settings, true)); ?></pre>
                    </details>
                    
                    <?php if (is_array($current_settings) && !empty($current_settings)): ?>
                        <details style="margin: 10px 0;">
                            <summary style="cursor: pointer; font-weight: bold; color: #0073aa;">
                                Настройки по категории
                            </summary>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 10px;">
                                <?php
                                $categories = array(
                                    'General' => array('posts_per_page'),
                                    'URL' => array('parfume_slug', 'blog_slug', 'marki_slug', 'gender_slug', 'aroma_type_slug'),
                                    'Homepage' => array('homepage_hero_enabled', 'homepage_featured_enabled', 'homepage_latest_count'),
                                    'Mobile' => array('mobile_fixed_panel', 'mobile_show_close_btn', 'mobile_z_index'),
                                    'Stores' => array('available_stores'),
                                    'Scraper' => array('scraper_enabled', 'scraper_frequency'),
                                    'Price' => array('price_currency', 'price_format')
                                );
                                
                                foreach ($categories as $cat_name => $keys) {
                                    $found = array();
                                    foreach ($keys as $key) {
                                        if (isset($current_settings[$key])) {
                                            $found[$key] = $current_settings[$key];
                                        }
                                    }
                                    
                                    if (!empty($found)) {
                                        echo '<div style="background: #f0f0f0; padding: 10px; border-radius: 4px;">';
                                        echo '<strong>' . esc_html($cat_name) . ':</strong>';
                                        echo '<ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">';
                                        foreach ($found as $k => $v) {
                                            $display_val = is_array($v) ? '[Array: ' . count($v) . ' items]' : (strlen($v) > 30 ? substr($v, 0, 30) . '...' : $v);
                                            echo '<li><code>' . esc_html($k) . '</code>: ' . esc_html($display_val) . '</li>';
                                        }
                                        echo '</ul>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
                
                <!-- WordPress Hooks -->
                <h4 style="margin-top: 20px;">🔧 WordPress Hooks Status:</h4>
                <ul style="list-style: none; padding: 0;">
                    <li>
                        <?php echo has_action('admin_menu', array($this, 'add_admin_menu')) ? '✅' : '❌'; ?>
                        <strong>admin_menu:</strong> <?php echo has_action('admin_menu', array($this, 'add_admin_menu')) ? 'Registered' : 'Not registered'; ?>
                    </li>
                    <li>
                        <?php echo has_action('admin_init', array($this, 'register_settings')) ? '✅' : '❌'; ?>
                        <strong>admin_init (register_settings):</strong> <?php echo has_action('admin_init', array($this, 'register_settings')) ? 'Registered' : 'Not registered'; ?>
                    </li>
                    <li>
                        <?php echo has_action('admin_init', array($this, 'detect_form_submission')) ? '✅' : '❌'; ?>
                        <strong>admin_init (detect_form_submission):</strong> <?php echo has_action('admin_init', array($this, 'detect_form_submission')) ? 'Registered' : 'Not registered'; ?>
                    </li>
                    <li>
                        <?php echo has_filter('pre_update_option_parfume_reviews_settings', array($this, 'debug_before_save')) ? '✅' : '❌'; ?>
                        <strong>pre_update_option:</strong> <?php echo has_filter('pre_update_option_parfume_reviews_settings', array($this, 'debug_before_save')) ? 'Registered' : 'Not registered'; ?>
                    </li>
                    <li>
                        <?php echo has_action('update_option_parfume_reviews_settings', array($this, 'debug_option_update')) ? '✅' : '❌'; ?>
                        <strong>update_option:</strong> <?php echo has_action('update_option_parfume_reviews_settings', array($this, 'debug_option_update')) ? 'Registered' : 'Not registered'; ?>
                    </li>
                </ul>
                
                <!-- WordPress Settings API Info -->
                <h4 style="margin-top: 20px;">⚙️ WordPress Settings API:</h4>
                <?php
                global $wp_settings_fields, $wp_registered_settings;
                $our_setting = isset($wp_registered_settings['parfume_reviews_settings']) ? $wp_registered_settings['parfume_reviews_settings'] : null;
                ?>
                <ul style="list-style: none; padding: 0;">
                    <li>
                        <?php echo $our_setting ? '✅' : '❌'; ?>
                        <strong>Setting registered:</strong> <?php echo $our_setting ? 'Yes' : 'No'; ?>
                    </li>
                    <?php if ($our_setting): ?>
                        <li style="margin-left: 20px;">
                            <strong>Type:</strong> <?php echo isset($our_setting['type']) ? esc_html($our_setting['type']) : 'not set'; ?>
                        </li>
                        <li style="margin-left: 20px;">
                            <strong>Sanitize callback:</strong> 
                            <?php 
                            if (isset($our_setting['sanitize_callback']) && is_array($our_setting['sanitize_callback'])) {
                                echo 'Array callback (likely class method)';
                            } elseif (isset($our_setting['sanitize_callback'])) {
                                echo esc_html($our_setting['sanitize_callback']);
                            } else {
                                echo 'Not set';
                            }
                            ?>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Debug Log -->
                <h4 style="margin-top: 20px;">📝 Debug Log (Current Page Load):</h4>
                <div style="max-height: 300px; overflow-y: auto; background: white; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <?php if (!empty($this->debug_info)): ?>
                        <?php foreach ($this->debug_info as $entry): ?>
                            <div style="padding: 5px 0; border-bottom: 1px solid #eee;">
                                <strong style="color: #0073aa;">[<?php echo esc_html($entry['time']); ?>]</strong>
                                <?php echo esc_html($entry['message']); ?>
                                <?php if ($entry['data'] !== null): ?>
                                    <details style="margin: 5px 0;">
                                        <summary style="cursor: pointer; color: #666; font-size: 11px;">Show data</summary>
                                        <pre style="margin: 5px 0; padding: 5px; background: #f0f0f0; font-size: 11px; max-height: 200px; overflow: auto;"><?php echo esc_html(print_r($entry['data'], true)); ?></pre>
                                    </details>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #666;">No debug entries for this page load.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Files Check -->
                <h4 style="margin-top: 20px;">📁 Required Files:</h4>
                <?php
                $files_to_check = array(
                    'includes/settings/class-settings-general.php',
                    'includes/settings/class-settings-url.php',
                    'includes/settings/class-settings-homepage.php',
                    'includes/settings/class-settings-mobile.php',
                    'includes/settings/class-settings-stores.php',
                    'includes/settings/class-settings-scraper.php',
                    'includes/settings/class-settings-price.php',
                    'includes/settings/class-settings-import-export.php',
                    'includes/settings/class-settings-shortcodes.php',
                    'includes/settings/class-settings-debug.php'
                );
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 10px;">
                    <?php foreach ($files_to_check as $file): ?>
                        <?php 
                        $file_path = PARFUME_REVIEWS_PLUGIN_DIR . $file;
                        $exists = file_exists($file_path);
                        ?>
                        <div style="padding: 8px; background: <?php echo $exists ? '#e8f5e9' : '#ffebee'; ?>; border-radius: 4px; font-size: 12px;">
                            <?php echo $exists ? '✅' : '❌'; ?>
                            <code><?php echo esc_html(basename($file)); ?></code>
                            <?php if ($exists): ?>
                                <span style="color: #666;">(<?php echo size_format(filesize($file_path)); ?>)</span>
                            <?php else: ?>
                                <strong style="color: #dc3232;">MISSING!</strong>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Зарежда всички settings компоненти
     */
    private function load_settings_components() {
        $this->log_debug('Loading settings components...');
        
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
                $full_class_name = 'Parfume_Reviews\\Settings\\' . $class_name;
                
                if (class_exists($full_class_name)) {
                    $property_name = $this->get_property_name($class_name);
                    try {
                        $this->$property_name = new $full_class_name();
                        $this->log_debug("✅ Component loaded: {$class_name}");
                    } catch (\Exception $e) {
                        $this->log_debug("❌ Failed to initialize {$class_name}: " . $e->getMessage());
                    }
                } else {
                    $this->log_debug("❌ Class not found: {$full_class_name}");
                }
            } else {
                $this->log_debug("❌ File not found: {$file}");
            }
        }
    }
    
    private function get_property_name($class_name) {
        $property_name = str_replace('Settings_', '', $class_name);
        $property_name = strtolower(preg_replace('/([A-Z])/', '_$1', $property_name));
        $property_name = ltrim($property_name, '_');
        return $property_name . '_settings';
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Parfume Reviews настройки', 'parfume-reviews'),
            __('Настройки', 'parfume-reviews'),
            'manage_options',
            'parfume-reviews-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting(
            'parfume_reviews_settings',
            'parfume_reviews_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array()
            )
        );
        
        $this->register_component_settings();
    }
    
    private function register_component_settings() {
        $components = array(
            'general_settings', 'url_settings', 'homepage_settings',
            'mobile_settings', 'stores_settings', 'scraper_settings',
            'price_settings', 'import_export_settings', 'shortcodes_settings', 'debug_settings'
        );
        
        foreach ($components as $component) {
            if (isset($this->$component) && method_exists($this->$component, 'register_settings')) {
                $this->$component->register_settings();
            }
        }
    }
    
    public function sanitize_settings($input) {
        $this->log_form_submission('=== SANITIZE_SETTINGS CALLED ===', array(
            'input_is_array' => is_array($input),
            'input_keys' => is_array($input) ? array_keys($input) : 'not_array',
            'input_count' => is_array($input) ? count($input) : 0
        ));
        
        if (!is_array($input)) {
            $this->log_form_submission('❌ Input is not an array!');
            return array();
        }
        
        $sanitized = array();
        
        $components = array(
            'general_settings', 'url_settings', 'homepage_settings',
            'mobile_settings', 'stores_settings', 'scraper_settings', 'price_settings'
        );
        
        foreach ($components as $component) {
            if (isset($this->$component) && method_exists($this->$component, 'sanitize')) {
                $component_result = $this->$component->sanitize($input);
                $sanitized = array_merge($sanitized, $component_result);
                $this->log_form_submission("Component {$component} sanitized", array(
                    'keys_added' => array_keys($component_result)
                ));
            }
        }
        
        $this->log_form_submission('=== SANITIZE_SETTINGS COMPLETED ===', array(
            'sanitized_keys' => array_keys($sanitized),
            'sanitized_count' => count($sanitized)
        ));
        
        // Записваме timestamp на последна промяна
        update_option('parfume_reviews_settings_modified', current_time('mysql'));
        
        return $sanitized;
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'parfume_page_parfume-reviews-settings') {
            return;
        }
        
        wp_enqueue_style(
            'parfume-admin-settings',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-settings.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        wp_enqueue_script(
            'parfume-admin-settings',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-settings.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
                <?php if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')): ?>
                    <a href="?page=parfume-reviews-settings&post_type=parfume&debug=1" class="button button-secondary">
                        🔍 Debug Mode
                    </a>
                <?php endif; ?>
            </h1>
            
            <form method="post" action="options.php" id="parfume-settings-form">
                <?php
                settings_fields('parfume_reviews_settings');
                do_settings_sections('parfume-reviews-settings');
                
                $this->render_tabs();
                $this->render_tab_contents();
                
                submit_button();
                ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Log form submission attempt
            $('#parfume-settings-form').on('submit', function() {
                console.log('🔍 Form submission started');
                console.log('Form action:', $(this).attr('action'));
                console.log('Form method:', $(this).attr('method'));
                
                // Log all inputs
                var formData = $(this).serializeArray();
                console.log('Form data:', formData);
            });
        });
        </script>
        <?php
    }
    
    private function render_tabs() {
        ?>
        <div class="parfume-settings-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab">Общи</a>
                <a href="#url" class="nav-tab">URL</a>
                <a href="#homepage" class="nav-tab">Начална</a>
                <a href="#mobile" class="nav-tab">Мобилни</a>
                <a href="#stores" class="nav-tab">Магазини</a>
                <a href="#price" class="nav-tab">Цени</a>
                <a href="#import-export" class="nav-tab">Import/Export</a>
                <a href="#shortcodes" class="nav-tab">Shortcodes</a>
                <a href="#debug" class="nav-tab">Debug</a>
            </h2>
        </div>
        <?php
    }
    
    private function render_tab_contents() {
        $tabs = array(
            'general' => 'general_settings',
            'url' => 'url_settings',
            'homepage' => 'homepage_settings',
            'mobile' => 'mobile_settings',
            'stores' => 'stores_settings',
            'price' => 'price_settings',
            'import-export' => 'import_export_settings',
            'shortcodes' => 'shortcodes_settings',
            'debug' => 'debug_settings'
        );
        
        foreach ($tabs as $tab_id => $component_property) {
            echo '<div id="' . esc_attr($tab_id) . '" class="tab-content">';
            
            if (isset($this->$component_property) && method_exists($this->$component_property, 'render_section')) {
                $this->$component_property->render_section();
            } else {
                echo '<p style="color: #dc3232;">❌ Component not available: ' . esc_html($component_property) . '</p>';
            }
            
            echo '</div>';
        }
    }
    
    public function init_scraper_cron() {
        if ($this->scraper_settings && method_exists($this->scraper_settings, 'init_cron')) {
            $this->scraper_settings->init_cron();
        }
    }
    
    public function ajax_run_scraper_batch() {
        if ($this->scraper_settings && method_exists($this->scraper_settings, 'ajax_run_batch')) {
            $this->scraper_settings->ajax_run_batch();
        } else {
            wp_send_json_error(array('message' => 'Scraper not available'));
        }
    }
    
    public function ajax_scraper_test_url() {
        if ($this->stores_settings && method_exists($this->stores_settings, 'ajax_test_url')) {
            $this->stores_settings->ajax_test_url();
        } else {
            wp_send_json_error(array('message' => 'Stores settings not available'));
        }
    }
    
    public function ajax_save_store_schema() {
        if ($this->stores_settings && method_exists($this->stores_settings, 'ajax_save_schema')) {
            $this->stores_settings->ajax_save_schema();
        } else {
            wp_send_json_error(array('message' => 'Stores settings not available'));
        }
    }
}