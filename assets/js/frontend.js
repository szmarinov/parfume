/**
 * Frontend JavaScript for Parfume Reviews Plugin
 * 
 * Handles all frontend functionality including:
 * - AJAX filtering
 * - Lightbox functionality
 * - Mobile interactions
 * - Search functionality
 * - Smooth scrolling
 * - Lazy loading
 */

(function($) {
    'use strict';
    
    // Global variables
    var isLoading = false;
    var currentPage = 1;
    
    $(document).ready(function() {
        initializeParfumeReviews();
    });
    
    /**
     * Initialize all frontend functionality
     */
    function initializeParfumeReviews() {
        // Core functionality
        initAjaxFilters();
        initImageLightbox();
        initSmoothScrolling();
        initMobileMenu();
        initSearchAutocomplete();
        initInfiniteScroll();
        initLazyLoading();
        
        // Interactive elements
        initRatingDisplay();
        initTabNavigation();
        initAccordions();
        initTooltips();
        initBackToTop();
        
        // Advanced features
        initRecentlyViewed();
        initQuickView();
        initImageZoom();
        initSocialSharing();
        
        // Utility functions
        initClickToCopy();
        initFormValidation();
        
        console.log('Parfume Reviews Frontend initialized');
    }
    
    /**
     * AJAX Filtering System
     */
    function initAjaxFilters() {
        $(document).on('submit', '.parfume-filters-form', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            var $form = $(this);
            var $container = $('.parfume-grid, .parfume-archive .archive-main');
            var formData = $form.serialize();
            
            // Add current page URL as base
            var currentUrl = new URL(window.location);
            var baseUrl = currentUrl.pathname;
            
            isLoading = true;
            showLoadingSpinner($container);
            
            // Build AJAX URL
            var ajaxUrl = parfumeReviews.ajaxurl;
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_filter_products',
                    nonce: parfumeReviews.nonce,
                    filters: formData,
                    page: 1
                },
                success: function(response) {
                    if (response.success) {
                        // Update content
                        $container.html(response.data.html);
                        
                        // Update URL without page reload
                        var newUrl = baseUrl + '?' + formData;
                        history.pushState(null, '', newUrl);
                        
                        // Update results count
                        updateResultsCount(response.data.count);
                        
                        // Reinitialize components for new content
                        initLazyLoading();
                        initImageLightbox();
                        
                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $container.offset().top - 100
                        }, 500);
                        
                    } else {
                        showNotification(parfumeReviews.strings.error, 'error');
                    }
                },
                error: function() {
                    showNotification(parfumeReviews.strings.error, 'error');
                },
                complete: function() {
                    isLoading = false;
                    hideLoadingSpinner($container);
                }
            });
        });
        
        // Filter reset button
        $(document).on('click', '.filter-reset', function(e) {
            e.preventDefault();
            
            var $form = $(this).closest('.parfume-filters-form');
            $form[0].reset();
            $form.trigger('submit');
        });
        
        // Individual filter changes
        $(document).on('change', '.parfume-filters-form select, .parfume-filters-form input[type="radio"]', function() {
            var $form = $(this).closest('.parfume-filters-form');
            
            // Auto-submit if not compact mode
            if (!$form.hasClass('compact')) {
                setTimeout(function() {
                    $form.trigger('submit');
                }, 300);
            }
        });
    }
    
    /**
     * Image Lightbox Functionality
     */
    function initImageLightbox() {
        // Create lightbox modal
        if ($('.parfume-lightbox').length === 0) {
            $('body').append(`
                <div class="parfume-lightbox" style="display: none;">
                    <div class="lightbox-overlay"></div>
                    <div class="lightbox-container">
                        <img class="lightbox-image" src="" alt="">
                        <button class="lightbox-close">&times;</button>
                        <button class="lightbox-prev">&#8249;</button>
                        <button class="lightbox-next">&#8250;</button>
                        <div class="lightbox-caption"></div>
                    </div>
                </div>
            `);
        }
        
        // Image click handler
        $(document).on('click', '.parfume-thumbnail img, .parfume-image img, .gallery-image', function(e) {
            e.preventDefault();
            
            var $img = $(this);
            var src = $img.attr('data-large') || $img.attr('src');
            var alt = $img.attr('alt') || '';
            var caption = $img.attr('data-caption') || '';
            
            openLightbox(src, alt, caption);
        });
        
        // Lightbox controls
        $(document).on('click', '.lightbox-close, .lightbox-overlay', function() {
            closeLightbox();
        });
        
        $(document).on('click', '.lightbox-prev', function() {
            navigateLightbox('prev');
        });
        
        $(document).on('click', '.lightbox-next', function() {
            navigateLightbox('next');
        });
        
        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if ($('.parfume-lightbox').is(':visible')) {
                switch(e.keyCode) {
                    case 27: // Escape
                        closeLightbox();
                        break;
                    case 37: // Left arrow
                        navigateLightbox('prev');
                        break;
                    case 39: // Right arrow
                        navigateLightbox('next');
                        break;
                }
            }
        });
    }
    
    function openLightbox(src, alt, caption) {
        var $lightbox = $('.parfume-lightbox');
        var $img = $lightbox.find('.lightbox-image');
        var $caption = $lightbox.find('.lightbox-caption');
        
        $img.attr('src', src).attr('alt', alt);
        $caption.text(caption);
        
        $lightbox.fadeIn(300);
        $('body').addClass('lightbox-open');
    }
    
    function closeLightbox() {
        $('.parfume-lightbox').fadeOut(300);
        $('body').removeClass('lightbox-open');
    }
    
    function navigateLightbox(direction) {
        // Implementation for gallery navigation
        var $current = $('.parfume-thumbnail img:visible, .gallery-image:visible');
        var currentIndex = $current.index();
        var $next;
        
        if (direction === 'next') {
            $next = $current.eq(currentIndex + 1).length ? $current.eq(currentIndex + 1) : $current.first();
        } else {
            $next = currentIndex > 0 ? $current.eq(currentIndex - 1) : $current.last();
        }
        
        if ($next.length) {
            var src = $next.attr('data-large') || $next.attr('src');
            var alt = $next.attr('alt') || '';
            var caption = $next.attr('data-caption') || '';
            
            $('.lightbox-image').attr('src', src).attr('alt', alt);
            $('.lightbox-caption').text(caption);
        }
    }
    
    /**
     * Smooth Scrolling
     */
    function initSmoothScrolling() {
        $(document).on('click', 'a[href^="#"]', function(e) {
            var href = $(this).attr('href');
            var $target = $(href);
            
            if ($target.length) {
                e.preventDefault();
                
                $('html, body').animate({
                    scrollTop: $target.offset().top - 100
                }, 800, 'swing');
            }
        });
    }
    
    /**
     * Mobile Menu Toggle
     */
    function initMobileMenu() {
        // Mobile filter toggle
        $(document).on('click', '.mobile-filter-toggle', function(e) {
            e.preventDefault();
            
            $('.parfume-filters-form').toggleClass('mobile-open');
            $(this).toggleClass('active');
        });
        
        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.parfume-filters-form, .mobile-filter-toggle').length) {
                $('.parfume-filters-form').removeClass('mobile-open');
                $('.mobile-filter-toggle').removeClass('active');
            }
        });
    }
    
    /**
     * Search Autocomplete
     */
    function initSearchAutocomplete() {
        var searchTimeout;
        
        $(document).on('input', 'input[name="parfume_search"], input[name="s"]', function() {
            var $input = $(this);
            var query = $input.val().trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                hideAutocomplete($input);
                return;
            }
            
            searchTimeout = setTimeout(function() {
                performAutocompleteSearch($input, query);
            }, 300);
        });
        
        // Hide autocomplete on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-autocomplete-container').length) {
                $('.search-autocomplete').hide();
            }
        });
    }
    
    function performAutocompleteSearch($input, query) {
        $.ajax({
            url: parfumeReviews.ajaxurl,
            type: 'POST',
            data: {
                action: 'parfume_autocomplete_search',
                nonce: parfumeReviews.nonce,
                query: query
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    showAutocomplete($input, response.data);
                } else {
                    hideAutocomplete($input);
                }
            }
        });
    }
    
    function showAutocomplete($input, results) {
        var $container = $input.closest('.search-input-wrapper, .filter-group');
        var $autocomplete = $container.find('.search-autocomplete');
        
        if ($autocomplete.length === 0) {
            $autocomplete = $('<div class="search-autocomplete"></div>');
            $container.append($autocomplete);
        }
        
        var html = '<ul>';
        results.forEach(function(item) {
            html += '<li><a href="' + item.url + '">';
            html += '<span class="title">' + item.title + '</span>';
            if (item.brand) {
                html += '<span class="brand">' + item.brand + '</span>';
            }
            html += '</a></li>';
        });
        html += '</ul>';
        
        $autocomplete.html(html).show();
    }
    
    function hideAutocomplete($input) {
        var $container = $input.closest('.search-input-wrapper, .filter-group');
        $container.find('.search-autocomplete').hide();
    }
    
    /**
     * Infinite Scroll
     */
    function initInfiniteScroll() {
        if (!$('.parfume-archive').length) return;
        
        var $window = $(window);
        var $loadMore = $('.load-more-products');
        var loading = false;
        
        $window.on('scroll', function() {
            if (loading) return;
            
            var scrollTop = $window.scrollTop();
            var windowHeight = $window.height();
            var documentHeight = $(document).height();
            
            // Load more when near bottom (200px threshold)
            if (scrollTop + windowHeight > documentHeight - 200) {
                loadMoreProducts();
            }
        });
        
        // Manual load more button
        $(document).on('click', '.load-more-btn', function(e) {
            e.preventDefault();
            loadMoreProducts();
        });
    }
    
    function loadMoreProducts() {
        if (isLoading) return;
        
        currentPage++;
        isLoading = true;
        
        var $container = $('.parfume-grid');
        var formData = $('.parfume-filters-form').serialize();
        
        $.ajax({
            url: parfumeReviews.ajaxurl,
            type: 'POST',
            data: {
                action: 'parfume_load_more',
                nonce: parfumeReviews.nonce,
                page: currentPage,
                filters: formData
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $container.append(response.data.html);
                    
                    // Reinitialize for new content
                    initLazyLoading();
                    initImageLightbox();
                    
                    // Hide load more if no more products
                    if (!response.data.has_more) {
                        $('.load-more-products').hide();
                    }
                } else {
                    currentPage--; // Reset page number
                }
            },
            error: function() {
                currentPage--; // Reset page number
                showNotification(parfumeReviews.strings.error, 'error');
            },
            complete: function() {
                isLoading = false;
            }
        });
    }
    
    /**
     * Lazy Loading for Images
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            $('img[data-src]').each(function() {
                $(this).attr('src', $(this).data('src')).removeClass('lazy');
            });
        }
    }
    
    /**
     * Rating Display Animation
     */
    function initRatingDisplay() {
        $('.rating-display').each(function() {
            var $rating = $(this);
            var rating = parseFloat($rating.data('rating'));
            
            if (rating) {
                animateRating($rating, rating);
            }
        });
    }
    
    function animateRating($element, targetRating) {
        var $stars = $element.find('.star');
        var delay = 0;
        
        $stars.each(function(index) {
            var $star = $(this);
            
            setTimeout(function() {
                if (index < Math.floor(targetRating)) {
                    $star.addClass('filled');
                } else if (index < targetRating) {
                    $star.addClass('half-filled');
                }
            }, delay);
            
            delay += 100;
        });
    }
    
    /**
     * Tab Navigation
     */
    function initTabNavigation() {
        $(document).on('click', '.tab-navigation .tab-link', function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var $container = $tab.closest('.tab-container');
            var target = $tab.attr('href') || $tab.data('tab');
            
            // Update active tab
            $container.find('.tab-link').removeClass('active');
            $tab.addClass('active');
            
            // Show target content
            $container.find('.tab-content').removeClass('active');
            $container.find(target).addClass('active');
            
            // Trigger custom event
            $container.trigger('tabChanged', [target]);
        });
    }
    
    /**
     * Accordion Functionality
     */
    function initAccordions() {
        $(document).on('click', '.accordion-header', function(e) {
            e.preventDefault();
            
            var $header = $(this);
            var $accordion = $header.closest('.accordion-item');
            var $content = $accordion.find('.accordion-content');
            
            if ($accordion.hasClass('active')) {
                $accordion.removeClass('active');
                $content.slideUp(300);
            } else {
                // Close other accordions in the same group
                var $group = $header.closest('.accordion-group');
                if ($group.length) {
                    $group.find('.accordion-item.active').removeClass('active')
                          .find('.accordion-content').slideUp(300);
                }
                
                $accordion.addClass('active');
                $content.slideDown(300);
            }
        });
    }
    
    /**
     * Tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            var $element = $(this);
            var text = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                showTooltip($element, text);
            }).on('mouseleave', function() {
                hideTooltip();
            });
        });
    }
    
    function showTooltip($element, text) {
        var $tooltip = $('<div class="tooltip">' + text + '</div>');
        $('body').append($tooltip);
        
        var offset = $element.offset();
        var elementWidth = $element.outerWidth();
        var elementHeight = $element.outerHeight();
        var tooltipWidth = $tooltip.outerWidth();
        
        $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 10,
            left: offset.left + (elementWidth / 2) - (tooltipWidth / 2)
        }).fadeIn(200);
    }
    
    function hideTooltip() {
        $('.tooltip').fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Back to Top Button
     */
    function initBackToTop() {
        var $backToTop = $('.back-to-top');
        
        if ($backToTop.length === 0) {
            $backToTop = $('<button class="back-to-top" title="Back to Top">↑</button>');
            $('body').append($backToTop);
        }
        
        $(window).on('scroll', function() {
            if ($(window).scrollTop() > 300) {
                $backToTop.addClass('visible');
            } else {
                $backToTop.removeClass('visible');
            }
        });
        
        $backToTop.on('click', function(e) {
            e.preventDefault();
            
            $('html, body').animate({
                scrollTop: 0
            }, 800, 'swing');
        });
    }
    
    /**
     * Recently Viewed Products
     */
    function initRecentlyViewed() {
        if (!$('body').hasClass('single-parfume')) return;
        
        var postId = $('body').data('post-id') || $('.parfume-single').data('id');
        if (!postId) return;
        
        var recentlyViewed = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
        
        // Remove current product if already in list
        recentlyViewed = recentlyViewed.filter(function(id) {
            return id !== postId;
        });
        
        // Add current product to beginning
        recentlyViewed.unshift(postId);
        
        // Keep only last 10 items
        recentlyViewed = recentlyViewed.slice(0, 10);
        
        // Save to localStorage
        localStorage.setItem('parfume_recently_viewed', JSON.stringify(recentlyViewed));
    }
    
    /**
     * Quick View Functionality
     */
    function initQuickView() {
        $(document).on('click', '.quick-view-btn', function(e) {
            e.preventDefault();
            
            var postId = $(this).data('post-id');
            if (!postId) return;
            
            openQuickView(postId);
        });
        
        // Close quick view
        $(document).on('click', '.quick-view-close, .quick-view-overlay', function() {
            closeQuickView();
        });
    }
    
    function openQuickView(postId) {
        var $quickView = $('.quick-view-modal');
        
        if ($quickView.length === 0) {
            $quickView = $(`
                <div class="quick-view-modal">
                    <div class="quick-view-overlay"></div>
                    <div class="quick-view-container">
                        <button class="quick-view-close">&times;</button>
                        <div class="quick-view-content">
                            <div class="quick-view-loading">
                                <div class="spinner"></div>
                                <p>Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            $('body').append($quickView);
        }
        
        $quickView.fadeIn(300);
        $('body').addClass('quick-view-open');
        
        // Load content
        $.ajax({
            url: parfumeReviews.ajaxurl,
            type: 'POST',
            data: {
                action: 'parfume_quick_view',
                nonce: parfumeReviews.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    $quickView.find('.quick-view-content').html(response.data.html);
                    initImageZoom(); // Reinitialize for quick view
                }
            },
            error: function() {
                $quickView.find('.quick-view-content').html('<p>Error loading product details.</p>');
            }
        });
    }
    
    function closeQuickView() {
        $('.quick-view-modal').fadeOut(300);
        $('body').removeClass('quick-view-open');
    }
    
    /**
     * Image Zoom on Hover
     */
    function initImageZoom() {
        $('.parfume-image-zoom').each(function() {
            var $container = $(this);
            var $image = $container.find('img');
            
            $container.on('mousemove', function(e) {
                var offset = $container.offset();
                var x = (e.pageX - offset.left) / $container.width() * 100;
                var y = (e.pageY - offset.top) / $container.height() * 100;
                
                $image.css('transform-origin', x + '% ' + y + '%');
            });
            
            $container.on('mouseenter', function() {
                $image.css('transform', 'scale(2)');
            });
            
            $container.on('mouseleave', function() {
                $image.css('transform', 'scale(1)');
            });
        });
    }
    
    /**
     * Social Sharing
     */
    function initSocialSharing() {
        $(document).on('click', '.social-share-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var platform = $btn.data('platform');
            var url = encodeURIComponent(window.location.href);
            var title = encodeURIComponent(document.title);
            var shareUrl = '';
            
            switch(platform) {
                case 'facebook':
                    shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
                    break;
                case 'twitter':
                    shareUrl = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title;
                    break;
                case 'pinterest':
                    var image = encodeURIComponent($('.parfume-image img').attr('src') || '');
                    shareUrl = 'https://pinterest.com/pin/create/button/?url=' + url + '&media=' + image + '&description=' + title;
                    break;
                case 'whatsapp':
                    shareUrl = 'https://wa.me/?text=' + title + ' ' + url;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, 'share', 'width=600,height=400');
            }
        });
    }
    
    /**
     * Click to Copy Functionality
     */
    function initClickToCopy() {
        $(document).on('click', '.copy-to-clipboard', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var text = $btn.data('copy') || $btn.text();
            
            copyToClipboard(text);
            
            // Visual feedback
            var originalText = $btn.text();
            $btn.text('Copied!').addClass('copied');
            
            setTimeout(function() {
                $btn.text(originalText).removeClass('copied');
            }, 2000);
        });
        
        // Promo code copy functionality
        $(document).on('click', '.promo-code-button', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var code = $btn.data('code');
            var url = $btn.data('url');
            
            if (code) {
                copyToClipboard(code);
                showNotification('Promo code copied: ' + code, 'success');
                
                // Optional: open affiliate URL
                if (url) {
                    setTimeout(function() {
                        window.open(url, '_blank');
                    }, 1000);
                }
            }
        });
    }
    
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }
    
    /**
     * Form Validation
     */
    function initFormValidation() {
        $(document).on('submit', '.parfume-form', function(e) {
            var $form = $(this);
            var isValid = true;
            
            // Clear previous errors
            $form.find('.field-error').removeClass('field-error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('field-error');
                    $field.after('<span class="error-message">This field is required</span>');
                    isValid = false;
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val().trim();
                
                if (email && !isValidEmail(email)) {
                    $field.addClass('field-error');
                    $field.after('<span class="error-message">Please enter a valid email address</span>');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                var $firstError = $form.find('.field-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
            }
        });
    }
    
    function isValidEmail(email) {
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    /**
     * Utility Functions
     */
    function showLoadingSpinner($container) {
        var $spinner = $('<div class="loading-spinner"><div class="spinner"></div><p>' + parfumeReviews.strings.loading + '</p></div>');
        $container.prepend($spinner);
    }
    
    function hideLoadingSpinner($container) {
        $container.find('.loading-spinner').remove();
    }
    
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="parfume-notification ' + type + '">' + message + '</div>');
        $('body').append($notification);
        
        // Animate in
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        // Auto hide after 5 seconds
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 5000);
    }
    
    function updateResultsCount(count) {
        var $counter = $('.results-count');
        if ($counter.length) {
            $counter.text(count + ' results found');
        }
    }
    
    /**
     * CSS for dynamic elements
     */
    function addDynamicStyles() {
        var styles = `
            <style>
            .parfume-lightbox {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .lightbox-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
            }
            
            .lightbox-container {
                position: relative;
                max-width: 90%;
                max-height: 90%;
                z-index: 10000;
            }
            
            .lightbox-image {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
            
            .lightbox-close {
                position: absolute;
                top: -40px;
                right: 0;
                background: none;
                border: none;
                color: white;
                font-size: 30px;
                cursor: pointer;
                z-index: 10001;
            }
            
            .lightbox-prev,
            .lightbox-next {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                background: rgba(255, 255, 255, 0.8);
                border: none;
                font-size: 24px;
                padding: 10px 15px;
                cursor: pointer;
                border-radius: 5px;
            }
            
            .lightbox-prev {
                left: -60px;
            }
            
            .lightbox-next {
                right: -60px;
            }
            
            .lightbox-caption {
                position: absolute;
                bottom: -40px;
                left: 0;
                right: 0;
                color: white;
                text-align: center;
                font-size: 16px;
            }
            
            .loading-spinner {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 40px;
                color: #666;
            }
            
            .spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #0073aa;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-bottom: 15px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .parfume-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 300px;
            }
            
            .parfume-notification.show {
                transform: translateX(0);
            }
            
            .parfume-notification.success {
                background: #28a745;
            }
            
            .parfume-notification.error {
                background: #dc3545;
            }
            
            .parfume-notification.info {
                background: #17a2b8;
            }
            
            .back-to-top {
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 50px;
                width: 50px;
                height: 50px;
                font-size: 18px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.3s ease;
                z-index: 1000;
            }
            
            .back-to-top.visible {
                opacity: 1;
                transform: translateY(0);
            }
            
            .back-to-top:hover {
                background: #005a87;
                transform: translateY(-2px);
            }
            
            .search-autocomplete {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 5px 5px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                max-height: 300px;
                overflow-y: auto;
            }
            
            .search-autocomplete ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .search-autocomplete li {
                border-bottom: 1px solid #eee;
            }
            
            .search-autocomplete li:last-child {
                border-bottom: none;
            }
            
            .search-autocomplete a {
                display: block;
                padding: 10px 15px;
                text-decoration: none;
                color: #333;
                transition: background 0.2s;
            }
            
            .search-autocomplete a:hover {
                background: #f5f5f5;
            }
            
            .search-autocomplete .title {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .search-autocomplete .brand {
                display: block;
                font-size: 0.9em;
                color: #666;
            }
            
            .tooltip {
                position: absolute;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 10000;
                pointer-events: none;
            }
            
            .tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border: 5px solid transparent;
                border-top-color: #333;
            }
            
            body.lightbox-open,
            body.quick-view-open {
                overflow: hidden;
            }
            
            .field-error {
                border-color: #dc3545 !important;
                background-color: #fff5f5;
            }
            
            .error-message {
                color: #dc3545;
                font-size: 12px;
                display: block;
                margin-top: 5px;
            }
            
            .copied {
                background: #28a745 !important;
                color: white !important;
            }
            
            @media (max-width: 768px) {
                .lightbox-prev,
                .lightbox-next {
                    position: fixed;
                    top: 50%;
                    font-size: 20px;
                    padding: 8px 12px;
                }
                
                .lightbox-prev {
                    left: 10px;
                }
                
                .lightbox-next {
                    right: 10px;
                }
                
                .lightbox-close {
                    top: 10px;
                    right: 10px;
                    font-size: 24px;
                }
                
                .parfume-notification {
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }
                
                .back-to-top {
                    bottom: 20px;
                    right: 20px;
                    width: 45px;
                    height: 45px;
                    font-size: 16px;
                }
            }
            </style>
        `;
        
        $('head').append(styles);
    }
    
    // Add dynamic styles on page load
    addDynamicStyles();
    
})(jQuery);