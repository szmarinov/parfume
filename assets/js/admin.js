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
            this.initStoreModal();
            this.initStoreMetaBox();
            this.initScraperInterface();
            this.initTestTool();
            this.initComparison();
            this.initImportExport();
            this.initNotifications();
            this.initFormValidation();
            this.initMediaUploader();
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
            
            // Add new store button
            $(document).on('click', '#add-new-store', function(e) {
                e.preventDefault();
                self.openStoreModal();
            });
            
            // Edit store button
            $(document).on('click', '.edit-store', function(e) {
                e.preventDefault();
                var storeId = $(this).data('store-id');
                self.editStore(storeId);
            });
            
            // Delete store button
            $(document).on('click', '.delete-store', function(e) {
                e.preventDefault();
                var storeId = $(this).data('store-id');
                self.deleteStore(storeId);
            });
            
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
            
            // Store logo upload (legacy support)
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
                        nonce: parfume_catalog_admin_ajax.nonce,
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
         * Initialize store modal functionality
         */
        initStoreModal: function() {
            var self = this;
            
            // Close modal events
            $(document).on('click', '.store-modal-close, .cancel-store-btn, #store-modal-overlay', function(e) {
                if (e.target === this || $(e.target).hasClass('store-modal-close') || $(e.target).hasClass('cancel-store-btn')) {
                    self.closeStoreModal();
                }
            });
            
            // Save store
            $(document).on('click', '.save-store-btn', function(e) {
                e.preventDefault();
                self.saveStore();
            });
            
            // Test schema
            $(document).on('click', '.test-schema-btn', function(e) {
                e.preventDefault();
                self.testSchema();
            });
            
            // Close modal on ESC
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#store-modal').is(':visible')) {
                    self.closeStoreModal();
                }
            });
        },

        /**
         * Initialize store meta box functionality
         */
        initStoreMetaBox: function() {
            var self = this;
            
            // Add store to post
            $(document).on('click', '.add-store-to-post', function(e) {
                e.preventDefault();
                self.addStoreToPost();
            });
            
            // Remove store from post
            $(document).on('click', '.remove-store', function(e) {
                e.preventDefault();
                self.removeStoreFromPost($(this));
            });
            
            // Move store up/down
            $(document).on('click', '.move-store-up', function(e) {
                e.preventDefault();
                self.moveStoreUp($(this));
            });
            
            $(document).on('click', '.move-store-down', function(e) {
                e.preventDefault();
                self.moveStoreDown($(this));
            });
            
            // Manual scrape
            $(document).on('click', '.manual-scrape', function(e) {
                e.preventDefault();
                var $button = $(this);
                var postId = $button.data('post-id');
                var storeId = $button.data('store-id');
                self.manualScrape($button, postId, storeId);
            });
            
            // Make stores sortable in post meta box
            if ($('#selected-stores-list').length) {
                $('#selected-stores-list').sortable({
                    handle: '.store-item-header',
                    placeholder: 'post-store-item-placeholder',
                    opacity: 0.7
                });
            }
        },

        /**
         * Initialize media uploader
         */
        initMediaUploader: function() {
            var self = this;
            
            // Upload logo button in modal
            $(document).on('click', '.upload-logo-btn', function(e) {
                e.preventDefault();
                self.openMediaUploader();
            });
            
            // Remove logo button
            $(document).on('click', '.remove-logo-btn', function(e) {
                e.preventDefault();
                self.removeLogo();
            });
        },

        /**
         * Open store modal
         */
        openStoreModal: function(storeId) {
            // Reset form
            $('#store-form')[0].reset();
            $('#store-id').val(storeId || '');
            
            if (storeId) {
                $('#store-modal-title').text('Редактиране на магазин');
                this.loadStoreData(storeId);
            } else {
                $('#store-modal-title').text('Добави нов магазин');
            }
            
            $('#logo-preview').empty();
            $('.remove-logo-btn').hide();
            
            // Show modal
            $('#store-modal, #store-modal-overlay').fadeIn(300);
            $('body').addClass('modal-open');
            
            // Focus first input
            setTimeout(function() {
                $('#store-name').focus();
            }, 350);
        },

        /**
         * Close store modal
         */
        closeStoreModal: function() {
            $('#store-modal, #store-modal-overlay').fadeOut(300);
            $('body').removeClass('modal-open');
            $('#schema-test-results').hide();
        },

        /**
         * Load store data for editing
         */
        loadStoreData: function(storeId) {
            // This would load existing store data
            // Implementation depends on how store data is passed to frontend
        },

        /**
         * Save store
         */
        saveStore: function() {
            var self = this;
            var $btn = $('.save-store-btn');
            
            // Validate required fields
            if (!$('#store-name').val().trim()) {
                self.showNotification('Моля въведете име на магазин', 'error');
                $('#store-name').focus();
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).text('Запазване...');
            
            // Prepare data
            var data = {
                action: 'parfume_add_store',
                nonce: parfume_catalog_admin_ajax.nonce,
                store_id: $('#store-id').val(),
                store_name: $('#store-name').val(),
                store_url: $('#store-url').val(),
                store_logo: $('#store-logo').val(),
                store_active: $('#store-active').is(':checked') ? 1 : 0,
                price_selector: $('#price-selector').val(),
                old_price_selector: $('#old-price-selector').val(),
                availability_selector: $('#availability-selector').val(),
                delivery_selector: $('#delivery-selector').val(),
                variants_selector: $('#variants-selector').val()
            };
            
            // Send AJAX request
            $.post(parfume_catalog_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        self.closeStoreModal();
                        
                        // Reload page to show updated store list
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        self.showNotification(response.data || 'Възникна грешка при запазването', 'error');
                    }
                })
                .fail(function() {
                    self.showNotification('Възникна грешка при връзката със сървъра', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Запази');
                });
        },

        /**
         * Edit store
         */
        editStore: function(storeId) {
            this.openStoreModal(storeId);
        },

        /**
         * Delete store
         */
        deleteStore: function(storeId) {
            var self = this;
            
            if (!confirm(parfume_catalog_admin_ajax.strings.confirm_delete || 'Сигурни ли сте, че искате да изтриете този магазин?')) {
                return;
            }
            
            var data = {
                action: 'parfume_delete_store',
                nonce: parfume_catalog_admin_ajax.nonce,
                store_id: storeId
            };
            
            $.post(parfume_catalog_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        self.showNotification(response.data, 'success');
                        $('tr[data-store-id="' + storeId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        self.showNotification(response.data || 'Възникна грешка при изтриването', 'error');
                    }
                })
                .fail(function() {
                    self.showNotification('Възникна грешка при връзката със сървъра', 'error');
                });
        },

        /**
         * Open media uploader
         */
        openMediaUploader: function() {
            var frame = wp.media({
                title: 'Избери лого на магазин',
                button: { text: 'Избери лого' },
                multiple: false,
                library: { type: 'image' }
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                
                // Set logo URL
                $('#store-logo').val(attachment.url);
                
                // Show preview
                $('#logo-preview').html('<img src="' + attachment.url + '" alt="Logo" style="max-width: 100px; max-height: 60px;" />');
                
                // Show remove button
                $('.remove-logo-btn').show();
            });
            
            frame.open();
        },

        /**
         * Remove logo
         */
        removeLogo: function() {
            $('#store-logo').val('');
            $('#logo-preview').empty();
            $('.remove-logo-btn').hide();
        },

        /**
         * Test schema
         */
        testSchema: function() {
            var self = this;
            var $btn = $('.test-schema-btn');
            var testUrl = $('#test-url').val();
            
            if (!testUrl) {
                self.showNotification('Моля въведете URL за тестване', 'error');
                $('#test-url').focus();
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).text('Тестване...');
            $('#schema-test-results').hide();
            
            // Prepare data
            var data = {
                action: 'parfume_test_scraper_url',
                nonce: parfume_catalog_admin_ajax.nonce,
                test_url: testUrl,
                price_selector: $('#price-selector').val(),
                old_price_selector: $('#old-price-selector').val(),
                availability_selector: $('#availability-selector').val(),
                delivery_selector: $('#delivery-selector').val(),
                variants_selector: $('#variants-selector').val()
            };
            
            // Send AJAX request
            $.post(parfume_catalog_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        self.displayTestResults(response.data);
                    } else {
                        self.showNotification(response.data || 'Неуспешен тест на schema', 'error');
                    }
                })
                .fail(function() {
                    self.showNotification('Възникна грешка при тестването', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Тествай');
                });
        },

        /**
         * Display test results
         */
        displayTestResults: function(data) {
            var html = '<h4>Резултати от тестването:</h4>';
            
            if (data.success) {
                html += '<div class="parfume-notice"><p><strong>Успешен тест!</strong> ' + data.message + '</p></div>';
                
                if (data.data) {
                    html += '<div class="test-data">';
                    html += '<h5>Извлечени данни:</h5>';
                    html += '<ul>';
                    
                    if (data.data.price) {
                        html += '<li><strong>Цена:</strong> ' + data.data.price + '</li>';
                    }
                    if (data.data.old_price) {
                        html += '<li><strong>Стара цена:</strong> ' + data.data.old_price + '</li>';
                    }
                    if (data.data.availability) {
                        html += '<li><strong>Наличност:</strong> ' + data.data.availability + '</li>';
                    }
                    if (data.data.delivery) {
                        html += '<li><strong>Доставка:</strong> ' + data.data.delivery + '</li>';
                    }
                    if (data.data.variants && data.data.variants.length) {
                        html += '<li><strong>Варианти:</strong> ' + data.data.variants.join(', ') + '</li>';
                    }
                    
                    html += '</ul>';
                    html += '</div>';
                }
            } else {
                html += '<div class="parfume-notice notice-error"><p><strong>Неуспешен тест:</strong> ' + data.message + '</p></div>';
            }
            
            $('#schema-test-results').html(html).slideDown();
        },

        /**
         * Add store to post
         */
        addStoreToPost: function() {
            var storeId = $('#add-store-select').val();
            if (!storeId) {
                return;
            }
            
            // Check if store is already added
            if ($('.post-store-item[data-store-id="' + storeId + '"]').length) {
                this.showNotification('Този магазин вече е добавен', 'warning');
                return;
            }
            
            // Disable option in select
            $('#add-store-select option[value="' + storeId + '"]').prop('disabled', true);
            $('#add-store-select').val('');
            
            // Add store item
            var storeName = $('#add-store-select option[value="' + storeId + '"]').text();
            this.addStoreItem(storeId, storeName);
        },

        /**
         * Add store item to list
         */
        addStoreItem: function(storeId, storeName) {
            var template = `
                <div class="post-store-item" data-store-id="${storeId}">
                    <div class="store-item-header">
                        <div class="store-info">
                            <strong>${storeName}</strong>
                        </div>
                        <div class="store-controls">
                            <button type="button" class="button-link move-store-up" title="Нагоре">↑</button>
                            <button type="button" class="button-link move-store-down" title="Надолу">↓</button>
                            <button type="button" class="button-link remove-store" title="Премахни">×</button>
                        </div>
                    </div>
                    <div class="store-item-fields">
                        <input type="hidden" name="parfume_stores[${storeId}][store_id]" value="${storeId}" />
                        <label>
                            Product URL:
                            <input type="url" name="parfume_stores[${storeId}][product_url]" value="" class="widefat" placeholder="https://example.com/product/..." />
                        </label>
                        <label>
                            Affiliate URL:
                            <input type="url" name="parfume_stores[${storeId}][affiliate_url]" value="" class="widefat" placeholder="https://affiliate.link/..." />
                        </label>
                        <label>
                            Promo Code:
                            <input type="text" name="parfume_stores[${storeId}][promo_code]" value="" class="widefat" placeholder="DISCOUNT10" />
                        </label>
                        <label>
                            Promo Code Info:
                            <input type="text" name="parfume_stores[${storeId}][promo_code_info]" value="" class="widefat" placeholder="10% отстъпка" />
                        </label>
                    </div>
                </div>
            `;
            
            $('#selected-stores-list').append(template);
        },

        /**
         * Remove store from post
         */
        removeStoreFromPost: function($button) {
            var $item = $button.closest('.post-store-item');
            var storeId = $item.data('store-id');
            
            // Re-enable option in select
            $('#add-store-select option[value="' + storeId + '"]').prop('disabled', false);
            
            // Remove item
            $item.fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Move store up
         */
        moveStoreUp: function($button) {
            var $item = $button.closest('.post-store-item');
            var $prev = $item.prev('.post-store-item');
            
            if ($prev.length) {
                $item.fadeOut(200, function() {
                    $item.insertBefore($prev).fadeIn(200);
                });
            }
        },

        /**
         * Move store down
         */
        moveStoreDown: function($button) {
            var $item = $button.closest('.post-store-item');
            var $next = $item.next('.post-store-item');
            
            if ($next.length) {
                $item.fadeOut(200, function() {
                    $item.insertAfter($next).fadeIn(200);
                });
            }
        },

        /**
         * Manual scrape
         */
        manualScrape: function($button, postId, storeId) {
            var self = this;
            
            // Show loading state
            $button.prop('disabled', true).text('Скрейпване...');
            
            var data = {
                action: 'parfume_manual_scrape',
                nonce: parfume_catalog_admin_ajax.nonce,
                store_id: storeId,
                post_id: postId
            };
            
            $.post(parfume_catalog_admin_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        self.showNotification('Скрейпването е завършено успешно', 'success');
                        // Update scraper status
                        $button.siblings('.scraper-status').text('Последно: току що');
                    } else {
                        self.showNotification(response.data || 'Грешка при скрейпването', 'error');
                    }
                })
                .fail(function() {
                    self.showNotification('Възникна грешка при скрейпването', 'error');
                })
                .always(function() {
                    $button.prop('disabled', false).text('Ръчно обновяване');
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
         * Add new store (legacy support)
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
                    nonce: parfume_catalog_admin_ajax.nonce,
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
                    nonce: parfume_catalog_admin_ajax.nonce
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
                        nonce: parfume_catalog_admin_ajax.nonce
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
                    nonce: parfume_catalog_admin_ajax.nonce
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
                    nonce: parfume_catalog_admin_ajax.nonce,
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
                    nonce: parfume_catalog_admin_ajax.nonce,
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
            formData.append('nonce', parfume_catalog_admin_ajax.nonce);
            
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
                    nonce: parfume_catalog_admin_ajax.nonce,
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
            
            // Remove existing notices
            $('.parfume-notice').remove();
            
            // Add notice
            if ($('.parfume-admin-header').length) {
                $('.parfume-admin-header').after($notice);
            } else if ($('.wrap').length) {
                $('.wrap').prepend($notice);
            } else {
                $('body').prepend($notice);
            }
            
            // Auto-hide success notices
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
            
            // Scroll to notice
            if ($notice.offset()) {
                $('html, body').animate({
                    scrollTop: $notice.offset().top - 100
                }, 300);
            }
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
        if (typeof parfume_catalog_admin_ajax !== 'undefined') {
            ParfumeAdmin.init();
        }
    });

})(jQuery);