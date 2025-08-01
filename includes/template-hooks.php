<?php
/**
 * Template Hooks - закачалки за template-ите
 * РЕВИЗИРАНА ВЕРСИЯ: Пълна функционалност с всички hooks и filters
 * 
 * Файл: includes/template-hooks.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CONTENT DISPLAY HOOKS
 * Hooks за показване на съдържание в различните секции
 */

/**
 * Display parfume rating in header
 */
add_action('parfume_header_after_title', function() {
    if (is_singular('parfume')) {
        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
        if (!empty($rating)) {
            echo '<div class="parfume-rating">';
            if (function_exists('parfume_reviews_display_rating')) {
                echo parfume_reviews_display_rating($rating);
            } else {
                // Fallback rating display
                echo '<div class="rating-stars">';
                for ($i = 1; $i <= 5; $i++) {
                    $class = $i <= $rating ? 'filled' : 'empty';
                    echo '<span class="star ' . $class . '">★</span>';
                }
                echo '</div>';
            }
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
        $year = get_post_meta(get_the_ID(), 'parfume_year', true);
        $concentration = get_post_meta(get_the_ID(), 'parfume_concentration', true);
        
        echo '<div class="parfume-meta-info">';
        
        if (!empty($brands)) {
            echo '<span class="parfume-brand">';
            echo '<strong>' . __('Марка:', 'parfume-reviews') . '</strong> ';
            $brand_links = array();
            foreach ($brands as $brand) {
                $brand_links[] = '<a href="' . get_term_link($brand) . '">' . esc_html($brand->name) . '</a>';
            }
            echo implode(', ', $brand_links);
            echo '</span>';
        }
        
        if (!empty($perfumers)) {
            echo '<span class="parfume-perfumer">';
            echo '<strong>' . __('Парфюмерист:', 'parfume-reviews') . '</strong> ';
            $perfumer_links = array();
            foreach ($perfumers as $perfumer) {
                $perfumer_links[] = '<a href="' . get_term_link($perfumer) . '">' . esc_html($perfumer->name) . '</a>';
            }
            echo implode(', ', $perfumer_links);
            echo '</span>';
        }
        
        if (!empty($year)) {
            echo '<span class="parfume-year">';
            echo '<strong>' . __('Година:', 'parfume-reviews') . '</strong> ' . esc_html($year);
            echo '</span>';
        }
        
        if (!empty($concentration)) {
            echo '<span class="parfume-concentration">';
            echo '<strong>' . __('Концентрация:', 'parfume-reviews') . '</strong> ' . esc_html($concentration);
            echo '</span>';
        }
        
        echo '</div>';
    }
});

/**
 * Display parfume stores information
 */
add_action('parfume_stores_display', function() {
    if (is_singular('parfume')) {
        $stores = get_post_meta(get_the_ID(), 'parfume_stores', true);
        
        if (!empty($stores) && is_array($stores)) {
            echo '<div class="parfume-stores">';
            echo '<h3>' . __('Къде да купите', 'parfume-reviews') . '</h3>';
            echo '<div class="stores-list">';
            
            foreach ($stores as $store) {
                if (!empty($store['name']) && !empty($store['url'])) {
                    echo '<div class="store-item">';
                    echo '<div class="store-name">' . esc_html($store['name']) . '</div>';
                    
                    if (!empty($store['price'])) {
                        echo '<div class="store-price">' . esc_html($store['price']) . '</div>';
                    }
                    
                    $url = !empty($store['affiliate_url']) ? $store['affiliate_url'] : $store['url'];
                    echo '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow" class="store-link">';
                    echo __('Виж в магазина', 'parfume-reviews');
                    echo '</a>';
                    
                    if (!empty($store['promo_code'])) {
                        echo '<div class="promo-code">';
                        echo __('Промо код:', 'parfume-reviews') . ' <code>' . esc_html($store['promo_code']) . '</code>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
            }
            
            echo '</div>';
            echo '</div>';
        }
    }
});

/**
 * Display comparison button
 */
add_action('parfume_comparison_button', function() {
    if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
        $post_id = get_the_ID();
        if ($post_id) {
            echo '<div class="parfume-comparison-button">';
            echo '<button type="button" class="add-to-comparison" data-parfume-id="' . $post_id . '">';
            echo __('Добави за сравняване', 'parfume-reviews');
            echo '</button>';
            echo '</div>';
        }
    }
});

/**
 * BODY CLASSES FILTER
 * Добавя custom CSS класове към body
 */
add_filter('body_class', function($classes) {
    if (is_singular('parfume')) {
        $classes[] = 'single-parfume-page';
        
        // Добавяме клас за оценката
        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
        if (!empty($rating)) {
            $classes[] = 'has-rating';
            $classes[] = 'rating-' . round($rating);
        }
        
        // Добавяме клас за пол
        $gender_terms = wp_get_post_terms(get_the_ID(), 'gender');
        if (!empty($gender_terms)) {
            foreach ($gender_terms as $gender) {
                $classes[] = 'gender-' . $gender->slug;
            }
        }
        
    } elseif (is_singular('parfume_blog')) {
        $classes[] = 'single-parfume-blog-page';
        
    } elseif (is_post_type_archive('parfume')) {
        $classes[] = 'parfume-archive-page';
        
    } elseif (is_post_type_archive('parfume_blog')) {
        $classes[] = 'parfume-blog-archive-page';
        
    } elseif (is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
        $classes[] = 'parfume-taxonomy-page';
        
        $queried_object = get_queried_object();
        if ($queried_object && isset($queried_object->taxonomy)) {
            $classes[] = 'parfume-taxonomy-' . $queried_object->taxonomy;
            
            // Специални класове за различни таксономии
            switch ($queried_object->taxonomy) {
                case 'perfumer':
                    $classes[] = 'single-perfumer-page';
                    break;
                case 'marki':
                    $classes[] = 'brand-archive-page';
                    break;
                case 'notes':
                    $classes[] = 'notes-archive-page';
                    break;
                case 'gender':
                    $classes[] = 'gender-archive-page';
                    break;
            }
            
            // Добавяме слъг на термина като клас
            if (isset($queried_object->slug)) {
                $classes[] = 'term-' . $queried_object->slug;
            }
        }
    }
    
    // Добавяме клас ако има активни филтри
    if (function_exists('parfume_reviews_get_active_filters')) {
        $active_filters = parfume_reviews_get_active_filters();
        if (!empty($active_filters)) {
            $classes[] = 'has-active-filters';
            $classes[] = 'filter-count-' . count($active_filters);
        }
    }
    
    return $classes;
});

/**
 * ASSETS ENQUEUE HOOKS
 * Зарежда CSS и JavaScript файлове
 */
add_action('wp_enqueue_scripts', function() {
    // Проверяваме дали сме на парфюмна страница
    if (!function_exists('parfume_reviews_is_parfume_page') || !parfume_reviews_is_parfume_page()) {
        return;
    }
    
    $plugin_version = defined('PARFUME_REVIEWS_VERSION') ? PARFUME_REVIEWS_VERSION : '1.0.0';
    
    // Main frontend CSS
    $frontend_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/frontend.css';
    if (file_exists($frontend_css)) {
        wp_enqueue_style(
            'parfume-reviews-frontend',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $plugin_version
        );
    }
    
    // Filters CSS
    $filters_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/filters.css';
    if (file_exists($filters_css)) {
        wp_enqueue_style(
            'parfume-reviews-filters',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/filters.css',
            array('parfume-reviews-frontend'),
            $plugin_version
        );
    }
    
    // Comparison CSS
    $comparison_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/comparison.css';
    if (file_exists($comparison_css)) {
        wp_enqueue_style(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comparison.css',
            array('parfume-reviews-frontend'),
            $plugin_version
        );
    }
    
    // Single parfume specific CSS
    if (is_singular('parfume')) {
        $single_parfume_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/single-parfume.css';
        if (file_exists($single_parfume_css)) {
            wp_enqueue_style(
                'parfume-reviews-single-parfume',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                array('parfume-reviews-frontend'),
                $plugin_version
            );
        }
    }
    
    // Single perfumer specific CSS - ПОПРАВЕНО
    if (is_tax('perfumer') || 
        (is_tax() && get_queried_object() && get_queried_object()->taxonomy === 'perfumer') ||
        in_array('single-perfumer-page', get_body_class())) {
        
        $single_perfumer_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/single-perfumer.css';
        if (file_exists($single_perfumer_css)) {
            wp_enqueue_style(
                'parfume-reviews-single-perfumer',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-perfumer.css',
                array('parfume-reviews-frontend'),
                $plugin_version
            );
            
            // Debug лог
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('PERFUMER CSS: Loading single-perfumer.css for URL: ' . $_SERVER['REQUEST_URI']);
            }
        }
    }
    
    // Column2 CSS за stores и mobile panel
    $column2_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/single-parfume.css'; // Column2 стиловете са в single-parfume.css
    if (file_exists($column2_css) && (is_singular('parfume') || is_post_type_archive('parfume'))) {
        // Вече се зарежда в single parfume секцията
    }
    
    // JavaScript files
    $frontend_js = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/frontend.js';
    if (file_exists($frontend_js)) {
        wp_enqueue_script(
            'parfume-reviews-frontend',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $plugin_version,
            true
        );
    }
    
    $filters_js = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/filters.js';
    if (file_exists($filters_js)) {
        wp_enqueue_script(
            'parfume-reviews-filters',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/filters.js',
            array('jquery'),
            $plugin_version,
            true
        );
    }
    
    $comparison_js = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/comparison.js';
    if (file_exists($comparison_js)) {
        wp_enqueue_script(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
            array('jquery'),
            $plugin_version,
            true
        );
    }
    
    // Column2 JavaScript за stores functionality
    $column2_js = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/column2.js';
    if (file_exists($column2_js) && is_singular('parfume')) {
        wp_enqueue_script(
            'parfume-reviews-column2',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/column2.js',
            array('jquery'),
            $plugin_version,
            true
        );
    }
    
    // Localize script data
    wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('parfume_reviews_nonce'),
        'currentPostId' => get_the_ID(),
        'isParfumePage' => is_singular('parfume'),
        'strings' => array(
            'addToComparison' => __('Добави за сравняване', 'parfume-reviews'),
            'removeFromComparison' => __('Премахни от сравняването', 'parfume-reviews'),
            'comparisonLimit' => __('Можете да сравнявате максимум 4 парфюма', 'parfume-reviews'),
            'noResults' => __('Няма намерени резултати', 'parfume-reviews'),
            'loading' => __('Зареждане...', 'parfume-reviews'),
            'error' => __('Възникна грешка', 'parfume-reviews'),
            'copied' => __('Копирано!', 'parfume-reviews'),
            'copyPromoCode' => __('Копирай промо код', 'parfume-reviews')
        )
    ));
});

/**
 * Admin scripts енqueue
 */
add_action('admin_enqueue_scripts', function($hook) {
    // Зареждаме само на parfume edit страници
    if (!in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
        return;
    }
    
    global $post_type;
    if ($post_type !== 'parfume') {
        return;
    }
    
    $plugin_version = defined('PARFUME_REVIEWS_VERSION') ? PARFUME_REVIEWS_VERSION : '1.0.0';
    
    // Admin CSS
    $admin_css = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/admin.css';
    if (file_exists($admin_css)) {
        wp_enqueue_style(
            'parfume-reviews-admin',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $plugin_version
        );
    }
    
    // Admin JavaScript
    $admin_js = PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/admin.js';
    if (file_exists($admin_js)) {
        wp_enqueue_script(
            'parfume-reviews-admin',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            $plugin_version,
            true
        );
        
        // Localize за admin
        wp_localize_script('parfume-reviews-admin', 'parfumeReviewsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_reviews_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Сигурни ли сте, че искате да изтриете този елемент?', 'parfume-reviews'),
                'saved' => __('Записано!', 'parfume-reviews'),
                'error' => __('Възникна грешка при записването', 'parfume-reviews')
            )
        ));
    }
    
    // WordPress media upload
    wp_enqueue_media();
});

/**
 * SEO AND META HOOKS
 * Hooks за SEO оптимизация и meta тагове
 */

/**
 * Add structured data for parfume
 */
add_action('wp_head', function() {
    if (is_singular('parfume')) {
        $post_id = get_the_ID();
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        $brands = wp_get_post_terms($post_id, 'marki');
        $perfumers = wp_get_post_terms($post_id, 'perfumer');
        $price = get_post_meta($post_id, 'parfume_price', true);
        $year = get_post_meta($post_id, 'parfume_year', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30),
            'url' => get_permalink(),
            'category' => 'Parfume'
        );
        
        // Добавяме изображение
        if (has_post_thumbnail()) {
            $schema['image'] = get_the_post_thumbnail_url($post_id, 'large');
        }
        
        // Добавяме марка
        if (!empty($brands)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brands[0]->name
            );
        }
        
        // Добавяме създател (парфюмерист)
        if (!empty($perfumers)) {
            $schema['creator'] = array(
                '@type' => 'Person',
                'name' => $perfumers[0]->name
            );
        }
        
        // Добавяме рейтинг
        if (!empty($rating)) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => '1'
            );
        }
        
        // Добавяме цена
        if (!empty($price)) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'BGN',
                'availability' => 'https://schema.org/InStock'
            );
        }
        
        // Добавяме година на издаване
        if (!empty($year)) {
            $schema['dateCreated'] = $year;
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
});

/**
 * Add Open Graph meta tags
 */
add_action('wp_head', function() {
    if (is_singular('parfume') || is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))) {
        $title = '';
        $description = '';
        $image = '';
        $url = '';
        
        if (is_singular('parfume')) {
            $title = get_the_title();
            $description = get_the_excerpt() ?: wp_trim_words(get_the_content(), 30);
            $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
            $url = get_permalink();
            
            // Добавяме марка към заглавието
            $brands = wp_get_post_terms(get_the_ID(), 'marki');
            if (!empty($brands)) {
                $title = $brands[0]->name . ' ' . $title;
            }
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            $title = $term->name;
            $description = $term->description ?: sprintf(__('Разгледайте всички парфюми в категорията %s', 'parfume-reviews'), $term->name);
            $url = get_term_link($term);
            
            // Търсим изображение за термина
            $taxonomy = $term->taxonomy;
            $image_id = get_term_meta($term->term_id, $taxonomy . '_image_id', true);
            if ($image_id) {
                $image = wp_get_attachment_image_url($image_id, 'large');
            }
        }
        
        // Output Open Graph tags
        if ($title) {
            echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        }
        
        if ($description) {
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        }
        
        if ($url) {
            echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        }
        
        echo '<meta property="og:type" content="product">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    }
});

/**
 * QUERY MODIFICATION HOOKS
 * Hooks за модификация на WordPress queries
 */

/**
 * Модифицира main query за parfume archives
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query()) {
        
        // За parfume archive страници
        if (is_post_type_archive('parfume') || $query->is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            // Задаваме брой постове на страница
            $posts_per_page = get_option('parfume_reviews_posts_per_page', 12);
            $query->set('posts_per_page', $posts_per_page);
            
            // Применяваме филтри от URL параметрите
            if (function_exists('parfume_reviews_apply_query_filters')) {
                parfume_reviews_apply_query_filters($query);
            }
        }
    }
});

/**
 * CLEANUP AND MAINTENANCE HOOKS
 * Hooks за почистване и maintenance
 */

/**
 * Почиства кешове когато се запазва парфюм
 */
add_action('save_post_parfume', function($post_id) {
    // Изчистваме кешове
    if (function_exists('parfume_reviews_clear_template_caches')) {
        parfume_reviews_clear_template_caches();
    }
    
    // Флъшваме object cache за този парфюм
    wp_cache_delete($post_id, 'parfume_reviews_parfume_data');
    
    // Флъшваме статистически кешове
    wp_cache_delete('parfume_reviews_stats', 'parfume_reviews');
});

/**
 * Почиства кешове когато се променя таксономия
 */
add_action('edit_term', function($term_id, $tt_id, $taxonomy) {
    $supported_taxonomies = array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
    
    if (in_array($taxonomy, $supported_taxonomies)) {
        // Изчистваме кешове за тази таксономия
        wp_cache_delete("taxonomy_{$taxonomy}_{$term_id}", 'parfume_reviews');
        wp_cache_delete('parfume_reviews_stats', 'parfume_reviews');
        
        if (function_exists('parfume_reviews_clear_template_caches')) {
            parfume_reviews_clear_template_caches();
        }
    }
}, 10, 3);

/**
 * DEBUG HOOKS
 * Hooks за debugging и development
 */

/**
 * Debug информация за CSS зареждане
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_footer', function() {
        if (is_tax('perfumer') || in_array('single-perfumer-page', get_body_class())) {
            echo '<!-- DEBUG INFO: ';
            echo 'URL: ' . $_SERVER['REQUEST_URI'] . ', ';
            echo 'Taxonomy: ' . (get_queried_object() ? get_queried_object()->taxonomy : 'none') . ', ';
            echo 'Body Classes: ' . implode(' ', get_body_class()) . ', ';
            echo 'Is Tax Perfumer: ' . (is_tax('perfumer') ? 'YES' : 'NO');
            echo ' -->';
        }
    });
}

/**
 * FILTER HOOKS
 * Различни filter hooks за модификация на данни
 */

/**
 * Модифицира excerpt length за парфюми
 */
add_filter('excerpt_length', function($length) {
    if (is_singular('parfume') || is_post_type_archive('parfume')) {
        return 25;
    }
    return $length;
});

/**
 * Модифицира excerpt more text за парфюми
 */
add_filter('excerpt_more', function($more) {
    if (is_singular('parfume') || is_post_type_archive('parfume')) {
        return '...';
    }
    return $more;
});

/**
 * Добавя canonical URL за parfume страници
 */
add_action('wp_head', function() {
    if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
        $canonical_url = '';
        
        if (is_singular('parfume')) {
            $canonical_url = get_permalink();
        } elseif (is_post_type_archive('parfume')) {
            $canonical_url = get_post_type_archive_link('parfume');
        } elseif (is_tax()) {
            $canonical_url = get_term_link(get_queried_object());
        }
        
        if ($canonical_url) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
        }
    }
});

/**
 * THEME COMPATIBILITY HOOKS
 * Hooks за съвместимост с различни теми
 */

/**
 * Wrapper функция за проверка на parfume страници
 */
if (!function_exists('parfume_reviews_is_parfume_page')) {
    function parfume_reviews_is_parfume_page() {
        return is_singular('parfume') || 
               is_singular('parfume_blog') || 
               is_post_type_archive('parfume') || 
               is_post_type_archive('parfume_blog') || 
               is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
    }
}

/**
 * Custom do_action hooks за теми
 */

// Hook за header секция
add_action('parfume_before_content', function() {
    // Placeholder за theme compatibility
});

// Hook за footer секция  
add_action('parfume_after_content', function() {
    // Placeholder за theme compatibility
});

// Hook за sidebar
add_action('parfume_sidebar', function() {
    if (is_active_sidebar('parfume-sidebar')) {
        dynamic_sidebar('parfume-sidebar');
    }
});

/**
 * ACCESSIBILITY HOOKS
 * Hooks за accessibility подобрения
 */

/**
 * Добавя aria labels към navigation елементи
 */
add_filter('wp_nav_menu_args', function($args) {
    if (parfume_reviews_is_parfume_page()) {
        if (!isset($args['menu_class'])) {
            $args['menu_class'] = '';
        }
        $args['menu_class'] .= ' parfume-navigation';
        
        if (!isset($args['container_aria_label'])) {
            $args['container_aria_label'] = __('Навигация за парфюми', 'parfume-reviews');
        }
    }
    
    return $args;
});

/**
 * PERFORMANCE OPTIMIZATION HOOKS
 * Hooks за оптимизация на производителността
 */

/**
 * Предзарежда критични CSS файлове
 */
add_action('wp_head', function() {
    if (parfume_reviews_is_parfume_page()) {
        $critical_css = array(
            'frontend.css',
            'filters.css'
        );
        
        foreach ($critical_css as $css_file) {
            $css_path = PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/' . $css_file;
            echo '<link rel="preload" href="' . esc_url($css_path) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
        }
    }
}, 1);

/**
 * Добавя resource hints
 */
add_action('wp_head', function() {
    if (parfume_reviews_is_parfume_page()) {
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="//fonts.gstatic.com" crossorigin>' . "\n";
    }
}, 1);