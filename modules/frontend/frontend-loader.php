<?php
namespace ParfumeReviews\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class Loader {
    
    public function __construct() {
        $this->load_frontend_modules();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_head', [$this, 'add_schema_markup']);
    }
    
    private function load_frontend_modules() {
        $modules = [
            'shortcodes.php',
            'template-functions.php',
        ];
        
        foreach ($modules as $module) {
            $file = PARFUME_REVIEWS_PLUGIN_DIR . 'modules/frontend/' . $module;
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        // Initialize shortcodes
        new Shortcodes();
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || $this->is_parfume_taxonomy()) {
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                ['jquery'],
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-reviews-nonce'),
                'strings' => [
                    'loading' => __('Зареждане...', 'parfume-reviews'),
                    'error' => __('Възникна грешка', 'parfume-reviews'),
                ],
            ]);
        }
    }
    
    private function is_parfume_taxonomy() {
        return is_tax(['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer']);
    }
    
    public function add_schema_markup() {
        if (!is_singular('parfume')) {
            return;
        }
        
        global $post;
        
        if (!$post || !is_object($post)) {
            return;
        }
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $brands = wp_get_post_terms($post->ID, 'marki', ['fields' => 'names']);
        
        if (is_wp_error($brands)) {
            $brands = [];
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
        ];
        
        if (!empty($brands) && is_array($brands)) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $brands[0],
            ];
        }
        
        if (!empty($rating) && is_numeric($rating)) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => floatval($rating),
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => '1',
            ];
        }
        
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($thumbnail_url) {
                $schema['image'] = $thumbnail_url;
            }
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}