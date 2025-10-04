/**
 * Comments JavaScript
 * Handles comment submission via AJAX
 */

(function($) {
    'use strict';

    const ParfumeComments = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#parfume-comment-form').on('submit', this.handleSubmit.bind(this));
        },

        handleSubmit: function(e) {
            e.preventDefault();

            const form = $(e.currentTarget);
            const submitButton = form.find('.submit-button');
            const messageBox = form.find('.form-message');

            // Validate
            if (!this.validateForm(form)) {
                return;
            }

            // Disable submit button
            submitButton.prop('disabled', true);
            submitButton.find('.button-text').hide();
            submitButton.find('.button-loader').show();

            // Hide previous messages
            messageBox.hide().removeClass('success error');

            // Prepare data
            const formData = {
                action: 'parfume_submit_comment',
                nonce: parfumeComments.nonce,
                post_id: form.find('[name="post_id"]').val(),
                name: form.find('[name="name"]').val(),
                email: form.find('[name="email"]').val(),
                comment: form.find('[name="comment"]').val(),
                rating: form.find('[name="rating"]:checked').val(),
                captcha_answer: form.find('[name="captcha_answer"]').val(),
                captcha_expected: form.find('[name="captcha_expected"]').val()
            };

            // Send AJAX request
            $.ajax({
                url: parfumeComments.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        messageBox
                            .addClass('success')
                            .html(response.data.message)
                            .fadeIn();

                        // Reset form
                        form[0].reset();

                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: messageBox.offset().top - 100
                        }, 500);
                    } else {
                        // Show error message
                        messageBox
                            .addClass('error')
                            .html(response.data.message)
                            .fadeIn();
                    }
                },
                error: function() {
                    messageBox
                        .addClass('error')
                        .html(parfumeComments.strings.error)
                        .fadeIn();
                },
                complete: function() {
                    // Re-enable submit button
                    submitButton.prop('disabled', false);
                    submitButton.find('.button-text').show();
                    submitButton.find('.button-loader').hide();
                }
            });
        },

        validateForm: function(form) {
            let isValid = true;
            const messageBox = form.find('.form-message');

            // Clear previous errors
            form.find('.form-group').removeClass('has-error');

            // Validate email
            const email = form.find('[name="email"]').val().trim();
            if (!email) {
                this.showFieldError(form.find('[name="email"]'), parfumeComments.strings.required);
                isValid = false;
            } else if (!this.isValidEmail(email)) {
                this.showFieldError(form.find('[name="email"]'), parfumeComments.strings.invalid_email);
                isValid = false;
            }

            // Validate comment
            const comment = form.find('[name="comment"]').val().trim();
            if (!comment) {
                this.showFieldError(form.find('[name="comment"]'), parfumeComments.strings.required);
                isValid = false;
            }

            // Validate rating
            const rating = form.find('[name="rating"]:checked').val();
            if (!rating) {
                messageBox
                    .addClass('error')
                    .html(parfumeComments.strings.rating_required)
                    .fadeIn();
                isValid = false;
            }

            // Validate captcha
            const captchaAnswer = form.find('[name="captcha_answer"]').val().trim();
            if (!captchaAnswer) {
                this.showFieldError(form.find('[name="captcha_answer"]'), parfumeComments.strings.required);
                isValid = false;
            }

            return isValid;
        },

        showFieldError: function(field, message) {
            field.closest('.form-group').addClass('has-error');
            
            // Remove existing error message
            field.siblings('.field-error').remove();
            
            // Add new error message
            field.after('<span class="field-error">' + message + '</span>');
        },

        isValidEmail: function(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ParfumeComments.init();
    });

})(jQuery);