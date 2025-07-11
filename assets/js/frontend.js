/**
 * Parfume Catalog Frontend JavaScript
 * 
 * Основна JavaScript функционалност за frontend
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Глобален обект за парфюм каталога
    window.parfumeCatalog = {
        
        // Настройки
        settings: {
            recentlyViewedLimit: 6,
            storageKey: 'parfume_recently_viewed',
            comparisonKey: 'parfume_comparison',
            ajaxUrl: window.parfume_catalog_ajax ? window.parfume_catalog_ajax.ajax_url : '/wp-admin/admin-ajax.php',
            nonce: window.parfume_catalog_ajax ? window.parfume_catalog_ajax.nonce : ''
        },

        // Инициализация
        init: function() {
            this.initDOM();
            this.initEvents();
            this.loadRecentlyViewed();
            this.initLazyLoading();
            this.initTooltips();
            this.initViewportAnimations();
            this.initAccessibility();
            this.initPromoCodeCopy();
            this.initVariantSelection();
            this.initStoreActions();
            
            console.log('Parfume Catalog Frontend initialized');
        },

        // DOM елементи
        initDOM: function() {
            this.$body = $('body');
            this.$recentlyViewedContainer = $('#recently-viewed-parfumes');
            this.$comparisonBtn = $('.parfume-compare-btn');
            this.$storeItems = $('.store-item');
            this.$variantBtns = $('.variant-btn');
            this.$promoBtns = $('.promo-code-btn');
        },

        // Event listeners
        initEvents: function() {
            var self = this;

            // Comparison button clicks
            $(document).on('click', '.parfume-compare-btn', function(e) {
                e.preventDefault();
                self.toggleComparison($(this));
            });

            // Variant selection
            $(document).on('click', '.variant-btn', function(e) {
                e.preventDefault();
                self.selectVariant($(this));
            });

            // Promo code copy
            $(document).on('click', '.promo-code-btn', function(e) {
                e.preventDefault();
                self.copyPromoCode($(this));
            });

            // Store button clicks
            $(document).on('click', '.store-btn', function(e) {
                self.trackStoreClick($(this));
            });

            // Filter form submissions
            $(document).on('submit', '.filters-form', function(e) {
                e.preventDefault();
                self.handleFilterSubmit($(this));
            });

            // Load more buttons
            $(document).on('click', '.load-more-btn', function(e) {
                e.preventDefault();
                self.loadMoreContent($(this));
            });

            // Search suggestions
            $(document).on('input', '.parfume-search-input', function() {
                self.handleSearchInput($(this));
            });

            // Mobile menu toggles
            $(document).on('click', '.mobile-menu-toggle', function(e) {
                e.preventDefault();
                self.toggleMobileMenu();
            });

            // Window scroll for animations
            $(window).on('scroll', function() {
                self.handleScroll();
            });

            // Window resize for responsive adjustments
            $(window).on('resize', function() {
                self.handleResize();
            });
        },

        // Comparison functionality
        toggleComparison: function($btn) {
            var parfumeId = $btn.data('parfume-id');
            var parfumeData = {
                id: parfumeId,
                title: $btn.closest('.parfume-card, .parfume-single-wrapper').find('.parfume-title, .parfume-card-title').first().text().trim(),
                url: window.location.href,
                image: $btn.closest('.parfume-card, .parfume-single-wrapper').find('img').first().attr('src') || ''
            };

            var comparison = this.getComparison();
            var index = comparison.findIndex(function(item) {
                return item.id == parfumeId;
            });

            if (index > -1) {
                // Remove from comparison
                comparison.splice(index, 1);
                $btn.removeClass('active').find('.compare-text').text(window.parfume_catalog_ajax.strings.addToComparison || 'Добави за сравнение');
                this.showMessage('Премахнат от сравнение', 'success');
            } else {
                // Add to comparison
                var maxItems = window.parfumeComparison ? window.parfumeComparison.maxItems : 4;
                if (comparison.length >= maxItems) {
                    this.showMessage('Максимум ' + maxItems + ' парфюма могат да се сравняват едновременно', 'warning');
                    return;
                }

                comparison.push(parfumeData);
                $btn.addClass('active').find('.compare-text').text(window.parfume_catalog_ajax.strings.removeFromComparison || 'Премахни от сравнение');
                this.showMessage('Добавен за сравнение', 'success');
            }

            this.saveComparison(comparison);
            this.updateComparisonButtons();
            
            // Trigger comparison module if available
            if (window.parfumeComparison && typeof window.parfumeComparison.updateUI === 'function') {
                window.parfumeComparison.updateUI();
            }
        },

        // Get comparison data from localStorage
        getComparison: function() {
            try {
                var stored = localStorage.getItem(this.settings.comparisonKey);
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.warn('Error loading comparison data:', e);
                return [];
            }
        },

        // Save comparison data to localStorage
        saveComparison: function(comparison) {
            try {
                localStorage.setItem(this.settings.comparisonKey, JSON.stringify(comparison));
            } catch (e) {
                console.warn('Error saving comparison data:', e);
            }
        },

        // Update comparison button states
        updateComparisonButtons: function() {
            var comparison = this.getComparison();
            var comparisonIds = comparison.map(function(item) {
                return item.id.toString();
            });

            $('.parfume-compare-btn').each(function() {
                var $btn = $(this);
                var parfumeId = $btn.data('parfume-id').toString();
                
                if (comparisonIds.indexOf(parfumeId) > -1) {
                    $btn.addClass('active').find('.compare-text').text(window.parfume_catalog_ajax.strings.removeFromComparison || 'Премахни от сравнение');
                } else {
                    $btn.removeClass('active').find('.compare-text').text(window.parfume_catalog_ajax.strings.addToComparison || 'Добави за сравнение');
                }
            });
        },

        // Recently viewed functionality
        addToRecentlyViewed: function(parfumeData) {
            var recentlyViewed = this.getRecentlyViewed();
            
            // Remove if already exists
            recentlyViewed = recentlyViewed.filter(function(item) {
                return item.id != parfumeData.id;
            });
            
            // Add to beginning
            recentlyViewed.unshift(parfumeData);
            
            // Limit array size
            if (recentlyViewed.length > this.settings.recentlyViewedLimit) {
                recentlyViewed = recentlyViewed.slice(0, this.settings.recentlyViewedLimit);
            }
            
            this.saveRecentlyViewed(recentlyViewed);
        },

        // Get recently viewed data
        getRecentlyViewed: function() {
            try {
                var stored = localStorage.getItem(this.settings.storageKey);
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.warn('Error loading recently viewed data:', e);
                return [];
            }
        },

        // Save recently viewed data
        saveRecentlyViewed: function(data) {
            try {
                localStorage.setItem(this.settings.storageKey, JSON.stringify(data));
            } catch (e) {
                console.warn('Error saving recently viewed data:', e);
            }
        },

        // Load and display recently viewed
        loadRecentlyViewed: function() {
            if (!this.$recentlyViewedContainer.length) {
                return;
            }

            var recentlyViewed = this.getRecentlyViewed();
            
            if (recentlyViewed.length === 0) {
                this.$recentlyViewedContainer.closest('.parfume-recently-viewed').hide();
                return;
            }

            var html = '';
            recentlyViewed.forEach(function(parfume) {
                html += `
                    <div class="recently-viewed-item">
                        <a href="${parfume.url}">
                            ${parfume.image ? `<img src="${parfume.image}" alt="${parfume.title}" class="recently-viewed-image">` : '<div class="recently-viewed-placeholder"><span class="placeholder-icon">🌸</span></div>'}
                            <h3 class="recently-viewed-title">${parfume.title}</h3>
                        </a>
                    </div>
                `;
            });

            this.$recentlyViewedContainer.html(html);
            this.$recentlyViewedContainer.closest('.parfume-recently-viewed').show();
        },

        // Variant selection
        selectVariant: function($btn) {
            var $container = $btn.closest('.store-variants');
            var variantData = $btn.data('variant');
            
            // Update active state
            $container.find('.variant-btn').removeClass('active');
            $btn.addClass('active');
            
            // Update store price display
            var $storeItem = $btn.closest('.store-item');
            var $priceContainer = $storeItem.find('.store-price');
            
            if (variantData && variantData.price) {
                $priceContainer.find('.current-price').text(variantData.price + ' лв.');
                
                // Update affiliate link if needed
                var $storeBtn = $storeItem.find('.store-btn');
                var baseUrl = $storeBtn.attr('href');
                if (baseUrl && variantData.url_param) {
                    var separator = baseUrl.indexOf('?') > -1 ? '&' : '?';
                    $storeBtn.attr('href', baseUrl + separator + variantData.url_param);
                }
            }
            
            this.showMessage('Избран размер: ' + variantData.ml + ' мл.', 'info');
        },

        // Promo code copy functionality
        copyPromoCode: function($btn) {
            var promoCode = $btn.data('promo-code');
            var promoUrl = $btn.data('promo-url');
            
            if (!promoCode) {
                this.showMessage('Няма промо код за копиране', 'error');
                return;
            }

            // Copy to clipboard
            this.copyToClipboard(promoCode).then(function() {
                this.showMessage('Промо кодът е копиран: ' + promoCode, 'success');
                
                // Redirect to store if URL is provided
                if (promoUrl) {
                    setTimeout(function() {
                        window.open(promoUrl, '_blank');
                    }, 1000);
                }
            }.bind(this)).catch(function() {
                this.showMessage('Грешка при копиране на промо кода', 'error');
                this.fallbackCopyToClipboard(promoCode);
            }.bind(this));
        },

        // Modern clipboard API
        copyToClipboard: function(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            } else {
                return Promise.reject('Clipboard API not available');
            }
        },

        // Fallback clipboard copy
        fallbackCopyToClipboard: function(text) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showMessage('Промо кодът е копиран: ' + text, 'success');
            } catch (err) {
                this.showMessage('Моля, копирайте ръчно: ' + text, 'info');
            } finally {
                document.body.removeChild(textArea);
            }
        },

        // Store action tracking
        trackStoreClick: function($btn) {
            var storeId = $btn.closest('.store-item').data('store-id');
            var parfumeId = $('body').hasClass('single-parfumes') ? $('[data-parfume-id]').first().data('parfume-id') : null;
            
            // Track click for analytics
            if (window.gtag) {
                gtag('event', 'store_click', {
                    'store_id': storeId,
                    'parfume_id': parfumeId,
                    'event_category': 'ecommerce'
                });
            }
            
            // Add loading state
            $btn.addClass('loading').append('<span class="loading-spinner"></span>');
        },

        // Filter form handling
        handleFilterSubmit: function($form) {
            var formData = $form.serialize();
            var $submitBtn = $form.find('.filter-submit');
            var $resultsContainer = $('.parfume-grid, .parfume-archive-container');
            
            $submitBtn.addClass('loading').prop('disabled', true);
            $resultsContainer.addClass('loading');
            
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_filter_results',
                    nonce: this.settings.nonce,
                    filters: formData
                },
                success: function(response) {
                    if (response.success) {
                        $resultsContainer.html(response.data.html);
                        
                        // Update URL without page reload
                        if (history.pushState && response.data.url) {
                            history.pushState(null, '', response.data.url);
                        }
                        
                        // Reinitialize components
                        this.updateComparisonButtons();
                        this.initLazyLoading();
                    } else {
                        this.showMessage('Грешка при филтриране: ' + response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('Грешка при заявката за филтриране', 'error');
                }.bind(this),
                complete: function() {
                    $submitBtn.removeClass('loading').prop('disabled', false);
                    $resultsContainer.removeClass('loading');
                }
            });
        },

        // Load more content
        loadMoreContent: function($btn) {
            var page = $btn.data('page') || 2;
            var maxPages = $btn.data('max-pages') || 1;
            var $container = $($btn.data('container') || '.parfume-grid');
            
            if (page > maxPages) {
                $btn.hide();
                return;
            }
            
            $btn.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_load_more',
                    nonce: this.settings.nonce,
                    page: page,
                    query_vars: $btn.data('query-vars') || {}
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $container.append(response.data.html);
                        $btn.data('page', page + 1);
                        
                        if (page >= maxPages) {
                            $btn.hide();
                        }
                        
                        // Reinitialize for new content
                        this.updateComparisonButtons();
                        this.initLazyLoading();
                        this.initViewportAnimations();
                    } else {
                        this.showMessage('Няма повече съдържание за зареждане', 'info');
                        $btn.hide();
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('Грешка при зареждане на съдържание', 'error');
                }.bind(this),
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        // Search input handling with suggestions
        handleSearchInput: function($input) {
            var query = $input.val().trim();
            var $suggestionsContainer = $input.siblings('.search-suggestions');
            
            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            if (query.length < 2) {
                $suggestionsContainer.hide().empty();
                return;
            }
            
            // Debounce search
            this.searchTimeout = setTimeout(function() {
                this.fetchSearchSuggestions(query, $suggestionsContainer);
            }.bind(this), 300);
        },

        // Fetch search suggestions
        fetchSearchSuggestions: function(query, $container) {
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_search_suggestions',
                    nonce: this.settings.nonce,
                    query: query
                },
                success: function(response) {
                    if (response.success && response.data.suggestions) {
                        var html = '';
                        response.data.suggestions.forEach(function(suggestion) {
                            html += `
                                <div class="search-suggestion" data-url="${suggestion.url}">
                                    <div class="suggestion-image">
                                        ${suggestion.image ? `<img src="${suggestion.image}" alt="${suggestion.title}">` : '<span class="placeholder">🌸</span>'}
                                    </div>
                                    <div class="suggestion-content">
                                        <div class="suggestion-title">${suggestion.title}</div>
                                        <div class="suggestion-meta">${suggestion.brand || ''} ${suggestion.type || ''}</div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        $container.html(html).show();
                        
                        // Handle suggestion clicks
                        $container.find('.search-suggestion').on('click', function() {
                            window.location.href = $(this).data('url');
                        });
                    } else {
                        $container.hide().empty();
                    }
                },
                error: function() {
                    $container.hide().empty();
                }
            });
        },

        // Initialize promo code copy functionality
        initPromoCodeCopy: function() {
            // Already handled in events, but we can add visual enhancements here
            $('.promo-code-btn').each(function() {
                var $btn = $(this);
                if (!$btn.find('.copy-icon').length) {
                    $btn.append('<span class="copy-icon">📋</span>');
                }
            });
        },

        // Initialize variant selection
        initVariantSelection: function() {
            // Set first variant as active by default
            $('.store-variants').each(function() {
                var $container = $(this);
                if (!$container.find('.variant-btn.active').length) {
                    $container.find('.variant-btn').first().addClass('active');
                }
            });
        },

        // Initialize store actions
        initStoreActions: function() {
            // Add any additional store-related initialization here
            $('.store-item').each(function() {
                var $item = $(this);
                var $priceBtn = $item.find('.price-info-btn');
                
                if ($priceBtn.length && !$priceBtn.attr('title')) {
                    $priceBtn.attr('title', 'Цената се актуализира автоматично');
                }
            });
        },

        // Initialize lazy loading for images
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            var src = img.dataset.src;
                            
                            if (src) {
                                img.src = src;
                                img.classList.remove('lazy');
                                img.classList.add('loaded');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });

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

        // Initialize tooltips
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

        // Initialize viewport animations
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

        // Initialize accessibility features
        initAccessibility: function() {
            // Skip link functionality
            $('.skip-link').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $(target).focus();
            });

            // Keyboard navigation for custom elements
            $(document).on('keydown', '.parfume-compare-btn, .variant-btn, .promo-code-btn', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });

            // ARIA labels for dynamic content
            $('.parfume-compare-btn').each(function() {
                var $btn = $(this);
                if (!$btn.attr('aria-label')) {
                    $btn.attr('aria-label', 'Добави парфюма за сравнение');
                }
            });
        },

        // Mobile menu functionality
        toggleMobileMenu: function() {
            this.$body.toggleClass('mobile-menu-open');
            var $toggle = $('.mobile-menu-toggle');
            var isOpen = this.$body.hasClass('mobile-menu-open');
            
            $toggle.attr('aria-expanded', isOpen);
            $toggle.find('.menu-text').text(isOpen ? 'Затвори' : 'Меню');
        },

        // Handle scroll events
        handleScroll: function() {
            var scrollTop = $(window).scrollTop();
            
            // Sticky header behavior
            if (scrollTop > 100) {
                this.$body.addClass('scrolled');
            } else {
                this.$body.removeClass('scrolled');
            }
            
            // Progress bar for reading
            var $progressBar = $('.reading-progress');
            if ($progressBar.length) {
                var docHeight = $(document).height() - $(window).height();
                var progress = (scrollTop / docHeight) * 100;
                $progressBar.css('width', Math.min(progress, 100) + '%');
            }
        },

        // Handle resize events
        handleResize: function() {
            // Update mobile menu state if needed
            if ($(window).width() > 768 && this.$body.hasClass('mobile-menu-open')) {
                this.toggleMobileMenu();
            }
            
            // Recalculate sticky positions
            this.updateStickyPositions();
        },

        // Update sticky element positions
        updateStickyPositions: function() {
            $('.parfume-stores-column').each(function() {
                var $column = $(this);
                var windowHeight = $(window).height();
                var headerHeight = $('.site-header').outerHeight() || 0;
                var maxHeight = windowHeight - headerHeight - 40;
                
                $column.css('max-height', maxHeight + 'px');
            });
        },

        // Show user messages
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Remove existing messages
            $('.parfume-message').remove();
            
            var $message = $(`
                <div class="parfume-message parfume-message-${type}">
                    <span class="message-text">${message}</span>
                    <button class="message-close" aria-label="Затвори съобщението">&times;</button>
                </div>
            `);
            
            // Add to page
            this.$body.prepend($message);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
            
            // Close button functionality
            $message.find('.message-close').on('click', function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            });
        },

        // Utility: Debounce function
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        // Utility: Throttle function
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        parfumeCatalog.init();
    });

    // Re-initialize on AJAX content load
    $(document).on('DOMContentLoaded', function() {
        parfumeCatalog.updateComparisonButtons();
        parfumeCatalog.initLazyLoading();
    });

})(jQuery);