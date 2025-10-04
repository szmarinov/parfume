<?php
/**
 * Template Functions - Filter Functions
 * Функции за работа с филтри и URL-и
 * 
 * Файл: includes/template-functions-filters.php
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
 */
function parfume_reviews_is_filter_active($filter_key, $filter_value = null) {
    $active_filters = parfume_reviews_get_active_filters();
    
    if (!isset($active_filters[$filter_key])) {
        return false;
    }
    
    if ($filter_value === null) {
        return true; // Проверяваме само дали ключа съществува
    }
    
    if (is_array($active_filters[$filter_key])) {
        return in_array($filter_value, $active_filters[$filter_key]);
    }
    
    return $active_filters[$filter_key] == $filter_value;
}

/**
 * Показва активните филтри
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
    
    ?>
    <div class="active-filters">
        <h4 class="active-filters-title"><?php _e('Активни филтри:', 'parfume-reviews'); ?></h4>
        <div class="active-filters-list">
            <?php foreach ($active_filters as $filter_key => $filter_value): ?>
                <?php if (in_array($filter_key, array('min_price', 'max_price', 'min_rating', 'search', 'orderby'))): ?>
                    <!-- Специални филтри -->
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
                <?php else: ?>
                    <!-- Taxonomy филтри -->
                    <?php 
                    $filter_values = is_array($filter_value) ? $filter_value : array($filter_value);
                    foreach ($filter_values as $value):
                    ?>
                        <div class="active-filter">
                            <span class="filter-label"><?php echo esc_html($taxonomy_labels[$filter_key] ?? $filter_key); ?>:</span>
                            <span class="filter-value"><?php echo esc_html($value); ?></span>
                            <a href="<?php echo esc_url(parfume_reviews_get_remove_filter_url($filter_key, $value)); ?>" class="remove-filter">
                                <span class="dashicons dashicons-no-alt"></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <!-- Бутон за изчистване на всички филтри -->
            <a href="<?php echo parfume_reviews_build_filter_url(); ?>" class="clear-all-filters">
                <?php _e('Изчисти всички', 'parfume-reviews'); ?>
            </a>
        </div>
    </div>
    <?php
}

/**
 * Показва филтър форма
 */
function parfume_reviews_display_filter_form() {
    $active_filters = parfume_reviews_get_active_filters();
    
    ?>
    <form class="parfume-filters-form" method="get" action="">
        <div class="filter-row">
            <!-- Търсене -->
            <div class="filter-group">
                <label for="search"><?php _e('Търсене:', 'parfume-reviews'); ?></label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?php echo esc_attr($active_filters['search'] ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Търси парфюм...', 'parfume-reviews'); ?>">
            </div>
            
            <!-- Ценови диапазон -->
            <div class="filter-group">
                <label><?php _e('Цена (лв.):', 'parfume-reviews'); ?></label>
                <div class="price-range">
                    <input type="number" 
                           name="min_price" 
                           value="<?php echo esc_attr($active_filters['min_price'] ?? ''); ?>"
                           placeholder="<?php esc_attr_e('От', 'parfume-reviews'); ?>"
                           min="0" 
                           step="0.01">
                    <span>-</span>
                    <input type="number" 
                           name="max_price" 
                           value="<?php echo esc_attr($active_filters['max_price'] ?? ''); ?>"
                           placeholder="<?php esc_attr_e('До', 'parfume-reviews'); ?>"
                           min="0" 
                           step="0.01">
                </div>
            </div>
            
            <!-- Рейтинг -->
            <div class="filter-group">
                <label for="min_rating"><?php _e('Мин. рейтинг:', 'parfume-reviews'); ?></label>
                <select id="min_rating" name="min_rating">
                    <option value=""><?php _e('Всички', 'parfume-reviews'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($active_filters['min_rating'] ?? '', $i); ?>>
                            <?php echo $i; ?>+ ★
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <div class="filter-row">
            <!-- Taxonomy филтри -->
            <?php
            $taxonomies_to_show = array(
                'gender' => __('Пол', 'parfume-reviews'),
                'marki' => __('Марка', 'parfume-reviews'),
                'season' => __('Сезон', 'parfume-reviews'),
                'intensity' => __('Интензивност', 'parfume-reviews')
            );
            
            foreach ($taxonomies_to_show as $taxonomy => $label):
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                
                if (!empty($terms) && !is_wp_error($terms)):
            ?>
                <div class="filter-group">
                    <label for="<?php echo esc_attr($taxonomy); ?>"><?php echo esc_html($label); ?>:</label>
                    <select id="<?php echo esc_attr($taxonomy); ?>" name="<?php echo esc_attr($taxonomy); ?>[]" multiple>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" 
                                    <?php echo parfume_reviews_is_filter_active($taxonomy, $term->slug) ? 'selected' : ''; ?>>
                                <?php echo esc_html($term->name); ?> (<?php echo $term->count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="filter-submit">
                <?php _e('Приложи филтри', 'parfume-reviews'); ?>
            </button>
            
            <a href="<?php echo parfume_reviews_build_filter_url(); ?>" class="filter-reset">
                <?php _e('Изчисти', 'parfume-reviews'); ?>
            </a>
        </div>
    </form>
    <?php
}

/**
 * Показва сортиране опции
 */
function parfume_reviews_display_sort_options() {
    $current_orderby = $_GET['orderby'] ?? 'date';
    $current_order = $_GET['order'] ?? 'DESC';
    
    $sort_options = array(
        'date-DESC' => __('Най-нови първо', 'parfume-reviews'),
        'date-ASC' => __('Най-стари първо', 'parfume-reviews'),
        'title-ASC' => __('Име А-Я', 'parfume-reviews'),
        'title-DESC' => __('Име Я-А', 'parfume-reviews'),
        'rating-DESC' => __('Най-висок рейтинг', 'parfume-reviews'),
        'rating-ASC' => __('Най-нисък рейтинг', 'parfume-reviews'),
        'price-ASC' => __('Цена възходящо', 'parfume-reviews'),
        'price-DESC' => __('Цена низходящо', 'parfume-reviews')
    );
    
    $current_sort = $current_orderby . '-' . $current_order;
    
    ?>
    <div class="sort-options">
        <label for="sort-select"><?php _e('Сортиране:', 'parfume-reviews'); ?></label>
        <select id="sort-select" name="orderby" onchange="this.form.submit();">
            <?php foreach ($sort_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_sort, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}