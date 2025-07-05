<?php
/**
 * Archive template for parfume post type
 * Location: /templates/archive-parfume.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$settings = get_option('parfume_reviews_settings', array());
$archive_title = post_type_archive_title('', false);
$show_sidebar = isset($settings['show_archive_sidebar']) ? $settings['show_archive_sidebar'] : 1;
$grid_columns = isset($settings['archive_grid_columns']) ? $settings['archive_grid_columns'] : 3;
$card_settings = array(
    'show_image' => isset($settings['card_show_image']) ? $settings['card_show_image'] : 1,
    'show_brand' => isset($settings['card_show_brand']) ? $settings['card_show_brand'] : 1,
    'show_name' => isset($settings['card_show_name']) ? $settings['card_show_name'] : 1,
    'show_price' => isset($settings['card_show_price']) ? $settings['card_show_price'] : 1,
    'show_availability' => isset($settings['card_show_availability']) ? $settings['card_show_availability'] : 1,
    'show_shipping' => isset($settings['card_show_shipping']) ? $settings['card_show_shipping'] : 1,
);

// Get some statistics for the header
$total_perfumes = wp_count_posts('parfume')->publish;
$total_brands = wp_count_terms(array('taxonomy' => 'marki', 'hide_empty' => false));
$total_notes = wp_count_terms(array('taxonomy' => 'notes', 'hide_empty' => false));
?>

<div class="parfume-archive">
    <header class="archive-header">
        <div class="container">
            <h1 class="archive-title"><?php echo $archive_title; ?></h1>
            
            <div class="archive-description">
                <p><?php _e('Открийте вашия следващ любим аромат от нашата колекция от професионални ревюта на парфюми.', 'parfume-reviews'); ?></p>
            </div>
            
            <!-- Archive Statistics -->
            <div class="archive-stats">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_perfumes); ?></span>
                        <span class="stat-label"><?php _e('Парфюма', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_brands); ?></span>
                        <span class="stat-label"><?php _e('Марки', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_notes); ?></span>
                        <span class="stat-label"><?php _e('Нотки', 'parfume-reviews'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="quick-links">
                <a href="<?php echo home_url('/parfiumi/marki/'); ?>" class="quick-link">
                    <span class="link-icon">🏷️</span>
                    <span class="link-text"><?php _e('Всички марки', 'parfume-reviews'); ?></span>
                </a>
                <a href="<?php echo home_url('/parfiumi/notes/'); ?>" class="quick-link">
                    <span class="link-icon">🌿</span>
                    <span class="link-text"><?php _e('Всички нотки', 'parfume-reviews'); ?></span>
                </a>
                <a href="<?php echo home_url('/parfiumi/parfumers/'); ?>" class="quick-link">
                    <span class="link-icon">👨‍🔬</span>
                    <span class="link-text"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></span>
                </a>
            </div>
        </div>
    </header>
    
    <div class="archive-content">
        <div class="container">
            <div class="archive-layout <?php echo $show_sidebar ? 'has-sidebar' : 'full-width'; ?>">
                <?php if ($show_sidebar): ?>
                    <aside class="archive-sidebar">
                        <?php echo do_shortcode('[parfume_filters]'); ?>
                        
                        <!-- Popular Brands -->
                        <div class="sidebar-section popular-brands">
                            <h3><?php _e('Популярни марки', 'parfume-reviews'); ?></h3>
                            <?php
                            $popular_brands = get_terms(array(
                                'taxonomy' => 'marki',
                                'orderby' => 'count',
                                'order' => 'DESC',
                                'number' => 8,
                                'hide_empty' => true,
                            ));
                            
                            if (!empty($popular_brands) && !is_wp_error($popular_brands)): ?>
                                <div class="brands-list">
                                    <?php foreach ($popular_brands as $brand): ?>
                                        <a href="<?php echo get_term_link($brand); ?>" class="brand-item">
                                            <?php 
                                            $brand_logo = get_term_meta($brand->term_id, 'marki-image-id', true);
                                            if ($brand_logo):
                                            ?>
                                                <img src="<?php echo wp_get_attachment_image_url($brand_logo, 'thumbnail'); ?>" alt="<?php echo esc_attr($brand->name); ?>" class="brand-logo">
                                            <?php endif; ?>
                                            <span class="brand-name"><?php echo esc_html($brand->name); ?></span>
                                            <span class="brand-count">(<?php echo $brand->count; ?>)</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Recently Added -->
                        <div class="sidebar-section recently-added">
                            <h3><?php _e('Наскоро добавени', 'parfume-reviews'); ?></h3>
                            <?php
                            $recent_perfumes = get_posts(array(
                                'post_type' => 'parfume',
                                'posts_per_page' => 5,
                                'orderby' => 'date',
                                'order' => 'DESC',
                            ));
                            
                            if (!empty($recent_perfumes)): ?>
                                <div class="recent-list">
                                    <?php foreach ($recent_perfumes as $perfume): ?>
                                        <a href="<?php echo get_permalink($perfume->ID); ?>" class="recent-item">
                                            <?php if (has_post_thumbnail($perfume->ID)): ?>
                                                <div class="recent-thumbnail">
                                                    <?php echo get_the_post_thumbnail($perfume->ID, 'thumbnail'); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="recent-info">
                                                <h4><?php echo esc_html($perfume->post_title); ?></h4>
                                                <span class="recent-date"><?php echo human_time_diff(get_the_time('U', $perfume->ID), current_time('timestamp')) . ' ' . __('ago', 'parfume-reviews'); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Top Rated -->
                        <div class="sidebar-section top-rated">
                            <h3><?php _e('Най-високо оценени', 'parfume-reviews'); ?></h3>
                            <?php
                            $top_rated = get_posts(array(
                                'post_type' => 'parfume',
                                'posts_per_page' => 5,
                                'meta_key' => '_parfume_rating',
                                'orderby' => 'meta_value_num',
                                'order' => 'DESC',
                                'meta_query' => array(
                                    array(
                                        'key' => '_parfume_rating',
                                        'value' => 0,
                                        'compare' => '>',
                                    ),
                                ),
                            ));
                            
                            if (!empty($top_rated)): ?>
                                <div class="top-rated-list">
                                    <?php foreach ($top_rated as $perfume): ?>
                                        <?php 
                                        $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
                                        ?>
                                        <a href="<?php echo get_permalink($perfume->ID); ?>" class="top-rated-item">
                                            <div class="rated-info">
                                                <h4><?php echo esc_html($perfume->post_title); ?></h4>
                                                <div class="rating-display">
                                                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                                    <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </aside>
                <?php endif; ?>
                
                <main class="archive-main">
                    <!-- Results Info -->
                    <?php if (have_posts()): ?>
                        <div class="results-info">
                            <div class="results-count">
                                <?php
                                global $wp_query;
                                $total = $wp_query->found_posts;
                                $current_page = max(1, get_query_var('paged'));
                                $per_page = get_query_var('posts_per_page');
                                $start = (($current_page - 1) * $per_page) + 1;
                                $end = min($start + $per_page - 1, $total);
                                
                                printf(
                                    __('Показване на %d-%d от общо %d парфюма', 'parfume-reviews'),
                                    $start,
                                    $end,
                                    $total
                                );
                                ?>
                            </div>
                            
                            <div class="results-controls">
                                <!-- Sort Options -->
                                <select id="parfume-sort" onchange="location = this.value;">
                                    <option value="<?php echo remove_query_arg('orderby'); ?>" <?php selected(!isset($_GET['orderby'])); ?>>
                                        <?php _e('Сортиране по дата', 'parfume-reviews'); ?>
                                    </option>
                                    <option value="<?php echo add_query_arg(array('orderby' => 'title', 'order' => 'ASC')); ?>" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'title'); ?>>
                                        <?php _e('Име (А-Я)', 'parfume-reviews'); ?>
                                    </option>
                                    <option value="<?php echo add_query_arg(array('orderby' => 'meta_value_num', 'meta_key' => '_parfume_rating', 'order' => 'DESC')); ?>" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'meta_value_num'); ?>>
                                        <?php _e('Най-високо оценени', 'parfume-reviews'); ?>
                                    </option>
                                </select>
                                
                                <!-- View Toggle -->
                                <div class="view-toggle">
                                    <button class="view-btn grid-view active" data-view="grid" title="<?php _e('Grid изглед', 'parfume-reviews'); ?>">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M1 1h6v6H1V1zm8 0h6v6H9V1zM1 9h6v6H1V9zm8 0h6v6H9V9z"/>
                                        </svg>
                                    </button>
                                    <button class="view-btn list-view" data-view="list" title="<?php _e('List изглед', 'parfume-reviews'); ?>">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M0 2h4v2H0V2zm6 0h10v2H6V2zm0 4h10v2H6V6zM0 6h4v2H0V6zm0 4h4v2H0v-2zm6 0h10v2H6v-2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="parfume-grid" data-columns="<?php echo esc_attr($grid_columns); ?>" data-view="grid">
                            <?php while (have_posts()): the_post(); ?>
                                <article class="parfume-card">
                                    <?php if ($card_settings['show_image'] && has_post_thumbnail()): ?>
                                        <div class="parfume-thumbnail">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </a>
                                            
                                            <!-- Quick Actions Overlay -->
                                            <div class="quick-actions">
                                                <button class="quick-btn add-to-comparison" data-perfume-id="<?php echo get_the_ID(); ?>" title="<?php _e('Добави за сравнение', 'parfume-reviews'); ?>">
                                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
                                                    </svg>
                                                </button>
                                                <button class="quick-btn add-to-collection" data-perfume-id="<?php echo get_the_ID(); ?>" title="<?php _e('Добави в колекция', 'parfume-reviews'); ?>">
                                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M8 12.5a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5.5.5 0 0 1 .5.5v8a.5.5 0 0 1-.5.5z"/>
                                                        <path d="M4.5 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="parfume-content">
                                        <?php if ($card_settings['show_name']): ?>
                                            <h2 class="parfume-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h2>
                                        <?php endif; ?>
                                        
                                        <?php if ($card_settings['show_brand']): ?>
                                            <?php
                                            $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                            if (!empty($brands) && !is_wp_error($brands)): ?>
                                                <div class="parfume-brand">
                                                    <a href="<?php echo get_term_link(get_term_by('name', $brands[0], 'marki')); ?>">
                                                        <?php echo esc_html($brands[0]); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                        if (!empty($rating) && is_numeric($rating)): 
                                        ?>
                                            <div class="parfume-rating">
                                                <div class="rating-stars">
                                                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                                </div>
                                                <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="parfume-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                        </div>
                                        
                                        <?php if ($card_settings['show_price']): ?>
                                            <?php 
                                            $lowest_store = parfume_reviews_get_lowest_price(get_the_ID());
                                            if ($lowest_store): ?>
                                                <div class="parfume-price">
                                                    <span class="price-label"><?php _e('от:', 'parfume-reviews'); ?></span>
                                                    <span class="price-value"><?php echo esc_html($lowest_store['price']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <div class="parfume-meta">
                                            <?php if ($card_settings['show_availability']): ?>
                                                <div class="availability">
                                                    <?php if (parfume_reviews_is_available(get_the_ID())): ?>
                                                        <span class="available">
                                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                                            </svg>
                                                            <?php _e('Наличен', 'parfume-reviews'); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="unavailable">
                                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                                            </svg>
                                                            <?php _e('Не е наличен', 'parfume-reviews'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($card_settings['show_shipping']): ?>
                                                <?php 
                                                $shipping = parfume_reviews_get_cheapest_shipping(get_the_ID());
                                                if ($shipping): ?>
                                                    <div class="shipping">
                                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                            <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7zm1.294 7.456A1.999 1.999 0 0 1 4.732 11h5.536a2.01 2.01 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456zM12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12v4zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                                                        </svg>
                                                        <?php echo esc_html($shipping); ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="parfume-actions">
                                            <a href="<?php the_permalink(); ?>" class="view-details-btn">
                                                <?php _e('Виж детайли', 'parfume-reviews'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php 
                        // Pagination
                        the_posts_pagination(array(
                            'mid_size' => 2,
                            'prev_text' => __('‹ Предишна', 'parfume-reviews'),
                            'next_text' => __('Следваща ›', 'parfume-reviews'),
                            'screen_reader_text' => __('Навигация в резултатите', 'parfume-reviews'),
                        )); 
                        ?>
                        
                    <?php else: ?>
                        <div class="no-perfumes">
                            <div class="no-results-icon">🔍</div>
                            <h2><?php _e('Няма намерени парфюми', 'parfume-reviews'); ?></h2>
                            <p><?php _e('Опитайте да промените филтрите или търсенето си.', 'parfume-reviews'); ?></p>
                            <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="reset-filters-btn">
                                <?php _e('Изчисти всички филтри', 'parfume-reviews'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>
</div>

<!-- Comparison floating button -->
<div id="comparison-floating-btn" class="comparison-floating" style="display: none;">
    <button class="comparison-toggle">
        <span class="comparison-icon">⚖️</span>
        <span class="comparison-text"><?php _e('Сравни', 'parfume-reviews'); ?></span>
        <span class="comparison-count">0</span>
    </button>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Archive Header */
.archive-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
}

.archive-title {
    font-size: 3em;
    margin-bottom: 15px;
    text-align: center;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.archive-description {
    text-align: center;
    font-size: 1.2em;
    margin-bottom: 40px;
    opacity: 0.9;
}

.archive-stats {
    margin-bottom: 40px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    text-align: center;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.stat-label {
    font-size: 1.1em;
    opacity: 0.9;
}

.quick-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.2);
    padding: 12px 20px;
    border-radius: 25px;
    text-decoration: none;
    color: white;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.quick-link:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    color: white;
}

.link-icon {
    font-size: 1.2em;
}

/* Archive Layout */
.archive-layout.has-sidebar {
    display: flex;
    gap: 30px;
}

.archive-layout.full-width .archive-main {
    width: 100%;
}

.archive-sidebar {
    flex: 0 0 280px;
}

.archive-main {
    flex: 1;
}

/* Sidebar Sections */
.sidebar-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.sidebar-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 1.1em;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 8px;
}

.brands-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.brand-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    background: white;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.brand-item:hover {
    background: #0073aa;
    color: white;
    transform: translateX(5px);
}

.brand-logo {
    width: 24px;
    height: 24px;
    object-fit: contain;
    border-radius: 3px;
}

.brand-name {
    flex: 1;
    font-weight: 500;
}

.brand-count {
    font-size: 0.85em;
    opacity: 0.8;
}

.recent-list, .top-rated-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.recent-item, .top-rated-item {
    display: flex;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.recent-item:hover, .top-rated-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.recent-thumbnail {
    flex: 0 0 40px;
}

.recent-thumbnail img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.recent-info h4, .rated-info h4 {
    margin: 0 0 4px;
    font-size: 0.9em;
    line-height: 1.3;
}

.recent-date {
    font-size: 0.8em;
    color: #666;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-display .rating-stars {
    color: #ffc107;
    font-size: 0.8em;
}

.rating-number {
    font-size: 0.8em;
    font-weight: bold;
    color: #333;
}

/* Results Info */
.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.results-count {
    font-weight: 500;
    color: #333;
}

.results-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

#parfume-sort {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
}

.view-toggle {
    display: flex;
    background: white;
    border-radius: 6px;
    border: 1px solid #ddd;
    overflow: hidden;
}

.view-btn {
    padding: 8px 12px;
    border: none;
    background: transparent;
    cursor: pointer;
    color: #666;
    transition: all 0.3s ease;
}

.view-btn.active {
    background: #0073aa;
    color: white;
}

.view-btn:hover:not(.active) {
    background: #f8f9fa;
}

/* Parfume Grid */
.parfume-grid {
    display: grid;
    gap: 25px;
    margin-bottom: 40px;
}

.parfume-grid[data-columns="2"] {
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
}

.parfume-grid[data-columns="3"] {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
}

.parfume-grid[data-columns="4"] {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.parfume-grid[data-columns="5"] {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

.parfume-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

.parfume-grid[data-view="list"] .parfume-card {
    display: flex;
    padding: 20px;
}

.parfume-grid[data-view="list"] .parfume-thumbnail {
    flex: 0 0 150px;
    height: 150px;
    margin-right: 20px;
}

.parfume-grid[data-view="list"] .parfume-content {
    flex: 1;
    padding: 0;
}

.parfume-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    position: relative;
}

.parfume-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.parfume-thumbnail {
    height: 250px;
    overflow: hidden;
    position: relative;
}

.parfume-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.parfume-card:hover .parfume-thumbnail img {
    transform: scale(1.05);
}

.quick-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.parfume-card:hover .quick-actions {
    opacity: 1;
}

.quick-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.9);
    color: #333;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.quick-btn:hover {
    background: #0073aa;
    color: white;
    transform: scale(1.1);
}

.parfume-content {
    padding: 20px;
}

.parfume-title {
    margin: 0 0 10px;
    font-size: 1.2em;
    line-height: 1.3;
}

.parfume-title a {
    text-decoration: none;
    color: #333;
    transition: color 0.3s ease;
}

.parfume-title a:hover {
    color: #0073aa;
}

.parfume-brand {
    margin-bottom: 8px;
}

.parfume-brand a {
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9em;
}

.parfume-brand a:hover {
    text-decoration: underline;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.rating-stars {
    color: #ffc107;
    font-size: 1.1em;
}

.parfume-excerpt {
    color: #666;
    font-size: 0.9em;
    line-height: 1.5;
    margin-bottom: 15px;
}

.parfume-price {
    margin-bottom: 15px;
    padding: 8px 12px;
    background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
    border-radius: 6px;
    border-left: 3px solid #4CAF50;
}

.price-label {
    font-size: 0.85em;
    color: #666;
    margin-right: 5px;
}

.price-value {
    font-weight: bold;
    color: #2e7d32;
    font-size: 1.1em;
}

.parfume-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.availability, .shipping {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85em;
}

.available {
    color: #4CAF50;
}

.unavailable {
    color: #f44336;
}

.shipping {
    color: #666;
}

.parfume-actions {
    text-align: center;
}

.view-details-btn {
    display: inline-block;
    background: linear-gradient(135deg, #0073aa, #005a87);
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,115,170,0.3);
}

.view-details-btn:hover {
    background: linear-gradient(135deg, #005a87, #004466);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,115,170,0.4);
    color: white;
}

/* No Results */
.no-perfumes {
    text-align: center;
    padding: 80px 20px;
    color: #666;
}

.no-results-icon {
    font-size: 4em;
    margin-bottom: 20px;
}

.no-perfumes h2 {
    margin-bottom: 15px;
    color: #333;
}

.reset-filters-btn {
    display: inline-block;
    background: #0073aa;
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    margin-top: 20px;
    transition: background 0.3s ease;
}

.reset-filters-btn:hover {
    background: #005a87;
    color: white;
}

/* Comparison Floating Button */
.comparison-floating {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.comparison-toggle {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 15px 25px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
}

.comparison-toggle:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.6);
}

.comparison-count {
    background: #ff4757;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8em;
    margin-left: 5px;
}

/* Responsive */
@media (max-width: 768px) {
    .archive-layout.has-sidebar {
        flex-direction: column;
    }
    
    .archive-sidebar {
        flex: 0 0 auto;
        order: 1;
    }
    
    .archive-main {
        order: 2;
    }
    
    .parfume-grid[data-columns="2"],
    .parfume-grid[data-columns="3"],
    .parfume-grid[data-columns="4"],
    .parfume-grid[data-columns="5"] {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    
    .results-info {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 20px;
    }
    
    .stat-number {
        font-size: 2em;
    }
    
    .archive-title {
        font-size: 2em;
    }
    
    .quick-links {
        gap: 10px;
    }
    
    .quick-link {
        padding: 8px 15px;
        font-size: 0.9em;
    }
    
    .comparison-floating {
        bottom: 20px;
        right: 20px;
    }
    
    .comparison-toggle {
        padding: 12px 20px;
        border-radius: 40px;
    }
}

@media (max-width: 480px) {
    .parfume-grid[data-columns="2"],
    .parfume-grid[data-columns="3"],
    .parfume-grid[data-columns="4"],
    .parfume-grid[data-columns="5"] {
        grid-template-columns: 1fr;
    }
    
    .container {
        padding: 0 15px;
    }
    
    .archive-header {
        padding: 40px 0;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .quick-links {
        flex-direction: column;
        gap: 8px;
    }
    
    .parfume-grid[data-view="list"] .parfume-card {
        flex-direction: column;
    }
    
    .parfume-grid[data-view="list"] .parfume-thumbnail {
        margin-right: 0;
        margin-bottom: 15px;
        height: 200px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const viewButtons = document.querySelectorAll('.view-btn');
    const parfumeGrid = document.querySelector('.parfume-grid');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active state
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update grid view
            parfumeGrid.dataset.view = view;
            
            // Save preference
            localStorage.setItem('parfume_archive_view', view);
        });
    });
    
    // Restore saved view preference
    const savedView = localStorage.getItem('parfume_archive_view');
    if (savedView) {
        const viewBtn = document.querySelector(`[data-view="${savedView}"]`);
        if (viewBtn) {
            viewBtn.click();
        }
    }
    
    // Comparison functionality
    let comparisonItems = JSON.parse(localStorage.getItem('parfume_comparison') || '[]');
    const comparisonBtn = document.getElementById('comparison-floating-btn');
    const comparisonCount = document.querySelector('.comparison-count');
    
    function updateComparisonUI() {
        if (comparisonItems.length > 0) {
            comparisonBtn.style.display = 'block';
            comparisonCount.textContent = comparisonItems.length;
        } else {
            comparisonBtn.style.display = 'none';
        }
        
        // Update button states
        document.querySelectorAll('.add-to-comparison').forEach(btn => {
            const perfumeId = btn.dataset.perfumeId;
            if (comparisonItems.includes(perfumeId)) {
                btn.classList.add('added');
                btn.title = '<?php _e("Премахни от сравнение", "parfume-reviews"); ?>';
            } else {
                btn.classList.remove('added');
                btn.title = '<?php _e("Добави за сравнение", "parfume-reviews"); ?>';
            }
        });
    }
    
    // Add to comparison
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-comparison')) {
            e.preventDefault();
            const btn = e.target.closest('.add-to-comparison');
            const perfumeId = btn.dataset.perfumeId;
            
            if (comparisonItems.includes(perfumeId)) {
                // Remove from comparison
                comparisonItems = comparisonItems.filter(id => id !== perfumeId);
            } else {
                // Add to comparison (max 4 items)
                if (comparisonItems.length >= 4) {
                    alert('<?php _e("Можете да сравните максимум 4 парфюма едновременно", "parfume-reviews"); ?>');
                    return;
                }
                comparisonItems.push(perfumeId);
            }
            
            localStorage.setItem('parfume_comparison', JSON.stringify(comparisonItems));
            updateComparisonUI();
        }
    });
    
    // Initialize comparison UI
    updateComparisonUI();
    
    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Smooth scrolling for quick actions
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php get_footer(); ?>