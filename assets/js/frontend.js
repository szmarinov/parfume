/**
 * Parfume Reviews Frontend JavaScript
 * ОБНОВЕН ЗА РАБОТА С МНОЖЕСТВЕНО ФИЛТРИРАНЕ
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
            
            // Show loading state
            showFilterLoading();
            
            // Navigate to new URL
            window.location.href = finalUrl;
        });

        // Handle filter search functionality
        $('.filter-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const $options = $(this).siblings('.scrollable-options, .filter-options').find('.filter-option');
            
            $options.each(function() {
                const optionText = $(this).find('label').text().toLowerCase();
                if (optionText.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Handle collapsible filter sections
        $('.filter-title').on('click', function() {
            const $section = $(this).closest('.filter-section');
            const $options = $section.find('.filter-options');
            
            if ($options.is(':visible')) {
                $options.slideUp(300);
                $(this).addClass('collapsed');
            } else {
                $options.slideDown(300);
                $(this).removeClass('collapsed');
            }
        });

        // Handle active filter tag removal
        $(document).on('click', '.remove-tag', function(e) {
            e.preventDefault();
            const filterType = $(this).data('filter-type');
            const filterValue = $(this).data('filter-value');
            
            // Find and uncheck/clear the corresponding filter
            const $input = $filterForm.find(`input[name="${filterType}[]"][value="${filterValue}"], select[name="${filterType}"]`);
            
            if ($input.is(':checkbox')) {
                $input.prop('checked', false);
            } else {
                $input.val('');
            }
            
            // Resubmit form
            $filterForm.submit();
        });
    }

    function showFilterLoading() {
        $('.parfume-filters').addClass('loading');
        $('.filter-button').prop('disabled', true).text('Зареждане...');
    }

    /**
     * Comparison functionality
     */
    function initComparison() {
        let comparisonItems = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');
        updateComparisonUI();

        // Add to comparison
        $(document).on('click', '.compare-add', function(e) {
            e.preventDefault();
            const parfumeId = $(this).data('id');
            
            if (comparisonItems.includes(parfumeId)) {
                return; // Already added
            }
            
            if (comparisonItems.length >= 4) {
                alert('Можете да сравнявате максимум 4 парфюма');
                return;
            }
            
            comparisonItems.push(parfumeId);
            localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
            updateComparisonUI();
            
            // Visual feedback
            $(this).text('Добавен').addClass('added').prop('disabled', true);
            setTimeout(() => {
                $(this).text('Сравни').removeClass('added').prop('disabled', false);
            }, 2000);
        });

        // Remove from comparison
        $(document).on('click', '.compare-remove', function(e) {
            e.preventDefault();
            const parfumeId = $(this).data('id');
            
            comparisonItems = comparisonItems.filter(id => id !== parfumeId);
            localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
            updateComparisonUI();
        });

        // View comparison
        $(document).on('click', '.view-comparison', function(e) {
            e.preventDefault();
            if (comparisonItems.length === 0) {
                alert('Няма избрани парфюми за сравнение');
                return;
            }
            
            const comparisonUrl = $(this).data('url') || '/parfiumi/compare/';
            window.location.href = comparisonUrl + '?ids=' + comparisonItems.join(',');
        });

        function updateComparisonUI() {
            const count = comparisonItems.length;
            $('.comparison-count').text(count);
            $('.comparison-widget').toggle(count > 0);
            
            // Update button states
            $('.compare-add').each(function() {
                const id = $(this).data('id');
                if (comparisonItems.includes(id)) {
                    $(this).text('Добавен').addClass('added');
                } else {
                    $(this).text('Сравни').removeClass('added');
                }
            });
        }
    }

    /**
     * Single parfume page functionality
     */
    function initSingleParfume() {
        if (!$('body').hasClass('single-parfume-page')) return;

        // Gallery functionality
        $('.parfume-gallery-thumb').on('click', function(e) {
            e.preventDefault();
            const newSrc = $(this).data('full-src');
            $('.parfume-gallery-main img').attr('src', newSrc);
            
            $('.parfume-gallery-thumb').removeClass('active');
            $(this).addClass('active');
        });

        // Tabs functionality
        $('.parfume-tabs .tab-nav a').on('click', function(e) {
            e.preventDefault();
            const targetTab = $(this).attr('href');
            
            $('.parfume-tabs .tab-nav a').removeClass('active');
            $(this).addClass('active');
            
            $('.parfume-tabs .tab-content').removeClass('active');
            $(targetTab).addClass('active');
        });

        // Reviews functionality
        initReviews();
        
        // Share functionality
        initShareButtons();
    }

    function initReviews() {
        // Rating stars interaction
        $('.rating-input .star').on('click', function() {
            const rating = $(this).data('rating');
            const $container = $(this).closest('.rating-input');
            
            $container.find('.star').removeClass('active');
            $container.find('.star').each(function(index) {
                if (index < rating) {
                    $(this).addClass('active');
                }
            });
            
            $container.find('input[type="hidden"]').val(rating);
        });

        // Review form submission
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
                        $form[0].reset();
                        $('.reviews-list').prepend(response.data.html);
                        showNotification('Отзивът е изпратен успешно!', 'success');
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
                    const image = encodeURIComponent($('.parfume-gallery-main img').attr('src'));
                    shareUrl = `https://pinterest.com/pin/create/button/?url=${url}&media=${image}&description=${title}`;
                    break;
                case 'email':
                    shareUrl = `mailto:?subject=${title}&body=${url}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        });
    }

    /**
     * Infinite scroll for archive pages
     */
    function initInfiniteScroll() {
        if (!$('.parfume-archive').length) return;
        
        let loading = false;
        let currentPage = 1;
        const maxPages = parseInt($('.pagination').data('max-pages')) || 1;
        
        $(window).on('scroll', function() {
            if (loading || currentPage >= maxPages) return;
            
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            
            if (scrollTop + windowHeight >= documentHeight - 1000) {
                loadMorePosts();
            }
        });
        
        function loadMorePosts() {
            loading = true;
            currentPage++;
            
            const $loader = $('<div class="loading-more">Зареждане на още парфюми...</div>');
            $('.parfume-grid').after($loader);
            
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('paged', currentPage);
            
            $.get(currentUrl.toString())
                .done(function(data) {
                    const $newPosts = $(data).find('.parfume-card');
                    if ($newPosts.length) {
                        $('.parfume-grid').append($newPosts);
                        // Reinitialize lazy loading for new images
                        initLazyLoading();
                    } else {
                        currentPage = maxPages; // No more posts
                    }
                })
                .fail(function() {
                    showNotification('Грешка при зареждането на още парфюми', 'error');
                    currentPage--; // Revert page increment
                })
                .always(function() {
                    $loader.remove();
                    loading = false;
                });
        }
    }

    /**
     * Lazy loading for images
     */
    function initLazyLoading() {
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

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Tooltip functionality
     */
    function initTooltips() {
        $('[data-tooltip]').on('mouseenter', function() {
            const tooltipText = $(this).data('tooltip');
            const $tooltip = $('<div class="parfume-tooltip">' + tooltipText + '</div>');
            
            $('body').append($tooltip);
            
            const $this = $(this);
            const offset = $this.offset();
            
            $tooltip.css({
                position: 'absolute',
                top: offset.top - $tooltip.outerHeight() - 5,
                left: offset.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                zIndex: 9999
            });
        });

        $('[data-tooltip]').on('mouseleave', function() {
            $('.parfume-tooltip').remove();
        });
    }

    /**
     * Notification system
     */
    function showNotification(message, type = 'info') {
        const $notification = $(`
            <div class="parfume-notification ${type}">
                <span class="message">${message}</span>
                <button class="close">&times;</button>
            </div>
        `);
        
        $('body').append($notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $notification.fadeOut(() => $notification.remove());
        }, 5000);
        
        // Manual close
        $notification.find('.close').on('click', () => {
            $notification.fadeOut(() => $notification.remove());
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
            
            if (query.length < 3) {
                $searchResults.hide();
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        function performSearch(query) {
            $.ajax({
                url: parfumeReviews.ajaxurl,
                method: 'GET',
                data: {
                    action: 'parfume_search',
                    query: query,
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $searchResults.html(response.data.html).show();
                    }
                }
            });
        }
        
        // Close search results when clicking outside
        $(document).on('click', function(e) {
            if (!$searchForm.is(e.target) && !$searchForm.has(e.target).length) {
                $searchResults.hide();
            }
        });
    }

    /**
     * Mobile menu functionality
     */
    function initMobileMenu() {
        $('.mobile-filters-toggle').on('click', function() {
            $('.parfume-filters-wrapper').toggleClass('mobile-open');
            $(this).toggleClass('active');
        });

        // Close mobile filters when clicking outside
        $(document).on('click', function(e) {
            if (!$('.parfume-filters-wrapper').is(e.target) && 
                !$('.parfume-filters-wrapper').has(e.target).length &&
                !$('.mobile-filters-toggle').is(e.target)) {
                $('.parfume-filters-wrapper').removeClass('mobile-open');
                $('.mobile-filters-toggle').removeClass('active');
            }
        });
    }

    /**
     * Price tracking functionality
     */
    function initPriceTracking() {
        $('.track-price-btn').on('click', function(e) {
            e.preventDefault();
            const parfumeId = $(this).data('parfume-id');
            const $btn = $(this);
            
            $btn.prop('disabled', true).text('Обработва...');
            
            $.ajax({
                url: parfumeReviews.ajaxurl,
                method: 'POST',
                data: {
                    action: 'track_parfume_price',
                    parfume_id: parfumeId,
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $btn.text('Проследява се').addClass('tracking');
                        showNotification('Цената се проследява! Ще получите имейл при промяна.', 'success');
                    } else {
                        showNotification('Грешка: ' + response.data.message, 'error');
                        $btn.prop('disabled', false).text('Проследи цената');
                    }
                },
                error: function() {
                    showNotification('Възникна грешка при настройването на проследяването', 'error');
                    $btn.prop('disabled', false).text('Проследи цената');
                }
            });
        });
    }

    /**
     * Wishlist functionality
     */
    function initWishlist() {
        $('.wishlist-toggle').on('click', function(e) {
            e.preventDefault();
            const parfumeId = $(this).data('parfume-id');
            const $btn = $(this);
            const isInWishlist = $btn.hasClass('in-wishlist');
            
            $btn.prop('disabled', true);
            
            $.ajax({
                url: parfumeReviews.ajaxurl,
                method: 'POST',
                data: {
                    action: isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist',
                    parfume_id: parfumeId,
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (isInWishlist) {
                            $btn.removeClass('in-wishlist').html('♡ Добави в желани');
                            showNotification('Премахнато от желани', 'info');
                        } else {
                            $btn.addClass('in-wishlist').html('♥ В желани');
                            showNotification('Добавено в желани', 'success');
                        }
                        
                        // Update wishlist counter
                        $('.wishlist-count').text(response.data.count);
                    } else {
                        showNotification('Грешка: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Възникна грешка', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Image zoom functionality
     */
    function initImageZoom() {
        $('.parfume-image-zoom').on('mouseenter', function() {
            const $img = $(this).find('img');
            const $zoom = $('<div class="zoom-overlay"></div>');
            
            $(this).append($zoom);
            
            $img.on('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                
                $zoom.css({
                    'background-image': `url(${$img.attr('src')})`,
                    'background-position': `${x}% ${y}%`,
                    'background-size': '200%'
                });
            });
        });

        $('.parfume-image-zoom').on('mouseleave', function() {
            $(this).find('.zoom-overlay').remove();
        });
    }

    /**
     * Advanced filtering with URL state management
     */
    function initAdvancedFiltering() {
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            // Reload page to reflect URL changes
            window.location.reload();
        });

        // Update URL when filters change (for bookmarking)
        function updateUrlState() {
            const formData = new FormData($('.parfume-filters form')[0]);
            const urlParams = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value && value.trim() !== '') {
                    urlParams.append(key, value);
                }
            }
            
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            
            // Only update if URL actually changed
            if (newUrl !== window.location.href) {
                history.pushState(null, '', newUrl);
            }
        }
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
                // Scroll-based functionality here
                updateVisibleElements();
            }, 100);
        });

        // Lazy load non-critical elements
        function loadNonCriticalElements() {
            // Load social media widgets, analytics, etc.
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
            if (parfumeReviews.debug) {
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