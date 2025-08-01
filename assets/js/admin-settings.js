/**
 * Parfume Reviews Admin Settings JavaScript - FIXED VERSION
 * assets/js/admin-settings.js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initTabs();
        initFormEnhancements();
        initValidation();
        initTooltips();
    });

    /**
     * Initialize tab functionality
     * FIXED: Updated selectors to work with WordPress nav-tab-wrapper
     */
    function initTabs() {
        // Handle tab clicks - FIXED: Updated selector to use .nav-tab-wrapper
        $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const $clickedTab = $(this);
            const targetTabId = $clickedTab.attr('href').replace('#', '');
            
            // Update active tab visually - FIXED: Updated selector
            $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
            $clickedTab.addClass('nav-tab-active');
            
            // Hide all tab content with fade effect
            $('.tab-content').hide().removeClass('tab-content-active');
            
            // Show target tab content with fade effect
            $('#' + targetTabId).addClass('tab-content-active').show();
            
            // Update URL hash without triggering scroll
            if (history.pushState) {
                history.pushState(null, null, '#' + targetTabId);
            }
            
            // Trigger custom event for other scripts
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
     * Initialize form enhancements
     */
    function initFormEnhancements() {
        // Add loading state to form submission
        $('form').on('submit', function() {
            const $form = $(this);
            const $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
            
            $submitBtn.addClass('updating-message').prop('disabled', true);
            $form.addClass('settings-loading');
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
            const $btn = $(this);
            const section = $btn.data('section');
            
            $.post(ajaxurl, {
                action: 'parfume_reset_settings',
                section: section,
                nonce: parfumeSettings.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Грешка при нулиране на настройките: ' + response.data);
                }
            });
        }
    });

    /**
     * Handle dynamic store management
     */
    $(document).on('click', '.add-store', function(e) {
        e.preventDefault();
        // Store management functionality would go here
    });

    /**
     * Handle scraper queue management
     */
    $(document).on('click', '#run-scraper-now', function(e) {
        e.preventDefault();
        const $btn = $(this);
        
        $btn.prop('disabled', true).text('Стартира...');
        
        $.post(ajaxurl, {
            action: 'parfume_run_scraper_now',
            nonce: parfumeSettings.nonce
        }, function(response) {
            if (response.success) {
                alert('Scraper е стартиран успешно.');
                location.reload();
            } else {
                alert('Грешка при стартиране на scraper: ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Стартирай скрейпване сега');
        });
    });

    /**
     * Auto-refresh for dynamic content
     */
    function setupAutoRefresh() {
        // Refresh scraper queue status every 30 seconds if on scraper tab
        setInterval(function() {
            if ($('#product-scraper').hasClass('tab-content-active')) {
                const $queueStatus = $('#scraper-queue-status');
                if ($queueStatus.length) {
                    $.post(ajaxurl, {
                        action: 'parfume_get_queue_status',
                        nonce: parfumeSettings.nonce
                    }, function(response) {
                        if (response.success) {
                            $queueStatus.html(response.data);
                        }
                    });
                }
            }
        }, 30000);
    }

    // Initialize auto-refresh
    setupAutoRefresh();

    /**
     * Handle shortcode generation
     */
    $(document).on('change', '#shortcode-type, #shortcode-count, #shortcode-columns, #shortcode-orderby', function() {
        generateShortcode();
    });

    function generateShortcode() {
        const type = $('#shortcode-type').val();
        const count = $('#shortcode-count').val();
        const columns = $('#shortcode-columns').val();
        const orderby = $('#shortcode-orderby').val();
        
        if (!type) {
            $('#generated-shortcode').val('');
            return;
        }
        
        let shortcode = '[' + type;
        
        if (count && count !== '6') {
            shortcode += ' count="' + count + '"';
        }
        
        if (columns && columns !== '3') {
            shortcode += ' columns="' + columns + '"';
        }
        
        if (orderby && orderby !== 'date') {
            shortcode += ' orderby="' + orderby + '"';
        }
        
        shortcode += ']';
        
        $('#generated-shortcode').val(shortcode);
    }

    // Global functions for shortcode copying
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const $message = $('<div class="notice notice-success is-dismissible"><p>Shortcode копиран в клипборда!</p></div>');
            $('.wrap').prepend($message);
            setTimeout(function() {
                $message.fadeOut();
            }, 3000);
        }, function(err) {
            // Fallback for older browsers
            const textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                alert('Shortcode копиран в клипборда!');
            } catch (err) {
                alert('Грешка при копиране. Моля копирайте ръчно.');
            }
            document.body.removeChild(textArea);
        });
    };

    window.copyGeneratedShortcode = function() {
        const shortcode = document.getElementById('generated-shortcode').value;
        if (shortcode) {
            copyToClipboard(shortcode);
        } else {
            alert('Първо генерирайте shortcode.');
        }
    };

})(jQuery);