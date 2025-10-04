/**
 * Comparison JavaScript
 * 
 * Handles parfume comparison functionality
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Comparison Object
     */
    const Comparison = {
        
        /**
         * Comparison items (stored in session)
         */
        items: [],
        
        /**
         * Maximum items allowed
         */
        maxItems: 4,
        
        /**
         * Initialize
         */
        init: function() {
            // Get settings from localized script
            if (typeof parfumeComparison !== 'undefined') {
                this.maxItems = parseInt(parfumeComparison.maxItems) || 4;
                this.items = parfumeComparison.items || [];
            }
            
            this.updateBar();
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;
            
            // Add to comparison
            $(document).on('click', '.comparison-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const postId = $btn.data('post-id');
                
                if ($btn.hasClass('in-comparison')) {
                    self.remove(postId);
                } else {
                    self.add(postId);
                }
            });
            
            // Remove from comparison bar
            $(document).on('click', '.remove-from-comparison', function(e) {
                e.preventDefault();
                
                const postId = $(this).data('post-id');
                self.remove(postId);
            });
            
            // Clear all
            $(document).on('click', '.clear-comparison-btn', function(e) {
                e.preventDefault();
                self.clear();
            });
            
            // Listen for custom event
            $(document).on('parfume:addToComparison', function(e, postId) {
                self.add(postId);
            });
        },
        
        /**
         * Add item to comparison
         */
        add: function(postId) {
            postId = parseInt(postId);
            
            // Check if already in comparison
            if (this.items.indexOf(postId) !== -1) {
                this.showNotification(parfumeComparison.strings.added, 'info');
                return;
            }
            
            // Check max limit
            if (this.items.length >= this.maxItems) {
                this.showNotification(parfumeComparison.strings.max_reached, 'warning');
                return;
            }
            
            // Add to items
            this.items.push(postId);
            this.save();
            this.updateBar();
            this.updateButtons();
            
            this.showNotification(parfumeComparison.strings.added, 'success');
        },
        
        /**
         * Remove item from comparison
         */
        remove: function(postId) {
            postId = parseInt(postId);
            
            const index = this.items.indexOf(postId);
            
            if (index > -1) {
                this.items.splice(index, 1);
                this.save();
                this.updateBar();
                this.updateButtons();
                
                this.showNotification(parfumeComparison.strings.removed, 'success');
            }
        },
        
        /**
         * Clear all items
         */
        clear: function() {
            if (!confirm('Сигурни ли сте, че искате да изчистите всички?')) {
                return;
            }
            
            this.items = [];
            this.save();
            this.updateBar();
            this.updateButtons();
            
            this.showNotification(parfumeComparison.strings.clear, 'success');
        },
        
        /**
         * Save to AJAX (session storage on server)
         */
        save: function() {
            $.ajax({
                url: parfumeComparison.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_comparison',
                    items: this.items,
                    nonce: parfumeComparison.nonce
                }
            });
        },
        
        /**
         * Update comparison bar
         */
        updateBar: function() {
            const $bar = $('.comparison-bar');
            const $items = $bar.find('.comparison-items');
            const $count = $bar.find('.comparison-count');
            
            // Update count
            $count.text('Сравнение (' + this.items.length + ')');
            
            // Show/hide bar
            if (this.items.length > 0) {
                $bar.addClass('has-items');
            } else {
                $bar.removeClass('has-items');
            }
            
            // Update items in bar (if needed, fetch data via AJAX)
            if (this.items.length > 0) {
                this.fetchComparisonData();
            } else {
                $items.empty();
            }
        },
        
        /**
         * Fetch comparison data via AJAX
         */
        fetchComparisonData: function() {
            const self = this;
            
            $.ajax({
                url: parfumeComparison.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_comparison_data',
                    nonce: parfumeComparison.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.renderComparisonItems(response.data);
                    }
                }
            });
        },
        
        /**
         * Render comparison items in bar
         */
        renderComparisonItems: function(data) {
            const $items = $('.comparison-items');
            $items.empty();
            
            $.each(data, function(index, item) {
                const $item = $('<div class="comparison-item" data-post-id="' + item.id + '">' +
                    (item.thumbnail ? '<img src="' + item.thumbnail + '" alt="' + item.title + '">' : '') +
                    '<button type="button" class="remove-from-comparison" data-post-id="' + item.id + '">' +
                    '<span class="dashicons dashicons-no"></span>' +
                    '</button>' +
                    '</div>');
                
                $items.append($item);
            });
        },
        
        /**
         * Update comparison toggle buttons
         */
        updateButtons: function() {
            const self = this;
            
            $('.comparison-toggle').each(function() {
                const $btn = $(this);
                const postId = parseInt($btn.data('post-id'));
                
                if (self.items.indexOf(postId) !== -1) {
                    $btn.addClass('in-comparison');
                    $btn.find('.btn-text').text('Премахни от сравнение');
                } else {
                    $btn.removeClass('in-comparison');
                    $btn.find('.btn-text').text('Добави за сравнение');
                }
            });
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            if (typeof ParfumeReviews !== 'undefined' && ParfumeReviews.showNotification) {
                ParfumeReviews.showNotification(message, type);
            } else {
                // Fallback
                alert(message);
            }
        }
    };
    
    /**
     * Document Ready
     */
    $(document).ready(function() {
        if (typeof parfumeComparison !== 'undefined') {
            Comparison.init();
        }
    });
    
    /**
     * Make object globally available
     */
    window.ParfumeComparison = Comparison;
    
})(jQuery);