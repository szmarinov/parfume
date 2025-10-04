/**
 * Comparison Lightbox
 * 
 * Handles comparison popup/lightbox functionality
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    var ComparisonLightbox = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.createLightbox();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Open comparison lightbox
            $(document).on('click', '.view-comparison, .comparison-count', function(e) {
                e.preventDefault();
                ComparisonLightbox.openLightbox();
            });
            
            // Close lightbox
            $(document).on('click', '.comparison-lightbox-close, .comparison-lightbox-overlay', function(e) {
                e.preventDefault();
                ComparisonLightbox.closeLightbox();
            });
            
            // ESC key to close
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    ComparisonLightbox.closeLightbox();
                }
            });
            
            // Prevent closing when clicking inside lightbox
            $(document).on('click', '.comparison-lightbox-content', function(e) {
                e.stopPropagation();
            });
            
            // Remove from comparison
            $(document).on('click', '.remove-from-comparison-lightbox', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                ComparisonLightbox.removeItem(postId);
            });
            
            // Clear all
            $(document).on('click', '.clear-all-comparison', function(e) {
                e.preventDefault();
                if (confirm('Наистина ли искате да изчистите всички парфюми от сравнението?')) {
                    ComparisonLightbox.clearAll();
                }
            });
        },
        
        /**
         * Create lightbox HTML
         */
        createLightbox: function() {
            if ($('#comparison-lightbox').length > 0) {
                return;
            }
            
            var lightboxHtml = 
                '<div id="comparison-lightbox" class="comparison-lightbox" style="display: none;">' +
                    '<div class="comparison-lightbox-overlay"></div>' +
                    '<div class="comparison-lightbox-wrapper">' +
                        '<div class="comparison-lightbox-content">' +
                            '<button class="comparison-lightbox-close" aria-label="Затвори">×</button>' +
                            '<div class="comparison-lightbox-header">' +
                                '<h2>Сравнение на парфюми</h2>' +
                                '<button class="clear-all-comparison button">Изчисти всички</button>' +
                            '</div>' +
                            '<div class="comparison-lightbox-body">' +
                                '<div class="comparison-loading">Зареждане...</div>' +
                                '<div class="comparison-table-container"></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            
            $('body').append(lightboxHtml);
        },
        
        /**
         * Open lightbox
         */
        openLightbox: function() {
            var $lightbox = $('#comparison-lightbox');
            
            // Show lightbox
            $lightbox.fadeIn(300);
            $('body').addClass('comparison-lightbox-open');
            
            // Load comparison data
            this.loadComparisonData();
        },
        
        /**
         * Close lightbox
         */
        closeLightbox: function() {
            var $lightbox = $('#comparison-lightbox');
            
            $lightbox.fadeOut(300);
            $('body').removeClass('comparison-lightbox-open');
        },
        
        /**
         * Load comparison data via AJAX
         */
        loadComparisonData: function() {
            var $container = $('.comparison-table-container');
            var $loading = $('.comparison-loading');
            
            $loading.show();
            $container.hide();
            
            $.ajax({
                url: parfumeReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_comparison_data',
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    $loading.hide();
                    
                    if (response.success && response.data) {
                        ComparisonLightbox.renderComparisonTable(response.data);
                        $container.show();
                    } else {
                        $container.html('<div class="comparison-empty">Няма добавени парфюми за сравнение</div>').show();
                    }
                },
                error: function() {
                    $loading.hide();
                    $container.html('<div class="comparison-error">Грешка при зареждане</div>').show();
                }
            });
        },
        
        /**
         * Render comparison table
         * 
         * @param {Object} data Comparison data
         */
        renderComparisonTable: function(data) {
            if (!data.items || data.items.length === 0) {
                $('.comparison-table-container').html('<div class="comparison-empty">Няма добавени парфюми за сравнение</div>');
                return;
            }
            
            var html = '<div class="comparison-table-scroll"><table class="comparison-table">';
            
            // Header row with images
            html += '<thead><tr><th class="comparison-row-label">Парфюм</th>';
            $.each(data.items, function(index, item) {
                html += '<th class="comparison-item">';
                html += '<div class="comparison-item-image">';
                if (item.image) {
                    html += '<img src="' + item.image + '" alt="' + item.title + '">';
                }
                html += '</div>';
                html += '<div class="comparison-item-title">' + item.title + '</div>';
                html += '<button class="remove-from-comparison-lightbox" data-post-id="' + item.id + '">×</button>';
                html += '</th>';
            });
            html += '</tr></thead>';
            
            html += '<tbody>';
            
            // Brand row
            html += '<tr><th>Марка</th>';
            $.each(data.items, function(index, item) {
                html += '<td>' + (item.brand || '-') + '</td>';
            });
            html += '</tr>';
            
            // Gender row
            html += '<tr><th>Пол</th>';
            $.each(data.items, function(index, item) {
                html += '<td>' + (item.gender || '-') + '</td>';
            });
            html += '</tr>';
            
            // Rating row
            html += '<tr><th>Оценка</th>';
            $.each(data.items, function(index, item) {
                html += '<td>';
                if (item.rating) {
                    html += '<span class="rating-value">' + item.rating + '/10</span>';
                    html += '<div class="rating-stars">' + ComparisonLightbox.renderStars(item.rating) + '</div>';
                } else {
                    html += '-';
                }
                html += '</td>';
            });
            html += '</tr>';
            
            // Price row
            html += '<tr><th>Най-ниска цена</th>';
            $.each(data.items, function(index, item) {
                html += '<td>';
                if (item.price) {
                    html += '<span class="price">' + parseFloat(item.price).toFixed(2) + ' лв</span>';
                } else {
                    html += '-';
                }
                html += '</td>';
            });
            html += '</tr>';
            
            // Notes row
            html += '<tr><th>Нотки</th>';
            $.each(data.items, function(index, item) {
                html += '<td>';
                if (item.notes && item.notes.length > 0) {
                    html += item.notes.join(', ');
                } else {
                    html += '-';
                }
                html += '</td>';
            });
            html += '</tr>';
            
            // Actions row
            html += '<tr><th>Действия</th>';
            $.each(data.items, function(index, item) {
                html += '<td>';
                html += '<a href="' + item.url + '" class="button button-primary" target="_blank">Виж детайли</a>';
                html += '</td>';
            });
            html += '</tr>';
            
            html += '</tbody></table></div>';
            
            $('.comparison-table-container').html(html);
        },
        
        /**
         * Render star rating
         * 
         * @param {number} rating Rating 0-10
         * @return {string} HTML
         */
        renderStars: function(rating) {
            var stars = '';
            var starsCount = Math.round(rating / 2); // Convert 0-10 to 0-5
            
            for (var i = 1; i <= 5; i++) {
                if (i <= starsCount) {
                    stars += '<span class="star filled">★</span>';
                } else {
                    stars += '<span class="star empty">☆</span>';
                }
            }
            
            return stars;
        },
        
        /**
         * Remove item from comparison
         * 
         * @param {number} postId Post ID
         */
        removeItem: function(postId) {
            $.ajax({
                url: parfumeReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'remove_from_comparison',
                    post_id: postId,
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload comparison data
                        ComparisonLightbox.loadComparisonData();
                        
                        // Update comparison count in bar
                        if (typeof window.updateComparisonCount === 'function') {
                            window.updateComparisonCount();
                        }
                        
                        // Trigger custom event
                        $(document).trigger('parfume_comparison_updated');
                    }
                }
            });
        },
        
        /**
         * Clear all items
         */
        clearAll: function() {
            $.ajax({
                url: parfumeReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clear_comparison',
                    nonce: parfumeReviews.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ComparisonLightbox.closeLightbox();
                        
                        // Update comparison count
                        if (typeof window.updateComparisonCount === 'function') {
                            window.updateComparisonCount();
                        }
                        
                        // Trigger custom event
                        $(document).trigger('parfume_comparison_updated');
                    }
                }
            });
        }
    };
    
    // Initialize when document ready
    $(document).ready(function() {
        ComparisonLightbox.init();
    });
    
})(jQuery);