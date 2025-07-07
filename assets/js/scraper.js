(function($) {
    'use strict';

    var scraper = {
        init: function() {
            this.bindEvents();
            this.initializeMonitoring();
        },

        bindEvents: function() {
            var self = this;

            // Test URL scraping
            $(document).on('click', '.test-scrape-url', function(e) {
                e.preventDefault();
                var url = $(this).data('url') || $(this).closest('.url-test-container').find('.test-url-input').val();
                self.testUrl(url, $(this));
            });

            // Manual scrape for specific store
            $(document).on('click', '.manual-scrape-store', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                var storeIndex = $(this).data('store-index');
                self.manualScrape(postId, storeIndex, $(this));
            });

            // Bulk scrape all
            $(document).on('click', '.bulk-scrape-all', function(e) {
                e.preventDefault();
                if (confirm('Това ще обнови всички продукти. Продължавате?')) {
                    self.bulkScrapeAll($(this));
                }
            });

            // Schema configuration
            $(document).on('click', '.configure-schema', function(e) {
                e.preventDefault();
                var storeId = $(this).data('store-id');
                self.openSchemaConfigurator(storeId);
            });

            // Save schema configuration
            $(document).on('click', '.save-schema-config', function(e) {
                e.preventDefault();
                self.saveSchemaConfig();
            });

            // Test schema selectors
            $(document).on('click', '.test-schema', function(e) {
                e.preventDefault();
                self.testSchemaConfig();
            });

            // Auto-detect schema elements
            $(document).on('click', '.auto-detect-schema', function(e) {
                e.preventDefault();
                var url = $('#schema-test-url').val();
                if (url) {
                    self.autoDetectSchema(url);
                } else {
                    alert('Моля въведете URL за анализ');
                }
            });

            // Monitor page refresh
            $(document).on('click', '.refresh-monitor', function(e) {
                e.preventDefault();
                self.refreshMonitorData();
            });

            // Toggle scraper for specific post/store
            $(document).on('change', '.toggle-scraper', function() {
                var postId = $(this).data('post-id');
                var storeIndex = $(this).data('store-index');
                var enabled = $(this).is(':checked');
                self.toggleScraper(postId, storeIndex, enabled);
            });
        },

        testUrl: function(url, button) {
            if (!url) {
                alert('Моля въведете URL за тестване');
                return;
            }

            var originalText = button.text();
            button.prop('disabled', true).text('Тества...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_scrape_url',
                    url: url,
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var resultsContainer = button.closest('.url-test-container').find('.test-results');
                        if (resultsContainer.length === 0) {
                            resultsContainer = $('<div class="test-results"></div>');
                            button.closest('.url-test-container').append(resultsContainer);
                        }
                        
                        var html = '<div class="test-result-item">';
                        html += '<h4>Резултати от тестването:</h4>';
                        
                        if (response.data.price) {
                            html += '<p><strong>Цена:</strong> ' + response.data.price + '</p>';
                        }
                        
                        if (response.data.old_price) {
                            html += '<p><strong>Стара цена:</strong> ' + response.data.old_price + '</p>';
                        }
                        
                        if (response.data.availability) {
                            html += '<p><strong>Наличност:</strong> ' + response.data.availability + '</p>';
                        }
                        
                        if (response.data.shipping) {
                            html += '<p><strong>Доставка:</strong> ' + response.data.shipping + '</p>';
                        }
                        
                        if (response.data.variants && response.data.variants.length > 0) {
                            html += '<p><strong>Разфасовки:</strong> ' + response.data.variants.join(', ') + '</p>';
                        }
                        
                        html += '<p><em>Последно обновяване: ' + new Date().toLocaleString() + '</em></p>';
                        html += '</div>';
                        
                        resultsContainer.html(html);
                    } else {
                        alert('Грешка при тестване: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Грешка при свързване със сървъра');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        manualScrape: function(postId, storeIndex, button) {
            var originalText = button.text();
            button.prop('disabled', true).text('Обработва...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'manual_scrape_store',
                    post_id: postId,
                    store_index: storeIndex,
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the scraped data display
                        var container = button.closest('.scraped-data-container');
                        container.find('.scraped-data').html(response.data.html);
                        container.find('.last-scraped').text('Последно обновяване: ' + new Date().toLocaleString());
                        
                        scraper.showMessage('Данните са обновени успешно!', 'success');
                    } else {
                        scraper.showMessage('Грешка при обновяване: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    scraper.showMessage('Грешка при свързване със сървъра', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        bulkScrapeAll: function(button) {
            var originalText = button.text();
            button.prop('disabled', true).text('Стартира масово обновяване...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_scrape_all',
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        scraper.showMessage('Масовото обновяване е стартирано в background процес', 'success');
                        // Start monitoring progress
                        scraper.monitorBulkProgress();
                    } else {
                        scraper.showMessage('Грешка при стартиране: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    scraper.showMessage('Грешка при свързване със сървъра', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        monitorBulkProgress: function() {
            var progressInterval = setInterval(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_bulk_scrape_progress',
                        nonce: parfumeScraperAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var progress = response.data;
                            $('.bulk-progress').show();
                            $('.bulk-progress .progress-bar').css('width', progress.percentage + '%');
                            $('.bulk-progress .progress-text').text(progress.current + ' от ' + progress.total);
                            
                            if (progress.completed) {
                                clearInterval(progressInterval);
                                $('.bulk-progress').hide();
                                scraper.showMessage('Масовото обновяване приключи успешно!', 'success');
                                scraper.refreshMonitorData();
                            }
                        }
                    }
                });
            }, 2000);
        },

        openSchemaConfigurator: function(storeId) {
            // Create modal for schema configuration
            var modal = this.createSchemaModal(storeId);
            $('body').append(modal);
            $('.schema-configurator-modal').fadeIn();
            
            // Load existing schema if available
            this.loadSchemaConfig(storeId);
        },

        createSchemaModal: function(storeId) {
            var html = '<div class="schema-configurator-modal" style="display: none;">';
            html += '<div class="schema-modal-content">';
            html += '<div class="schema-modal-header">';
            html += '<h3>Конфигуриране на Schema за магазин</h3>';
            html += '<button class="close-schema-modal">&times;</button>';
            html += '</div>';
            html += '<div class="schema-modal-body">';
            
            // URL for testing
            html += '<div class="schema-section">';
            html += '<label>Тестов URL:</label>';
            html += '<input type="url" id="schema-test-url" placeholder="https://example.com/product">';
            html += '<button class="button auto-detect-schema">Автоматично откриване</button>';
            html += '</div>';
            
            // Selectors configuration
            html += '<div class="schema-section">';
            html += '<h4>CSS Селектори:</h4>';
            html += '<div class="selector-group">';
            html += '<label>Цена:</label>';
            html += '<input type="text" name="price_selector" placeholder=".price, .current-price">';
            html += '</div>';
            html += '<div class="selector-group">';
            html += '<label>Стара цена:</label>';
            html += '<input type="text" name="old_price_selector" placeholder=".old-price, .original-price">';
            html += '</div>';
            html += '<div class="selector-group">';
            html += '<label>Наличност:</label>';
            html += '<input type="text" name="availability_selector" placeholder=".availability, .stock-status">';
            html += '</div>';
            html += '<div class="selector-group">';
            html += '<label>Доставка:</label>';
            html += '<input type="text" name="shipping_selector" placeholder=".shipping-info, .delivery">';
            html += '</div>';
            html += '<div class="selector-group">';
            html += '<label>Разфасовки:</label>';
            html += '<input type="text" name="variants_selector" placeholder=".product-variants option, .size-options">';
            html += '</div>';
            html += '</div>';
            
            // Test results
            html += '<div class="schema-section">';
            html += '<button class="button test-schema">Тествай Schema</button>';
            html += '<div class="schema-test-results"></div>';
            html += '</div>';
            
            html += '</div>';
            html += '<div class="schema-modal-footer">';
            html += '<button class="button button-primary save-schema-config" data-store-id="' + storeId + '">Запази Schema</button>';
            html += '<button class="button cancel-schema-config">Отказ</button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            return html;
        },

        loadSchemaConfig: function(storeId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_store_schema',
                    store_id: storeId,
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var schema = response.data;
                        $('input[name="price_selector"]').val(schema.price_selector || '');
                        $('input[name="old_price_selector"]').val(schema.old_price_selector || '');
                        $('input[name="availability_selector"]').val(schema.availability_selector || '');
                        $('input[name="shipping_selector"]').val(schema.shipping_selector || '');
                        $('input[name="variants_selector"]').val(schema.variants_selector || '');
                    }
                }
            });
        },

        saveSchemaConfig: function() {
            var storeId = $('.save-schema-config').data('store-id');
            var schema = {
                price_selector: $('input[name="price_selector"]').val(),
                old_price_selector: $('input[name="old_price_selector"]').val(),
                availability_selector: $('input[name="availability_selector"]').val(),
                shipping_selector: $('input[name="shipping_selector"]').val(),
                variants_selector: $('input[name="variants_selector"]').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_store_schema',
                    store_id: storeId,
                    schema: schema,
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        scraper.showMessage('Schema е запазена успешно!', 'success');
                        $('.schema-configurator-modal').fadeOut();
                    } else {
                        scraper.showMessage('Грешка при запазване: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    scraper.showMessage('Грешка при свързване със сървъра', 'error');
                }
            });
        },

        testSchemaConfig: function() {
            var url = $('#schema-test-url').val();
            if (!url) {
                alert('Моля въведете URL за тестване');
                return;
            }

            var schema = {
                price_selector: $('input[name="price_selector"]').val(),
                old_price_selector: $('input[name="old_price_selector"]').val(),
                availability_selector: $('input[name="availability_selector"]').val(),
                shipping_selector: $('input[name="shipping_selector"]').val(),
                variants_selector: $('input[name="variants_selector"]').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_schema_config',
                    url: url,
                    schema: schema,
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var html = '<h4>Резултати от тестването:</h4>';
                        var data = response.data;
                        
                        html += '<table class="schema-test-table">';
                        html += '<tr><td>Цена:</td><td>' + (data.price || 'Не е открито') + '</td></tr>';
                        html += '<tr><td>Стара цена:</td><td>' + (data.old_price || 'Не е открито') + '</td></tr>';
                        html += '<tr><td>Наличност:</td><td>' + (data.availability || 'Не е открито') + '</td></tr>';
                        html += '<tr><td>Доставка:</td><td>' + (data.shipping || 'Не е открито') + '</td></tr>';
                        html += '<tr><td>Разфасовки:</td><td>' + (data.variants ? data.variants.join(', ') : 'Не са открити') + '</td></tr>';
                        html += '</table>';
                        
                        $('.schema-test-results').html(html);
                    } else {
                        $('.schema-test-results').html('<p class="error">Грешка: ' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $('.schema-test-results').html('<p class="error">Грешка при свързване със сървъра</p>');
                }
            });
        },

        autoDetectSchema: function(url) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'auto_detect_schema',
                    url: url,
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var selectors = response.data;
                        
                        if (selectors.price_selector) {
                            $('input[name="price_selector"]').val(selectors.price_selector);
                        }
                        if (selectors.old_price_selector) {
                            $('input[name="old_price_selector"]').val(selectors.old_price_selector);
                        }
                        if (selectors.availability_selector) {
                            $('input[name="availability_selector"]').val(selectors.availability_selector);
                        }
                        if (selectors.shipping_selector) {
                            $('input[name="shipping_selector"]').val(selectors.shipping_selector);
                        }
                        if (selectors.variants_selector) {
                            $('input[name="variants_selector"]').val(selectors.variants_selector);
                        }
                        
                        scraper.showMessage('Селекторите са открити автоматично', 'success');
                    } else {
                        scraper.showMessage('Не могат да бъдат открити автоматично селектори', 'warning');
                    }
                },
                error: function() {
                    scraper.showMessage('Грешка при автоматично откриване', 'error');
                }
            });
        },

        refreshMonitorData: function() {
            if ($('.scraper-monitor-table').length > 0) {
                location.reload();
            }
        },

        toggleScraper: function(postId, storeIndex, enabled) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'toggle_scraper',
                    post_id: postId,
                    store_index: storeIndex,
                    enabled: enabled,
                    nonce: parfumeScraperAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var message = enabled ? 'Scraper е активиран' : 'Scraper е деактивиран';
                        scraper.showMessage(message, 'success');
                    } else {
                        scraper.showMessage('Грешка при промяна на настройката', 'error');
                    }
                },
                error: function() {
                    scraper.showMessage('Грешка при свързване със сървъра', 'error');
                }
            });
        },

        initializeMonitoring: function() {
            // Auto-refresh monitor data every 30 seconds if on monitor page
            if ($('.scraper-monitor-table').length > 0) {
                setInterval(function() {
                    scraper.refreshMonitorData();
                }, 30000);
            }
        },

        showMessage: function(message, type) {
            var messageClass = 'scraper-message-' + type;
            var messageHtml = '<div class="notice notice-' + type + ' is-dismissible scraper-message ' + messageClass + '">';
            messageHtml += '<p>' + message + '</p>';
            messageHtml += '</div>';
            
            if ($('.wrap h1').length > 0) {
                $('.wrap h1').after(messageHtml);
            } else {
                $('body').append(messageHtml);
            }
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('.' + messageClass).fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Event handlers for modal
    $(document).on('click', '.close-schema-modal, .cancel-schema-config', function() {
        $('.schema-configurator-modal').fadeOut(function() {
            $(this).remove();
        });
    });

    // Initialize when DOM is ready
    $(document).ready(function() {
        scraper.init();
    });

    // Make scraper object available globally
    window.parfumeScraper = scraper;

})(jQuery);