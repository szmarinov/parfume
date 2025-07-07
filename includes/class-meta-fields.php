<?php
/**
 * Meta Fields class for Parfume Catalog plugin
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Meta_Fields {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-catalog'),
            array($this, 'render_parfume_details'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_characteristics',
            __('Характеристики', 'parfume-catalog'),
            array($this, 'render_parfume_characteristics'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_stores',
            __('Магазини и цени', 'parfume-catalog'),
            array($this, 'render_parfume_stores'),
            'parfumes',
            'normal',
            'high'
        );
    }
    
    /**
     * Render parfume details meta box
     */
    public function render_parfume_details($post) {
        wp_nonce_field('parfume_details_nonce', 'parfume_details_nonce');
        
        $perfumer = get_post_meta($post->ID, '_perfumer', true);
        $release_year = get_post_meta($post->ID, '_release_year', true);
        $base_price = get_post_meta($post->ID, '_pc_base_price', true);
        $advantages = get_post_meta($post->ID, '_advantages', true);
        $disadvantages = get_post_meta($post->ID, '_disadvantages', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="perfumer"><?php _e('Парфюмер', 'parfume-catalog'); ?></label></th>
                <td><input type="text" id="perfumer" name="perfumer" value="<?php echo esc_attr($perfumer); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="release_year"><?php _e('Година на издаване', 'parfume-catalog'); ?></label></th>
                <td><input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" min="1900" max="<?php echo date('Y'); ?>" /></td>
            </tr>
            <tr>
                <th><label for="base_price"><?php _e('Базова цена (лв.)', 'parfume-catalog'); ?></label></th>
                <td><input type="number" id="base_price" name="base_price" value="<?php echo esc_attr($base_price); ?>" step="0.01" min="0" /></td>
            </tr>
            <tr>
                <th><label for="advantages"><?php _e('Предимства', 'parfume-catalog'); ?></label></th>
                <td>
                    <textarea id="advantages" name="advantages" rows="4" class="large-text"><?php echo esc_textarea($advantages); ?></textarea>
                    <p class="description"><?php _e('Въведете всяко предимство на нов ред', 'parfume-catalog'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="disadvantages"><?php _e('Недостатъци', 'parfume-catalog'); ?></label></th>
                <td>
                    <textarea id="disadvantages" name="disadvantages" rows="4" class="large-text"><?php echo esc_textarea($disadvantages); ?></textarea>
                    <p class="description"><?php _e('Въведете всеки недостатък на нов ред', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render parfume characteristics meta box
     */
    public function render_parfume_characteristics($post) {
        wp_nonce_field('parfume_characteristics_nonce', 'parfume_characteristics_nonce');
        
        $longevity = get_post_meta($post->ID, '_longevity', true);
        $sillage = get_post_meta($post->ID, '_sillage', true);
        $gender = get_post_meta($post->ID, '_gender', true);
        $price_rating = get_post_meta($post->ID, '_price_rating', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="longevity"><?php _e('Дълготрайност', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="longevity" name="longevity">
                        <option value=""><?php _e('Избери...', 'parfume-catalog'); ?></option>
                        <option value="velmi_slab" <?php selected($longevity, 'velmi_slab'); ?>><?php _e('Много слаб', 'parfume-catalog'); ?></option>
                        <option value="slab" <?php selected($longevity, 'slab'); ?>><?php _e('Слаб', 'parfume-catalog'); ?></option>
                        <option value="umeren" <?php selected($longevity, 'umeren'); ?>><?php _e('Умерен', 'parfume-catalog'); ?></option>
                        <option value="traen" <?php selected($longevity, 'traen'); ?>><?php _e('Траен', 'parfume-catalog'); ?></option>
                        <option value="izklyuchitelno_traen" <?php selected($longevity, 'izklyuchitelno_traen'); ?>><?php _e('Изключително траен', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sillage"><?php _e('Ароматна следа', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="sillage" name="sillage">
                        <option value=""><?php _e('Избери...', 'parfume-catalog'); ?></option>
                        <option value="slaba" <?php selected($sillage, 'slaba'); ?>><?php _e('Слаба', 'parfume-catalog'); ?></option>
                        <option value="umerena" <?php selected($sillage, 'umerena'); ?>><?php _e('Умерена', 'parfume-catalog'); ?></option>
                        <option value="silna" <?php selected($sillage, 'silna'); ?>><?php _e('Силна', 'parfume-catalog'); ?></option>
                        <option value="ogromna" <?php selected($sillage, 'ogromna'); ?>><?php _e('Огромна', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gender"><?php _e('Пол', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="gender" name="gender">
                        <option value=""><?php _e('Избери...', 'parfume-catalog'); ?></option>
                        <option value="damski" <?php selected($gender, 'damski'); ?>><?php _e('Дамски', 'parfume-catalog'); ?></option>
                        <option value="mazhki" <?php selected($gender, 'mazhki'); ?>><?php _e('Мъжки', 'parfume-catalog'); ?></option>
                        <option value="uniseks" <?php selected($gender, 'uniseks'); ?>><?php _e('Унисекс', 'parfume-catalog'); ?></option>
                        <option value="po_mladi" <?php selected($gender, 'po_mladi'); ?>><?php _e('По-млади', 'parfume-catalog'); ?></option>
                        <option value="po_zreli" <?php selected($gender, 'po_zreli'); ?>><?php _e('По-зрели', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="price_rating"><?php _e('Ценова категория', 'parfume-catalog'); ?></label></th>
                <td>
                    <select id="price_rating" name="price_rating">
                        <option value=""><?php _e('Избери...', 'parfume-catalog'); ?></option>
                        <option value="prekalno_skap" <?php selected($price_rating, 'prekalno_skap'); ?>><?php _e('Прекалено скъп', 'parfume-catalog'); ?></option>
                        <option value="skap" <?php selected($price_rating, 'skap'); ?>><?php _e('Скъп', 'parfume-catalog'); ?></option>
                        <option value="priemliwa_cena" <?php selected($price_rating, 'priemliwa_cena'); ?>><?php _e('Приемлива цена', 'parfume-catalog'); ?></option>
                        <option value="dobra_cena" <?php selected($price_rating, 'dobra_cena'); ?>><?php _e('Добра цена', 'parfume-catalog'); ?></option>
                        <option value="evtin" <?php selected($price_rating, 'evtin'); ?>><?php _e('Евтин', 'parfume-catalog'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render parfume stores meta box
     */
    public function render_parfume_stores($post) {
        wp_nonce_field('parfume_stores_nonce', 'parfume_stores_nonce');
        
        $stores = get_post_meta($post->ID, '_pc_stores', true);
        if (!is_array($stores)) {
            $stores = array();
        }
        
        $all_stores = get_option('parfume_catalog_stores', array());
        ?>
        <div id="parfume-stores-container">
            <div id="stores-list">
                <?php foreach ($stores as $index => $store_data): ?>
                    <?php $this->render_store_item($index, $store_data, $all_stores); ?>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="add-store" class="button">
                <?php _e('Добави магазин', 'parfume-catalog'); ?>
            </button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var storeIndex = <?php echo count($stores); ?>;
            
            $('#add-store').on('click', function() {
                var template = '<div class="store-item" data-index="' + storeIndex + '">' +
                    '<h4><?php _e('Магазин', 'parfume-catalog'); ?> #' + (storeIndex + 1) + '</h4>' +
                    '<table class="form-table">' +
                    '<tr><th><label><?php _e('Магазин', 'parfume-catalog'); ?></label></th>' +
                    '<td><select name="stores[' + storeIndex + '][store_id]">' +
                    '<option value=""><?php _e('Избери магазин...', 'parfume-catalog'); ?></option>' +
                    <?php foreach ($all_stores as $store): ?>
                    '<option value="<?php echo $store['id']; ?>"><?php echo esc_js($store['name']); ?></option>' +
                    <?php endforeach; ?>
                    '</select></td></tr>' +
                    '<tr><th><label><?php _e('Product URL', 'parfume-catalog'); ?></label></th>' +
                    '<td><input type="url" name="stores[' + storeIndex + '][product_url]" class="large-text" /></td></tr>' +
                    '<tr><th><label><?php _e('Affiliate URL', 'parfume-catalog'); ?></label></th>' +
                    '<td><input type="url" name="stores[' + storeIndex + '][affiliate_url]" class="large-text" /></td></tr>' +
                    '<tr><th><label><?php _e('Promo Code', 'parfume-catalog'); ?></label></th>' +
                    '<td><input type="text" name="stores[' + storeIndex + '][promo_code]" /></td></tr>' +
                    '<tr><th><label><?php _e('Promo Code Info', 'parfume-catalog'); ?></label></th>' +
                    '<td><input type="text" name="stores[' + storeIndex + '][promo_code_info]" class="regular-text" /></td></tr>' +
                    '</table>' +
                    '<button type="button" class="button remove-store"><?php _e('Премахни', 'parfume-catalog'); ?></button>' +
                    '<hr>' +
                    '</div>';
                
                $('#stores-list').append(template);
                storeIndex++;
            });
            
            $(document).on('click', '.remove-store', function() {
                $(this).closest('.store-item').remove();
            });
        });
        </script>
        
        <style>
        .store-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .store-item h4 {
            margin-top: 0;
            color: #333;
        }
        
        .remove-store {
            color: #a00;
            border-color: #a00;
        }
        
        .remove-store:hover {
            background: #a00;
            color: white;
        }
        </style>
        <?php
    }
    
    /**
     * Render single store item
     */
    private function render_store_item($index, $store_data, $all_stores) {
        ?>
        <div class="store-item" data-index="<?php echo $index; ?>">
            <h4><?php _e('Магазин', 'parfume-catalog'); ?> #<?php echo ($index + 1); ?></h4>
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Магазин', 'parfume-catalog'); ?></label></th>
                    <td>
                        <select name="stores[<?php echo $index; ?>][store_id]">
                            <option value=""><?php _e('Избери магазин...', 'parfume-catalog'); ?></option>
                            <?php foreach ($all_stores as $store): ?>
                                <option value="<?php echo $store['id']; ?>" <?php selected($store_data['store_id'] ?? '', $store['id']); ?>>
                                    <?php echo esc_html($store['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Product URL', 'parfume-catalog'); ?></label></th>
                    <td><input type="url" name="stores[<?php echo $index; ?>][product_url]" value="<?php echo esc_attr($store_data['product_url'] ?? ''); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Affiliate URL', 'parfume-catalog'); ?></label></th>
                    <td><input type="url" name="stores[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($store_data['affiliate_url'] ?? ''); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Promo Code', 'parfume-catalog'); ?></label></th>
                    <td><input type="text" name="stores[<?php echo $index; ?>][promo_code]" value="<?php echo esc_attr($store_data['promo_code'] ?? ''); ?>" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Promo Code Info', 'parfume-catalog'); ?></label></th>
                    <td><input type="text" name="stores[<?php echo $index; ?>][promo_code_info]" value="<?php echo esc_attr($store_data['promo_code_info'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <button type="button" class="button remove-store"><?php _e('Премахни', 'parfume-catalog'); ?></button>
            <hr>
        </div>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check nonce for details
        if (isset($_POST['parfume_details_nonce']) && wp_verify_nonce($_POST['parfume_details_nonce'], 'parfume_details_nonce')) {
            $this->save_parfume_details($post_id);
        }
        
        // Check nonce for characteristics
        if (isset($_POST['parfume_characteristics_nonce']) && wp_verify_nonce($_POST['parfume_characteristics_nonce'], 'parfume_characteristics_nonce')) {
            $this->save_parfume_characteristics($post_id);
        }
        
        // Check nonce for stores
        if (isset($_POST['parfume_stores_nonce']) && wp_verify_nonce($_POST['parfume_stores_nonce'], 'parfume_stores_nonce')) {
            $this->save_parfume_stores($post_id);
        }
    }
    
    /**
     * Save parfume details
     */
    private function save_parfume_details($post_id) {
        $fields = array('perfumer', 'release_year', 'base_price', 'advantages', 'disadvantages');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                if ($field === 'advantages' || $field === 'disadvantages') {
                    $value = sanitize_textarea_field($_POST[$field]);
                }
                update_post_meta($post_id, '_' . $field, $value);
                
                // Special handling for base price
                if ($field === 'base_price') {
                    update_post_meta($post_id, '_pc_base_price', floatval($value));
                }
            }
        }
    }
    
    /**
     * Save parfume characteristics
     */
    private function save_parfume_characteristics($post_id) {
        $fields = array('longevity', 'sillage', 'gender', 'price_rating');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * Save parfume stores
     */
    private function save_parfume_stores($post_id) {
        if (isset($_POST['stores']) && is_array($_POST['stores'])) {
            $stores = array();
            
            foreach ($_POST['stores'] as $store_data) {
                if (!empty($store_data['store_id'])) {
                    $stores[] = array(
                        'store_id' => intval($store_data['store_id']),
                        'product_url' => sanitize_url($store_data['product_url']),
                        'affiliate_url' => sanitize_url($store_data['affiliate_url']),
                        'promo_code' => sanitize_text_field($store_data['promo_code']),
                        'promo_code_info' => sanitize_text_field($store_data['promo_code_info'])
                    );
                }
            }
            
            update_post_meta($post_id, '_pc_stores', $stores);
        }
    }
}