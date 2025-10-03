<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Debug class - Управлява debug настройките и инструментите
 * 
 * Файл: includes/settings/class-settings-debug.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_Debug {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за debug
     */
    public function register_settings() {
        // Debug Section
        add_settings_section(
            'parfume_reviews_debug_section',
            __('Debug настройки', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'debug_mode',
            __('Debug режим', 'parfume-reviews'),
            array($this, 'debug_mode_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_debug_section'
        );
        
        add_settings_field(
            'debug_log_enabled',
            __('Логиране на активност', 'parfume-reviews'),
            array($this, 'debug_log_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_debug_section'
        );
        
        add_settings_field(
            'debug_show_query_info',
            __('Показвай query информация', 'parfume-reviews'),
            array($this, 'debug_show_query_info_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_debug_section'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Настройки за дебъгиране и отстраняване на проблеми.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с debug настройки
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="debug_mode"><?php _e('Debug режим', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->debug_mode_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="debug_log_enabled"><?php _e('Логиране на активност', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->debug_log_enabled_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="debug_show_query_info"><?php _e('Показвай query информация', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->debug_show_query_info_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Debug Информация -->
        <div class="debug-info-section" style="margin-top: 30px;">
            <h3><?php _e('Системна информация', 'parfume-reviews'); ?></h3>
            <?php $this->render_system_info(); ?>
        </div>
        
        <!-- Debug Инструменти -->
        <div class="debug-tools-section" style="margin-top: 30px;">
            <h3><?php _e('Debug инструменти', 'parfume-reviews'); ?></h3>
            <?php $this->render_debug_tools(); ?>
        </div>
        
        <!-- Template Debug Информация -->
        <div class="template-debug-section" style="margin-top: 30px;">
            <h3><?php _e('Template debug информация', 'parfume-reviews'); ?></h3>
            <?php $this->render_template_debug(); ?>
        </div>
        <?php
    }
    
    /**
     * Callback за debug_mode настройката
     */
    public function debug_mode_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['debug_mode']) ? $settings['debug_mode'] : false;
        
        echo '<input type="checkbox" 
                     id="debug_mode"
                     name="parfume_reviews_settings[debug_mode]" 
                     value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Включва детайлно логиране и debug информация.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за debug_log_enabled настройката
     */
    public function debug_log_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['debug_log_enabled']) ? $settings['debug_log_enabled'] : false;
        
        echo '<input type="checkbox" 
                     id="debug_log_enabled"
                     name="parfume_reviews_settings[debug_log_enabled]" 
                     value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Логира активност в WordPress debug.log файла.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за debug_show_query_info настройката
     */
    public function debug_show_query_info_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['debug_show_query_info']) ? $settings['debug_show_query_info'] : false;
        
        echo '<input type="checkbox" 
                     id="debug_show_query_info"
                     name="parfume_reviews_settings[debug_show_query_info]" 
                     value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Показва query информация в HTML коментари (само за администратори).', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира системна информация
     */
    public function render_system_info() {
        global $wp_version;
        ?>
        <div class="system-info-box" style="background: #f1f1f1; padding: 15px; border-radius: 4px;">
            <ul style="margin: 0;">
                <li><strong><?php _e('WordPress версия:', 'parfume-reviews'); ?></strong> <?php echo esc_html($wp_version); ?></li>
                <li><strong><?php _e('PHP версия:', 'parfume-reviews'); ?></strong> <?php echo esc_html(PHP_VERSION); ?></li>
                <li><strong><?php _e('Плъгин версия:', 'parfume-reviews'); ?></strong> <?php echo esc_html(PARFUME_REVIEWS_VERSION); ?></li>
                <li><strong><?php _e('WP_DEBUG:', 'parfume-reviews'); ?></strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? __('Включен', 'parfume-reviews') : __('Изключен', 'parfume-reviews'); ?></li>
                <li><strong><?php _e('WP_DEBUG_LOG:', 'parfume-reviews'); ?></strong> <?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? __('Включен', 'parfume-reviews') : __('Изключен', 'parfume-reviews'); ?></li>
                <li><strong><?php _e('Активна тема:', 'parfume-reviews'); ?></strong> <?php echo esc_html(wp_get_theme()->get('Name')); ?></li>
                <li><strong><?php _e('Активни плъгини:', 'parfume-reviews'); ?></strong> <?php echo count(get_option('active_plugins', array())); ?></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Рендерира debug инструменти
     */
    public function render_debug_tools() {
        ?>
        <div class="debug-tools-box" style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
            <p><?php _e('Използвайте тези инструменти за диагностика на проблеми:', 'parfume-reviews'); ?></p>
            
            <div class="debug-actions" style="margin-top: 15px;">
                <a href="<?php echo esc_url(add_query_arg('parfume_debug', 'urls', admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings'))); ?>" 
                   class="button button-secondary"><?php _e('Провери URL структура', 'parfume-reviews'); ?></a>
                
                <a href="<?php echo esc_url(add_query_arg('parfume_debug', 'templates', admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings'))); ?>" 
                   class="button button-secondary"><?php _e('Провери template файлове', 'parfume-reviews'); ?></a>
                
                <a href="<?php echo esc_url(add_query_arg('parfume_debug', 'taxonomies', admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings'))); ?>" 
                   class="button button-secondary"><?php _e('Провери таксономии', 'parfume-reviews'); ?></a>
                   
                <a href="<?php echo esc_url(add_query_arg('parfume_debug', 'clear_cache', admin_url('edit.php?post_type=parfume&page=parfume-reviews-settings'))); ?>" 
                   class="button button-secondary"><?php _e('Изчисти кешове', 'parfume-reviews'); ?></a>
            </div>
            
            <?php $this->handle_debug_actions(); ?>
        </div>
        <?php
    }
    
    /**
     * Рендерира template debug информация
     */
    public function render_template_debug() {
        ?>
        <div class="template-debug-box" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <h4><?php _e('Налични template файлове:', 'parfume-reviews'); ?></h4>
            <?php
            $template_files = array(
                'single-parfume.php' => __('Single парфюм', 'parfume-reviews'),
                'archive-parfume.php' => __('Архив парфюми', 'parfume-reviews'),
                'archive-perfumer.php' => __('Архив парфюмеристи', 'parfume-reviews'),
                'taxonomy-marki.php' => __('Таксономия марки', 'parfume-reviews'),
                'taxonomy-gender.php' => __('Таксономия пол', 'parfume-reviews'),
                'taxonomy-notes.php' => __('Таксономия ноти', 'parfume-reviews'),
                'taxonomy-perfumer.php' => __('Таксономия парфюмеристи', 'parfume-reviews')
            );
            
            echo '<ul>';
            foreach ($template_files as $file => $label) {
                $plugin_path = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $file;
                $theme_path = get_template_directory() . '/' . $file;
                
                $status = '';
                if (file_exists($theme_path)) {
                    $status = '<span style="color: #0073aa;">' . __('Презаписан в темата', 'parfume-reviews') . '</span>';
                } elseif (file_exists($plugin_path)) {
                    $status = '<span style="color: #46b450;">' . __('Наличен в плъгина', 'parfume-reviews') . '</span>';
                } else {
                    $status = '<span style="color: #dc3232;">' . __('Липсва', 'parfume-reviews') . '</span>';
                }
                
                echo '<li><strong>' . esc_html($label) . '</strong> (' . esc_html($file) . '): ' . $status . '</li>';
            }
            echo '</ul>';
            ?>
            
            <h4 style="margin-top: 20px;"><?php _e('Rewrite rules статус:', 'parfume-reviews'); ?></h4>
            <?php
            $rewrite_rules = get_option('rewrite_rules');
            $parfume_rules = array();
            
            if (is_array($rewrite_rules)) {
                foreach ($rewrite_rules as $pattern => $rule) {
                    if (strpos($pattern, 'parfume') !== false || strpos($rule, 'parfume') !== false) {
                        $parfume_rules[$pattern] = $rule;
                    }
                }
            }
            
            if (!empty($parfume_rules)) {
                echo '<p style="color: #46b450;">' . sprintf(__('Намерени %d rewrite правила за парфюми.', 'parfume-reviews'), count($parfume_rules)) . '</p>';
                
                if (count($parfume_rules) <= 10) {
                    echo '<ul style="font-family: monospace; font-size: 12px;">';
                    foreach ($parfume_rules as $pattern => $rule) {
                        echo '<li><strong>' . esc_html($pattern) . '</strong> → ' . esc_html($rule) . '</li>';
                    }
                    echo '</ul>';
                }
            } else {
                echo '<p style="color: #dc3232;">' . __('Не са намерени rewrite правила за парфюми. Може да е необходимо flush на permalinks.', 'parfume-reviews') . '</p>';
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Обработва debug действия
     */
    public function handle_debug_actions() {
        if (!isset($_GET['parfume_debug']) || !current_user_can('manage_options')) {
            return;
        }
        
        $action = sanitize_text_field($_GET['parfume_debug']);
        
        switch ($action) {
            case 'urls':
                $this->debug_url_structure();
                break;
            case 'templates':
                $this->debug_templates();
                break;
            case 'taxonomies':
                $this->debug_taxonomies();
                break;
            case 'clear_cache':
                $this->clear_debug_cache();
                break;
        }
    }
    
    /**
     * Debug URL структура
     */
    private function debug_url_structure() {
        echo '<div class="notice notice-info" style="margin-top: 15px;"><p>';
        echo '<strong>' . __('URL Debug:', 'parfume-reviews') . '</strong><br>';
        
        // Проверяваме permalink структурата
        $permalink_structure = get_option('permalink_structure');
        if (empty($permalink_structure)) {
            echo '<span style="color: #dc3232;">' . __('⚠️ Permalink структурата не е конфигурирана (използват се plain permalinks)', 'parfume-reviews') . '</span><br>';
        } else {
            echo '<span style="color: #46b450;">' . __('✅ Permalink структурата е конфигурирана:', 'parfume-reviews') . ' ' . esc_html($permalink_structure) . '</span><br>';
        }
        
        // Проверяваме flush статуса
        $flush_needed = get_option('parfume_reviews_flush_rewrite_rules', false);
        if ($flush_needed) {
            echo '<span style="color: #ff922b;">' . __('⚠️ Необходимо е flush на rewrite правилата', 'parfume-reviews') . '</span><br>';
        } else {
            echo '<span style="color: #46b450;">' . __('✅ Rewrite правилата са актуални', 'parfume-reviews') . '</span><br>';
        }
        
        echo '</p></div>';
    }
    
    /**
     * Debug templates
     */
    private function debug_templates() {
        echo '<div class="notice notice-info" style="margin-top: 15px;"><p>';
        echo '<strong>' . __('Template Debug:', 'parfume-reviews') . '</strong><br>';
        
        $templates_dir = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/';
        if (is_dir($templates_dir)) {
            $templates = scandir($templates_dir);
            $template_count = count(array_filter($templates, function($file) use ($templates_dir) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'php';
            }));
            
            echo '<span style="color: #46b450;">' . sprintf(__('✅ Намерени %d template файла в плъгина', 'parfume-reviews'), $template_count) . '</span><br>';
        } else {
            echo '<span style="color: #dc3232;">' . __('❌ Templates директорията не съществува', 'parfume-reviews') . '</span><br>';
        }
        
        echo '</p></div>';
    }
    
    /**
     * Debug таксономии
     */
    private function debug_taxonomies() {
        echo '<div class="notice notice-info" style="margin-top: 15px;"><p>';
        echo '<strong>' . __('Taxonomies Debug:', 'parfume-reviews') . '</strong><br>';
        
        $parfume_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        $registered_count = 0;
        
        foreach ($parfume_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $registered_count++;
            }
        }
        
        if ($registered_count === count($parfume_taxonomies)) {
            echo '<span style="color: #46b450;">' . sprintf(__('✅ Всички %d таксономии са регистрирани', 'parfume-reviews'), $registered_count) . '</span><br>';
        } else {
            echo '<span style="color: #dc3232;">' . sprintf(__('⚠️ Регистрирани са само %d от %d таксономии', 'parfume-reviews'), $registered_count, count($parfume_taxonomies)) . '</span><br>';
        }
        
        echo '</p></div>';
    }
    
    /**
     * Изчиства debug кешове
     */
    private function clear_debug_cache() {
        // Изчистваме WordPress кешове
        wp_cache_flush();
        
        // Изчистваме rewrite rules
        flush_rewrite_rules();
        
        // Изчистваме parfume reviews специфични кешове
        if (function_exists('parfume_reviews_clear_template_caches')) {
            parfume_reviews_clear_template_caches();
        }
        
        echo '<div class="notice notice-success" style="margin-top: 15px;"><p>';
        echo '<strong>' . __('✅ Кешовете са изчистени успешно', 'parfume-reviews') . '</strong>';
        echo '</p></div>';
    }
    
    /**
     * Получава debug настройките
     */
    public function get_debug_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'debug_mode' => isset($settings['debug_mode']) ? $settings['debug_mode'] : false,
            'debug_log_enabled' => isset($settings['debug_log_enabled']) ? $settings['debug_log_enabled'] : false,
            'debug_show_query_info' => isset($settings['debug_show_query_info']) ? $settings['debug_show_query_info'] : false
        );
    }
    
    /**
     * Проверява дали debug режимът е включен
     */
    public function is_debug_mode() {
        $settings = $this->get_debug_settings();
        return !empty($settings['debug_mode']);
    }
    
    /**
     * Проверява дали логирането е включено
     */
    public function is_logging_enabled() {
        $settings = $this->get_debug_settings();
        return !empty($settings['debug_log_enabled']);
    }
    
    /**
     * Логира debug съобщение ако логирането е включено
     */
    public function log($message, $level = 'info') {
        if (!$this->is_logging_enabled()) {
            return;
        }
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("DEBUG [{$level}]: {$message}");
        }
    }
}