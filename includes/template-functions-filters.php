<?php
/**
 * Template Functions - Filter Functions
 * Функции за работа с филтри и URL-и
 * РЕВИЗИРАНА ВЕРСИЯ: Пълна функционалност с всички filter опции
 * 
 * Файл: includes/template-functions-filters.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACTIVE FILTERS FUNCTIONS
 * Функции за работа с активни филтри
 */

/**
 * Получава активните филтри от URL параметрите
 */
if (!function_exists('parfume_reviews_get_active_filters')) {
    function parfume_reviews_get_active_filters() {
        $active_filters = array();
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        // Taxonomy филтри
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
            }
        }
        
        // Ценови филтри
        if (!empty($_GET['min_price'])) {
            $active_filters['min_price'] = floatval($_GET['min_price']);
        }
        
        if (!empty($_GET['max_price'])) {
            $active_filters['max_price'] = floatval($_GET['max_price']);
        }
        
        // Рейтинг филтър
        if (!empty($_GET['min_rating'])) {
            $active_filters['min_rating'] = floatval($_GET['min_rating']);
        }
        
        // Търсене
        if (!empty($_GET['search'])) {
            $active_filters['search'] = sanitize_text_field($_GET['search']);
        }
        
        // Сортиране
        if (!empty($_GET['orderby'])) {
            $allowed_orderby = array('date', 'title', 'rating', 'price', 'popularity', 'random');
            $orderby = sanitize_text_field($_GET['orderby']);
            if (in_array($orderby, $allowed_orderby)) {
                $active_filters['orderby'] = $orderby;
            }
        }
        
        // Order direction
        if (!empty($_GET['order'])) {
            $allowed_order = array('ASC', 'DESC');
            $order = strtoupper(sanitize_text_field($_GET['order']));
            if (in_array($order, $allowed_order)) {
                $active_filters['order'] = $order;
            }
        }
        
        // Availability филтър
        if (!empty($_GET['availability'])) {
            $active_filters['availability'] = sanitize_text_field($_GET['availability']);
        }
        
        // On sale филтър
        if (!empty($_GET['on_sale'])) {
            $active_filters['on_sale'] = (bool) $_GET['on_sale'];
        }
        
        // Featured филтър
        if (!empty($_GET['featured'])) {
            $active_filters['featured'] = (bool) $_GET['featured'];
        }
        
        return $active_filters;
    }
}

/**
 * URL BUILDING FUNCTIONS
 * Функции за изграждане на URL-и
 */

/**
 * Построява URL с филтри
 */
if (!function_exists('parfume_reviews_build_filter_url')) {
    function parfume_reviews_build_filter_url($filters = array(), $base_url = '') {
        if (empty($base_url)) {
            if (is_post_type_archive('parfume')) {
                $base_url = get_post_type_archive_link('parfume');
            } elseif (is_tax()) {
                $queried_object = get_queried_object();
                if ($queried_object && !is_wp_error($queried_object)) {
                    $base_url = get_term_link($queried_object);
                }
            } else {
                // Fallback URL
                $url_settings = get_option('parfume_reviews_url_settings', array());
                $parfume_slug = isset($url_settings['parfume_slug']) ? $url_settings['parfume_slug'] : 'parfiumi';
                $base_url = home_url('/' . $parfume_slug . '/');
            }
        }
        
        // Премахваме празни филтри
        $clean_filters = array();
        foreach ($filters as $key => $value) {
            if (!empty($value) || $value === 0 || $value === '0') {
                $clean_filters[$key] = $value;
            }
        }
        
        if (!empty($clean_filters)) {
            $base_url = add_query_arg($clean_filters, $base_url);
        }
        
        return $base_url;
    }
}

/**
 * Получава URL за премахване на филтър
 */
if (!function_exists('parfume_reviews_get_remove_filter_url')) {
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
}

/**
 * Получава URL за добавяне на филтър
 */
if (!function_exists('parfume_reviews_get_add_filter_url')) {
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
}

/**
 * Получава URL за изчистване на всички филтри
 */
if (!function_exists('parfume_reviews_get_clear_filters_url')) {
    function parfume_reviews_get_clear_filters_url() {
        return parfume_reviews_build_filter_url(array());
    }
}

/**
 * FILTER CHECK FUNCTIONS
 * Функции за проверка на филтри
 */

/**
 * Проверява дали филтър е активен
 */
if (!function_exists('parfume_reviews_is_filter_active')) {
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
}

/**
 * Проверява дали има активни филтри
 */
if (!function_exists('parfume_reviews_has_active_filters')) {
    function parfume_reviews_has_active_filters() {
        $active_filters = parfume_reviews_get_active_filters();
        return !empty($active_filters);
    }
}

/**
 * Получава броя на активните филтри
 */
if (!function_exists('parfume_reviews_get_active_filters_count')) {
    function parfume_reviews_get_active_filters_count() {
        $active_filters = parfume_reviews_get_active_filters();
        $count = 0;
        
        foreach ($active_filters as $filter_value) {
            if (is_array($filter_value)) {
                $count += count($filter_value);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
}

/**
 * DISPLAY FUNCTIONS
 * Функции за показване на филтри
 */

/**
 * Показва активните филтри
 */
if (!function_exists('parfume_reviews_display_active_filters')) {
    function parfume_reviews_display_active_filters($args = array()) {
        $active_filters = parfume_reviews_get_active_filters();
        
        if (empty($active_filters)) {
            return;
        }
        
        $defaults = array(
            'title' => __('Активни филтри:', 'parfume-reviews'),
            'class' => 'active-filters',
            'show_clear_all' => true,
            'clear_all_text' => __('Изчисти всички', 'parfume-reviews')
        );
        
        $args = wp_parse_args($args, $defaults);
        
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
        <div class="<?php echo esc_attr($args['class']); ?>">
            <?php if ($args['title']): ?>
            <h4 class="active-filters-title"><?php echo esc_html($args['title']); ?></h4>
            <?php endif; ?>
            
            <div class="active-filters-list">
                <?php foreach ($active_filters as $filter_key => $filter_value): ?>
                    <?php if (in_array($filter_key, array('min_price', 'max_price', 'min_rating', 'search', 'orderby', 'order', 'availability', 'on_sale', 'featured'))): ?>
                        <!-- Специални филтри -->
                        <div class="active-filter special-filter">
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
                                    case 'order':
                                        echo __('Посока:', 'parfume-reviews');
                                        break;
                                    case 'availability':
                                        echo __('Наличност:', 'parfume-reviews');
                                        break;
                                    case 'on_sale':
                                        echo __('В промоция', 'parfume-reviews');
                                        break;
                                    case 'featured':
                                        echo __('Препоръчани', 'parfume-reviews');
                                        break;
                                }
                                ?>
                            </span>
                            
                            <?php if (!in_array($filter_key, array('on_sale', 'featured'))): ?>
                            <span class="filter-value"><?php echo esc_html($filter_value); ?></span>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url(parfume_reviews_get_remove_filter_url($filter_key)); ?>" 
                               class="remove-filter" 
                               title="<?php esc_attr_e('Премахни филтъра', 'parfume-reviews'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- Taxonomy филтри -->
                        <?php 
                        $filter_values = is_array($filter_value) ? $filter_value : array($filter_value);
                        foreach ($filter_values as $value):
                            $term = get_term_by('slug', $value, $filter_key);
                            $display_value = $term ? $term->name : $value;
                        ?>
                            <div class="active-filter taxonomy-filter">
                                <span class="filter-label"><?php echo esc_html($taxonomy_labels[$filter_key] ?? $filter_key); ?>:</span>
                                <span class="filter-value"><?php echo esc_html($display_value); ?></span>
                                <a href="<?php echo esc_url(parfume_reviews_get_remove_filter_url($filter_key, $value)); ?>" 
                                   class="remove-filter" 
                                   title="<?php esc_attr_e('Премахни филтъра', 'parfume-reviews'); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($args['show_clear_all']): ?>
                <!-- Бутон за изчистване на всички филтри -->
                <a href="<?php echo esc_url(parfume_reviews_get_clear_filters_url()); ?>" 
                   class="clear-all-filters">
                    <?php echo esc_html($args['clear_all_text']); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Показва филтър форма
 */
if (!function_exists('parfume_reviews_display_filter_form')) {
    function parfume_reviews_display_filter_form($args = array()) {
        $defaults = array(
            'method' => 'get',
            'class' => 'parfume-filters-form',
            'show_search' => true,
            'show_price_range' => true,
            'show_rating' => true,
            'show_taxonomies' => true,
            'show_sorting' => true,
            'show_special_filters' => true,
            'submit_text' => __('Филтрирай', 'parfume-reviews'),
            'reset_text' => __('Изчисти', 'parfume-reviews')
        );
        
        $args = wp_parse_args($args, $defaults);
        $active_filters = parfume_reviews_get_active_filters();
        
        ?>
        <form class="<?php echo esc_attr($args['class']); ?>" method="<?php echo esc_attr($args['method']); ?>" action="">
            
            <?php if ($args['show_search']): ?>
            <div class="filter-group search-group">
                <label for="parfume-search"><?php _e('Търсене:', 'parfume-reviews'); ?></label>
                <input type="text" 
                       id="parfume-search" 
                       name="search" 
                       value="<?php echo esc_attr($active_filters['search'] ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Търси парфюм...', 'parfume-reviews'); ?>">
            </div>
            <?php endif; ?>
            
            <?php if ($args['show_price_range']): ?>
            <div class="filter-group price-group">
                <label><?php _e('Цена (лв.):', 'parfume-reviews'); ?></label>
                <div class="price-range">
                    <input type="number" 
                           name="min_price" 
                           value="<?php echo esc_attr($active_filters['min_price'] ?? ''); ?>"
                           placeholder="<?php esc_attr_e('От', 'parfume-reviews'); ?>"
                           min="0" 
                           step="0.01"
                           class="price-input">
                    <span class="price-separator">-</span>
                    <input type="number" 
                           name="max_price" 
                           value="<?php echo esc_attr($active_filters['max_price'] ?? ''); ?>"
                           placeholder="<?php esc_attr_e('До', 'parfume-reviews'); ?>"
                           min="0" 
                           step="0.01"
                           class="price-input">
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($args['show_rating']): ?>
            <div class="filter-group rating-group">
                <label for="min-rating"><?php _e('Мин. рейтинг:', 'parfume-reviews'); ?></label>
                <select id="min-rating" name="min_rating">
                    <option value=""><?php _e('Всички', 'parfume-reviews'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($active_filters['min_rating'] ?? '', $i); ?>>
                        <?php echo $i; ?>+ ★
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($args['show_taxonomies']): ?>
            <!-- Taxonomy филтри -->
            <?php
            $taxonomies = array(
                'gender' => __('Пол', 'parfume-reviews'),
                'marki' => __('Марка', 'parfume-reviews'),
                'aroma_type' => __('Тип аромат', 'parfume-reviews'),
                'season' => __('Сезон', 'parfume-reviews'),
                'intensity' => __('Интензивност', 'parfume-reviews'),
                'notes' => __('Нотки', 'parfume-reviews'),
                'perfumer' => __('Парфюмерист', 'parfume-reviews')
            );
            
            foreach ($taxonomies as $taxonomy => $label):
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'number' => 50
                ));
                
                if (!empty($terms) && !is_wp_error($terms)):
            ?>
            <div class="filter-group taxonomy-group taxonomy-<?php echo esc_attr($taxonomy); ?>">
                <label><?php echo esc_html($label); ?>:</label>
                <div class="taxonomy-options">
                    <?php foreach ($terms as $term): ?>
                    <label class="taxonomy-option">
                        <input type="checkbox" 
                               name="<?php echo esc_attr($taxonomy); ?>[]" 
                               value="<?php echo esc_attr($term->slug); ?>"
                               <?php checked(in_array($term->slug, $active_filters[$taxonomy] ?? array())); ?>>
                        <span class="option-text"><?php echo esc_html($term->name); ?></span>
                        <span class="option-count">(<?php echo $term->count; ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php 
                endif;
            endforeach; 
            ?>
            <?php endif; ?>
            
            <?php if ($args['show_sorting']): ?>
            <div class="filter-group sorting-group">
                <label for="orderby"><?php _e('Сортиране:', 'parfume-reviews'); ?></label>
                <select id="orderby" name="orderby">
                    <option value=""><?php _e('По подразбиране', 'parfume-reviews'); ?></option>
                    <option value="date" <?php selected($active_filters['orderby'] ?? '', 'date'); ?>><?php _e('Най-нови', 'parfume-reviews'); ?></option>
                    <option value="title" <?php selected($active_filters['orderby'] ?? '', 'title'); ?>><?php _e('По име', 'parfume-reviews'); ?></option>
                    <option value="rating" <?php selected($active_filters['orderby'] ?? '', 'rating'); ?>><?php _e('По рейтинг', 'parfume-reviews'); ?></option>
                    <option value="price" <?php selected($active_filters['orderby'] ?? '', 'price'); ?>><?php _e('По цена', 'parfume-reviews'); ?></option>
                    <option value="popularity" <?php selected($active_filters['orderby'] ?? '', 'popularity'); ?>><?php _e('По популярност', 'parfume-reviews'); ?></option>
                </select>
                
                <select name="order">
                    <option value="DESC" <?php selected($active_filters['order'] ?? 'DESC', 'DESC'); ?>><?php _e('Низходящо', 'parfume-reviews'); ?></option>
                    <option value="ASC" <?php selected($active_filters['order'] ?? 'DESC', 'ASC'); ?>><?php _e('Възходящо', 'parfume-reviews'); ?></option>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($args['show_special_filters']): ?>
            <div class="filter-group special-filters-group">
                <div class="special-options">
                    <label class="special-option">
                        <input type="checkbox" 
                               name="on_sale" 
                               value="1"
                               <?php checked(!empty($active_filters['on_sale'])); ?>>
                        <span class="option-text"><?php _e('В промоция', 'parfume-reviews'); ?></span>
                    </label>
                    
                    <label class="special-option">
                        <input type="checkbox" 
                               name="featured" 
                               value="1"
                               <?php checked(!empty($active_filters['featured'])); ?>>
                        <span class="option-text"><?php _e('Препоръчани', 'parfume-reviews'); ?></span>
                    </label>
                    
                    <label class="special-option">
                        <input type="checkbox" 
                               name="availability" 
                               value="in_stock"
                               <?php checked($active_filters['availability'] ?? '', 'in_stock'); ?>>
                        <span class="option-text"><?php _e('Налични', 'parfume-reviews'); ?></span>
                    </label>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="filter-actions">
                <button type="submit" class="button button-primary filter-submit">
                    <?php echo esc_html($args['submit_text']); ?>
                </button>
                
                <a href="<?php echo esc_url(parfume_reviews_get_clear_filters_url()); ?>" 
                   class="button button-secondary filter-reset">
                    <?php echo esc_html($args['reset_text']); ?>
                </a>
            </div>
            
        </form>
        <?php
    }
}

/**
 * Показва опции за сортиране
 */
if (!function_exists('parfume_reviews_display_sort_options')) {
    function parfume_reviews_display_sort_options($args = array()) {
        $defaults = array(
            'class' => 'sort-options',
            'show_label' => true,
            'label' => __('Сортиране:', 'parfume-reviews'),
            'auto_submit' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        $active_filters = parfume_reviews_get_active_filters();
        $current_orderby = $active_filters['orderby'] ?? '';
        $current_order = $active_filters['order'] ?? 'DESC';
        
        ?>
        <div class="<?php echo esc_attr($args['class']); ?>">
            <?php if ($args['show_label']): ?>
            <span class="sort-label"><?php echo esc_html($args['label']); ?></span>
            <?php endif; ?>
            
            <select name="orderby" class="sort-select" <?php if ($args['auto_submit']) echo 'data-auto-submit="true"'; ?>>
                <option value=""><?php _e('По подразбиране', 'parfume-reviews'); ?></option>
                <option value="date" <?php selected($current_orderby, 'date'); ?>><?php _e('Най-нови', 'parfume-reviews'); ?></option>
                <option value="title" <?php selected($current_orderby, 'title'); ?>><?php _e('По име', 'parfume-reviews'); ?></option>
                <option value="rating" <?php selected($current_orderby, 'rating'); ?>><?php _e('По рейтинг', 'parfume-reviews'); ?></option>
                <option value="price" <?php selected($current_orderby, 'price'); ?>><?php _e('По цена', 'parfume-reviews'); ?></option>
                <option value="popularity" <?php selected($current_orderby, 'popularity'); ?>><?php _e('По популярност', 'parfume-reviews'); ?></option>
            </select>
            
            <select name="order" class="order-select" <?php if ($args['auto_submit']) echo 'data-auto-submit="true"'; ?>>
                <option value="DESC" <?php selected($current_order, 'DESC'); ?>><?php _e('Низходящо', 'parfume-reviews'); ?></option>
                <option value="ASC" <?php selected($current_order, 'ASC'); ?>><?php _e('Възходящо', 'parfume-reviews'); ?></option>
            </select>
        </div>
        <?php
    }
}

/**
 * QUERY MODIFICATION FUNCTIONS
 * Функции за модификация на заявки
 */

/**
 * Прилага филтри към WP_Query
 */
if (!function_exists('parfume_reviews_apply_query_filters')) {
    function parfume_reviews_apply_query_filters($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $active_filters = parfume_reviews_get_active_filters();
        
        if (empty($active_filters)) {
            return;
        }
        
        // Tax query за таксономии
        $tax_query = array('relation' => 'AND');
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($active_filters[$taxonomy])) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $active_filters[$taxonomy],
                    'operator' => 'IN'
                );
            }
        }
        
        if (count($tax_query) > 1) {
            $query->set('tax_query', $tax_query);
        }
        
        // Meta query за цени и рейтинг
        $meta_query = array('relation' => 'AND');
        
        if (!empty($active_filters['min_price']) || !empty($active_filters['max_price'])) {
            $price_query = array(
                'key' => 'parfume_lowest_price',
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            );
            
            $min_price = !empty($active_filters['min_price']) ? $active_filters['min_price'] : 0;
            $max_price = !empty($active_filters['max_price']) ? $active_filters['max_price'] : 999999;
            
            $price_query['value'] = array($min_price, $max_price);
            $meta_query[] = $price_query;
        }
        
        if (!empty($active_filters['min_rating'])) {
            $meta_query[] = array(
                'key' => 'parfume_rating',
                'value' => $active_filters['min_rating'],
                'type' => 'NUMERIC',
                'compare' => '>='
            );
        }
        
        if (!empty($active_filters['availability'])) {
            $meta_query[] = array(
                'key' => 'parfume_availability',
                'value' => $active_filters['availability'],
                'compare' => '='
            );
        }
        
        if (!empty($active_filters['on_sale'])) {
            $meta_query[] = array(
                'key' => 'parfume_on_sale',
                'value' => '1',
                'compare' => '='
            );
        }
        
        if (!empty($active_filters['featured'])) {
            $meta_query[] = array(
                'key' => 'parfume_featured',
                'value' => '1',
                'compare' => '='
            );
        }
        
        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
        
        // Search query
        if (!empty($active_filters['search'])) {
            $query->set('s', $active_filters['search']);
        }
        
        // Ordering
        if (!empty($active_filters['orderby'])) {
            $orderby = $active_filters['orderby'];
            $order = $active_filters['order'] ?? 'DESC';
            
            switch ($orderby) {
                case 'price':
                    $query->set('meta_key', 'parfume_lowest_price');
                    $query->set('orderby', 'meta_value_num');
                    break;
                case 'rating':
                    $query->set('meta_key', 'parfume_rating');
                    $query->set('orderby', 'meta_value_num');
                    break;
                case 'popularity':
                    $query->set('meta_key', 'parfume_views');
                    $query->set('orderby', 'meta_value_num');
                    break;
                default:
                    $query->set('orderby', $orderby);
                    break;
            }
            
            $query->set('order', $order);
        }
    }
}

/**
 * UTILITY FUNCTIONS
 * Помощни функции за филтри
 */

/**
 * Получава възможните стойности за филтър
 */
if (!function_exists('parfume_reviews_get_filter_options')) {
    function parfume_reviews_get_filter_options($filter_key) {
        $options = array();
        
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        if (in_array($filter_key, $supported_taxonomies)) {
            $terms = get_terms(array(
                'taxonomy' => $filter_key,
                'hide_empty' => true
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $options[$term->slug] = $term->name;
                }
            }
        }
        
        return $options;
    }
}

/**
 * Получава броя резултати за филтър
 */
if (!function_exists('parfume_reviews_get_filter_count')) {
    function parfume_reviews_get_filter_count($filters = array()) {
        $args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'fields' => 'ids',
            'no_found_rows' => false
        );
        
        // Прилагаме филтрите
        if (!empty($filters)) {
            // Temporary query за получаване на броя
            $temp_query = new WP_Query();
            $temp_query->parse_query($args);
            
            // Симулираме активни филтри
            $old_get = $_GET;
            $_GET = array_merge($_GET, $filters);
            
            parfume_reviews_apply_query_filters($temp_query);
            
            $query = new WP_Query($temp_query->query_vars);
            $count = $query->found_posts;
            
            // Възстановяваме $_GET
            $_GET = $old_get;
            
            return $count;
        }
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
}

/**
 * LEGACY COMPATIBILITY FUNCTIONS
 * Функции за backward compatibility
 */

// Алтернативни имена за backward compatibility
if (!function_exists('get_parfume_active_filters')) {
    function get_parfume_active_filters() {
        return parfume_reviews_get_active_filters();
    }
}

if (!function_exists('build_parfume_filter_url')) {
    function build_parfume_filter_url($filters = array(), $base_url = '') {
        return parfume_reviews_build_filter_url($filters, $base_url);
    }
}

if (!function_exists('is_filter_active')) {
    function is_filter_active($filter_key, $filter_value = null) {
        return parfume_reviews_is_filter_active($filter_key, $filter_value);
    }
}