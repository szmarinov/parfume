<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Mobile class - Управлява mobile настройките за "Колона 2"
 * 
 * Файл: includes/settings/class-settings-mobile.php
 * Създаден за mobile поведение на "Колона 2"
 */
class Settings_Mobile {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за mobile
     */
    public function register_settings() {
        // Mobile Section
        add_settings_section(
            'parfume_reviews_mobile_section',
            __('Mobile настройки', 'parfume-reviews'),
            array($this, 'section_description'),
            'parfume-reviews-settings'
        );
        
        register_setting('parfume-reviews-settings', 'parfume_reviews_mobile_settings', array(
            'sanitize_callback' => array($this, 'sanitize_mobile_settings')
        ));
    }
    
    /**
     * Описание на секцията
     */
    public function section_description() {
        echo '<p>' . __('Настройки за поведението на "Колона 2" на мобилни устройства.', 'parfume-reviews') . '</p>';
    }
    
    /**
     * Рендерира секцията с mobile настройки
     */
    public function render_section() {
        $mobile_settings = get_option('parfume_reviews_mobile_settings', array());
        $default_settings = $this->get_default_mobile_settings();
        $settings = wp_parse_args($mobile_settings, $default_settings);
        ?>
        <div class="mobile-settings">
            <h3><?php _e('Глобални mobile настройки', 'parfume-reviews'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Фиксиран панел', 'parfume-reviews'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label for="fixed_panel_enabled">
                                <input type="checkbox" id="fixed_panel_enabled" 
                                       name="parfume_reviews_mobile_settings[fixed_panel_enabled]" 
                                       value="1" <?php checked($settings['fixed_panel_enabled'], 1); ?> />
                                <?php _e('Показвай фиксиран магазин в долната част на екрана на мобилни устройства', 'parfume-reviews'); ?>
                            </label>
                            <p class="description"><?php _e('Когато е включено, първият магазин остава фиксиран в дъното на екрана при скролиране.', 'parfume-reviews'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php _e('Бутон "X" за скриване', 'parfume-reviews'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label for="show_close_button">
                                <input type="checkbox" id="show_close_button" 
                                       name="parfume_reviews_mobile_settings[show_close_button]" 
                                       value="1" <?php checked($settings['show_close_button'], 1); ?> />
                                <?php _e('Позволявай скриване на "Колона 2" чрез бутон "X"', 'parfume-reviews'); ?>
                            </label>
                            <p class="description"><?php _e('Добавя бутон за затваряне на мобилния панел.', 'parfume-reviews'); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Позициониране и стилизиране', 'parfume-reviews'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="z_index"><?php _e('Z-index', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="z_index" 
                               name="parfume_reviews_mobile_settings[z_index]" 
                               value="<?php echo esc_attr($settings['z_index']); ?>" 
                               min="1" max="99999" class="small-text" />
                        <p class="description"><?php _e('Z-index стойност за контрол на припокриването с други елементи.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bottom_offset"><?php _e('Отстояние отдолу (px)', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="bottom_offset" 
                               name="parfume_reviews_mobile_settings[bottom_offset]" 
                               value="<?php echo esc_attr($settings['bottom_offset']); ?>" 
                               min="0" max="200" class="small-text" />
                        <p class="description"><?php _e('Отстояние от долния край на екрана в пиксели. Полезно за избягване на припокриване с други фиксирани елементи.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="animation_duration"><?php _e('Продължителност на анимация (ms)', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="animation_duration" 
                               name="parfume_reviews_mobile_settings[animation_duration]" 
                               value="<?php echo esc_attr($settings['animation_duration']); ?>" 
                               min="100" max="1000" class="small-text" />
                        <p class="description"><?php _e('Продължителност на slide up/down анимацията в милисекунди.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="breakpoint"><?php _e('Mobile breakpoint (px)', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="breakpoint" 
                               name="parfume_reviews_mobile_settings[breakpoint]" 
                               value="<?php echo esc_attr($settings['breakpoint']); ?>" 
                               min="320" max="1200" class="small-text" />
                        <p class="description"><?php _e('Максимална ширина на екрана (в пиксели) при която се активират mobile настройките.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Поведение при други фиксирани елементи', 'parfume-reviews'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="detect_conflicts"><?php _e('Автоматично детектиране на конфликти', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="detect_conflicts">
                                <input type="checkbox" id="detect_conflicts" 
                                       name="parfume_reviews_mobile_settings[detect_conflicts]" 
                                       value="1" <?php checked($settings['detect_conflicts'], 1); ?> />
                                <?php _e('Автоматично детектирай други фиксирани елементи и се позиционирай над тях', 'parfume-reviews'); ?>
                            </label>
                            <p class="description"><?php _e('Скриптът ще търси cookie bars, навигации и други fixed елементи.', 'parfume-reviews'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="conflict_selectors"><?php _e('CSS селектори за конфликти', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <textarea id="conflict_selectors" 
                                  name="parfume_reviews_mobile_settings[conflict_selectors]" 
                                  class="large-text" rows="4"><?php echo esc_textarea($settings['conflict_selectors']); ?></textarea>
                        <p class="description"><?php _e('CSS селектори за елементи, които могат да се припокрият (разделени с нов ред). Пример: .cookie-bar, #bottom-nav', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Персонализиране на стила', 'parfume-reviews'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="background_color"><?php _e('Цвят на фона', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="color" id="background_color" 
                               name="parfume_reviews_mobile_settings[background_color]" 
                               value="<?php echo esc_attr($settings['background_color']); ?>" />
                        <p class="description"><?php _e('Цвят на фона на мобилния панел.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="border_color"><?php _e('Цвят на рамката', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="color" id="border_color" 
                               name="parfume_reviews_mobile_settings[border_color]" 
                               value="<?php echo esc_attr($settings['border_color']); ?>" />
                        <p class="description"><?php _e('Цвят на горната рамка на панела.', 'parfume-reviews'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="shadow_enabled"><?php _e('Сянка', 'parfume-reviews'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="shadow_enabled">
                                <input type="checkbox" id="shadow_enabled" 
                                       name="parfume_reviews_mobile_settings[shadow_enabled]" 
                                       value="1" <?php checked($settings['shadow_enabled'], 1); ?> />
                                <?php _e('Показвай сянка около панела', 'parfume-reviews'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Индивидуални настройки за постове', 'parfume-reviews'); ?></h3>
            <p><?php _e('В админ панела на всеки пост може да се направят индивидуални настройки, които имат приоритет пред глобалните.', 'parfume-reviews'); ?></p>
            
            <div class="mobile-preview">
                <h4><?php _e('Преглед', 'parfume-reviews'); ?></h4>
                <div class="mobile-preview-container" style="width: 320px; height: 200px; border: 2px solid #ddd; position: relative; background: #f9f9f9; margin: 20px 0;">
                    <div class="preview-content" style="padding: 20px; color: #666;">
                        <?php _e('Съдържание на страницата...', 'parfume-reviews'); ?>
                    </div>
                    <div class="preview-mobile-panel" style="
                        position: absolute; 
                        bottom: <?php echo esc_attr($settings['bottom_offset']); ?>px; 
                        left: 0; 
                        right: 0; 
                        background: <?php echo esc_attr($settings['background_color']); ?>; 
                        border-top: 2px solid <?php echo esc_attr($settings['border_color']); ?>;
                        padding: 10px;
                        <?php if ($settings['shadow_enabled']): ?>box-shadow: 0 -2px 10px rgba(0,0,0,0.1);<?php endif; ?>
                        ">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <div style="width: 30px; height: 20px; background: #ddd; margin-right: 10px; border-radius: 2px;"></div>
                                <div>
                                    <div style="font-size: 12px; font-weight: bold;">Магазин</div>
                                    <div style="font-size: 10px; color: #ff6600;">49.99 лв</div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <?php if ($settings['show_close_button']): ?>
                                    <span style="margin-right: 10px; font-size: 16px; color: #666;">×</span>
                                <?php endif; ?>
                                <span style="font-size: 12px; color: #666;">↑</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="description"><?php _e('Приблизителен изглед на мобилния панел с текущите настройки.', 'parfume-reviews'); ?></p>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Live preview update
            function updatePreview() {
                var bottomOffset = $('#bottom_offset').val() || 0;
                var backgroundColor = $('#background_color').val();
                var borderColor = $('#border_color').val();
                var showShadow = $('#shadow_enabled').is(':checked');
                var showCloseButton = $('#show_close_button').is(':checked');
                
                var panel = $('.preview-mobile-panel');
                panel.css({
                    'bottom': bottomOffset + 'px',
                    'background': backgroundColor,
                    'border-top-color': borderColor
                });
                
                if (showShadow) {
                    panel.css('box-shadow', '0 -2px 10px rgba(0,0,0,0.1)');
                } else {
                    panel.css('box-shadow', 'none');
                }
                
                var closeButton = panel.find('span').first();
                if (showCloseButton) {
                    closeButton.show();
                } else {
                    closeButton.hide();
                }
            }
            
            // Update preview on change
            $('#bottom_offset, #background_color, #border_color, #shadow_enabled, #show_close_button').on('change input', updatePreview);
            
            // Initial preview update
            updatePreview();
        });
        </script>
        
        <style>
        .mobile-preview-container {
            border-radius: 8px;
            overflow: hidden;
        }
        .preview-mobile-panel {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        </style>
        <?php
    }
    
    /**
     * Получава default настройки за mobile
     */
    private function get_default_mobile_settings() {
        return array(
            'fixed_panel_enabled' => 1,
            'show_close_button' => 1,
            'z_index' => 9999,
            'bottom_offset' => 0,
            'animation_duration' => 300,
            'breakpoint' => 768,
            'detect_conflicts' => 1,
            'conflict_selectors' => ".cookie-bar\n#bottom-nav\n.mobile-menu\n.sticky-footer",
            'background_color' => '#ffffff',
            'border_color' => '#e0e0e0',
            'shadow_enabled' => 1
        );
    }
    
    /**
     * Санитизация на mobile настройки
     */
    public function sanitize_mobile_settings($input) {
        if (!is_array($input)) {
            return $this->get_default_mobile_settings();
        }
        
        $sanitized = array();
        $defaults = $this->get_default_mobile_settings();
        
        $sanitized['fixed_panel_enabled'] = isset($input['fixed_panel_enabled']) ? 1 : 0;
        $sanitized['show_close_button'] = isset($input['show_close_button']) ? 1 : 0;
        $sanitized['z_index'] = isset($input['z_index']) ? max(1, min(99999, intval($input['z_index']))) : $defaults['z_index'];
        $sanitized['bottom_offset'] = isset($input['bottom_offset']) ? max(0, min(200, intval($input['bottom_offset']))) : $defaults['bottom_offset'];
        $sanitized['animation_duration'] = isset($input['animation_duration']) ? max(100, min(1000, intval($input['animation_duration']))) : $defaults['animation_duration'];
        $sanitized['breakpoint'] = isset($input['breakpoint']) ? max(320, min(1200, intval($input['breakpoint']))) : $defaults['breakpoint'];
        $sanitized['detect_conflicts'] = isset($input['detect_conflicts']) ? 1 : 0;
        $sanitized['conflict_selectors'] = isset($input['conflict_selectors']) ? sanitize_textarea_field($input['conflict_selectors']) : $defaults['conflict_selectors'];
        $sanitized['background_color'] = isset($input['background_color']) ? sanitize_hex_color($input['background_color']) : $defaults['background_color'];
        $sanitized['border_color'] = isset($input['border_color']) ? sanitize_hex_color($input['border_color']) : $defaults['border_color'];
        $sanitized['shadow_enabled'] = isset($input['shadow_enabled']) ? 1 : 0;
        
        return $sanitized;
    }
}