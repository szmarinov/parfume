<?php
/**
 * Parfume Catalog Meta Fields Class
 * 
 * Управлява базовите мета полета за парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Meta_Fields {

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_parfume_import_notes', array($this, 'handle_notes_import'));
    }

    /**
     * Добавяне на мета boxes
     */
    public function add_meta_boxes() {
        // Основни характеристики
        add_meta_box(
            'parfume_basic_info',
            __('Основни характеристики', 'parfume-catalog'),
            array($this, 'basic_info_meta_box'),
            'parfumes',
            'normal',
            'high'
        );

        // Нотки и състав
        add_meta_box(
            'parfume_notes_composition',
            __('Нотки и състав', 'parfume-catalog'),
            array($this, 'notes_composition_meta_box'),
            'parfumes',
            'normal',
            'high'
        );

        // Графика на аромата
        add_meta_box(
            'parfume_aroma_stats',
            __('Графика на аромата', 'parfume-catalog'),
            array($this, 'aroma_stats_meta_box'),
            'parfumes',
            'normal',
            'high'
        );

        // Предимства и недостатъци
        add_meta_box(
            'parfume_pros_cons',
            __('Предимства и недостатъци', 'parfume-catalog'),
            array($this, 'pros_cons_meta_box'),
            'parfumes',
            'normal',
            'default'
        );

        // Настройки за single страница
        add_meta_box(
            'parfume_single_settings',
            __('Настройки за single страница', 'parfume-catalog'),
            array($this, 'single_settings_meta_box'),
            'parfumes',
            'side',
            'default'
        );

        // JSON Import за нотки (само за taxonomy нотки)
        add_meta_box(
            'parfume_notes_import',
            __('JSON Import за нотки', 'parfume-catalog'),
            array($this, 'notes_import_meta_box'),
            'parfume_notes',
            'side',
            'default'
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-sortable');
            
            // Custom admin script
            wp_enqueue_script(
                'parfume-meta-fields',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/meta-fields.js',
                array('jquery', 'jquery-ui-sortable'),
                PARFUME_CATALOG_VERSION,
                true
            );
            
            wp_localize_script('parfume-meta-fields', 'parfume_meta_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_meta_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Сигурни ли сте?', 'parfume-catalog'),
                    'add_item' => __('Добави', 'parfume-catalog'),
                    'remove_item' => __('Премахни', 'parfume-catalog')
                )
            ));
        }
    }

    /**
     * Основни характеристики meta box
     */
    public function basic_info_meta_box($post) {
        wp_nonce_field('parfume_meta_nonce', 'parfume_meta_nonce_field');

        // Получаване на запазени стойности
        $suitable_day = get_post_meta($post->ID, '_parfume_suitable_day', true);
        $suitable_night = get_post_meta($post->ID, '_parfume_suitable_night', true);
        $launch_year = get_post_meta($post->ID, '_parfume_launch_year', true);
        $concentration = get_post_meta($post->ID, '_parfume_concentration', true);
        $main_notes = get_post_meta($post->ID, '_parfume_main_notes', true);
        $perfumer = get_post_meta($post->ID, '_parfume_perfumer', true);
        $description_short = get_post_meta($post->ID, '_parfume_description_short', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_suitable_day"><?php _e('Подходящ за ден', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="parfume_suitable_day" name="parfume_suitable_day" value="1" <?php checked(1, $suitable_day); ?> />
                    <label for="parfume_suitable_day"><?php _e('Този парфюм е подходящ за носене през деня', 'parfume-catalog'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_suitable_night"><?php _e('Подходящ за нощ', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="parfume_suitable_night" name="parfume_suitable_night" value="1" <?php checked(1, $suitable_night); ?> />
                    <label for="parfume_suitable_night"><?php _e('Този парфюм е подходящ за носене през нощта', 'parfume-catalog'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_launch_year"><?php _e('Година на излизане', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="number" id="parfume_launch_year" name="parfume_launch_year" value="<?php echo esc_attr($launch_year); ?>" min="1900" max="<?php echo date('Y'); ?>" class="small-text" />
                    <p class="description"><?php _e('Година, в която парфюмът е пуснат на пазара', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_concentration"><?php _e('Концентрация', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_concentration" name="parfume_concentration" class="regular-text">
                        <option value=""><?php _e('Изберете концентрация', 'parfume-catalog'); ?></option>
                        <option value="edt" <?php selected($concentration, 'edt'); ?>><?php _e('EDT (Eau de Toilette)', 'parfume-catalog'); ?></option>
                        <option value="edp" <?php selected($concentration, 'edp'); ?>><?php _e('EDP (Eau de Parfum)', 'parfume-catalog'); ?></option>
                        <option value="parfum" <?php selected($concentration, 'parfum'); ?>><?php _e('Parfum', 'parfume-catalog'); ?></option>
                        <option value="cologne" <?php selected($concentration, 'cologne'); ?>><?php _e('Cologne', 'parfume-catalog'); ?></option>
                        <option value="extrait" <?php selected($concentration, 'extrait'); ?>><?php _e('Extrait de Parfum', 'parfume-catalog'); ?></option>
                    </select>
                    <p class="description"><?php _e('Концентрация на ароматните масла в парфюма', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_perfumer"><?php _e('Парфюмерист', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="text" id="parfume_perfumer" name="parfume_perfumer" value="<?php echo esc_attr($perfumer); ?>" class="regular-text" />
                    <p class="description"><?php _e('Име на парфюмериста, създал аромата', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_description_short"><?php _e('Кратко описание', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <textarea id="parfume_description_short" name="parfume_description_short" rows="3" class="large-text"><?php echo esc_textarea($description_short); ?></textarea>
                    <p class="description"><?php _e('Кратко описание за показване в листинги и превюта', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_main_notes"><?php _e('Основни нотки', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <textarea id="parfume_main_notes" name="parfume_main_notes" rows="2" class="large-text"><?php echo esc_textarea($main_notes); ?></textarea>
                    <p class="description"><?php _e('Основни ароматни нотки за показване в листинги (разделени със запетая)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Нотки и състав meta box
     */
    public function notes_composition_meta_box($post) {
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
        $heart_notes = get_post_meta($post->ID, '_parfume_heart_notes', true);
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);
        
        if (!is_array($top_notes)) $top_notes = array();
        if (!is_array($heart_notes)) $heart_notes = array();
        if (!is_array($base_notes)) $base_notes = array();
        
        ?>
        <div class="parfume-notes-composition">
            <div class="notes-section">
                <h3><?php _e('Върхни нотки', 'parfume-catalog'); ?></h3>
                <div class="notes-list" data-type="top">
                    <?php foreach ($top_notes as $note): ?>
                        <div class="note-item">
                            <input type="text" name="parfume_top_notes[]" value="<?php echo esc_attr($note); ?>" placeholder="<?php _e('Въведете нотка', 'parfume-catalog'); ?>" />
                            <button type="button" class="button remove-note">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-note" data-type="top"><?php _e('Добави върхна нотка', 'parfume-catalog'); ?></button>
            </div>

            <div class="notes-section">
                <h3><?php _e('Средни нотки (сърце)', 'parfume-catalog'); ?></h3>
                <div class="notes-list" data-type="heart">
                    <?php foreach ($heart_notes as $note): ?>
                        <div class="note-item">
                            <input type="text" name="parfume_heart_notes[]" value="<?php echo esc_attr($note); ?>" placeholder="<?php _e('Въведете нотка', 'parfume-catalog'); ?>" />
                            <button type="button" class="button remove-note">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-note" data-type="heart"><?php _e('Добави средна нотка', 'parfume-catalog'); ?></button>
            </div>

            <div class="notes-section">
                <h3><?php _e('Базови нотки', 'parfume-catalog'); ?></h3>
                <div class="notes-list" data-type="base">
                    <?php foreach ($base_notes as $note): ?>
                        <div class="note-item">
                            <input type="text" name="parfume_base_notes[]" value="<?php echo esc_attr($note); ?>" placeholder="<?php _e('Въведете нотка', 'parfume-catalog'); ?>" />
                            <button type="button" class="button remove-note">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-note" data-type="base"><?php _e('Добави базова нотка', 'parfume-catalog'); ?></button>
            </div>
        </div>

        <script type="text/template" id="note-item-template">
            <div class="note-item">
                <input type="text" name="parfume_{{type}}_notes[]" value="" placeholder="<?php _e('Въведете нотка', 'parfume-catalog'); ?>" />
                <button type="button" class="button remove-note">×</button>
            </div>
        </script>
        <?php
    }

    /**
     * Графика на аромата meta box
     */
    public function aroma_stats_meta_box($post) {
        $durability = get_post_meta($post->ID, '_parfume_durability', true) ?: 3;
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true) ?: 3;
        $gender = get_post_meta($post->ID, '_parfume_gender', true) ?: 3;
        $price_range = get_post_meta($post->ID, '_parfume_price_range', true) ?: 3;

        ?>
        <div class="parfume-aroma-stats">
            <div class="stats-grid">
                <div class="stat-section">
                    <h3><?php _e('Дълготрайност', 'parfume-catalog'); ?></h3>
                    <div class="stat-bars">
                        <?php
                        $durability_labels = array(
                            1 => __('Много слаб', 'parfume-catalog'),
                            2 => __('Слаб', 'parfume-catalog'),
                            3 => __('Умерен', 'parfume-catalog'),
                            4 => __('Траен', 'parfume-catalog'),
                            5 => __('Изключително траен', 'parfume-catalog')
                        );
                        
                        foreach ($durability_labels as $value => $label) {
                            $checked = ($durability == $value) ? 'checked' : '';
                            echo '<label class="stat-bar-label">';
                            echo '<input type="radio" name="parfume_durability" value="' . $value . '" ' . $checked . ' />';
                            echo '<span class="stat-bar-visual" data-level="' . $value . '"></span>';
                            echo '<span class="stat-bar-text">' . $label . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>

                <div class="stat-section">
                    <h3><?php _e('Ароматна следа', 'parfume-catalog'); ?></h3>
                    <div class="stat-bars">
                        <?php
                        $sillage_labels = array(
                            1 => __('Слаба', 'parfume-catalog'),
                            2 => __('Умерена', 'parfume-catalog'),
                            3 => __('Силна', 'parfume-catalog'),
                            4 => __('Огромна', 'parfume-catalog')
                        );
                        
                        foreach ($sillage_labels as $value => $label) {
                            $checked = ($sillage == $value) ? 'checked' : '';
                            echo '<label class="stat-bar-label">';
                            echo '<input type="radio" name="parfume_sillage" value="' . $value . '" ' . $checked . ' />';
                            echo '<span class="stat-bar-visual" data-level="' . $value . '"></span>';
                            echo '<span class="stat-bar-text">' . $label . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>

                <div class="stat-section">
                    <h3><?php _e('Пол', 'parfume-catalog'); ?></h3>
                    <div class="stat-bars">
                        <?php
                        $gender_labels = array(
                            1 => __('Дамски', 'parfume-catalog'),
                            2 => __('Мъжки', 'parfume-catalog'),
                            3 => __('Унисекс', 'parfume-catalog'),
                            4 => __('По-млади', 'parfume-catalog'),
                            5 => __('По-зрели', 'parfume-catalog')
                        );
                        
                        foreach ($gender_labels as $value => $label) {
                            $checked = ($gender == $value) ? 'checked' : '';
                            echo '<label class="stat-bar-label">';
                            echo '<input type="radio" name="parfume_gender" value="' . $value . '" ' . $checked . ' />';
                            echo '<span class="stat-bar-visual" data-level="' . $value . '"></span>';
                            echo '<span class="stat-bar-text">' . $label . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>

                <div class="stat-section">
                    <h3><?php _e('Цена', 'parfume-catalog'); ?></h3>
                    <div class="stat-bars">
                        <?php
                        $price_labels = array(
                            1 => __('Прекалено скъп', 'parfume-catalog'),
                            2 => __('Скъп', 'parfume-catalog'),
                            3 => __('Приемлива цена', 'parfume-catalog'),
                            4 => __('Добра цена', 'parfume-catalog'),
                            5 => __('Евтин', 'parfume-catalog')
                        );
                        
                        foreach ($price_labels as $value => $label) {
                            $checked = ($price_range == $value) ? 'checked' : '';
                            echo '<label class="stat-bar-label">';
                            echo '<input type="radio" name="parfume_price_range" value="' . $value . '" ' . $checked . ' />';
                            echo '<span class="stat-bar-visual" data-level="' . $value . '"></span>';
                            echo '<span class="stat-bar-text">' . $label . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Предимства и недостатъци meta box
     */
    public function pros_cons_meta_box($post) {
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);
        
        if (!is_array($pros)) $pros = array();
        if (!is_array($cons)) $cons = array();

        ?>
        <div class="parfume-pros-cons">
            <div class="pros-cons-grid">
                <div class="pros-section">
                    <h3><?php _e('Предимства', 'parfume-catalog'); ?></h3>
                    <div class="pros-list">
                        <?php foreach ($pros as $pro): ?>
                            <div class="pros-cons-item">
                                <input type="text" name="parfume_pros[]" value="<?php echo esc_attr($pro); ?>" placeholder="<?php _e('Въведете предимство', 'parfume-catalog'); ?>" />
                                <button type="button" class="button remove-item">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button add-pro"><?php _e('Добави предимство', 'parfume-catalog'); ?></button>
                </div>

                <div class="cons-section">
                    <h3><?php _e('Недостатъци', 'parfume-catalog'); ?></h3>
                    <div class="cons-list">
                        <?php foreach ($cons as $con): ?>
                            <div class="pros-cons-item">
                                <input type="text" name="parfume_cons[]" value="<?php echo esc_attr($con); ?>" placeholder="<?php _e('Въведете недостатък', 'parfume-catalog'); ?>" />
                                <button type="button" class="button remove-item">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button add-con"><?php _e('Добави недостатък', 'parfume-catalog'); ?></button>
                </div>
            </div>
        </div>

        <script type="text/template" id="pros-cons-template">
            <div class="pros-cons-item">
                <input type="text" name="{{name}}" value="" placeholder="{{placeholder}}" />
                <button type="button" class="button remove-item">×</button>
            </div>
        </script>
        <?php
    }

    /**
     * Настройки за single страница meta box
     */
    public function single_settings_meta_box($post) {
        $use_fixed_panel = get_post_meta($post->ID, '_parfume_use_fixed_panel', true);
        $hide_comparison = get_post_meta($post->ID, '_parfume_hide_comparison', true);
        $custom_related_count = get_post_meta($post->ID, '_parfume_custom_related_count', true);
        $custom_recent_count = get_post_meta($post->ID, '_parfume_custom_recent_count', true);
        $custom_brand_count = get_post_meta($post->ID, '_parfume_custom_brand_count', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_use_fixed_panel"><?php _e('Фиксиран панел', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="parfume_use_fixed_panel" name="parfume_use_fixed_panel" value="1" <?php checked(1, $use_fixed_panel); ?> />
                    <label for="parfume_use_fixed_panel"><?php _e('Използвай фиксиран панел за магазини на мобилни устройства', 'parfume-catalog'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_hide_comparison"><?php _e('Скрий сравнение', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="parfume_hide_comparison" name="parfume_hide_comparison" value="1" <?php checked(1, $hide_comparison); ?> />
                    <label for="parfume_hide_comparison"><?php _e('Скрий бутона за сравнение за този парфюм', 'parfume-catalog'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_custom_related_count"><?php _e('Брой подобни аромати', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="number" id="parfume_custom_related_count" name="parfume_custom_related_count" value="<?php echo esc_attr($custom_related_count); ?>" min="0" max="20" class="small-text" />
                    <p class="description"><?php _e('Брой подобни аромати за показване (празно = използвай глобални настройки)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_custom_recent_count"><?php _e('Брой наскоро разгледани', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="number" id="parfume_custom_recent_count" name="parfume_custom_recent_count" value="<?php echo esc_attr($custom_recent_count); ?>" min="0" max="20" class="small-text" />
                    <p class="description"><?php _e('Брой наскоро разгледани парфюми за показване (празно = използвай глобални настройки)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_custom_brand_count"><?php _e('Брой от същата марка', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="number" id="parfume_custom_brand_count" name="parfume_custom_brand_count" value="<?php echo esc_attr($custom_brand_count); ?>" min="0" max="20" class="small-text" />
                    <p class="description"><?php _e('Брой парфюми от същата марка за показване (празно = използвай глобални настройки)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * JSON Import за нотки meta box
     */
    public function notes_import_meta_box($post) {
        ?>
        <div class="parfume-notes-import">
            <p><?php _e('Импортиране на нотки и техните групи от JSON файл:', 'parfume-catalog'); ?></p>
            
            <div class="import-section">
                <label for="notes_json_file"><?php _e('JSON файл:', 'parfume-catalog'); ?></label>
                <input type="file" id="notes_json_file" name="notes_json_file" accept=".json" />
                <p class="description"><?php _e('Изберете JSON файл с нотки за импортиране', 'parfume-catalog'); ?></p>
            </div>

            <div class="import-section">
                <label for="notes_json_text"><?php _e('JSON текст:', 'parfume-catalog'); ?></label>
                <textarea id="notes_json_text" name="notes_json_text" rows="8" class="large-text" placeholder='[{"note": "Роза", "group": "цветни"}, {"note": "Сандал", "group": "дървесни"}]'></textarea>
                <p class="description"><?php _e('Или поставете JSON текст директно', 'parfume-catalog'); ?></p>
            </div>

            <div class="import-actions">
                <button type="button" id="import-notes-btn" class="button button-primary"><?php _e('Импортирай нотки', 'parfume-catalog'); ?></button>
                <span class="spinner"></span>
            </div>

            <div id="import-results" class="import-results"></div>
        </div>
        <?php
    }

    /**
     * Запазване на мета полета
     */
    public function save_meta_fields($post_id) {
        // Проверка на nonce
        if (!isset($_POST['parfume_meta_nonce_field']) || !wp_verify_nonce($_POST['parfume_meta_nonce_field'], 'parfume_meta_nonce')) {
            return;
        }

        // Проверка на автоматично запазване
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Проверка на permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Проверка на post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }

        // Запазване на основни характеристики
        $this->save_basic_info_fields($post_id);
        
        // Запазване на нотки
        $this->save_notes_fields($post_id);
        
        // Запазване на статистики
        $this->save_stats_fields($post_id);
        
        // Запазване на предимства/недостатъци
        $this->save_pros_cons_fields($post_id);
        
        // Запазване на настройки
        $this->save_settings_fields($post_id);
    }

    /**
     * Запазване на основни характеристики
     */
    private function save_basic_info_fields($post_id) {
        $fields = array(
            'parfume_suitable_day',
            'parfume_suitable_night',
            'parfume_launch_year',
            'parfume_concentration',
            'parfume_perfumer',
            'parfume_description_short',
            'parfume_main_notes'
        );

        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? $_POST[$field] : '';
            
            if ($field === 'parfume_suitable_day' || $field === 'parfume_suitable_night') {
                $value = ($value === '1') ? 1 : 0;
            } elseif ($field === 'parfume_launch_year') {
                $value = intval($value);
            } else {
                $value = sanitize_text_field($value);
            }
            
            update_post_meta($post_id, '_' . $field, $value);
        }
    }

    /**
     * Запазване на нотки
     */
    private function save_notes_fields($post_id) {
        $note_types = array('top', 'heart', 'base');
        
        foreach ($note_types as $type) {
            $field_name = 'parfume_' . $type . '_notes';
            $notes = isset($_POST[$field_name]) ? $_POST[$field_name] : array();
            
            // Почистване на празни стойности
            $notes = array_filter($notes, function($note) {
                return !empty(trim($note));
            });
            
            // Sanitize
            $notes = array_map('sanitize_text_field', $notes);
            
            update_post_meta($post_id, '_' . $field_name, $notes);
        }
    }

    /**
     * Запазване на статистики
     */
    private function save_stats_fields($post_id) {
        $stats_fields = array(
            'parfume_durability',
            'parfume_sillage',
            'parfume_gender',
            'parfume_price_range'
        );

        foreach ($stats_fields as $field) {
            $value = isset($_POST[$field]) ? intval($_POST[$field]) : 3;
            update_post_meta($post_id, '_' . $field, $value);
        }
    }

    /**
     * Запазване на предимства/недостатъци
     */
    private function save_pros_cons_fields($post_id) {
        $pros = isset($_POST['parfume_pros']) ? $_POST['parfume_pros'] : array();
        $cons = isset($_POST['parfume_cons']) ? $_POST['parfume_cons'] : array();
        
        // Почистване на празни стойности
        $pros = array_filter($pros, function($item) {
            return !empty(trim($item));
        });
        
        $cons = array_filter($cons, function($item) {
            return !empty(trim($item));
        });
        
        // Sanitize
        $pros = array_map('sanitize_text_field', $pros);
        $cons = array_map('sanitize_text_field', $cons);
        
        update_post_meta($post_id, '_parfume_pros', $pros);
        update_post_meta($post_id, '_parfume_cons', $cons);
    }

    /**
     * Запазване на настройки
     */
    private function save_settings_fields($post_id) {
        $settings_fields = array(
            'parfume_use_fixed_panel',
            'parfume_hide_comparison',
            'parfume_custom_related_count',
            'parfume_custom_recent_count',
            'parfume_custom_brand_count'
        );

        foreach ($settings_fields as $field) {
            $value = isset($_POST[$field]) ? $_POST[$field] : '';
            
            if ($field === 'parfume_use_fixed_panel' || $field === 'parfume_hide_comparison') {
                $value = ($value === '1') ? 1 : 0;
            } elseif (strpos($field, 'count') !== false) {
                $value = intval($value);
            } else {
                $value = sanitize_text_field($value);
            }
            
            update_post_meta($post_id, '_' . $field, $value);
        }
    }

    /**
     * Handle JSON import за нотки
     */
    public function handle_notes_import() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_nonce')) {
            wp_send_json_error(__('Нарушение на сигурността', 'parfume-catalog'));
        }

        // Проверка на permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате право да извършите това действие', 'parfume-catalog'));
        }

        $json_data = isset($_POST['json_data']) ? $_POST['json_data'] : '';
        
        if (empty($json_data)) {
            wp_send_json_error(__('Няма данни за импортиране', 'parfume-catalog'));
        }

        $notes_data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Невалиден JSON формат', 'parfume-catalog'));
        }

        $imported = 0;
        $skipped = 0;
        $errors = array();

        foreach ($notes_data as $note_data) {
            if (!isset($note_data['note']) || !isset($note_data['group'])) {
                $errors[] = __('Липсват полета note или group', 'parfume-catalog');
                continue;
            }

            $note_name = sanitize_text_field($note_data['note']);
            $note_group = sanitize_text_field($note_data['group']);
            $note_slug = sanitize_title($note_name);

            // Проверка дали нотката вече съществува
            if (term_exists($note_name, 'parfume_notes')) {
                $skipped++;
                continue;
            }

            // Създаване на нотката
            $result = wp_insert_term($note_name, 'parfume_notes', array(
                'slug' => $note_slug
            ));

            if (is_wp_error($result)) {
                $errors[] = sprintf(__('Грешка при създаване на нотка "%s": %s', 'parfume-catalog'), $note_name, $result->get_error_message());
                continue;
            }

            // Добавяне на група като term meta
            add_term_meta($result['term_id'], 'note_group', $note_group, true);
            $imported++;
        }

        $response = array(
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => sprintf(__('Импортирани %d нотки, пропуснати %d', 'parfume-catalog'), $imported, $skipped)
        );

        wp_send_json_success($response);
    }
}