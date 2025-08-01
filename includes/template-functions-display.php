<?php
/**
 * Template Functions - Display Functions
 * Функции за показване на парфюмни карточки и UI елементи
 * РЕВИЗИРАНА ВЕРСИЯ: Пълна функционалност с всички display елементи
 * 
 * Файл: includes/template-functions-display.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PARFUME CARD DISPLAY FUNCTIONS
 * Функции за показване на парфюмни карточки
 */

/**
 * Показва карточка на парфюм
 */
if (!function_exists('parfume_reviews_display_parfume_card')) {
    function parfume_reviews_display_parfume_card($post_id, $args = array()) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'parfume') {
            return;
        }
        
        // Default аргументи
        $defaults = array(
            'show_rating' => true,
            'show_price' => true,
            'show_brand' => true,
            'show_excerpt' => true,
            'show_comparison_button' => true,
            'image_size' => 'medium',
            'card_class' => 'parfume-card',
            'link_to_single' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Получаваме данни за парфюма
        $rating = function_exists('parfume_reviews_get_rating') ? parfume_reviews_get_rating($post_id) : 0;
        $price = function_exists('parfume_reviews_get_lowest_price') ? parfume_reviews_get_lowest_price($post_id) : 0;
        $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
        $brand_name = !empty($brands) ? $brands[0] : '';
        $featured_image = get_the_post_thumbnail_url($post_id, $args['image_size']);
        $excerpt = get_the_excerpt($post_id);
        
        // Gender класове
        $gender_terms = wp_get_post_terms($post_id, 'gender', array('fields' => 'names'));
        $gender_class = !empty($gender_terms) ? 'gender-' . sanitize_html_class(strtolower($gender_terms[0])) : '';
        
        // Класове за карточката
        $card_classes = array(
            $args['card_class'],
            'parfume-item',
            $gender_class,
            $rating > 0 ? 'has-rating' : 'no-rating',
            $price > 0 ? 'has-price' : 'no-price'
        );
        
        ?>
        <div class="<?php echo esc_attr(implode(' ', array_filter($card_classes))); ?>" data-parfume-id="<?php echo esc_attr($post_id); ?>">
            
            <?php if ($args['link_to_single']): ?>
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="parfume-card-link">
            <?php endif; ?>
            
                <!-- Изображение -->
                <div class="parfume-image">
                    <?php if ($featured_image): ?>
                        <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <span class="dashicons dashicons-camera"></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($args['show_comparison_button']): ?>
                        <div class="comparison-button-wrapper">
                            <?php echo parfume_reviews_get_comparison_button($post_id); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Съдържание -->
                <div class="parfume-content">
                    
                    <?php if ($args['show_brand'] && $brand_name): ?>
                    <div class="parfume-brand">
                        <?php echo esc_html($brand_name); ?>
                    </div>
                    <?php endif; ?>
                    
                    <h3 class="parfume-title">
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    </h3>
                    
                    <?php if ($args['show_rating'] && $rating > 0): ?>
                    <div class="parfume-rating">
                        <?php echo parfume_reviews_display_star_rating($rating); ?>
                        <span class="rating-value"><?php echo esc_html($rating); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($args['show_excerpt'] && $excerpt): ?>
                    <div class="parfume-excerpt">
                        <?php echo wp_kses_post(wp_trim_words($excerpt, 15)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($args['show_price'] && $price > 0): ?>
                    <div class="parfume-price">
                        <span class="price-label"><?php _e('от', 'parfume-reviews'); ?></span>
                        <span class="price-value"><?php echo esc_html(parfume_reviews_get_formatted_price($price)); ?></span>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
            <?php if ($args['link_to_single']): ?>
            </a>
            <?php endif; ?>
            
        </div>
        <?php
    }
}

/**
 * RATING DISPLAY FUNCTIONS
 * Функции за показване на рейтинг
 */

/**
 * Показва звезден рейтинг
 */
if (!function_exists('parfume_reviews_display_star_rating')) {
    function parfume_reviews_display_star_rating($rating, $args = array()) {
        $defaults = array(
            'max_rating' => 5,
            'star_size' => 'medium',
            'show_empty' => true,
            'class' => 'star-rating'
        );
        
        $args = wp_parse_args($args, $defaults);
        $rating = floatval($rating);
        
        $output = '<div class="' . esc_attr($args['class'] . ' star-size-' . $args['star_size']) . '">';
        
        for ($i = 1; $i <= $args['max_rating']; $i++) {
            $star_class = 'star';
            
            if ($i <= $rating) {
                $star_class .= ' star-filled';
            } elseif ($i - 0.5 <= $rating) {
                $star_class .= ' star-half';
            } elseif ($args['show_empty']) {
                $star_class .= ' star-empty';
            } else {
                continue;
            }
            
            $output .= '<span class="' . esc_attr($star_class) . '">★</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}

/**
 * Получава HTML за звезди без wrapper
 */
if (!function_exists('parfume_reviews_get_rating_stars')) {
    function parfume_reviews_get_rating_stars($rating, $max_rating = 5) {
        $rating = floatval($rating);
        $stars = '';
        
        for ($i = 1; $i <= $max_rating; $i++) {
            if ($i <= $rating) {
                $stars .= '<span class="star filled">★</span>';
            } elseif ($i - 0.5 <= $rating) {
                $stars .= '<span class="star half">★</span>';
            } else {
                $stars .= '<span class="star empty">☆</span>';
            }
        }
        
        return $stars;
    }
}

/**
 * Alias функция за display_star_rating
 */
if (!function_exists('parfume_reviews_display_stars')) {
    function parfume_reviews_display_stars($rating, $max_rating = 5) {
        return parfume_reviews_display_star_rating($rating, array('max_rating' => $max_rating));
    }
}

/**
 * GRID DISPLAY FUNCTIONS
 * Функции за показване на решетки с парфюми
 */

/**
 * Показва решетка с парфюми
 */
if (!function_exists('parfume_reviews_display_parfumes_grid')) {
    function parfume_reviews_display_parfumes_grid($parfumes, $args = array()) {
        if (empty($parfumes)) {
            echo '<p class="no-parfumes-found">' . __('Няма намерени парфюми.', 'parfume-reviews') . '</p>';
            return;
        }
        
        $defaults = array(
            'columns' => 3,
            'grid_class' => 'parfumes-grid',
            'show_pagination' => false,
            'pagination_args' => array(),
            'card_args' => array()
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ?>
        <div class="<?php echo esc_attr($args['grid_class'] . ' columns-' . $args['columns']); ?>">
            <?php foreach ($parfumes as $parfume): ?>
                <?php 
                $post_id = is_object($parfume) ? $parfume->ID : $parfume;
                parfume_reviews_display_parfume_card($post_id, $args['card_args']); 
                ?>
            <?php endforeach; ?>
        </div>
        
        <?php if ($args['show_pagination']): ?>
            <?php parfume_reviews_display_pagination($args['pagination_args']); ?>
        <?php endif; ?>
        <?php
    }
}

/**
 * PAGINATION FUNCTIONS
 * Функции за пагинация
 */

/**
 * Показва пагинация
 */
if (!function_exists('parfume_reviews_display_pagination')) {
    function parfume_reviews_display_pagination($args = array()) {
        global $wp_query;
        
        $defaults = array(
            'mid_size' => 2,
            'prev_text' => '‹ ' . __('Предишна', 'parfume-reviews'),
            'next_text' => __('Следваща', 'parfume-reviews') . ' ›',
            'screen_reader_text' => __('Навигация за постове', 'parfume-reviews'),
            'class' => 'parfume-pagination'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $pagination = paginate_links(array(
            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $wp_query->max_num_pages,
            'mid_size' => $args['mid_size'],
            'prev_text' => $args['prev_text'],
            'next_text' => $args['next_text'],
            'type' => 'list'
        ));
        
        if ($pagination) {
            echo '<nav class="' . esc_attr($args['class']) . '" aria-label="' . esc_attr($args['screen_reader_text']) . '">';
            echo $pagination;
            echo '</nav>';
        }
    }
}

/**
 * NAVIGATION FUNCTIONS
 * Функции за навигация
 */

/**
 * Показва breadcrumb навигация
 */
if (!function_exists('parfume_reviews_display_breadcrumb')) {
    function parfume_reviews_display_breadcrumb($args = array()) {
        $defaults = array(
            'separator' => ' › ',
            'home_text' => __('Начало', 'parfume-reviews'),
            'class' => 'parfume-breadcrumb',
            'show_current' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $breadcrumbs = array();
        
        // Начало
        $breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">' . esc_html($args['home_text']) . '</a>';
        
        if (is_singular('parfume')) {
            // Single парфюм
            $breadcrumbs[] = '<a href="' . esc_url(get_post_type_archive_link('parfume')) . '">' . __('Парфюми', 'parfume-reviews') . '</a>';
            
            if ($args['show_current']) {
                $breadcrumbs[] = '<span class="current">' . esc_html(get_the_title()) . '</span>';
            }
            
        } elseif (is_post_type_archive('parfume')) {
            // Архив парфюми
            if ($args['show_current']) {
                $breadcrumbs[] = '<span class="current">' . __('Парфюми', 'parfume-reviews') . '</span>';
            }
            
        } elseif (is_tax()) {
            // Таксономии
            $term = get_queried_object();
            $taxonomy = get_taxonomy($term->taxonomy);
            
            $breadcrumbs[] = '<a href="' . esc_url(get_post_type_archive_link('parfume')) . '">' . __('Парфюми', 'parfume-reviews') . '</a>';
            
            if ($args['show_current']) {
                $breadcrumbs[] = '<span class="current">' . esc_html($term->name) . '</span>';
            }
        }
        
        if (!empty($breadcrumbs)) {
            echo '<nav class="' . esc_attr($args['class']) . '" aria-label="' . __('Breadcrumb навигация', 'parfume-reviews') . '">';
            echo implode($args['separator'], $breadcrumbs);
            echo '</nav>';
        }
    }
}

/**
 * ARCHIVE DISPLAY FUNCTIONS
 * Функции за архивни страници
 */

/**
 * Показва header на архивна страница
 */
if (!function_exists('parfume_reviews_display_archive_header')) {
    function parfume_reviews_display_archive_header($args = array()) {
        $defaults = array(
            'show_title' => true,
            'show_description' => true,
            'show_count' => true,
            'show_image' => true,
            'class' => 'archive-header'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $title = '';
        $description = '';
        $count = 0;
        $image = '';
        
        if (is_post_type_archive('parfume')) {
            $title = __('Всички парфюми', 'parfume-reviews');
            $count = wp_count_posts('parfume')->publish;
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            $title = $term->name;
            $description = $term->description;
            $count = $term->count;
            
            // Изображение на термина
            if ($args['show_image']) {
                $image_id = get_term_meta($term->term_id, $term->taxonomy . '_image_id', true);
                if ($image_id) {
                    $image = wp_get_attachment_image_url($image_id, 'large');
                }
            }
        }
        
        if ($title) {
            ?>
            <header class="<?php echo esc_attr($args['class']); ?>">
                <?php if ($args['show_image'] && $image): ?>
                <div class="archive-image">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>">
                </div>
                <?php endif; ?>
                
                <div class="archive-content">
                    <?php if ($args['show_title']): ?>
                    <h1 class="archive-title"><?php echo esc_html($title); ?></h1>
                    <?php endif; ?>
                    
                    <?php if ($args['show_count'] && $count > 0): ?>
                    <div class="archive-count">
                        <?php echo sprintf(_n('%d парфюм', '%d парфюма', $count, 'parfume-reviews'), $count); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($args['show_description'] && $description): ?>
                    <div class="archive-description">
                        <?php echo wpautop(wp_kses_post($description)); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </header>
            <?php
        }
    }
}

/**
 * Показва изображение на таксономия
 */
if (!function_exists('parfume_reviews_display_term_image')) {
    function parfume_reviews_display_term_image($term_id, $taxonomy, $size = 'medium', $args = array()) {
        $defaults = array(
            'class' => 'term-image',
            'alt' => '',
            'link_to_term' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $image_id = get_term_meta($term_id, $taxonomy . '_image_id', true);
        
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, $size);
            $alt_text = $args['alt'] ?: get_term_field('name', $term_id, $taxonomy);
            
            $image_html = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" class="' . esc_attr($args['class']) . '">';
            
            if ($args['link_to_term']) {
                $term_link = get_term_link($term_id, $taxonomy);
                if (!is_wp_error($term_link)) {
                    $image_html = '<a href="' . esc_url($term_link) . '">' . $image_html . '</a>';
                }
            }
            
            echo $image_html;
        }
    }
}

/**
 * UI HELPER FUNCTIONS
 * Помощни функции за UI елементи
 */

/**
 * Показва loading индикатор
 */
if (!function_exists('parfume_reviews_display_loading_indicator')) {
    function parfume_reviews_display_loading_indicator($text = '', $args = array()) {
        $defaults = array(
            'class' => 'parfume-loading-indicator',
            'spinner_class' => 'loading-spinner',
            'text_class' => 'loading-text'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        if (empty($text)) {
            $text = __('Зареждане...', 'parfume-reviews');
        }
        
        ?>
        <div class="<?php echo esc_attr($args['class']); ?>">
            <div class="<?php echo esc_attr($args['spinner_class']); ?>"></div>
            <span class="<?php echo esc_attr($args['text_class']); ?>"><?php echo esc_html($text); ?></span>
        </div>
        <?php
    }
}

/**
 * Получава бутон за сравняване
 */
if (!function_exists('parfume_reviews_get_comparison_button')) {
    function parfume_reviews_get_comparison_button($post_id, $args = array()) {
        $defaults = array(
            'text' => __('Добави за сравняване', 'parfume-reviews'),
            'added_text' => __('Премахни от сравняването', 'parfume-reviews'),
            'class' => 'comparison-button',
            'icon' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $button_html = '<button type="button" class="' . esc_attr($args['class']) . '" data-parfume-id="' . esc_attr($post_id) . '" data-text="' . esc_attr($args['text']) . '" data-added-text="' . esc_attr($args['added_text']) . '">';
        
        if ($args['icon']) {
            $button_html .= '<span class="comparison-icon dashicons dashicons-plus-alt2"></span>';
        }
        
        $button_html .= '<span class="comparison-text">' . esc_html($args['text']) . '</span>';
        $button_html .= '</button>';
        
        return $button_html;
    }
}

/**
 * SPECIAL DISPLAY FUNCTIONS
 * Специални display функции
 */

/**
 * Показва collections dropdown (за съвместимост с template файлове)
 */
if (!function_exists('parfume_reviews_get_collections_dropdown')) {
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
        if (method_exists('Parfume_Reviews\\Collections', 'get_collections_dropdown')) {
            return \Parfume_Reviews\Collections::get_collections_dropdown($post_id);
        }
        
        return '';
    }
}

/**
 * Показва статистики за парфюм
 */
if (!function_exists('parfume_reviews_display_parfume_stats')) {
    function parfume_reviews_display_parfume_stats($post_id, $args = array()) {
        $defaults = array(
            'show_views' => true,
            'show_rating_count' => true,
            'show_comparison_count' => true,
            'class' => 'parfume-stats'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $stats = array();
        
        if ($args['show_views']) {
            $views = get_post_meta($post_id, 'parfume_views', true);
            if ($views) {
                $stats[] = sprintf(__('Видян %d пъти', 'parfume-reviews'), intval($views));
            }
        }
        
        if ($args['show_rating_count']) {
            $rating_count = get_post_meta($post_id, 'parfume_rating_count', true);
            if ($rating_count) {
                $stats[] = sprintf(_n('%d оценка', '%d оценки', $rating_count, 'parfume-reviews'), intval($rating_count));
            }
        }
        
        if ($args['show_comparison_count']) {
            $comparison_count = get_post_meta($post_id, 'parfume_comparison_count', true);
            if ($comparison_count) {
                $stats[] = sprintf(__('Сравняван %d пъти', 'parfume-reviews'), intval($comparison_count));
            }
        }
        
        if (!empty($stats)) {
            echo '<div class="' . esc_attr($args['class']) . '">';
            echo implode(' • ', $stats);
            echo '</div>';
        }
    }
}

/**
 * Показва social sharing бутони
 */
if (!function_exists('parfume_reviews_display_social_sharing')) {
    function parfume_reviews_display_social_sharing($post_id, $args = array()) {
        $defaults = array(
            'platforms' => array('facebook', 'twitter', 'pinterest'),
            'class' => 'social-sharing',
            'show_labels' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $url = get_permalink($post_id);
        $title = get_the_title($post_id);
        $image = get_the_post_thumbnail_url($post_id, 'large');
        
        echo '<div class="' . esc_attr($args['class']) . '">';
        
        if (in_array('facebook', $args['platforms'])) {
            $facebook_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url);
            echo '<a href="' . esc_url($facebook_url) . '" target="_blank" class="social-share facebook">';
            echo '<span class="dashicons dashicons-facebook"></span>';
            if ($args['show_labels']) echo '<span class="label">' . __('Facebook', 'parfume-reviews') . '</span>';
            echo '</a>';
        }
        
        if (in_array('twitter', $args['platforms'])) {
            $twitter_url = 'https://twitter.com/intent/tweet?url=' . urlencode($url) . '&text=' . urlencode($title);
            echo '<a href="' . esc_url($twitter_url) . '" target="_blank" class="social-share twitter">';
            echo '<span class="dashicons dashicons-twitter"></span>';
            if ($args['show_labels']) echo '<span class="label">' . __('Twitter', 'parfume-reviews') . '</span>';
            echo '</a>';
        }
        
        if (in_array('pinterest', $args['platforms']) && $image) {
            $pinterest_url = 'https://pinterest.com/pin/create/button/?url=' . urlencode($url) . '&media=' . urlencode($image) . '&description=' . urlencode($title);
            echo '<a href="' . esc_url($pinterest_url) . '" target="_blank" class="social-share pinterest">';
            echo '<span class="dashicons dashicons-pinterest"></span>';
            if ($args['show_labels']) echo '<span class="label">' . __('Pinterest', 'parfume-reviews') . '</span>';
            echo '</a>';
        }
        
        echo '</div>';
    }
}

/**
 * MOBILE DISPLAY FUNCTIONS
 * Функции за мобилни дисплеи
 */

/**
 * Показва мобилен панел за магазини
 */
if (!function_exists('parfume_reviews_display_mobile_stores_panel')) {
    function parfume_reviews_display_mobile_stores_panel($post_id, $args = array()) {
        $defaults = array(
            'class' => 'mobile-stores-panel',
            'toggle_class' => 'mobile-stores-toggle',
            'content_class' => 'mobile-stores-content'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $stores = function_exists('parfume_reviews_get_parfume_stores') ? parfume_reviews_get_parfume_stores($post_id) : array();
        
        if (empty($stores)) {
            return;
        }
        
        ?>
        <div class="<?php echo esc_attr($args['class']); ?>">
            <button type="button" class="<?php echo esc_attr($args['toggle_class']); ?>">
                <span class="toggle-text"><?php _e('Къде да купите', 'parfume-reviews'); ?></span>
                <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
            </button>
            
            <div class="<?php echo esc_attr($args['content_class']); ?>" style="display: none;">
                <?php foreach ($stores as $store): ?>
                <div class="mobile-store-item">
                    <div class="store-info">
                        <div class="store-name"><?php echo esc_html($store['name']); ?></div>
                        <?php if (!empty($store['price'])): ?>
                        <div class="store-price"><?php echo esc_html(parfume_reviews_get_formatted_price($store['price'])); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="store-actions">
                        <?php 
                        $store_url = !empty($store['affiliate_url']) ? $store['affiliate_url'] : $store['url'];
                        ?>
                        <a href="<?php echo esc_url($store_url); ?>" target="_blank" rel="nofollow" class="store-button">
                            <?php _e('Виж', 'parfume-reviews'); ?>
                        </a>
                        
                        <?php if (!empty($store['promo_code'])): ?>
                        <button type="button" class="promo-code-button" data-code="<?php echo esc_attr($store['promo_code']); ?>">
                            <?php _e('Код', 'parfume-reviews'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}

/**
 * LEGACY COMPATIBILITY FUNCTIONS
 * Функции за backward compatibility
 */

// Алтернативни имена за backward compatibility
if (!function_exists('show_parfume_card')) {
    function show_parfume_card($post_id) {
        return parfume_reviews_display_parfume_card($post_id);
    }
}

if (!function_exists('display_parfume_rating')) {
    function display_parfume_rating($rating) {
        return parfume_reviews_display_star_rating($rating);
    }
}

if (!function_exists('get_parfume_comparison_button')) {
    function get_parfume_comparison_button($post_id) {
        return parfume_reviews_get_comparison_button($post_id);
    }
}