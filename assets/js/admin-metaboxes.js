/**
 * Admin Meta Boxes JavaScript
 * 
 * Handles repeater fields and store management
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initRepeaterFields();
        initGalleryFields();
        initScraperButtons();
    });
    
    /**
     * Initialize repeater fields
     */
    function initRepeaterFields() {
        // Add repeater item
        $(document).on('click', '.add-repeater-item', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var fieldName = $button.data('field-name');
            var $container = $button.siblings('.repeater-items');
            var $template = $('#' + fieldName + '-template');
            
            if ($template.length === 0) {
                console.error('Template not found for:', fieldName);
                return;
            }
            
            // Get current index
            var index = $container.find('.repeater-item').length;
            
            // Get template HTML and replace placeholder
            var templateHtml = $template.html();
            var newItemHtml = templateHtml.replace(/\{\{INDEX\}\}/g, index);
            
            // Append new item
            $container.append(newItemHtml);
            
            // Focus first input
            $container.find('.repeater-item:last input:first').focus();
        });
        
        // Remove repeater item
        $(document).on('click', '.remove-repeater-item', function(e) {
            e.preventDefault();
            
            var $item = $(this).closest('.repeater-item');
            
            // Confirm deletion
            if (confirm('Наистина ли искате да премахнете този елемент?')) {
                $item.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
        
        // Make repeater items sortable
        if (typeof $.fn.sortable !== 'undefined') {
            $('.repeater-items').sortable({
                handle: '.repeater-item-handle',
                placeholder: 'repeater-item-placeholder',
                opacity: 0.6,
                cursor: 'move'
            });
        }
    }
    
    /**
     * Initialize gallery fields
     */
    function initGalleryFields() {
        var galleryFrame;
        
        // Add gallery images
        $(document).on('click', '.add-gallery-images', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var fieldName = $button.data('field-name');
            var $container = $button.siblings('.gallery-images');
            
            // Create media frame if not exists
            if (galleryFrame) {
                galleryFrame.open();
                return;
            }
            
            galleryFrame = wp.media({
                title: 'Изберете снимки',
                button: {
                    text: 'Добави'
                },
                multiple: true
            });
            
            // On select
            galleryFrame.on('select', function() {
                var attachments = galleryFrame.state().get('selection').toJSON();
                
                attachments.forEach(function(attachment) {
                    var imageHtml = '<div class="gallery-image" data-id="' + attachment.id + '">' +
                        '<img src="' + attachment.sizes.thumbnail.url + '" />' +
                        '<button type="button" class="remove-gallery-image">×</button>' +
                        '<input type="hidden" name="' + fieldName + '[]" value="' + attachment.id + '" />' +
                        '</div>';
                    
                    $container.append(imageHtml);
                });
            });
            
            galleryFrame.open();
        });
        
        // Remove gallery image
        $(document).on('click', '.remove-gallery-image', function(e) {
            e.preventDefault();
            $(this).closest('.gallery-image').fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Make gallery sortable
        if (typeof $.fn.sortable !== 'undefined') {
            $('.gallery-images').sortable({
                placeholder: 'gallery-image-placeholder',
                opacity: 0.6,
                cursor: 'move'
            });
        }
    }
    
    /**
     * Initialize scraper buttons
     */
    function initScraperButtons() {
        // Update price button
        $(document).on('click', '.update-store-price', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var postId = $button.data('post-id');
            var storeIndex = $button.data('store-index');
            var $priceField = $button.closest('.repeater-item').find('input[name*="[price]"]');
            
            if (!postId || storeIndex === undefined) {
                alert('Моля първо запазете парфюма!');
                return;
            }
            
            // Show loading
            $button.prop('disabled', true).text('Обновява се...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_update_price',
                    post_id: postId,
                    store_index: storeIndex,
                    nonce: parfumeMetaboxes.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update price field
                        if (response.data.price) {
                            $priceField.val(response.data.price);
                            
                            // Show success message
                            showNotice('Цената е обновена успешно!', 'success');
                        } else {
                            showNotice('Не успя да извлече цена', 'warning');
                        }
                    } else {
                        showNotice('Грешка: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotice('AJAX грешка: ' + error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('🔄 Обнови цена');
                }
            });
        });
        
        // Scrape URL button
        $(document).on('click', '.scrape-store-url', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $item = $button.closest('.repeater-item');
            var $urlField = $item.find('input[name*="[url]"]');
            var $nameField = $item.find('input[name*="[name]"]');
            var $priceField = $item.find('input[name*="[price]"]');
            var url = $urlField.val();
            
            if (!url) {
                alert('Моля въведете URL!');
                return;
            }
            
            // Show loading
            $button.prop('disabled', true).text('Scraping...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_scrape_product',
                    url: url,
                    nonce: parfumeMetaboxes.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        // Fill fields
                        if (data.name && !$nameField.val()) {
                            $nameField.val(data.name);
                        }
                        
                        if (data.price) {
                            $priceField.val(data.price);
                        }
                        
                        showNotice('Данните са извлечени успешно!', 'success');
                    } else {
                        showNotice('Грешка: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotice('AJAX грешка: ' + error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('🔍 Scrape данни');
                }
            });
        });
        
        // View scraper logs
        $(document).on('click', '.view-scraper-logs', function(e) {
            e.preventDefault();
            
            // Open logs in new window or show modal
            var logsUrl = ajaxurl + '?action=parfume_view_logs&nonce=' + parfumeMetaboxes.nonce;
            window.open(logsUrl, 'Scraper Logs', 'width=800,height=600,scrollbars=yes');
        });
    }
    
    /**
     * Show admin notice
     * 
     * @param {string} message Message text
     * @param {string} type Notice type (success, error, warning, info)
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Dismiss button
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Validate URL field
     */
    $(document).on('blur', 'input[type="url"]', function() {
        var $input = $(this);
        var url = $input.val();
        
        if (url && !isValidUrl(url)) {
            $input.css('border-color', '#dc3545');
            showNotice('Невалиден URL адрес', 'error');
        } else {
            $input.css('border-color', '');
        }
    });
    
    /**
     * Validate URL
     * 
     * @param {string} url URL to validate
     * @return {boolean}
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }
    
})(jQuery);