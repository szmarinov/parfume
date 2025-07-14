jQuery(document).ready(function($) {
    var file_frame;
    
    // Media uploader for taxonomy images
    $(document).on('click', '.pr_tax_media_button', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var fieldId = $button.data('field');
        var wrapperId = $button.data('wrapper');
        var $wrapper = $('#' + wrapperId);
        var $input = $('#' + fieldId);
        
        // Create the media frame
        file_frame = wp.media({
            title: 'Избери изображение',
            button: {
                text: 'Използвай това изображение'
            },
            multiple: false
        });
        
        // When an image is selected, run a callback
        file_frame.on('select', function() {
            var attachment = file_frame.state().get('selection').first().toJSON();
            var thumbnail = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            
            $input.val(attachment.id);
            $wrapper.html('<img src="' + thumbnail + '" alt="" style="max-width: 150px;" />');
        });
        
        // Open the modal
        file_frame.open();
    });
    
    // Remove image
    $(document).on('click', '.pr_tax_media_remove', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var fieldId = $button.data('field');
        var wrapperId = $button.data('wrapper');
        var $wrapper = $('#' + wrapperId);
        var $input = $('#' + fieldId);
        
        $input.val('');
        $wrapper.html('');
    });
});