<?php
/**
 * Template Functions - Filter Functions
 * Функции за работа с филтри и URL-и
 * 
 * ФАЙЛ: includes/template-functions-filters.php
 * ПОПРАВЕНА ВЕРСИЯ - Довършени функции
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Получава активните филтри за display
 */
function parfume_reviews_get_active_filters() {
    $active_filters = array();
    $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    
    foreach ($supported_taxonomies as $taxonomy) {
        if (!empty($_GET[$taxonomy])) {
            $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
            $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
        }
    }
    
    // Добавяме ценови филтри
    if (!empty($_GET['min_price'])) {
        $active_filters['min_price'] = floatval($_GET['min_price']);
    }
    
    if (!empty($_GET['max_price'])) {
        $active_filters['max_price'] = floatval($_GET['max_price']);
    }
    
    // Добавяме рейтинг филтър
    if (!empty($_GET['min_rating'])) {
        $active_filters['min_rating'] = floatval($_GET['min_rating']);
    }
    
    // Добавяме търсене
    if (!empty($_GET['search'])) {
        $active_filters['search'] = sanitize_text_field($_GET['search']);
    }
    
    // Добавяме сортиране
    if (!empty($_GET['orderby'])) {
        $active_filters['orderby'] = sanitize_text_field($_GET['orderby']);
    }
    
    return $active_filters;
}

/**
 * Построява URL за филтри
 */
function parfume_reviews_build_filter_url($filters = array(), $base_url = '') {
    if (empty($base_url)) {
        if (is_post_type_archive('parfume')) {
            $base_url = get_post_type_archive_link('parfume');
        } elseif (is_tax()) {
            $base_url = get_term_link(get_queried_object());
        } else {
            $base_url = home_url('/parfiumi/');
        }
    }
    
    if (!empty($filters)) {
        $base_url = add_query_arg($filters, $base_url);
    }
    
    return $base_url;
}

/**
 * Получава URL за премахване на филтър
 */
function parfume_reviews_get_remove_filter_url($filter_key, $filter_value = null) {
    $current_filters = parfume_reviews_get_active_filters();
    
    if (isset($current_filters[$filter_key])) {
        if ($filter_value === null) {
            // Премахваме целия филтър
            unset($current_filters[$filter_key]);
        } elseif (is_array($current_filters[$filter_key])) {
            // Премахваме конкретна стойност от масива
            $current_filters[$filter_key] = array_diff($current_filters[$filter_key], array($filter_value));
            if (empty($current_filters[$filter_key])) {
                unset($current_filters[$filter_key]);
            }
        } else {
            // Ако филтърът не е масив и стойността съвпада
            if ($current_filters[$filter_key] == $filter_value) {
                unset($current_filters[$filter_key]);
            }
        }
    }
    
    return parfume_reviews_build_filter_url($current_filters);
}

/**
 * Получава URL за добавяне на филтър
 */
function parfume_reviews_get_add_filter_url($filter_key, $filter_value) {
    $current_filters = parfume_reviews_get_active_filters();
    
    if (isset($current_filters[$filter_key])) {
        if (is_array($current_filters[$filter_key])) {
            if (!in_array($filter_value, $current_filters[$filter_key])) {
                $current_filters[$filter_key][] = $filter_value;
            }
        } else {
            // Превръщаме в масив ако не е
            if ($current_filters[$filter_key] != $filter_value) {
                $current_filters[$filter_key] = array($current_filters[$filter_key], $filter_value);
            }
        }
    } else {
        $current_filters[$filter_key] = $filter_value;
    }
    
    return parfume_reviews_build_filter_url($current_filters);
}

/**
 * Проверява дали филтър е активен
 * ПОПРАВЕНО: Довършена функция
 */
function parfume_reviews_is_filter_active($filter_key, $filter_value = null) {
    $active_filters = parfume_reviews_get_active_filters();
    
    if (!isset($active_filters[$filter_key])) {
        return false;
    }
    
    // Ако не е подадена стойност, проверяваме само дали ключът съществува
    if ($filter_value === null) {
        return true;
    }
    
    // Ако филтърът е масив
    if (is_array($active_filters[$filter_key])) {
        return in_array($filter_value, $active_filters[$filter_key]);
    }
    
    // Ако филтърът е единична стойност
    return $active_filters[$filter_key] == $filter_value;
}

/**
 * Показва активните филтри като тагове
 */
function parfume_reviews_display_active_filters() {
    $active_filters = parfume_reviews_get_active_filters();
    
    if (empty($active_filters)) {
        return;
    }
    
    $taxonomy_labels = array(
        'gender' => __('Пол', 'parfume-reviews'),
        'aroma_type' => __('Тип аромат', 'parfume-reviews'),
        'marki' => __('Марка', 'parfume-reviews'),
        'season' => __('Сезон', 'parfume-reviews'),
        'intensity' => __('Интензивност', 'parfume-reviews'),
        'notes' => __('Нотки', 'parfume-reviews'),
        'perfumer' => __('Парфюмерист', 'parfume-reviews')
    );
    
    echo '<div class="active-filters">';
    echo '<h3 class="active-filters-title">' . __('Активни филтри:', 'parfume-reviews') . '</h3>';
    echo '<div class="filter-tags">';
    
    foreach ($active_filters as $filter_key => $filter_value) {
        // Специални филтри (цена, рейтинг, търсене)
        if (in_array($filter_key, array('min_price', 'max_price', 'min_rating', 'search', 'orderby'))) {
            ?>
            <div class="active-filter">
                <span class="filter-label">
                    <?php 
                    switch ($filter_key) {
                        case 'min_price':
                            echo __('Мин. цена:', 'parfume-reviews');
                            break;
                        case 'max_price':
                            echo __('Макс. цена:', 'parfume-reviews');
                            break;
                        case 'min_rating':
                            echo __('Мин. рейтинг:', 'parfume-reviews');
                            break;
                        case 'search':
                            echo __('Търсене:', 'parfume-reviews');
                            break;
                        case 'orderby':
                            echo __('Сортиране:', 'parfume-reviews');
                            break;
                    }
                    ?>
                </span>
                <span class="filter-value"><?php echo esc_html($filter_value); ?></span>
                <a href="<?php echo esc_url(parfume_reviews_get_remove_filter_url($filter_key)); ?>" class="remove-filter">
                    <span class="dashicons dashicons-no-alt"></span>
                </a>
            </div>
            <?php
        } else {
            // Taxonomy филтри
            $filter_values = is_array($filter_value) ? $filter_value : array($filter_value);
            foreach ($filter_values as $value) {
                ?>
                <div class="active-filter">
                    <span class="filter-label"><?php echo esc_html($taxonomy_labels[$filter_key] ?? $filter_key); ?>:</span>
                    <span class="filter-value"><?php echo esc_html($value); ?></span>
                    <a href="<?php echo esc_url(parfume_reviews_get_remove_filter_url($filter_key, $value)); ?>" class="remove-filter">
                        <span class="dashicons dashicons-no-alt"></span>
                    </a>
                </div>
                <?php
            }
        }
    }
    
    echo '</div>';
    
    // Бутон за изчистване на всички филтри
    $clear_url = strtok($_SERVER['REQUEST_URI'], '?');
    echo '<a href="' . esc_url($clear_url) . '" class="clear-all-filters button">' . __('Изчисти всички филтри', 'parfume-reviews') . '</a>';
    
    echo '</div>';
}

/**
 * Показва форма за филтриране
 */
function parfume_reviews_display_filter_form($atts = array()) {
    $defaults = array(
        'show_gender' => true,
        'show_aroma_type' => true,
        'show_brands' => true,
        'show_season' => true,
        'show_intensity' => true,
        'show_notes' => true,
        'show_perfumer' => true,
        'show_price' => true,
        'show_rating' => true,
        'show_search' => true
    );
    
    $atts = wp_parse_args($atts, $defaults);
    
    ?>
    <div class="parfume-filters">
        <form method="get" action="" class="filter-form">
            
            <?php if ($atts['show_search']): ?>
            <div class="filter-group">
                <label for="parfume-search"><?php _e('Търсене', 'parfume-reviews'); ?></label>
                <input type="text" id="parfume-search" name="search" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>" placeholder="<?php _e('Търси парфюм...', 'parfume-reviews'); ?>">
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_gender']): ?>
            <div class="filter-group">
                <label><?php _e('Пол', 'parfume-reviews'); ?></label>
                <?php
                $gender_terms = get_terms(array('taxonomy' => 'gender', 'hide_empty' => false));
                foreach ($gender_terms as $term) {
                    $checked = parfume_reviews_is_filter_active('gender', $term->slug) ? 'checked' : '';
                    echo '<label class="filter-option">';
                    echo '<input type="checkbox" name="gender[]" value="' . esc_attr($term->slug) . '" ' . $checked . '>';
                    echo esc_html($term->name);
                    echo '</label>';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_price']): ?>
            <div class="filter-group">
                <label><?php _e('Ценови диапазон', 'parfume-reviews'); ?></label>
                <div class="price-range">
                    <input type="number" name="min_price" placeholder="<?php _e('Мин', 'parfume-reviews'); ?>" value="<?php echo esc_attr(isset($_GET['min_price']) ? $_GET['min_price'] : ''); ?>" step="0.01" min="0">
                    <span>-</span>
                    <input type="number" name="max_price" placeholder="<?php _e('Макс', 'parfume-reviews'); ?>" value="<?php echo esc_attr(isset($_GET['max_price']) ? $_GET['max_price'] : ''); ?>" step="0.01" min="0">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="filter-submit">
                <button type="submit" class="button button-primary"><?php _e('Филтрирай', 'parfume-reviews'); ?></button>
                <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="button button-secondary"><?php _e('Изчисти', 'parfume-reviews'); ?></a>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Показва опции за сортиране
 */
function parfume_reviews_display_sort_options() {
    $current_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    
    $sort_options = array(
        'date' => __('Най-нови', 'parfume-reviews'),
        'title' => __('По име (А-Я)', 'parfume-reviews'),
        'price_low' => __('Цена (ниска към висока)', 'parfume-reviews'),
        'price_high' => __('Цена (висока към ниска)', 'parfume-reviews'),
        'rating' => __('Рейтинг', 'parfume-reviews'),
        'popular' => __('Популярни', 'parfume-reviews')
    );
    
    ?>
    <div class="parfume-sort-options">
        <label for="parfume-orderby"><?php _e('Сортирай по:', 'parfume-reviews'); ?></label>
        <select id="parfume-orderby" name="orderby" onchange="this.form.submit()">
            <?php foreach ($sort_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_orderby, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}