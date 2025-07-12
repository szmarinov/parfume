// Comparison functionality for Parfume Reviews
jQuery(document).ready(function($) {
    
    var updateComparisonCount = function() {
        var comparison = getComparisonItems();
        $('.comparison-count').text(comparison.length);
        
        if (comparison.length > 0) {
            $('.comparison-link').show();
        } else {
            $('.comparison-link').hide();
        }
    };
    
    var getComparisonItems = function() {
        var cookies = document.cookie.split(';');
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].trim();
            if (cookie.indexOf('parfume_comparison=') === 0) {
                var value = cookie.substring('parfume_comparison='.length);
                try {
                    return JSON.parse(decodeURIComponent(value));
                } catch (e) {
                    return [];
                }
            }
        }
        return [];
    };
    
    // Initialize comparison count
    updateComparisonCount();
    
    // Add to comparison
    $('.add-to-comparison').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var postId = $this.data('post-id');
        
        if ($this.hasClass('added')) {
            return;
        }
        
        $.ajax({
            url: parfumeComparison.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_to_comparison',
                post_id: postId,
                nonce: parfumeComparison.nonce
            },
            beforeSend: function() {
                $this.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $this.text(parfumeComparison.addedText).addClass('added');
                    updateComparisonCount();
                } else {
                    alert(response.data);
                }
            },
            complete: function() {
                $this.prop('disabled', false);
            }
        });
    });
    
    // Show comparison popup
    $('#show-comparison').on('click', function(e) {
        e.preventDefault();
        
        var comparison = getComparisonItems();
        
        if (comparison.length === 0) {
            alert(parfumeComparison.emptyText);
            return;
        }
        
        $.ajax({
            url: parfumeComparison.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_comparison_table',
                nonce: parfumeComparison.nonce
            },
            success: function(response) {
                if (response.success) {
                    var popup = $('<div class="comparison-popup"><div class="comparison-container">' + 
                                 '<button class="close-comparison">&times;</button>' + 
                                 response.data.html + '</div></div>');
                    
                    $('body').append(popup);
                    $('.comparison-popup').fadeIn();
                    
                    // Close popup
                    $('.close-comparison').on('click', function() {
                        $('.comparison-popup').fadeOut(function() {
                            $(this).remove();
                        });
                    });
                    
                    // Clear comparison
                    $('#clear-comparison').on('click', function() {
                        $.ajax({
                            url: parfumeComparison.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'clear_comparison',
                                nonce: parfumeComparison.nonce
                            },
                            success: function() {
                                $('.comparison-popup').fadeOut(function() {
                                    $(this).remove();
                                });
                                updateComparisonCount();
                                $('.add-to-comparison').removeClass('added').text(parfumeComparison.addText);
                            }
                        });
                    });
                } else {
                    alert(response.data);
                }
            }
        });
    });
});