/**
 * Mobile stores functionality for Parfume Catalog Plugin
 * 
 * @package ParfumeCatalog
 */

(function($) {
    'use strict';

    // Global mobile stores object
    window.ParfumeMobileStores = {
        isExpanded: false,
        isHidden: false,
        settings: {
            enabled: true,
            showCloseButton: true,
            zIndex: 1000,
            bottomOffset: 0,
            animationDuration: 300
        },

        init: function() {
            this.loadSettings();
            this.checkMobileDevice();
            this.initEventHandlers();
            this.initPromoCodeFunctionality();
            this.initScrollBehavior();
            this.handleOffsetElements();
            this.initAccessibility();
        },

        /**
         * Load settings from localized data
         */
        loadSettings: function() {
            if (typeof parfume_mobile_stores !== 'undefined') {
                this.settings = $.extend(this.settings, parfume_mobile_stores);
            }
        },

        /**
         * Check if on mobile device
         */
        checkMobileDevice: function() {
            var isMobile = window.innerWidth <= 768;
            
            if (!isMobile || !this.settings.enabled) {
                return;
            }

            this.initMobilePanel();
        },

        /**
         * Initialize mobile panel
         */
        initMobilePanel: function() {
            var $storesColumn = $('.parfume-stores-column');
            
            if (!$storesColumn.length) {
                return;
            }

            // Add mobile classes and structure
            $storesColumn.addClass('mobile-stores-panel');
            
            // Apply z-index and offset settings
            $storesColumn.css({
                'z-index': this.settings.zIndex,
                'bottom': this.settings.bottomOffset + 'px'
            });

            // Wrap stores in container for mobile behavior
            var $storesList = $storesColumn.find('.store-item');
            if ($storesList.length > 1) {
                this.createExpandableInterface($storesColumn, $storesList);
            }

            this.addMobileControls($storesColumn);
        },

        /**
         * Create expandable interface for multiple stores
         */
        createExpandableInterface: function($container, $stores) {
            var $firstStore = $stores.first().addClass('first-store');
            var $otherStores = $stores.slice(1).wrapAll('<div class="mobile-stores-list collapsed"></div>');
            
            // Show only first store initially
            $otherStores.hide();
        },

        /**
         * Add mobile controls
         */
        addMobileControls: function($container) {
            var $storesList = $container.find('.store-item');
            var hasMultipleStores = $storesList.length > 1;
            
            var controlsHtml = '<div class="mobile-controls">';
            
            // Expand/collapse button (only if multiple stores)
            if (hasMultipleStores) {
                controlsHtml += '<button class="mobile-expand-btn" aria-label="Покажи всички магазини" title="Покажи всички магазини">↑</button>';
            }
            
            // Close button (if enabled in settings)
            if (this.settings.showCloseButton) {
                controlsHtml += '<button class="mobile-close-btn" aria-label="Скрий панела с магазини" title="Скрий панела с магазини">×</button>';
            }
            
            controlsHtml += '</div>';
            
            $container.prepend(controlsHtml);
            
            // Add show panel button (hidden initially)
            if (this.settings.showCloseButton) {
                $('body').append('<button class="mobile-show-panel" aria-label="Покажи панела с магазини" title="Покажи панела с магазини" style="display: none;">🛒</button>');
            }
        },

        /**
         * Initialize event handlers
         */
        initEventHandlers: function() {
            var self = this;

            // Expand/collapse stores
            $(document).on('click', '.mobile-expand-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleStoresList();
            });

            // Close panel
            $(document).on('click', '.mobile-close-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.hidePanel();
            });

            // Show panel
            $(document).on('click', '.mobile-show-panel', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.showPanel();
            });

            // Size option clicks (for multiple sizes)
            $(document).on('click', '.mobile-size-option', function(e) {
                e.preventDefault();
                var url = $(this).attr('href') || $(this).data('url');
                if (url) {
                    window.open(url, '_blank', 'noopener,noreferrer');
                }
            });

            // Store button clicks
            $(document).on('click', '.mobile-btn-store', function(e) {
                e.preventDefault();
                var url = $(this).attr('href') || $(this).data('url');
                if (url) {
                    window.open(url, '_blank', 'noopener,noreferrer');
                }
            });

            // Outside click to collapse (if expanded)
            $(document).on('click', function(e) {
                if (self.isExpanded && 
                    !$(e.target).closest('.parfume-stores-column').length &&
                    !$(e.target).hasClass('mobile-expand-btn')) {
                    self.collapseStoresList();
                }
            });

            // Window resize handler
            $(window).on('resize', function() {
                self.handleResize();
            });

            // Orientation change
            $(window).on('orientationchange', function() {
                setTimeout(function() {
                    self.handleResize();
                }, 100);
            });
        },

        /**
         * Toggle stores list expansion
         */
        toggleStoresList: function() {
            if (this.isExpanded) {
                this.collapseStoresList();
            } else {
                this.expandStoresList();
            }
        },

        /**
         * Expand stores list
         */
        expandStoresList: function() {
            var $storesList = $('.mobile-stores-list');
            var $expandBtn = $('.mobile-expand-btn');
            var $panel = $('.parfume-stores-column');
            
            $storesList.removeClass('collapsed').addClass('expanded');
            $storesList.slideDown(this.settings.animationDuration);
            
            $expandBtn.addClass('rotated').attr('aria-label', 'Скрий другите магазини');
            $panel.addClass('expanded');
            
            this.isExpanded = true;
            
            // Add overlay
            this.addOverlay();
            
            // Announce to screen readers
            this.announceToScreenReader('Показани са всички магазини');
        },

        /**
         * Collapse stores list
         */
        collapseStoresList: function() {
            var $storesList = $('.mobile-stores-list');
            var $expandBtn = $('.mobile-expand-btn');
            var $panel = $('.parfume-stores-column');
            
            $storesList.slideUp(this.settings.animationDuration, function() {
                $storesList.removeClass('expanded').addClass('collapsed');
            });
            
            $expandBtn.removeClass('rotated').attr('aria-label', 'Покажи всички магазини');
            $panel.removeClass('expanded');
            
            this.isExpanded = false;
            
            // Remove overlay
            this.removeOverlay();
            
            // Announce to screen readers
            this.announceToScreenReader('Скрити са другите магазини');
        },

        /**
         * Hide panel completely
         */
        hidePanel: function() {
            var $panel = $('.parfume-stores-column');
            var $showBtn = $('.mobile-show-panel');
            
            $panel.addClass('slide-up').addClass('hidden');
            
            setTimeout(function() {
                $panel.hide();
                $showBtn.addClass('visible').show();
            }, this.settings.animationDuration);
            
            this.isHidden = true;
            this.isExpanded = false;
            
            // Remove overlay
            this.removeOverlay();
            
            // Announce to screen readers
            this.announceToScreenReader('Панелът с магазини е скрит');
        },

        /**
         * Show panel
         */
        showPanel: function() {
            var $panel = $('.parfume-stores-column');
            var $showBtn = $('.mobile-show-panel');
            
            $showBtn.removeClass('visible').hide();
            $panel.show().removeClass('slide-up hidden').addClass('slide-down');
            
            setTimeout(function() {
                $panel.removeClass('slide-down');
            }, this.settings.animationDuration);
            
            this.isHidden = false;
            
            // Announce to screen readers
            this.announceToScreenReader('Панелът с магазини е показан');
        },

        /**
         * Add overlay for expanded state
         */
        addOverlay: function() {
            if (!$('.mobile-stores-overlay').length) {
                $('body').append('<div class="mobile-stores-overlay"></div>');
            }
            
            $('.mobile-stores-overlay').fadeIn(200);
        },

        /**
         * Remove overlay
         */
        removeOverlay: function() {
            $('.mobile-stores-overlay').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Initialize promo code functionality
         */
        initPromoCodeFunctionality: function() {
            var self = this;

            // Promo code copy and redirect
            $(document).on('click', '.mobile-btn-promo', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $btn = $(this);
                var promoCode = $btn.find('.mobile-promo-code').text().trim();
                var promoUrl = $btn.data('url') || $btn.attr('href');
                
                if (promoCode) {
                    self.copyToClipboard(promoCode, function(success) {
                        if (success) {
                            // Visual feedback
                            $btn.addClass('copy-success');
                            
                            // Show success message
                            self.showMessage('Промо кодът е копиран!', 'success');
                            
                            // Redirect after short delay
                            setTimeout(function() {
                                if (promoUrl) {
                                    window.open(promoUrl, '_blank', 'noopener,noreferrer');
                                }
                                $btn.removeClass('copy-success');
                            }, 800);
                        } else {
                            self.showMessage('Грешка при копиране на промо кода.', 'error');
                        }
                    });
                } else if (promoUrl) {
                    // No promo code, just redirect
                    window.open(promoUrl, '_blank', 'noopener,noreferrer');
                }
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text, callback) {
            // Modern approach
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    callback(true);
                }).catch(function() {
                    // Fallback to legacy method
                    callback(this.legacyCopyToClipboard(text));
                }.bind(this));
            } else {
                // Legacy approach
                callback(this.legacyCopyToClipboard(text));
            }
        },

        /**
         * Legacy clipboard copy method
         */
        legacyCopyToClipboard: function(text) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            var successful = false;
            try {
                successful = document.execCommand('copy');
            } catch (err) {
                console.error('Clipboard copy failed:', err);
            }
            
            document.body.removeChild(textArea);
            return successful;
        },

        /**
         * Initialize scroll behavior
         */
        initScrollBehavior: function() {
            var self = this;
            var lastScrollTop = 0;
            var scrollThreshold = 50;
            
            $(window).on('scroll', function() {
                var scrollTop = $(this).scrollTop();
                var scrollDiff = Math.abs(scrollTop - lastScrollTop);
                
                // Only react to significant scroll changes
                if (scrollDiff < scrollThreshold) {
                    return;
                }
                
                var $panel = $('.parfume-stores-column');
                
                if (!$panel.length || self.isHidden) {
                    return;
                }
                
                // Auto-collapse on scroll down
                if (scrollTop > lastScrollTop && self.isExpanded) {
                    self.collapseStoresList();
                }
                
                lastScrollTop = scrollTop;
            });
        },

        /**
         * Handle offset elements (like cookie bars, bottom navigation)
         */
        handleOffsetElements: function() {
            var totalOffset = this.settings.bottomOffset;
            
            // Check for common bottom elements
            var bottomElements = [
                '.cookie-notice',
                '.cookie-bar',
                '.bottom-navigation',
                '.bottom-nav',
                '.chat-widget',
                '.floating-widget'
            ];
            
            $.each(bottomElements, function(index, selector) {
                var $element = $(selector);
                if ($element.length && $element.is(':visible')) {
                    totalOffset += $element.outerHeight();
                }
            });
            
            // Apply calculated offset
            if (totalOffset > this.settings.bottomOffset) {
                $('.parfume-stores-column').css('bottom', totalOffset + 'px');
            }
        },

        /**
         * Handle window resize
         */
        handleResize: function() {
            var isMobile = window.innerWidth <= 768;
            
            if (isMobile && this.settings.enabled) {
                // Ensure mobile behavior is active
                if (!$('.parfume-stores-column').hasClass('mobile-stores-panel')) {
                    this.initMobilePanel();
                }
            } else {
                // Disable mobile behavior on desktop
                this.disableMobileMode();
            }
            
            // Recalculate offsets
            this.handleOffsetElements();
        },

        /**
         * Disable mobile mode
         */
        disableMobileMode: function() {
            var $panel = $('.parfume-stores-column');
            
            $panel.removeClass('mobile-stores-panel expanded hidden')
                  .css({
                      'position': '',
                      'bottom': '',
                      'z-index': '',
                      'display': ''
                  });
            
            $('.mobile-controls').remove();
            $('.mobile-show-panel').remove();
            $('.mobile-stores-overlay').remove();
            $('.mobile-stores-list').removeClass('collapsed expanded').show();
            
            this.isExpanded = false;
            this.isHidden = false;
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels and roles
            $('.parfume-stores-column').attr('role', 'region').attr('aria-label', 'Магазини за покупка');
            
            // Add live region for announcements
            if (!$('#mobile-stores-announcements').length) {
                $('body').append('<div id="mobile-stores-announcements" aria-live="polite" aria-atomic="true" class="screen-reader-only"></div>');
            }
            
            // Keyboard navigation
            this.initKeyboardNavigation();
        },

        /**
         * Initialize keyboard navigation
         */
        initKeyboardNavigation: function() {
            var self = this;
            
            // Tab navigation through store items
            $(document).on('keydown', '.parfume-stores-column', function(e) {
                var $focusable = $(this).find('button, a, [tabindex="0"]').filter(':visible');
                var currentIndex = $focusable.index($(document.activeElement));
                
                switch (e.which) {
                    case 9: // Tab
                        // Let default tab behavior work
                        break;
                        
                    case 27: // Escape
                        e.preventDefault();
                        if (self.isExpanded) {
                            self.collapseStoresList();
                        } else if (!self.isHidden) {
                            self.hidePanel();
                        }
                        break;
                        
                    case 13: // Enter
                    case 32: // Space
                        if ($(document.activeElement).hasClass('mobile-expand-btn')) {
                            e.preventDefault();
                            self.toggleStoresList();
                        }
                        break;
                }
            });
        },

        /**
         * Announce to screen reader
         */
        announceToScreenReader: function(message) {
            $('#mobile-stores-announcements').text(message);
        },

        /**
         * Show user message
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Create toast notification
            var $toast = $(`
                <div class="mobile-stores-toast mobile-stores-toast-${type}">
                    <span class="toast-message">${message}</span>
                </div>
            `);
            
            $('body').append($toast);
            
            // Show toast
            setTimeout(function() {
                $toast.addClass('show');
            }, 100);
            
            // Auto hide after 2 seconds
            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 2000);
        },

        /**
         * Update store data (called from scraper)
         */
        updateStoreData: function(storeId, data) {
            var $store = $(`.store-item[data-store-id="${storeId}"]`);
            
            if ($store.length && data) {
                // Update price
                if (data.price) {
                    $store.find('.mobile-current-price').text(data.price);
                }
                
                // Update old price
                if (data.old_price) {
                    $store.find('.mobile-old-price').text(data.old_price).show();
                } else {
                    $store.find('.mobile-old-price').hide();
                }
                
                // Update availability
                if (data.availability) {
                    $store.find('.mobile-availability').text(data.availability).show();
                } else {
                    $store.find('.mobile-availability').hide();
                }
                
                // Update shipping
                if (data.shipping) {
                    $store.find('.mobile-shipping').text(data.shipping).show();
                } else {
                    $store.find('.mobile-shipping').hide();
                }
                
                // Update variants
                if (data.variants && data.variants.length > 0) {
                    var $sizeOptions = $store.find('.mobile-size-options');
                    $sizeOptions.empty();
                    
                    $.each(data.variants, function(index, variant) {
                        var $option = $(`
                            <a href="${variant.url || '#'}" class="mobile-size-option ${variant.on_sale ? 'on-sale' : ''}" data-url="${variant.url || ''}">
                                <span class="mobile-size-ml">${variant.size}</span>
                                <span class="mobile-size-price">${variant.price}</span>
                            </a>
                        `);
                        
                        $sizeOptions.append($option);
                    });
                }
                
                // Show update indicator
                $store.addClass('recently-updated');
                setTimeout(function() {
                    $store.removeClass('recently-updated');
                }, 2000);
            }
        },

        /**
         * Show loading state
         */
        showLoading: function(storeId) {
            var $store = $(`.store-item[data-store-id="${storeId}"]`);
            $store.addClass('mobile-store-loading');
        },

        /**
         * Hide loading state
         */
        hideLoading: function(storeId) {
            var $store = $(`.store-item[data-store-id="${storeId}"]`);
            $store.removeClass('mobile-store-loading');
        },

        /**
         * Show error state
         */
        showError: function(storeId, message) {
            var $store = $(`.store-item[data-store-id="${storeId}"]`);
            $store.addClass('mobile-store-error');
            
            if (message) {
                this.showMessage('Грешка при обновяване: ' + message, 'error');
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on mobile or when forced
        if (window.innerWidth <= 768 || (typeof parfume_mobile_stores !== 'undefined' && parfume_mobile_stores.force_init)) {
            ParfumeMobileStores.init();
        }
    });

    // Expose to global scope
    window.parfumeMobileStores = ParfumeMobileStores;

})(jQuery);