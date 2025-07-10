/**
 * Frontend JavaScript for Parfume Catalog Plugin
 * 
 * @package ParfumeCatalog
 */

(function($) {
    'use strict';

    // Global frontend object
    window.ParfumeFrontend = {
        init: function() {
            this.initFilters();
            this.initAZNavigation();
            this.initSearch();
            this.initSmoothScroll();
            this.initLazyLoading();
            this.initTooltips();
            this.initRecentlyViewed();
            this.initViewportAnimations();
            this.initAccessibility();
            this.initSocialShare();
            this.initReadingProgress();
        },

        /**
         * Initialize filter functionality
         */
        initFilters: function() {
            var self = this;

            // Filter form submissions
            $(document).on('click', '#apply-filters', function(e) {
                e.preventDefault();
                self.applyFilters();
            });

            $(document).on('click', '#clear-filters', function(e) {
                e.preventDefault();
                self.clearFilters();
            });

            // Auto-apply filters on select change (optional)
            $(document).on('change', '.filter-group select', function() {
                if ($(this).hasClass('auto-apply')) {
                    self.applyFilters();
                }
            });

            // Sorting functionality
            $(document).on('change', '#sort-by', function() {
                self.applySorting();
            });

            // AJAX filtering (if enabled)
            if (typeof parfume_frontend_ajax !== 'undefined' && parfume_frontend_ajax.ajax_filters) {
                this.initAjaxFilters();
            }
        },

        /**
         * Apply filters
         */
        applyFilters: function() {
            var url = new URL(window.location);
            var hasChanges = false;

            // Get all filter values
            $('.filter-group select').each(function() {
                var $select = $(this);
                var name = $select.attr('name');
                var value = $select.val();

                if (value && value !== '') {
                    url.searchParams.set(name, value);
                    hasChanges = true;
                } else {
                    url.searchParams.delete(name);
                }
            });

            // Apply filters
            if (hasChanges || url.searchParams.toString() !== new URL(window.location).searchParams.toString()) {
                this.showLoadingState();
                window.location.href = url.toString();
            }
        },

        /**
         * Clear all filters
         */
        clearFilters: function() {
            var url = new URL(window.location);
            var hasFilters = false;

            // Remove all filter parameters
            $('.filter-group select').each(function() {
                var name = $(this).attr('name');
                if (url.searchParams.has(name)) {
                    url.searchParams.delete(name);
                    hasFilters = true;
                }
                $(this).val('');
            });

            // Redirect if filters were cleared
            if (hasFilters) {
                this.showLoadingState();
                window.location.href = url.toString();
            }
        },

        /**
         * Apply sorting
         */
        applySorting: function() {
            var url = new URL(window.location);
            var orderby = $('#sort-by').val();

            if (orderby && orderby !== 'date') {
                url.searchParams.set('orderby', orderby);
            } else {
                url.searchParams.delete('orderby');
            }

            this.showLoadingState();
            window.location.href = url.toString();
        },

        /**
         * Initialize AJAX filters
         */
        initAjaxFilters: function() {
            var self = this;

            // Override normal filtering with AJAX
            $(document).off('click', '#apply-filters').on('click', '#apply-filters', function(e) {
                e.preventDefault();
                self.ajaxApplyFilters();
            });

            $(document).off('click', '#clear-filters').on('click', '#clear-filters', function(e) {
                e.preventDefault();
                self.ajaxClearFilters();
            });
        },

        /**
         * AJAX apply filters
         */
        ajaxApplyFilters: function() {
            var self = this;
            var filterData = {};

            // Collect filter data
            $('.filter-group select').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (value) {
                    filterData[name] = value;
                }
            });

            // Add sorting
            var orderby = $('#sort-by').val();
            if (orderby) {
                filterData.orderby = orderby;
            }

            // Show loading
            this.showLoadingState();

            $.ajax({
                url: parfume_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_filter_products',
                    nonce: parfume_frontend_ajax.nonce,
                    filters: filterData,
                    paged: 1
                },
                success: function(response) {
                    if (response.success) {
                        self.updateProductGrid(response.data.html);
                        self.updatePagination(response.data.pagination);
                        self.updateUrl(filterData);
                    } else {
                        self.showMessage('Грешка при филтрирането.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Възникна грешка при филтрирането.', 'error');
                },
                complete: function() {
                    self.hideLoadingState();
                }
            });
        },

        /**
         * AJAX clear filters
         */
        ajaxClearFilters: function() {
            var self = this;

            // Clear all selects
            $('.filter-group select').val('');
            $('#sort-by').val('date');

            // Show loading
            this.showLoadingState();

            $.ajax({
                url: parfume_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_filter_products',
                    nonce: parfume_frontend_ajax.nonce,
                    filters: {},
                    paged: 1
                },
                success: function(response) {
                    if (response.success) {
                        self.updateProductGrid(response.data.html);
                        self.updatePagination(response.data.pagination);
                        self.updateUrl({});
                    }
                },
                error: function() {
                    self.showMessage('Възникна грешка.', 'error');
                },
                complete: function() {
                    self.hideLoadingState();
                }
            });
        },

        /**
         * Update product grid
         */
        updateProductGrid: function(html) {
            var $grid = $('#parfume-results, .parfume-grid');
            
            $grid.fadeOut(200, function() {
                $grid.html(html).fadeIn(200);
                
                // Reinitialize components for new content
                this.reinitializeComponents();
                
                // Scroll to top of results
                $('html, body').animate({
                    scrollTop: $grid.offset().top - 100
                }, 500);
            }.bind(this));
        },

        /**
         * Update pagination
         */
        updatePagination: function(paginationHtml) {
            if (paginationHtml) {
                $('.navigation.pagination').html(paginationHtml);
            }
        },

        /**
         * Update URL without reload
         */
        updateUrl: function(filters) {
            var url = new URL(window.location);
            
            // Clear existing filter params
            $('.filter-group select').each(function() {
                var name = $(this).attr('name');
                url.searchParams.delete(name);
            });
            
            // Add new filter params
            $.each(filters, function(key, value) {
                if (value) {
                    url.searchParams.set(key, value);
                }
            });

            // Update URL
            if (history.pushState) {
                history.pushState(null, null, url.toString());
            }
        },

        /**
         * Initialize A-Z navigation
         */
        initAZNavigation: function() {
            // Smooth scroll for A-Z navigation
            $(document).on('click', '.az-nav a, .group-nav-link', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                var $target = $(target);
                
                if ($target.length) {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 800);
                    
                    // Update active state
                    $(this).addClass('active').siblings().removeClass('active');
                }
            });

            // Highlight active letter on scroll
            if ($('.az-nav').length) {
                this.initAZScrollHighlight();
            }
        },

        /**
         * Initialize A-Z scroll highlighting
         */
        initAZScrollHighlight: function() {
            var $navLinks = $('.az-nav a');
            var $sections = $('.az-list h2, .note-group-section');
            
            $(window).on('scroll', function() {
                var scrollTop = $(window).scrollTop() + 150;
                var current = '';
                
                $sections.each(function() {
                    var $section = $(this);
                    var sectionTop = $section.offset().top;
                    
                    if (scrollTop >= sectionTop) {
                        current = $section.attr('id');
                    }
                });
                
                if (current) {
                    $navLinks.removeClass('active');
                    $navLinks.filter('[href="#' + current + '"]').addClass('active');
                }
            });
        },

        /**
         * Initialize search functionality
         */
        initSearch: function() {
            var self = this;

            // Live search (debounced)
            var searchTimeout;
            $(document).on('input', '.parfume-search-input', function() {
                var query = $(this).val();
                var $input = $(this);
                
                clearTimeout(searchTimeout);
                
                if (query.length >= 3) {
                    searchTimeout = setTimeout(function() {
                        self.performLiveSearch(query, $input);
                    }, 500);
                } else {
                    self.hideLiveSearchResults($input);
                }
            });

            // Search form submission
            $(document).on('submit', '.parfume-search-form', function(e) {
                var query = $(this).find('input[type="search"]').val();
                
                if (!query || query.length < 2) {
                    e.preventDefault();
                    self.showMessage('Моля въведете поне 2 символа за търсене.', 'warning');
                }
            });

            // Close search results on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.parfume-search-container').length) {
                    $('.live-search-results').hide();
                }
            });
        },

        /**
         * Perform live search
         */
        performLiveSearch: function(query, $input) {
            var self = this;
            var $container = $input.closest('.parfume-search-container');
            var $results = $container.find('.live-search-results');
            
            if (!$results.length) {
                $results = $('<div class="live-search-results"></div>');
                $container.append($results);
            }

            $results.html('<div class="search-loading">Търсене...</div>').show();

            $.ajax({
                url: parfume_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_live_search',
                    nonce: parfume_frontend_ajax.nonce,
                    query: query
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.displayLiveSearchResults(response.data, $results);
                    } else {
                        $results.html('<div class="no-results">Няма намерени резултати</div>');
                    }
                },
                error: function() {
                    $results.html('<div class="search-error">Грешка при търсенето</div>');
                }
            });
        },

        /**
         * Display live search results
         */
        displayLiveSearchResults: function(results, $container) {
            var html = '<div class="search-results-list">';
            
            $.each(results, function(index, item) {
                html += `
                    <div class="search-result-item">
                        <a href="${item.url}">
                            ${item.image ? `<img src="${item.image}" alt="${item.title}" class="result-image">` : ''}
                            <div class="result-content">
                                <div class="result-title">${item.title}</div>
                                <div class="result-meta">${item.brand || ''}</div>
                            </div>
                        </a>
                    </div>
                `;
            });
            
            html += '</div>';
            $container.html(html);
        },

        /**
         * Hide live search results
         */
        hideLiveSearchResults: function($input) {
            $input.closest('.parfume-search-container').find('.live-search-results').hide();
        },

        /**
         * Initialize smooth scrolling
         */
        initSmoothScroll: function() {
            // Smooth scroll for anchor links
            $(document).on('click', 'a[href^="#"]', function(e) {
                var href = $(this).attr('href');
                var $target = $(href);
                
                if ($target.length) {
                    e.preventDefault();
                    
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 800);
                }
            });

            // Back to top button
            this.initBackToTop();
        },

        /**
         * Initialize back to top button
         */
        initBackToTop: function() {
            // Create back to top button if it doesn't exist
            if (!$('.back-to-top').length) {
                $('body').append('<button class="back-to-top" aria-label="Връщане към началото" title="Връщане към началото">↑</button>');
            }

            var $backToTop = $('.back-to-top');

            // Show/hide based on scroll position
            $(window).on('scroll', function() {
                if ($(this).scrollTop() > 500) {
                    $backToTop.addClass('visible');
                } else {
                    $backToTop.removeClass('visible');
                }
            });

            // Scroll to top on click
            $backToTop.on('click', function() {
                $('html, body').animate({ scrollTop: 0 }, 800);
            });
        },

        /**
         * Initialize lazy loading for images
         */
        initLazyLoading: function() {
            // Only initialize if IntersectionObserver is supported
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            var src = img.getAttribute('data-src');
                            
                            if (src) {
                                img.src = src;
                                img.classList.remove('lazy');
                                img.classList.add('loaded');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });

                // Observe all lazy images
                document.querySelectorAll('img.lazy').forEach(function(img) {
                    imageObserver.observe(img);
                });
            } else {
                // Fallback for older browsers
                $('img.lazy').each(function() {
                    var $img = $(this);
                    var src = $img.data('src');
                    if (src) {
                        $img.attr('src', src).removeClass('lazy').addClass('loaded');
                    }
                });
            }
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Simple tooltip implementation
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var $element = $(this);
                var text = $element.data('tooltip');
                
                if (text && !$element.find('.tooltip').length) {
                    var $tooltip = $('<div class="tooltip">' + text + '</div>');
                    $element.append($tooltip);
                    
                    // Position tooltip
                    setTimeout(function() {
                        var elementRect = $element[0].getBoundingClientRect();
                        var tooltipRect = $tooltip[0].getBoundingClientRect();
                        
                        // Adjust position if tooltip goes outside viewport
                        if (elementRect.left + tooltipRect.width > window.innerWidth) {
                            $tooltip.addClass('tooltip-left');
                        }
                        
                        if (elementRect.top - tooltipRect.height < 0) {
                            $tooltip.addClass('tooltip-bottom');
                        }
                        
                        $tooltip.addClass('visible');
                    }, 10);
                }
            });

            $(document).on('mouseleave', '[data-tooltip]', function() {
                $(this).find('.tooltip').remove();
            });
        },

        /**
         * Initialize recently viewed functionality
         */
        initRecentlyViewed: function() {
            // Only on single parfume pages
            if ($('body').hasClass('single-parfumes')) {
                this.addToRecentlyViewed();
            }

            // Update recently viewed sections
            this.updateRecentlyViewedSections();
        },

        /**
         * Add current parfume to recently viewed
         */
        addToRecentlyViewed: function() {
            var parfumeId = this.getCurrentParfumeId();
            
            if (!parfumeId) return;

            var recentlyViewed = this.getRecentlyViewed();
            
            // Remove if already exists
            recentlyViewed = recentlyViewed.filter(function(id) {
                return id !== parfumeId;
            });
            
            // Add to beginning
            recentlyViewed.unshift(parfumeId);
            
            // Limit to 10 items
            recentlyViewed = recentlyViewed.slice(0, 10);
            
            // Save to localStorage
            try {
                localStorage.setItem('parfume_recently_viewed', JSON.stringify(recentlyViewed));
            } catch (e) {
                console.error('Error saving recently viewed:', e);
            }
        },

        /**
         * Get recently viewed parfumes
         */
        getRecentlyViewed: function() {
            try {
                var stored = localStorage.getItem('parfume_recently_viewed');
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.error('Error loading recently viewed:', e);
                return [];
            }
        },

        /**
         * Get current parfume ID
         */
        getCurrentParfumeId: function() {
            var $body = $('body');
            var classes = $body.attr('class') || '';
            var match = classes.match(/postid-(\d+)/);
            
            return match ? parseInt(match[1]) : null;
        },

        /**
         * Update recently viewed sections
         */
        updateRecentlyViewedSections: function() {
            var recentlyViewed = this.getRecentlyViewed();
            var currentId = this.getCurrentParfumeId();
            
            // Filter out current parfume
            if (currentId) {
                recentlyViewed = recentlyViewed.filter(function(id) {
                    return id !== currentId;
                });
            }

            if (recentlyViewed.length === 0) {
                $('.recently-viewed-section').hide();
                return;
            }

            // Get parfume data and update sections
            this.loadRecentlyViewedData(recentlyViewed);
        },

        /**
         * Load recently viewed data
         */
        loadRecentlyViewedData: function(ids) {
            var self = this;

            $.ajax({
                url: parfume_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_get_recently_viewed',
                    nonce: parfume_frontend_ajax.nonce,
                    ids: ids.slice(0, 4) // Limit to 4
                },
                success: function(response) {
                    if (response.success) {
                        self.displayRecentlyViewed(response.data);
                    }
                },
                error: function() {
                    $('.recently-viewed-section').hide();
                }
            });
        },

        /**
         * Display recently viewed parfumes
         */
        displayRecentlyViewed: function(parfumes) {
            if (parfumes.length === 0) {
                $('.recently-viewed-section').hide();
                return;
            }

            var html = '';
            $.each(parfumes, function(index, parfume) {
                html += `
                    <div class="parfume-item-small">
                        <a href="${parfume.url}">
                            ${parfume.image ? `<img src="${parfume.image}" alt="${parfume.title}">` : '<div class="no-image">Няма снимка</div>'}
                            <div class="parfume-title">${parfume.title}</div>
                        </a>
                    </div>
                `;
            });

            $('.recently-viewed-list').html(html);
            $('.recently-viewed-section').show();
        },

        /**
         * Initialize viewport animations
         */
        initViewportAnimations: function() {
            if ('IntersectionObserver' in window) {
                var animationObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                            animationObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });

                // Observe elements with animation classes
                document.querySelectorAll('.animate-on-scroll').forEach(function(el) {
                    animationObserver.observe(el);
                });
            }
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Skip link functionality
            $('.skip-link').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $(target).focus();
            });

            // Keyboard navigation for custom elements
            $(document).on('keydown', '.parfume-item', function(e) {
                if (e.which === 13 || e.which === 32) { // Enter or Space
                    var $link = $(this).find('a').first();
                    if ($link.length) {
                        window.location = $link.attr('href');
                    }
                }
            });

            // Focus management for modals/popups
            $(document).on('shown.bs.modal', '.modal', function() {
                $(this).find('[autofocus]').focus();
            });

            // ARIA live regions for dynamic content
            if (!$('#aria-announcements').length) {
                $('body').append('<div id="aria-announcements" aria-live="polite" aria-atomic="true" class="screen-reader-only"></div>');
            }
        },

        /**
         * Initialize social sharing
         */
        initSocialShare: function() {
            $(document).on('click', '.social-share a', function(e) {
                e.preventDefault();
                
                var url = $(this).attr('href');
                var width = 600;
                var height = 400;
                var left = (screen.width / 2) - (width / 2);
                var top = (screen.height / 2) - (height / 2);
                
                window.open(url, 'share', `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`);
            });
        },

        /**
         * Initialize reading progress (for blog posts)
         */
        initReadingProgress: function() {
            if ($('.blog-post').length) {
                var $progress = $('<div class="reading-progress"><div class="progress-bar"></div></div>');
                $('body').append($progress);

                $(window).on('scroll', function() {
                    var $content = $('.post-content');
                    if (!$content.length) return;

                    var contentTop = $content.offset().top;
                    var contentHeight = $content.outerHeight();
                    var scrollTop = $(window).scrollTop();
                    var windowHeight = $(window).height();

                    var progress = Math.max(0, Math.min(100, 
                        ((scrollTop - contentTop + windowHeight) / contentHeight) * 100
                    ));

                    $progress.find('.progress-bar').css('width', progress + '%');
                });
            }
        },

        /**
         * Reinitialize components after AJAX content update
         */
        reinitializeComponents: function() {
            this.initLazyLoading();
            this.initTooltips();
            this.initViewportAnimations();
            
            // Reinitialize comparison if available
            if (window.parfumeComparison && window.parfumeComparison.updateUI) {
                window.parfumeComparison.updateUI();
            }
        },

        /**
         * Show loading state
         */
        showLoadingState: function() {
            $('.parfume-grid, #parfume-results').addClass('loading');
            
            if (!$('.loading-overlay').length) {
                $('body').append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
            }
            
            $('.loading-overlay').fadeIn(200);
        },

        /**
         * Hide loading state
         */
        hideLoadingState: function() {
            $('.parfume-grid, #parfume-results').removeClass('loading');
            $('.loading-overlay').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            var $message = $(`
                <div class="parfume-frontend-message parfume-message-${type}">
                    <span class="message-text">${message}</span>
                    <button class="message-close">×</button>
                </div>
            `);
            
            $('body').append($message);
            
            setTimeout(function() {
                $message.addClass('show');
            }, 100);
            
            // Auto hide
            setTimeout(function() {
                $message.removeClass('show');
                setTimeout(function() {
                    $message.remove();
                }, 300);
            }, 4000);
            
            // Manual close
            $message.find('.message-close').on('click', function() {
                $message.removeClass('show');
                setTimeout(function() {
                    $message.remove();
                }, 300);
            });

            // Announce to screen reader
            $('#aria-announcements').text(message);
        },

        /**
         * Announce to screen reader
         */
        announceToScreenReader: function(message) {
            $('#aria-announcements').text(message);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ParfumeFrontend.init();
    });

    // Expose to global scope
    window.parfumeFrontend = ParfumeFrontend;

})(jQuery);