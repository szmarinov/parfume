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
    
    // Filter functionality
    $('#parfume-filters-form').on('submit', function(e) {
        // Make sure form submits to current archive page
        var currentUrl = window.location.pathname;
        this.action = currentUrl;
    });
    
    // Handle "Select All" checkboxes
    $('.select-all').on('change', function() {
        var $group = $(this).closest('.filter-group');
        var $checkboxes = $group.find('input[type="checkbox"]:not(.select-all)');
        
        if (this.checked) {
            $checkboxes.prop('checked', false);
        }
    });
    
    // Uncheck "Select All" when other options are selected
    $('.filter-option input[type="checkbox"]:not(.select-all)').on('change', function() {
        if (this.checked) {
            var $selectAll = $(this).closest('.filter-group').find('.select-all');
            $selectAll.prop('checked', false);
        }
    });
    
    // Search functionality in filters
    $('.filter-search').on('input', function() {
        var query = this.value.toLowerCase();
        var $options = $(this).siblings('.scrollable-options').find('.filter-option');
        
        $options.each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(query));
        });
    });
    
    // Toggle filter sections
    $('.filter-title').on('click', function() {
        var $this = $(this);
        var $options = $this.next('.filter-options');
        var $arrow = $this.find('.toggle-arrow');
        
        if ($options.is(':visible')) {
            $options.slideUp();
            $arrow.text('▶');
            $this.addClass('collapsed');
        } else {
            $options.slideDown();
            $arrow.text('▼');
            $this.removeClass('collapsed');
        }
    });
    
    // Price update functionality for admin
    if (typeof parfumeReviewsAdmin !== 'undefined') {
        $('.update-price-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var storeIndex = $btn.data('index');
            var $urlInput = $btn.closest('.store-item').find('input[name*="[url]"]');
            var url = $urlInput.val();
            
            if (!url) {
                alert('Моля въведете URL на магазина първо');
                return;
            }
            
            $btn.prop('disabled', true).text('Обновяване...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_store_price',
                    store_url: url,
                    nonce: parfumeReviewsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $priceInput = $btn.closest('.store-item').find('input[name*="[price]"]');
                        $priceInput.val(response.data.price);
                        
                        if (response.data.sizes) {
                            var $sizeInput = $btn.closest('.store-item').find('input[name*="[size]"]');
                            $sizeInput.val(response.data.sizes.join(', '));
                        }
                        
                        alert('Цената е обновена успешно');
                    } else {
                        alert('Грешка при обновяване на цената: ' + response.data);
                    }
                },
                error: function() {
                    alert('Грешка при обновяване на цената');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Обнови цена');
                }
            });
        });
        
        $('.get-sizes-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var storeIndex = $btn.data('index');
            var $urlInput = $btn.closest('.store-item').find('input[name*="[url]"]');
            var url = $urlInput.val();
            
            if (!url) {
                alert('Моля въведете URL на магазина първо');
                return;
            }
            
            $btn.prop('disabled', true).text('Извличане...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_store_sizes',
                    store_url: url,
                    nonce: parfumeReviewsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $sizeInput = $btn.closest('.store-item').find('input[name*="[size]"]');
                        $sizeInput.val(response.data.sizes.join(', '));
                        alert('Размерите са извлечени успешно');
                    } else {
                        alert('Грешка при извличане на размерите: ' + response.data);
                    }
                },
                error: function() {
                    alert('Грешка при извличане на размерите');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Извлечи размери');
                }
            });
        });
    }
    
    // Recently viewed tracking
    if ($('body').hasClass('single-parfume')) {
        var postId = $('body').attr('data-post-id') || $('.parfume-single').data('post-id');
        
        if (postId) {
            // Get current recently viewed
            var viewed = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
            
            // Remove current post if already in list
            viewed = viewed.filter(function(id) {
                return id != postId;
            });
            
            // Add current post to beginning
            viewed.unshift(postId);
            
            // Keep only last 10
            viewed = viewed.slice(0, 10);
            
            // Save back to localStorage
            localStorage.setItem('parfume_recently_viewed', JSON.stringify(viewed));
        }
    }
    
    // Settings functionality
    if (typeof parfumeSettings !== 'undefined') {
        // Auto-update URL preview when slug fields change
        function updateUrlPreviews() {
            var parfumeSlug = $('input[name="parfume_reviews_settings[parfume_slug]"]').val() || 'parfiumi';
            
            var slugFields = {
                'brands_slug': 'marki',
                'notes_slug': 'notes', 
                'perfumers_slug': 'parfumers',
                'gender_slug': 'gender',
                'aroma_type_slug': 'aroma-type',
                'season_slug': 'season',
                'intensity_slug': 'intensity'
            };
            
            $.each(slugFields, function(fieldName, defaultValue) {
                var slugValue = $('input[name="parfume_reviews_settings[' + fieldName + ']"]').val() || defaultValue;
                var $description = $('input[name="parfume_reviews_settings[' + fieldName + ']"]').siblings('.description');
                
                if ($description.length) {
                    var originalText = $description.data('original-text');
                    if (!originalText) {
                        originalText = $description.text();
                        $description.data('original-text', originalText);
                    }
                    
                    var newText = originalText.replace(/\/[^\/]+\/[^\/]+\//g, '/' + parfumeSlug + '/' + slugValue + '/');
                    $description.text(newText);
                }
            });
        }
        
        $('input[name^="parfume_reviews_settings["][name$="_slug]"]').on('input', updateUrlPreviews);
        updateUrlPreviews();
    }
});