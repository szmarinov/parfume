<?php
/**
 * Template Functions - Display Functions
 * Функции за показване на парфюмни карточки и UI елементи
 * 
 * ФАЙЛ: includes/template-functions-display.php
 * ПОПРАВЕНА ВЕРСИЯ - Добавена липсваща функция parfume_reviews_get_collections_dropdown()
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
    $gender_class = !empty($gender_terms) ? 'gender-' . sanitize_title($gender_terms[0]) : 'gender-unisex';
    
    ?>
    <div class="parfume-card <?php echo esc_attr($gender_class); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
        <?php if ($featured_image): ?>
            <div class="parfume-image">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy">
                </a>
            </div>
        <?php endif; ?>
        
        <div class="parfume-info">
            <?php if ($brand_name): ?>
                <div class="parfume-brand"><?php echo esc_html($brand_name); ?></div>
            <?php endif; ?>
            
            <h3 class="parfume-title">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <?php echo esc_html(get_the_title($post_id)); ?>
                </a>
            </h3>
            
            <?php if ($rating): ?>
                <div class="parfume-rating">
                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                    <span class="rating-value"><?php echo esc_html(number_format($rating, 1)); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($price): ?>
                <div class="parfume-price">
                    <?php echo esc_html(number_format($price, 2)); ?> <?php _e('лв.', 'parfume-reviews'); ?>
                </div>
            <?php endif; ?>
            
            <div class="parfume-actions">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="button view-details">
                    <?php _e('Детайли', 'parfume-reviews'); ?>
                </a>
                <?php echo parfume_reviews_get_comparison_button($post_id); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Показва звезден рейтинг
 */
function parfume_reviews_display_star_rating($rating, $show_number = true) {
    if (empty($rating)) {
        return;
    }
    
    ?>
    <div class="star-rating" data-rating="<?php echo esc_attr($rating); ?>">
        <?php echo parfume_reviews_get_rating_stars($rating); ?>
        <?php if ($show_number): ?>
            <span class="rating-number"><?php echo esc_html(number_format($rating, 1)); ?></span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Получава HTML за звезди на рейтинг
 */
function parfume_reviews_get_rating_stars($rating) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - ceil($rating);
    
    $html = '<div class="stars">';
    
    // Пълни звезди
    for ($i = 0; $i < $full_stars; $i++) {
        $html .= '<span class="star full">★</span>';
    }
    
    // Половин звезда
    if ($half_star) {
        $html .= '<span class="star half">★</span>';
    }
    
    // Празни звезди
    for ($i = 0; $i < $empty_stars; $i++) {
        $html .= '<span class="star empty">☆</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Показва звезди (shorthand функция)
 */
function parfume_reviews_display_stars($rating) {
    return parfume_reviews_get_rating_stars($rating);
}

/**
 * Показва grid от парфюми
 */
function parfume_reviews_display_parfumes_grid($query, $columns = 4) {
    if (!$query->have_posts()) {
        echo '<p class="no-parfumes">' . __('Не са намерени парфюми.', 'parfume-reviews') . '</p>';
        return;
    }
    
    echo '<div class="parfumes-grid columns-' . esc_attr($columns) . '">';
    
    while ($query->have_posts()) {
        $query->the_post();
        parfume_reviews_display_parfume_card(get_the_ID());
    }
    
    echo '</div>';
    
    wp_reset_postdata();
}

/**
 * Показва pagination
 */
function parfume_reviews_display_pagination($query = null) {
    global $wp_query;
    
    if ($query === null) {
        $query = $wp_query;
    }
    
    if ($query->max_num_pages <= 1) {
        return;
    }
    
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
    
    echo '<nav class="parfume-pagination">';
    
    echo paginate_links(array(
        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'format' => '?paged=%#%',
        'current' => max(1, $paged),
        'total' => $query->max_num_pages,
        'prev_text' => __('« Предишна', 'parfume-reviews'),
        'next_text' => __('Следваща »', 'parfume-reviews'),
        'type' => 'list',
        'mid_size' => 2
    ));
    
    echo '</nav>';
}

/**
 * Показва breadcrumb навигация
 */
function parfume_reviews_display_breadcrumb() {
    if (!parfume_reviews_is_parfume_page()) {
        return;
    }
    
    $breadcrumb_items = array();
    
    // Home
    $breadcrumb_items[] = array(
        'title' => __('Начало', 'parfume-reviews'),
        'url' => home_url('/')
    );
    
    // Parfumes archive
    $breadcrumb_items[] = array(
        'title' => __('Парфюми', 'parfume-reviews'),
        'url' => get_post_type_archive_link('parfume')
    );
    
    // Current page
    if (is_singular('parfume')) {
        $breadcrumb_items[] = array(
            'title' => get_the_title(),
            'url' => ''
        );
    } elseif (is_tax()) {
        $term = get_queried_object();
        $breadcrumb_items[] = array(
            'title' => $term->name,
            'url' => ''
        );
    }
    
    ?>
    <nav class="parfume-breadcrumb" aria-label="breadcrumb">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumb_items as $index => $item): ?>
                <li class="breadcrumb-item <?php echo empty($item['url']) ? 'active' : ''; ?>">
                    <?php if (!empty($item['url'])): ?>
                        <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                    <?php else: ?>
                        <?php echo esc_html($item['title']); ?>
                    <?php endif; ?>
                </li>
                <?php if ($index < count($breadcrumb_items) - 1): ?>
                    <li class="breadcrumb-separator" aria-hidden="true">/</li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

/**
 * Показва архивен header
 */
function parfume_reviews_display_archive_header() {
    if (!is_post_type_archive('parfume') && !is_tax()) {
        return;
    }
    
    ?>
    <header class="archive-header">
        <h1 class="archive-title">
            <?php
            if (is_post_type_archive('parfume')) {
                echo __('Всички парфюми', 'parfume-reviews');
            } elseif (is_tax()) {
                $term = get_queried_object();
                echo esc_html($term->name);
            }
            ?>
        </h1>
        
        <?php if (is_tax() && !empty(get_queried_object()->description)): ?>
            <div class="archive-description">
                <?php echo wpautop(get_queried_object()->description); ?>
            </div>
        <?php endif; ?>
    </header>
    <?php
}

/**
 * Показва изображение на term
 */
function parfume_reviews_display_term_image($term_id, $taxonomy, $size = 'thumbnail') {
    $image_id = get_term_meta($term_id, $taxonomy . '-image-id', true);
    
    if (!$image_id) {
        return;
    }
    
    $image_url = wp_get_attachment_image_url($image_id, $size);
    
    if (!$image_url) {
        return;
    }
    
    ?>
    <div class="term-image">
        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_term($term_id)->name); ?>" loading="lazy">
    </div>
    <?php
}

/**
 * Показва loading индикатор
 */
function parfume_reviews_display_loading_indicator() {
    ?>
    <div class="parfume-loading-indicator">
        <div class="spinner"></div>
        <p><?php _e('Зареждане...', 'parfume-reviews'); ?></p>
    </div>
    <?php
}

/**
 * Получава бутон за сравнение
 */
function parfume_reviews_get_comparison_button($post_id) {
    $comparison_items = isset($_COOKIE['parfume_comparison']) ? json_decode(stripslashes($_COOKIE['parfume_comparison']), true) : array();
    $is_in_comparison = in_array($post_id, (array)$comparison_items);
    
    $button_class = $is_in_comparison ? 'remove-from-comparison' : 'add-to-comparison';
    $button_text = $is_in_comparison ? __('Премахни от сравнение', 'parfume-reviews') : __('Добави за сравнение', 'parfume-reviews');
    
    return sprintf(
        '<button class="button comparison-button %s" data-post-id="%d">%s</button>',
        esc_attr($button_class),
        esc_attr($post_id),
        esc_html($button_text)
    );
}

/**
 * НОВА ФУНКЦИЯ: Показва dropdown за collections/колекции
 * Тази функция липсваше и предизвикваше Fatal Error
 */
function parfume_reviews_get_collections_dropdown($args = array()) {
    $defaults = array(
        'show_option_all' => __('Всички колекции', 'parfume-reviews'),
        'taxonomy' => 'parfume_collection', // Може да бъде custom taxonomy за collections
        'name' => 'collection',
        'selected' => isset($_GET['collection']) ? sanitize_text_field($_GET['collection']) : '',
        'class' => 'parfume-collection-dropdown',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Проверяваме дали таксономията съществува
    if (!taxonomy_exists($args['taxonomy'])) {
        // Ако не съществува, използваме 'marki' (brands) като fallback
        $args['taxonomy'] = 'marki';
    }
    
    // Получаваме terms
    $terms = get_terms(array(
        'taxonomy' => $args['taxonomy'],
        'hide_empty' => $args['hide_empty'],
        'orderby' => $args['orderby'],
        'order' => $args['order']
    ));
    
    if (empty($terms) || is_wp_error($terms)) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="parfume-collections-filter">
        <label for="<?php echo esc_attr($args['name']); ?>">
            <?php _e('Филтър по колекция:', 'parfume-reviews'); ?>
        </label>
        <select name="<?php echo esc_attr($args['name']); ?>" id="<?php echo esc_attr($args['name']); ?>" class="<?php echo esc_attr($args['class']); ?>">
            <?php if (!empty($args['show_option_all'])): ?>
                <option value=""><?php echo esc_html($args['show_option_all']); ?></option>
            <?php endif; ?>
            
            <?php foreach ($terms as $term): ?>
                <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($args['selected'], $term->slug); ?>>
                    <?php echo esc_html($term->name); ?>
                    <?php if (!$args['hide_empty']): ?>
                        (<?php echo intval($term->count); ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Показва collections dropdown (helper функция)
 */
function parfume_reviews_display_collections_dropdown($args = array()) {
    echo parfume_reviews_get_collections_dropdown($args);
}