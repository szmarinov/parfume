/**
 * Admin Settings JavaScript
 * assets/js/admin-settings.js
 * 
 * Handles admin settings functionality for Parfume Reviews plugin
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize all settings features
    initTabNavigation();
    initFormValidation();
    initBackupRestore();
    initStoresManagement();
    initScraperSettings();
    initMobileSettings();
    initHelpTooltips();
    initProgressBars();
    
    /**
     * Initialize tab navigation
     */
    function initTabNavigation() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const $tab = $(this);
            const target = $tab.attr('href').substring(1);
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show target content
            $('.tab-content').removeClass('active');
            $('#' + target).addClass('active');
            
            // Save active tab in localStorage
            localStorage.setItem('parfume_active_tab', target);
            
            // Update URL hash without scrolling
            if (history.replaceState) {
                history.replaceState(null, null, '#' + target);
            }
        });
        
        // Restore active tab from localStorage or URL hash
        const savedTab = localStorage.getItem('parfume_active_tab') || 
                        window.location.hash.substring(1) || 
                        'general';
        
        const $targetTab = $('.nav-tab[href="#' + savedTab + '"]');
        if ($targetTab.length) {
            $targetTab.click();
        }
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        $('form.parfume-settings-form').on('submit', function(e) {
            const $form = $(this);
            let isValid = true;
            
            // Clear previous errors
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">Това поле е задължително</span>');
                }
            });
            
            // Validate URLs
            $form.find('input[type="url"]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (value && !isValidUrl(value)) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">Моля въведете валиден URL</span>');
                }
            });
            
            // Validate numbers
            $form.find('input[type="number"]').each(function() {
                const $field = $(this);
                const value = parseFloat($field.val());
                const min = parseFloat($field.attr('min'));
                const max = parseFloat($field.attr('max'));
                
                if (!isNaN(min) && value < min) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">Стойността трябва да е най-малко ' + min + '</span>');
                }
                
                if (!isNaN(max) && value > max) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">Стойността трябва да е най-много ' + max + '</span>');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                const $firstError = $form.find('.error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
                
                showNotice('Моля поправете грешките преди запазване', 'error');
            }
        });
    }
    
    /**
     * Initialize backup and restore functionality
     */
    function initBackupRestore() {
        // Backup button
        $('.backup-settings').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text('Създаване на backup...').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_backup_settings',
                    nonce: parfumeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        downloadBackup(response.data.backup, response.data.filename);
                        showNotice('Backup файлът е създаден успешно', 'success');
                    } else {
                        showNotice('Грешка при създаване на backup: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('AJAX грешка при създаване на backup', 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Restore functionality
        $('.restore-settings').on('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            if (file.type !== 'application/json') {
                showNotice('Моля изберете валиден JSON файл', 'error');
                return;
            }
            
            if (confirm('Сигурни ли сте, че искате да възстановите настройките? Текущите настройки ще бъдат заменени.')) {
                restoreFromFile(file);
            }
        });
    }
    
    /**
     * Initialize stores management
     */
    function initStoresManagement() {
        // Add new store
        $('.add-store-btn').on('click', function(e) {
            e.preventDefault();
            addNewStoreRow();
        });
        
        // Remove store
        $(document).on('click', '.remove-store-btn', function(e) {
            e.preventDefault();
            
            if (confirm('Сигурни ли сте, че искате да изтриете този магазин?')) {
                $(this).closest('.store-row').fadeOut(300, function() {
                    $(this).remove();
                    updateStoreIndexes();
                });
            }
        });
        
        // Store logo upload
        $(document).on('click', '.store-logo-upload', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $row = $button.closest('.store-row');
            
            openMediaUploader(function(url) {
                $row.find('.store-logo-url').val(url);
                $row.find('.store-logo-preview').attr('src', url).show();
            });
        });
        
        // Make stores sortable
        if ($.fn.sortable) {
            $('.stores-container').sortable({
                handle: '.store-drag-handle',
                placeholder: 'store-placeholder',
                update: function() {
                    updateStoreIndexes();
                }
            });
        }
    }
    
    /**
     * Initialize scraper settings
     */
    function initScraperSettings() {
        // Test scraper button
        $('.test-scraper-btn').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const testUrl = $('#scraper_test_url').val();
            
            if (!testUrl) {
                showNotice('Моля въведете URL за тест', 'error');
                return;
            }
            
            $button.text('Тестване...').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_test_scraper',
                    nonce: parfumeAdmin.nonce,
                    test_url: testUrl
                },
                success: function(response) {
                    if (response.success) {
                        displayScraperResults(response.data);
                        showNotice('Scraper тестът завърши успешно', 'success');
                    } else {
                        showNotice('Scraper грешка: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('AJAX грешка при тестване на scraper', 'error');
                },
                complete: function() {
                    $button.text('Тестване').prop('disabled', false);
                }
            });
        });
        
        // Bulk scrape button
        $('.bulk-scrape-btn').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Сигурни ли сте, че искате да започнете bulk scraping? Това може да отнеме време.')) {
                return;
            }
            
            const $button = $(this);
            $button.text('Стартиране...').prop('disabled', true);
            
            startBulkScrape($button);
        });
    }
    
    /**
     * Initialize mobile settings
     */
    function initMobileSettings() {
        // Z-index conflict detection
        $('#mobile_z_index').on('change', function() {
            const zIndex = parseInt($(this).val());
            detectZIndexConflicts(zIndex);
        });
        
        // Preview mobile panel
        $('.preview-mobile-panel').on('click', function(e) {
            e.preventDefault();
            previewMobilePanel();
        });
    }
    
    /**
     * Initialize help tooltips
     */
    function initHelpTooltips() {
        // Add help icons to fields
        const helpTexts = {
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
     * Initialize progress bars for long operations
     */
    function initProgressBars() {
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
        
        // Handle import form submission with progress
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
        
        // Handle settings reset functionality
        $(document).on('click', '.reset-settings', function(e) {
            e.preventDefault();
            
            if (confirm('Сигурни ли сте, че искате да нулирате настройките? Тази операция не може да бъде отменена.')) {
                resetSettings($(this));
            }
        });
    }
    
    /**
     * Helper Functions
     */
    
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap .notice').remove();
        $('.wrap h1').after($notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    }
    
    function downloadBackup(data, filename) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename || 'parfume-settings-backup.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    function restoreFromFile(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                restoreSettings(data);
            } catch (error) {
                showNotice('Невалиден backup файл: ' + error.message, 'error');
            }
        };
        reader.readAsText(file);
    }
    
    function restoreSettings(data) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'parfume_restore_settings',
                nonce: parfumeAdmin.nonce,
                settings_data: JSON.stringify(data)
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Настройките са възстановени успешно', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice('Грешка при възстановяване: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('AJAX грешка при възстановяване', 'error');
            }
        });
    }
    
    function addNewStoreRow() {
        const storeIndex = $('.store-row').length;
        const $newRow = $(`
            <div class="store-row">
                <div class="store-drag-handle">⋮⋮</div>
                <input type="text" name="stores[${storeIndex}][name]" placeholder="Име на магазин" required>
                <input type="url" name="stores[${storeIndex}][url]" placeholder="URL на магазин">
                <input type="hidden" name="stores[${storeIndex}][logo_url]" class="store-logo-url">
                <button type="button" class="button store-logo-upload">Качи лого</button>
                <img class="store-logo-preview" style="display:none; width:50px; height:50px;">
                <button type="button" class="button remove-store-btn">Премахни</button>
            </div>
        `);
        
        $('.stores-container').append($newRow);
        $newRow.find('input[name*="[name]"]').focus();
    }
    
    function updateStoreIndexes() {
        $('.store-row').each(function(index) {
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }
    
    function openMediaUploader(callback) {
        if (typeof wp !== 'undefined' && wp.media) {
            const mediaUploader = wp.media({
                title: 'Изберете лого',
                button: {
                    text: 'Използване'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                callback(attachment.url);
            });
            
            mediaUploader.open();
        }
    }
    
    function displayScraperResults(data) {
        const $results = $('#scraper-results');
        $results.empty();
        
        if (data.prices && data.prices.length > 0) {
            $results.append('<h4>Намерени цени:</h4>');
            data.prices.forEach(function(price) {
                $results.append(`<p>${price.size}: ${price.price}</p>`);
            });
        }
        
        if (data.availability) {
            $results.append(`<p><strong>Наличност:</strong> ${data.availability}</p>`);
        }
        
        if (data.errors && data.errors.length > 0) {
            $results.append('<h4>Грешки:</h4>');
            data.errors.forEach(function(error) {
                $results.append(`<p style="color: red;">${error}</p>`);
            });
        }
        
        $results.show();
    }
    
    function startBulkScrape($button) {
        let processedCount = 0;
        const totalCount = parseInt($('#bulk-scrape-total').text()) || 0;
        
        function processBatch() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_bulk_scrape_batch',
                    nonce: parfumeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        processedCount += response.data.processed;
                        const progress = Math.round((processedCount / totalCount) * 100);
                        
                        $button.text(`Обработени: ${processedCount}/${totalCount} (${progress}%)`);
                        
                        if (response.data.completed) {
                            $button.text('Bulk scraping завършен').prop('disabled', false);
                            showNotice('Bulk scraping завърши успешно', 'success');
                        } else {
                            // Continue with next batch
                            setTimeout(processBatch, 2000);
                        }
                    } else {
                        $button.text('Bulk scraping').prop('disabled', false);
                        showNotice('Грешка при bulk scraping: ' + response.data, 'error');
                    }
                },
                error: function() {
                    $button.text('Bulk scraping').prop('disabled', false);
                    showNotice('AJAX грешка при bulk scraping', 'error');
                }
            });
        }
        
        processBatch();
    }
    
    function detectZIndexConflicts(zIndex) {
        // This would check for common z-index conflicts
        const conflicts = [];
        
        if (zIndex <= 9999) {
            conflicts.push('WordPress admin bar (z-index: 99999)');
        }
        
        if (zIndex <= 100000) {
            conflicts.push('Някои popup плъгини могат да имат конфликти');
        }
        
        if (conflicts.length > 0) {
            const message = 'Възможни z-index конфликти: ' + conflicts.join(', ');
            $('#mobile_z_index').after('<div class="conflict-warning">' + message + '</div>');
        } else {
            $('.conflict-warning').remove();
        }
    }
    
    function previewMobilePanel() {
        // Simple preview simulation
        const $preview = $('<div class="mobile-panel-preview">Preview на mobile panel</div>');
        $preview.css({
            position: 'fixed',
            bottom: '20px',
            right: '20px',
            background: '#fff',
            border: '1px solid #ccc',
            padding: '20px',
            'box-shadow': '0 2px 10px rgba(0,0,0,0.1)',
            'z-index': $('#mobile_z_index').val() || 9999
        });
        
        $('body').append($preview);
        
        setTimeout(function() {
            $preview.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    function resetSettings($button) {
        const originalText = $button.text();
        $button.text('Нулиране...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'parfume_reset_settings',
                nonce: parfumeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Настройките са нулирани успешно', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice('Грешка при нулиране: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('AJAX грешка при нулиране', 'error');
            },
            complete: function() {
                $button.text(originalText).prop('disabled', false);
            }
        });
    }
});