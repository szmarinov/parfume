/**
 * Parfume Reviews Frontend JavaScript
 * МОДИФИЦИРАН ЗА ДА СЕ ПРЕМАХНЕ АВТОМАТИЧНОТО ФИЛТРИРАНЕ
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
        initComparison();
        initRecentlyViewed();
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
     * Initialize archive filters - МОДИФИЦИРАНО!
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
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
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
        $('a[href*="#"]:not([href="#"])').click(function() {
            if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
                var target = $(this.hash);
                target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 1000);
                    return false;
                }
            }
        });
    }

    /**
     * Initialize rating display
     */
    function initRatingDisplay() {
        $('.rating-stars').each(function() {
            const $stars = $(this);
            const rating = parseFloat($stars.data('rating')) || 0;
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            
            $stars.empty();
            
            // Add full stars
            for (let i = 0; i < fullStars; i++) {
                $stars.append('<span class="star full">★</span>');
            }
            
            // Add half star if needed
            if (hasHalfStar) {
                $stars.append('<span class="star half">★</span>');
            }
            
            // Add empty stars
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
            for (let i = 0; i < emptyStars; i++) {
                $stars.append('<span class="star empty">☆</span>');
            }
        });
    }

    /**
     * Initialize aroma chart
     */
    function initAromaChart() {
        const $aromaChart = $('#aroma-chart');
        if ($aromaChart.length && typeof Chart !== 'undefined') {
            const ctx = $aromaChart[0].getContext('2d');
            const data = $aromaChart.data('chart-data');
            
            new Chart(ctx, {
                type: 'radar',
                data: data,
                options: {
                    responsive: true,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const $notification = $('<div class="parfume-notification ' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(() => {
            $notification.addClass('show');
        }, 100);
        
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Comparison functionality
     */
    function initComparison() {
        let comparisonItems = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');

        // Add to comparison
        $('.add-to-comparison').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = parseInt($button.data('post-id'));
            const postTitle = $button.data('post-title') || document.title;
            
            // Check if already added
            if (comparisonItems.find(item => item.id === postId)) {
                showNotification('Вече е добавен за сравнение', 'warning');
                return;
            }
            
            // Check limit
            if (comparisonItems.length >= 3) {
                showNotification('Максимум 3 парфюма за сравнение', 'error');
                return;
            }
            
            // Add to comparison
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

})(jQuery);