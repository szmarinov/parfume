<?php
/**
 * Admin Comparison Management Class
 * 
 * Handles comparison functionality settings in admin panel
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Admin_Comparison {
    
    private $comparison_option = 'parfume_comparison_settings';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_comparison_page'));
        add_action('admin_init', array($this, 'register_comparison_settings'));
        add_action('wp_ajax_parfume_save_comparison_criteria', array($this, 'save_comparison_criteria'));
        add_action('wp_ajax_parfume_reorder_criteria', array($this, 'reorder_criteria'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add comparison management page to admin menu
     */
    public function add_comparison_page() {
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Сравнения', 'parfume-catalog'),
            __('Сравнения', 'parfume-catalog'),
            'manage_options',
            'parfume-comparison',
            array($this, 'render_comparison_page')
        );
    }
    
    /**
     * Register comparison settings
     */
    public function register_comparison_settings() {
        register_setting('parfume_catalog_settings', 'parfume_comparison_enabled', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_max_items', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_popup_width', array(
            'type' => 'integer',
            'default' => 90,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_popup_position', array(
            'type' => 'string',
            'default' => 'center',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_show_undo', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_show_clear_all', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_auto_hide', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_show_search', array(
            'type' => 'boolean',
            'default' => true
        ));
        
        register_setting('parfume_catalog_settings', 'parfume_comparison_show_visuals', array(
            'type' => 'boolean',
            'default' => true
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'parfumes_page_parfume-comparison') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-tabs');
    }
    
    /**
     * Render the comparison management page
     */
    public function render_comparison_page() {
        $comparison_criteria = $this->get_comparison_criteria();
        $available_criteria = $this->get_available_criteria();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Настройки за сравнения', 'parfume-catalog'); ?></h1>
            
            <div id="comparison-tabs">
                <ul>
                    <li><a href="#general-tab"><?php _e('Общи настройки', 'parfume-catalog'); ?></a></li>
                    <li><a href="#criteria-tab"><?php _e('Критерии за сравнение', 'parfume-catalog'); ?></a></li>
                    <li><a href="#appearance-tab"><?php _e('Външен вид', 'parfume-catalog'); ?></a></li>
                    <li><a href="#texts-tab"><?php _e('Текстове', 'parfume-catalog'); ?></a></li>
                </ul>
                
                <!-- General Settings Tab -->
                <div id="general-tab">
                    <h3><?php _e('Основни настройки', 'parfume-catalog'); ?></h3>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_catalog_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Активиране на функционалността', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_enabled" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_enabled', true)); ?> />
                                        <?php _e('Разреши сравняване на парфюми', 'parfume-catalog'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('При изключване всички бутони за сравнение и pop-up-ът стават невидими', 'parfume-catalog'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_max_items"><?php _e('Максимален брой за сравнение', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_comparison_max_items" 
                                           name="parfume_comparison_max_items" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_max_items', 4)); ?>" 
                                           min="2" max="10" />
                                    <p class="description"><?php _e('Максимален брой парфюми за едновременно сравнение', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <h4><?php _e('UX Функции', 'parfume-catalog'); ?></h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Undo функционалност', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_undo" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_undo', true)); ?> />
                                        <?php _e('Показвай възможност за отмяна на последното премахване', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Групово премахване', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_clear_all" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_clear_all', true)); ?> />
                                        <?php _e('Показвай бутон за премахване на всички продукти', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Автоматично скриване', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_auto_hide" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_auto_hide', true)); ?> />
                                        <?php _e('Автоматично скривай pop-up-а при липса на продукти за сравнение', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Търсене в pop-up', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_search" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_search', true)); ?> />
                                        <?php _e('Показвай поле за търсене за добавяне на парфюми в pop-up-а', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Визуални графики', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_visuals" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_visuals', true)); ?> />
                                        <?php _e('Показвай графики и визуални елементи в pop-up-а', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <!-- Criteria Tab -->
                <div id="criteria-tab">
                    <h3><?php _e('Критерии за сравнение', 'parfume-catalog'); ?></h3>
                    <p><?php _e('Изберете и подредете кои характеристики да се показват при сравнение.', 'parfume-catalog'); ?></p>
                    
                    <div class="criteria-manager">
                        <div class="criteria-columns">
                            <div class="available-criteria">
                                <h4><?php _e('Налични критерии', 'parfume-catalog'); ?></h4>
                                <div class="criteria-list" id="available-criteria-list">
                                    <?php foreach ($available_criteria as $key => $criterion): ?>
                                        <?php if (!isset($comparison_criteria[$key])): ?>
                                            <div class="criterion-item" data-key="<?php echo esc_attr($key); ?>">
                                                <span class="criterion-icon"><?php echo esc_html($criterion['icon']); ?></span>
                                                <span class="criterion-label"><?php echo esc_html($criterion['label']); ?></span>
                                                <button type="button" class="button button-small add-criterion"><?php _e('Добави', 'parfume-catalog'); ?></button>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="active-criteria">
                                <h4><?php _e('Активни критерии за сравнение', 'parfume-catalog'); ?></h4>
                                <div class="criteria-list sortable" id="active-criteria-list">
                                    <?php foreach ($comparison_criteria as $key => $criterion): ?>
                                        <div class="criterion-item active" data-key="<?php echo esc_attr($key); ?>">
                                            <span class="criterion-handle">⋮⋮</span>
                                            <span class="criterion-icon"><?php echo esc_html($criterion['icon']); ?></span>
                                            <span class="criterion-label"><?php echo esc_html($criterion['label']); ?></span>
                                            <button type="button" class="button button-small remove-criterion"><?php _e('Премахни', 'parfume-catalog'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <p class="submit">
                                    <button type="button" id="save-criteria" class="button button-primary">
                                        <?php _e('Запази критериите', 'parfume-catalog'); ?>
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Appearance Tab -->
                <div id="appearance-tab">
                    <h3><?php _e('Външен вид на pop-up', 'parfume-catalog'); ?></h3>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_catalog_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_popup_width"><?php _e('Ширина на pop-up (%)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="range" 
                                           id="parfume_comparison_popup_width" 
                                           name="parfume_comparison_popup_width" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_popup_width', 90)); ?>" 
                                           min="50" max="100" 
                                           oninput="updateWidthValue(this.value)" />
                                    <span id="width-value"><?php echo esc_attr(get_option('parfume_comparison_popup_width', 90)); ?>%</span>
                                    <p class="description"><?php _e('Ширина на pop-up прозореца в проценти от екрана', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_popup_position"><?php _e('Позиция на pop-up', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <select id="parfume_comparison_popup_position" name="parfume_comparison_popup_position">
                                        <option value="center" <?php selected(get_option('parfume_comparison_popup_position', 'center'), 'center'); ?>>
                                            <?php _e('В центъра', 'parfume-catalog'); ?>
                                        </option>
                                        <option value="top" <?php selected(get_option('parfume_comparison_popup_position', 'center'), 'top'); ?>>
                                            <?php _e('Отгоре', 'parfume-catalog'); ?>
                                        </option>
                                        <option value="bottom" <?php selected(get_option('parfume_comparison_popup_position', 'center'), 'bottom'); ?>>
                                            <?php _e('Отдолу', 'parfume-catalog'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <h4><?php _e('Цветова схема', 'parfume-catalog'); ?></h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_primary_color"><?php _e('Основен цвят', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="color" 
                                           id="parfume_comparison_primary_color" 
                                           name="parfume_comparison_primary_color" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_primary_color', '#0073aa')); ?>" />
                                    <p class="description"><?php _e('Цвят на бутоните и акцентите', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_background_color"><?php _e('Цвят на фона', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="color" 
                                           id="parfume_comparison_background_color" 
                                           name="parfume_comparison_background_color" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_background_color', '#ffffff')); ?>" />
                                    <p class="description"><?php _e('Цвят на фона на pop-up-а', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <!-- Texts Tab -->
                <div id="texts-tab">
                    <h3><?php _e('Персонализиране на текстове', 'parfume-catalog'); ?></h3>
                    <p><?php _e('Редактирайте текстовете, които се показват на потребителите.', 'parfume-catalog'); ?></p>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_catalog_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_add_text"><?php _e('Текст на бутон "Добави"', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_add_text" 
                                           name="parfume_comparison_add_text" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_add_text', __('Добави за сравнение', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_remove_text"><?php _e('Текст на бутон "Премахни"', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_remove_text" 
                                           name="parfume_comparison_remove_text" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_remove_text', __('Премахни от сравнение', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_max_reached_text"><?php _e('Съобщение при достигнат максимум', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_max_reached_text" 
                                           name="parfume_comparison_max_reached_text" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_max_reached_text', __('Максимален брой достигнат', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_empty_text"><?php _e('Съобщение при празен списък', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_empty_text" 
                                           name="parfume_comparison_empty_text" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_empty_text', __('Все още няма избрани парфюми за сравнение', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_popup_title"><?php _e('Заглавие на pop-up', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_popup_title" 
                                           name="parfume_comparison_popup_title" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_popup_title', __('Сравнение на парфюми', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_clear_all_text"><?php _e('Текст на бутон "Изчисти всички"', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_clear_all_text" 
                                           name="parfume_comparison_clear_all_text" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_clear_all_text', __('Изчисти всички', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_undo_text"><?php _e('Текст на бутон "Отмени"', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_undo_text" 
                                           name="parfume_comparison_undo_text" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_undo_text', __('Отмени', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .criteria-manager {
            margin-top: 20px;
        }
        
        .criteria-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .criteria-list {
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 300px;
            padding: 15px;
            background: #fafafa;
        }
        
        .criteria-list.sortable {
            background: #fff;
        }
        
        .criterion-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: default;
        }
        
        .criterion-item.active {
            cursor: move;
        }
        
        .criterion-handle {
            margin-right: 10px;
            color: #666;
            cursor: move;
        }
        
        .criterion-icon {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .criterion-label {
            flex: 1;
            font-weight: 500;
        }
        
        .criterion-item .button {
            margin-left: 10px;
        }
        
        .sortable-placeholder {
            border: 2px dashed #0073aa;
            background: rgba(0, 115, 170, 0.1);
            height: 40px;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        
        #width-value {
            display: inline-block;
            min-width: 40px;
            margin-left: 10px;
            font-weight: bold;
        }
        
        .form-table input[type="color"] {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize tabs
            $('#comparison-tabs').tabs();
            
            // Make active criteria sortable
            $('#active-criteria-list').sortable({
                placeholder: 'sortable-placeholder',
                handle: '.criterion-handle',
                update: function(event, ui) {
                    // Reorder is handled when saving
                }
            });
            
            // Add criterion
            $(document).on('click', '.add-criterion', function() {
                var item = $(this).closest('.criterion-item');
                var clone = item.clone();
                
                // Modify for active list
                clone.addClass('active');
                clone.find('.add-criterion').removeClass('add-criterion').addClass('remove-criterion').text('<?php _e('Премахни', 'parfume-catalog'); ?>');
                clone.prepend('<span class="criterion-handle">⋮⋮</span>');
                
                // Add to active list
                $('#active-criteria-list').append(clone);
                
                // Remove from available list
                item.remove();
            });
            
            // Remove criterion
            $(document).on('click', '.remove-criterion', function() {
                var item = $(this).closest('.criterion-item');
                var clone = item.clone();
                
                // Modify for available list
                clone.removeClass('active');
                clone.find('.criterion-handle').remove();
                clone.find('.remove-criterion').removeClass('remove-criterion').addClass('add-criterion').text('<?php _e('Добави', 'parfume-catalog'); ?>');
                
                // Add to available list
                $('#available-criteria-list').append(clone);
                
                // Remove from active list
                item.remove();
            });
            
            // Save criteria
            $('#save-criteria').click(function() {
                var criteria = [];
                $('#active-criteria-list .criterion-item').each(function() {
                    criteria.push($(this).data('key'));
                });
                
                $.post(ajaxurl, {
                    action: 'parfume_save_comparison_criteria',
                    nonce: '<?php echo wp_create_nonce('parfume_comparison_action'); ?>',
                    criteria: criteria
                }, function(response) {
                    if (response.success) {
                        // Show success message
                        $('<div class="notice notice-success is-dismissible"><p><?php _e('Критериите са запазени успешно!', 'parfume-catalog'); ?></p></div>')
                            .insertAfter('#comparison-tabs')
                            .delay(3000)
                            .fadeOut();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при запазване', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Width slider update
            window.updateWidthValue = function(value) {
                document.getElementById('width-value').textContent = value + '%';
            };
        });
        </script>
        <?php
    }
    
    /**
     * Get comparison criteria
     */
    private function get_comparison_criteria() {
        $default_criteria = array(
            'name' => array(
                'label' => __('Име', 'parfume-catalog'),
                'icon' => '📝',
                'order' => 1
            ),
            'brand' => array(
                'label' => __('Марка', 'parfume-catalog'),
                'icon' => '🏷️',
                'order' => 2
            ),
            'price' => array(
                'label' => __('Цена', 'parfume-catalog'),
                'icon' => '💰',
                'order' => 3
            ),
            'rating' => array(
                'label' => __('Рейтинг', 'parfume-catalog'),
                'icon' => '⭐',
                'order' => 4
            )
        );
        
        $saved_criteria = get_option($this->comparison_option, $default_criteria);
        
        // Sort by order
        uasort($saved_criteria, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        return $saved_criteria;
    }
    
    /**
     * Get all available criteria
     */
    private function get_available_criteria() {
        return array(
            'name' => array(
                'label' => __('Име', 'parfume-catalog'),
                'icon' => '📝'
            ),
            'brand' => array(
                'label' => __('Марка', 'parfume-catalog'),
                'icon' => '🏷️'
            ),
            'type' => array(
                'label' => __('Тип', 'parfume-catalog'),
                'icon' => '👤'
            ),
            'vid' => array(
                'label' => __('Вид аромат', 'parfume-catalog'),
                'icon' => '🧪'
            ),
            'price' => array(
                'label' => __('Цена', 'parfume-catalog'),
                'icon' => '💰'
            ),
            'rating' => array(
                'label' => __('Рейтинг', 'parfume-catalog'),
                'icon' => '⭐'
            ),
            'durability' => array(
                'label' => __('Дълготрайност', 'parfume-catalog'),
                'icon' => '⏱️'
            ),
            'sillage' => array(
                'label' => __('Ароматна следа', 'parfume-catalog'),
                'icon' => '🌬️'
            ),
            'intensity' => array(
                'label' => __('Интензивност', 'parfume-catalog'),
                'icon' => '🔥'
            ),
            'season' => array(
                'label' => __('Сезон', 'parfume-catalog'),
                'icon' => '🌍'
            ),
            'top_notes' => array(
                'label' => __('Връхни нотки', 'parfume-catalog'),
                'icon' => '🌸'
            ),
            'heart_notes' => array(
                'label' => __('Средни нотки', 'parfume-catalog'),
                'icon' => '🌹'
            ),
            'base_notes' => array(
                'label' => __('Базови нотки', 'parfume-catalog'),
                'icon' => '🌰'
            ),
            'year' => array(
                'label' => __('Година на създаване', 'parfume-catalog'),
                'icon' => '📅'
            ),
            'concentration' => array(
                'label' => __('Концентрация', 'parfume-catalog'),
                'icon' => '💧'
            ),
            'gender' => array(
                'label' => __('Пол', 'parfume-catalog'),
                'icon' => '⚥'
            ),
            'occasion' => array(
                'label' => __('Повод', 'parfume-catalog'),
                'icon' => '🎭'
            ),
            'pros' => array(
                'label' => __('Предимства', 'parfume-catalog'),
                'icon' => '✅'
            ),
            'cons' => array(
                'label' => __('Недостатъци', 'parfume-catalog'),
                'icon' => '❌'
            )
        );
    }
    
    /**
     * Save comparison criteria via AJAX
     */
    public function save_comparison_criteria() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_comparison_action', 'nonce');
        
        $criteria_keys = array_map('sanitize_text_field', $_POST['criteria']);
        $available_criteria = $this->get_available_criteria();
        $ordered_criteria = array();
        
        foreach ($criteria_keys as $index => $key) {
            if (isset($available_criteria[$key])) {
                $ordered_criteria[$key] = $available_criteria[$key];
                $ordered_criteria[$key]['order'] = $index + 1;
            }
        }
        
        if (update_option($this->comparison_option, $ordered_criteria)) {
            wp_send_json_success(array('message' => __('Критериите са запазени успешно', 'parfume-catalog')));
        } else {
            wp_send_json_error(array('message' => __('Грешка при запазване', 'parfume-catalog')));
        }
    }
    
    /**
     * Get comparison settings for frontend use
     */
    public static function get_comparison_settings() {
        return array(
            'enabled' => get_option('parfume_comparison_enabled', true),
            'max_items' => get_option('parfume_comparison_max_items', 4),
            'show_undo' => get_option('parfume_comparison_show_undo', true),
            'show_clear_all' => get_option('parfume_comparison_show_clear_all', true),
            'auto_hide' => get_option('parfume_comparison_auto_hide', true),
            'show_search' => get_option('parfume_comparison_show_search', true),
            'show_visuals' => get_option('parfume_comparison_show_visuals', true),
            'popup_width' => get_option('parfume_comparison_popup_width', 90),
            'popup_position' => get_option('parfume_comparison_popup_position', 'center'),
            'primary_color' => get_option('parfume_comparison_primary_color', '#0073aa'),
            'background_color' => get_option('parfume_comparison_background_color', '#ffffff'),
            'texts' => array(
                'add' => get_option('parfume_comparison_add_text', __('Добави за сравнение', 'parfume-catalog')),
                'remove' => get_option('parfume_comparison_remove_text', __('Премахни от сравнение', 'parfume-catalog')),
                'max_reached' => get_option('parfume_comparison_max_reached_text', __('Максимален брой достигнат', 'parfume-catalog')),
                'empty' => get_option('parfume_comparison_empty_text', __('Все още няма избрани парфюми за сравнение', 'parfume-catalog')),
                'popup_title' => get_option('parfume_comparison_popup_title', __('Сравнение на парфюми', 'parfume-catalog')),
                'clear_all' => get_option('parfume_comparison_clear_all_text', __('Изчисти всички', 'parfume-catalog')),
                'undo' => get_option('parfume_comparison_undo_text', __('Отмени', 'parfume-catalog'))
            )
        );
    }
    
    /**
     * Get active comparison criteria
     */
    public static function get_active_criteria() {
        $comparison_admin = new self();
        return $comparison_admin->get_comparison_criteria();
    }
}

// Initialize the admin comparison
new Parfume_Admin_Comparison();