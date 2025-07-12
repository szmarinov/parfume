jQuery(document).ready(function($) {
    // Tabs functionality
    $('.parfume-tabs .tabs-nav a').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this),
            tabId = $this.attr('href'),
            $tabPanel = $(tabId),
            $tabsContainer = $this.closest('.parfume-tabs');
        
        // Hide all tab panels
        $tabsContainer.find('.tab-panel').hide();
        
        // Remove active class from all tabs
        $tabsContainer.find('.tabs-nav a').removeClass('active');
        
        // Show current tab panel
        $tabPanel.show();
        
        // Add active class to current tab
        $this.addClass('active');
    });
    
    // Activate first tab by default
    $('.parfume-tabs .tabs-nav a:first').trigger('click');
    
    // Comparison functionality
    var updateComparisonCount = function() {
        var comparison = getComparisonItems();
        $('.comparison-count').text(comparison.length);
    };
    
    var getComparisonItems = function() {
        var comparisonCookie = $.cookie('parfume_comparison');
        return comparisonCookie ? JSON.parse(comparisonCookie) : [];
    };
    
    // Initialize comparison count
    updateComparisonCount();
    
    // Add to comparison
    $('.add-to-comparison').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this),
            postId = $this.data('post-id'),
            nonce = parfumeReviews.nonce;
        
        if ($this.hasClass('added')) {
            return;
        }
        
        $.ajax({
            url: parfumeReviews.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_to_comparison',
                post_id: postId,
                nonce: nonce
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
        
        var nonce = parfumeReviews.nonce;
        var comparison = getComparisonItems();
        
        if (comparison.length === 0) {
            alert(parfumeComparison.emptyText);
            return;
        }
        
        $.ajax({
            url: parfumeReviews.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_comparison_table',
                nonce: nonce
            },
            beforeSend: function() {
                // Show loading indicator
            },
            success: function(response) {
                if (response.success) {
                    // Create popup
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
                    
                    // Remove item from comparison
                    $('.remove-from-comparison').on('click', function() {
                        var $this = $(this),
                            postId = $this.data('post-id');
                        
                        $.ajax({
                            url: parfumeReviews.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'remove_from_comparison',
                                post_id: postId,
                                nonce: nonce
                            },
                            beforeSend: function() {
                                $this.prop('disabled', true);
                            },
                            success: function(response) {
                                if (response.success) {
                                    $this.closest('th').remove();
                                    $('td:nth-child(' + ($this.closest('th').index() + 1) + ')').remove();
                                    updateComparisonCount();
                                    
                                    if ($('thead th').length <= 1) {
                                        $('.comparison-popup').fadeOut(function() {
                                            $(this).remove();
                                        });
                                    }
                                } else {
                                    alert(response.data);
                                }
                            },
                            complete: function() {
                                $this.prop('disabled', false);
                            }
                        });
                    });
                    
                    // Clear comparison
                    $('#clear-comparison').on('click', function() {
                        var comparison = getComparisonItems();
                        
                        $.each(comparison, function(index, postId) {
                            $.ajax({
                                url: parfumeReviews.ajaxurl,
                                type: 'POST',
                                async: false,
                                data: {
                                    action: 'remove_from_comparison',
                                    post_id: postId,
                                    nonce: nonce
                                }
                            });
                        });
                        
                        $('.comparison-popup').fadeOut(function() {
                            $(this).remove();
                        });
                        
                        updateComparisonCount();
                        $('.add-to-comparison').removeClass('added').text(parfumeComparison.addText);
                    });
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // Collections dropdown
    $('.collections-toggle').on('click', function() {
        var $dropdown = $(this).next('.collections-dropdown-content');
        $dropdown.toggleClass('show');
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.parfume-collections-dropdown').length) {
            $('.collections-dropdown-content').removeClass('show');
        }
    });
    
    // Load user collections
    $('.collections-toggle').on('click', function() {
        var $dropdown = $(this).closest('.parfume-collections-dropdown'),
            $collectionsList = $dropdown.find('.collections-list'),
            postId = $dropdown.find('.create-collection').data('post-id'),
            nonce = parfumeReviews.nonce;
        
        if ($collectionsList.hasClass('loaded')) {
            return;
        }
        
        $.ajax({
            url: parfumeReviews.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_collections',
                post_id: postId,
                nonce: nonce
            },
            beforeSend: function() {
                $collectionsList.html('<p>Loading collections...</p>');
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.length > 0) {
                        var html = '';
                        
                        $.each(response.data, function(index, collection) {
                            html += '<div class="collection-item">';
                            html += '<label>';
                            html += '<input type="checkbox" class="collection-checkbox" ' + 
                                   (collection.has_parfume ? 'checked' : '') + 
                                   ' data-collection-id="' + collection.id + '">';
                            html += collection.name;
                            html += '</label>';
                            html += '</div>';
                        });
                        
                        $collectionsList.html(html);
                        
                        // Handle collection toggles
                        $('.collection-checkbox').on('change', function() {
                            var $this = $(this),
                                collectionId = $this.data('collection-id'),
                                action = $this.is(':checked') ? 'add_to_collection' : 'remove_from_collection';
                            
                            $.ajax({
                                url: parfumeReviews.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: action,
                                    post_id: postId,
                                    collection_id: collectionId,
                                    nonce: nonce
                                },
                                beforeSend: function() {
                                    $this.prop('disabled', true);
                                },
                                success: function(response) {
                                    if (!response.success) {
                                        $this.prop('checked', !$this.is(':checked'));
                                        alert(response.data);
                                    }
                                },
                                complete: function() {
                                    $this.prop('disabled', false);
                                }
                            });
                        });
                    } else {
                        $collectionsList.html('<p>No collections found.</p>');
                    }
                    
                    $collectionsList.addClass('loaded');
                } else {
                    $collectionsList.html('<p>' + response.data + '</p>');
                }
            }
        });
    });
    
    // Create new collection
    $('.create-collection').on('click', function() {
        var $this = $(this),
            $dropdown = $this.closest('.parfume-collections-dropdown'),
            $nameInput = $dropdown.find('.new-collection-name'),
            $privacySelect = $dropdown.find('.new-collection-privacy'),
            postId = $this.data('post-id'),
            nonce = parfumeReviews.nonce;
        
        if ($nameInput.val().trim() === '') {
            alert(parfumeCollections.collectionNameRequired);
            return;
        }
        
        $.ajax({
            url: parfumeReviews.ajaxurl,
            type: 'POST',
            data: {
                action: 'create_collection',
                name: $nameInput.val().trim(),
                privacy: $privacySelect.val(),
                post_id: postId,
                nonce: nonce
            },
            beforeSend: function() {
                $this.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $nameInput.val('');
                    $dropdown.find('.collections-list').removeClass('loaded');
                    $dropdown.find('.collections-toggle').trigger('click');
                } else {
                    alert(response.data);
                }
            },
            complete: function() {
                $this.prop('disabled', false);
            }
        });
    });
    
    // Price update functionality
    var updatePrices = function() {
        // This would be more complex in a real implementation
        // Would need to scrape store pages or use APIs to get current prices
        console.log('Price update functionality would go here');
    };
    
    // Check if we should update prices
    var settings = typeof parfumeReviewsSettings !== 'undefined' ? parfumeReviewsSettings : {};
    var lastUpdate = $.cookie('parfume_price_last_update');
    var updateInterval = settings.price_update_interval || 24;
    
    if (!lastUpdate || (new Date().getTime() - new Date(lastUpdate).getTime()) > (updateInterval * 3600 * 1000)) {
        updatePrices();
        $.cookie('parfume_price_last_update', new Date().toISOString(), { expires: 30, path: '/' });
    }
});