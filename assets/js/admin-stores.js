/**
 * Admin Stores JavaScript
 * 
 * Handles stores meta box functionality
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    let storeIndex = 0;
    
    /**
     * Initialize
     */
    function init() {
        // Set initial index
        storeIndex = $('.store-item').length;
        
        // Add store button
        $(document).on('click', '.add-store-item', handleAddStore);
        
        // Remove store button
        $(document).on('click', '.remove-store-item', handleRemoveStore);
        
        // Toggle store details
        $(document).on('click', '.toggle-store-details', handleToggleDetails);
        
        // Store select change
        $(document).on('change', '.store-select-field', handleStoreSelect);
        
        // Scrape now button
        $(document).on('click', '.scrape-now-button', handleScrapeNow);
        
        // Make stores sortable
        initSortable();
        
        // Initialize collapsed state
        $('.store-item-content').hide();
    }
    
    /**
     * Add new store
     */
    function handleAddStore(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $list = $('#stores-list');
        const $template = $('#store-item-template');
        
        if (!$template.length) {
            console.error('Store item template not found');
            return;
        }
        
        // Get template HTML
        let html = $template.html();
        
        // Replace placeholders
        html = html.replace(/\{\{INDEX\}\}/g, storeIndex);
        html = html.replace(/\{\{STORE_NAME\}\}/g, 'Нов магазин');
        
        // Append to list
        const $newItem = $(html);
        $list.append($newItem);
        
        // Show details for new item
        $newItem.find('.store-item-content').show();
        $newItem.find('.toggle-store-details .dashicons')
            .removeClass('dashicons-arrow-down-alt2')
            .addClass('dashicons-arrow-up-alt2');
        
        // Scroll to new item
        $('html, body').animate({
            scrollTop: $newItem.offset().top - 100
        }, 300);
        
        // Increment index
        storeIndex++;
    }
    
    /**
     * Remove store
     */
    function handleRemoveStore(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $item = $button.closest('.store-item');
        
        if (!confirm('Сигурни ли сте, че искате да премахнете този магазин?')) {
            return;
        }
        
        // Animate removal
        $item.slideUp(300, function() {
            $(this).remove();
            reindexStores();
        });
    }
    
    /**
     * Toggle store details
     */
    function handleToggleDetails(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $item = $button.closest('.store-item');
        const $content = $item.find('.store-item-content');
        const $icon = $button.find('.dashicons');
        
        $content.slideToggle(300, function() {
            if ($content.is(':visible')) {
                $icon.removeClass('dashicons-arrow-down-alt2')
                     .addClass('dashicons-arrow-up-alt2');
            } else {
                $icon.removeClass('dashicons-arrow-up-alt2')
                     .addClass('dashicons-arrow-down-alt2');
            }
        });
    }
    
    /**
     * Handle store select change
     */
    function handleStoreSelect(e) {
        const $select = $(this);
        const $item = $select.closest('.store-item');
        const $option = $select.find('option:selected');
        
        const storeName = $option.data('name') || $option.text();
        const storeLogo = $option.data('logo') || '';
        
        // Update header
        const $header = $item.find('.store-item-header');
        $header.find('.store-item-title').text(storeName);
        
        // Update logo
        $header.find('.store-logo-preview').remove();
        if (storeLogo) {
            $header.prepend(
                $('<img>', {
                    src: storeLogo,
                    alt: storeName,
                    class: 'store-logo-preview'
                })
            );
        }
    }
    
    /**
     * Handle scrape now button
     */
    function handleScrapeNow(e) {
        e.preventDefault();
        
        const $button = $(this);
        const postId = $button.data('post-id');
        const storeIndex = $button.data('store-index');
        
        if (!postId || storeIndex === undefined) {
            alert('Невалидни данни');
            return;
        }
        
        // Disable button
        $button.prop('disabled', true);
        $button.html('<span class="dashicons dashicons-update spin"></span> Обновява...');
        
        // AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'parfume_update_price',
                post_id: postId,
                store_index: storeIndex,
                nonce: parfumeStores.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotice('Цената е обновена успешно!', 'success');
                    
                    // Reload page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('Грешка: ' + response.data, 'error');
                    $button.prop('disabled', false);
                    $button.html('<span class="dashicons dashicons-update"></span> Обнови сега');
                }
            },
            error: function(xhr, status, error) {
                showNotice('AJAX грешка: ' + error, 'error');
                $button.prop('disabled', false);
                $button.html('<span class="dashicons dashicons-update"></span> Обнови сега');
            }
        });
    }
    
    /**
     * Initialize sortable
     */
    function initSortable() {
        const $list = $('#stores-list');
        
        if (!$list.length) {
            return;
        }
        
        $list.sortable({
            handle: '.repeater-item-handle',
            placeholder: 'sortable-placeholder',
            axis: 'y',
            opacity: 0.7,
            cursor: 'move',
            update: function(event, ui) {
                reindexStores();
            }
        });
    }
    
    /**
     * Reindex stores after add/remove/sort
     */
    function reindexStores() {
        $('.store-item').each(function(index) {
            const $item = $(this);
            
            // Update data-index
            $item.attr('data-index', index);
            
            // Update all input names
            $item.find('select, input').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                
                if (name) {
                    // Replace [old_index] with [new_index]
                    const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $input.attr('name', newName);
                }
            });
            
            // Update button data attributes
            $item.find('.scrape-now-button').attr('data-store-index', index);
        });
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        const $notice = $('<div>', {
            class: 'notice notice-' + type + ' is-dismissible',
            html: '<p>' + message + '</p>'
        });
        
        // Remove existing notices
        $('.wrap > .notice').remove();
        
        // Add new notice
        $('.wrap h1').after($notice);
        
        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 50
        }, 300);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Add spinning animation for dashicons
     */
    const style = document.createElement('style');
    style.textContent = `
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize on document ready
    $(document).ready(init);
    
})(jQuery);