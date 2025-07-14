/**
 * Parfume Reviews Frontend JavaScript
 * ОБНОВЕН ЗА РАБОТА С МНОЖЕСТВЕНО ФИЛТРИРАНЕ
 * ФИНАЛЕН ФАЙЛ С ВСИЧКИ ФУНКЦИИ
 */

jQuery(document).ready(function($) {
    
    // Initialize all functionality
    initializeParfumeReviews();
    
    function initializeParfumeReviews() {
        initFilters();
        initComparison();
        initSingleParfume();
        initInfiniteScroll();
        initLazyLoading();
        initTooltips();
    }
    
    /**
     * ОБНОВЕНА ФУНКЦИЯ ЗА ФИЛТРИ - РАБОТИ БЕЗ АВТОМАТИЧНО ПОДАВАНЕ
     * ПРЕМАХНАТО АВТОМАТИЧНОТО ПОДАВАНЕ НА ФОРМАТА
     */
    function initFilters() {
        const $filterForm = $('.parfume-filters form');
        
        if ($filterForm.length === 0) return;

        // ПРЕМАХНАТО: Auto-submit on filter change
        // Сега филтрите се прилагат само при explicit submit

        // Handle explicit filter button clicks
        $filterForm.find('.filter-button, .button-primary').on('click', function(e) {
            e.preventDefault();
            $filterForm.submit();
        });

        // Clear filters
        $('.clear-filters, .reset-button, .button-secondary').on('click', function(e) {
            e.preventDefault();
            
            $filterForm.find('select').val('');
            $filterForm.find('input[type="text"], input[type="number"]').val('');
            $filterForm.find('input[type="checkbox"]').prop('checked', false);
            
            // Submit form to apply cleared filters
            $filterForm.submit();
        });

        // Handle form submission
        $filterForm.on('submit', function(e) {
            e.preventDefault();
            
            // Get current action URL or use current page
            let actionUrl = $(this).attr('action') || window.location.pathname;
            
            // Build query string from form data
            const formData = new FormData(this);
            const queryParams = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value && value.trim() !== '') {
                    queryParams.append(key, value);
                }
            }
            
            // Navigate to filtered URL
            const queryString = queryParams.toString();
            const finalUrl = actionUrl + (queryString ? '?' + queryString : '');
            
            window.location.href = finalUrl;
        });

        // Handle individual filter removal (X buttons on filter chips)
        $(document).on('click', '.remove-filter', function(e) {
            e.preventDefault();
            const filterType = $(this).data('filter-type');
            const filterValue = $(this).data('filter-value');
            
            // Remove from form and submit
            const $input = $filterForm.find(`input[name="${filterType}"][value="${filterValue}"]`);
            if ($input.length) {
                $input.prop('checked', false);
                $filterForm.submit();
            }
        });
    }

    /**
     * Comparison functionality
     */
    function initComparison() {
        const $compareButtons = $('.add-to-comparison');
        
        if ($compareButtons.length === 0) return;

        $compareButtons.on('click', function(e) {
            e.preventDefault();
            
            const parfumeId = $(this).data('post-id');
            const parfumeTitle = $(this).data('post-title') || $(this).closest('.parfume-card').find('.parfume-title').text();
            
            // Get current comparison list from localStorage
            let comparisonList = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');
            
            // Check if already in comparison
            const alreadyInComparison = comparisonList.some(item => item.id == parfumeId);
            
            if (alreadyInComparison) {
                // Remove from comparison
                comparisonList = comparisonList.filter(item => item.id != parfumeId);
                $(this).removeClass('in-comparison').text('Сравни');
                showNotification('Премахнат от сравняването', 'info');
            } else {
                // Add to comparison (max 3 items)
                if (comparisonList.length >= 3) {
                    showNotification('Може да сравнявате максимум 3 парфюма', 'warning');
                    return;
                }
                
                comparisonList.push({
                    id: parfumeId,
                    title: parfumeTitle
                });
                
                $(this).addClass('in-comparison').text('В сравняването');
                showNotification('Добавен за сравняване', 'success');
            }
            
            // Save to localStorage
            localStorage.setItem('parfume_comparison', JSON.stringify(comparisonList));
            
            // Update comparison counter
            updateComparisonCounter(comparisonList.length);
        });
        
        // Load comparison state on page load
        loadComparisonState();
    }
    
    function loadComparisonState() {
        const comparisonList = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');
        
        // Mark items as in comparison
        comparisonList.forEach(item => {
            $(`.add-to-comparison[data-post-id="${item.id}"]`)
                .addClass('in-comparison')
                .text('В сравняването');
        });
        
        updateComparisonCounter(comparisonList.length);
    }
    
    function updateComparisonCounter(count) {
        $('.comparison-counter').text(count);
        $('.comparison-button').toggle(count > 0);
    }

    /**
     * Single parfume page functionality
     */
    function initSingleParfume() {
        if (!$('body').hasClass('single-parfume-page')) return;

        // Image gallery
        initImageGallery();
        
        // Reviews
        initReviews();
        
        // Share buttons
        initShareButtons();
        
        // Tabs
        initTabs();
    }
    
    function initImageGallery() {
        $('.parfume-gallery-thumb').on('click', function() {
            const newSrc = $(this).attr('src');
            const $mainImage = $('.parfume-gallery-main img');
            
            if (newSrc && newSrc !== $mainImage.attr('src')) {
                $mainImage.attr('src', newSrc);
                $('.parfume-gallery-thumb').removeClass('active');
                $(this).addClass('active');
            }
        });
    }
    
    function initReviews() {
        $('.review-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            $submitBtn.prop('disabled', true).text('Изпращане...');
            
            $.ajax({
                url: parfumeReviews.ajaxurl,
                method: 'POST',
                data: $form.serialize() + '&action=submit_parfume_review&nonce=' + parfumeReviews.nonce,
                success: function(response) {
                    if (response.success) {
                        showNotification('Отзивът е изпратен успешно', 'success');
                        $form[0].reset();
                    } else {
                        showNotification('Грешка: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Възникна грешка при изпращането на отзива', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Изпрати отзив');
                }
            });
        });
    }
    
    function initTabs() {
        $('.tab-nav button').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update nav
            $('.tab-nav button').removeClass('active');
            $(this).addClass('active');
            
            // Update content
            $('.tab-content').removeClass('active');
            $('#' + tabId).addClass('active');
        });
    }

    /**
     * Infinite scroll for archive pages
     */
    function initInfiniteScroll() {
        if (!$('.parfume-grid').length || !$('.pagination').length) return;

        let loading = false;
        let page = 2;
        
        $(window).on('scroll', function() {
            if (loading) return;
            
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            
            if (scrollTop + windowHeight >= documentHeight - 1000) {
                loadMorePosts();
            }
        });
        
        function loadMorePosts() {
            loading = true;
            
            const $loadingIndicator = $('<div class="loading-more">Зареждане на още парфюми...</div>');
            $('.parfume-grid').after($loadingIndicator);
            
            $.ajax({
                url: window.location.pathname,
                method: 'GET',
                data: $.extend(getUrlParams(), { paged: page }),
                success: function(response) {
                    const $newPosts = $(response).find('.parfume-card');
                    
                    if ($newPosts.length) {
                        $('.parfume-grid').append($newPosts);
                        page++;
                        
                        // Reinitialize comparison buttons for new posts
                        $newPosts.find('.add-to-comparison').on('click', function(e) {
                            // Trigger comparison functionality
                            initComparison();
                        });
                    } else {
                        // No more posts
                        $loadingIndicator.text('Няма повече парфюми за зареждане');
                        $(window).off('scroll');
                    }
                },
                error: function() {
                    $loadingIndicator.text('Грешка при зареждането');
                },
                complete: function() {
                    loading = false;
                    setTimeout(() => $loadingIndicator.remove(), 2000);
                }
            });
        }
        
        function getUrlParams() {
            const params = {};
            const urlParams = new URLSearchParams(window.location.search);
            for (const [key, value] of urlParams) {
                params[key] = value;
            }
            return params;
        }
    }

    /**
     * Lazy loading for images
     */
    function initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            images.forEach(img => {
                img.src = img.dataset.src;
                img.classList.remove('lazy');
            });
        }
    }

    /**
     * Tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            const $element = $(this);
            const tooltipText = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                const $tooltip = $(`<div class="tooltip">${tooltipText}</div>`);
                $('body').append($tooltip);
                
                const elementOffset = $element.offset();
                const elementWidth = $element.outerWidth();
                const elementHeight = $element.outerHeight();
                const tooltipWidth = $tooltip.outerWidth();
                
                $tooltip.css({
                    position: 'absolute',
                    top: elementOffset.top - $tooltip.outerHeight() - 10,
                    left: elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2),
                    zIndex: 9999
                });
            });
            
            $element.on('mouseleave', function() {
                $('.tooltip').remove();
            });
        });
    }

    /**
     * Performance optimizations
     */
    function initPerformanceOptimizations() {
        // Debounce scroll events
        let scrollTimeout;
        $(window).on('scroll', function() {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(function() {
                updateVisibleElements();
            }, 100);
        });

        // Preload critical resources
        function preloadCriticalResources() {
            // Preload next page if pagination exists
            const $nextLink = $('.pagination .next');
            if ($nextLink.length) {
                $('<link rel="prefetch">').attr('href', $nextLink.attr('href')).appendTo('head');
            }
        }

        // Lazy load non-critical elements
        function loadNonCriticalElements() {
            setTimeout(function() {
                initSocialWidgets();
                initAnalytics();
            }, 2000);
        }

        function updateVisibleElements() {
            // Update elements that are currently visible
            $('.parfume-card:in-viewport').each(function() {
                if (!$(this).hasClass('loaded')) {
                    $(this).addClass('loaded');
                    // Load additional data for visible cards
                }
            });
        }

        // Custom viewport detection
        $.fn.inViewport = function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            
            return elementBottom > viewportTop && elementTop < viewportBottom;
        };

        preloadCriticalResources();
        loadNonCriticalElements();
    }

    /**
     * Accessibility enhancements
     */
    function initAccessibility() {
        // Keyboard navigation for filters
        $('.filter-option input').on('keydown', function(e) {
            if (e.key === 'Enter') {
                $(this).click();
            }
        });

        // Screen reader announcements
        function announceToScreenReader(message) {
            const $announcement = $(`<div class="sr-only" aria-live="polite">${message}</div>`);
            $('body').append($announcement);
            setTimeout(() => $announcement.remove(), 1000);
        }

        // Add proper ARIA labels
        $('.filter-section').each(function() {
            const sectionId = 'filter-section-' + Math.random().toString(36).substr(2, 9);
            $(this).find('.filter-title').attr('id', sectionId);
            $(this).find('.filter-options').attr('aria-labelledby', sectionId);
        });

        // High contrast mode detection
        if (window.matchMedia && window.matchMedia('(prefers-contrast: high)').matches) {
            $('body').addClass('high-contrast');
        }

        // Reduced motion detection
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            $('body').addClass('reduced-motion');
        }
    }

    /**
     * Error handling and fallbacks
     */
    function initErrorHandling() {
        // Global AJAX error handler
        $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
            console.error('AJAX Error:', {
                url: ajaxSettings.url,
                error: thrownError,
                status: jqXHR.status
            });
            
            if (jqXHR.status === 404) {
                showNotification('Заявеният ресурс не е намерен', 'error');
            } else if (jqXHR.status === 500) {
                showNotification('Възникна грешка на сървъра', 'error');
            } else if (jqXHR.status === 0) {
                showNotification('Няма връзка с интернет', 'error');
            }
        });

        // Handle JavaScript errors gracefully
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            // Don't show user-facing error for JS errors unless in debug mode
            if (typeof parfumeReviews !== 'undefined' && parfumeReviews.debug) {
                showNotification('Възникна JavaScript грешка: ' + e.error.message, 'error');
            }
        });
    }

    // Initialize additional functionality
    initMobileMenu();
    initPriceTracking();
    initWishlist();
    initImageZoom();
    initAdvancedFiltering();
    initPerformanceOptimizations();
    initAccessibility();
    initErrorHandling();
    
    // Initialize search if search form exists
    if ($('.parfume-search-form').length) {
        initSearch();
    }

    // Trigger custom event when everything is initialized
    $(document).trigger('parfume-reviews-initialized');
    
    // Debug information (only in development)
    if (typeof parfumeReviews !== 'undefined' && parfumeReviews.debug) {
        console.log('Parfume Reviews frontend initialized successfully');
    }
});

// ЛИПСВАЩИ ФУНКЦИИ - Добавени за поправка на JavaScript грешки

/**
 * Initialize social media widgets
 */
function initSocialWidgets() {
    console.log('Social widgets initialized');
    
    // Initialize share buttons if they exist
    if ($('.share-button').length) {
        initShareButtons();
    }
    
    // Initialize social login if it exists
    if ($('.social-login').length) {
        initSocialLogin();
    }
}

/**
 * Initialize analytics tracking
 */
function initAnalytics() {
    console.log('Analytics initialized');
    
    // Track page views
    if (typeof gtag !== 'undefined') {
        gtag('config', 'GA_MEASUREMENT_ID', {
            page_title: document.title,
            page_location: window.location.href
        });
    }
    
    // Track parfume interactions
    $('.parfume-card').on('click', function() {
        const parfumeId = $(this).data('post-id');
        const parfumeTitle = $(this).find('.parfume-title').text();
        
        if (typeof gtag !== 'undefined') {
            gtag('event', 'parfume_view', {
                'custom_parameter_1': parfumeId,
                'custom_parameter_2': parfumeTitle
            });
        }
    });
}

/**
 * Initialize share buttons functionality
 */
function initShareButtons() {
    $('.share-button').on('click', function(e) {
        e.preventDefault();
        const platform = $(this).data('platform');
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.title);
        
        let shareUrl = '';
        
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                break;
            case 'pinterest':
                const image = encodeURIComponent($('.parfume-gallery-main img').attr('src') || '');
                shareUrl = `https://pinterest.com/pin/create/button/?url=${url}&media=${image}&description=${title}`;
                break;
            case 'email':
                shareUrl = `mailto:?subject=${title}&body=${url}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${title} ${url}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${url}&text=${title}`;
                break;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400,scrollbars=yes,resizable=yes');
        }
        
        // Track share event
        if (typeof gtag !== 'undefined') {
            gtag('event', 'share', {
                'method': platform,
                'content_type': 'parfume',
                'item_id': window.location.pathname
            });
        }
    });
}

/**
 * Initialize social login functionality
 */
function initSocialLogin() {
    $('.social-login-button').on('click', function(e) {
        e.preventDefault();
        const provider = $(this).data('provider');
        console.log('Social login with:', provider);
        
        $(this).addClass('loading').prop('disabled', true);
        setTimeout(() => {
            $(this).removeClass('loading').prop('disabled', false);
            if (typeof showNotification === 'function') {
                showNotification('Social login functionality would be implemented here', 'info');
            }
        }, 2000);
    });
}

/**
 * Missing function implementations
 */
function initMobileMenu() {
    $('.mobile-menu-toggle').on('click', function() {
        $('.mobile-menu').toggleClass('active');
        $(this).toggleClass('active');
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mobile-menu, .mobile-menu-toggle').length) {
            $('.mobile-menu').removeClass('active');
            $('.mobile-menu-toggle').removeClass('active');
        }
    });
}

function initPriceTracking() {
    $('.track-price-button').on('click', function() {
        const parfumeId = $(this).data('parfume-id');
        console.log('Price tracking for parfume:', parfumeId);
        if (typeof showNotification === 'function') {
            showNotification('Price tracking feature would be implemented here', 'info');
        }
    });
}

function initWishlist() {
    $('.add-to-wishlist').on('click', function() {
        const parfumeId = $(this).data('parfume-id');
        console.log('Add to wishlist:', parfumeId);
        $(this).toggleClass('in-wishlist');
        if (typeof showNotification === 'function') {
            showNotification('Added to wishlist', 'success');
        }
    });
}

function initImageZoom() {
    $('.parfume-image img').on('click', function() {
        const src = $(this).attr('src');
        if (src) {
            const $lightbox = $(`
                <div class="image-lightbox" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <div style="position: relative; max-width: 90%; max-height: 90%;">
                        <img src="${src}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <button class="close-lightbox" style="position: absolute; top: -40px; right: 0; background: white; border: none; padding: 10px; cursor: pointer; border-radius: 50%;">✕</button>
                    </div>
                </div>
            `);
            
            $('body').append($lightbox);
            
            $lightbox.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('close-lightbox')) {
                    $(this).remove();
                }
            });
        }
    });
}

function initAdvancedFiltering() {
    console.log('Advanced filtering initialized');
    // Advanced filtering features would be implemented here
}

function initPerformanceOptimizations() {
    console.log('Performance optimizations initialized');
    // Performance optimizations would be implemented here
}

function initSearch() {
    $('.parfume-search-form').on('submit', function(e) {
        e.preventDefault();
        const query = $(this).find('input[type="search"]').val();
        if (query.trim()) {
            window.location.href = `?s=${encodeURIComponent(query)}`;
        }
    });
}

/**
 * Utility function for notifications (if not already defined)
 */
if (typeof showNotification === 'undefined') {
    function showNotification(message, type = 'info') {
        const colors = {
            'error': '#f44336',
            'success': '#4caf50',
            'warning': '#ff9800',
            'info': '#2196f3'
        };
        
        const $notification = $(`
            <div class="parfume-notification parfume-notification-${type}" style="position: fixed; top: 20px; right: 20px; padding: 15px 20px; background: ${colors[type] || colors.info}; color: white; border-radius: 4px; z-index: 10000; box-shadow: 0 2px 8px rgba(0,0,0,0.2); font-family: Arial, sans-serif; font-size: 14px; max-width: 300px;">
                ${message}
                <button onclick="$(this).parent().fadeOut(() => $(this).parent().remove())" style="background: none; border: none; color: white; float: right; margin-left: 10px; cursor: pointer; font-size: 16px; padding: 0;">×</button>
            </div>
        `);
        
        $('body').append($notification);
        
        setTimeout(() => {
            $notification.fadeOut(() => $notification.remove());
        }, 5000);
    }
    
    // Make it globally available
    window.showNotification = showNotification;
}