<?php
/**
 * Template functions for Parfume Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get HTML for rating stars
 */
function parfume_reviews_get_rating_stars($rating, $max = 5) {
    $rating = floatval($rating);
    $max = intval($max);
    
    if ($max <= 0) $max = 5;
    
    $output = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
    $empty_stars = $max - $full_stars - $half_star;
    
    // Ensure we don't exceed max stars
    if ($full_stars > $max) {
        $full_stars = $max;
        $half_star = 0;
        $empty_stars = 0;
    }
    
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<span class="star filled">★</span>';
    }
    
    for ($i = 0; $i < $half_star; $i++) {
        $output .= '<span class="star half">★</span>';
    }
    
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<span class="star">★</span>';
    }
    
    return $output;
}

/**
 * Get comparison button HTML
 */
function parfume_reviews_get_comparison_button($post_id) {
    $post_id = intval($post_id);
    
    if (!$post_id || get_post_type($post_id) !== 'parfume') {
        return '';
    }
    
    if (class_exists('Parfume_Reviews\\Comparison')) {
        return Parfume_Reviews\Comparison::get_comparison_button($post_id);
    }
    return '';
}

/**
 * Get collections dropdown HTML
 */
function parfume_reviews_get_collections_dropdown($post_id) {
    $post_id = intval($post_id);
    
    if (!$post_id || get_post_type($post_id) !== 'parfume') {
        return '';
    }
    
    if (class_exists('Parfume_Reviews\\Collections')) {
        return Parfume_Reviews\Collections::get_collections_dropdown($post_id);
    }
    return '';
}

/**
 * ПРЕМАХНАТА: Get price history - функционалността е премахната
 */
function parfume_reviews_get_price_history($post_id) {
    // Price history функционалността е премахната
    return array();
}

/**
 * Get perfumer photo
 */
function parfume_reviews_get_perfumer_photo($term_id) {
    $image_id = get_term_meta($term_id, 'perfumer-image-id', true);
    if ($image_id) {
        return wp_get_attachment_image($image_id, 'thumbnail');
    }
    return '';
}

/**
 * НОВА: Get stores for single parfume display
 */
function parfume_reviews_get_stores_display($post_id) {
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    $stores = !empty($stores) && is_array($stores) ? $stores : array();
    
    if (empty($stores)) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="parfume-stores-sidebar">
        <?php foreach ($stores as $index => $store): ?>
            <div class="store-item" data-store-index="<?php echo $index; ?>">
                <?php echo parfume_reviews_render_single_store($store, $post_id); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * НОВА: Render single store for sidebar
 */
function parfume_reviews_render_single_store($store, $post_id) {
    if (empty($store['name'])) {
        return '';
    }
    
    // Get scraped data
    $scraped_data = get_post_meta($post_id, '_store_scraped_data_' . md5($store['product_url']), true);
    $scraped_data = is_array($scraped_data) ? $scraped_data : array();
    
    // Get settings
    $settings = get_option('parfume_reviews_settings', array());
    $scrape_interval = isset($settings['scrape_interval']) ? $settings['scrape_interval'] : 24;
    
    ob_start();
    ?>
    <div class="single-store-display">
        <!-- Първи ред: Лого и цена -->
        <div class="store-header">
            <div class="store-logo">
                <?php if (!empty($store['logo'])): ?>
                    <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>">
                <?php else: ?>
                    <span class="store-name-text"><?php echo esc_html($store['name']); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="store-price-info">
                <?php if (!empty($scraped_data['prices'])): ?>
                    <?php $prices = $scraped_data['prices']; ?>
                    <div class="price-display">
                        <?php if (isset($prices['old_price']) && isset($prices['current_price']) && $prices['old_price'] > $prices['current_price']): ?>
                            <span class="old-price"><?php echo esc_html($prices['old_price']); ?></span>
                            <span class="current-price"><?php echo esc_html($prices['current_price']); ?></span>
                            <?php 
                            $discount = round((($prices['old_price'] - $prices['current_price']) / $prices['old_price']) * 100);
                            ?>
                            <span class="discount-percent">По-изгодно с <?php echo $discount; ?>%</span>
                        <?php else: ?>
                            <span class="current-price"><?php echo esc_html($prices['current_price']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="price-update-info">
                    <i class="info-icon" title="Цената се актуализира на всеки <?php echo $scrape_interval; ?> час">ℹ</i>
                </div>
            </div>
        </div>
        
        <!-- Втори ред: Информация за продукта -->
        <div class="store-product-info">
            <?php if (!empty($scraped_data['variants']) && count($scraped_data['variants']) == 1): ?>
                <!-- Един вариант -->
                <div class="single-variant">
                    <span class="variant-ml"><?php echo esc_html($scraped_data['variants'][0]['ml']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($scraped_data['availability'])): ?>
                <div class="availability-status">
                    <span class="availability-badge available">
                        <i class="check-icon">✓</i>
                        наличен
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($scraped_data['delivery'])): ?>
                <div class="delivery-info">
                    <?php echo esc_html($scraped_data['delivery']); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Трети ред: Варианти (ако има повече от един) -->
        <?php if (!empty($scraped_data['variants']) && count($scraped_data['variants']) > 1): ?>
            <div class="store-variants">
                <?php foreach ($scraped_data['variants'] as $variant): ?>
                    <a href="<?php echo esc_url($store['affiliate_url']); ?>" target="_blank" rel="nofollow" class="variant-button">
                        <?php if (isset($variant['discount']) && $variant['discount']): ?>
                            <span class="discount-badge">%</span>
                        <?php endif; ?>
                        <span class="variant-ml"><?php echo esc_html($variant['ml']); ?></span>
                        <span class="variant-price"><?php echo esc_html($variant['price']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Четвърти ред: Бутони за действие -->
        <div class="store-actions">
            <?php if (!empty($store['promo_code'])): ?>
                <!-- Два бутона 50/50 -->
                <div class="action-buttons-split">
                    <a href="<?php echo esc_url($store['affiliate_url']); ?>" target="_blank" rel="nofollow" class="btn-shop">
                        Към магазина
                    </a>
                    <div class="promo-code-button" data-code="<?php echo esc_attr($store['promo_code']); ?>" data-url="<?php echo esc_url($store['affiliate_url']); ?>">
                        <?php if (!empty($store['promo_code_info'])): ?>
                            <div class="promo-info"><?php echo esc_html($store['promo_code_info']); ?></div>
                        <?php endif; ?>
                        <div class="promo-code-display">
                            <span class="promo-code"><?php echo esc_html($store['promo_code']); ?></span>
                            <i class="copy-icon">📋</i>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Един бутон пълна ширина -->
                <a href="<?php echo esc_url($store['affiliate_url']); ?>" target="_blank" rel="nofollow" class="btn-shop full-width">
                    Към магазина
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * НОВА: Get progress bar for ratings/characteristics
 */
function parfume_reviews_get_progress_bar($value, $max_value, $labels = array()) {
    $value = intval($value);
    $max_value = intval($max_value);
    
    if ($value <= 0 || $max_value <= 0) {
        return '';
    }
    
    $percentage = ($value / $max_value) * 100;
    
    ob_start();
    ?>
    <div class="progress-bar-container">
        <?php for ($i = 1; $i <= $max_value; $i++): ?>
            <div class="progress-bar-item <?php echo $i <= $value ? 'active' : ''; ?>">
                <div class="progress-bar"></div>
                <?php if (isset($labels[$i-1])): ?>
                    <span class="progress-label"><?php echo esc_html($labels[$i-1]); ?></span>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * НОВА: Get advantages and disadvantages
 */
function parfume_reviews_get_advantages_disadvantages($post_id) {
    $advantages = get_post_meta($post_id, '_parfume_advantages', true);
    $disadvantages = get_post_meta($post_id, '_parfume_disadvantages', true);
    
    $advantages = is_array($advantages) ? $advantages : array();
    $disadvantages = is_array($disadvantages) ? $disadvantages : array();
    
    if (empty($advantages) && empty($disadvantages)) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="advantages-disadvantages">
        <h3>Предимства и недостатъци</h3>
        
        <div class="advantages-disadvantages-grid">
            <?php if (!empty($advantages)): ?>
                <div class="advantages-column">
                    <h4 class="advantages-title">
                        <i class="icon-plus">✓</i>
                        Предимства
                    </h4>
                    <ul class="advantages-list">
                        <?php foreach ($advantages as $advantage): ?>
                            <li class="advantage-item">
                                <i class="icon-check">✓</i>
                                <?php echo esc_html($advantage); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($disadvantages)): ?>
                <div class="disadvantages-column">
                    <h4 class="disadvantages-title">
                        <i class="icon-minus">✗</i>
                        Недостатъци
                    </h4>
                    <ul class="disadvantages-list">
                        <?php foreach ($disadvantages as $disadvantage): ?>
                            <li class="disadvantage-item">
                                <i class="icon-cross">✗</i>
                                <?php echo esc_html($disadvantage); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * НОВА: Get seasonal/time icons
 */
function parfume_reviews_get_seasonal_icons($post_id) {
    $seasons = wp_get_post_terms($post_id, 'season', array('fields' => 'names'));
    $times = get_post_meta($post_id, '_parfume_suitable_times', true);
    
    if (is_wp_error($seasons)) {
        $seasons = array();
    }
    
    $times = is_array($times) ? $times : array();
    
    $season_icons = array(
        'Пролет' => '🌸',
        'Лято' => '☀️',
        'Есен' => '🍂',
        'Зима' => '❄️'
    );
    
    $time_icons = array(
        'Ден' => '🌅',
        'Нощ' => '🌙'
    );
    
    ob_start();
    ?>
    <div class="seasonal-time-info">
        <?php if (!empty($seasons)): ?>
            <div class="seasonal-icons">
                <?php foreach ($seasons as $season): ?>
                    <?php if (isset($season_icons[$season])): ?>
                        <span class="season-icon" title="<?php echo esc_attr($season); ?>">
                            <?php echo $season_icons[$season]; ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($times)): ?>
            <div class="time-icons">
                <?php foreach ($times as $time): ?>
                    <?php if (isset($time_icons[$time])): ?>
                        <span class="time-icon" title="<?php echo esc_attr($time); ?>">
                            <?php echo $time_icons[$time]; ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * НОВА: Get notes with icons
 */
function parfume_reviews_get_notes_with_icons($post_id, $level = 'all') {
    $notes = wp_get_post_terms($post_id, 'notes');
    
    if (is_wp_error($notes) || empty($notes)) {
        return '';
    }
    
    // Разделяне на нотките по нива
    $notes_by_level = array(
        'top' => array_slice($notes, 0, 3),
        'middle' => array_slice($notes, 3, 3), 
        'base' => array_slice($notes, 6)
    );
    
    if ($level !== 'all' && isset($notes_by_level[$level])) {
        $notes = $notes_by_level[$level];
    }
    
    ob_start();
    ?>
    <div class="notes-with-icons">
        <?php if ($level === 'all'): ?>
            <div class="notes-pyramid">
                <div class="pyramid-level top-notes">
                    <h4>Връхни нотки</h4>
                    <div class="notes-list">
                        <?php foreach ($notes_by_level['top'] as $note): ?>
                            <div class="note-item">
                                <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                                <span class="note-name"><?php echo esc_html($note->name); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="pyramid-level middle-notes">
                    <h4>Средни нотки</h4>
                    <div class="notes-list">
                        <?php foreach ($notes_by_level['middle'] as $note): ?>
                            <div class="note-item">
                                <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                                <span class="note-name"><?php echo esc_html($note->name); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="pyramid-level base-notes">
                    <h4>Базови нотки</h4>
                    <div class="notes-list">
                        <?php foreach ($notes_by_level['base'] as $note): ?>
                            <div class="note-item">
                                <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                                <span class="note-name"><?php echo esc_html($note->name); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="notes-list">
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                        <span class="note-name"><?php echo esc_html($note->name); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * НОВА: Get note icon
 */
function parfume_reviews_get_note_icon($term_id) {
    $icon_id = get_term_meta($term_id, 'note-icon-id', true);
    
    if ($icon_id) {
        return wp_get_attachment_image($icon_id, array(24, 24), false, array('class' => 'note-icon'));
    }
    
    // Fallback icon
    return '<span class="note-icon-fallback">🌿</span>';
}

/**
 * НОВА: Get similar products based on notes
 */
function parfume_reviews_get_similar_products($post_id, $limit = 4) {
    $settings = get_option('parfume_reviews_settings', array());
    $limit = isset($settings['similar_products_count']) ? intval($settings['similar_products_count']) : $limit;
    
    // Получаваме нотките на текущия парфюм
    $current_notes = wp_get_post_terms($post_id, 'notes', array('fields' => 'ids'));
    $current_brand = wp_get_post_terms($post_id, 'marki', array('fields' => 'ids'));
    $current_gender = wp_get_post_terms($post_id, 'gender', array('fields' => 'ids'));
    
    if (is_wp_error($current_notes)) $current_notes = array();
    if (is_wp_error($current_brand)) $current_brand = array();
    if (is_wp_error($current_gender)) $current_gender = array();
    
    $tax_query = array('relation' => 'OR');
    
    if (!empty($current_notes)) {
        $tax_query[] = array(
            'taxonomy' => 'notes',
            'field' => 'term_id',
            'terms' => $current_notes,
        );
    }
    
    if (!empty($current_brand)) {
        $tax_query[] = array(
            'taxonomy' => 'marki',
            'field' => 'term_id',
            'terms' => $current_brand,
        );
    }
    
    if (!empty($current_gender)) {
        $tax_query[] = array(
            'taxonomy' => 'gender',
            'field' => 'term_id',
            'terms' => $current_gender,
        );
    }
    
    if (count($tax_query) === 1) {
        return ''; // Няма критерии за сравнение
    }
    
    $args = array(
        'post_type' => 'parfume',
        'posts_per_page' => $limit,
        'post__not_in' => array($post_id),
        'tax_query' => $tax_query,
        'orderby' => 'rand'
    );
    
    $similar = new WP_Query($args);
    
    if (!$similar->have_posts()) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="similar-products">
        <h3>Подобни аромати</h3>
        <div class="products-grid">
            <?php while ($similar->have_posts()): $similar->the_post(); ?>
                <div class="product-item">
                    <a href="<?php the_permalink(); ?>">
                        <div class="product-image">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium'); ?>
                            <?php else: ?>
                                <div class="no-image">📦</div>
                            <?php endif; ?>
                        </div>
                        <h4 class="product-title"><?php the_title(); ?></h4>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php
    
    wp_reset_postdata();
    return ob_get_clean();
}