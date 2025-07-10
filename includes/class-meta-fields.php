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

        // JSON Import за нотки
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
                    <input type="number" id="parfume_launch_year" name="parfume_launch_year" value="<?php echo esc_attr($launch_year); ?>" min="1900" max="<?php echo date('Y'); ?>" />
                    <p class="description"><?php _e('Годината, в която е пуснат парфюмът', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_concentration"><?php _e('Концентрация', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="text" id="parfume_concentration" name="parfume_concentration" value="<?php echo esc_attr($concentration); ?>" class="regular-text" />
                    <p class="description"><?php _e('Например: 15-20% (Eau de Parfum)', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_main_notes"><?php _e('Основни ароматни нотки', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <textarea id="parfume_main_notes" name="parfume_main_notes" rows="3" class="large-text"><?php echo esc_textarea($main_notes); ?></textarea>
                    <p class="description"><?php _e('Кратко описание на основните нотки за показване под заглавието', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Нотки и състав meta box
     */
    public function notes_composition_meta_box($post) {
        // Получаване на запазени стойности
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
        $middle_notes = get_post_meta($post->ID, '_parfume_middle_notes', true);
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_top_notes"><?php _e('Връхни нотки', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <?php
                    $top_notes_terms = wp_get_object_terms($post->ID, 'parfume_notes', array('meta_key' => 'note_position', 'meta_value' => 'top'));
                    $this->render_notes_selector('parfume_top_notes', $top_notes_terms, 'top');
                    ?>
                    <p class="description"><?php _e('Изберете връхните нотки от списъка или добавете нови', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_middle_notes"><?php _e('Средни нотки (сърце)', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <?php
                    $middle_notes_terms = wp_get_object_terms($post->ID, 'parfume_notes', array('meta_key' => 'note_position', 'meta_value' => 'middle'));
                    $this->render_notes_selector('parfume_middle_notes', $middle_notes_terms, 'middle');
                    ?>
                    <p class="description"><?php _e('Изберете средните нотки от списъка или добавете нови', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_base_notes"><?php _e('Базови нотки', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <?php
                    $base_notes_terms = wp_get_object_terms($post->ID, 'parfume_notes', array('meta_key' => 'note_position', 'meta_value' => 'base'));
                    $this->render_notes_selector('parfume_base_notes', $base_notes_terms, 'base');
                    ?>
                    <p class="description"><?php _e('Изберете базовите нотки от списъка или добавете нови', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>

        <div class="parfume-notes-legend">
            <h4><?php _e('Групи нотки:', 'parfume-catalog'); ?></h4>
            <div class="notes-groups">
                <span class="note-group" data-group="дървесни"><?php _e('Дървесни', 'parfume-catalog'); ?></span>
                <span class="note-group" data-group="ароматни"><?php _e('Ароматни', 'parfume-catalog'); ?></span>
                <span class="note-group" data-group="зелени"><?php _e('Зелени', 'parfume-catalog'); ?></span>
                <span class="note-group" data-group="ориенталски"><?php _e('Ориенталски', 'parfume-catalog'); ?></span>
                <span class="note-group" data-group="цветни"><?php _e('Цветни', 'parfume-catalog'); ?></span>
                <span class="note-group" data-group="гурме"><?php _e('Гурме', 'parfume-catalog'); ?></span>
                <span class="note-group" data-group="плодови"><?php _e('Плодови', 'parfume-catalog'); ?></span>
                <span class="note-group" data-group="морски"><?php _e('Морски', 'parfume-catalog'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Рендериране на селектор за нотки
     */
    private function render_notes_selector($field_name, $selected_terms, $position) {
        $all_notes = get_terms(array(
            'taxonomy' => 'parfume_notes',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $selected_ids = array();
        if (!empty($selected_terms)) {
            foreach ($selected_terms as $term) {
                $selected_ids[] = $term->term_id;
            }
        }

        ?>
        <div class="parfume-notes-selector" data-position="<?php echo esc_attr($position); ?>">
            <select name="<?php echo esc_attr($field_name); ?>[]" multiple="multiple" class="parfume-notes-select" data-placeholder="<?php _e('Изберете нотки...', 'parfume-catalog'); ?>">
                <?php foreach ($all_notes as $note): 
                    $note_group = get_term_meta($note->term_id, 'note_group', true);
                ?>
                    <option value="<?php echo esc_attr($note->term_id); ?>" 
                            data-group="<?php echo esc_attr($note_group); ?>"
                            <?php selected(in_array($note->term_id, $selected_ids)); ?>>
                        <?php echo esc_html($note->name); ?>
                        <?php if ($note_group): ?>
                            (<?php echo esc_html($note_group); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button add-custom-note" data-position="<?php echo esc_attr($position); ?>">
                <?php _e('Добави нова нотка', 'parfume-catalog'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Графика на аромата meta box
     */
    public function aroma_stats_meta_box($post) {
        // Получаване на запазени стойности
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $price_rating = get_post_meta($post->ID, '_parfume_price_rating', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_longevity"><?php _e('Дълготрайност', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_longevity" name="parfume_longevity">
                        <option value=""><?php _e('Изберете дълготрайност', 'parfume-catalog'); ?></option>
                        <option value="1" <?php selected($longevity, '1'); ?>><?php _e('Много слаб', 'parfume-catalog'); ?></option>
                        <option value="2" <?php selected($longevity, '2'); ?>><?php _e('Слаб', 'parfume-catalog'); ?></option>
                        <option value="3" <?php selected($longevity, '3'); ?>><?php _e('Умерен', 'parfume-catalog'); ?></option>
                        <option value="4" <?php selected($longevity, '4'); ?>><?php _e('Траен', 'parfume-catalog'); ?></option>
                        <option value="5" <?php selected($longevity, '5'); ?>><?php _e('Изключително траен', 'parfume-catalog'); ?></option>
                    </select>
                    <p class="description"><?php _e('Колко дълго се усеща парфюмът', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_sillage"><?php _e('Ароматна следа', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_sillage" name="parfume_sillage">
                        <option value=""><?php _e('Изберете ароматна следа', 'parfume-catalog'); ?></option>
                        <option value="1" <?php selected($sillage, '1'); ?>><?php _e('Слаба', 'parfume-catalog'); ?></option>
                        <option value="2" <?php selected($sillage, '2'); ?>><?php _e('Умерена', 'parfume-catalog'); ?></option>
                        <option value="3" <?php selected($sillage, '3'); ?>><?php _e('Силна', 'parfume-catalog'); ?></option>
                        <option value="4" <?php selected($sillage, '4'); ?>><?php _e('Огромна', 'parfume-catalog'); ?></option>
                    </select>
                    <p class="description"><?php _e('Колко далече се усеща парфюмът от носителя', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_price_rating"><?php _e('Ценова категория', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_price_rating" name="parfume_price_rating">
                        <option value=""><?php _e('Изберете ценова категория', 'parfume-catalog'); ?></option>
                        <option value="1" <?php selected($price_rating, '1'); ?>><?php _e('Прекалено скъп', 'parfume-catalog'); ?></option>
                        <option value="2" <?php selected($price_rating, '2'); ?>><?php _e('Скъп', 'parfume-catalog'); ?></option>
                        <option value="3" <?php selected($price_rating, '3'); ?>><?php _e('Приемлива цена', 'parfume-catalog'); ?></option>
                        <option value="4" <?php selected($price_rating, '4'); ?>><?php _e('Добра цена', 'parfume-catalog'); ?></option>
                        <option value="5" <?php selected($price_rating, '5'); ?>><?php _e('Евтин', 'parfume-catalog'); ?></option>
                    </select>
                    <p class="description"><?php _e('Оценка на цената спрямо качеството', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>

        <div class="parfume-stats-preview">
            <h4><?php _e('Преглед на статистиките:', 'parfume-catalog'); ?></h4>
            <div class="stats-preview-container">
                <div class="stat-preview">
                    <label><?php _e('Дълготрайност:', 'parfume-catalog'); ?></label>
                    <div class="progress-bar" data-stat="longevity">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <span class="bar <?php echo ($i <= $longevity) ? 'active' : ''; ?>"></span>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="stat-preview">
                    <label><?php _e('Ароматна следа:', 'parfume-catalog'); ?></label>
                    <div class="progress-bar" data-stat="sillage">
                        <?php for($i = 1; $i <= 4; $i++): ?>
                            <span class="bar <?php echo ($i <= $sillage) ? 'active' : ''; ?>"></span>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="stat-preview">
                    <label><?php _e('Цена:', 'parfume-catalog'); ?></label>
                    <div class="progress-bar" data-stat="price">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <span class="bar <?php echo ($i <= $price_rating) ? 'active' : ''; ?>"></span>
                        <?php endfor; ?>
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
        // Получаване на запазени стойности
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);

        if (!is_array($pros)) $pros = array();
        if (!is_array($cons)) $cons = array();

        ?>
        <div class="parfume-pros-cons-container">
            <div class="pros-section">
                <h4><?php _e('Предимства', 'parfume-catalog'); ?></h4>
                <div class="pros-list" data-type="pros">
                    <?php foreach ($pros as $index => $pro): ?>
                        <div class="pros-cons-item">
                            <input type="text" name="parfume_pros[]" value="<?php echo esc_attr($pro); ?>" placeholder="<?php _e('Въведете предимство', 'parfume-catalog'); ?>" />
                            <button type="button" class="button remove-item">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-pro"><?php _e('Добави предимство', 'parfume-catalog'); ?></button>
            </div>

            <div class="cons-section">
                <h4><?php _e('Недостатъци', 'parfume-catalog'); ?></h4>
                <div class="cons-list" data-type="cons">
                    <?php foreach ($cons as $index => $con): ?>
                        <div class="pros-cons-item">
                            <input type="text" name="parfume_cons[]" value="<?php echo esc_attr($con); ?>" placeholder="<?php _e('Въведете недостатък', 'parfume-catalog'); ?>" />
                            <button type="button" class="button remove-item">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-con"><?php _e('Добави недостатък', 'parfume-catalog'); ?></button>
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
        // Получаване на запазени стойности
        $use_fixed_panel = get_post_meta($post->ID, '_parfume_use_fixed_panel', true);
        $hide_comparison = get_post_meta($post->ID, '_parfume_hide_comparison', true);
        $custom_related_count = get_post_meta($post->ID, '_parfume_custom_related_count', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="parfume_use_fixed_panel"><?php _e('Фиксиран панел', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <select id="parfume_use_fixed_panel" name="parfume_use_fixed_panel">
                        <option value=""><?php _e('Използвай глобална настройка', 'parfume-catalog'); ?></option>
                        <option value="1" <?php selected($use_fixed_panel, '1'); ?>><?php _e('Включи фиксиран панел', 'parfume-catalog'); ?></option>
                        <option value="0" <?php selected($use_fixed_panel, '0'); ?>><?php _e('Изключи фиксиран панел', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_hide_comparison"><?php _e('Сравняване', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="parfume_hide_comparison" name="parfume_hide_comparison" value="1" <?php checked(1, $hide_comparison); ?> />
                    <label for="parfume_hide_comparison"><?php _e('Скрий бутона за сравняване за този парфюм', 'parfume-catalog'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parfume_custom_related_count"><?php _e('Брой подобни парфюми', 'parfume-catalog'); ?></label>
                </th>
                <td>
                    <input type="number" id="parfume_custom_related_count" name="parfume_custom_related_count" value="<?php echo esc_attr($custom_related_count); ?>" min="0" max="12" />
                    <p class="description"><?php _e('Оставете празно за глобална настройка. 0 = без подобни парфюми', 'parfume-catalog'); ?></p>
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
            <p><?php _e('Импортирайте нотки и техните групи от JSON файл:', 'parfume-catalog'); ?></p>
            
            <div class="import-form">
                <textarea id="notes_json_input" rows="10" placeholder='<?php _e('Поставете JSON тук...', 'parfume-catalog'); ?>'>
[
    {"note": "Iso E Super", "group": "дървесни"},
    {"note": "Абаносово дърво", "group": "дървесни"},
    {"note": "Абсент", "group": "ароматни"},
    {"note": "Авокадо", "group": "зелени"}
]</textarea>
                
                <p class="import-actions">
                    <button type="button" class="button button-primary" id="import_notes_btn">
                        <?php _e('Импортирай нотки', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button" id="validate_json_btn">
                        <?php _e('Валидирай JSON', 'parfume-catalog'); ?>
                    </button>
                </p>
                
                <div id="import_results" style="display: none;"></div>
            </div>

            <div class="import-help">
                <h4><?php _e('Формат на JSON:', 'parfume-catalog'); ?></h4>
                <pre>[
    {"note": "Име на нотка", "group": "група"},
    {"note": "Друга нотка", "group": "друга група"}
]</pre>
                <p><?php _e('Валидни групи: дървесни, ароматни, зелени, ориенталски, цветни, гурме, плодови, морски', 'parfume-catalog'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Запазване на мета полетата
     */
    public function save_meta_fields($post_id) {
        // Проверка на nonce
        if (!isset($_POST['parfume_meta_nonce_field']) || !wp_verify_nonce($_POST['parfume_meta_nonce_field'], 'parfume_meta_nonce')) {
            return;
        }

        // Проверка на автосъхранение
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Проверка на права
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Проверка на post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }

        // Запазване на основни характеристики
        $this->save_checkbox_field($post_id, 'parfume_suitable_day', '_parfume_suitable_day');
        $this->save_checkbox_field($post_id, 'parfume_suitable_night', '_parfume_suitable_night');
        $this->save_text_field($post_id, 'parfume_launch_year', '_parfume_launch_year');
        $this->save_text_field($post_id, 'parfume_concentration', '_parfume_concentration');
        $this->save_textarea_field($post_id, 'parfume_main_notes', '_parfume_main_notes');

        // Запазване на нотки
        $this->save_notes_fields($post_id);

        // Запазване на статистики
        $this->save_select_field($post_id, 'parfume_longevity', '_parfume_longevity');
        $this->save_select_field($post_id, 'parfume_sillage', '_parfume_sillage');
        $this->save_select_field($post_id, 'parfume_price_rating', '_parfume_price_rating');

        // Запазване на предимства и недостатъци
        $this->save_array_field($post_id, 'parfume_pros', '_parfume_pros');
        $this->save_array_field($post_id, 'parfume_cons', '_parfume_cons');

        // Запазване на настройки за single страница
        $this->save_select_field($post_id, 'parfume_use_fixed_panel', '_parfume_use_fixed_panel');
        $this->save_checkbox_field($post_id, 'parfume_hide_comparison', '_parfume_hide_comparison');
        $this->save_text_field($post_id, 'parfume_custom_related_count', '_parfume_custom_related_count');
    }

    /**
     * Helper функции за запазване на полета
     */
    private function save_checkbox_field($post_id, $field_name, $meta_key) {
        $value = isset($_POST[$field_name]) ? 1 : 0;
        update_post_meta($post_id, $meta_key, $value);
    }

    private function save_text_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }

    private function save_textarea_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_textarea_field($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }

    private function save_select_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field($_POST[$field_name]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }

    private function save_array_field($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
            $values = array_map('sanitize_text_field', $_POST[$field_name]);
            $values = array_filter($values); // Премахваме празни стойности
            update_post_meta($post_id, $meta_key, $values);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }

    private function save_notes_fields($post_id) {
        // Запазване на нотки чрез таксономии
        $positions = array('top', 'middle', 'base');
        
        foreach ($positions as $position) {
            $field_name = 'parfume_' . $position . '_notes';
            if (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
                $note_ids = array_map('intval', $_POST[$field_name]);
                
                // Задаване на позиция за всяка нотка
                foreach ($note_ids as $note_id) {
                    update_term_meta($note_id, 'note_position', $position);
                }
                
                // Прикачване към поста
                wp_set_object_terms($post_id, $note_ids, 'parfume_notes', false);
            }
        }
    }

    /**
     * Enqueue на admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        if ($post_type === 'parfumes' || $post_type === 'parfume_notes') {
            wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
            wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'));
            
            wp_add_inline_script('select2', "
                jQuery(document).ready(function($) {
                    $('.parfume-notes-select').select2({
                        placeholder: 'Изберете нотки...',
                        allowClear: true
                    });
                    
                    // Динамично обновяване на статистики preview
                    $('select[name*=\"parfume_\"]').on('change', function() {
                        var statType = $(this).attr('name').replace('parfume_', '').replace('_rating', '');
                        var value = parseInt($(this).val());
                        var maxValue = statType === 'sillage' ? 4 : 5;
                        
                        $('.progress-bar[data-stat*=\"' + statType + '\"] .bar').each(function(index) {
                            if (index + 1 <= value) {
                                $(this).addClass('active');
                            } else {
                                $(this).removeClass('active');
                            }
                        });
                    });
                    
                    // Добавяне на предимства/недостатъци
                    $('.add-pro, .add-con').on('click', function() {
                        var type = $(this).hasClass('add-pro') ? 'pros' : 'cons';
                        var container = type === 'pros' ? $('.pros-list') : $('.cons-list');
                        var template = $('#pros-cons-template').html();
                        var name = 'parfume_' + type + '[]';
                        var placeholder = type === 'pros' ? 'Въведете предимство' : 'Въведете недостатък';
                        
                        var html = template.replace('{{name}}', name).replace('{{placeholder}}', placeholder);
                        container.append(html);
                    });
                    
                    // Премахване на предимства/недостатъци
                    $(document).on('click', '.remove-item', function() {
                        $(this).closest('.pros-cons-item').remove();
                    });
                    
                    // JSON Import functionality
                    $('#validate_json_btn').on('click', function() {
                        var jsonText = $('#notes_json_input').val();
                        try {
                            var data = JSON.parse(jsonText);
                            $('#import_results').html('<div class=\"notice notice-success\"><p>JSON е валиден! Открити ' + data.length + ' нотки.</p></div>').show();
                        } catch (e) {
                            $('#import_results').html('<div class=\"notice notice-error\"><p>Грешка в JSON: ' + e.message + '</p></div>').show();
                        }
                    });
                    
                    $('#import_notes_btn').on('click', function() {
                        var jsonText = $('#notes_json_input').val();
                        var button = $(this);
                        
                        button.prop('disabled', true).text('Импортиране...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'parfume_import_notes',
                                notes_data: jsonText,
                                nonce: '" . wp_create_nonce('parfume_import_notes') . "'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#import_results').html('<div class=\"notice notice-success\"><p>' + response.data.message + '</p></div>').show();
                                    location.reload(); // Презареди страницата за да покаже новите нотки
                                } else {
                                    $('#import_results').html('<div class=\"notice notice-error\"><p>' + response.data.message + '</p></div>').show();
                                }
                            },
                            error: function() {
                                $('#import_results').html('<div class=\"notice notice-error\"><p>Грешка при импорта.</p></div>').show();
                            },
                            complete: function() {
                                button.prop('disabled', false).text('Импортирай нотки');
                            }
                        });
                    });
                });
            ");
        }
    }

    /**
     * AJAX handler за импорт на нотки
     */
    public function handle_notes_import() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_import_notes')) {
            wp_send_json_error(array('message' => 'Невалидна сигурност.'));
        }

        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нямате права за тази операция.'));
        }

        $notes_data = sanitize_textarea_field($_POST['notes_data']);
        
        try {
            $notes = json_decode($notes_data, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Невалиден JSON формат.');
            }

            $imported = 0;
            $skipped = 0;
            $valid_groups = array('дървесни', 'ароматни', 'зелени', 'ориенталски', 'цветни', 'гурме', 'плодови', 'морски');

            foreach ($notes as $note_data) {
                if (!isset($note_data['note']) || !isset($note_data['group'])) {
                    $skipped++;
                    continue;
                }

                $note_name = sanitize_text_field($note_data['note']);
                $note_group = sanitize_text_field($note_data['group']);

                if (!in_array($note_group, $valid_groups)) {
                    $skipped++;
                    continue;
                }

                // Проверка дали нотката вече съществува
                if (term_exists($note_name, 'parfume_notes')) {
                    $skipped++;
                    continue;
                }

                // Създаване на нотката
                $result = wp_insert_term($note_name, 'parfume_notes');
                
                if (!is_wp_error($result)) {
                    update_term_meta($result['term_id'], 'note_group', $note_group);
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            wp_send_json_success(array(
                'message' => sprintf(
                    'Импортирани %d нотки. Пропуснати %d (вече съществуват или невалидни данни).',
                    $imported,
                    $skipped
                )
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}