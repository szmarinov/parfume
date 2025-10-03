<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Price class - Управлява настройките за цени и валута
 * 
 * Файл: includes/settings/class-settings-price.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_Price {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за цени
     */
    public function register_settings() {
        // Price Section
        add_settings_section(
            'parfume_reviews_price_section',
            __('Настройки за цени', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        add_settings_field(
            'price_format',
            __('Позиция на валутата', 'parfume-reviews'),
            array($this, 'price_format_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_price_section'
        );
        
        add_settings_field(
            'currency_symbol',
            __('Символ на валутата', 'parfume-reviews'),
            array($this, 'currency_symbol_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_price_section'
        );
        
        add_settings_field(
            'show_old_prices',
            __('Показвай стари цени', 'parfume-reviews'),
            array($this, 'show_old_prices_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_price_section'
        );
        
        add_settings_field(
            'price_comparison_enabled',
            __('Сравнение на цени', 'parfume-reviews'),
            array($this, 'price_comparison_enabled_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_price_section'
        );
        
        add_settings_field(
            'price_precision',
            __('Точност на цените', 'parfume-reviews'),
            array($this, 'price_precision_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_price_section'
        );
        
        add_settings_field(
            'price_thousands_separator',
            __('Разделител за хиляди', 'parfume-reviews'),
            array($this, 'price_thousands_separator_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_price_section'
        );
        
        add_settings_field(
            'price_decimal_separator',
            __('Десетичен разделител', 'parfume-reviews'),
            array($this, 'price_decimal_separator_callback'),
            'parfume-reviews-settings',
            'parfume_reviews_price_section'
        );
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Конфигурирайте как се показват и форматират цените на парфюмите.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с price настройки
     */
    public function render_section() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="currency_symbol"><?php _e('Символ на валутата', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->currency_symbol_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="price_format"><?php _e('Позиция на валутата', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->price_format_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="price_precision"><?php _e('Точност на цените', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->price_precision_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="price_thousands_separator"><?php _e('Разделител за хиляди', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->price_thousands_separator_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="price_decimal_separator"><?php _e('Десетичен разделител', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->price_decimal_separator_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="show_old_prices"><?php _e('Показвай стари цени', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->show_old_prices_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="price_comparison_enabled"><?php _e('Сравнение на цени', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <?php $this->price_comparison_enabled_callback(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Price Formatting Preview -->
        <div class="price-format-preview" style="margin-top: 30px;">
            <h3><?php _e('Преглед на форматирането', 'parfume-reviews'); ?></h3>
            <div id="price-preview-container" style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <?php $this->render_price_preview(); ?>
            </div>
        </div>
        
        <!-- Price Statistics -->
        <div class="price-statistics" style="margin-top: 30px;">
            <h3><?php _e('Статистики за цени', 'parfume-reviews'); ?></h3>
            <?php $this->render_price_statistics(); ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Update price preview when settings change
            function updatePricePreview() {
                var currency = $('#currency_symbol').val() || 'лв.';
                var format = $('input[name="parfume_reviews_settings[price_format]"]:checked').val() || 'after';
                var precision = parseInt($('#price_precision').val()) || 2;
                var thousands = $('#price_thousands_separator').val() || ' ';
                var decimal = $('#price_decimal_separator').val() || ',';
                
                var samplePrice = 123.45;
                var formattedPrice = samplePrice.toFixed(precision);
                
                // Apply thousands separator (simplified for preview)
                if (samplePrice >= 1000) {
                    formattedPrice = formattedPrice.replace('.', decimal).replace(/\B(?=(\d{3})+(?!\d))/g, thousands);
                } else {
                    formattedPrice = formattedPrice.replace('.', decimal);
                }
                
                var priceDisplay = format === 'before' ? currency + ' ' + formattedPrice : formattedPrice + ' ' + currency;
                
                $('#price-preview-value').text(priceDisplay);
            }
            
            // Bind events
            $('#currency_symbol, #price_precision, #price_thousands_separator, #price_decimal_separator').on('input', updatePricePreview);
            $('input[name="parfume_reviews_settings[price_format]"]').on('change', updatePricePreview);
            
            // Initial update
            updatePricePreview();
        });
        </script>
        <?php
    }
    
    /**
     * Callback за currency_symbol настройката
     */
    public function currency_symbol_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'лв.';
        
        echo '<input type="text" 
                     id="currency_symbol"
                     name="parfume_reviews_settings[currency_symbol]" 
                     value="' . esc_attr($value) . '" 
                     class="regular-text" 
                     placeholder="лв." />';
        echo '<p class="description">' . __('Символът на валутата, който се показва при цените.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за price_format настройката
     */
    public function price_format_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['price_format']) ? $settings['price_format'] : 'after';
        
        echo '<fieldset>';
        echo '<label><input type="radio" name="parfume_reviews_settings[price_format]" value="before" ' . checked($value, 'before', false) . '> ';
        echo __('Преди цената', 'parfume-reviews') . ' <span class="example">(' . __('$ 123.45', 'parfume-reviews') . ')</span></label><br>';
        echo '<label><input type="radio" name="parfume_reviews_settings[price_format]" value="after" ' . checked($value, 'after', false) . '> ';
        echo __('След цената', 'parfume-reviews') . ' <span class="example">(' . __('123.45 лв.', 'parfume-reviews') . ')</span></label>';
        echo '</fieldset>';
        echo '<p class="description">' . __('Позиция на валутния символ спрямо цената.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за show_old_prices настройката
     */
    public function show_old_prices_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['show_old_prices']) ? $settings['show_old_prices'] : true;
        
        echo '<input type="checkbox" 
                     id="show_old_prices"
                     name="parfume_reviews_settings[show_old_prices]" 
                     value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Показва зачертани стари цени когато има промоция.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за price_comparison_enabled настройката
     */
    public function price_comparison_enabled_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['price_comparison_enabled']) ? $settings['price_comparison_enabled'] : true;
        
        echo '<input type="checkbox" 
                     id="price_comparison_enabled"
                     name="parfume_reviews_settings[price_comparison_enabled]" 
                     value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Позволява сравнение на цени между различни магазини.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за price_precision настройката
     */
    public function price_precision_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['price_precision']) ? $settings['price_precision'] : 2;
        
        echo '<select id="price_precision" name="parfume_reviews_settings[price_precision]">';
        for ($i = 0; $i <= 4; $i++) {
            echo '<option value="' . $i . '" ' . selected($value, $i, false) . '>' . $i . ' ' . __('десетични знака', 'parfume-reviews') . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Брой десетични знаци за показване при цените.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за price_thousands_separator настройката
     */
    public function price_thousands_separator_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['price_thousands_separator']) ? $settings['price_thousands_separator'] : ' ';
        
        $options = array(
            ' ' => __('Интервал', 'parfume-reviews') . ' (1 234,56)',
            ',' => __('Запетая', 'parfume-reviews') . ' (1,234.56)',
            '.' => __('Точка', 'parfume-reviews') . ' (1.234,56)',
            '' => __('Без разделител', 'parfume-reviews') . ' (1234,56)'
        );
        
        echo '<select id="price_thousands_separator" name="parfume_reviews_settings[price_thousands_separator]">';
        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>';
            echo esc_html($option_label);
            echo '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Символ за разделяне на хилядите.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Callback за price_decimal_separator настройката
     */
    public function price_decimal_separator_callback() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = isset($settings['price_decimal_separator']) ? $settings['price_decimal_separator'] : ',';
        
        $options = array(
            ',' => __('Запетая', 'parfume-reviews') . ' (123,45)',
            '.' => __('Точка', 'parfume-reviews') . ' (123.45)'
        );
        
        echo '<select id="price_decimal_separator" name="parfume_reviews_settings[price_decimal_separator]">';
        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>';
            echo esc_html($option_label);
            echo '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Символ за разделяне на десетичните знаци.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира преглед на форматирането на цени
     */
    private function render_price_preview() {
        ?>
        <div class="price-preview-display">
            <h4><?php _e('Примерно форматиране:', 'parfume-reviews'); ?></h4>
            <div class="preview-examples">
                <div class="preview-item">
                    <strong><?php _e('Обикновена цена:', 'parfume-reviews'); ?></strong>
                    <span id="price-preview-value" class="price-value">123,45 лв.</span>
                </div>
                <div class="preview-item" style="margin-top: 10px;">
                    <strong><?php _e('С отстъпка:', 'parfume-reviews'); ?></strong>
                    <span class="old-price" style="text-decoration: line-through; color: #999;">150,00 лв.</span>
                    <span class="current-price" style="color: #e74c3c; font-weight: bold;">123,45 лв.</span>
                    <span class="discount-badge" style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px; margin-left: 5px;">-18%</span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира статистики за цени
     */
    private function render_price_statistics() {
        $stats = $this->get_price_statistics();
        ?>
        <div class="price-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="stat-box" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php _e('Общо парфюми', 'parfume-reviews'); ?></h4>
                <div class="stat-value" style="font-size: 24px; font-weight: bold;"><?php echo esc_html($stats['total_parfumes']); ?></div>
            </div>
            <div class="stat-box" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php _e('Парфюми с цени', 'parfume-reviews'); ?></h4>
                <div class="stat-value" style="font-size: 24px; font-weight: bold;"><?php echo esc_html($stats['parfumes_with_prices']); ?></div>
            </div>
            <div class="stat-box" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php _e('Средна цена', 'parfume-reviews'); ?></h4>
                <div class="stat-value" style="font-size: 24px; font-weight: bold;"><?php echo esc_html($stats['average_price']); ?></div>
            </div>
            <div class="stat-box" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php _e('Ценови диапазон', 'parfume-reviews'); ?></h4>
                <div class="stat-value" style="font-size: 16px; font-weight: bold;">
                    <?php echo esc_html($stats['price_range']['min'] . ' - ' . $stats['price_range']['max']); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Получава статистики за цените
     */
    private function get_price_statistics() {
        global $wpdb;
        
        // Общо парфюми
        $total_parfumes = wp_count_posts('parfume')->publish;
        
        // Парфюми с цени (от stores meta)
        $parfumes_with_prices = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores' 
            AND meta_value != '' 
            AND meta_value != 'a:0:{}'
        ");
        
        // Извличане на всички цени за средна стойност
        $stores_data = $wpdb->get_results("
            SELECT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_parfume_stores' 
            AND meta_value != '' 
            AND meta_value != 'a:0:{}'
        ");
        
        $prices = array();
        $currency = $this->get_currency_symbol();
        
        foreach ($stores_data as $row) {
            $stores = maybe_unserialize($row->meta_value);
            if (is_array($stores)) {
                foreach ($stores as $store) {
                    if (!empty($store['scraped_price'])) {
                        $price = $this->extract_price_number($store['scraped_price']);
                        if ($price > 0) {
                            $prices[] = $price;
                        }
                    }
                    // Check variants
                    if (!empty($store['variants']) && is_array($store['variants'])) {
                        foreach ($store['variants'] as $variant) {
                            if (!empty($variant['price'])) {
                                $price = $this->extract_price_number($variant['price']);
                                if ($price > 0) {
                                    $prices[] = $price;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Калкулиране на статистики
        $average_price = '';
        $price_range = array('min' => '', 'max' => '');
        
        if (!empty($prices)) {
            $average = array_sum($prices) / count($prices);
            $average_price = $this->format_price($average);
            
            $min_price = min($prices);
            $max_price = max($prices);
            $price_range = array(
                'min' => $this->format_price($min_price),
                'max' => $this->format_price($max_price)
            );
        }
        
        return array(
            'total_parfumes' => $total_parfumes,
            'parfumes_with_prices' => intval($parfumes_with_prices),
            'average_price' => $average_price ?: __('Няма данни', 'parfume-reviews'),
            'price_range' => $price_range
        );
    }
    
    /**
     * Извлича числовата стойност от цена string
     */
    private function extract_price_number($price_string) {
        // Remove currency symbols and extract number
        $price = preg_replace('/[^\d.,]/', '', $price_string);
        $price = str_replace(',', '.', $price);
        return floatval($price);
    }
    
    /**
     * Форматира цена според настройките
     */
    public function format_price($price, $include_currency = true) {
        $settings = get_option('parfume_reviews_settings', array());
        
        $precision = isset($settings['price_precision']) ? intval($settings['price_precision']) : 2;
        $thousands_separator = isset($settings['price_thousands_separator']) ? $settings['price_thousands_separator'] : ' ';
        $decimal_separator = isset($settings['price_decimal_separator']) ? $settings['price_decimal_separator'] : ',';
        $currency_symbol = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'лв.';
        $price_format = isset($settings['price_format']) ? $settings['price_format'] : 'after';
        
        $formatted_price = number_format($price, $precision, $decimal_separator, $thousands_separator);
        
        if ($include_currency) {
            if ($price_format === 'before') {
                $formatted_price = $currency_symbol . ' ' . $formatted_price;
            } else {
                $formatted_price = $formatted_price . ' ' . $currency_symbol;
            }
        }
        
        return $formatted_price;
    }
    
    /**
     * Получава символа на валутата
     */
    public function get_currency_symbol() {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'лв.';
    }
    
    /**
     * Получава формата на цените
     */
    public function get_price_format() {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings['price_format']) ? $settings['price_format'] : 'after';
    }
    
    /**
     * Проверява дали да се показват стари цени
     */
    public function should_show_old_prices() {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings['show_old_prices']) ? $settings['show_old_prices'] : true;
    }
    
    /**
     * Проверява дали сравнението на цени е включено
     */
    public function is_price_comparison_enabled() {
        $settings = get_option('parfume_reviews_settings', array());
        return isset($settings['price_comparison_enabled']) ? $settings['price_comparison_enabled'] : true;
    }
    
    /**
     * Получава всички price настройки
     */
    public function get_all_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'currency_symbol' => isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'лв.',
            'price_format' => isset($settings['price_format']) ? $settings['price_format'] : 'after',
            'price_precision' => isset($settings['price_precision']) ? $settings['price_precision'] : 2,
            'price_thousands_separator' => isset($settings['price_thousands_separator']) ? $settings['price_thousands_separator'] : ' ',
            'price_decimal_separator' => isset($settings['price_decimal_separator']) ? $settings['price_decimal_separator'] : ',',
            'show_old_prices' => isset($settings['show_old_prices']) ? $settings['show_old_prices'] : true,
            'price_comparison_enabled' => isset($settings['price_comparison_enabled']) ? $settings['price_comparison_enabled'] : true
        );
    }
    
    /**
     * Експортира price настройките в JSON формат
     */
    public function export_settings() {
        $settings = $this->get_all_settings();
        
        return json_encode(array(
            'component' => 'price',
            'version' => PARFUME_REVIEWS_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Импортира price настройки от JSON данни
     */
    public function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат.', 'parfume-reviews'));
        }
        
        if (!isset($data['component']) || $data['component'] !== 'price') {
            return new \WP_Error('invalid_component', __('Файлът не съдържа настройки за цени.', 'parfume-reviews'));
        }
        
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return new \WP_Error('invalid_settings', __('Невалидни настройки в файла.', 'parfume-reviews'));
        }
        
        // Валидираме настройките
        $validated_settings = $this->validate_settings($data['settings']);
        
        // Запазваме настройките
        $current_settings = get_option('parfume_reviews_settings', array());
        $current_settings = array_merge($current_settings, $validated_settings);
        
        $result = update_option('parfume_reviews_settings', $current_settings);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => __('Настройките за цени са импортирани успешно.', 'parfume-reviews'),
                'imported_count' => count($validated_settings)
            );
        } else {
            return new \WP_Error('save_failed', __('Грешка при запазване на настройките.', 'parfume-reviews'));
        }
    }
    
    /**
     * Валидира price настройките
     */
    private function validate_settings($settings) {
        $validated = array();
        
        // Currency symbol validation
        if (isset($settings['currency_symbol'])) {
            $validated['currency_symbol'] = sanitize_text_field($settings['currency_symbol']);
        }
        
        // Price format validation
        if (isset($settings['price_format']) && in_array($settings['price_format'], array('before', 'after'))) {
            $validated['price_format'] = $settings['price_format'];
        }
        
        // Price precision validation
        if (isset($settings['price_precision'])) {
            $precision = intval($settings['price_precision']);
            if ($precision >= 0 && $precision <= 4) {
                $validated['price_precision'] = $precision;
            }
        }
        
        // Thousands separator validation
        if (isset($settings['price_thousands_separator'])) {
            $separator = $settings['price_thousands_separator'];
            if (in_array($separator, array(' ', ',', '.', ''))) {
                $validated['price_thousands_separator'] = $separator;
            }
        }
        
        // Decimal separator validation
        if (isset($settings['price_decimal_separator'])) {
            $separator = $settings['price_decimal_separator'];
            if (in_array($separator, array(',', '.'))) {
                $validated['price_decimal_separator'] = $separator;
            }
        }
        
        // Boolean settings validation
        $boolean_settings = array('show_old_prices', 'price_comparison_enabled');
        foreach ($boolean_settings as $setting) {
            if (isset($settings[$setting])) {
                $validated[$setting] = (bool) $settings[$setting];
            }
        }
        
        return $validated;
    }
}