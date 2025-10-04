/**
 * Mobile Fixed Panel JavaScript
 * 
 * Handles:
 * - Mobile viewport detection
 * - Fixed positioning of first store
 * - Slide up/down animation
 * - Close/reopen functionality
 * 
 * @package ParfumeReviews
 */

(function($) {
    'use strict';

    /**
     * Mobile Panel Handler
     */
    const MobilePanel = {

        /**
         * Settings from WordPress
         */
        settings: {
            enabled: true,
            zIndex: 9999,
            offset: 0,
            showCloseButton: true,
            breakpoint: 768
        },

        /**
         * State
         */
        state: {
            isVisible: true,
            isExpanded: false,
            isClosed: false
        },

        /**
         * Elements
         */
        $panel: null,
        $reopenButton: null,

        /**
         * Initialize
         */
        init: function(settings) {
            // Merge settings
            if (settings) {
                $.extend(this.settings, settings);
            }

            // Check if mobile panel should be shown
            if (!this.isMobileViewport()) {
                return;
            }

            // Build panel
            this.buildPanel();
            
            // Bind events
            this.bindEvents();
            
            // Apply settings
            this.applySettings();
            
            // Show panel
            this.show();
        },

        /**
         * Check if mobile viewport
         */
        isMobileViewport: function() {
            return $(window).width() <= this.settings.breakpoint;
        },

        /**
         * Build mobile panel HTML
         */
        buildPanel: function() {
            const $storesWrapper = $('.stores-wrapper');
            
            if (!$storesWrapper.length) {
                return;
            }

            const $storesList = $storesWrapper.find('.store-item');
            
            if (!$storesList.length) {
                return;
            }

            // Clone first store
            const $firstStore = $storesList.first().clone();
            
            // Clone other stores
            const $otherStores = $storesList.slice(1).clone();
            const storesCount = $storesList.length;

            // Build panel HTML
            const panelHTML = `
                <div class="mobile-stores-panel panel-hidden">
                    <div class="mobile-panel-header">
                        <div class="mobile-panel-toggle">
                            <svg class="toggle-arrow" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                            </svg>
                            <span>Магазини</span>
                            ${storesCount > 1 ? '<span class="stores-counter">' + storesCount + '</span>' : ''}
                        </div>
                        ${this.settings.showCloseButton ? `
                            <button type="button" class="mobile-panel-close" aria-label="Затвори">
                                <svg class="close-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                    <div class="mobile-panel-content">
                        <div class="mobile-first-store"></div>
                        ${storesCount > 1 ? '<div class="mobile-other-stores"></div>' : ''}
                    </div>
                </div>
            `;

            // Build reopen button
            const reopenHTML = `
                <button type="button" class="mobile-reopen-button" aria-label="Покажи магазини">
                    <svg class="reopen-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                </button>
            `;

            // Append to body
            $('body').append(panelHTML);
            $('body').append(reopenHTML);

            // Cache elements
            this.$panel = $('.mobile-stores-panel');
            this.$reopenButton = $('.mobile-reopen-button');

            // Insert stores
            this.$panel.find('.mobile-first-store').append($firstStore);
            
            if ($otherStores.length > 0) {
                this.$panel.find('.mobile-other-stores').append($otherStores);
            }

            // Add body class
            $('body').addClass('has-mobile-panel');

            // Hide original stores on mobile
            $storesWrapper.hide();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            if (!this.$panel) {
                return;
            }

            // Toggle expand/collapse
            this.$panel.find('.mobile-panel-header').on('click', (e) => {
                // Don't toggle if clicking close button
                if ($(e.target).closest('.mobile-panel-close').length) {
                    return;
                }
                this.toggle();
            });

            // Close button
            this.$panel.find('.mobile-panel-close').on('click', (e) => {
                e.stopPropagation();
                this.close();
            });

            // Reopen button
            this.$reopenButton.on('click', () => {
                this.reopen();
            });

            // Handle window resize
            $(window).on('resize', this.handleResize.bind(this));

            // Prevent body scroll when panel is expanded
            this.$panel.find('.mobile-panel-content').on('touchmove', (e) => {
                if (this.state.isExpanded) {
                    e.stopPropagation();
                }
            });
        },

        /**
         * Apply settings from WordPress
         */
        applySettings: function() {
            if (!this.$panel) {
                return;
            }

            // Apply z-index
            this.$panel.css('z-index', this.settings.zIndex);
            this.$reopenButton.css('z-index', this.settings.zIndex - 1);

            // Apply offset
            if (this.settings.offset > 0) {
                this.$panel.css('bottom', this.settings.offset + 'px');
            }

            // Hide close button if disabled
            if (!this.settings.showCloseButton) {
                this.$panel.find('.mobile-panel-close').hide();
            }
        },

        /**
         * Show panel
         */
        show: function() {
            if (!this.$panel) {
                return;
            }

            this.$panel.css('display', 'block');
            this.state.isVisible = true;
            
            // Start collapsed
            setTimeout(() => {
                this.$panel.addClass('panel-hidden');
            }, 100);
        },

        /**
         * Toggle expand/collapse
         */
        toggle: function() {
            if (this.state.isExpanded) {
                this.collapse();
            } else {
                this.expand();
            }
        },

        /**
         * Expand panel
         */
        expand: function() {
            if (!this.$panel) {
                return;
            }

            this.$panel.removeClass('panel-hidden');
            this.state.isExpanded = true;

            // Track event
            this.trackEvent('mobile_panel_expanded');
        },

        /**
         * Collapse panel
         */
        collapse: function() {
            if (!this.$panel) {
                return;
            }

            this.$panel.addClass('panel-hidden');
            this.state.isExpanded = false;

            // Track event
            this.trackEvent('mobile_panel_collapsed');
        },

        /**
         * Close panel completely
         */
        close: function() {
            if (!this.$panel) {
                return;
            }

            this.$panel.addClass('panel-closed');
            this.state.isClosed = true;
            this.state.isVisible = false;

            // Show reopen button after animation
            setTimeout(() => {
                this.$reopenButton.addClass('show');
            }, 300);

            // Track event
            this.trackEvent('mobile_panel_closed');
        },

        /**
         * Reopen panel
         */
        reopen: function() {
            if (!this.$panel) {
                return;
            }

            this.$panel.removeClass('panel-closed panel-hidden');
            this.$reopenButton.removeClass('show');
            
            this.state.isClosed = false;
            this.state.isVisible = true;
            this.state.isExpanded = true;

            // Track event
            this.trackEvent('mobile_panel_reopened');
        },

        /**
         * Handle window resize
         */
        handleResize: function() {
            // Hide panel on desktop
            if (!this.isMobileViewport()) {
                if (this.$panel) {
                    this.$panel.hide();
                    this.$reopenButton.hide();
                }
                $('.stores-wrapper').show();
            } else {
                // Show panel on mobile
                if (this.$panel && !this.state.isClosed) {
                    this.$panel.show();
                }
                $('.stores-wrapper').hide();
            }
        },

        /**
         * Track analytics event
         */
        trackEvent: function(eventName, data) {
            // Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, data);
            }

            // Google Tag Manager
            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    'event': eventName,
                    'eventData': data
                });
            }

            // Console log for debugging
            if (window.location.hostname === 'localhost' || window.location.hostname.includes('local')) {
                console.log('Mobile panel event:', eventName, data);
            }
        }

    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Get settings from localized script (if available)
        const settings = typeof parfumeMobileSettings !== 'undefined' ? parfumeMobileSettings : {};
        
        MobilePanel.init(settings);
    });

    /**
     * Expose to global scope for external access
     */
    window.ParfumeMobilePanel = MobilePanel;

})(jQuery);