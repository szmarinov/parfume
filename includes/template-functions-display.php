<?php
/**
 * Template Functions - Display Functions
 * Функции за показване на парфюмни карточки и UI елементи
 * 
 * Файл: includes/template-functions-display.php
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
                        <a href="<?php echo get_term_link($brand_name, 'marki'); ?>">
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
                <?php if (!empty($gender_terms)): ?>
                    <div class="parfume-card-gender">
                        <?php foreach ($gender_terms as $gender): ?>
                            <span class="gender-tag"><?php echo esc_html($gender); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($price)): ?>
                    <div class="parfume-card-price">
                        <span class="price-amount"><?php echo esc_html($price); ?></span>
                        <span class="price-currency">лв.</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="parfume-card-excerpt">
                <?php echo wp_trim_words(get_the_excerpt($post_id), 15, '...'); ?>
            </div>
            
            <div class="parfume-card-actions">
                <a href="<?php echo get_permalink($post_id); ?>" class="parfume-card-button">
                    <?php _e('Виж повече', 'parfume-reviews'); ?>
                </a>
                
                <?php if (!empty($settings['enable_comparison']) && $settings['enable_comparison']): ?>
                    <button class="add-to-comparison" data-post-id="<?php echo esc_attr($post_id); ?>">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php _e('Сравни', 'parfume-reviews'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

/**
 * Показва звездна оценка
 */
function parfume_reviews_display_star_rating($rating, $show_number = true) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
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
            'end_size' => 1,
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
    if (is_front_page()) {
        return;
    }
    
    $breadcrumbs = array();
    $breadcrumbs[] = array(
        'title' => __('Начало', 'parfume-reviews'),
        'url' => home_url('/')
    );
    
    if (is_singular('parfume')) {
        $breadcrumbs[] = array(
            'title' => __('Парфюми', 'parfume-reviews'),
            'url' => get_post_type_archive_link('parfume')
        );
        $breadcrumbs[] = array(
            'title' => get_the_title(),
            'url' => ''
        );
    } elseif (is_post_type_archive('parfume')) {
        $breadcrumbs[] = array(
            'title' => __('Парфюми', 'parfume-reviews'),
            'url' => ''
        );
    } elseif (is_tax()) {
        $queried_object = get_queried_object();
        $breadcrumbs[] = array(
            'title' => __('Парфюми', 'parfume-reviews'),
            'url' => get_post_type_archive_link('parfume')
        );
        
        if ($queried_object->taxonomy === 'perfumer') {
            $breadcrumbs[] = array(
                'title' => __('Парфюмеристи', 'parfume-reviews'),
                'url' => home_url('/parfiumi/parfumeri/')
            );
        }
        
        $breadcrumbs[] = array(
            'title' => $queried_object->name,
            'url' => ''
        );
    }
    
    if (empty($breadcrumbs)) {
        return;
    }
    
    ?>
    <nav class="parfume-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'parfume-reviews'); ?>">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                <li class="breadcrumb-item <?php echo $index === count($breadcrumbs) - 1 ? 'current' : ''; ?>">
                    <?php if (!empty($breadcrumb['url'])): ?>
                        <a href="<?php echo esc_url($breadcrumb['url']); ?>">
                            <?php echo esc_html($breadcrumb['title']); ?>
                        </a>
                    <?php else: ?>
                        <span><?php echo esc_html($breadcrumb['title']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <span class="separator"> › </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

/**
 * Показва архивна страница header
 */
function parfume_reviews_display_archive_header($title = '', $description = '') {
    if (empty($title)) {
        if (is_post_type_archive('parfume')) {
            $title = __('Всички Парфюми', 'parfume-reviews');
        } elseif (is_tax()) {
            $queried_object = get_queried_object();
            $title = $queried_object->name;
        }
    }
    
    ?>
    <header class="archive-header">
        <div class="container">
            <?php parfume_reviews_display_breadcrumb(); ?>
            
            <?php if (!empty($title)): ?>
                <h1 class="archive-title"><?php echo esc_html($title); ?></h1>
            <?php endif; ?>
            
            <?php if (!empty($description)): ?>
                <div class="archive-description">
                    <?php echo wpautop($description); ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <?php
}

/**
 * Показва term изображение
 */
function parfume_reviews_display_term_image($term_id, $taxonomy, $size = 'thumbnail', $attr = array()) {
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