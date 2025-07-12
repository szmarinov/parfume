<?php
/**
 * Template hooks for Parfume Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta tags for single parfume pages
 */
add_action('wp_head', function() {
    if (is_singular('parfume')) {
        global $post;
        
        // Get parfume metadata
        $description = get_post_meta($post->ID, '_parfume_description', true);
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        $notes = wp_get_post_terms($post->ID, 'notes', array('fields' => 'names'));
        $brand = wp_get_post_terms($post->ID, 'marki', array('fields' => 'names'));
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        
        // Add basic meta tags
        if (!empty($description)) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        // Add Open Graph tags
        echo '<meta property="og:title" content="' . esc_attr(get_the_title($post->ID)) . '">' . "\n";
        echo '<meta property="og:type" content="product">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '">' . "\n";
        
        if (!empty($description)) {
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
            if ($thumbnail_url) {
                echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '">' . "\n";
            }
        }
        
        // Add structured data (JSON-LD) for SEO
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'url' => get_permalink($post->ID),
        );
        
        if (!empty($description)) {
            $schema['description'] = $description;
        }
        
        if (!empty($brand) && is_array($brand)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => sanitize_text_field($brand[0]),
            );
        }
        
        // Add rating if exists
        if (!empty($rating)) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => sanitize_text_field($rating),
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => '1',
            );
        }
        
        // Add image if exists
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($thumbnail_url) {
                $schema['image'] = $thumbnail_url;
            }
        }
        
        // Add additional properties if they exist
        if (!empty($gender)) {
            $schema['additionalProperty'] = array(
                '@type' => 'PropertyValue',
                'name' => 'gender',
                'value' => sanitize_text_field($gender),
            );
        }
        
        if (!empty($release_year)) {
            $schema['releaseDate'] = sanitize_text_field($release_year);
        }
        
        if (!empty($notes) && is_array($notes)) {
            $schema['keywords'] = implode(', ', array_map('sanitize_text_field', $notes));
        }
        
        // Use wp_json_encode for better compatibility
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
        }
    }
    
    return $classes;
});

/**
 * Enqueue styles and scripts for parfume pages
 * ОБНОВЕНО - ДОБАВЕН FILTERS.CSS!
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
        
        // Filters CSS - НОВ ФАЙЛ!
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
        
        // Localization
        wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume-reviews-nonce'),
            'strings' => array(
                'loading' => __('Зареждане...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
                'success' => __('Успех', 'parfume-reviews'),
            ),
        ));
        
        wp_localize_script('parfume-comparison', 'parfumeComparison', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume-comparison-nonce'),
            'maxItems' => 4,
            'addedText' => __('Added to comparison', 'parfume-reviews'),
            'addText' => __('Add to comparison', 'parfume-reviews'),
            'removeText' => __('Remove', 'parfume-reviews'),
            'compareText' => __('Compare', 'parfume-reviews'),
            'emptyText' => __('No items to compare', 'parfume-reviews'),
            'alreadyAddedText' => __('Already added to comparison', 'parfume-reviews'),
        ));
    }
});

/**
 * Add custom hooks for theme integration
 */

// Hook before main content on archive pages
if (!has_action('parfume_reviews_before_archive_content')) {
    add_action('parfume_reviews_before_archive_content', function() {
        // Placeholder for themes to hook into
    });
}

// Hook after main content on archive pages
if (!has_action('parfume_reviews_after_archive_content')) {
    add_action('parfume_reviews_after_archive_content', function() {
        // Placeholder for themes to hook into
    });
}

// Hook before single parfume content
if (!has_action('parfume_reviews_before_single_content')) {
    add_action('parfume_reviews_before_single_content', function() {
        // Placeholder for themes to hook into
    });
}

// Hook after single parfume content
if (!has_action('parfume_reviews_after_single_content')) {
    add_action('parfume_reviews_after_single_content', function() {
        // Placeholder for themes to hook into
    });
}

/**
 * Custom navigation for parfume archives
 */
add_action('parfume_reviews_archive_navigation', function() {
    if (is_post_type_archive('parfume') || is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => __('&laquo; Previous', 'parfume-reviews'),
            'next_text' => __('Next &raquo;', 'parfume-reviews'),
            'before_page_number' => '<span class="screen-reader-text">' . __('Page', 'parfume-reviews') . ' </span>',
        ));
    }
});

/**
 * Add breadcrumbs for parfume pages
 */
add_action('parfume_reviews_breadcrumbs', function() {
    if (!is_singular('parfume') && !is_post_type_archive('parfume') && !is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        return;
    }
    
    $breadcrumbs = array();
    $breadcrumbs[] = '<a href="' . home_url() . '">' . __('Home', 'parfume-reviews') . '</a>';
    
    if (is_singular('parfume')) {
        $breadcrumbs[] = '<a href="' . get_post_type_archive_link('parfume') . '">' . __('Parfumes', 'parfume-reviews') . '</a>';
        $breadcrumbs[] = '<span>' . get_the_title() . '</span>';
    } elseif (is_post_type_archive('parfume')) {
        $breadcrumbs[] = '<span>' . __('Parfumes', 'parfume-reviews') . '</span>';
    } elseif (is_tax()) {
        $queried_object = get_queried_object();
        $breadcrumbs[] = '<a href="' . get_post_type_archive_link('parfume') . '">' . __('Parfumes', 'parfume-reviews') . '</a>';
        
        if ($queried_object && isset($queried_object->name)) {
            $breadcrumbs[] = '<span>' . esc_html($queried_object->name) . '</span>';
        }
    }
    
    if (!empty($breadcrumbs)) {
        echo '<nav class="parfume-breadcrumbs" aria-label="' . esc_attr__('Breadcrumbs', 'parfume-reviews') . '">';
        echo '<ol class="breadcrumb-list">';
        foreach ($breadcrumbs as $crumb) {
            echo '<li class="breadcrumb-item">' . $crumb . '</li>';
        }
        echo '</ol>';
        echo '</nav>';
    }
});

/**
 * Add search form for parfume archives
 */
add_action('parfume_reviews_search_form', function() {
    if (is_post_type_archive('parfume') || is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        ?>
        <form role="search" method="get" class="parfume-search-form" action="<?php echo esc_url(home_url('/')); ?>">
            <label class="screen-reader-text" for="parfume-search"><?php _e('Search for:', 'parfume-reviews'); ?></label>
            <input type="search" id="parfume-search" class="search-field" placeholder="<?php esc_attr_e('Search parfumes...', 'parfume-reviews'); ?>" value="<?php echo get_search_query(); ?>" name="s" />
            <input type="hidden" name="post_type" value="parfume" />
            <button type="submit" class="search-submit">
                <span class="screen-reader-text"><?php _e('Search', 'parfume-reviews'); ?></span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </form>
        <?php
    }
});

/**
 * Add comparison widget to footer (if not already present)
 */
add_action('wp_footer', function() {
    if (parfume_reviews_is_parfume_page()) {
        ?>
        <div class="comparison-widget" style="display: none;">
            <span class="widget-icon">⚖️</span>
            <span class="widget-text"><?php _e('Comparison', 'parfume-reviews'); ?></span>
            <span class="widget-count">0</span>
            <button class="widget-button"><?php _e('View', 'parfume-reviews'); ?></button>
        </div>
        <?php
    }
});