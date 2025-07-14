<?php
/**
 * Template Functions - Filter Functions
 * Функции за работа с филтри и URL-и
 * 
 * Файл: includes/template-functions-filters.php
 * РЕВИЗИРАНА ВЕРСИЯ - ПЪЛЕН НАБОР ОТ FILTER ФУНКЦИИ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * РАЗДЕЛ 1: ОСНОВНИ FILTER ФУНКЦИИ
 */

/**
 * Получава активните филтри за display
 * ВАЖНО: Основната функция за получаване на активни филтри
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
 * РАЗДЕЛ 2: URL BUILDING ФУНКЦИИ
 */

/**
 * Построява URL за филтри
 * ВАЖНО: Основната функция за построяване на филтриращи URL-и
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
        // Почистваме празни стойности
        $filters = array_filter($filters, function($value) {
            return !empty($value) && $value !== '' && $value !== 0;
        });
        
        $base_url = add_query_arg($filters, $base_url);
    }
    
    return $base_url;
}

/**
 * Получава URL за премахване на филтър
 * ВАЖНО: Премахва специфичен филтър от URL-а
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
 * ВАЖНО: Добавя нов филтър към съществуващите
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
 * РАЗДЕЛ 3: ПРОВЕРКИ ЗА АКТИВНИ ФИЛТРИ
 */

/**
 * Проверява дали филтър е активен
 * ВАЖНО: Основната функция за проверка на активни филтри
 */
function parfume_reviews_is_filter_active($filter_key, $filter_value = null) {
    $active_filters = parfume_reviews_get_active_filters();
    
    if (!isset($active_filters[$filter_key])) {
        return false;
    }
    
    if ($filter_value === null) {
        return true; // Филтърът съществува, не ни интересува стойността
    }
    
    if (is_array($active_filters[$filter_key])) {
        return in_array($filter_value, $active_filters[$filter_key]);
    }
    
    return $active_filters[$filter_key] == $filter_value;
}

/**
 * Проверява дали има активни филтри
 * ВАЖНО: Помощна функция за проверка дали има избрани филтри
 */
function parfume_reviews_has_active_filters() {
    $active_filters = parfume_reviews_get_active_filters();
    
    // Игнорираме сортирането при проверката
    unset($active_filters['orderby']);
    
    return !empty($active_filters);
}

/**
 * РАЗДЕЛ 4: DISPLAY ФУНКЦИИ ЗА ФИЛТРИ
 */

/**
 * Показва активните филтри
 * ВАЖНО: Визуализира активните филтри с възможност за премахване
 */
function parfume_reviews_display_active_filters() {
    $active_filters = parfume_reviews_get_active_filters();
    
    // Премахваме orderby от показването
    unset($active_filters['orderby']);
    
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
        'perfumer' => __('Парфюмерист', 'parfume-reviews'),
        'min_price' => __('Мин. цена', 'parfume-reviews'),
        'max_price' => __('Макс. цена', 'parfume-reviews'),
        'min_rating' => __('Мин. рейтинг', 'parfume-reviews'),
        'search' => __('Търсене', 'parfume-reviews')
    );
    
    ?>
    <div class="active-filters-wrapper">
        <h4 class="active-filters-title"><?php _e('Активни филтри:', 'parfume-reviews'); ?></h4>
        <div class="active-filters">
            <?php foreach ($active_filters as $filter_key => $filter_value): ?>
                <?php if (!empty($filter_value)): ?>
                    <?php 
                    $filter_values = is_array($filter_value) ? $filter_value : array($filter_value);
                    foreach ($filter_values as $value):
                    ?>
                        <div class="active-filter">
                            <span class="filter-label"><?php echo esc_html($taxonomy_labels[$filter_key] ?? $filter_key); ?>:</span>
                            <span class="filter-value"><?php echo esc_html($value); ?></span>
                            <a href="<?php echo esc_url(parfume_reviews_get_remove_filter_url($filter_key, $value)); ?>" class="remove-filter" title="<?php esc_attr_e('Премахни филтър', 'parfume-reviews'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <!-- Бутон за изчистване на всички филтри -->
            <a href="<?php echo parfume_reviews_build_filter_url(); ?>" class="clear-all-filters" title="<?php esc_attr_e('Изчисти всички филтри', 'parfume-reviews'); ?>">
                <?php _e('Изчисти всички', 'parfume-reviews'); ?>
            </a>
        </div>
    </div>
    <?php
}

/**
 * РАЗДЕЛ 5: ФИЛТЪРНА ФОРМА
 */

/**
 * Показва филтър форма
 * ВАЖНО: Основната форма за филтриране на парфюми
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
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php selected($active_filters['min_rating'] ?? '', $i); ?>>
                            <?php echo sprintf(__('%d+ звезди', 'parfume-reviews'), $i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <!-- Таксономични филтри -->
        <div class="filter-row">
            <?php
            $taxonomies_to_show = array('gender', 'aroma_type', 'marki', 'season', 'intensity');
            foreach ($taxonomies_to_show as $taxonomy):
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'number' => 50
                ));
                
                if (!empty($terms) && !is_wp_error($terms)):
            ?>
                <div class="filter-group">
                    <label><?php echo esc_html(parfume_reviews_get_taxonomy_label($taxonomy)); ?>:</label>
                    <select name="<?php echo esc_attr($taxonomy); ?>[]" multiple class="filter-select">
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" 
                                <?php echo in_array($term->slug, $active_filters[$taxonomy] ?? array()) ? 'selected' : ''; ?>>
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
        
        <!-- Бутони -->
        <div class="filter-submit">
            <button type="submit" class="button button-primary"><?php _e('Филтрирай', 'parfume-reviews'); ?></button>
            <a href="<?php echo parfume_reviews_build_filter_url(); ?>" class="button button-secondary"><?php _e('Изчисти', 'parfume-reviews'); ?></a>
        </div>
    </form>
    <?php
}

/**
 * РАЗДЕЛ 6: СОРТИРАНЕ
 */

/**
 * Показва опции за сортиране
 * ВАЖНО: Dropdown за сортиране на резултатите
 * ВЕЧЕ ДЕФИНИРАНА В template-functions-display.php - НЕ ДУБЛИРАМЕ!
 */

/**
 * РАЗДЕЛ 7: AJAX ФИЛТРИ
 */

/**
 * Инициализира AJAX филтриране
 * ВАЖНО: Настройва AJAX функционалност за филтрите
 */
function parfume_reviews_init_ajax_filters() {
    if (!parfume_reviews_is_parfume_archive()) {
        return;
    }
    
    // Enqueue JavaScript за AJAX филтри
    wp_enqueue_script(
        'parfume-reviews-ajax-filters',
        PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/ajax-filters.js',
        array('jquery'),
        PARFUME_REVIEWS_VERSION,
        true
    );
    
    // Localize script
    wp_localize_script('parfume-reviews-ajax-filters', 'parfume_ajax_filters', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('parfume_ajax_filters_nonce'),
        'loading_text' => __('Зареждане...', 'parfume-reviews'),
        'no_results_text' => __('Няма намерени резултати.', 'parfume-reviews')
    ));
}

/**
 * AJAX handler за филтриране на парфюми
 * ВАЖНО: Обработва AJAX заявки за филтриране
 */
function parfume_reviews_ajax_filter_parfumes() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'parfume_ajax_filters_nonce')) {
        wp_die(__('Грешка в сигурността.', 'parfume-reviews'));
    }
    
    // Collect filters from POST data
    $filters = array();
    $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    
    foreach ($supported_taxonomies as $taxonomy) {
        if (!empty($_POST[$taxonomy])) {
            $filters[$taxonomy] = array_map('sanitize_text_field', (array)$_POST[$taxonomy]);
        }
    }
    
    // Price filters
    if (!empty($_POST['min_price'])) {
        $filters['min_price'] = floatval($_POST['min_price']);
    }
    
    if (!empty($_POST['max_price'])) {
        $filters['max_price'] = floatval($_POST['max_price']);
    }
    
    // Rating filter
    if (!empty($_POST['min_rating'])) {
        $filters['min_rating'] = floatval($_POST['min_rating']);
    }
    
    // Search filter
    if (!empty($_POST['search'])) {
        $filters['search'] = sanitize_text_field($_POST['search']);
    }
    
    // Build query
    $query_args = parfume_reviews_build_filter_query($filters);
    $query_args['posts_per_page'] = 12;
    $query_args['paged'] = max(1, intval($_POST['page'] ?? 1));
    
    // Execute query
    $parfumes_query = new WP_Query($query_args);
    
    // Build response
    $response = array(
        'success' => true,
        'html' => '',
        'pagination' => '',
        'count' => $parfumes_query->found_posts
    );
    
    if ($parfumes_query->have_posts()) {
        ob_start();
        while ($parfumes_query->have_posts()) {
            $parfumes_query->the_post();
            parfume_reviews_display_parfume_card(get_the_ID());
        }
        $response['html'] = ob_get_clean();
        
        // Pagination
        if ($parfumes_query->max_num_pages > 1) {
            ob_start();
            parfume_reviews_display_pagination($parfumes_query);
            $response['pagination'] = ob_get_clean();
        }
        
        wp_reset_postdata();
    } else {
        $response['html'] = '<div class="no-parfumes-message"><p>' . __('Няма намерени парфюми за показване.', 'parfume-reviews') . '</p></div>';
    }
    
    wp_send_json($response);
}

/**
 * РАЗДЕЛ 8: HELPER ФУНКЦИИ
 */

/**
 * Построява WP_Query args от филтри
 * ВАЖНО: Преобразува филтрите в WP_Query параметри
 */
function parfume_reviews_build_filter_query($filters = array()) {
    $query_args = array(
        'post_type' => 'parfume',
        'post_status' => 'publish',
        'posts_per_page' => -1
    );
    
    // Taxonomy queries
    $tax_query = array('relation' => 'AND');
    $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    
    foreach ($supported_taxonomies as $taxonomy) {
        if (!empty($filters[$taxonomy])) {
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => (array)$filters[$taxonomy],
                'operator' => 'IN'
            );
        }
    }
    
    if (count($tax_query) > 1) {
        $query_args['tax_query'] = $tax_query;
    }
    
    // Meta queries
    $meta_query = array('relation' => 'AND');
    
    // Price filters
    if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
        $price_query = array('key' => '_price', 'type' => 'NUMERIC');
        
        if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
            $price_query['value'] = array($filters['min_price'], $filters['max_price']);
            $price_query['compare'] = 'BETWEEN';
        } elseif (!empty($filters['min_price'])) {
            $price_query['value'] = $filters['min_price'];
            $price_query['compare'] = '>=';
        } elseif (!empty($filters['max_price'])) {
            $price_query['value'] = $filters['max_price'];
            $price_query['compare'] = '<=';
        }
        
        $meta_query[] = $price_query;
    }
    
    // Rating filter
    if (!empty($filters['min_rating'])) {
        $meta_query[] = array(
            'key' => '_parfume_rating',
            'value' => $filters['min_rating'],
            'type' => 'NUMERIC',
            'compare' => '>='
        );
    }
    
    if (count($meta_query) > 1) {
        $query_args['meta_query'] = $meta_query;
    }
    
    // Search query
    if (!empty($filters['search'])) {
        $query_args['s'] = $filters['search'];
    }
    
    // Ordering
    if (!empty($filters['orderby'])) {
        switch ($filters['orderby']) {
            case 'title':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
            case 'rating':
                $query_args['meta_key'] = '_parfume_rating';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
            case 'price_low':
                $query_args['meta_key'] = '_price';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'ASC';
                break;
            case 'price_high':
                $query_args['meta_key'] = '_price';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
            case 'random':
                $query_args['orderby'] = 'rand';
                break;
            default:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
        }
    }
    
    return $query_args;
}

// Hook AJAX actions
add_action('wp_ajax_filter_parfumes', 'parfume_reviews_ajax_filter_parfumes');
add_action('wp_ajax_nopriv_filter_parfumes', 'parfume_reviews_ajax_filter_parfumes');

// Initialize AJAX filters on appropriate pages
add_action('wp_enqueue_scripts', 'parfume_reviews_init_ajax_filters');

// End of file