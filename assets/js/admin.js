/**
 * Admin JavaScript for Parfume Reviews Plugin
 * Handles media upload for taxonomy terms
 * ПОПРАВЕНА ВЕРСИЯ - записва ID вместо URL
 */
jQuery(document).ready(function($) {
    
    // Media uploader for taxonomy terms
    var mediaUploader;
    
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
            
            // ВАЖНО: Записваме ID, не URL!
            $field.val(attachment.id);
            
            // Показваме preview с thumbnail URL
            var thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail ? 
                              attachment.sizes.thumbnail.url : 
                              attachment.url;
            
            var imageHtml = '<img src="' + thumbnailUrl + '" alt="" style="max-width: 150px; height: auto; display: block; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">';
            $wrapper.html(imageHtml);
            
            // Update button text
            button.val('Промени изображение');
        });
        
        // Open the uploader
        mediaUploader.open();
    });
    
    // Remove image functionality
    $(document).on('click', '.pr_tax_media_remove', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var fieldId = button.data('field');
        var wrapperId = button.data('wrapper');
        var $field = $('#' + fieldId);
        var $wrapper = $('#' + wrapperId);
        var $addButton = button.siblings('.pr_tax_media_button');
        
        // Clear the field value
        $field.val('');
        
        // Clear the preview
        $wrapper.html('');
        
        // Reset button text
        $addButton.val('Добави изображение');
    });
    
    // Initialize existing images on page load
    $('.custom_media_url').each(function() {
        var $field = $(this);
        var fieldId = $field.attr('id');
        var wrapperId = fieldId.replace('-image-id', '-image-wrapper');
        var $wrapper = $('#' + wrapperId);
        var imageId = $field.val();
        
        // Ако имаме ID, но няма preview, зареждаме го
        if (imageId && $wrapper.length && $wrapper.is(':empty')) {
            // Ajax заявка за получаване на thumbnail URL от ID
            wp.media.attachment(imageId).fetch().then(function(attachment) {
                if (attachment && attachment.sizes) {
                    var thumbnailUrl = attachment.sizes.thumbnail ? 
                                      attachment.sizes.thumbnail.url : 
                                      attachment.url;
                    
                    var imageHtml = '<img src="' + thumbnailUrl + '" alt="" style="max-width: 150px; height: auto; display: block; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">';
                    $wrapper.html(imageHtml);
                    
                    // Update button text
                    var $button = $wrapper.siblings('p').find('.pr_tax_media_button');
                    $button.val('Промени изображение');
                }
            });
        }
    });
    
    // Enhanced form validation for perfumer fields
    $('form#addtag, form#edittag').on('submit', function(e) {
        var form = $(this);
        var errors = [];
        
        // Check if this is a perfumer taxonomy form
        if (form.find('input[name="taxonomy"]').val() === 'perfumer' || 
            window.location.href.indexOf('taxonomy=perfumer') !== -1) {
            
            // Validate birth date if exists
            var birthdate = form.find('#perfumer_birthdate').val();
            if (birthdate) {
                var birthdateObj = new Date(birthdate);
                var today = new Date();
                var minDate = new Date('1900-01-01');
                
                if (birthdateObj > today) {
                    errors.push('Датата на раждане не може да бъде в бъдещето.');
                } else if (birthdateObj < minDate) {
                    errors.push('Датата на раждане изглежда твърде стара. Моля проверете датата.');
                }
            }
        }
        
        // Show errors if any
        if (errors.length > 0) {
            alert('Моля поправете следните грешки:\n\n' + errors.join('\n'));
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-save functionality for long forms
    var autoSaveTimer;
    $('form#edittag input, form#edittag textarea, form#edittag select').on('input change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Save form data to localStorage as backup
            var formData = {};
            $('form#edittag').find('input, textarea, select').each(function() {
                var $element = $(this);
                var name = $element.attr('name');
                var value = $element.val();
                
                if (name && name !== 'action' && name !== '_wpnonce' && name !== '_wp_http_referer') {
                    formData[name] = value;
                }
            });
            
            if (Object.keys(formData).length > 0) {
                localStorage.setItem('parfume_taxonomy_backup', JSON.stringify(formData));
                console.log('Form data backed up locally');
            }
        }, 2000); // Auto-save every 2 seconds after user stops typing
    });
    
    // Restore form data from backup if available
    $(window).on('load', function() {
        var backup = localStorage.getItem('parfume_taxonomy_backup');
        if (backup) {
            try {
                var formData = JSON.parse(backup);
                var hasChanges = false;
                
                $.each(formData, function(name, value) {
                    var $element = $('[name="' + name + '"]');
                    if ($element.length && $element.val() !== value) {
                        hasChanges = true;
                    }
                });
                
                if (hasChanges && confirm('Намерени са запазени данни от предишна сесия. Искате ли да ги възстановите?')) {
                    $.each(formData, function(name, value) {
                        var $element = $('[name="' + name + '"]');
                        if ($element.length) {
                            $element.val(value);
                        }
                    });
                }
                
                // Clear backup after restore attempt
                localStorage.removeItem('parfume_taxonomy_backup');
            } catch (e) {
                console.log('Error restoring backup:', e);
            }
        }
    });
    
    // Clear backup on successful form submission
    $('form#edittag, form#addtag').on('submit', function() {
        localStorage.removeItem('parfume_taxonomy_backup');
    });
    
    // Helper function to show loading states
    function showLoadingState($element, message) {
        $element.prop('disabled', true);
        $element.data('original-text', $element.val() || $element.text());
        $element.val(message || 'Зареждане...');
    }
    
    function hideLoadingState($element) {
        $element.prop('disabled', false);
        var originalText = $element.data('original-text');
        if (originalText) {
            $element.val(originalText);
        }
    }
    
    // Show loading state for media buttons
    $(document).on('click', '.pr_tax_media_button', function() {
        showLoadingState($(this), 'Отваряне...');
        
        setTimeout(function() {
            hideLoadingState($(this));
        }.bind(this), 1000);
    });
});