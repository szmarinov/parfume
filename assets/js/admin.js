// Admin functionality for Parfume Reviews
jQuery(document).ready(function($) {
    
    // Media uploader for taxonomy images
    var file_frame;
    
    $(document).on('click', '.pr_tax_media_button', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $wrapper = $button.siblings('[id$="-wrapper"]');
        var $input = $button.siblings('input[type="hidden"]');
        
        if (file_frame) {
            file_frame.open();
            return;
        }
        
        file_frame = wp.media.frames.downloadable_file = wp.media({
            title: 'Choose an Image',
            button: {
                text: 'Use this Image'
            },
            multiple: false
        });
        
        file_frame.on('select', function() {
            var attachment = file_frame.state().get('selection').first().toJSON();
            var thumbnail = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            
            $input.val(attachment.id);
            $wrapper.html('<img src="' + thumbnail + '" alt="" style="max-width: 150px;" />');
        });
        
        file_frame.open();
    });
    
    // Remove image
    $(document).on('click', '.pr_tax_media_remove', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $wrapper = $button.siblings('[id$="-wrapper"]');
        var $input = $button.siblings('input[type="hidden"]');
        
        $input.val('');
        $wrapper.html('');
    });
    
    // Rating preview
    $('#parfume_rating').on('input', function() {
        var rating = parseFloat($(this).val()) || 0;
        var $preview = $('.rating-preview');
        
        if ($preview.length === 0) {
            var previewHtml = '<div class="rating-preview">' +
                '<strong>Preview:</strong><br>' +
                '<span class="star">★</span>' +
                '<span class="star">★</span>' +
                '<span class="star">★</span>' +
                '<span class="star">★</span>' +
                '<span class="star">★</span>' +
                ' <span class="rating-text">(' + rating.toFixed(1) + '/5)</span>' +
                '</div>';
            
            $(this).parent().append(previewHtml);
            $preview = $('.rating-preview');
        }
        
        $preview.find('.star').each(function(index) {
            if (index < Math.round(rating)) {
                $(this).addClass('filled');
            } else {
                $(this).removeClass('filled');
            }
        });
        
        $preview.find('.rating-text').text('(' + rating.toFixed(1) + '/5)');
    });
});