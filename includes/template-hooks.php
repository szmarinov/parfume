<?php
/**
 * Template Hooks - WordPress hooks за template система
 * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена поддръжка за season archive
 * 
 * Файл: includes/template-hooks.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom post types to main query on home and archive pages
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query()) {
        if (is_home()) {
            $post_types = $query->get('post_type');
            if (empty($post_types)) {
                $post_types = array('post');
            }
            if (is_array($post_types)) {
                $post_types[] = 'parfume_blog';
            } else {
                $post_types = array($post_types, 'parfume_blog');
            }
            $query->set('post_type', $post_types);
        }
    }
});

/**
 * Add Schema.org structured data for parfume pages
 */
add_action('wp_head', function() {
    if (is_singular('parfume')) {
        global $post;
        
        $schema = array(
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(strip_tags(get_the_content()), 30)
        );
        
        // Добавяме рейтинг ако има
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        if ($rating) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'bestRating' => '5',
                'ratingCount' => '1'
            );
        }
        
        // Добавяме марка ако има
        $brands = get_the_terms($post->ID, 'marki');
        if ($brands && !is_wp_error($brands)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brands[0]->name
            );
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
});

/**
 * Add custom body classes for parfume pages
 * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена поддръжка за season archive
 */
add_filter('body_class', function($classes) {
    global $wp_query;
    
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
            
            // НОВО - СПЕЦИАЛНО за season таксономия
            if ($queried_object->taxonomy === 'season') {
                $classes[] = 'single-season-page';
            }
        }
    }
    
    // НОВО - Проверяваме за season archive
    if (isset($wp_query->query_vars['season_archive']) || 
        (isset($wp_query->query_vars['parfume_taxonomy_archive']) && 
         $wp_query->query_vars['parfume_taxonomy_archive'] === 'season')) {
        $classes[] = 'season-archive-page';
        $classes[] = 'parfume-taxonomy-archive';
    }
    
    // Проверяваме за perfumer archive
    if (isset($wp_query->query_vars['perfumer_archive']) || 
        isset($wp_query->query_vars['is_perfumer_archive'])) {
        $classes[] = 'perfumer-archive-page';
        $classes[] = 'parfume-taxonomy-archive';
    }
    
    // Общ клас за други taxonomy archives
    if (isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
        $taxonomy = $wp_query->query_vars['parfume_taxonomy_archive'];
        $classes[] = $taxonomy . '-archive-page';
        $classes[] = 'parfume-taxonomy-archive';
    }
    
    return $classes;
});

/**
 * Enqueue styles and scripts for parfume pages
 * АКТУАЛИЗИРАНА ВЕРСИЯ - добавена поддръжка за season archive
 */
add_action('wp_enqueue_scripts', function() {
    global $wp_query;
    
    if (parfume_reviews_is_parfume_page() || 
        isset($wp_query->query_vars['season_archive']) ||
        isset($wp_query->query_vars['perfumer_archive']) ||
        isset($wp_query->query_vars['parfume_taxonomy_archive'])) {
        
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
        
        // Single perfumer specific CSS
        if (is_tax('perfumer') || 
            (is_tax() && get_queried_object() && get_queried_object()->taxonomy === 'perfumer') ||
            (isset($wp_query->query_vars['perfumer_archive'])) ||
            in_array('single-perfumer-page', get_body_class())) {
            
            wp_enqueue_style(
                'parfume-reviews-single-perfumer',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-perfumer.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            // DEBUG лог
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('PERFUMER CSS: Loading single-perfumer.css for URL: ' . $_SERVER['REQUEST_URI']);
            }
        }
        
        // НОВО - Season archive CSS (използва стиловете от frontend.css)
        if (isset($wp_query->query_vars['season_archive']) ||
            (isset($wp_query->query_vars['parfume_taxonomy_archive']) && 
             $wp_query->query_vars['parfume_taxonomy_archive'] === 'season') ||
            in_array('season-archive-page', get_body_class())) {
            
            // DEBUG лог
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SEASON ARCHIVE: Detected season archive page for URL: ' . $_SERVER['REQUEST_URI']);
                error_log('SEASON ARCHIVE: Body classes: ' . implode(', ', get_body_class()));
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
 * FALLBACK HOOK - Зарежда CSS за perfumer страници ако горният не работи
 * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
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
            PARFUME_REVIEWS_VERSION
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PERFUMER CSS FALLBACK: Loading CSS via fallback hook');
        }
    }
}, 15);

/**
 * НОВО - Debug hook за season archive
 */
add_action('wp_footer', function() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    global $wp_query;
    
    if (isset($wp_query->query_vars['season_archive']) ||
        (isset($wp_query->query_vars['parfume_taxonomy_archive']) && 
         $wp_query->query_vars['parfume_taxonomy_archive'] === 'season')) {
        
        echo '<!-- Season Archive Debug Info -->';
        echo '<script>';
        echo 'console.log("Season Archive Detected");';
        echo 'console.log("Query vars:", ' . wp_json_encode($wp_query->query_vars) . ');';
        echo 'console.log("Body classes:", ' . wp_json_encode(get_body_class()) . ');';
        echo '</script>';
    }
}, 999);

/**
 * Add pagination support for taxonomy archives
 * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query()) {
        if (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
            $query->set('posts_per_page', 12);
        }
    }
});

/**
 * Custom excerpt length for parfume posts
 * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
 */
add_filter('excerpt_length', function($length) {
    if (is_singular('parfume') || is_post_type_archive('parfume') || 
        is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        return 30;
    }
    return $length;
});

/**
 * Custom excerpt more text
 * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
 */
add_filter('excerpt_more', function($more) {
    if (is_singular('parfume') || is_post_type_archive('parfume') || 
        is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        return '...';
    }
    return $more;
});

/**
 * Remove default WordPress meta boxes from parfume edit screen
 * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
 */
add_action('add_meta_boxes', function() {
    remove_meta_box('commentsdiv', 'parfume', 'normal');
    remove_meta_box('trackbacksdiv', 'parfume', 'normal');
    remove_meta_box('postcustom', 'parfume', 'normal');
    remove_meta_box('commentstatusdiv', 'parfume', 'normal');
    remove_meta_box('slugdiv', 'parfume', 'normal');
});

/**
 * Modify the document title for parfume pages
 * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
 */
add_filter('wp_title', function($title, $sep) {
    if (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        $queried_object = get_queried_object();
        if ($queried_object) {
            $title = $queried_object->name . ' ' . $sep . ' ' . get_bloginfo('name');
        }
    }
    return $title;
}, 10, 2);

/**
 * Add custom meta description for SEO
 * ЗАПАЗЕНА ОРИГИНАЛНА ФУНКЦИОНАЛНОСТ
 */
add_action('wp_head', function() {
    if (is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        $queried_object = get_queried_object();
        if ($queried_object && $queried_object->description) {
            echo '<meta name="description" content="' . esc_attr(wp_trim_words($queried_object->description, 25)) . '">' . "\n";
        }
    }
});