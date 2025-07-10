<?php
/**
 * Parfume Catalog Admin Comparison
 * 
 * Управление на функционалността за сравняване в админ панела
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin_Comparison {
    
    /**
     * Опция за запазване на критерии
     */
    private $comparison_option = 'parfume_comparison_criteria';
    
    /**
     * Конструктор
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_comparison_page'));
        add_action('admin_init', array($this, 'register_comparison_settings'));
        add_action('wp_ajax_parfume_save_comparison_criteria', array($this, 'ajax_save_comparison_criteria'));
        add_action('wp_ajax_parfume_reorder_criteria', array($this, 'ajax_reorder_criteria'));
        add_action('wp_ajax_parfume_reset_comparison_settings', array($this, 'ajax_reset_comparison_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Добавяне на страница в админ менюто
     */
    public function add_comparison_page() {
        add_submenu_page(
            'edit.php?post_type=parfumes',
            __('Настройки за сравнения', 'parfume-catalog'),
            __('Сравнения', 'parfume-catalog'),
            'manage_options',
            'parfume-comparison',
            array($this, 'render_comparison_page')
        );
    }
    
    /**
     * Регистрация на settings
     */
    public function register_comparison_settings() {
        // Основни настройки
        register_setting('parfume_comparison_settings', 'parfume_comparison_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_max_items', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => array($this, 'sanitize_max_items')
        ));
        
        // Popup настройки
        register_setting('parfume_comparison_settings', 'parfume_comparison_popup_width', array(
            'type' => 'integer',
            'default' => 90,
            'sanitize_callback' => array($this, 'sanitize_popup_width')
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_popup_position', array(
            'type' => 'string',
            'default' => 'center',
            'sanitize_callback' => array($this, 'sanitize_popup_position')
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_popup_z_index', array(
            'type' => 'integer',
            'default' => 9999,
            'sanitize_callback' => 'absint'
        ));
        
        // UX настройки
        register_setting('parfume_comparison_settings', 'parfume_comparison_show_undo', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_show_clear_all', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_auto_hide', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_show_search', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_show_visuals', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        // Текстове
        register_setting('parfume_comparison_settings', 'parfume_comparison_add_text', array(
            'type' => 'string',
            'default' => __('Добави за сравнение', 'parfume-catalog'),
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_remove_text', array(
            'type' => 'string',
            'default' => __('Премахни от сравнение', 'parfume-catalog'),
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_max_reached_text', array(
            'type' => 'string',
            'default' => __('Максимален брой достигнат', 'parfume-catalog'),
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_empty_text', array(
            'type' => 'string',
            'default' => __('Все още няма избрани парфюми за сравнение', 'parfume-catalog'),
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('parfume_comparison_settings', 'parfume_comparison_popup_title', array(
            'type' => 'string',
            'default' => __('Сравнение на парфюми', 'parfume-catalog'),
            'sanitize_callback' => 'sanitize_text_field'
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'parfumes_page_parfume-comparison') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('parfume-admin-comparison', 
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/admin-comparison.js', 
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-tabs'), 
            PARFUME_CATALOG_VERSION, 
            true
        );
        
        wp_localize_script('parfume-admin-comparison', 'parfumeComparisonAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_comparison_admin'),
            'texts' => array(
                'save_success' => __('Настройките са запазени успешно', 'parfume-catalog'),
                'save_error' => __('Грешка при запазване на настройките', 'parfume-catalog'),
                'reset_confirm' => __('Сигурни ли сте, че искате да възстановите настройките по подразбиране?', 'parfume-catalog'),
                'reset_success' => __('Настройките са възстановени по подразбиране', 'parfume-catalog')
            )
        ));
        
        wp_enqueue_style('parfume-admin-comparison', 
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/admin-comparison.css', 
            array(), 
            PARFUME_CATALOG_VERSION
        );
    }
    
    /**
     * Render comparison management page
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
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_comparison_settings'); ?>
                        
                        <h3><?php _e('Основни настройки', 'parfume-catalog'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_enabled"><?php _e('Активирай сравнения', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               id="parfume_comparison_enabled" 
                                               name="parfume_comparison_enabled" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_enabled', true)); ?> />
                                        <?php _e('Включи функционалността за сравняване на парфюми', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_max_items"><?php _e('Максимален брой парфюми', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_comparison_max_items" 
                                           name="parfume_comparison_max_items" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_max_items', 4)); ?>" 
                                           min="2" 
                                           max="10" />
                                    <p class="description"><?php _e('Максимален брой парфюми за едновременно сравнение (2-10)', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php _e('UX настройки', 'parfume-catalog'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Undo функционалност', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_undo" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_undo', true)); ?> />
                                        <?php _e('Показвай бутон "Отмени" за последно премахване', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Изчисти всички', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_clear_all" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_clear_all', true)); ?> />
                                        <?php _e('Показвай бутон за изчистване на всички парфюми', 'parfume-catalog'); ?>
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
                                        <?php _e('Скривай popup автоматично при 0 или 1 парфюм', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Търсене в popup', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_search" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_search', true)); ?> />
                                        <?php _e('Показвай търсачка за добавяне на парфюми в popup-а', 'parfume-catalog'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Визуални елементи', 'parfume-catalog'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="parfume_comparison_show_visuals" 
                                               value="1" 
                                               <?php checked(get_option('parfume_comparison_show_visuals', true)); ?> />
                                        <?php _e('Включи графики и визуални елементи в сравнението', 'parfume-catalog'); ?>
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
                    <p><?php _e('Изберете и подредете критериите, които ще се показват в таблицата за сравнение.', 'parfume-catalog'); ?></p>
                    
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
                                                <button type="button" class="button button-small add-criterion" data-key="<?php echo esc_attr($key); ?>">
                                                    <?php _e('Добави', 'parfume-catalog'); ?>
                                                </button>
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
                                            <span class="criterion-handle dashicons dashicons-menu"></span>
                                            <span class="criterion-icon"><?php echo esc_html($criterion['icon']); ?></span>
                                            <span class="criterion-label"><?php echo esc_html($criterion['label']); ?></span>
                                            <button type="button" class="button button-small remove-criterion" data-key="<?php echo esc_attr($key); ?>">
                                                <?php _e('Премахни', 'parfume-catalog'); ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="criteria-actions">
                            <button type="button" class="button button-primary" id="save-criteria"><?php _e('Запази критерии', 'parfume-catalog'); ?></button>
                            <button type="button" class="button" id="reset-criteria"><?php _e('Възстанови по подразбиране', 'parfume-catalog'); ?></button>
                        </div>
                    </div>
                </div>
                
                <!-- Appearance Tab -->
                <div id="appearance-tab">
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_comparison_settings'); ?>
                        
                        <h3><?php _e('Настройки на popup прозореца', 'parfume-catalog'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_popup_width"><?php _e('Ширина на popup (%)', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="range" 
                                           id="parfume_comparison_popup_width" 
                                           name="parfume_comparison_popup_width" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_popup_width', 90)); ?>" 
                                           min="60" 
                                           max="95" 
                                           step="5" 
                                           oninput="updateWidthValue(this.value)" />
                                    <span id="width-value"><?php echo esc_html(get_option('parfume_comparison_popup_width', 90)); ?>%</span>
                                    <p class="description"><?php _e('Ширина на popup прозореца като процент от екрана', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_popup_position"><?php _e('Позиция на popup', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <select id="parfume_comparison_popup_position" name="parfume_comparison_popup_position">
                                        <option value="center" <?php selected(get_option('parfume_comparison_popup_position', 'center'), 'center'); ?>><?php _e('Център', 'parfume-catalog'); ?></option>
                                        <option value="top" <?php selected(get_option('parfume_comparison_popup_position'), 'top'); ?>><?php _e('Отгоре', 'parfume-catalog'); ?></option>
                                        <option value="bottom" <?php selected(get_option('parfume_comparison_popup_position'), 'bottom'); ?>><?php _e('Отдолу', 'parfume-catalog'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Позиционирайте popup спрямо екрана', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_popup_z_index"><?php _e('Z-index', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="parfume_comparison_popup_z_index" 
                                           name="parfume_comparison_popup_z_index" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_popup_z_index', 9999)); ?>" 
                                           min="1000" 
                                           max="999999" />
                                    <p class="description"><?php _e('Z-index на popup прозореца за правилно позициониране', 'parfume-catalog'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <!-- Texts Tab -->
                <div id="texts-tab">
                    <form method="post" action="options.php">
                        <?php settings_fields('parfume_comparison_settings'); ?>
                        
                        <h3><?php _e('Персонализиране на текстове', 'parfume-catalog'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="parfume_comparison_add_text"><?php _e('Текст за добавяне', 'parfume-catalog'); ?></label>
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
                                    <label for="parfume_comparison_remove_text"><?php _e('Текст за премахване', 'parfume-catalog'); ?></label>
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
                                    <label for="parfume_comparison_max_reached_text"><?php _e('Текст при достигнат максимум', 'parfume-catalog'); ?></label>
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
                                    <label for="parfume_comparison_empty_text"><?php _e('Текст при празно сравнение', 'parfume-catalog'); ?></label>
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
                                    <label for="parfume_comparison_popup_title"><?php _e('Заглавие на popup', 'parfume-catalog'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="parfume_comparison_popup_title" 
                                           name="parfume_comparison_popup_title" 
                                           value="<?php echo esc_attr(get_option('parfume_comparison_popup_title', __('Сравнение на парфюми', 'parfume-catalog'))); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize tabs
            $('#comparison-tabs').tabs();
            
            // Initialize sortable
            $('#active-criteria-list').sortable({
                handle: '.criterion-handle',
                axis: 'y',
                update: function(event, ui) {
                    // Auto-save order when changed
                    var order = [];
                    $('#active-criteria-list .criterion-item').each(function(index) {
                        order.push({
                            key: $(this).data('key'),
                            order: index
                        });
                    });
                    
                    $.post(ajaxurl, {
                        action: 'parfume_reorder_criteria',
                        order: order,
                        nonce: '<?php echo wp_create_nonce('parfume_comparison_admin'); ?>'
                    });
                }
            });
            
            // Add criterion
            $(document).on('click', '.add-criterion', function() {
                var key = $(this).data('key');
                var $item = $(this).closest('.criterion-item');
                
                $item.fadeOut(300, function() {
                    var $activeList = $('#active-criteria-list');
                    var $newItem = $item.clone();
                    
                    $newItem.removeClass('available').addClass('active');
                    $newItem.find('.add-criterion').removeClass('add-criterion').addClass('remove-criterion').text('<?php _e('Премахни', 'parfume-catalog'); ?>');
                    $newItem.prepend('<span class="criterion-handle dashicons dashicons-menu"></span>');
                    
                    $activeList.append($newItem);
                    $newItem.fadeIn(300);
                    
                    $item.remove();
                });
            });
            
            // Remove criterion
            $(document).on('click', '.remove-criterion', function() {
                var key = $(this).data('key');
                var $item = $(this).closest('.criterion-item');
                
                $item.fadeOut(300, function() {
                    var $availableList = $('#available-criteria-list');
                    var $newItem = $item.clone();
                    
                    $newItem.removeClass('active').addClass('available');
                    $newItem.find('.remove-criterion').removeClass('remove-criterion').addClass('add-criterion').text('<?php _e('Добави', 'parfume-catalog'); ?>');
                    $newItem.find('.criterion-handle').remove();
                    
                    $availableList.append($newItem);
                    $newItem.fadeIn(300);
                    
                    $item.remove();
                });
            });
            
            // Save criteria
            $('#save-criteria').click(function() {
                var activeCriteria = [];
                $('#active-criteria-list .criterion-item').each(function(index) {
                    activeCriteria.push({
                        key: $(this).data('key'),
                        order: index
                    });
                });
                
                $.post(ajaxurl, {
                    action: 'parfume_save_comparison_criteria',
                    criteria: activeCriteria,
                    nonce: '<?php echo wp_create_nonce('parfume_comparison_admin'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('<div class="notice notice-success is-dismissible"><p><?php _e('Критериите са запазени успешно', 'parfume-catalog'); ?></p></div>')
                            .insertAfter('#comparison-tabs')
                            .delay(3000)
                            .fadeOut();
                    } else {
                        alert(response.data.message || '<?php _e('Грешка при запазване', 'parfume-catalog'); ?>');
                    }
                });
            });
            
            // Reset criteria
            $('#reset-criteria').click(function() {
                if (confirm('<?php _e('Сигурни ли сте, че искате да възстановите критериите по подразбиране?', 'parfume-catalog'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'parfume_reset_comparison_settings',
                        nonce: '<?php echo wp_create_nonce('parfume_comparison_admin'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php _e('Грешка при възстановяване', 'parfume-catalog'); ?>');
                        }
                    });
                }
            });
            
            // Width slider update
            window.updateWidthValue = function(value) {
                document.getElementById('width-value').textContent = value + '%';
            };
        });
        </script>
        
        <style>
        .criteria-manager {
            margin: 20px 0;
        }
        
        .criteria-columns {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .available-criteria,
        .active-criteria {
            flex: 1;
            min-height: 300px;
        }
        
        .criteria-list {
            border: 1px solid #ddd;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            min-height: 250px;
        }
        
        .criterion-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            margin-bottom: 5px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .criterion-item.active {
            background: #e7f3ff;
            border-color: #0073aa;
        }
        
        .criterion-handle {
            cursor: move;
            color: #666;
        }
        
        .criterion-icon {
            font-size: 16px;
        }
        
        .criterion-label {
            flex: 1;
            font-weight: 500;
        }
        
        .criteria-actions {
            text-align: center;
            padding: 20px 0;
        }
        
        .criteria-actions .button {
            margin: 0 10px;
        }
        
        .sortable .criterion-item {
            cursor: move;
        }
        
        .ui-sortable-helper {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        </style>
        <?php
    }
    
    /**
     * AJAX: Запазване на критерии за сравнение
     */
    public function ajax_save_comparison_criteria() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_comparison_admin')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $criteria = isset($_POST['criteria']) ? $_POST['criteria'] : array();
        $available_criteria = $this->get_available_criteria();
        $formatted_criteria = array();
        
        foreach ($criteria as $item) {
            $key = sanitize_key($item['key']);
            $order = absint($item['order']);
            
            if (isset($available_criteria[$key])) {
                $formatted_criteria[$key] = array(
                    'label' => $available_criteria[$key]['label'],
                    'icon' => $available_criteria[$key]['icon'],
                    'order' => $order
                );
            }
        }
        
        update_option($this->comparison_option, $formatted_criteria);
        
        wp_send_json_success(array(
            'message' => __('Критериите са запазени успешно', 'parfume-catalog')
        ));
    }
    
    /**
     * AJAX: Пренареждане на критерии
     */
    public function ajax_reorder_criteria() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_comparison_admin')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $order = isset($_POST['order']) ? $_POST['order'] : array();
        $current_criteria = $this->get_comparison_criteria();
        
        foreach ($order as $item) {
            $key = sanitize_key($item['key']);
            $new_order = absint($item['order']);
            
            if (isset($current_criteria[$key])) {
                $current_criteria[$key]['order'] = $new_order;
            }
        }
        
        update_option($this->comparison_option, $current_criteria);
        
        wp_send_json_success(array(
            'message' => __('Редът е актуализиран', 'parfume-catalog')
        ));
    }
    
    /**
     * AJAX: Възстановяване на настройки по подразбиране
     */
    public function ajax_reset_comparison_settings() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_comparison_admin')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        // Изтриване на запазени критерии
        delete_option($this->comparison_option);
        
        // Възстановяване на default settings
        $default_options = array(
            'parfume_comparison_enabled' => true,
            'parfume_comparison_max_items' => 4,
            'parfume_comparison_popup_width' => 90,
            'parfume_comparison_popup_position' => 'center',
            'parfume_comparison_popup_z_index' => 9999,
            'parfume_comparison_show_undo' => true,
            'parfume_comparison_show_clear_all' => true,
            'parfume_comparison_auto_hide' => true,
            'parfume_comparison_show_search' => true,
            'parfume_comparison_show_visuals' => true,
            'parfume_comparison_add_text' => __('Добави за сравнение', 'parfume-catalog'),
            'parfume_comparison_remove_text' => __('Премахни от сравнение', 'parfume-catalog'),
            'parfume_comparison_max_reached_text' => __('Максимален брой достигнат', 'parfume-catalog'),
            'parfume_comparison_empty_text' => __('Все още няма избрани парфюми за сравнение', 'parfume-catalog'),
            'parfume_comparison_popup_title' => __('Сравнение на парфюми', 'parfume-catalog')
        );
        
        foreach ($default_options as $option => $value) {
            update_option($option, $value);
        }
        
        wp_send_json_success(array(
            'message' => __('Настройките са възстановени по подразбиране', 'parfume-catalog')
        ));
    }
    
    /**
     * Получаване на активни критерии за сравнение
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
        
        // Сортиране по ред
        uasort($saved_criteria, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        return $saved_criteria;
    }
    
    /**
     * Получаване на всички налични критерии
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
                'icon' => '🎭'
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
            'top_notes' => array(
                'label' => __('Връхни нотки', 'parfume-catalog'),
                'icon' => '🌿'
            ),
            'middle_notes' => array(
                'label' => __('Средни нотки', 'parfume-catalog'),
                'icon' => '🌸'
            ),
            'base_notes' => array(
                'label' => __('Базови нотки', 'parfume-catalog'),
                'icon' => '🌰'
            ),
            'longevity' => array(
                'label' => __('Дълготрайност', 'parfume-catalog'),
                'icon' => '⏰'
            ),
            'sillage' => array(
                'label' => __('Ароматна следа', 'parfume-catalog'),
                'icon' => '👃'
            ),
            'seasons' => array(
                'label' => __('Сезони', 'parfume-catalog'),
                'icon' => '🌤️'
            ),
            'intensity' => array(
                'label' => __('Интензивност', 'parfume-catalog'),
                'icon' => '🔥'
            ),
            'year' => array(
                'label' => __('Година', 'parfume-catalog'),
                'icon' => '📅'
            ),
            'advantages' => array(
                'label' => __('Предимства', 'parfume-catalog'),
                'icon' => '✅'
            ),
            'disadvantages' => array(
                'label' => __('Недостатъци', 'parfume-catalog'),
                'icon' => '❌'
            )
        );
    }
    
    /**
     * Sanitization функции
     */
    public function sanitize_max_items($value) {
        $value = absint($value);
        return ($value >= 2 && $value <= 10) ? $value : 4;
    }
    
    public function sanitize_popup_width($value) {
        $value = absint($value);
        return ($value >= 60 && $value <= 95) ? $value : 90;
    }
    
    public function sanitize_popup_position($value) {
        $allowed = array('center', 'top', 'bottom');
        return in_array($value, $allowed) ? $value : 'center';
    }
    
    /**
     * Static helper методи за external access
     */
    public static function get_active_criteria() {
        $instance = new self();
        return $instance->get_comparison_criteria();
    }
    
    public static function is_comparison_enabled() {
        return get_option('parfume_comparison_enabled', true);
    }
    
    public static function get_comparison_settings() {
        return array(
            'enabled' => get_option('parfume_comparison_enabled', true),
            'max_items' => get_option('parfume_comparison_max_items', 4),
            'popup_width' => get_option('parfume_comparison_popup_width', 90),
            'popup_position' => get_option('parfume_comparison_popup_position', 'center'),
            'popup_z_index' => get_option('parfume_comparison_popup_z_index', 9999),
            'show_undo' => get_option('parfume_comparison_show_undo', true),
            'show_clear_all' => get_option('parfume_comparison_show_clear_all', true),
            'auto_hide' => get_option('parfume_comparison_auto_hide', true),
            'show_search' => get_option('parfume_comparison_show_search', true),
            'show_visuals' => get_option('parfume_comparison_show_visuals', true),
            'texts' => array(
                'add' => get_option('parfume_comparison_add_text', __('Добави за сравнение', 'parfume-catalog')),
                'remove' => get_option('parfume_comparison_remove_text', __('Премахни от сравнение', 'parfume-catalog')),
                'max_reached' => get_option('parfume_comparison_max_reached_text', __('Максимален брой достигнат', 'parfume-catalog')),
                'empty' => get_option('parfume_comparison_empty_text', __('Все още няма избрани парфюми за сравнение', 'parfume-catalog')),
                'popup_title' => get_option('parfume_comparison_popup_title', __('Сравнение на парфюми', 'parfume-catalog'))
            )
        );
    }
    
    /**
     * Render comparison button for archive/list pages
     */
    public static function render_comparison_button($post_id, $classes = '') {
        if (!self::is_comparison_enabled()) {
            return '';
        }
        
        $settings = self::get_comparison_settings();
        $add_text = $settings['texts']['add'];
        $remove_text = $settings['texts']['remove'];
        
        $button_classes = 'parfume-comparison-btn ' . esc_attr($classes);
        
        return sprintf(
            '<button type="button" class="%s" data-post-id="%d" data-add-text="%s" data-remove-text="%s">%s</button>',
            $button_classes,
            absint($post_id),
            esc_attr($add_text),
            esc_attr($remove_text),
            esc_html($add_text)
        );
    }
}