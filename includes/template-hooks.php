<?php
/**
 * Template Hooks - закачалки за template-ите
 * ПОПРАВЕНА ВЕРСИЯ - правилно зареждане на single-perfumer.css
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display parfume rating in header
 */
add_action('parfume_header_after_title', function() {
    if (is_singular('parfume')) {
        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
        if (!empty($rating)) {
            echo '<div class="parfume-rating">';
            echo parfume_reviews_display_rating($rating);
            echo '</div>';
        }
    }
});

/**
 * Display parfume meta info
 */
add_action('parfume_meta_display', function() {
    if (is_singular('parfume')) {
        $perfumers = wp_get_post_terms(get_the_ID(), 'perfumer');
        $brands = wp_get_post_terms(get_the_ID(), 'marki');
        
        if (!empty($brands)) {
            echo '<span class="parfume-brand">';
            foreach ($brands as $brand) {
                echo '<a href="' . get_term_link($brand) . '">' . esc_html($brand->name) . '</a> ';
            }
            echo '</span>';
        }
        
        if (!empty($perfumers)) {
            echo '<span class="parfume-perfumer">';
            echo __('от ', 'parfume-reviews');
            foreach ($perfumers as $perfumer) {
                echo '<a href="' . get_term_link($perfumer) . '">' . esc_html($perfumer->name) . '</a> ';
            }
            echo '</span>';
        }
    }
});

/**
 * Add structured data for parfume
 */
add_action('wp_head', function() {
    if (is_singular('parfume')) {
        $post_id = get_the_ID();
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        $brands = wp_get_post_terms($post_id, 'marki');
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30),
            'url' => get_permalink(),
        );
        
        if (!empty($brands)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brands[0]->name
            );
        }
        
        if (!empty($rating)) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => '1'
            );
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
});

/**
 * Add custom body classes for parfume pages
 */
add_filter('body_class', function($classes) {
    if (is_singular('parfume')) {
        $classes[] = 'single-parfume-page';
    } elseif (is_post_type_archive('parfume')) {
        $classes[] = 'parfume-archive-page';
    } elseif (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        $classes[] = 'parfume-taxonomy-page';
        
        $queried_object = get_queried_object();
        if ($queried_object && isset($queried_object->taxonomy)) {
            $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            
            // СПЕЦИАЛНО за perfumer таксономия
            if ($queried_object->taxonomy === 'perfumer') {
                $classes[] = 'single-perfumer-page';
            }
        }
    }
    
    return $classes;
});

/**
 * Enqueue styles and scripts for parfume pages
 * ПОПРАВЕНА ВЕРСИЯ - ПРАВИЛНО ЗАРЕЖДАНЕ НА SINGLE-PERFUMER.CSS!
 */
add_action('wp_enqueue_scripts', function() {
    if (parfume_reviews_is_parfume_page()) {
        
        // Main frontend CSS
        wp_enqueue_style(
            'parfume-reviews-frontend',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        // Filters CSS
        wp_enqueue_style(
            'parfume-reviews-filters',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/filters.css',
            array('parfume-reviews-frontend'),
            PARFUME_REVIEWS_VERSION
        );
        
        // Comparison CSS
        wp_enqueue_style(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comparison.css',
            array('parfume-reviews-frontend'),
            PARFUME_REVIEWS_VERSION
        );
        
        // Single parfume specific CSS
        if (is_singular('parfume')) {
            wp_enqueue_style(
                'parfume-reviews-single',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
        }
        
        // ПОПРАВЕНО! Single perfumer specific CSS - РАЗШИРЕНО УСЛОВИЕ
        if (is_tax('perfumer') || 
            (is_tax() && get_queried_object() && get_queried_object()->taxonomy === 'perfumer') ||
            (isset($GLOBALS['wp_query']) && $GLOBALS['wp_query']->is_tax('perfumer')) ||
            in_array('single-perfumer-page', get_body_class())) {
            
            wp_enqueue_style(
                'parfume-reviews-single-perfumer',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-perfumer.css',
                array('parfume-reviews-frontend'),
                time() // Force refresh за тестване
            );
            
            // DEBUG лог
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('PERFUMER CSS: Loading single-perfumer.css for URL: ' . $_SERVER['REQUEST_URI']);
            }
        }
        
        // JavaScript files
        wp_enqueue_script(
            'parfume-reviews-frontend',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_enqueue_script(
            'parfume-reviews-filters',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/filters.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_enqueue_script(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Localize script data
        wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_reviews_nonce'),
            'strings' => array(
                'addToComparison' => __('Добави за сравняване', 'parfume-reviews'),
                'removeFromComparison' => __('Премахни от сравняването', 'parfume-reviews'),
                'comparisonLimit' => __('Можете да сравнявате максимум 4 парфюма', 'parfume-reviews'),
                'noResults' => __('Няма намерени резултати', 'parfume-reviews'),
                'loading' => __('Зареждане...', 'parfume-reviews')
            )
        ));
    }
});

/**
 * АЛТЕРНАТИВЕН HOOK - Ако горният не работи
 * Зарежда CSS директно за perfumer страници
 */
add_action('wp_enqueue_scripts', function() {
    // Проверяваме дали сме на perfumer страница по различен начин
    if (is_tax('perfumer') || 
        (function_exists('get_queried_object') && 
         get_queried_object() && 
         isset(get_queried_object()->taxonomy) && 
         get_queried_object()->taxonomy === 'perfumer')) {
        
        wp_enqueue_style(
            'parfume-reviews-single-perfumer-fallback',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-perfumer.css',
            array(),
            time() // Force refresh
        );
        
        // DEBUG съобщение
        add_action('wp_footer', function() {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<!-- PERFUMER CSS: Fallback CSS loaded -->';
            }
        });
    }
}, 20); // По-висок приоритет

/**
 * Add noindex to comparison pages for SEO
 */
add_action('wp_head', function() {
    if (is_page() && get_query_var('comparison')) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    }
});

/**
 * Remove WordPress version from head for security
 */
add_filter('the_generator', '__return_empty_string');

/**
 * Optimize images for parfume pages
 */
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
    if (parfume_reviews_is_parfume_page()) {
        $attr['loading'] = 'lazy';
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }
    }
    return $attr;
}, 10, 3);

/**
 * Add custom CSS variables for theming
 */
add_action('wp_head', function() {
    if (parfume_reviews_is_parfume_page()) {
        echo '<style>
        :root {
            --parfume-primary-color: #667eea;
            --parfume-secondary-color: #764ba2;
            --parfume-accent-color: #f093fb;
            --parfume-text-color: #333;
            --parfume-light-bg: #f8f9fa;
            --parfume-border-color: #e1e5e9;
            --parfume-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --parfume-border-radius: 8px;
        }
        </style>';
    }
}, 5);

/**
 * Improve excerpt for parfume posts
 */
add_filter('get_the_excerpt', function($excerpt, $post) {
    if ($post && $post->post_type === 'parfume' && empty($excerpt)) {
        // Generate excerpt from content if not set
        $content = wp_strip_all_tags($post->post_content);
        $excerpt = wp_trim_words($content, 30, '...');
    }
    return $excerpt;
}, 10, 2);

/**
 * Add Open Graph meta tags for better social sharing
 */
add_action('wp_head', function() {
    if (is_singular('parfume') || parfume_reviews_is_parfume_taxonomy()) {
        $title = wp_get_document_title();
        $description = '';
        $image = '';
        
        if (is_singular('parfume')) {
            $description = get_the_excerpt() ?: wp_trim_words(get_the_content(), 30);
            $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
        } elseif (is_tax()) {
            $term = get_queried_object();
            $description = $term->description ?: sprintf(__('Разгледайте всички парфюми в категорията %s', 'parfume-reviews'), $term->name);
            $taxonomy = $term->taxonomy;
            $image_id = get_term_meta($term->term_id, $taxonomy . '-image-id', true);
            if ($image_id) {
                $image = wp_get_attachment_image_url($image_id, 'large');
            }
        }
        
        if ($description) {
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        }
    }
});

/**
 * DEBUG ФУНКЦИЯ - Показва кои CSS файлове се зареждат
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_footer', function() {
        if (is_tax('perfumer')) {
            echo '<!-- DEBUG INFO: ';
            echo 'URL: ' . $_SERVER['REQUEST_URI'] . ', ';
            echo 'Taxonomy: ' . (get_queried_object() ? get_queried_object()->taxonomy : 'none') . ', ';
            echo 'Classes: ' . implode(' ', get_body_class());
            echo ' -->';
        }
    });
}