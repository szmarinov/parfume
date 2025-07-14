<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Meta Boxes Handler - управлява всички meta boxes за parfume posts
 */
class Meta_Boxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        
        // AJAX handlers for price updates
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
    }
    
    /**
     * Add meta boxes for parfume posts
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume-details',
            __('Parfume Details', 'parfume-reviews'),
            array($this, 'parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume-notes',
            __('Scent Notes', 'parfume-reviews'),
            array($this, 'parfume_notes_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume-stores',
            __('Store Prices', 'parfume-reviews'),
            array($this, 'parfume_stores_meta_box'),
            'parfume',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume-review',
            __('Review & Rating', 'parfume-reviews'),
            array($this, 'parfume_review_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    public function parfume_details_meta_box($post) {
        wp_nonce_field('parfume_meta_box', 'parfume_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_parfume_price', true);
        $size = get_post_meta($post->ID, '_parfume_size', true);
        $brand = get_post_meta($post->ID, '_parfume_brand', true);
        $year = get_post_meta($post->ID, '_parfume_year', true);
        $concentration = get_post_meta($post->ID, '_parfume_concentration', true);
        $availability = get_post_meta($post->ID, '_parfume_availability', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_price"><?php _e('Price', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="text" id="parfume_price" name="parfume_price" value="<?php echo esc_attr($price); ?>" class="regular-text">
                    <p class="description"><?php _e('е.g. 120.00 лв.', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_size"><?php _e('Size', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="text" id="parfume_size" name="parfume_size" value="<?php echo esc_attr($size); ?>" class="regular-text">
                    <p class="description"><?php _e('е.g. 100ml, 50ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_year"><?php _e('Release Year', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="number" id="parfume_year" name="parfume_year" value="<?php echo esc_attr($year); ?>" min="1900" max="<?php echo date('Y'); ?>" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_concentration"><?php _e('Concentration', 'parfume-reviews'); ?></label></th>
                <td>
                    <select id="parfume_concentration" name="parfume_concentration">
                        <option value=""><?php _e('Select concentration', 'parfume-reviews'); ?></option>
                        <option value="parfum" <?php selected($concentration, 'parfum'); ?>><?php _e('Parfum (20-40%)', 'parfume-reviews'); ?></option>
                        <option value="edp" <?php selected($concentration, 'edp'); ?>><?php _e('Eau de Parfum (15-20%)', 'parfume-reviews'); ?></option>
                        <option value="edt" <?php selected($concentration, 'edt'); ?>><?php _e('Eau de Toilette (5-15%)', 'parfume-reviews'); ?></option>
                        <option value="edc" <?php selected($concentration, 'edc'); ?>><?php _e('Eau de Cologne (2-4%)', 'parfume-reviews'); ?></option>
                        <option value="edv" <?php selected($concentration, 'edv'); ?>><?php _e('Eau de Vie (15-25%)', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_availability"><?php _e('Availability', 'parfume-reviews'); ?></label></th>
                <td>
                    <select id="parfume_availability" name="parfume_availability">
                        <option value="available" <?php selected($availability, 'available'); ?>><?php _e('Available', 'parfume-reviews'); ?></option>
                        <option value="limited" <?php selected($availability, 'limited'); ?>><?php _e('Limited Edition', 'parfume-reviews'); ?></option>
                        <option value="discontinued" <?php selected($availability, 'discontinued'); ?>><?php _e('Discontinued', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function parfume_notes_meta_box($post) {
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
        $middle_notes = get_post_meta($post->ID, '_parfume_middle_notes', true);
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_top_notes"><?php _e('Top Notes', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_top_notes" name="parfume_top_notes" rows="3" cols="50" class="large-text"><?php echo esc_textarea($top_notes); ?></textarea>
                    <p class="description"><?php _e('Separate notes with commas', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_middle_notes"><?php _e('Middle Notes', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_middle_notes" name="parfume_middle_notes" rows="3" cols="50" class="large-text"><?php echo esc_textarea($middle_notes); ?></textarea>
                    <p class="description"><?php _e('Separate notes with commas', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_base_notes"><?php _e('Base Notes', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_base_notes" name="parfume_base_notes" rows="3" cols="50" class="large-text"><?php echo esc_textarea($base_notes); ?></textarea>
                    <p class="description"><?php _e('Separate notes with commas', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function parfume_review_meta_box($post) {
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_rating"><?php _e('Rating', 'parfume-reviews'); ?></label></th>
                <td>
                    <select id="parfume_rating" name="parfume_rating">
                        <option value=""><?php _e('Select rating', 'parfume-reviews'); ?></option>
                        <?php for ($i = 1; $i <= 10; $i += 0.5): ?>
                            <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>><?php echo $i; ?>/10</option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_pros"><?php _e('Pros', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_pros" name="parfume_pros" rows="5" cols="50" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('One pro per line', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_cons"><?php _e('Cons', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_cons" name="parfume_cons" rows="5" cols="50" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('One con per line', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function parfume_stores_meta_box($post) {
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores)) {
            $stores = array();
        }
        
        ?>
        <div id="parfume-stores-container">
            <?php foreach ($stores as $index => $store): ?>
                <?php $this->render_store_row($store, $index); ?>
            <?php endforeach; ?>
        </div>
        
        <p>
            <button type="button" id="add-store" class="button"><?php _e('Add Store', 'parfume-reviews'); ?></button>
        </p>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var storeIndex = <?php echo count($stores); ?>;
            
            $('#add-store').on('click', function() {
                var newRow = `
                <div class="store-row" data-index="${storeIndex}">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label>Store Name</label></th>
                            <td><input type="text" name="parfume_stores[${storeIndex}][name]" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>URL</label></th>
                            <td><input type="url" name="parfume_stores[${storeIndex}][url]" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Price</label></th>
                            <td><input type="text" name="parfume_stores[${storeIndex}][price]" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Size</label></th>
                            <td><input type="text" name="parfume_stores[${storeIndex}][size]" class="regular-text"></td>
                        </tr>
                    </table>
                    <p><button type="button" class="button remove-store">Remove Store</button></p>
                </div>`;
                
                $('#parfume-stores-container').append(newRow);
                storeIndex++;
            });
            
            $(document).on('click', '.remove-store', function() {
                $(this).closest('.store-row').remove();
            });
        });
        </script>
        <?php
    }
    
    private function render_store_row($store, $index) {
        ?>
        <div class="store-row" data-index="<?php echo $index; ?>">
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php _e('Store Name', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][name]" value="<?php echo esc_attr($store['name'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php _e('URL', 'parfume-reviews'); ?></label></th>
                    <td><input type="url" name="parfume_stores[<?php echo $index; ?>][url]" value="<?php echo esc_attr($store['url'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php _e('Price', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][price]" value="<?php echo esc_attr($store['price'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php _e('Size', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][size]" value="<?php echo esc_attr($store['size'] ?? ''); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p><button type="button" class="button remove-store"><?php _e('Remove Store', 'parfume-reviews'); ?></button></p>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['parfume_meta_box_nonce']) || !wp_verify_nonce($_POST['parfume_meta_box_nonce'], 'parfume_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array(
            '_parfume_price' => 'parfume_price',
            '_parfume_size' => 'parfume_size',
            '_parfume_brand' => 'parfume_brand',
            '_parfume_year' => 'parfume_year',
            '_parfume_concentration' => 'parfume_concentration',
            '_parfume_availability' => 'parfume_availability',
            '_parfume_top_notes' => 'parfume_top_notes',
            '_parfume_middle_notes' => 'parfume_middle_notes',
            '_parfume_base_notes' => 'parfume_base_notes',
            '_parfume_rating' => 'parfume_rating',
            '_parfume_pros' => 'parfume_pros',
            '_parfume_cons' => 'parfume_cons',
        );
        
        foreach ($fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            }
        }
        
        // Save stores data
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores = array();
            foreach ($_POST['parfume_stores'] as $store) {
                if (!empty($store['name'])) {
                    $stores[] = array(
                        'name' => sanitize_text_field($store['name']),
                        'url' => esc_url_raw($store['url']),
                        'price' => sanitize_text_field($store['price']),
                        'size' => sanitize_text_field($store['size']),
                    );
                }
            }
            update_post_meta($post_id, '_parfume_stores', $stores);
        }
    }
    
    // AJAX Methods
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        
        // Here you would implement price checking logic
        // For now, return a mock response
        wp_send_json_success(array(
            'price' => '120.00 лв.',
            'last_updated' => current_time('mysql')
        ));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        
        // Mock data for now
        $sizes = array(
            array('size' => '30ml', 'price' => '45.00 лв.'),
            array('size' => '50ml', 'price' => '75.00 лв.'),
            array('size' => '100ml', 'price' => '120.00 лв.'),
        );
        
        wp_send_json_success($sizes);
    }
}