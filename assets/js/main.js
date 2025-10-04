/**
 * Main JavaScript
 * 
 * Core JavaScript functionality for Parfume Reviews plugin
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Parfume Reviews Main Object
     */
    const ParfumeReviews = {
        
        /**
         * Initialize
         */
        init: function() {
            this.initGallery();
            this.initQuickActions();
            this.initFilters();
            this.initLoadMore();
            this.initNotifications();
        },
        
        /**
         * Initialize Image Gallery
         */
        initGallery: function() {
            const $gallery = $('.parfume-gallery');
            
            if ($gallery.length === 0) return;
            
            // Thumbnail click
            $gallery.on('click', '.gallery-thumbnails .thumbnail', function() {
                const $thumb = $(this);
                const $img = $thumb.find('img');
                const imgSrc = $img.attr('src');
                const fullSrc = imgSrc.replace('-150x150', '');
                
                // Update main image
                $gallery.find('.main-image img').attr('src', fullSrc);
                
                // Update active state
                $gallery.find('.thumbnail').removeClass('active');
                $thumb.addClass('active');
            });
            
            // Set first thumbnail as active
            $gallery.find('.gallery-thumbnails .thumbnail').first().addClass('active');
        },
        
        /**
         * Initialize Quick Actions
         */
        initQuickActions: function() {
            const self = this;
            
            // Comparison toggle
            $(document).on('click', '.comparison-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const postId = $btn.data('post-id');
                
                if (!postId) return;
                
                // Add to comparison (handled by comparison.js)
                $(document).trigger('parfume:addToComparison', [postId]);
            });
            
            // Wishlist toggle
            $(document).on('click', '.wishlist-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const postId = $btn.data('post-id');
                
                if (!postId) return;
                
                self.toggleWishlist(postId, $btn);
            });
        },
        
        /**
         * Toggle Wishlist
         */
        toggleWishlist: function(postId, $btn) {
            // Get current wishlist from localStorage
            let wishlist = this.getWishlist();
            const index = wishlist.indexOf(postId);
            
            if (index > -1) {
                // Remove from wishlist
                wishlist.splice(index, 1);
                $btn.removeClass('active');
                this.showNotification('Премахнато от любими', 'success');
            } else {
                // Add to wishlist
                wishlist.push(postId);
                $btn.addClass('active');
                this.showNotification('Добавено в любими', 'success');
            }
            
            // Save to localStorage
            this.saveWishlist(wishlist);
            
            // Update button state
            this.updateWishlistButtons();
        },
        
        /**
         * Get Wishlist
         */
        getWishlist: function() {
            const wishlist = localStorage.getItem('parfume_wishlist');
            return wishlist ? JSON.parse(wishlist) : [];
        },
        
        /**
         * Save Wishlist
         */
        saveWishlist: function(wishlist) {
            localStorage.setItem('parfume_wishlist', JSON.stringify(wishlist));
        },
        
        /**
         * Update Wishlist Buttons
         */
        updateWishlistButtons: function() {
            const wishlist = this.getWishlist();
            
            $('.wishlist-toggle').each(function() {
                const $btn = $(this);
                const postId = $btn.data('post-id');
                
                if (wishlist.indexOf(postId) > -1) {
                    $btn.addClass('active');
                } else {
                    $btn.removeClass('active');
                }
            });
        },
        
        /**
         * Initialize Filters
         */
        initFilters: function() {
            const $filterForm = $('.parfume-filter-form');
            
            if ($filterForm.length === 0) return;
            
            // Auto-submit on change
            $filterForm.on('change', 'select, input[type="checkbox"]', function() {
                $filterForm.submit();
            });
            
            // Clear filters
            $(document).on('click', '.clear-filters', function(e) {
                e.preventDefault();
                
                $filterForm.find('select').val('');
                $filterForm.find('input[type="checkbox"]').prop('checked', false);
                $filterForm.submit();
            });
        },
        
        /**
         * Initialize Load More
         */
        initLoadMore: function() {
            const $loadMore = $('.load-more-btn');
            
            if ($loadMore.length === 0) return;
            
            $loadMore.on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const page = $btn.data('page');
                const maxPages = $btn.data('max-pages');
                
                if (page >= maxPages) {
                    $btn.hide();
                    return;
                }
                
                $btn.addClass('loading').text('Зареждане...');
                
                $.ajax({
                    url: parfumeReviews.ajaxurl,
                    type: 'GET',
                    data: {
                        action: 'load_more_parfumes',
                        page: page + 1,
                        // Add current filters
                        filters: $('.parfume-filter-form').serialize()
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('.parfumes-grid').append(response.data.html);
                            $btn.data('page', page + 1);
                            
                            if (page + 1 >= maxPages) {
                                $btn.hide();
                            }
                        }
                    },
                    error: function() {
                        alert('Грешка при зареждане');
                    },
                    complete: function() {
                        $btn.removeClass('loading').text('Зареди още');
                    }
                });
            });
        },
        
        /**
         * Initialize Notifications
         */
        initNotifications: function() {
            // Create notification container if it doesn't exist
            if ($('.parfume-notifications').length === 0) {
                $('body').append('<div class="parfume-notifications"></div>');
            }
        },
        
        /**
         * Show Notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            const $notification = $('<div class="parfume-notification ' + type + '">' + message + '</div>');
            
            $('.parfume-notifications').append($notification);
            
            // Animate in
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
            
            // Auto remove after 3 seconds
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },
        
        /**
         * Debounce Function
         */
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
        }
    };
    
    /**
     * Document Ready
     */
    $(document).ready(function() {
        ParfumeReviews.init();
    });
    
    /**
     * Make object globally available
     */
    window.ParfumeReviews = ParfumeReviews;
    
})(jQuery);