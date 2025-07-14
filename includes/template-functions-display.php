<?php
/**
 * Template Functions - Display Functions
 * Функции за показване на парфюмни карточки и UI елементи
 * 
 * Файл: includes/template-functions-display.php
 * ПОПРАВЕНА ВЕРСИЯ С ЛИПСВАЩАТА ФУНКЦИЯ parfume_reviews_get_collections_dropdown()
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Показва карточка на парфюм
 */
function parfume_reviews_display_parfume_card($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'parfume') {
        return;
    }
    
    $settings = get_option('parfume_reviews_settings', array());
    
    // Get meta data
    $price = get_post_meta($post_id, '_price', true);
    $brand = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
    $brand_name = !empty($brand) ? $brand[0] : '';
    $rating = get_post_meta($post_id, '_rating', true);
    $featured_image = get_the_post_thumbnail_url($post_id, 'medium');
    
    // Get gender terms
    $gender_terms = wp_get_post_terms($post_id, 'gender', array('fields' => 'names'));
    $gender_class = !empty($gender_terms) ? sanitize_html_class(strtolower($gender_terms[0])) : '';
    
    ?>
    <article class="parfume-card <?php echo esc_attr($gender_class); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
        <div class="parfume-card-image">
            <?php if ($featured_image): ?>
                <a href="<?php echo get_permalink($post_id); ?>">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy">
                </a>
            <?php else: ?>
                <div class="parfume-card-placeholder">
                    <span class="dashicons dashicons-format-image"></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($rating)): ?>
                <div class="parfume-card-rating">
                    <?php parfume_reviews_display_star_rating(floatval($rating)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="parfume-card-content">
            <div class="parfume-card-header">
                <?php if (!empty($brand_name)): ?>
                    <div class="parfume-card-brand">
                        <a href="<?php echo esc_url(get_term_link($brand_name, 'marki')); ?>">
                            <?php echo esc_html($brand_name); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <h3 class="parfume-card-title">
                    <a href="<?php echo get_permalink($post_id); ?>">
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    </a>
                </h3>
            </div>
            
            <div class="parfume-card-meta">
                <?php if (!empty($price)): ?>
                    <div class="parfume-card-price">
                        <?php echo parfume_reviews_get_formatted_price($price); ?>
                    </div>
                <?php endif; ?>
                
                <div class="parfume-card-actions">
                    <?php if (!empty($settings['enable_comparison'])): ?>
                        <?php echo parfume_reviews_get_comparison_button($post_id); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['enable_collections']) && is_user_logged_in()): ?>
                        <?php echo parfume_reviews_get_collections_dropdown($post_id); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </article>
    <?php
}

/**
 * Показва звездичен рейтинг
 */
function parfume_reviews_display_star_rating($rating, $max_rating = 5, $show_number = false) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max_rating - $full_stars - ($half_star ? 1 : 0);
    
    ?>
    <div class="star-rating" data-rating="<?php echo esc_attr($rating); ?>">
        <div class="stars">
            <?php
            // Full stars
            for ($i = 0; $i < $full_stars; $i++) {
                echo '<span class="star star-full">★</span>';
            }
            
            // Half star
            if ($half_star) {
                echo '<span class="star star-half">★</span>';
            }
            
            // Empty stars
            for ($i = 0; $i < $empty_stars; $i++) {
                echo '<span class="star star-empty">☆</span>';
            }
            ?>
        </div>
        
        <?php if ($show_number): ?>
            <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * ЛИПСВАЩАТА ФУНКЦИЯ - Връща HTML за звездния рейтинг (за template файлове)
 * Тази функция се използва в templates/archive-perfumer.php
 */
function parfume_reviews_get_rating_stars($rating, $max_rating = 5) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max_rating - $full_stars - ($half_star ? 1 : 0);
    
    $output = '<div class="star-rating" data-rating="' . esc_attr($rating) . '">';
    $output .= '<div class="stars">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<span class="star star-full">★</span>';
    }
    
    // Half star
    if ($half_star) {
        $output .= '<span class="star star-half">★</span>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<span class="star star-empty">☆</span>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * ЛИПСВАЩАТА ФУНКЦИЯ - Показва звезди за рейтинг без wrapper div
 * Използва се за backward compatibility
 */
function parfume_reviews_display_stars($rating, $max_rating = 5) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max_rating - $full_stars - ($half_star ? 1 : 0);
    
    echo '<div class="star-rating">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        echo '<span class="star star-full">★</span>';
    }
    
    // Half star
    if ($half_star) {
        echo '<span class="star star-half">★</span>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        echo '<span class="star star-empty">☆</span>';
    }
    
    echo '</div>';
}

/**
 * Показва списък с парфюми в grid формат
 */
function parfume_reviews_display_parfumes_grid($query_args = array(), $columns = 3) {
    $default_args = array(
        'post_type' => 'parfume',
        'posts_per_page' => 12,
        'post_status' => 'publish'
    );
    
    $query_args = wp_parse_args($query_args, $default_args);
    $parfumes_query = new WP_Query($query_args);
    
    if (!$parfumes_query->have_posts()) {
        ?>
        <div class="no-parfumes-message">
            <p><?php _e('Няма намерени парфюми за показване.', 'parfume-reviews'); ?></p>
        </div>
        <?php
        return;
    }
    
    $grid_class = 'parfumes-grid parfumes-grid-' . intval($columns);
    ?>
    <div class="<?php echo esc_attr($grid_class); ?>">
        <?php while ($parfumes_query->have_posts()): $parfumes_query->the_post(); ?>
            <?php parfume_reviews_display_parfume_card(get_the_ID()); ?>
        <?php endwhile; ?>
    </div>
    
    <?php
    // Pagination
    if ($parfumes_query->max_num_pages > 1) {
        parfume_reviews_display_pagination($parfumes_query);
    }
    
    wp_reset_postdata();
}

/**
 * Показва pagination
 */
function parfume_reviews_display_pagination($query = null) {
    if (!$query) {
        global $wp_query;
        $query = $wp_query;
    }
    
    if ($query->max_num_pages <= 1) {
        return;
    }
    
    $current_page = max(1, get_query_var('paged'));
    
    ?>
    <nav class="parfume-pagination" role="navigation">
        <?php
        echo paginate_links(array(
            'total' => $query->max_num_pages,
            'current' => $current_page,
            'prev_text' => '‹ ' . __('Предишна', 'parfume-reviews'),
            'next_text' => __('Следваща', 'parfume-reviews') . ' ›',
            'type' => 'list',
            'end_size' => 2,
            'mid_size' => 2
        ));
        ?>
    </nav>
    <?php
}

/**
 * Показва breadcrumb навигация
 */
function parfume_reviews_display_breadcrumb() {
    $breadcrumbs = array();
    $settings = get_option('parfume_reviews_settings', array());
    $home_text = !empty($settings['breadcrumb_home']) ? $settings['breadcrumb_home'] : __('Начало', 'parfume-reviews');
    
    // Добавяме начало
    $breadcrumbs[] = array(
        'url' => home_url('/'),
        'title' => $home_text
    );
    
    // Парфюми архив
    $breadcrumbs[] = array(
        'url' => get_post_type_archive_link('parfume'),
        'title' => __('Парфюми', 'parfume-reviews')
    );
    
    // Текуща страница
    if (is_singular('parfume')) {
        $breadcrumbs[] = array(
            'url' => '',
            'title' => get_the_title()
        );
    } elseif (is_tax()) {
        $term = get_queried_object();
        $breadcrumbs[] = array(
            'url' => '',
            'title' => $term->name
        );
    }
    
    if (empty($breadcrumbs)) {
        return;
    }
    
    ?>
    <nav class="parfume-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'parfume-reviews'); ?>">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <li class="breadcrumb-item">
                    <?php if (!empty($crumb['url'])): ?>
                        <a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['title']); ?></a>
                    <?php else: ?>
                        <span class="current"><?php echo esc_html($crumb['title']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <span class="separator">/</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

/**
 * Показва header на архивна страница
 */
function parfume_reviews_display_archive_header() {
    if (is_post_type_archive('parfume')) {
        ?>
        <div class="archive-header parfume-archive-header">
            <h1 class="archive-title"><?php _e('Всички парфюми', 'parfume-reviews'); ?></h1>
            <div class="archive-description">
                <p><?php _e('Открийте вашия идеален парфюм от нашата колекция', 'parfume-reviews'); ?></p>
            </div>
        </div>
        <?php
    } elseif (is_tax()) {
        $term = get_queried_object();
        $taxonomy = get_taxonomy($term->taxonomy);
        ?>
        <div class="archive-header taxonomy-archive-header">
            <h1 class="archive-title"><?php echo esc_html($term->name); ?></h1>
            
            <?php if (!empty($term->description)): ?>
                <div class="archive-description">
                    <?php echo wpautop(esc_html($term->description)); ?>
                </div>
            <?php endif; ?>
            
            <div class="archive-meta">
                <span class="taxonomy-label"><?php echo esc_html($taxonomy->labels->singular_name); ?>:</span>
                <span class="items-count">
                    <?php printf(_n('%d парфюм', '%d парфюма', $term->count, 'parfume-reviews'), $term->count); ?>
                </span>
            </div>
        </div>
        <?php
    }
}

/**
 * Показва изображение на term (таксономия)
 */
function parfume_reviews_display_term_image($term_id, $taxonomy, $size = 'medium', $attr = array()) {
    $image_id = get_term_meta($term_id, $taxonomy . '-image-id', true);
    
    if (!$image_id) {
        return false;
    }
    
    $default_attr = array(
        'class' => 'term-image term-image-' . $taxonomy,
        'loading' => 'lazy'
    );
    
    $attr = wp_parse_args($attr, $default_attr);
    
    return wp_get_attachment_image($image_id, $size, false, $attr);
}

/**
 * ЛИПСВАЩА ФУНКЦИЯ - Показва бутон за добавяне в сравнение
 * Използва се в templates/taxonomy-marki.php на ред 241
 */
function parfume_reviews_get_comparison_button($post_id, $return = true) {
    $settings = get_option('parfume_reviews_settings', array());
    
    // Проверяваме дали comparison е включен
    if (empty($settings['enable_comparison'])) {
        return '';
    }
    
    $button_html = sprintf(
        '<button type="button" class="add-to-comparison" data-post-id="%d" data-post-title="%s">
            <span class="dashicons dashicons-plus-alt2"></span>
            %s
        </button>',
        intval($post_id),
        esc_attr(get_the_title($post_id)),
        esc_html__('Сравни', 'parfume-reviews')
    );
    
    if ($return) {
        return $button_html;
    } else {
        echo $button_html;
    }
}

/**
 * ЛИПСВАЩА ФУНКЦИЯ - Collections dropdown
 * КЛЮЧОВА ФУНКЦИЯ КОЯТО ЛИПСВАШЕ И ПРИЧИНЯВАШЕ FATAL ERROR!
 * Използва се в templates/taxonomy-marki.php на ред 242
 */
function parfume_reviews_get_collections_dropdown($post_id) {
    // Проверяваме дали класът Collections съществува
    if (!class_exists('Parfume_Reviews\\Collections')) {
        return '';
    }
    
    // Проверяваме дали потребителят е логнат
    if (!is_user_logged_in()) {
        return '';
    }
    
    // Проверяваме дали collections са активирани
    $settings = get_option('parfume_reviews_settings', array());
    if (empty($settings['enable_collections'])) {
        return '';
    }
    
    // Използваме статичния метод от Collections класа
    return \Parfume_Reviews\Collections::get_collections_dropdown($post_id);
}

/**
 * Показва loading индикатор
 */
function parfume_reviews_display_loading_indicator($text = '') {
    if (empty($text)) {
        $text = __('Зареждане...', 'parfume-reviews');
    }
    
    ?>
    <div class="parfume-loading-indicator">
        <div class="loading-spinner"></div>
        <span class="loading-text"><?php echo esc_html($text); ?></span>
    </div>
    <?php
}

/**
 * ДОБАВЕНА ФУНКЦИЯ ЗА ОБНОВЯВАНЕ НА СПИСЪКА С AVAILABLE FUNCTIONS
 * Актуализираме списъка в template-functions.php
 */
function parfume_reviews_update_available_template_functions() {
    // Тази функция ще бъде използвана за актуализиране на списъка в main файла
    // Добавяме parfume_reviews_get_collections_dropdown в display функциите
    return array(
        'utils' => array(
            'parfume_reviews_is_parfume_page',
            'parfume_reviews_is_parfume_archive',
            'parfume_reviews_is_single_parfume',
            'parfume_reviews_is_parfume_taxonomy',
            'parfume_reviews_get_supported_taxonomies',
            'parfume_reviews_is_supported_taxonomy',
            'parfume_reviews_get_taxonomy_label',
            'parfume_reviews_get_taxonomy_archive_url',
            'parfume_reviews_get_formatted_price',
            'parfume_reviews_get_rating',
            'parfume_reviews_get_parfume_stores',
            'parfume_reviews_get_lowest_price',
            'parfume_reviews_get_popular_parfumes',
            'parfume_reviews_get_latest_parfumes',
            'parfume_reviews_get_random_parfumes',
            'parfume_reviews_get_similar_parfumes',
            'parfume_reviews_get_parfume_stats',
            'parfume_reviews_clear_stats_cache',
            'parfume_reviews_sanitize_rating',
            'parfume_reviews_sanitize_price',
            'parfume_reviews_user_can_edit_reviews',
            'parfume_reviews_user_can_manage_plugin',
            'parfume_reviews_get_first_image_from_content',
            'parfume_reviews_format_longevity',
            'parfume_reviews_extract_price_number',
            'parfume_reviews_is_available',
            'parfume_reviews_get_shipping_info',
            'parfume_reviews_get_cheapest_shipping',
            'parfume_reviews_has_promotion'
        ),
        'display' => array(
            'parfume_reviews_display_parfume_card',
            'parfume_reviews_display_star_rating',
            'parfume_reviews_get_rating_stars',
            'parfume_reviews_display_stars',
            'parfume_reviews_display_parfumes_grid',
            'parfume_reviews_display_pagination',
            'parfume_reviews_display_breadcrumb',
            'parfume_reviews_display_archive_header',
            'parfume_reviews_display_term_image',
            'parfume_reviews_display_loading_indicator',
            'parfume_reviews_get_comparison_button',
            'parfume_reviews_get_collections_dropdown' // ДОБАВЕНА ЛИПСВАЩАТА ФУНКЦИЯ!
        ),
        'filters' => array(
            'parfume_reviews_get_active_filters',
            'parfume_reviews_build_filter_url',
            'parfume_reviews_get_remove_filter_url',
            'parfume_reviews_get_add_filter_url',
            'parfume_reviews_is_filter_active',
            'parfume_reviews_display_active_filters',
            'parfume_reviews_display_filter_form',
            'parfume_reviews_display_sort_options'
        )
    );
}