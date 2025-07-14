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
            showNotification('Премахнат от сравнение', 'info');
        });
        
        // View comparison
        $('.view-comparison').on('click', function(e) {
            e.preventDefault();
            
            if (comparisonItems.length < 2) {
                showNotification('Добавете поне 2 парфюма за сравнение', 'warning');
                return;
            }
            
            const comparisonUrl = $(this).attr('href') || '/parfumi/sravnenie/';
            const ids = comparisonItems.map(item => item.id).join(',');
            window.location.href = comparisonUrl + '?ids=' + ids;
        });
        
        function updateComparisonUI() {
            $('.comparison-count').text(comparisonItems.length);
            
            if (comparisonItems.length === 0) {
                $('.comparison-bar').hide();
            } else {
                $('.comparison-bar').show();
                $('.comparison-items').html(
                    comparisonItems.map(item => 
                        `<span class="comparison-item">
                            ${item.name}
                            <button class="remove-from-comparison" data-parfume-id="${item.id}">×</button>
                        </span>`
                    ).join('')
                );
            }
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
            openImageLightbox(src);
        });
        
        // Rating system
        $('.rating-stars .star').on('click', function() {
            const rating = $(this).data('rating');
            const $container = $(this).closest('.rating-stars');
            
            $container.find('.star').removeClass('active');
            $container.find('.star').slice(0, rating).addClass('active');
            
            // Update hidden input if exists
            const $input = $container.siblings('input[name="rating"]');
            if ($input.length) {
                $input.val(rating);
            }
            
            showNotification('Оценка: ' + rating + ' звезди', 'info');
        });
        
        // Reviews toggle
        $('.toggle-reviews').on('click', function(e) {
            e.preventDefault();
            const $reviews = $('.parfume-reviews');
            $reviews.slideToggle();
            
            $(this).text(function(i, text) {
                return text === 'Покажи отзиви' ? 'Скрий отзиви' : 'Покажи всички отзиви';
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
        let hasMore = true; // Track if there are more items to load
        const $loadMore = $('.load-more-parfumes');
        
        $(window).on('scroll', function() {
            if (loading || !hasMore) return;
            
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            
            if (scrollTop + windowHeight >= documentHeight - 1000) {
                loadMorePerfumes();
            }
        });
        
        $loadMore.on('click', function(e) {
            e.preventDefault();
            if (!loading && hasMore) {
                loadMorePerfumes();
            }
        });
        
        function loadMorePerfumes() {
            if (loading || !hasMore) return;
            
            loading = true;
            $loadMore.text('Зареждане...').prop('disabled', true);
            
            const queryString = window.location.search;
            const loadUrl = window.location.pathname + '?page=' + page + (queryString ? '&' + queryString.slice(1) : '');
            
            $.get(loadUrl)
                .done(function(data) {
                    const $newItems = $(data).find('.parfume-card');
                    
                    if ($newItems.length > 0) {
                        // Check for existing items to prevent duplicates using multiple methods
                        const existingItems = new Set();
                        
                        $('.parfume-grid .parfume-card').each(function() {
                            // Try multiple ways to get unique identifier
                            const dataId = $(this).data('id') || $(this).data('post-id') || $(this).data('parfume-id');
                            const linkHref = $(this).find('a[href]').first().attr('href');
                            const title = $(this).find('.parfume-title, .parfume-card-title').text().trim();
                            
                            // Create unique identifier from available data
                            let identifier = dataId || linkHref || title;
                            if (identifier) {
                                existingItems.add(identifier);
                            }
                        });
                        
                        // Filter out duplicate items using same logic
                        const $uniqueItems = $newItems.filter(function() {
                            const dataId = $(this).data('id') || $(this).data('post-id') || $(this).data('parfume-id');
                            const linkHref = $(this).find('a[href]').first().attr('href');
                            const title = $(this).find('.parfume-title, .parfume-card-title').text().trim();
                            
                            let identifier = dataId || linkHref || title;
                            
                            // If no identifier found, allow the item (better to have duplicate than missing content)
                            if (!identifier) return true;
                            
                            return !existingItems.has(identifier);
                        });
                        
                        if ($uniqueItems.length > 0) {
                            $('.parfume-grid').append($uniqueItems);
                            page++;
                            
                            // Trigger lazy loading for new items
                            initLazyLoading();
                            
                            $loadMore.text('Зареди още').prop('disabled', false);
                        } else {
                            // All items were duplicates, mark as no more content
                            hasMore = false;
                            $loadMore.text('Няма повече парфюми').prop('disabled', true);
                        }
                    } else {
                        hasMore = false;
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
        
        // Multi-select dropdowns
        $('.multi-select').on('change', function() {
            const $this = $(this);
            const selectedOptions = $this.find('option:selected');
            const selectedText = selectedOptions.length > 0 
                ? selectedOptions.length + ' избрани' 
                : 'Нищо избрано';
            
            $this.siblings('.select-label').text(selectedText);
        });
        
        // Search within filter options
        $('.filter-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const $options = $(this).siblings('.filter-options').find('.filter-option');
            
            $options.each(function() {
                const optionText = $(this).text().toLowerCase();
                $(this).toggle(optionText.includes(searchTerm));
            });
        });
    }

    /**
     * Search functionality
     */
    function initSearch() {
        let searchTimeout;
        
        $('.parfume-search-input').on('input', function() {
            const query = $(this).val();
            const $results = $('.search-results');
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                $results.hide();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                performSearch(query, $results);
            }, 300);
        });
        
        // Handle search form submission
        $('.parfume-search-form').on('submit', function(e) {
            e.preventDefault();
            const query = $(this).find('.parfume-search-input').val();
            if (query.length >= 2) {
                window.location.href = '/parfumi/search/?q=' + encodeURIComponent(query);
            }
        });
        
        function performSearch(query, $results) {
            $results.html('<div class="search-loading">Търсене...</div>').show();
            
            // Simulate search API call
            setTimeout(() => {
                $results.html(`
                    <div class="search-result">
                        <h4>Резултати за "${query}"</h4>
                        <p>Намерени са 5 парфюма</p>
                        <a href="/parfumi/search/?q=${encodeURIComponent(query)}">Виж всички резултати</a>
                    </div>
                `);
            }, 500);
        }
    }

    /**
     * Performance optimizations
     */
    function initPerformanceOptimizations() {
        // Throttle scroll events
        let scrollTimeout;
        $(window).on('scroll', function() {
            if (scrollTimeout) return;
            
            scrollTimeout = setTimeout(function() {
                updateVisibleElements();
                scrollTimeout = null;
            }, 16); // ~60fps
        });
        
        // Preload critical resources
        function preloadCriticalResources() {
            $('.parfume-card img').each(function() {
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
        // Initialize social sharing buttons
        $('.social-share-button').on('click', function(e) {
            e.preventDefault();
            
            const platform = $(this).data('platform');
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            
            let shareUrl = '';
            
            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'pinterest':
                    const image = encodeURIComponent($('.parfume-image img').attr('src') || '');
                    shareUrl = `https://pinterest.com/pin/create/button/?url=${url}&media=${image}&description=${title}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, 'share', 'width=600,height=400');
            }
        });
        
        // Load Instagram feed if element exists
        if ($('.instagram-feed').length) {
            loadInstagramFeed();
        }
        
        function loadInstagramFeed() {
            // Placeholder for Instagram API integration
            $('.instagram-feed').html('<p>Instagram feed ще бъде зареден тук</p>');
        }
    }

    /**
     * Analytics tracking
     */
    function initAnalytics() {
        // Track parfume views
        $('.parfume-card a').on('click', function() {
            const parfumeId = $(this).closest('.parfume-card').data('parfume-id');
            const parfumeName = $(this).closest('.parfume-card').find('.parfume-title').text();
            
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

    /**
     * Image lightbox helper
     */
    function openImageLightbox(src) {
        const $lightbox = $(`
            <div class="image-lightbox" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <div style="position: relative; max-width: 90%; max-height: 90%;">
                    <img src="${src}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    <button class="close-lightbox" style="position: absolute; top: -40px; right: 0; background: white; border: none; padding: 10px; cursor: pointer; border-radius: 50%; font-size: 16px;">×</button>
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
    }

    // Експортираме функциите в глобален scope за backward compatibility
    window.parfumeReviewsFunctions = {
        showNotification: showNotification,
        initMobileMenu: initMobileMenu,
        initFilters: initFilters,
        initComparison: initComparison,
        openImageLightbox: openImageLightbox
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