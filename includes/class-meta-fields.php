<?php
/**
 * Meta Fields class for Parfume Catalog plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Meta_Fields {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Parfume details meta box
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-catalog'),
            array($this, 'parfume_details_callback'),
            'parfumes',
            'normal',
            'high'
        );
        
        // Parfume composition meta box
        add_meta_box(
            'parfume_composition',
            __('Състав на парфюма', 'parfume-catalog'),
            array($this, 'parfume_composition_callback'),
            'parfumes',
            'normal',
            'high'
        );
        
        // Parfume characteristics meta box
        add_meta_box(
            'parfume_characteristics',
            __('Характеристики', 'parfume-catalog'),
            array($this, 'parfume_characteristics_callback'),
            'parfumes',
            'normal',
            'high'
        );
        
        // Parfume pros and cons meta box
        add_meta_box(
            'parfume_pros_cons',
            __('Предимства и недостатъци', 'parfume-catalog'),
            array($this, 'parfume_pros_cons_callback'),
            'parfumes',
            'normal',
            'high'
        );
        
        // Stores meta box
        add_meta_box(
            'parfume_stores',
            __('Магазини', 'parfume-catalog'),
            array($this, 'parfume_stores_callback'),
            'parfumes',
            'side',
            'default'
        );
    }
    
    /**
     * Parfume details meta box callback
     */
    public function parfume_details_callback($post) {
        wp_nonce_field('parfume_details_nonce', 'parfume_details_nonce');
        
        $year = get_post_meta($post->ID, '_parfume_year', true);
        $perfumer = get_post_meta($post->ID, '_parfume_perfumer', true);
        $concentration = get_post_meta($post->ID, '_parfume_concentration', true);
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $occasion_day = get_post_meta($post->ID, '_parfume_occasion_day', true);
        $occasion_night = get_post_meta($post->ID, '_parfume_occasion_night', true);
        $suitable_seasons = get_post_meta($post->ID, '_parfume_suitable_seasons', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="parfume_year"><?php _e('Година на издаване', 'parfume-catalog'); ?></label></th>
                <td><input type="number" id="parfume_year" name="parfume_year" value="<?php echo esc_attr($year); ?>" min="1900" max="<?php echo date('Y'); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="parfume_perfumer"><?php _e('Парфюмерист', 'parfume-catalog'); ?></label></th>
                <td><input type="text" id="parfume_perfumer" name="parfume_perfumer" value="<?php echo esc_attr($perfumer); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="parfume_concentration"><?php _e('Концентрация', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_concentration" name="parfume_concentration" class="regular-text">
                        <option value=""><?php _e('Избери концентрация', 'parfume-catalog'); ?></option>
                        <option value="parfum" <?php selected($concentration, 'parfum'); ?>><?php _e('Parfum (15-40%)', 'parfume-catalog'); ?></option>
                        <option value="edp" <?php selected($concentration, 'edp'); ?>><?php _e('Eau de Parfum (10-20%)', 'parfume-catalog'); ?></option>
                        <option value="edt" <?php selected($concentration, 'edt'); ?>><?php _e('Eau de Toilette (5-15%)', 'parfume-catalog'); ?></option>
                        <option value="edc" <?php selected($concentration, 'edc'); ?>><?php _e('Eau de Cologne (2-5%)', 'parfume-catalog'); ?></option>
                        <option value="edm" <?php selected($concentration, 'edm'); ?>><?php _e('Eau de Mist (1-3%)', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="parfume_gender"><?php _e('Пол', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_gender" name="parfume_gender" class="regular-text">
                        <option value=""><?php _e('Избери пол', 'parfume-catalog'); ?></option>
                        <option value="women" <?php selected($gender, 'women'); ?>><?php _e('Дамски', 'parfume-catalog'); ?></option>
                        <option value="men" <?php selected($gender, 'men'); ?>><?php _e('Мъжки', 'parfume-catalog'); ?></option>
                        <option value="unisex" <?php selected($gender, 'unisex'); ?>><?php _e('Унисекс', 'parfume-catalog'); ?></option>
                        <option value="young" <?php selected($gender, 'young'); ?>><?php _e('По-млади', 'parfume-catalog'); ?></option>
                        <option value="mature" <?php selected($gender, 'mature'); ?>><?php _e('По-зрели', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Подходящ за', 'parfume-catalog'); ?></label></th>
                <td>
                    <label><input type="checkbox" name="parfume_occasion_day" value="1" <?php checked($occasion_day, 1); ?> /> <?php _e('Ден', 'parfume-catalog'); ?></label><br>
                    <label><input type="checkbox" name="parfume_occasion_night" value="1" <?php checked($occasion_night, 1); ?> /> <?php _e('Нощ', 'parfume-catalog'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Подходящи сезони', 'parfume-catalog'); ?></label></th>
                <td>
                    <?php
                    $seasons = array('spring' => 'Пролет', 'summer' => 'Лято', 'autumn' => 'Есен', 'winter' => 'Зима');
                    $suitable_seasons = is_array($suitable_seasons) ? $suitable_seasons : array();
                    
                    foreach ($seasons as $key => $label) {
                        $checked = in_array($key, $suitable_seasons) ? 'checked' : '';
                        echo '<label><input type="checkbox" name="parfume_suitable_seasons[]" value="' . $key . '" ' . $checked . ' /> ' . __($label, 'parfume-catalog') . '</label><br>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Parfume composition meta box callback
     */
    public function parfume_composition_callback($post) {
        wp_nonce_field('parfume_composition_nonce', 'parfume_composition_nonce');
        
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
        $middle_notes = get_post_meta($post->ID, '_parfume_middle_notes', true);
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);
        
        // Get all available notes
        $notes_terms = get_terms(array(
            'taxonomy' => 'notki',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="parfume_top_notes"><?php _e('Връхни нотки', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_top_notes" name="parfume_top_notes[]" multiple class="parfume-notes-select" data-placeholder="<?php _e('Избери връхни нотки', 'parfume-catalog'); ?>">
                        <?php
                        $selected_top = is_array($top_notes) ? $top_notes : array();
                        foreach ($notes_terms as $term) {
                            $selected = in_array($term->term_id, $selected_top) ? 'selected' : '';
                            echo '<option value="' . $term->term_id . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="parfume_middle_notes"><?php _e('Средни нотки', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_middle_notes" name="parfume_middle_notes[]" multiple class="parfume-notes-select" data-placeholder="<?php _e('Избери средни нотки', 'parfume-catalog'); ?>">
                        <?php
                        $selected_middle = is_array($middle_notes) ? $middle_notes : array();
                        foreach ($notes_terms as $term) {
                            $selected = in_array($term->term_id, $selected_middle) ? 'selected' : '';
                            echo '<option value="' . $term->term_id . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="parfume_base_notes"><?php _e('Базови нотки', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_base_notes" name="parfume_base_notes[]" multiple class="parfume-notes-select" data-placeholder="<?php _e('Избери базови нотки', 'parfume-catalog'); ?>">
                        <?php
                        $selected_base = is_array($base_notes) ? $base_notes : array();
                        foreach ($notes_terms as $term) {
                            $selected = in_array($term->term_id, $selected_base) ? 'selected' : '';
                            echo '<option value="' . $term->term_id . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Parfume characteristics meta box callback
     */
    public function parfume_characteristics_callback($post) {
        wp_nonce_field('parfume_characteristics_nonce', 'parfume_characteristics_nonce');
        
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $price_range = get_post_meta($post->ID, '_parfume_price_range', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="parfume_longevity"><?php _e('Дълготрайност', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_longevity" name="parfume_longevity" class="regular-text">
                        <option value=""><?php _e('Избери дълготрайност', 'parfume-catalog'); ?></option>
                        <option value="very_weak" <?php selected($longevity, 'very_weak'); ?>><?php _e('Много слаб', 'parfume-catalog'); ?></option>
                        <option value="weak" <?php selected($longevity, 'weak'); ?>><?php _e('Слаб', 'parfume-catalog'); ?></option>
                        <option value="moderate" <?php selected($longevity, 'moderate'); ?>><?php _e('Умерен', 'parfume-catalog'); ?></option>
                        <option value="long_lasting" <?php selected($longevity, 'long_lasting'); ?>><?php _e('Траен', 'parfume-catalog'); ?></option>
                        <option value="eternal" <?php selected($longevity, 'eternal'); ?>><?php _e('Изключително траен', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="parfume_sillage"><?php _e('Ароматна следа', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_sillage" name="parfume_sillage" class="regular-text">
                        <option value=""><?php _e('Избери ароматна следа', 'parfume-catalog'); ?></option>
                        <option value="weak" <?php selected($sillage, 'weak'); ?>><?php _e('Слаба', 'parfume-catalog'); ?></option>
                        <option value="moderate" <?php selected($sillage, 'moderate'); ?>><?php _e('Умерена', 'parfume-catalog'); ?></option>
                        <option value="strong" <?php selected($sillage, 'strong'); ?>><?php _e('Силна', 'parfume-catalog'); ?></option>
                        <option value="enormous" <?php selected($sillage, 'enormous'); ?>><?php _e('Огромна', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="parfume_price_range"><?php _e('Ценова категория', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="parfume_price_range" name="parfume_price_range" class="regular-text">
                        <option value=""><?php _e('Избери ценова категория', 'parfume-catalog'); ?></option>
                        <option value="very_expensive" <?php selected($price_range, 'very_expensive'); ?>><?php _e('Прекалено скъп', 'parfume-catalog'); ?></option>
                        <option value="expensive" <?php selected($price_range, 'expensive'); ?>><?php _e('Скъп', 'parfume-catalog'); ?></option>
                        <option value="acceptable" <?php selected($price_range, 'acceptable'); ?>><?php _e('Приемлива цена', 'parfume-catalog'); ?></option>
                        <option value="good_price" <?php selected($price_range, 'good_price'); ?>><?php _e('Добра цена', 'parfume-catalog'); ?></option>
                        <option value="cheap" <?php selected($price_range, 'cheap'); ?>><?php _e('Евтин', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Parfume pros and cons meta box callback
     */
    public function parfume_pros_cons_callback($post) {
        wp_nonce_field('parfume_pros_cons_nonce', 'parfume_pros_cons_nonce');
        
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);
        
        $pros = is_array($pros) ? $pros : array();
        $cons = is_array($cons) ? $cons : array();
        
        ?>
        <div class="parfume-pros-cons-container">
            <div class="pros-section">
                <h4><?php _e('Предимства', 'parfume-catalog'); ?></h4>
                <div id="parfume-pros-list">
                    <?php
                    if (!empty($pros)) {
                        foreach ($pros as $index => $pro) {
                            echo '<div class="pros-item">';
                            echo '<input type="text" name="parfume_pros[]" value="' . esc_attr($pro) . '" placeholder="' . __('Въведи предимство', 'parfume-catalog') . '" />';
                            echo '<button type="button" class="remove-pros-item button">' . __('Премахни', 'parfume-catalog') . '</button>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                <button type="button" id="add-pros-item" class="button"><?php _e('Добави предимство', 'parfume-catalog'); ?></button>
            </div>
            
            <div class="cons-section">
                <h4><?php _e('Недостатъци', 'parfume-catalog'); ?></h4>
                <div id="parfume-cons-list">
                    <?php
                    if (!empty($cons)) {
                        foreach ($cons as $index => $con) {
                            echo '<div class="cons-item">';
                            echo '<input type="text" name="parfume_cons[]" value="' . esc_attr($con) . '" placeholder="' . __('Въведи недостатък', 'parfume-catalog') . '" />';
                            echo '<button type="button" class="remove-cons-item button">' . __('Премахни', 'parfume-catalog') . '</button>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                <button type="button" id="add-cons-item" class="button"><?php _e('Добави недостатък', 'parfume-catalog'); ?></button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Stores meta box callback
     */
    public function parfume_stores_callback($post) {
        wp_nonce_field('parfume_stores_nonce', 'parfume_stores_nonce');
        
        $stores_data = get_post_meta($post->ID, '_parfume_stores', true);
        $stores_data = is_array($stores_data) ? $stores_data : array();
        
        // Get available stores
        $available_stores = get_option('parfume_catalog_stores', array());
        
        ?>
        <div id="parfume-stores-container">
            <div id="parfume-stores-list">
                <?php
                foreach ($stores_data as $index => $store_data) {
                    $this->render_store_item($index, $store_data, $available_stores);
                }
                ?>
            </div>
            
            <div class="add-store-section">
                <select id="available-stores-select">
                    <option value=""><?php _e('Избери магазин', 'parfume-catalog'); ?></option>
                    <?php foreach ($available_stores as $store_id => $store) : ?>
                        <option value="<?php echo esc_attr($store_id); ?>"><?php echo esc_html($store['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add-store-btn" class="button"><?php _e('Добави магазин', 'parfume-catalog'); ?></button>
            </div>
        </div>
        
        <script type="text/template" id="store-item-template">
            <?php $this->render_store_item('{{INDEX}}', array(), $available_stores, true); ?>
        </script>
        <?php
    }
    
    /**
     * Render single store item
     */
    private function render_store_item($index, $store_data = array(), $available_stores = array(), $is_template = false) {
        $store_id = isset($store_data['store_id']) ? $store_data['store_id'] : '';
        $product_url = isset($store_data['product_url']) ? $store_data['product_url'] : '';
        $affiliate_url = isset($store_data['affiliate_url']) ? $store_data['affiliate_url'] : '';
        $promo_code = isset($store_data['promo_code']) ? $store_data['promo_code'] : '';
        $promo_info = isset($store_data['promo_info']) ? $store_data['promo_info'] : '';
        
        $store_name = '';
        if ($store_id && isset($available_stores[$store_id])) {
            $store_name = $available_stores[$store_id]['name'];
        }
        
        ?>
        <div class="store-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="store-header">
                <h4><?php echo $is_template ? '{{STORE_NAME}}' : esc_html($store_name); ?></h4>
                <div class="store-controls">
                    <span class="move-handle dashicons dashicons-move"></span>
                    <button type="button" class="remove-store-btn button-link-delete"><?php _e('Премахни', 'parfume-catalog'); ?></button>
                </div>
            </div>
            
            <input type="hidden" name="parfume_stores[<?php echo esc_attr($index); ?>][store_id]" value="<?php echo esc_attr($store_id); ?>" />
            
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Product URL', 'parfume-catalog'); ?></label></th>
                    <td>
                        <input type="url" name="parfume_stores[<?php echo esc_attr($index); ?>][product_url]" value="<?php echo esc_attr($product_url); ?>" class="regular-text" />
                        <button type="button" class="manual-scrape-btn button" data-store-index="<?php echo esc_attr($index); ?>"><?php _e('Скрейпни сега', 'parfume-catalog'); ?></button>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Affiliate URL', 'parfume-catalog'); ?></label></th>
                    <td><input type="url" name="parfume_stores[<?php echo esc_attr($index); ?>][affiliate_url]" value="<?php echo esc_attr($affiliate_url); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Promo Code', 'parfume-catalog'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo esc_attr($index); ?>][promo_code]" value="<?php echo esc_attr($promo_code); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Promo Code Info', 'parfume-catalog'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo esc_attr($index); ?>][promo_info]" value="<?php echo esc_attr($promo_info); ?>" class="regular-text" /></td>
                </tr>
            </table>
            
            <div class="scraped-data-section">
                <h5><?php _e('Скрейпнати данни', 'parfume-catalog'); ?></h5>
                <div class="scraped-data-content" id="scraped-data-<?php echo esc_attr($index); ?>">
                    <!-- Scraped data will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (isset($_POST['post_type']) && 'parfumes' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
        
        // Save parfume details
        if (isset($_POST['parfume_details_nonce']) && wp_verify_nonce($_POST['parfume_details_nonce'], 'parfume_details_nonce')) {
            $fields = array(
                'parfume_year' => 'sanitize_text_field',
                'parfume_perfumer' => 'sanitize_text_field',
                'parfume_concentration' => 'sanitize_text_field',
                'parfume_gender' => 'sanitize_text_field',
                'parfume_occasion_day' => 'absint',
                'parfume_occasion_night' => 'absint'
            );
            
            foreach ($fields as $field => $sanitize_callback) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, call_user_func($sanitize_callback, $_POST[$field]));
                } else {
                    delete_post_meta($post_id, '_' . $field);
                }
            }
            
            // Handle suitable seasons (array)
            if (isset($_POST['parfume_suitable_seasons']) && is_array($_POST['parfume_suitable_seasons'])) {
                $seasons = array_map('sanitize_text_field', $_POST['parfume_suitable_seasons']);
                update_post_meta($post_id, '_parfume_suitable_seasons', $seasons);
            } else {
                delete_post_meta($post_id, '_parfume_suitable_seasons');
            }
        }
        
        // Save parfume composition
        if (isset($_POST['parfume_composition_nonce']) && wp_verify_nonce($_POST['parfume_composition_nonce'], 'parfume_composition_nonce')) {
            $notes_fields = array('parfume_top_notes', 'parfume_middle_notes', 'parfume_base_notes');
            
            foreach ($notes_fields as $field) {
                if (isset($_POST[$field]) && is_array($_POST[$field])) {
                    $notes = array_map('absint', $_POST[$field]);
                    update_post_meta($post_id, '_' . $field, $notes);
                } else {
                    delete_post_meta($post_id, '_' . $field);
                }
            }
        }
        
        // Save parfume characteristics
        if (isset($_POST['parfume_characteristics_nonce']) && wp_verify_nonce($_POST['parfume_characteristics_nonce'], 'parfume_characteristics_nonce')) {
            $characteristics = array('parfume_longevity', 'parfume_sillage', 'parfume_price_range');
            
            foreach ($characteristics as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                } else {
                    delete_post_meta($post_id, '_' . $field);
                }
            }
        }
        
        // Save pros and cons
        if (isset($_POST['parfume_pros_cons_nonce']) && wp_verify_nonce($_POST['parfume_pros_cons_nonce'], 'parfume_pros_cons_nonce')) {
            // Save pros
            if (isset($_POST['parfume_pros']) && is_array($_POST['parfume_pros'])) {
                $pros = array_filter(array_map('sanitize_text_field', $_POST['parfume_pros']));
                update_post_meta($post_id, '_parfume_pros', $pros);
            } else {
                delete_post_meta($post_id, '_parfume_pros');
            }
            
            // Save cons
            if (isset($_POST['parfume_cons']) && is_array($_POST['parfume_cons'])) {
                $cons = array_filter(array_map('sanitize_text_field', $_POST['parfume_cons']));
                update_post_meta($post_id, '_parfume_cons', $cons);
            } else {
                delete_post_meta($post_id, '_parfume_cons');
            }
        }
        
        // Save stores
        if (isset($_POST['parfume_stores_nonce']) && wp_verify_nonce($_POST['parfume_stores_nonce'], 'parfume_stores_nonce')) {
            if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
                $stores_data = array();
                
                foreach ($_POST['parfume_stores'] as $index => $store_data) {
                    $clean_data = array(
                        'store_id' => sanitize_text_field($store_data['store_id']),
                        'product_url' => esc_url_raw($store_data['product_url']),
                        'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                        'promo_code' => sanitize_text_field($store_data['promo_code']),
                        'promo_info' => sanitize_text_field($store_data['promo_info'])
                    );
                    
                    if (!empty($clean_data['store_id'])) {
                        $stores_data[] = $clean_data;
                    }
                }
                
                update_post_meta($post_id, '_parfume_stores', $stores_data);
            } else {
                delete_post_meta($post_id, '_parfume_stores');
            }
        }
    }
    
    /**
     * Enqueue admin scripts for meta boxes
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        global $post_type;
        if ($post_type !== 'parfumes') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'));
    }
}