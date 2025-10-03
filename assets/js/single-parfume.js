/**
 * Single Parfume Page JavaScript
 * Functionality for single parfume pages
 * 
 * ФАЙЛ: assets/js/single-parfume.js
 * НОВА ВЕРСИЯ - Създаден липсващ файл
 */
(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        console.log('Single Parfume JS loaded');
        
        initImageGallery();
        initPriceUpdater();
        initCouponCode();
        initShareButtons();
        initReviewForm();
        initRelatedProducts();
        initStickyInfo();
    });
    
    /**
     * Initialize image gallery/slider
     */
    function initImageGallery() {
        const $gallery = $('.parfume-gallery');
        if ($gallery.length === 0) return;
        
        console.log('Initializing image gallery');
        
        // Main image click - open lightbox
        $gallery.find('.main-image img').on('click', function() {
            const src = $(this).attr('src');
            openLightbox(src);
        });
        
        // Thumbnail clicks
        $gallery.find('.thumbnail').on('click', function(e) {
            e.preventDefault();
            const $thumb = $(this);
            const newSrc = $thumb.data('full') || $thumb.find('img').attr('src');
            
            // Update main image
            $gallery.find('.main-image img').attr('src', newSrc);
            
            // Update active thumbnail
            $gallery.find('.thumbnail').removeClass('active');
            $thumb.addClass('active');
        });
    }
    
    /**
     * Open image lightbox
     */
    function openLightbox(imageSrc) {
        const $lightbox = $('<div class="parfume-lightbox"></div>');
        const $overlay = $('<div class="lightbox-overlay"></div>');
        const $content = $('<div class="lightbox-content"></div>');
        const $img = $('<img src="' + imageSrc + '" alt="">');
        const $close = $('<button class="lightbox-close">&times;</button>');
        
        $content.append($img, $close);
        $lightbox.append($overlay, $content);
        $('body').append($lightbox);
        
        // Animate in
        setTimeout(function() {
            $lightbox.addClass('active');
        }, 10);
        
        // Close handlers
        $close.on('click', closeLightbox);
        $overlay.on('click', closeLightbox);
        
        // ESC key
        $(document).on('keyup.lightbox', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
        
        function closeLightbox() {
            $lightbox.removeClass('active');
            setTimeout(function() {
                $lightbox.remove();
                $(document).off('keyup.lightbox');
            }, 300);
        }
    }
    
    /**
     * Initialize price updater for stores
     */
    function initPriceUpdater() {
        const $updateButtons = $('.update-price-btn');
        if ($updateButtons.length === 0) return;
        
        console.log('Initializing price updater');
        
        $updateButtons.on('click', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const storeId = $btn.data('store-id');
            const parfumeId = $btn.data('parfume-id');
            
            if (!storeId || !parfumeId) {
                showNotification(parfume_single_ajax.strings.update_failed, 'error');
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true)
                .text(parfume_single_ajax.strings.updating_price);
            
            // AJAX request to update price
            $.ajax({
                url: parfume_single_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_update_store_price',
                    nonce: parfume_single_ajax.nonce,
                    parfume_id: parfumeId,
                    store_id: storeId
                },
                success: function(response) {
                    if (response.success) {
                        // Update price display
                        const $priceDisplay = $btn.closest('.store-item').find('.store-price');
                        if (response.data.price) {
                            $priceDisplay.html(response.data.price + ' лв.');
                        }
                        
                        showNotification(parfume_single_ajax.strings.price_updated, 'success');
                    } else {
                        showNotification(response.data || parfume_single_ajax.strings.update_failed, 'error');
                    }
                },
                error: function() {
                    showNotification(parfume_single_ajax.strings.update_failed, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false)
                        .html('<span class="dashicons dashicons-update"></span>');
                }
            });
        });
    }
    
    /**
     * Initialize coupon code copy functionality
     */
    function initCouponCode() {
        const $couponButtons = $('.copy-coupon-btn');
        if ($couponButtons.length === 0) return;
        
        console.log('Initializing coupon code functionality');
        
        $couponButtons.on('click', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const couponCode = $btn.data('coupon-code');
            
            if (!couponCode) return;
            
            // Copy to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(couponCode).then(function() {
                    showNotification(parfume_single_ajax.strings.code_copied, 'success');
                    
                    // Change button text temporarily
                    const originalText = $btn.text();
                    $btn.text('Копирано!');
                    setTimeout(function() {
                        $btn.text(originalText);
                    }, 2000);
                }).catch(function() {
                    fallbackCopyToClipboard(couponCode);
                });
            } else {
                fallbackCopyToClipboard(couponCode);
            }
        });
    }
    
    /**
     * Fallback copy to clipboard for older browsers
     */
    function fallbackCopyToClipboard(text) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        
        try {
            document.execCommand('copy');
            showNotification(parfume_single_ajax.strings.code_copied, 'success');
        } catch (err) {
            showNotification(parfume_single_ajax.strings.copy_failed, 'error');
        }
        
        $temp.remove();
    }
    
    /**
     * Initialize share buttons
     */
    function initShareButtons() {
        const $shareButtons = $('.share-button');
        if ($shareButtons.length === 0) return;
        
        console.log('Initializing share buttons');
        
        $shareButtons.on('click', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const platform = $btn.data('platform');
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            
            let shareUrl = '';
            
            switch (platform) {
                case 'facebook':
                    shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
                    break;
                case 'twitter':
                    shareUrl = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title;
                    break;
                case 'pinterest':
                    const image = encodeURIComponent($('.parfume-gallery .main-image img').attr('src') || '');
                    shareUrl = 'https://pinterest.com/pin/create/button/?url=' + url + '&media=' + image + '&description=' + title;
                    break;
                case 'whatsapp':
                    shareUrl = 'https://wa.me/?text=' + title + ' ' + url;
                    break;
                case 'viber':
                    shareUrl = 'viber://forward?text=' + title + ' ' + url;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, 'share', 'width=600,height=400');
            }
        });
        
        // Native Web Share API if available
        if (navigator.share) {
            const $nativeShareBtn = $('.share-native');
            if ($nativeShareBtn.length > 0) {
                $nativeShareBtn.on('click', function(e) {
                    e.preventDefault();
                    
                    navigator.share({
                        title: document.title,
                        url: window.location.href
                    }).catch(function(err) {
                        console.log('Share failed:', err);
                    });
                });
            }
        }
    }
    
    /**
     * Initialize review form
     */
    function initReviewForm() {
        const $form = $('.parfume-review-form');
        if ($form.length === 0) return;
        
        console.log('Initializing review form');
        
        // Star rating
        $form.find('.rating-input .star').on('click', function() {
            const $star = $(this);
            const rating = $star.data('rating');
            const $container = $star.closest('.rating-input');
            
            $container.find('.star').removeClass('active');
            $container.find('.star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).addClass('active');
                }
            });
            
            $container.find('input[name="rating"]').val(rating);
        });
        
        // Form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const $submitBtn = $form.find('button[type="submit"]');
            const formData = $form.serialize();
            
            $submitBtn.prop('disabled', true).text('Изпращане...');
            
            $.ajax({
                url: parfume_single_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=parfume_submit_review&nonce=' + parfume_single_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        showNotification('Отзивът е изпратен успешно!', 'success');
                        $form[0].reset();
                        $form.find('.star').removeClass('active');
                        
                        // Optionally reload reviews section
                        if (response.data.html) {
                            $('.parfume-reviews-list').prepend(response.data.html);
                        }
                    } else {
                        showNotification(response.data || 'Грешка при изпращане', 'error');
                    }
                },
                error: function() {
                    showNotification('Грешка при изпращане', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Изпрати отзив');
                }
            });
        });
    }
    
    /**
     * Initialize related products slider
     */
    function initRelatedProducts() {
        const $related = $('.related-parfumes');
        if ($related.length === 0) return;
        
        console.log('Initializing related products');
        
        // Simple carousel if multiple items
        const $items = $related.find('.parfume-card');
        if ($items.length > 4) {
            let currentIndex = 0;
            const itemsPerPage = 4;
            const totalPages = Math.ceil($items.length / itemsPerPage);
            
            // Add navigation
            const $nav = $('<div class="carousel-nav">' +
                '<button class="prev">&larr;</button>' +
                '<button class="next">&rarr;</button>' +
                '</div>');
            $related.append($nav);
            
            $nav.find('.prev').on('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            });
            
            $nav.find('.next').on('click', function() {
                if (currentIndex < totalPages - 1) {
                    currentIndex++;
                    updateCarousel();
                }
            });
            
            function updateCarousel() {
                const offset = currentIndex * itemsPerPage * -100;
                $items.parent().css('transform', 'translateX(' + offset + '%)');
                
                // Update button states
                $nav.find('.prev').prop('disabled', currentIndex === 0);
                $nav.find('.next').prop('disabled', currentIndex === totalPages - 1);
            }
            
            updateCarousel();
        }
    }
    
    /**
     * Initialize sticky info panel
     */
    function initStickyInfo() {
        const $stickyPanel = $('.parfume-info-sticky');
        if ($stickyPanel.length === 0) return;
        
        console.log('Initializing sticky info panel');
        
        const stickyOffset = $stickyPanel.offset().top;
        
        $(window).on('scroll', function() {
            if ($(window).scrollTop() > stickyOffset) {
                $stickyPanel.addClass('is-sticky');
            } else {
                $stickyPanel.removeClass('is-sticky');
            }
        });
    }
    
    /**
     * Show notification message
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        const $notification = $('<div class="parfume-notification ' + type + '">' + message + '</div>');
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('show');
        }, 10);
        
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
    // CSS for notifications (inject if not present)
    if ($('style#parfume-notification-styles').length === 0) {
        $('head').append(
            '<style id="parfume-notification-styles">' +
            '.parfume-notification {' +
            '  position: fixed;' +
            '  top: 20px;' +
            '  right: 20px;' +
            '  padding: 15px 20px;' +
            '  background: #fff;' +
            '  border-radius: 4px;' +
            '  box-shadow: 0 2px 10px rgba(0,0,0,0.1);' +
            '  z-index: 99999;' +
            '  opacity: 0;' +
            '  transform: translateX(100px);' +
            '  transition: all 0.3s ease;' +
            '}' +
            '.parfume-notification.show {' +
            '  opacity: 1;' +
            '  transform: translateX(0);' +
            '}' +
            '.parfume-notification.success {' +
            '  border-left: 4px solid #46b450;' +
            '}' +
            '.parfume-notification.error {' +
            '  border-left: 4px solid #dc3232;' +
            '}' +
            '.parfume-notification.info {' +
            '  border-left: 4px solid #0073aa;' +
            '}' +
            '.parfume-lightbox {' +
            '  position: fixed;' +
            '  top: 0;' +
            '  left: 0;' +
            '  width: 100%;' +
            '  height: 100%;' +
            '  z-index: 99999;' +
            '  opacity: 0;' +
            '  transition: opacity 0.3s ease;' +
            '}' +
            '.parfume-lightbox.active {' +
            '  opacity: 1;' +
            '}' +
            '.lightbox-overlay {' +
            '  position: absolute;' +
            '  top: 0;' +
            '  left: 0;' +
            '  width: 100%;' +
            '  height: 100%;' +
            '  background: rgba(0,0,0,0.9);' +
            '}' +
            '.lightbox-content {' +
            '  position: relative;' +
            '  z-index: 1;' +
            '  display: flex;' +
            '  align-items: center;' +
            '  justify-content: center;' +
            '  height: 100%;' +
            '  padding: 20px;' +
            '}' +
            '.lightbox-content img {' +
            '  max-width: 90%;' +
            '  max-height: 90%;' +
            '  object-fit: contain;' +
            '}' +
            '.lightbox-close {' +
            '  position: absolute;' +
            '  top: 20px;' +
            '  right: 20px;' +
            '  width: 40px;' +
            '  height: 40px;' +
            '  background: #fff;' +
            '  border: none;' +
            '  border-radius: 50%;' +
            '  font-size: 24px;' +
            '  cursor: pointer;' +
            '  z-index: 2;' +
            '}' +
            '</style>'
        );
    }
    
})(jQuery);