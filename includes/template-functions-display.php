<?php
/**
 * Template Functions - Display Functions
 * Функции за показване на парфюмни карточки и UI елементи
 * 
 * Файл: includes/template-functions-display.php
 * РЕВИЗИРАНА ВЕРСИЯ - ПЪЛЕН НАБОР ОТ DISPLAY ФУНКЦИИ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * РАЗДЕЛ 1: ОСНОВНИ DISPLAY ФУНКЦИИ
 */

/**
 * Показва карточка на парфюм
 * ВАЖНО: Основната функция за показване на парфюмни карточки
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
    $rating = get_post_meta($post_id, '_parfume_rating', true);
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
 * РАЗДЕЛ 2: РЕЙТИНГ ФУНКЦИИ
 */

/**
 * Показва звездичен рейтинг
 * ВАЖНО: Основната функция за показване на рейтинг
 */
function parfume_reviews_display_star_rating($rating, $max_rating = 5, $show_number = false) {
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
    
    // Показва числото ако е поискано
    if ($show_number) {
        echo '<span class="rating-number">' . number_format($rating, 1) . '/' . $max_rating . '</span>';
    }
    
    echo '</div>';
}

/**
 * Получава HTML за звездичен рейтинг
 * ВАЖНО: Връща HTML като string вместо да го отпечатва
 */
function parfume_reviews_get_rating_stars($rating, $max_rating = 5) {
    ob_start();
    parfume_reviews_display_star_rating($rating, $max_rating);
    return ob_get_clean();
}

/**
 * Псевдоним за parfume_reviews_display_star_rating
 * ВАЖНО: Backward compatibility функция
 */
function parfume_reviews_display_stars($rating, $max_rating = 5, $show_number = false) {
    parfume_reviews_display_star_rating($rating, $max_rating, $show_number);
}

/**
 * РАЗДЕЛ 3: GRID И LISTING ФУНКЦИИ
 */

/**
 * Показва списък с парфюми в grid формат
 * ВАЖНО: Основната функция за показване на парфюмни списъци
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
    <div class="<?php echo esc_attr($grid_class); ?>" data-columns="<?php echo esc_attr($columns); ?>">
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
 * РАЗДЕЛ 4: PAGINATION ФУНКЦИИ
 */

/**
 * Показва pagination
 * ВАЖНО: Основната функция за pagination
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
    <nav class="parfume-pagination" role="navigation" aria-label="<?php _e('Навигация в страниците', 'parfume-reviews'); ?>">
        <?php
        echo paginate_links(array(
            'total' => $query->max_num_pages,
            'current' => $current_page,
            'prev_text' => '‹ ' . __('Предишна', 'parfume-reviews'),
            'next_text' => __('Следваща', 'parfume-reviews') . ' ›',
            'type' => 'list',
            'end_size' => 2,
            'mid_size' => 2,
            'add_args' => array_merge($_GET, array('paged' => '%#%'))
        ));
        ?>
    </nav>
    <?php
}

/**
 * РАЗДЕЛ 5: НАВИГАЦИОННИ ФУНКЦИИ
 */

/**
 * Показва breadcrumb навигация
 * ВАЖНО: Основната функция за breadcrumb
 */
function parfume_reviews_display_breadcrumb() {
    $breadcrumbs = array();
    $settings = get_option('parfume_reviews_settings', array());
    $home_text = !empty($settings['breadcrumb_home']) ? $settings['breadcrumb_home'] : __('Начало', 'parfume-reviews');
    
    // Home link
    $breadcrumbs[] = '<a href="' . home_url('/') . '">' . esc_html($home_text) . '</a>';
    
    // Parfume archive link
    if (!is_post_type_archive('parfume')) {
        $parfume_archive = get_post_type_archive_link('parfume');
        if ($parfume_archive) {
            $breadcrumbs[] = '<a href="' . esc_url($parfume_archive) . '">' . __('Парфюми', 'parfume-reviews') . '</a>';
        }
    }
    
    // Current page
    if (is_singular('parfume')) {
        $breadcrumbs[] = '<span class="current">' . get_the_title() . '</span>';
    } elseif (is_post_type_archive('parfume')) {
        $breadcrumbs[] = '<span class="current">' . __('Парфюми', 'parfume-reviews') . '</span>';
    } elseif (is_tax()) {
        $term = get_queried_object();
        if ($term) {
            $breadcrumbs[] = '<span class="current">' . esc_html($term->name) . '</span>';
        }
    }
    
    if (!empty($breadcrumbs)) {
        ?>
        <nav class="parfume-breadcrumb" aria-label="<?php _e('Breadcrumb', 'parfume-reviews'); ?>">
            <ol class="breadcrumb-list">
                <?php foreach ($breadcrumbs as $breadcrumb): ?>
                    <li class="breadcrumb-item"><?php echo $breadcrumb; ?></li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php
    }
}

/**
 * РАЗДЕЛ 6: HEADER И АРХИВНИ ФУНКЦИИ
 */

/**
 * Показва header на архивна страница
 * ВАЖНО: Header за архивни страници
 */
function parfume_reviews_display_archive_header() {
    ?>
    <div class="archive-header">
        <?php if (is_post_type_archive('parfume')): ?>
            <h1 class="archive-title"><?php _e('Всички парфюми', 'parfume-reviews'); ?></h1>
            <div class="archive-description">
                <?php
                $description = get_option('parfume_reviews_archive_description', '');
                if (!empty($description)) {
                    echo wp_kses_post($description);
                }
                ?>
            </div>
        <?php elseif (is_tax()): ?>
            <?php
            $term = get_queried_object();
            if ($term):
            ?>
                <h1 class="archive-title">
                    <?php 
                    echo esc_html(parfume_reviews_get_taxonomy_label($term->taxonomy)) . ': ' . esc_html($term->name);
                    ?>
                </h1>
                <?php if (!empty($term->description)): ?>
                    <div class="archive-description">
                        <?php echo wp_kses_post($term->description); ?>
                    </div>
                <?php endif; ?>
                
                <?php parfume_reviews_display_term_image($term->term_id); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Показва изображение на таксономия
 * ВАЖНО: Показва изображението ако съществува
 */
function parfume_reviews_display_term_image($term_id) {
    $image_id = get_term_meta($term_id, 'image', true);
    
    if (!empty($image_id)) {
        $image_url = wp_get_attachment_image_url($image_id, 'medium');
        if ($image_url) {
            ?>
            <div class="term-image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_term($term_id)->name); ?>" loading="lazy">
            </div>
            <?php
        }
    }
}

/**
 * РАЗДЕЛ 7: ИНТЕРАКТИВНИ ЕЛЕМЕНТИ
 */

/**
 * Показва loading индикатор
 * ВАЖНО: Loading елемент за AJAX операции
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
 * Получава бутон за сравнение
 * ВАЖНО: Връща HTML за comparison бутон
 */
function parfume_reviews_get_comparison_button($post_id) {
    // Проверяваме дали comparison е активиран
    $settings = get_option('parfume_reviews_settings', array());
    if (empty($settings['enable_comparison'])) {
        return '';
    }
    
    // Проверяваме дали класът Comparison съществува
    if (!class_exists('Parfume_Reviews\\Comparison')) {
        return '';
    }
    
    $button_text = __('Сравни', 'parfume-reviews');
    $button_class = 'parfume-comparison-btn';
    
    return sprintf(
        '<button type="button" class="%s" data-parfume-id="%d" title="%s">
            <span class="dashicons dashicons-update-alt"></span>
            %s
        </button>',
        esc_attr($button_class),
        intval($post_id),
        esc_attr($button_text),
        esc_html($button_text)
    );
}

/**
 * Получава dropdown за колекции
 * ВАЖНО: Collections dropdown за логнати потребители
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
 * РАЗДЕЛ 8: ПОМОЩНИ DISPLAY ФУНКЦИИ
 */

/**
 * Показва статистики за архивна страница
 * ВАЖНО: Показва броя намерени резултати
 */
function parfume_reviews_display_archive_stats($query = null) {
    if (!$query) {
        global $wp_query;
        $query = $wp_query;
    }
    
    if ($query->found_posts > 0) {
        ?>
        <div class="archive-stats">
            <p class="results-count">
                <?php
                printf(
                    _n(
                        'Намерен %d парфюм',
                        'Намерени %d парфюма',
                        $query->found_posts,
                        'parfume-reviews'
                    ),
                    $query->found_posts
                );
                ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Показва опции за сортиране
 * ВАЖНО: Dropdown за сортиране на резултатите
 */
function parfume_reviews_display_sort_options() {
    $current_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    
    $sort_options = array(
        'date' => __('Най-нови първо', 'parfume-reviews'),
        'title' => __('По име (А-Я)', 'parfume-reviews'),
        'rating' => __('По рейтинг (най-високи първо)', 'parfume-reviews'),
        'price_low' => __('По цена (ниски първо)', 'parfume-reviews'),
        'price_high' => __('По цена (високи първо)', 'parfume-reviews'),
        'random' => __('Произволно', 'parfume-reviews')
    );
    
    ?>
    <div class="sort-options">
        <label for="parfume-sort"><?php _e('Сортиране:', 'parfume-reviews'); ?></label>
        <select id="parfume-sort" name="orderby" class="parfume-sort-select">
            <?php foreach ($sort_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_orderby, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

/**
 * Показва бържа от информация
 * ВАЖНО: Бързо показване на ключова информация
 */
function parfume_reviews_display_quick_info($post_id) {
    $longevity = get_post_meta($post_id, '_parfume_longevity', true);
    $sillage = get_post_meta($post_id, '_parfume_sillage', true);
    $bottle_size = get_post_meta($post_id, '_parfume_bottle_size', true);
    
    if (empty($longevity) && empty($sillage) && empty($bottle_size)) {
        return;
    }
    
    ?>
    <div class="parfume-quick-info">
        <?php if (!empty($longevity)): ?>
            <span class="quick-info-item">
                <strong><?php _e('Дълготрайност:', 'parfume-reviews'); ?></strong>
                <?php echo esc_html($longevity); ?>
            </span>
        <?php endif; ?>
        
        <?php if (!empty($sillage)): ?>
            <span class="quick-info-item">
                <strong><?php _e('Прожекция:', 'parfume-reviews'); ?></strong>
                <?php echo esc_html($sillage); ?>
            </span>
        <?php endif; ?>
        
        <?php if (!empty($bottle_size)): ?>
            <span class="quick-info-item">
                <strong><?php _e('Размер:', 'parfume-reviews'); ?></strong>
                <?php echo esc_html($bottle_size); ?>
            </span>
        <?php endif; ?>
    </div>
    <?php
}

// End of file