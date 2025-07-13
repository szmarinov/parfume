/**
 * Parfume Reviews - Beautiful Comparison Functionality
 * КРАСИВ И ПРОФЕСИОНАЛЕН ДИЗАЙН!
 */
jQuery(document).ready(function($) {
    
    // Initialize comparison functionality
    updateComparisonCount();
    
    // Helper function to get comparison items from localStorage
    function getComparisonItems() {
        var comparison = localStorage.getItem('parfume_comparison');
        return comparison ? JSON.parse(comparison) : [];
    }
    
    // Helper function to save comparison items to localStorage
    function saveComparisonItems(items) {
        localStorage.setItem('parfume_comparison', JSON.stringify(items));
    }
    
    // Update comparison count in UI
    function updateComparisonCount() {
        var comparison = getComparisonItems();
        var count = comparison.length;
        
        $('.comparison-count').text(count > 0 ? count : '');
        
        // Show/hide comparison widget with beautiful animation
        if (count > 0) {
            $('.comparison-widget').addClass('active').fadeIn(300);
            $('.comparison-widget .widget-count').text(count);
        } else {
            $('.comparison-widget').removeClass('active').fadeOut(300);
        }
        
        // Update buttons state with icons
        $('.add-to-comparison').each(function() {
            var $this = $(this);
            var postId = parseInt($this.data('post-id'));
            var isAdded = comparison.some(function(item) {
                return item.id === postId;
            });
            
            if (isAdded) {
                $this.addClass('added')
                     .html('<span class="button-icon">✓</span>' + parfumeComparison.addedText);
            } else {
                $this.removeClass('added')
                     .html('<span class="button-icon">⚖</span>' + parfumeComparison.addText);
            }
        });
    }
    
    // Add to comparison with beautiful feedback
    $(document).on('click', '.add-to-comparison', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var postId = parseInt($this.data('post-id'));
        
        // Add ripple effect
        addRippleEffect($this, e);
        
        if ($this.hasClass('added')) {
            // Remove from comparison
            removeFromComparison(postId);
            return;
        }
        
        var comparison = getComparisonItems();
        
        // Check if already added
        if (comparison.some(function(item) { return item.id === postId; })) {
            showNotification(parfumeComparison.alreadyAddedText || 'Вече е добавен за сравнение', 'warning');
            return;
        }
        
        // Check maximum items
        if (comparison.length >= parfumeComparison.maxItems) {
            showNotification('Можете да сравнявате максимум ' + parfumeComparison.maxItems + ' парфюма', 'error');
            return;
        }
        
        // Add loading state
        $this.addClass('loading').prop('disabled', true)
             .html('<span class="button-icon">⏳</span>Добавяне...');
        
        // Add to localStorage immediately for faster UI response
        var postTitle = getPostTitle($this);
        
        comparison.push({
            id: postId,
            title: postTitle.trim(),
            url: window.location.href
        });
        saveComparisonItems(comparison);
        
        // Simulate a short delay for better UX
        setTimeout(function() {
            $this.removeClass('loading').prop('disabled', false)
                 .addClass('added')
                 .html('<span class="button-icon">✓</span>' + parfumeComparison.addedText);
            
            updateComparisonCount();
            showNotification(parfumeComparison.addedText, 'success');
        }, 500);
    });
    
    // Helper function to get post title
    function getPostTitle($button) {
        var title = $button.closest('.parfume-card').find('.parfume-title').text() || 
                   $button.closest('article').find('h1, h2, h3').first().text() || 
                   $button.closest('.product').find('.title, .name').text() ||
                   'Парфюм #' + $button.data('post-id');
        return title;
    }
    
    // Add ripple effect to buttons
    function addRippleEffect($button, event) {
        var rect = $button[0].getBoundingClientRect();
        var x = event.clientX - rect.left;
        var y = event.clientY - rect.top;
        
        var $ripple = $('<span class="ripple"></span>');
        $ripple.css({
            position: 'absolute',
            borderRadius: '50%',
            background: 'rgba(255,255,255,0.6)',
            transform: 'scale(0)',
            animation: 'ripple 0.6s linear',
            left: x - 10 + 'px',
            top: y - 10 + 'px',
            width: '20px',
            height: '20px'
        });
        
        $button.css('position', 'relative').append($ripple);
        
        setTimeout(function() {
            $ripple.remove();
        }, 600);
    }
    
    // Add CSS for ripple animation
    if (!$('#ripple-css').length) {
        $('<style id="ripple-css">@keyframes ripple { to { transform: scale(4); opacity: 0; } }</style>')
            .appendTo('head');
    }
    
    // Remove from comparison
    function removeFromComparison(postId) {
        var comparison = getComparisonItems();
        comparison = comparison.filter(function(item) {
            return item.id !== postId;
        });
        saveComparisonItems(comparison);
        updateComparisonCount();
        showNotification('Премахнато от сравнението', 'info');
    }
    
    // Show beautiful comparison popup
    $(document).on('click', '#show-comparison, .comparison-widget .widget-button, .comparison-widget, .comparison-link', function(e) {
        e.preventDefault();
        
        var comparison = getComparisonItems();
        
        if (comparison.length === 0) {
            showNotification(parfumeComparison.emptyText, 'warning');
            return;
        }
        
        // Remove any existing popups first
        $('.comparison-popup').remove();
        
        // Create beautiful loading popup
        var $loadingPopup = $('<div class="comparison-popup" style="opacity: 0;">' +
                              '<div class="comparison-container">' +
                              '<button class="close-comparison">&times;</button>' +
                              '<div class="comparison-loading">Зареждане на сравнението...</div>' +
                              '</div></div>');
        
        $('body').append($loadingPopup);
        
        // Beautiful fade in with backdrop
        $loadingPopup.animate({opacity: 1}, 300);
        
        // Get comparison table via AJAX
        $.ajax({
            url: parfumeComparison.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_comparison_table',
                nonce: parfumeComparison.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create beautiful popup with content
                    var $popup = $('<div class="comparison-popup" style="opacity: 0;">' +
                                   response.data.html + 
                                   '</div>');
                    
                    // Replace loading popup with content popup
                    $loadingPopup.fadeOut(200, function() {
                        $(this).remove();
                        $('body').append($popup);
                        $popup.animate({opacity: 1}, 300);
                        
                        // Initialize popup features
                        initializePopupFeatures();
                    });
                    
                } else {
                    // Show beautiful error state
                    $loadingPopup.find('.comparison-loading').html(
                        '<div class="comparison-empty">' +
                        '<div class="empty-icon">❌</div>' +
                        '<h3>Възникна грешка</h3>' +
                        '<p>' + (response.data || parfumeComparison.emptyText) + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $loadingPopup.find('.comparison-loading').html(
                    '<div class="comparison-empty">' +
                    '<div class="empty-icon">❌</div>' +
                    '<h3>Възникна грешка</h3>' +
                    '<p>Моля, опитайте отново по-късно</p>' +
                    '</div>'
                );
            }
        });
    });
    
    // Initialize popup features after popup is created
    function initializePopupFeatures() {
        // Add hover effects to parfume cards
        $('.parfume-info').hover(
            function() {
                $(this).addClass('hovered');
            },
            function() {
                $(this).removeClass('hovered');
            }
        );
        
        // Handle remove from comparison in popup
        $(document).on('click', '.remove-from-comparison', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var postId = parseInt($(this).data('post-id'));
            var $parfumeColumn = $(this).closest('td');
            
            // Beautiful remove animation
            $parfumeColumn.addClass('removing');
            
            setTimeout(function() {
                removeFromComparison(postId);
                
                // If no more items, close popup
                if (getComparisonItems().length === 0) {
                    $('.comparison-popup').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    // Refresh popup content
                    $('#show-comparison').trigger('click');
                }
            }, 300);
        });
        
        // Add print functionality
        $('#print-comparison').on('click', function() {
            window.print();
        });
    }
    
    // Close popup with beautiful animation
    $(document).on('click', '.close-comparison', function(e) {
        e.preventDefault();
        var $popup = $('.comparison-popup');
        $popup.animate({opacity: 0}, 300, function() {
            $(this).remove();
        });
    });
    
    // Close popup when clicking outside
    $(document).on('click', '.comparison-popup', function(e) {
        if (e.target === this) {
            var $popup = $(this);
            $popup.animate({opacity: 0}, 300, function() {
                $(this).remove();
            });
        }
    });
    
    // Clear comparison with confirmation
    $(document).on('click', '#clear-comparison', function(e) {
        e.preventDefault();
        
        // Beautiful confirmation dialog
        showConfirmDialog(
            'Изчистване на сравнението',
            'Сигурни ли сте, че искате да изчистите всички парфюми от сравнението?',
            function() {
                // Clear localStorage immediately
                localStorage.removeItem('parfume_comparison');
                
                // Beautiful close animation
                $('.comparison-popup').animate({opacity: 0}, 300, function() {
                    $(this).remove();
                });
                
                // Update UI
                updateComparisonCount();
                showNotification('Сравнението е изчистено успешно', 'success');
            }
        );
    });
    
    // Beautiful notification system
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.parfume-notification').each(function() {
            $(this).animate({
                opacity: 0,
                transform: 'translateX(100%)'
            }, 200, function() {
                $(this).remove();
            });
        });
        
        var icons = {
            'success': '✓',
            'error': '❌',
            'warning': '⚠',
            'info': 'ℹ'
        };
        
        var $notification = $('<div class="parfume-notification ' + type + '">' +
                              '<span class="notification-icon">' + (icons[type] || icons.info) + '</span>' +
                              '<span class="notification-message">' + message + '</span>' +
                              '</div>');
        
        $notification.css({
            opacity: 0,
            transform: 'translateX(100%)'
        });
        
        $('body').append($notification);
        
        // Beautiful slide in animation
        setTimeout(function() {
            $notification.animate({
                opacity: 1,
                transform: 'translateX(0)'
            }, 300);
        }, 10);
        
        // Auto remove after 4 seconds
        setTimeout(function() {
            $notification.animate({
                opacity: 0,
                transform: 'translateX(100%)'
            }, 300, function() {
                $(this).remove();
            });
        }, 4000);
        
        // Click to dismiss
        $notification.on('click', function() {
            $(this).animate({
                opacity: 0,
                transform: 'translateX(100%)'
            }, 300, function() {
                $(this).remove();
            });
        });
    }
    
    // Beautiful confirmation dialog
    function showConfirmDialog(title, message, onConfirm) {
        var $dialog = $('<div class="comparison-popup" style="opacity: 0;">' +
                        '<div class="comparison-container" style="max-width: 500px;">' +
                        '<div class="comparison-header">' +
                        '<h2>' + title + '</h2>' +
                        '</div>' +
                        '<div class="comparison-content" style="padding: 30px; text-align: center;">' +
                        '<p style="font-size: 16px; margin-bottom: 30px; line-height: 1.6;">' + message + '</p>' +
                        '<div class="dialog-actions">' +
                        '<button class="button cancel-btn" style="background: #6c757d; color: white; margin-right: 15px;">Отказ</button>' +
                        '<button class="button confirm-btn" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white;">Потвърди</button>' +
                        '</div>' +
                        '</div>' +
                        '</div></div>');
        
        $('body').append($dialog);
        $dialog.animate({opacity: 1}, 300);
        
        $dialog.find('.cancel-btn').on('click', function() {
            $dialog.animate({opacity: 0}, 300, function() {
                $(this).remove();
            });
        });
        
        $dialog.find('.confirm-btn').on('click', function() {
            $dialog.animate({opacity: 0}, 300, function() {
                $(this).remove();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
        });
        
        // Close on outside click
        $dialog.on('click', function(e) {
            if (e.target === this) {
                $dialog.animate({opacity: 0}, 300, function() {
                    $(this).remove();
                });
            }
        });
    }
    
    // Add beautiful comparison widget if it doesn't exist
    if (!$('.comparison-widget').length) {
        var $widget = $('<div class="comparison-widget" style="display: none;">' +
                        '<span class="widget-icon">⚖️</span>' +
                        '<span class="widget-text">Сравнение</span>' +
                        '<span class="widget-count">0</span>' +
                        '<button class="widget-button">Покажи</button>' +
                        '</div>');
        $('body').append($widget);
    }
    
    // Handle keyboard shortcuts
    $(document).on('keydown', function(e) {
        // ESC to close popup
        if (e.keyCode === 27) {
            $('.comparison-popup').animate({opacity: 0}, 300, function() {
                $(this).remove();
            });
        }
        
        // Ctrl+Shift+C to show comparison
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            if ($('.comparison-widget').is(':visible')) {
                $('.comparison-widget .widget-button').trigger('click');
            }
        }
    });
    
    // Debug function to test beautiful popup
    window.testBeautifulComparison = function() {
        var testItems = [
            {id: 1, title: 'Chanel No. 5', url: '/test1'},
            {id: 2, title: 'Dior Sauvage', url: '/test2'},
            {id: 3, title: 'Tom Ford Black Orchid', url: '/test3'}
        ];
        saveComparisonItems(testItems);
        updateComparisonCount();
        $('.comparison-widget .widget-button').trigger('click');
    };
    
    console.log('🎨 Beautiful Comparison functionality loaded! Use testBeautifulComparison() to test.');
});