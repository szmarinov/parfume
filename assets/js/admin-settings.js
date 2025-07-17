/**
 * Enhanced Admin Settings JavaScript for Parfume Reviews Plugin
 * FIXED VERSION: Поправен submit button и добавена search функционалност
 */
jQuery(document).ready(function($) {
    'use strict';

    // Initialize all functionality
    initTabs();
    initFormEnhancements();
    initValidation();
    initTooltips();
    initSelect2();

    /**
     * Initialize tab functionality - FIXED: Improved selectors
     */
    function initTabs() {
        // Tab switching
        $('.parfume-settings-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const $clickedTab = $(this);
            const targetTabId = $clickedTab.attr('href').substring(1);
            
            // Update active tab
            $('.parfume-settings-tabs .nav-tab').removeClass('nav-tab-active');
            $clickedTab.addClass('nav-tab-active');
            
            // Update active content
            $('.tab-content').removeClass('tab-content-active').hide();
            $('#' + targetTabId).addClass('tab-content-active').show();
            
            // Update URL hash without jumping
            if (history.pushState) {
                history.pushState(null, null, '#' + targetTabId);
            } else {
                location.hash = '#' + targetTabId;
            }
            
            // Custom event for other scripts
            $(document).trigger('parfume-tab-changed', [targetTabId]);
            
            // Focus management for accessibility
            setTimeout(function() {
                $('#' + targetTabId).find('input, select, textarea').first().focus();
            }, 100);
        });
        
        // Handle browser back/forward - FIXED: Updated selector
        $(window).on('popstate', function() {
            const hash = window.location.hash;
            if (hash) {
                const tabId = hash.replace('#', '');
                if ($('#' + tabId).length) {
                    $('.nav-tab-wrapper .nav-tab[href="' + hash + '"]').trigger('click');
                }
            } else {
                // Default to first tab if no hash
                $('.nav-tab-wrapper .nav-tab').first().trigger('click');
            }
        });
        
        // Initialize from URL hash on page load or default to first tab - FIXED: Updated selector
        const initialHash = window.location.hash;
        if (initialHash && $(initialHash).length) {
            $('.nav-tab-wrapper .nav-tab[href="' + initialHash + '"]').trigger('click');
        } else {
            // Show first tab by default
            $('.nav-tab-wrapper .nav-tab').first().trigger('click');
        }
    }

    /**
     * Initialize form enhancements - FIXED: Proper submit handling
     */
    function initFormEnhancements() {
        // FIXED: Form submission handling with proper selectors
        $('#parfume-settings-form').on('submit', function(e) {
            const $form = $(this);
            const $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
            
            // Basic validation before submission
            let hasErrors = false;
            
            // Check required fields
            $form.find('input[required], select[required], textarea[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('invalid');
                    hasErrors = true;
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('Моля попълнете всички задължителни полета.');
                return false;
            }
            
            // Add loading state
            $submitBtn.addClass('updating-message').prop('disabled', true);
            $form.addClass('settings-loading');
            
            // Show success message after form submission
            setTimeout(function() {
                $submitBtn.removeClass('updating-message').prop('disabled', false);
                $form.removeClass('settings-loading');
            }, 2000);
        });
        
        // Enhanced file input styling
        $('input[type="file"]').on('change', function() {
            const $input = $(this);
            const fileName = $input.val().split('\\').pop();
            const $label = $input.siblings('label, .file-label');
            
            if (fileName) {
                $label.text(fileName);
            }
        });
        
        // Auto-save draft functionality for large forms
        let autoSaveTimeout;
        $('.tab-content input, .tab-content select, .tab-content textarea').on('input change', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                // Could implement auto-save here if needed
                console.log('Auto-save triggered');
            }, 2000);
        });
    }

    /**
     * Initialize form validation
     */
    function initValidation() {
        // URL slug validation
        $('input[name*="_slug"]').on('input', function() {
            const $input = $(this);
            let value = $input.val();
            
            // Convert to valid slug format
            value = value.toLowerCase()
                        .replace(/[^\w\-]/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
            
            if (value !== $input.val()) {
                $input.val(value);
            }
            
            // Visual feedback
            if (value.length > 0 && value.match(/^[a-z0-9\-]+$/)) {
                $input.removeClass('invalid').addClass('valid');
            } else {
                $input.removeClass('valid').addClass('invalid');
            }
        });
        
        // Number input validation
        $('input[type="number"]').on('input', function() {
            const $input = $(this);
            const min = parseInt($input.attr('min')) || 0;
            const max = parseInt($input.attr('max')) || Infinity;
            const value = parseInt($input.val()) || 0;
            
            if (value < min || value > max) {
                $input.addClass('invalid');
            } else {
                $input.removeClass('invalid');
            }
        });
        
        // Required field validation
        $('input[required], select[required], textarea[required]').on('blur', function() {
            const $input = $(this);
            if (!$input.val().trim()) {
                $input.addClass('invalid');
            } else {
                $input.removeClass('invalid');
            }
        });
    }

    /**
     * Initialize tooltips and help text
     */
    function initTooltips() {
        // Add help tooltips for complex settings
        const helpTexts = {
            'parfume_slug': 'Промяната изисква обновяване на permalink структурата.',
            'posts_per_page': 'Определя колко парфюма се показват на една страница в архива.',
            'scraper_frequency': 'Колко често системата проверява за промени в цените от външните магазини.',
            'mobile_z_index': 'CSS z-index стойност за mobile panel. По-висока стойност означава, че панелът ще се показва над други елементи.',
            'debug_mode': 'Включва допълнителна информация за debugging в browser console и WordPress debug log.',
            'backup_frequency': 'Как често да се създават автоматични backup файлове на настройките и данните.'
        };
        
        $.each(helpTexts, function(fieldName, helpText) {
            const $field = $('input[name*="' + fieldName + '"], select[name*="' + fieldName + '"]');
            if ($field.length) {
                const $helpIcon = $('<span class="help-icon dashicons dashicons-editor-help" title="' + helpText + '"></span>');
                $field.after($helpIcon);
                
                $helpIcon.on('click', function() {
                    alert(helpText);
                });
            }
        });
    }

    /**
     * NEW: Initialize Select2 for enhanced search functionality
     */
    function initSelect2() {
        // Check if Select2 is available
        if (typeof $.fn.select2 === 'undefined') {
            // Load Select2 from CDN if not available
            const select2CSS = document.createElement('link');
            select2CSS.rel = 'stylesheet';
            select2CSS.href = 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css';
            document.head.appendChild(select2CSS);
            
            const select2JS = document.createElement('script');
            select2JS.src = 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js';
            select2JS.onload = function() {
                initializeSelect2Elements();
            };
            document.head.appendChild(select2JS);
        } else {
            initializeSelect2Elements();
        }
    }

    /**
     * Initialize Select2 elements
     */
    function initializeSelect2Elements() {
        // Initialize Select2 for perfume and brand selectors with search
        $('select[data-field*="perfumes"], select[data-field*="brands"]').each(function() {
            const $select = $(this);
            
            if (!$select.hasClass('select2-hidden-accessible')) {
                $select.select2({
                    placeholder: 'Търсете...',
                    allowClear: true,
                    width: '100%',
                    language: {
                        searching: function() {
                            return 'Търсене...';
                        },
                        noResults: function() {
                            return 'Няма намерени резултати';
                        },
                        inputTooShort: function() {
                            return 'Въведете поне 1 символ за търсене';
                        }
                    },
                    minimumInputLength: 1
                });
            }
        });
    }

    /**
     * Handle view archive button clicks
     */
    $(document).on('click', '.view-archive-btn', function(e) {
        const $btn = $(this);
        
        // Add loading state
        $btn.addClass('loading');
        
        // Track click for analytics (if needed)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'view_archive', {
                'event_category': 'admin_settings',
                'event_label': $btn.attr('href')
            });
        }
        
        // Remove loading state after a short delay
        setTimeout(function() {
            $btn.removeClass('loading');
        }, 1000);
    });

    /**
     * Handle import form submission with progress
     */
    $(document).on('submit', 'form[action*="parfume_import"]', function(e) {
        const $form = $(this);
        const $submitBtn = $form.find('input[type="submit"]');
        const $fileInput = $form.find('input[type="file"]');
        
        if (!$fileInput.val()) {
            e.preventDefault();
            alert('Моля изберете файл за импорт.');
            return;
        }
        
        // Show progress
        $submitBtn.val('Импортиране...').prop('disabled', true);
        
        // Add progress bar if needed
        if (!$form.find('.progress-bar').length) {
            $form.append('<div class="progress-bar"><div class="progress-fill"></div></div>');
        }
    });

    /**
     * Handle settings reset functionality
     */
    $(document).on('click', '.reset-settings', function(e) {
        e.preventDefault();
        
        if (confirm('Сигурни ли сте, че искате да нулирате настройките? Тази операция не може да бъде отменена.')) {
            // Add reset functionality here
            window.location.reload();
        }
    });

    /**
     * Handle cleanup buttons
     */
    $(document).on('click', '.cleanup-btn', function(e) {
        e.preventDefault();
        
        const action = $(this).data('action');
        const $btn = $(this);
        
        if (confirm('Сигурни ли сте, че искате да изпълните тази операция?')) {
            $btn.prop('disabled', true).text('Изпълнява се...');
            
            // AJAX call for cleanup action
            $.post(ajaxurl, {
                action: 'parfume_' + action,
                nonce: parfumeSettings.nonce
            }, function(response) {
                if (response.success) {
                    alert('Операцията беше изпълнена успешно.');
                } else {
                    alert('Възникна грешка: ' + response.data);
                }
                
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Изпълни');
            }).fail(function() {
                alert('Възникна грешка при свързването със сървъра.');
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Изпълни');
            });
        }
    });

    /**
     * Store original button text for cleanup buttons
     */
    $('.cleanup-btn').each(function() {
        $(this).data('original-text', $(this).text());
    });

    /**
     * Advanced form handling for complex fields
     */
    
    // Handle dynamic form fields
    $(document).on('click', '.add-dynamic-field', function(e) {
        e.preventDefault();
        
        const template = $(this).siblings('.field-template').html();
        const container = $(this).siblings('.dynamic-fields-container');
        const index = container.children().length;
        
        const newField = template.replace(/\[INDEX\]/g, index);
        container.append(newField);
    });
    
    // Remove dynamic fields
    $(document).on('click', '.remove-dynamic-field', function(e) {
        e.preventDefault();
        $(this).closest('.dynamic-field-item').remove();
    });

    /**
     * Real-time preview functionality
     */
    $('input[name*="color"], input[name*="font_size"]').on('input', function() {
        const $input = $(this);
        const previewTarget = $input.data('preview');
        
        if (previewTarget) {
            const value = $input.val();
            $(previewTarget).css($input.data('css-property'), value);
        }
    });

    /**
     * Conditional field display
     */
    $('input[type="checkbox"][data-toggle]').on('change', function() {
        const $checkbox = $(this);
        const target = $checkbox.data('toggle');
        const $target = $(target);
        
        if ($checkbox.is(':checked')) {
            $target.slideDown();
        } else {
            $target.slideUp();
        }
    }).trigger('change'); // Initialize state

    /**
     * Copy to clipboard functionality
     */
    $(document).on('click', '.copy-to-clipboard', function(e) {
        e.preventDefault();
        
        const text = $(this).data('text') || $(this).attr('href');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Копирано в clipboard!');
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Копирано в clipboard!');
        }
    });

    /**
     * Keyboard shortcuts
     */
    $(document).on('keydown', function(e) {
        // Ctrl+S to save settings
        if (e.ctrlKey && e.which === 83) {
            e.preventDefault();
            $('#parfume-settings-form').submit();
        }
        
        // Ctrl+R to reset current tab
        if (e.ctrlKey && e.which === 82) {
            e.preventDefault();
            if (confirm('Нулиране на настройките в текущия таб?')) {
                $('.tab-content-active').find('input, select, textarea').val('');
            }
        }
    });

    /**
     * Form change tracking
     */
    let formChanged = false;
    
    $('#parfume-settings-form input, #parfume-settings-form select, #parfume-settings-form textarea').on('change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return 'Имате незапазени промени. Сигурни ли сте, че искате да напуснете страницата?';
        }
    });
    
    $('#parfume-settings-form').on('submit', function() {
        formChanged = false;
    });

    /**
     * Accessibility improvements
     */
    
    // Add ARIA labels and roles
    $('.nav-tab').attr('role', 'tab');
    $('.tab-content').attr('role', 'tabpanel');
    
    // Keyboard navigation for tabs
    $('.nav-tab').on('keydown', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter or Space
            e.preventDefault();
            $(this).click();
        }
    });

    /**
     * Debug information toggle
     */
    $(document).on('click', '.toggle-debug-info', function(e) {
        e.preventDefault();
        
        const targetId = $(this).data('target');
        const $target = $('#' + targetId);
        
        if ($target.is(':visible')) {
            $target.slideUp();
            $(this).text('Покажи информация');
        } else {
            $target.slideDown();
            $(this).text('Скрий информация');
        }
    });

    // Initialize debug toggles
    $('.debug-info-content').hide();

    /**
     * Success/error message handling
     */
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut();
    }, 5000);

    // Console debug info
    if (typeof parfumeSettings !== 'undefined' && parfumeSettings.debug) {
        console.log('Parfume Reviews Admin Settings loaded successfully');
    }
});