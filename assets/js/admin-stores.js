/**
 * Admin Stores Management JavaScript
 */
(function($) {
    'use strict';

    var StoreManager = {
        modal: null,
        mediaUploader: null,
        currentStoreId: null,

        init: function() {
            this.modal = $('#store-modal');
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Add new store button - multiple selectors for compatibility
            $(document).on('click', '#add-store-btn, #add-new-store', function(e) {
                e.preventDefault();
                self.openModal();
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

            // Close modal
            $('.store-modal-close').on('click', function() {
                self.closeModal();
            });

            // Close modal on overlay click
            this.modal.on('click', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });

            // Save store
            $('#save-store-btn').on('click', function(e) {
                e.preventDefault();
                self.saveStore();
            });

            // Logo upload
            $('#upload-logo-btn').on('click', function(e) {
                e.preventDefault();
                self.openMediaUploader();
            });

            // Remove logo
            $('#remove-logo-btn').on('click', function(e) {
                e.preventDefault();
                self.removeLogo();
            });

            // Form validation
            $('#store-name').on('input', function() {
                self.validateForm();
            });

            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.modal.is(':visible')) {
                    self.closeModal();
                }
            });
        },

        openModal: function(storeData) {
            this.currentStoreId = null;
            this.resetForm();
            
            if (storeData) {
                this.populateForm(storeData);
                $('#modal-title').text(parfumeStores.strings.editStore || 'Редактирай магазин');
            } else {
                $('#modal-title').text(parfumeStores.strings.addStore || 'Добави магазин');
            }

            this.modal.fadeIn(300);
            $('#store-name').focus();
        },

        closeModal: function() {
            this.modal.fadeOut(300);
            this.resetForm();
        },

        resetForm: function() {
            $('#store-form')[0].reset();
            $('#store-id').val('');
            $('#store-logo').val('');
            $('#logo-preview-img').hide().attr('src', '');
            $('#upload-logo-btn').text(parfumeStores.strings.selectLogo || 'Избери лого');
            $('#remove-logo-btn').hide();
            this.validateForm();
        },

        populateForm: function(store) {
            this.currentStoreId = store.id;
            $('#store-id').val(store.id);
            $('#store-name').val(store.name || '');
            $('#store-url').val(store.url || '');
            $('#store-description').val(store.description || '');
            $('#store-status').val(store.status || 'active');
            $('#store-priority').val(store.priority || 1);

            if (store.logo_id && store.logo_url) {
                $('#store-logo').val(store.logo_id);
                $('#logo-preview-img').attr('src', store.logo_url).show();
                $('#upload-logo-btn').text(parfumeStores.strings.changeLogo || 'Смени лого');
                $('#remove-logo-btn').show();
            }

            this.validateForm();
        },

        validateForm: function() {
            var isValid = true;
            var storeName = $('#store-name').val().trim();

            if (!storeName) {
                isValid = false;
            }

            $('#save-store-btn').prop('disabled', !isValid);
            return isValid;
        },

        editStore: function(storeId) {
            var self = this;
            
            this.showLoading();

            $.ajax({
                url: parfumeStores.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_get_store',
                    store_id: storeId,
                    nonce: parfumeStores.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.openModal(response.data.store);
                    } else {
                        self.showNotification(response.data.message || 'Грешка при зареждане на магазина', 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotification('Грешка при свързване със сървъра', 'error');
                }
            });
        },

        saveStore: function() {
            if (!this.validateForm()) {
                this.showNotification(parfumeStores.strings.storeNameRequired || 'Името на магазина е задължително', 'warning');
                return;
            }

            var self = this;
            var formData = this.getFormData();
            
            $('#save-store-btn').prop('disabled', true).text(parfumeStores.strings.savingStore || 'Запазване...');

            $.ajax({
                url: parfumeStores.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#save-store-btn').prop('disabled', false).text('Запази магазин');
                    
                    if (response.success) {
                        self.showNotification(response.data.message || 'Магазинът е запазен успешно', 'success');
                        self.closeModal();
                        self.refreshStoresList();
                    } else {
                        self.showNotification(response.data.message || parfumeStores.strings.errorSaving, 'error');
                    }
                },
                error: function() {
                    $('#save-store-btn').prop('disabled', false).text('Запази магазин');
                    self.showNotification(parfumeStores.strings.errorSaving || 'Грешка при запазване', 'error');
                }
            });
        },

        deleteStore: function(storeId) {
            if (!confirm(parfumeStores.strings.confirmDelete || 'Сигурни ли сте, че искате да изтриете този магазин?')) {
                return;
            }

            var self = this;
            this.showLoading();

            $.ajax({
                url: parfumeStores.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_delete_store',
                    store_id: storeId,
                    nonce: parfumeStores.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showNotification(response.data.message || 'Магазинът е изтрит успешно', 'success');
                        self.refreshStoresList();
                    } else {
                        self.showNotification(response.data.message || parfumeStores.strings.errorDeleting, 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotification(parfumeStores.strings.errorDeleting || 'Грешка при изтриване', 'error');
                }
            });
        },

        getFormData: function() {
            return {
                action: 'parfume_save_store',
                store_id: $('#store-id').val(),
                store_name: $('#store-name').val().trim(),
                store_logo: $('#store-logo').val(),
                store_url: $('#store-url').val().trim(),
                store_description: $('#store-description').val().trim(),
                store_status: $('#store-status').val(),
                store_priority: $('#store-priority').val(),
                nonce: parfumeStores.nonce
            };
        },

        openMediaUploader: function() {
            var self = this;

            // Create media uploader if it doesn't exist
            if (!this.mediaUploader) {
                this.mediaUploader = wp.media({
                    title: 'Избери лого за магазина',
                    button: {
                        text: 'Използвай това изображение'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                this.mediaUploader.on('select', function() {
                    var attachment = self.mediaUploader.state().get('selection').first().toJSON();
                    self.setLogo(attachment.id, attachment.url);
                });
            }

            this.mediaUploader.open();
        },

        setLogo: function(logoId, logoUrl) {
            $('#store-logo').val(logoId);
            $('#logo-preview-img').attr('src', logoUrl).show();
            $('#upload-logo-btn').text(parfumeStores.strings.changeLogo || 'Смени лого');
            $('#remove-logo-btn').show();
        },

        removeLogo: function() {
            $('#store-logo').val('');
            $('#logo-preview-img').hide().attr('src', '');
            $('#upload-logo-btn').text(parfumeStores.strings.selectLogo || 'Избери лого');
            $('#remove-logo-btn').hide();
        },

        refreshStoresList: function() {
            // Reload the page to refresh the stores list
            // In a more advanced implementation, we could update just the list via AJAX
            window.location.reload();
        },

        showLoading: function() {
            $('#loading-overlay').fadeIn(200);
        },

        hideLoading: function() {
            $('#loading-overlay').fadeOut(200);
        },

        showNotification: function(message, type) {
            type = type || 'info';
            
            // Remove existing notifications
            $('.parfume-notification').remove();
            
            var notificationClass = 'notice notice-' + type;
            if (type === 'success') {
                notificationClass = 'notice notice-success';
            } else if (type === 'error') {
                notificationClass = 'notice notice-error';
            } else if (type === 'warning') {
                notificationClass = 'notice notice-warning';
            }
            
            var notification = $('<div class="' + notificationClass + ' parfume-notification is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
                '</div>');
            
            $('.wrap h1').after(notification);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                notification.fadeOut(function() {
                    notification.remove();
                });
            }, 5000);
            
            // Handle dismiss button
            notification.on('click', '.notice-dismiss', function() {
                notification.fadeOut(function() {
                    notification.remove();
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        StoreManager.init();
    });

})(jQuery);