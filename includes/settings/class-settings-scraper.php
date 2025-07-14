<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Scraper class - Управлява настройките за product scraper
 * 
 * Файл: includes/settings/class-settings-scraper.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_Scraper {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за scraper
     */
    public function register_settings() {
        // Scraper Section
        add_settings_section(
            'parfume_reviews_scraper_section',
            __('Product Scraper настройки', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'scraper_enabled',
            __('Активирай Scraper', 'parfume-reviews'),
            array($this, 'scraper_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'scraper_frequency',
            __('Честота на скрейпване', 'parfume-reviews'),
            array($this, 'scraper_frequency_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'scraper_timeout',
            __('Timeout', 'parfume-reviews'),
            array($this, 'scraper_timeout_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'scraper_user_agent',
            __('User Agent', 'parfume-reviews'),
            array($this, 'scraper_user_agent_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'scraper_concurrent_requests',
            __('Едновременни заявки', 'parfume-reviews'),
            array($this, 'scraper_concurrent_requests_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'scraper_delay_between_requests',
            __('Забавяне между заявките (секунди)', 'parfume-reviews'),
            array($this, 'scraper_delay_between_requests_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
        
        add_settings_field(
            'scraper_max_retries',
            __('Максимални опити', 'parfume-reviews'),
            array($this, 'scraper_max_retries_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_scraper_section'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Конфигурирайте настройките за автоматично извличане на информация за продукти от магазини.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с scraper настройки
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="scraper_enabled"><?php _e('Активирай Scraper', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->scraper_enabled_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="scraper_frequency"><?php _e('Честота на скрейпване', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->scraper_frequency_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="scraper_timeout"><?php _e('Timeout', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->scraper_timeout_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="scraper_user_agent"><?php _e('User Agent', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->scraper_user_agent_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="scraper_concurrent_requests"><?php _e('Едновременни заявки', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->scraper_concurrent_requests_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="scraper_delay_between_requests"><?php _e('Забавяне между заявките', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->scraper_delay_between_requests_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="scraper_max_retries"><?php _e('Максимални опити', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->scraper_max_retries_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php $this->render_scraper_statistics(); ?>
        <?php $this->render_scraper_queue_management(); ?>
        <?php
    }
    
    /**
     * Callback за scraper_enabled настройката
     */
    public function scraper_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scraper_enabled']) ? $settings['scraper_enabled'] : false;
        
        echo '<input type="checkbox" 
                     id="scraper_enabled"
                     name="parfume_reviews_settings[scraper_enabled]" 
                     value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Включва автоматично скрейпване на данни от продуктови страници.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за scraper_frequency настройката
     */
    public function scraper_frequency_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scraper_frequency']) ? $settings['scraper_frequency'] : 24;
        
        echo '<select id="scraper_frequency" name="parfume_reviews_settings[scraper_frequency]">';
        $frequencies = array(
            '1' => __('На час', 'parfume-reviews'),
            '6' => __('На 6 часа', 'parfume-reviews'),
            '12' => __('На 12 часа', 'parfume-reviews'),
            '24' => __('Дневно', 'parfume-reviews'),
            '48' => __('На 2 дни', 'parfume-reviews'),
            '72' => __('На 3 дни', 'parfume-reviews'),
            '168' => __('Седмично', 'parfume-reviews')
        );
        
        foreach ($frequencies as $freq => $label) {
            echo '<option value="' . esc_attr($freq) . '" ' . selected($value, $freq, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Колко често да се извършва автоматично скрейпване на продуктите.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за scraper_timeout настройката
     */
    public function scraper_timeout_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scraper_timeout']) ? $settings['scraper_timeout'] : 30;
        
        echo '<input type="number" 
                     id="scraper_timeout"
                     name="parfume_reviews_settings[scraper_timeout]" 
                     value="' . esc_attr($value) . '" 
                     min="5" 
                     max="120" 
                     class="small-text" />';
        echo ' ' . __('секунди', 'parfume-reviews');
        echo '<p class="description">' . __('Максимално време за изчакване на отговор от магазина.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за scraper_user_agent настройката
     */
    public function scraper_user_agent_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scraper_user_agent']) ? $settings['scraper_user_agent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        
        echo '<textarea id="scraper_user_agent"
                        name="parfume_reviews_settings[scraper_user_agent]" 
                        rows="3" 
                        cols="50" 
                        class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('User Agent string, който ще се използва при HTTP заявките.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за scraper_concurrent_requests настройката
     */
    public function scraper_concurrent_requests_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scraper_concurrent_requests']) ? $settings['scraper_concurrent_requests'] : 3;
        
        echo '<input type="number" 
                     id="scraper_concurrent_requests"
                     name="parfume_reviews_settings[scraper_concurrent_requests]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="10" 
                     class="small-text" />';
        echo '<p class="description">' . __('Брой едновременни заявки към различни магазини.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за scraper_delay_between_requests настройката
     */
    public function scraper_delay_between_requests_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scraper_delay_between_requests']) ? $settings['scraper_delay_between_requests'] : 2;
        
        echo '<input type="number" 
                     id="scraper_delay_between_requests"
                     name="parfume_reviews_settings[scraper_delay_between_requests]" 
                     value="' . esc_attr($value) . '" 
                     min="0" 
                     max="30" 
                     class="small-text" />';
        echo ' ' . __('секунди', 'parfume-reviews');
        echo '<p class="description">' . __('Забавяне между заявките към същия магазин (за да не натоварваме сървъра).', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за scraper_max_retries настройката
     */
    public function scraper_max_retries_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['scraper_max_retries']) ? $settings['scraper_max_retries'] : 3;
        
        echo '<input type="number" 
                     id="scraper_max_retries"
                     name="parfume_reviews_settings[scraper_max_retries]" 
                     value="' . esc_attr($value) . '" 
                     min="1" 
                     max="10" 
                     class="small-text" />';
        echo '<p class="description">' . __('Брой опити при неуспешна заявка преди да се счита за неуспешна.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира статистики за scraper
     */
    private function render_scraper_statistics() {
        $stats = $this->get_scraper_statistics();
        ?>
        <div class="scraper-stats-section" style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h3><?php _e('Статистики за Scraper', 'parfume-reviews'); ?></h3>
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="stat-item">
                    <strong><?php _e('Общо продукти:', 'parfume-reviews'); ?></strong>
                    <span><?php echo esc_html($stats['total_products']); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php _e('Продукти със scraping:', 'parfume-reviews'); ?></strong>
                    <span><?php echo esc_html($stats['products_with_scraping']); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php _e('Последни 24ч скрейпвания:', 'parfume-reviews'); ?></strong>
                    <span><?php echo esc_html($stats['recent_scrapes']); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php _e('Неуспешни опити:', 'parfume-reviews'); ?></strong>
                    <span><?php echo esc_html($stats['failed_scrapes']); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира управление на scraper queue
     */
    private function render_scraper_queue_management() {
        ?>
        <div class="scraper-queue-section" style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px;">
            <h3><?php _e('Управление на Scraper Queue', 'parfume-reviews'); ?></h3>
            <div class="queue-actions" style="margin-bottom: 15px;">
                <button type="button" class="button button-primary" id="run-scraper-now">
                    <?php _e('Стартирай скрейпване сега', 'parfume-reviews'); ?>
                </button>
                <button type="button" class="button button-secondary" id="clear-scraper-queue">
                    <?php _e('Изчисти опашката', 'parfume-reviews'); ?>
                </button>
                <button type="button" class="button button-secondary" id="reset-failed-scrapes">
                    <?php _e('Рестартирай неуспешни', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <div class="queue-status">
                <h4><?php _e('Статус на опашката', 'parfume-reviews'); ?></h4>
                <div id="scraper-queue-status">
                    <?php $this->render_queue_status(); ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Стартирай scraper сега
            $('#run-scraper-now').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Стартира...', 'parfume-reviews'); ?>');
                
                $.post(ajaxurl, {
                    action: 'parfume_run_scraper_now',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Scraper е стартиран успешно.', 'parfume-reviews'); ?>');
                        location.reload();
                    } else {
                        alert('<?php _e('Грешка при стартиране на scraper.', 'parfume-reviews'); ?>');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('<?php _e('Стартирай скрейпване сега', 'parfume-reviews'); ?>');
                });
            });
            
            // Изчисти queue
            $('#clear-scraper-queue').on('click', function() {
                if (confirm('<?php _e('Сигурни ли сте, че искате да изчистите опашката?', 'parfume-reviews'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'parfume_clear_scraper_queue',
                        nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('<?php _e('Опашката е изчистена.', 'parfume-reviews'); ?>');
                            location.reload();
                        }
                    });
                }
            });
            
            // Рестартирай неуспешни
            $('#reset-failed-scrapes').on('click', function() {
                $.post(ajaxurl, {
                    action: 'parfume_reset_failed_scrapes',
                    nonce: '<?php echo wp_create_nonce('parfume_scraper_action'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Неуспешните скрейпвания са рестартирани.', 'parfume-reviews'); ?>');
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Рендерира статуса на queue
     */
    private function render_queue_status() {
        $queue_items = $this->get_queue_items();
        
        if (empty($queue_items)) {
            echo '<p>' . __('Опашката е празна.', 'parfume-reviews') . '</p>';
            return;
        }
        
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Продукт', 'parfume-reviews') . '</th>';
        echo '<th>' . __('Магазин', 'parfume-reviews') . '</th>';
        echo '<th>' . __('Статус', 'parfume-reviews') . '</th>';
        echo '<th>' . __('Следващо скрейпване', 'parfume-reviews') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($queue_items as $item) {
            echo '<tr>';
            echo '<td><a href="' . esc_url(get_edit_post_link($item['post_id'])) . '">' . esc_html(get_the_title($item['post_id'])) . '</a></td>';
            echo '<td>' . esc_html($item['store_name']) . '</td>';
            echo '<td><span class="status-' . esc_attr($item['status']) . '">' . esc_html($this->get_status_label($item['status'])) . '</span></td>';
            echo '<td>' . esc_html($item['next_scrape']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Получава статистики за scraper
     */
    private function get_scraper_statistics() {
        global $wpdb;
        
        $stats = array(
            'total_products' => 0,
            'products_with_scraping' => 0,
            'recent_scrapes' => 0,
            'failed_scrapes' => 0
        );
        
        // Общо продукти
        $stats['total_products'] = wp_count_posts('parfume')->publish;
        
        // Продукти със scraping
        $products_with_stores = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores' 
            AND meta_value != ''
        ");
        $stats['products_with_scraping'] = intval($products_with_stores);
        
        // Последни скрейпвания (последните 24 часа)
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $recent_scrapes = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->options} 
            WHERE option_name LIKE 'parfume_scraper_log_%' 
            AND option_value LIKE %s
        ", '%"timestamp":"' . date('Y-m-d') . '%'));
        $stats['recent_scrapes'] = intval($recent_scrapes);
        
        // Неуспешни скрейпвания
        $failed_scrapes = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->options} 
            WHERE option_name LIKE 'parfume_scraper_failed_%'
        ");
        $stats['failed_scrapes'] = intval($failed_scrapes);
        
        return $stats;
    }
    
    /**
     * Получава елементите от queue
     */
    private function get_queue_items() {
        global $wpdb;
        
        $queue_items = array();
        
        // Намираме всички постове с магазини
        $posts_with_stores = $wpdb->get_results("
            SELECT post_id, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores'
            AND meta_value != ''
            LIMIT 50
        ");
        
        foreach ($posts_with_stores as $post_meta) {
            $stores = maybe_unserialize($post_meta->meta_value);
            if (!is_array($stores)) continue;
            
            foreach ($stores as $store) {
                if (empty($store['product_url'])) continue;
                
                $queue_items[] = array(
                    'post_id' => $post_meta->post_id,
                    'store_name' => $store['name'],
                    'status' => isset($store['scrape_status']) ? $store['scrape_status'] : 'pending',
                    'next_scrape' => isset($store['next_scrape']) ? $store['next_scrape'] : __('Неизвестно', 'parfume-reviews')
                );
            }
        }
        
        return $queue_items;
    }
    
    /**
     * Получава етикета за статус
     */
    private function get_status_label($status) {
        $labels = array(
            'pending' => __('Чакащ', 'parfume-reviews'),
            'running' => __('Изпълнява се', 'parfume-reviews'),
            'completed' => __('Завършен', 'parfume-reviews'),
            'failed' => __('Неуспешен', 'parfume-reviews'),
            'skipped' => __('Пропуснат', 'parfume-reviews')
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * Получава настройките за export
     */
    public function get_all_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'scraper_enabled' => isset($settings['scraper_enabled']) ? $settings['scraper_enabled'] : false,
            'scraper_frequency' => isset($settings['scraper_frequency']) ? $settings['scraper_frequency'] : 24,
            'scraper_timeout' => isset($settings['scraper_timeout']) ? $settings['scraper_timeout'] : 30,
            'scraper_user_agent' => isset($settings['scraper_user_agent']) ? $settings['scraper_user_agent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'scraper_concurrent_requests' => isset($settings['scraper_concurrent_requests']) ? $settings['scraper_concurrent_requests'] : 3,
            'scraper_delay_between_requests' => isset($settings['scraper_delay_between_requests']) ? $settings['scraper_delay_between_requests'] : 2,
            'scraper_max_retries' => isset($settings['scraper_max_retries']) ? $settings['scraper_max_retries'] : 3
        );
    }
    
    /**
     * Валидира настройките преди запазване
     */
    public function validate_settings($input) {
        $validated = array();
        
        // scraper_enabled
        $validated['scraper_enabled'] = isset($input['scraper_enabled']) ? true : false;
        
        // scraper_frequency
        $validated['scraper_frequency'] = intval($input['scraper_frequency']);
        if ($validated['scraper_frequency'] < 1) {
            $validated['scraper_frequency'] = 24;
        }
        
        // scraper_timeout
        $validated['scraper_timeout'] = intval($input['scraper_timeout']);
        if ($validated['scraper_timeout'] < 5 || $validated['scraper_timeout'] > 120) {
            $validated['scraper_timeout'] = 30;
        }
        
        // scraper_user_agent
        $validated['scraper_user_agent'] = sanitize_textarea_field($input['scraper_user_agent']);
        if (empty($validated['scraper_user_agent'])) {
            $validated['scraper_user_agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        }
        
        // scraper_concurrent_requests
        $validated['scraper_concurrent_requests'] = intval($input['scraper_concurrent_requests']);
        if ($validated['scraper_concurrent_requests'] < 1 || $validated['scraper_concurrent_requests'] > 10) {
            $validated['scraper_concurrent_requests'] = 3;
        }
        
        // scraper_delay_between_requests
        $validated['scraper_delay_between_requests'] = intval($input['scraper_delay_between_requests']);
        if ($validated['scraper_delay_between_requests'] < 0 || $validated['scraper_delay_between_requests'] > 30) {
            $validated['scraper_delay_between_requests'] = 2;
        }
        
        // scraper_max_retries
        $validated['scraper_max_retries'] = intval($input['scraper_max_retries']);
        if ($validated['scraper_max_retries'] < 1 || $validated['scraper_max_retries'] > 10) {
            $validated['scraper_max_retries'] = 3;
        }
        
        return $validated;
    }
    
    /**
     * Експортира scraper настройките в JSON формат
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        
        return json_encode(array(
            'component' => 'scraper',
            'version' => PARFUME_REVIEWS_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Импортира scraper настройки от JSON данни
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['component']) || $data['component'] !== 'scraper') {
            return new \WP_Error('invalid_component', __('Файлът не съдържа scraper настройки.', 'parfume-reviews'));
        }
        
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return new \WP_Error('invalid_settings', __('Невалидни настройки в файла.', 'parfume-reviews'));
        }
        
        // Валидираме и запазваме настройките
        $validated_settings = $this->validate_settings($data['settings']);
        $current_settings = get_option('parfume_reviews_settings', array());
        
        // Запазваме настройките от други компоненти
        foreach ($validated_settings as $key => $value) {
            $current_settings[$key] = $value;
        }
        
        $result = update_option('parfume_reviews_settings', $current_settings);
        
        if ($result) {
            // Изчистваме cache за scraper настройките
            delete_transient('parfume_scraper_settings_cache');
        }
        
        return $result;
    }
}