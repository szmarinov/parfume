<?php
namespace Parfume_Reviews\Post_Type;

/**
 * Stores_Meta_Box class - Управлява meta box за магазини в parfume постове
 * 
 * Файл: includes/post-type/class-stores-meta-box.php
 * Създаден за "Колона 2" функционалността
 */
class Stores_Meta_Box {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX actions за управление на магазини в post
        add_action('wp_ajax_parfume_add_post_store', array($this, 'ajax_add_post_store'));
        add_action('wp_ajax_parfume_remove_post_store', array($this, 'ajax_remove_post_store'));
        add_action('wp_ajax_parfume_update_store_order', array($this, 'ajax_update_store_order'));
        add_action('wp_ajax_parfume_scrape_store_data', array($this, 'ajax_scrape_store_data'));
    }
    
    /**
     * Добавя meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_stores_meta_box',
            __('Магазини за "Колона 2"', 'parfume-reviews'),
            array($this, 'render_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        // Mobile настройки meta box
        add_meta_box(
            'parfume_mobile_settings_meta_box',
            __('Mobile настройки за този пост', 'parfume-reviews'),
            array($this, 'render_mobile_settings_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts и styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if ($post_type !== 'parfume') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();
        
        // Fallback inline JavaScript if separate file doesn't exist
        $inline_js = "
        jQuery(document).ready(function($) {
            // Sortable stores
            $('#parfume-stores-list').sortable({
                handle: '.store-handle',
                placeholder: 'store-placeholder',
                update: function() {
                    updateStoreOrder();
                }
            });
            
            // Add store button
            $('#add-store-btn').on('click', function(e) {
                e.preventDefault();
                addNewStore();
            });
            
            // Remove store
            $(document).on('click', '.remove-store', function(e) {
                e.preventDefault();
                if (confirm('Сигурни ли сте?')) {
                    $(this).closest('.store-item').fadeOut(300, function() {
                        $(this).remove();
                        updateStoreOrder();
                    });
                }
            });
            
            // Scrape data button
            $(document).on('click', '.scrape-store-data', function(e) {
                e.preventDefault();
                scrapeStoreData($(this));
            });
            
            function addNewStore() {
                const storeIndex = $('#parfume-stores-list .store-item').length;
                const newStore = `
                    <div class='store-item' data-index='` + storeIndex + `'>
                        <div class='store-handle'>⋮⋮</div>
                        <div class='store-fields'>
                            <label>Магазин:</label>
                            <select name='parfume_stores[` + storeIndex + `][store_id]' required>
                                <option value=''>Изберете магазин</option>
                                <!-- Store options would be populated here -->
                            </select>
                            
                            <label>Product URL:</label>
                            <input type='url' name='parfume_stores[` + storeIndex + `][product_url]' placeholder='https://store.com/product'>
                            
                            <label>Affiliate URL:</label>
                            <input type='url' name='parfume_stores[` + storeIndex + `][affiliate_url]' placeholder='https://affiliate.com/link'>
                            
                            <label>Promo Code:</label>
                            <input type='text' name='parfume_stores[` + storeIndex + `][promo_code]' placeholder='PROMO20'>
                            
                            <button type='button' class='button scrape-store-data'>Обнови данни</button>
                            <button type='button' class='button remove-store'>Премахни</button>
                        </div>
                        <div class='store-scraped-data'>
                            <em>Няма скрейпнати данни</em>
                        </div>
                    </div>
                `;
                $('#parfume-stores-list').append(newStore);
            }
            
            function updateStoreOrder() {
                $('#parfume-stores-list .store-item').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('input, select').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            const newName = name.replace(/\\[\\d+\\]/, '[' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });
            }
            
            function scrapeStoreData($button) {
                const $storeItem = $button.closest('.store-item');
                const productUrl = $storeItem.find('input[name*=\"[product_url]\"]').val();
                
                if (!productUrl) {
                    alert('Моля въведете Product URL');
                    return;
                }
                
                $button.text('Обновяване...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_scrape_store_data',
                        nonce: $('#parfume_stores_nonce').val(),
                        product_url: productUrl
                    },
                    success: function(response) {
                        if (response.success) {
                            $storeItem.find('.store-scraped-data').html(formatScrapedData(response.data));
                        } else {
                            alert('Грешка при скрейпване: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('AJAX грешка');
                    },
                    complete: function() {
                        $button.text('Обнови данни').prop('disabled', false);
                    }
                });
            }
            
            function formatScrapedData(data) {
                let html = '<div class=\"scraped-info\">';
                
                if (data.prices && data.prices.length > 0) {
                    html += '<strong>Цени:</strong><br>';
                    data.prices.forEach(function(price) {
                        html += price.size + ': ' + price.price + '<br>';
                    });
                }
                
                if (data.availability) {
                    html += '<strong>Наличност:</strong> ' + data.availability + '<br>';
                }
                
                if (data.last_updated) {
                    html += '<em>Обновено: ' + data.last_updated + '</em>';
                }
                
                html += '</div>';
                return html;
            }
        });
        ";
        
        wp_add_inline_script('jquery', $inline_js);
        
        wp_localize_script('jquery', 'parfumeStoresMetaBox', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_stores_meta_box_nonce'),
            'strings' => array(
                'confirm_remove' => __('Сигурни ли сте, че искате да премахнете този магазин?', 'parfume-reviews'),
                'scraping' => __('Скрейпване...', 'parfume-reviews'),
                'scrape_error' => __('Грешка при скрейпване', 'parfume-reviews'),
                'no_url' => __('Моля въведете Product URL', 'parfume-reviews'),
            )
        ));
    }
    
    /**
     * Render главния stores meta box
     */
    public function render_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_box', 'parfume_stores_nonce');
        
        $stores_data = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores_data)) {
            $stores_data = array();
        }
        
        // Get available stores from settings
        $available_stores = get_option('parfume_reviews_stores_settings', array());
        $stores_list = isset($available_stores['stores']) ? $available_stores['stores'] : array();
        
        echo '<div class="parfume-stores-meta-box">';
        echo '<p><strong>Конфигурирайте магазини за "Колона 2" на този парфюм</strong></p>';
        
        echo '<div id="parfume-stores-list" class="stores-list">';
        
        if (!empty($stores_data)) {
            foreach ($stores_data as $index => $store) {
                $this->render_store_item($index, $store, $stores_list);
            }
        }
        
        echo '</div>';
        
        echo '<p><button type="button" id="add-store-btn" class="button button-secondary">+ Добави магазин</button></p>';
        
        echo '<div class="stores-help">';
        echo '<h4>Инструкции:</h4>';
        echo '<ul>';
        echo '<li>Изберете магазин от конфигурираните в настройките</li>';
        echo '<li>Въведете Product URL от магазина</li>';
        echo '<li>Affiliate URL е опционален</li>';
        echo '<li>Promo Code ще се показва като копируем код</li>';
        echo '<li>Използвайте "Обнови данни" за скрейпване на цени</li>';
        echo '<li>Плъгнете и пуснете за промяна на реда</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
        
        $this->add_meta_box_styles();
    }
    
    /**
     * Render individual store item
     */
    private function render_store_item($index, $store_data, $stores_list) {
        echo '<div class="store-item" data-index="' . esc_attr($index) . '">';
        echo '<div class="store-handle" title="Drag to reorder">⋮⋮</div>';
        echo '<div class="store-fields">';
        
        // Store selection
        echo '<label>Магазин:</label>';
        echo '<select name="parfume_stores[' . $index . '][store_id]" required>';
        echo '<option value="">Изберете магазин</option>';
        
        foreach ($stores_list as $store_id => $store_info) {
            $selected = selected(isset($store_data['store_id']) ? $store_data['store_id'] : '', $store_id, false);
            $store_name = isset($store_info['name']) ? $store_info['name'] : 'Store ' . $store_id;
            echo '<option value="' . esc_attr($store_id) . '"' . $selected . '>' . esc_html($store_name) . '</option>';
        }
        
        echo '</select>';
        
        // Product URL
        echo '<label>Product URL:</label>';
        echo '<input type="url" name="parfume_stores[' . $index . '][product_url]" ';
        echo 'value="' . esc_attr(isset($store_data['product_url']) ? $store_data['product_url'] : '') . '" ';
        echo 'placeholder="https://store.com/product">';
        
        // Affiliate URL
        echo '<label>Affiliate URL:</label>';
        echo '<input type="url" name="parfume_stores[' . $index . '][affiliate_url]" ';
        echo 'value="' . esc_attr(isset($store_data['affiliate_url']) ? $store_data['affiliate_url'] : '') . '" ';
        echo 'placeholder="https://affiliate.com/link">';
        
        // Promo Code
        echo '<label>Promo Code:</label>';
        echo '<input type="text" name="parfume_stores[' . $index . '][promo_code]" ';
        echo 'value="' . esc_attr(isset($store_data['promo_code']) ? $store_data['promo_code'] : '') . '" ';
        echo 'placeholder="PROMO20">';
        
        // Action buttons
        echo '<button type="button" class="button scrape-store-data">Обнови данни</button>';
        echo '<button type="button" class="button remove-store">Премахни</button>';
        
        echo '</div>';
        
        // Display scraped data if available
        echo '<div class="store-scraped-data">';
        if (isset($store_data['scraped_data']) && !empty($store_data['scraped_data'])) {
            $this->display_scraped_data($store_data['scraped_data']);
        } else {
            echo '<em>Няма скрейпнати данни</em>';
        }
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Display scraped data
     */
    private function display_scraped_data($scraped_data) {
        echo '<div class="scraped-info">';
        
        if (isset($scraped_data['prices']) && !empty($scraped_data['prices'])) {
            echo '<strong>Цени:</strong><br>';
            foreach ($scraped_data['prices'] as $price) {
                $size = isset($price['size']) ? $price['size'] : 'Unknown';
                $current_price = isset($price['current_price']) ? $price['current_price'] : 'N/A';
                $old_price = isset($price['old_price']) ? $price['old_price'] : '';
                
                echo esc_html($size) . ': ' . esc_html($current_price);
                if ($old_price) {
                    echo ' (беше: ' . esc_html($old_price) . ')';
                }
                echo '<br>';
            }
        }
        
        if (isset($scraped_data['availability'])) {
            echo '<strong>Наличност:</strong> ' . esc_html($scraped_data['availability']) . '<br>';
        }
        
        if (isset($scraped_data['last_updated'])) {
            echo '<em>Обновено: ' . esc_html($scraped_data['last_updated']) . '</em>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render mobile settings meta box
     */
    public function render_mobile_settings_meta_box($post) {
        $mobile_settings = get_post_meta($post->ID, '_parfume_mobile_settings', true);
        if (!is_array($mobile_settings)) {
            $mobile_settings = array();
        }
        
        echo '<table class="form-table">';
        
        // Custom mobile panel title
        echo '<tr>';
        echo '<th><label for="mobile_panel_title">Panel заглавие:</label></th>';
        echo '<td><input type="text" id="mobile_panel_title" name="parfume_mobile_settings[panel_title]" ';
        echo 'value="' . esc_attr(isset($mobile_settings['panel_title']) ? $mobile_settings['panel_title'] : '') . '" ';
        echo 'placeholder="Магазини"></td>';
        echo '</tr>';
        
        // Hide mobile panel for this post
        echo '<tr>';
        echo '<th><label for="hide_mobile_panel">Скрий mobile panel:</label></th>';
        echo '<td><input type="checkbox" id="hide_mobile_panel" name="parfume_mobile_settings[hide_panel]" ';
        echo 'value="1"' . checked(isset($mobile_settings['hide_panel']) ? $mobile_settings['hide_panel'] : 0, 1, false) . '>';
        echo '<span class="description">Не показвай mobile panel за този пост</span></td>';
        echo '</tr>';
        
        // Custom z-index
        echo '<tr>';
        echo '<th><label for="mobile_custom_z_index">Custom Z-Index:</label></th>';
        echo '<td><input type="number" id="mobile_custom_z_index" name="parfume_mobile_settings[custom_z_index]" ';
        echo 'value="' . esc_attr(isset($mobile_settings['custom_z_index']) ? $mobile_settings['custom_z_index'] : '') . '" ';
        echo 'placeholder="9999">';
        echo '<span class="description">Остави празно за глобална настройка</span></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_box($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['parfume_stores_nonce']) || 
            !wp_verify_nonce($_POST['parfume_stores_nonce'], 'parfume_stores_meta_box')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Skip for autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Only for parfume post type
        if ($post->post_type !== 'parfume') {
            return;
        }
        
        // Save stores data
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores_data = array();
            
            foreach ($_POST['parfume_stores'] as $index => $store) {
                $clean_store = array();
                
                if (!empty($store['store_id'])) {
                    $clean_store['store_id'] = sanitize_text_field($store['store_id']);
                }
                
                if (!empty($store['product_url'])) {
                    $clean_store['product_url'] = esc_url_raw($store['product_url']);
                }
                
                if (!empty($store['affiliate_url'])) {
                    $clean_store['affiliate_url'] = esc_url_raw($store['affiliate_url']);
                }
                
                if (!empty($store['promo_code'])) {
                    $clean_store['promo_code'] = sanitize_text_field($store['promo_code']);
                }
                
                // Preserve existing scraped data
                $existing_stores = get_post_meta($post_id, '_parfume_stores', true);
                if (is_array($existing_stores) && isset($existing_stores[$index]['scraped_data'])) {
                    $clean_store['scraped_data'] = $existing_stores[$index]['scraped_data'];
                }
                
                if (!empty($clean_store)) {
                    $stores_data[] = $clean_store;
                }
            }
            
            update_post_meta($post_id, '_parfume_stores', $stores_data);
        } else {
            delete_post_meta($post_id, '_parfume_stores');
        }
        
        // Save mobile settings
        if (isset($_POST['parfume_mobile_settings']) && is_array($_POST['parfume_mobile_settings'])) {
            $mobile_settings = array();
            
            if (!empty($_POST['parfume_mobile_settings']['panel_title'])) {
                $mobile_settings['panel_title'] = sanitize_text_field($_POST['parfume_mobile_settings']['panel_title']);
            }
            
            if (isset($_POST['parfume_mobile_settings']['hide_panel'])) {
                $mobile_settings['hide_panel'] = 1;
            }
            
            if (!empty($_POST['parfume_mobile_settings']['custom_z_index'])) {
                $mobile_settings['custom_z_index'] = intval($_POST['parfume_mobile_settings']['custom_z_index']);
            }
            
            if (!empty($mobile_settings)) {
                update_post_meta($post_id, '_parfume_mobile_settings', $mobile_settings);
            } else {
                delete_post_meta($post_id, '_parfume_mobile_settings');
            }
        }
    }
    
    /**
     * AJAX handler за scraping store data
     */
    public function ajax_scrape_store_data() {
        check_ajax_referer('parfume_stores_meta_box_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $product_url = isset($_POST['product_url']) ? esc_url_raw($_POST['product_url']) : '';
        
        if (empty($product_url)) {
            wp_send_json_error('Product URL is required');
        }
        
        // Here you would implement the actual scraping logic
        // For now, return mock data
        $scraped_data = array(
            'prices' => array(
                array(
                    'size' => '50ml',
                    'current_price' => '89.90 лв',
                    'old_price' => '99.90 лв'
                ),
                array(
                    'size' => '100ml',
                    'current_price' => '129.90 лв',
                    'old_price' => ''
                )
            ),
            'availability' => 'В наличност',
            'last_updated' => current_time('Y-m-d H:i:s')
        );
        
        wp_send_json_success($scraped_data);
    }
    
    /**
     * Add inline styles for the meta box
     */
    private function add_meta_box_styles() {
        echo '<style>
        .parfume-stores-meta-box .stores-list {
            margin: 15px 0;
        }
        
        .store-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 15px;
            position: relative;
        }
        
        .store-item:hover {
            background: #f0f0f0;
        }
        
        .store-handle {
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            cursor: move;
            color: #666;
            font-weight: bold;
        }
        
        .store-fields {
            margin-left: 25px;
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 10px;
            align-items: center;
        }
        
        .store-fields label {
            font-weight: 600;
            color: #333;
        }
        
        .store-fields input,
        .store-fields select {
            width: 100%;
        }
        
        .store-fields .button {
            margin-top: 10px;
        }
        
        .store-scraped-data {
            margin-left: 25px;
            margin-top: 15px;
            padding: 10px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
        }
        
        .scraped-info {
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        .stores-help {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .stores-help h4 {
            margin-top: 0;
            color: #0073aa;
        }
        
        .stores-help ul {
            margin-bottom: 0;
        }
        
        .store-placeholder {
            background: #ffeaa7;
            border: 2px dashed #fdcb6e;
            height: 100px;
            border-radius: 5px;
        }
        </style>';
    }
}

// Initialize if we are in admin
if (is_admin()) {
    new Stores_Meta_Box();
}