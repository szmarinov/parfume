/**
 * Parfume Reviews Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initStoreActions();
        initFilters();
        initLazyLoading();
        initSmoothScrolling();
        initRatingDisplay();
        initAromaChart();
        initSectionScrollspy();
    });

    /**
     * Initialize section scrollspy for better navigation
     */
    function initSectionScrollspy() {
        const sections = document.querySelectorAll('.content-section');
        const observerOptions = {
            threshold: 0.3,
            rootMargin: '-50px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add visual indicator when section is in view
                    entry.target.classList.add('in-view');
                } else {
                    entry.target.classList.remove('in-view');
                }
            });
        }, observerOptions);

        sections.forEach(section => {
            observer.observe(section);
        });
    }

    /**
     * Initialize store action buttons
     */
    function initStoreActions() {
        // Handle store button clicks
        $('.store-button').on('click', function(e) {
            const $button = $(this);
            
            // Add loading state
            $button.addClass('loading');
            
            // Track click for analytics (if needed)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    'event_category': 'store_link',
                    'event_label': $button.closest('.store-item').find('.store-name').text()
                });
            }
            
            // Remove loading state after a short delay
            setTimeout(function() {
                $button.removeClass('loading');
            }, 500);
        });

        // Price update functionality
        $('.update-price-btn').on('click', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const storeId = $btn.data('store-id');
            const originalText = $btn.text();
            
            $btn.text(parfumeReviews.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: parfumeReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_store_price',
                    store_id: storeId,
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $priceElement = $btn.closest('.store-item').find('.store-price');
                        $priceElement.text(response.data.price);
                        $priceElement.addClass('updated');
                        
                        showNotification(parfumeReviews.strings.success, 'success');
                    } else {
                        showNotification(response.data || parfumeReviews.strings.error, 'error');
                    }
                },
                error: function() {
                    showNotification(parfumeReviews.strings.error, 'error');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize archive filters
     */
    function initFilters() {
        const $filterForm = $('.parfume-filters form');
        
        if ($filterForm.length === 0) return;

        // Auto-submit on filter change
        $filterForm.on('change', 'select, input', function() {
            const $this = $(this);
            
            // Add small delay to prevent too many requests
            clearTimeout($this.data('timer'));
            $this.data('timer', setTimeout(function() {
                $filterForm.submit();
            }, 300));
        });

        // Clear filters
        $('.clear-filters').on('click', function(e) {
            e.preventDefault();
            
            $filterForm.find('select').val('');
            $filterForm.find('input[type="text"], input[type="number"]').val('');
            $filterForm.submit();
        });

        // Price range slider
        const $priceMin = $('#price_min');
        const $priceMax = $('#price_max');
        
        if ($priceMin.length && $priceMax.length) {
            $priceMin.add($priceMax).on('input', function() {
                const min = parseInt($priceMin.val()) || 0;
                const max = parseInt($priceMax.val()) || 1000;
                
                if (min > max) {
                    if (this === $priceMin[0]) {
                        $priceMax.val(min);
                    } else {
                        $priceMin.val(max);
                    }
                }
            });
        }
    }

    /**
     * Initialize lazy loading for images
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.dataset.src;
                        
                        if (src) {
                            img.src = src;
                            img.classList.remove('lazy');
                            img.classList.add('loaded');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Initialize smooth scrolling
     */
    function initSmoothScrolling() {
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            
            if (target.length) {
                e.preventDefault();
                
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 600, 'easeInOutCubic');
            }
        });
    }

    /**
     * Initialize rating display animations
     */
    function initRatingDisplay() {
        $('.rating-stars').each(function() {
            const $this = $(this);
            const rating = parseFloat($this.data('rating')) || 0;
            
            // Animate stars on load
            $this.find('.star').each(function(index) {
                const $star = $(this);
                
                setTimeout(() => {
                    if (index < Math.floor(rating)) {
                        $star.addClass('filled');
                    } else if (index < rating) {
                        $star.addClass('half');
                    }
                }, index * 100);
            });
        });
    }

    /**
     * Initialize aroma chart animations
     */
    function initAromaChart() {
        const $chartItems = $('.chart-item');
        
        if ($chartItems.length === 0) return;

        // Animate chart bars when in viewport
        const chartObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const $item = $(entry.target);
                    const $fill = $item.find('.chart-fill');
                    const value = parseInt($item.find('.chart-value').text()) || 0;
                    const width = (value / 10) * 100;
                    
                    setTimeout(() => {
                        $fill.css('width', width + '%');
                    }, Math.random() * 500);
                    
                    chartObserver.unobserve(entry.target);
                }
            });
        });

        $chartItems.each(function() {
            chartObserver.observe(this);
        });
    }

    /**
     * Show notification message
     */
    function showNotification(message, type) {
        const $notification = $('<div class="parfume-notification"></div>')
            .addClass('notification-' + type)
            .text(message)
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                backgroundColor: type === 'success' ? '#28a745' : '#dc3545',
                color: 'white',
                borderRadius: '6px',
                zIndex: 9999,
                opacity: 0,
                transform: 'translateX(100%)'
            });

        $('body').append($notification);

        // Animate in
        $notification.animate({
            opacity: 1,
            transform: 'translateX(0)'
        }, 300);

        // Auto remove after 3 seconds
        setTimeout(() => {
            $notification.animate({
                opacity: 0,
                transform: 'translateX(100%)'
            }, 300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Initialize comparison functionality
     */
    function initComparison() {
        let comparisonItems = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');

        $('.add-to-comparison').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = $button.data('post-id');
            const postTitle = $button.data('post-title');
            
            if (comparisonItems.find(item => item.id === postId)) {
                showNotification('Този парфюм вече е добавен за сравнение', 'error');
                return;
            }
            
            if (comparisonItems.length >= 4) {
                showNotification('Можете да сравнявате максимум 4 парфюма', 'error');
                return;
            }
            
            comparisonItems.push({
                id: postId,
                title: postTitle,
                url: window.location.href
            });
            
            localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
            updateComparisonCounter();
            
            $button.text('Добавен за сравнение').prop('disabled', true);
            showNotification('Добавен за сравнение', 'success');
        });

        // Update comparison counter
        function updateComparisonCounter() {
            const count = comparisonItems.length;
            $('.comparison-counter').text(count).toggle(count > 0);
        }

        // Initialize counter
        updateComparisonCounter();
    }

    /**
     * Recently viewed functionality
     */
    function initRecentlyViewed() {
        if ($('body').hasClass('single-parfume')) {
            const postId = $('body').data('post-id') || $('.parfume-single').data('post-id');
            
            if (postId) {
                let viewed = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
                
                // Remove if already exists
                viewed = viewed.filter(item => item.id !== postId);
                
                // Add to beginning
                viewed.unshift({
                    id: postId,
                    title: document.title,
                    url: window.location.href,
                    timestamp: Date.now()
                });
                
                // Keep only last 10
                viewed = viewed.slice(0, 10);
                
                localStorage.setItem('parfume_recently_viewed', JSON.stringify(viewed));
            }
        }
    }

    /**
     * Initialize search functionality
     */
    function initSearch() {
        const $searchInput = $('.parfume-search');
        
        if ($searchInput.length === 0) return;

        let searchTimeout;
        
        $searchInput.on('input', function() {
            const query = $(this).val();
            
            clearTimeout(searchTimeout);
            
            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            } else {
                hideSearchResults();
            }
        });

        function performSearch(query) {
            $.ajax({
                url: parfumeReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_search',
                    query: query,
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showSearchResults(response.data);
                    }
                }
            });
        }

        function showSearchResults(results) {
            let $resultsContainer = $('.search-results');
            
            if ($resultsContainer.length === 0) {
                $resultsContainer = $('<div class="search-results"></div>');
                $searchInput.after($resultsContainer);
            }
            
            let html = '';
            
            if (results.length > 0) {
                results.forEach(item => {
                    html += `
                        <div class="search-result-item">
                            <a href="${item.url}">
                                <div class="result-title">${item.title}</div>
                                <div class="result-brand">${item.brand}</div>
                            </a>
                        </div>
                    `;
                });
            } else {
                html = '<div class="no-results">Няма намерени резултати</div>';
            }
            
            $resultsContainer.html(html).show();
        }

        function hideSearchResults() {
            $('.search-results').hide();
        }

        // Hide results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.parfume-search, .search-results').length) {
                hideSearchResults();
            }
        });
    }

    // Initialize all functionality
    initComparison();
    initRecentlyViewed();
    initSearch();

    // Custom easing function
    $.easing.easeInOutCubic = function(x, t, b, c, d) {
        if ((t /= d / 2) < 1) return c / 2 * t * t * t + b;
        return c / 2 * ((t -= 2) * t * t + 2) + b;
    };

})(jQuery);