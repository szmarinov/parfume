<?php
/**
 * Template Functions - глобални функции за template файлове
 * ПЪЛЕН ФАЙЛ С ВСИЧКИ НУЖНИ ФУНКЦИИ
 * 
 * Файл: includes/template-functions.php
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
        <ul class="price-list">
            <?php foreach ($prices as $store => $price): ?>
                <li class="price-item">
                    <span class="store-name"><?php echo esc_html(ucfirst($store)); ?>:</span>
                    <span class="store-price"><?php echo esc_html($price); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

/**
 * Получава най-ниската цена за парфюм
 */
function parfume_reviews_get_lowest_price($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    $lowest_price = null;
    $lowest_store = null;
    
    foreach ($stores as $store) {
        if (!empty($store['variants']) && is_array($store['variants'])) {
            foreach ($store['variants'] as $variant) {
                if (!empty($variant['price'])) {
                    $price = parfume_reviews_extract_price_number($variant['price']);
                    if ($price > 0 && ($lowest_price === null || $price < $lowest_price)) {
                        $lowest_price = $price;
                        $lowest_store = array(
                            'name' => isset($store['name']) ? $store['name'] : '',
                            'price' => $variant['price'],
                            'size' => isset($variant['size']) ? $variant['size'] : '',
                            'url' => isset($store['affiliate_url']) ? $store['affiliate_url'] : '',
                        );
                    }
                }
            }
        } elseif (!empty($store['price'])) {
            $price = parfume_reviews_extract_price_number($store['price']);
            if ($price > 0 && ($lowest_price === null || $price < $lowest_price)) {
                $lowest_price = $price;
                $lowest_store = array(
                    'name' => isset($store['name']) ? $store['name'] : '',
                    'price' => $store['price'],
                    'size' => isset($store['size']) ? $store['size'] : '',
                    'url' => isset($store['affiliate_url']) ? $store['affiliate_url'] : '',
                );
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
            <a href="<?php echo esc_url($store_data['url']); ?>" target="_blank" rel="nofollow" class="store-link">
                <?php _e('Виж в магазина', 'parfume-reviews'); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Проверява дали има промоция за парфюм
 */
function parfume_reviews_has_promotion($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    
    if (empty($stores) || !is_array($stores)) {
        return false;
    }
    
    foreach ($stores as $store) {
        // Check for discount flag
        if (!empty($store['has_discount']) || !empty($store['has_promotion'])) {
            return true;
        }
        
        // Check for original vs current price
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
 * ПОПРАВКА - Получава най-евтината доставка за парфюм
 * Тази функция се използва в taxonomy-perfumer.php
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
    
    // Сортираме по цена
    if (!empty($shipping_options)) {
        usort($shipping_options, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        return $shipping_options[0]['text'];
    }
    
    return '';
}

/**
 * Получава всички термини от дадена таксономия
 */
function parfume_reviews_get_taxonomy_terms($taxonomy, $args = array()) {
    $default_args = array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    );
    
    $args = wp_parse_args($args, $default_args);
    
    return get_terms($args);
}

/**
 * Показва таксономични връзки за парфюм
 */
function parfume_reviews_display_taxonomy_links($post_id, $taxonomy, $label = '') {
    $terms = wp_get_post_terms($post_id, $taxonomy);
    
    if (empty($terms) || is_wp_error($terms)) {
        return;
    }
    
    if (empty($label)) {
        $tax_obj = get_taxonomy($taxonomy);
        $label = $tax_obj->labels->name;
    }
    
    ?>
    <div class="parfume-taxonomy-links taxonomy-<?php echo esc_attr($taxonomy); ?>">
        <span class="taxonomy-label"><?php echo esc_html($label); ?>:</span>
        <div class="taxonomy-terms">
            <?php foreach ($terms as $term): ?>
                <a href="<?php echo esc_url(get_term_link($term)); ?>" class="taxonomy-term">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Получава броя парфюми в дадена таксономия
 */
function parfume_reviews_get_taxonomy_count($taxonomy) {
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
        'fields' => 'count'
    ));
    
    return is_array($terms) ? array_sum($terms) : 0;
}

/**
 * Проверява дали термин има изображение
 */
function parfume_reviews_term_has_image($term_id) {
    $image = get_term_meta($term_id, 'taxonomy_image', true);
    return !empty($image);
}

/**
 * Получава URL на изображението на термин
 */
function parfume_reviews_get_term_image_url($term_id, $size = 'medium') {
    $image_id = get_term_meta($term_id, 'taxonomy_image_id', true);
    
    if ($image_id) {
        return wp_get_attachment_image_url($image_id, $size);
    }
    
    // Fallback към URL path
    $image_url = get_term_meta($term_id, 'taxonomy_image', true);
    return !empty($image_url) ? $image_url : false;
}

/**
 * Показва хлебните трохи за таксономични страници
 */
function parfume_reviews_display_taxonomy_breadcrumbs($current_term = null) {
    ?>
    <nav class="parfume-breadcrumbs">
        <div class="breadcrumb-trail">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">
                <?php _e('Начало', 'parfume-reviews'); ?>
            </a>
            <span class="breadcrumb-separator"> › </span>
            
            <a href="<?php echo esc_url(home_url('/parfiumi/')); ?>" class="breadcrumb-link">
                <?php _e('Парфюми', 'parfume-reviews'); ?>
            </a>
            <span class="breadcrumb-separator"> › </span>
            
            <?php if ($current_term && isset($current_term->taxonomy)): ?>
                <?php
                $taxonomy_obj = get_taxonomy($current_term->taxonomy);
                $taxonomy_archive_url = '';
                
                // Специални URL-ти за различните таксономии
                switch ($current_term->taxonomy) {
                    case 'perfumer':
                        $taxonomy_archive_url = home_url('/parfiumi/parfumeri/');
                        break;
                    case 'marki':
                        $taxonomy_archive_url = home_url('/parfiumi/marki/');
                        break;
                    case 'notes':
                        $taxonomy_archive_url = home_url('/parfiumi/notes/');
                        break;
                    default:
                        $taxonomy_archive_url = home_url('/parfiumi/');
                        break;
                }
                ?>
                
                <a href="<?php echo esc_url($taxonomy_archive_url); ?>" class="breadcrumb-link">
                    <?php echo esc_html($taxonomy_obj->labels->name); ?>
                </a>
                <span class="breadcrumb-separator"> › </span>
                
                <span class="breadcrumb-current"><?php echo esc_html($current_term->name); ?></span>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}

/**
 * Проверява дали има парфюми в дадена таксономия
 */
function parfume_reviews_taxonomy_has_parfumes($taxonomy, $term_slug = '') {
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_post_status',
                'value' => 'publish',
                'compare' => '='
            )
        )
    );
    
    if (!empty($term_slug)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $term_slug
            )
        );
    }
    
    $query = new WP_Query($args);
    return $query->have_posts();
}

/**
 * Получава популярните термини от таксономия
 */
function parfume_reviews_get_popular_terms($taxonomy, $limit = 10) {
    return get_terms(array(
        'taxonomy' => $taxonomy,
        'orderby' => 'count',
        'order' => 'DESC',
        'number' => $limit,
        'hide_empty' => true
    ));
}

/**
 * Форматира описанието на термин
 */
function parfume_reviews_format_term_description($description, $length = 150) {
    if (empty($description)) {
        return '';
    }
    
    // Премахваме HTML тагове
    $description = wp_strip_all_tags($description);
    
    // Съкращаваме ако е прекалено дълъг
    if (strlen($description) > $length) {
        $description = substr($description, 0, $length) . '...';
    }
    
    return $description;
}

/**
 * Генерира excerpt за парфюм
 */
function parfume_reviews_get_parfume_excerpt($post_id, $length = 150) {
    $post = get_post($post_id);
    
    if (!$post) {
        return '';
    }
    
    $excerpt = '';
    
    // Първо опитваме excerpt полето
    if (!empty($post->post_excerpt)) {
        $excerpt = $post->post_excerpt;
    } else {
        // После content-а
        $excerpt = $post->post_content;
    }
    
    // Почистваме HTML
    $excerpt = wp_strip_all_tags($excerpt);
    
    // Съкращаваме
    if (strlen($excerpt) > $length) {
        $excerpt = substr($excerpt, 0, $length) . '...';
    }
    
    return $excerpt;
}

/**
 * Проверява дали парфюм е нов (създаден в последните X дни)
 */
function parfume_reviews_is_new_parfume($post_id, $days = 30) {
    $post = get_post($post_id);
    
    if (!$post) {
        return false;
    }
    
    $post_date = strtotime($post->post_date);
    $cutoff_date = strtotime("-{$days} days");
    
    return $post_date > $cutoff_date;
}

/**
 * Получава цветовия код за наличност
 */
function parfume_reviews_get_availability_color($availability) {
    switch (strtolower($availability)) {
        case 'available':
        case 'в наличност':
        case 'наличен':
            return '#28a745'; // Зелено
        case 'limited':
        case 'ограничено количество':
            return '#ffc107'; // Жълто
        case 'discontinued':
        case 'прекратен':
            return '#dc3545'; // Червено
        case 'preorder':
        case 'предварителна поръчка':
            return '#17a2b8'; // Синьо
        default:
            return '#6c757d'; // Сиво
    }
}

/**
 * Получава иконката за наличност
 */
function parfume_reviews_get_availability_icon($availability) {
    switch (strtolower($availability)) {
        case 'available':
        case 'в наличност':
        case 'наличен':
            return '✓';
        case 'limited':
        case 'ограничено количество':
            return '⚠';
        case 'discontinued':
        case 'прекратен':
            return '✗';
        case 'preorder':
        case 'предварителна поръчка':
            return '⏰';
        default:
            return '?';
    }
}

/**
 * Показва статус на наличност с цвят и икона
 */
function parfume_reviews_display_availability_status($availability) {
    if (empty($availability)) {
        return;
    }
    
    $color = parfume_reviews_get_availability_color($availability);
    $icon = parfume_reviews_get_availability_icon($availability);
    
    ?>
    <span class="availability-status" style="color: <?php echo esc_attr($color); ?>;">
        <span class="availability-icon"><?php echo esc_html($icon); ?></span>
        <span class="availability-text"><?php echo esc_html($availability); ?></span>
    </span>
    <?php
}

/**
 * Генерира structured data за парфюм
 */
function parfume_reviews_get_parfume_structured_data($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'parfume') {
        return array();
    }
    
    $structured_data = array(
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => get_the_title($post_id),
        'description' => parfume_reviews_get_parfume_excerpt($post_id),
        'url' => get_permalink($post_id),
    );
    
    // Добавяме изображение
    if (has_post_thumbnail($post_id)) {
        $structured_data['image'] = get_the_post_thumbnail_url($post_id, 'large');
    }
    
    // Добавяме марка
    $brand = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
    if (!empty($brand)) {
        $structured_data['brand'] = array(
            '@type' => 'Brand',
            'name' => $brand[0]
        );
    }
    
    // Добавяме рейтинг
    $rating = get_post_meta($post_id, '_parfume_rating', true);
    if (!empty($rating)) {
        $structured_data['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $rating,
            'bestRating' => '5',
            'worstRating' => '1'
        );
    }
    
    // Добавяме цена
    $lowest_store = parfume_reviews_get_lowest_price($post_id);
    if ($lowest_store) {
        $price = parfume_reviews_extract_price_number($lowest_store['price']);
        if ($price > 0) {
            $structured_data['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'BGN',
                'availability' => 'https://schema.org/InStock',
                'url' => isset($lowest_store['url']) ? $lowest_store['url'] : get_permalink($post_id)
            );
        }
    }
    
    return $structured_data;
}

/**
 * Изписва structured data за парфюм
 */
function parfume_reviews_output_parfume_structured_data($post_id) {
    $structured_data = parfume_reviews_get_parfume_structured_data($post_id);
    
    if (!empty($structured_data)) {
        echo '<script type="application/ld+json">';
        echo wp_json_encode($structured_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo '</script>';
    }
}