<?php
namespace ParfumeReviews\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class MediaHandler {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_scripts']);
    }
    
    public function enqueue_media_scripts($hook) {
        if (strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) {
            wp_enqueue_media();
            
            wp_enqueue_script(
                'parfume-taxonomy-media',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/taxonomy-media.js',
                ['jquery'],
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Add inline script for media handling
            wp_add_inline_script('parfume-taxonomy-media', $this->get_media_script());
        }
    }
    
    private function get_media_script() {
        return "
        jQuery(document).ready(function($) {
            var file_frame;
            
            // Media uploader
            $(document).on('click', '.pr_tax_media_button', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var fieldId = button.data('field');
                var wrapperId = button.data('wrapper');
                var wrapper = $('#' + wrapperId);
                var input = $('#' + fieldId);
                
                file_frame = wp.media({
                    title: 'Избери изображение',
                    button: { text: 'Използвай това изображение' },
                    multiple: false
                });
                
                file_frame.on('select', function() {
                    var attachment = file_frame.state().get('selection').first().toJSON();
                    var thumbnail = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    
                    input.val(attachment.id);
                    wrapper.html('<img src=\"' + thumbnail + '\" alt=\"\" style=\"max-width: 150px;\" />');
                });
                
                file_frame.open();
            });
            
            // Remove image
            $(document).on('click', '.pr_tax_media_remove', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var fieldId = button.data('field');
                var wrapperId = button.data('wrapper');
                var wrapper = $('#' + wrapperId);
                var input = $('#' + fieldId);
                
                input.val('');
                wrapper.html('');
            });
        });
        ";
    }
}