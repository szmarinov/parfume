<?php
/**
 * Archive template for parfume post type
 * Показва homepage на плъгина на /parfiumi/
 * 
 * Template: templates/archive-parfume.php
 * МОДИФИЦИРАН: Добавени homepage секции в началото
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Получаваме настройките
$settings = get_option('parfume_reviews_settings', array());
$show_sidebar = isset($settings['show_archive_sidebar']) ? $settings['show_archive_sidebar'] : 1;
$posts_per_page = isset($settings['archive_posts_per_page']) ? $settings['archive_posts_per_page'] : 12;
$grid_columns = isset($settings['archive_grid_columns']) ? $settings['archive_grid_columns'] : 3;
?>

<div class="parfume-archive parfume-homepage">
    <!-- НОВИ HOMEPAGE СЕКЦИИ - ДОБАВЕНИ В НАЧАЛОТО -->
    
    <?php
    // Показваме homepage секциите само ако не е pagination (първа страница)
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
    if ($paged <= 1):
    ?>
        <!-- Homepage секции -->
        <div class="homepage-sections">
            <?php
            // Проверяваме дали функциите съществуват и ги извикваме
            if (function_exists('parfume_reviews_display_men_perfumes_section')) {
                parfume_reviews_display_men_perfumes_section();
            }
            
            if (function_exists('parfume_reviews_display_women_perfumes_section')) {
                parfume_reviews_display_women_perfumes_section();
            }
            
            if (function_exists('parfume_reviews_display_featured_brands_section')) {
                parfume_reviews_display_featured_brands_section();
            }
            
            if (function_exists('parfume_reviews_display_arabic_perfumes_section')) {
                parfume_reviews_display_arabic_perfumes_section();
            }
            
            if (function_exists('parfume_reviews_display_latest_perfumes_section')) {
                parfume_reviews_display_latest_perfumes_section();
            }
            
            if (function_exists('parfume_reviews_display_blog_section')) {
                parfume_reviews_display_blog_section();
            }
            
            if (function_exists('parfume_reviews_display_description_section')) {
                parfume_reviews_display_description_section();
            }
            ?>
        </div>
        
        <!-- Разделител между homepage секции и архив -->
        <div class="section-divider">
            <div class="container">
                <hr style="margin: 60px 0; border: none; border-top: 2px solid #eee;">
            </div>
        </div>
    <?php endif; ?>
    
    <!-- ОРИГИНАЛЕН ARCHIVE CONTENT -->
    <div class="archive-header">
        <div class="container">
            <h1 class="archive-title">
                <?php 
                if ($paged > 1) {
                    printf(__('Всички Парфюми - Страница %d', 'parfume-reviews'), $paged);
                } else {
                    _e('Всички Парфюми', 'parfume-reviews'); 
                }
                ?>
            </h1>
            
            <?php if ($paged <= 1): ?>
                <div class="archive-description">
                    <p><?php _e('Открийте най-добрите парфюми от цял свят. Разгледайте нашата колекция от ексклузивни аромати.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="archive-stats">
                <?php
                global $wp_query;
                $total = $wp_query->found_posts;
                printf(_n('Намерен %d парфюм', 'Намерени %d парфюма', $total, 'parfume-reviews'), number_format($total));
                ?>
            </div>
        </div>
    </div>

    <div class="archive-content">
        <div class="container">
            <div class="archive-layout <?php echo $show_sidebar ? 'has-sidebar' : 'full-width'; ?>">
                
                <?php if ($show_sidebar): ?>
                <aside class="archive-sidebar">
                    <div class="sidebar-content">
                        <div class="widget widget-filters">
                            <h3><?php _e('Филтрирай парфюми', 'parfume-reviews'); ?></h3>
                            
                            <!-- Search -->
                            <div class="filter-group">
                                <label><?php _e('Търсене', 'parfume-reviews'); ?></label>
                                <input type="text" id="parfume-search" placeholder="<?php _e('Търси парфюм...', 'parfume-reviews'); ?>">
                            </div>
                            
                            <!-- Brand Filter -->
                            <div class="filter-group">
                                <label><?php _e('Марка', 'parfume-reviews'); ?></label>
                                <select id="brand-filter">
                                    <option value=""><?php _e('Всички марки', 'parfume-reviews'); ?></option>
                                    <?php
                                    $brands = get_terms(array(
                                        'taxonomy' => 'marki',
                                        'hide_empty' => true,
                                        'orderby' => 'name'
                                    ));
                                    foreach ($brands as $brand):
                                    ?>
                                        <option value="<?php echo esc_attr($brand->slug); ?>"><?php echo esc_html($brand->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Gender Filter -->
                            <div class="filter-group">
                                <label><?php _e('Пол', 'parfume-reviews'); ?></label>
                                <select id="gender-filter">
                                    <option value=""><?php _e('Всички', 'parfume-reviews'); ?></option>
                                    <?php
                                    $genders = get_terms(array(
                                        'taxonomy' => 'gender',
                                        'hide_empty' => true
                                    ));
                                    foreach ($genders as $gender):
                                    ?>
                                        <option value="<?php echo esc_attr($gender->slug); ?>"><?php echo esc_html($gender->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="filter-group">
                                <label><?php _e('Ценова категория', 'parfume-reviews'); ?></label>
                                <select id="price-filter">
                                    <option value=""><?php _e('Всички цени', 'parfume-reviews'); ?></option>
                                    <option value="0-50"><?php _e('До 50 лв.', 'parfume-reviews'); ?></option>
                                    <option value="50-100"><?php _e('50-100 лв.', 'parfume-reviews'); ?></option>
                                    <option value="100-200"><?php _e('100-200 лв.', 'parfume-reviews'); ?></option>
                                    <option value="200+"><?php _e('Над 200 лв.', 'parfume-reviews'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Popular Brands Widget -->
                        <div class="widget popular-brands">
                            <h3><?php _e('Популярни марки', 'parfume-reviews'); ?></h3>
                            <div class="brand-cloud">
                                <?php
                                $popular_brands = get_terms(array(
                                    'taxonomy' => 'marki',
                                    'orderby' => 'count',
                                    'order' => 'DESC',
                                    'number' => 10,
                                    'hide_empty' => true
                                ));
                                
                                foreach ($popular_brands as $brand):
                                    $brand_url = get_term_link($brand);
                                ?>
                                    <a href="<?php echo esc_url($brand_url); ?>" class="brand-tag">
                                        <?php echo esc_html($brand->name); ?>
                                        <span class="count">(<?php echo $brand->count; ?>)</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </aside>
                <?php endif; ?>

                <main class="archive-main">
                    <div class="archive-controls">
                        <div class="view-controls">
                            <span><?php _e('Изглед:', 'parfume-reviews'); ?></span>
                            <button class="view-btn grid-view active" data-view="grid">
                                <span class="dashicons dashicons-grid-view"></span>
                            </button>
                            <button class="view-btn list-view" data-view="list">
                                <span class="dashicons dashicons-list-view"></span>
                            </button>
                        </div>
                        
                        <div class="sort-controls">
                            <label><?php _e('Сортирай по:', 'parfume-reviews'); ?></label>
                            <select id="sort-select">
                                <option value="date"><?php _e('Най-нови', 'parfume-reviews'); ?></option>
                                <option value="title"><?php _e('Име А-Я', 'parfume-reviews'); ?></option>
                                <option value="popularity"><?php _e('Популярност', 'parfume-reviews'); ?></option>
                                <option value="rating"><?php _e('Рейтинг', 'parfume-reviews'); ?></option>
                                <option value="price"><?php _e('Цена', 'parfume-reviews'); ?></option>
                            </select>
                        </div>
                    </div>

                    <?php if (have_posts()): ?>
                        <div class="parfume-grid" data-columns="<?php echo esc_attr($grid_columns); ?>">
                            <?php while (have_posts()): the_post(); ?>
                                <div class="parfume-card">
                                    <div class="parfume-thumbnail">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php if (has_post_thumbnail()): ?>
                                                <?php the_post_thumbnail('medium'); ?>
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <span class="dashicons dashicons-format-image"></span>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                        
                                        <?php 
                                        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                        if ($rating):
                                        ?>
                                            <div class="parfume-rating">
                                                <div class="rating-stars">
                                                    <?php 
                                                    if (function_exists('parfume_reviews_get_rating_stars')) {
                                                        echo parfume_reviews_get_rating_stars($rating);
                                                    }
                                                    ?>
                                                </div>
                                                <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="parfume-content">
                                        <h3 class="parfume-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>

                                        <?php 
                                        $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                        if (!empty($brands) && !is_wp_error($brands)): 
                                        ?>
                                            <div class="parfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                        <?php endif; ?>

                                        <?php 
                                        $price = get_post_meta(get_the_ID(), '_parfume_price', true);
                                        if ($price):
                                        ?>
                                            <div class="parfume-price">
                                                <span class="price-label"><?php _e('Цена:', 'parfume-reviews'); ?></span>
                                                <span class="price-value"><?php echo esc_html($price); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="parfume-meta">
                                            <?php 
                                            $availability = get_post_meta(get_the_ID(), '_parfume_availability', true);
                                            $availability_class = ($availability === 'in_stock') ? 'available' : 'unavailable';
                                            $availability_text = ($availability === 'in_stock') ? __('В наличност', 'parfume-reviews') : __('Няма в наличност', 'parfume-reviews');
                                            ?>
                                            <div class="availability <?php echo esc_attr($availability_class); ?>">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                <?php echo esc_html($availability_text); ?>
                                            </div>
                                            
                                            <div class="shipping">
                                                <span class="dashicons dashicons-cart"></span>
                                                <?php _e('Безплатна доставка', 'parfume-reviews'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <?php
                        // Pagination
                        the_posts_pagination(array(
                            'prev_text' => '<i class="icon-arrow-left"></i> ' . __('Предишна', 'parfume-reviews'),
                            'next_text' => __('Следваща', 'parfume-reviews') . ' <i class="icon-arrow-right"></i>',
                            'type' => 'list',
                            'end_size' => 2,
                            'mid_size' => 2,
                        ));
                        ?>

                    <?php else: ?>
                        <div class="no-parfumes">
                            <h2><?php _e('Няма намерени парфюми', 'parfume-reviews'); ?></h2>
                            <p><?php _e('Няма парфюми, които да отговарят на вашите критерии.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>
</div>

<style>
/* Homepage секции стилове */
.homepage-sections {
    margin-bottom: 40px;
}

.homepage-sections .homepage-section {
    margin-bottom: 60px;
}

.section-divider {
    margin: 40px 0;
}

/* Основни стилове запазени */
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
    position: relative;
}

.parfume-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: #f8f9fa;
    color: #dee2e6;
    font-size: 48px;
}

.parfume-rating {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    padding: 5px 8px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8em;
}

.rating-stars {
    color: #ffc107;
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

/* Archive Header */
.archive-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
}

.archive-title {
    font-size: 3rem;
    margin-bottom: 20px;
    font-weight: 700;
}

.archive-description {
    font-size: 1.2rem;
    margin-bottom: 20px;
    opacity: 0.9;
}

.archive-stats {
    font-size: 1.1rem;
    opacity: 0.8;
}

/* Controls */
.archive-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-controls, .sort-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.view-btn {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.view-btn.active, .view-btn:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

/* Sidebar */
.archive-sidebar {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: fit-content;
}

.widget {
    margin-bottom: 30px;
}

.widget h3 {
    margin-bottom: 15px;
    color: #333;
    font-size: 1.1em;
}

.filter-group {
    margin-bottom: 15px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #666;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.brand-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.brand-tag {
    display: inline-block;
    background: #f8f9fa;
    color: #666;
    padding: 6px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.brand-tag:hover {
    background: #0073aa;
    color: white;
}

.brand-tag .count {
    opacity: 0.7;
    font-size: 0.8em;
}

/* Responsive */
@media (max-width: 768px) {
    .archive-layout.has-sidebar {
        flex-direction: column;
    }
    
    .archive-sidebar {
        order: 2;
    }
    
    .parfume-grid[data-columns="2"],
    .parfume-grid[data-columns="3"],
    .parfume-grid[data-columns="4"],
    .parfume-grid[data-columns="5"] {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    
    .archive-controls {
        flex-direction: column;
        gap: 15px;
    }
    
    .archive-title {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .parfume-grid {
        grid-template-columns: 1fr;
    }
    
    .container {
        padding: 0 15px;
    }
}
</style>

<?php get_footer(); ?>