/**
 * Admin JavaScript for Parfume Reviews Plugin
 * Handles media upload for taxonomy terms
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
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Create a new media uploader
        mediaUploader = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected in the media uploader
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Set the image URL to the input field
            $field.val(attachment.url);
            
            // Display the image preview
            var imageHtml = '<img src="' + attachment.url + '" alt="" style="max-width: 100px; height: auto; display: block; margin-bottom: 10px;">';
            $wrapper.html(imageHtml);
            
            // Update button text
            button.val('Change Image');
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
        $addButton.val('Add Image');
    });
    
    // Initialize existing images on page load
    $('.custom_media_url').each(function() {
        var $field = $(this);
        var fieldId = $field.attr('id');
        var wrapperId = fieldId.replace(/[^a-zA-Z0-9]/g, '') + '-wrapper';
        var $wrapper = $('#' + wrapperId);
        var imageUrl = $field.val();
        
        if (imageUrl && $wrapper.length) {
            var imageHtml = '<img src="' + imageUrl + '" alt="" style="max-width: 100px; height: auto; display: block; margin-bottom: 10px;">';
            $wrapper.html(imageHtml);
            
            // Update button text
            var $button = $wrapper.siblings('p').find('.pr_tax_media_button');
            $button.val('Change Image');
        }
    });
    
    // Enhanced form validation for perfumer fields
    $('form#addtag, form#edittag').on('submit', function(e) {
        var form = $(this);
        var errors = [];
        
        // Check if this is a perfumer taxonomy form
        if (form.find('input[name="taxonomy"]').val() === 'perfumer' || 
            window.location.href.indexOf('taxonomy=perfumer') !== -1) {
            
            // Validate birth date
            var birthdate = form.find('#perfumer_birthdate').val();
            if (birthdate) {
                var birthdateObj = new Date(birthdate);
                var today = new Date();
                var minDate = new Date('1900-01-01');
                
                if (birthdateObj > today) {
                    errors.push('Birth date cannot be in the future.');
                } else if (birthdateObj < minDate) {
                    errors.push('Birth date seems too old. Please check the date.');
                }
            }
            
            // Validate URLs
            var urlFields = ['perfumer_website', 'perfumer_instagram', 'perfumer_facebook', 'perfumer_twitter', 'perfumer_linkedin'];
            urlFields.forEach(function(fieldId) {
                var url = form.find('#' + fieldId).val();
                if (url && !isValidUrl(url)) {
                    errors.push('Invalid URL in field: ' + fieldId.replace('perfumer_', '').replace('_', ' '));
                }
            });
            
            // Show errors if any
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
        }
    });
    
    // URL validation helper function
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Auto-format nationality field
    $('#perfumer_nationality').on('blur', function() {
        var value = $(this).val();
        if (value) {
            // Capitalize first letter
            var formatted = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
            $(this).val(formatted);
        }
    });
    
    // Character counter for textarea fields
    var textareaFields = ['perfumer_education', 'perfumer_signature_style', 'perfumer_awards'];
    textareaFields.forEach(function(fieldId) {
        var $field = $('#' + fieldId);
        if ($field.length) {
            // Add character counter
            var $counter = $('<div class="character-counter" style="text-align: right; margin-top: 5px; font-size: 12px; color: #666;"></div>');
            $field.after($counter);
            
            // Update counter on input
            $field.on('input', function() {
                var length = $(this).val().length;
                var maxLength = 1000; // Set reasonable limit
                
                $counter.text(length + ' / ' + maxLength + ' characters');
                
                if (length > maxLength * 0.9) {
                    $counter.css('color', '#d63638');
                } else if (length > maxLength * 0.7) {
                    $counter.css('color', '#dba617');
                } else {
                    $counter.css('color', '#666');
                }
            }).trigger('input');
        }
    });
    
    // Preview functionality for social media links
    $('input[name^="perfumer_social_media"]').on('blur', function() {
        var url = $(this).val();
        var platform = $(this).attr('name').match(/\[(.+)\]/)[1];
        
        if (url && isValidUrl(url)) {
            // Validate platform-specific URL format
            var platformPatterns = {
                'instagram': /instagram\.com\/[a-zA-Z0-9_.]+/,
                'facebook': /facebook\.com\/[a-zA-Z0-9_.]+/,
                'twitter': /twitter\.com\/[a-zA-Z0-9_]+/,
                'linkedin': /linkedin\.com\/in\/[a-zA-Z0-9-]+/
            };
            
            if (platformPatterns[platform] && !platformPatterns[platform].test(url)) {
                $(this).css('border-color', '#d63638');
                
                // Show warning message
                var $warning = $(this).siblings('.url-warning');
                if ($warning.length === 0) {
                    $warning = $('<div class="url-warning" style="color: #d63638; font-size: 12px; margin-top: 2px;"></div>');
                    $(this).after($warning);
                }
                $warning.text('This doesn\'t look like a valid ' + platform + ' URL.');
            } else {
                $(this).css('border-color', '');
                $(this).siblings('.url-warning').remove();
            }
        }
    });
    
    // Tab functionality for better organization
    if ($('.perfumer-meta-tabs').length === 0 && $('#perfumer_nationality').length) {
        // Create tabs for perfumer edit form
        var $form = $('#edittag');
        var $perfumerFields = $form.find('tr:has(#perfumer_nationality)').nextAll('tr').addBack();
        
        if ($perfumerFields.length > 1) {
            // Create tab structure
            var $tabContainer = $('<div class="perfumer-meta-tabs" style="margin: 20px 0;"></div>');
            var $tabNav = $('<div class="tab-nav" style="border-bottom: 1px solid #ccd0d4; margin-bottom: 20px;"></div>');
            
            // Tab buttons
            var tabs = [
                { id: 'basic-info', label: 'Basic Info', fields: ['perfumer_nationality', 'perfumer_birthdate'] },
                { id: 'biography', label: 'Biography', fields: ['perfumer_education', 'perfumer_signature_style', 'perfumer_awards'] },
                { id: 'online-presence', label: 'Online Presence', fields: ['perfumer_website', 'perfumer_social_media'] }
            ];
            
            tabs.forEach(function(tab, index) {
                var $tabButton = $('<button type="button" class="tab-button" data-tab="' + tab.id + '" style="padding: 10px 20px; border: none; background: none; cursor: pointer; margin-right: 10px;">' + tab.label + '</button>');
                if (index === 0) $tabButton.addClass('active').css('border-bottom', '2px solid #0073aa');
                $tabNav.append($tabButton);
            });
            
            $tabContainer.append($tabNav);
            
            // Insert tabs before first perfumer field
            $perfumerFields.first().before($tabContainer);
            
            // Group fields into tabs
            tabs.forEach(function(tab, index) {
                var $tabContent = $('<div class="tab-content" data-tab="' + tab.id + '" style="' + (index === 0 ? '' : 'display: none;') + '"></div>');
                
                tab.fields.forEach(function(fieldName) {
                    var $field = $form.find('#' + fieldName).closest('tr');
                    if ($field.length === 0) {
                        // Handle social media fields
                        $field = $form.find('input[name^="' + fieldName + '"]').closest('tr');
                    }
                    $tabContent.append($field);
                });
                
                $tabContainer.append($tabContent);
            });
            
            // Tab switching functionality
            $('.tab-button').on('click', function() {
                var targetTab = $(this).data('tab');
                
                // Update buttons
                $('.tab-button').removeClass('active').css('border-bottom', 'none');
                $(this).addClass('active').css('border-bottom', '2px solid #0073aa');
                
                // Update content
                $('.tab-content').hide();
                $('.tab-content[data-tab="' + targetTab + '"]').show();
            });
        }
    }
});