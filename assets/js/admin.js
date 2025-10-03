/**
 * Admin JavaScript for Parfume Reviews Plugin
 * Handles media upload for taxonomy terms
 * 
 * ФАЙЛ: assets/js/admin.js
 * ПОПРАВЕНА ВЕРСИЯ - Довършен media upload код
 */
jQuery(document).ready(function($) {
    
    // Media uploader for taxonomy terms
    var mediaUploader;
    
    // Handle upload button click
    $(document).on('click', '.pr_tax_media_button', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var fieldId = button.data('field');
        var wrapperId = button.data('wrapper');
        var $field = $('#' + fieldId);
        var $wrapper = $('#' + wrapperId);
        
        // Create a new media uploader
        mediaUploader = wp.media({
            title: parfumeTaxonomy.selectImageTitle || 'Избери изображение',
            button: {
                text: parfumeTaxonomy.selectImageButton || 'Използвай това изображение'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected in the media uploader
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // ПОПРАВЕНО: Записваме ID, не URL!
            $field.val(attachment.id);
            
            // Показваме preview с thumbnail URL
            var thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail 
                ? attachment.sizes.thumbnail.url 
                : attachment.url;
            
            // ПОПРАВЕНО: Довършен код за показване на preview
            var previewHtml = '<div class="taxonomy-image-preview">' +
                '<img src="' + thumbnailUrl + '" alt="" style="max-width: 150px; height: auto; display: block; margin: 10px 0;" />' +
                '</div>';
            
            $wrapper.html(previewHtml);
            
            // Debug log
            if (typeof console !== 'undefined' && console.log) {
                console.log('Image selected - ID:', attachment.id, 'URL:', attachment.url);
            }
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    // Handle remove button click
    $(document).on('click', '.pr_tax_media_remove', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var fieldId = button.data('field');
        var wrapperId = button.data('wrapper');
        var $field = $('#' + fieldId);
        var $wrapper = $('#' + wrapperId);
        
        // Clear the field value
        $field.val('');
        
        // Remove the preview
        $wrapper.empty();
        
        // Debug log
        if (typeof console !== 'undefined' && console.log) {
            console.log('Image removed from field:', fieldId);
        }
    });
    
    // Initialize existing images on page load
    initializeExistingImages();
    
    function initializeExistingImages() {
        $('.custom_media_url').each(function() {
            var $field = $(this);
            var imageId = $field.val();
            
            if (imageId && imageId !== '') {
                var fieldId = $field.attr('id');
                var wrapperId = fieldId.replace('-image-id', '-image-wrapper');
                var $wrapper = $('#' + wrapperId);
                
                // Ако вече има preview, не правим нищо
                if ($wrapper.find('.taxonomy-image-preview').length > 0) {
                    return;
                }
                
                // Зареждаме изображението чрез AJAX
                loadImagePreview(imageId, $wrapper);
            }
        });
    }
    
    function loadImagePreview(imageId, $wrapper) {
        // Използваме WordPress REST API за да получим attachment данни
        $.ajax({
            url: '/wp-json/wp/v2/media/' + imageId,
            method: 'GET',
            success: function(response) {
                if (response && response.media_details) {
                    var thumbnailUrl = response.media_details.sizes && response.media_details.sizes.thumbnail
                        ? response.media_details.sizes.thumbnail.source_url
                        : response.source_url;
                    
                    var previewHtml = '<div class="taxonomy-image-preview">' +
                        '<img src="' + thumbnailUrl + '" alt="" style="max-width: 150px; height: auto; display: block; margin: 10px 0;" />' +
                        '</div>';
                    
                    $wrapper.html(previewHtml);
                }
            },
            error: function(xhr, status, error) {
                // Ако REST API не работи, използваме fallback
                if (typeof console !== 'undefined' && console.log) {
                    console.log('Could not load image preview via REST API:', error);
                }
            }
        });
    }
    
    // ============================================
    // STORES META BOX FUNCTIONALITY
    // ============================================
    
    // Add store to post
    $(document).on('click', '.add-store-to-post', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var postId = $button.data('post-id');
        var storeId = $('#store-select').val();
        
        if (!storeId) {
            alert(parfumeTaxonomy.selectStoreFirst || 'Моля изберете магазин първо');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'parfume_add_store_to_post',
                post_id: postId,
                store_id: storeId,
                nonce: parfumeTaxonomy.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text(parfumeTaxonomy.adding || 'Добавяне...');
            },
            success: function(response) {
                if (response.success) {
                    // Презареждаме списъка с магазини
                    location.reload();
                } else {
                    alert(response.data || 'Грешка при добавяне на магазин');
                }
            },
            error: function() {
                alert(parfumeTaxonomy.error || 'Възникна грешка. Моля опитайте отново.');
            },
            complete: function() {
                $button.prop('disabled', false).text(parfumeTaxonomy.addStore || 'Добави магазин');
            }
        });
    });
    
    // Remove store from post
    $(document).on('click', '.remove-store-from-post', function(e) {
        e.preventDefault();
        
        if (!confirm(parfumeTaxonomy.confirmRemove || 'Сигурни ли сте че искате да премахнете този магазин?')) {
            return;
        }
        
        var $button = $(this);
        var postId = $button.data('post-id');
        var storeId = $button.data('store-id');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'parfume_remove_store_from_post',
                post_id: postId,
                store_id: storeId,
                nonce: parfumeTaxonomy.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('.store-item').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || 'Грешка при премахване на магазин');
                }
            },
            error: function() {
                alert(parfumeTaxonomy.error || 'Възникна грешка. Моля опитайте отново.');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
    
    // Make stores list sortable
    if (typeof $.fn.sortable !== 'undefined') {
        $('.stores-list').sortable({
            handle: '.store-drag-handle',
            placeholder: 'store-placeholder',
            update: function(event, ui) {
                var storeOrder = [];
                $('.stores-list .store-item').each(function() {
                    storeOrder.push($(this).data('store-id'));
                });
                
                var postId = $('.stores-list').data('post-id');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'parfume_reorder_stores',
                        post_id: postId,
                        store_order: storeOrder,
                        nonce: parfumeTaxonomy.nonce
                    },
                    success: function(response) {
                        if (!response.success) {
                            console.error('Failed to save store order');
                        }
                    }
                });
            }
        });
    }
    
    // ============================================
    // SETTINGS PAGE TABS
    // ============================================
    
    // Handle tab switching
    $('.parfume-settings-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var $tab = $(this);
        var tabId = $tab.attr('href').substring(1);
        
        // Remove active class from all tabs
        $('.parfume-settings-tabs .nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab
        $tab.addClass('nav-tab-active');
        $('#' + tabId).addClass('active');
        
        // Save active tab to localStorage
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('parfume_active_tab', tabId);
        }
    });
    
    // Restore active tab from localStorage
    if (typeof localStorage !== 'undefined') {
        var activeTab = localStorage.getItem('parfume_active_tab');
        if (activeTab) {
            var $tab = $('.parfume-settings-tabs .nav-tab[href="#' + activeTab + '"]');
            if ($tab.length > 0) {
                $tab.trigger('click');
            }
        }
    }
    
    // ============================================
    // HELPER FUNCTIONS
    // ============================================
    
    // Copy text to clipboard
    window.copyToClipboard = function(text) {
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show feedback
        var $feedback = $('<div class="copy-feedback">Копирано!</div>');
        $('body').append($feedback);
        setTimeout(function() {
            $feedback.fadeOut(300, function() {
                $(this).remove();
            });
        }, 1500);
    };
});