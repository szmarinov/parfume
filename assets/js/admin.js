/**
 * Admin JavaScript for Parfume Catalog Plugin
 * 
 * @package ParfumeCatalog
 */

(function($) {
    'use strict';

    // Global admin object
    window.ParfumeAdmin = {
        init: function() {
            this.initTabs();
            this.initStoreManager();
            this.initScraperInterface();
            this.initTestTool();
            this.initComparison();
            this.initImportExport();
            this.initNotifications();
            this.initFormValidation();
        },

        /**
         * Initialize tab navigation
         */
        initTabs: function() {
            $('.parfume-nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                var $targetSection = $(target);
                
                if ($targetSection.length) {
                    // Update active tab
                    $('.parfume-nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    // Show target section
                    $('.parfume-tab-content').hide();
                    $targetSection.show();
                    
                    // Update URL hash
                    if (history.pushState) {
                        history.pushState(null, null, target);
                    }
                }
            });
            
            // Show initial tab based on URL hash
            var hash = window.location.hash;
            if (hash && $(hash).length) {
                $('.parfume-nav-tab[href="' + hash + '"]').trigger('click');
            } else {
                $('.parfume-nav-tab').first().trigger('click');
            }
        },

        /**
         * Initialize store management
         */
        initStoreManager: function() {
            var self = this;
            
            // Make stores sortable
            if ($('#parfume-stores-list').length) {
                $('#parfume-stores-list').sortable({
                    handle: '.parfume-store-drag-handle',
                    placeholder: 'parfume-store-placeholder',
                    update: function() {
                        self.updateStoreOrder();
                    }
                });
            }
            
            // Add new store
            $(document).on('click', '.parfume-add-store', function() {
                self.addNewStore();
            });
            
            // Remove store
            $(document).on('click', '.parfume-remove-store', function() {
                if (confirm('Сигурни ли сте, че искате да премахнете този магазин?')) {
                    $(this).closest('.parfume-store-item').fadeOut(300, function() {
                        $(this).remove();
                        self.updateStoreOrder();
                    });
                }
            });
            
            // Store logo upload
            $(document).on('click', '.parfume-upload-logo', function() {
                var $button = $(this);
                var frame = wp.media({
                    title: 'Избери лого на магазин',
                    button: { text: 'Използвай това изображение' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var $container = $button.closest('.parfume-store-item');
                    
                    $container.find('.store-logo-preview').attr('src', attachment.url);
                    $container.find('.store-logo-url').val(attachment.url);
                    $container.find('.store-logo-id').val(attachment.id);
                });
                
                frame.open();
            });
            
            // Test store connection
            $(document).on('click', '.parfume-test-store', function() {
                var $button = $(this);
                var $store = $button.closest('.parfume-store-item');
                var storeData = self.getStoreData($store);
                
                $button.prop('disabled', true).text('Тествам...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_test_store',
                        nonce: parfume_admin.nonce,
                        store_data: storeData
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification('Връзката с магазина е успешна!', 'success');
                        } else {
                            self.showNotification('Грешка: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Възникна грешка при тестването.', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Тествай връзката');
                    }
                });
            });
        },

        /**
         * Get store data from form
         */
        getStoreData: function($store) {
            var data = {};
            $store.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (name) {
                    data[name] = $field.val();
                }
            });
            return data;
        },

        /**
         * Add new store
         */
        addNewStore: function() {
            var template = $('#parfume-store-template').html();
            var storeIndex = Date.now(); // Use timestamp as unique index
            
            template = template.replace(/\{index\}/g, storeIndex);
            
            var $newStore = $(template);
            $('#parfume-stores-list').append($newStore);
            
            // Animate in
            $newStore.hide().fadeIn(300);
            
            // Focus first input
            $newStore.find('input').first().focus();
        },

        /**
         * Update store order
         */
        updateStoreOrder: function() {
            var order = [];
            $('#parfume-stores-list .parfume-store-item').each(function(index) {
                $(this).find('.store-order').val(index);
                order.push(index);
            });
            
            // Save order via AJAX if needed
            // This could be implemented later for immediate saving
        },

        /**
         * Initialize scraper interface
         */
        initScraperInterface: function() {
            var self = this;
            
            // Manual scrape button
            $(document).on('click', '.parfume-scrape-now', function() {
                var $button = $(this);
                var postId = $button.data('post-id');
                var storeId = $button.data('store-id');
                
                self.runScraper($button, postId, storeId);
            });
            
            // Batch scrape
            $(document).on('click', '#parfume-batch-scrape', function() {
                var $button = $(this);
                self.runBatchScraper($button);
            });
            
            // Auto-refresh scraper status
            if ($('.parfume-scraper-status').length) {
                setInterval(function() {
                    self.updateScraperStatus();
                }, 30000); // Every 30 seconds
            }
        },

        /**
         * Run individual scraper
         */
        runScraper: function($button, postId, storeId) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Скрейпвам...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_scrape_product',
                    nonce: parfume_admin.nonce,
                    post_id: postId,
                    store_id: storeId
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification('Скрейпването е завършено успешно!', 'success');
                        this.updateScraperResults($button, response.data);
                    } else {
                        this.showNotification('Грешка при скрейпване: ' + response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification('Възникна грешка при скрейпването.', 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Run batch scraper
         */
        runBatchScraper: function($button) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Обработвам...');
            
            // Show progress bar
            var $progress = $('.parfume-scraper-progress');
            $progress.show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_batch_scrape',
                    nonce: parfume_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification('Batch скрейпването е стартирано!', 'success');
                        this.monitorBatchProgress();
                    } else {
                        this.showNotification('Грешка при стартиране: ' + response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification('Възникна грешка.', 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Monitor batch progress
         */
        monitorBatchProgress: function() {
            var self = this;
            var checkProgress = function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'parfume_scraper_progress',
                        nonce: parfume_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var progress = response.data;
                            self.updateProgressBar(progress.percent);
                            
                            if (progress.status === 'completed') {
                                self.showNotification('Batch скрейпването е завършено!', 'success');
                                $('.parfume-scraper-progress').hide();
                            } else if (progress.status === 'running') {
                                setTimeout(checkProgress, 2000);
                            }
                        }
                    }
                });
            };
            
            checkProgress();
        },

        /**
         * Update progress bar
         */
        updateProgressBar: function(percent) {
            $('.parfume-scraper-progress-bar').css('width', percent + '%');
            $('.parfume-scraper-progress-text').text(percent + '%');
        },

        /**
         * Update scraper status
         */
        updateScraperStatus: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_scraper_status',
                    nonce: parfume_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update status indicators
                        $('.parfume-scraper-status').each(function() {
                            var $status = $(this);
                            var postId = $status.data('post-id');
                            var storeId = $status.data('store-id');
                            
                            if (response.data[postId] && response.data[postId][storeId]) {
                                var status = response.data[postId][storeId];
                                $status.removeClass('success error pending running')
                                       .addClass(status.status)
                                       .text(status.label);
                            }
                        });
                    }
                }
            });
        },

        /**
         * Initialize test tool
         */
        initTestTool: function() {
            var self = this;
            
            // Test URL scraping
            $(document).on('click', '#parfume-test-scrape', function() {
                var $button = $(this);
                var url = $('#test-url').val();
                
                if (!url) {
                    self.showNotification('Моля въведете URL за тестване.', 'warning');
                    return;
                }
                
                self.runTestScrape($button, url);
            });
            
            // Save schema
            $(document).on('click', '#parfume-save-schema', function() {
                var $button = $(this);
                var schema = self.getSelectedSchema();
                var storeId = $('#test-store-id').val();
                
                if (!storeId) {
                    self.showNotification('Моля изберете магазин.', 'warning');
                    return;
                }
                
                self.saveSchema($button, storeId, schema);
            });
            
            // Selector clicking
            $(document).on('click', '.parfume-selector-item', function() {
                $(this).toggleClass('selected');
                self.updateSelectedSchema();
            });
        },

        /**
         * Run test scrape
         */
        runTestScrape: function($button, url) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Анализирам...');
            
            $('.parfume-test-results').empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_test_scrape',
                    nonce: parfume_admin.nonce,
                    url: url
                },
                success: function(response) {
                    if (response.success) {
                        this.displayTestResults(response.data);
                    } else {
                        this.showNotification('Грешка при анализиране: ' + response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification('Възникна грешка при анализирането.', 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Display test results
         */
        displayTestResults: function(data) {
            var $results = $('.parfume-test-results');
            $results.empty();
            
            // Show page preview
            if (data.preview) {
                $results.append('<h4>Преглед на страницата:</h4>');
                $results.append('<div class="parfume-test-preview">' + data.preview + '</div>');
            }
            
            // Show detected elements
            if (data.elements && data.elements.length > 0) {
                $results.append('<h4>Открити елементи:</h4>');
                
                var $elementsList = $('<div class="parfume-detected-elements"></div>');
                
                $.each(data.elements, function(index, element) {
                    var $item = $('<div class="parfume-selector-item" data-type="' + element.type + '" data-selector="' + element.selector + '">');
                    $item.append('<div class="parfume-selector-value"><strong>' + element.type + ':</strong> ' + element.value + '</div>');
                    $item.append('<div class="parfume-selector-path">' + element.selector + '</div>');
                    $elementsList.append($item);
                });
                
                $results.append($elementsList);
            } else {
                $results.append('<p>Не са открити елементи за скрейпване.</p>');
            }
        },

        /**
         * Get selected schema
         */
        getSelectedSchema: function() {
            var schema = {};
            
            $('.parfume-selector-item.selected').each(function() {
                var $item = $(this);
                var type = $item.data('type');
                var selector = $item.data('selector');
                
                schema[type + '_selector'] = selector;
            });
            
            return schema;
        },

        /**
         * Save schema
         */
        saveSchema: function($button, storeId, schema) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Запазвам...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_save_schema',
                    nonce: parfume_admin.nonce,
                    store_id: storeId,
                    schema: schema
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification('Схемата е запазена успешно!', 'success');
                    } else {
                        this.showNotification('Грешка при запазване: ' + response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification('Възникна грешка при запазването.', 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Initialize comparison settings
         */
        initComparison: function() {
            var self = this;
            
            // Toggle comparison fields
            $(document).on('change', '.comparison-field-toggle', function() {
                var $checkbox = $(this);
                var $field = $checkbox.closest('.parfume-comparison-field');
                
                if ($checkbox.is(':checked')) {
                    $field.addClass('enabled');
                } else {
                    $field.removeClass('enabled');
                }
                
                self.updateComparisonPreview();
            });
            
            // Make comparison fields sortable
            if ($('.parfume-comparison-fields').length) {
                $('.parfume-comparison-fields').sortable({
                    update: function() {
                        self.updateComparisonPreview();
                    }
                });
            }
        },

        /**
         * Update comparison preview
         */
        updateComparisonPreview: function() {
            var enabledFields = [];
            
            $('.parfume-comparison-field.enabled').each(function() {
                var $field = $(this);
                var fieldName = $field.find('.comparison-field-toggle').val();
                var fieldLabel = $field.find('label').text();
                
                enabledFields.push({
                    name: fieldName,
                    label: fieldLabel
                });
            });
            
            // Update preview table
            var $preview = $('.parfume-comparison-preview');
            if ($preview.length && enabledFields.length > 0) {
                var tableHTML = '<table class="parfume-comparison-table"><thead><tr><th>Критерий</th><th>Парфюм 1</th><th>Парфюм 2</th></tr></thead><tbody>';
                
                $.each(enabledFields, function(index, field) {
                    tableHTML += '<tr><td>' + field.label + '</td><td>Примерна стойност</td><td>Примерна стойност</td></tr>';
                });
                
                tableHTML += '</tbody></table>';
                $preview.html(tableHTML);
            }
        },

        /**
         * Initialize import/export
         */
        initImportExport: function() {
            var self = this;
            
            // File drop zone
            var $dropZone = $('.parfume-import-drop-zone');
            
            $dropZone.on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $dropZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            $dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    self.handleFileUpload(files[0]);
                }
            });
            
            // File input change
            $(document).on('change', '#parfume-import-file', function() {
                var files = this.files;
                if (files.length > 0) {
                    self.handleFileUpload(files[0]);
                }
            });
            
            // Export button
            $(document).on('click', '#parfume-export-data', function() {
                var $button = $(this);
                var exportType = $('input[name="export_type"]:checked').val();
                
                self.exportData($button, exportType);
            });
        },

        /**
         * Handle file upload
         */
        handleFileUpload: function(file) {
            var self = this;
            
            if (file.type !== 'application/json') {
                self.showNotification('Моля изберете JSON файл.', 'warning');
                return;
            }
            
            var formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'parfume_import_data');
            formData.append('nonce', parfume_admin.nonce);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showNotification('Импортирането е завършено успешно!', 'success');
                    } else {
                        self.showNotification('Грешка при импортиране: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Възникна грешка при импортирането.', 'error');
                }
            });
        },

        /**
         * Export data
         */
        exportData: function($button, exportType) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Експортирам...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_export_data',
                    nonce: parfume_admin.nonce,
                    export_type: exportType
                },
                success: function(response) {
                    if (response.success) {
                        // Download file
                        var blob = new Blob([JSON.stringify(response.data, null, 2)], {
                            type: 'application/json'
                        });
                        
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'parfume-export-' + Date.now() + '.json';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                        
                        this.showNotification('Експортирането е завършено!', 'success');
                    } else {
                        this.showNotification('Грешка при експортиране: ' + response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification('Възникна грешка при експортирането.', 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Initialize notifications
         */
        initNotifications: function() {
            // Auto-hide notices after 5 seconds
            setTimeout(function() {
                $('.parfume-notice').fadeOut();
            }, 5000);
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="parfume-notice notice-' + type + '"><p>' + message + '</p></div>');
            
            $('.parfume-admin-header').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            // Real-time validation
            $(document).on('blur', 'input[type="url"]', function() {
                var $input = $(this);
                var url = $input.val();
                
                if (url && !this.isValidUrl(url)) {
                    $input.addClass('error');
                    this.showFieldError($input, 'Моля въведете валиден URL.');
                } else {
                    $input.removeClass('error');
                    this.hideFieldError($input);
                }
            }.bind(this));
            
            $(document).on('blur', 'input[type="email"]', function() {
                var $input = $(this);
                var email = $input.val();
                
                if (email && !this.isValidEmail(email)) {
                    $input.addClass('error');
                    this.showFieldError($input, 'Моля въведете валиден имейл.');
                } else {
                    $input.removeClass('error');
                    this.hideFieldError($input);
                }
            }.bind(this));
        },

        /**
         * Validate URL
         */
        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },

        /**
         * Validate email
         */
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            this.hideFieldError($field);
            
            var $error = $('<div class="field-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;">' + message + '</div>');
            $field.after($error);
        },

        /**
         * Hide field error
         */
        hideFieldError: function($field) {
            $field.next('.field-error').remove();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof parfume_admin !== 'undefined') {
            ParfumeAdmin.init();
        }
    });

})(jQuery);