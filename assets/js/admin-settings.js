/**
 * Parfume Reviews Admin Settings JavaScript
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
     */
    function initTabs() {
        // Handle tab clicks
        $('.parfume-settings-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const $clickedTab = $(this);
            const targetTabId = $clickedTab.attr('href').replace('#', '');
            
            // Update active tab visually
            $('.parfume-settings-tabs .nav-tab').removeClass('nav-tab-active');
            $clickedTab.addClass('nav-tab-active');
            
            // Hide all tab content with fade effect
            $('.tab-content').removeClass('tab-content-active').fadeOut(200, function() {
                // Show target tab content with fade effect after previous content is hidden
                $('#' + targetTabId).addClass('tab-content-active').fadeIn(300, function() {
                    // Update URL hash without triggering scroll
                    if (history.pushState) {
                        history.pushState(null, null, '#' + targetTabId);
                    }
                    
                    // Trigger custom event for other scripts
                    $(document).trigger('parfume-tab-changed', [targetTabId]);
                });
                
                // Focus management for accessibility
                setTimeout(function() {
                    $('#' + targetTabId).find('input, select, textarea').first().focus();
                }, 300);
            });
        });
        
        // Handle browser back/forward
        $(window).on('popstate', function() {
            const hash = window.location.hash;
            if (hash) {
                const tabId = hash.replace('#', '');
                if ($('#' + tabId).length) {
                    $('.parfume-settings-tabs .nav-tab[href="' + hash + '"]').trigger('click');
                }
            } else {
                // Default to first tab if no hash
                $('.parfume-settings-tabs .nav-tab').first().trigger('click');
            }
        });
        
        // Initialize from URL hash on page load or default to first tab
        const initialHash = window.location.hash;
        if (initialHash && $(initialHash).length) {
            $('.parfume-settings-tabs .nav-tab[href="' + initialHash + '"]').trigger('click');
        } else {
            // Show first tab by default
            $('.parfume-settings-tabs .nav-tab').first().trigger('click');
        }
    }

    /**
     * Initialize form enhancements
     */
    function initFormEnhancements() {
        // Add loading state to form submission
        $('form').on('submit', function() {
            const $form = $(this);
            const $submitBtn = $form.find('.button-primary');
            
            $submitBtn.prop('disabled', true)
                     .text('Запазване...')
                     .addClass('updating-message');
            
            $form.addClass('settings-loading');
        });
        
        // Auto-save indication for certain fields
        $('.auto-save input, .auto-save select, .auto-save textarea').on('change', function() {
            const $field = $(this);
            const $indicator = $('<span class="save-indicator">Промяна направена</span>');
            
            $field.parent().find('.save-indicator').remove();
            $field.parent().append($indicator);
            
            setTimeout(function() {
                $indicator.fadeOut(function() {
                    $indicator.remove();
                });
            }, 2000);
        });
    }

    /**
     * Initialize form validation
     */
    function initValidation() {
        // Slug validation
        $('input[name*="_slug"]').on('input', function() {
            const $input = $(this);
            let value = $input.val();
            
            // Convert to valid slug
            value = value.toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove special chars
                        .replace(/[\s_-]+/g, '-') // Replace spaces/underscores with hyphens
                        .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
            
            if (value !== $input.val()) {
                $input.val(value);
                showValidationMessage($input, 'Slug-ът е автоматично форматиран', 'info');
            }
            
            // Check for empty slug
            if (value === '') {
                showValidationMessage($input, 'Slug-ът не може да бъде празен', 'error');
            } else {
                clearValidationMessage($input);
            }
        });
        
        // Numeric validation
        $('input[type="number"]').on('input', function() {
            const $input = $(this);
            const value = parseInt($input.val());
            const min = parseInt($input.attr('min')) || 0;
            const max = parseInt($input.attr('max')) || 999;
            
            if (isNaN(value) || value < min || value > max) {
                showValidationMessage($input, `Стойността трябва да бъде между ${min} и ${max}`, 'error');
            } else {
                clearValidationMessage($input);
            }
        });
        
        // File upload validation
        $('input[type="file"]').on('change', function() {
            const $input = $(this);
            const file = this.files[0];
            
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['application/json'];
                
                if (file.size > maxSize) {
                    showValidationMessage($input, 'Файлът е твърде голям (макс. 10MB)', 'error');
                    $input.val('');
                } else if (!allowedTypes.includes(file.type) && !file.name.endsWith('.json')) {
                    showValidationMessage($input, 'Позволени са само JSON файлове', 'error');
                    $input.val('');
                } else {
                    showValidationMessage($input, 'Файлът е валиден', 'success');
                }
            }
        });
    }

    /**
     * Show validation message
     */
    function showValidationMessage($field, message, type) {
        clearValidationMessage($field);
        
        const $message = $('<div class="validation-message validation-' + type + '">' + message + '</div>');
        $field.after($message);
        
        // Auto-hide success and info messages
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 3000);
        }
    }

    /**
     * Clear validation message
     */
    function clearValidationMessage($field) {
        $field.siblings('.validation-message').remove();
    }

    /**
     * Initialize tooltips and help text
     */
    function initTooltips() {
        // Add help icons for complex fields
        const helpTexts = {
            'parfume_slug': 'Това е основният URL slug за архивната страница на парфюмите. Промяната изисква обновяване на permalink структурата.',
            'archive_posts_per_page': 'Определя колко парфюма се показват на една страница в архива.',
            'price_check_interval': 'Колко често системата проверява за промени в цените от външните магазини.'
        };
        
        $.each(helpTexts, function(fieldName, helpText) {
            const $field = $('input[name*="' + fieldName + '"], select[name*="' + fieldName + '"]');
            if ($field.length) {
                const $helpIcon = $('<span class="help-icon" title="' + helpText + '">?</span>');
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
            return false;
        }
        
        // Show progress
        $submitBtn.prop('disabled', true)
                  .val('Импортиране...')
                  .addClass('updating-message');
        
        // Add progress indicator
        const $progress = $('<div class="import-progress">Обработване на файла...</div>');
        $form.append($progress);
    });

    /**
     * Handle export button clicks
     */
    $(document).on('click', 'a[href*="parfume_export"]', function(e) {
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.text('Експортиране...')
            .addClass('updating-message')
            .prop('disabled', true);
        
        // Reset button after delay (since it's a download link)
        setTimeout(function() {
            $btn.text(originalText)
                .removeClass('updating-message')
                .prop('disabled', false);
        }, 3000);
    });

    /**
     * Keyboard shortcuts
     */
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('form .button-primary').click();
        }
        
        // Ctrl/Cmd + number to switch tabs
        if ((e.ctrlKey || e.metaKey) && e.which >= 49 && e.which <= 57) {
            const tabIndex = e.which - 49;
            const $tab = $('.parfume-settings-tabs .nav-tab').eq(tabIndex);
            if ($tab.length) {
                e.preventDefault();
                $tab.click();
            }
        }
    });

    /**
     * Auto-save draft functionality
     */
    let autoSaveTimer;
    function startAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            const formData = $('form').serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_autosave_settings',
                    nonce: $('#_wpnonce').val(),
                    data: formData
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Настройките са автоматично запазени', 'info');
                    }
                }
            });
        }, 30000); // Auto-save every 30 seconds
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        const $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap > h1').after($notification);
        
        setTimeout(function() {
            $notification.fadeOut();
        }, 3000);
    }

    // Start auto-save on form changes
    $('form input, form select, form textarea').on('change', startAutoSave);

})(jQuery);