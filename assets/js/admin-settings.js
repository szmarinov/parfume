/**
 * Admin Settings JavaScript
 * 
 * Handles interactivity on settings page
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Tab navigation with hash
        initTabNavigation();
        
        // URL preview
        initURLPreviews();
        
        // Conditional fields
        initConditionalFields();
        
        // Scraper status indicator
        initScraperStatus();
        
        // Comparison slider
        initComparisonSlider();
        
        // Confirm dangerous actions
        initDangerousActionConfirm();
    });
    
    /**
     * Tab navigation
     */
    function initTabNavigation() {
        // Add data attribute to tabs
        $('.nav-tab').each(function() {
            var href = $(this).attr('href');
            if (href) {
                var tab = href.split('tab=')[1];
                if (tab) {
                    $(this).attr('data-tab', tab);
                }
            }
        });
        
        // Handle hash change
        $(window).on('hashchange', function() {
            var hash = window.location.hash;
            if (hash) {
                var tab = hash.replace('#', '');
                $('.nav-tab[data-tab="' + tab + '"]').click();
            }
        });
        
        // Click handler
        $('.nav-tab').on('click', function(e) {
            var tab = $(this).attr('data-tab');
            if (tab) {
                window.location.hash = tab;
            }
        });
    }
    
    /**
     * URL Preview
     */
    function initURLPreviews() {
        // Watch parfume slug changes
        $('input[name="parfume_reviews_settings[parfume_slug]"]').on('input', function() {
            updateURLPreviews();
        });
        
        // Watch taxonomy slug changes
        $('input[name^="parfume_reviews_settings"][name$="_slug]"]').on('input', function() {
            updateURLPreviews();
        });
        
        updateURLPreviews();
    }
    
    /**
     * Update URL previews
     */
    function updateURLPreviews() {
        var baseURL = window.location.origin;
        var parfumeSlug = $('input[name="parfume_reviews_settings[parfume_slug]"]').val() || 'parfiumi';
        
        // Remove existing previews
        $('.url-preview').remove();
        
        // Add parfume URL preview
        var parfumePreview = $('<div class="url-preview">' +
            '<div class="url-preview-label">Пример URL за парфюм:</div>' +
            baseURL + '/' + parfumeSlug + '/chanel-no-5/' +
            '</div>');
        
        $('input[name="parfume_reviews_settings[parfume_slug]"]').closest('td').append(parfumePreview);
        
        // Add taxonomy URL previews
        $('input[name^="parfume_reviews_settings"][name$="_slug]"]').not('[name="parfume_reviews_settings[parfume_slug]"]').each(function() {
            var slug = $(this).val();
            var fieldName = $(this).attr('name').match(/\[([^\]]+)\]/)[1];
            
            if (slug && fieldName !== 'parfume_slug') {
                var preview = $('<div class="url-preview">' +
                    '<div class="url-preview-label">Пример URL:</div>' +
                    baseURL + '/' + parfumeSlug + '/' + slug + '/term-name/' +
                    '</div>');
                
                $(this).closest('td').append(preview);
            }
        });
    }
    
    /**
     * Conditional Fields
     */
    function initConditionalFields() {
        // Scraper auto-update
        var scraperCheckbox = $('input[name="parfume_reviews_settings[enable_scraper]"]');
        var autoUpdateRow = $('input[name="parfume_reviews_settings[auto_update_prices]"]').closest('tr');
        var intervalRow = $('input[name="parfume_reviews_settings[update_interval]"]').closest('tr');
        
        function toggleScraperFields() {
            if (scraperCheckbox.is(':checked')) {
                autoUpdateRow.show();
                toggleIntervalField();
            } else {
                autoUpdateRow.hide();
                intervalRow.hide();
            }
        }
        
        function toggleIntervalField() {
            var autoUpdate = $('input[name="parfume_reviews_settings[auto_update_prices]"]');
            if (autoUpdate.is(':checked')) {
                intervalRow.show();
            } else {
                intervalRow.hide();
            }
        }
        
        scraperCheckbox.on('change', toggleScraperFields);
        $('input[name="parfume_reviews_settings[auto_update_prices]"]').on('change', toggleIntervalField);
        
        toggleScraperFields();
        
        // Comparison enable
        var comparisonCheckbox = $('input[name="parfume_reviews_settings[enable_comparison]"]');
        var comparisonFields = comparisonCheckbox.closest('table').find('tr').not(comparisonCheckbox.closest('tr'));
        
        function toggleComparisonFields() {
            if (comparisonCheckbox.is(':checked')) {
                comparisonFields.show();
            } else {
                comparisonFields.hide();
            }
        }
        
        comparisonCheckbox.on('change', toggleComparisonFields);
        toggleComparisonFields();
    }
    
    /**
     * Scraper Status Indicator
     */
    function initScraperStatus() {
        var scraperCheckbox = $('input[name="parfume_reviews_settings[enable_scraper]"]');
        var statusContainer = scraperCheckbox.closest('td');
        
        function updateStatus() {
            $('.scraper-status').remove();
            
            var status = scraperCheckbox.is(':checked') ? 'enabled' : 'disabled';
            var text = scraperCheckbox.is(':checked') ? 'Активен' : 'Неактивен';
            
            var statusHTML = '<div class="scraper-status ' + status + '">' + text + '</div>';
            statusContainer.append(statusHTML);
        }
        
        scraperCheckbox.on('change', updateStatus);
        updateStatus();
    }
    
    /**
     * Comparison Slider
     */
    function initComparisonSlider() {
        var slider = $('input[name="parfume_reviews_settings[max_comparison_items]"]');
        
        if (slider.length && slider.attr('type') === 'number') {
            var value = slider.val();
            var container = slider.closest('td');
            
            // Create visual indicator
            var indicator = $('<span class="comparison-max-items-value">' + value + '</span>');
            container.find('.description').before(indicator);
            
            slider.on('input change', function() {
                indicator.text($(this).val());
            });
        }
    }
    
    /**
     * Confirm Dangerous Actions
     */
    function initDangerousActionConfirm() {
        var deleteCheckbox = $('input[name="parfume_reviews_settings[delete_data_on_uninstall]"]');
        
        if (deleteCheckbox.length) {
            // Add warning
            var warning = $('<div class="setting-danger">' +
                'ВНИМАНИЕ: Това действие е необратимо! Всички парфюми, таксономии и настройки ще бъдат изтрити завинаги.' +
                '</div>');
            
            deleteCheckbox.closest('td').append(warning);
            warning.hide();
            
            // Show/hide warning
            deleteCheckbox.on('change', function() {
                if ($(this).is(':checked')) {
                    warning.slideDown();
                    
                    // Confirm
                    var confirmed = confirm('Наистина ли искате да активирате изтриване на данните при деинсталация?\n\n' +
                        'Това ще ИЗТРИЕ ЗАВИНАГИ всички парфюми, таксономии и настройки когато изтриете плъгина!');
                    
                    if (!confirmed) {
                        $(this).prop('checked', false);
                        warning.slideUp();
                    }
                } else {
                    warning.slideUp();
                }
            });
            
            // Initial state
            if (deleteCheckbox.is(':checked')) {
                warning.show();
            }
        }
    }
    
})(jQuery);