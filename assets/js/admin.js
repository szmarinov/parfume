/**
 * Admin JavaScript for Parfume Reviews Plugin
 * 
 * Handles all admin functionality including:
 * - Meta box interactions
 * - Dynamic form fields
 * - Media upload functionality
 * - Sortable lists
 * - AJAX admin functions
 * - Form validation
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initializeAdmin();
    });
    
    /**
     * Initialize all admin functionality
     */
    function initializeAdmin() {
        // Core admin functionality
        initMetaBoxes();
        initMediaUploader();
        initDynamicFields();
        initSortableLists();
        initTabNavigation();
        
        // Form handling
        initFormValidation();
        initDependentFields();
        initBulkActions();
        
        // Advanced features
        initColorPickers();
        initDatePickers();
        initCodeEditors();
        initTaxonomyMeta();
        
        // Import/Export functionality
        initImportExport();
        
        // Product scraper functionality
        initProductScraper();
        
        console.log('Parfume Reviews Admin initialized');
    }
    
    /**
     * Meta Box Functionality
     */
    function initMetaBoxes() {
        // Stores meta box functionality
        initStoresMetaBox();
        
        // Notes meta box
        initNotesMetaBox();
        
        // Characteristics meta box
        initCharacteristicsMetaBox();
        
        // Featured settings
        initFeaturedSettings();
        
        // Blog related parfumes
        initBlogRelatedParfumes();
    }
    
    /**
     * Stores Meta Box
     */
    function initStoresMetaBox() {
        var storeIndex = $('.store-item-admin').length;
        
        // Add new store
        $(document).on('click', '.add-store-btn', function(e) {
            e.preventDefault();
            
            var $container = $('.stores-container');
            var template = $('#store-item-template').html();
            
            if (!template) {
                console.error('Store template not found');
                return;
            }
            
            // Replace placeholders
            template = template.replace(/{{INDEX}}/g, storeIndex);
            
            $container.append(template);
            storeIndex++;
            
            // Initialize new store
            var $newStore = $container.find('.store-item-admin').last();
            initSingleStore($newStore);
            
            // Update store numbers
            updateStoreNumbers();
        });
        
        // Remove store
        $(document).on('click', '.store-remove', function(e) {
            e.preventDefault();
            
            if (confirm(parfumeReviewsAdmin.strings.confirmRemove)) {
                $(this).closest('.store-item-admin').remove();
                updateStoreNumbers();
            }
        });
        
        // Store URL change - trigger scraping
        $(document).on('change', '.store-product-url', function() {
            var $input = $(this);
            var $store = $input.closest('.store-item-admin');
            var url = $input.val().trim();
            
            if (url && isValidUrl(url)) {
                triggerStoreScraping($store, url);
            }
        });
        
        // Manual scrape button
        $(document).on('click', '.manual-scrape-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $store = $btn.closest('.store-item-admin');
            var url = $store.find('.store-product-url').val().trim();
            
            if (url) {
                triggerStoreScraping($store, url, true);
            }
        });
        
        // Initialize existing stores
        $('.store-item-admin').each(function() {
            initSingleStore($(this));
        });
        
        // Make stores sortable
        if ($('.stores-container').length) {
            $('.stores-container').sortable({
                handle: '.store-drag-handle',
                placeholder: 'store-placeholder',
                update: function() {
                    updateStoreNumbers();
                }
            });
        }
    }
    
    function initSingleStore($store) {
        // Store selection change
        $store.find('.store-select').on('change', function() {
            var storeId = $(this).val();
            var $store = $(this).closest('.store-item-admin');
            
            // Update store logo and info
            updateStoreDisplay($store, storeId);
        });
        
        // Toggle advanced options
        $store.find('.toggle-advanced').on('click', function(e) {
            e.preventDefault();
            
            var $advanced = $store.find('.advanced-options');
            $advanced.slideToggle();
            
            $(this).text($advanced.is(':visible') ? 'Hide Advanced' : 'Show Advanced');
        });
    }
    
    function triggerStoreScraping($store, url, manual = false) {
        var $indicator = $store.find('.scrape-indicator');
        var $results = $store.find('.scraped-results');
        
        $indicator.removeClass('success error').addClass('loading');
        $results.empty();
        
        if (!manual) {
            // Add delay to avoid rapid requests
            clearTimeout($store.data('scrapeTimeout'));
            $store.data('scrapeTimeout', setTimeout(function() {
                performScraping($store, url);
            }, 1000));
        } else {
            performScraping($store, url);
        }
    }
    
    function performScraping($store, url) {
        var postId = $('#post_ID').val();
        var storeIndex = $store.data('index');
        
        $.ajax({
            url: parfumeReviewsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'scrape_store_data',
                nonce: parfumeReviewsAdmin.nonce,
                url: url,
                post_id: postId,
                store_index: storeIndex
            },
            success: function(response) {
                var $indicator = $store.find('.scrape-indicator');
                var $results = $store.find('.scraped-results');
                
                if (response.success) {
                    $indicator.removeClass('loading error').addClass('success');
                    displayScrapedData($results, response.data);
                } else {
                    $indicator.removeClass('loading success').addClass('error');
                    $results.html('<div class="error">Error: ' + (response.data || 'Unknown error') + '</div>');
                }
            },
            error: function() {
                var $indicator = $store.find('.scrape-indicator');
                $indicator.removeClass('loading success').addClass('error');
            }
        });
    }
    
    function displayScrapedData($container, data) {
        var html = '<div class="scraped-data">';
        
        if (data.price) {
            html += '<div class="scraped-item"><strong>Price:</strong> ' + data.price + '</div>';
        }
        
        if (data.old_price) {
            html += '<div class="scraped-item"><strong>Old Price:</strong> ' + data.old_price + '</div>';
        }
        
        if (data.availability) {
            html += '<div class="scraped-item"><strong>Availability:</strong> ' + data.availability + '</div>';
        }
        
        if (data.variants && data.variants.length > 0) {
            html += '<div class="scraped-item"><strong>Variants:</strong><ul>';
            data.variants.forEach(function(variant) {
                html += '<li>' + variant.ml + ' - ' + variant.price + '</li>';
            });
            html += '</ul></div>';
        }
        
        html += '<div class="scraped-meta">Scraped: ' + new Date().toLocaleString() + '</div>';
        html += '</div>';
        
        $container.html(html);
    }
    
    function updateStoreNumbers() {
        $('.store-item-admin').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.store-number').text(index + 1);
            
            // Update field names
            $(this).find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                
                if (name && name.includes('[stores]')) {
                    name = name.replace(/\[stores\]\[\d+\]/, '[stores][' + index + ']');
                    $field.attr('name', name);
                }
            });
        });
    }
    
    function updateStoreDisplay($store, storeId) {
        // This would update store logo and information based on store selection
        // Implementation depends on available store data
        
        var $logo = $store.find('.store-logo');
        var $info = $store.find('.store-info');
        
        if (storeId && parfumeStores && parfumeStores[storeId]) {
            var storeData = parfumeStores[storeId];
            
            if (storeData.logo) {
                $logo.html('<img src="' + storeData.logo + '" alt="' + storeData.name + '">');
            }
            
            if (storeData.info) {
                $info.html(storeData.info);
            }
        } else {
            $logo.empty();
            $info.empty();
        }
    }
    
    /**
     * Notes Meta Box
     */
    function initNotesMetaBox() {
        // Notes selection with search
        if ($('.notes-selector').length) {
            $('.notes-selector').select2({
                placeholder: 'Search and select notes...',
                allowClear: true,
                width: '100%'
            });
        }
        
        // Add custom note
        $(document).on('click', '.add-custom-note', function(e) {
            e.preventDefault();
            
            var noteName = prompt('Enter note name:');
            if (noteName && noteName.trim()) {
                addCustomNote(noteName.trim());
            }
        });
        
        // Notes pyramid preview
        updateNotesPyramid();
        $(document).on('change', '.notes-selector', function() {
            updateNotesPyramid();
        });
    }
    
    function addCustomNote(noteName) {
        $.ajax({
            url: parfumeReviewsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_custom_note',
                nonce: parfumeReviewsAdmin.nonce,
                note_name: noteName
            },
            success: function(response) {
                if (response.success && response.data.term_id) {
                    // Add to select box
                    var $select = $('.notes-selector');
                    var $option = $('<option value="' + response.data.term_id + '" selected>' + noteName + '</option>');
                    $select.append($option).trigger('change');
                    
                    showAdminNotice('Note added successfully', 'success');
                } else {
                    showAdminNotice('Error adding note', 'error');
                }
            }
        });
    }
    
    function updateNotesPyramid() {
        var selectedNotes = $('.notes-selector').val() || [];
        var $preview = $('.notes-pyramid-preview');
        
        if (!$preview.length) return;
        
        if (selectedNotes.length === 0) {
            $preview.html('<p>No notes selected</p>');
            return;
        }
        
        // Divide notes into pyramid levels
        var topNotes = selectedNotes.slice(0, 3);
        var middleNotes = selectedNotes.slice(3, 6);
        var baseNotes = selectedNotes.slice(6);
        
        var html = '<div class="pyramid-preview">';
        
        if (topNotes.length > 0) {
            html += '<div class="level top"><strong>Top:</strong> ' + getNoteNames(topNotes).join(', ') + '</div>';
        }
        
        if (middleNotes.length > 0) {
            html += '<div class="level middle"><strong>Middle:</strong> ' + getNoteNames(middleNotes).join(', ') + '</div>';
        }
        
        if (baseNotes.length > 0) {
            html += '<div class="level base"><strong>Base:</strong> ' + getNoteNames(baseNotes).join(', ') + '</div>';
        }
        
        html += '</div>';
        
        $preview.html(html);
    }
    
    function getNoteNames(noteIds) {
        var names = [];
        var $select = $('.notes-selector');
        
        noteIds.forEach(function(id) {
            var $option = $select.find('option[value="' + id + '"]');
            if ($option.length) {
                names.push($option.text());
            }
        });
        
        return names;
    }
    
    /**
     * Characteristics Meta Box
     */
    function initCharacteristicsMetaBox() {
        // Range sliders
        $('.characteristic-range').each(function() {
            var $slider = $(this);
            var $display = $slider.siblings('.range-display');
            
            $slider.on('input', function() {
                var value = $(this).val();
                var labels = $(this).data('labels');
                
                if (labels && labels[value - 1]) {
                    $display.text(labels[value - 1]);
                } else {
                    $display.text(value);
                }
            });
            
            // Initialize display
            $slider.trigger('input');
        });
        
        // Suitable times checkboxes
        $('.suitable-times input[type="checkbox"]').on('change', function() {
            updateSuitableTimesPreview();
        });
        
        updateSuitableTimesPreview();
    }
    
    function updateSuitableTimesPreview() {
        var selected = [];
        $('.suitable-times input[type="checkbox"]:checked').each(function() {
            selected.push($(this).next('label').text());
        });
        
        var $preview = $('.suitable-times-preview');
        if ($preview.length) {
            $preview.text(selected.length > 0 ? selected.join(', ') : 'None selected');
        }
    }
    
    /**
     * Featured Settings
     */
    function initFeaturedSettings() {
        // Featured checkbox toggle
        $(document).on('change', '#parfume_featured', function() {
            var $orderField = $('.featured-order-field');
            
            if ($(this).is(':checked')) {
                $orderField.slideDown();
            } else {
                $orderField.slideUp();
            }
        });
        
        // Initialize on page load
        $('#parfume_featured').trigger('change');
    }
    
    /**
     * Blog Related Parfumes
     */
    function initBlogRelatedParfumes() {
        if ($('.related-parfumes-selector').length) {
            $('.related-parfumes-selector').select2({
                placeholder: 'Search and select parfumes...',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: parfumeReviewsAdmin.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'search_parfumes',
                            nonce: parfumeReviewsAdmin.nonce,
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.success ? data.data : []
                        };
                    }
                }
            });
        }
    }
    
    /**
     * Media Uploader
     */
    function initMediaUploader() {
        var mediaUploader;
        
        $(document).on('click', '.upload-media-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.media-upload-container');
            var $input = $container.find('.media-id-input');
            var $preview = $container.find('.media-preview');
            var mediaType = $btn.data('media-type') || 'image';
            var multiple = $btn.data('multiple') || false;
            
            // Create media uploader
            mediaUploader = wp.media({
                title: $btn.data('title') || 'Select Media',
                button: {
                    text: $btn.data('button-text') || 'Use this media'
                },
                multiple: multiple,
                library: {
                    type: mediaType
                }
            });
            
            // Media selected
            mediaUploader.on('select', function() {
                var selection = mediaUploader.state().get('selection');
                
                if (multiple) {
                    var ids = [];
                    var previews = [];
                    
                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        ids.push(attachment.id);
                        
                        if (mediaType === 'image') {
                            previews.push('<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="">');
                        } else {
                            previews.push('<div class="media-item">' + attachment.filename + '</div>');
                        }
                    });
                    
                    $input.val(ids.join(','));
                    $preview.html(previews.join(''));
                    
                } else {
                    var attachment = selection.first().toJSON();
                    
                    $input.val(attachment.id);
                    
                    if (mediaType === 'image') {
                        var imageUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                        $preview.html('<img src="' + imageUrl + '" alt="">');
                    } else {
                        $preview.html('<div class="media-item">' + attachment.filename + '</div>');
                    }
                }
                
                $container.find('.remove-media-btn').show();
                $container.addClass('has-media');
            });
            
            mediaUploader.open();
        });
        
        // Remove media
        $(document).on('click', '.remove-media-btn', function(e) {
            e.preventDefault();
            
            var $container = $(this).closest('.media-upload-container');
            var $input = $container.find('.media-id-input');
            var $preview = $container.find('.media-preview');
            
            $input.val('');
            $preview.empty();
            $container.removeClass('has-media');
            $(this).hide();
        });
    }
    
    /**
     * Dynamic Fields
     */
    function initDynamicFields() {
        // Add repeater field
        $(document).on('click', '.add-repeater-btn', function(e) {
            e.preventDefault();
            
            var $container = $(this).closest('.repeater-container');
            var $template = $container.find('.repeater-template');
            var $list = $container.find('.repeater-list');
            var index = $list.find('.repeater-item').length;
            
            var template = $template.html();
            template = template.replace(/{{INDEX}}/g, index);
            
            $list.append('<div class="repeater-item">' + template + '</div>');
            
            // Make sortable if not already
            if (!$list.hasClass('ui-sortable')) {
                $list.sortable({
                    handle: '.repeater-handle',
                    placeholder: 'repeater-placeholder'
                });
            }
        });
        
        // Remove repeater field
        $(document).on('click', '.remove-repeater-btn', function(e) {
            e.preventDefault();
            
            if (confirm('Remove this item?')) {
                $(this).closest('.repeater-item').remove();
            }
        });
    }
    
    /**
     * Sortable Lists
     */
    function initSortableLists() {
        $('.sortable-list').sortable({
            handle: '.sort-handle',
            placeholder: 'sort-placeholder',
            update: function(event, ui) {
                updateSortOrder($(this));
            }
        });
    }
    
    function updateSortOrder($list) {
        $list.find('.sort-order').each(function(index) {
            $(this).val(index);
        });
    }
    
    /**
     * Tab Navigation in Admin
     */
    function initTabNavigation() {
        $(document).on('click', '.admin-tab-nav .nav-tab', function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var $container = $tab.closest('.admin-tabs');
            var target = $tab.data('tab');
            
            // Update active tab
            $container.find('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show target content
            $container.find('.tab-content').hide();
            $container.find('#' + target).show();
            
            // Save active tab in user meta
            if ($container.data('save-state')) {
                $.ajax({
                    url: parfumeReviewsAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_admin_tab_state',
                        nonce: parfumeReviewsAdmin.nonce,
                        tab_group: $container.data('tab-group'),
                        active_tab: target
                    }
                });
            }
        });
        
        // Load saved tab state
        $('.admin-tabs[data-save-state="true"]').each(function() {
            var $container = $(this);
            var tabGroup = $container.data('tab-group');
            
            if (tabGroup) {
                $.ajax({
                    url: parfumeReviewsAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_admin_tab_state',
                        nonce: parfumeReviewsAdmin.nonce,
                        tab_group: tabGroup
                    },
                    success: function(response) {
                        if (response.success && response.data.active_tab) {
                            var $targetTab = $container.find('[data-tab="' + response.data.active_tab + '"]');
                            if ($targetTab.length) {
                                $targetTab.trigger('click');
                            }
                        }
                    }
                });
            }
        });
    }
    
    /**
     * Form Validation
     */
    function initFormValidation() {
        // Real-time validation
        $(document).on('blur', '.validate-required', function() {
            validateField($(this));
        });
        
        $(document).on('blur', '.validate-url', function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (value && !isValidUrl(value)) {
                showFieldError($field, 'Please enter a valid URL');
            } else {
                clearFieldError($field);
            }
        });
        
        $(document).on('blur', '.validate-price', function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (value && !isValidPrice(value)) {
                showFieldError($field, 'Please enter a valid price');
            } else {
                clearFieldError($field);
            }
        });
        
        // Form submission validation
        $(document).on('submit', '.validate-form', function(e) {
            var $form = $(this);
            var isValid = true;
            
            $form.find('.validate-required').each(function() {
                if (!validateField($(this))) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                var $firstError = $form.find('.field-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
                
                showAdminNotice('Please fix validation errors before saving', 'error');
            }
        });
    }
    
    function validateField($field) {
        var value = $field.val().trim();
        var isRequired = $field.hasClass('validate-required');
        
        if (isRequired && !value) {
            showFieldError($field, 'This field is required');
            return false;
        } else {
            clearFieldError($field);
            return true;
        }
    }
    
    function showFieldError($field, message) {
        $field.addClass('field-error');
        
        var $error = $field.siblings('.field-error-message');
        if ($error.length === 0) {
            $error = $('<div class="field-error-message"></div>');
            $field.after($error);
        }
        
        $error.text(message);
    }
    
    function clearFieldError($field) {
        $field.removeClass('field-error');
        $field.siblings('.field-error-message').remove();
    }
    
    /**
     * Dependent Fields
     */
    function initDependentFields() {
        $(document).on('change', '[data-depends-on]', function() {
            updateDependentFields();
        });
        
        // Initialize on page load
        updateDependentFields();
    }
    
    function updateDependentFields() {
        $('[data-depends-on]').each(function() {
            var $field = $(this);
            var dependsOn = $field.data('depends-on');
            var dependsValue = $field.data('depends-value');
            var $trigger = $('#' + dependsOn);
            
            if ($trigger.length) {
                var currentValue = $trigger.val();
                var shouldShow = false;
                
                if (dependsValue) {
                    shouldShow = currentValue === dependsValue;
                } else {
                    shouldShow = Boolean(currentValue);
                }
                
                if (shouldShow) {
                    $field.closest('.form-field, tr').show();
                } else {
                    $field.closest('.form-field, tr').hide();
                }
            }
        });
    }
    
    /**
     * Bulk Actions
     */
    function initBulkActions() {
        // Bulk select all
        $(document).on('change', '.bulk-select-all', function() {
            var isChecked = $(this).is(':checked');
            var $container = $(this).closest('.bulk-actions-container');
            
            $container.find('.bulk-select-item').prop('checked', isChecked);
        });
        
        // Individual item selection
        $(document).on('change', '.bulk-select-item', function() {
            var $container = $(this).closest('.bulk-actions-container');
            var $allCheckbox = $container.find('.bulk-select-all');
            var total = $container.find('.bulk-select-item').length;
            var checked = $container.find('.bulk-select-item:checked').length;
            
            $allCheckbox.prop('indeterminate', checked > 0 && checked < total);
            $allCheckbox.prop('checked', checked === total);
        });
        
        // Bulk action execution
        $(document).on('click', '.execute-bulk-action', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.bulk-actions-container');
            var action = $container.find('.bulk-action-select').val();
            var selected = [];
            
            $container.find('.bulk-select-item:checked').each(function() {
                selected.push($(this).val());
            });
            
            if (!action) {
                alert('Please select an action');
                return;
            }
            
            if (selected.length === 0) {
                alert('Please select items to process');
                return;
            }
            
            if (!confirm('Are you sure you want to perform this action on ' + selected.length + ' items?')) {
                return;
            }
            
            executeBulkAction(action, selected, $btn);
        });
    }
    
    function executeBulkAction(action, items, $btn) {
        var originalText = $btn.text();
        $btn.text('Processing...').prop('disabled', true);
        
        $.ajax({
            url: parfumeReviewsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'parfume_bulk_action',
                nonce: parfumeReviewsAdmin.nonce,
                bulk_action: action,
                items: items
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice(response.data.message || 'Bulk action completed', 'success');
                    
                    // Reload page if needed
                    if (response.data.reload) {
                        window.location.reload();
                    }
                } else {
                    showAdminNotice(response.data || 'Error performing bulk action', 'error');
                }
            },
            error: function() {
                showAdminNotice('Error performing bulk action', 'error');
            },
            complete: function() {
                $btn.text(originalText).prop('disabled', false);
            }
        });
    }
    
    /**
     * Color Pickers
     */
    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }
    }
    
    /**
     * Date Pickers
     */
    function initDatePickers() {
        if ($.fn.datepicker) {
            $('.date-picker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }
    }
    
    /**
     * Code Editors
     */
    function initCodeEditors() {
        if (typeof wp !== 'undefined' && wp.codeEditor) {
            $('.code-editor').each(function() {
                var $textarea = $(this);
                var editorType = $textarea.data('editor-type') || 'text/plain';
                
                wp.codeEditor.initialize($textarea[0], {
                    codemirror: {
                        mode: editorType,
                        lineNumbers: true,
                        theme: 'default'
                    }
                });
            });
        }
    }
    
    /**
     * Taxonomy Meta Fields
     */
    function initTaxonomyMeta() {
        // Brand logo upload
        initBrandLogoUpload();
        
        // Perfumer photo upload
        initPerfumerPhotoUpload();
        
        // Note category selection
        initNoteCategorySelection();
    }
    
    function initBrandLogoUpload() {
        // Similar to media uploader but specific for brand logos
        $(document).on('click', '.upload-brand-logo', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var mediaUploader = wp.media({
                title: 'Select Brand Logo',
                button: { text: 'Use as Logo' },
                multiple: false,
                library: { type: 'image' }
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                $('#brand-image-id').val(attachment.id);
                $('#brand-image-wrapper').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="">');
                $('.remove-brand-logo').show();
            });
            
            mediaUploader.open();
        });
        
        $(document).on('click', '.remove-brand-logo', function(e) {
            e.preventDefault();
            
            $('#brand-image-id').val('');
            $('#brand-image-wrapper').empty();
            $(this).hide();
        });
    }
    
    function initPerfumerPhotoUpload() {
        // Similar implementation for perfumer photos
        $(document).on('click', '.upload-perfumer-photo', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var mediaUploader = wp.media({
                title: 'Select Perfumer Photo',
                button: { text: 'Use as Photo' },
                multiple: false,
                library: { type: 'image' }
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                $('#perfumer-image-id').val(attachment.id);
                $('#perfumer-image-wrapper').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="">');
                $('.remove-perfumer-photo').show();
            });
            
            mediaUploader.open();
        });
        
        $(document).on('click', '.remove-perfumer-photo', function(e) {
            e.preventDefault();
            
            $('#perfumer-image-id').val('');
            $('#perfumer-image-wrapper').empty();
            $(this).hide();
        });
    }
    
    function initNoteCategorySelection() {
        $(document).on('change', '.note-category-select', function() {
            var category = $(this).val();
            var $iconField = $('.note-icon-field');
            
            // Show/hide icon field based on category
            if (category && category !== 'other') {
                $iconField.show();
            } else {
                $iconField.hide();
            }
        });
    }
    
    /**
     * Import/Export Functionality
     */
    function initImportExport() {
        // File upload validation
        $(document).on('change', 'input[name="parfume_import_file"]', function() {
            var file = this.files[0];
            var $container = $(this).closest('.import-section');
            
            if (file) {
                // Validate file type
                if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
                    showImportError($container, 'Please select a valid JSON file');
                    $(this).val('');
                    return;
                }
                
                // Validate file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    showImportError($container, 'File is too large. Maximum size is 10MB');
                    $(this).val('');
                    return;
                }
                
                // Show file info
                showImportInfo($container, file);
            }
        });
        
        // Import form submission
        $(document).on('submit', '.import-form', function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('[type="submit"]');
            var file = $form.find('input[type="file"]')[0].files[0];
            
            if (!file) {
                e.preventDefault();
                alert('Please select a file to import');
                return;
            }
            
            // Show loading state
            $submitBtn.val('Importing...').prop('disabled', true);
            
            // Form will submit normally, but we show progress
            setTimeout(function() {
                showImportProgress();
            }, 100);
        });
        
        // Export functionality
        $(document).on('click', '.export-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.text('Exporting...').prop('disabled', true);
            
            // Trigger export
            window.location.href = $btn.attr('href');
            
            // Reset button after delay
            setTimeout(function() {
                $btn.text(originalText).prop('disabled', false);
            }, 3000);
        });
    }
    
    function showImportError($container, message) {
        var $error = $container.find('.import-error');
        if ($error.length === 0) {
            $error = $('<div class="import-error" style="color: red; margin-top: 10px;"></div>');
            $container.append($error);
        }
        
        $error.text(message);
    }
    
    function showImportInfo($container, file) {
        var $info = $container.find('.import-info');
        if ($info.length === 0) {
            $info = $('<div class="import-info" style="color: green; margin-top: 10px;"></div>');
            $container.append($info);
        }
        
        var size = Math.round(file.size / 1024);
        $info.text('Selected: ' + file.name + ' (' + size + ' KB)');
        
        // Clear any previous errors
        $container.find('.import-error').remove();
    }
    
    function showImportProgress() {
        var $progress = $('<div class="import-progress" style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 5px;"><div class="progress-bar" style="width: 0%; height: 20px; background: #0073aa; border-radius: 10px; transition: width 0.3s;"></div><p style="margin-top: 10px;">Importing... Please wait.</p></div>');
        
        $('.import-section').append($progress);
        
        // Animate progress bar
        var width = 0;
        var interval = setInterval(function() {
            width += Math.random() * 10;
            if (width > 90) width = 90;
            
            $progress.find('.progress-bar').css('width', width + '%');
        }, 500);
        
        // Clear interval when page reloads/changes
        $(window).on('beforeunload', function() {
            clearInterval(interval);
        });
    }
    
    /**
     * Product Scraper Admin
     */
    function initProductScraper() {
        // Test scraper URL
        $(document).on('click', '.test-scraper-url', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var url = $('#test-url').val().trim();
            
            if (!url) {
                alert('Please enter a URL to test');
                return;
            }
            
            if (!isValidUrl(url)) {
                alert('Please enter a valid URL');
                return;
            }
            
            testScraperUrl(url, $btn);
        });
        
        // Manual scrape all
        $(document).on('click', '.manual-scrape-all', function(e) {
            e.preventDefault();
            
            if (!confirm('This will scrape all products with store URLs. Continue?')) {
                return;
            }
            
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.text('Scraping...').prop('disabled', true);
            
            $.ajax({
                url: parfumeReviewsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'manual_scrape_all_products',
                    nonce: parfumeReviewsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showAdminNotice('Scraping initiated for ' + response.data.count + ' products', 'success');
                    } else {
                        showAdminNotice(response.data || 'Error initiating scraping', 'error');
                    }
                },
                error: function() {
                    showAdminNotice('Error initiating scraping', 'error');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
    }
    
    function testScraperUrl(url, $btn) {
        var originalText = $btn.text();
        $btn.text('Testing...').prop('disabled', true);
        
        var $results = $('#test-results');
        $results.html('<div class="spinner is-active"></div><p>Testing URL...</p>');
        
        $.ajax({
            url: parfumeReviewsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'test_scraper_url',
                nonce: parfumeReviewsAdmin.nonce,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    displayTestResults($results, response.data);
                } else {
                    $results.html('<div class="error">Error: ' + (response.data || 'Unknown error') + '</div>');
                }
            },
            error: function() {
                $results.html('<div class="error">AJAX Error</div>');
            },
            complete: function() {
                $btn.text(originalText).prop('disabled', false);
            }
        });
    }
    
    function displayTestResults($container, data) {
        var html = '<div class="test-results-success">';
        
        html += '<h4>Scraping Results:</h4>';
        
        if (data.title) {
            html += '<p><strong>Page Title:</strong> ' + data.title + '</p>';
        }
        
        if (data.potential_prices && data.potential_prices.length > 0) {
            html += '<h5>Potential Prices Found:</h5><ul>';
            data.potential_prices.forEach(function(price) {
                html += '<li><strong>' + price.text + '</strong><br>';
                html += 'Selector: <code>' + price.selector + '</code></li>';
            });
            html += '</ul>';
        }
        
        if (data.potential_availability && data.potential_availability.length > 0) {
            html += '<h5>Potential Availability:</h5><ul>';
            data.potential_availability.forEach(function(avail) {
                html += '<li><strong>' + avail.text + '</strong><br>';
                html += 'Selector: <code>' + avail.selector + '</code></li>';
            });
            html += '</ul>';
        }
        
        if (!data.potential_prices || data.potential_prices.length === 0) {
            html += '<p style="color: orange;">No prices detected. The site may require JavaScript or use dynamic loading.</p>';
        }
        
        html += '</div>';
        
        $container.html(html);
    }
    
    /**
     * Utility Functions
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    function isValidPrice(price) {
        return /^\d+(\.\d{1,2})?$/.test(price);
    }
    
    function showAdminNotice(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
        
        // Handle dismiss button
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut();
        });
    }
    
    /**
     * Auto-save functionality
     */
    function initAutoSave() {
        var autoSaveTimeout;
        var $form = $('#post');
        
        if ($form.length && $form.find('#auto_draft').val() !== '1') {
            $(document).on('input change', '#post input, #post select, #post textarea', function() {
                clearTimeout(autoSaveTimeout);
                
                autoSaveTimeout = setTimeout(function() {
                    wp.autosave && wp.autosave.server.triggerSave();
                }, 10000); // Auto-save after 10 seconds of inactivity
            });
        }
    }
    
    // Initialize auto-save
    initAutoSave();
    
})(jQuery);