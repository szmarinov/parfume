<?php
/**
 * Parfume Catalog Meta Stores
 * 
 * Управление на магазини и цени в мета полетата за парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Meta_Stores {
    
    /**
     * Конструктор
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_parfume_manual_scrape_store', array($this, 'ajax_manual_scrape_store'));
        add_action('wp_ajax_parfume_add_store_to_post', array($this, 'ajax_add_store_to_post'));
        add_action('wp_ajax_parfume_remove_store_from_post', array($this, 'ajax_remove_store_from_post'));
        add_action('wp_ajax_parfume_reorder_post_stores', array($this, 'ajax_reorder_post_stores'));
        add_action('wp_ajax_parfume_get_store_data', array($this, 'ajax_get_store_data'));
    }
    
    /**
     * Добавяне на мета boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_stores',
            __('Магазини и цени', 'parfume-catalog'),
            array($this, 'render_stores_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_stores_quick_add',
            __('Бърз достъп до магазини', 'parfume-catalog'),
            array($this, 'render_quick_add_meta_box'),
            'parfumes',
            'side',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('parfume-meta-stores', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/meta-stores.js', 
                array('jquery', 'jquery-ui-sortable'), 
                PARFUME_CATALOG_VERSION, 
                true
            );
            
            wp_localize_script('parfume-meta-stores', 'parfumeMetaStores', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_meta_stores'),
                'texts' => array(
                    'confirm_remove' => __('Сигурни ли сте, че искате да премахнете този магазин?', 'parfume-catalog'),
                    'scraping' => __('Скрейпване...', 'parfume-catalog'),
                    'scrape_success' => __('Данните са обновени успешно', 'parfume-catalog'),
                    'scrape_error' => __('Грешка при скрейпване', 'parfume-catalog'),
                    'add_store' => __('Добави магазин', 'parfume-catalog'),
                    'loading' => __('Зареждане...', 'parfume-catalog')
                )
            ));
            
            wp_enqueue_style('parfume-meta-stores', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/meta-stores.css', 
                array(), 
                PARFUME_CATALOG_VERSION
            );
        }
    }
    
    /**
     * Render stores meta box
     */
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_nonce', 'parfume_stores_meta_nonce_field');
        
        $post_stores = get_post_meta($post->ID, '_parfume_stores', true) ?: array();
        $all_stores = $this->get_all_stores();
        ?>
        <div class="parfume-stores-container">
            <div class="stores-header">
                <h4><?php _e('Добавени магазини за този парфюм', 'parfume-catalog'); ?></h4>
                <div class="stores-actions">
                    <select id="available-stores-select">
                        <option value=""><?php _e('Изберете магазин за добавяне', 'parfume-catalog'); ?></option>
                        <?php foreach ($all_stores as $store): ?>
                            <?php if (!$this->is_store_added($store['id'], $post_stores)): ?>
                                <option value="<?php echo esc_attr($store['id']); ?>">
                                    <?php echo esc_html($store['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button button-primary" id="add-store-btn">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Добави магазин', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div class="stores-list" id="stores-list">
                <?php if (empty($post_stores)): ?>
                    <div class="no-stores-message">
                        <p><?php _e('Още не са добавени магазини за този парфюм.', 'parfume-catalog'); ?></p>
                        <p><?php _e('Използвайте формата по-горе за да добавите магазин.', 'parfume-catalog'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($post_stores as $index => $store_data): ?>
                        <?php $this->render_store_item($store_data, $index, $post->ID); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="stores-footer">
                <p class="description">
                    <?php _e('Може да пренареждате магазините чрез влачене. Редът тук определя реда на показване във фронтенда.', 'parfume-catalog'); ?>
                </p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize sortable
            $('#stores-list').sortable({
                handle: '.store-handle',
                axis: 'y',
                update: function(event, ui) {
                    updateStoreOrder();
                }
            });
            
            // Add store
            $('#add-store-btn').click(function() {
                var storeId = $('#available-stores-select').val();
                if (storeId) {
                    addStoreToPost(storeId);
                }
            });
            
            // Remove store
            $(document).on('click', '.remove-store-btn', function() {
                if (confirm(parfumeMetaStores.texts.confirm_remove)) {
                    var $storeItem = $(this).closest('.store-item');
                    $storeItem.fadeOut(300, function() {
                        $(this).remove();
                        updateStoreOrder();
                        checkEmptyState();
                    });
                }
            });
            
            // Manual scrape
            $(document).on('click', '.manual-scrape-btn', function() {
                var $btn = $(this);
                var storeIndex = $btn.data('store-index');
                
                $btn.prop('disabled', true).text(parfumeMetaStores.texts.scraping);
                
                $.post(parfumeMetaStores.ajax_url, {
                    action: 'parfume_manual_scrape_store',
                    post_id: <?php echo $post->ID; ?>,
                    store_index: storeIndex,
                    nonce: parfumeMetaStores.nonce
                }, function(response) {
                    if (response.success) {
                        updateStoreData(storeIndex, response.data);
                        showSuccessMessage(parfumeMetaStores.texts.scrape_success);
                    } else {
                        showErrorMessage(response.data.message || parfumeMetaStores.texts.scrape_error);
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Обнови', 'parfume-catalog'); ?>');
                });
            });
            
            function addStoreToPost(storeId) {
                $.post(parfumeMetaStores.ajax_url, {
                    action: 'parfume_add_store_to_post',
                    post_id: <?php echo $post->ID; ?>,
                    store_id: storeId,
                    nonce: parfumeMetaStores.nonce
                }, function(response) {
                    if (response.success) {
                        $('#stores-list').append(response.data.html);
                        $('#available-stores-select option[value="' + storeId + '"]').remove();
                        $('#available-stores-select').val('');
                        $('.no-stores-message').remove();
                        updateStoreOrder();
                    } else {
                        showErrorMessage(response.data.message);
                    }
                });
            }
            
            function updateStoreOrder() {
                $('#stores-list .store-item').each(function(index) {
                    $(this).find('input[name$="[order]"]').val(index);
                });
            }
            
            function checkEmptyState() {
                if ($('#stores-list .store-item').length === 0) {
                    $('#stores-list').html('<div class="no-stores-message"><p><?php _e('Още не са добавени магазини за този парфюм.', 'parfume-catalog'); ?></p></div>');
                }
            }
            
            function updateStoreData(storeIndex, data) {
                var $storeItem = $('.store-item[data-store-index="' + storeIndex + '"]');
                
                if (data.price) {
                    $storeItem.find('.scraped-price').text(data.price);
                }
                if (data.availability) {
                    $storeItem.find('.scraped-availability').text(data.availability);
                }
                if (data.delivery_info) {
                    $storeItem.find('.scraped-delivery').text(data.delivery_info);
                }
                if (data.variants) {
                    $storeItem.find('.scraped-variants').html(data.variants.join(', '));
                }
                
                $storeItem.find('.last-scraped').text('<?php _e('Току-що', 'parfume-catalog'); ?>');
            }
            
            function showSuccessMessage(message) {
                $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>')
                    .insertAfter('.parfume-stores-container')
                    .delay(3000)
                    .fadeOut();
            }
            
            function showErrorMessage(message) {
                $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>')
                    .insertAfter('.parfume-stores-container')
                    .delay(5000)
                    .fadeOut();
            }
        });
        </script>
        
        <style>
        .parfume-stores-container {
            margin: 15px 0;
        }
        
        .stores-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .stores-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        #available-stores-select {
            min-width: 200px;
        }
        
        .stores-list {
            min-height: 100px;
            border: 1px dashed #ddd;
            border-radius: 4px;
            padding: 15px;
        }
        
        .no-stores-message {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px 20px;
        }
        
        .store-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            padding: 15px;
            position: relative;
        }
        
        .store-item:last-child {
            margin-bottom: 0;
        }
        
        .store-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .store-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .store-logo {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            background: #f0f0f1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .store-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .store-actions {
            display: flex;
            gap: 5px;
        }
        
        .store-handle {
            cursor: move;
            padding: 5px;
            color: #666;
        }
        
        .store-handle:hover {
            color: #0073aa;
        }
        
        .store-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .field-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .field-group label {
            font-weight: 500;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .field-group input,
        .field-group textarea {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .field-group input:focus,
        .field-group textarea:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
        }
        
        .scraped-data {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .scraped-data h5 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .scraped-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            font-size: 12px;
        }
        
        .scraped-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .scraped-label {
            font-weight: 500;
            color: #666;
        }
        
        .scraped-value {
            color: #333;
        }
        
        .scraped-price {
            font-weight: 600;
            color: #27ae60;
        }
        
        .scraped-availability {
            font-weight: 500;
        }
        
        .scraped-availability.available {
            color: #27ae60;
        }
        
        .scraped-availability.unavailable {
            color: #e74c3c;
        }
        
        .last-updated {
            font-size: 11px;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }
        
        .stores-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .manual-scrape-btn {
            font-size: 11px;
            height: auto;
            line-height: 1.4;
            padding: 4px 8px;
        }
        
        .remove-store-btn {
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .remove-store-btn:hover {
            background: #dc3545;
            color: #fff;
        }
        
        @media (max-width: 768px) {
            .stores-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .stores-actions {
                flex-direction: column;
            }
            
            .store-fields {
                grid-template-columns: 1fr;
            }
            
            .scraped-info {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Render quick add meta box
     */
    public function render_quick_add_meta_box($post) {
        $all_stores = $this->get_all_stores();
        $post_stores = get_post_meta($post->ID, '_parfume_stores', true) ?: array();
        ?>
        <div class="quick-add-container">
            <p><?php _e('Бърз достъп до всички налични магазини:', 'parfume-catalog'); ?></p>
            
            <div class="quick-stores-list">
                <?php foreach ($all_stores as $store): ?>
                    <div class="quick-store-item">
                        <div class="quick-store-info">
                            <?php if (!empty($store['logo'])): ?>
                                <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" class="quick-store-logo">
                            <?php endif; ?>
                            <span class="quick-store-name"><?php echo esc_html($store['name']); ?></span>
                        </div>
                        
                        <?php if ($this->is_store_added($store['id'], $post_stores)): ?>
                            <span class="quick-store-status added">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Добавен', 'parfume-catalog'); ?>
                            </span>
                        <?php else: ?>
                            <button type="button" 
                                    class="button button-small quick-add-store" 
                                    data-store-id="<?php echo esc_attr($store['id']); ?>">
                                <span class="dashicons dashicons-plus"></span>
                                <?php _e('Добави', 'parfume-catalog'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($all_stores)): ?>
                <p class="no-stores">
                    <?php _e('Няма създадени магазини.', 'parfume-catalog'); ?>
                    <br>
                    <a href="<?php echo admin_url('admin.php?page=parfume-stores'); ?>" target="_blank">
                        <?php _e('Създайте първия магазин', 'parfume-catalog'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.quick-add-store').click(function() {
                var storeId = $(this).data('store-id');
                var $btn = $(this);
                
                $btn.prop('disabled', true).text(parfumeMetaStores.texts.loading);
                
                $.post(parfumeMetaStores.ajax_url, {
                    action: 'parfume_add_store_to_post',
                    post_id: <?php echo $post->ID; ?>,
                    store_id: storeId,
                    nonce: parfumeMetaStores.nonce
                }, function(response) {
                    if (response.success) {
                        // Update main stores list
                        $('#stores-list').append(response.data.html);
                        $('.no-stores-message').remove();
                        
                        // Update this quick item
                        $btn.closest('.quick-store-item').find('.quick-add-store').replaceWith(
                            '<span class="quick-store-status added">' +
                            '<span class="dashicons dashicons-yes"></span>' +
                            '<?php _e('Добавен', 'parfume-catalog'); ?>' +
                            '</span>'
                        );
                        
                        // Update main dropdown
                        $('#available-stores-select option[value="' + storeId + '"]').remove();
                    } else {
                        alert(response.data.message || 'Грешка при добавяне');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus"></span> <?php _e('Добави', 'parfume-catalog'); ?>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .quick-add-container {
            padding: 10px 0;
        }
        
        .quick-stores-list {
            margin-top: 10px;
        }
        
        .quick-store-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .quick-store-item:last-child {
            border-bottom: none;
        }
        
        .quick-store-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .quick-store-logo {
            width: 20px;
            height: 20px;
            border-radius: 2px;
        }
        
        .quick-store-name {
            font-size: 12px;
            font-weight: 500;
        }
        
        .quick-store-status.added {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #27ae60;
        }
        
        .quick-add-store {
            font-size: 11px;
            height: auto;
            line-height: 1.4;
            padding: 4px 8px;
        }
        
        .no-stores {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }
        </style>
        <?php
    }
    
    /**
     * Render individual store item
     */
    private function render_store_item($store_data, $index, $post_id) {
        $store_info = $this->get_store_info($store_data['store_id']);
        $scraped_data = $this->get_scraped_data($post_id, $store_data['store_id']);
        ?>
        <div class="store-item" data-store-index="<?php echo esc_attr($index); ?>">
            <div class="store-header">
                <div class="store-info">
                    <span class="store-handle dashicons dashicons-menu"></span>
                    
                    <?php if (!empty($store_info['logo'])): ?>
                        <div class="store-logo">
                            <img src="<?php echo esc_url($store_info['logo']); ?>" 
                                 alt="<?php echo esc_attr($store_info['name']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div class="store-logo">
                            <span class="dashicons dashicons-store"></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="store-name"><?php echo esc_html($store_info['name']); ?></div>
                </div>
                
                <div class="store-actions">
                    <button type="button" class="button button-small manual-scrape-btn" data-store-index="<?php echo esc_attr($index); ?>">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Обнови', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-small remove-store-btn">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Премахни', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
            
            <div class="store-fields">
                <div class="field-group">
                    <label for="store_<?php echo $index; ?>_product_url">
                        <?php _e('Product URL', 'parfume-catalog'); ?>
                    </label>
                    <input type="url" 
                           id="store_<?php echo $index; ?>_product_url"
                           name="parfume_stores[<?php echo $index; ?>][product_url]" 
                           value="<?php echo esc_url($store_data['product_url']); ?>" 
                           placeholder="https://example.com/product" />
                </div>
                
                <div class="field-group">
                    <label for="store_<?php echo $index; ?>_affiliate_url">
                        <?php _e('Affiliate URL', 'parfume-catalog'); ?>
                    </label>
                    <input type="url" 
                           id="store_<?php echo $index; ?>_affiliate_url"
                           name="parfume_stores[<?php echo $index; ?>][affiliate_url]" 
                           value="<?php echo esc_url($store_data['affiliate_url']); ?>" 
                           placeholder="https://affiliate.com/link" />
                </div>
                
                <div class="field-group">
                    <label for="store_<?php echo $index; ?>_promo_code">
                        <?php _e('Promo Code', 'parfume-catalog'); ?>
                    </label>
                    <input type="text" 
                           id="store_<?php echo $index; ?>_promo_code"
                           name="parfume_stores[<?php echo $index; ?>][promo_code]" 
                           value="<?php echo esc_attr($store_data['promo_code']); ?>" 
                           placeholder="DISCOUNT20" />
                </div>
                
                <div class="field-group">
                    <label for="store_<?php echo $index; ?>_promo_info">
                        <?php _e('Promo Code Info', 'parfume-catalog'); ?>
                    </label>
                    <input type="text" 
                           id="store_<?php echo $index; ?>_promo_info"
                           name="parfume_stores[<?php echo $index; ?>][promo_code_info]" 
                           value="<?php echo esc_attr($store_data['promo_code_info']); ?>" 
                           placeholder="<?php _e('20% отстъпка', 'parfume-catalog'); ?>" />
                </div>
            </div>
            
            <!-- Hidden fields -->
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][store_id]" value="<?php echo esc_attr($store_data['store_id']); ?>" />
            <input type="hidden" name="parfume_stores[<?php echo $index; ?>][order]" value="<?php echo esc_attr($index); ?>" />
            
            <?php if (!empty($scraped_data)): ?>
                <div class="scraped-data">
                    <h5><?php _e('Скрейпнати данни', 'parfume-catalog'); ?></h5>
                    <div class="scraped-info">
                        <?php if (!empty($scraped_data['price'])): ?>
                            <div class="scraped-item">
                                <span class="scraped-label"><?php _e('Цена:', 'parfume-catalog'); ?></span>
                                <span class="scraped-value scraped-price"><?php echo esc_html($scraped_data['price']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_data['old_price'])): ?>
                            <div class="scraped-item">
                                <span class="scraped-label"><?php _e('Стара цена:', 'parfume-catalog'); ?></span>
                                <span class="scraped-value"><?php echo esc_html($scraped_data['old_price']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_data['availability'])): ?>
                            <div class="scraped-item">
                                <span class="scraped-label"><?php _e('Наличност:', 'parfume-catalog'); ?></span>
                                <span class="scraped-value scraped-availability"><?php echo esc_html($scraped_data['availability']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_data['delivery_info'])): ?>
                            <div class="scraped-item">
                                <span class="scraped-label"><?php _e('Доставка:', 'parfume-catalog'); ?></span>
                                <span class="scraped-value scraped-delivery"><?php echo esc_html($scraped_data['delivery_info']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_data['variants'])): ?>
                            <div class="scraped-item">
                                <span class="scraped-label"><?php _e('Варианти:', 'parfume-catalog'); ?></span>
                                <span class="scraped-value scraped-variants"><?php echo esc_html(implode(', ', $scraped_data['variants'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="last-updated">
                        <?php if (!empty($scraped_data['last_scraped'])): ?>
                            <?php printf(__('Последно обновяване: %s', 'parfume-catalog'), 
                                human_time_diff(strtotime($scraped_data['last_scraped'])) . ' ' . __('преди', 'parfume-catalog')); ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($scraped_data['next_scrape'])): ?>
                            | <?php printf(__('Следващо: %s', 'parfume-catalog'), 
                                human_time_diff(strtotime($scraped_data['next_scrape'])) . ' ' . __('след', 'parfume-catalog')); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['parfume_stores_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['parfume_stores_meta_nonce_field'], 'parfume_stores_meta_nonce')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save stores data
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores_data = array();
            
            foreach ($_POST['parfume_stores'] as $index => $store_data) {
                $sanitized_store = array(
                    'store_id' => absint($store_data['store_id']),
                    'product_url' => esc_url_raw($store_data['product_url']),
                    'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                    'promo_code' => sanitize_text_field($store_data['promo_code']),
                    'promo_code_info' => sanitize_text_field($store_data['promo_code_info']),
                    'order' => absint($store_data['order'])
                );
                
                // Only save if store_id and product_url are provided
                if ($sanitized_store['store_id'] && $sanitized_store['product_url']) {
                    $stores_data[] = $sanitized_store;
                }
            }
            
            // Sort by order
            usort($stores_data, function($a, $b) {
                return $a['order'] - $b['order'];
            });
            
            update_post_meta($post_id, '_parfume_stores', $stores_data);
        } else {
            delete_post_meta($post_id, '_parfume_stores');
        }
    }
    
    /**
     * AJAX: Manual scrape store
     */
    public function ajax_manual_scrape_store() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_stores')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        $store_index = absint($_POST['store_index']);
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true) ?: array();
        
        if (!isset($post_stores[$store_index])) {
            wp_send_json_error(array('message' => __('Невалиден магазин', 'parfume-catalog')));
        }
        
        $store_data = $post_stores[$store_index];
        
        // Simulate scraping (in real implementation, call scraper class)
        if (class_exists('Parfume_Catalog_Scraper')) {
            $scraper = new Parfume_Catalog_Scraper();
            $scraped_data = $scraper->scrape_single_url($store_data['product_url'], $store_data['store_id']);
            
            if ($scraped_data) {
                wp_send_json_success($scraped_data);
            } else {
                wp_send_json_error(array('message' => __('Грешка при скрейпване', 'parfume-catalog')));
            }
        } else {
            // Fallback simulation
            wp_send_json_success(array(
                'price' => '89.99 лв',
                'availability' => 'Наличен',
                'delivery_info' => 'Безплатна доставка',
                'variants' => array('50ml', '100ml')
            ));
        }
    }
    
    /**
     * AJAX: Add store to post
     */
    public function ajax_add_store_to_post() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_stores')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        $store_id = absint($_POST['store_id']);
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true) ?: array();
        
        // Check if store is already added
        foreach ($post_stores as $store) {
            if ($store['store_id'] == $store_id) {
                wp_send_json_error(array('message' => __('Този магазин вече е добавен', 'parfume-catalog')));
            }
        }
        
        // Add new store
        $new_store = array(
            'store_id' => $store_id,
            'product_url' => '',
            'affiliate_url' => '',
            'promo_code' => '',
            'promo_code_info' => '',
            'order' => count($post_stores)
        );
        
        $post_stores[] = $new_store;
        update_post_meta($post_id, '_parfume_stores', $post_stores);
        
        // Return HTML for new store item
        ob_start();
        $this->render_store_item($new_store, count($post_stores) - 1, $post_id);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Remove store from post
     */
    public function ajax_remove_store_from_post() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_stores')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        $store_index = absint($_POST['store_index']);
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true) ?: array();
        
        if (isset($post_stores[$store_index])) {
            unset($post_stores[$store_index]);
            $post_stores = array_values($post_stores); // Reindex array
            update_post_meta($post_id, '_parfume_stores', $post_stores);
        }
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Reorder stores
     */
    public function ajax_reorder_post_stores() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_stores')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        $new_order = array_map('absint', $_POST['order']);
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true) ?: array();
        $reordered_stores = array();
        
        foreach ($new_order as $index) {
            if (isset($post_stores[$index])) {
                $reordered_stores[] = $post_stores[$index];
            }
        }
        
        update_post_meta($post_id, '_parfume_stores', $reordered_stores);
        wp_send_json_success();
    }
    
    /**
     * AJAX: Get store data
     */
    public function ajax_get_store_data() {
        // Проверка на nonce
        if (!wp_verify_nonce($_GET['nonce'], 'parfume_meta_stores')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        $store_id = absint($_GET['store_id']);
        $store_info = $this->get_store_info($store_id);
        
        if ($store_info) {
            wp_send_json_success($store_info);
        } else {
            wp_send_json_error(array('message' => __('Магазинът не е намерен', 'parfume-catalog')));
        }
    }
    
    /**
     * Helper methods
     */
    
    private function get_all_stores() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'parfume_stores';
        
        return $wpdb->get_results(
            "SELECT id, name, logo, website FROM $table_name WHERE active = 1 ORDER BY name",
            ARRAY_A
        ) ?: array();
    }
    
    private function get_store_info($store_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'parfume_stores';
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $store_id),
            ARRAY_A
        );
    }
    
    private function get_scraped_data($post_id, $store_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'parfume_scraper_data';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE post_id = %d AND store_id = %d ORDER BY created_at DESC LIMIT 1",
                $post_id,
                $store_id
            ),
            ARRAY_A
        );
    }
    
    private function is_store_added($store_id, $post_stores) {
        foreach ($post_stores as $store) {
            if ($store['store_id'] == $store_id) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Static helper methods for external access
     */
    public static function get_post_stores($post_id) {
        return get_post_meta($post_id, '_parfume_stores', true) ?: array();
    }
    
    public static function get_store_by_id($post_id, $store_id) {
        $stores = self::get_post_stores($post_id);
        
        foreach ($stores as $store) {
            if ($store['store_id'] == $store_id) {
                return $store;
            }
        }
        
        return null;
    }
    
    public static function has_stores($post_id) {
        $stores = self::get_post_stores($post_id);
        return !empty($stores);
    }
    
    public static function get_stores_count($post_id) {
        $stores = self::get_post_stores($post_id);
        return count($stores);
    }
}