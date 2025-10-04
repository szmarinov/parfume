/**
 * Admin Stores Page JavaScript
 * Manages store CRUD operations in admin
 */

(function($) {
    'use strict';

    const StoresPage = {
        mediaUploader: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Media uploader
            $(document).on('click', '.upload-logo-button', this.openMediaUploader.bind(this));
            $(document).on('click', '.remove-logo-button', this.removeLogo.bind(this));
            
            // Delete store
            $(document).on('click', '.delete-store-btn', this.confirmDelete.bind(this));
            
            // Form validation
            $('form[name="store-form"]').on('submit', this.validateForm.bind(this));
        },

        openMediaUploader: function(e) {
            e.preventDefault();
            
            const button = $(e.currentTarget);
            const wrapper = button.closest('.logo-upload-wrapper');
            
            // If media uploader exists, open it
            if (this.mediaUploader) {
                this.mediaUploader.open();
                return;
            }
            
            // Create new media uploader
            this.mediaUploader = wp.media({
                title: 'Избери лого на магазин',
                button: {
                    text: 'Използвай това изображение'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When image is selected
            this.mediaUploader.on('select', function() {
                const attachment = this.mediaUploader.state().get('selection').first().toJSON();
                
                // Update hidden input with attachment ID
                wrapper.find('.logo-id-input').val(attachment.id);
                
                // Update preview
                const preview = wrapper.find('.logo-preview');
                preview.html('<img src="' + attachment.url + '" alt="Logo Preview">');
                preview.show();
                
                // Show remove button, hide upload button
                wrapper.find('.remove-logo-button').show();
                button.hide();
            }.bind(this));
            
            // Open uploader
            this.mediaUploader.open();
        },

        removeLogo: function(e) {
            e.preventDefault();
            
            const button = $(e.currentTarget);
            const wrapper = button.closest('.logo-upload-wrapper');
            
            // Clear hidden input
            wrapper.find('.logo-id-input').val('');
            
            // Clear preview
            wrapper.find('.logo-preview').html('').hide();
            
            // Show upload button, hide remove button
            wrapper.find('.upload-logo-button').show();
            button.hide();
        },

        confirmDelete: function(e) {
            e.preventDefault();
            
            const storeId = $(e.currentTarget).data('store-id');
            const storeName = $(e.currentTarget).data('store-name');
            
            if (confirm('Сигурни ли сте, че искате да изтриете магазин "' + storeName + '"?\n\nТова действие е необратимо и ще премахне магазина от всички постове.')) {
                // Create form and submit
                const form = $('<form>', {
                    method: 'POST',
                    action: ''
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'action',
                    value: 'delete_store'
                }));
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'store_id',
                    value: storeId
                }));
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'parfume_stores_nonce',
                    value: $('#parfume_stores_nonce').val()
                }));
                
                $('body').append(form);
                form.submit();
            }
        },

        validateForm: function(e) {
            const form = $(e.currentTarget);
            let isValid = true;
            let errorMessage = '';
            
            // Validate store name
            const storeName = form.find('input[name="store_name"]').val().trim();
            if (storeName === '') {
                isValid = false;
                errorMessage += '• Името на магазина е задължително\n';
            }
            
            // Validate domain
            const domain = form.find('input[name="domain"]').val().trim();
            if (domain === '') {
                isValid = false;
                errorMessage += '• Домейнът е задължителен\n';
            } else if (!this.isValidDomain(domain)) {
                isValid = false;
                errorMessage += '• Домейнът не е валиден (пример: example.com)\n';
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Моля, коригирайте следните грешки:\n\n' + errorMessage);
            }
            
            return isValid;
        },

        isValidDomain: function(domain) {
            // Basic domain validation
            const domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/;
            return domainRegex.test(domain);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        StoresPage.init();
    });

})(jQuery);