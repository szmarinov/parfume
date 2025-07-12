<?php
/**
 * Template Functions for Parfume Reviews
 * ПОПРАВЕН ЗА МНОЖЕСТВЕНО ФИЛТРИРАНЕ ПО НЯКОЛКО КРИТЕРИЯ
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Проверява дали страницата е свързана с парфюми
 */
function parfume_reviews_is_parfume_page() {
    return (
        is_singular('parfume') || 
        is_post_type_archive('parfume') || 
        is_tax(array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'))
    );
}

/**
 * ПОПРАВЕНА ФУНКЦИЯ - Показва филтърната форма с множествено филтриране
 */
function parfume_reviews_display_filters() {
    $settings = get_option('parfume_reviews_settings', array());
    $show_sidebar = !empty($settings['show_archive_sidebar']) ? $settings['show_archive_sidebar'] : 1;
    
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
                
                <!-- Brands Filter -->
                <?php if (taxonomy_exists('marki')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Марки', 'parfume-reviews'); ?>
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
                                'orderby' => 'name',
                                'order' => 'ASC',
                                'number' => 50 // Лимитираме за производителност
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
                
                <!-- Perfumer Filter -->
                <?php if (taxonomy_exists('perfumer')): ?>
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Парфюмер', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <input type="text" class="filter-search" placeholder="<?php _e('Търси парфюмер...', 'parfume-reviews'); ?>">
                        <div class="scrollable-options">
                            <?php
                            $perfumer_terms = get_terms(array(
                                'taxonomy' => 'perfumer',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            
                            if (!empty($perfumer_terms) && !is_wp_error($perfumer_terms)) {
                                foreach ($perfumer_terms as $term) {
                                    $checked = !empty($active_filters['perfumer']) && in_array($term->slug, $active_filters['perfumer']);
                                    ?>
                                    <div class="filter-option">
                                        <label>
                                            <input type="checkbox" name="perfumer[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked($checked); ?>>
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
                        <div class="price-inputs">
                            <input type="number" name="min_price" placeholder="<?php _e('Мин. цена', 'parfume-reviews'); ?>" 
                                   value="<?php echo esc_attr(!empty($_GET['min_price']) ? $_GET['min_price'] : ''); ?>" 
                                   min="0" step="0.01">
                            <span class="price-separator">-</span>
                            <input type="number" name="max_price" placeholder="<?php _e('Макс. цена', 'parfume-reviews'); ?>" 
                                   value="<?php echo esc_attr(!empty($_GET['max_price']) ? $_GET['max_price'] : ''); ?>" 
                                   min="0" step="0.01">
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
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php selected(!empty($_GET['min_rating']) ? $_GET['min_rating'] : '', $i); ?>>
                                    <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?> (<?php echo $i; ?>+)
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Sorting Options -->
                <div class="filter-section">
                    <h4 class="filter-title">
                        <?php _e('Сортиране', 'parfume-reviews'); ?>
                        <span class="toggle-arrow">▼</span>
                    </h4>
                    <div class="filter-options">
                        <select name="orderby">
                            <option value=""><?php _e('По подразбиране', 'parfume-reviews'); ?></option>
                            <option value="name" <?php selected(!empty($_GET['orderby']) ? $_GET['orderby'] : '', 'name'); ?>><?php _e('По име', 'parfume-reviews'); ?></option>
                            <option value="price" <?php selected(!empty($_GET['orderby']) ? $_GET['orderby'] : '', 'price'); ?>><?php _e('По цена', 'parfume-reviews'); ?></option>
                            <option value="rating" <?php selected(!empty($_GET['orderby']) ? $_GET['orderby'] : '', 'rating'); ?>><?php _e('По рейтинг', 'parfume-reviews'); ?></option>
                            <option value="date" <?php selected(!empty($_GET['orderby']) ? $_GET['orderby'] : '', 'date'); ?>><?php _e('По дата', 'parfume-reviews'); ?></option>
                            <option value="popularity" <?php selected(!empty($_GET['orderby']) ? $_GET['orderby'] : '', 'popularity'); ?>><?php _e('По популярност', 'parfume-reviews'); ?></option>
                        </select>
                        
                        <select name="order">
                            <option value="ASC" <?php selected(!empty($_GET['order']) ? $_GET['order'] : '', 'ASC'); ?>><?php _e('Възходящо', 'parfume-reviews'); ?></option>
                            <option value="DESC" <?php selected(!empty($_GET['order']) ? $_GET['order'] : '', 'DESC'); ?>><?php _e('Низходящо', 'parfume-reviews'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Filter Buttons -->
                <div class="filter-submit">
                    <button type="submit" class="button button-primary filter-button">
                        <?php _e('Филтрирай', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="button button-secondary reset-button">
                        <?php _e('Изчисти', 'parfume-reviews'); ?>
                    </button>
                </div>
                
            </form>
        </div>
        
        <!-- Active Filters Display -->
        <?php parfume_reviews_display_active_filters($active_filters); ?>
    </div>
    <?php
}

/**
 * НОВА ФУНКЦИЯ - Показва активните филтри
 */
function parfume_reviews_display_active_filters($active_filters = array()) {
    if (empty($active_filters)) {
        return;
    }
    
    ?>
    <div class="active-filters" style="<?php echo empty($active_filters) ? 'display: none;' : ''; ?>">
        <h4><?php _e('Активни филтри:', 'parfume-reviews'); ?></h4>
        <div class="filter-tags">
            <?php
            foreach ($active_filters as $taxonomy => $terms) {
                if (!empty($terms)) {
                    foreach ($terms as $term_slug) {
                        $term = get_term_by('slug', $term_slug, $taxonomy);
                        if ($term && !is_wp_error($term)) {
                            ?>
                            <span class="filter-tag">
                                <?php echo esc_html($term->name); ?>
                                <button class="remove-tag" data-filter-type="<?php echo esc_attr($taxonomy); ?>" data-filter-value="<?php echo esc_attr($term_slug); ?>">×</button>
                            </span>
                            <?php
                        }
                    }
                }
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * ПОПРАВЕНА ФУНКЦИЯ - Получава активните филтри
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
    
    $availability = get_post_meta($post_id, '_availability', true);
    $shipping = get_post_meta($post_id, '_shipping_info', true);
    $rating = get_post_meta($post_id, '_average_rating', true);
    
    ?>
    <article class="parfume-card" data-id="<?php echo esc_attr($post_id); ?>">
        <?php if (!empty($settings['card_show_image']) && has_post_thumbnail($post_id)): ?>
        <div class="parfume-thumbnail">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php echo get_the_post_thumbnail($post_id, 'medium', array('alt' => get_the_title($post_id))); ?>
            </a>
        </div>
        <?php endif; ?>
        
        <div class="parfume-card-content">
            <?php if (!empty($settings['card_show_brand']) && $brand_name): ?>
            <div class="parfume-brand"><?php echo esc_html($brand_name); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_name'])): ?>
            <h3 class="parfume-title">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <?php echo esc_html(get_the_title($post_id)); ?>
                </a>
            </h3>
            <?php endif; ?>
            
            <?php if ($rating): ?>
            <div class="parfume-rating">
                <?php parfume_reviews_display_stars($rating); ?>
                <span class="rating-value">(<?php echo esc_html(number_format($rating, 1)); ?>)</span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['card_show_price']) && $price): ?>
            <div class="parfume-price">
                <?php echo esc_html($price); ?> лв.
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
 * Показва звезди за рейтинг
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
                <span class="store-price"><?php echo esc_html($price); ?> лв.</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Показва страничен панел с филтри
 */
function parfume_reviews_display_sidebar() {
    ?>
    <aside class="archive-sidebar">
        <?php parfume_reviews_display_filters(); ?>
        
        <?php
        // Допълнителни widget-и ако има
        if (is_active_sidebar('parfume-archive-sidebar')) {
            dynamic_sidebar('parfume-archive-sidebar');
        }
        ?>
    </aside>
    <?php
}

/**
 * Показва pagination за архивни страници
 */
function parfume_reviews_display_pagination() {
    global $wp_query;
    
    if ($wp_query->max_num_pages <= 1) {
        return;
    }
    
    $big = 999999999; // need an unlikely integer
    
    echo paginate_links(array(
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $wp_query->max_num_pages,
        'prev_text' => __('&laquo; Предишна', 'parfume-reviews'),
        'next_text' => __('Следваща &raquo;', 'parfume-reviews'),
        'before_page_number' => '<span class="screen-reader-text">' . __('Страница ', 'parfume-reviews') . '</span>',
    ));
}

/**
 * Показва архивен header с информация
 */
function parfume_reviews_display_archive_header() {
    ?>
    <div class="archive-header">
        <?php if (is_post_type_archive('parfume')): ?>
            <h1 class="archive-title"><?php _e('Всички парфюми', 'parfume-reviews'); ?></h1>
            <?php
            $settings = get_option('parfume_reviews_settings', array());
            if (!empty($settings['homepage_description'])) {
                echo '<div class="archive-description">' . esc_html($settings['homepage_description']) . '</div>';
            }
            ?>
        <?php elseif (is_tax()): ?>
            <?php
            $queried_object = get_queried_object();
            if ($queried_object) {
                ?>
                <h1 class="archive-title"><?php echo esc_html($queried_object->name); ?></h1>
                <?php if ($queried_object->description): ?>
                    <div class="archive-description"><?php echo esc_html($queried_object->description); ?></div>
                <?php endif; ?>
                <?php
            }
            ?>
        <?php endif; ?>
        
        <?php
        // Показваме брой резултати
        global $wp_query;
        if ($wp_query->found_posts > 0) {
            ?>
            <div class="results-count">
                <?php
                printf(
                    _n('Намерен %d парфюм', 'Намерени %d парфюма', $wp_query->found_posts, 'parfume-reviews'),
                    $wp_query->found_posts
                );
                ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

/**
 * Показва grid layout за парфюмите
 */
function parfume_reviews_display_parfume_grid() {
    global $wp_query;
    
    if (!have_posts()) {
        ?>
        <div class="no-results">
            <h3><?php _e('Няма намерени парфюми', 'parfume-reviews'); ?></h3>
            <p><?php _e('Опитайте да промените филтрите или да търсите нещо друго.', 'parfume-reviews'); ?></p>
        </div>
        <?php
        return;
    }
    
    $settings = get_option('parfume_reviews_settings', array());
    $columns = !empty($settings['archive_grid_columns']) ? intval($settings['archive_grid_columns']) : 3;
    
    ?>
    <div class="parfume-grid" data-columns="<?php echo esc_attr($columns); ?>">
        <?php
        while (have_posts()) {
            the_post();
            parfume_reviews_display_parfume_card(get_the_ID());
        }
        ?>
    </div>
    <?php
    
    // Reset post data
    wp_reset_postdata();
}

/**
 * Получава breadcrumbs за архивни страници
 */
function parfume_reviews_get_breadcrumbs() {
    $breadcrumbs = array();
    
    // Home
    $breadcrumbs[] = array(
        'title' => __('Начало', 'parfume-reviews'),
        'url' => home_url('/')
    );
    
    // Archive
    $breadcrumbs[] = array(
        'title' => __('Парфюми', 'parfume-reviews'),
        'url' => get_post_type_archive_link('parfume')
    );
    
    // Current taxonomy/term
    if (is_tax()) {
        $queried_object = get_queried_object();
        if ($queried_object) {
            $breadcrumbs[] = array(
                'title' => $queried_object->name,
                'url' => get_term_link($queried_object)
            );
        }
    }
    
    return $breadcrumbs;
}

/**
 * Показва breadcrumbs
 */
function parfume_reviews_display_breadcrumbs() {
    $breadcrumbs = parfume_reviews_get_breadcrumbs();
    
    if (empty($breadcrumbs)) {
        return;
    }
    
    ?>
    <nav class="parfume-breadcrumbs" aria-label="<?php _e('Breadcrumb Navigation', 'parfume-reviews'); ?>">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <li class="breadcrumb-item">
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['title']); ?></a>
                    <?php else: ?>
                        <span class="current"><?php echo esc_html($crumb['title']); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

/**
 * Debug функция за филтри
 */
function parfume_reviews_debug_filters() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $active_filters = parfume_reviews_get_active_filters();
    
    if (!empty($active_filters)) {
        echo '<div class="parfume-debug-filters">';
        echo '<h4>Debug: Активни филтри</h4>';
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