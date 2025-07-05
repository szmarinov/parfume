// Admin Settings functionality for Parfume Reviews
jQuery(document).ready(function($) {
    
    // Auto-update URL preview when slug fields change
    function updateUrlPreviews() {
        var parfumeSlug = $('input[name="parfume_reviews_settings[parfume_slug]"]').val() || 'parfiumi';
        
        // Update all the URL examples in descriptions
        var slugFields = {
            'brands_slug': 'marki',
            'notes_slug': 'notes', 
            'perfumers_slug': 'parfumers',
            'gender_slug': 'gender',
            'aroma_type_slug': 'aroma-type',
            'season_slug': 'season',
            'intensity_slug': 'intensity'
        };
        
        $.each(slugFields, function(fieldName, defaultValue) {
            var slugValue = $('input[name="parfume_reviews_settings[' + fieldName + ']"]').val() || defaultValue;
            var $description = $('input[name="parfume_reviews_settings[' + fieldName + ']"]').siblings('.description');
            
            if ($description.length) {
                var originalText = $description.data('original-text');
                if (!originalText) {
                    originalText = $description.text();
                    $description.data('original-text', originalText);
                }
                
                // Update the URL example in the description
                var newText = originalText.replace(/\/[^\/]+\/[^\/]+\//g, '/' + parfumeSlug + '/' + slugValue + '/');
                $description.text(newText);
            }
        });
        
        // Update the URL structure info section if it exists
        updateUrlStructureInfo(parfumeSlug, slugFields);
    }
    
    function updateUrlStructureInfo(parfumeSlug, slugFields) {
        var $urlInfo = $('.url-structure-info');
        if ($urlInfo.length) {
            var $list = $urlInfo.find('ul');
            if ($list.length) {
                var html = '';
                
                // Main archive
                html += '<li>Main Archive: <code>/' + parfumeSlug + '/</code></li>';
                html += '<li>Single Perfume: <code>/' + parfumeSlug + '/perfume-name/</code></li>';
                
                // Taxonomies
                $.each(slugFields, function(fieldName, defaultValue) {
                    var slugValue = $('input[name="parfume_reviews_settings[' + fieldName + ']"]').val() || defaultValue;
                    var label = fieldName.replace('_slug', '').replace('_', ' ');
                    label = label.charAt(0).toUpperCase() + label.slice(1);
                    
                    if (fieldName === 'brands_slug') label = 'Brands';
                    else if (fieldName === 'aroma_type_slug') label = 'Aroma Type';
                    
                    html += '<li>' + label + ': <code>/' + parfumeSlug + '/' + slugValue + '/' + label.toLowerCase().replace(' ', '-') + '-name/</code></li>';
                });
                
                $list.html(html);
            }
        }
    }
    
    // Attach event listeners to slug input fields
    $('input[name^="parfume_reviews_settings["][name$="_slug]"]').on('input', function() {
        updateUrlPreviews();
    });
    
    // Initial update
    updateUrlPreviews();
    
    // Form validation
    $('form[action="options.php"]').on('submit', function(e) {
        var isValid = true;
        var errors = [];
        
        // Check that slug fields are not empty
        $('input[name^="parfume_reviews_settings["][name$="_slug]"]').each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            var fieldName = $field.attr('name').match(/\[([^\]]+)\]/)[1];
            
            if (value === '') {
                errors.push('The ' + fieldName.replace('_', ' ') + ' field cannot be empty.');
                $field.css('border-color', '#dc3232');
                isValid = false;
            } else {
                $field.css('border-color', '');
            }
            
            // Check for invalid characters
            if (!/^[a-z0-9\-]+$/.test(value)) {
                errors.push('The ' + fieldName.replace('_', ' ') + ' field can only contain lowercase letters, numbers, and hyphens.');
                $field.css('border-color', '#dc3232');
                isValid = false;
            }
        });
        
        // Check price update interval
        var priceInterval = $('input[name="parfume_reviews_settings[price_update_interval]"]').val();
        if (priceInterval && (isNaN(priceInterval) || parseInt(priceInterval) < 1)) {
            errors.push('Price update interval must be a positive number.');
            $('input[name="parfume_reviews_settings[price_update_interval]"]').css('border-color', '#dc3232');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
            return false;
        }
        
        // Show confirmation for URL changes
        var hasUrlChanges = false;
        $('input[name^="parfume_reviews_settings["][name$="_slug]"]').each(function() {
            var $field = $(this);
            var originalValue = $field.data('original-value');
            if (originalValue === undefined) {
                // Store original value on page load
                $field.data('original-value', $field.val());
            } else if (originalValue !== $field.val()) {
                hasUrlChanges = true;
            }
        });
        
        if (hasUrlChanges) {
            var confirmMessage = 'You have changed URL slugs. This will change the URLs for all taxonomy pages. ' +
                                'Make sure to update any links and consider setting up redirects from old URLs. ' +
                                'Continue with saving?';
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Store original values on page load
    $('input[name^="parfume_reviews_settings["][name$="_slug]"]').each(function() {
        $(this).data('original-value', $(this).val());
    });
    
    // Help tooltips
    $('<style>.help-tooltip { cursor: help; color: #0073aa; margin-left: 5px; }</style>').appendTo('head');
    
    // Add help tooltips to descriptions
    $('.description').each(function() {
        var $desc = $(this);
        if ($desc.text().indexOf('Will create URLs like') !== -1) {
            $desc.append(' <span class="help-tooltip" title="URLs will be automatically updated when you change these settings">?</span>');
        }
    });
    
    // Import file validation
    $('input[name="parfume_import_file"]').on('change', function() {
        var file = this.files[0];
        if (file) {
            var fileName = file.name.toLowerCase();
            var fileSize = file.size;
            
            // Check file extension
            if (!fileName.endsWith('.json')) {
                alert('Please select a JSON file.');
                $(this).val('');
                return;
            }
            
            // Check file size (10MB limit)
            if (fileSize > 10 * 1024 * 1024) {
                alert('File is too large. Maximum size is 10MB.');
                $(this).val('');
                return;
            }
            
            // Show file info
            var fileInfo = 'Selected file: ' + file.name + ' (' + Math.round(fileSize / 1024) + ' KB)';
            var $info = $(this).siblings('.file-info');
            if ($info.length === 0) {
                $info = $('<div class="file-info" style="margin-top: 5px; font-size: 12px; color: #666;"></div>');
                $(this).after($info);
            }
            $info.text(fileInfo);
        }
    });
    
    // Collapsible sections
    $('.json-format-instructions details').on('toggle', function() {
        if (this.open) {
            $(this).find('summary').css('background', '#c0c0c0');
        } else {
            $(this).find('summary').css('background', '#e0e0e0');
        }
    });
});