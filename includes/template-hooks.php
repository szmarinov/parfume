<?php
/**
 * Template hooks for Parfume Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add hooks only if we're not in admin and functions don't already exist
if (!is_admin()) {
    add_action('wp_head', 'parfume_reviews_add_schema_markup');
    add_action('wp_footer', 'parfume_reviews_track_recently_viewed');
}

/**
 * Add schema.org markup for perfume products
 */
if (!function_exists('parfume_reviews_add_schema_markup')) {
    function parfume_reviews_add_schema_markup() {
        if (!is_singular('parfume')) {
            return;
        }
        
        global $post;
        
        // Check if $post exists and is valid
        if (!$post || !is_object($post) || !isset($post->ID)) {
            return;
        }
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $brands = wp_get_post_terms($post->ID, 'marki', array('fields' => 'names'));
        $notes = wp_get_post_terms($post->ID, 'notes', array('fields' => 'names'));
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        
        // Handle WP_Error for taxonomy terms
        if (is_wp_error($brands)) {
            $brands = array();
        }
        if (is_wp_error($notes)) {
            $notes = array();
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
        );
        
        // Only add brand if we have valid brands
        if (!empty($brands) && is_array($brands)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brands[0],
            );
        }
        
        // Only add rating if it's valid
        if (!empty($rating) && is_numeric($rating)) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => floatval($rating),
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
}

/**
 * Track recently viewed parfumes
 */
if (!function_exists('parfume_reviews_track_recently_viewed')) {
    function parfume_reviews_track_recently_viewed() {
        if (!is_singular('parfume')) {
            return;
        }
        
        global $post;
        
        if (!$post || !isset($post->ID)) {
            return;
        }
        
        ?>
        <script>
        (function() {
            var parfumeId = <?php echo intval($post->ID); ?>;
            var recentlyViewed = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
            
            // Remove if already exists
            recentlyViewed = recentlyViewed.filter(function(id) {
                return id !== parfumeId;
            });
            
            // Add to beginning
            recentlyViewed.unshift(parfumeId);
            
            // Keep only last 10
            if (recentlyViewed.length > 10) {
                recentlyViewed = recentlyViewed.slice(0, 10);
            }
            
            // Save back to localStorage
            try {
                localStorage.setItem('parfume_recently_viewed', JSON.stringify(recentlyViewed));
            } catch (e) {
                // Ignore localStorage errors
            }
        })();
        </script>
        <?php
    }
}

/**
 * Add comparison functionality hooks
 */
add_action('wp_footer', function() {
    if (parfume_reviews_is_parfume_page()) {
        echo '<div id="comparison-sidebar" style="display: none;"></div>';
        echo parfume_reviews_get_comparison_link();
    }
});

/**
 * Add breadcrumbs to parfume pages
 */
add_action('parfume_reviews_before_content', function() {
    if (parfume_reviews_is_parfume_page()) {
        echo parfume_reviews_get_breadcrumbs();
    }
});

/**
 * Add meta tags for SEO
 */
add_action('wp_head', function() {
    if (is_singular('parfume')) {
        global $post;
        
        if (!$post) return;
        
        $description = wp_strip_all_tags(get_the_excerpt($post->ID));
        if (empty($description)) {
            $description = wp_trim_words(wp_strip_all_tags($post->post_content), 30);
        }
        
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
 * Enqueue comparison scripts and styles
 */
add_action('wp_enqueue_scripts', function() {
    if (parfume_reviews_is_parfume_page()) {
        wp_enqueue_style(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comparison.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        wp_enqueue_script(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_localize_script('parfume-comparison', 'parfumeComparison', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume-comparison-nonce'),
            'maxItems' => 4,
            'addedText' => __('Added to comparison', 'parfume-reviews'),
            'addText' => __('Add to comparison', 'parfume-reviews'),
            'removeText' => __('Remove', 'parfume-reviews'),
            'compareText' => __('Compare', 'parfume-reviews'),
            'emptyText' => __('No items to compare', 'parfume-reviews'),
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
            'screen_reader_text' => __('Parfumes navigation', 'parfume-reviews'),
        ));
    }
});

/**
 * Add filters sidebar to archive pages
 */
add_action('parfume_reviews_archive_sidebar', function() {
    if (is_post_type_archive('parfume') || is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        $settings = get_option('parfume_reviews_settings', array());
        
        if (!empty($settings['show_archive_sidebar'])) {
            // This can be implemented as a widget area or custom filters
            dynamic_sidebar('parfume-archive-sidebar');
        }
    }
});

/**
 * Recently viewed parfumes hook
 */
add_action('parfume_reviews_recently_viewed', function($limit = 5) {
    ?>
    <div id="recently-viewed-parfumes" class="recently-viewed-section">
        <h3><?php _e('Recently Viewed', 'parfume-reviews'); ?></h3>
        <div class="recently-viewed-container" data-limit="<?php echo intval($limit); ?>">
            <!-- Content will be populated via JavaScript -->
        </div>
    </div>
    
    <script>
    (function($) {
        $(document).ready(function() {
            var recentlyViewed = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
            var limit = parseInt($('.recently-viewed-container').data('limit')) || 5;
            var container = $('.recently-viewed-container');
            
            if (recentlyViewed.length === 0) {
                $('#recently-viewed-parfumes').hide();
                return;
            }
            
            // Take only the requested number of items
            var itemsToShow = recentlyViewed.slice(0, limit);
            
            // AJAX call to get perfume data
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'get_recently_viewed_parfumes',
                    parfume_ids: itemsToShow,
                    nonce: '<?php echo wp_create_nonce('parfume-recently-viewed'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        container.html(response.data);
                    } else {
                        $('#recently-viewed-parfumes').hide();
                    }
                },
                error: function() {
                    $('#recently-viewed-parfumes').hide();
                }
            });
        });
    })(jQuery);
    </script>
    <?php
});

/**
 * AJAX handler for recently viewed parfumes
 */
add_action('wp_ajax_get_recently_viewed_parfumes', 'parfume_reviews_ajax_get_recently_viewed');
add_action('wp_ajax_nopriv_get_recently_viewed_parfumes', 'parfume_reviews_ajax_get_recently_viewed');

function parfume_reviews_ajax_get_recently_viewed() {
    check_ajax_referer('parfume-recently-viewed', 'nonce');
    
    if (empty($_POST['parfume_ids']) || !is_array($_POST['parfume_ids'])) {
        wp_send_json_error('Invalid parfume IDs');
    }
    
    $parfume_ids = array_map('intval', $_POST['parfume_ids']);
    $parfume_ids = array_filter($parfume_ids);
    
    if (empty($parfume_ids)) {
        wp_send_json_error('No valid parfume IDs');
    }
    
    $query = new WP_Query(array(
        'post_type' => 'parfume',
        'post__in' => $parfume_ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($parfume_ids),
    ));
    
    if (!$query->have_posts()) {
        wp_send_json_error('No parfumes found');
    }
    
    ob_start();
    ?>
    <div class="recently-viewed-grid">
        <?php while ($query->have_posts()): $query->the_post(); ?>
            <div class="recently-viewed-item">
                <a href="<?php the_permalink(); ?>" class="recently-viewed-link">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="recently-viewed-image">
                            <?php the_post_thumbnail('thumbnail'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="recently-viewed-content">
                        <h4 class="recently-viewed-title"><?php the_title(); ?></h4>
                        <?php 
                        $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                        if (!empty($brands) && !is_wp_error($brands)): 
                        ?>
                            <span class="recently-viewed-brand"><?php echo esc_html($brands[0]); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    
    $html = ob_get_clean();
    wp_send_json_success($html);
}