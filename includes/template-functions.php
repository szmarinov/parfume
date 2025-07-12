<?php
/**
 * Template Functions - глобални функции за template файлове
 * АКТУАЛИЗИРАН: Добавена липсващата parfume_reviews_get_rating_stars() функция
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
 * ПОПРАВЕНА ФУНКЦИЯ - Построява URL за филтри
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
    $shipping = get_post_meta($post_id, '_parfume_shipping', true);
    
    ?>
    <article class="parfume-card" data-id="<?php echo esc_attr($post_id); ?>">
        <?php if (has_post_thumbnail($post_id)): ?>
        <div class="parfume-thumbnail">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php echo get_the_post_thumbnail($post_id, 'medium', array('class' => 'parfume-image')); ?>
            </a>
            
            <?php if (!empty($settings['card_show_wishlist'])): ?>
            <button type="button" class="wishlist-toggle" data-id="<?php echo esc_attr($post_id); ?>" title="<?php _e('Добави в любими', 'parfume-reviews'); ?>">
                <span class="heart-icon">♡</span>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="parfume-content">
            <h3 class="parfume-title">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <?php echo esc_html(get_the_title($post_id)); ?>
                </a>
            </h3>
            
            <?php if (!empty($settings['card_show_brand']) && $brand_name): ?>
            <div class="parfume-brand">
                <?php echo esc_html($brand_name); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_rating']) && $rating): ?>
            <div class="parfume-rating">
                <?php parfume_reviews_display_stars($rating); ?>
                <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_price']) && $price): ?>
            <div class="parfume-price">
                <?php echo esc_html($price); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_availability']) && $availability): ?>
            <div class="parfume-availability availability-<?php echo esc_attr($availability); ?>">
                <?php 
                switch($availability) {
                    case 'in_stock':
                        _e('В наличност', 'parfume-reviews');
                        break;
                    case 'out_of_stock':
                        _e('Изчерпан', 'parfume-reviews');
                        break;
                    case 'pre_order':
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
 * НОВА ФУНКЦИЯ - Връща HTML за звездния рейтинг (за template файлове)
 * Тази функция се използва в taxonomy template файловете
 */
function parfume_reviews_get_rating_stars($rating, $max_rating = 5) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max_rating - $full_stars - ($half_star ? 1 : 0);
    
    $output = '';
    
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
    $active_filters = array();
    $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    
    foreach ($supported_taxonomies as $taxonomy) {
        if (!empty($_GET[$taxonomy])) {
            $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
            $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
        }
    }
    
    // Получаваме текущия URL за формата
    $current_url = '';
    if (is_post_type_archive('parfume')) {
        $current_url = get_post_type_archive_link('parfume');
    } elseif (is_tax()) {
        $current_url = get_term_link(get_queried_object());
    } else {
        $current_url = home_url('/parfiumi/');
    }
    
    ?>
    <div class="parfume-filters-wrapper">
        <div class="parfume-filters">
            <form method="get" action="<?php echo esc_url($current_url); ?>">
                
                <!-- ПОПРАВЕНИ ФИЛТРИ ЗА МНОЖЕСТВЕН ИЗБОР -->
                
                <!-- Gender Filter -->
                <?php if (taxonomy_exists('gender')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Пол', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <?php
                        $gender_terms = get_terms(array(
                            'taxonomy' => 'gender',
                            'hide_empty' => true,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        
                        if (!empty($gender_terms) && !is_wp_error($gender_terms)) {
                            foreach ($gender_terms as $term) {
                                $checked = !empty($active_filters['gender']) && in_array($term->slug, $active_filters['gender']);
                                ?>
                                <div class="filter-option">
                                    <label>
                                        <input type="checkbox" name="gender[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked($checked); ?>>
                                        <?php echo esc_html($term->name); ?>
                                        <span class="filter-count">(<?php echo $term->count; ?>)</span>
                                    </label>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Brand Filter -->
                <?php if (taxonomy_exists('marki')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Марка', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <input type="text" class="filter-search" placeholder="<?php _e('Търси марка...', 'parfume-reviews'); ?>">
                        <div class="scrollable-options">
                            <?php
                            $brand_terms = get_terms(array(
                                'taxonomy' => 'marki',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            
                            if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
                                foreach ($brand_terms as $term) {
                                    $checked = !empty($active_filters['marki']) && in_array($term->slug, $active_filters['marki']);
                                    ?>
                                    <div class="filter-option">
                                        <label>
                                            <input type="checkbox" name="marki[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked($checked); ?>>
                                            <?php echo esc_html($term->name); ?>
                                            <span class="filter-count">(<?php echo $term->count; ?>)</span>
                                        </label>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Aroma Type Filter -->
                <?php if (taxonomy_exists('aroma_type')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Тип аромат', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <?php
                        $aroma_terms = get_terms(array(
                            'taxonomy' => 'aroma_type',
                            'hide_empty' => true,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        
                        if (!empty($aroma_terms) && !is_wp_error($aroma_terms)) {
                            foreach ($aroma_terms as $term) {
                                $checked = !empty($active_filters['aroma_type']) && in_array($term->slug, $active_filters['aroma_type']);
                                ?>
                                <div class="filter-option">
                                    <label>
                                        <input type="checkbox" name="aroma_type[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked($checked); ?>>
                                        <?php echo esc_html($term->name); ?>
                                        <span class="filter-count">(<?php echo $term->count; ?>)</span>
                                    </label>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Season Filter -->
                <?php if (taxonomy_exists('season')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Сезон', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <?php
                        $season_terms = get_terms(array(
                            'taxonomy' => 'season',
                            'hide_empty' => true,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        
                        if (!empty($season_terms) && !is_wp_error($season_terms)) {
                            foreach ($season_terms as $term) {
                                $checked = !empty($active_filters['season']) && in_array($term->slug, $active_filters['season']);
                                ?>
                                <div class="filter-option">
                                    <label>
                                        <input type="checkbox" name="season[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked($checked); ?>>
                                        <?php echo esc_html($term->name); ?>
                                        <span class="filter-count">(<?php echo $term->count; ?>)</span>
                                    </label>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Intensity Filter -->
                <?php if (taxonomy_exists('intensity')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Интензивност', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <?php
                        $intensity_terms = get_terms(array(
                            'taxonomy' => 'intensity',
                            'hide_empty' => true,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        
                        if (!empty($intensity_terms) && !is_wp_error($intensity_terms)) {
                            foreach ($intensity_terms as $term) {
                                $checked = !empty($active_filters['intensity']) && in_array($term->slug, $active_filters['intensity']);
                                ?>
                                <div class="filter-option">
                                    <label>
                                        <input type="checkbox" name="intensity[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked($checked); ?>>
                                        <?php echo esc_html($term->name); ?>
                                        <span class="filter-count">(<?php echo $term->count; ?>)</span>
                                    </label>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Notes Filter -->
                <?php if (taxonomy_exists('notes')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Нотки', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <input type="text" class="filter-search" placeholder="<?php _e('Търси нотка...', 'parfume-reviews'); ?>">
                        <div class="scrollable-options">
                            <?php
                            $notes_terms = get_terms(array(
                                'taxonomy' => 'notes',
                                'hide_empty' => true,
                                'orderby' => 'count',
                                'order' => 'DESC',
                                'number' => 50 // Показваме топ 50 нотки
                            ));
                            
                            if (!empty($notes_terms) && !is_wp_error($notes_terms)) {
                                foreach ($notes_terms as $term) {
                                    $checked = !empty($active_filters['notes']) && in_array($term->slug, $active_filters['notes']);
                                    ?>
                                    <div class="filter-option">
                                        <label>
                                            <input type="checkbox" name="notes[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked($checked); ?>>
                                            <?php echo esc_html($term->name); ?>
                                            <span class="filter-count">(<?php echo $term->count; ?>)</span>
                                        </label>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Price Range Filter -->
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Ценови диапазон', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <div class="price-range">
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
                        
                        <select name="order">
                            <option value="DESC" <?php selected(isset($_GET['order']) ? $_GET['order'] : '', 'DESC'); ?>><?php _e('Намаляващо', 'parfume-reviews'); ?></option>
                            <option value="ASC" <?php selected(isset($_GET['order']) ? $_GET['order'] : '', 'ASC'); ?>><?php _e('Нарастващо', 'parfume-reviews'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Filter Actions -->
                <div class="filter-submit">
                    <button type="submit" class="button button-primary filter-button">
                        <?php _e('Филтрирай', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="button button-secondary clear-filters">
                        <?php _e('Изчисти', 'parfume-reviews'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Active Filters Display -->
        <div class="active-filters" style="display: none;">
            <h4><?php _e('Активни филтри:', 'parfume-reviews'); ?></h4>
            <div class="active-filter-tags"></div>
        </div>
    </div>
    <?php
}

/**
 * Показва debug информация за филтри (ако е включен DEBUG)
 */
function parfume_reviews_debug_filters() {
    if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('manage_options')) {
        return;
    }
    
    $active_filters = parfume_reviews_get_active_filters();
    
    if (!empty($active_filters)) {
        echo '<div style="background: #fff; border: 2px solid #0073aa; padding: 10px; margin: 10px; font-size: 12px;">';
        echo '<strong>Активни филтри (Debug):</strong>';
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
 * НОВИ ФУНКЦИИ ЗА COMPARISON И COLLECTIONS
 * Тези функции се използват в template файловете
 */

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
    // За бъдеща функционалност
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
 * Добавя schema.org structured data
 */
function parfume_reviews_add_structured_data() {
    if (!parfume_reviews_is_parfume_page()) {
        return;
    }
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => get_the_title(),
        'description' => get_bloginfo('description'),
        'url' => get_permalink()
    );
    
    // Добавяме парфюмите като част от колекцията
    global $wp_query;
    if (have_posts()) {
        $items = array();
        
        while (have_posts()) {
            the_post();
            $items[] = array(
                '@type' => 'Product',
                'name' => get_the_title(),
                'url' => get_permalink(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'large')
            );
        }
        
        wp_reset_postdata();
        
        if (!empty($items)) {
            $schema['mainEntity'] = $items;
        }
    }
    
    ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
    <?php
}