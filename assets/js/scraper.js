/**
 * Scraper AJAX functionality for Parfume Catalog Plugin
 * 
 * @package ParfumeCatalog
 */

(function($) {
    'use strict';

    // Global scraper object
    window.ParfumeScraper = {
        isRunning: false,
        currentRequests: [],
        progressInterval: null,
        statusCheckInterval: null,

        init: function() {
            this.initEventHandlers();
            this.initStatusMonitoring();
            this.initBatchInterface();
            this.initManualControls();
            this.checkInitialStatus();
        },

        /**
         * Initialize event handlers
         */
        initEventHandlers: function() {
            var self = this;

            // Manual scrape buttons
            $(document).on('click', '.scrape-single-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var postId = $btn.data('post-id');
                var storeId = $btn.data('store-id');
                
                self.scrapeSingle(postId, storeId, $btn);
            });

            // Batch scrape button
            $(document).on('click', '#start-batch-scrape', function(e) {
                e.preventDefault();
                var $btn = $(this);
                
                if (self.isRunning) {
                    self.stopBatchScrape();
                } else {
                    self.startBatchScrape($btn);
                }
            });

            // Test URL button
            $(document).on('click', '#test-scrape-url', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var url = $('#test-url-input').val();
                
                if (!url) {
                    self.showMessage('Моля въведете URL за тестване.', 'warning');
                    return;
                }
                
                self.testUrl(url, $btn);
            });

            // Save schema button
            $(document).on('click', '#save-scraper-schema', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var storeId = $('#schema-store-id').val();
                var schema = self.getSchemaFromForm();
                
                if (!storeId) {
                    self.showMessage('Моля изберете магазин.', 'warning');
                    return;
                }
                
                self.saveSchema(storeId, schema, $btn);
            });

            // Refresh status button
            $(document).on('click', '.refresh-status-btn', function(e) {
                e.preventDefault();
                self.updateAllStatuses();
            });

            // Clear log button
            $(document).on('click', '.clear-log-btn', function(e) {
                e.preventDefault();
                if (confirm('Сигурни ли сте, че искате да изчистите лога?')) {
                    self.clearLog();
                }
            });

            // Auto-refresh toggle
            $(document).on('change', '#auto-refresh-status', function() {
                if ($(this).is(':checked')) {
                    self.startStatusMonitoring();
                } else {
                    self.stopStatusMonitoring();
                }
            });

            // Interval settings change
            $(document).on('change', '#scrape-interval', function() {
                var interval = $(this).val();
                self.updateScrapeInterval(interval);
            });

            // Batch size change
            $(document).on('change', '#batch-size', function() {
                var size = $(this).val();
                self.updateBatchSize(size);
            });
        },

        /**
         * Scrape single product
         */
        scrapeSingle: function(postId, storeId, $btn) {
            var self = this;
            var originalText = $btn.text();
            
            $btn.prop('disabled', true)
                .text('Скрейпвам...')
                .addClass('loading');

            // Update status indicator
            var $status = $btn.closest('.scraper-item').find('.scraper-status');
            $status.removeClass('success error pending')
                   .addClass('running')
                   .text('Обработва се...');

            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_scrape_single',
                    nonce: parfume_scraper_ajax.nonce,
                    post_id: postId,
                    store_id: storeId
                },
                timeout: 60000, // 60 seconds timeout
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Скрейпването е завършено успешно!', 'success');
                        self.updateScrapedData($btn, response.data);
                        $status.removeClass('running')
                               .addClass('success')
                               .text('Успешно');
                    } else {
                        self.showMessage('Грешка при скрейпване: ' + response.data.message, 'error');
                        $status.removeClass('running')
                               .addClass('error')
                               .text('Грешка');
                        
                        if (response.data.log) {
                            self.appendToLog(response.data.log);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    var message = 'Възникна грешка при скрейпването.';
                    
                    if (status === 'timeout') {
                        message = 'Скрейпването отне твърде много време.';
                    } else if (xhr.responseJSON && xhr.responseJSON.data) {
                        message = xhr.responseJSON.data.message || message;
                    }
                    
                    self.showMessage(message, 'error');
                    $status.removeClass('running')
                           .addClass('error')
                           .text('Грешка');
                },
                complete: function() {
                    $btn.prop('disabled', false)
                        .text(originalText)
                        .removeClass('loading');
                }
            });
        },

        /**
         * Start batch scraping
         */
        startBatchScrape: function($btn) {
            var self = this;
            
            $btn.text('Спиране на batch скрейпване')
                .removeClass('button-primary')
                .addClass('button-secondary');
            
            this.isRunning = true;
            this.showMessage('Batch скрейпването е стартирано.', 'info');
            
            // Show progress container
            $('.batch-progress-container').show();
            this.resetProgress();
            
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_start_batch_scrape',
                    nonce: parfume_scraper_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Batch скрейпването е стартирано успешно!', 'success');
                        self.startProgressMonitoring();
                    } else {
                        self.showMessage('Грешка при стартиране: ' + response.data.message, 'error');
                        self.stopBatchScrape();
                    }
                },
                error: function() {
                    self.showMessage('Възникна грешка при стартирането.', 'error');
                    self.stopBatchScrape();
                }
            });
        },

        /**
         * Stop batch scraping
         */
        stopBatchScrape: function() {
            var self = this;
            
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_stop_batch_scrape',
                    nonce: parfume_scraper_ajax.nonce
                },
                success: function(response) {
                    self.showMessage('Batch скрейпването е спряно.', 'info');
                },
                error: function() {
                    self.showMessage('Грешка при спиране на batch скрейпването.', 'warning');
                }
            });
            
            this.isRunning = false;
            this.stopProgressMonitoring();
            
            $('#start-batch-scrape')
                .text('Старт на batch скрейпване')
                .removeClass('button-secondary')
                .addClass('button-primary');
        },

        /**
         * Test URL scraping
         */
        testUrl: function(url, $btn) {
            var self = this;
            var originalText = $btn.text();
            
            $btn.prop('disabled', true)
                .text('Тествам...')
                .addClass('loading');
            
            // Clear previous results
            $('#test-results').empty().hide();

            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_test_url',
                    nonce: parfume_scraper_ajax.nonce,
                    url: url
                },
                timeout: 30000, // 30 seconds for testing
                success: function(response) {
                    if (response.success) {
                        self.showTestResults(response.data);
                        self.showMessage('URL тестването е завършено.', 'success');
                    } else {
                        self.showMessage('Грешка при тестване: ' + response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    var message = 'Възникна грешка при тестването.';
                    
                    if (status === 'timeout') {
                        message = 'Тестването отне твърде много време.';
                    }
                    
                    self.showMessage(message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false)
                        .text(originalText)
                        .removeClass('loading');
                }
            });
        },

        /**
         * Show test results
         */
        showTestResults: function(data) {
            var $results = $('#test-results');
            var html = '';
            
            if (data.detected_elements && data.detected_elements.length > 0) {
                html += '<h4>Открити елементи:</h4>';
                html += '<div class="detected-elements">';
                
                $.each(data.detected_elements, function(index, element) {
                    html += `
                        <div class="element-item" data-type="${element.type}" data-selector="${element.selector}">
                            <div class="element-header">
                                <strong>${element.type}:</strong> ${element.value}
                                <button class="use-element" type="button">Използвай</button>
                            </div>
                            <div class="element-selector">Селектор: <code>${element.selector}</code></div>
                        </div>
                    `;
                });
                
                html += '</div>';
            } else {
                html += '<p>Не са открити подходящи елементи за скрейпване.</p>';
            }
            
            if (data.page_info) {
                html += '<h4>Информация за страницата:</h4>';
                html += '<ul>';
                html += `<li>Заглавие: ${data.page_info.title || 'Няма'}</li>`;
                html += `<li>Статус код: ${data.page_info.status_code || 'Няма'}</li>`;
                html += `<li>Размер: ${data.page_info.content_length || 'Няма'} байта</li>`;
                html += '</ul>';
            }
            
            $results.html(html).show();
            
            // Handle use element buttons
            $results.find('.use-element').on('click', function() {
                var $item = $(this).closest('.element-item');
                var type = $item.data('type');
                var selector = $item.data('selector');
                
                // Fill the corresponding schema field
                $(`#schema-${type}-selector`).val(selector);
                
                this.showMessage(`Селекторът за ${type} е добавен.`, 'success');
            }.bind(this));
        },

        /**
         * Save scraper schema
         */
        saveSchema: function(storeId, schema, $btn) {
            var self = this;
            var originalText = $btn.text();
            
            $btn.prop('disabled', true)
                .text('Запазвам...')
                .addClass('loading');

            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_save_schema',
                    nonce: parfume_scraper_ajax.nonce,
                    store_id: storeId,
                    schema: schema
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Схемата е запазена успешно!', 'success');
                    } else {
                        self.showMessage('Грешка при запазване: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showMessage('Възникна грешка при запазването.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false)
                        .text(originalText)
                        .removeClass('loading');
                }
            });
        },

        /**
         * Get schema from form
         */
        getSchemaFromForm: function() {
            var schema = {};
            
            $('#scraper-schema-form input, #scraper-schema-form select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                
                if (name && value) {
                    schema[name] = value;
                }
            });
            
            return schema;
        },

        /**
         * Initialize status monitoring
         */
        initStatusMonitoring: function() {
            // Auto-start monitoring if enabled
            if ($('#auto-refresh-status').is(':checked')) {
                this.startStatusMonitoring();
            }
        },

        /**
         * Start status monitoring
         */
        startStatusMonitoring: function() {
            var self = this;
            
            if (this.statusCheckInterval) {
                clearInterval(this.statusCheckInterval);
            }
            
            this.statusCheckInterval = setInterval(function() {
                self.updateAllStatuses();
            }, 30000); // Check every 30 seconds
        },

        /**
         * Stop status monitoring
         */
        stopStatusMonitoring: function() {
            if (this.statusCheckInterval) {
                clearInterval(this.statusCheckInterval);
                this.statusCheckInterval = null;
            }
        },

        /**
         * Update all scraper statuses
         */
        updateAllStatuses: function() {
            var self = this;
            
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_get_all_statuses',
                    nonce: parfume_scraper_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateStatusDisplay(response.data);
                    }
                },
                error: function() {
                    // Silently fail for background updates
                }
            });
        },

        /**
         * Update status display
         */
        updateStatusDisplay: function(statusData) {
            $('.scraper-status').each(function() {
                var $status = $(this);
                var postId = $status.data('post-id');
                var storeId = $status.data('store-id');
                
                if (statusData[postId] && statusData[postId][storeId]) {
                    var status = statusData[postId][storeId];
                    
                    $status.removeClass('success error pending running')
                           .addClass(status.status)
                           .text(status.label);
                    
                    // Update timestamps
                    var $item = $status.closest('.scraper-item');
                    if (status.last_scraped) {
                        $item.find('.last-scraped').text(status.last_scraped);
                    }
                    if (status.next_scrape) {
                        $item.find('.next-scrape').text(status.next_scrape);
                    }
                }
            });
        },

        /**
         * Initialize batch interface
         */
        initBatchInterface: function() {
            // Progress monitoring setup
            $('.batch-progress-container').hide();
        },

        /**
         * Start progress monitoring
         */
        startProgressMonitoring: function() {
            var self = this;
            
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            
            this.progressInterval = setInterval(function() {
                self.updateProgress();
            }, 2000); // Check every 2 seconds
        },

        /**
         * Stop progress monitoring
         */
        stopProgressMonitoring: function() {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }
        },

        /**
         * Update progress
         */
        updateProgress: function() {
            var self = this;
            
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_get_batch_progress',
                    nonce: parfume_scraper_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var progress = response.data;
                        
                        self.updateProgressBar(progress.percent);
                        self.updateProgressStats(progress);
                        
                        if (progress.status === 'completed' || progress.status === 'stopped') {
                            self.stopBatchScrape();
                            self.showMessage('Batch скрейпването е завършено.', 'success');
                        }
                    }
                },
                error: function() {
                    // Continue monitoring even on errors
                }
            });
        },

        /**
         * Update progress bar
         */
        updateProgressBar: function(percent) {
            $('.batch-progress-bar').css('width', percent + '%');
            $('.batch-progress-text').text(Math.round(percent) + '%');
        },

        /**
         * Update progress stats
         */
        updateProgressStats: function(progress) {
            $('.progress-processed').text(progress.processed || 0);
            $('.progress-total').text(progress.total || 0);
            $('.progress-errors').text(progress.errors || 0);
            $('.progress-eta').text(progress.eta || 'Неизвестно');
        },

        /**
         * Reset progress
         */
        resetProgress: function() {
            this.updateProgressBar(0);
            this.updateProgressStats({
                processed: 0,
                total: 0,
                errors: 0,
                eta: 'Изчислява се...'
            });
        },

        /**
         * Initialize manual controls
         */
        initManualControls: function() {
            // Already handled in main event handlers
        },

        /**
         * Update scraped data display
         */
        updateScrapedData: function($btn, data) {
            var $item = $btn.closest('.scraper-item');
            var $dataContainer = $item.find('.scraped-data');
            
            if ($dataContainer.length && data) {
                var html = '<div class="scraped-info">';
                
                if (data.price) {
                    html += `<div class="data-item"><strong>Цена:</strong> ${data.price}</div>`;
                }
                
                if (data.old_price) {
                    html += `<div class="data-item"><strong>Стара цена:</strong> ${data.old_price}</div>`;
                }
                
                if (data.variants && data.variants.length > 0) {
                    html += '<div class="data-item"><strong>Варианти:</strong>';
                    $.each(data.variants, function(index, variant) {
                        html += ` ${variant.size} (${variant.price})`;
                        if (index < data.variants.length - 1) html += ', ';
                    });
                    html += '</div>';
                }
                
                if (data.availability) {
                    html += `<div class="data-item"><strong>Наличност:</strong> ${data.availability}</div>`;
                }
                
                if (data.shipping) {
                    html += `<div class="data-item"><strong>Доставка:</strong> ${data.shipping}</div>`;
                }
                
                html += '</div>';
                $dataContainer.html(html);
            }
        },

        /**
         * Check initial status
         */
        checkInitialStatus: function() {
            // Check if batch scraping is already running
            var self = this;
            
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_check_batch_status',
                    nonce: parfume_scraper_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.is_running) {
                        self.isRunning = true;
                        
                        $('#start-batch-scrape')
                            .text('Спиране на batch скрейпване')
                            .removeClass('button-primary')
                            .addClass('button-secondary');
                        
                        $('.batch-progress-container').show();
                        self.startProgressMonitoring();
                    }
                }
            });
        },

        /**
         * Update scrape interval
         */
        updateScrapeInterval: function(interval) {
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_update_scrape_interval',
                    nonce: parfume_scraper_ajax.nonce,
                    interval: interval
                },
                success: function(response) {
                    if (response.success) {
                        this.showMessage('Интервалът е обновен.', 'success');
                    }
                }.bind(this)
            });
        },

        /**
         * Update batch size
         */
        updateBatchSize: function(size) {
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_update_batch_size',
                    nonce: parfume_scraper_ajax.nonce,
                    size: size
                },
                success: function(response) {
                    if (response.success) {
                        this.showMessage('Размерът на batch е обновен.', 'success');
                    }
                }.bind(this)
            });
        },

        /**
         * Clear log
         */
        clearLog: function() {
            $.ajax({
                url: parfume_scraper_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_clear_scraper_log',
                    nonce: parfume_scraper_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.scraper-log').empty();
                        this.showMessage('Логът е изчистен.', 'success');
                    }
                }.bind(this)
            });
        },

        /**
         * Append to log
         */
        appendToLog: function(logEntry) {
            var $log = $('.scraper-log');
            if ($log.length) {
                $log.append(logEntry + '\n');
                $log.scrollTop($log[0].scrollHeight);
            }
        },

        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Create notification
            var $notification = $(`
                <div class="parfume-scraper-notification parfume-notification-${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">×</button>
                </div>
            `);
            
            // Add to page
            if ($('.scraper-notifications').length) {
                $('.scraper-notifications').append($notification);
            } else {
                $('body').append($notification);
            }
            
            // Show notification
            $notification.addClass('show');
            
            // Auto hide after 5 seconds
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 5000);
            
            // Manual close
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof parfume_scraper_ajax !== 'undefined') {
            ParfumeScraper.init();
        }
    });

    // Expose to global scope
    window.parfumeScraper = ParfumeScraper;

})(jQuery);