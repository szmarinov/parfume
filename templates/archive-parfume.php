<?php
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
?>

<div class="parfume-archive">
    <header class="archive-header">
        <div class="container">
            <h1 class="archive-title"><?php echo $archive_title; ?></h1>
            
            <?php if (!empty($settings['archive_description'])): ?>
                <div class="archive-description">
                    <?php echo wpautop($settings['archive_description']); ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="archive-content">
        <div class="container">
            <div class="archive-layout <?php echo $show_sidebar ? 'has-sidebar' : 'full-width'; ?>">
                <?php if ($show_sidebar): ?>
                    <aside class="archive-sidebar">
                        <?php echo do_shortcode('[parfume_filters]'); ?>
                        
                        <div class="popular-brands">
                            <h3><?php _e('Популярни марки', 'parfume-reviews'); ?></h3>
                            <?php
                            $brands = get_terms(array(
                                'taxonomy' => 'marki',
                                'orderby' => 'count',
                                'order' => 'DESC',
                                'number' => 10,
                                'hide_empty' => true,
                            ));
                            
                            if (!empty($brands)): ?>
                                <ul>
                                    <?php foreach ($brands as $brand): ?>
                                        <li>
                                            <a href="<?php echo get_term_link($brand); ?>">
                                                <?php echo $brand->name; ?>
                                                <span class="count">(<?php echo $brand->count; ?>)</span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </aside>
                <?php endif; ?>
                
                <main class="archive-main">
                    <?php if (have_posts()): ?>
                        <div class="parfume-grid" data-columns="<?php echo esc_attr($grid_columns); ?>">
                            <?php while (have_posts()): the_post(); ?>
                                <article class="parfume-card">
                                    <?php if ($card_settings['show_image'] && has_post_thumbnail()): ?>
                                        <div class="parfume-thumbnail">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </a>
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
                                                <div class="parfume-brand"><?php echo implode(', ', $brands); ?></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                        if (!empty($rating)): ?>
                                            <div class="parfume-rating">
                                                <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                                <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($card_settings['show_price']): ?>
                                            <?php 
                                            $lowest_store = parfume_reviews_get_lowest_price(get_the_ID());
                                            if ($lowest_store): ?>
                                                <div class="parfume-price">
                                                    <span class="price-label">от:</span>
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
                                                            Наличен
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="unavailable">
                                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                                            </svg>
                                                            Не е наличен
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
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php the_posts_pagination(array(
                            'prev_text' => __('‹ Предишна', 'parfume-reviews'),
                            'next_text' => __('Следваща ›', 'parfume-reviews'),
                        )); ?>
                    <?php else: ?>
                        <p class="no-perfumes"><?php _e('Няма намерени парфюми.', 'parfume-reviews'); ?></p>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

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

.parfume-grid {
    display: grid;
    gap: 25px;
    margin-bottom: 40px;
}

.parfume-grid[data-columns="2"] {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
}

.parfume-grid[data-columns="3"] {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.parfume-grid[data-columns="4"] {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
}

.parfume-grid[data-columns="5"] {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
}

.parfume-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.parfume-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.parfume-thumbnail {
    height: 200px;
    overflow: hidden;
}

.parfume-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.parfume-content {
    padding: 20px;
}

.parfume-title {
    margin: 0 0 10px;
    font-size: 1.1em;
}

.parfume-title a {
    text-decoration: none;
    color: #333;
}

.parfume-title a:hover {
    color: #0073aa;
}

.parfume-brand {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
}

.rating-stars {
    color: #ffc107;
}

.rating-number {
    font-weight: bold;
    color: #333;
    font-size: 0.9em;
}

.parfume-price {
    margin-bottom: 15px;
    padding: 8px 12px;
    background: #e8f5e8;
    border-radius: 4px;
    border-left: 3px solid #4CAF50;
}

.price-label {
    font-size: 0.9em;
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
}

.availability, .shipping {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9em;
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

.popular-brands h3 {
    margin-bottom: 15px;
    color: #333;
}

.popular-brands ul {
    list-style: none;
    padding: 0;
}

.popular-brands li {
    margin-bottom: 8px;
}

.popular-brands a {
    text-decoration: none;
    color: #0073aa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px;
    border-radius: 3px;
    transition: background-color 0.3s ease;
}

.popular-brands a:hover {
    background: #f8f9fa;
}

.popular-brands .count {
    color: #666;
    font-size: 0.8em;
}

.no-perfumes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 1.2em;
}

/* Responsive */
@media (max-width: 768px) {
    .archive-layout.has-sidebar {
        flex-direction: column;
    }
    
    .archive-sidebar {
        flex: 0 0 auto;
    }
    
    .parfume-grid[data-columns="2"],
    .parfume-grid[data-columns="3"],
    .parfume-grid[data-columns="4"],
    .parfume-grid[data-columns="5"] {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
}
</style>

<?php get_footer(); ?>