/**
 * Column 2 JavaScript - Frontend functionality for stores and mobile panel
 * assets/js/column2.js
 * 
 * Handles:
 * - Mobile panel toggle
 * - Promo code copying
 * - Store data refresh
 * - Price tracking
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        initColumn2();
    });
    
    /**
     * Initialize Column 2 functionality
     */
    function initColumn2() {
        initMobilePanel();
        initPromoCodeCopy();
        initStoreRefresh();
        initPriceTracking();
        initAccessibility();
        
        // Initialize mobile settings if available
        if (typeof parfumeColumn2 !== 'undefined' && parfumeColumn2.mobile_settings) {
            applyMobileSettings(parfumeColumn2.mobile_settings);
        }
        
        console.log('Column 2 initialized successfully');
    }
    
    /**
     * Initialize mobile panel functionality
     */
    function initMobilePanel() {
        // Only on mobile devices
        if (window.innerWidth <= 768) {
            createMobilePanel();
            initMobilePanelEvents();
        }
        
        // Listen for window resize
        $(window).on('resize', debounce(function() {
            if (window.innerWidth <= 768) {
                if (!$('.parfume-stores-mobile-panel').length) {
                    createMobilePanel();
                    initMobilePanelEvents();
                }
            } else {
                removeMobilePanel();
            }
        }, 250));
    }
    
    /**
     * Create mobile panel from Column 2 content
     */
    function createMobilePanel() {
        const $column2 = $('.parfume-column-2');
        if (!$column2.length) return;
        
        // Create mobile panel structure
        const $mobilePanel = $('<div>', {
            class: 'parfume-stores-mobile-panel',
            html: `
                <div class="mobile-panel-header">
                    <h3 class="mobile-panel-title">${getLocalizedString('stores_title', 'Магазини')}</h3>
                    <button class="mobile-panel-close" aria-label="${getLocalizedString('close', 'Затвори')}">&times;</button>
                </div>
                <div class="mobile-panel-content"></div>
            `
        });
        
        // Create toggle button
        const $toggleButton = $('<button>', {
            class: 'parfume-stores-mobile-toggle',
            'aria-label': getLocalizedString('show_stores', 'Покажи магазини'),
            html: '🛒'
        });
        
        // Clone column 2 content to mobile panel
        const $clonedContent = $column2.clone();
        $clonedContent.find('.parfume-stores-container').removeClass('parfume-stores-container');
        $mobilePanel.find('.mobile-panel-content').html($clonedContent.html());
        
        // Add to DOM
        $('body').append($mobilePanel);
        $('body').append($toggleButton);
        
        // Hide original column 2 on mobile
        $column2.hide();
    }
    
    /**
     * Initialize mobile panel events
     */
    function initMobilePanelEvents() {
        const $panel = $('.parfume-stores-mobile-panel');
        const $toggle = $('.parfume-stores-mobile-toggle');
        const $close = $('.mobile-panel-close');
        
        // Toggle panel
        $toggle.on('click', function() {
            $panel.toggleClass('open');
            
            if ($panel.hasClass('open')) {
                $('body').addClass('mobile-panel-open');
                $(this).attr('aria-label', getLocalizedString('hide_stores', 'Скрий магазини'));
                
                // Accessibility: focus management
                $close.focus();
            } else {
                $('body').removeClass('mobile-panel-open');
                $(this).attr('aria-label', getLocalizedString('show_stores', 'Покажи магазини'));
                
                // Return focus to toggle button
                $(this).focus();
            }
        });
        
        // Close panel
        $close.on('click', function() {
            $panel.removeClass('open');
            $('body').removeClass('mobile-panel-open');
            $toggle.attr('aria-label', getLocalizedString('show_stores', 'Покажи магазини'));
            $toggle.focus();
        });
        
        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $panel.hasClass('open')) {
                $close.click();
            }
        });
        
        // Close on backdrop click
        $panel.on('click', function(e) {
            if (e.target === this) {
                $close.click();
            }
        });
    }
    
    /**
     * Remove mobile panel when switching to desktop
     */
    function removeMobilePanel() {
        $('.parfume-stores-mobile-panel').remove();
        $('.parfume-stores-mobile-toggle').remove();
        $('.parfume-column-2').show();
        $('body').removeClass('mobile-panel-open');
    }
    
    /**
     * Initialize promo code copying functionality
     */
    function initPromoCodeCopy() {
        $(document).on('click', '.parfume-promo-copy', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $promoCode = $button.siblings('.parfume-promo-value');
            const code = $promoCode.text().trim();
            
            if (!code) return;
            
            // Copy to clipboard
            copyToClipboard(code).then(function() {
                showCopySuccess($button);
            }).catch(function() {
                showCopyError($button);
            });
        });
    }
    
    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        return new Promise(function(resolve, reject) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                // Modern clipboard API
                navigator.clipboard.writeText(text).then(resolve).catch(reject);
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    
                    if (successful) {
                        resolve();
                    } else {
                        reject(new Error('Copy command failed'));
                    }
                } catch (err) {
                    document.body.removeChild(textarea);
                    reject(err);
                }
            }
        });
    }
    
    /**
     * Show copy success feedback
     */
    function showCopySuccess($button) {
        const originalText = $button.text();
        $button.text(getLocalizedString('copied', 'Копирано!'));
        $button.addClass('success');
        
        setTimeout(function() {
            $button.text(originalText);
            $button.removeClass('success');
        }, 2000);
    }
    
    /**
     * Show copy error feedback
     */
    function showCopyError($button) {
        const originalText = $button.text();
        $button.text(getLocalizedString('copy_failed', 'Грешка!'));
        $button.addClass('error');
        
        setTimeout(function() {
            $button.text(originalText);
            $button.removeClass('error');
        }, 2000);
    }
    
    /**
     * Initialize store refresh functionality
     */
    function initStoreRefresh() {
        $(document).on('click', '.parfume-store-refresh', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $storeItem = $button.closest('.parfume-store-item');
            const storeId = $storeItem.data('store-id');
            const postId = getPostId();
            
            if (!storeId || !postId) return;
            
            refreshStoreData(storeId, postId, $storeItem);
        });
    }
    
    /**
     * Refresh store data via AJAX
     */
    function refreshStoreData(storeId, postId, $storeItem) {
        if (!parfumeColumn2 || !parfumeColumn2.ajax_url) {
            console.error('Column 2 AJAX configuration missing');
            return;
        }
        
        $storeItem.addClass('loading');
        
        $.ajax({
            url: parfumeColumn2.ajax_url,
            type: 'POST',
            data: {
                action: 'parfume_refresh_store_data',
                nonce: parfumeColumn2.nonce,
                store_id: storeId,
                post_id: postId
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateStoreDisplay($storeItem, response.data);
                } else {
                    console.error('Store refresh failed:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Store refresh AJAX error:', error);
            },
            complete: function() {
                $storeItem.removeClass('loading');
            }
        });
    }
    
    /**
     * Update store display with new data
     */
    function updateStoreDisplay($storeItem, data) {
        // Update availability
        if (data.availability) {
            const $availability = $storeItem.find('.parfume-store-availability');
            $availability.text(data.availability);
            $availability.toggleClass('out-of-stock', data.availability_status === 'out_of_stock');
        }
        
        // Update prices
        if (data.prices && Array.isArray(data.prices)) {
            const $pricesContainer = $storeItem.find('.parfume-store-prices');
            $pricesContainer.empty();
            
            data.prices.forEach(function(price) {
                const $priceRow = $('<div>', {
                    class: 'parfume-price-row',
                    html: `
                        <span class="parfume-size-info">${price.size}</span>
                        <div class="parfume-price-info">
                            ${price.old_price ? `<span class="parfume-old-price">${price.old_price}</span>` : ''}
                            <span class="parfume-current-price">${price.current_price}</span>
                            ${price.discount ? `<span class="parfume-discount">-${price.discount}%</span>` : ''}
                        </div>
                    `
                });
                $pricesContainer.append($priceRow);
            });
        }
        
        // Update timestamp
        const now = new Date().toLocaleString('bg-BG');
        $storeItem.attr('data-last-updated', now);
    }
    
    /**
     * Initialize price tracking and change detection
     */
    function initPriceTracking() {
        // Highlight price changes
        $('.parfume-store-item').each(function() {
            const $item = $(this);
            const lastUpdate = $item.attr('data-last-updated');
            
            if (lastUpdate) {
                const updateTime = new Date(lastUpdate);
                const now = new Date();
                const hoursSinceUpdate = (now - updateTime) / (1000 * 60 * 60);
                
                // Highlight if updated in last 24 hours
                if (hoursSinceUpdate < 24) {
                    $item.addClass('recently-updated');
                }
            }
        });
    }
    
    /**
     * Apply mobile settings from WordPress admin
     */
    function applyMobileSettings(settings) {
        if (!settings || typeof settings !== 'object') return;
        
        // Apply z-index
        if (settings.z_index) {
            $('.parfume-stores-mobile-panel').css('z-index', settings.z_index);
            $('.parfume-stores-mobile-toggle').css('z-index', settings.z_index - 1);
        }
        
        // Apply custom animations
        if (settings.animation_duration) {
            const duration = settings.animation_duration + 'ms';
            $('.parfume-stores-mobile-panel').css('transition-duration', duration);
        }
        
        // Apply panel height
        if (settings.panel_height) {
            $('.parfume-stores-mobile-panel').css('max-height', settings.panel_height);
        }
    }
    
    /**
     * Initialize accessibility features
     */
    function initAccessibility() {
        // Add ARIA labels
        $('.parfume-store-button').each(function() {
            const $button = $(this);
            const storeName = $button.closest('.parfume-store-item').find('.parfume-store-name').text();
            
            if (storeName && !$button.attr('aria-label')) {
                $button.attr('aria-label', `${$button.text()} - ${storeName}`);
            }
        });
        
        // Keyboard navigation for mobile panel
        $(document).on('keydown', '.parfume-stores-mobile-panel', function(e) {
            if (e.key === 'Tab') {
                // Trap focus within panel when open
                const $panel = $(this);
                if ($panel.hasClass('open')) {
                    const $focusable = $panel.find('button, a, input, [tabindex]:not([tabindex="-1"])');
                    const $first = $focusable.first();
                    const $last = $focusable.last();
                    
                    if (e.shiftKey && document.activeElement === $first[0]) {
                        e.preventDefault();
                        $last.focus();
                    } else if (!e.shiftKey && document.activeElement === $last[0]) {
                        e.preventDefault();
                        $first.focus();
                    }
                }
            }
        });
    }
    
    /**
     * Get current post ID
     */
    function getPostId() {
        // Try various methods to get post ID
        if (typeof parfumeColumn2 !== 'undefined' && parfumeColumn2.post_id) {
            return parfumeColumn2.post_id;
        }
        
        // From body class
        const bodyClasses = document.body.className.split(' ');
        for (let i = 0; i < bodyClasses.length; i++) {
            const match = bodyClasses[i].match(/postid-(\d+)/);
            if (match) {
                return match[1];
            }
        }
        
        // From data attribute
        const postId = $('body').attr('data-post-id') || $('.parfume-stores-container').attr('data-post-id');
        if (postId) {
            return postId;
        }
        
        return null;
    }
    
    /**
     * Get localized string
     */
    function getLocalizedString(key, fallback) {
        if (typeof parfumeColumn2 !== 'undefined' && 
            parfumeColumn2.strings && 
            parfumeColumn2.strings[key]) {
            return parfumeColumn2.strings[key];
        }
        return fallback;
    }
    
    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Check if device is mobile
     */
    function isMobile() {
        return window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    /**
     * Animate element
     */
    function animateElement($element, animation) {
        $element.addClass('animated ' + animation);
        setTimeout(function() {
            $element.removeClass('animated ' + animation);
        }, 1000);
    }
    
    // Public API for external use
    window.ParfumeColumn2 = {
        refreshStore: refreshStoreData,
        copyToClipboard: copyToClipboard,
        isMobile: isMobile,
        getPostId: getPostId
    };
    
})(jQuery);