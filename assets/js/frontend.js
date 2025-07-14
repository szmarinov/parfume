/**
 * Parfume Reviews Frontend JavaScript
 * ПОПРАВЕНА ВЕРСИЯ - БЕЗ ДУБЛИРАНЕ НА ПАРФЮМИ ПРИ СКРОЛВАНЕ
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
            window.location.href = window.location.pathname;
        });

        // Handle filter submissions
        $filterForm.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const filterParams = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value && value !== '' && value !== 'all') {
                    filterParams.append(key, value);
                }
            }
            
            const queryString = filterParams.toString();
            const newUrl = queryString ? 
                window.location.pathname + '?' + queryString : 
                window.location.pathname;
            
            window.location.href = newUrl;
        });
    }

    /**
     * ПОПРАВЕНА ФУНКЦИЯ ЗА INFINITE SCROLL - БЕЗ ДУБЛИРАНЕ
     */
    function initInfiniteScroll() {
        if (!$('.parfume-archive').length) return;
        
        let loading = false;
        let page = 2;
        let maxPages = 10; // Limit to prevent infinite loading
        const $loadMore = $('.load-more-parfumes');
        const $grid = $('.parfume-grid, .parfumes-grid');
        
        // Tracking existing perfume IDs to prevent duplicates
        const existingPerfumeIds = new Set();
        
        // Initialize with existing perfumes on page load
        function initializeExistingPerfumes() {
            $grid.find('.parfume-card').each(function() {
                const perfumeId = $(this).data('parfume-id') || $(this).find('[data-parfume-id]').data('parfume-id');
                if (perfumeId) {
                    existingPerfumeIds.add(perfumeId.toString());
                }
            });
        }
        
        // Call initialization
        initializeExistingPerfumes();
        
        $(window).on('scroll', function() {
            if (loading || page > maxPages) return;
            
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
            if (loading || page > maxPages) return;
            
            loading = true;
            $loadMore.text('Зареждане...').prop('disabled', true);
            
            const queryString = window.location.search;
            const pageParam = queryString ? '&paged=' + page : '?paged=' + page;
            const loadUrl = window.location.pathname + pageParam + (queryString ? queryString.substring(1) : '');
            
            $.get(loadUrl)
                .done(function(data) {
                    const $response = $(data);
                    const $newItems = $response.find('.parfume-card');
                    
                    if ($newItems.length > 0) {
                        // Filter out duplicates
                        const $uniqueItems = $newItems.filter(function() {
                            const perfumeId = $(this).data('parfume-id') || $(this).find('[data-parfume-id]').data('parfume-id');
                            
                            if (!perfumeId) {
                                return true; // Keep items without IDs
                            }
                            
                            const id = perfumeId.toString();
                            if (existingPerfumeIds.has(id)) {
                                return false; // Exclude duplicates
                            }
                            
                            existingPerfumeIds.add(id);
                            return true;
                        });
                        
                        if ($uniqueItems.length > 0) {
                            // Add new unique items to grid
                            $grid.append($uniqueItems);
                            page++;
                            
                            // Trigger lazy loading for new items
                            initLazyLoading();
                            
                            // Check if we reached the end
                            const $pagination = $response.find('.parfume-pagination, .blog-pagination');
                            const hasNextPage = $pagination.find('.next').length > 0;
                            
                            if (!hasNextPage) {
                                $loadMore.text('Няма повече парфюми').prop('disabled', true);
                                maxPages = page - 1; // Set limit
                            } else {
                                $loadMore.text('Зареди още').prop('disabled', false);
                            }
                        } else {
                            // All items were duplicates
                            $loadMore.text('Няма повече парфюми').prop('disabled', true);
                        }
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
     * Tooltips initialization
     */
    function initTooltips() {
        $('[data-tooltip]').hover(
            function() {
                const tooltip = $(this).attr('data-tooltip');
                $(this).append('<div class="tooltip">' + tooltip + '</div>');
            },
            function() {
                $(this).find('.tooltip').remove();
            }
        );
    }

    /**
     * Comparison functionality
     */
    function initComparison() {
        let comparisonItems = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');
        
        // Update UI on page load
        updateComparisonUI();
        
        // Add to comparison
        $(document).on('click', '.add-to-comparison', function(e) {
            e.preventDefault();
            
            if (comparisonItems.length >= 4) {
                showNotification('Можете да сравнявате максимум 4 парфюма', 'warning');
                return;
            }
            
            const parfumeId = $(this).data('parfume-id');
            const parfumeName = $(this).data('parfume-name') || 'Парфюм';
            
            if (!comparisonItems.find(item => item.id == parfumeId)) {
                comparisonItems.push({
                    id: parfumeId,
                    name: parfumeName
                });
                
                localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
                updateComparisonUI();
                showNotification('Добавен за сравнение', 'success');
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
                return text === 'Покажи всички отзиви' ?
                'Скрий отзиви' : 'Покажи всички отзиви';
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
            
            if (query.length < 2) {
                $searchResults.hide();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                // Mock search results - replace with actual AJAX call
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
     * Mobile menu functionality
     */
    function initMobileMenu() {
        $('.mobile-menu-toggle').on('click', function() {
            $('.mobile-menu').toggleClass('active');
        });
        
        $('.mobile-menu .menu-item-has-children > a').on('click', function(e) {
            e.preventDefault();
            $(this).parent().toggleClass('open');
        });
    }

    /**
     * Price tracking
     */
    function initPriceTracking() {
        $('.track-price').on('click', function(e) {
            e.preventDefault();
            const parfumeId = $(this).data('parfume-id');
            // Implement price tracking logic
            showNotification('Цената ще бъде следена', 'success');
        });
    }

    /**
     * Wishlist functionality
     */
    function initWishlist() {
        $('.add-to-wishlist').on('click', function(e) {
            e.preventDefault();
            const parfumeId = $(this).data('parfume-id');
            // Toggle wishlist status
            $(this).toggleClass('in-wishlist');
            const message = $(this).hasClass('in-wishlist') ? 
                'Добавен в любими' : 'Премахнат от любими';
            showNotification(message, 'success');
        });
    }

    /**
     * Image zoom functionality
     */
    function initImageZoom() {
        $('.parfume-image img').on('mouseenter', function() {
            $(this).addClass('zoomed');
        }).on('mouseleave', function() {
            $(this).removeClass('zoomed');
        });
    }

    /**
     * Advanced filtering
     */
    function initAdvancedFiltering() {
        $('.filter-toggle').on('click', function() {
            $('.advanced-filters').toggleClass('open');
        });
        
        // Range sliders
        $('.price-range').each(function() {
            const $slider = $(this);
            const min = parseInt($slider.data('min'));
            const max = parseInt($slider.data('max'));
            
            // Initialize range slider (if library available)
            if (typeof noUiSlider !== 'undefined') {
                noUiSlider.create($slider[0], {
                    start: [min, max],
                    range: { 'min': min, 'max': max },
                    connect: true
                });
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
            const $section = $(this);
            const title = $section.find('.filter-title').text();
            $section.attr('aria-label', title);
        });
    }

    /**
     * Error handling
     */
    function initErrorHandling() {
        window.addEventListener('error', function(e) {
            console.error('Parfume Reviews Error:', e.error);
            // Don't show error to users unless in debug mode
            if (typeof parfumeReviews !== 'undefined' && parfumeReviews.debug) {
                showNotification('Възникна грешка. Моля, презаредете страницата.', 'error');
            }
        });
    }

    /**
     * Social widgets
     */
    function initSocialWidgets() {
        $('.share-button').on('click', function(e) {
            e.preventDefault();
            const url = $(this).data('url') || window.location.href;
            const text = $(this).data('text') || document.title;
            const platform = $(this).data('platform');
            
            let shareUrl = '';
            switch(platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`;
                    break;
                case 'pinterest':
                    shareUrl = `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(url)}&description=${encodeURIComponent(text)}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, 'share', 'width=600,height=400');
            }
        });
    }

    /**
     * Analytics tracking
     */
    function initAnalytics() {
        // Track parfume card clicks
        $('.parfume-card a').on('click', function() {
            const parfumeName = $(this).find('.parfume-name').text();
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    event_category: 'Parfume',
                    event_label: parfumeName
                });
            }
        });
        
        // Track filter usage
        $('.filter-option input').on('change', function() {
            const filterType = $(this).closest('.filter-section').find('.filter-title').text();
            const filterValue = $(this).val();
            if (typeof gtag !== 'undefined') {
                gtag('event', 'filter_use', {
                    event_category: 'Filter',
                    event_label: `${filterType}: ${filterValue}`
                });
            }
        });
    }

    /**
     * Show notification helper
     */
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="parfume-notification ${type}">
                ${message}
                <button class="close-notification">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
        
        // Close on click
        notification.find('.close-notification').on('click', () => {
            notification.fadeOut(() => notification.remove());
        });
    }

})(jQuery);