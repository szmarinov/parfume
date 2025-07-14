// Collections functionality for Parfume Reviews
jQuery(document).ready(function($) {
    
    // Collections dropdown
    $('.collections-toggle').on('click', function() {
        var $dropdown = $(this).next('.collections-dropdown-content');
        $dropdown.toggleClass('show');
        
        // Load collections if not already loaded
        if (!$dropdown.find('.collections-list').hasClass('loaded')) {
            loadUserCollections($dropdown);
        }
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.parfume-collections-dropdown').length) {
            $('.collections-dropdown-content').removeClass('show');
        }
    });
    
    function loadUserCollections($dropdown) {
        var $collectionsList = $dropdown.find('.collections-list');
        var postId = $dropdown.find('.create-collection').data('post-id');
        
        $.ajax({
            url: parfumeCollections.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_collections',
                post_id: postId,
                nonce: parfumeCollections.nonce
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
                            var $this = $(this);
                            var collectionId = $this.data('collection-id');
                            var action = $this.is(':checked') ? 'add_to_collection' : 'remove_from_collection';
                            
                            $.ajax({
                                url: parfumeCollections.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: action,
                                    post_id: postId,
                                    collection_id: collectionId,
                                    nonce: parfumeCollections.nonce
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
    }
    
    // Create new collection
    $('.create-collection').on('click', function() {
        var $this = $(this);
        var $dropdown = $this.closest('.parfume-collections-dropdown');
        var $nameInput = $dropdown.find('.new-collection-name');
        var $privacySelect = $dropdown.find('.new-collection-privacy');
        var postId = $this.data('post-id');
        
        if ($nameInput.val().trim() === '') {
            alert(parfumeCollections.collectionNameRequired);
            return;
        }
        
        $.ajax({
            url: parfumeCollections.ajaxurl,
            type: 'POST',
            data: {
                action: 'create_collection',
                name: $nameInput.val().trim(),
                privacy: $privacySelect.val(),
                post_id: postId,
                nonce: parfumeCollections.nonce
            },
            beforeSend: function() {
                $this.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $nameInput.val('');
                    $dropdown.find('.collections-list').removeClass('loaded');
                    loadUserCollections($dropdown);
                } else {
                    alert(response.data);
                }
            },
            complete: function() {
                $this.prop('disabled', false);
            }
        });
    });
});