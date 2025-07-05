/**
 * UI Interactions & Effects for Parfume Reviews Plugin
 * 
 * Handles:
 * - Smooth animations and transitions
 * - Interactive elements (tabs, dropdowns, modals)
 * - Loading states and feedback
 * - Tooltip systems
 * - Image lazy loading
 * - Smooth scrolling and navigation
 * - Advanced UI components
 * 
 * @package ParfumeReviews
 * @version 1.0.0
 */

(function($) {
    'use strict';

    const PerfumeUI = {
        
        // Configuration
        config: {
            animationDuration: 300,
            scrollDuration: 800,
            lazyLoadOffset: 100,
            tooltipDelay: 300,
            debounceDelay: 250
        },

        // Initialize all UI components
        init: function() {
            this.initTabs();
            this.initDropdowns();
            this.initModals();
            this.initTooltips();
            this.initLazyLoading();
            this.initSmoothScrolling();
            this.initBackToTop();
            this.initLoadingStates();
            this.initCardAnimations();
            this.initRatingStars();
            this.initImageZoom();
            this.initAccordions();
            this.initProgressBars();
            this.initNotifications();
            this.initInfiniteScroll();
            this.bindEvents();
        },

        // Tab System
        initTabs: function() {
            $('.parfume-tabs').each(function() {
                const $tabContainer = $(this);
                const $tabNav = $tabContainer.find('.tabs-nav');
                const $tabPanels = $tabContainer.find('.tab-panel');

                // Set first tab as active
                $tabNav.find('a:first').addClass('active');
                $tabPanels.hide().first().show();

                // Tab click handler
                $tabNav.on('click', 'a', function(e) {
                    e.preventDefault();
                    
                    const $this = $(this);
                    const target = $this.attr('href');
                    
                    // Update active states
                    $tabNav.find('a').removeClass('active');
                    $this.addClass('active');
                    
                    // Show target panel with animation
                    $tabPanels.fadeOut(200, function() {
                        $(target).fadeIn(PerfumeUI.config.animationDuration);
                    });
                });
            });
        },

        // Dropdown System
        initDropdowns: function() {
            $('.dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $this = $(this);
                const $dropdown = $this.next('.dropdown-menu');
                const $parent = $this.closest('.dropdown');
                
                // Close other dropdowns
                $('.dropdown').not($parent).removeClass('open');
                $('.dropdown-menu').not($dropdown).slideUp(200);
                
                // Toggle current dropdown
                $parent.toggleClass('open');
                $dropdown.slideToggle(PerfumeUI.config.animationDuration);
            });

            // Close dropdowns on outside click
            $(document).on('click', function() {
                $('.dropdown').removeClass('open');
                $('.dropdown-menu').slideUp(200);
            });
        },

        // Modal System
        initModals: function() {
            // Modal trigger
            $('[data-modal]').on('click', function(e) {
                e.preventDefault();
                const modalId = $(this).data('modal');
                PerfumeUI.openModal(modalId);
            });

            // Close modal handlers
            $(document).on('click', '.modal-close, .modal-overlay', function(e) {
                if (e.target === this) {
                    PerfumeUI.closeModal();
                }
            });

            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    PerfumeUI.closeModal();
                }
            });
        },

        // Open modal with animation
        openModal: function(modalId) {
            const $modal = $('#' + modalId);
            if ($modal.length) {
                $modal.addClass('active').fadeIn(this.config.animationDuration);
                $('body').addClass('modal-open');
                
                // Animate modal content
                setTimeout(() => {
                    $modal.find('.modal-content').addClass('show');
                }, 50);
            }
        },

        // Close modal with animation
        closeModal: function() {
            const $activeModal = $('.modal.active');
            if ($activeModal.length) {
                $activeModal.find('.modal-content').removeClass('show');
                
                setTimeout(() => {
                    $activeModal.removeClass('active').fadeOut(200);
                    $('body').removeClass('modal-open');
                }, 200);
            }
        },

        // Tooltip System
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const tooltipText = $element.data('tooltip');
                const position = $element.data('tooltip-position') || 'top';
                
                $element.on('mouseenter', function() {
                    PerfumeUI.showTooltip($element, tooltipText, position);
                });
                
                $element.on('mouseleave', function() {
                    PerfumeUI.hideTooltip();
                });
            });
        },

        // Show tooltip
        showTooltip: function($element, text, position) {
            const $tooltip = $('<div class="parfume-tooltip">' + text + '</div>');
            $('body').append($tooltip);
            
            const elementPos = $element.offset();
            const elementWidth = $element.outerWidth();
            const elementHeight = $element.outerHeight();
            const tooltipWidth = $tooltip.outerWidth();
            const tooltipHeight = $tooltip.outerHeight();
            
            let top, left;
            
            switch (position) {
                case 'bottom':
                    top = elementPos.top + elementHeight + 10;
                    left = elementPos.left + (elementWidth / 2) - (tooltipWidth / 2);
                    break;
                case 'left':
                    top = elementPos.top + (elementHeight / 2) - (tooltipHeight / 2);
                    left = elementPos.left - tooltipWidth - 10;
                    break;
                case 'right':
                    top = elementPos.top + (elementHeight / 2) - (tooltipHeight / 2);
                    left = elementPos.left + elementWidth + 10;
                    break;
                default: // top
                    top = elementPos.top - tooltipHeight - 10;
                    left = elementPos.left + (elementWidth / 2) - (tooltipWidth / 2);
            }
            
            $tooltip.css({ top: top, left: left }).fadeIn(200);
        },

        // Hide tooltip
        hideTooltip: function() {
            $('.parfume-tooltip').remove();
        },

        // Lazy Loading
        initLazyLoading: function() {
            const images = document.querySelectorAll('img[data-src]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            img.classList.add('loaded');
                            imageObserver.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: this.config.lazyLoadOffset + 'px'
                });
                
                images.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for older browsers
                images.forEach(img => {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    img.classList.add('loaded');
                });
            }
        },

        // Smooth Scrolling
        initSmoothScrolling: function() {
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, PerfumeUI.config.scrollDuration);
                }
            });
        },

        // Back to Top Button
        initBackToTop: function() {
            const $backToTop = $('<button class="back-to-top" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>');
            $('body').append($backToTop);
            
            $(window).on('scroll', this.debounce(function() {
                if ($(window).scrollTop() > 500) {
                    $backToTop.addClass('visible');
                } else {
                    $backToTop.removeClass('visible');
                }
            }, this.config.debounceDelay));
            
            $backToTop.on('click', function() {
                $('html, body').animate({ scrollTop: 0 }, PerfumeUI.config.scrollDuration);
            });
        },

        // Loading States
        initLoadingStates: function() {
            // Add loading class to buttons on form submit
            $('form').on('submit', function() {
                $(this).find('button[type="submit"]').addClass('loading');
            });
            
            // AJAX loading indicators
            $(document).ajaxStart(function() {
                $('.ajax-loader').addClass('active');
            }).ajaxStop(function() {
                $('.ajax-loader').removeClass('active');
                $('button.loading').removeClass('loading');
            });
        },

        // Card Animations
        initCardAnimations: function() {
            // Animate cards on scroll
            const cards = document.querySelectorAll('.parfume-card, .brand-item, .note-item');
            
            if ('IntersectionObserver' in window) {
                const cardObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                            cardObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '50px'
                });
                
                cards.forEach(card => {
                    card.classList.add('animate-ready');
                    cardObserver.observe(card);
                });
            }
        },

        // Interactive Rating Stars
        initRatingStars: function() {
            $('.interactive-rating').each(function() {
                const $rating = $(this);
                const $stars = $rating.find('.star');
                const $input = $rating.find('input[type="hidden"]');
                
                $stars.on('mouseenter', function() {
                    const index = $(this).index();
                    $stars.removeClass('hover').slice(0, index + 1).addClass('hover');
                });
                
                $rating.on('mouseleave', function() {
                    $stars.removeClass('hover');
                });
                
                $stars.on('click', function() {
                    const index = $(this).index();
                    const rating = index + 1;
                    
                    $stars.removeClass('active').slice(0, index + 1).addClass('active');
                    $input.val(rating);
                    
                    // Trigger change event
                    $input.trigger('change');
                });
            });
        },

        // Image Zoom
        initImageZoom: function() {
            $('.zoomable-image').on('click', function() {
                const $img = $(this);
                const src = $img.attr('src') || $img.data('zoom-src');
                
                const $overlay = $('<div class="image-zoom-overlay"><img src="' + src + '" alt="Zoomed image"></div>');
                $('body').append($overlay);
                
                $overlay.fadeIn(300);
                
                $overlay.on('click', function() {
                    $(this).fadeOut(300, function() {
                        $(this).remove();
                    });
                });
            });
        },

        // Accordion System
        initAccordions: function() {
            $('.accordion-item').each(function() {
                const $item = $(this);
                const $header = $item.find('.accordion-header');
                const $content = $item.find('.accordion-content');
                
                $header.on('click', function() {
                    const isActive = $item.hasClass('active');
                    
                    // Close other accordion items (if single mode)
                    if ($item.closest('.accordion').hasClass('single-mode')) {
                        $item.siblings().removeClass('active').find('.accordion-content').slideUp(300);
                    }
                    
                    // Toggle current item
                    if (isActive) {
                        $item.removeClass('active');
                        $content.slideUp(300);
                    } else {
                        $item.addClass('active');
                        $content.slideDown(300);
                    }
                });
            });
        },

        // Progress Bars
        initProgressBars: function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            
            if ('IntersectionObserver' in window) {
                const progressObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const progressBar = entry.target;
                            const percentage = progressBar.dataset.percentage || 0;
                            const fill = progressBar.querySelector('.progress-fill');
                            
                            fill.style.width = percentage + '%';
                            progressObserver.unobserve(progressBar);
                        }
                    });
                });
                
                progressBars.forEach(bar => progressObserver.observe(bar));
            }
        },

        // Notification System
        initNotifications: function() {
            // Auto-hide notifications
            $('.notification').each(function() {
                const $notification = $(this);
                const autoHide = $notification.data('auto-hide');
                
                if (autoHide !== false) {
                    setTimeout(() => {
                        PerfumeUI.hideNotification($notification);
                    }, autoHide || 5000);
                }
            });
            
            // Close notification on click
            $(document).on('click', '.notification-close', function() {
                PerfumeUI.hideNotification($(this).closest('.notification'));
            });
        },

        // Show notification
        showNotification: function(message, type = 'info', autoHide = true) {
            const $notification = $(`
                <div class="notification notification-${type}">
                    <div class="notification-content">
                        <span class="notification-message">${message}</span>
                        <button class="notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            $('.notification-container').append($notification);
            
            // Animate in
            setTimeout(() => $notification.addClass('show'), 100);
            
            // Auto hide
            if (autoHide) {
                setTimeout(() => {
                    this.hideNotification($notification);
                }, 5000);
            }
            
            return $notification;
        },

        // Hide notification
        hideNotification: function($notification) {
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        },

        // Infinite Scroll
        initInfiniteScroll: function() {
            const $container = $('.infinite-scroll-container');
            const $trigger = $('.load-more-trigger');
            
            if ($container.length && $trigger.length) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !$trigger.hasClass('loading')) {
                            this.loadMoreContent();
                        }
                    });
                });
                
                observer.observe($trigger[0]);
            }
        },

        // Load more content
        loadMoreContent: function() {
            const $trigger = $('.load-more-trigger');
            const page = parseInt($trigger.data('page')) || 1;
            const nextPage = page + 1;
            
            $trigger.addClass('loading').data('page', nextPage);
            
            // This would typically make an AJAX call
            // For now, we'll just simulate loading
            setTimeout(() => {
                $trigger.removeClass('loading');
                // Add new content logic here
            }, 1000);
        },

        // Utility: Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Utility: Throttle function
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        // Bind additional events
        bindEvents: function() {
            // Window resize handler
            $(window).on('resize', this.debounce(function() {
                // Recalculate positions, update layouts, etc.
                PerfumeUI.handleResize();
            }, 250));
            
            // Form validation
            $('form[data-validate]').on('submit', function(e) {
                if (!PerfumeUI.validateForm($(this))) {
                    e.preventDefault();
                }
            });
            
            // Copy to clipboard
            $('.copy-to-clipboard').on('click', function() {
                const text = $(this).data('copy') || $(this).prev('input').val();
                PerfumeUI.copyToClipboard(text);
            });
        },

        // Handle window resize
        handleResize: function() {
            // Update masonry layouts
            $('.masonry-grid').trigger('resize');
            
            // Recalculate sticky elements
            $('.sticky-element').trigger('sticky_kit:recalc');
            
            // Update any positioned elements
            this.repositionElements();
        },

        // Reposition floating elements
        repositionElements: function() {
            $('.floating-element').each(function() {
                // Recalculate positions for floating elements
                const $element = $(this);
                // Update position logic here
            });
        },

        // Form validation
        validateForm: function($form) {
            let isValid = true;
            
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            return isValid;
        },

        // Copy to clipboard
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showNotification('Copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showNotification('Copied to clipboard!', 'success');
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        PerfumeUI.init();
    });

    // Make PerfumeUI globally available
    window.PerfumeUI = PerfumeUI;

})(jQuery);

// CSS Classes for animations (should be in CSS file)
const uiStyles = `
/* Animation classes */
.animate-ready {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Loading states */
.loading {
    position: relative;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #333;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Tooltip styles */
.parfume-tooltip {
    position: absolute;
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 9999;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* Modal styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 90%;
    max-height: 90%;
    overflow: auto;
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.modal-content.show {
    transform: scale(1);
}

/* Back to top button */
.back-to-top {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    background: #333;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
}

/* Notification styles */
.notification {
    padding: 12px 20px;
    margin-bottom: 10px;
    border-radius: 4px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification-info { background: #d1ecf1; color: #0c5460; }
.notification-success { background: #d4edda; color: #155724; }
.notification-warning { background: #fff3cd; color: #856404; }
.notification-error { background: #f8d7da; color: #721c24; }
`;

// Inject styles if not already present
if (!document.getElementById('parfume-ui-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'parfume-ui-styles';
    styleSheet.textContent = uiStyles;
    document.head.appendChild(styleSheet);
}