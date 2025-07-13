<?php
/**
 * Template Functions - глобални функции за template файлове
 * ПЪЛЕН ФАЙЛ С ВСИЧКИ НУЖНИ ФУНКЦИИ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Проверява дали сме на парфюмна страница
 */
function parfume_reviews_is_parfume_page() {
    return is_singular('parfume') || 
           is_post_type_archive('parfume') || 
           is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
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
    $rating = get_post_meta($post_id, '_parfume_rating', true);
    $availability = get_post_meta($post_id, '_parfume_availability', true);
    $shipping = parfume_reviews_get_shipping_info($post_id);
    
    ?>
    <article class="parfume-card" data-id="<?php echo esc_attr($post_id); ?>">
        <?php if (!empty($settings['card_show_image']) && has_post_thumbnail($post_id)): ?>
        <div class="parfume-image">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php echo get_the_post_thumbnail($post_id, 'medium', array('alt' => get_the_title($post_id))); ?>
            </a>
        </div>
        <?php endif; ?>
        
        <div class="parfume-content">
            <h3 class="parfume-title">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo get_the_title($post_id); ?></a>
            </h3>
            
            <?php if (!empty($settings['card_show_brand']) && $brand_name): ?>
            <div class="parfume-brand"><?php echo esc_html($brand_name); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($rating)): ?>
                <div class="parfume-rating">
                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                    <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_price'])): ?>
                <?php 
                $lowest_store = parfume_reviews_get_lowest_price($post_id);
                if ($lowest_store): ?>
                    <div class="parfume-price">
                        <span class="price-label"><?php _e('от:', 'parfume-reviews'); ?></span>
                        <span class="price-value"><?php echo esc_html($lowest_store['price']); ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_availability']) && $availability): ?>
            <div class="parfume-availability">
                <?php
                switch ($availability) {
                    case 'available':
                        _e('В наличност', 'parfume-reviews');
                        break;
                    case 'limited':
                        _e('Ограничено количество', 'parfume-reviews');
                        break;
                    case 'discontinued':
                        _e('Прекратен', 'parfume-reviews');
                        break;
                    case 'preorder':
                        _e('Предварителна поръчка', 'parfume-reviews');
                        break;
                    default:
                        echo esc_html($availability);
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_shipping']) && $shipping): ?>
            <div class="parfume-shipping">
                <?php echo esc_html($shipping); ?>
            </div>
            <?php endif; ?>
            
            <div class="parfume-actions">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="button view-parfume">
                    <?php _e('Виж детайли', 'parfume-reviews'); ?>
                </a>
                <button type="button" class="button compare-add" data-id="<?php echo esc_attr($post_id); ?>">
                    <?php _e('Сравни', 'parfume-reviews'); ?>
                </button>
            </div>
        </div>
    </article>
    <?php
}

/**
 * Показва звезди за рейтинг - използва се в карточки
 */
function parfume_reviews_display_stars($rating, $max_rating = 5) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max_rating - $full_stars - ($half_star ? 1 : 0);
    
    echo '<div class="star-rating">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        echo '<span class="star full">★</span>';
    }
    
    // Half star
    if ($half_star) {
        echo '<span class="star half">★</span>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        echo '<span class="star empty">☆</span>';
    }
    
    echo '</div>';
}

/**
 * Връща HTML за звездния рейтинг (за template файлове)
 */
function parfume_reviews_get_rating_stars($rating, $max_rating = 5) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max_rating - $full_stars - ($half_star ? 1 : 0);
    
    $output = '<div class="star-rating">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<span class="star full">★</span>';
    }
    
    // Half star
    if ($half_star) {
        $output .= '<span class="star half">★</span>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<span class="star empty">☆</span>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Показва информация за нотки
 */
function parfume_reviews_display_notes($post_id) {
    $top_notes = get_post_meta($post_id, '_top_notes', true);
    $middle_notes = get_post_meta($post_id, '_middle_notes', true);
    $base_notes = get_post_meta($post_id, '_base_notes', true);
    
    if (!$top_notes && !$middle_notes && !$base_notes) {
        return;
    }
    
    ?>
    <div class="parfume-notes">
        <h3><?php _e('Нотки', 'parfume-reviews'); ?></h3>
        
        <?php if ($top_notes): ?>
        <div class="notes-section">
            <h4><?php _e('Горни нотки', 'parfume-reviews'); ?></h4>
            <p><?php echo esc_html($top_notes); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($middle_notes): ?>
        <div class="notes-section">
            <h4><?php _e('Средни нотки', 'parfume-reviews'); ?></h4>
            <p><?php echo esc_html($middle_notes); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($base_notes): ?>
        <div class="notes-section">
            <h4><?php _e('Базови нотки', 'parfume-reviews'); ?></h4>
            <p><?php echo esc_html($base_notes); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Показва информация за цени
 */
function parfume_reviews_display_price_info($post_id) {
    $prices = array(
        'parfium' => get_post_meta($post_id, '_price_parfium', true),
        'douglas' => get_post_meta($post_id, '_price_douglas', true),
        'notino' => get_post_meta($post_id, '_price_notino', true),
    );
    
    $prices = array_filter($prices);
    
    if (empty($prices)) {
        return;
    }
    
    ?>
    <div class="parfume-prices">
        <h3><?php _e('Цени в магазини', 'parfume-reviews'); ?></h3>
        <div class="price-comparison">
            <?php foreach ($prices as $store => $price): ?>
            <div class="price-item">
                <span class="store-name"><?php echo esc_html(ucfirst($store)); ?></span>
                <span class="store-price"><?php echo esc_html($price); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Генерира кратко описание на парфюм
 */
function parfume_reviews_get_excerpt($post_id, $length = 150) {
    $post = get_post($post_id);
    if (!$post) {
        return '';
    }
    
    $excerpt = $post->post_excerpt;
    if (!$excerpt) {
        $excerpt = $post->post_content;
    }
    
    $excerpt = wp_strip_all_tags($excerpt);
    
    if (strlen($excerpt) > $length) {
        $excerpt = substr($excerpt, 0, $length);
        $excerpt = rtrim($excerpt, " \t\n\r\0\x0B.,;") . '...';
    }
    
    return $excerpt;
}

/**
 * Показва филтри за архивните страници
 */
function parfume_reviews_display_archive_filters() {
    $settings = get_option('parfume_reviews_settings', array());
    $show_sidebar = isset($settings['show_archive_sidebar']) ? $settings['show_archive_sidebar'] : 1;
    
    if (!$show_sidebar) {
        return;
    }
    
    // Получаваме активните филтри
    $active_filters = parfume_reviews_get_active_filters();
    $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    
    ?>
    <div class="parfume-filters-sidebar">
        <form class="parfume-filters-form" method="get">
            <h3><?php _e('Филтри', 'parfume-reviews'); ?></h3>
            
            <?php foreach ($supported_taxonomies as $taxonomy): ?>
                <?php
                $taxonomy_obj = get_taxonomy($taxonomy);
                if (!$taxonomy_obj) continue;
                
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'number' => 50
                ));
                
                if (empty($terms) || is_wp_error($terms)) continue;
                ?>
                
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php echo esc_html($taxonomy_obj->labels->name); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <?php foreach ($terms as $term): ?>
                            <label class="filter-option">
                                <input type="checkbox" 
                                       name="<?php echo esc_attr($taxonomy); ?>[]" 
                                       value="<?php echo esc_attr($term->slug); ?>"
                                       <?php checked(in_array($term->slug, isset($active_filters[$taxonomy]) ? $active_filters[$taxonomy] : array())); ?>>
                                <span class="checkmark"></span>
                                <?php echo esc_html($term->name); ?>
                                <span class="term-count">(<?php echo $term->count; ?>)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Price Range Filter -->
            <div class="filter-section">
                <h4 class="filter-title">
                    <?php _e('Ценови диапазон', 'parfume-reviews'); ?>
                    <span class="toggle-arrow">▼</span>
                </h4>
                <div class="filter-options">
                    <div class="price-range-inputs">
                        <input type="number" name="min_price" placeholder="<?php _e('Мин. цена', 'parfume-reviews'); ?>" 
                               value="<?php echo isset($_GET['min_price']) ? esc_attr($_GET['min_price']) : ''; ?>" min="0" step="0.01">
                        <span class="price-separator">-</span>
                        <input type="number" name="max_price" placeholder="<?php _e('Макс. цена', 'parfume-reviews'); ?>" 
                               value="<?php echo isset($_GET['max_price']) ? esc_attr($_GET['max_price']) : ''; ?>" min="0" step="0.01">
                    </div>
                </div>
            </div>
            
            <!-- Rating Filter -->
            <div class="filter-section">
                <h4 class="filter-title">
                    <?php _e('Минимален рейтинг', 'parfume-reviews'); ?>
                    <span class="toggle-arrow">▼</span>
                </h4>
                <div class="filter-options">
                    <select name="min_rating">
                        <option value=""><?php _e('Всички рейтинги', 'parfume-reviews'); ?></option>
                        <option value="4" <?php selected(isset($_GET['min_rating']) ? $_GET['min_rating'] : '', '4'); ?>>4+ звезди</option>
                        <option value="3" <?php selected(isset($_GET['min_rating']) ? $_GET['min_rating'] : '', '3'); ?>>3+ звезди</option>
                        <option value="2" <?php selected(isset($_GET['min_rating']) ? $_GET['min_rating'] : '', '2'); ?>>2+ звезди</option>
                        <option value="1" <?php selected(isset($_GET['min_rating']) ? $_GET['min_rating'] : '', '1'); ?>>1+ звезди</option>
                    </select>
                </div>
            </div>
            
            <!-- Sorting -->
            <div class="filter-section">
                <h4 class="filter-title">
                    <?php _e('Сортиране', 'parfume-reviews'); ?>
                    <span class="toggle-arrow">▼</span>
                </h4>
                <div class="filter-options">
                    <select name="orderby">
                        <option value="date" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'date'); ?>><?php _e('Най-нови', 'parfume-reviews'); ?></option>
                        <option value="title" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'title'); ?>><?php _e('По име', 'parfume-reviews'); ?></option>
                        <option value="rating" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'rating'); ?>><?php _e('По рейтинг', 'parfume-reviews'); ?></option>
                        <option value="price" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'price'); ?>><?php _e('По цена', 'parfume-reviews'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="button button-primary"><?php _e('Приложи филтри', 'parfume-reviews'); ?></button>
                <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="button button-secondary"><?php _e('Изчисти', 'parfume-reviews'); ?></a>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Показва активните филтри
 */
function parfume_reviews_display_active_filters() {
    $active_filters = parfume_reviews_get_active_filters();
    
    if (empty($active_filters)) {
        return;
    }
    
    ?>
    <div class="active-filters">
        <h4><?php _e('Активни филтри:', 'parfume-reviews'); ?></h4>
        <div class="filter-tags">
            <?php foreach ($active_filters as $filter_type => $filter_values): ?>
                <?php if (is_array($filter_values)): ?>
                    <?php foreach ($filter_values as $value): ?>
                        <?php
                        $remove_filters = $active_filters;
                        $key = array_search($value, $remove_filters[$filter_type]);
                        if ($key !== false) {
                            unset($remove_filters[$filter_type][$key]);
                            if (empty($remove_filters[$filter_type])) {
                                unset($remove_filters[$filter_type]);
                            }
                        }
                        $remove_url = parfume_reviews_build_filter_url($remove_filters);
                        
                        $term = get_term_by('slug', $value, $filter_type);
                        $display_name = $term ? $term->name : $value;
                        ?>
                        <span class="filter-tag">
                            <?php echo esc_html($display_name); ?>
                            <a href="<?php echo esc_url($remove_url); ?>" class="remove-filter" aria-label="<?php _e('Премахни филтър', 'parfume-reviews'); ?>">×</a>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php
                    $remove_filters = $active_filters;
                    unset($remove_filters[$filter_type]);
                    $remove_url = parfume_reviews_build_filter_url($remove_filters);
                    ?>
                    <span class="filter-tag">
                        <?php echo esc_html($filter_type . ': ' . $filter_values); ?>
                        <a href="<?php echo esc_url($remove_url); ?>" class="remove-filter" aria-label="<?php _e('Премахни филтър', 'parfume-reviews'); ?>">×</a>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Показва debug информация за филтри (само в WP_DEBUG режим)
 */
function parfume_reviews_display_filters_debug() {
    if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('manage_options')) {
        return;
    }
    
    $active_filters = parfume_reviews_get_active_filters();
    
    if (!empty($active_filters)) {
        echo '<div class="filters-debug" style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
        echo '<strong>Debug - Active Filters:</strong>';
        echo '<pre>' . print_r($active_filters, true) . '</pre>';
        echo '</div>';
    }
}

/**
 * Показва mobile филтърен toggle бутон
 */
function parfume_reviews_display_mobile_filter_toggle() {
    ?>
    <button class="mobile-filters-toggle" aria-label="<?php _e('Отвори филтри', 'parfume-reviews'); ?>">
        <span class="toggle-icon">⚙️</span>
        <span class="toggle-text"><?php _e('Филтри', 'parfume-reviews'); ?></span>
    </button>
    <?php
}

/**
 * Връща HTML за comparison бутон
 */
function parfume_reviews_get_comparison_button($post_id) {
    return '<button type="button" class="button compare-add" data-id="' . esc_attr($post_id) . '">' . 
           __('Сравни', 'parfume-reviews') . 
           '</button>';
}

/**
 * Връща HTML за collections dropdown
 */
function parfume_reviews_get_collections_dropdown($post_id) {
    return '<button type="button" class="button collections-add" data-id="' . esc_attr($post_id) . '">' . 
           __('Добави в колекция', 'parfume-reviews') . 
           '</button>';
}

/**
 * Получава meta информация за SEO
 */
function parfume_reviews_get_archive_meta() {
    $meta = array();
    
    if (is_post_type_archive('parfume')) {
        $meta['title'] = __('Всички парфюми - Най-добрите аромати', 'parfume-reviews');
        $meta['description'] = __('Открийте най-добрите парфюми с нашите подробни ревюта и сравнения. Филтрирайте по марка, нотки, сезон и още.', 'parfume-reviews');
    } elseif (is_tax()) {
        $queried_object = get_queried_object();
        if ($queried_object) {
            $meta['title'] = sprintf(__('%s - Парфюми', 'parfume-reviews'), $queried_object->name);
            $meta['description'] = $queried_object->description ? $queried_object->description : sprintf(__('Парфюми от категория %s. Открийте най-добрите аромати.', 'parfume-reviews'), $queried_object->name);
        }
    }
    
    return $meta;
}

/**
 * НОВИ ФУНКЦИИ ЗА STORES ДАННИ
 */

/**
 * Получава най-ниската цена от stores данните
 */
function parfume_reviews_get_lowest_price($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    $lowest_price = null;
    $lowest_store = null;
    
    foreach ($stores as $store) {
        if (empty($store['name'])) {
            continue;
        }
        
        // Check variants if they exist
        if (!empty($store['variants']) && is_array($store['variants'])) {
            foreach ($store['variants'] as $variant) {
                if (!empty($variant['price'])) {
                    $price = parfume_reviews_extract_price_number($variant['price']);
                    if ($price > 0 && ($lowest_price === null || $price < $lowest_price)) {
                        $lowest_price = $price;
                        $lowest_store = array(
                            'name' => $store['name'],
                            'price' => $variant['price'],
                            'size' => isset($variant['size']) ? $variant['size'] : '',
                            'url' => isset($store['affiliate_url']) ? $store['affiliate_url'] : '',
                        );
                    }
                }
            }
        } else {
            // Single price
            if (!empty($store['price'])) {
                $price = parfume_reviews_extract_price_number($store['price']);
                if ($price > 0 && ($lowest_price === null || $price < $lowest_price)) {
                    $lowest_price = $price;
                    $lowest_store = array(
                        'name' => $store['name'],
                        'price' => $store['price'],
                        'size' => isset($store['size']) ? $store['size'] : '',
                        'url' => isset($store['affiliate_url']) ? $store['affiliate_url'] : '',
                    );
                }
            }
        }
    }
    
    return $lowest_store;
}

/**
 * Извлича числовата стойност от цена стринг
 */
function parfume_reviews_extract_price_number($price_string) {
    // Remove currency symbols and extract number
    $price = preg_replace('/[^\d.,]/', '', $price_string);
    $price = str_replace(',', '.', $price);
    return floatval($price);
}

/**
 * Проверява дали парфюм е наличен
 */
function parfume_reviews_is_available($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    foreach ($stores as $store) {
        if (!empty($store['availability'])) {
            $availability = strtolower($store['availability']);
            if (in_array($availability, array('в наличност', 'available', 'наличен', 'в склад'))) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Получава информация за доставка
 */
function parfume_reviews_get_shipping_info($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return '';
    }
    
    foreach ($stores as $store) {
        if (!empty($store['shipping_info'])) {
            return $store['shipping_info'];
        }
    }
    
    return '';
}

/**
 * Показва store карта в архивните страници
 */
function parfume_reviews_display_store_card($store_data) {
    if (empty($store_data) || !is_array($store_data)) {
        return;
    }
    
    ?>
    <div class="store-card">
        <div class="store-header">
            <h4 class="store-name"><?php echo esc_html($store_data['name']); ?></h4>
            <div class="store-price"><?php echo esc_html($store_data['price']); ?></div>
        </div>
        
        <?php if (!empty($store_data['size'])): ?>
            <div class="store-size"><?php echo esc_html($store_data['size']); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($store_data['url'])): ?>
            <a href="<?php echo esc_url($store_data['url']); ?>" target="_blank" rel="nofollow" class="store-button">
                <?php _e('Към магазина', 'parfume-reviews'); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Получава всички магазини за парфюм
 */
function parfume_reviews_get_all_stores($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return array();
    }
    
    $formatted_stores = array();
    
    foreach ($stores as $store) {
        if (!empty($store['name'])) {
            $formatted_stores[] = wp_parse_args($store, array(
                'name' => '',
                'logo' => '',
                'price' => '',
                'original_price' => '',
                'discount' => '',
                'availability' => '',
                'shipping_info' => '',
                'size' => '',
                'affiliate_url' => '',
                'promo_code' => '',
                'promo_code_info' => '',
                'promo_url' => '',
                'variants' => array(),
                'features' => array(),
            ));
        }
    }
    
    return $formatted_stores;
}

/**
 * Проверява дали има промоции в stores
 */
function parfume_reviews_has_promotions($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    foreach ($stores as $store) {
        // Check for promo code
        if (!empty($store['promo_code'])) {
            return true;
        }
        
        // Check for discount
        if (!empty($store['original_price']) && !empty($store['price'])) {
            $original = parfume_reviews_extract_price_number($store['original_price']);
            $current = parfume_reviews_extract_price_number($store['price']);
            if ($original > $current) {
                return true;
            }
        }
        
        // Check for variant promotions
        if (!empty($store['variants']) && is_array($store['variants'])) {
            foreach ($store['variants'] as $variant) {
                if (!empty($variant['has_discount']) || !empty($variant['has_promotion'])) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * Получава средната цена за парфюм
 */
function parfume_reviews_get_average_price($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return 0;
    }
    
    $prices = array();
    
    foreach ($stores as $store) {
        if (!empty($store['variants']) && is_array($store['variants'])) {
            foreach ($store['variants'] as $variant) {
                if (!empty($variant['price'])) {
                    $price = parfume_reviews_extract_price_number($variant['price']);
                    if ($price > 0) {
                        $prices[] = $price;
                    }
                }
            }
        } elseif (!empty($store['price'])) {
            $price = parfume_reviews_extract_price_number($store['price']);
            if ($price > 0) {
                $prices[] = $price;
            }
        }
    }
    
    if (empty($prices)) {
        return 0;
    }
    
    return array_sum($prices) / count($prices);
}

/**
 * Форматира цена за показване
 */
function parfume_reviews_format_price($price, $currency = 'лв') {
    if (is_numeric($price)) {
        return number_format($price, 2) . ' ' . $currency;
    }
    
    return $price;
}



/**
 * КРИТИЧНА ПОПРАВКА ЗА ФАТАЛНА ГРЕШКА
 * 
 * ИНСТРУКЦИИ ЗА ДОБАВЯНЕ:
 * 1. Отворете файла includes/template-functions.php
 * 2. Идете в самия край на файла (след последната функция parfume_reviews_format_price)
 * 3. ДОБАВЕТЕ следния код в края на файла преди затварящия PHP таг ?>
 * 
 * НЕ ЗАМЕНЯЙТЕ НИЩО - САМО ДОБАВЕТЕ В КРАЯ!
 */

/**
 * КРИТИЧНА ПОПРАВКА: Добавяне на липсващата функция
 * Тази функция се използва в templates/archive-perfumer.php на ред 136
 * 
 * Получава най-евтината доставка за парфюм
 */
function parfume_reviews_get_cheapest_shipping($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return '';
    }
    
    $shipping_options = array();
    
    foreach ($stores as $store) {
        if (!empty($store['shipping_info'])) {
            // Извличаме цена от shipping информацията
            $shipping_text = $store['shipping_info'];
            
            // Търсим цени в shipping текста
            if (preg_match('/(\d+[\.,]?\d*)\s*(лв|bgn|eur|€)/i', $shipping_text, $matches)) {
                $price = floatval(str_replace(',', '.', $matches[1]));
                $shipping_options[] = array(
                    'text' => $shipping_text,
                    'price' => $price,
                    'store' => isset($store['name']) ? $store['name'] : ''
                );
            } else {
                // Ако няма цена, добавяме като безплатна доставка или специална информация
                if (stripos($shipping_text, 'безплатна') !== false || 
                    stripos($shipping_text, 'free') !== false ||
                    stripos($shipping_text, 'безплатно') !== false) {
                    $shipping_options[] = array(
                        'text' => $shipping_text,
                        'price' => 0,
                        'store' => isset($store['name']) ? $store['name'] : ''
                    );
                } else {
                    // Просто добавяме текста без цена
                    $shipping_options[] = array(
                        'text' => $shipping_text,
                        'price' => 999999, // Висока цена за несортирани опции
                        'store' => isset($store['name']) ? $store['name'] : ''
                    );
                }
            }
        }
    }
    
    if (empty($shipping_options)) {
        return '';
    }
    
    // Сортираме по цена (най-евтина първо)
    usort($shipping_options, function($a, $b) {
        return $a['price'] <=> $b['price'];
    });
    
    // Връщаме най-евтината опция
    return $shipping_options[0]['text'];
}

/**
 * БОНУС ФУНКЦИЯ: Получава най-евтината доставка с детайли
 */
function parfume_reviews_get_cheapest_shipping_details($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    $cheapest_shipping = null;
    $lowest_price = null;
    
    foreach ($stores as $store) {
        if (!empty($store['shipping_info'])) {
            $shipping_text = $store['shipping_info'];
            
            // Извличаме цена от shipping информацията
            if (preg_match('/(\d+[\.,]?\d*)\s*(лв|bgn|eur|€)/i', $shipping_text, $matches)) {
                $price = floatval(str_replace(',', '.', $matches[1]));
                
                if ($lowest_price === null || $price < $lowest_price) {
                    $lowest_price = $price;
                    $cheapest_shipping = array(
                        'text' => $shipping_text,
                        'price' => $price,
                        'store' => isset($store['name']) ? $store['name'] : '',
                        'formatted_price' => number_format($price, 2) . ' лв'
                    );
                }
            } elseif (stripos($shipping_text, 'безплатна') !== false || 
                     stripos($shipping_text, 'free') !== false ||
                     stripos($shipping_text, 'безплатно') !== false) {
                // Безплатна доставка е най-евтина
                $cheapest_shipping = array(
                    'text' => $shipping_text,
                    'price' => 0,
                    'store' => isset($store['name']) ? $store['name'] : '',
                    'formatted_price' => 'Безплатна'
                );
                $lowest_price = 0;
                break; // Не може да бъде по-евтино от безплатно
            }
        }
    }
    
    return $cheapest_shipping;
}
/**
 * Генерира breadcrumbs за parfume страници
 */
function parfume_reviews_breadcrumbs() {
    if (!parfume_reviews_is_parfume_page()) {
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
        
        $brands = wp_get_post_terms(get_the_ID(), 'marki');
        if (!empty($brands)) {
            $breadcrumbs[] = array(
                'title' => $brands[0]->name,
                'url' => get_term_link($brands[0])
            );
        }
        
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
        $taxonomy_obj = get_taxonomy($queried_object->taxonomy);
        
        $breadcrumbs[] = array(
            'title' => __('Парфюми', 'parfume-reviews'),
            'url' => get_post_type_archive_link('parfume')
        );
        
        $breadcrumbs[] = array(
            'title' => $taxonomy_obj->labels->name,
            'url' => get_term_link($queried_object)
        );
        
        $breadcrumbs[] = array(
            'title' => $queried_object->name,
            'url' => ''
        );
    }
    
    if (count($breadcrumbs) <= 1) {
        return;
    }
    
    ?>
    <nav class="parfume-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumbs', 'parfume-reviews'); ?>">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                <li class="breadcrumb-item <?php echo $index === count($breadcrumbs) - 1 ? 'active' : ''; ?>">
                    <?php if (!empty($breadcrumb['url'])): ?>
                        <a href="<?php echo esc_url($breadcrumb['url']); ?>"><?php echo esc_html($breadcrumb['title']); ?></a>
                    <?php else: ?>
                        <span><?php echo esc_html($breadcrumb['title']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <span class="breadcrumb-separator">/</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}