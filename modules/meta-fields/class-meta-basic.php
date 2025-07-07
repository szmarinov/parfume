<?php
/**
 * Basic Meta Fields Class
 * 
 * Handles basic meta fields for parfumes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Meta_Basic {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_basic_info',
            __('Основна информация', 'parfume-catalog'),
            array($this, 'render_basic_info_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_characteristics',
            __('Характеристики на аромата', 'parfume-catalog'),
            array($this, 'render_characteristics_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_pros_cons',
            __('Предимства и недостатъци', 'parfume-catalog'),
            array($this, 'render_pros_cons_meta_box'),
            'parfumes',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume_additional_info',
            __('Допълнителна информация', 'parfume-catalog'),
            array($this, 'render_additional_info_meta_box'),
            'parfumes',
            'side',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        global $post_type;
        
        if ($hook === 'post.php' && $post_type === 'parfumes') {
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_style('jquery-ui-slider', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        }
    }
    
    /**
     * Render basic info meta box
     */
    public function render_basic_info_meta_box($post) {
        wp_nonce_field('parfume_basic_meta_nonce', 'parfume_basic_meta_nonce_field');
        
        // Get saved values
        $year_created = get_post_meta($post->ID, '_parfume_year_created', true);
        $concentration = get_post_meta($post->ID, '_parfume_concentration', true);
        $perfumer = get_post_meta($post->ID, '_parfume_perfumer', true);
        $description_short = get_post_meta($post->ID, '_parfume_description_short', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_year_created"><?php _e('Година на създаване', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="parfume_year_created" 
                           name="parfume_year_created" 
                           value="<?php echo esc_attr($year_created); ?>" 
                           min="1800" 
                           max="<?php echo date('Y'); ?>" 
                           class="small-text" />
                    <p class="description"><?php _e('Година, в която е създаден парфюмът', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="parfume_concentration"><?php _e('Концентрация', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_concentration" name="parfume_concentration">
                        <option value=""><?php _e('Изберете концентрация', 'parfume-catalog'); ?></option>
                        <option value="EdP" <?php selected($concentration, 'EdP'); ?>><?php _e('Eau de Parfum (EdP)', 'parfume-catalog'); ?></option>
                        <option value="EdT" <?php selected($concentration, 'EdT'); ?>><?php _e('Eau de Toilette (EdT)', 'parfume-catalog'); ?></option>
                        <option value="EdC" <?php selected($concentration, 'EdC'); ?>><?php _e('Eau de Cologne (EdC)', 'parfume-catalog'); ?></option>
                        <option value="Parfum" <?php selected($concentration, 'Parfum'); ?>><?php _e('Parfum', 'parfume-catalog'); ?></option>
                        <option value="EdF" <?php selected($concentration, 'EdF'); ?>><?php _e('Eau Fraiche (EdF)', 'parfume-catalog'); ?></option>
                    </select>
                    <p class="description"><?php _e('Концентрация на парфюмните масла', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="parfume_perfumer"><?php _e('Парфюмерист', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="parfume_perfumer" 
                           name="parfume_perfumer" 
                           value="<?php echo esc_attr($perfumer); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Име на парфюмериста, създал аромата', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="parfume_description_short"><?php _e('Кратко описание', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <textarea id="parfume_description_short" 
                              name="parfume_description_short" 
                              rows="3" 
                              class="large-text"><?php echo esc_textarea($description_short); ?></textarea>
                    <p class="description"><?php _e('Кратко описание за показване в листинги и превюта', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render characteristics meta box
     */
    public function render_characteristics_meta_box($post) {
        // Get saved values
        $durability = get_post_meta($post->ID, '_parfume_durability', true) ?: 3;
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true) ?: 3;
        $price_range = get_post_meta($post->ID, '_parfume_price_range', true) ?: 3;
        $suitable_for = get_post_meta($post->ID, '_parfume_suitable_for', true) ?: array();
        
        if (!is_array($suitable_for)) {
            $suitable_for = array();
        }
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_durability"><?php _e('Дълготрайност', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <div class="slider-container">
                        <div id="durability-slider" class="parfume-slider" data-value="<?php echo esc_attr($durability); ?>"></div>
                        <input type="hidden" id="parfume_durability" name="parfume_durability" value="<?php echo esc_attr($durability); ?>" />
                        <div class="slider-labels">
                            <span><?php _e('Много слаб', 'parfume-catalog'); ?></span>
                            <span><?php _e('Слаб', 'parfume-catalog'); ?></span>
                            <span><?php _e('Умерен', 'parfume-catalog'); ?></span>
                            <span><?php _e('Траен', 'parfume-catalog'); ?></span>
                            <span><?php _e('Изключително траен', 'parfume-catalog'); ?></span>
                        </div>
                    </div>
                    <p class="description"><?php _e('Оценка на дълготрайността на аромата (1-5)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="parfume_sillage"><?php _e('Ароматна следа', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <div class="slider-container">
                        <div id="sillage-slider" class="parfume-slider" data-value="<?php echo esc_attr($sillage); ?>"></div>
                        <input type="hidden" id="parfume_sillage" name="parfume_sillage" value="<?php echo esc_attr($sillage); ?>" />
                        <div class="slider-labels">
                            <span><?php _e('Слаба', 'parfume-catalog'); ?></span>
                            <span><?php _e('Умерена', 'parfume-catalog'); ?></span>
                            <span><?php _e('Силна', 'parfume-catalog'); ?></span>
                            <span><?php _e('Огромна', 'parfume-catalog'); ?></span>
                        </div>
                    </div>
                    <p class="description"><?php _e('Оценка на ароматната следа (1-4)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="parfume_price_range"><?php _e('Ценова категория', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <div class="slider-container">
                        <div id="price-slider" class="parfume-slider" data-value="<?php echo esc_attr($price_range); ?>"></div>
                        <input type="hidden" id="parfume_price_range" name="parfume_price_range" value="<?php echo esc_attr($price_range); ?>" />
                        <div class="slider-labels">
                            <span><?php _e('Евтин', 'parfume-catalog'); ?></span>
                            <span><?php _e('Добра цена', 'parfume-catalog'); ?></span>
                            <span><?php _e('Приемлива цена', 'parfume-catalog'); ?></span>
                            <span><?php _e('Скъп', 'parfume-catalog'); ?></span>
                            <span><?php _e('Прекалено скъп', 'parfume-catalog'); ?></span>
                        </div>
                    </div>
                    <p class="description"><?php _e('Оценка на цената спрямо качеството (1-5)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Подходящ за', 'parfume-catalog'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Подходящ за', 'parfume-catalog'); ?></legend>
                        
                        <label>
                            <input type="checkbox" 
                                   name="parfume_suitable_for[]" 
                                   value="spring" 
                                   <?php checked(in_array('spring', $suitable_for)); ?> />
                            <?php _e('Пролет', 'parfume-catalog'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="parfume_suitable_for[]" 
                                   value="summer" 
                                   <?php checked(in_array('summer', $suitable_for)); ?> />
                            <?php _e('Лято', 'parfume-catalog'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="parfume_suitable_for[]" 
                                   value="autumn" 
                                   <?php checked(in_array('autumn', $suitable_for)); ?> />
                            <?php _e('Есен', 'parfume-catalog'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="parfume_suitable_for[]" 
                                   value="winter" 
                                   <?php checked(in_array('winter', $suitable_for)); ?> />
                            <?php _e('Зима', 'parfume-catalog'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="parfume_suitable_for[]" 
                                   value="day" 
                                   <?php checked(in_array('day', $suitable_for)); ?> />
                            <?php _e('Ден', 'parfume-catalog'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="parfume_suitable_for[]" 
                                   value="night" 
                                   <?php checked(in_array('night', $suitable_for)); ?> />
                            <?php _e('Нощ', 'parfume-catalog'); ?>
                        </label>
                    </fieldset>
                    <p class="description"><?php _e('Изберете за кои случаи е подходящ парфюмът', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        
        <style>
        .slider-container {
            margin: 10px 0;
        }
        
        .parfume-slider {
            width: 300px;
            margin: 10px 0;
        }
        
        .slider-labels {
            display: flex;
            justify-content: space-between;
            width: 300px;
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .slider-labels span {
            text-align: center;
            flex: 1;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Durability slider (1-5)
            $('#durability-slider').slider({
                min: 1,
                max: 5,
                value: $('#durability-slider').data('value'),
                slide: function(event, ui) {
                    $('#parfume_durability').val(ui.value);
                }
            });
            
            // Sillage slider (1-4)
            $('#sillage-slider').slider({
                min: 1,
                max: 4,
                value: $('#sillage-slider').data('value'),
                slide: function(event, ui) {
                    $('#parfume_sillage').val(ui.value);
                }
            });
            
            // Price slider (1-5)
            $('#price-slider').slider({
                min: 1,
                max: 5,
                value: $('#price-slider').data('value'),
                slide: function(event, ui) {
                    $('#parfume_price_range').val(ui.value);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render pros and cons meta box
     */
    public function render_pros_cons_meta_box($post) {
        $pros = get_post_meta($post->ID, '_parfume_pros', true) ?: array('');
        $cons = get_post_meta($post->ID, '_parfume_cons', true) ?: array('');
        
        if (!is_array($pros)) {
            $pros = array('');
        }
        if (!is_array($cons)) {
            $cons = array('');
        }
        ?>
        <div class="pros-cons-container">
            <div class="pros-cons-columns">
                <div class="pros-column">
                    <h4><?php _e('Предимства', 'parfume-catalog'); ?></h4>
                    <div id="pros-list">
                        <?php foreach ($pros as $index => $pro): ?>
                            <div class="pros-cons-item">
                                <input type="text" 
                                       name="parfume_pros[]" 
                                       value="<?php echo esc_attr($pro); ?>" 
                                       placeholder="<?php _e('Добавете предимство', 'parfume-catalog'); ?>" 
                                       class="regular-text" />
                                <button type="button" class="button remove-item" <?php echo $index === 0 ? 'style="display:none;"' : ''; ?>>
                                    <?php _e('Премахни', 'parfume-catalog'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-pro" class="button button-secondary">
                        <?php _e('Добави предимство', 'parfume-catalog'); ?>
                    </button>
                </div>
                
                <div class="cons-column">
                    <h4><?php _e('Недостатъци', 'parfume-catalog'); ?></h4>
                    <div id="cons-list">
                        <?php foreach ($cons as $index => $con): ?>
                            <div class="pros-cons-item">
                                <input type="text" 
                                       name="parfume_cons[]" 
                                       value="<?php echo esc_attr($con); ?>" 
                                       placeholder="<?php _e('Добавете недостатък', 'parfume-catalog'); ?>" 
                                       class="regular-text" />
                                <button type="button" class="button remove-item" <?php echo $index === 0 ? 'style="display:none;"' : ''; ?>>
                                    <?php _e('Премахни', 'parfume-catalog'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-con" class="button button-secondary">
                        <?php _e('Добави недостатък', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .pros-cons-container {
            margin-top: 15px;
        }
        
        .pros-cons-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .pros-cons-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }
        
        .pros-cons-item input {
            flex: 1;
        }
        
        .pros-column h4 {
            color: #46b450;
            margin-bottom: 15px;
        }
        
        .cons-column h4 {
            color: #dc3232;
            margin-bottom: 15px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Add pro
            $('#add-pro').click(function() {
                var newItem = '<div class="pros-cons-item">' +
                    '<input type="text" name="parfume_pros[]" placeholder="<?php _e('Добавете предимство', 'parfume-catalog'); ?>" class="regular-text" />' +
                    '<button type="button" class="button remove-item"><?php _e('Премахни', 'parfume-catalog'); ?></button>' +
                    '</div>';
                $('#pros-list').append(newItem);
            });
            
            // Add con
            $('#add-con').click(function() {
                var newItem = '<div class="pros-cons-item">' +
                    '<input type="text" name="parfume_cons[]" placeholder="<?php _e('Добавете недостатък', 'parfume-catalog'); ?>" class="regular-text" />' +
                    '<button type="button" class="button remove-item"><?php _e('Премахни', 'parfume-catalog'); ?></button>' +
                    '</div>';
                $('#cons-list').append(newItem);
            });
            
            // Remove item
            $(document).on('click', '.remove-item', function() {
                $(this).closest('.pros-cons-item').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render additional info meta box
     */
    public function render_additional_info_meta_box($post) {
        $launch_date = get_post_meta($post->ID, '_parfume_launch_date', true);
        $discontinued = get_post_meta($post->ID, '_parfume_discontinued', true);
        $limited_edition = get_post_meta($post->ID, '_parfume_limited_edition', true);
        $unisex = get_post_meta($post->ID, '_parfume_unisex', true);
        $occasion = get_post_meta($post->ID, '_parfume_occasion', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_launch_date"><?php _e('Дата на пускане', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="date" 
                           id="parfume_launch_date" 
                           name="parfume_launch_date" 
                           value="<?php echo esc_attr($launch_date); ?>" />
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Статус', 'parfume-catalog'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="parfume_discontinued" 
                               value="1" 
                               <?php checked($discontinued, '1'); ?> />
                        <?php _e('Спрян от производство', 'parfume-catalog'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" 
                               name="parfume_limited_edition" 
                               value="1" 
                               <?php checked($limited_edition, '1'); ?> />
                        <?php _e('Лимитирано издание', 'parfume-catalog'); ?>
                    </label><br>
                    
                    <label>
                        <input type="checkbox" 
                               name="parfume_unisex" 
                               value="1" 
                               <?php checked($unisex, '1'); ?> />
                        <?php _e('Унисекс', 'parfume-catalog'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="parfume_occasion"><?php _e('Подходящ повод', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_occasion" name="parfume_occasion">
                        <option value=""><?php _e('Изберете повод', 'parfume-catalog'); ?></option>
                        <option value="daily" <?php selected($occasion, 'daily'); ?>><?php _e('Ежедневно', 'parfume-catalog'); ?></option>
                        <option value="work" <?php selected($occasion, 'work'); ?>><?php _e('Работа', 'parfume-catalog'); ?></option>
                        <option value="evening" <?php selected($occasion, 'evening'); ?>><?php _e('Вечерен', 'parfume-catalog'); ?></option>
                        <option value="formal" <?php selected($occasion, 'formal'); ?>><?php _e('Официален', 'parfume-catalog'); ?></option>
                        <option value="casual" <?php selected($occasion, 'casual'); ?>><?php _e('Неформален', 'parfume-catalog'); ?></option>
                        <option value="romantic" <?php selected($occasion, 'romantic'); ?>><?php _e('Романтичен', 'parfume-catalog'); ?></option>
                        <option value="sport" <?php selected($occasion, 'sport'); ?>><?php _e('Спорт', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['parfume_basic_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['parfume_basic_meta_nonce_field'], 'parfume_basic_meta_nonce')) {
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save basic info fields
        $basic_fields = array(
            'parfume_year_created' => 'absint',
            'parfume_concentration' => 'sanitize_text_field',
            'parfume_perfumer' => 'sanitize_text_field',
            'parfume_description_short' => 'sanitize_textarea_field'
        );
        
        foreach ($basic_fields as $field => $sanitize_function) {
            if (isset($_POST[$field])) {
                $value = $sanitize_function($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        // Save characteristics
        $characteristics = array(
            'parfume_durability' => 'absint',
            'parfume_sillage' => 'absint',
            'parfume_price_range' => 'absint'
        );
        
        foreach ($characteristics as $field => $sanitize_function) {
            if (isset($_POST[$field])) {
                $value = $sanitize_function($_POST[$field]);
                // Ensure values are within range
                if ($field === 'parfume_sillage') {
                    $value = max(1, min(4, $value));
                } else {
                    $value = max(1, min(5, $value));
                }
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        // Save suitable for checkboxes
        if (isset($_POST['parfume_suitable_for'])) {
            $suitable_for = array_map('sanitize_text_field', $_POST['parfume_suitable_for']);
            update_post_meta($post_id, '_parfume_suitable_for', $suitable_for);
        } else {
            update_post_meta($post_id, '_parfume_suitable_for', array());
        }
        
        // Save pros and cons
        if (isset($_POST['parfume_pros'])) {
            $pros = array_filter(array_map('sanitize_text_field', $_POST['parfume_pros']));
            update_post_meta($post_id, '_parfume_pros', $pros);
        }
        
        if (isset($_POST['parfume_cons'])) {
            $cons = array_filter(array_map('sanitize_text_field', $_POST['parfume_cons']));
            update_post_meta($post_id, '_parfume_cons', $cons);
        }
        
        // Save additional info
        $additional_fields = array(
            'parfume_launch_date' => 'sanitize_text_field',
            'parfume_occasion' => 'sanitize_text_field'
        );
        
        foreach ($additional_fields as $field => $sanitize_function) {
            if (isset($_POST[$field])) {
                $value = $sanitize_function($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        // Save checkboxes for additional info
        $checkbox_fields = array(
            'parfume_discontinued',
            'parfume_limited_edition',
            'parfume_unisex'
        );
        
        foreach ($checkbox_fields as $field) {
            $value = isset($_POST[$field]) ? '1' : '0';
            update_post_meta($post_id, '_' . $field, $value);
        }
    }
    
    /**
     * Get parfume basic info
     */
    public static function get_parfume_info($post_id) {
        return array(
            'year_created' => get_post_meta($post_id, '_parfume_year_created', true),
            'concentration' => get_post_meta($post_id, '_parfume_concentration', true),
            'perfumer' => get_post_meta($post_id, '_parfume_perfumer', true),
            'description_short' => get_post_meta($post_id, '_parfume_description_short', true),
            'durability' => get_post_meta($post_id, '_parfume_durability', true) ?: 3,
            'sillage' => get_post_meta($post_id, '_parfume_sillage', true) ?: 3,
            'price_range' => get_post_meta($post_id, '_parfume_price_range', true) ?: 3,
            'suitable_for' => get_post_meta($post_id, '_parfume_suitable_for', true) ?: array(),
            'pros' => get_post_meta($post_id, '_parfume_pros', true) ?: array(),
            'cons' => get_post_meta($post_id, '_parfume_cons', true) ?: array(),
            'launch_date' => get_post_meta($post_id, '_parfume_launch_date', true),
            'discontinued' => get_post_meta($post_id, '_parfume_discontinued', true),
            'limited_edition' => get_post_meta($post_id, '_parfume_limited_edition', true),
            'unisex' => get_post_meta($post_id, '_parfume_unisex', true),
            'occasion' => get_post_meta($post_id, '_parfume_occasion', true)
        );
    }
    
    /**
     * Get durability label
     */
    public static function get_durability_label($value) {
        $labels = array(
            1 => __('Много слаб', 'parfume-catalog'),
            2 => __('Слаб', 'parfume-catalog'),
            3 => __('Умерен', 'parfume-catalog'),
            4 => __('Траен', 'parfume-catalog'),
            5 => __('Изключително траен', 'parfume-catalog')
        );
        
        return isset($labels[$value]) ? $labels[$value] : '';
    }
    
    /**
     * Get sillage label
     */
    public static function get_sillage_label($value) {
        $labels = array(
            1 => __('Слаба', 'parfume-catalog'),
            2 => __('Умерена', 'parfume-catalog'),
            3 => __('Силна', 'parfume-catalog'),
            4 => __('Огромна', 'parfume-catalog')
        );
        
        return isset($labels[$value]) ? $labels[$value] : '';
    }
    
    /**
     * Get price range label
     */
    public static function get_price_range_label($value) {
        $labels = array(
            1 => __('Евтин', 'parfume-catalog'),
            2 => __('Добра цена', 'parfume-catalog'),
            3 => __('Приемлива цена', 'parfume-catalog'),
            4 => __('Скъп', 'parfume-catalog'),
            5 => __('Прекалено скъп', 'parfume-catalog')
        );
        
        return isset($labels[$value]) ? $labels[$value] : '';
    }
    
    /**
     * Get suitable for labels
     */
    public static function get_suitable_for_labels() {
        return array(
            'spring' => __('Пролет', 'parfume-catalog'),
            'summer' => __('Лято', 'parfume-catalog'),
            'autumn' => __('Есен', 'parfume-catalog'),
            'winter' => __('Зима', 'parfume-catalog'),
            'day' => __('Ден', 'parfume-catalog'),
            'night' => __('Нощ', 'parfume-catalog')
        );
    }
    
    /**
     * Get occasion labels
     */
    public static function get_occasion_labels() {
        return array(
            'daily' => __('Ежедневно', 'parfume-catalog'),
            'work' => __('Работа', 'parfume-catalog'),
            'evening' => __('Вечерен', 'parfume-catalog'),
            'formal' => __('Официален', 'parfume-catalog'),
            'casual' => __('Неформален', 'parfume-catalog'),
            'romantic' => __('Романтичен', 'parfume-catalog'),
            'sport' => __('Спорт', 'parfume-catalog')
        );
    }
}

// Initialize the basic meta fields
new Parfume_Meta_Basic();