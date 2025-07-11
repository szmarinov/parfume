/**
 * Parfume Catalog Admin JavaScript
 * 
 * Административна JavaScript функционалност
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Глобален admin обект
    window.parfumeAdmin = {
        
        // Настройки
        settings: {
            ajaxUrl: window.parfume_catalog_admin_ajax ? window.parfume_catalog_admin_ajax.ajax_url : ajaxurl,
            nonce: window.parfume_catalog_admin_ajax ? window.parfume_catalog_admin_ajax.nonce : '',
            strings: window.parfume_catalog_admin_ajax ? window.parfume_catalog_admin_ajax.strings : {}
        },

        // Инициализация
        init: function() {
            this.initTabs();
            this.initForms();
            this.initModals();
            this.initFileUploads();
            this.initDragDrop();
            this.initTooltips();
            this.initConfirmations();
            this.initStores();
            this.initScraper();
            this.initComments();
            this.initMetaBoxes();
            this.initSettings();
            
            console.log('Parfume Admin initialized');
        },

        // Tab navigation
        initTabs: function() {
            $(document).on('click', '.nav-tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var target = $tab.attr('href') || $tab.data('target');
                
                if (!target) return;
                
                // Remove active class from all tabs and contents
                $tab.siblings('.nav-tab').removeClass('nav-tab-active');
                $tab.addClass('nav-tab-active');
                
                // Hide all tab contents and show target
                $('.tab-content').removeClass('active').hide();
                $(target).addClass('active').show();
                
                // Trigger custom event
                $(document).trigger('parfume:tab:changed', [target, $tab]);
            });

            // Modal tabs
            $(document).on('click', '.modal-tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var target = $tab.data('target');
                
                if (!target) return;
                
                $tab.siblings('.modal-tab').removeClass('active');
                $tab.addClass('active');
                
                $tab.closest('.store-modal').find('.modal-tab-content').removeClass('active');
                $(target).addClass('active');
            });

            // Initialize first tab
            $('.nav-tab.nav-tab-active').trigger('click');
        },

        // Form handling
        initForms: function() {
            var self = this;

            // Generic form submission with AJAX
            $(document).on('submit', '.parfume-ajax-form', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });

            // Auto-save forms
            $(document).on('change input', '.parfume-auto-save', function() {
                var $form = $(this).closest('form');
                clearTimeout($form.data('autosave-timeout'));
                
                $form.data('autosave-timeout', setTimeout(function() {
                    self.submitForm($form, true);
                }, 2000));
            });

            // Settings form validation
            $(document).on('submit', '.parfume-settings-form', function(e) {
                var valid = self.validateSettingsForm($(this));
                if (!valid) {
                    e.preventDefault();
                }
            });

            // Dynamic field addition
            $(document).on('click', '.add-dynamic-field', function(e) {
                e.preventDefault();
                self.addDynamicField($(this));
            });

            $(document).on('click', '.remove-dynamic-field', function(e) {
                e.preventDefault();
                self.removeDynamicField($(this));
            });
        },

        // Submit form via AJAX
        submitForm: function($form, isAutoSave) {
            var self = this;
            var $submitBtn = $form.find('[type="submit"]');
            var originalText = $submitBtn.val() || $submitBtn.text();
            
            // Prevent double submission
            if ($form.hasClass('submitting')) {
                return false;
            }
            
            $form.addClass('submitting');
            
            if (!isAutoSave) {
                $submitBtn.prop('disabled', true);
                if ($submitBtn.is('input')) {
                    $submitBtn.val(self.settings.strings.saving || 'Запазване...');
                } else {
                    $submitBtn.html('<span class="spinner"></span> ' + (self.settings.strings.saving || 'Запазване...'));
                }
            }

            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        if (!isAutoSave) {
                            self.showNotice(response.data.message || self.settings.strings.saved || 'Запазено успешно!', 'success');
                        }
                        
                        // Trigger custom event
                        $form.trigger('parfume:form:success', [response.data]);
                    } else {
                        self.showNotice(response.data || self.settings.strings.error || 'Възникна грешка!', 'error');
                        $form.trigger('parfume:form:error', [response.data]);
                    }
                },
                error: function() {
                    self.showNotice(self.settings.strings.ajax_error || 'Грешка при заявката!', 'error');
                },
                complete: function() {
                    $form.removeClass('submitting');
                    
                    if (!isAutoSave) {
                        $submitBtn.prop('disabled', false);
                        if ($submitBtn.is('input')) {
                            $submitBtn.val(originalText);
                        } else {
                            $submitBtn.text(originalText);
                        }
                    }
                }
            });
        },

        // Form validation
        validateSettingsForm: function($form) {
            var isValid = true;
            var self = this;
            
            // Remove previous error highlights
            $form.find('.error').removeClass('error');
            
            // Required field validation
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    self.showNotice(self.settings.strings.required_field || 'Моля, попълнете задължителните полета!', 'error');
                    isValid = false;
                }
            });
            
            // URL validation
            $form.find('input[type="url"]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (value && !self.isValidUrl(value)) {
                    $field.addClass('error');
                    self.showNotice(self.settings.strings.invalid_url || 'Невалиден URL адрес!', 'error');
                    isValid = false;
                }
            });
            
            // Email validation
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (value && !self.isValidEmail(value)) {
                    $field.addClass('error');
                    self.showNotice(self.settings.strings.invalid_email || 'Невалиден email адрес!', 'error');
                    isValid = false;
                }
            });
            
            return isValid;
        },

        // Modal handling
        initModals: function() {
            var self = this;

            // Open modal
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                self.openModal(modalId, $(this));
            });

            // Close modal
            $(document).on('click', '.modal-close, .modal-overlay', function(e) {
                if (e.target === this) {
                    self.closeModal($(this).closest('.modal-overlay, .store-modal-overlay'));
                }
            });

            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.modal-overlay.active, .store-modal-overlay.active').each(function() {
                        self.closeModal($(this));
                    });
                }
            });
        },

        // Open modal
        openModal: function(modalId, $trigger) {
            var $modal = $('#' + modalId);
            
            if (!$modal.length) return;
            
            // Load data if needed
            var entityId = $trigger ? $trigger.data('entity-id') : null;
            if (entityId) {
                this.loadModalData($modal, entityId);
            }
            
            $modal.addClass('active');
            $('body').addClass('modal-open');
            
            // Focus first input
            setTimeout(function() {
                $modal.find('input, select, textarea').first().focus();
            }, 300);
        },

        // Close modal
        closeModal: function($modal) {
            $modal.removeClass('active');
            $('body').removeClass('modal-open');
            
            // Reset form if exists
            var $form = $modal.find('form');
            if ($form.length) {
                $form[0].reset();
                $form.find('.error').removeClass('error');
            }
        },

        // Load modal data
        loadModalData: function($modal, entityId) {
            var self = this;
            var entityType = $modal.data('entity-type') || 'store';
            
            $modal.addClass('loading');
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_get_' + entityType,
                    nonce: self.settings.nonce,
                    id: entityId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.populateModal($modal, response.data);
                    }
                },
                error: function() {
                    self.showNotice(self.settings.strings.load_error || 'Грешка при зареждане на данните!', 'error');
                },
                complete: function() {
                    $modal.removeClass('loading');
                }
            });
        },

        // Populate modal with data
        populateModal: function($modal, data) {
            $modal.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = data[name];
                
                if (name && value !== undefined) {
                    if ($field.is(':checkbox')) {
                        $field.prop('checked', !!value);
                    } else {
                        $field.val(value);
                    }
                }
            });
            
            // Handle special fields
            if (data.logo) {
                var $logoArea = $modal.find('.logo-upload-area');
                $logoArea.addClass('has-logo');
                $logoArea.find('.logo-preview').attr('src', data.logo).show();
                $logoArea.find('.upload-text').hide();
            }
        },

        // File uploads
        initFileUploads: function() {
            var self = this;

            // WordPress media uploader
            $(document).on('click', '.upload-logo-btn', function(e) {
                e.preventDefault();
                self.openMediaUploader($(this));
            });

            // Remove logo
            $(document).on('click', '.remove-logo', function(e) {
                e.preventDefault();
                self.removeLogo($(this));
            });

            // Drag and drop
            $(document).on('dragover dragenter', '.logo-upload-area', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            $(document).on('dragleave dragend drop', '.logo-upload-area', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                
                if (e.type === 'drop') {
                    var files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0) {
                        self.handleFileUpload(files[0], $(this));
                    }
                }
            });
        },

        // Open WordPress media uploader
        openMediaUploader: function($button) {
            var self = this;
            
            if (typeof wp === 'undefined' || !wp.media) {
                self.showNotice('WordPress media library не е налична!', 'error');
                return;
            }

            var mediaUploader = wp.media({
                title: 'Избери лого',
                button: {
                    text: 'Използвай това изображение'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                self.setLogo($button.closest('.logo-upload-area'), attachment.url);
            });

            mediaUploader.open();
        },

        // Set logo
        setLogo: function($area, logoUrl) {
            $area.addClass('has-logo');
            
            var $preview = $area.find('.logo-preview');
            if (!$preview.length) {
                $preview = $('<img class="logo-preview" />');
                $area.find('.upload-icon').after($preview);
            }
            
            $preview.attr('src', logoUrl).show();
            $area.find('.upload-text').hide();
            $area.find('input[name*="logo"]').val(logoUrl);
            
            if (!$area.find('.remove-logo').length) {
                $area.append('<button type="button" class="remove-logo">Премахни</button>');
            }
        },

        // Remove logo
        removeLogo: function($button) {
            var $area = $button.closest('.logo-upload-area');
            
            $area.removeClass('has-logo');
            $area.find('.logo-preview').hide();
            $area.find('.upload-text').show();
            $area.find('input[name*="logo"]').val('');
            $button.remove();
        },

        // Handle file upload
        handleFileUpload: function(file, $area) {
            var self = this;
            
            if (!file.type.match('image.*')) {
                self.showNotice('Моля, изберете изображение!', 'error');
                return;
            }
            
            var formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'parfume_upload_logo');
            formData.append('nonce', self.settings.nonce);
            
            $area.addClass('uploading');
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.setLogo($area, response.data.url);
                        self.showNotice('Логото е качено успешно!', 'success');
                    } else {
                        self.showNotice(response.data || 'Грешка при качване!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при качване на файла!', 'error');
                },
                complete: function() {
                    $area.removeClass('uploading');
                }
            });
        },

        // Drag and drop functionality
        initDragDrop: function() {
            // Make lists sortable
            $('.sortable-list').sortable({
                handle: '.drag-handle',
                placeholder: 'sortable-placeholder',
                helper: 'clone',
                opacity: 0.8,
                update: function(event, ui) {
                    var $list = $(this);
                    var order = $list.sortable('toArray', { attribute: 'data-id' });
                    
                    // Save order
                    parfumeAdmin.saveSortOrder($list, order);
                }
            });

            // Stores drag and drop
            $('#post-stores-list').sortable({
                handle: '.store-drag-handle',
                placeholder: 'store-placeholder',
                update: function(event, ui) {
                    parfumeAdmin.updateStoresOrder();
                }
            });
        },

        // Save sort order
        saveSortOrder: function($list, order) {
            var listType = $list.data('type');
            var postId = $list.data('post-id');
            
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_save_order',
                    nonce: this.settings.nonce,
                    type: listType,
                    post_id: postId,
                    order: order
                },
                success: function(response) {
                    if (!response.success) {
                        console.warn('Error saving order:', response.data);
                    }
                }
            });
        },

        // Tooltips
        initTooltips: function() {
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var $element = $(this);
                var text = $element.data('tooltip');
                
                if (!text || $element.find('.admin-tooltip').length) return;
                
                var $tooltip = $('<div class="admin-tooltip">' + text + '</div>');
                $element.append($tooltip);
                
                // Position tooltip
                setTimeout(function() {
                    var rect = $element[0].getBoundingClientRect();
                    var tooltipRect = $tooltip[0].getBoundingClientRect();
                    
                    if (rect.left + tooltipRect.width > window.innerWidth) {
                        $tooltip.addClass('tooltip-left');
                    }
                    
                    if (rect.top - tooltipRect.height < 0) {
                        $tooltip.addClass('tooltip-bottom');
                    }
                    
                    $tooltip.addClass('visible');
                }, 10);
            });

            $(document).on('mouseleave', '[data-tooltip]', function() {
                $(this).find('.admin-tooltip').remove();
            });
        },

        // Confirmation dialogs
        initConfirmations: function() {
            $(document).on('click', '[data-confirm]', function(e) {
                var message = $(this).data('confirm') || 'Сигурни ли сте?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        // Stores functionality
        initStores: function() {
            var self = this;

            // Add new store
            $(document).on('click', '.add-store-btn', function(e) {
                e.preventDefault();
                self.openModal('store-modal');
            });

            // Edit store
            $(document).on('click', '.edit-store-btn', function(e) {
                e.preventDefault();
                var storeId = $(this).data('store-id');
                self.openModal('store-modal', $(this));
            });

            // Delete store
            $(document).on('click', '.delete-store-btn', function(e) {
                e.preventDefault();
                if (confirm(self.settings.strings.confirm_delete || 'Сигурни ли сте?')) {
                    self.deleteStore($(this).data('store-id'));
                }
            });

            // Test store URL
            $(document).on('click', '.test-store-url', function(e) {
                e.preventDefault();
                self.testStoreUrl($(this));
            });

            // Store form submission
            $(document).on('submit', '#store-form', function(e) {
                e.preventDefault();
                self.saveStore($(this));
            });
        },

        // Save store
        saveStore: function($form) {
            var self = this;
            var $modal = $form.closest('.store-modal-overlay');
            var $submitBtn = $form.find('[type="submit"]');
            
            $submitBtn.prop('disabled', true).text('Запазване...');
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Магазинът е запазен успешно!', 'success');
                        self.closeModal($modal);
                        
                        // Reload stores if on stores page
                        if ($('.stores-grid').length) {
                            location.reload();
                        }
                    } else {
                        self.showNotice(response.data || 'Грешка при запазване!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Запази');
                }
            });
        },

        // Delete store
        deleteStore: function(storeId) {
            var self = this;
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_delete_store',
                    nonce: self.settings.nonce,
                    store_id: storeId
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Магазинът е изтрит успешно!', 'success');
                        $('[data-store-id="' + storeId + '"]').closest('.store-card').fadeOut();
                    } else {
                        self.showNotice(response.data || 'Грешка при изтриване!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                }
            });
        },

        // Test store URL
        testStoreUrl: function($button) {
            var self = this;
            var url = $button.siblings('input').val();
            var storeId = $button.data('store-id');
            
            if (!url) {
                self.showNotice('Моля, въведете URL за тестване!', 'warning');
                return;
            }
            
            $button.prop('disabled', true).text('Тестване...');
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_test_store_url',
                    nonce: self.settings.nonce,
                    url: url,
                    store_id: storeId
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('URL-ът е достъпен!', 'success');
                        
                        if (response.data.scraped_data) {
                            // Show scraped data preview
                            self.showScrapedDataPreview(response.data.scraped_data);
                        }
                    } else {
                        self.showNotice(response.data || 'URL-ът не е достъпен!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при тестване!', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Тествай');
                }
            });
        },

        // Show scraped data preview
        showScrapedDataPreview: function(data) {
            var content = '<div class="scraped-data-preview">';
            content += '<h4>Извлечени данни:</h4>';
            
            if (data.price) {
                content += '<p><strong>Цена:</strong> ' + data.price + '</p>';
            }
            
            if (data.availability) {
                content += '<p><strong>Наличност:</strong> ' + data.availability + '</p>';
            }
            
            if (data.variants && data.variants.length > 0) {
                content += '<p><strong>Варианти:</strong> ' + data.variants.length + '</p>';
            }
            
            content += '</div>';
            
            // Show in modal or notification
            this.showNotice(content, 'info', 10000);
        },

        // Scraper functionality
        initScraper: function() {
            var self = this;

            // Manual scraper run
            $(document).on('click', '.run-scraper-btn', function(e) {
                e.preventDefault();
                var type = $(this).data('type') || 'full';
                self.runScraper(type);
            });

            // Test single URL
            $(document).on('click', '.test-single-url', function(e) {
                e.preventDefault();
                self.testSingleUrl();
            });

            // Clear logs
            $(document).on('click', '.clear-logs-btn', function(e) {
                e.preventDefault();
                if (confirm('Сигурни ли сте, че искате да изчистите всички логове?')) {
                    self.clearScraperLogs();
                }
            });

            // Auto refresh monitor
            if ($('.scraper-monitor').length) {
                setInterval(function() {
                    if ($('#auto-refresh').is(':checked')) {
                        self.refreshMonitorData();
                    }
                }, 30000);
            }
        },

        // Run scraper
        runScraper: function(type) {
            var self = this;
            var $button = $('.run-scraper-btn[data-type="' + type + '"]');
            
            $button.prop('disabled', true).text('Стартиране...');
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_run_scraper',
                    nonce: self.settings.nonce,
                    type: type
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Scraper-ът е стартиран успешно!', 'success');
                        
                        // Refresh stats after a delay
                        setTimeout(function() {
                            self.refreshScraperStats();
                        }, 2000);
                    } else {
                        self.showNotice(response.data || 'Грешка при стартиране!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Стартирай');
                }
            });
        },

        // Test single URL
        testSingleUrl: function() {
            var self = this;
            var url = $('#test-url').val();
            var storeId = $('#test-store').val();
            
            if (!url) {
                self.showNotice('Моля, въведете URL за тестване!', 'warning');
                return;
            }
            
            $('#test-url-btn').prop('disabled', true).text('Тестване...');
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_test_single_url',
                    nonce: self.settings.nonce,
                    url: url,
                    store_id: storeId
                },
                success: function(response) {
                    if (response.success) {
                        $('#test-results').show();
                        $('#test-results-content').html(self.formatTestResults(response.data));
                    } else {
                        self.showNotice(response.data || 'Грешка при тестване!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                },
                complete: function() {
                    $('#test-url-btn').prop('disabled', false).text('Тествай');
                }
            });
        },

        // Format test results
        formatTestResults: function(data) {
            var html = '<div class="test-results">';
            
            if (data.status_code) {
                html += '<p><strong>HTTP статус:</strong> ' + data.status_code + '</p>';
            }
            
            if (data.content_length) {
                html += '<p><strong>Размер на съдържанието:</strong> ' + data.content_length + ' символа</p>';
            }
            
            if (data.schema_test) {
                html += '<h4>Резултати от schema тест:</h4>';
                
                if (data.schema_test.data) {
                    var scrapedData = data.schema_test.data;
                    
                    if (scrapedData.price) {
                        html += '<p><strong>Цена:</strong> ' + scrapedData.price + '</p>';
                    }
                    
                    if (scrapedData.availability) {
                        html += '<p><strong>Наличност:</strong> ' + scrapedData.availability + '</p>';
                    }
                    
                    if (scrapedData.variants) {
                        html += '<p><strong>Варианти:</strong> ' + scrapedData.variants.length + '</p>';
                    }
                }
                
                if (data.schema_test.error) {
                    html += '<p class="error"><strong>Грешка:</strong> ' + data.schema_test.error + '</p>';
                }
            }
            
            html += '</div>';
            return html;
        },

        // Clear scraper logs
        clearScraperLogs: function() {
            var self = this;
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_clear_scraper_logs',
                    nonce: self.settings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Логовете са изчистени успешно!', 'success');
                        
                        // Clear logs display
                        $('#logs-list').empty();
                    } else {
                        self.showNotice(response.data || 'Грешка при изчистване!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                }
            });
        },

        // Refresh scraper stats
        refreshScraperStats: function() {
            var self = this;
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_get_scraper_stats',
                    nonce: self.settings.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.updateStatsDisplay(response.data);
                    }
                }
            });
        },

        // Update stats display
        updateStatsDisplay: function(stats) {
            $('.stat-card.total .stat-number').text(stats.total || 0);
            $('.stat-card.success .stat-number').text(stats.successful || 0);
            $('.stat-card.error .stat-number').text(stats.failed || 0);
            $('.stat-card.pending .stat-number').text(stats.pending || 0);
        },

        // Refresh monitor data
        refreshMonitorData: function() {
            // This would load fresh monitor data via AJAX
            // Implementation depends on specific requirements
        },

        // Comments functionality
        initComments: function() {
            var self = this;

            // Moderate comment
            $(document).on('click', '.moderate-comment', function(e) {
                e.preventDefault();
                var commentId = $(this).data('comment-id');
                var action = $(this).data('action');
                self.moderateComment(commentId, action);
            });

            // Bulk moderate comments
            $(document).on('click', '.bulk-moderate-btn', function(e) {
                e.preventDefault();
                var action = $('#bulk-action').val();
                var commentIds = [];
                
                $('.comment-checkbox:checked').each(function() {
                    commentIds.push($(this).val());
                });
                
                if (commentIds.length === 0) {
                    self.showNotice('Моля, изберете коментари!', 'warning');
                    return;
                }
                
                self.bulkModerateComments(commentIds, action);
            });
        },

        // Moderate single comment
        moderateComment: function(commentId, action) {
            var self = this;
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_moderate_comment',
                    nonce: self.settings.nonce,
                    comment_id: commentId,
                    moderate_action: action
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Коментарът е модериран успешно!', 'success');
                        
                        // Update comment status in UI
                        var $comment = $('[data-comment-id="' + commentId + '"]').closest('.comment-item');
                        $comment.find('.comment-status').removeClass().addClass('comment-status ' + action);
                    } else {
                        self.showNotice(response.data || 'Грешка при модериране!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                }
            });
        },

        // Bulk moderate comments
        bulkModerateComments: function(commentIds, action) {
            var self = this;
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_bulk_moderate_comments',
                    nonce: self.settings.nonce,
                    comment_ids: commentIds,
                    moderate_action: action
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Коментарите са модерирани успешно!', 'success');
                        location.reload(); // Refresh to show updated comments
                    } else {
                        self.showNotice(response.data || 'Грешка при модериране!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                }
            });
        },

        // Meta boxes functionality
        initMetaBoxes: function() {
            var self = this;

            // Dynamic fields (pros/cons, notes, etc.)
            $(document).on('click', '.add-dynamic-item', function(e) {
                e.preventDefault();
                self.addDynamicItem($(this));
            });

            $(document).on('click', '.remove-dynamic-item', function(e) {
                e.preventDefault();
                $(this).closest('.dynamic-item').remove();
            });

            // Notes selection
            $(document).on('change', '.notes-checkbox', function() {
                self.updateSelectedNotes();
            });

            // Store management in post
            $(document).on('click', '.add-post-store', function(e) {
                e.preventDefault();
                self.addPostStore($(this));
            });

            $(document).on('click', '.remove-post-store', function(e) {
                e.preventDefault();
                $(this).closest('.post-store-item').remove();
                self.updateStoresOrder();
            });

            // Manual scrape for post store
            $(document).on('click', '.manual-scrape-store', function(e) {
                e.preventDefault();
                self.manualScrapeStore($(this));
            });
        },

        // Add dynamic item (pros/cons)
        addDynamicItem: function($button) {
            var $container = $button.siblings('.dynamic-items');
            var template = $button.data('template') || '<div class="dynamic-item"><input type="text" name="{name}[]" /><button type="button" class="remove-dynamic-item">×</button></div>';
            var name = $button.data('name');
            
            var html = template.replace('{name}', name);
            $container.append(html);
        },

        // Update selected notes display
        updateSelectedNotes: function() {
            var $container = $('.selected-notes');
            var selectedNotes = [];
            
            $('.notes-checkbox:checked').each(function() {
                var noteName = $(this).data('note-name');
                selectedNotes.push(noteName);
            });
            
            var html = selectedNotes.map(function(note) {
                return '<span class="note-tag">' + note + '</span>';
            }).join(' ');
            
            $container.html(html);
        },

        // Add store to post
        addPostStore: function($button) {
            var storeId = $button.data('store-id');
            var storeName = $button.data('store-name');
            
            if ($('#post-stores-list').find('[data-store-id="' + storeId + '"]').length > 0) {
                this.showNotice('Този магазин вече е добавен!', 'warning');
                return;
            }
            
            var template = wp.template('post-store-item');
            var html = template({
                store_id: storeId,
                store_name: storeName
            });
            
            $('#post-stores-list').append(html);
            this.updateStoresOrder();
        },

        // Update stores order
        updateStoresOrder: function() {
            $('#post-stores-list .post-store-item').each(function(index) {
                $(this).find('[name*="[order]"]').val(index);
            });
        },

        // Manual scrape store
        manualScrapeStore: function($button) {
            var self = this;
            var storeId = $button.data('store-id');
            var postId = $button.data('post-id');
            
            $button.prop('disabled', true).text('Скрейпване...');
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_manual_scrape_store',
                    nonce: self.settings.nonce,
                    store_id: storeId,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Скрейпването завърши успешно!', 'success');
                        
                        // Update scraped data display
                        if (response.data.scraped_data) {
                            self.updateScrapedDataDisplay($button, response.data.scraped_data);
                        }
                    } else {
                        self.showNotice(response.data || 'Грешка при скрейпване!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Обнови');
                }
            });
        },

        // Update scraped data display
        updateScrapedDataDisplay: function($button, data) {
            var $container = $button.closest('.post-store-item').find('.scraped-data');
            
            var html = '';
            if (data.price) {
                html += '<div class="scraped-price"><strong>Цена:</strong> ' + data.price + ' лв.</div>';
            }
            
            if (data.availability) {
                html += '<div class="scraped-availability"><strong>Наличност:</strong> ' + data.availability + '</div>';
            }
            
            if (data.variants && data.variants.length > 0) {
                html += '<div class="scraped-variants"><strong>Варианти:</strong> ' + data.variants.length + '</div>';
            }
            
            html += '<div class="scraped-time"><small>Обновено: ' + new Date().toLocaleString('bg-BG') + '</small></div>';
            
            $container.html(html);
        },

        // Settings functionality
        initSettings: function() {
            var self = this;

            // Import/Export functionality
            $(document).on('click', '.export-settings-btn', function(e) {
                e.preventDefault();
                self.exportSettings();
            });

            $(document).on('click', '.import-settings-btn', function(e) {
                e.preventDefault();
                $('#settings-import-file').click();
            });

            $(document).on('change', '#settings-import-file', function() {
                if (this.files && this.files[0]) {
                    self.importSettings(this.files[0]);
                }
            });

            // Reset settings
            $(document).on('click', '.reset-settings-btn', function(e) {
                e.preventDefault();
                if (confirm('Сигурни ли сте, че искате да възстановите настройките по подразбиране?')) {
                    self.resetSettings();
                }
            });

            // Notes import
            $(document).on('click', '.import-notes-btn', function(e) {
                e.preventDefault();
                $('#notes-import-file').click();
            });

            $(document).on('change', '#notes-import-file', function() {
                if (this.files && this.files[0]) {
                    self.importNotes(this.files[0]);
                }
            });
        },

        // Export settings
        exportSettings: function() {
            var self = this;
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_export_settings',
                    nonce: self.settings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var dataStr = JSON.stringify(response.data, null, 2);
                        var dataBlob = new Blob([dataStr], {type: 'application/json'});
                        
                        var link = document.createElement('a');
                        link.href = URL.createObjectURL(dataBlob);
                        link.download = 'parfume-catalog-settings.json';
                        link.click();
                        
                        self.showNotice('Настройките са експортирани успешно!', 'success');
                    } else {
                        self.showNotice(response.data || 'Грешка при експорт!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                }
            });
        },

        // Import settings
        importSettings: function(file) {
            var self = this;
            
            if (!file.type.match('application/json')) {
                self.showNotice('Моля, изберете JSON файл!', 'error');
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);
                    
                    $.ajax({
                        url: self.settings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'parfume_import_settings',
                            nonce: self.settings.nonce,
                            settings_data: JSON.stringify(settings)
                        },
                        success: function(response) {
                            if (response.success) {
                                self.showNotice('Настройките са импортирани успешно!', 'success');
                                location.reload();
                            } else {
                                self.showNotice(response.data || 'Грешка при импорт!', 'error');
                            }
                        },
                        error: function() {
                            self.showNotice('Грешка при заявката!', 'error');
                        }
                    });
                } catch (err) {
                    self.showNotice('Невалиден JSON файл!', 'error');
                }
            };
            
            reader.readAsText(file);
        },

        // Import notes
        importNotes: function(file) {
            var self = this;
            
            if (!file.type.match('application/json')) {
                self.showNotice('Моля, изберете JSON файл!', 'error');
                return;
            }
            
            var formData = new FormData();
            formData.append('notes_file', file);
            formData.append('action', 'parfume_import_notes');
            formData.append('nonce', self.settings.nonce);
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message || 'Нотките са импортирани успешно!', 'success');
                    } else {
                        self.showNotice(response.data || 'Грешка при импорт!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                }
            });
        },

        // Reset settings
        resetSettings: function() {
            var self = this;
            
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_reset_settings',
                    nonce: self.settings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Настройките са възстановени успешно!', 'success');
                        location.reload();
                    } else {
                        self.showNotice(response.data || 'Грешка при възстановяване!', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Грешка при заявката!', 'error');
                }
            });
        },

        // Utility functions
        showNotice: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;
            
            $('.parfume-message').remove();
            
            var $notice = $('<div class="parfume-message ' + type + '">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">&times;</button>' +
                '</div>');
            
            $('.wrap').first().prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, duration);
            
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            });
        },

        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        // Add dynamic field
        addDynamicField: function($button) {
            var $container = $button.siblings('.dynamic-fields-container');
            var template = $button.data('template');
            var index = $container.children().length;
            
            var html = template.replace(/{{INDEX}}/g, index);
            $container.append(html);
        },

        // Remove dynamic field
        removeDynamicField: function($button) {
            $button.closest('.dynamic-field').remove();
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        parfumeAdmin.init();
    });

    // WordPress post edit screen integration
    if (typeof postboxes !== 'undefined') {
        postboxes.add_postbox_toggles(pagenow);
    }

})(jQuery);