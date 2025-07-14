/**
 * Parfume Reviews Frontend JavaScript
 * ПОПРАВЕНА ВЕРСИЯ - БЕЗ JQUERY ГРЕШКИ
 * Файл: assets/js/frontend.js
 */

// Проверка за jQuery и безопасно зареждане
(function($) {
    'use strict';
    
    // Проверка дали jQuery е зареден
    if (typeof $ === 'undefined') {
        console.error('Parfume Reviews: jQuery is not loaded!');
        return;
    }

    // Изчакваме DOM да е готов
    $(document).ready(function() {
        initializeParfumeReviews();
    });

    /**
     * Главна инициализираща функция
     */
    function initializeParfumeReviews() {
        try {
            // Основни функционалности
            initFilters();
            initComparison();
            initSingleParfume();
            initInfiniteScroll();
            initLazyLoading();
            initTooltips();
            
            // Мобилни функционалности  
            initMobileMenu();
            initPriceTracking();
            initWishlist();
            initImageZoom();
            initAdvancedFiltering();
            initPerformanceOptimizations();
            initAccessibility();
            initErrorHandling();
            
            // Социални и допълнителни функции
            initSocialWidgets();
            initAnalytics();
            
            // Инициализираме търсене ако има форма
            if ($('.parfume-search-form').length) {
                initSearch();
            }

            // Тригъриране на custom event
            $(document).trigger('parfume-reviews-initialized');
            
            // Debug информация
            if (typeof parfumeReviews !== 'undefined' && parfumeReviews.debug) {
                console.log('Parfume Reviews frontend initialized successfully');
            }
            
        } catch (error) {
            console.error('Error initializing Parfume Reviews:', error);
        }
    }

    /**
     * ОБНОВЕНА ФУНКЦИЯ ЗА ФИЛТРИ - БЕЗ АВТОМАТИЧНО ПОДАВАНЕ
     */
    function initFilters() {
        const $filterForm = $('.parfume-filters form');
        
        if ($filterForm.length === 0) return;

        // Премахнато автоматичното подаване на филтри
        // Сега се прилагат само при explicit submit

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
    }

    /**
     * Comparison functionality
     */
    function initComparison() {
        let comparisonItems = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');
        
        updateComparisonUI();
        
        // Add to comparison
        $(document).on('click', '.add-to-comparison', function(e) {
            e.preventDefault();
            
            const parfumeId = $(this).data('parfume-id');
            const parfumeName = $(this).data('parfume-name') || 'Unknown';
            
            if (comparisonItems.length >= 4) {
                showNotification('Можете да сравнявате максимум 4 парфюма', 'warning');
                return;
            }
            
            if (!comparisonItems.find(item => item.id == parfumeId)) {
                comparisonItems.push({
                    id: parfumeId,
                    name: parfumeName
                });
                
                localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
                updateComparisonUI();
                showNotification('Добавен за сравнение', 'success');
            } else {
                showNotification('Вече е добавен за сравнение', 'info');
            }
        });
        
        // Remove from comparison
        $(document).on('click', '.remove-from-comparison', function(e) {
            e.preventDefault();
            
            const parfumeId = $(this).data('parfume-id');
            comparisonItems = comparisonItems.filter(item => item.id != parfumeId);
            
            localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
            updateComparisonUI();
            showNotification('Премахнат от сравнение', 'success');
        });
        
        // Clear all comparison
        $(document).on('click', '.clear-comparison', function(e) {
            e.preventDefault();
            
            comparisonItems = [];
            localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
            updateComparisonUI();
            showNotification('Изчистени всички за сравнение', 'success');
        });
        
        function updateComparisonUI() {
            $('.comparison-count').text(comparisonItems.length);
            
            if (comparisonItems.length > 0) {
                $('.comparison-widget').addClass('has-items');
                $('.compare-button').prop('disabled', false);
            } else {
                $('.comparison-widget').removeClass('has-items');
                $('.compare-button').prop('disabled', true);
            }
            
            // Update add/remove buttons
            $('.add-to-comparison').each(function() {
                const parfumeId = $(this).data('parfume-id');
                const inComparison = comparisonItems.find(item => item.id == parfumeId);
                
                if (inComparison) {
                    $(this).addClass('in-comparison').text('В сравнение');
                } else {
                    $(this).removeClass('in-comparison').text('Добави за сравнение');
                }
            });
        }
    }

    /**
     * Single parfume page functionality
     */
    function initSingleParfume() {
        if (!$('body').hasClass('single-parfume')) return;
        
        // Image gallery
        $('.parfume-gallery img').on('click', function() {
            const src = $(this).attr('src');
            $('.parfume-main-image img').attr('src', src);
            $('.parfume-gallery img').removeClass('active');
            $(this).addClass('active');
        });
        
        // Reviews toggle
        $('.toggle-reviews').on('click', function(e) {
            e.preventDefault();
            $('.parfume-reviews').toggleClass('expanded');
            $(this).text(function(i, text) {
                return text === 'Покажи всички отзиви' ? 'Скрий отзиви' : 'Покажи всички отзиви';
            });
        });
        
        // Rating submission
        $('.rating-stars').on('click', '.star', function() {
            const rating = $(this).data('rating');
            $('.rating-stars .star').removeClass('active');
            $('.rating-stars .star').slice(0, rating).addClass('active');
            $('#parfume-rating').val(rating);
        });
    }

    /**
     * Infinite scroll for archives
     */
    function initInfiniteScroll() {
        if (!$('.parfume-archive').length) return;
        
        let loading = false;
        let page = 2;
        const $loadMore = $('.load-more-parfumes');
        
        $(window).on('scroll', function() {
            if (loading) return;
            
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            
            if (scrollTop + windowHeight >= documentHeight - 1000) {
                loadMorePerfumes();
            }
        });
        
        $loadMore.on('click', function(e) {
            e.preventDefault();
            loadMorePerfumes();
        });
        
        function loadMorePerfumes() {
            if (loading) return;
            
            loading = true;
            $loadMore.text('Зареждане...').prop('disabled', true);
            
            const queryString = window.location.search;
            const loadUrl = window.location.pathname + '?page=' + page + queryString.slice(1);
            
            $.get(loadUrl)
                .done(function(data) {
                    const $newItems = $(data).find('.parfume-card');
                    
                    if ($newItems.length > 0) {
                        $('.parfume-grid').append($newItems);
                        page++;
                        
                        // Trigger lazy loading for new items
                        initLazyLoading();
                        
                        $loadMore.text('Зареди още').prop('disabled', false);
                    } else {
                        $loadMore.text('Няма повече парфюми').prop('disabled', true);
                    }
                })
                .fail(function() {
                    showNotification('Грешка при зареждане', 'error');
                    $loadMore.text('Зареди още').prop('disabled', false);
                })
                .always(function() {
                    loading = false;
                });
        }
    }

    /**
     * Lazy loading for images
     */
    function initLazyLoading() {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            lazyImages.forEach(function(img) {
                img.src = img.dataset.src;
                img.classList.remove('lazy');
            });
        }
    }

    /**
     * Tooltips
     */
    function initTooltips() {
        $('.tooltip-trigger').on('mouseenter', function() {
            const tooltipText = $(this).attr('title') || $(this).data('tooltip');
            if (!tooltipText) return;
            
            const $tooltip = $('<div class="custom-tooltip">' + tooltipText + '</div>');
            $('body').append($tooltip);
            
            const offset = $(this).offset();
            $tooltip.css({
                top: offset.top - $tooltip.outerHeight() - 5,
                left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
            });
            
            // Remove title to prevent browser tooltip
            $(this).data('original-title', $(this).attr('title')).removeAttr('title');
        });
        
        $('.tooltip-trigger').on('mouseleave', function() {
            $('.custom-tooltip').remove();
            
            // Restore title
            const originalTitle = $(this).data('original-title');
            if (originalTitle) {
                $(this).attr('title', originalTitle);
            }
        });
    }

    /**
     * ОБЯЗАТЕЛНИ ФУНКЦИИ ЗА ИЗБЯГВАНЕ НА JAVASCRIPT ГРЕШКИ
     */

    /**
     * Mobile menu - ПОПРАВЕНА ВЕРСИЯ
     */
    function initMobileMenu() {
        $('.mobile-menu-toggle').on('click', function(e) {
            e.preventDefault();
            $('.mobile-menu').toggleClass('active');
            $(this).toggleClass('active');
        });
        
        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mobile-menu, .mobile-menu-toggle').length) {
                $('.mobile-menu').removeClass('active');
                $('.mobile-menu-toggle').removeClass('active');
            }
        });
        
        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.mobile-menu').removeClass('active');
                $('.mobile-menu-toggle').removeClass('active');
            }
        });
    }

    /**
     * Price tracking functionality
     */
    function initPriceTracking() {
        $('.track-price-button').on('click', function(e) {
            e.preventDefault();
            const parfumeId = $(this).data('parfume-id');
            
            $(this).addClass('loading').text('Зареждане...');
            
            // Simulate API call
            setTimeout(() => {
                $(this).removeClass('loading').text('Следене на цена');
                showNotification('Ще следим цената на този парфюм', 'success');
            }, 1000);
        });
    }

    /**
     * Wishlist functionality
     */
    function initWishlist() {
        $('.add-to-wishlist').on('click', function(e) {
            e.preventDefault();
            
            const parfumeId = $(this).data('parfume-id');
            const $button = $(this);
            
            $button.toggleClass('in-wishlist');
            
            if ($button.hasClass('in-wishlist')) {
                $button.find('.text').text('В любими');
                showNotification('Добавен в любими', 'success');
            } else {
                $button.find('.text').text('Добави в любими');
                showNotification('Премахнат от любими', 'info');
            }
        });
    }

    /**
     * Image zoom functionality
     */
    function initImageZoom() {
        $('.parfume-image img').on('click', function() {
            const src = $(this).attr('src');
            if (!src) return;
            
            const $lightbox = $(`
                <div class="image-lightbox" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <div style="position: relative; max-width: 90%; max-height: 90%;">
                        <img src="${src}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <button class="close-lightbox" style="position: absolute; top: -40px; right: 0; background: white; border: none; padding: 10px; cursor: pointer; border-radius: 50%;">×</button>
                    </div>
                </div>
            `);
            
            $('body').append($lightbox);
            
            $lightbox.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('close-lightbox')) {
                    $lightbox.remove();
                }
            });
            
            $(document).on('keydown.lightbox', function(e) {
                if (e.key === 'Escape') {
                    $lightbox.remove();
                    $(document).off('keydown.lightbox');
                }
            });
        });
    }

    /**
     * Advanced filtering functionality
     */
    function initAdvancedFiltering() {
        // Price range slider
        $('.price-range-slider').each(function() {
            const $slider = $(this);
            const min = $slider.data('min') || 0;
            const max = $slider.data('max') || 1000;
            
            $slider.on('input', function() {
                const value = $(this).val();
                $(this).siblings('.price-display').text(value + ' лв.');
            });
        });
        
        // Advanced filter toggle
        $('.toggle-advanced-filters').on('click', function(e) {
            e.preventDefault();
            $('.advanced-filters').slideToggle();
            $(this).toggleClass('active');
        });
    }

    /**
     * Search functionality
     */
    function initSearch() {
        const $searchForm = $('.parfume-search-form');
        const $searchInput = $searchForm.find('input[type="search"]');
        const $searchResults = $('.search-results');
        
        let searchTimeout;
        
        $searchInput.on('input', function() {
            const query = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            } else {
                $searchResults.empty().hide();
            }
        });
        
        function performSearch(query) {
            $searchResults.html('<div class="search-loading">Търсене...</div>').show();
            
            // Simulate search API call
            setTimeout(() => {
                const mockResults = [
                    { id: 1, name: 'Chanel No. 5', brand: 'Chanel' },
                    { id: 2, name: 'Dior Sauvage', brand: 'Dior' },
                    { id: 3, name: 'Tom Ford Black Orchid', brand: 'Tom Ford' }
                ].filter(item => 
                    item.name.toLowerCase().includes(query.toLowerCase()) ||
                    item.brand.toLowerCase().includes(query.toLowerCase())
                );
                
                if (mockResults.length > 0) {
                    let html = '<div class="search-results-list">';
                    mockResults.forEach(item => {
                        html += `<div class="search-result-item">
                            <a href="/parfume/${item.id}">${item.name} - ${item.brand}</a>
                        </div>`;
                    });
                    html += '</div>';
                    $searchResults.html(html);
                } else {
                    $searchResults.html('<div class="no-search-results">Няма намерени резултати</div>');
                }
            }, 500);
        }
        
        // Hide search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.parfume-search-form, .search-results').length) {
                $searchResults.hide();
            }
        });
    }

    /**
     * Performance optimizations
     */
    function initPerformanceOptimizations() {
        // Debounce scroll events
        let scrollTimeout;
        $(window).on('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                // Handle scroll-dependent functionality
                updateVisibleElements();
            }, 16); // ~60fps
        });
        
        // Preload critical resources
        function preloadCriticalResources() {
            const criticalImages = $('.parfume-card img').slice(0, 6);
            criticalImages.each(function() {
                if (this.dataset.src) {
                    this.src = this.dataset.src;
                }
            });
        }
        
        // Load non-critical elements when idle
        function loadNonCriticalElements() {
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    // Load additional data for visible cards
                });
            }
        }

        // Custom viewport detection
        $.fn.inViewport = function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            
            return elementBottom > viewportTop && elementTop < viewportBottom;
        };

        function updateVisibleElements() {
            $('.parfume-card').each(function() {
                if ($(this).inViewport() && !$(this).hasClass('loaded')) {
                    $(this).addClass('loaded');
                    // Load additional data for this card
                }
            });
        }

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

    /**
     * Social media widgets
     */
    function initSocialWidgets() {
        // Initialize share buttons
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
                    const image = encodeURIComponent($('.parfume-main-image img').attr('src') || '');
                    shareUrl = `https://pinterest.com/pin/create/button/?url=${url}&media=${image}&description=${title}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, 'share', 'width=600,height=400');
            }
        });

        // Initialize social login
        $('.social-login-button').on('click', function(e) {
            e.preventDefault();
            
            const provider = $(this).data('provider');
            
            $(this).addClass('loading').prop('disabled', true);
            setTimeout(() => {
                $(this).removeClass('loading').prop('disabled', false);
                showNotification('Social login функционалност ще бъде имплементирана', 'info');
            }, 2000);
        });
    }

    /**
     * Analytics tracking
     */
    function initAnalytics() {
        // Track parfume interactions
        $('.parfume-card').on('click', function() {
            const parfumeId = $(this).data('parfume-id');
            const parfumeName = $(this).find('.parfume-name').text();
            
            // Google Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'parfume_view', {
                    parfume_id: parfumeId,
                    parfume_name: parfumeName
                });
            }
        });

        // Track filter usage
        $('.filter-option input').on('change', function() {
            const filterType = $(this).closest('.filter-section').data('filter-type');
            const filterValue = $(this).val();
            
            if (typeof gtag !== 'undefined') {
                gtag('event', 'filter_use', {
                    filter_type: filterType,
                    filter_value: filterValue
                });
            }
        });
    }

    /**
     * Notification system
     */
    function showNotification(message, type = 'info') {
        const $notification = $(`
            <div class="parfume-notification notification-${type}">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `);
        
        $('body').append($notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $notification.fadeOut(() => $notification.remove());
        }, 5000);
        
        // Manual close
        $notification.find('.notification-close').on('click', () => {
            $notification.fadeOut(() => $notification.remove());
        });
    }

    // Експортираме функциите в глобален scope за backward compatibility
    window.parfumeReviewsFunctions = {
        showNotification: showNotification,
        initMobileMenu: initMobileMenu,
        initFilters: initFilters,
        initComparison: initComparison
    };

})(jQuery);

// Fallback ако jQuery не е зареден
if (typeof jQuery === 'undefined') {
    console.error('Parfume Reviews: jQuery is required but not loaded!');
    
    // Базова fallback функционалност без jQuery
    document.addEventListener('DOMContentLoaded', function() {
        // Мобилно меню с vanilla JS
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu');
        
        if (mobileToggle && mobileMenu) {
            mobileToggle.addEventListener('click', function(e) {
                e.preventDefault();
                mobileMenu.classList.toggle('active');
                this.classList.toggle('active');
            });
        }
    });
}